<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/partner/portal_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$csrf = (string)($_POST['csrf_token'] ?? '');
if ($csrf === '' || empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], $csrf)) {
    echo json_encode(['success' => false, 'message' => 'Security token expired. Please refresh and try again.']);
    exit;
}

if (!rate_limit_check('partner_register_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 5, 3600)) {
    echo json_encode(['success' => false, 'message' => 'Too many registration attempts. Please try again later.']);
    exit;
}

$ownerName = cleanInput($_POST['owner_name'] ?? '');
$businessName = cleanInput($_POST['business_name'] ?? '');
$ownerMobile = cleanInput($_POST['owner_mobile'] ?? '');
$businessMobile = cleanInput($_POST['business_mobile'] ?? '');
$ownerEmail = filter_var($_POST['owner_email'] ?? '', FILTER_SANITIZE_EMAIL);
$password = (string)($_POST['password'] ?? '');
$confirmPassword = (string)($_POST['confirm_password'] ?? '');
$activityType = cleanInput($_POST['activity_type'] ?? 'retail');
$latitude = trim((string)($_POST['latitude'] ?? ''));
$longitude = trim((string)($_POST['longitude'] ?? ''));
$businessAddress = cleanInput($_POST['business_address'] ?? '');
$cityName = cleanInput($_POST['city_name'] ?? '');
$stateName = cleanInput($_POST['state_name'] ?? '');

if ($ownerName === '' || $businessName === '' || $ownerMobile === '' || !filter_var($ownerEmail, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please fill all required fields marked with *.']);
    exit;
}

// Owner Name validation
if (!preg_match('/^[a-zA-Z\s]{2,100}$/', $ownerName)) {
    echo json_encode(['success' => false, 'message' => 'Owner Name must be between 2 and 100 characters and contain only letters and spaces.']);
    exit;
}

// Business Name validation
if (strlen($businessName) < 2 || strlen($businessName) > 150) {
    echo json_encode(['success' => false, 'message' => 'Business Name must be between 2 and 150 characters.']);
    exit;
}

// Mobile validations (digits only, 10 to 15 digits)
if (!preg_match('/^[0-9]{10,15}$/', $ownerMobile)) {
    echo json_encode(['success' => false, 'message' => 'Owner Mobile must be a valid number of 10 to 15 digits.']);
    exit;
}

if ($businessMobile !== '' && !preg_match('/^[0-9]{10,15}$/', $businessMobile)) {
    echo json_encode(['success' => false, 'message' => 'Business Mobile must be a valid number of 10 to 15 digits.']);
    exit;
}

// Password strength validation (min 8 chars, 1 upper, 1 lower, 1 digit, 1 special)
if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]{8,}$/', $password)) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters and contain at least one uppercase letter, one lowercase letter, one number, and one special character.']);
    exit;
}

if ($password !== $confirmPassword) {
    echo json_encode(['success' => false, 'message' => 'Password and confirm password do not match.']);
    exit;
}

// Dynamic activity type categories lookup from database settings
$settingsList = [];
try {
    $sstmt = $pdo->query("SELECT * FROM settings WHERE setting_key = 'partner_categories_json'");
    $settRow = $sstmt->fetch();
    $categoriesRaw = json_decode($settRow['setting_value'] ?? '[]', true);
} catch (Throwable $e) {
    $categoriesRaw = [];
}
$validCategories = [];
if (is_array($categoriesRaw)) {
    foreach ($categoriesRaw as $cat) {
        if (isset($cat['id'])) {
            $validCategories[] = (string)$cat['id'];
        }
    }
}
if (empty($validCategories)) {
    $validCategories = ['retail', 'physical_activity_center']; // fallback
}

if (!in_array($activityType, $validCategories, true)) {
    echo json_encode(['success' => false, 'message' => 'Please select a valid activity type.']);
    exit;
}

// Location coordinates validations
if ($latitude === '' || $longitude === '' || !is_numeric($latitude) || !is_numeric($longitude)) {
    echo json_encode(['success' => false, 'message' => 'Valid location coordinates (latitude/longitude) are required.']);
    exit;
}

$latVal = (float)$latitude;
$lngVal = (float)$longitude;
if ($latVal < -90 || $latVal > 90) {
    echo json_encode(['success' => false, 'message' => 'Latitude must be between -90 and 90.']);
    exit;
}
if ($lngVal < -180 || $lngVal > 180) {
    echo json_encode(['success' => false, 'message' => 'Longitude must be between -180 and 180.']);
    exit;
}

if ($businessAddress === '') {
    echo json_encode(['success' => false, 'message' => 'Business address is required.']);
    exit;
}

// City / State validation (if provided)
if ($cityName !== '' && !preg_match('/^[a-zA-Z\s\.\-]{2,100}$/', $cityName)) {
    echo json_encode(['success' => false, 'message' => 'City name must be between 2 and 100 characters and contain only letters and spaces.']);
    exit;
}
if ($stateName !== '' && !preg_match('/^[a-zA-Z\s]{2,100}$/', $stateName)) {
    echo json_encode(['success' => false, 'message' => 'State name must be between 2 and 100 characters and contain only letters and spaces.']);
    exit;
}

try {
    if (!dbTableExists($pdo, 'sa_partners')) {
        echo json_encode(['success' => false, 'message' => 'Partner portal is not configured. Please contact administrator.']);
        exit;
    }

    $emailStmt = $pdo->prepare('SELECT COUNT(*) FROM sa_partners WHERE owner_email = ?');
    $emailStmt->execute([$ownerEmail]);
    if ((int)$emailStmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'This email is already registered as a partner.']);
        exit;
    }

    $partnerCode = partner_generate_code($pdo);
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        INSERT INTO sa_partners (
            partner_code, owner_name, business_name, owner_mobile, business_mobile,
            owner_email, password_hash, activity_type, latitude, longitude,
            business_address, city_name, state_name, status, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW(), NOW())
    ");
    $stmt->execute([
        $partnerCode,
        $ownerName,
        $businessName,
        $ownerMobile,
        $businessMobile !== '' ? $businessMobile : null,
        $ownerEmail,
        $passwordHash,
        $activityType,
        round((float)$latitude, 7),
        round((float)$longitude, 7),
        $businessAddress,
        $cityName !== '' ? $cityName : null,
        $stateName !== '' ? $stateName : null,
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Partner registration submitted successfully. Admin will review and activate your account.',
        'partner_code' => $partnerCode,
    ]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
}
