<?php
header('Content-Type: application/json');

require '../config/db.php';
require '../includes/functions.php';
require '../includes/upload_validator.php';
require '../includes/student/vendor_leads.php';

if (empty($_SESSION['student_logged_in']) || empty($_SESSION['student_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please log in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$csrf = (string)($_POST['csrf_token'] ?? '');
if ($csrf === '' || empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], $csrf)) {
    echo json_encode(['success' => false, 'message' => 'Security token expired. Please refresh and try again.']);
    exit;
}

if (!rate_limit_check('vendor_lead_' . (int)$_SESSION['student_id'], 15, 3600)) {
    echo json_encode(['success' => false, 'message' => 'Too many lead submissions. Please try again later.']);
    exit;
}

$studentId = (int)$_SESSION['student_id'];
$proofPath = null;

if (isset($_FILES['proof_file']) && $_FILES['proof_file']['error'] === UPLOAD_ERR_OK) {
    $validation = validateUploadedFile(
        $_FILES['proof_file'],
        ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'],
        5 * 1024 * 1024,
        ['jpg', 'jpeg', 'png', 'webp', 'pdf']
    );
    if (empty($validation['success'])) {
        echo json_encode(['success' => false, 'message' => $validation['message'] ?? 'Invalid proof file.']);
        exit;
    }

    $stored = storeValidatedUpload(
        $_FILES['proof_file'],
        dirname(__DIR__) . '/uploads/vendor-leads',
        'uploads/vendor-leads',
        'lead'
    );
    if (empty($stored['success'])) {
        echo json_encode(['success' => false, 'message' => $stored['message'] ?? 'Failed to upload proof file.']);
        exit;
    }
    $proofPath = $stored['relative_path'];
}

$engine = new StudentVendorLeadEngine($pdo);
$result = $engine->submitLead($studentId, [
    'lead_type' => $_POST['lead_type'] ?? '',
    'business_name' => cleanInput($_POST['business_name'] ?? ''),
    'contact_name' => cleanInput($_POST['contact_name'] ?? ''),
    'contact_phone' => cleanInput($_POST['contact_phone'] ?? ''),
    'contact_email' => filter_var($_POST['contact_email'] ?? '', FILTER_SANITIZE_EMAIL),
    'city_name' => cleanInput($_POST['city_name'] ?? ''),
    'state_name' => cleanInput($_POST['state_name'] ?? ''),
    'meeting_date' => cleanInput($_POST['meeting_date'] ?? ''),
    'notes' => cleanInput($_POST['notes'] ?? ''),
    'proof_path' => $proofPath,
]);

echo json_encode($result);
exit;
