<?php
// ============================================================
// includes/custom_receipt_helper.php
// Custom Receipt Generator & PDF + QR Rendering Helper
// Reuses existing FPDF + QR Code pattern from donation receipts.
// Compatible with PHP 7.4+, 8.x
// ============================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../libs/fpdf/fpdf.php';
require_once __DIR__ . '/member_module.php';
require_once __DIR__ . '/functions.php';

if (!function_exists('cr_number_to_words')) {
    /**
     * Converts a numeric amount to Indian Currency Words (e.g., 5000 -> Five Thousand Rupees Only)
     */
    function cr_number_to_words(float $number): string
    {
        $decimal = round($number - ($no = floor($number)), 2) * 100;
        $hundred = null;
        $digits_length = strlen((string)$no);
        $i = 0;
        $str = [];
        $words = [
            0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five',
            6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten',
            11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen',
            16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen', 20 => 'Twenty',
            30 => 'Thirty', 40 => 'Forty', 50 => 'Fifty', 60 => 'Sixty', 70 => 'Seventy',
            80 => 'Eighty', 90 => 'Ninety'
        ];
        $digits = ['', 'Hundred', 'Thousand', 'Lakh', 'Crore'];

        while ($i < $digits_length) {
            $divider = ($i == 2) ? 10 : 100;
            $number = floor($no % $divider);
            $no = floor($no / $divider);
            $i += ($divider == 10) ? 1 : 2;
            if ($number) {
                $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
                $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
                $str[] = ($number < 21) ? $words[$number] . ' ' . $digits[$counter] . $plural . ' ' . $hundred
                    : $words[floor($number / 10) * 10] . ' ' . $words[$number % 10] . ' ' . $digits[$counter] . $plural . ' ' . $hundred;
            } else {
                $str[] = null;
            }
        }

        $rupees = implode('', array_reverse($str));
        $paise = '';
        if ($decimal > 0) {
            $paise = ' and ' . ($words[floor($decimal / 10) * 10] . ' ' . $words[$decimal % 10]) . ' Paise';
        }

        $result = trim($rupees) ? trim($rupees) . ' Rupees' . $paise . ' Only' : 'Zero Rupees Only';
        return preg_replace('/\s+/', ' ', $result);
    }
}

if (!function_exists('cr_generate_receipt_no')) {
    /**
     * Generates a unique sequential Receipt Number (e.g. REC-2026-0001)
     */
    function cr_generate_receipt_no(PDO $pdo, string $prefix = 'REC'): string
    {
        $year = date('Y');
        $stmt = $pdo->prepare("SELECT `receipt_no` FROM `custom_receipts` WHERE `receipt_no` LIKE ? ORDER BY `id` DESC LIMIT 1");
        $stmt->execute(["{$prefix}-{$year}-%"]);
        $last = $stmt->fetchColumn();

        if ($last && preg_match('/-(\d+)$/', (string)$last, $m)) {
            $seq = (int)$m[1] + 1;
        } else {
            // Count total receipts for year
            $cntStmt = $pdo->prepare("SELECT COUNT(*) FROM `custom_receipts` WHERE YEAR(`date`) = ? OR YEAR(`created_at`) = ?");
            $cntStmt->execute([$year, $year]);
            $seq = (int)$cntStmt->fetchColumn() + 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $year, $seq);
    }
}

