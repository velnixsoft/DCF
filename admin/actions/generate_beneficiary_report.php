<?php
// ============================================================
// admin/actions/generate_beneficiary_report.php
// Generates JSON report data for Beneficiary Management & Welfare Aid Distributions
// Supports Location-wise (State/District/Block), Assistance Type-wise, Category-wise, and Detailed Rosters
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

$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$isManagerOrAdmin = checkRole($pdo, 'manager');

// Retrieve Filter Parameters
$reportMode = cleanInput($_GET['report_mode'] ?? 'location_wise');
$dateRange = cleanInput($_GET['date_range'] ?? 'all_time');
$state = cleanInput($_GET['state'] ?? '');
$district = cleanInput($_GET['district'] ?? '');
$block = cleanInput($_GET['block'] ?? '');
$categoryId = filter_input(INPUT_GET, 'category_id', FILTER_VALIDATE_INT) ?: null;
$assistanceType = cleanInput($_GET['assistance_type'] ?? '');
$status = cleanInput($_GET['status'] ?? '');
$customStart = cleanInput($_GET['custom_start'] ?? '');
$customEnd = cleanInput($_GET['custom_end'] ?? '');

// Coordinator filter (RBAC enforced)
if (!$isManagerOrAdmin) {
    $coordinatorId = $currentUserId;
} else {
    $coordinatorId = filter_input(INPUT_GET, 'coordinator_id', FILTER_VALIDATE_INT) ?: null;
}

// ── 1. DATE CLAUSE GENERATION ─────────────────────────────────
function buildDateClause(string $columnName, string $dateRange, string $customStart, string $customEnd, array &$params): string {
    $clause = "";
    $now = new DateTime();

    if ($dateRange === 'this_month') {
        $clause .= " AND MONTH({$columnName}) = ? AND YEAR({$columnName}) = ?";
        $params[] = $now->format('m');
        $params[] = $now->format('Y');
    } elseif ($dateRange === 'last_month') {
        $now->modify('first day of last month');
        $clause .= " AND MONTH({$columnName}) = ? AND YEAR({$columnName}) = ?";
        $params[] = $now->format('m');
        $params[] = $now->format('Y');
    } elseif ($dateRange === 'this_year') {
        $clause .= " AND YEAR({$columnName}) = ?";
        $params[] = $now->format('Y');
    } elseif ($dateRange === 'this_quarter') {
        $month = (int)$now->format('n');
        if ($month <= 3) { $start = '01'; $end = '03'; }
        elseif ($month <= 6) { $start = '04'; $end = '06'; }
        elseif ($month <= 9) { $start = '07'; $end = '09'; }
        else { $start = '10'; $end = '12'; }
        $clause .= " AND MONTH({$columnName}) BETWEEN ? AND ? AND YEAR({$columnName}) = ?";
        $params[] = $start;
        $params[] = $end;
        $params[] = $now->format('Y');
    } elseif ($dateRange === 'custom' && $customStart && $customEnd) {
        $clause .= " AND DATE({$columnName}) BETWEEN ? AND ?";
        $params[] = $customStart;
        $params[] = $customEnd;
    }
    return $clause;
}

// ── 2. GLOBAL SUMMARY METRICS ─────────────────────────────────
// Beneficiaries summary
$benSummarySql = "SELECT 
    COUNT(*) as total_beneficiaries,
    SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active_count,
    SUM(CASE WHEN status = 'Under Review' THEN 1 ELSE 0 END) as review_count,
    SUM(CASE WHEN status = 'Inactive' THEN 1 ELSE 0 END) as inactive_count,
    COUNT(DISTINCT state) as unique_states,
    COUNT(DISTINCT CONCAT(COALESCE(state,''), '-', COALESCE(district,''))) as unique_districts
    FROM beneficiaries WHERE 1=1";
$benSummaryParams = [];

if (!empty($state)) {
    $benSummarySql .= " AND state = ?";
    $benSummaryParams[] = $state;
}
if (!empty($district)) {
    $benSummarySql .= " AND district = ?";
    $benSummaryParams[] = $district;
}
if (!empty($block)) {
    $benSummarySql .= " AND block LIKE ?";
    $benSummaryParams[] = '%' . $block . '%';
}
if (!empty($categoryId)) {
    $benSummarySql .= " AND category_id = ?";
    $benSummaryParams[] = $categoryId;
}
if (!empty($status) && $status !== 'all') {
    $benSummarySql .= " AND status = ?";
    $benSummaryParams[] = $status;
}
$benSummarySql .= buildDateClause("COALESCE(registration_date, DATE(created_at))", $dateRange, $customStart, $customEnd, $benSummaryParams);

