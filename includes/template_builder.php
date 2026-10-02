<?php

if (!function_exists('tb_allowed_template_types')) {
    function tb_allowed_template_types()
    {
        return ['id_card', 'receipt', 'membership_certificate', 'achievement_certificate', 'appointment_letter', 'visitor_certificate', 'volunteer_certificate', 'student_certificate', 'sanstha_authorization'];
    }
}

if (!function_exists('tb_type_label')) {
    function tb_type_label($type)
    {
        $labels = [
            'id_card' => 'ID Card',
            'receipt' => 'Receipt',
            'membership_certificate' => 'Membership Certificate',
            'achievement_certificate' => 'Achievement Certificate',
            'appointment_letter' => 'Appointment Letter',
            'visitor_certificate' => 'Visitor Certificate',
            'volunteer_certificate' => 'Volunteer Certificate',
            'student_certificate' => 'Student Ambassador Certificate',
            'sanstha_authorization' => 'Sanstha Authorization Certificate',
        ];
        return $labels[$type] ?? 'Document';
    }
}

if (!function_exists('tb_json_response')) {
    function tb_json_response($payload, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
        exit;
    }
}

if (!function_exists('tb_require_admin_access')) {
    function tb_require_admin_access(PDO $pdo)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !canAccessModule($pdo, 'coordinator', 'page.member_documents')) {
            tb_json_response(['success' => false, 'message' => 'Unauthorized'], 403);
        }
    }
}

if (!function_exists('tb_validate_csrf')) {
    function tb_validate_csrf()
    {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$token)) {
            tb_json_response(['success' => false, 'message' => 'Invalid security token.'], 419);
        }
    }
}

if (!function_exists('tb_upload_dir')) {
    function tb_upload_dir()
    {
        $dir = __DIR__ . '/../uploads/templates';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir;
    }
}

if (!function_exists('tb_public_upload_path')) {
    function tb_public_upload_path($filename)
    {
        return 'uploads/templates/' . ltrim((string)$filename, '/');
    }
}

if (!function_exists('tb_sanitize_upload')) {
    function tb_sanitize_upload(array $file)
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Background upload failed.');
        }
        if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
            throw new RuntimeException('Background image must be 5MB or smaller.');
        }

        $tmp = $file['tmp_name'] ?? '';
        $info = $tmp ? @getimagesize($tmp) : false;
        if (!$info || empty($info['mime'])) {
            throw new RuntimeException('Invalid image file.');
        }

        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
        ];
        if (!isset($extensions[$info['mime']])) {
            throw new RuntimeException('Only JPG, PNG, or GIF images are allowed.');
        }

        $name = 'template_bg_' . time() . '_' . bin2hex(random_bytes(5)) . '.' . $extensions[$info['mime']];
        $target = tb_upload_dir() . DIRECTORY_SEPARATOR . $name;
        if (!move_uploaded_file($tmp, $target)) {
            throw new RuntimeException('Unable to save background image.');
        }

        return tb_public_upload_path($name);
    }
}

