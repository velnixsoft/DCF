<?php
// ============================================================
// admin/expenses.php
// Expense Management System: Add, Track, Approve, and Review Expenses
// Features: Category-wise classification, Project linking, Document uploads, Approval Workflow
// ============================================================

require_once '../config/db.php';
require_once '../includes/functions.php';

$csrfToken = generateCsrfToken();

if (!canAccessModule($pdo, 'coordinator', 'page.expenses')) {
    setFlash('error', 'Access denied. You do not have permission to view Expenses.');
    header('Location: dashboard.php');
    exit;
}

require 'includes/header.php';

$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$isManagerOrAdmin = checkRole($pdo, 'manager');

// Fetch Expense Categories
$categories = $pdo->query("SELECT id, category_name, category_slug, icon FROM expense_categories WHERE is_active = 1 ORDER BY display_order ASC, category_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch Active Projects
$projects = $pdo->query("SELECT id, title FROM projects ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch Expenses with Categories, Projects, and Users
$sql = "SELECT 
    e.*,
    c.category_name,
    c.icon as category_icon,
    p.title as project_name,
    u.name as added_by_name,
    u.role as added_by_role,
    ap.name as approved_by_name
    FROM expenses e
    LEFT JOIN expense_categories c ON e.category_id = c.id
    LEFT JOIN projects p ON e.project_id = p.id
    LEFT JOIN users u ON e.added_by = u.id
    LEFT JOIN users ap ON e.approved_by = ap.id
    ORDER BY e.date DESC, e.id DESC";

$expenses = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Calculate KPI Summary
$totalAmount = 0.0;
$totalCount = count($expenses);
$pendingCount = 0;
$pendingAmount = 0.0;
$approvedAmount = 0.0;
$thisMonthAmount = 0.0;
$currentMonthStr = date('Y-m');

foreach ($expenses as $exp) {
    $amt = (float)$exp['amount'];
    $totalAmount += $amt;
    if ($exp['approved_status'] === 'Pending') {
        $pendingCount++;
        $pendingAmount += $amt;
    } elseif ($exp['approved_status'] === 'Approved' || $exp['approved_status'] === 'Paid') {
        $approvedAmount += $amt;
    }
    if (strpos($exp['date'], $currentMonthStr) === 0) {
        $thisMonthAmount += $amt;
    }
}

$initialCategory = cleanInput($_GET['category'] ?? '');
$initialDateRange = cleanInput($_GET['date_range'] ?? 'all');
$initialStatus = cleanInput($_GET['status'] ?? '');
$initialProject = cleanInput($_GET['project'] ?? '');
?>

<div class="flex h-screen overflow-hidden bg-slate-50 dark:bg-gray-900"
    x-data="expenseManager({
        initialCategory: '<?php echo htmlspecialchars(addslashes($initialCategory)); ?>',
        initialDateRange: '<?php echo htmlspecialchars(addslashes($initialDateRange)); ?>',
        initialStatus: '<?php echo htmlspecialchars(addslashes($initialStatus)); ?>',
        initialProject: '<?php echo htmlspecialchars(addslashes($initialProject)); ?>',
        today: '<?php echo date('Y-m-d'); ?>'
    })"
    x-init="init()">
    <?php require 'includes/sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>
        
        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-8">

            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <div class="flex items-center gap-3">
                        <span class="w-10 h-10 rounded-2xl bg-amber-50 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 flex items-center justify-center text-lg shadow-sm">
                            <i class="fa-solid fa-receipt"></i>
                        </span>
                        <h3 class="text-2xl md:text-3xl font-black text-gray-800 dark:text-white">
                            Expense Management
                        </h3>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Record operational expenses, attach bills/receipts, allocate to projects, and process approval workflows.
                    </p>
                </div>

                <!-- Action Buttons: Open Add Expense Modal & Report -->
                <div class="flex flex-wrap items-center gap-2.5">
                    <a href="income_vs_expense.php" class="bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 hover:bg-slate-50 dark:hover:bg-gray-700 font-bold px-4 py-2.5 rounded-2xl shadow-sm text-xs flex items-center gap-2 transition">
                        <i class="fa-solid fa-scale-balanced text-teal-600"></i> Income vs Expense Report
                    </a>
                    <button type="button" @click="openAddModal()" 
                            class="bg-[#0F8B8D] hover:bg-[#0c7274] text-white font-bold py-2.5 px-5 rounded-2xl shadow-md transition flex items-center gap-2 text-xs transform active:scale-95">
                        <i class="fa-solid fa-plus"></i> Add New Expense
                    </button>
                </div>
            </div>

            <!-- KPI Metric Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                
                <!-- Total Expenses -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase text-gray-400">Total Recorded</span>
                        <span class="w-9 h-9 rounded-xl bg-teal-50 dark:bg-teal-900/30 text-[#0F8B8D] flex items-center justify-center text-sm">
                            <i class="fa-solid fa-receipt"></i>
                        </span>
                    </div>
                    <p class="text-2xl font-black text-gray-800 dark:text-white mt-2">₹<?php echo number_format($totalAmount, 2); ?></p>
                    <p class="text-[11px] text-gray-400 mt-1"><?php echo $totalCount; ?> total expense entries</p>
                </div>

                <!-- This Month's Expenses -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase text-gray-400">This Month (<?php echo date('M Y'); ?>)</span>
                        <span class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 flex items-center justify-center text-sm">
                            <i class="fa-solid fa-calendar-day"></i>
                        </span>
                    </div>
                    <p class="text-2xl font-black text-blue-600 mt-2">₹<?php echo number_format($thisMonthAmount, 2); ?></p>
                    <p class="text-[11px] text-gray-400 mt-1">Current billing cycle</p>
                </div>

                <!-- Approved & Paid -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase text-gray-400">Approved / Paid</span>
                        <span class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 flex items-center justify-center text-sm">
                            <i class="fa-solid fa-circle-check"></i>
                        </span>
                    </div>
                    <p class="text-2xl font-black text-emerald-600 mt-2">₹<?php echo number_format($approvedAmount, 2); ?></p>
                    <p class="text-[11px] text-gray-400 mt-1">Settled operational costs</p>
                </div>

                <!-- Pending Approvals -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase text-gray-400">Pending Review</span>
                        <span class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 flex items-center justify-center text-sm">
                            <i class="fa-solid fa-hourglass-half"></i>
                        </span>
                    </div>
                    <p class="text-2xl font-black text-amber-600 mt-2"><?php echo $pendingCount; ?></p>
                    <p class="text-[11px] text-gray-400 mt-1">₹<?php echo number_format($pendingAmount, 2); ?> awaiting approval</p>
                </div>

            </div>

            <!-- ============================================================ -->
            <!-- ADVANCED CATEGORY & DATE-WISE EXPENSE FILTER COMPONENT       -->
            <!-- ============================================================ -->
            <div class="bg-white dark:bg-gray-800 p-5 md:p-6 rounded-3xl shadow-sm border border-slate-200 dark:border-gray-700 mb-6 space-y-4">
                
                <!-- 1. Quick Category Filter Pills Strip -->
                <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-thin">
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider whitespace-nowrap mr-1 flex items-center gap-1.5">
                        <i class="fa-solid fa-tags text-teal-600"></i> Category:
                    </span>
                    <button type="button" @click="categoryFilter = ''" 
                            :class="categoryFilter === '' ? 'bg-[#0F8B8D] text-white shadow-sm font-bold' : 'bg-slate-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-slate-200 dark:hover:bg-gray-600 font-medium'"
                            class="px-3.5 py-1.5 rounded-xl text-xs whitespace-nowrap transition flex items-center gap-1.5">
                        <i class="fa-solid fa-layer-group"></i> All (<?php echo $totalCount; ?>)
                    </button>
                    <?php foreach ($categories as $cat): ?>
                        <button type="button" @click="categoryFilter = '<?php echo htmlspecialchars(addslashes($cat['category_name'])); ?>'" 
                                :class="categoryFilter === '<?php echo htmlspecialchars(addslashes($cat['category_name'])); ?>' ? 'bg-[#0F8B8D] text-white shadow-sm font-bold' : 'bg-slate-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-slate-200 dark:hover:bg-gray-600 font-medium'"
                                class="px-3.5 py-1.5 rounded-xl text-xs whitespace-nowrap transition flex items-center gap-1.5">
                            <i class="fa-solid <?php echo htmlspecialchars($cat['icon'] ?: 'fa-tag'); ?>"></i>
                            <?php echo htmlspecialchars($cat['category_name']); ?>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold" 
                                  :class="categoryFilter === '<?php echo htmlspecialchars(addslashes($cat['category_name'])); ?>' ? 'bg-white/25 text-white' : 'bg-slate-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300'"
                                  x-text="countByCategory('<?php echo htmlspecialchars(addslashes($cat['category_name'])); ?>')">
                            </span>
                        </button>
                    <?php endforeach; ?>
                </div>

                <!-- 2. Detailed Multi-Filter Controls Grid -->
                <div class="pt-3 border-t border-slate-100 dark:border-gray-700 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                    
                    <!-- Search Input -->
                    <div class="lg:col-span-2">
                        <label class="text-[11px] font-bold text-gray-600 dark:text-gray-400 block mb-1">
                            <i class="fa-solid fa-magnifying-glass text-teal-600 mr-1"></i> Search Expenses
                        </label>
                        <div class="relative">
                            <input type="text" x-model="search" placeholder="Voucher code, purpose, vendor, staff..." 
                                   class="w-full text-xs p-2.5 pl-8 pr-8 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                            <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 text-gray-400 text-xs"></i>
                            <button type="button" x-show="search" @click="search = ''" class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600 dark:hover:text-white text-xs">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Date Period Selector -->
                    <div>
                        <label class="text-[11px] font-bold text-gray-600 dark:text-gray-400 block mb-1">
                            <i class="fa-solid fa-calendar-days text-teal-600 mr-1"></i> Date Period
                        </label>
                        <select x-model="dateRange" class="w-full text-xs font-medium p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                            <option value="all">All Time</option>
                            <option value="today">Today (<?php echo date('d M'); ?>)</option>
                            <option value="yesterday">Yesterday</option>
                            <option value="this_week">This Week</option>
                            <option value="current_month">Current Month (<?php echo date('M Y'); ?>)</option>
                            <option value="prev_month">Previous Month</option>
                            <option value="this_quarter">This Quarter</option>
                            <option value="this_year">This Year (<?php echo date('Y'); ?>)</option>
                            <option value="custom">Custom Date Range...</option>
                        </select>
                    </div>

                    <!-- Category Dropdown Filter -->
                    <div>
                        <label class="text-[11px] font-bold text-gray-600 dark:text-gray-400 block mb-1">
                            <i class="fa-solid fa-folder-tree text-teal-600 mr-1"></i> Category
                        </label>
                        <select x-model="categoryFilter" class="w-full text-xs font-medium p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['category_name']); ?>">
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Approval Status Filter -->
                    <div>
                        <label class="text-[11px] font-bold text-gray-600 dark:text-gray-400 block mb-1">
                            <i class="fa-solid fa-circle-check text-indigo-600 mr-1"></i> Approval Status
                        </label>
                        <select x-model="statusFilter" class="w-full text-xs font-medium p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                            <option value="">All Statuses</option>
                            <option value="Pending">Pending Review</option>
                            <option value="Approved">Approved</option>
                            <option value="Paid">Paid / Settled</option>
                            <option value="Rejected">Rejected</option>
                        </select>
                    </div>

                    <!-- Project Link Filter -->
                    <div>
                        <label class="text-[11px] font-bold text-gray-600 dark:text-gray-400 block mb-1">
                            <i class="fa-solid fa-hand-holding-dollar text-teal-600 mr-1"></i> Project Allocation
                        </label>
                        <select x-model="projectFilter" class="w-full text-xs font-medium p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                            <option value="">All Allocations</option>
                            <option value="General">General (No Project)</option>
                            <?php foreach ($projects as $p): ?>
                                <option value="<?php echo htmlspecialchars($p['title']); ?>">
                                    <?php echo htmlspecialchars($p['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                </div>

                <!-- 3. Dynamic Custom Date Picker (Shows when dateRange === 'custom') -->
                <div x-show="dateRange === 'custom'" x-cloak class="p-4 bg-slate-50 dark:bg-gray-700/50 rounded-2xl border border-dashed border-teal-300 dark:border-teal-700 flex flex-wrap items-center gap-4" x-transition>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-teal-700 dark:text-teal-300 flex items-center gap-1">
                            <i class="fa-solid fa-calendar-week"></i> Custom Date Range:
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="text-xs text-gray-500 dark:text-gray-400">From:</label>
                        <input type="date" x-model="customStart" :max="customEnd || today" 
                               class="text-xs p-2 border rounded-xl bg-white dark:bg-gray-800 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="text-xs text-gray-500 dark:text-gray-400">To:</label>
                        <input type="date" x-model="customEnd" :min="customStart" :max="today" 
                               class="text-xs p-2 border rounded-xl bg-white dark:bg-gray-800 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="setPresetDates('this_month')" class="text-[11px] px-2.5 py-1 bg-white dark:bg-gray-800 border rounded-lg hover:bg-teal-50 text-gray-600 dark:text-gray-300 font-medium">This Month</button>
                        <button type="button" @click="setPresetDates('last_30')" class="text-[11px] px-2.5 py-1 bg-white dark:bg-gray-800 border rounded-lg hover:bg-teal-50 text-gray-600 dark:text-gray-300 font-medium">Last 30 Days</button>
                        <button type="button" @click="setPresetDates('this_year')" class="text-[11px] px-2.5 py-1 bg-white dark:bg-gray-800 border rounded-lg hover:bg-teal-50 text-gray-600 dark:text-gray-300 font-medium">This Year</button>
                    </div>
                </div>

                <!-- 4. Active Filters & Results Summary Strip -->
                <div class="pt-3 border-t border-slate-100 dark:border-gray-700 flex flex-col md:flex-row md:items-center justify-between gap-3 text-xs">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="text-gray-500 dark:text-gray-400">
                            Showing <strong class="text-gray-800 dark:text-white" x-text="filteredExpenses.length"></strong> of <strong class="text-gray-800 dark:text-white"><?php echo $totalCount; ?></strong> expenses
                        </span>
                        
                        <span class="w-1 h-1 rounded-full bg-slate-300 dark:bg-gray-600 hidden sm:inline-block"></span>

                        <span class="text-gray-700 dark:text-gray-300">
                            Filtered Total: <strong class="text-[#0F8B8D] dark:text-teal-400 font-black">₹<span x-text="formatCurrency(filteredTotalAmount)"></span></strong>
                        </span>

                        <template x-if="filteredPendingAmount > 0">
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 text-[11px] font-bold">
                                <i class="fa-solid fa-hourglass-half text-[10px]"></i> Pending: ₹<span x-text="formatCurrency(filteredPendingAmount)"></span>
                            </span>
                        </template>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Active Filter Chips -->
                        <template x-if="categoryFilter">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-teal-50 dark:bg-teal-900/30 text-teal-800 dark:text-teal-300 text-[11px] font-bold">
                                Category: <span x-text="categoryFilter"></span>
                                <button type="button" @click="categoryFilter = ''" class="hover:text-teal-950 dark:hover:text-white ml-0.5"><i class="fa-solid fa-xmark"></i></button>
                            </span>
                        </template>

                        <template x-if="dateRange !== 'all'">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 text-[11px] font-bold">
                                Date: <span x-text="dateRangeLabel"></span>
                                <button type="button" @click="dateRange = 'all'; customStart = ''; customEnd = '';" class="hover:text-blue-950 dark:hover:text-white ml-0.5"><i class="fa-solid fa-xmark"></i></button>
                            </span>
                        </template>

                        <template x-if="statusFilter">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-300 text-[11px] font-bold">
                                Status: <span x-text="statusFilter"></span>
                                <button type="button" @click="statusFilter = ''" class="hover:text-indigo-950 dark:hover:text-white ml-0.5"><i class="fa-solid fa-xmark"></i></button>
                            </span>
                        </template>

                        <template x-if="projectFilter">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-purple-50 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300 text-[11px] font-bold">
                                Project: <span x-text="projectFilter"></span>
                                <button type="button" @click="projectFilter = ''" class="hover:text-purple-950 dark:hover:text-white ml-0.5"><i class="fa-solid fa-xmark"></i></button>
                            </span>
                        </template>

                        <!-- Clear / Reset Filters -->
                        <button type="button" x-show="hasActiveFilters" @click="resetFilters()" 
                                class="text-rose-600 hover:text-rose-700 dark:text-rose-400 text-xs font-bold flex items-center gap-1 px-2 py-1 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-900/30 transition">
                            <i class="fa-solid fa-rotate-left"></i> Reset Filters
                        </button>

                        <!-- Export CSV Button -->
                        <button type="button" @click="exportCsv()" 
                                class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-3 py-1.5 rounded-xl shadow-xs text-xs flex items-center gap-1.5 transition">
                            <i class="fa-solid fa-file-excel"></i> Export CSV
                        </button>
                    </div>
                </div>

            </div>

            <!-- Expenses Table -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl border border-slate-200 dark:border-gray-700 shadow-sm overflow-hidden">
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
                                <th class="p-3.5">Project</th>
                                <th class="p-3.5 text-center">Bill / Proof</th>
                                <th class="p-3.5">Added By</th>
                                <th class="p-3.5 text-center">Approval Status</th>
                                <th class="p-3.5 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-gray-700">
                            <template x-for="(exp, idx) in paginatedExpenses" :key="exp.id">
                                <tr class="hover:bg-slate-50/70 dark:hover:bg-gray-700/30 transition">
                                    <td class="p-3.5 text-center font-bold text-gray-400" x-text="(page - 1) * perPage + idx + 1"></td>
                                    
                                    <!-- Date -->
                                    <td class="p-3.5 font-medium whitespace-nowrap text-gray-700 dark:text-gray-300" x-text="formatDate(exp.date)"></td>
                                    
                                    <!-- Voucher Code -->
                                    <td class="p-3.5 font-black text-indigo-600 dark:text-indigo-400" x-text="exp.expense_code || ('EXP-' + exp.id)"></td>
                                    
                                    <!-- Category -->
                                    <td class="p-3.5">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-slate-100 dark:bg-gray-700 text-slate-800 dark:text-slate-200 font-bold text-[11px]">
                                            <i class="fa-solid" :class="exp.category_icon || 'fa-receipt'"></i>
                                            <span x-text="exp.category_name || 'General'"></span>
                                        </span>
                                    </td>
                                    
                                    <!-- Amount -->
                                    <td class="p-3.5 text-right font-black text-gray-900 dark:text-white text-sm">
                                        ₹<span x-text="formatCurrency(exp.amount)"></span>
                                    </td>
                                    
                                    <!-- Purpose & Payee -->
                                    <td class="p-3.5 max-w-xs">
                                        <div class="font-bold text-gray-900 dark:text-white truncate" :title="exp.purpose" x-text="exp.purpose"></div>
                                        <div class="text-[11px] text-gray-400 mt-0.5 flex items-center gap-2">
                                            <span x-show="exp.vendor_payee_name" class="flex items-center gap-1">
                                                <i class="fa-solid fa-store"></i> <span x-text="exp.vendor_payee_name"></span>
                                            </span>
                                            <span x-show="exp.payment_mode" class="px-1.5 py-0.2 bg-slate-100 dark:bg-gray-700 rounded text-[10px]" x-text="exp.payment_mode"></span>
                                        </div>
                                    </td>
                                    
                                    <!-- Project -->
                                    <td class="p-3.5 text-xs">
                                        <span x-show="exp.project_name" class="text-teal-600 dark:text-teal-400 font-semibold" x-text="exp.project_name"></span>
                                        <span x-show="!exp.project_name" class="text-gray-400 italic">General Fund</span>
                                    </td>
                                    
                                    <!-- Bill / Document Thumbnail -->
                                    <td class="p-3.5 text-center">
                                        <template x-if="exp.bill_document_path">
                                            <div>
                                                <template x-if="isPdf(exp.bill_document_path)">
                                                    <a :href="'../' + exp.bill_document_path" target="_blank" class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-rose-50 dark:bg-rose-900/30 text-rose-600 hover:scale-110 transition shadow-sm" title="View PDF Bill">
                                                        <i class="fa-solid fa-file-pdf text-sm"></i>
                                                    </a>
                                                </template>
                                                <template x-if="!isPdf(exp.bill_document_path)">
                                                    <button type="button" @click="openLightbox('../' + exp.bill_document_path, exp.expense_code)" class="inline-block relative group">
                                                        <img :src="'../' + exp.bill_document_path" class="w-8 h-8 rounded-lg object-cover border border-slate-200 shadow-sm group-hover:scale-110 transition">
                                                    </button>
                                                </template>
                                            </div>
                                        </template>
                                        <template x-if="!exp.bill_document_path">
                                            <span class="text-gray-300 dark:text-gray-600 text-xs italic">No bill</span>
                                        </template>
                                    </td>
                                    
                                    <!-- Added By -->
                                    <td class="p-3.5">
                                        <div class="font-medium text-gray-800 dark:text-gray-200" x-text="exp.added_by_name || 'Staff'"></div>
                                        <div class="text-[10px] text-gray-400" x-text="exp.added_by_role || ''"></div>
                                    </td>
                                    
                                    <!-- Approved Status -->
                                    <td class="p-3.5 text-center">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider"
                                              :class="{
                                                  'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300': exp.approved_status === 'Approved',
                                                  'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300': exp.approved_status === 'Paid',
                                                  'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300': exp.approved_status === 'Pending',
                                                  'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300': exp.approved_status === 'Rejected'
                                              }"
                                              x-text="exp.approved_status"></span>
                                    </td>
                                    
                                    <!-- Actions -->
                                    <td class="p-3.5 text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <?php if ($isManagerOrAdmin): ?>
                                            <!-- Status Trigger -->
                                            <button type="button" @click="openStatusModal(exp)" class="p-1.5 rounded-lg text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition" title="Change Approval Status">
                                                <i class="fa-solid fa-stamp"></i>
                                            </button>
                                            <?php endif; ?>
                                            
                                            <!-- Edit Trigger -->
                                            <button type="button" @click="openEditModal(exp)" class="p-1.5 rounded-lg text-teal-600 hover:bg-teal-50 dark:hover:bg-teal-900/30 transition" title="Edit Expense">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>

                                            <!-- Delete Trigger -->
                                            <button type="button" @click="confirmDelete(exp.id)" class="p-1.5 rounded-lg text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 transition" title="Delete Expense">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <template x-if="filteredExpenses.length === 0">
                                <tr>
                                    <td colspan="11" class="p-10 text-center text-xs text-gray-400">
                                        <i class="fa-solid fa-receipt text-3xl mb-2 text-gray-300 block"></i>
                                        No expense records found matching your filters.
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div class="px-6 py-4 bg-gray-50/50 dark:bg-gray-700/20 border-t border-slate-100 dark:border-gray-700 flex flex-col sm:flex-row items-center justify-between gap-4" x-show="filteredExpenses.length > 0">
                    <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                        <span>Showing <strong class="text-gray-800 dark:text-gray-200" x-text="(page - 1) * perPage + 1"></strong> to <strong class="text-gray-800 dark:text-gray-200" x-text="Math.min(page * perPage, filteredExpenses.length)"></strong> of <strong class="text-gray-800 dark:text-gray-200" x-text="filteredExpenses.length"></strong> results</span>
                        <div class="flex items-center gap-1.5 ml-2">
                            <label class="text-[11px] text-gray-400">Per page:</label>
                            <select x-model.number="perPage" @change="page = 1" class="text-xs py-1 px-2 bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-lg text-gray-700 dark:text-gray-300">
                                <option :value="10">10</option>
                                <option :value="25">25</option>
                                <option :value="50">50</option>
                                <option :value="100">100</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex items-center gap-1" x-show="totalPages > 1">
                        <button @click="setPage(1)" :disabled="page === 1" class="px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-gray-700 text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed">
                            <i class="fa-solid fa-angles-left text-[10px]"></i>
                        </button>
                        <button @click="setPage(page - 1)" :disabled="page === 1" class="px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-gray-700 text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed">
                            <i class="fa-solid fa-chevron-left text-[10px]"></i>
                        </button>
                        <template x-for="p in totalPages" :key="p">
                            <button x-show="p === 1 || p === totalPages || (p >= page - 2 && p <= page + 2)"
                                    @click="setPage(p)" 
                                    :class="page === p ? 'bg-[#0F8B8D] text-white font-bold border-[#0F8B8D] shadow-xs' : 'text-gray-700 dark:text-gray-300 border-slate-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700'" 
                                    class="w-8 h-8 rounded-lg border text-xs flex items-center justify-center transition" 
                                    x-text="p">
                            </button>
                        </template>
                        <button @click="setPage(page + 1)" :disabled="page === totalPages" class="px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-gray-700 text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed">
                            <i class="fa-solid fa-chevron-right text-[10px]"></i>
                        </button>
                        <button @click="setPage(totalPages)" :disabled="page === totalPages" class="px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-gray-700 text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed">
                            <i class="fa-solid fa-angles-right text-[10px]"></i>
                        </button>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <!-- ── 1. ADD EXPENSE MODAL ────────────────────────────────────── -->
    <div x-show="isAddModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-transition.opacity>
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden border border-slate-200 dark:border-gray-700" @click.away="closeAddModal()">
            
            <div class="p-5 border-b border-slate-100 dark:border-gray-700 flex items-center justify-between bg-slate-50 dark:bg-gray-700/50">
                <div class="flex items-center gap-3">
                    <span class="w-9 h-9 rounded-xl bg-teal-50 dark:bg-teal-900/40 text-[#0F8B8D] flex items-center justify-center text-base">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                    </span>
                    <div>
                        <h4 class="text-base font-black text-gray-900 dark:text-white">Record New Expense</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Enter expense details and upload invoice or bill receipt</p>
                    </div>
                </div>
                <button type="button" @click="closeAddModal()" class="text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 p-2 rounded-xl transition">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form action="actions/expense_logic.php" method="POST" enctype="multipart/form-data" class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="action" value="add_expense">

                <!-- Category & Amount -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                            Expense Category <span class="text-red-500">*</span>
                        </label>
                        <select name="category_id" required class="w-full px-3.5 py-2.5 text-xs font-medium rounded-xl border border-slate-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white focus:ring-2 focus:ring-[#0F8B8D] outline-none">
                            <option value="">-- Select Category --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo (int)$cat['id']; ?>">
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                            Amount (₹) <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 text-xs font-bold text-gray-400">₹</span>
                            <input type="number" step="0.01" min="0.01" name="amount" required placeholder="0.00" class="w-full pl-7 pr-3.5 py-2.5 text-xs font-bold rounded-xl border border-slate-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white focus:ring-2 focus:ring-[#0F8B8D] outline-none">
                        </div>
                    </div>
                </div>

                <!-- Date & Project Linkage -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                            Expense Date <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="date" required value="<?php echo date('Y-m-d'); ?>" class="w-full px-3.5 py-2.5 text-xs font-medium rounded-xl border border-slate-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white focus:ring-2 focus:ring-[#0F8B8D] outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                            Link to Project / Cause <span class="text-gray-400 font-normal">(Optional)</span>
                        </label>
                        <select name="project_id" class="w-full px-3.5 py-2.5 text-xs font-medium rounded-xl border border-slate-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white focus:ring-2 focus:ring-[#0F8B8D] outline-none">
                            <option value="">General NGO Operations</option>
                            <?php foreach ($projects as $p): ?>
                                <option value="<?php echo (int)$p['id']; ?>">
                                    <?php echo htmlspecialchars($p['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Purpose / Particulars -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                        Purpose / Particulars <span class="text-red-500">*</span>
                    </label>
                    <textarea name="purpose" required rows="2" placeholder="Specify what the expense was for (e.g., Purchased 50 Ration kits packaging bags, Diesel for ambulance...)" class="w-full px-3.5 py-2.5 text-xs font-medium rounded-xl border border-slate-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white focus:ring-2 focus:ring-[#0F8B8D] outline-none"></textarea>
                </div>

                <!-- Payment Mode, Ref No & Payee Vendor -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Payment Mode</label>
                        <select name="payment_mode" class="w-full px-3.5 py-2.5 text-xs font-medium rounded-xl border border-slate-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white focus:ring-2 focus:ring-[#0F8B8D] outline-none">
                            <option value="Cash" selected>Cash</option>
                            <option value="UPI">UPI (GPay, PhonePe, Paytm)</option>
                            <option value="Bank Transfer">Bank Transfer (NEFT/IMPS)</option>
                            <option value="Cheque">Cheque</option>
                            <option value="Credit/Debit Card">Credit / Debit Card</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Voucher / Ref No.</label>
                        <input type="text" name="reference_no" placeholder="UPI Ref / Cheque No." class="w-full px-3.5 py-2.5 text-xs font-medium rounded-xl border border-slate-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white focus:ring-2 focus:ring-[#0F8B8D] outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Paid To / Payee Vendor</label>
                        <input type="text" name="vendor_payee_name" placeholder="Shop / Person Name" class="w-full px-3.5 py-2.5 text-xs font-medium rounded-xl border border-slate-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white focus:ring-2 focus:ring-[#0F8B8D] outline-none">
                    </div>
                </div>

                <!-- Bill / Document File Upload (Reusing existing Certificate/Gallery upload pattern) -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                        Bill / Invoice Document Upload
                    </label>
                    <div class="border-2 border-dashed border-slate-200 dark:border-gray-700 rounded-2xl p-4 text-center hover:border-[#0F8B8D] transition bg-slate-50/50 dark:bg-gray-900/30">
                        <input type="file" name="bill_document" id="bill_document_add" accept="image/jpeg,image/png,image/webp,application/pdf" class="w-full text-xs text-gray-500 dark:text-gray-400 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-teal-50 file:text-[#0F8B8D] hover:file:bg-teal-100 dark:file:bg-teal-900/40 dark:file:text-teal-300 cursor-pointer">
                        <p class="text-[11px] text-gray-400 mt-2">Accepted formats: JPG, PNG, WEBP, PDF (Max size: 5MB)</p>
                    </div>
                </div>

                <?php if ($isManagerOrAdmin): ?>
                <!-- Approval Status Preset for Admin/Manager -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Initial Status</label>
                    <select name="approved_status" class="w-full px-3.5 py-2.5 text-xs font-medium rounded-xl border border-slate-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white focus:ring-2 focus:ring-[#0F8B8D] outline-none">
                        <option value="Approved" selected>Approved (Instant Approval)</option>
                        <option value="Paid">Paid (Already Disbursed)</option>
                        <option value="Pending">Pending (Requires Later Review)</option>
                    </select>
                </div>
                <?php endif; ?>

                <!-- Remarks -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Internal Remarks <span class="text-gray-400 font-normal">(Optional)</span></label>
                    <input type="text" name="remarks" placeholder="Optional audit or accounting note..." class="w-full px-3.5 py-2.5 text-xs font-medium rounded-xl border border-slate-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white focus:ring-2 focus:ring-[#0F8B8D] outline-none">
                </div>

                <div class="flex gap-3 pt-4 border-t border-slate-100 dark:border-gray-700">
                    <button type="submit" class="flex-1 bg-[#0F8B8D] hover:bg-[#0c7274] text-white font-bold py-3 rounded-xl shadow-md text-xs transition flex items-center justify-center gap-2">
                        <i class="fa-solid fa-check"></i> Save Expense Record
                    </button>
                    <button type="button" @click="closeAddModal()" class="px-5 bg-slate-100 hover:bg-slate-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-bold py-3 rounded-xl text-xs transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ── 2. EDIT EXPENSE MODAL ───────────────────────────────────── -->
    <div x-show="isEditModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-transition.opacity>
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden border border-slate-200 dark:border-gray-700" @click.away="closeEditModal()">
            
            <div class="p-5 border-b border-slate-100 dark:border-gray-700 flex items-center justify-between bg-slate-50 dark:bg-gray-700/50">
                <div class="flex items-center gap-3">
                    <span class="w-9 h-9 rounded-xl bg-teal-50 dark:bg-teal-900/40 text-[#0F8B8D] flex items-center justify-center text-base">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </span>
                    <div>
                        <h4 class="text-base font-black text-gray-900 dark:text-white">Edit Expense Record</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Update expense parameters or replace receipt document</p>
                    </div>
                </div>
                <button type="button" @click="closeEditModal()" class="text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 p-2 rounded-xl transition">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form action="actions/expense_logic.php" method="POST" enctype="multipart/form-data" class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="action" value="update_expense">
                <input type="hidden" name="id" :value="editData.id">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Expense Category <span class="text-red-500">*</span></label>
                        <select name="category_id" x-model="editData.category_id" required class="w-full px-3.5 py-2.5 text-xs font-medium rounded-xl border border-slate-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white outline-none">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo (int)$cat['id']; ?>">
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Amount (₹) <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="amount" x-model="editData.amount" required class="w-full px-3.5 py-2.5 text-xs font-bold rounded-xl border border-slate-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Expense Date <span class="text-red-500">*</span></label>
                        <input type="date" name="date" x-model="editData.date" required class="w-full px-3.5 py-2.5 text-xs font-medium rounded-xl border border-slate-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Project</label>
                        <select name="project_id" x-model="editData.project_id" class="w-full px-3.5 py-2.5 text-xs font-medium rounded-xl border border-slate-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white outline-none">
                            <option value="">General NGO Operations</option>
                            <?php foreach ($projects as $p): ?>
                                <option value="<?php echo (int)$p['id']; ?>">
                                    <?php echo htmlspecialchars($p['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Purpose / Particulars <span class="text-red-500">*</span></label>
                    <textarea name="purpose" x-model="editData.purpose" required rows="2" class="w-full px-3.5 py-2.5 text-xs font-medium rounded-xl border border-slate-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white outline-none"></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Payment Mode</label>
                        <select name="payment_mode" x-model="editData.payment_mode" class="w-full px-3.5 py-2.5 text-xs font-medium rounded-xl border border-slate-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white outline-none">
                            <option value="Cash">Cash</option>
                            <option value="UPI">UPI</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Cheque">Cheque</option>
                            <option value="Credit/Debit Card">Card</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Voucher / Ref No.</label>
                        <input type="text" name="reference_no" x-model="editData.reference_no" class="w-full px-3.5 py-2.5 text-xs font-medium rounded-xl border border-slate-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Paid To / Payee Vendor</label>
                        <input type="text" name="vendor_payee_name" x-model="editData.vendor_payee_name" class="w-full px-3.5 py-2.5 text-xs font-medium rounded-xl border border-slate-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Replace Bill Document (Optional)</label>
                    <input type="file" name="bill_document" accept="image/jpeg,image/png,image/webp,application/pdf" class="w-full text-xs text-gray-500 dark:text-gray-400 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-teal-50 file:text-[#0F8B8D]">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Remarks</label>
                    <input type="text" name="remarks" x-model="editData.remarks" class="w-full px-3.5 py-2.5 text-xs font-medium rounded-xl border border-slate-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white outline-none">
                </div>

                <div class="flex gap-3 pt-4 border-t border-slate-100 dark:border-gray-700">
                    <button type="submit" class="flex-1 bg-[#0F8B8D] hover:bg-[#0c7274] text-white font-bold py-3 rounded-xl shadow-md text-xs transition">
                        Update Expense Record
                    </button>
                    <button type="button" @click="closeEditModal()" class="px-5 bg-slate-100 hover:bg-slate-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-bold py-3 rounded-xl text-xs transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($isManagerOrAdmin): ?>
    <!-- ── 3. APPROVAL STATUS MODAL (Admin/Manager Only) ──────────── -->
    <div x-show="isStatusModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-transition.opacity>
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-200 dark:border-gray-700" @click.away="closeStatusModal()">
            <div class="p-5 border-b border-slate-100 dark:border-gray-700 bg-slate-50 dark:bg-gray-700/50 flex items-center justify-between">
                <h4 class="text-sm font-black text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-stamp text-indigo-600"></i> Update Approval Status
                </h4>
                <button type="button" @click="closeStatusModal()" class="text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form action="actions/expense_logic.php" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="id" :value="statusData.id">

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Select Status</label>
                    <select name="status" x-model="statusData.status" required class="w-full px-3.5 py-2.5 text-xs font-bold rounded-xl border border-slate-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white outline-none">
                        <option value="Approved">Approved</option>
                        <option value="Paid">Paid</option>
                        <option value="Pending">Pending</option>
                        <option value="Rejected">Rejected</option>
                    </select>
                </div>

                <div x-show="statusData.status === 'Rejected'">
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Rejection Reason</label>
                    <textarea name="rejection_reason" x-model="statusData.rejection_reason" rows="2" placeholder="State reason for rejecting this claim..." class="w-full px-3.5 py-2.5 text-xs font-medium rounded-xl border border-slate-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white outline-none"></textarea>
                </div>

                <div class="flex gap-3 pt-3">
                    <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 rounded-xl text-xs transition">
                        Update Status
                    </button>
                    <button type="button" @click="closeStatusModal()" class="px-4 bg-slate-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-bold py-2.5 rounded-xl text-xs">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── 4. LIGHTBOX MODAL (Image Preview) ───────────────────────── -->
    <div x-show="isLightboxOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md" x-transition.opacity @click.self="isLightboxOpen = false">
        <div class="relative max-w-3xl max-h-[85vh] overflow-hidden rounded-2xl bg-white dark:bg-gray-900 p-2 border border-slate-700">
            <button type="button" @click="isLightboxOpen = false" class="absolute top-4 right-4 z-10 w-8 h-8 rounded-full bg-black/60 text-white flex items-center justify-center hover:bg-black transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <img :src="lightboxSrc" class="max-h-[80vh] w-auto mx-auto object-contain rounded-xl">
        </div>
    </div>

    <!-- ── 5. DELETE CONFIRMATION FORM (HIDDEN) ────────────────────── -->
    <form id="deleteExpenseForm" action="actions/expense_logic.php" method="POST" class="hidden">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <input type="hidden" name="action" value="delete_expense">
        <input type="hidden" name="id" id="deleteExpenseId">
    </form>

</div>

<script>
    function expenseManager(config = {}) {
        return {
            items: <?php echo json_encode($expenses); ?>,
            search: '',
            categoryFilter: config.initialCategory || '',
            dateRange: config.initialDateRange || 'all',
            customStart: '',
            customEnd: '',
            statusFilter: config.initialStatus || '',
            projectFilter: config.initialProject || '',
            paymentModeFilter: '',
            today: config.today || '<?php echo date('Y-m-d'); ?>',
            
            page: 1,
            perPage: 10,

            get totalPages() {
                return Math.ceil(this.filteredExpenses.length / this.perPage) || 1;
            },

            get paginatedExpenses() {
                const start = (this.page - 1) * this.perPage;
                return this.filteredExpenses.slice(start, start + this.perPage);
            },

            setPage(p) {
                if (p >= 1 && p <= this.totalPages) this.page = p;
            },

            isAddModalOpen: false,
            isEditModalOpen: false,
            isStatusModalOpen: false,
            isLightboxOpen: false,
            lightboxSrc: '',

            editData: {},
            statusData: {},

            init() {
                // Watch for custom date changes to auto-constrain
                this.$watch('customStart', value => {
                    if (value && this.customEnd && value > this.customEnd) {
                        this.customStart = this.customEnd;
                    }
                });
                this.$watch('customEnd', value => {
                    if (value && this.customStart && value < this.customStart) {
                        this.customEnd = this.customStart;
                    }
                });
            },

            openAddModal() {
                this.isAddModalOpen = true;
            },

            closeAddModal() {
                this.isAddModalOpen = false;
            },

            openEditModal(exp) {
                this.editData = JSON.parse(JSON.stringify(exp));
                this.isEditModalOpen = true;
            },

            closeEditModal() {
                this.isEditModalOpen = false;
            },

            openStatusModal(exp) {
                this.statusData = {
                    id: exp.id,
                    status: exp.approved_status,
                    rejection_reason: exp.rejection_reason || ''
                };
                this.isStatusModalOpen = true;
            },

            closeStatusModal() {
                this.isStatusModalOpen = false;
            },

            openLightbox(src, code) {
                this.lightboxSrc = src;
                this.isLightboxOpen = true;
            },

            confirmDelete(id) {
                if (confirm('Are you sure you want to delete this expense entry and its attached receipt?')) {
                    document.getElementById('deleteExpenseId').value = id;
                    document.getElementById('deleteExpenseForm').submit();
                }
            },

            isPdf(path) {
                if (!path) return false;
                return path.toLowerCase().endsWith('.pdf');
            },

            countByCategory(catName) {
                if (!this.items) return 0;
                return this.items.filter(e => e.category_name === catName).length;
            },

            setPresetDates(preset) {
                const now = new Date();
                const curY = now.getFullYear();
                const curM = now.getMonth();
                
                if (preset === 'this_month') {
                    const firstDay = new Date(curY, curM, 1);
                    this.customStart = firstDay.toISOString().split('T')[0];
                    this.customEnd = this.today;
                } else if (preset === 'last_30') {
                    const d = new Date();
                    d.setDate(d.getDate() - 30);
                    this.customStart = d.toISOString().split('T')[0];
                    this.customEnd = this.today;
                } else if (preset === 'this_year') {
                    this.customStart = `${curY}-01-01`;
                    this.customEnd = this.today;
                }
            },

            resetFilters() {
                this.search = '';
                this.categoryFilter = '';
                this.dateRange = 'all';
                this.customStart = '';
                this.customEnd = '';
                this.statusFilter = '';
                this.projectFilter = '';
                this.paymentModeFilter = '';
            },

            get hasActiveFilters() {
                return !!(this.search || this.categoryFilter || this.dateRange !== 'all' || this.customStart || this.customEnd || this.statusFilter || this.projectFilter || this.paymentModeFilter);
            },

            get dateRangeLabel() {
                switch(this.dateRange) {
                    case 'today': return 'Today';
                    case 'yesterday': return 'Yesterday';
                    case 'this_week': return 'This Week';
                    case 'current_month': return 'This Month';
                    case 'prev_month': return 'Last Month';
                    case 'this_quarter': return 'This Quarter';
                    case 'this_year': return 'This Year';
                    case 'custom': 
                        return (this.customStart && this.customEnd) ? `${this.customStart} to ${this.customEnd}` : 'Custom';
                    default: return 'All Time';
                }
            },

            isDateMatching(dateStr) {
                if (!dateStr || this.dateRange === 'all') return true;
                const todayStr = this.today;
                const now = new Date();
                
                if (this.dateRange === 'today') {
                    return dateStr === todayStr;
                }
                
                if (this.dateRange === 'yesterday') {
                    const y = new Date();
                    y.setDate(y.getDate() - 1);
                    const yStr = y.toISOString().split('T')[0];
                    return dateStr === yStr;
                }
                
                if (this.dateRange === 'this_week') {
                    const curr = new Date();
                    const day = curr.getDay(); // 0 is Sun
                    const diff = curr.getDate() - day + (day === 0 ? -6 : 1); // adjust when day is sunday
                    const monday = new Date(curr.setDate(diff));
                    const monStr = monday.toISOString().split('T')[0];
                    return dateStr >= monStr && dateStr <= todayStr;
                }
                
                if (this.dateRange === 'current_month') {
                    const curYm = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0');
                    return dateStr.startsWith(curYm);
                }
                
                if (this.dateRange === 'prev_month') {
                    const prev = new Date(now.getFullYear(), now.getMonth() - 1, 1);
                    const prevYm = prev.getFullYear() + '-' + String(prev.getMonth() + 1).padStart(2, '0');
                    return dateStr.startsWith(prevYm);
                }
                
                if (this.dateRange === 'this_quarter') {
                    const qMonth = Math.floor(now.getMonth() / 3) * 3;
                    const qStart = new Date(now.getFullYear(), qMonth, 1);
                    const qStr = qStart.toISOString().split('T')[0];
                    return dateStr >= qStr && dateStr <= todayStr;
                }
                
                if (this.dateRange === 'this_year') {
                    const curYear = String(now.getFullYear());
                    return dateStr.startsWith(curYear);
                }
                
                if (this.dateRange === 'custom') {
                    if (this.customStart && dateStr < this.customStart) return false;
                    if (this.customEnd && dateStr > this.customEnd) return false;
                    return true;
                }
                
                return true;
            },

            get filteredExpenses() {
                return this.items.filter(e => {
                    const matchesSearch = !this.search || 
                        (e.expense_code && e.expense_code.toLowerCase().includes(this.search.toLowerCase())) ||
                        (e.purpose && e.purpose.toLowerCase().includes(this.search.toLowerCase())) ||
                        (e.vendor_payee_name && e.vendor_payee_name.toLowerCase().includes(this.search.toLowerCase())) ||
                        (e.added_by_name && e.added_by_name.toLowerCase().includes(this.search.toLowerCase())) ||
                        (e.reference_no && e.reference_no.toLowerCase().includes(this.search.toLowerCase()));

                    const matchesCategory = !this.categoryFilter || (e.category_name === this.categoryFilter);
                    const matchesStatus = !this.statusFilter || (e.approved_status === this.statusFilter);
                    const matchesProject = !this.projectFilter || 
                        (this.projectFilter === 'General' && !e.project_name) ||
                        (e.project_name === this.projectFilter);
                    const matchesPaymentMode = !this.paymentModeFilter || (e.payment_mode === this.paymentModeFilter);
                    const matchesDate = this.isDateMatching(e.date);

                    return matchesSearch && matchesCategory && matchesStatus && matchesProject && matchesPaymentMode && matchesDate;
                });
            },

            get filteredTotalAmount() {
                return this.filteredExpenses.reduce((sum, e) => sum + (parseFloat(e.amount) || 0), 0);
            },

            get filteredApprovedAmount() {
                return this.filteredExpenses
                    .filter(e => e.approved_status === 'Approved' || e.approved_status === 'Paid')
                    .reduce((sum, e) => sum + (parseFloat(e.amount) || 0), 0);
            },

            get filteredPendingAmount() {
                return this.filteredExpenses
                    .filter(e => e.approved_status === 'Pending')
                    .reduce((sum, e) => sum + (parseFloat(e.amount) || 0), 0);
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
            },

            exportCsv() {
                const data = this.filteredExpenses;
                if (!data || data.length === 0) {
                    alert('No expense records available to export.');
                    return;
                }

                const headers = ['#', 'Voucher Code', 'Date', 'Category', 'Amount (INR)', 'Purpose / Particulars', 'Payee / Vendor', 'Payment Mode', 'Ref / Cheque No', 'Project', 'Added By', 'Status', 'Approved By'];
                
                const csvRows = [headers.join(',')];

                data.forEach((exp, idx) => {
                    const row = [
                        idx + 1,
                        `"${exp.expense_code || ('EXP-' + exp.id)}"`,
                        `"${exp.date || ''}"`,
                        `"${(exp.category_name || 'General').replace(/"/g, '""')}"`,
                        parseFloat(exp.amount) || 0,
                        `"${(exp.purpose || '').replace(/"/g, '""')}"`,
                        `"${(exp.vendor_payee_name || '').replace(/"/g, '""')}"`,
                        `"${(exp.payment_mode || '').replace(/"/g, '""')}"`,
                        `"${(exp.reference_no || '').replace(/"/g, '""')}"`,
                        `"${(exp.project_name || 'General Fund').replace(/"/g, '""')}"`,
                        `"${(exp.added_by_name || '').replace(/"/g, '""')}"`,
                        `"${exp.approved_status || ''}"`,
                        `"${(exp.approved_by_name || '').replace(/"/g, '""')}"`
                    ];
                    csvRows.push(row.join(','));
                });

                const csvString = csvRows.join('\n');
                const blob = new Blob([csvString], { type: 'text/csv;charset=utf-8;' });
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.setAttribute('href', url);
                link.setAttribute('download', `Expense_Report_${this.today}.csv`);
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        };
    }
</script>

<?php require 'includes/footer.php'; ?>