$benSummaryStmt = $pdo->prepare($benSummarySql);
$benSummaryStmt->execute($benSummaryParams);
$benSummary = $benSummaryStmt->fetch(PDO::FETCH_ASSOC) ?: [];

// Assistance Summary
$aidSummarySql = "SELECT 
    COUNT(ast.id) as total_assistance_events,
    COUNT(DISTINCT ast.beneficiary_id) as assisted_beneficiaries_count,
    COALESCE(SUM(ast.amount), 0) as total_cash_amount,
    COALESCE(SUM(ast.estimated_value), 0) as total_in_kind_value,
    COALESCE(SUM(ast.amount + ast.estimated_value), 0) as combined_aid_value,
    COALESCE(SUM(ast.quantity), 0) as total_aid_quantity
    FROM beneficiary_assistance_history ast
    JOIN beneficiaries b ON ast.beneficiary_id = b.id
    WHERE 1=1";
$aidSummaryParams = [];

if (!empty($state)) {
    $aidSummarySql .= " AND b.state = ?";
    $aidSummaryParams[] = $state;
}
if (!empty($district)) {
    $aidSummarySql .= " AND b.district = ?";
    $aidSummaryParams[] = $district;
}
if (!empty($block)) {
    $aidSummarySql .= " AND b.block LIKE ?";
    $aidSummaryParams[] = '%' . $block . '%';
}
if (!empty($categoryId)) {
    $aidSummarySql .= " AND b.category_id = ?";
    $aidSummaryParams[] = $categoryId;
}
if (!empty($assistanceType) && $assistanceType !== 'all') {
    $aidSummarySql .= " AND ast.assistance_type = ?";
    $aidSummaryParams[] = $assistanceType;
}
if (!empty($coordinatorId)) {
    $aidSummarySql .= " AND ast.coordinator_id = ?";
    $aidSummaryParams[] = $coordinatorId;
}
$aidSummarySql .= buildDateClause("ast.date", $dateRange, $customStart, $customEnd, $aidSummaryParams);

$aidSummaryStmt = $pdo->prepare($aidSummarySql);
$aidSummaryStmt->execute($aidSummaryParams);
$aidSummary = $aidSummaryStmt->fetch(PDO::FETCH_ASSOC) ?: [];

// ── 3. LOCATION-WISE BREAKDOWN (State & District & Block) ──────
$locSql = "SELECT 
    COALESCE(NULLIF(b.state, ''), 'Unspecified State') as state_name,
    COALESCE(NULLIF(b.district, ''), 'Unspecified District') as district_name,
    COALESCE(NULLIF(b.block, ''), 'Unspecified Block') as block_name,
    COUNT(DISTINCT b.id) as beneficiary_count,
    COUNT(ast.id) as aid_events_count,
    COALESCE(SUM(ast.amount), 0) as cash_amount,
    COALESCE(SUM(ast.estimated_value), 0) as in_kind_value,
    COALESCE(SUM(ast.amount + ast.estimated_value), 0) as total_aid_value
    FROM beneficiaries b
    LEFT JOIN beneficiary_assistance_history ast ON b.id = ast.beneficiary_id
    WHERE 1=1";
$locParams = [];

if (!empty($state)) {
    $locSql .= " AND b.state = ?";
    $locParams[] = $state;
}
if (!empty($district)) {
    $locSql .= " AND b.district = ?";
    $locParams[] = $district;
}
if (!empty($block)) {
    $locSql .= " AND b.block LIKE ?";
    $locParams[] = '%' . $block . '%';
}
if (!empty($categoryId)) {
    $locSql .= " AND b.category_id = ?";
    $locParams[] = $categoryId;
}
if (!empty($status) && $status !== 'all') {
    $locSql .= " AND b.status = ?";
    $locParams[] = $status;
}
if (!empty($coordinatorId)) {
    $locSql .= " AND (ast.coordinator_id = ? OR b.coordinator_id = ?)";
    $locParams[] = $coordinatorId;
    $locParams[] = $coordinatorId;
}
$locSql .= buildDateClause("COALESCE(b.registration_date, DATE(b.created_at))", $dateRange, $customStart, $customEnd, $locParams);

$locSql .= " GROUP BY state_name, district_name, block_name ORDER BY beneficiary_count DESC, total_aid_value DESC";

