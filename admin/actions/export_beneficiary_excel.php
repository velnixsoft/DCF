<?php
// ============================================================
// admin/actions/export_beneficiary_excel.php
// Exports Beneficiary and Assistance reports as styled Excel (.xls)
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

$siteName = (string)($pdo->query("SELECT setting_value FROM settings WHERE setting_key='site_name'")->fetchColumn() ?: 'NGO System');

function buildDateFilterClause(string $columnName, string $dateRange, string $customStart, string $customEnd, array &$params): string {
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

$filename = 'beneficiary_report_' . $reportMode . '_' . date('Y-m-d') . '.xls';
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

echo "<html xmlns:x=\"urn:schemas-microsoft-com:office:excel\">";
echo "<head><meta charset=\"UTF-8\"><style>
    body { font-family: Calibri, Arial, sans-serif; }
    .org-title { font-size: 16pt; font-weight: bold; color: #0F8B8D; }
    .report-sub { font-size: 10pt; color: #475569; margin-bottom: 12px; }
    .meta-box { font-size: 9pt; color: #64748b; background-color: #f8fafc; padding: 6px; border: 1px solid #e2e8f0; margin-bottom: 10px; }
    table { border-collapse: collapse; width: 100%; margin-top: 10px; }
    th { background-color: #0F8B8D; color: #ffffff; font-weight: bold; padding: 8px 10px; border: 1px solid #0c7274; text-align: left; font-size: 10pt; }
    td { padding: 6px 10px; border: 1px solid #e2e8f0; font-size: 9.5pt; vertical-align: middle; }
    .num { text-align: right; mso-number-format: '0.00'; }
    .int { text-align: right; mso-number-format: '0'; }
    .center { text-align: center; }
    .total-row { background-color: #f1f5f9; font-weight: bold; border-top: 2px solid #0F8B8D; }
    .badge { padding: 2px 6px; border-radius: 4px; font-weight: bold; font-size: 8.5pt; }
    .badge-active { background-color: #dcfce7; color: #15803d; }
</style></head><body>";

// Top Header
echo "<div class='org-title'>" . htmlspecialchars(strtoupper($siteName)) . "</div>";
echo "<div class='report-sub'>Official Beneficiary & Welfare Aid Distribution Report</div>";
echo "<div class='meta-box'>";
echo "<strong>Report Mode:</strong> " . htmlspecialchars(ucwords(str_replace('_', ' ', $reportMode))) . " | ";
echo "<strong>Period:</strong> " . htmlspecialchars(ucwords(str_replace('_', ' ', $dateRange))) . " | ";
if (!empty($state)) echo "<strong>State:</strong> " . htmlspecialchars($state) . " | ";
if (!empty($district)) echo "<strong>District:</strong> " . htmlspecialchars($district) . " | ";
echo "<strong>Generated On:</strong> " . date('d F Y, h:i A') . " | ";
echo "<strong>Generated By:</strong> " . htmlspecialchars($_SESSION['user_name'] ?? 'Authorized User');
echo "</div>";

// ── 1. LOCATION-WISE REPORT ───────────────────────────────────
if ($reportMode === 'location_wise') {
    $sql = "SELECT 
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
    $params = [];

    if (!empty($state)) { $sql .= " AND b.state = ?"; $params[] = $state; }
    if (!empty($district)) { $sql .= " AND b.district = ?"; $params[] = $district; }
    if (!empty($block)) { $sql .= " AND b.block LIKE ?"; $params[] = '%' . $block . '%'; }
    if (!empty($categoryId)) { $sql .= " AND b.category_id = ?"; $params[] = $categoryId; }
    if (!empty($status) && $status !== 'all') { $sql .= " AND b.status = ?"; $params[] = $status; }
    if (!empty($coordinatorId)) { $sql .= " AND (ast.coordinator_id = ? OR b.coordinator_id = ?)"; $params[] = $coordinatorId; $params[] = $coordinatorId; }
    $sql .= buildDateFilterClause("COALESCE(b.registration_date, DATE(b.created_at))", $dateRange, $customStart, $customEnd, $params);
    $sql .= " GROUP BY state_name, district_name, block_name ORDER BY beneficiary_count DESC, total_aid_value DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table>";
    echo "<thead><tr>
        <th class='center'>#</th>
        <th>State / UT</th>
        <th>District</th>
        <th>Block / Tehsil</th>
        <th class='int'>Enrolled Beneficiaries</th>
        <th class='int'>Aid Events Logged</th>
        <th class='num'>Direct Cash Aid (₹)</th>
        <th class='num'>In-Kind Goods Value (₹)</th>
        <th class='num'>Total Aid Valuation (₹)</th>
    </tr></thead><tbody>";

    $totBen = 0; $totEvents = 0; $totCash = 0.0; $totInKind = 0.0; $totVal = 0.0;

    foreach ($rows as $idx => $r) {
        $totBen += (int)$r['beneficiary_count'];
        $totEvents += (int)$r['aid_events_count'];
        $totCash += (float)$r['cash_amount'];
        $totInKind += (float)$r['in_kind_value'];
        $totVal += (float)$r['total_aid_value'];

        echo "<tr>";
        echo "<td class='center'>" . ($idx + 1) . "</td>";
        echo "<td>" . htmlspecialchars($r['state_name']) . "</td>";
        echo "<td>" . htmlspecialchars($r['district_name']) . "</td>";
        echo "<td>" . htmlspecialchars($r['block_name']) . "</td>";
        echo "<td class='int'>" . number_format($r['beneficiary_count']) . "</td>";
        echo "<td class='int'>" . number_format($r['aid_events_count']) . "</td>";
        echo "<td class='num'>" . number_format($r['cash_amount'], 2, '.', '') . "</td>";
        echo "<td class='num'>" . number_format($r['in_kind_value'], 2, '.', '') . "</td>";
        echo "<td class='num'>" . number_format($r['total_aid_value'], 2, '.', '') . "</td>";
        echo "</tr>";
    }

    echo "<tr class='total-row'>";
    echo "<td colspan='4' class='center'>TOTAL SUMMARY</td>";
    echo "<td class='int'>" . number_format($totBen) . "</td>";
    echo "<td class='int'>" . number_format($totEvents) . "</td>";
    echo "<td class='num'>" . number_format($totCash, 2, '.', '') . "</td>";
    echo "<td class='num'>" . number_format($totInKind, 2, '.', '') . "</td>";
    echo "<td class='num'>" . number_format($totVal, 2, '.', '') . "</td>";
    echo "</tr>";
    echo "</tbody></table>";

// ── 2. ASSISTANCE TYPE-WISE REPORT ─────────────────────────────
} elseif ($reportMode === 'assistance_type_wise') {
    $sql = "SELECT 
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
    $params = [];

    if (!empty($state)) { $sql .= " AND b.state = ?"; $params[] = $state; }
    if (!empty($district)) { $sql .= " AND b.district = ?"; $params[] = $district; }
    if (!empty($block)) { $sql .= " AND b.block LIKE ?"; $params[] = '%' . $block . '%'; }
    if (!empty($categoryId)) { $sql .= " AND b.category_id = ?"; $params[] = $categoryId; }
    if (!empty($assistanceType) && $assistanceType !== 'all') { $sql .= " AND ast.assistance_type = ?"; $params[] = $assistanceType; }
    if (!empty($coordinatorId)) { $sql .= " AND ast.coordinator_id = ?"; $params[] = $coordinatorId; }
    $sql .= buildDateFilterClause("ast.date", $dateRange, $customStart, $customEnd, $params);
    $sql .= " GROUP BY ast.assistance_type ORDER BY total_valuation DESC, distribution_count DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table>";
    echo "<thead><tr>
        <th class='center'>#</th>
        <th>Assistance Category / Type</th>
        <th class='int'>Distribution Events</th>
        <th class='int'>Beneficiaries Reached</th>
        <th class='num'>Direct Cash Support (₹)</th>
        <th class='num'>In-Kind Goods Value (₹)</th>
        <th class='num'>Total Valuation (₹)</th>
        <th class='int'>Total Quantity Disbursed</th>
        <th>Units Used</th>
    </tr></thead><tbody>";

    $totDist = 0; $totBenServed = 0; $totCash = 0.0; $totInKind = 0.0; $totVal = 0.0; $totQty = 0.0;

    foreach ($rows as $idx => $r) {
        $totDist += (int)$r['distribution_count'];
        $totBenServed += (int)$r['beneficiaries_served'];
        $totCash += (float)$r['total_cash'];
        $totInKind += (float)$r['total_in_kind'];
        $totVal += (float)$r['total_valuation'];
        $totQty += (float)$r['total_quantity'];

        echo "<tr>";
        echo "<td class='center'>" . ($idx + 1) . "</td>";
        echo "<td><strong>" . htmlspecialchars($r['assistance_type']) . "</strong></td>";
        echo "<td class='int'>" . number_format($r['distribution_count']) . "</td>";
        echo "<td class='int'>" . number_format($r['beneficiaries_served']) . "</td>";
        echo "<td class='num'>" . number_format($r['total_cash'], 2, '.', '') . "</td>";
        echo "<td class='num'>" . number_format($r['total_in_kind'], 2, '.', '') . "</td>";
        echo "<td class='num'>" . number_format($r['total_valuation'], 2, '.', '') . "</td>";
        echo "<td class='int'>" . number_format($r['total_quantity'], 1) . "</td>";
        echo "<td>" . htmlspecialchars($r['units_used'] ?: 'units') . "</td>";
        echo "</tr>";
    }

    echo "<tr class='total-row'>";
    echo "<td colspan='2' class='center'>TOTAL AGGREGATE</td>";
    echo "<td class='int'>" . number_format($totDist) . "</td>";
    echo "<td class='int'>" . number_format($totBenServed) . "</td>";
    echo "<td class='num'>" . number_format($totCash, 2, '.', '') . "</td>";
    echo "<td class='num'>" . number_format($totInKind, 2, '.', '') . "</td>";
    echo "<td class='num'>" . number_format($totVal, 2, '.', '') . "</td>";
    echo "<td class='int'>" . number_format($totQty, 1) . "</td>";
    echo "<td>-</td>";
    echo "</tr>";
    echo "</tbody></table>";

// ── 3. DETAILED BENEFICIARIES ROSTER ──────────────────────────
} elseif ($reportMode === 'beneficiaries_list') {
    $sql = "SELECT 
        b.id, b.beneficiary_code, b.name, b.father_husband_name, b.contact, b.email,
        b.state, b.district, b.block, b.village_ward, b.status, b.registration_date,
        c.category_name, u.name as coordinator_name,
        COUNT(ast.id) as assistance_count,
        COALESCE(SUM(ast.amount + ast.estimated_value), 0) as total_aid_received
        FROM beneficiaries b
        LEFT JOIN beneficiary_categories c ON b.category_id = c.id
        LEFT JOIN users u ON b.coordinator_id = u.id
        LEFT JOIN beneficiary_assistance_history ast ON b.id = ast.beneficiary_id
        WHERE 1=1";
    $params = [];

    if (!empty($state)) { $sql .= " AND b.state = ?"; $params[] = $state; }
    if (!empty($district)) { $sql .= " AND b.district = ?"; $params[] = $district; }
    if (!empty($block)) { $sql .= " AND b.block LIKE ?"; $params[] = '%' . $block . '%'; }
    if (!empty($categoryId)) { $sql .= " AND b.category_id = ?"; $params[] = $categoryId; }
    if (!empty($status) && $status !== 'all') { $sql .= " AND b.status = ?"; $params[] = $status; }
    if (!empty($coordinatorId)) { $sql .= " AND (ast.coordinator_id = ? OR b.coordinator_id = ?)"; $params[] = $coordinatorId; $params[] = $coordinatorId; }
    $sql .= buildDateFilterClause("COALESCE(b.registration_date, DATE(b.created_at))", $dateRange, $customStart, $customEnd, $params);
    $sql .= " GROUP BY b.id, b.beneficiary_code, b.name, b.father_husband_name, b.contact, b.email, b.state, b.district, b.block, b.village_ward, b.status, b.registration_date, c.category_name, u.name ORDER BY b.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table>";
    echo "<thead><tr>
        <th class='center'>#</th>
        <th>Beneficiary Code</th>
        <th>Full Name</th>
        <th>Father / Husband</th>
        <th>Contact No</th>
        <th>Category</th>
        <th>State</th>
        <th>District</th>
        <th>Block</th>
        <th>Village / Ward</th>
        <th>Status</th>
        <th class='int'>Aid Events Count</th>
        <th class='num'>Total Aid Received (₹)</th>
        <th>Coordinator</th>
        <th>Enrolled Date</th>
    </tr></thead><tbody>";

    $totAidReceived = 0.0;

    foreach ($rows as $idx => $r) {
        $totAidReceived += (float)$r['total_aid_received'];
        echo "<tr>";
        echo "<td class='center'>" . ($idx + 1) . "</td>";
        echo "<td><strong>" . htmlspecialchars($r['beneficiary_code'] ?: ('BEN-' . $r['id'])) . "</strong></td>";
        echo "<td>" . htmlspecialchars($r['name']) . "</td>";
        echo "<td>" . htmlspecialchars($r['father_husband_name'] ?: '-') . "</td>";
        echo "<td>" . htmlspecialchars($r['contact'] ?: '-') . "</td>";
        echo "<td>" . htmlspecialchars($r['category_name'] ?: 'General') . "</td>";
        echo "<td>" . htmlspecialchars($r['state'] ?: '-') . "</td>";
        echo "<td>" . htmlspecialchars($r['district'] ?: '-') . "</td>";
        echo "<td>" . htmlspecialchars($r['block'] ?: '-') . "</td>";
        echo "<td>" . htmlspecialchars($r['village_ward'] ?: '-') . "</td>";
        echo "<td>" . htmlspecialchars($r['status'] ?: 'Active') . "</td>";
        echo "<td class='int'>" . (int)$r['assistance_count'] . "</td>";
        echo "<td class='num'>" . number_format($r['total_aid_received'], 2, '.', '') . "</td>";
        echo "<td>" . htmlspecialchars($r['coordinator_name'] ?: 'Unassigned') . "</td>";
        echo "<td>" . (!empty($r['registration_date']) ? date('d-m-Y', strtotime($r['registration_date'])) : '-') . "</td>";
        echo "</tr>";
    }

    echo "<tr class='total-row'>";
    echo "<td colspan='11' class='center'>TOTAL SUMMARY (" . count($rows) . " Beneficiaries)</td>";
    echo "<td colspan='2' class='num'>₹" . number_format($totAidReceived, 2, '.', '') . "</td>";
    echo "<td colspan='2'></td>";
    echo "</tr>";
    echo "</tbody></table>";

// ── 4. DETAILED ASSISTANCE HANDOVER LOG ────────────────────────
} else {
    $sql = "SELECT 
        ast.id, ast.assistance_code, ast.date, ast.assistance_type, ast.description,
        ast.amount, ast.estimated_value, (ast.amount + ast.estimated_value) as total_value,
        ast.quantity, ast.unit, ast.distribution_location, ast.given_by, ast.receipt_no, ast.status,
        b.name as beneficiary_name, b.beneficiary_code, b.contact as beneficiary_contact,
        b.district, b.state, c.category_name, u.name as coordinator_name
        FROM beneficiary_assistance_history ast
        JOIN beneficiaries b ON ast.beneficiary_id = b.id
        LEFT JOIN beneficiary_categories c ON b.category_id = c.id
        LEFT JOIN users u ON ast.coordinator_id = u.id
        WHERE 1=1";
    $params = [];

    if (!empty($state)) { $sql .= " AND b.state = ?"; $params[] = $state; }
    if (!empty($district)) { $sql .= " AND b.district = ?"; $params[] = $district; }
    if (!empty($block)) { $sql .= " AND b.block LIKE ?"; $params[] = '%' . $block . '%'; }
    if (!empty($categoryId)) { $sql .= " AND b.category_id = ?"; $params[] = $categoryId; }
    if (!empty($assistanceType) && $assistanceType !== 'all') { $sql .= " AND ast.assistance_type = ?"; $params[] = $assistanceType; }
    if (!empty($coordinatorId)) { $sql .= " AND ast.coordinator_id = ?"; $params[] = $coordinatorId; }
    $sql .= buildDateFilterClause("ast.date", $dateRange, $customStart, $customEnd, $params);
    $sql .= " ORDER BY ast.date DESC, ast.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table>";
    echo "<thead><tr>
        <th class='center'>#</th>
        <th>Handover Date</th>
        <th>Aid Code / Voucher</th>
        <th>Beneficiary Name</th>
        <th>Beneficiary Code</th>
        <th>Category</th>
        <th>Location (Dist, State)</th>
        <th>Assistance Type</th>
        <th>Description / Particulars</th>
        <th class='num'>Cash Support (₹)</th>
        <th class='num'>In-Kind Goods Value (₹)</th>
        <th class='num'>Combined Valuation (₹)</th>
        <th class='int'>Qty / Unit</th>
        <th>Distribution Center</th>
        <th>Coordinator / Given By</th>
    </tr></thead><tbody>";

    $totCash = 0.0; $totInKind = 0.0; $totVal = 0.0;

    foreach ($rows as $idx => $r) {
        $totCash += (float)$r['amount'];
        $totInKind += (float)$r['estimated_value'];
        $totVal += (float)$r['total_value'];

        echo "<tr>";
        echo "<td class='center'>" . ($idx + 1) . "</td>";
        echo "<td>" . date('d-m-Y', strtotime($r['date'])) . "</td>";
        echo "<td><strong>" . htmlspecialchars($r['assistance_code'] ?: ($r['receipt_no'] ?: ('AID-' . $r['id']))) . "</strong></td>";
        echo "<td>" . htmlspecialchars($r['beneficiary_name']) . "</td>";
        echo "<td>" . htmlspecialchars($r['beneficiary_code'] ?: '-') . "</td>";
        echo "<td>" . htmlspecialchars($r['category_name'] ?: 'General') . "</td>";
        echo "<td>" . htmlspecialchars(($r['district'] ?: '') . ', ' . ($r['state'] ?: '')) . "</td>";
        echo "<td>" . htmlspecialchars($r['assistance_type']) . "</td>";
        echo "<td>" . htmlspecialchars($r['description']) . "</td>";
        echo "<td class='num'>" . number_format($r['amount'], 2, '.', '') . "</td>";
        echo "<td class='num'>" . number_format($r['estimated_value'], 2, '.', '') . "</td>";
        echo "<td class='num'>" . number_format($r['total_value'], 2, '.', '') . "</td>";
        echo "<td class='int'>" . number_format($r['quantity'], 1) . ' ' . htmlspecialchars($r['unit']) . "</td>";
        echo "<td>" . htmlspecialchars($r['distribution_location'] ?: '-') . "</td>";
        echo "<td>" . htmlspecialchars($r['coordinator_name'] ?: ($r['given_by'] ?: 'Coordinator')) . "</td>";
        echo "</tr>";
    }

    echo "<tr class='total-row'>";
    echo "<td colspan='9' class='center'>TOTAL AID DISBURSED (" . count($rows) . " Events)</td>";
    echo "<td class='num'>" . number_format($totCash, 2, '.', '') . "</td>";
    echo "<td class='num'>" . number_format($totInKind, 2, '.', '') . "</td>";
    echo "<td class='num'>" . number_format($totVal, 2, '.', '') . "</td>";
    echo "<td colspan='3'></td>";
    echo "</tr>";
    echo "</tbody></table>";
}

echo "</body></html>";
