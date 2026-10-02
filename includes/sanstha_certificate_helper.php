<?php
// ============================================================
// includes/sanstha_certificate_helper.php
// Sanstha Authorization Certificate Generator & PDF Engine
// Author: VELNIX SOFT / Antigravity AI
// Date: 2026-09-12
// ============================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/member_module.php';
require_once __DIR__ . '/template_builder.php';
if (!class_exists('FPDF')) {
    require_once __DIR__ . '/../libs/fpdf/fpdf.php';
}

if (!function_exists('get_sanstha_auth_types')) {
    function get_sanstha_auth_types()
    {
        return [
            'Branch Office' => 'Branch Office (Shakha Karyalay)',
            'District Project Center' => 'District Project Center (Zila Karyalay)',
            'State Coordination Wing' => 'State Coordination Wing (Rajya Prabhag)',
            'Health & Diagnostic Unit' => 'Health & Diagnostic Partner Center',
            'Skill & Vocational Training' => 'Skill & Vocational Training Center',
            'Social Welfare & Relief Unit' => 'Social Welfare & Relief Unit',
            'Empaneled NGO Partner' => 'Empaneled NGO Partner Organization',
            'Authorized Franchisee Center' => 'Authorized Franchisee / Service Center'
        ];
    }
}

if (!function_exists('get_sanstha_scope_presets')) {
    function get_sanstha_scope_presets()
    {
        return [
            'general_branch' => [
                'title' => 'Standard Branch Office Operations',
                'scope' => 'Authorized to operate as an official Branch Office, conduct member enrollments, organize social awareness drives, coordinate welfare projects, and represent the organization within the assigned district jurisdiction.'
            ],
            'healthcare_wing' => [
                'title' => 'Healthcare & Diagnostic Operations',
                'scope' => 'Authorized to organize subsidized medical health camps, issue NGO Swasthya Cards, coordinate patient relief with empaneled hospitals, and deliver preventive healthcare initiatives.'
            ],
            'skill_education' => [
                'title' => 'Skill Development & Training Center',
                'scope' => 'Authorized to conduct certified vocational training, computer literacy programs, women empowerment workshops, and distribute recognized course completion certificates under official guidelines.'
            ],
            'welfare_relief' => [
                'title' => 'Disaster Relief & Social Welfare Unit',
                'scope' => 'Authorized to collect humanitarian aid, conduct food/clothing distribution drives, coordinate disaster relief operations, and deliver emergency assistance to underprivileged beneficiaries.'
            ],
            'partner_collaborator' => [
                'title' => 'Empaneled Institutional Partner',
                'scope' => 'Authorized as an institutional collaborative partner to implement joint developmental programs, CSR social projects, and community upliftment campaigns in full compliance with NGO bylaws.'
            ]
        ];
    }
}

if (!function_exists('get_sanstha_color_palettes')) {
    function get_sanstha_color_palettes()
    {
        return [
            1 => [
                'name' => 'Royal Sapphire & Gold',
                'primary' => [15, 44, 89],      // Deep Navy #0F2C59
                'secondary' => [197, 145, 22],  // Royal Gold #C59116
                'accent' => [248, 244, 230],    // Warm Cream Parchment
                'text' => [20, 25, 35]
            ],
            2 => [
                'name' => 'Imperial Emerald & Bronze',
                'primary' => [11, 77, 60],      // Deep Forest Emerald #0B4D3C
                'secondary' => [180, 130, 40],  // Bronze Gold
                'accent' => [240, 248, 245],    // Mint Silk
                'text' => [15, 30, 25]
            ],
            3 => [
                'name' => 'Regal Maroon & Gold',
                'primary' => [128, 20, 35],     // Deep Crimson/Maroon #801423
                'secondary' => [212, 160, 23],  // Rich Gold
                'accent' => [255, 248, 245],    // Soft Rose Parchment
                'text' => [30, 15, 20]
            ],
            4 => [
                'name' => 'Corporate Slate & Platinum',
                'primary' => [30, 41, 59],      // Slate Blue-Gray #1E293B
                'secondary' => [79, 70, 229],   // Indigo Accent #4F46E5
                'accent' => [248, 250, 252],    // Ice White
                'text' => [15, 23, 42]
            ],
            5 => [
                'name' => 'Majestic Purple & Champagne',
                'primary' => [76, 29, 149],     // Deep Royal Purple #4C1D95
                'secondary' => [217, 119, 6],   // Amber Gold #D97706
                'accent' => [250, 245, 255],    // Lavender Silk
                'text' => [30, 15, 45]
            ],
            6 => [
                'name' => 'Oceanic Teal & Copper',
                'primary' => [13, 110, 110],    // Deep Teal #0D6E6E
                'secondary' => [194, 101, 28],  // Warm Copper #C2651C
                'accent' => [240, 253, 250],    // Seafoam Cream
                'text' => [10, 30, 30]
            ],
        ];
    }
}

