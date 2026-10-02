<?php
require 'includes/header.php';

$campaigns = [];
$error = null;
$editCampaign = null;

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$where = [];
$params = [];
if ($search !== '') {
    $where[] = "(title LIKE :search OR description LIKE :search)";
    $params[':search'] = "%$search%";
}
if ($statusFilter !== '') {
    $where[] = "status = :status";
    $params[':status'] = $statusFilter;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;
$totalCampaigns = 0;

try {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM crowdfunding_campaigns $whereSql");
    foreach ($params as $k => $v) $countStmt->bindValue($k, $v);
    $countStmt->execute();
    $totalCampaigns = (int)$countStmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT * FROM crowdfunding_campaigns $whereSql ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $error = 'Crowdfunding module database table is missing. Run DB migration: Database/upgrade_v2.sql';
}

if (isset($_GET['edit']) && (int)$_GET['edit'] > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM crowdfunding_campaigns WHERE id = ? LIMIT 1");
        $stmt->execute([(int)$_GET['edit']]);
        $editCampaign = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) {
        $editCampaign = null;
    }
}
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900" x-data="{ isDeleteModalOpen: false, deleteId: null }">
    <?php require 'includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                <div>
                    <h3 class="text-2xl md:text-3xl font-medium text-gray-800 dark:text-white">Crowdfunding Campaigns</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Create campaigns and show them publicly on the website.</p>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4 mb-6">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-1 bg-white dark:bg-gray-800 rounded-xl shadow border dark:border-gray-700 p-5">
                    <h4 class="font-semibold mb-4 dark:text-white"><?php echo $editCampaign ? 'Edit Campaign' : 'Create Campaign'; ?></h4>

                    <form action="actions/crowdfunding_crud.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="action" value="<?php echo $editCampaign ? 'update' : 'create'; ?>">
                        <input type="hidden" name="id" value="<?php echo (int)($editCampaign['id'] ?? 0); ?>">

                        <div>
                            <label class="text-sm text-gray-600 dark:text-gray-300">Title *</label>
                            <input type="text" name="title" required value="<?php echo htmlspecialchars($editCampaign['title'] ?? ''); ?>" class="w-full mt-1 border rounded-lg p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>

                        <div>
                            <label class="text-sm text-gray-600 dark:text-gray-300">Goal Amount (INR) *</label>
                            <input type="number" step="0.01" min="0" name="goal_amount" required value="<?php echo htmlspecialchars((string)($editCampaign['goal_amount'] ?? '')); ?>" class="w-full mt-1 border rounded-lg p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>

                        <div>
                            <label class="text-sm text-gray-600 dark:text-gray-300">Raised Amount (INR)</label>
                            <input type="number" step="0.01" min="0" name="raised_amount" value="<?php echo htmlspecialchars((string)($editCampaign['raised_amount'] ?? '0')); ?>" class="w-full mt-1 border rounded-lg p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <p class="text-xs text-gray-500 mt-1">If you later integrate payment, this can be auto-calculated.</p>
                        </div>

                        <div>
                            <label class="text-sm text-gray-600 dark:text-gray-300">Status</label>
                            <select name="status" class="w-full mt-1 border rounded-lg p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <?php
                                $currentStatus = $editCampaign['status'] ?? 'Active';
                                foreach (['Active', 'Completed', 'Paused'] as $st):
                                ?>
                                    <option value="<?php echo $st; ?>" <?php echo $currentStatus === $st ? 'selected' : ''; ?>><?php echo $st; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="text-sm text-gray-600 dark:text-gray-300">Image (optional, max 2MB)</label>
                            <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="w-full text-sm text-gray-500">
                            <?php if (!empty($editCampaign['image_path'])): ?>
                                <a class="text-xs text-indigo-600 hover:underline" href="../<?php echo htmlspecialchars($editCampaign['image_path']); ?>" target="_blank">View current image</a>
                            <?php endif; ?>
                        </div>

                        <div>
                            <label class="text-sm text-gray-600 dark:text-gray-300">Description</label>
                            <textarea name="description" rows="7" class="w-full mt-1 border rounded-lg p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"><?php echo htmlspecialchars($editCampaign['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="flex gap-2">
                            <button class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2.5 rounded-lg font-semibold">
                                <?php echo $editCampaign ? 'Update' : 'Create'; ?>
                            </button>
                            <?php if ($editCampaign): ?>
                                <a href="crowdfunding.php" class="flex-1 text-center border border-gray-300 dark:border-gray-600 py-2.5 rounded-lg dark:text-white">Cancel</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <div class="lg:col-span-2 space-y-4">
                    <!-- Search & Filters -->
                    <form method="GET" class="flex flex-wrap gap-3 items-center">
                        <div class="relative flex-1 min-w-[200px]">
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search campaign title, description..."
                                   class="w-full pl-9 pr-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-blue-500">
                            <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                        </div>
                        <select name="status" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 text-xs text-gray-700 dark:text-gray-200">
                            <option value="">All Statuses</option>
                            <option value="Active" <?php echo $statusFilter === 'Active' ? 'selected' : ''; ?>>Active</option>
                            <option value="Completed" <?php echo $statusFilter === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="Paused" <?php echo $statusFilter === 'Paused' ? 'selected' : ''; ?>>Paused</option>
                        </select>
                        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition">
                            Filter
                        </button>
                        <?php if ($search !== '' || $statusFilter !== ''): ?>
                            <a href="crowdfunding.php" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 text-gray-700 dark:text-gray-300 text-xs font-bold rounded-xl transition">
                                Reset
                            </a>
                        <?php endif; ?>
                    </form>

                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow border dark:border-gray-700 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-gray-700 dark:text-gray-200">
                                <thead class="bg-gray-50 dark:bg-gray-700/50">
                                    <tr>
                                        <th class="p-4 text-left font-semibold">Campaign</th>
                                        <th class="p-4 text-left font-semibold">Progress</th>
                                        <th class="p-4 text-left font-semibold">Status</th>
                                        <th class="p-4 text-right font-semibold">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y dark:divide-gray-700">
                                    <?php foreach ($campaigns as $c): ?>
                                        <?php
                                        $goal = (float)($c['goal_amount'] ?? 0);
                                        $raised = (float)($c['raised_amount'] ?? 0);
                                        $pct = $goal > 0 ? min(100, max(0, ($raised / $goal) * 100)) : 0;
                                        ?>
                                        <tr class="align-top">
                                            <td class="p-4">
                                                <div class="font-semibold dark:text-white"><?php echo htmlspecialchars($c['title']); ?></div>
                                                <div class="text-xs text-gray-500 mt-1">Goal: ₹<?php echo number_format($goal, 2); ?> • Raised: ₹<?php echo number_format($raised, 2); ?></div>
                                                <div class="text-xs mt-1">
                                                    <a class="text-blue-600 hover:underline" href="../campaign.php?id=<?php echo (int)$c['id']; ?>" target="_blank">View Public</a>
                                                </div>
                                            </td>
                                            <td class="p-4">
                                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden">
                                                    <div class="bg-emerald-600 h-2.5" style="width: <?php echo (int)round($pct); ?>%"></div>
                                                </div>
                                                <div class="text-xs text-gray-500 mt-2"><?php echo (int)round($pct); ?>%</div>
                                            </td>
                                            <td class="p-4">
                                                <span class="px-2 py-1 rounded-full text-xs <?php echo $c['status'] === 'Active' ? 'bg-green-100 text-green-700' : ($c['status'] === 'Paused' ? 'bg-amber-100 text-amber-700' : 'bg-gray-200 text-gray-700'); ?>">
                                                    <?php echo htmlspecialchars($c['status']); ?>
                                                </span>
                                            </td>
                                            <td class="p-4 text-right">
                                                <a class="text-indigo-600 hover:underline text-sm mr-3" href="crowdfunding.php?edit=<?php echo (int)$c['id']; ?>">Edit</a>
                                                <button type="button" @click="deleteId = <?php echo (int)$c['id']; ?>; isDeleteModalOpen = true;" class="text-red-600 hover:underline text-sm">Delete</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($campaigns) && !$error): ?>
                                        <tr><td class="p-8 text-center text-gray-500" colspan="4">No campaigns found.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php echo render_admin_pagination($totalCampaigns, $page, $perPage, ['search' => $search, 'status' => $statusFilter]); ?>
                    </div>
                </div>
            </div>
            <!-- Delete Confirmation Modal -->
            <div x-show="isDeleteModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" x-cloak>
                <div class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-3xl p-6 w-full max-w-sm text-center">
                    <div class="w-12 h-12 bg-red-50 dark:bg-red-950/30 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fa-solid fa-triangle-exclamation text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Delete Campaign?</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Are you sure you want to permanently delete this crowdfunding campaign? This action cannot be undone.</p>
                    <div class="mt-6 flex flex-col sm:flex-row gap-2">
                        <button @click="isDeleteModalOpen = false" class="flex-1 border dark:border-gray-600 bg-white dark:bg-gray-700 py-2 rounded-xl text-sm font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600">Cancel</button>
                        <form action="actions/crowdfunding_crud.php" method="POST" class="flex-1">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" :value="deleteId">
                            <button type="submit" class="w-full bg-red-600 text-white py-2 rounded-xl text-sm font-bold hover:bg-red-700 transition">Confirm Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require 'includes/footer.php'; ?>

