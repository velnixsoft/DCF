<?php
// ============================================================
// includes/letterhead_helper.php
// Official Letterhead Configuration & PDF Rendering Engine
// Seamlessly linked with CMS Settings table
// ============================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../libs/fpdf/fpdf.php';

/**
 * Fetch unified letterhead settings with automatic fallback to general CMS settings
 *
 * @param PDO $pdo
 * @return array
 */
function get_letterhead_settings($pdo) {
    static $cached = null;
    if ($cached !== null) return $cached;

    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $cached = [
        'org_name' => !empty($settings['letterhead_org_name']) ? $settings['letterhead_org_name'] : ($settings['site_name'] ?? 'Jaysmrutti Foundation'),
        'reg_no' => !empty($settings['letterhead_reg_no']) ? $settings['letterhead_reg_no'] : ($settings['reg_no'] ?? 'Reg. No. 123455'),
        'tagline' => !empty($settings['letterhead_tagline']) ? $settings['letterhead_tagline'] : 'Empowering Communities • Transforming Lives • Sustainable Development',
        'logo' => !empty($settings['letterhead_logo']) ? $settings['letterhead_logo'] : ($settings['ngo_logo'] ?? ''),
        'address' => !empty($settings['letterhead_address']) ? $settings['letterhead_address'] : ($settings['ngo_address'] ?? 'Jaunpur, Uttar Pradesh, India'),
        'phone' => !empty($settings['letterhead_phone']) ? $settings['letterhead_phone'] : ($settings['ngo_phone'] ?? '+91 7651910331'),
        'email' => !empty($settings['letterhead_email']) ? $settings['letterhead_email'] : ($settings['ngo_email'] ?? 'info@velnixsoft.com'),
        'website' => !empty($settings['letterhead_website']) ? $settings['letterhead_website'] : ($settings['ngo_website'] ?: 'www.jaysmruttifoundation.org'),
        'footer_text' => !empty($settings['letterhead_footer_text']) ? $settings['letterhead_footer_text'] : 'Registered under Societies Registration Act | Donations Tax Exempted u/s 80G & 12A of Income Tax Act',
        'header_color' => !empty($settings['letterhead_header_color']) ? $settings['letterhead_header_color'] : '#0F8B8D',
        'watermark_enabled' => ($settings['letterhead_watermark_enabled'] ?? '1') === '1',
        'signature_image' => !empty($settings['letterhead_signature_image']) ? $settings['letterhead_signature_image'] : ($settings['ngo_signature'] ?? ''),
        'signatory_name' => !empty($settings['letterhead_signatory_name']) ? $settings['letterhead_signatory_name'] : 'Authorized Signatory',
        'signatory_designation' => !empty($settings['letterhead_signatory_designation']) ? $settings['letterhead_signatory_designation'] : 'President / General Secretary',
    ];

    return $cached;
}

/**
 * Hex to RGB helper
 */
function letterhead_hex2rgb($hex) {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
        $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
        $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
    } else {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    }
    return [$r, $g, $b];
}

/**
 * Custom FPDF class for Official Letterhead
 */
class OfficialLetterheadPDF extends FPDF {
    public $lhSettings;
    public $colorRGB;

    public function __construct($lhSettings) {
        parent::__construct('P', 'mm', 'A4');
        $this->lhSettings = $lhSettings;
        $this->colorRGB = letterhead_hex2rgb($lhSettings['header_color']);
        $this->SetAutoPageBreak(true, 30);
        $this->SetMargins(20, 20, 20);
    }

    public function Header() {
        list($r, $g, $b) = $this->colorRGB;

        // Top Accent Color Bar
        $this->SetFillColor($r, $g, $b);
        $this->Rect(0, 0, 210, 5, 'F');

        // Logo
        $logoPath = $this->lhSettings['logo'];
        $hasLogo = false;
        if (!empty($logoPath)) {
            $absLogo = __DIR__ . '/../' . ltrim($logoPath, '/');
            if (file_exists($absLogo)) {
                $this->Image($absLogo, 20, 10, 24);
                $hasLogo = true;
            }
        }

        // Watermark Logo
        if ($this->lhSettings['watermark_enabled'] && $hasLogo) {
            // Note: FPDF without alpha extension places subtle watermark
        }

        // Header Title & Details
        $leftOffset = $hasLogo ? 48 : 20;
        $this->SetXY($leftOffset, 10);
        
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(30, 41, 59);
        $this->Cell(100, 7, iconv('UTF-8', 'windows-1252//IGNORE', $this->lhSettings['org_name']), 0, 1, 'L');

        $this->SetX($leftOffset);
        $this->SetFont('Arial', 'B', 8);
        $this->SetTextColor($r, $g, $b);
        $this->Cell(100, 4, iconv('UTF-8', 'windows-1252//IGNORE', $this->lhSettings['tagline']), 0, 1, 'L');

        $this->SetX($leftOffset);
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(100, 116, 139);
        $this->Cell(100, 4, iconv('UTF-8', 'windows-1252//IGNORE', $this->lhSettings['reg_no']), 0, 1, 'L');

        // Contact box on Right
        $this->SetXY(125, 10);
        $this->SetFont('Arial', '', 7.5);
        $this->SetTextColor(71, 85, 105);
        $this->MultiCell(65, 3.5, iconv('UTF-8', 'windows-1252//IGNORE', 
            "Phone: " . $this->lhSettings['phone'] . "\n" .
            "Email: " . $this->lhSettings['email'] . "\n" .
            "Web: " . $this->lhSettings['website'] . "\n" .
            $this->lhSettings['address']
        ), 0, 'R');

        // Divider Line
        $this->SetDrawColor($r, $g, $b);
        $this->SetLineWidth(0.7);
        $this->Line(20, 38, 190, 38);

        $this->SetLineWidth(0.2);
        $this->SetDrawColor(226, 232, 240);
        $this->Line(20, 39.5, 190, 39.5);

        $this->Ln(24);
    }

