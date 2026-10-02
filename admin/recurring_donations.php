<?php
require 'includes/header.php';
require '../config/db.php';

if (!canAccessModule($pdo, 'manager', 'page.recurring_donations') && !canAccessModule($pdo, 'manager', 'page.donations')) {
    setFlash('error', 'Access denied.');
    header('Location: dashboard.php');
    exit;
}

$csrfToken = generateCsrfToken();

// Fetch summary metrics
$kpi = [
    'active_count' => 0,
    'mrr' => 0.00,
    'total_collected' => 0.00,
    'total_cycles' => 0,
    'paused_count' => 0,
    'stopped_count' => 0
];

try {
    $activeStmt = $pdo->query("SELECT COUNT(*) AS cnt, COALESCE(SUM(amount), 0) AS mrr FROM recurring_donations WHERE status = 'active'");
    $actRow = $activeStmt->fetch(PDO::FETCH_ASSOC);
    $kpi['active_count'] = (int)($actRow['cnt'] ?? 0);
    $kpi['mrr'] = (float)($actRow['mrr'] ?? 0);

    $statusStmt = $pdo->query("SELECT status, COUNT(*) AS cnt FROM recurring_donations GROUP BY status");
    while ($r = $statusStmt->fetch(PDO::FETCH_ASSOC)) {
        if ($r['status'] === 'paused') $kpi['paused_count'] = (int)$r['cnt'];
        if ($r['status'] === 'stopped') $kpi['stopped_count'] = (int)$r['cnt'];
    }

    $txStmt = $pdo->query("SELECT COUNT(*) AS total_cycles, COALESCE(SUM(amount), 0) AS total_amount FROM recurring_donation_transactions WHERE status = 'success'");
    $txRow = $txStmt->fetch(PDO::FETCH_ASSOC);
    $kpi['total_cycles'] = (int)($txRow['total_cycles'] ?? 0);
    $kpi['total_collected'] = (float)($txRow['total_amount'] ?? 0);

} catch (Throwable $e) {
    // Graceful fallback
}

// Fetch all recurring donations
$sql = "
    SELECT r.*, p.title AS project_title,
           COALESCE(s.full_name, r.referral_code) AS referrer_display,
           COUNT(t.id) AS total_cycle_count,
           COALESCE(SUM(CASE WHEN t.status = 'success' THEN t.amount ELSE 0 END), 0) AS total_paid_amount
    FROM recurring_donations r
    LEFT JOIN projects p ON r.project_id = p.id
    LEFT JOIN sa_students s ON r.sa_student_id = s.id
    LEFT JOIN recurring_donation_transactions t ON r.id = t.recurring_donation_id
    GROUP BY r.id
    ORDER BY r.created_at DESC
