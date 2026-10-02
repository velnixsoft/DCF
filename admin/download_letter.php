<?php
// ============================================================
// admin/download_letter.php
// Streams or downloads official letterhead PDF for a letter record
// ============================================================

require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../includes/letterhead_helper.php';

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: index.php');
    exit;
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    die("Invalid Letter ID specified.");
}

$stmt = $pdo->prepare("SELECT * FROM letters WHERE id = ?");
$stmt->execute([$id]);
$letter = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$letter) {
    die("Letter record not found.");
}

$isDownload = isset($_GET['download']) && $_GET['download'] === '1';
$cleanRef = preg_replace('/[^A-Za-z0-9_\-]/', '_', $letter['reference_no'] ?: ('Letter_' . $id));
$filename = "{$cleanRef}.pdf";

if (ob_get_length()) {
    ob_end_clean();
}

header('Content-Type: application/pdf');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

if ($isDownload) {
    header("Content-Disposition: attachment; filename=\"{$filename}\"");
} else {
    header("Content-Disposition: inline; filename=\"{$filename}\"");
}

try {
    echo generate_letterhead_pdf($pdo, $letter, 'S');
} catch (Throwable $e) {
    echo "Error rendering Letterhead PDF: " . $e->getMessage();
}
exit;
