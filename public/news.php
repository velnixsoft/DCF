<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

$posts = [];
$error = null;

try {
    $posts = $pdo->query("SELECT title, slug, cover_path, content, published_at, created_at FROM posts WHERE status = 'Published' ORDER BY COALESCE(published_at, created_at) DESC LIMIT 30")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $error = 'News module is not installed. Ask admin to run DB migration (Database/upgrade_v3.sql).';
    $posts = [];
}
?>

<div class="bg-white min-h-screen mt-6">
    <div class="bg-gray-50 py-14 border-b border-gray-100">
        <div class="container mx-auto px-6 text-center">
            <h1 class="text-4xl font-extrabold text-gray-900">News & Updates</h1>
            <p class="mt-3 text-gray-600 max-w-2xl mx-auto">Latest announcements, meeting/event updates, and NGO activities.</p>
        </div>
    </div>

    <div class="container mx-auto px-6 py-12">
        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($posts as $p): ?>
                <a href="../news-details.php?slug=<?php echo urlencode((string)$p['slug']); ?>" class="bg-white rounded-2xl border border-gray-100 shadow hover:shadow-xl transition overflow-hidden group">
                    <?php if (!empty($p['cover_path'])): ?>
                        <div class="aspect-[16/9] bg-gray-100 overflow-hidden">
                            <img src="../<?php echo htmlspecialchars((string)$p['cover_path']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition">
                        </div>
                    <?php else: ?>
                        <div class="aspect-[16/9] bg-gradient-to-br from-emerald-50 to-amber-50"></div>
                    <?php endif; ?>

                    <div class="p-5">
                        <p class="text-xs text-gray-500">
                            <?php
                            $dt = $p['published_at'] ?: $p['created_at'];
                            echo $dt ? htmlspecialchars(date('d M Y', strtotime((string)$dt))) : '';
                            ?>
                        </p>
                        <h3 class="mt-2 text-lg font-bold text-gray-900 line-clamp-2"><?php echo htmlspecialchars((string)$p['title']); ?></h3>
                        <?php
                        $excerpt = trim((string)($p['content'] ?? ''));
                        if (strlen($excerpt) > 160) $excerpt = substr($excerpt, 0, 160) . '...';
                        ?>
                        <p class="mt-3 text-sm text-gray-600 line-clamp-3"><?php echo htmlspecialchars($excerpt); ?></p>
                        <p class="mt-4 text-sm font-semibold text-emerald-700">Read more →</p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($posts) && !$error): ?>
            <div class="text-center py-16 text-gray-500">No news published yet.</div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

