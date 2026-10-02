<?php
require '../config/db.php';
require '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $paymentMode = strtolower((string)cleanInput($_POST['payment_mode'] ?? 'manual'));
    if (!in_array($paymentMode, ['manual', 'razorpay'], true)) {
        $paymentMode = 'manual';
    }

    $name = cleanInput($_POST['name'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $mobile = cleanInput($_POST['mobile'] ?? '');
    $amount = filter_var($_POST['amount'] ?? '', FILTER_VALIDATE_FLOAT);
    $txn_id = cleanInput($_POST['transaction_id'] ?? '');
    $rzpOrderId = cleanInput($_POST['razorpay_order_id'] ?? '');
    $rzpPaymentId = cleanInput($_POST['razorpay_payment_id'] ?? '');
    $rzpSignature = cleanInput($_POST['razorpay_signature'] ?? '');
    $referral_code = cleanInput($_POST['referral_code'] ?? '');
    
    $sa_student_id = null;
    if (!empty($referral_code)) {
        $stmt = $pdo->prepare("SELECT id FROM sa_students WHERE referral_code = ? AND status = 'Active' LIMIT 1");
        $stmt->execute([$referral_code]);
        $sa_student_id = $stmt->fetchColumn() ?: null;
    }

    $project_id = !empty($_POST['project_id']) ? (int)$_POST['project_id'] : NULL;

    // Server-side validation of common details
    if (empty($name)) {
        setFlash('error', 'Name is required.');
        header('Location: ../donate.php');
        exit;
    }
    if (!$email) {
        setFlash('error', 'A valid email is required.');
        header('Location: ../donate.php');
        exit;
    }
    if (empty($mobile) || !preg_match('/^[0-9]{10}$/', $mobile)) {
        setFlash('error', 'A valid 10-digit mobile number is required.');
        header('Location: ../donate.php');
        exit;
    }
    if (!$amount || $amount < 1) {
        setFlash('error', 'Donation amount must be at least ₹1.');
        header('Location: ../donate.php');
        exit;
    }

    $wants_80g = isset($_POST['wants_80g']) && $_POST['wants_80g'] == '1';
    $pan = null;
    $is_eligible_for_80g = false;

    if ($wants_80g) {
        $pan = isset($_POST['pan']) ? cleanInput($_POST['pan']) : null;
        if (empty($pan)) {
            setFlash('error', 'PAN number is required for an 80G receipt.');
            header('Location: ../donate.php');
            exit;
        }
        if (!preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/', strtoupper($pan))) {
            setFlash('error', 'A valid PAN format (e.g. ABCDE1234F) is required for 80G receipt.');
            header('Location: ../donate.php');
            exit;
        }
        $is_eligible_for_80g = true;
    }

    // Razorpay verification (if selected)
    $paymentStatus = 'Pending';
    $paymentGateway = $paymentMode === 'razorpay' ? 'Razorpay' : 'Manual';

    if ($paymentMode === 'manual') {
        $txn_id = trim((string)$txn_id);
        if ($txn_id === '') {
            setFlash('error', 'Transaction ID is required.');
            header('Location: ../donate.php');
            exit;
        }
        if (!preg_match('/^[a-zA-Z0-9]{8,50}$/', $txn_id)) {
            setFlash('error', 'Transaction ID must be between 8 and 50 alphanumeric characters.');
            header('Location: ../donate.php');
            exit;
        }
        $simpleTxn = strtolower($txn_id);
        if (preg_match('/^(.)\1+$/', $simpleTxn) || 
            in_array($simpleTxn, ['12345678', '123456789', '1234567890', 'abcdefgh', 'transaction', 'txnid123', 'test1234', 'testing123'])) {
            setFlash('error', 'Please enter a valid, non-placeholder transaction ID.');
            header('Location: ../donate.php');
            exit;
        }
        
        // Duplicate check
        $dupStmt = $pdo->prepare("SELECT COUNT(*) FROM donations WHERE transaction_id = ? AND payment_status != 'Failed'");
        $dupStmt->execute([$txn_id]);
        if ($dupStmt->fetchColumn() > 0) {
            setFlash('error', 'This transaction ID has already been submitted.');
            header('Location: ../donate.php');
            exit;
        }

        // Verify screenshot file upload exists
        if (!isset($_FILES['screenshot']) || $_FILES['screenshot']['error'] !== 0) {
            setFlash('error', 'Payment screenshot image is required for manual verification.');
            header('Location: ../donate.php');
            exit;
        }
        $paymentStatus = 'Pending';
    } else {
        if ($rzpOrderId === '' || $rzpPaymentId === '' || $rzpSignature === '') {
            setFlash('error', 'Razorpay payment details are missing.');
            header('Location: ../donate.php');
            exit;
        }

        $settings = [];
        try {
            $stmt = $pdo->query("SELECT * FROM settings");
            while ($row = $stmt->fetch()) $settings[$row['setting_key']] = $row['setting_value'];
        } catch (Throwable $e) {
            $settings = [];
        }

        $keySecret = trim((string)($settings['razorpay_donation_key_secret'] ?? ''));
        if ($keySecret === '') {
            $keySecret = trim((string)($settings['razorpay_key_secret'] ?? ''));
        }
        if ($keySecret === '') {
            setFlash('error', 'Razorpay is not configured by admin.');
            header('Location: ../donate.php');
            exit;
        }

        $generated = hash_hmac('sha256', $rzpOrderId . '|' . $rzpPaymentId, $keySecret);
        if (!hash_equals($generated, $rzpSignature)) {
            setFlash('error', 'Payment signature verification failed.');
            header('Location: ../donate.php');
            exit;
        }

        $txn_id = $rzpPaymentId;
        $paymentStatus = 'Success';
    }

    $screenshotPath = null;
    if ($paymentMode === 'manual' && isset($_FILES['screenshot']) && $_FILES['screenshot']['error'] == 0) {
        if ($_FILES['screenshot']['size'] > 2 * 1024 * 1024) {
            setFlash('error', 'Screenshot size must be under 2MB.');
            header('Location: ../donate.php');
            exit;
        }
        
        // finfo file type check
        $mime = null;
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES['screenshot']['tmp_name']);
            finfo_close($finfo);
        } else {
            $mime = $_FILES['screenshot']['type'];
        }
        
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
        if (!in_array(strtolower($mime), $allowedMimes)) {
            setFlash('error', 'Only JPG, PNG, WEBP files are allowed.');
            header('Location: ../donate.php');
            exit;
        }

        // Verify the image with getimagesize
        $imageInfo = @getimagesize($_FILES['screenshot']['tmp_name']);
        if ($imageInfo === false) {
            setFlash('error', 'Invalid screenshot image file.');
            header('Location: ../donate.php');
            exit;
        }

        $targetDir = "../uploads/donations/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $fileName = time() . '_' . uniqid() . '_' . basename($_FILES['screenshot']['name']);
        if (move_uploaded_file($_FILES['screenshot']['tmp_name'], $targetDir . $fileName)) {
            $screenshotPath = 'uploads/donations/' . $fileName;
        }
    }

    try {
        $pdo->beginTransaction();

        $receiptNo = null;
        if ($paymentStatus === 'Success') {
            $receiptNo = generateNextDonationReceiptNumber($pdo);
            if ($project_id) {
                $pdo->prepare("UPDATE projects SET raised_amount = raised_amount + ? WHERE id = ?")->execute([$amount, $project_id]);
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
            $project_id,
            $name,
            $email,
            $mobile,
            $amount,
            $txn_id,
            $screenshotPath,
            $pan,
            $is_eligible_for_80g ? 1 : 0,
            $referral_code ?: null,
            $paymentStatus,
            $receiptNo,
        ];

        if ($sa_student_id) {
            $columns[] = 'sa_student_id';
            $values[] = $sa_student_id;
        }

        // Optional v3 fields
        try {
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
        } catch (Throwable $e) {
        }

        $placeholders = implode(',', array_fill(0, count($columns), '?'));
        $sql = "INSERT INTO donations (" . implode(',', $columns) . ") VALUES (" . $placeholders . ")";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        $donationId = $pdo->lastInsertId();

        $pdo->commit();

        // Trigger points engine AFTER commit if Success immediately (Razorpay success checkout)
        if ($paymentStatus === 'Success' && $sa_student_id && $donationId) {
            require_once __DIR__ . '/../includes/student/donation_achievements.php';
            $achEngine = new DonationAchievementEngine($pdo);
            $achEngine->processStudentRewards($sa_student_id, $donationId);
        }

        header('Location: ../thankyou.php');
        exit;
    } catch (PDOException $e) {
        try { $pdo->rollBack(); } catch (Throwable $e2) {}

        setFlash('error', 'Database error. Please try again.');
        header('Location: ../donate.php');
        exit;
    }
} else {
    header('Location: ../donate.php');
    exit;
}
