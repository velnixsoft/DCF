<?php
// ============================================================
// admin/staff_letters.php
// Staff, Employee & Volunteer Letters Directory & Generator
// Built on Letter Head Management (Module 19) Engine
// ============================================================

require 'includes/header.php';
require '../config/db.php';
require_once '../includes/functions.php';
require_once '../includes/letterhead_helper.php';
require_once '../includes/staff_letter_helper.php';

if (!canAccessModule($pdo, 'coordinator', 'page.letters')) {
    setFlash('error', 'Access denied. You do not have permission to access staff letters.');
    header('Location: dashboard.php');
    exit;
}

$csrfToken = generateCsrfToken();
$lhSettings = get_letterhead_settings($pdo);

// 1. Fetch All Staff Letters
$letters = [];
try {
    $letters = $pdo->query("SELECT * FROM staff_letters ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $letters = [];
}

// 2. Compute Metric Counters
$totalLetters = count($letters);
$offerCount = 0;
$appointmentCount = 0;
$joiningCount = 0;
$volunteerCount = 0;

foreach ($letters as $l) {
    if ($l['type'] === 'offer') $offerCount++;
    elseif ($l['type'] === 'appointment') $appointmentCount++;
    elseif ($l['type'] === 'joining') $joiningCount++;
    elseif ($l['type'] === 'volunteer_joining') $volunteerCount++;
}
?>

<div class="flex h-screen overflow-hidden bg-slate-50 dark:bg-gray-900" x-data="staffLettersApp">

    <?php require 'includes/sidebar.php'; ?>

    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300 min-w-0">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-8">

            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div>
                    <div class="flex items-center gap-3">
                        <span class="w-10 h-10 rounded-2xl bg-teal-50 dark:bg-teal-900/40 text-[#0F8B8D] flex items-center justify-center text-lg shadow-sm">
                            <i class="fa-solid fa-envelope-open-text"></i>
                        </span>
                        <div>
                            <h3 class="text-2xl md:text-3xl font-black text-gray-800 dark:text-white">
                                Staff & Volunteer Letters
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                Generate official Letterhead Offer Letters, Appointment Letters, Joining Letters, and Volunteer Onboarding Letters.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <a href="letters.php" class="bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 hover:bg-slate-50 dark:hover:bg-gray-700 font-bold px-3.5 py-2.5 rounded-2xl shadow-sm text-xs flex items-center gap-1.5 transition">
                        <i class="fa-solid fa-folder-open text-[#0F8B8D]"></i> General Letters
                    </a>
                    <a href="staff_letter_composer.php?type=offer" class="bg-[#0F8B8D] hover:bg-[#0c7274] text-white font-bold py-2.5 px-4 rounded-2xl shadow-md text-xs flex items-center gap-1.5 transition transform active:scale-95">
                        <i class="fa-solid fa-plus"></i> Compose Staff Letter
                    </a>
                </div>
            </div>

            <!-- Flash Alerts -->
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

            <!-- 4 Metric KPI Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <!-- Total Letters -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase text-gray-400">Total Letters</span>
                        <span class="w-9 h-9 rounded-xl bg-teal-50 dark:bg-teal-900/30 text-[#0F8B8D] flex items-center justify-center text-sm">
                            <i class="fa-solid fa-envelope"></i>
                        </span>
                    </div>
                    <div class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white mt-2"><?php echo $totalLetters; ?></div>
                    <div class="text-[11px] text-gray-500 mt-1">Issued staff & volunteer records</div>
                </div>

                <!-- Offer Letters -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase text-blue-600">Offer Letters</span>
                        <span class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 flex items-center justify-center text-sm">
                            <i class="fa-solid fa-file-lines"></i>
                        </span>
                    </div>
                    <div class="text-2xl sm:text-3xl font-black text-blue-600 dark:text-blue-400 mt-2"><?php echo $offerCount; ?></div>
                    <div class="text-[11px] text-gray-500 mt-1">Formal employment proposals</div>
                </div>

                <!-- Appointment Letters -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase text-emerald-600">Appointment Letters</span>
                        <span class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 flex items-center justify-center text-sm">
                            <i class="fa-solid fa-file-signature"></i>
                        </span>
                    </div>
                    <div class="text-2xl sm:text-3xl font-black text-emerald-600 dark:text-emerald-400 mt-2"><?php echo $appointmentCount; ?></div>
                    <div class="text-[11px] text-gray-500 mt-1">Confirmed appointments</div>
                </div>

                <!-- Volunteer Joining -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase text-pink-500">Volunteer Joining</span>
                        <span class="w-9 h-9 rounded-xl bg-pink-50 dark:bg-pink-900/30 text-pink-500 flex items-center justify-center text-sm">
                            <i class="fa-solid fa-hand-holding-heart"></i>
                        </span>
                    </div>
                    <div class="text-2xl sm:text-3xl font-black text-pink-600 dark:text-pink-400 mt-2"><?php echo $volunteerCount; ?></div>
                    <div class="text-[11px] text-gray-500 mt-1">Community volunteers onboarded</div>
                </div>
            </div>

            <!-- Quick Template Launch Bar -->
            <div class="bg-white dark:bg-gray-800 p-4 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm mb-6 flex flex-wrap items-center justify-between gap-3">
                <span class="text-xs font-bold text-gray-500 flex items-center gap-1.5">
                    <i class="fa-solid fa-bolt text-amber-500"></i> Quick Generate Template:
                </span>
                
                <div class="flex flex-wrap items-center gap-2">
                    <a href="staff_letter_composer.php?type=offer" class="px-3 py-1.5 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 hover:bg-blue-100 font-bold text-xs transition inline-flex items-center gap-1">
                        <i class="fa-solid fa-plus text-[10px]"></i> Offer Letter
                    </a>
                    <a href="staff_letter_composer.php?type=appointment" class="px-3 py-1.5 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 font-bold text-xs transition inline-flex items-center gap-1">
                        <i class="fa-solid fa-plus text-[10px]"></i> Appointment Letter
                    </a>
                    <a href="staff_letter_composer.php?type=joining" class="px-3 py-1.5 rounded-xl bg-teal-50 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300 hover:bg-teal-100 font-bold text-xs transition inline-flex items-center gap-1">
                        <i class="fa-solid fa-plus text-[10px]"></i> Joining Letter
                    </a>
                    <a href="staff_letter_composer.php?type=volunteer_joining" class="px-3 py-1.5 rounded-xl bg-pink-50 dark:bg-pink-900/30 text-pink-700 dark:text-pink-300 hover:bg-pink-100 font-bold text-xs transition inline-flex items-center gap-1">
                        <i class="fa-solid fa-plus text-[10px]"></i> Volunteer Joining
                    </a>
                    <a href="staff_letter_composer.php?type=experience" class="px-3 py-1.5 rounded-xl bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 hover:bg-purple-100 font-bold text-xs transition inline-flex items-center gap-1">
                        <i class="fa-solid fa-plus text-[10px]"></i> Experience Certificate
                    </a>
                    <a href="staff_letter_composer.php?type=relieving" class="px-3 py-1.5 rounded-xl bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 hover:bg-amber-100 font-bold text-xs transition inline-flex items-center gap-1">
                        <i class="fa-solid fa-plus text-[10px]"></i> Relieving Letter
                    </a>
                </div>
            </div>

            <!-- Table Container -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl border border-slate-200 dark:border-gray-700 shadow-sm overflow-hidden">
                
                <!-- Table Search & Filter Bar -->
                <div class="p-5 border-b border-slate-100 dark:border-gray-700 bg-slate-50/50 dark:bg-gray-900/30">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        
                        <!-- Search Keyword -->
                        <div class="relative lg:col-span-2">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <i class="fa-solid fa-magnifying-glass text-xs"></i>
                            </span>
                            <input type="text" x-model="searchQuery"
                                   placeholder="Search by recipient name, ref code, designation, department..."
                                   class="w-full pl-9 pr-4 py-2.5 bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-2xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                        </div>

                        <!-- Type Filter -->
                        <div>
                            <select x-model="typeFilter" class="w-full px-3 py-2.5 bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-2xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                                <option value="">All Letter Types</option>
                                <option value="offer">Offer Letter</option>
                                <option value="appointment">Appointment Letter</option>
                                <option value="joining">Joining Letter</option>
                                <option value="volunteer_joining">Volunteer Joining</option>
                                <option value="experience">Experience Letter</option>
                                <option value="relieving">Relieving Letter</option>
                                <option value="appreciation">Appreciation Letter</option>
                            </select>
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <select x-model="statusFilter" class="w-full px-3 py-2.5 bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-2xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                                <option value="">All Statuses</option>
                                <option value="draft">Draft</option>
                                <option value="issued">Issued</option>
                                <option value="accepted">Accepted</option>
                                <option value="signed">Signed</option>
                            </select>
                        </div>

                    </div>
                </div>

                <!-- Table Content -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-gray-700 bg-slate-50/75 dark:bg-gray-800 text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                                <th class="py-3.5 px-4 sm:px-6">Letter Ref & Type</th>
                                <th class="py-3.5 px-4">Recipient Particulars</th>
                                <th class="py-3.5 px-4">Designation & Wing</th>
                                <th class="py-3.5 px-4">Timeline & Salary</th>
                                <th class="py-3.5 px-4">Status</th>
                                <th class="py-3.5 px-4 sm:px-6 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-gray-700 text-xs text-gray-700 dark:text-gray-300">
                            <template x-for="item in paginatedLetters" :key="item.id">
                                <tr class="hover:bg-slate-50/80 dark:hover:bg-gray-700/40 transition">
                                    
                                    <!-- Ref & Type -->
                                    <td class="py-4 px-4 sm:px-6 whitespace-nowrap">
                                        <div class="font-mono font-bold text-[#0F8B8D] dark:text-teal-400" x-text="item.letter_no"></div>
                                        <div class="mt-1">
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                                  :class="getTypeBadgeClass(item.type)"
                                                  x-text="formatTypeLabel(item.type)"></span>
                                        </div>
                                    </td>

                                    <!-- Recipient -->
                                    <td class="py-4 px-4">
                                        <div class="font-bold text-gray-900 dark:text-white" x-text="item.name"></div>
                                        <div class="text-[11px] text-gray-500 mt-0.5" x-text="item.email || item.contact || 'No contact provided'"></div>
                                    </td>

                                    <!-- Designation & Department -->
                                    <td class="py-4 px-4">
                                        <div class="font-semibold text-gray-800 dark:text-gray-200" x-text="item.designation"></div>
                                        <div class="text-[11px] text-gray-400" x-text="item.department || 'General Administration'"></div>
                                    </td>

                                    <!-- Timeline & Salary -->
                                    <td class="py-4 px-4 whitespace-nowrap text-[11px]">
                                        <div>Issued: <strong class="text-gray-800 dark:text-gray-200" x-text="formatDate(item.issued_date)"></strong></div>
                                        <div class="text-gray-500 mt-0.5">Start: <span x-text="item.joining_date ? formatDate(item.joining_date) : 'Immediate'"></span></div>
                                        <div class="text-[#0F8B8D] font-bold mt-0.5" x-text="item.salary_or_stipend || 'Honorary'"></div>
                                    </td>

                                    <!-- Status -->
                                    <td class="py-4 px-4 whitespace-nowrap">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                              :class="getStatusBadgeClass(item.status)"
                                              x-text="item.status"></span>
                                    </td>

                                    <!-- Actions -->
                                    <td class="py-4 px-4 sm:px-6 whitespace-nowrap text-right space-x-1.5">
                                        
                                        <!-- View / Print PDF -->
                                        <a :href="'actions/staff_letter_logic.php?action=view_letter&id=' + item.id" target="_blank"
                                           class="px-2.5 py-1.5 rounded-xl bg-teal-50 hover:bg-teal-100 dark:bg-teal-900/30 text-[#0F8B8D] dark:text-teal-300 font-bold text-[11px] transition inline-flex items-center gap-1 border border-teal-200 dark:border-teal-800"
                                           title="View Official Letterhead PDF">
                                            <i class="fa-solid fa-file-pdf"></i>
                                            <span>View PDF</span>
                                        </a>

                                        <!-- Download PDF -->
                                        <a :href="'actions/staff_letter_logic.php?action=download_letter&id=' + item.id"
                                           class="p-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-xs transition inline-block"
                                           title="Download PDF">
                                            <i class="fa-solid fa-download"></i>
                                        </a>

                                        <!-- Email Letter -->
                                        <a :href="'actions/staff_letter_logic.php?action=email_letter&id=' + item.id + '&csrf_token=<?php echo htmlspecialchars($csrfToken); ?>'"
                                           class="p-1.5 rounded-xl text-sky-600 hover:bg-sky-50 dark:hover:bg-sky-900/30 text-xs transition inline-block"
                                           title="Email PDF to Recipient"
                                           onclick="return confirm('Send official letterhead PDF via email to recipient?');">
                                            <i class="fa-solid fa-paper-plane"></i>
                                        </a>

                                        <!-- Edit -->
                                        <a :href="'staff_letter_composer.php?id=' + item.id"
                                           class="p-1.5 rounded-xl text-gray-600 hover:bg-slate-100 dark:text-gray-300 text-xs transition inline-block"
                                           title="Edit Letter">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>

                                        <!-- Delete -->
                                        <form action="actions/staff_letter_logic.php" method="POST" class="inline-block" onsubmit="return confirm('Delete this staff letter permanently?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                            <input type="hidden" name="action" value="delete_letter">
                                            <input type="hidden" name="id" :value="item.id">
                                            <button type="submit" class="p-1.5 rounded-xl text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/30 text-xs transition" title="Delete Letter">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>

                                    </td>

                                </tr>
                            </template>

                            <template x-if="filteredLetters.length === 0">
                                <tr>
                                    <td colspan="6" class="py-12 text-center text-gray-400">
                                        <i class="fa-solid fa-envelope-open-text text-3xl mb-2 block opacity-40"></i>
                                        <span>No staff letters found matching your search.</span>
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
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('staffLettersApp', () => ({
        searchQuery: '',
        typeFilter: '',
        statusFilter: '',
        page: 1,
        perPage: 10,
        allLetters: <?php echo json_encode($letters); ?>,

        get totalPages() {
            return Math.max(1, Math.ceil(this.filteredLetters.length / this.perPage));
        },

        get paginatedLetters() {
            const start = (this.page - 1) * this.perPage;
            return this.filteredLetters.slice(start, start + this.perPage);
        },

        setPage(p) {
            if (p >= 1 && p <= this.totalPages) {
                this.page = p;
            }
        },

        get filteredLetters() {
            return this.allLetters.filter(l => {
                const q = (this.searchQuery || '').toLowerCase().trim();
                const matchesSearch = !q ||
                    (l.letter_no && l.letter_no.toLowerCase().includes(q)) ||
                    (l.name && l.name.toLowerCase().includes(q)) ||
                    (l.designation && l.designation.toLowerCase().includes(q)) ||
                    (l.department && l.department.toLowerCase().includes(q)) ||
                    (l.subject && l.subject.toLowerCase().includes(q));

                const matchesType = !this.typeFilter || l.type === this.typeFilter;
                const matchesStatus = !this.statusFilter || l.status === this.statusFilter;

                return matchesSearch && matchesType && matchesStatus;
            });
        },

        formatDate(dateStr) {
            if (!dateStr) return '—';
            const d = new Date(dateStr);
            return isNaN(d.getTime()) ? dateStr : d.toLocaleDateString('en-US', { day: '2-digit', month: 'short', year: 'numeric' });
        },

        formatTypeLabel(t) {
            const map = {
                'offer': 'Offer Letter',
                'appointment': 'Appointment',
                'joining': 'Joining Letter',
                'volunteer_joining': 'Volunteer Joining',
                'experience': 'Experience',
                'appreciation': 'Appreciation'
            };
            return map[t] || t;
        },

        getTypeBadgeClass(t) {
            const map = {
                'offer': 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
                'appointment': 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
                'joining': 'bg-teal-100 text-teal-800 dark:bg-teal-900/40 dark:text-teal-300',
                'volunteer_joining': 'bg-pink-100 text-pink-800 dark:bg-pink-900/40 dark:text-pink-300',
                'experience': 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300',
                'appreciation': 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300'
            };
            return map[t] || 'bg-gray-100 text-gray-800';
        },

        getStatusBadgeClass(s) {
            const map = {
                'draft': 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                'issued': 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
                'accepted': 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
                'signed': 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300',
                'cancelled': 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300'
            };
            return map[s] || 'bg-gray-100 text-gray-800';
        }
    }));
});
</script>

<?php require 'includes/footer.php'; ?>
