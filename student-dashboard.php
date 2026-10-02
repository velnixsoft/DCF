<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/student/portal_helpers.php';
require_once __DIR__ . '/includes/student/leaderboard_cache.php';

if (empty($_SESSION['csrf_token'])) {
    generateCsrfToken();
}
$dashboardCsrf = $_SESSION['csrf_token'];

$student = student_portal_require_student($pdo, 'dashboard');
$studentId = (int)$student['id'];

// 1. Dynamic Rank Calculations
// National Rank
$stmt = $pdo->prepare("SELECT COUNT(*) + 1 FROM sa_students WHERE total_points > ? AND status = 'Active'");
$stmt->execute([$student['total_points']]);
$nationalRank = (int)$stmt->fetchColumn();

// City Rank
$stmt = $pdo->prepare("SELECT COUNT(*) + 1 FROM sa_students WHERE total_points > ? AND city_name = ? AND status = 'Active'");
$stmt->execute([$student['total_points'], $student['city_name']]);
$cityRank = (int)$stmt->fetchColumn();

// Campus Rank
$stmt = $pdo->prepare("SELECT COUNT(*) + 1 FROM sa_students WHERE total_points > ? AND college_name = ? AND status = 'Active'");
$stmt->execute([$student['total_points'], $student['college_name']]);
$campusRank = (int)$stmt->fetchColumn();

// State Rank
$stmt = $pdo->prepare("SELECT COUNT(*) + 1 FROM sa_students WHERE total_points > ? AND state_name = ? AND status = 'Active'");
$stmt->execute([$student['total_points'], $student['state_name']]);
$stateRank = (int)$stmt->fetchColumn();

// 2. Fetch Referrals Count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM sa_students WHERE referred_by_student_id = ? AND status = 'Active'");
$stmt->execute([$studentId]);
$referralCount = (int)$stmt->fetchColumn();

// 3. Fetch Total Donations Collected
$stmt = $pdo->prepare("SELECT SUM(amount) FROM donations WHERE sa_student_id = ? AND payment_status = 'Success'");
$stmt->execute([$studentId]);
$donationsCollected = (float)($stmt->fetchColumn() ?: 0.0);

// 3b. Fetch Fundraising History
$stmt = $pdo->prepare("SELECT d.*, p.title AS project_name FROM donations d LEFT JOIN projects p ON d.project_id = p.id WHERE d.sa_student_id = ? ORDER BY d.created_at DESC");
$stmt->execute([$studentId]);
$fundraisingHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Check Daily Attendance Status
$stmt = $pdo->prepare("SELECT COUNT(*) FROM sa_attendance_logs WHERE student_id = ? AND attendance_date = CURDATE()");
$stmt->execute([$studentId]);
$attendanceMarkedToday = $stmt->fetchColumn() > 0;

// 5. Fetch Badges Earned
$stmt = $pdo->prepare("
    SELECT b.badge_name, b.description, b.icon_class, sb.awarded_at 
    FROM sa_student_badges sb
    JOIN sa_badges b ON sb.badge_id = b.id
    WHERE sb.student_id = ?
");
$stmt->execute([$studentId]);
$badges = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 6. Fetch Certificates
$stmt = $pdo->prepare("SELECT * FROM sa_certificates WHERE student_id = ? AND status = 'Generated' ORDER BY issued_at DESC");
$stmt->execute([$studentId]);
$certificates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 7. Get UPI Settings and construct Pay QR Code
$stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'upi_vpa'");
$upiVpa = $stmt ? $stmt->fetchColumn() : 'suchi@upi';
$stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'upi_payee_name'");
$upiPayeeName = $stmt ? $stmt->fetchColumn() : 'Suchi NGO';

$upiNote = "INT-" . $student['student_no'] . "-" . str_replace(' ', '', (string)$student['city_name']);
$upiPayUrl = "upi://pay?pa=" . urlencode($upiVpa) . "&pn=" . urlencode($upiPayeeName) . "&tn=" . urlencode($upiNote) . "&cu=INR";
$qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($upiPayUrl);

// 8. Levels Map for Targets
$levelsMap = [
    'Volunteer' => ['min' => 0, 'next_min' => 50, 'next_level' => 'Community Intern'],
    'Community Intern' => ['min' => 50, 'next_min' => 150, 'next_level' => 'Senior Intern'],
    'Senior Intern' => ['min' => 150, 'next_min' => 400, 'next_level' => 'Campus Ambassador'],
    'Campus Ambassador' => ['min' => 400, 'next_min' => 800, 'next_level' => 'Team Leader'],
    'Team Leader' => ['min' => 800, 'next_min' => 1500, 'next_level' => 'City Coordinator'],
    'City Coordinator' => ['min' => 1500, 'next_min' => 3000, 'next_level' => 'State Coordinator'],
    'State Coordinator' => ['min' => 3000, 'next_min' => 99999, 'next_level' => 'Max Level']
];
$currentLevel = $student['level_name'] === 'State Leadership' ? 'State Coordinator' : $student['level_name'];
$levelInfo = $levelsMap[$currentLevel] ?? $levelsMap['Volunteer'];
$pointsToNext = max(0, $levelInfo['next_min'] - $student['total_points']);
$pointsRange = $levelInfo['next_min'] - $levelInfo['min'];
$pointsProgress = $student['total_points'] - $levelInfo['min'];
$levelPercent = min(100, max(0, round(($pointsProgress / $pointsRange) * 100)));

// 9. Fetch Tasks
$stmt = $pdo->prepare("SELECT * FROM sa_tasks WHERE status = 'Active' ORDER BY due_date ASC");
$stmt->execute();
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Task Submission statuses
$stmt = $pdo->prepare("SELECT task_id, status, proof_path, notes FROM sa_task_submissions WHERE student_id = ?");
$stmt->execute([$studentId]);
$submissions = $stmt->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);

// 10. Fetch Vendor Leads
$stmt = $pdo->prepare("SELECT * FROM sa_vendor_leads WHERE student_id = ? ORDER BY created_at DESC");
$stmt->execute([$studentId]);
$vendorLeads = $stmt->fetchAll(PDO::FETCH_ASSOC);

$vendorLeadLabels = [
    'vendor_meeting' => 'Vendor Meeting',
    'vendor_onboarding' => 'Vendor Onboarding',
    'premium_vendor_onboarding' => 'Premium Vendor Onboarding',
    'sponsor_partnership' => 'Sponsor Lead',
    'business_partnership' => 'Sponsor Lead',
    'college_partnership' => 'College Partnership',
];

// Fetch Onboarded Partners
$stmt = $pdo->prepare("SELECT * FROM sa_partners WHERE referred_by_student_id = ? ORDER BY created_at DESC");
$stmt->execute([$studentId]);
$onboardedPartners = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Google Maps API Key
$stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'google_maps_api_key'");
$googleMapsApiKey = $stmt ? $stmt->fetchColumn() : '';

// 11. Fetch Leaderboards (cached for 5 minutes)
$nationalLeaderboard = StudentLeaderboardCache::getNational($pdo, 10);
$cityLeaderboard = StudentLeaderboardCache::getScoped($pdo, 'city', (string)($student['city_name'] ?? ''), 10);
$campusLeaderboard = StudentLeaderboardCache::getScoped($pdo, 'campus', (string)($student['college_name'] ?? ''), 10);

student_portal_render_shell_start($pdo, $student, 'Dashboard', 'dashboard');
?>

