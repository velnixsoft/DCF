<?php
/**
 * Notification Queue Cron Job Processor
 * 
 * CLI-ONLY batch processor for sending queued notifications
 * 
 * Usage:
 *   php run_notification_queue_cron.php              # Process 100 notifications
 *   php run_notification_queue_cron.php --limit=200  # Process 200 notifications
 *   php run_notification_queue_cron.php --retry      # Retry failed notifications
 *   php run_notification_queue_cron.php --dry-run    # Test without sending
 *   php run_notification_queue_cron.php --stats      # Show queue statistics
 * 
 * Security:
 *   - CLI-only (no web access)
 *   - Requires command line execution
 * 
 * Example crontab entry:
 *   * /15 * * * * /usr/bin/php /var/www/html/process/run_notification_queue_cron.php >> /var/log/notification_queue.log 2>&1
 */

// Security: CLI-only
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die(json_encode(['success' => false, 'error' => 'CLI access only']));
}

// Required files
require '../config/db.php';
require '../includes/functions.php';
require '../includes/member_module.php';
require '../includes/student/notification_manager.php';
require '../includes/student/notification_queue.php';
require '../includes/student/notification_template.php';

// Execution context
$startTime = time();
$executionId = date('Y-m-d H:i:s');

// Parse command line arguments
$limit = 100;
$dryRun = false;
$retry = false;
$statsOnly = false;

for ($i = 1; $i < $argc; $i++) {
    $arg = $argv[$i];
    
    if ($arg === '--dry-run') {
        $dryRun = true;
    } elseif ($arg === '--retry') {
        $retry = true;
    } elseif ($arg === '--stats') {
        $statsOnly = true;
    } elseif (strpos($arg, '--limit=') === 0) {
        $limit = (int)substr($arg, 8);
    } elseif ($arg === '--help' || $arg === '-h') {
        echo "Usage: php run_notification_queue_cron.php [options]\n";
        echo "Options:\n";
        echo "  --limit=N       Process max N notifications (default: 100)\n";
        echo "  --retry         Retry failed notifications\n";
        echo "  --dry-run       Test without sending emails\n";
        echo "  --stats         Show queue statistics\n";
        echo "  --help, -h      Show this help\n";
        exit(0);
    }
}

// Initialize result
$result = [
    'success' => false,
    'execution_id' => $executionId,
    'dry_run' => $dryRun,
    'timestamp' => date('Y-m-d H:i:s'),
    'stages' => []
];

