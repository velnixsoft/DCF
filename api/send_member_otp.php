<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/member_module.php';

api_require_method(['POST']);

$email = filter_var((string)api_input('email', ''), FILTER_SANITIZE_EMAIL);
$memberNo = trim((string)api_input('member_no', ''));

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $memberNo === '') {
    api_error('Please enter valid member details.', 422);
}

try {
    $stmt = $pdo->prepare("SELECT id, full_name, email, status, payment_status FROM members WHERE email = ? AND member_no = ? LIMIT 1");
    $stmt->execute([$email, $memberNo]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $member = false;
}

if (!$member || ($member['status'] ?? '') !== 'Active' || ($member['payment_status'] ?? '') !== 'Success') {
    api_ok(['otp_sent' => false], 'If your membership is active, an OTP has been sent to your registered email.');
}

$otp = (string)random_int(100000, 999999);
api_create_otp_challenge($pdo, 'member_login', strtolower($email) . '|' . strtolower($memberNo), $otp, [
    'member_id' => (int)$member['id'],
    'member_no' => $memberNo,
    'email' => $email,
], 300, 5);

$settings = mm_load_settings($pdo);
$siteName = $settings['site_name'] ?? 'NGO';

if (!mm_send_email(
    $settings,
    $email,
    $member['full_name'] ?? 'Member',
    'Your OTP for Member Login - ' . $siteName,
    "<div style='font-family:Arial,sans-serif;max-width:560px;margin:auto;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden'>
        <div style='background:#16a34a;color:#fff;padding:18px 20px'>
            <h2 style='margin:0;font-size:18px'>Member Login Verification</h2>
        </div>
        <div style='padding:18px 20px;color:#0f172a'>
            <p style='margin:0 0 10px'>Use this OTP to login. It is valid for <b>5 minutes</b>.</p>
            <p style='margin:14px 0;padding:12px 14px;background:#f1f5f9;border-radius:8px;font-size:22px;letter-spacing:6px;font-weight:700;display:inline-block'>{$otp}</p>
            <p style='margin:14px 0 0;color:#475569;font-size:12px'>If you did not request this, you can ignore this email.</p>
        </div>
    </div>"
)) {
    api_error('Unable to send OTP right now. Please try again.', 500);
}

api_ok([
    'otp_sent' => true,
], 'OTP sent successfully to your registered email.');
