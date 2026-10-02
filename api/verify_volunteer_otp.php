<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/member_module.php';

api_require_method(['POST']);

$email = filter_var((string)api_input('email', ''), FILTER_SANITIZE_EMAIL);
$otp = trim((string)api_input('otp', ''));

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $otp === '') {
    api_error('Please enter valid details.', 422);
}

$result = api_verify_otp_challenge($pdo, 'volunteer_login', strtolower($email), $otp);
if (empty($result['success'])) {
    api_error((string)($result['message'] ?? 'Invalid or expired OTP.'), 422);
}

try {
    $stmt = $pdo->prepare("SELECT id, name, email, phone, address, photo, blood_group, status, id_card_no, valid_from, valid_until, created_at FROM volunteers WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $volunteer = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    api_error('Unable to verify right now.', 500);
}

if (!$volunteer) {
    api_error('No volunteer found.', 404);
}

$token = api_issue_access_token($pdo, 'volunteer', (int)$volunteer['id'], [
    'device_name' => (string)api_input('device_name', ''),
    'device_id' => (string)api_input('device_id', ''),
], 30);

$volunteer['photo_url'] = api_public_url($volunteer['photo'] ?? '');

api_ok([
    'volunteer' => $volunteer,
    'access_token' => $token['access_token'],
    'token_type' => $token['token_type'],
    'expires_in' => $token['expires_in'],
    'expires_at' => $token['expires_at'],
    'redirect' => 'volunteer-dashboard.php',
], 'Login successful.');
