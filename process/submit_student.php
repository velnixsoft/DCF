<?php
header('Content-Type: application/json');

require '../config/db.php';
require '../includes/functions.php';
require '../includes/student/referral_service.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$csrf = (string)($_POST['csrf_token'] ?? '');
if ($csrf === '' || empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], $csrf)) {
    echo json_encode(['success' => false, 'message' => 'Security token expired. Please refresh and try again.']);
    exit;
}

if (!rate_limit_check('student_register_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 5, 3600)) {
    echo json_encode(['success' => false, 'message' => 'Too many registration attempts. Please try again later.']);
    exit;
}

$fullName = cleanInput($_POST['full_name'] ?? '');
if (!preg_match('/^[a-zA-Z\s]+$/', $fullName)) {
    echo json_encode([
        'success' => false,
        'message' => 'Full Name should only contain letters and spaces.'
    ]);
    exit;
}
$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$mobile = trim($_POST['mobile'] ?? '');

if (!preg_match('/^[6-9][0-9]{9}$/', $mobile) || preg_match('/^(.)\1{9}$/', $mobile)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please enter a valid 10-digit mobile number starting with 6-9 (cannot be all identical digits).'
    ]);
    exit;
}
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';
$gender = cleanInput($_POST['gender'] ?? '');
$collegeName = cleanInput($_POST['college_name'] ?? '');
if (!preg_match('/^[a-zA-Z\s]+$/', $collegeName)) {
    echo json_encode([
        'success' => false,
        'message' => 'College / Institution should only contain letters and spaces.'
    ]);
    exit;
}
$departmentName = cleanInput($_POST['department_name'] ?? '');
if (!preg_match('/^[a-zA-Z\s]+$/', $departmentName)) {
    echo json_encode([
        'success' => false,
        'message' => 'Department / Stream should only contain letters and spaces.'
    ]);
    exit;
}
$yearOfStudy = cleanInput($_POST['year_of_study'] ?? '');
$stateName = cleanInput($_POST['state_name'] ?? '');
$cityName = cleanInput($_POST['city_name'] ?? '');
if (!preg_match('/^[a-zA-Z\s]+$/', $cityName)) {
    echo json_encode([
        'success' => false,
        'message' => 'City / District should only contain letters and spaces.'
    ]);
    exit;
}
$address = cleanInput($_POST['address'] ?? '');
$referredByCode = strtoupper(trim(cleanInput($_POST['referred_by_code'] ?? '')));

if (empty($fullName) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($mobile) || empty($password) || empty($confirmPassword) || empty($gender) || empty($collegeName) || empty($departmentName) || empty($yearOfStudy) || empty($stateName) || empty($cityName)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields marked with *.']);
    exit;
}

if ($password !== $confirmPassword) {
    echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
    exit;
}

// 1. Email check
$stmt = $pdo->prepare("SELECT status FROM sa_students WHERE email = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$email]);
$existingEmailStatus = $stmt->fetchColumn();
if ($existingEmailStatus !== false) {
    $message = ((string)$existingEmailStatus === 'Pending')
        ? 'This email address is already used in a pending student ambassador application.'
        : 'This email address is already registered as an ambassador.';
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

// 2. Mobile check
$stmt = $pdo->prepare("SELECT status FROM sa_students WHERE mobile = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$mobile]);
$existingMobileStatus = $stmt->fetchColumn();
if ($existingMobileStatus !== false) {
    $message = ((string)$existingMobileStatus === 'Pending')
        ? 'This mobile number is already used in a pending student ambassador application.'
        : 'This mobile number is already registered as an ambassador.';
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

// 3. Referral code check (if provided)
$referredById = null;
if ($referredByCode !== '') {
    $refStmt = $pdo->prepare("SELECT id FROM sa_students WHERE referral_code = ? AND status = 'Active' AND is_verified = 1 LIMIT 1");
    $refStmt->execute([$referredByCode]);
    $referringStudent = $refStmt->fetch();
    if (!$referringStudent) {
        echo json_encode(['success' => false, 'message' => 'The referral / sponsor code you entered is invalid or not yet eligible for referrals.']);
        exit;
    }
    $referredById = (int)$referringStudent['id'];
}

// 4. Generate Student No
$idStmt = $pdo->query("SELECT IFNULL(MAX(id), 0) + 1 FROM sa_students");
$nextId = (int)$idStmt->fetchColumn();
$studentNo = "SA-" . date('Y') . "-" . sprintf("%04d", $nextId);

// 5. Generate unique Referral Code
$referralCode = '';
$isUnique = false;
$attempts = 0;
while (!$isUnique && $attempts < 10) {
    $code = "INT-" . strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM sa_students WHERE referral_code = ?");
    $checkStmt->execute([$code]);
    if ($checkStmt->fetchColumn() == 0) {
        $referralCode = $code;
        $isUnique = true;
    }
    $attempts++;
}
if (!$isUnique) {
    $referralCode = "INT-" . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
}

// Hash password
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

try {
    $sql = "INSERT INTO sa_students (
                student_no, full_name, email, mobile, password_hash, status, is_verified, 
                gender, college_name, city_name, state_name, department_name, year_of_study, 
                address, referral_code, referred_by_student_id, total_points, level_name, 
                certificates_earned, created_at
            ) VALUES (?, ?, ?, ?, ?, 'Pending', 0, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 'Student Ambassador', 0, NOW())";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $studentNo,
        $fullName,
        $email,
        $mobile,
        $passwordHash,
        $gender ?: null,
        $collegeName ?: null,
        $cityName ?: null,
        $stateName ?: null,
        $departmentName ?: null,
        $yearOfStudy ?: null,
        $address ?: null,
        $referralCode,
        $referredById,
    ]);
    
    $insertedId = (int)$pdo->lastInsertId();

    if ($referredById !== null) {
        $referralService = new StudentReferralService($pdo);
        $referralService->createPendingReferral(
            $referredById,
            $insertedId,
            $referredByCode,
            [
                'full_name' => $fullName,
                'email' => $email,
                'mobile' => $mobile,
            ]
        );
    }
    
    // Log user onboarding activity
    $logSql = "INSERT INTO sa_activity_logs (student_id, activity_type, title, description, points, created_at) 
               VALUES (?, 'onboarding', 'Registered on Platform', 'Student ambassador registration submitted', 0, NOW())";
    $logStmt = $pdo->prepare($logSql);
    $logStmt->execute([$insertedId]);

    echo json_encode([
        'success' => true,
        'student_no' => $studentNo,
        'referral_code' => $referralCode,
        'message' => 'Registration successful!'
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'A database error occurred during registration. Details: ' . $e->getMessage()]);
}

exit;
