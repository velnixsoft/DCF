<?php
// ============================================================
// process/payu_webhook.php
// Server-to-Server Webhook / IPN Handler for PayU Money / India
// Configure in PayU Merchant Dashboard / Webhook Settings:
// Webhook URL: https://yourdomain.com/process/payu_webhook.php
// ============================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/member_module.php';
require_once __DIR__ . '/../includes/payments/PaymentGatewayManager.php';

use App\Payments\PaymentGatewayManager;

// Read raw body or POST
$rawBody = file_get_contents('php://input');
if (empty($rawBody) && !empty($_POST)) {
    $rawBody = http_build_query($_POST);
}

$headers = [];
if (function_exists('getallheaders')) {
    $headers = getallheaders() ?: [];
}

$manager = new PaymentGatewayManager($pdo);
$payuAdapter = $manager->getGateway('payu');

if (!$payuAdapter) {
    http_response_code(500);
    exit('PayU adapter not configured.');
}

// Verify webhook payload & signature
$webhookResult = $payuAdapter->verifyWebhook($rawBody, $headers);

if (!$webhookResult['verified']) {
    http_response_code(400);
    error_log('PayU webhook verification failed: ' . $webhookResult['message']);
    exit('Invalid PayU Hash');
}

$orderId   = $webhookResult['order_id'];
$paymentId = $webhookResult['payment_id'] ?: $orderId;
$status    = $webhookResult['status'];
$event     = $webhookResult['event'];

// Idempotency check: skip if already processed
try {
    $chk = $pdo->prepare("SELECT id FROM payment_webhook_logs WHERE gateway = 'payu' AND (payment_id = ? OR order_id = ?) AND processed = 1");
    $chk->execute([$paymentId, $orderId]);
    if ($chk->fetch()) {
        http_response_code(200);
        echo json_encode(['status' => 'already_processed']);
        exit;
    }
} catch (PDOException $e) {
    error_log('PayU webhook idempotency check failed: ' . $e->getMessage());
}

