<?php
require 'c:/Users/dell/Downloads/public_html (4)/config/db.php';
$stmt = $pdo->query("SELECT id, full_name, level_name, total_points, status FROM sa_students");
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($r);
}
