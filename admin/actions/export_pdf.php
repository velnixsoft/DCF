<?php
// ============================================================
// admin/actions/export_pdf.php
// Exports Item Donation or Monetary Donation reports as PDF
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

$reportType = cleanInput($_GET['report_type'] ?? 'all_donations');
$dateRange = cleanInput($_GET['date_range'] ?? 'this_month');
$projectId = filter_input(INPUT_GET, 'project_id', FILTER_VALIDATE_INT) ?: null;
$categoryId = filter_input(INPUT_GET, 'category_id', FILTER_VALIDATE_INT) ?: null;
$status = cleanInput($_GET['status'] ?? '');
$customStart = cleanInput($_GET['custom_start'] ?? '');
$customEnd = cleanInput($_GET['custom_end'] ?? '');

$settings_data = $pdo->query("SELECT * FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);

// ── 1. ITEM DONATION PDF EXPORT ──────────────────────────────
if ($reportType === 'item_donations' || $reportType === 'item_category_wise' || strpos($reportType, 'item_') === 0) {
    $sql = "
        SELECT i.*, c.category_name, p.title AS project_name 
        FROM item_donations i 
        LEFT JOIN item_donation_categories c ON i.category_id = c.id 
        LEFT JOIN projects p ON i.project_id = p.id 
        WHERE 1=1
    ";
    $params = [];

    $filterSubtitleParts = [];

    if (!empty($categoryId)) {
        $sql .= " AND i.category_id = ?";
        $params[] = $categoryId;
        $catName = (string)($pdo->query("SELECT category_name FROM item_donation_categories WHERE id = " . (int)$categoryId)->fetchColumn() ?: 'Category #' . $categoryId);
        $filterSubtitleParts[] = "Category: " . $catName;
    } else {
        $filterSubtitleParts[] = "Category: All Categories";
    }

    if (!empty($status) && $status !== 'all') {
        $sql .= " AND i.status = ?";
        $params[] = $status;
        $filterSubtitleParts[] = "Status: " . ucfirst($status);
    }

    $dateCol = "COALESCE(i.donation_date, DATE(i.created_at))";
    $now = new DateTime();

    if ($dateRange === 'this_month') {
        $sql .= " AND MONTH({$dateCol}) = ? AND YEAR({$dateCol}) = ?";
        $params[] = $now->format('m');
        $params[] = $now->format('Y');
        $filterSubtitleParts[] = "Period: This Month (" . $now->format('F Y') . ")";
    } elseif ($dateRange === 'last_month') {
        $now->modify('first day of last month');
        $sql .= " AND MONTH({$dateCol}) = ? AND YEAR({$dateCol}) = ?";
        $params[] = $now->format('m');
        $params[] = $now->format('Y');
        $filterSubtitleParts[] = "Period: Last Month (" . $now->format('F Y') . ")";
    } elseif ($dateRange === 'this_year') {
        $sql .= " AND YEAR({$dateCol}) = ?";
        $params[] = $now->format('Y');
        $filterSubtitleParts[] = "Period: Year " . $now->format('Y');
    } elseif ($dateRange === 'this_quarter') {
        $month = (int)$now->format('n');
        if ($month <= 3) { $start = '01'; $end = '03'; $q = 'Q1'; }
        elseif ($month <= 6) { $start = '04'; $end = '06'; $q = 'Q2'; }
        elseif ($month <= 9) { $start = '07'; $end = '09'; $q = 'Q3'; }
        else { $start = '10'; $end = '12'; $q = 'Q4'; }
        $sql .= " AND MONTH({$dateCol}) BETWEEN ? AND ? AND YEAR({$dateCol}) = ?";
        $params[] = $start;
        $params[] = $end;
        $params[] = $now->format('Y');
        $filterSubtitleParts[] = "Period: " . $q . " " . $now->format('Y');
    } elseif ($dateRange === 'custom' && $customStart && $customEnd) {
        $sql .= " AND DATE({$dateCol}) BETWEEN ? AND ?";
        $params[] = $customStart;
        $params[] = $customEnd;
        $filterSubtitleParts[] = "Period: " . date('d-m-Y', strtotime($customStart)) . " to " . date('d-m-Y', strtotime($customEnd));
    } else {
        $filterSubtitleParts[] = "Period: All Time";
    }

    $sql .= " ORDER BY i.donation_date DESC, i.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    class ItemReportPDF extends FPDF {
        public $settings;
        public $filterSubtitle;

        function __construct($settings, $filterSubtitle) {
            parent::__construct('L', 'mm', 'A4');
            $this->settings = $settings;
            $this->filterSubtitle = $filterSubtitle;
            $this->SetAutoPageBreak(true, 15);
        }

        function Header() {
            // Background Header Box
            $this->SetFillColor(15, 139, 141);
            $this->Rect(0, 0, 297, 24, 'F');

            // Logo
            if (!empty($this->settings['ngo_logo'])) {
                $logoPath = mm_prepare_image_for_fpdf(__DIR__ . '/../../' . $this->settings['ngo_logo']);
                if ($logoPath && file_exists($logoPath)) {
                    $this->Image($logoPath, 10, 3, 18);
                }
            }

            // Title
            $this->SetTextColor(255, 255, 255);
            $this->SetFont('Arial', 'B', 14);
            $this->SetXY(32, 4);
            $this->Cell(180, 6, strtoupper((string)($this->settings['site_name'] ?? 'NGO SYSTEM')), 0, 1, 'L');

            $this->SetFont('Arial', '', 9);
            $this->SetXY(32, 11);
            $this->Cell(180, 5, 'Official In-Kind Item Donation Report (Category-wise & Date-wise)', 0, 1, 'L');

            $this->SetFont('Arial', 'I', 7.5);
            $this->SetXY(32, 17);
            $this->Cell(180, 4, $this->filterSubtitle . ' | Generated: ' . date('d F, Y H:i'), 0, 1, 'L');

            $this->Ln(8);
        }

        function Footer() {
            $this->SetY(-12);
            $this->SetFont('Arial', 'I', 8);
            $this->SetTextColor(120);
            $this->Cell(0, 6, 'Page ' . $this->PageNo() . '/{nb} | Confirmed Records from ' . ($this->settings['site_name'] ?? 'NGO System'), 0, 0, 'C');
        }
    }

    $filterSubtitleStr = implode(' | ', $filterSubtitleParts);
    $pdf = new ItemReportPDF($settings_data, $filterSubtitleStr);
    $pdf->AliasNbPages();
    $pdf->AddPage();

    // Table Header
    $pdf->SetY(28);
    $pdf->SetFillColor(240, 253, 253);
    $pdf->SetDrawColor(204, 251, 241);
    $pdf->SetTextColor(15, 139, 141);
    $pdf->SetFont('Arial', 'B', 8.5);

    $header = ['#', 'Date', 'Receipt No', 'Donor Name', 'Category', 'Item Description', 'Qty / Unit', 'Est. Value', 'Status'];
    $w = [12, 22, 35, 45, 35, 65, 25, 25, 15];

    for ($i = 0; $i < count($header); $i++) {
        $align = ($i === 0 || $i === 1 || $i === 6 || $i === 8) ? 'C' : (($i === 7) ? 'R' : 'L');
        $pdf->Cell($w[$i], 7, $header[$i], 1, 0, $align, true);
    }
    $pdf->Ln();

    // Table Body
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetDrawColor(226, 232, 240);
    $fill = false;

    $totalVal = 0.0;
    $totalQty = 0.0;

    foreach ($items as $idx => $row) {
        $pdf->SetFillColor($fill ? 248 : 255, $fill ? 250 : 255, $fill ? 252 : 255);
        $pdf->SetTextColor(40);

        $val = (float)($row['estimated_value'] ?? 0);
        $qty = (float)($row['quantity'] ?? 0);
        $totalVal += $val;
        $totalQty += $qty;

        $donorText = strlen($row['donor_name']) > 22 ? substr($row['donor_name'], 0, 22) . '...' : $row['donor_name'];
        $catText = strlen($row['category_name'] ?: 'Essential Goods') > 18 ? substr($row['category_name'] ?: 'Essential Goods', 0, 18) . '...' : ($row['category_name'] ?: 'Essential Goods');
        $descText = strlen($row['item_description']) > 35 ? substr($row['item_description'], 0, 35) . '...' : $row['item_description'];
        $qtyText = number_format($qty, 1) . ' ' . $row['unit'];
        $valText = ($val > 0) ? ('INR ' . number_format($val)) : 'In-Kind';
        $rcpText = $row['receipt_no'] ?: ('RCP-ITM-' . $row['id']);
        $dateText = !empty($row['donation_date']) ? date('d-m-Y', strtotime($row['donation_date'])) : date('d-m-Y', strtotime($row['created_at']));

        $pdf->Cell($w[0], 6, (string)($idx + 1), 1, 0, 'C', $fill);
        $pdf->Cell($w[1], 6, $dateText, 1, 0, 'C', $fill);
        $pdf->Cell($w[2], 6, $rcpText, 1, 0, 'L', $fill);
        $pdf->Cell($w[3], 6, $donorText, 1, 0, 'L', $fill);
        $pdf->Cell($w[4], 6, $catText, 1, 0, 'L', $fill);
        $pdf->Cell($w[5], 6, $descText, 1, 0, 'L', $fill);
        $pdf->Cell($w[6], 6, $qtyText, 1, 0, 'C', $fill);
        $pdf->Cell($w[7], 6, $valText, 1, 0, 'R', $fill);
        $pdf->Cell($w[8], 6, substr($row['status'], 0, 8), 1, 0, 'C', $fill);
        $pdf->Ln();

        $fill = !$fill;
    }

    // Totals Row
    $pdf->SetFont('Arial', 'B', 8.5);
    $pdf->SetFillColor(241, 245, 249);
    $pdf->SetTextColor(15, 139, 141);
    $pdf->Cell($w[0] + $w[1] + $w[2] + $w[3] + $w[4] + $w[5], 7, 'Total Aggregate Summary (' . count($items) . ' items):', 1, 0, 'R', true);
    $pdf->Cell($w[6], 7, number_format($totalQty, 1), 1, 0, 'C', true);
    $pdf->Cell($w[7], 7, 'INR ' . number_format($totalVal, 2), 1, 0, 'R', true);
    $pdf->Cell($w[8], 7, '', 1, 1, 'C', true);

    $pdf->Output('D', 'Item_Donation_Report_' . date('Y-m-d') . '.pdf');
    exit;
}