$locStmt = $pdo->prepare($locSql);
$locStmt->execute($locParams);
$locationBreakdown = $locStmt->fetchAll(PDO::FETCH_ASSOC);

// State-level Rollup
$stateRollupMap = [];
foreach ($locationBreakdown as $row) {
    $st = $row['state_name'];
    if (!isset($stateRollupMap[$st])) {
        $stateRollupMap[$st] = [
            'state_name' => $st,
            'districts_count' => 0,
            'beneficiary_count' => 0,
            'aid_events_count' => 0,
            'cash_amount' => 0.0,
            'in_kind_value' => 0.0,
            'total_aid_value' => 0.0,
            'districts' => []
        ];
    }
    $stateRollupMap[$st]['beneficiary_count'] += (int)$row['beneficiary_count'];
    $stateRollupMap[$st]['aid_events_count'] += (int)$row['aid_events_count'];
    $stateRollupMap[$st]['cash_amount'] += (float)$row['cash_amount'];
    $stateRollupMap[$st]['in_kind_value'] += (float)$row['in_kind_value'];
    $stateRollupMap[$st]['total_aid_value'] += (float)$row['total_aid_value'];

    $dst = $row['district_name'];
    if (!isset($stateRollupMap[$st]['districts'][$dst])) {
        $stateRollupMap[$st]['districts'][$dst] = [
            'district_name' => $dst,
            'beneficiary_count' => 0,
            'aid_events_count' => 0,
            'total_aid_value' => 0.0,
            'blocks' => []
        ];
        $stateRollupMap[$st]['districts_count']++;
    }
    $stateRollupMap[$st]['districts'][$dst]['beneficiary_count'] += (int)$row['beneficiary_count'];
    $stateRollupMap[$st]['districts'][$dst]['aid_events_count'] += (int)$row['aid_events_count'];
    $stateRollupMap[$st]['districts'][$dst]['total_aid_value'] += (float)$row['total_aid_value'];
    $stateRollupMap[$st]['districts'][$dst]['blocks'][] = [
        'block_name' => $row['block_name'],
        'beneficiary_count' => (int)$row['beneficiary_count'],
        'aid_events_count' => (int)$row['aid_events_count'],
        'total_aid_value' => (float)$row['total_aid_value']
    ];
}
$stateRollupList = array_values($stateRollupMap);
usort($stateRollupList, function ($a, $b) {
    return $b['beneficiary_count'] <=> $a['beneficiary_count'];
});

// ── 4. ASSISTANCE TYPE-WISE BREAKDOWN ──────────────────────────
$typeSql = "SELECT 
    ast.assistance_type,
    COUNT(ast.id) as distribution_count,
    COUNT(DISTINCT ast.beneficiary_id) as beneficiaries_served,
    COALESCE(SUM(ast.amount), 0) as total_cash,
    COALESCE(SUM(ast.estimated_value), 0) as total_in_kind,
    COALESCE(SUM(ast.amount + ast.estimated_value), 0) as total_valuation,
    COALESCE(SUM(ast.quantity), 0) as total_quantity,
    GROUP_CONCAT(DISTINCT ast.unit SEPARATOR ', ') as units_used
    FROM beneficiary_assistance_history ast
    JOIN beneficiaries b ON ast.beneficiary_id = b.id
    WHERE 1=1";
$typeParams = [];

if (!empty($state)) {
    $typeSql .= " AND b.state = ?";
    $typeParams[] = $state;
}
if (!empty($district)) {
    $typeSql .= " AND b.district = ?";
    $typeParams[] = $district;
}
if (!empty($block)) {
    $typeSql .= " AND b.block LIKE ?";
    $typeParams[] = '%' . $block . '%';
}
if (!empty($categoryId)) {
    $typeSql .= " AND b.category_id = ?";
    $typeParams[] = $categoryId;
}
if (!empty($assistanceType) && $assistanceType !== 'all') {
    $typeSql .= " AND ast.assistance_type = ?";
    $typeParams[] = $assistanceType;
}
if (!empty($coordinatorId)) {
    $typeSql .= " AND ast.coordinator_id = ?";
    $typeParams[] = $coordinatorId;
}
$typeSql .= buildDateClause("ast.date", $dateRange, $customStart, $customEnd, $typeParams);

$typeSql .= " GROUP BY ast.assistance_type ORDER BY total_valuation DESC, distribution_count DESC";

