<?php
/**
 * includes/website_visitor_qr_helper.php
 * Website Visitor QR Generator Engine (PNG & Printable A4 Standee / Poster PDF).
 * Reuses existing QR generator library (mm_qr_image_url & FPDF).
 * Author: VELNIX SOFT / Antigravity AI
 * Date: 2026-09-12
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../libs/fpdf/fpdf.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/member_module.php';

if (!function_exists('get_website_visitor_qr_url')) {
    /**
     * Resolves the target website home page URL.
     */
    function get_website_visitor_qr_url(?string $customUrl = null): string
    {
        if ($customUrl && filter_var($customUrl, FILTER_VALIDATE_URL)) {
            return $customUrl;
        }
        return rtrim(appBaseUrl(), '/');
    }
}

if (!function_exists('get_website_visitor_qr_image_url')) {
    /**
     * Returns high-resolution QR image URL using existing QR generator pattern.
     */
    function get_website_visitor_qr_image_url(string $url, int $size = 500): string
    {
        return 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . urlencode($url);
    }
}

if (!function_exists('get_website_visitor_qr_local_file')) {
    /**
     * Downloads and caches the QR PNG image locally for PDF embedding or direct download.
     */
    function get_website_visitor_qr_local_file(string $url, int $size = 800): ?string
    {
        $hash = md5($url . '_' . $size);
        $tempDir = sys_get_temp_dir();
        $filePath = $tempDir . DIRECTORY_SEPARATOR . 'visitor_qr_' . $hash . '.png';

        if (file_exists($filePath) && filesize($filePath) > 500) {
            return $filePath;
        }

        $qrApiUrl = get_website_visitor_qr_image_url($url, $size);
        $imgData = false;

        if (function_exists('curl_init')) {
            $ch = curl_init($qrApiUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_FOLLOWLOCATION => true
            ]);
            $imgData = curl_exec($ch);
            curl_close($ch);
        }

        if (!$imgData || strlen($imgData) < 200) {
            $imgData = @file_get_contents($qrApiUrl);
        }

        if ($imgData && strlen($imgData) > 200) {
            file_put_contents($filePath, $imgData);
            return $filePath;
        }

        return null;
    }
}

