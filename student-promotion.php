<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/student/portal_helpers.php';

$student = student_portal_require_student($pdo, 'promotion');
$levelMap = [
    'Student Ambassador' => ['min' => 0, 'next_min' => 50, 'next_level' => 'Community Intern'],
    'Community Intern' => ['min' => 50, 'next_min' => 150, 'next_level' => 'Senior Intern'],
    'Senior Intern' => ['min' => 150, 'next_min' => 400, 'next_level' => 'Campus Ambassador'],
    'Campus Ambassador' => ['min' => 400, 'next_min' => 800, 'next_level' => 'Team Leader'],
    'Team Leader' => ['min' => 800, 'next_min' => 1500, 'next_level' => 'City Coordinator'],
    'City Coordinator' => ['min' => 1500, 'next_min' => 3000, 'next_level' => 'State Coordinator'],
    'State Coordinator' => ['min' => 3000, 'next_min' => 999999, 'next_level' => 'Max Level'],
];
$currentLevel = $student['level_name'] ?? 'Student Ambassador';
$levelInfo = $levelMap[$currentLevel] ?? $levelMap['Student Ambassador'];
$points = (int)($student['total_points'] ?? 0);
$progress = min(100, max(0, (int)round((max(0, $points - $levelInfo['min']) / max(1, $levelInfo['next_min'] - $levelInfo['min'])) * 100)));

$metrics = [
    'attendance' => 0,
    'referrals' => 0,
    'vendor_leads' => 0,
    'campaigns' => 0,
];

try {
    if ($pdo->query("SHOW TABLES LIKE 'sa_attendance_logs'")->fetchColumn()) {
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT attendance_date) FROM sa_attendance_logs WHERE student_id = ? AND status = 'Present'");
        $stmt->execute([(int)$student['id']]);
        $metrics['attendance'] = (int)$stmt->fetchColumn();
    }
    if ($pdo->query("SHOW TABLES LIKE 'sa_referrals'")->fetchColumn()) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM sa_referrals WHERE referrer_student_id = ? AND verification_status = 'verified' AND deleted_at IS NULL");
        $stmt->execute([(int)$student['id']]);
        $metrics['referrals'] = (int)$stmt->fetchColumn();
    }
    if ($pdo->query("SHOW TABLES LIKE 'sa_vendor_leads'")->fetchColumn()) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM sa_vendor_leads WHERE student_id = ? AND verification_status = 'verified' AND deleted_at IS NULL");
        $stmt->execute([(int)$student['id']]);
        $metrics['vendor_leads'] = (int)$stmt->fetchColumn();
    }
    if ($pdo->query("SHOW TABLES LIKE 'sa_task_submissions'")->fetchColumn()) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM sa_task_submissions WHERE student_id = ? AND status = 'Approved'");
        $stmt->execute([(int)$student['id']]);
        $metrics['campaigns'] = (int)$stmt->fetchColumn();
    }
} catch (Throwable $e) {
}

student_portal_render_shell_start($pdo, $student, 'Promotion Progress', 'promotion');
?>

