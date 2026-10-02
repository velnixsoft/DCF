<?php
// ============================================================
// admin/agreements.php
// Agreements & MoUs Management Directory
// Author: VELNIX SOFT / Antigravity AI
// Date: 2026-09-12
// ============================================================

require 'includes/header.php';
require '../config/db.php';
require_once '../includes/functions.php';
require_once '../includes/letterhead_helper.php';
require_once '../includes/agreement_helper.php';

if (!canAccessModule($pdo, 'coordinator', 'page.letters')) {
    setFlash('error', 'Access denied. You do not have permission to view agreements & MOUs.');
    header('Location: dashboard.php');
    exit;
}

$csrfToken = generateCsrfToken();
$lhSettings = get_letterhead_settings($pdo);
$types = get_agreement_types();

// Fetch all agreements
$agreements = [];
if (dbTableExists($pdo, 'agreements')) {
    $stmt = $pdo->query("
        SELECT a.*, u.name AS created_by_name 
        FROM agreements a
        LEFT JOIN users u ON a.created_by = u.id
        ORDER BY a.id DESC
    ");
    $agreements = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Compute Metrics
$totalAgreements = count($agreements);
$mouCount = 0;
$authCount = 0;
$signedCount = 0;
$pendingCount = 0;

foreach ($agreements as $a) {
    if ($a['type'] === 'mou') $mouCount++;
    if ($a['type'] === 'authorization') $authCount++;
    if ($a['signed_status'] === 'signed') $signedCount++;
    if ($a['signed_status'] === 'pending_signature') $pendingCount++;
}
?>

<div class="flex h-screen overflow-hidden bg-slate-50 dark:bg-gray-900" x-data="agreementsManager(<?php echo htmlspecialchars(json_encode($agreements, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>)" x-cloak>
    <?php require 'includes/sidebar.php'; ?>

    <div class="flex-1 flex flex-col md:ml-64 overflow-hidden">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6 lg:p-8 space-y-6">
            <!-- Flash Message -->
            <?php displayFlash(); ?>

            <!-- Page Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2.5">
                        <span class="p-2.5 rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                            <i class="fa-solid fa-file-contract text-2xl"></i>
                        </span>
                        <div>
                            <h1 class="text-2xl md:text-3xl font-black text-gray-900 dark:text-white tracking-tight">Agreements & MoUs</h1>
                            <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                                Institutional Memorandums of Understanding, Bilateral Partnerships & Authorization Letters on Official Letterhead.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Launch Composer -->
                <div class="flex items-center gap-2.5">
                    <a href="agreement_composer.php" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-all shadow-md shadow-indigo-200 dark:shadow-none flex items-center gap-2">
                        <i class="fa-solid fa-plus text-sm"></i>
                        <span>Compose Agreement / MoU</span>
                    </a>
                </div>
            </div>

            <!-- KPI Metric Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Total Agreements</p>
                        <h3 class="text-2xl font-black text-gray-900 dark:text-white mt-1"><?php echo $totalAgreements; ?></h3>
                        <p class="text-[11px] text-indigo-600 dark:text-indigo-400 font-semibold mt-0.5">All Categories</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-xl shadow-xs">
                        <i class="fa-solid fa-handshake"></i>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Executed & Signed</p>
                        <h3 class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1"><?php echo $signedCount; ?></h3>
                        <p class="text-[11px] text-gray-500 mt-0.5">Active Partnerships</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-xl shadow-xs">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Pending Signatures</p>
                        <h3 class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1"><?php echo $pendingCount; ?></h3>
                        <p class="text-[11px] text-gray-500 mt-0.5">Under Review / Sign</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center text-xl shadow-xs">
                        <i class="fa-solid fa-hourglass-half"></i>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Authorizations & MoUs</p>
                        <h3 class="text-2xl font-black text-teal-600 dark:text-teal-400 mt-1"><?php echo ($mouCount + $authCount); ?></h3>
                        <p class="text-[11px] text-gray-500 mt-0.5"><?php echo $mouCount; ?> MoUs • <?php echo $authCount; ?> Auth</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-teal-50 dark:bg-teal-900/30 text-teal-600 dark:text-teal-400 flex items-center justify-center text-xl shadow-xs">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                </div>
            </div>

            <!-- Quick Preset Launch Bar -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-100 dark:border-gray-700/60 shadow-xs">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-400">
                            <i class="fa-solid fa-bolt text-amber-500 mr-1"></i> Quick Compose:
                        </span>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="agreement_composer.php?type=mou" class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300 border border-indigo-100 dark:border-indigo-800 transition-colors flex items-center gap-1.5">
                            <i class="fa-solid fa-handshake"></i>
                            <span>Bilateral MoU</span>
                        </a>
                        <a href="agreement_composer.php?type=authorization" class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-teal-50 hover:bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-300 border border-teal-100 dark:border-teal-800 transition-colors flex items-center gap-1.5">
                            <i class="fa-solid fa-shield-halved"></i>
                            <span>Authorization Letter</span>
                        </a>
                        <a href="agreement_composer.php?type=service_agreement" class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-blue-50 hover:bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 border border-blue-100 dark:border-blue-800 transition-colors flex items-center gap-1.5">
                            <i class="fa-solid fa-file-signature"></i>
                            <span>Service Agreement</span>
                        </a>
                        <a href="agreement_composer.php?type=partnership" class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-emerald-50 hover:bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 border border-emerald-100 dark:border-emerald-800 transition-colors flex items-center gap-1.5">
                            <i class="fa-solid fa-building-ngo"></i>
                            <span>CSR Partnership</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Search & Filters Toolbar -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 md:p-5 border border-gray-100 dark:border-gray-700/60 shadow-xs">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                    <!-- Search Input -->
                    <div class="lg:col-span-2 relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                        <input type="text" x-model="search" placeholder="Search partner name, agreement no, title..." class="w-full pl-9 pr-4 py-2 bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <!-- Type Filter -->
                    <div>
                        <select x-model="filterType" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-semibold">
                            <option value="">All Agreement Types</option>
                            <option value="mou">Memorandum of Understanding (MoU)</option>
                            <option value="authorization">Letter of Authorization</option>
                            <option value="service_agreement">Service Agreement</option>
                            <option value="partnership">Strategic CSR Partnership</option>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <select x-model="filterStatus" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-semibold">
                            <option value="">All Statuses</option>
                            <option value="signed">Signed & Executed</option>
                            <option value="pending_signature">Pending Signature</option>
                            <option value="draft">Draft</option>
                            <option value="expired">Expired</option>
                            <option value="terminated">Terminated</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Agreements Roster Table -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                        <thead class="bg-gray-50 dark:bg-gray-750 text-[11px] uppercase tracking-wider text-gray-500 dark:text-gray-400 font-bold border-b border-gray-100 dark:border-gray-700">
                            <tr>
                                <th class="p-4">Agreement Ref & Type</th>
                                <th class="p-4">Partner Organization</th>
                                <th class="p-4">Title & Scope</th>
                                <th class="p-4">Validity Timeline</th>
                                <th class="p-4">Signed Status</th>
                                <th class="p-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <template x-for="a in paginatedItems" :key="'agr-' + a.id">
                                <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-700/40 transition-colors">
                                    
                                    <!-- Ref No & Type Badge -->
                                    <td class="p-4">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400 flex-shrink-0 text-sm">
                                                <i class="fa-solid" :class="typeIcon(a.type)"></i>
                                            </div>
                                            <div>
                                                <span class="font-mono font-bold text-gray-900 dark:text-white" x-text="a.agreement_no"></span>
                                                <span class="block text-[10px] font-semibold mt-0.5" :class="typeBadgeClass(a.type)" x-text="typeLabel(a.type)"></span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Partner Organization -->
                                    <td class="p-4">
                                        <div>
                                            <p class="font-bold text-gray-900 dark:text-white" x-text="a.partner_name"></p>
                                            <template x-if="a.partner_type">
                                                <span class="text-[10px] text-gray-400 block" x-text="a.partner_type"></span>
                                            </template>
                                            <template x-if="a.partner_contact || a.partner_email">
                                                <p class="text-[10px] text-gray-500 mt-0.5 flex items-center gap-1.5">
                                                    <template x-if="a.partner_contact">
                                                        <span x-text="a.partner_contact"></span>
                                                    </template>
                                                    <template x-if="a.partner_email">
                                                        <span class="text-indigo-600" x-text="'• ' + a.partner_email"></span>
                                                    </template>
                                                </p>
                                            </template>
                                        </div>
                                    </td>

                                    <!-- Title & Scope -->
                                    <td class="p-4">
                                        <div class="max-w-[220px]">
                                            <p class="font-medium text-gray-800 dark:text-gray-200 line-clamp-2" :title="a.title" x-text="a.title"></p>
                                        </div>
                                    </td>

                                    <!-- Validity Timeline -->
                                    <td class="p-4 whitespace-nowrap">
                                        <div>
                                            <p class="text-[11px] font-semibold text-gray-700 dark:text-gray-300">
                                                <span class="text-gray-400 font-normal">Signed:</span> <span x-text="formatDate(a.signed_date)"></span>
                                            </p>
                                            <template x-if="a.valid_until">
                                                <p class="text-[10px] text-gray-500 mt-0.5">
                                                    <span class="text-gray-400">Valid to:</span> <span x-text="formatDate(a.valid_until)"></span>
                                                </p>
                                            </template>
                                        </div>
                                    </td>

                                    <!-- Signed Status & Digital Acknowledgment Audit -->
                                    <td class="p-4">
                                        <button @click="toggleStatus(a)" class="px-2.5 py-1 rounded-full text-[10px] font-bold cursor-pointer transition-transform active:scale-95" :class="statusClass(a.signed_status)" :title="'Click to toggle status (Currently: ' + a.signed_status + ')'" x-text="statusLabel(a.signed_status)"></button>
                                        
                                        <template x-if="a.is_acknowledged == 1 || a.acknowledged_at">
                                            <div class="mt-1 text-[10px] text-emerald-600 dark:text-emerald-400 font-medium">
                                                <div class="flex items-center gap-1">
                                                    <i class="fa-solid fa-signature"></i>
                                                    <span x-text="a.acknowledged_name || 'Partner Rep'"></span>
                                                </div>
                                                <div class="text-[9px] text-gray-400" x-text="formatDateTime(a.acknowledged_at)"></div>
                                                <template x-if="a.acknowledged_ip">
                                                    <div class="text-[9px] font-mono text-gray-400" x-text="'IP: ' + a.acknowledged_ip"></div>
                                                </template>
                                            </div>
                                        </template>
                                    </td>

                                    <!-- Actions -->
                                    <td class="p-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <!-- Partner Portal View & Sign Page -->
                                            <a :href="'../view-agreement.php?id=' + a.id" target="_blank" class="p-2 text-gray-500 hover:text-purple-600 hover:bg-purple-50 dark:hover:bg-purple-900/30 rounded-xl transition-colors" title="Partner Digital View & Signing Portal">
                                                <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i>
                                            </a>

                                            <!-- View PDF -->
                                            <a :href="'actions/agreement_logic.php?action=view_agreement&id=' + a.id" target="_blank" class="p-2 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-xl transition-colors" title="View Agreement PDF on Letterhead">
                                                <i class="fa-solid fa-eye text-xs"></i>
                                            </a>

                                            <!-- Download PDF -->
                                            <a :href="'actions/agreement_logic.php?action=download_agreement&id=' + a.id" class="p-2 text-gray-500 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 rounded-xl transition-colors" title="Download PDF">
                                                <i class="fa-solid fa-download text-xs"></i>
                                            </a>

                                            <!-- Email to Partner -->
                                            <button type="button" @click="openEmailModal(a)" class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-xl transition-colors" title="Email Agreement to Partner">
                                                <i class="fa-solid fa-paper-plane text-xs"></i>
                                            </button>

                                            <!-- Edit in Composer Studio -->
                                            <a :href="'agreement_composer.php?id=' + a.id" class="p-2 text-gray-500 hover:text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/30 rounded-xl transition-colors" title="Edit in Composer Studio">
                                                <i class="fa-solid fa-pen-to-square text-xs"></i>
                                            </a>

                                            <!-- Delete -->
                                            <button type="button" @click="deleteAgreement(a)" class="p-2 text-gray-500 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-xl transition-colors" title="Delete Agreement">
                                                <i class="fa-solid fa-trash-can text-xs"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div x-show="filteredItems.length === 0" class="p-12 text-center">
                    <div class="w-16 h-16 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-3 text-2xl">
                        <i class="fa-solid fa-file-contract"></i>
                    </div>
                    <h3 class="text-base font-bold text-gray-800 dark:text-white">No Agreements Found</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 max-w-sm mx-auto">No agreements or MoUs matched your search or filters. Create your first bilateral MoU on letterhead.</p>
                    <a href="agreement_composer.php" class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 text-white text-xs font-bold rounded-xl hover:bg-indigo-700 shadow-sm transition-colors">
                        <i class="fa-solid fa-plus"></i>
                        <span>Compose Agreement</span>
                    </a>
                </div>

                <!-- Pagination Bar -->
                <div x-show="filteredItems.length > pageSize" class="p-4 border-t border-gray-100 dark:border-gray-700/60 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-gray-500 dark:text-gray-400">
                    <p>Showing <span class="font-bold text-gray-800 dark:text-gray-200" x-text="pageStartIndex + 1"></span> to <span class="font-bold text-gray-800 dark:text-gray-200" x-text="Math.min(pageStartIndex + pageSize, filteredItems.length)"></span> of <span class="font-bold text-gray-800 dark:text-gray-200" x-text="filteredItems.length"></span> agreements</p>
                    <div class="flex items-center gap-1.5">
                        <button @click="currentPage--" :disabled="currentPage === 1" class="px-3 py-1.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl disabled:opacity-40 hover:bg-gray-50 transition-colors">
                            <i class="fa-solid fa-chevron-left text-[10px]"></i>
                        </button>
                        <template x-for="p in totalPages" :key="p">
                            <button @click="currentPage = p" :class="currentPage === p ? 'bg-indigo-600 text-white font-bold' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 border border-gray-200 dark:border-gray-700'" class="w-8 h-8 rounded-xl transition-all" x-text="p"></button>
                        </template>
                        <button @click="currentPage++" :disabled="currentPage === totalPages" class="px-3 py-1.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl disabled:opacity-40 hover:bg-gray-50 transition-colors">
                            <i class="fa-solid fa-chevron-right text-[10px]"></i>
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Email Dispatch Modal -->
    <div x-show="showEmailModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-4" x-transition.opacity>
        <div class="bg-white dark:bg-gray-800 rounded-3xl max-w-md w-full shadow-2xl border border-gray-100 dark:border-gray-700 overflow-hidden" @click.outside="showEmailModal = false">
            <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 flex items-center justify-center">
                        <i class="fa-solid fa-paper-plane"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white text-base">Email Agreement PDF</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-mono" x-text="emailItem.agreement_no"></p>
                    </div>
                </div>
                <button @click="showEmailModal = false" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form @submit.prevent="sendEmailSubmit()" class="p-6 space-y-4 text-xs">
                <div>
                    <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">Partner Organization</label>
                    <input type="text" :value="emailItem.partner_name" readonly class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-600 dark:text-gray-300 font-bold">
                </div>

                <div>
                    <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">Recipient Email Address <span class="text-red-500">*</span></label>
                    <input type="email" x-model="emailAddress" required placeholder="director@partner.org" class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-900 dark:text-white focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div class="p-3 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-800 text-indigo-900 dark:text-indigo-300 text-[11px] flex items-center gap-2">
                    <i class="fa-solid fa-paperclip text-indigo-600"></i>
                    <span>Official Letterhead PDF attachment will be automatically included.</span>
                </div>

                <div class="pt-2 flex items-center justify-end gap-2.5">
                    <button type="button" @click="showEmailModal = false" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold rounded-xl hover:bg-gray-200 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" :disabled="sendingEmail" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow transition-all flex items-center gap-1.5">
                        <i class="fa-solid fa-paper-plane text-xs"></i>
                        <span x-text="sendingEmail ? 'Dispatching...' : 'Dispatch Email'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('agreementsManager', (initialData) => ({
        items: initialData || [],
        search: '',
        filterType: '',
        filterStatus: '',
        currentPage: 1,
        pageSize: 10,

        // Email Modal
        showEmailModal: false,
        emailItem: {},
        emailAddress: '',
        sendingEmail: false,

        get filteredItems() {
            return this.items.filter(a => {
                if (this.filterType && a.type !== this.filterType) return false;
                if (this.filterStatus && a.signed_status !== this.filterStatus) return false;

                if (this.search) {
                    const q = this.search.toLowerCase();
                    const match = (a.agreement_no && a.agreement_no.toLowerCase().includes(q)) ||
                                  (a.partner_name && a.partner_name.toLowerCase().includes(q)) ||
                                  (a.title && a.title.toLowerCase().includes(q)) ||
                                  (a.partner_email && a.partner_email.toLowerCase().includes(q)) ||
                                  (a.partner_contact && a.partner_contact.includes(q));
                    if (!match) return false;
                }

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

        formatDate(dateStr) {
            if (!dateStr) return '-';
            const d = new Date(dateStr);
            if (isNaN(d.getTime())) return dateStr;
            return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
        },

        formatDateTime(dateStr) {
            if (!dateStr) return '-';
            const d = new Date(dateStr);
            if (isNaN(d.getTime())) return dateStr;
            return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        },

        typeLabel(type) {
            const map = {
                'mou': 'Bilateral MoU',
                'authorization': 'Authorization',
                'service_agreement': 'Service Agreement',
                'partnership': 'CSR Partnership'
            };
            return map[type] || type;
        },

        typeIcon(type) {
            const map = {
                'mou': 'fa-handshake',
                'authorization': 'fa-shield-halved',
                'service_agreement': 'fa-file-signature',
                'partnership': 'fa-building-ngo'
            };
            return map[type] || 'fa-file-contract';
        },

        typeBadgeClass(type) {
            const map = {
                'mou': 'text-indigo-600 dark:text-indigo-400',
                'authorization': 'text-teal-600 dark:text-teal-400',
                'service_agreement': 'text-blue-600 dark:text-blue-400',
                'partnership': 'text-emerald-600 dark:text-emerald-400'
            };
            return map[type] || 'text-gray-500';
        },

        statusLabel(status) {
            const map = {
                'draft': 'Draft',
                'pending_signature': 'Pending Sign',
                'signed': 'Signed & Active',
                'expired': 'Expired',
                'terminated': 'Terminated'
            };
            return map[status] || status;
        },

        statusClass(status) {
            const map = {
                'signed': 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
                'pending_signature': 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
                'draft': 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                'expired': 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
                'terminated': 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300'
            };
            return map[status] || 'bg-gray-100 text-gray-700';
        },

        async toggleStatus(a) {
            const nextStatusMap = {
                'draft': 'pending_signature',
                'pending_signature': 'signed',
                'signed': 'draft',
                'expired': 'draft',
                'terminated': 'draft'
            };
            const nextStatus = nextStatusMap[a.signed_status] || 'signed';

            const formData = new FormData();
            formData.append('csrf_token', '<?php echo $csrfToken; ?>');
            formData.append('action', 'update_status');
            formData.append('id', a.id);
            formData.append('status', nextStatus);
            formData.append('is_ajax', '1');

            try {
                const res = await fetch('actions/agreement_logic.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    a.signed_status = nextStatus;
                }
            } catch (err) {
                console.error(err);
            }
        },

        openEmailModal(a) {
            this.emailItem = a;
            this.emailAddress = a.partner_email || '';
            this.showEmailModal = true;
        },

        async sendEmailSubmit() {
            if (!this.emailAddress) return;
            this.sendingEmail = true;

            const formData = new FormData();
            formData.append('csrf_token', '<?php echo $csrfToken; ?>');
            formData.append('action', 'email_agreement');
            formData.append('id', this.emailItem.id);
            formData.append('email', this.emailAddress);
            formData.append('is_ajax', '1');

            try {
                const res = await fetch('actions/agreement_logic.php', { method: 'POST', body: formData });
                const data = await res.json();
                this.sendingEmail = false;
                if (data.success) {
                    alert(data.message);
                    this.showEmailModal = false;
                } else {
                    alert(data.message || 'Failed to dispatch email.');
                }
            } catch (err) {
                this.sendingEmail = false;
                console.error(err);
                alert('An error occurred while dispatching the email.');
            }
        },

        async deleteAgreement(a) {
            if (!confirm(`Are you sure you want to delete agreement ${a.agreement_no} for ${a.partner_name}? This action cannot be undone.`)) {
                return;
            }

            const formData = new FormData();
            formData.append('csrf_token', '<?php echo $csrfToken; ?>');
            formData.append('action', 'delete_agreement');
            formData.append('id', a.id);
            formData.append('is_ajax', '1');

            try {
                const res = await fetch('actions/agreement_logic.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    this.items = this.items.filter(item => item.id !== a.id);
                } else {
                    alert(data.message || 'Failed to delete agreement.');
                }
            } catch (err) {
                console.error(err);
            }
        }
    }));
});
</script>

<?php require 'includes/footer.php'; ?>
