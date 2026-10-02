<?php
/**
 * Donation Achievement Cron Job Runner
 * 
 * Processes pending donations and awards achievements/milestones.
 * 
 * Usage:
 * php process/run_donation_achievement_cron.php
 * php process/run_donation_achievement_cron.php --limit=1000
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo "CLI only.\n";
    exit(1);
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/student/donation_achievements.php';

$limit = 500;
$debug = false;

foreach ($argv as $arg) {
    if (strpos($arg, '--limit=') === 0) {
        $limit = max(1, (int)substr($arg, 8));
    }
    if ($arg === '--debug') {
        $debug = true;
    }
}

try {
    $engine = new DonationAchievementEngine($pdo, $debug);
    $summary = $engine->processBatchDonations($limit);
    
    $summary['timestamp'] = date('Y-m-d H:i:s');
    $summary['limit'] = $limit;
    $summary['success'] = true;
    
    echo json_encode($summary, JSON_PRETTY_PRINT) . PHP_EOL;
    exit(0);
    
} catch (Exception $e) {
    $error = [
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ];
    echo json_encode($error, JSON_PRETTY_PRINT) . PHP_EOL;
    exit(1);
}