if (!function_exists('generate_sanstha_cert_no')) {
    function generate_sanstha_cert_no(PDO $pdo)
    {
        $year = date('Y');
        $prefix = "AUTH-SANSTHA-{$year}-";

        if (dbTableExists($pdo, 'sanstha_certificates')) {
            $stmt = $pdo->prepare("SELECT certificate_no FROM sanstha_certificates WHERE certificate_no LIKE ? ORDER BY id DESC LIMIT 1");
            $stmt->execute([$prefix . '%']);
            $last = $stmt->fetchColumn();
            if ($last && preg_match('/-(\d{4,5})$/', $last, $m)) {
                $nextSeq = (int)$m[1] + 1;
                return $prefix . str_pad((string)$nextSeq, 4, '0', STR_PAD_LEFT);
            }
        }
        return $prefix . '0001';
    }
}

if (!function_exists('sanstha_generate_qr')) {
    function sanstha_generate_qr($data)
    {
        $url = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($data);
        if (function_exists('mm_fetch_remote_image')) {
            return mm_fetch_remote_image($url);
        }
        $file = sys_get_temp_dir() . '/qr_' . md5($data) . '.png';
        if (!file_exists($file)) {
            if (function_exists('curl_init')) {
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                $qrData = curl_exec($ch);
                curl_close($ch);
                if ($qrData !== false && $qrData !== '') {
                    file_put_contents($file, $qrData);
                    return $file;
                }
            }
            $qrData = @file_get_contents($url);
            if ($qrData === false) {
                return null;
            }
            file_put_contents($file, $qrData);
        }
        return $file;
    }
}

