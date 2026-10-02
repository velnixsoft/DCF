<?php
// ============================================================
// admin/feedbacks.php
// Employee, Member, Volunteer & Stakeholder Feedback Management
// Built on Complaint Management Pattern (Module 21)
// ============================================================

require 'includes/header.php';
require '../config/db.php';
require_once '../includes/functions.php';

if (!canAccessModule($pdo, 'coordinator', 'page.complaints')) {
    setFlash('error', 'Access denied. You do not have permission to manage feedback.');
    header('Location: dashboard.php');
    exit;
}

$csrfToken = generateCsrfToken();

// Fetch all feedbacks with replied_by user information
$sql = "
    SELECT 
        f.*, 
        u.name AS replied_by_name, 
        u.role AS replied_by_role
    FROM `feedbacks` f
    LEFT JOIN `users` u ON f.reply_by = u.id
    ORDER BY f.created_at DESC, f.id DESC
";
$feedbacks = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Calculate metrics
$totalCount = count($feedbacks);
$pendingCount = 0;
$underReviewCount = 0;
$actionTakenCount = 0;
$closedCount = 0;
$totalRatingSum = 0;
$ratingCount = 0;

$employeeCount = 0;
$memberCount = 0;
$volunteerCount = 0;
$fieldAgentCount = 0;

foreach ($feedbacks as $fb) {
    if ($fb['status'] === 'pending') $pendingCount++;
    elseif ($fb['status'] === 'under_review') $underReviewCount++;
    elseif ($fb['status'] === 'action_taken') $actionTakenCount++;
    elseif ($fb['status'] === 'closed') $closedCount++;

    if (!empty($fb['rating'])) {
        $totalRatingSum += (int)$fb['rating'];
        $ratingCount++;
    }

    if ($fb['submitter_type'] === 'employee') $employeeCount++;
    elseif ($fb['submitter_type'] === 'member') $memberCount++;
    elseif ($fb['submitter_type'] === 'volunteer') $volunteerCount++;
    elseif ($fb['submitter_type'] === 'field_agent') $fieldAgentCount++;
}

$avgRating = $ratingCount > 0 ? round($totalRatingSum / $ratingCount, 1) : 5.0;
?>

