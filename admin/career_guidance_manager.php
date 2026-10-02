<?php
// ============================================================
// admin/career_guidance_manager.php
// Career Guidance CMS & Skills Training Management
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

$currentTab = cleanInput($_GET['tab'] ?? 'cms');
if (!in_array($currentTab, ['cms', 'courses', 'enrollments'], true)) {
    $currentTab = 'cms';
}

// 1. Fetch CMS Settings
$cmsSettings = [];
$cStmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'career_guidance_%'");
while ($row = $cStmt->fetch(PDO::FETCH_ASSOC)) {
    $cmsSettings[$row['setting_key']] = $row['setting_value'];
}

// 2. Fetch Skill Courses with Pagination
$pageCourses = max(1, (int)($_GET['p_c'] ?? 1));
$perPageCourses = 10;
$totalCourses = 0;
try {
    $totalCourses = (int)$pdo->query("SELECT COUNT(*) FROM skill_courses")->fetchColumn();
    $offsetCourses = ($pageCourses - 1) * $perPageCourses;
    $courses = $pdo->query("
        SELECT c.*, 
               (SELECT COUNT(*) FROM skill_course_enrollments e WHERE e.course_id = c.id) AS enrollments_count,
               (SELECT COUNT(*) FROM skill_course_enrollments e WHERE e.course_id = c.id AND e.status = 'enrolled') AS active_students_count
        FROM skill_courses c 
        ORDER BY c.id DESC
        LIMIT $perPageCourses OFFSET $offsetCourses
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $courses = [];
}

// 3. Fetch Enrollments with Pagination & Search
$pageEnr = max(1, (int)($_GET['p_e'] ?? 1));
$perPageEnr = 10;
$searchEnr = trim($_GET['search_enr'] ?? '');
$statusEnr = trim($_GET['status_enr'] ?? '');

$whereEnr = ["1=1"];
$paramsEnr = [];
if ($statusEnr !== '') {
    $whereEnr[] = "e.status = :st";
    $paramsEnr[':st'] = $statusEnr;
}
if ($searchEnr !== '') {
    $whereEnr[] = "(e.application_no LIKE :s OR e.applicant_name LIKE :s OR e.contact LIKE :s OR e.email LIKE :s OR c.title LIKE :s)";
    $paramsEnr[':s'] = "%{$searchEnr}%";
}
$whereSqlEnr = implode(' AND ', $whereEnr);

$totalEnrollments = 0;
$enrollments = [];
try {
    $cntStmt = $pdo->prepare("SELECT COUNT(*) FROM skill_course_enrollments e JOIN skill_courses c ON e.course_id = c.id WHERE {$whereSqlEnr}");
    $cntStmt->execute($paramsEnr);
    $totalEnrollments = (int)$cntStmt->fetchColumn();

    $offsetEnr = ($pageEnr - 1) * $perPageEnr;
    $stmtEnr = $pdo->prepare("
        SELECT e.*, c.title AS course_title, c.category AS course_category, u.name AS reviewer_name
        FROM skill_course_enrollments e
        JOIN skill_courses c ON e.course_id = c.id
        LEFT JOIN users u ON e.reviewed_by = u.id
        WHERE {$whereSqlEnr}
        ORDER BY e.id DESC
        LIMIT :limit OFFSET :offset
    ");
    foreach ($paramsEnr as $k => $v) {
        $stmtEnr->bindValue($k, $v);
    }
    $stmtEnr->bindValue(':limit', $perPageEnr, PDO::PARAM_INT);
    $stmtEnr->bindValue(':offset', $offsetEnr, PDO::PARAM_INT);
    $stmtEnr->execute();
    $enrollments = $stmtEnr->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $enrollments = [];
}

// Compute Metrics
$activeCourses = 0;
$pendingEnrollments = 0;
$enrolledCount = 0;
try {
    $activeCourses = (int)$pdo->query("SELECT COUNT(*) FROM skill_courses WHERE status = 'active'")->fetchColumn();
    $pendingEnrollments = (int)$pdo->query("SELECT COUNT(*) FROM skill_course_enrollments WHERE status = 'pending'")->fetchColumn();
    $enrolledCount = (int)$pdo->query("SELECT COUNT(*) FROM skill_course_enrollments WHERE status = 'enrolled'")->fetchColumn();
} catch (Throwable $e) {}

$indiaStatesJson = india_state_district_js();
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900" x-data="careerGuidanceManager('<?php echo $currentTab; ?>')">
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
                        <span class="p-2.5 rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                            <i class="fa-solid fa-graduation-cap text-2xl"></i>
                        </span>
                        <div>
                            <h1 class="text-2xl md:text-3xl font-black text-gray-900 dark:text-white tracking-tight">Career Guidance & Skills Training</h1>
                            <p class="text-xs md:text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                                Manage guidance CMS content, skill development courses, and student interest applications.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <a href="/career-guidance" target="_blank" class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-800 hover:bg-gray-900 text-white text-xs font-bold rounded-xl shadow-sm transition">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                        <span>View Public Page</span>
                    </a>

                    <button @click="openCreateCourseModal()" class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-sm transition transform hover:-translate-y-0.5 shadow-indigo-600/20">
                        <i class="fa-solid fa-plus text-xs"></i>
                        <span>Add New Course</span>
                    </button>
                </div>
            </div>

            <!-- 4 KPI Metrics -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-100 dark:border-gray-700 shadow-xs flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-lg flex-shrink-0">
                        <i class="fa-solid fa-book-open-reader"></i>
                    </div>
                    <div>
                        <span class="text-xs text-gray-400 font-bold uppercase block">Total Courses</span>
                        <span class="text-xl font-black text-gray-900 dark:text-white"><?php echo $totalCourses; ?></span>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-100 dark:border-gray-700 shadow-xs flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-lg flex-shrink-0">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <div>
                        <span class="text-xs text-gray-400 font-bold uppercase block">Active Programs</span>
                        <span class="text-xl font-black text-emerald-600 dark:text-emerald-400"><?php echo $activeCourses; ?></span>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-100 dark:border-gray-700 shadow-xs flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 flex items-center justify-center text-lg flex-shrink-0">
                        <i class="fa-solid fa-user-graduate"></i>
                    </div>
                    <div>
                        <span class="text-xs text-gray-400 font-bold uppercase block">Total Enrolled</span>
                        <span class="text-xl font-black text-blue-600 dark:text-blue-400"><?php echo $totalEnrollments; ?></span>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-100 dark:border-gray-700 shadow-xs flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 flex items-center justify-center text-lg flex-shrink-0">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                    <div>
                        <span class="text-xs text-gray-400 font-bold uppercase block">Pending Inquiries</span>
                        <span class="text-xl font-black text-amber-600 dark:text-amber-400"><?php echo $pendingEnrollments; ?></span>
                    </div>
                </div>
            </div>

            <!-- Tab Navigation Bar -->
            <div class="flex items-center gap-2 border-b border-gray-200 dark:border-gray-700 mb-6 pb-2">
                <button type="button" @click="activeTab = 'cms'" 
                        class="px-4 py-2 text-xs font-bold rounded-xl transition flex items-center gap-2"
                        :class="activeTab === 'cms' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'">
                    <i class="fa-solid fa-pen-to-square"></i>
                    <span>CMS Page Content</span>
                </button>

                <button type="button" @click="activeTab = 'courses'" 
                        class="px-4 py-2 text-xs font-bold rounded-xl transition flex items-center gap-2"
                        :class="activeTab === 'courses' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'">
                    <i class="fa-solid fa-layer-group"></i>
                    <span>Courses & Workshops (<?php echo $totalCourses; ?>)</span>
                </button>

                <button type="button" @click="activeTab = 'enrollments'" 
                        class="px-4 py-2 text-xs font-bold rounded-xl transition flex items-center gap-2"
                        :class="activeTab === 'enrollments' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'">
                    <i class="fa-solid fa-clipboard-user"></i>
                    <span>Student Enrollments (<?php echo $totalEnrollments; ?>)</span>
                    <span x-show="<?php echo $pendingEnrollments; ?> > 0" class="px-1.5 py-0.2 bg-amber-500 text-white rounded-full text-[10px]"><?php echo $pendingEnrollments; ?></span>
                </button>
            </div>

            <!-- ============================================================ -->
            <!-- TAB 1: CMS PAGE CONTENT (ABOUT US PATTERN) -->
            <!-- ============================================================ -->
            <div x-show="activeTab === 'cms'" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 md:p-8">
                <div class="border-b border-gray-100 dark:border-gray-700 pb-4 mb-6">
                    <h3 class="text-lg font-black text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fa-solid fa-file-lines text-indigo-600"></i>
                        <span>Manage Career Guidance Page Content</span>
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Customize headline, mission statement, counseling support text, and banner imagery for the public guidance portal.
                    </p>
                </div>

                <form action="actions/career_guidance_logic.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="action" value="update_cms">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Page Title -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">Page Title *</label>
                            <input type="text" name="career_guidance_title" required
                                   value="<?php echo htmlspecialchars($cmsSettings['career_guidance_title'] ?? 'Career Guidance & Skills Training'); ?>"
                                   class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <!-- Subtitle / Tagline -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">Subtitle / Tagline</label>
                            <input type="text" name="career_guidance_subtitle"
                                   value="<?php echo htmlspecialchars($cmsSettings['career_guidance_subtitle'] ?? 'Empowering youth with market-ready vocational skills, interview mentorship, and career counseling.'); ?>"
                                   class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <!-- Main Overview Description -->
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">Main Overview Description</label>
                            <textarea name="career_guidance_desc" rows="4"
                                      class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"><?php echo htmlspecialchars($cmsSettings['career_guidance_desc'] ?? ''); ?></textarea>
                        </div>

                        <!-- Free Counseling Guidance Advice Text -->
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">One-on-One Counseling Highlight</label>
                            <textarea name="career_guidance_counseling_text" rows="3"
                                      class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"><?php echo htmlspecialchars($cmsSettings['career_guidance_counseling_text'] ?? ''); ?></textarea>
                        </div>

                        <!-- Helpline Contact -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">Career Helpline / WhatsApp</label>
                            <input type="text" name="career_guidance_helpline"
                                   value="<?php echo htmlspecialchars($cmsSettings['career_guidance_helpline'] ?? '+91 98765 43210'); ?>"
                                   class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <!-- Contact Email -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">Career Support Email</label>
                            <input type="email" name="career_guidance_email"
                                   value="<?php echo htmlspecialchars($cmsSettings['career_guidance_email'] ?? 'careers@ngocare.org'); ?>"
                                   class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <!-- Banner Image Upload -->
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">Banner Image (Optional - JPG, PNG, WEBP)</label>
                            <div class="flex items-center gap-4">
                                <?php if (!empty($cmsSettings['career_guidance_banner_image'])): ?>
                                    <img src="../<?php echo htmlspecialchars($cmsSettings['career_guidance_banner_image']); ?>" class="w-20 h-14 object-cover rounded-lg border border-gray-200">
                                <?php endif; ?>
                                <input type="file" name="career_guidance_banner" accept="image/*"
                                       class="text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-gray-100 dark:border-gray-700 flex justify-end">
                        <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-sm transition">
                            Save CMS Changes
                        </button>
                    </div>
                </form>
            </div>

            <!-- ============================================================ -->
            <!-- TAB 2: SKILL COURSES & WORKSHOPS CRUD -->
            <!-- ============================================================ -->
            <div x-show="activeTab === 'courses'" class="space-y-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="p-4 md:p-6 border-b border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row items-center justify-between gap-3">
                        <h3 class="text-base font-black text-gray-900 dark:text-white">Skill Courses Roster</h3>
                        <button type="button" @click="openCreateCourseModal()" class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl transition flex items-center gap-1.5">
                            <i class="fa-solid fa-plus text-xs"></i>
                            <span>Add Course</span>
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                            <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-500 dark:text-gray-400 font-bold uppercase text-[10px] tracking-wider">
                                <tr>
                                    <th class="py-3.5 px-4">Course Title & Category</th>
                                    <th class="py-3.5 px-4">Mode & Duration</th>
                                    <th class="py-3.5 px-4">Fee Structure</th>
                                    <th class="py-3.5 px-4">Instructor / Center</th>
                                    <th class="py-3.5 px-4 text-center">Enrollments</th>
                                    <th class="py-3.5 px-4 text-center">Status</th>
                                    <th class="py-3.5 px-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                <?php if (empty($courses)): ?>
                                    <tr>
                                        <td colspan="7" class="py-8 text-center text-gray-400">No skill courses created yet. Click "Add Course" to create one.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($courses as $c): ?>
                                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition">
                                            <td class="py-3 px-4">
                                                <div class="font-bold text-gray-900 dark:text-white text-xs"><?php echo htmlspecialchars($c['title']); ?></div>
                                                <span class="inline-block mt-0.5 px-2 py-0.5 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 rounded text-[10px] font-bold">
                                                    <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $c['category']))); ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-4">
                                                <div class="font-semibold text-gray-800 dark:text-gray-200"><?php echo htmlspecialchars($c['mode']); ?></div>
                                                <div class="text-[11px] text-gray-400"><?php echo htmlspecialchars($c['duration']); ?></div>
                                            </td>
                                            <td class="py-3 px-4">
                                                <span class="px-2 py-0.5 rounded text-[11px] font-bold <?php echo $c['fee_type'] === '100% Free' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'; ?>">
                                                    <?php echo htmlspecialchars($c['fee_type']); ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-4">
                                                <div class="text-gray-800 dark:text-gray-200 font-medium"><?php echo htmlspecialchars($c['instructor'] ?: 'NGO Faculty'); ?></div>
                                                <div class="text-[10px] text-gray-400"><?php echo htmlspecialchars($c['location'] ?: 'Field Center'); ?></div>
                                            </td>
                                            <td class="py-3 px-4 text-center">
                                                <span class="font-black text-indigo-600 dark:text-indigo-400"><?php echo (int)($c['enrollments_count'] ?? 0); ?></span>
                                            </td>
                                            <td class="py-3 px-4 text-center">
                                                <form action="actions/career_guidance_logic.php" method="POST" class="inline">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                    <input type="hidden" name="action" value="toggle_course_status">
                                                    <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                                    <button type="submit" class="px-2.5 py-1 rounded-full text-[10px] font-bold transition <?php echo $c['status'] === 'active' ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">
                                                        <?php echo ucfirst($c['status']); ?>
                                                    </button>
                                                </form>
                                            </td>
                                            <td class="py-3 px-4 text-right space-x-1">
                                                <button type="button" @click="editCourse(<?php echo htmlspecialchars(json_encode($c)); ?>)" class="p-1.5 text-gray-500 hover:text-indigo-600 transition" title="Edit Course">
                                                    <i class="fa-regular fa-pen-to-square"></i>
                                                </button>
                                                <form action="actions/career_guidance_logic.php" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this course? All associated enrollments will also be removed.');">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                    <input type="hidden" name="action" value="delete_course">
                                                    <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                                    <button type="submit" class="p-1.5 text-gray-400 hover:text-rose-600 transition" title="Delete Course">
                                                        <i class="fa-regular fa-trash-can"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Courses Pagination Footer -->
                    <?php echo render_admin_pagination($totalCourses, $pageCourses, $perPageCourses, ['tab' => 'courses'], 'p_c'); ?>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- TAB 3: STUDENT ENROLLMENTS ROSTER -->
            <!-- ============================================================ -->
            <div x-show="activeTab === 'enrollments'" class="space-y-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="p-4 md:p-6 border-b border-gray-100 dark:border-gray-700 space-y-3">
                        <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                            <div>
                                <h3 class="text-base font-black text-gray-900 dark:text-white">Student Course Enrollments</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Applications submitted by candidates interested in skill workshops.</p>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300 shrink-0">
                                <?php echo $totalEnrollments; ?> applications
                            </span>
                        </div>

                        <!-- Filter Form -->
                        <form method="GET" action="career_guidance_manager.php" class="flex flex-wrap items-center gap-2 pt-1">
                            <input type="hidden" name="tab" value="enrollments">
                            <input type="text" name="search_enr" value="<?php echo htmlspecialchars($searchEnr); ?>" placeholder="Search student, contact, course..." class="flex-1 min-w-[150px] bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3 py-1.5 text-xs text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                            
                            <select name="status_enr" onchange="this.form.submit()" class="bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3 py-1.5 text-xs text-gray-900 dark:text-white">
                                <option value="">All Statuses</option>
                                <option value="pending" <?php echo $statusEnr === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="contacted" <?php echo $statusEnr === 'contacted' ? 'selected' : ''; ?>>Contacted</option>
                                <option value="enrolled" <?php echo $statusEnr === 'enrolled' ? 'selected' : ''; ?>>Enrolled</option>
                                <option value="completed" <?php echo $statusEnr === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $statusEnr === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>

                            <button type="submit" class="px-3 py-1.5 bg-slate-800 text-white rounded-xl text-xs font-bold hover:bg-slate-900">Filter</button>
                            <?php if ($searchEnr || $statusEnr): ?>
                                <a href="career_guidance_manager.php?tab=enrollments" class="px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-xl text-xs font-bold hover:bg-gray-200">Reset</a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                            <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-500 dark:text-gray-400 font-bold uppercase text-[10px] tracking-wider">
                                <tr>
                                    <th class="py-3.5 px-4">App No / Student</th>
                                    <th class="py-3.5 px-4">Contact Info</th>
                                    <th class="py-3.5 px-4">Applied Course</th>
                                    <th class="py-3.5 px-4">Location & Qualification</th>
                                    <th class="py-3.5 px-4 text-center">Status</th>
                                    <th class="py-3.5 px-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                <?php if (empty($enrollments)): ?>
                                    <tr>
                                        <td colspan="6" class="py-8 text-center text-gray-400">No student enrollment forms received yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($enrollments as $enr): ?>
                                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition">
                                            <td class="py-3 px-4">
                                                <span class="font-mono text-[11px] font-bold text-indigo-600 dark:text-indigo-400 block"><?php echo htmlspecialchars($enr['application_no']); ?></span>
                                                <span class="font-bold text-gray-900 dark:text-white"><?php echo htmlspecialchars($enr['applicant_name']); ?></span>
                                                <span class="text-[10px] text-gray-400 block"><?php echo date('d M Y', strtotime($enr['applied_date'])); ?></span>
                                            </td>
                                            <td class="py-3 px-4">
                                                <div class="font-medium text-gray-800 dark:text-gray-200">
                                                    <i class="fa-solid fa-phone text-indigo-500 mr-1 text-[10px]"></i><?php echo htmlspecialchars($enr['contact']); ?>
                                                </div>
                                                <?php if (!empty($enr['email'])): ?>
                                                    <div class="text-[11px] text-gray-400"><?php echo htmlspecialchars($enr['email']); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-3 px-4">
                                                <span class="font-bold text-gray-900 dark:text-white block"><?php echo htmlspecialchars($enr['course_title']); ?></span>
                                            </td>
                                            <td class="py-3 px-4">
                                                <div><?php echo htmlspecialchars($enr['district'] . ', ' . $enr['state']); ?></div>
                                                <div class="text-[11px] text-gray-400"><?php echo htmlspecialchars($enr['qualification']); ?></div>
                                            </td>
                                            <td class="py-3 px-4 text-center">
                                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold <?php
                                                    echo match($enr['status']) {
                                                        'enrolled' => 'bg-emerald-100 text-emerald-800',
                                                        'contacted' => 'bg-blue-100 text-blue-800',
                                                        'completed' => 'bg-purple-100 text-purple-800',
                                                        'cancelled' => 'bg-rose-100 text-rose-800',
                                                        default => 'bg-amber-100 text-amber-800'
                                                    };
                                                ?>">
                                                    <?php echo ucfirst($enr['status']); ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-4 text-right space-x-1.5">
                                                <!-- WhatsApp Contact Button -->
                                                <a href="https://wa.me/91<?php echo preg_replace('/[^0-9]/', '', $enr['contact']); ?>?text=<?php echo urlencode("Namaste {$enr['applicant_name']}, we received your interest form for our {$enr['course_title']} skill program at our NGO center. Let us know when you are available to discuss the batch schedule."); ?>"
                                                   target="_blank" class="p-1.5 text-emerald-600 hover:text-emerald-700" title="Message on WhatsApp">
                                                    <i class="fa-brands fa-whatsapp text-sm"></i>
                                                </a>

                                                <button type="button" @click="openEnrollmentModal(<?php echo htmlspecialchars(json_encode($enr)); ?>)" class="p-1.5 text-indigo-600 hover:text-indigo-800" title="Review & Update Status">
                                                    <i class="fa-solid fa-arrow-right-arrow-left"></i>
                                                </button>

                                                <form action="actions/career_guidance_logic.php" method="POST" class="inline" onsubmit="return confirm('Delete this enrollment record?');">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                    <input type="hidden" name="action" value="delete_enrollment">
                                                    <input type="hidden" name="id" value="<?php echo $enr['id']; ?>">
                                                    <button type="submit" class="p-1.5 text-gray-400 hover:text-rose-600" title="Delete">
                                                        <i class="fa-regular fa-trash-can"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Enrollments Pagination Footer -->
                    <?php echo render_admin_pagination($totalEnrollments, $pageEnr, $perPageEnr, ['tab' => 'enrollments', 'search_enr' => $searchEnr, 'status_enr' => $statusEnr], 'p_e'); ?>
                </div>
            </div>

        </main>
    </div>

    <!-- ============================================================ -->
    <!-- ADD / EDIT COURSE MODAL -->
    <!-- ============================================================ -->
    <div x-show="showCourseModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900/60 transition-opacity" @click="showCourseModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-gray-100 dark:border-gray-700">
                <form action="actions/career_guidance_logic.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="action" value="save_course">
                    <input type="hidden" name="id" :value="courseForm.id">

                    <div class="p-6 bg-gradient-to-r from-indigo-600 to-indigo-800 text-white relative">
                        <button type="button" @click="showCourseModal = false" class="absolute right-5 top-5 text-white/80 hover:text-white">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                        <h3 class="text-xl font-black" x-text="courseForm.id ? 'Edit Skill Course' : 'Create New Skill Course'"></h3>
                        <p class="text-xs text-white/80 mt-0.5">Define duration, curriculum, mode, and eligibility for vocational courses.</p>
                    </div>

                    <div class="p-6 max-h-[65vh] overflow-y-auto space-y-4 text-xs">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Course Title *</label>
                                <input type="text" name="title" x-model="courseForm.title" required placeholder="e.g. Digital Literacy & Office Productivity"
                                       class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                            </div>

                            <div>
                                <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Category</label>
                                <select name="category" x-model="courseForm.category" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 dark:text-white">
                                    <option value="vocational">Vocational Trades</option>
                                    <option value="computer_it">Computer & Digital IT</option>
                                    <option value="soft_skills">Soft Skills & Spoken English</option>
                                    <option value="competitive_exams">Competitive Exams Prep</option>
                                    <option value="entrepreneurship">Micro-Entrepreneurship</option>
                                    <option value="healthcare_aid">Healthcare & First Aid</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>

                            <div>
                                <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Training Mode</label>
                                <select name="mode" x-model="courseForm.mode" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 dark:text-white">
                                    <option value="Offline">Offline (Classroom / Field)</option>
                                    <option value="Online">Online (Live Virtual)</option>
                                    <option value="Hybrid">Hybrid (Classroom + Online)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Duration</label>
                                <input type="text" name="duration" x-model="courseForm.duration" placeholder="e.g. 3 Months (60 Hours)"
                                       class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 dark:text-white">
                            </div>

                            <div>
                                <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Fee Type</label>
                                <select name="fee_type" x-model="courseForm.fee_type" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 dark:text-white">
                                    <option value="100% Free">100% Free</option>
                                    <option value="Subsidized Aid">Subsidized Aid</option>
                                    <option value="Scholarship Based">Scholarship Based</option>
                                </select>
                            </div>

                            <div>
                                <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Eligibility</label>
                                <input type="text" name="eligibility" x-model="courseForm.eligibility" placeholder="e.g. 10th / 12th Pass"
                                       class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 dark:text-white">
                            </div>

                            <div>
                                <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Instructor / Trainer</label>
                                <input type="text" name="instructor" x-model="courseForm.instructor" placeholder="e.g. Rajesh Verma"
                                       class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 dark:text-white">
                            </div>

                            <div>
                                <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Batch Start Date</label>
                                <input type="date" name="batch_start_date" x-model="courseForm.batch_start_date"
                                       class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 dark:text-white">
                            </div>

                            <div>
                                <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Max Seats</label>
                                <input type="number" name="max_seats" x-model="courseForm.max_seats"
                                       class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 dark:text-white">
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Course Overview & Description</label>
                                <textarea name="description" x-model="courseForm.description" rows="3" placeholder="Overview of skills learned in this program..."
                                          class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3.5 py-2 text-xs text-gray-900 dark:text-white"></textarea>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Curriculum Modules (one per line)</label>
                                <textarea name="curriculum" x-model="courseForm.curriculum" rows="3" placeholder="Module 1: Basic Foundation&#10;Module 2: Practical Lab"
                                          class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3.5 py-2 text-xs text-gray-900 dark:text-white"></textarea>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Cover Image (Optional)</label>
                                <input type="file" name="course_image" accept="image/*" class="text-xs text-gray-500">
                            </div>
                        </div>
                    </div>

                    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-100 dark:border-gray-700 flex justify-between">
                        <button type="button" @click="showCourseModal = false" class="px-4 py-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-bold text-xs rounded-xl border">Cancel</button>
                        <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl">Save Course</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- ENROLLMENT STATUS UPDATE MODAL -->
    <!-- ============================================================ -->
    <div x-show="showEnrollmentModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900/60 transition-opacity" @click="showEnrollmentModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-100 dark:border-gray-700">
                <form action="actions/career_guidance_logic.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="action" value="update_enrollment_status">
                    <input type="hidden" name="id" :value="activeEnrollment?.id">

                    <div class="p-6 bg-gradient-to-r from-indigo-700 to-indigo-900 text-white relative">
                        <button type="button" @click="showEnrollmentModal = false" class="absolute right-5 top-5 text-white/80 hover:text-white">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                        <span class="font-mono text-xs text-indigo-200" x-text="activeEnrollment?.application_no"></span>
                        <h3 class="text-lg font-black mt-1" x-text="'Candidate: ' + (activeEnrollment?.applicant_name || '')"></h3>
                        <p class="text-xs text-white/80 mt-0.5" x-text="'Course: ' + (activeEnrollment?.course_title || '')"></p>
                    </div>

                    <div class="p-6 space-y-4 text-xs">
                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Application Status</label>
                            <select name="status" x-model="activeEnrollment.status" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 dark:text-white">
                                <option value="pending">Pending (New Submission)</option>
                                <option value="contacted">Contacted (Call / WhatsApp done)</option>
                                <option value="enrolled">Enrolled (Confirmed in batch)</option>
                                <option value="completed">Completed (Graduated)</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Admin / Counselor Notes</label>
                            <textarea name="admin_notes" x-model="activeEnrollment.admin_notes" rows="3" placeholder="Batch timings conveyed, student interested in morning batch..."
                                      class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3.5 py-2 text-xs text-gray-900 dark:text-white"></textarea>
                        </div>
                    </div>

                    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-100 dark:border-gray-700 flex justify-between">
                        <button type="button" @click="showEnrollmentModal = false" class="px-4 py-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-bold text-xs rounded-xl border">Cancel</button>
                        <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('careerGuidanceManager', (initialTab = 'cms') => ({
        activeTab: initialTab,
        showCourseModal: false,
        showEnrollmentModal: false,
        activeEnrollment: null,
        
        courseForm: {
            id: '',
            title: '',
            category: 'vocational',
            mode: 'Offline',
            duration: '3 Months',
            fee_type: '100% Free',
            eligibility: '10th / 12th Pass',
            instructor: '',
            batch_start_date: '',
            max_seats: 30,
            description: '',
            curriculum: ''
        },

        openCreateCourseModal() {
            this.courseForm = {
                id: '',
                title: '',
                category: 'vocational',
                mode: 'Offline',
                duration: '3 Months',
                fee_type: '100% Free',
                eligibility: '10th / 12th Pass',
                instructor: '',
                batch_start_date: '',
                max_seats: 30,
                description: '',
                curriculum: ''
            };
            this.showCourseModal = true;
        },

        editCourse(course) {
            this.courseForm = {
                id: course.id,
                title: course.title || '',
                category: course.category || 'vocational',
                mode: course.mode || 'Offline',
                duration: course.duration || '3 Months',
                fee_type: course.fee_type || '100% Free',
                eligibility: course.eligibility || '',
                instructor: course.instructor || '',
                batch_start_date: course.batch_start_date || '',
                max_seats: course.max_seats || 30,
                description: course.description || '',
                curriculum: course.curriculum || ''
            };
            this.showCourseModal = true;
        },

        openEnrollmentModal(enr) {
            this.activeEnrollment = Object.assign({}, enr);
            this.showEnrollmentModal = true;
        }
    }));
});
</script>

<?php require 'includes/footer.php'; ?>