// Log webhook event
$logId = null;
try {
    $logStmt = $pdo->prepare("
        INSERT INTO payment_webhook_logs (gateway, event_type, payment_id, order_id, payload)
        VALUES ('payu', ?, ?, ?, ?)
    ");
    $logStmt->execute([$event, $paymentId, $orderId, $rawBody]);
    $logId = $pdo->lastInsertId();
} catch (PDOException $e) {
    error_log('PayU webhook log insert failed: ' . $e->getMessage());
}

// Handle payment status
if ($status === 'SUCCESS') {
    handlePayUPaymentSuccess($pdo, $paymentId, $orderId, $webhookResult, $logId);
} elseif ($status === 'FAILED') {
    handlePayUPaymentFailed($pdo, $paymentId, $orderId);
}

http_response_code(200);
echo json_encode(['status' => 'ok']);
exit;

// ============================================================
// HANDLER: PayU Payment Success
// ============================================================
function handlePayUPaymentSuccess(PDO $pdo, ?string $paymentId, ?string $orderId, array $webhookResult, ?int $logId): void
{
    if (!$orderId) return;

    $payload = $webhookResult['payload'] ?? [];
    $donationIdFromUdf = !empty($payload['udf1']) && is_numeric($payload['udf1']) ? (int)$payload['udf1'] : 0;

    // 1. Check in Donations table
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM donations 
            WHERE (razorpay_order_id = ? OR transaction_id = ? OR id = ?) 
              AND payment_status = 'Pending'
            LIMIT 1
        ");
        $stmt->execute([$orderId, $orderId, $donationIdFromUdf]);
        $donation = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('PayU Webhook DB fetch error (donations): ' . $e->getMessage());
        $donation = null;
    }

    if ($donation) {
        try {
            $pdo->beginTransaction();

            $update = $pdo->prepare("
                UPDATE donations SET
                    payment_status  = 'Success',
                    payment_gateway = 'PayU',
                    transaction_id  = ?,
                    razorpay_payment_id = ?
                WHERE id = ? AND payment_status = 'Pending'
            ");
            $update->execute([$paymentId, $paymentId, $donation['id']]);

            if ($update->rowCount() > 0) {
                $receipt_no = generateNextReceiptNumber($pdo);
                $pdo->prepare("UPDATE donations SET receipt_no = ? WHERE id = ?")
                    ->execute([$receipt_no, $donation['id']]);

                if (!empty($donation['project_id'])) {
                    $proj = $pdo->prepare("UPDATE projects SET raised_amount = raised_amount + ? WHERE id = ?");
                    $proj->execute([$donation['amount'], $donation['project_id']]);
                }
            }

            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log('PayU Webhook donation update failed: ' . $e->getMessage());
            return;
        }

        if (!empty($donation['donor_email'])) {
            sendPayUReceiptEmail($pdo, $donation, $receipt_no ?? ($donation['receipt_no'] ?? 'REC-' . time()));
        }
    }

    // 2. Check in Join Applications table
    try {
        $joinStmt = $pdo->prepare("
            SELECT * FROM join_applications 
            WHERE (razorpay_order_id = ? OR application_no = ? OR id = ?)
              AND payment_status = 'pending'
            LIMIT 1
        ");
        $joinStmt->execute([$orderId, $orderId, $donationIdFromUdf]);
        $app = $joinStmt->fetch(PDO::FETCH_ASSOC);
        if ($app) {
            $pdo->prepare("
                UPDATE join_applications SET
                    payment_status = 'paid',
                    payment_method = 'PayU',
                    transaction_id = ?,
                    razorpay_payment_id = ?,
                    updated_at = NOW()
                WHERE id = ?
            ")->execute([$paymentId, $paymentId, $app['id']]);
        }
    } catch (PDOException $e) {
        error_log('PayU Webhook join_applications error: ' . $e->getMessage());
    }

    markPayUWebhookProcessed($pdo, $logId);
}

// ============================================================
// HANDLER: PayU Payment Failed
// ============================================================
function handlePayUPaymentFailed(PDO $pdo, ?string $paymentId, ?string $orderId): void
{
    if (!$orderId) return;

    try {
        $stmt = $pdo->prepare("
            UPDATE donations SET
                payment_status  = 'Failed',
                payment_gateway = 'PayU',
                transaction_id  = ?
            WHERE (razorpay_order_id = ? OR transaction_id = ?) AND payment_status = 'Pending'
        ");
        $stmt->execute([$paymentId, $orderId, $orderId]);
    } catch (PDOException $e) {
        error_log('PayU Webhook donation failed update: ' . $e->getMessage());
    }
}

// ============================================================
// Mark webhook log as processed
// ============================================================
function markPayUWebhookProcessed(PDO $pdo, ?int $logId): void
{
    if (!$logId) return;
    try {
        $pdo->prepare("UPDATE payment_webhook_logs SET processed = 1 WHERE id = ?")->execute([$logId]);
    } catch (PDOException $e) {
        error_log('PayU Webhook mark processed failed: ' . $e->getMessage());
    }
}

// ============================================================
// Send receipt email helper
// ============================================================
function sendPayUReceiptEmail(PDO $pdo, array $donation, string $receipt_no): void
{
    try {
        $settings = [];
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    } catch (\Throwable $e) {
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
        <p>Thank you for your generous donation of <strong>INR " . number_format((float)($donation['amount'] ?? 0), 2) . "</strong> via PayU.</p>
        <p><strong>Receipt No:</strong> " . htmlspecialchars($receipt_no) . "</p>
        <p>
            <a href='{$receipt_url}' style='background:#16a34a;color:#fff;padding:10px 20px;
            text-decoration:none;border-radius:5px;display:inline-block;margin-top:10px;'>
            Download Your Receipt
            </a>
        </p>
        <p>Your support makes a difference!</p>
    ";

    mm_send_email(
        $settings,
        $donation['donor_email'],
        $donation['donor_name'] ?? 'Donor',
        'Donation Receipt - ' . $receipt_no,
        $emailBody
    );
}
