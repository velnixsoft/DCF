<?php
header('Content-Type: application/json');

require '../config/db.php';
require '../includes/functions.php';
require '../includes/member_module.php';

mm_ensure_member_identity_columns($pdo);
mm_ensure_member_registration_columns($pdo);
mm_ensure_member_event_columns($pdo);
mm_ensure_achievement_positions_table($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$fullName = mm_clean($_POST['full_name'] ?? '');
$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$phone = mm_clean($_POST['phone'] ?? '');
$bloodGroup = mm_clean($_POST['blood_group'] ?? '');
$dob = mm_clean($_POST['dob'] ?? '');
$gender = mm_clean($_POST['gender'] ?? '');
$qualification = mm_clean($_POST['qualification'] ?? '');
$profession = mm_clean($_POST['profession'] ?? '');
$maritalStatus = mm_clean($_POST['marital_status'] ?? '');
$address = mm_clean($_POST['address'] ?? '');
$district = mm_clean($_POST['district'] ?? '');
$state = mm_clean($_POST['state'] ?? '');
$localBodyType = mm_clean($_POST['local_body_type'] ?? '');
$localBodyName = mm_clean($_POST['local_body_name'] ?? '');
$wardNo = mm_clean($_POST['ward_no'] ?? '');
$wardName = mm_clean($_POST['ward_name'] ?? '');
$kudumbhaSamithi = mm_clean($_POST['kudumbha_samithi'] ?? '');
$designationId = (int)($_POST['designation_id'] ?? 0);
$eventId = (int)($_POST['event_id'] ?? 0);
$membershipFeeInput = (float)($_POST['membership_fee'] ?? 0);
$paymentMode = strtolower(mm_clean($_POST['payment_mode'] ?? 'manual'));
$txnId = mm_clean($_POST['payment_txn_id'] ?? '');
$rzpOrderId = mm_clean($_POST['razorpay_order_id'] ?? '');
$rzpPaymentId = mm_clean($_POST['razorpay_payment_id'] ?? '');
$rzpSignature = mm_clean($_POST['razorpay_signature'] ?? '');
$refCode = mm_clean($_POST['ref'] ?? '');
$donationRef = mm_clean($_POST['mref'] ?? '');
$occasionName = mm_clean($_POST['occasion_name'] ?? '');
$achievementPositionId = (int)($_POST['achievement_position_id'] ?? 0);

if ($fullName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $phone === '' || $dob === '' || $designationId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Please fill all required fields.']);
    exit;
}

$designationStmt = $pdo->prepare("SELECT id, fee_amount FROM member_designations WHERE id = ? AND is_active = 1");
$designationStmt->execute([$designationId]);
$designation = $designationStmt->fetch(PDO::FETCH_ASSOC);
if (!$designation) {
    echo json_encode(['success' => false, 'message' => 'Selected designation is invalid.']);
    exit;
}

$expectedFee = (float)$designation['fee_amount'];
if ((int)round($expectedFee * 100) !== (int)round($membershipFeeInput * 100)) {
    echo json_encode(['success' => false, 'message' => 'Membership fee mismatch. Please re-select designation.']);
    exit;
}

$eventTitle = null;
$eventDate = null;
$eventLocation = null;
if ($eventId > 0) {
    $eventStmt = $pdo->prepare("SELECT id, title, event_date, location FROM events WHERE id = ? LIMIT 1");
    $eventStmt->execute([$eventId]);
    $event = $eventStmt->fetch(PDO::FETCH_ASSOC);
    if (!$event) {
        echo json_encode(['success' => false, 'message' => 'Selected event is invalid.']);
        exit;
    }
    $eventTitle = trim((string)($event['title'] ?? '')) ?: null;
    $eventDate = !empty($event['event_date']) ? (string)$event['event_date'] : null;
    $eventLocation = trim((string)($event['location'] ?? '')) ?: null;
}

$achievementPosition = null;
if ($achievementPositionId > 0) {
    $positionStmt = $pdo->prepare("SELECT title FROM achievement_positions WHERE id = ? AND is_active = 1 LIMIT 1");
    $positionStmt->execute([$achievementPositionId]);
    $achievementPosition = $positionStmt->fetchColumn() ?: null;
    if ($achievementPosition === null) {
        echo json_encode(['success' => false, 'message' => 'Selected result / position is invalid.']);
        exit;
    }
}

if (!in_array($paymentMode, ['manual', 'razorpay'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment mode.']);
    exit;
}

$settings = mm_load_settings($pdo);
$paymentGateway = $paymentMode === 'razorpay' ? 'Razorpay' : 'Manual';
$paymentStatus = 'Pending';
$memberStatus = 'Pending';
$memberNo = null;
$memberReceiptNo = null;
$memberSince = null;
$validUntil = null;

if ($paymentMode === 'manual') {
    if ($txnId === '') {
        echo json_encode(['success' => false, 'message' => 'Transaction ID is required for manual payment.']);
        exit;
    }
} else {
    if ($rzpOrderId === '' || $rzpPaymentId === '' || $rzpSignature === '') {
        echo json_encode(['success' => false, 'message' => 'Razorpay payment details are missing.']);
        exit;
    }

    $keySecret = trim($settings['razorpay_key_secret'] ?? '');
    if ($keySecret === '') {
        echo json_encode(['success' => false, 'message' => 'Razorpay is not configured by admin.']);
        exit;
    }

    $generated = hash_hmac('sha256', $rzpOrderId . '|' . $rzpPaymentId, $keySecret);
    if (!hash_equals($generated, $rzpSignature)) {
        echo json_encode(['success' => false, 'message' => 'Payment signature verification failed.']);
        exit;
    }

    $txnId = $rzpPaymentId;
    $paymentStatus = 'Success';
    $memberStatus = 'Active';
    $memberNo = mm_next_member_no($pdo, $settings['member_prefix'] ?? 'MEM-');
    $memberReceiptNo = mm_next_receipt_no($pdo, $settings['member_receipt_prefix'] ?? 'MRCPT-');
    $memberSince = date('Y-m-d');
    $validUntil = date('Y-m-d', strtotime('+1 year'));
}

$dupStmt = $pdo->prepare("SELECT id FROM members WHERE email = ?");
$dupStmt->execute([$email]);
if ($dupStmt->fetchColumn()) {
    echo json_encode(['success' => false, 'message' => 'This email is already registered as a member.']);
    exit;
}

$paymentProof = null;
if ($paymentMode === 'manual' && isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === 0) {
    if ($_FILES['payment_proof']['size'] > 2 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'Payment proof size must be under 2MB.']);
        exit;
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($_FILES['payment_proof']['type'], $allowedTypes, true)) {
        echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, or WEBP files are allowed.']);
        exit;
    }

    $targetDir = "../uploads/members/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileName = time() . '_' . uniqid() . '_' . basename($_FILES['payment_proof']['name']);
    if (!move_uploaded_file($_FILES['payment_proof']['tmp_name'], $targetDir . $fileName)) {
        echo json_encode(['success' => false, 'message' => 'Failed to upload payment proof.']);
        exit;
    }
    $paymentProof = 'uploads/members/' . $fileName;
}

$memberPhoto = null;
if (isset($_FILES['member_photo']) && $_FILES['member_photo']['error'] === 0) {
    if ($_FILES['member_photo']['size'] > 2 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'Member photo size must be under 2MB.']);
        exit;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? finfo_file($finfo, $_FILES['member_photo']['tmp_name']) : ($_FILES['member_photo']['type'] ?? '');
    if ($finfo) {
        finfo_close($finfo);
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($mime, $allowedTypes, true)) {
        echo json_encode(['success' => false, 'message' => 'Member photo must be JPG, PNG, or WEBP.']);
        exit;
    }

    $targetDir = "../uploads/members/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileName = time() . '_' . uniqid() . '_' . basename($_FILES['member_photo']['name']);
    if (!move_uploaded_file($_FILES['member_photo']['tmp_name'], $targetDir . $fileName)) {
        echo json_encode(['success' => false, 'message' => 'Failed to upload member photo.']);
        exit;
    }
    $memberPhoto = 'uploads/members/' . $fileName;
}

