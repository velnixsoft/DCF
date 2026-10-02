<?php
require 'includes/header.php';
require '../config/db.php';
require '../includes/functions.php';

// Role check
if (!checkRole($pdo, 'coordinator')) {
    setFlash('error', 'Access denied. Coordinator/Manager/Admin required.');
    header('Location: dashboard.php');
    exit;
}

$reports = [];
$total_coordinators = 0;
$reportError = null;

try {
    if (dbViewExists($pdo, 'coordinator_reports')) {
        $reports = $pdo->query("SELECT * FROM coordinator_reports ORDER BY donations_tracked DESC, members_added DESC")->fetchAll();
    } else {
        $reportError = 'Coordinator report view is missing. Run DB migration (upgrade_v2.sql).';
    }
} catch (Throwable $e) {
    $reportError = 'Unable to load coordinator reports. Run DB migration (upgrade_v2.sql).';
}

try {
    if (dbColumnExists($pdo, 'users', 'hierarchy_level')) {
        $total_coordinators = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE hierarchy_level IN ('coordinator','manager')")->fetchColumn();
    }
} catch (Throwable $e) {
    $total_coordinators = 0;
}
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900">
    <?php require 'includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
                <div>
                    <h3 class="text-2xl md:text-3xl font-medium text-gray-700 dark:text-white">Coordinator Reports</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Tracking: Members added, volunteers approved, donations verified by coordinators/managers.</p>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-gray-600 dark:text-gray-300">
                        <thead class="bg-gray-50 dark:bg-gray-700/50 text-left">
                            <tr>
                                <th class="p-4 font-semibold">Coordinator/Manager</th>
                                <th class="p-4 font-semibold">Code</th>
                                <th class="p-4 font-semibold text-right">Members Added</th>
                                <th class="p-4 font-semibold text-right">Volunteers Approved</th>
                                <th class="p-4 font-semibold text-right">Donations Tracked (&#8377;)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y dark:divide-gray-700">
                            <?php foreach ($reports as $r): ?>
                            <tr>
                                <td class="p-4 font-medium dark:text-white"><?php echo htmlspecialchars($r['coordinator_name'] ?: 'Unassigned'); ?></td>
                                <td class="p-4"><?php echo htmlspecialchars($r['coordinator_code'] ?: '-'); ?></td>
                                <td class="p-4 text-right font-semibold text-green-600"> <?php echo (int)$r['members_added']; ?></td>
                                <td class="p-4 text-right font-semibold text-blue-600"><?php echo (int)$r['volunteers_approved']; ?></td>
                                <td class="p-4 text-right font-semibold text-indigo-600">&#8377;<?php echo number_format($r['donations_tracked'] ?: 0); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($reports)): ?>
                            <tr><td colspan="5" class="p-8 text-center text-gray-500"><?php echo htmlspecialchars($reportError ?: 'No reports yet. Run DB migration first.'); ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div class="bg-blue-50 dark:bg-blue-900/30 p-4 rounded-lg">
                    <p class="text-blue-800 dark:text-blue-200 font-semibold">Total Coordinators</p>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400"><?php echo $total_coordinators; ?></p>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require 'includes/footer.php'; ?>

