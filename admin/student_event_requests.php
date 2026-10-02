<?php require 'includes/header.php'; ?>
<?php require '../config/db.php'; ?>

<?php
if (!checkRole($pdo, 'coordinator')) {
    setFlash('error', 'Unauthorized access.');
    header('Location: dashboard.php');
    exit;
}

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$where = [];
$params = [];
if ($search !== '') {
    $where[] = "(r.event_title LIKE :search OR r.description LIKE :search OR s.full_name LIKE :search OR s.student_no LIKE :search)";
    $params[':search'] = "%$search%";
}
if ($statusFilter !== '') {
    $where[] = "r.status = :status";
    $params[':status'] = $statusFilter;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

$totalRequests = 0;
$requests = [];

if (dbTableExists($pdo, 'sa_student_event_requests')) {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM sa_student_event_requests r JOIN sa_students s ON s.id = r.student_id $whereSql");
    foreach ($params as $k => $v) $countStmt->bindValue($k, $v);
    $countStmt->execute();
    $totalRequests = (int)$countStmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT r.*, s.full_name, s.student_no, s.college_name, s.city_name
        FROM sa_student_event_requests r
        JOIN sa_students s ON s.id = r.student_id
        $whereSql
        ORDER BY r.created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
$csrfToken = generateCsrfToken();
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900">
    <?php require 'includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>
        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6">
            <div class="mb-6">
                <h3 class="text-2xl font-bold text-gray-800 dark:text-white">Student Event Requests</h3>
                <p class="text-xs text-gray-500 mt-1">Review ambassador-submitted event proposals before publishing.</p>
            </div>

            <!-- Search & Filter -->
            <form method="GET" class="mb-6 flex flex-wrap gap-3 items-center">
                <div class="relative flex-1 min-w-[220px] max-w-md">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search student, event title, description..."
                           class="w-full pl-9 pr-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-emerald-500">
                    <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                </div>
                <select name="status" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 text-xs text-gray-700 dark:text-gray-200">
                    <option value="">All Statuses</option>
                    <option value="Pending" <?php echo $statusFilter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="Approved" <?php echo $statusFilter === 'Approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="Rejected" <?php echo $statusFilter === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
                <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition">
                    Filter
                </button>
                <?php if ($search !== '' || $statusFilter !== ''): ?>
                    <a href="student_event_requests.php" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 text-gray-700 dark:text-gray-300 text-xs font-bold rounded-xl transition">
                        Reset
                    </a>
                <?php endif; ?>
            </form>

            <div class="grid grid-cols-1 gap-4 lg:hidden mb-6">
                <?php foreach ($requests as $req): ?>
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 p-4 space-y-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-bold text-gray-900 dark:text-white"><?php echo htmlspecialchars($req['event_title']); ?></p>
                                <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($req['full_name']); ?> • <?php echo htmlspecialchars($req['student_no']); ?></p>
                            </div>
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-orange-100 text-orange-800"><?php echo htmlspecialchars($req['status']); ?></span>
                        </div>
                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars(mb_strimwidth((string)($req['description'] ?? ''), 0, 120, '...')); ?></p>
                        <p class="text-xs"><?php echo !empty($req['event_date']) ? htmlspecialchars(date('d M Y', strtotime($req['event_date']))) : 'TBA'; ?> • <?php echo htmlspecialchars($req['event_location'] ?? '-'); ?></p>
                        <?php if ($req['status'] === 'Pending'): ?>
                            <form action="actions/student_event_logic.php" method="POST" class="flex flex-wrap gap-2">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <input type="hidden" name="id" value="<?php echo (int)$req['id']; ?>">
                                <button name="action" value="approve" class="flex-1 min-w-[110px] bg-emerald-600 text-white px-3 py-2.5 rounded-xl text-sm font-bold">Approve</button>
                                <button name="action" value="reject" class="flex-1 min-w-[110px] bg-red-600 text-white px-3 py-2.5 rounded-xl text-sm font-bold">Reject</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($requests)): ?><div class="bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 p-8 text-center text-gray-400">No event requests found.</div><?php endif; ?>
                <?php echo render_admin_pagination($totalRequests, $page, $perPage, ['search' => $search, 'status' => $statusFilter]); ?>
            </div>
            <div class="hidden lg:block bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase font-bold">
                        <tr>
                            <th class="p-4">Student</th>
                            <th class="p-4">Event</th>
                            <th class="p-4">When / Where</th>
                            <th class="p-4">Status</th>
                            <th class="p-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-gray-700">
                        <?php foreach ($requests as $req): ?>
                            <tr>
                                <td class="p-4">
                                    <p class="font-bold"><?php echo htmlspecialchars($req['full_name']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($req['student_no']); ?> · <?php echo htmlspecialchars($req['college_name']); ?></p>
                                </td>
                                <td class="p-4">
                                    <p class="font-semibold"><?php echo htmlspecialchars($req['event_title']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars(mb_strimwidth((string)($req['description'] ?? ''), 0, 80, '…')); ?></p>
                                </td>
                                <td class="p-4 text-xs">
                                    <?php echo !empty($req['event_date']) ? htmlspecialchars(date('d M Y', strtotime($req['event_date']))) : 'TBA'; ?><br>
                                    <?php echo htmlspecialchars($req['event_location'] ?? '—'); ?>
                                </td>
                                <td class="p-4"><span class="px-2 py-0.5 rounded-full text-xs font-bold bg-orange-100 text-orange-800"><?php echo htmlspecialchars($req['status']); ?></span></td>
                                <td class="p-4 text-right">
                                    <?php if ($req['status'] === 'Pending'): ?>
                                        <form action="actions/student_event_logic.php" method="POST" class="inline-flex gap-1">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                            <input type="hidden" name="id" value="<?php echo (int)$req['id']; ?>">
                                            <button name="action" value="approve" class="bg-emerald-600 text-white px-3 py-1.5 rounded-lg text-xs font-bold">Approve</button>
                                            <button name="action" value="reject" class="bg-red-600 text-white px-3 py-1.5 rounded-lg text-xs font-bold">Reject</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($requests)): ?>
                            <tr><td colspan="5" class="p-8 text-center text-gray-400">No event requests found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <?php echo render_admin_pagination($totalRequests, $page, $perPage, ['search' => $search, 'status' => $statusFilter]); ?>
            </div>
        </main>
    </div>
</div>

<?php require 'includes/footer.php'; ?>

