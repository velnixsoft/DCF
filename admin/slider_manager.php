<?php
require '../config/db.php';
require '../includes/functions.php';
require 'includes/header.php';

$slides = $pdo->query("SELECT * FROM sliders ORDER BY priority ASC, id DESC")->fetchAll();
$active_count = $pdo->query("SELECT COUNT(*) FROM sliders WHERE is_active = 1")->fetchColumn();
$limit_reached = $active_count >= 5;
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900"
    x-data="{ isDeleteModalOpen: false, targetId: null }">
    <?php require 'includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-8">
            <h3 class="text-3xl font-bold dark:text-white mb-6">Home Page Slider</h3>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <div class="lg:col-span-1 bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md border dark:border-gray-700 h-fit">
                    <h4 class="text-lg font-bold mb-4 dark:text-white border-b pb-2 dark:border-gray-700">Add New Slide</h4>

                    <?php if ($limit_reached): ?>
                        <div class="bg-yellow-50 text-yellow-800 p-4 rounded-lg border border-yellow-200 text-sm">
                            <strong>Limit Reached!</strong> You cannot add new slides as 5 are already active. Please deactivate one first.
                        </div>
                    <?php else: ?>
                        <form method="POST" action="actions/slider_logic.php" enctype="multipart/form-data" class="space-y-4">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="action" value="add">
                            <div><label class="text-sm dark:text-gray-300">Title*</label><input type="text" name="title" required class="w-full border p-2 rounded mt-1 dark:bg-gray-700 dark:text-white"></div>
                            <div><label class="text-sm dark:text-gray-300">Subtitle</label><input type="text" name="subtitle" class="w-full border p-2 rounded mt-1 dark:bg-gray-700 dark:text-white"></div>
                            <div><label class="text-sm dark:text-gray-300">Image (1920x800px)*</label><input type="file" name="image" required class="w-full text-sm mt-1 dark:text-gray-400"></div>
                            <button type="submit" class="w-full bg-blue-600 text-white font-medium py-2.5 rounded-lg hover:bg-blue-700">Upload Slide</button>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="lg:col-span-2 space-y-4">
                    <?php foreach ($slides as $s): ?>
                        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border dark:border-gray-700 flex flex-col sm:flex-row items-center gap-4">
                            <img src="../<?php echo $s['image_path']; ?>" class="w-full sm:w-40 h-24 object-cover rounded-md border dark:border-gray-600">
                            <div class="flex-1 text-center sm:text-left">
                                <h5 class="font-bold dark:text-white"><?php echo htmlspecialchars($s['title']); ?></h5>
                                <p class="text-sm text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($s['subtitle']); ?></p>
                            </div>
                            <div class="flex items-center gap-3 w-full sm:w-auto justify-center">

                                <form action="actions/slider_logic.php" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                                    <button class="px-3 py-1 text-xs font-bold rounded-full border <?php echo $s['is_active'] ? 'bg-green-100 text-green-700 border-green-200' : 'bg-gray-100 text-gray-600 border-gray-200'; ?>">
                                        <?php echo $s['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </button>
                                </form>

                                <button @click="targetId = <?php echo $s['id']; ?>; isDeleteModalOpen = true" class="text-red-500 hover:text-red-700 p-2 rounded-full hover:bg-red-50">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div x-show="isDeleteModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-cloak>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-sm text-center p-6" @click.away="isDeleteModalOpen = false">
                    <h3 class="text-lg font-bold dark:text-white">Confirm Deletion</h3>
                    <p class="text-sm text-gray-500 mt-2">Are you sure you want to delete this slide? This cannot be undone.</p>
                    <div class="mt-6 flex gap-3">
                        <button @click="isDeleteModalOpen = false" class="flex-1 rounded-lg border dark:border-gray-600 py-2">Cancel</button>
                        <form action="actions/slider_logic.php" method="POST" class="flex-1">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" :value="targetId">
                            <button class="w-full rounded-lg bg-red-600 py-2 text-white hover:bg-red-700">Confirm</button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<?php require 'includes/footer.php'; ?>