<?php
require 'includes/header.php';

$keys = ['objectives_title', 'objectives_content', 'objectives_image'];
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

$title = $content['objectives_title'] ?? 'Our Objectives';
$body = $content['objectives_content'] ?? '';
$image = $content['objectives_image'] ?? '';
?>

<div class="min-h-screen pt-28 pb-14 bg-[#FFF8F1]/40">
    <div class="container mx-auto px-6 max-w-5xl">
        <div class="bg-white rounded-[18px] shadow-xl border border-gray-100 overflow-hidden">
            <?php if ($image): ?>
                <div class="h-56 md:h-72 bg-gray-100 overflow-hidden">
                    <img src="<?php echo htmlspecialchars($image); ?>" alt="Objectives" class="w-full h-full object-cover">
                </div>
            <?php endif; ?>

            <div class="p-8 md:p-10">
                <h1 class="text-4xl font-extrabold text-[#0F8B8D]"><?php echo htmlspecialchars($title); ?></h1>
                <p class="text-[#4B5563] mt-3">What we aim to achieve through our programs and community work.</p>

                <div class="mt-8 prose max-w-none">
                    <?php if (trim($body) !== ''): ?>
                        <div class="text-[#1F2937] leading-relaxed whitespace-pre-wrap"><?php echo nl2br(htmlspecialchars($body)); ?></div>
                    <?php else: ?>
                        <div class="text-[#4B5563]">Objectives will be published soon.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require 'includes/footer.php'; ?>

