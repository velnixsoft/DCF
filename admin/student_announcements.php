<?php require 'includes/header.php'; ?>
<?php require '../config/db.php'; ?>

<?php
if (!checkRole($pdo, 'coordinator')) {
    header('Location: dashboard.php');
    exit;
}

$announcements = [];
$totalAnnouncements = 0;
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

if (dbTableExists($pdo, 'sa_announcements')) {
    $totalAnnouncements = (int)$pdo->query('SELECT COUNT(*) FROM sa_announcements')->fetchColumn();
    $totalPages = max(1, ceil($totalAnnouncements / $perPage));
    if ($page > $totalPages && $totalAnnouncements > 0) $page = $totalPages;
    $offset = ($page - 1) * $perPage;

    $stmt = $pdo->prepare('SELECT * FROM sa_announcements ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
    $stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
$csrfToken = generateCsrfToken();
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900">
    <?php require 'includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col md:ml-64">
        <?php require 'includes/navbar.php'; ?>
        <main class="flex-1 overflow-y-auto p-4 md:p-6">
            <h3 class="text-2xl font-bold text-gray-800 dark:text-white mb-6">Student Announcements</h3>
            <div class="grid gap-6 lg:grid-cols-[380px_1fr]">
                <form action="actions/announcement_logic.php" method="POST" class="bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 p-6 space-y-4 h-fit">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="action" value="broadcast">
                    <div>
                        <label class="text-xs font-bold text-gray-500">Title *</label>
                        <input type="text" name="title" required class="w-full mt-1 px-3 py-2.5 rounded-xl border dark:border-gray-600 dark:bg-gray-700 text-sm">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-500">Message *</label>
                        <textarea name="message" required rows="4" class="w-full mt-1 px-3 py-2.5 rounded-xl border dark:border-gray-600 dark:bg-gray-700 text-sm"></textarea>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-500">Target</label>
                        <select name="target_scope" class="w-full mt-1 px-3 py-2.5 rounded-xl border dark:border-gray-600 dark:bg-gray-700 text-sm">
                            <option value="all">All Active Students</option>
                            <option value="city">By City</option>
                            <option value="college">By College</option>
                            <option value="level">By Level</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-500">Target Value (if filtered)</label>
                        <input type="text" name="target_value" placeholder="e.g. Delhi, Campus Ambassador" class="w-full mt-1 px-3 py-2.5 rounded-xl border dark:border-gray-600 dark:bg-gray-700 text-sm">
                    </div>
                    <button type="submit" class="w-full bg-emerald-600 text-white rounded-xl py-2.5 text-sm font-bold">Broadcast</button>
                </form>
                <div class="bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase font-bold"><tr><th class="p-4 text-left">Title</th><th class="p-4">Scope</th><th class="p-4">Date</th></tr></thead>
                        <tbody class="divide-y dark:divide-gray-700">
                            <?php foreach ($announcements as $a): ?>
                                <tr><td class="p-4 font-semibold"><?php echo htmlspecialchars($a['title']); ?></td><td class="p-4 text-xs"><?php echo htmlspecialchars($a['target_scope'] . ($a['target_value'] ? ': ' . $a['target_value'] : '')); ?></td><td class="p-4 text-xs"><?php echo date('d M Y', strtotime($a['created_at'])); ?></td></tr>
                            <?php endforeach; ?>
                            <?php if (empty($announcements)): ?><tr><td colspan="3" class="p-8 text-center text-gray-400">No announcements yet.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                    <?php if ($totalAnnouncements > 0): ?>
                        <?= render_admin_pagination($totalAnnouncements, $page, $perPage); ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>
<?php require 'includes/footer.php'; ?>
