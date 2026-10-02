<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/member_module.php';
require_once __DIR__ . '/../../includes/template_builder.php';
require_once __DIR__ . '/../../includes/qr_attendance.php';
require_once __DIR__ . '/../../libs/fpdf/fpdf.php';

if (!isset($_SESSION['logged_in'])) {
    header('Location: ../index.php');
    exit;
}
if (!canAccessModule($pdo, 'coordinator', 'page.member_documents')) {
    setFlash('error', 'Access denied.');
    header('Location: ../dashboard.php');
    exit;
}

$id = (int)($_GET['id'] ?? 0);
$type = $_GET['type'] ?? 'membership_certificate';
$brandId = (int)($_GET['brand'] ?? 0);
$templateId = (int)($_GET['template_id'] ?? 0);
$allowed = ['id_card', 'appointment_letter', 'membership_certificate', 'achievement_certificate'];
if ($id <= 0 || !in_array($type, $allowed, true)) {
    setFlash('error', 'Invalid request.');
    header('Location: ../memberships.php');
    exit;
}

$member = mm_get_member($pdo, $id);
if (!$member || empty($member['email'])) {
    setFlash('error', 'Member or email not found.');
    header('Location: ../memberships.php');
    exit;
}

$settings = mm_load_settings($pdo);
$settings = mm_load_document_brand_settings($settings, $brandId);
$tpl = mm_get_pdf_color_template($settings);
$siteName = $settings['site_name'] ?? 'NGO';
$docNo = mm_member_doc_no($type, $member['member_no'] ?: ('MID-' . $member['id']));
$docVerifyUrl = qa_member_verify_url($pdo, $settings, (int)$member['id'], $type);
if ($docVerifyUrl === '') {
    $verifyBase = ($settings['ngo_website'] ?? '') ? rtrim($settings['ngo_website'], '/') : '';
    $docVerifyUrl = $verifyBase ? ($verifyBase . '/member-verify.php?doc=' . urlencode($docNo)) : ('DOC:' . $docNo . ';MEMBER:' . ($member['member_no'] ?: $member['id']));
}

function generateQR($data)
{
    $url = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($data);
    $file = sys_get_temp_dir() . '/qr_' . md5($data) . '.png';
    if (!file_exists($file)) {
        $qrData = @file_get_contents($url);
        if ($qrData === false) {
            return null;
        }
        file_put_contents($file, $qrData);
    }
    return $file;
}

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

    if (!empty($settings['ngo_logo']) && file_exists(__DIR__ . '/../../' . $settings['ngo_logo'])) {
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect(15, 14, 18, 18, 'F');
        $pdf->Image(__DIR__ . '/../../' . $settings['ngo_logo'], 15.5, 14.5, 17, 17);
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

    if (!empty($settings['ngo_signature']) && file_exists(__DIR__ . '/../../' . $settings['ngo_signature'])) {
        $pdf->Image(__DIR__ . '/../../' . $settings['ngo_signature'], 130, 185, 40);
    }

    $pdf->SetFont('Arial', '', 10);
    $pdf->SetXY(20, 248);
    $pdf->Cell(0, 6, 'Document No: ' . $docNo, 0, 1);
    $pdf->Cell(0, 6, 'Website: ' . ($settings['ngo_website'] ?? 'N/A'), 0, 1);

    $qrPath = generateQR($docVerifyUrl);
    if ($qrPath && file_exists($qrPath)) {
        $pdf->Image($qrPath, 22, 235, 26, 26);
    }

    return $pdf->Output('S');
}

