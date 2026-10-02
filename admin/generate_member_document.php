<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../libs/fpdf/fpdf.php';
require_once __DIR__ . '/../includes/member_module.php';
require_once __DIR__ . '/../includes/template_builder.php';
require_once __DIR__ . '/../includes/qr_attendance.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!canAccessModule($pdo, 'coordinator', 'page.member_documents')) {
    die('Unauthorized');
}

$id = (int)($_GET['id'] ?? 0);
$type = $_GET['type'] ?? 'membership_certificate';
$brandId = (int)($_GET['brand'] ?? 0);
$templateId = (int)($_GET['template_id'] ?? 0);

$allowed = ['id_card', 'appointment_letter', 'membership_certificate', 'achievement_certificate'];
if (!in_array($type, $allowed, true) || $id <= 0) {
    die('Invalid request.');
}

$member = mm_get_member($pdo, $id);
if (!$member) {
    die('Member not found.');
}

$settings = mm_load_settings($pdo);
$settings = mm_load_document_brand_settings($settings, $brandId);
$siteName = $settings['site_name'] ?? 'NGO';
$tpl = mm_get_pdf_color_template($settings);
$docNo = mm_member_doc_no($type, $member['member_no'] ?: ('MID-' . $member['id']));
$docVerifyUrl = qa_member_verify_url($pdo, $settings, (int)$member['id'], $type);
if ($docVerifyUrl === '') {
    $verifyBase = ($settings['ngo_website'] ?? '') ? rtrim($settings['ngo_website'], '/') : '';
    $docVerifyUrl = $verifyBase
        ? ($verifyBase . '/member-verify.php?doc=' . urlencode($docNo))
        : ('DOC:' . $docNo . ';MEMBER:' . ($member['member_no'] ?: $member['id']));
}

function generateQR($data)
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

$customTemplate = $templateId > 0 ? tb_load_template_by_id($pdo, $templateId, $type) : tb_load_active_template($pdo, $type);
if ($customTemplate) {
    $pdfContent = tb_render_template_pdf(
        $pdo,
        $customTemplate,
        tb_member_pdf_context($member, $settings, $docNo, $docVerifyUrl)
    );

    if (ob_get_length()) {
        ob_end_clean();
    }
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $type) . '.pdf"');
    echo $pdfContent;
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// FANCY CERTIFICATE (Membership + Achievement)
// Layout copied exactly from second file's certificate block.
// ─────────────────────────────────────────────────────────────────────────────
function buildFancyCertificatePdf(
    array $member,
    array $settings,
    array $tpl,
    string $siteName,
    string $docNo,
    string $docVerifyUrl,
    string $bodyText,
    string $accentLabel = 'Verified Member'
): string {
    $pdf = new FPDF('L', 'mm', 'A4');
    $pdf->SetMargins(0, 0, 0);
    $pdf->SetAutoPageBreak(false);
    $pdf->AddPage();

    // ── Background ──────────────────────────────────────────────────────────
    $bgPath = __DIR__ . '/../assets/certificate_2.png';
    if (file_exists($bgPath)) {
        $pdf->Image($bgPath, 0, 0, 297, 210);
    } else {
        $pdf->SetFillColor(249, 250, 251);
        $pdf->Rect(0, 0, 297, 210, 'F');
        $pdf->SetDrawColor($tpl['primary'][0], $tpl['primary'][1], $tpl['primary'][2]);
        $pdf->Rect(10, 10, 277, 190);
    }

    // ── NGO Logo (top-left, same as second file) ────────────────────────────
    if (!empty($settings['ngo_logo'])) {
        $logoPathLocal = mm_prepare_image_for_fpdf(__DIR__ . '/../' . $settings['ngo_logo']);
        if ($logoPathLocal && file_exists($logoPathLocal)) {
            $pdf->Image($logoPathLocal, 15, 15, 45, 45);
        }
    }

    // ── QR Code (top-right, coordinates from second file: x=238, y=17, w=42, h=42) ──
    $qrPath = generateQR($docVerifyUrl);
    if ($qrPath && file_exists($qrPath)) {
        $pdf->Image($qrPath, 238, 17, 42, 42);
    }

    // ── Member Name ─────────────────────────────────────────────────────────
    $pdf->SetFont('Arial', 'B', 28);
    $pdf->SetTextColor(28, 48, 92);
    $pdf->SetXY(0, 90);
    $pdf->Cell(295, 0, strtoupper($member['full_name']), 0, 1, 'C');

    // ── Body text (same position as second file) ────────────────────────────
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(80, 80, 80);
    $pdf->SetXY(50, 138);
    $pdf->MultiCell(197, 7, $bodyText, 0, 'C');

    // ── Signature ───────────────────────────────────────────────────────────
    if (!empty($settings['ngo_signature'])) {
        $sigPathLocal = mm_prepare_image_for_fpdf(__DIR__ . '/../' . $settings['ngo_signature']);
        if ($sigPathLocal && file_exists($sigPathLocal)) {
            $pdf->Image($sigPathLocal, 45, 125, 40);
        }
    }

    $pdf->SetXY(55, 182);
    $pdf->Cell(55, 5, $member['member_no'], 0, 0, 'L');

    $pdf->SetXY(55, 191);
    $pdf->Cell(60, 5, strtolower($settings['ngo_website'] ?? 'www.ngo.org'), 0, 0, 'L');

    // ── Bottom-right: Doc No + Date (same as second file) ───────────────────
    $pdf->SetFont('Arial', 'B', 14);

    $pdf->SetXY(230, 182);
    $pdf->Cell(55, 5, $docNo, 0, 0, 'L');

    $pdf->SetXY(230, 191);
    $pdf->Cell(30, 5, date('d-m-Y'), 0, 0, 'L');

    // ── Accent label badge (same as second file) ────────────────────────────
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetXY(110, 155);
    $pdf->Cell(77, 6, $accentLabel, 0, 0, 'C');

    return $pdf->Output('S');
}

