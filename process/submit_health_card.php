<?php
// ============================================================
// process/submit_health_card.php
// Handles Health Card Applications & Renewals from Website & Member Dashboard
// ============================================================

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/upload_validator.php';
require_once __DIR__ . '/../includes/health_card_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

try {
    // 1. CSRF Verification
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$csrfToken)) {
        throw new Exception('Security validation failed (Invalid CSRF Token). Please refresh and try again.');
    }

    // 2. Extract and sanitize inputs
    $applicantName = cleanInput($_POST['applicant_name'] ?? '');
    $contact = cleanInput($_POST['contact'] ?? '');
    $email = filter_var(trim((string)($_POST['email'] ?? '')), FILTER_SANITIZE_EMAIL);
    $dob = cleanInput($_POST['dob'] ?? '');
    $gender = cleanInput($_POST['gender'] ?? 'Male');
    $bloodGroup = cleanInput($_POST['blood_group'] ?? '');
    $aadhaarNo = cleanInput($_POST['aadhaar_no'] ?? '');
    $emergencyContact = cleanInput($_POST['emergency_contact'] ?? '');
    $state = cleanInput($_POST['state'] ?? '');
    $district = cleanInput($_POST['district'] ?? '');
    $block = cleanInput($_POST['block'] ?? '');
    $pincode = cleanInput($_POST['pincode'] ?? '');
    $address = cleanInput($_POST['address'] ?? '');
    $remarks = cleanInput($_POST['remarks'] ?? '');

    // Renewal tracking
    $renewFromCardNumber = cleanInput($_POST['renew_from_card_number'] ?? '');
    $renewFromId = filter_input(INPUT_POST, 'renew_from_id', FILTER_VALIDATE_INT);
    $isRenewal = !empty($renewFromCardNumber) || !empty($renewFromId);

    // Logged in member ID if available
    $memberId = !empty($_SESSION['member_id']) ? (int)$_SESSION['member_id'] : null;
    $beneficiaryId = !empty($_POST['beneficiary_id']) ? (int)$_POST['beneficiary_id'] : null;

    // 3. Validation
    if (empty($applicantName)) {
        throw new Exception('Applicant Full Name is required.');
    }
    if (empty($contact)) {
        throw new Exception('Contact Mobile Number is required.');
    }
    if (empty($state) || empty($district)) {
        throw new Exception('State and District are required.');
    }
    if (empty($address)) {
        throw new Exception('Complete Residential Address is required.');
    }

    $allowedGenders = ['Male', 'Female', 'Other'];
    if (!in_array($gender, $allowedGenders, true)) {
        $gender = 'Male';
    }

    // Calculate Age from DOB if provided
    $age = null;
    if (!empty($dob)) {
        $dobDate = new DateTime($dob);
        $now = new DateTime();
        $age = $now->diff($dobDate)->y;
    } elseif (!empty($_POST['age'])) {
        $age = (int)$_POST['age'];
    }

    // 4. Handle Photo Upload or Reuse Old Card Photo
    $photoPath = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadDir = __DIR__ . '/../uploads/health_cards';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $val = validateUploadedFile(
            $_FILES['photo'],
            ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
            5 * 1024 * 1024,
            ['jpg', 'jpeg', 'png', 'webp', 'gif']
        );

        if (!$val['success']) {
            throw new Exception('Photo upload error: ' . ($val['message'] ?? 'Invalid file'));
        }

        $stored = storeValidatedUpload($_FILES['photo'], $uploadDir, 'uploads/health_cards', 'hc_photo');
        if ($stored['success']) {
            $photoPath = $stored['relative_path'];
        }
    }

    // If no new photo uploaded during renewal, reuse previous photo
    if (empty($photoPath) && $isRenewal) {
        if ($renewFromCardNumber) {
            $pStmt = $pdo->prepare("SELECT photo FROM health_cards WHERE card_number = ? LIMIT 1");
            $pStmt->execute([$renewFromCardNumber]);
            $photoPath = $pStmt->fetchColumn() ?: null;
        } elseif ($renewFromId) {
            $pStmt = $pdo->prepare("SELECT photo FROM health_cards WHERE id = ? LIMIT 1");
            $pStmt->execute([$renewFromId]);
            $photoPath = $pStmt->fetchColumn() ?: null;
        }
    }

    // 5. Generate Card Number or set placeholder for Admin Approval
    $cardNumber = hc_generate_card_number($pdo);

    // 6. Dates & Status
    $issueDate = date('Y-m-d');
    $expiryDate = date('Y-m-d', strtotime('+1 year'));
    $status = 'pending_approval'; // Routed to Admin for approval

    // 7. QR Code generation URL
    $qrVerificationUrl = appBaseUrl() . "/verify-health-card.php?card=" . urlencode($cardNumber);
    $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($qrVerificationUrl);

    // Set renewal remarks
    if ($isRenewal && empty($remarks)) {
        $remarks = "Renewal application for previous Health Card: " . ($renewFromCardNumber ?: '#' . $renewFromId);
    }

    // 8. Insert into Database
    $insertStmt = $pdo->prepare("
        INSERT INTO health_cards (
            card_number, previous_card_number, applicant_name, contact, email, dob,
            age, gender, blood_group, aadhaar_no, emergency_contact,
            state, district, block, pincode, address,
            photo, issue_date, expiry_date, status,
            beneficiary_id, member_id, qr_code_path, remarks, created_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?, ?, NOW()
        )
    ");

    $insertStmt->execute([
        $cardNumber, $renewFromCardNumber ?: null, $applicantName, $contact, $email ?: null, $dob ?: null,
        $age, $gender, $bloodGroup ?: null, $aadhaarNo ?: null, $emergencyContact ?: null,
        $state, $district, $block ?: null, $pincode ?: null, $address,
        $photoPath, $issueDate, $expiryDate, $status,
        $beneficiaryId, $memberId, $qrCodeUrl, $remarks ?: null
    ]);

    $cardId = (int)$pdo->lastInsertId();

    $successMsg = $isRenewal
        ? "Health Card renewal application for " . htmlspecialchars($renewFromCardNumber ?: 'previous card') . " submitted successfully! Application ID: #{$cardId}. Once approved by the administrator, your renewed card will be available for download."
        : "Health Card application submitted successfully! Application ID: #{$cardId}. Your application is under review and will be verified shortly.";

    echo json_encode([
        'success' => true,
        'message' => $successMsg,
        'is_renewal' => $isRenewal,
        'card_id' => $cardId,
        'card_number' => $cardNumber,
        'applicant_name' => $applicantName,
        'contact' => $contact,
        'issue_date' => $issueDate,
        'expiry_date' => $expiryDate,
        'status' => $status,
        'photo' => $photoPath,
        'qr_code' => $qrCodeUrl,
        'blood_group' => $bloodGroup
    ]);

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
