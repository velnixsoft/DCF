<?php
// ============================================================
// api/create_recurring_subscription.php
// REST API endpoint for initiating recurring donations / AutoPay
// ============================================================

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/razorpay_subscription_helper.php';

api_require_method(['POST']);

$amount = (float)api_input('amount', 0);
$frequency = strtolower(trim((string)api_input('frequency', 'monthly')));
$name = trim((string)api_input('name', api_input('donor_name', '')));
$email = filter_var(api_input('email', api_input('donor_email', '')), FILTER_VALIDATE_EMAIL);
$mobile = preg_replace('/[^0-9]/', '', (string)api_input('mobile', api_input('donor_mobile', '')));
$pan = strtoupper(trim((string)api_input('pan', api_input('donor_pan', ''))));
$address = trim((string)api_input('address', api_input('donor_address', '')));
$projectId = (int)api_input('project_id', 0) ?: null;
$referralCode = trim((string)api_input('referral_code', ''));
$is80g = (bool)api_input('is_80g', api_input('wants_80g', false));
$cycles = (int)api_input('cycles', 0);

if ($amount < 1) {
    api_error('Valid recurring amount is required (minimum ₹1).', 422);
}
if (!in_array($frequency, ['monthly', 'quarterly', 'half_yearly', 'yearly'], true)) {
    $frequency = 'monthly';
}
if ($name === '') {
    api_error('Donor name is required.', 422);
}
if (!$email) {
    api_error('Valid email is required.', 422);
}
if (strlen($mobile) !== 10) {
    api_error('Valid 10-digit mobile number is required.', 422);
}
if ($is80g && !preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/', $pan)) {
    api_error('Valid PAN is required for 80G tax benefit.', 422);
}

// Resolve Referral Code (SA student / Field agent)
$sa_student_id = null;
$field_agent_id = null;
if ($referralCode !== '') {
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
    } catch (Throwable $e) {}
}

// 1. Create or get Razorpay Plan
$planTitle = ucfirst($frequency) . ' Donation - INR ' . number_format($amount, 2);
$planId = createOrGetRazorpayPlan($pdo, $amount, $frequency, $planTitle);

if (!$planId) {
    api_error('Unable to create recurring plan on gateway.', 502);
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
    api_error('Unable to create subscription with gateway.', 502);
}

$subscriptionId = $subscription['id'];

// 3. Store pending profile in database
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

} catch (PDOException $e) {
    error_log('DB Insert failed (api/create_recurring_subscription): ' . $e->getMessage());
    api_error('Failed to register recurring donation profile.', 500);
}

$credentials = getRazorpayCredentials($pdo, 'donation');

api_ok([
    'subscription_id' => $subscriptionId,
    'recurring_id'    => $recurringId,
    'key'             => $credentials['key_id'],
    'company_name'    => $credentials['company_name'],
    'amount'          => $amount,
    'frequency'       => $frequency,
    'donor_name'      => $name,
    'donor_email'     => $email,
    'donor_mobile'    => $mobile,
    'description'     => $planTitle
], 'Recurring subscription initialized.');
