<?php
$sql = file_get_contents(__DIR__ . '/Database.sql');

preg_match_all('/INSERT INTO `([^`]+)` \(([^)]+)\)\s*VALUES\s*(.*?);/s', $sql, $matches, PREG_SET_ORDER);

foreach ($matches as $m) {
    $tbl = $m[1];
    $cols = $m[2];
    $vals = trim($m[3]);
    echo "====================================================\n";
    echo "TABLE: $tbl\n";
    echo "COLUMNS: $cols\n";
    echo "DATA:\n$vals\n\n";
}
