<?php
// ============================================================
// process/donor_recurring_action.php
// Donor self-service controller for managing recurring subscriptions
// Handles: pause, resume, cancel, get_data
// ============================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/razorpay_subscription_helper.php';

header('Content-Type: application/json');

// 1. Identify logged-in donor email
$donorEmail = '';

if (!empty($_SESSION['donor_verified']) && !empty($_SESSION['donor_email'])) {
    $donorEmail = trim((string)$_SESSION['donor_email']);
} elseif (!empty($_SESSION['member_logged_in']) && !empty($_SESSION['member_email'])) {
    $donorEmail = trim((string)$_SESSION['member_email']);
}

if (empty($donorEmail)) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized. Please login with OTP or Member account to manage your AutoPay.'
    ]);
    exit;
}

$action = cleanInput($_REQUEST['action'] ?? '');

// ── ACTION: FETCH RECURRING DATA ──────────────────────────────
if ($action === 'get_data') {
    try {
        // 1. Recurring mandates
        $recStmt = $pdo->prepare("
            SELECT r.*, p.title AS project_title,
                   (SELECT COUNT(*) FROM recurring_donation_transactions WHERE recurring_donation_id = r.id AND status = 'success') AS total_cycles_paid,
                   (SELECT COALESCE(SUM(amount), 0) FROM recurring_donation_transactions WHERE recurring_donation_id = r.id AND status = 'success') AS total_paid_amount
            FROM recurring_donations r
            LEFT JOIN projects p ON r.project_id = p.id
            WHERE r.donor_email = ?
            ORDER BY r.created_at DESC
        ");
        $recStmt->execute([$donorEmail]);
        $recurring = $recStmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. All cycle transactions
        $txStmt = $pdo->prepare("
            SELECT t.*, r.frequency, r.razorpay_subscription_id, p.title AS project_title, d.receipt_no AS donation_receipt_no
            FROM recurring_donation_transactions t
            JOIN recurring_donations r ON t.recurring_donation_id = r.id
            LEFT JOIN donations d ON t.donation_id = d.id
            LEFT JOIN projects p ON r.project_id = p.id
            WHERE r.donor_email = ?
            ORDER BY t.charge_date DESC, t.id DESC
        ");
        $txStmt->execute([$donorEmail]);
        $transactions = $txStmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. One-time donations
        $donStmt = $pdo->prepare("
            SELECT d.*, p.title AS project_title 
            FROM donations d 
            LEFT JOIN projects p ON d.project_id = p.id 
            WHERE d.donor_email = ? 
            ORDER BY d.created_at DESC
        ");
        $donStmt->execute([$donorEmail]);
        $donations = $donStmt->fetchAll(PDO::FETCH_ASSOC);

        // 4. Item donations
        $itemStmt = $pdo->prepare("
            SELECT i.*, c.category_name, c.category_icon, p.title AS project_title
            FROM item_donations i
            LEFT JOIN item_donation_categories c ON i.category_id = c.id
            LEFT JOIN projects p ON i.project_id = p.id
            WHERE i.donor_email = ?
            ORDER BY i.created_at DESC
        ");
        $itemStmt->execute([$donorEmail]);
        $itemDonationsRaw = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

        $itemDonations = [];
        foreach ($itemDonationsRaw as $itm) {
            $rcp = $itm['receipt_no'] ?: ('RCP-ITM-' . $itm['id']);
            $token = generateItemDonationReceiptToken($itm['id'], $itm['donation_code'], $rcp, $itm['donor_email'], $itm['created_at']);
            $itm['receipt_download_url'] = 'download-item-receipt.php?id=' . (int)$itm['id'] . '&token=' . urlencode($token);
            $itm['verify_url'] = 'verify-item.php?code=' . urlencode($itm['donation_code']) . '&token=' . urlencode($token);
            $itemDonations[] = $itm;
        }

        echo json_encode([
            'success' => true,
            'email' => $donorEmail,
            'recurring' => $recurring,
            'transactions' => $transactions,
            'donations' => $donations,
            'item_donations' => $itemDonations
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// ── MUTATION ACTIONS (Pause / Resume / Cancel) ────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$recurringId = filter_input(INPUT_POST, 'recurring_id', FILTER_VALIDATE_INT);
$reason = cleanInput($_POST['reason'] ?? '');

if (!$recurringId) {
    echo json_encode(['success' => false, 'message' => 'Invalid recurring donation ID.']);
    exit;
}

try {
    // Verify ownership: subscription MUST belong to logged-in donor
    $stmt = $pdo->prepare("SELECT * FROM recurring_donations WHERE id = ? AND donor_email = ?");
    $stmt->execute([$recurringId, $donorEmail]);
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$subscription) {
        echo json_encode(['success' => false, 'message' => 'Subscription record not found or access denied.']);
        exit;
    }

    $subId = (string)($subscription['razorpay_subscription_id'] ?? '');

    switch ($action) {
        case 'pause':
            if ($subscription['status'] !== 'active') {
                echo json_encode(['success' => false, 'message' => 'Only active recurring donations can be paused.']);
                exit;
            }

            // Call Razorpay API
            if (!empty($subId)) {
                pauseRazorpaySubscription($pdo, $subId);
            }

            $pauseNote = $reason ?: 'Paused by donor via self-service portal';
            $upd = $pdo->prepare("UPDATE recurring_donations SET status = 'paused', pause_reason = ? WHERE id = ?");
            $upd->execute([$pauseNote, $recurringId]);

            echo json_encode([
                'success' => true,
                'message' => 'Your recurring donation has been paused. Auto-debit will not be charged until you resume.',
                'new_status' => 'paused'
            ]);
            break;

        case 'resume':
            if ($subscription['status'] !== 'paused') {
                echo json_encode(['success' => false, 'message' => 'Only paused recurring donations can be resumed.']);
                exit;
            }

            // Call Razorpay API
            if (!empty($subId)) {
                resumeRazorpaySubscription($pdo, $subId);
            }

            $upd = $pdo->prepare("UPDATE recurring_donations SET status = 'active', pause_reason = NULL WHERE id = ?");
            $upd->execute([$recurringId]);

            echo json_encode([
                'success' => true,
                'message' => 'Your recurring donation has been resumed to Active.',
                'new_status' => 'active'
            ]);
            break;

        case 'cancel':
        case 'stop':
            if ($subscription['status'] === 'stopped') {
                echo json_encode(['success' => false, 'message' => 'This recurring donation is already stopped.']);
                exit;
            }

            // Call Razorpay API
            if (!empty($subId)) {
                cancelRazorpaySubscription($pdo, $subId, false);
            }

            $cancelNote = $reason ?: 'Cancelled by donor via self-service portal';
            $upd = $pdo->prepare("UPDATE recurring_donations SET status = 'stopped', cancel_reason = ?, end_date = CURRENT_DATE WHERE id = ?");
            $upd->execute([$cancelNote, $recurringId]);

            echo json_encode([
                'success' => true,
                'message' => 'Your recurring donation mandate has been successfully cancelled.',
                'new_status' => 'stopped'
            ]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action: ' . $action]);
            break;
    }

} catch (PDOException $e) {
    error_log("donor_recurring_action error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred. Please try again.']);
}
