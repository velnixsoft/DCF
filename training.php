<?php
require 'includes/header.php';

$videos = [];

function getYouTubeId($url) {
    preg_match('/(youtu\.be\/|v=)([^&]+)/', $url, $matches);
    return $matches[2] ?? null;
}

try {
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'admin_training_videos_json' LIMIT 1");
    $stmt->execute();
    $raw = (string)($stmt->fetchColumn() ?: '[]');
    $decoded = json_decode($raw, true);
    $videos = is_array($decoded) ? $decoded : [];
} catch (Throwable $e) {
    $videos = [];
}
?>

<div class="min-h-screen pt-28 pb-16 bg-gray-50 mt-6">
    <div class="container mx-auto px-6 max-w-7xl">

        <!-- Header -->
        <div class="mb-10">
            <h1 class="text-4xl font-extrabold text-gray-900">Training Videos</h1>
            <p class="text-gray-600 mt-3 max-w-2xl">
                Learn faster with curated training content designed for admins and staff.
            </p>
        </div>

        <?php if (!empty($videos)): ?>

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">

                <?php foreach ($videos as $v): ?>
                    <?php
                    $title = (string)($v['title'] ?? 'Training Video');
                    $url = (string)($v['url'] ?? '');
                    $ytId = getYouTubeId($url);

                    $thumbnail = $ytId 
                        ? "https://img.youtube.com/vi/$ytId/hqdefault.jpg"
                        : "https://via.placeholder.com/600x350?text=Video";
                    ?>

                    <a href="<?php echo htmlspecialchars($url); ?>" target="_blank"
                       class="group block">

                        <div class="relative rounded-xl overflow-hidden bg-gray-200">

                            <!-- Thumbnail -->
                            <img src="<?php echo $thumbnail; ?>"
                                 class="w-full h-48 object-cover group-hover:scale-105 transition duration-300">

                            <!-- Overlay -->
                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
                                <div class="bg-white/90 p-3 rounded-full shadow-lg">
                                    ▶
                                </div>
                            </div>

                        </div>

                        <!-- Title -->
                        <div class="mt-3">
                            <h3 class="text-sm font-semibold text-gray-900 line-clamp-2 group-hover:text-indigo-600">
                                <?php echo htmlspecialchars($title); ?>
                            </h3>
                        </div>

                    </a>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="text-center text-gray-500 py-20">
                <p class="text-lg">No training videos available yet.</p>
            </div>

        <?php endif; ?>

    </div>
</div>

<?php require 'includes/footer.php'; ?>