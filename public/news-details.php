<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

$slug = trim((string)($_GET['slug'] ?? ''));
$post = null;
$error = null;

if ($slug === '') {
    $error = 'Invalid post.';
} else {
    try {
        $stmt = $pdo->prepare("SELECT * FROM posts WHERE slug = ? AND status = 'Published' LIMIT 1");
        $stmt->execute([$slug]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$post) $error = 'Post not found.';
    } catch (Throwable $e) {
        $error = 'News module is not installed. Ask admin to run DB migration (Database/upgrade_v3.sql).';
    }
}
?>

<div class="bg-white min-h-screen">
    <div class="container mx-auto px-6 py-10 max-w-3xl">
        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <div class="mt-6">
                <a href="../news.php" class="text-indigo-600 hover:underline">← Back to News</a>
            </div>
        <?php else: ?>
            <a href="../news.php" class="text-indigo-600 hover:underline text-sm">← Back to News</a>

            <h1 class="mt-4 text-3xl md:text-4xl font-extrabold text-gray-900"><?php echo htmlspecialchars((string)$post['title']); ?></h1>
            <p class="mt-2 text-sm text-gray-500">
                <?php
                $dt = $post['published_at'] ?: $post['created_at'];
                echo $dt ? htmlspecialchars(date('d M Y', strtotime((string)$dt))) : '';
                ?>
            </p>

            <?php if (!empty($post['cover_path'])): ?>
                <div class="mt-6 rounded-2xl overflow-hidden border border-gray-100 bg-gray-50">
                    <img src="../<?php echo htmlspecialchars((string)$post['cover_path']); ?>" class="w-full h-auto object-cover">
                </div>
            <?php endif; ?>

            <div class="prose max-w-none mt-8 text-gray-800">
                <?php echo nl2br(htmlspecialchars((string)($post['content'] ?? ''))); ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

