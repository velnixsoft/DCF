<?php
require '../config/db.php';
require '../includes/functions.php';
require '../includes/header.php';

try {
    $pdo->exec("ALTER TABLE field_agents
        ADD COLUMN IF NOT EXISTS base_salary_monthly DECIMAL(10,2) DEFAULT 0.00,
        ADD COLUMN IF NOT EXISTS incentive_rate_percent DECIMAL(5,2) DEFAULT 2.00,
        ADD COLUMN IF NOT EXISTS min_attendance_days INT DEFAULT 20");
} catch (Throwable $e) {
}

if (!canAccessModule($pdo, 'area_manager', 'page.field_agents')) {
    setFlash('error', 'Access denied.');
    header('Location: dashboard.php');
    exit;
}

ensureFieldAgentSchema($pdo);

$search = trim($_GET['search'] ?? '');
$roleFilter = trim($_GET['role'] ?? '');

$where = [];
$params = [];
if ($search !== '') {
    $where[] = "(u.name LIKE :search OR u.email LIKE :search OR u.state LIKE :search OR u.area LIKE :search)";
    $params[':search'] = "%$search%";
}
if ($roleFilter !== '') {
    $where[] = "u.hierarchy_level = :role";
    $params[':role'] = $roleFilter;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM field_agents fa JOIN users u ON fa.user_id = u.id $whereSql");
foreach ($params as $k => $v) $countStmt->bindValue($k, $v);
$countStmt->execute();
$totalAgents = (int)$countStmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT fa.*, u.name, u.email, u.state, u.area, u.hierarchy_level,
           COALESCE(collected.total, 0) as monthly_collected,
           COALESCE(attendance.days_count, 0) as attendance_days_month,
           COALESCE(cash_issues.issue_count, 0) as pending_or_overdue_deposits
    FROM field_agents fa
    JOIN users u ON fa.user_id = u.id
    LEFT JOIN (
        SELECT d.field_agent_id as agent_id, SUM(d.amount) as total
        FROM donations d
        WHERE DATE_FORMAT(d.created_at, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
        GROUP BY d.field_agent_id
    ) collected ON fa.id = collected.agent_id
    LEFT JOIN (
        SELECT aa.agent_id, COUNT(DISTINCT DATE(aa.punch_time)) as days_count
        FROM agent_attendance aa
        WHERE DATE_FORMAT(aa.punch_time, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
        GROUP BY aa.agent_id
    ) attendance ON fa.id = attendance.agent_id
    LEFT JOIN (
        SELECT cd.agent_id, COUNT(*) as issue_count
        FROM cash_deposits cd
        WHERE cd.status IN ('pending', 'overdue')
        GROUP BY cd.agent_id
    ) cash_issues ON fa.id = cash_issues.agent_id
    $whereSql
    ORDER BY u.state, u.area, u.name
    LIMIT :limit OFFSET :offset
");
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$agents = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_agent'])) {
    // Add logic later
    setFlash('success', 'Agent added.');
}

?>
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">Field Agents Management</h1>
    
    <div class="bg-white rounded-xl shadow-sm border p-6 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="text-center p-4 bg-blue-50 rounded-lg">
                <div class="text-2xl font-bold text-blue-600"><?php echo $totalAgents; ?></div>
                <div class="text-sm text-gray-600">Total Agents</div>
            </div>
            <div class="text-center p-4 bg-green-50 rounded-lg">
                <div class="text-2xl font-bold text-green-600"><?php echo $pdo->query('SELECT SUM(collected_amount_monthly) FROM field_agents')->fetchColumn() ?: 0; ?></div>
                <div class="text-sm text-gray-600">Monthly Target</div>
            </div>
            <div class="text-center p-4 bg-orange-50 rounded-lg">
                <div class="text-2xl font-bold text-orange-600"><?php echo $pdo->query("SELECT COUNT(*) FROM cash_deposits WHERE status = 'pending'")->fetchColumn() ?: 0; ?></div>
                <div class="text-sm text-gray-600">Pending Deposits</div>
            </div>
            <div class="text-center p-4 bg-emerald-50 rounded-lg">
                <div class="text-2xl font-bold text-emerald-600">
                    <?php
                    $eligibleCount = 0;
                    foreach ($agents as $agentRow) {
                        $m = getAgentPayrollMetrics($agentRow);
                        if ($m['eligible']) {
                            $eligibleCount++;
                        }
                    }
                    echo $eligibleCount;
                    ?>
                </div>
                <div class="text-sm text-gray-600">Incentive Achievers</div>
            </div>
        </div>
    </div>

    <!-- Search & Filters -->
    <form method="GET" class="mb-6 flex flex-wrap gap-3 items-center">
        <div class="relative flex-1 min-w-[220px] max-w-md">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search agent name, email, state, area..."
                   class="w-full pl-9 pr-4 py-2 bg-white border border-gray-200 rounded-xl text-xs text-gray-800 focus:ring-2 focus:ring-blue-500">
            <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
        </div>
        <select name="role" class="bg-white border border-gray-200 rounded-xl px-3 py-2 text-xs text-gray-700">
            <option value="">All Roles</option>
            <option value="field_agent" <?php echo $roleFilter === 'field_agent' ? 'selected' : ''; ?>>Field Agent</option>
            <option value="area_manager" <?php echo $roleFilter === 'area_manager' ? 'selected' : ''; ?>>Area Manager</option>
        </select>
        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition">
            Filter
        </button>
        <?php if ($search !== '' || $roleFilter !== ''): ?>
            <a href="field-agents.php" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-bold rounded-xl transition">
                Reset
            </a>
        <?php endif; ?>
    </form>

    <div class="overflow-x-auto bg-white border border-gray-200 rounded-xl mb-4">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase">Name</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase">Role</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase">State/Area</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase">Target</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase">Collected</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase">Criteria</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase">Pending Deposits</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($agents as $agent): ?>
                <?php $metrics = getAgentPayrollMetrics($agent); ?>
                <tr class="border-t hover:bg-gray-50">
                    <td class="px-6 py-4 font-medium"><?php echo htmlspecialchars($agent['name']); ?></td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded-full bg-<?php echo $agent['hierarchy_level'] === 'field_agent' ? 'orange' : 'blue'; ?>-100 text-<?php echo $agent['hierarchy_level'] === 'field_agent' ? 'orange' : 'blue'; ?>-800">
                            <?php echo ucwords(str_replace('_', ' ', $agent['hierarchy_level'])); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($agent['state'] . ' / ' . $agent['area']); ?></td>
                    <td class="px-6 py-4 font-bold text-green-600">₹<?php echo number_format($agent['target_amount_monthly']); ?></td>
                    <td class="px-6 py-4 font-bold text-blue-600">₹<?php echo number_format($agent['monthly_collected']); ?></td>
                    <td class="px-6 py-4 text-xs">
                        <div class="<?php echo $metrics['target_met'] ? 'text-emerald-600' : 'text-red-600'; ?>">Target: <?php echo $metrics['target_met'] ? 'OK' : 'NO'; ?></div>
                        <div class="<?php echo $metrics['attendance_met'] ? 'text-emerald-600' : 'text-red-600'; ?>">Attendance: <?php echo $metrics['attendance_days']; ?>/<?php echo $metrics['min_attendance_days']; ?></div>
                        <div class="<?php echo $metrics['compliance_met'] ? 'text-emerald-600' : 'text-red-600'; ?>">Compliance: <?php echo $metrics['compliance_met'] ? 'OK' : 'NO'; ?></div>
                    </td>
                    <td class="px-6 py-4">
                        <?php 
                        $pending = $pdo->prepare("SELECT COUNT(*) FROM cash_deposits WHERE agent_id = ? AND status = 'pending'");
                        $pending->execute([$agent['id']]);
                        $count = $pending->fetchColumn();
                        echo $count;
                        ?>
                        <span class="text-orange-600 font-bold ml-1">(<?php echo $count > 0 ? 'Pending' : 'OK'; ?>)</span>
                    </td>
                    <td class="px-6 py-4">
                        <a href="agent-payroll.php" class="text-blue-600 hover:underline text-sm">Payroll</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($agents)): ?>
                <tr><td colspan="8" class="px-6 py-8 text-center text-gray-500">No field agents found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php echo render_admin_pagination($totalAgents, $page, $perPage, ['search' => $search, 'role' => $roleFilter]); ?>
</div>

<?php require '../includes/footer.php'; ?>

