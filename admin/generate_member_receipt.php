<?php
require_once '../config/db.php';
require_once '../libs/fpdf/fpdf.php';
require_once '../includes/member_module.php';
require_once '../includes/template_builder.php';
require_once '../includes/qr_attendance.php';
require_once __DIR__ . '/../includes/functions.php';



if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$member = null;

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $id = (int)($_GET['id'] ?? 0);
    if ($id > 0) {
        $member = mm_get_member($pdo, $id);
    }
} else {
    $memberNo = trim($_GET['member_no'] ?? '');
    $email = trim($_GET['email'] ?? '');
    if ($memberNo !== '' && $email !== '') {
        $stmt = $pdo->prepare("SELECT m.*, d.title AS designation_title FROM members m LEFT JOIN member_designations d ON d.id = m.designation_id WHERE m.member_no = ? AND m.email = ? LIMIT 1");
        $stmt->execute([$memberNo, $email]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

if (!$member || $member['payment_status'] !== 'Success') {
    die('Unauthorized or receipt unavailable.');
}

$settings = mm_load_settings($pdo);
$siteName = $settings['site_name'] ?? 'NGO';
$receiptNo = $member['member_receipt_no'] ?: ('MRCPT-' . (int)$member['id']);
$verifyUrl = qa_member_verify_url($pdo, $settings, (int)$member['id'], 'receipt');
$qrPayload = $verifyUrl !== '' ? $verifyUrl : ((($settings['ngo_website'] ?? '') ? rtrim($settings['ngo_website'], '/') : '') . '/member-verify.php?member=' . urlencode((string)$member['member_no']));
if ($qrPayload === '') {
    $qrPayload = 'MemberNo:' . $member['member_no'] . ';Receipt:' . $receiptNo;
}

$customTemplate = tb_load_active_template($pdo, 'receipt');
if ($customTemplate) {
    $context = tb_member_pdf_context($member, $settings, $receiptNo, $qrPayload);
    $context['doc_no'] = $receiptNo;
    $context['date'] = !empty($member['member_since']) ? date('d-m-Y', strtotime((string)$member['member_since'])) : date('d-m-Y');
    $pdfContent = tb_render_template_pdf($pdo, $customTemplate, $context);

    if (ob_get_length()) {
        ob_end_clean();
    }
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="Membership_Receipt_' . preg_replace('/[^A-Za-z0-9\-]/', '', $receiptNo) . '.pdf"');
    echo $pdfContent;
    exit;
}

class MemberReceiptPDF extends FPDF
{
    public $settings = [];
    public $colors = [];

    public function RoundedRect($x, $y, $w, $h, $r, $style = '')
    {
        $k = $this->k;
        $hp = $this->h;
        if ($style == 'F') $op = 'f';
        elseif ($style == 'FD' || $style == 'DF') $op = 'B';
        else $op = 'S';
        $MyArc = 4 / 3 * (sqrt(2) - 1);
        $this->_out(sprintf('%.2F %.2F m', ($x + $r) * $k, ($hp - $y) * $k));
        $xc = $x + $w - $r;
        $yc = $y + $r;
        $this->_out(sprintf('%.2F %.2F l', $xc * $k, ($hp - $y) * $k));
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c', ($xc + $r * $MyArc) * $k, ($hp - $y) * $k, ($xc + $r) * $k, ($hp - ($y + $r * $MyArc)) * $k, ($xc + $r) * $k, ($hp - $yc) * $k));
        $xc = $x + $w - $r;
        $yc = $y + $h - $r;
        $this->_out(sprintf('%.2F %.2F l', ($x + $w) * $k, ($hp - $yc) * $k));
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c', ($x + $w) * $k, ($hp - ($yc + $r * $MyArc)) * $k, ($xc + $r * $MyArc) * $k, ($hp - ($y + $h)) * $k, $xc * $k, ($hp - ($y + $h)) * $k));
        $xc = $x + $r;
        $yc = $y + $h - $r;
        $this->_out(sprintf('%.2F %.2F l', $xc * $k, ($hp - ($y + $h)) * $k));
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c', ($xc - $r * $MyArc) * $k, ($hp - ($y + $h)) * $k, $x * $k, ($hp - ($yc + $r * $MyArc)) * $k, $x * $k, ($hp - $yc) * $k));
        $xc = $x + $r;
        $yc = $y + $r;
        $this->_out(sprintf('%.2F %.2F l', $x * $k, ($hp - $yc) * $k));
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c', $x * $k, ($hp - ($yc - $r * $MyArc)) * $k, ($xc - $r * $MyArc) * $k, ($hp - $y) * $k, $xc * $k, ($hp - $y) * $k));
        $this->_out($op);
    }

    public function Header()
    {
        $this->SetFillColor($this->colors['primary'][0], $this->colors['primary'][1], $this->colors['primary'][2]);
        $this->Rect(0, 0, 210, 40, 'F');
        $this->SetFillColor(255, 255, 255);
        $this->RoundedRect(14, 7, 27, 27, 3, 'F');
        if (!empty($this->settings['ngo_logo'])) {
            $logoPathLocal = mm_prepare_image_for_fpdf(__DIR__ . '/../' . $this->settings['ngo_logo']);
            if ($logoPathLocal && file_exists($logoPathLocal)) {
                $this->Image($logoPathLocal, 15, 8, 25);
            }
        }
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 16);
        $this->SetXY(45, 12);
        $this->Cell(0, 7, strtoupper($this->settings['site_name'] ?? 'NGO'), 0, 1);
        $this->SetFont('Arial', '', 10);
        $this->SetX(45);
        $this->Cell(0, 6, 'Membership Fee Receipt', 0, 1);
    }
}

$pdf = new MemberReceiptPDF();
$pdf->settings = $settings;
$pdf->colors = mm_get_pdf_color_template($settings);
$pdf->AddPage();
$pdf->SetY(50);
$pdf->SetTextColor(30, 30, 30);
$pdf->SetFont('Arial', 'B', 15);
$pdf->Cell(0, 8, 'MEMBERSHIP RECEIPT', 0, 1, 'C');
$pdf->Ln(4);

$rows = [
    ['Receipt No', $member['member_receipt_no']],
    ['Date', date('d M Y', strtotime($member['member_since'] ?: $member['created_at']))],
    ['Member Name', $member['full_name']],
    ['Member No', $member['member_no'] ?: 'Pending'],
    ['Designation', $member['designation_title'] ?: '-'],
    ['Membership Fee', 'INR ' . number_format($member['membership_fee'], 2)],
    ['Transaction ID', $member['payment_txn_id'] ?: '-'],
];

foreach ($rows as $row) {
    $pdf->SetFont('Arial', '', 11);
    $pdf->SetTextColor($pdf->colors['text_muted'][0], $pdf->colors['text_muted'][1], $pdf->colors['text_muted'][2]);
    $pdf->Cell(58, 8, $row[0], 0, 0, 'R');
    $pdf->SetTextColor($pdf->colors['text_dark'][0], $pdf->colors['text_dark'][1], $pdf->colors['text_dark'][2]);
    $pdf->Cell(7, 8, ':', 0, 0, 'C');
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(0, 8, $row[1], 0, 1);
}

$qrPathLocal = mm_prepare_image_for_fpdf(mm_qr_image_url($qrPayload));
if ($qrPathLocal && file_exists($qrPathLocal)) {
    $pdf->Image($qrPathLocal, 18, 190, 30, 30, 'PNG');
}

$pdf->SetXY(15, 225);
$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(100, 100, 100);
$pdf->MultiCell(0, 5, 'Scan QR to verify this receipt. This is a system-generated document.', 0, 'L');

$pdf->Line(145, 220, 190, 220);
$pdf->SetXY(145, 221);
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(45, 5, 'Authorized Signatory', 0, 0, 'C');

$pdf->Output('I', 'Membership_Receipt_' . preg_replace('/[^A-Za-z0-9\-]/', '', $member['member_receipt_no']) . '.pdf');
