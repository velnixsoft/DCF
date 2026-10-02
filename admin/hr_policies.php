<?php
// ============================================================
// admin/hr_policies.php
// HR Policy & Institutional Compliance Management (Manager/Admin)
// ============================================================

require_once '../config/db.php';
require_once '../includes/functions.php';

$csrfToken = generateCsrfToken();

if (!checkRole($pdo, 'manager')) {
    setFlash('error', 'Unauthorized access. Manager/Admin role required.');
    header('Location: dashboard.php');
    exit;
}

require 'includes/header.php';

$policies = [];
$error = null;

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$totalPolicies = 0;
$categoryFilter = trim($_GET['category'] ?? '');
$searchQuery = trim($_GET['search'] ?? '');

$where = ["1=1"];
$params = [];

if ($categoryFilter !== '') {
    $where[] = "category = :cat";
    $params[':cat'] = $categoryFilter;
}
if ($searchQuery !== '') {
    $where[] = "(title LIKE :search OR policy_code LIKE :search OR description LIKE :search)";
    $params[':search'] = "%{$searchQuery}%";
}
$whereSql = implode(' AND ', $where);

try {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM hr_policies WHERE {$whereSql}");
    $countStmt->execute($params);
    $totalPolicies = (int)$countStmt->fetchColumn();

    $offset = ($page - 1) * $perPage;
    $stmt = $pdo->prepare("SELECT * FROM hr_policies WHERE {$whereSql} ORDER BY sort_order ASC, id DESC LIMIT :limit OFFSET :offset");
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $policies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $error = 'HR Policies table is missing. Run DB migration: database/create_hr_policies.sql';
}

