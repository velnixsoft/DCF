<?php
// admin/admin_management_body.php
require '../config/db.php';
require '../includes/functions.php';

if (!canAccessModule($pdo, 'manager', 'page.management_body')) {
    setFlash('error', 'Access denied. Manager/Admin required.');
    header('Location: dashboard.php');
    exit;
}

$uploadDir = '../uploads/management/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
        !hash_equals((string)$_SESSION['csrf_token'], (string)$_POST['csrf_token'])) {
        setFlash('error', 'Security Token Error');
        header('Location: admin_management_body.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id  = (int)($_POST['id'] ?? 0);
        $row = $pdo->prepare("SELECT photo FROM management_body WHERE id = ?");
        $row->execute([$id]);
        $old = $row->fetch(PDO::FETCH_ASSOC);
        if ($old && $old['photo'] && file_exists('../' . $old['photo'])) unlink('../' . $old['photo']);
        $pdo->prepare("DELETE FROM management_body WHERE id = ?")->execute([$id]);
        setFlash('success', 'Member deleted successfully.');
        header('Location: admin_management_body.php');
        exit;
    }

    if ($action === 'toggle') {
        $id  = (int)($_POST['id']  ?? 0);
        $val = (int)($_POST['val'] ?? 0);
        $pdo->prepare("UPDATE management_body SET is_active = ? WHERE id = ?")->execute([$val, $id]);
        setFlash('success', 'Status updated.');
        header('Location: admin_management_body.php');
        exit;
    }

    if ($action === 'save') {
        $id           = (int)($_POST['id']           ?? 0);
        $name         = trim($_POST['name']          ?? '');
        $designation  = trim($_POST['designation']   ?? '');
        $department   = trim($_POST['department']    ?? '');
        $phone        = trim($_POST['phone']         ?? '');
        $email        = trim($_POST['email']         ?? '');
        $bio          = trim($_POST['bio']           ?? '');
        $fb_url       = trim($_POST['fb_url']        ?? '');
        $linkedin_url = trim($_POST['linkedin_url']  ?? '');
        $sort_order   = (int)($_POST['sort_order']   ?? 0);
        $is_active    = (int)($_POST['is_active']    ?? 1);

        if ($name === '' || $designation === '') {
            setFlash('error', 'Name and Designation are required.');
            header('Location: ' . ($id > 0 ? "admin_management_body.php?edit={$id}" : "admin_management_body.php?add=1"));
            exit;
        }

        $photoPath = $_POST['existing_photo'] ?? '';
        if (!empty($_FILES['photo']['name'])) {
            if ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
                setFlash('error', 'Image too large. Max 2MB allowed.');
                header('Location: admin_management_body.php' . ($id > 0 ? "?edit={$id}" : "?add=1"));
                exit;
            }
            $ext     = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            if (!in_array($ext, $allowed)) {
                setFlash('error', 'Invalid image format.');
                header('Location: admin_management_body.php' . ($id > 0 ? "?edit={$id}" : "?add=1"));
                exit;
            }
            $filename = 'mgmt_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $filename)) {
                if ($photoPath && file_exists('../' . $photoPath)) unlink('../' . $photoPath);
                $photoPath = 'uploads/management/' . $filename;
            } else {
                setFlash('error', 'Failed to upload image.');
                header('Location: admin_management_body.php' . ($id > 0 ? "?edit={$id}" : "?add=1"));
                exit;
            }
        }

        if ($id > 0) {
            $pdo->prepare("UPDATE management_body SET name=?,designation=?,department=?,phone=?,email=?,bio=?,photo=?,fb_url=?,linkedin_url=?,sort_order=?,is_active=? WHERE id=?")
                ->execute([$name,$designation,$department,$phone,$email,$bio,$photoPath,$fb_url,$linkedin_url,$sort_order,$is_active,$id]);
            setFlash('success', 'Member updated successfully.');
        } else {
            $pdo->prepare("INSERT INTO management_body (name,designation,department,phone,email,bio,photo,fb_url,linkedin_url,sort_order,is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
                ->execute([$name,$designation,$department,$phone,$email,$bio,$photoPath,$fb_url,$linkedin_url,$sort_order,$is_active]);
            setFlash('success', 'Member added successfully.');
        }
        header('Location: admin_management_body.php');
        exit;
    }
}

