<?php
// ============================================================
// process/razorpay_webhook.php
// Razorpay sends events to this URL automatically
// Configure in: Razorpay Dashboard -> Settings -> Webhooks
// Webhook URL: https://yourdomain.com/process/razorpay_webhook.php
// Events to enable: payment.captured, payment.failed, order.paid
// ============================================================

// NO session needed - this is a server-to-server call from Razorpay
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/razorpay.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/member_module.php';
require_once __DIR__ . '/../includes/razorpay_subscription_helper.php';

// Read raw body (must be before any output)
$raw_body = file_get_contents('php://input');

$webhook_signature = '';
if (function_exists('getallheaders')) {
    foreach (getallheaders() ?: [] as $name => $value) {
        if (strcasecmp((string) $name, 'X-Razorpay-Signature') === 0) {
            $webhook_signature = (string) $value;
            break;
        }
    }
}
if ($webhook_signature === '' && !empty($_SERVER['HTTP_X_RAZORPAY_SIGNATURE'])) {
    $webhook_signature = (string) $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'];
}

// Verify webhook signature
$expected_signature = hash_hmac('sha256', $raw_body, RAZORPAY_WEBHOOK_SECRET);

if ($webhook_signature === '' || !hash_equals($expected_signature, $webhook_signature)) {
    http_response_code(400);
    error_log('Razorpay webhook: invalid signature');
    exit('Invalid signature');
}

// Parse payload
$payload = json_decode($raw_body, true);
if (json_last_error() !== JSON_ERROR_NONE || empty($payload['event'])) {
    http_response_code(400);
    exit('Invalid JSON payload');
}

$event = $payload['event'];
$payEntity = $payload['payload']['payment']['entity'] ?? null;
$payment_id = is_array($payEntity) ? ($payEntity['id'] ?? null) : null;
$order_id   = is_array($payEntity) ? ($payEntity['order_id'] ?? null) : null;
if (!$order_id && !empty($payload['payload']['order']['entity']['id'])) {
    $order_id = $payload['payload']['order']['entity']['id'];
}

// Idempotency check: skip if already processed
try {
    $chk = $pdo->prepare("SELECT id FROM razorpay_webhook_logs WHERE payment_id = ? AND processed = 1");
    $chk->execute([$payment_id]);
    if ($chk->fetch()) {
        http_response_code(200);
        exit('Already processed');
    }
} catch (PDOException $e) {
    error_log('Webhook idempotency check failed: ' . $e->getMessage());
}

