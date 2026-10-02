<?php
// ============================================================
// includes/health_card_helper.php
// Health Card PDF Generator with Embedded Verification QR Code
// Reuses FPDF + QR Code generation architecture
// ============================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../libs/fpdf/fpdf.php';
require_once __DIR__ . '/member_module.php';
require_once __DIR__ . '/functions.php';

if (!class_exists('HealthCardPDF')) {
    class HealthCardPDF extends FPDF
    {
        /**
         * Draws a rounded rectangle
         */
        public function RoundedRect($x, $y, $w, $h, $r, $corners = '1234', $style = '')
        {
            $k = $this->k;
            $hp = $this->h;
            if ($style == 'F') {
                $op = 'f';
            } elseif ($style == 'FD' || $style == 'DF') {
                $op = 'B';
            } else {
                $op = 'S';
            }
            $MyArc = 4 / 3 * (sqrt(2) - 1);
            $this->_out(sprintf('%.2F %.2F m', ($x + $r) * $k, ($hp - $y) * $k));

            $xc = $x + $w - $r;
            $yc = $y + $r;
            $this->_out(sprintf('%.2F %.2F l', $xc * $k, ($hp - $y) * $k));
            if (strpos($corners, '2') === false) {
                $this->_out(sprintf('%.2F %.2F l %.2F %.2F l', ($x + $w) * $k, ($hp - $y) * $k, ($x + $w) * $k, ($hp - ($y + $r)) * $k));
            } else {
                $this->_Arc($xc + $r * $MyArc, $yc - $r, $xc + $r, $yc - $r * $MyArc, $xc + $r, $yc);
            }

            $xc = $x + $w - $r;
            $yc = $y + $h - $r;
            $this->_out(sprintf('%.2F %.2F l', ($x + $w) * $k, ($hp - $yc) * $k));
            if (strpos($corners, '3') === false) {
                $this->_out(sprintf('%.2F %.2F l %.2F %.2F l', ($x + $w) * $k, ($hp - ($y + $h)) * $k, ($x + $w - $r) * $k, ($hp - ($y + $h)) * $k));
            } else {
                $this->_Arc($xc + $r, $yc + $r * $MyArc, $xc + $r * $MyArc, $yc + $r, $xc, $yc + $r);
            }

            $xc = $x + $r;
            $yc = $y + $h - $r;
            $this->_out(sprintf('%.2F %.2F l', $xc * $k, ($hp - ($y + $h)) * $k));
            if (strpos($corners, '4') === false) {
                $this->_out(sprintf('%.2F %.2F l %.2F %.2F l', $x * $k, ($hp - ($y + $h)) * $k, $x * $k, ($hp - ($y + $h - $r)) * $k));
            } else {
                $this->_Arc($xc - $r * $MyArc, $yc + $r, $xc - $r, $yc + $r * $MyArc, $xc - $r, $yc);
            }

            $xc = $x + $r;
            $yc = $y + $r;
            $this->_out(sprintf('%.2F %.2F l', $x * $k, ($hp - $yc) * $k));
            if (strpos($corners, '1') === false) {
                $this->_out(sprintf('%.2F %.2F l %.2F %.2F l', $x * $k, ($hp - $y) * $k, ($x + $r) * $k, ($hp - $y) * $k));
            } else {
                $this->_Arc($xc - $r, $yc - $r * $MyArc, $xc - $r * $MyArc, $yc - $r, $xc, $yc - $r);
            }
            $this->_out($op);
        }

        private function _Arc($x1, $y1, $x2, $y2, $x3, $y3)
        {
            $h = $this->h;
            $this->_out(sprintf(
                '%.2F %.2F %.2F %.2F %.2F %.2F c ',
                $x1 * $this->k,
                ($h - $y1) * $this->k,
                $x2 * $this->k,
                ($h - $y2) * $this->k,
                $x3 * $this->k,
                ($h - $y3) * $this->k
            ));
        }
    }
}

