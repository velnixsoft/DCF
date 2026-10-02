<?php
require '../config/db.php';
require '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        setFlash('error', 'Security Token Invalid');
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'add_media') {
            $type = $_POST['type'];
            $title = cleanInput($_POST['title']);
            $path = '';

            if ($type === 'image' && !empty($_FILES['image']['name'])) {
                if ($_FILES['image']['size'] > 2 * 1024 * 1024) {
                    setFlash('error', 'Image size must be under 2MB.');
                } elseif (!in_array($_FILES['image']['type'], ['image/jpeg', 'image/png', 'image/webp'])) {
                    setFlash('error', 'Only JPG, PNG, WEBP images are allowed.');
                } else {
                    $targetDir = "../uploads/gallery/";
                    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
                    $fileName = time() . '_' . uniqid() . '_' . basename($_FILES['image']['name']);
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $targetDir . $fileName)) {
                        $path = 'uploads/gallery/' . $fileName;
                    }
                }
            } elseif ($type === 'video') {
                $path = cleanInput($_POST['video_link']);
            }

            if ($path) {
                $pdo->prepare("INSERT INTO gallery (title, type, file_path) VALUES (?, ?, ?)")->execute([$title, $type, $path]);
                setFlash('success', 'Media added successfully!');
            } elseif (!isset($_SESSION['flash'])) {
                setFlash('error', 'Failed to add media. Please check input.');
            }
        }

        if ($action === 'delete') {
            $id = $_POST['id'];
            $stmt = $pdo->prepare("SELECT file_path, type FROM gallery WHERE id=?");
            $stmt->execute([$id]);
            $media = $stmt->fetch();

            if ($media && $media['type'] == 'image' && file_exists("../" . $media['file_path'])) {
                unlink("../" . $media['file_path']);
            }
            $pdo->prepare("DELETE FROM gallery WHERE id=?")->execute([$id]);
            setFlash('success', 'Media item removed.');
        }
    }
    header('Location: gallery_manager.php');
    exit;
}

$mediaItems = $pdo->query("SELECT * FROM gallery ORDER BY id DESC")->fetchAll();

require 'includes/header.php';
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900"
    x-data="{ isModalOpen: false, isDeleteModalOpen: false, targetId: null, type: 'image' }">

    <?php require 'includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-8">

            <div class="flex justify-between items-center mb-8">
                <div>
                    <h3 class="text-3xl font-bold text-gray-800 dark:text-white">Gallery</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage photos and videos</p>
                </div>
                <button @click="isModalOpen = true; type = 'image'; if(window.$refs && $refs.addMediaForm) $refs.addMediaForm.reset(); else { const f = document.getElementById('addMediaForm'); if(f) f.reset(); }" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg shadow-lg flex items-center gap-2 text-sm font-medium">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span class="hidden sm:inline">Add Media</span>
                </button>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 md:gap-6">
                <?php if (count($mediaItems) > 0): foreach ($mediaItems as $m): ?>
                        <div class="group relative bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 border border-gray-100 dark:border-gray-700 overflow-hidden flex flex-col">

                            <button @click="targetId = <?php echo $m['id']; ?>; isDeleteModalOpen = true" class="absolute top-2 right-2 p-1.5 bg-red-50 text-red-500 rounded-full opacity-0 group-hover:opacity-100 transition z-20">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>

                            <a href="<?php echo ($m['type'] == 'image') ? '../' . $m['file_path'] : $m['file_path']; ?>" target="_blank" class="block aspect-square bg-gray-50 dark:bg-gray-700/50 relative">
                                <?php if ($m['type'] == 'image'): ?>
                                    <img src="../<?php echo $m['file_path']; ?>" class="w-full h-full object-cover transition duration-500 transform group-hover:scale-105">
                                <?php else: ?>
                                    <?php
                                    $video_id = '';
                                    if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $m['file_path'], $match)) $video_id = $match[1];
                                    $thumbnail_url = $video_id ? "https://img.youtube.com/vi/{$video_id}/mqdefault.jpg" : "https://placehold.co/400x300?text=Invalid";
                                    ?>
                                    <img src="<?php echo $thumbnail_url; ?>" class="w-full h-full object-cover">
                                    <div class="absolute inset-0 flex items-center justify-center bg-black/30">
                                        <div class="w-12 h-12 bg-red-600 rounded-full flex items-center justify-center text-white"><i class="fas fa-play"></i></div>
                                    </div>
                                <?php endif; ?>
                            </a>

                            <div class="p-3 text-center border-t dark:border-gray-700">
                                <h5 class="font-bold text-gray-800 dark:text-white text-sm truncate"><?php echo htmlspecialchars($m['title']); ?></h5>
                            </div>
                        </div>
                    <?php endforeach;
                else: ?>
                    <div class="col-span-full py-16 flex flex-col items-center text-center bg-white dark:bg-gray-800 rounded-xl border-2 border-dashed dark:border-gray-700">
                        <p>Gallery is Empty. Add your first photo or video.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div x-show="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-cloak>
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg" @click.away="isModalOpen = false">
                    <div class="p-6 border-b dark:border-gray-700 flex justify-between items-center">
                        <h3 class="text-lg font-bold dark:text-white">Add New Media</h3><button @click="isModalOpen = false" class="text-gray-400">&times;</button>
                    </div>
                    <form method="POST" id="addMediaForm" x-ref="addMediaForm" enctype="multipart/form-data" class="p-6 space-y-5">
                        <input type="hidden" name="action" value="add_media">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <div><label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Title *</label><input type="text" name="title" required class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700"></div>
                        <div><label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Media Type</label><select name="type" x-model="type" class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700">
                                <option value="image">Image</option>
                                <option value="video">Video URL</option>
                            </select></div>
                        <div x-show="type === 'image'" x-transition>
                            <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Upload Image File (Max 2MB)*</label>
                            <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:bg-blue-50 file:text-blue-700">
                        </div>
                        <div x-show="type === 'video'" x-transition>
                            <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">YouTube Video Link *</label>
                            <input type="url" name="video_link" placeholder="https://www.youtube.com/watch?v=..." class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700">
                        </div>
                        <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg">Save Media</button>
                    </form>
                </div>
            </div>

            <div x-show="isDeleteModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-cloak>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-sm text-center p-6" @click.away="isDeleteModalOpen = false">
                    <h3 class="text-lg font-bold dark:text-white">Confirm Deletion</h3>
                    <p class="text-sm text-gray-500 mt-2">Are you sure? This cannot be undone.</p>
                    <div class="mt-6 flex gap-3">
                        <button @click="isDeleteModalOpen = false" class="flex-1 rounded-lg border dark:border-gray-600 py-2">Cancel</button>
                        <form action="gallery_manager.php" method="POST" class="flex-1">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" :value="targetId">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <button class="w-full rounded-lg bg-red-600 py-2 text-white hover:bg-red-700">Confirm</button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<?php require 'includes/footer.php'; ?>