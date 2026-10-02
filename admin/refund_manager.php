<?php
require '../config/db.php';
require '../includes/functions.php';

if (!canAccessModule($pdo, 'manager', 'page.refund_manager')) {
    setFlash('error', 'Access denied.');
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
        $content = $_POST['refund_policy_content'] ?? '';
        $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('refund_policy_content', ?) ON DUPLICATE KEY UPDATE setting_value = ?")->execute([$content, $content]);
        setFlash('success', 'Refund Policy updated successfully!');
    } else {
        setFlash('error', 'Invalid security token.');
    }
    header('Location: refund_manager.php');
    exit;
}

$content = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'refund_policy_content'")->fetchColumn();

require 'includes/header.php';
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900">
    <?php require 'includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-8">
            <h3 class="text-3xl font-bold text-gray-800 dark:text-white mb-6">Manage Refund Policy</h3>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-100 dark:border-gray-700">
                    <form method="POST" class="flex flex-col h-full">
                        <div class="p-6 border-b dark:border-gray-700">
                            <h4 class="text-lg font-bold text-gray-800 dark:text-white">Content Editor</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">You can use HTML tags like `h2`, `p`, `ul`, `li`, `strong` for formatting.</p>
                        </div>

                        <div class="p-6 flex-1">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <textarea name="refund_policy_content" rows="20" class="w-full border p-4 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-blue-500 font-mono text-sm"><?php echo htmlspecialchars($content ?? ''); ?></textarea>
                        </div>

                        <div class="p-4 bg-gray-50 dark:bg-gray-800/50 border-t dark:border-gray-700 flex justify-end">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded-lg shadow-lg">
                                Save Policy
                            </button>
                        </div>
                    </form>
                </div>

                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md border border-gray-100 dark:border-gray-700">
                        <h4 class="text-lg font-bold text-gray-800 dark:text-white mb-3">Important Note</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                            Clearly state the conditions under which a donation might be refunded, if at all. For non-profits, it's common to state that donations are non-refundable.
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<?php require 'includes/footer.php'; ?>
