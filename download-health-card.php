<?php
// ============================================================
// download-health-card.php
// Public / Member Health Card PDF Download & Stream Endpoint
// ============================================================

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/health_card_helper.php';

$cardNo = cleanInput($_GET['card'] ?? '');
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$mode = cleanInput($_GET['mode'] ?? 'stream'); // 'stream' or 'download'

if ($cardNo) {
    $stmt = $pdo->prepare("SELECT * FROM health_cards WHERE card_number = ? AND status IN ('active','renewed','expired') LIMIT 1");
    $stmt->execute([$cardNo]);
} elseif ($id) {
    $stmt = $pdo->prepare("SELECT * FROM health_cards WHERE id = ? AND status IN ('active','renewed','expired') LIMIT 1");
    $stmt->execute([$id]);
} else {
    die('Invalid health card request.');
}

$card = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$card) {
    die('Health Card record not found or not yet approved.');
}

$settings = mm_load_settings($pdo);
$outputMode = ($mode === 'download') ? 'D' : 'I';
hc_render_card_pdf($card, $settings, $outputMode);
exit;
