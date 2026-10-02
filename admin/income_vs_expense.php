<?php
// ============================================================
// admin/income_vs_expense.php
// Income vs Expense Financial Analytics, Comparison Dashboard & Reports
// Features: Monthly/Yearly Chart.js Visualizations, Breakdown Tables, PDF & Excel Exports
// ============================================================

require_once '../config/db.php';
require_once '../includes/functions.php';

if (!canAccessModule($pdo, 'coordinator', 'page.reports')) {
    setFlash('error', 'Access denied. You do not have permission to access Financial Reports.');
    header('Location: dashboard.php');
    exit;
}

require 'includes/header.php';

// Fetch Active Projects for Filter
$projects = $pdo->query("SELECT id, title FROM projects ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);

// Available Years
$years = [date('Y'), (string)(date('Y') - 1), (string)(date('Y') - 2)];
?>

<!-- Load Chart.js (reusing project's standard Chart.js library) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="flex h-screen overflow-hidden bg-slate-50 dark:bg-gray-900"
    x-data="incomeExpenseManager()"
    x-init="init()">
    <?php require 'includes/sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>
        
        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-8">

            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <div class="flex items-center gap-3">
                        <span class="w-10 h-10 rounded-2xl bg-teal-50 dark:bg-teal-900/40 text-[#0F8B8D] flex items-center justify-center text-lg shadow-sm">
                            <i class="fa-solid fa-scale-balanced"></i>
                        </span>
                        <h3 class="text-2xl md:text-3xl font-black text-gray-800 dark:text-white">
                            Income vs Expense Analytics
                        </h3>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Compare incoming monetary donations against operational expenditures with monthly & yearly financial health indicators.
                    </p>
                </div>

                <!-- Navigation Quick Links -->
                <div class="flex flex-wrap items-center gap-2">
                    <a href="expenses.php" class="px-3.5 py-2 bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                        <i class="fa-solid fa-receipt"></i> Expense Tracker
                    </a>
                    <a href="reports.php" class="px-3.5 py-2 bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                        <i class="fa-solid fa-chart-pie"></i> Donation Reports
                    </a>
                    <a href="beneficiary_reports.php" class="px-3.5 py-2 bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                        <i class="fa-solid fa-hands-holding-child"></i> Beneficiary Reports
                    </a>
                </div>
            </div>

            <!-- Filter Controls Panel -->
            <div class="bg-white dark:bg-gray-800 p-5 md:p-6 rounded-3xl shadow-sm border border-slate-200 dark:border-gray-700 mb-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                    
                    <!-- View Mode (Monthly vs Yearly vs Custom) -->
                    <div>
                        <label class="text-xs font-bold text-gray-700 dark:text-gray-300 block mb-1">Comparison Period</label>
                        <select x-model="filters.view_mode" @change="generateReport()" class="w-full text-xs font-medium p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                            <option value="monthly">Monthly Breakdown (12 Months)</option>
                            <option value="yearly">Yearly Historical Trend</option>
                            <option value="custom">Custom Date Range</option>
                        </select>
                    </div>

                    <!-- Year Selector (For Monthly view) -->
                    <div x-show="filters.view_mode === 'monthly'">
                        <label class="text-xs font-bold text-gray-700 dark:text-gray-300 block mb-1">Calendar Year</label>
                        <select x-model="filters.year" @change="generateReport()" class="w-full text-xs font-medium p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                            <?php foreach ($years as $yr): ?>
                                <option value="<?php echo $yr; ?>"><?php echo $yr; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Project Linkage Filter -->
                    <div>
                        <label class="text-xs font-bold text-gray-700 dark:text-gray-300 block mb-1">Project / Cause</label>
                        <select x-model="filters.project_id" @change="generateReport()" class="w-full text-xs font-medium p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                            <option value="">All Projects & Operations</option>
                            <?php foreach ($projects as $p): ?>
                                <option value="<?php echo (int)$p['id']; ?>"><?php echo htmlspecialchars($p['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Expense Approval Status -->
                    <div>
                        <label class="text-xs font-bold text-gray-700 dark:text-gray-300 block mb-1">Expense Scope</label>
                        <select x-model="filters.expense_status" @change="generateReport()" class="w-full text-xs font-medium p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                            <option value="approved_paid">Approved & Paid Only (Settled)</option>
                            <option value="approved_only">Approved Only</option>
                            <option value="paid_only">Paid Only</option>
                            <option value="all">All Expenses (Including Pending)</option>
                        </select>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-wrap items-end gap-2">
                        <button @click="generateReport()" :disabled="loading" 
                                class="flex-1 bg-[#0F8B8D] hover:bg-[#0c7274] text-white font-bold py-2.5 px-4 rounded-xl shadow-md transition flex items-center justify-center gap-1.5 text-xs disabled:opacity-50">
                            <span x-show="!loading"><i class="fa-solid fa-arrows-rotate"></i> Refresh</span>
                            <span x-show="loading"><i class="fa-solid fa-circle-notch fa-spin"></i> Loading...</span>
                        </button>

                        <button @click="exportExcel()" 
                                class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-3.5 rounded-xl shadow-md transition flex items-center justify-center gap-1 text-xs" title="Export Excel">
                            <i class="fa-solid fa-file-excel"></i> Excel
                        </button>

                        <button @click="exportPdf()" 
                                class="bg-rose-600 hover:bg-rose-700 text-white font-bold py-2.5 px-3.5 rounded-xl shadow-md transition flex items-center justify-center gap-1 text-xs" title="Export PDF">
                            <i class="fa-solid fa-file-pdf"></i> PDF
                        </button>
                    </div>

                </div>

                <!-- Custom Date Inputs -->
                <div x-show="filters.view_mode === 'custom'" x-collapse class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4 pt-3 border-t border-slate-100 dark:border-gray-700">
                    <div>
                        <label class="text-xs font-bold text-gray-600 dark:text-gray-300 block mb-1">Start Date</label>
                        <input type="date" x-model="filters.custom_start" @change="generateReport()" class="w-full text-xs p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-600 dark:text-gray-300 block mb-1">End Date</label>
                        <input type="date" x-model="filters.custom_end" @change="generateReport()" class="w-full text-xs p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none">
                    </div>
                </div>
            </div>

            <!-- KPI SUMMARY METRIC CARDS -->
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6" x-show="reportData !== null" x-transition.opacity>
                
                <!-- Total Income -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase text-gray-400">Total Income (Donations)</span>
                        <span class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 flex items-center justify-center text-sm">
                            <i class="fa-solid fa-arrow-down-long"></i>
                        </span>
                    </div>
                    <p class="text-2xl font-black text-emerald-600 mt-2">₹<span x-text="formatCurrency(summary.totalIncome || 0)"></span></p>
                    <p class="text-[11px] text-gray-400 mt-1"><span x-text="summary.incomeTxCount || 0"></span> successful donations</p>
                </div>

                <!-- Total Expense -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase text-gray-400">Total Expenses</span>
                        <span class="w-9 h-9 rounded-xl bg-rose-50 dark:bg-rose-900/30 text-rose-600 flex items-center justify-center text-sm">
                            <i class="fa-solid fa-arrow-up-long"></i>
                        </span>
                    </div>
                    <p class="text-2xl font-black text-rose-600 mt-2">₹<span x-text="formatCurrency(summary.totalExpense || 0)"></span></p>
                    <p class="text-[11px] text-gray-400 mt-1"><span x-text="summary.expenseTxCount || 0"></span> expense claims</p>
                </div>

                <!-- Net Surplus / Deficit -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase text-gray-400">Net Balance</span>
                        <span class="w-9 h-9 rounded-xl flex items-center justify-center text-sm"
                              :class="summary.netBalance >= 0 ? 'bg-teal-50 dark:bg-teal-900/30 text-[#0F8B8D]' : 'bg-rose-50 dark:bg-rose-900/30 text-rose-600'">
                            <i class="fa-solid" :class="summary.netBalance >= 0 ? 'fa-wallet' : 'fa-triangle-exclamation'"></i>
                        </span>
                    </div>
                    <p class="text-2xl font-black mt-2" 
                       :class="summary.netBalance >= 0 ? 'text-teal-600 dark:text-teal-400' : 'text-rose-600'">
                        ₹<span x-text="formatCurrency(summary.netBalance || 0)"></span>
                    </p>
                    <p class="text-[11px] font-bold mt-1"
                       :class="summary.netBalance >= 0 ? 'text-emerald-500' : 'text-rose-500'">
                        <span x-text="summary.netBalance >= 0 ? '✅ Net Surplus Reserve' : '⚠️ Net Deficit'"></span>
                    </p>
                </div>

                <!-- Operating Ratio -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase text-gray-400">Operating Ratio</span>
                        <span class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 flex items-center justify-center text-sm">
                            <i class="fa-solid fa-percent"></i>
                        </span>
                    </div>
                    <p class="text-2xl font-black text-gray-800 dark:text-white mt-2"><span x-text="summary.operatingRatio || 0"></span>%</p>
                    <p class="text-[11px] text-gray-400 mt-1">Expenses as % of Income</p>
                </div>

                <!-- Savings / Retained Margin -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm col-span-2 sm:col-span-2 lg:col-span-1">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase text-gray-400">Net Margin Rate</span>
                        <span class="w-9 h-9 rounded-xl bg-purple-50 dark:bg-purple-900/30 text-purple-600 flex items-center justify-center text-sm">
                            <i class="fa-solid fa-piggy-bank"></i>
                        </span>
                    </div>
                    <p class="text-2xl font-black text-purple-600 mt-2"><span x-text="summary.savingsRate || 0"></span>%</p>
                    <p class="text-[11px] text-gray-400 mt-1">Net Retained Reserve Margin</p>
                </div>

            </div>

            <!-- CHARTS SECTION (Chart.js Visualizations) -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6" x-show="reportData !== null" x-transition.opacity>
                
                <!-- Main Dual Bar / Line Comparison Chart -->
                <div class="lg:col-span-2 bg-white dark:bg-gray-800 p-5 md:p-6 rounded-3xl border border-slate-200 dark:border-gray-700 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h4 class="text-sm font-bold text-gray-800 dark:text-white flex items-center gap-2">
                                <i class="fa-solid fa-chart-column text-[#0F8B8D]"></i>
                                Income vs Expense Comparative Timeline
                            </h4>
                            <p class="text-xs text-gray-400">Green = Donations (Income), Red = Expenses, Line = Net Balance</p>
                        </div>
                    </div>
                    
                    <div class="relative h-72 md:h-80 w-full">
                        <canvas id="incomeExpenseChart"></canvas>
                    </div>
                </div>

                <!-- Expense Category Breakdown (Doughnut Chart) -->
                <div class="bg-white dark:bg-gray-800 p-5 md:p-6 rounded-3xl border border-slate-200 dark:border-gray-700 shadow-sm flex flex-col justify-between">
                    <div>
                        <h4 class="text-sm font-bold text-gray-800 dark:text-white flex items-center gap-2 mb-1">
                            <i class="fa-solid fa-chart-pie text-[#0F8B8D]"></i>
                            Expense Category Allocation
                        </h4>
                        <p class="text-xs text-gray-400 mb-4">Distribution by operational area</p>
                    </div>
                    
                    <div class="relative h-60 w-full flex items-center justify-center">
                        <canvas id="categoryPieChart"></canvas>
                    </div>

                    <div class="mt-3 pt-3 border-t border-slate-100 dark:border-gray-700 text-center">
                        <span class="text-xs text-gray-400">Total Expenditure: <strong>₹<span x-text="formatCurrency(summary.totalExpense || 0)"></span></strong></span>
                    </div>
                </div>

            </div>

            <!-- DETAILED COMPARATIVE TABLE -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl border border-slate-200 dark:border-gray-700 shadow-sm overflow-hidden mb-6" x-show="reportData !== null">
                <div class="p-5 border-b border-slate-100 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h4 class="text-sm font-bold text-gray-800 dark:text-white">Detailed Period-by-Period Statement</h4>
                        <p class="text-xs text-gray-400">Monthly or yearly breakdown of receipts, disbursements, and surplus margins</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="exportExcel()" class="px-3 py-1.5 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 text-xs font-bold hover:bg-emerald-100 transition flex items-center gap-1">
                            <i class="fa-solid fa-file-excel"></i> Export Table
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                        <thead class="bg-slate-50 dark:bg-gray-700/50 text-[11px] font-bold uppercase text-gray-500 dark:text-gray-400 border-b border-slate-200 dark:border-gray-700">
                            <tr>
                                <th class="p-3.5 text-center">#</th>
                                <th class="p-3.5">Period</th>
                                <th class="p-3.5 text-right">Donations / Income (₹)</th>
                                <th class="p-3.5 text-right">Operating Expenses (₹)</th>
                                <th class="p-3.5 text-right">Net Cashflow / Balance (₹)</th>
                                <th class="p-3.5 text-center">Net Margin %</th>
                                <th class="p-3.5 text-center">Financial Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-gray-700">
                            <template x-for="(row, idx) in comparisonData" :key="row.period_key">
                                <tr class="hover:bg-slate-50/70 dark:hover:bg-gray-700/30 transition">
                                    <td class="p-3.5 text-center font-bold text-gray-400" x-text="idx + 1"></td>
                                    <td class="p-3.5 font-bold text-gray-900 dark:text-white" x-text="row.period_label"></td>
                                    <td class="p-3.5 text-right font-semibold text-emerald-600">₹<span x-text="formatCurrency(row.income)"></span></td>
                                    <td class="p-3.5 text-right font-semibold text-rose-600">₹<span x-text="formatCurrency(row.expense)"></span></td>
                                    <td class="p-3.5 text-right font-black"
                                        :class="row.net_balance >= 0 ? 'text-teal-600 dark:text-teal-400' : 'text-rose-600'">
                                        ₹<span x-text="formatCurrency(row.net_balance)"></span>
                                    </td>
                                    <td class="p-3.5 text-center font-bold" x-text="row.margin_percentage + '%'"></td>
                                    <td class="p-3.5 text-center">
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                                              :class="row.status === 'Surplus' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300'"
                                              x-text="row.status"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot class="bg-slate-50 dark:bg-gray-800/80 font-black border-t-2 border-slate-200 dark:border-gray-700">
                            <tr>
                                <td colspan="2" class="p-3.5 text-center text-gray-800 dark:text-white">CONSOLIDATED TOTAL</td>
                                <td class="p-3.5 text-right text-emerald-600 text-sm">₹<span x-text="formatCurrency(summary.totalIncome || 0)"></span></td>
                                <td class="p-3.5 text-right text-rose-600 text-sm">₹<span x-text="formatCurrency(summary.totalExpense || 0)"></span></td>
                                <td class="p-3.5 text-right text-sm"
                                    :class="summary.netBalance >= 0 ? 'text-teal-600 dark:text-teal-400' : 'text-rose-600'">
                                    ₹<span x-text="formatCurrency(summary.netBalance || 0)"></span>
                                </td>
                                <td class="p-3.5 text-center" x-text="(summary.savingsRate || 0) + '%'"></td>
                                <td class="p-3.5 text-center">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] uppercase tracking-wider"
                                          :class="summary.netBalance >= 0 ? 'bg-emerald-500 text-white' : 'bg-rose-500 text-white'"
                                          x-text="summary.financialStatus"></span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- EXPENSE CATEGORY & PROJECT PERFORMANCE GRID -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6" x-show="reportData !== null">
                
                <!-- Category Summary Table -->
                <div class="bg-white dark:bg-gray-800 rounded-3xl border border-slate-200 dark:border-gray-700 shadow-sm p-5">
                    <h4 class="text-sm font-bold text-gray-800 dark:text-white mb-3 flex items-center gap-2">
                        <i class="fa-solid fa-layer-group text-[#0F8B8D]"></i> Expense Categories Summary
                    </h4>
                    <div class="space-y-3">
                        <template x-for="cat in categoryBreakdown" :key="cat.category_id">
                            <div class="p-3 rounded-2xl bg-slate-50 dark:bg-gray-700/50 border border-slate-200/80 dark:border-gray-600/80 flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-7 h-7 rounded-xl bg-teal-50 dark:bg-teal-900/40 text-[#0F8B8D] flex items-center justify-center text-xs">
                                        <i class="fa-solid" :class="cat.category_icon || 'fa-receipt'"></i>
                                    </span>
                                    <div>
                                        <div class="font-bold text-gray-900 dark:text-white" x-text="cat.category_name"></div>
                                        <div class="text-[10px] text-gray-400" x-text="cat.expense_count + ' entries'"></div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="font-black text-gray-900 dark:text-white">₹<span x-text="formatCurrency(cat.total_amount)"></span></div>
                                    <div class="text-[10px] text-teal-600 font-bold" x-text="cat.percentage + '% of expenses'"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Project Financial Performance -->
                <div class="bg-white dark:bg-gray-800 rounded-3xl border border-slate-200 dark:border-gray-700 shadow-sm p-5">
                    <h4 class="text-sm font-bold text-gray-800 dark:text-white mb-3 flex items-center gap-2">
                        <i class="fa-solid fa-seedling text-emerald-600"></i> Project Allocation Breakdown
                    </h4>
                    <div class="space-y-3">
                        <template x-for="p in projectBreakdown" :key="p.project_id">
                            <div class="p-3 rounded-2xl bg-slate-50 dark:bg-gray-700/50 border border-slate-200/80 dark:border-gray-600/80 text-xs">
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="font-bold text-gray-900 dark:text-white" x-text="p.project_title"></span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                          :class="p.status === 'Surplus' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300'"
                                          x-text="p.status"></span>
                                </div>
                                <div class="flex justify-between text-[11px] text-gray-500 dark:text-gray-400">
                                    <span>Income: <strong class="text-emerald-600">₹<span x-text="formatCurrency(p.income)"></span></strong></span>
                                    <span>Spent: <strong class="text-rose-600">₹<span x-text="formatCurrency(p.expense)"></span></strong></span>
                                    <span>Net: <strong :class="p.net_balance >= 0 ? 'text-teal-600' : 'text-rose-600'">₹<span x-text="formatCurrency(p.net_balance)"></span></strong></span>
                                </div>
                            </div>
                        </template>
                        <template x-if="projectBreakdown.length === 0">
                            <div class="py-8 text-center text-xs text-gray-400">No projects recorded yet.</div>
                        </template>
                    </div>
                </div>

            </div>

        </main>
    </div>
</div>

<script>
    let mainChartInstance = null;
    let pieChartInstance = null;

    function incomeExpenseManager() {
        return {
            loading: false,
            
            filters: {
                view_mode: 'monthly',
                year: '<?php echo date('Y'); ?>',
                project_id: '',
                expense_status: 'approved_paid',
                custom_start: '',
                custom_end: ''
            },

            reportData: null,
            summary: {},
            comparisonData: [],
            categoryBreakdown: [],
            projectBreakdown: [],

            init() {
                this.generateReport();
            },

            serializeParams() {
                const params = new URLSearchParams();
                for (const key in this.filters) {
                    if (this.filters[key] !== '' && this.filters[key] !== null) {
                        params.append(key, this.filters[key]);
                    }
                }
                return params.toString();
            },

            async generateReport() {
                this.loading = true;
                const qs = this.serializeParams();

                try {
                    const res = await fetch('actions/generate_income_expense_report.php?' + qs);
                    const json = await res.json();

                    if (json.success) {
                        this.reportData = json;
                        this.summary = json.summary || {};
                        this.comparisonData = json.comparison_data || [];
                        this.categoryBreakdown = json.category_breakdown || [];
                        this.projectBreakdown = json.project_breakdown || [];

                        this.$nextTick(() => {
                            this.renderCharts(json);
                        });
                    } else {
                        alert(json.message || 'Error generating report.');
                    }
                } catch (e) {
                    console.error('Error:', e);
                } finally {
                    this.loading = false;
                }
            },

            renderCharts(data) {
                const isDark = document.documentElement.classList.contains('dark');
                const gridColor = isDark ? '#374151' : '#f1f5f9';
                const textColor = isDark ? '#9ca3af' : '#64748b';

                // ── 1. Main Dual Bar & Line Chart ─────────────────────
                const ctxMain = document.getElementById('incomeExpenseChart');
                if (ctxMain) {
                    if (mainChartInstance) mainChartInstance.destroy();

                    mainChartInstance = new Chart(ctxMain, {
                        type: 'bar',
                        data: {
                            labels: data.chart_config.labels,
                            datasets: [
                                {
                                    type: 'line',
                                    label: 'Net Balance (₹)',
                                    data: data.chart_config.net_data,
                                    borderColor: '#0F8B8D',
                                    backgroundColor: 'rgba(15, 139, 141, 0.15)',
                                    borderWidth: 3,
                                    tension: 0.35,
                                    pointRadius: 4,
                                    pointHoverRadius: 6,
                                    order: 1
                                },
                                {
                                    type: 'bar',
                                    label: 'Income (Donations ₹)',
                                    data: data.chart_config.income_data,
                                    backgroundColor: '#10B981',
                                    borderRadius: 8,
                                    order: 2
                                },
                                {
                                    type: 'bar',
                                    label: 'Operating Expenses (₹)',
                                    data: data.chart_config.expense_data,
                                    backgroundColor: '#F43F5E',
                                    borderRadius: 8,
                                    order: 3
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'top',
                                    labels: { color: textColor, font: { size: 11, weight: 'bold' } }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            let val = context.parsed.y !== null ? context.parsed.y : context.raw;
                                            return context.dataset.label + ': ₹' + Number(val).toLocaleString('en-IN', {minimumFractionDigits: 2});
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: { color: gridColor },
                                    ticks: {
                                        color: textColor,
                                        callback: function(value) {
                                            return '₹' + Number(value).toLocaleString('en-IN');
                                        }
                                    }
                                },
                                x: {
                                    grid: { display: false },
                                    ticks: { color: textColor, font: { weight: 'bold' } }
                                }
                            }
                        }
                    });
                }

                // ── 2. Expense Category Doughnut Chart ─────────────────
                const ctxPie = document.getElementById('categoryPieChart');
                if (ctxPie) {
                    if (pieChartInstance) pieChartInstance.destroy();

                    const catLabels = this.categoryBreakdown.map(c => c.category_name);
                    const catAmounts = this.categoryBreakdown.map(c => c.total_amount);
                    const palette = ['#0F8B8D', '#3B82F6', '#8B5CF6', '#EC4899', '#F59E0B', '#10B981', '#64748B'];

                    pieChartInstance = new Chart(ctxPie, {
                        type: 'doughnut',
                        data: {
                            labels: catLabels.length ? catLabels : ['No Expenses'],
                            datasets: [{
                                data: catAmounts.length && catAmounts.some(a => a > 0) ? catAmounts : [1],
                                backgroundColor: catAmounts.length && catAmounts.some(a => a > 0) ? palette.slice(0, catLabels.length) : ['#e2e8f0'],
                                borderWidth: 2,
                                borderColor: isDark ? '#1f2937' : '#ffffff'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: { color: textColor, boxWidth: 12, font: { size: 10 } }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return context.label + ': ₹' + Number(context.raw).toLocaleString('en-IN', {minimumFractionDigits: 2});
                                        }
                                    }
                                }
                            },
                            cutout: '65%'
                        }
                    });
                }
            },

            exportExcel() {
                const qs = this.serializeParams();
                window.location.href = 'actions/export_income_expense_excel.php?' + qs;
            },

            exportPdf() {
                const qs = this.serializeParams();
                window.open('actions/export_income_expense_pdf.php?' + qs, '_blank');
            },

            formatCurrency(val) {
                const num = parseFloat(val) || 0;
                return num.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
        };
    }
</script>

<?php require 'includes/footer.php'; ?>
