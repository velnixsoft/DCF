<?php
require '../config/db.php';
require '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        setFlash('error', 'Security Token Error');
    } else {

        if (strlen($_POST['about_title']) > 15) {
            setFlash('error', 'Page Title cannot exceed 15 characters.');
        } elseif (strlen($_POST['about_desc']) > 5000) {
            setFlash('error', 'Main Description cannot exceed 700 characters.');
        } elseif (strlen($_POST['about_mission']) > 2000) {
            setFlash('error', 'Mission text cannot exceed 500 characters.');
        } elseif (strlen($_POST['about_vision']) > 2000) {
            setFlash('error', 'Vision text cannot exceed 500 characters.');
        } else {

            $fields = ['about_title', 'about_desc', 'about_mission', 'about_vision'];
            foreach ($fields as $key) {
                $val = $_POST[$key] ?? '';
                $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?")->execute([$val, $key]);
            }

            if (!empty($_FILES['about_image']['name'])) {
                if ($_FILES['about_image']['size'] > 200 * 1024) {
                    setFlash('error', 'Image size must be under 200KB.');
                } else {
                    $targetDir = "../uploads/content/";
                    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

                    $fileName = 'about_us_' . time() . '.jpg';
                    if (move_uploaded_file($_FILES['about_image']['tmp_name'], $targetDir . $fileName)) {
                        $path = 'uploads/content/' . $fileName;
                        $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'about_image'")->execute([$path]);
                    }
                }
            }
            if (!isset($_SESSION['flash'])) setFlash('success', 'About Us page updated successfully!');
        }
    }
    header('Location: about_manager.php');
    exit;
}

$settings = [];
$stmt = $pdo->query("SELECT * FROM settings WHERE setting_key LIKE 'about_%'");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

require 'includes/header.php';
?>

<div class="flex h-screen overflow-hidden bg-gray-100 dark:bg-dark-bg">
    <?php require 'includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-6">
            <h3 class="text-3xl font-medium text-gray-700 dark:text-white mb-6">Manage About Us Page</h3>

            <div class="bg-white dark:bg-dark-card rounded-lg shadow-lg p-6">
                <form method="POST" enctype="multipart/form-data"
                    class="grid grid-cols-1 md:grid-cols-2 gap-8"
                    x-data="{
                          title: '<?php echo htmlspecialchars($settings['about_title'] ?? ''); ?>',
                          desc: `<?php echo htmlspecialchars($settings['about_desc'] ?? ''); ?>`,
                          mission: `<?php echo htmlspecialchars($settings['about_mission'] ?? ''); ?>`,
                          vision: `<?php echo htmlspecialchars($settings['about_vision'] ?? ''); ?>`
                      }">

                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-bold mb-2 dark:text-gray-300">Page Title</label>
                            <input type="text" name="about_title" x-model="title" maxlength="15"
                                class="w-full border p-3 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500">
                            <div class="text-right text-xs mt-1" :class="title.length > 15 ? 'text-red-500' : 'text-gray-400'">
                                <span x-text="title.length"></span> / 15
                            </div>
                        </div>

                       <div>
    <label class="block text-sm font-bold mb-2 dark:text-gray-300">
        Main Description
    </label>

    <textarea 
        name="about_desc"
        rows="10"
        x-model="desc"
        maxlength="5000"
        class="w-full border p-3 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500"></textarea>

    <div class="text-right text-xs mt-1"
         :class="desc.length > 5000 ? 'text-red-500' : 'text-gray-400'">

        <span x-text="desc.length"></span> / 5000

    </div>

    <p class="text-xs text-gray-500 mt-2">
        You can use HTML tags like:
        &lt;b&gt;Bold&lt;/b&gt;,
        &lt;i&gt;Italic&lt;/i&gt;,
        &lt;ul&gt;&lt;li&gt;List Item&lt;/li&gt;&lt;/ul&gt;
    </p>
</div>

                        <div>
                            <label class="block text-sm font-bold mb-2 dark:text-gray-300">Our Mission</label>
                            <textarea name="about_mission" rows="3" x-model="mission" maxlength="2000"
                                class="w-full border p-3 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500"></textarea>
                            <div class="text-right text-xs mt-1" :class="mission.length > 2000 ? 'text-red-500' : 'text-gray-400'">
                                <span x-text="mission.length"></span> / 2000
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-bold mb-2 dark:text-gray-300">Our Vision</label>
                            <textarea name="about_vision" rows="3" x-model="vision" maxlength="2000"
                                class="w-full border p-3 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500"></textarea>
                            <div class="text-right text-xs mt-1" :class="vision.length > 2000 ? 'text-red-500' : 'text-gray-400'">
                                <span x-text="vision.length"></span> / 2000
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <label class="block text-sm font-bold mb-2 dark:text-gray-300">Featured Image (800x600, < 200KB)</label>

                                <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 text-center">
                                    <?php if (!empty($settings['about_image'])): ?>
                                        <img src="../<?php echo $settings['about_image']; ?>" class="w-full h-auto max-h-80 object-contain rounded mb-4 shadow-sm">
                                    <?php else: ?>
                                        <div class="h-64 bg-gray-100 dark:bg-gray-800 flex items-center justify-center rounded mb-4">
                                            <span class="text-gray-400">No Image Uploaded</span>
                                        </div>
                                    <?php endif; ?>

                                    <input type="file" name="about_image" accept="image/jpeg, image/png" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                                </div>

                                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-100 dark:border-blue-800">
                                    <h5 class="font-bold text-blue-700 dark:text-blue-400 mb-2">Tips:</h5>
                                    <ul class="text-sm text-gray-600 dark:text-gray-300 list-disc list-inside space-y-1">
                                        <li>Use high-quality images (800x600px recommended).</li>
                                        <li>Keep file size under 200KB for fast loading.</li>
                                        <li>Ensure content is clear and inspiring.</li>
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