<?php
/**
 * join-us.php
 * Unified "Join Us" Portal: Join Foundation, Join a Project, Apply for Job.
 * Dynamic interactive multi-stream form with Razorpay Payment Gateway integration.
 * Author: VELNIX SOFT / Antigravity AI
 * Date: 2026-09-12
 */

require_once 'config/db.php';
require_once 'config/razorpay.php';
require_once 'includes/functions.php';
require_once 'includes/india_locations.php';
require_once 'includes/join_application_helper.php';

$csrfToken = generateCsrfToken();
$activeProjects = get_active_projects_for_join($pdo);
$activeJobs = get_active_jobs_for_join($pdo);
$streamMeta = get_join_application_types_meta();

// URL Query Param Defaults
$initialType = cleanInput($_GET['type'] ?? 'join_foundation');
if (!in_array($initialType, ['join_foundation', 'join_project', 'job_application'], true)) {
    $initialType = 'join_foundation';
}
$initialProjectId = filter_input(INPUT_GET, 'project_id', FILTER_VALIDATE_INT) ?: 0;
$initialJobId = filter_input(INPUT_GET, 'job_id', FILTER_VALIDATE_INT) ?: 0;

$indiaStatesJson = india_state_district_js();
$indiaStatesList = india_state_list();

// Fetch Razorpay credentials for frontend key display
$rzpCreds = getRazorpayCredentials($pdo, 'donation');
$rzpKeyId = $rzpCreds['key_id'];

require 'includes/header.php';
?>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>