<div class="grid gap-6 lg:grid-cols-[1fr_320px]">
    <!-- Left Column: Progress Card & Metrics -->
    <section class="rounded-3xl border border-slate-100 bg-white p-8 shadow-sm transition-all duration-300 hover:shadow-md">
        <div class="flex items-center gap-4 border-b border-slate-100 pb-6 mb-8">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                <i class="fa-solid fa-chart-line text-xl"></i>
            </div>
            <div>
                <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Promotion Progress</h2>
                <p class="text-sm text-slate-500">Track milestones and promotion conditions based on points, attendance, and campaign approvals.</p>
            </div>
        </div>

        <!-- Level Progress Card -->
        <div class="rounded-3xl bg-slate-50 border border-slate-100 p-6 shadow-inner relative overflow-hidden group">
            <div class="flex items-center justify-between text-sm">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Current Rank Level</p>
                    <h3 class="text-lg font-black text-slate-800 mt-1"><?php echo htmlspecialchars($currentLevel); ?></h3>
                </div>
                <div class="text-right">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Current Balance</p>
                    <p class="text-lg font-black text-emerald-600 mt-1"><?php echo (int)$points; ?> points</p>
                </div>
            </div>
            
            <div class="mt-5 h-3 overflow-hidden rounded-full bg-white border border-slate-100">
                <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-emerald-650 transition-all duration-500" style="width: <?php echo $progress; ?>%"></div>
            </div>
            
            <div class="mt-4 flex items-center justify-between text-xs font-semibold text-slate-500">
                <span class="flex items-center gap-1">
                    <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                    Next: <strong class="text-slate-800"><?php echo htmlspecialchars($levelInfo['next_level']); ?></strong>
                </span>
                <span><?php echo $progress; ?>% Completed</span>
            </div>
        </div>

        <!-- Metrics Grid -->
        <div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-5 transition-all hover:bg-slate-50">
                <div class="flex items-center justify-between text-slate-400">
                    <span class="text-xs font-bold uppercase tracking-wider">Attendance</span>
                    <i class="fa-regular fa-calendar-check text-base text-slate-400"></i>
                </div>
                <p class="mt-3 text-3xl font-black text-slate-900"><?php echo (int)$metrics['attendance']; ?></p>
                <p class="text-[10px] text-slate-400 mt-1 font-semibold">Total marked days</p>
            </div>
            
            <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-5 transition-all hover:bg-slate-50">
                <div class="flex items-center justify-between text-slate-400">
                    <span class="text-xs font-bold uppercase tracking-wider">Referrals</span>
                    <i class="fa-solid fa-users text-base text-slate-400"></i>
                </div>
                <p class="mt-3 text-3xl font-black text-slate-900"><?php echo (int)$metrics['referrals']; ?></p>
                <p class="text-[10px] text-slate-400 mt-1 font-semibold">Verified signups</p>
            </div>
            
            <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-5 transition-all hover:bg-slate-50">
                <div class="flex items-center justify-between text-slate-400">
                    <span class="text-xs font-bold uppercase tracking-wider">Vendor Leads</span>
                    <i class="fa-solid fa-store text-base text-slate-400"></i>
                </div>
                <p class="mt-3 text-3xl font-black text-slate-900"><?php echo (int)$metrics['vendor_leads']; ?></p>
                <p class="text-[10px] text-slate-400 mt-1 font-semibold">Verified leads</p>
            </div>
            
            <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-5 transition-all hover:bg-slate-50">
                <div class="flex items-center justify-between text-slate-400">
                    <span class="text-xs font-bold uppercase tracking-wider">Campaigns</span>
                    <i class="fa-solid fa-list-check text-base text-slate-400"></i>
                </div>
                <p class="mt-3 text-3xl font-black text-slate-900"><?php echo (int)$metrics['campaigns']; ?></p>
                <p class="text-[10px] text-slate-400 mt-1 font-semibold">Approved tasks</p>
            </div>
        </div>

        <!-- Requirements Table -->
        <div class="mt-8 rounded-3xl border border-slate-100 p-6">
            <h3 class="text-base font-extrabold text-slate-800 tracking-tight mb-4 flex items-center gap-2">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                Next Level Requirements
            </h3>
            
            <div class="grid gap-4 sm:grid-cols-2 text-xs font-semibold text-slate-600">
                <div class="rounded-2xl bg-slate-50/50 border border-slate-100/50 px-4 py-3.5 flex items-center gap-3">
                    <i class="fa-solid fa-circle-check text-emerald-600 text-base shrink-0"></i>
                    <div>
                        <p class="text-slate-800 font-bold">Min. Points Required</p>
                        <p class="text-[10px] text-slate-400 font-normal mt-0.5"><?php echo $levelInfo['next_min']; ?> points to unlock <?php echo htmlspecialchars($levelInfo['next_level']); ?>.</p>
                    </div>
                </div>
                
                <div class="rounded-2xl bg-slate-50/50 border border-slate-100/50 px-4 py-3.5 flex items-center gap-3">
                    <i class="fa-solid fa-circle-check text-emerald-600 text-base shrink-0"></i>
                    <div>
                        <p class="text-slate-800 font-bold">Attendance Policy</p>
                        <p class="text-[10px] text-slate-400 font-normal mt-0.5">Maintain healthy daily presence scores.</p>
                    </div>
                </div>
                
                <div class="rounded-2xl bg-slate-50/50 border border-slate-100/50 px-4 py-3.5 flex items-center gap-3">
                    <i class="fa-solid fa-circle-check text-emerald-600 text-base shrink-0"></i>
                    <div>
                        <p class="text-slate-800 font-bold">Referral Conversions</p>
                        <p class="text-[10px] text-slate-400 font-normal mt-0.5">Invited ambassadors must be approved.</p>
                    </div>
                </div>
                
                <div class="rounded-2xl bg-slate-50/50 border border-slate-100/50 px-4 py-3.5 flex items-center gap-3">
                    <i class="fa-solid fa-circle-check text-emerald-600 text-base shrink-0"></i>
                    <div>
                        <p class="text-slate-800 font-bold">Campaign Performance</p>
                        <p class="text-[10px] text-slate-400 font-normal mt-0.5">Admin-reviewed evidence verification.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Sidebar: Actions -->
    <aside class="space-y-6">
        <section class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-extrabold text-slate-900 tracking-tight">How to promote?</h3>
            <p class="mt-3 text-xs text-slate-500 leading-relaxed">The system automatically scans student metrics nightly. Ensure your tasks are completed, attendance is marked, and referrals are verified to trigger promotion events instantly.</p>
            <a href="student-dashboard.php" class="inline-flex w-full justify-center items-center rounded-2xl bg-slate-900 hover:bg-black text-white text-xs font-bold py-3.5 mt-5 shadow transition-all">
                Review Dashboard Tasks
            </a>
        </section>
    </aside>
</div>

<?php student_portal_render_shell_end(); ?>
