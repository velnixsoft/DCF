<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
require_once __DIR__ . '/../libs/fpdf/fpdf.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/member_module.php';
require_once __DIR__ . '/../includes/template_builder.php';
require '../includes/functions.php';

$id = $_GET['id'] ?? 0;
if (!$id) die("Invalid ID");

$stmt = $pdo->prepare("SELECT d.*, p.title as project_name FROM donations d LEFT JOIN projects p ON d.project_id = p.id WHERE d.id = ?");
$stmt->execute([$id]);
$donation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$donation || $donation['payment_status'] !== 'Success') die("Receipt unavailable.");

$settings = [];
$stmt = $pdo->query("SELECT * FROM settings");
while ($row = $stmt->fetch()) $settings[$row['setting_key']] = $row['setting_value'];

$customTemplate = tb_load_active_template($pdo, 'receipt');
if ($customTemplate) {
    $receiptNo = $donation['receipt_no'] ?: ('RCP-' . (int)$donation['id']);
    $qrData = "Receipt:{$receiptNo},Name:{$donation['donor_name']},Amt:{$donation['amount']}";
    $context = [
        'full_name' => $donation['donor_name'] ?? '',
        'member_no' => '',
        'phone' => $donation['donor_phone'] ?? '',
        'email' => $donation['donor_email'] ?? '',
        'address' => $donation['donor_address'] ?? '',
        'date' => !empty($donation['created_at']) ? date('d-m-Y', strtotime((string)$donation['created_at'])) : date('d-m-Y'),
        'doc_no' => $receiptNo,
        'photo_path' => '',
        'qr_path' => mm_qr_image_url($qrData),
        'logo_path' => $settings['ngo_logo'] ?? '',
        'signature_path' => $settings['ngo_signature'] ?? '',
    ];
    $pdfContent = tb_render_template_pdf($pdo, $customTemplate, $context);
    if (ob_get_length()) {
        ob_end_clean();
    }
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="receipt.pdf"');
    echo $pdfContent;
    exit;
}

class PDF extends FPDF
{
    private $settings;
    private $colors;
    function __construct($settings)
    {
        parent::__construct();
        $this->settings = $settings;
        $this->colors = mm_get_pdf_color_template($settings);
    }

    function RoundedRect($x, $y, $w, $h, $r, $style = '')
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

    function addWatermark()
    {
        // FPDF does not support opacity out-of-the-box.
        // Keeping this method as a safe no-op to avoid runtime errors.
    }

    function Header()
    {
        $this->SetFillColor($this->colors['primary'][0], $this->colors['primary'][1], $this->colors['primary'][2]);
        $this->SetTextColor(255, 255, 255);
        $this->Rect(0, 0, 210, 40, 'F');
        $this->SetFillColor(255, 255, 255);
        $this->RoundedRect(14, 7, 27, 27, 3, 'F');
        if (!empty($this->settings['ngo_logo'])) {
            $logoPathLocal = mm_prepare_image_for_fpdf(__DIR__ . '/../' . $this->settings['ngo_logo']);
            if ($logoPathLocal && file_exists($logoPathLocal)) {
                $this->Image($logoPathLocal, 15, 8, 25);
            }
        }
        $this->SetFont('Arial', 'B', 20);
        $this->SetXY(45, 12);
        $this->Cell(0, 10, strtoupper($this->settings['site_name'] ?? 'NGO NAME'), 0, 1, 'L');
        $this->SetFont('Arial', '', 9);
        $this->SetXY(45, 22);
        $this->Cell(0, 5, 'Official Donation Receipt', 0, 1, 'L');
    }

    function Footer()
    {
        $this->SetY(-30);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(150);
        $this->MultiCell(0, 4, 'This is a computer-generated receipt and does not require a physical signature.', 0, 'C');
        $this->SetFillColor($this->colors['primary'][0], $this->colors['primary'][1], $this->colors['primary'][2]);
        $this->Rect(0, 287, 210, 10, 'F');
    }
}

$pdf = new PDF($settings);
$pdf->AddPage();

$pdf->SetY(50);
$pdf->SetFont('Arial', '', 11);
$pdf->SetTextColor(100);

