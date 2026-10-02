<?php
// ============================================================
// admin/join_applications.php
// Unified Join Foundation, Join Project & Job Applications Manager
// Author: VELNIX SOFT / Antigravity AI
// Date: 2026-09-12
// ============================================================

require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../includes/join_application_helper.php';

if (!checkRole($pdo, 'coordinator')) {
    setFlash('error', 'Unauthorized access. Coordinator/Manager/Admin role required.');
    header('Location: dashboard.php');
    exit;
}

require 'includes/header.php';

$csrfToken = generateCsrfToken();

// Fetch All Join Applications with Linked Projects and Jobs
$applications = [];
try {
    $sql = "
        SELECT j.*, 
               p.title AS project_title, p.thumbnail_image AS project_thumb,
               o.title AS job_title, o.job_code, o.location AS job_location, o.salary_range AS job_salary,
               u.name AS reviewer_name
        FROM join_applications j
        LEFT JOIN projects p ON j.project_id = p.id
        LEFT JOIN job_openings o ON j.job_id = o.id
        LEFT JOIN users u ON j.reviewed_by = u.id
        ORDER BY j.id DESC
    ";
    $applications = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $applications = [];
}

// Compute Real-Time Metric Counters
$totalCount = count($applications);
$foundationCount = 0;
$projectCount = 0;
$jobCount = 0;
$paidCount = 0;
$paidAmount = 0.00;
$pendingReviewCount = 0;
$approvedCount = 0;

