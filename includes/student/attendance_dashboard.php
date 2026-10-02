<?php
/**
 * Attendance Dashboard Widgets
 * 
 * Displays attendance metrics, bonuses, and promotion information
 * for the student dashboard
 */

// This file should be included from student-dashboard.php
if (!isset($pdo) || !isset($_SESSION['student_id'])) {
    http_response_code(403);
    die('Unauthorized access');
}

$studentId = $_SESSION['student_id'];

try {
    // Load attendance classes
    require_once __DIR__ . '/attendance_scoring.php';
    require_once __DIR__ . '/monthly_attendance.php';
    require_once __DIR__ . '/attendance_bonus.php';
    
    $scorer = new AttendanceScorer($pdo);
    $calculator = new MonthlyAttendanceEngine($pdo);
    $bonusEngine = new AttendanceBonusEngine($pdo);
    
    // Get current month attendance
    $currentAttendance = $scorer->getCurrentMonthAttendance($studentId);
    
    // Get attendance trend (last 6 months)
    $trend = $scorer->getAttendanceTrend($studentId, 6);
    
    // Get days needed to reach 90%
    $daysNeeded = $scorer->daysNeededForTargetAttendance($studentId, 90);
    
    // Get bonus statistics
    $bonusStats = $bonusEngine->getBonusStatistics($studentId);
    $bonusHistory = $bonusEngine->getBonusHistory($studentId, 6);
    
    // Get promotion eligibility
    $promotionStatus = $calculator->checkPromotionEligibilityByAttendance($studentId);
    
    // Get student summary
    $studentSummary = $calculator->getStudentAttendanceSummary($studentId, 6);
    
} catch (Exception $e) {
    $error = $e->getMessage();
}

?>