$typeStmt = $pdo->prepare($typeSql);
$typeStmt->execute($typeParams);
$assistanceTypeBreakdown = $typeStmt->fetchAll(PDO::FETCH_ASSOC);

// ── 5. BENEFICIARY CATEGORY-WISE BREAKDOWN ────────────────────
$catSql = "SELECT 
    c.id as category_id,
    COALESCE(c.category_name, 'General / Unclassified') as category_name,
    COUNT(DISTINCT b.id) as beneficiary_count,
    COUNT(ast.id) as assistance_count,
    COALESCE(SUM(ast.amount + ast.estimated_value), 0) as total_aid_value
    FROM beneficiaries b
    LEFT JOIN beneficiary_categories c ON b.category_id = c.id
    LEFT JOIN beneficiary_assistance_history ast ON b.id = ast.beneficiary_id
    WHERE 1=1";
$catParams = [];

if (!empty($state)) {
    $catSql .= " AND b.state = ?";
    $catParams[] = $state;
}
if (!empty($district)) {
    $catSql .= " AND b.district = ?";
    $catParams[] = $district;
}
if (!empty($block)) {
    $catSql .= " AND b.block LIKE ?";
    $catParams[] = '%' . $block . '%';
}
if (!empty($status) && $status !== 'all') {
    $catSql .= " AND b.status = ?";
    $catParams[] = $status;
}
if (!empty($coordinatorId)) {
    $catSql .= " AND (ast.coordinator_id = ? OR b.coordinator_id = ?)";
    $catParams[] = $coordinatorId;
    $catParams[] = $coordinatorId;
}
$catSql .= buildDateClause("COALESCE(b.registration_date, DATE(b.created_at))", $dateRange, $customStart, $customEnd, $catParams);

$catSql .= " GROUP BY c.id, c.category_name ORDER BY beneficiary_count DESC";

$catStmt = $pdo->prepare($catSql);
$catStmt->execute($catParams);
$categoryBreakdown = $catStmt->fetchAll(PDO::FETCH_ASSOC);

// ── 6. DETAILED BENEFICIARIES ROSTER ──────────────────────────
$benListSql = "SELECT 
    b.id,
    b.beneficiary_code,
    b.name,
    b.father_husband_name,
    b.contact,
    b.state,
    b.district,
    b.block,
    b.village_ward,
    b.status,
    b.registration_date,
    c.category_name,
    u.name as coordinator_name,
    COUNT(ast.id) as assistance_count,
    COALESCE(SUM(ast.amount + ast.estimated_value), 0) as total_aid_received
    FROM beneficiaries b
    LEFT JOIN beneficiary_categories c ON b.category_id = c.id
    LEFT JOIN users u ON b.coordinator_id = u.id
    LEFT JOIN beneficiary_assistance_history ast ON b.id = ast.beneficiary_id
    WHERE 1=1";
$benListParams = [];

if (!empty($state)) {
    $benListSql .= " AND b.state = ?";
    $benListParams[] = $state;
}
if (!empty($district)) {
    $benListSql .= " AND b.district = ?";
    $benListParams[] = $district;
}
if (!empty($block)) {
    $benListSql .= " AND b.block LIKE ?";
    $benListParams[] = '%' . $block . '%';
}
if (!empty($categoryId)) {
    $benListSql .= " AND b.category_id = ?";
    $benListParams[] = $categoryId;
}
if (!empty($status) && $status !== 'all') {
    $benListSql .= " AND b.status = ?";
    $benListParams[] = $status;
}
if (!empty($coordinatorId)) {
    $benListSql .= " AND (ast.coordinator_id = ? OR b.coordinator_id = ?)";
    $benListParams[] = $coordinatorId;
    $benListParams[] = $coordinatorId;
}
$benListSql .= buildDateClause("COALESCE(b.registration_date, DATE(b.created_at))", $dateRange, $customStart, $customEnd, $benListParams);

$benListSql .= " GROUP BY b.id, b.beneficiary_code, b.name, b.father_husband_name, b.contact, b.state, b.district, b.block, b.village_ward, b.status, b.registration_date, c.category_name, u.name 
                 ORDER BY b.id DESC LIMIT 500";

$benListStmt = $pdo->prepare($benListSql);
$benListStmt->execute($benListParams);
$beneficiariesList = $benListStmt->fetchAll(PDO::FETCH_ASSOC);

