<?php
require_once __DIR__ . '/_bootstrap.php';

$items = [];
try {
    $stmt = $pdo->query("SELECT id, name, photo, blood_group, created_at, id_card_no FROM volunteers WHERE status = 'Active' ORDER BY created_at ASC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['photo_url'] = api_public_url($row['photo'] ?? '');
        $items[] = $row;
    }
} catch (Throwable $e) {
}

api_ok([
    'volunteers' => $items,
], 'Volunteer team loaded.');
