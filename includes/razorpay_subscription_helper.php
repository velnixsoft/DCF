<?php
// ============================================================
// includes/razorpay_subscription_helper.php
// Helper functions for Razorpay Subscriptions (Mandates / Auto-Debit)
// Follows existing codebase conventions & PDO singleton pattern.
// ============================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/razorpay.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/member_module.php';

/**
 * Convert internal frequency to Razorpay period and interval.
 */
function getRazorpayPlanPeriod(string $frequency): array
{
    $freq = strtolower(trim($frequency));
    switch ($freq) {
        case 'quarterly':
            return ['period' => 'monthly', 'interval' => 3];
        case 'half_yearly':
            return ['period' => 'monthly', 'interval' => 6];
        case 'yearly':
        case 'annually':
            return ['period' => 'yearly', 'interval' => 1];
        case 'monthly':
        default:
            return ['period' => 'monthly', 'interval' => 1];
    }
}

/**
 * Calculate the next charge date based on frequency.
 */
function calculateNextChargeDate(string $frequency, string $fromDate = 'now'): string
{
    $freq = strtolower(trim($frequency));
    $time = strtotime($fromDate) ?: time();
    switch ($freq) {
        case 'quarterly':
            return date('Y-m-d', strtotime('+3 months', $time));
        case 'half_yearly':
            return date('Y-m-d', strtotime('+6 months', $time));
        case 'yearly':
        case 'annually':
            return date('Y-m-d', strtotime('+1 year', $time));
        case 'monthly':
        default:
            return date('Y-m-d', strtotime('+1 month', $time));
    }
}

/**
 * Create or reuse a Razorpay Plan via API.
 */
function createOrGetRazorpayPlan(PDO $pdo, float $amount, string $frequency = 'monthly', string $planTitle = ''): ?string
{
    $credentials = getRazorpayCredentials($pdo, 'donation');
    $periodInfo = getRazorpayPlanPeriod($frequency);
    $amountPaise = (int)round($amount * 100);

    if ($planTitle === '') {
        $planTitle = ucfirst($frequency) . ' Contribution - INR ' . number_format($amount, 2);
    }

    $payload = [
        'period' => $periodInfo['period'],
        'interval' => $periodInfo['interval'],
        'item' => [
            'name' => $planTitle,
            'amount' => $amountPaise,
            'currency' => RAZORPAY_CURRENCY,
            'description' => 'Automated ' . $frequency . ' recurring donation',
        ],
        'notes' => [
            'module' => 'recurring_donations',
            'frequency' => $frequency,
            'amount' => $amount,
        ]
    ];

    $ch = curl_init('https://api.razorpay.com/v1/plans');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_USERPWD => $credentials['key_id'] . ':' . $credentials['key_secret'],
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);

    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($response === false || $httpCode < 200 || $httpCode >= 300) {
        error_log('Razorpay create plan failed: HTTP ' . $httpCode . ' - ' . ($curlErr ?: $response));
        return null;
    }

    $data = json_decode($response, true);
    return $data['id'] ?? null;
}

/**
 * Create a Razorpay Subscription via API.
 */
function createRazorpaySubscription(
    PDO $pdo,
    string $planId,
    array $donorData,
    int $totalCount = 0
): ?array {
    $credentials = getRazorpayCredentials($pdo, 'donation');
    
    // Default total_count (0 or empty = unlimited, Razorpay requires a count like 120 for 10 years or 12 for 1 year)
    $cycleCount = ($totalCount > 0) ? $totalCount : 120;

    $payload = [
        'plan_id' => $planId,
        'total_count' => $cycleCount,
        'quantity' => 1,
        'customer_notify' => 1,
        'notes' => [
            'module' => 'recurring_donations',
            'donor_name' => $donorData['donor_name'] ?? '',
            'donor_email' => $donorData['donor_email'] ?? '',
            'donor_mobile' => $donorData['donor_mobile'] ?? '',
            'project_id' => $donorData['project_id'] ?? 'general',
            'referral_code' => $donorData['referral_code'] ?? '',
        ]
    ];

    $ch = curl_init('https://api.razorpay.com/v1/subscriptions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_USERPWD => $credentials['key_id'] . ':' . $credentials['key_secret'],
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);

    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($response === false || $httpCode < 200 || $httpCode >= 300) {
        error_log('Razorpay create subscription failed: HTTP ' . $httpCode . ' - ' . ($curlErr ?: $response));
        return null;
    }

    return json_decode($response, true);
}

