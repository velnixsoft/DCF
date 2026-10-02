<?php
session_start();
require '../config/db.php';
require '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../member-dashboard.php');
    exit;
}

if (empty($_SESSION['member_logged_in']) || empty($_SESSION['member_id'])) {
    setFlash('error', 'Please login to update profile.');
    header('Location: ../member-login.php');
    exit;
}

$csrf = (string)($_POST['csrf_token'] ?? '');
if ($csrf === '' || !isset($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], $csrf)) {
    setFlash('error', 'Invalid Security Token! Please try again.');
    header('Location: ../member-dashboard.php');
    exit;
}

$memberId = (int)$_SESSION['member_id'];
$phone = cleanInput($_POST['phone'] ?? '');
$dob = cleanInput($_POST['dob'] ?? '');
$gender = cleanInput($_POST['gender'] ?? '');
$address = cleanInput($_POST['address'] ?? '');

if ($memberId <= 0 || $phone === '') {
    setFlash('error', 'Phone is required.');
    header('Location: ../member-dashboard.php');
    exit;
}

if ($dob !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
    setFlash('error', 'Invalid DOB format.');
    header('Location: ../member-dashboard.php');
    exit;
}

$allowedGenders = ['', 'Male', 'Female', 'Other'];
if (!in_array($gender, $allowedGenders, true)) {
    $gender = '';
}

try {
    $stmt = $pdo->prepare("UPDATE members SET phone = ?, dob = ?, gender = ?, address = ? WHERE id = ? LIMIT 1");
    $stmt->execute([$phone, $dob !== '' ? $dob : null, $gender !== '' ? $gender : null, $address !== '' ? $address : null, $memberId]);

    $_SESSION['member_phone'] = $phone;
    setFlash('success', 'Profile updated.');
    header('Location: ../member-dashboard.php');
    exit;
} catch (Throwable $e) {
    setFlash('error', 'Unable to update profile right now.');
    header('Location: ../member-dashboard.php');
    exit;
}

