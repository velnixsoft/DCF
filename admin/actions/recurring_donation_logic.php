<?php
// ============================================================
// admin/actions/recurring_donation_logic.php
// Handles admin actions for Recurring Donations (Pause, Resume, Cancel, Export, Fetch Cycles)
// Follows existing donation_logic.php conventions.
// ============================================================

require '../../config/db.php';
require '../../includes/functions.php';
require '../../includes/razorpay_subscription_helper.php';

// Auth check
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

if (!canAccessModule($pdo, 'manager', 'page.recurring_donations') && !canAccessModule($pdo, 'manager', 'page.donations')) {
    setFlash('error', 'Access denied.');
    header('Location: ../dashboard.php');
    exit;
}

// ── GET Actions (Export Excel / Fetch Cycles JSON) ───────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    // A. FETCH TRANSACTION CYCLES (AJAX for Modal)
    if ($action === 'get_cycles') {
        header('Content-Type: application/json');
        $recurringId = filter_input(INPUT_GET, 'recurring_id', FILTER_VALIDATE_INT);
        if (!$recurringId) {
            echo json_encode(['success' => false, 'message' => 'Invalid recurring ID']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("
                SELECT t.*, d.receipt_no AS donation_receipt_no
                FROM recurring_donation_transactions t
                LEFT JOIN donations d ON t.donation_id = d.id
                WHERE t.recurring_donation_id = ?
                ORDER BY t.cycle_number DESC, t.id DESC
            ");
            $stmt->execute([$recurringId]);
            $cycles = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'cycles' => $cycles]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
        }
        exit;
    }

    // B. EXPORT EXCEL REPORT
    if ($action === 'export_excel') {
        $statusFilter = cleanInput($_GET['status'] ?? 'all');
        $query = "
            SELECT r.*, p.title AS project_title,
                   COUNT(t.id) AS total_charged_cycles,
                   COALESCE(SUM(CASE WHEN t.status = 'success' THEN t.amount ELSE 0 END), 0) AS total_collected
            FROM recurring_donations r
            LEFT JOIN projects p ON r.project_id = p.id
            LEFT JOIN recurring_donation_transactions t ON r.id = t.recurring_donation_id
        ";
        $params = [];
        if ($statusFilter !== 'all' && in_array($statusFilter, ['active', 'paused', 'stopped', 'pending', 'failed'], true)) {
            $query .= " WHERE r.status = ?";
            $params[] = $statusFilter;
        }
        $query .= " GROUP BY r.id ORDER BY r.created_at DESC";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $siteName = (string)($pdo->query("SELECT setting_value FROM settings WHERE setting_key='site_name'")->fetchColumn() ?: 'NGO System');

        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="recurring_donations_' . date('Y-m-d') . '.xls"');

        echo "<html><head><meta charset=\"UTF-8\"></head><body>";
        echo "<table border=\"1\">";
        echo "<tr><th colspan=\"13\" style=\"background:#0F8B8D;color:#fff;font-size:16px;padding:8px;\">" . htmlspecialchars($siteName) . " - Recurring Donations / AutoPay Report (" . date('d-m-Y') . ")</th></tr>";
        echo "<tr style=\"background:#f3f4f6;font-weight:bold;\">
                <th>ID</th>
                <th>Subscription ID</th>
                <th>Donor Name</th>
                <th>Email</th>
                <th>Mobile</th>
                <th>PAN</th>
                <th>Frequency</th>
                <th>Amount (INR)</th>
                <th>Project</th>
                <th>Completed Cycles</th>
                <th>Total Collected</th>
                <th>Next Charge Date</th>
                <th>Status</th>
              </tr>";

        foreach ($rows as $r) {
            echo "<tr>";
            echo "<td>" . (int)$r['id'] . "</td>";
            echo "<td>" . htmlspecialchars((string)($r['razorpay_subscription_id'] ?? 'N/A')) . "</td>";
            echo "<td>" . htmlspecialchars((string)$r['donor_name']) . "</td>";
            echo "<td>" . htmlspecialchars((string)$r['donor_email']) . "</td>";
            echo "<td>" . htmlspecialchars((string)$r['donor_mobile']) . "</td>";
            echo "<td>" . htmlspecialchars((string)($r['donor_pan'] ?? '—')) . "</td>";
            echo "<td>" . htmlspecialchars(ucfirst((string)$r['frequency'])) . "</td>";
            echo "<td>" . htmlspecialchars(number_format((float)$r['amount'], 2)) . "</td>";
            echo "<td>" . htmlspecialchars((string)($r['project_title'] ?? 'General')) . "</td>";
            echo "<td>" . (int)$r['completed_cycles'] . "</td>";
            echo "<td>" . htmlspecialchars(number_format((float)$r['total_collected'], 2)) . "</td>";
            echo "<td>" . htmlspecialchars((string)($r['next_charge_date'] ? date('d-m-Y', strtotime($r['next_charge_date'])) : '—')) . "</td>";
            echo "<td>" . htmlspecialchars(ucfirst((string)$r['status'])) . "</td>";
            echo "</tr>";
        }
        echo "</table></body></html>";
        exit;
    }
}

// ── POST Actions (Pause, Resume, Cancel) ──────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid security token.');
        header('Location: ../recurring_donations.php');
        exit;
    }

    $action = cleanInput($_POST['action'] ?? '');
    $recurringId = filter_input(INPUT_POST, 'recurring_id', FILTER_VALIDATE_INT);
    $reason = cleanInput($_POST['reason'] ?? '');

    if (!$recurringId) {
        setFlash('error', 'Invalid recurring donation ID.');
        header('Location: ../recurring_donations.php');
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM recurring_donations WHERE id = ?");
        $stmt->execute([$recurringId]);
        $recurring = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$recurring) {
            setFlash('error', 'Recurring donation record not found.');
            header('Location: ../recurring_donations.php');
            exit;
        }

        $subId = $recurring['razorpay_subscription_id'] ?? '';

        switch ($action) {
            case 'pause':
                $pauseReason = $reason ?: 'Paused manually by Admin (' . ($_SESSION['user_name'] ?? 'Admin') . ')';
                $pdo->prepare("UPDATE recurring_donations SET status = 'paused', pause_reason = ? WHERE id = ?")
                    ->execute([$pauseReason, $recurringId]);
                setFlash('success', 'Recurring donation #' . $recurringId . ' has been paused.');
                break;

            case 'resume':
                $pdo->prepare("UPDATE recurring_donations SET status = 'active', pause_reason = NULL WHERE id = ?")
                    ->execute([$recurringId]);
                setFlash('success', 'Recurring donation #' . $recurringId . ' has been resumed to Active.');
                break;

            case 'cancel':
                $cancelReason = $reason ?: 'Cancelled manually by Admin (' . ($_SESSION['user_name'] ?? 'Admin') . ')';
                $pdo->prepare("UPDATE recurring_donations SET status = 'stopped', cancel_reason = ?, end_date = CURRENT_DATE WHERE id = ?")
                    ->execute([$cancelReason, $recurringId]);
                setFlash('success', 'Recurring donation #' . $recurringId . ' has been stopped/cancelled.');
                break;

            default:
                setFlash('error', 'Unknown action.');
                break;
        }

    } catch (PDOException $e) {
        error_log("Admin recurring action error: " . $e->getMessage());
        setFlash('error', 'Database error: ' . $e->getMessage());
    }

    header('Location: ../recurring_donations.php');
    exit;
}
