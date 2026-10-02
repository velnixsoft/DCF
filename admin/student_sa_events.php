<?php require 'includes/header.php'; ?>
<?php require '../config/db.php'; ?>

<?php
if (!checkRole($pdo, 'coordinator')) {
    header('Location: dashboard.php');
    exit;
}

$events = [];
if (dbTableExists($pdo, 'sa_events')) {
    $events = $pdo->query("
        SELECT e.*, s.full_name AS submitter_name, s.student_no
        FROM sa_events e
        LEFT JOIN sa_students s ON s.id = e.submitted_by_student_id
        ORDER BY e.created_at DESC LIMIT 200
    ")->fetchAll(PDO::FETCH_ASSOC);
}
$csrfToken = generateCsrfToken();
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900">
    <?php require 'includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col md:ml-64">
        <?php require 'includes/navbar.php'; ?>
        <main class="flex-1 overflow-y-auto p-4 md:p-6">
            <h3 class="text-2xl font-bold mb-6">Ambassador Events (sa_events)</h3>
            <div class="grid grid-cols-1 gap-4 lg:hidden">
                <?php foreach ($events as $ev): ?>
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 p-4 space-y-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-bold text-gray-900 dark:text-white"><?php echo htmlspecialchars($ev['title']); ?></p>
                                <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($ev['submitter_name'] ?? 'Admin'); ?><?php echo $ev['student_no'] ? ' • ' . htmlspecialchars($ev['student_no']) : ''; ?></p>
                            </div>
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-orange-100 text-orange-800"><?php echo htmlspecialchars($ev['status']); ?></span>
                        </div>
                        <p class="text-xs"><?php echo $ev['start_date'] ? date('d M Y', strtotime($ev['start_date'])) : '-'; ?> • <?php echo htmlspecialchars($ev['venue'] ?? $ev['city_name'] ?? '-'); ?></p>
                        <div class="flex flex-wrap gap-2">
                            <?php if ($ev['status'] === 'Pending'): ?>
                                <form action="actions/sa_event_logic.php" method="POST" class="flex-1 min-w-[110px]">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                    <input type="hidden" name="id" value="<?php echo (int)$ev['id']; ?>">
                                    <button name="action" value="approve" class="w-full bg-emerald-600 text-white px-3 py-2.5 rounded-xl text-sm font-bold">Approve</button>
                                </form>
                                <form action="actions/sa_event_logic.php" method="POST" class="flex-1 min-w-[110px]">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                    <input type="hidden" name="id" value="<?php echo (int)$ev['id']; ?>">
                                    <button name="action" value="reject" class="w-full bg-red-600 text-white px-3 py-2.5 rounded-xl text-sm font-bold">Reject</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($ev['status'] === 'Approved'): ?>
                                <form action="actions/sa_event_logic.php" method="POST" class="w-full">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                    <input type="hidden" name="id" value="<?php echo (int)$ev['id']; ?>">
                                    <button name="action" value="complete" class="w-full bg-blue-600 text-white px-3 py-2.5 rounded-xl text-sm font-bold">Mark Completed</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($events)): ?><div class="bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 p-8 text-center text-gray-400">No ambassador events yet.</div><?php endif; ?>
            </div>
            <div class="hidden lg:block bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase font-bold">
                        <tr><th class="p-4">Event</th><th class="p-4">Submitted By</th><th class="p-4">When / Where</th><th class="p-4">Status</th><th class="p-4 text-right">Actions</th></tr>
                    </thead>
                    <tbody class="divide-y dark:divide-gray-700">
                        <?php foreach ($events as $ev): ?>
                            <tr>
                                <td class="p-4 font-bold"><?php echo htmlspecialchars($ev['title']); ?></td>
                                <td class="p-4 text-xs"><?php echo htmlspecialchars($ev['submitter_name'] ?? 'Admin'); ?> <?php echo $ev['student_no'] ? '(' . htmlspecialchars($ev['student_no']) . ')' : ''; ?></td>
                                <td class="p-4 text-xs"><?php echo $ev['start_date'] ? date('d M Y', strtotime($ev['start_date'])) : '—'; ?> · <?php echo htmlspecialchars($ev['venue'] ?? $ev['city_name'] ?? '—'); ?></td>
                                <td class="p-4"><span class="px-2 py-0.5 rounded-full text-xs font-bold bg-orange-100 text-orange-800"><?php echo htmlspecialchars($ev['status']); ?></span></td>
                                <td class="p-4 text-right">
                                    <?php if ($ev['status'] === 'Pending'): ?>
                                        <form action="actions/sa_event_logic.php" method="POST" class="inline-flex gap-1">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                            <input type="hidden" name="id" value="<?php echo (int)$ev['id']; ?>">
                                            <button name="action" value="approve" class="bg-emerald-600 text-white px-3 py-1 rounded-lg text-xs font-bold">Approve</button>
                                            <button name="action" value="reject" class="bg-red-600 text-white px-3 py-1 rounded-lg text-xs font-bold">Reject</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($ev['status'] === 'Approved'): ?>
                                        <form action="actions/sa_event_logic.php" method="POST" class="inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                            <input type="hidden" name="id" value="<?php echo (int)$ev['id']; ?>">
                                            <button name="action" value="complete" class="bg-blue-600 text-white px-3 py-1 rounded-lg text-xs font-bold">Mark Completed</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($events)): ?><tr><td colspan="5" class="p-8 text-center text-gray-400">No ambassador events yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>
<?php require 'includes/footer.php'; ?>
