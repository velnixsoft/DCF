<?php
require 'c:/Users/dell/Downloads/public_html (4)/config/db.php';
require 'c:/Users/dell/Downloads/public_html (4)/includes/student/levels.php';

$levelEngine = new StudentLevelEngine($pdo);
$result = $levelEngine->evaluatePromotion(1, ['apply' => false]);
print_r($result);
