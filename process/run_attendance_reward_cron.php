<?php
/**
 * Attendance Reward Cron Job Processor
 * 
 * CLI-ONLY batch processor for monthly attendance bonuses
 * 
 * Usage:
 *   php run_attendance_reward_cron.php                          # Process previous month
 *   php run_attendance_reward_cron.php --month=2024-01         # Process specific month
 *   php run_attendance_reward_cron.php --dry-run               # Test without making changes
 *   php run_attendance_reward_cron.php --year=2024 --month=3   # Process by year/month numbers
 * 
 * Security:
 *   - CLI-only (no web access)
 *   - Must be run by authorized user
 *   - Returns JSON for easy logging
 * 
 * Example crontab entry:
 *   0 2 1 * * /usr/bin/php /var/www/html/process/run_attendance_reward_cron.php >> /var/log/attendance_cron.log 2>&1
 */

// Security: CLI-only
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die(json_encode(['success' => false, 'error' => 'CLI access only']));
}

// Required files
require '../config/db.php';
require '../includes/functions.php';
require '../includes/student/attendance_scoring.php';
require '../includes/student/monthly_attendance.php';
require '../includes/student/attendance_bonus.php';

// Execution context
$startTime = time();
$executionId = date('Y-m-d H:i:s');

// Parse command line arguments
$year = null;
$month = null;
$dryRun = false;
$forceMonth = null;

for ($i = 1; $i < $argc; $i++) {
    $arg = $argv[$i];
    
    if ($arg === '--dry-run') {
        $dryRun = true;
    } elseif (strpos($arg, '--year=') === 0) {
        $year = (int)substr($arg, 7);
    } elseif (strpos($arg, '--month=') === 0) {
        $forceMonth = substr($arg, 8);
    } elseif (strpos($arg, '--month=') === 0) {
        $month = (int)substr($arg, 8);
    } elseif ($arg === '--help' || $arg === '-h') {
        echo "Usage: php run_attendance_reward_cron.php [options]\n";
        echo "Options:\n";
        echo "  --year=YYYY           Specify year\n";
        echo "  --month=N             Specify month (1-12)\n";
        echo "  --month=YYYY-MM       Process specific YYYY-MM\n";
        echo "  --dry-run             Test without making changes\n";
        echo "  --help, -h            Show this help\n";
        exit(0);
    }
}

// Parse YYYY-MM format if provided
if ($forceMonth) {
    if (preg_match('/^(\d{4})-(\d{1,2})$/', $forceMonth, $matches)) {
        $year = (int)$matches[1];
        $month = (int)$matches[2];
    }
}

// Default to previous month if not specified
if ($year === null || $month === null) {
    $date = new DateTime('first day of last month');
    if ($year === null) {
        $year = (int)$date->format('Y');
    }
    if ($month === null) {
        $month = (int)$date->format('m');
    }
}

// Initialize result
$result = [
    'success' => false,
    'execution_id' => $executionId,
    'dry_run' => $dryRun,
    'year' => $year,
    'month' => $month,
    'month_display' => date('F Y', mktime(0, 0, 0, $month, 1, $year)),
    'timestamp' => date('Y-m-d H:i:s'),
    'stages' => []
];

