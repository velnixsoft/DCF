<?php
require 'includes/header.php';

$keys = ['awards_title', 'awards_content', 'awards_image'];
$content = [];
try {
    $in = implode(',', array_fill(0, count($keys), '?'));
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ($in)");
    $stmt->execute($keys);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $content[$row['setting_key']] = $row['setting_value'];
    }
} catch (Throwable $e) {
}

$title = $content['awards_title'] ?? 'Awards & Recognition';
$body = $content['awards_content'] ?? '';
$image = $content['awards_image'] ?? '';
?>

<div class="min-h-screen pt-28 pb-14 bg-gray-50 mt-6">
    <div class="container mx-auto px-6 max-w-5xl">
        <div class="bg-white rounded-2xl shadow border border-gray-100 overflow-hidden">
            <?php if ($image): ?>
                <div class="h-56 md:h-72 bg-gray-100 overflow-hidden">
                    <img src="<?php echo htmlspecialchars($image); ?>" alt="Awards" class="w-full h-full object-cover">
                </div>
            <?php endif; ?>

            <div class="p-8 md:p-10">
                <h1 class="text-4xl font-extrabold text-gray-900"><?php echo htmlspecialchars($title); ?></h1>
                <p class="text-gray-600 mt-3">Recognitions, milestones, and achievements that reflect our impact.</p>

                <div class="mt-8 prose max-w-none">
                    <?php if (trim($body) !== ''): ?>
                        <div class="text-gray-800 leading-relaxed whitespace-pre-wrap"><?php echo nl2br(htmlspecialchars($body)); ?></div>
                    <?php else: ?>
                        <div class="text-gray-600">Awards and recognitions will be published soon.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require 'includes/footer.php'; ?>

