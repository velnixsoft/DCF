<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/member_module.php';

api_require_method(['POST']);

$name = cleanInput(api_input('name', ''));
$email = filter_var((string)api_input('email', ''), FILTER_SANITIZE_EMAIL);
$message = cleanInput(api_input('message', ''));

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $message === '') {
    api_error('Please fill all fields correctly.', 422);
}

try {
    $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, message, status) VALUES (?, ?, ?, 'New')");
    $stmt->execute([$name, $email, $message]);
    $messageId = (int)$pdo->lastInsertId();
} catch (Throwable $e) {
    api_error('Something went wrong. Please try again later.', 500);
}

$settings = mm_load_settings($pdo);
$adminEmail = trim((string)($settings['ngo_email'] ?? ''));

if ($adminEmail !== '') {
    $siteName = trim((string)($settings['site_name'] ?? 'NGO')) ?: 'NGO';
    $subject = 'New Contact Message from ' . $name;
    $body = '
        <div style="font-family:Arial,sans-serif;padding:20px;border:1px solid #e5e7eb;border-radius:10px">
            <h2 style="margin-top:0">New Inquiry Received</h2>
            <p>You have received a new message from your website contact form.</p>
            <hr>
            <p><strong>Name:</strong> ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</p>
            <p><strong>Email:</strong> ' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '</p>
            <p><strong>Message:</strong></p>
            <blockquote style="border-left:4px solid #d1d5db;padding-left:15px;margin:0;font-style:italic">' . nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . '</blockquote>
            <hr>
            <p>You can reply from the admin dashboard.</p>
        </div>';

    mm_send_email($settings, $adminEmail, $siteName, $subject, $body);
}

api_ok([
    'message_id' => $messageId,
], 'Thank you! Your message has been sent successfully.');
