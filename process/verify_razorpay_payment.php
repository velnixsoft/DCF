<?php
// ============================================================
// process/verify_razorpay_payment.php
// Step 2: Called via AJAX after Razorpay checkout success
// Verifies signature, marks donation Success, issues receipt
// ============================================================

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/razorpay.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/member_module.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// ── Collect POST data from Razorpay checkout handler ────────
$razorpay_payment_id = cleanInput($_POST['razorpay_payment_id'] ?? '');
$razorpay_order_id   = cleanInput($_POST['razorpay_order_id']   ?? '');
$razorpay_signature  = cleanInput($_POST['razorpay_signature']  ?? '');
$donation_id         = filter_input(INPUT_POST, 'donation_id', FILTER_VALIDATE_INT);
if (!$donation_id && !empty($_SESSION['pending_donation_id'])) {
    $donation_id = (int) $_SESSION['pending_donation_id'];
}

if (!$razorpay_payment_id || !$razorpay_order_id || !$razorpay_signature || !$donation_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment data received.']);
    exit;
}

// ── Verify Razorpay signature ────────────────────────────────
$razorpay = getRazorpayCredentials($pdo, 'donation');
$generated_signature = hash_hmac(
    'sha256',
    $razorpay_order_id . '|' . $razorpay_payment_id,
    $razorpay['key_secret']
);

if (!hash_equals($generated_signature, $razorpay_signature)) {
    error_log("Razorpay signature mismatch for order: $razorpay_order_id");
    echo json_encode(['success' => false, 'message' => 'Payment verification failed. Please contact support.']);
    exit;
}

// ── Fetch the pending donation ───────────────────────────────
try {
    $stmt = $pdo->prepare("SELECT * FROM donations WHERE id = ? AND razorpay_order_id = ? AND payment_status = 'Pending'");
    $stmt->execute([$donation_id, $razorpay_order_id]);
    $donation = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('DB fetch failed (verify): ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error.']);
    exit;
}

// Idempotent: webhook may have already marked Success (avoid false failure + double updates)
if (!$donation) {
    try {
        $done = $pdo->prepare("
            SELECT id, receipt_no, donor_email FROM donations
            WHERE id = ? AND razorpay_order_id = ?
              AND payment_status = 'Success'
              AND razorpay_payment_id = ?
        ");
        $done->execute([$donation_id, $razorpay_order_id, $razorpay_payment_id]);
        $already = $done->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('DB fetch failed (verify idempotent): ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error.']);
        exit;
    }
    if ($already) {
        $_SESSION['last_donation_id'] = (int) $already['id'];
        $_SESSION['last_receipt_no']  = $already['receipt_no'] ?? '';
        echo json_encode([
            'success'    => true,
            'message'    => 'Payment already confirmed.',
            'receipt_no' => $already['receipt_no'],
            'email_sent' => false,
            'redirect'   => rtrim(appBaseUrl(), '/') . '/thankyou.php?donation_id=' . (int) $already['id'],
        ]);
        exit;
    }
    echo json_encode(['success' => false, 'message' => 'Donation record not found or already processed.']);
    exit;
}

// ── Generate receipt number (same logic as existing system) ──
$receipt_no = generateNextReceiptNumber($pdo);