// Log webhook event
try {
    $logStmt = $pdo->prepare("
        INSERT INTO razorpay_webhook_logs (event_type, payment_id, order_id, payload)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE payload = VALUES(payload)
    ");
    $logStmt->execute([$event, $payment_id, $order_id, $raw_body]);
    $log_id = $pdo->lastInsertId();
} catch (PDOException $e) {
    error_log('Webhook log insert failed: ' . $e->getMessage());
}

// Handle events
switch ($event) {
    case 'payment.captured':
    case 'order.paid':
        handlePaymentSuccess($pdo, $payment_id, $order_id, $payload, $log_id ?? null);
        break;

    case 'payment.failed':
        handlePaymentFailed($pdo, $payment_id, $order_id);
        break;

    case 'subscription.charged':
        handleSubscriptionCharged($pdo, $payload, $log_id ?? null);
        break;

    case 'subscription.cancelled':
        handleSubscriptionCancelled($pdo, $payload, $log_id ?? null);
        break;

    case 'subscription.halted':
    case 'subscription.paused':
        handleSubscriptionHalted($pdo, $payload, $log_id ?? null);
        break;

    case 'subscription.resumed':
    case 'subscription.activated':
        handleSubscriptionActivated($pdo, $payload, $log_id ?? null);
        break;

    default:
        error_log("Razorpay webhook: unhandled event '$event'");
        break;
}

http_response_code(200);
echo json_encode(['status' => 'ok']);
exit;

// ============================================================
// HANDLER: Payment Success
// ============================================================
function handlePaymentSuccess(PDO $pdo, ?string $payment_id, ?string $order_id, array $payload, ?int $log_id): void
{
    if (!$payment_id || !$order_id) return;

    try {
        $stmt = $pdo->prepare("SELECT * FROM donations WHERE razorpay_order_id = ? AND payment_status = 'Pending'");
        $stmt->execute([$order_id]);
        $donation = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Webhook: DB fetch error: ' . $e->getMessage());
        return;
    }

    if (!$donation) {
        error_log("Webhook: No pending donation found for order_id $order_id (may already be Success)");
        markWebhookProcessed($pdo, $log_id);
        return;
    }

    try {
        $pdo->beginTransaction();

        $update = $pdo->prepare("
            UPDATE donations SET
                payment_status       = 'Success',
                razorpay_payment_id  = ?,
                transaction_id       = ?
            WHERE id = ? AND payment_status = 'Pending'
        ");
        $update->execute([$payment_id, $payment_id, $donation['id']]);

        if ($update->rowCount() === 0) {
            $pdo->rollBack();
            markWebhookProcessed($pdo, $log_id);
            return;
        }

        $receipt_no = generateNextReceiptNumber($pdo);
        $pdo->prepare("UPDATE donations SET receipt_no = ? WHERE id = ?")
            ->execute([$receipt_no, $donation['id']]);

        if (!empty($donation['project_id'])) {
            $proj = $pdo->prepare("UPDATE projects SET raised_amount = raised_amount + ? WHERE id = ?");
            $proj->execute([$donation['amount'], $donation['project_id']]);
        }

        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('Webhook: DB update failed: ' . $e->getMessage());
        return;
    }

    if (!empty($donation['donor_email'])) {
        sendWebhookReceiptEmail($pdo, $donation, $receipt_no);
    }

    markWebhookProcessed($pdo, $log_id);
}

// ============================================================
// HANDLER: Payment Failed
// ============================================================
function handlePaymentFailed(PDO $pdo, ?string $payment_id, ?string $order_id): void
{
    if (!$order_id) return;

    try {
        $stmt = $pdo->prepare("
            UPDATE donations SET
                payment_status      = 'Failed',
                razorpay_payment_id = ?
            WHERE razorpay_order_id = ? AND payment_status = 'Pending'
        ");
        $stmt->execute([$payment_id, $order_id]);
    } catch (PDOException $e) {
        error_log('Webhook: Failed status update error: ' . $e->getMessage());
    }
}

// ============================================================
// Mark webhook log as processed
// ============================================================
function markWebhookProcessed(PDO $pdo, ?int $log_id): void
{
    if (!$log_id) return;
    try {
        $pdo->prepare("UPDATE razorpay_webhook_logs SET processed = 1 WHERE id = ?")->execute([$log_id]);
    } catch (PDOException $e) {
        error_log('Webhook: mark processed failed: ' . $e->getMessage());
    }
}

// ============================================================
// Send receipt email from webhook context
// ============================================================
function sendWebhookReceiptEmail(PDO $pdo, array $donation, string $receipt_no): void
{
    try {
        $settings = [];
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    } catch (\Throwable $e) {
        error_log('Settings fetch failed in webhook: ' . $e->getMessage());
        return;
    }

    $receiptToken = generateDonationReceiptToken(
        $donation['id'],
        $receipt_no,
        $donation['donor_email'] ?? '',
        $donation['amount'] ?? 0,
        $donation['created_at'] ?? ''
    );
    $receipt_url = rtrim(appBaseUrl(), '/') . '/download-receipt.php?id=' . (int) $donation['id'] . '&token=' . urlencode($receiptToken);

    $emailBody = "
        <p>Dear " . htmlspecialchars($donation['donor_name'] ?? 'Donor') . ",</p>
        <p>Thank you for your generous donation of <strong>INR " . number_format((float)($donation['amount'] ?? 0), 2) . "</strong>.</p>
        <p><strong>Receipt No:</strong> " . htmlspecialchars($receipt_no) . "</p>
        <p>
            <a href='{$receipt_url}' style='background:#16a34a;color:#fff;padding:10px 20px;
            text-decoration:none;border-radius:5px;display:inline-block;margin-top:10px;'>
            Download Your Receipt
            </a>
        </p>
        <p>Your support makes a difference!</p>
    ";

    $sent = mm_send_email(
        $settings,
        $donation['donor_email'],
        $donation['donor_name'] ?? 'Donor',
        'Donation Receipt - ' . $receipt_no,
        $emailBody
    );

    if (!$sent) {
        error_log('Webhook receipt email failed: ' . ($GLOBALS['mm_last_mail_error'] ?? 'unknown error'));
    }
}

// ============================================================
// HANDLER: Subscription Charged (Recurring Cycle Payment Success)
// ============================================================
function handleSubscriptionCharged(PDO $pdo, array $payload, ?int $log_id): void
{
    $subEntity = $payload['payload']['subscription']['entity'] ?? null;
    $payEntity = $payload['payload']['payment']['entity'] ?? null;

    if (!$subEntity || !$payEntity) {
        error_log('Webhook subscription.charged: missing subscription or payment entity');
        markWebhookProcessed($pdo, $log_id);
        return;
    }

    $subscriptionId = (string)($subEntity['id'] ?? '');
    $paymentId      = (string)($payEntity['id'] ?? '');
    $invoiceId      = !empty($payEntity['invoice_id']) ? (string)$payEntity['invoice_id'] : null;
    $orderId        = !empty($payEntity['order_id']) ? (string)$payEntity['order_id'] : null;
    
    $amountPaise    = (float)($payEntity['amount'] ?? 0);
    $amount         = $amountPaise > 0 ? ($amountPaise / 100) : ((float)($subEntity['item']['amount'] ?? 0) / 100);
    $chargeDate     = !empty($payEntity['created_at']) ? date('Y-m-d', $payEntity['created_at']) : date('Y-m-d');

    processSubscriptionChargeSuccess(
        $pdo,
        $subscriptionId,
        $paymentId,
        $invoiceId,
        $amount,
        $chargeDate,
        json_encode($payload),
        $orderId
    );

    markWebhookProcessed($pdo, $log_id);
}

// ============================================================
// HANDLER: Subscription Cancelled
// ============================================================
function handleSubscriptionCancelled(PDO $pdo, array $payload, ?int $log_id): void
{
    $subEntity = $payload['payload']['subscription']['entity'] ?? null;
    if ($subEntity && !empty($subEntity['id'])) {
        processSubscriptionCancelled($pdo, $subEntity['id'], 'Cancelled by donor or gateway webhook');
    }
    markWebhookProcessed($pdo, $log_id);
}

// ============================================================
// HANDLER: Subscription Halted / Paused
// ============================================================
function handleSubscriptionHalted(PDO $pdo, array $payload, ?int $log_id): void
{
    $subEntity = $payload['payload']['subscription']['entity'] ?? null;
    if ($subEntity && !empty($subEntity['id'])) {
        processSubscriptionHalted($pdo, $subEntity['id'], 'Halted/Paused due to repeated payment failures');
    }
    markWebhookProcessed($pdo, $log_id);
}

// ============================================================
// HANDLER: Subscription Activated / Resumed
// ============================================================
function handleSubscriptionActivated(PDO $pdo, array $payload, ?int $log_id): void
{
    $subEntity = $payload['payload']['subscription']['entity'] ?? null;
    if ($subEntity && !empty($subEntity['id'])) {
        processSubscriptionActivated($pdo, $subEntity['id']);
    }
    markWebhookProcessed($pdo, $log_id);
}