<div class="flex h-screen overflow-hidden bg-slate-50 dark:bg-gray-900" 
     x-data="feedbacksAdminApp()" 
     x-init="init()">

    <?php require 'includes/sidebar.php'; ?>

    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300 min-w-0">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-8">

            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div>
                    <div class="flex items-center gap-3">
                        <span class="w-10 h-10 rounded-2xl bg-sky-50 dark:bg-sky-900/40 text-[#1070B0] flex items-center justify-center text-lg shadow-sm">
                            <i class="fa-solid fa-comment-dots"></i>
                        </span>
                        <div>
                            <h3 class="text-2xl md:text-3xl font-black text-gray-800 dark:text-white">
                                Employee & Member Feedback
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                Review continuous feedback from staff, members, volunteers, and field officers with instant status updates & responses.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <a href="../track-feedback.php" target="_blank" class="bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 hover:bg-slate-50 dark:hover:bg-gray-700 font-bold px-3.5 py-2.5 rounded-2xl shadow-sm text-xs flex items-center gap-1.5 transition">
                        <i class="fa-solid fa-arrow-up-right-from-square text-[#1070B0]"></i> Public Tracker
                    </a>
                    <a href="../feedback.php" target="_blank" class="bg-[#1070B0] hover:bg-[#0c598d] text-white font-bold py-2.5 px-4 rounded-2xl shadow-md text-xs flex items-center gap-1.5 transition transform active:scale-95">
                        <i class="fa-solid fa-plus"></i> Submit Feedback
                    </a>
                </div>
            </div>

            <!-- Flash Message Banner -->
            <?php if ($flash = getFlash('success')): ?>
                <div class="bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 px-4 py-3.5 rounded-2xl mb-6 text-xs sm:text-sm flex items-center gap-2.5 shadow-sm">
                    <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
                    <span><?php echo htmlspecialchars($flash); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($flash = getFlash('error')): ?>
                <div class="bg-rose-50 dark:bg-rose-900/40 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 px-4 py-3.5 rounded-2xl mb-6 text-xs sm:text-sm flex items-center gap-2.5 shadow-sm">
                    <i class="fa-solid fa-circle-exclamation text-rose-600 text-base"></i>
                    <span><?php echo htmlspecialchars($flash); ?></span>
                </div>
            <?php endif; ?>

            <!-- 4 KPI Metric Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <!-- Total Feedback -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase text-gray-400">Total Feedbacks</span>
                        <span class="w-9 h-9 rounded-xl bg-sky-50 dark:bg-sky-900/30 text-[#1070B0] flex items-center justify-center text-sm">
                            <i class="fa-solid fa-comments"></i>
                        </span>
                    </div>
                    <div class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white mt-2"><?php echo $totalCount; ?></div>
                    <div class="text-[11px] text-gray-500 mt-1">Across all wings & teams</div>
                </div>

                <!-- Pending / Review -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase text-amber-500">Under Review</span>
                        <span class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 flex items-center justify-center text-sm">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                        </span>
                    </div>
                    <div class="text-2xl sm:text-3xl font-black text-amber-600 dark:text-amber-400 mt-2"><?php echo ($pendingCount + $underReviewCount); ?></div>
                    <div class="text-[11px] text-gray-500 mt-1"><?php echo $pendingCount; ?> new, <?php echo $underReviewCount; ?> under review</div>
                </div>

                <!-- Action Taken -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase text-emerald-600">Action Taken</span>
                        <span class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 flex items-center justify-center text-sm">
                            <i class="fa-solid fa-circle-check"></i>
                        </span>
                    </div>
                    <div class="text-2xl sm:text-3xl font-black text-emerald-600 dark:text-emerald-400 mt-2"><?php echo ($actionTakenCount + $closedCount); ?></div>
                    <div class="text-[11px] text-gray-500 mt-1"><?php echo $actionTakenCount; ?> actions, <?php echo $closedCount; ?> resolved</div>
                </div>

                <!-- Avg Rating -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase text-amber-500">Avg Satisfaction</span>
                        <span class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-900/30 text-amber-500 flex items-center justify-center text-sm">
                            <i class="fa-solid fa-star"></i>
                        </span>
                    </div>
                    <div class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white mt-2 flex items-center gap-1.5">
                        <span><?php echo number_format($avgRating, 1); ?></span>
                        <span class="text-xs text-amber-500 font-bold">/ 5.0</span>
                    </div>
                    <div class="text-[11px] text-gray-500 mt-1">Based on <?php echo $ratingCount; ?> ratings</div>
                </div>
            </div>

            <!-- Wing Breakdown Quick Bar -->
            <div class="flex flex-wrap items-center gap-2 mb-6">
                <span class="text-xs font-bold text-gray-500 mr-1">Wing Breakdown:</span>
                <span class="px-3 py-1 rounded-full bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-xs font-bold border border-blue-200 dark:border-blue-800">
                    <i class="fa-solid fa-briefcase text-[10px]"></i> Staff: <?php echo $employeeCount; ?>
                </span>
                <span class="px-3 py-1 rounded-full bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 text-xs font-bold border border-purple-200 dark:border-purple-800">
                    <i class="fa-solid fa-id-badge text-[10px]"></i> Members: <?php echo $memberCount; ?>
                </span>
                <span class="px-3 py-1 rounded-full bg-pink-50 dark:bg-pink-900/30 text-pink-700 dark:text-pink-300 text-xs font-bold border border-pink-200 dark:border-pink-800">
                    <i class="fa-solid fa-hand-holding-heart text-[10px]"></i> Volunteers: <?php echo $volunteerCount; ?>
                </span>
                <span class="px-3 py-1 rounded-full bg-teal-50 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300 text-xs font-bold border border-teal-200 dark:border-teal-800">
                    <i class="fa-solid fa-route text-[10px]"></i> Field Coordinators: <?php echo $fieldAgentCount; ?>
                </span>
            </div>

            <!-- Main Content Container -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl border border-slate-200 dark:border-gray-700 shadow-sm overflow-hidden">
                
                <!-- Filter & Search Bar -->
                <div class="p-5 border-b border-slate-100 dark:border-gray-700 bg-slate-50/50 dark:bg-gray-900/30 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                        
                        <!-- Search Box -->
                        <div class="relative lg:col-span-2">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <i class="fa-solid fa-magnifying-glass text-xs"></i>
                            </span>
                            <input type="text" 
                                   x-model="searchQuery" 
                                   placeholder="Search by code, submitter, subject, department..." 
                                   class="w-full pl-9 pr-4 py-2.5 bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-2xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1070B0]">
                        </div>

                        <!-- Submitter Role Filter -->
                        <div>
                            <select x-model="typeFilter" class="w-full px-3 py-2.5 bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-2xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1070B0]">
                                <option value="">All Submitter Wings</option>
                                <option value="employee">Staff / Employees</option>
                                <option value="member">Registered Members</option>
                                <option value="volunteer">Volunteers</option>
                                <option value="field_agent">Field Coordinators</option>
                                <option value="donor">Donors / Partners</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <select x-model="statusFilter" class="w-full px-3 py-2.5 bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-2xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1070B0]">
                                <option value="">All Statuses</option>
                                <option value="pending">Pending</option>
                                <option value="under_review">Under Review</option>
                                <option value="action_taken">Action Taken</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>

                        <!-- Rating Filter -->
                        <div>
                            <select x-model="ratingFilter" class="w-full px-3 py-2.5 bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-2xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1070B0]">
                                <option value="">All Ratings</option>
                                <option value="5">⭐⭐⭐⭐⭐ (5 Stars)</option>
                                <option value="4">⭐⭐⭐⭐ (4 Stars)</option>
                                <option value="3">⭐⭐⭐ (3 Stars)</option>
                                <option value="2">⭐⭐ (2 Stars)</option>
                                <option value="1">⭐ (1 Star)</option>
                            </select>
                        </div>

                    </div>
                </div>

                <!-- Feedbacks Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-gray-700 bg-slate-50/75 dark:bg-gray-800 text-[11px] font-bold text-gray-400 dark:text-gray-400 uppercase tracking-wider">
                                <th class="py-3.5 px-4 sm:px-6">Feedback Ref</th>
                                <th class="py-3.5 px-4">Submitter & Wing</th>
                                <th class="py-3.5 px-4">Rating</th>
                                <th class="py-3.5 px-4">Topic / Department</th>
                                <th class="py-3.5 px-4">Status</th>
                                <th class="py-3.5 px-4">Submitted</th>
                                <th class="py-3.5 px-4 sm:px-6 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-gray-700 text-xs text-gray-700 dark:text-gray-300">
                            <template x-for="item in paginatedFeedbacks" :key="item.id">
                                <tr class="hover:bg-slate-50/80 dark:hover:bg-gray-700/40 transition">
                                    
                                    <!-- Ref Number -->
                                    <td class="py-4 px-4 sm:px-6 whitespace-nowrap">
                                        <span class="font-mono font-bold text-[#1070B0] dark:text-sky-400" x-text="item.feedback_no"></span>
                                        <template x-if="item.priority === 'high'">
                                            <span class="ml-1.5 px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300 uppercase">High</span>
                                        </template>
                                    </td>

                                    <!-- Submitter -->
                                    <td class="py-4 px-4">
                                        <div class="font-bold text-gray-900 dark:text-white" x-text="item.name"></div>
                                        <div class="flex items-center gap-1.5 mt-0.5">
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                                  :class="getSubmitterBadgeClass(item.submitter_type)"
                                                  x-text="item.submitter_type"></span>
                                            <template x-if="item.is_anonymous == 1">
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                                                    <i class="fa-solid fa-user-secret"></i> Anon
                                                </span>
                                            </template>
                                        </div>
                                    </td>

                                    <!-- Rating Stars -->
                                    <td class="py-4 px-4 whitespace-nowrap">
                                        <div class="flex items-center text-amber-400">
                                            <template x-for="s in 5" :key="s">
                                                <i class="fa-solid fa-star text-[11px]" :class="s <= item.rating ? 'text-amber-400' : 'text-gray-200 dark:text-gray-700'"></i>
                                            </template>
                                            <span class="ml-1 text-[11px] font-bold text-gray-700 dark:text-gray-300" x-text="'(' + item.rating + ')'"></span>
                                        </div>
                                    </td>

                                    <!-- Subject / Department -->
                                    <td class="py-4 px-4 max-w-xs">
                                        <div class="font-semibold text-gray-900 dark:text-white truncate" x-text="item.subject"></div>
                                        <div class="text-[11px] text-gray-400 truncate">
                                            <span x-text="formatCategory(item.category)"></span>
                                            <span x-show="item.department" x-text="' &bull; ' + item.department"></span>
                                        </div>
                                    </td>

                                    <!-- Status Badge -->
                                    <td class="py-4 px-4 whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider"
                                              :class="getStatusBadgeClass(item.status)">
                                            <i class="text-[9px]" :class="getStatusIcon(item.status)"></i>
                                            <span x-text="getStatusLabel(item.status)"></span>
                                        </span>
                                    </td>

                                    <!-- Date -->
                                    <td class="py-4 px-4 whitespace-nowrap text-gray-500 dark:text-gray-400 text-[11px]" x-text="formatDate(item.created_at)"></td>

                                    <!-- Action Buttons -->
                                    <td class="py-4 px-4 sm:px-6 whitespace-nowrap text-right space-x-2">
                                        <button type="button" 
                                                @click="openReviewModal(item)"
                                                class="px-3 py-1.5 rounded-xl bg-sky-50 dark:bg-sky-900/30 text-[#1070B0] dark:text-sky-300 hover:bg-sky-100 font-bold text-xs transition inline-flex items-center gap-1">
                                            <i class="fa-solid fa-reply"></i> Review & Action
                                        </button>

                                        <button type="button" 
                                                @click="deleteFeedback(item.id, item.feedback_no)"
                                                class="p-1.5 rounded-xl text-gray-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 transition text-xs"
                                                title="Delete Feedback">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>

                            <template x-if="filteredFeedbacks.length === 0">
                                <tr>
                                    <td colspan="7" class="py-12 text-center text-gray-400">
                                        <i class="fa-solid fa-comments text-3xl mb-2 block opacity-40"></i>
                                        <span>No feedbacks found matching your filters.</span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div class="px-6 py-4 bg-gray-50/50 dark:bg-gray-700/20 border-t border-slate-100 dark:border-gray-700 flex flex-col sm:flex-row items-center justify-between gap-4" x-show="filteredFeedbacks.length > 0">
                    <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                        <span>Showing <strong class="text-gray-800 dark:text-gray-200" x-text="(page - 1) * perPage + 1"></strong> to <strong class="text-gray-800 dark:text-gray-200" x-text="Math.min(page * perPage, filteredFeedbacks.length)"></strong> of <strong class="text-gray-800 dark:text-gray-200" x-text="filteredFeedbacks.length"></strong> results</span>
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
                                    :class="page === p ? 'bg-[#1070B0] text-white font-bold border-[#1070B0] shadow-xs' : 'text-gray-700 dark:text-gray-300 border-slate-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700'" 
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

    <!-- ════════════════════════════════════════════════════════════ -->
    <!-- REVIEW & ACTION MODAL                                        -->
    <!-- ════════════════════════════════════════════════════════════ -->
    <div x-show="showModal" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">

        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl border border-slate-200 dark:border-gray-700 w-full max-w-2xl overflow-hidden animate-fade-in"
             @click.away="showModal = false">

            <!-- Modal Header -->
            <div class="p-6 bg-gradient-to-r from-slate-900 to-slate-800 text-white flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-2xl bg-white/10 text-white flex items-center justify-center text-lg">
                        <i class="fa-solid fa-comment-dots"></i>
                    </span>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-mono font-bold text-sky-400" x-text="activeItem ? activeItem.feedback_no : ''"></span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-white/20 text-white" x-text="activeItem ? activeItem.submitter_type : ''"></span>
                        </div>
                        <h3 class="text-base font-bold text-white mt-0.5" x-text="activeItem ? activeItem.subject : ''"></h3>
                    </div>
                </div>

                <button type="button" @click="showModal = false" class="text-gray-400 hover:text-white p-2 rounded-xl">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <form action="actions/feedback_logic.php" method="POST" class="p-6 space-y-5">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="action" value="update_feedback">
                <input type="hidden" name="id" :value="activeItem ? activeItem.id : ''">

                <!-- Submitter Details Box -->
                <div class="p-4 rounded-2xl bg-slate-50 dark:bg-gray-700/30 border border-slate-200 dark:border-gray-700 grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs">
                    <div>
                        <span class="text-gray-400 uppercase font-bold text-[10px] block">Submitter Name</span>
                        <span class="font-bold text-gray-900 dark:text-white mt-0.5 block" x-text="activeItem ? activeItem.name : ''"></span>
                    </div>

                    <div>
                        <span class="text-gray-400 uppercase font-bold text-[10px] block">Contact / Email</span>
                        <span class="font-medium text-gray-700 dark:text-gray-300 mt-0.5 block truncate" x-text="activeItem ? (activeItem.email || activeItem.contact || 'None / Anonymous') : ''"></span>
                    </div>

                    <div>
                        <span class="text-gray-400 uppercase font-bold text-[10px] block">Rating Given</span>
                        <span class="font-bold text-amber-500 mt-0.5 block">
                            <span x-text="activeItem ? (activeItem.rating + ' / 5 Stars') : ''"></span>
                        </span>
                    </div>

                    <div>
                        <span class="text-gray-400 uppercase font-bold text-[10px] block">Department</span>
                        <span class="font-medium text-gray-700 dark:text-gray-300 mt-0.5 block" x-text="activeItem ? (activeItem.department || 'General') : ''"></span>
                    </div>

                    <div>
                        <span class="text-gray-400 uppercase font-bold text-[10px] block">User Identifier</span>
                        <span class="font-mono text-gray-700 dark:text-gray-300 mt-0.5 block" x-text="activeItem ? (activeItem.user_identifier || 'N/A') : ''"></span>
                    </div>

                    <div>
                        <span class="text-gray-400 uppercase font-bold text-[10px] block">Submitted On</span>
                        <span class="font-medium text-gray-700 dark:text-gray-300 mt-0.5 block" x-text="formatDate(activeItem ? activeItem.created_at : '')"></span>
                    </div>
                </div>

                <!-- Full Feedback Message -->
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Feedback Content</label>
                    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-gray-900 border border-slate-200 dark:border-gray-700 text-xs sm:text-sm text-gray-800 dark:text-gray-200 whitespace-pre-line leading-relaxed max-h-40 overflow-y-auto" x-text="activeItem ? activeItem.message : ''"></div>
                </div>

                <!-- Attachment Link (if present) -->
                <template x-if="activeItem && activeItem.attachment_path">
                    <div class="p-3 rounded-2xl bg-sky-50 dark:bg-sky-900/30 border border-sky-200 dark:border-sky-800 flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2 text-sky-800 dark:text-sky-200 font-bold">
                            <i class="fa-solid fa-paperclip"></i>
                            <span>Attached Supporting Document</span>
                        </div>
                        <a :href="'../' + activeItem.attachment_path" target="_blank" class="px-3 py-1 rounded-xl bg-[#1070B0] text-white font-bold text-xs hover:bg-[#0c598d] transition">
                            View / Download
                        </a>
                    </div>
                </template>

                <!-- Update Status & Priority -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">
                            Update Status <span class="text-rose-500">*</span>
                        </label>
                        <select name="status" x-model="modalStatus" class="w-full px-3 py-2.5 rounded-2xl bg-slate-50 dark:bg-gray-700 border border-slate-200 dark:border-gray-600 text-xs sm:text-sm text-gray-900 dark:text-white font-bold focus:outline-none focus:ring-2 focus:ring-[#1070B0]">
                            <option value="pending">Pending Review</option>
                            <option value="under_review">Under Active Review</option>
                            <option value="action_taken">Action Taken & Implemented</option>
                            <option value="closed">Closed / Archived</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">
                            Priority
                        </label>
                        <select name="priority" x-model="modalPriority" class="w-full px-3 py-2.5 rounded-2xl bg-slate-50 dark:bg-gray-700 border border-slate-200 dark:border-gray-600 text-xs sm:text-sm text-gray-900 dark:text-white font-bold focus:outline-none focus:ring-2 focus:ring-[#1070B0]">
                            <option value="low">Low Priority</option>
                            <option value="medium">Medium Priority</option>
                            <option value="high">High Priority</option>
                        </select>
                    </div>
                </div>

                <!-- Admin Action Remarks / Reply -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">
                        Institutional Response / Action Remarks
                    </label>
                    <textarea name="admin_reply" 
                              x-model="modalReply"
                              rows="4" 
                              placeholder="Type administrative review decision, notes for contributor, or actions executed..."
                              class="w-full p-3 rounded-2xl bg-slate-50 dark:bg-gray-700 border border-slate-200 dark:border-gray-600 text-xs sm:text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1070B0]"></textarea>
                </div>

                <!-- Send Email Toggle -->
                <div class="flex items-center justify-between p-3 rounded-2xl bg-slate-50 dark:bg-gray-700/40 border border-slate-200 dark:border-gray-600"
                     x-show="activeItem && activeItem.email && activeItem.is_anonymous != 1">
                    <div class="flex items-center gap-2.5">
                        <i class="fa-solid fa-envelope text-sky-600"></i>
                        <span class="text-xs font-bold text-gray-700 dark:text-gray-300">
                            Dispatch Email Notification to <span class="text-[#1070B0]" x-text="activeItem ? activeItem.email : ''"></span>
                        </span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="send_email" value="1" x-model="modalSendEmail" class="sr-only peer">
                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#1070B0]"></div>
                    </label>
                </div>

                <!-- Action Footer -->
                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-gray-700">
                    <button type="button" @click="showModal = false" class="px-5 py-2.5 rounded-2xl text-xs font-bold text-gray-500 hover:text-gray-800 dark:hover:text-white">
                        Cancel
                    </button>
                    <button type="submit" class="px-6 py-2.5 rounded-2xl bg-[#1070B0] hover:bg-[#0c598d] text-white font-bold text-xs shadow-md transition">
                        Save Status & Response
                    </button>
                </div>
            </form>

        </div>
    </div>

    <!-- Hidden Delete Form -->
    <form id="deleteFeedbackForm" action="actions/feedback_logic.php" method="POST" style="display:none;">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <input type="hidden" name="action" value="delete_feedback">
        <input type="hidden" name="id" id="deleteFeedbackId" value="">
    </form>

