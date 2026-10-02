<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/member_module.php';

api_require_method(['POST']);

$email = filter_var((string)api_input('email', ''), FILTER_SANITIZE_EMAIL);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    api_error('Please enter a valid email.', 422);
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM volunteers WHERE email = ? AND status = 'Active'");
$stmt->execute([$email]);
if ((int)$stmt->fetchColumn() === 0) {
    api_ok(['otp_sent' => false], 'If you are a registered & active volunteer, an OTP has been sent.');
}

$otp = (string)random_int(100000, 999999);
api_create_otp_challenge($pdo, 'volunteer_login', strtolower($email), $otp, [
    'email' => $email,
], 300, 5);

$settings = mm_load_settings($pdo);
$siteName = $settings['site_name'] ?? 'NGO';

if (!mm_send_email(
    $settings,
    $email,
    'Volunteer',
    'Your OTP to Verify Volunteer ID - ' . $siteName,
    "<div style='font-family:sans-serif;padding:20px;border:1px solid #ddd;'><h2 style='color:#1e40af;'>Your Verification Code</h2><p>Use the code below to verify your identity. It is valid for 5 minutes.</p><p style='font-size:24px;font-weight:bold;letter-spacing:5px;background:#f1f1f1;padding:10px;'>{$otp}</p></div>"
)) {
    api_error('Failed to send OTP. Please try again.', 500);
}

api_ok(['otp_sent' => true], 'OTP sent successfully to your registered email.');