// ── Update donation to Success ───────────────────────────────
try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        UPDATE donations SET
            payment_status       = 'Success',
            razorpay_payment_id  = ?,
            razorpay_signature   = ?,
            receipt_no           = ?,
            transaction_id       = ?
        WHERE id = ? AND payment_status = 'Pending'
    ");
    $stmt->execute([
        $razorpay_payment_id,
        $razorpay_signature,
        $receipt_no,
        $razorpay_payment_id,
        $donation_id,
    ]);

    if ($stmt->rowCount() === 0) {
        $pdo->rollBack();
        try {
            $done = $pdo->prepare("
                SELECT id, receipt_no FROM donations
                WHERE id = ? AND razorpay_order_id = ?
                  AND payment_status = 'Success'
                  AND razorpay_payment_id = ?
            ");
            $done->execute([$donation_id, $razorpay_order_id, $razorpay_payment_id]);
            $already = $done->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $already = null;
        }
        if ($already) {
            $_SESSION['last_donation_id'] = (int) $already['id'];
            $_SESSION['last_receipt_no']  = $already['receipt_no'] ?? '';
            echo json_encode([
                'success'    => true,
                'message'    => 'Payment already confirmed.',
                'receipt_no' => $already['receipt_no'],
                'email_sent' => false,
                'redirect'   => rtrim(appBaseUrl(), '/') . '/thankyou.php?donation_id=' . (int) $already['id'],
            ]);
            exit;
        }
        echo json_encode(['success' => false, 'message' => 'Could not finalize donation. Please contact support.']);
        exit;
    }

    if (!empty($donation['project_id'])) {
        $stmt2 = $pdo->prepare("UPDATE projects SET raised_amount = raised_amount + ? WHERE id = ?");
        $stmt2->execute([$donation['amount'], $donation['project_id']]);
    }

    $pdo->commit();

    if (!empty($donation['sa_student_id'])) {
        try {
            require_once __DIR__ . '/../includes/student/donation_achievements.php';
            $achEngine = new DonationAchievementEngine($pdo);
            $achEngine->processStudentRewards((int)$donation['sa_student_id'], $donation_id);
        } catch (\Throwable $ex) {
            error_log('Points engine exception: ' . $ex->getMessage());
        }
    }

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log('DB update failed (verify): ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Could not finalize donation. Please contact support.']);
    exit;
}

// ── Auto-send receipt email ──────────────────────────────────
$emailSent = false;
if (!empty($donation['donor_email'])) {
    $emailSent = sendReceiptEmail($pdo, $donation_id, $donation['donor_email'], $receipt_no);
}

// ── Store in session for thank-you page ─────────────────────
$_SESSION['last_donation_id'] = $donation_id;
$_SESSION['last_receipt_no']  = $receipt_no;

echo json_encode([
    'success'    => true,
    'message'    => 'Payment verified successfully!',
    'receipt_no' => $receipt_no,
    'email_sent' => $emailSent,
    'redirect'   => rtrim(appBaseUrl(), '/') . '/thankyou.php?donation_id=' . $donation_id,
]);

// ============================================================
// Helper: send receipt email (uses existing PHPMailer setup)
// ============================================================
function sendReceiptEmail(PDO $pdo, int $donation_id, string $email, string $receipt_no): bool
{
    try {
        $settings = [];
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    } catch (\Throwable $e) {
        error_log('Settings fetch failed: ' . $e->getMessage());
        return false;
    }

    $stmt = $pdo->prepare("SELECT id, receipt_no, donor_email, donor_name, amount, created_at FROM donations WHERE id = ?");
    $stmt->execute([$donation_id]);
    $donation = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$donation) {
        return false;
    }

    $receiptToken = generateDonationReceiptToken(
        $donation['id'],
        $receipt_no,
        $donation['donor_email'] ?? '',
        $donation['amount'] ?? 0,
        $donation['created_at'] ?? ''
    );
    $receipt_url = rtrim(appBaseUrl(), '/') . '/download-receipt.php?id=' . (int) $donation['id'] . '&token=' . urlencode($receiptToken);

    $emailBody = "
        <p>Dear " . htmlspecialchars($donation['donor_name'] ?? 'Donor') . ",</p>
        <p>Thank you for your generous donation of <strong>INR " . number_format((float)($donation['amount'] ?? 0), 2) . "</strong>.</p>
        <p><strong>Receipt No:</strong> " . htmlspecialchars($receipt_no) . "</p>
        <p>
            <a href='{$receipt_url}' style='background:#16a34a;color:#fff;padding:10px 20px;
            text-decoration:none;border-radius:5px;display:inline-block;margin-top:10px;'>
            Download Receipt
            </a>
        </p>
        <p>Your support makes a difference!</p>
    ";

    $sent = mm_send_email(
        $settings,
        $email,
        $donation['donor_name'] ?? 'Donor',
        'Donation Receipt - ' . $receipt_no,
        $emailBody
    );

    if (!$sent) {
        error_log('Webhook receipt email failed: ' . ($GLOBALS['mm_last_mail_error'] ?? 'unknown error'));
    }

    return $sent;
}
