<?php
/**
 * Student badge cron runner.
 *
 * Example:
 * php process/run_student_badge_cron.php
 * php process/run_student_badge_cron.php --limit=1000
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo "CLI only.\n";
    exit(1);
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/student/badges.php';

$limit = 500;
foreach ($argv as $arg) {
    if (strpos($arg, '--limit=') === 0) {
        $limit = max(1, (int)substr($arg, 8));
    }
}

$engine = new StudentBadgeEngine($pdo);
$summary = $engine->evaluateActiveStudents($limit);

echo json_encode($summary, JSON_PRETTY_PRINT) . PHP_EOL;
exit(empty($summary['success']) ? 1 : 0);
