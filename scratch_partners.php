<?php
$lines = file('themes/home_theme1_final.php');
foreach ($lines as $i => $line) {
    if (stripos($line, 'partner') !== false || stripos($line, 'zappile') !== false || stripos($line, 'zapilee') !== false) {
        $ln = $i + 1;
        echo "Line $ln: " . trim($line) . "\n";
    }
}
?>
