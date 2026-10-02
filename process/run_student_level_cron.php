<?php
/**
 * Student level cron runner.
 *
 * Example:
 * php process/run_student_level_cron.php
 * php process/run_student_level_cron.php --allow-demotion --limit=1000
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo "CLI only.\n";
    exit(1);
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/student/levels.php';

$allowDemotion = in_array('--allow-demotion', $argv, true);
$limit = 500;

foreach ($argv as $arg) {
    if (strpos($arg, '--limit=') === 0) {
        $limit = max(1, (int)substr($arg, 8));
    }
}

$engine = new StudentLevelEngine($pdo);
$summary = $engine->evaluateActiveStudents($allowDemotion, $limit);

echo json_encode($summary, JSON_PRETTY_PRINT) . PHP_EOL;
exit(empty($summary['success']) ? 1 : 0);
