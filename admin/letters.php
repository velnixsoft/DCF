<?php
// ============================================================
// admin/letters.php
// Generated Letters List & Correspondence History Management
// Features: Search by Type, Date, Recipient, Re-download PDF, Print & Email
// ============================================================

require 'includes/header.php';
require '../config/db.php';
require_once '../includes/functions.php';
require_once '../includes/letterhead_helper.php';

if (!canAccessModule($pdo, 'coordinator', 'page.letters')) {
    setFlash('error', 'Access denied. You do not have permission to view letters.');
    header('Location: dashboard.php');
    exit;
}

$csrfToken = generateCsrfToken();
$lhSettings = get_letterhead_settings($pdo);

// Fetch all letters with user information
$sql = "
    SELECT l.*, u.name as generated_by_name, u.role as generated_by_role
    FROM letters l
    LEFT JOIN users u ON l.generated_by = u.id
    ORDER BY l.generated_date DESC, l.id DESC
";
$letters = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Metrics
$totalLetters = count($letters);
$sentCount = 0;
$generatedCount = 0;
$draftCount = 0;
$thisMonthCount = 0;
$currentYm = date('Y-m');

foreach ($letters as $l) {
    if ($l['status'] === 'Sent') $sentCount++;
    if ($l['status'] === 'Generated') $generatedCount++;
    if ($l['status'] === 'Draft') $draftCount++;
    if (!empty($l['generated_date']) && strpos($l['generated_date'], $currentYm) === 0) {
        $thisMonthCount++;
    }
}
?>

