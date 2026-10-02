<?php require 'includes/header.php'; ?>
<?php require '../config/db.php'; ?>

<?php
if (!checkRole($pdo, 'coordinator')) {
    setFlash('error', 'Unauthorized access.');
    header('Location: dashboard.php');
    exit;
}

$pendingEmailCount = 0;
$recentNotifications = [];
$totalNotifications = 0;

$search = trim($_GET['search'] ?? '');
$emailStatus = trim($_GET['email_status'] ?? '');

$where = [];
$params = [];
if ($search !== '') {
    $where[] = "(sn.title LIKE :search OR sn.message LIKE :search OR s.full_name LIKE :search OR s.email LIKE :search)";
    $params[':search'] = "%$search%";
}
if ($emailStatus === 'sent') {
    $where[] = "sn.email_sent = 1";
} elseif ($emailStatus === 'pending') {
    $where[] = "sn.email_sent = 0";
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

try {
    $pendingEmailCount = (int)$pdo->query("
        SELECT COUNT(*) FROM sa_notifications
        WHERE email_sent = 0 AND email_retry_count < 5
    ")->fetchColumn();

    $countStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM sa_notifications sn
        JOIN sa_students s ON s.id = sn.student_id
        $whereSql
    ");
    foreach ($params as $k => $v) $countStmt->bindValue($k, $v);
    $countStmt->execute();
    $totalNotifications = (int)$countStmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT sn.*, s.full_name, s.email
        FROM sa_notifications sn
        JOIN sa_students s ON s.id = sn.student_id
        $whereSql
        ORDER BY sn.created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $recentNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $pendingEmailCount = 0;
    $recentNotifications = [];
}
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900">
    <?php require 'includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6">
            <div class="mb-6">
                <h3 class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-white">Notification Queue</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Monitor dashboard notifications and pending email delivery.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-xl border dark:border-gray-700 p-5">
                    <p class="text-xs uppercase tracking-wider text-gray-500">Pending Emails</p>
                    <p class="text-3xl font-bold text-orange-600 mt-2"><?php echo $pendingEmailCount; ?></p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border dark:border-gray-700 p-5 md:col-span-2">
                    <p class="text-sm text-gray-600 dark:text-gray-300">Run the queue processor via cron:</p>
                    <code class="mt-2 block text-xs bg-gray-100 dark:bg-gray-900 p-3 rounded-lg overflow-x-auto">php process/run_notification_queue_cron.php --limit=100</code>
                </div>
            </div>

            <!-- Search & Filters -->
            <form method="GET" class="mb-6 flex flex-wrap gap-3 items-center">
                <div class="relative flex-1 min-w-[220px] max-w-md">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search student, email, title..."
                           class="w-full pl-9 pr-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-blue-500">
                    <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                </div>
                <select name="email_status" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 text-xs text-gray-700 dark:text-gray-200">
                    <option value="">All Delivery Statuses</option>
                    <option value="sent" <?php echo $emailStatus === 'sent' ? 'selected' : ''; ?>>Sent</option>
                    <option value="pending" <?php echo $emailStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
                </select>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition">
                    Filter
                </button>
                <?php if ($search !== '' || $emailStatus !== ''): ?>
                    <a href="notification_queue.php" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 text-gray-700 dark:text-gray-300 text-xs font-bold rounded-xl transition">
                        Reset
                    </a>
                <?php endif; ?>
            </form>

            <div class="bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 overflow-hidden">
                <div class="p-4 border-b dark:border-gray-700 font-bold text-gray-800 dark:text-white">
                    Notifications (Total: <?php echo $totalNotifications; ?>)
                </div>
                <div class="divide-y dark:divide-gray-700 lg:hidden">
                    <?php foreach ($recentNotifications as $row): ?>
                        <div class="p-4 space-y-2">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($row['full_name']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($row['notification_type']); ?></p>
                                </div>
                                <span class="px-2 py-1 rounded-full text-xs font-bold <?php echo !empty($row['email_sent']) ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'; ?>">
                                    <?php echo !empty($row['email_sent']) ? 'Sent' : 'Pending'; ?>
                                </span>
                            </div>
                            <p class="text-sm text-gray-700 dark:text-gray-200"><?php echo htmlspecialchars($row['title']); ?></p>
                            <p class="text-xs text-gray-500"><?php echo date('d M Y H:i', strtotime($row['created_at'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($recentNotifications)): ?>
                        <div class="p-8 text-center text-gray-500">No notifications found.</div>
                    <?php endif; ?>
                </div>
                <div class="hidden lg:block overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase text-gray-500">
                            <tr>
                                <th class="p-3">Student</th>
                                <th class="p-3">Type</th>
                                <th class="p-3">Title</th>
                                <th class="p-3">Email</th>
                                <th class="p-3 text-right">Created</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y dark:divide-gray-700">
                            <?php foreach ($recentNotifications as $row): ?>
                                <tr>
                                    <td class="p-3"><?php echo htmlspecialchars($row['full_name']); ?></td>
                                    <td class="p-3"><?php echo htmlspecialchars($row['notification_type']); ?></td>
                                    <td class="p-3"><?php echo htmlspecialchars($row['title']); ?></td>
                                    <td class="p-3">
                                        <span class="px-2 py-1 rounded-full text-xs font-bold <?php echo !empty($row['email_sent']) ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'; ?>">
                                            <?php echo !empty($row['email_sent']) ? 'Sent' : 'Pending'; ?>
                                        </span>
                                    </td>
                                    <td class="p-3 text-right text-xs text-gray-500"><?php echo date('d M Y H:i', strtotime($row['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recentNotifications)): ?>
                                <tr><td colspan="5" class="p-8 text-center text-gray-500">No notifications found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php echo render_admin_pagination($totalNotifications, $page, $perPage, ['search' => $search, 'email_status' => $emailStatus]); ?>
            </div>
        </main>
    </div>
</div>

<?php require 'includes/footer.php'; ?>

