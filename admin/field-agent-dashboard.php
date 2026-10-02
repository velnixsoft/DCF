<?php
session_start();
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

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: index.php');
    exit;
}

ensureFieldAgentSchema($pdo);

$userHierarchy = normalizeHierarchyRole($_SESSION['hierarchy_level'] ?? '');
if ($userHierarchy !== 'field_agent') {
    header('Location: field-agents.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$stmt = $pdo->prepare("
    SELECT fa.*, u.state, u.area, u.name,
           COALESCE(attendance_today.total_punches, 0) as punches_today,
           COALESCE(collected_month.total, 0) as monthly_collected,
           COALESCE(pending_deposits.count, 0) as pending_deposits,
           COALESCE(overdue_deposits.count, 0) as overdue_deposits,
           COALESCE(attendance_month.days_count, 0) as attendance_days_month,
           asl.status as salary_status,
           asl.total_payable as salary_total_payable,
           asl.credited_at as salary_credited_at
    FROM field_agents fa
    JOIN users u ON fa.user_id = u.id
    LEFT JOIN (
        SELECT agent_id, COUNT(*) as total_punches
        FROM agent_attendance 
        WHERE DATE(punch_time) = CURDATE()
        GROUP BY agent_id
    ) attendance_today ON fa.id = attendance_today.agent_id
    LEFT JOIN (
        SELECT d.field_agent_id as agent_id, SUM(d.amount) as total
        FROM donations d
        WHERE DATE_FORMAT(d.created_at, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
        GROUP BY d.field_agent_id
    ) collected_month ON fa.id = collected_month.agent_id
    LEFT JOIN (
        SELECT aa.agent_id, COUNT(DISTINCT DATE(aa.punch_time)) as days_count
        FROM agent_attendance aa
        WHERE DATE_FORMAT(aa.punch_time, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
        GROUP BY aa.agent_id
    ) attendance_month ON fa.id = attendance_month.agent_id
    LEFT JOIN (
        SELECT agent_id, COUNT(*) as count
        FROM cash_deposits WHERE status = 'pending'
        GROUP BY agent_id
    ) pending_deposits ON fa.id = pending_deposits.agent_id
    LEFT JOIN (
        SELECT agent_id, COUNT(*) as count
        FROM cash_deposits WHERE status = 'overdue'
        GROUP BY agent_id
    ) overdue_deposits ON fa.id = overdue_deposits.agent_id
    LEFT JOIN agent_salary_ledger asl
        ON asl.agent_id = fa.id
       AND asl.month_key = DATE_FORMAT(CURDATE(), '%Y-%m')
    WHERE fa.user_id = ?
");
$stmt->execute([$userId]);
$agentData = $stmt->fetch(PDO::FETCH_ASSOC);
$progressPercent = 0;
if (!empty($agentData['target_amount_monthly']) && (float)$agentData['target_amount_monthly'] > 0) {
    $progressPercent = min(100, ((float)$agentData['monthly_collected'] / (float)$agentData['target_amount_monthly']) * 100);
}

if (!$agentData) {
    die('Agent profile not found.');
}
$payrollMetrics = getAgentPayrollMetrics($agentData);

require '../includes/header.php';
?>
<div class="min-h-screen bg-gray-50 py-8">
    <div class="container mx-auto px-4">
        <div class="bg-white rounded-xl shadow-lg border p-8 mb-8">
            <div class="flex flex-col md:flex-row gap-6 items-start md:items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Welcome, <?php echo htmlspecialchars($agentData['name']); ?></h1>
                    <p class="text-gray-600"><?php echo ucwords(str_replace('_', ' ', $userHierarchy)); ?> - <?php echo htmlspecialchars($agentData['state'] . ', ' . $agentData['area']); ?></p>
                </div>
                <div class="ml-auto">
                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-bold <?php echo (($agentData['attendance_status'] ?? 'active') === 'active') ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                        <i class="fas fa-clock mr-1"></i> <?php echo (($agentData['attendance_status'] ?? 'active') === 'active') ? 'Active' : 'Restricted'; ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <h3 class="font-bold text-lg mb-4">Quick Actions</h3>
                <div class="grid grid-cols-2 gap-3">
                    <a href="../field-agent-punch.php" class="block p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-blue-400 hover:bg-blue-50 text-center transition">
                        <i class="fas fa-clock text-2xl text-blue-600 mb-2 block"></i>
                        <span class="font-bold text-gray-800 block">Punch In/Out</span>
                    </a>
                    <a href="../field-agent-collect.php" class="block p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-green-400 hover:bg-green-50 text-center transition">
                        <i class="fas fa-rupee-sign text-2xl text-green-600 mb-2 block"></i>
                        <span class="font-bold text-gray-800 block">Collect Donation</span>
                    </a>
                </div>
            </div>

            <!-- Stats -->
            <div class="bg-gradient-to-br from-blue-500 to-indigo-600 text-white rounded-xl p-6">
                <h3 class="font-bold text-xl mb-6">This Month</h3>
                <div class="space-y-4">
                    <div class="flex justify-between">
                        <span>Target</span>
                        <span class="font-bold">₹<?php echo number_format($agentData['target_amount_monthly']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Collected</span>
                        <span class="font-bold">₹<?php echo number_format($agentData['monthly_collected']); ?></span>
                    </div>
                    <div class="w-full bg-white/20 rounded-full h-3">
                        <div class="bg-white h-3 rounded-full" style="width: <?php echo (float)$progressPercent; ?>%"></div>
                    </div>
                    <div class="text-sm opacity-90"><span id="pending-deposits-count"><?php echo (int)$agentData['pending_deposits']; ?></span> pending deposits</div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <h3 class="font-bold text-lg mb-4">Salary + Incentive</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span>Base Salary</span><span class="font-semibold">₹<?php echo number_format($payrollMetrics['base_salary']); ?></span></div>
                    <div class="flex justify-between"><span>Incentive (<?php echo number_format($payrollMetrics['incentive_rate_percent'], 2); ?>%)</span><span class="font-semibold">₹<?php echo number_format($payrollMetrics['incentive_amount']); ?></span></div>
                    <div class="flex justify-between text-base border-t pt-2"><span class="font-bold">Total Payable</span><span class="font-bold">₹<?php echo number_format($payrollMetrics['total_payable']); ?></span></div>
                </div>
                <div class="mt-4 text-xs">
                    <?php if (($agentData['salary_status'] ?? '') === 'credited'): ?>
                        <span class="px-2 py-1 rounded bg-blue-100 text-blue-700 font-semibold">Salary Credited</span>
                        <div class="text-gray-500 mt-1">Credited on: <?php echo htmlspecialchars((string)$agentData['salary_credited_at']); ?></div>
                    <?php else: ?>
                        <span class="px-2 py-1 rounded bg-yellow-100 text-yellow-700 font-semibold">Salary Pending Credit</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border p-6">
                <h3 class="font-bold text-lg mb-4">Eligibility Criteria Status</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between"><span>Target Met</span><span class="<?php echo $payrollMetrics['target_met'] ? 'text-emerald-600' : 'text-red-600'; ?> font-semibold"><?php echo $payrollMetrics['target_met'] ? 'YES' : 'NO'; ?></span></div>
                    <div class="flex justify-between"><span>Attendance (<?php echo (int)$payrollMetrics['attendance_days']; ?>/<?php echo (int)$payrollMetrics['min_attendance_days']; ?>)</span><span class="<?php echo $payrollMetrics['attendance_met'] ? 'text-emerald-600' : 'text-red-600'; ?> font-semibold"><?php echo $payrollMetrics['attendance_met'] ? 'YES' : 'NO'; ?></span></div>
                    <div class="flex justify-between"><span>Compliance (overdue only)</span><span class="<?php echo $payrollMetrics['compliance_met'] ? 'text-emerald-600' : 'text-red-600'; ?> font-semibold"><?php echo $payrollMetrics['compliance_met'] ? 'CLEAR' : (int)$payrollMetrics['overdue_deposits']; ?></span></div>
                    <div class="flex justify-between"><span>Pending deposits (allowed)</span><span class="text-gray-600 font-semibold"><?php echo (int)$payrollMetrics['pending_deposits']; ?></span></div>
                    <div class="border-t pt-3 flex justify-between"><span class="font-bold">You are eligible</span><span class="<?php echo $payrollMetrics['eligible'] ? 'text-emerald-700' : 'text-red-700'; ?> font-bold"><?php echo $payrollMetrics['eligible'] ? 'YES' : 'NO'; ?></span></div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <h3 class="font-bold text-xl mb-4">Recent Activity</h3>
            <div id="activity-list" class="space-y-3">
                <!-- JS populated -->
            </div>
        </div>
    </div>

    <script>
        function loadRecentActivity() {
            fetch('../api/agent_recent_activity.php?limit=10')
                .then(r => r.json())
                .then(data => {
                    const container = document.getElementById('activity-list');
                    container.innerHTML = '';
                    if (data.success) {
                        if (!data.activities.length) {
                            container.innerHTML = '<div class="text-sm text-gray-500">No recent activity found.</div>';
                        }
                        data.activities.forEach(activity => {
                            container.innerHTML += `
                                <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 font-bold text-sm ml-2">${activity.initial}</div>
                                    <div class="ml-3">
                                        <div class="font-medium">${activity.action}</div>
                                        <div class="text-xs text-gray-500">${activity.time}</div>
                                    </div>
                                </div>
                            `;
                        });
                    }
                });
        }

        function loadLiveStats() {
            fetch('../api/agent_live_stats.php')
                .then(r => r.json())
                .then(data => {
                    if (!data.success) return;
                    if (document.getElementById('pending-deposits-count')) {
                        document.getElementById('pending-deposits-count').textContent = data.pending_deposits ?? 0;
                    }
                });
        }

        loadRecentActivity();
        loadLiveStats();
        setInterval(loadRecentActivity, 30000);
        setInterval(loadLiveStats, 15000);
    </script>
</div>

<?php require '../includes/footer.php'; ?>