function addDetailRow($pdf, $label, $value, $isBold = false)
{
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(100);
    $pdf->Cell(45, 8, $label, 0, 0, 'R');
    $pdf->SetFont('Arial', $isBold ? 'B' : '', 11);
    $pdf->SetTextColor(20);
    $pdf->Cell(5, 8, ':', 0, 0, 'C');
    $pdf->MultiCell(0, 8, $value, 0, 'L');
}

$title = ($donation['is_80g_eligible'] == 1) ? 'DONATION RECEIPT (80G ELIGIBLE)' : 'ACKNOWLEDGEMENT RECEIPT';
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, $title, 0, 1, 'C');
$pdf->Ln(5);
addDetailRow($pdf, 'Receipt No', $donation['receipt_no'], true);
addDetailRow($pdf, 'Date', date('d F, Y', strtotime($donation['created_at'])));
$pdf->Ln(8);
addDetailRow($pdf, 'Received From', $donation['donor_name'], true);
addDetailRow($pdf, 'Email', $donation['donor_email']);
if ($donation['is_80g_eligible'] == 1 && !empty($donation['donor_pan'])) {
    addDetailRow($pdf, 'PAN', $donation['donor_pan']);
}
$pdf->Ln(8);
$pdf->SetDrawColor(220);
$pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
$pdf->Ln(4);
addDetailRow($pdf, 'Amount', 'INR ' . number_format($donation['amount'], 2), true);
addDetailRow($pdf, 'Transaction ID', $donation['transaction_id'] ?? 'N/A');
addDetailRow($pdf, 'For Project', $donation['project_name'] ?? 'General Fund');
$pdf->Ln(4);
$pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
$pdf->Ln(15);
$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(80);
$pdf->MultiCell(0, 6, "On behalf of " . ($settings['site_name'] ?? 'our organization') . ", we extend our sincerest gratitude for your generous donation.", 0, 'C');

if ($donation['is_80g_eligible'] == 1 && !empty($settings['receipt_disclaimer'])) {
    $tpl = mm_get_pdf_color_template($settings);
    $pdf->Ln(5);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetTextColor($tpl['accent'][0], $tpl['accent'][1], $tpl['accent'][2]);
    $pdf->Cell(0, 5, $settings['receipt_disclaimer'], 0, 1, 'C');
}

$signatureY = $pdf->GetY() + 10;
if ($signatureY < 200) $signatureY = 200;

if (!empty($settings['ngo_signature'])) {
    $sigPathLocal = mm_prepare_image_for_fpdf(__DIR__ . '/../' . $settings['ngo_signature']);
    if ($sigPathLocal && file_exists($sigPathLocal)) {
        $pdf->Image($sigPathLocal, 140, $signatureY, 45, 15);
    }
}
$pdf->Line(140, $signatureY + 16, 185, $signatureY + 16);
$pdf->SetXY(140, $signatureY + 17);
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(45, 5, 'Authorized Signatory', 0, 0, 'C');
$qrData = urlencode("Receipt:{$donation['receipt_no']},Name:{$donation['donor_name']},Amt:{$donation['amount']}");
$qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . $qrData;
$localQrPath = __DIR__ . '/../uploads/temp_qr_gen_' . $donation['id'] . '.png';
$qrSuccess = false;

if (!is_dir(__DIR__ . '/../uploads/')) {
    mkdir(__DIR__ . '/../uploads/', 0777, true);
}

$ch = curl_init($qrUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$qrImageData = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($qrImageData && $httpCode === 200) {
    if (file_put_contents($localQrPath, $qrImageData)) {
        $qrSuccess = true;
    }
}

if ($qrSuccess) {
    $pdf->Image($localQrPath, 20, $signatureY - 5, 30, 30, 'PNG');
}

$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

if (strpos($userAgent, 'Android') !== false && strpos($userAgent, 'wv') !== false) {
    // 👉 Android WebView
    $pdf->Output('D', 'receipt.pdf'); // download
} else {
    // 👉 Browser
    $pdf->Output('I', 'receipt.pdf'); // open
}

if ($qrSuccess && file_exists($localQrPath)) {
    @unlink($localQrPath);
}
