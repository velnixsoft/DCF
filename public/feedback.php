<?php
// ============================================================
// public/feedback.php
// Employee, Member, Volunteer & Stakeholder Feedback Portal
// Allows all community members and internal team to share ideas,
// ratings, and feedback with optional anonymity and tracking.
// ============================================================

require 'includes/header.php';
$csrfToken = generateCsrfToken();
?>

<div class="min-h-screen py-12 md:py-16 px-4 sm:px-6 lg:px-8 bg-slate-50 dark:bg-gray-900 relative overflow-hidden" 
     x-data="feedbackApp()" 
     x-cloak>

    <!-- Background decorative ambient blobs -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-[5%] left-[-10%] w-[380px] sm:w-[520px] h-[380px] sm:h-[520px] rounded-full bg-emerald-100/40 dark:bg-emerald-950/20 blur-[100px] sm:blur-[140px] transition-all duration-1000"></div>
        <div class="absolute bottom-[10%] right-[-10%] w-[380px] sm:w-[580px] h-[380px] sm:h-[580px] rounded-full bg-sky-100/40 dark:bg-sky-950/20 blur-[100px] sm:blur-[140px] transition-all duration-1000"></div>
    </div>

    <div class="max-w-3xl mx-auto relative z-10">

        <!-- ════════════════════════════════════════════════════════════ -->
        <!-- SUCCESS STATE CARD (Rendered after successful submission)    -->
        <!-- ════════════════════════════════════════════════════════════ -->
        <template x-if="isSubmitted">
            <div class="bg-white/95 dark:bg-gray-800/95 backdrop-blur-md shadow-2xl border border-emerald-200 dark:border-emerald-800/60 rounded-3xl p-8 sm:p-12 text-center space-y-6 animate-fade-in">
                
                <div class="w-20 h-20 mx-auto rounded-3xl bg-emerald-50 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-3xl shadow-inner">
                    <i class="fa-solid fa-circle-check"></i>
                </div>

                <div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 dark:bg-emerald-900/50 text-emerald-800 dark:text-emerald-300 mb-2 uppercase tracking-wider">
                        <i class="fa-solid fa-heart-pulse"></i> Feedback Logged Successfully
                    </span>
                    <h2 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white">
                        Thank You, <span x-text="form.name || 'Valued Contributor'"></span>!
                    </h2>
                    <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-2 max-w-lg mx-auto leading-relaxed">
                        Your feedback has been safely submitted into our continuous improvement register. We deeply appreciate your time in helping build a transparent and supportive NGO ecosystem.
                    </p>
                </div>

                <!-- Reference Number Box -->
                <div class="p-6 rounded-2xl bg-slate-50 dark:bg-gray-900/80 border border-slate-200 dark:border-gray-700 space-y-3">
                    <span class="text-[11px] font-bold text-gray-400 uppercase tracking-widest block">Your Feedback Reference ID</span>
                    <div class="flex items-center justify-center gap-3">
                        <span class="text-2xl sm:text-3xl font-mono font-black text-[#1070B0] dark:text-sky-400 tracking-wider" x-text="submittedTicket"></span>
                        <button type="button" @click="copyTicket()" class="px-3 py-1.5 rounded-xl bg-sky-50 hover:bg-sky-100 dark:bg-sky-900/40 text-[#1070B0] dark:text-sky-300 text-xs font-bold transition flex items-center gap-1">
                            <i class="fa-solid" :class="copied ? 'fa-check text-emerald-600' : 'fa-copy'"></i>
                            <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                        </button>
                    </div>
                    <p class="text-[11px] text-gray-400">
                        Keep this reference number handy if you wish to check institutional review updates later.
                    </p>
                </div>

                <!-- Email notice -->
                <div class="flex items-center justify-center gap-2 text-xs text-slate-600 dark:text-gray-300" x-show="form.email && !form.is_anonymous">
                    <i class="fa-solid fa-envelope-circle-check text-sky-600 text-base"></i>
                    <span>Acknowledgement sent to: <strong class="text-gray-900 dark:text-white" x-text="form.email"></strong></span>
                </div>

                <div class="pt-4 flex flex-col sm:flex-row items-center justify-center gap-3">
                    <a :href="'track-feedback.php?ticket=' + encodeURIComponent(submittedTicket)" class="w-full sm:w-auto px-6 py-3.5 rounded-2xl bg-[#1070B0] hover:bg-[#0c598d] text-white font-bold text-xs transition shadow-md flex items-center justify-center gap-2">
                        <i class="fa-solid fa-magnifying-glass-chart"></i> Track Feedback Status
                    </a>
                    <button type="button" @click="resetForm()" class="w-full sm:w-auto px-6 py-3.5 rounded-2xl bg-slate-100 dark:bg-gray-700 hover:bg-slate-200 dark:hover:bg-gray-600 text-gray-800 dark:text-white font-bold text-xs transition">
                        <i class="fa-solid fa-plus mr-1.5"></i> Submit Another Feedback
                    </button>
                    <a href="index.php" class="w-full sm:w-auto px-5 py-3.5 rounded-2xl text-gray-500 hover:text-gray-800 dark:hover:text-white font-bold text-xs transition">
                        Return Home
                    </a>
                </div>

            </div>
        </template>


        <!-- ════════════════════════════════════════════════════════════ -->
        <!-- FORM CONTAINER                                               -->
        <!-- ════════════════════════════════════════════════════════════ -->
        <div x-show="!isSubmitted">
            
            <!-- Header Section -->
            <div class="text-center mb-8">
                <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold bg-sky-50 dark:bg-sky-900/30 text-[#1070B0] dark:text-sky-300 mb-3 border border-sky-200/60 dark:border-sky-800/40 uppercase tracking-wider shadow-sm">
                    <i class="fa-solid fa-comment-dots"></i> Internal & Community Voice
                </span>
                <h1 class="text-3xl sm:text-4xl font-black text-gray-900 dark:text-white tracking-tight">
                    Employee & Member <span class="text-[#1070B0]">Feedback</span>
                </h1>
                <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-2.5 max-w-lg mx-auto leading-relaxed">
                    Share your experience, suggest workplace or program enhancements, or submit valuable inputs. All members, volunteers, and staff voices matter.
                </p>
            </div>

            <!-- Main Form Card -->
            <div class="bg-white/95 dark:bg-gray-800/95 backdrop-blur-md shadow-xl border border-slate-200 dark:border-gray-700 rounded-3xl p-6 sm:p-10 space-y-8">

                <!-- Alert Message Box -->
                <template x-if="errorMessage">
                    <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-900/30 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 text-xs sm:text-sm flex items-start gap-3">
                        <i class="fa-solid fa-circle-exclamation text-base mt-0.5 shrink-0"></i>
                        <div class="flex-1" x-text="errorMessage"></div>
                    </div>
                </template>

                <form @submit.prevent="submitFeedback()" class="space-y-6" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                    <!-- 1. SUBMITTER ROLE SELECTOR (Tabs) -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2.5">
                            I am submitting this feedback as: <span class="text-rose-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 sm:grid-cols-5 gap-2.5">
                            <button type="button" 
                                    @click="form.submitter_type = 'member'"
                                    :class="form.submitter_type === 'member' ? 'bg-[#1070B0] text-white shadow-md border-[#1070B0]' : 'bg-slate-50 dark:bg-gray-700/50 text-gray-700 dark:text-gray-300 border-slate-200 dark:border-gray-600 hover:bg-slate-100'"
                                    class="py-2.5 px-3 rounded-2xl border text-xs font-bold transition flex flex-col items-center gap-1 text-center">
                                <i class="fa-solid fa-id-badge text-base"></i>
                                <span>Member</span>
                            </button>

                            <button type="button" 
                                    @click="form.submitter_type = 'volunteer'"
                                    :class="form.submitter_type === 'volunteer' ? 'bg-[#1070B0] text-white shadow-md border-[#1070B0]' : 'bg-slate-50 dark:bg-gray-700/50 text-gray-700 dark:text-gray-300 border-slate-200 dark:border-gray-600 hover:bg-slate-100'"
                                    class="py-2.5 px-3 rounded-2xl border text-xs font-bold transition flex flex-col items-center gap-1 text-center">
                                <i class="fa-solid fa-hand-holding-heart text-base"></i>
                                <span>Volunteer</span>
                            </button>

                            <button type="button" 
                                    @click="form.submitter_type = 'employee'"
                                    :class="form.submitter_type === 'employee' ? 'bg-[#1070B0] text-white shadow-md border-[#1070B0]' : 'bg-slate-50 dark:bg-gray-700/50 text-gray-700 dark:text-gray-300 border-slate-200 dark:border-gray-600 hover:bg-slate-100'"
                                    class="py-2.5 px-3 rounded-2xl border text-xs font-bold transition flex flex-col items-center gap-1 text-center">
                                <i class="fa-solid fa-briefcase text-base"></i>
                                <span>Employee / Staff</span>
                            </button>

                            <button type="button" 
                                    @click="form.submitter_type = 'field_agent'"
                                    :class="form.submitter_type === 'field_agent' ? 'bg-[#1070B0] text-white shadow-md border-[#1070B0]' : 'bg-slate-50 dark:bg-gray-700/50 text-gray-700 dark:text-gray-300 border-slate-200 dark:border-gray-600 hover:bg-slate-100'"
                                    class="py-2.5 px-3 rounded-2xl border text-xs font-bold transition flex flex-col items-center gap-1 text-center">
                                <i class="fa-solid fa-route text-base"></i>
                                <span>Field Coordinator</span>
                            </button>

                            <button type="button" 
                                    @click="form.submitter_type = 'other'"
                                    :class="form.submitter_type === 'other' ? 'bg-[#1070B0] text-white shadow-md border-[#1070B0]' : 'bg-slate-50 dark:bg-gray-700/50 text-gray-700 dark:text-gray-300 border-slate-200 dark:border-gray-600 hover:bg-slate-100'"
                                    class="py-2.5 px-3 rounded-2xl border text-xs font-bold transition flex flex-col items-center gap-1 text-center col-span-2 sm:col-span-1">
                                <i class="fa-solid fa-users text-base"></i>
                                <span>Partner / Other</span>
                            </button>
                        </div>
                    </div>

                    <!-- 2. ANONYMOUS TOGGLE BANNER -->
                    <div class="p-4 rounded-2xl bg-amber-50/70 dark:bg-amber-950/30 border border-amber-200/80 dark:border-amber-800/50 flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-user-secret text-lg"></i>
                            </div>
                            <div>
                                <h4 class="text-xs sm:text-sm font-bold text-gray-900 dark:text-white">Submit Anonymously?</h4>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400">Your name and personal contact details will be completely hidden from the review report.</p>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer shrink-0">
                            <input type="checkbox" x-model="form.is_anonymous" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-[#1070B0]"></div>
                        </label>
                    </div>

                    <!-- 3. PERSONAL / ID DETAILS (Conditionally simplified if anonymous) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" x-show="!form.is_anonymous">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">
                                Your Full Name <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                    <i class="fa-solid fa-user text-xs"></i>
                                </span>
                                <input type="text" 
                                       x-model="form.name" 
                                       placeholder="e.g. Ramesh Kumar"
                                       class="w-full pl-9 pr-4 py-2.5 rounded-2xl bg-slate-50 dark:bg-gray-700/50 border border-slate-200 dark:border-gray-600 text-xs sm:text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1070B0]">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">
                                Email Address (For Updates)
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                    <i class="fa-solid fa-envelope text-xs"></i>
                                </span>
                                <input type="email" 
                                       x-model="form.email" 
                                       placeholder="name@example.com"
                                       class="w-full pl-9 pr-4 py-2.5 rounded-2xl bg-slate-50 dark:bg-gray-700/50 border border-slate-200 dark:border-gray-600 text-xs sm:text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1070B0]">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">
                                Contact / Mobile Number
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                    <i class="fa-solid fa-phone text-xs"></i>
                                </span>
                                <input type="text" 
                                       x-model="form.contact" 
                                       placeholder="+91 98765 43210"
                                       class="w-full pl-9 pr-4 py-2.5 rounded-2xl bg-slate-50 dark:bg-gray-700/50 border border-slate-200 dark:border-gray-600 text-xs sm:text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1070B0]">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">
                                Member ID / Employee Code (Optional)
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                    <i class="fa-solid fa-fingerprint text-xs"></i>
                                </span>
                                <input type="text" 
                                       x-model="form.user_identifier" 
                                       placeholder="e.g. MEM-2026-102 or EMP-04"
                                       class="w-full pl-9 pr-4 py-2.5 rounded-2xl bg-slate-50 dark:bg-gray-700/50 border border-slate-200 dark:border-gray-600 text-xs sm:text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1070B0]">
                            </div>
                        </div>
                    </div>

                    <!-- 4. RATING SELECTOR (Interactive Stars) -->
                    <div class="p-5 rounded-2xl bg-slate-50 dark:bg-gray-700/40 border border-slate-200 dark:border-gray-600 text-center space-y-2">
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Overall Experience / Satisfaction Rating
                        </label>
                        <div class="flex items-center justify-center gap-2 pt-1">
                            <template x-for="star in [1, 2, 3, 4, 5]" :key="star">
                                <button type="button" 
                                        @click="form.rating = star"
                                        @mouseenter="hoverRating = star"
                                        @mouseleave="hoverRating = 0"
                                        class="p-1.5 transition transform hover:scale-125 focus:outline-none">
                                    <i class="fa-solid fa-star text-2xl sm:text-3xl"
                                       :class="(hoverRating || form.rating) >= star ? 'text-amber-400 drop-shadow-sm' : 'text-gray-300 dark:text-gray-600'"></i>
                                </button>
                            </template>
                        </div>
                        <div class="text-xs font-semibold text-gray-500 dark:text-gray-400" x-text="getRatingLabel(hoverRating || form.rating)"></div>
                    </div>

                    <!-- 5. CATEGORY & DEPARTMENT -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">
                                Feedback Category <span class="text-rose-500">*</span>
                            </label>
                            <select x-model="form.category" class="w-full px-3.5 py-2.5 rounded-2xl bg-slate-50 dark:bg-gray-700/50 border border-slate-200 dark:border-gray-600 text-xs sm:text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1070B0]">
                                <option value="workplace_environment">Workplace & Office Environment</option>
                                <option value="management_support">Management & Leadership Support</option>
                                <option value="program_execution">Program Execution & Field Camps</option>
                                <option value="compensation_benefits">Compensation, Allowances & Benefits</option>
                                <option value="training_guidance">Training, Skill & Career Guidance</option>
                                <option value="it_tools_support">IT Systems & Digital Tools Support</option>
                                <option value="policy_feedback">HR Policy, POSH & Workplace Ethics</option>
                                <option value="general_suggestion">General Suggestion / Creative Idea</option>
                                <option value="other">Other Inquiries / Remarks</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">
                                Department / Wing (Optional)
                            </label>
                            <input type="text" 
                                   x-model="form.department" 
                                   placeholder="e.g. Field Operations, Healthcare Wing, IT"
                                   class="w-full px-3.5 py-2.5 rounded-2xl bg-slate-50 dark:bg-gray-700/50 border border-slate-200 dark:border-gray-600 text-xs sm:text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1070B0]">
                        </div>
                    </div>

                    <!-- 6. SUBJECT & DETAILED MESSAGE -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">
                            Feedback Topic / Subject <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               x-model="form.subject" 
                               required
                               placeholder="Brief summary of your suggestion or feedback"
                               class="w-full px-3.5 py-2.5 rounded-2xl bg-slate-50 dark:bg-gray-700/50 border border-slate-200 dark:border-gray-600 text-xs sm:text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1070B0]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">
                            Detailed Feedback / Constructive Suggestions <span class="text-rose-500">*</span>
                        </label>
                        <textarea x-model="form.message" 
                                  required
                                  rows="5"
                                  placeholder="Please provide specific details, observations, and recommendations..."
                                  class="w-full px-3.5 py-2.5 rounded-2xl bg-slate-50 dark:bg-gray-700/50 border border-slate-200 dark:border-gray-600 text-xs sm:text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#1070B0]"></textarea>
                    </div>

                    <!-- 7. ATTACHMENT (Optional) -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">
                            Supporting Document / Screenshot (Optional, Max 5MB)
                        </label>
                        <input type="file" 
                               @change="handleFileChange"
                               accept=".pdf,.doc,.docx,.png,.jpg,.jpeg"
                               class="block w-full text-xs text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-sky-50 file:text-[#1070B0] dark:file:bg-sky-900/40 dark:file:text-sky-300 hover:file:bg-sky-100 cursor-pointer">
                    </div>

                    <!-- 8. SUBMIT BUTTON -->
                    <div class="pt-2">
                        <button type="submit" 
                                :disabled="isSubmitting"
                                class="w-full py-4 rounded-2xl bg-gradient-to-r from-[#1070B0] to-[#0F8B8D] hover:opacity-95 text-white font-extrabold text-sm transition shadow-lg flex items-center justify-center gap-2 transform active:scale-[0.99] disabled:opacity-50">
                            <template x-if="!isSubmitting">
                                <span class="flex items-center gap-2">
                                    <i class="fa-solid fa-paper-plane"></i> Submit Feedback
                                </span>
                            </template>
                            <template x-if="isSubmitting">
                                <span class="flex items-center gap-2">
                                    <i class="fa-solid fa-circle-notch fa-spin"></i> Submitting Feedback...
                                </span>
                            </template>
                        </button>
                    </div>

                    <div class="text-center pt-2">
                        <a href="track-feedback.php" class="text-xs font-bold text-[#1070B0] hover:underline">
                            <i class="fa-solid fa-magnifying-glass-location mr-1"></i> Already submitted? Track your feedback here
                        </a>
                    </div>
                </form>

            </div>

        </div>

    </div>
</div>

<script>
function feedbackApp() {
    return {
        isSubmitting: false,
        isSubmitted: false,
        submittedTicket: '',
        errorMessage: '',
        hoverRating: 0,
        copied: false,
        fileAttachment: null,
        form: {
            submitter_type: 'member',
            is_anonymous: false,
            name: '',
            email: '',
            contact: '',
            user_identifier: '',
            category: 'workplace_environment',
            department: '',
            rating: 5,
            subject: '',
            message: ''
        },

        getRatingLabel(r) {
            const labels = {
                1: '⭐ (1/5) - Unsatisfactory / Critical Improvements Needed',
                2: '⭐⭐ (2/5) - Below Average / Needs Attention',
                3: '⭐⭐⭐ (3/5) - Average / Decent Experience',
                4: '⭐⭐⭐⭐ (4/5) - Good / Very Supportive',
                5: '⭐⭐⭐⭐⭐ (5/5) - Outstanding / Inspiring'
            };
            return labels[r] || 'Select your rating';
        },

        handleFileChange(e) {
            this.fileAttachment = e.target.files[0] || null;
        },

        copyTicket() {
            navigator.clipboard.writeText(this.submittedTicket).then(() => {
                this.copied = true;
                setTimeout(() => this.copied = false, 3000);
            });
        },

        resetForm() {
            this.isSubmitted = false;
            this.submittedTicket = '';
            this.errorMessage = '';
            this.fileAttachment = null;
            this.form = {
                submitter_type: 'member',
                is_anonymous: false,
                name: '',
                email: '',
                contact: '',
                user_identifier: '',
                category: 'workplace_environment',
                department: '',
                rating: 5,
                subject: '',
                message: ''
            };
        },

        async submitFeedback() {
            this.errorMessage = '';

            if (!this.form.subject.trim() || !this.form.message.trim()) {
                this.errorMessage = 'Please provide both Subject and Feedback message.';
                return;
            }

            if (!this.form.is_anonymous && !this.form.name.trim()) {
                this.errorMessage = 'Please provide your Name or switch to Anonymous mode.';
                return;
            }

            this.isSubmitting = true;

            const fd = new FormData();
            fd.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
            fd.append('submitter_type', this.form.submitter_type);
            fd.append('is_anonymous', this.form.is_anonymous ? '1' : '0');
            fd.append('name', this.form.name);
            fd.append('email', this.form.email);
            fd.append('contact', this.form.contact);
            fd.append('user_identifier', this.form.user_identifier);
            fd.append('category', this.form.category);
            fd.append('department', this.form.department);
            fd.append('rating', this.form.rating);
            fd.append('subject', this.form.subject);
            fd.append('message', this.form.message);
            if (this.fileAttachment) {
                fd.append('attachment', this.fileAttachment);
            }

            try {
                const res = await fetch('process/submit_feedback.php', {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();

                if (data.success) {
                    this.submittedTicket = data.feedback_no;
                    this.isSubmitted = true;
                } else {
                    this.errorMessage = data.message || 'Submission failed. Please check required fields.';
                }
            } catch (err) {
                this.errorMessage = 'Network or server error while submitting feedback. Please try again.';
            } finally {
                this.isSubmitting = false;
            }
        }
    }
}
</script>

<?php require 'includes/footer.php'; ?>
