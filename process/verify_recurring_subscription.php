<?php
// ============================================================
// process/verify_recurring_subscription.php
// Step 2: Called via AJAX after Razorpay Subscription Checkout success
// Verifies subscription signature, activates profile, records 1st cycle, issues receipt
// Follows existing verify_razorpay_payment.php conventions.
// ============================================================

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/razorpay.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/razorpay_subscription_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// ── Collect POST data from Razorpay checkout handler ────────
$razorpay_payment_id      = cleanInput($_POST['razorpay_payment_id'] ?? '');
$razorpay_subscription_id = cleanInput($_POST['razorpay_subscription_id'] ?? '');
$razorpay_signature       = cleanInput($_POST['razorpay_signature'] ?? '');
$recurring_id             = filter_input(INPUT_POST, 'recurring_id', FILTER_VALIDATE_INT);

if (!$recurring_id && !empty($_SESSION['pending_recurring_id'])) {
    $recurring_id = (int)$_SESSION['pending_recurring_id'];
}
if (!$razorpay_subscription_id && !empty($_SESSION['pending_subscription_id'])) {
    $razorpay_subscription_id = (string)$_SESSION['pending_subscription_id'];
}

if (!$razorpay_payment_id || !$razorpay_subscription_id || !$razorpay_signature) {
    echo json_encode(['success' => false, 'message' => 'Invalid recurring payment details received.']);
    exit;
}

// ── Verify Razorpay subscription signature ───────────────────
$credentials = getRazorpayCredentials($pdo, 'donation');
$isValidSignature = verifySubscriptionSignature(
    $razorpay_payment_id,
    $razorpay_subscription_id,
    $razorpay_signature,
    $credentials['key_secret']
);

if (!$isValidSignature) {
    error_log("Razorpay subscription signature mismatch for sub: $razorpay_subscription_id");
    echo json_encode(['success' => false, 'message' => 'Subscription verification failed. Signature mismatch.']);
    exit;
}

// ── Fetch the pending recurring profile ──────────────────────
try {
    $stmt = $pdo->prepare("SELECT * FROM recurring_donations WHERE razorpay_subscription_id = ?");
    $stmt->execute([$razorpay_subscription_id]);
    $recurring = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('DB fetch failed (verify recurring): ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error.']);
    exit;
}

if (!$recurring) {
    echo json_encode(['success' => false, 'message' => 'Recurring profile not found.']);
    exit;
}

// ── Process 1st Cycle Charge & Issue Receipt ─────────────────
$chargeDate = date('Y-m-d');
$amount = (float)$recurring['amount'];

$donationId = processSubscriptionChargeSuccess(
    $pdo,
    $razorpay_subscription_id,
    $razorpay_payment_id,
    null,
    $amount,
    $chargeDate,
    json_encode($_POST),
    null,
    $razorpay_signature
);

if (!$donationId) {
    echo json_encode(['success' => false, 'message' => 'Failed to process recurring transaction.']);
    exit;
}

// Fetch generated receipt number
$receiptNo = '';
try {
    $rStmt = $pdo->prepare("SELECT receipt_no FROM donations WHERE id = ?");
    $rStmt->execute([$donationId]);
    $receiptNo = (string)($rStmt->fetchColumn() ?: '');
} catch (Throwable $e) {}

$_SESSION['last_donation_id'] = $donationId;
$_SESSION['last_receipt_no'] = $receiptNo;

echo json_encode([
    'success' => true,
    'message' => 'Recurring donation / AutoPay activated successfully!',
    'receipt_no' => $receiptNo,
    'subscription_id' => $razorpay_subscription_id,
    'donation_id' => $donationId,
    'redirect' => rtrim(appBaseUrl(), '/') . '/thankyou.php?donation_id=' . $donationId . '&recurring=1',
]);
