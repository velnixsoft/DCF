<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/member_module.php';

api_require_method(['POST']);

$token = api_bearer_token();
if ($token === '') {
    api_error('Authorization token is required.', 401);
}

$tokenRow = api_find_access_token($pdo, $token, 'member');
if (!$tokenRow) {
    api_error('Invalid or expired token.', 401);
}

$memberId = (int)$tokenRow['user_id'];
$phone = cleanInput(api_input('phone', ''));
$dob = cleanInput(api_input('dob', ''));
$gender = cleanInput(api_input('gender', ''));
$address = cleanInput(api_input('address', ''));

if ($memberId <= 0 || $phone === '') {
    api_error('Phone is required.', 422);
}

if ($dob !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
    api_error('Invalid DOB format.', 422);
}

$allowedGenders = ['', 'Male', 'Female', 'Other'];
if (!in_array($gender, $allowedGenders, true)) {
    $gender = '';
}

try {
    $stmt = $pdo->prepare("UPDATE members SET phone = ?, dob = ?, gender = ?, address = ? WHERE id = ? LIMIT 1");
    $stmt->execute([$phone, $dob !== '' ? $dob : null, $gender !== '' ? $gender : null, $address !== '' ? $address : null, $memberId]);
} catch (Throwable $e) {
    api_error('Unable to update profile right now.', 500);
}

api_ok(['member_id' => $memberId], 'Profile updated.');
