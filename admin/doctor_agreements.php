<?php
// ============================================================
// admin/doctor_agreements.php
// Partner Office & Doctor Agreements Management & Certificate Auto-Generator
// ============================================================

require 'includes/header.php';
require '../config/db.php';
require_once '../includes/functions.php';
require_once '../includes/doctor_certificate_helper.php';

if (!canAccessModule($pdo, 'coordinator', 'page.healthcare')) {
    setFlash('error', 'Access denied. Healthcare coordinator privileges required.');
    header('Location: dashboard.php');
    exit;
}

$csrfToken = generateCsrfToken();

// 1. Fetch Healthcare Providers for dropdown selection
$providers = [];
try {
    $providers = $pdo->query("SELECT id, provider_code, name, type, speciality, contact, email, district FROM healthcare_providers ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $providers = [];
}

// 2. Pagination & Filters
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$totalAgreements = 0;
$statusFilter = trim($_GET['status'] ?? '');
$searchQuery = trim($_GET['search'] ?? '');

$where = ["1=1"];
$params = [];

if ($statusFilter !== '') {
    $where[] = "da.status = :status";
    $params[':status'] = $statusFilter;
}
if ($searchQuery !== '') {
    $where[] = "(da.agreement_no LIKE :search OR da.agreement_title LIKE :search OR da.doctor_name LIKE :search OR hp.name LIKE :search OR hp.speciality LIKE :search)";
    $params[':search'] = "%{$searchQuery}%";
}
$whereSql = implode(' AND ', $where);

try {
    $countStmt = $pdo->prepare("
        SELECT COUNT(*) FROM doctor_agreements da
        JOIN healthcare_providers hp ON da.partner_id = hp.id
        WHERE {$whereSql}
    ");
    $countStmt->execute($params);
    $totalAgreements = (int)$countStmt->fetchColumn();
} catch (Throwable $e) {
    $totalAgreements = 0;
}

$offset = ($page - 1) * $perPage;
$agreements = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            da.*,
            hp.provider_code,
            hp.name AS partner_name,
            hp.type AS partner_type,
            hp.speciality AS partner_speciality,
            hp.contact AS partner_contact,
            hp.email AS partner_email,
            hp.district AS partner_district
        FROM doctor_agreements da
        JOIN healthcare_providers hp ON da.partner_id = hp.id
        WHERE {$whereSql}
        ORDER BY da.id DESC
        LIMIT :limit OFFSET :offset
    ");
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $agreements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $agreements = [];
}

// 3. Edit Record Lookup if requested via GET
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
$editAgreement = null;
if ($editId) {
    try {
        $eStmt = $pdo->prepare("SELECT * FROM doctor_agreements WHERE id = ?");
        $eStmt->execute([$editId]);
        $editAgreement = $eStmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $editAgreement = null;
    }
}

// 4. Calculate KPI Metrics
$activeAgreements = 0;
$pendingAgreements = 0;
$certsIssued = 0;
try {
    $activeAgreements = (int)$pdo->query("SELECT COUNT(*) FROM doctor_agreements WHERE status = 'active'")->fetchColumn();
    $pendingAgreements = (int)$pdo->query("SELECT COUNT(*) FROM doctor_agreements WHERE status IN ('pending_signature', 'under_renewal')")->fetchColumn();
    $certsIssued = (int)$pdo->query("SELECT COUNT(*) FROM doctor_agreements WHERE certificate_no IS NOT NULL AND certificate_no != ''")->fetchColumn();
} catch (Throwable $e) {}
?>

<div class="flex h-screen overflow-hidden bg-slate-50 dark:bg-gray-900" 
     x-data="{
         showModal: <?php echo $editAgreement ? 'true' : 'false'; ?>,
         searchQuery: '',
         statusFilter: '',
         partnerFilter: ''
     }">

    <?php require 'includes/sidebar.php'; ?>

    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300 min-w-0">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-8">

            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div>
                    <div class="flex items-center gap-3">
                        <span class="w-10 h-10 rounded-2xl bg-teal-50 dark:bg-teal-900/40 text-[#0F8B8D] flex items-center justify-center text-lg shadow-sm">
                            <i class="fa-solid fa-file-contract"></i>
                        </span>
                        <div>
                            <h3 class="text-2xl md:text-3xl font-black text-gray-800 dark:text-white">
                                Doctor Agreements & Certificates
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                Upload partner MOUs, manage empanelment contracts, and auto-generate QR-verified Authorized Doctor Certificates.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <a href="healthcare_directory.php" class="bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 hover:bg-slate-50 dark:hover:bg-gray-700 font-bold px-3.5 py-2.5 rounded-2xl shadow-sm text-xs flex items-center gap-1.5 transition">
                        <i class="fa-solid fa-hospital-user text-[#0F8B8D]"></i> Healthcare Directory
                    </a>
                    <button type="button" @click="showModal = true" class="bg-[#0F8B8D] hover:bg-[#0c7274] text-white font-bold py-2.5 px-4 rounded-2xl shadow-md text-xs flex items-center gap-1.5 transition transform active:scale-95">
                        <i class="fa-solid fa-cloud-arrow-up"></i> Upload Doctor Agreement
                    </button>
                </div>
            </div>

            <!-- Flash Banners -->
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
                <!-- Total Agreements -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase text-gray-400">Total Agreements</span>
                        <span class="w-9 h-9 rounded-xl bg-teal-50 dark:bg-teal-900/30 text-[#0F8B8D] flex items-center justify-center text-sm">
                            <i class="fa-solid fa-handshake"></i>
                        </span>
                    </div>
                    <div class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white mt-2"><?php echo $totalAgreements; ?></div>
                    <div class="text-[11px] text-gray-500 mt-1">Doctor & Clinic partnerships</div>
                </div>

                <!-- Active Partnerships -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase text-emerald-600">Active Empanelments</span>
                        <span class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 flex items-center justify-center text-sm">
                            <i class="fa-solid fa-circle-check"></i>
                        </span>
                    </div>
                    <div class="text-2xl sm:text-3xl font-black text-emerald-600 dark:text-emerald-400 mt-2"><?php echo $activeAgreements; ?></div>
                    <div class="text-[11px] text-gray-500 mt-1">Live active healthcare partners</div>
                </div>

                <!-- Pending Signature / Renewal -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase text-amber-500">Under Renewal / Pending</span>
                        <span class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-900/30 text-amber-500 flex items-center justify-center text-sm">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                        </span>
                    </div>
                    <div class="text-2xl sm:text-3xl font-black text-amber-600 dark:text-amber-400 mt-2"><?php echo $pendingAgreements; ?></div>
                    <div class="text-[11px] text-gray-500 mt-1">Pending signature or renewal</div>
                </div>

                <!-- Issued Certificates -->
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase text-sky-500">QR Certificates</span>
                        <span class="w-9 h-9 rounded-xl bg-sky-50 dark:bg-sky-900/30 text-sky-500 flex items-center justify-center text-sm">
                            <i class="fa-solid fa-certificate"></i>
                        </span>
                    </div>
                    <div class="text-2xl sm:text-3xl font-black text-sky-600 dark:text-sky-400 mt-2"><?php echo $certsIssued; ?></div>
                    <div class="text-[11px] text-gray-500 mt-1">Authorized Doctor Certificates</div>
                </div>
            </div>

            <!-- Main Table Container -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl border border-slate-200 dark:border-gray-700 shadow-sm overflow-hidden">
                
                <!-- Table Controls Header -->
                <div class="p-5 border-b border-slate-100 dark:border-gray-700 bg-slate-50/50 dark:bg-gray-900/30">
                    <form method="GET" action="doctor_agreements.php" class="flex flex-col md:flex-row items-center justify-between gap-4">
                        <div class="flex flex-1 items-center gap-3 w-full">
                            <div class="relative flex-1">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                                </span>
                                <input type="text" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>"
                                       placeholder="Search agreements, doctors, hospital, speciality..."
                                       class="w-full pl-9 pr-4 py-2 bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                            </div>

                            <select name="status" onchange="this.form.submit()" class="px-3 py-2 bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                                <option value="">All Statuses</option>
                                <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="pending_signature" <?php echo $statusFilter === 'pending_signature' ? 'selected' : ''; ?>>Pending Signature</option>
                                <option value="under_renewal" <?php echo $statusFilter === 'under_renewal' ? 'selected' : ''; ?>>Under Renewal</option>
                                <option value="expired" <?php echo $statusFilter === 'expired' ? 'selected' : ''; ?>>Expired</option>
                                <option value="terminated" <?php echo $statusFilter === 'terminated' ? 'selected' : ''; ?>>Terminated</option>
                            </select>

                            <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 text-white hover:bg-slate-900 text-xs font-bold transition">
                                Filter
                            </button>
                            <?php if ($searchQuery || $statusFilter): ?>
                                <a href="doctor_agreements.php" class="px-3 py-2 rounded-xl bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs font-bold hover:bg-gray-200 transition">
                                    Reset
                                </a>
                            <?php endif; ?>
                        </div>

                        <div class="flex items-center gap-3 shrink-0">
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-teal-100 text-[#0F8B8D] dark:bg-teal-900/40 dark:text-teal-300">
                                <?php echo $totalAgreements; ?> records
                            </span>
                            <a href="../verify-doctor-certificate.php" target="_blank" class="text-xs font-bold text-[#0F8B8D] hover:underline flex items-center gap-1 shrink-0">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i> Public Portal
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Table Content -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-gray-700 bg-slate-50/75 dark:bg-gray-800 text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                                <th class="py-3.5 px-4 sm:px-6">Agreement No & Title</th>
                                <th class="py-3.5 px-4">Doctor / Healthcare Partner</th>
                                <th class="py-3.5 px-4">Concession Terms</th>
                                <th class="py-3.5 px-4">Validity</th>
                                <th class="py-3.5 px-4">Agreement Doc</th>
                                <th class="py-3.5 px-4">Official Certificate</th>
                                <th class="py-3.5 px-4 sm:px-6 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-gray-700 text-xs text-gray-700 dark:text-gray-300">
                            <?php if (empty($agreements)): ?>
                                <tr>
                                    <td colspan="7" class="py-12 text-center text-gray-400">
                                        <i class="fa-solid fa-file-signature text-3xl mb-2 block opacity-40"></i>
                                        <span>No doctor agreements registered yet. Click "Upload Doctor Agreement" to begin.</span>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($agreements as $a): ?>
                                    <tr class="hover:bg-slate-50/80 dark:hover:bg-gray-700/40 transition">
                                        
                                        <!-- Agreement Number & Title -->
                                        <td class="py-4 px-4 sm:px-6 whitespace-nowrap">
                                            <div class="font-mono font-bold text-[#0F8B8D] dark:text-teal-400"><?php echo htmlspecialchars($a['agreement_no']); ?></div>
                                            <div class="font-semibold text-gray-900 dark:text-white truncate max-w-xs mt-0.5"><?php echo htmlspecialchars($a['agreement_title']); ?></div>
                                            <div class="mt-1">
                                                <?php
                                                $statusClasses = [
                                                    'active' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
                                                    'pending_signature' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
                                                    'under_renewal' => 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-300',
                                                    'expired' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300',
                                                    'terminated' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
                                                ];
                                                $badgeClass = $statusClasses[$a['status']] ?? 'bg-gray-100 text-gray-800';
                                                ?>
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider <?php echo $badgeClass; ?>">
                                                    <?php echo str_replace('_', ' ', $a['status']); ?>
                                                </span>
                                            </div>
                                        </td>

                                        <!-- Doctor / Partner -->
                                        <td class="py-4 px-4">
                                            <div class="font-bold text-gray-900 dark:text-white">
                                                <?php echo htmlspecialchars(!empty($a['doctor_name']) ? $a['doctor_name'] : $a['partner_name']); ?>
                                            </div>
                                            <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                                                <?php echo htmlspecialchars($a['partner_name']); ?> (<?php echo htmlspecialchars($a['provider_code']); ?>)
                                            </div>
                                            <div class="text-[11px] text-[#0F8B8D] dark:text-teal-400 font-semibold">
                                                <i class="fa-solid fa-stethoscope text-[10px]"></i> <?php echo ucwords(str_replace('_', ' ', $a['speciality'] ?: $a['partner_speciality'])); ?> &bull; <?php echo htmlspecialchars($a['partner_district']); ?>
                                            </div>
                                        </td>

                                        <!-- Concession Terms -->
                                        <td class="py-4 px-4 max-w-xs">
                                            <div class="text-[11px] font-medium text-gray-800 dark:text-gray-200 line-clamp-2">
                                                <?php echo htmlspecialchars($a['discount_terms'] ?: 'Standard Health Card Concession Scheme'); ?>
                                            </div>
                                        </td>

                                        <!-- Validity -->
                                        <td class="py-4 px-4 whitespace-nowrap text-[11px]">
                                            <div class="text-gray-500">From: <strong class="text-gray-800 dark:text-gray-200"><?php echo !empty($a['signed_date']) ? date('d M Y', strtotime($a['signed_date'])) : '—'; ?></strong></div>
                                            <div class="text-gray-500 mt-0.5">Until: <strong class="text-emerald-600 dark:text-emerald-400"><?php echo !empty($a['valid_until']) ? date('d M Y', strtotime($a['valid_until'])) : 'Perpetual'; ?></strong></div>
                                        </td>

                                        <!-- Agreement Document (Uploaded File) -->
                                        <td class="py-4 px-4 whitespace-nowrap">
                                            <?php if (!empty($a['agreement_doc_path']) && file_exists(__DIR__ . '/../../' . ltrim($a['agreement_doc_path'], '/\\'))): ?>
                                                <a href="../<?php echo htmlspecialchars($a['agreement_doc_path']); ?>" target="_blank" class="px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 font-bold text-[11px] transition inline-flex items-center gap-1.5 shadow-sm">
                                                    <i class="fa-solid fa-file-pdf text-rose-500"></i>
                                                    <span>View MOU</span>
                                                    <span class="text-[10px] text-gray-400">(<?php echo htmlspecialchars($a['file_size'] ?: 'PDF'); ?>)</span>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-gray-400 text-[11px] italic">No doc uploaded</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Official Certificate (Auto-generated QR+PDF) -->
                                        <td class="py-4 px-4 whitespace-nowrap">
                                            <div class="space-y-1.5">
                                                <div class="flex items-center gap-1.5">
                                                    <a href="actions/doctor_agreement_logic.php?action=view_certificate&id=<?php echo $a['id']; ?>" target="_blank" class="px-3 py-1.5 rounded-xl bg-teal-50 hover:bg-teal-100 dark:bg-teal-900/40 dark:hover:bg-teal-900/60 text-[#0F8B8D] dark:text-teal-300 font-bold text-[11px] transition inline-flex items-center gap-1.5 border border-teal-200 dark:border-teal-800 shadow-sm">
                                                        <i class="fa-solid fa-certificate text-teal-600"></i>
                                                        <span>View Certificate</span>
                                                    </a>
                                                    
                                                    <a href="actions/doctor_agreement_logic.php?action=download_certificate&id=<?php echo $a['id']; ?>" class="p-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 text-xs transition" title="Download Certificate PDF">
                                                        <i class="fa-solid fa-download"></i>
                                                    </a>
                                                </div>

                                                <?php if (!empty($a['certificate_no'])): ?>
                                                    <div class="text-[10px] font-mono text-gray-400">
                                                        Cert No: <?php echo htmlspecialchars($a['certificate_no']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>

                                        <!-- Actions -->
                                        <td class="py-4 px-4 sm:px-6 whitespace-nowrap text-right space-x-1.5">
                                            <!-- Email Certificate -->
                                            <a href="actions/doctor_agreement_logic.php?action=email_certificate&id=<?php echo $a['id']; ?>&csrf_token=<?php echo htmlspecialchars($csrfToken); ?>" 
                                               class="p-2 rounded-xl text-sky-600 hover:bg-sky-50 dark:hover:bg-sky-900/30 transition text-xs inline-block"
                                               title="Email Official Certificate to Doctor/Partner"
                                               onclick="return confirm('Send official empanelment certificate via email to partner?');">
                                                <i class="fa-solid fa-paper-plane"></i>
                                            </a>

                                            <!-- Edit -->
                                            <a href="doctor_agreements.php?edit=<?php echo $a['id']; ?>" class="p-2 rounded-xl text-gray-600 hover:bg-slate-100 dark:text-gray-300 dark:hover:bg-gray-700 transition text-xs inline-block" title="Edit Agreement">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>

                                            <!-- Delete -->
                                            <form action="actions/doctor_agreement_logic.php" method="POST" class="inline-block" onsubmit="return confirm('Delete this doctor agreement and certificate permanently?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                                                <button type="submit" class="p-2 rounded-xl text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/30 transition text-xs" title="Delete Agreement">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </form>
                                        </td>

                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <?php echo render_admin_pagination($totalAgreements, $page, $perPage, ['search' => $searchQuery, 'status' => $statusFilter]); ?>

            </div>

        </main>
    </div>

    <!-- ════════════════════════════════════════════════════════════ -->
    <!-- UPLOAD & EDIT DOCTOR AGREEMENT MODAL                         -->
    <!-- ════════════════════════════════════════════════════════════ -->
    <div x-show="showModal" 
         x-transition
         class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">

        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl border border-slate-200 dark:border-gray-700 w-full max-w-2xl overflow-hidden animate-fade-in"
             @click.away="showModal = false">

            <!-- Modal Header -->
            <div class="p-6 bg-gradient-to-r from-teal-900 to-[#0F8B8D] text-white flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-2xl bg-white/10 text-white flex items-center justify-center text-lg">
                        <i class="fa-solid fa-file-contract"></i>
                    </span>
                    <div>
                        <h3 class="text-base font-bold text-white">
                            <?php echo $editAgreement ? 'Edit Doctor Agreement' : 'Upload Doctor / Partner Agreement'; ?>
                        </h3>
                        <p class="text-xs text-teal-100">
                            Partner empanelment MOU, concession parameters & certificate auto-issuance
                        </p>
                    </div>
                </div>

                <button type="button" @click="showModal = false" class="text-teal-200 hover:text-white p-2 rounded-xl">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <!-- Modal Form -->
            <form action="actions/doctor_agreement_logic.php" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?php echo (int)($editAgreement['id'] ?? 0); ?>">

                <!-- 1. Partner Selection -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">
                        Healthcare Partner / Hospital / Clinic <span class="text-rose-500">*</span>
                    </label>
                    <select name="partner_id" required class="w-full px-3.5 py-2.5 rounded-2xl bg-slate-50 dark:bg-gray-700 border border-slate-200 dark:border-gray-600 text-xs sm:text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                        <option value="">-- Select Healthcare Provider from Directory --</option>
                        <?php foreach ($providers as $prov): ?>
                            <option value="<?php echo $prov['id']; ?>" <?php echo ($editAgreement && $editAgreement['partner_id'] == $prov['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($prov['name']); ?> [<?php echo htmlspecialchars($prov['provider_code']); ?>] &bull; <?php echo htmlspecialchars($prov['district']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- 2. Agreement Title & Ref Code -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">
                            Agreement Title <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="agreement_title" required
                               value="<?php echo htmlspecialchars($editAgreement['agreement_title'] ?? 'Annual Doctor Empanelment & Free Consultation MOU'); ?>"
                               placeholder="e.g. Annual Doctor Empanelment MOU"
                               class="w-full px-3.5 py-2.5 rounded-2xl bg-slate-50 dark:bg-gray-700 border border-slate-200 dark:border-gray-600 text-xs sm:text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">
                            Agreement Ref Code (Leave blank for auto)
                        </label>
                        <input type="text" name="agreement_no"
                               value="<?php echo htmlspecialchars($editAgreement['agreement_no'] ?? ''); ?>"
                               placeholder="e.g. AGR-DR-2026-001"
                               class="w-full px-3.5 py-2.5 rounded-2xl bg-slate-50 dark:bg-gray-700 border border-slate-200 dark:border-gray-600 text-xs sm:text-sm text-gray-900 dark:text-white font-mono uppercase focus:outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                    </div>
                </div>

                <!-- 3. Doctor Name & Speciality -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">
                            Empanelled Doctor Name
                        </label>
                        <input type="text" name="doctor_name"
                               value="<?php echo htmlspecialchars($editAgreement['doctor_name'] ?? ''); ?>"
                               placeholder="e.g. Dr. Rajesh Sharma, MD"
                               class="w-full px-3.5 py-2.5 rounded-2xl bg-slate-50 dark:bg-gray-700 border border-slate-200 dark:border-gray-600 text-xs sm:text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">
                            Medical Speciality
                        </label>
                        <input type="text" name="speciality"
                               value="<?php echo htmlspecialchars($editAgreement['speciality'] ?? ''); ?>"
                               placeholder="e.g. Ophthalmology / General Medicine / Dental"
                               class="w-full px-3.5 py-2.5 rounded-2xl bg-slate-50 dark:bg-gray-700 border border-slate-200 dark:border-gray-600 text-xs sm:text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                    </div>
                </div>

                <!-- 4. Concession Terms & Discount Offered -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">
                        Authorized Beneficiary Terms & Concessions
                    </label>
                    <input type="text" name="discount_terms"
                           value="<?php echo htmlspecialchars($editAgreement['discount_terms'] ?? '25% Discount on OPD & 100% Free Consultations for Verified Cardholders'); ?>"
                           placeholder="e.g. 25% Discount on OPD & Free checkups for Health Card holders"
                           class="w-full px-3.5 py-2.5 rounded-2xl bg-slate-50 dark:bg-gray-700 border border-slate-200 dark:border-gray-600 text-xs sm:text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                </div>

                <!-- 5. Signed Date, Valid Until & Status -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">
                            Signed Date
                        </label>
                        <input type="date" name="signed_date"
                               value="<?php echo htmlspecialchars($editAgreement['signed_date'] ?? date('Y-m-d')); ?>"
                               class="w-full px-3.5 py-2.5 rounded-2xl bg-slate-50 dark:bg-gray-700 border border-slate-200 dark:border-gray-600 text-xs sm:text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">
                            Valid Until Date
                        </label>
                        <input type="date" name="valid_until"
                               value="<?php echo htmlspecialchars($editAgreement['valid_until'] ?? date('Y-m-d', strtotime('+1 year'))); ?>"
                               class="w-full px-3.5 py-2.5 rounded-2xl bg-slate-50 dark:bg-gray-700 border border-slate-200 dark:border-gray-600 text-xs sm:text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">
                            Status
                        </label>
                        <select name="status" class="w-full px-3.5 py-2.5 rounded-2xl bg-slate-50 dark:bg-gray-700 border border-slate-200 dark:border-gray-600 text-xs sm:text-sm text-gray-900 dark:text-white font-bold focus:outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                            <option value="active" <?php echo ($editAgreement && $editAgreement['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="pending_signature" <?php echo ($editAgreement && $editAgreement['status'] === 'pending_signature') ? 'selected' : ''; ?>>Pending Signature</option>
                            <option value="under_renewal" <?php echo ($editAgreement && $editAgreement['status'] === 'under_renewal') ? 'selected' : ''; ?>>Under Renewal</option>
                            <option value="expired" <?php echo ($editAgreement && $editAgreement['status'] === 'expired') ? 'selected' : ''; ?>>Expired</option>
                            <option value="terminated" <?php echo ($editAgreement && $editAgreement['status'] === 'terminated') ? 'selected' : ''; ?>>Terminated</option>
                        </select>
                    </div>
                </div>

                <!-- 6. Agreement Document Upload -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">
                        Upload Signed Agreement / MOU (PDF, DOC, DOCX - Max 10MB)
                    </label>
                    <input type="file" name="agreement_doc" accept=".pdf,.doc,.docx,.jpg,.png"
                           class="block w-full text-xs text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-teal-50 file:text-[#0F8B8D] dark:file:bg-teal-900/40 dark:file:text-teal-300 hover:file:bg-teal-100 cursor-pointer">
                    <?php if (!empty($editAgreement['agreement_doc_path'])): ?>
                        <div class="text-[11px] text-gray-400 mt-1">Current file: <code class="text-teal-600"><?php echo htmlspecialchars($editAgreement['agreement_doc_path']); ?></code> (Leave empty to keep existing)</div>
                    <?php endif; ?>
                </div>

                <!-- 7. Remarks -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">
                        Internal Notes / Remarks
                    </label>
                    <textarea name="remarks" rows="2" placeholder="Administrative notes regarding agreement execution..."
                              class="w-full px-3.5 py-2.5 rounded-2xl bg-slate-50 dark:bg-gray-700 border border-slate-200 dark:border-gray-600 text-xs sm:text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#0F8B8D]"><?php echo htmlspecialchars($editAgreement['remarks'] ?? ''); ?></textarea>
                </div>

                <!-- Modal Actions -->
                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-gray-700">
                    <button type="button" @click="showModal = false" class="px-5 py-2.5 rounded-2xl text-xs font-bold text-gray-500 hover:text-gray-800 dark:hover:text-white">
                        Cancel
                    </button>
                    <button type="submit" class="px-6 py-2.5 rounded-2xl bg-[#0F8B8D] hover:bg-[#0c7274] text-white font-bold text-xs shadow-md transition">
                        <?php echo $editAgreement ? 'Update & Refresh Certificate' : 'Save Agreement & Generate Certificate'; ?>
                    </button>
                </div>
            </form>

        </div>
    </div>

</div>

<?php require 'includes/footer.php'; ?>