// ─────────────────────────────────────────────────────────────────────────────
// APPOINTMENT LETTER  (unchanged from first file)
// ─────────────────────────────────────────────────────────────────────────────
function buildAppointmentLetterPdf(array $member, array $settings, array $tpl, string $siteName, string $docNo, string $docVerifyUrl): string
{
    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->SetMargins(18, 18, 18);
    $pdf->SetAutoPageBreak(false);
    $pdf->AddPage();

    $pdf->SetDrawColor($tpl['primary'][0], $tpl['primary'][1], $tpl['primary'][2]);
    $pdf->Rect(10, 10, 190, 277);
    $pdf->Rect(12, 12, 186, 273);
    $pdf->SetFillColor($tpl['primary'][0], $tpl['primary'][1], $tpl['primary'][2]);
    $pdf->Rect(10, 10, 190, 18, 'F');

    if (!empty($settings['ngo_logo'])) {
        $logoPathLocal = mm_prepare_image_for_fpdf(__DIR__ . '/../' . $settings['ngo_logo']);
        if ($logoPathLocal && file_exists($logoPathLocal)) {
            $pdf->SetFillColor(255, 255, 255);
            $pdf->Rect(15, 14, 18, 18, 'F');
            $pdf->Image($logoPathLocal, 15.5, 14.5, 17, 17);
        }
    }

    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->SetXY(0, 15);
    $pdf->Cell(210, 6, strtoupper($siteName), 0, 1, 'C');
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetXY(0, 21);
    $pdf->Cell(210, 5, 'APPOINTMENT LETTER', 0, 1, 'C');

    $pdf->SetTextColor($tpl['text_dark'][0], $tpl['text_dark'][1], $tpl['text_dark'][2]);
    $pdf->SetFont('Arial', '', 11);
    $pdf->SetXY(20, 40);
    $pdf->Cell(0, 6, 'Date: ' . date('d F Y'), 0, 1, 'R');
    $pdf->Ln(8);

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 7, 'To,', 0, 1);
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(0, 6, $member['full_name'], 0, 1);
    $pdf->Cell(0, 6, 'Member No: ' . ($member['member_no'] ?: 'PENDING'), 0, 1);
    $pdf->Ln(8);

    $designation = $member['designation_title'] ?: 'the assigned position';
    $body = "Subject: Appointment as {$designation}\n\n"
        . "Dear {$member['full_name']},\n\n"
        . "We are pleased to inform you that you have been appointed as {$designation} at {$siteName}. "
        . "This appointment is effective immediately and is issued to recognize your responsibility, commitment, and contribution.\n\n"
        . "Your appointment reference number is {$docNo}. Please keep this letter for your records.\n\n"
        . "We look forward to your continued support and leadership.\n\n"
        . "Sincerely,";
    $pdf->MultiCell(0, 7, $body, 0, 'L');

    $sigY = 212;
    $pdf->Line(130, $sigY, 190, $sigY);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetXY(130, $sigY + 2);
    $pdf->Cell(60, 5, 'Authorized Signatory', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetX(130);
    $pdf->Cell(60, 5, $siteName, 0, 1, 'C');

    // ── Signature (moved up slightly so it sits above the line) ───────────
    if (!empty($settings['ngo_signature'])) {
        $sigPathLocal = mm_prepare_image_for_fpdf(__DIR__ . '/../' . $settings['ngo_signature']);
        if ($sigPathLocal && file_exists($sigPathLocal)) {
            $pdf->Image($sigPathLocal, 130, 182, 40);
        }
    }

    // ── Footer: QR on left, Doc No + Website text to the RIGHT of QR ──────
    $qrPath = generateQR($docVerifyUrl);
    if ($qrPath && file_exists($qrPath)) {
        $pdf->Image($qrPath, 16, 245, 30, 30);
    }

    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetTextColor($tpl['text_dark'][0], $tpl['text_dark'][1], $tpl['text_dark'][2]);

    $pdf->SetXY(50, 248);
    $pdf->Cell(0, 6, 'Document No: ' . $docNo, 0, 1);

    $pdf->SetXY(50, 255);
    $pdf->SetFont('Arial', '', 9);
    $pdf->Cell(0, 6, 'Website: ' . ($settings['ngo_website'] ?? 'N/A'), 0, 1);

    return $pdf->Output('S');
}