if (!function_exists('generate_sanstha_certificate_pdf')) {
    function generate_sanstha_certificate_pdf(PDO $pdo, $certDataOrId, $outputMode = 'S')
    {
        $cert = [];
        if (is_numeric($certDataOrId)) {
            $stmt = $pdo->prepare("SELECT * FROM sanstha_certificates WHERE id = ? LIMIT 1");
            $stmt->execute([(int)$certDataOrId]);
            $cert = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$cert) {
                throw new RuntimeException("Sanstha Certificate not found with ID: $certDataOrId");
            }
        } elseif (is_array($certDataOrId)) {
            $cert = $certDataOrId;
        } else {
            throw new InvalidArgumentException("Invalid argument provided to generate_sanstha_certificate_pdf");
        }

        $settings = mm_load_settings($pdo);
        $certNo = $cert['certificate_no'] ?? ('AUTH-SANSTHA-' . date('Y') . '-0001');

        // Build Public Verification URL
        $verifyBase = ($settings['ngo_website'] ?? '') ? rtrim($settings['ngo_website'], '/') : '';
        if ($verifyBase === '') {
            $verifyBase = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        }
        $verifyUrl = $verifyBase . '/verify-sanstha-certificate.php?scert=' . urlencode($certNo);
        $qrPayload = $verifyUrl;

        // Check if custom template from Template Builder is active or requested
        $templateId = (int)($cert['template_id'] ?? 0);
        $customTemplate = null;
        if ($templateId > 0) {
            $customTemplate = tb_load_template_by_id($pdo, $templateId, 'sanstha_authorization');
        } else {
            $customTemplate = tb_load_active_template($pdo, 'sanstha_authorization');
        }

        if ($customTemplate) {
            $context = tb_sanstha_pdf_context($cert, $settings, $certNo, $qrPayload);
            $pdfContent = tb_render_template_pdf($pdo, $customTemplate, $context);

            if ($outputMode === 'F') {
                $dir = __DIR__ . '/../uploads/documents/sanstha_certificates';
                if (!is_dir($dir)) mkdir($dir, 0777, true);
                $filename = 'Sanstha_Certificate_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $certNo) . '.pdf';
                $fullPath = $dir . '/' . $filename;
                file_put_contents($fullPath, $pdfContent);
                $relPath = 'uploads/documents/sanstha_certificates/' . $filename;
                if (!empty($cert['id'])) {
                    $uStmt = $pdo->prepare("UPDATE sanstha_certificates SET pdf_path = ?, qr_payload = ? WHERE id = ?");
                    $uStmt->execute([$relPath, $qrPayload, (int)$cert['id']]);
                }
                return $relPath;
            } elseif ($outputMode === 'D') {
                if (ob_get_length()) ob_end_clean();
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="Sanstha_Certificate_' . $certNo . '.pdf"');
                echo $pdfContent;
                exit;
            } elseif ($outputMode === 'I') {
                if (ob_get_length()) ob_end_clean();
                header('Content-Type: application/pdf');
                header('Content-Disposition: inline; filename="Sanstha_Certificate_' . $certNo . '.pdf"');
                echo $pdfContent;
                exit;
            }
            return $pdfContent;
        }

        // ============================================================
        // CLASSIC ROYAL BUILT-IN GENERATOR (Landscape A4: 297 x 210 mm)
        // ============================================================
        $templateNo = (int)($cert['template_no'] ?? 1);
        $palettes = get_sanstha_color_palettes();
        $palette = $palettes[$templateNo] ?? $palettes[1];

        $pri = $palette['primary'];
        $sec = $palette['secondary'];
        $acc = $palette['accent'];
        $txt = $palette['text'];

if (!class_exists('SansthaCertificatePDF')) {
    class SansthaCertificatePDF extends FPDF
    {
        public function RoundedRect($x, $y, $w, $h, $r, $style = '', $corners = '1234')
        {
            $k = $this->k;
            $hp = $this->h;
            if ($style == 'F')
                $op = 'f';
            elseif ($style == 'FD' || $style == 'DF')
                $op = 'B';
            else
                $op = 'S';
            $MyArc = 4 / 3 * (sqrt(2) - 1);
            $this->_out(sprintf('%.2F %.2F m', ($x + $r) * $k, ($hp - $y) * $k));

            $xc = $x + $w - $r;
            $yc = $y + $r;
            $this->_out(sprintf('%.2F %.2F l', $xc * $k, ($hp - $y) * $k));
            if (strpos($corners, '2') === false)
                $this->_out(sprintf('%.2F %.2F l', ($x + $w) * $k, ($hp - $y) * $k));
            else
                $this->_Arc($xc + $r * $MyArc, $yc - $r, $xc + $r, $yc - $r * $MyArc, $xc + $r, $yc);

            $xc = $x + $w - $r;
            $yc = $y + $h - $r;
            $this->_out(sprintf('%.2F %.2F l', ($x + $w) * $k, ($hp - $yc) * $k));
            if (strpos($corners, '3') === false)
                $this->_out(sprintf('%.2F %.2F l', ($x + $w) * $k, ($hp - ($y + $h)) * $k));
            else
                $this->_Arc($xc + $r, $yc + $r * $MyArc, $xc + $r * $MyArc, $yc + $r, $xc, $yc + $r);

            $xc = $x + $r;
            $yc = $y + $h - $r;
            $this->_out(sprintf('%.2F %.2F l', $xc * $k, ($hp - ($y + $h)) * $k));
            if (strpos($corners, '4') === false)
                $this->_out(sprintf('%.2F %.2F l', $x * $k, ($hp - ($y + $h)) * $k));
            else
                $this->_Arc($xc - $r * $MyArc, $yc + $r, $xc - $r, $yc + $r * $MyArc, $xc - $r, $yc);

            $xc = $x + $r;
            $yc = $y + $r;
            $this->_out(sprintf('%.2F %.2F l', $x * $k, ($hp - $yc) * $k));
            if (strpos($corners, '1') === false) {
                $this->_out(sprintf('%.2F %.2F l', $x * $k, ($hp - $y) * $k));
                $this->_out(sprintf('%.2F %.2F l', ($x + $r) * $k, ($hp - $y) * $k));
            } else
                $this->_Arc($xc - $r, $yc - $r * $MyArc, $xc - $r * $MyArc, $yc - $r, $xc, $yc - $r);
            $this->_out($op);
        }

        public function _Arc($x1, $y1, $x2, $y2, $x3, $y3)
        {
            $h = $this->h;
            $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c ', $x1 * $this->k, ($h - $y1) * $this->k, $x2 * $this->k, ($h - $y2) * $this->k, $x3 * $this->k, ($h - $y3) * $this->k));
        }

        public function Circle($x, $y, $r, $style = 'D')
        {
            $this->Ellipse($x, $y, $r, $r, $style);
        }

        public function Ellipse($x, $y, $rx, $ry, $style = 'D')
        {
            if ($style == 'F')
                $op = 'f';
            elseif ($style == 'FD' || $style == 'DF')
                $op = 'B';
            else
                $op = 'S';
            $lx = 4 / 3 * (sqrt(2) - 1) * $rx;
            $ly = 4 / 3 * (sqrt(2) - 1) * $ry;
            $k = $this->k;
            $h = $this->h;
            $this->_out(sprintf('%.2F %.2F m', ($x + $rx) * $k, ($h - $y) * $k));
            $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c', ($x + $rx) * $k, ($h - ($y - $ly)) * $k, ($x + $lx) * $k, ($h - ($y - $ry)) * $k, $x * $k, ($h - ($y - $ry)) * $k));
            $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c', ($x - $lx) * $k, ($h - ($y - $ry)) * $k, ($x - $rx) * $k, ($h - ($y - $ly)) * $k, ($x - $rx) * $k, ($h - $y) * $k));
            $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c', ($x - $rx) * $k, ($h - ($y + $ly)) * $k, ($x - $lx) * $k, ($h - ($y + $ry)) * $k, $x * $k, ($h - ($y + $ry)) * $k));
            $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c %s', ($x + $lx) * $k, ($h - ($y + $ry)) * $k, ($x + $rx) * $k, ($h - ($y + $ly)) * $k, ($x + $rx) * $k, ($h - $y) * $k, $op));
        }
    }
}

        $pdf = new SansthaCertificatePDF('L', 'mm', 'A4');
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();

        // 1. Base Warm Background Fill
        $pdf->SetFillColor($acc[0], $acc[1], $acc[2]);
        $pdf->Rect(0, 0, 297, 210, 'F');

        // 2. Outer Ornate Border
        $pdf->SetDrawColor($pri[0], $pri[1], $pri[2]);
        $pdf->SetLineWidth(3.0);
        $pdf->Rect(7, 7, 283, 196, 'D');

        // 3. Inner Double Gold Filigree Border
        $pdf->SetDrawColor($sec[0], $sec[1], $sec[2]);
        $pdf->SetLineWidth(1.0);
        $pdf->Rect(10, 10, 277, 190, 'D');
        $pdf->SetLineWidth(0.4);
        $pdf->Rect(11.5, 11.5, 274, 187, 'D');

        // 4. Corner Ornaments (Classic Geometric Flourish)
        $corners = [
            [10, 10, 1, 1],
            [287, 10, -1, 1],
            [10, 200, 1, -1],
            [287, 200, -1, -1]
        ];
        foreach ($corners as $c) {
            $cx = $c[0]; $cy = $c[1]; $dx = $c[2]; $dy = $c[3];
            $pdf->SetFillColor($pri[0], $pri[1], $pri[2]);
            $pdf->Rect($cx, $cy, $dx * 9, $dy * 9, 'F');
            $pdf->SetFillColor($sec[0], $sec[1], $sec[2]);
            $pdf->Rect($cx + ($dx * 2), $cy + ($dy * 2), $dx * 5, $dy * 5, 'F');
        }

        // 5. Header: NGO Logo & Official Name
        $logoX = 18;
        $logoY = 16;
        $logoW = 28;
        $logoH = 28;

        if (!empty($settings['ngo_logo'])) {
            $logoLocal = mm_prepare_image_for_fpdf('../' . $settings['ngo_logo']);
            if (!$logoLocal || !file_exists($logoLocal)) {
                $logoLocal = mm_prepare_image_for_fpdf($settings['ngo_logo']);
            }
            if ($logoLocal && file_exists($logoLocal)) {
                $pdf->Image($logoLocal, $logoX, $logoY, $logoW, $logoH);
            }
        }

        // Top Certificate Identification Bar (Right aligned)
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetTextColor($pri[0], $pri[1], $pri[2]);
        $pdf->SetXY(180, 15);
        $pdf->Cell(100, 5, 'AUTH REF: ' . $certNo, 0, 1, 'R');
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(100, 110, 120);
        $pdf->SetXY(180, 20);
        $pdf->Cell(100, 4, 'DATE OF ISSUE: ' . date('d M Y', strtotime($cert['valid_from'] ?? 'now')), 0, 1, 'R');
        if (!empty($settings['ngo_reg_no']) || !empty($settings['reg_no'])) {
            $regNo = $settings['ngo_reg_no'] ?? ($settings['reg_no'] ?? '');
            $pdf->SetXY(180, 24);
            $pdf->Cell(100, 4, 'GOVT REG NO: ' . $regNo, 0, 1, 'R');
        }

        // Center: NGO Name & Tagline
        $pdf->SetXY(48, 16);
        $pdf->SetFont('Times', 'B', 19);
        $pdf->SetTextColor($pri[0], $pri[1], $pri[2]);
        $orgName = strtoupper($settings['site_name'] ?? 'NATIONAL SOCIAL WELFARE ORGANIZATION');
        $pdf->Cell(200, 7, $orgName, 0, 1, 'C');

        $pdf->SetXY(48, 23);
        $pdf->SetFont('Arial', 'I', 8.5);
        $pdf->SetTextColor(110, 120, 130);
        $tagline = $settings['site_tagline'] ?? 'Registered Under Section 8 / Indian Trusts Act • Committed to Human Welfare';
        $pdf->Cell(200, 4, $tagline, 0, 1, 'C');

        // Decorative Header Separator
        $pdf->SetDrawColor($sec[0], $sec[1], $sec[2]);
        $pdf->SetLineWidth(0.7);
        $pdf->Line(40, 31, 257, 31);
        $pdf->SetFillColor($sec[0], $sec[1], $sec[2]);
        $pdf->Circle(148.5, 31, 1.8, 'F');

        // 6. Main Certificate Title Ribbon Banner
        $pdf->SetFillColor($pri[0], $pri[1], $pri[2]);
        $pdf->Rect(38, 36, 221, 12, 'F');
        $pdf->SetFillColor($sec[0], $sec[1], $sec[2]);
        $pdf->Rect(40, 37.5, 217, 9, 'D');

        $pdf->SetXY(38, 38);
        $pdf->SetFont('Times', 'B', 13);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(221, 8, 'CERTIFICATE OF SANSTHA AUTHORIZATION', 0, 1, 'C');

        // 7. Preamble Line
        $pdf->SetXY(20, 52);
        $pdf->SetFont('Arial', 'I', 10.5);
        $pdf->SetTextColor(80, 85, 95);
        $pdf->Cell(257, 5, 'This is to officially certify that the Governing Council has conferred institutional authorization upon:', 0, 1, 'C');

        // 8. Sanstha / Center Name (Prominent Gold & Navy Title)
        $sansthaName = strtoupper((string)($cert['sanstha_name'] ?? 'AUTHORIZED CENTER'));
        $pdf->SetXY(20, 60);
        $pdf->SetFont('Times', 'B', 21);
        $pdf->SetTextColor($pri[0], $pri[1], $pri[2]);
        $pdf->Cell(257, 9, $sansthaName, 0, 1, 'C');

        // Underline under Sanstha Name
        $pdf->SetDrawColor($sec[0], $sec[1], $sec[2]);
        $pdf->SetLineWidth(0.6);
        $pdf->Line(65, 70.5, 232, 70.5);

        // 9. Authorization Category & In-Charge Box
        $authType = (string)($cert['auth_type'] ?? 'Branch Office');
        $authPerson = (string)($cert['authorized_person'] ?? 'Authorized In-Charge');
        $desig = (string)($cert['designation'] ?? 'Center Head');

        $pdf->SetXY(20, 73);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetTextColor($sec[0], $sec[1], $sec[2]);
        $pdf->Cell(257, 5.5, strtoupper($authType), 0, 1, 'C');

        $pdf->SetXY(20, 79);
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(40, 45, 55);
        $personLine = "Under the Supervision & Charge of: " . $authPerson . " (" . $desig . ")";
        $pdf->Cell(257, 5, $personLine, 0, 1, 'C');

        // Center Full Address
        $addressParts = [];
        if (!empty($cert['center_address'])) $addressParts[] = $cert['center_address'];
        if (!empty($cert['city'])) $addressParts[] = $cert['city'];
        if (!empty($cert['district']) && $cert['district'] !== ($cert['city'] ?? '')) $addressParts[] = $cert['district'];
        if (!empty($cert['state'])) $addressParts[] = $cert['state'];
        if (!empty($cert['pincode'])) $addressParts[] = 'PIN: ' . $cert['pincode'];
        $formattedAddress = implode(', ', $addressParts);

        if ($formattedAddress !== '') {
            $pdf->SetXY(25, 85);
            $pdf->SetFont('Arial', 'I', 8.5);
            $pdf->SetTextColor(90, 95, 105);
            $pdf->Cell(247, 4.5, "Center Location: " . $formattedAddress, 0, 1, 'C');
        }

        // 10. Scope of Work / Jurisdiction Panel (Framed Box)
        $scopeY = 92;
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetDrawColor(215, 220, 230);
        $pdf->SetLineWidth(0.4);
        $pdf->RoundedRect(22, $scopeY, 253, 27, 2, 'DF');

        // Scope Box Header
        $pdf->SetFillColor($pri[0], $pri[1], $pri[2]);
        $pdf->SetXY(22, $scopeY);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor($sec[0], $sec[1], $sec[2]);
        $pdf->Cell(253, 5, "  TERMS OF AUTHORIZATION & JURISDICTIONAL SCOPE", 0, 1, 'L');

        // Scope Text
        $scopeText = (string)($cert['scope_of_work'] ?? 'Authorized to conduct official institutional operations, public awareness, and welfare projects.');
        $pdf->SetXY(25, $scopeY + 6);
        $pdf->SetFont('Arial', '', 8.5);
        $pdf->SetTextColor(40, 45, 55);
        $pdf->MultiCell(247, 4.2, $scopeText, 0, 'L');

        // 11. Validity Timeline & Contact Info Row
        $validFrom = !empty($cert['valid_from']) ? date('d M Y', strtotime($cert['valid_from'])) : date('d M Y');
        $validUntil = !empty($cert['valid_until']) ? date('d M Y', strtotime($cert['valid_until'])) : 'Perpetual / Subject to Annual Review';

        $timelineY = 123;
        $pdf->SetXY(22, $timelineY);
        $pdf->SetFont('Arial', 'B', 8.5);
        $pdf->SetTextColor($pri[0], $pri[1], $pri[2]);
        $pdf->Cell(130, 4.5, "VALIDITY: " . $validFrom . "  to  " . $validUntil, 0, 0, 'L');

        $contactInfo = "";
        if (!empty($cert['contact_phone'])) $contactInfo .= "Helpline: " . $cert['contact_phone'];
        if (!empty($cert['contact_email'])) $contactInfo .= ($contactInfo ? " | " : "") . "Email: " . $cert['contact_email'];
        if ($contactInfo) {
            $pdf->SetFont('Arial', '', 8);
            $pdf->SetTextColor(100, 105, 115);
            $pdf->Cell(123, 4.5, $contactInfo, 0, 1, 'R');
        } else {
            $pdf->Ln(4.5);
        }

        // 12. Bottom Security & Signature Section (QR + Signatures)
        $botY = 135;

        // Dynamic QR Code (Left)
        $qrImg = sanstha_generate_qr($qrPayload);
        if ($qrImg && file_exists($qrImg)) {
            $pdf->Image($qrImg, 24, $botY + 2, 28, 28);
            $pdf->SetXY(20, $botY + 31);
            $pdf->SetFont('Arial', 'B', 6.5);
            $pdf->SetTextColor($pri[0], $pri[1], $pri[2]);
            $pdf->Cell(36, 3.5, "SCAN TO VERIFY", 0, 1, 'C');
            $pdf->SetFont('Arial', '', 6);
            $pdf->SetTextColor(130, 135, 145);
            $pdf->Cell(36, 3, "Digital Sanstha Portal", 0, 0, 'C');
        }

        // Center: Official Holographic Style Security Seal
        $sealX = 148.5;
        $sealY = $botY + 14;
        $pdf->SetDrawColor($sec[0], $sec[1], $sec[2]);
        $pdf->SetLineWidth(0.6);
        $pdf->Circle($sealX, $sealY, 13, 'D');
        $pdf->SetLineWidth(0.3);
        $pdf->Circle($sealX, $sealY, 11, 'D');
        $pdf->SetFont('Arial', 'B', 6.5);
        $pdf->SetTextColor($pri[0], $pri[1], $pri[2]);
        $pdf->SetXY($sealX - 12, $sealY - 6);
        $pdf->Cell(24, 3, "GOVERNING", 0, 1, 'C');
        $pdf->SetXY($sealX - 12, $sealY - 2.5);
        $pdf->Cell(24, 3, "COUNCIL", 0, 1, 'C');
        $pdf->SetFont('Arial', 'B', 6);
        $pdf->SetTextColor($sec[0], $sec[1], $sec[2]);
        $pdf->SetXY($sealX - 12, $sealY + 1.5);
        $pdf->Cell(24, 3, "★ OFFICIAL ★", 0, 1, 'C');
        $pdf->SetXY($sealX - 12, $sealY + 4.5);
        $pdf->Cell(24, 3, "SANSTHA SEAL", 0, 1, 'C');

        // Right 1: General Secretary Signature
        $sig1X = 185;
        $pdf->SetDrawColor(180, 190, 205);
        $pdf->SetLineWidth(0.5);
        $pdf->Line($sig1X, $botY + 22, $sig1X + 40, $botY + 22);

        $pdf->SetXY($sig1X, $botY + 23);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor($pri[0], $pri[1], $pri[2]);
        $pdf->Cell(40, 3.5, "General Secretary", 0, 1, 'C');
        $pdf->SetXY($sig1X, $botY + 26.5);
        $pdf->SetFont('Arial', '', 7);
        $pdf->SetTextColor(110, 115, 125);
        $pdf->Cell(40, 3, "Admin & Operations", 0, 1, 'C');

        // Right 2: President / Director Signature & Official Signature Image
        $sig2X = 232;
        if (!empty($settings['ngo_signature'])) {
            $sigPath = mm_prepare_image_for_fpdf('../' . $settings['ngo_signature']);
            if (!$sigPath || !file_exists($sigPath)) {
                $sigPath = mm_prepare_image_for_fpdf($settings['ngo_signature']);
            }
            if ($sigPath && file_exists($sigPath)) {
                $pdf->Image($sigPath, $sig2X + 5, $botY + 7, 30, 13);
            }
        }

        $pdf->Line($sig2X, $botY + 22, $sig2X + 40, $botY + 22);
        $pdf->SetXY($sig2X, $botY + 23);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor($pri[0], $pri[1], $pri[2]);
        $pdf->Cell(40, 3.5, "President / Director", 0, 1, 'C');
        $pdf->SetXY($sig2X, $botY + 26.5);
        $pdf->SetFont('Arial', '', 7);
        $pdf->SetTextColor(110, 115, 125);
        $pdf->Cell(40, 3, "Authorized Signatory", 0, 1, 'C');

        // 13. Bottom Legal Disclaimer Footer
        $pdf->SetXY(20, 196);
        $pdf->SetFont('Arial', 'I', 6.8);
        $pdf->SetTextColor(130, 135, 145);
        $pdf->Cell(257, 3.5, "This official certificate confirms institutional authorization under the registered constitution of the Organization. Valid solely for legal, public welfare & approved operations.", 0, 1, 'C');

        // Output logic
        if ($outputMode === 'F') {
            $dir = __DIR__ . '/../uploads/documents/sanstha_certificates';
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            $filename = 'Sanstha_Certificate_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $certNo) . '.pdf';
            $fullPath = $dir . '/' . $filename;
            $pdf->Output('F', $fullPath);
            $relPath = 'uploads/documents/sanstha_certificates/' . $filename;
            if (!empty($cert['id'])) {
                $uStmt = $pdo->prepare("UPDATE sanstha_certificates SET pdf_path = ?, qr_payload = ? WHERE id = ?");
                $uStmt->execute([$relPath, $qrPayload, (int)$cert['id']]);
            }
            return $relPath;
        } elseif ($outputMode === 'D') {
            if (ob_get_length()) ob_end_clean();
            $pdf->Output('D', 'Sanstha_Certificate_' . $certNo . '.pdf');
            exit;
        } elseif ($outputMode === 'I') {
            if (ob_get_length()) ob_end_clean();
            $pdf->Output('I', 'Sanstha_Certificate_' . $certNo . '.pdf');
            exit;
        }

        return $pdf->Output('S');
    }
}