<div x-data="dashboardTabs()" x-cloak class="space-y-8 font-sans">
    
    <!-- Premium Header Profile Card -->
    <div class="bg-white rounded-[2rem] shadow-xl border border-slate-100 p-6 md:p-8 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-80 h-80 bg-emerald-500/5 rounded-full -mr-28 -mt-28 blur-3xl"></div>
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6 relative z-10">
            <div class="flex items-center gap-5">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-emerald-500 to-green-600 text-white flex items-center justify-center font-black text-2xl shadow-lg shadow-emerald-500/20 border border-emerald-400/20">
                    <?php echo strtoupper(substr((string)($student['full_name'] ?? 'S'), 0, 1)); ?>
                </div>
                <div>
                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-50 border border-emerald-100 text-emerald-700 text-[10px] font-extrabold uppercase tracking-wider">
                        <?php echo htmlspecialchars($currentLevel); ?>
                    </span>
                    <h1 class="text-2xl font-black text-slate-900 mt-1.5"><?php echo htmlspecialchars((string)$student['full_name']); ?></h1>
                    <p class="text-xs text-slate-400 font-semibold mt-1">ID: <span class="font-mono text-slate-700 font-bold"><?php echo htmlspecialchars((string)$student['student_no']); ?></span> | College: <span class="text-slate-600 font-bold"><?php echo htmlspecialchars((string)($student['college_name'] ?? '—')); ?></span></p>
                </div>
            </div>

            <div class="flex flex-wrap gap-2.5">
                <button type="button" @click="markAttendance" :disabled="attendanceMarked"
                    :class="attendanceMarked ? 'bg-slate-50 text-slate-400 border border-slate-200' : 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-lg shadow-emerald-600/20 active:scale-[0.98] transition-all'"
                    class="px-5 py-3 rounded-2xl font-bold flex items-center gap-2 text-xs disabled:cursor-not-allowed">
                    <i class="fa-solid" :class="attendanceMarked ? 'fa-calendar-check text-emerald-600' : 'fa-check'"></i>
                    <span x-text="attendanceMarked ? 'Attendance Marked' : 'Mark Daily Attendance'"></span>
                </button>
                <a href="student-profile.php" class="border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 px-5 py-3 rounded-2xl font-bold text-xs shadow-sm transition">
                    Edit Profile
                </a>
            </div>
        </div>

        <!-- Level Promotion Progress -->
        <div class="mt-8 border-t border-slate-100 pt-6">
            <div class="flex justify-between text-xs font-bold mb-2">
                <span class="text-slate-500 uppercase tracking-wider">Level Promotion Progress</span>
                <span class="text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full"><?php echo $student['total_points']; ?> / <?php echo $levelInfo['next_min']; ?> Points</span>
            </div>
            <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden border border-slate-100 p-[1px]">
                <div class="bg-gradient-to-r from-emerald-500 to-emerald-600 h-full rounded-full transition-all duration-500 shadow-inner" style="width: <?php echo $levelPercent; ?>%"></div>
            </div>
            <div class="flex justify-between text-[10px] text-slate-400 mt-2 font-bold uppercase tracking-wider">
                <span><?php echo $currentLevel; ?></span>
                <?php if ($levelInfo['next_level'] !== 'Max Level'): ?>
                    <span>Next level: <b class="text-slate-700"><?php echo $levelInfo['next_level']; ?></b> (in <?php echo $pointsToNext; ?> pts)</span>
                <?php else: ?>
                    <span>Maximum level reached!</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Quick Stats Grid -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-3xl border border-slate-100 p-5 shadow-sm hover:shadow-md transition duration-200 relative overflow-hidden group">
            <div class="absolute -right-5 -bottom-5 w-16 h-16 bg-amber-500/5 rounded-full group-hover:scale-125 transition-transform duration-300"></div>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Total Points</p>
            <div class="flex items-center justify-between mt-3">
                <h3 class="text-2xl font-black text-slate-900"><?php echo $student['total_points']; ?></h3>
                <div class="w-9 h-9 rounded-xl bg-amber-50 border border-amber-100 text-amber-600 flex items-center justify-center text-sm shadow-sm">
                    <i class="fa-solid fa-star"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-3xl border border-slate-100 p-5 shadow-sm hover:shadow-md transition duration-200 relative overflow-hidden group">
            <div class="absolute -right-5 -bottom-5 w-16 h-16 bg-blue-500/5 rounded-full group-hover:scale-125 transition-transform duration-300"></div>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">National Rank</p>
            <div class="flex items-center justify-between mt-3">
                <h3 class="text-2xl font-black text-slate-900">#<?php echo $nationalRank; ?></h3>
                <div class="w-9 h-9 rounded-xl bg-blue-50 border border-blue-100 text-blue-600 flex items-center justify-center text-sm shadow-sm">
                    <i class="fa-solid fa-globe"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-3xl border border-slate-100 p-5 shadow-sm hover:shadow-md transition duration-200 relative overflow-hidden group">
            <div class="absolute -right-5 -bottom-5 w-16 h-16 bg-purple-500/5 rounded-full group-hover:scale-125 transition-transform duration-300"></div>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Referrals</p>
            <div class="flex items-center justify-between mt-3">
                <h3 class="text-2xl font-black text-slate-900"><?php echo $referralCount; ?></h3>
                <div class="w-9 h-9 rounded-xl bg-purple-50 border border-purple-100 text-purple-600 flex items-center justify-center text-sm shadow-sm">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-3xl border border-slate-100 p-5 shadow-sm hover:shadow-md transition duration-200 relative overflow-hidden group">
            <div class="absolute -right-5 -bottom-5 w-16 h-16 bg-green-500/5 rounded-full group-hover:scale-125 transition-transform duration-300"></div>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Funds Raised</p>
            <div class="flex items-center justify-between mt-3">
                <h3 class="text-xl font-extrabold text-emerald-600">₹<?php echo number_format($donationsCollected, 2); ?></h3>
                <div class="w-9 h-9 rounded-xl bg-green-50 border border-green-100 text-green-600 flex items-center justify-center text-sm shadow-sm">
                    <i class="fa-solid fa-hand-holding-dollar"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Dashboard Content Workspace Tabs -->
    <div class="bg-white rounded-[2rem] shadow-lg border border-slate-100 overflow-hidden">
        <!-- Scrollable Tab Header -->
        <div class="border-b border-slate-100 bg-slate-50/50 p-3">
            <nav class="flex flex-nowrap overflow-x-auto gap-2 p-1 custom-scrollbar scroll-smooth">
                <button type="button" @click="activeTab = 'tasks'" 
                    :class="activeTab === 'tasks' ? 'bg-gradient-to-r from-emerald-500 to-emerald-600 text-white shadow-md shadow-emerald-500/20' : 'bg-white border border-slate-200/60 text-slate-600 hover:bg-slate-50'" 
                    class="px-5 py-3 rounded-2xl text-xs font-bold transition flex items-center gap-2 flex-shrink-0 whitespace-nowrap">
                    <i class="fa-solid fa-list-check"></i> Tasks & Campaigns
                </button>
                <button type="button" @click="activeTab = 'vendorLeads'" 
                    :class="activeTab === 'vendorLeads' ? 'bg-gradient-to-r from-emerald-500 to-emerald-600 text-white shadow-md shadow-emerald-500/20' : 'bg-white border border-slate-200/60 text-slate-600 hover:bg-slate-50'" 
                    class="px-5 py-3 rounded-2xl text-xs font-bold transition flex items-center gap-2 flex-shrink-0 whitespace-nowrap">
                    <i class="fa-solid fa-handshake-angle"></i> Vendor Leads (<?php echo count($vendorLeads); ?>)
                </button>
                <button type="button" @click="activeTab = 'payment'" 
                    :class="activeTab === 'payment' ? 'bg-gradient-to-r from-emerald-500 to-emerald-600 text-white shadow-md shadow-emerald-500/20' : 'bg-white border border-slate-200/60 text-slate-600 hover:bg-slate-50'" 
                    class="px-5 py-3 rounded-2xl text-xs font-bold transition flex items-center gap-2 flex-shrink-0 whitespace-nowrap">
                    <i class="fa-solid fa-qrcode"></i> Donation QR
                </button>
                <button type="button" @click="activeTab = 'fundraisingHistory'" 
                    :class="activeTab === 'fundraisingHistory' ? 'bg-gradient-to-r from-emerald-500 to-emerald-600 text-white shadow-md shadow-emerald-500/20' : 'bg-white border border-slate-200/60 text-slate-600 hover:bg-slate-50'" 
                    class="px-5 py-3 rounded-2xl text-xs font-bold transition flex items-center gap-2 flex-shrink-0 whitespace-nowrap">
                    <i class="fa-solid fa-hand-holding-dollar"></i> Fundraising History (<?php echo count($fundraisingHistory); ?>)
                </button>
                <button type="button" @click="activeTab = 'leaderboard'" 
                    :class="activeTab === 'leaderboard' ? 'bg-gradient-to-r from-emerald-500 to-emerald-600 text-white shadow-md shadow-emerald-500/20' : 'bg-white border border-slate-200/60 text-slate-600 hover:bg-slate-50'" 
                    class="px-5 py-3 rounded-2xl text-xs font-bold transition flex items-center gap-2 flex-shrink-0 whitespace-nowrap">
                    <i class="fa-solid fa-ranking-star"></i> Leaderboards
                </button>
                <button type="button" @click="activeTab = 'badges'" 
                    :class="activeTab === 'badges' ? 'bg-gradient-to-r from-emerald-500 to-emerald-600 text-white shadow-md shadow-emerald-500/20' : 'bg-white border border-slate-200/60 text-slate-600 hover:bg-slate-50'" 
                    class="px-5 py-3 rounded-2xl text-xs font-bold transition flex items-center gap-2 flex-shrink-0 whitespace-nowrap">
                    <i class="fa-solid fa-medal"></i> Badges (<?php echo count($badges); ?>)
                </button>
                <button type="button" @click="activeTab = 'certificates'" 
                    :class="activeTab === 'certificates' ? 'bg-gradient-to-r from-emerald-500 to-emerald-600 text-white shadow-md shadow-emerald-500/20' : 'bg-white border border-slate-200/60 text-slate-600 hover:bg-slate-50'" 
                    class="px-5 py-3 rounded-2xl text-xs font-bold transition flex items-center gap-2 flex-shrink-0 whitespace-nowrap">
                    <i class="fa-solid fa-award"></i> Certificates (<?php echo count($certificates); ?>)
                </button>
                <button type="button" @click="activeTab = 'donationAchievements'" 
                    :class="activeTab === 'donationAchievements' ? 'bg-gradient-to-r from-emerald-500 to-emerald-600 text-white shadow-md shadow-emerald-500/20' : 'bg-white border border-slate-200/60 text-slate-600 hover:bg-slate-50'" 
                    class="px-5 py-3 rounded-2xl text-xs font-bold transition flex items-center gap-2 flex-shrink-0 whitespace-nowrap">
                    <i class="fa-solid fa-hand-holding-heart"></i> Donation Achievements
                </button>
                <button type="button" @click="activeTab = 'attendance'" 
                    :class="activeTab === 'attendance' ? 'bg-gradient-to-r from-emerald-500 to-emerald-600 text-white shadow-md shadow-emerald-500/20' : 'bg-white border border-slate-200/60 text-slate-600 hover:bg-slate-50'" 
                    class="px-5 py-3 rounded-2xl text-xs font-bold transition flex items-center gap-2 flex-shrink-0 whitespace-nowrap">
                    <i class="fa-solid fa-calendar-check"></i> Attendance
                </button>
                <button type="button" @click="activeTab = 'onboardPartner'" 
                    :class="activeTab === 'onboardPartner' ? 'bg-gradient-to-r from-emerald-500 to-emerald-600 text-white shadow-md shadow-emerald-500/20' : 'bg-white border border-slate-200/60 text-slate-600 hover:bg-slate-50'" 
                    class="px-5 py-3 rounded-2xl text-xs font-bold transition flex items-center gap-2 flex-shrink-0 whitespace-nowrap">
                    <i class="fa-solid fa-store"></i> Onboard Partner (<?php echo count($onboardedPartners); ?>)
                </button>
            </nav>
        </div>

        <div class="p-6 md:p-8">
                
                <!-- Tab: Tasks -->
                <div x-show="activeTab === 'tasks'" class="space-y-6">
                    <div class="flex items-center justify-between border-b pb-4 border-gray-100">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">Campaign Activities</h2>
                            <p class="text-sm text-gray-500">Submit screenshots or proofs of completion to earn points.</p>
                        </div>
                    </div>

                    <?php if (empty($tasks)): ?>
                        <div class="text-center py-10 text-gray-400">
                            <i class="fa-solid fa-clipboard-question text-4xl mb-3"></i>
                            <p>No active tasks or campaigns assigned at this time.</p>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 gap-4">
                            <?php $taskIdx = 0; foreach ($tasks as $task): 
                                $taskId = (int)$task['id'];
                                $subStatus = $submissions[$taskId]['status'] ?? 'Pending';
                                $subNotes = $submissions[$taskId]['notes'] ?? '';
                            ?>
                                <div x-show="isPageItem(<?php echo $taskIdx++; ?>, 'tasks')" class="border border-gray-200 rounded-2xl p-5 hover:border-emerald-300 transition duration-300">
                                    <div class="flex flex-col sm:flex-row items-start justify-between gap-4">
                                        <div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-bold">
                                                    +<?php echo $task['points_reward']; ?> Points
                                                </span>
                                                <?php if ($task['campaign_name']): ?>
                                                    <span class="px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-700 text-xs font-bold">
                                                        <?php echo htmlspecialchars($task['campaign_name']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <h3 class="text-lg font-bold text-gray-900 mt-2"><?php echo htmlspecialchars($task['title']); ?></h3>
                                            <p class="text-sm text-gray-600 mt-1"><?php echo nl2br(htmlspecialchars((string)$task['description'])); ?></p>
                                            
                                            <?php if ($task['due_date']): ?>
                                                <p class="text-xs text-red-500 mt-3 font-semibold">
                                                    <i class="fa-regular fa-clock"></i> Due Date: <?php echo date('d M Y', strtotime($task['due_date'])); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>

                                        <div class="w-full sm:w-auto">
                                            <?php if ($subStatus === 'Pending'): ?>
                                                <button type="button" @click="openProofModal(<?php echo $taskId; ?>, '<?php echo htmlspecialchars($task['title']); ?>')" class="w-full sm:w-auto bg-gradient-to-r from-emerald-600 to-green-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-md hover:shadow-lg transition">
                                                    Submit Proof
                                                </button>
                                            <?php elseif ($subStatus === 'Submitted'): ?>
                                                <span class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-amber-50 border border-amber-100 text-amber-700 text-sm font-bold">
                                                    <i class="fa-solid fa-spinner animate-spin"></i> Waiting Review
                                                </span>
                                            <?php elseif ($subStatus === 'Approved'): ?>
                                                <span class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-700 text-sm font-bold">
                                                    <i class="fa-solid fa-circle-check"></i> Approved
                                                </span>
                                            <?php elseif ($subStatus === 'Rejected'): ?>
                                                <div class="space-y-2">
                                                    <span class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-red-50 border border-red-100 text-red-700 text-sm font-bold">
                                                        <i class="fa-solid fa-circle-xmark"></i> Rejected
                                                    </span>
                                                    <button type="button" @click="openProofModal(<?php echo $taskId; ?>, '<?php echo htmlspecialchars($task['title']); ?>')" class="block w-full text-center text-xs text-green-700 font-bold hover:underline">
                                                        Resubmit Proof
                                                    </button>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <!-- Pagination UI Controls -->
                        <div x-show="<?php echo count($tasks); ?> > pagination.tasks.pageSize" class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-6 px-4 py-3 bg-white border rounded-2xl shadow-sm">
                            <div class="text-xs text-gray-500 font-medium">
                                Showing <span class="font-bold text-gray-900" x-text="Math.min((pagination.tasks.page - 1) * pagination.tasks.pageSize + 1, <?php echo count($tasks); ?>)"></span> to 
                                <span class="font-bold text-gray-900" x-text="Math.min(pagination.tasks.page * pagination.tasks.pageSize, <?php echo count($tasks); ?>)"></span> of 
                                <span class="font-bold text-gray-900"><?php echo count($tasks); ?></span> entries
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="if (pagination.tasks.page > 1) pagination.tasks.page--" :disabled="pagination.tasks.page === 1" 
                                    class="px-4 py-2 text-xs font-bold rounded-xl border bg-gray-50 text-gray-700 hover:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none transition flex items-center gap-1">
                                    <i class="fa-solid fa-chevron-left"></i> Previous
                                </button>
                                <div class="flex items-center gap-1">
                                    <span class="text-xs font-semibold text-gray-500">Page</span>
                                    <span class="text-xs font-bold text-gray-900" x-text="pagination.tasks.page"></span>
                                    <span class="text-xs font-semibold text-gray-500">of</span>
                                    <span class="text-xs font-bold text-gray-900" x-text="getTotalPages(<?php echo count($tasks); ?>, 'tasks')"></span>
                                </div>
                                <button type="button" @click="if (pagination.tasks.page < getTotalPages(<?php echo count($tasks); ?>, 'tasks')) pagination.tasks.page++" :disabled="pagination.tasks.page === getTotalPages(<?php echo count($tasks); ?>, 'tasks')" 
                                    class="px-4 py-2 text-xs font-bold rounded-xl border bg-gray-50 text-gray-700 hover:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none transition flex items-center gap-1">
                                    Next <i class="fa-solid fa-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Tab: Vendor Leads -->
                <div x-show="activeTab === 'vendorLeads'" class="space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b pb-4 border-gray-100 gap-4">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">Vendor & Partnership Leads</h2>
                            <p class="text-sm text-gray-500">Submit meetings, onboarding, sponsor leads, and college partnerships for admin verification.</p>
                        </div>
                        <button type="button" @click="vendorLeadModalOpen = true" class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 rounded-xl font-bold text-sm shadow-md shadow-emerald-500/20 transition flex items-center gap-2">
                            <i class="fa-solid fa-plus"></i> Submit Lead
                        </button>
                    </div>

                    <?php if (empty($vendorLeads)): ?>
                        <div class="text-center py-10 text-gray-400">
                            <i class="fa-solid fa-handshake text-4xl mb-3"></i>
                            <p>No vendor or partnership leads submitted yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto border rounded-2xl">
                            <table class="w-full text-left text-sm text-gray-600">
                                <thead class="bg-gray-50 text-gray-700 font-bold">
                                    <tr>
                                        <th class="p-4">Lead</th>
                                        <th class="p-4">Contact</th>
                                        <th class="p-4">Status</th>
                                        <th class="p-4 rounded-r-xl">Submitted</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <?php $leadIdx = 0; foreach ($vendorLeads as $lead): ?>
                                        <tr x-show="isPageItem(<?php echo $leadIdx++; ?>, 'vendorLeads')">
                                            <td class="p-4">
                                                <p class="font-bold text-gray-900"><?php echo htmlspecialchars($lead['business_name']); ?></p>
                                                <p class="text-xs text-blue-700 font-semibold"><?php echo htmlspecialchars($vendorLeadLabels[$lead['lead_type']] ?? $lead['lead_type']); ?></p>
                                                <p class="text-xs text-gray-400"><?php echo htmlspecialchars(trim(($lead['city_name'] ?? '') . ', ' . ($lead['state_name'] ?? ''), ', ')); ?></p>
                                            </td>
                                            <td class="p-4 text-xs">
                                                <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($lead['contact_name'] ?: 'Not provided'); ?></p>
                                                <p><?php echo htmlspecialchars($lead['contact_phone'] ?: $lead['contact_email'] ?: ''); ?></p>
                                            </td>
                                            <td class="p-4">
                                                <?php
                                                    $status = (string)$lead['verification_status'];
                                                    $statusClass = $status === 'verified'
                                                        ? 'bg-emerald-50 border-emerald-100 text-emerald-700'
                                                        : ($status === 'rejected' ? 'bg-red-50 border-red-100 text-red-700' : 'bg-orange-50 border-orange-100 text-orange-700');
                                                ?>
                                                <span class="px-2.5 py-1 rounded-full border text-[10px] font-bold uppercase tracking-wider <?php echo $statusClass; ?>">
                                                    <?php echo htmlspecialchars($status); ?>
                                                </span>
                                                <?php if (!empty($lead['verification_notes'])): ?>
                                                    <p class="text-xs text-gray-500 mt-2"><?php echo htmlspecialchars($lead['verification_notes']); ?></p>
                                                <?php endif; ?>
                                            </td>
                                            <td class="p-4 text-xs text-gray-500 font-mono"><?php echo date('d M Y', strtotime($lead['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <!-- Pagination UI Controls -->
                        <div x-show="<?php echo count($vendorLeads); ?> > pagination.vendorLeads.pageSize" class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-6 px-4 py-3 bg-white border rounded-2xl shadow-sm">
                            <div class="text-xs text-gray-500 font-medium">
                                Showing <span class="font-bold text-gray-900" x-text="Math.min((pagination.vendorLeads.page - 1) * pagination.vendorLeads.pageSize + 1, <?php echo count($vendorLeads); ?>)"></span> to 
                                <span class="font-bold text-gray-900" x-text="Math.min(pagination.vendorLeads.page * pagination.vendorLeads.pageSize, <?php echo count($vendorLeads); ?>)"></span> of 
                                <span class="font-bold text-gray-900"><?php echo count($vendorLeads); ?></span> entries
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="if (pagination.vendorLeads.page > 1) pagination.vendorLeads.page--" :disabled="pagination.vendorLeads.page === 1" 
                                    class="px-4 py-2 text-xs font-bold rounded-xl border bg-gray-50 text-gray-700 hover:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none transition flex items-center gap-1">
                                    <i class="fa-solid fa-chevron-left"></i> Previous
                                </button>
                                <div class="flex items-center gap-1">
                                    <span class="text-xs font-semibold text-gray-500">Page</span>
                                    <span class="text-xs font-bold text-gray-900" x-text="pagination.vendorLeads.page"></span>
                                    <span class="text-xs font-semibold text-gray-500">of</span>
                                    <span class="text-xs font-bold text-gray-900" x-text="getTotalPages(<?php echo count($vendorLeads); ?>, 'vendorLeads')"></span>
                                </div>
                                <button type="button" @click="if (pagination.vendorLeads.page < getTotalPages(<?php echo count($vendorLeads); ?>, 'vendorLeads')) pagination.vendorLeads.page++" :disabled="pagination.vendorLeads.page === getTotalPages(<?php echo count($vendorLeads); ?>, 'vendorLeads')" 
                                    class="px-4 py-2 text-xs font-bold rounded-xl border bg-gray-50 text-gray-700 hover:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none transition flex items-center gap-1">
                                    Next <i class="fa-solid fa-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Tab: Payment QR -->
                <div x-show="activeTab === 'payment'" class="space-y-6">
                    <div class="border-b pb-4 border-gray-100">
                        <h2 class="text-xl font-bold text-gray-900">Your Donation Tracking QR</h2>
                        <p class="text-sm text-gray-500">Collect donations using this QR code or link to track your fundraising progress.</p>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-[1.1fr_0.9fr] gap-8">
                        <div class="bg-gray-50 rounded-3xl p-6 border text-center flex flex-col justify-center items-center">
                            <h3 class="font-bold text-gray-800 text-lg mb-4">Scan using any UPI App (GPay, PhonePe, Paytm)</h3>
                            <div class="bg-white p-4 rounded-3xl shadow-sm border mb-4">
                                <img src="<?php echo $qrCodeUrl; ?>" alt="UPI QR" class="w-64 h-64 mx-auto object-contain">
                            </div>
                            <div class="bg-emerald-50 border border-emerald-100 px-4 py-2 rounded-xl text-emerald-800 font-mono text-sm font-bold mb-4">
                                Transaction Note: <?php echo htmlspecialchars($upiNote); ?>
                            </div>
                            <p class="text-xs text-gray-500 max-w-sm">
                                The QR is linked to the NGO's bank account. The unique Transaction Note tags the donation to you for city rankings and points allocation.
                            </p>
                        </div>

                        <div class="space-y-6">
                            <div class="bg-white border rounded-2xl p-5">
                                <h4 class="font-bold text-gray-900 mb-2">Personal Donation Link</h4>
                                <p class="text-xs text-gray-500 mb-3">Copy this link and share it on social media. Online payments will automatically record your referral code.</p>
                                <div class="flex gap-2">
                                    <input type="text" readonly value="<?php echo appBaseUrl(); ?>/donate.php?mref=<?php echo urlencode($student['referral_code']); ?>" x-ref="donLink" class="bg-gray-50 border rounded-xl px-3 py-2 text-xs flex-1 outline-none font-mono text-gray-600">
                                    <button type="button" @click="copyLink" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl text-xs font-bold transition">
                                        Copy
                                    </button>
                                </div>
                            </div>

                            <div class="bg-gradient-to-br from-emerald-600 to-green-700 rounded-3xl p-6 text-white shadow-lg">
                                <h4 class="font-bold text-lg mb-3">Milestone Points Rules</h4>
                                <ul class="space-y-2 text-sm text-emerald-50 font-medium">
                                    <li class="flex items-start gap-2">
                                        <i class="fa-solid fa-circle-check mt-0.5 text-amber-300"></i>
                                        <span><b>20 points</b> per verified donor.</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <i class="fa-solid fa-circle-check mt-0.5 text-amber-300"></i>
                                        <span><b>+25 bonus points</b> for donations ≥ ₹500.</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <i class="fa-solid fa-circle-check mt-0.5 text-amber-300"></i>
                                        <span><b>+75 bonus points</b> for donations ≥ ₹2,000.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: Fundraising History -->
                <div x-show="activeTab === 'fundraisingHistory'" class="space-y-6">
                    <div class="border-b pb-4 border-gray-100">
                        <h2 class="text-xl font-bold text-gray-900">Your Fundraising History</h2>
                        <p class="text-sm text-gray-500">Track all donations referred through your unique link or UPI code.</p>
                    </div>

                    <?php if (empty($fundraisingHistory)): ?>
                        <div class="text-center py-10 text-gray-400">
                            <i class="fa-solid fa-hand-holding-heart text-4xl mb-3"></i>
                            <p>No donations recorded yet. Share your donation link to start fundraising!</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto border rounded-2xl">
                            <table class="w-full text-left text-sm text-gray-600">
                                <thead class="bg-gray-50 text-gray-700 font-bold">
                                    <tr>
                                        <th class="p-4 rounded-l-xl">Donor Details</th>
                                        <th class="p-4">Project</th>
                                        <th class="p-4">Amount</th>
                                        <th class="p-4">Payment Method</th>
                                        <th class="p-4">Status</th>
                                        <th class="p-4 rounded-r-xl">Date</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y bg-white">
                                    <?php $donIdx = 0; foreach ($fundraisingHistory as $don): ?>
                                        <tr x-show="isPageItem(<?php echo $donIdx++; ?>, 'fundraising')" class="hover:bg-slate-50/50 transition">
                                            <td class="p-4">
                                                <p class="font-bold text-gray-900"><?php echo htmlspecialchars($don['donor_name']); ?></p>
                                                <p class="text-xs text-gray-500"><?php echo htmlspecialchars($don['donor_mobile'] ?: $don['donor_email'] ?: ''); ?></p>
                                            </td>
                                            <td class="p-4 text-xs">
                                                <?php echo htmlspecialchars($don['project_name'] ?: 'General Fund'); ?>
                                            </td>
                                            <td class="p-4 font-bold text-slate-900">
                                                ₹<?php echo number_format($don['amount'], 2); ?>
                                            </td>
                                            <td class="p-4 text-xs font-semibold">
                                                <?php echo htmlspecialchars($don['payment_gateway'] ?: $don['payment_mode'] ?: 'Manual'); ?>
                                            </td>
                                            <td class="p-4">
                                                <?php
                                                    $status = $don['payment_status'];
                                                    $statusClass = $status === 'Success'
                                                        ? 'bg-emerald-50 border-emerald-100 text-emerald-700'
                                                        : ($status === 'Failed' ? 'bg-red-50 border-red-100 text-red-700' : 'bg-orange-50 border-orange-100 text-orange-700');
                                                ?>
                                                <span class="px-2.5 py-1 rounded-full border text-[10px] font-bold uppercase tracking-wider <?php echo $statusClass; ?>">
                                                    <?php echo htmlspecialchars($status); ?>
                                                </span>
                                            </td>
                                            <td class="p-4 text-xs text-gray-500 font-mono">
                                                <?php echo date('d M Y, h:i A', strtotime($don['created_at'])); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <!-- Pagination UI Controls -->
                        <div x-show="<?php echo count($fundraisingHistory); ?> > pagination.fundraising.pageSize" class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-6 px-4 py-3 bg-white border rounded-2xl shadow-sm">
                            <div class="text-xs text-gray-500 font-medium">
                                Showing <span class="font-bold text-gray-900" x-text="Math.min((pagination.fundraising.page - 1) * pagination.fundraising.pageSize + 1, <?php echo count($fundraisingHistory); ?>)"></span> to 
                                <span class="font-bold text-gray-900" x-text="Math.min(pagination.fundraising.page * pagination.fundraising.pageSize, <?php echo count($fundraisingHistory); ?>)"></span> of 
                                <span class="font-bold text-gray-900"><?php echo count($fundraisingHistory); ?></span> entries
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="if (pagination.fundraising.page > 1) pagination.fundraising.page--" :disabled="pagination.fundraising.page === 1" 
                                    class="px-4 py-2 text-xs font-bold rounded-xl border bg-gray-50 text-gray-700 hover:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none transition flex items-center gap-1">
                                    <i class="fa-solid fa-chevron-left"></i> Previous
                                </button>
                                <div class="flex items-center gap-1">
                                    <span class="text-xs font-semibold text-gray-500">Page</span>
                                    <span class="text-xs font-bold text-gray-900" x-text="pagination.fundraising.page"></span>
                                    <span class="text-xs font-semibold text-gray-500">of</span>
                                    <span class="text-xs font-bold text-gray-900" x-text="getTotalPages(<?php echo count($fundraisingHistory); ?>, 'fundraising')"></span>
                                </div>
                                <button type="button" @click="if (pagination.fundraising.page < getTotalPages(<?php echo count($fundraisingHistory); ?>, 'fundraising')) pagination.fundraising.page++" :disabled="pagination.fundraising.page === getTotalPages(<?php echo count($fundraisingHistory); ?>, 'fundraising')" 
                                    class="px-4 py-2 text-xs font-bold rounded-xl border bg-gray-50 text-gray-700 hover:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none transition flex items-center gap-1">
                                    Next <i class="fa-solid fa-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Tab: Leaderboard -->
                <div x-show="activeTab === 'leaderboard'" class="space-y-6" x-data="{ leaderTab: 'national' }">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b pb-4 border-gray-100 gap-4">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">Ambassador Leaderboards</h2>
                            <p class="text-sm text-gray-500">Compete with other ambassadors and track growth.</p>
                        </div>
                        
                        <div class="inline-flex rounded-xl bg-gray-100 p-1 text-xs font-bold">
                            <button type="button" @click="leaderTab = 'national'" :class="leaderTab === 'national' ? 'bg-white text-emerald-700 shadow-sm' : 'text-gray-500'" class="px-4 py-2 rounded-lg transition">National</button>
                            <button type="button" @click="leaderTab = 'state'" :class="leaderTab === 'state' ? 'bg-white text-emerald-700 shadow-sm' : 'text-gray-500'" class="px-4 py-2 rounded-lg transition">State</button>
                            <button type="button" @click="leaderTab = 'city'" :class="leaderTab === 'city' ? 'bg-white text-emerald-700 shadow-sm' : 'text-gray-500'" class="px-4 py-2 rounded-lg transition">City (<?php echo htmlspecialchars($student['city_name']); ?>)</button>
                            <button type="button" @click="leaderTab = 'campus'" :class="leaderTab === 'campus' ? 'bg-white text-emerald-700 shadow-sm' : 'text-gray-500'" class="px-4 py-2 rounded-lg transition">Campus</button>
                        </div>
                    </div>

                    <!-- National Leaderboard -->
                    <div x-show="leaderTab === 'national'" class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-600">
                            <thead class="bg-gray-50 text-gray-700 font-bold">
                                <tr>
                                    <th class="p-4 rounded-l-xl">Rank</th>
                                    <th class="p-4">Ambassador Name</th>
                                    <th class="p-4">College</th>
                                    <th class="p-4">Level</th>
                                    <th class="p-4 rounded-r-xl text-right">Points</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <?php foreach ($nationalLeaderboard as $i => $row): ?>
                                    <tr class="<?php echo $row['full_name'] === $student['full_name'] ? 'bg-emerald-50/50 font-semibold text-emerald-900' : ''; ?>">
                                        <td class="p-4 font-bold">#<?php echo $i + 1; ?></td>
                                        <td class="p-4"><?php echo htmlspecialchars($row['full_name']); ?></td>
                                        <td class="p-4 text-xs"><?php echo htmlspecialchars($row['college_name']); ?></td>
                                        <td class="p-4"><span class="px-2 py-0.5 rounded text-xs bg-gray-100 font-semibold"><?php echo htmlspecialchars($row['level_name']); ?></span></td>
                                        <td class="p-4 text-right font-black"><?php echo $row['total_points']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- State Leaderboard -->
                    <div x-show="leaderTab === 'state'" class="text-center py-10 text-gray-400">
                        <i class="fa-solid fa-map-location text-3xl mb-2"></i>
                        <p class="text-sm font-semibold">State Rank: #<?php echo $stateRank; ?></p>
                        <p class="text-xs mt-1">Full state listing is available in reports.</p>
                    </div>

                    <!-- City Leaderboard -->
                    <div x-show="leaderTab === 'city'" class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-600">
                            <thead class="bg-gray-50 text-gray-700 font-bold">
                                <tr>
                                    <th class="p-4 rounded-l-xl">Rank</th>
                                    <th class="p-4">Ambassador Name</th>
                                    <th class="p-4">College</th>
                                    <th class="p-4">Level</th>
                                    <th class="p-4 rounded-r-xl text-right">Points</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <?php foreach ($cityLeaderboard as $i => $row): ?>
                                    <tr class="<?php echo $row['full_name'] === $student['full_name'] ? 'bg-emerald-50/50 font-semibold text-emerald-900' : ''; ?>">
                                        <td class="p-4 font-bold">#<?php echo $i + 1; ?></td>
                                        <td class="p-4"><?php echo htmlspecialchars($row['full_name']); ?></td>
                                        <td class="p-4 text-xs"><?php echo htmlspecialchars($row['college_name']); ?></td>
                                        <td class="p-4"><span class="px-2 py-0.5 rounded text-xs bg-gray-100 font-semibold"><?php echo htmlspecialchars($row['level_name']); ?></span></td>
                                        <td class="p-4 text-right font-black"><?php echo $row['total_points']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Campus Leaderboard -->
                    <div x-show="leaderTab === 'campus'" class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-600">
                            <thead class="bg-gray-50 text-gray-700 font-bold">
                                <tr>
                                    <th class="p-4 rounded-l-xl">Rank</th>
                                    <th class="p-4">Ambassador Name</th>
                                    <th class="p-4">Level</th>
                                    <th class="p-4 rounded-r-xl text-right">Points</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <?php foreach ($campusLeaderboard as $i => $row): ?>
                                    <tr class="<?php echo $row['full_name'] === $student['full_name'] ? 'bg-emerald-50/50 font-semibold text-emerald-900' : ''; ?>">
                                        <td class="p-4 font-bold">#<?php echo $i + 1; ?></td>
                                        <td class="p-4"><?php echo htmlspecialchars($row['full_name']); ?></td>
                                        <td class="p-4"><span class="px-2 py-0.5 rounded text-xs bg-gray-100 font-semibold"><?php echo htmlspecialchars($row['level_name']); ?></span></td>
                                        <td class="p-4 text-right font-black"><?php echo $row['total_points']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab: Badges -->
                <div x-show="activeTab === 'badges'" class="space-y-6">
                    <div class="border-b pb-4 border-gray-100">
                        <h2 class="text-xl font-bold text-gray-900">Your Achievement Badges</h2>
                        <p class="text-sm text-gray-500">Collect points and complete milestones to unlock digital badges.</p>
                    </div>

                    <?php if (empty($badges)): ?>
                        <div class="text-center py-10 text-gray-400">
                            <i class="fa-solid fa-ribbon text-4xl mb-3"></i>
                            <p>No badges earned yet. Complete tasks and verify onboarding to earn badges!</p>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                            <?php foreach ($badges as $badge): ?>
                                <div class="bg-gray-50 rounded-2xl p-5 border text-center flex flex-col justify-center items-center shadow-sm">
                                    <div class="w-16 h-16 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center text-3xl mb-4 shadow-inner ring-4 ring-amber-50">
                                        <i class="fa-solid <?php echo htmlspecialchars($badge['icon_class'] ?: 'fa-award'); ?>"></i>
                                    </div>
                                    <h4 class="font-bold text-gray-900"><?php echo htmlspecialchars($badge['badge_name']); ?></h4>
                                    <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($badge['description']); ?></p>
                                    <p class="text-[10px] text-gray-400 mt-3 font-semibold uppercase">Unlocked: <?php echo date('d M Y', strtotime($badge['awarded_at'])); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Tab: Certificates -->
                <div x-show="activeTab === 'certificates'" class="space-y-6">
                    <div class="border-b pb-4 border-gray-100">
                        <h2 class="text-xl font-bold text-gray-900">Verified Certificates</h2>
                        <p class="text-sm text-gray-500">Official completion, volunteer, or leadership credentials issued by the administration.</p>
                    </div>

                    <?php if (empty($certificates)): ?>
                        <div class="text-center py-10 text-gray-400">
                            <i class="fa-solid fa-file-invoice text-4xl mb-3"></i>
                            <p>No certificates issued yet. Reach level milestones to unlock completing credentials.</p>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <?php foreach ($certificates as $cert): ?>
                                <div class="bg-gray-50 rounded-2xl border p-5 flex items-start gap-4 shadow-sm hover:border-emerald-300 transition">
                                    <div class="p-3 bg-emerald-100 text-emerald-700 rounded-xl text-2xl shadow-sm">
                                        <i class="fa-solid fa-certificate"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="font-bold text-gray-900"><?php echo htmlspecialchars($cert['certificate_title'] ?? $cert['title'] ?? 'Certificate'); ?></h4>
                                        <p class="text-xs text-gray-500 mt-0.5">Certificate No: <span class="font-mono text-gray-800 font-semibold"><?php echo htmlspecialchars($cert['certificate_no']); ?></span></p>
                                        <p class="text-xs text-gray-500 mt-0.5">Issued: <?php echo !empty($cert['issued_at']) ? date('d M Y', strtotime($cert['issued_at'])) : '—'; ?></p>
                                        
                                        <?php if (($cert['status'] ?? '') === 'Generated'): ?>
                                            <a href="process/download_student_certificate.php?id=<?php echo (int)$cert['id']; ?>" target="_blank" class="inline-flex items-center gap-1 bg-emerald-600 text-white font-bold text-xs px-3.5 py-2 rounded-lg mt-4 shadow-sm hover:bg-emerald-700 transition">
                                                <i class="fa-solid fa-download"></i> View / Download
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Tab: Donation Achievements -->
                <div x-show="activeTab === 'donationAchievements'" class="space-y-6">
                    <div class="flex items-center justify-between border-b pb-4 border-gray-100">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">Donation Achievements</h2>
                            <p class="text-sm text-gray-500">Track your fundraising milestones and earn rewards for facilitating donations.</p>
                        </div>
                    </div>
                    
                    <?php 
                    // Include the donation achievements widget
                    require_once __DIR__ . '/includes/student/donation_achievements_dashboard.php';
                    ?>
                </div>

                <!-- Tab: Attendance -->
                <div x-show="activeTab === 'attendance'" class="space-y-6">
                    <div class="flex items-center justify-between border-b pb-4 border-gray-100">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">Attendance & Bonuses</h2>
                            <p class="text-sm text-gray-500">Track your monthly attendance, earn bonuses for 90% attendance, and see your promotion eligibility.</p>
                        </div>
                    </div>
                    
                    <?php 
                    // Include the attendance dashboard widget
                    require_once __DIR__ . '/includes/student/attendance_dashboard.php';
                    ?>
                </div>

                <!-- Tab: Onboard Partner -->
                <div x-show="activeTab === 'onboardPartner'" class="space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b pb-4 border-gray-100 gap-2">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">Onboard Wellness Partners</h2>
                            <p class="text-sm text-gray-500">Register new retail or physical activity partners to expand the network and earn points instantly.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Left/Main: Onboarding Form -->
                        <div class="lg:col-span-2 bg-slate-50/50 rounded-2xl border p-6">
                            <form @submit.prevent="submitPartner($event)" class="space-y-6">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($dashboardCsrf); ?>">

                                <!-- Step 1: Owner Details -->
                                <div class="space-y-4">
                                    <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider flex items-center gap-2">
                                        <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-bold">1</span>
                                        Owner & Security Details
                                    </h3>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wider">Owner Name *</label>
                                            <input type="text" name="owner_name" x-model="partnerForm.owner_name" required @input="partnerForm.owner_name = partnerForm.owner_name.replace(/[^a-zA-Z\s]/g, '').slice(0, 100)" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-emerald-500 outline-none text-sm bg-white" placeholder="Full Name">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wider">Owner Email *</label>
                                            <input type="email" name="owner_email" x-model="partnerForm.owner_email" required class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-emerald-500 outline-none text-sm bg-white" placeholder="owner@email.com">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wider">Owner Mobile *</label>
                                            <input type="tel" name="owner_mobile" x-model="partnerForm.owner_mobile" required @input="partnerForm.owner_mobile = partnerForm.owner_mobile.replace(/[^0-9]/g, '').slice(0, 15)" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-emerald-500 outline-none text-sm bg-white" placeholder="e.g. 9876543210">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wider">Password *</label>
                                            <input type="password" name="password" x-model="partnerForm.password" required class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-emerald-500 outline-none text-sm bg-white" placeholder="Min 8 characters">
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wider">Confirm Password *</label>
                                            <input type="password" name="confirm_password" x-model="partnerForm.confirm_password" required class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-emerald-500 outline-none text-sm bg-white" placeholder="Re-enter Password">
                                        </div>
                                    </div>
                                </div>

                                <hr class="border-gray-200">

                                <!-- Step 2: Business details -->
                                <div class="space-y-4">
                                    <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider flex items-center gap-2">
                                        <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-bold">2</span>
                                        Business & Activity Details
                                    </h3>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wider">Business Name *</label>
                                            <input type="text" name="business_name" x-model="partnerForm.business_name" required @input="partnerForm.business_name = partnerForm.business_name.slice(0, 150)" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-emerald-500 outline-none text-sm bg-white" placeholder="e.g. FitLife Gym">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wider">Business Mobile</label>
                                            <input type="tel" name="business_mobile" x-model="partnerForm.business_mobile" @input="partnerForm.business_mobile = partnerForm.business_mobile.replace(/[^0-9]/g, '').slice(0, 15)" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-emerald-500 outline-none text-sm bg-white" placeholder="Contact number (optional)">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wider">Activity Type *</label>
                                            <select name="activity_type" x-model="partnerForm.activity_type" required class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-emerald-500 outline-none text-sm bg-white">
                                                <option value="retail">Retail / General Shop / Cafe</option>
                                                <option value="physical_activity_center">Physical Activity Center / Gym</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wider">Govt Registered? *</label>
                                            <select name="govt_registered" x-model="partnerForm.govt_registered" required class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-emerald-500 outline-none text-sm bg-white">
                                                <option value="N">No / Not Registered</option>
                                                <option value="Y">Yes / Registered</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wider">Presence *</label>
                                            <select name="presence" x-model="partnerForm.presence" required class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-emerald-500 outline-none text-sm bg-white">
                                                <option value="ON">Online / ON</option>
                                                <option value="OFF">Offline / OFF</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wider">Parent Partner ID</label>
                                            <input type="number" name="parent_business_partner_id" x-model="partnerForm.parent_business_partner_id" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-emerald-500 outline-none text-sm bg-white">
                                        </div>
                                    </div>
                                </div>

                                <hr class="border-gray-200">

                                <!-- Step 3: Location Details -->
                                <div class="space-y-4">
                                    <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider flex items-center gap-2">
                                        <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-bold">3</span>
                                        Location & Maps
                                    </h3>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div class="sm:col-span-2">
                                            <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wider">Business Address *</label>
                                            <textarea name="business_address" x-model="partnerForm.business_address" required rows="2" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-emerald-500 outline-none text-sm bg-white" placeholder="Full business address..."></textarea>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wider">State Name *</label>
                                            <select name="state_name" x-model="partnerForm.state_name" @change="updatePartnerDistricts()" required class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-emerald-500 outline-none text-sm bg-white">
                                                <option value="">Select State</option>
                                                <template x-for="st in states" :key="st">
                                                    <option :value="st" x-text="st"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wider">City / District *</label>
                                            <select name="city_name" x-model="partnerForm.city_name" required class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-emerald-500 outline-none text-sm bg-white">
                                                <option value="">Select City / District</option>
                                                <template x-for="dist in partnerDistricts" :key="dist">
                                                    <option :value="dist" x-text="dist"></option>
                                                </template>
                                            </select>
                                        </div>
                                        
                                        <!-- API numeric fields -->
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wider">Country ID</label>
                                            <input type="number" name="country_id" x-model="partnerForm.country_id" required class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-emerald-500 outline-none text-sm bg-white">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wider">State ID</label>
                                            <input type="number" name="state_id" x-model="partnerForm.state_id" required class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-emerald-500 outline-none text-sm bg-white">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wider">City ID</label>
                                            <input type="number" name="city_id" x-model="partnerForm.city_id" required class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-emerald-500 outline-none text-sm bg-white">
                                        </div>

                                        <div class="grid grid-cols-2 gap-2 sm:col-span-2">
                                            <div>
                                                <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wider">Latitude *</label>
                                                <input type="text" name="latitude" id="partner_latitude" x-model="partnerForm.latitude" required class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-emerald-500 outline-none text-sm bg-white" placeholder="e.g. 19.1234">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wider">Longitude *</label>
                                                <input type="text" name="longitude" id="partner_longitude" x-model="partnerForm.longitude" required class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-emerald-500 outline-none text-sm bg-white" placeholder="e.g. 72.1234">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="pt-2">
                                        <button type="button" @click="detectPartnerLocation()" class="inline-flex items-center gap-2 rounded-xl bg-slate-800 hover:bg-slate-900 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition">
                                            <i class="fa-solid fa-location-crosshairs"></i> Detect My Location
                                        </button>
                                    </div>

                                    <!-- Google Maps Container -->
                                    <?php if (!empty($googleMapsApiKey)): ?>
                                        <div class="mt-4">
                                            <p class="text-xs text-gray-500 mb-2 font-semibold"><i class="fa-solid fa-circle-info mr-1"></i> Click map or drag marker to pinpoint coordinates.</p>
                                            <div id="partner-map" class="w-full h-64 rounded-2xl border border-slate-200 shadow-inner bg-slate-100"></div>
                                        </div>
                                    <?php else: ?>
                                        <div class="mt-4 p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-800 text-xs flex items-start gap-2.5">
                                            <i class="fa-solid fa-triangle-exclamation text-base"></i>
                                            <div>
                                                <p class="font-bold">Google Maps Visual Picker Disabled</p>
                                                <p class="mt-0.5 leading-relaxed">The map picker will load once the admin configures a Google Maps API Key under settings.</p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div x-show="partnerMessage" x-text="partnerMessage" 
                                    :class="partnerMessageType === 'success' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200'"
                                    class="p-4 rounded-xl border text-sm font-medium animate-pulse">
                                </div>

                                <div class="pt-4 border-t flex justify-end">
                                    <button type="submit" :disabled="partnerLoading" class="w-full sm:w-auto bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white font-bold text-xs px-8 py-3.5 rounded-xl shadow-lg transition disabled:opacity-60 flex items-center justify-center gap-2 uppercase tracking-wider">
                                        <span x-show="!partnerLoading"><i class="fa-solid fa-store mr-1"></i> Register Partner</span>
                                        <span x-show="partnerLoading" class="flex items-center gap-1.5">
                                            <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Syncing with External API...
                                        </span>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Right Column: Onboarded Partners List -->
                        <div class="bg-white rounded-2xl border p-6 space-y-4">
                            <h3 class="text-sm font-bold text-gray-900 uppercase tracking-widest border-b pb-2">Registered Partners</h3>
                            <p class="text-xs text-gray-500 leading-relaxed">Below are the wellness partners you have registered. Points are credited immediately upon verified API registration.</p>
                            
                            <div class="space-y-3 max-h-[600px] overflow-y-auto pr-1 custom-scrollbar">
                                <?php if (empty($onboardedPartners)): ?>
                                    <div class="text-center py-12 text-slate-300">
                                        <i class="fa-solid fa-store-slash text-4xl mb-2.5"></i>
                                        <p class="text-xs font-bold uppercase tracking-wider">No partners onboarded</p>
                                    </div>
                                <?php else: ?>
                                    <?php $partnerIdx = 0; foreach ($onboardedPartners as $p): ?>
                                         <div x-show="isPageItem(<?php echo $partnerIdx++; ?>, 'partners')" class="p-4 rounded-xl border border-slate-100 bg-slate-50/50 hover:bg-slate-50 hover:shadow-sm transition">
                                            <div class="flex items-start justify-between gap-2">
                                                <div class="min-w-0">
                                                    <p class="font-bold text-xs text-slate-800 truncate"><?php echo htmlspecialchars($p['business_name']); ?></p>
                                                    <p class="text-[9px] text-slate-400 font-mono mt-0.5"><?php echo htmlspecialchars($p['partner_code']); ?></p>
                                                </div>
                                                <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold uppercase <?php echo ($p['status'] === 'Active') ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-amber-50 text-amber-700 border border-amber-100'; ?>">
                                                    <?php echo htmlspecialchars($p['status']); ?>
                                                </span>
                                            </div>
                                            <div class="mt-3 grid grid-cols-2 gap-2 text-[10px] text-slate-500">
                                                <div>
                                                    <p class="font-bold uppercase text-[9px] text-slate-400">Owner</p>
                                                    <p class="font-semibold text-slate-700 truncate mt-0.5"><?php echo htmlspecialchars($p['owner_name']); ?></p>
                                                </div>
                                                <div>
                                                    <p class="font-bold uppercase text-[9px] text-slate-400">Category</p>
                                                    <p class="font-semibold text-slate-700 mt-0.5 truncate"><?php echo ($p['activity_type'] === 'physical_activity_center') ? 'Fitness / Gym' : 'Retail / Cafe'; ?></p>
                                                </div>
                                            </div>
                                            <p class="text-[9px] text-slate-400 font-medium mt-3 border-t pt-2 flex items-center justify-between">
                                                <span>Registered On</span>
                                                <span class="font-bold text-slate-600"><?php echo date('d M Y', strtotime($p['created_at'])); ?></span>
                                            </p>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <!-- Pagination UI Controls -->
                            <div x-show="<?php echo count($onboardedPartners); ?> > pagination.partners.pageSize" class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-4 px-3 py-2.5 bg-white border rounded-xl shadow-sm">
                                <div class="text-[10px] text-gray-500 font-medium">
                                    Page <span class="font-bold text-gray-900" x-text="pagination.partners.page"></span> of 
                                    <span class="font-bold text-gray-900" x-text="getTotalPages(<?php echo count($onboardedPartners); ?>, 'partners')"></span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <button type="button" @click="if (pagination.partners.page > 1) pagination.partners.page--" :disabled="pagination.partners.page === 1" 
                                        class="p-1.5 rounded-lg border bg-gray-50 text-gray-700 hover:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none transition text-[10px]">
                                        <i class="fa-solid fa-chevron-left"></i>
                                    </button>
                                    <button type="button" @click="if (pagination.partners.page < getTotalPages(<?php echo count($onboardedPartners); ?>, 'partners')) pagination.partners.page++" :disabled="pagination.partners.page === getTotalPages(<?php echo count($onboardedPartners); ?>, 'partners')" 
                                        class="p-1.5 rounded-lg border bg-gray-50 text-gray-700 hover:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none transition text-[10px]">
                                        <i class="fa-solid fa-chevron-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

    <!-- Vendor Lead Modal -->
    <div x-show="vendorLeadModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-cloak>
        <div class="bg-white max-w-2xl w-full rounded-3xl shadow-2xl p-6 md:p-8 border" @click.away="vendorLeadModalOpen = false">
            <h3 class="text-2xl font-bold text-gray-900 tracking-tight">Submit Vendor Lead</h3>
            <p class="text-xs text-gray-500 mt-1">Your lead will be reviewed by the admin team before points are awarded.</p>

            <form @submit.prevent="submitVendorLead($event)" class="mt-6 space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($dashboardCsrf); ?>">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Lead Type *</label>
                        <select name="lead_type" required class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-green-500 outline-none text-sm">
                            <option value="vendor_meeting">Vendor Meeting</option>
                            <option value="vendor_onboarding">Vendor Onboarding</option>
                            <option value="premium_vendor_onboarding">Premium Vendor Onboarding</option>
                            <option value="sponsor_partnership">Sponsor Lead</option>
                            <option value="college_partnership">College Partnership</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Business / Organization *</label>
                        <input type="text" name="business_name" required class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-green-500 outline-none text-sm" placeholder="Name of vendor, sponsor, or college">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Contact Name</label>
                        <input type="text" name="contact_name" pattern="^[a-zA-Z\s'\.\-]+$" title="Contact Name should only contain letters, spaces, hyphens, apostrophes, and dots" oninput="this.value = this.value.replace(/[^a-zA-Z\s'\.\-]/g, '')" class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-green-500 outline-none text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Contact Phone</label>
                        <input type="tel" name="contact_phone" maxlength="10" pattern="^[6-9][0-9]{9}$" title="Contact Phone must start with 6, 7, 8, or 9 and be exactly 10 digits" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)" class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-green-500 outline-none text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Contact Email</label>
                        <input type="email" name="contact_email" class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-green-500 outline-none text-sm">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">State</label>
                        <select name="state_name" x-model="vendorLeadSelectedState" @change="updateVendorLeadDistricts()" class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-green-500 outline-none text-sm">
                            <option value="">Select State</option>
                            <template x-for="st in states" :key="st">
                                <option :value="st" x-text="st" :selected="st === '<?php echo addslashes((string)($student['state_name'] ?? '')); ?>'"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">City / District</label>
                        <select name="city_name" x-model="vendorLeadSelectedCity" class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-green-500 outline-none text-sm">
                            <option value="">Select City / District</option>
                            <template x-for="dist in vendorLeadDistricts" :key="dist">
                                <option :value="dist" x-text="dist"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Meeting Date</label>
                        <input type="date" name="meeting_date" min="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d', strtotime('+10 years')); ?>" class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-green-500 outline-none text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Proof File</label>
                    <input type="file" name="proof_file" accept="image/*,.pdf" class="w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Notes</label>
                    <textarea name="notes" rows="3" class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-green-500 outline-none" placeholder="Describe the discussion, onboarding status, or partnership opportunity."></textarea>
                </div>

                <div x-show="vendorLeadMessage" x-text="vendorLeadMessage"
                    :class="vendorLeadMessageType === 'error' ? 'bg-red-50 text-red-700 border-red-200' : 'bg-green-50 text-green-700 border-green-200'"
                    class="p-4 rounded-xl border text-sm font-medium">
                </div>

                <div class="flex justify-end gap-2 pt-4 border-t border-gray-100">
                    <button type="button" @click="vendorLeadModalOpen = false" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-5 py-2.5 rounded-xl font-bold text-sm transition">Cancel</button>
                    <button type="submit" :disabled="vendorLeadLoading" class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 rounded-xl font-bold text-sm transition">
                        <span x-show="!vendorLeadLoading">Submit Lead</span>
                        <span x-show="vendorLeadLoading">Submitting...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Submit Proof Modal -->
    <div x-show="proofModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-cloak>
        <div class="bg-white max-w-lg w-full rounded-3xl shadow-2xl p-6 md:p-8 border" @click.away="proofModalOpen = false">
            <h3 class="text-2xl font-bold text-gray-900 tracking-tight" x-text="currentTaskTitle">Submit Task Proof</h3>
            <p class="text-xs text-gray-500 mt-1">Upload screenshots or notes confirming your task completion.</p>

            <form @submit.prevent="submitProof($event)" class="mt-6 space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($dashboardCsrf); ?>">
                <input type="hidden" name="task_id" :value="currentTaskId">

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Proof Screenshot *</label>
                    <input type="file" name="proof_screenshot" required accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Submission Notes / Description</label>
                    <textarea name="notes" rows="3" class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-green-500 outline-none" placeholder="Provide extra description about completed task..."></textarea>
                </div>

                <div x-show="modalMessage" x-text="modalMessage" 
                    :class="modalMessageType === 'error' ? 'bg-red-50 text-red-700 border-red-200' : 'bg-green-50 text-green-700 border-green-200'"
                    class="p-4 rounded-xl border text-sm font-medium">
                </div>

                <div class="flex justify-end gap-2 pt-4 border-t border-gray-100">
                    <button type="button" @click="proofModalOpen = false" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-5 py-2.5 rounded-xl font-bold text-sm transition">Cancel</button>
                    <button type="submit" :disabled="modalLoading" class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 rounded-xl font-bold text-sm transition flex items-center gap-1.5">
                        <span x-show="!modalLoading">Submit Proof</span>
                        <span x-show="modalLoading" class="flex items-center gap-1.5">
                            <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Uploading...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
</div>

<script>
function dashboardTabs() {
    return {
        activeTab: 'tasks',
        attendanceMarked: <?php echo $attendanceMarkedToday ? 'true' : 'false'; ?>,
        proofModalOpen: false,
        vendorLeadModalOpen: false,
        
        // Pagination state
        pagination: {
            tasks: { page: 1, pageSize: 5 },
            vendorLeads: { page: 1, pageSize: 5 },
            fundraising: { page: 1, pageSize: 5 },
            partners: { page: 1, pageSize: 5 }
        },

        isPageItem(index, key) {
            const state = this.pagination[key];
            if (!state) return true;
            const itemPage = Math.ceil((index + 1) / state.pageSize);
            return itemPage === state.page;
        },

        getTotalPages(count, key) {
            const state = this.pagination[key];
            if (!state) return 1;
            return Math.ceil(count / state.pageSize);
        },
        currentTaskId: 0,
        currentTaskTitle: '',
        modalLoading: false,
        modalMessage: '',
        modalMessageType: 'error',
        vendorLeadLoading: false,
        vendorLeadMessage: '',
        vendorLeadMessageType: 'error',

        stateDistrictData: <?php require_once __DIR__ . '/includes/india_locations.php'; echo india_state_district_js(); ?>,
        get states() { return Object.keys(this.stateDistrictData); },
        partnerDistricts: [],
        updatePartnerDistricts() {
            this.partnerDistricts = this.stateDistrictData[this.partnerForm.state_name] || [];
            this.partnerForm.city_name = '';
        },
        vendorLeadSelectedState: '<?php echo addslashes((string)($student['state_name'] ?? '')); ?>',
        vendorLeadSelectedCity: '<?php echo addslashes((string)($student['city_name'] ?? '')); ?>',
        vendorLeadDistricts: <?php echo json_encode(india_state_district_map()[$student['state_name'] ?? ''] ?? []); ?>,
        updateVendorLeadDistricts() {
            this.vendorLeadDistricts = this.stateDistrictData[this.vendorLeadSelectedState] || [];
            this.vendorLeadSelectedCity = '';
        },

        // Onboard Partner State
        partnerForm: {
            owner_name: '',
            owner_email: '',
            owner_mobile: '',
            password: '',
            confirm_password: '',
            business_name: '',
            business_mobile: '',
            activity_type: 'retail',
            govt_registered: 'N',
            presence: 'ON',
            business_address: '',
            latitude: '',
            longitude: '',
            city_name: '',
            state_name: '',
            country_id: 93,
            state_id: 1,
            city_id: 1,
            parent_business_partner_id: 1
        },
        partnerLoading: false,
        partnerMessage: '',
        partnerMessageType: 'error',

        openProofModal(taskId, taskTitle) {
            this.currentTaskId = taskId;
            this.currentTaskTitle = taskTitle;
            this.modalMessage = '';
            this.proofModalOpen = true;
        },

        async markAttendance() {
            if (this.attendanceMarked) return;
            try {
                const res = await fetch('process/mark_student_attendance.php', { method: 'POST' });
                const data = await res.json();
                if (data.success) {
                    this.attendanceMarked = true;
                    window.studentShowToast(data.message || 'Attendance marked successfully!', 'success');
                    setTimeout(() => window.location.reload(), 900);
                } else {
                    window.studentShowToast(data.message || 'Failed to mark attendance.', 'error');
                }
            } catch (e) {
                window.studentShowToast('An error occurred. Please try again.', 'error');
            }
        },

        async submitProof(event) {
            this.modalLoading = true;
            this.modalMessage = '';
            
            const formData = new FormData(event.target);

            try {
                const res = await fetch('process/submit_task_proof.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    this.modalMessage = 'Proof submitted successfully!';
                    this.modalMessageType = 'success';
                    setTimeout(() => {
                        this.proofModalOpen = false;
                        window.location.reload();
                    }, 1000);
                } else {
                    this.modalMessage = data.message || 'Failed to submit proof.';
                    this.modalMessageType = 'error';
                }
            } catch (e) {
                this.modalMessage = 'An unexpected error occurred.';
                this.modalMessageType = 'error';
            } finally {
                this.modalLoading = false;
            }
        },

        async submitVendorLead(event) {
            this.vendorLeadLoading = true;
            this.vendorLeadMessage = '';

            try {
                const res = await fetch('process/submit_vendor_lead.php', {
                    method: 'POST',
                    body: new FormData(event.target)
                });
                const data = await res.json();
                if (data.success) {
                    this.vendorLeadMessage = data.message || 'Lead submitted successfully!';
                    this.vendorLeadMessageType = 'success';
                    setTimeout(() => {
                        this.vendorLeadModalOpen = false;
                        window.location.reload();
                    }, 1000);
                } else {
                    this.vendorLeadMessage = data.message || 'Failed to submit lead.';
                    this.vendorLeadMessageType = 'error';
                }
            } catch (e) {
                this.vendorLeadMessage = 'An unexpected error occurred.';
                this.vendorLeadMessageType = 'error';
            } finally {
                this.vendorLeadLoading = false;
            }
        },

        async submitPartner(event) {
            // Strength check validation
            const pw = this.partnerForm.password;
            if (pw.length < 8 || !/[A-Z]/.test(pw) || !/[a-z]/.test(pw) || !/[0-9]/.test(pw) || !/[@$!%*?&#]/.test(pw)) {
                this.partnerMessage = 'Password must be at least 8 characters and contain uppercase, lowercase, number, and special character.';
                this.partnerMessageType = 'error';
                window.studentShowToast('Weak password.', 'error');
                return;
            }

            if (pw !== this.partnerForm.confirm_password) {
                this.partnerMessage = 'Passwords do not match.';
                this.partnerMessageType = 'error';
                window.studentShowToast('Passwords do not match.', 'error');
                return;
            }

            this.partnerLoading = true;
            this.partnerMessage = '';

            const formData = new FormData(event.target);
            try {
                const res = await fetch('process/student_onboard_partner.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    this.partnerMessage = data.message;
                    this.partnerMessageType = 'success';
                    window.studentShowToast(data.message || 'Partner onboarded successfully!', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    this.partnerMessage = data.message || 'Registration failed.';
                    this.partnerMessageType = 'error';
                    window.studentShowToast(data.message || 'Registration failed.', 'error');
                }
            } catch (e) {
                this.partnerMessage = 'An unexpected error occurred.';
                this.partnerMessageType = 'error';
                window.studentShowToast('An error occurred.', 'error');
            } finally {
                this.partnerLoading = false;
            }
        },

        detectPartnerLocation() {
            if (!navigator.geolocation) {
                this.partnerMessage = 'Geolocation is not supported by your browser.';
                this.partnerMessageType = 'error';
                return;
            }
            navigator.geolocation.getCurrentPosition((pos) => {
                this.partnerForm.latitude = pos.coords.latitude.toFixed(7);
                this.partnerForm.longitude = pos.coords.longitude.toFixed(7);
                this.partnerMessage = 'Location captured successfully.';
                this.partnerMessageType = 'success';
                
                if (typeof window.partnerMarker !== 'undefined') {
                    const latlng = new google.maps.LatLng(pos.coords.latitude, pos.coords.longitude);
                    window.partnerMarker.setPosition(latlng);
                    window.partnerMap.setCenter(latlng);
                    window.partnerMap.setZoom(15);
                }
            }, () => {
                this.partnerMessage = 'Unable to detect location. Enter coordinates manually.';
                this.partnerMessageType = 'error';
            });
        },

        copyLink() {
            const input = this.$refs.donLink;
            input.select();
            input.setSelectionRange(0, 99999);
            document.execCommand('copy');
            window.studentShowToast('Donation link copied to clipboard!', 'success');
        }
    };
}
</script>

<?php if (!empty($googleMapsApiKey)): ?>
<script>
window.initPartnerMap = function() {
    const defaultCoords = { lat: 20.5937, lng: 78.9629 };
    const mapContainer = document.getElementById('partner-map');
    if (!mapContainer) return;
    
    window.partnerMap = new google.maps.Map(mapContainer, {
        center: defaultCoords,
        zoom: 5
    });
    
    window.partnerMarker = new google.maps.Marker({
        position: defaultCoords,
        map: window.partnerMap,
        draggable: true
    });
    
    google.maps.event.addListener(window.partnerMarker, 'dragend', function() {
        const position = window.partnerMarker.getPosition();
        updateCoords(position.lat(), position.lng());
    });
    
    google.maps.event.addListener(window.partnerMap, 'click', function(event) {
        window.partnerMarker.setPosition(event.latLng);
        updateCoords(event.latLng.lat(), event.latLng.lng());
    });
    
    function updateCoords(lat, lng) {
        const inputLat = document.getElementById('partner_latitude');
        const inputLng = document.getElementById('partner_longitude');
        if (inputLat && inputLng) {
            inputLat.value = lat.toFixed(7);
            inputLng.value = lng.toFixed(7);
            inputLat.dispatchEvent(new Event('input'));
            inputLng.dispatchEvent(new Event('input'));
        }
    }
};
</script>
<script src="https://maps.googleapis.com/maps/api/js?key=<?php echo htmlspecialchars($googleMapsApiKey); ?>&callback=initPartnerMap" async defer></script>
<?php endif; ?>

<?php student_portal_render_shell_end(); ?>
