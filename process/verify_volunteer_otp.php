<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$email = $_POST['email'] ?? '';
$otp = $_POST['otp'] ?? '';
$email = filter_var($email, FILTER_SANITIZE_EMAIL);

if (
    empty($_SESSION['volunteer_otp_code']) ||
    (string)$_SESSION['volunteer_otp_code'] !== (string)$otp ||
    !isset($_SESSION['volunteer_otp_email']) ||
    !hash_equals((string)$_SESSION['volunteer_otp_email'], (string)$email) ||
    time() > (int)($_SESSION['volunteer_otp_expiry'] ?? 0)
) {
    echo json_encode(['success' => false, 'message' => 'Invalid or expired OTP.']);
    exit;
}

$stmt = $pdo->prepare("SELECT id, name, email, phone, photo, status, id_card_no, valid_until FROM volunteers WHERE email = ? AND status = 'Active' LIMIT 1");
$stmt->execute([$email]);
$volunteer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$volunteer) {
    echo json_encode(['success' => false, 'message' => 'No active volunteer found.']);
    exit;
}

unset($_SESSION['volunteer_otp_code'], $_SESSION['volunteer_otp_email'], $_SESSION['volunteer_otp_expiry']);
$_SESSION['volunteer_verified_email'] = $email;

// Session login for volunteer dashboard
$vid = (int)($volunteer['id'] ?? 0);
if ($vid > 0) {
    session_regenerate_id(true);
    $_SESSION['volunteer_logged_in'] = true;
    $_SESSION['volunteer_id'] = $vid;
    $_SESSION['volunteer_email'] = $email;
}

echo json_encode(['success' => true, 'volunteer' => $volunteer, 'redirect' => 'volunteer-dashboard.php']);