<div class="flex h-screen overflow-hidden bg-slate-50 dark:bg-gray-900"
     x-data="lettersDirectory()"
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
                            <i class="fa-solid fa-file-signature"></i>
                        </span>
                        <div>
                            <h3 class="text-2xl md:text-3xl font-black text-gray-800 dark:text-white">
                                Generated Letters & History
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                Search archive by type, date period, or recipient, and re-download official letterhead PDFs instantly.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <a href="settings.php?tab=letterhead" class="bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 hover:bg-slate-50 dark:hover:bg-gray-700 font-bold px-3.5 py-2.5 rounded-2xl shadow-sm text-xs flex items-center gap-2 transition">
                        <i class="fa-solid fa-sliders text-teal-600"></i> Letterhead Settings
                    </a>
                    <a href="letter_editor.php" class="bg-[#0F8B8D] hover:bg-[#0c7274] text-white font-bold py-2.5 px-5 rounded-2xl shadow-md text-xs flex items-center gap-2 transition transform active:scale-95">
                        <i class="fa-solid fa-pen-nib"></i> Compose New Letter
                    </a>
                </div>
            </div>

            <!-- KPI Metric Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <!-- Total Generated -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase text-gray-400">Total Letters</span>
                        <span class="w-9 h-9 rounded-xl bg-teal-50 dark:bg-teal-900/30 text-[#0F8B8D] flex items-center justify-center text-sm">
                            <i class="fa-solid fa-file-invoice"></i>
                        </span>
                    </div>
                    <p class="text-2xl font-black text-gray-800 dark:text-white mt-2"><?php echo $totalLetters; ?></p>
                    <p class="text-[11px] text-gray-400 mt-1">All historical correspondence</p>
                </div>

                <!-- Issued This Month -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase text-gray-400">Issued This Month</span>
                        <span class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 flex items-center justify-center text-sm">
                            <i class="fa-solid fa-calendar-check"></i>
                        </span>
                    </div>
                    <p class="text-2xl font-black text-indigo-600 mt-2"><?php echo $thisMonthCount; ?></p>
                    <p class="text-[11px] text-gray-400 mt-1"><?php echo date('F Y'); ?> issuances</p>
                </div>

                <!-- Dispatched / Sent via Email -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase text-gray-400">Dispatched (Email)</span>
                        <span class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 flex items-center justify-center text-sm">
                            <i class="fa-solid fa-paper-plane"></i>
                        </span>
                    </div>
                    <p class="text-2xl font-black text-emerald-600 mt-2"><?php echo $sentCount; ?></p>
                    <p class="text-[11px] text-gray-400 mt-1">Emailed to recipients</p>
                </div>

                <!-- Drafts / Pending -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase text-gray-400">Drafts / In-Progress</span>
                        <span class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 flex items-center justify-center text-sm">
                            <i class="fa-solid fa-pen-ruler"></i>
                        </span>
                    </div>
                    <p class="text-2xl font-black text-amber-600 mt-2"><?php echo $draftCount; ?></p>
                    <p class="text-[11px] text-gray-400 mt-1">Unfinalized records</p>
                </div>
            </div>

            <!-- Filter Controls Panel -->
            <div class="bg-white dark:bg-gray-800 p-5 rounded-3xl shadow-sm border border-slate-200 dark:border-gray-700 mb-6 space-y-4">
                
                <!-- Quick Type Pills -->
                <div class="flex items-center justify-between gap-3 overflow-x-auto pb-1 scrollbar-thin">
                    <div class="flex items-center gap-1.5 flex-nowrap">
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider whitespace-nowrap mr-1 flex items-center gap-1">
                            <i class="fa-solid fa-tags text-teal-600"></i> Type:
                        </span>
                        <button type="button" @click="typeFilter = ''" 
                                :class="typeFilter === '' ? 'bg-[#0F8B8D] text-white shadow-sm font-bold' : 'bg-slate-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-slate-200 dark:hover:bg-gray-600 font-medium'"
                                class="px-3 py-1.5 rounded-xl text-xs whitespace-nowrap transition">
                            All Types (<?php echo $totalLetters; ?>)
                        </button>
                        <template x-for="t in letterTypes" :key="t">
                            <button type="button" @click="typeFilter = (typeFilter === t ? '' : t)" 
                                    :class="typeFilter === t ? 'bg-[#0F8B8D] text-white shadow-sm font-bold' : 'bg-slate-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-slate-200 dark:hover:bg-gray-600 font-medium'"
                                    class="px-3 py-1.5 rounded-xl text-xs whitespace-nowrap transition flex items-center gap-1.5">
                                <span x-text="t"></span>
                                <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold bg-slate-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300" 
                                      :class="typeFilter === t ? 'bg-white/25 text-white' : ''"
                                      x-text="countByType(t)"></span>
                            </button>
                        </template>
                    </div>

                    <button type="button" x-show="hasActiveFilters" @click="resetFilters()" class="text-xs text-rose-600 dark:text-rose-400 font-bold hover:underline flex items-center gap-1 whitespace-nowrap">
                        <i class="fa-solid fa-rotate-left"></i> Reset Filters
                    </button>
                </div>

                <!-- Multi Filter Inputs: Search, Recipient, Date Period, Status -->
                <div class="pt-3 border-t border-slate-100 dark:border-gray-700 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
                    
                    <!-- Search Input (Subject, Ref, Any) -->
                    <div class="lg:col-span-4">
                        <label class="text-[11px] font-bold text-gray-600 dark:text-gray-400 block mb-1">
                            <i class="fa-solid fa-magnifying-glass text-teal-600 mr-1"></i> Search Correspondence
                        </label>
                        <div class="relative">
                            <input type="text" x-model="search" placeholder="Search subject, ref no, keywords..." 
                                   class="w-full text-xs p-2.5 pl-8 pr-8 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                            <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 text-gray-400 text-xs"></i>
                            <button type="button" x-show="search" @click="search = ''" class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600 text-xs">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Search by Recipient specifically -->
                    <div class="lg:col-span-3">
                        <label class="text-[11px] font-bold text-gray-600 dark:text-gray-400 block mb-1">
                            <i class="fa-solid fa-user text-indigo-600 mr-1"></i> Filter Recipient
                        </label>
                        <div class="relative">
                            <input type="text" x-model="recipientFilter" placeholder="Recipient name, org, email..." 
                                   class="w-full text-xs p-2.5 pl-8 pr-8 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                            <i class="fa-solid fa-user-tag absolute left-3 top-3 text-gray-400 text-xs"></i>
                            <button type="button" x-show="recipientFilter" @click="recipientFilter = ''" class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600 text-xs">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Date Period -->
                    <div class="lg:col-span-3">
                        <label class="text-[11px] font-bold text-gray-600 dark:text-gray-400 block mb-1">
                            <i class="fa-solid fa-calendar-days text-teal-600 mr-1"></i> Date Period
                        </label>
                        <select x-model="dateFilter" class="w-full text-xs font-medium p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                            <option value="all">All Time History</option>
                            <option value="today">Today</option>
                            <option value="this_month">This Month (<?php echo date('M Y'); ?>)</option>
                            <option value="last_month">Last Month</option>
                            <option value="this_year">This Year (<?php echo date('Y'); ?>)</option>
                            <option value="custom">Custom Date Range...</option>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div class="lg:col-span-2">
                        <label class="text-[11px] font-bold text-gray-600 dark:text-gray-400 block mb-1">
                            <i class="fa-solid fa-circle-check text-emerald-600 mr-1"></i> Status
                        </label>
                        <select x-model="statusFilter" class="w-full text-xs font-medium p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                            <option value="">All Statuses</option>
                            <option value="Generated">Generated</option>
                            <option value="Sent">Sent (Email)</option>
                            <option value="Draft">Draft</option>
                            <option value="Archived">Archived</option>
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

            </div>

            <!-- Letters List / History Table -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl border border-slate-200 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-slate-100 dark:border-gray-700 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-black text-gray-800 dark:text-white uppercase tracking-wider">
                            Correspondence Archive
                        </span>
                        <span class="px-2 py-0.5 rounded-full bg-teal-100 text-[#0F8B8D] dark:bg-teal-900/40 dark:text-teal-300 text-[10px] font-bold" x-text="filteredLetters.length + ' records'"></span>
                    </div>

                    <div class="text-[11px] text-gray-400 flex items-center gap-2">
                        <i class="fa-solid fa-circle-info text-teal-600"></i> Click Re-Download PDF for instant generation
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                        <thead class="bg-slate-50 dark:bg-gray-700/50 text-[11px] font-bold uppercase text-gray-500 dark:text-gray-400 border-b border-slate-200 dark:border-gray-700">
                            <tr>
                                <th class="p-3.5 text-center">#</th>
                                <th class="p-3.5">Date</th>
                                <th class="p-3.5">Ref. Number</th>
                                <th class="p-3.5">Type</th>
                                <th class="p-3.5">Recipient Particulars</th>
                                <th class="p-3.5">Subject</th>
                                <th class="p-3.5">Issued By</th>
                                <th class="p-3.5 text-center">Status</th>
                                <th class="p-3.5 text-center min-w-[200px]">Actions / Re-Download</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-gray-700">
                            <template x-for="(l, idx) in paginatedLetters" :key="l.id">
                                <tr class="hover:bg-slate-50/70 dark:hover:bg-gray-700/30 transition">
                                    <td class="p-3.5 text-center font-bold text-gray-400" x-text="(page - 1) * perPage + idx + 1"></td>
                                    
                                    <!-- Date -->
                                    <td class="p-3.5 font-medium whitespace-nowrap text-gray-700 dark:text-gray-300" x-text="formatDate(l.generated_date)"></td>
                                    
                                    <!-- Ref Number -->
                                    <td class="p-3.5 font-mono font-black text-indigo-600 dark:text-indigo-400 whitespace-nowrap" x-text="l.reference_no || ('LTR-' + l.id)"></td>
                                    
                                    <!-- Type -->
                                    <td class="p-3.5 whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-teal-50 dark:bg-teal-900/30 text-[#0F8B8D] dark:text-teal-300 font-bold text-[11px]">
                                            <i class="fa-solid fa-file-lines"></i>
                                            <span x-text="l.letter_type"></span>
                                        </span>
                                    </td>

                                    <!-- Recipient Particulars -->
                                    <td class="p-3.5">
                                        <div class="font-bold text-gray-900 dark:text-white" x-text="l.recipient_name"></div>
                                        <div class="text-[11px] text-gray-400" x-show="l.recipient_designation || l.recipient_organization">
                                            <span x-text="l.recipient_designation"></span>
                                            <span x-show="l.recipient_designation && l.recipient_organization"> • </span>
                                            <span x-text="l.recipient_organization"></span>
                                        </div>
                                        <div class="text-[10px] text-indigo-500 dark:text-indigo-400 mt-0.5" x-show="l.recipient_email">
                                            <i class="fa-solid fa-envelope mr-1"></i> <span x-text="l.recipient_email"></span>
                                        </div>
                                    </td>

                                    <!-- Subject -->
                                    <td class="p-3.5 max-w-xs">
                                        <div class="font-medium text-gray-800 dark:text-gray-200 truncate" :title="l.subject" x-text="l.subject"></div>
                                    </td>

                                    <!-- Issued By -->
                                    <td class="p-3.5 whitespace-nowrap">
                                        <div class="font-medium text-gray-800 dark:text-gray-200" x-text="l.generated_by_name || 'Admin'"></div>
                                        <div class="text-[10px] text-gray-400" x-text="l.generated_by_role || ''"></div>
                                    </td>

                                    <!-- Status -->
                                    <td class="p-3.5 text-center whitespace-nowrap">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider inline-flex items-center gap-1"
                                              :class="{
                                                  'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300': l.status === 'Generated',
                                                  'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300': l.status === 'Sent',
                                                  'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300': l.status === 'Draft',
                                                  'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300': l.status === 'Archived'
                                              }">
                                            <i class="fa-solid text-[9px]" :class="{
                                                'fa-check': l.status === 'Generated',
                                                'fa-paper-plane': l.status === 'Sent',
                                                'fa-pencil': l.status === 'Draft',
                                                'fa-box-archive': l.status === 'Archived'
                                            }"></i>
                                            <span x-text="l.status"></span>
                                        </span>
                                    </td>

                                    <!-- Actions & Re-Download Options -->
                                    <td class="p-3.5 text-center whitespace-nowrap">
                                        <div class="flex items-center justify-center gap-1.5">
                                            
                                            <!-- Prominent Re-Download PDF Button -->
                                            <a :href="'download_letter.php?id=' + l.id + '&download=1'" 
                                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 dark:bg-rose-900/30 dark:hover:bg-rose-900/50 text-rose-700 dark:text-rose-300 font-bold text-xs border border-rose-200 dark:border-rose-800 transition" 
                                               title="Re-download Official Letterhead PDF">
                                                <i class="fa-solid fa-cloud-arrow-down"></i>
                                                <span>Re-Download</span>
                                            </a>

                                            <!-- View Modal -->
                                            <button type="button" @click="openViewModal(l)" class="p-1.5 rounded-lg text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition" title="Quick View Letter">
                                                <i class="fa-solid fa-eye"></i>
                                            </button>

                                            <!-- Print Directly -->
                                            <button type="button" @click="printLetter(l.id)" class="p-1.5 rounded-lg text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700/50 transition" title="Print Letterhead">
                                                <i class="fa-solid fa-print"></i>
                                            </button>

                                            <!-- Email to Recipient -->
                                            <button type="button" @click="openEmailModal(l)" class="p-1.5 rounded-lg text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 transition" title="Email PDF to Recipient">
                                                <i class="fa-solid fa-envelope"></i>
                                            </button>

                                            <!-- Edit in Composer -->
                                            <a :href="'letter_editor.php?id=' + l.id" class="p-1.5 rounded-lg text-teal-600 hover:bg-teal-50 dark:hover:bg-teal-900/30 transition" title="Edit Letter">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>

                                            <!-- Delete -->
                                            <button type="button" @click="confirmDelete(l.id)" class="p-1.5 rounded-lg text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 transition" title="Delete Letter">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <template x-if="filteredLetters.length === 0">
                                <tr>
                                    <td colspan="9" class="p-12 text-center text-xs text-gray-400">
                                        <i class="fa-solid fa-envelope-open-text text-4xl mb-3 text-gray-300 dark:text-gray-600 block"></i>
                                        <p class="font-bold text-gray-600 dark:text-gray-300 text-sm">No Letter Records Found</p>
                                        <p class="mt-1">No generated correspondence matched your search, type, recipient, or date filters.</p>
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
                <div class="px-6 py-4 bg-gray-50/50 dark:bg-gray-700/20 border-t border-slate-100 dark:border-gray-700 flex flex-col sm:flex-row items-center justify-between gap-4" x-show="filteredLetters.length > 0">
                    <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                        <span>Showing <strong class="text-gray-800 dark:text-gray-200" x-text="(page - 1) * perPage + 1"></strong> to <strong class="text-gray-800 dark:text-gray-200" x-text="Math.min(page * perPage, filteredLetters.length)"></strong> of <strong class="text-gray-800 dark:text-gray-200" x-text="filteredLetters.length"></strong> results</span>
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

    <!-- ── QUICK VIEW MODAL ────────────────────────────────────────── -->
    <div x-show="isViewModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm" x-transition.opacity @click.self="closeViewModal()">
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden border border-slate-200 dark:border-gray-700 flex flex-col max-h-[90vh]">
            
            <div class="p-5 border-b border-slate-100 dark:border-gray-700 flex items-center justify-between bg-slate-50 dark:bg-gray-700/50">
                <div class="flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-xl bg-teal-50 dark:bg-teal-900/40 text-[#0F8B8D] flex items-center justify-center text-xs">
                        <i class="fa-solid fa-file-lines"></i>
                    </span>
                    <div>
                        <h4 class="text-sm font-black text-gray-900 dark:text-white" x-text="activeLetter.subject"></h4>
                        <p class="text-[11px] text-gray-400 font-mono" x-text="activeLetter.reference_no"></p>
                    </div>
                </div>
                <button type="button" @click="closeViewModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-white">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <div class="p-6 overflow-y-auto space-y-4 flex-1 text-xs text-gray-700 dark:text-gray-200">
                <div class="grid grid-cols-2 gap-4 pb-4 border-b border-slate-100 dark:border-gray-700">
                    <div>
                        <span class="text-gray-400 font-bold block text-[10px] uppercase">Recipient:</span>
                        <p class="font-bold text-sm text-gray-900 dark:text-white" x-text="activeLetter.recipient_name"></p>
                        <p x-text="activeLetter.recipient_designation"></p>
                        <p x-text="activeLetter.recipient_organization"></p>
                        <p class="text-indigo-600 dark:text-indigo-400 mt-1" x-show="activeLetter.recipient_email">
                            <i class="fa-solid fa-envelope mr-1"></i> <span x-text="activeLetter.recipient_email"></span>
                        </p>
                    </div>
                    <div class="text-right">
                        <span class="text-gray-400 font-bold block text-[10px] uppercase">Issuance Date:</span>
                        <p class="font-bold text-gray-900 dark:text-white" x-text="formatDate(activeLetter.generated_date)"></p>
                        <span class="inline-block px-2 py-0.5 mt-1 rounded-full text-[10px] font-bold bg-teal-100 text-teal-800" x-text="activeLetter.letter_type"></span>
                    </div>
                </div>

                <div>
                    <span class="text-gray-400 font-bold block text-[10px] uppercase mb-1">Letter Body Content:</span>
                    <div class="p-4 rounded-xl bg-slate-50 dark:bg-gray-900/60 border border-slate-200 dark:border-gray-700 whitespace-pre-line leading-relaxed font-sans" x-text="activeLetter.content"></div>
                </div>
            </div>

            <div class="p-4 bg-slate-50 dark:bg-gray-700/50 border-t border-slate-100 dark:border-gray-700 flex flex-wrap justify-between items-center gap-3">
                <a :href="'download_letter.php?id=' + activeLetter.id" target="_blank" class="text-xs text-indigo-600 dark:text-indigo-400 font-bold hover:underline flex items-center gap-1">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i> Open In Browser
                </a>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" @click="printLetter(activeLetter.id)" class="px-3.5 py-2 rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-white font-bold text-xs flex items-center gap-1.5 transition">
                        <i class="fa-solid fa-print"></i> Print
                    </button>
                    <button type="button" @click="closeViewModal(); openEmailModal(activeLetter)" class="px-3.5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm transition">
                        <i class="fa-solid fa-envelope"></i> Email PDF
                    </button>
                    <a :href="'letter_editor.php?id=' + activeLetter.id" class="px-3.5 py-2 rounded-xl bg-slate-200 dark:bg-gray-600 text-gray-800 dark:text-white font-bold text-xs">
                        Edit Letter
                    </a>
                    <a :href="'download_letter.php?id=' + activeLetter.id + '&download=1'" class="px-3.5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm transition">
                        <i class="fa-solid fa-cloud-arrow-down"></i> Re-Download PDF
                    </a>
                </div>
            </div>

        </div>
    </div>

    <!-- ── EMAIL TO RECIPIENT MODAL ─────────────────────────────────── -->
    <div x-show="isEmailModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm" x-transition.opacity @click.self="closeEmailModal()">
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden border border-slate-200 dark:border-gray-700 flex flex-col">
            
            <form action="actions/letter_logic.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="action" value="email_letter">
                <input type="hidden" name="id" :value="emailTargetLetter.id">

                <div class="p-5 border-b border-slate-100 dark:border-gray-700 flex items-center justify-between bg-gradient-to-r from-blue-600 to-indigo-700 text-white">
                    <div class="flex items-center gap-2.5">
                        <span class="w-8 h-8 rounded-xl bg-white/20 flex items-center justify-center text-xs">
                            <i class="fa-solid fa-paper-plane"></i>
                        </span>
                        <div>
                            <h4 class="text-sm font-black">Email Official Letterhead PDF</h4>
                            <p class="text-[11px] text-blue-100 font-mono" x-text="emailTargetLetter.reference_no"></p>
                        </div>
                    </div>
                    <button type="button" @click="closeEmailModal()" class="text-white/70 hover:text-white">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <div class="p-6 space-y-4 text-xs text-gray-700 dark:text-gray-200">
                    <div>
                        <label class="text-[11px] font-bold text-gray-600 dark:text-gray-400 block mb-1">
                            Recipient Name
                        </label>
                        <input type="text" readonly :value="emailTargetLetter.recipient_name" class="w-full text-xs font-semibold p-2.5 border rounded-xl bg-slate-100 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 outline-none cursor-not-allowed">
                    </div>

                    <div>
                        <label class="text-[11px] font-bold text-gray-600 dark:text-gray-400 block mb-1">
                            Recipient Email Address *
                        </label>
                        <div class="relative">
                            <input type="email" name="recipient_email" required x-model="emailForm.email" placeholder="recipient@example.com" class="w-full text-xs font-medium p-2.5 pl-8 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-blue-500">
                            <i class="fa-solid fa-envelope absolute left-3 top-3 text-gray-400 text-xs"></i>
                        </div>
                        <p class="text-[10px] text-gray-400 mt-1">The official letterhead PDF document will be attached to this email.</p>
                    </div>

                    <div>
                        <label class="text-[11px] font-bold text-gray-600 dark:text-gray-400 block mb-1">
                            Letter Subject
                        </label>
                        <input type="text" readonly :value="emailTargetLetter.subject" class="w-full text-xs font-medium p-2.5 border rounded-xl bg-slate-100 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 outline-none cursor-not-allowed">
                    </div>

                    <div>
                        <label class="text-[11px] font-bold text-gray-600 dark:text-gray-400 block mb-1">
                            Additional Personal Message / Note (Optional)
                        </label>
                        <textarea name="custom_message" x-model="emailForm.message" rows="3" placeholder="Add any personalized message or notes to include in the email body..." class="w-full text-xs p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>

                    <div class="p-3 rounded-xl bg-teal-50 dark:bg-teal-900/30 border border-teal-200 dark:border-teal-700/50 flex items-start gap-2.5 text-teal-800 dark:text-teal-300 text-[11px]">
                        <i class="fa-solid fa-circle-info text-xs mt-0.5"></i>
                        <span>Dispatching will send the high-resolution PDF generated via institutional template and automatically mark letter status as <strong>Sent</strong>.</span>
                    </div>
                </div>

                <div class="p-4 bg-slate-50 dark:bg-gray-700/50 border-t border-slate-100 dark:border-gray-700 flex justify-end items-center gap-2">
                    <button type="button" @click="closeEmailModal()" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 font-bold text-xs hover:bg-slate-100 dark:hover:bg-gray-600 transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs flex items-center gap-2 shadow-md transition">
                        <i class="fa-solid fa-paper-plane"></i> Send Email Now
                    </button>
                </div>
            </form>

        </div>
    </div>

    <!-- ── HIDDEN DELETE FORM ──────────────────────────────────────── -->
    <form id="deleteLetterForm" action="actions/letter_logic.php" method="POST" class="hidden">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <input type="hidden" name="action" value="delete_letter">
        <input type="hidden" name="id" id="deleteLetterId">
    </form>