/**
 * Verify Razorpay Subscription Checkout Signature.
 */
function verifySubscriptionSignature(string $paymentId, string $subscriptionId, string $signature, string $secret): bool
{
    $payload = $paymentId . '|' . $subscriptionId;
    $expected = hash_hmac('sha256', $payload, $secret);
    return hash_equals($expected, $signature);
}

/**
 * Process a successful recurring charge cycle.
 * Inserts transaction into `recurring_donation_transactions`, creates standard row in `donations`,
 * updates project total, issues receipt, and sends receipt email.
 */
function processSubscriptionChargeSuccess(
    PDO $pdo,
    string $subscriptionId,
    string $paymentId,
    ?string $invoiceId,
    float $amount,
    string $chargeDate,
    string $rawPayload = '',
    ?string $orderId = null,
    ?string $signature = null
): ?int {
    // 1. Fetch the recurring profile
    try {
        $stmt = $pdo->prepare("SELECT * FROM recurring_donations WHERE razorpay_subscription_id = ?");
        $stmt->execute([$subscriptionId]);
        $recurring = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("SubscriptionCharge error fetch profile: " . $e->getMessage());
        return null;
    }

    if (!$recurring) {
        error_log("SubscriptionCharge error: recurring profile not found for sub_id $subscriptionId");
        return null;
    }

    // 2. Check if this payment_id has already been processed
    try {
        $check = $pdo->prepare("SELECT id, donation_id, receipt_no FROM recurring_donation_transactions WHERE razorpay_payment_id = ? AND status = 'success'");
        $check->execute([$paymentId]);
        $existing = $check->fetch(PDO::FETCH_ASSOC);
        if ($existing) {
            return (int)$existing['donation_id'];
        }
    } catch (PDOException $e) {
        error_log("SubscriptionCharge idempotency check error: " . $e->getMessage());
    }

    $cycleNumber = ((int)$recurring['completed_cycles']) + 1;
    $receiptNo = generateNextReceiptNumber($pdo);

    try {
        $pdo->beginTransaction();

        // A. Insert into master `donations` table (with is_recurring = 1 and recurring_donation_id)
        $donStmt = $pdo->prepare("
            INSERT INTO donations (
                project_id,
                donor_name,
                donor_email,
                donor_mobile,
                donor_pan,
                donor_address,
                amount,
                payment_gateway,
                transaction_id,
                razorpay_order_id,
                razorpay_payment_id,
                razorpay_signature,
                payment_status,
                receipt_no,
                referral_code,
                is_80g_eligible,
                sa_student_id,
                field_agent_id,
                recurring_donation_id,
                is_recurring
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?,
                'Razorpay',
                ?, ?, ?, ?,
                'Success',
                ?, ?, ?, ?, ?,
                ?, 1
            )
        ");

        $donStmt->execute([
            $recurring['project_id'],
            $recurring['donor_name'],
            $recurring['donor_email'],
            $recurring['donor_mobile'],
            $recurring['donor_pan'],
            $recurring['donor_address'],
            $amount,
            $paymentId,
            $orderId,
            $paymentId,
            $signature,
            $receiptNo,
            $recurring['referral_code'],
            $recurring['is_80g_eligible'],
            $recurring['sa_student_id'],
            $recurring['field_agent_id'],
            $recurring['id']
        ]);

        $donationId = (int)$pdo->lastInsertId();

        // B. Insert into `recurring_donation_transactions`
        $txStmt = $pdo->prepare("
            INSERT INTO recurring_donation_transactions (
                recurring_donation_id,
                donation_id,
                cycle_number,
                amount,
                payment_gateway,
                razorpay_subscription_id,
                razorpay_payment_id,
                razorpay_order_id,
                razorpay_signature,
                razorpay_invoice_id,
                charge_date,
                status,
                receipt_no,
                webhook_payload
            ) VALUES (
                ?, ?, ?, ?, 'Razorpay', ?, ?, ?, ?, ?, ?, 'success', ?, ?
            )
        ");

        $txStmt->execute([
            $recurring['id'],
            $donationId,
            $cycleNumber,
            $amount,
            $subscriptionId,
            $paymentId,
            $orderId,
            $signature,
            $invoiceId,
            $chargeDate,
            $receiptNo,
            $rawPayload
        ]);

        // C. Update project raised amount
        if (!empty($recurring['project_id'])) {
            $proj = $pdo->prepare("UPDATE projects SET raised_amount = raised_amount + ? WHERE id = ?");
            $proj->execute([$amount, $recurring['project_id']]);
        }

        // D. Update recurring profile status and next charge date
        $nextChargeDate = calculateNextChargeDate($recurring['frequency'], $chargeDate);
        $updRec = $pdo->prepare("
            UPDATE recurring_donations SET
                status = 'active',
                completed_cycles = completed_cycles + 1,
                last_charge_date = ?,
                next_charge_date = ?
            WHERE id = ?
        ");
        $updRec->execute([$chargeDate, $nextChargeDate, $recurring['id']]);

        $pdo->commit();

        // E. Send Automated Receipt Email
        if (!empty($recurring['donor_email'])) {
            $donationRow = [
                'id' => $donationId,
                'donor_name' => $recurring['donor_name'],
                'donor_email' => $recurring['donor_email'],
                'amount' => $amount,
                'created_at' => date('Y-m-d H:i:s')
            ];
            sendRecurringReceiptEmail($pdo, $donationRow, $receiptNo, $cycleNumber);
        }

        return $donationId;

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("processSubscriptionChargeSuccess failed: " . $e->getMessage());
        return null;
    }
}

/**
 * Update recurring profile when cancelled.
 */
function processSubscriptionCancelled(PDO $pdo, string $subscriptionId, string $reason = 'Cancelled by donor or gateway'): void
{
    try {
        $stmt = $pdo->prepare("
            UPDATE recurring_donations 
            SET status = 'stopped', cancel_reason = ?, end_date = CURRENT_DATE 
            WHERE razorpay_subscription_id = ? AND status != 'stopped'
        ");
        $stmt->execute([$reason, $subscriptionId]);
    } catch (PDOException $e) {
        error_log("processSubscriptionCancelled error: " . $e->getMessage());
    }
}

/**
 * Update recurring profile when halted / paused due to failure.
 */
function processSubscriptionHalted(PDO $pdo, string $subscriptionId, string $reason = 'Halted due to payment failures'): void
{
    try {
        $stmt = $pdo->prepare("
            UPDATE recurring_donations 
            SET status = 'paused', pause_reason = ? 
            WHERE razorpay_subscription_id = ? AND status = 'active'
        ");
        $stmt->execute([$reason, $subscriptionId]);
    } catch (PDOException $e) {
        error_log("processSubscriptionHalted error: " . $e->getMessage());
    }
}

/**
 * Update recurring profile when resumed / activated.
 */
function processSubscriptionActivated(PDO $pdo, string $subscriptionId): void
{
    try {
        $stmt = $pdo->prepare("
            UPDATE recurring_donations 
            SET status = 'active', pause_reason = NULL 
            WHERE razorpay_subscription_id = ?
        ");
        $stmt->execute([$subscriptionId]);
    } catch (PDOException $e) {
        error_log("processSubscriptionActivated error: " . $e->getMessage());
    }
}

/**
 * Send Receipt Email for Recurring Cycle.
 */
function sendRecurringReceiptEmail(PDO $pdo, array $donation, string $receiptNo, int $cycleNumber): void
{
    try {
        $settings = [];
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    } catch (Throwable $e) {
        error_log('Settings fetch failed in recurring receipt email: ' . $e->getMessage());
        return;
    }

    $receiptToken = generateDonationReceiptToken(
        $donation['id'],
        $receiptNo,
        $donation['donor_email'] ?? '',
        $donation['amount'] ?? 0,
        $donation['created_at'] ?? ''
    );
    $receipt_url = rtrim(appBaseUrl(), '/') . '/download-receipt.php?id=' . (int)$donation['id'] . '&token=' . urlencode($receiptToken);

    $emailBody = "
        <p>Dear " . htmlspecialchars($donation['donor_name'] ?? 'Donor') . ",</p>
        <p>Thank you for your ongoing support! Your recurring contribution for <strong>Cycle #{$cycleNumber}</strong> of <strong>INR " . number_format((float)($donation['amount'] ?? 0), 2) . "</strong> was successfully processed.</p>
        <p><strong>Receipt No:</strong> " . htmlspecialchars($receiptNo) . "</p>
        <p>
            <a href='{$receipt_url}' style='background:#16a34a;color:#fff;padding:10px 20px;
            text-decoration:none;border-radius:5px;display:inline-block;margin-top:10px;'>
            Download Your Receipt
            </a>
        </p>
        <p>Thank you for creating lasting impact through your regular contribution!</p>
    ";

    mm_send_email(
        $settings,
        $donation['donor_email'],
        $donation['donor_name'] ?? 'Donor',
        'Recurring Donation Receipt (Cycle #' . $cycleNumber . ') - ' . $receiptNo,
        $emailBody
    );
}

/**
 * Call Razorpay API to Pause a Subscription.
 */
function pauseRazorpaySubscription(PDO $pdo, string $subscriptionId): bool
{
    if (empty($subscriptionId) || str_starts_with($subscriptionId, 'sub_manual_')) {
        return true;
    }

    $credentials = getRazorpayCredentials($pdo, 'donation');
    $ch = curl_init('https://api.razorpay.com/v1/subscriptions/' . urlencode($subscriptionId) . '/pause');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(['pause_at' => 'now']),
        CURLOPT_USERPWD => $credentials['key_id'] . ':' . $credentials['key_secret'],
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 20,
    ]);

    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($httpCode >= 200 && $httpCode < 300);
}

/**
 * Call Razorpay API to Resume a Subscription.
 */
function resumeRazorpaySubscription(PDO $pdo, string $subscriptionId): bool
{
    if (empty($subscriptionId) || str_starts_with($subscriptionId, 'sub_manual_')) {
        return true;
    }

    $credentials = getRazorpayCredentials($pdo, 'donation');
    $ch = curl_init('https://api.razorpay.com/v1/subscriptions/' . urlencode($subscriptionId) . '/resume');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(['resume_at' => 'now']),
        CURLOPT_USERPWD => $credentials['key_id'] . ':' . $credentials['key_secret'],
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 20,
    ]);

    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($httpCode >= 200 && $httpCode < 300);
}

/**
 * Call Razorpay API to Cancel a Subscription.
 */
function cancelRazorpaySubscription(PDO $pdo, string $subscriptionId, bool $cancelAtCycleEnd = false): bool
{
    if (empty($subscriptionId) || str_starts_with($subscriptionId, 'sub_manual_')) {
        return true;
    }

    $credentials = getRazorpayCredentials($pdo, 'donation');
    $ch = curl_init('https://api.razorpay.com/v1/subscriptions/' . urlencode($subscriptionId) . '/cancel');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(['cancel_at_cycle_end' => $cancelAtCycleEnd ? 1 : 0]),
        CURLOPT_USERPWD => $credentials['key_id'] . ':' . $credentials['key_secret'],
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 20,
    ]);

    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($httpCode >= 200 && $httpCode < 300);
}

