<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$name = cleanInput($_POST['name']);
$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
$message = cleanInput($_POST['message']);

if (empty($name) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Please fill all fields correctly.']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, message, status) VALUES (?, ?, ?, 'New')");
    $stmt->execute([$name, $email, $message]);
    $settings = [];
    $stmt = $pdo->query("SELECT * FROM settings");
    while ($row = $stmt->fetch()) $settings[$row['setting_key']] = $row['setting_value'];

    $adminEmail = $settings['ngo_email'] ?? null;
    if ($adminEmail) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $settings['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $settings['smtp_user'];
            $mail->Password = $settings['smtp_pass'];
            $mail->SMTPSecure = $settings['smtp_secure'];
            $mail->Port = $settings['smtp_port'];

            $mail->setFrom($settings['smtp_user'], 'Website Inquiry');
            $mail->addAddress($adminEmail);

            $mail->isHTML(true);
            $mail->Subject = 'New Contact Message from ' . $name;
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; padding: 20px; border: 1px solid #ddd;'>
                    <h2>New Inquiry Received</h2>
                    <p>You have received a new message from your website's contact form.</p>
                    <hr>
                    <p><strong>Name:</strong> {$name}</p>
                    <p><strong>Email:</strong> {$email}</p>
                    <p><strong>Message:</strong></p>
                    <blockquote style='border-left: 4px solid #ccc; padding-left: 15px; margin: 0; font-style: italic;'>
                        " . nl2br($message) . "
                    </blockquote>
                    <hr>
                    <p>You can view and reply to this message from your admin dashboard.</p>
                </div>";

            $mail->send();
        } catch (Exception $e) {
            error_log('Contact form email notification failed: ' . $mail->ErrorInfo);
        }
    }

    echo json_encode(['success' => true, 'message' => 'Thank you! Your message has been sent successfully.']);
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Something went wrong. Please try again later.']);
}

exit;
