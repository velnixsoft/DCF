<?php
// ============================================================
// download-custom-receipt.php
// Public Endpoint for Accessing & Downloading Custom Receipt PDF
// ============================================================

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/member_module.php';
require_once __DIR__ . '/includes/custom_receipt_helper.php';
require_once __DIR__ . '/includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$receiptNo = cleanInput($_GET['receipt_no'] ?? ($_GET['receipt'] ?? ''));

if ($id <= 0 && empty($receiptNo)) {
    die("Invalid request. Missing receipt reference.");
}

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM `custom_receipts` WHERE `id` = ? LIMIT 1");
    $stmt->execute([$id]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM `custom_receipts` WHERE `receipt_no` = ? LIMIT 1");
    $stmt->execute([$receiptNo]);
}

$receipt = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$receipt) {
    die("Receipt record not found.");
}

$forceDownload = isset($_GET['download']) && $_GET['download'] == '1';
$mode = $forceDownload ? 'D' : 'I';

// Check if PDF already exists on disk
$pdfPath = $receipt['pdf_path'] ?? '';
$absolutePath = $pdfPath !== '' ? (__DIR__ . '/' . ltrim($pdfPath, '/')) : '';

if ($absolutePath !== '' && file_exists($absolutePath) && filesize($absolutePath) > 500) {
    $cleanFileName = 'Receipt_' . preg_replace('/[^A-Za-z0-9\-]/', '_', $receipt['receipt_no']) . '.pdf';
    if (ob_get_length()) ob_end_clean();
    header('Content-Type: application/pdf');
    header('Content-Disposition: ' . ($forceDownload ? 'attachment' : 'inline') . '; filename="' . $cleanFileName . '"');
    header('Content-Length: ' . filesize($absolutePath));
    readfile($absolutePath);
    exit;
}

// Render on the fly
$pdfDir = __DIR__ . '/uploads/custom_receipts';
if (!is_dir($pdfDir)) {
    @mkdir($pdfDir, 0777, true);
}
$safeFileName = 'Receipt_' . preg_replace('/[^A-Za-z0-9\-]/', '_', $receipt['receipt_no']) . '.pdf';
$newAbsolutePath = $pdfDir . '/' . $safeFileName;

cr_render_receipt_pdf($pdo, $receipt, $mode, $newAbsolutePath);