if (!function_exists('hc_generate_card_number')) {
    /**
     * Generates a sequential Health Card Number: HC-YYYY-XXXX
     */
    function hc_generate_card_number(PDO $pdo): string
    {
        $year = date('Y');
        $prefix = "HC-{$year}-";
        $stmt = $pdo->prepare("SELECT card_number FROM health_cards WHERE card_number LIKE ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$prefix . '%']);
        $last = $stmt->fetchColumn();

        if ($last && preg_match('/HC-\d{4}-(\d+)/', $last, $m)) {
            $next = (int)$m[1] + 1;
        } else {
            $stmtCount = $pdo->query("SELECT COUNT(*) FROM health_cards");
            $next = ((int)$stmtCount->fetchColumn()) + 1;
        }

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('hc_render_card_pdf')) {
    /**
     * Renders a professional PVC Smart Health Card PDF with embedded QR Code & Photo
     */
    function hc_render_card_pdf(array $card, array $settings = [], string $outputMode = 'S', ?string $downloadName = null)
    {
        if (empty($settings)) {
            global $pdo;
            if (isset($pdo)) {
                $settings = mm_load_settings($pdo);
            }
        }

        $siteName = $settings['site_name'] ?? 'NGO HEALTH MISSION';
        $sitePhone = $settings['site_phone'] ?? $settings['ngo_phone'] ?? '+91 98765 43210';
        $siteEmail = $settings['site_email'] ?? $settings['ngo_email'] ?? 'health@ngo.org';
        $siteWeb = $settings['site_website'] ?? $settings['ngo_website'] ?? 'www.ngo.org';

        // Card Dimensions: Standard Landscape (85.6 mm x 54.0 mm)
        $cardW = 85.6;
        $cardH = 54.0;

        $pdf = new HealthCardPDF('L', 'mm', [$cardW, $cardH]);
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->AddPage();

        // 1. Card Outer Background with Rounded Border
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetDrawColor(15, 139, 141); // Teal Border
        $pdf->SetLineWidth(0.4);
        $pdf->RoundedRect(0.5, 0.5, $cardW - 1.0, $cardH - 1.0, 3.0, '1234', 'FD');

        // 2. Header Top Banner (Teal Gradient / Solid Fill)
        $pdf->SetFillColor(15, 139, 141);
        $pdf->RoundedRect(0.5, 0.5, $cardW - 1.0, 13.5, 3.0, '12', 'F');
        $pdf->Rect(0.5, 9.0, $cardW - 1.0, 5.0, 'F'); // square off bottom corners of header

        // Logo in Header
        $logoX = 2.5;
        $logoY = 2.0;
        $hasLogo = false;
        if (!empty($settings['ngo_logo'])) {
            $logoPath = __DIR__ . '/../' . ltrim($settings['ngo_logo'], '/');
            if (file_exists($logoPath) && is_file($logoPath)) {
                $preparedLogo = mm_prepare_image_for_fpdf($logoPath);
                if ($preparedLogo && file_exists($preparedLogo)) {
                    $pdf->SetFillColor(255, 255, 255);
                    $pdf->RoundedRect($logoX, $logoY, 9.5, 9.5, 1.5, '1234', 'F');
                    $pdf->Image($preparedLogo, $logoX + 0.5, $logoY + 0.5, 8.5, 8.5);
                    $hasLogo = true;
                }
            }
        }

        // Organization Name & Subtitle
        $textX = $hasLogo ? 13.5 : 3.5;
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 7.5);
        $pdf->SetXY($textX, 2.5);
        $pdf->Cell(52, 4.0, substr(strtoupper($siteName), 0, 34), 0, 1, 'L');

        $pdf->SetFont('Arial', 'B', 5.5);
        $pdf->SetTextColor(244, 166, 64); // Warm Gold
        $pdf->SetXY($textX, 6.5);
        $pdf->Cell(52, 3.0, 'SWASTHYA SEVA CARD / HEALTH CARD', 0, 1, 'L');

        $pdf->SetFont('Arial', '', 4.5);
        $pdf->SetTextColor(220, 252, 231);
        $pdf->SetXY($textX, 9.5);
        $pdf->Cell(52, 2.8, 'Empaneled Hospital & Pharmacy Concession', 0, 1, 'L');

        // Smart Chip / Badge on Top Right
        $pdf->SetFillColor(244, 166, 64);
        $pdf->RoundedRect($cardW - 16.5, 3.0, 14.0, 7.5, 1.5, '1234', 'F');
        $pdf->SetTextColor(20, 20, 20);
        $pdf->SetFont('Arial', 'B', 4.5);
        $pdf->SetXY($cardW - 16.5, 3.5);
        $pdf->Cell(14.0, 3.0, 'HEALTH ID', 0, 1, 'C');
        $pdf->SetFont('Arial', 'B', 4.0);
        $pdf->SetXY($cardW - 16.5, 6.8);
        $pdf->Cell(14.0, 2.8, 'VERIFIED', 0, 1, 'C');

        // 3. Middle Section: Photo (Left), Particulars (Center), QR Code (Right)

        // Photo on Left
        $photoX = 2.5;
        $photoY = 15.5;
        $photoW = 18.0;
        $photoH = 22.5;

        $pdf->SetDrawColor(203, 213, 225);
        $pdf->SetFillColor(241, 245, 249);
        $pdf->RoundedRect($photoX, $photoY, $photoW, $photoH, 1.5, '1234', 'FD');

        $hasPhoto = false;
        if (!empty($card['photo'])) {
            $photoPath = __DIR__ . '/../' . ltrim($card['photo'], '/');
            if (file_exists($photoPath) && is_file($photoPath)) {
                $prepPhoto = mm_prepare_image_for_fpdf($photoPath);
                if ($prepPhoto && file_exists($prepPhoto)) {
                    $pdf->Image($prepPhoto, $photoX + 0.5, $photoY + 0.5, $photoW - 1.0, $photoH - 1.0);
                    $hasPhoto = true;
                }
            }
        }
        if (!$hasPhoto) {
            $pdf->SetFont('Arial', '', 5.0);
            $pdf->SetTextColor(148, 163, 184);
            $pdf->SetXY($photoX, $photoY + 9.0);
            $pdf->Cell($photoW, 3.0, 'PHOTO', 0, 1, 'C');
        }

        // Center Particulars
        $infoX = 22.0;
        $infoY = 15.0;

        // Cardholder Name
        $pdf->SetTextColor(15, 23, 42); // Slate 900
        $pdf->SetFont('Arial', 'B', 7.5);
        $pdf->SetXY($infoX, $infoY);
        $pdf->Cell(44.0, 3.8, strtoupper(substr((string)$card['applicant_name'], 0, 24)), 0, 1, 'L');

        // Card Number Badge
        $pdf->SetFont('Arial', 'B', 6.0);
        $pdf->SetTextColor(15, 139, 141);
        $pdf->SetXY($infoX, $infoY + 4.0);
        $pdf->Cell(44.0, 3.2, 'CARD NO: ' . ($card['card_number'] ?: 'HC-PENDING'), 0, 1, 'L');

        // Details Grid
        $pdf->SetFont('Arial', '', 5.0);
        $pdf->SetTextColor(71, 85, 105);

        // Line 1: Gender / Age & Blood Group
        $genderAge = ($card['gender'] ?: 'Male') . (!empty($card['age']) ? (' / ' . $card['age'] . ' Y') : '');
        $pdf->SetXY($infoX, $infoY + 7.5);
        $pdf->Cell(22.0, 3.0, 'Gen/Age: ' . $genderAge, 0, 0, 'L');

        $bg = !empty($card['blood_group']) ? $card['blood_group'] : 'N/A';
        $pdf->SetFont('Arial', 'B', 5.2);
        $pdf->SetTextColor(225, 29, 72); // Rose Red
        $pdf->Cell(22.0, 3.0, 'Blood: ' . $bg, 0, 1, 'L');

        // Line 2: Contact Phone
        $pdf->SetFont('Arial', '', 5.0);
        $pdf->SetTextColor(71, 85, 105);
        $pdf->SetXY($infoX, $infoY + 10.8);
        $pdf->Cell(44.0, 3.0, 'Contact: ' . ($card['contact'] ?: '-'), 0, 1, 'L');

        // Line 3: Emergency Helpline
        if (!empty($card['emergency_contact'])) {
            $pdf->SetXY($infoX, $infoY + 14.0);
            $pdf->Cell(44.0, 3.0, 'Emergency: ' . $card['emergency_contact'], 0, 1, 'L');
        } else {
            $pdf->SetXY($infoX, $infoY + 14.0);
            $pdf->Cell(44.0, 3.0, 'Location: ' . substr(($card['district'] ? $card['district'] . ', ' : '') . ($card['state'] ?: 'India'), 0, 26), 0, 1, 'L');
        }

        // Line 4: Validity
        $issueFormatted = !empty($card['issue_date']) ? date('d/m/Y', strtotime((string)$card['issue_date'])) : date('d/m/Y');
        $expiryFormatted = !empty($card['expiry_date']) ? date('d/m/Y', strtotime((string)$card['expiry_date'])) : date('d/m/Y', strtotime('+1 year'));
        $pdf->SetFont('Arial', 'B', 4.8);
        $pdf->SetTextColor(16, 149, 106); // Emerald Green
        $pdf->SetXY($infoX, $infoY + 17.5);
        $pdf->Cell(44.0, 3.0, 'VALID: ' . $issueFormatted . ' TO ' . $expiryFormatted, 0, 1, 'L');

        // Right Box: Verification QR Code
        $qrX = $cardW - 19.5;
        $qrY = 15.5;
        $qrSize = 17.0;

        $verifyUrl = appBaseUrl() . "/verify-health-card.php?card=" . urlencode((string)$card['card_number']);
        $qrApiUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($verifyUrl);
        $qrLocalPath = mm_qr_image_url($verifyUrl);

        if (!empty($qrLocalPath)) {
            $prepQr = mm_prepare_image_for_fpdf($qrLocalPath);
            if ($prepQr && file_exists($prepQr)) {
                $pdf->SetFillColor(255, 255, 255);
                $pdf->SetDrawColor(203, 213, 225);
                $pdf->RoundedRect($qrX - 0.5, $qrY - 0.5, $qrSize + 1.0, $qrSize + 1.0, 1.5, '1234', 'FD');
                $pdf->Image($prepQr, $qrX, $qrY, $qrSize, $qrSize);
            }
        }

        $pdf->SetXY($qrX - 2.0, $qrY + $qrSize + 0.8);
        $pdf->SetFont('Arial', 'B', 4.0);
        $pdf->SetTextColor(15, 139, 141);
        $pdf->Cell($qrSize + 4.0, 2.5, 'SCAN TO VERIFY', 0, 1, 'C');

        // 4. Bottom Footer Ribbon (Helpline & Terms)
        $footerY = $cardH - 12.0;
        $pdf->SetFillColor(241, 245, 249);
        $pdf->SetDrawColor(226, 232, 240);
        $pdf->RoundedRect(1.5, $footerY, $cardW - 3.0, 10.5, 1.5, '1234', 'FD');

        $pdf->SetTextColor(71, 85, 105);
        $pdf->SetFont('Arial', '', 4.2);
        $pdf->SetXY(2.5, $footerY + 1.2);
        $pdf->Cell($cardW - 5.0, 2.6, 'Present this card at empaneled hospitals, eye/dental clinics & labs to avail NGO tie-up subsidies.', 0, 1, 'C');

        $pdf->SetFont('Arial', 'B', 4.2);
        $pdf->SetTextColor(15, 139, 141);
        $pdf->SetXY(2.5, $footerY + 4.2);
        $pdf->Cell($cardW - 5.0, 2.6, 'Helpline: ' . $sitePhone . '  |  Web: ' . $siteWeb . '  |  Non-Transferable', 0, 1, 'C');

        $pdf->SetFont('Arial', '', 3.8);
        $pdf->SetTextColor(148, 163, 184);
        $pdf->SetXY(2.5, $footerY + 7.0);
        $pdf->Cell($cardW - 5.0, 2.2, 'Authorised Signatory - Community Health Initiative', 0, 1, 'C');

        // Output formatting
        $fileName = $downloadName ?: ('Health_Card_' . preg_replace('/[^A-Za-z0-9\-]/', '', (string)$card['card_number']) . '.pdf');

        if ($outputMode === 'S') {
            return $pdf->Output('S');
        } elseif ($outputMode === 'I') {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $fileName . '"');
            $pdf->Output('I', $fileName);
            exit;
        } elseif ($outputMode === 'D') {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            $pdf->Output('D', $fileName);
            exit;
        }

        return $pdf->Output('S');
    }
}

