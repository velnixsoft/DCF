<?php
// ============================================================
// admin/custom_receipts.php
// Custom Receipts History, Search, Filter & PDF Re-download
// ============================================================

require 'includes/header.php';
require '../config/db.php';
require_once '../includes/member_module.php';
require_once '../includes/custom_receipt_helper.php';
require_once '../includes/functions.php';

if (!canAccessModule($pdo, 'coordinator', 'page.donations') && !canAccessModule($pdo, 'coordinator', 'page.expenses')) {
    setFlash('error', 'Access denied.');
    header('Location: dashboard.php');
    exit;
}

$csrfToken = generateCsrfToken();

// Fetch all custom receipts
$stmt = $pdo->query("
    SELECT r.*, u.name AS generated_by_name 
    FROM `custom_receipts` r 
    LEFT JOIN `users` u ON r.generated_by = u.id 
    ORDER BY r.id DESC
");
$receipts = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

// Real-time statistics
$totalCount = count($receipts);
$totalAmount = 0;
$cashCount = 0;
$digitalCount = 0;

foreach ($receipts as $r) {
    $totalAmount += (float)$r['amount'];
    if (stripos($r['payment_mode'], 'Cash') !== false) {
        $cashCount++;
    } else {
        $digitalCount++;
    }
}
?>

<div class="flex h-screen overflow-hidden bg-slate-50 dark:bg-gray-900" x-data="customReceiptsHistoryApp()">
    <?php require 'includes/sidebar.php'; ?>

    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-8 space-y-6">
            
            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="w-10 h-10 rounded-2xl bg-[#0F8B8D]/10 text-[#0F8B8D] flex items-center justify-center text-lg shadow-sm">
                            <i class="fa-solid fa-file-invoice-dollar"></i>
                        </span>
                        <div>
                            <h3 class="text-2xl font-black text-gray-900 dark:text-white tracking-tight">Custom Receipts Directory</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Manage, track, view and re-download generated custom payment & donation receipts.</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <a href="generate_custom_receipt.php" class="px-5 py-2.5 rounded-xl bg-[#0F8B8D] hover:bg-[#0c7274] text-white text-xs font-bold transition flex items-center gap-1.5 shadow-md shadow-teal-700/20">
                        <i class="fa-solid fa-plus"></i> Generate New Receipt
                    </a>
                </div>
            </div>

            <!-- 4 Metric KPI Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Receipts -->
                <div class="p-5 rounded-3xl bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-gray-400 font-bold block text-[10px] uppercase">Total Receipts</span>
                        <h4 class="text-2xl font-black text-gray-900 dark:text-white mt-1"><?php echo number_format($totalCount); ?></h4>
                    </div>
                    <span class="w-11 h-11 rounded-2xl bg-teal-50 dark:bg-teal-900/30 text-[#0F8B8D] flex items-center justify-center text-lg">
                        <i class="fa-solid fa-receipt"></i>
                    </span>
                </div>

                <!-- Total Amount Issued -->
                <div class="p-5 rounded-3xl bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-gray-400 font-bold block text-[10px] uppercase">Total Collection</span>
                        <h4 class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">₹ <?php echo number_format($totalAmount, 2); ?></h4>
                    </div>
                    <span class="w-11 h-11 rounded-2xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-indian-rupee-sign"></i>
                    </span>
                </div>

                <!-- Cash Mode -->
                <div class="p-5 rounded-3xl bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-gray-400 font-bold block text-[10px] uppercase">Cash Payments</span>
                        <h4 class="text-2xl font-black text-indigo-600 dark:text-indigo-400 mt-1"><?php echo number_format($cashCount); ?></h4>
                    </div>
                    <span class="w-11 h-11 rounded-2xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-money-bill-wave"></i>
                    </span>
                </div>

                <!-- Digital / UPI Mode -->
                <div class="p-5 rounded-3xl bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-gray-400 font-bold block text-[10px] uppercase">Digital / UPI</span>
                        <h4 class="text-2xl font-black text-purple-600 dark:text-purple-400 mt-1"><?php echo number_format($digitalCount); ?></h4>
                    </div>
                    <span class="w-11 h-11 rounded-2xl bg-purple-50 dark:bg-purple-900/30 text-purple-600 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-qrcode"></i>
                    </span>
                </div>
            </div>

            <!-- Search & Filters Bar -->
            <div class="p-4 bg-white dark:bg-gray-800 rounded-3xl border border-slate-200 dark:border-gray-700 shadow-sm space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3">
                    <!-- Search Input -->
                    <div class="lg:col-span-6">
                        <label class="text-[11px] font-bold text-gray-600 dark:text-gray-400 block mb-1">
                            <i class="fa-solid fa-magnifying-glass text-teal-600 mr-1"></i> Search Receipts
                        </label>
                        <input type="text" x-model="search" placeholder="Search by Receipt No, Payer Name, Phone, Email, PAN, Txn Ref..." 
                               class="w-full text-xs font-medium p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                    </div>

                    <!-- Payment Mode Filter -->
                    <div class="lg:col-span-3">
                        <label class="text-[11px] font-bold text-gray-600 dark:text-gray-400 block mb-1">
                            <i class="fa-solid fa-wallet text-indigo-600 mr-1"></i> Payment Mode
                        </label>
                        <select x-model="modeFilter" class="w-full text-xs font-medium p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                            <option value="">All Payment Modes</option>
                            <option value="Cash">Cash</option>
                            <option value="UPI">UPI / QR Code</option>
                            <option value="Bank">Bank Transfer</option>
                            <option value="Cheque">Cheque</option>
                            <option value="Online">Online Gateway</option>
                        </select>
                    </div>

                    <!-- Date Range Filter -->
                    <div class="lg:col-span-3">
                        <label class="text-[11px] font-bold text-gray-600 dark:text-gray-400 block mb-1">
                            <i class="fa-solid fa-calendar-days text-teal-600 mr-1"></i> Date Filter
                        </label>
                        <select x-model="dateFilter" class="w-full text-xs font-medium p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                            <option value="all">All Time</option>
                            <option value="today">Today</option>
                            <option value="this_month">This Month</option>
                            <option value="last_month">Last Month</option>
                            <option value="this_year">This Year (<?php echo date('Y'); ?>)</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs pt-1" x-show="hasActiveFilters">
                    <span class="text-gray-500" x-text="'Showing ' + filteredReceipts.length + ' matching receipts'"></span>
                    <button type="button" @click="resetFilters()" class="text-rose-600 dark:text-rose-400 font-bold hover:underline flex items-center gap-1">
                        <i class="fa-solid fa-rotate-left"></i> Reset Filters
                    </button>
                </div>
            </div>

            <!-- Receipts Table -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl border border-slate-200 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                        <thead class="bg-slate-50 dark:bg-gray-700/50 text-[11px] font-bold uppercase text-gray-500 dark:text-gray-400 border-b border-slate-200 dark:border-gray-700">
                            <tr>
                                <th class="p-3.5 text-center">#</th>
                                <th class="p-3.5">Receipt No</th>
                                <th class="p-3.5">Payer Particulars</th>
                                <th class="p-3.5">Amount (INR)</th>
                                <th class="p-3.5">Purpose / Head</th>
                                <th class="p-3.5">Mode & Ref</th>
                                <th class="p-3.5">Date</th>
                                <th class="p-3.5">Issued By</th>
                                <th class="p-3.5 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-gray-700">
                            <template x-for="(r, idx) in paginatedReceipts" :key="r.id">
                                <tr class="hover:bg-slate-50/70 dark:hover:bg-gray-700/30 transition">
                                    <td class="p-3.5 text-center font-bold text-gray-400" x-text="(page - 1) * perPage + idx + 1"></td>
                                    
                                    <!-- Receipt No -->
                                    <td class="p-3.5 font-mono font-black text-teal-700 dark:text-teal-400 whitespace-nowrap">
                                        <a :href="'download_custom_receipt.php?id=' + r.id" target="_blank" class="hover:underline flex items-center gap-1">
                                            <span x-text="r.receipt_no"></span>
                                            <i class="fa-solid fa-arrow-up-right-from-square text-[9px] text-gray-400"></i>
                                        </a>
                                    </td>

                                    <!-- Payer Particulars -->
                                    <td class="p-3.5">
                                        <div class="font-bold text-gray-900 dark:text-white" x-text="r.payer_name"></div>
                                        <div class="text-[11px] text-gray-500 flex items-center gap-1 mt-0.5" x-show="r.payer_phone">
                                            <i class="fa-solid fa-phone text-[9px] text-gray-400"></i>
                                            <span x-text="r.payer_phone"></span>
                                        </div>
                                        <div class="text-[10px] text-gray-400 truncate max-w-[150px]" x-show="r.payer_email" x-text="r.payer_email"></div>
                                        <div class="text-[10px] font-mono font-bold text-indigo-600 dark:text-indigo-400" x-show="r.payer_pan" x-text="'PAN: ' + r.payer_pan"></div>
                                    </td>

                                    <!-- Amount -->
                                    <td class="p-3.5 whitespace-nowrap font-black text-sm text-gray-900 dark:text-white">
                                        <span x-text="'₹ ' + Number(r.amount || 0).toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>
                                    </td>

                                    <!-- Purpose -->
                                    <td class="p-3.5 max-w-xs">
                                        <div class="font-semibold text-gray-800 dark:text-gray-200 truncate" :title="r.purpose" x-text="r.purpose"></div>
                                        <div class="text-[10px] text-gray-400 truncate max-w-[200px]" x-show="r.remarks" :title="r.remarks" x-text="r.remarks"></div>
                                    </td>

                                    <!-- Payment Mode & Ref -->
                                    <td class="p-3.5 whitespace-nowrap">
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-slate-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200" x-text="r.payment_mode"></span>
                                        <div class="text-[10px] font-mono text-gray-400 mt-0.5 truncate max-w-[120px]" x-show="r.transaction_ref" x-text="r.transaction_ref"></div>
                                    </td>

                                    <!-- Date -->
                                    <td class="p-3.5 whitespace-nowrap font-medium text-gray-700 dark:text-gray-300" x-text="formatDate(r.date)"></td>

                                    <!-- Generated By -->
                                    <td class="p-3.5 whitespace-nowrap text-gray-500" x-text="r.generated_by_name || 'System Admin'"></td>

                                    <!-- Actions -->
                                    <td class="p-3.5 text-center whitespace-nowrap">
                                        <div class="flex items-center justify-center gap-1.5">
                                            
                                            <!-- Print / View PDF -->
                                            <button type="button" @click="printReceipt(r.id)" 
                                                    class="px-2.5 py-1.5 rounded-xl bg-teal-50 hover:bg-teal-100 dark:bg-teal-900/30 text-[#0F8B8D] dark:text-teal-300 font-bold text-xs flex items-center gap-1 border border-teal-200 dark:border-teal-800 transition" 
                                                    title="Print Receipt PDF">
                                                <i class="fa-solid fa-print"></i>
                                                <span class="hidden sm:inline">Print</span>
                                            </button>

                                            <!-- Download PDF -->
                                            <a :href="'download_custom_receipt.php?id=' + r.id + '&download=1'" 
                                               class="p-1.5 rounded-xl text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition" 
                                               title="Download PDF File">
                                                <i class="fa-solid fa-download"></i>
                                            </a>

                                            <!-- Email to Recipient -->
                                            <button type="button" @click="openEmailModal(r)" 
                                                    class="p-1.5 rounded-xl text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 transition" 
                                                    title="Email PDF Receipt to Payer">
                                                <i class="fa-solid fa-envelope"></i>
                                            </button>

                                            <!-- WhatsApp Share -->
                                            <button type="button" @click="shareWhatsApp(r)" 
                                                    class="p-1.5 rounded-xl text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 transition" 
                                                    title="Share on WhatsApp">
                                                <i class="fa-brands fa-whatsapp text-sm"></i>
                                            </button>

                                            <!-- Delete -->
                                            <button type="button" @click="confirmDelete(r.id, r.receipt_no)" 
                                                    class="p-1.5 rounded-xl text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/30 transition" 
                                                    title="Delete Receipt">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>

                                        </div>
                                    </td>

                                </tr>
                            </template>

                            <template x-if="filteredReceipts.length === 0">
                                <tr>
                                    <td colspan="9" class="p-12 text-center text-xs text-gray-400">
                                        <i class="fa-solid fa-receipt text-4xl mb-3 text-gray-300 dark:text-gray-600 block"></i>
                                        <p class="font-bold text-gray-600 dark:text-gray-300 text-sm">No Custom Receipts Found</p>
                                        <p class="mt-1">Generate a receipt using the top button or clear your filter criteria.</p>
                                        <a href="generate_custom_receipt.php" class="mt-3 inline-flex items-center gap-1 px-4 py-2 bg-[#0F8B8D] text-white rounded-xl font-bold text-xs shadow-md transition">
                                            <i class="fa-solid fa-plus"></i> Generate First Custom Receipt
                                        </a>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div class="px-6 py-4 bg-gray-50/50 dark:bg-gray-700/20 border-t border-slate-100 dark:border-gray-700 flex flex-col sm:flex-row items-center justify-between gap-4" x-show="filteredReceipts.length > 0">
                    <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                        <span>Showing <strong class="text-gray-800 dark:text-gray-200" x-text="(page - 1) * perPage + 1"></strong> to <strong class="text-gray-800 dark:text-gray-200" x-text="Math.min(page * perPage, filteredReceipts.length)"></strong> of <strong class="text-gray-800 dark:text-gray-200" x-text="filteredReceipts.length"></strong> results</span>
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
    <!-- EMAIL RECEIPT MODAL                                          -->
    <!-- ════════════════════════════════════════════════════════════ -->
    <div x-show="isEmailModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm" x-transition.opacity @click.self="isEmailModalOpen = false">
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-200 dark:border-gray-700">
            <div class="p-5 border-b border-slate-100 dark:border-gray-700 bg-gradient-to-r from-slate-900 to-slate-800 text-white flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-xl bg-blue-500/20 text-blue-300 flex items-center justify-center text-xs">
                        <i class="fa-solid fa-paper-plane"></i>
                    </span>
                    <div>
                        <h4 class="text-sm font-black">Email Receipt PDF</h4>
                        <p class="text-[11px] text-slate-400" x-text="'Receipt: #' + activeReceipt?.receipt_no"></p>
                    </div>
                </div>
                <button type="button" @click="isEmailModalOpen = false" class="text-white/70 hover:text-white">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form @submit.prevent="sendEmailReceipt()" class="p-6 space-y-4 text-xs">
                <div>
                    <label class="block text-gray-400 font-bold uppercase text-[10px] mb-1">Payer Name:</label>
                    <p class="font-bold text-gray-900 dark:text-white text-sm" x-text="activeReceipt?.payer_name"></p>
                </div>

                <div>
                    <label class="block text-gray-700 dark:text-gray-300 font-bold mb-1">Recipient Email Address *</label>
                    <input type="email" x-model="emailForm.email" required placeholder="payer@example.com" 
                           class="w-full text-xs font-semibold p-3 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                    <p class="text-[10px] text-gray-400 mt-1">The official PDF receipt with verification QR code will be attached.</p>
                </div>

                <div class="pt-3 border-t border-slate-100 dark:border-gray-700 flex items-center justify-end gap-2">
                    <button type="button" @click="isEmailModalOpen = false" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 font-bold text-xs hover:bg-slate-100 transition">
                        Cancel
                    </button>
                    <button type="submit" :disabled="isSendingEmail" 
                            class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-md transition disabled:opacity-50">
                        <i class="fa-solid fa-spinner fa-spin" x-show="isSendingEmail"></i>
                        <i class="fa-solid fa-paper-plane" x-show="!isSendingEmail"></i>
                        <span x-text="isSendingEmail ? 'Sending Email...' : 'Send PDF Receipt'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Hidden Delete Form -->
    <form id="deleteReceiptForm" action="actions/custom_receipt_logic.php" method="POST" class="hidden">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <input type="hidden" name="action" value="delete_receipt">
        <input type="hidden" name="id" id="deleteReceiptId">
    </form>
</div>

<script>
function customReceiptsHistoryApp() {
    return {
        items: <?php echo json_encode($receipts); ?>,
        search: '',
        modeFilter: '',
        dateFilter: 'all',

        isEmailModalOpen: false,
        activeReceipt: null,
        emailForm: { email: '' },
        isSendingEmail: false,

        page: 1,
        perPage: 10,

        get totalPages() {
            return Math.ceil(this.filteredReceipts.length / this.perPage) || 1;
        },

        get paginatedReceipts() {
            const start = (this.page - 1) * this.perPage;
            return this.filteredReceipts.slice(start, start + this.perPage);
        },

        setPage(p) {
            if (p >= 1 && p <= this.totalPages) this.page = p;
        },

        get hasActiveFilters() {
            return !!(this.search || this.modeFilter || this.dateFilter !== 'all');
        },

        resetFilters() {
            this.search = '';
            this.modeFilter = '';
            this.dateFilter = 'all';
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

        printReceipt(id) {
            const url = `download_custom_receipt.php?id=${id}`;
            const printWin = window.open(url, '_blank');
            if (printWin) {
                printWin.focus();
            }
        },

        openEmailModal(r) {
            this.activeReceipt = r;
            this.emailForm.email = r.payer_email || '';
            this.isEmailModalOpen = true;
        },

        async sendEmailReceipt() {
            if (!this.emailForm.email) {
                alert('Please enter a valid email address.');
                return;
            }

            this.isSendingEmail = true;
            const fd = new FormData();
            fd.append('id', this.activeReceipt.id);
            fd.append('email', this.emailForm.email);
            fd.append('ajax', '1');

            try {
                const res = await fetch('actions/send_custom_receipt.php', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: fd
                });
                const data = await res.json();
                if (data.status === 'success') {
                    alert(data.message);
                    this.isEmailModalOpen = false;
                    // Update local email if needed
                    if (this.activeReceipt) {
                        this.activeReceipt.payer_email = this.emailForm.email;
                    }
                } else {
                    alert(data.message || 'Error sending email.');
                }
            } catch (err) {
                console.error(err);
                alert('Email dispatch failed. Please check network/SMTP settings.');
            } finally {
                this.isSendingEmail = false;
            }
        },

        confirmDelete(id, receiptNo) {
            if (confirm(`Are you sure you want to delete Custom Receipt #${receiptNo} permanently?`)) {
                document.getElementById('deleteReceiptId').value = id;
                document.getElementById('deleteReceiptForm').submit();
            }
        },

        shareWhatsApp(r) {
            const phone = (r.payer_phone || '').replace(/\D/g, '');
            const cleanPhone = phone.length === 10 ? ('91' + phone) : phone;
            const baseUrl = <?php echo json_encode(rtrim(appBaseUrl(), '/')); ?>;
            const downloadUrl = `${baseUrl}/download-custom-receipt.php?id=${r.id}`;
            const msg = `*Namaste ${r.payer_name},*\n\n` +
                        `Your official donation/payment receipt *#${r.receipt_no}* for *INR ${Number(r.amount).toLocaleString('en-IN', {minimumFractionDigits: 2})}* has been generated.\n\n` +
                        `📌 *Purpose:* ${r.purpose}\n` +
                        `📅 *Date:* ${this.formatDate(r.date)}\n` +
                        `📄 *Download Receipt PDF with QR Code:* ${downloadUrl}\n\n` +
                        `Thank you for your generous support!`;

            const waUrl = cleanPhone 
                ? `https://wa.me/${cleanPhone}?text=${encodeURIComponent(msg)}`
                : `https://wa.me/?text=${encodeURIComponent(msg)}`;
            window.open(waUrl, '_blank');
        },

        get filteredReceipts() {
            return this.items.filter(r => {
                const s = this.search.toLowerCase().trim();
                const matchesSearch = !s ||
                    (r.receipt_no && r.receipt_no.toLowerCase().includes(s)) ||
                    (r.payer_name && r.payer_name.toLowerCase().includes(s)) ||
                    (r.payer_phone && r.payer_phone.toLowerCase().includes(s)) ||
                    (r.payer_email && r.payer_email.toLowerCase().includes(s)) ||
                    (r.payer_pan && r.payer_pan.toLowerCase().includes(s)) ||
                    (r.purpose && r.purpose.toLowerCase().includes(s)) ||
                    (r.transaction_ref && r.transaction_ref.toLowerCase().includes(s));

                const matchesMode = !this.modeFilter || (r.payment_mode && r.payment_mode.toLowerCase().includes(this.modeFilter.toLowerCase()));

                let matchesDate = true;
                const rDate = r.date ? r.date.split(' ')[0] : '';
                if (this.dateFilter === 'today') {
                    const today = new Date().toISOString().slice(0, 10);
                    matchesDate = rDate === today;
                } else if (this.dateFilter === 'this_month') {
                    const curYm = new Date().toISOString().slice(0, 7);
                    matchesDate = rDate.startsWith(curYm);
                } else if (this.dateFilter === 'last_month') {
                    const d = new Date();
                    d.setMonth(d.getMonth() - 1);
                    const prevYm = d.toISOString().slice(0, 7);
                    matchesDate = rDate.startsWith(prevYm);
                } else if (this.dateFilter === 'this_year') {
                    const curY = new Date().getFullYear().toString();
                    matchesDate = rDate.startsWith(curY);
                }

                return matchesSearch && matchesMode && matchesDate;
            });
        }
    };
}
</script>

<?php require 'includes/footer.php'; ?>

