<?php
require_once __DIR__ . '/config/db.php';

try {
    $sql = file_get_contents(__DIR__ . '/database/create_feedbacks.sql');
    $pdo->exec($sql);
    echo "Feedback Migration applied successfully!\n";
    $count = $pdo->query("SELECT COUNT(*) FROM feedbacks")->fetchColumn();
    echo "Total feedbacks in DB: $count\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