// ── 7. DETAILED ASSISTANCE HANDOVER LOG ────────────────────────
$aidListSql = "SELECT 
    ast.id,
    ast.assistance_code,
    ast.date,
    ast.assistance_type,
    ast.description,
    ast.amount,
    ast.estimated_value,
    (ast.amount + ast.estimated_value) as total_value,
    ast.quantity,
    ast.unit,
    ast.distribution_location,
    ast.given_by,
    ast.receipt_no,
    ast.status,
    b.id as beneficiary_id,
    b.name as beneficiary_name,
    b.beneficiary_code,
    b.contact as beneficiary_contact,
    b.district,
    b.state,
    c.category_name,
    u.name as coordinator_name,
    u.coordinator_code
    FROM beneficiary_assistance_history ast
    JOIN beneficiaries b ON ast.beneficiary_id = b.id
    LEFT JOIN beneficiary_categories c ON b.category_id = c.id
    LEFT JOIN users u ON ast.coordinator_id = u.id
    WHERE 1=1";
$aidListParams = [];

if (!empty($state)) {
    $aidListSql .= " AND b.state = ?";
    $aidListParams[] = $state;
}
if (!empty($district)) {
    $aidListSql .= " AND b.district = ?";
    $aidListParams[] = $district;
}
if (!empty($block)) {
    $aidListSql .= " AND b.block LIKE ?";
    $aidListParams[] = '%' . $block . '%';
}
if (!empty($categoryId)) {
    $aidListSql .= " AND b.category_id = ?";
    $aidListParams[] = $categoryId;
}
if (!empty($assistanceType) && $assistanceType !== 'all') {
    $aidListSql .= " AND ast.assistance_type = ?";
    $aidListParams[] = $assistanceType;
}
if (!empty($coordinatorId)) {
    $aidListSql .= " AND ast.coordinator_id = ?";
    $aidListParams[] = $coordinatorId;
}
$aidListSql .= buildDateClause("ast.date", $dateRange, $customStart, $customEnd, $aidListParams);

$aidListSql .= " ORDER BY ast.date DESC, ast.id DESC LIMIT 500";

$aidListStmt = $pdo->prepare($aidListSql);
$aidListStmt->execute($aidListParams);
$assistanceList = $aidListStmt->fetchAll(PDO::FETCH_ASSOC);

// ── 8. RETURN FINAL JSON RESPONSE ──────────────────────────────
echo json_encode([
    'success' => true,
    'summary' => [
        'totalBeneficiaries' => (int)($benSummary['total_beneficiaries'] ?? 0),
        'activeBeneficiaries' => (int)($benSummary['active_count'] ?? 0),
        'reviewBeneficiaries' => (int)($benSummary['review_count'] ?? 0),
        'inactiveBeneficiaries' => (int)($benSummary['inactive_count'] ?? 0),
        'uniqueStates' => (int)($benSummary['unique_states'] ?? 0),
        'uniqueDistricts' => (int)($benSummary['unique_districts'] ?? 0),
        'totalAidEvents' => (int)($aidSummary['total_assistance_events'] ?? 0),
        'assistedBeneficiaries' => (int)($aidSummary['assisted_beneficiaries_count'] ?? 0),
        'totalCashAmount' => (float)($aidSummary['total_cash_amount'] ?? 0),
        'totalInKindValue' => (float)($aidSummary['total_in_kind_value'] ?? 0),
        'combinedAidValue' => (float)($aidSummary['combined_aid_value'] ?? 0),
        'totalAidQuantity' => (float)($aidSummary['total_aid_quantity'] ?? 0),
        'topAssistanceType' => !empty($assistanceTypeBreakdown) ? $assistanceTypeBreakdown[0]['assistance_type'] : 'N/A',
        'topState' => !empty($stateRollupList) ? $stateRollupList[0]['state_name'] : 'N/A'
    ],
    'state_rollup' => $stateRollupList,
    'location_breakdown' => $locationBreakdown,
    'assistance_type_breakdown' => $assistanceTypeBreakdown,
    'category_breakdown' => $categoryBreakdown,
    'beneficiaries_list' => $beneficiariesList,
    'assistance_list' => $assistanceList,
    'filters_applied' => [
        'report_mode' => $reportMode,
        'date_range' => $dateRange,
        'state' => $state,
        'district' => $district,
        'block' => $block,
        'category_id' => $categoryId,
        'assistance_type' => $assistanceType,
        'status' => $status,
        'coordinator_id' => $coordinatorId
    ]
], JSON_UNESCAPED_UNICODE);
