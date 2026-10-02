<?php
require '../config/db.php';
require '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        setFlash('error', 'Security Token Invalid');
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'add_sponsor') {
            $name = cleanInput($_POST['name']);
            if (trim(strtolower($name)) === 'zappile tech' || trim(strtolower($name)) === 'zappile technology') {
                $name = 'Zapilee Technology';
            }
            $url = cleanInput($_POST['website_url']);
            $priority = (int)$_POST['priority'];

            if (!empty($_FILES['logo']['name'])) {
                $targetDir = "../uploads/sponsors/";
                if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
                $fileName = time() . '_' . basename($_FILES['logo']['name']);

                if (move_uploaded_file($_FILES['logo']['tmp_name'], $targetDir . $fileName)) {
                    $dbPath = 'uploads/sponsors/' . $fileName;
                    $stmt = $pdo->prepare("INSERT INTO sponsors (name, logo_path, website_url, priority) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$name, $dbPath, $url, $priority]);
                    setFlash('success', 'Sponsor added successfully!');
                } else {
                    setFlash('error', 'Failed to upload logo.');
                }
            } else {
                setFlash('error', 'Please upload a logo.');
            }
        }

        if ($action === 'delete') {
            $id = $_POST['id'];
            $stmt = $pdo->prepare("SELECT logo_path FROM sponsors WHERE id=?");
            $stmt->execute([$id]);
            $logo = $stmt->fetchColumn();

            if ($logo && file_exists("../" . $logo)) unlink("../" . $logo);

            $pdo->prepare("DELETE FROM sponsors WHERE id=?")->execute([$id]);
            setFlash('success', 'Sponsor removed.');
        }
    }
    header('Location: sponsor_manager.php');
    exit;
}

$sponsors = $pdo->query("SELECT * FROM sponsors ORDER BY priority ASC, created_at DESC")->fetchAll();

require 'includes/header.php';
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900"
    x-data="{ isModalOpen: <?php echo (isset($_GET['action']) && $_GET['action'] === 'add') ? 'true' : 'false'; ?>, isDeleteModalOpen: false, targetId: null }">

    <?php require 'includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-8">

            <div class="flex justify-between items-center mb-8">
                <div>
                    <h3 class="text-3xl font-bold text-gray-800 dark:text-white">Our Partners</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage sponsors and collaborations</p>
                </div>
                <button @click="isModalOpen = true" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg shadow-lg flex items-center gap-2 text-sm font-medium">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span class="hidden sm:inline">Add Partner</span>
                </button>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 md:gap-6">
                <?php if (count($sponsors) > 0): foreach ($sponsors as $s): ?>
                        <div class="group relative bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md transition-all duration-300 border border-gray-100 dark:border-gray-700 overflow-hidden flex flex-col">

                            <button @click="targetId = <?php echo $s['id']; ?>; isDeleteModalOpen = true" class="absolute top-2 right-2 p-1.5 bg-red-50 text-red-500 rounded-full opacity-0 group-hover:opacity-100 transition duration-200 hover:bg-red-100 z-20">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>

                            <div class="aspect-video p-6 flex items-center justify-center bg-gray-50 dark:bg-gray-700/50">
                                <img src="../<?php echo $s['logo_path']; ?>" class="max-w-full max-h-full object-contain transform transition duration-500 group-hover:scale-110" alt="<?php echo htmlspecialchars($s['name']); ?>">
                            </div>

                            <div class="p-3 text-center border-t dark:border-gray-700">
                                <h5 class="font-bold text-gray-800 dark:text-white text-sm truncate"><?php echo htmlspecialchars($s['name']); ?></h5>
                            </div>
                        </div>
                    <?php endforeach;
                else: ?>
                    <div class="col-span-full py-16 text-center text-gray-500 dark:text-gray-400">No partners added yet.</div>
                <?php endif; ?>
            </div>

            <div x-show="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-cloak>
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md" @click.away="isModalOpen = false">
                    <div class="p-6 border-b dark:border-gray-700">
                        <h3 class="text-lg font-bold dark:text-white">Add New Partner</h3>
                    </div>
                    <form method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                        <input type="hidden" name="action" value="add_sponsor">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <div><label class="text-sm">Company Name *</label><input type="text" name="name" required class="w-full border p-2 rounded mt-1 dark:bg-gray-700"></div>
                        <div><label class="text-sm">Website URL</label><input type="url" name="website_url" class="w-full border p-2 rounded mt-1 dark:bg-gray-700"></div>
                        <div><label class="text-sm">Logo *</label><input type="file" name="logo" required accept="image/*" class="w-full text-sm mt-1"></div>
                        <div><label class="text-sm">Priority</label><input type="number" name="priority" value="0" class="w-full border p-2 rounded mt-1 dark:bg-gray-700"></div>
                        <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg">Save Partner</button>
                    </form>
                </div>
            </div>

            <div x-show="isDeleteModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-cloak>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-sm text-center p-6" @click.away="isDeleteModalOpen = false">
                    <h3 class="text-lg font-bold dark:text-white">Confirm Deletion</h3>
                    <p class="text-sm text-gray-500 mt-2">Are you sure? This cannot be undone.</p>
                    <div class="mt-6 flex gap-3">
                        <button @click="isDeleteModalOpen = false" class="flex-1 rounded-lg border dark:border-gray-600 py-2">Cancel</button>
                        <form action="sponsor_manager.php" method="POST" class="flex-1">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" :value="targetId">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <button class="w-full rounded-lg bg-red-600 py-2 text-white">Confirm</button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<?php require 'includes/footer.php'; ?>