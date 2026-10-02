<?php
session_start();
require '../config/db.php';
require '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../student-profile.php');
    exit;
}

if (empty($_SESSION['student_logged_in']) || empty($_SESSION['student_id'])) {
    setFlash('error', 'Please login to update your profile.');
    header('Location: ../student-login.php');
    exit;
}

$csrf = (string)($_POST['csrf_token'] ?? '');
if ($csrf === '' || empty($_SESSION['student_profile_csrf']) || !hash_equals((string)$_SESSION['student_profile_csrf'], $csrf)) {
    setFlash('error', 'Security check failed. Please try again.');
    header('Location: ../student-profile.php');
    exit;
}

$studentId = (int)$_SESSION['student_id'];
$fullName = cleanInput($_POST['full_name'] ?? '');
$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$mobile = trim((string)($_POST['mobile'] ?? ''));
$gender = cleanInput($_POST['gender'] ?? '');
$collegeName = cleanInput($_POST['college_name'] ?? '');
$departmentName = cleanInput($_POST['department_name'] ?? '');
$yearOfStudy = cleanInput($_POST['year_of_study'] ?? '');
$stateName = cleanInput($_POST['state_name'] ?? '');
$cityName = cleanInput($_POST['city_name'] ?? '');
$address = cleanInput($_POST['address'] ?? '');

if ($fullName === '' || $email === '' || $mobile === '' || $gender === '' || $collegeName === '' || $departmentName === '' || $yearOfStudy === '' || $stateName === '' || $cityName === '') {
    setFlash('error', 'Please complete all required profile fields.');
    header('Location: ../student-profile.php');
    exit;
}

if (!preg_match("/^[a-zA-Z\s'\.\-]+$/", $fullName)) {
    setFlash('error', 'Full Name should only contain letters, spaces, hyphens, apostrophes, and dots.');
    header('Location: ../student-profile.php');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    setFlash('error', 'Please enter a valid email address.');
    header('Location: ../student-profile.php');
    exit;
}

$allowedGenders = ['Male', 'Female', 'Other'];
if (!in_array($gender, $allowedGenders, true)) {
    setFlash('error', 'Invalid gender selected.');
    header('Location: ../student-profile.php');
    exit;
}

if (!preg_match('/^[6-9][0-9]{9}$/', $mobile) || preg_match('/^(.)\1{9}$/', $mobile)) {
    setFlash('error', 'Please enter a valid 10-digit mobile number starting with 6-9 and not using identical digits.');
    header('Location: ../student-profile.php');
    exit;
}

try {
    $oldStmt = $pdo->prepare("SELECT * FROM sa_students WHERE id = ? LIMIT 1");
    $oldStmt->execute([$studentId]);
    $old = $oldStmt->fetch(PDO::FETCH_ASSOC);

    if (!$old) {
        setFlash('error', 'Student account not found.');
        header('Location: ../student-profile.php');
        exit;
    }

    $emailCheck = $pdo->prepare("SELECT status FROM sa_students WHERE email = ? AND id <> ? ORDER BY id DESC LIMIT 1");
    $emailCheck->execute([$email, $studentId]);
    $existingEmailStatus = $emailCheck->fetchColumn();
    if ($existingEmailStatus !== false) {
        $message = ((string)$existingEmailStatus === 'Pending')
            ? 'This email address is already used in a pending student ambassador application.'
            : 'This email address is already registered as an ambassador.';
        setFlash('error', $message);
        header('Location: ../student-profile.php');
        exit;
    }

    $mobileCheck = $pdo->prepare("SELECT status FROM sa_students WHERE mobile = ? AND id <> ? ORDER BY id DESC LIMIT 1");
    $mobileCheck->execute([$mobile, $studentId]);
    $existingMobileStatus = $mobileCheck->fetchColumn();
    if ($existingMobileStatus !== false) {
        $message = ((string)$existingMobileStatus === 'Pending')
            ? 'This mobile number is already used in a pending student ambassador application.'
            : 'This mobile number is already registered as an ambassador.';
        setFlash('error', $message);
        header('Location: ../student-profile.php');
        exit;
    }

    $changes = [];
    if ($old['full_name'] !== $fullName) $changes[] = "Name: '{$old['full_name']}' -> '{$fullName}'";
    if ($old['email'] !== $email) $changes[] = "Email: '{$old['email']}' -> '{$email}'";
    if ($old['mobile'] !== $mobile) $changes[] = "Mobile: '{$old['mobile']}' -> '{$mobile}'";
    if ($old['gender'] !== $gender) $changes[] = "Gender: '{$old['gender']}' -> '{$gender}'";
    if ($old['college_name'] !== $collegeName) $changes[] = "College: '{$old['college_name']}' -> '{$collegeName}'";
    if ($old['department_name'] !== $departmentName) $changes[] = "Department: '{$old['department_name']}' -> '{$departmentName}'";
    if ($old['year_of_study'] !== $yearOfStudy) $changes[] = "Year of Study: '{$old['year_of_study']}' -> '{$yearOfStudy}'";
    if ($old['state_name'] !== $stateName) $changes[] = "State: '{$old['state_name']}' -> '{$stateName}'";
    if ($old['city_name'] !== $cityName) $changes[] = "City: '{$old['city_name']}' -> '{$cityName}'";
    $oldAddress = $old['address'] !== null ? $old['address'] : '';
    if ($oldAddress !== $address) $changes[] = "Address: '{$oldAddress}' -> '{$address}'";

    if (!empty($changes)) {
        $desc = "Changed: " . implode(', ', $changes);

        $updateStmt = $pdo->prepare("
            UPDATE sa_students
            SET full_name = ?, email = ?, mobile = ?, gender = ?, college_name = ?, department_name = ?, year_of_study = ?, state_name = ?, city_name = ?, address = ?, status = 'Pending', is_verified = 0, updated_at = NOW()
            WHERE id = ?
            LIMIT 1
        ");
        $updateStmt->execute([
            $fullName,
            $email,
            $mobile,
            $gender,
            $collegeName,
            $departmentName,
            $yearOfStudy,
            $stateName,
            $cityName,
            $address !== '' ? $address : null,
            $studentId,
        ]);

        $logStmt = $pdo->prepare("
            INSERT INTO sa_activity_logs (student_id, activity_type, title, description, points, created_at)
            VALUES (?, 'profile_update', 'Profile Updated (Verification Required)', ?, 0, NOW())
        ");
        $logStmt->execute([$studentId, $desc]);

        // Destroy session / Logout the student so they have to wait for approval
        unset($_SESSION['student_logged_in'], $_SESSION['student_id'], $_SESSION['student_no'], $_SESSION['student_name'], $_SESSION['student_email']);

        setFlash('warning', 'Your profile details have been updated and require Admin verification. Your account status is now Pending.');
        header('Location: ../student-login.php');
        exit;
    } else {
        setFlash('info', 'No changes were made to your profile.');
        header('Location: ../student-profile.php');
        exit;
    }
} catch (Throwable $e) {
    setFlash('error', 'Unable to save profile right now.');
    header('Location: ../student-profile.php');
    exit;
}
