<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/member_module.php';

api_require_method(['POST']);
mm_ensure_member_identity_columns($pdo);

$fullName = mm_clean(api_input('full_name', ''));
$email = filter_var((string)api_input('email', ''), FILTER_SANITIZE_EMAIL);
$phone = mm_clean(api_input('phone', ''));
$bloodGroup = mm_clean(api_input('blood_group', ''));
$dob = mm_clean(api_input('dob', ''));
$gender = mm_clean(api_input('gender', ''));
$address = mm_clean(api_input('address', ''));
$designationId = (int)api_input('designation_id', 0);
$membershipFeeInput = (float)api_input('membership_fee', 0);
$paymentMode = strtolower(mm_clean(api_input('payment_mode', 'manual')));
$txnId = mm_clean(api_input('payment_txn_id', ''));
$rzpOrderId = mm_clean(api_input('razorpay_order_id', ''));
$rzpPaymentId = mm_clean(api_input('razorpay_payment_id', ''));
$rzpSignature = mm_clean(api_input('razorpay_signature', ''));
$refCode = mm_clean(api_input('ref', ''));
$donationRef = mm_clean(api_input('mref', ''));

if ($fullName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $phone === '' || $dob === '' || $designationId <= 0) {
    api_error('Please fill all required fields.', 422);
}

$designationStmt = $pdo->prepare("SELECT id, fee_amount FROM member_designations WHERE id = ? AND is_active = 1");
$designationStmt->execute([$designationId]);
$designation = $designationStmt->fetch(PDO::FETCH_ASSOC);
if (!$designation) {
    api_error('Selected designation is invalid.', 422);
}

$expectedFee = (float)$designation['fee_amount'];
if ((int)round($expectedFee * 100) !== (int)round($membershipFeeInput * 100)) {
    api_error('Membership fee mismatch. Please re-select designation.', 422);
}

if (!in_array($paymentMode, ['manual', 'razorpay'], true)) {
    api_error('Invalid payment mode.', 422);
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
        api_error('Transaction ID is required for manual payment.', 422);
    }
} else {
    if ($rzpOrderId === '' || $rzpPaymentId === '' || $rzpSignature === '') {
        api_error('Razorpay payment details are missing.', 422);
    }

    $keySecret = trim((string)($settings['razorpay_key_secret'] ?? ''));
    if ($keySecret === '') {
        api_error('Razorpay is not configured by admin.', 503);
    }

    $generated = hash_hmac('sha256', $rzpOrderId . '|' . $rzpPaymentId, $keySecret);
    if (!hash_equals($generated, $rzpSignature)) {
        api_error('Payment signature verification failed.', 422);
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
    api_error('This email is already registered as a member.', 422);
}

$paymentProof = null;
if ($paymentMode === 'manual' && !empty($_FILES['payment_proof']) && (int)($_FILES['payment_proof']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
    if ((int)$_FILES['payment_proof']['size'] > 2 * 1024 * 1024) {
        api_error('Payment proof size must be under 2MB.', 422);
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array((string)$_FILES['payment_proof']['type'], $allowedTypes, true)) {
        api_error('Only JPG, PNG, or WEBP files are allowed.', 422);
    }

    $targetDir = dirname(__DIR__) . '/uploads/members/';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $fileName = time() . '_' . uniqid('', true) . '_' . basename((string)$_FILES['payment_proof']['name']);
    if (!move_uploaded_file((string)$_FILES['payment_proof']['tmp_name'], $targetDir . $fileName)) {
        api_error('Failed to upload payment proof.', 500);
    }
    $paymentProof = 'uploads/members/' . $fileName;
}

$memberPhoto = null;
if (!empty($_FILES['member_photo']) && (int)($_FILES['member_photo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
    if ((int)$_FILES['member_photo']['size'] > 2 * 1024 * 1024) {
        api_error('Member photo size must be under 2MB.', 422);
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? finfo_file($finfo, (string)$_FILES['member_photo']['tmp_name']) : ($_FILES['member_photo']['type'] ?? '');
    if ($finfo) {
        finfo_close($finfo);
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array((string)$mime, $allowedTypes, true)) {
        api_error('Member photo must be JPG, PNG, or WEBP.', 422);
    }

    $targetDir = dirname(__DIR__) . '/uploads/members/';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $fileName = time() . '_' . uniqid('', true) . '_' . basename((string)$_FILES['member_photo']['name']);
    if (!move_uploaded_file((string)$_FILES['member_photo']['tmp_name'], $targetDir . $fileName)) {
        api_error('Failed to upload member photo.', 500);
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
        (member_no, full_name, email, phone, blood_group, dob, gender, address, photo, designation_id, membership_fee, payment_gateway, payment_status, payment_txn_id, razorpay_order_id, razorpay_payment_id, razorpay_signature, payment_proof, status, referral_code, donation_ref_code, referred_by_member_id, referred_by_source, member_receipt_no, member_since, valid_until)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $memberNo,
        $fullName,
        $email,
        $phone,
        $bloodGroup ?: null,
        $dob,
        $gender ?: null,
        $address ?: null,
        $memberPhoto,
        $designationId,
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
} catch (Throwable $e) {
    api_error('Database error. Please try again.', 500);
}

if ($paymentMode === 'razorpay') {
    $siteName = $settings['site_name'] ?? 'NGO';
    $receiptLink = (rtrim((string)($settings['ngo_website'] ?? ''), '/') !== '')
        ? rtrim((string)$settings['ngo_website'], '/') . '/download-member-receipt.php?member_no=' . urlencode((string)$memberNo) . '&email=' . urlencode($email)
        : '';

    try {
        $body = '<p>Dear ' . htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') . ',</p>'
            . '<p>Your membership payment was successful.</p>'
            . '<p><strong>Member No:</strong> ' . htmlspecialchars((string)$memberNo, ENT_QUOTES, 'UTF-8') . '<br>'
            . '<strong>Receipt No:</strong> ' . htmlspecialchars((string)$memberReceiptNo, ENT_QUOTES, 'UTF-8') . '</p>';
        if ($receiptLink !== '') {
            $body .= '<p><a href="' . htmlspecialchars($receiptLink, ENT_QUOTES, 'UTF-8') . '">Download Membership Receipt (PDF)</a></p>';
        }
        $body .= '<p>Regards,<br>' . htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') . '</p>';
        mm_send_email($settings, $email, $fullName, 'Membership Payment Successful - ' . $siteName, $body);
    } catch (Throwable $e) {
    }
}

api_ok([
    'member_id' => $memberId,
    'member_no' => $memberNo,
    'member_receipt_no' => $memberReceiptNo,
    'payment_status' => $paymentStatus,
    'member_status' => $memberStatus,
], $paymentMode === 'razorpay' ? 'Payment successful. Membership activated.' : 'Membership application submitted successfully. Admin verification pending.');
