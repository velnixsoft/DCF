<?php
// ============================================================
// admin/actions/export_income_expense_pdf.php
// Exports Income vs Expense Comparative Financial Statement as PDF via FPDF
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

$viewMode = cleanInput($_GET['view_mode'] ?? 'monthly');
$selectedYear = cleanInput($_GET['year'] ?? date('Y'));
$projectId = filter_input(INPUT_GET, 'project_id', FILTER_VALIDATE_INT) ?: null;
$expenseStatus = cleanInput($_GET['expense_status'] ?? 'approved_paid');
$customStart = cleanInput($_GET['custom_start'] ?? '');
$customEnd = cleanInput($_GET['custom_end'] ?? '');

$settings_data = $pdo->query("SELECT * FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);

$expStatusClause = "";
if ($expenseStatus === 'approved_paid') {
    $expStatusClause = " AND e.approved_status IN ('Approved', 'Paid')";
} elseif ($expenseStatus === 'approved_only') {
    $expStatusClause = " AND e.approved_status = 'Approved'";
} elseif ($expenseStatus === 'paid_only') {
    $expStatusClause = " AND e.approved_status = 'Paid'";
}

$comparisonRows = [];
$totIncome = 0.0;
$totExpense = 0.0;

if ($viewMode === 'monthly') {
    $targetYear = ($selectedYear && $selectedYear !== 'all') ? (int)$selectedYear : (int)date('Y');
    
    $mIncSql = "SELECT MONTH(created_at) as m_num, COALESCE(SUM(amount), 0) as m_income FROM donations WHERE payment_status = 'Success' AND YEAR(created_at) = ?";
    $mIncParams = [$targetYear];
    if ($projectId) { $mIncSql .= " AND project_id = ?"; $mIncParams[] = $projectId; }
    $mIncSql .= " GROUP BY MONTH(created_at)";
    $mIncStmt = $pdo->prepare($mIncSql);
    $mIncStmt->execute($mIncParams);
    $incMap = $mIncStmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $mExpSql = "SELECT MONTH(date) as m_num, COALESCE(SUM(amount), 0) as m_expense FROM expenses e WHERE 1=1 {$expStatusClause} AND YEAR(date) = ?";
    $mExpParams = [$targetYear];
    if ($projectId) { $mExpSql .= " AND e.project_id = ?"; $mExpParams[] = $projectId; }
    $mExpSql .= " GROUP BY MONTH(date)";
    $mExpStmt = $pdo->prepare($mExpSql);
    $mExpStmt->execute($mExpParams);
    $expMap = $mExpStmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    for ($m = 1; $m <= 12; $m++) {
        $inc = (float)($incMap[$m] ?? 0);
        $exp = (float)($expMap[$m] ?? 0);
        $net = $inc - $exp;
        $totIncome += $inc;
        $totExpense += $exp;
        $comparisonRows[] = [
            'period' => $monthNames[$m - 1] . ' ' . $targetYear,
            'income' => $inc,
            'expense' => $exp,
            'net' => $net,
            'margin' => ($inc > 0) ? round(($net / $inc) * 100, 1) : (($exp > 0) ? -100.0 : 0.0),
            'status' => ($net >= 0) ? 'Surplus' : 'Deficit'
        ];
    }
} else {
    $yearsSql = "SELECT DISTINCT yr FROM (
        SELECT YEAR(created_at) as yr FROM donations WHERE payment_status = 'Success'
        UNION 
        SELECT YEAR(date) as yr FROM expenses WHERE 1=1 {$expStatusClause}
    ) all_years ORDER BY yr ASC";
    $years = $pdo->query($yearsSql)->fetchAll(PDO::FETCH_COLUMN) ?: [(int)date('Y')];

    $yIncSql = "SELECT YEAR(created_at) as yr, COALESCE(SUM(amount), 0) as y_income FROM donations WHERE payment_status = 'Success' GROUP BY YEAR(created_at)";
    $yIncMap = $pdo->query($yIncSql)->fetchAll(PDO::FETCH_KEY_PAIR);

    $yExpSql = "SELECT YEAR(date) as yr, COALESCE(SUM(amount), 0) as y_expense FROM expenses e WHERE 1=1 {$expStatusClause} GROUP BY YEAR(date)";
    $yExpMap = $pdo->query($yExpSql)->fetchAll(PDO::FETCH_KEY_PAIR);

    foreach ($years as $yr) {
        $inc = (float)($yIncMap[$yr] ?? 0);
        $exp = (float)($yExpMap[$yr] ?? 0);
        $net = $inc - $exp;
        $totIncome += $inc;
        $totExpense += $exp;
        $comparisonRows[] = [
            'period' => 'Year ' . $yr,
            'income' => $inc,
            'expense' => $exp,
            'net' => $net,
            'margin' => ($inc > 0) ? round(($net / $inc) * 100, 1) : (($exp > 0) ? -100.0 : 0.0),
            'status' => ($net >= 0) ? 'Surplus' : 'Deficit'
        ];
    }
}

$netTotal = $totIncome - $totExpense;

// Filter Subtitle
$filterSub = "Mode: " . ucfirst($viewMode);
if ($viewMode === 'monthly') $filterSub .= " | Year: " . ($selectedYear ?: date('Y'));
$filterSub .= " | Status: " . ucwords(str_replace('_', ' ', $expenseStatus));

class IncomeExpenseReportPDF extends FPDF {
    public $settings;
    public $filterSubtitle;

    function __construct($settings, $filterSubtitle) {
        parent::__construct('L', 'mm', 'A4');
        $this->settings = $settings;
        $this->filterSubtitle = $filterSubtitle;
        $this->SetAutoPageBreak(true, 15);
    }

