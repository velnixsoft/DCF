<?php
require '../config/db.php';
require '../includes/functions.php';

try {
    $pdo->exec("ALTER TABLE field_agents
        ADD COLUMN IF NOT EXISTS base_salary_monthly DECIMAL(10,2) DEFAULT 0.00,
        ADD COLUMN IF NOT EXISTS incentive_rate_percent DECIMAL(5,2) DEFAULT 2.00,
        ADD COLUMN IF NOT EXISTS min_attendance_days INT DEFAULT 20");
    $pdo->exec("CREATE TABLE IF NOT EXISTS agent_salary_ledger (
        id INT AUTO_INCREMENT PRIMARY KEY,
        agent_id INT NOT NULL,
        month_key CHAR(7) NOT NULL,
        target_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        achieved_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        base_salary DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        incentive_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        total_payable DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        status ENUM('pending', 'credited') NOT NULL DEFAULT 'pending',
        credited_at DATETIME DEFAULT NULL,
        credited_by_user_id INT DEFAULT NULL,
        remarks VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_agent_month (agent_id, month_key),
        INDEX idx_salary_month (month_key),
        FOREIGN KEY (agent_id) REFERENCES field_agents(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Throwable $e) {
}

if (!canAccessModule($pdo, 'area_manager', 'page.agent_payroll')) {
    setFlash('error', 'Access denied.');
    header('Location: dashboard.php');
    exit;
}

$monthKey = date('Y-m');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['credit_salary'])) {
    $agentId = (int)($_POST['agent_id'] ?? 0);
    if ($agentId > 0) {
        $dataStmt = $pdo->prepare("
            SELECT fa.id, fa.target_amount_monthly, fa.base_salary_monthly, fa.incentive_rate_percent, fa.min_attendance_days,
                   COALESCE(collected.total, 0) AS monthly_collected,
                   COALESCE(attendance.days_count, 0) AS attendance_days_month,
                   COALESCE(cash_pending.pending_count, 0) AS pending_deposits,
                   COALESCE(cash_overdue.overdue_count, 0) AS overdue_deposits
            FROM field_agents fa
            LEFT JOIN (
                SELECT d.field_agent_id AS agent_id, SUM(d.amount) AS total
                FROM donations d
                WHERE DATE_FORMAT(d.created_at, '%Y-%m') = ?
                GROUP BY d.field_agent_id
            ) collected ON collected.agent_id = fa.id
            LEFT JOIN (
                SELECT aa.agent_id, COUNT(DISTINCT DATE(aa.punch_time)) AS days_count
                FROM agent_attendance aa
                WHERE DATE_FORMAT(aa.punch_time, '%Y-%m') = ?
                GROUP BY aa.agent_id
            ) attendance ON attendance.agent_id = fa.id
            LEFT JOIN (
                SELECT cd.agent_id, COUNT(*) AS pending_count
                FROM cash_deposits cd
                WHERE cd.status = 'pending'
                GROUP BY cd.agent_id
            ) cash_pending ON cash_pending.agent_id = fa.id
            LEFT JOIN (
                SELECT cd.agent_id, COUNT(*) AS overdue_count
                FROM cash_deposits cd
                WHERE cd.status = 'overdue'
                GROUP BY cd.agent_id
            ) cash_overdue ON cash_overdue.agent_id = fa.id
            WHERE fa.id = ?
            LIMIT 1
        ");
        $dataStmt->execute([$monthKey, $monthKey, $agentId]);
        $agentRow = $dataStmt->fetch(PDO::FETCH_ASSOC);

        if ($agentRow) {
            $metrics = getAgentPayrollMetrics($agentRow);
            $status = $metrics['eligible'] ? 'credited' : 'pending';
            $creditedAt = $metrics['eligible'] ? date('Y-m-d H:i:s') : null;
            $creditedBy = $metrics['eligible'] ? (int)($_SESSION['user_id'] ?? 0) : null;

            $upsertStmt = $pdo->prepare("
                INSERT INTO agent_salary_ledger
                    (agent_id, month_key, target_amount, achieved_amount, base_salary, incentive_amount, total_payable, status, credited_at, credited_by_user_id, remarks)
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    target_amount = VALUES(target_amount),
                    achieved_amount = VALUES(achieved_amount),
                    base_salary = VALUES(base_salary),
                    incentive_amount = VALUES(incentive_amount),
                    total_payable = VALUES(total_payable),
                    status = VALUES(status),
                    credited_at = VALUES(credited_at),
                    credited_by_user_id = VALUES(credited_by_user_id),
                    remarks = VALUES(remarks)
            ");

            $remarks = $metrics['eligible']
                ? 'Salary credited after meeting target, attendance and compliance.'
                : 'Not credited: criteria not met.';

            $upsertStmt->execute([
                $agentId,
                $monthKey,
                $metrics['target'],
                $metrics['achieved'],
                $metrics['base_salary'],
                $metrics['incentive_amount'],
                $metrics['total_payable'],
                $status,
                $creditedAt,
                $creditedBy,
                $remarks
            ]);

            setFlash($metrics['eligible'] ? 'success' : 'error', $metrics['eligible'] ? 'Salary credited successfully.' : 'Criteria not met. Salary stays pending.');
        }
    }
    header('Location: agent-payroll.php');
    exit;
}

$selectedMonth = trim($_GET['month'] ?? date('Y-m'));
$search = trim($_GET['search'] ?? '');

$where = [];
$params = [$selectedMonth, $selectedMonth, $selectedMonth];

if ($search !== '') {
    $where[] = "(u.name LIKE ? OR u.email LIKE ? OR u.state LIKE ? OR u.area LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

$countSql = "SELECT COUNT(*) FROM field_agents fa JOIN users u ON u.id = fa.user_id " . ($search !== '' ? "WHERE (u.name LIKE ? OR u.email LIKE ? OR u.state LIKE ? OR u.area LIKE ?)" : "");
$countParams = $search !== '' ? ["%$search%", "%$search%", "%$search%", "%$search%"] : [];
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($countParams);
$totalAgents = (int)$countStmt->fetchColumn();

$agentsStmt = $pdo->prepare("
    SELECT fa.id, fa.user_id, fa.target_amount_monthly, fa.base_salary_monthly, fa.incentive_rate_percent, fa.min_attendance_days,
           u.name, u.email, u.state, u.area,
           COALESCE(collected.total, 0) AS monthly_collected,
           COALESCE(attendance.days_count, 0) AS attendance_days_month,
           COALESCE(cash_pending.pending_count, 0) AS pending_deposits,
           COALESCE(cash_overdue.overdue_count, 0) AS overdue_deposits,
           asl.status AS salary_status,
           asl.total_payable,
           asl.credited_at
    FROM field_agents fa
    JOIN users u ON u.id = fa.user_id
    LEFT JOIN (
        SELECT d.field_agent_id AS agent_id, SUM(d.amount) AS total
        FROM donations d
        WHERE DATE_FORMAT(d.created_at, '%Y-%m') = ?
        GROUP BY d.field_agent_id
    ) collected ON collected.agent_id = fa.id
    LEFT JOIN (
        SELECT aa.agent_id, COUNT(DISTINCT DATE(aa.punch_time)) AS days_count
        FROM agent_attendance aa
        WHERE DATE_FORMAT(aa.punch_time, '%Y-%m') = ?
        GROUP BY aa.agent_id
    ) attendance ON attendance.agent_id = fa.id
    LEFT JOIN (
        SELECT cd.agent_id, COUNT(*) AS pending_count
        FROM cash_deposits cd
        WHERE cd.status = 'pending'
        GROUP BY cd.agent_id
    ) cash_pending ON cash_pending.agent_id = fa.id
    LEFT JOIN (
        SELECT cd.agent_id, COUNT(*) AS overdue_count
        FROM cash_deposits cd
        WHERE cd.status = 'overdue'
        GROUP BY cd.agent_id
    ) cash_overdue ON cash_overdue.agent_id = fa.id
    LEFT JOIN agent_salary_ledger asl ON asl.agent_id = fa.id AND asl.month_key = ?
    $whereSql
    ORDER BY (COALESCE(collected.total,0) / NULLIF(fa.target_amount_monthly,0)) DESC, u.name ASC
    LIMIT $perPage OFFSET $offset
");
$agentsStmt->execute($params);
$agents = $agentsStmt->fetchAll(PDO::FETCH_ASSOC);

$achievers = [];
foreach ($agents as $agent) {
    $m = getAgentPayrollMetrics($agent);
    if ($m['eligible']) {
        $agent['achievement_percent'] = $m['achievement_percent'];
        $achievers[] = $agent;
    }
}

require '../includes/header.php';
?>
<div class="container mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold">Agent Payroll & Incentives</h1>
            <p class="text-gray-600">Month: <?php echo htmlspecialchars(date('F Y', strtotime($selectedMonth . '-01'))); ?> | Salary + Target + Incentive + Credit Status</p>
        </div>
        <div class="bg-green-50 border border-green-200 rounded-lg px-4 py-2 text-sm">
            <span class="font-semibold text-green-700">Incentive Rule:</span>
            <span class="text-green-800">Target met + attendance + compliance => salary credited with incentive</span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-8">
        <div class="bg-white rounded-xl border p-5">
            <p class="text-sm text-gray-500">Total Agents</p>
            <p class="text-2xl font-bold"><?php echo $totalAgents; ?></p>
        </div>
        <div class="bg-white rounded-xl border p-5">
            <p class="text-sm text-gray-500">Eligible Achievers</p>
            <p class="text-2xl font-bold text-emerald-600"><?php echo count($achievers); ?></p>
        </div>
        <div class="bg-white rounded-xl border p-5">
            <p class="text-sm text-gray-500">Salaries Credited</p>
            <p class="text-2xl font-bold text-blue-600"><?php echo count(array_filter($agents, fn($a) => ($a['salary_status'] ?? '') === 'credited')); ?></p>
        </div>
    </div>

    <!-- Search & Month Filters -->
    <form method="GET" class="mb-6 flex flex-wrap gap-3 items-center">
        <div>
            <input type="month" name="month" value="<?php echo htmlspecialchars($selectedMonth); ?>"
                   class="bg-white border border-gray-200 rounded-xl px-3 py-2 text-xs text-gray-800">
        </div>
        <div class="relative flex-1 min-w-[200px] max-w-md">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search agent name, email, state, area..."
                   class="w-full pl-9 pr-4 py-2 bg-white border border-gray-200 rounded-xl text-xs text-gray-800 focus:ring-2 focus:ring-blue-500">
            <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
        </div>
        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition">
            Filter
        </button>
        <?php if ($selectedMonth !== date('Y-m') || $search !== ''): ?>
            <a href="agent-payroll.php" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-bold rounded-xl transition">
                Reset
            </a>
        <?php endif; ?>
    </form>

    <?php if (!empty($achievers)): ?>
    <div class="bg-white rounded-xl border p-6 mb-8">
        <h2 class="text-xl font-bold mb-4">Incentive Achiever List</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <?php foreach ($achievers as $achiever): ?>
                <div class="border rounded-lg p-4 bg-emerald-50 border-emerald-200">
                <div class="font-semibold"><?php echo htmlspecialchars($achiever['name']); ?></div>
                    <div class="text-sm text-gray-600"><?php echo htmlspecialchars($achiever['state'] . ' / ' . $achiever['area']); ?></div>
                    <div class="mt-2 text-sm">
                        <span class="font-semibold text-emerald-700"><?php echo number_format((float)$achiever['achievement_percent'], 1); ?>%</span> target achieved
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-xl border overflow-x-auto mb-4">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-bold uppercase">Agent</th>
                    <th class="px-4 py-3 text-left text-xs font-bold uppercase">Target / Achieved</th>
                    <th class="px-4 py-3 text-left text-xs font-bold uppercase">Attendance</th>
                    <th class="px-4 py-3 text-left text-xs font-bold uppercase">Salary + Incentive</th>
                    <th class="px-4 py-3 text-left text-xs font-bold uppercase">Criteria</th>
                    <th class="px-4 py-3 text-left text-xs font-bold uppercase">Credit Status</th>
                    <th class="px-4 py-3 text-left text-xs font-bold uppercase">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($agents as $agent): ?>
                    <?php $metrics = getAgentPayrollMetrics($agent); ?>
                    <tr class="border-t">
                        <td class="px-4 py-3">
                            <div class="font-semibold"><?php echo htmlspecialchars($agent['name']); ?></div>
                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars($agent['state'] . ' / ' . $agent['area']); ?></div>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            ₹<?php echo number_format($metrics['target']); ?> / ₹<?php echo number_format($metrics['achieved']); ?>
                            <div class="text-xs text-gray-500"><?php echo number_format($metrics['achievement_percent'], 1); ?>%</div>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <?php echo (int)$metrics['attendance_days']; ?> days
                            <div class="text-xs text-gray-500">Min <?php echo (int)$metrics['min_attendance_days']; ?></div>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <div>Base: ₹<?php echo number_format($metrics['base_salary']); ?></div>
                            <div>Inc: ₹<?php echo number_format($metrics['incentive_amount']); ?> (<?php echo number_format($metrics['incentive_rate_percent'], 2); ?>%)</div>
                            <div class="font-semibold">Total: ₹<?php echo number_format($metrics['total_payable']); ?></div>
                        </td>
                        <td class="px-4 py-3 text-xs">
                            <div class="<?php echo $metrics['target_met'] ? 'text-emerald-600' : 'text-red-600'; ?>">Target: <?php echo $metrics['target_met'] ? 'OK' : 'NO'; ?></div>
                            <div class="<?php echo $metrics['attendance_met'] ? 'text-emerald-600' : 'text-red-600'; ?>">Attendance: <?php echo $metrics['attendance_met'] ? 'OK' : 'NO'; ?></div>
                            <div class="<?php echo $metrics['compliance_met'] ? 'text-emerald-600' : 'text-red-600'; ?>">Compliance: <?php echo $metrics['compliance_met'] ? 'OK' : ('Overdue: ' . (int)$metrics['overdue_deposits']); ?></div>
                            <div class="text-gray-500">Pending (allowed): <?php echo (int)$metrics['pending_deposits']; ?></div>
                        </td>
                        <td class="px-4 py-3">
                            <?php if (($agent['salary_status'] ?? '') === 'credited'): ?>
                                <span class="px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">Credited</span>
                                <div class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars((string)$agent['credited_at']); ?></div>
                            <?php else: ?>
                                <span class="px-2 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <form method="POST">
                                <input type="hidden" name="credit_salary" value="1">
                                <input type="hidden" name="agent_id" value="<?php echo (int)$agent['id']; ?>">
                                <button type="submit" class="px-3 py-1.5 rounded text-xs font-semibold <?php echo $metrics['eligible'] ? 'bg-emerald-600 text-white hover:bg-emerald-700' : 'bg-gray-200 text-gray-700'; ?>">
                                    <?php echo $metrics['eligible'] ? 'Credit Salary' : 'Not Eligible'; ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($agents)): ?>
                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">No agent payroll records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php echo render_admin_pagination($totalAgents, $page, $perPage, ['month' => $selectedMonth, 'search' => $search]); ?>
</div>

<?php require '../includes/footer.php'; ?>

