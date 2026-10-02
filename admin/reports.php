<?php
// ============================================================
// admin/reports.php
// Comprehensive Reporting System: Monetary & In-Kind Item Donations
// Features: Category-wise, Date-wise, Status filters, Summary KPIs, CSV/Excel/PDF export
// ============================================================

require 'includes/header.php';
require '../config/db.php';

$projects = $pdo->query("SELECT id, title FROM projects ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);
$itemCategories = [];
try {
    $itemCategories = $pdo->query("SELECT id, category_name, category_icon FROM item_donation_categories WHERE is_active = 1 ORDER BY display_order ASC, category_name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $itemCategories = [];
}

$initialFamily = (isset($_GET['type']) && $_GET['type'] === 'item') ? 'item' : 'monetary';
?>

<div class="flex h-screen overflow-hidden bg-slate-50 dark:bg-gray-900"
    x-data="reportManager({
        initialFamily: '<?php echo $initialFamily; ?>'
    })"
    x-init="init()">
    <?php require 'includes/sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>
        
        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-8">

            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h3 class="text-2xl md:text-3xl font-black text-gray-800 dark:text-white flex items-center gap-3">
                        <span class="w-10 h-10 rounded-2xl bg-teal-50 dark:bg-teal-900/40 text-[#0F8B8D] flex items-center justify-center text-lg shadow-sm">
                            <i class="fa-solid fa-chart-pie"></i>
                        </span>
                        Reports & Analytics Center
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Generate granular Category-wise, Date-wise, and Project-wise donation summaries with Excel & PDF exports.
                    </p>
                </div>

                <!-- Report Family Switcher -->
                <div class="inline-flex p-1 bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-2xl shadow-sm">
                    <button type="button" @click="setFamily('item')" 
                            class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2"
                            :class="family === 'item' ? 'bg-[#0F8B8D] text-white shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900'">
                        <i class="fa-solid fa-gift"></i>
                        Item Donations Report
                    </button>
                    <button type="button" @click="setFamily('monetary')" 
                            class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2"
                            :class="family === 'monetary' ? 'bg-[#0F8B8D] text-white shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900'">
                        <i class="fa-solid fa-sack-dollar"></i>
                        Monetary Donations
                    </button>
                    <a href="beneficiary_reports.php" 
                       class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 text-gray-600 dark:text-gray-300 hover:text-teal-600 dark:hover:text-teal-400">
                        <i class="fa-solid fa-hands-holding-child"></i>
                        Beneficiary Reports
                    </a>
                    <a href="income_vs_expense.php" 
                       class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 text-gray-600 dark:text-gray-300 hover:text-teal-600 dark:hover:text-teal-400">
                        <i class="fa-solid fa-scale-balanced"></i>
                        Income vs Expense
                    </a>
                </div>
            </div>

            <!-- Filter Controls Panel -->
            <div class="bg-white dark:bg-gray-800 p-5 md:p-6 rounded-3xl shadow-sm border border-slate-200 dark:border-gray-700 mb-6">
                
                <!-- 1. ITEM DONATIONS FILTERS -->
                <div x-show="family === 'item'" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                        
                        <!-- Report Type -->
                        <div>
                            <label class="text-xs font-bold text-gray-700 dark:text-gray-300 block mb-1">Report Mode</label>
                            <select x-model="filters.report_type" class="w-full text-xs font-medium p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                                <option value="item_donations">All Item Donations (Detailed)</option>
                                <option value="item_category_wise">Category-Wise Breakdown & Records</option>
                            </select>
                        </div>

                        <!-- Item Category Filter -->
                        <div>
                            <label class="text-xs font-bold text-gray-700 dark:text-gray-300 block mb-1">Item Category</label>
                            <select x-model="filters.category_id" class="w-full text-xs font-medium p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                                <option value="">All Categories</option>
                                <?php foreach ($itemCategories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>">
                                        <?php echo htmlspecialchars($cat['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <label class="text-xs font-bold text-gray-700 dark:text-gray-300 block mb-1">Status</label>
                            <select x-model="filters.status" class="w-full text-xs font-medium p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                                <option value="all">All Statuses</option>
                                <option value="Pledged">Pledged</option>
                                <option value="Verified">Verified</option>
                                <option value="Collected">Collected</option>
                                <option value="Distributed">Distributed</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>

                        <!-- Date Range -->
                        <div>
                            <label class="text-xs font-bold text-gray-700 dark:text-gray-300 block mb-1">Date Period</label>
                            <select x-model="filters.date_range" class="w-full text-xs font-medium p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                                <option value="all_time">All Time</option>
                                <option value="this_month">This Month</option>
                                <option value="last_month">Last Month</option>
                                <option value="this_quarter">This Quarter</option>
                                <option value="this_year">This Year</option>
                                <option value="custom">Custom Date Range</option>
                            </select>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex items-end">
                            <button @click="generateReport" :disabled="loading" 
                                    class="w-full bg-[#0F8B8D] hover:bg-[#0c7274] text-white font-bold py-2.5 px-4 rounded-xl shadow-md transition flex items-center justify-center gap-2 text-xs disabled:opacity-50">
                                <span x-show="!loading"><i class="fa-solid fa-magnifying-glass-chart"></i> Generate Report</span>
                                <span x-show="loading"><i class="fa-solid fa-circle-notch fa-spin"></i> Loading...</span>
                            </button>
                        </div>
                    </div>

                    <!-- Custom Date Inputs -->
                    <div x-show="filters.date_range === 'custom'" x-collapse class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-slate-100 dark:border-gray-700">
                        <div>
                            <label class="text-xs font-bold text-gray-600 dark:text-gray-300 block mb-1">Start Date</label>
                            <input type="date" x-model="filters.custom_start" class="w-full text-xs p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-gray-600 dark:text-gray-300 block mb-1">End Date</label>
                            <input type="date" x-model="filters.custom_end" class="w-full text-xs p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none">
                        </div>
                    </div>
                </div>

                <!-- 2. MONETARY DONATIONS FILTERS -->
                <div x-show="family === 'monetary'" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                        <div>
                            <label class="text-xs font-bold text-gray-700 dark:text-gray-300 block mb-1">Report Type</label>
                            <select x-model="filters.report_type" class="w-full text-xs font-medium p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none">
                                <option value="all_donations">All Monetary Donations</option>
                                <option value="80g_donations">80G Tax Eligible Donations</option>
                                <option value="project_wise">Project-Wise Donations</option>
                            </select>
                        </div>

                        <div x-show="filters.report_type === 'project_wise'">
                            <label class="text-xs font-bold text-gray-700 dark:text-gray-300 block mb-1">Project</label>
                            <select x-model="filters.project_id" class="w-full text-xs font-medium p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none">
                                <option value="">Select Project</option>
                                <?php foreach ($projects as $p): ?>
                                    <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="text-xs font-bold text-gray-700 dark:text-gray-300 block mb-1">Date Range</label>
                            <select x-model="filters.date_range" class="w-full text-xs font-medium p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none">
                                <option value="all_time">All Time</option>
                                <option value="this_month">This Month</option>
                                <option value="last_month">Last Month</option>
                                <option value="this_quarter">This Quarter</option>
                                <option value="this_year">This Year</option>
                                <option value="custom">Custom Date Range</option>
                            </select>
                        </div>

                        <div class="flex items-end">
                            <button @click="generateReport" :disabled="loading" 
                                    class="w-full bg-[#0F8B8D] hover:bg-[#0c7274] text-white font-bold py-2.5 px-4 rounded-xl shadow-md transition flex items-center justify-center gap-2 text-xs disabled:opacity-50">
                                <span x-show="!loading"><i class="fa-solid fa-magnifying-glass-chart"></i> Generate Report</span>
                                <span x-show="loading"><i class="fa-solid fa-circle-notch fa-spin"></i> Loading...</span>
                            </button>
                        </div>
                    </div>

                    <div x-show="filters.date_range === 'custom'" x-collapse class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-slate-100 dark:border-gray-700">
                        <div>
                            <label class="text-xs font-bold text-gray-600 dark:text-gray-300 block mb-1">Start Date</label>
                            <input type="date" x-model="filters.custom_start" class="w-full text-xs p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-gray-600 dark:text-gray-300 block mb-1">End Date</label>
                            <input type="date" x-model="filters.custom_end" class="w-full text-xs p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none">
                        </div>
                    </div>
                </div>

            </div>

            <!-- RESULTS SECTION -->
            <div x-show="reportData !== null" x-transition.opacity>

                <!-- 1. KPI STRIP FOR ITEM DONATIONS -->
                <template x-if="isItemReport && summary">
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                        <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase text-gray-400">Total Items Pledged</span>
                                <span class="w-9 h-9 rounded-xl bg-teal-50 dark:bg-teal-900/30 text-[#0F8B8D] flex items-center justify-center text-sm"><i class="fa-solid fa-boxes-stacked"></i></span>
                            </div>
                            <p class="text-2xl font-black text-gray-800 dark:text-white mt-2" x-text="summary.totalCount"></p>
                            <p class="text-[11px] text-gray-400 mt-1">In-kind donation contributions</p>
                        </div>

                        <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase text-gray-400">Total Est. Valuation</span>
                                <span class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 flex items-center justify-center text-sm"><i class="fa-solid fa-indian-rupee-sign"></i></span>
                            </div>
                            <p class="text-2xl font-black text-emerald-600 mt-2">₹<span x-text="summary.totalEstimatedValue || '0.00'"></span></p>
                            <p class="text-[11px] text-gray-400 mt-1">Combined estimated value</p>
                        </div>

                        <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase text-gray-400">Active Categories</span>
                                <span class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 flex items-center justify-center text-sm"><i class="fa-solid fa-tags"></i></span>
                            </div>
                            <p class="text-2xl font-black text-gray-800 dark:text-white mt-2" x-text="summary.categoriesCount || 0"></p>
                            <p class="text-[11px] text-gray-400 mt-1">Different item categories</p>
                        </div>

                        <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase text-gray-400">Top Category</span>
                                <span class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 flex items-center justify-center text-sm"><i class="fa-solid fa-trophy"></i></span>
                            </div>
                            <p class="text-lg font-black text-gray-800 dark:text-white truncate mt-2" x-text="summary.topCategory || 'N/A'"></p>
                            <p class="text-[11px] text-gray-400 mt-1">Highest item count</p>
                        </div>
                    </div>
                </template>

                <!-- 2. CATEGORY BREAKDOWN ACCORDION / SUMMARY BOX (For Item Reports) -->
                <div x-show="isItemReport && categoryBreakdown.length > 0" class="bg-white dark:bg-gray-800 p-5 rounded-3xl border border-slate-200 dark:border-gray-700 shadow-sm mb-6">
                    <h4 class="text-sm font-bold text-gray-800 dark:text-white flex items-center gap-2 mb-4">
                        <i class="fa-solid fa-layer-group text-[#0F8B8D]"></i>
                        Category-Wise Aggregate Summary
                    </h4>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                        <template x-for="cat in categoryBreakdown" :key="'cat_' + cat.category_id">
                            <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-gray-700/50 border border-slate-100 dark:border-gray-600 flex items-start gap-3">
                                <div class="w-10 h-10 rounded-xl bg-[#0F8B8D]/10 text-[#0F8B8D] dark:bg-teal-900/50 flex items-center justify-center text-base flex-shrink-0">
                                    <i class="fa-solid" :class="cat.category_icon || 'fa-gift'"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h5 class="font-bold text-xs text-gray-800 dark:text-white truncate" x-text="cat.category_name"></h5>
                                    <p class="text-[11px] text-emerald-600 font-bold mt-0.5">
                                        <span x-text="cat.count"></span> Pledges • <span x-text="Number(cat.total_quantity).toLocaleString()"></span> Units
                                    </p>
                                    <p class="text-[10px] text-gray-400" x-show="Number(cat.total_value) > 0">
                                        Est. ₹<span x-text="Number(cat.total_value).toLocaleString('en-IN')"></span>
                                    </p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- 3. ACTION & EXPORT BAR -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white dark:bg-gray-800 p-4 rounded-2xl shadow-sm border border-slate-200 dark:border-gray-700 mb-4">
                    <div class="text-xs text-gray-600 dark:text-gray-300">
                        Showing <strong class="text-gray-900 dark:text-white" x-text="filteredData.length"></strong> records matching criteria.
                    </div>

                    <!-- Client-side filter + Export Buttons -->
                    <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
                        <div class="relative flex-1 md:w-56">
                            <i class="fa-solid fa-search absolute left-3 top-2.5 text-gray-400 text-xs"></i>
                            <input type="text" x-model="searchQuery" placeholder="Search donor, item, code..." 
                                   class="w-full text-xs pl-8 pr-3 py-2 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none">
                        </div>

                        <a :href="exportUrl('csv')" target="_blank" 
                           class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-xl text-xs font-bold transition shadow-sm">
                            <i class="fa-solid fa-file-csv"></i> Export CSV
                        </a>

                        <a :href="exportUrl('excel')" target="_blank" 
                           class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-xl text-xs font-bold transition shadow-sm">
                            <i class="fa-solid fa-file-excel"></i> Export Excel
                        </a>

                        <a :href="exportUrl('pdf')" target="_blank" 
                           class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-xl text-xs font-bold transition shadow-sm">
                            <i class="fa-solid fa-file-pdf"></i> Export PDF
                        </a>
                    </div>
                </div>

                <!-- 4. DETAILED RECORDS TABLE (ITEM DONATIONS) -->
                <div x-show="isItemReport" class="hidden md:block bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-slate-200 dark:border-gray-700 overflow-hidden">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead class="bg-slate-50 dark:bg-gray-700/50 text-gray-500 uppercase tracking-wider border-b dark:border-gray-700">
                            <tr>
                                <th class="p-3.5">Date & Code</th>
                                <th class="p-3.5">Donor Details</th>
                                <th class="p-3.5">Category</th>
                                <th class="p-3.5">Item Description</th>
                                <th class="p-3.5">Qty / Unit</th>
                                <th class="p-3.5">Est. Value</th>
                                <th class="p-3.5">Status</th>
                                <th class="p-3.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y dark:divide-gray-700">
                            <template x-for="item in filteredData" :key="'itm_' + item.id">
                                <tr class="hover:bg-slate-50/80 dark:hover:bg-gray-700/30 transition">
                                    <td class="p-3.5">
                                        <p class="font-mono text-gray-800 dark:text-white font-bold" x-text="new Date(item.donation_date || item.created_at).toLocaleDateString('en-GB')"></p>
                                        <span class="font-mono text-[10px] text-[#0F8B8D] font-semibold" x-text="item.donation_code"></span>
                                    </td>
                                    <td class="p-3.5">
                                        <p class="font-bold text-gray-800 dark:text-white" x-text="item.donor_name"></p>
                                        <p class="text-[11px] text-gray-400" x-text="item.donor_email"></p>
                                        <p class="text-[10px] text-gray-400" x-text="item.donor_mobile"></p>
                                    </td>
                                    <td class="p-3.5">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-teal-50 dark:bg-teal-900/30 text-[#0F8B8D] text-[11px] font-bold">
                                            <i class="fa-solid" :class="item.category_icon || 'fa-gift'"></i>
                                            <span x-text="item.category_name || 'Essential'"></span>
                                        </span>
                                    </td>
                                    <td class="p-3.5 max-w-xs">
                                        <p class="font-medium text-gray-700 dark:text-gray-300 line-clamp-2" x-text="item.item_description"></p>
                                        <span class="text-[10px] text-gray-400">Condition: <strong x-text="item.condition_type"></strong></span>
                                    </td>
                                    <td class="p-3.5">
                                        <span class="font-bold text-emerald-600" x-text="Number(item.quantity).toLocaleString() + ' ' + item.unit"></span>
                                    </td>
                                    <td class="p-3.5 font-bold">
                                        <span x-show="Number(item.estimated_value) > 0" class="text-gray-900 dark:text-white">
                                            ₹<span x-text="Number(item.estimated_value).toLocaleString('en-IN')"></span>
                                        </span>
                                        <span x-show="!Number(item.estimated_value)" class="text-gray-400 text-[11px] font-normal">
                                            In-Kind
                                        </span>
                                    </td>
                                    <td class="p-3.5">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                              :class="{
                                                  'bg-emerald-100 text-emerald-700': item.status === 'Distributed' || item.status === 'Verified',
                                                  'bg-teal-100 text-[#0F8B8D]': item.status === 'Pledged',
                                                  'bg-blue-100 text-blue-700': item.status === 'Collected',
                                                  'bg-rose-100 text-rose-700': item.status === 'Cancelled'
                                              }"
                                              x-text="item.status"></span>
                                    </td>
                                    <td class="p-3.5 text-right whitespace-nowrap">
                                        <a :href="item.receipt_download_url || ('../download-item-receipt.php?id=' + item.id)" target="_blank"
                                           class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-[#0F8B8D]/10 hover:bg-[#0F8B8D]/20 text-[#0F8B8D] rounded-xl font-bold text-xs transition">
                                            <i class="fa-solid fa-file-pdf"></i> Receipt
                                        </a>
                                        <a :href="item.verify_url || ('../verify-item.php?code=' + item.donation_code)" target="_blank"
                                           class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-gray-700 dark:text-gray-200 rounded-xl font-bold text-xs transition ml-1">
                                            <i class="fa-solid fa-qrcode"></i> QR
                                        </a>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- 5. DETAILED RECORDS TABLE (MONETARY DONATIONS) -->
                <div x-show="!isItemReport" class="hidden md:block bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-slate-200 dark:border-gray-700 overflow-hidden">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead class="bg-slate-50 dark:bg-gray-700/50 text-gray-500 uppercase tracking-wider border-b dark:border-gray-700">
                            <tr>
                                <th class="p-3.5">Date</th>
                                <th class="p-3.5">Donor</th>
                                <th class="p-3.5">Amount</th>
                                <th class="p-3.5">Project</th>
                                <th class="p-3.5">Receipt No</th>
                                <th class="p-3.5 text-right">Receipt</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y dark:divide-gray-700">
                            <template x-for="item in filteredData" :key="'don_' + item.id">
                                <tr class="hover:bg-slate-50/80 dark:hover:bg-gray-700/30">
                                    <td class="p-3.5 font-mono" x-text="new Date(item.created_at).toLocaleDateString('en-GB')"></td>
                                    <td class="p-3.5">
                                        <p class="font-bold text-gray-800 dark:text-white" x-text="item.donor_name"></p>
                                        <span class="text-[11px] text-gray-400" x-text="item.donor_email"></span>
                                    </td>
                                    <td class="p-3.5 font-black text-emerald-600">₹<span x-text="Number(item.amount).toLocaleString('en-IN')"></span></td>
                                    <td class="p-3.5 font-medium text-gray-700 dark:text-gray-300" x-text="item.project_name || 'General'"></td>
                                    <td class="p-3.5 font-mono text-gray-500" x-text="item.receipt_no || '—'"></td>
                                    <td class="p-3.5 text-right">
                                        <a :href="'../download-receipt.php?id=' + item.id" target="_blank"
                                           class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded-xl font-bold transition">
                                            <i class="fa-solid fa-file-pdf"></i> PDF
                                        </a>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- 6. MOBILE CARD VIEW (Responsive) -->
                <div class="md:hidden space-y-3">
                    <template x-for="item in filteredData" :key="'mob_' + item.id">
                        <div class="bg-white dark:bg-gray-800 p-4 rounded-2xl shadow-sm border border-slate-200 dark:border-gray-700">
                            <div class="flex justify-between items-start">
                                <div>
                                    <span class="font-mono text-[10px] text-gray-400" x-text="item.donation_code || item.receipt_no"></span>
                                    <h5 class="font-bold text-sm text-gray-800 dark:text-white" x-text="item.donor_name"></h5>
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-teal-100 text-[#0F8B8D]" x-text="item.status || item.payment_status"></span>
                            </div>

                            <div class="mt-2 text-xs text-gray-600 dark:text-gray-300 space-y-1">
                                <p x-show="item.category_name">Category: <strong x-text="item.category_name"></strong></p>
                                <p x-show="item.item_description" class="line-clamp-2" x-text="item.item_description"></p>
                                <div class="flex justify-between text-xs pt-1 border-t dark:border-gray-700">
                                    <span x-show="item.quantity">Qty: <strong class="text-emerald-600" x-text="item.quantity + ' ' + item.unit"></strong></span>
                                    <span x-show="item.amount">Amt: <strong class="text-emerald-600">₹<span x-text="item.amount"></span></strong></span>
                                    <span x-show="Number(item.estimated_value) > 0">Est: <strong>₹<span x-text="item.estimated_value"></span></strong></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

            </div>

            <!-- EMPTY / INITIAL STATE -->
            <div x-show="reportData === null && !loading" class="text-center py-20 text-gray-400 bg-white dark:bg-gray-800 rounded-3xl border border-slate-200 dark:border-gray-700 mt-4">
                <div class="w-16 h-16 rounded-2xl bg-teal-50 dark:bg-teal-900/30 text-[#0F8B8D] flex items-center justify-center mx-auto mb-4 text-2xl shadow-inner">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
                <h4 class="text-base font-bold text-gray-700 dark:text-gray-300">No Report Generated Yet</h4>
                <p class="text-xs text-gray-500 max-w-sm mx-auto mt-1 mb-6">Select your desired date range, category, or status filter above and click "Generate Report".</p>
                <button @click="generateReport" class="px-5 py-2.5 bg-[#0F8B8D] hover:bg-[#0c7274] text-white rounded-xl text-xs font-bold shadow-md transition inline-flex items-center gap-2">
                    <i class="fa-solid fa-play"></i> Run Quick Report
                </button>
            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('reportManager', (config) => ({
        loading: false,
        family: config.initialFamily || 'item',
        searchQuery: '',
        filters: {
            report_type: config.initialFamily === 'item' ? 'item_donations' : 'all_donations',
            date_range: 'all_time',
            category_id: '',
            status: 'all',
            project_id: '',
            custom_start: '',
            custom_end: ''
        },
        reportData: null,
        isItemReport: true,
        categoryBreakdown: [],
        summary: null,

        init() {
            this.generateReport();
        },

        setFamily(newFamily) {
            this.family = newFamily;
            if (newFamily === 'item') {
                this.filters.report_type = 'item_donations';
            } else {
                this.filters.report_type = 'all_donations';
            }
            this.generateReport();
        },

        get filteredData() {
            if (!this.reportData) return [];
            if (!this.searchQuery.trim()) return this.reportData;
            
            const q = this.searchQuery.toLowerCase();
            return this.reportData.filter(item => {
                const donor = (item.donor_name || '').toLowerCase();
                const email = (item.donor_email || '').toLowerCase();
                const code = (item.donation_code || '').toLowerCase();
                const rcp = (item.receipt_no || '').toLowerCase();
                const cat = (item.category_name || '').toLowerCase();
                const desc = (item.item_description || '').toLowerCase();
                const status = (item.status || '').toLowerCase();
                return donor.includes(q) || email.includes(q) || code.includes(q) || rcp.includes(q) || cat.includes(q) || desc.includes(q) || status.includes(q);
            });
        },

        generateReport() {
            this.loading = true;
            this.reportData = null;
            const params = new URLSearchParams(this.filters);

            fetch(`actions/generate_report.php?${params.toString()}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        this.reportData = data.data || [];
                        this.isItemReport = !!data.is_item_report;
                        this.categoryBreakdown = data.category_breakdown || [];
                        this.summary = data.summary || null;
                    }
                })
                .catch(err => {
                    console.error("Report generation error:", err);
                })
                .finally(() => this.loading = false);
        },

        exportUrl(format) {
            const params = new URLSearchParams(this.filters);
            return `actions/export_${format}.php?${params.toString()}`;
        }
    }));
});
</script>

<?php require 'includes/footer.php'; ?>
