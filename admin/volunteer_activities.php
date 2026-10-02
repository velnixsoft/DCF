<?php
require 'includes/header.php';

$error = null;
$volunteers = [];
$events = [];
$activities = [];

try {
    $volunteers = $pdo->query("SELECT id, name, email FROM volunteers WHERE status = 'Active' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $volunteers = [];
}

try {
    $events = $pdo->query("SELECT id, title, event_date FROM events ORDER BY event_date DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $events = [];
}

try {
    $activities = $pdo->query("
        SELECT va.*, v.name AS volunteer_name, v.email AS volunteer_email, e.title AS event_title
        FROM volunteer_activities va
        INNER JOIN volunteers v ON v.id = va.volunteer_id
        LEFT JOIN events e ON e.id = va.event_id
        ORDER BY va.created_at DESC
        LIMIT 200
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $error = 'Volunteer activities table is missing. Run DB migration: Database/upgrade_v2.sql';
    $activities = [];
}
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900">
    <?php require 'includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                <div>
                    <h3 class="text-2xl md:text-3xl font-medium text-gray-800 dark:text-white">Volunteer Activities</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Track volunteer work, hours, and activity logs.</p>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4 mb-6">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-1 bg-white dark:bg-gray-800 rounded-xl shadow border dark:border-gray-700 p-5">
                    <h4 class="font-semibold mb-4 dark:text-white">Add Activity</h4>

                    <form action="actions/volunteer_activity_crud.php" method="POST" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="action" value="create">

                        <div>
                            <label class="text-sm text-gray-600 dark:text-gray-300">Volunteer *</label>
                            <select name="volunteer_id" required class="w-full mt-1 border rounded-lg p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="">Select volunteer</option>
                                <?php foreach ($volunteers as $v): ?>
                                    <option value="<?php echo (int)$v['id']; ?>"><?php echo htmlspecialchars($v['name'] . ' (' . $v['email'] . ')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($volunteers)): ?>
                                <p class="text-xs text-gray-500 mt-1">No active volunteers found.</p>
                            <?php endif; ?>
                        </div>

                        <div>
                            <label class="text-sm text-gray-600 dark:text-gray-300">Activity Type</label>
                            <input type="text" name="activity_type" placeholder="e.g. Field Visit, Training, Event Support" class="w-full mt-1 border rounded-lg p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>

                        <div>
                            <label class="text-sm text-gray-600 dark:text-gray-300">Hours Spent</label>
                            <input type="number" name="hours_spent" step="0.25" min="0" class="w-full mt-1 border rounded-lg p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>

                        <div>
                            <label class="text-sm text-gray-600 dark:text-gray-300">Link to Event (optional)</label>
                            <select name="event_id" class="w-full mt-1 border rounded-lg p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="">-- None --</option>
                                <?php foreach ($events as $e): ?>
                                    <option value="<?php echo (int)$e['id']; ?>"><?php echo htmlspecialchars($e['title'] . ' (' . date('d M Y', strtotime((string)$e['event_date'])) . ')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="text-sm text-gray-600 dark:text-gray-300">Description</label>
                            <textarea name="description" rows="5" class="w-full mt-1 border rounded-lg p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                        </div>

                        <button class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2.5 rounded-lg font-semibold">Save Activity</button>
                    </form>
                </div>

                <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow border dark:border-gray-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-gray-700 dark:text-gray-200">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="p-4 text-left font-semibold">Volunteer</th>
                                    <th class="p-4 text-left font-semibold">Activity</th>
                                    <th class="p-4 text-left font-semibold">Hours</th>
                                    <th class="p-4 text-left font-semibold">Event</th>
                                    <th class="p-4 text-right font-semibold">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y dark:divide-gray-700">
                                <?php foreach ($activities as $a): ?>
                                    <tr class="align-top">
                                        <td class="p-4">
                                            <div class="font-semibold dark:text-white"><?php echo htmlspecialchars($a['volunteer_name']); ?></div>
                                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars($a['volunteer_email']); ?></div>
                                            <div class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars(date('d M Y H:i', strtotime((string)$a['created_at']))); ?></div>
                                        </td>
                                        <td class="p-4">
                                            <div class="text-sm"><?php echo htmlspecialchars((string)($a['activity_type'] ?? '')); ?></div>
                                            <?php if (!empty($a['description'])): ?>
                                                <div class="text-xs text-gray-500 mt-1"><?php echo nl2br(htmlspecialchars((string)$a['description'])); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-4"><?php echo htmlspecialchars((string)($a['hours_spent'] ?? '')); ?></td>
                                        <td class="p-4 text-xs"><?php echo htmlspecialchars((string)($a['event_title'] ?? '-')); ?></td>
                                        <td class="p-4 text-right">
                                            <form action="actions/volunteer_activity_crud.php" method="POST" class="inline" onsubmit="return confirm('Delete this activity log?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo (int)$a['id']; ?>">
                                                <button class="text-red-600 hover:underline text-sm">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($activities) && !$error): ?>
                                    <tr><td class="p-8 text-center text-gray-500" colspan="5">No activities logged yet.</td></tr>
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

