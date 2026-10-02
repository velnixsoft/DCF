<?php
// ============================================================
// admin/beneficiary_reports.php
// Comprehensive Beneficiary & Assistance Analytics and Reporting
// Features: Location-wise (State/District/Block), Assistance Type-wise,
// Category Breakdown, Granular Rosters, Excel & PDF exports, RBAC support.
// ============================================================

require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../includes/india_locations.php';

if (!canAccessModule($pdo, 'coordinator', 'page.beneficiaries')) {
    setFlash('error', 'Access denied. Coordinator/Manager/Admin role required.');
    header('Location: dashboard.php');
    exit;
}

require 'includes/header.php';

$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$isManagerOrAdmin = checkRole($pdo, 'manager');

// Fetch Beneficiary Categories for Filter
$categories = [];
try {
    $categories = $pdo->query("SELECT id, category_name FROM beneficiary_categories WHERE is_active = 1 ORDER BY category_name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $categories = [];
}

// Fetch Coordinators (for Admin/Manager filters)
$coordinators = [];
if ($isManagerOrAdmin) {
    $coordinators = $pdo->query("SELECT id, name, coordinator_code, role FROM users ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
}

// Assistance Types list
$assistanceTypesList = [
    'Ration & Food Kit',
    'Financial Aid',
    'Medical Aid',
    'Educational Support',
    'Clothing & Blankets',
    'Mobility & Assistive Devices',
    'Shelter & Housing',
    'Skill Training & Livelihood',
    'Emergency Relief',
    'In-Kind Items',
    'Other'
];
?>

<div class="flex h-screen overflow-hidden bg-slate-50 dark:bg-gray-900"
    x-data="beneficiaryReportManager()"
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
                            <i class="fa-solid fa-file-invoice"></i>
                        </span>
                        <h3 class="text-2xl md:text-3xl font-black text-gray-800 dark:text-white">
                            Beneficiary Reports & Impact Analytics
                        </h3>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Location-wise demographic count (State/District/Block), Assistance type breakdown, Valuation summaries, and Granular Excel/PDF exports.
                    </p>
                </div>

                <!-- Navigation Quick Actions -->
                <div class="flex flex-wrap items-center gap-2">
                    <a href="beneficiaries.php" class="px-4 py-2 bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                        <i class="fa-solid fa-users"></i> Beneficiary List
                    </a>
                    <a href="beneficiary_assistance.php" class="px-4 py-2 bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                        <i class="fa-solid fa-hand-holding-heart"></i> Assistance Log
                    </a>
                    <a href="reports.php" class="px-4 py-2 bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                        <i class="fa-solid fa-chart-pie"></i> Donation Reports
                    </a>
                </div>
            </div>

            <!-- RBAC Notice for Coordinator -->
            <?php if (!$isManagerOrAdmin): ?>
                <div class="mb-6 bg-gradient-to-r from-teal-500/10 via-emerald-500/5 to-transparent border border-teal-500/20 rounded-2xl p-3.5 flex items-center justify-between text-xs text-teal-800 dark:text-teal-300">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-id-badge text-teal-600 text-sm"></i>
                        <span><strong>Coordinator View Active:</strong> Showing assistance records and beneficiary distributions logged by your account.</span>
                    </div>
                    <span class="px-2.5 py-0.5 rounded-full bg-teal-100 dark:bg-teal-900/40 text-teal-700 dark:text-teal-300 text-[10px] font-bold">Scoped Access</span>
                </div>
            <?php endif; ?>

            <!-- Report Perspective Mode Switcher -->
            <div class="bg-white dark:bg-gray-800 p-2 rounded-2xl shadow-sm border border-slate-200 dark:border-gray-700 mb-6 flex flex-wrap gap-1.5">
                <button type="button" @click="setReportMode('location_wise')"
                        class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2"
                        :class="filters.report_mode === 'location_wise' ? 'bg-[#0F8B8D] text-white shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'">
                    <i class="fa-solid fa-map-location-dot"></i>
                    Location-Wise (State/District/Block)
                </button>
                <button type="button" @click="setReportMode('assistance_type_wise')"
                        class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2"
                        :class="filters.report_mode === 'assistance_type_wise' ? 'bg-[#0F8B8D] text-white shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'">
                    <i class="fa-solid fa-hand-holding-heart"></i>
                    Assistance Type-Wise
                </button>
                <button type="button" @click="setReportMode('category_wise')"
                        class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2"
                        :class="filters.report_mode === 'category_wise' ? 'bg-[#0F8B8D] text-white shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'">
                    <i class="fa-solid fa-layer-group"></i>
                    Category Breakdown
                </button>
                <button type="button" @click="setReportMode('beneficiaries_list')"
                        class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2"
                        :class="filters.report_mode === 'beneficiaries_list' ? 'bg-[#0F8B8D] text-white shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'">
                    <i class="fa-solid fa-address-book"></i>
                    Beneficiary Roster
                </button>
                <button type="button" @click="setReportMode('assistance_list')"
                        class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2"
                        :class="filters.report_mode === 'assistance_list' ? 'bg-[#0F8B8D] text-white shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'">
                    <i class="fa-solid fa-list-check"></i>
                    Detailed Aid Handover Log
                </button>
            </div>

            <!-- Filter Controls Panel -->
            <div class="bg-white dark:bg-gray-800 p-5 md:p-6 rounded-3xl shadow-sm border border-slate-200 dark:border-gray-700 mb-6">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100 dark:border-gray-700">
                    <span class="text-xs font-black uppercase text-gray-700 dark:text-gray-300 flex items-center gap-2">
                        <i class="fa-solid fa-filter text-[#0F8B8D]"></i> Granular Report Filters
                    </span>
                    <button type="button" @click="resetFilters" class="text-xs text-gray-500 hover:text-red-600 font-bold transition flex items-center gap-1">
                        <i class="fa-solid fa-rotate-left"></i> Reset Filters
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
                    
                    <!-- Date Period -->
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

                    <!-- State Filter -->
                    <div>
                        <label class="text-xs font-bold text-gray-700 dark:text-gray-300 block mb-1">State / UT</label>
                        <select x-model="filters.state" @change="onStateChange()" class="w-full text-xs font-medium p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                            <option value="">All States</option>
                            <template x-for="st in availableStates" :key="st">
                                <option :value="st" x-text="st"></option>
                            </template>
                        </select>
                    </div>

                    <!-- District Filter -->
                    <div>
                        <label class="text-xs font-bold text-gray-700 dark:text-gray-300 block mb-1">District</label>
                        <select x-model="filters.district" class="w-full text-xs font-medium p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                            <option value="">All Districts</option>
                            <template x-for="dst in availableDistricts" :key="dst">
                                <option :value="dst" x-text="dst"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Block Filter -->
                    <div>
                        <label class="text-xs font-bold text-gray-700 dark:text-gray-300 block mb-1">Block / Tehsil</label>
                        <input type="text" x-model="filters.block" placeholder="Type block name..." class="w-full text-xs font-medium p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                    </div>

                    <!-- Category Filter -->
                    <div>
                        <label class="text-xs font-bold text-gray-700 dark:text-gray-300 block mb-1">Beneficiary Category</label>
                        <select x-model="filters.category_id" class="w-full text-xs font-medium p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo (int)$cat['id']; ?>">
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Assistance Type Filter -->
                    <div>
                        <label class="text-xs font-bold text-gray-700 dark:text-gray-300 block mb-1">Assistance Type</label>
                        <select x-model="filters.assistance_type" class="w-full text-xs font-medium p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                            <option value="">All Assistance Types</option>
                            <?php foreach ($assistanceTypesList as $type): ?>
                                <option value="<?php echo htmlspecialchars($type); ?>">
                                    <?php echo htmlspecialchars($type); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label class="text-xs font-bold text-gray-700 dark:text-gray-300 block mb-1">Beneficiary Status</label>
                        <select x-model="filters.status" class="w-full text-xs font-medium p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                            <option value="all">All Statuses</option>
                            <option value="Active">Active</option>
                            <option value="Under Review">Under Review</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>

                    <?php if ($isManagerOrAdmin): ?>
                    <!-- Coordinator Filter (Admin/Manager only) -->
                    <div>
                        <label class="text-xs font-bold text-gray-700 dark:text-gray-300 block mb-1">Assigned Coordinator</label>
                        <select x-model="filters.coordinator_id" class="w-full text-xs font-medium p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                            <option value="">All Coordinators</option>
                            <?php foreach ($coordinators as $coord): ?>
                                <option value="<?php echo (int)$coord['id']; ?>">
                                    <?php echo htmlspecialchars($coord['name'] . ($coord['coordinator_code'] ? ' (' . $coord['coordinator_code'] . ')' : '')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <!-- Action Buttons -->
                    <div class="sm:col-span-2 md:col-span-3 lg:col-span-2 xl:col-span-4 flex flex-wrap items-end gap-2 pt-1">
                        <button @click="generateReport" :disabled="loading" 
                                class="bg-[#0F8B8D] hover:bg-[#0c7274] text-white font-bold py-2.5 px-5 rounded-xl shadow-md transition flex items-center justify-center gap-2 text-xs disabled:opacity-50">
                            <span x-show="!loading"><i class="fa-solid fa-magnifying-glass-chart"></i> Generate Report</span>
                            <span x-show="loading"><i class="fa-solid fa-circle-notch fa-spin"></i> Loading...</span>
                        </button>

                        <button @click="exportExcel" 
                                class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-4 rounded-xl shadow-md transition flex items-center justify-center gap-2 text-xs">
                            <i class="fa-solid fa-file-excel"></i> Export Excel
                        </button>

                        <button @click="exportPdf" 
                                class="bg-rose-600 hover:bg-rose-700 text-white font-bold py-2.5 px-4 rounded-xl shadow-md transition flex items-center justify-center gap-2 text-xs">
                            <i class="fa-solid fa-file-pdf"></i> Export PDF
                        </button>

                        <button @click="printReport" 
                                class="bg-slate-700 hover:bg-slate-800 text-white font-bold py-2.5 px-4 rounded-xl shadow-md transition flex items-center justify-center gap-2 text-xs">
                            <i class="fa-solid fa-print"></i> Print
                        </button>
                    </div>

                </div>

                <!-- Custom Date Range Inputs -->
                <div x-show="filters.date_range === 'custom'" x-collapse class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4 pt-3 border-t border-slate-100 dark:border-gray-700">
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

            <!-- SUMMARY KPI CARDS STRIP -->
            <div x-show="reportData !== null" class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6" x-transition.opacity>
                
                <!-- Total Beneficiaries -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase text-gray-400">Total Enrolled</span>
                        <span class="w-9 h-9 rounded-xl bg-teal-50 dark:bg-teal-900/30 text-[#0F8B8D] flex items-center justify-center text-sm">
                            <i class="fa-solid fa-users"></i>
                        </span>
                    </div>
                    <p class="text-2xl font-black text-gray-800 dark:text-white mt-2" x-text="summary.totalBeneficiaries || 0"></p>
                    <p class="text-[11px] text-gray-400 mt-1">
                        <span class="text-emerald-500 font-bold" x-text="summary.activeBeneficiaries || 0"></span> Active, 
                        <span class="text-amber-500 font-bold" x-text="summary.reviewBeneficiaries || 0"></span> Review
                    </p>
                </div>

                <!-- Total Combined Aid Valuation -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase text-gray-400">Total Aid Value</span>
                        <span class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 flex items-center justify-center text-sm">
                            <i class="fa-solid fa-indian-rupee-sign"></i>
                        </span>
                    </div>
                    <p class="text-2xl font-black text-emerald-600 mt-2">₹<span x-text="formatCurrency(summary.combinedAidValue || 0)"></span></p>
                    <p class="text-[11px] text-gray-400 mt-1">
                        Cash ₹<span x-text="formatCurrency(summary.totalCashAmount || 0)"></span> | In-Kind ₹<span x-text="formatCurrency(summary.totalInKindValue || 0)"></span>
                    </p>
                </div>

                <!-- Total Aid Distribution Events -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase text-gray-400">Aid Handover Events</span>
                        <span class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 flex items-center justify-center text-sm">
                            <i class="fa-solid fa-hand-holding-heart"></i>
                        </span>
                    </div>
                    <p class="text-2xl font-black text-indigo-600 mt-2" x-text="summary.totalAidEvents || 0"></p>
                    <p class="text-[11px] text-gray-400 mt-1">Total distribution instances</p>
                </div>

                <!-- Unique Beneficiaries Reached -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase text-gray-400">Assisted Reached</span>
                        <span class="w-9 h-9 rounded-xl bg-purple-50 dark:bg-purple-900/30 text-purple-600 flex items-center justify-center text-sm">
                            <i class="fa-solid fa-user-check"></i>
                        </span>
                    </div>
                    <p class="text-2xl font-black text-purple-600 mt-2" x-text="summary.assistedBeneficiaries || 0"></p>
                    <p class="text-[11px] text-gray-400 mt-1">
                        <span x-text="calculateReachPercentage()"></span>% of total enrolled
                    </p>
                </div>

                <!-- Top State & Assistance -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm col-span-2 sm:col-span-2 lg:col-span-1">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase text-gray-400">Top Aid & Cluster</span>
                        <span class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 flex items-center justify-center text-sm">
                            <i class="fa-solid fa-trophy"></i>
                        </span>
                    </div>
                    <p class="text-sm font-black text-gray-800 dark:text-white truncate mt-2" x-text="summary.topAssistanceType || 'N/A'"></p>
                    <p class="text-[11px] text-gray-400 mt-1">
                        State: <span class="font-bold text-gray-600 dark:text-gray-300" x-text="summary.topState || 'N/A'"></span>
                    </p>
                </div>

            </div>

            <!-- RESULTS SECTION -->
            <div x-show="reportData !== null" x-transition.opacity>

                <!-- 1. LOCATION-WISE REPORT TAB -->
                <div x-show="filters.report_mode === 'location_wise'" class="space-y-6">
                    
                    <!-- State-Level Rollup Summary Cards -->
                    <div class="bg-white dark:bg-gray-800 p-5 md:p-6 rounded-3xl border border-slate-200 dark:border-gray-700 shadow-sm">
                        <h4 class="text-sm font-bold text-gray-800 dark:text-white flex items-center gap-2 mb-4">
                            <i class="fa-solid fa-map-location-dot text-[#0F8B8D]"></i>
                            State & UT Geographic Aggregates
                        </h4>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <template x-for="st in stateRollup" :key="st.state_name">
                                <div class="p-4 rounded-2xl bg-slate-50 dark:bg-gray-700/50 border border-slate-200 dark:border-gray-600 hover:border-[#0F8B8D] transition">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <h5 class="text-sm font-black text-gray-900 dark:text-white" x-text="st.state_name"></h5>
                                            <span class="text-[11px] text-gray-400" x-text="st.districts_count + ' active districts'"></span>
                                        </div>
                                        <span class="px-2.5 py-1 rounded-full bg-teal-50 dark:bg-teal-900/40 text-[#0F8B8D] text-xs font-black" x-text="st.beneficiary_count + ' beneficiaries'"></span>
                                    </div>
                                    <div class="mt-3 pt-3 border-t border-slate-200/60 dark:border-gray-600/60 flex items-center justify-between text-xs">
                                        <span class="text-gray-500 dark:text-gray-400">Aid Handover: <strong class="text-indigo-600" x-text="st.aid_events_count"></strong></span>
                                        <span class="text-gray-500 dark:text-gray-400">Valuation: <strong class="text-emerald-600">₹<span x-text="formatCurrency(st.total_aid_value)"></span></strong></span>
                                    </div>
                                </div>
                            </template>
                            <template x-if="stateRollup.length === 0">
                                <div class="col-span-full py-8 text-center text-xs text-gray-400">No state geographic records found matching filters.</div>
                            </template>
                        </div>
                    </div>

                    <!-- Granular Location Table (State -> District -> Block) -->
                    <div class="bg-white dark:bg-gray-800 rounded-3xl border border-slate-200 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="p-5 border-b border-slate-100 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div>
                                <h4 class="text-sm font-bold text-gray-800 dark:text-white">State $\to$ District $\to$ Block Detailed Matrix</h4>
                                <p class="text-xs text-gray-400">Beneficiary count, Aid events, and Cash vs In-Kind distribution values</p>
                            </div>
                            <div class="w-full sm:w-64">
                                <input type="text" x-model="searchTable" placeholder="Search locations..." class="w-full text-xs p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none">
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                                <thead class="bg-slate-50 dark:bg-gray-700/50 text-[11px] font-bold uppercase text-gray-500 dark:text-gray-400 border-b border-slate-200 dark:border-gray-700">
                                    <tr>
                                        <th class="p-3.5 text-center">#</th>
                                        <th class="p-3.5">State / UT</th>
                                        <th class="p-3.5">District</th>
                                        <th class="p-3.5">Block / Tehsil</th>
                                        <th class="p-3.5 text-center">Beneficiaries</th>
                                        <th class="p-3.5 text-center">Aid Events</th>
                                        <th class="p-3.5 text-right">Cash Aid (₹)</th>
                                        <th class="p-3.5 text-right">In-Kind Valuation (₹)</th>
                                        <th class="p-3.5 text-right">Total Aid Valuation (₹)</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-gray-700">
                                    <template x-for="(loc, idx) in filteredLocations" :key="idx">
                                        <tr class="hover:bg-slate-50/70 dark:hover:bg-gray-700/30 transition">
                                            <td class="p-3.5 text-center font-bold text-gray-400" x-text="idx + 1"></td>
                                            <td class="p-3.5 font-bold text-gray-900 dark:text-white" x-text="loc.state_name"></td>
                                            <td class="p-3.5 font-semibold text-gray-700 dark:text-gray-300" x-text="loc.district_name"></td>
                                            <td class="p-3.5 text-gray-600 dark:text-gray-400" x-text="loc.block_name"></td>
                                            <td class="p-3.5 text-center font-bold text-teal-600 dark:text-teal-400" x-text="loc.beneficiary_count"></td>
                                            <td class="p-3.5 text-center font-bold text-indigo-600 dark:text-indigo-400" x-text="loc.aid_events_count"></td>
                                            <td class="p-3.5 text-right font-medium">₹<span x-text="formatCurrency(loc.cash_amount)"></span></td>
                                            <td class="p-3.5 text-right font-medium">₹<span x-text="formatCurrency(loc.in_kind_value)"></span></td>
                                            <td class="p-3.5 text-right font-black text-emerald-600">₹<span x-text="formatCurrency(loc.total_aid_value)"></span></td>
                                        </tr>
                                    </template>
                                    <template x-if="filteredLocations.length === 0">
                                        <tr>
                                            <td colspan="9" class="p-8 text-center text-xs text-gray-400">No matching location data found.</td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- 2. ASSISTANCE TYPE-WISE REPORT TAB -->
                <div x-show="filters.report_mode === 'assistance_type_wise'" class="space-y-6">
                    <div class="bg-white dark:bg-gray-800 rounded-3xl border border-slate-200 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="p-5 border-b border-slate-100 dark:border-gray-700 flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-bold text-gray-800 dark:text-white">Assistance Type Breakdown & Disbursement Matrix</h4>
                                <p class="text-xs text-gray-400">Breakdown of aid categories: Ration, Cash Grants, Medical, Education, Blankets, Mobility Kits, etc.</p>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                                <thead class="bg-slate-50 dark:bg-gray-700/50 text-[11px] font-bold uppercase text-gray-500 dark:text-gray-400 border-b border-slate-200 dark:border-gray-700">
                                    <tr>
                                        <th class="p-3.5 text-center">#</th>
                                        <th class="p-3.5">Assistance Type / Category</th>
                                        <th class="p-3.5 text-center">Distribution Events</th>
                                        <th class="p-3.5 text-center">Beneficiaries Served</th>
                                        <th class="p-3.5 text-right">Direct Cash Support (₹)</th>
                                        <th class="p-3.5 text-right">In-Kind Goods Value (₹)</th>
                                        <th class="p-3.5 text-right">Total Valuation (₹)</th>
                                        <th class="p-3.5 text-center">Total Quantity Disbursed</th>
                                        <th class="p-3.5">Units</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-gray-700">
                                    <template x-for="(type, idx) in assistanceTypeBreakdown" :key="idx">
                                        <tr class="hover:bg-slate-50/70 dark:hover:bg-gray-700/30 transition">
                                            <td class="p-3.5 text-center font-bold text-gray-400" x-text="idx + 1"></td>
                                            <td class="p-3.5 font-black text-gray-900 dark:text-white flex items-center gap-2">
                                                <span class="w-7 h-7 rounded-lg bg-teal-50 dark:bg-teal-900/30 text-[#0F8B8D] flex items-center justify-center text-xs">
                                                    <i class="fa-solid fa-gift"></i>
                                                </span>
                                                <span x-text="type.assistance_type"></span>
                                            </td>
                                            <td class="p-3.5 text-center font-bold text-indigo-600 dark:text-indigo-400" x-text="type.distribution_count"></td>
                                            <td class="p-3.5 text-center font-bold text-purple-600 dark:text-purple-400" x-text="type.beneficiaries_served"></td>
                                            <td class="p-3.5 text-right font-medium">₹<span x-text="formatCurrency(type.total_cash)"></span></td>
                                            <td class="p-3.5 text-right font-medium">₹<span x-text="formatCurrency(type.total_in_kind)"></span></td>
                                            <td class="p-3.5 text-right font-black text-emerald-600 text-sm">₹<span x-text="formatCurrency(type.total_valuation)"></span></td>
                                            <td class="p-3.5 text-center font-bold text-gray-700 dark:text-gray-300" x-text="type.total_quantity"></td>
                                            <td class="p-3.5 text-gray-500" x-text="type.units_used || 'units'"></td>
                                        </tr>
                                    </template>
                                    <template x-if="assistanceTypeBreakdown.length === 0">
                                        <tr>
                                            <td colspan="9" class="p-8 text-center text-xs text-gray-400">No assistance distribution records found matching filters.</td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- 3. CATEGORY BREAKDOWN TAB -->
                <div x-show="filters.report_mode === 'category_wise'" class="space-y-6">
                    <div class="bg-white dark:bg-gray-800 rounded-3xl border border-slate-200 dark:border-gray-700 shadow-sm p-6">
                        <h4 class="text-sm font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                            <i class="fa-solid fa-layer-group text-[#0F8B8D]"></i>
                            Beneficiary Category & Vulnerability Group Breakdown
                        </h4>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <template x-for="cat in categoryBreakdown" :key="cat.category_id || cat.category_name">
                                <div class="p-5 rounded-2xl bg-slate-50 dark:bg-gray-700/50 border border-slate-200 dark:border-gray-600">
                                    <div class="flex items-center justify-between">
                                        <h5 class="text-sm font-bold text-gray-900 dark:text-white" x-text="cat.category_name"></h5>
                                        <span class="px-2.5 py-1 rounded-full bg-teal-100 dark:bg-teal-900/50 text-[#0F8B8D] text-xs font-black" x-text="cat.beneficiary_count + ' enrolled'"></span>
                                    </div>
                                    <div class="mt-4 pt-3 border-t border-slate-200 dark:border-gray-600 flex justify-between text-xs">
                                        <span class="text-gray-500">Aid Handover Count: <strong class="text-indigo-600" x-text="cat.assistance_count"></strong></span>
                                        <span class="text-gray-500">Total Valuation: <strong class="text-emerald-600">₹<span x-text="formatCurrency(cat.total_aid_value)"></span></strong></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- 4. BENEFICIARY ROSTER TAB -->
                <div x-show="filters.report_mode === 'beneficiaries_list'" class="space-y-6">
                    <div class="bg-white dark:bg-gray-800 rounded-3xl border border-slate-200 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="p-5 border-b border-slate-100 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div>
                                <h4 class="text-sm font-bold text-gray-800 dark:text-white">Beneficiary Granular Roster (<span x-text="beneficiariesList.length"></span> Records)</h4>
                                <p class="text-xs text-gray-400">Complete listing of enrolled individuals, contact details, location, and lifetime aid received</p>
                            </div>
                            <div class="w-full sm:w-64">
                                <input type="text" x-model="searchBeneficiary" placeholder="Filter names, code, contact..." class="w-full text-xs p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none">
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                                <thead class="bg-slate-50 dark:bg-gray-700/50 text-[11px] font-bold uppercase text-gray-500 dark:text-gray-400 border-b border-slate-200 dark:border-gray-700">
                                    <tr>
                                        <th class="p-3.5 text-center">#</th>
                                        <th class="p-3.5">Code</th>
                                        <th class="p-3.5">Beneficiary Name</th>
                                        <th class="p-3.5">Contact No</th>
                                        <th class="p-3.5">Category</th>
                                        <th class="p-3.5">Location (Dist, State)</th>
                                        <th class="p-3.5 text-center">Status</th>
                                        <th class="p-3.5 text-center">Aid Count</th>
                                        <th class="p-3.5 text-right">Total Aid Received (₹)</th>
                                        <th class="p-3.5 text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-gray-700">
                                    <template x-for="(b, idx) in filteredBeneficiaries" :key="b.id">
                                        <tr class="hover:bg-slate-50/70 dark:hover:bg-gray-700/30 transition">
                                            <td class="p-3.5 text-center font-bold text-gray-400" x-text="idx + 1"></td>
                                            <td class="p-3.5 font-bold text-indigo-600" x-text="b.beneficiary_code || ('ID:' + b.id)"></td>
                                            <td class="p-3.5 font-bold text-gray-900 dark:text-white" x-text="b.name"></td>
                                            <td class="p-3.5" x-text="b.contact || '-'"></td>
                                            <td class="p-3.5" x-text="b.category_name || 'General'"></td>
                                            <td class="p-3.5 text-gray-500" x-text="(b.district || '') + (b.state ? ', ' + b.state : '')"></td>
                                            <td class="p-3.5 text-center">
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                                      :class="b.status === 'Active' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'"
                                                      x-text="b.status"></span>
                                            </td>
                                            <td class="p-3.5 text-center font-bold text-teal-600" x-text="b.assistance_count"></td>
                                            <td class="p-3.5 text-right font-black text-emerald-600">₹<span x-text="formatCurrency(b.total_aid_received)"></span></td>
                                            <td class="p-3.5 text-center">
                                                <a :href="'beneficiary_profile.php?id=' + b.id" target="_blank" class="p-1.5 rounded-lg text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/40 transition">
                                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    </template>
                                    <template x-if="filteredBeneficiaries.length === 0">
                                        <tr>
                                            <td colspan="10" class="p-8 text-center text-xs text-gray-400">No beneficiaries found matching search filters.</td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- 5. ASSISTANCE LOG TAB -->
                <div x-show="filters.report_mode === 'assistance_list'" class="space-y-6">
                    <div class="bg-white dark:bg-gray-800 rounded-3xl border border-slate-200 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="p-5 border-b border-slate-100 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div>
                                <h4 class="text-sm font-bold text-gray-800 dark:text-white">Assistance Handover Log (<span x-text="assistanceList.length"></span> Records)</h4>
                                <p class="text-xs text-gray-400">Detailed transactions of assistance items and financial grants disbursed to beneficiaries</p>
                            </div>
                            <div class="w-full sm:w-64">
                                <input type="text" x-model="searchAssistance" placeholder="Filter assistance vouchers..." class="w-full text-xs p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none">
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                                <thead class="bg-slate-50 dark:bg-gray-700/50 text-[11px] font-bold uppercase text-gray-500 dark:text-gray-400 border-b border-slate-200 dark:border-gray-700">
                                    <tr>
                                        <th class="p-3.5 text-center">#</th>
                                        <th class="p-3.5">Date</th>
                                        <th class="p-3.5">Voucher / Code</th>
                                        <th class="p-3.5">Beneficiary</th>
                                        <th class="p-3.5">Assistance Type</th>
                                        <th class="p-3.5">Description</th>
                                        <th class="p-3.5 text-right">Cash (₹)</th>
                                        <th class="p-3.5 text-right">In-Kind (₹)</th>
                                        <th class="p-3.5 text-right">Total (₹)</th>
                                        <th class="p-3.5 text-center">Qty</th>
                                        <th class="p-3.5">Coordinator / Distributor</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-gray-700">
                                    <template x-for="(a, idx) in filteredAssistance" :key="a.id">
                                        <tr class="hover:bg-slate-50/70 dark:hover:bg-gray-700/30 transition">
                                            <td class="p-3.5 text-center font-bold text-gray-400" x-text="idx + 1"></td>
                                            <td class="p-3.5 text-gray-500 whitespace-nowrap" x-text="formatDate(a.date)"></td>
                                            <td class="p-3.5 font-bold text-indigo-600" x-text="a.assistance_code || a.receipt_no || ('AID-' + a.id)"></td>
                                            <td class="p-3.5 font-bold text-gray-900 dark:text-white" x-text="a.beneficiary_name"></td>
                                            <td class="p-3.5">
                                                <span class="px-2 py-0.5 rounded-md bg-teal-50 dark:bg-teal-900/30 text-[#0F8B8D] text-[10px] font-bold" x-text="a.assistance_type"></span>
                                            </td>
                                            <td class="p-3.5 text-gray-500 max-w-xs truncate" :title="a.description" x-text="a.description"></td>
                                            <td class="p-3.5 text-right font-medium">₹<span x-text="formatCurrency(a.amount)"></span></td>
                                            <td class="p-3.5 text-right font-medium">₹<span x-text="formatCurrency(a.estimated_value)"></span></td>
                                            <td class="p-3.5 text-right font-black text-emerald-600">₹<span x-text="formatCurrency(a.total_value)"></span></td>
                                            <td class="p-3.5 text-center" x-text="a.quantity + ' ' + (a.unit || '')"></td>
                                            <td class="p-3.5 text-gray-500" x-text="a.coordinator_name || a.given_by || 'Coordinator'"></td>
                                        </tr>
                                    </template>
                                    <template x-if="filteredAssistance.length === 0">
                                        <tr>
                                            <td colspan="11" class="p-8 text-center text-xs text-gray-400">No aid handover transactions found matching search.</td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>

        </main>
    </div>
</div>

<script>
    const indiaLocationsMap = <?php echo india_state_district_js(); ?>;

    function beneficiaryReportManager() {
        return {
            loading: false,
            indiaLocations: indiaLocationsMap,
            availableStates: Object.keys(indiaLocationsMap),
            availableDistricts: [],
            searchTable: '',
            searchBeneficiary: '',
            searchAssistance: '',
            
            filters: {
                report_mode: 'location_wise',
                date_range: 'all_time',
                state: '',
                district: '',
                block: '',
                category_id: '',
                assistance_type: '',
                status: 'all',
                coordinator_id: '',
                custom_start: '',
                custom_end: ''
            },

            reportData: null,
            summary: {},
            stateRollup: [],
            locationBreakdown: [],
            assistanceTypeBreakdown: [],
            categoryBreakdown: [],
            beneficiariesList: [],
            assistanceList: [],

            init() {
                this.generateReport();
            },

            setReportMode(mode) {
                this.filters.report_mode = mode;
                this.generateReport();
            },

            onStateChange() {
                if (this.filters.state && this.indiaLocations[this.filters.state]) {
                    this.availableDistricts = this.indiaLocations[this.filters.state];
                } else {
                    this.availableDistricts = [];
                }
                this.filters.district = '';
            },

            resetFilters() {
                this.filters = {
                    report_mode: this.filters.report_mode || 'location_wise',
                    date_range: 'all_time',
                    state: '',
                    district: '',
                    block: '',
                    category_id: '',
                    assistance_type: '',
                    status: 'all',
                    coordinator_id: '',
                    custom_start: '',
                    custom_end: ''
                };
                this.availableDistricts = [];
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
                    const res = await fetch('actions/generate_beneficiary_report.php?' + qs);
                    const json = await res.json();

                    if (json.success) {
                        this.reportData = json;
                        this.summary = json.summary || {};
                        this.stateRollup = json.state_rollup || [];
                        this.locationBreakdown = json.location_breakdown || [];
                        this.assistanceTypeBreakdown = json.assistance_type_breakdown || [];
                        this.categoryBreakdown = json.category_breakdown || [];
                        this.beneficiariesList = json.beneficiaries_list || [];
                        this.assistanceList = json.assistance_list || [];
                    } else {
                        alert(json.message || 'Unable to generate report.');
                    }
                } catch (e) {
                    console.error('Report Generation Error:', e);
                } finally {
                    this.loading = false;
                }
            },

            exportExcel() {
                const qs = this.serializeParams();
                window.location.href = 'actions/export_beneficiary_excel.php?' + qs;
            },

            exportPdf() {
                const qs = this.serializeParams();
                window.open('actions/export_beneficiary_pdf.php?' + qs, '_blank');
            },

            printReport() {
                window.print();
            },

            get filteredLocations() {
                if (!this.searchTable) return this.locationBreakdown;
                const q = this.searchTable.toLowerCase();
                return this.locationBreakdown.filter(l => 
                    (l.state_name && l.state_name.toLowerCase().includes(q)) ||
                    (l.district_name && l.district_name.toLowerCase().includes(q)) ||
                    (l.block_name && l.block_name.toLowerCase().includes(q))
                );
            },

            get filteredBeneficiaries() {
                if (!this.searchBeneficiary) return this.beneficiariesList;
                const q = this.searchBeneficiary.toLowerCase();
                return this.beneficiariesList.filter(b => 
                    (b.name && b.name.toLowerCase().includes(q)) ||
                    (b.beneficiary_code && b.beneficiary_code.toLowerCase().includes(q)) ||
                    (b.contact && b.contact.includes(q)) ||
                    (b.district && b.district.toLowerCase().includes(q))
                );
            },

            get filteredAssistance() {
                if (!this.searchAssistance) return this.assistanceList;
                const q = this.searchAssistance.toLowerCase();
                return this.assistanceList.filter(a => 
                    (a.beneficiary_name && a.beneficiary_name.toLowerCase().includes(q)) ||
                    (a.assistance_code && a.assistance_code.toLowerCase().includes(q)) ||
                    (a.assistance_type && a.assistance_type.toLowerCase().includes(q)) ||
                    (a.description && a.description.toLowerCase().includes(q))
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
            },

            calculateReachPercentage() {
                const total = parseInt(this.summary.totalBeneficiaries) || 0;
                const reached = parseInt(this.summary.assistedBeneficiaries) || 0;
                if (total === 0) return '0';
                return ((reached / total) * 100).toFixed(1);
            }
        };
    }
</script>

<?php require 'includes/footer.php'; ?>
