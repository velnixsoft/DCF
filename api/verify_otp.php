<?php
require_once __DIR__ . '/_bootstrap.php';

api_require_method(['POST']);

$email = filter_var((string)api_input('email', ''), FILTER_SANITIZE_EMAIL);
$otp = trim((string)api_input('otp', ''));

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $otp === '') {
    api_error('Please enter valid details.', 422);
}

$result = api_verify_otp_challenge($pdo, 'donor_history', $email, $otp);
if (empty($result['success'])) {
    api_error((string)($result['message'] ?? 'Invalid or expired OTP. Please try again.'), 422);
}

try {
    $stmt = $pdo->prepare("SELECT * FROM donations WHERE donor_email = ? ORDER BY created_at DESC");
    $stmt->execute([$email]);
    $donations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $donations = [];
}

api_ok([
    'donations' => $donations,
], 'Donation history loaded.');
