<?php require 'includes/header.php'; ?>
<?php require '../config/db.php'; ?>

<?php
if (!checkRole($pdo, 'coordinator')) {
    setFlash('error', 'Unauthorized access.');
    header('Location: dashboard.php');
    exit;
}

$stats = [
    'active_students' => 0,
    'pending_students' => 0,
    'points_this_month' => 0,
    'referrals_verified_month' => 0,
    'submissions_pending' => 0,
];

try {
    $stats['active_students'] = (int)$pdo->query("SELECT COUNT(*) FROM sa_students WHERE status = 'Active'")->fetchColumn();
    $stats['pending_students'] = (int)$pdo->query("SELECT COUNT(*) FROM sa_students WHERE status = 'Pending'")->fetchColumn();
    $stats['submissions_pending'] = (int)$pdo->query("SELECT COUNT(*) FROM sa_task_submissions WHERE status = 'Pending'")->fetchColumn();
} catch (Throwable $e) {}

try {
    $stats['points_this_month'] = (int)$pdo->query("
        SELECT COALESCE(SUM(points_delta), 0)
        FROM sa_point_transactions
        WHERE deleted_at IS NULL
          AND transaction_status = 'posted'
          AND posted_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
    ")->fetchColumn();
} catch (Throwable $e) {}

try {
    $stats['referrals_verified_month'] = (int)$pdo->query("
        SELECT COUNT(*) FROM sa_referrals
        WHERE verification_status = 'verified'
          AND verified_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
    ")->fetchColumn();
} catch (Throwable $e) {}

$topStudents = [];
try {
    $topStudents = $pdo->query("
        SELECT student_no, full_name, college_name, city_name, level_name, total_points
        FROM sa_students
        WHERE status = 'Active'
        ORDER BY total_points DESC
        LIMIT 25
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

$recentPoints = [];
try {
    $recentPoints = $pdo->query("
        SELECT pt.posted_at, pt.points_delta, pt.description, pt.source_type, s.full_name, s.student_no
        FROM sa_point_transactions pt
        JOIN sa_students s ON s.id = pt.student_id
        WHERE pt.deleted_at IS NULL AND pt.transaction_status = 'posted'
        ORDER BY pt.posted_at DESC
        LIMIT 20
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900">
    <?php require 'includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6">
            <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h3 class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-white">Ambassador Reports</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Student program health, points flow, and leaderboard snapshot.</p>
                </div>
                <a href="student_directory.php" class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-emerald-700">Open Student Directory</a>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-xl border dark:border-gray-700 p-4">
                    <p class="text-xs uppercase text-gray-500">Active Students</p>
                    <p class="text-2xl font-bold text-emerald-600 mt-1"><?php echo $stats['active_students']; ?></p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border dark:border-gray-700 p-4">
                    <p class="text-xs uppercase text-gray-500">Pending Approvals</p>
                    <p class="text-2xl font-bold text-orange-600 mt-1"><?php echo $stats['pending_students']; ?></p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border dark:border-gray-700 p-4">
                    <p class="text-xs uppercase text-gray-500">Points This Month</p>
                    <p class="text-2xl font-bold text-blue-600 mt-1"><?php echo number_format($stats['points_this_month']); ?></p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border dark:border-gray-700 p-4">
                    <p class="text-xs uppercase text-gray-500">Verified Referrals</p>
                    <p class="text-2xl font-bold text-purple-600 mt-1"><?php echo $stats['referrals_verified_month']; ?></p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border dark:border-gray-700 p-4">
                    <p class="text-xs uppercase text-gray-500">Pending Submissions</p>
                    <p class="text-2xl font-bold text-rose-600 mt-1"><?php echo $stats['submissions_pending']; ?></p>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                <section class="bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 overflow-hidden">
                    <div class="p-4 border-b dark:border-gray-700 font-bold text-gray-800 dark:text-white">Top 25 Ambassadors</div>
                    <div class="divide-y dark:divide-gray-700 lg:hidden">
                        <?php foreach ($topStudents as $row): ?>
                            <div class="p-4 space-y-2">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($row['full_name']); ?></p>
                                        <p class="text-[10px] font-mono text-gray-400"><?php echo htmlspecialchars($row['student_no']); ?></p>
                                    </div>
                                    <p class="font-bold <?php echo (int)$row['total_points'] >= 0 ? 'text-emerald-600' : 'text-rose-600'; ?>"><?php echo (int)$row['total_points']; ?> pts</p>
                                </div>
                                <p class="text-xs text-gray-600"><?php echo htmlspecialchars($row['college_name'] ?? '-'); ?></p>
                                <p class="text-xs text-gray-500"><?php echo htmlspecialchars($row['level_name'] ?? 'Student Ambassador'); ?></p>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($topStudents)): ?>
                            <div class="p-8 text-center text-gray-500">No student data available.</div>
                        <?php endif; ?>
                    </div>
                    <div class="hidden lg:block overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase text-gray-500">
                                <tr>
                                    <th class="p-3">Student</th>
                                    <th class="p-3">College</th>
                                    <th class="p-3">Level</th>
                                    <th class="p-3 text-right">Points</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y dark:divide-gray-700">
                                <?php foreach ($topStudents as $row): ?>
                                    <tr>
                                        <td class="p-3">
                                            <p class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($row['full_name']); ?></p>
                                            <p class="text-[10px] font-mono text-gray-400"><?php echo htmlspecialchars($row['student_no']); ?></p>
                                        </td>
                                        <td class="p-3 text-gray-600"><?php echo htmlspecialchars($row['college_name'] ?? '—'); ?></td>
                                        <td class="p-3 text-gray-600"><?php echo htmlspecialchars($row['level_name'] ?? 'Student Ambassador'); ?></td>
                                        <td class="p-3 text-right font-bold <?php echo (int)$row['total_points'] >= 0 ? 'text-emerald-600' : 'text-rose-600'; ?>"><?php echo (int)$row['total_points']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($topStudents)): ?>
                                    <tr><td colspan="4" class="p-8 text-center text-gray-500">No student data available.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 overflow-hidden">
                    <div class="p-4 border-b dark:border-gray-700 font-bold text-gray-800 dark:text-white">Recent Point Transactions</div>
                    <div class="divide-y dark:divide-gray-700 lg:hidden">
                        <?php foreach ($recentPoints as $row): ?>
                            <div class="p-4 space-y-2">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($row['full_name']); ?></p>
                                        <p class="text-[10px] text-gray-400"><?php echo htmlspecialchars($row['description'] ?? ''); ?></p>
                                    </div>
                                    <p class="font-bold <?php echo (int)$row['points_delta'] >= 0 ? 'text-emerald-600' : 'text-rose-600'; ?>">
                                        <?php echo ((int)$row['points_delta'] >= 0 ? '+' : '') . (int)$row['points_delta']; ?>
                                    </p>
                                </div>
                                <div class="flex items-center justify-between gap-3 text-xs text-gray-500">
                                    <span><?php echo date('d M Y', strtotime($row['posted_at'])); ?></span>
                                    <span><?php echo htmlspecialchars($row['source_type']); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($recentPoints)): ?>
                            <div class="p-8 text-center text-gray-500">No point transactions yet.</div>
                        <?php endif; ?>
                    </div>
                    <div class="hidden lg:block overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase text-gray-500">
                                <tr>
                                    <th class="p-3">Date</th>
                                    <th class="p-3">Student</th>
                                    <th class="p-3">Source</th>
                                    <th class="p-3 text-right">Points</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y dark:divide-gray-700">
                                <?php foreach ($recentPoints as $row): ?>
                                    <tr>
                                        <td class="p-3 text-xs text-gray-500"><?php echo date('d M Y', strtotime($row['posted_at'])); ?></td>
                                        <td class="p-3">
                                            <p class="font-semibold"><?php echo htmlspecialchars($row['full_name']); ?></p>
                                            <p class="text-[10px] text-gray-400"><?php echo htmlspecialchars($row['description'] ?? ''); ?></p>
                                        </td>
                                        <td class="p-3 text-gray-600"><?php echo htmlspecialchars($row['source_type']); ?></td>
                                        <td class="p-3 text-right font-bold <?php echo (int)$row['points_delta'] >= 0 ? 'text-emerald-600' : 'text-rose-600'; ?>">
                                            <?php echo ((int)$row['points_delta'] >= 0 ? '+' : '') . (int)$row['points_delta']; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($recentPoints)): ?>
                                    <tr><td colspan="4" class="p-8 text-center text-gray-500">No point transactions yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </main>
    </div>
</div>

<?php require 'includes/footer.php'; ?>
