<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'otp_sent' => false, 'message' => 'Please enter a valid email.']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM volunteers WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$volunteer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$volunteer) {
    echo json_encode(['success' => false, 'otp_sent' => false, 'message' => 'No registered volunteer account found with this email. Please check your spelling or contact support.']);
    exit;
}

if (($volunteer['status'] ?? '') !== 'Active') {
    $statusText = htmlspecialchars($volunteer['status'] ?? 'Inactive');
    echo json_encode(['success' => false, 'otp_sent' => false, 'message' => "Your volunteer account status is currently: {$statusText}. Only active volunteer accounts can log in. Please contact support."]);
    exit;
}

$otp = random_int(100000, 999999);
$_SESSION['volunteer_otp_code'] = $otp;
$_SESSION['volunteer_otp_email'] = $email;
$_SESSION['volunteer_otp_expiry'] = time() + 300;

$settings = [];
$stmt = $pdo->query("SELECT * FROM settings");
while ($row = $stmt->fetch()) $settings[$row['setting_key']] = $row['setting_value'];

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = $settings['smtp_host'];
    $mail->SMTPAuth = true;
    $mail->Username = $settings['smtp_user'];
    $mail->Password = $settings['smtp_pass'];
    $mail->SMTPSecure = $settings['smtp_secure'];
    $mail->Port = $settings['smtp_port'];

    $mail->setFrom($settings['smtp_user'], $settings['site_name']);
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = 'Your OTP to Verify Volunteer ID';
    $mail->Body = "<div style='font-family:sans-serif;padding:20px;border:1px solid #ddd;'><h2 style='color:#1e40af;'>Your Verification Code</h2><p>Use the code below to verify your identity. It is valid for 5 minutes.</p><p style='font-size:24px;font-weight:bold;letter-spacing:5px;background:#f1f1f1;padding:10px;'>{$otp}</p></div>";

    $mail->send();
    echo json_encode(['success' => true, 'otp_sent' => true, 'message' => 'OTP sent successfully to your registered email.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'otp_sent' => false, 'message' => 'Failed to send OTP. Please try again.']);
}
