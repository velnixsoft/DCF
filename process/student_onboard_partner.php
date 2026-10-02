<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/student/portal_helpers.php';
require_once __DIR__ . '/../includes/student/points.php';
require_once __DIR__ . '/../includes/partner/portal_helpers.php';

// Authenticate student session
$student = student_portal_require_student($pdo, 'dashboard');
$studentId = (int)$student['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$csrf = (string)($_POST['csrf_token'] ?? '');
if ($csrf === '' || empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], $csrf)) {
    echo json_encode(['success' => false, 'message' => 'Security token expired. Please refresh and try again.']);
    exit;
}

// Extract inputs
$ownerName = cleanInput($_POST['owner_name'] ?? '');
$ownerEmail = filter_var($_POST['owner_email'] ?? '', FILTER_SANITIZE_EMAIL);
$ownerMobile = cleanInput($_POST['owner_mobile'] ?? '');
$password = (string)($_POST['password'] ?? '');
$confirmPassword = (string)($_POST['confirm_password'] ?? '');

$businessName = cleanInput($_POST['business_name'] ?? '');
$businessMobile = cleanInput($_POST['business_mobile'] ?? '');
$activityType = cleanInput($_POST['activity_type'] ?? 'retail');
$govtRegistered = cleanInput($_POST['govt_registered'] ?? 'N');
$presence = cleanInput($_POST['presence'] ?? 'ON');
$businessAddress = cleanInput($_POST['business_address'] ?? '');

$latitude = trim((string)($_POST['latitude'] ?? ''));
$longitude = trim((string)($_POST['longitude'] ?? ''));
$cityName = cleanInput($_POST['city_name'] ?? '');
$stateName = cleanInput($_POST['state_name'] ?? '');

$countryId = (int)($_POST['country_id'] ?? 93);
$stateId = (int)($_POST['state_id'] ?? 1);
$cityId = (int)($_POST['city_id'] ?? 1);
$parentBusinessPartnerId = (int)($_POST['parent_business_partner_id'] ?? 1);

// Standard Validations
if ($ownerName === '' || $businessName === '' || $ownerMobile === '' || !filter_var($ownerEmail, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please fill all required fields marked with *.']);
    exit;
}

if (!preg_match('/^[a-zA-Z\s]{2,100}$/', $ownerName)) {
    echo json_encode(['success' => false, 'message' => 'Owner Name must be between 2 and 100 characters and contain only letters and spaces.']);
    exit;
}

if (strlen($businessName) < 2 || strlen($businessName) > 150) {
    echo json_encode(['success' => false, 'message' => 'Business Name must be between 2 and 150 characters.']);
    exit;
}

if (!preg_match('/^[0-9]{10,15}$/', $ownerMobile)) {
    echo json_encode(['success' => false, 'message' => 'Owner Mobile must be a valid number of 10 to 15 digits.']);
    exit;
}

if ($businessMobile !== '' && !preg_match('/^[0-9]{10,15}$/', $businessMobile)) {
    echo json_encode(['success' => false, 'message' => 'Business Mobile must be a valid number of 10 to 15 digits.']);
    exit;
}

if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]{8,}$/', $password)) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters and contain at least one uppercase letter, one lowercase letter, one number, and one special character.']);
    exit;
}

if ($password !== $confirmPassword) {
    echo json_encode(['success' => false, 'message' => 'Password and confirm password do not match.']);
    exit;
}

if ($latitude === '' || $longitude === '' || !is_numeric($latitude) || !is_numeric($longitude)) {
    echo json_encode(['success' => false, 'message' => 'Valid location coordinates (latitude/longitude) are required.']);
    exit;
}

$latVal = (float)$latitude;
$lngVal = (float)$longitude;
if ($latVal < -90 || $latVal > 90 || $lngVal < -180 || $lngVal > 180) {
    echo json_encode(['success' => false, 'message' => 'Latitude/Longitude must be within valid bounds.']);
    exit;
}

if ($businessAddress === '') {
    echo json_encode(['success' => false, 'message' => 'Business address is required.']);
    exit;
}