</div>

<script>
function feedbacksAdminApp() {
    return {
        allFeedbacks: <?php echo json_encode($feedbacks, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
        searchQuery: '',
        typeFilter: '',
        statusFilter: '',
        ratingFilter: '',

        page: 1,
        perPage: 10,

        showModal: false,
        activeItem: null,
        modalStatus: 'pending',
        modalPriority: 'medium',
        modalReply: '',
        modalSendEmail: true,

        get totalPages() {
            return Math.ceil(this.filteredFeedbacks.length / this.perPage) || 1;
        },

        get paginatedFeedbacks() {
            const start = (this.page - 1) * this.perPage;
            return this.filteredFeedbacks.slice(start, start + this.perPage);
        },

        setPage(p) {
            if (p >= 1 && p <= this.totalPages) this.page = p;
        },

        init() {
            // Initialization
        },

        get filteredFeedbacks() {
            return this.allFeedbacks.filter(item => {
                const matchesSearch = !this.searchQuery || 
                    (item.feedback_no && item.feedback_no.toLowerCase().includes(this.searchQuery.toLowerCase())) ||
                    (item.name && item.name.toLowerCase().includes(this.searchQuery.toLowerCase())) ||
                    (item.subject && item.subject.toLowerCase().includes(this.searchQuery.toLowerCase())) ||
                    (item.message && item.message.toLowerCase().includes(this.searchQuery.toLowerCase())) ||
                    (item.department && item.department.toLowerCase().includes(this.searchQuery.toLowerCase())) ||
                    (item.user_identifier && item.user_identifier.toLowerCase().includes(this.searchQuery.toLowerCase()));

                const matchesType = !this.typeFilter || item.submitter_type === this.typeFilter;
                const matchesStatus = !this.statusFilter || item.status === this.statusFilter;
                const matchesRating = !this.ratingFilter || String(item.rating) === String(this.ratingFilter);

                return matchesSearch && matchesType && matchesStatus && matchesRating;
            });
        },

        openReviewModal(item) {
            this.activeItem = item;
            this.modalStatus = item.status || 'pending';
            this.modalPriority = item.priority || 'medium';
            this.modalReply = item.admin_reply || '';
            this.modalSendEmail = Boolean(item.email && item.is_anonymous != 1);
            this.showModal = true;
        },

        deleteFeedback(id, feedbackNo) {
            if (confirm(`Are you sure you want to permanently delete feedback [${feedbackNo}]?`)) {
                document.getElementById('deleteFeedbackId').value = id;
                document.getElementById('deleteFeedbackForm').submit();
            }
        },

        formatDate(dateStr) {
            if (!dateStr) return 'N/A';
            const d = new Date(dateStr);
            return d.toLocaleDateString('en-US', { day: '2-digit', month: 'short', year: 'numeric' });
        },

        formatCategory(cat) {
            if (!cat) return 'General';
            return cat.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
        },

        getSubmitterBadgeClass(type) {
            const map = {
                'employee': 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
                'member': 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300',
                'volunteer': 'bg-pink-100 text-pink-800 dark:bg-pink-900/40 dark:text-pink-300',
                'field_agent': 'bg-teal-100 text-teal-800 dark:bg-teal-900/40 dark:text-teal-300',
                'donor': 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300'
            };
            return map[type] || 'bg-gray-100 text-gray-800';
        },

        getStatusBadgeClass(s) {
            const map = {
                'pending': 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
                'under_review': 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-300',
                'action_taken': 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300',
                'closed': 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300'
            };
            return map[s] || 'bg-gray-100 text-gray-800';
        },

        getStatusIcon(s) {
            const map = {
                'pending': 'fa-solid fa-clock',
                'under_review': 'fa-solid fa-magnifying-glass',
                'action_taken': 'fa-solid fa-list-check',
                'closed': 'fa-solid fa-circle-check'
            };
            return map[s] || 'fa-solid fa-circle-info';
        },

        getStatusLabel(s) {
            const map = {
                'pending': 'Pending',
                'under_review': 'Under Review',
                'action_taken': 'Action Taken',
                'closed': 'Closed'
            };
            return map[s] || s;
        }
    }
}
</script>

<?php require 'includes/footer.php'; ?>
