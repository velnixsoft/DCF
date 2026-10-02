<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/student/portal_helpers.php';

$student = student_portal_require_student($pdo, 'points');
$transactions = [];

try {
    $stmt = $pdo->prepare("
        SELECT pt.*, pr.rule_name, pr.category, pr.trigger_key
        FROM sa_point_transactions pt
        LEFT JOIN sa_point_rules pr ON pr.id = pt.rule_id
        WHERE pt.student_id = ? AND pt.deleted_at IS NULL
        ORDER BY pt.posted_at DESC, pt.id DESC
        LIMIT 100
    ");
    $stmt->execute([(int)$student['id']]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $transactions = [];
}

student_portal_render_shell_start($pdo, $student, 'My Points History', 'points');
?>

<div class="grid gap-6 lg:grid-cols-[1fr_320px]" x-data="{ page: 1, pageSize: 10 }">
    <!-- Left Section: Transaction Ledger -->
    <section class="rounded-3xl border border-slate-100 bg-white p-8 shadow-sm transition-all duration-300 hover:shadow-md">
        <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between border-b border-slate-100 pb-6">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                    <i class="fa-solid fa-coins text-xl"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Points Ledger</h2>
                    <p class="text-sm text-slate-500">Chronological list of all your point actions.</p>
                </div>
            </div>
            
            <div class="rounded-2xl bg-emerald-50/50 border border-emerald-100/50 px-6 py-4 text-center sm:text-right min-w-[140px]">
                <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-700">Available Balance</p>
                <p class="text-3xl font-black text-emerald-950 mt-1"><?php echo (int)($student['total_points'] ?? 0); ?></p>
            </div>
        </div>

        <div class="mt-8 overflow-hidden rounded-2xl border border-slate-100">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-slate-50/75 border-b border-slate-100 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            <th class="px-5 py-3.5">Date</th>
                            <th class="px-5 py-3.5">Rule / Action</th>
                            <th class="px-5 py-3.5">Notes</th>
                            <th class="px-5 py-3.5 text-right">Points</th>
                            <th class="px-5 py-3.5 text-right">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="5" class="px-5 py-12 text-center text-slate-400">
                                    <span class="text-2xl block mb-2">🪙</span>
                                    No point transactions recorded yet.
                                </td>
                            </tr>
                        <?php endif; ?>
                        
                        <?php $txIdx = 0; foreach ($transactions as $row): 
                            $delta = (int)$row['points_delta'];
                            $isCredit = $delta >= 0;
                        ?>
                            <tr x-show="Math.ceil((<?php echo ++$txIdx; ?>) / pageSize) === page" class="transition-all hover:bg-slate-50/50">
                                <td class="px-5 py-4 text-slate-400 text-xs whitespace-nowrap">
                                    <?php echo htmlspecialchars(date('d M Y', strtotime($row['posted_at']))); ?>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-bold text-slate-900 leading-snug">
                                        <?php echo htmlspecialchars($row['rule_name'] ?: ($row['source_type'] ?? 'system')); ?>
                                    </p>
                                    <span class="inline-flex items-center rounded-full mt-1.5 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider <?php echo $isCredit ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-rose-50 text-rose-750 border border-rose-100'; ?>">
                                        <?php echo $isCredit ? 'Credit' : 'Debit'; ?>
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-slate-500 text-xs max-w-xs leading-normal">
                                    <?php echo htmlspecialchars($row['description'] ?? ''); ?>
                                </td>
                                <td class="px-5 py-4 text-right font-black text-sm whitespace-nowrap <?php echo $isCredit ? 'text-emerald-600' : 'text-rose-600'; ?>">
                                    <?php echo ($isCredit ? '+' : '') . $delta; ?>
                                </td>
                                <td class="px-5 py-4 text-right font-bold text-slate-800 whitespace-nowrap">
                                    <?php echo isset($row['balance_after']) ? (int)$row['balance_after'] : '—'; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Pagination UI Controls -->
        <div x-show="<?php echo count($transactions); ?> > pageSize" class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-6 px-4 py-3 bg-white border rounded-2xl shadow-sm">
            <div class="text-xs text-slate-500 font-medium">
                Showing <span class="font-bold text-slate-900" x-text="Math.min((page - 1) * pageSize + 1, <?php echo count($transactions); ?>)"></span> to 
                <span class="font-bold text-slate-900" x-text="Math.min(page * pageSize, <?php echo count($transactions); ?>)"></span> of 
                <span class="font-bold text-slate-900"><?php echo count($transactions); ?></span> entries
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
                    <span class="text-xs font-bold text-slate-900" x-text="Math.ceil(<?php echo count($transactions); ?> / pageSize)"></span>
                </div>
                <button type="button" @click="if (page < Math.ceil(<?php echo count($transactions); ?> / pageSize)) page++" :disabled="page === Math.ceil(<?php echo count($transactions); ?> / pageSize)" 
                    class="px-4 py-2 text-xs font-bold rounded-xl border bg-gray-50 text-gray-700 hover:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none transition flex items-center gap-1">
                    Next <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </section>

    <!-- Sidebar: Info cards -->
    <aside class="space-y-6">
        <section class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-extrabold text-slate-900 tracking-tight">Point Sources</h3>
            <p class="text-xs text-slate-500 mt-1 mb-4">Earn points through several core actions:</p>
            
            <ul class="space-y-3">
                <li class="rounded-2xl bg-slate-50/50 border border-slate-100/50 px-4 py-3.5 flex items-center gap-3">
                    <span class="text-lg">🎯</span>
                    <div class="min-w-0">
                        <p class="text-xs font-bold text-slate-800">Tasks & Campaigns</p>
                        <p class="text-[10px] text-slate-400 mt-0.5">Approved campaign proofs</p>
                    </div>
                </li>
                <li class="rounded-2xl bg-slate-50/50 border border-slate-100/50 px-4 py-3.5 flex items-center gap-3">
                    <span class="text-lg">📅</span>
                    <div class="min-w-0">
                        <p class="text-xs font-bold text-slate-800">Attendance Bonuses</p>
                        <p class="text-[10px] text-slate-400 mt-0.5">Maintain 90%+ monthly score</p>
                    </div>
                </li>
                <li class="rounded-2xl bg-slate-50/50 border border-slate-100/50 px-4 py-3.5 flex items-center gap-3">
                    <span class="text-lg">🤝</span>
                    <div class="min-w-0">
                        <p class="text-xs font-bold text-slate-800">Referrals & Leads</p>
                        <p class="text-[10px] text-slate-400 mt-0.5">Verified signups & vendor leads</p>
                    </div>
                </li>
            </ul>
        </section>
    </aside>
</div>

<?php student_portal_render_shell_end(); ?>
