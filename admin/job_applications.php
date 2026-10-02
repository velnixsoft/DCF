<?php
// ============================================================
// admin/job_applications.php
// Job Applications & Candidate Recruitment Pipeline Management
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

$selectedJobId = filter_input(INPUT_GET, 'job_id', FILTER_VALIDATE_INT) ?: '';

// Fetch all jobs for filter dropdown
$allJobs = [];
try {
    $allJobs = $pdo->query("SELECT id, job_code, title, category, status FROM job_openings ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $allJobs = [];
}

// Fetch applications with joined job details
$applications = [];
try {
    $sql = "
        SELECT a.*, 
               j.title AS job_title, j.job_code, j.category AS job_category, j.location AS job_location,
               u.name AS reviewer_name
        FROM job_applications a
        JOIN job_openings j ON a.job_id = j.id
        LEFT JOIN users u ON a.reviewed_by = u.id
        ORDER BY a.id DESC
    ";
    $applications = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $applications = [];
}

// Compute Metrics
$totalApps = count($applications);
$pendingCount = 0;
$shortlistedCount = 0;
$interviewCount = 0;
$selectedCount = 0;
$rejectedCount = 0;

foreach ($applications as $app) {
    if ($app['status'] === 'pending' || $app['status'] === 'reviewed') {
        $pendingCount++;
    } elseif ($app['status'] === 'shortlisted') {
        $shortlistedCount++;
    } elseif ($app['status'] === 'interview_scheduled') {
        $interviewCount++;
    } elseif ($app['status'] === 'selected') {
        $selectedCount++;
    } elseif ($app['status'] === 'rejected') {
        $rejectedCount++;
    }
}
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900" x-data="jobApplicationsManager(<?php echo $selectedJobId ? $selectedJobId : "''"; ?>)">
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
                        <span class="p-2.5 rounded-2xl bg-blue-500/10 text-blue-600 dark:text-blue-400">
                            <i class="fa-solid fa-users-viewfinder text-2xl"></i>
                        </span>
                        <div>
                            <h1 class="text-2xl md:text-3xl font-black text-gray-900 dark:text-white tracking-tight">Job Applications & Hiring Pipeline</h1>
                            <p class="text-xs md:text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                                Screen candidate resumes, shortlist profiles, schedule interviews, and record recruitment status.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <a href="jobs.php" class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-800 hover:bg-gray-900 text-white text-xs font-bold rounded-xl shadow-sm transition">
                        <i class="fa-solid fa-briefcase"></i>
                        <span>Manage Job Openings</span>
                    </a>

                    <button @click="exportCSV()" class="inline-flex items-center gap-2 px-3.5 py-2.5 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 text-xs font-semibold rounded-xl transition shadow-xs">
                        <i class="fa-solid fa-file-csv text-teal-600"></i>
                        <span class="hidden sm:inline">Export</span>
                    </button>
                </div>
            </div>

            <!-- KPI Metric Stats (4 Cards) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <!-- Total Applications -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700/60 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Applications</p>
                        <p class="text-2xl font-black text-gray-900 dark:text-white mt-1"><?php echo $totalApps; ?></p>
                        <p class="text-[11px] text-gray-400 mt-0.5">Across all coordinator roles</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center text-xl">
                        <i class="fa-solid fa-file-lines"></i>
                    </div>
                </div>

                <!-- Pending Review -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700/60 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-amber-600 dark:text-amber-400">Pending Review</p>
                        <p class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1"><?php echo $pendingCount; ?></p>
                        <p class="text-[11px] text-gray-400 mt-0.5">Awaiting recruiter screening</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center text-xl">
                        <i class="fa-solid fa-hourglass-half"></i>
                    </div>
                </div>

                <!-- Shortlisted & Interviews -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700/60 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-purple-600 dark:text-purple-400">Shortlisted & Interviews</p>
                        <p class="text-2xl font-black text-purple-600 dark:text-purple-400 mt-1"><?php echo $shortlistedCount + $interviewCount; ?></p>
                        <p class="text-[11px] text-gray-400 mt-0.5"><?php echo $shortlistedCount; ?> Shortlisted • <?php echo $interviewCount; ?> Scheduled</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 flex items-center justify-center text-xl">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>
                </div>

                <!-- Selected Candidates -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700/60 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Selected Candidates</p>
                        <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1"><?php echo $selectedCount; ?></p>
                        <p class="text-[11px] text-gray-400 mt-0.5"><?php echo $rejectedCount; ?> Rejected</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-xl">
                        <i class="fa-solid fa-user-check"></i>
                    </div>
                </div>
            </div>

            <!-- Filters Bar -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 md:p-5 border border-gray-100 dark:border-gray-700/60 shadow-xs mb-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                    
                    <!-- Search -->
                    <div class="relative lg:col-span-1">
                        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-gray-400 text-xs"></i>
                        <input type="text" x-model="searchQuery" placeholder="Search candidate, phone, email, city..." class="w-full pl-9 pr-3.5 py-2 bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                    </div>

                    <!-- Job Opening Filter -->
                    <div>
                        <select x-model="filterJobId" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                            <option value="">All Job Openings</option>
                            <?php foreach ($allJobs as $jb): ?>
                                <option value="<?php echo (int)$jb['id']; ?>">
                                    <?php echo htmlspecialchars($jb['job_code'] . ' - ' . $jb['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <select x-model="filterStatus" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending Review</option>
                            <option value="reviewed">Reviewed</option>
                            <option value="shortlisted">Shortlisted</option>
                            <option value="interview_scheduled">Interview Scheduled</option>
                            <option value="selected">Selected</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>

                    <!-- Role Category Filter -->
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

                </div>
            </div>

            <!-- Applications Table -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-xs overflow-hidden">
                <div class="overflow-x-auto w-full">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-gray-50/80 dark:bg-gray-700/50 text-gray-500 dark:text-gray-400 font-bold uppercase tracking-wider text-[11px] border-b border-gray-100 dark:border-gray-700">
                            <tr>
                                <th class="p-4">App No. & Date</th>
                                <th class="p-4">Candidate Profile</th>
                                <th class="p-4">Applied Job Role</th>
                                <th class="p-4">Qualification & Exp</th>
                                <th class="p-4">Location</th>
                                <th class="p-4 text-center">Resume</th>
                                <th class="p-4 text-center">Status</th>
                                <th class="p-4 text-right">Recruiter Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <template x-for="app in paginatedApplications" :key="app.id">
                                <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-700/30 transition-colors">
                                    
                                    <!-- App No. & Date -->
                                    <td class="p-4 whitespace-nowrap">
                                        <span class="font-mono text-[11px] font-bold text-blue-700 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/40 px-1.5 py-0.5 rounded" x-text="app.application_no"></span>
                                        <div class="text-[10px] text-gray-400 mt-1" x-text="formatDate(app.applied_date)"></div>
                                    </td>

                                    <!-- Candidate Info -->
                                    <td class="p-4">
                                        <div class="flex items-center gap-2">
                                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-teal-500 to-emerald-600 text-white flex items-center justify-center font-bold text-xs flex-shrink-0" x-text="app.applicant_name ? app.applicant_name.charAt(0).toUpperCase() : 'U'"></div>
                                            <div>
                                                <h4 class="font-bold text-gray-900 dark:text-white text-xs" x-text="app.applicant_name"></h4>
                                                <div class="flex items-center gap-2 text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                                                    <a :href="'tel:' + app.contact" class="hover:text-teal-600 flex items-center gap-1 font-mono">
                                                        <i class="fa-solid fa-phone text-[9px]"></i>
                                                        <span x-text="app.contact"></span>
                                                    </a>
                                                    <span x-show="app.gender" class="text-gray-300 dark:text-gray-600">•</span>
                                                    <span x-show="app.gender" x-text="app.gender + (app.age ? ' (' + app.age + 'y)' : '')"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Applied Job Role -->
                                    <td class="p-4">
                                        <div class="font-bold text-gray-900 dark:text-white truncate max-w-[200px]" x-text="app.job_title"></div>
                                        <div class="flex items-center gap-1.5 mt-0.5">
                                            <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider"
                                                  :class="{
                                                      'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300': app.job_category === 'state_coordinator',
                                                      'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300': app.job_category === 'district_coordinator',
                                                      'bg-teal-100 text-teal-800 dark:bg-teal-900/40 dark:text-teal-300': app.job_category === 'block_coordinator',
                                                      'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300': app.job_category === 'panchayat_coordinator',
                                                      'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300': app.job_category === 'other'
                                                  }"
                                                  x-text="formatCategoryLabel(app.job_category)">
                                            </span>
                                            <span class="text-[10px] text-gray-400 font-mono" x-text="app.job_code"></span>
                                        </div>
                                    </td>

                                    <!-- Qualification & Exp -->
                                    <td class="p-4">
                                        <div class="font-semibold text-gray-800 dark:text-gray-200" x-text="app.qualification || 'Not Specified'"></div>
                                        <div class="text-[11px] text-teal-600 dark:text-teal-400 font-medium mt-0.5" x-text="app.experience_years ? app.experience_years + ' Yrs Experience' : 'Fresher / Entry Level'"></div>
                                    </td>

                                    <!-- Location -->
                                    <td class="p-4 text-gray-700 dark:text-gray-300">
                                        <div class="truncate max-w-[140px]" x-text="app.current_city || app.district || 'India'"></div>
                                        <div class="text-[11px] text-gray-400" x-text="app.state || ''"></div>
                                    </td>

                                    <!-- Resume File -->
                                    <td class="p-4 text-center whitespace-nowrap">
                                        <template x-if="app.resume_path">
                                            <a :href="'../' + app.resume_path" 
                                               target="_blank"
                                               class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-teal-50 text-teal-700 hover:bg-teal-100 dark:bg-teal-900/40 dark:text-teal-300 font-bold text-[11px] transition shadow-xs">
                                                <i class="fa-solid fa-file-pdf text-rose-500"></i>
                                                <span>Resume</span>
                                            </a>
                                        </template>
                                        <template x-if="!app.resume_path">
                                            <span class="text-gray-400 italic text-[11px]">No Resume</span>
                                        </template>
                                    </td>

                                    <!-- Status Badge -->
                                    <td class="p-4 text-center whitespace-nowrap">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider inline-flex items-center gap-1"
                                              :class="{
                                                  'bg-amber-100 text-amber-900 border border-amber-300 dark:bg-amber-900/40 dark:text-amber-200': app.status === 'pending',
                                                  'bg-blue-100 text-blue-900 border border-blue-300 dark:bg-blue-900/40 dark:text-blue-200': app.status === 'reviewed',
                                                  'bg-purple-100 text-purple-900 border border-purple-300 dark:bg-purple-900/40 dark:text-purple-200': app.status === 'shortlisted',
                                                  'bg-indigo-100 text-indigo-900 border border-indigo-300 dark:bg-indigo-900/40 dark:text-indigo-200': app.status === 'interview_scheduled',
                                                  'bg-emerald-100 text-emerald-900 border border-emerald-300 dark:bg-emerald-900/40 dark:text-emerald-200': app.status === 'selected',
                                                  'bg-rose-100 text-rose-900 border border-rose-300 dark:bg-rose-900/40 dark:text-rose-200': app.status === 'rejected'
                                              }">
                                            <span x-text="formatStatusLabel(app.status)"></span>
                                        </span>
                                    </td>

                                    <!-- Recruiter Actions -->
                                    <td class="p-4 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-1.5">
                                            
                                            <!-- Change Status Modal -->
                                            <button type="button" 
                                                    @click="openStatusModal(app)" 
                                                    title="Change Application Status"
                                                    class="p-2 rounded-lg bg-teal-50 text-teal-600 hover:bg-teal-100 dark:bg-teal-900/30 dark:text-teal-400 transition font-bold">
                                                <i class="fa-solid fa-sliders"></i>
                                            </button>

                                            <!-- View Details & Cover Letter -->
                                            <button type="button" 
                                                    @click="openViewModal(app)" 
                                                    title="View Full Application"
                                                    class="p-2 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 transition">
                                                <i class="fa-solid fa-eye"></i>
                                            </button>

                                            <!-- WhatsApp Message Trigger -->
                                            <a :href="'https://wa.me/' + cleanPhone(app.contact) + '?text=' + encodeURIComponent('Dear ' + app.applicant_name + ', Greetings from Jaysmrutti Foundation regarding your application for ' + app.job_title + ' (' + app.application_no + ').')"
                                               target="_blank"
                                               title="Chat with Candidate on WhatsApp"
                                               class="p-2 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-400 transition">
                                                <i class="fa-brands fa-whatsapp text-sm"></i>
                                            </a>

                                            <!-- Delete Application -->
                                            <button type="button" 
                                                    @click="confirmDelete(app)" 
                                                    title="Delete Application"
                                                    class="p-2 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 dark:bg-rose-900/30 dark:text-rose-400 transition">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>

                                        </div>
                                    </td>

                                </tr>
                            </template>

                            <template x-if="filteredApplications.length === 0">
                                <tr>
                                    <td colspan="8" class="p-12 text-center text-gray-500 dark:text-gray-400">
                                        <i class="fa-solid fa-users-viewfinder text-3xl text-gray-300 dark:text-gray-600 mb-2"></i>
                                        <p class="text-sm font-semibold">No candidate applications found.</p>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div class="px-6 py-4 bg-gray-50/50 dark:bg-gray-700/20 border-t border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row items-center justify-between gap-4" x-show="filteredApplications.length > 0">
                    <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                        <span>Showing <strong class="text-gray-800 dark:text-gray-200" x-text="(page - 1) * perPage + 1"></strong> to <strong class="text-gray-800 dark:text-gray-200" x-text="Math.min(page * perPage, filteredApplications.length)"></strong> of <strong class="text-gray-800 dark:text-gray-200" x-text="filteredApplications.length"></strong> results</span>
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
                                    :class="page === p ? 'bg-blue-600 text-white font-bold border-blue-600 shadow-xs' : 'text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700'" 
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

    <!-- CHANGE APPLICATION STATUS MODAL -->
    <div x-show="showStatusModal" 
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/70 backdrop-blur-xs"
         x-transition>

        <div class="bg-white dark:bg-gray-800 rounded-3xl max-w-lg w-full shadow-2xl overflow-hidden border border-gray-100 dark:border-gray-700"
             @click.away="showStatusModal = false">
            
            <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-teal-50/50 dark:bg-gray-800">
                <div class="flex items-center gap-2">
                    <span class="p-2 rounded-xl bg-teal-600 text-white text-sm">
                        <i class="fa-solid fa-sliders"></i>
                    </span>
                    <div>
                        <h3 class="text-base font-black text-gray-900 dark:text-white">Update Recruitment Status</h3>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400" x-text="statusData.applicant_name + ' (' + statusData.application_no + ')'"></p>
                    </div>
                </div>
                <button type="button" @click="showStatusModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form @submit.prevent="submitStatusUpdate($event)" class="p-5 space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1.5">Recruitment Status <span class="text-rose-500">*</span></label>
                    <select x-model="statusData.status" required class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white font-bold focus:outline-none focus:ring-2 focus:ring-teal-500">
                        <option value="pending">⏳ Pending Review</option>
                        <option value="reviewed">👀 Reviewed / Under Screening</option>
                        <option value="shortlisted">⭐ Shortlisted for Role</option>
                        <option value="interview_scheduled">📅 Interview Scheduled</option>
                        <option value="selected">🎉 Selected / Offer Extended</option>
                        <option value="rejected">❌ Application Rejected</option>
                    </select>
                </div>

                <!-- Conditional: Interview Date & Venue -->
                <div x-show="statusData.status === 'interview_scheduled'" class="space-y-3 p-3.5 bg-indigo-50/60 dark:bg-indigo-950/30 rounded-2xl border border-indigo-100 dark:border-indigo-900/50" x-transition>
                    <div>
                        <label class="block font-bold text-indigo-950 dark:text-indigo-200 mb-1">Interview Date & Time</label>
                        <input type="datetime-local" x-model="statusData.interview_date" class="w-full px-3 py-2 bg-white dark:bg-gray-900 border border-indigo-200 dark:border-indigo-800 rounded-xl text-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block font-bold text-indigo-950 dark:text-indigo-200 mb-1">Interview Venue / Online Meeting Link</label>
                        <input type="text" x-model="statusData.interview_venue" placeholder="e.g. State Office Room 4 or Google Meet Link" class="w-full px-3 py-2 bg-white dark:bg-gray-900 border border-indigo-200 dark:border-indigo-800 rounded-xl text-gray-900 dark:text-white">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Recruiter Notes / Feedback</label>
                    <textarea x-model="statusData.admin_notes" rows="3" placeholder="Enter interview evaluation, salary discussion, screening remarks..." class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500"></textarea>
                </div>

                <div class="pt-3 border-t border-gray-100 dark:border-gray-700 flex items-center justify-end gap-2">
                    <button type="button" @click="showStatusModal = false" class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-semibold hover:bg-gray-50 dark:hover:bg-gray-700">
                        Cancel
                    </button>
                    <button type="submit" :disabled="statusLoading" class="px-5 py-2 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-bold flex items-center gap-1.5 shadow-sm">
                        <i class="fa-solid fa-check" :class="{'fa-spin': statusLoading}"></i>
                        <span>Save Status</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- VIEW FULL APPLICATION & RESUME MODAL -->
    <div x-show="showViewModal" 
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/70 backdrop-blur-xs"
         x-transition>

        <div class="bg-white dark:bg-gray-800 rounded-3xl max-w-3xl w-full max-h-[90vh] flex flex-col shadow-2xl overflow-hidden border border-gray-100 dark:border-gray-700"
             @click.away="showViewModal = false">
            
            <div class="p-5 md:p-6 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-blue-50/40 dark:bg-gray-800">
                <div>
                    <span class="font-mono text-[11px] font-bold text-blue-700 dark:text-blue-400" x-text="viewData.application_no"></span>
                    <h3 class="text-xl font-black text-gray-900 dark:text-white mt-0.5" x-text="viewData.applicant_name"></h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5" x-text="'Applied for: ' + viewData.job_title + ' (' + viewData.job_code + ')'"></p>
                </div>
                <button type="button" @click="showViewModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <div class="p-5 md:p-6 overflow-y-auto flex-1 space-y-4 text-xs text-gray-700 dark:text-gray-300">
                
                <!-- Demographics & Contact Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-3.5 bg-gray-50 dark:bg-gray-900/50 rounded-2xl">
                    <div>
                        <span class="text-gray-400 uppercase text-[10px] block font-bold">Contact Phone</span>
                        <strong class="text-gray-900 dark:text-white font-mono" x-text="viewData.contact"></strong>
                    </div>
                    <div>
                        <span class="text-gray-400 uppercase text-[10px] block font-bold">Email Address</span>
                        <strong class="text-gray-900 dark:text-white truncate block" x-text="viewData.email || 'N/A'"></strong>
                    </div>
                    <div>
                        <span class="text-gray-400 uppercase text-[10px] block font-bold">Gender & Age</span>
                        <strong class="text-gray-900 dark:text-white" x-text="(viewData.gender || 'Male') + (viewData.age ? ' • ' + viewData.age + 'y' : '')"></strong>
                    </div>
                    <div>
                        <span class="text-gray-400 uppercase text-[10px] block font-bold">Experience</span>
                        <strong class="text-teal-600 font-bold" x-text="viewData.experience_years ? viewData.experience_years + ' Years' : 'Fresher'"></strong>
                    </div>
                </div>

                <!-- Qualification & Location -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <h4 class="font-bold text-gray-900 dark:text-white uppercase tracking-wider text-[11px] mb-1">Qualification</h4>
                        <p x-text="viewData.qualification || 'Not specified'"></p>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-900 dark:text-white uppercase tracking-wider text-[11px] mb-1">Residential Address</h4>
                        <p x-text="(viewData.address ? viewData.address + ', ' : '') + (viewData.current_city ? viewData.current_city + ', ' : '') + (viewData.district ? viewData.district + ', ' : '') + (viewData.state || '')"></p>
                    </div>
                </div>

                <!-- Cover Letter / Statement -->
                <div x-show="viewData.cover_letter">
                    <h4 class="font-bold text-gray-900 dark:text-white uppercase tracking-wider text-[11px] mb-1">Applicant Statement / Cover Letter</h4>
                    <p class="whitespace-pre-line p-3 bg-gray-50 dark:bg-gray-900 rounded-xl text-gray-600 dark:text-gray-400" x-text="viewData.cover_letter"></p>
                </div>

                <!-- Interview & Recruiter Remarks -->
                <div x-show="viewData.admin_notes || viewData.interview_date" class="p-3.5 bg-amber-50/60 dark:bg-amber-950/30 rounded-2xl border border-amber-200 dark:border-amber-900/40">
                    <h4 class="font-bold text-amber-900 dark:text-amber-200 uppercase tracking-wider text-[11px] mb-1">Recruiter Screening Notes</h4>
                    <p class="text-amber-950 dark:text-amber-100" x-text="viewData.admin_notes || 'No remarks recorded'"></p>
                    <template x-if="viewData.interview_date">
                        <div class="mt-2 pt-2 border-t border-amber-200 dark:border-amber-900/50 text-[11px]">
                            <strong>Interview:</strong> <span x-text="viewData.interview_date"></span>
                            <span x-show="viewData.interview_venue" x-text="' at ' + viewData.interview_venue"></span>
                        </div>
                    </template>
                </div>

                <!-- Resume Preview / Download -->
                <div>
                    <h4 class="font-bold text-gray-900 dark:text-white uppercase tracking-wider text-[11px] mb-2">Resume Attachment</h4>
                    <template x-if="viewData.resume_path">
                        <div class="flex items-center gap-3 p-3 bg-teal-50/50 dark:bg-gray-900 rounded-2xl border border-teal-100 dark:border-gray-700">
                            <i class="fa-solid fa-file-pdf text-3xl text-rose-500"></i>
                            <div class="flex-1 min-w-0">
                                <p class="font-bold text-gray-900 dark:text-white truncate" x-text="viewData.resume_path.split('/').pop()"></p>
                                <p class="text-[11px] text-gray-400">Candidate CV / Resume document</p>
                            </div>
                            <a :href="'../' + viewData.resume_path" target="_blank" class="px-3.5 py-1.5 bg-teal-600 hover:bg-teal-700 text-white rounded-xl font-bold text-xs flex items-center gap-1">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                <span>Open File</span>
                            </a>
                        </div>
                    </template>
                    <template x-if="!viewData.resume_path">
                        <p class="text-gray-400 italic">No resume file attached with this application.</p>
                    </template>
                </div>

            </div>

            <div class="p-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/80 flex items-center justify-between">
                <button type="button" @click="showViewModal = false; openStatusModal(viewData)" class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-xl font-bold text-xs flex items-center gap-1.5 shadow-sm">
                    <i class="fa-solid fa-sliders"></i>
                    <span>Update Status</span>
                </button>
                <button type="button" @click="showViewModal = false" class="px-4 py-2 rounded-xl bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold text-xs">
                    Close
                </button>
            </div>

        </div>
    </div>

</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('jobApplicationsManager', (initialJobId) => ({
        applicationsList: <?php echo json_encode($applications); ?>,
        
        searchQuery: '',
        filterJobId: initialJobId || '',
        filterStatus: '',
        filterCategory: '',

        showStatusModal: false,
        showViewModal: false,
        statusLoading: false,

        page: 1,
        perPage: 10,

        statusData: {
            id: '',
            applicant_name: '',
            application_no: '',
            status: 'pending',
            admin_notes: '',
            interview_date: '',
            interview_venue: ''
        },

        viewData: {},

        get totalPages() {
            return Math.ceil(this.filteredApplications.length / this.perPage) || 1;
        },

        get paginatedApplications() {
            const start = (this.page - 1) * this.perPage;
            return this.filteredApplications.slice(start, start + this.perPage);
        },

        setPage(p) {
            if (p >= 1 && p <= this.totalPages) this.page = p;
        },

        get filteredApplications() {
            return this.applicationsList.filter(app => {
                const matchesSearch = !this.searchQuery || 
                    (app.applicant_name && app.applicant_name.toLowerCase().includes(this.searchQuery.toLowerCase())) ||
                    (app.application_no && app.application_no.toLowerCase().includes(this.searchQuery.toLowerCase())) ||
                    (app.contact && app.contact.includes(this.searchQuery)) ||
                    (app.email && app.email.toLowerCase().includes(this.searchQuery.toLowerCase())) ||
                    (app.current_city && app.current_city.toLowerCase().includes(this.searchQuery.toLowerCase()));

                const matchesJob = !this.filterJobId || String(app.job_id) === String(this.filterJobId);
                const matchesStatus = !this.filterStatus || app.status === this.filterStatus;
                const matchesCategory = !this.filterCategory || app.job_category === this.filterCategory;

                return matchesSearch && matchesJob && matchesStatus && matchesCategory;
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

        formatStatusLabel(st) {
            const labels = {
                'pending': 'Pending Review',
                'reviewed': 'Reviewed',
                'shortlisted': 'Shortlisted ⭐',
                'interview_scheduled': 'Interview 📅',
                'selected': 'Selected 🎉',
                'rejected': 'Rejected ❌'
            };
            return labels[st] || st;
        },

        formatDate(d) {
            if (!d) return '-';
            const date = new Date(d);
            return date.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
        },

        cleanPhone(ph) {
            return ph ? ph.replace(/[^0-9]/g, '') : '';
        },

        openStatusModal(app) {
            this.statusData = {
                id: app.id,
                applicant_name: app.applicant_name,
                application_no: app.application_no,
                status: app.status || 'pending',
                admin_notes: app.admin_notes || '',
                interview_date: app.interview_date ? app.interview_date.replace(' ', 'T') : '',
                interview_venue: app.interview_venue || ''
            };
            this.showStatusModal = true;
        },

        openViewModal(app) {
            this.viewData = app;
            this.showViewModal = true;
        },

        async submitStatusUpdate(event) {
            this.statusLoading = true;
            const formData = new FormData();
            formData.append('csrf_token', '<?php echo htmlspecialchars($csrfToken); ?>');
            formData.append('action', 'update_application_status');
            formData.append('id', this.statusData.id);
            formData.append('status', this.statusData.status);
            formData.append('admin_notes', this.statusData.admin_notes);
            formData.append('interview_date', this.statusData.interview_date);
            formData.append('interview_venue', this.statusData.interview_venue);
            formData.append('is_ajax', '1');

            try {
                const response = await fetch('actions/job_logic.php', { method: 'POST', body: formData });
                const res = await response.json();

                if (res.success) {
                    const match = this.applicationsList.find(a => a.id == this.statusData.id);
                    if (match) {
                        match.status = this.statusData.status;
                        match.admin_notes = this.statusData.admin_notes;
                        match.interview_date = this.statusData.interview_date;
                        match.interview_venue = this.statusData.interview_venue;
                    }
                    this.showStatusModal = false;
                } else {
                    alert(res.message || 'Failed to update status.');
                }
            } catch (err) {
                console.error(err);
                alert('Network error during status update.');
            } finally {
                this.statusLoading = false;
            }
        },

        confirmDelete(app) {
            if (confirm(`Are you sure you want to delete application ${app.application_no} of '${app.applicant_name}'?`)) {
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
                actionInput.value = 'delete_application';
                form.appendChild(actionInput);

                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = app.id;
                form.appendChild(idInput);

                document.body.appendChild(form);
                form.submit();
            }
        },

        exportCSV() {
            let csv = 'App No,Applicant Name,Contact,Email,Gender,Age,Qualification,Experience Yrs,City,State,Job Code,Job Title,Category,Status,Applied Date,Interview Date,Admin Notes\n';
            this.filteredApplications.forEach(a => {
                csv += `"${a.application_no}","${a.applicant_name.replace(/"/g, '""')}","${a.contact}","${a.email || ''}","${a.gender || ''}",${a.age || ''},"${(a.qualification || '').replace(/"/g, '""')}",${a.experience_years || 0},"${a.current_city || ''}","${a.state || ''}","${a.job_code}","${a.job_title.replace(/"/g, '""')}","${a.job_category}","${a.status}","${a.applied_date}","${a.interview_date || ''}","${(a.admin_notes || '').replace(/"/g, '""')}"\n`;
            });
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'Job_Applications_' + new Date().toISOString().slice(0, 10) + '.csv';
            link.click();
        }
    }));
});
</script>

<?php require 'includes/footer.php'; ?>
