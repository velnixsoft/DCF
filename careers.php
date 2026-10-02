<?php
// ============================================================
// careers.php
// Public NGO Careers & Job Openings Portal
// ============================================================

require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/india_locations.php';

$csrfToken = generateCsrfToken();

// Fetch active job openings
$jobs = [];
$totalCount = 0;
$stateCoordCount = 0;
$districtCoordCount = 0;
$blockCoordCount = 0;
$panchayatCoordCount = 0;
$otherCount = 0;
$totalVacancies = 0;

if (dbTableExists($pdo, 'job_openings')) {
    try {
        $stmt = $pdo->query("
            SELECT * 
            FROM job_openings 
            WHERE status = 'active' 
            ORDER BY posted_date DESC, id DESC
        ");
        $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $jobs = [];
    }
}

foreach ($jobs as $j) {
    $totalCount++;
    $totalVacancies += (int)($j['openings_count'] ?? 1);
    $cat = $j['category'] ?? 'other';
    if ($cat === 'state_coordinator') $stateCoordCount++;
    elseif ($cat === 'district_coordinator') $districtCoordCount++;
    elseif ($cat === 'block_coordinator') $blockCoordCount++;
    elseif ($cat === 'panchayat_coordinator') $panchayatCoordCount++;
    else $otherCount++;
}

// Pre-check for direct job application via ?apply_job_id=XX
$applyJobId = filter_input(INPUT_GET, 'apply_job_id', FILTER_VALIDATE_INT) ?: (filter_input(INPUT_GET, 'job_id', FILTER_VALIDATE_INT) ?: '');
$viewJobId = filter_input(INPUT_GET, 'view_id', FILTER_VALIDATE_INT) ?: '';

$indiaStatesJson = india_state_district_js();
$indiaStatesList = india_state_list();

// Unique states and districts in active jobs
$jobStates = [];
$jobDistricts = [];
foreach ($jobs as $j) {
    if (!empty($j['state']) && !in_array($j['state'], $jobStates, true)) $jobStates[] = $j['state'];
    if (!empty($j['district']) && !in_array($j['district'], $jobDistricts, true)) $jobDistricts[] = $j['district'];
}
sort($jobStates);
sort($jobDistricts);

require 'includes/header.php';
?>

<div class="bg-gradient-to-b from-[#F0FDFD] via-white to-[#F8FAFC] min-h-screen py-8 md:py-14"
     x-data="careersPortal(
         <?php echo htmlspecialchars(json_encode($jobs, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>,
         <?php echo $applyJobId ? (int)$applyJobId : "''"; ?>,
         <?php echo $viewJobId ? (int)$viewJobId : "''"; ?>
     )"
     x-cloak>

    <div class="container mx-auto px-4 max-w-7xl">
        
        <!-- Top Navigation Switcher (Jobs <-> Skills Guidance) -->
        <div class="flex items-center justify-center mb-8">
            <div class="inline-flex p-1 bg-gray-100 rounded-2xl shadow-inner border border-gray-200">
                <a href="careers.php" class="px-5 py-2 rounded-xl text-xs font-bold bg-white text-teal-700 shadow-sm transition flex items-center gap-2">
                    <i class="fa-solid fa-briefcase text-teal-600"></i>
                    <span>Job Openings</span>
                </a>
                <a href="career-guidance.php" class="px-5 py-2 rounded-xl text-xs font-bold text-gray-600 hover:text-gray-900 transition flex items-center gap-2">
                    <i class="fa-solid fa-graduation-cap text-indigo-600"></i>
                    <span>Career Guidance & Skills</span>
                </a>
            </div>
        </div>

        <!-- Hero Header -->
        <div class="max-w-4xl mx-auto text-center mb-8 md:mb-12">
            <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white shadow-xs border border-teal-100 text-teal-700 text-xs sm:text-sm font-bold uppercase tracking-wider">
                <i class="fa-solid fa-briefcase text-teal-600"></i>
                Careers & Opportunities • NGO Recruitment Drive
            </span>
            <h1 class="mt-4 text-3xl sm:text-4xl md:text-5xl font-black text-gray-900 tracking-tight">
                Work with Purpose. <span class="text-teal-600">Create Impact.</span>
            </h1>
            <p class="mt-3 text-sm sm:text-base md:text-lg text-gray-600 max-w-2xl mx-auto leading-relaxed">
                Join our grassroots initiatives across State, District, Block, and Gram Panchayat levels. Drive social welfare, community health, education, and rural empowerment programs.
            </p>

            <!-- Metrics Pills -->
            <div class="mt-6 flex flex-wrap items-center justify-center gap-3 md:gap-6 text-xs md:text-sm">
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-teal-50 border border-teal-100 text-teal-800 font-bold">
                    <i class="fa-solid fa-layer-group text-teal-600"></i>
                    <span><?php echo $totalCount; ?> Active Roles</span>
                </div>
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-amber-50 border border-amber-100 text-amber-800 font-bold">
                    <i class="fa-solid fa-users-line text-amber-600"></i>
                    <span><?php echo $totalVacancies; ?> Open Vacancies</span>
                </div>
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-800 font-bold">
                    <i class="fa-solid fa-shield-halved text-emerald-600"></i>
                    <span>Transparent Recruitment</span>
                </div>
            </div>
        </div>

        <!-- Filter & Search Panel -->
        <div class="bg-white rounded-3xl shadow-xl border border-teal-50 p-5 md:p-7 mb-10 transition-all">
            
            <!-- Category Tabs -->
            <div class="mb-6">
                <div class="flex items-center justify-between mb-2.5">
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Filter by Designation / Category:</span>
                    <span class="text-xs font-semibold text-teal-600" x-text="filteredJobs.length + ' positions found'"></span>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" @click="setCategoryFilter('')" 
                            class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all duration-150 flex items-center gap-2 shadow-xs"
                            :class="activeCategory === '' ? 'bg-teal-600 text-white shadow-teal-200' : 'bg-gray-50 text-gray-600 hover:bg-gray-100 border border-gray-100'">
                        <i class="fa-solid fa-list-check"></i>
                        <span>All Openings</span>
                        <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="activeCategory === '' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-700'"><?php echo $totalCount; ?></span>
                    </button>

                    <button type="button" @click="setCategoryFilter('state_coordinator')" 
                            class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all duration-150 flex items-center gap-2 shadow-xs"
                            :class="activeCategory === 'state_coordinator' ? 'bg-teal-600 text-white shadow-teal-200' : 'bg-gray-50 text-gray-600 hover:bg-gray-100 border border-gray-100'">
                        <i class="fa-solid fa-landmark"></i>
                        <span>State Coordinator</span>
                        <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="activeCategory === 'state_coordinator' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-700'"><?php echo $stateCoordCount; ?></span>
                    </button>

                    <button type="button" @click="setCategoryFilter('district_coordinator')" 
                            class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all duration-150 flex items-center gap-2 shadow-xs"
                            :class="activeCategory === 'district_coordinator' ? 'bg-teal-600 text-white shadow-teal-200' : 'bg-gray-50 text-gray-600 hover:bg-gray-100 border border-gray-100'">
                        <i class="fa-solid fa-city"></i>
                        <span>District Coordinator</span>
                        <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="activeCategory === 'district_coordinator' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-700'"><?php echo $districtCoordCount; ?></span>
                    </button>

                    <button type="button" @click="setCategoryFilter('block_coordinator')" 
                            class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all duration-150 flex items-center gap-2 shadow-xs"
                            :class="activeCategory === 'block_coordinator' ? 'bg-teal-600 text-white shadow-teal-200' : 'bg-gray-50 text-gray-600 hover:bg-gray-100 border border-gray-100'">
                        <i class="fa-solid fa-tree"></i>
                        <span>Block Coordinator</span>
                        <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="activeCategory === 'block_coordinator' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-700'"><?php echo $blockCoordCount; ?></span>
                    </button>

                    <button type="button" @click="setCategoryFilter('panchayat_coordinator')" 
                            class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all duration-150 flex items-center gap-2 shadow-xs"
                            :class="activeCategory === 'panchayat_coordinator' ? 'bg-teal-600 text-white shadow-teal-200' : 'bg-gray-50 text-gray-600 hover:bg-gray-100 border border-gray-100'">
                        <i class="fa-solid fa-house-chimney-user"></i>
                        <span>Panchayat Coordinator</span>
                        <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="activeCategory === 'panchayat_coordinator' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-700'"><?php echo $panchayatCoordCount; ?></span>
                    </button>

                    <button type="button" @click="setCategoryFilter('other')" 
                            class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all duration-150 flex items-center gap-2 shadow-xs"
                            :class="activeCategory === 'other' ? 'bg-teal-600 text-white shadow-teal-200' : 'bg-gray-50 text-gray-600 hover:bg-gray-100 border border-gray-100'">
                        <i class="fa-solid fa-user-gear"></i>
                        <span>Other Staff & Specialized</span>
                        <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="activeCategory === 'other' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-700'"><?php echo $otherCount; ?></span>
                    </button>
                </div>
            </div>

            <!-- Search Inputs & Geographic Filters -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                <!-- Search Keyword -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">
                        <i class="fa-solid fa-magnifying-glass text-teal-600 mr-1"></i> Search Role / Keywords
                    </label>
                    <div class="relative">
                        <input type="text" 
                               x-model="searchKeyword" 
                               placeholder="e.g. Coordinator, MSW, Lucknow..." 
                               class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all">
                        <button type="button" x-show="searchKeyword" @click="searchKeyword = ''" class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600 text-xs">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>

                <!-- State Filter -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">
                        <i class="fa-solid fa-map-location-dot text-teal-600 mr-1"></i> State
                    </label>
                    <select x-model="selectedState" 
                            @change="onStateChange()"
                            class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all">
                        <option value="">All States / Locations</option>
                        <?php foreach ($indiaStatesList as $st): ?>
                            <option value="<?php echo htmlspecialchars($st); ?>"><?php echo htmlspecialchars($st); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- District Filter -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">
                        <i class="fa-solid fa-location-pin text-teal-600 mr-1"></i> District
                    </label>
                    <select x-model="selectedDistrict" 
                            class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all"
                            :disabled="!selectedState && districtsList.length === 0">
                        <option value="">All Districts</option>
                        <template x-for="d in districtsList" :key="d">
                            <option :value="d" x-text="d"></option>
                        </template>
                    </select>
                </div>

                <!-- Job Type / Clear Filter -->
                <div class="flex items-center gap-2">
                    <div class="flex-1">
                        <label class="block text-xs font-bold text-gray-700 mb-1.5">
                            <i class="fa-solid fa-clock text-teal-600 mr-1"></i> Job Type
                        </label>
                        <select x-model="selectedJobType" 
                                class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all">
                            <option value="">All Types</option>
                            <option value="Full-time">Full-time</option>
                            <option value="Part-time">Part-time</option>
                            <option value="Contract">Contract</option>
                            <option value="Internship">Internship</option>
                            <option value="Volunteer">Volunteer</option>
                        </select>
                    </div>

                    <button type="button" 
                            x-show="hasActiveFilters()" 
                            @click="clearFilters()"
                            title="Reset all filters"
                            class="h-[38px] px-3 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-xl text-xs font-bold transition flex items-center justify-center border border-rose-100 flex-shrink-0">
                        <i class="fa-solid fa-rotate-left mr-1"></i> Reset
                    </button>
                </div>
            </div>
        </div>

        <!-- Job Openings Card Grid -->
        <div class="mb-14">
            
            <!-- When jobs match filter -->
            <div x-show="filteredJobs.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <template x-for="job in filteredJobs" :key="job.id">
                    <div class="group bg-white rounded-3xl p-6 shadow-md hover:shadow-2xl border border-gray-100 hover:border-teal-300 transition-all duration-300 flex flex-col justify-between relative overflow-hidden">
                        
                        <!-- Top Decor Accent -->
                        <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r"
                             :class="{
                                 'from-teal-500 to-emerald-500': job.category === 'state_coordinator',
                                 'from-blue-500 to-indigo-500': job.category === 'district_coordinator',
                                 'from-amber-500 to-orange-500': job.category === 'block_coordinator',
                                 'from-purple-500 to-pink-500': job.category === 'panchayat_coordinator',
                                 'from-gray-500 to-slate-700': job.category === 'other'
                             }"></div>

                        <div>
                            <!-- Header & Category Badge -->
                            <div class="flex items-start justify-between gap-3 mb-3.5">
                                <span class="px-2.5 py-1 rounded-lg text-[11px] font-black uppercase tracking-wider inline-flex items-center gap-1.5"
                                      :class="{
                                          'bg-teal-50 text-teal-700 border border-teal-200/60': job.category === 'state_coordinator',
                                          'bg-blue-50 text-blue-700 border border-blue-200/60': job.category === 'district_coordinator',
                                          'bg-amber-50 text-amber-700 border border-amber-200/60': job.category === 'block_coordinator',
                                          'bg-purple-50 text-purple-700 border border-purple-200/60': job.category === 'panchayat_coordinator',
                                          'bg-gray-100 text-gray-700 border border-gray-200': job.category === 'other'
                                      }">
                                    <i :class="getCategoryIcon(job.category)"></i>
                                    <span x-text="getCategoryLabel(job)"></span>
                                </span>

                                <span class="text-[11px] font-mono text-gray-400 bg-gray-50 px-2 py-0.5 rounded-md border border-gray-100" x-text="job.job_code"></span>
                            </div>

                            <!-- Job Title -->
                            <h3 class="text-lg md:text-xl font-black text-gray-900 group-hover:text-teal-600 transition-colors line-clamp-2 mb-2" x-text="job.title"></h3>

                            <!-- Location & Basic Details -->
                            <div class="space-y-1.5 mb-4">
                                <div class="flex items-center gap-2 text-xs text-gray-600">
                                    <i class="fa-solid fa-location-dot text-teal-600 w-4 text-center"></i>
                                    <span class="font-medium" x-text="formatLocation(job)"></span>
                                </div>

                                <div class="flex items-center gap-2 text-xs text-gray-600" x-show="job.openings_count">
                                    <i class="fa-solid fa-users text-teal-600 w-4 text-center"></i>
                                    <span>Vacancies: <strong class="text-gray-900" x-text="job.openings_count + ' Openings'"></strong></span>
                                </div>

                                <div class="flex items-center gap-2 text-xs text-gray-600" x-show="job.salary_range">
                                    <i class="fa-solid fa-indian-rupee-sign text-teal-600 w-4 text-center"></i>
                                    <span>Remuneration: <strong class="text-emerald-700 font-bold" x-text="job.salary_range"></strong></span>
                                </div>
                            </div>

                            <!-- Highlights Tag Cloud -->
                            <div class="flex flex-wrap items-center gap-1.5 mb-4 pt-3 border-t border-gray-100">
                                <span class="px-2 py-0.5 rounded-md bg-gray-100 text-gray-700 text-[10px] font-bold" x-text="job.job_type || 'Full-time'"></span>
                                <span class="px-2 py-0.5 rounded-md bg-teal-50 text-teal-700 text-[10px] font-bold" x-show="job.min_qualification" x-text="'🎓 ' + job.min_qualification"></span>
                                <span class="px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 text-[10px] font-bold" x-show="job.experience_required" x-text="'⏳ ' + job.experience_required"></span>
                            </div>

                            <!-- Description Snippet -->
                            <p class="text-xs text-gray-500 line-clamp-3 mb-5 leading-relaxed" x-text="job.description"></p>
                        </div>

                        <!-- Action Buttons -->
                        <div class="pt-4 border-t border-gray-100 flex items-center gap-2">
                            <button type="button" 
                                    @click="openJobDetailsModal(job)"
                                    class="flex-1 py-2.5 px-3 bg-gray-100 hover:bg-gray-200 text-gray-800 text-xs font-bold rounded-xl transition flex items-center justify-center gap-1.5">
                                <i class="fa-regular fa-eye"></i>
                                <span>Details</span>
                            </button>

                            <button type="button" 
                                    @click="openApplyModal(job)"
                                    class="flex-1 py-2.5 px-3 bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold rounded-xl shadow-md shadow-teal-600/20 hover:shadow-teal-600/40 transition flex items-center justify-center gap-1.5 transform active:scale-95">
                                <i class="fa-solid fa-paper-plane text-xs"></i>
                                <span>Apply Now</span>
                            </button>
                        </div>

                    </div>
                </template>
            </div>

            <!-- Empty State -->
            <div x-show="filteredJobs.length === 0" class="bg-white rounded-3xl p-12 text-center border border-gray-100 shadow-sm max-w-lg mx-auto">
                <div class="w-16 h-16 bg-teal-50 text-teal-600 rounded-2xl flex items-center justify-center text-2xl mx-auto mb-4">
                    <i class="fa-solid fa-briefcase"></i>
                </div>
                <h3 class="text-lg font-black text-gray-900">No Open Positions Match Your Criteria</h3>
                <p class="text-xs text-gray-500 mt-2 max-w-sm mx-auto">
                    Try adjusting your keyword search, removing the state or district filter, or switching category to view all available NGO job postings.
                </p>
                <button type="button" @click="clearFilters()" class="mt-5 px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold rounded-xl transition shadow-xs">
                    View All Active Roles
                </button>
            </div>

        </div>

        <!-- Trust & Why Work With Us Section -->
        <div class="bg-white rounded-3xl shadow-xl border border-teal-50 p-6 md:p-10 mb-14">
            <div class="text-center max-w-2xl mx-auto mb-8">
                <span class="text-xs font-bold text-teal-600 uppercase tracking-wider">Join Our Movement</span>
                <h2 class="text-2xl md:text-3xl font-black text-gray-900 mt-1">Why Work with Our NGO?</h2>
                <p class="text-xs md:text-sm text-gray-500 mt-2">
                    We offer grassroots leadership roles, direct community interaction, and genuine career growth in the development sector.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="p-5 rounded-2xl bg-teal-50/50 border border-teal-100">
                    <div class="w-10 h-10 rounded-xl bg-teal-600 text-white flex items-center justify-center text-base mb-3 shadow-sm">
                        <i class="fa-solid fa-hands-holding-child"></i>
                    </div>
                    <h4 class="text-sm font-black text-gray-900 mb-1">Direct Social Impact</h4>
                    <p class="text-xs text-gray-600">Work directly with beneficiaries, SHGs, youth, and underprivileged families to transform lives.</p>
                </div>

                <div class="p-5 rounded-2xl bg-blue-50/50 border border-blue-100">
                    <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center text-base mb-3 shadow-sm">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                    <h4 class="text-sm font-black text-gray-900 mb-1">Field Leadership</h4>
                    <p class="text-xs text-gray-600">Lead Block and District operations with full autonomy and institutional backing.</p>
                </div>

                <div class="p-5 rounded-2xl bg-amber-50/50 border border-amber-100">
                    <div class="w-10 h-10 rounded-xl bg-amber-600 text-white flex items-center justify-center text-base mb-3 shadow-sm">
                        <i class="fa-solid fa-certificate"></i>
                    </div>
                    <h4 class="text-sm font-black text-gray-900 mb-1">Skill Development</h4>
                    <p class="text-xs text-gray-600">Continuous training in community mobilization, project governance, and stakeholder relations.</p>
                </div>

                <div class="p-5 rounded-2xl bg-purple-50/50 border border-purple-100">
                    <div class="w-10 h-10 rounded-xl bg-purple-600 text-white flex items-center justify-center text-base mb-3 shadow-sm">
                        <i class="fa-solid fa-scale-balanced"></i>
                    </div>
                    <h4 class="text-sm font-black text-gray-900 mb-1">Equal Opportunity</h4>
                    <p class="text-xs text-gray-600">Merit-based hiring, supportive work culture, and respectful environment for all staff.</p>
                </div>
            </div>
        </div>

    </div>

    <!-- ============================================================ -->
    <!-- 1. JOB DETAILS MODAL -->
    <!-- ============================================================ -->
    <div x-show="showDetailsModal" 
         class="fixed inset-0 z-50 overflow-y-auto" 
         aria-labelledby="modal-title" role="dialog" aria-modal="true"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs transition-opacity" @click="closeJobDetailsModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-gray-100"
                 x-show="showDetailsModal"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

                <!-- Modal Header -->
                <div class="p-6 md:p-8 bg-gradient-to-r from-teal-600 to-teal-800 text-white relative">
                    <button type="button" @click="closeJobDetailsModal()" class="absolute right-5 top-5 text-white/80 hover:text-white text-lg">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                    
                    <div class="flex items-center gap-2 mb-2">
                        <span class="px-2.5 py-0.5 rounded-md bg-white/20 text-white text-[11px] font-bold uppercase tracking-wider" x-text="getCategoryLabel(activeJob)"></span>
                        <span class="text-xs font-mono text-white/80" x-text="activeJob?.job_code"></span>
                    </div>

                    <h2 class="text-xl md:text-2xl font-black tracking-tight" x-text="activeJob?.title"></h2>
                    
                    <div class="flex flex-wrap items-center gap-3 mt-3 text-xs text-white/90">
                        <span><i class="fa-solid fa-location-dot mr-1 text-teal-300"></i><span x-text="formatLocation(activeJob)"></span></span>
                        <span><i class="fa-solid fa-briefcase mr-1 text-teal-300"></i><span x-text="activeJob?.job_type || 'Full-time'"></span></span>
                        <span x-show="activeJob?.salary_range"><i class="fa-solid fa-indian-rupee-sign mr-1 text-teal-300"></i><span x-text="activeJob?.salary_range"></span></span>
                    </div>
                </div>

                <!-- Modal Content -->
                <div class="p-6 md:p-8 max-h-[60vh] overflow-y-auto space-y-6 text-xs md:text-sm text-gray-700">
                    
                    <!-- Key Specs Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 p-4 rounded-2xl bg-gray-50 border border-gray-100">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-gray-400 block">Total Vacancies</span>
                            <span class="font-bold text-gray-900" x-text="(activeJob?.openings_count || 1) + ' Posts'"></span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-gray-400 block">Min Qualification</span>
                            <span class="font-bold text-gray-900" x-text="activeJob?.min_qualification || 'Graduate / Relevant'"></span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-gray-400 block">Experience</span>
                            <span class="font-bold text-gray-900" x-text="activeJob?.experience_required || 'Not Specified'"></span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-gray-400 block">Posted Date</span>
                            <span class="font-bold text-gray-900" x-text="activeJob?.posted_date || 'Recently'"></span>
                        </div>
                        <div x-show="activeJob?.last_date">
                            <span class="text-[10px] uppercase font-bold text-rose-500 block">Last Date to Apply</span>
                            <span class="font-bold text-rose-700" x-text="activeJob?.last_date"></span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-gray-400 block">Status</span>
                            <span class="font-bold text-emerald-600 uppercase">Actively Hiring</span>
                        </div>
                    </div>

                    <!-- Job Description -->
                    <div x-show="activeJob?.description">
                        <h4 class="text-xs font-black uppercase tracking-wider text-gray-400 mb-2">Role Overview & Objective</h4>
                        <p class="text-gray-700 whitespace-pre-line leading-relaxed" x-text="activeJob?.description"></p>
                    </div>

                    <!-- Responsibilities -->
                    <div x-show="activeJob?.responsibilities">
                        <h4 class="text-xs font-black uppercase tracking-wider text-gray-400 mb-2">Key Responsibilities</h4>
                        <div class="p-4 rounded-2xl bg-teal-50/40 border border-teal-100 text-gray-800 whitespace-pre-line leading-relaxed" x-text="activeJob?.responsibilities"></div>
                    </div>

                    <!-- Requirements / Eligibility -->
                    <div x-show="activeJob?.requirements">
                        <h4 class="text-xs font-black uppercase tracking-wider text-gray-400 mb-2">Eligibility & Requirements</h4>
                        <div class="p-4 rounded-2xl bg-amber-50/40 border border-amber-100 text-gray-800 whitespace-pre-line leading-relaxed" x-text="activeJob?.requirements"></div>
                    </div>

                </div>

                <!-- Modal Footer -->
                <div class="p-4 md:p-6 bg-gray-50 border-t border-gray-100 flex items-center justify-between gap-3">
                    <button type="button" @click="closeJobDetailsModal()" class="px-5 py-2.5 bg-white hover:bg-gray-100 text-gray-700 text-xs font-bold rounded-xl border border-gray-200 transition">
                        Close
                    </button>

                    <button type="button" 
                            @click="applyFromDetailsModal()"
                            class="px-6 py-2.5 bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold rounded-xl shadow-md shadow-teal-600/20 transition flex items-center gap-2">
                        <i class="fa-solid fa-paper-plane"></i>
                        <span>Apply for this Position</span>
                    </button>
                </div>

            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- 2. APPLY FOR JOB APPLICATION FORM MODAL (EVENT PATTERN) -->
    <!-- ============================================================ -->
    <div x-show="showApplyModal" 
         class="fixed inset-0 z-50 overflow-y-auto" 
         aria-labelledby="modal-title" role="dialog" aria-modal="true"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs transition-opacity" @click="closeApplyModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-gray-100"
                 x-show="showApplyModal"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

                <!-- Form Modal Header -->
                <div class="p-6 bg-gradient-to-r from-teal-700 via-teal-800 to-slate-900 text-white relative">
                    <button type="button" @click="closeApplyModal()" class="absolute right-5 top-5 text-white/80 hover:text-white text-lg">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                    
                    <div class="flex items-center gap-2 mb-1.5">
                        <span class="px-2.5 py-0.5 rounded-md bg-white/20 text-white text-[10px] font-bold uppercase tracking-wider">Candidate Application Form</span>
                        <span class="text-xs font-mono text-teal-200" x-text="activeJob?.job_code"></span>
                    </div>

                    <h2 class="text-lg md:text-xl font-black tracking-tight" x-text="'Apply for: ' + (activeJob?.title || 'Open Position')"></h2>
                    <p class="text-xs text-white/80 mt-1">Please fill in your authentic personal and professional details. You can attach your CV / Resume below.</p>
                </div>

                <!-- Form Body -->
                <form @submit.prevent="submitApplication($event)" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="job_id" :value="activeJob?.id">

                    <div class="p-6 md:p-8 max-h-[65vh] overflow-y-auto space-y-4 text-xs">
                        
                        <!-- Error Alert -->
                        <div x-show="errorMessage" x-cloak class="p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 flex items-start gap-2.5">
                            <i class="fa-solid fa-triangle-exclamation text-rose-500 mt-0.5"></i>
                            <span class="text-xs font-medium" x-text="errorMessage"></span>
                        </div>

                        <!-- 1. Personal Particulars -->
                        <div class="border-b border-gray-100 pb-3">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-teal-700 flex items-center gap-1.5">
                                <i class="fa-solid fa-user"></i> Personal Particulars
                            </h4>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Full Name -->
                            <div class="sm:col-span-2">
                                <label class="block font-bold text-gray-700 mb-1">Candidate Full Name <span class="text-rose-500">*</span></label>
                                <input type="text" name="applicant_name" x-model="form.applicant_name" required placeholder="e.g. Amit Kumar Verma"
                                       class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                            </div>

                            <!-- Mobile Contact -->
                            <div>
                                <label class="block font-bold text-gray-700 mb-1">Mobile / WhatsApp Number <span class="text-rose-500">*</span></label>
                                <div class="relative">
                                    <span class="absolute left-3.5 top-2.5 text-gray-400 font-bold">+91</span>
                                    <input type="tel" name="contact" x-model="form.contact" required maxlength="10" placeholder="9876543210"
                                           class="w-full pl-12 bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                                </div>
                            </div>

                            <!-- Email Address -->
                            <div>
                                <label class="block font-bold text-gray-700 mb-1">Email Address <span class="text-rose-500">*</span></label>
                                <input type="email" name="email" x-model="form.email" required placeholder="amit.verma@example.com"
                                       class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                            </div>

                            <!-- Gender -->
                            <div>
                                <label class="block font-bold text-gray-700 mb-1">Gender</label>
                                <select name="gender" x-model="form.gender" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <!-- Date of Birth & Age -->
                            <div>
                                <label class="block font-bold text-gray-700 mb-1">Date of Birth</label>
                                <div class="flex items-center gap-2">
                                    <input type="date" name="dob" x-model="form.dob" @change="calculateAge()"
                                           class="flex-1 bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                                    <span x-show="calculatedAge" class="text-xs font-bold text-teal-700 bg-teal-50 px-2.5 py-2 rounded-xl border border-teal-100 flex-shrink-0" x-text="calculatedAge + ' yrs'"></span>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Professional & Academic Credentials -->
                        <div class="border-b border-gray-100 pb-3 pt-3">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-teal-700 flex items-center gap-1.5">
                                <i class="fa-solid fa-graduation-cap"></i> Academic & Professional Credentials
                            </h4>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Highest Qualification -->
                            <div>
                                <label class="block font-bold text-gray-700 mb-1">Highest Qualification <span class="text-rose-500">*</span></label>
                                <select name="qualification" x-model="form.qualification" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                                    <option value="">Select Qualification</option>
                                    <option value="Post Graduate (MSW / Sociology / Rural Dev)">Post Graduate (MSW / Sociology / Rural Dev)</option>
                                    <option value="Post Graduate (MA / M.Sc / M.Com / MBA)">Post Graduate (MA / M.Sc / M.Com / MBA)</option>
                                    <option value="Graduate (BSW / Social Sciences)">Graduate (BSW / Social Sciences)</option>
                                    <option value="Graduate (BA / B.Sc / B.Com / B.Tech)">Graduate (BA / B.Sc / B.Com / B.Tech)</option>
                                    <option value="B.Ed / D.El.Ed / Education">B.Ed / D.El.Ed / Education</option>
                                    <option value="Diploma / Polytechnic / Vocational">Diploma / Polytechnic / Vocational</option>
                                    <option value="12th Pass (Higher Secondary)">12th Pass (Higher Secondary)</option>
                                    <option value="Other Degree">Other Degree</option>
                                </select>
                            </div>

                            <!-- Experience in Years -->
                            <div>
                                <label class="block font-bold text-gray-700 mb-1">Relevant Experience</label>
                                <select name="experience_years" x-model="form.experience_years" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                                    <option value="Fresher / Entry Level">Fresher / Entry Level</option>
                                    <option value="Under 1 Year">Under 1 Year</option>
                                    <option value="1 - 2 Years">1 - 2 Years</option>
                                    <option value="3 - 5 Years">3 - 5 Years</option>
                                    <option value="5+ Years in NGO / Field Sector">5+ Years in NGO / Field Sector</option>
                                </select>
                            </div>
                        </div>

                        <!-- 3. Residential Location Particulars -->
                        <div class="border-b border-gray-100 pb-3 pt-3">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-teal-700 flex items-center gap-1.5">
                                <i class="fa-solid fa-map-location-dot"></i> Residential Location
                            </h4>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <!-- State -->
                            <div>
                                <label class="block font-bold text-gray-700 mb-1">State <span class="text-rose-500">*</span></label>
                                <select name="state" x-model="form.state" @change="onFormStateChange()" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                                    <option value="">Select State</option>
                                    <?php foreach ($indiaStatesList as $st): ?>
                                        <option value="<?php echo htmlspecialchars($st); ?>"><?php echo htmlspecialchars($st); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- District -->
                            <div>
                                <label class="block font-bold text-gray-700 mb-1">District <span class="text-rose-500">*</span></label>
                                <select name="district" x-model="form.district" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500"
                                        :disabled="!form.state && formDistricts.length === 0">
                                    <option value="">Select District</option>
                                    <template x-for="d in formDistricts" :key="d">
                                        <option :value="d" x-text="d"></option>
                                    </template>
                                </select>
                            </div>

                            <!-- City / Block -->
                            <div>
                                <label class="block font-bold text-gray-700 mb-1">Current City / Block</label>
                                <input type="text" name="current_city" x-model="form.current_city" placeholder="e.g. Aliganj / Bakshi Ka Talab"
                                       class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                            </div>

                            <!-- Full Address -->
                            <div class="sm:col-span-3">
                                <label class="block font-bold text-gray-700 mb-1">Full Residential Address <span class="text-rose-500">*</span></label>
                                <textarea name="address" x-model="form.address" rows="2" required placeholder="House/Flat No, Street, Landmark, Pincode"
                                          class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500"></textarea>
                            </div>
                        </div>

                        <!-- 4. Resume Attachment & Cover Note -->
                        <div class="border-b border-gray-100 pb-3 pt-3">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-teal-700 flex items-center gap-1.5">
                                <i class="fa-solid fa-file-arrow-up"></i> Resume / CV & Cover Pitch
                            </h4>
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Attach Resume / CV (PDF, DOC, DOCX - Max 10MB)</label>
                            <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-dashed border-teal-200 hover:border-teal-400 bg-teal-50/20 rounded-2xl transition-all">
                                <div class="space-y-1 text-center">
                                    <i class="fa-solid fa-cloud-arrow-up text-3xl text-teal-600 mb-2"></i>
                                    <div class="flex text-xs text-gray-600 justify-center">
                                        <label for="resume-upload" class="relative cursor-pointer bg-white rounded-md font-bold text-teal-600 hover:text-teal-700 focus-within:outline-none px-2 py-1 shadow-xs border border-gray-200">
                                            <span>Upload Resume</span>
                                            <input id="resume-upload" name="resume" type="file" accept=".pdf,.doc,.docx" @change="onResumeSelected($event)" class="sr-only">
                                        </label>
                                        <p class="pl-2 pt-1">or drag & drop here</p>
                                    </div>
                                    <p class="text-[10px] text-gray-400">PDF, DOC, DOCX up to 10MB</p>
                                    <div x-show="resumeFileName" class="mt-2 text-xs font-bold text-teal-800 bg-teal-100/80 px-3 py-1 rounded-lg inline-flex items-center gap-2">
                                        <i class="fa-solid fa-file-pdf"></i>
                                        <span x-text="resumeFileName"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Cover Note / Pitch -->
                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Brief Statement / Why do you want to join our team?</label>
                            <textarea name="cover_letter" x-model="form.cover_letter" rows="2" placeholder="Describe your background, skills and passion for community welfare..."
                                      class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500"></textarea>
                        </div>

                    </div>

                    <!-- Modal Actions Footer -->
                    <div class="p-4 md:p-6 bg-gray-50 border-t border-gray-100 flex items-center justify-between gap-3">
                        <button type="button" @click="closeApplyModal()" class="px-5 py-2.5 bg-white hover:bg-gray-100 text-gray-700 text-xs font-bold rounded-xl border border-gray-200 transition">
                            Cancel
                        </button>

                        <button type="submit" 
                                :disabled="loading"
                                class="px-6 py-2.5 bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold rounded-xl shadow-md shadow-teal-600/20 transition flex items-center gap-2 disabled:opacity-50">
                            <i class="fa-solid fa-paper-plane" :class="{'fa-spin': loading}"></i>
                            <span x-text="loading ? 'Submitting Application...' : 'Submit Application'"></span>
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- 3. SUCCESS CONFIRMATION MODAL -->
    <!-- ============================================================ -->
    <div x-show="showSuccessModal" 
         class="fixed inset-0 z-50 overflow-y-auto" 
         aria-labelledby="modal-title" role="dialog" aria-modal="true"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs transition-opacity" @click="showSuccessModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-3xl text-center overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full p-6 md:p-8 border border-gray-100"
                 x-show="showSuccessModal"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

                <!-- Success Icon -->
                <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-3xl flex items-center justify-center text-3xl mx-auto mb-4 shadow-inner">
                    <i class="fa-solid fa-circle-check"></i>
                </div>

                <h3 class="text-xl md:text-2xl font-black text-gray-900">Application Submitted!</h3>
                <p class="text-xs md:text-sm text-gray-600 mt-2" x-text="successData?.message"></p>

                <!-- Receipt Card -->
                <div class="mt-5 p-4 rounded-2xl bg-gray-50 border border-gray-200/80 text-left text-xs space-y-2">
                    <div class="flex justify-between items-center pb-2 border-b border-gray-200">
                        <span class="text-gray-500 font-medium">Application No:</span>
                        <span class="font-mono font-bold text-teal-700 text-sm" x-text="successData?.application_no"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500">Applicant:</span>
                        <span class="font-bold text-gray-800" x-text="successData?.applicant_name"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500">Applied Role:</span>
                        <span class="font-bold text-gray-800" x-text="successData?.job_title"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500">Submission Date:</span>
                        <span class="text-gray-700" x-text="successData?.applied_date"></span>
                    </div>
                </div>

                <div class="mt-6 flex flex-col sm:flex-row gap-3">
                    <button type="button" @click="showSuccessModal = false" class="flex-1 py-3 px-4 bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs rounded-xl shadow-md transition">
                        Explore Other Openings
                    </button>
                    <a href="index.php" class="flex-1 py-3 px-4 bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold text-xs rounded-xl transition">
                        Back to Home
                    </a>
                </div>

            </div>
        </div>
    </div>