if (!class_exists('CustomReceiptPDF')) {
    class CustomReceiptPDF extends FPDF
    {
        public $settings = [];
        public $colors = [];

        public function __construct(array $settings = [])
        {
            parent::__construct('P', 'mm', 'A4');
            $this->settings = $settings;
            $this->colors = function_exists('mm_get_pdf_color_template') 
                ? mm_get_pdf_color_template($settings) 
                : ['primary' => [15, 139, 141], 'accent' => [234, 88, 12]];
        }

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
            $p = $this->colors['primary'] ?? [15, 139, 141];
            
            // Header Top Bar
            $this->SetFillColor($p[0], $p[1], $p[2]);
            $this->Rect(0, 0, 210, 38, 'F');
            
            // White Logo Box
            $this->SetFillColor(255, 255, 255);
            $this->RoundedRect(14, 6, 26, 26, 3, 'F');
            
            $logoPath = $this->settings['ngo_logo'] ?? '';
            if ($logoPath !== '') {
                $resolvedLogo = function_exists('mm_prepare_image_for_fpdf') 
                    ? mm_prepare_image_for_fpdf(__DIR__ . '/../' . ltrim($logoPath, '/')) 
                    : (__DIR__ . '/../' . ltrim($logoPath, '/'));
                if ($resolvedLogo && file_exists($resolvedLogo)) {
                    $this->Image($resolvedLogo, 15, 7, 24, 24);
                }
            }

            // Organization Name
            $this->SetTextColor(255, 255, 255);
            $this->SetFont('Arial', 'B', 17);
            $this->SetXY(45, 9);
            $siteName = $this->settings['site_name'] ?? 'OFFICIAL NGO SYSTEM';
            $this->Cell(120, 8, strtoupper(substr($siteName, 0, 45)), 0, 1, 'L');

            // Subtitle / Tagline / Reg Info
            $this->SetFont('Arial', '', 8.5);
            $this->SetXY(45, 17);
            $regNo = $this->settings['ngo_reg_no'] ?? ($this->settings['letterhead_reg_no'] ?? '');
            $regText = $regNo ? "Reg. No: {$regNo} | " : "";
            $tagline = $this->settings['ngo_tagline'] ?? ($this->settings['letterhead_tagline'] ?? 'Empowering Society & Communities');
            $this->Cell(120, 5, $regText . substr($tagline, 0, 65), 0, 1, 'L');

            // Contact details header row
            $this->SetFont('Arial', 'I', 7.5);
            $this->SetXY(45, 23);
            $phone = $this->settings['contact_phone'] ?? ($this->settings['ngo_phone'] ?? '');
            $email = $this->settings['contact_email'] ?? ($this->settings['ngo_email'] ?? '');
            $contactLine = ($phone ? "Tel: {$phone}  " : "") . ($email ? "| Email: {$email}" : "");
            $this->Cell(120, 5, $contactLine, 0, 1, 'L');

            // Badge / Watermark in Header
            $this->SetXY(155, 10);
            $this->SetFont('Arial', 'B', 10);
            $this->SetFillColor(255, 255, 255);
            $this->SetTextColor($p[0], $p[1], $p[2]);
            $this->RoundedRect(152, 10, 46, 18, 3, 'F');
            $this->SetXY(152, 12);
            $this->Cell(46, 5, 'OFFICIAL RECEIPT', 0, 1, 'C');
            $this->SetFont('Arial', 'B', 7.5);
            $this->SetXY(152, 18);
            $this->SetTextColor(100, 100, 100);
            $this->Cell(46, 4, 'DONATION & PAYMENT', 0, 1, 'C');
        }

        public function Footer()
        {
            $p = $this->colors['primary'] ?? [15, 139, 141];
            $this->SetY(-25);
            $this->SetFont('Arial', 'I', 7.5);
            $this->SetTextColor(130, 130, 130);
            $this->MultiCell(0, 3.8, "This is an authorized official digital receipt issued by " . ($this->settings['site_name'] ?? 'the Organization') . ". It is digitally signed and verifiable via the QR Code.", 0, 'C');
            
            // Bottom Colored Decorative Bar
            $this->SetFillColor($p[0], $p[1], $p[2]);
            $this->Rect(0, 290, 210, 7, 'F');
        }
    }
}

