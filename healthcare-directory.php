<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/india_locations.php';
require_once 'includes/healthcare_map_helper.php';

// Fetch active healthcare providers
$providers = [];
if (dbTableExists($pdo, 'healthcare_providers')) {
    $stmt = $pdo->query("
        SELECT * 
        FROM healthcare_providers 
        WHERE status = 'active' 
        ORDER BY is_verified DESC, id DESC
    ");
    $providers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($providers as &$p) {
        $p['map_details'] = getProviderMapDetails($p);
    }
    unset($p);
}

// Compute counts for filter pills
$totalCount = count($providers);
$hospitalCount = 0;
$clinicCount = 0;
$eyeCount = 0;
$dentalCount = 0;
$labCount = 0;
$pharmacyCount = 0;
$doctorCount = 0;

foreach ($providers as $p) {
    if ($p['type'] === 'hospital') $hospitalCount++;
    if ($p['type'] === 'clinic') $clinicCount++;
    if ($p['speciality'] === 'eye') $eyeCount++;
    if ($p['speciality'] === 'dental') $dentalCount++;
    if ($p['type'] === 'pathology_lab') $labCount++;
    if ($p['type'] === 'pharmacy') $pharmacyCount++;
    if ($p['type'] === 'doctor') $doctorCount++;
}

$indiaStatesJson = india_state_district_js();
$indiaStatesList = india_state_list();

// Unique states & districts present in data
$dataStates = [];
$dataDistricts = [];
$dataBlocks = [];
foreach ($providers as $p) {
    if (!empty($p['state']) && !in_array($p['state'], $dataStates, true)) $dataStates[] = $p['state'];
    if (!empty($p['district']) && !in_array($p['district'], $dataDistricts, true)) $dataDistricts[] = $p['district'];
    if (!empty($p['block']) && !in_array($p['block'], $dataBlocks, true)) $dataBlocks[] = $p['block'];
}
sort($dataStates);
sort($dataDistricts);
sort($dataBlocks);

require 'includes/header.php';
?>

<div class="bg-gradient-to-b from-[#F0FDFD] via-white to-[#F8FAFC] min-h-screen py-8 md:py-14" 
     x-data="healthcareDirectoryPage(<?php echo htmlspecialchars(json_encode($providers, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>)" 
     x-cloak>
    
    <div class="container mx-auto px-4 max-w-7xl">
        
        <!-- Hero Header -->
        <div class="max-w-4xl mx-auto text-center mb-8 md:mb-12">
            <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white shadow-xs border border-teal-100 text-teal-700 text-xs sm:text-sm font-bold uppercase tracking-wider">
                <i class="fa-solid fa-heart-pulse text-rose-500 animate-pulse"></i>
                Community Health Mission • Verified Medical Network
            </span>
            <h1 class="mt-4 text-3xl sm:text-4xl md:text-5xl font-black text-gray-900 tracking-tight">
                Healthcare <span class="text-teal-600">Directory</span>
            </h1>
            <p class="mt-3 text-sm sm:text-base md:text-lg text-gray-600 max-w-2xl mx-auto leading-relaxed">
                Find empaneled hospitals, clinics, eye care & dental centers, pathology labs, pharmacies, and specialists providing quality care and NGO concessions for underprivileged families.
            </p>
        </div>

        <!-- Filter & Search Panel -->
        <div class="bg-white rounded-3xl shadow-xl border border-teal-50 p-5 md:p-7 mb-10 transition-all">
            
            <!-- 1. Quick Category / Type Filter Tabs -->
            <div class="mb-6">
                <div class="flex items-center justify-between mb-2.5">
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Filter by Healthcare Category:</span>
                    <span class="text-xs font-semibold text-teal-600" x-text="filteredItems.length + ' facilities found'"></span>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" @click="setTypeFilter('')" 
                            class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all duration-150 flex items-center gap-2 shadow-xs"
                            :class="activeFilter === '' ? 'bg-teal-600 text-white shadow-teal-200' : 'bg-gray-50 text-gray-600 hover:bg-gray-100 border border-gray-100'">
                        <i class="fa-solid fa-hospital-user"></i>
                        <span>All Providers</span>
                        <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="activeFilter === '' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-700'"><?php echo $totalCount; ?></span>
                    </button>

                    <button type="button" @click="setTypeFilter('hospital')" 
                            class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all duration-150 flex items-center gap-2 shadow-xs"
                            :class="activeFilter === 'hospital' ? 'bg-teal-600 text-white shadow-teal-200' : 'bg-gray-50 text-gray-600 hover:bg-gray-100 border border-gray-100'">
                        <i class="fa-solid fa-hospital"></i>
                        <span>Hospitals</span>
                        <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="activeFilter === 'hospital' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-700'"><?php echo $hospitalCount; ?></span>
                    </button>

                    <button type="button" @click="setTypeFilter('clinic')" 
                            class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all duration-150 flex items-center gap-2 shadow-xs"
                            :class="activeFilter === 'clinic' ? 'bg-teal-600 text-white shadow-teal-200' : 'bg-gray-50 text-gray-600 hover:bg-gray-100 border border-gray-100'">
                        <i class="fa-solid fa-house-medical"></i>
                        <span>Clinics</span>
                        <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="activeFilter === 'clinic' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-700'"><?php echo $clinicCount; ?></span>
                    </button>

                    <button type="button" @click="setTypeFilter('eye')" 
                            class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all duration-150 flex items-center gap-2 shadow-xs"
                            :class="activeFilter === 'eye' ? 'bg-teal-600 text-white shadow-teal-200' : 'bg-gray-50 text-gray-600 hover:bg-gray-100 border border-gray-100'">
                        <i class="fa-solid fa-eye"></i>
                        <span>Eye Care</span>
                        <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="activeFilter === 'eye' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-700'"><?php echo $eyeCount; ?></span>
                    </button>

                    <button type="button" @click="setTypeFilter('dental')" 
                            class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all duration-150 flex items-center gap-2 shadow-xs"
                            :class="activeFilter === 'dental' ? 'bg-teal-600 text-white shadow-teal-200' : 'bg-gray-50 text-gray-600 hover:bg-gray-100 border border-gray-100'">
                        <i class="fa-solid fa-tooth"></i>
                        <span>Dental Care</span>
                        <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="activeFilter === 'dental' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-700'"><?php echo $dentalCount; ?></span>
                    </button>

                    <button type="button" @click="setTypeFilter('pathology_lab')" 
                            class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all duration-150 flex items-center gap-2 shadow-xs"
                            :class="activeFilter === 'pathology_lab' ? 'bg-teal-600 text-white shadow-teal-200' : 'bg-gray-50 text-gray-600 hover:bg-gray-100 border border-gray-100'">
                        <i class="fa-solid fa-flask-vial"></i>
                        <span>Pathology Labs</span>
                        <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="activeFilter === 'pathology_lab' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-700'"><?php echo $labCount; ?></span>
                    </button>

                    <button type="button" @click="setTypeFilter('pharmacy')" 
                            class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all duration-150 flex items-center gap-2 shadow-xs"
                            :class="activeFilter === 'pharmacy' ? 'bg-teal-600 text-white shadow-teal-200' : 'bg-gray-50 text-gray-600 hover:bg-gray-100 border border-gray-100'">
                        <i class="fa-solid fa-pills"></i>
                        <span>Pharmacies</span>
                        <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="activeFilter === 'pharmacy' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-700'"><?php echo $pharmacyCount; ?></span>
                    </button>

                    <button type="button" @click="setTypeFilter('doctor')" 
                            class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all duration-150 flex items-center gap-2 shadow-xs"
                            :class="activeFilter === 'doctor' ? 'bg-teal-600 text-white shadow-teal-200' : 'bg-gray-50 text-gray-600 hover:bg-gray-100 border border-gray-100'">
                        <i class="fa-solid fa-user-doctor"></i>
                        <span>Doctors</span>
                        <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="activeFilter === 'doctor' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-700'"><?php echo $doctorCount; ?></span>
                    </button>
                </div>
            </div>

            <!-- 2. Location & Search Inputs Form -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 pt-4 border-t border-gray-100">
                <!-- Search Input -->
                <div class="relative">
                    <label class="block text-[11px] font-bold uppercase text-gray-500 mb-1">Search Keywords</label>
                    <div class="relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                        <input type="text" x-model="search" placeholder="Name, doctor, service, PIN..." class="w-full pl-9 pr-4 py-2.5 bg-gray-50/70 border border-gray-200 rounded-xl text-xs text-gray-900 placeholder-gray-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                    </div>
                </div>

                <!-- State Dropdown -->
                <div>
                    <label class="block text-[11px] font-bold uppercase text-gray-500 mb-1">State</label>
                    <select x-model="selectedState" @change="onStateChange()" class="w-full px-3 py-2.5 bg-gray-50/70 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                        <option value="">All States across India</option>
                        <?php foreach ($indiaStatesList as $st): ?>
                            <option value="<?php echo htmlspecialchars($st); ?>"><?php echo htmlspecialchars($st); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- District Dropdown -->
                <div>
                    <label class="block text-[11px] font-bold uppercase text-gray-500 mb-1">District</label>
                    <select x-model="selectedDistrict" class="w-full px-3 py-2.5 bg-gray-50/70 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                        <option value="">All Districts</option>
                        <template x-for="d in districtOptions" :key="d">
                            <option :value="d" x-text="d"></option>
                        </template>
                    </select>
                </div>

                <!-- Block / Tehsil Filter -->
                <div>
                    <label class="block text-[11px] font-bold uppercase text-gray-500 mb-1">Block / Tehsil / Area</label>
                    <input type="text" x-model="selectedBlock" placeholder="e.g. Hazratganj / Andheri" class="w-full px-3.5 py-2.5 bg-gray-50/70 border border-gray-200 rounded-xl text-xs text-gray-900 placeholder-gray-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                </div>
            </div>

            <!-- 3. Checkbox toggles & Reset -->
            <div class="flex flex-wrap items-center justify-between gap-3 pt-4 mt-4 border-t border-gray-100 text-xs">
                <div class="flex flex-wrap items-center gap-4">
                    <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" x-model="emergencyOnly" class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                        <span class="text-gray-700 font-semibold flex items-center gap-1">
                            <i class="fa-solid fa-truck-medical text-rose-500"></i> 24x7 Emergency Available
                        </span>
                    </label>

                    <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" x-model="discountOnly" class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                        <span class="text-gray-700 font-semibold flex items-center gap-1">
                            <i class="fa-solid fa-tag text-emerald-600"></i> Free / NGO Discount Offered
                        </span>
                    </label>
                </div>

                <button x-show="hasActiveFilters" @click="resetFilters()" class="text-red-500 hover:text-red-600 font-bold inline-flex items-center gap-1.5 transition-colors">
                    <i class="fa-solid fa-rotate-left"></i>
                    <span>Reset All Filters</span>
                </button>
            </div>
        </div>

        <!-- Healthcare Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <template x-for="p in paginatedItems" :key="'provider-' + p.id">
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-xl transition-all duration-200 flex flex-col justify-between overflow-hidden group">
                    <div class="p-6">
                        
                        <!-- Top Badges & Verification -->
                        <div class="flex items-start justify-between gap-2 mb-4">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold uppercase tracking-wider" :class="typeBadgeClass(p.type)" x-text="typeLabel(p.type)"></span>
                                <span class="px-2 py-0.5 rounded-lg text-[11px] font-semibold bg-gray-100 text-gray-700" x-text="specialityLabel(p)"></span>
                                <template x-if="parseInt(p.emergency_available) === 1">
                                    <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-rose-50 text-rose-600 border border-rose-100 flex items-center gap-1">
                                        <i class="fa-solid fa-truck-medical text-[9px]"></i> 24x7
                                    </span>
                                </template>
                            </div>

                            <template x-if="parseInt(p.is_verified) === 1">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-teal-50 text-teal-700 border border-teal-100 text-[10px] font-bold" title="Empaneled NGO Partner">
                                    <i class="fa-solid fa-circle-check text-teal-500"></i> Verified
                                </span>
                            </template>
                        </div>

                        <!-- Provider Title & Photo -->
                        <div class="flex items-start gap-4 mb-4">
                            <div class="w-14 h-14 rounded-2xl bg-teal-50 border border-teal-100/60 flex-shrink-0 overflow-hidden flex items-center justify-center shadow-xs">
                                <template x-if="p.photo">
                                    <img :src="p.photo" :alt="p.name" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!p.photo">
                                    <i class="fa-solid text-teal-600 text-xl" :class="typeIcon(p.type)"></i>
                                </template>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="font-bold text-gray-900 text-base leading-snug group-hover:text-teal-600 transition-colors line-clamp-2" x-text="p.name"></h3>
                                <template x-if="p.contact_person">
                                    <p class="text-xs text-gray-600 font-medium mt-1 flex items-center gap-1">
                                        <i class="fa-solid fa-user-doctor text-[10px] text-teal-600"></i>
                                        <span x-text="p.contact_person"></span>
                                    </p>
                                </template>
                            </div>
                        </div>

                        <!-- Location & Map Pin -->
                        <div class="bg-gray-50/80 p-3.5 rounded-2xl border border-gray-100 space-y-2 text-xs text-gray-600 mb-4">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-start gap-2 min-w-0">
                                    <i class="fa-solid fa-location-dot text-teal-600 mt-0.5 flex-shrink-0 text-sm"></i>
                                    <p class="leading-relaxed">
                                        <span class="font-bold text-gray-900" x-text="p.address"></span><br>
                                        <span class="text-gray-600 text-[11px]" x-text="(p.block ? p.block + ', ' : '') + p.district + ', ' + p.state + (p.pincode ? ' - ' + p.pincode : '')"></span>
                                    </p>
                                </div>

                                <!-- Glowing Map Pin Badge / Action Button -->
                                <a :href="getMapLink(p)" target="_blank" 
                                   class="px-2.5 py-1 rounded-xl bg-gradient-to-r from-rose-50 to-pink-50 hover:from-rose-100 hover:to-pink-100 text-rose-700 border border-rose-200/80 text-[10px] font-bold flex-shrink-0 flex items-center gap-1.5 shadow-xs transition-all hover:scale-105" 
                                   title="Open Google Maps Location Pin">
                                    <i class="fa-solid fa-map-pin text-rose-600 animate-pulse"></i>
                                    <span>Map Pin</span>
                                </a>
                            </div>

                            <template x-if="p.latitude && p.longitude">
                                <div class="flex items-center gap-1.5 text-[10px] text-teal-700 font-mono bg-teal-50/80 px-2 py-0.5 rounded-lg w-fit border border-teal-100/60">
                                    <i class="fa-solid fa-crosshairs text-teal-600 text-[9px]"></i>
                                    <span x-text="'GPS: ' + Number(p.latitude).toFixed(4) + ', ' + Number(p.longitude).toFixed(4)"></span>
                                </div>
                            </template>

                            <template x-if="p.timing">
                                <div class="flex items-center gap-2 pt-1 border-t border-gray-100 text-gray-500">
                                    <i class="fa-regular fa-clock text-teal-600 flex-shrink-0"></i>
                                    <span class="truncate font-medium text-[11px]" x-text="p.timing"></span>
                                </div>
                            </template>
                        </div>

                        <!-- NGO Discount Ribbon / Card -->
                        <template x-if="p.discount_offered">
                            <div class="mb-4 p-2.5 rounded-xl bg-gradient-to-r from-emerald-50 to-teal-50 border border-emerald-100 text-emerald-900 text-xs flex items-start gap-2 shadow-xs">
                                <i class="fa-solid fa-gift text-emerald-600 mt-0.5 flex-shrink-0 text-sm"></i>
                                <div>
                                    <p class="font-bold text-[10px] uppercase tracking-wider text-emerald-700">NGO Tie-up Benefit</p>
                                    <p class="font-semibold text-emerald-900 line-clamp-2 mt-0.5" x-text="p.discount_offered"></p>
                                </div>
                            </div>
                        </template>

                        <!-- Services Highlights -->
                        <template x-if="p.services_offered">
                            <p class="text-xs text-gray-500 line-clamp-2 mb-3" x-text="p.services_offered"></p>
                        </template>
                    </div>

                    <!-- Card Actions -->
                    <div class="p-4 bg-gray-50/80 border-t border-gray-100 flex items-center justify-between gap-2">
                        <!-- Left Action: Phone & WhatsApp -->
                        <div class="flex items-center gap-1.5">
                            <a :href="'tel:' + p.contact" class="px-3 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-bold transition-all shadow-xs flex items-center gap-1.5">
                                <i class="fa-solid fa-phone text-xs"></i>
                                <span>Call</span>
                            </a>

                            <template x-if="p.alternate_contact || p.contact">
                                <a :href="'https://wa.me/' + cleanPhone(p.alternate_contact || p.contact) + '?text=' + encodeURIComponent('Hello ' + p.name + ', I am contacting you through the NGO Healthcare Directory regarding medical assistance.')" target="_blank" class="px-2.5 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-xl text-xs font-bold transition-colors" title="Chat on WhatsApp">
                                    <i class="fa-brands fa-whatsapp text-sm"></i>
                                </a>
                            </template>
                        </div>

                        <!-- Right Action: Directions & Details -->
                        <div class="flex items-center gap-1.5">
                            <a :href="getMapLink(p)" target="_blank" class="p-2 text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-100 rounded-xl transition-all shadow-xs" title="Google Maps Navigation & Directions">
                                <i class="fa-solid fa-diamond-turn-right text-xs"></i>
                            </a>

                            <button type="button" @click="openModal(p)" class="px-3 py-2 bg-white hover:bg-gray-100 text-gray-700 border border-gray-200 rounded-xl text-xs font-semibold transition-colors flex items-center gap-1">
                                <span>Details</span>
                                <i class="fa-solid fa-chevron-right text-[9px]"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Empty State -->
        <div x-show="filteredItems.length === 0" class="p-12 text-center bg-white rounded-3xl border border-gray-100 shadow-sm mt-6">
            <div class="w-16 h-16 bg-teal-50 text-teal-600 rounded-2xl flex items-center justify-center mx-auto mb-3 text-2xl">
                <i class="fa-solid fa-hospital-user"></i>
            </div>
            <h3 class="text-base font-bold text-gray-900">No Healthcare Facilities Found</h3>
            <p class="text-xs text-gray-500 mt-1 max-w-sm mx-auto">No healthcare centers matched your chosen location or filters. Try choosing a different state or category.</p>
            <button @click="resetFilters()" class="mt-4 px-4 py-2 bg-teal-600 text-white text-xs font-bold rounded-xl hover:bg-teal-700 shadow-sm transition-colors">
                Reset All Filters
            </button>
        </div>

        <!-- Pagination -->
        <div x-show="filteredItems.length > pageSize" class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-8 text-xs text-gray-500">
            <p>Showing <span class="font-bold text-gray-800" x-text="pageStartIndex + 1"></span> to <span class="font-bold text-gray-800" x-text="Math.min(pageStartIndex + pageSize, filteredItems.length)"></span> of <span class="font-bold text-gray-800" x-text="filteredItems.length"></span> healthcare providers</p>
            <div class="flex items-center gap-1.5">
                <button @click="currentPage--" :disabled="currentPage === 1" class="px-3 py-1.5 bg-white border border-gray-200 rounded-xl disabled:opacity-40 hover:bg-gray-50 transition-colors">
                    <i class="fa-solid fa-chevron-left text-[10px]"></i>
                </button>
                <template x-for="p in totalPages" :key="p">
                    <button @click="currentPage = p" :class="currentPage === p ? 'bg-teal-600 text-white font-bold' : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-200'" class="w-8 h-8 rounded-xl transition-all" x-text="p"></button>
                </template>
                <button @click="currentPage++" :disabled="currentPage === totalPages" class="px-3 py-1.5 bg-white border border-gray-200 rounded-xl disabled:opacity-40 hover:bg-gray-50 transition-colors">
                    <i class="fa-solid fa-chevron-right text-[10px]"></i>
                </button>
            </div>
        </div>

        <!-- Help & Beneficiary Assistance Banner -->
        <div class="mt-14 p-6 md:p-8 rounded-3xl bg-gradient-to-r from-teal-800 to-emerald-900 text-white shadow-xl flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-white/10 flex items-center justify-center text-2xl flex-shrink-0 shadow-inner">
                    <i class="fa-solid fa-hand-holding-medical text-amber-300"></i>
                </div>
                <div>
                    <h3 class="text-lg md:text-xl font-black">Need Financial or Medical Emergency Support?</h3>
                    <p class="text-xs md:text-sm text-teal-100 mt-1 max-w-xl">
                        Our NGO coordinates free treatments, subsidized diagnostics, and surgery sponsorships for low-income and BPL card families.
                    </p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="<?php echo cleanUrl('contact.php'); ?>" class="px-5 py-2.5 bg-white text-teal-800 hover:bg-amber-300 hover:text-teal-900 rounded-xl text-xs font-bold transition-all shadow-md">
                    <i class="fa-solid fa-phone mr-1"></i> Contact Health Desk
                </a>
                <a href="<?php echo cleanUrl('complaints.php'); ?>" class="px-4 py-2.5 bg-teal-700 hover:bg-teal-600 text-white rounded-xl text-xs font-semibold border border-teal-500 transition-colors">
                    Submit Medical Request
                </a>
            </div>
        </div>
    </div>

    <!-- ==================================================== -->
    <!-- PROVIDER DETAILS MODAL                               -->
    <!-- ==================================================== -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-4" x-transition.opacity>
        <div class="bg-white rounded-3xl max-w-2xl w-full shadow-2xl border border-gray-100 overflow-hidden flex flex-col max-h-[90vh]" @click.outside="showModal = false">
            
            <!-- Modal Header -->
            <div class="p-6 border-b border-gray-100 flex items-start justify-between bg-gradient-to-r from-teal-50/50 to-white">
                <div class="flex items-center gap-3.5">
                    <div class="w-14 h-14 rounded-2xl bg-teal-50 border border-teal-100 flex items-center justify-center text-teal-600 text-2xl overflow-hidden shadow-xs">
                        <template x-if="selectedProvider.photo">
                            <img :src="selectedProvider.photo" :alt="selectedProvider.name" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!selectedProvider.photo">
                            <i :class="typeIcon(selectedProvider.type)"></i>
                        </template>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 text-lg leading-snug" x-text="selectedProvider.name"></h3>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold uppercase" :class="typeBadgeClass(selectedProvider.type)" x-text="typeLabel(selectedProvider.type)"></span>
                            <span class="text-xs font-semibold text-gray-500" x-text="specialityLabel(selectedProvider)"></span>
                        </div>
                    </div>
                </div>

                <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 p-2 rounded-xl">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <!-- Modal Content -->
            <div class="p-6 overflow-y-auto space-y-4 text-xs">
                
                <!-- NGO Tie-up Discount Banner -->
                <template x-if="selectedProvider.discount_offered">
                    <div class="p-4 rounded-2xl bg-gradient-to-r from-emerald-50 to-teal-50 border border-emerald-100 text-emerald-900">
                        <p class="font-bold flex items-center gap-1.5 text-xs uppercase tracking-wider text-emerald-700">
                            <i class="fa-solid fa-gift text-sm"></i> NGO Tie-up & Beneficiary Concession
                        </p>
                        <p class="mt-1 font-semibold text-emerald-900 text-sm" x-text="selectedProvider.discount_offered"></p>
                    </div>
                </template>

                <!-- Contact & Timings Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 bg-gray-50 p-4 rounded-2xl border border-gray-100">
                    <div>
                        <p class="text-gray-400 uppercase font-semibold text-[10px]">In-charge / Doctor</p>
                        <p class="font-bold text-gray-900 mt-0.5" x-text="selectedProvider.contact_person || 'Facility Incharge'"></p>
                    </div>

                    <div>
                        <p class="text-gray-400 uppercase font-semibold text-[10px]">Helpline Phone</p>
                        <p class="font-bold text-teal-600 mt-0.5 font-mono" x-text="selectedProvider.contact"></p>
                    </div>

                    <template x-if="selectedProvider.timing">
                        <div class="sm:col-span-2">
                            <p class="text-gray-400 uppercase font-semibold text-[10px]">Operating / OPD Hours</p>
                            <p class="font-semibold text-gray-800 mt-0.5 flex items-center gap-1.5">
                                <i class="fa-regular fa-clock text-teal-600"></i>
                                <span x-text="selectedProvider.timing"></span>
                            </p>
                        </div>
                    </template>
                </div>

                <!-- Complete Address & Interactive Embedded Google Map -->
                <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100 space-y-3">
                    <div class="flex items-center justify-between">
                        <p class="text-gray-400 uppercase font-bold text-[10px] flex items-center gap-1.5">
                            <i class="fa-solid fa-map-pin text-rose-500 text-xs"></i> Location Address & Google Map Pin
                        </p>
                        <template x-if="selectedProvider.latitude && selectedProvider.longitude">
                            <span class="px-2 py-0.5 rounded-lg bg-teal-50 text-teal-700 text-[10px] font-mono font-bold border border-teal-100" x-text="'GPS Pin: ' + Number(selectedProvider.latitude).toFixed(4) + ', ' + Number(selectedProvider.longitude).toFixed(4)"></span>
                        </template>
                    </div>

                    <div>
                        <p class="font-bold text-gray-900 text-sm" x-text="selectedProvider.address"></p>
                        <p class="text-gray-600 mt-0.5" x-text="(selectedProvider.block ? selectedProvider.block + ', ' : '') + selectedProvider.district + ', ' + selectedProvider.state + (selectedProvider.pincode ? ' - ' + selectedProvider.pincode : '')"></p>
                        <template x-if="selectedProvider.landmark">
                            <p class="text-gray-500 italic text-[11px] mt-1" x-text="'Landmark: ' + selectedProvider.landmark"></p>
                        </template>
                    </div>

                    <!-- Live Interactive Embedded Map Iframe -->
                    <div class="w-full h-48 rounded-xl overflow-hidden border border-gray-200 bg-gray-100 shadow-inner relative">
                        <iframe :src="getEmbedUrl(selectedProvider)" class="w-full h-full border-0" loading="lazy" allowfullscreen referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>

                    <div class="pt-1 flex flex-wrap items-center justify-between gap-2">
                        <a :href="getMapLink(selectedProvider)" target="_blank" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white hover:bg-teal-50 text-teal-700 border border-teal-200 rounded-xl text-xs font-bold transition-all shadow-xs">
                            <i class="fa-solid fa-diamond-turn-right text-rose-500"></i>
                            <span>Get Driving / Transit Directions</span>
                        </a>
                        <template x-if="selectedProvider.map_location">
                            <button type="button" @click="navigator.clipboard.writeText(getMapLink(selectedProvider)); alert('Google Maps location link copied!')" class="text-[11px] text-gray-500 hover:text-gray-700 flex items-center gap-1">
                                <i class="fa-regular fa-copy"></i> Copy Link
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Services List -->
                <template x-if="selectedProvider.services_offered">
                    <div>
                        <p class="text-gray-400 uppercase font-semibold text-[10px] mb-1">Available Services & Specialities</p>
                        <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100 text-gray-700 leading-relaxed whitespace-pre-line" x-text="selectedProvider.services_offered"></div>
                    </div>
                </template>
            </div>

            <!-- Modal Footer -->
            <div class="p-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between gap-3">
                <a :href="'tel:' + selectedProvider.contact" class="px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 shadow-sm">
                    <i class="fa-solid fa-phone"></i>
                    <span>Direct Call</span>
                </a>

                <button @click="showModal = false" class="px-5 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-xl text-xs font-bold transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('healthcareDirectoryPage', (initialData) => ({
        items: initialData || [],
        indiaLocations: <?php echo $indiaStatesJson; ?>,
        activeFilter: '', // '' for all, 'hospital', 'clinic', 'eye', 'dental', 'pathology_lab', 'pharmacy', 'doctor'
        search: '',
        selectedState: '',
        selectedDistrict: '',
        selectedBlock: '',
        emergencyOnly: false,
        discountOnly: false,
        currentPage: 1,
        pageSize: 9,

        // Modal
        showModal: false,
        selectedProvider: {},

        get districtOptions() {
            if (!this.selectedState || !this.indiaLocations[this.selectedState]) return [];
            return this.indiaLocations[this.selectedState];
        },

        onStateChange() {
            this.selectedDistrict = '';
            this.currentPage = 1;
        },

        setTypeFilter(type) {
            this.activeFilter = type;
            this.currentPage = 1;
        },

        get hasActiveFilters() {
            return this.activeFilter || this.search || this.selectedState || this.selectedDistrict || this.selectedBlock || this.emergencyOnly || this.discountOnly;
        },

        resetFilters() {
            this.activeFilter = '';
            this.search = '';
            this.selectedState = '';
            this.selectedDistrict = '';
            this.selectedBlock = '';
            this.emergencyOnly = false;
            this.discountOnly = false;
            this.currentPage = 1;
        },

        get filteredItems() {
            return this.items.filter(p => {
                // Category / Type pill
                if (this.activeFilter === 'eye') {
                    if (p.speciality !== 'eye') return false;
                } else if (this.activeFilter === 'dental') {
                    if (p.speciality !== 'dental') return false;
                } else if (this.activeFilter) {
                    if (p.type !== this.activeFilter) return false;
                }

                // Search keyword
                if (this.search) {
                    const q = this.search.toLowerCase();
                    const match = (p.name && p.name.toLowerCase().includes(q)) ||
                                  (p.contact_person && p.contact_person.toLowerCase().includes(q)) ||
                                  (p.services_offered && p.services_offered.toLowerCase().includes(q)) ||
                                  (p.district && p.district.toLowerCase().includes(q)) ||
                                  (p.pincode && p.pincode.includes(q)) ||
                                  (p.address && p.address.toLowerCase().includes(q));
                    if (!match) return false;
                }

                // State
                if (this.selectedState && p.state !== this.selectedState) return false;

                // District
                if (this.selectedDistrict && p.district !== this.selectedDistrict) return false;

                // Block
                if (this.selectedBlock) {
                    const b = this.selectedBlock.toLowerCase();
                    if (!p.block || !p.block.toLowerCase().includes(b)) return false;
                }

                // Emergency
                if (this.emergencyOnly && parseInt(p.emergency_available) !== 1) return false;

                // Discount
                if (this.discountOnly && !p.discount_offered) return false;

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

        openModal(p) {
            this.selectedProvider = p;
            this.showModal = true;
        },

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
            return p.speciality_custom || 'General Healthcare';
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
                'hospital': 'bg-blue-50 text-blue-700 border border-blue-100',
                'clinic': 'bg-teal-50 text-teal-700 border border-teal-100',
                'pathology_lab': 'bg-purple-50 text-purple-700 border border-purple-100',
                'pharmacy': 'bg-emerald-50 text-emerald-700 border border-emerald-100',
                'doctor': 'bg-amber-50 text-amber-700 border border-amber-100'
            };
            return map[type] || 'bg-gray-50 text-gray-700';
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
        }
    }));
});
</script>

<?php require 'includes/footer.php'; ?>