if (!function_exists('tb_load_active_template')) {
    function tb_load_active_template(PDO $pdo, $templateType)
    {
        $templateType = trim((string)$templateType);
        if (!in_array($templateType, tb_allowed_template_types(), true) || !dbTableExists($pdo, 'templates')) {
            return null;
        }

        $stmt = $pdo->prepare("SELECT * FROM templates WHERE template_type = ? AND status = 1 ORDER BY updated_at DESC, id DESC LIMIT 1");
        $stmt->execute([$templateType]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        return $template ?: null;
    }
}

if (!function_exists('tb_load_template_by_id')) {
    function tb_load_template_by_id(PDO $pdo, $templateId, $templateType = '')
    {
        $templateId = (int)$templateId;
        if ($templateId <= 0 || !dbTableExists($pdo, 'templates')) {
            return null;
        }

        $params = [$templateId];
        $where = 'id = ?';
        $templateType = trim((string)$templateType);
        if ($templateType !== '') {
            $where .= ' AND template_type = ?';
            $params[] = $templateType;
        }

        $stmt = $pdo->prepare("SELECT * FROM templates WHERE {$where} LIMIT 1");
        $stmt->execute($params);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        return $template ?: null;
    }
}

if (!function_exists('tb_hex_to_rgb')) {
    function tb_hex_to_rgb($color, array $fallback = [0, 0, 0])
    {
        $color = trim((string)$color);
        if (preg_match('/^rgba?\((\d+),\s*(\d+),\s*(\d+)/i', $color, $m)) {
            return [min(255, (int)$m[1]), min(255, (int)$m[2]), min(255, (int)$m[3])];
        }
        $color = ltrim($color, '#');
        if (strlen($color) === 3) {
            $color = $color[0] . $color[0] . $color[1] . $color[1] . $color[2] . $color[2];
        }
        if (!preg_match('/^[0-9a-f]{6}$/i', $color)) {
            return $fallback;
        }
        return [hexdec(substr($color, 0, 2)), hexdec(substr($color, 2, 2)), hexdec(substr($color, 4, 2))];
    }
}

if (!function_exists('tb_document_type_page_size')) {
    function tb_document_type_page_size($type, $canvasWidth, $canvasHeight)
    {
        if ($type === 'id_card') {
            return ['orientation' => 'L', 'size' => [85.6, 54.0], 'w' => 85.6, 'h' => 54.0];
        }
        if ($type === 'receipt' || $type === 'appointment_letter') {
            return ['orientation' => 'P', 'size' => 'A4', 'w' => 210.0, 'h' => 297.0];
        }
        if ($type === 'visitor_certificate' || $type === 'volunteer_certificate' || $type === 'student_certificate' || $type === 'sanstha_authorization') {
            return ['orientation' => 'L', 'size' => 'A4', 'w' => 297.0, 'h' => 210.0];
        }
        if ((float)$canvasHeight > (float)$canvasWidth) {
            return ['orientation' => 'P', 'size' => 'A4', 'w' => 210.0, 'h' => 297.0];
        }
        return ['orientation' => 'L', 'size' => 'A4', 'w' => 297.0, 'h' => 210.0];
    }
}

if (!function_exists('tb_context_value')) {
    function tb_context_value(array $context, $token)
    {
        $key = trim((string)$token, '{} ');
        return (string)($context[$key] ?? '');
    }
}

if (!function_exists('tb_replace_tokens')) {
    function tb_replace_tokens($text, array $context)
    {
        return preg_replace_callback('/{{\s*([a-zA-Z0-9_]+)\s*}}/', static function ($m) use ($context) {
            return (string)($context[$m[1]] ?? '');
        }, (string)$text);
    }
}

if (!function_exists('tb_image_for_placeholder')) {
    function tb_image_for_placeholder($placeholderType, array $context)
    {
        $placeholderType = trim((string)$placeholderType, '{} ');
        $map = [
            'photo' => 'photo_path',
            'qr' => 'qr_path',
            'logo' => 'logo_path',
            'signature' => 'signature_path',
        ];
        $key = $map[$placeholderType] ?? '';
        return $key !== '' ? ($context[$key] ?? '') : '';
    }
}

if (!function_exists('tb_resolve_pdf_image_source')) {
    function tb_resolve_pdf_image_source($source)
    {
        $source = trim((string)$source);
        if ($source === '') {
            return null;
        }

        if (function_exists('mm_prepare_image_for_fpdf')) {
            $isRemote = preg_match('#^https?://#i', $source);
            $pathToResolve = $isRemote ? $source : mm_document_file_path($source);
            if ($pathToResolve) {
                return mm_prepare_image_for_fpdf($pathToResolve);
            }
        }

        if (preg_match('#^https?://#i', $source)) {
            if (function_exists('mm_fetch_remote_image')) {
                return mm_fetch_remote_image($source);
            }
            // Fallback if mm_fetch_remote_image isn't loaded
            $tempFile = tempnam(sys_get_temp_dir(), 'tb_img_');
            if (function_exists('curl_init')) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $source);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                $data = curl_exec($ch);
                curl_close($ch);
                if ($data !== false && $data !== '') {
                    file_put_contents($tempFile, $data);
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
            }
            return null;
        }

        return mm_document_file_path($source);
    }
}