<div class="bg-gradient-to-b from-[#FFFDF9] via-white to-[#F0FDFD] min-h-screen py-8 md:py-14"
     x-data="joinUsPortal(
         '<?php echo $initialType; ?>',
         <?php echo (int)$initialProjectId; ?>,
         <?php echo (int)$initialJobId; ?>,
         <?php echo htmlspecialchars(json_encode($activeProjects, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>,
         <?php echo htmlspecialchars(json_encode($activeJobs, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>,
         '<?php echo htmlspecialchars($rzpKeyId, ENT_QUOTES, 'UTF-8'); ?>',
         '<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>'
     )"
     x-cloak>

    <div class="container mx-auto px-4 max-w-6xl">
        
        <!-- ==================== HERO SECTION ==================== -->
        <div class="max-w-3xl mx-auto text-center mb-10 md:mb-14">
            <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white shadow-xs border border-teal-200 text-teal-800 text-xs sm:text-sm font-bold uppercase tracking-wider">
                <i class="fa-solid fa-hands-holding-child text-[#F4A640]"></i>
                <span>Join Our Mission • Be The Change</span>
            </span>
            <h1 class="mt-4 text-3xl sm:text-4xl md:text-5xl font-black text-gray-900 tracking-tight">
                Join <span class="text-[#0F8B8D]">Jaysmrutti Foundation</span>
            </h1>
            <p class="mt-3 text-sm sm:text-base text-gray-600 max-w-2xl mx-auto leading-relaxed">
                Whether you want to become a core foundation member, volunteer your time for a social campaign, or build a purpose-driven career with us — select your path below to get started.
            </p>
        </div>

        <!-- ==================== STEP 1: STREAM SELECTOR CARDS ==================== -->
        <div x-show="!submitted" class="mb-10">
            <div class="text-center mb-6">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Step 1: Choose Application Stream</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                
                <!-- Card 1: Join Foundation -->
                <div @click="selectStream('join_foundation')"
                     class="cursor-pointer relative rounded-2xl p-6 transition-all duration-300 border-2 bg-white shadow-sm hover:shadow-xl group"
                     :class="stream === 'join_foundation' ? 'border-[#0F8B8D] ring-4 ring-teal-500/15 bg-gradient-to-b from-teal-50/40 to-white' : 'border-gray-200 hover:border-teal-300'">
                    
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl transition-all"
                             :class="stream === 'join_foundation' ? 'bg-[#0F8B8D] text-white shadow-md shadow-teal-700/20' : 'bg-teal-50 text-[#0F8B8D] group-hover:bg-[#0F8B8D] group-hover:text-white'">
                            <i class="fa-solid fa-hands-holding-child"></i>
                        </div>
                        <span x-show="stream === 'join_foundation'" class="inline-flex items-center gap-1 text-xs font-bold text-[#0F8B8D] bg-teal-100/70 px-2.5 py-1 rounded-full">
                            <i class="fa-solid fa-circle-check"></i> Selected
                        </span>
                    </div>

                    <h3 class="text-lg font-bold text-gray-900 group-hover:text-[#0F8B8D] transition-colors">Join Foundation</h3>
                    <p class="text-xs font-semibold text-[#0F8B8D] mt-0.5">Membership & General Volunteer</p>
                    <p class="text-xs text-gray-500 mt-2.5 leading-relaxed">
                        Become an official volunteer or honorary member driving healthcare, education, and community outreach.
                    </p>

                    <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between text-xs">
                        <span class="text-gray-500">Induction / Fee:</span>
                        <span class="font-bold text-[#0F8B8D]">₹500 / Optional Free</span>
                    </div>
                </div>

                <!-- Card 2: Join a Project -->
                <div @click="selectStream('join_project')"
                     class="cursor-pointer relative rounded-2xl p-6 transition-all duration-300 border-2 bg-white shadow-sm hover:shadow-xl group"
                     :class="stream === 'join_project' ? 'border-emerald-600 ring-4 ring-emerald-500/15 bg-gradient-to-b from-emerald-50/40 to-white' : 'border-gray-200 hover:border-emerald-300'">
                    
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl transition-all"
                             :class="stream === 'join_project' ? 'bg-emerald-600 text-white shadow-md shadow-emerald-700/20' : 'bg-emerald-50 text-emerald-600 group-hover:bg-emerald-600 group-hover:text-white'">
                            <i class="fa-solid fa-seedling"></i>
                        </div>
                        <span x-show="stream === 'join_project'" class="inline-flex items-center gap-1 text-xs font-bold text-emerald-700 bg-emerald-100/70 px-2.5 py-1 rounded-full">
                            <i class="fa-solid fa-circle-check"></i> Selected
                        </span>
                    </div>

                    <h3 class="text-lg font-bold text-gray-900 group-hover:text-emerald-700 transition-colors">Join a Project</h3>
                    <p class="text-xs font-semibold text-emerald-600 mt-0.5">Field Campaigns & Social Missions</p>
                    <p class="text-xs text-gray-500 mt-2.5 leading-relaxed">
                        Volunteer directly for active social projects like Green India, Stationery Bank, or School Chalo Abhiyan.
                    </p>

                    <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between text-xs">
                        <span class="text-gray-500">Active Projects:</span>
                        <span class="font-bold text-emerald-700"><?php echo count($activeProjects); ?> Campaigns</span>
                    </div>
                </div>

                <!-- Card 3: Apply for Job -->
                <div @click="selectStream('job_application')"
                     class="cursor-pointer relative rounded-2xl p-6 transition-all duration-300 border-2 bg-white shadow-sm hover:shadow-xl group"
                     :class="stream === 'job_application' ? 'border-amber-500 ring-4 ring-amber-500/15 bg-gradient-to-b from-amber-50/40 to-white' : 'border-gray-200 hover:border-amber-300'">
                    
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl transition-all"
                             :class="stream === 'job_application' ? 'bg-amber-500 text-white shadow-md shadow-amber-600/20' : 'bg-amber-50 text-amber-600 group-hover:bg-amber-500 group-hover:text-white'">
                            <i class="fa-solid fa-briefcase"></i>
                        </div>
                        <span x-show="stream === 'job_application'" class="inline-flex items-center gap-1 text-xs font-bold text-amber-800 bg-amber-100/70 px-2.5 py-1 rounded-full">
                            <i class="fa-solid fa-circle-check"></i> Selected
                        </span>
                    </div>

                    <h3 class="text-lg font-bold text-gray-900 group-hover:text-amber-700 transition-colors">Apply for Job</h3>
                    <p class="text-xs font-semibold text-amber-600 mt-0.5">Careers & Full-time Vacancies</p>
                    <p class="text-xs text-gray-500 mt-2.5 leading-relaxed">
                        Apply for professional coordinator, field officer, or administrative openings across our regional offices.
                    </p>

                    <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between text-xs">
                        <span class="text-gray-500">Open Vacancies:</span>
                        <span class="font-bold text-amber-700"><?php echo count($activeJobs); ?> Openings</span>
                    </div>
                </div>

            </div>
        </div>

        <!-- ==================== STEP 2: DYNAMIC FORM CONTAINER ==================== -->
        <div x-show="!submitted" class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <!-- Left 7 Cols: Dynamic Input Form -->
            <div class="lg:col-span-7 bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden">
                
                <!-- Form Header Bar -->
                <div class="p-5 md:p-6 border-b border-gray-100"
                     :class="{
                         'bg-gradient-to-r from-teal-50 via-white to-teal-50/20': stream === 'join_foundation',
                         'bg-gradient-to-r from-emerald-50 via-white to-emerald-50/20': stream === 'join_project',
                         'bg-gradient-to-r from-amber-50 via-white to-amber-50/20': stream === 'job_application'
                     }">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold uppercase tracking-wider px-2.5 py-0.5 rounded-full"
                                      :class="{
                                          'bg-teal-100 text-teal-800': stream === 'join_foundation',
                                          'bg-emerald-100 text-emerald-800': stream === 'join_project',
                                          'bg-amber-100 text-amber-800': stream === 'job_application'
                                      }">
                                    <span x-text="streamLabel()"></span>
                                </span>
                                <span class="text-xs text-gray-400">• Application Form</span>
                            </div>
                            <h2 class="text-xl font-black text-gray-900 mt-1">Fill Your Applicant Details</h2>
                        </div>
                        <div class="text-right">
                            <span class="text-xs text-gray-400">Step 2 of 2</span>
                        </div>
                    </div>
                </div>

                <!-- Error Notice Banner -->
                <div x-show="errorMessage" x-transition class="p-4 bg-rose-50 border-b border-rose-200 text-rose-700 text-xs flex items-center gap-2">
                    <i class="fa-solid fa-triangle-exclamation text-rose-500 text-base"></i>
                    <span x-text="errorMessage"></span>
                </div>

                <!-- Main Form Body -->
                <form @submit.prevent="submitApplication()" class="p-6 md:p-8 space-y-6">
                    
                    <!-- Section A: Common Applicant Particulars -->
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-3 flex items-center gap-2">
                            <i class="fa-solid fa-user-circle text-teal-600"></i> Personal Information
                        </h4>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">
                                    Full Name <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" 
                                       x-model="form.applicant_name" 
                                       required
                                       placeholder="Enter your legal full name"
                                       class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">
                                        Mobile / WhatsApp <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-3.5 top-2.5 text-xs text-gray-400 font-bold">+91</span>
                                        <input type="tel" 
                                               x-model="form.contact" 
                                               required
                                               maxlength="10"
                                               placeholder="10-digit mobile"
                                               class="w-full pl-12 pr-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">
                                        Email Address
                                    </label>
                                    <input type="email" 
                                           x-model="form.email" 
                                           placeholder="e.g. yourname@example.com"
                                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">State</label>
                                    <select x-model="form.state" 
                                            @change="onStateChange()"
                                            class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                                        <option value="">Select State</option>
                                        <?php foreach ($indiaStatesList as $st): ?>
                                            <option value="<?php echo htmlspecialchars($st); ?>"><?php echo htmlspecialchars($st); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">District / City</label>
                                    <select x-model="form.district" 
                                            class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                                        <option value="">Select District</option>
                                        <template x-for="dist in currentDistricts" :key="dist">
                                            <option :value="dist" x-text="dist"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ==================== SECTION B: STREAM-SPECIFIC DYNAMICS ==================== -->

                    <!-- DYNAMIC STREAM 1: JOIN FOUNDATION -->
                    <div x-show="stream === 'join_foundation'" x-transition class="space-y-4 pt-2 border-t border-gray-100">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-teal-700 flex items-center gap-2">
                            <i class="fa-solid fa-hands-holding-child"></i> Foundation Membership Details
                        </h4>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Preferred Wing / Interest Area</label>
                            <select x-model="form.foundation_wing" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                                <option value="Community Welfare & Swasthya">Community Welfare & Swasthya (Healthcare)</option>
                                <option value="Child Education & Literacy">Child Education & School Literacy</option>
                                <option value="Women Empowerment & Self-Help">Women Empowerment & Self-Help Groups</option>
                                <option value="Environment & Green Plantation">Environment, Green India & Tree Plantation</option>
                                <option value="Disaster Relief & Ration Drive">Disaster Relief & Essentials Ration Drive</option>
                                <option value="Youth Career & Skill Development">Youth Career & Skill Development</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Statement of Motivation / Purpose</label>
                            <textarea x-model="form.details" 
                                      rows="3" 
                                      placeholder="Why would you like to join Jaysmrutti Foundation and how would you like to contribute?"
                                      class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#0F8B8D]"></textarea>
                        </div>

                        <!-- Membership Fee Tier Selection -->
                        <div class="p-4 bg-teal-50/60 rounded-2xl border border-teal-100">
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-xs font-bold text-teal-900">Membership / Induction Contribution</label>
                                <span class="text-[11px] text-teal-700 font-semibold">Processed via Razorpay</span>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-3">
                                <button type="button" 
                                        @click="setFee(500)" 
                                        :class="form.fee_amount === 500 ? 'bg-[#0F8B8D] text-white font-bold shadow-sm' : 'bg-white text-gray-700 border border-gray-200'"
                                        class="py-2 px-3 rounded-xl text-xs text-center transition-all">
                                    ₹500 <span class="text-[10px] block opacity-80">(Standard)</span>
                                </button>
                                <button type="button" 
                                        @click="setFee(250)" 
                                        :class="form.fee_amount === 250 ? 'bg-[#0F8B8D] text-white font-bold shadow-sm' : 'bg-white text-gray-700 border border-gray-200'"
                                        class="py-2 px-3 rounded-xl text-xs text-center transition-all">
                                    ₹250 <span class="text-[10px] block opacity-80">(Supporter)</span>
                                </button>
                                <button type="button" 
                                        @click="setFee(1000)" 
                                        :class="form.fee_amount === 1000 ? 'bg-[#0F8B8D] text-white font-bold shadow-sm' : 'bg-white text-gray-700 border border-gray-200'"
                                        class="py-2 px-3 rounded-xl text-xs text-center transition-all">
                                    ₹1000 <span class="text-[10px] block opacity-80">(Patron)</span>
                                </button>
                                <button type="button" 
                                        @click="setFee(0)" 
                                        :class="form.fee_amount === 0 ? 'bg-emerald-700 text-white font-bold shadow-sm' : 'bg-white text-gray-700 border border-gray-200'"
                                        class="py-2 px-3 rounded-xl text-xs text-center transition-all">
                                    ₹0 Free <span class="text-[10px] block opacity-80">(Exempted)</span>
                                </button>
                            </div>
                            <p class="text-[11px] text-gray-500">
                                <i class="fa-solid fa-circle-info text-teal-600"></i> Free/Exempted applications are welcome for student & honorary volunteers without any mandatory payment.
                            </p>
                        </div>
                    </div>

                    <!-- DYNAMIC STREAM 2: JOIN A PROJECT -->
                    <div x-show="stream === 'join_project'" x-transition class="space-y-4 pt-2 border-t border-gray-100">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-emerald-700 flex items-center gap-2">
                            <i class="fa-solid fa-seedling"></i> Select Social Project to Join
                        </h4>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">
                                Choose Project / Campaign <span class="text-rose-500">*</span>
                            </label>
                            <select x-model="form.project_id" 
                                    @change="onProjectSelect()"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-emerald-600 font-semibold text-gray-800">
                                <option value="0">-- Select an Active Project --</option>
                                <template x-for="p in projects" :key="p.id">
                                    <option :value="p.id" x-text="p.title"></option>
                                </template>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Volunteer Skills & Availability</label>
                            <input type="text" 
                                   x-model="form.volunteer_skills" 
                                   placeholder="e.g. Field Coordination, Teaching, First Aid, Photography, 5 hrs/week"
                                   class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Remarks / How you want to assist</label>
                            <textarea x-model="form.details" 
                                      rows="3" 
                                      placeholder="Tell us why you are interested in this specific project and any prior experience..."
                                      class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600"></textarea>
                        </div>

                        <!-- Project Voluntary Contribution (Optional) -->
                        <div class="p-4 bg-emerald-50/60 rounded-2xl border border-emerald-100">
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-xs font-bold text-emerald-900">Optional Project Aid Contribution</label>
                                <span class="text-[11px] text-emerald-700 font-semibold">Free Volunteering Supported</span>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                <button type="button" 
                                        @click="setFee(0)" 
                                        :class="form.fee_amount === 0 ? 'bg-emerald-700 text-white font-bold shadow-sm' : 'bg-white text-gray-700 border border-gray-200'"
                                        class="py-2 px-3 rounded-xl text-xs text-center transition-all">
                                    ₹0 (Pure Volunteer)
                                </button>
                                <button type="button" 
                                        @click="setFee(100)" 
                                        :class="form.fee_amount === 100 ? 'bg-emerald-700 text-white font-bold shadow-sm' : 'bg-white text-gray-700 border border-gray-200'"
                                        class="py-2 px-3 rounded-xl text-xs text-center transition-all">
                                    ₹100 Aid
                                </button>
                                <button type="button" 
                                        @click="setFee(250)" 
                                        :class="form.fee_amount === 250 ? 'bg-emerald-700 text-white font-bold shadow-sm' : 'bg-white text-gray-700 border border-gray-200'"
                                        class="py-2 px-3 rounded-xl text-xs text-center transition-all">
                                    ₹250 Aid
                                </button>
                                <button type="button" 
                                        @click="setFee(500)" 
                                        :class="form.fee_amount === 500 ? 'bg-emerald-700 text-white font-bold shadow-sm' : 'bg-white text-gray-700 border border-gray-200'"
                                        class="py-2 px-3 rounded-xl text-xs text-center transition-all">
                                    ₹500 Aid
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- DYNAMIC STREAM 3: APPLY FOR JOB -->
                    <div x-show="stream === 'job_application'" x-transition class="space-y-4 pt-2 border-t border-gray-100">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-amber-700 flex items-center gap-2">
                            <i class="fa-solid fa-briefcase"></i> Job Opening & Qualifications
                        </h4>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">
                                Select Vacancy <span class="text-rose-500">*</span>
                            </label>
                            <select x-model="form.job_id" 
                                    @change="onJobSelect()"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-amber-500 font-semibold text-gray-800">
                                <option value="0">-- Select Open Vacancy --</option>
                                <template x-for="j in jobs" :key="j.id">
                                    <option :value="j.id" x-text="j.title + ' (' + (j.location || j.district || 'All India') + ')'"></option>
                                </template>
                            </select>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Highest Qualification</label>
                                <input type="text" 
                                       x-model="form.qualification" 
                                       placeholder="e.g. MSW, MBA, B.Sc, Graduate"
                                       class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Total Experience (Years)</label>
                                <input type="text" 
                                       x-model="form.experience" 
                                       placeholder="e.g. 2 Years in NGO / Fresher"
                                       class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Resume Link / Portfolio / LinkedIn URL</label>
                            <input type="url" 
                                   x-model="form.resume_link" 
                                   placeholder="e.g. Google Drive link or LinkedIn profile"
                                   class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Cover Note / Key Responsibilities Experience</label>
                            <textarea x-model="form.details" 
                                      rows="3" 
                                      placeholder="Briefly describe your background, suitability for this position, and expected joining timeline..."
                                      class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500"></textarea>
                        </div>
                    </div>

                    <!-- ==================== SUBMISSION BUTTON & RAZORPAY CTA ==================== -->
                    <div class="pt-4 border-t border-gray-100">
                        
                        <!-- Fee Status Indicator -->
                        <div class="flex items-center justify-between p-3.5 rounded-2xl mb-4 text-xs"
                             :class="form.fee_amount > 0 ? 'bg-amber-50 border border-amber-200 text-amber-900' : 'bg-gray-50 border border-gray-200 text-gray-700'">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid" :class="form.fee_amount > 0 ? 'fa-credit-card text-[#0F8B8D]' : 'fa-check-circle text-emerald-600'"></i>
                                <span>Fee Payable:</span>
                                <span class="font-extrabold text-sm" x-text="form.fee_amount > 0 ? '₹' + Number(form.fee_amount).toFixed(2) : 'Free / Exempted'"></span>
                            </div>
                            <span class="font-semibold text-[11px]" x-text="form.fee_amount > 0 ? 'Online Checkout via Razorpay' : 'Direct Instant Submission'"></span>
                        </div>

                        <button type="submit" 
                                :disabled="loading"
                                class="w-full py-3.5 px-6 rounded-2xl text-white font-bold text-sm sm:text-base transition-all duration-300 shadow-lg flex items-center justify-center gap-2 disabled:opacity-50"
                                :class="{
                                    'bg-[#0F8B8D] hover:bg-teal-700 shadow-teal-700/20': stream === 'join_foundation',
                                    'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-700/20': stream === 'join_project',
                                    'bg-amber-600 hover:bg-amber-700 shadow-amber-700/20': stream === 'job_application'
                                }">
                            <i class="fa-solid" :class="loading ? 'fa-spinner fa-spin' : (form.fee_amount > 0 ? 'fa-lock' : 'fa-paper-plane')"></i>
                            <span x-text="buttonText()"></span>
                        </button>
                    </div>

                </form>
            </div>

            <!-- Right 5 Cols: Interactive Spotlight & Stream Meta Sidebar -->
            <div class="lg:col-span-5 space-y-6">
                
                <!-- SPOTLIGHT CARD 1: Selected Project Spotlight (Visible when join_project selected) -->
                <div x-show="stream === 'join_project' && selectedProject" x-transition class="bg-white rounded-3xl p-6 shadow-xl border border-emerald-100">
                    <div class="flex items-center gap-2 text-xs font-bold text-emerald-700 uppercase tracking-wider mb-3">
                        <i class="fa-solid fa-bullseye"></i> Project Spotlight
                    </div>
                    <div class="rounded-2xl overflow-hidden mb-4 bg-gray-100 aspect-video relative">
                        <template x-if="selectedProject && selectedProject.thumbnail_image">
                            <img :src="selectedProject.thumbnail_image" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!selectedProject || !selectedProject.thumbnail_image">
                            <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-emerald-100 to-teal-100 text-emerald-700">
                                <i class="fa-solid fa-seedling text-4xl"></i>
                            </div>
                        </template>
                    </div>
                    <h3 class="text-base font-black text-gray-900" x-text="selectedProject ? selectedProject.title : ''"></h3>
                    <p class="text-xs text-gray-600 mt-2 line-clamp-3 leading-relaxed" x-text="selectedProject ? selectedProject.description : ''"></p>
                    
                    <div class="mt-4 pt-3 border-t border-gray-100 grid grid-cols-2 gap-2 text-xs">
                        <div class="bg-emerald-50/60 p-2.5 rounded-xl">
                            <span class="text-gray-500 block text-[10px]">Target Goal</span>
                            <span class="font-extrabold text-emerald-800" x-text="'₹' + Number(selectedProject ? selectedProject.target_amount : 0).toLocaleString()"></span>
                        </div>
                        <div class="bg-teal-50/60 p-2.5 rounded-xl">
                            <span class="text-gray-500 block text-[10px]">Campaign Status</span>
                            <span class="font-extrabold text-[#0F8B8D]">Active Mission</span>
                        </div>
                    </div>
                </div>

                <!-- SPOTLIGHT CARD 2: Selected Job Vacancy Spotlight (Visible when job_application selected) -->
                <div x-show="stream === 'job_application' && selectedJob" x-transition class="bg-white rounded-3xl p-6 shadow-xl border border-amber-100">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold text-amber-700 uppercase tracking-wider">
                            <i class="fa-solid fa-briefcase"></i> Vacancy Overview
                        </span>
                        <span class="text-[11px] font-bold px-2 py-0.5 rounded-md bg-amber-100 text-amber-900" x-text="selectedJob ? selectedJob.job_code : ''"></span>
                    </div>
                    <h3 class="text-base font-black text-gray-900" x-text="selectedJob ? selectedJob.title : ''"></h3>
                    <div class="flex flex-wrap gap-1.5 mt-2">
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-gray-100 text-gray-700" x-text="selectedJob ? selectedJob.job_type : ''"></span>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-amber-50 text-amber-800" x-text="selectedJob ? (selectedJob.location || selectedJob.district) : ''"></span>
                    </div>

                    <div class="mt-4 space-y-2 text-xs">
                        <div class="flex items-center justify-between py-1.5 border-b border-gray-100">
                            <span class="text-gray-500">Salary Range:</span>
                            <span class="font-bold text-gray-900" x-text="selectedJob ? (selectedJob.salary_range || 'Negotiable') : ''"></span>
                        </div>
                        <div class="flex items-center justify-between py-1.5 border-b border-gray-100">
                            <span class="text-gray-500">Experience Req:</span>
                            <span class="font-bold text-gray-900" x-text="selectedJob ? (selectedJob.experience_required || 'Fresher/Experienced') : ''"></span>
                        </div>
                        <div class="flex items-center justify-between py-1.5">
                            <span class="text-gray-500">Min Qualification:</span>
                            <span class="font-bold text-gray-900" x-text="selectedJob ? (selectedJob.min_qualification || 'Graduate') : ''"></span>
                        </div>
                    </div>
                </div>

                <!-- INFO CARD: Why Join Us -->
                <div class="bg-gradient-to-br from-[#0F8B8D] to-[#0d7577] rounded-3xl p-6 text-white shadow-xl">
                    <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center text-lg mb-3">
                        <i class="fa-solid fa-award text-[#F4A640]"></i>
                    </div>
                    <h3 class="text-lg font-bold">Why Join Jaysmrutti Foundation?</h3>
                    <ul class="mt-3 space-y-2.5 text-xs text-teal-50">
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-[#F4A640] mt-0.5"></i>
                            <span><strong>Official Certificate of Induction</strong> & Volunteer Identity Card issued to all approved members.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-[#F4A640] mt-0.5"></i>
                            <span>Direct grassroots impact across health, child education, and rural development wings.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-[#F4A640] mt-0.5"></i>
                            <span>Professional mentorship, career networking, and field leadership opportunities.</span>
                        </li>
                    </ul>
                </div>

                <!-- TRUST BADGE: Secure Payment & Verification -->
                <div class="bg-white rounded-2xl p-5 border border-gray-200 text-xs text-gray-600 flex items-center gap-3 shadow-xs">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-lg flex-shrink-0">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <div>
                        <h5 class="font-bold text-gray-900">Secure & Direct Processing</h5>
                        <p class="text-[11px] text-gray-500 mt-0.5">All applications are instantly logged with official reference numbers. Online fees processed via 256-bit encrypted Razorpay gateway.</p>
                    </div>
                </div>

            </div>

        </div>

        <!-- ==================== STEP 3: SUBMISSION CONFIRMATION SCREEN ==================== -->
        <div x-show="submitted" x-transition.opacity class="max-w-2xl mx-auto">
            <div class="bg-white rounded-3xl shadow-2xl border border-teal-100 overflow-hidden text-center p-8 md:p-12">
                
                <div class="w-20 h-20 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-3xl mx-auto mb-6 shadow-inner animate-bounce">
                    <i class="fa-solid fa-check"></i>
                </div>

                <span class="inline-flex items-center gap-1 text-xs font-bold text-emerald-800 bg-emerald-50 px-3 py-1 rounded-full border border-emerald-200">
                    <i class="fa-solid fa-circle-check"></i> Application Successfully Received
                </span>

                <h2 class="text-2xl sm:text-3xl font-black text-gray-900 mt-3">Welcome to the Mission!</h2>
                <p class="text-xs sm:text-sm text-gray-600 mt-2 max-w-md mx-auto">
                    Your application has been registered with Jaysmrutti Foundation. Please save your reference tracking code for future correspondence.
                </p>

                <!-- Reference Card -->
                <div class="my-6 p-5 rounded-2xl bg-gradient-to-r from-teal-50 via-emerald-50 to-teal-50 border border-teal-200/80 text-left">
                    <div class="flex items-center justify-between pb-3 mb-3 border-b border-teal-200/60">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-gray-500 block">Application Reference No</span>
                            <span class="text-xl sm:text-2xl font-black text-[#0F8B8D] tracking-wide" x-text="result.application_no"></span>
                        </div>
                        <button type="button" 
                                @click="copyAppNo()"
                                class="px-3 py-1.5 bg-white border border-teal-200 text-teal-800 hover:bg-teal-50 rounded-xl text-xs font-bold transition flex items-center gap-1 shadow-xs">
                            <i class="fa-solid" :class="copied ? 'fa-check text-emerald-600' : 'fa-copy'"></i>
                            <span x-text="copied ? 'Copied!' : 'Copy Code'"></span>
                        </button>
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div>
                            <span class="text-gray-500 block text-[11px]">Applicant Name:</span>
                            <span class="font-bold text-gray-900" x-text="result.applicant_name"></span>
                        </div>
                        <div>
                            <span class="text-gray-500 block text-[11px]">Contact No:</span>
                            <span class="font-bold text-gray-900" x-text="result.contact"></span>
                        </div>
                        <div>
                            <span class="text-gray-500 block text-[11px]">Application Stream:</span>
                            <span class="font-bold text-teal-800" x-text="result.application_type"></span>
                        </div>
                        <div>
                            <span class="text-gray-500 block text-[11px]">Payment Status:</span>
                            <span class="font-bold" 
                                  :class="result.payment_status === 'paid' ? 'text-emerald-700' : 'text-gray-700'" 
                                  x-text="result.payment_status === 'paid' ? '✓ Paid (₹' + Number(result.fee_amount).toFixed(2) + ')' : 'Exempted / Free'"></span>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                    <button type="button" 
                            onclick="window.print()" 
                            class="w-full sm:w-auto px-6 py-3 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold text-xs transition flex items-center justify-center gap-2">
                        <i class="fa-solid fa-print"></i> Print Acknowledgment
                    </button>
                    <a href="index.php" 
                       class="w-full sm:w-auto px-6 py-3 rounded-xl bg-[#0F8B8D] hover:bg-teal-700 text-white font-bold text-xs transition flex items-center justify-center gap-2 shadow-md">
                        <i class="fa-solid fa-house"></i> Return to Home
                    </a>
                </div>

            </div>
        </div>

    </div>

</div>

<!-- ==================== ALPINE.JS CONTROLLER SCRIPT ==================== -->
<script>
function joinUsPortal(initialType, initialProjectId, initialJobId, projectsList, jobsList, rzpKeyId, csrfToken) {
    return {
        stream: initialType || 'join_foundation',
        projects: projectsList || [],
        jobs: jobsList || [],
        rzpKey: rzpKeyId || '',
        csrf: csrfToken || '',
        
        selectedProject: null,
        selectedJob: null,
        currentDistricts: [],
        indiaMap: <?php echo $indiaStatesJson; ?>,

        loading: false,
        submitted: false,
        copied: false,
        errorMessage: '',

        form: {
            applicant_name: '',
            contact: '',
            email: '',
            state: 'Uttar Pradesh',
            district: '',
            application_type: initialType || 'join_foundation',
            project_id: initialProjectId || 0,
            job_id: initialJobId || 0,
            foundation_wing: 'Community Welfare & Swasthya',
            volunteer_skills: '',
            qualification: '',
            experience: '',
            resume_link: '',
            details: '',
            fee_amount: initialType === 'join_foundation' ? 500 : 0
        },

        result: {
            application_no: '',
            applicant_name: '',
            contact: '',
            application_type: '',
            fee_amount: 0,
            payment_status: 'exempted'
        },

        init() {
            this.onStateChange();
            if (this.form.project_id > 0) {
                this.onProjectSelect();
            }
            if (this.form.job_id > 0) {
                this.onJobSelect();
            }
        },

        selectStream(type) {
            this.stream = type;
            this.form.application_type = type;
            this.errorMessage = '';

            if (type === 'join_foundation') {
                this.form.fee_amount = 500;
            } else if (type === 'join_project') {
                this.form.fee_amount = 0;
                if (!this.form.project_id && this.projects.length > 0) {
                    this.form.project_id = this.projects[0].id;
                    this.onProjectSelect();
                }
            } else if (type === 'job_application') {
                this.form.fee_amount = 0;
                if (!this.form.job_id && this.jobs.length > 0) {
                    this.form.job_id = this.jobs[0].id;
                    this.onJobSelect();
                }
            }
        },

        streamLabel() {
            if (this.stream === 'join_project') return 'Join a Project';
            if (this.stream === 'job_application') return 'Apply for Job';
            return 'Join Foundation';
        },

        setFee(amount) {
            this.form.fee_amount = amount;
        },

        onStateChange() {
            if (this.indiaMap && this.form.state && this.indiaMap[this.form.state]) {
                this.currentDistricts = this.indiaMap[this.form.state];
                if (!this.currentDistricts.includes(this.form.district)) {
                    this.form.district = this.currentDistricts[0] || '';
                }
            } else {
                this.currentDistricts = [];
            }
        },

        onProjectSelect() {
            const pId = parseInt(this.form.project_id, 10);
            this.selectedProject = this.projects.find(p => parseInt(p.id, 10) === pId) || null;
        },

        onJobSelect() {
            const jId = parseInt(this.form.job_id, 10);
            this.selectedJob = this.jobs.find(j => parseInt(j.id, 10) === jId) || null;
        },

        buttonText() {
            if (this.loading) return 'Processing...';
            if (this.form.fee_amount > 0) {
                return 'Pay ₹' + Number(this.form.fee_amount).toFixed(2) + ' & Submit Application';
            }
            return 'Submit Application (Instant)';
        },

        buildDetailsPayload() {
            let parts = [];
            if (this.form.details.trim()) {
                parts.push(this.form.details.trim());
            }
            if (this.stream === 'join_foundation' && this.form.foundation_wing) {
                parts.push('Preferred Wing: ' + this.form.foundation_wing);
            }
            if (this.stream === 'join_project' && this.form.volunteer_skills) {
                parts.push('Skills/Availability: ' + this.form.volunteer_skills);
            }
            if (this.stream === 'job_application') {
                if (this.form.qualification) parts.push('Qualification: ' + this.form.qualification);
                if (this.form.experience) parts.push('Experience: ' + this.form.experience);
                if (this.form.resume_link) parts.push('Resume Link: ' + this.form.resume_link);
            }
            return parts.join(' | ');
        },

        async submitApplication() {
            this.errorMessage = '';

            if (!this.form.applicant_name.trim()) {
                this.errorMessage = 'Please enter your Full Name.';
                return;
            }
            if (!this.form.contact.trim() || !/^\d{10}$/.test(this.form.contact.replace(/\D/g, ''))) {
                this.errorMessage = 'Please enter a valid 10-digit mobile number.';
                return;
            }

            if (this.stream === 'join_project' && (!this.form.project_id || parseInt(this.form.project_id, 10) === 0)) {
                this.errorMessage = 'Please select a project from the dropdown.';
                return;
            }

            if (this.stream === 'job_application' && (!this.form.job_id || parseInt(this.form.job_id, 10) === 0)) {
                this.errorMessage = 'Please select an open vacancy to apply for.';
                return;
            }

            this.loading = true;
            const combinedDetails = this.buildDetailsPayload();

            // ── CASE 1: FREE / EXEMPTED DIRECT SUBMISSION ──────────
            if (this.form.fee_amount <= 0) {
                try {
                    const fd = new FormData();
                    fd.append('csrf_token', this.csrf);
                    fd.append('is_ajax', '1');
                    fd.append('applicant_name', this.form.applicant_name.trim());
                    fd.append('contact', this.form.contact.trim());
                    fd.append('email', this.form.email.trim());
                    fd.append('state', this.form.state);
                    fd.append('district', this.form.district);
                    fd.append('application_type', this.stream);
                    fd.append('project_id', this.form.project_id || '');
                    fd.append('job_id', this.form.job_id || '');
                    fd.append('details', combinedDetails);
                    fd.append('fee_amount', '0');

                    const res = await fetch('process/submit_join_application.php', {
                        method: 'POST',
                        body: fd
                    });
                    const json = await res.json();

                    if (json.success) {
                        this.result = {
                            application_no: json.application_no,
                            applicant_name: json.applicant_name,
                            contact: json.contact,
                            application_type: this.streamLabel(),
                            fee_amount: 0,
                            payment_status: 'exempted'
                        };
                        this.submitted = true;
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    } else {
                        this.errorMessage = json.message || 'Submission error. Please try again.';
                    }
                } catch (err) {
                    this.errorMessage = 'Network connection error. Please try again.';
                } finally {
                    this.loading = false;
                }
                return;
            }

            // ── CASE 2: PAID FEE VIA RAZORPAY GATEWAY ──────────────
            try {
                const orderFd = new FormData();
                orderFd.append('csrf_token', this.csrf);
                orderFd.append('applicant_name', this.form.applicant_name.trim());
                orderFd.append('contact', this.form.contact.trim());
                orderFd.append('email', this.form.email.trim());
                orderFd.append('state', this.form.state);
                orderFd.append('district', this.form.district);
                orderFd.append('application_type', this.stream);
                orderFd.append('project_id', this.form.project_id || '');
                orderFd.append('job_id', this.form.job_id || '');
                orderFd.append('details', combinedDetails);
                orderFd.append('fee_amount', this.form.fee_amount);

                const orderRes = await fetch('process/create_join_order.php', {
                    method: 'POST',
                    body: orderFd
                });
                const orderData = await orderRes.json();

                if (!orderData.success) {
                    this.errorMessage = orderData.message || 'Unable to initialize Razorpay checkout.';
                    this.loading = false;
                    return;
                }

                const self = this;
                const options = {
                    key: orderData.key_id,
                    amount: orderData.amount,
                    currency: orderData.currency || 'INR',
                    name: orderData.company || 'Jaysmrutti Foundation',
                    description: 'Application Fee - ' + orderData.application_no,
                    order_id: orderData.order_id,
                    prefill: {
                        name: self.form.applicant_name.trim(),
                        email: self.form.email.trim(),
                        contact: self.form.contact.trim()
                    },
                    theme: { color: '#0F8B8D' },
                    handler: async function(response) {
                        self.loading = true;
                        try {
                            const verifyFd = new FormData();
                            verifyFd.append('razorpay_payment_id', response.razorpay_payment_id || '');
                            verifyFd.append('razorpay_order_id', response.razorpay_order_id || orderData.order_id);
                            verifyFd.append('razorpay_signature', response.razorpay_signature || '');
                            verifyFd.append('application_id', orderData.application_id || '');
                            verifyFd.append('application_no', orderData.application_no || '');

                            const verifyRes = await fetch('process/verify_join_payment.php', {
                                method: 'POST',
                                body: verifyFd
                            });
                            const verifyData = await verifyRes.json();

                            if (verifyData.success) {
                                self.result = {
                                    application_no: verifyData.application_no || orderData.application_no,
                                    applicant_name: verifyData.applicant_name || self.form.applicant_name,
                                    contact: verifyData.contact || self.form.contact,
                                    application_type: self.streamLabel(),
                                    fee_amount: verifyData.fee_amount || self.form.fee_amount,
                                    payment_status: 'paid'
                                };
                                self.submitted = true;
                                window.scrollTo({ top: 0, behavior: 'smooth' });
                            } else {
                                self.errorMessage = verifyData.message || 'Payment verification failed.';
                            }
                        } catch (err) {
                            self.errorMessage = 'Verification error. If fee was debited, your application will be verified shortly.';
                        } finally {
                            self.loading = false;
                        }
                    },
                    modal: {
                        ondismiss: function() {
                            self.loading = false;
                            self.errorMessage = 'Payment checkout was dismissed. You can retry anytime.';
                        }
                    }
                };

                const rzp = new Razorpay(options);
                rzp.on('payment.failed', function(response) {
                    self.loading = false;
                    self.errorMessage = 'Payment failed: ' + (response?.error?.description || 'Please try again.');
                });
                rzp.open();
                this.loading = false;

            } catch (err) {
                this.loading = false;
                this.errorMessage = 'Could not launch Razorpay gateway. Please check your connection.';
            }
        },

        copyAppNo() {
            if (!this.result.application_no) return;
            navigator.clipboard.writeText(this.result.application_no);
            this.copied = true;
            setTimeout(() => { this.copied = false; }, 3000);
        }
    };
}
</script>

<?php require 'includes/footer.php'; ?>
