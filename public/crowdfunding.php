<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

$campaigns = [];
$error = null;

try {
    $campaigns = $pdo->query("SELECT * FROM crowdfunding_campaigns WHERE status IN ('Active','Completed') ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $error = 'Crowdfunding module is not installed. Ask admin to run DB migration (Database/upgrade_v2.sql).';
    $campaigns = [];
}
?>

<div class="bg-white min-h-screen mt-6">
    <div class="bg-emerald-50  py-14 border-b border-emerald-100">
        <div class="container mx-auto px-6 text-center">
            <h1 class="text-4xl font-extrabold text-emerald-900">Crowdfunding</h1>
            <p class="mt-3 text-gray-700 max-w-2xl mx-auto">Support specific causes through our active crowdfunding campaigns.</p>
        </div>
    </div>

    <div class="container mx-auto px-6 py-12">
        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($campaigns as $c): ?>
                <?php
                $goal = (float)($c['goal_amount'] ?? 0);
                $raised = (float)($c['raised_amount'] ?? 0);
                $pct = $goal > 0 ? min(100, max(0, ($raised / $goal) * 100)) : 0;
                ?>
                <a href="campaign.php?id=<?php echo (int)$c['id']; ?>" class="bg-white rounded-2xl border border-gray-100 shadow hover:shadow-xl transition overflow-hidden group">
                    <?php if (!empty($c['image_path'])): ?>
                        <div class="aspect-[16/9] bg-gray-100 overflow-hidden">
                            <img src="<?php echo htmlspecialchars((string)$c['image_path']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition">
                        </div>
                    <?php else: ?>
                        <div class="aspect-[16/9] bg-gradient-to-br from-emerald-50 to-amber-50"></div>
                    <?php endif; ?>
                    <div class="p-5">
                        <h3 class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars((string)$c['title']); ?></h3>
                        <p class="mt-2 text-sm text-gray-600 line-clamp-3"><?php echo htmlspecialchars(substr((string)($c['description'] ?? ''), 0, 160)); ?></p>

                        <div class="mt-4">
                            <div class="flex justify-between text-xs text-gray-500 mb-1">
                                <span>Raised: ₹<?php echo number_format($raised, 2); ?></span>
                                <span>Goal: ₹<?php echo number_format($goal, 2); ?></span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                                <div class="bg-emerald-600 h-2" style="width: <?php echo (int)round($pct); ?>%"></div>
                            </div>
                            <div class="text-xs text-gray-500 mt-2"><?php echo (int)round($pct); ?>% funded</div>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($campaigns) && !$error): ?>
            <div class="text-center py-16 text-gray-500">No campaigns available right now.</div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