// ── 2. MONETARY DONATION PDF EXPORT ──────────────────────────
$sql = "SELECT d.*, p.title as project_name FROM donations d LEFT JOIN projects p ON d.project_id = p.id WHERE d.payment_status = 'Success'";
$params = [];
$filterSubtitleParts = [];

if ($reportType === '80g_donations') {
    $sql .= " AND d.is_80g_eligible = 1";
    $filterSubtitleParts[] = "Type: 80G Tax Eligible";
} elseif ($reportType === 'project_wise' && $projectId) {
    $sql .= " AND d.project_id = ?";
    $params[] = $projectId;
    $pTitle = (string)($pdo->query("SELECT title FROM projects WHERE id = " . (int)$projectId)->fetchColumn() ?: 'Project #' . $projectId);
    $filterSubtitleParts[] = "Project: " . $pTitle;
} else {
    $filterSubtitleParts[] = "Type: All Monetary Donations";
}

$now = new DateTime();
if ($dateRange === 'this_month') {
    $sql .= " AND MONTH(d.created_at) = ? AND YEAR(d.created_at) = ?";
    $params[] = $now->format('m');
    $params[] = $now->format('Y');
    $filterSubtitleParts[] = "Period: This Month (" . $now->format('F Y') . ")";
} elseif ($dateRange === 'last_month') {
    $now->modify('first day of last month');
    $sql .= " AND MONTH(d.created_at) = ? AND YEAR(d.created_at) = ?";
    $params[] = $now->format('m');
    $params[] = $now->format('Y');
    $filterSubtitleParts[] = "Period: Last Month (" . $now->format('F Y') . ")";
} elseif ($dateRange === 'this_year') {
    $sql .= " AND YEAR(d.created_at) = ?";
    $params[] = $now->format('Y');
    $filterSubtitleParts[] = "Period: Year " . $now->format('Y');
} elseif ($dateRange === 'custom' && $customStart && $customEnd) {
    $sql .= " AND DATE(d.created_at) BETWEEN ? AND ?";
    $params[] = $customStart;
    $params[] = $customEnd;
    $filterSubtitleParts[] = "Period: " . date('d-m-Y', strtotime($customStart)) . " to " . date('d-m-Y', strtotime($customEnd));
} else {
    $filterSubtitleParts[] = "Period: All Time";
}

