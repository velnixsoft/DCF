<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/india_locations.php';

$csrfToken = generateCsrfToken();

// Auto-fill logged-in member data if present
$loggedInMember = null;
if (!empty($_SESSION['member_logged_in']) && !empty($_SESSION['member_id'])) {
    $mStmt = $pdo->prepare("SELECT * FROM members WHERE id = ? LIMIT 1");
    $mStmt->execute([(int)$_SESSION['member_id']]);
    $loggedInMember = $mStmt->fetch(PDO::FETCH_ASSOC);
}

// Check for Renewal Request via Query Parameters
$renewCard = null;
$renewCardNo = cleanInput($_GET['renew_card'] ?? '');
$renewId = filter_input(INPUT_GET, 'renew_id', FILTER_VALIDATE_INT);

if ($renewCardNo || $renewId) {
    try {
        if ($renewId) {
            $rStmt = $pdo->prepare("SELECT * FROM health_cards WHERE id = ? LIMIT 1");
            $rStmt->execute([$renewId]);
        } else {
            $rStmt = $pdo->prepare("SELECT * FROM health_cards WHERE card_number = ? LIMIT 1");
            $rStmt->execute([$renewCardNo]);
        }
        $renewCard = $rStmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $renewCard = null;
    }
}

$indiaStatesJson = india_state_district_js();
$indiaStatesList = india_state_list();

require 'includes/header.php';
?>

