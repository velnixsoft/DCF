<?php
// ============================================================
// process/create_join_order.php
// Creates a Razorpay order for Join Foundation / Join Project / Job Applications
// Author: VELNIX SOFT / Antigravity AI
// Date: 2026-09-12
// ============================================================

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/razorpay.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/join_application_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid or expired CSRF security token. Please refresh.']);
    exit;
}

// ── Sanitize & Validate Inputs ──────────────────────────────
$applicantName   = cleanInput($_POST['applicant_name'] ?? '');
$contact         = cleanInput($_POST['contact'] ?? '');
$email           = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL) ?: null;
$state           = cleanInput($_POST['state'] ?? '');
$district        = cleanInput($_POST['district'] ?? '');
$applicationType = cleanInput($_POST['application_type'] ?? 'join_foundation');
$projectId       = filter_input(INPUT_POST, 'project_id', FILTER_VALIDATE_INT) ?: null;
$jobId           = filter_input(INPUT_POST, 'job_id', FILTER_VALIDATE_INT) ?: null;
$details         = cleanInput($_POST['details'] ?? '');
$feeAmount       = filter_input(INPUT_POST, 'fee_amount', FILTER_VALIDATE_FLOAT);

$allowedTypes = ['join_foundation', 'join_project', 'job_application'];
if (!in_array($applicationType, $allowedTypes, true)) {
    $applicationType = 'join_foundation';
}

$errors = [];
if ($applicantName === '') {
    $errors[] = 'Full Name is required.';
}
if ($contact === '') {
    $errors[] = 'Mobile / Contact number is required.';
} elseif (!preg_match('/^[0-9]{10}$/', preg_replace('/[^0-9]/', '', $contact))) {
    $errors[] = 'Please provide a valid 10-digit mobile number.';
}

if ($applicationType === 'join_project' && !$projectId) {
    $errors[] = 'Please select a specific project to join.';
}
if ($applicationType === 'job_application' && !$jobId) {
    $errors[] = 'Please select a job vacancy to apply for.';
}

if (!$feeAmount || $feeAmount < 1) {
    $errors[] = 'A valid fee amount of at least ₹1 is required for online payment.';
}

if ($errors) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

// ── Prepare Razorpay Order ──────────────────────────────────
$credentials = getRazorpayCredentials($pdo, 'donation');
$amountPaise = (int) round(((float) $feeAmount) * 100);
$applicationNo = generate_join_application_no($pdo, $applicationType);

$orderPayload = [
    'amount' => $amountPaise,
    'currency' => RAZORPAY_CURRENCY,
    'receipt' => 'join_' . date('YmdHis') . '_' . random_int(100, 999),
    'payment_capture' => 1,
    'notes' => [
        'module' => 'join_applications',
        'application_no' => $applicationNo,
        'applicant_name' => $applicantName,
        'contact' => $contact,
        'application_type' => $applicationType,
        'project_id' => $projectId ?? 'N/A',
        'job_id' => $jobId ?? 'N/A',
    ],
];

$ch = curl_init('https://api.razorpay.com/v1/orders');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($orderPayload),
    CURLOPT_USERPWD => $credentials['key_id'] . ':' . $credentials['key_secret'],
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_CONNECTTIMEOUT => 10,
]);

$response = curl_exec($ch);
$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($response === false) {
    error_log('Razorpay join order curl error: ' . $curlErr);
    echo json_encode(['success' => false, 'message' => 'Payment gateway connection failed. Please try again.']);
    exit;
}

$order = json_decode($response, true);
if ($httpCode < 200 || $httpCode >= 300 || empty($order['id'])) {
    error_log('Razorpay join order creation failed: ' . $response);
    echo json_encode(['success' => false, 'message' => 'Unable to create payment order. ' . ($order['error']['description'] ?? 'Please try again.')]);
    exit;
}

// ── Store Pending Application in Database ───────────────────
try {
    $stmt = $pdo->prepare("INSERT INTO join_applications 
        (application_no, applicant_name, contact, email, state, district, application_type, project_id, job_id, details, fee_amount, payment_status, razorpay_order_id, payment_method, status, applied_date) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, 'Razorpay', 'pending', NOW())");
    
    $stmt->execute([
        $applicationNo,
        $applicantName,
        $contact,
        $email,
        $state ?: null,
        $district ?: null,
        $applicationType,
        $projectId,
        $jobId,
        $details ?: null,
        $feeAmount,
        $order['id']
    ]);

    $appId = (int)$pdo->lastInsertId();
    $_SESSION['pending_join_app_id'] = $appId;
    $_SESSION['pending_join_app_no'] = $applicationNo;

    echo json_encode([
        'success' => true,
        'order_id' => $order['id'],
        'amount' => $order['amount'],
        'currency' => $order['currency'] ?? RAZORPAY_CURRENCY,
        'key_id' => $credentials['key_id'],
        'company' => $credentials['company_name'],
        'applicant_name' => $applicantName,
        'contact' => $contact,
        'email' => $email ?: '',
        'application_id' => $appId,
        'application_no' => $applicationNo,
        'application_type' => $applicationType
    ]);
} catch (PDOException $e) {
    error_log('Database insert error in create_join_order: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to initialize application record. Please try again.']);
}
