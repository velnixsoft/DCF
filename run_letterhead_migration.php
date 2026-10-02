<?php
// ============================================================
// run_letterhead_migration.php
// Executes SQL migration for Letterhead Management system
// ============================================================

require_once __DIR__ . '/config/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $sqlFile = __DIR__ . '/database/create_letterhead_management.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Migration file not found at: {$sqlFile}");
    }

    $sql = file_get_contents($sqlFile);
    $pdo->exec($sql);

    // Verify table creation
    $stmt = $pdo->query("SHOW TABLES LIKE 'letters'");
    $tableExists = $stmt->fetchColumn();

    // Verify columns in letters
    $columns = [];
    $colStmt = $pdo->query("DESCRIBE `letters`");
    while ($r = $colStmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[] = $r['Field'];
    }

    // Verify template settings linked to CMS
    $setStmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'letterhead_%'");
    $letterheadSettings = $setStmt->fetchAll(PDO::FETCH_KEY_PAIR);

    echo json_encode([
        'status' => 'success',
        'message' => 'Letterhead Management migration executed successfully.',
        'table_exists' => (bool)$tableExists,
        'letters_columns' => $columns,
        'letterhead_settings' => $letterheadSettings
    ], JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
