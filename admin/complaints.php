<?php
// ============================================================
// admin/complaints.php
// Complaints & Suggestions Management Dashboard
// Features: List, Search by keyword, Status/Date Filter, Status Change,
// Admin Direct Reply Box, and Automated Email Notification on Reply.
// ============================================================

require 'includes/header.php';
require '../config/db.php';
require_once '../includes/functions.php';

if (!canAccessModule($pdo, 'coordinator', 'page.complaints')) {
    setFlash('error', 'Access denied. You do not have permission to manage complaints.');
    header('Location: dashboard.php');
    exit;
}

$csrfToken = generateCsrfToken();

// Fetch all complaints and suggestions with replied_by officer details
$sql = "
    SELECT 
        c.*, 
        u.name AS replied_by_name, 
        u.role AS replied_by_role
    FROM `complaints` c
    LEFT JOIN `users` u ON c.reply_by = u.id
    ORDER BY c.created_at DESC, c.id DESC
";
$complaints = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Calculate real-time metrics
$totalCount = count($complaints);
$pendingCount = 0;
$inProgressCount = 0;
$onHoldCount = 0;
$resolvedCount = 0;
$complaintTypeCount = 0;
$suggestionTypeCount = 0;

foreach ($complaints as $c) {
    if ($c['status'] === 'pending') $pendingCount++;
    elseif ($c['status'] === 'in_progress') $inProgressCount++;
    elseif ($c['status'] === 'on_hold') $onHoldCount++;
    elseif ($c['status'] === 'resolved') $resolvedCount++;

    if ($c['type'] === 'complaint') $complaintTypeCount++;
    elseif ($c['type'] === 'suggestion') $suggestionTypeCount++;
}
?>

