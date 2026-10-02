<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/member_module.php';

api_require_method(['POST']);

$email = filter_var((string)api_input('email', ''), FILTER_SANITIZE_EMAIL);
$memberNo = trim((string)api_input('member_no', ''));
$otp = trim((string)api_input('otp', ''));

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $memberNo === '' || $otp === '') {
    api_error('Please enter valid details.', 422);
}

$identifier = strtolower($email) . '|' . strtolower($memberNo);
$result = api_verify_otp_challenge($pdo, 'member_login', $identifier, $otp);
if (empty($result['success'])) {
    api_error((string)($result['message'] ?? 'Invalid or expired OTP.'), 422);
}

try {
    $stmt = $pdo->prepare("SELECT id, member_no, full_name, email, status, payment_status FROM members WHERE email = ? AND member_no = ? LIMIT 1");
    $stmt->execute([$email, $memberNo]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    api_error('Unable to verify right now.', 500);
}

if (!$member || ($member['status'] ?? '') !== 'Active' || ($member['payment_status'] ?? '') !== 'Success') {
    api_error('Member is not active or membership fee is pending.', 422);
}

$token = api_issue_access_token($pdo, 'member', (int)$member['id'], [
    'device_name' => (string)api_input('device_name', ''),
    'device_id' => (string)api_input('device_id', ''),
], 30);

api_ok([
    'member' => $member,
    'access_token' => $token['access_token'],
    'token_type' => $token['token_type'],
    'expires_in' => $token['expires_in'],
    'expires_at' => $token['expires_at'],
    'redirect' => 'member-dashboard.php',
], 'Login successful.');
