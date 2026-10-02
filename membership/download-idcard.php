<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/member_module.php';
require_once __DIR__ . '/../includes/qr_attendance.php';
require_once __DIR__ . '/../libs/fpdf/fpdf.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['member_logged_in']) || empty($_SESSION['member_id'])) {
    die('Unauthorized');
}

$memberId = (int)$_SESSION['member_id'];
if ($memberId <= 0) {
    die('Invalid request');
}

$member = mm_get_member($pdo, $memberId);
if (!$member) {
    die('Member not found');
}

if (($member['status'] ?? '') !== 'Active') {
    die('ID card is available for active members only.');
}

$settings = mm_load_settings($pdo);
$tpl = mm_get_pdf_color_template($settings);
$siteName = $settings['site_name'] ?? 'NGO';

$docNo = mm_member_doc_no('id_card', $member['member_no'] ?: ('MID-' . $member['id']));
$docVerifyUrl = qa_member_verify_url($pdo, $settings, (int)$member['id'], 'id_card');
if ($docVerifyUrl === '') {
    $verifyBase = ($settings['ngo_website'] ?? '') ? rtrim($settings['ngo_website'], '/') : '';
    $docVerifyUrl = $verifyBase ? ($verifyBase . '/member-verify.php?doc=' . urlencode($docNo)) : ('DOC:' . $docNo . ';MEMBER:' . ($member['member_no'] ?: $member['id']));
}

class MemberIDCardPDF extends FPDF
{
}

$pdf = new MemberIDCardPDF('L', 'mm', [85.6, 54]);
$pdf->SetAutoPageBreak(false);
$pdf->SetMargins(0, 0, 0);
$pdf->AddPage();
$pdf->SetFillColor($tpl['primary'][0], $tpl['primary'][1], $tpl['primary'][2]);
$pdf->Rect(0, 0, 85.6, 14, 'F');

if (!empty($settings['ngo_logo']) && file_exists(__DIR__ . '/../' . $settings['ngo_logo'])) {
    $pdf->SetFillColor(255, 255, 255);
    $pdf->Rect(2.5, 2, 9.5, 9.5, 'F');
    $pdf->Image(__DIR__ . '/../' . $settings['ngo_logo'], 3, 2.5, 8.5, 8.5);
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
$pdf->SetTextColor(10, 10, 10);
$pdf->SetFont('Arial', 'B', 8);
$pdf->SetXY(4, 18);
$pdf->Cell(45, 4, strtoupper((string)$member['full_name']), 0, 1);
$pdf->SetFont('Arial', '', 6.2);
$pdf->SetX(4);
$pdf->Cell(45, 4, 'Member No: ' . ($member['member_no'] ?: 'PENDING'), 0, 1);
$pdf->SetX(4);
$pdf->Cell(45, 4, 'Designation: ' . ($member['designation_title'] ?: '-'), 0, 1);
$pdf->SetX(4);
$pdf->Cell(45, 4, 'Phone: ' . ($member['phone'] ?: '-'), 0, 1);
$pdf->SetX(4);
$pdf->Cell(45, 4, 'Valid Until: ' . ($member['valid_until'] ? date('d M Y', strtotime((string)$member['valid_until'])) : '-'), 0, 1);
$pdf->Image(mm_qr_image_url($docVerifyUrl), 63, 18, 19, 19, 'PNG');
$pdf->SetXY(58, 38);
$pdf->SetFont('Arial', '', 5);
$pdf->Cell(25, 3, 'Scan to Verify', 0, 1, 'C');

$fileName = 'Member_ID_' . preg_replace('/[^A-Za-z0-9\-]/', '', (string)($member['member_no'] ?: $member['id'])) . '.pdf';
$pdf->Output('I', $fileName);
exit;