    public function Footer() {
        list($r, $g, $b) = $this->colorRGB;

        $this->SetY(-22);
        $this->SetDrawColor($r, $g, $b);
        $this->SetLineWidth(0.4);
        $this->Line(20, 275, 190, 275);

        $this->SetY(-18);
        $this->SetFont('Arial', 'I', 7.5);
        $this->SetTextColor(100, 116, 139);
        $this->MultiCell(170, 3.5, iconv('UTF-8', 'windows-1252//IGNORE', $this->lhSettings['footer_text']), 0, 'C');

        // Bottom Strip
        $this->SetFillColor($r, $g, $b);
        $this->Rect(0, 294, 210, 3, 'F');
    }
}

/**
 * Generate Letterhead PDF for a given letter record
 *
 * @param PDO $pdo
 * @param array|int $letterDataOrId
 * @param string $outputMode 'S' (string), 'I' (inline browser), 'D' (download), 'F' (save file)
 * @param string|null $savePath
 * @return string
 */
function generate_letterhead_pdf($pdo, $letterDataOrId, $outputMode = 'S', $savePath = null) {
    if (is_numeric($letterDataOrId)) {
        $stmt = $pdo->prepare("SELECT * FROM letters WHERE id = ?");
        $stmt->execute([(int)$letterDataOrId]);
        $letter = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$letter) throw new Exception("Letter record not found.");
    } else {
        $letter = $letterDataOrId;
    }

    $lh = get_letterhead_settings($pdo);
    $pdf = new OfficialLetterheadPDF($lh);
    $pdf->AddPage();

    // 1. Ref No. and Date Line
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetTextColor(30, 41, 59);
    
    $refNo = !empty($letter['reference_no']) ? $letter['reference_no'] : ('REF/LTR/' . date('Y') . '/' . str_pad($letter['id'] ?? 1, 3, '0', STR_PAD_LEFT));
    $genDate = !empty($letter['generated_date']) ? date('d M, Y', strtotime($letter['generated_date'])) : date('d M, Y');

    $pdf->Cell(85, 6, "Ref. No: " . $refNo, 0, 0, 'L');
    $pdf->Cell(85, 6, "Date: " . $genDate, 0, 1, 'R');
    $pdf->Ln(4);

    // 2. Recipient Block
    $pdf->SetFont('Arial', 'B', 9.5);
    $pdf->SetTextColor(15, 23, 42);
    $pdf->Cell(170, 5, "To,", 0, 1, 'L');

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(170, 5, iconv('UTF-8', 'windows-1252//IGNORE', $letter['recipient_name']), 0, 1, 'L');

    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(71, 85, 105);

    if (!empty($letter['recipient_designation'])) {
        $pdf->Cell(170, 4.5, iconv('UTF-8', 'windows-1252//IGNORE', $letter['recipient_designation']), 0, 1, 'L');
    }
    if (!empty($letter['recipient_organization'])) {
        $pdf->Cell(170, 4.5, iconv('UTF-8', 'windows-1252//IGNORE', $letter['recipient_organization']), 0, 1, 'L');
    }
    if (!empty($letter['recipient_address'])) {
        $pdf->MultiCell(120, 4.5, iconv('UTF-8', 'windows-1252//IGNORE', $letter['recipient_address']), 0, 'L');
    }
    $pdf->Ln(5);

    // 3. Subject Line
    list($r, $g, $b) = letterhead_hex2rgb($lh['header_color']);
    $pdf->SetFont('Arial', 'B', 10.5);
    $pdf->SetTextColor($r, $g, $b);
    $subjectText = "Subject: " . $letter['subject'];
    $pdf->MultiCell(170, 5.5, iconv('UTF-8', 'windows-1252//IGNORE', $subjectText), 0, 'L');
    $pdf->Ln(3);

    // 4. Salutation & Body Content
    $pdf->SetFont('Arial', '', 9.5);
    $pdf->SetTextColor(30, 41, 59);

    $content = $letter['content'];
    // Process paragraphs
    $paragraphs = explode("\n", str_replace(["\r\n", "\r"], "\n", $content));
    foreach ($paragraphs as $para) {
        $trimmed = trim($para);
        if ($trimmed === '') {
            $pdf->Ln(3);
        } else {
            $pdf->MultiCell(170, 5.5, iconv('UTF-8', 'windows-1252//IGNORE', $trimmed), 0, 'J');
        }
    }

    $pdf->Ln(8);

    // 5. Official Closing & Signatory Block
    $pdf->SetFont('Arial', '', 9.5);
    $pdf->Cell(170, 5, "Sincerely / Yours Faithfully,", 0, 1, 'R');
    $pdf->SetFont('Arial', 'B', 9.5);
    $pdf->Cell(170, 5, "For " . iconv('UTF-8', 'windows-1252//IGNORE', $lh['org_name']), 0, 1, 'R');

    // Signature image if present
    $sigPath = !empty($letter['signatory_signature']) ? $letter['signatory_signature'] : $lh['signature_image'];
    if (!empty($sigPath)) {
        $absSig = __DIR__ . '/../' . ltrim($sigPath, '/');
        if (file_exists($absSig)) {
            $pdf->Image($absSig, 150, $pdf->GetY() + 1, 35);
            $pdf->Ln(14);
        } else {
            $pdf->Ln(10);
        }
    } else {
        $pdf->Ln(10);
    }

    $signatoryName = !empty($letter['signatory_name']) ? $letter['signatory_name'] : $lh['signatory_name'];
    $signatoryDesig = !empty($letter['signatory_designation']) ? $letter['signatory_designation'] : $lh['signatory_designation'];

    $pdf->SetFont('Arial', 'B', 9.5);
    $pdf->SetTextColor(15, 23, 42);
    $pdf->Cell(170, 4.5, iconv('UTF-8', 'windows-1252//IGNORE', $signatoryName), 0, 1, 'R');

    $pdf->SetFont('Arial', '', 8.5);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->Cell(170, 4, iconv('UTF-8', 'windows-1252//IGNORE', $signatoryDesig), 0, 1, 'R');

    if ($outputMode === 'F' && $savePath) {
        $pdf->Output('F', $savePath);
        return $savePath;
    }

    return $pdf->Output($outputMode, $savePath ?: 'letter.pdf');
}