if (!function_exists('tb_pdf_font_family')) {
    function tb_pdf_font_family($fontFamily)
    {
        $fontFamily = strtolower((string)$fontFamily);
        if (strpos($fontFamily, 'times') !== false || strpos($fontFamily, 'serif') !== false) {
            return 'Times';
        }
        if (strpos($fontFamily, 'courier') !== false || strpos($fontFamily, 'mono') !== false) {
            return 'Courier';
        }
        return 'Arial';
    }
}

if (!function_exists('tb_render_template_pdf')) {
    function tb_render_template_pdf(PDO $pdo, array $template, array $context)
    {
        if (!class_exists('FPDF')) {
            require_once __DIR__ . '/../libs/fpdf/fpdf.php';
        }
        if (!class_exists('TBTemplatePDF')) {
            class TBTemplatePDF extends FPDF
            {
                public function Ellipse($x, $y, $rx, $ry, $style = 'D')
                {
                    if ($style === 'F') {
                        $op = 'f';
                    } elseif ($style === 'FD' || $style === 'DF') {
                        $op = 'B';
                    } else {
                        $op = 'S';
                    }

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

        $canvasWidth = max(1.0, (float)($template['canvas_width'] ?? 800));
        $canvasHeight = max(1.0, (float)($template['canvas_height'] ?? 600));
        $page = tb_document_type_page_size($template['template_type'] ?? '', $canvasWidth, $canvasHeight);
        $scaleX = $page['w'] / $canvasWidth;
        $scaleY = $page['h'] / $canvasHeight;

        $pdf = new TBTemplatePDF($page['orientation'], 'mm', $page['size']);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();

        $background = trim((string)($template['background_image'] ?? ''));
        if ($background !== '') {
            $bgPath = mm_document_file_path($background);
            if ($bgPath) {
                $pdf->Image($bgPath, 0, 0, $page['w'], $page['h']);
            }
        }

        $json = json_decode((string)($template['json_data'] ?? ''), true);
        $objects = is_array($json['objects'] ?? null) ? $json['objects'] : [];

        foreach ($objects as $object) {
            if (!is_array($object)) {
                continue;
            }

            $type = (string)($object['type'] ?? '');
            $left = (float)($object['left'] ?? 0);
            $top = (float)($object['top'] ?? 0);
            $width = (float)($object['width'] ?? 0) * (float)($object['scaleX'] ?? 1);
            $height = (float)($object['height'] ?? 0) * (float)($object['scaleY'] ?? 1);
            $x = $left * $scaleX;
            $y = $top * $scaleY;
            $w = max(0.1, $width * $scaleX);
            $h = max(0.1, $height * $scaleY);

            if (!empty($object['placeholderType'])) {
                $source = tb_image_for_placeholder($object['placeholderType'], $context);
                $resolved = tb_resolve_pdf_image_source($source);
                if ($resolved) {
                    $pdf->Image($resolved, $x, $y, $w, $h);
                } else {
                    $label = $object['placeholderLabel'] ?? $object['placeholderType'];
                    mm_document_place_image_or_placeholder($pdf, '', $x, $y, $w, $h, (string)$label);
                }
                continue;
            }

            if (in_array($type, ['textbox', 'i-text', 'text'], true)) {
                $text = tb_replace_tokens($object['text'] ?? '', $context);
                $fill = tb_hex_to_rgb($object['fill'] ?? '#111111');
                $fontSize = max(4, ((float)($object['fontSize'] ?? 18)) * min($scaleX, $scaleY) * 2.1);
                $style = !empty($object['fontWeight']) && (string)$object['fontWeight'] !== 'normal' ? 'B' : '';
                if (!empty($object['fontStyle']) && $object['fontStyle'] === 'italic') {
                    $style .= 'I';
                }
                $pdf->SetTextColor($fill[0], $fill[1], $fill[2]);
                $pdf->SetFont(tb_pdf_font_family($object['fontFamily'] ?? 'Arial'), $style, $fontSize);
                $pdf->SetXY($x, $y);
                $lineHeight = max(2.5, $fontSize * 0.45);
                $align = strtoupper(substr((string)($object['textAlign'] ?? 'L'), 0, 1));
                if (!in_array($align, ['L', 'C', 'R'], true)) {
                    $align = 'L';
                }
                $pdf->MultiCell($w, $lineHeight, $text, 0, $align);
                continue;
            }

            if ($type === 'image') {
                $source = '';
                if (!empty($object['placeholderType'])) {
                    $source = tb_image_for_placeholder($object['placeholderType'], $context);
                } elseif (!empty($object['src']) && strpos((string)$object['src'], 'data:') !== 0) {
                    $source = (string)$object['src'];
                }
                $resolved = tb_resolve_pdf_image_source($source);
                if ($resolved) {
                    $pdf->Image($resolved, $x, $y, $w, $h);
                } else {
                    mm_document_place_image_or_placeholder($pdf, '', $x, $y, $w, $h, tb_type_label($object['placeholderType'] ?? 'Image'));
                }
                continue;
            }

            if ($type === 'rect') {
                $fill = tb_hex_to_rgb($object['fill'] ?? '#ffffff', [255, 255, 255]);
                $stroke = tb_hex_to_rgb($object['stroke'] ?? '#000000');
                $pdf->SetFillColor($fill[0], $fill[1], $fill[2]);
                $pdf->SetDrawColor($stroke[0], $stroke[1], $stroke[2]);
                $pdf->Rect($x, $y, $w, $h, empty($object['fill']) ? 'D' : 'DF');
                continue;
            }

            if ($type === 'circle' || $type === 'ellipse') {
                $fill = tb_hex_to_rgb($object['fill'] ?? '#ffffff', [255, 255, 255]);
                $pdf->SetFillColor($fill[0], $fill[1], $fill[2]);
                $pdf->SetDrawColor($fill[0], $fill[1], $fill[2]);
                $pdf->Ellipse($x + ($w / 2), $y + ($h / 2), $w / 2, $h / 2, 'F');
                continue;
            }

            if ($type === 'triangle') {
                $fill = tb_hex_to_rgb($object['fill'] ?? '#ffffff', [255, 255, 255]);
                $pdf->SetFillColor($fill[0], $fill[1], $fill[2]);
                $pdf->SetDrawColor($fill[0], $fill[1], $fill[2]);
                $pdf->Rect($x, $y, $w, $h, 'F');
                continue;
            }

            if ($type === 'line') {
                $stroke = tb_hex_to_rgb($object['stroke'] ?? '#000000');
                $pdf->SetDrawColor($stroke[0], $stroke[1], $stroke[2]);
                $pdf->SetLineWidth(max(0.1, ((float)($object['strokeWidth'] ?? 1)) * min($scaleX, $scaleY)));
                $x1 = ((float)($object['x1'] ?? 0) + $left) * $scaleX;
                $y1 = ((float)($object['y1'] ?? 0) + $top) * $scaleY;
                $x2 = ((float)($object['x2'] ?? $width) + $left) * $scaleX;
                $y2 = ((float)($object['y2'] ?? $height) + $top) * $scaleY;
                $pdf->Line($x1, $y1, $x2, $y2);
            }
        }

        return $pdf->Output('S');
    }
}

if (!function_exists('tb_member_pdf_context')) {
    function tb_member_pdf_context(array $member, array $settings, $docNo, $docVerifyUrl)
    {
        $validFrom = trim((string)($member['member_since'] ?? $member['valid_from'] ?? ''));
        $validUntil = trim((string)($member['valid_until'] ?? ''));
        return [
            'full_name' => $member['full_name'] ?? '',
            'member_no' => $member['member_no'] ?? '',
            'designation' => $member['designation_title'] ?? '',
             'dob' => $member['dob'] ?? '',
            'phone' => $member['phone'] ?? '',
            'email' => $member['email'] ?? '',
            'blood_group' => $member['blood_group'] ?? '',
            'address' => $member['address'] ?? '',
            'date' => date('d-m-Y'),
            'doc_no' => $docNo,
            'event_title' => $member['event_title'] ?? '',
            'event_date' => !empty($member['event_date']) ? date('d-m-Y', strtotime((string)$member['event_date'])) : '',
            'event_location' => $member['event_location'] ?? '',
            'occasion_name' => $member['occasion_name'] ?? '',
            'achievement_position' => $member['achievement_position'] ?? '',
            'valid_from' => $validFrom !== '' ? date('d-m-Y', strtotime($validFrom)) : '',
            'valid_until' => $validUntil !== '' ? date('d-m-Y', strtotime($validUntil)) : '',
            'site_name' => $settings['site_name'] ?? '',
            'ngo_address' => $settings['ngo_address'] ?? '',
            'ngo_phone' => $settings['ngo_phone'] ?? '',
            'ngo_website' => $settings['ngo_website'] ?? '',
            'photo_path' => $member['photo'] ?? '',
            'qr_path' => mm_qr_image_url($docVerifyUrl),
            'logo_path' => $settings['ngo_logo'] ?? '',
            'signature_path' => $settings['ngo_signature'] ?? '',
        ];
    }
}

if (!function_exists('tb_visitor_pdf_context')) {
    function tb_visitor_pdf_context(array $visitor, array $settings, $docNo, $verifyPayload)
    {
        return [
            'full_name' => $visitor['recipient_name'] ?? '',
            'member_no' => '',
            'designation' => '',
            'phone' => $visitor['phone'] ?? '',
            'email' => $visitor['recipient_email'] ?? '',
            'blood_group' => '',
            'address' => $visitor['address'] ?? '',
            'date' => date('d-m-Y'),
            'doc_no' => $docNo,
            'event_title' => $visitor['event_title'] ?? '',
            'event_date' => !empty($visitor['event_date']) ? date('d-m-Y', strtotime((string)$visitor['event_date'])) : '',
            'event_location' => $visitor['event_location'] ?? '',
            'occasion_name' => $visitor['occasion_name'] ?? '',
            'achievement_position' => $visitor['achievement_position'] ?? '',
            'certificate_title' => $visitor['certificate_title'] ?? '',
            'issued_for' => $visitor['issued_for'] ?? '',
            'valid_from' => '',
            'valid_until' => '',
            'site_name' => $settings['site_name'] ?? '',
            'ngo_address' => $settings['ngo_address'] ?? '',
            'ngo_phone' => $settings['ngo_phone'] ?? '',
            'ngo_website' => $settings['ngo_website'] ?? '',
            'photo_path' => $visitor['photo'] ?? '',
            'qr_path' => mm_qr_image_url($verifyPayload),
            'logo_path' => $settings['ngo_logo'] ?? '',
            'signature_path' => $settings['ngo_signature'] ?? '',
        ];
    }
}

if (!function_exists('tb_volunteer_pdf_context')) {
    function tb_volunteer_pdf_context(array $volunteer, array $settings, $docNo, $verifyPayload)
    {
        $validFrom = trim((string)($volunteer['valid_from'] ?? ''));
        $validUntil = trim((string)($volunteer['valid_until'] ?? ''));

        return [
            'full_name' => $volunteer['name'] ?? '',
            'member_no' => $volunteer['id_card_no'] ?? '',
            'designation' => 'Volunteer',
            'phone' => $volunteer['phone'] ?? '',
            'email' => $volunteer['email'] ?? '',
            'blood_group' => $volunteer['blood_group'] ?? '',
            'address' => $volunteer['address'] ?? '',
            'date' => date('d-m-Y'),
            'doc_no' => $docNo,
            'event_title' => '',
            'event_date' => '',
            'event_location' => '',
            'occasion_name' => '',
            'achievement_position' => '',
            'certificate_title' => 'Volunteer Certificate',
            'issued_for' => 'In recognition of valuable service, commitment, and support.',
            'valid_from' => $validFrom !== '' ? date('d-m-Y', strtotime($validFrom)) : '',
            'valid_until' => $validUntil !== '' ? date('d-m-Y', strtotime($validUntil)) : '',
            'site_name' => $settings['site_name'] ?? '',
            'ngo_address' => $settings['ngo_address'] ?? '',
            'ngo_phone' => $settings['ngo_phone'] ?? '',
            'ngo_website' => $settings['ngo_website'] ?? '',
            'photo_path' => $volunteer['photo'] ?? '',
            'qr_path' => mm_qr_image_url($verifyPayload),
            'logo_path' => $settings['ngo_logo'] ?? '',
            'signature_path' => $settings['ngo_signature'] ?? '',
        ];
    }
}

if (!function_exists('tb_sanstha_pdf_context')) {
    function tb_sanstha_pdf_context(array $sanstha, array $settings, $docNo, $verifyPayload)
    {
        $validFrom = trim((string)($sanstha['valid_from'] ?? ''));
        $validUntil = trim((string)($sanstha['valid_until'] ?? ''));

        $fullAddress = trim((string)($sanstha['center_address'] ?? ''));
        if (!empty($sanstha['city'])) $fullAddress .= ($fullAddress !== '' ? ', ' : '') . $sanstha['city'];
        if (!empty($sanstha['district']) && $sanstha['district'] !== ($sanstha['city'] ?? '')) $fullAddress .= ($fullAddress !== '' ? ', ' : '') . $sanstha['district'];
        if (!empty($sanstha['state'])) $fullAddress .= ($fullAddress !== '' ? ', ' : '') . $sanstha['state'];
        if (!empty($sanstha['pincode'])) $fullAddress .= ' - ' . $sanstha['pincode'];

        return [
            'sanstha_name' => $sanstha['sanstha_name'] ?? '',
            'full_name' => $sanstha['sanstha_name'] ?? '',
            'authorized_person' => $sanstha['authorized_person'] ?? '',
            'recipient_name' => $sanstha['authorized_person'] ?? '',
            'designation' => $sanstha['designation'] ?? 'Center Head / Director',
            'auth_type' => $sanstha['auth_type'] ?? 'Branch Office',
            'auth_code' => $docNo,
            'certificate_no' => $docNo,
            'doc_no' => $docNo,
            'member_no' => $docNo,
            'center_address' => $sanstha['center_address'] ?? '',
            'address' => $fullAddress,
            'city_name' => $sanstha['city'] ?? '',
            'district' => $sanstha['district'] ?? '',
            'state_name' => $sanstha['state'] ?? '',
            'pincode' => $sanstha['pincode'] ?? '',
            'phone' => $sanstha['contact_phone'] ?? '',
            'email' => $sanstha['contact_email'] ?? '',
            'date' => date('d-m-Y'),
            'valid_from' => $validFrom !== '' ? date('d-m-Y', strtotime($validFrom)) : date('d-m-Y'),
            'valid_until' => $validUntil !== '' ? date('d-m-Y', strtotime($validUntil)) : 'Perpetual / Ongoing',
            'scope_of_work' => $sanstha['scope_of_work'] ?? 'Authorized for institutional operations, public welfare projects and official representation.',
            'issued_for' => $sanstha['scope_of_work'] ?? 'Institutional Sanstha Authorization',
            'certificate_title' => 'Certificate of Sanstha Authorization',
            'site_name' => $settings['site_name'] ?? '',
            'ngo_address' => $settings['ngo_address'] ?? '',
            'ngo_phone' => $settings['ngo_phone'] ?? '',
            'ngo_website' => $settings['ngo_website'] ?? '',
            'ngo_reg_no' => $settings['ngo_reg_no'] ?? ($settings['reg_no'] ?? ''),
            'photo_path' => $sanstha['photo'] ?? '',
            'qr_path' => mm_qr_image_url($verifyPayload),
            'logo_path' => $settings['ngo_logo'] ?? '',
            'signature_path' => $settings['ngo_signature'] ?? '',
        ];
    }
}

