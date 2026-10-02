<?php
// ============================================================
// download-item-receipt.php
// Generates official PDF receipt with dynamic QR verification for Item Donations
// ============================================================

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/libs/fpdf/fpdf.php';

$itemId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$donationCode = cleanInput($_GET['code'] ?? '');
$downloadToken = trim((string)($_GET['token'] ?? ''));

if (!$itemId && empty($donationCode)) {
    die("Invalid receipt request.");
}

// Fetch Item Donation record
if ($itemId) {
    $stmt = $pdo->prepare("
        SELECT i.*, c.category_name, c.category_icon, p.title AS project_title 
        FROM item_donations i
        LEFT JOIN item_donation_categories c ON i.category_id = c.id
        LEFT JOIN projects p ON i.project_id = p.id
        WHERE i.id = ?
        LIMIT 1
    ");
    $stmt->execute([$itemId]);
} else {
    $stmt = $pdo->prepare("
        SELECT i.*, c.category_name, c.category_icon, p.title AS project_title 
        FROM item_donations i
        LEFT JOIN item_donation_categories c ON i.category_id = c.id
        LEFT JOIN projects p ON i.project_id = p.id
        WHERE i.donation_code = ?
        LIMIT 1
    ");
    $stmt->execute([$donationCode]);
}

$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    die("Item donation record not found.");
}

$itemId = (int)$item['id'];
$receiptNo = $item['receipt_no'] ?: ('RCP-ITM-' . $itemId);

// Authorization check
$authorized = false;

if ($downloadToken !== '') {
    $authorized = isValidItemDonationReceiptToken(
        $item['id'],
        $item['donation_code'],
        $receiptNo,
        $item['donor_email'],
        $item['created_at'],
        $downloadToken
    );
}

// Check logged in admin
if (!$authorized && !empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $authorized = true;
}

// Check logged in donor
if (!$authorized && !empty($_SESSION['donor_verified']) && !empty($_SESSION['donor_email'])) {
    $authorized = hash_equals(strtolower((string)$_SESSION['donor_email']), strtolower((string)$item['donor_email']));
}

// Check logged in member
if (!$authorized && !empty($_SESSION['member_logged_in']) && !empty($_SESSION['member_email'])) {
    $authorized = hash_equals(strtolower((string)$_SESSION['member_email']), strtolower((string)$item['donor_email']));
}

// If token not provided but requested via public direct link
if (!$authorized && empty($downloadToken)) {
    // Generate valid token for public receipt viewing
    $validToken = generateItemDonationReceiptToken($item['id'], $item['donation_code'], $receiptNo, $item['donor_email'], $item['created_at']);
    $authorized = true;
}

if (!$authorized) {
    die("Access denied. Invalid verification token.");
}

// Fetch NGO settings
$settings = [];
$st = $pdo->query("SELECT setting_key, setting_value FROM settings");
while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
    $settings[$r['setting_key']] = $r['setting_value'];
}

$siteName = (string)($settings['site_name'] ?? 'NGO Organization');
$siteAddress = (string)($settings['contact_address'] ?? $settings['ngo_address'] ?? '');
$sitePhone = (string)($settings['contact_phone'] ?? $settings['ngo_phone'] ?? '');
$siteEmail = (string)($settings['contact_email'] ?? $settings['ngo_email'] ?? '');

class ItemReceiptPDF extends FPDF
{
    public $settings;
    
    function __construct($settings)
    {
        parent::__construct('P', 'mm', 'A4');
        $this->settings = $settings;
        $this->SetAutoPageBreak(true, 25);
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

    function Header()
    {
        // Top Banner
        $this->SetFillColor(15, 139, 141); // Teal
        $this->Rect(0, 0, 210, 38, 'F');
        
        // Logo Box
        $this->SetFillColor(255, 255, 255);
        $this->RoundedRect(14, 6, 26, 26, 3, 'F');
        
        $logoFile = __DIR__ . '/' . ($this->settings['ngo_logo'] ?? '');
        if (!empty($this->settings['ngo_logo']) && file_exists($logoFile)) {
            $this->Image($logoFile, 15, 7, 24);
        }

        // Title
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 16);
        $this->SetXY(45, 10);
        $this->Cell(0, 8, strtoupper((string)($this->settings['site_name'] ?? 'NGO SYSTEM')), 0, 1, 'L');
        
        $this->SetFont('Arial', '', 9);
        $this->SetXY(45, 18);
        $this->Cell(0, 5, 'Official In-Kind Item Donation Acknowledgement & Receipt', 0, 1, 'L');
        
        $this->SetXY(45, 24);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 5, 'Committed to Grassroots Transformation & Transparent Welfare Distribution', 0, 1, 'L');
    }

    function Footer()
    {
        $this->SetY(-24);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(130);
        $this->MultiCell(0, 4, 'This is a computer-generated in-kind donation receipt with QR code authentication. No physical signature required.', 0, 'C');
        
        $this->SetFillColor(15, 139, 141);
        $this->Rect(0, 292, 210, 5, 'F');
    }
}

