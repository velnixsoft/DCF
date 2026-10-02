<?php
require 'includes/header.php';
$refCode = isset($_GET['ref']) ? strtoupper(trim((string)$_GET['ref'])) : '';
$studentRegisterCsrf = generateCsrfToken();
?>

<div class="bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-emerald-50 via-slate-50 to-slate-100 min-h-screen py-16 px-4 font-sans"
     x-data="studentRegister()">

    <div class="container mx-auto max-w-3xl">
        <div class="bg-white/95 backdrop-blur-xl p-8 md:p-14 rounded-[2.5rem] shadow-2xl border border-slate-200/50 relative overflow-hidden">
            <!-- Top brand accent -->
            <div class="absolute top-0 left-0 right-0 h-[5px] bg-gradient-to-r from-emerald-400 via-emerald-600 to-green-600"></div>
            
            <div class="text-center mb-12">
                <div class="w-16 h-16 bg-gradient-to-br from-emerald-50 to-green-100/50 text-emerald-700 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-md shadow-emerald-500/10 ring-4 ring-emerald-50/70 border border-emerald-200/40 relative">
                    <span class="absolute inset-0 rounded-2xl border-2 border-emerald-500/10 animate-ping opacity-75"></span>
                    <i class="fas fa-graduation-cap text-3xl"></i>
                </div>
                <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight">Become an Ambassador</h1>
                <p class="text-xs font-semibold text-slate-400 mt-2.5 uppercase tracking-wider">Student & Intern Application</p>
                <p class="text-sm text-slate-500 mt-4 leading-relaxed max-w-md mx-auto">Join SEED Council's community initiative to build leadership, earn points, and make real-world social impact.</p>
            </div>

            <form @submit.prevent="submitForm($event)" class="space-y-8">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($studentRegisterCsrf); ?>">

                <!-- Step 1 Section -->
                <div class="bg-slate-50/60 p-6 md:p-8 rounded-[2rem] border border-slate-100 space-y-6">
                    <h3 class="font-extrabold text-slate-900 flex items-center gap-3 text-lg">
                        <span class="w-7 h-7 rounded-xl bg-emerald-500 text-white flex items-center justify-center text-sm font-black shadow-md shadow-emerald-500/10">1</span>
                        Personal Details
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="md:col-span-2 space-y-1.5">
                            <label for="full_name" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Full Name *</label>
                            <input type="text" id="full_name" name="full_name" required 
                                pattern="[a-zA-Z\s]+"
                                title="Full Name should only contain letters and spaces."
                                oninput="this.value=this.value.replace(/[^a-zA-Z\s]/g,'')"
                                class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 bg-white/70 focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition duration-150 text-sm font-semibold text-slate-800"
                                placeholder="Enter your full name">
                        </div>

                        <div class="space-y-1.5">
                            <label for="email" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Email Address *</label>
                            <input type="email" id="email" name="email" required 
                                class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 bg-white/70 focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition duration-150 text-sm font-semibold text-slate-800"
                                placeholder="you@example.com">
                        </div>

                        <div class="space-y-1.5">
                            <label for="mobile" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Mobile Number *</label>
                           <input type="tel"
        id="mobile"
        name="mobile"
        required
        maxlength="10"
        pattern="(?!([0-9])\1{9})[6-9][0-9]{9}"
        title="Please enter a valid 10-digit mobile number starting with 6, 7, 8, or 9 (cannot be all identical digits)."
        inputmode="numeric"
        oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,10)"
        @input="validateMobileField($event.target)"
        @blur="validateMobileField($event.target)"
        class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 bg-white/70 focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition duration-150 text-sm font-semibold text-slate-800"
        placeholder="10-digit number">
                        </div>

                        <div class="space-y-1.5">
                            <label for="password" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Choose Password *</label>
                            <input type="password" id="password" name="password" required minlength="6"
                                @input="validatePasswordConfirm($el.closest('form'))"
                                class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 bg-white/70 focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition duration-150 text-sm font-semibold text-slate-800"
                                placeholder="At least 6 characters">
                        </div>

                        <div class="space-y-1.5">
                            <label for="confirm_password" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Confirm Password *</label>
                            <input type="password" id="confirm_password" name="confirm_password" required minlength="6"
                                @input="validatePasswordConfirm($el.closest('form'))"
                                class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 bg-white/70 focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition duration-150 text-sm font-semibold text-slate-800"
                                placeholder="Confirm your password">
                        </div>

                        <div class="space-y-1.5">
                            <label for="gender" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Gender *</label>
                            <select id="gender" name="gender" required 
                                class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 bg-white/70 focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition duration-150 text-sm font-semibold text-slate-800">
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Step 2 Section -->
                <div class="bg-slate-50/60 p-6 md:p-8 rounded-[2rem] border border-slate-100 space-y-6">
                    <h3 class="font-extrabold text-slate-900 flex items-center gap-3 text-lg">
                        <span class="w-7 h-7 rounded-xl bg-emerald-500 text-white flex items-center justify-center text-sm font-black shadow-md shadow-emerald-500/10">2</span>
                        College & Location Details
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="md:col-span-2 space-y-1.5">
                            <label for="college_name" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">College / Institution *</label>
                            <input type="text" id="college_name" name="college_name" required 
                                pattern="[A-Za-z\s]+"
                                title="College / Institution should only contain letters and spaces."
                                oninput="this.value=this.value.replace(/[^A-Za-z\s]/g,'')"
                                @input="validateCollegeField($event.target)"
                                @blur="validateCollegeField($event.target)"
                                class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 bg-white/70 focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition duration-150 text-sm font-semibold text-slate-800"
                                placeholder="Enter full college name">
                        </div>

                        <div class="space-y-1.5">
                            <label for="department_name" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Department / Stream *</label>
                            <input type="text" id="department_name" name="department_name" required 
                                pattern="[A-Za-z\s]+"
                                title="Department / Stream should only contain letters and spaces."
                                oninput="this.value=this.value.replace(/[^A-Za-z\s]/g,'')"
                                @input="validateDepartmentField($event.target)"
                                @blur="validateDepartmentField($event.target)"
                                class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 bg-white/70 focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition duration-150 text-sm font-semibold text-slate-800"
                                placeholder="e.g. Computer Science, Commerce">
                        </div>

                        <div class="space-y-1.5">
                            <label for="year_of_study" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Year of Study *</label>
                            <select id="year_of_study" name="year_of_study" required 
                                class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 bg-white/70 focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition duration-150 text-sm font-semibold text-slate-800">
                                <option value="">Select Year</option>
                                <option value="1st Year">1st Year</option>
                                <option value="2nd Year">2nd Year</option>
                                <option value="3rd Year">3rd Year</option>
                                <option value="4th Year">4th Year</option>
                                <option value="Post Graduate">Post Graduate</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="space-y-1.5">
                            <label for="state_name" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">State *</label>
                            <select id="state_name" name="state_name" required 
                                @change="updateDistricts($event.target.value)"
                                class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 bg-white/70 focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition duration-150 text-sm font-semibold text-slate-800">
                                <option value="">Select State</option>
                                <template x-for="state in states" :key="state">
                                    <option :value="state" x-text="state"></option>
                                </template>
                            </select>
                        </div>

                        <div class="space-y-1.5">
                            <label for="city_name" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">District *</label>
                            <select id="city_name" name="city_name" required
                                x-model="selectedDistrict"
                                class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 bg-white/70 focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition duration-150 text-sm font-semibold text-slate-800">
                                <option value="">Select District</option>
                                <template x-for="dist in districts" :key="dist">
                                    <option :value="dist" x-text="dist"></option>
                                </template>
                            </select>
                        </div>

                        <div class="md:col-span-2 space-y-1.5">
                            <label for="address" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Address Details</label>
                            <textarea id="address" name="address" rows="2" 
                                class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 bg-white/70 focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition duration-150 text-sm font-semibold text-slate-800"
                                placeholder="Enter your full street or locality details"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Step 3 Section -->
                <div class="bg-slate-50/60 p-6 md:p-8 rounded-[2rem] border border-slate-100 space-y-4">
                    <h3 class="font-extrabold text-slate-900 flex items-center gap-3 text-lg">
                        <span class="w-7 h-7 rounded-xl bg-emerald-500 text-white flex items-center justify-center text-sm font-black shadow-md shadow-emerald-500/10">3</span>
                        Referrals & Invitations (Optional)
                    </h3>
                    <div class="space-y-1.5">
                        <label for="referred_by_code" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Referral / Sponsor Code</label>
                        <input type="text" id="referred_by_code" name="referred_by_code" 
                            value="<?php echo htmlspecialchars($refCode); ?>"
                            class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 bg-white/70 focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition duration-150 text-sm font-bold text-slate-800 uppercase font-mono tracking-wider"
                            placeholder="e.g. INT-XXXXXX">
                        <p class="text-[10px] text-slate-400 font-semibold mt-1">If you were referred by another student ambassador, input their code here.</p>
                    </div>
                </div>

                <!-- Error Message banner -->
                <div x-show="formMessage" x-transition
                     :class="formMessageType === 'error' ? 'bg-rose-50/70 text-rose-800 border-rose-100' : 'bg-emerald-50/70 text-emerald-800 border-emerald-100'"
                     class="p-4 rounded-2xl border text-xs font-bold flex items-center gap-2">
                    <i class="fa-solid" :class="formMessageType === 'error' ? 'fa-circle-exclamation text-rose-600' : 'fa-circle-check text-emerald-600'"></i>
                    <span x-text="formMessage"></span>
                </div>

                <div>
                    <!-- Submit button -->
                    <button type="submit" :disabled="loading" 
                        class="w-full bg-green-600 hover:from-emerald-700 hover:to-green-700 text-white font-bold py-4 rounded-2xl shadow-lg shadow-emerald-500/10 hover:shadow-xl hover:shadow-emerald-500/20 active:scale-[0.98] transition-all duration-150 disabled:opacity-60 disabled:cursor-not-allowed text-sm">
                        <span x-show="!loading" class="tracking-wide">Submit Registration</span>
                        <span x-show="loading" class="flex items-center gap-2 justify-center font-semibold">
                            <svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Processing application...
                        </span>
                    </button>
                    <div class="mt-4 text-center text-xs text-slate-500 font-semibold">
                        Already have an ambassador account? <a href="student-login.php" class="text-emerald-600 hover:text-emerald-700 transition font-bold underline decoration-2 decoration-emerald-600/20 hover:decoration-emerald-600/50">Log In here</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Success Modal with Blur Backdrop -->
    <div x-show="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm" x-cloak>
        <div class="bg-white max-w-md w-full rounded-[2.5rem] shadow-2xl p-8 md:p-10 text-center border border-slate-200" @click.away="isModalOpen = false">
            <div class="mx-auto w-16 h-16 bg-emerald-50 border border-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mb-6 ring-8 ring-emerald-50/60">
                <i class="fas fa-check-double text-2xl animate-bounce"></i>
            </div>
            <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Application Submitted!</h2>
            
            <div class="mt-5 bg-slate-50/50 border border-slate-100 rounded-2xl p-5 text-left space-y-2.5 text-xs text-slate-700 font-semibold">
                <p>Ambassador ID: <span class="font-extrabold text-slate-900 font-mono select-all" x-text="generatedStudentNo"></span></p>
                <p>Referral Code: <span class="font-extrabold text-emerald-700 font-mono select-all" x-text="generatedReferralCode"></span></p>
            </div>

            <p class="text-slate-500 mt-5 leading-relaxed text-xs font-semibold">
                Thank you for applying. Your registration is currently pending admin approval. You can log in using your credentials once verified.
            </p>
            <div class="mt-8">
                <a href="student-login.php" class="inline-block w-full bg-gradient-to-r from-emerald-600 to-green-600 hover:from-emerald-700 hover:to-green-700 text-white py-3 rounded-2xl font-bold shadow-lg shadow-emerald-500/10 hover:shadow-xl hover:shadow-emerald-500/20 active:scale-[0.98] transition-all text-xs">Proceed to Login</a>
            </div>
