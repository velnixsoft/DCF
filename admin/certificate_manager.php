<?php
require '../config/db.php';
require '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $title = cleanInput($_POST['title']);
        if (!empty($_FILES['cert_image']['name'])) {
            $targetDir = "../uploads/certificates/";
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

            $fileName = time() . '_' . basename($_FILES['cert_image']['name']);
            if (move_uploaded_file($_FILES['cert_image']['tmp_name'], $targetDir . $fileName)) {
                $dbPath = 'uploads/certificates/' . $fileName;
                $pdo->prepare("INSERT INTO certificates (title, image_path) VALUES (?, ?)")->execute([$title, $dbPath]);
                setFlash('success', 'Certificate uploaded successfully!');
            }
        }
    }

    if ($action === 'delete') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("SELECT image_path FROM certificates WHERE id=?");
        $stmt->execute([$id]);
        $path = $stmt->fetchColumn();
        if ($path && file_exists("../" . $path)) unlink("../" . $path);

        $pdo->prepare("DELETE FROM certificates WHERE id=?")->execute([$id]);
        setFlash('success', 'Certificate removed.');
    }

    header('Location: certificate_manager.php');
    exit;
}

$certificates = $pdo->query("SELECT * FROM certificates ORDER BY id DESC")->fetchAll();

require 'includes/header.php';
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900"
    x-data="{ isModalOpen: false, isDeleteModalOpen: false, targetId: null }">

    <?php require 'includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-8">

            <div class="flex justify-between items-center mb-8">
                <div>
                    <h3 class="text-3xl font-bold text-gray-800 dark:text-white">Certificates</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage legal documents and awards</p>
                </div>
                <button @click="isModalOpen = true" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg shadow-lg flex items-center gap-2 text-sm font-medium">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span class="hidden sm:inline">Upload Certificate</span>
                </button>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 md:gap-6">
                <?php foreach ($certificates as $c): ?>
                    <div class="group relative bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 border border-gray-100 dark:border-gray-700 overflow-hidden flex flex-col">

                        <button @click="targetId = <?php echo $c['id']; ?>; isDeleteModalOpen = true" class="absolute top-2 right-2 p-1.5 bg-red-50 text-red-500 rounded-full opacity-0 group-hover:opacity-100 transition z-20">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>

                        <a href="../<?php echo $c['image_path']; ?>" target="_blank" class="block aspect-[3/4] p-3 bg-gray-50 dark:bg-gray-700/50">
                            <img src="../<?php echo $c['image_path']; ?>" alt="<?php echo htmlspecialchars($c['title']); ?>" class="w-full h-full object-contain transition duration-300 transform group-hover:scale-105">
                        </a>

                        <div class="p-3 text-center border-t dark:border-gray-700">
                            <h5 class="font-bold text-gray-800 dark:text-white text-sm truncate"><?php echo htmlspecialchars($c['title']); ?></h5>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div x-show="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-cloak>
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg" @click.away="isModalOpen = false">
                    <div class="p-6 border-b dark:border-gray-700 flex justify-between">
                        <h3 class="text-lg font-bold dark:text-white">Upload New Certificate</h3>
                        <button @click="isModalOpen = false" class="text-gray-400">&times;</button>
                    </div>
                    <form method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                        <input type="hidden" name="action" value="add">
                        <div>
                            <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Certificate Title *</label>
                            <input type="text" name="title" required class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Image File *</label>
                            <input type="file" name="cert_image" required accept="image/*" class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:bg-blue-50 file:text-blue-700">
                        </div>
                        <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg">Upload</button>
                    </form>
                </div>
            </div>

            <div x-show="isDeleteModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-cloak>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-sm text-center p-6" @click.away="isDeleteModalOpen = false">
                    <h3 class="text-lg font-bold dark:text-white">Confirm Deletion</h3>
                    <p class="text-sm text-gray-500 mt-2">Are you sure? This cannot be undone.</p>
                    <div class="mt-6 flex gap-3">
                        <button @click="isDeleteModalOpen = false" class="flex-1 rounded-lg border dark:border-gray-600 py-2">Cancel</button>
                        <form action="certificate_manager.php" method="POST" class="flex-1">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" :value="targetId">
                            <button class="w-full rounded-lg bg-red-600 py-2 text-white">Confirm</button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<?php require 'includes/footer.php'; ?>