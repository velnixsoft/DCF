<?php
// ============================================================
// public/complaint.php
// Public Complaint & Suggestion Submission Form
// Design pattern matches public/inquiry.php with real-time validation,
// unique ticket generation, and instant confirmation email dispatch
// ============================================================

require 'includes/header.php';
$csrfToken = generateCsrfToken();
?>

<div class="min-h-screen py-12 md:py-16 px-4 sm:px-6 lg:px-8 bg-gray-50 dark:bg-gray-900 relative overflow-hidden" 
     x-data="complaintForm()" 
     x-cloak>

    <!-- Background decorative blur blobs -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-[8%] left-[-10%] w-[320px] sm:w-[500px] h-[320px] sm:h-[500px] rounded-full bg-teal-100/40 dark:bg-teal-950/20 blur-[90px] sm:blur-[130px] transition-all duration-1000"></div>
        <div class="absolute bottom-[15%] right-[-10%] w-[350px] sm:w-[550px] h-[350px] sm:h-[550px] rounded-full bg-amber-100/40 dark:bg-amber-950/15 blur-[90px] sm:blur-[140px] transition-all duration-1000"></div>
    </div>

    <div class="max-w-2xl mx-auto relative z-10">

        <!-- ════════════════════════════════════════════════════════════ -->
        <!-- SUCCESS STATE CARD (Rendered after submission)               -->
        <!-- ════════════════════════════════════════════════════════════ -->
        <template x-if="isSubmitted">
            <div class="bg-white/90 dark:bg-gray-800/90 backdrop-blur-md shadow-2xl border border-emerald-200/80 dark:border-emerald-800/50 rounded-[2.5rem] p-8 sm:p-12 text-center space-y-6 animate-fade-in">
                
                <div class="w-20 h-20 mx-auto rounded-3xl bg-emerald-50 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-3xl shadow-sm">
                    <i class="fa-solid fa-circle-check"></i>
                </div>

                <div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 dark:bg-emerald-900/50 text-emerald-800 dark:text-emerald-300 mb-2 uppercase tracking-wider">
                        <i class="fa-solid fa-receipt"></i> Ticket Generated Successfully
                    </span>
                    <h2 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white">
                        Thank You, <span x-text="form.name"></span>!
                    </h2>
                    <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-2 max-w-md mx-auto leading-relaxed">
                        Your <span class="font-bold text-gray-700 dark:text-gray-200" x-text="form.type"></span> has been registered into our tracking system. A confirmation email has been dispatched with full ticket particulars.
                    </p>
                </div>

                <!-- Ticket Reference Display Box -->
                <div class="p-6 rounded-2xl bg-slate-50 dark:bg-gray-900/80 border border-slate-200 dark:border-gray-700 space-y-3">
                    <span class="text-[11px] font-bold text-gray-400 uppercase tracking-widest block">Your Unique Ticket Reference</span>
                    <div class="flex items-center justify-center gap-3">
                        <span class="text-2xl sm:text-3xl font-mono font-black text-[#0F8B8D] dark:text-teal-400 tracking-wider" x-text="submittedTicket"></span>
                        <button type="button" @click="copyTicket()" class="px-3 py-1.5 rounded-xl bg-teal-50 hover:bg-teal-100 dark:bg-teal-900/40 text-[#0F8B8D] dark:text-teal-300 text-xs font-bold transition flex items-center gap-1">
                            <i class="fa-solid" :class="copied ? 'fa-check text-emerald-600' : 'fa-copy'"></i>
                            <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                        </button>
                    </div>
                    <p class="text-[11px] text-gray-400">
                        Please save this reference number for future communication and status inquiries.
                    </p>
                </div>

                <!-- Email notice -->
                <div class="flex items-center justify-center gap-2 text-xs text-slate-600 dark:text-gray-300" x-show="form.email">
                    <i class="fa-solid fa-envelope-circle-check text-teal-600 text-base"></i>
                    <span>Confirmation sent to: <strong class="text-gray-900 dark:text-white" x-text="form.email"></strong></span>
                </div>

                <div class="pt-4 flex flex-col sm:flex-row items-center justify-center gap-3">
                    <a :href="'track-complaint.php?ticket=' + encodeURIComponent(submittedTicket)" class="w-full sm:w-auto px-6 py-3.5 rounded-2xl bg-[#0F8B8D] hover:bg-[#0c7274] text-white font-bold text-xs transition shadow-md flex items-center justify-center gap-2">
                        <i class="fa-solid fa-magnifying-glass-location"></i> Track This Ticket Now
                    </a>
                    <button type="button" @click="resetForm()" class="w-full sm:w-auto px-6 py-3.5 rounded-2xl bg-slate-100 dark:bg-gray-700 hover:bg-slate-200 dark:hover:bg-gray-600 text-gray-800 dark:text-white font-bold text-xs transition">
                        <i class="fa-solid fa-plus mr-1.5"></i> Submit Another Ticket
                    </button>
                    <a href="index.php" class="w-full sm:w-auto px-5 py-3.5 rounded-2xl text-gray-500 hover:text-gray-800 dark:hover:text-white font-bold text-xs transition">
                        Return Home
                    </a>
                </div>

            </div>
        </template>


        <!-- ════════════════════════════════════════════════════════════ -->
        <!-- FORM CONTAINER (When not submitted)                          -->
        <!-- ════════════════════════════════════════════════════════════ -->
        <div x-show="!isSubmitted">
            
            <!-- Header Section -->
            <div class="text-center mb-8">
                <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold bg-teal-50 dark:bg-teal-900/30 text-[#0F8B8D] dark:text-teal-300 mb-3 border border-teal-200/60 dark:border-teal-800/40 uppercase tracking-wider shadow-sm">
                    <i class="fa-solid fa-comments"></i> Citizen Grievance & Ideas Portal
                </span>
                <h1 class="text-3xl sm:text-4xl font-black text-gray-900 dark:text-white tracking-tight">
                    Complaints & <span class="text-[#0F8B8D]">Suggestions</span>
                </h1>
                <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-2.5 max-w-md mx-auto leading-relaxed">
                    We value your feedback. Submit your grievances or constructive ideas to help us improve our community impact and services.
                </p>
            </div>

            <!-- Main Form Card Container -->
            <div class="bg-white/85 dark:bg-gray-800/85 backdrop-blur-md shadow-[0_20px_50px_rgba(0,0,0,0.04)] dark:shadow-[0_20px_50px_rgba(0,0,0,0.3)] border border-gray-200/70 dark:border-gray-700/60 rounded-[2.5rem] p-6 sm:p-10 space-y-6">
                
                <form @submit.prevent="submitComplaint()" enctype="multipart/form-data" class="space-y-5" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                    <!-- 1. Type Switcher: Complaint vs Suggestion -->
                    <div>
                        <label class="block text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">
                            Select Submission Type <span class="text-rose-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 gap-3 p-1.5 bg-slate-100 dark:bg-gray-950/60 rounded-2xl border border-slate-200 dark:border-gray-700">
                            
                            <!-- Complaint Option -->
                            <button type="button" @click="form.type = 'complaint'" 
                                    :class="form.type === 'complaint' ? 'bg-white dark:bg-gray-800 text-rose-600 dark:text-rose-400 shadow-sm font-black border border-rose-200/80 dark:border-rose-900/50' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 font-semibold'"
                                    class="py-3 px-4 rounded-xl text-xs flex items-center justify-center gap-2 transition duration-200">
                                <i class="fa-solid fa-triangle-exclamation text-sm"></i>
                                <span>Official Complaint</span>
                            </button>

                            <!-- Suggestion Option -->
                            <button type="button" @click="form.type = 'suggestion'" 
                                    :class="form.type === 'suggestion' ? 'bg-white dark:bg-gray-800 text-teal-600 dark:text-teal-400 shadow-sm font-black border border-teal-200/80 dark:border-teal-900/50' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 font-semibold'"
                                    class="py-3 px-4 rounded-xl text-xs flex items-center justify-center gap-2 transition duration-200">
                                <i class="fa-solid fa-lightbulb text-sm"></i>
                                <span>Creative Suggestion</span>
                            </button>

                        </div>
                    </div>

                    <!-- 2. Full Name -->
                    <div>
                        <label for="name" class="block text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">
                            Full Name <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 text-sm">
                                <i class="fa-regular fa-user"></i>
                            </span>
                            <input type="text" id="name" name="name" x-model="form.name" @input="validateName()" required 
                                   class="w-full pl-11 pr-4 py-3 bg-gray-50/50 dark:bg-gray-950/40 border border-gray-200 dark:border-gray-700/70 rounded-2xl focus:bg-white dark:focus:bg-gray-900 focus:ring-4 focus:ring-[#0F8B8D]/10 focus:border-[#0F8B8D] outline-none text-gray-900 dark:text-white transition-all text-xs sm:text-sm placeholder-gray-400" 
                                   placeholder="Enter your full name">
                        </div>
                        <p x-show="errors.name" x-text="errors.name" class="text-[11px] text-rose-500 mt-1 font-medium flex items-center gap-1"></p>
                    </div>

                    <!-- 3. Contact & Email Row -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        
                        <!-- Contact Phone -->
                        <div>
                            <label for="contact" class="block text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">
                                Phone Number <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 text-sm">
                                    <i class="fa-solid fa-phone"></i>
                                </span>
                                <input type="tel" id="contact" name="contact" x-model="form.contact" @input="validateContact()" maxlength="10" required 
                                       class="w-full pl-11 pr-4 py-3 bg-gray-50/50 dark:bg-gray-950/40 border border-gray-200 dark:border-gray-700/70 rounded-2xl focus:bg-white dark:focus:bg-gray-900 focus:ring-4 focus:ring-[#0F8B8D]/10 focus:border-[#0F8B8D] outline-none text-gray-900 dark:text-white transition-all text-xs sm:text-sm placeholder-gray-400" 
                                       placeholder="10-digit mobile number">
                            </div>
                            <p x-show="errors.contact" x-text="errors.contact" class="text-[11px] text-rose-500 mt-1 font-medium flex items-center gap-1"></p>
                        </div>

                        <!-- Email Address -->
                        <div>
                            <label for="email" class="block text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">
                                Email Address <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 text-sm">
                                    <i class="fa-regular fa-envelope"></i>
                                </span>
                                <input type="email" id="email" name="email" x-model="form.email" @input="validateEmail()" required 
                                       class="w-full pl-11 pr-4 py-3 bg-gray-50/50 dark:bg-gray-950/40 border border-gray-200 dark:border-gray-700/70 rounded-2xl focus:bg-white dark:focus:bg-gray-900 focus:ring-4 focus:ring-[#0F8B8D]/10 focus:border-[#0F8B8D] outline-none text-gray-900 dark:text-white transition-all text-xs sm:text-sm placeholder-gray-400" 
                                       placeholder="your.email@example.com">
                            </div>
                            <p x-show="errors.email" x-text="errors.email" class="text-[11px] text-rose-500 mt-1 font-medium flex items-center gap-1"></p>
                        </div>

                    </div>

                    <!-- 4. Subject & Priority Row -->
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                        
                        <!-- Subject -->
                        <div class="sm:col-span-8">
                            <label for="subject" class="block text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">
                                Subject / Matter <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 text-sm">
                                    <i class="fa-solid fa-heading"></i>
                                </span>
                                <input type="text" id="subject" name="subject" x-model="form.subject" @input="validateSubject()" required 
                                       class="w-full pl-11 pr-4 py-3 bg-gray-50/50 dark:bg-gray-950/40 border border-gray-200 dark:border-gray-700/70 rounded-2xl focus:bg-white dark:focus:bg-gray-900 focus:ring-4 focus:ring-[#0F8B8D]/10 focus:border-[#0F8B8D] outline-none text-gray-900 dark:text-white transition-all text-xs sm:text-sm placeholder-gray-400" 
                                       :placeholder="form.type === 'complaint' ? 'Brief summary of the issue...' : 'Brief title of your suggestion...'">
                            </div>
                            <p x-show="errors.subject" x-text="errors.subject" class="text-[11px] text-rose-500 mt-1 font-medium flex items-center gap-1"></p>
                        </div>

                        <!-- Priority -->
                        <div class="sm:col-span-4">
                            <label for="priority" class="block text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">
                                Priority Level
                            </label>
                            <div class="relative">
                                <select id="priority" name="priority" x-model="form.priority" 
                                        class="w-full px-4 py-3 bg-gray-50/50 dark:bg-gray-950/40 border border-gray-200 dark:border-gray-700/70 rounded-2xl focus:bg-white dark:focus:bg-gray-900 focus:ring-4 focus:ring-[#0F8B8D]/10 focus:border-[#0F8B8D] outline-none text-gray-900 dark:text-white transition-all text-xs sm:text-sm appearance-none cursor-pointer">
                                    <option value="low">Low</option>
                                    <option value="medium">Medium (Standard)</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                                <span class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-gray-400 text-xs">
                                    <i class="fa-solid fa-chevron-down"></i>
                                </span>
                            </div>
                        </div>

                    </div>

                    <!-- 5. Description -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="description" class="block text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">
                                <span x-text="form.type === 'complaint' ? 'Detailed Description of Grievance' : 'Detailed Explanation of Suggestion'"></span> <span class="text-rose-500">*</span>
                            </label>
                            <span class="text-[10px] text-gray-400" x-text="form.description.length + ' chars'"></span>
                        </div>
                        <textarea id="description" name="description" rows="5" x-model="form.description" @input="validateDescription()" required 
                                  class="w-full p-4 bg-gray-50/50 dark:bg-gray-950/40 border border-gray-200 dark:border-gray-700/70 rounded-2xl focus:bg-white dark:focus:bg-gray-900 focus:ring-4 focus:ring-[#0F8B8D]/10 focus:border-[#0F8B8D] outline-none text-gray-900 dark:text-white transition-all text-xs sm:text-sm placeholder-gray-400 resize-none leading-relaxed" 
                                  :placeholder="form.type === 'complaint' ? 'Please provide clear facts, dates, location, persons involved, or any previous references...' : 'Explain your idea, expected social impact, and how the foundation can implement it...'"></textarea>
                        <p x-show="errors.description" x-text="errors.description" class="text-[11px] text-rose-500 mt-1 font-medium flex items-center gap-1"></p>
                    </div>

                    <!-- 6. Optional Supporting File Attachment -->
                    <div class="border-2 border-dashed border-gray-200 dark:border-gray-700 hover:border-teal-400 dark:hover:border-teal-500/50 rounded-2xl p-5 bg-gray-50/30 dark:bg-gray-950/20 transition-all">
                        <label for="attachment" class="flex flex-col items-center cursor-pointer">
                            <div class="w-10 h-10 rounded-2xl bg-teal-50 dark:bg-teal-950/40 flex items-center justify-center text-[#0F8B8D] dark:text-teal-400 mb-2">
                                <i class="fa-solid fa-cloud-arrow-up text-lg"></i>
                            </div>
                            <p class="text-xs font-bold text-gray-700 dark:text-gray-200 mb-0.5">Attach Supporting Document / Photo (Optional)</p>
                            <p class="text-[10px] text-gray-400 dark:text-gray-500">PDF, JPG, PNG up to 5MB</p>
                            <input type="file" id="attachment" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="hidden" 
                                   @change="fileName = $event.target.files[0] ? $event.target.files[0].name : ''">
                        </label>
                        <div x-show="fileName" class="mt-2 text-xs text-[#0F8B8D] font-bold flex items-center justify-center gap-1.5 bg-teal-50/50 dark:bg-teal-950/30 py-1 px-3 rounded-lg border border-teal-200/50" style="display:none;">
                            <i class="fa-solid fa-paperclip"></i> Selected: <span class="truncate max-w-[220px]" x-text="fileName"></span>
                            <button type="button" @click="fileName = ''; document.getElementById('attachment').value = '';" class="text-rose-500 hover:text-rose-700 ml-1">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Error Alert banner if submission fails -->
                    <div x-show="serverError" x-collapse class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-900/30 border border-rose-200 dark:border-rose-800/50 text-rose-700 dark:text-rose-300 text-xs font-semibold flex items-center gap-2">
                        <i class="fa-solid fa-circle-exclamation text-base"></i>
                        <span x-text="serverError"></span>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" :disabled="loading" 
                            class="w-full py-4 px-6 text-white font-black text-sm rounded-2xl shadow-lg transition-all duration-300 transform active:scale-[0.99] flex items-center justify-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed"
                            :class="form.type === 'complaint' ? 'bg-gradient-to-r from-rose-600 to-rose-700 hover:from-rose-700 hover:to-rose-800 shadow-rose-600/20' : 'bg-gradient-to-r from-[#0F8B8D] to-teal-700 hover:from-[#0c7274] hover:to-teal-800 shadow-teal-600/20'">
                        <span x-show="!loading" class="flex items-center gap-2">
                            <span x-text="form.type === 'complaint' ? 'Lodge Official Complaint' : 'Submit Creative Suggestion'"></span>
                            <i class="fa-solid fa-arrow-right text-xs"></i>
                        </span>
                        <span x-show="loading" class="flex items-center gap-2">
                            <i class="fa-solid fa-circle-notch fa-spin text-sm"></i>
                            <span>Processing & Dispatching Email...</span>
                        </span>
                    </button>

                </form>

            </div>

        </div>

    </div>

</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('complaintForm', () => ({
        form: {
            type: 'complaint',
            name: '',
            contact: '',
            email: '',
            subject: '',
            priority: 'medium',
            description: ''
        },
        fileName: '',
        errors: {},
        loading: false,
        isSubmitted: false,
        submittedTicket: '',
        copied: false,
        serverError: '',

        validateName() {
            if (!this.form.name.trim()) {
                this.errors.name = 'Full name is required.';
            } else if (this.form.name.trim().length < 2) {
                this.errors.name = 'Name must be at least 2 characters.';
            } else {
                delete this.errors.name;
            }
        },

        validateContact() {
            const clean = this.form.contact.replace(/[^0-9]/g, '');
            this.form.contact = clean;
            if (!clean) {
                this.errors.contact = 'Phone number is required.';
            } else if (clean.length !== 10) {
                this.errors.contact = 'Phone number must be exactly 10 digits.';
            } else if (!/^[6-9]/.test(clean)) {
                this.errors.contact = 'Mobile number must start with 6, 7, 8, or 9.';
            } else {
                delete this.errors.contact;
            }
        },

        validateEmail() {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!this.form.email.trim()) {
                this.errors.email = 'Email address is required for confirmation.';
            } else if (!emailRegex.test(this.form.email.trim())) {
                this.errors.email = 'Please enter a valid email address.';
            } else {
                delete this.errors.email;
            }
        },

        validateSubject() {
            if (!this.form.subject.trim()) {
                this.errors.subject = 'Subject is required.';
            } else if (this.form.subject.trim().length < 4) {
                this.errors.subject = 'Subject must be at least 4 characters.';
            } else {
                delete this.errors.subject;
            }
        },

        validateDescription() {
            if (!this.form.description.trim()) {
                this.errors.description = 'Please provide details.';
            } else if (this.form.description.trim().length < 10) {
                this.errors.description = 'Description must be at least 10 characters.';
            } else {
                delete this.errors.description;
            }
        },

        copyTicket() {
            if (!this.submittedTicket) return;
            navigator.clipboard.writeText(this.submittedTicket).then(() => {
                this.copied = true;
                setTimeout(() => { this.copied = false; }, 2500);
            });
        },

        resetForm() {
            this.form = {
                type: 'complaint',
                name: '',
                contact: '',
                email: '',
                subject: '',
                priority: 'medium',
                description: ''
            };
            this.fileName = '';
            this.errors = {};
            this.isSubmitted = false;
            this.submittedTicket = '';
            this.serverError = '';
        },

        async submitComplaint() {
            this.validateName();
            this.validateContact();
            this.validateEmail();
            this.validateSubject();
            this.validateDescription();

            if (Object.keys(this.errors).length > 0) {
                return;
            }

            this.loading = true;
            this.serverError = '';

            const formData = new FormData();
            formData.append('type', this.form.type);
            formData.append('name', this.form.name);
            formData.append('contact', this.form.contact);
            formData.append('email', this.form.email);
            formData.append('subject', this.form.subject);
            formData.append('priority', this.form.priority);
            formData.append('description', this.form.description);

            const fileInput = document.getElementById('attachment');
            if (fileInput && fileInput.files[0]) {
                formData.append('attachment', fileInput.files[0]);
            }

            try {
                const res = await fetch('process/submit_complaint.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await res.json();

                if (data.success) {
                    this.submittedTicket = data.ticket_no || 'TKT-LOGGED';
                    this.isSubmitted = true;
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                } else {
                    this.serverError = data.message || 'Submission failed. Please verify your details.';
                }
            } catch (err) {
                this.serverError = 'A network error occurred. Please check your connection and try again.';
            } finally {
                this.loading = false;
            }
        }
    }));
});
</script>

<?php require 'includes/footer.php'; ?>
