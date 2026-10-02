<?php
// ============================================================
// admin/actions/export_excel.php
// Exports Item Donation or Monetary Donation reports as Excel (.xls)
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/functions.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    exit;
}
if (!checkRole($pdo, 'coordinator')) {
    http_response_code(403);
    exit;
}

$reportType = cleanInput($_GET['report_type'] ?? 'all_donations');
$dateRange = cleanInput($_GET['date_range'] ?? 'this_month');
$projectId = filter_input(INPUT_GET, 'project_id', FILTER_VALIDATE_INT) ?: null;
$categoryId = filter_input(INPUT_GET, 'category_id', FILTER_VALIDATE_INT) ?: null;
$status = cleanInput($_GET['status'] ?? '');
$customStart = cleanInput($_GET['custom_start'] ?? '');
$customEnd = cleanInput($_GET['custom_end'] ?? '');

$siteName = (string)($pdo->query("SELECT setting_value FROM settings WHERE setting_key='site_name'")->fetchColumn() ?: 'NGO System');

// ── 1. ITEM DONATION EXCEL EXPORT ────────────────────────────
if ($reportType === 'item_donations' || $reportType === 'item_category_wise' || strpos($reportType, 'item_') === 0) {
    $sql = "
        SELECT i.*, c.category_name, p.title AS project_name 
        FROM item_donations i 
        LEFT JOIN item_donation_categories c ON i.category_id = c.id 
        LEFT JOIN projects p ON i.project_id = p.id 
        WHERE 1=1
    ";
    $params = [];

    if (!empty($categoryId)) {
        $sql .= " AND i.category_id = ?";
        $params[] = $categoryId;
    }

    if (!empty($status) && $status !== 'all') {
        $sql .= " AND i.status = ?";
        $params[] = $status;
    }

    $dateCol = "COALESCE(i.donation_date, DATE(i.created_at))";
    $now = new DateTime();

    if ($dateRange === 'this_month') {
        $sql .= " AND MONTH({$dateCol}) = ? AND YEAR({$dateCol}) = ?";
        $params[] = $now->format('m');
        $params[] = $now->format('Y');
    } elseif ($dateRange === 'last_month') {
        $now->modify('first day of last month');
        $sql .= " AND MONTH({$dateCol}) = ? AND YEAR({$dateCol}) = ?";
        $params[] = $now->format('m');
        $params[] = $now->format('Y');
    } elseif ($dateRange === 'this_year') {
        $sql .= " AND YEAR({$dateCol}) = ?";
        $params[] = $now->format('Y');
    } elseif ($dateRange === 'this_quarter') {
        $month = (int)$now->format('n');
        if ($month <= 3) { $start = '01'; $end = '03'; }
        elseif ($month <= 6) { $start = '04'; $end = '06'; }
        elseif ($month <= 9) { $start = '07'; $end = '09'; }
        else { $start = '10'; $end = '12'; }
        $sql .= " AND MONTH({$dateCol}) BETWEEN ? AND ? AND YEAR({$dateCol}) = ?";
        $params[] = $start;
        $params[] = $end;
        $params[] = $now->format('Y');
    } elseif ($dateRange === 'custom' && $customStart && $customEnd) {
        $sql .= " AND DATE({$dateCol}) BETWEEN ? AND ?";
        $params[] = $customStart;
        $params[] = $customEnd;
    }

    $sql .= " ORDER BY i.donation_date DESC, i.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="item_donation_report_' . date('Y-m-d') . '.xls"');

    $totalVal = 0.0;
    $totalQty = 0.0;

    echo "<html><head><meta charset=\"UTF-8\"><style>
        body { font-family: Arial, sans-serif; }
        th { background-color: #0F8B8D; color: #ffffff; font-weight: bold; padding: 8px; text-align: left; }
        td { padding: 6px 8px; border: 1px solid #e2e8f0; font-size: 12px; }
        .num { text-align: right; }
        .center { text-align: center; }
        .total-row { background-color: #f1f5f9; font-weight: bold; }
    </style></head><body>";

    echo "<table border=\"1\" cellpadding=\"5\" cellspacing=\"0\">";
    echo "<tr><th colspan=\"17\" style=\"font-size: 16px; background-color: #0F8B8D; color: #ffffff; text-align: center; padding: 12px;\">" . htmlspecialchars($siteName) . " - In-Kind Item Donation Report</th></tr>";
    echo "<tr><td colspan=\"17\" style=\"background-color: #f8fafc; font-size: 11px; color: #64748b;\">Generated on: " . date('d-m-Y H:i:s') . " | Period: " . htmlspecialchars($dateRange) . " | Total Records: " . count($items) . "</td></tr>";
    
    echo "<tr>
        <th>ID</th>
        <th>Date</th>
        <th>Receipt No</th>
        <th>Pledge Code</th>
        <th>Donor Name</th>
        <th>Email</th>
        <th>Mobile</th>
        <th>PAN</th>
        <th>Category</th>
        <th>Item Description</th>
        <th>Qty</th>
        <th>Unit</th>
        <th>Condition</th>
        <th>Est. Value (INR)</th>
        <th>Status</th>
        <th>Pickup Location</th>
        <th>Project</th>
    </tr>";

    foreach ($items as $row) {
        $val = (float)($row['estimated_value'] ?? 0);
        $qty = (float)($row['quantity'] ?? 0);
        $totalVal += $val;
        $totalQty += $qty;

        echo "<tr>";
        echo "<td class=\"center\">" . (int)$row['id'] . "</td>";
        echo "<td class=\"center\">" . htmlspecialchars(!empty($row['donation_date']) ? date('d-m-Y', strtotime($row['donation_date'])) : '') . "</td>";
        echo "<td>" . htmlspecialchars($row['receipt_no'] ?: ('RCP-ITM-' . $row['id'])) . "</td>";
        echo "<td>" . htmlspecialchars($row['donation_code']) . "</td>";
        echo "<td><strong>" . htmlspecialchars($row['donor_name']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($row['donor_email']) . "</td>";
        echo "<td>" . htmlspecialchars($row['donor_mobile']) . "</td>";
        echo "<td>" . htmlspecialchars($row['donor_pan'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row['category_name'] ?: 'Essential Goods') . "</td>";
        echo "<td>" . htmlspecialchars($row['item_description']) . "</td>";
        echo "<td class=\"num\">" . number_format($qty, 2) . "</td>";
        echo "<td>" . htmlspecialchars($row['unit']) . "</td>";
        echo "<td class=\"center\">" . htmlspecialchars($row['condition_type']) . "</td>";
        echo "<td class=\"num\">" . number_format($val, 2) . "</td>";
        echo "<td class=\"center\">" . htmlspecialchars($row['status']) . "</td>";
        echo "<td>" . htmlspecialchars($row['pickup_city'] . ' (' . $row['pickup_pincode'] . ')') . "</td>";
        echo "<td>" . htmlspecialchars($row['project_name'] ?? 'General Fund') . "</td>";
        echo "</tr>";
    }

    echo "<tr class=\"total-row\">";
    echo "<td colspan=\"10\" style=\"text-align: right;\"><strong>Total Aggregate Summary:</strong></td>";
    echo "<td class=\"num\"><strong>" . number_format($totalQty, 2) . "</strong></td>";
    echo "<td colspan=\"2\"></td>";
    echo "<td class=\"num\"><strong>₹" . number_format($totalVal, 2) . "</strong></td>";
    echo "<td colspan=\"3\"></td>";
    echo "</tr>";

    echo "</table></body></html>";
    exit;
}

// ── 2. MONETARY DONATION EXCEL EXPORT ────────────────────────
$sql = "SELECT d.*, COALESCE(d.donor_name, 'Anonymous') AS donor_name, p.title as project_name FROM donations d LEFT JOIN projects p ON d.project_id = p.id WHERE d.payment_status IN ('Success', 'Pending')";
$params = [];

if ($reportType === '80g_donations') {
    $sql .= " AND d.is_80g_eligible = 1";
} elseif ($reportType === 'project_wise' && $projectId) {
    $sql .= " AND d.project_id = ?";
    $params[] = $projectId;
}

$now = new DateTime();
if ($dateRange === 'this_month') {
    $sql .= " AND MONTH(d.created_at) = ? AND YEAR(d.created_at) = ?";
    $params[] = $now->format('m');
    $params[] = $now->format('Y');
} elseif ($dateRange === 'last_month') {
    $now->modify('first day of last month');
    $sql .= " AND MONTH(d.created_at) = ? AND YEAR(d.created_at) = ?";
    $params[] = $now->format('m');
    $params[] = $now->format('Y');
} elseif ($dateRange === 'this_year') {
    $sql .= " AND YEAR(d.created_at) = ?";
    $params[] = $now->format('Y');
} elseif ($dateRange === 'custom' && $customStart && $customEnd) {
    $sql .= " AND DATE(d.created_at) BETWEEN ? AND ?";
    $params[] = $customStart;
    $params[] = $customEnd;
}

$sql .= " ORDER BY d.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$donations = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="donation_report_' . date('Y-m-d') . '.xls"');

echo "<html><head><meta charset=\"UTF-8\"></head><body>";
echo "<table border=\"1\">";
echo "<tr><th colspan=\"9\">" . htmlspecialchars($siteName !== '' ? $siteName : 'Donation Report') . "</th></tr>";
echo "<tr><th>ID</th><th>Date</th><th>Donor Name</th><th>Email</th><th>Mobile</th><th>Amount (INR)</th><th>PAN</th><th>Project</th><th>Receipt No</th></tr>";

foreach ($donations as $row) {
    echo "<tr>";
    echo "<td>" . (int)$row['id'] . "</td>";
    echo "<td>" . htmlspecialchars(date('d-m-Y', strtotime((string)$row['created_at']))) . "</td>";
    echo "<td>" . htmlspecialchars((string)$row['donor_name']) . "</td>";
    echo "<td>" . htmlspecialchars((string)$row['donor_email']) . "</td>";
    echo "<td>" . htmlspecialchars((string)$row['donor_mobile']) . "</td>";
    echo "<td>" . htmlspecialchars((string)$row['amount']) . "</td>";
    echo "<td>" . htmlspecialchars((string)($row['donor_pan'] ?? '')) . "</td>";
    echo "<td>" . htmlspecialchars((string)($row['project_name'] ?? 'General')) . "</td>";
    echo "<td>" . htmlspecialchars((string)($row['receipt_no'] ?? '')) . "</td>";
    echo "</tr>";
}

echo "</table></body></html>";
exit;
