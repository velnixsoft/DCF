<?php
// ============================================================
// process/verify_join_payment.php
// Verifies Razorpay payment signature and updates join_applications
// Author: VELNIX SOFT / Antigravity AI
// Date: 2026-09-12
// ============================================================

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/razorpay.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$razorpayPaymentId = cleanInput($_POST['razorpay_payment_id'] ?? '');
$razorpayOrderId   = cleanInput($_POST['razorpay_order_id'] ?? '');
$razorpaySignature = cleanInput($_POST['razorpay_signature'] ?? '');
$applicationId     = filter_input(INPUT_POST, 'application_id', FILTER_VALIDATE_INT);
$applicationNo     = cleanInput($_POST['application_no'] ?? '');

if (!$razorpayPaymentId || !$razorpayOrderId || !$razorpaySignature) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Incomplete payment payload received from Razorpay.']);
    exit;
}

// ── Verify Signature ────────────────────────────────────────
$credentials = getRazorpayCredentials($pdo, 'donation');
$generatedSignature = hash_hmac(
    'sha256',
    $razorpayOrderId . '|' . $razorpayPaymentId,
    $credentials['key_secret']
);

if (!hash_equals($generatedSignature, $razorpaySignature)) {
    error_log("Razorpay signature mismatch for join order: $razorpayOrderId");
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Security signature verification failed. Please contact support.']);
    exit;
}

// ── Locate and Update Application ───────────────────────────
try {
    if ($applicationId) {
        $stmt = $pdo->prepare("SELECT * FROM join_applications WHERE id = ? AND razorpay_order_id = ? LIMIT 1");
        $stmt->execute([$applicationId, $razorpayOrderId]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM join_applications WHERE (application_no = ? OR razorpay_order_id = ?) LIMIT 1");
        $stmt->execute([$applicationNo, $razorpayOrderId]);
    }

    $app = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$app) {
        error_log("Join application not found for order: $razorpayOrderId");
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Application record could not be matched.']);
        exit;
    }

    // Update status to paid and approved / reviewed
    $updateStmt = $pdo->prepare("UPDATE join_applications SET 
        payment_status = 'paid',
        payment_method = 'Razorpay',
        transaction_id = ?,
        razorpay_payment_id = ?,
        razorpay_signature = ?,
        status = CASE WHEN application_type = 'join_foundation' THEN 'approved' ELSE 'reviewed' END,
        updated_at = NOW()
        WHERE id = ?");
    
    $updateStmt->execute([
        $razorpayPaymentId,
        $razorpayPaymentId,
        $razorpaySignature,
        $app['id']
    ]);

    // Clear session pending values
    unset($_SESSION['pending_join_app_id'], $_SESSION['pending_join_app_no']);

    // Send email notification if available
    try {
        if (!empty($app['email'])) {
            $siteName = $settings['site_name'] ?? 'Jaysmrutti Foundation';
            $typeLabel = match ($app['application_type']) {
                'join_project' => 'Project Volunteer Application',
                'job_application' => 'Job Vacancy Application',
                default => 'Foundation Membership Application'
            };

            $mailSubject = "Application Confirmed - {$app['application_no']} | {$siteName}";
            $mailBody = "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;'>
                <h2 style='color: #0F8B8D; margin-top: 0;'>Application & Payment Confirmed!</h2>
                <p>Dear <strong>" . htmlspecialchars($app['applicant_name']) . "</strong>,</p>
                <p>Thank you for connecting with <strong>{$siteName}</strong>. Your application has been successfully submitted and fee payment has been confirmed.</p>
                <table style='width: 100%; border-collapse: collapse; margin: 15px 0;'>
                    <tr style='background: #f8fafc;'><td style='padding: 8px; border: 1px solid #cbd5e1;'><strong>Application No:</strong></td><td style='padding: 8px; border: 1px solid #cbd5e1; font-weight: bold; color: #0F8B8D;'>{$app['application_no']}</td></tr>
                    <tr><td style='padding: 8px; border: 1px solid #cbd5e1;'><strong>Type:</strong></td><td style='padding: 8px; border: 1px solid #cbd5e1;'>{$typeLabel}</td></tr>
                    <tr style='background: #f8fafc;'><td style='padding: 8px; border: 1px solid #cbd5e1;'><strong>Amount Paid:</strong></td><td style='padding: 8px; border: 1px solid #cbd5e1;'>₹" . number_format((float)$app['fee_amount'], 2) . "</td></tr>
                    <tr><td style='padding: 8px; border: 1px solid #cbd5e1;'><strong>Transaction ID:</strong></td><td style='padding: 8px; border: 1px solid #cbd5e1; font-family: monospace;'>{$razorpayPaymentId}</td></tr>
                    <tr style='background: #f8fafc;'><td style='padding: 8px; border: 1px solid #cbd5e1;'><strong>Status:</strong></td><td style='padding: 8px; border: 1px solid #cbd5e1;'><span style='color: #16a34a; font-weight: bold;'>Payment Verified</span></td></tr>
                </table>
                <p>Our team will review your application details and get in touch shortly.</p>
                <p style='color: #64748b; font-size: 12px; margin-top: 25px;'>Best regards,<br>{$siteName}</p>
            </div>";

            sendNotificationEmail($pdo, $app['email'], $mailSubject, $mailBody);
        }
    } catch (Throwable $mailEx) {
        error_log('Join application confirmation email sending failed: ' . $mailEx->getMessage());
    }

    echo json_encode([
        'success' => true,
        'message' => 'Payment verified and application submitted successfully!',
        'application_no' => $app['application_no'],
        'applicant_name' => $app['applicant_name'],
        'contact' => $app['contact'],
        'email' => $app['email'] ?? '',
        'application_type' => $app['application_type'],
        'fee_amount' => (float)$app['fee_amount'],
        'payment_status' => 'paid',
        'transaction_id' => $razorpayPaymentId,
        'status' => $app['application_type'] === 'join_foundation' ? 'approved' : 'reviewed'
    ]);
} catch (Throwable $e) {
    error_log("Database verification error in verify_join_payment: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error while verifying application status.']);
}
