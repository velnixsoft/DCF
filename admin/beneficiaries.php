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

require 'includes/header.php';

// Fetch Categories & Active Projects
$categories = $pdo->query("SELECT * FROM beneficiary_categories WHERE is_active = 1 ORDER BY display_order ASC, category_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$projects = $pdo->query("SELECT id, title FROM projects ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch Coordinators & Field Managers for Filter
$coordinators = $pdo->query("
    SELECT id, name, coordinator_code, role, hierarchy_level 
    FROM users 
    ORDER BY name ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch All Beneficiaries with Assistance Aggregates & Coordinator Details
$sql = "SELECT 
    b.*,
    c.category_name,
    c.icon AS category_icon,
    p.title AS project_title,
    u.name AS registered_by_name,
    coord.name AS coordinator_name,
    coord.coordinator_code AS coordinator_code,
    (SELECT COUNT(*) FROM beneficiary_assistance_history ast WHERE ast.beneficiary_id = b.id) AS total_aid_count,
    (SELECT COALESCE(SUM(amount), 0) FROM beneficiary_assistance_history ast WHERE ast.beneficiary_id = b.id) AS total_financial_aid,
    (SELECT COALESCE(SUM(estimated_value), 0) FROM beneficiary_assistance_history ast WHERE ast.beneficiary_id = b.id) AS total_inkind_val,
    (SELECT MAX(date) FROM beneficiary_assistance_history ast WHERE ast.beneficiary_id = b.id) AS last_aid_date
FROM beneficiaries b
LEFT JOIN beneficiary_categories c ON b.category_id = c.id
LEFT JOIN projects p ON b.project_id = p.id
LEFT JOIN users u ON b.registered_by = u.id
LEFT JOIN users coord ON b.coordinator_id = coord.id
ORDER BY b.id DESC";

$beneficiaries = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Extract unique states, districts, and blocks for location analytics
$registeredStates = [];
$registeredDistricts = [];
$registeredBlocks = [];

foreach ($beneficiaries as $b) {
    if (!empty($b['state']) && !in_array($b['state'], $registeredStates, true)) {
        $registeredStates[] = $b['state'];
    }
    if (!empty($b['district']) && !in_array($b['district'], $registeredDistricts, true)) {
        $registeredDistricts[] = $b['district'];
    }
    if (!empty($b['block']) && !in_array($b['block'], $registeredBlocks, true)) {
        $registeredBlocks[] = $b['block'];
    }
}
sort($registeredStates);
sort($registeredDistricts);
sort($registeredBlocks);

// Calculate overall summary metrics
$totalBen = count($beneficiaries);
$activeBen = 0;
$assistedBen = 0;
$underReviewBen = 0;
$totalAidEvents = 0;
$totalAidMonetaryVal = 0.00;

foreach ($beneficiaries as $b) {
    if ($b['status'] === 'Active') $activeBen++;
    elseif ($b['status'] === 'Assisted') $assistedBen++;
    elseif ($b['status'] === 'Under Review') $underReviewBen++;
    
    $totalAidEvents += (int)($b['total_aid_count'] ?? 0);
    $totalAidMonetaryVal += ((float)($b['total_financial_aid'] ?? 0) + (float)($b['total_inkind_val'] ?? 0));
}
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900" x-data="beneficiaryManager()">
    <?php require 'includes/sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6">
            <!-- Top Header & Action Buttons -->
            <div class="flex flex-col gap-4 mb-6">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="p-2 rounded-xl bg-teal-500/10 text-teal-600 dark:text-teal-400">
                                <i class="fa-solid fa-hands-holding-child text-xl"></i>
                            </span>
                            <h3 class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-white">Beneficiary Management</h3>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Register beneficiaries, filter location-wise (State/District/Block), track coordinators & assistance history timeline.</p>
                    </div>
                    
                    <div class="flex flex-wrap items-center gap-2.5">
                        <a href="beneficiary_reports.php" class="bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 hover:bg-slate-50 dark:hover:bg-gray-700 font-medium px-4 py-2.5 rounded-xl shadow-sm text-sm flex items-center gap-2 transition">
                            <i class="fa-solid fa-file-invoice text-teal-600"></i>
                            <span>Reports & Analytics</span>
                        </a>
                        <button type="button" @click="openQuickAidModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-4 py-2.5 rounded-xl shadow-sm text-sm flex items-center gap-2 transition transform active:scale-95">
                            <i class="fa-solid fa-hand-holding-heart"></i>
                            <span>Log Aid Handover</span>
                        </button>
                        <button type="button" @click="openAddModal()" class="bg-teal-600 hover:bg-teal-700 text-white font-medium px-4 py-2.5 rounded-xl shadow-sm text-sm flex items-center gap-2 transition transform active:scale-95">
                            <i class="fa-solid fa-user-plus"></i>
                            <span>Register Beneficiary</span>
                        </button>
                    </div>
                </div>

                <!-- KPI Metric Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white dark:bg-dark-card p-4 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center text-xl flex-shrink-0">
                            <i class="fa-solid fa-users"></i>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Total Registered</p>
                            <h4 class="text-2xl font-bold text-gray-800 dark:text-white"><?php echo number_format($totalBen); ?></h4>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-dark-card p-4 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-xl flex-shrink-0">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Active / Enrolled</p>
                            <h4 class="text-2xl font-bold text-emerald-600 dark:text-emerald-400"><?php echo number_format($activeBen); ?></h4>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-dark-card p-4 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-xl flex-shrink-0">
                            <i class="fa-solid fa-gift"></i>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Assisted Beneficiaries</p>
                            <h4 class="text-2xl font-bold text-indigo-600 dark:text-indigo-400"><?php echo number_format($assistedBen); ?></h4>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-dark-card p-4 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-teal-50 dark:bg-teal-900/30 text-teal-600 dark:text-teal-400 flex items-center justify-center text-xl flex-shrink-0">
                            <i class="fa-solid fa-indian-rupee-sign"></i>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Total Aid Value</p>
                            <h4 class="text-xl font-bold text-teal-600 dark:text-teal-400">₹<?php echo number_format($totalAidMonetaryVal, 2); ?></h4>
                            <span class="text-[11px] text-gray-400"><?php echo $totalAidEvents; ?> aid events logged</span>
                        </div>
                    </div>
                </div>

                <!-- ============================================================ -->
                <!-- ADVANCED LOCATION & COORDINATOR FILTER TOOLBAR               -->
                <!-- ============================================================ -->
                <div class="bg-white dark:bg-dark-card p-5 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800 space-y-4">
                    
                    <!-- Row 1: Status Filter Tabs & Main Search Box -->
                    <div class="flex flex-col lg:flex-row justify-between items-stretch lg:items-center gap-4">
                        <!-- Status Filter Tabs -->
                        <div class="flex flex-wrap gap-1.5 bg-gray-100 dark:bg-gray-800 p-1.5 rounded-2xl">
                            <button type="button" @click="tab = 'all'" :class="tab === 'all' ? 'bg-white dark:bg-gray-700 shadow-sm text-teal-600 dark:text-teal-400 font-bold' : 'text-gray-500 dark:text-gray-300 hover:text-gray-700'" class="px-3.5 py-1.5 rounded-xl text-xs transition">
                                All (<span x-text="items.length"></span>)
                            </button>
                            <button type="button" @click="tab = 'Active'" :class="tab === 'Active' ? 'bg-white dark:bg-gray-700 shadow-sm text-emerald-600 dark:text-emerald-400 font-bold' : 'text-gray-500 dark:text-gray-300 hover:text-gray-700'" class="px-3.5 py-1.5 rounded-xl text-xs transition">
                                Active (<span x-text="countByStatus('Active')"></span>)
                            </button>
                            <button type="button" @click="tab = 'Assisted'" :class="tab === 'Assisted' ? 'bg-white dark:bg-gray-700 shadow-sm text-indigo-600 dark:text-indigo-400 font-bold' : 'text-gray-500 dark:text-gray-300 hover:text-gray-700'" class="px-3.5 py-1.5 rounded-xl text-xs transition">
                                Assisted (<span x-text="countByStatus('Assisted')"></span>)
                            </button>
                            <button type="button" @click="tab = 'Under Review'" :class="tab === 'Under Review' ? 'bg-white dark:bg-gray-700 shadow-sm text-amber-600 dark:text-amber-400 font-bold' : 'text-gray-500 dark:text-gray-300 hover:text-gray-700'" class="px-3.5 py-1.5 rounded-xl text-xs transition">
                                Under Review (<span x-text="countByStatus('Under Review')"></span>)
                            </button>
                            <button type="button" @click="tab = 'Inactive'" :class="tab === 'Inactive' ? 'bg-white dark:bg-gray-700 shadow-sm text-gray-600 dark:text-gray-400 font-bold' : 'text-gray-500 dark:text-gray-300 hover:text-gray-700'" class="px-3.5 py-1.5 rounded-xl text-xs transition">
                                Inactive
                            </button>
                        </div>

                        <!-- Full Text Search Bar -->
                        <div class="relative flex-1 max-w-md">
                            <input type="text" x-model="search" placeholder="Search name, code, phone, Aadhaar, coordinator..." class="w-full pl-9 pr-8 py-2.5 text-xs rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-teal-500">
                            <i class="fa-solid fa-magnifying-glass text-gray-400 absolute left-3 top-3 text-xs"></i>
                            <button x-show="search" @click="search = ''" class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600 text-xs">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Row 2: Location-Wise (State / District / Block) & Coordinator Filters -->
                    <div class="pt-3 border-t border-gray-100 dark:border-gray-800">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                            <!-- 1. State Filter -->
                            <div>
                                <label class="text-[11px] font-bold text-gray-600 dark:text-gray-400 flex items-center gap-1 mb-1">
                                    <i class="fa-solid fa-map-pin text-teal-600"></i> State
                                </label>
                                <select x-model="stateFilter" @change="onStateFilterChange()" class="w-full text-xs font-medium p-2.5 border rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 outline-none focus:ring-2 focus:ring-teal-500">
                                    <option value="">All States</option>
                                    <?php foreach (india_state_list() as $st): ?>
                                        <option value="<?php echo htmlspecialchars($st); ?>"><?php echo htmlspecialchars($st); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- 2. District Filter (Cascades with Selected State) -->
                            <div>
                                <label class="text-[11px] font-bold text-gray-600 dark:text-gray-400 flex items-center gap-1 mb-1">
                                    <i class="fa-solid fa-city text-teal-600"></i> District
                                </label>
                                <select x-model="districtFilter" class="w-full text-xs font-medium p-2.5 border rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 outline-none focus:ring-2 focus:ring-teal-500">
                                    <option value="">All Districts</option>
                                    <template x-for="d in availableDistricts" :key="d">
                                        <option :value="d" x-text="d"></option>
                                    </template>
                                </select>
                            </div>

                            <!-- 3. Block / Tehsil Search Filter -->
                            <div>
                                <label class="text-[11px] font-bold text-gray-600 dark:text-gray-400 flex items-center gap-1 mb-1">
                                    <i class="fa-solid fa-signs-post text-teal-600"></i> Block / Tehsil
                                </label>
                                <input type="text" x-model="blockFilter" placeholder="Filter by block..." class="w-full text-xs font-medium p-2.5 border rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 outline-none focus:ring-2 focus:ring-teal-500">
                            </div>

                            <!-- 4. Coordinator / Manager Filter -->
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

                            <!-- 5. Beneficiary Category Filter -->
                            <div>
                                <label class="text-[11px] font-bold text-gray-600 dark:text-gray-400 flex items-center gap-1 mb-1">
                                    <i class="fa-solid fa-layer-group text-teal-600"></i> Category
                                </label>
                                <select x-model="categoryFilter" class="w-full text-xs font-medium p-2.5 border rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 outline-none focus:ring-2 focus:ring-teal-500">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat['category_name']); ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Row 3: Active Filter Chips & Clear All Reset Button -->
                    <div x-show="hasActiveFilters" class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-gray-100 dark:border-gray-800 text-xs">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span class="text-gray-400 font-semibold text-[11px]">Active Filters:</span>
                            
                            <!-- State Tag -->
                            <span x-show="stateFilter" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-teal-50 text-teal-700 dark:bg-teal-900/30 dark:text-teal-300 font-medium text-[11px]">
                                State: <strong x-text="stateFilter"></strong>
                                <button type="button" @click="stateFilter = ''; onStateFilterChange();" class="hover:text-teal-900 ml-0.5"><i class="fa-solid fa-xmark"></i></button>
                            </span>

                            <!-- District Tag -->
                            <span x-show="districtFilter" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-teal-50 text-teal-700 dark:bg-teal-900/30 dark:text-teal-300 font-medium text-[11px]">
                                District: <strong x-text="districtFilter"></strong>
                                <button type="button" @click="districtFilter = ''" class="hover:text-teal-900 ml-0.5"><i class="fa-solid fa-xmark"></i></button>
                            </span>

                            <!-- Block Tag -->
                            <span x-show="blockFilter" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-teal-50 text-teal-700 dark:bg-teal-900/30 dark:text-teal-300 font-medium text-[11px]">
                                Block: <strong x-text="blockFilter"></strong>
                                <button type="button" @click="blockFilter = ''" class="hover:text-teal-900 ml-0.5"><i class="fa-solid fa-xmark"></i></button>
                            </span>

                            <!-- Coordinator Tag -->
                            <span x-show="coordinatorFilter" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300 font-medium text-[11px]">
                                Coordinator: <strong x-text="getCoordinatorName(coordinatorFilter)"></strong>
                                <button type="button" @click="coordinatorFilter = ''" class="hover:text-indigo-900 ml-0.5"><i class="fa-solid fa-xmark"></i></button>
                            </span>

                            <!-- Category Tag -->
                            <span x-show="categoryFilter" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300 font-medium text-[11px]">
                                Category: <strong x-text="categoryFilter"></strong>
                                <button type="button" @click="categoryFilter = ''" class="hover:text-purple-900 ml-0.5"><i class="fa-solid fa-xmark"></i></button>
                            </span>

                            <!-- Search Tag -->
                            <span x-show="search" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 font-medium text-[11px]">
                                Query: "<span x-text="search"></span>"
                                <button type="button" @click="search = ''" class="hover:text-gray-900 ml-0.5"><i class="fa-solid fa-xmark"></i></button>
                            </span>
                        </div>

                        <!-- Clear All Button -->
                        <button type="button" @click="clearAllFilters()" class="text-xs font-bold text-red-600 hover:text-red-700 dark:text-red-400 hover:underline flex items-center gap-1">
                            <i class="fa-solid fa-rotate-left"></i> Reset All Filters
                        </button>
                    </div>
                </div>
            </div>

            <!-- Beneficiaries Table (Desktop) -->
            <div class="hidden md:block bg-white dark:bg-dark-card rounded-2xl shadow-sm overflow-hidden border border-gray-100 dark:border-gray-800">
                <table class="w-full whitespace-no-wrap text-left">
                    <thead>
                        <tr class="text-[11px] font-bold tracking-wider text-gray-400 uppercase border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50">
                            <th class="px-5 py-3.5">Beneficiary</th>
                            <th class="px-5 py-3.5">Category & Socio-Economic</th>
                            <th class="px-5 py-3.5">Location (State/District/Block)</th>
                            <th class="px-5 py-3.5">Coordinator / In-Charge</th>
                            <th class="px-5 py-3.5">Assistance History</th>
                            <th class="px-5 py-3.5">Status</th>
                            <th class="px-5 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-sm">
                        <template x-for="item in pagedItems" :key="item.id">
                            <tr class="hover:bg-teal-50/20 dark:hover:bg-gray-800/40 transition align-top">
                                <!-- Beneficiary Column -->
                                <td class="px-5 py-4">
                                    <div class="flex items-start gap-3">
                                        <img :src="item.photo ? '../' + item.photo : 'https://ui-avatars.com/api/?name=' + encodeURIComponent(item.name || 'Beneficiary') + '&background=0F8B8D&color=fff'" 
                                             class="w-11 h-11 rounded-xl object-cover border border-gray-200 dark:border-gray-700 shadow-sm flex-shrink-0">
                                        <div>
                                            <a :href="'beneficiary_profile.php?id=' + item.id" class="font-bold text-gray-900 dark:text-white hover:text-teal-600 dark:hover:text-teal-400 transition" x-text="item.name"></a>
                                            <div class="flex items-center gap-1.5 mt-0.5">
                                                <span class="font-mono text-xs font-bold text-teal-600 dark:text-teal-400 bg-teal-50 dark:bg-teal-900/30 px-1.5 py-0.5 rounded" x-text="item.beneficiary_code || ('BEN-' + item.id)"></span>
                                                <span class="text-[11px] text-gray-400" x-show="item.gender" x-text="item.gender + (item.age ? ', ' + item.age + ' yrs' : '')"></span>
                                            </div>
                                            <p class="text-[11px] text-gray-400 mt-0.5" x-show="item.father_or_spouse_name">
                                                C/o: <span x-text="item.father_or_spouse_name"></span>
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Category & Socio-Economic -->
                                <td class="px-5 py-4 text-xs">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full font-medium bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                        <i class="fa-solid" :class="item.category_icon || 'fa-tag'"></i>
                                        <span x-text="item.category_name || 'General'"></span>
                                    </span>
                                    <div class="mt-1.5 space-y-0.5 text-gray-500 dark:text-gray-400 text-[11px]">
                                        <p x-show="item.beneficiary_type">Type: <span class="text-gray-700 dark:text-gray-200 font-medium" x-text="item.beneficiary_type"></span></p>
                                        <p x-show="item.family_members_count">Family: <span class="text-gray-700 dark:text-gray-200" x-text="item.family_members_count + ' members'"></span></p>
                                        <p x-show="item.disability_status === 'Yes'" class="text-purple-600 dark:text-purple-400 font-semibold flex items-center gap-1">
                                            <i class="fa-solid fa-wheelchair"></i> Divyang / Special Need
                                        </p>
                                    </div>
                                </td>

                                <!-- Location (State/District/Block/Address) -->
                                <td class="px-5 py-4 text-xs">
                                    <div class="font-medium text-gray-800 dark:text-gray-200 flex items-center gap-1">
                                        <i class="fa-solid fa-location-dot text-teal-600 text-xs"></i>
                                        <span class="font-bold text-gray-900 dark:text-white" x-text="item.district"></span>, 
                                        <span x-text="item.state"></span>
                                    </div>
                                    <p class="text-gray-500 dark:text-gray-400 mt-1 flex items-center gap-1" x-show="item.block">
                                        <i class="fa-solid fa-signs-post text-gray-400 text-[10px]"></i>
                                        <span>Block: <strong class="text-gray-700 dark:text-gray-300" x-text="item.block"></strong></span>
                                    </p>
                                    <div class="font-medium text-gray-500 dark:text-gray-400 mt-1 flex items-center gap-1">
                                        <i class="fa-solid fa-phone text-gray-400 text-[10px]"></i>
                                        <a :href="'tel:' + item.contact" class="hover:underline text-teal-600 dark:text-teal-400" x-text="item.contact"></a>
                                    </div>
                                </td>

                                <!-- Coordinator / In-Charge -->
                                <td class="px-5 py-4 text-xs">
                                    <div x-show="item.coordinator_name || item.registered_by_name" class="space-y-1">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 font-semibold text-[11px]">
                                            <i class="fa-solid fa-user-tie text-[10px]"></i>
                                            <span x-text="item.coordinator_name || item.registered_by_name"></span>
                                        </span>
                                        <p class="text-[10px] text-gray-400" x-show="item.coordinator_code" x-text="'Code: ' + item.coordinator_code"></p>
                                    </div>
                                    <span x-show="!item.coordinator_name && !item.registered_by_name" class="text-gray-400 italic text-[11px]">Unassigned</span>
                                </td>

                                <!-- Assistance Summary -->
                                <td class="px-5 py-4 text-xs">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 rounded font-bold" 
                                              :class="Number(item.total_aid_count) > 0 ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' : 'bg-gray-100 text-gray-500 dark:bg-gray-800'">
                                            <span x-text="item.total_aid_count || 0"></span> Aid Event(s)
                                        </span>
                                    </div>
                                    <div class="mt-1 text-gray-600 dark:text-gray-300 font-semibold" x-show="Number(item.total_financial_aid) > 0 || Number(item.total_inkind_val) > 0">
                                        Valuation: ₹<span x-text="formatMoney(Number(item.total_financial_aid || 0) + Number(item.total_inkind_val || 0))"></span>
                                    </div>
                                    <p class="text-[11px] text-gray-400 mt-0.5" x-show="item.last_aid_date">
                                        Last Aid: <span x-text="item.last_aid_date"></span>
                                    </p>
                                </td>

                                <!-- Status Badge -->
                                <td class="px-5 py-4 text-xs">
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold" :class="getStatusBadgeClass(item.status)" x-text="item.status"></span>
                                    <p class="text-[10px] text-gray-400 mt-1" x-text="'Reg: ' + (item.registration_date || 'N/A')"></p>
                                </td>

                                <!-- Action Buttons -->
                                <td class="px-5 py-4 text-sm text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a :href="'beneficiary_profile.php?id=' + item.id" class="p-2 text-teal-600 hover:bg-teal-50 dark:hover:bg-teal-900/30 rounded-lg transition" title="View Profile & Timeline">
                                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        </a>

                                        <button type="button" @click="openQuickAidModal(item)" class="p-2 text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition" title="Log Aid Handover">
                                            <i class="fa-solid fa-hand-holding-heart"></i>
                                        </button>

                                        <button type="button" @click="openEditModal(item)" class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition" title="Edit Beneficiary">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>

                                        <button type="button" @click="confirmDelete(item.id, item.name)" class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition" title="Delete Beneficiary">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <div x-show="filteredItems.length === 0" class="p-12 text-center text-gray-400 dark:text-gray-500">
                    <i class="fa-solid fa-location-crosshairs text-4xl mb-3 block opacity-40"></i>
                    <p class="font-medium text-base text-gray-700 dark:text-gray-300">No beneficiaries match your location or search filters.</p>
                    <p class="text-xs text-gray-400 mt-1">Try clearing some filters or search query.</p>
                    <button type="button" @click="clearAllFilters()" class="mt-4 px-4 py-2 rounded-xl bg-teal-50 text-teal-700 dark:bg-teal-900/30 dark:text-teal-300 font-bold text-xs">
                        Reset All Filters
                    </button>
                </div>
            </div>

            <!-- Beneficiaries Card View (Mobile) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 md:hidden pb-12">
                <template x-for="item in pagedItems" :key="item.id">
                    <div class="bg-white dark:bg-dark-card p-4 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 flex flex-col justify-between">
                        <div>
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-center gap-3">
                                    <img :src="item.photo ? '../' + item.photo : 'https://ui-avatars.com/api/?name=' + encodeURIComponent(item.name || 'Beneficiary') + '&background=0F8B8D&color=fff'" 
                                         class="w-12 h-12 rounded-xl object-cover border border-gray-200 dark:border-gray-700 flex-shrink-0">
                                    <div>
                                        <a :href="'beneficiary_profile.php?id=' + item.id" class="font-bold text-gray-900 dark:text-white text-base block" x-text="item.name"></a>
                                        <span class="font-mono text-xs font-bold text-teal-600 dark:text-teal-400" x-text="item.beneficiary_code || ('BEN-' + item.id)"></span>
                                    </div>
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold" :class="getStatusBadgeClass(item.status)" x-text="item.status"></span>
                            </div>

                            <div class="mt-3 py-2 border-t border-b border-gray-100 dark:border-gray-800 grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300">
                                <div>
                                    <span class="text-[10px] text-gray-400 uppercase block">Category</span>
                                    <span class="font-medium" x-text="item.category_name || 'General'"></span>
                                </div>
                                <div>
                                    <span class="text-[10px] text-gray-400 uppercase block">Location</span>
                                    <span class="font-bold text-gray-800 dark:text-gray-200" x-text="item.district + ', ' + item.state"></span>
                                </div>
                                <div>
                                    <span class="text-[10px] text-gray-400 uppercase block">Block</span>
                                    <span x-text="item.block || 'N/A'"></span>
                                </div>
                                <div>
                                    <span class="text-[10px] text-gray-400 uppercase block">Coordinator</span>
                                    <span class="text-indigo-600 font-medium" x-text="item.coordinator_name || item.registered_by_name || 'Unassigned'"></span>
                                </div>
                                <div>
                                    <span class="text-[10px] text-gray-400 uppercase block">Phone</span>
                                    <span x-text="item.contact"></span>
                                </div>
                                <div>
                                    <span class="text-[10px] text-gray-400 uppercase block">Aid Events</span>
                                    <span class="font-bold text-indigo-600" x-text="(item.total_aid_count || 0) + ' events'"></span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 flex items-center gap-2">
                            <a :href="'beneficiary_profile.php?id=' + item.id" class="flex-1 bg-teal-50 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300 text-center py-2 rounded-xl text-xs font-bold transition">
                                Profile & Timeline
                            </a>
                            <button type="button" @click="openQuickAidModal(item)" class="bg-indigo-600 text-white px-3 py-2 rounded-xl text-xs font-bold shadow-sm" title="Log Aid">
                                <i class="fa-solid fa-hand-holding-heart"></i>
                            </button>
                            <button type="button" @click="openEditModal(item)" class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 px-3 py-2 rounded-xl text-xs font-bold">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <button type="button" @click="confirmDelete(item.id, item.name)" class="bg-red-50 text-red-600 dark:bg-red-900/30 px-3 py-2 rounded-xl text-xs font-bold">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Pagination UI Controls -->
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
</div>

<!-- ============================================================ -->
<!-- 1. ADD BENEFICIARY MODAL                                     -->
<!-- ============================================================ -->
<div id="addBeneficiaryModal" class="fixed inset-0 hidden z-[90] items-center justify-center p-4 bg-black/60 backdrop-blur-sm overflow-y-auto">
    <div class="bg-white dark:bg-gray-900 w-full max-w-3xl rounded-3xl shadow-2xl border border-gray-100 dark:border-gray-800 overflow-hidden my-8">
        <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-gradient-to-r from-teal-50 to-emerald-50 dark:from-gray-800 dark:to-gray-800">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-xl bg-teal-600 text-white flex items-center justify-center text-lg shadow-sm">
                    <i class="fa-solid fa-user-plus"></i>
                </span>
                <div>
                    <h4 class="text-lg font-bold text-gray-900 dark:text-white">Register New Beneficiary</h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Add personal details, socio-economic category, contact, and address</p>
                </div>
            </div>
            <button type="button" class="text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 p-2 rounded-xl transition" onclick="closeAddModal()">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>

        <form action="actions/beneficiary_logic.php" method="POST" enctype="multipart/form-data" class="p-6 space-y-5 max-h-[75vh] overflow-y-auto">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="action" value="add_beneficiary">

            <!-- Section 1: Basic Information -->
            <div>
                <h5 class="text-xs font-bold uppercase tracking-wider text-teal-600 dark:text-teal-400 mb-3 flex items-center gap-1.5">
                    <i class="fa-solid fa-id-card"></i> 1. Personal & Basic Information
                </h5>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Full Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" required placeholder="e.g. Ramesh Kumar" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-teal-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Father's / Husband's Name</label>
                        <input type="text" name="father_or_spouse_name" placeholder="Care of / Guardian" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-teal-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Gender <span class="text-red-500">*</span></label>
                        <select name="gender" required class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-teal-500">
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Age (Yrs)</label>
                            <input type="number" name="age" min="0" max="120" placeholder="e.g. 45" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-teal-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Date of Birth</label>
                            <input type="date" name="dob" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-teal-500">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 2: Contact & Identity -->
            <div class="pt-3 border-t border-gray-100 dark:border-gray-800">
                <h5 class="text-xs font-bold uppercase tracking-wider text-teal-600 dark:text-teal-400 mb-3 flex items-center gap-1.5">
                    <i class="fa-solid fa-phone"></i> 2. Contact & Identity Proofs
                </h5>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Primary Mobile <span class="text-red-500">*</span></label>
                        <input type="tel" name="contact" required pattern="[0-9]{10,12}" placeholder="10-digit mobile" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-teal-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Alternate Phone</label>
                        <input type="tel" name="alternate_contact" placeholder="Optional phone" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-teal-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Email Address</label>
                        <input type="email" name="email" placeholder="Optional email" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-teal-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Aadhaar Card No.</label>
                        <input type="text" name="aadhar_no" placeholder="12-digit Aadhaar" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-teal-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Ration Card No.</label>
                        <input type="text" name="ration_card_no" placeholder="Ration Card ID" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-teal-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">ID Proof Doc (PDF/JPG)</label>
                        <input type="file" name="id_proof_doc" accept="image/*,application/pdf" class="w-full text-xs text-gray-500 dark:text-gray-400 file:mr-2 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100">
                    </div>
                </div>
            </div>

            <!-- Section 3: Socio-Economic & Category -->
            <div class="pt-3 border-t border-gray-100 dark:border-gray-800">
                <h5 class="text-xs font-bold uppercase tracking-wider text-teal-600 dark:text-teal-400 mb-3 flex items-center gap-1.5">
                    <i class="fa-solid fa-layer-group"></i> 3. Category & Socio-Economic Status
                </h5>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Beneficiary Category <span class="text-red-500">*</span></label>
                        <select name="category_id" required class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-teal-500">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo (int)$cat['id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Beneficiary Type</label>
                        <select name="beneficiary_type" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-teal-500">
                            <option value="General / BPL">General / BPL</option>
                            <option value="Antyodaya Anna Yojana (AAY)">Antyodaya Anna Yojana (AAY)</option>
                            <option value="Priority Household (PHH)">Priority Household (PHH)</option>
                            <option value="Non-Priority Household (NPHH)">Non-Priority Household (NPHH)</option>
                            <option value="Destitute / Homeless">Destitute / Homeless</option>
                            <option value="Orphan / Foster Child">Orphan / Foster Child</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Annual Family Income (₹)</label>
                        <input type="number" step="0.01" name="annual_income" placeholder="e.g. 48000" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-teal-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Family Members Count</label>
                        <input type="number" min="1" name="family_members_count" value="1" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-teal-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Disability / Divyang</label>
                        <select name="disability_status" id="add_disability_status" onchange="toggleDisabilityDetail(this, 'add_disability_box')" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-teal-500">
                            <option value="No">No</option>
                            <option value="Yes">Yes (Divyang)</option>
                        </select>
                    </div>

                    <div id="add_disability_box" class="hidden">
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Disability Details</label>
                        <input type="text" name="disability_details" placeholder="e.g. Locomotor 40%, Visual" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-teal-500">
                    </div>
                </div>
            </div>

            <!-- Section 4: Address & Location -->
            <div class="pt-3 border-t border-gray-100 dark:border-gray-800">
                <h5 class="text-xs font-bold uppercase tracking-wider text-teal-600 dark:text-teal-400 mb-3 flex items-center gap-1.5">
                    <i class="fa-solid fa-map-location-dot"></i> 4. Residential Address & Location
                </h5>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">State <span class="text-red-500">*</span></label>
                        <select name="state" id="add_state" required onchange="updateAddDistricts()" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-teal-500">
                            <option value="">Select State</option>
                            <?php foreach (india_state_list() as $st): ?>
                                <option value="<?php echo htmlspecialchars($st); ?>"><?php echo htmlspecialchars($st); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">District <span class="text-red-500">*</span></label>
                        <select name="district" id="add_district" required class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-teal-500">
                            <option value="">Select District</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Block / Taluka / Tehsil</label>
                        <input type="text" name="block" placeholder="Block name" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-teal-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Village / Town / City</label>
                        <input type="text" name="village_city" placeholder="Village or City" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-teal-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Pincode</label>
                        <input type="text" name="pincode" pattern="[0-9]{6}" placeholder="6-digit PIN" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-teal-500">
                    </div>

                    <div class="md:col-span-3">
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Complete House Address <span class="text-red-500">*</span></label>
                        <textarea name="address" required rows="2" placeholder="House no., Landmark, Street address" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-teal-500"></textarea>
                    </div>
                </div>
            </div>

            <!-- Section 5: Photo, Coordinator & Administrative Status -->
            <div class="pt-3 border-t border-gray-100 dark:border-gray-800">
                <h5 class="text-xs font-bold uppercase tracking-wider text-teal-600 dark:text-teal-400 mb-3 flex items-center gap-1.5">
                    <i class="fa-solid fa-camera"></i> 5. Profile Photo, Assigned Coordinator & Status
                </h5>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Upload Photo (JPG/PNG)</label>
                        <input type="file" name="photo" id="add_photo_input" accept="image/jpeg,image/png,image/webp" onchange="previewAddPhoto(this)" class="w-full text-xs text-gray-500 dark:text-gray-400 file:mr-2 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100">
                    </div>

                    <div class="flex items-center gap-3">
                        <img id="add_photo_preview" class="w-14 h-14 rounded-2xl object-cover border border-gray-200 dark:border-gray-700 hidden shadow-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Assigned Coordinator</label>
                        <select name="coordinator_id" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-teal-500">
                            <option value="">-- Select Coordinator --</option>
                            <?php foreach ($coordinators as $c): ?>
                                <option value="<?php echo (int)$c['id']; ?>">
                                    <?php echo htmlspecialchars($c['name'] . ($c['coordinator_code'] ? ' (' . $c['coordinator_code'] . ')' : '')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Initial Status</label>
                        <select name="status" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-teal-500">
                            <option value="Active" selected>Active</option>
                            <option value="Under Review">Under Review</option>
                            <option value="Assisted">Assisted</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Remarks / Internal Notes</label>
                        <input type="text" name="remarks" placeholder="Optional notes for coordinator" class="w-full px-3.5 py-2 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-teal-500">
                    </div>
                </div>
            </div>

            <!-- Modal Action Buttons -->
            <div class="flex gap-3 pt-4 border-t border-gray-100 dark:border-gray-800">
                <button type="submit" class="flex-1 bg-teal-600 hover:bg-teal-700 text-white font-bold py-3 rounded-xl shadow-sm text-sm transition">
                    <i class="fa-solid fa-check mr-1"></i> Register Beneficiary
                </button>
                <button type="button" onclick="closeAddModal()" class="px-6 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-bold py-3 rounded-xl text-sm transition">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- 2. EDIT BENEFICIARY MODAL                                    -->
<!-- ============================================================ -->
<div id="editBeneficiaryModal" class="fixed inset-0 hidden z-[90] items-center justify-center p-4 bg-black/60 backdrop-blur-sm overflow-y-auto">
    <div class="bg-white dark:bg-gray-900 w-full max-w-3xl rounded-3xl shadow-2xl border border-gray-100 dark:border-gray-800 overflow-hidden my-8">
        <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-800">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center text-lg shadow-sm">
                    <i class="fa-solid fa-user-pen"></i>
                </span>
                <div>
                    <h4 class="text-lg font-bold text-gray-900 dark:text-white">Edit Beneficiary Details</h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400" id="editModalSub">Update beneficiary profile information</p>
                </div>
            </div>
            <button type="button" class="text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 p-2 rounded-xl transition" onclick="closeEditModal()">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>

        <form id="editBeneficiaryForm" action="actions/beneficiary_logic.php" method="POST" enctype="multipart/form-data" class="p-6 space-y-5 max-h-[75vh] overflow-y-auto">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="action" value="update_beneficiary">
            <input type="hidden" name="id" id="edit_ben_id" value="">

            <!-- Basic Info -->
            <div>
                <h5 class="text-xs font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400 mb-3 flex items-center gap-1.5">
                    <i class="fa-solid fa-id-card"></i> 1. Personal Details
                </h5>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Full Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" id="edit_name" required class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Father's / Spouse's Name</label>
                        <input type="text" name="father_or_spouse_name" id="edit_father_or_spouse" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Gender <span class="text-red-500">*</span></label>
                        <select name="gender" id="edit_gender" required class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500">
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Age (Yrs)</label>
                            <input type="number" name="age" id="edit_age" min="0" max="120" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Date of Birth</label>
                            <input type="date" name="dob" id="edit_dob" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500">
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
                        <input type="tel" name="contact" id="edit_contact" required class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Alternate Phone</label>
                        <input type="tel" name="alternate_contact" id="edit_alt_contact" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Email</label>
                        <input type="email" name="email" id="edit_email" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Aadhaar Card No.</label>
                        <input type="text" name="aadhar_no" id="edit_aadhar" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Ration Card No.</label>
                        <input type="text" name="ration_card_no" id="edit_ration_card" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500">
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
                        <select name="category_id" id="edit_category_id" required class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo (int)$cat['id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Beneficiary Type</label>
                        <select name="beneficiary_type" id="edit_beneficiary_type" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500">
                            <option value="General / BPL">General / BPL</option>
                            <option value="Antyodaya Anna Yojana (AAY)">Antyodaya Anna Yojana (AAY)</option>
                            <option value="Priority Household (PHH)">Priority Household (PHH)</option>
                            <option value="Non-Priority Household (NPHH)">Non-Priority Household (NPHH)</option>
                            <option value="Destitute / Homeless">Destitute / Homeless</option>
                            <option value="Orphan / Foster Child">Orphan / Foster Child</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Annual Income (₹)</label>
                        <input type="number" step="0.01" name="annual_income" id="edit_annual_income" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Family Members</label>
                        <input type="number" min="1" name="family_members_count" id="edit_family_members" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Disability / Divyang</label>
                        <select name="disability_status" id="edit_disability_status" onchange="toggleDisabilityDetail(this, 'edit_disability_box')" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500">
                            <option value="No">No</option>
                            <option value="Yes">Yes (Divyang)</option>
                        </select>
                    </div>

                    <div id="edit_disability_box" class="hidden">
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Disability Details</label>
                        <input type="text" name="disability_details" id="edit_disability_details" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500">
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
                        <select name="state" id="edit_state" required onchange="updateEditDistricts()" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500">
                            <option value="">Select State</option>
                            <?php foreach (india_state_list() as $st): ?>
                                <option value="<?php echo htmlspecialchars($st); ?>"><?php echo htmlspecialchars($st); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">District <span class="text-red-500">*</span></label>
                        <select name="district" id="edit_district" required class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500">
                            <option value="">Select District</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Block / Tehsil</label>
                        <input type="text" name="block" id="edit_block" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="md:col-span-3">
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Complete Address <span class="text-red-500">*</span></label>
                        <textarea name="address" id="edit_address" required rows="2" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                </div>
            </div>

            <!-- Status, Coordinator & Photo -->
            <div class="pt-3 border-t border-gray-100 dark:border-gray-800">
                <h5 class="text-xs font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400 mb-3 flex items-center gap-1.5">
                    <i class="fa-solid fa-camera"></i> 5. Photo, Coordinator & Administrative Status
                </h5>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Replace Photo</label>
                        <input type="file" name="photo" id="edit_photo_input" accept="image/jpeg,image/png,image/webp" onchange="previewEditPhoto(this)" class="w-full text-xs text-gray-500 dark:text-gray-400 file:mr-2 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>

                    <div class="flex items-center gap-3">
                        <img id="edit_photo_preview" class="w-14 h-14 rounded-2xl object-cover border border-gray-200 dark:border-gray-700 shadow-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Assigned Coordinator</label>
                        <select name="coordinator_id" id="edit_coordinator_id" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Select Coordinator --</option>
                            <?php foreach ($coordinators as $c): ?>
                                <option value="<?php echo (int)$c['id']; ?>">
                                    <?php echo htmlspecialchars($c['name'] . ($c['coordinator_code'] ? ' (' . $c['coordinator_code'] . ')' : '')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Current Status</label>
                        <select name="status" id="edit_status" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500">
                            <option value="Active">Active</option>
                            <option value="Assisted">Assisted</option>
                            <option value="Under Review">Under Review</option>
                            <option value="Inactive">Inactive</option>
                            <option value="Archived">Archived</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Remarks</label>
                        <input type="text" name="remarks" id="edit_remarks" class="w-full px-3.5 py-2 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="flex gap-3 pt-4 border-t border-gray-100 dark:border-gray-800">
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl shadow-sm text-sm transition">
                    <i class="fa-solid fa-save mr-1"></i> Save Changes
                </button>
                <button type="button" onclick="closeEditModal()" class="px-6 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-bold py-3 rounded-xl text-sm transition">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- 3. QUICK LOG ASSISTANCE MODAL (Direct Handover)              -->
<!-- ============================================================ -->
<div id="quickAidModal" class="fixed inset-0 hidden z-[90] items-center justify-center p-4 bg-black/60 backdrop-blur-sm overflow-y-auto">
    <div class="bg-white dark:bg-gray-900 w-full max-w-2xl rounded-3xl shadow-2xl border border-gray-100 dark:border-gray-800 overflow-hidden my-8">
        <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-gray-800 dark:to-gray-800">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center text-lg shadow-sm">
                    <i class="fa-solid fa-hand-holding-heart"></i>
                </span>
                <div>
                    <h4 class="text-lg font-bold text-gray-900 dark:text-white">Log Assistance Handover</h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400" id="quickAidSub">Record welfare items, financial aid, or materials provided</p>
                </div>
            </div>
            <button type="button" class="text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 p-2 rounded-xl transition" onclick="closeQuickAidModal()">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>

        <form action="actions/beneficiary_logic.php" method="POST" enctype="multipart/form-data" class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="action" value="add_assistance">

            <!-- Beneficiary Selector -->
            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Select Beneficiary <span class="text-red-500">*</span></label>
                <select name="beneficiary_id" id="quick_aid_ben_id" required class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-indigo-500">
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
                        <option value="Financial Aid">Financial Aid / Direct Cash Support</option>
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
                <textarea name="description" required rows="2" placeholder="Describe the assistance provided (e.g. Monthly dry ration kit containing 10kg rice, 5kg wheat, 2L oil...)" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-indigo-500"></textarea>
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

            <!-- Distribution Location & Handover Person -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Distribution Location / Center</label>
                    <input type="text" name="distribution_location" placeholder="e.g. Head Office / Community Hall" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Handed Over By (Coordinator/Guest)</label>
                    <input type="text" name="given_by" placeholder="Name of distributor" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            <!-- Handover Proof Photo -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center">
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Proof Photo (Handover)</label>
                    <input type="file" name="proof_photo" accept="image/jpeg,image/png,image/webp" class="w-full text-xs text-gray-500 dark:text-gray-400 file:mr-2 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Receipt / Voucher Ref No.</label>
                    <input type="text" name="receipt_no" placeholder="Optional voucher/bill no." class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            <div class="flex gap-3 pt-4 border-t border-gray-100 dark:border-gray-800">
                <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-xl shadow-sm text-sm transition">
                    <i class="fa-solid fa-check mr-1"></i> Record Aid Handover
                </button>
                <button type="button" onclick="closeQuickAidModal()" class="px-6 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-bold py-3 rounded-xl text-sm transition">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const indiaLocations = <?php echo india_state_district_js(); ?>;

    function beneficiaryManager() {
        return {
            items: <?php echo json_encode($beneficiaries); ?>,
            coordinators: <?php echo json_encode($coordinators); ?>,
            search: '',
            tab: 'all',
            stateFilter: '',
            districtFilter: '',
            blockFilter: '',
            coordinatorFilter: '',
            categoryFilter: '',
            availableDistricts: [],
            page: 1,
            pageSize: 10,

            init() {
                this.$watch('search', () => this.page = 1);
                this.$watch('tab', () => this.page = 1);
                this.$watch('stateFilter', () => this.page = 1);
                this.$watch('districtFilter', () => this.page = 1);
                this.$watch('blockFilter', () => this.page = 1);
                this.$watch('coordinatorFilter', () => this.page = 1);
                this.$watch('categoryFilter', () => this.page = 1);

                // Collect initial unique districts from all items
                this.updateAvailableDistricts();
            },

            onStateFilterChange() {
                this.districtFilter = '';
                this.updateAvailableDistricts();
            },

            updateAvailableDistricts() {
                if (this.stateFilter && indiaLocations[this.stateFilter]) {
                    this.availableDistricts = indiaLocations[this.stateFilter];
                } else {
                    // Extract unique districts from existing data
                    const set = new Set();
                    this.items.forEach(i => {
                        if (i.district) set.add(i.district);
                    });
                    this.availableDistricts = Array.from(set).sort();
                }
            },

            get hasActiveFilters() {
                return Boolean(this.stateFilter || this.districtFilter || this.blockFilter || this.coordinatorFilter || this.categoryFilter || this.search);
            },

            clearAllFilters() {
                this.stateFilter = '';
                this.districtFilter = '';
                this.blockFilter = '';
                this.coordinatorFilter = '';
                this.categoryFilter = '';
                this.search = '';
                this.tab = 'all';
                this.updateAvailableDistricts();
                this.page = 1;
            },

            getCoordinatorName(coordId) {
                if (!coordId) return '';
                const found = this.coordinators.find(c => String(c.id) === String(coordId));
                return found ? found.name : ('ID #' + coordId);
            },

            countByStatus(st) {
                return this.items.filter(i => i.status === st).length;
            },

            get filteredItems() {
                const s = this.search.toLowerCase().trim();
                const stFilter = this.stateFilter.toLowerCase().trim();
                const distFilter = this.districtFilter.toLowerCase().trim();
                const blkFilter = this.blockFilter.toLowerCase().trim();
                const coordId = String(this.coordinatorFilter || '');

                return this.items.filter(item => {
                    // 1. Status Tab Filter
                    if (this.tab !== 'all' && item.status !== this.tab) return false;

                    // 2. State Filter
                    if (stFilter && (item.state || '').toLowerCase() !== stFilter) return false;

                    // 3. District Filter
                    if (distFilter && (item.district || '').toLowerCase() !== distFilter) return false;

                    // 4. Block Filter (Sub-string search)
                    if (blkFilter && !(item.block || '').toLowerCase().includes(blkFilter)) return false;

                    // 5. Coordinator Filter
                    if (coordId) {
                        const itemCoord = String(item.coordinator_id || '');
                        const itemReg = String(item.registered_by || '');
                        if (itemCoord !== coordId && itemReg !== coordId) return false;
                    }

                    // 6. Category Filter
                    if (this.categoryFilter && (item.category_name || '') !== this.categoryFilter) return false;

                    // 7. Search Query across multiple fields
                    if (s) {
                        const name = (item.name || '').toLowerCase();
                        const code = (item.beneficiary_code || '').toLowerCase();
                        const contact = (item.contact || '').toLowerCase();
                        const aadhar = (item.aadhar_no || '').toLowerCase();
                        const district = (item.district || '').toLowerCase();
                        const state = (item.state || '').toLowerCase();
                        const block = (item.block || '').toLowerCase();
                        const address = (item.address || '').toLowerCase();
                        const coordName = (item.coordinator_name || item.registered_by_name || '').toLowerCase();
                        
                        return name.includes(s) 
                            || code.includes(s) 
                            || contact.includes(s) 
                            || aadhar.includes(s) 
                            || district.includes(s) 
                            || state.includes(s) 
                            || block.includes(s) 
                            || address.includes(s)
                            || coordName.includes(s);
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

            getStatusBadgeClass(status) {
                return {
                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400': status === 'Active',
                    'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400': status === 'Assisted',
                    'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400': status === 'Under Review',
                    'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400': status === 'Inactive' || status === 'Archived'
                };
            },

            openAddModal() {
                window.openAddModal();
            },

            openEditModal(item) {
                window.openEditModal(item);
            },

            openQuickAidModal(item = null) {
                window.openQuickAidModal(item);
            },

            confirmDelete(id, name) {
                window.confirmDeleteBeneficiary(id, name, <?php echo json_encode($csrfToken); ?>);
            }
        };
    }

    // Modal Control Helpers
    function openAddModal() {
        const m = document.getElementById('addBeneficiaryModal');
        m.classList.remove('hidden');
        m.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function closeAddModal() {
        const m = document.getElementById('addBeneficiaryModal');
        m.classList.add('hidden');
        m.classList.remove('flex');
        document.body.style.overflow = 'unset';
    }

    function openEditModal(item) {
        document.getElementById('edit_ben_id').value = item.id || '';
        document.getElementById('edit_name').value = item.name || '';
        document.getElementById('edit_father_or_spouse').value = item.father_or_spouse_name || '';
        document.getElementById('edit_gender').value = item.gender || 'Male';
        document.getElementById('edit_age').value = item.age || '';
        document.getElementById('edit_dob').value = item.dob || '';
        document.getElementById('edit_contact').value = item.contact || '';
        document.getElementById('edit_alt_contact').value = item.alternate_contact || '';
        document.getElementById('edit_email').value = item.email || '';
        document.getElementById('edit_aadhar').value = item.aadhar_no || '';
        document.getElementById('edit_ration_card').value = item.ration_card_no || '';
        document.getElementById('edit_category_id').value = item.category_id || '';
        document.getElementById('edit_beneficiary_type').value = item.beneficiary_type || 'General / BPL';
        document.getElementById('edit_annual_income').value = item.annual_income || '';
        document.getElementById('edit_family_members').value = item.family_members_count || 1;
        document.getElementById('edit_disability_status').value = item.disability_status || 'No';
        document.getElementById('edit_disability_details').value = item.disability_details || '';
        
        toggleDisabilityDetail(document.getElementById('edit_disability_status'), 'edit_disability_box');

        document.getElementById('edit_state').value = item.state || '';
        updateEditDistricts(item.district || '');
        document.getElementById('edit_block').value = item.block || '';
        document.getElementById('edit_address').value = item.address || '';
        document.getElementById('edit_coordinator_id').value = item.coordinator_id || item.registered_by || '';
        document.getElementById('edit_status').value = item.status || 'Active';
        document.getElementById('edit_remarks').value = item.remarks || '';

        const photoPreview = document.getElementById('edit_photo_preview');
        if (item.photo) {
            photoPreview.src = '../' + item.photo;
            photoPreview.classList.remove('hidden');
        } else {
            photoPreview.src = 'https://ui-avatars.com/api/?name=' + encodeURIComponent(item.name || 'Beneficiary') + '&background=0F8B8D&color=fff';
            photoPreview.classList.remove('hidden');
        }

        document.getElementById('editModalSub').textContent = (item.name || '') + ' (' + (item.beneficiary_code || ('BEN-' + item.id)) + ')';

        const m = document.getElementById('editBeneficiaryModal');
        m.classList.remove('hidden');
        m.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function closeEditModal() {
        const m = document.getElementById('editBeneficiaryModal');
        m.classList.add('hidden');
        m.classList.remove('flex');
        document.body.style.overflow = 'unset';
    }

    function openQuickAidModal(item = null) {
        const select = document.getElementById('quick_aid_ben_id');
        if (item && item.id) {
            select.value = item.id;
            document.getElementById('quickAidSub').textContent = 'Recording aid for: ' + item.name + ' (' + (item.beneficiary_code || ('BEN-' + item.id)) + ')';
        } else {
            select.value = '';
            document.getElementById('quickAidSub').textContent = 'Record welfare items, financial aid, or materials provided';
        }

        const m = document.getElementById('quickAidModal');
        m.classList.remove('hidden');
        m.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function closeQuickAidModal() {
        const m = document.getElementById('quickAidModal');
        m.classList.add('hidden');
        m.classList.remove('flex');
        document.body.style.overflow = 'unset';
    }

    function updateAddDistricts() {
        const state = document.getElementById('add_state').value;
        const distSelect = document.getElementById('add_district');
        const dists = indiaLocations[state] || [];
        let html = '<option value="">Select District</option>';
        dists.forEach(d => {
            html += `<option value="${d}">${d}</option>`;
        });
        distSelect.innerHTML = html;
    }

    function updateEditDistricts(selectedDistrict = '') {
        const state = document.getElementById('edit_state').value;
        const distSelect = document.getElementById('edit_district');
        const dists = indiaLocations[state] || [];
        let html = '<option value="">Select District</option>';
        dists.forEach(d => {
            const sel = (d === selectedDistrict) ? 'selected' : '';
            html += `<option value="${d}" ${sel}>${d}</option>`;
        });
        distSelect.innerHTML = html;
    }

    function toggleDisabilityDetail(selectEl, targetBoxId) {
        const box = document.getElementById(targetBoxId);
        if (selectEl.value === 'Yes') {
            box.classList.remove('hidden');
        } else {
            box.classList.add('hidden');
        }
    }

    function previewAddPhoto(input) {
        if (input.files && input.files[0]) {
            const preview = document.getElementById('add_photo_preview');
            preview.src = URL.createObjectURL(input.files[0]);
            preview.classList.remove('hidden');
        }
    }

    function previewEditPhoto(input) {
        if (input.files && input.files[0]) {
            const preview = document.getElementById('edit_photo_preview');
            preview.src = URL.createObjectURL(input.files[0]);
            preview.classList.remove('hidden');
        }
    }

    function confirmDeleteBeneficiary(id, name, csrf) {
        if (confirm(`Are you sure you want to permanently delete beneficiary "${name}"?\nAll associated assistance timeline records will also be removed.`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'actions/beneficiary_logic.php';
            form.innerHTML = `
                <input type="hidden" name="csrf_token" value="${csrf}">
                <input type="hidden" name="action" value="delete_beneficiary">
                <input type="hidden" name="id" value="${id}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
</script>

<?php require 'includes/footer.php'; ?>
