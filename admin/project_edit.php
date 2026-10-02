<?php
// ============================================================
// admin/project_edit.php
// Project Management & Fund Utilization Ledger
// Features: Details, Media, Gallery, and Project Expenses (Raised vs Spent vs Remaining)
// ============================================================

require 'includes/header.php';
require '../config/db.php';
require_once '../includes/functions.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    setFlash('error', 'Invalid Project ID.');
    header('Location: projects.php');
    exit;
}

$csrfToken = generateCsrfToken();
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$isManagerOrAdmin = checkRole($pdo, 'manager');

$project = $pdo->query("SELECT * FROM projects WHERE id = $id")->fetch(PDO::FETCH_ASSOC);
if (!$project) {
    setFlash('error', 'Project not found.');
    header('Location: projects.php');
    exit;
}

$gallery = $pdo->query("SELECT * FROM project_gallery WHERE project_id = $id ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

// ── 1. PROJECT FINANCIAL LEDGER (Raised vs Spent vs Remaining) ─
$targetAmount = (float)$project['target_amount'];
$donationsTotal = (float)($pdo->query("SELECT COALESCE(SUM(amount), 0) FROM donations WHERE project_id = $id AND payment_status = 'Success'")->fetchColumn() ?: 0);
$donationsCount = (int)($pdo->query("SELECT COUNT(*) FROM donations WHERE project_id = $id AND payment_status = 'Success'")->fetchColumn() ?: 0);
$totalRaised = max((float)$project['raised_amount'], $donationsTotal);

// Fetch Expenses specifically linked to this Project
$projectExpenses = $pdo->query("
    SELECT e.*, c.category_name, c.icon as category_icon, u.name as added_by_name, ap.name as approved_by_name
    FROM expenses e
    LEFT JOIN expense_categories c ON e.category_id = c.id
    LEFT JOIN users u ON e.added_by = u.id
    LEFT JOIN users ap ON e.approved_by = ap.id
    WHERE e.project_id = $id
    ORDER BY e.date DESC, e.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$totalSpent = 0.0;
$pendingSpent = 0.0;
$approvedExpenseCount = 0;

foreach ($projectExpenses as $exp) {
    $amt = (float)$exp['amount'];
    if ($exp['approved_status'] === 'Approved' || $exp['approved_status'] === 'Paid') {
        $totalSpent += $amt;
        $approvedExpenseCount++;
    } elseif ($exp['approved_status'] === 'Pending') {
        $pendingSpent += $amt;
    }
}

$remainingBalance = $totalRaised - $totalSpent;
$utilizationPercent = ($totalRaised > 0) ? min(round(($totalSpent / $totalRaised) * 100, 1), 100) : 0;
$goalCompletionPercent = ($targetAmount > 0) ? min(round(($totalRaised / $targetAmount) * 100, 1), 100) : 0;

// Fetch Expense Categories for in-tab "+ Log Expense"
$expenseCategories = $pdo->query("SELECT id, category_name, icon FROM expense_categories WHERE is_active = 1 ORDER BY display_order ASC, category_name ASC")->fetchAll(PDO::FETCH_ASSOC);

$initialTab = cleanInput($_GET['tab'] ?? 'details');
if (!in_array($initialTab, ['details', 'media', 'gallery', 'expenses'], true)) {
    $initialTab = 'details';
}
?>

<div class="flex h-screen overflow-hidden bg-slate-50 dark:bg-gray-900"
    x-data="projectDetailManager({
        initialTab: '<?php echo $initialTab; ?>',
        expenses: <?php echo json_encode($projectExpenses); ?>
    })"
    x-init="init()">

    <?php require 'includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>
        
        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-8">

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <a href="projects.php" class="text-xs font-bold text-teal-600 dark:text-teal-400 hover:underline mb-2 inline-flex items-center gap-1">
                        <i class="fa-solid fa-arrow-left"></i> Back to Projects Directory
                    </a>
                    <h3 class="text-2xl md:text-3xl font-black text-gray-800 dark:text-white truncate max-w-[80vw]" title="<?php echo htmlspecialchars($project['title']); ?>">
                        <?php echo htmlspecialchars($project['title']); ?>
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Project configuration, visual media, gallery assets, and Fund Utilization Ledger.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" @click="tab = 'expenses'; isAddExpenseModalOpen = true" class="bg-[#0F8B8D] hover:bg-[#0c7274] text-white font-bold px-4 py-2.5 rounded-2xl shadow-md text-xs flex items-center gap-2 transition transform active:scale-95">
                        <i class="fa-solid fa-receipt"></i> Log Project Expense
                    </button>
                </div>
            </div>

            <!-- TAB NAVIGATION -->
            <div class="mt-4 border-b border-slate-200 dark:border-gray-700 flex overflow-x-auto gap-2">
                <button @click="tab = 'details'" 
                        :class="tab === 'details' ? 'border-b-2 border-[#0F8B8D] text-[#0F8B8D] dark:text-teal-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700'" 
                        class="px-5 py-3 font-bold text-xs transition whitespace-nowrap flex items-center gap-2">
                    <i class="fa-solid fa-pen-to-square"></i> Project Details
                </button>
                <button @click="tab = 'expenses'" 
                        :class="tab === 'expenses' ? 'border-b-2 border-[#0F8B8D] text-[#0F8B8D] dark:text-teal-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700'" 
                        class="px-5 py-3 font-bold text-xs transition whitespace-nowrap flex items-center gap-2">
                    <i class="fa-solid fa-scale-balanced"></i> Fund Utilization Ledger
                    <span class="px-2 py-0.5 rounded-full bg-teal-100 dark:bg-teal-900/40 text-[#0F8B8D] dark:text-teal-300 text-[10px] font-black">
                        <?php echo count($projectExpenses); ?>
                    </span>
                </button>
                <button @click="tab = 'media'" 
                        :class="tab === 'media' ? 'border-b-2 border-[#0F8B8D] text-[#0F8B8D] dark:text-teal-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700'" 
                        class="px-5 py-3 font-bold text-xs transition whitespace-nowrap flex items-center gap-2">
                    <i class="fa-solid fa-photo-film"></i> Media & QR
                </button>
                <button @click="tab = 'gallery'" 
                        :class="tab === 'gallery' ? 'border-b-2 border-[#0F8B8D] text-[#0F8B8D] dark:text-teal-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700'" 
                        class="px-5 py-3 font-bold text-xs transition whitespace-nowrap flex items-center gap-2">
                    <i class="fa-solid fa-images"></i> Gallery Assets (<?php echo count($gallery); ?>)
                </button>
            </div>

            <!-- ── TAB 1: FUND UTILIZATION LEDGER (PROJECT EXPENSES) ────── -->
            <div x-show="tab === 'expenses'" class="mt-6 space-y-6" x-transition.opacity>
                
                <!-- 4 KPI Summary Cards for Project Ledger -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    
                    <!-- Target Goal -->
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase text-gray-400">Target Budget</span>
                            <span class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 flex items-center justify-center text-xs">
                                <i class="fa-solid fa-bullseye"></i>
                            </span>
                        </div>
                        <p class="text-2xl font-black text-gray-800 dark:text-white mt-2">₹<?php echo number_format($targetAmount, 2); ?></p>
                        <p class="text-[11px] text-gray-400 mt-1"><?php echo $goalCompletionPercent; ?>% of goal raised</p>
                    </div>

                    <!-- Total Funds Raised (Income) -->
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase text-gray-400">Total Funds Raised</span>
                            <span class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 flex items-center justify-center text-xs">
                                <i class="fa-solid fa-arrow-down-long"></i>
                            </span>
                        </div>
                        <p class="text-2xl font-black text-emerald-600 mt-2">₹<?php echo number_format($totalRaised, 2); ?></p>
                        <p class="text-[11px] text-gray-400 mt-1"><?php echo $donationsCount; ?> verified donations</p>
                    </div>

                    <!-- Total Funds Spent (Expenses) -->
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase text-gray-400">Total Funds Spent</span>
                            <span class="w-8 h-8 rounded-xl bg-rose-50 dark:bg-rose-900/30 text-rose-600 flex items-center justify-center text-xs">
                                <i class="fa-solid fa-arrow-up-long"></i>
                            </span>
                        </div>
                        <p class="text-2xl font-black text-rose-600 mt-2">₹<?php echo number_format($totalSpent, 2); ?></p>
                        <p class="text-[11px] text-gray-400 mt-1"><?php echo $approvedExpenseCount; ?> approved expenses</p>
                    </div>

                    <!-- Remaining Available Balance -->
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase text-gray-400">Remaining Balance</span>
                            <span class="w-8 h-8 rounded-xl flex items-center justify-center text-xs <?php echo $remainingBalance >= 0 ? 'bg-teal-50 dark:bg-teal-900/30 text-[#0F8B8D]' : 'bg-rose-50 dark:bg-rose-900/30 text-rose-600'; ?>">
                                <i class="fa-solid <?php echo $remainingBalance >= 0 ? 'fa-wallet' : 'fa-triangle-exclamation'; ?>"></i>
                            </span>
                        </div>
                        <p class="text-2xl font-black mt-2 <?php echo $remainingBalance >= 0 ? 'text-teal-600 dark:text-teal-400' : 'text-rose-600'; ?>">
                            ₹<?php echo number_format($remainingBalance, 2); ?>
                        </p>
                        <p class="text-[11px] font-bold mt-1 <?php echo $remainingBalance >= 0 ? 'text-emerald-500' : 'text-rose-500'; ?>">
                            <?php echo $remainingBalance >= 0 ? 'Available for project aid' : 'Deficit / Over-budget'; ?>
                        </p>
                    </div>

                </div>

                <!-- Fund Utilization Progress Bar Strip -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-3xl border border-slate-200 dark:border-gray-700 shadow-sm">
                    <div class="flex items-center justify-between text-xs font-bold mb-2">
                        <span class="text-gray-700 dark:text-gray-300 flex items-center gap-2">
                            <i class="fa-solid fa-chart-pie text-[#0F8B8D]"></i> Fund Utilization Rate (Spent vs Raised)
                        </span>
                        <span class="text-teal-600 dark:text-teal-400"><?php echo $utilizationPercent; ?>% Utilized</span>
                    </div>
                    <div class="w-full bg-slate-100 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                        <div class="bg-gradient-to-r from-teal-500 to-emerald-500 h-3 rounded-full transition-all duration-500" style="width: <?php echo $utilizationPercent; ?>%"></div>
                    </div>
                    <div class="flex justify-between text-[11px] text-gray-400 mt-2">
                        <span>Spent: <strong>₹<?php echo number_format($totalSpent, 2); ?></strong></span>
                        <?php if ($pendingSpent > 0): ?>
                            <span class="text-amber-500 font-medium">Pending Approval: ₹<?php echo number_format($pendingSpent, 2); ?></span>
                        <?php endif; ?>
                        <span>Remaining Reserve: <strong>₹<?php echo number_format($remainingBalance, 2); ?></strong></span>
                    </div>
                </div>

                <!-- Project Expenses Table Section -->
                <div class="bg-white dark:bg-gray-800 rounded-3xl border border-slate-200 dark:border-gray-700 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-slate-100 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <h4 class="text-sm font-bold text-gray-800 dark:text-white">Project Expense Ledger (<?php echo count($projectExpenses); ?> Records)</h4>
                            <p class="text-xs text-gray-400">All expenditures and material purchases charged to this project</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <input type="text" x-model="searchExpense" placeholder="Search voucher, purpose..." class="text-xs p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none w-56">
                            <button type="button" @click="isAddExpenseModalOpen = true" class="bg-[#0F8B8D] hover:bg-[#0c7274] text-white font-bold py-2 px-3.5 rounded-xl shadow-sm text-xs flex items-center gap-1.5 transition">
                                <i class="fa-solid fa-plus"></i> Add Expense
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                            <thead class="bg-slate-50 dark:bg-gray-700/50 text-[11px] font-bold uppercase text-gray-500 dark:text-gray-400 border-b border-slate-200 dark:border-gray-700">
                                <tr>
                                    <th class="p-3.5 text-center">#</th>
                                    <th class="p-3.5">Date</th>
                                    <th class="p-3.5">Voucher Code</th>
                                    <th class="p-3.5">Category</th>
                                    <th class="p-3.5 text-right">Amount (₹)</th>
                                    <th class="p-3.5">Purpose & Payee</th>
                                    <th class="p-3.5 text-center">Bill / Proof</th>
                                    <th class="p-3.5">Added By</th>
                                    <th class="p-3.5 text-center">Status</th>
                                    <th class="p-3.5 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-gray-700">
                                <template x-for="(exp, idx) in filteredExpenses" :key="exp.id">
                                    <tr class="hover:bg-slate-50/70 dark:hover:bg-gray-700/30 transition">
                                        <td class="p-3.5 text-center font-bold text-gray-400" x-text="idx + 1"></td>
                                        <td class="p-3.5 font-medium whitespace-nowrap" x-text="formatDate(exp.date)"></td>
                                        <td class="p-3.5 font-bold text-indigo-600 dark:text-indigo-400" x-text="exp.expense_code || ('EXP-' + exp.id)"></td>
                                        <td class="p-3.5">
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-slate-100 dark:bg-gray-700 text-slate-800 dark:text-slate-200 font-bold text-[11px]">
                                                <i class="fa-solid" :class="exp.category_icon || 'fa-receipt'"></i>
                                                <span x-text="exp.category_name || 'General'"></span>
                                            </span>
                                        </td>
                                        <td class="p-3.5 text-right font-black text-gray-900 dark:text-white text-sm">
                                            ₹<span x-text="formatCurrency(exp.amount)"></span>
                                        </td>
                                        <td class="p-3.5 max-w-xs">
                                            <div class="font-bold text-gray-900 dark:text-white truncate" :title="exp.purpose" x-text="exp.purpose"></div>
                                            <div class="text-[10px] text-gray-400 mt-0.5" x-show="exp.vendor_payee_name">
                                                Paid to: <span x-text="exp.vendor_payee_name"></span>
                                            </div>
                                        </td>
                                        <td class="p-3.5 text-center">
                                            <template x-if="exp.bill_document_path">
                                                <div>
                                                    <template x-if="isPdf(exp.bill_document_path)">
                                                        <a :href="'../' + exp.bill_document_path" target="_blank" class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-rose-50 dark:bg-rose-900/30 text-rose-600 hover:scale-110 transition shadow-sm">
                                                            <i class="fa-solid fa-file-pdf"></i>
                                                        </a>
                                                    </template>
                                                    <template x-if="!isPdf(exp.bill_document_path)">
                                                        <button type="button" @click="openLightbox('../' + exp.bill_document_path)" class="inline-block relative group">
                                                            <img :src="'../' + exp.bill_document_path" class="w-7 h-7 rounded-lg object-cover border border-slate-200 shadow-sm group-hover:scale-110 transition">
                                                        </button>
                                                    </template>
                                                </div>
                                            </template>
                                            <template x-if="!exp.bill_document_path">
                                                <span class="text-gray-300 dark:text-gray-600 text-[10px] italic">No bill</span>
                                            </template>
                                        </td>
                                        <td class="p-3.5 text-gray-600 dark:text-gray-400" x-text="exp.added_by_name || 'Staff'"></td>
                                        <td class="p-3.5 text-center">
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase"
                                                  :class="{
                                                      'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300': exp.approved_status === 'Approved',
                                                      'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300': exp.approved_status === 'Paid',
                                                      'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300': exp.approved_status === 'Pending',
                                                      'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300': exp.approved_status === 'Rejected'
                                                  }"
                                                  x-text="exp.approved_status"></span>
                                        </td>
                                        <td class="p-3.5 text-center">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <a href="expenses.php" class="p-1.5 rounded-lg text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition" title="Open in Expense Tracker">
                                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <template x-if="filteredExpenses.length === 0">
                                    <tr>
                                        <td colspan="10" class="p-8 text-center text-xs text-gray-400">
                                            <i class="fa-solid fa-receipt text-3xl mb-2 text-gray-300 block"></i>
                                            No expenses logged for this project yet. Click "Add Expense" to record project costs.
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <!-- ── TAB 2: DETAILS ────────────────────────────────────────── -->
            <div x-show="tab === 'details'" class="mt-6 bg-white dark:bg-gray-800 p-5 md:p-6 rounded-3xl shadow border dark:border-gray-700" x-transition.opacity>
                <form action="actions/project_crud.php" method="POST" class="space-y-5">
                    <input type="hidden" name="action" value="update_details">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                    <div>
                        <label class="block text-xs font-bold mb-1 dark:text-gray-300">Project Title *</label>
                        <input type="text" name="title" required value="<?php echo htmlspecialchars($project['title']); ?>" class="w-full px-4 py-2.5 text-xs font-medium border rounded-xl dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-[#0F8B8D] outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold mb-1 dark:text-gray-300">Description *</label>
                        <textarea name="description" required rows="5" class="w-full px-4 py-2.5 text-xs font-medium border rounded-xl dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-[#0F8B8D] outline-none"><?php echo htmlspecialchars($project['description']); ?></textarea>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div>
                            <label class="block text-xs font-bold mb-1 dark:text-gray-300">Target Amount (₹) *</label>
                            <input type="number" name="target_amount" min="1" required value="<?php echo htmlspecialchars($project['target_amount']); ?>" class="w-full px-4 py-2.5 text-xs font-bold border rounded-xl dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-[#0F8B8D] outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold mb-1 dark:text-gray-300">Raised Amount (₹)</label>
                            <input type="number" name="raised_amount" min="0" value="<?php echo htmlspecialchars($project['raised_amount']); ?>" class="w-full px-4 py-2.5 text-xs font-bold border rounded-xl dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-[#0F8B8D] outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold mb-1 dark:text-gray-300">Status</label>
                            <select name="status" class="w-full px-4 py-2.5 text-xs font-medium border rounded-xl dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option <?php if ($project['status'] == 'Active') echo 'selected'; ?>>Active</option>
                                <option <?php if ($project['status'] == 'Completed') echo 'selected'; ?>>Completed</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold mb-1 dark:text-gray-300">YouTube Video URL</label>
                        <input type="url" name="video_url" pattern="^(https?:\/\/)?(www\.)?(youtube\.com|youtu\.be|youtube-nocookie\.com)\/.*$" placeholder="https://www.youtube.com/watch?v=..." value="<?php echo htmlspecialchars($project['video_url'] ?? ''); ?>" class="w-full px-4 py-2.5 text-xs font-medium border rounded-xl dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>

                    <div class="pt-2">
                        <button class="bg-[#0F8B8D] hover:bg-[#0c7274] text-white px-6 py-2.5 rounded-xl font-bold text-xs transition shadow-md">Save Changes</button>
                    </div>
                </form>
            </div>

            <!-- ── TAB 3: MEDIA ──────────────────────────────────────────── -->
            <div x-show="tab === 'media'" class="mt-6 bg-white dark:bg-gray-800 p-5 md:p-6 rounded-3xl shadow border dark:border-gray-700" x-transition.opacity>
                <form action="actions/project_crud.php" method="POST" enctype="multipart/form-data" class="space-y-8">
                    <input type="hidden" name="action" value="update_media">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="bg-slate-50 dark:bg-gray-700/50 p-4 rounded-2xl border dark:border-gray-600">
                            <label class="block text-xs font-bold mb-3 dark:text-gray-300">Thumbnail Image (JPG, PNG, WEBP)</label>
                            <div class="mb-4 aspect-video bg-white dark:bg-gray-800 rounded-xl overflow-hidden border dark:border-gray-600 flex items-center justify-center">
                                <img src="../<?php echo $project['thumbnail_image']; ?>" class="max-w-full max-h-full object-contain">
                            </div>
                            <input type="file" name="thumbnail" accept="image/jpeg,image/png,image/webp" class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:bg-teal-50 file:text-[#0F8B8D]">
                        </div>

                        <div class="bg-slate-50 dark:bg-gray-700/50 p-4 rounded-2xl border dark:border-gray-600">
                            <label class="block text-xs font-bold mb-3 dark:text-gray-300">UPI QR Code (JPG, PNG, WEBP)</label>
                            <div class="mb-4 aspect-[3/4] bg-white dark:bg-gray-800 rounded-xl overflow-hidden border dark:border-gray-600 flex items-center justify-center">
                                <?php if ($project['upi_qr_image']): ?>
                                    <img src="../<?php echo $project['upi_qr_image']; ?>" class="max-w-full max-h-full object-contain">
                                <?php else: ?>
                                    <span class="text-gray-400 text-xs">No QR Uploaded</span>
                                <?php endif; ?>
                            </div>
                            <input type="file" name="qr" accept="image/jpeg,image/png,image/webp" class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:bg-teal-50 file:text-[#0F8B8D]">
                        </div>
                    </div>

                    <div class="pt-2 border-t dark:border-gray-700 flex justify-end">
                        <button class="bg-[#0F8B8D] hover:bg-[#0c7274] text-white px-6 py-2.5 rounded-xl font-bold text-xs transition shadow-md">Update Media</button>
                    </div>
                </form>
            </div>

            <!-- ── TAB 4: GALLERY ────────────────────────────────────────── -->
            <div x-show="tab === 'gallery'" class="mt-6 bg-white dark:bg-gray-800 p-5 md:p-6 rounded-3xl shadow border dark:border-gray-700" x-transition.opacity>
                <form action="actions/project_crud.php" method="POST" enctype="multipart/form-data" class="mb-8 bg-slate-50 dark:bg-gray-700/50 p-4 rounded-2xl border dark:border-gray-600">
                    <input type="hidden" name="action" value="add_gallery">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                    <label class="block text-xs font-bold mb-2 dark:text-gray-300">Add New Images (JPG, PNG, WEBP)</label>
                    <div class="flex gap-3 items-center">
                        <input type="file" name="gallery[]" multiple accept="image/jpeg,image/png,image/webp" class="block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-teal-50 file:text-[#0F8B8D]">
                        <button class="bg-[#0F8B8D] hover:bg-[#0c7274] text-white px-6 py-2 rounded-xl text-xs font-bold shadow whitespace-nowrap">Upload</button>
                    </div>
                </form>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                    <?php foreach ($gallery as $img): ?>
                        <div class="group relative bg-gray-100 dark:bg-gray-700 rounded-xl overflow-hidden border dark:border-gray-600">
                            <div class="aspect-square flex items-center justify-center p-1">
                                <img src="../<?php echo $img['image_path']; ?>" class="max-w-full max-h-full object-contain rounded-lg">
                            </div>

                            <button @click="targetImageId = <?php echo $img['id']; ?>; isDeleteImageModalOpen = true"
                                class="absolute top-2 right-2 bg-red-500 text-white p-1.5 rounded-full opacity-0 group-hover:opacity-100 transition shadow-lg transform hover:scale-110">
                                <i class="fa-solid fa-xmark text-xs"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>

                    <?php if (empty($gallery)): ?>
                        <div class="col-span-full py-12 text-center text-gray-400 border-2 border-dashed rounded-2xl dark:border-gray-700 text-xs">
                            No images in gallery. Upload some!
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- DANGER ZONE: DELETE PROJECT -->
            <div class="mt-8 border-t dark:border-gray-700 pt-6">
                <div class="bg-rose-50 dark:bg-rose-900/10 border border-rose-100 dark:border-rose-900/30 rounded-2xl p-4 flex flex-col md:flex-row justify-between items-center gap-4">
                    <div>
                        <h4 class="text-rose-700 dark:text-rose-400 font-bold text-sm">Delete Project</h4>
                        <p class="text-xs text-rose-600/80 dark:text-rose-400/70">This will permanently remove the project and un-allocate linked expenses.</p>
                    </div>
                    <button @click="isDeleteProjectModalOpen = true" class="bg-white dark:bg-rose-900/20 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800 px-4 py-2 rounded-xl text-xs font-bold hover:bg-rose-50 transition">Delete Project</button>
                </div>
            </div>

            <!-- ── MODAL: ADD EXPENSE SPECIFIC TO THIS PROJECT ────────── -->
            <div x-show="isAddExpenseModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-transition.opacity>
                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden border border-slate-200 dark:border-gray-700" @click.away="isAddExpenseModalOpen = false">
                    
                    <div class="p-5 border-b border-slate-100 dark:border-gray-700 flex items-center justify-between bg-slate-50 dark:bg-gray-700/50">
                        <div class="flex items-center gap-3">
                            <span class="w-9 h-9 rounded-xl bg-teal-50 dark:bg-teal-900/40 text-[#0F8B8D] flex items-center justify-center text-base">
                                <i class="fa-solid fa-receipt"></i>
                            </span>
                            <div>
                                <h4 class="text-base font-black text-gray-900 dark:text-white">Record Project Expense</h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Allocate cost to: <strong><?php echo htmlspecialchars($project['title']); ?></strong></p>
                            </div>
                        </div>
                        <button type="button" @click="isAddExpenseModalOpen = false" class="text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 p-2 rounded-xl transition">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>

                    <form action="actions/expense_logic.php" method="POST" enctype="multipart/form-data" class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="action" value="add_expense">
                        <input type="hidden" name="project_id" value="<?php echo $id; ?>">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Expense Category <span class="text-red-500">*</span></label>
                                <select name="category_id" required class="w-full px-3.5 py-2.5 text-xs font-medium rounded-xl border border-slate-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                                    <option value="">-- Select Category --</option>
                                    <?php foreach ($expenseCategories as $cat): ?>
                                        <option value="<?php echo (int)$cat['id']; ?>">
                                            <?php echo htmlspecialchars($cat['category_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Amount (₹) <span class="text-red-500">*</span></label>
                                <input type="number" step="0.01" min="0.01" name="amount" required placeholder="0.00" class="w-full px-3.5 py-2.5 text-xs font-bold rounded-xl border border-slate-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Date <span class="text-red-500">*</span></label>
                                <input type="date" name="date" required value="<?php echo date('Y-m-d'); ?>" class="w-full px-3.5 py-2.5 text-xs font-medium rounded-xl border border-slate-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white outline-none">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Payment Mode</label>
                                <select name="payment_mode" class="w-full px-3.5 py-2.5 text-xs font-medium rounded-xl border border-slate-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white outline-none">
                                    <option value="Cash" selected>Cash</option>
                                    <option value="UPI">UPI</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="Cheque">Cheque</option>
                                    <option value="Credit/Debit Card">Card</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Purpose / Items Purchased <span class="text-red-500">*</span></label>
                            <textarea name="purpose" required rows="2" placeholder="e.g. Distributed 100 food packets, construction cement bags..." class="w-full px-3.5 py-2.5 text-xs font-medium rounded-xl border border-slate-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white outline-none"></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Paid To / Payee Vendor</label>
                                <input type="text" name="vendor_payee_name" placeholder="Vendor / Shop Name" class="w-full px-3.5 py-2.5 text-xs font-medium rounded-xl border border-slate-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white outline-none">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Bill / Receipt Document</label>
                                <input type="file" name="bill_document" accept="image/jpeg,image/png,image/webp,application/pdf" class="w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:bg-teal-50 file:text-[#0F8B8D]">
                            </div>
                        </div>

                        <div class="flex gap-3 pt-4 border-t border-slate-100 dark:border-gray-700">
                            <button type="submit" class="flex-1 bg-[#0F8B8D] hover:bg-[#0c7274] text-white font-bold py-2.5 rounded-xl shadow-md text-xs transition">
                                <i class="fa-solid fa-check"></i> Save Project Expense
                            </button>
                            <button type="button" @click="isAddExpenseModalOpen = false" class="px-5 bg-slate-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-bold py-2.5 rounded-xl text-xs">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- LIGHTBOX IMAGE MODAL -->
            <div x-show="isLightboxOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md" x-transition.opacity @click.self="isLightboxOpen = false">
                <div class="relative max-w-3xl max-h-[85vh] overflow-hidden rounded-2xl bg-white dark:bg-gray-900 p-2">
                    <button type="button" @click="isLightboxOpen = false" class="absolute top-4 right-4 z-10 w-8 h-8 rounded-full bg-black/60 text-white flex items-center justify-center hover:bg-black transition">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                    <img :src="lightboxSrc" class="max-h-[80vh] w-auto mx-auto object-contain rounded-xl">
                </div>
            </div>

            <!-- DELETE PROJECT MODAL -->
            <div x-show="isDeleteProjectModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-cloak>
                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl w-full max-w-sm text-center p-6 border dark:border-gray-700" @click.away="isDeleteProjectModalOpen = false">
                    <div class="mx-auto w-12 h-12 bg-rose-100 dark:bg-rose-900/30 text-rose-600 rounded-2xl flex items-center justify-center mb-4 text-xl">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <h3 class="text-base font-black text-gray-900 dark:text-white">Delete Project?</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">This action is irreversible and removes all media.</p>
                    <div class="mt-6 flex gap-3">
                        <button @click="isDeleteProjectModalOpen = false" class="flex-1 rounded-xl border dark:border-gray-600 py-2.5 text-xs font-bold text-gray-700 dark:text-gray-300">Cancel</button>
                        <form action="actions/project_crud.php" method="POST" class="flex-1">
                            <input type="hidden" name="action" value="delete_project">
                            <input type="hidden" name="id" value="<?php echo $id; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                            <button class="w-full rounded-xl bg-rose-600 py-2.5 text-xs font-bold text-white hover:bg-rose-700 shadow-md">Delete</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- DELETE IMAGE MODAL -->
            <div x-show="isDeleteImageModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-cloak>
                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl w-full max-w-sm text-center p-6 border dark:border-gray-700" @click.away="isDeleteImageModalOpen = false">
                    <div class="mx-auto w-12 h-12 bg-rose-100 dark:bg-rose-900/30 text-rose-600 rounded-2xl flex items-center justify-center mb-4 text-xl">
                        <i class="fa-solid fa-trash"></i>
                    </div>
                    <h3 class="text-base font-black text-gray-900 dark:text-white">Remove Image?</h3>
                    <div class="mt-6 flex gap-3">
                        <button @click="isDeleteImageModalOpen = false" class="flex-1 rounded-xl border dark:border-gray-600 py-2 text-xs font-bold text-gray-700 dark:text-gray-300">Cancel</button>
                        <form action="actions/project_crud.php" method="POST" class="flex-1">
                            <input type="hidden" name="action" value="delete_gallery_image">
                            <input type="hidden" name="image_id" :value="targetImageId">
                            <input type="hidden" name="project_id" value="<?php echo $id; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                            <button class="w-full rounded-xl bg-rose-600 py-2 text-xs font-bold text-white hover:bg-rose-700 shadow">Remove</button>
                        </form>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<script>
    function projectDetailManager(config) {
        return {
            tab: config.initialTab || 'details',
            expenses: config.expenses || [],
            searchExpense: '',
            isAddExpenseModalOpen: false,
            isDeleteProjectModalOpen: false,
            isDeleteImageModalOpen: false,
            isLightboxOpen: false,
            lightboxSrc: '',
            targetImageId: null,

            init() {},

            openLightbox(src) {
                this.lightboxSrc = src;
                this.isLightboxOpen = true;
            },

            isPdf(path) {
                if (!path) return false;
                return path.toLowerCase().endsWith('.pdf');
            },

            get filteredExpenses() {
                if (!this.searchExpense) return this.expenses;
                const q = this.searchExpense.toLowerCase();
                return this.expenses.filter(e => 
                    (e.expense_code && e.expense_code.toLowerCase().includes(q)) ||
                    (e.purpose && e.purpose.toLowerCase().includes(q)) ||
                    (e.vendor_payee_name && e.vendor_payee_name.toLowerCase().includes(q)) ||
                    (e.category_name && e.category_name.toLowerCase().includes(q))
                );
            },

            formatCurrency(val) {
                const num = parseFloat(val) || 0;
                return num.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },

            formatDate(d) {
                if (!d) return '-';
                const parts = d.split('-');
                if (parts.length === 3) {
                    return `${parts[2]}-${parts[1]}-${parts[0]}`;
                }
                return d;
            }
        };
    }
</script>

<?php require 'includes/footer.php'; ?>