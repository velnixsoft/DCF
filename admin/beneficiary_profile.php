<?php
require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../includes/india_locations.php';

$csrfToken = generateCsrfToken();
if (!canAccessModule($pdo, 'coordinator', 'page.beneficiaries')) {
    setFlash('error', 'Access denied. Coordinator/Manager/Admin role required.');
    header('Location: dashboard.php');
    exit;
}

// Get Beneficiary ID or Code
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$code = cleanInput($_GET['code'] ?? '');

if (!$id && empty($code)) {
    setFlash('error', 'Beneficiary identifier is missing.');
    header('Location: beneficiaries.php');
    exit;
}

// Fetch Beneficiary Details
if ($id) {
    $stmt = $pdo->prepare("
        SELECT b.*, 
               c.category_name, c.icon AS category_icon, c.description AS category_desc,
               p.title AS project_title,
               u.name AS registered_by_name, u.email AS registered_by_email,
               coord.name AS coordinator_name
        FROM beneficiaries b
        LEFT JOIN beneficiary_categories c ON b.category_id = c.id
        LEFT JOIN projects p ON b.project_id = p.id
        LEFT JOIN users u ON b.registered_by = u.id
        LEFT JOIN users coord ON b.coordinator_id = coord.id
        WHERE b.id = ?
        LIMIT 1
    ");
    $stmt->execute([$id]);
} else {
    $stmt = $pdo->prepare("
        SELECT b.*, 
               c.category_name, c.icon AS category_icon, c.description AS category_desc,
               p.title AS project_title,
               u.name AS registered_by_name, u.email AS registered_by_email,
               coord.name AS coordinator_name
        FROM beneficiaries b
        LEFT JOIN beneficiary_categories c ON b.category_id = c.id
        LEFT JOIN projects p ON b.project_id = p.id
        LEFT JOIN users u ON b.registered_by = u.id
        LEFT JOIN users coord ON b.coordinator_id = coord.id
        WHERE b.beneficiary_code = ?
        LIMIT 1
    ");
    $stmt->execute([$code]);
}

$beneficiary = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$beneficiary) {
    setFlash('error', 'Beneficiary record not found.');
    header('Location: beneficiaries.php');
    exit;
}

$benId = (int)$beneficiary['id'];
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$isManagerOrAdmin = checkRole($pdo, 'manager');

// Fetch Categories for Edit Modal
$categories = $pdo->query("SELECT * FROM beneficiary_categories WHERE is_active = 1 ORDER BY display_order ASC, category_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch Coordinators for Admin/Manager selection
$coordinators = $pdo->query("SELECT id, name, coordinator_code, role FROM users ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch Assistance History Timeline with RBAC scoping
if ($isManagerOrAdmin) {
    // Admin / Manager: Sees all assistance records for this beneficiary
    $stmtAid = $pdo->prepare("
        SELECT ast.*,
               u.name AS coordinator_user_name,
               p.title AS project_name,
               itd.item_description AS linked_item_donation_title
        FROM beneficiary_assistance_history ast
        LEFT JOIN users u ON ast.coordinator_id = u.id
        LEFT JOIN projects p ON ast.project_id = p.id
        LEFT JOIN item_donations itd ON ast.item_donation_id = itd.id
        WHERE ast.beneficiary_id = ?
        ORDER BY ast.date DESC, ast.created_at DESC
    ");
    $stmtAid->execute([$benId]);
} else {
    // Coordinator: Sees only assistance records logged by themselves
    $stmtAid = $pdo->prepare("
        SELECT ast.*,
               u.name AS coordinator_user_name,
               p.title AS project_name,
               itd.item_description AS linked_item_donation_title
        FROM beneficiary_assistance_history ast
        LEFT JOIN users u ON ast.coordinator_id = u.id
        LEFT JOIN projects p ON ast.project_id = p.id
        LEFT JOIN item_donations itd ON ast.item_donation_id = itd.id
        WHERE ast.beneficiary_id = ? AND ast.coordinator_id = ?
        ORDER BY ast.date DESC, ast.created_at DESC
    ");
    $stmtAid->execute([$benId, $currentUserId]);
}
$assistanceHistory = $stmtAid->fetchAll(PDO::FETCH_ASSOC);

// Calculate Aggregates
$totalAidEvents = count($assistanceHistory);
$totalFinancialAmount = 0.00;
$totalInKindValuation = 0.00;
$aidTypeCounts = [];

foreach ($assistanceHistory as $aid) {
    $totalFinancialAmount += (float)$aid['amount'];
    $totalInKindValuation += (float)$aid['estimated_value'];
    $type = $aid['assistance_type'] ?: 'Other';
    $aidTypeCounts[$type] = ($aidTypeCounts[$type] ?? 0) + 1;
}

$totalCombinedValuation = $totalFinancialAmount + $totalInKindValuation;

require 'includes/header.php';
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900" x-data="{ openImageModal: false, activeImage: '' }">
    <?php require 'includes/sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6">
            <!-- Breadcrumbs & Top Navigation -->
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-6">
                <div>
                    <nav class="flex items-center gap-2 text-xs font-semibold text-gray-400 mb-1">
                        <a href="dashboard.php" class="hover:text-teal-600 transition">Dashboard</a>
                        <i class="fa-solid fa-chevron-right text-[10px]"></i>
                        <a href="beneficiaries.php" class="hover:text-teal-600 transition">Beneficiary Management</a>
                        <i class="fa-solid fa-chevron-right text-[10px]"></i>
                        <span class="text-gray-700 dark:text-gray-200"><?php echo htmlspecialchars($beneficiary['name']); ?></span>
                    </nav>
                    <div class="flex flex-wrap items-center gap-3">
                        <h3 class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-white">
                            <?php echo htmlspecialchars($beneficiary['name']); ?>
                        </h3>
                        <span class="font-mono text-xs font-bold text-teal-700 dark:text-teal-300 bg-teal-100 dark:bg-teal-900/40 px-2.5 py-1 rounded-lg">
                            <?php echo htmlspecialchars($beneficiary['beneficiary_code'] ?? ('BEN-' . $benId)); ?>
                        </span>
                        <?php
                        $statusClass = match ($beneficiary['status']) {
                            'Active' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
                            'Assisted' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
                            'Under Review' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                            default => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
                        };
                        ?>
                        <span class="px-3 py-1 rounded-full text-xs font-bold <?php echo $statusClass; ?>">
                            <i class="fa-solid fa-circle text-[8px] mr-1"></i> <?php echo htmlspecialchars($beneficiary['status']); ?>
                        </span>
                    </div>
                </div>

                <!-- Top Quick Actions -->
                <div class="flex flex-wrap items-center gap-2.5">
                    <button type="button" onclick="openProfileAidModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-4 py-2.5 rounded-xl shadow-sm text-sm flex items-center gap-2 transition transform active:scale-95">
                        <i class="fa-solid fa-hand-holding-heart"></i>
                        <span>Log Assistance</span>
                    </button>
                    <button type="button" onclick="openProfileEditModal()" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2.5 rounded-xl shadow-sm text-sm flex items-center gap-2 transition transform active:scale-95">
                        <i class="fa-solid fa-user-pen"></i>
                        <span>Edit Profile</span>
                    </button>
                    <button type="button" onclick="window.print()" class="bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 font-medium px-4 py-2.5 rounded-xl text-sm flex items-center gap-2 transition">
                        <i class="fa-solid fa-print"></i>
                        <span>Print Dossier</span>
                    </button>
                    <a href="beneficiaries.php" class="bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 p-2.5 rounded-xl text-sm flex items-center justify-center transition" title="Back to List">
                        <i class="fa-solid fa-arrow-left"></i>
                    </a>
                </div>
            </div>

            <!-- Summary KPI Widgets -->
            <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white dark:bg-dark-card p-4 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-xl flex-shrink-0">
                        <i class="fa-solid fa-box-open"></i>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Total Aid Handed</p>
                        <h4 class="text-2xl font-bold text-gray-800 dark:text-white"><?php echo number_format($totalAidEvents); ?> Events</h4>
                    </div>
                </div>

                <div class="bg-white dark:bg-dark-card p-4 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-xl flex-shrink-0">
                        <i class="fa-solid fa-money-bill-wave"></i>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Financial Aid</p>
                        <h4 class="text-xl font-bold text-emerald-600 dark:text-emerald-400">₹<?php echo number_format($totalFinancialAmount, 2); ?></h4>
                    </div>
                </div>

                <div class="bg-white dark:bg-dark-card p-4 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 flex items-center justify-center text-xl flex-shrink-0">
                        <i class="fa-solid fa-wheat-awn"></i>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">In-Kind Valuation</p>
                        <h4 class="text-xl font-bold text-purple-600 dark:text-purple-400">₹<?php echo number_format($totalInKindValuation, 2); ?></h4>
                    </div>
                </div>

                <div class="bg-white dark:bg-dark-card p-4 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-teal-50 dark:bg-teal-900/30 text-teal-600 dark:text-teal-400 flex items-center justify-center text-xl flex-shrink-0">
                        <i class="fa-solid fa-scale-balanced"></i>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Combined Value</p>
                        <h4 class="text-xl font-bold text-teal-600 dark:text-teal-400">₹<?php echo number_format($totalCombinedValuation, 2); ?></h4>
                    </div>
                </div>
            </div>

            <!-- Main Profile Layout Grid (Left: Demographics / Right: Assistance Timeline) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- LEFT COLUMN: Beneficiary Demographics & Dossier (4 cols) -->
                <div class="lg:col-span-4 space-y-6">
                    <!-- Profile Card -->
                    <div class="bg-white dark:bg-dark-card rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
                        <div class="h-28 bg-gradient-to-r from-teal-500 to-emerald-600 relative"></div>
                        <div class="px-6 pb-6 pt-0 relative">
                            <!-- Photo Avatar -->
                            <div class="-mt-14 mb-4 flex justify-between items-end">
                                <div class="relative group cursor-pointer" @click="activeImage = '<?php echo $beneficiary['photo'] ? ('../' . $beneficiary['photo']) : ''; ?>'; if(activeImage) openImageModal = true;">
                                    <img src="<?php echo $beneficiary['photo'] ? ('../' . htmlspecialchars($beneficiary['photo'])) : ('https://ui-avatars.com/api/?name=' . urlencode($beneficiary['name']) . '&size=160&background=0F8B8D&color=fff'); ?>" 
                                         alt="<?php echo htmlspecialchars($beneficiary['name']); ?>"
                                         class="w-24 h-24 rounded-2xl object-cover border-4 border-white dark:border-gray-900 shadow-md">
                                    <?php if ($beneficiary['photo']): ?>
                                        <div class="absolute inset-0 bg-black/40 rounded-2xl flex items-center justify-center text-white opacity-0 group-hover:opacity-100 transition">
                                            <i class="fa-solid fa-expand text-lg"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="text-right">
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                        <i class="fa-solid <?php echo htmlspecialchars($beneficiary['category_icon'] ?? 'fa-tag'); ?>"></i>
                                        <span><?php echo htmlspecialchars($beneficiary['category_name'] ?? 'General'); ?></span>
                                    </span>
                                </div>
                            </div>

                            <h4 class="text-xl font-bold text-gray-800 dark:text-white">
                                <?php echo htmlspecialchars($beneficiary['name']); ?>
                            </h4>
                            <p class="text-xs font-mono font-bold text-teal-600 dark:text-teal-400 mt-0.5">
                                <?php echo htmlspecialchars($beneficiary['beneficiary_code'] ?? ('BEN-' . $benId)); ?>
                            </p>

                            <!-- Personal Details List -->
                            <div class="mt-5 space-y-3 text-xs border-t border-gray-100 dark:border-gray-800 pt-4">
                                <div class="flex justify-between py-1">
                                    <span class="text-gray-400">Gender / Age:</span>
                                    <span class="font-semibold text-gray-700 dark:text-gray-200">
                                        <?php echo htmlspecialchars($beneficiary['gender'] ?? 'N/A'); ?><?php echo $beneficiary['age'] ? (', ' . $beneficiary['age'] . ' yrs') : ''; ?>
                                    </span>
                                </div>

                                <?php if (!empty($beneficiary['dob'])): ?>
                                    <div class="flex justify-between py-1">
                                        <span class="text-gray-400">Date of Birth:</span>
                                        <span class="font-semibold text-gray-700 dark:text-gray-200"><?php echo date('d M Y', strtotime($beneficiary['dob'])); ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($beneficiary['father_or_spouse_name'])): ?>
                                    <div class="flex justify-between py-1">
                                        <span class="text-gray-400">Father / Spouse:</span>
                                        <span class="font-semibold text-gray-700 dark:text-gray-200"><?php echo htmlspecialchars($beneficiary['father_or_spouse_name']); ?></span>
                                    </div>
                                <?php endif; ?>

                                <div class="flex justify-between py-1">
                                    <span class="text-gray-400">Beneficiary Type:</span>
                                    <span class="font-semibold text-gray-700 dark:text-gray-200"><?php echo htmlspecialchars($beneficiary['beneficiary_type'] ?? 'General / BPL'); ?></span>
                                </div>

                                <div class="flex justify-between py-1">
                                    <span class="text-gray-400">Family Members:</span>
                                    <span class="font-semibold text-gray-700 dark:text-gray-200"><?php echo (int)($beneficiary['family_members_count'] ?? 1); ?> Person(s)</span>
                                </div>

                                <?php if (!empty($beneficiary['annual_income'])): ?>
                                    <div class="flex justify-between py-1">
                                        <span class="text-gray-400">Annual Income:</span>
                                        <span class="font-semibold text-gray-700 dark:text-gray-200">₹<?php echo number_format((float)$beneficiary['annual_income'], 2); ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if ($beneficiary['disability_status'] === 'Yes'): ?>
                                    <div class="p-2.5 rounded-xl bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300 border border-purple-100 dark:border-purple-800">
                                        <p class="font-bold flex items-center gap-1.5">
                                            <i class="fa-solid fa-wheelchair"></i> Divyang (Special Physical Need)
                                        </p>
                                        <p class="text-[11px] mt-0.5 text-purple-600 dark:text-purple-400">
                                            <?php echo htmlspecialchars($beneficiary['disability_details'] ?? 'Assistance required for mobility/health'); ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Contact & Identity Proofs -->
                    <div class="bg-white dark:bg-dark-card p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800 space-y-4">
                        <h5 class="text-xs font-bold uppercase tracking-wider text-teal-600 dark:text-teal-400 flex items-center gap-2">
                            <i class="fa-solid fa-address-book"></i> Contact & Identity Proofs
                        </h5>

                        <div class="space-y-3 text-xs">
                            <div class="flex items-center justify-between p-3 rounded-2xl bg-gray-50 dark:bg-gray-800/60">
                                <div class="flex items-center gap-3">
                                    <span class="w-8 h-8 rounded-xl bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300 flex items-center justify-center">
                                        <i class="fa-solid fa-phone"></i>
                                    </span>
                                    <div>
                                        <p class="text-[10px] text-gray-400 uppercase">Primary Mobile</p>
                                        <p class="font-bold text-gray-800 dark:text-white"><?php echo htmlspecialchars($beneficiary['contact']); ?></p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <a href="tel:<?php echo htmlspecialchars($beneficiary['contact']); ?>" class="p-2 rounded-lg bg-white dark:bg-gray-700 text-teal-600 hover:shadow-sm" title="Call">
                                        <i class="fa-solid fa-phone text-xs"></i>
                                    </a>
                                    <a href="https://wa.me/91<?php echo preg_replace('/\D/', '', $beneficiary['contact']); ?>" target="_blank" class="p-2 rounded-lg bg-emerald-50 text-emerald-600 hover:shadow-sm" title="WhatsApp">
                                        <i class="fa-brands fa-whatsapp text-xs"></i>
                                    </a>
                                </div>
                            </div>

                            <?php if (!empty($beneficiary['alternate_contact'])): ?>
                                <div class="flex items-center gap-3 p-3 rounded-2xl bg-gray-50 dark:bg-gray-800/60">
                                    <span class="w-8 h-8 rounded-xl bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 flex items-center justify-center">
                                        <i class="fa-solid fa-phone-volume"></i>
                                    </span>
                                    <div>
                                        <p class="text-[10px] text-gray-400 uppercase">Alternate Phone</p>
                                        <p class="font-bold text-gray-800 dark:text-white"><?php echo htmlspecialchars($beneficiary['alternate_contact']); ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($beneficiary['email'])): ?>
                                <div class="flex items-center gap-3 p-3 rounded-2xl bg-gray-50 dark:bg-gray-800/60">
                                    <span class="w-8 h-8 rounded-xl bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300 flex items-center justify-center">
                                        <i class="fa-solid fa-envelope"></i>
                                    </span>
                                    <div>
                                        <p class="text-[10px] text-gray-400 uppercase">Email Address</p>
                                        <p class="font-bold text-gray-800 dark:text-white"><?php echo htmlspecialchars($beneficiary['email']); ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($beneficiary['aadhar_no'])): ?>
                                <div class="flex items-center justify-between p-3 rounded-2xl bg-gray-50 dark:bg-gray-800/60">
                                    <div class="flex items-center gap-3">
                                        <span class="w-8 h-8 rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300 flex items-center justify-center">
                                            <i class="fa-solid fa-id-badge"></i>
                                        </span>
                                        <div>
                                            <p class="text-[10px] text-gray-400 uppercase">Aadhaar Card No.</p>
                                            <p class="font-mono font-bold text-gray-800 dark:text-white"><?php echo htmlspecialchars($beneficiary['aadhar_no']); ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($beneficiary['ration_card_no'])): ?>
                                <div class="flex items-center justify-between p-3 rounded-2xl bg-gray-50 dark:bg-gray-800/60">
                                    <div class="flex items-center gap-3">
                                        <span class="w-8 h-8 rounded-xl bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300 flex items-center justify-center">
                                            <i class="fa-solid fa-wheat-awn"></i>
                                        </span>
                                        <div>
                                            <p class="text-[10px] text-gray-400 uppercase">Ration Card ID</p>
                                            <p class="font-mono font-bold text-gray-800 dark:text-white"><?php echo htmlspecialchars($beneficiary['ration_card_no']); ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($beneficiary['id_proof_doc'])): ?>
                                <a href="../<?php echo htmlspecialchars($beneficiary['id_proof_doc']); ?>" target="_blank" class="flex items-center justify-between p-3 rounded-2xl bg-teal-50 dark:bg-teal-900/20 text-teal-700 dark:text-teal-300 hover:bg-teal-100 transition">
                                    <div class="flex items-center gap-3">
                                        <i class="fa-solid fa-file-pdf text-lg text-teal-600"></i>
                                        <span class="font-bold">View Identity Proof Document</span>
                                    </div>
                                    <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Address & Location -->
                    <div class="bg-white dark:bg-dark-card p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800 space-y-3">
                        <h5 class="text-xs font-bold uppercase tracking-wider text-teal-600 dark:text-teal-400 flex items-center gap-2">
                            <i class="fa-solid fa-map-location-dot"></i> Residential Location
                        </h5>

                        <div class="text-xs space-y-2 text-gray-600 dark:text-gray-300">
                            <p class="font-medium text-gray-800 dark:text-white leading-relaxed">
                                <?php echo nl2br(htmlspecialchars($beneficiary['address'])); ?>
                            </p>
                            <div class="grid grid-cols-2 gap-2 pt-2 border-t border-gray-100 dark:border-gray-800 text-[11px]">
                                <div>
                                    <span class="text-gray-400 block">District</span>
                                    <span class="font-semibold text-gray-700 dark:text-gray-200"><?php echo htmlspecialchars($beneficiary['district']); ?></span>
                                </div>
                                <div>
                                    <span class="text-gray-400 block">State</span>
                                    <span class="font-semibold text-gray-700 dark:text-gray-200"><?php echo htmlspecialchars($beneficiary['state']); ?></span>
                                </div>
                                <?php if (!empty($beneficiary['block'])): ?>
                                    <div>
                                        <span class="text-gray-400 block">Block / Tehsil</span>
                                        <span class="font-semibold text-gray-700 dark:text-gray-200"><?php echo htmlspecialchars($beneficiary['block']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($beneficiary['pincode'])): ?>
                                    <div>
                                        <span class="text-gray-400 block">PIN Code</span>
                                        <span class="font-semibold text-gray-700 dark:text-gray-200"><?php echo htmlspecialchars($beneficiary['pincode']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Administrative Metadata -->
                        <div class="pt-3 border-t border-gray-100 dark:border-gray-800 text-[10px] text-gray-400 space-y-1">
                            <p>Registered on: <span class="font-semibold text-gray-600 dark:text-gray-300"><?php echo date('d M Y', strtotime($beneficiary['registration_date'])); ?></span></p>
                            <?php if (!empty($beneficiary['registered_by_name'])): ?>
                                <p>Enrolled By: <span class="font-semibold text-gray-600 dark:text-gray-300"><?php echo htmlspecialchars($beneficiary['registered_by_name']); ?></span></p>
                            <?php endif; ?>
                            <?php if (!empty($beneficiary['remarks'])): ?>
                                <p class="italic mt-2 p-2 bg-gray-50 dark:bg-gray-800 rounded-xl text-gray-500">"<?php echo htmlspecialchars($beneficiary['remarks']); ?>"</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN: Assistance History Timeline (Activity Feed) (8 cols) -->
                <div class="lg:col-span-8 space-y-6">
                    <div class="bg-white dark:bg-dark-card p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 pb-5 border-b border-gray-100 dark:border-gray-800">
                            <div>
                                <h4 class="text-lg font-bold text-gray-800 dark:text-white flex items-center gap-2">
                                    <span class="p-2 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 text-sm">
                                        <i class="fa-solid fa-timeline"></i>
                                    </span>
                                    Assistance History Timeline
                                </h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Chronological activity feed of all aid, food kits, funds, and welfare items provided</p>
                            </div>

                            <button type="button" onclick="openProfileAidModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold px-3.5 py-2 rounded-xl shadow-sm flex items-center gap-1.5 transition">
                                <i class="fa-solid fa-plus"></i>
                                <span>Log New Aid</span>
                            </button>
                        </div>

                        <!-- TIMELINE ACTIVITY FEED -->
                        <?php if (empty($assistanceHistory)): ?>
                            <div class="py-16 text-center">
                                <div class="w-16 h-16 rounded-3xl bg-indigo-50 dark:bg-indigo-900/20 text-indigo-500 flex items-center justify-center text-2xl mx-auto mb-3 shadow-inner">
                                    <i class="fa-solid fa-hand-holding-heart"></i>
                                </div>
                                <h5 class="text-base font-bold text-gray-800 dark:text-white">No Assistance Records Logged Yet</h5>
                                <p class="text-xs text-gray-400 max-w-sm mx-auto mt-1 mb-5">Start by recording the first ration kit, financial grant, medical supplies, or educational materials distributed to this beneficiary.</p>
                                <button type="button" onclick="openProfileAidModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold px-5 py-2.5 rounded-xl shadow transition">
                                    <i class="fa-solid fa-plus mr-1"></i> Record First Aid Handover
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="relative pl-6 sm:pl-8 mt-6 space-y-8 before:absolute before:left-3 sm:before:left-4 before:top-3 before:bottom-3 before:w-0.5 before:bg-gradient-to-b before:from-indigo-500 before:via-teal-400 before:to-gray-200 dark:before:to-gray-800">
                                <?php foreach ($assistanceHistory as $index => $aid): 
                                    // Visual color styles depending on aid type
                                    $typeDetails = match ($aid['assistance_type']) {
                                        'Financial Aid' => ['icon' => 'fa-indian-rupee-sign', 'color' => 'bg-emerald-500', 'badge' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'],
                                        'Medical Aid' => ['icon' => 'fa-kit-medical', 'color' => 'bg-rose-500', 'badge' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300'],
                                        'Educational Support' => ['icon' => 'fa-graduation-cap', 'color' => 'bg-blue-500', 'badge' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300'],
                                        'Ration & Food Kit' => ['icon' => 'fa-wheat-awn', 'color' => 'bg-amber-500', 'badge' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'],
                                        'Clothing & Blankets' => ['icon' => 'fa-shirt', 'color' => 'bg-indigo-500', 'badge' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300'],
                                        'Mobility & Assistive Devices' => ['icon' => 'fa-wheelchair', 'color' => 'bg-purple-500', 'badge' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300'],
                                        'Shelter & Housing' => ['icon' => 'fa-house', 'color' => 'bg-teal-500', 'badge' => 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300'],
                                        'Skill Training & Livelihood' => ['icon' => 'fa-briefcase', 'color' => 'bg-cyan-500', 'badge' => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/40 dark:text-cyan-300'],
                                        default => ['icon' => 'fa-gift', 'color' => 'bg-indigo-500', 'badge' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300']
                                    };
                                ?>
                                    <div class="relative group">
                                        <!-- Timeline Node Circle -->
                                        <div class="absolute -left-[30px] sm:-left-[38px] top-1.5 w-6 h-6 sm:w-7 sm:h-7 rounded-full <?php echo $typeDetails['color']; ?> text-white flex items-center justify-center text-xs shadow-md border-2 border-white dark:border-gray-900 z-10 transition-transform group-hover:scale-110">
                                            <i class="fa-solid <?php echo $typeDetails['icon']; ?> text-[10px] sm:text-xs"></i>
                                        </div>

                                        <!-- Timeline Card Content -->
                                        <div class="bg-gray-50/70 dark:bg-gray-800/60 rounded-2xl p-4 sm:p-5 border border-gray-100 dark:border-gray-700/60 hover:shadow-md hover:border-indigo-200 dark:hover:border-indigo-800 transition">
                                            <!-- Card Header -->
                                            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 mb-3">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold <?php echo $typeDetails['badge']; ?>">
                                                        <i class="fa-solid <?php echo $typeDetails['icon']; ?> mr-1"></i>
                                                        <?php echo htmlspecialchars($aid['assistance_type']); ?>
                                                    </span>
                                                    <span class="font-mono text-xs font-bold text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-700 px-2 py-0.5 rounded border border-gray-200 dark:border-gray-600">
                                                        <?php echo htmlspecialchars($aid['assistance_code'] ?? ('AST-' . $aid['id'])); ?>
                                                    </span>
                                                    <span class="text-xs text-emerald-600 font-bold bg-emerald-50 dark:bg-emerald-900/30 px-2 py-0.5 rounded">
                                                        <?php echo htmlspecialchars($aid['status'] ?? 'Distributed'); ?>
                                                    </span>
                                                </div>

                                                <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                                                    <span class="flex items-center gap-1 font-semibold text-gray-700 dark:text-gray-300">
                                                        <i class="fa-solid fa-calendar-day text-indigo-500"></i>
                                                        <?php echo date('d M Y', strtotime($aid['date'])); ?>
                                                    </span>

                                                    <!-- Delete Aid Record Button -->
                                                    <form action="actions/beneficiary_logic.php" method="POST" onsubmit="return confirm('Remove this assistance record permanently?');" class="inline">
                                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                        <input type="hidden" name="action" value="delete_assistance">
                                                        <input type="hidden" name="assistance_id" value="<?php echo (int)$aid['id']; ?>">
                                                        <input type="hidden" name="beneficiary_id" value="<?php echo $benId; ?>">
                                                        <button type="submit" class="text-gray-400 hover:text-red-600 transition p-1" title="Delete record">
                                                            <i class="fa-solid fa-trash-can text-xs"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>

                                            <!-- Description -->
                                            <p class="text-sm font-medium text-gray-800 dark:text-gray-100 leading-relaxed">
                                                <?php echo nl2br(htmlspecialchars($aid['description'])); ?>
                                            </p>

                                            <!-- Valuation & Items Details Pill Matrix -->
                                            <div class="mt-3 flex flex-wrap gap-2 text-xs">
                                                <?php if ((float)$aid['amount'] > 0): ?>
                                                    <div class="px-3 py-1.5 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300 font-bold flex items-center gap-1.5 border border-emerald-100 dark:border-emerald-800">
                                                        <i class="fa-solid fa-indian-rupee-sign"></i>
                                                        <span>Cash Grant: ₹<?php echo number_format((float)$aid['amount'], 2); ?></span>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if ((float)$aid['estimated_value'] > 0 || !empty($aid['items_detail']) || (float)$aid['quantity'] > 1): ?>
                                                    <div class="px-3 py-1.5 rounded-xl bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300 font-medium flex items-center gap-1.5 border border-indigo-100 dark:border-indigo-800">
                                                        <i class="fa-solid fa-boxes-stacked"></i>
                                                        <span>Quantity: <?php echo (float)$aid['quantity'] . ' ' . htmlspecialchars($aid['unit'] ?? 'units'); ?></span>
                                                        <?php if ((float)$aid['estimated_value'] > 0): ?>
                                                            <span class="font-bold text-indigo-900 dark:text-indigo-200">(Valued: ₹<?php echo number_format((float)$aid['estimated_value'], 2); ?>)</span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if (!empty($aid['receipt_no'])): ?>
                                                    <div class="px-3 py-1.5 rounded-xl bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-mono text-[11px] flex items-center gap-1.5">
                                                        <i class="fa-solid fa-receipt text-gray-400"></i>
                                                        <span>Voucher: <?php echo htmlspecialchars($aid['receipt_no']); ?></span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Proof Photo & Handover Metadata Footer -->
                                            <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs text-gray-500 dark:text-gray-400">
                                                <div class="space-y-1">
                                                    <?php if (!empty($aid['distribution_location'])): ?>
                                                        <p class="flex items-center gap-1.5">
                                                            <i class="fa-solid fa-location-dot text-teal-600"></i>
                                                            <span>Location: <strong><?php echo htmlspecialchars($aid['distribution_location']); ?></strong></span>
                                                        </p>
                                                    <?php endif; ?>

                                                    <?php if (!empty($aid['given_by']) || !empty($aid['coordinator_user_name'])): ?>
                                                        <p class="flex items-center gap-1.5">
                                                            <i class="fa-solid fa-user-check text-indigo-500"></i>
                                                            <span>Handed over by: <strong><?php echo htmlspecialchars($aid['given_by'] ?: $aid['coordinator_user_name']); ?></strong></span>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>

                                                <!-- Handover Proof Photo Thumbnail -->
                                                <?php if (!empty($aid['proof_photo'])): ?>
                                                    <div class="flex items-center gap-2 cursor-pointer" @click="activeImage = '../<?php echo htmlspecialchars($aid['proof_photo']); ?>'; openImageModal = true;">
                                                        <img src="../<?php echo htmlspecialchars($aid['proof_photo']); ?>" alt="Proof" class="w-12 h-12 rounded-xl object-cover border border-gray-200 dark:border-gray-600 hover:opacity-80 transition shadow-sm">
                                                        <span class="text-[11px] text-indigo-600 dark:text-indigo-400 font-bold hover:underline">
                                                            <i class="fa-solid fa-camera mr-0.5"></i> Proof Photo
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Lightbox Modal for Photo Previews -->
    <div x-show="openImageModal" x-transition.opacity class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" style="display: none;">
        <div class="relative max-w-4xl max-h-[90vh] overflow-hidden rounded-3xl bg-black border border-gray-800 shadow-2xl" @click.away="openImageModal = false">
            <button type="button" @click="openImageModal = false" class="absolute top-4 right-4 z-10 w-10 h-10 rounded-full bg-black/60 text-white flex items-center justify-center hover:bg-black transition">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
            <img :src="activeImage" class="max-w-full max-h-[85vh] object-contain mx-auto">
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- 1. LOG ASSISTANCE MODAL (FOR THIS BENEFICIARY)               -->
<!-- ============================================================ -->
<div id="profileAidModal" class="fixed inset-0 hidden z-[90] items-center justify-center p-4 bg-black/60 backdrop-blur-sm overflow-y-auto">
    <div class="bg-white dark:bg-gray-900 w-full max-w-2xl rounded-3xl shadow-2xl border border-gray-100 dark:border-gray-800 overflow-hidden my-8">
        <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-gray-800 dark:to-gray-800">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center text-lg shadow-sm">
                    <i class="fa-solid fa-hand-holding-heart"></i>
                </span>
                <div>
                    <h4 class="text-lg font-bold text-gray-900 dark:text-white">Log Assistance Handover</h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Beneficiary: <strong><?php echo htmlspecialchars($beneficiary['name'] . ' (' . ($beneficiary['beneficiary_code'] ?? 'ID:'.$benId) . ')'); ?></strong></p>
                </div>
            </div>
            <button type="button" class="text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 p-2 rounded-xl transition" onclick="closeProfileAidModal()">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>

        <form action="actions/beneficiary_logic.php" method="POST" enctype="multipart/form-data" class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="action" value="add_assistance">
            <input type="hidden" name="beneficiary_id" value="<?php echo $benId; ?>">

            <!-- Assistance Type & Date -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Assistance Type <span class="text-red-500">*</span></label>
                    <select name="assistance_type" required class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-indigo-500">
                        <option value="Ration & Food Kit" selected>Ration & Food Kit (Dry Ration, Grains)</option>
                        <option value="Financial Aid">Financial Aid / Direct Cash Grant</option>
                        <option value="Medical Aid">Medical Aid & Medicines</option>
                        <option value="Educational Support">Educational Support (Books, Fees, Kits)</option>
                        <option value="Clothing & Blankets">Clothing & Blankets</option>
                        <option value="Mobility & Assistive Devices">Mobility & Assistive Devices (Wheelchair, Cane)</option>
                        <option value="Shelter & Housing">Shelter & Housing Support</option>
                        <option value="Skill Training & Livelihood">Skill Training & Livelihood Toolkits</option>
                        <option value="Emergency Relief">Emergency Relief</option>
                        <option value="In-Kind Items">In-Kind Items</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Distribution Date <span class="text-red-500">*</span></label>
                    <input type="date" name="date" required value="<?php echo date('Y-m-d'); ?>" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            <!-- Description -->
            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Aid Particulars & Description <span class="text-red-500">*</span></label>
                <textarea name="description" required rows="2" placeholder="Itemized list of items / relief assistance provided..." class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-indigo-500"></textarea>
            </div>

            <!-- Cash and In-kind Valuation -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Cash / Amount (₹)</label>
                    <input type="number" step="0.01" min="0" name="amount" value="0.00" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Material Value (₹)</label>
                    <input type="number" step="0.01" min="0" name="estimated_value" placeholder="Estimated value" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-indigo-500">
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Quantity</label>
                        <input type="number" step="0.01" min="1" name="quantity" value="1" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Unit</label>
                        <input type="text" name="unit" value="kits" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
            </div>

            <!-- Distribution Location & Given By / Coordinator -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Distribution Location / Center</label>
                    <input type="text" name="distribution_location" placeholder="e.g. Center #2, Block Camp" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Handed Over By</label>
                    <input type="text" name="given_by" placeholder="Coordinator or guest name" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-indigo-500">
                </div>

                <?php if ($isManagerOrAdmin): ?>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Assigned Coordinator</label>
                        <select name="coordinator_id" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-indigo-500">
                            <?php foreach ($coordinators as $c): ?>
                                <option value="<?php echo (int)$c['id']; ?>" <?php echo $c['id'] == $currentUserId ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['name'] . ($c['coordinator_code'] ? ' (' . $c['coordinator_code'] . ')' : '')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php else: ?>
                    <input type="hidden" name="coordinator_id" value="<?php echo $currentUserId; ?>">
                <?php endif; ?>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Receipt / Voucher Ref No.</label>
                    <input type="text" name="receipt_no" placeholder="Optional voucher/receipt no." class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            <!-- Proof Photo -->
            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Proof Photo (Handover)</label>
                <input type="file" name="proof_photo" accept="image/jpeg,image/png,image/webp" class="w-full text-xs text-gray-500 dark:text-gray-400 file:mr-2 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
            </div>

            <div class="flex gap-3 pt-4 border-t border-gray-100 dark:border-gray-800">
                <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-xl shadow-sm text-sm transition">
                    <i class="fa-solid fa-check mr-1"></i> Save Assistance Record
                </button>
                <button type="button" onclick="closeProfileAidModal()" class="px-6 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-bold py-3 rounded-xl text-sm transition">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- 2. EDIT BENEFICIARY MODAL                                    -->
<!-- ============================================================ -->
<div id="profileEditModal" class="fixed inset-0 hidden z-[90] items-center justify-center p-4 bg-black/60 backdrop-blur-sm overflow-y-auto">
    <div class="bg-white dark:bg-gray-900 w-full max-w-3xl rounded-3xl shadow-2xl border border-gray-100 dark:border-gray-800 overflow-hidden my-8">
        <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-800">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center text-lg shadow-sm">
                    <i class="fa-solid fa-user-pen"></i>
                </span>
                <div>
                    <h4 class="text-lg font-bold text-gray-900 dark:text-white">Edit Beneficiary Profile</h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($beneficiary['name'] . ' (' . ($beneficiary['beneficiary_code'] ?? 'ID:'.$benId) . ')'); ?></p>
                </div>
            </div>
            <button type="button" class="text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 p-2 rounded-xl transition" onclick="closeProfileEditModal()">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>

        <form action="actions/beneficiary_logic.php" method="POST" enctype="multipart/form-data" class="p-6 space-y-5 max-h-[75vh] overflow-y-auto">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="action" value="update_beneficiary">
            <input type="hidden" name="id" value="<?php echo $benId; ?>">
            <input type="hidden" name="redirect_to_profile" value="1">

            <!-- Personal Details -->
            <div>
                <h5 class="text-xs font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400 mb-3 flex items-center gap-1.5">
                    <i class="fa-solid fa-id-card"></i> 1. Personal Details
                </h5>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Full Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" required value="<?php echo htmlspecialchars($beneficiary['name']); ?>" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Father's / Spouse's Name</label>
                        <input type="text" name="father_or_spouse_name" value="<?php echo htmlspecialchars($beneficiary['father_or_spouse_name'] ?? ''); ?>" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Gender <span class="text-red-500">*</span></label>
                        <select name="gender" required class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500">
                            <option value="Male" <?php echo $beneficiary['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo $beneficiary['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo $beneficiary['gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Age (Yrs)</label>
                            <input type="number" name="age" min="0" max="120" value="<?php echo htmlspecialchars((string)($beneficiary['age'] ?? '')); ?>" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Date of Birth</label>
                            <input type="date" name="dob" value="<?php echo htmlspecialchars($beneficiary['dob'] ?? ''); ?>" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact & Identifiers -->
            <div class="pt-3 border-t border-gray-100 dark:border-gray-800">
                <h5 class="text-xs font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400 mb-3 flex items-center gap-1.5">
                    <i class="fa-solid fa-phone"></i> 2. Contact & Identity
                </h5>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Primary Mobile <span class="text-red-500">*</span></label>
                        <input type="tel" name="contact" required value="<?php echo htmlspecialchars($beneficiary['contact']); ?>" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Alternate Phone</label>
                        <input type="tel" name="alternate_contact" value="<?php echo htmlspecialchars($beneficiary['alternate_contact'] ?? ''); ?>" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($beneficiary['email'] ?? ''); ?>" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Aadhaar Card No.</label>
                        <input type="text" name="aadhar_no" value="<?php echo htmlspecialchars($beneficiary['aadhar_no'] ?? ''); ?>" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Ration Card No.</label>
                        <input type="text" name="ration_card_no" value="<?php echo htmlspecialchars($beneficiary['ration_card_no'] ?? ''); ?>" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <!-- Category & Socio-Economic -->
            <div class="pt-3 border-t border-gray-100 dark:border-gray-800">
                <h5 class="text-xs font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400 mb-3 flex items-center gap-1.5">
                    <i class="fa-solid fa-layer-group"></i> 3. Socio-Economic Status
                </h5>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Category <span class="text-red-500">*</span></label>
                        <select name="category_id" required class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo (int)$cat['id']; ?>" <?php echo $beneficiary['category_id'] == $cat['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['category_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Beneficiary Type</label>
                        <select name="beneficiary_type" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500">
                            <?php
                            $types = ['General / BPL', 'Antyodaya Anna Yojana (AAY)', 'Priority Household (PHH)', 'Non-Priority Household (NPHH)', 'Destitute / Homeless', 'Orphan / Foster Child', 'Other'];
                            foreach ($types as $t): ?>
                                <option value="<?php echo $t; ?>" <?php echo $beneficiary['beneficiary_type'] === $t ? 'selected' : ''; ?>><?php echo $t; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Annual Income (₹)</label>
                        <input type="number" step="0.01" name="annual_income" value="<?php echo htmlspecialchars((string)($beneficiary['annual_income'] ?? '')); ?>" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Family Members</label>
                        <input type="number" min="1" name="family_members_count" value="<?php echo (int)($beneficiary['family_members_count'] ?? 1); ?>" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Disability / Divyang</label>
                        <select name="disability_status" id="prof_edit_disability_status" onchange="toggleProfDisability(this)" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500">
                            <option value="No" <?php echo $beneficiary['disability_status'] === 'No' ? 'selected' : ''; ?>>No</option>
                            <option value="Yes" <?php echo $beneficiary['disability_status'] === 'Yes' ? 'selected' : ''; ?>>Yes (Divyang)</option>
                        </select>
                    </div>

                    <div id="prof_edit_disability_box" class="<?php echo $beneficiary['disability_status'] === 'Yes' ? '' : 'hidden'; ?>">
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Disability Details</label>
                        <input type="text" name="disability_details" value="<?php echo htmlspecialchars($beneficiary['disability_details'] ?? ''); ?>" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <!-- Address -->
            <div class="pt-3 border-t border-gray-100 dark:border-gray-800">
                <h5 class="text-xs font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400 mb-3 flex items-center gap-1.5">
                    <i class="fa-solid fa-map-location-dot"></i> 4. Residential Address
                </h5>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">State <span class="text-red-500">*</span></label>
                        <select name="state" id="prof_edit_state" required onchange="updateProfEditDistricts()" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500">
                            <?php foreach (india_state_list() as $st): ?>
                                <option value="<?php echo htmlspecialchars($st); ?>" <?php echo $beneficiary['state'] === $st ? 'selected' : ''; ?>><?php echo htmlspecialchars($st); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">District <span class="text-red-500">*</span></label>
                        <select name="district" id="prof_edit_district" required class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500">
                            <option value="<?php echo htmlspecialchars($beneficiary['district']); ?>" selected><?php echo htmlspecialchars($beneficiary['district']); ?></option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Block / Tehsil</label>
                        <input type="text" name="block" value="<?php echo htmlspecialchars($beneficiary['block'] ?? ''); ?>" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="md:col-span-3">
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Complete Address <span class="text-red-500">*</span></label>
                        <textarea name="address" required rows="2" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($beneficiary['address']); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Status & Photo -->
            <div class="pt-3 border-t border-gray-100 dark:border-gray-800">
                <h5 class="text-xs font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400 mb-3 flex items-center gap-1.5">
                    <i class="fa-solid fa-camera"></i> 5. Photo & Administrative Status
                </h5>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Replace Photo</label>
                        <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="w-full text-xs text-gray-500 dark:text-gray-400 file:mr-2 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Current Status</label>
                        <select name="status" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500">
                            <option value="Active" <?php echo $beneficiary['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                            <option value="Assisted" <?php echo $beneficiary['status'] === 'Assisted' ? 'selected' : ''; ?>>Assisted</option>
                            <option value="Under Review" <?php echo $beneficiary['status'] === 'Under Review' ? 'selected' : ''; ?>>Under Review</option>
                            <option value="Inactive" <?php echo $beneficiary['status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="Archived" <?php echo $beneficiary['status'] === 'Archived' ? 'selected' : ''; ?>>Archived</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Remarks</label>
                        <input type="text" name="remarks" value="<?php echo htmlspecialchars($beneficiary['remarks'] ?? ''); ?>" class="w-full px-3.5 py-2 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="flex gap-3 pt-4 border-t border-gray-100 dark:border-gray-800">
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl shadow-sm text-sm transition">
                    <i class="fa-solid fa-save mr-1"></i> Save Beneficiary Changes
                </button>
                <button type="button" onclick="closeProfileEditModal()" class="px-6 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-bold py-3 rounded-xl text-sm transition">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const indiaLocationsProfile = <?php echo india_state_district_js(); ?>;

    function openProfileAidModal() {
        const m = document.getElementById('profileAidModal');
        m.classList.remove('hidden');
        m.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function closeProfileAidModal() {
        const m = document.getElementById('profileAidModal');
        m.classList.add('hidden');
        m.classList.remove('flex');
        document.body.style.overflow = 'unset';
    }

    function openProfileEditModal() {
        updateProfEditDistricts('<?php echo addslashes($beneficiary['district']); ?>');
        const m = document.getElementById('profileEditModal');
        m.classList.remove('hidden');
        m.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function closeProfileEditModal() {
        const m = document.getElementById('profileEditModal');
        m.classList.add('hidden');
        m.classList.remove('flex');
        document.body.style.overflow = 'unset';
    }

    function updateProfEditDistricts(selectedDistrict = '') {
        const state = document.getElementById('prof_edit_state').value;
        const distSelect = document.getElementById('prof_edit_district');
        const dists = indiaLocationsProfile[state] || [];
        let html = '<option value="">Select District</option>';
        dists.forEach(d => {
            const sel = (d === selectedDistrict || d === '<?php echo addslashes($beneficiary['district']); ?>') ? 'selected' : '';
            html += `<option value="${d}" ${sel}>${d}</option>`;
        });
        distSelect.innerHTML = html;
    }

    function toggleProfDisability(selectEl) {
        const box = document.getElementById('prof_edit_disability_box');
        if (selectEl.value === 'Yes') {
            box.classList.remove('hidden');
        } else {
            box.classList.add('hidden');
        }
    }
</script>

<?php require 'includes/footer.php'; ?>
