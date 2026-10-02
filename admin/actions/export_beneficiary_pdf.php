<?php
// ============================================================
// admin/actions/export_beneficiary_pdf.php
// Exports Beneficiary & Welfare Aid reports as PDF via FPDF
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/member_module.php';
require_once '../../libs/fpdf/fpdf.php';

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

$settings_data = $pdo->query("SELECT * FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);

// Filter subtitle builder
$filterSubtitleParts = [];
$filterSubtitleParts[] = "Report: " . ucwords(str_replace('_', ' ', $reportMode));

if ($dateRange === 'this_month') {
    $filterSubtitleParts[] = "Period: This Month (" . date('F Y') . ")";
} elseif ($dateRange === 'last_month') {
    $filterSubtitleParts[] = "Period: Last Month (" . date('F Y', strtotime('first day of last month')) . ")";
} elseif ($dateRange === 'this_year') {
    $filterSubtitleParts[] = "Period: Year " . date('Y');
} elseif ($dateRange === 'custom' && $customStart && $customEnd) {
    $filterSubtitleParts[] = "Period: " . date('d-m-Y', strtotime($customStart)) . " to " . date('d-m-Y', strtotime($customEnd));
} else {
    $filterSubtitleParts[] = "Period: All Time";
}

if (!empty($state)) $filterSubtitleParts[] = "State: " . $state;
if (!empty($district)) $filterSubtitleParts[] = "District: " . $district;
if (!empty($assistanceType) && $assistanceType !== 'all') $filterSubtitleParts[] = "Aid Type: " . $assistanceType;

function buildDateFilterPdf(string $columnName, string $dateRange, string $customStart, string $customEnd, array &$params): string {
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

class BeneficiaryReportPDF extends FPDF {
    public $settings;
    public $filterSubtitle;
    public $reportTitle;

    function __construct($settings, $filterSubtitle, $reportTitle = 'Beneficiary & Welfare Aid Report') {
        parent::__construct('L', 'mm', 'A4');
        $this->settings = $settings;
        $this->filterSubtitle = $filterSubtitle;
        $this->reportTitle = $reportTitle;
        $this->SetAutoPageBreak(true, 15);
    }

    function Header() {
        // Teal Header Banner
        $this->SetFillColor(15, 139, 141);
        $this->Rect(0, 0, 297, 24, 'F');

        // Logo
        if (!empty($this->settings['ngo_logo'])) {
            $logoPath = mm_prepare_image_for_fpdf(__DIR__ . '/../../' . $this->settings['ngo_logo']);
            if ($logoPath && file_exists($logoPath)) {
                $this->Image($logoPath, 10, 3, 18);
            }
        }

        // Title text
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 13);
        $this->SetXY(32, 4);
        $this->Cell(180, 6, strtoupper((string)($this->settings['site_name'] ?? 'NGO SYSTEM')), 0, 1, 'L');

        $this->SetFont('Arial', '', 9);
        $this->SetXY(32, 11);
        $this->Cell(180, 5, $this->reportTitle, 0, 1, 'L');

        $this->SetFont('Arial', 'I', 7.5);
        $this->SetXY(32, 17);
        $this->Cell(250, 4, $this->filterSubtitle . ' | Generated: ' . date('d F, Y H:i'), 0, 1, 'L');

        $this->Ln(8);
    }

    function Footer() {
        $this->SetY(-12);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(120);
        $this->Cell(0, 6, 'Page ' . $this->PageNo() . '/{nb} | Beneficiary Management & Welfare Operations - ' . ($this->settings['site_name'] ?? 'NGO System'), 0, 0, 'C');
    }
}

$filterSubtitleStr = implode(' | ', $filterSubtitleParts);
$pdf = new BeneficiaryReportPDF($settings_data, $filterSubtitleStr, 'Official Beneficiary Analytics & Assistance Distribution Report');
$pdf->AliasNbPages();
$pdf->AddPage();

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
    $sql .= buildDateFilterPdf("COALESCE(b.registration_date, DATE(b.created_at))", $dateRange, $customStart, $customEnd, $params);
    $sql .= " GROUP BY state_name, district_name, block_name ORDER BY beneficiary_count DESC, total_aid_value DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Table Header
    $pdf->SetY(28);
    $pdf->SetFillColor(240, 253, 253);
    $pdf->SetDrawColor(204, 251, 241);
    $pdf->SetTextColor(15, 139, 141);
    $pdf->SetFont('Arial', 'B', 8.5);

    $header = ['#', 'State / UT', 'District', 'Block / Tehsil', 'Beneficiaries', 'Aid Events', 'Cash Aid (INR)', 'In-Kind Value (INR)', 'Total Aid (INR)'];
    $w = [12, 45, 45, 40, 25, 25, 28, 28, 29];

    for ($i = 0; $i < count($header); $i++) {
        $align = ($i === 0 || $i === 4 || $i === 5) ? 'C' : (($i >= 6) ? 'R' : 'L');
        $pdf->Cell($w[$i], 7, $header[$i], 1, 0, $align, true);
    }
    $pdf->Ln();

    // Table Body
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetDrawColor(226, 232, 240);
    $fill = false;

    $totBen = 0; $totEvents = 0; $totCash = 0.0; $totInKind = 0.0; $totVal = 0.0;

    foreach ($rows as $idx => $r) {
        $pdf->SetFillColor($fill ? 248 : 255, $fill ? 250 : 255, $fill ? 252 : 255);
        $pdf->SetTextColor(40);

        $totBen += (int)$r['beneficiary_count'];
        $totEvents += (int)$r['aid_events_count'];
        $totCash += (float)$r['cash_amount'];
        $totInKind += (float)$r['in_kind_value'];
        $totVal += (float)$r['total_aid_value'];

        $pdf->Cell($w[0], 6, (string)($idx + 1), 1, 0, 'C', $fill);
        $pdf->Cell($w[1], 6, substr($r['state_name'], 0, 22), 1, 0, 'L', $fill);
        $pdf->Cell($w[2], 6, substr($r['district_name'], 0, 22), 1, 0, 'L', $fill);
        $pdf->Cell($w[3], 6, substr($r['block_name'], 0, 20), 1, 0, 'L', $fill);
        $pdf->Cell($w[4], 6, number_format($r['beneficiary_count']), 1, 0, 'C', $fill);
        $pdf->Cell($w[5], 6, number_format($r['aid_events_count']), 1, 0, 'C', $fill);
        $pdf->Cell($w[6], 6, number_format($r['cash_amount'], 2), 1, 0, 'R', $fill);
        $pdf->Cell($w[7], 6, number_format($r['in_kind_value'], 2), 1, 0, 'R', $fill);
        $pdf->Cell($w[8], 6, number_format($r['total_aid_value'], 2), 1, 0, 'R', $fill);
        $pdf->Ln();
        $fill = !$fill;
    }

    // Totals Row
    $pdf->SetFont('Arial', 'B', 8.5);
    $pdf->SetFillColor(241, 245, 249);
    $pdf->SetTextColor(15, 23, 42);
    $pdf->Cell($w[0] + $w[1] + $w[2] + $w[3], 7, 'TOTAL SUMMARY', 1, 0, 'C', true);
    $pdf->Cell($w[4], 7, number_format($totBen), 1, 0, 'C', true);
    $pdf->Cell($w[5], 7, number_format($totEvents), 1, 0, 'C', true);
    $pdf->Cell($w[6], 7, number_format($totCash, 2), 1, 0, 'R', true);
    $pdf->Cell($w[7], 7, number_format($totInKind, 2), 1, 0, 'R', true);
    $pdf->Cell($w[8], 7, number_format($totVal, 2), 1, 0, 'R', true);
    $pdf->Ln();

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
    $sql .= buildDateFilterPdf("ast.date", $dateRange, $customStart, $customEnd, $params);
    $sql .= " GROUP BY ast.assistance_type ORDER BY total_valuation DESC, distribution_count DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Table Header
    $pdf->SetY(28);
    $pdf->SetFillColor(240, 253, 253);
    $pdf->SetDrawColor(204, 251, 241);
    $pdf->SetTextColor(15, 139, 141);
    $pdf->SetFont('Arial', 'B', 8.5);

    $header = ['#', 'Assistance Type / Category', 'Aid Events', 'Beneficiaries Served', 'Cash Support (INR)', 'In-Kind Value (INR)', 'Total Valuation (INR)', 'Total Qty', 'Units'];
    $w = [12, 65, 25, 32, 32, 32, 35, 22, 22];

    for ($i = 0; $i < count($header); $i++) {
        $align = ($i === 0 || $i === 2 || $i === 3 || $i === 7) ? 'C' : (($i >= 4 && $i <= 6) ? 'R' : 'L');
        $pdf->Cell($w[$i], 7, $header[$i], 1, 0, $align, true);
    }
    $pdf->Ln();

    // Table Body
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetDrawColor(226, 232, 240);
    $fill = false;

    $totDist = 0; $totBenServed = 0; $totCash = 0.0; $totInKind = 0.0; $totVal = 0.0; $totQty = 0.0;

    foreach ($rows as $idx => $r) {
        $pdf->SetFillColor($fill ? 248 : 255, $fill ? 250 : 255, $fill ? 252 : 255);
        $pdf->SetTextColor(40);

        $totDist += (int)$r['distribution_count'];
        $totBenServed += (int)$r['beneficiaries_served'];
        $totCash += (float)$r['total_cash'];
        $totInKind += (float)$r['total_in_kind'];
        $totVal += (float)$r['total_valuation'];
        $totQty += (float)$r['total_quantity'];

        $pdf->Cell($w[0], 6, (string)($idx + 1), 1, 0, 'C', $fill);
        $pdf->Cell($w[1], 6, substr($r['assistance_type'], 0, 35), 1, 0, 'L', $fill);
        $pdf->Cell($w[2], 6, number_format($r['distribution_count']), 1, 0, 'C', $fill);
        $pdf->Cell($w[3], 6, number_format($r['beneficiaries_served']), 1, 0, 'C', $fill);
        $pdf->Cell($w[4], 6, number_format($r['total_cash'], 2), 1, 0, 'R', $fill);
        $pdf->Cell($w[5], 6, number_format($r['total_in_kind'], 2), 1, 0, 'R', $fill);
        $pdf->Cell($w[6], 6, number_format($r['total_valuation'], 2), 1, 0, 'R', $fill);
        $pdf->Cell($w[7], 6, number_format($r['total_quantity'], 1), 1, 0, 'C', $fill);
        $pdf->Cell($w[8], 6, substr($r['units_used'] ?: 'units', 0, 12), 1, 0, 'L', $fill);
        $pdf->Ln();
        $fill = !$fill;
    }

    // Totals Row
    $pdf->SetFont('Arial', 'B', 8.5);
    $pdf->SetFillColor(241, 245, 249);
    $pdf->SetTextColor(15, 23, 42);
    $pdf->Cell($w[0] + $w[1], 7, 'TOTAL AGGREGATE', 1, 0, 'C', true);
    $pdf->Cell($w[2], 7, number_format($totDist), 1, 0, 'C', true);
    $pdf->Cell($w[3], 7, number_format($totBenServed), 1, 0, 'C', true);
    $pdf->Cell($w[4], 7, number_format($totCash, 2), 1, 0, 'R', true);
    $pdf->Cell($w[5], 7, number_format($totInKind, 2), 1, 0, 'R', true);
    $pdf->Cell($w[6], 7, number_format($totVal, 2), 1, 0, 'R', true);
    $pdf->Cell($w[7], 7, number_format($totQty, 1), 1, 0, 'C', true);
    $pdf->Cell($w[8], 7, '-', 1, 0, 'C', true);
    $pdf->Ln();

// ── 3. DETAILED BENEFICIARIES ROSTER ──────────────────────────
} elseif ($reportMode === 'beneficiaries_list') {
    $sql = "SELECT 
        b.id, b.beneficiary_code, b.name, b.father_husband_name, b.contact,
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
    $sql .= buildDateFilterPdf("COALESCE(b.registration_date, DATE(b.created_at))", $dateRange, $customStart, $customEnd, $params);
    $sql .= " GROUP BY b.id, b.beneficiary_code, b.name, b.father_husband_name, b.contact, b.state, b.district, b.block, b.village_ward, b.status, b.registration_date, c.category_name, u.name ORDER BY b.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Table Header
    $pdf->SetY(28);
    $pdf->SetFillColor(240, 253, 253);
    $pdf->SetDrawColor(204, 251, 241);
    $pdf->SetTextColor(15, 139, 141);
    $pdf->SetFont('Arial', 'B', 8);

    $header = ['#', 'Code', 'Full Name', 'Contact', 'Category', 'State', 'District', 'Block', 'Status', 'Aid Count', 'Total Aid (INR)'];
    $w = [10, 24, 45, 25, 30, 30, 30, 28, 18, 16, 21];

    for ($i = 0; $i < count($header); $i++) {
        $align = ($i === 0 || $i === 1 || $i === 8 || $i === 9) ? 'C' : (($i === 10) ? 'R' : 'L');
        $pdf->Cell($w[$i], 7, $header[$i], 1, 0, $align, true);
    }
    $pdf->Ln();

    $pdf->SetFont('Arial', '', 7.5);
    $pdf->SetDrawColor(226, 232, 240);
    $fill = false;
    $totAidReceived = 0.0;

    foreach ($rows as $idx => $r) {
        $pdf->SetFillColor($fill ? 248 : 255, $fill ? 250 : 255, $fill ? 252 : 255);
        $pdf->SetTextColor(40);
        $totAidReceived += (float)$r['total_aid_received'];

        $pdf->Cell($w[0], 6, (string)($idx + 1), 1, 0, 'C', $fill);
        $pdf->Cell($w[1], 6, substr($r['beneficiary_code'] ?: ('BEN-'.$r['id']), 0, 12), 1, 0, 'C', $fill);
        $pdf->Cell($w[2], 6, substr($r['name'], 0, 24), 1, 0, 'L', $fill);
        $pdf->Cell($w[3], 6, substr($r['contact'] ?: '-', 0, 12), 1, 0, 'L', $fill);
        $pdf->Cell($w[4], 6, substr($r['category_name'] ?: 'General', 0, 16), 1, 0, 'L', $fill);
        $pdf->Cell($w[5], 6, substr($r['state'] ?: '-', 0, 15), 1, 0, 'L', $fill);
        $pdf->Cell($w[6], 6, substr($r['district'] ?: '-', 0, 15), 1, 0, 'L', $fill);
        $pdf->Cell($w[7], 6, substr($r['block'] ?: '-', 0, 14), 1, 0, 'L', $fill);
        $pdf->Cell($w[8], 6, substr($r['status'] ?: 'Active', 0, 10), 1, 0, 'C', $fill);
        $pdf->Cell($w[9], 6, (string)$r['assistance_count'], 1, 0, 'C', $fill);
        $pdf->Cell($w[10], 6, number_format($r['total_aid_received'], 2), 1, 0, 'R', $fill);
        $pdf->Ln();
        $fill = !$fill;
    }

    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetFillColor(241, 245, 249);
    $pdf->Cell(array_sum(array_slice($w, 0, 10)), 7, 'TOTAL BENEFICIARIES (' . count($rows) . ')', 1, 0, 'C', true);
    $pdf->Cell($w[10], 7, number_format($totAidReceived, 2), 1, 0, 'R', true);
    $pdf->Ln();

// ── 4. DETAILED ASSISTANCE HANDOVER LOG ────────────────────────
} else {
    $sql = "SELECT 
        ast.id, ast.assistance_code, ast.date, ast.assistance_type, ast.description,
        ast.amount, ast.estimated_value, (ast.amount + ast.estimated_value) as total_value,
        ast.quantity, ast.unit, ast.distribution_location, ast.given_by, ast.receipt_no, ast.status,
        b.name as beneficiary_name, b.beneficiary_code, b.district, b.state, c.category_name
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
    $sql .= buildDateFilterPdf("ast.date", $dateRange, $customStart, $customEnd, $params);
    $sql .= " ORDER BY ast.date DESC, ast.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Table Header
    $pdf->SetY(28);
    $pdf->SetFillColor(240, 253, 253);
    $pdf->SetDrawColor(204, 251, 241);
    $pdf->SetTextColor(15, 139, 141);
    $pdf->SetFont('Arial', 'B', 8);

    $header = ['#', 'Date', 'Voucher / Code', 'Beneficiary', 'Location', 'Assistance Type', 'Particulars', 'Cash (INR)', 'In-Kind (INR)', 'Total (INR)'];
    $w = [10, 20, 28, 40, 35, 38, 50, 18, 18, 20];

    for ($i = 0; $i < count($header); $i++) {
        $align = ($i === 0 || $i === 1 || $i === 2) ? 'C' : (($i >= 7) ? 'R' : 'L');
        $pdf->Cell($w[$i], 7, $header[$i], 1, 0, $align, true);
    }
    $pdf->Ln();

    $pdf->SetFont('Arial', '', 7.5);
    $pdf->SetDrawColor(226, 232, 240);
    $fill = false;
    $totCash = 0.0; $totInKind = 0.0; $totVal = 0.0;

    foreach ($rows as $idx => $r) {
        $pdf->SetFillColor($fill ? 248 : 255, $fill ? 250 : 255, $fill ? 252 : 255);
        $pdf->SetTextColor(40);
        $totCash += (float)$r['amount'];
        $totInKind += (float)$r['estimated_value'];
        $totVal += (float)$r['total_value'];

        $dateStr = date('d-m-Y', strtotime($r['date']));
        $codeStr = $r['assistance_code'] ?: ($r['receipt_no'] ?: ('AID-'.$r['id']));
        $locStr = ($r['district'] ?: '') . ', ' . ($r['state'] ?: '');

        $pdf->Cell($w[0], 6, (string)($idx + 1), 1, 0, 'C', $fill);
        $pdf->Cell($w[1], 6, $dateStr, 1, 0, 'C', $fill);
        $pdf->Cell($w[2], 6, substr($codeStr, 0, 15), 1, 0, 'C', $fill);
        $pdf->Cell($w[3], 6, substr($r['beneficiary_name'], 0, 22), 1, 0, 'L', $fill);
        $pdf->Cell($w[4], 6, substr($locStr, 0, 20), 1, 0, 'L', $fill);
        $pdf->Cell($w[5], 6, substr($r['assistance_type'], 0, 20), 1, 0, 'L', $fill);
        $pdf->Cell($w[6], 6, substr($r['description'], 0, 30), 1, 0, 'L', $fill);
        $pdf->Cell($w[7], 6, number_format($r['amount'], 2), 1, 0, 'R', $fill);
        $pdf->Cell($w[8], 6, number_format($r['estimated_value'], 2), 1, 0, 'R', $fill);
        $pdf->Cell($w[9], 6, number_format($r['total_value'], 2), 1, 0, 'R', $fill);
        $pdf->Ln();
        $fill = !$fill;
    }

    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetFillColor(241, 245, 249);
    $pdf->Cell(array_sum(array_slice($w, 0, 7)), 7, 'TOTAL DISBURSEMENTS (' . count($rows) . ' Handover Events)', 1, 0, 'C', true);
    $pdf->Cell($w[7], 7, number_format($totCash, 2), 1, 0, 'R', true);
    $pdf->Cell($w[8], 7, number_format($totInKind, 2), 1, 0, 'R', true);
    $pdf->Cell($w[9], 7, number_format($totVal, 2), 1, 0, 'R', true);
    $pdf->Ln();
}

$pdf->Output('I', 'beneficiary_report_' . $reportMode . '_' . date('Y-m-d') . '.pdf');
