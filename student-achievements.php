<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/student/portal_helpers.php';

$student = student_portal_require_student($pdo, 'achievements');
$badges = [];

try {
    $stmt = $pdo->prepare("
        SELECT b.badge_name, b.description, b.icon_class, sb.awarded_at, sb.assignment_type
        FROM sa_student_badges sb
        JOIN sa_badges b ON b.id = sb.badge_id
        WHERE sb.student_id = ?
        ORDER BY sb.awarded_at DESC
    ");
    $stmt->execute([(int)$student['id']]);
    $badges = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $badges = [];
}

student_portal_render_shell_start($pdo, $student, 'My Achievements', 'achievements');
?>

<div class="grid gap-6 lg:grid-cols-[1fr_320px]">
    <!-- Left Column: Badges Grid -->
    <section class="rounded-3xl border border-slate-100 bg-white p-8 shadow-sm transition-all duration-300 hover:shadow-md">
        <div class="flex items-center gap-4 border-b border-slate-100 pb-6 mb-8">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                <i class="fa-solid fa-medal text-xl"></i>
            </div>
            <div>
                <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Earned Achievements</h2>
                <p class="text-sm text-slate-500">Badges and milestones unlocked during your active membership.</p>
            </div>
        </div>

        <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
            <?php if (empty($badges)): ?>
                <div class="rounded-3xl border border-dashed border-slate-200 bg-slate-50/50 p-12 text-center text-slate-400 sm:col-span-2 xl:col-span-3">
                    <span class="text-3xl block mb-2">🏅</span>
                    No badges unlocked yet. Complete tasks and maintain attendance to start earning rewards!
                </div>
            <?php endif; ?>
            
            <?php foreach ($badges as $badge): 
                $badgeName = (string)$badge['badge_name'];
                $assignmentType = (strtolower($badgeName) === 'top performer') ? 'auto' : $badge['assignment_type'];
                $isAuto = strtolower($assignmentType) === 'auto';
            ?>
                <article class="flex flex-col justify-between rounded-3xl border border-slate-100 bg-white p-6 shadow-sm transition-all hover:shadow-md hover:border-slate-200/80 relative overflow-hidden group">
                    <div class="absolute -right-8 -top-8 w-24 h-24 rounded-full bg-emerald-50/35 blur-2xl group-hover:bg-emerald-50/70 transition-all duration-300"></div>
                    
                    <div class="relative z-10">
                        <div class="flex items-center justify-between">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 border border-emerald-100/50 group-hover:scale-105 transition-transform duration-300">
                                <i class="fa-solid <?php echo htmlspecialchars($badge['icon_class'] ?: 'fa-medal'); ?> text-lg"></i>
                            </div>
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[9px] font-bold uppercase tracking-wider <?php echo $isAuto ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-blue-50 text-blue-700 border border-blue-100'; ?>">
                                <?php echo htmlspecialchars($assignmentType); ?>
                            </span>
                        </div>
                        
                        <h3 class="mt-5 text-base font-extrabold text-slate-900 tracking-tight leading-snug"><?php echo htmlspecialchars($badge['badge_name']); ?></h3>
                        <p class="mt-2 text-xs text-slate-500 leading-relaxed"><?php echo htmlspecialchars($badge['description']); ?></p>
                    </div>
                    
                    <div class="mt-6 pt-4 border-t border-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-400 relative z-10">
                        Awarded <?php echo htmlspecialchars(date('d M Y', strtotime($badge['awarded_at']))); ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Sidebar Info -->
    <aside class="space-y-6">
        <section class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-extrabold text-slate-900 tracking-tight mb-4">Milestone Paths</h3>
            
            <ul class="space-y-4 text-xs font-semibold text-slate-600">
                <li class="flex gap-3 items-start">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">1</span>
                    <div>
                        <p class="text-slate-800 font-bold">Rising Star Badge</p>
                        <p class="text-[10px] text-slate-400 font-normal mt-0.5">Complete onboarding & early setups.</p>
                    </div>
                </li>
                <li class="flex gap-3 items-start">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">2</span>
                    <div>
                        <p class="text-slate-800 font-bold">Community Builder</p>
                        <p class="text-[10px] text-slate-400 font-normal mt-0.5">Invite new interns & verify referrals.</p>
                    </div>
                </li>
                <li class="flex gap-3 items-start">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">3</span>
                    <div>
                        <p class="text-slate-800 font-bold">Campaign Champion</p>
                        <p class="text-[10px] text-slate-400 font-normal mt-0.5">Lead local city drives & meetups.</p>
                    </div>
                </li>
            </ul>
        </section>
    </aside>
</div>

<?php student_portal_render_shell_end(); ?>