// ─────────────────────────────────────────────────────────────────────────────
// ACHIEVEMENT CERTIFICATE  — same layout as second file
// ─────────────────────────────────────────────────────────────────────────────
function buildAchievementCertificatePdf(array $member, array $settings, array $tpl, string $siteName, string $docNo, string $docVerifyUrl): string
{
    $parts = [];
    if (!empty($member['event_title'])) {
        $parts[] = 'for participating in ' . $member['event_title'];
    }
    if (!empty($member['occasion_name'])) {
        $parts[] = 'on the occasion of ' . $member['occasion_name'];
    }
    if (!empty($member['event_date'])) {
        $parts[] = 'held on ' . date('d M Y', strtotime((string)$member['event_date']));
    }
    if (!empty($member['achievement_position'])) {
        $parts[] = 'and securing ' . $member['achievement_position'];
    }
    $bodyText = !empty($parts)
        ? ('This certificate is proudly presented ' . implode(' ', $parts) . '.')
        : ('In recognition of valuable contribution, dedication, and service to ' . $siteName . '.');
    return buildFancyCertificatePdf(
        $member,
        $settings,
        $tpl,
        $siteName,
        $docNo,
        $docVerifyUrl,
        $bodyText,
        'Verified Achievement'
    );
}

// ─────────────────────────────────────────────────────────────────────────────
// MEMBERSHIP CERTIFICATE  — same layout as second file
// ─────────────────────────────────────────────────────────────────────────────
function buildMembershipCertificatePdf(array $member, array $settings, array $tpl, string $siteName, string $docNo, string $docVerifyUrl): string
{
    $bodyText = 'Registered member of ' . $siteName . '.';
    return buildFancyCertificatePdf(
        $member,
        $settings,
        $tpl,
        $siteName,
        $docNo,
        $docVerifyUrl,
        $bodyText,
        'Verified Member'
    );
}

