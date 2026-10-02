<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$campaign = null;
$error = null;

if ($id <= 0) {
    $error = 'Invalid campaign.';
} else {
    try {
        $stmt = $pdo->prepare("SELECT * FROM crowdfunding_campaigns WHERE id = ? AND status IN ('Active','Completed','Paused') LIMIT 1");
        $stmt->execute([$id]);
        $campaign = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$campaign) $error = 'Campaign not found.';
    } catch (Throwable $e) {
        $error = 'Crowdfunding module is not installed. Ask admin to run DB migration (Database/upgrade_v2.sql).';
    }
}
?>

<div class="bg-white min-h-screen mt-6">
    <div class="container mx-auto px-6 py-10 max-w-4xl">
        <a href="crowdfunding.php" class="text-indigo-600 hover:underline text-sm">← Back to Crowdfunding</a>

        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4 mt-6">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php else: ?>
            <?php
            $goal = (float)($campaign['goal_amount'] ?? 0);
            $raised = (float)($campaign['raised_amount'] ?? 0);
            $pct = $goal > 0 ? min(100, max(0, ($raised / $goal) * 100)) : 0;
            ?>
            <h1 class="mt-4 text-3xl md:text-4xl font-extrabold text-gray-900"><?php echo htmlspecialchars((string)$campaign['title']); ?></h1>
            <div class="mt-2 flex items-center gap-2">
                <span class="text-sm text-gray-500">Status:</span>
                <?php
                $status = $campaign['status'] ?? 'Active';
                $badgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                if ($status === 'Completed') {
                    $badgeClass = 'bg-blue-50 text-blue-700 border-blue-200';
                } elseif ($status === 'Paused') {
                    $badgeClass = 'bg-amber-50 text-amber-700 border-amber-200';
                }
                ?>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold border <?php echo $badgeClass; ?>">
                    <?php echo htmlspecialchars((string)$status); ?>
                </span>
            </div>

            <?php if (!empty($campaign['image_path'])): ?>
                <div class="mt-6 rounded-2xl overflow-hidden border border-gray-100 bg-gray-50">
                    <img src="<?php echo htmlspecialchars((string)$campaign['image_path']); ?>" class="w-full h-auto object-cover">
                </div>
            <?php endif; ?>

            <div class="mt-6 bg-white rounded-2xl border border-gray-100 shadow p-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <p class="text-xs text-gray-500">Raised</p>
                        <p class="text-2xl font-bold text-emerald-600">₹<?php echo number_format($raised, 2); ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Goal</p>
                        <p class="text-2xl font-bold text-gray-900">₹<?php echo number_format($goal, 2); ?></p>
                    </div>
                    <div class="md:text-right">
                        <p class="text-xs text-gray-500">Funding</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo (int)round($pct); ?>%</p>
                    </div>
                </div>

                <div class="mt-4 w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                    <div class="bg-emerald-600 h-2" style="width: <?php echo (int)round($pct); ?>%"></div>
                </div>

                <div class="mt-6 text-gray-800">
                    <?php echo nl2br(htmlspecialchars((string)($campaign['description'] ?? ''))); ?>
                </div>

                <?php if (($campaign['status'] ?? 'Active') === 'Active'): ?>
                    <div class="mt-8 bg-amber-50 border border-amber-100 rounded-xl p-4">
                        <p class="text-sm text-gray-700"><b>Donate to this campaign:</b> Use our standard donation page and mention the campaign name in notes (campaign-specific payments can be integrated later).</p>
                        <a href="../donate.php" class="inline-block mt-3 bg-amber-500 hover:bg-amber-600 text-white font-bold px-5 py-2.5 rounded-lg transition-transform active:scale-95">Donate Now</a>
                    </div>
                <?php else: ?>
                    <div class="mt-8 bg-gray-50 border border-gray-200 rounded-xl p-5 text-center">
                        <svg class="mx-auto h-8 w-8 text-gray-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        <p class="text-sm text-gray-600 font-semibold">Donations are closed</p>
                        <p class="text-xs text-gray-500 mt-1">This campaign is currently <b><?php echo htmlspecialchars(strtolower((string)$campaign['status'])); ?></b> and is no longer accepting contributions.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
