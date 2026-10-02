<?php
// ============================================================
// admin/actions/generate_report.php
// Generates JSON report data for Monetary Donations & In-Kind Item Donations
// Supports category-wise, date-wise, project-wise, and status filtering
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
if (!checkRole($pdo, 'coordinator')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$reportType = cleanInput($_GET['report_type'] ?? 'all_donations');
$dateRange = cleanInput($_GET['date_range'] ?? 'this_month');
$projectId = filter_input(INPUT_GET, 'project_id', FILTER_VALIDATE_INT) ?: null;
$categoryId = filter_input(INPUT_GET, 'category_id', FILTER_VALIDATE_INT) ?: null;
$status = cleanInput($_GET['status'] ?? '');
$customStart = cleanInput($_GET['custom_start'] ?? '');
$customEnd = cleanInput($_GET['custom_end'] ?? '');

// ── 1. ITEM DONATION REPORT LOGIC ─────────────────────────────
if ($reportType === 'item_donations' || $reportType === 'item_category_wise' || strpos($reportType, 'item_') === 0) {
    $sql = "
        SELECT i.*, c.category_name, c.category_icon, p.title AS project_name 
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

    $enrichedItems = [];
    $totalEstVal = 0.0;
    $totalQty = 0.0;
    $catBreakdownMap = [];

    foreach ($items as $item) {
        $rcp = $item['receipt_no'] ?: ('RCP-ITM-' . $item['id']);
        $token = generateItemDonationReceiptToken($item['id'], $item['donation_code'], $rcp, $item['donor_email'], $item['created_at']);
        $item['receipt_download_url'] = '../download-item-receipt.php?id=' . (int)$item['id'] . '&token=' . urlencode($token);
        $item['verify_url'] = '../verify-item.php?code=' . urlencode($item['donation_code']) . '&token=' . urlencode($token);
        
        $val = (float)($item['estimated_value'] ?? 0);
        $qty = (float)($item['quantity'] ?? 0);
        $totalEstVal += $val;
        $totalQty += $qty;

        $catId = (int)($item['category_id'] ?: 0);
        $catName = $item['category_name'] ?: 'Essential Items';
        if (!isset($catBreakdownMap[$catId])) {
            $catBreakdownMap[$catId] = [
                'category_id' => $catId,
                'category_name' => $catName,
                'category_icon' => $item['category_icon'] ?: 'fa-gift',
                'count' => 0,
                'total_quantity' => 0.0,
                'total_value' => 0.0,
                'units' => []
            ];
        }
        $catBreakdownMap[$catId]['count']++;
        $catBreakdownMap[$catId]['total_quantity'] += $qty;
        $catBreakdownMap[$catId]['total_value'] += $val;
        if (!empty($item['unit']) && !in_array($item['unit'], $catBreakdownMap[$catId]['units'], true)) {
            $catBreakdownMap[$catId]['units'][] = $item['unit'];
        }

        $enrichedItems[] = $item;
    }

    $categoryBreakdown = array_values($catBreakdownMap);
    usort($categoryBreakdown, function ($a, $b) {
        return $b['count'] <=> $a['count'];
    });

    echo json_encode([
        'success' => true,
        'is_item_report' => true,
        'data' => $enrichedItems,
        'category_breakdown' => $categoryBreakdown,
        'summary' => [
            'totalCount' => count($enrichedItems),
            'totalAmount' => number_format($totalEstVal, 2),
            'totalEstimatedValue' => number_format($totalEstVal, 2),
            'totalQuantity' => $totalQty,
            'categoriesCount' => count($categoryBreakdown),
            'topCategory' => !empty($categoryBreakdown) ? $categoryBreakdown[0]['category_name'] : 'N/A'
        ]
    ]);
    exit;
}

// ── 2. MONETARY DONATION REPORT LOGIC ───────────────────────────
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
} elseif ($dateRange === 'this_quarter') {
    $month = (int)$now->format('n');
    if ($month <= 3) { $start = '01'; $end = '03'; }
    elseif ($month <= 6) { $start = '04'; $end = '06'; }
    elseif ($month <= 9) { $start = '07'; $end = '09'; }
    else { $start = '10'; $end = '12'; }
    $sql .= " AND MONTH(d.created_at) BETWEEN ? AND ? AND YEAR(d.created_at) = ?";
    $params[] = $start;
    $params[] = $end;
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

$totalAmount = array_sum(array_column($donations, 'amount'));
$totalCount = count($donations);

echo json_encode([
    'success' => true,
    'is_item_report' => false,
    'data' => $donations,
    'summary' => [
        'totalAmount' => number_format($totalAmount, 2),
        'totalCount' => $totalCount
    ]
]);