$pdf = new ItemReceiptPDF($settings);
$pdf->AddPage();
$pdf->SetMargins(15, 45, 15);

// Title Box
$pdf->SetY(44);
$pdf->SetFillColor(240, 253, 253);
$pdf->SetDrawColor(204, 251, 241);
$pdf->RoundedRect(15, 44, 180, 18, 3, 'DF');

$pdf->SetFont('Arial', 'B', 14);
$pdf->SetTextColor(15, 139, 141);
$pdf->SetXY(15, 46);
$pdf->Cell(180, 8, 'ITEM DONATION RECEIPT & ACKNOWLEDGEMENT', 0, 1, 'C');

$pdf->SetFont('Arial', 'B', 9);
$pdf->SetTextColor(244, 166, 64); // Gold/Orange
$pdf->Cell(180, 5, 'IN-KIND CHARITABLE CONTRIBUTION', 0, 1, 'C');

$pdf->Ln(4);

// Receipt Metadata Strip
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetTextColor(80);
$pdf->Cell(45, 6, 'Receipt No:', 0, 0, 'L');
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetTextColor(20);
$pdf->Cell(55, 6, $receiptNo, 0, 0, 'L');

$pdf->SetFont('Arial', 'B', 9);
$pdf->SetTextColor(80);
$pdf->Cell(35, 6, 'Donation Date:', 0, 0, 'L');
$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(20);
$pdf->Cell(45, 6, date('d F, Y', strtotime($item['donation_date'])), 0, 1, 'L');

$pdf->SetFont('Arial', 'B', 9);
$pdf->SetTextColor(80);
$pdf->Cell(45, 6, 'Pledge Tracking Code:', 0, 0, 'L');
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetTextColor(15, 139, 141);
$pdf->Cell(55, 6, $item['donation_code'], 0, 0, 'L');

$pdf->SetFont('Arial', 'B', 9);
$pdf->SetTextColor(80);
$pdf->Cell(35, 6, 'Status:', 0, 0, 'L');
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetTextColor(16, 112, 176);
$pdf->Cell(45, 6, strtoupper($item['status']), 0, 1, 'L');

$pdf->Ln(2);
$pdf->SetDrawColor(220);
$pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
$pdf->Ln(4);

// Donor Information Section
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetTextColor(15, 139, 141);
$pdf->Cell(0, 6, 'DONOR DETAILS', 0, 1, 'L');

$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(70);

function printItemRow($pdf, $label, $value, $bold = false) {
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetTextColor(90);
    $pdf->Cell(40, 6, $label, 0, 0, 'L');
    $pdf->SetFont('Arial', $bold ? 'B' : '', 9.5);
    $pdf->SetTextColor(20);
    $pdf->Cell(4, 6, ':', 0, 0, 'C');
    $pdf->MultiCell(0, 6, $value, 0, 'L');
}

printItemRow($pdf, 'Donor Name', $item['donor_name'], true);
printItemRow($pdf, 'Email Address', $item['donor_email']);
printItemRow($pdf, 'Mobile Number', $item['donor_mobile']);

if (!empty($item['donor_pan'])) {
    printItemRow($pdf, 'PAN Number', strtoupper($item['donor_pan']));
}

$pickupLoc = trim(($item['pickup_address'] ?? '') . ', ' . ($item['pickup_city'] ?? '') . ' - ' . ($item['pickup_pincode'] ?? ''), ', -');
printItemRow($pdf, 'Collection Location', $pickupLoc);

$pdf->Ln(3);
$pdf->SetDrawColor(220);
$pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
$pdf->Ln(4);

// Item Particulars Section
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetTextColor(15, 139, 141);
$pdf->Cell(0, 6, 'ITEM CONTRIBUTION PARTICULARS', 0, 1, 'L');
$pdf->Ln(1);

// Table Header
$pdf->SetFillColor(245, 247, 250);
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetTextColor(50);
$pdf->Cell(45, 7, 'Category', 1, 0, 'L', true);
$pdf->Cell(65, 7, 'Item Description', 1, 0, 'L', true);
$pdf->Cell(35, 7, 'Quantity / Unit', 1, 0, 'C', true);
$pdf->Cell(35, 7, 'Est. Value', 1, 1, 'R', true);

// Table Content
$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(30);

$valText = ((float)$item['estimated_value'] > 0) 
    ? ('INR ' . number_format((float)$item['estimated_value'], 2)) 
    : 'In-Kind Gift';

$catTitle = $item['category_name'] ?: 'Essential Items';
$descText = $item['item_description'] . "\n(Condition: " . $item['condition_type'] . ")";
$qtyText = number_format((float)$item['quantity'], 2) . ' ' . $item['unit'];

// Save coordinates for multi-cell row calculation
$startY = $pdf->GetY();
$pdf->SetXY(15, $startY);
$pdf->MultiCell(45, 6, $catTitle, 1, 'L');
$h1 = $pdf->GetY() - $startY;

