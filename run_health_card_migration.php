<?php
// ============================================================
// run_health_card_migration.php
// Executes SQL migration for Health Cards table
// ============================================================

require_once __DIR__ . '/config/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $sqlFile = __DIR__ . '/database/create_health_cards.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Migration file not found at: {$sqlFile}");
    }

    $sql = file_get_contents($sqlFile);
    $pdo->exec($sql);

    // Verify table creation
    $stmt = $pdo->query("SHOW TABLES LIKE 'health_cards'");
    $tableExists = (bool)$stmt->fetchColumn();

    // Verify columns in health_cards
    $columns = [];
    $colStmt = $pdo->query("DESCRIBE `health_cards`");
    while ($r = $colStmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[$r['Field']] = [
            'type' => $r['Type'],
            'null' => $r['Null'],
            'key'  => $r['Key'],
            'default' => $r['Default']
        ];
    }

    // Check count of seeded cards
    $countStmt = $pdo->query("SELECT COUNT(*) FROM `health_cards`");
    $cardCount = (int)$countStmt->fetchColumn();

    echo json_encode([
        'status' => 'success',
        'message' => 'Health Cards table migration executed successfully.',
        'table_exists' => $tableExists,
        'columns_count' => count($columns),
        'columns' => $columns,
        'seeded_cards_count' => $cardCount
    ], JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