try {
    // Stage 1: Validate inputs
    $stage = [];
    if ($month < 1 || $month > 12) {
        throw new Exception("Invalid month: {$month}. Must be 1-12");
    }
    if ($year < 2020 || $year > 2099) {
        throw new Exception("Invalid year: {$year}. Must be between 2020-2099");
    }
    $stage['success'] = true;
    $stage['message'] = "Inputs validated";
    $result['stages'][] = ['stage' => 'validation', 'result' => $stage];

    // Stage 2: Initialize engines
    $stage = [];
    $scorer = new AttendanceScorer($pdo, false);
    $calculator = new MonthlyAttendanceEngine($pdo, false);
    $bonusEngine = new AttendanceBonusEngine($pdo, false);
    $stage['success'] = true;
    $stage['message'] = 'Engines initialized';
    $result['stages'][] = ['stage' => 'initialization', 'result' => $stage];

    // Stage 3: Calculate monthly attendance for all students
    $stage = [];
    echo "[ATTENDANCE CALCULATION]\n";
    $calcResult = $calculator->processAllStudentsMonthlyAttendance($year, $month, 0);
    $stage['success'] = $calcResult['success'];
    $stage['total_students'] = $calcResult['total_students'];
    $stage['newly_processed'] = $calcResult['processed'];
    $stage['already_processed'] = $calcResult['already_processed'];
    $stage['failed'] = $calcResult['failed'];
    $stage['message'] = "Attendance calculated for {$calcResult['total_students']} students";
    echo "  Total students: {$calcResult['total_students']}\n";
    echo "  Newly processed: {$calcResult['processed']}\n";
    echo "  Already processed: {$calcResult['already_processed']}\n";
    echo "  Failed: {$calcResult['failed']}\n";
    $result['stages'][] = ['stage' => 'attendance_calculation', 'result' => $stage];

    if (!$calcResult['success']) {
        throw new Exception("Attendance calculation failed");
    }

    // Stage 4: Get monthly statistics
    $stage = [];
    $stats = $calculator->getMonthlyStatistics($year, $month);
    $stage['success'] = true;
    $stage['statistics'] = $stats;
    $stage['message'] = "Monthly statistics retrieved";
    echo "\n[MONTHLY STATISTICS]\n";
    echo "  Average attendance: " . round($stats['avg_attendance'], 2) . "%\n";
    echo "  Students above 90%: {$stats['students_above_90']}\n";
    echo "  Bonuses already awarded: {$stats['bonuses_awarded']}\n";
    $result['stages'][] = ['stage' => 'statistics', 'result' => $stage];

    // Stage 5: Process bonuses
    $stage = [];
    echo "\n[BONUS PROCESSING]\n";
    
    if ($dryRun) {
        echo "  [DRY RUN MODE] - No bonuses will be awarded\n";
        
        // Get what would be awarded
        $sql = "SELECT COUNT(*) as pending FROM attendance_monthly_summary
                WHERE year = ? AND month = ? AND attendance_bonus_awarded = 0 AND is_processed = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$year, $month]);
        $pending = $stmt->fetch(PDO::FETCH_ASSOC)['pending'];
        
        $stage['success'] = true;
        $stage['pending_bonuses'] = $pending;
        $stage['bonuses_awarded'] = 0;
        $stage['message'] = "DRY RUN: {$pending} bonuses would be awarded";
        echo "  Bonuses that would be awarded: {$pending}\n";
    } else {
        $bonusResult = $bonusEngine->processPendingBonuses($year, $month);
        $stage['success'] = $bonusResult['success'];
        $stage['total'] = $bonusResult['total'];
        $stage['processed'] = $bonusResult['processed'];
        $stage['bonuses_awarded'] = $bonusResult['awarded'];
        $stage['failed'] = $bonusResult['failed'];
        $stage['message'] = "Bonuses processed: {$bonusResult['awarded']} awarded to {$bonusResult['processed']} students";
        
        echo "  Bonuses processed: {$bonusResult['processed']}\n";
        echo "  Bonuses awarded: {$bonusResult['awarded']}\n";
        echo "  Failed: {$bonusResult['failed']}\n";
        
        if (!$bonusResult['success']) {
            throw new Exception("Bonus processing failed");
        }
    }
    
    $result['stages'][] = ['stage' => 'bonus_processing', 'result' => $stage];

    // Stage 6: Generate summary report
    $stage = [];
    $topPerformers = $calculator->getTopAttendancePerformers($year, $month, 5);
    $belowThreshold = $calculator->getStudentsBelowThreshold($year, $month, 70, 10);
    
    $stage['success'] = true;
    $stage['top_performers'] = count($topPerformers);
    $stage['below_threshold'] = count($belowThreshold);
    $stage['message'] = "Summary report generated";
    
    echo "\n[SUMMARY]\n";
    echo "  Top performers (90%+): " . count($topPerformers) . "\n";
    echo "  Students below 70%: " . count($belowThreshold) . "\n";
    
    $result['stages'][] = ['stage' => 'summary', 'result' => $stage];

    // Overall success
    $result['success'] = true;
    $result['execution_time_seconds'] = time() - $startTime;
    $result['message'] = 'Attendance reward cron job completed successfully';
    
    echo "\n[COMPLETION]\n";
    echo "  Execution time: " . $result['execution_time_seconds'] . " seconds\n";
    echo "  Status: SUCCESS\n";

} catch (Exception $e) {
    $result['success'] = false;
    $result['error'] = $e->getMessage();
    $result['message'] = 'Attendance reward cron job failed';
    $result['execution_time_seconds'] = time() - $startTime;
    
    echo "\n[ERROR]\n";
    echo "  Message: " . $e->getMessage() . "\n";
    echo "  Status: FAILED\n";
}

// Output JSON result (for logging/automation)
echo "\n[JSON OUTPUT]\n";
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

// Exit with appropriate code
exit($result['success'] ? 0 : 1);
