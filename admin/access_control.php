<?php
require '../config/db.php';
require '../includes/functions.php';

if (!canAccessModule($pdo, 'admin', 'page.access_control')) {
    setFlash('error', 'Access denied.');
    header('Location: dashboard.php');
    exit;
}

$csrfToken = generateCsrfToken();
$catalog = getAccessModuleCatalog();
$hasPermissions = dbTableExists($pdo, 'user_permissions');

$users = [];
try {
    $users = $pdo->query("SELECT * FROM users ORDER BY FIELD(hierarchy_level, 'admin', 'manager', 'coordinator'), name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $users = [];
}

$selectedUserId = (int)($_GET['user'] ?? ($_SESSION['user_id'] ?? 0));
if ($selectedUserId <= 0 && !empty($users)) {
    $selectedUserId = (int)$users[0]['id'];
}

$selectedUser = null;
foreach ($users as $user) {
    if ((int)$user['id'] === $selectedUserId) {
        $selectedUser = $user;
        break;
    }
}
if ($selectedUser === null && !empty($users)) {
    $selectedUser = $users[0];
    $selectedUserId = (int)$selectedUser['id'];
}

$selectedPermissions = [];
if ($hasPermissions && $selectedUserId > 0) {
    try {
        $stmt = $pdo->prepare("SELECT permission_key FROM user_permissions WHERE user_id = ? AND is_allowed = 1");
        $stmt->execute([$selectedUserId]);
        $selectedPermissions = array_fill_keys($stmt->fetchAll(PDO::FETCH_COLUMN), true);
    } catch (Throwable $e) {
        $selectedPermissions = [];
    }
}

$groupedCatalog = [];
foreach ($catalog as $module) {
    $groupedCatalog[$module['group']][] = $module;
}

$userPermissionCounts = [];
if ($hasPermissions) {
    try {
        $rows = $pdo->query("SELECT user_id, COUNT(*) AS permission_count FROM user_permissions WHERE is_allowed = 1 GROUP BY user_id")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $userPermissionCounts[(int)$row['user_id']] = (int)$row['permission_count'];
        }
    } catch (Throwable $e) {
        $userPermissionCounts = [];
    }
}

