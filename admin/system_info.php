<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!canAccessModule($pdo, 'admin', 'page.system_info')) {
    setFlash('error', 'Access denied.');
    header('Location: dashboard.php');
    exit;
}

$csrfToken = generateCsrfToken();

$settings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Throwable $e) {
    $settings = [];
}

$stats = [
    'users' => 0,
    'members' => 0,
    'volunteers' => 0,
    'donations' => 0,
    'projects' => 0,
    'events' => 0,
];

foreach ([
    'users' => 'SELECT COUNT(*) FROM users',
    'members' => 'SELECT COUNT(*) FROM members',
    'volunteers' => 'SELECT COUNT(*) FROM volunteers',
    'donations' => 'SELECT COUNT(*) FROM donations',
    'projects' => 'SELECT COUNT(*) FROM projects',
    'events' => 'SELECT COUNT(*) FROM events',
] as $key => $sql) {
    try {
        $stats[$key] = (int)$pdo->query($sql)->fetchColumn();
    } catch (Throwable $e) {
        $stats[$key] = 0;
    }
}

$dbVersion = 'Unknown';
try {
    $dbVersion = (string)($pdo->query('SELECT VERSION()')->fetchColumn() ?: 'Unknown');
} catch (Throwable $e) {
    $dbVersion = 'Unknown';
}

$extensions = array_values(array_filter([
    'pdo_mysql' => extension_loaded('pdo_mysql') ? 'pdo_mysql' : null,
    'gd' => extension_loaded('gd') ? 'gd' : null,
    'mbstring' => extension_loaded('mbstring') ? 'mbstring' : null,
    'openssl' => extension_loaded('openssl') ? 'openssl' : null,
    'zip' => extension_loaded('zip') ? 'zip' : null,
    'curl' => extension_loaded('curl') ? 'curl' : null,
], static function ($value) {
    return $value !== null;
}));

require_once __DIR__ . '/includes/header.php';
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900">
    <?php require __DIR__ . '/includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require __DIR__ . '/includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-8">
            <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-8">
                <div>
                    <h3 class="text-2xl md:text-3xl font-medium text-gray-800 dark:text-white">System Info</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Server, database, PHP, and module health summary.</p>
                </div>
                <div class="flex gap-2">
                    <a href="settings.php" class="bg-slate-700 hover:bg-slate-800 text-white px-4 py-2 rounded-lg text-sm">Settings</a>
                    <a href="access_control.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">Access Control</a>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 mb-6">
                <?php
                $cards = [
                    ['label' => 'PHP Version', 'value' => PHP_VERSION, 'color' => 'blue'],
                    ['label' => 'Database Version', 'value' => $dbVersion, 'color' => 'emerald'],
                    ['label' => 'Server', 'value' => $_SERVER['SERVER_SOFTWARE'] ?? php_uname('s'), 'color' => 'slate'],
                    ['label' => 'Timezone', 'value' => date_default_timezone_get(), 'color' => 'amber'],
                    ['label' => 'Memory Limit', 'value' => ini_get('memory_limit') ?: 'Unknown', 'color' => 'rose'],
                    ['label' => 'Upload Max', 'value' => ini_get('upload_max_filesize') ?: 'Unknown', 'color' => 'indigo'],
                ];
                foreach ($cards as $card):
                ?>
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 shadow-sm p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400"><?php echo htmlspecialchars($card['label']); ?></p>
                        <p class="mt-3 text-xl font-bold text-gray-900 dark:text-white break-words"><?php echo htmlspecialchars($card['value']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <div class="xl:col-span-2 space-y-6">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 shadow-sm p-6">
                        <h4 class="font-semibold text-gray-800 dark:text-white mb-4">Application Counts</h4>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            <?php foreach ($stats as $label => $value): ?>
                                <div class="rounded-xl border dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 p-4">
                                    <p class="text-xs uppercase tracking-[0.2em] text-gray-400 dark:text-gray-300"><?php echo htmlspecialchars($label); ?></p>
                                    <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-gray-100"><?php echo (int)$value; ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 shadow-sm p-6">
                        <h4 class="font-semibold text-gray-800 dark:text-white mb-4">Loaded Extensions</h4>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($extensions as $extension): ?>
                                <span class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100 text-sm"><?php echo htmlspecialchars($extension); ?></span>
                            <?php endforeach; ?>
                            <?php if (empty($extensions)): ?>
                                <span class="text-sm text-gray-500">No common extensions detected.</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 shadow-sm p-6">
                        <h4 class="font-semibold text-gray-800 dark:text-white mb-4">Brand / Org</h4>
                        <div class="space-y-3 text-sm">
                            <div>
                                <p class="text-gray-500">NGO Name</p>
                                <p class="font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($settings['site_name'] ?? 'NGO System'); ?></p>
                            </div>
                            <div>
                                <p class="text-gray-500">Website</p>
                                <p class="font-medium text-gray-900 dark:text-white break-words"><?php echo htmlspecialchars($settings['ngo_website'] ?? '-'); ?></p>
                            </div>
                            <div>
                                <p class="text-gray-500">Email</p>
                                <p class="font-medium text-gray-900 dark:text-white break-words"><?php echo htmlspecialchars($settings['ngo_email'] ?? '-'); ?></p>
                            </div>
                            <div>
                                <p class="text-gray-500">Phone</p>
                                <p class="font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($settings['ngo_phone'] ?? '-'); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 shadow-sm p-6">
                        <h4 class="font-semibold text-gray-800 dark:text-white mb-4">Module Health</h4>
                         <div class="space-y-3 text-sm">
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-gray-500 dark:text-gray-400">Permissions Table</span>
                                <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo dbTableExists($pdo, 'user_permissions') ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'; ?>">
                                    <?php echo dbTableExists($pdo, 'user_permissions') ? 'Installed' : 'Missing'; ?>
                                </span>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-gray-500 dark:text-gray-400">Document Studio</span>
                                <span class="px-2 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">Ready</span>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-gray-500 dark:text-gray-400">Access Control</span>
                                <span class="px-2 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">Ready</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