<div class="flex h-screen overflow-hidden bg-slate-50 dark:bg-gray-900" 
     x-data="complaintsAdminApp()" 
     x-init="init()">

    <?php require 'includes/sidebar.php'; ?>

    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300 min-w-0">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-8">

            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div>
                    <div class="flex items-center gap-3">
                        <span class="w-10 h-10 rounded-2xl bg-teal-50 dark:bg-teal-900/40 text-[#0F8B8D] flex items-center justify-center text-lg shadow-sm">
                            <i class="fa-solid fa-comments"></i>
                        </span>
                        <div>
                            <h3 class="text-2xl md:text-3xl font-black text-gray-800 dark:text-white">
                                Complaint & Suggestion Management
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                Review citizen grievances and suggestions, change status, post official resolutions, and dispatch automated email updates.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <a href="../track-complaint.php" target="_blank" class="bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 hover:bg-slate-50 dark:hover:bg-gray-700 font-bold px-3.5 py-2.5 rounded-2xl shadow-sm text-xs flex items-center gap-1.5 transition">
                        <i class="fa-solid fa-arrow-up-right-from-square text-teal-600"></i> Open Public Tracker
                    </a>
                    <a href="../complaint.php" target="_blank" class="bg-[#0F8B8D] hover:bg-[#0c7274] text-white font-bold py-2.5 px-4 rounded-2xl shadow-md text-xs flex items-center gap-1.5 transition transform active:scale-95">
                        <i class="fa-solid fa-plus"></i> Submit Portal Ticket
                    </a>
                </div>
            </div>

            <!-- KPI Metric Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <!-- Total Records -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase text-gray-400">Total Tickets</span>
                        <span class="w-9 h-9 rounded-xl bg-teal-50 dark:bg-teal-900/30 text-[#0F8B8D] flex items-center justify-center text-sm">
                            <i class="fa-solid fa-ticket"></i>
                        </span>
                    </div>
                    <p class="text-2xl font-black text-gray-800 dark:text-white mt-2"><?php echo $totalCount; ?></p>
                    <p class="text-[11px] text-gray-400 mt-1"><?php echo $complaintTypeCount; ?> Complaints • <?php echo $suggestionTypeCount; ?> Suggestions</p>
                </div>

                <!-- Pending Review -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase text-gray-400">Pending Review</span>
                        <span class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 flex items-center justify-center text-sm">
                            <i class="fa-solid fa-clock"></i>
                        </span>
                    </div>
                    <p class="text-2xl font-black text-amber-600 mt-2"><?php echo $pendingCount; ?></p>
                    <p class="text-[11px] text-gray-400 mt-1">Awaiting preliminary assessment</p>
                </div>

                <!-- In Progress / On Hold -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase text-gray-400">In Progress / Hold</span>
                        <span class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 flex items-center justify-center text-sm">
                            <i class="fa-solid fa-spinner"></i>
                        </span>
                    </div>
                    <p class="text-2xl font-black text-blue-600 mt-2"><?php echo $inProgressCount + $onHoldCount; ?></p>
                    <p class="text-[11px] text-gray-400 mt-1">Active case investigation</p>
                </div>

                <!-- Resolved & Closed -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase text-gray-400">Resolved & Closed</span>
                        <span class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 flex items-center justify-center text-sm">
                            <i class="fa-solid fa-circle-check"></i>
                        </span>
                    </div>
                    <p class="text-2xl font-black text-emerald-600 mt-2"><?php echo $resolvedCount; ?></p>
                    <p class="text-[11px] text-gray-400 mt-1">Resolution delivered to submitter</p>
                </div>
            </div>

            <!-- Search & Filter Controls Panel -->
            <div class="bg-white dark:bg-gray-800 p-5 rounded-3xl shadow-sm border border-slate-200 dark:border-gray-700 mb-6 space-y-4">
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
                    
                    <!-- Keyword Search -->
                    <div class="lg:col-span-4">
                        <label class="text-[11px] font-bold text-gray-600 dark:text-gray-400 block mb-1">
                            <i class="fa-solid fa-magnifying-glass text-teal-600 mr-1"></i> Search Tickets
                        </label>
                        <div class="relative">
                            <input type="text" x-model="search" placeholder="Search ticket no, name, phone, email, subject..." 
                                   class="w-full text-xs p-2.5 pl-8 pr-8 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                            <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 text-gray-400 text-xs"></i>
                            <button type="button" x-show="search" @click="search = ''" class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600 text-xs">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Type Filter -->
                    <div class="lg:col-span-2">
                        <label class="text-[11px] font-bold text-gray-600 dark:text-gray-400 block mb-1">
                            <i class="fa-solid fa-tag text-teal-600 mr-1"></i> Ticket Type
                        </label>
                        <select x-model="typeFilter" class="w-full text-xs font-medium p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                            <option value="">All Types (<?php echo $totalCount; ?>)</option>
                            <option value="complaint">Complaints (<?php echo $complaintTypeCount; ?>)</option>
                            <option value="suggestion">Suggestions (<?php echo $suggestionTypeCount; ?>)</option>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div class="lg:col-span-3">
                        <label class="text-[11px] font-bold text-gray-600 dark:text-gray-400 block mb-1">
                            <i class="fa-solid fa-circle-check text-indigo-600 mr-1"></i> Status
                        </label>
                        <select x-model="statusFilter" class="w-full text-xs font-medium p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending Review (<?php echo $pendingCount; ?>)</option>
                            <option value="in_progress">Under Investigation (<?php echo $inProgressCount; ?>)</option>
                            <option value="on_hold">On Hold (<?php echo $onHoldCount; ?>)</option>
                            <option value="resolved">Resolved & Closed (<?php echo $resolvedCount; ?>)</option>
                        </select>
                    </div>

                    <!-- Date Period -->
                    <div class="lg:col-span-3">
                        <label class="text-[11px] font-bold text-gray-600 dark:text-gray-400 block mb-1">
                            <i class="fa-solid fa-calendar-days text-teal-600 mr-1"></i> Date Range
                        </label>
                        <select x-model="dateFilter" class="w-full text-xs font-medium p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                            <option value="all">All Time</option>
                            <option value="today">Today</option>
                            <option value="this_month">This Month</option>
                            <option value="last_month">Last Month</option>
                            <option value="this_year">This Year (<?php echo date('Y'); ?>)</option>
                            <option value="custom">Custom Range...</option>
                        </select>
                    </div>

                </div>

                <!-- Optional Custom Date Range Row -->
                <div x-show="dateFilter === 'custom'" x-collapse class="pt-3 border-t border-dashed border-slate-200 dark:border-gray-700 grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="text-[11px] font-bold text-gray-600 dark:text-gray-400 block mb-1">Start Date (From)</label>
                        <input type="date" x-model="startDate" class="w-full text-xs p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                    </div>
                    <div>
                        <label class="text-[11px] font-bold text-gray-600 dark:text-gray-400 block mb-1">End Date (To)</label>
                        <input type="date" x-model="endDate" class="w-full text-xs p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs pt-1" x-show="hasActiveFilters">
                    <span class="text-gray-500" x-text="'Showing ' + filteredTickets.length + ' matching records'"></span>
                    <button type="button" @click="resetFilters()" class="text-rose-600 dark:text-rose-400 font-bold hover:underline flex items-center gap-1">
                        <i class="fa-solid fa-rotate-left"></i> Reset All Filters
                    </button>
                </div>

            </div>

            <!-- Complaints List Table -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl border border-slate-200 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                        <thead class="bg-slate-50 dark:bg-gray-700/50 text-[11px] font-bold uppercase text-gray-500 dark:text-gray-400 border-b border-slate-200 dark:border-gray-700">
                            <tr>
                                <th class="p-3.5 text-center">#</th>
                                <th class="p-3.5">Ticket Ref.</th>
                                <th class="p-3.5">Type & Priority</th>
                                <th class="p-3.5">Submitter Details</th>
                                <th class="p-3.5">Subject & Matter</th>
                                <th class="p-3.5 text-center">Status</th>
                                <th class="p-3.5">Admin Reply Status</th>
                                <th class="p-3.5">Registered Date</th>
                                <th class="p-3.5 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-gray-700">
                            <template x-for="(t, idx) in paginatedTickets" :key="t.id">
                                <tr class="hover:bg-slate-50/70 dark:hover:bg-gray-700/30 transition">
                                    <td class="p-3.5 text-center font-bold text-gray-400" x-text="(page - 1) * perPage + idx + 1"></td>
                                    
                                    <!-- Ticket No -->
                                    <td class="p-3.5 font-mono font-black text-indigo-600 dark:text-indigo-400 whitespace-nowrap">
                                        <a :href="'../track-complaint.php?ticket=' + encodeURIComponent(t.ticket_no)" target="_blank" class="hover:underline flex items-center gap-1">
                                            <span x-text="t.ticket_no"></span>
                                            <i class="fa-solid fa-arrow-up-right-from-square text-[10px] text-gray-400"></i>
                                        </a>
                                    </td>

                                    <!-- Type & Priority -->
                                    <td class="p-3.5 whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase"
                                              :class="t.type === 'complaint' ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300' : 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-300'">
                                            <i class="fa-solid" :class="t.type === 'complaint' ? 'fa-triangle-exclamation' : 'fa-lightbulb'"></i>
                                            <span x-text="t.type"></span>
                                        </span>
                                        <span class="block text-[10px] font-semibold text-gray-400 mt-0.5 uppercase" x-text="'Priority: ' + (t.priority || 'Medium')"></span>
                                    </td>

                                    <!-- Submitter Details -->
                                    <td class="p-3.5">
                                        <div class="font-bold text-gray-900 dark:text-white" x-text="t.name"></div>
                                        <div class="text-[11px] text-gray-500 flex items-center gap-1.5 mt-0.5">
                                            <span x-text="t.contact"></span>
                                            <button type="button" @click="quickWhatsApp(t)" 
                                                    class="text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 p-0.5 rounded hover:bg-emerald-50 dark:hover:bg-emerald-950/40 transition" 
                                                    title="Send WhatsApp update to submitter">
                                                <i class="fa-brands fa-whatsapp text-sm"></i>
                                            </button>
                                        </div>
                                        <div class="text-[10px] text-gray-400 truncate max-w-[150px]" x-show="t.email" x-text="t.email"></div>
                                    </td>

                                    <!-- Subject & Description -->
                                    <td class="p-3.5 max-w-xs">
                                        <div class="font-bold text-gray-800 dark:text-gray-200 truncate" :title="t.subject" x-text="t.subject"></div>
                                        <div class="text-[11px] text-gray-400 truncate max-w-[220px]" :title="t.description" x-text="t.description"></div>
                                        <div x-show="t.attachment_path" class="mt-1">
                                            <a :href="'../' + t.attachment_path" target="_blank" class="text-[10px] text-teal-600 dark:text-teal-400 font-bold hover:underline inline-flex items-center gap-1">
                                                <i class="fa-solid fa-paperclip"></i> Attachment
                                            </a>
                                        </div>
                                    </td>

                                    <!-- Status -->
                                    <td class="p-3.5 text-center whitespace-nowrap">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider inline-flex items-center gap-1"
                                              :class="{
                                                  'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300': t.status === 'pending',
                                                  'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300': t.status === 'in_progress',
                                                  'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300': t.status === 'on_hold',
                                                  'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300': t.status === 'resolved'
                                              }">
                                            <i class="fa-solid text-[9px]" :class="{
                                                'fa-clock': t.status === 'pending',
                                                'fa-spinner': t.status === 'in_progress',
                                                'fa-pause': t.status === 'on_hold',
                                                'fa-circle-check': t.status === 'resolved'
                                            }"></i>
                                            <span x-text="formatStatusLabel(t.status)"></span>
                                        </span>
                                    </td>

                                    <!-- Admin Reply Status -->
                                    <td class="p-3.5">
                                        <template x-if="t.admin_reply">
                                            <div class="text-xs">
                                                <span class="text-emerald-600 dark:text-emerald-400 font-bold flex items-center gap-1 text-[11px]">
                                                    <i class="fa-solid fa-circle-check"></i> Replied
                                                </span>
                                                <div class="text-[10px] text-gray-400 truncate max-w-[140px]" :title="t.admin_reply" x-text="t.admin_reply"></div>
                                            </div>
                                        </template>
                                        <template x-if="!t.admin_reply">
                                            <span class="text-gray-400 italic text-[11px]">Awaiting Reply</span>
                                        </template>
                                    </td>

                                    <!-- Registered Date -->
                                    <td class="p-3.5 font-medium whitespace-nowrap text-gray-700 dark:text-gray-300" x-text="formatDate(t.created_at)"></td>

                                    <!-- Actions -->
                                    <td class="p-3.5 text-center whitespace-nowrap">
                                        <div class="flex items-center justify-center gap-1.5">
                                            
                                            <!-- Manage / Direct Reply Button -->
                                            <button type="button" @click="openManageModal(t)" 
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-teal-50 hover:bg-teal-100 dark:bg-teal-900/30 dark:hover:bg-teal-900/50 text-[#0F8B8D] dark:text-teal-300 font-bold text-xs border border-teal-200 dark:border-teal-800 transition" 
                                                    title="Manage, Change Status & Reply">
                                                <i class="fa-solid fa-reply"></i>
                                                <span>Manage</span>
                                            </button>

                                            <!-- Quick WhatsApp Button -->
                                            <button type="button" @click="quickWhatsApp(t)" 
                                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:hover:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300 font-bold text-xs border border-emerald-200 dark:border-emerald-800 transition" 
                                                    title="Send Instant Status Update via WhatsApp">
                                                <i class="fa-brands fa-whatsapp text-sm text-emerald-600 dark:text-emerald-400"></i>
                                                <span class="hidden xl:inline">WhatsApp</span>
                                            </button>

                                            <!-- Delete Button -->
                                            <button type="button" @click="confirmDelete(t.id)" 
                                                    class="p-1.5 rounded-lg text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/30 transition" 
                                                    title="Delete Ticket Record">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>

                                        </div>
                                    </td>

                                </tr>
                            </template>

                            <template x-if="filteredTickets.length === 0">
                                <tr>
                                    <td colspan="9" class="p-12 text-center text-xs text-gray-400">
                                        <i class="fa-solid fa-inbox text-4xl mb-3 text-gray-300 dark:text-gray-600 block"></i>
                                        <p class="font-bold text-gray-600 dark:text-gray-300 text-sm">No Complaint or Suggestion Records Found</p>
                                        <p class="mt-1">No ticket matches your current search or filter criteria.</p>
                                        <button type="button" @click="resetFilters()" class="mt-3 px-4 py-2 bg-slate-100 dark:bg-gray-700 hover:bg-slate-200 text-gray-700 dark:text-gray-200 rounded-xl font-bold transition">
                                            Clear All Filters
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div class="px-6 py-4 bg-gray-50/50 dark:bg-gray-700/20 border-t border-slate-100 dark:border-gray-700 flex flex-col sm:flex-row items-center justify-between gap-4" x-show="filteredTickets.length > 0">
                    <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                        <span>Showing <strong class="text-gray-800 dark:text-gray-200" x-text="(page - 1) * perPage + 1"></strong> to <strong class="text-gray-800 dark:text-gray-200" x-text="Math.min(page * perPage, filteredTickets.length)"></strong> of <strong class="text-gray-800 dark:text-gray-200" x-text="filteredTickets.length"></strong> results</span>
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

    <!-- ════════════════════════════════════════════════════════════ -->
    <!-- MANAGE, STATUS CHANGE & DIRECT ADMIN REPLY MODAL             -->
    <!-- ════════════════════════════════════════════════════════════ -->
    <div x-show="isManageModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm" x-transition.opacity @click.self="closeManageModal()">
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden border border-slate-200 dark:border-gray-700 flex flex-col max-h-[90vh]">
            
            <form action="actions/complaint_logic.php" method="POST" class="flex flex-col h-full">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="action" value="update_complaint">
                <input type="hidden" name="id" :value="activeTicket.id">

                <!-- Modal Top Header -->
                <div class="p-5 border-b border-slate-100 dark:border-gray-700 flex items-center justify-between bg-gradient-to-r from-slate-900 to-slate-800 text-white">
                    <div class="flex items-center gap-2.5">
                        <span class="w-8 h-8 rounded-xl bg-teal-500/20 text-teal-300 flex items-center justify-center text-xs">
                            <i class="fa-solid fa-headset"></i>
                        </span>
                        <div>
                            <h4 class="text-sm font-black flex items-center gap-2">
                                <span>Manage Ticket:</span>
                                <span class="font-mono text-teal-300" x-text="activeTicket.ticket_no"></span>
                            </h4>
                            <p class="text-[11px] text-slate-400" x-text="'Submitted on ' + formatDate(activeTicket.created_at) + ' by ' + activeTicket.name"></p>
                        </div>
                    </div>
                    <button type="button" @click="closeManageModal()" class="text-white/70 hover:text-white">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <!-- Modal Content Scrollable Area -->
                <div class="p-6 overflow-y-auto space-y-5 flex-1 text-xs text-gray-700 dark:text-gray-200">
                    
                    <!-- Submitter Particulars Info Box -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-4 rounded-2xl bg-slate-50 dark:bg-gray-900/60 border border-slate-200 dark:border-gray-700">
                        <div>
                            <span class="text-gray-400 font-bold block text-[10px] uppercase">Submitter Name:</span>
                            <p class="font-bold text-gray-900 dark:text-white mt-0.5" x-text="activeTicket.name"></p>
                        </div>
                        <div>
                            <span class="text-gray-400 font-bold block text-[10px] uppercase">Contact Number:</span>
                            <div class="flex items-center gap-1.5 mt-0.5">
                                <p class="font-bold text-gray-900 dark:text-white" x-text="activeTicket.contact"></p>
                                <button type="button" @click="sendWhatsAppReply()" class="text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 text-xs" title="Chat on WhatsApp">
                                    <i class="fa-brands fa-whatsapp"></i>
                                </button>
                            </div>
                        </div>
                        <div>
                            <span class="text-gray-400 font-bold block text-[10px] uppercase">Email Address:</span>
                            <p class="font-bold text-indigo-600 dark:text-indigo-400 mt-0.5 truncate" x-text="activeTicket.email || 'N/A'"></p>
                        </div>
                    </div>

                    <!-- Subject & Description of Citizen Grievance -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-gray-400 font-bold block text-[10px] uppercase">
                                <span x-text="activeTicket.type === 'complaint' ? 'Grievance Matter & Description:' : 'Suggestion Details:'"></span>
                            </span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase"
                                  :class="activeTicket.type === 'complaint' ? 'bg-rose-100 text-rose-800' : 'bg-teal-100 text-teal-800'"
                                  x-text="activeTicket.type"></span>
                        </div>
                        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-gray-900/60 border border-slate-200 dark:border-gray-700 space-y-1.5">
                            <p class="font-bold text-gray-900 dark:text-white text-xs" x-text="activeTicket.subject"></p>
                            <p class="text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-line" x-text="activeTicket.description"></p>
                            <div x-show="activeTicket.attachment_path" class="pt-2 border-t border-slate-200 dark:border-gray-700">
                                <a :href="'../' + activeTicket.attachment_path" target="_blank" class="text-teal-600 dark:text-teal-400 font-bold hover:underline inline-flex items-center gap-1.5">
                                    <i class="fa-solid fa-paperclip"></i> View Attached Document / Photo
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- 1. STATUS CHANGE DROPDOWN -->
                    <div class="p-4 rounded-2xl bg-slate-100/70 dark:bg-gray-900/40 border border-slate-200 dark:border-gray-700 space-y-2">
                        <label class="block text-xs font-bold text-gray-800 dark:text-gray-200 flex items-center gap-1.5">
                            <i class="fa-solid fa-list-check text-teal-600"></i> Update Grievance Status *
                        </label>
                        <select name="status" x-model="activeTicket.status" class="w-full text-xs font-bold p-3 border rounded-xl bg-white dark:bg-gray-800 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                            <option value="pending">Pending Review</option>
                            <option value="in_progress">Under Investigation / In Progress</option>
                            <option value="on_hold">On Hold / Awaiting Further Info</option>
                            <option value="resolved">Resolved & Closed</option>
                        </select>
                    </div>

                    <!-- 2. DIRECT ADMIN REPLY / RESOLUTION TEXTAREA & WHATSAPP ACTION -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-gray-800 dark:text-gray-200 flex items-center gap-1.5">
                                <i class="fa-solid fa-pen-nib text-teal-600"></i> Official Administrative Reply / Resolution Notes
                            </label>
                            <button type="button" @click="sendWhatsAppReply()" 
                                    class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 font-bold text-[11px] inline-flex items-center gap-1 hover:underline">
                                <i class="fa-brands fa-whatsapp"></i> Test / Send on WhatsApp
                            </button>
                        </div>
                        <textarea name="admin_reply" x-model="activeTicket.admin_reply" rows="4" 
                                  placeholder="Write the official administrative response or action taken details here. This message will be displayed on the public tracking portal, emailed to the citizen, and can be sent directly via WhatsApp..." 
                                  class="w-full p-3.5 text-xs border rounded-2xl bg-white dark:bg-gray-800 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D] leading-relaxed resize-y"></textarea>
                        <p class="text-[10px] text-gray-400 mt-1">This resolution response will be permanently logged, emailed to the submitter, and can be sent instantly via WhatsApp.</p>
                    </div>

                    <!-- 3. EMAIL NOTIFICATION CHECKBOX -->
                    <div class="p-3.5 rounded-2xl bg-teal-50 dark:bg-teal-900/30 border border-teal-200 dark:border-teal-700/50 flex items-center justify-between gap-3">
                        <label class="flex items-center gap-2.5 cursor-pointer text-xs font-bold text-teal-900 dark:text-teal-200">
                            <input type="checkbox" name="send_email" value="1" checked class="w-4 h-4 text-[#0F8B8D] rounded border-gray-300 focus:ring-[#0F8B8D]">
                            <span>Send automated email notification with status and reply to submitter</span>
                        </label>
                        <span class="text-[10px] text-teal-700 dark:text-teal-300 font-mono font-bold" x-show="activeTicket.email" x-text="activeTicket.email"></span>
                    </div>

                </div>

                <!-- Modal Bottom Action Bar -->
                <div class="p-4 bg-slate-50 dark:bg-gray-700/50 border-t border-slate-100 dark:border-gray-700 flex flex-wrap items-center justify-between gap-3">
                    <button type="button" @click="confirmDelete(activeTicket.id)" class="px-3.5 py-2 rounded-xl text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 font-bold text-xs flex items-center gap-1.5 transition">
                        <i class="fa-solid fa-trash"></i> Delete Ticket
                    </button>

                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" @click="closeManageModal()" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 font-bold text-xs hover:bg-slate-100 transition">
                            Cancel
                        </button>
                        
                        <!-- Reply via WhatsApp Button -->
                        <button type="button" @click="sendWhatsAppReply()" 
                                class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-md shadow-emerald-600/20 transition transform active:scale-95"
                                title="Open WhatsApp with pre-filled status and reply text">
                            <i class="fa-brands fa-whatsapp text-sm"></i> Reply via WhatsApp
                        </button>

                        <button type="submit" class="px-5 py-2 rounded-xl bg-[#0F8B8D] hover:bg-[#0c7274] text-white font-bold text-xs flex items-center gap-1.5 shadow-md shadow-teal-700/20 transition transform active:scale-95">
                            <i class="fa-solid fa-floppy-disk"></i> Save & Dispatch Update
                        </button>
                    </div>
                </div>

            </form>

        </div>
    </div>

    <!-- ── HIDDEN DELETE FORM ──────────────────────────────────────── -->
    <form id="deleteComplaintForm" action="actions/complaint_logic.php" method="POST" class="hidden">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <input type="hidden" name="action" value="delete_complaint">
        <input type="hidden" name="id" id="deleteComplaintId">
    </form>

</div>

<script>
function complaintsAdminApp() {
    return {
        items: <?php echo json_encode($complaints); ?>,
        search: '',
        typeFilter: '',
        statusFilter: '',
        dateFilter: 'all',
        startDate: '',
        endDate: '',

        page: 1,
        perPage: 10,

        isManageModalOpen: false,
        activeTicket: {},

        get totalPages() {
            return Math.ceil(this.filteredTickets.length / this.perPage) || 1;
        },

        get paginatedTickets() {
            const start = (this.page - 1) * this.perPage;
            return this.filteredTickets.slice(start, start + this.perPage);
        },

        setPage(p) {
            if (p >= 1 && p <= this.totalPages) this.page = p;
        },

        init() {},

        get hasActiveFilters() {
            return !!(this.search || this.typeFilter || this.statusFilter || this.dateFilter !== 'all' || this.startDate || this.endDate);
        },

        resetFilters() {
            this.search = '';
            this.typeFilter = '';
            this.statusFilter = '';
            this.dateFilter = 'all';
            this.startDate = '';
            this.endDate = '';
        },

        openManageModal(ticket) {
            this.activeTicket = {
                ...ticket,
                admin_reply: ticket.admin_reply ?? ''
            };
            this.isManageModalOpen = true;
        },

        closeManageModal() {
            this.isManageModalOpen = false;
        },

        confirmDelete(id) {
            if (confirm('Are you sure you want to delete this complaint / suggestion record permanently?')) {
                document.getElementById('deleteComplaintId').value = id;
                document.getElementById('deleteComplaintForm').submit();
            }
        },

        formatDate(d) {
            if (!d) return '-';
            const parts = d.split(' ')[0].split('-');
            if (parts.length === 3) {
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                const mIdx = parseInt(parts[1], 10) - 1;
                return `${parts[2]} ${months[mIdx] || parts[1]}, ${parts[0]}`;
            }
            return d;
        },

        formatStatusLabel(st) {
            switch (st) {
                case 'pending': return 'Pending Review';
                case 'in_progress': return 'In Progress';
                case 'on_hold': return 'On Hold';
                case 'resolved': return 'Resolved & Closed';
                default: return st ? (st.charAt(0).toUpperCase() + st.slice(1)) : 'Pending';
            }
        },

        cleanPhoneNumber(phone) {
            if (!phone) return '';
            let clean = phone.toString().replace(/\D/g, '');
            if (clean.length === 10) {
                clean = '91' + clean;
            }
            return clean;
        },

        buildWhatsAppMessage(ticket, customReply, customStatus) {
            const orgName = <?php echo json_encode($siteName ?? 'NGO Support Team'); ?>;
            const baseUrl = <?php echo json_encode(rtrim(appBaseUrl(), '/')); ?>;
            const ticketNo = ticket.ticket_no || '';
            const name = ticket.name || 'Citizen';
            const typeLabel = (ticket.type === 'suggestion' ? 'Suggestion' : 'Complaint / Grievance');
            const subject = ticket.subject || 'General Request';
            const stLabel = this.formatStatusLabel(customStatus || ticket.status || 'pending');
            const reply = (customReply && customReply.trim()) ? customReply.trim() : (ticket.admin_reply && ticket.admin_reply.trim() ? ticket.admin_reply.trim() : 'We have acknowledged your submission and our team is actively addressing it.');
            const trackUrl = `${baseUrl}/track-complaint.php?ticket=${encodeURIComponent(ticketNo)}`;

            return `*Namaste ${name},*\n\n` +
                   `This is an official update regarding your *${typeLabel}* submitted to *${orgName}*.\n\n` +
                   `📌 *Ticket Ref:* ${ticketNo}\n` +
                   `📝 *Subject:* ${subject}\n` +
                   `📊 *Current Status:* ${stLabel}\n\n` +
                   `💬 *Official Administrative Response / Resolution:*\n${reply}\n\n` +
                   `🔍 *Track Live Status & Timeline:* ${trackUrl}\n\n` +
                   `Warm regards,\n*${orgName} Team*`;
        },

        sendWhatsAppReply() {
            const phone = this.cleanPhoneNumber(this.activeTicket.contact);
            if (!phone) {
                alert('No valid contact number found for this ticket.');
                return;
            }
            const msg = this.buildWhatsAppMessage(this.activeTicket, this.activeTicket.admin_reply, this.activeTicket.status);
            const waUrl = `https://wa.me/${phone}?text=${encodeURIComponent(msg)}`;
            window.open(waUrl, '_blank');
        },

        quickWhatsApp(ticket) {
            const phone = this.cleanPhoneNumber(ticket.contact);
            if (!phone) {
                alert('No valid contact number found for this ticket.');
                return;
            }
            const msg = this.buildWhatsAppMessage(ticket, ticket.admin_reply, ticket.status);
            const waUrl = `https://wa.me/${phone}?text=${encodeURIComponent(msg)}`;
            window.open(waUrl, '_blank');
        },

        get filteredTickets() {
            return this.items.filter(t => {
                const s = this.search.toLowerCase().trim();
                const matchesSearch = !s ||
                    (t.ticket_no && t.ticket_no.toLowerCase().includes(s)) ||
                    (t.name && t.name.toLowerCase().includes(s)) ||
                    (t.contact && t.contact.toLowerCase().includes(s)) ||
                    (t.email && t.email.toLowerCase().includes(s)) ||
                    (t.subject && t.subject.toLowerCase().includes(s)) ||
                    (t.description && t.description.toLowerCase().includes(s));

                const matchesType = !this.typeFilter || (t.type === this.typeFilter);
                const matchesStatus = !this.statusFilter || (t.status === this.statusFilter);

                let matchesDate = true;
                const createdDate = t.created_at ? t.created_at.split(' ')[0] : '';
                if (this.dateFilter === 'today') {
                    const today = new Date().toISOString().slice(0, 10);
                    matchesDate = createdDate === today;
                } else if (this.dateFilter === 'this_month') {
                    const curYm = new Date().toISOString().slice(0, 7);
                    matchesDate = createdDate.startsWith(curYm);
                } else if (this.dateFilter === 'last_month') {
                    const d = new Date();
                    d.setMonth(d.getMonth() - 1);
                    const prevYm = d.toISOString().slice(0, 7);
                    matchesDate = createdDate.startsWith(prevYm);
                } else if (this.dateFilter === 'this_year') {
                    const curY = new Date().getFullYear().toString();
                    matchesDate = createdDate.startsWith(curY);
                } else if (this.dateFilter === 'custom') {
                    if (this.startDate && createdDate < this.startDate) matchesDate = false;
                    if (this.endDate && createdDate > this.endDate) matchesDate = false;
                }

                return matchesSearch && matchesType && matchesStatus && matchesDate;
            });
        }
    };
}
</script>

<?php require 'includes/footer.php'; ?>
