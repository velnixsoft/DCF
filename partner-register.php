<?php
require_once __DIR__ . '/includes/header.php';
$partnerCategories = json_decode($settings['partner_categories_json'] ?? '[]', true);
$partnerRegisterCsrf = generateCsrfToken();
?>

<div class="bg-gradient-to-br from-slate-900 via-blue-950 to-slate-900 min-h-screen py-12 px-4"
     x-data="partnerRegister()">
    <div class="container mx-auto max-w-4xl">
        <div class="bg-white/95 backdrop-blur rounded-3xl shadow-2xl border border-white/20 overflow-hidden">
            <div class="h-2 bg-gradient-to-r from-blue-600 via-indigo-600 to-violet-600"></div>
            <div class="p-8 md:p-12">
                <div class="text-center mb-10">
                    <div class="w-20 h-20 bg-blue-50 text-blue-700 rounded-2xl flex items-center justify-center mx-auto mb-5 shadow-lg">
                        <i class="fas fa-handshake text-4xl"></i>
                    </div>
                    <h1 class="text-4xl font-extrabold text-gray-900 tracking-tight">Partner Portal Registration</h1>
                    <p class="text-gray-600 mt-3 max-w-2xl mx-auto">Register your business to connect with our student ambassador network. Retail and physical activity centers welcome.</p>
                </div>

                <form @submit.prevent="submitForm($event)" class="space-y-8">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($partnerRegisterCsrf); ?>">

                    <section class="rounded-2xl border border-gray-100 bg-gray-50/60 p-6 space-y-4">
                        <h2 class="font-bold text-gray-900 flex items-center gap-2 text-lg">
                            <span class="w-7 h-7 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-sm font-bold">1</span>
                            Owner & Business Details
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold mb-1.5 text-gray-700">Owner Name *</label>
                                <input type="text" name="owner_name" required x-model="ownerName" @input="ownerName = ownerName.replace(/[^a-zA-Z\s]/g, '').slice(0, 100)" class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Full name of owner">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold mb-1.5 text-gray-700">Business Name *</label>
                                <input type="text" name="business_name" required x-model="businessName" @input="businessName = businessName.slice(0, 150)" class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Registered business name">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold mb-1.5 text-gray-700">Owner Mobile *</label>
                                <input type="tel" name="owner_mobile" required x-model="ownerMobile" @input="ownerMobile = ownerMobile.replace(/[^0-9]/g, '').slice(0, 15)" class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="10-digit mobile">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold mb-1.5 text-gray-700">Business Mobile</label>
                                <input type="tel" name="business_mobile" x-model="businessMobile" @input="businessMobile = businessMobile.replace(/[^0-9]/g, '').slice(0, 15)" class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Business contact number">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-semibold mb-1.5 text-gray-700">Owner Email Address *</label>
                                <input type="email" name="owner_email" required class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="owner@business.com">
                            </div>
                        </div>
                    </section>
 
                    <section class="rounded-2xl border border-gray-100 bg-gray-50/60 p-6 space-y-4">
                        <h2 class="font-bold text-gray-900 flex items-center gap-2 text-lg">
                            <span class="w-7 h-7 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-sm font-bold">2</span>
                            Account Security
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold mb-1.5 text-gray-700">Create Password *</label>
                                <input type="password" name="password" required x-model="password" class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Minimum 8 characters">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold mb-1.5 text-gray-700">Confirm Password *</label>
                                <input type="password" name="confirm_password" required x-model="confirmPassword" class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Re-enter password">
                            </div>
                            
                            <!-- Real-time strength and checklist UI -->
                            <div class="md:col-span-2 space-y-3">
                                <div class="space-y-1">
                                    <div class="flex justify-between text-xs font-semibold text-gray-500">
                                        <span>Password Strength:</span>
                                        <span :class="strengthClass" x-text="strengthText"></span>
                                    </div>
                                    <div class="h-2 w-full bg-gray-200 rounded-full overflow-hidden">
                                        <div class="h-full transition-all duration-300" :class="strengthBarClass" :style="'width: ' + strengthPercentage + '%'"></div>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs font-medium text-gray-600 bg-gray-50 p-4 rounded-xl border border-gray-150">
                                    <div class="flex items-center gap-2">
                                        <i class="fa-solid" :class="password.length >= 8 ? 'fa-circle-check text-emerald-600' : 'fa-circle-xmark text-red-400'"></i>
                                        <span>At least 8 characters</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <i class="fa-solid" :class="/[A-Z]/.test(password) ? 'fa-circle-check text-emerald-600' : 'fa-circle-xmark text-red-400'"></i>
                                        <span>One uppercase letter (A-Z)</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <i class="fa-solid" :class="/[a-z]/.test(password) ? 'fa-circle-check text-emerald-600' : 'fa-circle-xmark text-red-400'"></i>
                                        <span>One lowercase letter (a-z)</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <i class="fa-solid" :class="/[0-9]/.test(password) ? 'fa-circle-check text-emerald-600' : 'fa-circle-xmark text-red-400'"></i>
                                        <span>One number (0-9)</span>
                                    </div>
                                    <div class="flex items-center gap-2 sm:col-span-2">
                                        <i class="fa-solid" :class="/[@$!%*?&#]/.test(password) ? 'fa-circle-check text-emerald-600' : 'fa-circle-xmark text-red-400'"></i>
                                        <span>One special character (@$!%*?&#)</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="md:col-span-2" x-show="confirmPassword.length > 0">
                                <div class="flex items-center gap-2 text-xs font-semibold" :class="password === confirmPassword ? 'text-emerald-600' : 'text-red-500'">
                                    <i class="fa-solid" :class="password === confirmPassword ? 'fa-circle-check' : 'fa-triangle-exclamation'"></i>
                                    <span x-text="password === confirmPassword ? 'Passwords match' : 'Passwords do not match'"></span>
                                </div>
                            </div>
                        </div>
                    </section>
 
                    <section class="rounded-2xl border border-gray-100 bg-gray-50/60 p-6 space-y-4">
                        <h2 class="font-bold text-gray-900 flex items-center gap-2 text-lg">
                            <span class="w-7 h-7 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-sm font-bold">3</span>
                            Activity & Location
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold mb-1.5 text-gray-700">Activity Type *</label>
                                <select name="activity_type" required class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                                    <?php foreach ($partnerCategories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat['id']); ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold mb-1.5 text-gray-700">Latitude *</label>
                                    <input type="text" name="latitude" x-model="latitude" required class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="e.g. 28.6139">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold mb-1.5 text-gray-700">Longitude *</label>
                                    <input type="text" name="longitude" x-model="longitude" required class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="e.g. 77.2090">
                                </div>
                            </div>
                            <div class="md:col-span-2">
                                <button type="button" @click="detectLocation()" class="inline-flex items-center gap-2 rounded-xl bg-slate-800 px-4 py-2.5 text-sm font-bold text-white hover:bg-slate-900">
                                    <i class="fa-solid fa-location-crosshairs"></i> Use My Current Location
                                </button>
                                <p class="text-xs text-gray-500 mt-2">Lat/Long with business address helps ambassadors and interns find your center on maps.</p>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-semibold mb-1.5 text-gray-700">Business Address *</label>
                                <textarea name="business_address" required rows="3" class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Full address with landmark, city, pin code"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold mb-1.5 text-gray-700">State *</label>
                                <select name="state_name" x-model="stateName" @change="updateDistricts()" required class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none">
                                    <option value="">Select State</option>
                                    <template x-for="state in states" :key="state">
                                        <option :value="state" x-text="state"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold mb-1.5 text-gray-700">City / District *</label>
                                <select name="city_name" x-model="cityName" required class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none">
                                    <option value="">Select City / District</option>
                                    <template x-for="dist in districts" :key="dist">
                                        <option :value="dist" x-text="dist"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                    </section>
 
                    <p x-show="message" x-text="message" class="text-sm font-medium rounded-xl px-4 py-3" :class="messageType === 'success' ? 'bg-emerald-50 text-emerald-800' : 'bg-red-50 text-red-700'"></p>
 
                    <div class="flex flex-col sm:flex-row gap-3 justify-between items-center">
                        <a href="partner-login.php" class="text-sm font-semibold text-blue-700 hover:underline">Already registered? Partner Login</a>
                        <button type="submit" :disabled="loading" class="w-full sm:w-auto bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white px-8 py-3.5 rounded-xl font-bold shadow-lg disabled:opacity-60">
                            <span x-show="!loading">Submit Registration</span>
                            <span x-show="loading">Submitting...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
 
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('partnerRegister', () => ({
        loading: false,
        message: '',
        messageType: 'error',
        latitude: '',
        longitude: '',
        ownerName: '',
        businessName: '',
        ownerMobile: '',
        cityName: '',
        stateName: '',
        districts: [],
        stateDistrictData: <?php require_once __DIR__ . '/includes/india_locations.php'; echo india_state_district_js(); ?>,
        get states() { return Object.keys(this.stateDistrictData); },
        updateDistricts() {
            this.districts = this.stateDistrictData[this.stateName] || [];
            this.cityName = '';
        },
        password: '',
        confirmPassword: '',
        get strengthText() {
            if (!this.password) return 'Too Short';
            let score = 0;
            if (this.password.length >= 8) score++;
            if (/[A-Z]/.test(this.password)) score++;
            if (/[a-z]/.test(this.password)) score++;
            if (/[0-9]/.test(this.password)) score++;
            if (/[@$!%*?&#]/.test(this.password)) score++;
            
            if (score <= 2) return 'Weak';
            if (score <= 4) return 'Medium';
            return 'Strong';
        },
        get strengthClass() {
            const text = this.strengthText;
            if (text === 'Weak') return 'text-red-500';
            if (text === 'Medium') return 'text-amber-500';
            if (text === 'Strong') return 'text-emerald-600';
            return 'text-gray-400';
        },
        get strengthPercentage() {
            if (!this.password) return 0;
            let score = 0;
            if (this.password.length >= 8) score++;
            if (/[A-Z]/.test(this.password)) score++;
            if (/[a-z]/.test(this.password)) score++;
            if (/[0-9]/.test(this.password)) score++;
            if (/[@$!%*?&#]/.test(this.password)) score++;
            return (score / 5) * 100;
        },
        get strengthBarClass() {
            const text = this.strengthText;
            if (text === 'Weak') return 'bg-red-500';
            if (text === 'Medium') return 'bg-amber-500';
            if (text === 'Strong') return 'bg-emerald-500';
            return 'bg-gray-200';
        },
        detectLocation() {
            if (!navigator.geolocation) {
                this.message = 'Geolocation is not supported by your browser.';
                this.messageType = 'error';
                return;
            }
            navigator.geolocation.getCurrentPosition((pos) => {
                this.latitude = pos.coords.latitude.toFixed(7);
                this.longitude = pos.coords.longitude.toFixed(7);
                this.message = 'Location captured successfully.';
                this.messageType = 'success';
            }, () => {
                this.message = 'Unable to detect location. Enter coordinates manually.';
                this.messageType = 'error';
            });
        },
        submitForm(event) {
            // Client-side validations
            if (!/^[a-zA-Z\s]{2,100}$/.test(this.ownerName.trim())) {
                this.message = 'Owner Name must be between 2 and 100 characters and contain only letters and spaces.';
                this.messageType = 'error';
                return;
            }
            if (this.businessName.trim().length < 2 || this.businessName.trim().length > 150) {
                this.message = 'Business Name must be between 2 and 150 characters.';
                this.messageType = 'error';
                return;
            }
            if (!/^[0-9]{10,15}$/.test(this.ownerMobile)) {
                this.message = 'Owner Mobile must be between 10 and 15 digits.';
                this.messageType = 'error';
                return;
            }
            if (this.businessMobile !== '' && !/^[0-9]{10,15}$/.test(this.businessMobile)) {
                this.message = 'Business Mobile must be between 10 and 15 digits.';
                this.messageType = 'error';
                return;
            }
            // Password strength check
            const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]{8,}$/;
            if (!passwordRegex.test(this.password)) {
                this.message = 'Password must be at least 8 characters and contain at least one uppercase letter, one lowercase letter, one number, and one special character.';
                this.messageType = 'error';
                return;
            }
            if (this.password !== this.confirmPassword) {
                this.message = 'Passwords do not match.';
                this.messageType = 'error';
                return;
            }
            
            const latVal = parseFloat(this.latitude);
            const lngVal = parseFloat(this.longitude);
            if (isNaN(latVal) || latVal < -90 || latVal > 90) {
                this.message = 'Latitude must be a valid number between -90 and 90.';
                this.messageType = 'error';
                return;
            }
            if (isNaN(lngVal) || lngVal < -180 || lngVal > 180) {
                this.message = 'Longitude must be a valid number between -180 and 180.';
                this.messageType = 'error';
                return;
            }
            
            if (!this.stateName) {
                this.message = 'Please select a State.';
                this.messageType = 'error';
                return;
            }
            
            if (!this.cityName) {
                this.message = 'Please select a City / District.';
                this.messageType = 'error';
                return;
            }

            this.loading = true;
            this.message = '';
            const formData = new FormData(event.target);
            fetch('process/submit_partner.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        this.message = data.message + ' Partner Code: ' + data.partner_code;
                        this.messageType = 'success';
                        event.target.reset();
                        this.ownerName = '';
                        this.businessName = '';
                        this.ownerMobile = '';
                        this.businessMobile = '';
                        this.cityName = '';
                        this.stateName = '';
                        this.password = '';
                        this.confirmPassword = '';
                        this.latitude = '';
                        this.longitude = '';
                    } else {
                        this.message = data.message;
                        this.messageType = 'error';
                    }
                })
                .catch(() => {
                    this.message = 'An unexpected error occurred.';
                    this.messageType = 'error';
                })
                .finally(() => { this.loading = false; });
        }
    }));
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
