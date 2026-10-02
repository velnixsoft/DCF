<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/student/portal_helpers.php';

$student = student_portal_require_student($pdo, 'program');
$program = student_portal_active_program($pdo, isset($student['program_id']) ? (int)$student['program_id'] : null);
$activeProgram = $program;
$myProgramId = $student['program_id'] ?? null;
$_SESSION['student_program_csrf'] = $_SESSION['student_program_csrf'] ?? bin2hex(random_bytes(16));

student_portal_render_shell_start($pdo, $student, 'Student Program', 'program');
?>

<div class="grid gap-6 lg:grid-cols-[1fr_360px]">
    <section class="rounded-3xl border border-slate-100 bg-white p-8 shadow-sm transition-all duration-300 hover:shadow-md">
        <div class="flex items-center gap-4">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                <i class="fa-solid fa-briefcase text-xl"></i>
            </div>
            <div>
                <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Program Membership</h2>
                <p class="text-sm text-slate-500">Track and manage your association with our core campaigns.</p>
            </div>
        </div>

        <?php if ($activeProgram): ?>
            <div class="mt-8 overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-emerald-950 to-slate-950 p-8 text-white relative shadow-xl">
                <div class="absolute -right-16 -top-16 w-48 h-48 rounded-full bg-emerald-500/10 blur-3xl"></div>
                <div class="relative z-10">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/20 px-3 py-1 text-xs font-semibold tracking-wider text-emerald-300 uppercase">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-ping"></span>
                        Active Program
                    </span>
                    <h3 class="mt-4 text-3xl font-extrabold tracking-tight text-white"><?php echo htmlspecialchars($activeProgram['program_name']); ?></h3>
                    <p class="mt-2 text-base text-slate-300/90 leading-relaxed max-w-xl"><?php echo htmlspecialchars($activeProgram['description'] ?? ''); ?></p>
                    
                    <div class="mt-6 flex flex-wrap gap-4 border-t border-white/10 pt-6">
                        <div class="rounded-2xl bg-white/5 backdrop-blur px-4 py-3 text-sm border border-white/10 min-w-[120px]">
                            <p class="text-xs text-slate-400 font-medium">Status</p>
                            <p class="mt-1 font-bold text-emerald-400"><?php echo htmlspecialchars($activeProgram['status']); ?></p>
                        </div>
                        <div class="rounded-2xl bg-white/5 backdrop-blur px-4 py-3 text-sm border border-white/10 min-w-[120px]">
                            <p class="text-xs text-slate-400 font-medium">Batch</p>
                            <p class="mt-1 font-bold text-slate-200"><?php echo htmlspecialchars($activeProgram['batch_name'] ?? 'N/A'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="mt-8 rounded-3xl border border-amber-100 bg-amber-50/50 p-6 text-amber-900 flex items-start gap-4">
                <i class="fa-solid fa-circle-exclamation text-amber-600 mt-1 text-lg"></i>
                <div>
                    <h4 class="font-bold text-amber-950">No Active Program Found</h4>
                    <p class="mt-1 text-sm text-amber-800">No active program is currently configured by the system. Please consult with the admin team.</p>
                </div>
            </div>
        <?php endif; ?>

        <div class="mt-8 grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-5 transition-all hover:bg-slate-50">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Program Link</p>
                <p class="mt-2 text-lg font-bold text-slate-800"><?php echo htmlspecialchars($student['program_id'] ? 'Linked Successfully' : 'Not Assigned'); ?></p>
            </div>
            <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-5 transition-all hover:bg-slate-50">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Level Name</p>
                <p class="mt-2 text-lg font-bold text-slate-800"><?php echo htmlspecialchars($student['level_name'] ?? 'Student Ambassador'); ?></p>
            </div>
            <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-5 transition-all hover:bg-slate-50">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Earned Points</p>
                <p class="mt-2 text-lg font-bold text-emerald-600"><?php echo (int)($student['total_points'] ?? 0); ?> pts</p>
            </div>
        </div>

        <form action="process/student_program_action.php" method="POST" class="mt-8 flex flex-wrap gap-4 border-t border-slate-100 pt-8">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['student_program_csrf']); ?>">
            <input type="hidden" name="action" value="join_active">
            <button type="submit" class="rounded-2xl bg-emerald-600 hover:bg-emerald-700 px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-emerald-600/20 hover:shadow-xl hover:shadow-emerald-600/30 transition-all duration-300">
                Join Active Program
            </button>
            <a href="student-dashboard.php" class="rounded-2xl border border-slate-200 bg-white hover:bg-slate-50 px-6 py-3.5 text-sm font-bold text-slate-700 transition-all">
                Back to Dashboard
            </a>
        </form>
    </section>

    <aside class="space-y-6">
        <section class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-extrabold text-slate-900 tracking-tight">Your Journey Path</h3>
            <div class="mt-6 flow-root">
                <ul role="list" class="-mb-8">
                    <?php 
                    $steps = [
                        ['num' => '1', 'title' => 'Join Program', 'desc' => 'Connect to the current batch program.'],
                        ['num' => '2', 'title' => 'Profile Setup', 'desc' => 'Ensure all your details are valid.'],
                        ['num' => '3', 'title' => 'Perform Tasks', 'desc' => 'Submit proofs of community work.'],
                        ['num' => '4', 'title' => 'Get Rewards', 'desc' => 'Earn promotion levels and certificates.']
                    ];
                    foreach ($steps as $index => $step):
                    ?>
                        <li>
                            <div class="relative pb-8">
                                <?php if ($index !== count($steps) - 1): ?>
                                    <span class="absolute left-5 top-5 -ml-px h-full w-0.5 bg-slate-100" aria-hidden="true"></span>
                                <?php endif; ?>
                                <div class="relative flex items-start space-x-3">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-sm font-bold text-emerald-600 border border-emerald-100">
                                        <?php echo $step['num']; ?>
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <h4 class="text-sm font-bold text-slate-800 mt-1"><?php echo $step['title']; ?></h4>
                                        <p class="text-xs text-slate-500 mt-0.5 leading-relaxed"><?php echo $step['desc']; ?></p>
                                    </div>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </section>
    </aside>
</div>

<?php student_portal_render_shell_end(); ?>
