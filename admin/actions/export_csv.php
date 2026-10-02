<?php
// ============================================================
// admin/actions/export_csv.php
// Exports Item Donation or Monetary Donation reports as CSV
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

// ── 1. ITEM DONATION CSV EXPORT ──────────────────────────────
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

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="item_donation_report_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Excel compatibility

    fputcsv($output, [$siteName . ' - In-Kind Item Donation Report']);
    fputcsv($output, ['Generated On: ' . date('d-m-Y H:i:s'), 'Filter: ' . $dateRange . ($categoryId ? ' (Category ID: ' . $categoryId . ')' : '')]);
    fputcsv($output, []);

    fputcsv($output, [
        'ID',
        'Donation Date',
        'Receipt No',
        'Pledge Code',
        'Donor Name',
        'Donor Email',
        'Donor Mobile',
        'PAN Number',
        'Category',
        'Item Description',
        'Quantity',
        'Unit',
        'Condition',
        'Estimated Value (INR)',
        'Status',
        'Pickup City',
        'Pickup Pincode',
        'Pickup Address',
        'Allocated Project',
        'Remarks'
    ]);

    foreach ($items as $row) {
        fputcsv($output, [
            $row['id'],
            !empty($row['donation_date']) ? date('d-m-Y', strtotime($row['donation_date'])) : '',
            $row['receipt_no'] ?: ('RCP-ITM-' . $row['id']),
            $row['donation_code'],
            $row['donor_name'],
            $row['donor_email'],
            $row['donor_mobile'],
            $row['donor_pan'] ?? '',
            $row['category_name'] ?: 'Essential Goods',
            $row['item_description'],
            $row['quantity'],
            $row['unit'],
            $row['condition_type'],
            $row['estimated_value'],
            $row['status'],
            $row['pickup_city'],
            $row['pickup_pincode'],
            $row['pickup_address'],
            $row['project_name'] ?? 'General Fund',
            $row['remarks'] ?? ''
        ]);
    }

    fclose($output);
    exit;
}

// ── 2. MONETARY DONATION CSV EXPORT ──────────────────────────
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
    $params[] = $now->format('m'); $params[] = $now->format('Y');
} elseif ($dateRange === 'last_month') {
    $now->modify('first day of last month');
    $sql .= " AND MONTH(d.created_at) = ? AND YEAR(d.created_at) = ?";
    $params[] = $now->format('m'); $params[] = $now->format('Y');
} elseif ($dateRange === 'this_year') {
    $sql .= " AND YEAR(d.created_at) = ?"; $params[] = $now->format('Y');
} elseif ($dateRange === 'custom' && $customStart && $customEnd) {
    $sql .= " AND DATE(d.created_at) BETWEEN ? AND ?";
    $params[] = $customStart; $params[] = $customEnd;
}

$sql .= " ORDER BY d.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$donations = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="donation_report_'.date('Y-m-d').'.csv"');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

fputcsv($output, [$siteName . ' - Monetary Donation Report']);
fputcsv($output, []);
fputcsv($output, ['ID', 'Date', 'Donor Name', 'Email', 'Mobile', 'Amount (INR)', 'PAN', 'Project', 'Receipt No']);

foreach ($donations as $row) {
    fputcsv($output, [
        $row['id'],
        date('d-m-Y', strtotime($row['created_at'])),
        $row['donor_name'],
        $row['donor_email'],
        $row['donor_mobile'],
        $row['amount'],
        $row['donor_pan'],
        $row['project_name'] ?? 'General',
        $row['receipt_no']
    ]);
}

fclose($output);
exit;