";
$recurringList = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="flex h-screen overflow-hidden bg-gray-100 dark:bg-dark-bg" x-data="recurringManager(<?php echo htmlspecialchars(json_encode($recurringList, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>)">

    <?php require 'includes/sidebar.php'; ?>

    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6">

            <!-- Title & Top Actions -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                <div>
                    <h3 class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-white flex items-center gap-3">
                        <i class="fa-solid fa-arrows-rotate text-[#0F8B8D]"></i>
                        Auto Pay / Recurring Donations
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Manage donor subscription profiles, auto-debit mandates, and billing cycle history.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a :href="'actions/recurring_donation_logic.php?action=export_excel&status=' + filterStatus" 
                       class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2.5 rounded-xl shadow-sm text-sm font-semibold flex items-center gap-2 transition">
                        <i class="fa-solid fa-file-excel"></i>
                        Export Excel
                    </a>
                </div>
            </div>

            <!-- KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white dark:bg-dark-card p-5 rounded-2xl shadow-sm border dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Active Mandates</p>
                            <h4 class="text-2xl font-black text-gray-800 dark:text-white mt-1">
                                <?php echo number_format($kpi['active_count']); ?>
                            </h4>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 flex items-center justify-center text-xl">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                    </div>
                    <div class="mt-3 text-xs text-gray-500">
                        MRR: <span class="font-bold text-emerald-600">₹<?php echo number_format($kpi['mrr'], 2); ?></span>/mo
                    </div>
                </div>

                <div class="bg-white dark:bg-dark-card p-5 rounded-2xl shadow-sm border dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Total Collected</p>
                            <h4 class="text-2xl font-black text-[#0F8B8D] dark:text-[#2DD4BF] mt-1">
                                ₹<?php echo number_format($kpi['total_collected'], 2); ?>
                            </h4>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-teal-50 dark:bg-teal-900/30 text-[#0F8B8D] flex items-center justify-center text-xl">
                            <i class="fa-solid fa-hand-holding-dollar"></i>
                        </div>
                    </div>
                    <div class="mt-3 text-xs text-gray-500">
                        Across <span class="font-bold text-gray-800 dark:text-gray-200"><?php echo number_format($kpi['total_cycles']); ?></span> successful cycles
                    </div>
                </div>

                <div class="bg-white dark:bg-dark-card p-5 rounded-2xl shadow-sm border dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Paused Mandates</p>
                            <h4 class="text-2xl font-black text-amber-500 mt-1">
                                <?php echo number_format($kpi['paused_count']); ?>
                            </h4>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-amber-50 dark:bg-amber-900/30 text-amber-500 flex items-center justify-center text-xl">
                            <i class="fa-solid fa-pause"></i>
                        </div>
                    </div>
                    <div class="mt-3 text-xs text-gray-500">Temporarily on hold</div>
                </div>

                <div class="bg-white dark:bg-dark-card p-5 rounded-2xl shadow-sm border dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Cancelled / Stopped</p>
                            <h4 class="text-2xl font-black text-gray-500 mt-1">
                                <?php echo number_format($kpi['stopped_count']); ?>
                            </h4>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-500 flex items-center justify-center text-xl">
                            <i class="fa-solid fa-ban"></i>
                        </div>
                    </div>
                    <div class="mt-3 text-xs text-gray-500">Ended subscriptions</div>
                </div>
            </div>

            <!-- Filter Toolbar -->
            <div class="bg-white dark:bg-dark-card p-4 rounded-2xl shadow-sm border dark:border-gray-700 mb-6 flex flex-col md:flex-row gap-4 items-stretch md:items-center justify-between">
                <div class="flex flex-wrap gap-3 items-center">
                    <!-- Status Filter -->
                    <div class="flex flex-col">
                        <label class="text-[11px] font-bold uppercase tracking-wider text-gray-400 mb-1">Status</label>
                        <select x-model="filterStatus" class="px-3 py-2 border rounded-xl bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm outline-none">
                            <option value="all">All Status</option>
                            <option value="active">Active</option>
                            <option value="paused">Paused</option>
                            <option value="stopped">Stopped / Cancelled</option>
                            <option value="pending">Pending</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>

                    <!-- Frequency Filter -->
                    <div class="flex flex-col">
                        <label class="text-[11px] font-bold uppercase tracking-wider text-gray-400 mb-1">Frequency</label>
                        <select x-model="filterFrequency" class="px-3 py-2 border rounded-xl bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm outline-none">
                            <option value="all">All Frequencies</option>
                            <option value="monthly">Monthly</option>
                            <option value="quarterly">Quarterly</option>
                            <option value="yearly">Yearly</option>
                        </select>
                    </div>
                </div>

                <!-- Search Input -->
                <div class="flex flex-col md:w-80">
                    <label class="text-[11px] font-bold uppercase tracking-wider text-gray-400 mb-1">Search Donors</label>
                    <div class="relative">
                        <input type="text" x-model="search" placeholder="Name, Email, Mobile or Sub ID..." 
                               class="w-full pl-9 pr-4 py-2 border rounded-xl bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm outline-none">
                        <i class="fa-solid fa-magnifying-glass text-gray-400 absolute left-3 top-3 text-xs"></i>
                    </div>
                </div>
            </div>

            <!-- Table of Recurring Subscriptions -->
            <div class="bg-white dark:bg-dark-card rounded-2xl shadow-sm border dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800/50 border-b dark:border-gray-700">
                                <th class="px-4 py-3.5">Donor Profile</th>
                                <th class="px-4 py-3.5">Amount / Plan</th>
                                <th class="px-4 py-3.5">Project</th>
                                <th class="px-4 py-3.5">Cycles / Paid</th>
                                <th class="px-4 py-3.5">Next Charge</th>
                                <th class="px-4 py-3.5">Status</th>
                                <th class="px-4 py-3.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y dark:divide-gray-700 text-sm">
                            <template x-for="item in paginatedItems" :key="item.id">
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                                    <td class="px-4 py-3.5">
                                        <div class="flex flex-col">
                                            <span class="font-bold text-gray-800 dark:text-white text-sm" x-text="item.donor_name"></span>
                                            <span class="text-xs text-gray-500 font-mono" x-text="item.donor_email"></span>
                                            <span class="text-xs text-gray-400" x-text="item.donor_mobile"></span>
                                        </div>
                                    </td>

                                    <td class="px-4 py-3.5">
                                        <div class="flex flex-col">
                                            <span class="font-bold text-emerald-600 text-base">₹<span x-text="Number(item.amount).toLocaleString('en-IN')"></span></span>
                                            <span class="text-[11px] font-semibold text-[#0F8B8D] uppercase tracking-wider" x-text="item.frequency"></span>
                                            <span class="text-[10px] text-gray-400 font-mono" x-text="item.razorpay_subscription_id || 'Direct'"></span>
                                        </div>
                                    </td>

                                    <td class="px-4 py-3.5">
                                        <span class="text-xs font-medium text-gray-600 dark:text-gray-300" x-text="item.project_title || 'General Purpose'"></span>
                                    </td>

                                    <td class="px-4 py-3.5">
                                        <div class="flex flex-col">
                                            <span class="font-semibold text-gray-800 dark:text-white">
                                                <span x-text="item.completed_cycles"></span> Cycles Paid
                                            </span>
                                            <span class="text-xs text-emerald-600 font-bold">
                                                Total: ₹<span x-text="Number(item.total_paid_amount || 0).toLocaleString('en-IN')"></span>
                                            </span>
                                        </div>
                                    </td>

                                    <td class="px-4 py-3.5">
                                        <template x-if="item.next_charge_date && item.status === 'active'">
                                            <span class="text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 px-2.5 py-1 rounded-lg" x-text="item.next_charge_date"></span>
                                        </template>
                                        <template x-if="!item.next_charge_date || item.status !== 'active'">
                                            <span class="text-xs text-gray-400">—</span>
                                        </template>
                                    </td>

                                    <td class="px-4 py-3.5">
                                        <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider inline-flex items-center gap-1.5"
                                              :class="{
                                                  'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400': item.status === 'active',
                                                  'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400': item.status === 'paused',
                                                  'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400': item.status === 'stopped',
                                                  'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400': item.status === 'pending',
                                                  'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400': item.status === 'failed'
                                              }">
                                            <span class="w-1.5 h-1.5 rounded-full"
                                                  :class="{
                                                      'bg-emerald-500': item.status === 'active',
                                                      'bg-amber-500': item.status === 'paused',
                                                      'bg-gray-400': item.status === 'stopped',
                                                      'bg-orange-500': item.status === 'pending',
                                                      'bg-rose-500': item.status === 'failed'
                                                  }"></span>
                                            <span x-text="item.status"></span>
                                        </span>
                                    </td>

                                    <td class="px-4 py-3.5 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <!-- View Cycles / History -->
                                            <button type="button" @click="viewCycles(item)" 
                                                    class="p-2 rounded-lg bg-teal-50 hover:bg-teal-100 text-[#0F8B8D] dark:bg-teal-900/20 dark:hover:bg-teal-900/40 transition" 
                                                    title="View Billing Cycles & Receipts">
                                                <i class="fa-solid fa-list-check"></i>
                                            </button>

                                            <!-- Pause Button -->
                                            <button type="button" x-show="item.status === 'active'" @click="openActionModal('pause', item)" 
                                                    class="p-2 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-600 dark:bg-amber-900/20 dark:hover:bg-amber-900/40 transition" 
                                                    title="Pause Auto-Debit">
                                                <i class="fa-solid fa-pause"></i>
                                            </button>

                                            <!-- Resume Button -->
                                            <button type="button" x-show="item.status === 'paused'" @click="openActionModal('resume', item)" 
                                                    class="p-2 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-600 dark:bg-emerald-900/20 dark:hover:bg-emerald-900/40 transition" 
                                                    title="Resume Auto-Debit">
                                                <i class="fa-solid fa-play"></i>
                                            </button>

                                            <!-- Cancel / Stop Button -->
                                            <button type="button" x-show="item.status === 'active' || item.status === 'paused'" @click="openActionModal('cancel', item)" 
                                                    class="p-2 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 dark:bg-rose-900/20 dark:hover:bg-rose-900/40 transition" 
                                                    title="Cancel Subscription">
                                                <i class="fa-solid fa-ban"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>

                    <div x-show="filteredItems.length === 0" class="p-12 text-center text-gray-400">
                        <i class="fa-solid fa-arrows-rotate text-3xl mb-3 opacity-40"></i>
                        <p class="font-medium">No recurring donations found matching the filters.</p>
                    </div>
                </div>

                <!-- Pagination Footer -->
                <div class="px-6 py-4 bg-gray-50/50 dark:bg-gray-800/50 border-t dark:border-gray-700 flex flex-col sm:flex-row items-center justify-between gap-4" x-show="filteredItems.length > 0">
                    <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                        <span>Showing <strong class="text-gray-800 dark:text-gray-200" x-text="(page - 1) * perPage + 1"></strong> to <strong class="text-gray-800 dark:text-gray-200" x-text="Math.min(page * perPage, filteredItems.length)"></strong> of <strong class="text-gray-800 dark:text-gray-200" x-text="filteredItems.length"></strong> results</span>
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

            <!-- MODAL: Billing Cycles & Charge History -->
            <div x-show="isCyclesModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-cloak x-transition>
                <div class="bg-white dark:bg-dark-card rounded-2xl shadow-2xl border dark:border-gray-700 w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden">
                    
                    <div class="p-6 border-b dark:border-gray-700 flex items-center justify-between bg-gray-50 dark:bg-gray-800/50">
                        <div>
                            <h4 class="text-lg font-bold text-gray-800 dark:text-white flex items-center gap-2">
                                <i class="fa-solid fa-clock-rotate-left text-[#0F8B8D]"></i>
                                Recurring Cycles History
                            </h4>
                            <p class="text-xs text-gray-500 mt-1">
                                Donor: <strong class="text-gray-700 dark:text-gray-300" x-text="selectedItem ? selectedItem.donor_name : ''"></strong> 
                                (<span x-text="selectedItem ? selectedItem.donor_email : ''"></span>)
                            </p>
                        </div>
                        <button type="button" @click="isCyclesModalOpen = false" class="text-gray-400 hover:text-gray-600 text-lg">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="p-6 flex-1 overflow-y-auto space-y-4">
                        <div x-show="loadingCycles" class="p-8 text-center text-gray-400">
                            <i class="fa-solid fa-circle-notch fa-spin text-2xl text-[#0F8B8D]"></i>
                            <p class="text-xs mt-2">Loading transactions...</p>
                        </div>

                        <div x-show="!loadingCycles">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr class="bg-gray-50 dark:bg-gray-800 text-gray-500 uppercase tracking-wider border-b dark:border-gray-700">
                                        <th class="p-3">Cycle #</th>
                                        <th class="p-3">Charge Date</th>
                                        <th class="p-3">Amount</th>
                                        <th class="p-3">Receipt No</th>
                                        <th class="p-3">Payment ID</th>
                                        <th class="p-3">Status</th>
                                        <th class="p-3 text-right">Receipt</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y dark:divide-gray-700">
                                    <template x-for="c in cyclesList" :key="c.id">
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/20">
                                            <td class="p-3 font-bold text-gray-700 dark:text-gray-300">
                                                Cycle #<span x-text="c.cycle_number"></span>
                                            </td>
                                            <td class="p-3" x-text="c.charge_date"></td>
                                            <td class="p-3 font-bold text-emerald-600">₹<span x-text="c.amount"></span></td>
                                            <td class="p-3 font-mono text-gray-500" x-text="c.receipt_no || c.donation_receipt_no || '—'"></td>
                                            <td class="p-3 font-mono text-gray-400 text-[11px]" x-text="c.razorpay_payment_id || 'Manual'"></td>
                                            <td class="p-3">
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase"
                                                      :class="c.status === 'success' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'"
                                                      x-text="c.status"></span>
                                            </td>
                                            <td class="p-3 text-right">
                                                <template x-if="c.donation_id && c.status === 'success'">
                                                    <a :href="'generate_receipt.php?id=' + c.donation_id" target="_blank"
                                                       class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded text-xs font-semibold border border-emerald-200">
                                                        <i class="fa-solid fa-file-pdf"></i> Receipt
                                                    </a>
                                                </template>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>

                            <div x-show="cyclesList.length === 0" class="p-8 text-center text-gray-400">
                                No billing cycles recorded yet for this mandate.
                            </div>
                        </div>
                    </div>

                    <div class="p-4 border-t dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex justify-end">
                        <button type="button" @click="isCyclesModalOpen = false" class="px-5 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-xl text-sm font-semibold">
                            Close
                        </button>
                    </div>
                </div>
            </div>

            <!-- MODAL: Confirmation & Reason (Pause/Cancel/Resume) -->
            <div x-show="isActionModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-cloak x-transition>
                <div class="bg-white dark:bg-dark-card rounded-2xl shadow-2xl border dark:border-gray-700 w-full max-w-md p-6">
                    <form action="actions/recurring_donation_logic.php" method="POST" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="action" :value="actionType">
                        <input type="hidden" name="recurring_id" :value="selectedItem ? selectedItem.id : ''">

                        <div class="text-center">
                            <div class="w-14 h-14 rounded-full mx-auto mb-3 flex items-center justify-center text-2xl"
                                 :class="{
                                     'bg-amber-100 text-amber-600': actionType === 'pause',
                                     'bg-emerald-100 text-emerald-600': actionType === 'resume',
                                     'bg-rose-100 text-rose-600': actionType === 'cancel'
                                 }">
                                <i :class="{
                                    'fa-solid fa-pause': actionType === 'pause',
                                    'fa-solid fa-play': actionType === 'resume',
                                    'fa-solid fa-ban': actionType === 'cancel'
                                }"></i>
                            </div>

                            <h4 class="text-lg font-bold text-gray-800 dark:text-white capitalize" x-text="actionType + ' Recurring Donation'"></h4>
                            <p class="text-xs text-gray-500 mt-1">
                                Are you sure you want to <span x-text="actionType"></span> auto-debit for <strong x-text="selectedItem ? selectedItem.donor_name : ''"></strong> (₹<span x-text="selectedItem ? selectedItem.amount : ''"></span>)?
                            </p>
                        </div>

                        <div x-show="actionType === 'pause' || actionType === 'cancel'">
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Reason / Note</label>
                            <input type="text" name="reason" placeholder="e.g. Donor requested temporary pause" class="w-full border rounded-xl px-3 py-2 text-sm bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none">
                        </div>

                        <div class="flex gap-2 pt-2">
                            <button type="button" @click="isActionModalOpen = false" class="flex-1 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-sm font-semibold">
                                Cancel
                            </button>
                            <button type="submit" class="flex-1 py-2.5 text-white rounded-xl text-sm font-semibold shadow-sm"
                                    :class="{
                                        'bg-amber-600 hover:bg-amber-700': actionType === 'pause',
                                        'bg-emerald-600 hover:bg-emerald-700': actionType === 'resume',
                                        'bg-rose-600 hover:bg-rose-700': actionType === 'cancel'
                                    }"
                                    x-text="'Confirm ' + actionType">
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </main>
    </div>