$referredById = null;
$source = 'none';
if ($refCode !== '') {
    $rStmt = $pdo->prepare("SELECT id FROM members WHERE referral_code = ? LIMIT 1");
    $rStmt->execute([$refCode]);
    $referredById = $rStmt->fetchColumn() ?: null;
    if ($referredById) {
        $source = 'member_ref';
    }
}
if (!$referredById && $donationRef !== '') {
    $rStmt = $pdo->prepare("SELECT id FROM members WHERE donation_ref_code = ? LIMIT 1");
    $rStmt->execute([$donationRef]);
    $referredById = $rStmt->fetchColumn() ?: null;
    if ($referredById) {
        $source = 'donation_ref';
    }
}

try {
    $referralCode = mm_rand_token('MRF-');
    $donationRefCode = mm_rand_token('DRF-');

    $sql = "INSERT INTO members
        (member_no, full_name, email, phone, blood_group, dob, gender, qualification, profession, marital_status, address, district, state, local_body_type, local_body_name, ward_no, ward_name, kudumbha_samithi, photo, designation_id, event_id, event_title, event_date, event_location, occasion_name, achievement_position, membership_fee, payment_gateway, payment_status, payment_txn_id, razorpay_order_id, razorpay_payment_id, razorpay_signature, payment_proof, status, referral_code, donation_ref_code, referred_by_member_id, referred_by_source, member_receipt_no, member_since, valid_until)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $memberNo,
        $fullName,
        $email,
        $phone,
        $bloodGroup ?: null,
        $dob,
        $gender ?: null,
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
        $memberPhoto,
        $designationId,
        $eventId > 0 ? $eventId : null,
        $eventTitle,
        $eventDate,
        $eventLocation,
        $occasionName !== '' ? $occasionName : null,
        $achievementPosition,
        $expectedFee,
        $paymentGateway,
        $paymentStatus,
        $txnId,
        $rzpOrderId ?: null,
        $rzpPaymentId ?: null,
        $rzpSignature ?: null,
        $paymentProof,
        $memberStatus,
        $referralCode,
        $donationRefCode,
        $referredById,
        $source,
        $memberReceiptNo,
        $memberSince,
        $validUntil
    ]);
    $memberId = (int)$pdo->lastInsertId();

    if ($paymentMode === 'razorpay') {
        $siteName = $settings['site_name'] ?? 'NGO';
        $receiptLink = (rtrim($settings['ngo_website'] ?? '', '/') !== '')
            ? rtrim($settings['ngo_website'], '/') . '/download-member-receipt.php?member_no=' . urlencode((string)$memberNo) . '&email=' . urlencode($email)
            : '';

        try {
            $body = '<p>Dear ' . htmlspecialchars($fullName) . ',</p>'
                . '<p>Your membership payment was successful.</p>'
                . '<p><strong>Member No:</strong> ' . htmlspecialchars((string)$memberNo) . '<br>'
                . '<strong>Receipt No:</strong> ' . htmlspecialchars((string)$memberReceiptNo) . '</p>';
            if ($receiptLink !== '') {
                $body .= '<p><a href="' . htmlspecialchars($receiptLink) . '">Download Membership Receipt (PDF)</a></p>';
            }
            $body .= '<p>Regards,<br>' . htmlspecialchars($siteName) . '</p>';
            mm_send_email($settings, $email, $fullName, 'Membership Payment Successful - ' . $siteName, $body);
        } catch (Exception $e) {
        }
    }

    echo json_encode([
        'success' => true,
        'message' => $paymentMode === 'razorpay'
            ? 'Payment successful. Membership activated.'
            : 'Membership application submitted successfully. Admin verification pending.',
        'member_id' => $memberId
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
}
exit;
