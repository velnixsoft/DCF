<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../libs/fpdf/fpdf.php';
require_once __DIR__ . '/../includes/member_module.php';
require_once __DIR__ . '/../includes/qr_attendance.php';

$memberNo = trim($_GET['member_no'] ?? '');
$email = trim($_GET['email'] ?? '');
if ($memberNo === '' || $email === '') {
    die('Invalid request.');
}

$stmt = $pdo->prepare("SELECT m.*, d.title AS designation_title FROM members m LEFT JOIN member_designations d ON d.id = m.designation_id WHERE m.member_no = ? AND m.email = ? LIMIT 1");
$stmt->execute([$memberNo, $email]);
$member = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$member || $member['payment_status'] !== 'Success') {
    die('Receipt unavailable.');
}

$settings = mm_load_settings($pdo);
$siteName = $settings['site_name'] ?? 'NGO';
$verifyUrl = qa_member_verify_url($pdo, $settings, (int)$member['id'], 'receipt');
$qrPayload = $verifyUrl !== '' ? $verifyUrl : ((($settings['ngo_website'] ?? '') ? rtrim($settings['ngo_website'], '/') : '') . '/member-verify.php?member=' . urlencode((string)$member['member_no']));
if ($qrPayload === '') {
    $qrPayload = 'MemberNo:' . $member['member_no'] . ';Receipt:' . $member['member_receipt_no'];
}

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFillColor(22, 78, 99);
$pdf->Rect(0, 0, 210, 28, 'F');
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Arial', 'B', 16);
$pdf->SetXY(15, 8);
$pdf->Cell(0, 8, strtoupper($siteName) . ' - Membership Receipt', 0, 1);

$pdf->SetY(40);
$pdf->SetTextColor(20, 20, 20);
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 8, 'MEMBERSHIP RECEIPT', 0, 1, 'C');
$pdf->Ln(4);

$rows = [
    ['Receipt No', $member['member_receipt_no']],
    ['Member Name', $member['full_name']],
    ['Member No', $member['member_no']],
    ['Designation', $member['designation_title'] ?: '-'],
    ['Amount', 'INR ' . number_format((float)$member['membership_fee'], 2)],
    ['Transaction ID', $member['payment_txn_id'] ?: '-']
];

foreach ($rows as $r) {
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(65, 8, $r[0], 0, 0, 'R');
    $pdf->Cell(5, 8, ':', 0, 0, 'C');
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(0, 8, $r[1], 0, 1);
}

$pdf->Image(mm_qr_image_url($qrPayload), 20, 188, 30, 30, 'PNG');
$pdf->SetXY(15, 221);
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(0, 5, 'Scan QR for verification.', 0, 1);

$pdf->Output('I', 'Membership_Receipt_' . preg_replace('/[^A-Za-z0-9\-]/', '', $member['member_receipt_no']) . '.pdf');
