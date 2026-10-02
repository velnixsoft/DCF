<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/student/certificates.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!canAccessModule($pdo, 'coordinator', 'page.volunteers')) {
    die('Unauthorized');
}

$certId = (int)($_GET['cert_id'] ?? 0);
$studentId = (int)($_GET['student_id'] ?? 0);

if ($certId > 0 && dbTableExists($pdo, 'sa_certificates')) {
    $stmt = $pdo->prepare('SELECT pdf_path FROM sa_certificates WHERE id = ? AND status = ? LIMIT 1');
    $stmt->execute([$certId, 'Generated']);
    $pdfPath = (string)($stmt->fetchColumn() ?: '');
    if ($pdfPath !== '' && file_exists(__DIR__ . '/../' . ltrim($pdfPath, '/'))) {
        if (ob_get_length()) {
            ob_end_clean();
        }
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="Student_Certificate.pdf"');
        readfile(__DIR__ . '/../' . ltrim($pdfPath, '/'));
        exit;
    }
}

if ($studentId <= 0) {
    die('Invalid request.');
}

$stmt = $pdo->prepare('SELECT * FROM sa_students WHERE id = ? LIMIT 1');
$stmt->execute([$studentId]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$student) {
    die('Student not found.');
}

$service = new StudentCertificateService($pdo);
$certs = $service->listForStudent($studentId);
$latest = null;
foreach ($certs as $cert) {
    if (($cert['status'] ?? '') === 'Generated') {
        $latest = $cert;
        break;
    }
}

if ($latest && !empty($latest['pdf_path']) && file_exists(__DIR__ . '/../' . ltrim($latest['pdf_path'], '/'))) {
    if (ob_get_length()) {
        ob_end_clean();
    }
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="Student_Certificate_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', (string)$student['student_no']) . '.pdf"');
    readfile(__DIR__ . '/../' . ltrim($latest['pdf_path'], '/'));
    exit;
}

die('No generated certificate found for this student.');