<!-- Attendance Dashboard Widgets -->
<div class="mt-6 grid gap-6 md:grid-cols-2">
    
    <!-- Widget 1: Current Month Attendance Stats -->
    <div class="relative overflow-hidden rounded-3xl border border-slate-100 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-md">
        <div class="flex items-center gap-3 mb-6">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                <i class="fa-solid fa-chart-simple text-lg"></i>
            </span>
            <h3 class="text-base font-extrabold text-slate-800">Current Month Attendance</h3>
        </div>
        
        <div class="grid grid-cols-2 gap-4 mb-6">
            <div class="rounded-2xl bg-slate-50 p-4 text-center border border-slate-100/50">
                <div class="text-3xl font-black text-slate-900">
                    <?php echo $currentAttendance['attended']; ?>/<?php echo $currentAttendance['eligible']; ?>
                </div>
                <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 mt-1">Days Attended</div>
            </div>
            
            <div class="rounded-2xl bg-slate-50 p-4 text-center border border-slate-100/50">
                <div class="text-3xl font-black text-slate-900">
                    <?php echo number_format($currentAttendance['percentage'], 1); ?>%
                </div>
                <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 mt-1">Attendance Rate</div>
            </div>
        </div>
        
        <!-- Progress Bar -->
        <div class="mb-5">
            <div class="flex justify-between items-center text-xs font-semibold text-slate-500 mb-2">
                <span>Progress to 90% Target</span>
                <span class="<?php echo ($currentAttendance['percentage'] >= 90) ? 'text-emerald-600' : 'text-slate-500'; ?>">
                    <?php 
                    if ($currentAttendance['percentage'] >= 90) {
                        echo '✓ Goal Achieved!';
                    } else {
                        echo round(90 - $currentAttendance['percentage'], 1) . '% left';
                    }
                    ?>
                </span>
            </div>
            <div class="h-2.5 w-full rounded-full bg-slate-100 overflow-hidden">
                <div class="h-full rounded-full bg-emerald-500 transition-all duration-500" style="width: <?php echo min(100, ($currentAttendance['percentage'] / 90) * 100); ?>%"></div>
            </div>
        </div>
        
        <!-- Days Needed Info -->
        <?php if ($daysNeeded['achievable']): ?>
        <div class="rounded-2xl bg-emerald-50/50 border border-emerald-100/50 p-4 text-xs text-emerald-950 flex items-start gap-2.5">
            <i class="fa-solid fa-circle-check text-emerald-600 mt-0.5 text-sm"></i>
            <div>
                <strong>On Track:</strong> Attend next <span class="font-bold"><?php echo $daysNeeded['days_needed']; ?></span> of <span class="font-bold"><?php echo $daysNeeded['days_remaining']; ?></span> remaining days to lock in 90%.
            </div>
        </div>
        <?php else: ?>
        <div class="rounded-2xl bg-rose-50/50 border border-rose-100/50 p-4 text-xs text-rose-950 flex items-start gap-2.5">
            <i class="fa-solid fa-circle-exclamation text-rose-600 mt-0.5 text-sm"></i>
            <div>
                <strong>At Risk:</strong> You need <span class="font-bold"><?php echo $daysNeeded['days_needed']; ?></span> more days but only <span class="font-bold"><?php echo $daysNeeded['days_remaining']; ?></span> remain this month.
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Widget 2: Bonus History -->
    <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-md">
        <div class="flex items-center gap-3 mb-6">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                <i class="fa-solid fa-gift text-lg"></i>
            </span>
            <h3 class="text-base font-extrabold text-slate-800">Bonus History</h3>
        </div>
        
        <div class="grid grid-cols-2 gap-4 mb-6">
            <div class="rounded-2xl bg-slate-50 p-4 text-center border border-slate-100/50">
                <div class="text-3xl font-black text-slate-900">
                    <?php echo $bonusStats['total_bonuses'] ?? 0; ?>
                </div>
                <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 mt-1">Total Bonuses</div>
            </div>
            <div class="rounded-2xl bg-slate-50 p-4 text-center border border-slate-100/50">
                <div class="text-3xl font-black text-emerald-600">
                    +<?php echo $bonusStats['total_bonus_points'] ?? 0; ?>
                </div>
                <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 mt-1">Bonus Points</div>
            </div>
        </div>
        
        <!-- Recent Bonuses Table -->
        <?php if (!empty($bonusHistory)): ?>
        <div class="overflow-hidden rounded-2xl border border-slate-100">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-400 font-semibold uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3">Month</th>
                        <th class="px-4 py-3 text-center">Rate</th>
                        <th class="px-4 py-3 text-right">Points</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                    <?php foreach (array_slice($bonusHistory, 0, 4) as $bonus): ?>
                    <tr>
                        <td class="px-4 py-3">
                            <?php echo date('M Y', mktime(0, 0, 0, $bonus['month'], 1, $bonus['year'])); ?>
                        </td>
                        <td class="px-4 py-3 text-center font-bold">
                            <?php echo number_format($bonus['attendance_percentage'], 1); ?>%
                        </td>
                        <td class="px-4 py-3 text-right font-black text-emerald-600">
                            +<?php echo $bonus['bonus_points_awarded']; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="rounded-2xl bg-slate-50 border border-slate-100/50 p-4 text-center text-xs text-slate-400">
            No bonus history yet. Maintain 90%+ attendance to earn bonuses.
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Widget 3: Attendance Trend -->
    <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-md md:col-span-2">
        <div class="flex items-center gap-3 mb-6">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-50 text-sky-600">
                <i class="fa-solid fa-chart-line text-lg"></i>
            </span>
            <h3 class="text-base font-extrabold text-slate-800">Attendance Trend (Last 6 Months)</h3>
        </div>
        
        <div class="overflow-x-auto pb-2">
            <div class="flex gap-4 min-w-max">
                <?php foreach ($trend as $month): ?>
                <div class="flex-1 min-w-[120px] rounded-2xl bg-slate-50 p-4 border border-slate-100/50 text-center transition-all hover:bg-slate-100/50">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2">
                        <?php echo $month['display']; ?>
                    </div>
                    <div class="text-2xl font-black text-slate-800 mb-1">
                        <?php echo number_format($month['percentage'], 0); ?>%
                    </div>
                    <div class="text-[10px] font-semibold text-slate-500 mb-3">
                        <?php echo $month['attended']; ?>/<?php echo $month['eligible']; ?> days
                    </div>
                    <div>
                        <?php if ($month['percentage'] >= 90): ?>
                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-1 text-[9px] font-bold text-emerald-700 border border-emerald-100">✓ Bonus</span>
                        <?php elseif ($month['percentage'] >= 80): ?>
                            <span class="inline-flex items-center rounded-full bg-sky-50 px-2 py-1 text-[9px] font-bold text-sky-700 border border-sky-100">Good</span>
                        <?php elseif ($month['percentage'] >= 70): ?>
                            <span class="inline-flex items-center rounded-full bg-amber-50 px-2 py-1 text-[9px] font-bold text-amber-700 border border-amber-100">Fair</span>
                        <?php else: ?>
                            <span class="inline-flex items-center rounded-full bg-rose-50 px-2 py-1 text-[9px] font-bold text-rose-700 border border-rose-100">Low</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Widget 4: Promotion Impact -->
    <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-md md:col-span-2">
        <div class="flex items-center gap-3 mb-6">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-50 text-rose-600">
                <i class="fa-solid fa-bullseye text-lg"></i>
            </span>
            <h3 class="text-base font-extrabold text-slate-800">Promotion Impact</h3>
        </div>
        
        <div class="grid gap-4 sm:grid-cols-2 mb-6">
            <div class="rounded-2xl bg-slate-50 p-4 border border-slate-100/50">
                <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Current Level</div>
                <div class="text-lg font-black text-slate-800 mt-1">
                    <?php echo $promotionStatus['current_level'] ?? 'N/A'; ?>
                </div>
            </div>
            
            <div class="rounded-2xl bg-slate-50 p-4 border border-slate-100/50">
                <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Required Attendance</div>
                <div class="text-lg font-black text-slate-800 mt-1">
                    <?php echo number_format($promotionStatus['required_attendance'], 0); ?>%
                </div>
            </div>
        </div>
        
        <!-- Status Bar -->
        <div class="rounded-2xl bg-slate-50 p-5 border border-slate-100/50 mb-6">
            <div class="flex justify-between items-center text-xs font-semibold text-slate-500 mb-2">
                <span>Current Month Attendance</span>
                <span class="text-base font-black text-slate-800"><?php echo number_format($promotionStatus['current_attendance'], 1); ?>%</span>
            </div>
            <div class="h-3 w-full rounded-full bg-slate-100 overflow-hidden mb-2">
                <div class="h-full rounded-full transition-all duration-500 <?php echo ($promotionStatus['current_attendance'] >= $promotionStatus['required_attendance']) ? 'bg-emerald-500' : 'bg-rose-500'; ?>" style="width: <?php echo min(100, ($promotionStatus['current_attendance'] / 100) * 100); ?>%"></div>
            </div>
        </div>
        
        <!-- Eligibility Status -->
        <?php if ($promotionStatus['eligible']): ?>
        <div class="rounded-2xl bg-emerald-50/50 border border-emerald-100/50 p-4 text-xs text-emerald-950 flex items-start gap-2.5">
            <i class="fa-solid fa-circle-check text-emerald-600 mt-0.5 text-sm"></i>
            <div>
                <strong class="text-emerald-900">Promotion Eligible:</strong> Your attendance record meets the promotion requirements for your current level.
            </div>
        </div>
        <?php else: ?>
        <div class="rounded-2xl bg-rose-50/50 border border-rose-100/50 p-4 text-xs text-rose-950 flex items-start gap-2.5">
            <i class="fa-solid fa-circle-exclamation text-rose-600 mt-0.5 text-sm"></i>
            <div>
                <strong class="text-rose-900">Attendance Below Requirement:</strong> <?php echo count($promotionStatus['issues'] ?? []) > 0 ? $promotionStatus['issues'][0] : 'Your attendance is below the requirement.'; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
