<?php
session_start();
require '../../config/db.php';
require '../../includes/functions.php';
require '../../includes/qr_attendance.php';
require '../../libs/fpdf/fpdf.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

if (!canAccessModule($pdo, 'manager', 'page.events')) {
    setFlash('error', 'Access denied.');
    header('Location: ../dashboard.php');
    exit;
}

$attendanceEventId = (int)($_GET['id'] ?? 0);
$format = strtolower(trim((string)($_GET['format'] ?? 'csv')));
$attendanceEvent = qa_attendance_event($pdo, $attendanceEventId);
if (!$attendanceEvent) {
    setFlash('error', 'Attendance event not found.');
    header('Location: ../events.php');
    exit;
}

$rows = qa_attendance_report_rows($pdo, $attendanceEventId, true);

$fileBase = preg_replace('/[^A-Za-z0-9_\-]/', '_', (string)($attendanceEvent['event_name'] ?? ('attendance_' . $attendanceEventId)));

if ($format === 'pdf') {
    $pdf = new FPDF('L', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 15);
    $pdf->Cell(0, 10, 'Attendance Report - ' . ($attendanceEvent['event_name'] ?? ''), 0, 1);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6, 'Event Date: ' . date('d M Y', strtotime((string)$attendanceEvent['event_date'])), 0, 1);
    $pdf->Ln(3);

    $headers = [
        ['Name', 50],
        ['ID No', 34],
        ['Type', 24],
        ['Designation', 42],
        ['Phone', 34],
        ['Scan Time', 50],
        ['Status', 24],
    ];
    $pdf->SetFont('Arial', 'B', 10);
    foreach ($headers as $header) {
        $pdf->Cell($header[1], 8, $header[0], 1, 0, 'C');
    }
    $pdf->Ln();

    $pdf->SetFont('Arial', '', 9);
    foreach ($rows as $row) {
        $pdf->Cell(50, 7, substr((string)$row['full_name'], 0, 30), 1);
        $pdf->Cell(34, 7, (string)$row['member_no'], 1);
        $pdf->Cell(24, 7, strtoupper((string)($row['attendee_type'] ?? 'member')), 1);
        $pdf->Cell(42, 7, substr((string)($row['designation_title'] ?? '-'), 0, 24), 1);
        $pdf->Cell(34, 7, substr((string)($row['phone'] ?? '-'), 0, 16), 1);
        $pdf->Cell(50, 7, date('d M Y h:i A', strtotime((string)$row['scan_time'])), 1);
        $pdf->Cell(24, 7, strtoupper((string)$row['status']), 1, 0, 'C');
        $pdf->Ln();
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $fileBase . '_attendance.pdf"');
    $pdf->Output('I', $fileBase . '_attendance.pdf');
    exit;
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $fileBase . '_attendance.csv"');
$output = fopen('php://output', 'w');
fputcsv($output, ['Name', 'ID No', 'Attendee Type', 'Designation', 'Phone', 'Gender', 'Scan Time', 'Status']);
foreach ($rows as $row) {
    fputcsv($output, [
        $row['full_name'] ?? '',
        $row['member_no'] ?? '',
        $row['attendee_type'] ?? 'member',
        $row['designation_title'] ?? '',
        $row['phone'] ?? '',
        $row['gender'] ?? '',
        $row['scan_time'] ?? '',
        $row['status'] ?? '',
    ]);
}
fclose($output);
exit;
