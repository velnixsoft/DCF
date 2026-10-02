<?php
require_once __DIR__ . '/_bootstrap.php';

api_require_method(['POST']);

$name = cleanInput(api_input('name', ''));
$email = filter_var((string)api_input('email', ''), FILTER_SANITIZE_EMAIL);
$phone = cleanInput(api_input('phone', ''));
$address = cleanInput(api_input('address', ''));
$bloodGroup = cleanInput(api_input('blood_group', ''));

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $phone === '') {
    api_error('Please fill all required fields.', 422);
}

$dupStmt = $pdo->prepare("SELECT COUNT(*) FROM volunteers WHERE email = ?");
$dupStmt->execute([$email]);
if ((int)$dupStmt->fetchColumn() > 0) {
    api_error('This email is already registered.', 422);
}

$photoPath = null;
if (!empty($_FILES['photo']) && (int)($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
    if ((int)$_FILES['photo']['size'] > 2 * 1024 * 1024) {
        api_error('Photo size must be under 2MB.', 422);
    }

    $allowedTypes = ['image/jpeg', 'image/png'];
    if (!in_array((string)$_FILES['photo']['type'], $allowedTypes, true)) {
        api_error('Only JPG or PNG photos are allowed.', 422);
    }

    $targetDir = dirname(__DIR__) . '/uploads/volunteers/';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $fileName = time() . '_' . uniqid('', true) . '_' . basename((string)$_FILES['photo']['name']);
    if (!move_uploaded_file((string)$_FILES['photo']['tmp_name'], $targetDir . $fileName)) {
        api_error('Failed to upload photo.', 500);
    }
    $photoPath = 'uploads/volunteers/' . $fileName;
}

try {
    $sql = "INSERT INTO volunteers (name, email, phone, address, blood_group, photo, status) VALUES (?, ?, ?, ?, ?, ?, 'Pending')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$name, $email, $phone, $address !== '' ? $address : null, $bloodGroup !== '' ? $bloodGroup : null, $photoPath]);
    $volunteerId = (int)$pdo->lastInsertId();
} catch (Throwable $e) {
    api_error('A database error occurred. Please try again.', 500);
}

api_ok([
    'volunteer_id' => $volunteerId,
], 'Registration successful!');
