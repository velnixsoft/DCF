<?php
require 'includes/header.php';

$currentYear = (int)date('Y');
$currentMonth = (int)date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : $currentYear;
$month = isset($_GET['month']) ? (int)$_GET['month'] : $currentMonth;
if ($year < 2000 || $year > $currentYear) {
    $year = $currentYear;
    $month = $currentMonth;
}
if ($month < 1 || $month > 12) {
    $month = $currentMonth;
}
if ($year === $currentYear && $month > $currentMonth) {
    $month = $currentMonth;
}

$start = sprintf('%04d-%02d-01 00:00:00', $year, $month);
$end = date('Y-m-d H:i:s', strtotime($start . ' +1 month'));

$summary = [
    'success_amount' => 0,
    'success_count' => 0,
    'pending_count' => 0,
    'failed_count' => 0,
];
$daily = [];
$byProject = [];

try {
    $stmt = $pdo->prepare("
        SELECT
            SUM(CASE WHEN payment_status = 'Success' THEN amount ELSE 0 END) AS success_amount,
            SUM(CASE WHEN payment_status = 'Success' THEN 1 ELSE 0 END) AS success_count,
            SUM(CASE WHEN payment_status = 'Pending' THEN 1 ELSE 0 END) AS pending_count,
            SUM(CASE WHEN payment_status = 'Failed' THEN 1 ELSE 0 END) AS failed_count
        FROM donations
        WHERE created_at >= ? AND created_at < ?
    ");
    $stmt->execute([$start, $end]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $summary['success_amount'] = (float)($row['success_amount'] ?? 0);
    $summary['success_count'] = (int)($row['success_count'] ?? 0);
    $summary['pending_count'] = (int)($row['pending_count'] ?? 0);
    $summary['failed_count'] = (int)($row['failed_count'] ?? 0);

    $stmt = $pdo->prepare("
        SELECT DATE(created_at) AS day, SUM(CASE WHEN payment_status = 'Success' THEN amount ELSE 0 END) AS amount, COUNT(*) AS count_all
        FROM donations
        WHERE created_at >= ? AND created_at < ?
        GROUP BY DATE(created_at)
        ORDER BY day ASC
    ");
    $stmt->execute([$start, $end]);
    $daily = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT COALESCE(p.title, 'General') AS project_name,
               SUM(CASE WHEN d.payment_status = 'Success' THEN d.amount ELSE 0 END) AS amount,
               SUM(CASE WHEN d.payment_status = 'Success' THEN 1 ELSE 0 END) AS cnt
        FROM donations d
        LEFT JOIN projects p ON p.id = d.project_id
        WHERE d.created_at >= ? AND d.created_at < ?
        GROUP BY d.project_id
        ORDER BY amount DESC
        LIMIT 50
    ");
    $stmt->execute([$start, $end]);
    $byProject = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    setFlash('error', 'Unable to load analytics.');
}
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900">
    <?php require 'includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                <div>
                    <h3 class="text-2xl md:text-3xl font-medium text-gray-800 dark:text-white">Donation Analytics</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Monthly overview of donation totals and trends.</p>
                </div>
                <form method="GET" class="flex gap-2 items-end">
                    <div>
                        <label class="text-xs font-bold text-gray-600 dark:text-gray-300">Month</label>
                        <select name="month" class="w-full mt-1 p-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?php echo $m; ?>" <?php echo $m === $month ? 'selected' : ''; ?>><?php echo date('F', mktime(0, 0, 0, $m, 1)); ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-600 dark:text-gray-300">Year</label>
                        <input type="number" name="year" value="<?php echo (int)$year; ?>" class="w-28 mt-1 p-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                    <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-bold">Apply</button>
                </form>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow border dark:border-gray-700 p-4">
                    <p class="text-xs text-gray-500">Success Amount</p>
                    <p class="text-2xl font-bold text-emerald-600">₹<?php echo number_format($summary['success_amount'], 2); ?></p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow border dark:border-gray-700 p-4">
                    <p class="text-xs text-gray-500">Success Count</p>
                    <p class="text-2xl font-bold text-gray-800 dark:text-white"><?php echo (int)$summary['success_count']; ?></p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow border dark:border-gray-700 p-4">
                    <p class="text-xs text-gray-500">Pending</p>
                    <p class="text-2xl font-bold text-amber-600"><?php echo (int)$summary['pending_count']; ?></p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow border dark:border-gray-700 p-4">
                    <p class="text-xs text-gray-500">Failed</p>
                    <p class="text-2xl font-bold text-red-600"><?php echo (int)$summary['failed_count']; ?></p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow border dark:border-gray-700 overflow-hidden">
                    <div class="p-4 border-b dark:border-gray-700">
                        <h4 class="font-semibold dark:text-white">Daily Totals</h4>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-700/50 text-left">
                                <tr>
                                    <th class="p-3">Day</th>
                                    <th class="p-3">Success Amount</th>
                                    <th class="p-3">Transactions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y dark:divide-gray-700">
                                <?php foreach ($daily as $d): ?>
                                    <tr class="dark:text-gray-200">
                                        <td class="p-3"><?php echo htmlspecialchars(date('d M Y', strtotime((string)$d['day']))); ?></td>
                                        <td class="p-3 font-semibold text-emerald-600">₹<?php echo number_format((float)$d['amount'], 2); ?></td>
                                        <td class="p-3"><?php echo (int)$d['count_all']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($daily)): ?>
                                    <tr><td class="p-8 text-center text-gray-500" colspan="3">No data for this month.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow border dark:border-gray-700 overflow-hidden">
                    <div class="p-4 border-b dark:border-gray-700">
                        <h4 class="font-semibold dark:text-white">Project-wise (Success)</h4>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-700/50 text-left">
                                <tr>
                                    <th class="p-3">Project</th>
                                    <th class="p-3">Amount</th>
                                    <th class="p-3">Count</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y dark:divide-gray-700">
                                <?php foreach ($byProject as $p): ?>
                                    <tr class="dark:text-gray-200">
                                        <td class="p-3"><?php echo htmlspecialchars((string)$p['project_name']); ?></td>
                                        <td class="p-3 font-semibold text-emerald-600">₹<?php echo number_format((float)$p['amount'], 2); ?></td>
                                        <td class="p-3"><?php echo (int)$p['cnt']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($byProject)): ?>
                                    <tr><td class="p-8 text-center text-gray-500" colspan="3">No data for this month.</td></tr>
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