require 'includes/header.php';
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900">
    <?php require 'includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                <div>
                    <h3 class="text-2xl md:text-3xl font-medium text-gray-800 dark:text-white">Access Control</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Grant page access, manage blocked users, and reset credentials from one place.</p>
                </div>
                <div class="flex gap-2">
                    <a href="settings.php" class="bg-slate-700 hover:bg-slate-800 text-white px-4 py-2 rounded-lg text-sm">Settings</a>
                    <a href="document_studio.php" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm">Document Studio</a>
                </div>
            </div>

            <?php if (!$hasPermissions): ?>
                <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 text-amber-800 p-4">
                    The <code>user_permissions</code> table is missing. Run the access-control migration so the checkbox matrix can store module access.
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <div class="space-y-6">
                    <div class="bg-white dark:bg-gray-800 rounded-xl border dark:border-gray-700 shadow-sm p-5">
                        <div class="flex items-center justify-between gap-3 mb-4">
                            <div>
                                <h4 class="font-semibold text-gray-800 dark:text-white">Create New User</h4>
                                <p class="text-xs text-gray-500 mt-1">Add a staff account and assign its base role.</p>
                            </div>
                            <span class="px-2 py-1 rounded-full text-[11px] font-semibold bg-blue-100 text-blue-700">Super Admin</span>
                        </div>

                        <form action="actions/access_control_logic.php" method="POST" class="space-y-3">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                            <input type="hidden" name="action" value="create_user">
                            <div>
                                <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Full Name</label>
                                <input type="text" name="name" required class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Email</label>
                                <input type="email" name="email" required class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Role</label>
                                    <select name="role" class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        <option value="Volunteer">Volunteer</option>
                                        <option value="CSR Manager">CSR Manager</option>
                                        <option value="Volunteer Manager">Volunteer Manager</option>
                                        <option value="Accountant">Accountant</option>
                                        <option value="Admin">Admin</option>
                                        <option value="Super Admin">Super Admin</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Status</label>
                                    <select name="status" class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        <option value="1">Active</option>
                                        <option value="0">Blocked</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Password</label>
                                <input type="password" name="password" required minlength="6" class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Confirm Password</label>
                                <input type="password" name="confirm_password" required minlength="6" class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            </div>
                            <button class="w-full bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg text-sm font-medium">Create User</button>
                        </form>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-xl border dark:border-gray-700 shadow-sm overflow-hidden">
                    <div class="p-4 border-b dark:border-gray-700">
                        <h4 class="font-semibold text-gray-800 dark:text-white">Users</h4>
                        <p class="text-xs text-gray-500 mt-1">Select a user to manage their access profile.</p>
                    </div>
                    <div class="divide-y dark:divide-gray-700 max-h-[70vh] overflow-y-auto">
                        <?php foreach ($users as $user): ?>
                            <?php
                            $isSelected = (int)$user['id'] === $selectedUserId;
                            $permissionCount = $userPermissionCounts[(int)$user['id']] ?? 0;
                            $status = (int)($user['status'] ?? 0) === 1 ? 'Active' : 'Blocked';
                            ?>
                            <a href="access_control.php?user=<?php echo (int)$user['id']; ?>" class="block p-4 transition <?php echo $isSelected ? 'bg-blue-50 dark:bg-blue-900/20' : 'hover:bg-gray-50 dark:hover:bg-gray-700/50'; ?>">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($user['name'] ?: 'Unnamed User'); ?></p>
                                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($user['email'] ?: '-'); ?></p>
                                        <p class="text-xs text-gray-400 mt-1"><?php echo htmlspecialchars($user['role'] ?: '-'); ?> · <?php echo htmlspecialchars($user['hierarchy_level'] ?: '-'); ?></p>
                                    </div>
                                    <span class="px-2 py-1 rounded-full text-[11px] font-semibold <?php echo $status === 'Active' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'; ?>">
                                        <?php echo $status; ?>
                                    </span>
                                </div>
                                <p class="text-[11px] text-gray-500 mt-2">
                                    <?php echo $permissionCount; ?> module permission<?php echo $permissionCount === 1 ? '' : 's'; ?>
                                </p>
                            </a>
                        <?php endforeach; ?>
                        <?php if (empty($users)): ?>
                            <div class="p-6 text-center text-gray-500">No users found.</div>
                        <?php endif; ?>
                    </div>
                    </div>
                </div>

                <div class="xl:col-span-2 space-y-6">
                    <div class="bg-white dark:bg-gray-800 rounded-xl border dark:border-gray-700 shadow-sm p-5">
                        <div class="flex items-center justify-between gap-3 mb-5">
                            <div>
                                <h4 class="font-semibold text-gray-800 dark:text-white">Selected User</h4>
                                <p class="text-xs text-gray-500">Update the account profile and lifecycle controls.</p>
                            </div>
                            <?php if ($selectedUser): ?>
                                <span class="text-xs font-mono text-gray-500">ID <?php echo (int)$selectedUser['id']; ?></span>
                            <?php endif; ?>
                        </div>

                        <?php if ($selectedUser): ?>
                            <form action="actions/access_control_logic.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <input type="hidden" name="action" value="update_user">
                                <input type="hidden" name="user_id" value="<?php echo (int)$selectedUser['id']; ?>">

                                <div>
                                    <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Name</label>
                                    <input type="text" name="name" value="<?php echo htmlspecialchars((string)($selectedUser['name'] ?? '')); ?>" class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Email</label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars((string)($selectedUser['email'] ?? '')); ?>" class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Role</label>
                                    <select name="role" class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        <?php
                                        $roles = ['Super Admin', 'Admin', 'Accountant', 'Volunteer Manager', 'CSR Manager', 'Volunteer'];
                                        $currentRole = (string)($selectedUser['role'] ?? 'Volunteer');
                                        foreach ($roles as $role):
                                        ?>
                                            <option value="<?php echo htmlspecialchars($role); ?>" <?php echo $currentRole === $role ? 'selected' : ''; ?>><?php echo htmlspecialchars($role); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Status</label>
                                    <select name="status" class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        <option value="1" <?php echo ((int)($selectedUser['status'] ?? 1) === 1) ? 'selected' : ''; ?>>Active</option>
                                        <option value="0" <?php echo ((int)($selectedUser['status'] ?? 1) === 0) ? 'selected' : ''; ?>>Blocked</option>
                                    </select>
                                </div>
                                <div class="md:col-span-2 flex justify-end">
                                    <button class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg text-sm font-medium">Save Profile</button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="text-sm text-gray-500">No user selected.</div>
                        <?php endif; ?>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-xl border dark:border-gray-700 shadow-sm p-5">
                        <div class="mb-5">
                            <h4 class="font-semibold text-gray-800 dark:text-white">Update Module Access</h4>
                            <p class="text-xs text-gray-500 mt-1">Tick the modules this user can open, even if their role is below the default level. Changes apply after you save.</p>
                        </div>

                        <?php if ($selectedUser && $hasPermissions): ?>
                            <form action="actions/access_control_logic.php" method="POST" class="space-y-6">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <input type="hidden" name="action" value="save_permissions">
                                <input type="hidden" name="user_id" value="<?php echo (int)$selectedUser['id']; ?>">

                                <?php foreach ($groupedCatalog as $group => $modules): ?>
                                    <div class="rounded-xl border dark:border-gray-700 p-4 bg-gray-50 dark:bg-gray-900/40">
                                        <div class="flex items-center justify-between mb-4">
                                            <h5 class="font-semibold text-gray-800 dark:text-white"><?php echo htmlspecialchars($group); ?></h5>
                                            <span class="text-xs text-gray-500"><?php echo count($modules); ?> items</span>
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                            <?php foreach ($modules as $module): ?>
                                                <?php $checked = !empty($selectedPermissions[$module['key']]); ?>
                                                <label class="flex items-start gap-3 rounded-lg border dark:border-gray-700 bg-white dark:bg-gray-800 p-3 cursor-pointer">
                                                    <input type="checkbox" name="permissions[]" value="<?php echo htmlspecialchars($module['key']); ?>" <?php echo $checked ? 'checked' : ''; ?> class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                    <span>
                                                        <span class="block font-medium text-gray-900 dark:text-gray-200"><?php echo htmlspecialchars($module['label']); ?></span>
                                                        <span class="block text-xs text-gray-500 dark:text-gray-400 mt-1"><?php echo htmlspecialchars($module['description']); ?></span>
                                                    </span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <div class="flex justify-end">
                                    <button class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 rounded-lg text-sm font-medium">Update Access</button>
                                </div>
                            </form>
                        <?php elseif ($selectedUser): ?>
                            <div class="rounded-xl border border-amber-200 bg-amber-50 text-amber-800 p-4">
                                Create the <code>user_permissions</code> table first, then return here to manage per-module access.
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 ">
                        <div class="bg-white dark:bg-gray-800 rounded-xl border dark:border-gray-700 shadow-sm p-5">
                            <h4 class="font-semibold text-gray-800 dark:text-white mb-3">Reset Password</h4>
                            <?php if ($selectedUser): ?>
                                <form action="actions/access_control_logic.php" method="POST" class="space-y-3">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                    <input type="hidden" name="action" value="reset_password">
                                    <input type="hidden" name="user_id" value="<?php echo (int)$selectedUser['id']; ?>">
                                    <input type="password" name="new_password" required placeholder="New password" class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <input type="password" name="confirm_password" required placeholder="Confirm password" class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <button class="w-full bg-slate-800 hover:bg-slate-900 text-white px-4 py-2.5 rounded-lg text-sm font-medium">Reset Password</button>
                                </form>
                            <?php endif; ?>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-xl border dark:border-gray-700 shadow-sm p-5">
                            <h4 class="font-semibold text-gray-800 dark:text-white mb-3">Block / Unblock</h4>
                            <?php if ($selectedUser): ?>
                                <form action="actions/access_control_logic.php" method="POST" class="space-y-3">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="user_id" value="<?php echo (int)$selectedUser['id']; ?>">
                                    <input type="hidden" name="status" value="<?php echo ((int)($selectedUser['status'] ?? 1) === 1) ? 0 : 1; ?>">
                                    <button class="w-full <?php echo ((int)($selectedUser['status'] ?? 1) === 1) ? 'bg-red-600 hover:bg-red-700' : 'bg-emerald-600 hover:bg-emerald-700'; ?> text-white px-4 py-2.5 rounded-lg text-sm font-medium">
                                        <?php echo ((int)($selectedUser['status'] ?? 1) === 1) ? 'Block User' : 'Unblock User'; ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-xl border dark:border-gray-700 shadow-sm p-5">
                            <h4 class="font-semibold text-gray-800 dark:text-white mb-3">Delete User</h4>
                            <?php if ($selectedUser): ?>
                                <form action="actions/access_control_logic.php" method="POST" onsubmit="return confirm('Delete this user permanently?');" class="space-y-3">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="user_id" value="<?php echo (int)$selectedUser['id']; ?>">
                                    <button class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2.5 rounded-lg text-sm font-medium" <?php echo ((int)$selectedUser['id'] === (int)($_SESSION['user_id'] ?? 0) || (string)($selectedUser['role'] ?? '') === 'Super Admin') ? 'disabled' : ''; ?>>
                                        Delete Permanently
                                    </button>
                                </form>
                                <p class="text-[11px] text-gray-500 mt-2">Self-delete and Super Admin deletion are blocked for safety.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require 'includes/footer.php'; ?>