foreach ($applications as $app) {
    if ($app['application_type'] === 'join_foundation') {
        $foundationCount++;
    } elseif ($app['application_type'] === 'join_project') {
        $projectCount++;
    } elseif ($app['application_type'] === 'job_application') {
        $jobCount++;
    }

    if ($app['payment_status'] === 'paid') {
        $paidCount++;
        $paidAmount += (float)($app['fee_amount'] ?? 0);
    }

    if ($app['status'] === 'pending') {
        $pendingReviewCount++;
    } elseif ($app['status'] === 'approved' || $app['status'] === 'onboarded') {
        $approvedCount++;
    }
}
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900" x-data="joinApplicationsAdmin(<?php echo htmlspecialchars(json_encode($applications, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>)" x-cloak>
    <?php require 'includes/sidebar.php'; ?>

    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6 lg:p-8">
            <!-- Flash Message -->
            <?php displayFlash(); ?>

            <!-- Page Title & Quick Actions -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <div>
                    <div class="flex items-center gap-2.5">
                        <span class="p-2.5 rounded-2xl bg-teal-500/10 text-[#0F8B8D] dark:text-teal-400">
                            <i class="fa-solid fa-id-card-clip text-2xl"></i>
                        </span>
                        <div>
                            <h1 class="text-2xl md:text-3xl font-black text-gray-900 dark:text-white tracking-tight">Join Applications & Induction Roster</h1>
                            <p class="text-xs md:text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                                Unified applications for Foundation Membership, Project Volunteering, and Job Openings with fee status tracking.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <a href="../join-us.php" target="_blank" class="inline-flex items-center gap-2 px-4 py-2.5 bg-[#0F8B8D] hover:bg-teal-700 text-white text-xs font-bold rounded-xl shadow-sm transition">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                        <span>Open Public Join Portal</span>
                    </a>

                    <button type="button" 
                            @click="exportToExcel()" 
                            class="inline-flex items-center gap-2 px-3.5 py-2.5 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 text-xs font-semibold rounded-xl transition shadow-xs">
                        <i class="fa-solid fa-file-excel text-emerald-600"></i>
                        <span>Export Excel</span>
                    </button>
                </div>
            </div>

            <!-- KPI Metric Stats (4 Top Cards) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                
                <!-- Card 1: Total Applications -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-200 dark:border-gray-700 shadow-xs">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total Received</p>
                            <h3 class="text-2xl font-black text-gray-900 dark:text-white mt-1"><?php echo number_format($totalCount); ?></h3>
                            <span class="text-[11px] text-teal-600 font-semibold mt-1 inline-block">
                                <i class="fa-solid fa-users"></i> All 3 Stream Applications
                            </span>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-teal-50 dark:bg-teal-900/30 text-[#0F8B8D] dark:text-teal-400 flex items-center justify-center text-xl">
                            <i class="fa-solid fa-layer-group"></i>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Join Foundation -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-200 dark:border-gray-700 shadow-xs">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-teal-700 dark:text-teal-400 uppercase tracking-wider">Foundation Joining</p>
                            <h3 class="text-2xl font-black text-gray-900 dark:text-white mt-1"><?php echo number_format($foundationCount); ?></h3>
                            <span class="text-[11px] text-gray-500 font-semibold mt-1 inline-block">
                                General Volunteer / Member
                            </span>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-teal-50 dark:bg-teal-900/30 text-teal-600 dark:text-teal-400 flex items-center justify-center text-xl">
                            <i class="fa-solid fa-hands-holding-child"></i>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Project Volunteers -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-200 dark:border-gray-700 shadow-xs">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider">Project Volunteers</p>
                            <h3 class="text-2xl font-black text-gray-900 dark:text-white mt-1"><?php echo number_format($projectCount); ?></h3>
                            <span class="text-[11px] text-emerald-600 font-semibold mt-1 inline-block">
                                Linked to Active Campaigns
                            </span>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-xl">
                            <i class="fa-solid fa-seedling"></i>
                        </div>
                    </div>
                </div>

                <!-- Card 4: Job Vacancies & Fee Collected -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-200 dark:border-gray-700 shadow-xs">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-amber-700 dark:text-amber-400 uppercase tracking-wider">Job Vacancy Apps</p>
                            <h3 class="text-2xl font-black text-gray-900 dark:text-white mt-1"><?php echo number_format($jobCount); ?></h3>
                            <span class="text-[11px] text-emerald-600 font-bold mt-1 inline-block">
                                ₹<?php echo number_format($paidAmount, 2); ?> Collected (<?php echo $paidCount; ?> Paid)
                            </span>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center text-xl">
                            <i class="fa-solid fa-briefcase"></i>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Filters & Controls Bar -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-200 dark:border-gray-700 shadow-xs mb-6 space-y-4">
                
                <!-- Stream Tabs -->
                <div class="flex flex-wrap items-center gap-2 border-b border-gray-100 dark:border-gray-700 pb-3">
                    <button type="button" 
                            @click="filterType = ''" 
                            :class="filterType === '' ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900 shadow-sm' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200'"
                            class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5">
                        <i class="fa-solid fa-list"></i>
                        <span>All Types (<?php echo $totalCount; ?>)</span>
                    </button>

                    <button type="button" 
                            @click="filterType = 'join_foundation'" 
                            :class="filterType === 'join_foundation' ? 'bg-[#0F8B8D] text-white shadow-sm' : 'bg-teal-50 dark:bg-teal-900/30 text-teal-800 dark:text-teal-300 hover:bg-teal-100'"
                            class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5">
                        <i class="fa-solid fa-hands-holding-child"></i>
                        <span>Join Foundation (<?php echo $foundationCount; ?>)</span>
                    </button>

                    <button type="button" 
                            @click="filterType = 'join_project'" 
                            :class="filterType === 'join_project' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300 hover:bg-emerald-100'"
                            class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5">
                        <i class="fa-solid fa-seedling"></i>
                        <span>Join a Project (<?php echo $projectCount; ?>)</span>
                    </button>

                    <button type="button" 
                            @click="filterType = 'job_application'" 
                            :class="filterType === 'job_application' ? 'bg-amber-600 text-white shadow-sm' : 'bg-amber-50 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300 hover:bg-amber-100'"
                            class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5">
                        <i class="fa-solid fa-briefcase"></i>
                        <span>Job Vacancy (<?php echo $jobCount; ?>)</span>
                    </button>
                </div>

                <!-- Secondary Filter Selectors & Search -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    
                    <!-- Search Input -->
                    <div class="relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-gray-400 text-xs"></i>
                        <input type="text" 
                               x-model="searchQuery" 
                               placeholder="Search applicant, phone, email, app no..."
                               class="w-full pl-9 pr-3.5 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                    </div>

                    <!-- Application Status Filter -->
                    <div>
                        <select x-model="filterStatus" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                            <option value="">Status: All Statuses</option>
                            <option value="pending">🟡 Pending Review</option>
                            <option value="reviewed">🔵 Reviewed</option>
                            <option value="approved">🟢 Approved</option>
                            <option value="rejected">🔴 Rejected</option>
                            <option value="onboarded">🟣 Onboarded</option>
                        </select>
                    </div>

                    <!-- Payment Status Filter -->
                    <div>
                        <select x-model="filterPayment" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                            <option value="">Payment: All Payment Status</option>
                            <option value="paid">✓ Paid (Razorpay)</option>
                            <option value="pending">⏳ Payment Pending</option>
                            <option value="exempted">🆓 Exempted / Free</option>
                            <option value="failed">❌ Failed</option>
                            <option value="refunded">↩️ Refunded</option>
                        </select>
                    </div>

                    <!-- Reset Filters Button -->
                    <div class="flex items-center justify-end">
                        <button type="button" 
                                @click="resetFilters()" 
                                class="w-full py-2 px-3 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-xl text-xs font-semibold transition flex items-center justify-center gap-1.5">
                            <i class="fa-solid fa-arrows-rotate"></i>
                            <span>Reset Filters</span>
                        </button>
                    </div>

                </div>

            </div>

            <!-- Applications Table Roster -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-gray-50 dark:bg-gray-700/60 text-gray-500 dark:text-gray-400 font-bold uppercase tracking-wider border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="py-3.5 px-4">App Ref & Date</th>
                                <th class="py-3.5 px-4">Applicant Particulars</th>
                                <th class="py-3.5 px-4">Stream & Target Entity</th>
                                <th class="py-3.5 px-4">Fee & Payment</th>
                                <th class="py-3.5 px-4">Status</th>
                                <th class="py-3.5 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60 font-medium text-gray-700 dark:text-gray-200">
                            
                            <template x-for="app in paginatedApplications" :key="app.id">
                                <tr class="hover:bg-teal-50/20 dark:hover:bg-gray-700/40 transition-colors">
                                    
                                    <!-- Column 1: Application No & Date -->
                                    <td class="py-3.5 px-4">
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-bold text-[#0F8B8D] dark:text-teal-400" x-text="app.application_no"></span>
                                        </div>
                                        <div class="text-[11px] text-gray-400 mt-0.5" x-text="formatDate(app.applied_date)"></div>
                                    </td>

                                    <!-- Column 2: Applicant Particulars -->
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-gray-900 dark:text-white" x-text="app.applicant_name"></div>
                                        <div class="text-[11px] text-gray-500 dark:text-gray-400 flex items-center gap-2 mt-0.5">
                                            <span><i class="fa-solid fa-phone text-[10px] text-gray-400"></i> <span x-text="app.contact"></span></span>
                                            <template x-if="app.email">
                                                <span><i class="fa-solid fa-envelope text-[10px] text-gray-400"></i> <span x-text="app.email"></span></span>
                                            </template>
                                        </div>
                                        <template x-if="app.district || app.state">
                                            <div class="text-[10px] text-gray-400 mt-0.5">
                                                <i class="fa-solid fa-location-dot"></i> <span x-text="[app.district, app.state].filter(Boolean).join(', ')"></span>
                                            </div>
                                        </template>
                                    </td>

                                    <!-- Column 3: Stream & Target Entity -->
                                    <td class="py-3.5 px-4">
                                        <!-- Stream Badge -->
                                        <div class="mb-1">
                                            <template x-if="app.application_type === 'join_foundation'">
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-teal-100 text-teal-800 dark:bg-teal-900/40 dark:text-teal-300">
                                                    <i class="fa-solid fa-hands-holding-child"></i> Foundation Member
                                                </span>
                                            </template>
                                            <template x-if="app.application_type === 'join_project'">
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                                    <i class="fa-solid fa-seedling"></i> Project Volunteer
                                                </span>
                                            </template>
                                            <template x-if="app.application_type === 'job_application'">
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                                    <i class="fa-solid fa-briefcase"></i> Job Vacancy
                                                </span>
                                            </template>
                                        </div>

                                        <!-- Target Entity Title -->
                                        <template x-if="app.application_type === 'join_project' && app.project_title">
                                            <span class="text-[11px] font-bold text-gray-800 dark:text-gray-200 block truncate max-w-[200px]" x-text="app.project_title"></span>
                                        </template>
                                        <template x-if="app.application_type === 'job_application' && app.job_title">
                                            <span class="text-[11px] font-bold text-gray-800 dark:text-gray-200 block truncate max-w-[200px]" x-text="app.job_title + ' (' + (app.job_code || '') + ')'"></span>
                                        </template>
                                        <template x-if="app.application_type === 'join_foundation'">
                                            <span class="text-[11px] text-gray-500">General Induction Wing</span>
                                        </template>
                                    </td>

                                    <!-- Column 4: Fee & Payment Status -->
                                    <td class="py-3.5 px-4">
                                        <div class="flex items-center gap-1.5">
                                            <template x-if="app.payment_status === 'paid'">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                                    <i class="fa-solid fa-circle-check"></i> Paid ₹<span x-text="Number(app.fee_amount || 0).toFixed(2)"></span>
                                                </span>
                                            </template>
                                            <template x-if="app.payment_status === 'pending'">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                                    <i class="fa-solid fa-clock"></i> Pending (₹<span x-text="Number(app.fee_amount || 0).toFixed(2)"></span>)
                                                </span>
                                            </template>
                                            <template x-if="app.payment_status === 'exempted'">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-semibold bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                                    <i class="fa-solid fa-tag"></i> Free / Exempted
                                                </span>
                                            </template>
                                        </div>
                                        <template x-if="app.transaction_id">
                                            <div class="text-[10px] text-gray-400 font-mono mt-0.5" x-text="'Txn: ' + app.transaction_id"></div>
                                        </template>
                                    </td>

                                    <!-- Column 5: Status Badge -->
                                    <td class="py-3.5 px-4">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold capitalize"
                                              :class="{
                                                  'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300': app.status === 'pending',
                                                  'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300': app.status === 'reviewed',
                                                  'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300': app.status === 'approved',
                                                  'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300': app.status === 'rejected',
                                                  'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300': app.status === 'onboarded'
                                              }">
                                            <i class="fa-solid" :class="{
                                                'fa-clock': app.status === 'pending',
                                                'fa-eye': app.status === 'reviewed',
                                                'fa-check': app.status === 'approved',
                                                'fa-xmark': app.status === 'rejected',
                                                'fa-user-check': app.status === 'onboarded'
                                            }"></i>
                                            <span x-text="app.status"></span>
                                        </span>
                                    </td>

                                    <!-- Column 6: Actions -->
                                    <td class="py-3.5 px-4 text-right">
                                        <div class="inline-flex items-center gap-1.5">
                                            <!-- View Details Modal Trigger -->
                                            <button type="button" 
                                                    @click="openViewModal(app)" 
                                                    title="View Full Application Details"
                                                    class="p-1.5 rounded-lg bg-gray-100 hover:bg-teal-50 text-gray-700 hover:text-[#0F8B8D] dark:bg-gray-700 dark:text-gray-300 transition">
                                                <i class="fa-solid fa-eye"></i>
                                            </button>

                                            <!-- Status Update Modal Trigger -->
                                            <button type="button" 
                                                    @click="openEditModal(app)" 
                                                    title="Update Status & Review Remarks"
                                                    class="p-1.5 rounded-lg bg-teal-50 hover:bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300 transition">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>

                                            <!-- Delete Form -->
                                            <form action="actions/join_application_logic.php" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this application record?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                <input type="hidden" name="id" :value="app.id">
                                                <button type="submit" 
                                                        title="Delete Application"
                                                        class="p-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 dark:bg-rose-900/30 dark:text-rose-400 transition">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>

                                </tr>
                            </template>

                            <template x-if="filteredApplications.length === 0">
                                <tr>
                                    <td colspan="6" class="p-8 text-center text-gray-500 dark:text-gray-400">
                                        <i class="fa-solid fa-inbox text-3xl mb-2 text-gray-300 block"></i>
                                        No join applications found matching the selected filters.
                                    </td>
                                </tr>
                            </template>

                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div class="px-6 py-4 bg-gray-50/50 dark:bg-gray-700/20 border-t border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row items-center justify-between gap-4" x-show="filteredApplications.length > 0">
                    <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                        <span>Showing <strong class="text-gray-800 dark:text-gray-200" x-text="(page - 1) * perPage + 1"></strong> to <strong class="text-gray-800 dark:text-gray-200" x-text="Math.min(page * perPage, filteredApplications.length)"></strong> of <strong class="text-gray-800 dark:text-gray-200" x-text="filteredApplications.length"></strong> results</span>
                        <div class="flex items-center gap-1.5 ml-2">
                            <label class="text-[11px] text-gray-400">Per page:</label>
                            <select x-model.number="perPage" @change="page = 1" class="text-xs py-1 px-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-700 dark:text-gray-300">
                                <option :value="10">10</option>
                                <option :value="25">25</option>
                                <option :value="50">50</option>
                                <option :value="100">100</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex items-center gap-1" x-show="totalPages > 1">
                        <button @click="setPage(1)" :disabled="page === 1" class="px-2.5 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed">
                            <i class="fa-solid fa-angles-left text-[10px]"></i>
                        </button>
                        <button @click="setPage(page - 1)" :disabled="page === 1" class="px-2.5 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed">
                            <i class="fa-solid fa-chevron-left text-[10px]"></i>
                        </button>
                        <template x-for="p in totalPages" :key="p">
                            <button x-show="p === 1 || p === totalPages || (p >= page - 2 && p <= page + 2)"
                                    @click="setPage(p)" 
                                    :class="page === p ? 'bg-[#0F8B8D] text-white font-bold border-[#0F8B8D] shadow-xs' : 'text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700'" 
                                    class="w-8 h-8 rounded-lg border text-xs flex items-center justify-center transition" 
                                    x-text="p">
                            </button>
                        </template>
                        <button @click="setPage(page + 1)" :disabled="page === totalPages" class="px-2.5 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed">
                            <i class="fa-solid fa-chevron-right text-[10px]"></i>
                        </button>
                        <button @click="setPage(totalPages)" :disabled="page === totalPages" class="px-2.5 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed">
                            <i class="fa-solid fa-angles-right text-[10px]"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ==================== MODAL 1: VIEW FULL APPLICATION ==================== -->
            <div x-show="viewModalOpen" 
                 x-transition.opacity 
                 class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
                
                <div class="bg-white dark:bg-gray-800 rounded-3xl max-w-2xl w-full overflow-hidden shadow-2xl border border-gray-100 dark:border-gray-700 max-h-[90vh] flex flex-col"
                     @click.away="viewModalOpen = false">
                    
                    <!-- Modal Header -->
                    <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-gradient-to-r from-teal-50 to-white dark:from-gray-700 dark:to-gray-800">
                        <div class="flex items-center gap-2">
                            <span class="w-9 h-9 rounded-xl bg-[#0F8B8D] text-white flex items-center justify-center text-sm font-bold">
                                <i class="fa-solid fa-id-card"></i>
                            </span>
                            <div>
                                <h3 class="text-base font-black text-gray-900 dark:text-white" x-text="activeApp ? activeApp.applicant_name : ''"></h3>
                                <p class="text-[11px] text-[#0F8B8D] dark:text-teal-400 font-mono" x-text="activeApp ? activeApp.application_no : ''"></p>
                            </div>
                        </div>
                        <button type="button" @click="viewModalOpen = false" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>

                    <!-- Modal Body (Scrollable) -->
                    <div class="p-6 overflow-y-auto space-y-5 text-xs text-gray-700 dark:text-gray-300">
                        
                        <!-- Grid 1: Basic Info -->
                        <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-2xl grid grid-cols-2 gap-3">
                            <div>
                                <span class="text-gray-400 block text-[10px] uppercase font-bold">Mobile Contact</span>
                                <span class="font-bold text-gray-900 dark:text-white" x-text="activeApp ? activeApp.contact : ''"></span>
                            </div>
                            <div>
                                <span class="text-gray-400 block text-[10px] uppercase font-bold">Email Address</span>
                                <span class="font-bold text-gray-900 dark:text-white" x-text="activeApp ? (activeApp.email || 'N/A') : ''"></span>
                            </div>
                            <div>
                                <span class="text-gray-400 block text-[10px] uppercase font-bold">State & District</span>
                                <span class="font-bold text-gray-900 dark:text-white" x-text="activeApp ? [activeApp.district, activeApp.state].filter(Boolean).join(', ') || 'N/A' : ''"></span>
                            </div>
                            <div>
                                <span class="text-gray-400 block text-[10px] uppercase font-bold">Applied Date</span>
                                <span class="font-bold text-gray-900 dark:text-white" x-text="activeApp ? formatDate(activeApp.applied_date) : ''"></span>
                            </div>
                        </div>

                        <!-- Grid 2: Stream & Target Entity -->
                        <div class="p-4 bg-teal-50/60 dark:bg-teal-900/20 rounded-2xl border border-teal-100 dark:border-teal-800/40">
                            <span class="text-teal-800 dark:text-teal-300 block text-[10px] uppercase font-bold mb-1">Application Stream</span>
                            <div class="font-black text-sm text-gray-900 dark:text-white capitalize" x-text="activeApp ? activeApp.application_type.replace('_', ' ') : ''"></div>
                            
                            <template x-if="activeApp && activeApp.project_title">
                                <div class="mt-2 pt-2 border-t border-teal-100 dark:border-teal-800/40">
                                    <span class="text-gray-500 block text-[10px]">Associated Project</span>
                                    <span class="font-bold text-[#0F8B8D]" x-text="activeApp.project_title"></span>
                                </div>
                            </template>

                            <template x-if="activeApp && activeApp.job_title">
                                <div class="mt-2 pt-2 border-t border-teal-100 dark:border-teal-800/40">
                                    <span class="text-gray-500 block text-[10px]">Job Opening Applied</span>
                                    <span class="font-bold text-amber-700 dark:text-amber-400" x-text="activeApp.job_title + ' (' + (activeApp.job_code || '') + ')'"></span>
                                </div>
                            </template>
                        </div>

                        <!-- Details & Statement of Purpose -->
                        <div>
                            <span class="text-gray-400 block text-[10px] uppercase font-bold mb-1">Details / Statement of Purpose / Resume</span>
                            <div class="p-3.5 bg-gray-50 dark:bg-gray-700/50 rounded-2xl whitespace-pre-line leading-relaxed text-gray-800 dark:text-gray-200" x-text="activeApp ? (activeApp.details || 'No additional details provided.') : ''"></div>
                        </div>

                        <!-- Payment & Financial Breakdown -->
                        <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-2xl grid grid-cols-2 sm:grid-cols-3 gap-3">
                            <div>
                                <span class="text-gray-400 block text-[10px] uppercase font-bold">Fee Amount</span>
                                <span class="font-black text-gray-900 dark:text-white" x-text="activeApp ? (activeApp.fee_amount ? '₹' + Number(activeApp.fee_amount).toFixed(2) : 'Free / Exempted') : ''"></span>
                            </div>
                            <div>
                                <span class="text-gray-400 block text-[10px] uppercase font-bold">Payment Status</span>
                                <span class="font-bold uppercase" x-text="activeApp ? activeApp.payment_status : ''"></span>
                            </div>
                            <div>
                                <span class="text-gray-400 block text-[10px] uppercase font-bold">Transaction ID</span>
                                <span class="font-mono text-[11px]" x-text="activeApp ? (activeApp.transaction_id || 'N/A') : ''"></span>
                            </div>
                        </div>

                        <!-- Review Notes -->
                        <template x-if="activeApp && activeApp.admin_notes">
                            <div class="p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-2xl">
                                <span class="text-amber-800 dark:text-amber-300 block text-[10px] uppercase font-bold mb-0.5">Admin Review Remarks</span>
                                <p class="text-xs text-amber-900 dark:text-amber-200" x-text="activeApp.admin_notes"></p>
                                <div class="text-[10px] text-amber-700 dark:text-amber-400 mt-1" x-text="'Reviewed by ' + (activeApp.reviewer_name || 'Admin') + ' on ' + (activeApp.reviewed_at || '')"></div>
                            </div>
                        </template>

                    </div>

                    <!-- Modal Footer -->
                    <div class="p-4 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between bg-gray-50 dark:bg-gray-700/50">
                        <button type="button" @click="viewModalOpen = false" class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-xl font-bold text-xs">
                            Close
                        </button>
                        <button type="button" @click="viewModalOpen = false; openEditModal(activeApp)" class="px-4 py-2 bg-[#0F8B8D] text-white rounded-xl font-bold text-xs shadow-sm">
                            <i class="fa-solid fa-pen-to-square"></i> Update Status
                        </button>
                    </div>

                </div>
            </div>

            <!-- ==================== MODAL 2: UPDATE STATUS & REVIEW NOTES ==================== -->
            <div x-show="editModalOpen" 
                 x-transition.opacity 
                 class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
                
                <div class="bg-white dark:bg-gray-800 rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl border border-gray-100 dark:border-gray-700"
                     @click.away="editModalOpen = false">
                    
                    <form action="actions/join_application_logic.php" method="POST" @submit="handleEditSubmit($event)">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="id" :value="editForm.id">

                        <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-gradient-to-r from-teal-50 to-white dark:from-gray-700 dark:to-gray-800">
                            <div>
                                <h3 class="text-base font-black text-gray-900 dark:text-white">Review & Update Status</h3>
                                <p class="text-[11px] text-gray-500" x-text="'Ref: ' + (activeApp ? activeApp.application_no : '')"></p>
                            </div>
                            <button type="button" @click="editModalOpen = false" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                <i class="fa-solid fa-xmark text-lg"></i>
                            </button>
                        </div>

                        <div class="p-6 space-y-4 text-xs">
                            
                            <div>
                                <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Application Status</label>
                                <select name="status" x-model="editForm.status" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-white text-xs font-semibold focus:ring-2 focus:ring-[#0F8B8D]">
                                    <option value="pending">🟡 Pending Review</option>
                                    <option value="reviewed">🔵 Reviewed & Verified</option>
                                    <option value="approved">🟢 Approved</option>
                                    <option value="onboarded">🟣 Onboarded / Inducted</option>
                                    <option value="rejected">🔴 Rejected</option>
                                </select>
                            </div>

                            <div>
                                <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Payment Status</label>
                                <select name="payment_status" x-model="editForm.payment_status" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-white text-xs font-semibold focus:ring-2 focus:ring-[#0F8B8D]">
                                    <option value="exempted">🆓 Exempted / Free</option>
                                    <option value="paid">✓ Paid (Verified)</option>
                                    <option value="pending">⏳ Pending Payment</option>
                                    <option value="failed">❌ Failed</option>
                                    <option value="refunded">↩️ Refunded</option>
                                </select>
                            </div>

                            <div>
                                <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Admin Review Notes / Remarks</label>
                                <textarea name="admin_notes" 
                                          x-model="editForm.admin_notes" 
                                          rows="3" 
                                          placeholder="Enter internal review notes or feedback..."
                                          class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-white text-xs focus:ring-2 focus:ring-[#0F8B8D]"></textarea>
                            </div>

                        </div>

                        <div class="p-4 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between bg-gray-50 dark:bg-gray-700/50">
                            <button type="button" @click="editModalOpen = false" class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-xl font-bold text-xs">
                                Cancel
                            </button>
                            <button type="submit" class="px-5 py-2 bg-[#0F8B8D] hover:bg-teal-700 text-white rounded-xl font-bold text-xs shadow-md">
                                Save Changes
                            </button>
                        </div>

                    </form>

                </div>
            </div>

        </main>
    </div>
</div>

<!-- ==================== ALPINE.JS CONTROLLER ==================== -->
<script>
function joinApplicationsAdmin(initialData) {
    return {
        applications: initialData || [],
        filterType: '',
        filterStatus: '',
        filterPayment: '',
        searchQuery: '',

        page: 1,
        perPage: 10,

        viewModalOpen: false,
        editModalOpen: false,
        activeApp: null,

        get totalPages() {
            return Math.ceil(this.filteredApplications.length / this.perPage) || 1;
        },

        get paginatedApplications() {
            const start = (this.page - 1) * this.perPage;
            return this.filteredApplications.slice(start, start + this.perPage);
        },

        setPage(p) {
            if (p >= 1 && p <= this.totalPages) this.page = p;
        },

        editForm: {
            id: 0,
            status: 'pending',
            payment_status: 'exempted',
            admin_notes: ''
        },

        get filteredApplications() {
            return this.applications.filter(app => {
                // Type Filter
                if (this.filterType && app.application_type !== this.filterType) {
                    return false;
                }
                // Status Filter
                if (this.filterStatus && app.status !== this.filterStatus) {
                    return false;
                }
                // Payment Filter
                if (this.filterPayment && app.payment_status !== this.filterPayment) {
                    return false;
                }
                // Search Query
                if (this.searchQuery.trim()) {
                    const q = this.searchQuery.toLowerCase();
                    const matchName = (app.applicant_name || '').toLowerCase().includes(q);
                    const matchPhone = (app.contact || '').toLowerCase().includes(q);
                    const matchEmail = (app.email || '').toLowerCase().includes(q);
                    const matchAppNo = (app.application_no || '').toLowerCase().includes(q);
                    const matchDetails = (app.details || '').toLowerCase().includes(q);
                    if (!matchName && !matchPhone && !matchEmail && !matchAppNo && !matchDetails) {
                        return false;
                    }
                }
                return true;
            });
        },

        resetFilters() {
            this.filterType = '';
            this.filterStatus = '';
            this.filterPayment = '';
            this.searchQuery = '';
        },

        openViewModal(app) {
            this.activeApp = app;
            this.viewModalOpen = true;
        },

        openEditModal(app) {
            this.activeApp = app;
            this.editForm = {
                id: app.id,
                status: app.status || 'pending',
                payment_status: app.payment_status || 'exempted',
                admin_notes: app.admin_notes || ''
            };
            this.editModalOpen = true;
        },

        handleEditSubmit(event) {
            // Standard form POST is allowed or can also do AJAX
        },

        exportToExcel() {
            const params = new URLSearchParams({
                action: 'export_csv',
                type: this.filterType,
                status: this.filterStatus,
                payment_status: this.filterPayment,
                search: this.searchQuery
            });
            window.location.href = 'actions/join_application_logic.php?' + params.toString();
        },

        formatDate(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
        }
    };
}
</script>

<?php require 'includes/footer.php'; ?>
