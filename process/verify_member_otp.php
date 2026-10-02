<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$memberNo = trim((string)($_POST['member_no'] ?? ''));
$otp = trim((string)($_POST['otp'] ?? ''));

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $memberNo === '' || $otp === '') {
    echo json_encode(['success' => false, 'message' => 'Please enter valid details.']);
    exit;
}

if (
    empty($_SESSION['member_otp_code']) ||
    (string)$_SESSION['member_otp_code'] !== (string)$otp ||
    empty($_SESSION['member_otp_email']) ||
    !hash_equals((string)$_SESSION['member_otp_email'], (string)$email) ||
    empty($_SESSION['member_otp_member_no']) ||
    !hash_equals((string)$_SESSION['member_otp_member_no'], (string)$memberNo) ||
    time() > (int)($_SESSION['member_otp_expiry'] ?? 0)
) {
    echo json_encode(['success' => false, 'message' => 'Invalid or expired OTP.']);
    exit;
}

$memberId = (int)($_SESSION['member_otp_member_id'] ?? 0);
if ($memberId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid or expired OTP.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, member_no, full_name, email, status, payment_status FROM members WHERE id = ? AND email = ? AND member_no = ? LIMIT 1");
    $stmt->execute([$memberId, $email, $memberNo]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Unable to verify right now.']);
    exit;
}

if (!$member || ($member['status'] ?? '') !== 'Active' || ($member['payment_status'] ?? '') !== 'Success') {
    echo json_encode(['success' => false, 'message' => 'Member is not active or membership fee is pending.']);
    exit;
}

session_regenerate_id(true);
$_SESSION['member_logged_in'] = true;
$_SESSION['member_id'] = (int)$member['id'];
$_SESSION['member_no'] = (string)$member['member_no'];
$_SESSION['member_email'] = (string)$member['email'];
$_SESSION['member_name'] = (string)($member['full_name'] ?? 'Member');

unset(
    $_SESSION['member_otp_code'],
    $_SESSION['member_otp_email'],
    $_SESSION['member_otp_member_no'],
    $_SESSION['member_otp_member_id'],
    $_SESSION['member_otp_expiry']
);

echo json_encode(['success' => true, 'redirect' => 'member-dashboard.php']);
exit;