$pdf->SetXY(60, $startY);
$pdf->MultiCell(65, 5, $descText, 1, 'L');
$h2 = $pdf->GetY() - $startY;

$maxH = max($h1, $h2, 14);

$pdf->SetXY(125, $startY);
$pdf->Cell(35, $maxH, $qtyText, 1, 0, 'C');

$pdf->SetXY(160, $startY);
$pdf->SetFont('Arial', 'B', 9.5);
$pdf->SetTextColor(15, 139, 141);
$pdf->Cell(35, $maxH, $valText, 1, 1, 'R');

$pdf->SetY($startY + $maxH);

$pdf->Ln(3);
if (!empty($item['project_title'])) {
    printItemRow($pdf, 'Allocated Project', $item['project_title'], true);
}
if (!empty($item['remarks'])) {
    printItemRow($pdf, 'Donor Remarks', $item['remarks']);
}

$pdf->Ln(4);

// Gratitude Statement
$pdf->SetFillColor(255, 248, 241);
$pdf->SetDrawColor(254, 236, 220);
$pdf->RoundedRect(15, $pdf->GetY(), 180, 16, 2, 'DF');
$pdf->SetFont('Arial', 'I', 8.5);
$pdf->SetTextColor(80);
$pdf->SetXY(17, $pdf->GetY() + 2);
$pdf->MultiCell(176, 4.5, "On behalf of " . $siteName . ", we express our heartfelt gratitude for your generous in-kind contribution. Your donated items will be carefully inspected and distributed directly to empower underprivileged beneficiaries.", 0, 'C');

// Bottom Section: QR Code and Authorized Signatory
$signY = $pdf->GetY() + 8;
if ($signY < 230) $signY = 230;

// QR Code Generation
$token = generateItemDonationReceiptToken($item['id'], $item['donation_code'], $receiptNo, $item['donor_email'], $item['created_at']);
$verifyUrl = rtrim(appBaseUrl(), '/') . '/verify-item.php?code=' . urlencode($item['donation_code']) . '&token=' . urlencode($token);

$qrData = urlencode("ItemReceipt:{$receiptNo},Donor:{$item['donor_name']},Cat:{$catTitle},Qty:{$qtyText},Code:{$item['donation_code']},Verify:{$verifyUrl}");
$qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . $qrData;

$localQrPath = __DIR__ . '/uploads/temp_qr_item_' . $item['id'] . '.png';
$qrSuccess = false;

if (!is_dir(__DIR__ . '/uploads/')) {
    mkdir(__DIR__ . '/uploads/', 0777, true);
}

$ch = curl_init($qrApiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 4,
    CURLOPT_SSL_VERIFYPEER => false
]);
$qrImageData = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($qrImageData && $httpCode === 200) {
    if (file_put_contents($localQrPath, $qrImageData)) {
        $qrSuccess = true;
    }
}

if ($qrSuccess && file_exists($localQrPath)) {
    $pdf->Image($localQrPath, 18, $signY - 2, 28, 28, 'PNG');
}

$pdf->SetXY(48, $signY + 2);
$pdf->SetFont('Arial', 'B', 8.5);
$pdf->SetTextColor(15, 139, 141);
$pdf->Cell(60, 4, 'SCAN TO VERIFY ITEM RECEIPT', 0, 1, 'L');

$pdf->SetXY(48, $signY + 7);
$pdf->SetFont('Arial', '', 7.5);
$pdf->SetTextColor(100);
$pdf->Cell(60, 4, 'Direct digital verification link:', 0, 1, 'L');
$pdf->SetXY(48, $signY + 11);
$pdf->SetFont('Arial', 'U', 7);
$pdf->SetTextColor(16, 112, 176);
$pdf->Cell(60, 4, substr($verifyUrl, 0, 45) . '...', 0, 1, 'L', false, $verifyUrl);

// Signature on Right
$sigFile = __DIR__ . '/' . ($settings['ngo_signature'] ?? '');
if (!empty($settings['ngo_signature']) && file_exists($sigFile)) {
    $pdf->Image($sigFile, 140, $signY - 2, 45, 14);
}

$pdf->Line(135, $signY + 14, 185, $signY + 14);
$pdf->SetXY(135, $signY + 15);
$pdf->SetFont('Arial', 'B', 8.5);
$pdf->SetTextColor(50);
$pdf->Cell(50, 4, 'Authorized Signatory', 0, 1, 'C');
$pdf->SetXY(135, $signY + 19);
$pdf->SetFont('Arial', '', 7.5);
$pdf->SetTextColor(100);
$pdf->Cell(50, 4, htmlspecialchars($siteName), 0, 1, 'C');

// Clean up temporary QR file
if ($qrSuccess && file_exists($localQrPath)) {
    @unlink($localQrPath);
}

// Output PDF
$fileName = 'Item_Receipt_' . $item['donation_code'] . '.pdf';
$action = (isset($_GET['mode']) && $_GET['mode'] === 'inline') ? 'I' : 'D';

if (ob_get_length()) {
    ob_end_clean();
}

$pdf->Output($action, $fileName);
exit;
