<?php
// ============================================================
// admin/sanstha_certificates.php
// Sanstha Authorization Certificate Generator & Management
// Author: VELNIX SOFT / Antigravity AI
// Date: 2026-09-12
// ============================================================

require 'includes/header.php';
require '../config/db.php';
require_once '../includes/functions.php';
require_once '../includes/member_module.php';
require_once '../includes/template_builder.php';
require_once '../includes/sanstha_certificate_helper.php';

if (!canAccessModule($pdo, 'coordinator', 'page.member_documents') && !canAccessModule($pdo, 'coordinator', 'page.visitor_certificates')) {
    setFlash('error', 'Access denied. You do not have permission to access Sanstha Certificates.');
    header('Location: dashboard.php');
    exit;
}

$csrfToken = generateCsrfToken();
$authTypes = get_sanstha_auth_types();
$scopePresets = get_sanstha_scope_presets();
$palettes = get_sanstha_color_palettes();

// Fetch custom templates for Sanstha Authorization from Template Builder
$customTemplates = [];
if (dbTableExists($pdo, 'templates')) {
    $stmt = $pdo->query("SELECT id, template_name, status FROM templates WHERE template_type = 'sanstha_authorization' ORDER BY id DESC");
    $customTemplates = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch all issued Sanstha Certificates
$certificates = [];
if (dbTableExists($pdo, 'sanstha_certificates')) {
    $stmt = $pdo->query("
        SELECT sc.*, u.name AS issued_by_name 
        FROM sanstha_certificates sc
        LEFT JOIN users u ON sc.issued_by = u.id
        ORDER BY sc.id DESC
    ");
    $certificates = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Metrics
$totalCerts = count($certificates);
$activeCount = 0;
$branchCount = 0;
$expiredCount = 0;
$today = date('Y-m-d');

foreach ($certificates as $c) {
    if ($c['status'] === 'active') {
        if (!empty($c['valid_until']) && $c['valid_until'] < $today) {
            $expiredCount++;
        } else {
            $activeCount++;
        }
    } else {
        $expiredCount++;
    }

    if (stripos($c['auth_type'], 'branch') !== false || stripos($c['auth_type'], 'district') !== false) {
        $branchCount++;
    }
}
?>

<div class="flex h-screen overflow-hidden bg-slate-50 dark:bg-gray-900" x-data="sansthaManager(<?php echo htmlspecialchars(json_encode($certificates, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>)" x-cloak>
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
                        <span class="p-2.5 rounded-2xl bg-amber-500/10 text-amber-600 dark:text-amber-400">
                            <i class="fa-solid fa-stamp text-2xl"></i>
                        </span>
                        <div>
                            <h1 class="text-2xl md:text-3xl font-black text-gray-900 dark:text-white tracking-tight">Sanstha Authorization Certificates</h1>
                            <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                                Generate, issue & verify official Institutional Authorization Certificates for Branches, Centers & Partner Wings.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-wrap items-center gap-2.5">
                    <a href="template-builder.php?type=sanstha_authorization" class="px-4 py-2.5 bg-white dark:bg-gray-800 hover:bg-gray-50 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 rounded-xl text-xs font-bold transition-all shadow-xs flex items-center gap-2">
                        <i class="fa-solid fa-pen-ruler text-indigo-600"></i>
                        <span>Design in Template Builder</span>
                    </a>

                    <button type="button" @click="openModal('create')" class="px-4 py-2.5 bg-gradient-to-r from-amber-600 via-orange-600 to-amber-700 hover:from-amber-700 hover:to-orange-700 text-white rounded-xl text-xs font-bold transition-all shadow-md shadow-amber-500/20 flex items-center gap-2">
                        <i class="fa-solid fa-plus text-sm"></i>
                        <span>Generate Sanstha Certificate</span>
                    </button>
                </div>
            </div>

            <!-- KPI Metric Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Total Authorizations</p>
                        <h3 class="text-2xl font-black text-gray-900 dark:text-white mt-1"><?php echo $totalCerts; ?></h3>
                        <p class="text-[11px] text-amber-600 dark:text-amber-400 font-semibold mt-0.5">All Centers & Wings</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center text-xl shadow-xs">
                        <i class="fa-solid fa-certificate"></i>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Active Centers</p>
                        <h3 class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1"><?php echo $activeCount; ?></h3>
                        <p class="text-[11px] text-gray-500 mt-0.5">Valid Authorizations</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-xl shadow-xs">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Branch & Project Offices</p>
                        <h3 class="text-2xl font-black text-indigo-600 dark:text-indigo-400 mt-1"><?php echo $branchCount; ?></h3>
                        <p class="text-[11px] text-gray-500 mt-0.5">District / Zila Wings</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-xl shadow-xs">
                        <i class="fa-solid fa-building-flag"></i>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Expired / Under Review</p>
                        <h3 class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1"><?php echo $expiredCount; ?></h3>
                        <p class="text-[11px] text-gray-500 mt-0.5">Requires Renewal</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 flex items-center justify-center text-xl shadow-xs">
                        <i class="fa-solid fa-hourglass-end"></i>
                    </div>
                </div>
            </div>

            <!-- Quick Preset Strip -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-100 dark:border-gray-700/60 shadow-xs">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-400">
                            <i class="fa-solid fa-bolt text-amber-500 mr-1"></i> Quick Presets:
                        </span>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" @click="openWithPreset('Branch Office', 'general_branch')" class="px-3 py-1.5 rounded-lg bg-amber-50 hover:bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200 text-xs font-semibold transition">
                            <i class="fa-solid fa-building mr-1"></i> Branch Office
                        </button>
                        <button type="button" @click="openWithPreset('Health & Diagnostic Unit', 'healthcare_wing')" class="px-3 py-1.5 rounded-lg bg-teal-50 hover:bg-teal-100 dark:bg-teal-900/30 text-teal-800 dark:text-teal-200 text-xs font-semibold transition">
                            <i class="fa-solid fa-hospital-user mr-1"></i> Health Unit
                        </button>
                        <button type="button" @click="openWithPreset('Skill & Vocational Training', 'skill_education')" class="px-3 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-200 text-xs font-semibold transition">
                            <i class="fa-solid fa-graduation-cap mr-1"></i> Skill Center
                        </button>
                        <button type="button" @click="openWithPreset('Social Welfare & Relief Unit', 'welfare_relief')" class="px-3 py-1.5 rounded-lg bg-purple-50 hover:bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-200 text-xs font-semibold transition">
                            <i class="fa-solid fa-hands-holding-child mr-1"></i> Welfare Unit
                        </button>
                    </div>
                </div>
            </div>

            <!-- Main Table Section -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-xs overflow-hidden">
                <!-- Toolbar -->
                <div class="p-4 md:p-5 border-b border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="relative flex-1 max-w-md">
                        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                        <input type="text" x-model="search" placeholder="Search by Sanstha name, In-charge, certificate no, city..." class="w-full pl-9 pr-3 py-2 bg-gray-50 dark:bg-gray-750 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-all">
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Filter by Type -->
                        <select x-model="filterType" class="px-3 py-2 bg-gray-50 dark:bg-gray-750 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-700 dark:text-gray-300">
                            <option value="">All Auth Types</option>
                            <?php foreach ($authTypes as $k => $v): ?>
                                <option value="<?php echo htmlspecialchars($k); ?>"><?php echo htmlspecialchars($v); ?></option>
                            <?php endforeach; ?>
                        </select>

                        <!-- Filter by Status -->
                        <select x-model="filterStatus" class="px-3 py-2 bg-gray-50 dark:bg-gray-750 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-700 dark:text-gray-300">
                            <option value="">All Statuses</option>
                            <option value="active">Active</option>
                            <option value="expired">Expired</option>
                            <option value="suspended">Suspended</option>
                            <option value="revoked">Revoked</option>
                        </select>
                    </div>
                </div>

                <!-- Table Content -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                        <thead class="bg-gray-50 dark:bg-gray-750 text-[11px] uppercase tracking-wider text-gray-500 dark:text-gray-400 font-bold border-b border-gray-100 dark:border-gray-700">
                            <tr>
                                <th class="p-4">Certificate Ref & Category</th>
                                <th class="p-4">Sanstha & Authorized Person</th>
                                <th class="p-4">Location / Jurisdiction</th>
                                <th class="p-4">Validity Timeline</th>
                                <th class="p-4">Status</th>
                                <th class="p-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <template x-for="c in paginatedItems" :key="'scert-' + c.id">
                                <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-700/40 transition-colors">
                                    
                                    <!-- Certificate No & Category -->
                                    <td class="p-4">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 dark:text-amber-400 flex-shrink-0 text-sm">
                                                <i class="fa-solid fa-stamp"></i>
                                            </div>
                                            <div>
                                                <span class="font-mono font-bold text-gray-900 dark:text-white" x-text="c.certificate_no"></span>
                                                <span class="block text-[10px] font-semibold text-amber-700 dark:text-amber-300 mt-0.5" x-text="c.auth_type"></span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Sanstha Name & Authorized Person -->
                                    <td class="p-4">
                                        <div>
                                            <p class="font-bold text-gray-900 dark:text-white" x-text="c.sanstha_name"></p>
                                            <p class="text-[11px] text-gray-500 mt-0.5">
                                                In-Charge: <strong class="text-gray-700 dark:text-gray-300" x-text="c.authorized_person"></strong> 
                                                <span class="text-gray-400" x-text="'(' + (c.designation || 'Director') + ')'"></span>
                                            </p>
                                            <template x-if="c.contact_phone || c.contact_email">
                                                <p class="text-[10px] text-gray-400 mt-0.5">
                                                    <span x-text="c.contact_phone"></span>
                                                    <span x-show="c.contact_email" x-text="' • ' + c.contact_email"></span>
                                                </p>
                                            </template>
                                        </div>
                                    </td>

                                    <!-- Location / District -->
                                    <td class="p-4">
                                        <div>
                                            <p class="font-medium text-gray-800 dark:text-gray-200">
                                                <span x-text="c.district || c.city || 'District Office'"></span>
                                                <span class="text-gray-400" x-text="c.state ? ', ' + c.state : ''"></span>
                                            </p>
                                            <p class="text-[10px] text-gray-400 line-clamp-1 mt-0.5" :title="c.center_address" x-text="c.center_address || 'Address registered'"></p>
                                        </div>
                                    </td>

                                    <!-- Validity Timeline -->
                                    <td class="p-4 whitespace-nowrap">
                                        <div>
                                            <p class="text-[11px] font-semibold text-gray-700 dark:text-gray-300">
                                                <span class="text-gray-400 font-normal">From:</span> <span x-text="formatDate(c.valid_from)"></span>
                                            </p>
                                            <p class="text-[10px] text-gray-500 mt-0.5">
                                                <span class="text-gray-400">To:</span> <span x-text="formatDate(c.valid_until) || 'Perpetual'"></span>
                                            </p>
                                        </div>
                                    </td>

                                    <!-- Status -->
                                    <td class="p-4">
                                        <button @click="toggleStatus(c)" class="px-2.5 py-1 rounded-full text-[10px] font-bold cursor-pointer transition-transform active:scale-95" :class="statusClass(c.status)" :title="'Click to toggle status'" x-text="statusLabel(c.status)"></button>
                                    </td>

                                    <!-- Actions -->
                                    <td class="p-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <!-- Public Verification Link -->
                                            <a :href="'../verify-sanstha-certificate.php?scert=' + c.certificate_no" target="_blank" class="p-2 text-gray-500 hover:text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/30 rounded-xl transition-colors" title="Public Verification & QR Page">
                                                <i class="fa-solid fa-qrcode text-xs"></i>
                                            </a>

                                            <!-- View PDF Inline -->
                                            <a :href="'actions/sanstha_certificate_logic.php?action=view_certificate&id=' + c.id" target="_blank" class="p-2 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-xl transition-colors" title="View Certificate PDF">
                                                <i class="fa-solid fa-eye text-xs"></i>
                                            </a>

                                            <!-- Download PDF -->
                                            <a :href="'actions/sanstha_certificate_logic.php?action=download_certificate&id=' + c.id" class="p-2 text-gray-500 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 rounded-xl transition-colors" title="Download PDF">
                                                <i class="fa-solid fa-download text-xs"></i>
                                            </a>

                                            <!-- Email to Sanstha -->
                                            <button type="button" @click="openEmailModal(c)" class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-xl transition-colors" title="Email Certificate">
                                                <i class="fa-solid fa-paper-plane text-xs"></i>
                                            </button>

                                            <!-- Edit -->
                                            <button type="button" @click="editCertificate(c)" class="p-2 text-gray-500 hover:text-orange-600 hover:bg-orange-50 dark:hover:bg-orange-900/30 rounded-xl transition-colors" title="Edit Certificate">
                                                <i class="fa-solid fa-pen-to-square text-xs"></i>
                                            </button>

                                            <!-- Delete -->
                                            <button type="button" @click="deleteCertificate(c)" class="p-2 text-gray-500 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-xl transition-colors" title="Delete Certificate">
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
                    <div class="w-16 h-16 bg-amber-50 dark:bg-amber-900/20 text-amber-600 rounded-2xl flex items-center justify-center mx-auto mb-3 text-2xl">
                        <i class="fa-solid fa-stamp"></i>
                    </div>
                    <h3 class="text-base font-bold text-gray-800 dark:text-white">No Sanstha Certificates Found</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 max-w-sm mx-auto">Generate your first official institutional authorization certificate for your branch office or partner center.</p>
                    <button type="button" @click="openModal('create')" class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 bg-amber-600 text-white text-xs font-bold rounded-xl hover:bg-amber-700 shadow-sm transition-colors">
                        <i class="fa-solid fa-plus"></i>
                        <span>Generate Certificate</span>
                    </button>
                </div>

                <!-- Pagination -->
                <div class="p-4 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between" x-show="filteredItems.length > 0">
                    <p class="text-xs text-gray-500">
                        Showing <span class="font-bold text-gray-800 dark:text-gray-200" x-text="pageStartIndex + 1"></span> to <span class="font-bold text-gray-800 dark:text-gray-200" x-text="Math.min(pageStartIndex + pageSize, filteredItems.length)"></span> of <span class="font-bold text-gray-800 dark:text-gray-200" x-text="filteredItems.length"></span> certificates
                    </p>
                    <div class="flex items-center gap-1">
                        <button @click="currentPage--" :disabled="currentPage === 1" class="px-3 py-1.5 rounded-lg border text-xs font-semibold disabled:opacity-40 hover:bg-gray-50 dark:hover:bg-gray-700">Prev</button>
                        <span class="px-3 py-1.5 text-xs text-gray-500" x-text="'Page ' + currentPage + ' of ' + totalPages"></span>
                        <button @click="currentPage++" :disabled="currentPage === totalPages" class="px-3 py-1.5 rounded-lg border text-xs font-semibold disabled:opacity-40 hover:bg-gray-50 dark:hover:bg-gray-700">Next</button>
                    </div>
                </div>
            </div>

            <!-- MODAL: Generate / Edit Sanstha Certificate -->
            <div x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm overflow-y-auto" x-cloak>
                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl w-full max-w-3xl my-8 overflow-hidden border border-gray-100 dark:border-gray-700" @click.away="showModal = false">
                    
                    <!-- Modal Header -->
                    <div class="p-5 md:p-6 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-gradient-to-r from-amber-500/10 via-orange-500/5 to-transparent">
                        <div class="flex items-center gap-3">
                            <span class="w-10 h-10 rounded-2xl bg-amber-600 text-white flex items-center justify-center text-lg shadow-md shadow-amber-500/20">
                                <i class="fa-solid fa-stamp"></i>
                            </span>
                            <div>
                                <h3 class="text-lg font-black text-gray-900 dark:text-white" x-text="formMode === 'create' ? 'Generate Sanstha Authorization Certificate' : 'Edit Sanstha Certificate'"></h3>
                                <p class="text-xs text-gray-500">Official certificate on royal ornate landscape format with dynamic verification QR</p>
                            </div>
                        </div>
                        <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-white text-xl p-2">&times;</button>
                    </div>

                    <!-- Modal Body Form -->
                    <form @submit.prevent="submitForm" class="p-5 md:p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="id" x-model="formData.id">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            
                            <!-- Sanstha Name -->
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">
                                    Sanstha / Center / Branch Name <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" x-model="formData.sanstha_name" required placeholder="e.g. Pragati Social Welfare Branch / Apex Diagnostic Center" class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-750 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 font-bold">
                            </div>

                            <!-- Authorized Person -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">
                                    Authorized Person / In-Charge <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" x-model="formData.authorized_person" required placeholder="e.g. Dr. Rajesh Verma / Anita Sharma" class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-750 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">
                            </div>

                            <!-- Designation -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">
                                    Designation / Role
                                </label>
                                <input type="text" x-model="formData.designation" placeholder="e.g. Center Director / Branch Head / Coordinator" class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-750 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">
                            </div>

                            <!-- Authorization Type -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">
                                    Authorization Category <span class="text-rose-500">*</span>
                                </label>
                                <select x-model="formData.auth_type" required class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-750 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">
                                    <?php foreach ($authTypes as $k => $v): ?>
                                        <option value="<?php echo htmlspecialchars($k); ?>"><?php echo htmlspecialchars($v); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Status -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">
                                    Certificate Status
                                </label>
                                <select x-model="formData.status" class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-750 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">
                                    <option value="active">Active</option>
                                    <option value="expired">Expired</option>
                                    <option value="suspended">Suspended</option>
                                    <option value="revoked">Revoked</option>
                                </select>
                            </div>

                            <!-- Contact Phone -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">
                                    Contact Helpline / Phone
                                </label>
                                <input type="text" x-model="formData.contact_phone" placeholder="e.g. +91 9876543210" class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-750 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">
                            </div>

                            <!-- Contact Email -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">
                                    Official Email Address
                                </label>
                                <input type="email" x-model="formData.contact_email" placeholder="e.g. branch.lucknow@ngo.org" class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-750 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">
                            </div>

                            <!-- Address -->
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">
                                    Center Full Address
                                </label>
                                <input type="text" x-model="formData.center_address" placeholder="e.g. Plot No 45, Sector 12, Vikas Nagar" class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-750 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">
                            </div>

                            <!-- City / District / State / PIN -->
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 md:col-span-2">
                                <div>
                                    <label class="block text-[10px] font-bold uppercase text-gray-500 mb-1">City</label>
                                    <input type="text" x-model="formData.city" placeholder="Lucknow" class="w-full p-2 bg-gray-50 dark:bg-gray-750 border border-gray-200 dark:border-gray-700 rounded-lg text-xs">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold uppercase text-gray-500 mb-1">District</label>
                                    <input type="text" x-model="formData.district" placeholder="Lucknow" class="w-full p-2 bg-gray-50 dark:bg-gray-750 border border-gray-200 dark:border-gray-700 rounded-lg text-xs">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold uppercase text-gray-500 mb-1">State</label>
                                    <input type="text" x-model="formData.state" placeholder="Uttar Pradesh" class="w-full p-2 bg-gray-50 dark:bg-gray-750 border border-gray-200 dark:border-gray-700 rounded-lg text-xs">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold uppercase text-gray-500 mb-1">PIN Code</label>
                                    <input type="text" x-model="formData.pincode" placeholder="226022" class="w-full p-2 bg-gray-50 dark:bg-gray-750 border border-gray-200 dark:border-gray-700 rounded-lg text-xs">
                                </div>
                            </div>

                            <!-- Valid From & Valid Until -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">
                                    Date of Issue (Valid From) <span class="text-rose-500">*</span>
                                </label>
                                <input type="date" x-model="formData.valid_from" required class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-750 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">
                                    Valid Until Date (Leave empty for Ongoing)
                                </label>
                                <input type="date" x-model="formData.valid_until" class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-750 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">
                            </div>

                            <!-- Scope of Work -->
                            <div class="md:col-span-2">
                                <div class="flex items-center justify-between mb-1">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                        Authorized Scope & Jurisdiction <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-[10px] text-gray-400">Load Preset:</span>
                                        <select @change="applyScopePreset($event.target.value)" class="text-[10px] bg-gray-100 dark:bg-gray-700 rounded px-2 py-0.5 border border-gray-300 dark:border-gray-600">
                                            <option value="">Select scope preset...</option>
                                            <?php foreach ($scopePresets as $sk => $sv): ?>
                                                <option value="<?php echo htmlspecialchars($sk); ?>"><?php echo htmlspecialchars($sv['title']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <textarea x-model="formData.scope_of_work" required rows="3" placeholder="Specify authorized activities, operational powers, beneficiary enrollment rights, and jurisdiction..." class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-750 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500"></textarea>
                            </div>

                            <!-- Template Theme Picker -->
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2">
                                    Certificate Style / Template Selection
                                </label>

                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                    <?php foreach ($palettes as $pNo => $pal): ?>
                                        <label class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer transition-all" :class="formData.template_no == <?php echo $pNo; ?> ? 'border-amber-500 bg-amber-50/50 dark:bg-amber-950/20 ring-2 ring-amber-400/30' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-750'">
                                            <input type="radio" name="template_no" :value="<?php echo $pNo; ?>" x-model="formData.template_no" class="text-amber-600">
                                            <div class="flex items-center gap-1.5">
                                                <span class="w-4 h-4 rounded-full border border-gray-300 shadow-xs" style="background-color: rgb(<?php echo implode(',', $pal['primary']); ?>)"></span>
                                                <span class="w-3 h-3 rounded-full border border-gray-300" style="background-color: rgb(<?php echo implode(',', $pal['secondary']); ?>)"></span>
                                                <span class="text-xs font-semibold text-gray-800 dark:text-gray-200"><?php echo htmlspecialchars($pal['name']); ?></span>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>

                                <?php if (!empty($customTemplates)): ?>
                                    <div class="mt-3 p-3 rounded-xl bg-indigo-50/50 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-900/40">
                                        <label class="block text-xs font-bold text-indigo-900 dark:text-indigo-300 mb-1">
                                            Or Select Custom Template from Template Builder:
                                        </label>
                                        <select x-model="formData.template_id" class="w-full p-2 bg-white dark:bg-gray-800 border border-indigo-200 dark:border-indigo-800 rounded-lg text-xs">
                                            <option value="">Use Built-in Royal Certificate Theme</option>
                                            <?php foreach ($customTemplates as $ct): ?>
                                                <option value="<?php echo (int)$ct['id']; ?>"><?php echo htmlspecialchars($ct['template_name']); ?> (Visual Builder Template)</option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                <?php endif; ?>
                            </div>

                        </div>

                        <!-- Footer Actions -->
                        <div class="pt-4 border-t border-gray-100 dark:border-gray-700 flex flex-wrap items-center justify-between gap-3">
                            <button type="button" @click="showModal = false" class="px-4 py-2.5 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 rounded-xl text-xs font-semibold hover:bg-gray-50">
                                Cancel
                            </button>

                            <div class="flex items-center gap-2">
                                <button type="submit" :disabled="submitting" class="px-6 py-2.5 bg-gradient-to-r from-amber-600 via-orange-600 to-amber-700 hover:from-amber-700 hover:to-orange-700 text-white rounded-xl text-xs font-bold shadow-md shadow-amber-500/20 flex items-center gap-2">
                                    <span x-show="!submitting"><i class="fa-solid fa-stamp mr-1"></i> <span x-text="formMode === 'create' ? 'Generate & Save Certificate' : 'Update Certificate'"></span></span>
                                    <span x-show="submitting"><i class="fa-solid fa-spinner fa-spin mr-1"></i> Generating PDF...</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- MODAL: Email Certificate -->
            <div x-show="showEmailModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-cloak>
                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl w-full max-w-md p-6 border border-gray-100 dark:border-gray-700" @click.away="showEmailModal = false">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-2.5">
                            <span class="p-2 rounded-xl bg-blue-500/10 text-blue-600">
                                <i class="fa-solid fa-paper-plane text-lg"></i>
                            </span>
                            <h3 class="font-bold text-gray-900 dark:text-white">Email Sanstha Certificate</h3>
                        </div>
                        <button @click="showEmailModal = false" class="text-gray-400 text-xl">&times;</button>
                    </div>

                    <div class="py-4 space-y-3">
                        <p class="text-xs text-gray-500">
                            Dispatch official Certificate <strong class="text-gray-800 dark:text-gray-200" x-text="emailItem.certificate_no"></strong> to Sanstha In-charge with PDF attachment.
                        </p>
                        <div>
                            <label class="block text-xs font-bold uppercase text-gray-600 dark:text-gray-400 mb-1">Recipient Email</label>
                            <input type="email" x-model="emailAddress" required class="w-full p-2.5 bg-gray-50 dark:bg-gray-750 border rounded-xl text-xs">
                        </div>
                    </div>

                    <div class="pt-3 border-t border-gray-100 dark:border-gray-700 flex justify-end gap-2">
                        <button type="button" @click="showEmailModal = false" class="px-4 py-2 border rounded-xl text-xs font-semibold">Cancel</button>
                        <button type="button" @click="sendEmailSubmit" :disabled="sendingEmail" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-sm">
                            <span x-show="!sendingEmail"><i class="fa-solid fa-paper-plane mr-1"></i> Send Email</span>
                            <span x-show="sendingEmail"><i class="fa-solid fa-spinner fa-spin mr-1"></i> Sending...</span>
                        </button>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('sansthaManager', (initialItems) => ({
        items: initialItems || [],
        search: '',
        filterType: '',
        filterStatus: '',
        currentPage: 1,
        pageSize: 10,

        showModal: false,
        formMode: 'create',
        submitting: false,

        showEmailModal: false,
        emailItem: {},
        emailAddress: '',
        sendingEmail: false,

        formData: {
            id: '',
            sanstha_name: '',
            authorized_person: '',
            designation: 'Center Head / Director',
            auth_type: 'Branch Office',
            contact_phone: '',
            contact_email: '',
            center_address: '',
            city: '',
            district: '',
            state: 'Uttar Pradesh',
            pincode: '',
            valid_from: '<?php echo date('Y-m-d'); ?>',
            valid_until: '',
            scope_of_work: 'Authorized to operate as an official Branch Office, conduct member enrollments, organize social awareness drives, coordinate welfare projects, and represent the organization within the assigned district jurisdiction.',
            template_id: '',
            template_no: 1,
            status: 'active'
        },

        scopePresets: <?php echo json_encode($scopePresets); ?>,

        get filteredItems() {
            return this.items.filter(c => {
                if (this.filterType && c.auth_type !== this.filterType) return false;
                if (this.filterStatus && c.status !== this.filterStatus) return false;

                if (this.search) {
                    const q = this.search.toLowerCase();
                    const match = (c.certificate_no && c.certificate_no.toLowerCase().includes(q)) ||
                                  (c.sanstha_name && c.sanstha_name.toLowerCase().includes(q)) ||
                                  (c.authorized_person && c.authorized_person.toLowerCase().includes(q)) ||
                                  (c.city && c.city.toLowerCase().includes(q)) ||
                                  (c.district && c.district.toLowerCase().includes(q)) ||
                                  (c.auth_type && c.auth_type.toLowerCase().includes(q));
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
            if (!dateStr) return '';
            const d = new Date(dateStr);
            if (isNaN(d.getTime())) return dateStr;
            return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
        },

        statusLabel(status) {
            const map = {
                'active': 'Active',
                'expired': 'Expired',
                'suspended': 'Suspended',
                'revoked': 'Revoked'
            };
            return map[status] || status;
        },

        statusClass(status) {
            const map = {
                'active': 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
                'expired': 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300',
                'suspended': 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
                'revoked': 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
            };
            return map[status] || 'bg-gray-100 text-gray-700';
        },

        openModal(mode = 'create') {
            this.formMode = mode;
            if (mode === 'create') {
                this.formData = {
                    id: '',
                    sanstha_name: '',
                    authorized_person: '',
                    designation: 'Center Head / Director',
                    auth_type: 'Branch Office',
                    contact_phone: '',
                    contact_email: '',
                    center_address: '',
                    city: '',
                    district: '',
                    state: 'Uttar Pradesh',
                    pincode: '',
                    valid_from: '<?php echo date('Y-m-d'); ?>',
                    valid_until: '',
                    scope_of_work: 'Authorized to operate as an official Branch Office, conduct member enrollments, organize social awareness drives, coordinate welfare projects, and represent the organization within the assigned district jurisdiction.',
                    template_id: '',
                    template_no: 1,
                    status: 'active'
                };
            }
            this.showModal = true;
        },

        openWithPreset(authType, scopeKey) {
            this.openModal('create');
            this.formData.auth_type = authType;
            if (this.scopePresets[scopeKey]) {
                this.formData.scope_of_work = this.scopePresets[scopeKey].scope;
            }
        },

        applyScopePreset(presetKey) {
            if (presetKey && this.scopePresets[presetKey]) {
                this.formData.scope_of_work = this.scopePresets[presetKey].scope;
            }
        },

        editCertificate(c) {
            this.formMode = 'edit';
            this.formData = {
                id: c.id,
                sanstha_name: c.sanstha_name || '',
                authorized_person: c.authorized_person || '',
                designation: c.designation || 'Center Head',
                auth_type: c.auth_type || 'Branch Office',
                contact_phone: c.contact_phone || '',
                contact_email: c.contact_email || '',
                center_address: c.center_address || '',
                city: c.city || '',
                district: c.district || '',
                state: c.state || '',
                pincode: c.pincode || '',
                valid_from: c.valid_from || '',
                valid_until: c.valid_until || '',
                scope_of_work: c.scope_of_work || '',
                template_id: c.template_id || '',
                template_no: c.template_no || 1,
                status: c.status || 'active'
            };
            this.showModal = true;
        },

        async submitForm() {
            this.submitting = true;
            const form = new FormData();
            form.append('csrf_token', '<?php echo $csrfToken; ?>');
            form.append('action', 'save_certificate');
            form.append('is_ajax', '1');

            for (const [key, value] of Object.entries(this.formData)) {
                form.append(key, value !== null ? value : '');
            }

            try {
                const res = await fetch('actions/sanstha_certificate_logic.php', { method: 'POST', body: form });
                const data = await res.json();
                this.submitting = false;

                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert(data.message || 'Failed to save certificate.');
                }
            } catch (err) {
                this.submitting = false;
                console.error(err);
                alert('An unexpected error occurred while saving the certificate.');
            }
        },

        async toggleStatus(c) {
            const nextStatusMap = {
                'active': 'expired',
                'expired': 'suspended',
                'suspended': 'active',
                'revoked': 'active'
            };
            const nextStatus = nextStatusMap[c.status] || 'active';

            const form = new FormData();
            form.append('csrf_token', '<?php echo $csrfToken; ?>');
            form.append('action', 'update_status');
            form.append('id', c.id);
            form.append('status', nextStatus);
            form.append('is_ajax', '1');

            try {
                const res = await fetch('actions/sanstha_certificate_logic.php', { method: 'POST', body: form });
                const data = await res.json();
                if (data.success) {
                    c.status = nextStatus;
                }
            } catch (err) {
                console.error(err);
            }
        },

        openEmailModal(c) {
            this.emailItem = c;
            this.emailAddress = c.contact_email || '';
            this.showEmailModal = true;
        },

        async sendEmailSubmit() {
            if (!this.emailAddress) return;
            this.sendingEmail = true;

            const form = new FormData();
            form.append('csrf_token', '<?php echo $csrfToken; ?>');
            form.append('action', 'email_certificate');
            form.append('id', this.emailItem.id);
            form.append('email', this.emailAddress);
            form.append('is_ajax', '1');

            try {
                const res = await fetch('actions/sanstha_certificate_logic.php', { method: 'POST', body: form });
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
                alert('Error sending email.');
            }
        },

        async deleteCertificate(c) {
            if (!confirm(`Are you sure you want to delete Sanstha Certificate ${c.certificate_no} for ${c.sanstha_name}?`)) {
                return;
            }

            const form = new FormData();
            form.append('csrf_token', '<?php echo $csrfToken; ?>');
            form.append('action', 'delete_certificate');
            form.append('id', c.id);
            form.append('is_ajax', '1');

            try {
                const res = await fetch('actions/sanstha_certificate_logic.php', { method: 'POST', body: form });
                const data = await res.json();
                if (data.success) {
                    this.items = this.items.filter(item => item.id !== c.id);
                } else {
                    alert(data.message || 'Failed to delete certificate.');
                }
            } catch (err) {
                console.error(err);
            }
        }
    }));
});
</script>

<?php require 'includes/footer.php'; ?>
