<?php
require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../includes/india_locations.php';
require_once '../includes/healthcare_map_helper.php';

$csrfToken = generateCsrfToken();

if (!checkRole($pdo, 'coordinator')) {
    setFlash('error', 'Unauthorized access. Coordinator/Manager/Admin role required.');
    header('Location: dashboard.php');
    exit;
}

require 'includes/header.php';

// Ensure table exists
$tableExists = dbTableExists($pdo, 'healthcare_providers');

// Fetch all providers
$providers = [];
if ($tableExists) {
    $stmt = $pdo->query("
        SELECT h.*, u.name AS created_by_name 
        FROM healthcare_providers h
        LEFT JOIN users u ON h.created_by = u.id
        ORDER BY h.id DESC
    ");
    $providers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($providers as &$p) {
        $p['map_details'] = getProviderMapDetails($p);
    }
    unset($p);
}

// Compute Metrics
$totalProviders = count($providers);
$hospitalClinicCount = 0;
$pathologyPharmacyCount = 0;
$doctorCount = 0;
$activeCount = 0;
$verifiedCount = 0;

foreach ($providers as $p) {
    if (in_array($p['type'], ['hospital', 'clinic'])) {
        $hospitalClinicCount++;
    } elseif (in_array($p['type'], ['pathology_lab', 'pharmacy'])) {
        $pathologyPharmacyCount++;
    } elseif ($p['type'] === 'doctor') {
        $doctorCount++;
    }

    if ($p['status'] === 'active') {
        $activeCount++;
    }
    if ((int)($p['is_verified'] ?? 0) === 1) {
        $verifiedCount++;
    }
}

$indiaStatesJson = india_state_district_js();
$indiaStatesList = india_state_list();
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900" x-data="healthcareManager()">
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
                            <i class="fa-solid fa-hospital-user text-xl"></i>
                        </span>
                        <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white tracking-tight">Healthcare Directory</h1>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Manage panel hospitals, clinics, pathology labs, pharmacies & doctors with NGO tie-ups and beneficiary discounts.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <button @click="openAddModal()" class="inline-flex items-center gap-2 px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-all duration-150 hover:shadow">
                        <i class="fa-solid fa-plus text-xs"></i>
                        <span>Add Healthcare Provider</span>
                    </button>

                    <button @click="exportCSV()" class="inline-flex items-center gap-2 px-3.5 py-2.5 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-xl transition-colors shadow-sm">
                        <i class="fa-solid fa-file-csv text-teal-600"></i>
                        <span class="hidden sm:inline">Export</span>
                    </button>
                </div>
            </div>

            <!-- KPI Metric Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <!-- Total Providers -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700/60 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Total Network</p>
                        <h3 class="text-2xl font-black text-gray-900 dark:text-white mt-1" x-text="items.length"></h3>
                        <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1 font-medium flex items-center gap-1">
                            <i class="fa-solid fa-circle-check text-[10px]"></i>
                            <span><?php echo $verifiedCount; ?> Verified Tie-ups</span>
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-teal-50 dark:bg-teal-900/30 flex items-center justify-center text-teal-600 dark:text-teal-400 text-xl shadow-inner">
                        <i class="fa-solid fa-hospital"></i>
                    </div>
                </div>

                <!-- Hospitals & Clinics -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700/60 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Hospitals & Clinics</p>
                        <h3 class="text-2xl font-black text-blue-600 dark:text-blue-400 mt-1"><?php echo $hospitalClinicCount; ?></h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 font-medium">Eye, Dental & Multi-speciality</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400 text-xl shadow-inner">
                        <i class="fa-solid fa-square-h"></i>
                    </div>
                </div>

                <!-- Pathology & Pharmacies -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700/60 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Labs & Pharmacies</p>
                        <h3 class="text-2xl font-black text-purple-600 dark:text-purple-400 mt-1"><?php echo $pathologyPharmacyCount; ?></h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 font-medium">Diagnostic & Generic Meds</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center text-purple-600 dark:text-purple-400 text-xl shadow-inner">
                        <i class="fa-solid fa-flask-vial"></i>
                    </div>
                </div>

                <!-- Doctors & Consultants -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700/60 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Doctors / Specialists</p>
                        <h3 class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1"><?php echo $doctorCount; ?></h3>
                        <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1 font-medium">Available for Camps</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 dark:text-amber-400 text-xl shadow-inner">
                        <i class="fa-solid fa-user-doctor"></i>
                    </div>
                </div>
            </div>

            <!-- Filters & Search Toolbar -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 md:p-5 border border-gray-100 dark:border-gray-700/60 shadow-sm mb-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
                    <!-- Search Input -->
                    <div class="lg:col-span-2 relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                        <input type="text" x-model="search" placeholder="Search provider name, code, contact..." class="w-full pl-9 pr-4 py-2 bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-500">
                    </div>

                    <!-- Type Filter -->
                    <div>
                        <select x-model="filterType" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                            <option value="">All Types</option>
                            <option value="hospital">Hospital</option>
                            <option value="clinic">Clinic</option>
                            <option value="pathology_lab">Pathology Lab</option>
                            <option value="pharmacy">Pharmacy</option>
                            <option value="doctor">Doctor</option>
                        </select>
                    </div>

                    <!-- Speciality Filter -->
                    <div>
                        <select x-model="filterSpeciality" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                            <option value="">All Specialities</option>
                            <option value="eye">Eye Care</option>
                            <option value="dental">Dental Care</option>
                            <option value="other">Other Speciality</option>
                        </select>
                    </div>

                    <!-- State Filter -->
                    <div>
                        <select x-model="filterState" @change="filterDistrict = ''" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                            <option value="">All States</option>
                            <?php foreach ($indiaStatesList as $st): ?>
                                <option value="<?php echo htmlspecialchars($st); ?>"><?php echo htmlspecialchars($st); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <select x-model="filterStatus" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                            <option value="">All Statuses</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="pending_approval">Pending Approval</option>
                        </select>
                    </div>
                </div>

                <!-- Secondary Filter Bar & View Toggle -->
                <div class="flex flex-wrap items-center justify-between gap-3 pt-3 mt-3 border-t border-gray-100 dark:border-gray-700/50 text-xs">
                    <div class="flex flex-wrap items-center gap-3">
                        <!-- Emergency Available Filter -->
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" x-model="filterEmergencyOnly" class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                            <span class="text-gray-600 dark:text-gray-300 font-medium">24x7 Emergency Only</span>
                        </label>

                        <!-- District Filter (if State Selected) -->
                        <template x-if="filterState && districtOptions.length > 0">
                            <div class="inline-flex items-center gap-1.5">
                                <span class="text-gray-400">District:</span>
                                <select x-model="filterDistrict" class="px-2.5 py-1 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-xs text-gray-800 dark:text-gray-200">
                                    <option value="">All Districts</option>
                                    <template x-for="d in districtOptions" :key="d">
                                        <option :value="d" x-text="d"></option>
                                    </template>
                                </select>
                            </div>
                        </template>

                        <!-- Clear Filters -->
                        <button x-show="hasActiveFilters" @click="resetFilters()" class="text-red-500 hover:text-red-600 font-medium inline-flex items-center gap-1 transition-colors">
                            <i class="fa-solid fa-rotate-left"></i>
                            <span>Clear Filters</span>
                        </button>
                    </div>

                    <!-- View Switcher (Grid vs Table) -->
                    <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-700/60 p-1 rounded-xl">
                        <button @click="viewMode = 'grid'" :class="viewMode === 'grid' ? 'bg-white dark:bg-gray-800 text-teal-600 shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700'" class="px-2.5 py-1 rounded-lg font-medium transition-all flex items-center gap-1.5">
                            <i class="fa-solid fa-grip"></i>
                            <span class="hidden sm:inline">Cards</span>
                        </button>
                        <button @click="viewMode = 'table'" :class="viewMode === 'table' ? 'bg-white dark:bg-gray-800 text-teal-600 shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700'" class="px-2.5 py-1 rounded-lg font-medium transition-all flex items-center gap-1.5">
                            <i class="fa-solid fa-list"></i>
                            <span class="hidden sm:inline">Table</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Provider Listing Container -->
            <!-- 1. GRID / CARD VIEW -->
            <div x-show="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <template x-for="p in paginatedItems" :key="'card-' + p.id">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-sm hover:shadow-md transition-all duration-200 flex flex-col justify-between overflow-hidden group">
                        <div class="p-5">
                            <!-- Card Header: Badges & Verification -->
                            <div class="flex items-start justify-between gap-3 mb-3">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold uppercase tracking-wider" :class="typeBadgeClass(p.type)" x-text="typeLabel(p.type)"></span>
                                    <span class="px-2 py-0.5 rounded-lg text-[10px] font-semibold bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300" x-text="specialityLabel(p)"></span>
                                    <template x-if="parseInt(p.emergency_available) === 1">
                                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-rose-50 text-rose-600 dark:bg-rose-900/30 dark:text-rose-400 border border-rose-100 dark:border-rose-800 flex items-center gap-1">
                                            <i class="fa-solid fa-truck-medical text-[9px]"></i> 24x7
                                        </span>
                                    </template>
                                </div>

                                <div class="flex items-center gap-1.5">
                                    <button @click="toggleVerified(p)" class="transition-transform active:scale-95" :title="parseInt(p.is_verified) === 1 ? 'Verified Partner (Click to toggle)' : 'Unverified (Click to verify)'">
                                        <i class="fa-solid fa-certificate text-base" :class="parseInt(p.is_verified) === 1 ? 'text-teal-500' : 'text-gray-300 dark:text-gray-600'"></i>
                                    </button>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold" :class="statusClass(p.status)" x-text="p.status"></span>
                                </div>
                            </div>

                            <!-- Provider Title & Photo/Icon -->
                            <div class="flex items-start gap-3.5 mb-3">
                                <div class="w-12 h-12 rounded-xl bg-gray-50 dark:bg-gray-700/50 border border-gray-100 dark:border-gray-700 flex-shrink-0 overflow-hidden flex items-center justify-center">
                                    <template x-if="p.photo">
                                        <img :src="'../' + p.photo" :alt="p.name" class="w-full h-full object-cover">
                                    </template>
                                    <template x-if="!p.photo">
                                        <i class="fa-solid text-gray-400 text-lg" :class="typeIcon(p.type)"></i>
                                    </template>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-bold text-gray-900 dark:text-white text-base leading-snug truncate group-hover:text-teal-600 dark:group-hover:text-teal-400 transition-colors" x-text="p.name"></h4>
                                    <p class="text-xs font-mono text-teal-600 dark:text-teal-400 mt-0.5" x-text="p.provider_code"></p>
                                    <template x-if="p.contact_person">
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5">
                                            <i class="fa-solid fa-user-tie text-[10px] text-gray-400 mr-1"></i>
                                            <span x-text="p.contact_person"></span>
                                        </p>
                                    </template>
                                </div>
                            </div>

                            <!-- Location Info & Map Pin -->
                            <div class="text-xs text-gray-600 dark:text-gray-300 space-y-1.5 mb-3 bg-gray-50/60 dark:bg-gray-750 p-2.5 rounded-xl border border-gray-100/80 dark:border-gray-700/50">
                                <div class="flex items-start justify-between gap-1.5">
                                    <div class="flex items-start gap-1.5 min-w-0">
                                        <i class="fa-solid fa-location-dot text-teal-600 dark:text-teal-400 mt-0.5 flex-shrink-0 text-[11px]"></i>
                                        <p class="truncate font-medium text-gray-800 dark:text-gray-200" x-text="(p.block ? p.block + ', ' : '') + p.district + ', ' + p.state"></p>
                                    </div>
                                    <a :href="getMapLink(p)" target="_blank" class="px-1.5 py-0.5 rounded-md bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 hover:bg-rose-100 border border-rose-100 dark:border-rose-800/40 text-[10px] font-bold flex-shrink-0 flex items-center gap-1 transition-colors" title="Open in Google Maps">
                                        <i class="fa-solid fa-map-pin text-[9px]"></i> Map
                                    </a>
                                </div>
                                <template x-if="p.address">
                                    <p class="text-[11px] text-gray-500 dark:text-gray-400 truncate pl-4" x-text="p.address"></p>
                                </template>
                                <template x-if="p.timing">
                                    <div class="flex items-center gap-1.5 text-gray-500 dark:text-gray-400 pl-4 border-t border-gray-100/60 dark:border-gray-700/40 pt-1">
                                        <i class="fa-regular fa-clock text-[10px]"></i>
                                        <span class="truncate text-[11px]" x-text="p.timing"></span>
                                    </div>
                                </template>
                            </div>

                            <!-- Discount & NGO Benefit Badge -->
                            <template x-if="p.discount_offered">
                                <div class="mb-3 p-2 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-100 dark:border-emerald-800/40 text-emerald-800 dark:text-emerald-300 text-xs flex items-start gap-2">
                                    <i class="fa-solid fa-tag text-emerald-600 dark:text-emerald-400 mt-0.5 flex-shrink-0"></i>
                                    <span class="line-clamp-2" x-text="p.discount_offered"></span>
                                </div>
                            </template>

                            <!-- Quick Contact Actions -->
                            <div class="flex items-center gap-2 pt-1 text-xs">
                                <a :href="'tel:' + p.contact" class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-teal-50 hover:text-teal-700 dark:hover:bg-teal-900/30 transition-colors">
                                    <i class="fa-solid fa-phone text-teal-600"></i>
                                    <span x-text="p.contact"></span>
                                </a>
                                <template x-if="p.alternate_contact || p.contact">
                                    <a :href="'https://wa.me/' + cleanPhone(p.alternate_contact || p.contact)" target="_blank" class="px-2.5 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 hover:bg-emerald-100 transition-colors" title="Chat on WhatsApp">
                                        <i class="fa-brands fa-whatsapp text-sm"></i>
                                    </a>
                                </template>
                            </div>
                        </div>

                        <!-- Card Footer Action Buttons -->
                        <div class="px-5 py-3 bg-gray-50/80 dark:bg-gray-800/80 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between gap-2">
                            <button @click="openViewModal(p)" class="text-xs font-semibold text-teal-600 dark:text-teal-400 hover:underline flex items-center gap-1">
                                <i class="fa-solid fa-eye"></i>
                                <span>Details</span>
                            </button>

                            <div class="flex items-center gap-1.5">
                                <button @click="openEditModal(p)" class="p-1.5 text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors" title="Edit Provider">
                                    <i class="fa-solid fa-pen-to-square text-xs"></i>
                                </button>
                                <button @click="confirmDelete(p)" class="p-1.5 text-gray-500 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-lg transition-colors" title="Delete Provider">
                                    <i class="fa-solid fa-trash-can text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- 2. TABLE / LIST VIEW -->
            <div x-show="viewMode === 'table'" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                        <thead class="bg-gray-50 dark:bg-gray-750 text-[11px] uppercase tracking-wider text-gray-500 dark:text-gray-400 font-bold border-b border-gray-100 dark:border-gray-700">
                            <tr>
                                <th class="p-4">Facility / Provider</th>
                                <th class="p-4">Type & Speciality</th>
                                <th class="p-4">Contact Person</th>
                                <th class="p-4">Location</th>
                                <th class="p-4">NGO Discount</th>
                                <th class="p-4">Status</th>
                                <th class="p-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <template x-for="p in paginatedItems" :key="'table-' + p.id">
                                <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-700/40 transition-colors">
                                    <td class="p-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-xl bg-gray-100 dark:bg-gray-700 flex-shrink-0 flex items-center justify-center overflow-hidden">
                                                <template x-if="p.photo">
                                                    <img :src="'../' + p.photo" :alt="p.name" class="w-full h-full object-cover">
                                                </template>
                                                <template x-if="!p.photo">
                                                    <i class="fa-solid text-gray-400" :class="typeIcon(p.type)"></i>
                                                </template>
                                            </div>
                                            <div>
                                                <div class="flex items-center gap-1.5">
                                                    <p class="font-bold text-gray-900 dark:text-white" x-text="p.name"></p>
                                                    <template x-if="parseInt(p.is_verified) === 1">
                                                        <i class="fa-solid fa-circle-check text-teal-500 text-[11px]" title="Verified"></i>
                                                    </template>
                                                </div>
                                                <p class="font-mono text-[10px] text-teal-600 dark:text-teal-400" x-text="p.provider_code"></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <div class="flex flex-col gap-1">
                                            <span class="font-semibold text-gray-800 dark:text-gray-200 capitalize" x-text="typeLabel(p.type)"></span>
                                            <span class="text-[10px] text-gray-500 dark:text-gray-400" x-text="specialityLabel(p)"></span>
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-white" x-text="p.contact_person || '-'"></p>
                                            <p class="text-gray-500 font-mono text-[11px]" x-text="p.contact"></p>
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <div class="flex items-start justify-between gap-2">
                                            <div>
                                                <p class="font-medium text-gray-800 dark:text-gray-200" x-text="p.district + ', ' + p.state"></p>
                                                <p class="text-gray-400 text-[10px] truncate max-w-[150px]" x-text="p.address"></p>
                                            </div>
                                            <a :href="getMapLink(p)" target="_blank" class="p-1 rounded bg-rose-50 text-rose-600 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-400 text-[10px] transition-colors" title="Google Map Pin">
                                                <i class="fa-solid fa-map-pin"></i>
                                            </a>
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <p class="text-emerald-700 dark:text-emerald-300 font-medium max-w-[180px] truncate" :title="p.discount_offered" x-text="p.discount_offered || 'Standard Rates'"></p>
                                    </td>
                                    <td class="p-4">
                                        <button @click="toggleStatus(p)" class="px-2.5 py-1 rounded-full text-[10px] font-bold cursor-pointer transition-transform active:scale-95" :class="statusClass(p.status)" x-text="p.status"></button>
                                    </td>
                                    <td class="p-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button @click="openViewModal(p)" class="p-1.5 text-gray-500 hover:text-teal-600 hover:bg-teal-50 dark:hover:bg-teal-900/30 rounded-lg transition-colors" title="View Details">
                                                <i class="fa-solid fa-eye"></i>
                                            </button>
                                            <button @click="openEditModal(p)" class="p-1.5 text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button @click="confirmDelete(p)" class="p-1.5 text-gray-500 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-lg transition-colors" title="Delete">
                                                <i class="fa-solid fa-trash-can"></i>
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
                    <i class="fa-solid fa-hospital-user"></i>
                </div>
                <h3 class="text-base font-bold text-gray-800 dark:text-white">No Healthcare Providers Found</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 max-w-sm mx-auto">No providers match your current search or filter criteria. Try resetting filters or adding a new provider.</p>
                <button @click="resetFilters()" class="mt-4 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-xs font-semibold rounded-xl hover:bg-gray-200 transition-colors">
                    Reset All Filters
                </button>
            </div>

            <!-- Pagination Bar -->
            <div x-show="filteredItems.length > pageSize" class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-6 text-xs text-gray-500 dark:text-gray-400">
                <p>Showing <span class="font-bold text-gray-800 dark:text-gray-200" x-text="pageStartIndex + 1"></span> to <span class="font-bold text-gray-800 dark:text-gray-200" x-text="Math.min(pageStartIndex + pageSize, filteredItems.length)"></span> of <span class="font-bold text-gray-800 dark:text-gray-200" x-text="filteredItems.length"></span> providers</p>
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
            <!-- MODAL 1: ADD / EDIT HEALTHCARE PROVIDER             -->
            <!-- ==================================================== -->
            <div x-show="showFormModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4" x-transition.opacity>
                <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-3xl w-full shadow-2xl border border-gray-100 dark:border-gray-700 overflow-hidden flex flex-col max-h-[90vh]" @click.outside="showFormModal = false">
                    <!-- Modal Header -->
                    <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-gray-50/50 dark:bg-gray-750">
                        <div class="flex items-center gap-2.5">
                            <span class="w-9 h-9 rounded-xl bg-teal-500/10 text-teal-600 dark:text-teal-400 flex items-center justify-center text-base">
                                <i :class="isEditMode ? 'fa-solid fa-pen-to-square' : 'fa-solid fa-plus'"></i>
                            </span>
                            <div>
                                <h3 class="font-bold text-gray-900 dark:text-white text-base" x-text="isEditMode ? 'Edit Healthcare Provider' : 'Add New Healthcare Provider'"></h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Fill in hospital, clinic, lab or doctor particulars & discount terms.</p>
                            </div>
                        </div>
                        <button @click="showFormModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-1.5 rounded-lg">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>

                    <!-- Modal Body Form -->
                    <form action="actions/healthcare_provider_logic.php" method="POST" enctype="multipart/form-data" class="overflow-y-auto p-5 space-y-5">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="action" :value="isEditMode ? 'update_provider' : 'create_provider'">
                        <input type="hidden" name="id" :value="formData.id">

                        <!-- Section 1: Basic Information -->
                        <div>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-teal-600 dark:text-teal-400 mb-3 flex items-center gap-1.5">
                                <i class="fa-solid fa-circle-info"></i> 1. Basic Information
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Provider / Facility Name <span class="text-red-500">*</span></label>
                                    <input type="text" name="name" x-model="formData.name" required placeholder="e.g., Drishti Eye Hospital / Dr. Sharma Clinic" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Provider Type <span class="text-red-500">*</span></label>
                                    <select name="type" x-model="formData.type" required class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                                        <option value="hospital">Hospital</option>
                                        <option value="clinic">Clinic</option>
                                        <option value="pathology_lab">Pathology Lab</option>
                                        <option value="pharmacy">Pharmacy</option>
                                        <option value="doctor">Doctor</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Speciality <span class="text-red-500">*</span></label>
                                    <select name="speciality" x-model="formData.speciality" required class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                                        <option value="eye">Eye Care</option>
                                        <option value="dental">Dental Care</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>

                                <div class="sm:col-span-2" x-show="formData.speciality === 'other'">
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Specific Speciality / Department Name</label>
                                    <input type="text" name="speciality_custom" x-model="formData.speciality_custom" placeholder="e.g. Cardiology, Orthopedic, General Medicine, Diagnostic Lab" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Doctor / Contact Person Name</label>
                                    <input type="text" name="contact_person" x-model="formData.contact_person" placeholder="Dr. / Mr. / Ms." class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Primary Phone / Mobile <span class="text-red-500">*</span></label>
                                    <input type="text" name="contact" x-model="formData.contact" required placeholder="+91 XXXXX XXXXX" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                                </div>
                            </div>
                        </div>

                        <!-- Section 2: Contact & Location -->
                        <div class="pt-3 border-t border-gray-100 dark:border-gray-700">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-teal-600 dark:text-teal-400 mb-3 flex items-center gap-1.5">
                                <i class="fa-solid fa-map-location-dot"></i> 2. Location & Additional Contact
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">State <span class="text-red-500">*</span></label>
                                    <select name="state" x-model="formData.state" @change="onModalStateChange()" required class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                                        <option value="">Select State</option>
                                        <?php foreach ($indiaStatesList as $st): ?>
                                            <option value="<?php echo htmlspecialchars($st); ?>"><?php echo htmlspecialchars($st); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">District <span class="text-red-500">*</span></label>
                                    <select name="district" x-model="formData.district" required class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                                        <option value="">Select District</option>
                                        <template x-for="d in formDistrictOptions" :key="d">
                                            <option :value="d" :selected="d === formData.district" x-text="d"></option>
                                        </template>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Block / Tehsil / Area</label>
                                    <input type="text" name="block" x-model="formData.block" placeholder="e.g. Hazratganj / Andheri East" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                                </div>

                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Complete Address <span class="text-red-500">*</span></label>
                                    <input type="text" name="address" x-model="formData.address" required placeholder="Plot, Street, Building, Area" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">PIN Code</label>
                                    <input type="text" name="pincode" x-model="formData.pincode" placeholder="6-digit PIN" maxlength="6" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Alternate / WhatsApp Phone</label>
                                    <input type="text" name="alternate_contact" x-model="formData.alternate_contact" placeholder="+91 XXXXX XXXXX" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Email Address</label>
                                    <input type="email" name="email" x-model="formData.email" placeholder="contact@hospital.org" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Website URL</label>
                                    <input type="url" name="website" x-model="formData.website" placeholder="https://" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Landmark</label>
                                    <input type="text" name="landmark" x-model="formData.landmark" placeholder="Near metro station / opposite park" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                                </div>

                                <!-- Google Map Location & Geolocation Pin Fields -->
                                <div class="sm:col-span-3 bg-gradient-to-br from-teal-50/70 to-emerald-50/50 dark:from-teal-950/30 dark:to-emerald-950/20 p-4 rounded-2xl border border-teal-100 dark:border-teal-800/50 space-y-3">
                                    <div class="flex items-center justify-between">
                                        <label class="text-xs font-bold text-teal-900 dark:text-teal-300 flex items-center gap-1.5">
                                            <i class="fa-solid fa-map-location-dot text-rose-500 text-sm"></i> Google Map Location & GPS Pin (Directions / Embed)
                                        </label>
                                        <span class="text-[10px] text-teal-700 dark:text-teal-400 font-medium bg-white dark:bg-gray-800 px-2 py-0.5 rounded-md border border-teal-100 dark:border-teal-800">Supports Share Link, Lat/Long coords, or Iframe</span>
                                    </div>

                                    <div>
                                        <label class="block text-[11px] font-semibold text-gray-700 dark:text-gray-300 mb-1">Google Maps URL / Share Link / Embed Code / Coordinates</label>
                                        <input type="text" name="map_location" x-model="formData.map_location" placeholder="e.g. https://maps.app.goo.gl/... OR 28.628929, 77.206532 OR <iframe src=...></iframe>" class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500 font-mono">
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-[11px] font-semibold text-gray-700 dark:text-gray-300 mb-1">Latitude (Optional / Auto-extracted)</label>
                                            <input type="text" name="latitude" x-model="formData.latitude" placeholder="e.g. 28.628929" class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500 font-mono">
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-semibold text-gray-700 dark:text-gray-300 mb-1">Longitude (Optional / Auto-extracted)</label>
                                            <input type="text" name="longitude" x-model="formData.longitude" placeholder="e.g. 77.206532" class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500 font-mono">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 3: Operations & NGO Discount -->
                        <div class="pt-3 border-t border-gray-100 dark:border-gray-700">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-teal-600 dark:text-teal-400 mb-3 flex items-center gap-1.5">
                                <i class="fa-solid fa-handshake-angle"></i> 3. NGO Benefits & Timings
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">OPD / Operating Timing</label>
                                    <input type="text" name="timing" x-model="formData.timing" placeholder="e.g. Mon - Sat: 09:00 AM - 08:00 PM" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">NGO Discount / Concession Offered</label>
                                    <input type="text" name="discount_offered" x-model="formData.discount_offered" placeholder="e.g. 100% Free Cataract Surgery / 30% on Tests" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                                </div>

                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Services & Facilities Offered</label>
                                    <textarea name="services_offered" x-model="formData.services_offered" rows="2" placeholder="Free OPD, Digital X-Ray, Blood Testing, Eye Screening camps, etc." class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500"></textarea>
                                </div>

                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Remarks / MoU Notes</label>
                                    <textarea name="remarks" x-model="formData.remarks" rows="2" placeholder="Internal remarks, coordinator contact, agreement terms..." class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Section 4: Media & Status -->
                        <div class="pt-3 border-t border-gray-100 dark:border-gray-700">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-teal-600 dark:text-teal-400 mb-3 flex items-center gap-1.5">
                                <i class="fa-solid fa-sliders"></i> 4. Media & Settings
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-center">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Facility / Doctor Photo</label>
                                    <input type="file" name="photo" accept="image/*" class="w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-teal-50 file:text-teal-700 dark:file:bg-teal-900/40 dark:file:text-teal-300 hover:file:bg-teal-100">
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Status</label>
                                    <select name="status" x-model="formData.status" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="pending_approval">Pending Approval</option>
                                    </select>
                                </div>

                                <div class="space-y-2 pt-4">
                                    <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                                        <input type="checkbox" name="emergency_available" value="1" :checked="parseInt(formData.emergency_available) === 1" class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                                        <span class="text-xs text-gray-700 dark:text-gray-300 font-semibold">24x7 Emergency Facility</span>
                                    </label>

                                    <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                                        <input type="checkbox" name="is_verified" value="1" :checked="parseInt(formData.is_verified) === 1" class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                                        <span class="text-xs text-gray-700 dark:text-gray-300 font-semibold">Verified Partner Badge</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Actions Footer -->
                        <div class="pt-4 border-t border-gray-100 dark:border-gray-700 flex items-center justify-end gap-3">
                            <button type="button" @click="showFormModal = false" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-xs font-semibold rounded-xl hover:bg-gray-200 transition-colors">
                                Cancel
                            </button>
                            <button type="submit" class="px-5 py-2 bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold rounded-xl shadow transition-all duration-150 flex items-center gap-2">
                                <i class="fa-solid fa-floppy-disk"></i>
                                <span x-text="isEditMode ? 'Update Provider' : 'Save Provider'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ==================================================== -->
            <!-- MODAL 2: VIEW HEALTHCARE PROVIDER DETAILS           -->
            <!-- ==================================================== -->
            <div x-show="showViewModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4" x-transition.opacity>
                <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-2xl w-full shadow-2xl border border-gray-100 dark:border-gray-700 overflow-hidden flex flex-col max-h-[90vh]" @click.outside="showViewModal = false">
                    <!-- View Header -->
                    <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-gray-50/50 dark:bg-gray-750">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl bg-teal-50 dark:bg-teal-900/30 flex items-center justify-center text-teal-600 text-xl font-bold overflow-hidden">
                                <template x-if="viewItem.photo">
                                    <img :src="'../' + viewItem.photo" :alt="viewItem.name" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!viewItem.photo">
                                    <i :class="typeIcon(viewItem.type)"></i>
                                </template>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900 dark:text-white text-base" x-text="viewItem.name"></h3>
                                <p class="text-xs font-mono text-teal-600 dark:text-teal-400" x-text="viewItem.provider_code"></p>
                            </div>
                        </div>
                        <button @click="showViewModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-1.5 rounded-lg">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>

                    <!-- View Body -->
                    <div class="p-5 overflow-y-auto space-y-4 text-xs">
                        <!-- Badges Strip -->
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="px-2.5 py-1 rounded-lg font-bold uppercase text-[11px]" :class="typeBadgeClass(viewItem.type)" x-text="typeLabel(viewItem.type)"></span>
                            <span class="px-2.5 py-1 rounded-lg font-semibold bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200" x-text="specialityLabel(viewItem)"></span>
                            <span class="px-2.5 py-1 rounded-full font-bold" :class="statusClass(viewItem.status)" x-text="viewItem.status"></span>
                            <template x-if="parseInt(viewItem.emergency_available) === 1">
                                <span class="px-2.5 py-1 rounded-lg font-bold bg-rose-50 text-rose-600 dark:bg-rose-900/30 dark:text-rose-400 border border-rose-100 flex items-center gap-1">
                                    <i class="fa-solid fa-truck-medical"></i> 24x7 Emergency
                                </span>
                            </template>
                        </div>

                        <!-- Contact Details Card -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 bg-gray-50 dark:bg-gray-750 p-4 rounded-xl border border-gray-100 dark:border-gray-700">
                            <div>
                                <p class="text-gray-400 uppercase font-semibold text-[10px]">Contact Person</p>
                                <p class="font-bold text-gray-900 dark:text-white mt-0.5" x-text="viewItem.contact_person || 'Facility Incharge'"></p>
                            </div>
                            <div>
                                <p class="text-gray-400 uppercase font-semibold text-[10px]">Primary Phone</p>
                                <p class="font-bold text-teal-600 dark:text-teal-400 mt-0.5 font-mono" x-text="viewItem.contact"></p>
                            </div>
                            <template x-if="viewItem.alternate_contact">
                                <div>
                                    <p class="text-gray-400 uppercase font-semibold text-[10px]">Alternate / WhatsApp</p>
                                    <p class="font-semibold text-gray-800 dark:text-gray-200 mt-0.5 font-mono" x-text="viewItem.alternate_contact"></p>
                                </div>
                            </template>
                            <template x-if="viewItem.email">
                                <div>
                                    <p class="text-gray-400 uppercase font-semibold text-[10px]">Email Address</p>
                                    <p class="font-semibold text-gray-800 dark:text-gray-200 mt-0.5 truncate" x-text="viewItem.email"></p>
                                </div>
                            </template>
                        </div>

                        <!-- Location, Address & Interactive Map Embed -->
                        <div class="space-y-3 bg-gray-50 dark:bg-gray-750 p-4 rounded-xl border border-gray-100 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <p class="text-gray-400 uppercase font-semibold text-[10px] flex items-center gap-1.5">
                                    <i class="fa-solid fa-map-pin text-rose-500"></i> Address & Geolocation Pin
                                </p>
                                <template x-if="viewItem.latitude && viewItem.longitude">
                                    <span class="px-2 py-0.5 rounded-md bg-teal-50 dark:bg-teal-900/40 text-teal-700 dark:text-teal-300 text-[10px] font-mono font-bold" x-text="'GPS: ' + Number(viewItem.latitude).toFixed(4) + ', ' + Number(viewItem.longitude).toFixed(4)"></span>
                                </template>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white" x-text="viewItem.address"></p>
                                <p class="text-gray-600 dark:text-gray-300" x-text="(viewItem.block ? viewItem.block + ', ' : '') + viewItem.district + ', ' + viewItem.state + (viewItem.pincode ? ' - ' + viewItem.pincode : '')"></p>
                                <template x-if="viewItem.landmark">
                                    <p class="text-gray-500 italic mt-0.5" x-text="'Landmark: ' + viewItem.landmark"></p>
                                </template>
                            </div>

                            <!-- Interactive Google Map Embed -->
                            <div class="w-full h-48 rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-200 dark:bg-gray-800 shadow-inner relative">
                                <iframe :src="getEmbedUrl(viewItem)" class="w-full h-full border-0" loading="lazy" allowfullscreen referrerpolicy="no-referrer-when-downgrade"></iframe>
                            </div>

                            <div class="pt-1 flex flex-wrap items-center justify-between gap-2">
                                <a :href="getMapLink(viewItem)" target="_blank" class="inline-flex items-center gap-1.5 text-xs font-bold text-teal-600 dark:text-teal-400 hover:underline">
                                    <i class="fa-solid fa-diamond-turn-right text-rose-500"></i>
                                    <span>Open Navigation on Google Maps</span>
                                </a>
                                <template x-if="viewItem.map_location">
                                    <button type="button" @click="navigator.clipboard.writeText(viewItem.map_location); alert('Google Map Link copied!')" class="text-[11px] text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 flex items-center gap-1">
                                        <i class="fa-regular fa-copy"></i> Copy Link
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- NGO Concession & Discount -->
                        <template x-if="viewItem.discount_offered">
                            <div class="p-3.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-100 dark:border-emerald-800/50 text-emerald-900 dark:text-emerald-200">
                                <p class="font-bold flex items-center gap-1.5 text-[11px] uppercase tracking-wider text-emerald-700 dark:text-emerald-400">
                                    <i class="fa-solid fa-tag"></i> NGO Discount / Tie-up Benefits
                                </p>
                                <p class="mt-1 font-medium" x-text="viewItem.discount_offered"></p>
                            </div>
                        </template>

                        <!-- Services List -->
                        <template x-if="viewItem.services_offered">
                            <div>
                                <p class="text-gray-400 uppercase font-semibold text-[10px] mb-1">Services & Specialities Offered</p>
                                <div class="bg-gray-50 dark:bg-gray-750 p-3.5 rounded-xl border border-gray-100 dark:border-gray-700 text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-line" x-text="viewItem.services_offered"></div>
                            </div>
                        </template>

                        <!-- Operational Timing -->
                        <template x-if="viewItem.timing">
                            <div>
                                <p class="text-gray-400 uppercase font-semibold text-[10px] mb-1">OPD / Operating Timing</p>
                                <p class="font-semibold text-gray-800 dark:text-gray-200" x-text="viewItem.timing"></p>
                            </div>
                        </template>
                    </div>

                    <!-- View Footer -->
                    <div class="p-4 bg-gray-50 dark:bg-gray-750 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
                        <button @click="showViewModal = false; openEditModal(viewItem)" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl text-xs transition-colors flex items-center gap-1.5">
                            <i class="fa-solid fa-pen-to-square"></i>
                            <span>Edit Provider</span>
                        </button>
                        <button @click="showViewModal = false" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 font-semibold rounded-xl text-xs hover:bg-gray-300 transition-colors">
                            Close
                        </button>
                    </div>
                </div>
            </div>

            <!-- ==================================================== -->
            <!-- MODAL 3: DELETE CONFIRMATION                         -->
            <!-- ==================================================== -->
            <div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4" x-transition.opacity>
                <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-md w-full shadow-2xl border border-gray-100 dark:border-gray-700 p-6 text-center" @click.outside="showDeleteModal = false">
                    <div class="w-14 h-14 bg-rose-50 dark:bg-rose-900/20 text-rose-600 rounded-2xl flex items-center justify-center mx-auto mb-4 text-2xl">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <h3 class="font-bold text-gray-900 dark:text-white text-lg">Delete Healthcare Provider?</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                        Are you sure you want to permanently delete <strong class="text-gray-800 dark:text-gray-200" x-text="deleteItem.name"></strong> (<span class="font-mono text-teal-600" x-text="deleteItem.provider_code"></span>)? This action cannot be undone.
                    </p>

                    <form action="actions/healthcare_provider_logic.php" method="POST" class="mt-6 flex items-center justify-center gap-3">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="action" value="delete_provider">
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
    Alpine.data('healthcareManager', () => ({
        items: <?php echo json_encode($providers, JSON_UNESCAPED_UNICODE); ?>,
        indiaLocations: <?php echo $indiaStatesJson; ?>,
        search: '',
        filterType: '',
        filterSpeciality: '',
        filterState: '',
        filterDistrict: '',
        filterStatus: '',
        filterEmergencyOnly: false,
        viewMode: 'grid', // 'grid' or 'table'
        currentPage: 1,
        pageSize: 9,

        // Modal States
        showFormModal: false,
        isEditMode: false,
        formData: {
            id: '',
            name: '',
            type: 'hospital',
            speciality: 'other',
            speciality_custom: '',
            contact_person: '',
            contact: '',
            alternate_contact: '',
            email: '',
            website: '',
            state: '',
            district: '',
            block: '',
            pincode: '',
            address: '',
            landmark: '',
            timing: '',
            emergency_available: 0,
            discount_offered: '',
            services_offered: '',
            remarks: '',
            status: 'active',
            is_verified: 1
        },
        formDistrictOptions: [],

        showViewModal: false,
        viewItem: {},

        showDeleteModal: false,
        deleteItem: {},

        get districtOptions() {
            if (!this.filterState || !this.indiaLocations[this.filterState]) return [];
            return this.indiaLocations[this.filterState];
        },

        get hasActiveFilters() {
            return this.search || this.filterType || this.filterSpeciality || this.filterState || this.filterDistrict || this.filterStatus || this.filterEmergencyOnly;
        },

        resetFilters() {
            this.search = '';
            this.filterType = '';
            this.filterSpeciality = '';
            this.filterState = '';
            this.filterDistrict = '';
            this.filterStatus = '';
            this.filterEmergencyOnly = false;
            this.currentPage = 1;
        },

        get filteredItems() {
            return this.items.filter(p => {
                // Search Keyword
                if (this.search) {
                    const q = this.search.toLowerCase();
                    const match = (p.name && p.name.toLowerCase().includes(q)) ||
                                  (p.provider_code && p.provider_code.toLowerCase().includes(q)) ||
                                  (p.contact && p.contact.includes(q)) ||
                                  (p.contact_person && p.contact_person.toLowerCase().includes(q)) ||
                                  (p.district && p.district.toLowerCase().includes(q)) ||
                                  (p.services_offered && p.services_offered.toLowerCase().includes(q));
                    if (!match) return false;
                }

                // Type
                if (this.filterType && p.type !== this.filterType) return false;

                // Speciality
                if (this.filterSpeciality && p.speciality !== this.filterSpeciality) return false;

                // State
                if (this.filterState && p.state !== this.filterState) return false;

                // District
                if (this.filterDistrict && p.district !== this.filterDistrict) return false;

                // Status
                if (this.filterStatus && p.status !== this.filterStatus) return false;

                // Emergency
                if (this.filterEmergencyOnly && parseInt(p.emergency_available) !== 1) return false;

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

        // Modal Handlers
        openAddModal() {
            this.isEditMode = false;
            this.formData = {
                id: '',
                name: '',
                type: 'hospital',
                speciality: 'other',
                speciality_custom: '',
                contact_person: '',
                contact: '',
                alternate_contact: '',
                email: '',
                website: '',
                state: '',
                district: '',
                block: '',
                pincode: '',
                address: '',
                landmark: '',
                map_location: '',
                latitude: '',
                longitude: '',
                map_embed_url: '',
                timing: '',
                emergency_available: 0,
                discount_offered: '',
                services_offered: '',
                remarks: '',
                status: 'active',
                is_verified: 1
            };
            this.formDistrictOptions = [];
            this.showFormModal = true;
        },

        openEditModal(p) {
            this.isEditMode = true;
            this.formData = { ...p };
            this.onModalStateChange(p.district);
            this.showFormModal = true;
        },

        onModalStateChange(defaultDistrict = '') {
            const st = this.formData.state;
            if (st && this.indiaLocations[st]) {
                this.formDistrictOptions = this.indiaLocations[st];
                if (defaultDistrict) {
                    this.formData.district = defaultDistrict;
                } else if (!this.formDistrictOptions.includes(this.formData.district)) {
                    this.formData.district = this.formDistrictOptions[0] || '';
                }
            } else {
                this.formDistrictOptions = [];
                this.formData.district = '';
            }
        },

        openViewModal(p) {
            this.viewItem = p;
            this.showViewModal = true;
        },

        confirmDelete(p) {
            this.deleteItem = p;
            this.showDeleteModal = true;
        },

        // Quick Toggles
        async toggleStatus(p) {
            const formData = new FormData();
            formData.append('csrf_token', '<?php echo $csrfToken; ?>');
            formData.append('action', 'toggle_status');
            formData.append('id', p.id);
            formData.append('is_ajax', '1');

            try {
                const res = await fetch('actions/healthcare_provider_logic.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    p.status = data.new_status;
                } else {
                    alert(data.message || 'Failed to update status');
                }
            } catch (err) {
                console.error(err);
            }
        },

        async toggleVerified(p) {
            const formData = new FormData();
            formData.append('csrf_token', '<?php echo $csrfToken; ?>');
            formData.append('action', 'toggle_verified');
            formData.append('id', p.id);
            formData.append('is_ajax', '1');

            try {
                const res = await fetch('actions/healthcare_provider_logic.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    p.is_verified = data.is_verified;
                } else {
                    alert(data.message || 'Failed to update verification');
                }
            } catch (err) {
                console.error(err);
            }
        },

        // Formatters & UI Helpers
        typeLabel(type) {
            const map = {
                'hospital': 'Hospital',
                'clinic': 'Clinic',
                'pathology_lab': 'Pathology Lab',
                'pharmacy': 'Pharmacy',
                'doctor': 'Doctor'
            };
            return map[type] || type;
        },

        specialityLabel(p) {
            if (p.speciality === 'eye') return 'Eye Care';
            if (p.speciality === 'dental') return 'Dental Care';
            return p.speciality_custom || 'General / Multi';
        },

        typeIcon(type) {
            const map = {
                'hospital': 'fa-hospital',
                'clinic': 'fa-house-medical',
                'pathology_lab': 'fa-flask-vial',
                'pharmacy': 'fa-pills',
                'doctor': 'fa-user-doctor'
            };
            return map[type] || 'fa-stethoscope';
        },

        typeBadgeClass(type) {
            const map = {
                'hospital': 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
                'clinic': 'bg-teal-100 text-teal-800 dark:bg-teal-900/40 dark:text-teal-300',
                'pathology_lab': 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300',
                'pharmacy': 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
                'doctor': 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300'
            };
            return map[type] || 'bg-gray-100 text-gray-800';
        },

        statusClass(status) {
            if (status === 'active') return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300';
            if (status === 'inactive') return 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
            return 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300';
        },

        cleanPhone(phone) {
            if (!phone) return '';
            return phone.replace(/[^0-9]/g, '');
        },

        getMapLink(p) {
            if (p?.map_details?.view_url) return p.map_details.view_url;
            if (p?.map_location && (p.map_location.startsWith('http://') || p.map_location.startsWith('https://'))) {
                return p.map_location;
            }
            if (p?.latitude && p?.longitude) {
                return `https://maps.google.com/?q=${p.latitude},${p.longitude}`;
            }
            const query = [p?.name, p?.address, p?.district, p?.state].filter(Boolean).join(', ');
            return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(query)}`;
        },

        getEmbedUrl(p) {
            if (p?.map_details?.embed_url) return p.map_details.embed_url;
            if (p?.map_embed_url && (p.map_embed_url.startsWith('http://') || p.map_embed_url.startsWith('https://'))) {
                return p.map_embed_url;
            }
            if (p?.latitude && p?.longitude) {
                return `https://maps.google.com/maps?q=${p.latitude},${p.longitude}&hl=en&z=15&output=embed`;
            }
            const query = [p?.name, p?.address, p?.district, p?.state].filter(Boolean).join(', ');
            return `https://maps.google.com/maps?q=${encodeURIComponent(query)}&hl=en&z=15&output=embed`;
        },

        hasMapPin(p) {
            return Boolean(p?.latitude || p?.longitude || p?.map_location || p?.map_embed_url);
        },

        exportCSV() {
            let csv = "Provider Code,Name,Type,Speciality,Contact Person,Phone,Email,State,District,Address,NGO Discount,Status\n";
            this.filteredItems.forEach(p => {
                const row = [
                    `"${p.provider_code}"`,
                    `"${(p.name || '').replace(/"/g, '""')}"`,
                    `"${p.type}"`,
                    `"${this.specialityLabel(p)}"`,
                    `"${(p.contact_person || '').replace(/"/g, '""')}"`,
                    `"${p.contact}"`,
                    `"${p.email || ''}"`,
                    `"${p.state}"`,
                    `"${p.district}"`,
                    `"${(p.address || '').replace(/"/g, '""')}"`,
                    `"${(p.discount_offered || '').replace(/"/g, '""')}"`,
                    `"${p.status}"`
                ];
                csv += row.join(",") + "\n";
            });

            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `healthcare_providers_${new Date().toISOString().slice(0, 10)}.csv`;
            a.click();
            URL.revokeObjectURL(url);
        }
    }));
});
</script>

<?php require 'includes/footer.php'; ?>