<script>
function studentRegister() {
    return { 
        isModalOpen: false, 
        loading: false, 
        formMessage: '', 
        formMessageType: 'error',
        generatedStudentNo: '',
        generatedReferralCode: '',
        states: [
            'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chhattisgarh', 'Goa', 'Gujarat', 
            'Haryana', 'Himachal Pradesh', 'Jharkhand', 'Karnataka', 'Kerala', 'Madhya Pradesh', 
            'Maharashtra', 'Manipur', 'Meghalaya', 'Mizoram', 'Nagaland', 'Odisha', 'Punjab', 
            'Rajasthan', 'Sikkim', 'Tamil Nadu', 'Telangana', 'Tripura', 'Uttar Pradesh', 
            'Uttarakhand', 'West Bengal', 'Andaman and Nicobar Islands', 'Chandigarh', 
            'Dadra and Nagar Haveli and Daman and Diu', 'Delhi', 'Jammu and Kashmir', 'Ladakh', 
            'Lakshadweep', 'Puducherry'
        ],
        selectedDistrict: '',
        districts: [],
        stateDistrictData: <?php require_once __DIR__ . '/includes/india_locations.php'; echo india_state_district_js(); ?>,
        updateDistricts(stateVal) { this.districts = this.stateDistrictData[stateVal] || []; this.selectedDistrict = ''; },
        validateMobileField(input) {
            const value = (input.value || '').trim();
            const isValid = /^[6-9][0-9]{9}$/.test(value) && !/^(.)\1{9}$/.test(value);
            input.setCustomValidity(value !== '' && !isValid
                ? 'Please enter a valid 10-digit mobile number starting with 6, 7, 8, or 9. Repeated digits like 0000000000 or 1111111111 are not allowed.'
                : ''
            );
            return isValid;
        },
        validatePasswordConfirm(form) {
            const pass = form.querySelector('#password').value;
            const confirmInput = form.querySelector('#confirm_password');
            if (!confirmInput) return true;
            const conf = confirmInput.value;
            const isValid = pass === conf;
            confirmInput.setCustomValidity(isValid ? '' : 'Passwords do not match.');
            return isValid;
        },
        validateCollegeField(input) {
            const value = (input.value || '').trim();
            const isValid = /^[A-Za-z\s]+$/.test(value);
            input.setCustomValidity(value !== '' && !isValid
                ? 'College / Institution should only contain letters and spaces.'
                : ''
            );
            return isValid;
        },
        validateDepartmentField(input) {
            const value = (input.value || '').trim();
            const isValid = /^[A-Za-z\s]+$/.test(value);
            input.setCustomValidity(value !== '' && !isValid
                ? 'Department / Stream should only contain letters and spaces.'
                : ''
            );
            return isValid;
        },
        validateCityField(input) {
            const value = (input.value || '').trim();
            const isValid = /^[A-Za-z\s]+$/.test(value);
            input.setCustomValidity(value !== '' && !isValid
                ? 'City / District should only contain letters and spaces.'
                : ''
            );
            return isValid;
        },
        
        submitForm(event) {
            const form = event.target;
            const mobileInput = form.querySelector('#mobile');
            const collegeInput = form.querySelector('#college_name');
            const departmentInput = form.querySelector('#department_name');

            if (mobileInput) {
                this.validateMobileField(mobileInput);
            }
            if (collegeInput) {
                this.validateCollegeField(collegeInput);
            }
            if (departmentInput) {
                this.validateDepartmentField(departmentInput);
            }
            this.validatePasswordConfirm(form);

            if (!form.checkValidity()) {
                const confirmInput = form.querySelector('#confirm_password');
                if (mobileInput && mobileInput.validity.customError) {
                    this.formMessage = mobileInput.validationMessage;
                } else if (collegeInput && collegeInput.validity.customError) {
                    this.formMessage = collegeInput.validationMessage;
                } else if (departmentInput && departmentInput.validity.customError) {
                    this.formMessage = departmentInput.validationMessage;
                } else if (confirmInput && confirmInput.validity.customError) {
                    this.formMessage = confirmInput.validationMessage;
                } else {
                    this.formMessage = 'Please correct the highlighted fields and try again.';
                }
                this.formMessageType = 'error';
                form.reportValidity();
                return;
            }

            this.loading = true;
            this.formMessage = '';
            
            const formData = new FormData(form);

            fetch('process/submit_student.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    this.generatedStudentNo = data.student_no;
                    this.generatedReferralCode = data.referral_code;
                    this.isModalOpen = true;
                    form.reset();
                } else {
                    this.formMessage = data.message;
                    this.formMessageType = 'error';
                }
            })
            .catch(() => { 
                this.formMessage = 'An unexpected error occurred.'; 
                this.formMessageType = 'error'; 
            })
            .finally(() => { 
                this.loading = false; 
            });
        }
    };
}
</script>

<?php require 'includes/footer.php'; ?>
