<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../student-profile.php');
    exit;
}

if (empty($_SESSION['student_logged_in']) || empty($_SESSION['student_id'])) {
    setFlash('error', 'Please login first.');
    header('Location: ../student-login.php');
    exit;
}

$csrf = (string)($_POST['csrf_token'] ?? '');
if ($csrf === '' || empty($_SESSION['student_profile_csrf']) || !hash_equals((string)$_SESSION['student_profile_csrf'], $csrf)) {
    setFlash('error', 'Security check failed.');
    header('Location: ../student-profile.php');
    exit;
}

$current = (string)($_POST['current_password'] ?? '');
$new = (string)($_POST['new_password'] ?? '');
$confirm = (string)($_POST['confirm_password'] ?? '');
$studentId = (int)$_SESSION['student_id'];

if ($current === '' || $new === '' || $confirm === '') {
    setFlash('error', 'All password fields are required.');
    header('Location: ../student-profile.php');
    exit;
}

if (strlen($new) < 6) {
    setFlash('error', 'New password must be at least 6 characters.');
    header('Location: ../student-profile.php');
    exit;
}

if ($new !== $confirm) {
    setFlash('error', 'New password and confirmation do not match.');
    header('Location: ../student-profile.php');
    exit;
}

$stmt = $pdo->prepare('SELECT password_hash FROM sa_students WHERE id = ? LIMIT 1');
$stmt->execute([$studentId]);
$hash = (string)($stmt->fetchColumn() ?: '');

if ($hash === '' || !password_verify($current, $hash)) {
    setFlash('error', 'Current password is incorrect.');
    header('Location: ../student-profile.php');
    exit;
}

$upd = $pdo->prepare('UPDATE sa_students SET password_hash = ?, updated_at = NOW() WHERE id = ?');
$upd->execute([password_hash($new, PASSWORD_DEFAULT), $studentId]);

setFlash('success', 'Password updated successfully.');
header('Location: ../student-profile.php');
exit;
