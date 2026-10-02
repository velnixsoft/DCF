<?php require 'includes/header.php'; ?>
<?php require '../config/db.php'; ?>

<?php
if (!checkRole($pdo, 'coordinator')) {
    setFlash('error', 'Unauthorized access.');
    header('Location: dashboard.php');
    exit;
}

// Run level name migration dynamically
try {
    $pdo->exec("ALTER TABLE sa_students MODIFY COLUMN level_name varchar(60) NOT NULL DEFAULT 'Student Ambassador'");
    $pdo->exec("UPDATE sa_students SET level_name = 'Student Ambassador' WHERE level_name = 'Volunteer'");
} catch (Throwable $e) {}

$students = $pdo->query("
    SELECT s.*, 
           (SELECT COUNT(*) FROM sa_students r WHERE r.referred_by_student_id = s.id AND r.status = 'Active' AND r.is_verified = 1) as ref_count,
           (SELECT SUM(amount) FROM donations d WHERE d.sa_student_id = s.id AND d.payment_status = 'Success') as collected_amount
    FROM sa_students s 
    ORDER BY s.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch activity logs for students to group them
$logs = [];
try {
    $logs = $pdo->query("SELECT id, student_id, activity_type, title, description, created_at FROM sa_activity_logs ORDER BY created_at DESC LIMIT 5000")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

$studentLogs = [];
foreach ($logs as $log) {
    $studentLogs[$log['student_id']][] = $log;
}

foreach ($students as &$student) {
    $student['activity_logs'] = $studentLogs[$student['id']] ?? [];
}
unset($student);

$badges = $pdo->query("SELECT * FROM sa_badges WHERE is_active = 1 ORDER BY badge_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$csrfToken = generateCsrfToken();
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900" x-data="studentManager">
    <?php require 'includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6">
            
            <div class="flex flex-col gap-4 mb-6">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <h3 class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-white">Student Ambassador Directory</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Review registrations, manage ranks, adjust point balances, and award achievements.</p>
                    </div>
                </div>

                <!-- Tabs & Filters -->
                <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border dark:border-gray-700 flex flex-col md:flex-row justify-between items-stretch md:items-center gap-4">
                    <div class="flex flex-wrap bg-gray-100 dark:bg-gray-900 p-1 rounded-xl w-full md:w-auto">
                        <button @click="tab = 'all'" :class="tab === 'all' ? 'bg-white shadow text-emerald-750 dark:bg-gray-800 dark:text-emerald-400 dark:shadow-md' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white'" class="flex-1 md:flex-none px-4 py-2 rounded-lg text-sm font-bold transition">
                            All Ambassadors
                        </button>
                        <button @click="tab = 'pending'" :class="tab === 'pending' ? 'bg-white shadow text-orange-650 dark:bg-gray-800 dark:text-orange-400 dark:shadow-md' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white'" class="flex-1 md:flex-none px-4 py-2 rounded-lg text-sm font-bold transition flex items-center justify-center gap-2">
                            Pending Requests 
                            <span x-show="pendingCount > 0" class="bg-orange-100 text-orange-700 text-xs px-1.5 rounded-full" x-text="pendingCount"></span>
                        </button>
                        <button @click="tab = 'rejected'" :class="tab === 'rejected' ? 'bg-white shadow text-red-650 dark:bg-gray-800 dark:text-red-400 dark:shadow-md' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white'" class="flex-1 md:flex-none px-4 py-2 rounded-lg text-sm font-bold transition">
                            Rejected Applications
                        </button>
                    </div>

                    <div class="relative w-full md:w-80">
                        <input type="text" x-model="search" placeholder="Search by name, college, city, or code..." 
                            class="w-full pl-10 pr-4 py-2.5 border rounded-xl bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400 focus:ring-2 focus:ring-emerald-500 focus:bg-white dark:focus:bg-gray-700 outline-none transition text-sm">
                        <span class="absolute left-3.5 top-3 text-gray-400">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Mobile Card View -->
            <div class="grid grid-cols-1 gap-4 lg:hidden">
                <template x-for="std in pagedItems" :key="'mobile-' + std.id">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow border dark:border-gray-700 p-4">
                        <div class="flex items-start gap-3">
                            <div class="w-11 h-11 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-base shadow-sm shrink-0">
                                <span x-text="std.full_name.charAt(0).toUpperCase()"></span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="font-bold text-gray-900 dark:text-white break-words" x-text="std.full_name"></p>
                                        <p class="text-xs text-gray-400 font-mono" x-text="std.student_no"></p>
                                    </div>
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold whitespace-nowrap" :class="getStatusClass(std.status)" x-text="std.status"></span>
                                </div>
                                <p class="text-xs text-gray-500 mt-1 break-all" x-text="std.email"></p>
                                <p class="text-xs text-gray-500" x-text="std.mobile"></p>
                                <template x-if="(std.status === 'Rejected' || std.status === 'Suspended') && std.rejection_reason">
                                    <p class="text-xs text-red-500 mt-1 break-words">
                                        <strong>Reason:</strong> <span x-text="std.rejection_reason"></span>
                                    </p>
                                </template>
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                            <div class="rounded-xl bg-gray-50 dark:bg-gray-700/40 p-3">
                                <p class="text-gray-400 uppercase font-semibold">College</p>
                                <p class="font-bold text-gray-800 dark:text-gray-100 mt-1 break-words" x-text="std.college_name"></p>
                            </div>
                            <div class="rounded-xl bg-gray-50 dark:bg-gray-700/40 p-3">
                                <p class="text-gray-400 uppercase font-semibold">Location</p>
                                <p class="font-bold text-gray-800 dark:text-gray-100 mt-1 break-words" x-text="std.city_name + ', ' + std.state_name"></p>
                            </div>
                            <div class="rounded-xl bg-gray-50 dark:bg-gray-700/40 p-3">
                                <p class="text-gray-400 uppercase font-semibold">Referral Code</p>
                                <p class="font-mono font-bold text-emerald-700 mt-1 break-all" x-text="std.referral_code"></p>
                            </div>
                            <div class="rounded-xl bg-gray-50 dark:bg-gray-700/40 p-3">
                                <p class="text-gray-400 uppercase font-semibold">Level / Points</p>
                                <p class="font-bold text-gray-800 dark:text-gray-100 mt-1">
                                    <span x-text="std.level_name"></span>
                                    <span class="text-gray-400">•</span>
                                    <span x-text="std.total_points"></span> pts
                                </p>
                            </div>
                            <div class="rounded-xl bg-gray-50 dark:bg-gray-700/40 p-3 sm:col-span-2">
                                <p class="text-gray-400 uppercase font-semibold">Collections</p>
                                <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1">
                                    <p class="text-emerald-700 font-black">₹<span x-text="Number(std.collected_amount || 0).toLocaleString('en-IN')"></span></p>
                                    <p class="text-gray-500 font-medium"><span x-text="std.ref_count"></span> active referrals</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <button @click="openViewModal(std)" class="flex-1 min-w-[120px] bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2.5 rounded-xl text-sm font-bold transition">
                                View Details
                            </button>

                            <template x-if="std.status === 'Active'">
                                <div class="contents">
                                    <button @click="openPointsModal(std)" class="flex-1 min-w-[120px] bg-amber-50 hover:bg-amber-100 text-amber-700 px-3 py-2.5 rounded-xl text-sm font-bold transition">
                                        Adjust Points
                                    </button>
                                    <button @click="openBadgeModal(std)" class="flex-1 min-w-[120px] bg-purple-50 hover:bg-purple-100 text-purple-700 px-3 py-2.5 rounded-xl text-sm font-bold transition">
                                        Award Badge
                                    </button>
                                    <a :href="'student_certificates.php?student_id=' + std.id" class="flex-1 min-w-[120px] bg-blue-50 hover:bg-blue-100 text-blue-700 px-3 py-2.5 rounded-xl text-sm font-bold transition text-center">
                                        Certificates
                                    </a>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <div x-show="filteredItems.length === 0" class="bg-white dark:bg-gray-800 rounded-2xl shadow border dark:border-gray-700 p-8 text-center text-gray-400 font-medium">
                    No student ambassadors found matching filters.
                </div>
            </div>

            <!-- Table View -->
            <div class="hidden lg:block bg-white dark:bg-gray-800 rounded-2xl shadow border dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                        <thead class="bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-bold text-xs uppercase">
                            <tr>
                                <th class="p-4">Ambassador Details</th>
                                <th class="p-4">College / City</th>
                                <th class="p-4">Referral Code</th>
                                <th class="p-4">Level / Points</th>
                                <th class="p-4">Collections</th>
                                <th class="p-4">Status</th>
                                <th class="p-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y dark:divide-gray-700">
                            <template x-for="std in pagedItems" :key="std.id">
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/50 transition">
                                    <td class="p-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-md shadow-sm">
                                                <span x-text="std.full_name.charAt(0).toUpperCase()"></span>
                                            </div>
                                            <div>
                                                <p class="font-bold text-gray-900 dark:text-white" x-text="std.full_name"></p>
                                                <p class="text-xs text-gray-400 font-mono" x-text="std.student_no"></p>
                                                <p class="text-[10px] text-gray-500" x-text="std.email + ' • ' + std.mobile"></p>
                                                <template x-if="(std.status === 'Rejected' || std.status === 'Suspended') && std.rejection_reason">
                                                    <p class="text-xs text-red-500 mt-1">
                                                        <strong>Reason:</strong> <span x-text="std.rejection_reason"></span>
                                                    </p>
                                                </template>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-4 text-xs">
                                        <p class="font-bold text-gray-800 dark:text-gray-200" x-text="std.college_name"></p>
                                        <p class="text-gray-500 dark:text-gray-400" x-text="std.city_name + ', ' + std.state_name"></p>
                                    </td>
                                    <td class="p-4 font-mono font-bold text-xs text-emerald-700 dark:text-emerald-400" x-text="std.referral_code"></td>
                                    <td class="p-4">
                                        <span class="px-2 py-0.5 rounded bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 text-xs font-semibold" x-text="std.level_name"></span>
                                        <p class="text-xs font-bold text-gray-900 dark:text-gray-200 mt-1"><span x-text="std.total_points"></span> pts</p>
                                    </td>
                                    <td class="p-4 text-xs font-bold">
                                        <p class="text-emerald-700 font-black">₹<span x-text="Number(std.collected_amount || 0).toLocaleString('en-IN')"></span></p>
                                        <p class="text-gray-400 font-medium mt-0.5"><span x-text="std.ref_count"></span> active referrals</p>
                                    </td>
                                    <td class="p-4">
                                        <span class="px-2.5 py-1 rounded-full text-xs font-bold" :class="getStatusClass(std.status)" x-text="std.status"></span>
                                    </td>
                                    <td class="p-4 text-right">
                                        <div class="inline-flex gap-1">
                                            <button @click="openViewModal(std)" class="bg-gray-100 hover:bg-gray-200 text-gray-700 p-2 rounded-lg text-xs font-bold transition" title="View Details">
                                                <i class="fa-solid fa-eye"></i>
                                            </button>
                                            
                                            <template x-if="std.status === 'Active'">
                                                <div class="inline-flex gap-1">
                                                    <button @click="openPointsModal(std)" class="bg-amber-50 hover:bg-amber-100 text-amber-700 p-2 rounded-lg text-xs font-bold transition" title="Adjust Points">
                                                        <i class="fa-solid fa-star-half-stroke"></i>
                                                    </button>
                                                    <button @click="openBadgeModal(std)" class="bg-purple-50 hover:bg-purple-100 text-purple-700 p-2 rounded-lg text-xs font-bold transition" title="Award Badge">
                                                        <i class="fa-solid fa-medal"></i>
                                                    </button>
                                                    <a :href="'student_certificates.php?student_id=' + std.id" class="bg-blue-50 hover:bg-blue-100 text-blue-700 p-2 rounded-lg text-xs font-bold transition inline-flex items-center" title="Certificates">
                                                        <i class="fa-solid fa-award"></i>
                                                    </a>
                                                </div>
                                            </template>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="filteredItems.length === 0">
                                <td colspan="7" class="p-8 text-center text-gray-400 font-medium">No student ambassadors found matching filters.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination UI Controls -->
            <div x-show="filteredItems.length > pageSize" class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-6 px-4 py-3 bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-2xl shadow-sm">
                <div class="text-xs text-gray-500 dark:text-gray-400 font-medium">
                    Showing <span class="font-bold text-gray-900 dark:text-white" x-text="filteredItems.length === 0 ? 0 : (page - 1) * pageSize + 1"></span> to 
                    <span class="font-bold text-gray-900 dark:text-white" x-text="Math.min(page * pageSize, filteredItems.length)"></span> of 
                    <span class="font-bold text-gray-900 dark:text-white" x-text="filteredItems.length"></span> entries
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="if (page > 1) page--" :disabled="page === 1" 
                        class="px-4 py-2 text-xs font-bold rounded-xl border dark:border-gray-700 bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 disabled:opacity-50 disabled:pointer-events-none transition flex items-center gap-1">
                        <i class="fa-solid fa-chevron-left"></i> Previous
                    </button>
                    <div class="flex items-center gap-1">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">Page</span>
                        <span class="text-xs font-bold text-gray-900 dark:text-white" x-text="page"></span>
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">of</span>
                        <span class="text-xs font-bold text-gray-900 dark:text-white" x-text="totalPages || 1"></span>
                    </div>
                    <button type="button" @click="if (page < totalPages) page++" :disabled="page === totalPages || totalPages === 0" 
                        class="px-4 py-2 text-xs font-bold rounded-xl border dark:border-gray-700 bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 disabled:opacity-50 disabled:pointer-events-none transition flex items-center gap-1">
                        Next <i class="fa-solid fa-chevron-right"></i>
                    </button>
                </div>
            </div>

            <!-- View / Approval Modal -->
            <div x-show="isViewModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" x-cloak>
                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl w-full max-w-lg overflow-hidden border border-gray-100 dark:border-gray-700" @click.away="isViewModalOpen = false">
                    <div class="p-6 border-b dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-900/50">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">Ambassador Details</h3>
                        <button @click="isViewModalOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-350 font-black">✕</button>
                    </div>
                    
                    <div class="p-6 space-y-4 max-h-[65vh] overflow-y-auto">
                        <div class="flex items-center gap-5 border-b dark:border-gray-700 pb-4">
                            <div class="w-16 h-16 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-black text-2xl">
                                <span x-text="activeData.full_name ? activeData.full_name.charAt(0).toUpperCase() : ''"></span>
                            </div>
                            <div>
                                <h4 class="text-xl font-extrabold text-gray-900 dark:text-white" x-text="activeData.full_name"></h4>
                                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400" x-text="'No: ' + activeData.student_no"></p>
                                <div class="flex flex-wrap gap-2 items-center mt-1">
                                    <span class="px-2 py-0.5 rounded bg-emerald-50 border border-emerald-100 text-emerald-700 text-[10px] font-bold uppercase tracking-wider" x-text="activeData.level_name"></span>
                                    <template x-if="(activeData.status === 'Rejected' || activeData.status === 'Suspended') && activeData.rejection_reason">
                                        <span class="text-xs text-red-500 font-semibold" x-text="'Reason: ' + activeData.rejection_reason"></span>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Premium Fundraising & Referral Performance Stats -->
                        <div class="grid grid-cols-2 gap-4 p-4 bg-emerald-50/50 dark:bg-emerald-950/20 rounded-2xl border border-emerald-100/50 dark:border-emerald-900/30">
                            <div>
                                <label class="text-[10px] font-bold text-emerald-800/60 dark:text-emerald-400/60 uppercase tracking-wider block">Total Funds Raised</label>
                                <p class="text-lg font-black text-emerald-700 dark:text-emerald-400 mt-0.5">₹<span x-text="Number(activeData.collected_amount || 0).toLocaleString('en-IN')"></span></p>
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-emerald-800/60 dark:text-emerald-400/60 uppercase tracking-wider block">Active Referrals</label>
                                <p class="text-lg font-black text-emerald-700 dark:text-emerald-400 mt-0.5"><span x-text="activeData.ref_count || 0"></span> referred</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                            <div>
                                <label class="text-gray-400 uppercase font-semibold">Email</label>
                                <p class="font-bold text-gray-900 dark:text-white mt-0.5" x-text="activeData.email"></p>
                            </div>
                            <div>
                                <label class="text-gray-400 uppercase font-semibold">Mobile</label>
                                <p class="font-bold text-gray-900 dark:text-white mt-0.5" x-text="activeData.mobile"></p>
                            </div>
                            <div>
                                <label class="text-gray-400 uppercase font-semibold">Gender</label>
                                <p class="font-bold text-gray-900 dark:text-white mt-0.5" x-text="activeData.gender || 'N/A'"></p>
                            </div>
                            <div>
                                <label class="text-gray-400 uppercase font-semibold">Year of Study</label>
                                <p class="font-bold text-gray-900 dark:text-white mt-0.5" x-text="activeData.year_of_study"></p>
                            </div>
                            <div class="col-span-2">
                                <label class="text-gray-400 uppercase font-semibold">College / Institution</label>
                                <p class="font-bold text-gray-900 dark:text-white mt-0.5" x-text="activeData.college_name"></p>
                            </div>
                            <div>
                                <label class="text-gray-400 uppercase font-semibold">Department</label>
                                <p class="font-bold text-gray-900 dark:text-white mt-0.5" x-text="activeData.department_name"></p>
                            </div>
                            <div>
                                <label class="text-gray-400 uppercase font-semibold">Location</label>
                                <p class="font-bold text-gray-900 dark:text-white mt-0.5" x-text="activeData.city_name + ', ' + activeData.state_name"></p>
                            </div>
                            <div class="col-span-2">
                                <label class="text-gray-400 uppercase font-semibold">Address</label>
                                <p class="font-medium text-gray-800 dark:text-gray-255 mt-0.5" x-text="activeData.address || '—'"></p>
                            </div>
                        </div>

                        <!-- History Trail -->
                        <div class="border-t dark:border-gray-700 pt-4">
                            <h5 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2.5">Activity & Change History</h5>
                            <div class="space-y-2 max-h-40 overflow-y-auto pr-1">
                                <template x-for="log in activeData.activity_logs" :key="log.id">
                                    <div class="bg-gray-50 dark:bg-gray-900/30 p-2.5 rounded-xl border dark:border-gray-700/50 flex items-start justify-between gap-3 text-xs">
                                        <div class="min-w-0">
                                            <p class="font-bold text-gray-800 dark:text-gray-200" x-text="log.title"></p>
                                            <p class="text-gray-500 mt-0.5 break-words" x-text="log.description"></p>
                                        </div>
                                        <span class="text-[10px] text-gray-400 font-medium whitespace-nowrap" x-text="formatDateTime(log.created_at)"></span>
                                    </div>
                                </template>
                                <template x-if="!activeData.activity_logs || activeData.activity_logs.length === 0">
                                    <p class="text-xs text-gray-400 italic">No history logged for this student.</p>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="p-4 bg-gray-50 dark:bg-gray-900/50 flex justify-end gap-2 border-t dark:border-gray-700">
                        <!-- Pending Actions -->
                        <template x-if="activeData.status === 'Pending'">
                            <div class="flex flex-col sm:flex-row gap-2 w-full">
                                <button @click="openSuspendModal(activeData, 'Rejected')" class="flex-1 bg-white dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 border dark:border-gray-600 text-red-600 dark:text-red-400 font-bold py-2.5 rounded-xl text-sm transition">
                                    Reject Application
                                </button>
                                <form action="actions/student_logic.php" method="POST" class="flex-1">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <input type="hidden" name="id" :value="activeData.id">
                                    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 rounded-xl text-sm transition shadow-md shadow-emerald-500/10">
                                        Approve & Verify
                                    </button>
                                </form>
                            </div>
                        </template>

                        <!-- Active Actions -->
                        <template x-if="activeData.status === 'Active'">
                            <div class="w-full">
                                <button @click="openSuspendModal(activeData, 'Suspended')" class="w-full bg-red-50 hover:bg-red-100 text-red-700 font-bold py-2.5 rounded-xl text-sm transition">
                                    Suspend Ambassador
                                </button>
                            </div>
                        </template>

                        <!-- Suspended Actions -->
                        <template x-if="activeData.status === 'Suspended'">
                            <form action="actions/student_logic.php" method="POST" class="w-full">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="id" :value="activeData.id">
                                <input type="hidden" name="status" value="Active">
                                <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 rounded-xl text-sm transition">
                                    Reactivate Ambassador
                                </button>
                            </form>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Points Adjustment Modal -->
            <div x-show="isPointsModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" x-cloak>
                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl w-full max-w-sm overflow-hidden border dark:border-gray-700" @click.away="closePointsModal()">
                    <div class="p-6 border-b dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 flex justify-between items-center">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Adjust Points Balance</h3>
                        <button type="button" @click="closePointsModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">✕</button>
                    </div>
                    <form action="actions/student_logic.php" method="POST" class="p-6 space-y-4">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="action" value="adjust_points">
                        <input type="hidden" name="id" :value="activeData.id">

                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Student: <b class="text-gray-800 dark:text-white" x-text="activeData.full_name"></b></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Current Points: <b class="text-gray-800 dark:text-white"><span x-text="activeData.total_points"></span> pts</b></p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Adjustment Value *</label>
                            <input type="number" name="points" required x-model="pointsValue" placeholder="e.g. 50 or -25"
                                class="w-full px-4 py-2.5 border dark:border-gray-600 rounded-xl outline-none focus:ring-2 focus:ring-emerald-500 text-sm bg-white text-gray-900 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400">
                            <span class="block text-[10px] text-gray-400 mt-1">Input positive to add points, negative to deduct.</span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Reason / Description *</label>
                            <input type="text" name="reason" required x-model="pointsReason" placeholder="e.g. Conducted Society Campaign"
                                class="w-full px-4 py-2.5 border dark:border-gray-600 rounded-xl outline-none focus:ring-2 focus:ring-emerald-500 text-sm bg-white text-gray-900 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400">
                        </div>

                        <div class="pt-4 flex justify-end gap-2 border-t dark:border-gray-700">
                            <button type="button" @click="closePointsModal()" class="px-4 py-2 border dark:border-gray-600 rounded-lg text-sm text-gray-600 dark:text-gray-300 font-bold bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-amber-600 text-white rounded-lg text-sm font-bold shadow hover:bg-amber-700">Submit Adjustment</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Award Badge Modal -->
            <div x-show="isBadgeModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" x-cloak>
                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl w-full max-w-sm overflow-hidden border dark:border-gray-700" @click.away="closeBadgeModal()">
                    <div class="p-6 border-b dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 flex justify-between items-center">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Award Achievement Badge</h3>
                        <button type="button" @click="closeBadgeModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">✕</button>
                    </div>
                    <form action="actions/student_logic.php" method="POST" class="p-6 space-y-4">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="action" value="award_badge">
                        <input type="hidden" name="id" :value="activeData.id">

                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Student: <b class="text-gray-800 dark:text-white" x-text="activeData.full_name"></b></p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Select Badge *</label>
                            <select name="badge_id" required x-model="badgeId" class="w-full px-4 py-2.5 border dark:border-gray-600 rounded-xl outline-none focus:ring-2 focus:ring-emerald-500 text-sm bg-white text-gray-900 dark:bg-gray-700 dark:text-white">
                                <option value="">Select Badge</option>
                                <?php foreach ($badges as $badge): ?>
                                    <option value="<?php echo $badge['id']; ?>"><?php echo htmlspecialchars($badge['badge_name']); ?> — <?php echo htmlspecialchars($badge['description']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Award Citation / Notes</label>
                            <input type="text" name="notes" x-model="badgeNotes" placeholder="e.g. Excellent presentation in XYZ Seminar"
                                class="w-full px-4 py-2.5 border dark:border-gray-600 rounded-xl outline-none focus:ring-2 focus:ring-emerald-500 text-sm bg-white text-gray-900 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400">
                        </div>

                        <div class="pt-4 flex justify-end gap-2 border-t dark:border-gray-700">
                            <button type="button" @click="closeBadgeModal()" class="px-4 py-2 border dark:border-gray-600 rounded-lg text-sm text-gray-600 dark:text-gray-300 font-bold bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg text-sm font-bold shadow hover:bg-purple-700">Award Badge</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Suspend Reason Modal -->
            <div x-show="isSuspendModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" x-cloak>
                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl w-full max-w-sm overflow-hidden border dark:border-gray-700" @click.away="isSuspendModalOpen = false">
                    <div class="p-6 border-b dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 flex justify-between items-center">
                        <h3 class="text-lg font-bold text-gray-900 text-red-650 dark:text-red-400" x-text="suspendTargetStatus === 'Rejected' ? 'Reject Application?' : 'Suspend Ambassador?'"></h3>
                        <button @click="isSuspendModalOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">✕</button>
                    </div>
                    <form action="actions/student_logic.php" method="POST" class="p-6 space-y-4">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="id" :value="activeData.id">
                        <input type="hidden" name="status" :value="suspendTargetStatus">

                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            <span x-show="suspendTargetStatus === 'Rejected'">Provide a reason for rejecting <b class="text-gray-800 dark:text-white" x-text="activeData.full_name"></b>'s application.</span>
                            <span x-show="suspendTargetStatus !== 'Rejected'">Provide a reason for suspending <b class="text-gray-800 dark:text-white" x-text="activeData.full_name"></b>.</span>
                        </p>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Reason / Description *</label>
                            <input type="text" name="reason" required :placeholder="suspendTargetStatus === 'Rejected' ? 'e.g. Incomplete details or invalid credentials' : 'e.g. Fake activity submission or misconduct'"
                                class="w-full px-4 py-2.5 border dark:border-gray-600 rounded-xl outline-none focus:ring-2 focus:ring-red-500 text-sm bg-white text-gray-900 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400">
                        </div>

                        <div class="pt-4 flex justify-end gap-2 border-t dark:border-gray-700">
                            <button type="button" @click="isSuspendModalOpen = false" class="px-4 py-2 border dark:border-gray-600 rounded-lg text-sm text-gray-600 dark:text-gray-300 font-bold bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-bold shadow hover:bg-red-700" x-text="suspendTargetStatus === 'Rejected' ? 'Confirm Rejection' : 'Confirm Suspension'"></button>
                        </div>
                    </form>
                </div>
            </div>

        </main>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('studentManager', () => ({
        items: <?php echo json_encode($students); ?>,
        search: '',
        tab: '<?php echo isset($_GET['status']) && strtolower($_GET['status']) === 'pending' ? 'pending' : 'all'; ?>',
        page: 1,
        pageSize: 10,
        isViewModalOpen: false,
        isPointsModalOpen: false,
        isBadgeModalOpen: false,
        isSuspendModalOpen: false,
        activeData: {},
        suspendTargetStatus: 'Suspended',
        pointsValue: '',
        pointsReason: '',
        badgeId: '',
        badgeNotes: '',

        init() {
            this.$watch('search', () => this.page = 1);
            this.$watch('tab', () => this.page = 1);
        },

        get filteredItems() {
            const query = this.search.toLowerCase();
            const searched = this.items.filter(item => 
                (item.full_name && item.full_name.toLowerCase().includes(query)) || 
                (item.student_no && item.student_no.toLowerCase().includes(query)) ||
                (item.college_name && item.college_name.toLowerCase().includes(query)) ||
                (item.city_name && item.city_name.toLowerCase().includes(query)) ||
                (item.referral_code && item.referral_code.toLowerCase().includes(query))
            );
            if (this.tab === 'pending') {
                return searched.filter(item => item.status && item.status.toLowerCase() === 'pending');
            }
            if (this.tab === 'rejected') {
                return searched.filter(item => item.status && item.status.toLowerCase() === 'rejected');
            }
            return searched.filter(item => item.status && (item.status.toLowerCase() === 'active' || item.status.toLowerCase() === 'suspended'));
        },

        get pagedItems() {
            const start = (this.page - 1) * this.pageSize;
            return this.filteredItems.slice(start, start + this.pageSize);
        },

        get totalPages() {
            return Math.ceil(this.filteredItems.length / this.pageSize);
        },

        get pendingCount() {
            return this.items.filter(item => item.status && item.status.toLowerCase() === 'pending').length;
        },

        openViewModal(student) {
            this.activeData = student;
            this.isViewModalOpen = true;
        },

        openPointsModal(student) {
            this.activeData = student;
            this.pointsValue = '';
            this.pointsReason = '';
            this.isPointsModalOpen = true;
        },

        closePointsModal() {
            this.isPointsModalOpen = false;
            this.pointsValue = '';
            this.pointsReason = '';
        },

        openBadgeModal(student) {
            this.activeData = student;
            this.badgeId = '';
            this.badgeNotes = '';
            this.isBadgeModalOpen = true;
        },

        closeBadgeModal() {
            this.isBadgeModalOpen = false;
            this.badgeId = '';
            this.badgeNotes = '';
        },

        openSuspendModal(student, targetStatus) {
            this.activeData = student;
            this.suspendTargetStatus = targetStatus;
            this.isSuspendModalOpen = true;
            this.isViewModalOpen = false;
        },

        getStatusClass(status) {
            if (!status) return 'bg-gray-100 text-gray-600';
            const s = status.toLowerCase();
            return {
                'bg-emerald-100 text-emerald-800': s === 'active',
                'bg-orange-100 text-orange-850': s === 'pending',
                'bg-red-100 text-red-800': s === 'rejected',
                'bg-rose-100 text-rose-800': s === 'suspended',
                'bg-gray-100 text-gray-600': s === 'inactive'
            };
        },

        formatDateTime(dateStr) {
            if (!dateStr) return '';
            const parts = dateStr.split(' ');
            const dateParts = parts[0].split('-').reverse().join('/');
            const timeParts = parts[1] ? parts[1].substring(0, 5) : '';
            return dateParts + (timeParts ? ' ' + timeParts : '');
        }
    }));
});
</script>

<?php require 'includes/footer.php'; ?>