function buildAchievementCertificatePdf(array $member, array $settings, array $tpl, string $siteName, string $docNo, string $docVerifyUrl): string
{
    $pdf = new FPDF('L', 'mm', 'A4');
    $pdf->SetMargins(14, 14, 14);
    $pdf->SetAutoPageBreak(false);
    $pdf->AddPage();

    $pdf->SetFillColor(248, 250, 252);
    $pdf->Rect(10, 10, 277, 190, 'F');
    $pdf->SetDrawColor($tpl['primary'][0], $tpl['primary'][1], $tpl['primary'][2]);
    $pdf->SetLineWidth(1.4);
    $pdf->Rect(10, 10, 277, 190);
    $pdf->SetLineWidth(0.4);
    $pdf->Rect(15, 15, 267, 180);

    $pdf->SetFillColor($tpl['primary'][0], $tpl['primary'][1], $tpl['primary'][2]);
    $pdf->Rect(10, 10, 277, 18, 'F');
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 18);
    $pdf->SetXY(0, 15);
    $pdf->Cell(297, 6, strtoupper($siteName), 0, 1, 'C');

    if (!empty($settings['ngo_logo']) && file_exists(__DIR__ . '/../../' . $settings['ngo_logo'])) {
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect(20, 25, 22, 22, 'F');
        $pdf->Image(__DIR__ . '/../../' . $settings['ngo_logo'], 21.5, 26.5, 19, 19);
    }

    $pdf->SetTextColor($tpl['primary'][0], $tpl['primary'][1], $tpl['primary'][2]);
    $pdf->SetFont('Arial', 'B', 26);
    $pdf->SetXY(0, 42);
    $pdf->Cell(297, 10, 'ACHIEVEMENT CERTIFICATE', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 11);
    $pdf->SetTextColor($tpl['text_muted'][0], $tpl['text_muted'][1], $tpl['text_muted'][2]);
    $pdf->SetXY(0, 55);
    $pdf->Cell(297, 6, 'Presented with appreciation and recognition', 0, 1, 'C');

    $pdf->SetTextColor($tpl['text_dark'][0], $tpl['text_dark'][1], $tpl['text_dark'][2]);
    $pdf->SetFont('Arial', 'B', 28);
    $pdf->SetXY(0, 82);
    $pdf->Cell(297, 10, strtoupper($member['full_name']), 0, 1, 'C');

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
    $awardText = !empty($parts)
        ? ('This certificate is proudly presented ' . implode(' ', $parts) . '.')
        : ('For outstanding contribution, dedication, and valuable service to ' . $siteName . '.');
    $pdf->SetFont('Arial', '', 14);
    $pdf->SetXY(38, 106);
    $pdf->MultiCell(220, 8, $awardText, 0, 'C');

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor($tpl['primary'][0], $tpl['primary'][1], $tpl['primary'][2]);
    $pdf->SetXY(0, 136);
    $pdf->Cell(297, 6, 'Document No: ' . $docNo, 0, 1, 'C');

    if (!empty($settings['ngo_signature']) && file_exists(__DIR__ . '/../../' . $settings['ngo_signature'])) {
        $pdf->Image(__DIR__ . '/../../' . $settings['ngo_signature'], 40, 144, 38);
    }

    $pdf->SetDrawColor($tpl['primary'][0], $tpl['primary'][1], $tpl['primary'][2]);
    $pdf->Line(38, 180, 90, 180);
    $pdf->Line(207, 180, 259, 180);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(34, 182);
    $pdf->Cell(60, 5, 'Authorized Signatory', 0, 0, 'C');
    $pdf->SetXY(203, 182);
    $pdf->Cell(60, 5, 'Verification', 0, 0, 'C');

    $qrPath = generateQR($docVerifyUrl);
    if ($qrPath && file_exists($qrPath)) {
        $pdf->Image($qrPath, 224, 126, 32, 32);
    }
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetXY(210, 160);
    $pdf->Cell(48, 5, 'Scan to verify', 0, 1, 'C');

    return $pdf->Output('S');
}

function buildMembershipCertificatePdf(array $member, array $settings, array $tpl, string $siteName, string $docNo, string $docVerifyUrl): string
{
    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(false);
    $pdf->AddPage();
    $pdf->SetFillColor(249, 250, 251);
    $pdf->Rect(8, 8, 194, 281, 'F');
    $pdf->SetDrawColor($tpl['primary'][0], $tpl['primary'][1], $tpl['primary'][2]);
    $pdf->Rect(8, 8, 194, 281, 'D');

    if (!empty($settings['ngo_logo']) && file_exists(__DIR__ . '/../../' . $settings['ngo_logo'])) {
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect(16, 14, 24, 24, 'F');
        $pdf->Image(__DIR__ . '/../../' . $settings['ngo_logo'], 18, 16, 20);
    }

    $pdf->SetTextColor($tpl['primary'][0], $tpl['primary'][1], $tpl['primary'][2]);
    $pdf->SetFont('Arial', 'B', 18);
    $pdf->SetXY(20, 20);
    $pdf->Cell(170, 8, strtoupper($siteName), 0, 1, 'C');
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->SetXY(20, 45);
    $pdf->Cell(170, 10, 'MEMBERSHIP CERTIFICATE', 0, 1, 'C');

    $pdf->SetTextColor($tpl['text_dark'][0], $tpl['text_dark'][1], $tpl['text_dark'][2]);
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetXY(22, 74);
    $content = "This is to certify that {$member['full_name']} is a registered member of {$siteName}.";
    $pdf->MultiCell(166, 8, $content, 0, 'C');
    $pdf->Ln(8);
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(0, 7, 'Member No: ' . ($member['member_no'] ?: 'PENDING'), 0, 1, 'C');
    $pdf->Cell(0, 7, 'Document No: ' . $docNo, 0, 1, 'C');
    $pdf->Cell(0, 7, 'Issued On: ' . date('d F Y'), 0, 1, 'C');

    $qrPath = generateQR($docVerifyUrl);
    if ($qrPath && file_exists($qrPath)) {
        $pdf->Image($qrPath, 88, 182, 34, 34);
    }

    return $pdf->Output('S');
}

