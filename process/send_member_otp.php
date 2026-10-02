<?php
header('Content-Type: application/json');

require '../config/db.php';
require '../includes/functions.php';
require '../includes/member_module.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'otp_sent' => false, 'message' => 'Invalid request.']);
    exit;
}

$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$memberNo = trim((string)($_POST['member_no'] ?? ''));

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $memberNo === '') {
    echo json_encode(['success' => false, 'otp_sent' => false, 'message' => 'Please enter valid member details.']);
    exit;
}

// Anti-enumeration: default to a generic success response.
$genericResponse = ['success' => true, 'otp_sent' => false, 'message' => 'If your membership is active, an OTP has been sent to your registered email.'];

try {
    $stmt = $pdo->prepare("SELECT id, full_name, email, status, payment_status FROM members WHERE email = ? AND member_no = ? LIMIT 1");
    $stmt->execute([$email, $memberNo]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$member || ($member['status'] ?? '') !== 'Active' || ($member['payment_status'] ?? '') !== 'Success') {
        echo json_encode($genericResponse);
        exit;
    }

    $otp = random_int(100000, 999999);
    $_SESSION['member_otp_code'] = (string)$otp;
    $_SESSION['member_otp_email'] = $email;
    $_SESSION['member_otp_member_no'] = $memberNo;
    $_SESSION['member_otp_member_id'] = (int)$member['id'];
    $_SESSION['member_otp_expiry'] = time() + 300;

    $settings = mm_load_settings($pdo);
    $siteName = $settings['site_name'] ?? 'NGO';

    try {
        mm_send_email(
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
        );
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'otp_sent' => false, 'message' => 'Unable to send OTP right now. Please try again.']);
        exit;
    }

    echo json_encode(['success' => true, 'otp_sent' => true, 'message' => 'OTP sent successfully to your registered email.']);
    exit;
} catch (Throwable $e) {
    echo json_encode($genericResponse);
    exit;
}

