<?php
require '../config/db.php';
require '../includes/functions.php';

if (!canAccessModule($pdo, 'manager', 'page.objectives_manager')) {
    setFlash('error', 'Access denied. Manager/Admin required.');
    header('Location: dashboard.php');
    exit;
}

function upsertSetting(PDO $pdo, string $key, string $value): void
{
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $stmt->execute([$key, $value]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$_POST['csrf_token'])) {
        setFlash('error', 'Security Token Error');
        header('Location: objectives_manager.php');
        exit;
    }

    $title = trim((string)($_POST['objectives_title'] ?? ''));
    $content = trim((string)($_POST['objectives_content'] ?? ''));

    if (mb_strlen($title) > 30) {
        setFlash('error', 'Title cannot exceed 30 characters.');
    } elseif (mb_strlen($content) > 4000) {
        setFlash('error', 'Content cannot exceed 4000 characters.');
    } else {
        upsertSetting($pdo, 'objectives_title', $title);
        upsertSetting($pdo, 'objectives_content', $content);

        if (!empty($_FILES['objectives_image']['name'])) {
            if ($_FILES['objectives_image']['size'] > 300 * 1024) {
                setFlash('error', 'Image size must be under 300KB.');
            } else {
                $targetDir = "../uploads/content/";
                if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

                $ext = strtolower(pathinfo($_FILES['objectives_image']['name'], PATHINFO_EXTENSION));
                $fileName = 'objectives_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . ($ext ?: 'jpg');
                if (move_uploaded_file($_FILES['objectives_image']['tmp_name'], $targetDir . $fileName)) {
                    upsertSetting($pdo, 'objectives_image', 'uploads/content/' . $fileName);
                }
            }
        }

        if (!isset($_SESSION['flash'])) setFlash('success', 'Objectives page updated successfully!');
    }

    header('Location: objectives_manager.php');
    exit;
}

$settings = [];
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('objectives_title','objectives_content','objectives_image')");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$csrfToken = generateCsrfToken();
require 'includes/header.php';
?>

<div class="flex h-screen overflow-hidden bg-gray-100 dark:bg-dark-bg">
    <?php require 'includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-6">
            <h3 class="text-3xl font-medium text-gray-700 dark:text-white mb-6">Manage Objectives Page</h3>

            <div class="bg-white dark:bg-dark-card rounded-lg shadow-lg p-6">
                <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-8"
                    x-data="{
                        title: '<?php echo htmlspecialchars($settings['objectives_title'] ?? 'Our Objectives'); ?>',
                        content: `<?php echo htmlspecialchars($settings['objectives_content'] ?? ''); ?>`
                    }">

                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-bold mb-2 dark:text-gray-300">Page Title</label>
                            <input type="text" name="objectives_title" x-model="title" maxlength="30"
                                class="w-full border p-3 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500">
                            <div class="text-right text-xs mt-1" :class="title.length > 30 ? 'text-red-500' : 'text-gray-400'">
                                <span x-text="title.length"></span> / 30
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-bold mb-2 dark:text-gray-300">Objectives Content</label>
                            <textarea name="objectives_content" rows="12" x-model="content" maxlength="4000"
                                class="w-full border p-3 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500"
                                placeholder="Write objectives, bullet points, and mission statements..."></textarea>
                            <div class="text-right text-xs mt-1" :class="content.length > 4000 ? 'text-red-500' : 'text-gray-400'">
                                <span x-text="content.length"></span> / 4000
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <label class="block text-sm font-bold mb-2 dark:text-gray-300">Featured Image (optional, < 300KB)</label>
                        <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 text-center">
                            <?php if (!empty($settings['objectives_image'])): ?>
                                <img src="../<?php echo htmlspecialchars($settings['objectives_image']); ?>" class="w-full h-auto max-h-80 object-cover rounded mb-4 shadow-sm">
                            <?php else: ?>
                                <div class="h-64 bg-gray-100 dark:bg-gray-800 flex items-center justify-center rounded mb-4">
                                    <span class="text-gray-400">No Image Uploaded</span>
                                </div>
                            <?php endif; ?>
                            <input type="file" name="objectives_image" accept="image/jpeg, image/png, image/webp" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                        </div>

                        <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-100 dark:border-blue-800">
                            <h5 class="font-bold text-blue-700 dark:text-blue-400 mb-2">Tips:</h5>
                            <ul class="text-sm text-gray-600 dark:text-gray-300 list-disc list-inside space-y-1">
                                <li>Use short paragraphs and bullet points for clarity.</li>
                                <li>Keep content focused on measurable objectives.</li>
                                <li>Upload a clean banner image for premium look.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="md:col-span-2 border-t pt-6 dark:border-gray-700 flex justify-end">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-lg shadow-lg transition transform hover:scale-105">
                            Update Content
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</div>

<?php require 'includes/footer.php'; ?>