$customTemplate = $templateId > 0 ? tb_load_template_by_id($pdo, $templateId, $type) : tb_load_active_template($pdo, $type);
if ($customTemplate) {
    $pdfContent = tb_render_template_pdf(
        $pdo,
        $customTemplate,
        tb_member_pdf_context($member, $settings, $docNo, $docVerifyUrl)
    );
} elseif ($type === 'id_card') {
    $pdf = new FPDF('L', 'mm', [85.6, 54]);
    $pdf->SetMargins(0, 0, 0);
    $pdf->SetAutoPageBreak(false);
    $pdf->AddPage();
    $pdf->SetFillColor($tpl['primary'][0], $tpl['primary'][1], $tpl['primary'][2]);
    $pdf->Rect(0, 0, 85.6, 14, 'F');

    if (!empty($settings['ngo_logo']) && file_exists(__DIR__ . '/../../' . $settings['ngo_logo'])) {
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect(2.5, 2, 9.5, 9.5, 'F');
        $pdf->Image(__DIR__ . '/../../' . $settings['ngo_logo'], 3, 2.5, 8.5, 8.5);
    }

    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY(13, 3.5);
    $pdf->Cell(52, 5, strtoupper($siteName), 0, 1);
    $pdf->SetFont('Arial', '', 6);
    $pdf->SetX(13);
    $pdf->Cell(52, 4, 'MEMBERSHIP ID CARD', 0, 1);
    $pdf->SetFillColor(241, 245, 249);
    $pdf->Rect(0, 14, 85.6, 40, 'F');
    $pdf->SetTextColor(20, 20, 20);
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetXY(4, 18);
    $pdf->Cell(45, 4, strtoupper($member['full_name']), 0, 1);
    $pdf->SetFont('Arial', '', 6.2);
    $pdf->SetX(4);
    $pdf->Cell(45, 4, 'Member No: ' . ($member['member_no'] ?: 'PENDING'), 0, 1);
    $pdf->SetX(4);
    $pdf->Cell(45, 4, 'Designation: ' . ($member['designation_title'] ?: '-'), 0, 1);
    $pdf->SetX(4);
    $pdf->Cell(45, 4, 'Phone: ' . ($member['phone'] ?: '-'), 0, 1);
    $pdf->Image(mm_qr_image_url($docVerifyUrl), 63, 18, 19, 19, 'PNG');
    $pdfContent = $pdf->Output('S');
} elseif ($type === 'appointment_letter') {
    $pdfContent = buildAppointmentLetterPdf($member, $settings, $tpl, $siteName, $docNo, $docVerifyUrl);
} elseif ($type === 'achievement_certificate') {
    $pdfContent = buildAchievementCertificatePdf($member, $settings, $tpl, $siteName, $docNo, $docVerifyUrl);
} else {
    $pdfContent = buildMembershipCertificatePdf($member, $settings, $tpl, $siteName, $docNo, $docVerifyUrl);
}

$subjectMap = [
    'id_card' => 'Your Membership ID Card',
    'appointment_letter' => 'Your Appointment Letter',
    'membership_certificate' => 'Your Membership Certificate',
    'achievement_certificate' => 'Your Achievement Certificate',
];

$subject = $subjectMap[$type] . ' - ' . $siteName;
$body = '<p>Dear ' . htmlspecialchars($member['full_name']) . ',</p>'
    . '<p>Please find your ' . str_replace('_', ' ', htmlspecialchars($type)) . ' attached.</p>'
    . '<p>Regards,<br>' . htmlspecialchars($siteName) . '</p>';

try {
    mm_send_email(
        $settings,
        $member['email'],
        $member['full_name'],
        $subject,
        $body,
        [[
            'name' => strtoupper($type) . '_' . preg_replace('/[^A-Za-z0-9\-]/', '', $member['member_no'] ?: (string)$member['id']) . '.pdf',
            'content' => $pdfContent,
        ]]
    );

    mm_add_member_document($pdo, (int)$member['id'], $type, $docNo, (int)($_SESSION['user_id'] ?? 0), ['sent_email' => 1]);
    setFlash('success', 'Document emailed to member successfully.');
} catch (Exception $e) {
    setFlash('error', 'Email failed: ' . $e->getMessage());
}

header('Location: ../memberships.php');
exit;
