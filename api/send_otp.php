<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/member_module.php';

api_require_method(['POST']);

$email = filter_var((string)api_input('email', ''), FILTER_SANITIZE_EMAIL);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    api_error('Please enter a valid email.', 422);
}

$otp = (string)random_int(100000, 999999);
api_create_otp_challenge($pdo, 'donor_history', $email, $otp, [], 300, 5);

$settings = mm_load_settings($pdo);
$siteName = trim((string)($settings['site_name'] ?? 'NGO')) ?: 'NGO';
$body = "<div style='font-family:Arial,sans-serif;padding:20px;border:1px solid #ddd;'><h2 style='color:#1e40af;'>Your Verification Code</h2><p>Use the code below to verify your identity. It is valid for 5 minutes.</p><p style='font-size:24px;font-weight:bold;letter-spacing:5px;background:#f1f1f1;padding:10px;'>{$otp}</p></div>";

if (!mm_send_email($settings, $email, 'Donor', 'Your OTP for Donation History - ' . $siteName, $body)) {
    api_error('Failed to send OTP. Please try again.', 500);
}

api_ok([
    'otp_sent' => true,
], 'OTP has been sent to your email.');
