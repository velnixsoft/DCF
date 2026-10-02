<?php
require 'includes/header.php';
require '../config/db.php';
require '../includes/functions.php';

if (!canAccessModule($pdo, 'manager', 'page.manager_panel')) {
    setFlash('error', 'Access denied. Manager/Admin required.');
    header('Location: dashboard.php');
    exit;
}

$stats = [
    'members_active' => 0,
    'members_pending' => 0,
    'members_blocked' => 0,
    'membership_collected' => 0.0,
    'donations_collected' => 0.0,
    'inquiries_new' => 0,
    'inquiries_open' => 0,
];

try {
    $rows = $pdo->query("SELECT status, COUNT(*) AS c FROM members GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $k = strtolower((string)$r['status']);
        if ($k === 'active') $stats['members_active'] = (int)$r['c'];
        if ($k === 'pending') $stats['members_pending'] = (int)$r['c'];
        if ($k === 'blocked') $stats['members_blocked'] = (int)$r['c'];
    }
} catch (Throwable $e) {
}

try {
    $stats['membership_collected'] = (float)$pdo->query("SELECT COALESCE(SUM(membership_fee),0) FROM members WHERE payment_status = 'Success'")->fetchColumn();
} catch (Throwable $e) {
}

try {
    $stats['donations_collected'] = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM donations WHERE payment_status = 'Success'")->fetchColumn();
} catch (Throwable $e) {
}

try {
    $stats['inquiries_new'] = (int)$pdo->query("SELECT COUNT(*) FROM inquiries WHERE status = 'New'")->fetchColumn();
    $stats['inquiries_open'] = (int)$pdo->query("SELECT COUNT(*) FROM inquiries WHERE status IN ('New','In Progress')")->fetchColumn();
} catch (Throwable $e) {
}

$reports = [];
$reportError = null;
try {
    if (dbViewExists($pdo, 'coordinator_reports')) {
        $reports = $pdo->query("SELECT * FROM coordinator_reports ORDER BY donations_tracked DESC, members_added DESC")->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $reportError = 'Coordinator report view is missing. Run DB migration (upgrade_v2.sql).';
    }
} catch (Throwable $e) {
    $reportError = 'Unable to load coordinator reports. Run DB migration (upgrade_v2.sql).';
}

$verifierRows = [];
try {
    if (dbColumnExists($pdo, 'members', 'verified_by')) {
        $verifierRows = $pdo->query("
            SELECT u.name, u.hierarchy_level, COUNT(*) AS verified_count
            FROM members m
            JOIN users u ON u.id = m.verified_by
            GROUP BY m.verified_by
            ORDER BY verified_count DESC
            LIMIT 10
        ")->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    $verifierRows = [];
}
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900">
    <?php require 'includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
                <div>
                    <h3 class="text-2xl md:text-3xl font-medium text-gray-700 dark:text-white">Manager Panel</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">High-level overview for Managers and Admins.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="coordinator_reports.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium">Coordinator Reports</a>
                    <a href="memberships.php" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-medium">Memberships</a>
                    <a href="inquiries.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium">Inquiries</a>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div class="bg-white dark:bg-gray-800 rounded-xl border dark:border-gray-700 p-5">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold">Members Active</p>
                    <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400 mt-2"><?php echo (int)$stats['members_active']; ?></p>
                    <p class="text-xs text-gray-500 mt-2">Pending: <?php echo (int)$stats['members_pending']; ?> · Blocked: <?php echo (int)$stats['members_blocked']; ?></p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border dark:border-gray-700 p-5">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold">Membership Collected</p>
                    <p class="text-3xl font-bold text-blue-600 dark:text-blue-400 mt-2">&#8377;<?php echo number_format((float)$stats['membership_collected'], 2); ?></p>
                    <p class="text-xs text-gray-500 mt-2">Sum of successful memberships</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border dark:border-gray-700 p-5">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold">Donations Collected</p>
                    <p class="text-3xl font-bold text-indigo-600 dark:text-indigo-400 mt-2">&#8377;<?php echo number_format((float)$stats['donations_collected'], 2); ?></p>
                    <p class="text-xs text-gray-500 mt-2">Sum of successful donations</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border dark:border-gray-700 p-5">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold">Inquiries</p>
                    <p class="text-3xl font-bold text-amber-600 dark:text-amber-400 mt-2"><?php echo (int)$stats['inquiries_open']; ?></p>
                    <p class="text-xs text-gray-500 mt-2">New: <?php echo (int)$stats['inquiries_new']; ?> · Open: <?php echo (int)$stats['inquiries_open']; ?></p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border dark:border-gray-700 overflow-hidden">
                    <div class="p-5 border-b dark:border-gray-700 flex items-center justify-between">
                        <h4 class="font-semibold text-gray-800 dark:text-white">Coordinator Performance</h4>
                        <span class="text-xs text-gray-500"><?php echo $reports ? ('Top ' . min(10, count($reports))) : ''; ?></span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="p-4 font-semibold text-left">Coordinator</th>
                                    <th class="p-4 font-semibold text-right">Members</th>
                                    <th class="p-4 font-semibold text-right">Volunteers</th>
                                    <th class="p-4 font-semibold text-right">Donations</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y dark:divide-gray-700">
                                <?php foreach (array_slice($reports, 0, 10) as $r): ?>
                                    <tr>
                                        <td class="p-4 font-medium dark:text-white">
                                            <?php echo htmlspecialchars($r['coordinator_name'] ?: 'Unassigned'); ?>
                                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars($r['coordinator_code'] ?: '-'); ?></div>
                                        </td>
                                        <td class="p-4 text-right font-semibold text-emerald-600"><?php echo (int)$r['members_added']; ?></td>
                                        <td class="p-4 text-right font-semibold text-blue-600"><?php echo (int)$r['volunteers_approved']; ?></td>
                                        <td class="p-4 text-right font-semibold text-indigo-600">&#8377;<?php echo number_format((float)($r['donations_tracked'] ?: 0)); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($reports)): ?>
                                    <tr>
                                        <td colspan="4" class="p-8 text-center text-gray-500"><?php echo htmlspecialchars($reportError ?: 'No reports available.'); ?></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border dark:border-gray-700 overflow-hidden">
                    <div class="p-5 border-b dark:border-gray-700">
                        <h4 class="font-semibold text-gray-800 dark:text-white">Member Verification (Top)</h4>
                        <p class="text-xs text-gray-500 mt-1">Counts are based on `members.verified_by` (requires upgrade_v2.sql).</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="p-4 font-semibold text-left">User</th>
                                    <th class="p-4 font-semibold text-left">Role</th>
                                    <th class="p-4 font-semibold text-right">Verified</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y dark:divide-gray-700">
                                <?php foreach ($verifierRows as $vr): ?>
                                    <tr>
                                        <td class="p-4 font-medium dark:text-white"><?php echo htmlspecialchars($vr['name']); ?></td>
                                        <td class="p-4 text-xs text-gray-500"><?php echo htmlspecialchars($vr['hierarchy_level'] ?? '-'); ?></td>
                                        <td class="p-4 text-right font-semibold"><?php echo (int)$vr['verified_count']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($verifierRows)): ?>
                                    <tr>
                                        <td colspan="3" class="p-8 text-center text-gray-500">No verification data available yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require 'includes/footer.php'; ?>
