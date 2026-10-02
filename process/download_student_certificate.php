<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/student/portal_helpers.php';

$student = student_portal_require_student($pdo, 'certificates');
$certId = (int)($_GET['id'] ?? 0);

if ($certId <= 0 || !dbTableExists($pdo, 'sa_certificates')) {
    http_response_code(404);
    die('Certificate not found.');
}

require_once __DIR__ . '/../includes/student/certificates.php';

$stmt = $pdo->prepare('SELECT * FROM sa_certificates WHERE id = ? AND student_id = ? LIMIT 1');
$stmt->execute([$certId, (int)$student['id']]);
$row = sa_cert_normalize_row($stmt->fetch(PDO::FETCH_ASSOC) ?: []);

if (!$row || ($row['status'] ?? '') !== 'Generated' || empty($row['pdf_path'])) {
    http_response_code(404);
    die('Certificate not available.');
}

$fullPath = __DIR__ . '/../' . ltrim((string)$row['pdf_path'], '/');
if (!is_file($fullPath)) {
    http_response_code(404);
    die('Certificate file missing.');
}

if (ob_get_length()) {
    ob_end_clean();
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="Certificate_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', (string)$student['student_no']) . '.pdf"');
readfile($fullPath);
exit;
