<?php
// ============================================================
// admin/download_health_card.php
// Admin Health Card PDF Stream / Download Endpoint
// ============================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/health_card_helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    die('Unauthorized');
}

if (!checkRole($pdo, 'coordinator')) {
    die('Access denied. Coordinator/Manager/Admin role required.');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$cardNo = cleanInput($_GET['card'] ?? '');
$mode = cleanInput($_GET['mode'] ?? 'stream'); // 'stream' (inline I) or 'download' (attachment D)

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM health_cards WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
} elseif ($cardNo) {
    $stmt = $pdo->prepare("SELECT * FROM health_cards WHERE card_number = ? LIMIT 1");
    $stmt->execute([$cardNo]);
} else {
    die('Invalid health card ID or number.');
}

$card = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$card) {
    die('Health Card record not found.');
}

// Check if PDF file exists on disk, otherwise generate dynamically
if (!empty($card['pdf_path'])) {
    $pdfFullPath = __DIR__ . '/../' . ltrim($card['pdf_path'], '/');
    if (file_exists($pdfFullPath) && is_file($pdfFullPath)) {
        $fileName = 'Health_Card_' . preg_replace('/[^A-Za-z0-9\-]/', '', (string)$card['card_number']) . '.pdf';
        header('Content-Type: application/pdf');
        if ($mode === 'download') {
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
        } else {
            header('Content-Disposition: inline; filename="' . $fileName . '"');
        }
        header('Content-Length: ' . filesize($pdfFullPath));
        readfile($pdfFullPath);
        exit;
    }
}

// Generate on the fly
$settings = mm_load_settings($pdo);
$outputMode = ($mode === 'download') ? 'D' : 'I';
hc_render_card_pdf($card, $settings, $outputMode);
exit;
