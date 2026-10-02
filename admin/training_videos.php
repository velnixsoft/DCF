<?php
require 'includes/header.php';

$videos = [];
$error = null;

try {
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'admin_training_videos_json' LIMIT 1");
    $stmt->execute();
    $raw = (string)($stmt->fetchColumn() ?: '[]');
    $decoded = json_decode($raw, true);
    $videos = is_array($decoded) ? $decoded : [];
} catch (Throwable $e) {
    $error = 'Training videos setting is missing. Run DB migration: Database/upgrade_v3.sql';
    $videos = [];
}
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900">
    <?php require 'includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                <div>
                    <h3 class="text-2xl md:text-3xl font-medium text-gray-800 dark:text-white">Admin Training Videos</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Store helpful video links for admin/staff training.</p>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4 mb-6">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-1 bg-white dark:bg-gray-800 rounded-xl shadow border dark:border-gray-700 p-5">
                    <h4 class="font-semibold mb-4 dark:text-white">Add Video</h4>

                    <form action="actions/training_videos_save.php" method="POST" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="action" value="add">
                        <div>
                            <label class="text-sm text-gray-600 dark:text-gray-300">Title *</label>
                            <input type="text" name="title" required pattern="[a-zA-Z\s]+" title="Only letters and spaces are allowed." class="w-full mt-1 border rounded-lg p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="e.g. How to verify donations">
                        </div>
                        <div>
                            <label class="text-sm text-gray-600 dark:text-gray-300">Video URL *</label>
                            <input type="url" name="url" required class="w-full mt-1 border rounded-lg p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="https://...">
                        </div>
                        <button class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2.5 rounded-lg font-semibold">Add Video</button>
                    </form>

                    <div class="mt-6">
                        <h4 class="font-semibold mb-2 dark:text-white">Tip</h4>
                        <p class="text-xs text-gray-500">You can paste YouTube, Google Drive, or any training video link.</p>
                    </div>
                </div>

                <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow border dark:border-gray-700 overflow-hidden">
                    <div class="p-4 border-b dark:border-gray-700">
                        <h4 class="font-semibold dark:text-white">Videos</h4>
                    </div>
                    <div class="divide-y dark:divide-gray-700">
                        <?php foreach ($videos as $idx => $v): ?>
                            <?php
                            $title = (string)($v['title'] ?? '');
                            $url = (string)($v['url'] ?? '');
                            ?>
                            <div class="p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                                <div>
                                    <p class="font-semibold dark:text-white"><?php echo htmlspecialchars($title !== '' ? $title : ('Video #' . ($idx + 1))); ?></p>
                                    <a href="<?php echo htmlspecialchars($url); ?>" target="_blank" class="text-sm text-indigo-600 hover:underline break-all"><?php echo htmlspecialchars($url); ?></a>
                                </div>
                                <form action="actions/training_videos_save.php" method="POST" onsubmit="return confirm('Remove this video?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="index" value="<?php echo (int)$idx; ?>">
                                    <button class="text-red-600 hover:underline text-sm">Remove</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($videos) && !$error): ?>
                            <div class="p-10 text-center text-gray-500">No training videos added yet.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require 'includes/footer.php'; ?>

