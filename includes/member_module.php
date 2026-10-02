<?php

if (!function_exists('mm_load_settings')) {
    function mm_load_settings(PDO $pdo)
    {
        $settings = [];
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }
}

if (!function_exists('mm_ensure_member_identity_columns')) {
    function mm_ensure_member_identity_columns(PDO $pdo): bool
    {
        $ok = true;

        try {
            if (!dbColumnExists($pdo, 'members', 'photo')) {
                $pdo->exec("ALTER TABLE members ADD COLUMN photo varchar(255) DEFAULT NULL AFTER address");
            }
        } catch (Throwable $e) {
            $ok = false;
        }

        try {
            if (!dbColumnExists($pdo, 'members', 'blood_group')) {
                $pdo->exec("ALTER TABLE members ADD COLUMN blood_group varchar(10) DEFAULT NULL AFTER phone");
            }
        } catch (Throwable $e) {
            $ok = false;
        }

        return $ok;
    }
}

if (!function_exists('mm_ensure_member_registration_columns')) {
    function mm_ensure_member_registration_columns(PDO $pdo): bool
    {
        $ok = true;

        $columns = [
            'qualification' => "ALTER TABLE members ADD COLUMN qualification varchar(150) DEFAULT NULL AFTER gender",
            'profession' => "ALTER TABLE members ADD COLUMN profession varchar(150) DEFAULT NULL AFTER qualification",
            'marital_status' => "ALTER TABLE members ADD COLUMN marital_status varchar(30) DEFAULT NULL AFTER profession",
            'district' => "ALTER TABLE members ADD COLUMN district varchar(100) DEFAULT NULL AFTER address",
            'state' => "ALTER TABLE members ADD COLUMN state varchar(100) DEFAULT NULL AFTER district",
            'local_body_type' => "ALTER TABLE members ADD COLUMN local_body_type varchar(30) DEFAULT NULL AFTER state",
            'local_body_name' => "ALTER TABLE members ADD COLUMN local_body_name varchar(150) DEFAULT NULL AFTER local_body_type",
            'ward_no' => "ALTER TABLE members ADD COLUMN ward_no varchar(20) DEFAULT NULL AFTER local_body_name",
            'ward_name' => "ALTER TABLE members ADD COLUMN ward_name varchar(150) DEFAULT NULL AFTER ward_no",
            'kudumbha_samithi' => "ALTER TABLE members ADD COLUMN kudumbha_samithi varchar(150) DEFAULT NULL AFTER ward_name",
        ];

        foreach ($columns as $column => $sql) {
            try {
                if (!dbColumnExists($pdo, 'members', $column)) {
                    $pdo->exec($sql);
                }
            } catch (Throwable $e) {
                $ok = false;
            }
        }

        return $ok;
    }
}

if (!function_exists('mm_ensure_volunteer_registration_columns')) {
    function mm_ensure_volunteer_registration_columns(PDO $pdo): bool
    {
        $ok = true;

        $columns = [
            'qualification' => "ALTER TABLE volunteers ADD COLUMN qualification varchar(150) DEFAULT NULL AFTER phone",
            'profession' => "ALTER TABLE volunteers ADD COLUMN profession varchar(150) DEFAULT NULL AFTER qualification",
            'marital_status' => "ALTER TABLE volunteers ADD COLUMN marital_status varchar(30) DEFAULT NULL AFTER profession",
            'district' => "ALTER TABLE volunteers ADD COLUMN district varchar(100) DEFAULT NULL AFTER address",
            'state' => "ALTER TABLE volunteers ADD COLUMN state varchar(100) DEFAULT NULL AFTER district",
            'local_body_type' => "ALTER TABLE volunteers ADD COLUMN local_body_type varchar(30) DEFAULT NULL AFTER state",
            'local_body_name' => "ALTER TABLE volunteers ADD COLUMN local_body_name varchar(150) DEFAULT NULL AFTER local_body_type",
            'ward_no' => "ALTER TABLE volunteers ADD COLUMN ward_no varchar(20) DEFAULT NULL AFTER local_body_name",
            'ward_name' => "ALTER TABLE volunteers ADD COLUMN ward_name varchar(150) DEFAULT NULL AFTER ward_no",
            'kudumbha_samithi' => "ALTER TABLE volunteers ADD COLUMN kudumbha_samithi varchar(150) DEFAULT NULL AFTER ward_name",
        ];

        foreach ($columns as $column => $sql) {
            try {
                if (!dbColumnExists($pdo, 'volunteers', $column)) {
                    $pdo->exec($sql);
                }
            } catch (Throwable $e) {
                $ok = false;
            }
        }

        return $ok;
    }
}

if (!function_exists('mm_ensure_member_event_columns')) {
    function mm_ensure_member_event_columns(PDO $pdo): bool
    {
        $ok = true;

        $columns = [
            'event_id' => "ALTER TABLE members ADD COLUMN event_id int(11) DEFAULT NULL AFTER designation_id",
            'event_title' => "ALTER TABLE members ADD COLUMN event_title varchar(255) DEFAULT NULL AFTER event_id",
            'event_date' => "ALTER TABLE members ADD COLUMN event_date date DEFAULT NULL AFTER event_title",
            'event_location' => "ALTER TABLE members ADD COLUMN event_location varchar(500) DEFAULT NULL AFTER event_date",
            'occasion_name' => "ALTER TABLE members ADD COLUMN occasion_name varchar(255) DEFAULT NULL AFTER event_location",
            'achievement_position' => "ALTER TABLE members ADD COLUMN achievement_position varchar(120) DEFAULT NULL AFTER occasion_name",
        ];

        foreach ($columns as $column => $sql) {
            try {
                if (!dbColumnExists($pdo, 'members', $column)) {
                    $pdo->exec($sql);
                }
            } catch (Throwable $e) {
                $ok = false;
            }
        }

        return $ok;
    }
}

