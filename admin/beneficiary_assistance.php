<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

$csrfToken = generateCsrfToken();
if (!canAccessModule($pdo, 'coordinator', 'page.beneficiaries')) {
    setFlash('error', 'Access denied. Coordinator/Manager/Admin role required.');
    header('Location: dashboard.php');
    exit;
}

require 'includes/header.php';

$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$isManagerOrAdmin = checkRole($pdo, 'manager');

// Fetch current user details for Coordinator view
$currentUserStmt = $pdo->prepare("SELECT id, name, coordinator_code, role, hierarchy_level FROM users WHERE id = ?");
$currentUserStmt->execute([$currentUserId]);
$currentUser = $currentUserStmt->fetch(PDO::FETCH_ASSOC);
$currentUserName = $currentUser['name'] ?? 'Coordinator';

// Fetch all active coordinators (for Admin/Manager filters and selectors)
$coordinators = $pdo->query("SELECT id, name, coordinator_code, role FROM users ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch Beneficiaries for Dropdown Selection
$beneficiaries = $pdo->query("
    SELECT id, beneficiary_code, name, contact, district, state, block, category_id, photo
    FROM beneficiaries
    ORDER BY name ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch Assistance Records with strict RBAC Scoping
if ($isManagerOrAdmin) {
    // Admin / Manager: Sees ALL assistance records
    $sql = "SELECT 
        ast.*,
        b.name AS beneficiary_name,
        b.beneficiary_code,
        b.contact AS beneficiary_contact,
        b.district AS beneficiary_district,
        b.state AS beneficiary_state,
        b.block AS beneficiary_block,
        b.photo AS beneficiary_photo,
        c.category_name,
        u.name AS coordinator_user_name,
        u.coordinator_code AS coordinator_user_code,
        p.title AS project_name
    FROM beneficiary_assistance_history ast
    JOIN beneficiaries b ON ast.beneficiary_id = b.id
    LEFT JOIN beneficiary_categories c ON b.category_id = c.id
    LEFT JOIN users u ON ast.coordinator_id = u.id
    LEFT JOIN projects p ON ast.project_id = p.id
    ORDER BY ast.date DESC, ast.id DESC";

    $stmt = $pdo->query($sql);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Coordinator: Sees ONLY their own logged assistance records
    $sql = "SELECT 
        ast.*,
        b.name AS beneficiary_name,
        b.beneficiary_code,
        b.contact AS beneficiary_contact,
        b.district AS beneficiary_district,
        b.state AS beneficiary_state,
        b.block AS beneficiary_block,
        b.photo AS beneficiary_photo,
        c.category_name,
        u.name AS coordinator_user_name,
        u.coordinator_code AS coordinator_user_code,
        p.title AS project_name
    FROM beneficiary_assistance_history ast
    JOIN beneficiaries b ON ast.beneficiary_id = b.id
    LEFT JOIN beneficiary_categories c ON b.category_id = c.id
    LEFT JOIN users u ON ast.coordinator_id = u.id
    LEFT JOIN projects p ON ast.project_id = p.id
    WHERE ast.coordinator_id = ?
    ORDER BY ast.date DESC, ast.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$currentUserId]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Calculate Summary Metrics
$totalAidEvents = count($records);
$totalFinancial = 0.00;
$totalInKind = 0.00;
$uniqueBeneficiaries = [];

foreach ($records as $r) {
    $totalFinancial += (float)$r['amount'];
    $totalInKind += (float)$r['estimated_value'];
    $uniqueBeneficiaries[$r['beneficiary_id']] = true;
}
$uniqueBeneficiariesCount = count($uniqueBeneficiaries);
$combinedValuation = $totalFinancial + $totalInKind;
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900" x-data="assistanceManager()">
    <?php require 'includes/sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6">
            <!-- Header Banner -->
            <div class="flex flex-col gap-4 mb-6">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="p-2 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                                <i class="fa-solid fa-hand-holding-heart text-xl"></i>
                            </span>
                            <h3 class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-white">
                                <?php echo $isManagerOrAdmin ? 'Assistance Distribution Log' : 'My Assistance Log'; ?>
                            </h3>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            <?php if ($isManagerOrAdmin): ?>
                                Central distribution register for welfare aid, ration kits, medicines, and grants across all coordinators.
                            <?php else: ?>
                                Welfare assistance, ration, and relief handovers logged by <strong><?php echo htmlspecialchars($currentUserName); ?></strong>.
                            <?php endif; ?>
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5">
                        <a href="beneficiary_reports.php" class="bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 hover:bg-slate-50 dark:hover:bg-gray-700 font-medium px-4 py-2.5 rounded-xl shadow-sm text-sm flex items-center gap-2 transition">
                            <i class="fa-solid fa-file-invoice text-teal-600"></i>
                            <span>Reports & Analytics</span>
                        </a>
                        <button type="button" @click="openAddModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-4 py-2.5 rounded-xl shadow-sm text-sm flex items-center gap-2 transition transform active:scale-95">
                            <i class="fa-solid fa-plus"></i>
                            <span>Log Assistance Handover</span>
                        </button>
                        <a href="beneficiaries.php" class="bg-teal-600 hover:bg-teal-700 text-white font-medium px-4 py-2.5 rounded-xl shadow-sm text-sm flex items-center gap-2 transition">
                            <i class="fa-solid fa-hands-holding-child"></i>
                            <span>Beneficiary Directory</span>
                        </a>
                    </div>
                </div>

                <!-- RBAC Notice Badge for Coordinator -->
                <?php if (!$isManagerOrAdmin): ?>
                    <div class="p-3.5 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800 flex items-center gap-3 text-xs text-indigo-800 dark:text-indigo-300">
                        <i class="fa-solid fa-shield-halved text-base text-indigo-600 dark:text-indigo-400 flex-shrink-0"></i>
                        <div>
                            <strong>Coordinator View Active:</strong> Showing only assistance records distributed and logged by you (<strong><?php echo htmlspecialchars($currentUserName); ?></strong>). All new records will be automatically tied to your account.
                        </div>
                    </div>
                <?php endif; ?>

                <!-- KPI Metric Summary Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white dark:bg-dark-card p-4 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-xl flex-shrink-0">
                            <i class="fa-solid fa-box-archive"></i>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Total Aid Events</p>
                            <h4 class="text-2xl font-bold text-gray-800 dark:text-white"><?php echo number_format($totalAidEvents); ?></h4>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-dark-card p-4 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-xl flex-shrink-0">
                            <i class="fa-solid fa-indian-rupee-sign"></i>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Financial Grants</p>
                            <h4 class="text-xl font-bold text-emerald-600 dark:text-emerald-400">₹<?php echo number_format($totalFinancial, 2); ?></h4>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-dark-card p-4 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 flex items-center justify-center text-xl flex-shrink-0">
                            <i class="fa-solid fa-wheat-awn"></i>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">In-Kind Material Value</p>
                            <h4 class="text-xl font-bold text-purple-600 dark:text-purple-400">₹<?php echo number_format($totalInKind, 2); ?></h4>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-dark-card p-4 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-teal-50 dark:bg-teal-900/30 text-teal-600 dark:text-teal-400 flex items-center justify-center text-xl flex-shrink-0">
                            <i class="fa-solid fa-users"></i>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Beneficiaries Reached</p>
                            <h4 class="text-2xl font-bold text-teal-600 dark:text-teal-400"><?php echo number_format($uniqueBeneficiariesCount); ?></h4>
                        </div>
                    </div>
                </div>

                <!-- Filter Controls Toolbar -->
                <div class="bg-white dark:bg-dark-card p-5 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-<?php echo $isManagerOrAdmin ? '4' : '3'; ?> gap-3">
                        <!-- 1. Aid Type Filter -->
                        <div>
                            <label class="text-[11px] font-bold text-gray-600 dark:text-gray-400 flex items-center gap-1 mb-1">
                                <i class="fa-solid fa-tags text-indigo-600"></i> Assistance Type
                            </label>
                            <select x-model="typeFilter" class="w-full text-xs font-medium p-2.5 border rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">All Assistance Types</option>
                                <option value="Ration & Food Kit">Ration & Food Kit</option>
                                <option value="Financial Aid">Financial Aid</option>
                                <option value="Medical Aid">Medical Aid & Medicines</option>
                                <option value="Educational Support">Educational Support</option>
                                <option value="Clothing & Blankets">Clothing & Blankets</option>
                                <option value="Mobility & Assistive Devices">Mobility & Assistive Devices</option>
                                <option value="Shelter & Housing">Shelter & Housing</option>
                                <option value="Skill Training & Livelihood">Skill Training & Livelihood</option>
                                <option value="Emergency Relief">Emergency Relief</option>
                                <option value="In-Kind Items">In-Kind Items</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <!-- 2. Coordinator Filter (Visible to Admin/Manager only) -->
                        <?php if ($isManagerOrAdmin): ?>
                            <div>
                                <label class="text-[11px] font-bold text-gray-600 dark:text-gray-400 flex items-center gap-1 mb-1">
                                    <i class="fa-solid fa-user-tie text-indigo-600"></i> Coordinator
                                </label>
                                <select x-model="coordinatorFilter" class="w-full text-xs font-medium p-2.5 border rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option value="">All Coordinators</option>
                                    <?php foreach ($coordinators as $c): ?>
                                        <option value="<?php echo (int)$c['id']; ?>">
                                            <?php echo htmlspecialchars($c['name'] . ($c['coordinator_code'] ? ' (' . $c['coordinator_code'] . ')' : '')); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <!-- 3. Date Range / Date Filter -->
                        <div>
                            <label class="text-[11px] font-bold text-gray-600 dark:text-gray-400 flex items-center gap-1 mb-1">
                                <i class="fa-solid fa-calendar-day text-indigo-600"></i> Date
                            </label>
                            <input type="date" x-model="dateFilter" class="w-full text-xs font-medium p-2.5 border rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <!-- 4. Text Search -->
                        <div>
                            <label class="text-[11px] font-bold text-gray-600 dark:text-gray-400 flex items-center gap-1 mb-1">
                                <i class="fa-solid fa-magnifying-glass text-indigo-600"></i> Search Query
                            </label>
                            <div class="relative">
                                <input type="text" x-model="search" placeholder="Search beneficiary, code, location, voucher..." class="w-full pl-3 pr-8 py-2.5 text-xs rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <button x-show="search" @click="search = ''" class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600 text-xs">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Active Filter Tags & Reset Button -->
                    <div x-show="hasActiveFilters" class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-gray-100 dark:border-gray-800 text-xs">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span class="text-gray-400 font-semibold text-[11px]">Active Filters:</span>
                            <span x-show="typeFilter" class="px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300 font-medium text-[11px]">
                                Type: <strong x-text="typeFilter"></strong>
                            </span>
                            <span x-show="coordinatorFilter" class="px-2.5 py-1 rounded-lg bg-teal-50 text-teal-700 dark:bg-teal-900/30 dark:text-teal-300 font-medium text-[11px]">
                                Coordinator Selected
                            </span>
                            <span x-show="dateFilter" class="px-2.5 py-1 rounded-lg bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300 font-medium text-[11px]">
                                Date: <strong x-text="dateFilter"></strong>
                            </span>
                            <span x-show="search" class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 font-medium text-[11px]">
                                Query: "<span x-text="search"></span>"
                            </span>
                        </div>

                        <button type="button" @click="clearFilters()" class="text-xs font-bold text-red-600 hover:text-red-700 dark:text-red-400 hover:underline flex items-center gap-1">
                            <i class="fa-solid fa-rotate-left"></i> Reset Filters
                        </button>
                    </div>
                </div>
            </div>

            <!-- Assistance Records Table (Desktop) -->
            <div class="hidden md:block bg-white dark:bg-dark-card rounded-2xl shadow-sm overflow-hidden border border-gray-100 dark:border-gray-800">
                <table class="w-full whitespace-no-wrap text-left">
                    <thead>
                        <tr class="text-[11px] font-bold tracking-wider text-gray-400 uppercase border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50">
                            <th class="px-5 py-3.5">Assistance Code & Date</th>
                            <th class="px-5 py-3.5">Beneficiary Details</th>
                            <th class="px-5 py-3.5">Aid Particulars</th>
                            <th class="px-5 py-3.5">Valuation / Cash</th>
                            <th class="px-5 py-3.5">Handed Over By / Location</th>
                            <th class="px-5 py-3.5">Proof & Voucher</th>
                            <th class="px-5 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-sm">
                        <template x-for="item in pagedItems" :key="item.id">
                            <tr class="hover:bg-indigo-50/20 dark:hover:bg-gray-800/40 transition align-top">
                                <!-- Code & Date -->
                                <td class="px-5 py-4 text-xs">
                                    <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30 px-2 py-0.5 rounded" x-text="item.assistance_code || ('AST-' + item.id)"></span>
                                    <p class="font-medium text-gray-700 dark:text-gray-300 mt-1.5 flex items-center gap-1">
                                        <i class="fa-solid fa-calendar-day text-gray-400 text-[10px]"></i>
                                        <span x-text="item.date"></span>
                                    </p>
                                    <span class="inline-block mt-1 text-[10px] font-bold text-emerald-600 bg-emerald-50 dark:bg-emerald-900/30 px-1.5 py-0.5 rounded" x-text="item.status"></span>
                                </td>

                                <!-- Beneficiary Details -->
                                <td class="px-5 py-4">
                                    <div class="flex items-start gap-3">
                                        <img :src="item.beneficiary_photo ? '../' + item.beneficiary_photo : 'https://ui-avatars.com/api/?name=' + encodeURIComponent(item.beneficiary_name || 'Beneficiary') + '&background=0F8B8D&color=fff'" 
                                             class="w-10 h-10 rounded-xl object-cover border border-gray-200 dark:border-gray-700 flex-shrink-0">
                                        <div>
                                            <a :href="'beneficiary_profile.php?id=' + item.beneficiary_id" class="font-bold text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 transition" x-text="item.beneficiary_name"></a>
                                            <p class="font-mono text-xs text-teal-600 dark:text-teal-400" x-text="item.beneficiary_code"></p>
                                            <p class="text-[11px] text-gray-400" x-text="item.beneficiary_district + ', ' + item.beneficiary_state"></p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Aid Type & Particulars -->
                                <td class="px-5 py-4 text-xs max-w-xs">
                                    <span class="px-2.5 py-0.5 rounded-full font-bold inline-block" :class="getTypeBadgeClass(item.assistance_type)" x-text="item.assistance_type"></span>
                                    <p class="mt-1 text-gray-800 dark:text-gray-200 font-medium line-clamp-2 leading-relaxed" x-text="item.description"></p>
                                </td>

                                <!-- Valuation / Cash -->
                                <td class="px-5 py-4 text-xs">
                                    <div x-show="Number(item.amount) > 0" class="font-bold text-emerald-600 dark:text-emerald-400">
                                        Cash: ₹<span x-text="formatMoney(item.amount)"></span>
                                    </div>
                                    <div x-show="Number(item.estimated_value) > 0 || Number(item.quantity) > 0" class="text-indigo-600 dark:text-indigo-400 font-medium mt-0.5">
                                        <span x-text="item.quantity + ' ' + (item.unit || 'units')"></span>
                                        <span x-show="Number(item.estimated_value) > 0" class="text-gray-400 block text-[11px]">
                                            Val: ₹<span x-text="formatMoney(item.estimated_value)"></span>
                                        </span>
                                    </div>
                                </td>

                                <!-- Handed Over By & Location -->
                                <td class="px-5 py-4 text-xs">
                                    <div class="font-semibold text-gray-800 dark:text-gray-200 flex items-center gap-1">
                                        <i class="fa-solid fa-user-check text-indigo-500 text-[10px]"></i>
                                        <span x-text="item.given_by || item.coordinator_user_name || 'Coordinator'"></span>
                                    </div>
                                    <p class="text-gray-500 dark:text-gray-400 mt-1 flex items-center gap-1" x-show="item.distribution_location">
                                        <i class="fa-solid fa-location-dot text-teal-600 text-[10px]"></i>
                                        <span x-text="item.distribution_location"></span>
                                    </p>
                                </td>

                                <!-- Proof & Voucher -->
                                <td class="px-5 py-4 text-xs">
                                    <div class="flex items-center gap-2">
                                        <template x-if="item.proof_photo">
                                            <div class="cursor-pointer" @click="activeImage = '../' + item.proof_photo; openImageModal = true;">
                                                <img :src="'../' + item.proof_photo" class="w-10 h-10 rounded-xl object-cover border border-gray-200 dark:border-gray-700 hover:opacity-80 transition shadow-sm">
                                            </div>
                                        </template>
                                        <div class="text-[11px]">
                                            <p x-show="item.receipt_no" class="font-mono text-gray-600 dark:text-gray-300">Ref: <span x-text="item.receipt_no"></span></p>
                                            <span x-show="!item.proof_photo && !item.receipt_no" class="text-gray-400 italic">No proof/voucher</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="px-5 py-4 text-sm text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a :href="'beneficiary_profile.php?id=' + item.beneficiary_id" class="p-2 text-teal-600 hover:bg-teal-50 dark:hover:bg-teal-900/30 rounded-lg transition" title="View Beneficiary Profile">
                                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        </a>

                                        <button type="button" @click="confirmDelete(item.id, item.beneficiary_id, item.assistance_code)" class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition" title="Delete Aid Record">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <div x-show="filteredItems.length === 0" class="p-12 text-center text-gray-400 dark:text-gray-500">
                    <i class="fa-solid fa-hand-holding-heart text-4xl mb-3 block opacity-40"></i>
                    <p class="font-medium text-base text-gray-700 dark:text-gray-300">No assistance records matching your filters.</p>
                    <p class="text-xs text-gray-400 mt-1">Log a new handover or adjust your search.</p>
                </div>
            </div>

            <!-- Card View (Mobile) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 md:hidden pb-12">
                <template x-for="item in pagedItems" :key="item.id">
                    <div class="bg-white dark:bg-dark-card p-4 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 flex flex-col justify-between">
                        <div>
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <span class="font-mono text-xs font-bold text-indigo-600 dark:text-indigo-400" x-text="item.assistance_code || ('AST-' + item.id)"></span>
                                    <p class="text-[11px] text-gray-400" x-text="item.date"></p>
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold" :class="getTypeBadgeClass(item.assistance_type)" x-text="item.assistance_type"></span>
                            </div>

                            <div class="mt-3 flex items-center gap-3">
                                <img :src="item.beneficiary_photo ? '../' + item.beneficiary_photo : 'https://ui-avatars.com/api/?name=' + encodeURIComponent(item.beneficiary_name || 'Beneficiary') + '&background=0F8B8D&color=fff'" 
                                     class="w-10 h-10 rounded-xl object-cover border border-gray-200">
                                <div>
                                    <a :href="'beneficiary_profile.php?id=' + item.beneficiary_id" class="font-bold text-gray-800 dark:text-white text-sm" x-text="item.beneficiary_name"></a>
                                    <p class="text-[11px] text-gray-500" x-text="item.beneficiary_district + ', ' + item.beneficiary_state"></p>
                                </div>
                            </div>

                            <p class="mt-2 text-xs text-gray-600 dark:text-gray-300 line-clamp-2" x-text="item.description"></p>
                            
                            <div class="mt-3 py-2 border-t border-b border-gray-100 dark:border-gray-800 grid grid-cols-2 gap-2 text-xs">
                                <div>
                                    <span class="text-[10px] text-gray-400 uppercase block">Amount / Value</span>
                                    <span class="font-bold text-emerald-600" x-text="'₹' + formatMoney(Number(item.amount || 0) + Number(item.estimated_value || 0))"></span>
                                </div>
                                <div>
                                    <span class="text-[10px] text-gray-400 uppercase block">Given By</span>
                                    <span class="truncate block" x-text="item.given_by || item.coordinator_user_name || 'Coordinator'"></span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 flex items-center gap-2">
                            <a :href="'beneficiary_profile.php?id=' + item.beneficiary_id" class="flex-1 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 text-center py-2 rounded-xl text-xs font-bold">
                                View Profile
                            </a>
                            <button type="button" @click="confirmDelete(item.id, item.beneficiary_id, item.assistance_code)" class="bg-red-50 text-red-600 dark:bg-red-900/30 px-3 py-2 rounded-xl text-xs font-bold">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Pagination UI -->
            <div x-show="filteredItems.length > pageSize" class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-6 px-4 py-3 bg-white dark:bg-dark-card border border-gray-100 dark:border-gray-800 rounded-2xl shadow-sm">
                <div class="text-xs text-gray-500 dark:text-gray-400 font-medium">
                    Showing <span class="font-bold text-gray-900 dark:text-white" x-text="filteredItems.length === 0 ? 0 : (page - 1) * pageSize + 1"></span> to 
                    <span class="font-bold text-gray-900 dark:text-white" x-text="Math.min(page * pageSize, filteredItems.length)"></span> of 
                    <span class="font-bold text-gray-900 dark:text-white" x-text="filteredItems.length"></span> entries
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="if (page > 1) page--" :disabled="page === 1" 
                        class="px-4 py-2 text-xs font-bold rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-50 disabled:pointer-events-none transition flex items-center gap-1">
                        <i class="fa-solid fa-chevron-left"></i> Previous
                    </button>
                    <div class="flex items-center gap-1">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">Page</span>
                        <span class="text-xs font-bold text-gray-900 dark:text-white" x-text="page"></span>
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">of</span>
                        <span class="text-xs font-bold text-gray-900 dark:text-white" x-text="totalPages || 1"></span>
                    </div>
                    <button type="button" @click="if (page < totalPages) page++" :disabled="page === totalPages || totalPages === 0" 
                        class="px-4 py-2 text-xs font-bold rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-50 disabled:pointer-events-none transition flex items-center gap-1">
                        Next <i class="fa-solid fa-chevron-right"></i>
                    </button>
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
<!-- ADD ASSISTANCE RECORD MODAL                                  -->
<!-- ============================================================ -->
<div id="addAssistanceModal" class="fixed inset-0 hidden z-[90] items-center justify-center p-4 bg-black/60 backdrop-blur-sm overflow-y-auto">
    <div class="bg-white dark:bg-gray-900 w-full max-w-2xl rounded-3xl shadow-2xl border border-gray-100 dark:border-gray-800 overflow-hidden my-8">
        <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-gray-800 dark:to-gray-800">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center text-lg shadow-sm">
                    <i class="fa-solid fa-hand-holding-heart"></i>
                </span>
                <div>
                    <h4 class="text-lg font-bold text-gray-900 dark:text-white">Record Assistance Handover</h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Log welfare items, financial grant, or material support provided</p>
                </div>
            </div>
            <button type="button" class="text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 p-2 rounded-xl transition" onclick="closeAddModal()">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>

        <form action="actions/beneficiary_logic.php" method="POST" enctype="multipart/form-data" class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="action" value="add_assistance">
            <input type="hidden" name="redirect_url" value="../beneficiary_assistance.php">

            <!-- Beneficiary Selector -->
            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Select Beneficiary <span class="text-red-500">*</span></label>
                <select name="beneficiary_id" required class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    <option value="">-- Choose Beneficiary --</option>
                    <?php foreach ($beneficiaries as $b): ?>
                        <option value="<?php echo (int)$b['id']; ?>">
                            <?php echo htmlspecialchars($b['name'] . ' (' . ($b['beneficiary_code'] ?? 'ID:'.$b['id']) . ') - ' . $b['contact'] . ' [' . ($b['district'] ?? '') . ', ' . ($b['state'] ?? '') . ']'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

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
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Aid Description / Particulars <span class="text-red-500">*</span></label>
                <textarea name="description" required rows="2" placeholder="Detail the assistance provided (e.g. Monthly dry ration packet: 10kg rice, 5kg atta, 2L cooking oil...)" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-indigo-500"></textarea>
            </div>

            <!-- Monetary & In-kind Valuation -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Cash / Amount (₹)</label>
                    <input type="number" step="0.01" min="0" name="amount" value="0.00" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Item Valuation (₹)</label>
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
                    <input type="text" name="distribution_location" placeholder="e.g. Community Center, Camp #1" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Handed Over By</label>
                    <input type="text" name="given_by" value="<?php echo htmlspecialchars($currentUserName); ?>" placeholder="Distributor name" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-indigo-500">
                </div>

                <!-- Coordinator Selection (Locked if Coordinator, Selectable if Admin) -->
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
                <button type="button" onclick="closeAddModal()" class="px-6 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-bold py-3 rounded-xl text-sm transition">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function assistanceManager() {
        return {
            items: <?php echo json_encode($records); ?>,
            typeFilter: '',
            coordinatorFilter: '',
            dateFilter: '',
            search: '',
            openImageModal: false,
            activeImage: '',
            page: 1,
            pageSize: 10,

            init() {
                this.$watch('typeFilter', () => this.page = 1);
                this.$watch('coordinatorFilter', () => this.page = 1);
                this.$watch('dateFilter', () => this.page = 1);
                this.$watch('search', () => this.page = 1);
            },

            get hasActiveFilters() {
                return Boolean(this.typeFilter || this.coordinatorFilter || this.dateFilter || this.search);
            },

            clearFilters() {
                this.typeFilter = '';
                this.coordinatorFilter = '';
                this.dateFilter = '';
                this.search = '';
                this.page = 1;
            },

            get filteredItems() {
                const s = this.search.toLowerCase().trim();
                const t = this.typeFilter.toLowerCase().trim();
                const coord = String(this.coordinatorFilter || '');
                const dt = this.dateFilter;

                return this.items.filter(item => {
                    // Type filter
                    if (t && (item.assistance_type || '').toLowerCase() !== t) return false;

                    // Coordinator filter
                    if (coord && String(item.coordinator_id || '') !== coord) return false;

                    // Date filter
                    if (dt && item.date !== dt) return false;

                    // Search Query
                    if (s) {
                        const benName = (item.beneficiary_name || '').toLowerCase();
                        const benCode = (item.beneficiary_code || '').toLowerCase();
                        const benPhone = (item.beneficiary_contact || '').toLowerCase();
                        const astCode = (item.assistance_code || '').toLowerCase();
                        const desc = (item.description || '').toLowerCase();
                        const loc = (item.distribution_location || '').toLowerCase();
                        const receipt = (item.receipt_no || '').toLowerCase();
                        const given = (item.given_by || item.coordinator_user_name || '').toLowerCase();

                        return benName.includes(s)
                            || benCode.includes(s)
                            || benPhone.includes(s)
                            || astCode.includes(s)
                            || desc.includes(s)
                            || loc.includes(s)
                            || receipt.includes(s)
                            || given.includes(s);
                    }
                    return true;
                });
            },

            get pagedItems() {
                const start = (this.page - 1) * this.pageSize;
                return this.filteredItems.slice(start, start + this.pageSize);
            },

            get totalPages() {
                return Math.ceil(this.filteredItems.length / this.pageSize);
            },

            formatMoney(val) {
                return Number(val || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },

            getTypeBadgeClass(type) {
                return {
                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300': type === 'Financial Aid',
                    'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300': type === 'Medical Aid',
                    'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300': type === 'Educational Support',
                    'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300': type === 'Ration & Food Kit',
                    'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300': type === 'Clothing & Blankets',
                    'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300': type === 'Mobility & Assistive Devices',
                    'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300': type === 'Shelter & Housing',
                    'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/40 dark:text-cyan-300': type === 'Skill Training & Livelihood',
                    'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300': !type || type === 'Other'
                };
            },

            openAddModal() {
                window.openAddModal();
            },

            confirmDelete(id, benId, code) {
                if (confirm(`Permanently remove assistance record "${code || ('AST-' + id)}"?`)) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'actions/beneficiary_logic.php';
                    form.innerHTML = `
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="action" value="delete_assistance">
                        <input type="hidden" name="assistance_id" value="${id}">
                        <input type="hidden" name="beneficiary_id" value="${benId}">
                        <input type="hidden" name="redirect_url" value="../beneficiary_assistance.php">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            }
        };
    }

    function openAddModal() {
        const m = document.getElementById('addAssistanceModal');
        m.classList.remove('hidden');
        m.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function closeAddModal() {
        const m = document.getElementById('addAssistanceModal');
        m.classList.add('hidden');
        m.classList.remove('flex');
        document.body.style.overflow = 'unset';
    }
</script>

<?php require 'includes/footer.php'; ?>
