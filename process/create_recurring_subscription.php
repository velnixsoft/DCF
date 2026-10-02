<?php
// ============================================================
// process/create_recurring_subscription.php
// Creates a Razorpay Plan & Subscription for Recurring Donations
// Follows existing create_razorpay_order.php conventions.
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

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}

$amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
$frequency = cleanInput($_POST['frequency'] ?? 'monthly');
$projectId = filter_input(INPUT_POST, 'project_id', FILTER_VALIDATE_INT);
$name = cleanInput($_POST['name'] ?? $_POST['donor_name'] ?? '');
$email = filter_var($_POST['email'] ?? $_POST['donor_email'] ?? '', FILTER_VALIDATE_EMAIL);
$mobile = cleanInput($_POST['mobile'] ?? $_POST['donor_mobile'] ?? '');
$pan = strtoupper(cleanInput($_POST['pan'] ?? $_POST['donor_pan'] ?? ''));
$address = cleanInput($_POST['address'] ?? $_POST['donor_address'] ?? '');
$referralCode = cleanInput($_POST['referral_code'] ?? '');
$cycles = filter_input(INPUT_POST, 'cycles', FILTER_VALIDATE_INT) ?: 0; // 0 for unlimited

$is80g = false;
if (isset($_POST['wants_80g'])) {
    $is80g = $_POST['wants_80g'] == '1';
} elseif (isset($_POST['is_80g'])) {
    $is80g = $_POST['is_80g'] == '1';
}

$errors = [];
if (!$amount || $amount < 1) {
    $errors[] = 'Valid recurring amount is required (minimum ₹1).';
}
if (!in_array($frequency, ['monthly', 'quarterly', 'half_yearly', 'yearly'], true)) {
    $frequency = 'monthly';
}
if ($name === '') {
    $errors[] = 'Donor name is required.';
}
if (!$email) {
    $errors[] = 'Valid email is required.';
}
if ($mobile === '') {
    $errors[] = 'Mobile number is required.';
} elseif (!preg_match('/^[0-9]{10}$/', $mobile)) {
    $errors[] = 'Valid 10-digit mobile number is required.';
}
if ($is80g && !preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/', $pan)) {
    $errors[] = 'Valid PAN is required for 80G tax benefit.';
}

if ($errors) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

$projectId = ($projectId && $projectId > 0) ? (int)$projectId : null;

// Resolve Referral Code (Student Ambassador / Field Agent)
$sa_student_id = null;
$field_agent_id = null;
if (!empty($referralCode)) {
    try {
        $saStmt = $pdo->prepare("SELECT id FROM sa_students WHERE referral_code = ? OR student_no = ?");
        $saStmt->execute([$referralCode, $referralCode]);
        $sa = $saStmt->fetch(PDO::FETCH_ASSOC);
        if ($sa) {
            $sa_student_id = (int)$sa['id'];
        } else {
            $faStmt = $pdo->prepare("SELECT id FROM field_agents WHERE area = ? OR id = ?");
            $faStmt->execute([$referralCode, (int)$referralCode]);
            $fa = $faStmt->fetch(PDO::FETCH_ASSOC);
            if ($fa) {
                $field_agent_id = (int)$fa['id'];
            }
        }
    } catch (Throwable $e) {
        // Continue safely
    }
}

// 1. Create or get Razorpay Plan
$planTitle = ucfirst($frequency) . ' Donation - INR ' . number_format($amount, 2);
$planId = createOrGetRazorpayPlan($pdo, $amount, $frequency, $planTitle);

if (!$planId) {
    echo json_encode(['success' => false, 'message' => 'Unable to initialize recurring plan on payment gateway. Please try again.']);
    exit;
}

// 2. Create Razorpay Subscription
$donorData = [
    'donor_name' => $name,
    'donor_email' => $email,
    'donor_mobile' => $mobile,
    'project_id' => $projectId,
    'referral_code' => $referralCode,
];

$subscription = createRazorpaySubscription($pdo, $planId, $donorData, $cycles);

if (!$subscription || empty($subscription['id'])) {
    echo json_encode(['success' => false, 'message' => 'Unable to create recurring subscription. Please try again.']);
    exit;
}

$subscriptionId = $subscription['id'];

// 3. Store pending recurring donation profile in database
try {
    $stmt = $pdo->prepare("
        INSERT INTO recurring_donations (
            project_id,
            donor_name,
            donor_email,
            donor_mobile,
            donor_pan,
            donor_address,
            amount,
            frequency,
            billing_cycle_count,
            completed_cycles,
            payment_gateway,
            razorpay_plan_id,
            razorpay_subscription_id,
            start_date,
            status,
            is_80g_eligible,
            referral_code,
            sa_student_id,
            field_agent_id
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 'Razorpay', ?, ?, CURRENT_DATE, 'pending', ?, ?, ?, ?
        )
    ");

    $stmt->execute([
        $projectId,
        $name,
        $email,
        $mobile,
        $pan ?: null,
        $address ?: null,
        $amount,
        $frequency,
        $cycles,
        $planId,
        $subscriptionId,
        $is80g ? 1 : 0,
        $referralCode ?: null,
        $sa_student_id,
        $field_agent_id
    ]);

    $recurringId = (int)$pdo->lastInsertId();
    $_SESSION['pending_recurring_id'] = $recurringId;
    $_SESSION['pending_subscription_id'] = $subscriptionId;

} catch (PDOException $e) {
    error_log('DB Insert failed (create_recurring_subscription): ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to save recurring profile.']);
    exit;
}

$credentials = getRazorpayCredentials($pdo, 'donation');

// 4. Return Subscription Checkout Details
echo json_encode([
    'success' => true,
    'subscription_id' => $subscriptionId,
    'recurring_id' => $recurringId,
    'key' => $credentials['key_id'],
    'company_name' => $credentials['company_name'],
    'amount' => $amount,
    'frequency' => $frequency,
    'donor_name' => $name,
    'donor_email' => $email,
    'donor_mobile' => $mobile,
    'description' => $planTitle
]);