<div class="bg-gradient-to-b from-[#F0FDFD] via-white to-[#F8FAFC] min-h-screen py-8 md:py-14"
     x-data="healthCardApplication(
         <?php echo htmlspecialchars(json_encode($loggedInMember, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>,
         <?php echo htmlspecialchars(json_encode($renewCard, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>
     )"
     x-cloak>

    <div class="container mx-auto px-4 max-w-6xl">
        
        <!-- Hero Header -->
        <div class="max-w-3xl mx-auto text-center mb-8 md:mb-12">
            <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white shadow-xs border border-teal-100 text-teal-700 text-xs sm:text-sm font-bold uppercase tracking-wider">
                <i class="fa-solid fa-id-card-clip text-teal-500"></i>
                <span x-show="!isRenewal">Swasthya Card • Healthcare Concessions & Support</span>
                <span x-show="isRenewal" class="text-amber-700">Health Card Renewal • Renew & Extend Validity</span>
            </span>
            <h1 class="mt-4 text-3xl sm:text-4xl md:text-5xl font-black text-gray-900 tracking-tight">
                <span x-show="!isRenewal">Apply for <span class="text-teal-600">Health Card</span></span>
                <span x-show="isRenewal">Renew Your <span class="text-teal-600">Health Card</span></span>
            </h1>
            <p class="mt-3 text-sm sm:text-base text-gray-600 max-w-xl mx-auto leading-relaxed">
                <span x-show="!isRenewal">Get subsidized medical treatments, free health checkups, surgery aids, and discounts on diagnostics & generic medicines at our empaneled healthcare partner network.</span>
                <span x-show="isRenewal">Extend your NGO health card validity to continue receiving seamless cashless discounts, OPD concessions, and hospital benefits across all empaneled facilities.</span>
            </p>

            <!-- Quick Card Lookup Tool for Renewal (if not already in renewal mode) -->
            <div x-show="!isRenewal" class="mt-6 inline-block bg-white p-2 rounded-2xl shadow-sm border border-teal-100 max-w-md w-full text-left">
                <div class="flex items-center gap-2">
                    <div class="relative flex-1">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 text-gray-400 text-xs"></i>
                        <input type="text" 
                               x-model="lookupQuery" 
                               @keydown.enter.prevent="lookupCard()"
                               placeholder="Enter Card No (e.g. HC-2026-0001) or Phone to Renew" 
                               class="w-full pl-8 pr-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-800 focus:outline-none focus:ring-2 focus:ring-teal-500">
                    </div>
                    <button type="button" 
                            @click="lookupCard()" 
                            :disabled="lookupLoading || !lookupQuery"
                            class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-bold transition-all disabled:opacity-50 flex items-center gap-1.5 flex-shrink-0 shadow-xs">
                        <i class="fa-solid fa-arrows-rotate" :class="{'fa-spin': lookupLoading}"></i>
                        <span x-text="lookupLoading ? 'Searching...' : 'Find & Auto-fill'"></span>
                    </button>
                </div>
                <p x-show="lookupError" x-text="lookupError" class="text-[11px] text-rose-600 font-medium mt-1.5 px-1"></p>
            </div>
        </div>

        <!-- Main Form & Digital Card Mockup Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <!-- Left Column: Application Form (7 cols) -->
            <div class="lg:col-span-7 bg-white rounded-3xl shadow-xl border border-teal-50 overflow-hidden">
                
                <!-- Renewal Notification Banner -->
                <div x-show="isRenewal" x-cloak class="p-4 md:p-5 bg-gradient-to-r from-amber-500/10 via-teal-50 to-emerald-50 border-b border-amber-200/60">
                    <div class="flex items-start gap-3">
                        <div class="w-9 h-9 rounded-xl bg-amber-500 text-white flex items-center justify-center text-base flex-shrink-0 shadow-sm shadow-amber-500/30">
                            <i class="fa-solid fa-arrows-rotate"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2 flex-wrap">
                                <h3 class="text-xs md:text-sm font-black text-amber-900 flex items-center gap-1.5">
                                    <span>Renewal Mode:</span>
                                    <span class="font-mono bg-white px-2 py-0.5 rounded-md border border-amber-300 text-teal-800" x-text="renewFromCardNumber"></span>
                                </h3>
                                <button type="button" @click="resetToNewApplication()" class="text-[11px] text-gray-500 hover:text-rose-600 font-semibold underline">
                                    Switch to New Application
                                </button>
                            </div>
                            <p class="text-xs text-amber-800/90 mt-1">
                                Your previous personal and contact particulars have been pre-filled below. Please review or update any information (such as phone, address, or photo) before submitting your renewal request.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="p-6 md:p-8 border-b border-gray-100 bg-gradient-to-r from-teal-50/50 to-white">
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <div>
                            <h2 class="text-xl md:text-2xl font-black text-gray-900 flex items-center gap-2.5">
                                <i class="fa-solid fa-address-card text-teal-600"></i>
                                <span x-text="isRenewal ? 'Health Card Renewal Form' : 'Health Card Registration'"></span>
                            </h2>
                            <p class="text-xs text-gray-500 mt-1">
                                <span x-show="!isRenewal">Please fill in accurate personal and contact details.</span>
                                <span x-show="isRenewal">Verify details below to renew your annual health membership.</span>
                            </p>
                        </div>
                        <?php if ($loggedInMember): ?>
                            <span class="px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-100 text-[11px] font-bold">
                                <i class="fa-solid fa-user-check mr-1"></i> Member Connected
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <form @submit.prevent="submitHealthCard($event)" enctype="multipart/form-data" class="p-6 md:p-8 space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="renew_from_card_number" :value="renewFromCardNumber">
                    <input type="hidden" name="renew_from_id" :value="renewFromId">
                    
                    <!-- Alert Message -->
                    <div x-show="errorMessage" x-cloak class="p-4 rounded-2xl bg-rose-50 border border-rose-100 text-rose-700 text-xs flex items-start gap-2.5" x-transition>
                        <i class="fa-solid fa-circle-exclamation mt-0.5 flex-shrink-0 text-sm"></i>
                        <span x-text="errorMessage"></span>
                    </div>

                    <!-- 1. Personal Information -->
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-teal-700 mb-3 flex items-center gap-1.5">
                            <i class="fa-solid fa-user"></i> 1. Personal Particulars
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Applicant Full Name <span class="text-rose-500">*</span></label>
                                <input type="text" name="applicant_name" x-model="form.applicant_name" required placeholder="Enter full name as per Aadhaar / ID" class="w-full px-3.5 py-2.5 bg-gray-50/70 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Date of Birth</label>
                                <input type="date" name="dob" x-model="form.dob" @change="calculateAge()" class="w-full px-3.5 py-2.5 bg-gray-50/70 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Gender <span class="text-rose-500">*</span></label>
                                <select name="gender" x-model="form.gender" required class="w-full px-3.5 py-2.5 bg-gray-50/70 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Blood Group</label>
                                <select name="blood_group" x-model="form.blood_group" class="w-full px-3.5 py-2.5 bg-gray-50/70 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                                    <option value="">Select Blood Group</option>
                                    <option value="A+">A+</option>
                                    <option value="A-">A-</option>
                                    <option value="B+">B+</option>
                                    <option value="B-">B-</option>
                                    <option value="O+">O+</option>
                                    <option value="O-">O-</option>
                                    <option value="AB+">AB+</option>
                                    <option value="AB-">AB-</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Aadhaar / ID Reference (Optional)</label>
                                <input type="text" name="aadhaar_no" x-model="form.aadhaar_no" placeholder="e.g. XXXX-XXXX-1234" class="w-full px-3.5 py-2.5 bg-gray-50/70 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                            </div>
                        </div>
                    </div>

                    <!-- 2. Contact & Emergency -->
                    <div class="pt-4 border-t border-gray-100">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-teal-700 mb-3 flex items-center gap-1.5">
                            <i class="fa-solid fa-phone"></i> 2. Contact & Emergency Details
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Primary Mobile Number <span class="text-rose-500">*</span></label>
                                <input type="tel" name="contact" x-model="form.contact" required placeholder="e.g. 9876543210" class="w-full px-3.5 py-2.5 bg-gray-50/70 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Emergency / Family Contact</label>
                                <input type="tel" name="emergency_contact" x-model="form.emergency_contact" placeholder="Alternative contact number" class="w-full px-3.5 py-2.5 bg-gray-50/70 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Email Address</label>
                                <input type="email" name="email" x-model="form.email" placeholder="applicant@example.com" class="w-full px-3.5 py-2.5 bg-gray-50/70 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                            </div>
                        </div>
                    </div>

                    <!-- 3. Address & Location -->
                    <div class="pt-4 border-t border-gray-100">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-teal-700 mb-3 flex items-center gap-1.5">
                            <i class="fa-solid fa-location-dot"></i> 3. Address & Location
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">State <span class="text-rose-500">*</span></label>
                                <select name="state" x-model="form.state" @change="onStateChange()" required class="w-full px-3.5 py-2.5 bg-gray-50/70 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                                    <option value="">Select State</option>
                                    <template x-for="st in Object.keys(indiaLocations)" :key="st">
                                        <option :value="st" x-text="st"></option>
                                    </template>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">District <span class="text-rose-500">*</span></label>
                                <select name="district" x-model="form.district" required class="w-full px-3.5 py-2.5 bg-gray-50/70 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                                    <option value="">Select District</option>
                                    <template x-for="dist in districtOptions" :key="dist">
                                        <option :value="dist" x-text="dist"></option>
                                    </template>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Block / Tehsil</label>
                                <input type="text" name="block" x-model="form.block" placeholder="e.g. Sadar" class="w-full px-3.5 py-2.5 bg-gray-50/70 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Residential Address <span class="text-rose-500">*</span></label>
                                <textarea name="address" x-model="form.address" required rows="2" placeholder="House/Flat No., Landmark, Village/Area" class="w-full px-3.5 py-2 bg-gray-50/70 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500"></textarea>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Pincode</label>
                                <input type="text" name="pincode" x-model="form.pincode" maxlength="6" placeholder="e.g. 110001" class="w-full px-3.5 py-2.5 bg-gray-50/70 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                            </div>
                        </div>
                    </div>

                    <!-- 4. Photo Upload -->
                    <div class="pt-4 border-t border-gray-100">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-teal-700 mb-3 flex items-center gap-1.5">
                            <i class="fa-solid fa-camera"></i> 4. Applicant Photo
                        </h3>
                        <div class="flex items-center gap-4">
                            <div class="w-20 h-24 rounded-2xl bg-gray-100 border-2 border-dashed border-gray-300 flex items-center justify-center overflow-hidden flex-shrink-0 relative">
                                <template x-if="photoPreview">
                                    <img :src="photoPreview" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!photoPreview">
                                    <i class="fa-solid fa-user text-gray-400 text-2xl"></i>
                                </template>
                            </div>
                            <div class="flex-1">
                                <label class="block text-xs font-semibold text-gray-700 mb-1">
                                    <span x-show="!isRenewal">Upload Passport Size Photo</span>
                                    <span x-show="isRenewal">Upload New Photo (Optional - Previous photo retained if left empty)</span>
                                </label>
                                <input type="file" name="photo" accept="image/*" @change="onPhotoSelected($event)" class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100">
                                <p class="text-[11px] text-gray-400 mt-1">Accepted formats: JPG, PNG, WEBP (Max 5MB).</p>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-4">
                        <button type="submit" 
                                :disabled="loading"
                                class="w-full py-4 px-6 rounded-2xl bg-gradient-to-r from-teal-600 via-teal-700 to-emerald-700 hover:from-teal-700 hover:to-emerald-800 text-white font-black text-sm uppercase tracking-wider shadow-lg shadow-teal-700/25 transition-all transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                            <template x-if="!loading">
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid" :class="isRenewal ? 'fa-arrows-rotate' : 'fa-check-circle'"></i>
                                    <span x-text="isRenewal ? 'Submit Health Card Renewal' : 'Submit Health Card Application'"></span>
                                </div>
                            </template>
                            <template x-if="loading">
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid fa-circle-notch fa-spin"></i>
                                    <span>Processing Application...</span>
                                </div>
                            </template>
                        </button>
                    </div>

                </form>
            </div>

            <!-- Right Column: Live Digital Health Card Preview (5 cols) -->
            <div class="lg:col-span-5 space-y-6 lg:sticky lg:top-24">
                
                <div class="text-center sm:text-left">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-gray-500 flex items-center gap-2 justify-center sm:justify-start">
                        <i class="fa-solid fa-eye text-teal-600"></i>
                        <span>Live PVC Card Preview</span>
                    </h3>
                </div>

                <!-- Digital PVC Health Card Mockup -->
                <div class="w-full max-w-[420px] mx-auto bg-gradient-to-br from-[#0F8B8D] via-[#0C6E70] to-[#0A5354] rounded-3xl p-5 text-white shadow-2xl shadow-teal-900/30 border border-teal-400/30 relative overflow-hidden aspect-[1.586/1]">
                    
                    <!-- Background Decorative Circles -->
                    <div class="absolute -right-12 -bottom-12 w-48 h-48 bg-white/10 rounded-full blur-xl pointer-events-none"></div>
                    <div class="absolute -left-12 -top-12 w-48 h-48 bg-emerald-400/10 rounded-full blur-xl pointer-events-none"></div>

                    <!-- Card Header -->
                    <div class="flex items-start justify-between relative z-10 border-b border-white/20 pb-3">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center border border-white/30 text-emerald-300 font-black text-sm shadow-inner">
                                <i class="fa-solid fa-hand-holding-medical"></i>
                            </div>
                            <div>
                                <h4 class="text-xs font-black tracking-wider uppercase text-white leading-none">
                                    <?php echo htmlspecialchars($settings['ngo_name'] ?? 'NGO HEALTH FOUNDATION'); ?>
                                </h4>
                                <p class="text-[9px] font-bold text-teal-200 tracking-widest uppercase mt-0.5">SWASTHYA SEVA CARD</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="px-2 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider"
                                  :class="isRenewal ? 'bg-amber-400 text-amber-950' : 'bg-emerald-400/30 text-emerald-100 border border-emerald-300/40'"
                                  x-text="isRenewal ? 'RENEWAL PREVIEW' : 'ACTIVE'">
                            </span>
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="mt-4 flex gap-3.5 relative z-10">
                        <!-- Photo Box -->
                        <div class="w-18 h-22 rounded-xl bg-white/20 border border-white/40 overflow-hidden flex items-center justify-center flex-shrink-0 shadow-md">
                            <template x-if="photoPreview">
                                <img :src="photoPreview" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!photoPreview">
                                <div class="text-center text-white/60">
                                    <i class="fa-solid fa-user text-xl"></i>
                                    <p class="text-[7px] mt-1 uppercase font-bold">Photo</p>
                                </div>
                            </template>
                        </div>

                        <!-- Cardholder Details -->
                        <div class="flex-1 min-w-0 space-y-1">
                            <div>
                                <p class="text-[8px] font-semibold text-teal-200 uppercase tracking-wider leading-none">Beneficiary Name</p>
                                <p class="text-xs font-black text-white truncate leading-tight mt-0.5" x-text="form.applicant_name || 'Applicant Full Name'"></p>
                            </div>

                            <div class="grid grid-cols-2 gap-2 pt-0.5">
                                <div>
                                    <p class="text-[7px] font-medium text-teal-200 uppercase leading-none">Card No.</p>
                                    <p class="text-[9px] font-mono font-bold text-amber-300 leading-tight" x-text="isRenewal ? renewFromCardNumber : 'HC-<?php echo date('Y'); ?>-XXXX'"></p>
                                </div>
                                <div>
                                    <p class="text-[7px] font-medium text-teal-200 uppercase leading-none">Blood Group</p>
                                    <p class="text-[9px] font-bold text-rose-300 leading-tight" x-text="form.blood_group || 'N/A'"></p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-2 pt-0.5">
                                <div>
                                    <p class="text-[7px] font-medium text-teal-200 uppercase leading-none">Contact</p>
                                    <p class="text-[9px] font-mono text-white leading-tight truncate" x-text="form.contact || '98XXXXXXXX'"></p>
                                </div>
                                <div>
                                    <p class="text-[7px] font-medium text-teal-200 uppercase leading-none">District / State</p>
                                    <p class="text-[9px] text-white leading-tight truncate" x-text="(form.district ? form.district + ', ' : '') + (form.state || 'India')"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Mock QR Code -->
                        <div class="w-16 h-16 bg-white p-1 rounded-xl shadow-md flex items-center justify-center flex-shrink-0 border border-teal-200 self-center">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=https%3A%2F%2Fexample.org" alt="QR Preview" class="w-full h-full">
                        </div>
                    </div>

                    <!-- Card Footer Ribbon -->
                    <div class="absolute bottom-2.5 left-5 right-5 flex items-center justify-between border-t border-white/15 pt-2 text-[8px] text-teal-100/90 font-medium">
                        <span>Helpline: <?php echo htmlspecialchars($settings['ngo_phone'] ?? '+91 98765 43210'); ?></span>
                        <span>Emergency Concessions Enabled</span>
                    </div>
                </div>

                <!-- Benefits Checklist -->
                <div class="bg-white rounded-3xl p-6 border border-teal-50 shadow-sm space-y-4">
                    <h4 class="text-xs font-black text-gray-900 uppercase tracking-wider flex items-center gap-2">
                        <i class="fa-solid fa-shield-heart text-teal-600"></i>
                        <span>Cardholder Entitlements</span>
                    </h4>
                    <ul class="text-xs text-gray-600 space-y-2.5">
                        <li class="flex items-start gap-2.5">
                            <i class="fa-solid fa-circle-check text-emerald-500 mt-0.5 flex-shrink-0"></i>
                            <span><strong>100% Free Eye & Dental Checkups</strong> across all partner specialty clinics.</span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <i class="fa-solid fa-circle-check text-emerald-500 mt-0.5 flex-shrink-0"></i>
                            <span><strong>Subsidized Surgeries & OPD</strong> at empaneled tertiary hospitals.</span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <i class="fa-solid fa-circle-check text-emerald-500 mt-0.5 flex-shrink-0"></i>
                            <span><strong>Up to 30% Off on Diagnostics & Labs</strong> (Blood tests, X-Ray, Ultrasound).</span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <i class="fa-solid fa-circle-check text-emerald-500 mt-0.5 flex-shrink-0"></i>
                            <span><strong>Instant QR Verification</strong> at hospital reception desk.</span>
                        </li>
                    </ul>
                </div>

            </div>

        </div>

    </div>

    <!-- Success Modal -->
    <div x-show="showSuccessModal" 
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/70 backdrop-blur-xs"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div class="bg-white rounded-3xl max-w-md w-full p-6 md:p-8 text-center shadow-2xl relative"
             @click.away="showSuccessModal = false">
            
            <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-3xl mx-auto mb-4 shadow-inner">
                <i class="fa-solid fa-circle-check"></i>
            </div>

            <h3 class="text-xl md:text-2xl font-black text-gray-900" x-text="issuedCard.is_renewal ? 'Renewal Submitted!' : 'Application Submitted!'"></h3>
            
            <p class="text-xs text-gray-600 mt-2 leading-relaxed" x-text="issuedCard.message"></p>

            <div class="my-5 p-4 rounded-2xl bg-teal-50 border border-teal-200">
                <p class="text-[10px] font-bold uppercase tracking-wider text-teal-600">Application Reference</p>
                <p class="text-2xl font-black text-teal-900 font-mono tracking-wider mt-0.5" x-text="issuedCard.card_number"></p>
                <div class="flex items-center justify-center gap-3 mt-2 text-xs text-gray-600 font-medium">
                    <span>Status: <strong class="text-amber-700 uppercase" x-text="issuedCard.status || 'Under Review'"></strong></span>
                    <span>•</span>
                    <span>Valid For: <strong>1 Year</strong></span>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                <a href="<?php echo cleanUrl('member-dashboard.php'); ?>" class="w-full sm:w-auto px-5 py-2.5 bg-gray-900 hover:bg-black text-white text-xs font-bold rounded-xl shadow transition-colors flex items-center justify-center gap-1.5">
                    <i class="fa-solid fa-gauge-high"></i>
                    <span>Go to Dashboard</span>
                </a>

                <a href="<?php echo cleanUrl('healthcare-directory.php'); ?>" class="w-full sm:w-auto px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold rounded-xl shadow transition-colors flex items-center justify-center gap-1.5">
                    <i class="fa-solid fa-hospital-user"></i>
                    <span>Browse Hospital Panel</span>
                </a>

                <button @click="showSuccessModal = false" class="w-full sm:w-auto px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-xl transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>

</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('healthCardApplication', (memberData, initialRenewCard) => ({
        indiaLocations: <?php echo $indiaStatesJson; ?>,
        loading: false,
        errorMessage: '',
        showSuccessModal: false,
        photoPreview: initialRenewCard && initialRenewCard.photo ? initialRenewCard.photo : '',
        calculatedAge: '',
        issuedCard: {},
        
        // Renewal state
        isRenewal: !!(initialRenewCard && (initialRenewCard.card_number || initialRenewCard.id)),
        renewFromCardNumber: initialRenewCard ? (initialRenewCard.card_number || '') : '',
        renewFromId: initialRenewCard ? (initialRenewCard.id || '') : '',

        // Lookup tool
        lookupQuery: '',
        lookupLoading: false,
        lookupError: '',

        form: {
            applicant_name: initialRenewCard ? (initialRenewCard.applicant_name || '') : (memberData ? (memberData.full_name || '') : ''),
            contact: initialRenewCard ? (initialRenewCard.contact || '') : (memberData ? (memberData.phone || '') : ''),
            email: initialRenewCard ? (initialRenewCard.email || '') : (memberData ? (memberData.email || '') : ''),
            dob: initialRenewCard ? (initialRenewCard.dob || '') : (memberData ? (memberData.dob || '') : ''),
            gender: initialRenewCard ? (initialRenewCard.gender || 'Male') : (memberData ? (memberData.gender || 'Male') : 'Male'),
            blood_group: initialRenewCard ? (initialRenewCard.blood_group || '') : (memberData ? (memberData.blood_group || '') : ''),
            aadhaar_no: initialRenewCard ? (initialRenewCard.aadhaar_no || '') : '',
            emergency_contact: initialRenewCard ? (initialRenewCard.emergency_contact || '') : '',
            state: initialRenewCard ? (initialRenewCard.state || '') : (memberData ? (memberData.state || '') : ''),
            district: initialRenewCard ? (initialRenewCard.district || '') : (memberData ? (memberData.district || '') : ''),
            block: initialRenewCard ? (initialRenewCard.block || '') : '',
            pincode: initialRenewCard ? (initialRenewCard.pincode || '') : '',
            address: initialRenewCard ? (initialRenewCard.address || '') : (memberData ? (memberData.address || '') : ''),
            remarks: ''
        },

        init() {
            if (this.form.dob) {
                this.calculateAge();
            }
        },

        get districtOptions() {
            if (!this.form.state || !this.indiaLocations[this.form.state]) return [];
            return this.indiaLocations[this.form.state];
        },

        onStateChange() {
            if (!this.districtOptions.includes(this.form.district)) {
                this.form.district = '';
            }
        },

        calculateAge() {
            if (!this.form.dob) {
                this.calculatedAge = '';
                return;
            }
            const birthDate = new Date(this.form.dob);
            const today = new Date();
            let age = today.getFullYear() - birthDate.getFullYear();
            const m = today.getMonth() - birthDate.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            this.calculatedAge = age > 0 ? age : '';
        },

        onPhotoSelected(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.photoPreview = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        },

        async lookupCard() {
            if (!this.lookupQuery.trim()) return;
            this.lookupLoading = true;
            this.lookupError = '';

            try {
                const response = await fetch('process/fetch_health_card_lookup.php?query=' + encodeURIComponent(this.lookupQuery.trim()));
                const data = await response.json();

                if (data.success && data.card) {
                    const c = data.card;
                    this.isRenewal = true;
                    this.renewFromCardNumber = c.card_number;
                    this.renewFromId = c.id;

                    this.form.applicant_name = c.applicant_name || '';
                    this.form.contact = c.contact || '';
                    this.form.email = c.email || '';
                    this.form.dob = c.dob || '';
                    this.form.gender = c.gender || 'Male';
                    this.form.blood_group = c.blood_group || '';
                    this.form.aadhaar_no = c.aadhaar_no || '';
                    this.form.emergency_contact = c.emergency_contact || '';
                    this.form.state = c.state || '';
                    this.form.district = c.district || '';
                    this.form.block = c.block || '';
                    this.form.pincode = c.pincode || '';
                    this.form.address = c.address || '';

                    if (c.photo) {
                        this.photoPreview = c.photo;
                    }

                    if (this.form.dob) {
                        this.calculateAge();
                    }
                    this.lookupQuery = '';
                } else {
                    this.lookupError = data.message || 'No matching Health Card record found.';
                }
            } catch (err) {
                console.error(err);
                this.lookupError = 'Network error during lookup. Please try again.';
            } finally {
                this.lookupLoading = false;
            }
        },

        resetToNewApplication() {
            this.isRenewal = false;
            this.renewFromCardNumber = '';
            this.renewFromId = '';
        },

        async submitHealthCard(event) {
            const formElement = event.target;
            this.loading = true;
            this.errorMessage = '';

            const formData = new FormData(formElement);
            if (this.calculatedAge) {
                formData.set('age', this.calculatedAge);
            }

            try {
                const response = await fetch('process/submit_health_card.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    this.issuedCard = data;
                    this.showSuccessModal = true;
                } else {
                    this.errorMessage = data.message || 'Failed to process application. Please check input fields.';
                }
            } catch (err) {
                console.error(err);
                this.errorMessage = 'Network or server error occurred. Please try again.';
            } finally {
                this.loading = false;
            }
        }
    }));
});
</script>

<?php require 'includes/footer.php'; ?>
