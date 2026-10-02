<?php
require '../../config/db.php';
require '../../includes/functions.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) exit;
if (!canAccessModule($pdo, 'admin', 'page.backup_db')) { http_response_code(403); exit; }

$tables = [];
$result = $pdo->query('SHOW TABLES');
while($row = $result->fetch(PDO::FETCH_NUM)) {
    $tables[] = $row[0];
}

$sqlScript = "-- NGO System Database Backup\n";
$sqlScript .= "-- Date: " . date('d-M-Y H:i:s') . "\n\n";

foreach($tables as $table) {

    $row = $pdo->query('SHOW CREATE TABLE '.$table)->fetch(PDO::FETCH_NUM);
    $sqlScript .= "\n\n" . $row[1] . ";\n\n";

    $rows = $pdo->query('SELECT * FROM '.$table)->fetchAll(PDO::FETCH_NUM);
    foreach($rows as $row) {
        $sqlScript .= "INSERT INTO $table VALUES(";
        $values = [];
        foreach($row as $data) {
            $values[] = $data === null ? "NULL" : "'" . addslashes($data) . "'";
        }
        $sqlScript .= implode(', ', $values);
        $sqlScript .= ");\n";
    }
}

$backup_name = 'db_backup_' . date('Y_m_d_H_i') . '.sql';
header('Content-Type: application/octet-stream');
header("Content-Transfer-Encoding: Binary"); 
header("Content-disposition: attachment; filename=\"".$backup_name."\""); 
echo $sqlScript; exit;
?>
