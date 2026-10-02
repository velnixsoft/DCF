<?php
// ============================================================
// process/create_razorpay_order.php
// Creates a Razorpay order and stores a pending donation row.
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

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}

$amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
$projectId = filter_input(INPUT_POST, 'project_id', FILTER_VALIDATE_INT);
$name = cleanInput($_POST['name'] ?? $_POST['donor_name'] ?? '');
$email = filter_var($_POST['email'] ?? $_POST['donor_email'] ?? '', FILTER_VALIDATE_EMAIL);
$mobile = cleanInput($_POST['mobile'] ?? $_POST['donor_mobile'] ?? '');
$pan = strtoupper(cleanInput($_POST['pan'] ?? $_POST['donor_pan'] ?? ''));
$referralCode = cleanInput($_POST['referral_code'] ?? '');
$is80g = false;
if (isset($_POST['wants_80g'])) {
    $is80g = $_POST['wants_80g'] == '1';
} elseif (isset($_POST['is_80g'])) {
    $is80g = $_POST['is_80g'] == '1';
}

$errors = [];
if (!$amount || $amount < 1) {
    $errors[] = 'Valid amount is required (minimum ₹1).';
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
    $errors[] = 'Valid PAN is required for 80G.';
}

if ($errors) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

$projectId = ($projectId && $projectId > 0) ? (int) $projectId : null;
$amountPaise = (int) round(((float) $amount) * 100);
$credentials = getRazorpayCredentials($pdo, 'donation');

$orderPayload = [
    'amount' => $amountPaise,
    'currency' => RAZORPAY_CURRENCY,
    'receipt' => 'don_' . date('YmdHis') . '_' . random_int(1000, 9999),
    'payment_capture' => 1,
    'notes' => [
        'module' => 'donations',
        'donor_name' => $name,
        'donor_email' => $email,
        'project_id' => $projectId ?? 'general',
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
$curlErr = curl_error($ch);
curl_close($ch);

if ($response === false) {
    error_log('Razorpay order curl error: ' . $curlErr);
    echo json_encode(['success' => false, 'message' => 'Payment gateway unavailable. Please try again.']);
    exit;
}

$order = json_decode($response, true);
if ($httpCode < 200 || $httpCode >= 300 || empty($order['id'])) {
    error_log('Razorpay order creation failed: ' . $response);
    echo json_encode(['success' => false, 'message' => 'Payment gateway error. Please try again.']);
    exit;
}

try {
    $columns = ['project_id', 'donor_name', 'donor_email', 'donor_mobile', 'donor_pan', 'amount', 'razorpay_order_id', 'payment_status', 'is_80g_eligible'];
    $values = [$projectId, $name, $email, $mobile, $pan, $amount, $order['id'], 'Pending', $is80g ? 1 : 0];

    if (dbColumnExists($pdo, 'donations', 'payment_gateway')) {
        $columns[] = 'payment_gateway';
        $values[] = 'Razorpay';
    }
    if (dbColumnExists($pdo, 'donations', 'payment_mode')) {
        $columns[] = 'payment_mode';
        $values[] = 'Razorpay';
    }
    if (dbColumnExists($pdo, 'donations', 'referral_code')) {
        $columns[] = 'referral_code';
        $values[] = $referralCode !== '' ? $referralCode : null;
    }
    
    $sa_student_id = null;
    if ($referralCode !== '') {
        $stmt = $pdo->prepare("SELECT id FROM sa_students WHERE referral_code = ? AND status = 'Active' LIMIT 1");
        $stmt->execute([$referralCode]);
        $sa_student_id = $stmt->fetchColumn() ?: null;
    }
    if ($sa_student_id && dbColumnExists($pdo, 'donations', 'sa_student_id')) {
        $columns[] = 'sa_student_id';
        $values[] = $sa_student_id;
    }

    $placeholders = implode(',', array_fill(0, count($columns), '?'));
    $stmt = $pdo->prepare('INSERT INTO donations (' . implode(',', $columns) . ') VALUES (' . $placeholders . ')');
    $stmt->execute($values);
    $donationId = (int) $pdo->lastInsertId();

    $_SESSION['pending_donation_id'] = $donationId;
} catch (PDOException $e) {
    error_log('DB insert failed (create_razorpay_order): ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error. Please contact support.']);
    exit;
}

echo json_encode([
    'success' => true,
    'order_id' => $order['id'],
    'amount' => $order['amount'],
    'currency' => $order['currency'] ?? RAZORPAY_CURRENCY,
    'key_id' => $credentials['key_id'],
    'company' => $credentials['company_name'],
    'donor_name' => $name,
    'donor_email' => $email,
    'donor_mobile' => $mobile,
    'donation_id' => $donationId,
]);
