<?php
require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../includes/india_locations.php';
require_once '../includes/health_card_helper.php';

$csrfToken = generateCsrfToken();

if (!checkRole($pdo, 'coordinator')) {
    setFlash('error', 'Unauthorized access. Coordinator/Manager/Admin role required.');
    header('Location: dashboard.php');
    exit;
}

require 'includes/header.php';

// Fetch all health card applications
$cards = [];
if (dbTableExists($pdo, 'health_cards')) {
    $stmt = $pdo->query("
        SELECT h.*, u.name AS issued_by_name, m.member_no AS member_ref_no 
        FROM health_cards h
        LEFT JOIN users u ON h.issued_by = u.id
        LEFT JOIN members m ON h.member_id = m.id
        ORDER BY h.id DESC
    ");
    $cards = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Compute Metrics
$totalCards = count($cards);
$pendingCount = 0;
$activeCount = 0;
$expiredCount = 0;
$rejectedCount = 0;

foreach ($cards as $c) {
    if ($c['status'] === 'pending_approval') {
        $pendingCount++;
    } elseif (in_array($c['status'], ['active', 'renewed'], true)) {
        $activeCount++;
    } elseif ($c['status'] === 'expired') {
        $expiredCount++;
    } elseif ($c['status'] === 'rejected') {
        $rejectedCount++;
    }
}

$indiaStatesJson = india_state_district_js();
$indiaStatesList = india_state_list();
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900" x-data="healthCardAdminManager()">
    <?php require 'includes/sidebar.php'; ?>

    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6 lg:p-8">
            <!-- Flash Message -->
            <?php displayFlash(); ?>

            <!-- Page Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="p-2 rounded-xl bg-teal-500/10 text-teal-600 dark:text-teal-400">
                            <i class="fa-solid fa-id-card-clip text-xl"></i>
                        </span>
                        <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white tracking-tight">Health Card Applications</h1>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Review, approve, and issue verified Health Cards with automated sequential numbering and embedded QR code PDFs.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <a href="../apply-health-card.php" target="_blank" class="inline-flex items-center gap-2 px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-all duration-150 hover:shadow">
                        <i class="fa-solid fa-plus text-xs"></i>
                        <span>New Application</span>
                    </a>

                    <button @click="exportCSV()" class="inline-flex items-center gap-2 px-3.5 py-2.5 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-xl transition-colors shadow-sm">
                        <i class="fa-solid fa-file-csv text-teal-600"></i>
                        <span class="hidden sm:inline">Export</span>
                    </button>
                </div>
            </div>

            <!-- KPI Metric Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <!-- Total Applications -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700/60 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Total Applications</p>
                        <h3 class="text-2xl font-black text-gray-900 dark:text-white mt-1"><?php echo $totalCards; ?></h3>
                        <p class="text-xs text-teal-600 dark:text-teal-400 mt-1 font-medium">All recorded cards</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-teal-50 dark:bg-teal-900/30 flex items-center justify-center text-teal-600 dark:text-teal-400 text-xl shadow-inner">
                        <i class="fa-solid fa-address-card"></i>
                    </div>
                </div>

                <!-- Pending Approvals -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700/60 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Pending Review</p>
                        <h3 class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1"><?php echo $pendingCount; ?></h3>
                        <p class="text-xs text-amber-600 dark:text-amber-400 mt-1 font-medium">Action required</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 dark:text-amber-400 text-xl shadow-inner">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                </div>

                <!-- Active Issued Cards -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700/60 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Active & Issued</p>
                        <h3 class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1"><?php echo $activeCount; ?></h3>
                        <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1 font-medium">QR Code live & valid</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400 text-xl shadow-inner">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                </div>

                <!-- Expired / Rejected -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700/60 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Expired / Rejected</p>
                        <h3 class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1"><?php echo ($expiredCount + $rejectedCount); ?></h3>
                        <p class="text-xs text-rose-600 dark:text-rose-400 mt-1 font-medium"><?php echo $expiredCount; ?> Expired • <?php echo $rejectedCount; ?> Rejected</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-rose-50 dark:bg-rose-900/30 flex items-center justify-center text-rose-600 dark:text-rose-400 text-xl shadow-inner">
                        <i class="fa-solid fa-ban"></i>
                    </div>
                </div>
            </div>

            <!-- Filters & Search Toolbar -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 md:p-5 border border-gray-100 dark:border-gray-700/60 shadow-sm mb-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                    <!-- Search Input -->
                    <div class="lg:col-span-2 relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                        <input type="text" x-model="search" placeholder="Search applicant, card no, phone, Aadhaar..." class="w-full pl-9 pr-4 py-2 bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-500">
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <select x-model="filterStatus" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                            <option value="">All Statuses</option>
                            <option value="pending_approval">Pending Approval</option>
                            <option value="active">Active</option>
                            <option value="expired">Expired</option>
                            <option value="renewed">Renewed</option>
                            <option value="rejected">Rejected</option>
                            <option value="blocked">Blocked</option>
                        </select>
                    </div>

                    <!-- State Filter -->
                    <div>
                        <select x-model="filterState" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                            <option value="">All States</option>
                            <?php foreach ($indiaStatesList as $st): ?>
                                <option value="<?php echo htmlspecialchars($st); ?>"><?php echo htmlspecialchars($st); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Blood Group Filter -->
                    <div>
                        <select x-model="filterBloodGroup" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                            <option value="">All Blood Groups</option>
                            <option value="A+">A+</option>
                            <option value="A-">A-</option>
                            <option value="B+">B+</option>
                            <option value="B-">B-</option>
                            <option value="O+">O+</option>
                            <option value="O-">O-</option>
                            <option value="AB+">AB+</option>
                            <option value="AB-">AB-</option>
                        </select>
                    </div>
                </div>

                <!-- Reset Filter Button -->
                <div x-show="hasActiveFilters" class="pt-3 mt-3 border-t border-gray-100 dark:border-gray-700/50 flex justify-end">
                    <button @click="resetFilters()" class="text-xs text-red-500 hover:text-red-600 font-bold inline-flex items-center gap-1.5 transition-colors">
                        <i class="fa-solid fa-rotate-left"></i>
                        <span>Reset Filters</span>
                    </button>
                </div>
            </div>

            <!-- Applications Table -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                        <thead class="bg-gray-50 dark:bg-gray-750 text-[11px] uppercase tracking-wider text-gray-500 dark:text-gray-400 font-bold border-b border-gray-100 dark:border-gray-700">
                            <tr>
                                <th class="p-4">Applicant</th>
                                <th class="p-4">Card Number</th>
                                <th class="p-4">Contact & Blood</th>
                                <th class="p-4">Location</th>
                                <th class="p-4">Validity</th>
                                <th class="p-4">Status</th>
                                <th class="p-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <template x-for="c in paginatedItems" :key="'card-' + c.id">
                                <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-700/40 transition-colors">
                                    
                                    <!-- Applicant Info & Photo -->
                                    <td class="p-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-gray-700 flex-shrink-0 flex items-center justify-center overflow-hidden border border-gray-200 dark:border-gray-600">
                                                <template x-if="c.photo">
                                                    <img :src="'../' + c.photo" :alt="c.applicant_name" class="w-full h-full object-cover">
                                                </template>
                                                <template x-if="!c.photo">
                                                    <i class="fa-solid fa-user text-gray-400 text-sm"></i>
                                                </template>
                                            </div>
                                            <div>
                                                <p class="font-bold text-gray-900 dark:text-white" x-text="c.applicant_name"></p>
                                                <p class="text-[11px] text-gray-400" x-text="(c.gender || 'Male') + (c.age ? ' • ' + c.age + ' yrs' : '')"></p>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Card Number -->
                                    <td class="p-4">
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-mono font-bold text-xs" :class="c.card_number ? 'text-teal-600 dark:text-teal-400' : 'text-gray-400'" x-text="c.card_number || 'Pending Generation'"></span>
                                        </div>
                                        <template x-if="c.member_ref_no">
                                            <span class="text-[10px] text-blue-600 font-semibold" x-text="'Member: ' + c.member_ref_no"></span>
                                        </template>
                                    </td>

                                    <!-- Contact & Blood Group -->
                                    <td class="p-4">
                                        <div>
                                            <p class="font-semibold text-gray-800 dark:text-gray-200 font-mono" x-text="c.contact"></p>
                                            <template x-if="c.blood_group">
                                                <span class="inline-block px-1.5 py-0.5 rounded bg-rose-50 text-rose-600 dark:bg-rose-900/40 dark:text-rose-300 font-bold text-[10px] mt-0.5" x-text="'Blood: ' + c.blood_group"></span>
                                            </template>
                                        </div>
                                    </td>

                                    <!-- Location -->
                                    <td class="p-4">
                                        <div>
                                            <p class="font-medium text-gray-800 dark:text-gray-200" x-text="(c.district ? c.district + ', ' : '') + (c.state || '-')"></p>
                                            <p class="text-gray-400 text-[10px] truncate max-w-[140px]" :title="c.address" x-text="c.address"></p>
                                        </div>
                                    </td>

                                    <!-- Validity -->
                                    <td class="p-4">
                                        <div class="text-[11px]">
                                            <p class="text-gray-700 dark:text-gray-300">Exp: <span class="font-semibold" x-text="formatDate(c.expiry_date)"></span></p>
                                            <p class="text-gray-400 text-[10px]" x-text="'Issued: ' + formatDate(c.issue_date)"></p>
                                        </div>
                                    </td>

                                    <!-- Status -->
                                    <td class="p-4">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold" :class="statusClass(c.status)" x-text="statusLabel(c.status)"></span>
                                    </td>

                                    <!-- Actions -->
                                    <td class="p-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            
                                            <!-- Approve Button (if pending) -->
                                            <template x-if="c.status === 'pending_approval'">
                                                <button @click="openApproveModal(c)" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-[11px] shadow-xs flex items-center gap-1 transition-colors" title="Approve & Generate Health Card PDF">
                                                    <i class="fa-solid fa-check text-[10px]"></i>
                                                    <span>Approve</span>
                                                </button>
                                            </template>

                                            <!-- Reject Button (if pending) -->
                                            <template x-if="c.status === 'pending_approval'">
                                                <button @click="openRejectModal(c)" class="p-1.5 text-gray-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-lg transition-colors" title="Reject Application">
                                                    <i class="fa-solid fa-xmark text-sm"></i>
                                                </button>
                                            </template>

                                            <!-- Print / View PDF Button (if active or approved) -->
                                            <template x-if="c.status === 'active' || c.status === 'renewed' || c.status === 'expired'">
                                                <a :href="'download_health_card.php?id=' + c.id + '&mode=stream'" target="_blank" class="p-1.5 text-teal-600 hover:bg-teal-50 dark:hover:bg-teal-900/30 rounded-lg transition-colors" title="View / Print Health Card PDF">
                                                    <i class="fa-solid fa-print text-sm"></i>
                                                </a>
                                            </template>

                                            <!-- Download PDF Button -->
                                            <template x-if="c.status === 'active' || c.status === 'renewed' || c.status === 'expired'">
                                                <a :href="'download_health_card.php?id=' + c.id + '&mode=download'" class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors" title="Download PDF">
                                                    <i class="fa-solid fa-download text-sm"></i>
                                                </a>
                                            </template>

                                            <!-- WhatsApp Link -->
                                            <template x-if="c.contact && (c.status === 'active' || c.status === 'renewed')">
                                                <a :href="'https://wa.me/' + cleanPhone(c.contact) + '?text=' + encodeURIComponent('Namaste ' + c.applicant_name + ', your NGO Health Card has been approved and issued with Card No: ' + c.card_number + '. Valid till: ' + formatDate(c.expiry_date))" target="_blank" class="p-1.5 text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 rounded-lg transition-colors" title="Share via WhatsApp">
                                                    <i class="fa-brands fa-whatsapp text-sm"></i>
                                                </a>
                                            </template>

                                            <!-- View Details Modal -->
                                            <button @click="openViewModal(c)" class="p-1.5 text-gray-500 hover:text-teal-600 hover:bg-teal-50 dark:hover:bg-teal-900/30 rounded-lg transition-colors" title="View Full Details">
                                                <i class="fa-solid fa-eye text-sm"></i>
                                            </button>

                                            <!-- Delete -->
                                            <button @click="confirmDelete(c)" class="p-1.5 text-gray-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-lg transition-colors" title="Delete">
                                                <i class="fa-solid fa-trash-can text-sm"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Empty State -->
            <div x-show="filteredItems.length === 0" class="p-12 text-center bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 mt-4">
                <div class="w-16 h-16 bg-teal-50 dark:bg-teal-900/20 text-teal-600 rounded-2xl flex items-center justify-center mx-auto mb-3 text-2xl">
                    <i class="fa-solid fa-id-card-clip"></i>
                </div>
                <h3 class="text-base font-bold text-gray-800 dark:text-white">No Health Card Applications Found</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 max-w-sm mx-auto">No cards matched your current filter criteria. Try resetting filters.</p>
                <button @click="resetFilters()" class="mt-4 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-xs font-semibold rounded-xl hover:bg-gray-200 transition-colors">
                    Reset All Filters
                </button>
            </div>

            <!-- Pagination Bar -->
            <div x-show="filteredItems.length > pageSize" class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-6 text-xs text-gray-500 dark:text-gray-400">
                <p>Showing <span class="font-bold text-gray-800 dark:text-gray-200" x-text="pageStartIndex + 1"></span> to <span class="font-bold text-gray-800 dark:text-gray-200" x-text="Math.min(pageStartIndex + pageSize, filteredItems.length)"></span> of <span class="font-bold text-gray-800 dark:text-gray-200" x-text="filteredItems.length"></span> applications</p>
                <div class="flex items-center gap-1.5">
                    <button @click="currentPage--" :disabled="currentPage === 1" class="px-3 py-1.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl disabled:opacity-40 hover:bg-gray-50 transition-colors">
                        <i class="fa-solid fa-chevron-left text-[10px]"></i>
                    </button>
                    <template x-for="p in totalPages" :key="p">
                        <button @click="currentPage = p" :class="currentPage === p ? 'bg-teal-600 text-white font-bold' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-50 border border-gray-200 dark:border-gray-700'" class="w-8 h-8 rounded-xl transition-all" x-text="p"></button>
                    </template>
                    <button @click="currentPage++" :disabled="currentPage === totalPages" class="px-3 py-1.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl disabled:opacity-40 hover:bg-gray-50 transition-colors">
                        <i class="fa-solid fa-chevron-right text-[10px]"></i>
                    </button>
                </div>
            </div>

            <!-- ==================================================== -->
            <!-- MODAL 1: APPROVE HEALTH CARD MODAL                   -->
            <!-- ==================================================== -->
            <div x-show="showApproveModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4" x-transition.opacity>
                <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-md w-full shadow-2xl border border-gray-100 dark:border-gray-700 p-6" @click.outside="showApproveModal = false">
                    <div class="w-14 h-14 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 rounded-2xl flex items-center justify-center mx-auto mb-4 text-2xl">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    
                    <h3 class="font-bold text-gray-900 dark:text-white text-lg text-center">Approve Health Card</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 text-center">
                        Approving will auto-generate an official sequential Card Number and render a verified Health Card PDF with scannable QR Code.
                    </p>

                    <form action="actions/health_card_logic.php" method="POST" class="mt-5 space-y-4">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="action" value="approve_card">
                        <input type="hidden" name="id" :value="approveItem.id">

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Applicant Name</label>
                            <input type="text" readonly :value="approveItem.applicant_name" class="w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-800 dark:text-gray-200">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Card Validity Duration</label>
                            <select name="validity_years" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                                <option value="1">1 Year Validity (Standard)</option>
                                <option value="2">2 Years Validity</option>
                                <option value="3">3 Years Validity (Senior Citizen / BPL)</option>
                            </select>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-2">
                            <button type="button" @click="showApproveModal = false" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-xs font-semibold rounded-xl hover:bg-gray-200 transition-colors">
                                Cancel
                            </button>
                            <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow transition-colors flex items-center gap-1.5">
                                <i class="fa-solid fa-stamp"></i>
                                <span>Approve & Issue PDF</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ==================================================== -->
            <!-- MODAL 2: REJECT APPLICATION MODAL                    -->
            <!-- ==================================================== -->
            <div x-show="showRejectModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4" x-transition.opacity>
                <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-md w-full shadow-2xl border border-gray-100 dark:border-gray-700 p-6" @click.outside="showRejectModal = false">
                    <div class="w-14 h-14 bg-rose-50 dark:bg-rose-900/20 text-rose-600 rounded-2xl flex items-center justify-center mx-auto mb-4 text-2xl">
                        <i class="fa-solid fa-ban"></i>
                    </div>

                    <h3 class="font-bold text-gray-900 dark:text-white text-lg text-center">Reject Application</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 text-center">
                        Specify reason for rejecting application for <strong x-text="rejectItem.applicant_name"></strong>.
                    </p>

                    <form action="actions/health_card_logic.php" method="POST" class="mt-5 space-y-4">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="action" value="reject_card">
                        <input type="hidden" name="id" :value="rejectItem.id">

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Rejection Reason / Notes</label>
                            <textarea name="reason" rows="3" required placeholder="e.g. Incomplete address, invalid contact number, or duplicate request..." class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500"></textarea>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-2">
                            <button type="button" @click="showRejectModal = false" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-xs font-semibold rounded-xl hover:bg-gray-200 transition-colors">
                                Cancel
                            </button>
                            <button type="submit" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl shadow transition-colors flex items-center gap-1.5">
                                <i class="fa-solid fa-ban"></i>
                                <span>Confirm Reject</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ==================================================== -->
            <!-- MODAL 3: VIEW HEALTH CARD DETAILS                    -->
            <!-- ==================================================== -->
            <div x-show="showViewModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4" x-transition.opacity>
                <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-xl w-full shadow-2xl border border-gray-100 dark:border-gray-700 overflow-hidden flex flex-col max-h-[90vh]" @click.outside="showViewModal = false">
                    <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-gray-50/50 dark:bg-gray-750">
                        <div class="flex items-center gap-3">
                            <div class="w-11 h-11 rounded-xl bg-teal-50 dark:bg-teal-900/30 flex items-center justify-center text-teal-600 text-lg overflow-hidden border border-teal-100">
                                <template x-if="viewItem.photo">
                                    <img :src="'../' + viewItem.photo" :alt="viewItem.applicant_name" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!viewItem.photo">
                                    <i class="fa-solid fa-user"></i>
                                </template>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900 dark:text-white text-base" x-text="viewItem.applicant_name"></h3>
                                <p class="text-xs font-mono text-teal-600 dark:text-teal-400" x-text="viewItem.card_number || 'Pending Approval'"></p>
                            </div>
                        </div>
                        <button @click="showViewModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-1.5 rounded-lg">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>

                    <div class="p-5 overflow-y-auto space-y-4 text-xs">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="px-2.5 py-1 rounded-full font-bold" :class="statusClass(viewItem.status)" x-text="statusLabel(viewItem.status)"></span>
                            <template x-if="viewItem.blood_group">
                                <span class="px-2.5 py-1 rounded-lg bg-rose-50 text-rose-600 dark:bg-rose-900/30 dark:text-rose-400 font-bold" x-text="'Blood Group: ' + viewItem.blood_group"></span>
                            </template>
                        </div>

                        <div class="grid grid-cols-2 gap-3 bg-gray-50 dark:bg-gray-750 p-4 rounded-xl border border-gray-100 dark:border-gray-700">
                            <div>
                                <p class="text-gray-400 uppercase font-semibold text-[10px]">Primary Mobile</p>
                                <p class="font-bold text-teal-600 dark:text-teal-400 mt-0.5 font-mono" x-text="viewItem.contact"></p>
                            </div>
                            <div>
                                <p class="text-gray-400 uppercase font-semibold text-[10px]">Emergency Mobile</p>
                                <p class="font-semibold text-gray-800 dark:text-gray-200 mt-0.5 font-mono" x-text="viewItem.emergency_contact || '-'"></p>
                            </div>
                            <div>
                                <p class="text-gray-400 uppercase font-semibold text-[10px]">Gender / Age</p>
                                <p class="font-semibold text-gray-800 dark:text-gray-200 mt-0.5" x-text="(viewItem.gender || 'Male') + (viewItem.age ? ' / ' + viewItem.age + ' Y' : '')"></p>
                            </div>
                            <div>
                                <p class="text-gray-400 uppercase font-semibold text-[10px]">Aadhaar / ID No</p>
                                <p class="font-semibold text-gray-800 dark:text-gray-200 mt-0.5 font-mono" x-text="viewItem.aadhaar_no || '-'"></p>
                            </div>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-750 p-4 rounded-xl border border-gray-100 dark:border-gray-700 space-y-1">
                            <p class="text-gray-400 uppercase font-semibold text-[10px]">Residential Address</p>
                            <p class="font-semibold text-gray-900 dark:text-white" x-text="viewItem.address"></p>
                            <p class="text-gray-600 dark:text-gray-300" x-text="(viewItem.block ? viewItem.block + ', ' : '') + viewItem.district + ', ' + viewItem.state + (viewItem.pincode ? ' - ' + viewItem.pincode : '')"></p>
                        </div>

                        <template x-if="viewItem.remarks">
                            <div class="bg-amber-50 dark:bg-amber-950/40 p-4 rounded-xl border border-amber-100 dark:border-amber-800/40 text-amber-900 dark:text-amber-200">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400">Medical Remarks / Concessions Notes</p>
                                <p class="mt-0.5" x-text="viewItem.remarks"></p>
                            </div>
                        </template>
                    </div>

                    <div class="p-4 bg-gray-50 dark:bg-gray-750 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
                        <template x-if="viewItem.status === 'active' || viewItem.status === 'renewed'">
                            <a :href="'download_health_card.php?id=' + viewItem.id + '&mode=stream'" target="_blank" class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white font-bold rounded-xl text-xs flex items-center gap-1.5 shadow-xs">
                                <i class="fa-solid fa-print"></i>
                                <span>Print Health Card</span>
                            </a>
                        </template>
                        <div x-show="viewItem.status !== 'active' && viewItem.status !== 'renewed'"></div>
                        <button @click="showViewModal = false" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 font-semibold rounded-xl text-xs hover:bg-gray-300 transition-colors">
                            Close
                        </button>
                    </div>
                </div>
            </div>

            <!-- ==================================================== -->
            <!-- MODAL 4: DELETE CONFIRMATION                         -->
            <!-- ==================================================== -->
            <div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4" x-transition.opacity>
                <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-md w-full shadow-2xl border border-gray-100 dark:border-gray-700 p-6 text-center" @click.outside="showDeleteModal = false">
                    <div class="w-14 h-14 bg-rose-50 dark:bg-rose-900/20 text-rose-600 rounded-2xl flex items-center justify-center mx-auto mb-4 text-2xl">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <h3 class="font-bold text-gray-900 dark:text-white text-lg">Delete Health Card Record?</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                        Are you sure you want to permanently delete the health card record for <strong class="text-gray-800 dark:text-gray-200" x-text="deleteItem.applicant_name"></strong>?
                    </p>

                    <form action="actions/health_card_logic.php" method="POST" class="mt-6 flex items-center justify-center gap-3">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="action" value="delete_card">
                        <input type="hidden" name="id" :value="deleteItem.id">

                        <button type="button" @click="showDeleteModal = false" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-xs font-semibold rounded-xl hover:bg-gray-200 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-xl shadow transition-colors flex items-center gap-1.5">
                            <i class="fa-solid fa-trash-can"></i>
                            <span>Confirm Delete</span>
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('healthCardAdminManager', () => ({
        items: <?php echo json_encode($cards, JSON_UNESCAPED_UNICODE); ?>,
        search: '',
        filterStatus: '',
        filterState: '',
        filterBloodGroup: '',
        currentPage: 1,
        pageSize: 10,

        // Modals
        showApproveModal: false,
        approveItem: {},
        showRejectModal: false,
        rejectItem: {},
        showViewModal: false,
        viewItem: {},
        showDeleteModal: false,
        deleteItem: {},

        get hasActiveFilters() {
            return this.search || this.filterStatus || this.filterState || this.filterBloodGroup;
        },

        resetFilters() {
            this.search = '';
            this.filterStatus = '';
            this.filterState = '';
            this.filterBloodGroup = '';
            this.currentPage = 1;
        },

        get filteredItems() {
            return this.items.filter(c => {
                if (this.search) {
                    const q = this.search.toLowerCase();
                    const match = (c.applicant_name && c.applicant_name.toLowerCase().includes(q)) ||
                                  (c.card_number && c.card_number.toLowerCase().includes(q)) ||
                                  (c.contact && c.contact.includes(q)) ||
                                  (c.aadhaar_no && c.aadhaar_no.toLowerCase().includes(q)) ||
                                  (c.district && c.district.toLowerCase().includes(q));
                    if (!match) return false;
                }

                if (this.filterStatus && c.status !== this.filterStatus) return false;
                if (this.filterState && c.state !== this.filterState) return false;
                if (this.filterBloodGroup && c.blood_group !== this.filterBloodGroup) return false;

                return true;
            });
        },

        get totalPages() {
            return Math.ceil(this.filteredItems.length / this.pageSize) || 1;
        },

        get pageStartIndex() {
            return (this.currentPage - 1) * this.pageSize;
        },

        get paginatedItems() {
            const start = this.pageStartIndex;
            return this.filteredItems.slice(start, start + this.pageSize);
        },

        openApproveModal(c) {
            this.approveItem = c;
            this.showApproveModal = true;
        },

        openRejectModal(c) {
            this.rejectItem = c;
            this.showRejectModal = true;
        },

        openViewModal(c) {
            this.viewItem = c;
            this.showViewModal = true;
        },

        confirmDelete(c) {
            this.deleteItem = c;
            this.showDeleteModal = true;
        },

        statusLabel(st) {
            const map = {
                'pending_approval': 'Pending Review',
                'active': 'Active / Issued',
                'expired': 'Expired',
                'renewed': 'Renewed',
                'rejected': 'Rejected',
                'blocked': 'Blocked'
            };
            return map[st] || st;
        },

        statusClass(st) {
            if (st === 'active' || st === 'renewed') return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300';
            if (st === 'pending_approval') return 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300';
            if (st === 'expired' || st === 'rejected' || st === 'blocked') return 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300';
            return 'bg-gray-100 text-gray-800';
        },

        formatDate(d) {
            if (!d) return '-';
            const parts = d.split('-');
            if (parts.length === 3) {
                return parts[2] + '/' + parts[1] + '/' + parts[0];
            }
            return d;
        },

        cleanPhone(phone) {
            if (!phone) return '';
            return phone.replace(/[^0-9]/g, '');
        },

        exportCSV() {
            let csv = "Card Number,Applicant Name,Contact,Blood Group,Gender,Age,State,District,Issue Date,Expiry Date,Status\n";
            this.filteredItems.forEach(c => {
                const row = [
                    `"${c.card_number || ''}"`,
                    `"${(c.applicant_name || '').replace(/"/g, '""')}"`,
                    `"${c.contact}"`,
                    `"${c.blood_group || ''}"`,
                    `"${c.gender || ''}"`,
                    `"${c.age || ''}"`,
                    `"${c.state || ''}"`,
                    `"${c.district || ''}"`,
                    `"${c.issue_date || ''}"`,
                    `"${c.expiry_date || ''}"`,
                    `"${c.status}"`
                ];
                csv += row.join(",") + "\n";
            });

            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `health_card_applications_${new Date().toISOString().slice(0, 10)}.csv`;
            a.click();
            URL.revokeObjectURL(url);
        }
    }));
});
</script>

<?php require 'includes/footer.php'; ?>
