<?php
require '../config/db.php';
require '../includes/functions.php';
require '../includes/header.php';

if (!canAccessModule($pdo, 'area_manager', 'page.agent_attendance')) {
    setFlash('error', 'Access denied.');
    header('Location: dashboard.php');
    exit;
}

ensureFieldAgentSchema($pdo);

$selectedDate = trim($_GET['date'] ?? date('Y-m-d'));
$search = trim($_GET['search'] ?? '');

$where = ["DATE(aa.punch_time) = :punch_date"];
$params = [':punch_date' => $selectedDate];

if ($search !== '') {
    $where[] = "(u.name LIKE :search OR fa.state LIKE :search OR fa.area LIKE :search)";
    $params[':search'] = "%$search%";
}
$whereSql = 'WHERE ' . implode(' AND ', $where);

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

$countStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM agent_attendance aa
    JOIN field_agents fa ON aa.agent_id = fa.id
    JOIN users u ON fa.user_id = u.id
    $whereSql
");
foreach ($params as $k => $v) $countStmt->bindValue($k, $v);
$countStmt->execute();
$totalAttendance = (int)$countStmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT aa.*, u.name, fa.state, fa.area
    FROM agent_attendance aa
    JOIN field_agents fa ON aa.agent_id = fa.id
    JOIN users u ON fa.user_id = u.id
    $whereSql
    ORDER BY aa.punch_time DESC
    LIMIT :limit OFFSET :offset
");
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$attendance = $stmt->fetchAll();

$summary = $pdo->prepare("SELECT 
    COUNT(DISTINCT agent_id) as active_agents,
    COUNT(*) as total_punches
    FROM agent_attendance 
    WHERE DATE(punch_time) = ?");
$summary->execute([$selectedDate]);
$summaryStats = $summary->fetch(PDO::FETCH_ASSOC) ?: ['active_agents' => 0, 'total_punches' => 0];
?>
<div class="container mx-auto px-4 py-8">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold">Agent Attendance</h1>
            <p class="text-xs text-gray-500 mt-1">Live agent punches and geo-verified logs.</p>
        </div>
        <div class="text-xs text-gray-500">Auto-refresh every 30s <span id="last-refresh-time"></span></div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm border p-6 mb-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <div class="text-center p-4 bg-green-50 rounded-lg">
                <div class="text-3xl font-bold text-green-600"><?php echo (int)($summaryStats['active_agents'] ?? 0); ?></div>
                <div class="text-sm text-gray-600">Active Agents (<?php echo htmlspecialchars($selectedDate); ?>)</div>
            </div>
            <div class="text-center p-4 bg-blue-50 rounded-lg">
                <div class="text-3xl font-bold text-blue-600"><?php echo (int)($summaryStats['total_punches'] ?? 0); ?></div>
                <div class="text-sm text-gray-600">Total Punches</div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" class="mb-6 flex flex-wrap gap-3 items-center">
        <div>
            <input type="date" name="date" value="<?php echo htmlspecialchars($selectedDate); ?>"
                   class="bg-white border border-gray-200 rounded-xl px-3 py-2 text-xs text-gray-800">
        </div>
        <div class="relative flex-1 min-w-[200px] max-w-md">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search agent name, state, area..."
                   class="w-full pl-9 pr-4 py-2 bg-white border border-gray-200 rounded-xl text-xs text-gray-800 focus:ring-2 focus:ring-blue-500">
            <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
        </div>
        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition">
            Filter
        </button>
        <?php if ($selectedDate !== date('Y-m-d') || $search !== ''): ?>
            <a href="agent-attendance.php" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-bold rounded-xl transition">
                Reset
            </a>
        <?php endif; ?>
    </form>

    <div class="bg-white rounded-xl shadow-sm border overflow-hidden mb-4">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-4 text-left text-sm font-bold text-gray-700">Agent</th>
                    <th class="px-6 py-4 text-left text-sm font-bold text-gray-700">Location</th>
                    <th class="px-6 py-4 text-left text-sm font-bold text-gray-700">Punch Type</th>
                    <th class="px-6 py-4 text-left text-sm font-bold text-gray-700">Time</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($attendance as $record): ?>
                <tr class="border-t hover:bg-gray-50">
                    <td class="px-6 py-4"><?php echo htmlspecialchars($record['name']); ?> (<?php echo htmlspecialchars($record['state']); ?>)</td>
                    <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($record['address'] ?: $record['area']); ?></td>
                    <td class="px-6 py-4">
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-<?php echo $record['punch_type'] === 'IN' ? 'green' : 'orange'; ?>-100 text-<?php echo $record['punch_type'] === 'IN' ? 'green' : 'orange'; ?>-800">
                            <?php echo $record['punch_type']; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm"><?php echo date('H:i:s', strtotime($record['punch_time'])); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($attendance)): ?>
                <tr><td colspan="4" class="px-6 py-8 text-center text-gray-500">No attendance records found for this date.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php echo render_admin_pagination($totalAttendance, $page, $perPage, ['date' => $selectedDate, 'search' => $search]); ?>
</div>

<script>
    function setRefreshTime() {
        const el = document.getElementById('last-refresh-time');
        if (el) {
            el.textContent = '(updated: ' + new Date().toLocaleTimeString() + ')';
        }
    }
    setRefreshTime();
    setInterval(() => {
        window.location.reload();
    }, 20000);
</script>

<?php require '../includes/footer.php'; ?>

