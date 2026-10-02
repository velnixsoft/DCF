<?php
header('Content-Type: application/json');

require '../config/db.php';
require '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$amountInput = (float)($_POST['amount'] ?? 0);
if ($amountInput <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid amount.']);
    exit;
}

$settings = [];
try {
    $stmt = $pdo->query("SELECT * FROM settings");
    while ($row = $stmt->fetch()) $settings[$row['setting_key']] = $row['setting_value'];
} catch (Throwable $e) {
}

$keyId = trim((string)($settings['razorpay_donation_key_id'] ?? ''));
$keySecret = trim((string)($settings['razorpay_donation_key_secret'] ?? ''));

if ($keyId === '' || $keySecret === '') {
    $keyId = trim((string)($settings['razorpay_key_id'] ?? ''));
    $keySecret = trim((string)($settings['razorpay_key_secret'] ?? ''));
}

if ($keyId === '' || $keySecret === '') {
    echo json_encode(['success' => false, 'message' => 'Razorpay is not configured by admin.']);
    exit;
}

$payload = [
    'amount' => (int)round($amountInput * 100),
    'currency' => 'INR',
    'receipt' => 'don_' . date('YmdHis') . '_' . random_int(1000, 9999),
    'notes' => [
        'module' => 'donations'
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

