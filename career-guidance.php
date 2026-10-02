<?php
// ============================================================
// career-guidance.php
// Public Career Guidance & Skills Training CMS Portal
// ============================================================

require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/india_locations.php';

$csrfToken = generateCsrfToken();

// 1. Fetch CMS Settings
$cmsSettings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'career_guidance_%'");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $cmsSettings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Throwable $e) {
    $cmsSettings = [];
}

// 2. Fetch Active Skill Courses
$courses = [];
if (dbTableExists($pdo, 'skill_courses')) {
    try {
        $stmt = $pdo->query("
            SELECT * 
            FROM skill_courses 
            WHERE status = 'active' 
            ORDER BY id DESC
        ");
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $courses = [];
    }
}

// Compute Category counts
$totalCount = count($courses);
$computerCount = 0;
$vocationalCount = 0;
$softSkillsCount = 0;
$healthcareCount = 0;
$entrepreneurshipCount = 0;

foreach ($courses as $c) {
    $cat = $c['category'] ?? 'vocational';
    if ($cat === 'computer_it') $computerCount++;
    elseif ($cat === 'vocational') $vocationalCount++;
    elseif ($cat === 'soft_skills') $softSkillsCount++;
    elseif ($cat === 'healthcare_aid') $healthcareCount++;
    elseif ($cat === 'entrepreneurship') $entrepreneurshipCount++;
}

$indiaStatesJson = india_state_district_js();
$indiaStatesList = india_state_list();

// Auto-select course if ?enroll_course_id=XX
$autoCourseId = filter_input(INPUT_GET, 'enroll_course_id', FILTER_VALIDATE_INT) ?: (filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT) ?: '');
$viewCourseId = filter_input(INPUT_GET, 'view_course_id', FILTER_VALIDATE_INT) ?: '';

require 'includes/header.php';
?>

