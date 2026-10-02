<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/member_module.php';

api_require_method(['POST']);

$designationId = (int)api_input('designation_id', 0);
$amountInput = (float)api_input('membership_fee', 0);

if ($designationId <= 0 || $amountInput <= 0) {
    api_error('Invalid designation or amount.', 422);
}

$settings = mm_load_settings($pdo);
$keyId = trim((string)($settings['razorpay_key_id'] ?? ''));
$keySecret = trim((string)($settings['razorpay_key_secret'] ?? ''));

if ($keyId === '' || $keySecret === '') {
    api_error('Razorpay is not configured by admin.', 503);
}

$designationStmt = $pdo->prepare("SELECT fee_amount FROM member_designations WHERE id = ? AND is_active = 1");
$designationStmt->execute([$designationId]);
$fee = (float)$designationStmt->fetchColumn();

if ($fee <= 0 || (int)round($fee * 100) !== (int)round($amountInput * 100)) {
    api_error('Amount mismatch for selected designation.', 422);
}

$payload = [
    'amount' => (int)round($fee * 100),
    'currency' => 'INR',
    'receipt' => 'mem_' . date('YmdHis') . '_' . random_int(1000, 9999),
    'notes' => [
        'module' => 'membership',
    ],
];

$ch = curl_init('https://api.razorpay.com/v1/orders');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_USERPWD => $keyId . ':' . $keySecret,
    CURLOPT_TIMEOUT => 30,
]);

$response = curl_exec($ch);
$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($response === false || $httpCode < 200 || $httpCode >= 300) {
    api_error('Unable to create Razorpay order.', 502, ['error' => $curlError]);
}

$data = json_decode($response, true);
if (!is_array($data) || empty($data['id'])) {
    api_error('Invalid Razorpay response.', 502);
}

api_ok([
    'order_id' => $data['id'],
    'amount' => $payload['amount'],
    'currency' => 'INR',
    'key_id' => $keyId,
    'name' => $settings['site_name'] ?? 'NGO',
], 'Razorpay order created.');