try {
    // Stage 1: Load settings
    $stage = [];
    $settings = mm_load_settings($pdo);
    if (empty($settings)) {
        throw new \Exception("Could not load email settings");
    }
    $stage['success'] = true;
    $stage['message'] = 'Email settings loaded';
    $result['stages'][] = ['stage' => 'settings', 'result' => $stage];
    echo "[SETTINGS] Email settings loaded\n";

    // Stage 2: Show stats if requested
    if ($statsOnly) {
        $stage = [];
        $queue = new NotificationQueue($pdo, false);
        $stats = $queue->getQueueStats();
        $statsByType = $queue->getQueueStatsByType();
        
        $stage['success'] = true;
        $stage['queue_stats'] = $stats;
        $stage['stats_by_type'] = $statsByType;
        $stage['message'] = 'Queue statistics';
        $result['stages'][] = ['stage' => 'statistics', 'result' => $stage];
        
        echo "\n[QUEUE STATISTICS]\n";
        echo "Total Notifications: {$stats['total']}\n";
        echo "Pending (not sent): {$stats['pending']}\n";
        echo "Sent: {$stats['sent']}\n";
        echo "Failed (retrying): {$stats['failed_retrying']}\n";
        
        echo "\n[STATISTICS BY TYPE]\n";
        foreach ($statsByType as $type) {
            echo "  {$type['notification_type']}: " . $type['pending'] . " pending, " . $type['sent'] . " sent\n";
        }
        
        $result['success'] = true;
        $result['message'] = 'Queue statistics retrieved';
        echo "\nJSON Output:\n";
        echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
        exit(0);
    }

    // Stage 3: Initialize queue processor
    $stage = [];
    $queue = new NotificationQueue($pdo, false);
    $stage['success'] = true;
    $stage['message'] = 'Queue processor initialized';
    $result['stages'][] = ['stage' => 'initialization', 'result' => $stage];
    echo "[INITIALIZATION] Queue processor initialized\n";

    // Stage 4: Process notifications
    $stage = [];
    echo "\n[PROCESSING NOTIFICATIONS]\n";
    
    if ($dryRun) {
        echo "DRY RUN MODE - No emails will be sent\n";
        $stats = $queue->getQueueStats();
        $stage['dry_run_pending'] = $stats['pending'];
        $stage['message'] = "DRY RUN: Would process {$stats['pending']} notifications";
        echo "Notifications in queue: {$stats['pending']}\n";
    } else {
        $processResult = $queue->processPendingNotifications($settings, $limit);
        $stage['total_pending'] = $processResult['total_pending'];
        $stage['sent'] = $processResult['sent'];
        $stage['failed'] = $processResult['failed'];
        $stage['skipped'] = $processResult['skipped'];
        $stage['message'] = "Processed {$processResult['total_pending']} notifications";
        
        echo "Total pending: {$processResult['total_pending']}\n";
        echo "Successfully sent: {$processResult['sent']}\n";
        echo "Failed: {$processResult['failed']}\n";
        echo "Skipped (preferences): {$processResult['skipped']}\n";
        
        if (!empty($processResult['errors'])) {
            echo "\nErrors:\n";
            foreach ($processResult['errors'] as $error) {
                echo "  - Notification {$error['notification_id']}: {$error['error']}\n";
            }
            $stage['errors'] = $processResult['errors'];
        }
    }
    
    $result['stages'][] = ['stage' => 'processing', 'result' => $stage];

    // Stage 5: Retry failed (if requested)
    if ($retry && !$dryRun) {
        $stage = [];
        echo "\n[RETRY FAILED]\n";
        
        $retryResult = $queue->retryFailedEmails($settings, 50);
        $stage['retried'] = $retryResult['retried'];
        $stage['sent'] = $retryResult['sent'];
        $stage['failed'] = $retryResult['failed'];
        $stage['message'] = "Retried {$retryResult['retried']} failed notifications";
        
        echo "Retried: {$retryResult['retried']}\n";
        echo "Sent: {$retryResult['sent']}\n";
        echo "Failed: {$retryResult['failed']}\n";
        
        $result['stages'][] = ['stage' => 'retry', 'result' => $stage];
    }

    // Stage 6: Clean up stuck notifications
    $stage = [];
    echo "\n[CLEANUP]\n";
    $cleaned = $queue->cleanStuckNotifications(7);
    $stage['cleaned' ] = $cleaned;
    $stage['message'] = "Cleaned up {$cleaned} stuck notifications";
    echo "Cleaned stuck notifications: {$cleaned}\n";
    $result['stages'][] = ['stage' => 'cleanup', 'result' => $stage];

    // Overall success
    $result['success'] = true;
    $result['execution_time_seconds'] = time() - $startTime;
    $result['message'] = 'Notification queue processor completed successfully';
    
    echo "\n[COMPLETION]\n";
    echo "Execution time: " . $result['execution_time_seconds'] . " seconds\n";
    echo "Status: SUCCESS\n";

} catch (\Exception $e) {
    $result['success'] = false;
    $result['error'] = $e->getMessage();
    $result['message'] = 'Notification queue processor failed';
    $result['execution_time_seconds'] = time() - $startTime;
    
    echo "\n[ERROR]\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "Status: FAILED\n";
}

// Output JSON result (for logging/automation)
echo "\n[JSON OUTPUT]\n";
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

// Exit with appropriate code
exit($result['success'] ? 0 : 1);
