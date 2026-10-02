<?php require 'includes/header.php'; ?>
<?php require '../config/db.php'; ?>

<?php
if (!checkRole($pdo, 'manager')) {
    header('Location: dashboard.php');
    exit;
}

$rules = [];
$editRule = null;
try {
    $rules = $pdo->query("SELECT * FROM sa_point_rules WHERE deleted_at IS NULL ORDER BY category, rule_name")->fetchAll(PDO::FETCH_ASSOC);
    if (isset($_GET['edit']) && (int)$_GET['edit'] > 0) {
        $stmt = $pdo->prepare("SELECT * FROM sa_point_rules WHERE id = ? AND deleted_at IS NULL LIMIT 1");
        $stmt->execute([(int)$_GET['edit']]);
        $editRule = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
} catch (Throwable $e) {}
$csrfToken = generateCsrfToken();
$formRule = $editRule ?: [
    'id' => 0,
    'rule_code' => '',
    'rule_name' => '',
    'category' => 'reward',
    'trigger_key' => '',
    'entity_scope' => 'student',
    'verification_mode' => 'none',
    'base_points' => 0,
    'is_active' => 1,
    'notes' => '',
];
$categories = ['reward', 'multiplier', 'bonus', 'penalty'];
$scopes = ['student', 'task_submission', 'attendance', 'donation', 'referral', 'vendor_lead', 'program', 'manual', 'system'];
$modes = ['none', 'admin_review', 'evidence_required', 'system_verified'];
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900">
    <?php require 'includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col md:ml-64">
        <?php require 'includes/navbar.php'; ?>
        <main class="flex-1 overflow-y-auto p-4 md:p-6">
            <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                <div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Point Rules Engine</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Core scoring configuration for referrals, tasks, attendance, vendor leads, and penalties.</p>
                </div>
                <?php if ($editRule): ?>
                    <a href="student_point_rules.php" class="inline-flex items-center justify-center rounded-xl border border-gray-300 dark:border-gray-600 px-4 py-2.5 text-sm font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">Create New Rule</a>
                <?php endif; ?>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <section class="bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 p-5 xl:col-span-1">
                    <h4 class="text-lg font-bold text-gray-900 dark:text-white"><?php echo $editRule ? 'Edit Point Rule' : 'Create Point Rule'; ?></h4>
                    <p class="text-xs text-gray-500 mt-1">Use stable rule codes because the point engine resolves rules by `rule_code`.</p>

                    <form action="actions/point_rule_logic.php" method="POST" class="mt-5 space-y-4">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="action" value="<?php echo $editRule ? 'update' : 'create'; ?>">
                        <input type="hidden" name="id" value="<?php echo (int)$formRule['id']; ?>">

                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Rule Code *</label>
                            <input type="text" name="rule_code" required value="<?php echo htmlspecialchars((string)$formRule['rule_code']); ?>" placeholder="VENDOR_ONBOARDING"
                                class="w-full px-4 py-2.5 border rounded-xl text-sm font-mono uppercase outline-none focus:ring-2 focus:ring-emerald-500 bg-white text-gray-900 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Rule Name *</label>
                            <input type="text" name="rule_name" required value="<?php echo htmlspecialchars((string)$formRule['rule_name']); ?>" placeholder="Vendor Onboarding"
                                class="w-full px-4 py-2.5 border rounded-xl text-sm outline-none focus:ring-2 focus:ring-emerald-500 bg-white text-gray-900 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Category *</label>
                                <select name="category" class="w-full px-4 py-2.5 border rounded-xl text-sm outline-none focus:ring-2 focus:ring-emerald-500 bg-white text-gray-900 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category; ?>" <?php echo $formRule['category'] === $category ? 'selected' : ''; ?>><?php echo ucfirst($category); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Base Points *</label>
                                <input type="number" name="base_points" required value="<?php echo (int)$formRule['base_points']; ?>"
                                    class="w-full px-4 py-2.5 border rounded-xl text-sm outline-none focus:ring-2 focus:ring-emerald-500 bg-white text-gray-900 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Trigger Key *</label>
                            <input type="text" name="trigger_key" required value="<?php echo htmlspecialchars((string)$formRule['trigger_key']); ?>" placeholder="vendor_onboarding"
                                class="w-full px-4 py-2.5 border rounded-xl text-sm outline-none focus:ring-2 focus:ring-emerald-500 bg-white text-gray-900 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Entity Scope *</label>
                                <select name="entity_scope" class="w-full px-4 py-2.5 border rounded-xl text-sm outline-none focus:ring-2 focus:ring-emerald-500 bg-white text-gray-900 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <?php foreach ($scopes as $scope): ?>
                                        <option value="<?php echo $scope; ?>" <?php echo $formRule['entity_scope'] === $scope ? 'selected' : ''; ?>><?php echo ucfirst(str_replace('_', ' ', $scope)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Verification *</label>
                                <select name="verification_mode" class="w-full px-4 py-2.5 border rounded-xl text-sm outline-none focus:ring-2 focus:ring-emerald-500 bg-white text-gray-900 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <?php foreach ($modes as $mode): ?>
                                        <option value="<?php echo $mode; ?>" <?php echo $formRule['verification_mode'] === $mode ? 'selected' : ''; ?>><?php echo ucfirst(str_replace('_', ' ', $mode)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="inline-flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <input type="checkbox" name="is_active" value="1" <?php echo (int)$formRule['is_active'] === 1 ? 'checked' : ''; ?> class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                                Active Rule
                            </label>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                            <textarea name="notes" rows="4" class="w-full px-4 py-2.5 border rounded-xl text-sm outline-none focus:ring-2 focus:ring-emerald-500 bg-white text-gray-900 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400"><?php echo htmlspecialchars((string)$formRule['notes']); ?></textarea>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-3 pt-2">
                            <button type="submit" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 rounded-xl text-sm">
                                <?php echo $editRule ? 'Update Rule' : 'Create Rule'; ?>
                            </button>
                            <?php if ($editRule): ?>
                                <a href="student_point_rules.php" class="flex-1 text-center border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-bold py-3 rounded-xl text-sm hover:bg-gray-50 dark:hover:bg-gray-700">Cancel</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </section>

                <section class="xl:col-span-2 bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 overflow-hidden">
                    <div class="p-5 border-b dark:border-gray-700">
                        <h4 class="text-lg font-bold text-gray-900 dark:text-white">Existing Rules</h4>
                    </div>
                    <div class="divide-y dark:divide-gray-700 lg:hidden">
                        <?php foreach ($rules as $rule): ?>
                            <div class="p-4 space-y-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="font-bold"><?php echo htmlspecialchars($rule['rule_name']); ?></p>
                                        <p class="text-xs font-mono text-gray-500"><?php echo htmlspecialchars($rule['rule_code']); ?></p>
                                    </div>
                                    <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-200"><?php echo htmlspecialchars($rule['category']); ?></span>
                                </div>
                                <div class="grid grid-cols-2 gap-3 text-xs">
                                    <div>
                                        <p class="text-gray-400 uppercase font-semibold">Points</p>
                                        <p class="font-bold mt-1"><?php echo (int)$rule['base_points']; ?></p>
                                    </div>
                                    <div>
                                        <p class="text-gray-400 uppercase font-semibold">Active</p>
                                        <p class="font-bold mt-1"><?php echo (int)$rule['is_active'] === 1 ? 'Yes' : 'No'; ?></p>
                                    </div>
                                    <div class="col-span-2">
                                        <p class="text-gray-400 uppercase font-semibold">Trigger</p>
                                        <p class="text-xs mt-1 break-all"><?php echo htmlspecialchars($rule['trigger_key']); ?></p>
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <a href="student_point_rules.php?edit=<?php echo (int)$rule['id']; ?>" class="flex-1 min-w-[110px] text-center bg-amber-50 text-amber-700 px-3 py-2.5 rounded-xl text-sm font-bold">Edit</a>
                                    <form action="actions/point_rule_logic.php" method="POST" class="flex-1 min-w-[110px]">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                        <input type="hidden" name="id" value="<?php echo (int)$rule['id']; ?>">
                                        <button name="action" value="toggle" class="w-full bg-blue-50 text-blue-700 px-3 py-2.5 rounded-xl text-sm font-bold"><?php echo (int)$rule['is_active'] === 1 ? 'Disable' : 'Enable'; ?></button>
                                    </form>
                                    <form action="actions/point_rule_logic.php" method="POST" class="flex-1 min-w-[110px]" onsubmit="return confirm('Delete this point rule?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                        <input type="hidden" name="id" value="<?php echo (int)$rule['id']; ?>">
                                        <button name="action" value="delete" class="w-full bg-red-50 text-red-700 px-3 py-2.5 rounded-xl text-sm font-bold">Delete</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($rules)): ?><div class="p-8 text-center text-gray-400">No rules found. Run seed_student_module_missing_tables.sql</div><?php endif; ?>
                    </div>
                    <div class="hidden lg:block overflow-x-auto">
                        <table class="w-full text-sm text-gray-600 dark:text-gray-300">
                            <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase font-bold text-gray-700 dark:text-gray-300">
                                <tr>
                                    <th class="p-4 text-left">Rule</th>
                                    <th class="p-4">Category</th>
                                    <th class="p-4">Points</th>
                                    <th class="p-4">Trigger</th>
                                    <th class="p-4">Scope</th>
                                    <th class="p-4">Active</th>
                                    <th class="p-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y dark:divide-gray-700 text-gray-800 dark:text-gray-300">
                                <?php foreach ($rules as $rule): ?>
                                    <tr>
                                        <td class="p-4">
                                            <p class="font-bold text-gray-900 dark:text-white"><?php echo htmlspecialchars($rule['rule_name']); ?></p>
                                            <p class="text-xs font-mono text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($rule['rule_code']); ?></p>
                                        </td>
                                        <td class="p-4"><span class="px-2 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-200"><?php echo htmlspecialchars($rule['category']); ?></span></td>
                                        <td class="p-4 font-bold"><?php echo (int)$rule['base_points']; ?></td>
                                        <td class="p-4 text-xs"><?php echo htmlspecialchars($rule['trigger_key']); ?></td>
                                        <td class="p-4 text-xs"><?php echo htmlspecialchars((string)$rule['entity_scope']); ?></td>
                                        <td class="p-4"><?php echo (int)$rule['is_active'] === 1 ? 'Yes' : 'No'; ?></td>
                                        <td class="p-4 text-right">
                                            <div class="inline-flex items-center gap-3">
                                                <a href="student_point_rules.php?edit=<?php echo (int)$rule['id']; ?>" class="text-xs font-bold text-amber-600">Edit</a>
                                                <form action="actions/point_rule_logic.php" method="POST" class="inline">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                    <input type="hidden" name="id" value="<?php echo (int)$rule['id']; ?>">
                                                    <button name="action" value="toggle" class="text-xs font-bold text-blue-600"><?php echo (int)$rule['is_active'] === 1 ? 'Disable' : 'Enable'; ?></button>
                                                </form>
                                                <form action="actions/point_rule_logic.php" method="POST" class="inline" onsubmit="return confirm('Delete this point rule?');">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                    <input type="hidden" name="id" value="<?php echo (int)$rule['id']; ?>">
                                                    <button name="action" value="delete" class="text-xs font-bold text-red-600">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($rules)): ?><tr><td colspan="7" class="p-8 text-center text-gray-400">No rules found. Run seed_student_module_missing_tables.sql</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </main>
    </div>
</div>
<?php require 'includes/footer.php'; ?>