$sql .= " ORDER BY d.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$donations = $stmt->fetchAll(PDO::FETCH_ASSOC);

class ReportPDF extends FPDF {
    public $settings;
    public $filterSubtitle;

    function __construct($settings, $filterSubtitle = '') {
        parent::__construct('L', 'mm', 'A4');
        $this->settings = $settings;
        $this->filterSubtitle = $filterSubtitle;
        $this->SetAutoPageBreak(true, 15);
    }

    function Header() {
        $this->SetFillColor(24, 49, 115);
        $this->Rect(0, 0, 297, 24, 'F');

        if (!empty($this->settings['ngo_logo'])) {
            $logoPath = mm_prepare_image_for_fpdf(__DIR__ . '/../../' . $this->settings['ngo_logo']);
            if ($logoPath && file_exists($logoPath)) {
                $this->Image($logoPath, 10, 3, 18);
            }
        }

        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 14);
        $this->SetXY(32, 4);
        $this->Cell(180, 6, strtoupper((string)($this->settings['site_name'] ?? 'NGO SYSTEM')), 0, 1, 'L');

        $this->SetFont('Arial', '', 9);
        $this->SetXY(32, 11);
        $this->Cell(180, 5, 'Donation Report (Date-wise & Project-wise)', 0, 1, 'L');

