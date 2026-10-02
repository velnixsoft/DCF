<?php
require '../config/db.php';
require '../includes/functions.php';

if (!canAccessModule($pdo, 'manager', 'page.privacy_manager')) {
    setFlash('error', 'Access denied.');
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {

        $policyContent = $_POST['privacy_policy_content'] ?? '';

        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('privacy_policy_content', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$policyContent, $policyContent]);

        setFlash('success', 'Privacy Policy updated successfully!');
    } else {
        setFlash('error', 'Invalid security token.');
    }
    header('Location: privacy_manager.php');
    exit;
}

$policyContent = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'privacy_policy_content'")->fetchColumn();

require 'includes/header.php';
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900">
    <?php require 'includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-8">
            <h3 class="text-3xl font-bold text-gray-800 dark:text-white mb-6">Manage Privacy Policy</h3>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-100 dark:border-gray-700">
                    <form method="POST" class="flex flex-col h-full">
                        <div class="p-6 border-b dark:border-gray-700">
                            <h4 class="text-lg font-bold text-gray-800 dark:text-white">Policy Content Editor</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">You can use HTML tags like `h2`, `p`, `ul`, `li`, `strong` for formatting.</p>
                        </div>

                        <div class="p-6 flex-1">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <textarea name="privacy_policy_content" rows="20" class="w-full border border-gray-300 dark:border-gray-600 p-4 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-blue-500 outline-none transition font-mono text-sm"><?php echo htmlspecialchars($policyContent ?? ''); ?></textarea>
                        </div>

                        <div class="p-4 bg-gray-50 dark:bg-gray-800/50 border-t dark:border-gray-700 flex justify-end">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded-lg shadow-lg transition transform active:scale-95">
                                Save Policy
                            </button>
                        </div>
                    </form>
                </div>

                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md border border-gray-100 dark:border-gray-700">
                        <h4 class="text-lg font-bold text-gray-800 dark:text-white mb-3">Need Help?</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                            If you don't have a privacy policy, you can use a free online generator to create one. Copy the generated HTML content and paste it into the editor.
                        </p>
                        <a href="https://www.privacypolicygenerator.info/" target="_blank" class="w-full inline-block text-center bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 font-medium py-3 px-4 rounded-lg border border-green-200 dark:border-green-800/50 hover:bg-green-200 transition">
                            <i class="fas fa-external-link-alt mr-2"></i> Free Policy Generator
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<?php require 'includes/footer.php'; ?>