</div>

<script>
    function lettersDirectory() {
        return {
            items: <?php echo json_encode($letters); ?>,
            search: '',
            recipientFilter: '',
            typeFilter: '',
            dateFilter: 'all',
            startDate: '',
            endDate: '',
            statusFilter: '',
            
            isViewModalOpen: false,
            activeLetter: {},

            isEmailModalOpen: false,
            emailTargetLetter: {},
            emailForm: {
                email: '',
                message: ''
            },

            page: 1,
            perPage: 10,

            get totalPages() {
                return Math.ceil(this.filteredLetters.length / this.perPage) || 1;
            },

            get paginatedLetters() {
                const start = (this.page - 1) * this.perPage;
                return this.filteredLetters.slice(start, start + this.perPage);
            },

            setPage(p) {
                if (p >= 1 && p <= this.totalPages) this.page = p;
            },

            letterTypes: [
                'Appointment Letter',
                'Appreciation Letter',
                'Experience Certificate',
                'Donation / CSR Request',
                'Recommendation Letter',
                'Relieving Letter',
                'Official Notice',
                'General Official Letter'
            ],

            init() {},

            countByType(type) {
                if (!this.items) return 0;
                return this.items.filter(l => l.letter_type === type).length;
            },

            get hasActiveFilters() {
                return !!(this.search || this.recipientFilter || this.typeFilter || this.dateFilter !== 'all' || this.startDate || this.endDate || this.statusFilter);
            },

            resetFilters() {
                this.search = '';
                this.recipientFilter = '';
                this.typeFilter = '';
                this.dateFilter = 'all';
                this.startDate = '';
                this.endDate = '';
                this.statusFilter = '';
            },

            openViewModal(letter) {
                this.activeLetter = letter;
                this.isViewModalOpen = true;
            },

            closeViewModal() {
                this.isViewModalOpen = false;
            },

            openEmailModal(letter) {
                this.emailTargetLetter = letter;
                this.emailForm.email = letter.recipient_email || '';
                this.emailForm.message = '';
                this.isEmailModalOpen = true;
            },

            closeEmailModal() {
                this.isEmailModalOpen = false;
            },

            printLetter(id) {
                const printWindow = window.open('download_letter.php?id=' + id, '_blank');
                if (printWindow) {
                    printWindow.focus();
                }
            },

            confirmDelete(id) {
                if (confirm('Are you sure you want to delete this letter record and its generated document?')) {
                    document.getElementById('deleteLetterId').value = id;
                    document.getElementById('deleteLetterForm').submit();
                }
            },

            formatDate(d) {
                if (!d) return '-';
                const parts = d.split('-');
                if (parts.length === 3) {
                    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                    const mIdx = parseInt(parts[1], 10) - 1;
                    return `${parts[2]} ${months[mIdx] || parts[1]}, ${parts[0]}`;
                }
                return d;
            },

            get filteredLetters() {
                return this.items.filter(l => {
                    // General Search
                    const s = this.search.toLowerCase().trim();
                    const matchesSearch = !s ||
                        (l.reference_no && l.reference_no.toLowerCase().includes(s)) ||
                        (l.recipient_name && l.recipient_name.toLowerCase().includes(s)) ||
                        (l.recipient_organization && l.recipient_organization.toLowerCase().includes(s)) ||
                        (l.recipient_designation && l.recipient_designation.toLowerCase().includes(s)) ||
                        (l.recipient_email && l.recipient_email.toLowerCase().includes(s)) ||
                        (l.subject && l.subject.toLowerCase().includes(s)) ||
                        (l.generated_by_name && l.generated_by_name.toLowerCase().includes(s));

                    // Recipient specific search
                    const r = this.recipientFilter.toLowerCase().trim();
                    const matchesRecipient = !r ||
                        (l.recipient_name && l.recipient_name.toLowerCase().includes(r)) ||
                        (l.recipient_organization && l.recipient_organization.toLowerCase().includes(r)) ||
                        (l.recipient_designation && l.recipient_designation.toLowerCase().includes(r)) ||
                        (l.recipient_email && l.recipient_email.toLowerCase().includes(r));

                    // Type & Status
                    const matchesType = !this.typeFilter || (l.letter_type === this.typeFilter);
                    const matchesStatus = !this.statusFilter || (l.status === this.statusFilter);

                    // Date Period
                    let matchesDate = true;
                    if (this.dateFilter === 'today') {
                        const today = new Date().toISOString().slice(0, 10);
                        matchesDate = l.generated_date === today;
                    } else if (this.dateFilter === 'this_month') {
                        const curYm = new Date().toISOString().slice(0, 7);
                        matchesDate = l.generated_date && l.generated_date.startsWith(curYm);
                    } else if (this.dateFilter === 'last_month') {
                        const d = new Date();
                        d.setMonth(d.getMonth() - 1);
                        const prevYm = d.toISOString().slice(0, 7);
                        matchesDate = l.generated_date && l.generated_date.startsWith(prevYm);
                    } else if (this.dateFilter === 'this_year') {
                        const curY = new Date().getFullYear().toString();
                        matchesDate = l.generated_date && l.generated_date.startsWith(curY);
                    } else if (this.dateFilter === 'custom') {
                        if (this.startDate && l.generated_date < this.startDate) matchesDate = false;
                        if (this.endDate && l.generated_date > this.endDate) matchesDate = false;
                    }

                    return matchesSearch && matchesRecipient && matchesType && matchesStatus && matchesDate;
                });
            }
        };
    }
</script>

<?php require 'includes/footer.php'; ?>
