<?php
header('Content-Type: application/json');

require '../config/db.php';
require '../includes/member_module.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$designationId = (int)($_POST['designation_id'] ?? 0);
$amountInput = (float)($_POST['membership_fee'] ?? 0);

if ($designationId <= 0 || $amountInput <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid designation or amount.']);
    exit;
}

$settings = mm_load_settings($pdo);
$keyId = trim($settings['razorpay_key_id'] ?? '');
$keySecret = trim($settings['razorpay_key_secret'] ?? '');

if ($keyId === '' || $keySecret === '') {
    echo json_encode(['success' => false, 'message' => 'Razorpay is not configured by admin.']);
    exit;
}

$designationStmt = $pdo->prepare("SELECT fee_amount FROM member_designations WHERE id = ? AND is_active = 1");
$designationStmt->execute([$designationId]);
$fee = (float)$designationStmt->fetchColumn();

if ($fee <= 0 || (int)round($fee * 100) !== (int)round($amountInput * 100)) {
    echo json_encode(['success' => false, 'message' => 'Amount mismatch for selected designation.']);
    exit;
}

$payload = [
    'amount' => (int)round($fee * 100),
    'currency' => 'INR',
    'receipt' => 'mem_' . date('YmdHis') . '_' . random_int(1000, 9999),
    'notes' => [
        'module' => 'membership'
    ]
];

$ch = curl_init('https://api.razorpay.com/v1/orders');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_USERPWD, $keyId . ':' . $keySecret);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($response === false || $httpCode < 200 || $httpCode >= 300) {
    echo json_encode(['success' => false, 'message' => 'Unable to create Razorpay order.', 'error' => $curlError]);
    exit;
}

$data = json_decode($response, true);
if (!is_array($data) || empty($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid Razorpay response.']);
    exit;
}

echo json_encode([
    'success' => true,
    'order_id' => $data['id'],
    'amount' => $payload['amount'],
    'currency' => 'INR',
    'key_id' => $keyId,
    'name' => $settings['site_name'] ?? 'NGO'
]);
exit;
