<?php
header('Content-Type: application/json');

require '../config/db.php';
require '../includes/functions.php';
require '../includes/member_module.php';

mm_ensure_volunteer_registration_columns($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$name = cleanInput($_POST['name']);
$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
$phone = cleanInput($_POST['phone']);
$qualification = cleanInput($_POST['qualification'] ?? '');
$profession = cleanInput($_POST['profession'] ?? '');
$maritalStatus = cleanInput($_POST['marital_status'] ?? '');
$address = cleanInput($_POST['address']);
$blood_group = cleanInput($_POST['blood_group']);
$district = cleanInput($_POST['district'] ?? '');
$state = cleanInput($_POST['state'] ?? '');
$localBodyType = cleanInput($_POST['local_body_type'] ?? '');
$localBodyName = cleanInput($_POST['local_body_name'] ?? '');
$wardNo = cleanInput($_POST['ward_no'] ?? '');
$wardName = cleanInput($_POST['ward_name'] ?? '');
$kudumbhaSamithi = cleanInput($_POST['kudumbha_samithi'] ?? '');

$dob = cleanInput($_POST['dob'] ?? '');

if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Full Name is required.']);
    exit;
}
if (!preg_match("/^[a-zA-Z\s'.\-]+$/", $name)) {
    echo json_encode(['success' => false, 'message' => 'Full Name should only contain letters, spaces, hyphens, apostrophes, and dots.']);
    exit;
}
if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Email Address is required.']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address format.']);
    exit;
}
if (empty($phone)) {
    echo json_encode(['success' => false, 'message' => 'Phone Number is required.']);
    exit;
}
if (!preg_match('/^[0-9]{10}$/', $phone)) {
    echo json_encode(['success' => false, 'message' => 'Phone Number must be exactly 10 digits.']);
    exit;
}
if (empty($qualification)) {
    echo json_encode(['success' => false, 'message' => 'Qualification is required.']);
    exit;
}
if (empty($dob)) {
    echo json_encode(['success' => false, 'message' => 'Date of Birth is required.']);
    exit;
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
    echo json_encode(['success' => false, 'message' => 'Invalid Date of Birth format. Use YYYY-MM-DD.']);
    exit;
}
if (empty($state)) {
    echo json_encode(['success' => false, 'message' => 'State is required.']);
    exit;
}
if (empty($district)) {
    echo json_encode(['success' => false, 'message' => 'District is required.']);
    exit;
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM volunteers WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetchColumn() > 0) {
    echo json_encode(['success' => false, 'message' => 'This email is already registered.']);
    exit;
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM volunteers WHERE phone = ?");
$stmt->execute([$phone]);
if ($stmt->fetchColumn() > 0) {
    echo json_encode(['success' => false, 'message' => 'This phone number is already registered.']);
    exit;
}

$photoPath = null;
if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
    if ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'Photo size must be under 2MB.']);
        exit;
    }

    $allowedTypes = ['image/jpeg', 'image/png'];
    if (!in_array($_FILES['photo']['type'], $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Only JPG or PNG photos are allowed.']);
        exit;
    }

    $targetDir = "../uploads/volunteers/";
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

    $fileName = time() . '_' . uniqid() . '_' . basename($_FILES['photo']['name']);
    if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetDir . $fileName)) {
        $photoPath = 'uploads/volunteers/' . $fileName;
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to upload photo.']);
        exit;
    }
}

try {
    $sql = "INSERT INTO volunteers (name, email, phone, qualification, profession, marital_status, address, district, state, local_body_type, local_body_name, ward_no, ward_name, kudumbha_samithi, blood_group, photo, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $name,
        $email,
        $phone,
        $qualification ?: null,
        $profession ?: null,
        $maritalStatus ?: null,
        $address ?: null,
        $district ?: null,
        $state ?: null,
        $localBodyType ?: null,
        $localBodyName ?: null,
        $wardNo ?: null,
        $wardName ?: null,
        $kudumbhaSamithi ?: null,
        $blood_group ?: null,
        $photoPath
    ]);

    echo json_encode(['success' => true, 'message' => 'Registration successful!']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'A database error occurred. Please try again.']);
}

exit;
