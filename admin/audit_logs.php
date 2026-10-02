<?php require 'includes/header.php'; ?>
<?php require '../config/db.php'; ?>

<?php
if (!checkRole($pdo, 'manager')) {
    setFlash('error', 'Unauthorized access.');
    header('Location: dashboard.php');
    exit;
}

$search = trim($_GET['search'] ?? '');
$actionFilter = trim($_GET['action_filter'] ?? '');

$where = [];
$params = [];
if ($search !== '') {
    $where[] = "(al.action LIKE :search OR al.entity_type LIKE :search OR al.description LIKE :search OR u.full_name LIKE :search)";
    $params[':search'] = "%$search%";
}
if ($actionFilter !== '') {
    $where[] = "al.action = :action";
    $params[':action'] = $actionFilter;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$totalLogs = 0;
$logs = [];
try {
    $countStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM admin_audit_logs al
        LEFT JOIN users u ON u.id = al.user_id
        $whereSql
    ");
    foreach ($params as $k => $v) $countStmt->bindValue($k, $v);
    $countStmt->execute();
    $totalLogs = (int)$countStmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT al.*, u.full_name AS admin_name
        FROM admin_audit_logs al
        LEFT JOIN users u ON u.id = al.user_id
        $whereSql
        ORDER BY al.created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $logs = [];
}
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900">
    <?php require 'includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6">
            <div class="mb-6">
                <h3 class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-white">Admin Audit Logs</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Security and operations trail for ambassador admin actions. (Total: <?php echo $totalLogs; ?> logs)</p>
            </div>

            <!-- Search & Filters -->
            <form method="GET" class="mb-6 flex flex-wrap gap-3 items-center">
                <div class="relative flex-1 min-w-[220px] max-w-md">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search action, entity, admin, description..."
                           class="w-full pl-9 pr-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-blue-500">
                    <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                </div>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition">
                    Filter
                </button>
                <?php if ($search !== '' || $actionFilter !== ''): ?>
                    <a href="audit_logs.php" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 text-gray-700 dark:text-gray-300 text-xs font-bold rounded-xl transition">
                        Reset
                    </a>
                <?php endif; ?>
            </form>

            <div class="divide-y dark:divide-gray-700 lg:hidden bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 overflow-hidden mb-6">
                <?php foreach ($logs as $log): ?>
                    <div class="p-4 space-y-2">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($log['admin_name'] ?? 'System'); ?></p>
                                <p class="text-xs text-gray-500"><?php echo date('d M Y H:i', strtotime($log['created_at'])); ?></p>
                            </div>
                            <p class="text-xs font-semibold text-gray-800 dark:text-white"><?php echo htmlspecialchars($log['action']); ?></p>
                        </div>
                        <p class="text-xs text-gray-600"><?php echo htmlspecialchars($log['entity_type'] . ($log['entity_id'] ? ' #' . $log['entity_id'] : '')); ?></p>
                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($log['description'] ?? ''); ?></p>
                        <p class="text-xs text-gray-400"><?php echo htmlspecialchars($log['ip_address'] ?? ''); ?></p>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($logs)): ?><div class="p-10 text-center text-gray-500">No audit logs found.</div><?php endif; ?>
                <?php echo render_admin_pagination($totalLogs, $page, $perPage, ['search' => $search, 'action_filter' => $actionFilter]); ?>
            </div>

            <div class="hidden lg:block bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase text-gray-500">
                            <tr>
                                <th class="p-3">When</th>
                                <th class="p-3">Admin</th>
                                <th class="p-3">Action</th>
                                <th class="p-3">Entity</th>
                                <th class="p-3">Description</th>
                                <th class="p-3">IP</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y dark:divide-gray-700">
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td class="p-3 text-xs text-gray-500 whitespace-nowrap"><?php echo date('d M Y H:i', strtotime($log['created_at'])); ?></td>
                                    <td class="p-3"><?php echo htmlspecialchars($log['admin_name'] ?? 'System'); ?></td>
                                    <td class="p-3 font-semibold text-gray-800 dark:text-white"><?php echo htmlspecialchars($log['action']); ?></td>
                                    <td class="p-3 text-gray-600"><?php echo htmlspecialchars($log['entity_type'] . ($log['entity_id'] ? ' #' . $log['entity_id'] : '')); ?></td>
                                    <td class="p-3 text-gray-600"><?php echo htmlspecialchars($log['description'] ?? ''); ?></td>
                                    <td class="p-3 text-xs text-gray-400"><?php echo htmlspecialchars($log['ip_address'] ?? ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="6" class="p-10 text-center text-gray-500">
                                        No audit logs found.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php echo render_admin_pagination($totalLogs, $page, $perPage, ['search' => $search, 'action_filter' => $actionFilter]); ?>
            </div>
        </main>
    </div>
</div>

<?php require 'includes/footer.php'; ?>
