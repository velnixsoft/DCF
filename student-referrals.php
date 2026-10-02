<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/student/portal_helpers.php';

$student = student_portal_require_student($pdo, 'referrals');
$referrals = [];

try {
    $stmt = $pdo->prepare("
        SELECT *
        FROM sa_referrals
        WHERE referrer_student_id = ? AND deleted_at IS NULL
        ORDER BY created_at DESC
        LIMIT 100
    ");
    $stmt->execute([(int)$student['id']]);
    $referrals = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $referrals = [];
}

$shareLink = appBaseUrl() . '/student-register.php?ref=' . urlencode((string)$student['referral_code']);
student_portal_render_shell_start($pdo, $student, 'My Referrals', 'referrals');
?>

<div class="grid gap-8 lg:grid-cols-[1fr_320px] font-sans" x-data="{ page: 1, pageSize: 10, copyText() { navigator.clipboard.writeText('<?php echo htmlspecialchars($shareLink); ?>'); window.studentShowToast('Referral link copied to clipboard!', 'success'); } }">
    <section class="rounded-[2rem] border border-slate-100 bg-white p-6 md:p-8 shadow-sm space-y-6">
        <div>
            <h2 class="text-xl font-black text-slate-900">Referral Sharing Workspace</h2>
            <p class="text-xs text-slate-400 mt-1">Invite friends to join the student program and earn points upon registration approval.</p>
        </div>

        <!-- Custom Brand Code Card -->
        <div class="rounded-3xl bg-gradient-to-br from-slate-900 via-emerald-950 to-slate-950 p-6 text-white shadow-xl relative overflow-hidden">
            <div class="absolute inset-0 bg-[linear-gradient(to_right,rgba(255,255,255,0.015)_1px,transparent_1px),linear-gradient(to_bottom,rgba(255,255,255,0.015)_1px,transparent_1px)] bg-[size:16px_16px] pointer-events-none"></div>
            
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6 relative z-10">
                <div class="space-y-1">
                    <span class="text-[9px] font-extrabold uppercase tracking-widest text-emerald-300">My Referral Code</span>
                    <p class="text-3xl font-black tracking-wider text-slate-100 font-mono"><?php echo htmlspecialchars($student['referral_code']); ?></p>
                </div>
                
                <div class="w-full md:w-auto flex-1 max-w-md">
                    <span class="text-[9px] font-extrabold uppercase tracking-widest text-emerald-300 block mb-2">Share Invitation Link</span>
                    <div class="flex gap-2 bg-white/5 border border-white/10 rounded-2xl p-1.5 backdrop-blur">
                        <input type="text" readonly value="<?php echo htmlspecialchars($shareLink); ?>" class="bg-transparent text-xs text-slate-300 px-3 outline-none flex-1 font-mono truncate">
                        <button type="button" @click="copyText()" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 active:scale-95">
                            <i class="fa-solid fa-copy"></i> Copy
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metric badges -->
        <div class="grid grid-cols-3 gap-4">
            <div class="rounded-2xl border border-emerald-100 bg-emerald-50/50 p-4 relative overflow-hidden group hover:bg-emerald-50 transition duration-150">
                <div class="absolute -right-3 -bottom-3 text-emerald-500/5 text-5xl font-black group-hover:scale-110 transition-transform duration-300"><i class="fa-solid fa-circle-check"></i></div>
                <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-700">Verified</p>
                <p class="text-2xl font-black text-emerald-950 mt-1"><?php echo count(array_filter($referrals, static fn($row) => ($row['verification_status'] ?? '') === 'verified')); ?></p>
            </div>
            
            <div class="rounded-2xl border border-amber-100 bg-amber-50/50 p-4 relative overflow-hidden group hover:bg-amber-50 transition duration-150">
                <div class="absolute -right-3 -bottom-3 text-amber-500/5 text-5xl font-black group-hover:scale-110 transition-transform duration-300"><i class="fa-solid fa-spinner"></i></div>
                <p class="text-[10px] font-bold uppercase tracking-wider text-amber-700">Pending</p>
                <p class="text-2xl font-black text-amber-950 mt-1"><?php echo count(array_filter($referrals, static fn($row) => ($row['verification_status'] ?? '') === 'pending')); ?></p>
            </div>
            
            <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-4 relative overflow-hidden group hover:bg-slate-100 transition duration-150">
                <div class="absolute -right-3 -bottom-3 text-slate-500/5 text-5xl font-black group-hover:scale-110 transition-transform duration-300"><i class="fa-solid fa-users"></i></div>
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Total Invites</p>
                <p class="text-2xl font-black text-slate-950 mt-1"><?php echo count($referrals); ?></p>
            </div>
        </div>

        <!-- History Log Table -->
        <div class="space-y-3">
            <h3 class="text-sm font-bold text-slate-800">Referral Signups</h3>
            <div class="overflow-hidden rounded-2xl border border-slate-100 shadow-inner">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-100">
                        <tr>
                            <th class="px-5 py-3.5">Name</th>
                            <th class="px-5 py-3.5">Contact Details</th>
                            <th class="px-5 py-3.5">Status</th>
                            <th class="px-5 py-3.5 text-right">Date Joined</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($referrals)): ?>
                            <tr>
                                <td colspan="4" class="px-5 py-12 text-center text-slate-400">
                                    <div class="w-12 h-12 rounded-full bg-slate-50 text-slate-400 flex items-center justify-center mx-auto mb-3"><i class="fa-solid fa-users-slash text-lg"></i></div>
                                    <p class="font-medium">No referrals submitted yet.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php $refIdx = 0; foreach ($referrals as $row): ?>
                            <tr x-show="Math.ceil((<?php echo ++$refIdx; ?>) / pageSize) === page" class="hover:bg-slate-50/30 transition-colors">
                                <td class="px-5 py-4 font-bold text-slate-900"><?php echo htmlspecialchars($row['referred_full_name'] ?: '—'); ?></td>
                                <td class="px-5 py-4 font-semibold text-slate-500"><?php echo htmlspecialchars(trim(($row['referred_email'] ?? '') . ' ' . ($row['referred_mobile'] ? '· ' . $row['referred_mobile'] : ''))); ?></td>
                                <td class="px-5 py-4">
                                    <?php 
                                        $vStatus = $row['verification_status'] ?? 'pending';
                                        $vClass = $vStatus === 'verified' 
                                            ? 'bg-emerald-50 border-emerald-100 text-emerald-700' 
                                            : ($vStatus === 'rejected' ? 'bg-rose-50 border-rose-100 text-rose-700' : 'bg-amber-50 border-amber-100 text-amber-700');
                                    ?>
                                    <span class="rounded-full px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wider border <?php echo $vClass; ?>">
                                        <?php echo htmlspecialchars($vStatus); ?>
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right font-medium text-slate-400 font-mono"><?php echo htmlspecialchars(date('d M Y', strtotime($row['created_at']))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Pagination UI Controls -->
        <div x-show="<?php echo count($referrals); ?> > pageSize" class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-6 px-4 py-3 bg-white border rounded-2xl shadow-sm">
            <div class="text-xs text-slate-500 font-medium">
                Showing <span class="font-bold text-slate-900" x-text="Math.min((page - 1) * pageSize + 1, <?php echo count($referrals); ?>)"></span> to 
                <span class="font-bold text-slate-900" x-text="Math.min(page * pageSize, <?php echo count($referrals); ?>)"></span> of 
                <span class="font-bold text-slate-900"><?php echo count($referrals); ?></span> entries
            </div>
            <div class="flex items-center gap-2">
                <button type="button" @click="if (page > 1) page--" :disabled="page === 1" 
                    class="px-4 py-2 text-xs font-bold rounded-xl border bg-gray-50 text-gray-700 hover:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none transition flex items-center gap-1">
                    <i class="fa-solid fa-chevron-left"></i> Previous
                </button>
                <div class="flex items-center gap-1">
                    <span class="text-xs font-semibold text-slate-500">Page</span>
                    <span class="text-xs font-bold text-slate-900" x-text="page"></span>
                    <span class="text-xs font-semibold text-slate-500">of</span>
                    <span class="text-xs font-bold text-slate-900" x-text="Math.ceil(<?php echo count($referrals); ?> / pageSize)"></span>
                </div>
                <button type="button" @click="if (page < Math.ceil(<?php echo count($referrals); ?> / pageSize)) page++" :disabled="page === Math.ceil(<?php echo count($referrals); ?> / pageSize)" 
                    class="px-4 py-2 text-xs font-bold rounded-xl border bg-gray-50 text-gray-700 hover:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none transition flex items-center gap-1">
                    Next <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </section>

    <!-- Side card for rules -->
    <aside class="space-y-6">
        <section class="rounded-[2rem] border border-slate-100 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-black text-slate-900 uppercase tracking-wider border-b pb-3 border-slate-100">Referral Program Rules</h3>
            <ul class="mt-4 space-y-3.5 text-xs text-slate-600 font-semibold">
                <li class="flex items-start gap-2.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mt-1.5 flex-shrink-0"></span>
                    <span>Admin verification is mandatory before referral points are posted to your balance.</span>
                </li>
                <li class="flex items-start gap-2.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mt-1.5 flex-shrink-0"></span>
                    <span>Verified referrals count towards level progression milestones.</span>
                </li>
                <li class="flex items-start gap-2.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500 mt-1.5 flex-shrink-0"></span>
                    <span>Duplicate submissions or fake referrals will trigger points deduction penalties.</span>
                </li>
            </ul>
        </section>
    </aside>
</div>

<?php student_portal_render_shell_end(); ?>
