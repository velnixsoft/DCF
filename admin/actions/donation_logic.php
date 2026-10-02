<?php
// ============================================================
// admin/actions/donation_logic.php  (Razorpay-updated — FIXED)
// Preserves ALL original logic exactly. Razorpay additions are
// clearly marked so nothing existing is broken.
// ============================================================

require '../../config/db.php';
require '../../includes/functions.php';

// ── Auth: matches your ORIGINAL check ───────────────────────
if (!isset($_SESSION['logged_in'])) { header('Location: ../index.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── CSRF check: same as original ────────────────────────
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        setFlash('error', 'Security Token Invalid');
        header('Location: ../donations.php'); exit;
    }

    $action = $_POST['action'] ?? null;

    // ══════════════════════════════════════════════════════════
    // 1. MANUAL ADD DONATION  (action = 'create')
    //    Identical to original field names: name, email, mobile,
    //    address, pan, transaction_id — nothing renamed.
    // ══════════════════════════════════════════════════════════
    if ($action === 'create') {
        $project_id  = !empty($_POST['project_id']) ? $_POST['project_id'] : NULL;
        $name        = cleanInput($_POST['name']);
        if (!preg_match("/^[a-zA-Z\s'.\-]+$/", $name)) {
            setFlash('error', 'Donor Name must contain only letters, spaces, apostrophes, periods, or hyphens.');
            header('Location: ../donations.php');
            exit;
        }
        $email       = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $mobile      = cleanInput($_POST['mobile']         ?? '');
        if (empty($mobile)) {
            setFlash('error', 'Mobile number is required.');
            header('Location: ../donations.php');
            exit;
        }
        $mobileClean = preg_replace('/[^0-9]/', '', $mobile);
        if (strlen($mobileClean) !== 10 || !preg_match("/^[6-9][0-9]{9}$/", $mobileClean)) {
            setFlash('error', 'Mobile number must start with 6, 7, 8, or 9 and be exactly 10 digits.');
            header('Location: ../donations.php');
            exit;
        }
        $mobile = $mobileClean;

        $address     = cleanInput($_POST['address']        ?? '');
        $amount      = cleanInput($_POST['amount']);
        $status      = $_POST['status'];
        $txn_id      = cleanInput($_POST['transaction_id'] ?? '');
        $is_eligible = isset($_POST['is_80g_eligible']) && $_POST['is_80g_eligible'] == '1';
        $pan         = cleanInput($_POST['pan'] ?? null);

        // Validations (same as original)
        if (empty($email))               { setFlash('error', 'Email is required.');        header('Location: ../donations.php'); exit; }
        if ($is_eligible && empty($pan)) { setFlash('error', 'PAN is required for 80G.'); header('Location: ../donations.php'); exit; }
        if (!empty($pan)) {
            $pan = strtoupper(trim($pan));
            if (!preg_match("/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/", $pan)) {
                setFlash('error', 'PAN is not in the correct format (e.g. ABCDE1234F).');
                header('Location: ../donations.php');
                exit;
            }
        }

        // Screenshot upload
        $screenshotPath = NULL;
        if (!empty($_FILES['payment_screenshot']['name'])) {
            $screenshotPath = uploadScreenshot($_FILES['payment_screenshot']);
            if (!$screenshotPath) { setFlash('error', 'Screenshot upload failed.'); header('Location: ../donations.php'); exit; }
        }

        // Resolve referral code
        $referral_code = cleanInput($_POST['referral_code'] ?? '');
        $sa_student_id = null;
        $referral_warning = false;
        if (!empty($referral_code)) {
            $stmt = $pdo->prepare("SELECT id FROM sa_students WHERE referral_code = ? AND status = 'Active' LIMIT 1");
            $stmt->execute([$referral_code]);
            $sa_student_id = $stmt->fetchColumn() ?: null;
            if (!$sa_student_id) {
                $referral_warning = true;
            }
        }

        $pdo->beginTransaction();
        try {
            $receipt_no = NULL;
            if ($status === 'Success') {
                $receipt_no = generateNextReceiptNumber($pdo);
                if ($project_id) {
                    $pdo->prepare("UPDATE projects SET raised_amount = raised_amount + ? WHERE id = ?")
                        ->execute([$amount, $project_id]);
                }
            }

            $columns = [
                'project_id',
                'donor_name',
                'donor_email',
                'donor_mobile',
                'amount',
                'donor_pan',
                'donor_address',
                'payment_status',
                'transaction_id',
                'receipt_no',
                'is_80g_eligible',
                'payment_screenshot',
            ];
            $values = [
                $project_id,
                $name,
                $email,
                $mobile,
                $amount,
                $pan,
                $address,
                $status,
                $txn_id,
                $receipt_no,
                $is_eligible,
                $screenshotPath,
            ];

            if (!empty($referral_code)) {
                $columns[] = 'referral_code';
                $values[] = $referral_code;
            }
            if ($sa_student_id) {
                $columns[] = 'sa_student_id';
                $values[] = $sa_student_id;
            }

            if (dbColumnExists($pdo, 'donations', 'payment_gateway')) {
                $columns[] = 'payment_gateway';
                $values[] = 'Manual';
            }
            if (dbColumnExists($pdo, 'donations', 'payment_mode')) {
                $columns[] = 'payment_mode';
                $values[] = 'Manual';
            }

            $sql = "INSERT INTO donations (" . implode(',', $columns) . ") VALUES (" . implode(',', array_fill(0, count($columns), '?')) . ")";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);
            $donationId = $pdo->lastInsertId();

            $pdo->commit();
            
            if ($referral_warning) {
                setFlash('warning', 'Donation added manually, but referral code did not match any active student ambassador.');
            } else {
                setFlash('success', 'Donation added manually.');
            }

            // Trigger point engine AFTER commit
            if ($status === 'Success' && $sa_student_id && $donationId) {
                require_once __DIR__ . '/../../includes/student/donation_achievements.php';
                $achEngine = new DonationAchievementEngine($pdo);
                $achEngine->processStudentRewards($sa_student_id, $donationId);
            }

        } catch (PDOException $e) {
            $pdo->rollBack();
            setFlash('error', 'Error: ' . $e->getMessage());
        }
    }

    // ══════════════════════════════════════════════════════════
    // 2. UPDATE STATUS — VERIFY / REJECT  (action = 'update_status')
    //    Original reversal logic fully preserved:
    //    Pending → Success : raised_amount += amount
    //    Success → other   : raised_amount -= amount
    // ══════════════════════════════════════════════════════════
    if ($action === 'update_status') {
        $id     = $_POST['donation_id'];
        $status = $_POST['status'];

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT amount, project_id, payment_status, receipt_no FROM donations WHERE id = ?");
            $stmt->execute([$id]);
            $donation = $stmt->fetch();

            $oldStatus = $donation['payment_status'];
            $amount    = $donation['amount'];
            $projectId = $donation['project_id'];

            // raised_amount adjustments: exactly as original
            if ($oldStatus === 'Pending' && $status === 'Success' && $projectId) {
                $pdo->prepare("UPDATE projects SET raised_amount = raised_amount + ? WHERE id = ?")
                    ->execute([$amount, $projectId]);
            } elseif ($oldStatus === 'Success' && $status !== 'Success' && $projectId) {
                $pdo->prepare("UPDATE projects SET raised_amount = raised_amount - ? WHERE id = ?")
                    ->execute([$amount, $projectId]);
            }

            // Only generate receipt if not already set — same as original
            $receipt_sql = "";
            if ($status === 'Success' && empty($donation['receipt_no'])) {
                $new_receipt = generateNextReceiptNumber($pdo);
                $receipt_sql = ", receipt_no = '$new_receipt'";
            }

            $sql = "UPDATE donations SET payment_status = ? $receipt_sql WHERE id = ?";
            $pdo->prepare($sql)->execute([$status, $id]);

            $pdo->commit();
            setFlash('success', "Status updated to $status");

            if ($status === 'Success' && $oldStatus !== 'Success') {
                $stmt = $pdo->prepare("SELECT sa_student_id, achievement_processed FROM donations WHERE id = ?");
                $stmt->execute([$id]);
                $donRow = $stmt->fetch();
                if ($donRow && $donRow['sa_student_id'] && !$donRow['achievement_processed']) {
                    require_once __DIR__ . '/../../includes/student/donation_achievements.php';
                    $achEngine = new DonationAchievementEngine($pdo);
                    $achEngine->processStudentRewards($donRow['sa_student_id'], $id);
                }
            }

        } catch (PDOException $e) {
            $pdo->rollBack();
            setFlash('error', 'Database Error: ' . $e->getMessage());
        }
    }

    // ══════════════════════════════════════════════════════════
    // [RAZORPAY NEW] 3. DELETE DONATION  (action = 'delete')
    //    New action — does NOT affect 'create' or 'update_status'.
    //    Safely reverses raised_amount if donation was Success.
    // ══════════════════════════════════════════════════════════
    if ($action === 'delete') {
        $id = filter_input(INPUT_POST, 'donation_id', FILTER_VALIDATE_INT);
        if (!$id) { setFlash('error', 'Invalid ID.'); header('Location: ../donations.php'); exit; }

        try {
            $stmt = $pdo->prepare("SELECT amount, project_id, payment_status FROM donations WHERE id = ?");
            $stmt->execute([$id]);
            $donation = $stmt->fetch();

            if ($donation && $donation['payment_status'] === 'Success' && $donation['project_id']) {
                $pdo->prepare("UPDATE projects SET raised_amount = GREATEST(0, raised_amount - ?) WHERE id = ?")
                    ->execute([$donation['amount'], $donation['project_id']]);
            }

            $pdo->prepare("DELETE FROM donations WHERE id = ?")->execute([$id]);
            setFlash('success', 'Donation deleted.');

        } catch (PDOException $e) {
            setFlash('error', 'Could not delete donation.');
        }
    }

    header('Location: ../donations.php');
    exit;
}


// ============================================================
// Helper: upload payment screenshot
// ============================================================
function uploadScreenshot(array $file): ?string {
    $allowed   = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize   = 5 * 1024 * 1024;
    $uploadDir = __DIR__ . '/../../uploads/donations/';

    if ($file['error'] !== UPLOAD_ERR_OK)                          return null;
    if ($file['size'] > $maxSize)                                  return null;
    if (!in_array(mime_content_type($file['tmp_name']), $allowed)) return null;

    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'manual_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest     = $uploadDir . $filename;

    return move_uploaded_file($file['tmp_name'], $dest)
        ? 'uploads/donations/' . $filename
        : null;
}