        $this->SetFont('Arial', 'I', 7.5);
        $this->SetXY(32, 17);
        $this->Cell(180, 4, $this->filterSubtitle . ' | Generated: ' . date('d F, Y H:i'), 0, 1, 'L');

        $this->Ln(8);
    }

    function Footer() {
        $this->SetY(-12);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(120);
        $this->Cell(0, 6, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

$pdf = new ReportPDF($settings_data, implode(' | ', $filterSubtitleParts));
$pdf->AliasNbPages();
$pdf->AddPage();

$pdf->SetY(28);
$pdf->SetFillColor(224, 235, 255);
$pdf->SetTextColor(24, 49, 115);
$pdf->SetFont('Arial', 'B', 8.5);

$header = ['#', 'Date', 'Receipt No', 'Donor Name', 'Email', 'PAN', 'Project', 'Amount (INR)'];
$w = [12, 25, 38, 55, 60, 30, 40, 25];

for ($i = 0; $i < count($header); $i++) {
    $align = ($i === 0 || $i === 1) ? 'C' : (($i === 7) ? 'R' : 'L');
    $pdf->Cell($w[$i], 7, $header[$i], 1, 0, $align, true);
}
$pdf->Ln();

$pdf->SetFont('Arial', '', 8);
$pdf->SetDrawColor(220);
$fill = false;
$totalAmt = 0.0;

foreach ($donations as $idx => $row) {
    $pdf->SetFillColor($fill ? 245 : 255, $fill ? 245 : 255, $fill ? 245 : 255);
    $pdf->SetTextColor(40);

    $amt = (float)($row['amount'] ?? 0);
    $totalAmt += $amt;

    $donorName = strlen($row['donor_name']) > 28 ? substr($row['donor_name'], 0, 28) . '...' : $row['donor_name'];
    $email = strlen($row['donor_email']) > 32 ? substr($row['donor_email'], 0, 32) . '...' : $row['donor_email'];
    $proj = strlen($row['project_name'] ?? 'General') > 20 ? substr($row['project_name'] ?? 'General', 0, 20) . '...' : ($row['project_name'] ?? 'General');

    $pdf->Cell($w[0], 6, (string)($idx + 1), 1, 0, 'C', $fill);
    $pdf->Cell($w[1], 6, date('d-m-Y', strtotime($row['created_at'])), 1, 0, 'C', $fill);
    $pdf->Cell($w[2], 6, $row['receipt_no'], 1, 0, 'L', $fill);
    $pdf->Cell($w[3], 6, $donorName, 1, 0, 'L', $fill);
    $pdf->Cell($w[4], 6, $email, 1, 0, 'L', $fill);
    $pdf->Cell($w[5], 6, $row['donor_pan'] ?? '', 1, 0, 'L', $fill);
    $pdf->Cell($w[6], 6, $proj, 1, 0, 'L', $fill);
    $pdf->Cell($w[7], 6, number_format($amt, 2), 1, 0, 'R', $fill);
    $pdf->Ln();

    $fill = !$fill;
}

// Totals Row
$pdf->SetFont('Arial', 'B', 8.5);
$pdf->SetFillColor(241, 245, 249);
$pdf->SetTextColor(24, 49, 115);
$pdf->Cell($w[0] + $w[1] + $w[2] + $w[3] + $w[4] + $w[5] + $w[6], 7, 'Total Amount (' . count($donations) . ' donations):', 1, 0, 'R', true);
$pdf->Cell($w[7], 7, 'INR ' . number_format($totalAmt, 2), 1, 1, 'R', true);

$pdf->Output('D', 'Donation_Report_' . date('Y-m-d') . '.pdf');
exit;
