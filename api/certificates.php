<?php
require_once __DIR__ . '/_bootstrap.php';

$items = [];
try {
    $stmt = $pdo->query("SELECT id, title, image_path, created_at FROM certificates ORDER BY id DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['image_url'] = api_public_url($row['image_path'] ?? '');
        $items[] = $row;
    }
} catch (Throwable $e) {
}

api_ok([
    'items' => $items,
], 'Certificates loaded.');
