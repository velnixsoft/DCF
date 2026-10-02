<?php
require 'includes/header.php';

$eventId = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
if ($eventId <= 0) {
    setFlash('error', 'Invalid event.');
    header('Location: events.php');
    exit;
}

$event = null;
$images = [];
$error = null;

try {
    $stmt = $pdo->prepare("SELECT id, title, event_date FROM events WHERE id = ? LIMIT 1");
    $stmt->execute([$eventId]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (Throwable $e) {
    $event = null;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM event_gallery WHERE event_id = ? ORDER BY created_at DESC");
    $stmt->execute([$eventId]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $error = 'Event gallery table is missing. Run DB migration: Database/upgrade_v3.sql';
    $images = [];
}
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900">
    <?php require 'includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-8">
            <div class="mb-6">
                <a href="events.php" class="text-sm font-semibold text-indigo-600 hover:underline">← Back to events</a>
            </div>

            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                <div>
                    <h3 class="text-2xl md:text-3xl font-medium text-gray-800 dark:text-white">Event Gallery</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        <?php if ($event): ?>
                            <?php echo htmlspecialchars((string)$event['title']); ?> • <?php echo htmlspecialchars(date('d M Y', strtotime((string)$event['event_date']))); ?>
                        <?php else: ?>
                            Upload and manage event photos.
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4 mb-6">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-1 bg-white dark:bg-gray-800 rounded-xl shadow border dark:border-gray-700 p-5">
                    <h4 class="font-semibold mb-4 dark:text-white">Upload Photos</h4>

                    <form action="actions/event_gallery_crud.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="action" value="upload">
                        <input type="hidden" name="event_id" value="<?php echo (int)$eventId; ?>">

                        <div>
                            <label class="text-sm text-gray-600 dark:text-gray-300">Select images (max 2MB each)</label>
                            <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple required class="w-full text-sm text-gray-500">
                        </div>

                        <button class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2.5 rounded-lg font-semibold">Upload</button>
                    </form>

                    <div class="mt-6 text-xs text-gray-500">
                        Public page shows this gallery on <span class="font-mono">event-details.php</span>.
                    </div>
                </div>

                <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow border dark:border-gray-700 p-5">
                    <h4 class="font-semibold mb-4 dark:text-white">Photos</h4>

                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                        <?php foreach ($images as $img): ?>
                            <div class="relative group border rounded-xl overflow-hidden bg-gray-50 dark:bg-gray-700/30">
                                <a href="../<?php echo htmlspecialchars((string)$img['image_path']); ?>" target="_blank" class="block aspect-square">
                                    <img src="../<?php echo htmlspecialchars((string)$img['image_path']); ?>" class="w-full h-full object-cover">
                                </a>
                                <form action="actions/event_gallery_crud.php" method="POST" onsubmit="return confirm('Delete this photo?');" class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo (int)$img['id']; ?>">
                                    <input type="hidden" name="event_id" value="<?php echo (int)$eventId; ?>">
                                    <button class="bg-red-600 text-white rounded-full w-7 h-7 flex items-center justify-center text-sm">×</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (empty($images) && !$error): ?>
                        <div class="text-center py-16 text-gray-500">No photos uploaded yet.</div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require 'includes/footer.php'; ?>