</div>

<!-- Alpine Logic Component -->
<script>
document.addEventListener('alpine:init', () => {
    const indiaLocations = <?php echo $indiaStatesJson; ?>;

    Alpine.data('careersPortal', (initialJobs = [], autoApplyId = '', autoViewId = '') => ({
        jobs: initialJobs,
        activeCategory: '',
        searchKeyword: '',
        selectedState: '',
        selectedDistrict: '',
        selectedJobType: '',
        districtsList: [],
        
        // Modal controls
        showDetailsModal: false,
        showApplyModal: false,
        showSuccessModal: false,
        activeJob: null,
        loading: false,
        errorMessage: '',
        resumeFileName: '',
        calculatedAge: '',
        successData: null,
        
        formDistricts: [],
        form: {
            applicant_name: '',
            contact: '',
            email: '',
            gender: 'Male',
            dob: '',
            qualification: '',
            experience_years: 'Fresher / Entry Level',
            state: '',
            district: '',
            current_city: '',
            address: '',
            cover_letter: ''
        },

        init() {
            if (autoApplyId) {
                const targetJob = this.jobs.find(j => parseInt(j.id) === parseInt(autoApplyId));
                if (targetJob) {
                    this.openApplyModal(targetJob);
                }
            } else if (autoViewId) {
                const targetJob = this.jobs.find(j => parseInt(j.id) === parseInt(autoViewId));
                if (targetJob) {
                    this.openJobDetailsModal(targetJob);
                }
            }
        },

        get filteredJobs() {
            return this.jobs.filter(job => {
                // Category Filter
                if (this.activeCategory !== '') {
                    if (this.activeCategory === 'other') {
                        if (['state_coordinator', 'district_coordinator', 'block_coordinator', 'panchayat_coordinator'].includes(job.category)) {
                            return false;
                        }
                    } else if (job.category !== this.activeCategory) {
                        return false;
                    }
                }

                // State Filter
                if (this.selectedState && job.state) {
                    if (job.state.toLowerCase() !== this.selectedState.toLowerCase()) return false;
                }

                // District Filter
                if (this.selectedDistrict && job.district) {
                    if (job.district.toLowerCase() !== this.selectedDistrict.toLowerCase()) return false;
                }

                // Job Type Filter
                if (this.selectedJobType && job.job_type) {
                    if (job.job_type.toLowerCase() !== this.selectedJobType.toLowerCase()) return false;
                }

                // Search Keyword
                if (this.searchKeyword.trim() !== '') {
                    const q = this.searchKeyword.toLowerCase().trim();
                    const title = (job.title || '').toLowerCase();
                    const code = (job.job_code || '').toLowerCase();
                    const desc = (job.description || '').toLowerCase();
                    const loc = (job.location || '').toLowerCase();
                    const state = (job.state || '').toLowerCase();
                    const district = (job.district || '').toLowerCase();
                    const req = (job.requirements || '').toLowerCase();

                    if (!title.includes(q) && !code.includes(q) && !desc.includes(q) && !loc.includes(q) && !state.includes(q) && !district.includes(q) && !req.includes(q)) {
                        return false;
                    }
                }

                return true;
            });
        },

        setCategoryFilter(cat) {
            this.activeCategory = cat;
        },

        onStateChange() {
            this.selectedDistrict = '';
            if (this.selectedState && indiaLocations[this.selectedState]) {
                this.districtsList = indiaLocations[this.selectedState];
            } else {
                this.districtsList = [];
            }
        },

        onFormStateChange() {
            this.form.district = '';
            if (this.form.state && indiaLocations[this.form.state]) {
                this.formDistricts = indiaLocations[this.form.state];
            } else {
                this.formDistricts = [];
            }
        },

        hasActiveFilters() {
            return this.activeCategory !== '' || this.searchKeyword !== '' || this.selectedState !== '' || this.selectedDistrict !== '' || this.selectedJobType !== '';
        },

        clearFilters() {
            this.activeCategory = '';
            this.searchKeyword = '';
            this.selectedState = '';
            this.selectedDistrict = '';
            this.selectedJobType = '';
            this.districtsList = [];
        },

        getCategoryLabel(job) {
            if (!job) return 'Role';
            const map = {
                'state_coordinator': 'State Coordinator',
                'district_coordinator': 'District Coordinator',
                'block_coordinator': 'Block Coordinator',
                'panchayat_coordinator': 'Panchayat Coordinator',
                'other': job.category_custom || 'Specialized Staff'
            };
            return map[job.category] || job.category_custom || 'Coordinator';
        },

        getCategoryIcon(cat) {
            const map = {
                'state_coordinator': 'fa-solid fa-landmark',
                'district_coordinator': 'fa-solid fa-city',
                'block_coordinator': 'fa-solid fa-tree',
                'panchayat_coordinator': 'fa-solid fa-house-chimney-user',
                'other': 'fa-solid fa-user-gear'
            };
            return map[cat] || 'fa-solid fa-briefcase';
        },

        formatLocation(job) {
            if (!job) return 'Field Location';
            if (job.location) return job.location;
            const parts = [];
            if (job.block) parts.push(job.block);
            if (job.district) parts.push(job.district);
            if (job.state) parts.push(job.state);
            return parts.length > 0 ? parts.join(', ') : 'All India / Field';
        },

        openJobDetailsModal(job) {
            this.activeJob = job;
            this.showDetailsModal = true;
        },

        closeJobDetailsModal() {
            this.showDetailsModal = false;
        },

        applyFromDetailsModal() {
            const currentJob = this.activeJob;
            this.closeJobDetailsModal();
            this.openApplyModal(currentJob);
        },

        openApplyModal(job) {
            this.activeJob = job;
            this.errorMessage = '';
            this.resumeFileName = '';
            this.showApplyModal = true;
            if (this.form.state && indiaLocations[this.form.state]) {
                this.formDistricts = indiaLocations[this.form.state];
            }
        },

        closeApplyModal() {
            this.showApplyModal = false;
        },

        calculateAge() {
            if (!this.form.dob) {
                this.calculatedAge = '';
                return;
            }
            const birthDate = new Date(this.form.dob);
            const today = new Date();
            let age = today.getFullYear() - birthDate.getFullYear();
            const m = today.getMonth() - birthDate.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            this.calculatedAge = age > 0 ? age : '';
        },

        onResumeSelected(event) {
            const file = event.target.files[0];
            if (file) {
                this.resumeFileName = file.name + ' (' + (file.size / (1024 * 1024)).toFixed(2) + ' MB)';
            } else {
                this.resumeFileName = '';
            }
        },

        async submitApplication(event) {
            const formElement = event.target;
            this.loading = true;
            this.errorMessage = '';

            const formData = new FormData(formElement);
            if (this.calculatedAge) {
                formData.set('age', this.calculatedAge);
            }

            try {
                const response = await fetch('process/submit_job_application.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    this.successData = data;
                    this.showApplyModal = false;
                    this.showSuccessModal = true;
                    // Reset form
                    this.form.applicant_name = '';
                    this.form.contact = '';
                    this.form.email = '';
                    this.form.dob = '';
                    this.form.address = '';
                    this.form.cover_letter = '';
                    this.calculatedAge = '';
                    this.resumeFileName = '';
                } else {
                    this.errorMessage = data.message || 'Failed to submit application. Please verify all details.';
                }
            } catch (err) {
                console.error(err);
                this.errorMessage = 'Network error during submission. Please try again.';
            } finally {
                this.loading = false;
            }
        }
    }));
});
</script>

<?php require 'includes/footer.php'; ?>