if (!function_exists('generate_website_visitor_qr_pdf')) {
    /**
     * Generates a high-fidelity A4 Printable Poster / Counter Standee PDF.
     * Output Modes:
     * - 'D': Force download
     * - 'I': Inline browser preview
     * - 'S': Return binary string
     */
    function generate_website_visitor_qr_pdf(PDO $pdo, ?string $customUrl = null, string $outputMode = 'D')
    {
        // 1. Fetch Organization Settings
        $settings = [];
        try {
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Throwable $e) {
            $settings = [];
        }

        $siteName   = $settings['site_name'] ?? 'Jaysmrutti Foundation';
        $regNo      = $settings['reg_no'] ?? 'IV-190302390/2026';
        $ngoPhone   = $settings['ngo_phone'] ?? '+91 98765 43210';
        $ngoEmail   = $settings['ngo_email'] ?? 'contact@jaysmruttifoundation.org';
        $ngoAddress = $settings['ngo_address'] ?? 'Headquarters, India';
        $siteLogo   = $settings['site_logo'] ?? '';

        $targetUrl = get_website_visitor_qr_url($customUrl);
        $qrLocalPath = get_website_visitor_qr_local_file($targetUrl, 1000);

        // Bootstrap MMPdfLayout if available, or fallback to standard FPDF
        if (function_exists('mm_pdf_layout_bootstrap')) {
            mm_pdf_layout_bootstrap();
        }

        $pdfClass = class_exists('MMPdfLayout') ? 'MMPdfLayout' : 'FPDF';
        $pdf = new $pdfClass('P', 'mm', 'A4'); // 210 x 297 mm
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();

        // ── 1. Background & Outer Borders ───────────────────────────
        // Main outer frame (Teal #0F8B8D)
        $pdf->SetDrawColor(15, 139, 141);
        $pdf->SetLineWidth(1.2);
        if (method_exists($pdf, 'RoundedRect')) {
            $pdf->RoundedRect(8, 8, 194, 281, 6, 'D');
        } else {
            $pdf->Rect(8, 8, 194, 281, 'D');
        }

        // Inner Gold Border (#F4A640)
        $pdf->SetDrawColor(244, 166, 64);
        $pdf->SetLineWidth(0.6);
        if (method_exists($pdf, 'RoundedRect')) {
            $pdf->RoundedRect(11, 11, 188, 275, 4, 'D');
        } else {
            $pdf->Rect(11, 11, 188, 275, 'D');
        }

        // ── 2. Top Header Banner ────────────────────────────────────
        $pdf->SetFillColor(15, 139, 141);
        if (method_exists($pdf, 'RoundedRect')) {
            $pdf->RoundedRect(13, 13, 184, 38, 3, 'F');
        } else {
            $pdf->Rect(13, 13, 184, 38, 'F');
        }

        // Gold accent bottom line on header
        $pdf->SetFillColor(244, 166, 64);
        $pdf->Rect(13, 49, 184, 2, 'F');

        // Organization Logo
        $logoRendered = false;
        if (!empty($siteLogo)) {
            $fullLogoPath = __DIR__ . '/../' . ltrim($siteLogo, '/');
            if (file_exists($fullLogoPath)) {
                try {
                    $pdf->Image($fullLogoPath, 18, 16, 26, 26);
                    $logoRendered = true;
                } catch (Throwable $e) {}
            }
        }

        // Organization Name & Sub-details
        $headerTextX = $logoRendered ? 48 : 15;
        $headerTextW = $logoRendered ? 144 : 180;

        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 18);
        $pdf->SetXY($headerTextX, 17);
        $pdf->Cell($headerTextW, 8, strtoupper(utf8_decode($siteName)), 0, 1, 'C');

        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(204, 251, 241); // light teal
        $pdf->SetXY($headerTextX, 26);
        $pdf->Cell($headerTextW, 5, 'Govt. Registered NGO | Reg. No: ' . utf8_decode($regNo), 0, 1, 'C');

        $pdf->SetFont('Arial', 'I', 8.5);
        $pdf->SetTextColor(254, 240, 138); // light gold
        $pdf->SetXY($headerTextX, 32);
        $pdf->Cell($headerTextW, 5, 'Serving Humanity Through Swasthya, Education, Child Welfare & Empowerment', 0, 1, 'C');

        // ── 3. Call to Action Headline ──────────────────────────────
        $pdf->SetTextColor(15, 139, 141);
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->SetXY(15, 56);
        $pdf->Cell(180, 8, 'SCAN TO VISIT OFFICIAL WEBSITE', 0, 1, 'C');

        $pdf->SetTextColor(75, 85, 99);
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetXY(15, 64);
        $pdf->Cell(180, 5, utf8_decode('Scan with any Smartphone Camera / Google Lens to explore our online portal'), 0, 1, 'C');

        // ── 4. Central Large QR Code Display ────────────────────────
        $qrBoxX = 55;
        $qrBoxY = 73;
        $qrBoxSize = 100;

        // White shadow background card for QR
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetDrawColor(229, 231, 235);
        $pdf->SetLineWidth(0.8);
        if (method_exists($pdf, 'RoundedRect')) {
            $pdf->RoundedRect($qrBoxX, $qrBoxY, $qrBoxSize, $qrBoxSize, 6, 'DF');
        } else {
            $pdf->Rect($qrBoxX, $qrBoxY, $qrBoxSize, $qrBoxSize, 'DF');
        }

        // Inner frame with teal accent
        $pdf->SetDrawColor(15, 139, 141);
        $pdf->SetLineWidth(0.5);
        if (method_exists($pdf, 'RoundedRect')) {
            $pdf->RoundedRect($qrBoxX + 4, $qrBoxY + 4, $qrBoxSize - 8, $qrBoxSize - 8, 4, 'D');
        }

        // Embed QR Code Image
        if ($qrLocalPath && file_exists($qrLocalPath)) {
            $pdf->Image($qrLocalPath, $qrBoxX + 10, $qrBoxY + 10, $qrBoxSize - 20, $qrBoxSize - 20, 'PNG');
        } else {
            // Fallback placeholder text if external API is unreachable
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->SetTextColor(220, 38, 38);
            $pdf->SetXY($qrBoxX, $qrBoxY + 40);
            $pdf->Cell($qrBoxSize, 10, 'QR Code Generation Online', 0, 1, 'C');
        }

        // ── 5. Website Target URL Display ───────────────────────────
        $pdf->SetFillColor(240, 253, 253);
        $pdf->SetDrawColor(204, 251, 241);
        $pdf->SetLineWidth(0.5);
        if (method_exists($pdf, 'RoundedRect')) {
            $pdf->RoundedRect(35, 178, 140, 12, 3, 'DF');
        } else {
            $pdf->Rect(35, 178, 140, 12, 'DF');
        }

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(15, 139, 141);
        $pdf->SetXY(35, 179);
        $pdf->Cell(140, 10, utf8_decode($targetUrl), 0, 1, 'C', false, $targetUrl);

        // ── 6. Portal Services & Highlights ─────────────────────────
        $pdf->SetTextColor(31, 41, 55);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetXY(20, 196);
        $pdf->Cell(170, 6, 'WHAT YOU CAN DO ON OUR PORTAL:', 0, 1, 'C');

        $features = [
            ['icon' => '[+]', 'title' => 'Healthcare & Swasthya Card', 'desc' => 'Apply for health concession cards & find empaneled partner hospitals.'],
            ['icon' => '[*]', 'title' => 'Social Projects & Drives', 'desc' => 'Explore Green India, Stationery Bank & School Chalo campaigns.'],
            ['icon' => '[v]', 'title' => 'Join & Volunteer', 'desc' => 'Register as an official foundation volunteer or apply for open vacancies.'],
            ['icon' => '[$]', 'title' => '100% Tax-Exempt Donations', 'desc' => 'Contribute securely with instant 80G receipts and audit transparency.']
        ];

        $featY = 205;
        foreach ($features as $f) {
            // Pill Box
            $pdf->SetFillColor(248, 250, 252);
            $pdf->SetDrawColor(226, 232, 240);
            $pdf->SetLineWidth(0.3);
            if (method_exists($pdf, 'RoundedRect')) {
                $pdf->RoundedRect(22, $featY, 166, 11, 2, 'DF');
            } else {
                $pdf->Rect(22, $featY, 166, 11, 'DF');
            }

            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetTextColor(15, 139, 141);
            $pdf->SetXY(25, $featY + 1.5);
            $pdf->Cell(65, 4, utf8_decode($f['title']), 0, 0, 'L');

            $pdf->SetFont('Arial', '', 8);
            $pdf->SetTextColor(100, 116, 139);
            $pdf->Cell(95, 4, utf8_decode($f['desc']), 0, 1, 'L');

            $featY += 13;
        }

        // ── 7. Footer Contact & Information Bar ─────────────────────
        $pdf->SetFillColor(15, 139, 141);
        if (method_exists($pdf, 'RoundedRect')) {
            $pdf->RoundedRect(13, 260, 184, 25, 3, 'F');
        } else {
            $pdf->Rect(13, 260, 184, 25, 'F');
        }

        $pdf->SetFont('Arial', 'B', 8.5);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY(15, 262);
        $pdf->Cell(180, 4.5, utf8_decode('Helpline: ' . $ngoPhone . '  |  Email: ' . $ngoEmail), 0, 1, 'C');

        $pdf->SetFont('Arial', '', 7.5);
        $pdf->SetTextColor(204, 251, 241);
        $pdf->SetXY(15, 267);
        $pdf->Cell(180, 4.5, utf8_decode('Address: ' . $ngoAddress), 0, 1, 'C');

        $pdf->SetFont('Arial', 'I', 7);
        $pdf->SetTextColor(254, 240, 138);
        $pdf->SetXY(15, 273);
        $pdf->Cell(180, 4, utf8_decode('Generated on ' . date('d M Y') . ' • Official Website Visitor Standee • ' . $siteName), 0, 1, 'C');

        $fileName = 'Website_Visitor_QR_Poster_' . date('Ymd') . '.pdf';

        if ($outputMode === 'S') {
            return $pdf->Output('S');
        }

        if ($outputMode === 'I') {
            $pdf->Output('I', $fileName);
            exit;
        }

        $pdf->Output('D', $fileName);
        exit;
    }
}