    function Header() {
        $this->SetFillColor(15, 139, 141);
        $this->Rect(0, 0, 297, 24, 'F');

        if (!empty($this->settings['ngo_logo'])) {
            $logoPath = mm_prepare_image_for_fpdf(__DIR__ . '/../../' . $this->settings['ngo_logo']);
            if ($logoPath && file_exists($logoPath)) {
                $this->Image($logoPath, 10, 3, 18);
            }
        }

        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 13);
        $this->SetXY(32, 4);
        $this->Cell(180, 6, strtoupper((string)($this->settings['site_name'] ?? 'NGO SYSTEM')), 0, 1, 'L');

        $this->SetFont('Arial', '', 9);
        $this->SetXY(32, 11);
        $this->Cell(180, 5, 'Financial Statement: Income (Donations) vs Operational Expenses Comparison', 0, 1, 'L');

        $this->SetFont('Arial', 'I', 7.5);
        $this->SetXY(32, 17);
        $this->Cell(250, 4, $this->filterSubtitle . ' | Generated: ' . date('d F, Y H:i'), 0, 1, 'L');

        $this->Ln(8);
    }

    function Footer() {
        $this->SetY(-12);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(120);
        $this->Cell(0, 6, 'Page ' . $this->PageNo() . '/{nb} | Financial Accountability & Audit - ' . ($this->settings['site_name'] ?? 'NGO System'), 0, 0, 'C');
    }
}

$pdf = new IncomeExpenseReportPDF($settings_data, $filterSub);
$pdf->AliasNbPages();
$pdf->AddPage();

// ── Summary KPI Strip ─────────────────────────────────────────
$pdf->SetY(28);
$pdf->SetFont('Arial', 'B', 8.5);
$pdf->SetFillColor(248, 250, 252);
$pdf->SetDrawColor(203, 213, 225);
$pdf->SetTextColor(15, 23, 42);

$pdf->Cell(65, 8, 'Total Income (Donations): INR ' . number_format($totIncome, 2), 1, 0, 'L', true);
$pdf->Cell(65, 8, 'Total Operating Expenses: INR ' . number_format($totExpense, 2), 1, 0, 'L', true);
$statusText = ($netTotal >= 0) ? 'SURPLUS' : 'DEFICIT';
$pdf->Cell(85, 8, 'Net Cashflow: INR ' . number_format($netTotal, 2) . ' (' . $statusText . ')', 1, 0, 'L', true);
$opRatio = ($totIncome > 0) ? round(($totExpense / $totIncome) * 100, 1) : 0;
$pdf->Cell(62, 8, 'Operating Expense Ratio: ' . $opRatio . '%', 1, 1, 'L', true);
$pdf->Ln(3);

// ── Comparison Table ──────────────────────────────────────────
$pdf->SetFillColor(240, 253, 253);
$pdf->SetDrawColor(204, 251, 241);
$pdf->SetTextColor(15, 139, 141);
$pdf->SetFont('Arial', 'B', 8.5);

$header = ['#', 'Period', 'Total Income / Donations (INR)', 'Operating Expenses (INR)', 'Net Balance / Cashflow (INR)', 'Net Margin %', 'Financial Status'];
$w = [12, 60, 50, 50, 50, 28, 27];

for ($i = 0; $i < count($header); $i++) {
    $align = ($i === 0 || $i === 5 || $i === 6) ? 'C' : (($i >= 2 && $i <= 4) ? 'R' : 'L');
    $pdf->Cell($w[$i], 7, $header[$i], 1, 0, $align, true);
}
$pdf->Ln();

// Table Body
$pdf->SetFont('Arial', '', 8);
$pdf->SetDrawColor(226, 232, 240);
$fill = false;

foreach ($comparisonRows as $idx => $r) {
    $pdf->SetFillColor($fill ? 248 : 255, $fill ? 250 : 255, $fill ? 252 : 255);
    $pdf->SetTextColor(40);

    $pdf->Cell($w[0], 6, (string)($idx + 1), 1, 0, 'C', $fill);
    $pdf->Cell($w[1], 6, $r['period'], 1, 0, 'L', $fill);
    $pdf->Cell($w[2], 6, number_format($r['income'], 2), 1, 0, 'R', $fill);
    $pdf->Cell($w[3], 6, number_format($r['expense'], 2), 1, 0, 'R', $fill);
    $pdf->Cell($w[4], 6, number_format($r['net'], 2), 1, 0, 'R', $fill);
    $pdf->Cell($w[5], 6, $r['margin'] . '%', 1, 0, 'C', $fill);
    $pdf->Cell($w[6], 6, $r['status'], 1, 0, 'C', $fill);
    $pdf->Ln();
    $fill = !$fill;
}

// Totals Row
$pdf->SetFont('Arial', 'B', 8.5);
$pdf->SetFillColor(241, 245, 249);
$pdf->SetTextColor(15, 23, 42);
$pdf->Cell($w[0] + $w[1], 7, 'CONSOLIDATED TOTAL', 1, 0, 'C', true);
$pdf->Cell($w[2], 7, number_format($totIncome, 2), 1, 0, 'R', true);
$pdf->Cell($w[3], 7, number_format($totExpense, 2), 1, 0, 'R', true);
$pdf->Cell($w[4], 7, number_format($netTotal, 2), 1, 0, 'R', true);
$pdf->Cell($w[5], 7, (($totIncome > 0) ? round(($netTotal / $totIncome) * 100, 1) : 0) . '%', 1, 0, 'C', true);
$pdf->Cell($w[6], 7, ($netTotal >= 0 ? 'SURPLUS' : 'DEFICIT'), 1, 0, 'C', true);
$pdf->Ln(6);

$pdf->Output('I', 'income_vs_expense_report_' . $viewMode . '_' . date('Y-m-d') . '.pdf');