if (!function_exists('mm_ensure_achievement_positions_table')) {
    function mm_ensure_achievement_positions_table(PDO $pdo): bool
    {
        try {
            if (!dbTableExists($pdo, 'achievement_positions')) {
                $pdo->exec("
                    CREATE TABLE achievement_positions (
                        id int(11) NOT NULL AUTO_INCREMENT,
                        title varchar(120) NOT NULL,
                        is_active tinyint(1) NOT NULL DEFAULT 1,
                        created_at timestamp NULL DEFAULT current_timestamp(),
                        PRIMARY KEY (id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");

                $seed = $pdo->prepare("INSERT INTO achievement_positions (title, is_active) VALUES (?, 1)");
                foreach (['Winner', '1st Position', '2nd Position', '3rd Position', 'Participant'] as $title) {
                    $seed->execute([$title]);
                }
            }
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('mm_clean')) {
    function mm_clean($value)
    {
        return htmlspecialchars(trim((string)$value), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('mm_load_document_brand_settings')) {
    function mm_load_document_brand_settings(array $settings, $brandId = 0)
    {
        $brandId = (int)$brandId;
        if ($brandId < 1 || $brandId > 3) {
            return $settings;
        }

        $prefix = 'doc_brand_' . $brandId . '_';
        $brandName = trim((string)($settings[$prefix . 'name'] ?? ''));

        if ($brandName === '' && empty($settings[$prefix . 'logo']) && empty($settings[$prefix . 'signature'])) {
            return $settings;
        }

        $overrides = [
            'site_name' => $brandName !== '' ? $brandName : ($settings['site_name'] ?? 'NGO'),
            'ngo_logo' => $settings[$prefix . 'logo'] ?? ($settings['ngo_logo'] ?? ''),
            'ngo_signature' => $settings[$prefix . 'signature'] ?? ($settings['ngo_signature'] ?? ''),
            'ngo_website' => $settings[$prefix . 'website'] ?? ($settings['ngo_website'] ?? ''),
            'ngo_address' => $settings[$prefix . 'address'] ?? ($settings['ngo_address'] ?? ''),
            'ngo_phone' => $settings[$prefix . 'phone'] ?? ($settings['ngo_phone'] ?? ''),
            'certificate_bg' => $settings[$prefix . 'certificate_bg'] ?? ($settings['certificate_bg'] ?? ''),
        ];

        return array_merge($settings, array_filter($overrides, static function ($value) {
            return $value !== null && $value !== '';
        }));
    }
}

if (!function_exists('mm_document_brand_options')) {
    function mm_document_brand_options(array $settings)
    {
        $options = [
            0 => [
                'label' => 'Default NGO Branding',
                'name' => $settings['site_name'] ?? 'NGO',
            ],
        ];

        for ($i = 1; $i <= 3; $i++) {
            $prefix = 'doc_brand_' . $i . '_';
            $label = trim((string)($settings[$prefix . 'name'] ?? ''));
            $options[$i] = [
                'label' => $label !== '' ? $label : ('Brand ' . $i),
                'name' => $label !== '' ? $label : ($settings['site_name'] ?? 'NGO'),
            ];
        }

        return $options;
    }
}

if (!function_exists('mm_rand_token')) {
    function mm_rand_token($prefix, $size = 8)
    {
        return $prefix . strtoupper(substr(bin2hex(random_bytes(8)), 0, $size));
    }
}

if (!function_exists('mm_next_receipt_no')) {
    function mm_next_receipt_no(PDO $pdo, $prefix = 'MRCPT-')
    {
        $year = date('Y');
        $base = $prefix . $year . '-';
        $stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING_INDEX(member_receipt_no, '-', -1) AS UNSIGNED)) FROM members WHERE member_receipt_no LIKE ?");
        $stmt->execute([$base . '%']);
        $next = ((int)$stmt->fetchColumn()) + 1;
        return $base . str_pad((string)$next, 6, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('mm_next_member_no')) {
    function mm_next_member_no(PDO $pdo, $prefix = 'MEM-')
    {
        $stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING(member_no, ?) AS UNSIGNED)) FROM members WHERE member_no LIKE ?");
        $startPos = strlen($prefix) + 1;
        $stmt->execute([$startPos, $prefix . '%']);
        $next = ((int)$stmt->fetchColumn()) + 1;
        return $prefix . str_pad((string)$next, 5, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('mm_member_referral_link')) {
    function mm_member_referral_link($baseUrl, $code)
    {
        return rtrim($baseUrl, '/') . '/member-register.php?ref=' . urlencode($code);
    }
}

if (!function_exists('mm_donation_referral_link')) {
    function mm_donation_referral_link($baseUrl, $code)
    {
        return rtrim($baseUrl, '/') . '/donate.php?mref=' . urlencode($code);
    }
}

if (!function_exists('mm_qr_image_url')) {
    function mm_qr_image_url($payload)
    {
        return 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . urlencode($payload);
    }
}

if (!function_exists('mm_member_doc_no')) {
    function mm_member_doc_no($type, $memberNo)
    {
        $map = [
            'id_card' => 'MID',
            'appointment_letter' => 'MAPP',
            'membership_certificate' => 'MCERT',
            'achievement_certificate' => 'MACH'
        ];
        $prefix = isset($map[$type]) ? $map[$type] : 'MDOC';
        return $prefix . '-' . date('Y') . '-' . preg_replace('/[^A-Z0-9]/', '', strtoupper($memberNo));
    }
}

if (!function_exists('mm_get_member')) {
    function mm_get_member(PDO $pdo, $id)
    {
        $sql = "SELECT m.*, d.title AS designation_title
                FROM members m
                LEFT JOIN member_designations d ON d.id = m.designation_id
                WHERE m.id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

if (!function_exists('mm_pdf_layout_bootstrap')) {
    function mm_pdf_layout_bootstrap()
    {
        if (!class_exists('FPDF') || class_exists('MMPdfLayout')) {
            return;
        }

        class MMPdfLayout extends FPDF
        {
            public function RoundedRect($x, $y, $w, $h, $r, $style = '')
            {
                $k = $this->k;
                $hp = $this->h;
                if ($style === 'F') {
                    $op = 'f';
                } elseif ($style === 'FD' || $style === 'DF') {
                    $op = 'B';
                } else {
                    $op = 'S';
                }
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

            public function CellFitScale($w, $h, $txt, $border = 0, $ln = 0, $align = '', $fill = false, $link = '')
            {
                $fontSize = null;
                $strWidth = $this->GetStringWidth($txt);
                if ($strWidth == 0) {
                    $strWidth = 1;
                }
                if ($strWidth > $w) {
                    $fontSize = $this->FontSizePt;
                    $scaleFactor = $w / $strWidth;
                    $this->SetFontSize($fontSize * $scaleFactor);
                }
                $this->Cell($w, $h, $txt, $border, $ln, $align, $fill, $link);
                if ($fontSize !== null) {
                    $this->SetFontSize($fontSize);
                }
            }
        }
    }
}

if (!function_exists('mm_document_file_path')) {
    function mm_document_file_path($path)
    {
        $path = trim((string)$path);
        if ($path === '') {
            return null;
        }
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        $normalized = ltrim(str_replace('\\', '/', $path), '/');
        $candidates = [
            __DIR__ . '/../' . $normalized,
            __DIR__ . '/../' . basename($normalized),
            $path,
        ];
        foreach ($candidates as $candidate) {
            if ($candidate !== '' && file_exists($candidate)) {
                return $candidate;
            }
        }
        return null;
    }
}

if (!function_exists('mm_fetch_remote_image')) {
    function mm_fetch_remote_image($url)
    {
        $url = trim((string)$url);
        if ($url === '') {
            return null;
        }
        if (!preg_match('#^https?://#i', $url)) {
            return $url;
        }

        $tempDir = sys_get_temp_dir();
        $tempFile = tempnam($tempDir, 'rem_img_');

        $downloaded = false;
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            $headers = [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
            ];
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            $data = curl_exec($ch);
            curl_close($ch);

            if ($data !== false && $data !== '') {
                file_put_contents($tempFile, $data);
                $downloaded = true;
            }
        }

        if (!$downloaded && ini_get('allow_url_fopen')) {
            $data = @file_get_contents($url);
            if ($data !== false && $data !== '') {
                file_put_contents($tempFile, $data);
                $downloaded = true;
            }
        }

        if ($downloaded) {
            $ext = 'png';
            $info = @getimagesize($tempFile);
            if ($info && !empty($info['mime'])) {
                $mimeMap = [
                    'image/jpeg' => 'jpg',
                    'image/jpg' => 'jpg',
                    'image/png' => 'png',
                    'image/gif' => 'gif',
                    'image/webp' => 'webp',
                ];
                $ext = $mimeMap[$info['mime']] ?? 'png';
            }
            $finalFile = $tempFile . '.' . $ext;
            if (@rename($tempFile, $finalFile)) {
                $tempFile = $finalFile;
            }
            register_shutdown_function(static function() use ($tempFile) {
                if (file_exists($tempFile)) {
                    @unlink($tempFile);
                }
            });
            return $tempFile;
        }

        return $url;
    }
}

if (!function_exists('mm_prepare_image_for_fpdf')) {
    function mm_prepare_image_for_fpdf($path)
    {
        $path = trim((string)$path);
        if ($path === '') {
            return null;
        }

        $localFile = $path;
        $isRemote = preg_match('#^https?://#i', $path);
        if ($isRemote) {
            $localFile = mm_fetch_remote_image($path);
        }

        if (!$localFile || !file_exists($localFile)) {
            return null;
        }

        $ext = strtolower(pathinfo($localFile, PATHINFO_EXTENSION));
        if ($ext === 'tmp' || !in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            $info = @getimagesize($localFile);
            if ($info && !empty($info['mime'])) {
                $mimeMap = [
                    'image/jpeg' => 'jpg',
                    'image/jpg' => 'jpg',
                    'image/png' => 'png',
                    'image/gif' => 'gif',
                    'image/webp' => 'webp',
                ];
                $detectedExt = $mimeMap[$info['mime']] ?? '';
                if ($detectedExt !== '') {
                    $tempDir = sys_get_temp_dir();
                    $newTempFile = tempnam($tempDir, 'img_ext_') . '.' . $detectedExt;
                    if (@copy($localFile, $newTempFile)) {
                        register_shutdown_function(static function() use ($newTempFile) {
                            if (file_exists($newTempFile)) {
                                @unlink($newTempFile);
                            }
                        });
                        if (strpos($localFile, $tempDir) !== false && file_exists($localFile)) {
                            @unlink($localFile);
                        }
                        $localFile = $newTempFile;
                        $ext = $detectedExt;
                    }
                }
            }
        }

        $isWebP = false;
        $fh = @fopen($localFile, 'rb');
        if ($fh) {
            $header = fread($fh, 12);
            fclose($fh);
            if (strlen($header) === 12 && substr($header, 0, 4) === 'RIFF' && substr($header, 8, 4) === 'WEBP') {
                $isWebP = true;
            }
        }

        if (!$isWebP) {
            $ext = strtolower(pathinfo($localFile, PATHINFO_EXTENSION));
            if ($ext === 'webp') {
                $isWebP = true;
            }
        }

        if ($isWebP && function_exists('imagecreatefromwebp') && function_exists('imagepng')) {
            $im = @imagecreatefromwebp($localFile);
            if ($im) {
                $tempDir = sys_get_temp_dir();
                $tempPng = tempnam($tempDir, 'webp_conv_') . '.png';
                if (@imagepng($im, $tempPng)) {
                    imagedestroy($im);
                    register_shutdown_function(static function() use ($tempPng) {
                        if (file_exists($tempPng)) {
                            @unlink($tempPng);
                        }
                    });
                    if ($isRemote && file_exists($localFile)) {
                        @unlink($localFile);
                    }
                    return $tempPng;
                }
                imagedestroy($im);
            }
        }

        return $localFile;
    }
}

if (!function_exists('mm_document_image_source')) {
    function mm_document_image_source($path, $fallbackName = '')
    {
        $resolved = mm_document_file_path($path);
        if ($resolved) {
            return $resolved;
        }

        $fallbackName = trim((string)$fallbackName);
        if ($fallbackName === '') {
            $fallbackName = 'NGO';
        }
        return 'https://ui-avatars.com/api/?name=' . urlencode($fallbackName) . '&background=e5e7eb&color=374151';
    }
}

if (!function_exists('mm_document_place_image_or_placeholder')) {
    function mm_document_place_image_or_placeholder($pdf, $path, $x, $y, $w, $h, $label = 'Photo')
    {
        $resolved = mm_document_file_path($path);
        if ($resolved) {
            $localFile = mm_prepare_image_for_fpdf($resolved);
            if ($localFile && file_exists($localFile)) {
                $pdf->Image($localFile, $x, $y, $w, $h);
                return;
            }
        }

        $pdf->SetDrawColor(203, 213, 225);
        $pdf->SetFillColor(248, 250, 252);
        $pdf->Rect($x, $y, $w, $h, 'DF');
        $pdf->SetTextColor(100, 116, 139);
        $pdf->SetFont('Arial', 'B', max(8, min(14, (int)($h * 0.28))));
        $pdf->SetXY($x, $y + ($h / 2) - 2);
        $pdf->Cell($w, 4, $label, 0, 0, 'C');
    }
}

if (!function_exists('mm_pdf_member_certificate')) {
    function mm_pdf_member_certificate(array $member, array $settings, array $tpl, string $siteName, string $docNo, string $docVerifyUrl, string $headline, string $bodyText, string $accentLabel = 'Verified Member', ?string $photoPath = null): string
    {
        mm_pdf_layout_bootstrap();
        $bgPath = mm_document_file_path('assets/member_certificate_template.jpeg');
        if ($bgPath && file_exists($bgPath)) {
            $imgSize = @getimagesize($bgPath);
            $pageW = ($imgSize && !empty($imgSize[0])) ? ((float)$imgSize[0] / 10.0) : 330.1;
            $pageH = ($imgSize && !empty($imgSize[1])) ? ((float)$imgSize[1] / 10.0) : 233.4;

            $pdf = new MMPdfLayout('L', 'mm', [$pageW, $pageH]);
            $pdf->SetMargins(0, 0, 0);
            $pdf->SetAutoPageBreak(false);
            $pdf->AddPage();
            $pdf->Image($bgPath, 0, 0, $pageW, $pageH);

            $validFrom = trim((string)($member['member_since'] ?? $member['valid_from'] ?? ''));
            $validTill = trim((string)($member['valid_until'] ?? ''));
            $pdf->SetTextColor(15, 15, 15);
            $pdf->SetFont('Arial', '', 6.4);
            $pdf->SetXY(24.0, 12.2);
            $pdf->Cell(22.0, 3.0, $validFrom !== '' ? date('d M Y', strtotime($validFrom)) : '-', 0, 0, 'L');
            $pdf->SetXY(24.0, 17.2);
            $pdf->Cell(22.0, 3.0, $validTill !== '' ? date('d M Y', strtotime($validTill)) : '-', 0, 0, 'L');

            $name = trim((string)($member['full_name'] ?? ''));
            if ($name !== '') {
                $pdf->SetTextColor(15, 15, 15);
                $pdf->SetFont('Times', 'B', 14.5);
                $pdf->SetXY(83.0, 93.0);
                $pdf->CellFitScale(160.0, 8.0, strtoupper($name), 0, 0, 'C');
            }

            mm_document_place_image_or_placeholder($pdf, $photoPath ?: ($member['photo'] ?? ''), 258.0, 23.0, 49.0, 58.0, 'Photo');

            $qrPath = mm_qr_image_url($docVerifyUrl);
            $pdf->SetDrawColor(25, 25, 25);
            $pdf->SetLineWidth(0.5);
            $pdf->Rect(15.0, 158.0, 30.0, 30.0);
            $pdf->Image($qrPath, 15.8, 158.8, 28.4, 28.4, 'PNG');

            return $pdf->Output('S');
        }

        $pdf = new MMPdfLayout('L', 'mm', 'A4');
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();

        $bgPath = mm_document_file_path('assets/member_certificate_template.jpeg');
        if (!$bgPath) {
            $bgPath = __DIR__ . '/../assets/certificate_2.png';
        }
        if (file_exists($bgPath)) {
            $pdf->Image($bgPath, 0, 0, 297, 210);
        } else {
            $pdf->SetFillColor(249, 250, 251);
            $pdf->Rect(0, 0, 297, 210, 'F');
            $pdf->SetDrawColor($tpl['primary'][0], $tpl['primary'][1], $tpl['primary'][2]);
            $pdf->Rect(10, 10, 277, 190);
        }

        $validFrom = trim((string)($member['member_since'] ?? $member['valid_from'] ?? ''));
        $validTill = trim((string)($member['valid_until'] ?? ''));
        $pdf->SetTextColor(15, 15, 15);
        $pdf->SetFont('Arial', 'B', 6.5);
        $pdf->SetXY(5.0, 12.5);
        $pdf->Cell(24, 3, 'Valid From:', 0, 0, 'L');
        $pdf->SetFont('Arial', '', 6.5);
        $pdf->Cell(26, 3, $validFrom !== '' ? date('d M Y', strtotime($validFrom)) : '-', 0, 1, 'L');
        $pdf->SetFont('Arial', 'B', 6.5);
        $pdf->SetX(5.0);
        $pdf->Cell(24, 3, 'Valid Till:', 0, 0, 'L');
        $pdf->SetFont('Arial', '', 6.5);
        $pdf->Cell(26, 3, $validTill !== '' ? date('d M Y', strtotime($validTill)) : '-', 0, 1, 'L');

        $name = trim((string)($member['full_name'] ?? ''));
        if ($name !== '') {
            $pdf->SetTextColor(15, 15, 15);
            $pdf->SetFont('Times', 'B', 13);
            $pdf->SetXY(87, 98);
            $pdf->CellFitScale(150, 8, strtoupper($name), 0, 0, 'C');
        }

        mm_document_place_image_or_placeholder($pdf, $photoPath ?: ($member['photo'] ?? ''), 246, 22, 44, 52, 'Photo');

        $qrPath = mm_qr_image_url($docVerifyUrl);
        $pdf->SetDrawColor(25, 25, 25);
        $pdf->SetLineWidth(0.5);
        $pdf->Rect(20, 160, 28, 28);
        $pdf->Image($qrPath, 20.8, 160.8, 26.4, 26.4, 'PNG');

        return $pdf->Output('S');
    }
}

if (!function_exists('mm_pdf_member_id_card')) {
    function mm_pdf_member_id_card(array $member, array $settings, string $siteName, string $docNo, string $docVerifyUrl): string
    {
        mm_pdf_layout_bootstrap();
        $bgPath = mm_document_file_path('assets/member_id_card_template.jpeg');
        if ($bgPath && file_exists($bgPath)) {
            $pdf = new MMPdfLayout('L', 'mm', [160.0, 95.3]);
            $pdf->SetMargins(0, 0, 0);
            $pdf->SetAutoPageBreak(false);
            $pdf->AddPage();
            $pdf->Image($bgPath, 0, 0, 160.0, 95.3);

            mm_document_place_image_or_placeholder($pdf, $member['photo'] ?? '', 11.4, 31.3, 35.0, 35.0, 'Photo');

            $memberNo = trim((string)($member['member_no'] ?? ''));
            if ($memberNo !== '') {
                $pdf->SetTextColor(255, 255, 255);
                $pdf->SetFont('Times', 'B', 8.2);
                $pdf->SetXY(15.5, 42.6);
                $pdf->CellFitScale(14.0, 4.0, $memberNo, 0, 0, 'L');
            }

            $valueX = 97.2;
            $valueW = 49.0;

            $pdf->SetTextColor(22, 32, 116);
            $pdf->SetFont('Times', 'B', 11.8);
            $pdf->SetXY($valueX, 27.8);
            $pdf->CellFitScale($valueW, 5.0, strtoupper((string)($member['full_name'] ?? '-')), 0, 0, 'L');

            $pdf->SetXY($valueX, 35.1);
            $pdf->SetFont('Times', 'B', 9.2);
            $pdf->CellFitScale($valueW, 5.0, (string)($member['designation_title'] ?? '-'), 0, 0, 'L');

            $pdf->SetXY($valueX, 42.6);
            $pdf->SetFont('Times', '', 8.3);
            $pdf->MultiCell(45.5, 3.5, (string)($member['address'] ?? '-'), 0, 'L');

            $contactY = max(55.6, $pdf->GetY() + 0.9);
            $pdf->SetFont('Times', 'B', 9.1);
            $pdf->SetXY($valueX, $contactY);
            $pdf->CellFitScale($valueW, 4.5, (string)($member['phone'] ?? '-'), 0, 0, 'L');

            $validY = $contactY + 7.0;
            $pdf->SetXY($valueX, $validY);
            $pdf->SetFont('Times', 'B', 9.1);
            $pdf->CellFitScale($valueW, 4.5, !empty($member['valid_until']) ? date('d-m-Y', strtotime((string)$member['valid_until'])) : '-', 0, 0, 'L');

            $bloodY = $validY + 7.0;
            $pdf->SetXY($valueX, $bloodY);
            $pdf->SetFont('Times', 'B', 9.1);
            $pdf->CellFitScale($valueW, 4.5, (string)($member['blood_group'] ?? '-'), 0, 0, 'L');

            $qrPath = mm_qr_image_url($docVerifyUrl);
            $pdf->SetDrawColor(35, 35, 35);
            $pdf->SetLineWidth(0.35);
            $pdf->Rect(136.8, 68.4, 21.2, 21.2);
            $pdf->Image($qrPath, 137.6, 69.2, 19.6, 19.6, 'PNG');

            return $pdf->Output('S');
        }

        $pdf = new MMPdfLayout('L', 'mm', [85.6, 54]);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();
        $bgPath = mm_document_file_path('assets/member_id_card_template.jpeg');
        if ($bgPath && file_exists($bgPath)) {
            $pdf->Image($bgPath, 0, 0, 85.6, 54);

            $logoPath = mm_document_file_path($settings['ngo_logo'] ?? '');
            if ($logoPath) {
                $pdf->SetFillColor(255, 255, 255);
                $pdf->Rect(1.8, 1.7, 10.5, 10.5, 'F');
                $pdf->Image($logoPath, 2.2, 2.1, 9.8, 9.8);
            }

            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('Arial', 'B', 10.5);
            $pdf->SetXY(24, 2.9);
            $pdf->CellFitScale(58, 4, strtoupper($siteName), 0, 1, 'C');
            $pdf->SetFont('Arial', '', 6.4);
            $pdf->SetXY(24, 8.0);
            $pdf->Cell(58, 3, 'MEMBER IDENTITY CARD', 0, 1, 'C');

            mm_document_place_image_or_placeholder($pdf, $member['photo'] ?? '', 5.7, 17.4, 23.2, 23.2, 'Photo');

            $pdf->SetFillColor(220, 20, 20);
            $pdf->Rect(5.0, 41.8, 23.8, 5.3, 'F');
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('Arial', 'B', 7.2);
            $pdf->SetXY(5.7, 42.4);
            $pdf->Cell(22.8, 3.5, 'ID No. ' . ($member['member_no'] ?? ''), 0, 0, 'L');

            $leftX = 31.8;
            $valueX = 49.5;
            $labelW = 16.0;
            $valueW = 27.0;
            $y = 17.2;

            $pdf->SetTextColor(15, 18, 28);
            $pdf->SetFont('Arial', 'B', 9.0);
            $pdf->SetXY($leftX, $y);
            $pdf->Cell($labelW, 4, 'Name', 0, 0);
            $pdf->Cell(2.5, 4, ':', 0, 0);
            $pdf->SetFont('Arial', 'B', 7.7);
            $pdf->SetXY($valueX, $y);
            $pdf->CellFitScale($valueW, 4, strtoupper((string)($member['full_name'] ?? '-')), 0, 0);

            $y += 4.9;
            $pdf->SetFont('Arial', 'B', 8.3);
            $pdf->SetXY($leftX, $y);
            $pdf->Cell($labelW, 4, 'Designation', 0, 0);
            $pdf->Cell(2.5, 4, ':', 0, 0);
            $pdf->SetFont('Arial', '', 6.8);
            $pdf->SetXY($valueX, $y);
            $pdf->CellFitScale($valueW, 4, (string)($member['designation_title'] ?? '-'), 0, 0);

            $y += 4.9;
            $pdf->SetFont('Arial', 'B', 8.3);
            $pdf->SetXY($leftX, $y);
            $pdf->Cell($labelW, 4, 'Address', 0, 0);
            $pdf->Cell(2.5, 4, ':', 0, 0);
            $pdf->SetFont('Arial', '', 5.7);
            $pdf->SetXY($valueX, $y);
            $pdf->MultiCell(31, 2.8, (string)($member['address'] ?? '-'), 0, 'L');

            $y = max($y + 6.1, $pdf->GetY());
            $pdf->SetFont('Arial', 'B', 8.1);
            $pdf->SetXY($leftX, $y);
            $pdf->Cell($labelW, 4, 'Contact', 0, 0);
            $pdf->Cell(2.5, 4, ':', 0, 0);
            $pdf->SetFont('Arial', '', 6.8);
            $pdf->SetXY($valueX, $y);
            $pdf->CellFitScale($valueW, 4, (string)($member['phone'] ?? '-'), 0, 0);

            $y += 4.4;
            $pdf->SetFont('Arial', 'B', 8.1);
            $pdf->SetXY($leftX, $y);
            $pdf->Cell($labelW, 4, 'Valid Upto', 0, 0);
            $pdf->Cell(2.5, 4, ':', 0, 0);
            $pdf->SetFont('Arial', '', 6.8);
            $pdf->SetXY($valueX, $y);
            $pdf->CellFitScale($valueW, 4, !empty($member['valid_until']) ? date('d M Y', strtotime((string)$member['valid_until'])) : '-', 0, 0);

            $y += 4.4;
            $pdf->SetFont('Arial', 'B', 8.1);
            $pdf->SetXY($leftX, $y);
            $pdf->Cell($labelW, 4, 'Blood Group', 0, 0);
            $pdf->Cell(2.5, 4, ':', 0, 0);
            $pdf->SetFont('Arial', '', 6.8);
            $pdf->SetXY($valueX, $y);
            $pdf->CellFitScale($valueW, 4, (string)($member['blood_group'] ?? '-'), 0, 0);

            $sigPath = mm_document_file_path($settings['ngo_signature'] ?? '');
            if ($sigPath) {
                $pdf->Image($sigPath, 12, 37.1, 18, 7);
            }
            $pdf->SetTextColor(80, 80, 80);
            $pdf->SetFont('Arial', '', 4.3);
            $pdf->SetXY(11.0, 46.4);
            $pdf->Cell(26, 2, 'Authorized Signatory', 0, 0, 'C');

            $qrPath = mm_qr_image_url($docVerifyUrl);
            $pdf->SetDrawColor(35, 35, 35);
            $pdf->SetLineWidth(0.4);
            $pdf->Rect(67.2, 32.3, 14.5, 14.5);
            $pdf->Image($qrPath, 67.7, 32.8, 13.7, 13.7, 'PNG');

            $footer = trim((string)($settings['ngo_address'] ?? '')) . ' ' . trim((string)($settings['ngo_phone'] ?? ''));
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('Arial', '', 4.9);
            $pdf->SetXY(11.0, 50.0);
            $pdf->CellFitScale(74.0, 3.4, $footer !== '' ? $footer : (string)$siteName, 0, 0, 'C');
            return $pdf->Output('S');
        }

        $pdf->SetFillColor(220, 20, 20);
        $pdf->Rect(0, 0, 11, 54, 'F');
        $pdf->SetFillColor(31, 41, 155);
        $pdf->Rect(11, 0, 74.6, 14.5, 'F');
        $pdf->SetFillColor(220, 20, 20);
        $pdf->Rect(43, 0, 42.6, 14.5, 'F');
        $pdf->SetFillColor(245, 247, 250);
        $pdf->Rect(11, 14.5, 74.6, 34.5, 'F');
        $pdf->SetDrawColor(220, 220, 220);
        $pdf->SetLineWidth(0.3);
        $pdf->Rect(11, 14.5, 74.6, 34.5);

        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetXY(0, 2.5);
        $pdf->MultiCell(11, 3.6, "MSGM\nFOUNDATION", 0, 'C');

        $logoPath = mm_document_file_path($settings['ngo_logo'] ?? '');
        if ($logoPath) {
            $pdf->SetFillColor(255, 255, 255);
            $pdf->Rect(13, 2.2, 9, 9, 'F');
            $pdf->Image($logoPath, 13.3, 2.5, 8.4, 8.4);
        }

        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetXY(24, 2.8);
        $pdf->CellFitScale(57, 4.5, strtoupper($siteName), 0, 1, 'C');
        $pdf->SetFont('Arial', '', 6.2);
        $pdf->SetXY(24, 8.3);
        $pdf->Cell(57, 3, 'MEMBER IDENTITY CARD', 0, 1, 'C');

        $pdf->SetDrawColor(32, 32, 32);
        $pdf->SetLineWidth(0.6);
        $pdf->Rect(5, 17, 24.5, 24.5);
        mm_document_place_image_or_placeholder($pdf, $member['photo'] ?? '', 5.5, 17.5, 23.5, 23.5, 'Photo');

        $pdf->SetFillColor(220, 20, 20);
        $pdf->Rect(5, 42, 24.5, 5.5, 'F');
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 7.4);
        $pdf->SetXY(5.5, 42.6);
        $pdf->Cell(23.5, 4, 'ID No. ' . ($member['member_no'] ?? ''), 0, 0, 'L');

        $leftX = 32;
        $valueX = 50;
        $labelW = 16;
        $valueW = 28;
        $y = 17.8;

        $pdf->SetTextColor(15, 18, 28);
        $pdf->SetFont('Arial', 'B', 8.9);
        $pdf->SetXY($leftX, $y);
        $pdf->Cell($labelW, 4, 'Name', 0, 0);
        $pdf->Cell(2.5, 4, ':', 0, 0);
        $pdf->SetFont('Arial', 'B', 7.8);
        $pdf->SetXY($valueX, $y);
        $pdf->CellFitScale($valueW, 4, strtoupper((string)($member['full_name'] ?? '-')), 0, 0);

        $y += 4.8;
        $pdf->SetFont('Arial', 'B', 8.3);
        $pdf->SetXY($leftX, $y);
        $pdf->Cell($labelW, 4, 'Designation', 0, 0);
        $pdf->Cell(2.5, 4, ':', 0, 0);
        $pdf->SetFont('Arial', '', 6.9);
        $pdf->SetXY($valueX, $y);
        $pdf->CellFitScale($valueW, 4, (string)($member['designation_title'] ?? '-'), 0, 0);

        $y += 4.8;
        $pdf->SetFont('Arial', 'B', 8.3);
        $pdf->SetXY($leftX, $y);
        $pdf->Cell($labelW, 4, 'Address', 0, 0);
        $pdf->Cell(2.5, 4, ':', 0, 0);
        $pdf->SetFont('Arial', '', 5.8);
        $pdf->SetXY($valueX, $y);
        $pdf->MultiCell(31, 2.9, (string)($member['address'] ?? '-'), 0, 'L');

        $y = max($y + 6.2, $pdf->GetY());
        $pdf->SetFont('Arial', 'B', 8.1);
        $pdf->SetXY($leftX, $y);
        $pdf->Cell($labelW, 4, 'Contact', 0, 0);
        $pdf->Cell(2.5, 4, ':', 0, 0);
        $pdf->SetFont('Arial', '', 6.9);
        $pdf->SetXY($valueX, $y);
        $pdf->CellFitScale($valueW, 4, (string)($member['phone'] ?? '-'), 0, 0);

        $y += 4.4;
        $pdf->SetFont('Arial', 'B', 8.1);
        $pdf->SetXY($leftX, $y);
        $pdf->Cell($labelW, 4, 'Valid Upto', 0, 0);
        $pdf->Cell(2.5, 4, ':', 0, 0);
        $pdf->SetFont('Arial', '', 6.9);
        $pdf->SetXY($valueX, $y);
        $pdf->CellFitScale($valueW, 4, !empty($member['valid_until']) ? date('d M Y', strtotime((string)$member['valid_until'])) : '-', 0, 0);

        $y += 4.4;
        $pdf->SetFont('Arial', 'B', 8.1);
        $pdf->SetXY($leftX, $y);
        $pdf->Cell($labelW, 4, 'Blood Group', 0, 0);
        $pdf->Cell(2.5, 4, ':', 0, 0);
        $pdf->SetFont('Arial', '', 6.9);
        $pdf->SetXY($valueX, $y);
        $pdf->CellFitScale($valueW, 4, (string)($member['blood_group'] ?? '-'), 0, 0);

        $sigPath = mm_document_file_path($settings['ngo_signature'] ?? '');
        if ($sigPath) {
            $pdf->Image($sigPath, 12, 37.3, 18, 7);
        }
        $pdf->SetTextColor(80, 80, 80);
        $pdf->SetFont('Arial', '', 4.5);
        $pdf->SetXY(11.5, 46.6);
        $pdf->Cell(26, 2, 'Authorized Signatory', 0, 0, 'C');

        $qrPath = mm_qr_image_url($docVerifyUrl);
        $pdf->SetDrawColor(35, 35, 35);
        $pdf->SetLineWidth(0.4);
        $pdf->Rect(67.5, 32.5, 14.5, 14.5);
        $pdf->Image($qrPath, 68, 33, 14, 14, 'PNG');

        $pdf->SetFillColor(31, 41, 155);
        $pdf->Rect(11, 49.2, 74.6, 4.8, 'F');
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Arial', '', 4.8);
        $footer = trim((string)($settings['ngo_address'] ?? '')) . ' ' . trim((string)($settings['ngo_phone'] ?? ''));
        $pdf->SetXY(12, 50);
        $pdf->CellFitScale(72.5, 3.4, $footer !== '' ? $footer : (string)$siteName, 0, 0, 'C');

        return $pdf->Output('S');
    }
}

if (!function_exists('mm_pdf_volunteer_certificate')) {
    function mm_pdf_volunteer_certificate(array $volunteer, array $settings, array $tpl, string $siteName, string $docNo, string $docVerifyUrl): string
    {
        $bodyText = 'This certificate is presented to a dedicated volunteer in recognition of valuable service, commitment, and support towards ' . $siteName . '.';
        $accent = 'Grant Post: Active Volunteer';
        return mm_pdf_member_certificate(
            [
                'full_name' => $volunteer['name'] ?? '',
                'member_no' => $volunteer['id_card_no'] ?? '',
                'photo' => $volunteer['photo'] ?? '',
                'valid_from' => $volunteer['valid_from'] ?? '',
                'valid_until' => $volunteer['valid_until'] ?? '',
            ],
            $settings,
            $tpl,
            $siteName,
            $docNo,
            $docVerifyUrl,
            'VOLUNTEER CERTIFICATE',
            $bodyText,
            $accent,
            $volunteer['photo'] ?? ''
        );
    }
}

if (!function_exists('mm_volunteer_doc_no')) {
    function mm_volunteer_doc_no($type, $volunteerIdCardNo)
    {
        $cleanNo = preg_replace('/[^A-Z0-9]/', '', strtoupper((string)$volunteerIdCardNo));
        if ($cleanNo === '') {
            $cleanNo = 'VOL';
        }

        $map = [
            'id_card' => 'VID',
            'certificate' => 'VCERT',
        ];
        $prefix = $map[$type] ?? 'VDOC';
        return $prefix . '-' . date('Y') . '-' . $cleanNo;
    }
}

if (!function_exists('mm_volunteer_qr_payload')) {
    function mm_volunteer_qr_payload(array $volunteer, string $siteName, string $docNo): string
    {
        return implode(';', [
            'DOC:' . $docNo,
            'NGO:' . $siteName,
            'VOLUNTEER:' . (string)($volunteer['name'] ?? ''),
            'ID:' . (string)($volunteer['id_card_no'] ?? ''),
            'PHONE:' . (string)($volunteer['phone'] ?? ''),
            'STATUS:' . (string)($volunteer['status'] ?? ''),
            'VALID_UNTIL:' . (!empty($volunteer['valid_until']) ? date('d-m-Y', strtotime((string)$volunteer['valid_until'])) : ''),
        ]);
    }
}

if (!function_exists('mm_pdf_volunteer_id_card')) {
    function mm_pdf_volunteer_id_card(array $volunteer, array $settings, string $siteName, string $docNo, string $qrPayload): string
    {
        mm_pdf_layout_bootstrap();

        $pdf = new MMPdfLayout('L', 'mm', [85.6, 54]);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();

        $colPrimary = [30, 64, 175];
        $colAccent = [239, 68, 68];
        $colText = [17, 24, 39];
        $colSubText = [107, 114, 128];

        $pdf->SetFillColor($colPrimary[0], $colPrimary[1], $colPrimary[2]);
        $pdf->Rect(0, 0, 85.6, 16, 'F');
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect(0, 15.5, 85.6, 0.5, 'F');
        $pdf->RoundedRect(1.5, 1.5, 82.6, 51, 2.5, 'D');

        $logoPath = mm_document_file_path($settings['ngo_logo'] ?? '');
        if ($logoPath) {
            $logoPathLocal = mm_prepare_image_for_fpdf($logoPath);
            if ($logoPathLocal && file_exists($logoPathLocal)) {
                $pdf->SetFillColor(255, 255, 255);
                $pdf->RoundedRect(3, 2.5, 11, 11, 2, 'F');
                $pdf->Image($logoPathLocal, 3.5, 3, 10, 10);
            }
        }

        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetXY(16, 3.5);
        $pdf->CellFitScale(54, 5, strtoupper($siteName), 0, 1, 'L');
        $pdf->SetFont('Arial', '', 7);
        $pdf->SetXY(16, 8.5);
        $pdf->Cell(54, 3, 'VOLUNTEER IDENTITY CARD', 0, 1, 'L');

        $qrPath = mm_qr_image_url($qrPayload);
        $qrPathLocal = mm_prepare_image_for_fpdf($qrPath);
        $pdf->SetDrawColor(255, 255, 255);
        $pdf->SetLineWidth(0.2);
        $pdf->Rect(72, 2.5, 11, 11);
        if ($qrPathLocal && file_exists($qrPathLocal)) {
            $pdf->Image($qrPathLocal, 72.4, 2.9, 10.2, 10.2, 'PNG');
        }

        $pdf->SetDrawColor(220, 220, 220);
        $pdf->SetLineWidth(0.2);
        $pdf->RoundedRect(4, 20, 24, 28, 1, 'D');
        mm_document_place_image_or_placeholder($pdf, $volunteer['photo'] ?? '', 4.5, 20.5, 23, 27, 'Photo');

        $leftX = 32;
        $y = 20;
        $pdf->SetXY($leftX, $y);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetTextColor($colPrimary[0], $colPrimary[1], $colPrimary[2]);
        $pdf->CellFitScale(50, 5, strtoupper((string)($volunteer['name'] ?? '-')), 0, 1);
        $pdf->SetDrawColor($colAccent[0], $colAccent[1], $colAccent[2]);
        $pdf->Line($leftX, $y + 5, $leftX + 40, $y + 5);
        $y += 7;

        $addRow = static function ($pdf, $label, $value, $x, $rowY, $colSub, $colTxt) {
            $pdf->SetXY($x, $rowY);
            $pdf->SetFont('Arial', '', 6);
            $pdf->SetTextColor($colSub[0], $colSub[1], $colSub[2]);
            $pdf->Cell(15, 3, $label, 0, 0);
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetTextColor($colTxt[0], $colTxt[1], $colTxt[2]);
            $pdf->SetXY($x + 12, $rowY);
            $pdf->CellFitScale(38, 3, ': ' . (string)$value, 0, 0);
        };

        $addRow($pdf, 'ID NO', $volunteer['id_card_no'] ?? '-', $leftX, $y, $colSubText, $colText);
        $y += 4.5;
        $addRow($pdf, 'PHONE', $volunteer['phone'] ?? '-', $leftX, $y, $colSubText, $colText);
        $y += 4.5;
        $addRow($pdf, 'BLOOD', $volunteer['blood_group'] ?? 'N/A', $leftX, $y, $colSubText, $colText);
        $y += 4.5;

        $pdf->SetXY($leftX, $y);
        $pdf->SetFont('Arial', '', 6);
        $pdf->SetTextColor($colSubText[0], $colSubText[1], $colSubText[2]);
        $pdf->Cell(15, 3, 'VALIDITY', 0, 0);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(220, 38, 38);
        $pdf->SetXY($leftX + 12, $y);
        $pdf->Cell(35, 3, ': ' . (!empty($volunteer['valid_until']) ? date('d M, Y', strtotime((string)$volunteer['valid_until'])) : '-'), 0, 0);

        $sigX = 68;
        $sigY = 45;
        $sigPath = mm_document_file_path($settings['ngo_signature'] ?? '');
        if ($sigPath) {
            $sigPathLocal = mm_prepare_image_for_fpdf($sigPath);
            if ($sigPathLocal && file_exists($sigPathLocal)) {
                $pdf->Image($sigPathLocal, $sigX, $sigY - 4, 15, 6);
            }
        }
        $pdf->SetDrawColor(120, 120, 120);
        $pdf->Line($sigX, $sigY + 2, $sigX + 15, $sigY + 2);
        $pdf->SetXY($sigX, $sigY + 2.5);
        $pdf->SetFont('Arial', '', 4);
        $pdf->SetTextColor(120, 120, 120);
        $pdf->Cell(15, 2, 'Authorized Sign', 0, 0, 'C');

        $pdf->SetFillColor($colPrimary[0], $colPrimary[1], $colPrimary[2]);
        $pdf->Rect(0, 50, 85.6, 4, 'F');
        $footer = 'Address: ' . trim((string)($settings['ngo_address'] ?? ''));
        $phone = trim((string)($settings['ngo_phone'] ?? ''));
        if ($phone !== '') {
            $footer .= ' | Emergency: ' . $phone;
        }
        $pdf->SetXY(0, 50.5);
        $pdf->SetFont('Arial', '', 5);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->CellFitScale(85.6, 3, trim($footer, ' |') !== '' ? $footer : $siteName, 0, 0, 'C');

        return $pdf->Output('S');
    }
}

if (!function_exists('mm_add_member_document')) {
    function mm_add_member_document(PDO $pdo, $memberId, $docType, $docNo, $issuedBy = null, $meta = null)
    {
        $stmt = $pdo->prepare("INSERT INTO member_documents (member_id, doc_type, doc_no, issued_by, meta_json) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$memberId, $docType, $docNo, $issuedBy, $meta ? json_encode($meta) : null]);
        return (int)$pdo->lastInsertId();
    }
}

if (!function_exists('mm_send_email')) {
    function mm_send_email(array $settings, $toEmail, $toName, $subject, $htmlBody, array $attachments = [])
    {
        if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
            require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
            require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';
        }

        $host = trim((string)($settings['smtp_host'] ?? ''));
        $user = trim((string)($settings['smtp_user'] ?? ''));
        $pass = (string)($settings['smtp_pass'] ?? '');
        $fromEmail = trim((string)($settings['smtp_from_email'] ?? ''));
        $fromName = trim((string)($settings['smtp_from_name'] ?? ''));
        $siteName = trim((string)($settings['site_name'] ?? 'NGO')) ?: 'NGO';
        $configuredSecure = strtolower(trim((string)($settings['smtp_secure'] ?? '')));
        $configuredPort = (int)($settings['smtp_port'] ?? 0);

        if ($fromEmail === '') {
            $fromEmail = $user !== '' ? $user : 'no-reply@example.com';
        }
        if ($fromName === '') {
            $fromName = $siteName;
        }

        $attempts = [];
        if ($configuredSecure === 'ssl' || $configuredSecure === 'smtps') {
            $attempts = [PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS, PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS, ''];
        } elseif ($configuredSecure === 'tls' || $configuredSecure === 'starttls') {
            $attempts = [PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS, PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS, ''];
        } else {
            if ($configuredPort === 465) {
                $attempts = [PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS, PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS, ''];
            } elseif ($configuredPort === 587) {
                $attempts = [PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS, PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS, ''];
            } else {
                $attempts = [PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS, PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS, ''];
            }
        }

        $attempts = array_values(array_unique($attempts, SORT_REGULAR));
        $lastError = '';

        foreach ($attempts as $secureMode) {
            try {
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = $host !== '' ? $host : 'localhost';
                $mail->SMTPAuth = $user !== '' || $pass !== '';
                $mail->Username = $user;
                $mail->Password = $pass;
                $mail->SMTPAutoTLS = true;
                $mail->Port = $configuredPort > 0 ? $configuredPort : ($secureMode === PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS ? 465 : 587);
                $mail->SMTPSecure = $secureMode;

                $mail->setFrom($fromEmail, $fromName);
                $mail->addAddress($toEmail, $toName);

                foreach ($attachments as $file) {
                    if (!empty($file['content']) && !empty($file['name'])) {
                        $mail->addStringAttachment($file['content'], $file['name']);
                    }
                }

                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $htmlBody;

                if ($mail->send()) {
                    $GLOBALS['mm_last_mail_error'] = '';
                    return true;
                }
            } catch (Throwable $e) {
                $lastError = $mail->ErrorInfo ?: $e->getMessage();
            }
        }

        $GLOBALS['mm_last_mail_error'] = $lastError;
        return false;
    }
}

if (!function_exists('mm_get_pdf_color_template')) {
    function mm_get_pdf_color_template(array $settings)
    {
        $key = $settings['pdf_color_template'] ?? 'navy';
        $templates = [
            'navy' => [
                'primary' => [24, 49, 115],
                'accent' => [20, 100, 20],
                'text_dark' => [20, 20, 20],
                'text_muted' => [90, 90, 90]
            ],
            'emerald' => [
                'primary' => [6, 95, 70],
                'accent' => [5, 150, 105],
                'text_dark' => [20, 20, 20],
                'text_muted' => [90, 90, 90]
            ],
            'maroon' => [
                'primary' => [127, 29, 29],
                'accent' => [185, 28, 28],
                'text_dark' => [20, 20, 20],
                'text_muted' => [90, 90, 90]
            ],
            'slate' => [
                'primary' => [30, 41, 59],
                'accent' => [71, 85, 105],
                'text_dark' => [20, 20, 20],
                'text_muted' => [90, 90, 90]
            ]
        ];

        return $templates[$key] ?? $templates['navy'];
    }
}