</div>

<script>
function recurringManager(initialItems) {
    return {
        items: initialItems || [],
        filterStatus: 'all',
        filterFrequency: 'all',
        search: '',
        page: 1,
        perPage: 10,
        selectedItem: null,
        cyclesList: [],
        loadingCycles: false,
        isCyclesModalOpen: false,
        isActionModalOpen: false,
        actionType: '',

        get totalPages() {
            return Math.ceil(this.filteredItems.length / this.perPage) || 1;
        },

        get paginatedItems() {
            const start = (this.page - 1) * this.perPage;
            return this.filteredItems.slice(start, start + this.perPage);
        },

        setPage(p) {
            if (p >= 1 && p <= this.totalPages) this.page = p;
        },

        get filteredItems() {
            return this.items.filter(item => {
                const matchStatus = (this.filterStatus === 'all') || (item.status === this.filterStatus);
                const matchFreq = (this.filterFrequency === 'all') || (item.frequency === this.filterFrequency);
                
                const q = this.search.toLowerCase().trim();
                const matchSearch = !q || 
                    (item.donor_name || '').toLowerCase().includes(q) ||
                    (item.donor_email || '').toLowerCase().includes(q) ||
                    (item.donor_mobile || '').includes(q) ||
                    (item.razorpay_subscription_id || '').toLowerCase().includes(q);

                return matchStatus && matchFreq && matchSearch;
            });
        },

        async viewCycles(item) {
            this.selectedItem = item;
            this.isCyclesModalOpen = true;
            this.loadingCycles = true;
            this.cyclesList = [];

            try {
                const res = await fetch('actions/recurring_donation_logic.php?action=get_cycles&recurring_id=' + item.id);
                const data = await res.json();
                if (data.success) {
                    this.cyclesList = data.cycles || [];
                }
            } catch (e) {
                console.error(e);
            } finally {
                this.loadingCycles = false;
            }
        },

        openActionModal(type, item) {
            this.actionType = type;
            this.selectedItem = item;
            this.isActionModalOpen = true;
        }
    };
}
</script>

<?php require 'includes/footer.php'; ?>