// Verify email uniqueness locally first
$emailStmt = $pdo->prepare('SELECT COUNT(*) FROM sa_partners WHERE owner_email = ?');
$emailStmt->execute([$ownerEmail]);
if ((int)$emailStmt->fetchColumn() > 0) {
    echo json_encode(['success' => false, 'message' => 'This email is already registered as a partner.']);
    exit;
}

// Map Activity Type for External API
// local activity_type enum: 'retail', 'physical_activity_center'
// external activityType: 'R' (Retail), 'P' (Physical Activity/Gym)
$apiActivityType = ($activityType === 'physical_activity_center') ? 'P' : 'R';

// Build Swastr Register API payload
$apiPayload = [
    'ownerName' => $ownerName,
    'ownerCountryCode' => '+91',
    'ownerMobile' => $ownerMobile,
    'ownerEmail' => $ownerEmail,
    'password' => $password,
    'confirmPassword' => $confirmPassword,
    'businessName' => $businessName,
    'businessCountryCode' => '+91',
    'businessMobile' => ($businessMobile !== '') ? $businessMobile : $ownerMobile,
    'activityType' => $apiActivityType,
    'govtRegistered' => ($govtRegistered === 'Y') ? 'Y' : 'N',
    'presence' => ($presence === 'OFF') ? 'OFF' : 'ON',
    'addressLine' => $businessAddress,
    'countryId' => $countryId,
    'stateId' => $stateId,
    'cityId' => $cityId,
    'lat' => $latVal,
    'lon' => $lngVal,
    'mediaType' => 'I',
    'urlLocation' => ''
];

// Call dev.swastr.in API via cURL
$ch = curl_init('https://dev.swastr.in/partner/api/auth/register');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-Client-ID: SWASTRPP'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($apiPayload));
curl_setopt($ch, CURLOPT_TIMEOUT, 15);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($response === false) {
    echo json_encode(['success' => false, 'message' => 'External API registration failed to connect: ' . $curlError]);
    exit;
}

$resData = json_decode($response, true);

// Check for errors returned by Swastr API
if ($httpCode >= 400 || (isset($resData['success']) && !$resData['success'])) {
    $errMsg = $resData['message'] ?? $resData['error'] ?? 'External registration failed (HTTP ' . $httpCode . ')';
    if (is_array($errMsg)) {
        $errMsg = implode(', ', $errMsg);
    }
    echo json_encode(['success' => false, 'message' => 'External API registration rejected: ' . $errMsg]);
    exit;
}

//External API was successful! Now save locally
try {
    $partnerCode = partner_generate_code($pdo);
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    // Auto-generate API key
    $apiKey = partner_generate_api_key();

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO sa_partners (
            partner_code, owner_name, business_name, owner_mobile, business_mobile,
            owner_email, password_hash, activity_type, latitude, longitude,
            business_address, city_name, state_name, status, api_key, api_enabled,
            referred_by_student_id, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', ?, 1, ?, NOW(), NOW())
    ");
    
    $stmt->execute([
        $partnerCode,
        $ownerName,
        $businessName,
        $ownerMobile,
        $businessMobile !== '' ? $businessMobile : null,
        $ownerEmail,
        $passwordHash,
        $activityType, // local activity_type enum: 'retail' / 'physical_activity_center'
        round($latVal, 7),
        round($lngVal, 7),
        $businessAddress,
        $cityName !== '' ? $cityName : null,
        $stateName !== '' ? $stateName : null,
        $apiKey,
        $studentId
    ]);
    
    $partnerId = (int)$pdo->lastInsertId();

    // Award Onboarding Points to Student immediately
    $pointEngine = new StudentPointEngine($pdo);
    $pointResult = $pointEngine->awardPoints($studentId, 'VENDOR_ONBOARDING', [
        'source_type' => 'partner',
        'source_id' => $partnerId,
        'description' => 'Onboarded partner: ' . $businessName . ' (' . $partnerCode . ')',
        'reference_code' => 'PRT-' . $partnerId,
        'idempotency_key' => 'partner-onboard:' . $partnerId
    ]);

    if (!$pointResult['success']) {
        // Rollback transaction if points fail to allocate
        throw new Exception('Point allocation failed: ' . $pointResult['message']);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Partner onboarded successfully and registered with external API! Points awarded.',
        'partner_code' => $partnerCode,
        'points_awarded' => 50
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Failed to complete local registration: ' . $e->getMessage()]);
}
