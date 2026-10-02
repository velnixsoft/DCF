<?php
require_once __DIR__ . '/_bootstrap.php';

api_require_method(['POST']);

$amountInput = (float)api_input('amount', 0);
if ($amountInput <= 0) {
    api_error('Invalid amount.', 422);
}

$settings = api_settings($pdo);
$keyId = trim((string)($settings['razorpay_donation_key_id'] ?? ''));
$keySecret = trim((string)($settings['razorpay_donation_key_secret'] ?? ''));

if ($keyId === '' || $keySecret === '') {
    $keyId = trim((string)($settings['razorpay_key_id'] ?? ''));
    $keySecret = trim((string)($settings['razorpay_key_secret'] ?? ''));
}

if ($keyId === '' || $keySecret === '') {
    api_error('Razorpay is not configured by admin.', 503);
}

$payload = [
    'amount' => (int)round($amountInput * 100),
    'currency' => 'INR',
    'receipt' => 'don_' . date('YmdHis') . '_' . random_int(1000, 9999),
    'notes' => [
        'module' => 'donations',
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
