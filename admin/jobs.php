<?php
// ============================================================
// admin/jobs.php
// Job Openings & Recruitment Management (Coordinator/Manager/Admin)
// ============================================================

require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../includes/india_locations.php';

$csrfToken = generateCsrfToken();

if (!checkRole($pdo, 'coordinator')) {
    setFlash('error', 'Unauthorized access. Coordinator/Manager/Admin role required.');
    header('Location: dashboard.php');
    exit;
}

require 'includes/header.php';

// Ensure tables exist
$jobsTableExists = dbTableExists($pdo, 'job_openings');
$appsTableExists = dbTableExists($pdo, 'job_applications');

$jobs = [];
$totalApplicationsCount = 0;
$shortlistedCount = 0;
$selectedCount = 0;

if ($jobsTableExists) {
    $stmt = $pdo->query("
        SELECT j.*, 
               u.name AS created_by_name,
               (SELECT COUNT(*) FROM job_applications a WHERE a.job_id = j.id) AS applications_count,
               (SELECT COUNT(*) FROM job_applications a WHERE a.job_id = j.id AND a.status IN ('shortlisted','interview_scheduled')) AS shortlisted_count,
               (SELECT COUNT(*) FROM job_applications a WHERE a.job_id = j.id AND a.status = 'selected') AS selected_count
        FROM job_openings j
        LEFT JOIN users u ON j.created_by = u.id
        ORDER BY j.id DESC
    ");
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($appsTableExists) {
        $totalApplicationsCount = (int)$pdo->query("SELECT COUNT(*) FROM job_applications")->fetchColumn();
        $shortlistedCount = (int)$pdo->query("SELECT COUNT(*) FROM job_applications WHERE status IN ('shortlisted','interview_scheduled')")->fetchColumn();
        $selectedCount = (int)$pdo->query("SELECT COUNT(*) FROM job_applications WHERE status = 'selected'")->fetchColumn();
    }
}

// Compute Metrics
$totalJobs = count($jobs);
$activeJobsCount = 0;
$totalVacancies = 0;

foreach ($jobs as $j) {
    if ($j['status'] === 'active') {
        $activeJobsCount++;
    }
    $totalVacancies += (int)($j['openings_count'] ?? 1);
}

$indiaStatesJson = india_state_district_js();
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900" x-data="jobOpeningsManager()">
    <?php require 'includes/sidebar.php'; ?>

    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6 lg:p-8">
            <!-- Flash Message -->
            <?php displayFlash(); ?>

            <!-- Page Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <div>
                    <div class="flex items-center gap-2.5">
                        <span class="p-2.5 rounded-2xl bg-teal-500/10 text-teal-600 dark:text-teal-400">
                            <i class="fa-solid fa-briefcase text-2xl"></i>
                        </span>
                        <div>
                            <h1 class="text-2xl md:text-3xl font-black text-gray-900 dark:text-white tracking-tight">Job Postings & Careers</h1>
                            <p class="text-xs md:text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                                Manage NGO recruitment drives for State, District, Block & Panchayat Coordinators and staff.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <a href="job_applications.php" class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-800 hover:bg-gray-900 text-white text-xs font-bold rounded-xl shadow-sm transition">
                        <i class="fa-solid fa-users-viewfinder"></i>
                        <span>View Applications (<?php echo $totalApplicationsCount; ?>)</span>
                    </a>

                    <button @click="openCreateModal()" class="inline-flex items-center gap-2 px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold rounded-xl shadow-sm transition transform hover:-translate-y-0.5 shadow-teal-600/20">
                        <i class="fa-solid fa-plus text-xs"></i>
                        <span>Post New Job</span>
                    </button>

                    <button @click="exportCSV()" class="inline-flex items-center gap-2 px-3.5 py-2.5 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 text-xs font-semibold rounded-xl transition shadow-xs">
                        <i class="fa-solid fa-file-csv text-teal-600"></i>
                        <span class="hidden sm:inline">Export</span>
                    </button>
                </div>
            </div>

            <!-- KPI Metric Stats (4 Cards) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <!-- Total Openings -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700/60 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Openings</p>
                        <p class="text-2xl font-black text-gray-900 dark:text-white mt-1"><?php echo $totalJobs; ?></p>
                        <p class="text-[11px] text-gray-400 mt-0.5"><?php echo $totalVacancies; ?> total vacancies</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-teal-50 dark:bg-teal-900/30 text-teal-600 dark:text-teal-400 flex items-center justify-center text-xl">
                        <i class="fa-solid fa-bullhorn"></i>
                    </div>
                </div>

                <!-- Active Jobs -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700/60 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Active Recruitment</p>
                        <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1"><?php echo $activeJobsCount; ?></p>
                        <p class="text-[11px] text-gray-400 mt-0.5">Accepting candidate applications</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-xl">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                </div>

                <!-- Applications Received -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700/60 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-blue-600 dark:text-blue-400">Total Applicants</p>
                        <p class="text-2xl font-black text-blue-600 dark:text-blue-400 mt-1"><?php echo $totalApplicationsCount; ?></p>
                        <p class="text-[11px] text-gray-400 mt-0.5">Online resumes submitted</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center text-xl">
                        <i class="fa-solid fa-file-lines"></i>
                    </div>
                </div>

                <!-- Shortlisted & Selected -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700/60 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-purple-600 dark:text-purple-400">Hiring Pipeline</p>
                        <p class="text-2xl font-black text-purple-600 dark:text-purple-400 mt-1"><?php echo $shortlistedCount + $selectedCount; ?></p>
                        <p class="text-[11px] text-gray-400 mt-0.5"><?php echo $shortlistedCount; ?> Shortlisted • <?php echo $selectedCount; ?> Selected</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 flex items-center justify-center text-xl">
                        <i class="fa-solid fa-award"></i>
                    </div>
                </div>
            </div>

            <!-- Filters Bar -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 md:p-5 border border-gray-100 dark:border-gray-700/60 shadow-xs mb-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
                    
                    <!-- Search -->
                    <div class="relative lg:col-span-2">
                        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-gray-400 text-xs"></i>
                        <input type="text" x-model="searchQuery" placeholder="Search by Job Code, Title, Location..." class="w-full pl-9 pr-3.5 py-2 bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                    </div>

                    <!-- Category Filter -->
                    <div>
                        <select x-model="filterCategory" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                            <option value="">All Categories</option>
                            <option value="state_coordinator">State Coordinator</option>
                            <option value="district_coordinator">District Coordinator</option>
                            <option value="block_coordinator">Block Coordinator</option>
                            <option value="panchayat_coordinator">Panchayat Coordinator</option>
                            <option value="other">Other Roles</option>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <select x-model="filterStatus" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                            <option value="">All Statuses</option>
                            <option value="active">Active Only</option>
                            <option value="inactive">Inactive</option>
                            <option value="closed">Closed</option>
                            <option value="draft">Draft</option>
                        </select>
                    </div>

                    <!-- State Filter -->
                    <div>
                        <select x-model="filterState" @change="filterDistrict = ''" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                            <option value="">All States</option>
                            <template x-for="st in Object.keys(indiaLocations)" :key="st">
                                <option :value="st" x-text="st"></option>
                            </template>
                        </select>
                    </div>

                </div>
            </div>

            <!-- Job Openings Table -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-xs overflow-hidden">
                <div class="overflow-x-auto w-full">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-gray-50/80 dark:bg-gray-700/50 text-gray-500 dark:text-gray-400 font-bold uppercase tracking-wider text-[11px] border-b border-gray-100 dark:border-gray-700">
                            <tr>
                                <th class="p-4">Job Code & Title</th>
                                <th class="p-4">Role Category</th>
                                <th class="p-4">Location / Area</th>
                                <th class="p-4 text-center">Vacancies</th>
                                <th class="p-4 text-center">Applicants</th>
                                <th class="p-4">Salary Range</th>
                                <th class="p-4">Timeline</th>
                                <th class="p-4 text-center">Status</th>
                                <th class="p-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <template x-for="job in paginatedJobs" :key="job.id">
                                <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-700/30 transition-colors">
                                    
                                    <!-- Job Code & Title -->
                                    <td class="p-4">
                                        <div>
                                            <span class="font-mono text-[11px] font-bold text-teal-700 dark:text-teal-400 bg-teal-50 dark:bg-teal-900/40 px-1.5 py-0.5 rounded" x-text="job.job_code"></span>
                                            <h4 class="font-bold text-gray-900 dark:text-white text-xs mt-1" x-text="job.title"></h4>
                                            <span class="text-[10px] text-gray-400" x-text="job.job_type"></span>
                                        </div>
                                    </td>

                                    <!-- Category Badge -->
                                    <td class="p-4 whitespace-nowrap">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide"
                                              :class="{
                                                  'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300': job.category === 'state_coordinator',
                                                  'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300': job.category === 'district_coordinator',
                                                  'bg-teal-100 text-teal-800 dark:bg-teal-900/40 dark:text-teal-300': job.category === 'block_coordinator',
                                                  'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300': job.category === 'panchayat_coordinator',
                                                  'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300': job.category === 'other'
                                              }"
                                              x-text="formatCategoryLabel(job.category)">
                                        </span>
                                    </td>

                                    <!-- Location -->
                                    <td class="p-4">
                                        <div class="text-gray-800 dark:text-gray-200 font-medium truncate max-w-[180px]" x-text="job.location"></div>
                                        <div class="text-[11px] text-gray-400 mt-0.5" x-text="(job.district ? job.district + ', ' : '') + (job.state || '')"></div>
                                    </td>

                                    <!-- Vacancies -->
                                    <td class="p-4 text-center">
                                        <span class="font-black text-gray-900 dark:text-white text-sm" x-text="job.openings_count"></span>
                                    </td>

                                    <!-- Applications Count -->
                                    <td class="p-4 text-center whitespace-nowrap">
                                        <a :href="'job_applications.php?job_id=' + job.id" 
                                           class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-black transition"
                                           :class="job.applications_count > 0 ? 'bg-blue-100 text-blue-800 hover:bg-blue-200 dark:bg-blue-900/50 dark:text-blue-200' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400'">
                                            <i class="fa-solid fa-users text-[10px]"></i>
                                            <span x-text="job.applications_count"></span>
                                        </a>
                                    </td>

                                    <!-- Salary Range -->
                                    <td class="p-4 whitespace-nowrap">
                                        <span class="font-semibold text-gray-700 dark:text-gray-300" x-text="job.salary_range || 'As per NGO norms'"></span>
                                    </td>

                                    <!-- Timeline -->
                                    <td class="p-4 whitespace-nowrap text-gray-600 dark:text-gray-400">
                                        <div>Posted: <span class="font-medium text-gray-800 dark:text-gray-200" x-text="job.posted_date"></span></div>
                                        <div class="text-[11px] text-gray-400" x-text="job.last_date ? 'Last Date: ' + job.last_date : 'Open until filled'"></div>
                                    </td>

                                    <!-- Status Badge & Toggle -->
                                    <td class="p-4 text-center whitespace-nowrap">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider"
                                              :class="{
                                                  'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300': job.status === 'active',
                                                  'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300': job.status === 'closed',
                                                  'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300': job.status === 'inactive',
                                                  'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300': job.status === 'draft'
                                              }"
                                              x-text="job.status">
                                        </span>
                                    </td>

                                    <!-- Actions -->
                                    <td class="p-4 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-1.5">
                                            
                                            <!-- View Applications Link -->
                                            <a :href="'job_applications.php?job_id=' + job.id" 
                                               title="View Candidates"
                                               class="p-2 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400 transition">
                                                <i class="fa-solid fa-users-viewfinder"></i>
                                            </a>

                                            <!-- View Details Modal -->
                                            <button type="button" 
                                                    @click="openViewModal(job)" 
                                                    title="View Full Details"
                                                    class="p-2 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 transition">
                                                <i class="fa-solid fa-eye"></i>
                                            </button>

                                            <!-- Edit Job -->
                                            <button type="button" 
                                                    @click="openEditModal(job)" 
                                                    title="Edit Job Opening"
                                                    class="p-2 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 dark:bg-amber-900/30 dark:text-amber-400 transition">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>

                                            <!-- Toggle Status -->
                                            <button type="button" 
                                                    @click="toggleStatus(job)" 
                                                    :title="job.status === 'active' ? 'Mark as Closed' : 'Mark as Active'"
                                                    class="p-2 rounded-lg transition"
                                                    :class="job.status === 'active' ? 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100 dark:bg-emerald-900/30' : 'bg-gray-100 text-gray-500 hover:bg-gray-200 dark:bg-gray-700'">
                                                <i class="fa-solid" :class="job.status === 'active' ? 'fa-toggle-on text-base' : 'fa-toggle-off text-base'"></i>
                                            </button>

                                            <!-- Delete Job -->
                                            <button type="button" 
                                                    @click="confirmDelete(job)" 
                                                    title="Delete Opening"
                                                    class="p-2 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 dark:bg-rose-900/30 dark:text-rose-400 transition">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </div>
                                    </td>

                                </tr>
                            </template>

                            <template x-if="filteredJobs.length === 0">
                                <tr>
                                    <td colspan="9" class="p-12 text-center text-gray-500 dark:text-gray-400">
                                        <i class="fa-solid fa-briefcase text-3xl text-gray-300 dark:text-gray-600 mb-2"></i>
                                        <p class="text-sm font-semibold">No job openings found matching the criteria.</p>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div class="px-6 py-4 bg-gray-50/50 dark:bg-gray-700/20 border-t border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row items-center justify-between gap-4" x-show="filteredJobs.length > 0">
                    <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                        <span>Showing <strong class="text-gray-800 dark:text-gray-200" x-text="(page - 1) * perPage + 1"></strong> to <strong class="text-gray-800 dark:text-gray-200" x-text="Math.min(page * perPage, filteredJobs.length)"></strong> of <strong class="text-gray-800 dark:text-gray-200" x-text="filteredJobs.length"></strong> results</span>
                        <div class="flex items-center gap-1.5 ml-2">
                            <label class="text-[11px] text-gray-400">Per page:</label>
                            <select x-model.number="perPage" @change="page = 1" class="text-xs py-1 px-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-700 dark:text-gray-300">
                                <option :value="10">10</option>
                                <option :value="25">25</option>
                                <option :value="50">50</option>
                                <option :value="100">100</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex items-center gap-1" x-show="totalPages > 1">
                        <button @click="setPage(1)" :disabled="page === 1" class="px-2.5 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed">
                            <i class="fa-solid fa-angles-left text-[10px]"></i>
                        </button>
                        <button @click="setPage(page - 1)" :disabled="page === 1" class="px-2.5 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed">
                            <i class="fa-solid fa-chevron-left text-[10px]"></i>
                        </button>
                        <template x-for="p in totalPages" :key="p">
                            <button x-show="p === 1 || p === totalPages || (p >= page - 2 && p <= page + 2)"
                                    @click="setPage(p)" 
                                    :class="page === p ? 'bg-teal-600 text-white font-bold border-teal-600 shadow-xs' : 'text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700'" 
                                    class="w-8 h-8 rounded-lg border text-xs flex items-center justify-center transition" 
                                    x-text="p">
                            </button>
                        </template>
                        <button @click="setPage(page + 1)" :disabled="page === totalPages" class="px-2.5 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed">
                            <i class="fa-solid fa-chevron-right text-[10px]"></i>
                        </button>
                        <button @click="setPage(totalPages)" :disabled="page === totalPages" class="px-2.5 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed">
                            <i class="fa-solid fa-angles-right text-[10px]"></i>
                        </button>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <!-- ADD / EDIT JOB OPENING MODAL -->
    <div x-show="showFormModal" 
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/70 backdrop-blur-xs"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div class="bg-white dark:bg-gray-800 rounded-3xl max-w-3xl w-full max-h-[92vh] flex flex-col shadow-2xl overflow-hidden border border-gray-100 dark:border-gray-700"
             @click.away="showFormModal = false">
            
            <div class="p-5 md:p-6 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-gradient-to-r from-teal-50/50 to-white dark:from-gray-800 dark:to-gray-800">
                <div class="flex items-center gap-2.5">
                    <span class="p-2 rounded-xl bg-teal-600 text-white text-base">
                        <i class="fa-solid" :class="isEditMode ? 'fa-pen-to-square' : 'fa-plus'"></i>
                    </span>
                    <h3 class="text-lg font-black text-gray-900 dark:text-white" x-text="isEditMode ? 'Edit Job Opening' : 'Post New Job Opening'"></h3>
                </div>
                <button type="button" @click="showFormModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form action="actions/job_logic.php" method="POST" class="p-5 md:p-6 overflow-y-auto flex-1 space-y-4 text-xs">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="action" :value="isEditMode ? 'update_job' : 'create_job'">
                <input type="hidden" name="id" :value="formData.id">

                <!-- 1. Basic Job Details -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5">
                    <div class="sm:col-span-2">
                        <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Job Title <span class="text-rose-500">*</span></label>
                        <input type="text" name="title" x-model="formData.title" required placeholder="e.g. State Program Coordinator" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Category <span class="text-rose-500">*</span></label>
                        <select name="category" x-model="formData.category" required class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                            <option value="state_coordinator">State Coordinator</option>
                            <option value="district_coordinator">District Coordinator</option>
                            <option value="block_coordinator">Block Coordinator</option>
                            <option value="panchayat_coordinator">Panchayat Coordinator</option>
                            <option value="other">Other Role</option>
                        </select>
                    </div>

                    <div x-show="formData.category === 'other'" class="sm:col-span-3">
                        <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Custom Role Name</label>
                        <input type="text" name="category_custom" x-model="formData.category_custom" placeholder="e.g. Health Outreach Executive" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Job Type</label>
                        <select name="job_type" x-model="formData.job_type" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                            <option value="Full-time">Full-time</option>
                            <option value="Part-time">Part-time</option>
                            <option value="Contract">Contract</option>
                            <option value="Internship">Internship</option>
                            <option value="Volunteer">Volunteer</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Number of Vacancies</label>
                        <input type="number" name="openings_count" x-model="formData.openings_count" min="1" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Salary / Remuneration Range</label>
                        <input type="text" name="salary_range" x-model="formData.salary_range" placeholder="e.g. ₹25,000 - ₹35,000 / month" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                    </div>
                </div>

                <!-- 2. Location Details -->
                <div class="pt-3 border-t border-gray-100 dark:border-gray-700 grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                    <div>
                        <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">State</label>
                        <select name="state" x-model="formData.state" @change="onStateChange()" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                            <option value="">Select State</option>
                            <template x-for="st in Object.keys(indiaLocations)" :key="st">
                                <option :value="st" x-text="st"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">District</label>
                        <select name="district" x-model="formData.district" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                            <option value="">Select District</option>
                            <template x-for="dist in districtOptions" :key="dist">
                                <option :value="dist" x-text="dist"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Block / Tehsil</label>
                        <input type="text" name="block" x-model="formData.block" placeholder="e.g. Sadar" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Job Location / HQ Address <span class="text-rose-500">*</span></label>
                        <input type="text" name="location" x-model="formData.location" required placeholder="e.g. State Head Office, Lucknow or District Office" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                    </div>
                </div>

                <!-- 3. Qualifications & Timeline -->
                <div class="pt-3 border-t border-gray-100 dark:border-gray-700 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                    <div>
                        <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Min. Qualification</label>
                        <input type="text" name="min_qualification" x-model="formData.min_qualification" placeholder="e.g. Graduate / MSW" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Experience Required</label>
                        <input type="text" name="experience_required" x-model="formData.experience_required" placeholder="e.g. 1-3 Years in Field" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Posted Date</label>
                        <input type="date" name="posted_date" x-model="formData.posted_date" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Last Date to Apply</label>
                        <input type="date" name="last_date" x-model="formData.last_date" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                    </div>
                </div>

                <!-- 4. Descriptions & Requirements -->
                <div class="pt-3 border-t border-gray-100 dark:border-gray-700 space-y-3">
                    <div>
                        <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Job Description & Role Overview <span class="text-rose-500">*</span></label>
                        <textarea name="description" x-model="formData.description" required rows="3" placeholder="Explain the key objective and scope of this role..." class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500"></textarea>
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Skills & Requirements</label>
                        <textarea name="requirements" x-model="formData.requirements" rows="2" placeholder="Key qualifications, travel requirements, vehicle, technical skills..." class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500"></textarea>
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Key Responsibilities</label>
                        <textarea name="responsibilities" x-model="formData.responsibilities" rows="2" placeholder="Day to day duties, reporting hierarchy, target enrollments..." class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500"></textarea>
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Posting Status</label>
                        <select name="status" x-model="formData.status" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                            <option value="active">Active (Visible & Open for applications)</option>
                            <option value="inactive">Inactive (Hidden)</option>
                            <option value="closed">Closed (Hiring completed)</option>
                            <option value="draft">Draft (Unpublished)</option>
                        </select>
                    </div>
                </div>

                <!-- Form Submit Actions -->
                <div class="pt-4 border-t border-gray-100 dark:border-gray-700 flex items-center justify-end gap-2.5">
                    <button type="button" @click="showFormModal = false" class="px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-semibold hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-bold transition shadow-sm">
                        <span x-text="isEditMode ? 'Update Job Opening' : 'Publish Job Opening'"></span>
                    </button>
                </div>

            </form>
        </div>
    </div>

    <!-- VIEW JOB DETAILS MODAL -->
    <div x-show="showViewModal" 
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/70 backdrop-blur-xs"
         x-transition>

        <div class="bg-white dark:bg-gray-800 rounded-3xl max-w-2xl w-full max-h-[90vh] flex flex-col shadow-2xl overflow-hidden border border-gray-100 dark:border-gray-700"
             @click.away="showViewModal = false">
            
            <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-teal-50/40 dark:bg-gray-800">
                <div>
                    <span class="font-mono text-xs font-bold text-teal-700 dark:text-teal-400" x-text="viewData.job_code"></span>
                    <h3 class="text-xl font-black text-gray-900 dark:text-white mt-0.5" x-text="viewData.title"></h3>
                </div>
                <button type="button" @click="showViewModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <div class="p-6 overflow-y-auto flex-1 space-y-4 text-xs leading-relaxed text-gray-700 dark:text-gray-300">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-3.5 bg-gray-50 dark:bg-gray-900/50 rounded-2xl">
                    <div>
                        <span class="text-gray-400 uppercase text-[10px] block font-bold">Category</span>
                        <strong class="text-gray-900 dark:text-white" x-text="formatCategoryLabel(viewData.category)"></strong>
                    </div>
                    <div>
                        <span class="text-gray-400 uppercase text-[10px] block font-bold">Job Type</span>
                        <strong class="text-gray-900 dark:text-white" x-text="viewData.job_type"></strong>
                    </div>
                    <div>
                        <span class="text-gray-400 uppercase text-[10px] block font-bold">Vacancies</span>
                        <strong class="text-teal-600" x-text="viewData.openings_count"></strong>
                    </div>
                    <div>
                        <span class="text-gray-400 uppercase text-[10px] block font-bold">Salary</span>
                        <strong class="text-gray-900 dark:text-white" x-text="viewData.salary_range || 'As per norms'"></strong>
                    </div>
                </div>

                <div>
                    <h4 class="font-bold text-gray-900 dark:text-white uppercase tracking-wider text-[11px] mb-1">Location</h4>
                    <p x-text="viewData.location + (viewData.district ? ', ' + viewData.district : '') + (viewData.state ? ', ' + viewData.state : '')"></p>
                </div>

                <div>
                    <h4 class="font-bold text-gray-900 dark:text-white uppercase tracking-wider text-[11px] mb-1">Role Description</h4>
                    <p class="whitespace-pre-line text-gray-600 dark:text-gray-400" x-text="viewData.description"></p>
                </div>

                <div x-show="viewData.requirements">
                    <h4 class="font-bold text-gray-900 dark:text-white uppercase tracking-wider text-[11px] mb-1">Requirements & Eligibility</h4>
                    <p class="whitespace-pre-line text-gray-600 dark:text-gray-400" x-text="viewData.requirements"></p>
                </div>

                <div x-show="viewData.responsibilities">
                    <h4 class="font-bold text-gray-900 dark:text-white uppercase tracking-wider text-[11px] mb-1">Key Responsibilities</h4>
                    <p class="whitespace-pre-line text-gray-600 dark:text-gray-400" x-text="viewData.responsibilities"></p>
                </div>

                <div class="pt-3 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between text-[11px] text-gray-400">
                    <span>Posted on: <strong class="text-gray-700 dark:text-gray-300" x-text="viewData.posted_date"></strong></span>
                    <span>Deadline: <strong class="text-gray-700 dark:text-gray-300" x-text="viewData.last_date || 'Open until filled'"></strong></span>
                </div>
            </div>

            <div class="p-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/80 flex items-center justify-between">
                <a :href="'job_applications.php?job_id=' + viewData.id" class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-xl font-bold text-xs flex items-center gap-1.5 transition">
                    <i class="fa-solid fa-users-viewfinder"></i>
                    <span>View Candidates (<span x-text="viewData.applications_count || 0"></span>)</span>
                </a>
                <button type="button" @click="showViewModal = false" class="px-4 py-2 rounded-xl bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold text-xs">
                    Close
                </button>
            </div>

        </div>
    </div>