<div class="bg-gradient-to-b from-[#F0FDFD] via-white to-[#F8FAFC] min-h-screen py-8 md:py-14"
     x-data="careerGuidancePortal(
         <?php echo htmlspecialchars(json_encode($courses, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>,
         <?php echo $autoCourseId ? (int)$autoCourseId : "''"; ?>,
         <?php echo $viewCourseId ? (int)$viewCourseId : "''"; ?>
     )"
     x-cloak>

    <div class="container mx-auto px-4 max-w-7xl">
        
        <!-- Top Navigation Switcher (Jobs <-> Skills Guidance) -->
        <div class="flex items-center justify-center mb-8">
            <div class="inline-flex p-1 bg-gray-100 rounded-2xl shadow-inner border border-gray-200">
                <a href="careers.php" class="px-5 py-2 rounded-xl text-xs font-bold text-gray-600 hover:text-gray-900 transition flex items-center gap-2">
                    <i class="fa-solid fa-briefcase text-teal-600"></i>
                    <span>Job Openings</span>
                </a>
                <a href="career-guidance.php" class="px-5 py-2 rounded-xl text-xs font-bold bg-white text-indigo-700 shadow-sm transition flex items-center gap-2">
                    <i class="fa-solid fa-graduation-cap text-indigo-600"></i>
                    <span>Career Guidance & Skills</span>
                </a>
            </div>
        </div>

        <!-- CMS Hero Banner -->
        <div class="max-w-4xl mx-auto text-center mb-8 md:mb-12">
            <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white shadow-xs border border-indigo-100 text-indigo-700 text-xs sm:text-sm font-bold uppercase tracking-wider">
                <i class="fa-solid fa-graduation-cap text-indigo-600"></i>
                <span>Youth Empowerment • Free & Subsidized Training</span>
            </span>

            <h1 class="mt-4 text-3xl sm:text-4xl md:text-5xl font-black text-gray-900 tracking-tight">
                <?php echo htmlspecialchars($cmsSettings['career_guidance_title'] ?? 'Career Guidance & Skills Training'); ?>
            </h1>

            <p class="mt-3 text-sm sm:text-base md:text-lg text-gray-600 max-w-2xl mx-auto leading-relaxed">
                <?php echo htmlspecialchars($cmsSettings['career_guidance_subtitle'] ?? 'Empowering youth with market-ready vocational skills, interview mentorship, and career counseling.'); ?>
            </p>

            <!-- Free Counseling & Helpline Banner Box -->
            <div class="mt-8 p-5 md:p-6 rounded-3xl bg-gradient-to-r from-indigo-50 via-purple-50 to-blue-50 border border-indigo-100/80 shadow-sm text-left max-w-3xl mx-auto">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="flex items-start gap-3.5">
                        <div class="w-11 h-11 rounded-2xl bg-indigo-600 text-white flex items-center justify-center text-lg flex-shrink-0 shadow-md shadow-indigo-600/30">
                            <i class="fa-solid fa-comments"></i>
                        </div>
                        <div>
                            <h4 class="text-xs md:text-sm font-black text-indigo-950">Free 1-on-1 Career Mentorship & Counseling</h4>
                            <p class="text-xs text-indigo-900/80 mt-1 max-w-md">
                                <?php echo htmlspecialchars($cmsSettings['career_guidance_counseling_text'] ?? 'Get advice on job opportunities, resume review, and skill selection.'); ?>
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 flex-shrink-0 w-full sm:w-auto">
                        <?php if (!empty($cmsSettings['career_guidance_helpline'])): ?>
                            <a href="tel:<?php echo htmlspecialchars(preg_replace('/[^0-9+]/', '', $cmsSettings['career_guidance_helpline'])); ?>"
                               class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-sm">
                                <i class="fa-solid fa-phone text-xs"></i>
                                <span><?php echo htmlspecialchars($cmsSettings['career_guidance_helpline']); ?></span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter & Search Panel -->
        <div class="bg-white rounded-3xl shadow-xl border border-indigo-50 p-5 md:p-7 mb-10 transition-all">
            
            <!-- Category Tabs -->
            <div class="mb-6">
                <div class="flex items-center justify-between mb-2.5">
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Filter by Skill Track:</span>
                    <span class="text-xs font-semibold text-indigo-600" x-text="filteredCourses.length + ' courses available'"></span>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" @click="activeCategory = ''" 
                            class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 shadow-xs"
                            :class="activeCategory === '' ? 'bg-indigo-600 text-white shadow-indigo-200' : 'bg-gray-50 text-gray-600 hover:bg-gray-100 border border-gray-100'">
                        <i class="fa-solid fa-layer-group"></i>
                        <span>All Tracks</span>
                        <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="activeCategory === '' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-700'"><?php echo $totalCount; ?></span>
                    </button>

                    <button type="button" @click="activeCategory = 'computer_it'" 
                            class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 shadow-xs"
                            :class="activeCategory === 'computer_it' ? 'bg-indigo-600 text-white shadow-indigo-200' : 'bg-gray-50 text-gray-600 hover:bg-gray-100 border border-gray-100'">
                        <i class="fa-solid fa-laptop-code"></i>
                        <span>Computer & Digital IT</span>
                        <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="activeCategory === 'computer_it' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-700'"><?php echo $computerCount; ?></span>
                    </button>

                    <button type="button" @click="activeCategory = 'healthcare_aid'" 
                            class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 shadow-xs"
                            :class="activeCategory === 'healthcare_aid' ? 'bg-indigo-600 text-white shadow-indigo-200' : 'bg-gray-50 text-gray-600 hover:bg-gray-100 border border-gray-100'">
                        <i class="fa-solid fa-user-nurse"></i>
                        <span>Healthcare & First Aid</span>
                        <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="activeCategory === 'healthcare_aid' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-700'"><?php echo $healthcareCount; ?></span>
                    </button>

                    <button type="button" @click="activeCategory = 'soft_skills'" 
                            class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 shadow-xs"
                            :class="activeCategory === 'soft_skills' ? 'bg-indigo-600 text-white shadow-indigo-200' : 'bg-gray-50 text-gray-600 hover:bg-gray-100 border border-gray-100'">
                        <i class="fa-solid fa-comments"></i>
                        <span>Spoken English & Interview</span>
                        <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="activeCategory === 'soft_skills' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-700'"><?php echo $softSkillsCount; ?></span>
                    </button>

                    <button type="button" @click="activeCategory = 'entrepreneurship'" 
                            class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 shadow-xs"
                            :class="activeCategory === 'entrepreneurship' ? 'bg-indigo-600 text-white shadow-indigo-200' : 'bg-gray-50 text-gray-600 hover:bg-gray-100 border border-gray-100'">
                        <i class="fa-solid fa-shop"></i>
                        <span>Micro-Entrepreneurship</span>
                        <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="activeCategory === 'entrepreneurship' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-700'"><?php echo $entrepreneurshipCount; ?></span>
                    </button>
                </div>
            </div>

            <!-- Search & Mode Filter -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">
                        <i class="fa-solid fa-magnifying-glass text-indigo-600 mr-1"></i> Search Course or Skill
                    </label>
                    <input type="text" x-model="searchKeyword" placeholder="e.g. Computer, First Aid, English, Marketing..."
                           class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">
                        <i class="fa-solid fa-chalkboard-user text-indigo-600 mr-1"></i> Mode of Learning
                    </label>
                    <select x-model="selectedMode" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">All Modes</option>
                        <option value="Offline">Offline Classroom</option>
                        <option value="Online">Online Virtual</option>
                        <option value="Hybrid">Hybrid</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Courses Cards Grid -->
        <div class="mb-14">
            <div x-show="filteredCourses.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <template x-for="course in filteredCourses" :key="course.id">
                    <div class="bg-white rounded-3xl p-6 shadow-md hover:shadow-2xl border border-gray-100 hover:border-indigo-300 transition-all duration-300 flex flex-col justify-between relative overflow-hidden group">
                        
                        <!-- Top Accent Line -->
                        <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-indigo-500 via-purple-500 to-blue-500"></div>

                        <div>
                            <!-- Header Badges -->
                            <div class="flex items-center justify-between gap-2 mb-3.5">
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-100" x-text="getCategoryLabel(course.category)"></span>
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider"
                                      :class="course.fee_type === '100% Free' ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-amber-50 text-amber-700 border border-amber-100'"
                                      x-text="course.fee_type"></span>
                            </div>

                            <!-- Title -->
                            <h3 class="text-lg md:text-xl font-black text-gray-900 group-hover:text-indigo-600 transition-colors line-clamp-2 mb-2" x-text="course.title"></h3>

                            <!-- Highlights -->
                            <div class="space-y-1.5 mb-4">
                                <div class="flex items-center gap-2 text-xs text-gray-600">
                                    <i class="fa-solid fa-clock text-indigo-600 w-4 text-center"></i>
                                    <span>Duration: <strong class="text-gray-900" x-text="course.duration"></strong></span>
                                </div>
                                <div class="flex items-center gap-2 text-xs text-gray-600">
                                    <i class="fa-solid fa-chalkboard text-indigo-600 w-4 text-center"></i>
                                    <span>Learning Mode: <strong class="text-gray-900" x-text="course.mode"></strong></span>
                                </div>
                                <div class="flex items-center gap-2 text-xs text-gray-600" x-show="course.eligibility">
                                    <i class="fa-solid fa-graduation-cap text-indigo-600 w-4 text-center"></i>
                                    <span>Eligibility: <span class="text-gray-700" x-text="course.eligibility"></span></span>
                                </div>
                            </div>

                            <p class="text-xs text-gray-500 line-clamp-3 mb-5 leading-relaxed" x-text="course.description"></p>
                        </div>

                        <!-- Action Buttons -->
                        <div class="pt-4 border-t border-gray-100 flex items-center gap-2">
                            <button type="button" @click="openDetailsModal(course)"
                                    class="flex-1 py-2.5 px-3 bg-gray-100 hover:bg-gray-200 text-gray-800 text-xs font-bold rounded-xl transition flex items-center justify-center gap-1.5">
                                <i class="fa-regular fa-eye"></i>
                                <span>Syllabus</span>
                            </button>

                            <button type="button" @click="openEnrollModal(course)"
                                    class="flex-1 py-2.5 px-3 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-md shadow-indigo-600/20 transition flex items-center justify-center gap-1.5">
                                <i class="fa-solid fa-user-plus text-xs"></i>
                                <span>Enroll Free</span>
                            </button>
                        </div>

                    </div>
                </template>
            </div>

            <!-- Empty State -->
            <div x-show="filteredCourses.length === 0" class="bg-white rounded-3xl p-12 text-center border border-gray-100 shadow-sm max-w-lg mx-auto">
                <div class="w-16 h-16 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center text-2xl mx-auto mb-4">
                    <i class="fa-solid fa-book-open"></i>
                </div>
                <h3 class="text-lg font-black text-gray-900">No Courses Match Your Filter</h3>
                <p class="text-xs text-gray-500 mt-2">Try clearing your search keyword or switching skill tracks to view all programs.</p>
                <button type="button" @click="activeCategory = ''; searchKeyword = ''; selectedMode = ''" class="mt-4 px-4 py-2 bg-indigo-600 text-white text-xs font-bold rounded-xl">
                    View All Courses
                </button>
            </div>
        </div>

    </div>

    <!-- ============================================================ -->
    <!-- 1. COURSE DETAILS MODAL -->
    <!-- ============================================================ -->
    <div x-show="showDetailsModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900/60 transition-opacity" @click="showDetailsModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-gray-100">
                <div class="p-6 md:p-8 bg-gradient-to-r from-indigo-700 to-indigo-900 text-white relative">
                    <button type="button" @click="showDetailsModal = false" class="absolute right-5 top-5 text-white/80 hover:text-white">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                    <span class="px-2.5 py-0.5 rounded bg-white/20 text-white text-[10px] font-bold uppercase tracking-wider" x-text="getCategoryLabel(activeCourse?.category)"></span>
                    <h2 class="text-xl md:text-2xl font-black mt-2" x-text="activeCourse?.title"></h2>
                    <div class="flex flex-wrap gap-3 mt-3 text-xs text-indigo-200">
                        <span><i class="fa-solid fa-clock mr-1"></i><span x-text="activeCourse?.duration"></span></span>
                        <span><i class="fa-solid fa-chalkboard mr-1"></i><span x-text="activeCourse?.mode"></span></span>
                        <span><i class="fa-solid fa-tag mr-1"></i><span x-text="activeCourse?.fee_type"></span></span>
                    </div>
                </div>

                <div class="p-6 md:p-8 max-h-[60vh] overflow-y-auto space-y-6 text-xs md:text-sm text-gray-700">
                    <div>
                        <h4 class="text-xs font-black uppercase tracking-wider text-gray-400 mb-2">Program Overview</h4>
                        <p class="leading-relaxed" x-text="activeCourse?.description"></p>
                    </div>

                    <div x-show="activeCourse?.curriculum">
                        <h4 class="text-xs font-black uppercase tracking-wider text-gray-400 mb-2">Modules & Practical Curriculum</h4>
                        <div class="p-4 rounded-2xl bg-indigo-50/50 border border-indigo-100 text-indigo-950 whitespace-pre-line leading-relaxed" x-text="activeCourse?.curriculum"></div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 p-4 rounded-2xl bg-gray-50 border border-gray-100 text-xs">
                        <div>
                            <span class="text-gray-400 font-bold block">Eligibility</span>
                            <span class="font-bold text-gray-900" x-text="activeCourse?.eligibility || 'Open to All'"></span>
                        </div>
                        <div>
                            <span class="text-gray-400 font-bold block">Instructor / Faculty</span>
                            <span class="font-bold text-gray-900" x-text="activeCourse?.instructor || 'NGO Expert Faculty'"></span>
                        </div>
                    </div>
                </div>

                <div class="p-4 md:p-6 bg-gray-50 border-t border-gray-100 flex justify-between">
                    <button type="button" @click="showDetailsModal = false" class="px-5 py-2.5 bg-white text-gray-700 text-xs font-bold rounded-xl border">Close</button>
                    <button type="button" @click="enrollFromDetails()" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl">Enroll in this Program</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- 2. ENROLL / REGISTER INTEREST FORM MODAL -->
    <!-- ============================================================ -->
    <div x-show="showEnrollModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900/60 transition-opacity" @click="showEnrollModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-gray-100">
                <form @submit.prevent="submitEnrollment($event)">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="course_id" :value="activeCourse?.id">

                    <div class="p-6 bg-gradient-to-r from-indigo-700 to-indigo-900 text-white relative">
                        <button type="button" @click="showEnrollModal = false" class="absolute right-5 top-5 text-white/80 hover:text-white">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                        <span class="px-2.5 py-0.5 rounded bg-white/20 text-white text-[10px] font-bold uppercase tracking-wider">Candidate Enrollment Form</span>
                        <h3 class="text-lg font-black mt-1" x-text="'Register for: ' + (activeCourse?.title || '')"></h3>
                        <p class="text-xs text-white/80 mt-0.5">Please provide your details below. Our training team will assist you with batch admission.</p>
                    </div>

                    <div class="p-6 max-h-[65vh] overflow-y-auto space-y-4 text-xs">
                        <div x-show="errorMessage" class="p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 font-medium" x-text="errorMessage"></div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <label class="block font-bold text-gray-700 mb-1">Student Full Name <span class="text-rose-500">*</span></label>
                                <input type="text" name="applicant_name" x-model="form.applicant_name" required placeholder="e.g. Ramesh Chandra"
                                       class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 focus:bg-white focus:ring-2 focus:ring-indigo-500">
                            </div>

                            <div>
                                <label class="block font-bold text-gray-700 mb-1">Mobile / WhatsApp Number <span class="text-rose-500">*</span></label>
                                <input type="tel" name="contact" x-model="form.contact" required maxlength="10" placeholder="9876543210"
                                       class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 focus:bg-white focus:ring-2 focus:ring-indigo-500">
                            </div>

                            <div>
                                <label class="block font-bold text-gray-700 mb-1">Email Address</label>
                                <input type="email" name="email" x-model="form.email" placeholder="ramesh@example.com"
                                       class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 focus:bg-white focus:ring-2 focus:ring-indigo-500">
                            </div>

                            <div>
                                <label class="block font-bold text-gray-700 mb-1">Gender</label>
                                <select name="gender" x-model="form.gender" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 focus:ring-2 focus:ring-indigo-500">
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <div>
                                <label class="block font-bold text-gray-700 mb-1">Current Qualification <span class="text-rose-500">*</span></label>
                                <select name="qualification" x-model="form.qualification" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 focus:ring-2 focus:ring-indigo-500">
                                    <option value="">Select Qualification</option>
                                    <option value="8th / 10th Pass">8th / 10th Pass</option>
                                    <option value="12th Pass">12th Pass</option>
                                    <option value="Undergraduate">Undergraduate Student</option>
                                    <option value="Graduate (Any Stream)">Graduate (Any Stream)</option>
                                    <option value="Post Graduate">Post Graduate</option>
                                    <option value="Diploma / ITI">Diploma / ITI</option>
                                </select>
                            </div>

                            <div>
                                <label class="block font-bold text-gray-700 mb-1">State <span class="text-rose-500">*</span></label>
                                <select name="state" x-model="form.state" @change="onFormStateChange()" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 focus:ring-2 focus:ring-indigo-500">
                                    <option value="">Select State</option>
                                    <?php foreach ($indiaStatesList as $st): ?>
                                        <option value="<?php echo htmlspecialchars($st); ?>"><?php echo htmlspecialchars($st); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label class="block font-bold text-gray-700 mb-1">District <span class="text-rose-500">*</span></label>
                                <select name="district" x-model="form.district" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 focus:ring-2 focus:ring-indigo-500"
                                        :disabled="!form.state && formDistricts.length === 0">
                                    <option value="">Select District</option>
                                    <template x-for="d in formDistricts" :key="d">
                                        <option :value="d" x-text="d"></option>
                                    </template>
                                </select>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block font-bold text-gray-700 mb-1">Full Residential Address <span class="text-rose-500">*</span></label>
                                <textarea name="address" x-model="form.address" rows="2" required placeholder="House/Village, Post Office, Pincode"
                                          class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-xs text-gray-900 focus:ring-2 focus:ring-indigo-500"></textarea>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block font-bold text-gray-700 mb-1">Why do you want to learn this skill? (Optional)</label>
                                <input type="text" name="motivation" x-model="form.motivation" placeholder="e.g. To start a shop / get a private job / self-reliant"
                                       class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 focus:ring-2 focus:ring-indigo-500">
                            </div>
                        </div>
                    </div>

                    <div class="p-4 md:p-6 bg-gray-50 border-t border-gray-100 flex justify-between">
                        <button type="button" @click="showEnrollModal = false" class="px-5 py-2.5 bg-white text-gray-700 text-xs font-bold rounded-xl border">Cancel</button>
                        <button type="submit" :disabled="loading" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-md">
                            <span x-text="loading ? 'Submitting...' : 'Register Interest'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- 3. SUCCESS MODAL -->
    <!-- ============================================================ -->
    <div x-show="showSuccessModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900/60 transition-opacity" @click="showSuccessModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-3xl text-center overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full p-6 md:p-8 border border-gray-100">
                <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-3xl flex items-center justify-center text-3xl mx-auto mb-4">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <h3 class="text-xl md:text-2xl font-black text-gray-900">Interest Registered!</h3>
                <p class="text-xs text-gray-600 mt-2" x-text="successData?.message"></p>

                <div class="mt-5 p-4 rounded-2xl bg-gray-50 border border-gray-200 text-left text-xs space-y-2">
                    <div class="flex justify-between items-center pb-2 border-b border-gray-200">
                        <span class="text-gray-500 font-medium">Application No:</span>
                        <span class="font-mono font-bold text-indigo-700 text-sm" x-text="successData?.application_no"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500">Candidate:</span>
                        <span class="font-bold text-gray-800" x-text="successData?.applicant_name"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500">Program:</span>
                        <span class="font-bold text-gray-800" x-text="successData?.course_title"></span>
                    </div>
                </div>

                <div class="mt-6">
                    <button type="button" @click="showSuccessModal = false" class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl">
                        Explore More Skill Programs
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
document.addEventListener('alpine:init', () => {
    const indiaLocations = <?php echo $indiaStatesJson; ?>;

    Alpine.data('careerGuidancePortal', (initialCourses = [], autoEnrollId = '', autoViewId = '') => ({
        courses: initialCourses,
        activeCategory: '',
        searchKeyword: '',
        selectedMode: '',
        
        showDetailsModal: false,
        showEnrollModal: false,
        showSuccessModal: false,
        activeCourse: null,
        loading: false,
        errorMessage: '',
        successData: null,
        
        formDistricts: [],
        form: {
            applicant_name: '',
            contact: '',
            email: '',
            gender: 'Male',
            qualification: '',
            state: '',
            district: '',
            address: '',
            motivation: ''
        },

        init() {
            if (autoEnrollId) {
                const target = this.courses.find(c => parseInt(c.id) === parseInt(autoEnrollId));
                if (target) this.openEnrollModal(target);
            } else if (autoViewId) {
                const target = this.courses.find(c => parseInt(c.id) === parseInt(autoViewId));
                if (target) this.openDetailsModal(target);
            }
        },

        get filteredCourses() {
            return this.courses.filter(course => {
                if (this.activeCategory !== '' && course.category !== this.activeCategory) return false;
                if (this.selectedMode !== '' && course.mode !== this.selectedMode) return false;
                if (this.searchKeyword.trim() !== '') {
                    const q = this.searchKeyword.toLowerCase().trim();
                    const title = (course.title || '').toLowerCase();
                    const desc = (course.description || '').toLowerCase();
                    const cur = (course.curriculum || '').toLowerCase();
                    if (!title.includes(q) && !desc.includes(q) && !cur.includes(q)) return false;
                }
                return true;
            });
        },

        getCategoryLabel(cat) {
            const map = {
                'vocational': 'Vocational Trade',
                'computer_it': 'Computer & IT',
                'soft_skills': 'Spoken English & Soft Skills',
                'competitive_exams': 'Competitive Exams',
                'entrepreneurship': 'Micro-Entrepreneurship',
                'healthcare_aid': 'Healthcare & First Aid',
                'other': 'Specialized'
            };
            return map[cat] || 'Skill Course';
        },

        onFormStateChange() {
            this.form.district = '';
            if (this.form.state && indiaLocations[this.form.state]) {
                this.formDistricts = indiaLocations[this.form.state];
            } else {
                this.formDistricts = [];
            }
        },

        openDetailsModal(course) {
            this.activeCourse = course;
            this.showDetailsModal = true;
        },

        enrollFromDetails() {
            const c = this.activeCourse;
            this.showDetailsModal = false;
            this.openEnrollModal(c);
        },

        openEnrollModal(course) {
            this.activeCourse = course;
            this.errorMessage = '';
            this.showEnrollModal = true;
            if (this.form.state && indiaLocations[this.form.state]) {
                this.formDistricts = indiaLocations[this.form.state];
            }
        },

        async submitEnrollment(event) {
            const formElement = event.target;
            this.loading = true;
            this.errorMessage = '';

            const formData = new FormData(formElement);

            try {
                const response = await fetch('process/submit_course_enrollment.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    this.successData = data;
                    this.showEnrollModal = false;
                    this.showSuccessModal = true;
                    this.form.applicant_name = '';
                    this.form.contact = '';
                    this.form.email = '';
                    this.form.address = '';
                    this.form.motivation = '';
                } else {
                    this.errorMessage = data.message || 'Failed to submit enrollment.';
                }
            } catch (err) {
                console.error(err);
                this.errorMessage = 'Network error during enrollment.';
            } finally {
                this.loading = false;
            }
        }
    }));
});
</script>

<?php require 'includes/footer.php'; ?>