// ─────────────────────────────────────────────────────────────────────────────
// ID CARD  (unchanged from first file)
// ─────────────────────────────────────────────────────────────────────────────
if ($type === 'id_card') {

    $pdf = new FPDF('L', 'mm', [85.6, 54]);
    $pdf->SetAutoPageBreak(false);
    $pdf->AddPage();

    // === Border ===
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->Rect(1, 1, 83.6, 52);

    // === Header ===
    $pdf->SetFillColor(20, 40, 90);
    $pdf->Rect(0, 0, 85.6, 14, 'F');

    // === Logo ===
    if (!empty($settings['ngo_logo'])) {
        $logoPathLocal = mm_prepare_image_for_fpdf(__DIR__ . '/../' . $settings['ngo_logo']);
        if ($logoPathLocal && file_exists($logoPathLocal)) {
            $pdf->Image($logoPathLocal, 4, 2.5, 9);
        }
    }

    // === NGO Name ===
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(15, 4);
    $pdf->Cell(60, 5, strtoupper($siteName));

    // === Label ===
    $pdf->SetFont('Arial', '', 7);
    $pdf->SetXY(15, 9);
    $pdf->Cell(60, 4, 'IDENTITY CARD');

    // === Reset color ===
    $pdf->SetTextColor(0, 0, 0);

    // =========================
    // 👤 MEMBER PHOTO (LEFT)
    // =========================
    $photoPath = !empty($member['photo']) ? __DIR__ . '/../' . $member['photo'] : '';
    if ($photoPath) {
        $photoPathLocal = mm_prepare_image_for_fpdf($photoPath);
        if ($photoPathLocal && file_exists($photoPathLocal)) {
            $pdf->Image($photoPathLocal, 4, 18, 16, 20);
            $pdf->Rect(4, 18, 16, 20); // border around photo
        } else {
            $pdf->Rect(4, 18, 16, 20);
            $pdf->SetFont('Arial', '', 6);
            $pdf->SetXY(4, 27);
            $pdf->Cell(16, 4, 'No Photo', 0, 0, 'C');
        }
    } else {
        $pdf->Rect(4, 18, 16, 20);
        $pdf->SetFont('Arial', '', 6);
        $pdf->SetXY(4, 27);
        $pdf->Cell(16, 4, 'No Photo', 0, 0, 'C');
    }

    // =========================
    // 🧾 MEMBER DETAILS (CENTER)
    // =========================
    $pdf->SetXY(22, 18);

    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(35, 5, strtoupper($member['full_name']), 0, 1);

    $pdf->SetX(22);
    $pdf->SetFont('Arial', '', 7);
    $pdf->Cell(35, 4, 'Member ID: ' . $member['member_no'], 0, 1);

    $pdf->SetX(22);
    $pdf->Cell(35, 4, 'Status: Active', 0, 1);

    $pdf->SetX(22);
    $pdf->Cell(35, 4, 'Valid: Lifetime', 0, 1);

    // Divider
    $pdf->Line(22, 34, 58, 34);

    // Footer note
    $pdf->SetFont('Arial', '', 6);
    $pdf->SetXY(22, 35);
    $pdf->MultiCell(35, 3, 'Carry this card during activities.');

    // =========================
    // 🔳 QR CODE (RIGHT)
    // =========================
    $qrPath = generateQR($docVerifyUrl);
    if ($qrPath && file_exists($qrPath)) {
        $pdf->Image($qrPath, 62, 18, 20, 20);
    }

    // Optional QR label
    $pdf->SetFont('Arial', '', 6);
    $pdf->SetXY(62, 39);
    $pdf->Cell(20, 3, 'Scan to verify', 0, 0, 'C');

    // =========================
    // OUTPUT
    // =========================
    if (ob_get_length()) {
        ob_end_clean();
    }

    header('Content-Type: application/pdf');

    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if (strpos($userAgent, 'Android') !== false && strpos($userAgent, 'wv') !== false) {
        $pdf->Output('D', 'id_card.pdf');
    } else {
        $pdf->Output('I', 'id_card.pdf');
    }

    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// DISPATCH
// ─────────────────────────────────────────────────────────────────────────────
$pdfContent = '';
if ($type === 'appointment_letter') {
    $pdfContent = buildAppointmentLetterPdf($member, $settings, $tpl, $siteName, $docNo, $docVerifyUrl);
} elseif ($type === 'achievement_certificate') {
    $pdfContent = buildAchievementCertificatePdf($member, $settings, $tpl, $siteName, $docNo, $docVerifyUrl);
} else {
    $pdfContent = buildMembershipCertificatePdf($member, $settings, $tpl, $siteName, $docNo, $docVerifyUrl);
}

if (ob_get_length()) {
    ob_end_clean();
}
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $type) . '.pdf"');
echo $pdfContent;
exit;
