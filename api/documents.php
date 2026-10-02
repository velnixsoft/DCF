<?php
require_once __DIR__ . '/_bootstrap.php';

$items = [];
try {
    $stmt = $pdo->query("SELECT id, title, category, file_path, upload_date FROM ngo_documents WHERE is_public = 1 ORDER BY upload_date DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['file_url'] = api_public_url($row['file_path'] ?? '');
        $items[] = $row;
    }
} catch (Throwable $e) {
}

api_ok(['documents' => $items], 'Public documents loaded.');