$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(name LIKE ? OR designation LIKE ? OR department LIKE ? OR phone LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($statusFilter !== '') {
    $where[] = "is_active = ?";
    $params[] = (int)$statusFilter;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM management_body $whereSql");
$countStmt->execute($params);
$totalMembers = (int)$countStmt->fetchColumn();

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$totalPages = max(1, ceil($totalMembers / $perPage));
if ($page > $totalPages && $totalMembers > 0) $page = $totalPages;
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("SELECT * FROM management_body $whereSql ORDER BY sort_order ASC, id ASC LIMIT :limit OFFSET :offset");
foreach ($params as $i => $val) {
    $stmt->bindValue($i + 1, $val);
}
$stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

$editMember = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM management_body WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editMember = $stmt->fetch(PDO::FETCH_ASSOC);
}
$formOpen  = isset($_GET['add']) || $editMember !== null;
$csrfToken = generateCsrfToken();

require 'includes/header.php';
?>

<div class="flex h-screen overflow-hidden bg-gray-100 dark:bg-gray-900">
    <?php require 'includes/sidebar.php'; ?>

    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300 min-w-0">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-3 sm:p-4 md:p-6"
              x-data="{ deleteId: null, confirmDelete: false }">

            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
                <div>
                    <h3 class="text-xl sm:text-2xl md:text-3xl font-medium text-gray-700 dark:text-white">Management Body</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Add, edit, and manage your organisation's leadership team</p>
                </div>
                <div class="flex flex-wrap items-center gap-2 sm:gap-3 shrink-0">
                    <a href="../management.php" target="_blank"
                       class="inline-flex items-center gap-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-3 sm:px-4 py-2 rounded-lg font-semibold text-sm transition whitespace-nowrap">
                        <i class="fas fa-eye"></i> <span class="hidden sm:inline">View Public Page</span><span class="sm:hidden">Preview</span>
                    </a>
                    <a href="?add=1"
                       class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-3 sm:px-5 py-2 rounded-lg font-semibold text-sm shadow transition whitespace-nowrap">
                        <i class="fas fa-plus"></i> Add Member
                    </a>
                </div>
            </div>

            <!-- ── Add / Edit Form ── -->
            <?php if ($formOpen): ?>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4 sm:p-6 mb-6">
                <h4 class="text-lg sm:text-xl font-semibold text-gray-700 dark:text-white mb-5 flex items-center gap-2">
                    <i class="fas <?= $editMember ? 'fa-edit' : 'fa-user-plus' ?> text-emerald-600"></i>
                    <?= $editMember ? 'Edit Member' : 'Add New Member' ?>
                </h4>

                <form method="POST" enctype="multipart/form-data"
                      x-data="{
                          nameVal: '<?= htmlspecialchars($editMember['name'] ?? '', ENT_QUOTES) ?>',
                          bioVal:  `<?= htmlspecialchars($editMember['bio']  ?? '', ENT_QUOTES) ?>`
                      }">
                    <input type="hidden" name="csrf_token"     value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="action"         value="save">
                    <input type="hidden" name="id"             value="<?= (int)($editMember['id'] ?? 0) ?>">
                    <input type="hidden" name="existing_photo" value="<?= htmlspecialchars($editMember['photo'] ?? '') ?>">

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8">

                        <!-- LEFT column -->
                        <div class="space-y-4 sm:space-y-5">

                            <div>
                                <label class="block text-sm font-bold mb-1.5 dark:text-gray-300">
                                    Full Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="name" x-model="nameVal"
                                       required maxlength="150"
                                       placeholder="e.g. Ramesh Kumar Sharma"
                                       value="<?= htmlspecialchars($editMember['name'] ?? '') ?>"
                                       class="w-full border px-3 py-2.5 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                <div class="text-right text-xs mt-1 text-gray-400">
                                    <span x-text="nameVal.length"></span> / 150
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-bold mb-1.5 dark:text-gray-300">
                                    Designation <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="designation"
                                       required maxlength="150"
                                       placeholder="e.g. President"
                                       value="<?= htmlspecialchars($editMember['designation'] ?? '') ?>"
                                       class="w-full border px-3 py-2.5 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-bold mb-1.5 dark:text-gray-300">Department / Group</label>
                                    <input type="text" name="department"
                                           placeholder="e.g. Board of Directors"
                                           value="<?= htmlspecialchars($editMember['department'] ?? '') ?>"
                                           class="w-full border px-3 py-2.5 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                    <p class="text-xs text-gray-400 mt-1">Groups members on public page</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold mb-1.5 dark:text-gray-300">Display Order</label>
                                    <input type="number" name="sort_order" min="0"
                                           value="<?= (int)($editMember['sort_order'] ?? 0) ?>"
                                           class="w-full border px-3 py-2.5 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                    <p class="text-xs text-gray-400 mt-1">Lower = shown first</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-bold mb-1.5 dark:text-gray-300">Phone</label>
                                    <input type="text" name="phone"
                                           placeholder="9876543210"
                                           value="<?= htmlspecialchars($editMember['phone'] ?? '') ?>"
                                           class="w-full border px-3 py-2.5 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold mb-1.5 dark:text-gray-300">Email</label>
                                    <input type="email" name="email"
                                           placeholder="president@ngo.org"
                                           value="<?= htmlspecialchars($editMember['email'] ?? '') ?>"
                                           class="w-full border px-3 py-2.5 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-bold mb-1.5 dark:text-gray-300">
                                        <i class="fab fa-facebook text-blue-600 mr-1"></i> Facebook URL
                                    </label>
                                    <input type="url" name="fb_url"
                                           placeholder="https://facebook.com/..."
                                           value="<?= htmlspecialchars($editMember['fb_url'] ?? '') ?>"
                                           class="w-full border px-3 py-2.5 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold mb-1.5 dark:text-gray-300">
                                        <i class="fab fa-linkedin text-blue-700 mr-1"></i> LinkedIn URL
                                    </label>
                                    <input type="url" name="linkedin_url"
                                           placeholder="https://linkedin.com/in/..."
                                           value="<?= htmlspecialchars($editMember['linkedin_url'] ?? '') ?>"
                                           class="w-full border px-3 py-2.5 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                </div>
                            </div>
                        </div>

                        <!-- RIGHT column -->
                        <div class="space-y-4 sm:space-y-5">

                            <div>
                                <label class="block text-sm font-bold mb-1.5 dark:text-gray-300">Bio / Description</label>
                                <textarea name="bio" rows="5" x-model="bioVal" maxlength="1000"
                                          placeholder="Short biography or role description..."
                                          class="w-full border px-3 py-2.5 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 resize-none"><?= htmlspecialchars($editMember['bio'] ?? '') ?></textarea>
                                <div class="text-right text-xs mt-1" :class="bioVal.length > 1000 ? 'text-red-500' : 'text-gray-400'">
                                    <span x-text="bioVal.length"></span> / 1000
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-bold mb-1.5 dark:text-gray-300">
                                    Photo <span class="text-xs font-normal text-gray-400">(JPG/PNG/WEBP, max 2MB)</span>
                                </label>
                                <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 text-center">
                                    <?php if (!empty($editMember['photo'])): ?>
                                        <img src="../<?= htmlspecialchars($editMember['photo']) ?>"
                                             class="w-20 h-20 sm:w-24 sm:h-24 rounded-full object-cover mx-auto mb-3 border-4 border-emerald-100 shadow"
                                             alt="Current photo">
                                        <p class="text-xs text-gray-400 mb-3">Current photo. Upload new to replace.</p>
                                    <?php else: ?>
                                        <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mx-auto mb-3">
                                            <i class="fas fa-user text-gray-400 text-3xl"></i>
                                        </div>
                                    <?php endif; ?>
                                    <input type="file" name="photo" accept="image/jpeg,image/png,image/webp"
                                           class="block w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-bold mb-2 dark:text-gray-300">Status</label>
                                <div class="flex gap-4 sm:gap-6">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="is_active" value="1"
                                               <?= ($editMember['is_active'] ?? 1) == 1 ? 'checked' : '' ?>
                                               class="text-emerald-600 focus:ring-emerald-500">
                                        <span class="text-sm font-medium text-emerald-700 dark:text-emerald-400">Active (Visible)</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="is_active" value="0"
                                               <?= ($editMember['is_active'] ?? 1) == 0 ? 'checked' : '' ?>
                                               class="text-gray-500">
                                        <span class="text-sm font-medium text-gray-500">Hidden</span>
                                    </label>
                                </div>
                            </div>

                            <div class="bg-emerald-50 dark:bg-emerald-900/20 p-4 rounded-lg border border-emerald-100 dark:border-emerald-800">
                                <h5 class="font-bold text-emerald-700 dark:text-emerald-400 mb-2 text-sm">Tips:</h5>
                                <ul class="text-sm text-gray-600 dark:text-gray-300 list-disc list-inside space-y-1">
                                    <li>Use a clear, professional headshot photo.</li>
                                    <li>Keep bio short — 2 to 3 sentences is ideal.</li>
                                    <li>Use Department to group members (e.g. "Board of Directors").</li>
                                    <li>Set Display Order to control who appears first.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Submit row -->
                    <div class="border-t pt-5 mt-5 dark:border-gray-700 flex flex-col-reverse sm:flex-row items-center gap-3 justify-end">
                        <a href="admin_management_body.php"
                           class="w-full sm:w-auto text-center bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-bold py-2.5 px-6 rounded-lg transition">
                            <i class="fas fa-times mr-2"></i> Cancel
                        </a>
                        <button type="submit"
                                class="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-8 rounded-lg shadow-lg transition">
                            <i class="fas fa-save mr-2"></i>
                            <?= $editMember ? 'Update Member' : 'Add Member' ?>
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <!-- ── Members Table ── -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                <div class="px-4 sm:px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <h4 class="text-base sm:text-lg font-semibold text-gray-700 dark:text-white flex items-center gap-2">
                        <span>All Members</span>
                        <span class="text-xs bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-400 font-semibold px-2 py-0.5 rounded-full">
                            <?= $totalMembers ?>
                        </span>
                    </h4>
                    <form method="GET" class="flex items-center gap-2">
                        <div class="relative flex-1 sm:w-64">
                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search members..."
                                   class="w-full pl-8 pr-3 py-1.5 text-xs rounded-lg border border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-gray-800 dark:text-gray-200 focus:ring-1 focus:ring-emerald-500">
                        </div>
                        <select name="status" class="text-xs rounded-lg border border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-gray-800 dark:text-gray-200 px-2 py-1.5 focus:ring-1 focus:ring-emerald-500">
                            <option value="">All Status</option>
                            <option value="1" <?= $statusFilter === '1' ? 'selected' : '' ?>>Active</option>
                            <option value="0" <?= $statusFilter === '0' ? 'selected' : '' ?>>Hidden</option>
                        </select>
                        <button type="submit" class="bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs px-3 py-1.5 rounded-lg font-medium transition">Filter</button>
                        <?php if ($search !== '' || $statusFilter !== ''): ?>
                            <a href="admin_management_body.php" class="text-xs text-red-500 hover:underline">Reset</a>
                        <?php endif; ?>
                    </form>
                </div>

                <?php if (empty($members)): ?>
                <div class="py-16 text-center text-gray-400">
                    <i class="fas fa-users fa-3x mb-3 opacity-40 block"></i>
                    <p class="font-medium">No members yet. Click "Add Member" to get started.</p>
                </div>

                <?php else: ?>

                <!-- Mobile cards (hidden md+) -->
                <div class="md:hidden divide-y dark:divide-gray-700">
                    <?php foreach ($members as $m): ?>
                    <div class="p-4 space-y-3">
                        <div class="flex items-center gap-3">
                            <?php if (!empty($m['photo'])): ?>
                                <img src="../<?= htmlspecialchars($m['photo']) ?>"
                                     class="h-11 w-11 rounded-full object-cover border-2 border-gray-200 dark:border-gray-600 shrink-0"
                                     alt="<?= htmlspecialchars($m['name']) ?>">
                            <?php else: ?>
                                <div class="h-11 w-11 rounded-full bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center shrink-0">
                                    <span class="text-emerald-700 dark:text-emerald-400 font-bold text-sm">
                                        <?= strtoupper(substr($m['name'], 0, 1)) ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-gray-800 dark:text-white truncate"><?= htmlspecialchars($m['name']) ?></p>
                                <p class="text-xs text-emerald-600 dark:text-emerald-400 truncate"><?= htmlspecialchars($m['designation']) ?></p>
                                <?php if ($m['department']): ?>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate"><?= htmlspecialchars($m['department']) ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="shrink-0 flex flex-col items-end gap-1">
                                <form method="POST" class="inline">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id"  value="<?= $m['id'] ?>">
                                    <input type="hidden" name="val" value="<?= $m['is_active'] ? 0 : 1 ?>">
                                    <button type="submit"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold transition
                                            <?= $m['is_active']
                                                ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-400'
                                                : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400' ?>">
                                        <span class="w-1.5 h-1.5 rounded-full <?= $m['is_active'] ? 'bg-emerald-500' : 'bg-gray-400' ?>"></span>
                                        <?= $m['is_active'] ? 'Active' : 'Hidden' ?>
                                    </button>
                                </form>
                                <span class="text-xs text-gray-400">Order: <?= (int)$m['sort_order'] ?></span>
                            </div>
                        </div>

                        <?php if ($m['phone'] || $m['email']): ?>
                        <div class="text-xs text-gray-500 dark:text-gray-400 space-y-0.5">
                            <?php if ($m['phone']): ?>
                            <p><i class="fas fa-phone fa-xs mr-1 text-gray-400"></i><?= htmlspecialchars($m['phone']) ?></p>
                            <?php endif; ?>
                            <?php if ($m['email']): ?>
                            <p class="truncate"><i class="fas fa-envelope fa-xs mr-1 text-gray-400"></i><?= htmlspecialchars($m['email']) ?></p>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <div class="flex gap-2">
                            <a href="?edit=<?= $m['id'] ?>"
                               class="flex-1 text-center text-xs bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-100 hover:bg-blue-200 px-3 py-2 rounded-lg font-bold border border-blue-200 dark:border-blue-800 transition">
                                <i class="fas fa-edit mr-1"></i> Edit
                            </a>
                            <button type="button"
                                    @click="deleteId = <?= $m['id'] ?>; confirmDelete = true"
                                    class="flex-1 text-xs bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-100 hover:bg-red-200 px-3 py-2 rounded-lg font-bold border border-red-200 dark:border-red-800 transition">
                                <i class="fas fa-trash mr-1"></i> Delete
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Desktop table (hidden below md) -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-sm" style="min-width: 640px;">
                        <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wider">
                            <tr>
                                <th class="px-5 py-3 text-left">Member</th>
                                <th class="px-5 py-3 text-left">Department</th>
                                <th class="px-5 py-3 text-left">Contact</th>
                                <th class="px-5 py-3 text-center">Order</th>
                                <th class="px-5 py-3 text-center">Status</th>
                                <th class="px-5 py-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <?php foreach ($members as $m): ?>
                            <tr class="group hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">

                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <?php if (!empty($m['photo'])): ?>
                                            <img src="../<?= htmlspecialchars($m['photo']) ?>"
                                                 class="h-10 w-10 rounded-full object-cover border-2 border-gray-200 dark:border-gray-600 shrink-0"
                                                 alt="<?= htmlspecialchars($m['name']) ?>">
                                        <?php else: ?>
                                            <div class="h-10 w-10 rounded-full bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center shrink-0">
                                                <span class="text-emerald-700 dark:text-emerald-400 font-bold text-sm">
                                                    <?= strtoupper(substr($m['name'], 0, 1)) ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-gray-800 dark:text-white group-hover:text-black dark:group-hover:text-black truncate max-w-[160px]"><?= htmlspecialchars($m['name']) ?></p>
                                            <p class="text-xs text-emerald-600 dark:text-emerald-400 group-hover:text-emerald-800 dark:group-hover:text-emerald-800 truncate max-w-[160px]"><?= htmlspecialchars($m['designation']) ?></p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-5 py-4 text-gray-600 dark:text-gray-300 group-hover:text-black dark:group-hover:text-black text-sm">
                                    <?= $m['department']
                                        ? htmlspecialchars($m['department'])
                                        : '<span class="text-gray-300 dark:text-gray-600 group-hover:text-gray-500">—</span>' ?>
                                </td>

                                <td class="px-5 py-4">
                                    <?php if ($m['phone']): ?>
                                    <p class="text-gray-600 dark:text-gray-300 group-hover:text-black dark:group-hover:text-black text-xs whitespace-nowrap">
                                        <i class="fas fa-phone fa-xs mr-1 text-gray-400 group-hover:text-gray-600"></i><?= htmlspecialchars($m['phone']) ?>
                                    </p>
                                    <?php endif; ?>
                                    <?php if ($m['email']): ?>
                                    <p class="text-gray-500 dark:text-gray-400 group-hover:text-black dark:group-hover:text-black text-xs max-w-[160px] truncate">
                                        <i class="fas fa-envelope fa-xs mr-1 text-gray-400 group-hover:text-gray-600"></i><?= htmlspecialchars($m['email']) ?>
                                    </p>
                                    <?php endif; ?>
                                </td>

                                <td class="px-5 py-4 text-center">
                                    <span class="inline-block bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs font-bold px-2 py-1 rounded">
                                        <?= (int)$m['sort_order'] ?>
                                    </span>
                                </td>

                                <td class="px-5 py-4 text-center">
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id"  value="<?= $m['id'] ?>">
                                        <input type="hidden" name="val" value="<?= $m['is_active'] ? 0 : 1 ?>">
                                        <button type="submit"
                                                class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold transition
                                                <?= $m['is_active']
                                                    ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-200'
                                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 hover:bg-gray-200' ?>">
                                            <span class="w-1.5 h-1.5 rounded-full <?= $m['is_active'] ? 'bg-emerald-500' : 'bg-gray-400' ?>"></span>
                                            <?= $m['is_active'] ? 'Active' : 'Hidden' ?>
                                        </button>
                                    </form>
                                </td>

                                <td class="px-5 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="?edit=<?= $m['id'] ?>"
                                           class="inline-flex items-center gap-1 text-xs bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-100 hover:bg-blue-200 px-3 py-1.5 rounded-lg font-bold border border-blue-200 dark:border-blue-800 transition whitespace-nowrap">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <button type="button"
                                                @click="deleteId = <?= $m['id'] ?>; confirmDelete = true"
                                                class="inline-flex items-center gap-1 text-xs bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-100 hover:bg-red-200 px-3 py-1.5 rounded-lg font-bold border border-red-200 dark:border-red-800 transition whitespace-nowrap">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?= render_admin_pagination($totalMembers, $page, $perPage, ['search' => $search, 'status' => $statusFilter]); ?>
                <?php endif; ?>
            </div>

            <!-- ── Delete Confirm Modal ── -->
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
                 x-show="confirmDelete" x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0">

                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-6 w-full max-w-sm"
                     @click.outside="confirmDelete = false">
                    <div class="text-center">
                        <div class="w-14 h-14 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-trash-alt text-red-500 text-xl"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-2">Delete Member?</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                            This will permanently remove this member and their photo. This cannot be undone.
                        </p>
                        <div class="flex gap-3">
                            <button @click="confirmDelete = false"
                                    class="flex-1 py-2.5 rounded-lg border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-semibold text-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                Cancel
                            </button>
                            <form method="POST" class="flex-1">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" :value="deleteId">
                                <button type="submit"
                                        class="w-full py-2.5 rounded-lg bg-red-600 hover:bg-red-700 text-white font-semibold text-sm transition">
                                    Yes, Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<?php require 'includes/footer.php'; ?>