if (!function_exists('hc_save_card_pdf')) {
    /**
     * Renders Health Card and writes to uploads/health_cards/ directory, returning relative path
     */
    function hc_save_card_pdf(PDO $pdo, int $cardId): string
    {
        $stmt = $pdo->prepare("SELECT * FROM health_cards WHERE id = ? LIMIT 1");
        $stmt->execute([$cardId]);
        $card = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$card) {
            throw new Exception("Health card with ID {$cardId} not found.");
        }

        $targetDir = __DIR__ . '/../uploads/health_cards';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $cleanCardNo = preg_replace('/[^A-Za-z0-9\-]/', '', (string)$card['card_number']);
        $pdfFileName = 'Health_Card_' . $cleanCardNo . '_' . time() . '.pdf';
        $pdfFullPath = $targetDir . DIRECTORY_SEPARATOR . $pdfFileName;
        $pdfRelPath = 'uploads/health_cards/' . $pdfFileName;

        $settings = mm_load_settings($pdo);
        $pdfBinary = hc_render_card_pdf($card, $settings, 'S');

        if (file_put_contents($pdfFullPath, $pdfBinary) === false) {
            throw new Exception("Failed to write Health Card PDF to {$pdfFullPath}");
        }

        $verifyUrl = appBaseUrl() . "/verify-health-card.php?card=" . urlencode((string)$card['card_number']);
        $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($verifyUrl);

        $upd = $pdo->prepare("UPDATE health_cards SET pdf_path = ?, qr_code_path = ?, updated_at = NOW() WHERE id = ?");
        $upd->execute([$pdfRelPath, $qrCodeUrl, $cardId]);

        return $pdfRelPath;
    }
}