</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('jobOpeningsManager', () => ({
        jobsList: <?php echo json_encode($jobs); ?>,
        indiaLocations: <?php echo $indiaStatesJson; ?>,
        
        searchQuery: '',
        filterCategory: '',
        filterStatus: '',
        filterState: '',

        showFormModal: false,
        showViewModal: false,
        isEditMode: false,

        formData: {
            id: '',
            title: '',
            category: 'other',
            category_custom: '',
            job_type: 'Full-time',
            openings_count: 1,
            salary_range: '',
            state: '',
            district: '',
            block: '',
            location: '',
            min_qualification: '',
            experience_required: '',
            posted_date: '<?php echo date('Y-m-d'); ?>',
            last_date: '',
            description: '',
            requirements: '',
            responsibilities: '',
            status: 'active'
        },

        viewData: {},

        get districtOptions() {
            if (!this.formData.state || !this.indiaLocations[this.formData.state]) return [];
            return this.indiaLocations[this.formData.state];
        },
        districtList: [],
        page: 1,
        perPage: 10,

        get totalPages() {
            return Math.ceil(this.filteredJobs.length / this.perPage) || 1;
        },

        get paginatedJobs() {
            const start = (this.page - 1) * this.perPage;
            return this.filteredJobs.slice(start, start + this.perPage);
        },

        setPage(p) {
            if (p >= 1 && p <= this.totalPages) this.page = p;
        },

        onStateChange() {
            if (!this.districtOptions.includes(this.formData.district)) {
                this.formData.district = '';
            }
        },

        get filteredJobs() {
            return this.jobsList.filter(j => {
                const matchesSearch = !this.searchQuery || 
                    (j.title && j.title.toLowerCase().includes(this.searchQuery.toLowerCase())) ||
                    (j.job_code && j.job_code.toLowerCase().includes(this.searchQuery.toLowerCase())) ||
                    (j.location && j.location.toLowerCase().includes(this.searchQuery.toLowerCase())) ||
                    (j.description && j.description.toLowerCase().includes(this.searchQuery.toLowerCase()));

                const matchesCategory = !this.filterCategory || j.category === this.filterCategory;
                const matchesStatus = !this.filterStatus || j.status === this.filterStatus;
                const matchesState = !this.filterState || j.state === this.filterState;

                return matchesSearch && matchesCategory && matchesStatus && matchesState;
            });
        },

        formatCategoryLabel(cat) {
            const labels = {
                'state_coordinator': 'State Coordinator',
                'district_coordinator': 'District Coordinator',
                'block_coordinator': 'Block Coordinator',
                'panchayat_coordinator': 'Panchayat Coordinator',
                'other': 'Other Role'
            };
            return labels[cat] || cat;
        },

        openCreateModal() {
            this.isEditMode = false;
            this.formData = {
                id: '',
                title: '',
                category: 'district_coordinator',
                category_custom: '',
                job_type: 'Full-time',
                openings_count: 1,
                salary_range: '',
                state: '',
                district: '',
                block: '',
                location: '',
                min_qualification: '',
                experience_required: '',
                posted_date: '<?php echo date('Y-m-d'); ?>',
                last_date: '',
                description: '',
                requirements: '',
                responsibilities: '',
                status: 'active'
            };
            this.showFormModal = true;
        },

        openEditModal(job) {
            this.isEditMode = true;
            this.formData = {
                id: job.id,
                title: job.title || '',
                category: job.category || 'other',
                category_custom: job.category_custom || '',
                job_type: job.job_type || 'Full-time',
                openings_count: job.openings_count || 1,
                salary_range: job.salary_range || '',
                state: job.state || '',
                district: job.district || '',
                block: job.block || '',
                location: job.location || '',
                min_qualification: job.min_qualification || '',
                experience_required: job.experience_required || '',
                posted_date: job.posted_date || '',
                last_date: job.last_date || '',
                description: job.description || '',
                requirements: job.requirements || '',
                responsibilities: job.responsibilities || '',
                status: job.status || 'active'
            };
            this.showFormModal = true;
        },

        openViewModal(job) {
            this.viewData = job;
            this.showViewModal = true;
        },

        async toggleStatus(job) {
            const newStatus = job.status === 'active' ? 'closed' : 'active';
            const formData = new FormData();
            formData.append('csrf_token', '<?php echo htmlspecialchars($csrfToken); ?>');
            formData.append('action', 'toggle_job_status');
            formData.append('id', job.id);
            formData.append('status', newStatus);
            formData.append('is_ajax', '1');

            try {
                const response = await fetch('actions/job_logic.php', { method: 'POST', body: formData });
                const res = await response.json();
                if (res.success) {
                    job.status = newStatus;
                } else {
                    alert(res.message || 'Failed to update status.');
                }
            } catch (err) {
                console.error(err);
                alert('Network error occurred.');
            }
        },

        confirmDelete(job) {
            if (confirm(`Are you sure you want to delete job opening '${job.title}' (${job.job_code})? Any submitted applications for this job will also be removed.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'actions/job_logic.php';

                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = '<?php echo htmlspecialchars($csrfToken); ?>';
                form.appendChild(csrfInput);

                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete_job';
                form.appendChild(actionInput);

                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = job.id;
                form.appendChild(idInput);

                document.body.appendChild(form);
                form.submit();
            }
        },

        exportCSV() {
            let csv = 'Job Code,Title,Category,Job Type,Vacancies,Location,State,District,Salary,Status,Posted Date,Last Date,Applicants\n';
            this.filteredJobs.forEach(j => {
                csv += `"${j.job_code}","${j.title.replace(/"/g, '""')}","${j.category}","${j.job_type}",${j.openings_count},"${j.location.replace(/"/g, '""')}","${j.state || ''}","${j.district || ''}","${j.salary_range || ''}","${j.status}","${j.posted_date}","${j.last_date || ''}",${j.applications_count || 0}\n`;
            });
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'NGO_Job_Openings_' + new Date().toISOString().slice(0, 10) + '.csv';
            link.click();
        }
    }));
});
</script>

<?php require 'includes/footer.php'; ?>
