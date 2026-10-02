<?php
// ============================================================
// run_healthcare_migration.php
// Executes SQL migration for Healthcare Panel tables
// ============================================================

require_once __DIR__ . '/config/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $sqlFile = __DIR__ . '/database/create_healthcare_panel.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Migration file not found at: {$sqlFile}");
    }

    $sql = file_get_contents($sqlFile);
    $pdo->exec($sql);

    // Verify table creation
    $tables = ['healthcare_providers', 'healthcare_services', 'healthcare_referrals'];
    $tableStatus = [];
    foreach ($tables as $tbl) {
        $stmt = $pdo->query("SHOW TABLES LIKE '{$tbl}'");
        $tableStatus[$tbl] = (bool)$stmt->fetchColumn();
    }

    // Verify columns in healthcare_providers
    $columns = [];
    $colStmt = $pdo->query("DESCRIBE `healthcare_providers`");
    while ($r = $colStmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[$r['Field']] = [
            'type' => $r['Type'],
            'null' => $r['Null'],
            'key'  => $r['Key'],
            'default' => $r['Default']
        ];
    }

    // Check count of seeded providers
    $countStmt = $pdo->query("SELECT COUNT(*) FROM `healthcare_providers`");
    $providerCount = (int)$countStmt->fetchColumn();

    echo json_encode([
        'status' => 'success',
        'message' => 'Healthcare Panel tables migration executed successfully.',
        'tables' => $tableStatus,
        'providers_columns_count' => count($columns),
        'providers_columns' => $columns,
        'seeded_providers_count' => $providerCount
    ], JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