/**
 * Send official letterhead PDF to recipient via email
 *
 * @param PDO $pdo
 * @param int $letterId
 * @param string|null $targetEmail
 * @param string|null $customMessage
 * @return array
 */
function email_letterhead_to_recipient($pdo, $letterId, $targetEmail = null, $customMessage = null) {
    require_once __DIR__ . '/member_module.php';

    $stmt = $pdo->prepare("SELECT * FROM letters WHERE id = ?");
    $stmt->execute([(int)$letterId]);
    $letter = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$letter) {
        return ['success' => false, 'message' => 'Letter record not found.'];
    }

    $toEmail = trim($targetEmail ?: ($letter['recipient_email'] ?? ''));
    if (empty($toEmail) || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'A valid recipient email address is required.'];
    }

    $lh = get_letterhead_settings($pdo);

    // Fetch CMS settings for SMTP
    $sStmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $rawSettings = $sStmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Generate PDF attachment
    try {
        $pdfContent = generate_letterhead_pdf($pdo, $letter, 'S');
    } catch (Throwable $e) {
        return ['success' => false, 'message' => 'Failed to generate Letterhead PDF: ' . $e->getMessage()];
    }

    $cleanRef = preg_replace('/[^A-Za-z0-9_\-]/', '_', $letter['reference_no'] ?: ('Letter_' . $letter['id']));
    $pdfFileName = "Official_Letter_{$cleanRef}.pdf";

    $attachments = [
        [
            'name' => $pdfFileName,
            'content' => $pdfContent
        ]
    ];

    $emailSubject = "[{$lh['org_name']}] {$letter['subject']}";
    $dateFormatted = !empty($letter['generated_date']) ? date('d M, Y', strtotime($letter['generated_date'])) : date('d M, Y');

    // Build responsive HTML email template
    $customMsgHtml = '';
    if (!empty($customMessage)) {
        $customMsgHtml = '<div style="margin: 15px 0; padding: 12px 16px; background-color: #f1f5f9; border-left: 4px solid ' . htmlspecialchars($lh['header_color']) . '; font-size: 13px; color: #334155; line-height: 1.5; border-radius: 4px;">' . nl2br(htmlspecialchars($customMessage)) . '</div>';
    }

    $htmlBody = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 20px; color: #1e293b; }
            .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
            .header-bar { height: 6px; background-color: ' . htmlspecialchars($lh['header_color']) . '; }
            .header { padding: 24px 28px; background: #ffffff; border-bottom: 2px solid ' . htmlspecialchars($lh['header_color']) . '; }
            .org-title { font-size: 18px; font-weight: 800; color: #0f172a; margin: 0; }
            .org-tagline { font-size: 11px; color: ' . htmlspecialchars($lh['header_color']) . '; font-weight: 600; margin-top: 2px; }
            .content { padding: 28px; font-size: 14px; line-height: 1.6; color: #334155; }
            .meta-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 18px; margin: 18px 0; }
            .meta-row { margin: 4px 0; font-size: 13px; }
            .meta-label { font-weight: 700; color: #64748b; }
            .btn { display: inline-block; padding: 12px 24px; background-color: ' . htmlspecialchars($lh['header_color']) . '; color: #ffffff; text-decoration: none; font-weight: 700; font-size: 13px; border-radius: 8px; margin-top: 10px; }
            .footer { padding: 20px 28px; background: #f8fafc; border-top: 1px solid #e2e8f0; font-size: 11px; color: #94a3b8; text-align: center; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header-bar"></div>
            <div class="header">
                <h1 class="org-title">' . htmlspecialchars($lh['org_name']) . '</h1>
                <div class="org-tagline">' . htmlspecialchars($lh['tagline']) . '</div>
                <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">' . htmlspecialchars($lh['reg_no']) . '</div>
            </div>
            <div class="content">
                <p style="font-size: 15px; font-weight: 700; color: #0f172a;">Dear ' . htmlspecialchars($letter['recipient_name']) . ',</p>
                <p>Please find attached the official letter issued by <strong>' . htmlspecialchars($lh['org_name']) . '</strong> regarding <strong>"' . htmlspecialchars($letter['subject']) . '"</strong>.</p>
                
                ' . $customMsgHtml . '

                <div class="meta-box">
                    <div class="meta-row"><span class="meta-label">Reference No:</span> <strong>' . htmlspecialchars($letter['reference_no']) . '</strong></div>
                    <div class="meta-row"><span class="meta-label">Date of Issuance:</span> ' . htmlspecialchars($dateFormatted) . '</div>
                    <div class="meta-row"><span class="meta-label">Document Type:</span> ' . htmlspecialchars($letter['letter_type']) . '</div>
                    <div class="meta-row"><span class="meta-label">Issued To:</span> ' . htmlspecialchars($letter['recipient_name']) . (!empty($letter['recipient_designation']) ? ' (' . htmlspecialchars($letter['recipient_designation']) . ')' : '') . '</div>
                </div>

                <p>The complete official document is attached to this email in high-resolution PDF format with authentic institutional letterhead and authorized signatory validation.</p>
                
                <p style="margin-top: 24px;">Sincerely,<br><strong>' . htmlspecialchars($letter['signatory_name'] ?: $lh['signatory_name']) . '</strong><br><span style="font-size: 12px; color: #64748b;">' . htmlspecialchars($letter['signatory_designation'] ?: $lh['signatory_designation']) . '<br>' . htmlspecialchars($lh['org_name']) . '</span></p>
            </div>
            <div class="footer">
                <div>' . htmlspecialchars($lh['address']) . '</div>
                <div>Phone: ' . htmlspecialchars($lh['phone']) . ' | Email: ' . htmlspecialchars($lh['email']) . ' | Web: ' . htmlspecialchars($lh['website']) . '</div>
                <div style="margin-top: 6px;">' . htmlspecialchars($lh['footer_text']) . '</div>
            </div>
        </div>
    </body>
    </html>
    ';

    $sent = mm_send_email($rawSettings, $toEmail, $letter['recipient_name'], $emailSubject, $htmlBody, $attachments);

    if ($sent) {
        // Update letter status to 'Sent' if it was Draft or Generated
        if ($letter['status'] === 'Draft' || $letter['status'] === 'Generated') {
            $pdo->prepare("UPDATE letters SET status = 'Sent' WHERE id = ?")->execute([(int)$letterId]);
        }
        return ['success' => true, 'message' => "Letter PDF successfully dispatched to {$toEmail}."];
    }

    $lastErr = $GLOBALS['mm_last_mail_error'] ?? 'SMTP dispatch failed. Please verify email settings in Settings > Email Server.';
    return ['success' => false, 'message' => $lastErr];
}

