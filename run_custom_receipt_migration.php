<?php
// ============================================================
// run_custom_receipt_migration.php
// Executes SQL migration for Custom Receipts table
// ============================================================

require_once __DIR__ . '/config/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $sqlFile = __DIR__ . '/database/create_custom_receipts.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Migration file not found at: {$sqlFile}");
    }

    $sql = file_get_contents($sqlFile);
    $pdo->exec($sql);

    // Verify table creation
    $stmt = $pdo->query("SHOW TABLES LIKE 'custom_receipts'");
    $tableExists = $stmt->fetchColumn();

    // Verify columns in custom_receipts
    $columns = [];
    $colStmt = $pdo->query("DESCRIBE `custom_receipts`");
    while ($r = $colStmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[$r['Field']] = [
            'type' => $r['Type'],
            'null' => $r['Null'],
            'key'  => $r['Key'],
            'default' => $r['Default']
        ];
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Custom Receipts table migration executed successfully.',
        'table_exists' => (bool)$tableExists,
        'columns_count' => count($columns),
        'columns' => $columns
    ], JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