if (!function_exists('cr_render_receipt_pdf')) {
    /**
     * Renders and streams or saves Custom Receipt PDF with QR code
     * 
     * @param PDO $pdo
     * @param array $receipt Receipt data array (from custom_receipts table or form input)
     * @param string $outputMode 'I' = Inline, 'D' = Force Download, 'F' = Save to file, 'S' = Return string
     * @param string|null $saveFilePath Absolute file path if saving to disk
     * @return string|void
     */
    function cr_render_receipt_pdf(PDO $pdo, array $receipt, string $outputMode = 'I', ?string $saveFilePath = null)
    {
        // Load settings
        $settings = function_exists('mm_load_settings') ? mm_load_settings($pdo) : [];
        if (empty($settings)) {
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
            $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
        }

        $pdf = new CustomReceiptPDF($settings);
        $pdf->SetAutoPageBreak(true, 25);
        $pdf->AddPage();

        $primaryColor = $pdf->colors['primary'] ?? [15, 139, 141];
        $accentColor = $pdf->colors['accent'] ?? [234, 88, 12];

        // Title Block
        $pdf->SetY(44);
        $pdf->SetFont('Arial', 'B', 15);
        $pdf->SetTextColor($primaryColor[0], $primaryColor[1], $primaryColor[2]);
        $pdf->Cell(0, 8, 'PAYMENT & DONATION RECEIPT', 0, 1, 'C');

        $pdf->SetFont('Arial', '', 8.5);
        $pdf->SetTextColor(120, 120, 120);
        $pdf->Cell(0, 4, 'TAX INVOICE / ACKNOWLEDGEMENT RECORD', 0, 1, 'C');
        $pdf->Ln(4);

        // Outer Decorative Rounded Box
        $boxStartY = $pdf->GetY();
        $pdf->SetDrawColor(210, 225, 225);
        $pdf->SetFillColor(252, 254, 254);
        $pdf->RoundedRect(14, $boxStartY, 182, 158, 4, 'DF');

        $pdf->SetY($boxStartY + 4);

        // Helper row function
        $renderRow = function($label, $value, $isBold = false, $highlight = false) use ($pdf, $primaryColor) {
            $pdf->SetFont('Arial', 'B', 8.5);
            $pdf->SetTextColor(90, 100, 110);
            $pdf->SetX(18);
            $pdf->Cell(48, 6.5, $label, 0, 0, 'L');
            
            $pdf->SetFont('Arial', '', 8.5);
            $pdf->Cell(4, 6.5, ':', 0, 0, 'C');
            
            $pdf->SetFont('Arial', $isBold ? 'B' : '', $highlight ? 10 : 8.5);
            if ($highlight) {
                $pdf->SetTextColor($primaryColor[0], $primaryColor[1], $primaryColor[2]);
            } else {
                $pdf->SetTextColor(30, 40, 50);
            }
            $pdf->MultiCell(120, 6.5, $value, 0, 'L');
        };

        // Receipt Meta Headers (2-column top bar)
        $receiptNo = $receipt['receipt_no'] ?? 'REC-0000';
        $receiptDate = !empty($receipt['date']) ? date('d F, Y', strtotime((string)$receipt['date'])) : date('d F, Y');
        
        // Receipt No & Date Banner inside box
        $pdf->SetFillColor(240, 248, 248);
        $pdf->RoundedRect(18, $pdf->GetY(), 174, 11, 2, 'F');
        $pdf->SetXY(22, $pdf->GetY() + 2);
        
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetTextColor($primaryColor[0], $primaryColor[1], $primaryColor[2]);
        $pdf->Cell(25, 7, 'RECEIPT NO:', 0, 0, 'L');
        $pdf->SetFont('Arial', 'B', 9.5);
        $pdf->SetTextColor(20, 30, 40);
        $pdf->Cell(65, 7, $receiptNo, 0, 0, 'L');

        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetTextColor($primaryColor[0], $primaryColor[1], $primaryColor[2]);
        $pdf->Cell(20, 7, 'DATE:', 0, 0, 'R');
        $pdf->SetFont('Arial', 'B', 9.5);
        $pdf->SetTextColor(20, 30, 40);
        $pdf->Cell(55, 7, $receiptDate, 0, 1, 'L');

        $pdf->Ln(4);

        // Payer Information
        $payerName = $receipt['payer_name'] ?? 'Anonymous Donor';
        $renderRow('Received With Thanks From', strtoupper($payerName), true, false);

        if (!empty($receipt['payer_phone'])) {
            $renderRow('Contact / Mobile', $receipt['payer_phone']);
        }
        if (!empty($receipt['payer_email'])) {
            $renderRow('Email Address', $receipt['payer_email']);
        }
        if (!empty($receipt['payer_pan'])) {
            $renderRow('PAN / Tax ID', strtoupper($receipt['payer_pan']), true);
        }
        if (!empty($receipt['payer_address'])) {
            $renderRow('Address / Location', $receipt['payer_address']);
        }

        // Horizontal Divider Line
        $pdf->Ln(2);
        $pdf->SetDrawColor(225, 235, 235);
        $pdf->Line(18, $pdf->GetY(), 192, $pdf->GetY());
        $pdf->Ln(3);

        // Amount Section
        $amount = (float)($receipt['amount'] ?? 0);
        $formattedAmount = 'INR ' . number_format($amount, 2);
        $amountInWords = !empty($receipt['amount_in_words']) 
            ? $receipt['amount_in_words'] 
            : cr_number_to_words($amount);

        $renderRow('Amount in Figures', $formattedAmount, true, true);
        $renderRow('Amount in Words', $amountInWords, true);

        // Horizontal Divider Line
        $pdf->Ln(2);
        $pdf->SetDrawColor(225, 235, 235);
        $pdf->Line(18, $pdf->GetY(), 192, $pdf->GetY());
        $pdf->Ln(3);

        // Payment Mode & Purpose
        $renderRow('Payment Mode', $receipt['payment_mode'] ?? 'Cash', true);
        if (!empty($receipt['transaction_ref'])) {
            $renderRow('Transaction Ref / Cheque No', $receipt['transaction_ref'], true);
        }
        $renderRow('Contribution Purpose', $receipt['purpose'] ?? 'General Donation / NGO Activities', true);
        
        if (!empty($receipt['remarks'])) {
            $renderRow('Remarks / Notes', $receipt['remarks']);
        }

        // 80G / Exempt Disclaimer Note
        $pdf->Ln(3);
        $pdf->SetFont('Arial', 'I', 7.5);
        $pdf->SetTextColor(40, 120, 60);
        $disclaimer = $settings['receipt_disclaimer'] ?? 'Donations to this organization are eligible for tax exemption under applicable Income Tax provisions.';
        $pdf->SetX(18);
        $pdf->MultiCell(174, 3.8, "• " . $disclaimer, 0, 'L');

        // Signature & QR Code Area
        $signY = 222;
        
        // 1. QR Code Generation (Exact pattern reuse)
        $baseUrl = rtrim(function_exists('appBaseUrl') ? appBaseUrl() : 'http://localhost', '/');
        $qrPayload = "Receipt:{$receiptNo};Name:{$payerName};Amount:{$amount};Date:{$receiptDate}";
        
        $qrImageUrl = function_exists('mm_qr_image_url') 
            ? mm_qr_image_url($qrPayload) 
            : ('https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=' . urlencode($qrPayload));

        $tempQrPath = __DIR__ . '/../uploads/temp_qr_cr_' . preg_replace('/[^a-zA-Z0-9]/', '', $receiptNo) . '.png';
        $qrReady = false;

        if (!is_dir(__DIR__ . '/../uploads/')) {
            @mkdir(__DIR__ . '/../uploads/', 0777, true);
        }

        $ch = curl_init($qrImageUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 4);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $qrData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($qrData && $httpCode === 200) {
            if (file_put_contents($tempQrPath, $qrData)) {
                $qrReady = true;
            }
        }

        if ($qrReady) {
            $pdf->Image($tempQrPath, 22, $signY - 2, 28, 28, 'PNG');
            $pdf->SetXY(20, $signY + 27);
            $pdf->SetFont('Arial', 'B', 6.5);
            $pdf->SetTextColor(120, 120, 120);
            $pdf->Cell(32, 4, 'SCAN TO VERIFY', 0, 0, 'C');
        }

        // 2. Organization Stamp / Seal Box (Center)
        $pdf->SetXY(65, $signY + 4);
        $pdf->SetFont('Arial', '', 7.5);
        $pdf->SetTextColor(140, 140, 140);
        $pdf->Cell(65, 4, 'Official Receipt Acknowledgment', 0, 1, 'C');
        $pdf->SetXY(65, $signY + 9);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor($primaryColor[0], $primaryColor[1], $primaryColor[2]);
        $pdf->Cell(65, 4, strtoupper($settings['site_name'] ?? 'NGO SEAL'), 0, 1, 'C');

        // 3. Authorized Signature (Right)
        $sigImage = $settings['ngo_signature'] ?? '';
        if ($sigImage !== '') {
            $resolvedSig = function_exists('mm_prepare_image_for_fpdf') 
                ? mm_prepare_image_for_fpdf(__DIR__ . '/../' . ltrim($sigImage, '/')) 
                : (__DIR__ . '/../' . ltrim($sigImage, '/'));
            if ($resolvedSig && file_exists($resolvedSig)) {
                $pdf->Image($resolvedSig, 142, $signY - 2, 44, 14);
            }
        }

        $pdf->Line(138, $signY + 16, 188, $signY + 16);
        $pdf->SetXY(138, $signY + 17);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(40, 40, 40);
        $pdf->Cell(50, 4, 'Authorized Signatory', 0, 1, 'C');
        $pdf->SetXY(138, $signY + 21);
        $pdf->SetFont('Arial', '', 7);
        $pdf->SetTextColor(120, 120, 120);
        $pdf->Cell(50, 4, $settings['site_name'] ?? 'Management Team', 0, 0, 'C');

        // Clean up temporary QR file
        if ($qrReady && file_exists($tempQrPath)) {
            @unlink($tempQrPath);
        }

        // Handle Output Modes
        $cleanFileName = 'Receipt_' . preg_replace('/[^A-Za-z0-9\-]/', '_', $receiptNo) . '.pdf';

        if ($saveFilePath !== null) {
            $saveDir = dirname($saveFilePath);
            if (!is_dir($saveDir)) {
                @mkdir($saveDir, 0777, true);
            }
            $pdf->Output('F', $saveFilePath);
        }

        if ($outputMode === 'S') {
            return $pdf->Output('S');
        } elseif ($outputMode === 'D') {
            if (ob_get_length()) ob_end_clean();
            $pdf->Output('D', $cleanFileName);
            exit;
        } elseif ($outputMode === 'I') {
            if (ob_get_length()) ob_end_clean();
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $cleanFileName . '"');
            $pdf->Output('I', $cleanFileName);
            exit;
        }

        return $cleanFileName;
    }
}
