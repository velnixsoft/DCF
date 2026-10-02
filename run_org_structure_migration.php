<?php
require_once 'c:/Users/dell/Downloads/Ngo Ai (2)/Ngo Ai/config/db.php';

try {
    $sql = file_get_contents('c:/Users/dell/Downloads/Ngo Ai (2)/Ngo Ai/database/create_org_structure.sql');
    $pdo->exec($sql);
    echo "Organization Structure Migration Executed Successfully!\n";
} catch (Throwable $e) {
    echo "Migration Error: " . $e->getMessage() . "\n";
    exit(1);
}
