<?php
require '../config/db.php';
require '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_info'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        setFlash('error', 'Security Token Invalid');
    } else {
        $fields = ['ngo_address', 'ngo_phone', 'ngo_email', 'contact_map_iframe', 'ngo_state', 'ngo_city', 'ngo_district'];
        foreach ($fields as $key) {
            $val = $_POST[$key] ?? '';
            $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?")->execute([$key, $val, $val]);
        }
        setFlash('success', 'Contact details updated!');
    }
    header('Location: contact_manager.php');
    exit;
}
if (isset($_GET['mark_replied'])) {
    $id = $_GET['mark_replied'];
    $pdo->prepare("UPDATE contact_messages SET status='Replied' WHERE id=?")->execute([$id]);
    setFlash('success', 'Message marked as Replied');
    header('Location: contact_manager.php');
    exit;
}

$settings = [];
$stmt = $pdo->query("SELECT * FROM settings WHERE setting_key IN ('ngo_address', 'ngo_phone', 'ngo_email', 'contact_map_iframe', 'ngo_state', 'ngo_city', 'ngo_district')");
while ($row = $stmt->fetch()) $settings[$row['setting_key']] = $row['setting_value'];

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');

$where = [];
$params = [];
if ($search !== '') {
    $where[] = "(name LIKE :search OR email LIKE :search OR message LIKE :search)";
    $params[':search'] = "%$search%";
}
if ($status !== '') {
    $where[] = "status = :status";
    $params[':status'] = $status;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM contact_messages $whereSql");
foreach ($params as $k => $v) $countStmt->bindValue($k, $v);
$countStmt->execute();
$totalMsgs = (int)$countStmt->fetchColumn();

$msgStmt = $pdo->prepare("SELECT * FROM contact_messages $whereSql ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
foreach ($params as $k => $v) $msgStmt->bindValue($k, $v);
$msgStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$msgStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$msgStmt->execute();
$msgs = $msgStmt->fetchAll();
$newMsgCount = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'New'")->fetchColumn();

require 'includes/header.php';
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900" x-data="{ tab: 'inbox' }">
    <?php require 'includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>
        <main class="p-4 md:p-6 overflow-y-auto flex-1">

            <div class="py-2">
                <div class="flex flex-col md:flex-row justify-between items-center mb-6">
                    <h3 class="text-3xl font-bold dark:text-white">Contact Manager</h3>

                    <div class="bg-white dark:bg-gray-800 rounded-lg p-1 shadow-sm flex border dark:border-gray-700 mt-4 md:mt-0">
                        <button @click="tab = 'inbox'" :class="tab === 'inbox' ? 'bg-blue-600 text-white' : 'text-gray-600 dark:text-gray-300'" class="px-4 py-2 rounded-md text-sm font-medium transition flex items-center gap-2">
                            Inbox <span class="bg-blue-100 text-blue-600 text-xs px-2 rounded-full"><?php echo $newMsgCount; ?></span>
                        </button>
                        <button @click="tab = 'settings'" :class="tab === 'settings' ? 'bg-blue-600 text-white' : 'text-gray-600 dark:text-gray-300'" class="px-4 py-2 rounded-md text-sm font-medium transition">Page Settings</button>
                    </div>
                </div>
            </div>

            <div x-show="tab === 'inbox'" class="space-y-4">
                <!-- Search & Filters -->
                <form method="GET" class="flex flex-wrap gap-3 items-center">
                    <div class="relative flex-1 min-w-[220px] max-w-md">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search name, email, message..."
                               class="w-full pl-9 pr-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-blue-500">
                        <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                    </div>
                    <select name="status" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 text-xs text-gray-700 dark:text-gray-200">
                        <option value="">All Statuses</option>
                        <option value="New" <?php echo $status === 'New' ? 'selected' : ''; ?>>New</option>
                        <option value="Replied" <?php echo $status === 'Replied' ? 'selected' : ''; ?>>Replied</option>
                    </select>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition">
                        Filter
                    </button>
                    <?php if ($search !== '' || $status !== ''): ?>
                        <a href="contact_manager.php" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 text-gray-700 dark:text-gray-300 text-xs font-bold rounded-xl transition">
                            Reset
                        </a>
                    <?php endif; ?>
                </form>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden border dark:border-gray-700">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-500 dark:text-gray-400 border-b dark:border-gray-700">
                                <tr>
                                    <th class="p-4 hidden md:table-cell">Sender Info</th>
                                    <th class="p-4">Message</th>
                                    <th class="p-4 text-right hidden md:table-cell">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y dark:divide-gray-700">
                                <?php if (count($msgs) > 0): foreach ($msgs as $msg): ?>
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 dark:text-gray-300">
                                            <td class="p-4 align-top hidden md:table-cell">
                                                <p class="font-bold text-gray-800 dark:text-white"><?php echo htmlspecialchars($msg['name']); ?></p>
                                                <a href="mailto:<?php echo htmlspecialchars($msg['email']); ?>" class="text-xs text-blue-500 hover:underline"><?php echo htmlspecialchars($msg['email']); ?></a>
                                            </td>
                                            <td class="p-4 align-top">
                                                <div class="md:hidden mb-2">
                                                    <p class="font-bold text-gray-800 dark:text-white"><?php echo htmlspecialchars($msg['name']); ?></p>
                                                    <span class="text-xs text-gray-500"><?php echo date('d M, Y', strtotime($msg['created_at'])); ?></span>
                                                </div>
                                                <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
                                                <div class="md:hidden mt-3 text-right">
                                                    <?php if ($msg['status'] == 'New'): ?>
                                                        <a href="?mark_replied=<?php echo $msg['id']; ?>" class="bg-orange-100 text-orange-700 px-3 py-1 rounded text-xs font-bold border border-orange-200">Mark Replied</a>
                                                    <?php else: ?>
                                                        <span class="bg-green-100 text-green-700 px-3 py-1 rounded text-xs font-bold border border-green-200">Replied</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="p-4 align-top text-right hidden md:table-cell">
                                                <?php if ($msg['status'] == 'New'): ?>
                                                    <a href="?mark_replied=<?php echo $msg['id']; ?>" class="bg-orange-100 text-orange-700 px-3 py-1 rounded-full text-xs font-bold hover:bg-orange-200 border border-orange-200">Mark as Replied</a>
                                                <?php else: ?>
                                                    <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold border border-green-200">Replied</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach;
                                else: ?>
                                    <tr>
                                        <td colspan="4" class="p-8 text-center text-gray-500">Inbox is empty.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php echo render_admin_pagination($totalMsgs, $page, $perPage, ['search' => $search, 'status' => $status]); ?>
                </div>
            </div>

            <div x-show="tab === 'settings'" class="grid grid-cols-1 lg:grid-cols-2 gap-8" x-cloak>

                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md border dark:border-gray-700 h-fit">
                    <h4 class="text-xl font-bold mb-4 dark:text-white border-b pb-2 dark:border-gray-700">Update Contact Page Info</h4>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="update_info" value="1">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                        <div>
                            <label class="block text-sm font-medium mb-1 dark:text-gray-300">Official Phone</label>
                            <input type="text" name="ngo_phone" value="<?php echo htmlspecialchars($settings['ngo_phone'] ?? ''); ?>" class="w-full border p-2 rounded bg-white text-slate-900 dark:bg-white dark:text-slate-900 dark:border-gray-600">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1 dark:text-gray-300">Official Email</label>
                            <input type="email" name="ngo_email" value="<?php echo htmlspecialchars($settings['ngo_email'] ?? ''); ?>" class="w-full border p-2 rounded bg-white text-slate-900 dark:bg-white dark:text-slate-900 dark:border-gray-600">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1 dark:text-gray-300">Office Address</label>
                            <textarea name="ngo_address" rows="3" class="w-full border p-2 rounded bg-white text-slate-900 dark:bg-white dark:text-slate-900 dark:border-gray-600"><?php echo htmlspecialchars($settings['ngo_address'] ?? ''); ?></textarea>
                        </div>
                        <?php require_once __DIR__ . '/../includes/india_locations.php'; ?>
                        <div>
                            <label class="block text-sm font-medium mb-1 dark:text-gray-300">State</label>
                            <select name="ngo_state" id="ngo_state" onchange="updateNgoDistricts()" class="w-full border p-2 rounded bg-white text-slate-900 dark:bg-white dark:text-slate-900 dark:border-gray-600">
                                <option value="">Select State</option>
                                <?php foreach (india_state_list() as $st): ?>
                                    <option value="<?php echo htmlspecialchars($st); ?>" <?php echo ($settings['ngo_state'] ?? '') === $st ? 'selected' : ''; ?>><?php echo htmlspecialchars($st); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1 dark:text-gray-300">City / District</label>
                            <select name="ngo_district" id="ngo_district" onchange="document.getElementById('ngo_city').value = this.value" class="w-full border p-2 rounded bg-white text-slate-900 dark:bg-white dark:text-slate-900 dark:border-gray-600">
                                <option value="">Select District</option>
                            </select>
                            <input type="hidden" name="ngo_city" id="ngo_city" value="<?php echo htmlspecialchars($settings['ngo_city'] ?? ''); ?>">
                        </div>
                        <script>
                        const ngoStateDistrictMap = <?php echo india_state_district_js(); ?>;
                        function updateNgoDistricts() {
                            const state = document.getElementById('ngo_state').value;
                            const distSelect = document.getElementById('ngo_district');
                            const currentDist = <?php echo json_encode($settings['ngo_district'] ?? ($settings['ngo_city'] ?? '')); ?>;
                            const dists = ngoStateDistrictMap[state] || [];
                            let html = '<option value="">Select District</option>';
                            dists.forEach(d => {
                                const sel = (d === currentDist || d === distSelect.value) ? 'selected' : '';
                                html += `<option value="${d.replace(/"/g, '&quot;')}" ${sel}>${d}</option>`;
                            });
                            distSelect.innerHTML = html;
                            document.getElementById('ngo_city').value = distSelect.value;
                        }
                        document.addEventListener('DOMContentLoaded', updateNgoDistricts);
                        </script>

                        <div>
                            <label class="block text-sm font-medium mb-1 dark:text-gray-300">Google Map Embed Code</label>
                            <textarea name="contact_map_iframe" rows="4" class="w-full border p-2 rounded bg-white text-slate-900 dark:bg-white dark:text-slate-900 dark:border-gray-600 font-mono text-xs" placeholder='<iframe src="..."></iframe>'><?php echo htmlspecialchars($settings['contact_map_iframe'] ?? ''); ?></textarea>
                            <p class="text-xs text-gray-400 mt-1">Go to Google Maps > Share > Embed a map > Copy HTML.</p>
                        </div>

                        <button class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 w-full font-medium">Save Changes</button>
                    </form>
                </div>

                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md border dark:border-gray-700">
                    <h4 class="text-xl font-bold mb-4 dark:text-white border-b pb-2 dark:border-gray-700">Map Preview</h4>
                    <div class="w-full h-80 bg-gray-100 dark:bg-gray-700 rounded overflow-hidden relative">
                        <?php if (!empty($settings['contact_map_iframe'])): ?>
                            <div class="absolute inset-0 w-full h-full [&>iframe]:w-full [&>iframe]:h-full">
                                <?php echo $settings['contact_map_iframe']; ?>
                            </div>
                        <?php else: ?>
                            <div class="flex items-center justify-center h-full text-gray-400">Map not set</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<?php require 'includes/footer.php'; ?>```
