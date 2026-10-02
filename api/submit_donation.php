<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/member_module.php';

api_require_method(['POST']);

$paymentMode = strtolower((string)cleanInput(api_input('payment_mode', 'manual')));
if (!in_array($paymentMode, ['manual', 'razorpay'], true)) {
    $paymentMode = 'manual';
}

$name = cleanInput(api_input('name', ''));
$email = filter_var((string)api_input('email', ''), FILTER_SANITIZE_EMAIL);
$mobile = cleanInput(api_input('mobile', ''));
$amount = (float)api_input('amount', 0);
$txnId = cleanInput(api_input('transaction_id', ''));
$rzpOrderId = cleanInput(api_input('razorpay_order_id', ''));
$rzpPaymentId = cleanInput(api_input('razorpay_payment_id', ''));
$rzpSignature = cleanInput(api_input('razorpay_signature', ''));
$referralCode = cleanInput(api_input('referral_code', ''));
$projectId = api_input('project_id', null);
$projectId = ($projectId !== null && (int)$projectId > 0) ? (int)$projectId : null;
$wants80g = (string)api_input('wants_80g', '0') === '1';
$pan = $wants80g ? strtoupper(cleanInput(api_input('pan', ''))) : null;

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $mobile === '' || $amount <= 0) {
    api_error('Please fill all required fields.', 422);
}

if ($wants80g && $pan === '') {
    api_error('PAN number is required for an 80G receipt.', 422);
}

$settings = mm_load_settings($pdo);
$paymentGateway = $paymentMode === 'razorpay' ? 'Razorpay' : 'Manual';
$paymentStatus = 'Pending';

if ($paymentMode === 'manual') {
    $txnId = trim((string)$txnId);
    if ($txnId === '') {
        api_error('Transaction ID is required.', 422);
    }
    if (!preg_match('/^[a-zA-Z0-9]{8,50}$/', $txnId)) {
        api_error('Transaction ID must be between 8 and 50 alphanumeric characters.', 422);
    }
    $simpleTxn = strtolower($txnId);
    if (preg_match('/^(.)\1+$/', $simpleTxn) || 
        in_array($simpleTxn, ['12345678', '123456789', '1234567890', 'abcdefgh', 'transaction', 'txnid123', 'test1234', 'testing123'])) {
        api_error('Please enter a valid, non-placeholder transaction ID.', 422);
    }
    $dupStmt = $pdo->prepare("SELECT COUNT(*) FROM donations WHERE transaction_id = ? AND payment_status != 'Failed'");
    $dupStmt->execute([$txnId]);
    if ($dupStmt->fetchColumn() > 0) {
        api_error('This transaction ID has already been submitted.', 422);
    }
} else {
    if ($rzpOrderId === '' || $rzpPaymentId === '' || $rzpSignature === '') {
        api_error('Razorpay payment details are missing.', 422);
    }

    $keySecret = trim((string)($settings['razorpay_donation_key_secret'] ?? $settings['razorpay_key_secret'] ?? ''));
    if ($keySecret === '') {
        api_error('Razorpay is not configured by admin.', 503);
    }

    $generated = hash_hmac('sha256', $rzpOrderId . '|' . $rzpPaymentId, $keySecret);
    if (!hash_equals($generated, $rzpSignature)) {
        api_error('Payment signature verification failed.', 422);
    }

    $txnId = $rzpPaymentId;
    $paymentStatus = 'Success';
}

$screenshotPath = null;
if ($paymentMode === 'manual' && !empty($_FILES['screenshot']) && (int)($_FILES['screenshot']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
    if ((int)$_FILES['screenshot']['size'] > 2 * 1024 * 1024) {
        api_error('Screenshot size must be under 2MB.', 422);
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array((string)$_FILES['screenshot']['type'], $allowedTypes, true)) {
        api_error('Only JPG, PNG, WEBP files are allowed.', 422);
    }

    $targetDir = dirname(__DIR__) . '/uploads/donations/';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $fileName = time() . '_' . uniqid('', true) . '_' . basename((string)$_FILES['screenshot']['name']);
    if (!move_uploaded_file((string)$_FILES['screenshot']['tmp_name'], $targetDir . $fileName)) {
        api_error('Failed to upload screenshot.', 500);
    }

    $screenshotPath = 'uploads/donations/' . $fileName;
}

try {
    $pdo->beginTransaction();

    $receiptNo = null;
    if ($paymentStatus === 'Success') {
        $receiptNo = generateNextDonationReceiptNumber($pdo);
        if ($projectId) {
            $pdo->prepare("UPDATE projects SET raised_amount = raised_amount + ? WHERE id = ?")->execute([$amount, $projectId]);
        }
    }

    $columns = [
        'project_id',
        'donor_name',
        'donor_email',
        'donor_mobile',
        'amount',
        'transaction_id',
        'payment_screenshot',
        'donor_pan',
        'is_80g_eligible',
        'referral_code',
        'payment_status',
        'receipt_no',
    ];
    $values = [
        $projectId,
        $name,
        $email,
        $mobile,
        $amount,
        $txnId,
        $screenshotPath,
        $pan,
        $wants80g ? 1 : 0,
        $referralCode !== '' ? $referralCode : null,
        $paymentStatus,
        $receiptNo,
    ];

    if (dbColumnExists($pdo, 'donations', 'payment_gateway')) {
        $columns[] = 'payment_gateway';
        $values[] = $paymentGateway;
    }
    if (dbColumnExists($pdo, 'donations', 'payment_mode')) {
        $columns[] = 'payment_mode';
        $values[] = $paymentGateway;
    }
    if ($paymentMode === 'razorpay') {
        if (dbColumnExists($pdo, 'donations', 'razorpay_order_id')) {
            $columns[] = 'razorpay_order_id';
            $values[] = $rzpOrderId;
        }
        if (dbColumnExists($pdo, 'donations', 'razorpay_payment_id')) {
            $columns[] = 'razorpay_payment_id';
            $values[] = $rzpPaymentId;
        }
        if (dbColumnExists($pdo, 'donations', 'razorpay_signature')) {
            $columns[] = 'razorpay_signature';
            $values[] = $rzpSignature;
        }
    }

    $stmt = $pdo->prepare('INSERT INTO donations (' . implode(',', $columns) . ') VALUES (' . implode(',', array_fill(0, count($columns), '?')) . ')');
    $stmt->execute($values);
    $donationId = (int)$pdo->lastInsertId();

    $pdo->commit();

    api_ok([
        'donation_id' => $donationId,
        'receipt_no' => $receiptNo,
        'payment_status' => $paymentStatus,
        'payment_gateway' => $paymentGateway,
    ], $paymentMode === 'razorpay' ? 'Payment successful. Donation recorded.' : 'Donation application submitted successfully.');
} catch (Throwable $e) {
    try {
        $pdo->rollBack();
    } catch (Throwable $ignored) {
    }

    api_error('Database error. Please try again.', 500);
}
