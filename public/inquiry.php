<?php
require 'includes/header.php';
?>

<div class="min-h-screen py-16 px-4 sm:px-6 lg:px-8 bg-gray-50 dark:bg-gray-900 relative overflow-hidden" x-data="inquiryForm()" x-cloak>
    <!-- Background decorative blur blobs -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-[10%] left-[-10%] w-[300px] sm:w-[500px] h-[300px] sm:h-[500px] rounded-full bg-amber-100/40 dark:bg-amber-950/10 blur-[80px] sm:blur-[120px] transition-all duration-1000"></div>
        <div class="absolute bottom-[20%] right-[-10%] w-[350px] sm:w-[600px] h-[350px] sm:h-[600px] rounded-full bg-emerald-50/40 dark:bg-emerald-950/5 blur-[80px] sm:blur-[150px] transition-all duration-1000"></div>
    </div>

    <div class="max-w-2xl mx-auto relative z-10">
        <!-- Header Section -->
        <div class="text-center mb-10">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 mb-4 border border-amber-200/50 dark:border-amber-800/30 uppercase tracking-wider">
                <i class="fa-solid fa-envelope-open-text"></i> Support & Feedback
            </span>
            <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-955 dark:text-white tracking-tight">
                Submit Your <span class="text-amber-500">Inquiry</span>
            </h1>
            <p class="text-sm sm:text-base text-gray-500 dark:text-gray-400 mt-3 max-w-md mx-auto leading-relaxed">
                Share your problem or suggestion. Our team will review and respond promptly.
            </p>
        </div>

        <!-- Form Card Container -->
        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md shadow-[0_20px_50px_rgba(0,0,0,0.03)] dark:shadow-[0_20px_50px_rgba(0,0,0,0.25)] border border-gray-200/60 dark:border-gray-700/50 rounded-[2rem] p-6 sm:p-10">
            <form action="process/submit_inquiry.php" method="POST" enctype="multipart/form-data" class="space-y-6" @submit="submitForm($event)" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">
                
                <!-- Full Name Field -->
                <div>
                    <label for="name" class="block text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">
                        Full Name <span class="text-amber-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 dark:text-gray-500 text-sm">
                            <i class="fa-regular fa-user"></i>
                        </span>
                        <input type="text" id="name" name="name" x-model="name" @input="validateName()" required 
                               class="w-full pl-11 pr-4 py-3.5 bg-gray-50/40 dark:bg-gray-950/40 border border-gray-200 dark:border-gray-700/60 rounded-2xl focus:bg-white dark:focus:bg-gray-900 focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 outline-none text-gray-900 dark:text-white transition-all duration-300 placeholder-gray-400 text-sm" 
                               placeholder="Your full name">
                    </div>
                    <p x-show="nameError" x-text="nameError" class="text-xs text-red-500 mt-1.5 font-medium flex items-center gap-1" style="display: none;"></p>
                </div>

                <!-- Email Address Field -->
                <div>
                    <label for="email" class="block text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">
                        Email Address <span class="text-amber-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 dark:text-gray-500 text-sm">
                            <i class="fa-regular fa-envelope"></i>
                        </span>
                        <input type="email" id="email" name="email" x-model="email" @input="validateEmail()" required 
                               class="w-full pl-11 pr-4 py-3.5 bg-gray-50/40 dark:bg-gray-950/40 border border-gray-200 dark:border-gray-700/60 rounded-2xl focus:bg-white dark:focus:bg-gray-900 focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 outline-none text-gray-900 dark:text-white transition-all duration-300 placeholder-gray-400 text-sm" 
                               placeholder="your.email@example.com">
                    </div>
                    <p x-show="emailError" x-text="emailError" class="text-xs text-red-500 mt-1.5 font-medium flex items-center gap-1" style="display: none;"></p>
                </div>

                <!-- Phone Number Field -->
                <div>
                    <label for="phone" class="block text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">
                        Phone Number <span class="text-amber-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 dark:text-gray-500 text-sm">
                            <i class="fa-solid fa-mobile-screen-button"></i>
                        </span>
                        <input type="tel" id="phone" required name="phone" x-model="phone" @input="validatePhone()" maxlength="10" pattern="^[6-9][0-9]{9}$" title="Phone number must start with 6, 7, 8 or 9 and be exactly 10 digits" 
                               class="w-full pl-11 pr-4 py-3.5 bg-gray-50/40 dark:bg-gray-950/40 border border-gray-200 dark:border-gray-700/60 rounded-2xl focus:bg-white dark:focus:bg-gray-900 focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 outline-none text-gray-900 dark:text-white transition-all duration-300 placeholder-gray-400 text-sm" 
                               placeholder="e.g. 9876543210">
                    </div>
                    <p x-show="phoneError" x-text="phoneError" class="text-xs text-red-500 mt-1.5 font-medium flex items-center gap-1" style="display: none;"></p>
                </div>

                <!-- Problem Description Field -->
                <div>
                    <label for="problem" class="block text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">
                        Describe Your Problem <span class="text-amber-500">*</span>
                    </label>
                    <textarea id="problem" name="problem" rows="5" required x-model="problem" @input="validateProblem()" 
                              class="w-full px-4 py-3.5 bg-gray-50/40 dark:bg-gray-950/40 border border-gray-200 dark:border-gray-700/60 rounded-2xl focus:bg-white dark:focus:bg-gray-900 focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 outline-none text-gray-900 dark:text-white transition-all duration-300 placeholder-gray-400 text-sm resize-none" 
                              placeholder="Please describe your issue in detail..."></textarea>
                    <p x-show="problemError" x-text="problemError" class="text-xs text-red-500 mt-1.5 font-medium flex items-center gap-1" style="display: none;"></p>
                </div>

                <!-- Category and Urgency Fields (2-column layout on medium+ screens) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label for="category" class="block text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">
                            Category <span class="text-amber-500">*</span>
                        </label>
                        <div class="relative font-Outfit">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 dark:text-gray-500 text-sm">
                                <i class="fa-solid fa-list-check"></i>
                            </span>
                            <select id="category" name="category" x-model="category" @change="validateCategory()" 
                                    class="w-full pl-11 pr-10 py-3.5 bg-gray-50/40 dark:bg-gray-950/40 border border-gray-200 dark:border-gray-700/60 rounded-2xl focus:bg-white dark:focus:bg-gray-900 focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 outline-none text-gray-900 dark:text-white transition-all duration-300 text-sm appearance-none cursor-pointer">
                                <option value="">Select Category</option>
                                <option value="membership">Membership Issue</option>
                                <option value="donation">Donation Problem</option>
                                <option value="volunteer">Volunteer Related</option>
                                <option value="event">Event Registration</option>
                                <option value="general">General Inquiry</option>
                            </select>
                            <span class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-gray-400 text-xs">
                                <i class="fa-solid fa-chevron-down"></i>
                            </span>
                        </div>
                        <p x-show="categoryError" x-text="categoryError" class="text-xs text-red-500 mt-1.5 font-medium flex items-center gap-1" style="display: none;"></p>
                    </div>
                    <div>
                        <label for="urgency" class="block text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">
                            Urgency
                        </label>
                        <div class="relative font-Outfit">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 dark:text-gray-500 text-sm">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                            </span>
                            <select id="urgency" name="urgency" x-model="urgency" 
                                    class="w-full pl-11 pr-10 py-3.5 bg-gray-50/40 dark:bg-gray-950/40 border border-gray-200 dark:border-gray-700/60 rounded-2xl focus:bg-white dark:focus:bg-gray-900 focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 outline-none text-gray-900 dark:text-white transition-all duration-300 text-sm appearance-none cursor-pointer">
                                <option value="normal">Normal</option>
                                <option value="urgent">Urgent</option>
                                <option value="critical">Critical</option>
                            </select>
                            <span class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-gray-400 text-xs">
                                <i class="fa-solid fa-chevron-down"></i>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Custom Drag and Drop style attachment element -->
                <div class="border-2 border-dashed border-gray-200 dark:border-gray-700 hover:border-amber-400 dark:hover:border-amber-500/50 rounded-2xl p-6 bg-gray-50/20 dark:bg-gray-950/10 transition-all duration-300">
                    <label for="attachment" class="flex flex-col items-center cursor-pointer">
                        <div class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-950/30 flex items-center justify-center text-amber-500 dark:text-amber-400 mb-3 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                            </svg>
                        </div>
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1">Attach supporting documents</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">PDF, JPG, PNG up to 5MB (Optional)</p>
                        <input type="file" id="attachment" name="attachment" accept=".pdf,.jpg,.jpeg,.png" class="hidden" 
                               @change="fileName = $event.target.files[0] ? $event.target.files[0].name : ''">
                    </label>
                    <div x-show="fileName" class="mt-3 text-xs text-amber-600 dark:text-amber-400 font-medium flex items-center justify-center gap-1.5 bg-amber-50/50 dark:bg-amber-950/20 py-1.5 px-3 rounded-lg border border-amber-100/50 dark:border-amber-950/40" style="display:none;">
                        <i class="fa-solid fa-file-invoice"></i> Selected: <span class="font-semibold truncate max-w-[200px]" x-text="fileName"></span>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full py-4 px-6 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-bold text-base rounded-2xl shadow-[0_10px_20px_rgba(244,166,64,0.15)] hover:shadow-[0_10px_25px_rgba(244,166,64,0.25)] transition-all duration-300 transform active:scale-[0.99] flex items-center justify-center gap-2">
                    <span>Submit Inquiry</span>
                    <i class="fa-solid fa-arrow-right-long text-sm"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('inquiryForm', () => ({
        name: '',
        email: '',
        phone: '',
        problem: '',
        category: '',
        urgency: 'normal',
        fileName: '', // Dynamic filename binding properties
        
        nameError: '',
        emailError: '',
        phoneError: '',
        problemError: '',
        categoryError: '',
        
        validateName() {
            if (!this.name.trim()) {
                this.nameError = 'Full name is required.';
            } else if (this.name.trim().length < 2) {
                this.nameError = 'Name must be at least 2 characters.';
            } else if (!/^[a-zA-Z\s]+$/.test(this.name.trim())) {
                this.nameError = 'Name can only contain letters and spaces.';
            } else {
                this.nameError = '';
            }
        },
        validateEmail() {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!this.email.trim()) {
                this.emailError = 'Email address is required.';
            } else if (!emailRegex.test(this.email.trim())) {
                this.emailError = 'Please enter a valid email address.';
            } else {
                this.emailError = '';
            }
        },
        validatePhone() {
            this.phone = this.phone.replace(/[^0-9]/g, '');
            if (this.phone.length > 10) {
                this.phone = this.phone.substring(0, 10);
            }
            if (!this.phone.trim()) {
                this.phoneError = 'Phone number is required.';
            } else if (this.phone.length !== 10) {
                this.phoneError = 'Phone number must be exactly 10 digits.';
            } else if (!/^[6-9]/.test(this.phone)) {
                this.phoneError = 'Phone number must start with 6, 7, 8, or 9.';
            } else {
                this.phoneError = '';
            }
        },
        validateProblem() {
            if (!this.problem.trim()) {
                this.problemError = 'Description of the problem is required.';
            } else if (this.problem.trim().length < 10) {
                this.problemError = 'Please describe the problem in more detail (at least 10 characters).';
            } else {
                this.problemError = '';
            }
        },
        validateCategory() {
            if (!this.category) {
                this.categoryError = 'Please select a category.';
            } else {
                this.categoryError = '';
            }
        },
        
        submitForm(event) {
            this.validateName();
            this.validateEmail();
            this.validatePhone();
            this.validateProblem();
            this.validateCategory();
            
            if (this.nameError || this.emailError || this.phoneError || this.problemError || this.categoryError) {
                event.preventDefault();
                return false;
            }
            return true;
        }
    }));
});
</script>

<?php require 'includes/footer.php'; ?>