$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
$editPolicy = null;
if ($editId) {
    try {
        $eStmt = $pdo->prepare("SELECT * FROM hr_policies WHERE id = ?");
        $eStmt->execute([$editId]);
        $editPolicy = $eStmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {}
}

// Compute Metrics
$publicCount = 0;
$cocCount = 0;
$poshCount = 0;
try {
    $publicCount = (int)$pdo->query("SELECT COUNT(*) FROM hr_policies WHERE is_public = 1")->fetchColumn();
    $cocCount = (int)$pdo->query("SELECT COUNT(*) FROM hr_policies WHERE category = 'code_of_conduct'")->fetchColumn();
    $poshCount = (int)$pdo->query("SELECT COUNT(*) FROM hr_policies WHERE category = 'posh_gender'")->fetchColumn();
} catch (Throwable $e) {}
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900" x-data="{
    showUploadModal: <?php echo $editPolicy ? 'true' : 'false'; ?>,
    activeCategoryFilter: ''
}">
    <?php require 'includes/sidebar.php'; ?>

    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6 lg:p-8">
            <!-- Flash Message -->
            <?php displayFlash(); ?>

            <!-- Page Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <div>
                    <div class="flex items-center gap-2.5">
                        <span class="p-2.5 rounded-2xl bg-teal-500/10 text-teal-600 dark:text-teal-400">
                            <i class="fa-solid fa-scale-balanced text-2xl"></i>
                        </span>
                        <div>
                            <h1 class="text-2xl md:text-3xl font-black text-gray-900 dark:text-white tracking-tight">HR Policies & Workplace Compliance</h1>
                            <p class="text-xs md:text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                                Upload and manage employee code of conduct, POSH guidelines, child safeguarding, leave, and field safety documents.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <a href="/hr-policies" target="_blank" class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-800 hover:bg-gray-900 text-white text-xs font-bold rounded-xl shadow-sm transition">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                        <span>View Public Policies</span>
                    </a>

                    <a href="hr_policies.php<?php echo $editPolicy ? '' : '#upload-form'; ?>" @click="showUploadModal = true" class="inline-flex items-center gap-2 px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold rounded-xl shadow-sm transition transform hover:-translate-y-0.5 shadow-teal-600/20">
                        <i class="fa-solid fa-file-arrow-up text-xs"></i>
                        <span>Upload New Policy</span>
                    </a>
                </div>
            </div>

            <!-- 4 KPI Metrics -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-100 dark:border-gray-700 shadow-xs flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-teal-50 dark:bg-teal-900/40 text-teal-600 dark:text-teal-400 flex items-center justify-center text-lg flex-shrink-0">
                        <i class="fa-solid fa-file-shield"></i>
                    </div>
                    <div>
                        <span class="text-xs text-gray-400 font-bold uppercase block">Total Policies</span>
                        <span class="text-xl font-black text-gray-900 dark:text-white"><?php echo $totalPolicies; ?></span>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-100 dark:border-gray-700 shadow-xs flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-lg flex-shrink-0">
                        <i class="fa-solid fa-globe"></i>
                    </div>
                    <div>
                        <span class="text-xs text-gray-400 font-bold uppercase block">Public Documents</span>
                        <span class="text-xl font-black text-emerald-600 dark:text-emerald-400"><?php echo $publicCount; ?></span>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-100 dark:border-gray-700 shadow-xs flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-purple-50 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400 flex items-center justify-center text-lg flex-shrink-0">
                        <i class="fa-solid fa-shield-heart"></i>
                    </div>
                    <div>
                        <span class="text-xs text-gray-400 font-bold uppercase block">POSH & Safety</span>
                        <span class="text-xl font-black text-purple-600 dark:text-purple-400"><?php echo $poshCount; ?></span>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-100 dark:border-gray-700 shadow-xs flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-lg flex-shrink-0">
                        <i class="fa-solid fa-handshake-angle"></i>
                    </div>
                    <div>
                        <span class="text-xs text-gray-400 font-bold uppercase block">Ethics & Conduct</span>
                        <span class="text-xl font-black text-indigo-600 dark:text-indigo-400"><?php echo $cocCount; ?></span>
                    </div>
                </div>
            </div>

            <!-- Main Layout: Left Form + Right Table (Legal Docs Pattern) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                
                <!-- Left Column: Upload / Edit Form (4 cols) -->
                <div id="upload-form" class="lg:col-span-4 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 md:p-6">
                    <div class="border-b border-gray-100 dark:border-gray-700 pb-3 mb-4 flex items-center justify-between">
                        <h4 class="font-black text-sm md:text-base text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fa-solid <?php echo $editPolicy ? 'fa-pen-to-square text-amber-500' : 'fa-file-circle-plus text-teal-600'; ?>"></i>
                            <span><?php echo $editPolicy ? 'Edit HR Policy' : 'Upload HR Policy Document'; ?></span>
                        </h4>
                        <?php if ($editPolicy): ?>
                            <a href="hr_policies.php" class="text-xs text-gray-400 hover:text-rose-600 font-bold">Clear</a>
                        <?php endif; ?>
                    </div>

                    <form action="actions/hr_policy_logic.php" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="id" value="<?php echo (int)($editPolicy['id'] ?? 0); ?>">

                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Policy Title *</label>
                            <input type="text" name="title" required value="<?php echo htmlspecialchars($editPolicy['title'] ?? ''); ?>"
                                   placeholder="e.g. Code of Conduct & Workplace Ethics"
                                   class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 dark:text-white focus:ring-2 focus:ring-teal-500">
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Policy Category</label>
                            <select name="category" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 dark:text-white">
                                <?php
                                $currentCat = $editPolicy['category'] ?? 'code_of_conduct';
                                $cats = [
                                    'code_of_conduct' => 'Code of Conduct & Ethics',
                                    'posh_gender' => 'POSH & Gender Safety',
                                    'child_safeguarding' => 'Child Protection & Safeguarding',
                                    'leave_benefits' => 'Leave, Attendance & Benefits',
                                    'whistleblower' => 'Whistleblower & Anti-Fraud',
                                    'travel_compensation' => 'Travel & Reimbursement',
                                    'volunteer_ethics' => 'Volunteer & Field Ethics',
                                    'general' => 'General Policy / Other'
                                ];
                                foreach ($cats as $k => $label):
                                ?>
                                    <option value="<?php echo $k; ?>" <?php echo $currentCat === $k ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Policy Code</label>
                                <input type="text" name="policy_code" value="<?php echo htmlspecialchars($editPolicy['policy_code'] ?? ''); ?>"
                                       placeholder="e.g. HRP-COC-01"
                                       class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3 py-2 text-xs text-gray-900 dark:text-white">
                            </div>

                            <div>
                                <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Version</label>
                                <input type="text" name="policy_version" value="<?php echo htmlspecialchars($editPolicy['policy_version'] ?? 'v1.0'); ?>"
                                       placeholder="v1.0"
                                       class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3 py-2 text-xs text-gray-900 dark:text-white">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Effective Date</label>
                                <input type="date" name="effective_date" value="<?php echo htmlspecialchars($editPolicy['effective_date'] ?? date('Y-m-d')); ?>"
                                       class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3 py-2 text-xs text-gray-900 dark:text-white">
                            </div>

                            <div>
                                <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Review Date</label>
                                <input type="date" name="review_date" value="<?php echo htmlspecialchars($editPolicy['review_date'] ?? ''); ?>"
                                       class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3 py-2 text-xs text-gray-900 dark:text-white">
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Summary / Key Scope</label>
                            <textarea name="description" rows="3" placeholder="Brief summary of policy objectives and enforcement..."
                                      class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3.5 py-2 text-xs text-gray-900 dark:text-white"><?php echo htmlspecialchars($editPolicy['description'] ?? ''); ?></textarea>
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">
                                Policy Document (PDF / DOC) <?php echo $editPolicy ? '(Optional to replace)' : '*'; ?>
                            </label>
                            <input type="file" name="policy_file" <?php echo $editPolicy ? '' : 'required'; ?> accept=".pdf,.doc,.docx"
                                   class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-teal-50 file:text-teal-700">
                            <p class="text-[10px] text-gray-400 mt-1">Allowed: PDF, DOC, DOCX up to 10MB</p>
                            <?php if (!empty($editPolicy['file_path'])): ?>
                                <div class="mt-1.5 flex items-center gap-1.5 text-xs text-teal-600 font-bold">
                                    <i class="fa-solid fa-file-pdf"></i>
                                    <a href="../<?php echo htmlspecialchars($editPolicy['file_path']); ?>" target="_blank" class="hover:underline">View Current Document</a>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="flex items-center gap-2 pt-1">
                            <?php $isPublic = isset($editPolicy) ? (int)$editPolicy['is_public'] === 1 : true; ?>
                            <input type="checkbox" id="is_public_chk" name="is_public" value="1" <?php echo $isPublic ? 'checked' : ''; ?>
                                   class="rounded text-teal-600 focus:ring-teal-500">
                            <label for="is_public_chk" class="text-xs font-bold text-gray-700 dark:text-gray-300">Visible on public HR Policies portal</label>
                        </div>

                        <div class="flex gap-2 pt-2">
                            <button type="submit" class="flex-1 bg-teal-600 hover:bg-teal-700 text-white py-2.5 rounded-xl font-bold transition shadow-sm">
                                <?php echo $editPolicy ? 'Update Policy' : 'Publish HR Policy'; ?>
                            </button>
                            <?php if ($editPolicy): ?>
                                <a href="hr_policies.php" class="px-4 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 font-bold rounded-xl transition text-center">
                                    Cancel
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- Right Column: Documents Table (8 cols) -->
                <div class="lg:col-span-8 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="p-4 md:p-6 border-b border-gray-100 dark:border-gray-700 space-y-3">
                        <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                            <div>
                                <h3 class="text-base font-black text-gray-900 dark:text-white">Published HR Policies</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">All institutional guidelines and workplace compliance records.</p>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-teal-100 text-teal-800 dark:bg-teal-900/40 dark:text-teal-300 shrink-0">
                                <?php echo $totalPolicies; ?> policies
                            </span>
                        </div>

                        <!-- Filter Form -->
                        <form method="GET" action="hr_policies.php" class="flex flex-wrap items-center gap-2 pt-1">
                            <input type="text" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="Search policies..." class="flex-1 min-w-[150px] bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3 py-1.5 text-xs text-gray-900 dark:text-white focus:ring-2 focus:ring-teal-500">
                            
                            <select name="category" onchange="this.form.submit()" class="bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3 py-1.5 text-xs text-gray-900 dark:text-white">
                                <option value="">All Categories</option>
                                <option value="code_of_conduct" <?php echo $categoryFilter === 'code_of_conduct' ? 'selected' : ''; ?>>Code of Conduct</option>
                                <option value="posh_gender" <?php echo $categoryFilter === 'posh_gender' ? 'selected' : ''; ?>>POSH & Gender Safety</option>
                                <option value="child_safeguarding" <?php echo $categoryFilter === 'child_safeguarding' ? 'selected' : ''; ?>>Child Protection</option>
                                <option value="leave_benefits" <?php echo $categoryFilter === 'leave_benefits' ? 'selected' : ''; ?>>Leave & Benefits</option>
                                <option value="whistleblower" <?php echo $categoryFilter === 'whistleblower' ? 'selected' : ''; ?>>Whistleblower</option>
                            </select>

                            <button type="submit" class="px-3 py-1.5 bg-slate-800 text-white rounded-xl text-xs font-bold hover:bg-slate-900">Filter</button>
                            <?php if ($searchQuery || $categoryFilter): ?>
                                <a href="hr_policies.php" class="px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-xl text-xs font-bold hover:bg-gray-200">Reset</a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                            <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-500 dark:text-gray-400 font-bold uppercase text-[10px] tracking-wider">
                                <tr>
                                    <th class="py-3.5 px-4">Policy Code & Title</th>
                                    <th class="py-3.5 px-4">Category</th>
                                    <th class="py-3.5 px-4">Effective Date</th>
                                    <th class="py-3.5 px-4 text-center">File</th>
                                    <th class="py-3.5 px-4 text-center">Status</th>
                                    <th class="py-3.5 px-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                <?php if (empty($policies)): ?>
                                    <tr>
                                        <td colspan="6" class="py-8 text-center text-gray-400">No HR policies uploaded yet. Use the upload form on the left to add one.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($policies as $pol): ?>
                                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition">
                                            <td class="py-3.5 px-4">
                                                <span class="font-mono text-[10px] text-gray-400 block"><?php echo htmlspecialchars($pol['policy_code']); ?></span>
                                                <div class="font-bold text-gray-900 dark:text-white text-xs"><?php echo htmlspecialchars($pol['title']); ?></div>
                                                <span class="text-[10px] text-gray-400"><?php echo htmlspecialchars($pol['policy_version'] ?? 'v1.0'); ?></span>
                                            </td>
                                            <td class="py-3.5 px-4">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold <?php
                                                    echo match($pol['category']) {
                                                        'code_of_conduct' => 'bg-indigo-50 text-indigo-700',
                                                        'posh_gender' => 'bg-purple-50 text-purple-700',
                                                        'child_safeguarding' => 'bg-rose-50 text-rose-700',
                                                        'leave_benefits' => 'bg-teal-50 text-teal-700',
                                                        'whistleblower' => 'bg-amber-50 text-amber-700',
                                                        default => 'bg-blue-50 text-blue-700'
                                                    };
                                                ?>">
                                                    <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $pol['category']))); ?>
                                                </span>
                                            </td>
                                            <td class="py-3.5 px-4">
                                                <div class="text-gray-800 dark:text-gray-200"><?php echo !empty($pol['effective_date']) ? date('d M Y', strtotime($pol['effective_date'])) : '-'; ?></div>
                                            </td>
                                            <td class="py-3.5 px-4 text-center">
                                                <?php if (!empty($pol['file_path'])): ?>
                                                    <a href="../<?php echo htmlspecialchars($pol['file_path']); ?>" target="_blank" class="p-1.5 text-teal-600 hover:text-teal-800 font-bold inline-flex items-center gap-1" title="Download Policy">
                                                        <i class="fa-solid fa-file-pdf text-sm"></i>
                                                        <span class="text-[10px]"><?php echo htmlspecialchars($pol['file_size'] ?? 'PDF'); ?></span>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-gray-400 text-[10px]">No File</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-3.5 px-4 text-center">
                                                <form action="actions/hr_policy_logic.php" method="POST" class="inline">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                    <input type="hidden" name="action" value="toggle_public">
                                                    <input type="hidden" name="id" value="<?php echo $pol['id']; ?>">
                                                    <button type="submit" class="px-2 py-0.5 rounded-full text-[10px] font-bold transition <?php echo $pol['is_public'] ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600'; ?>">
                                                        <?php echo $pol['is_public'] ? 'Public' : 'Internal'; ?>
                                                    </button>
                                                </form>
                                            </td>
                                            <td class="py-3.5 px-4 text-right space-x-1">
                                                <a href="hr_policies.php?edit=<?php echo $pol['id']; ?>" class="p-1.5 text-gray-500 hover:text-teal-600" title="Edit">
                                                    <i class="fa-regular fa-pen-to-square"></i>
                                                </a>

                                                <form action="actions/hr_policy_logic.php" method="POST" class="inline" onsubmit="return confirm('Delete policy \'<?php echo addslashes($pol['title']); ?>\'?');">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?php echo $pol['id']; ?>">
                                                    <button type="submit" class="p-1.5 text-gray-400 hover:text-rose-600" title="Delete">
                                                        <i class="fa-regular fa-trash-can"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Footer -->
                    <?php echo render_admin_pagination($totalPolicies, $page, $perPage, ['search' => $searchQuery, 'category' => $categoryFilter]); ?>
                </div>

            </div>

        </main>
    </div>
</div>

<?php require 'includes/footer.php'; ?>
