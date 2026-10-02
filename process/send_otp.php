<?php

$debug_log = [];
$debug_log[] = "Script execution started.";

header('Content-Type: application/json');

$baseDir = dirname(__DIR__);
$paths_to_check = [
    'config/db.php' => $baseDir . '/config/db.php',
    'includes/functions.php' => $baseDir . '/includes/functions.php',
    'PHPMailer/src/Exception.php' => $baseDir . '/libs/PHPMailer/src/Exception.php'
];

foreach ($paths_to_check as $name => $path) {
    if (file_exists($path)) {
        $debug_log[] = "SUCCESS: Found file -> " . $name;
    } else {
        $debug_log[] = "FATAL ERROR: File not found at path -> " . $path;

        echo json_encode(['success' => false, 'message' => "Server configuration error: A required file is missing.", 'debug' => $debug_log]);
        exit;
    }
}

require_once $baseDir . '/config/db.php';
require_once $baseDir . '/includes/functions.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once $baseDir . '/libs/PHPMailer/src/PHPMailer.php';
require_once $baseDir . '/libs/PHPMailer/src/SMTP.php';
require_once $baseDir . '/libs/PHPMailer/src/Exception.php';

$debug_log[] = "All required files included successfully.";

if (!isset($pdo)) {
    $debug_log[] = "FATAL ERROR: Database connection variable (\$pdo) not found after including db.php.";
    echo json_encode(['success' => false, 'message' => "Database connection failed.", 'debug' => $debug_log]);
    exit;
}
$debug_log[] = "Database connection object exists.";

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    if (empty($email)) {
        throw new Exception('Email is required.');
    }

    $checkStmt = $pdo->prepare("
        SELECT (
            (SELECT COUNT(*) FROM donations WHERE donor_email = ?) + 
            (SELECT COUNT(*) FROM recurring_donations WHERE donor_email = ?)
        ) AS total
    ");
    $checkStmt->execute([$email, $email]);
    if ((int)$checkStmt->fetchColumn() === 0) {
        throw new Exception('No donation or recurring AutoPay record found for this email address. Please make sure you entered the correct email.');
    }

    $debug_log[] = "Email received: " . $email;

    $settings = [];
    $stmt = $pdo->query("SELECT * FROM settings WHERE setting_key LIKE 'smtp_%' OR setting_key = 'site_name'");
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    $debug_log[] = "SMTP settings fetched from database.";

    $otp = random_int(100000, 999999);
    $_SESSION['otp_code'] = $otp;
    $_SESSION['otp_email'] = $email;
    $_SESSION['otp_expiry'] = time() + 300;
    $debug_log[] = "OTP generated: " . $otp;
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = $settings['smtp_host'] ?? '';
    $mail->SMTPAuth   = true;
    $mail->Username   = $settings['smtp_user'] ?? '';
    $mail->Password   = $settings['smtp_pass'] ?? '';
    $mail->SMTPSecure = $settings['smtp_secure'] ?? 'tls';
    $mail->Port       = $settings['smtp_port'] ?? 587;

    $mail->setFrom($settings['smtp_user'], $settings['site_name'] ?? 'NGO');
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = 'Your OTP for Donation History';
    $mail->Body    = "Your OTP is: <b>{$otp}</b>";

    $mail->send();

    $debug_log[] = "Email sent successfully via PHPMailer.";
    echo json_encode(['success' => true, 'message' => 'OTP has been sent to your email.']);
} catch (Exception $e) {
    $debug_log[] = "ERROR CAUGHT: " . $e->getMessage();
    error_log("OTP Send Error: " . $e->getMessage() . " | Debug Log: " . implode(" | ", $debug_log));
    
    $userMsg = 'Unable to send OTP. Please check if the email address is correct, or try again later.';
    if ($e->getMessage() === 'Invalid request method.' || $e->getMessage() === 'Email is required.') {
        $userMsg = $e->getMessage();
    }
    echo json_encode(['success' => false, 'message' => $userMsg]);
}
