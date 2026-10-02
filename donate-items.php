<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

// Fetch active categories
$categoriesStmt = $pdo->query("
    SELECT id, category_name, category_slug, category_icon, description, unit_suggestions 
    FROM item_donation_categories 
    WHERE is_active = 1 
    ORDER BY display_order ASC, id ASC
");
$categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch active projects
$projectsStmt = $pdo->query("SELECT id, title FROM projects WHERE status = 'Active' ORDER BY title ASC");
$projects = $projectsStmt->fetchAll(PDO::FETCH_ASSOC);

$csrfToken = generateCsrfToken();
$memberReferralCode = trim((string)($_GET['mref'] ?? $_GET['ref'] ?? ''));

require 'includes/header.php';
?>

<div class="bg-gradient-to-b from-[#FFF8F1] via-white to-[#F0FDFD] min-h-screen py-10 md:py-16" 
     x-data="itemDonationPage(<?php echo htmlspecialchars(json_encode($categories, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>)" 
     x-cloak>
    
    <div class="container mx-auto px-4 max-w-6xl">
        
        <!-- Hero Header -->
        <div class="max-w-4xl mx-auto text-center mb-10 md:mb-14">
            <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white shadow-sm border border-[#CCFBF1] text-[#0F8B8D] text-xs sm:text-sm font-bold uppercase tracking-wider">
                <i class="fa-solid fa-box-heart text-[#F4A640]"></i>
                In-Kind Giving • Share Blessings & Utility Goods
            </span>
            <h1 class="mt-4 text-3xl sm:text-4xl md:text-5xl font-black text-[#0F8B8D] tracking-tight">
                Donate Items & Essentials
            </h1>
            <p class="mt-3 text-base sm:text-lg text-[#4B5563] max-w-2xl mx-auto">
                Donate clothes, dry rations, books, stationery, medicines, blankets, wheelchairs, or food. We collect and distribute them directly to those in need.
            </p>
        </div>

        <div class="grid lg:grid-cols-[1.2fr_0.8fr] gap-8 items-start">
            
            <!-- Left Column: Item Donation Form -->
            <div class="bg-white rounded-[22px] shadow-2xl border border-gray-100 overflow-hidden">
                <div class="p-6 md:p-8 border-b border-gray-100 bg-gradient-to-r from-[#FFF8F1] to-white">
                    <h2 class="text-2xl font-black text-[#1F2937] flex items-center gap-3">
                        <i class="fa-solid fa-hand-holding-box text-[#0F8B8D]"></i>
                        Item Donation Details
                    </h2>
                    <p class="text-sm text-[#4B5563] mt-1">Select the item category, describe what you are giving, and provide pickup information.</p>
                </div>

                <form action="process/submit_item_donation.php" method="POST" enctype="multipart/form-data" 
                      class="p-6 md:p-8 space-y-6"
                      @submit="handleSubmit($event)">

                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="category_id" :value="selectedCategory">
                    <input type="hidden" name="condition_type" :value="conditionType">
                    <input type="hidden" name="unit" :value="selectedUnit">
                    <input type="hidden" name="referral_code" value="<?php echo htmlspecialchars($memberReferralCode, ENT_QUOTES, 'UTF-8'); ?>">

                    <!-- 1. Category Selection Grid -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#0F8B8D]">
                                1. Select Category <span class="text-red-500">*</span>
                            </label>
                            <span class="text-xs text-gray-400">Choose item type</span>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                            <template x-for="cat in categories" :key="cat.id">
                                <button type="button" 
                                        @click="selectCategory(cat)"
                                        class="p-3.5 rounded-2xl border text-left transition-all duration-200 flex flex-col justify-between gap-2"
                                        :class="selectedCategory == cat.id 
                                            ? 'border-[#0F8B8D] bg-[#F0FDFD] shadow-md shadow-[#0F8B8D]/10 ring-2 ring-[#0F8B8D]/20' 
                                            : 'border-gray-200 hover:border-[#0F8B8D]/40 bg-white hover:bg-gray-50/50'">
                                    <div class="w-8 h-8 rounded-xl flex items-center justify-center text-sm"
                                         :class="selectedCategory == cat.id ? 'bg-[#0F8B8D] text-white' : 'bg-gray-100 text-gray-600'">
                                        <i class="fa-solid" :class="cat.category_icon || 'fa-box'"></i>
                                    </div>
                                    <div>
                                        <p class="font-bold text-xs sm:text-sm text-[#1F2937]" x-text="cat.category_name"></p>
                                        <p class="text-[10px] text-gray-400 mt-0.5 line-clamp-1" x-text="cat.unit_suggestions"></p>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- 2. Item Specifics -->
                    <div class="bg-[#FFF8F1] border border-[#FEECDC] rounded-[18px] p-4 md:p-5 space-y-4">
                        <h3 class="font-bold text-sm text-[#1F2937] uppercase tracking-wider text-[#0F8B8D]">
                            2. Item Details & Quantity
                        </h3>

                        <!-- Item Description -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">
                                Item Description / Contents <span class="text-red-500">*</span>
                            </label>
                            <textarea name="item_description" x-model="itemDescription" required rows="3"
                                      class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-[#0F8B8D] focus:border-[#0F8B8D] outline-none"
                                      placeholder="e.g. 15 pairs of school uniforms (Age 8-12), 4 winter jackets, all cleaned and ready to wear"></textarea>
                        </div>

                        <!-- Quantity & Units -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">
                                    Quantity <span class="text-red-500">*</span>
                                </label>
                                <input type="number" name="quantity" x-model="quantity" required min="1" step="1"
                                       class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-[#0F8B8D] focus:border-[#0F8B8D] outline-none"
                                       placeholder="e.g. 10">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">
                                    Unit <span class="text-red-500">*</span>
                                </label>
                                <div class="flex gap-1.5 flex-wrap">
                                    <template x-for="u in currentUnitOptions" :key="u">
                                        <button type="button" @click="selectedUnit = u"
                                                class="px-3 py-2 rounded-xl text-xs font-bold transition border"
                                                :class="selectedUnit === u ? 'bg-[#0F8B8D] text-white border-[#0F8B8D]' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'"
                                                x-text="u">
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Condition Type Tabs -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">
                                Item Condition <span class="text-red-500">*</span>
                            </label>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                <template x-for="cond in ['New', 'Gently Used', 'Refurbished', 'Usable']" :key="cond">
                                    <button type="button" @click="conditionType = cond"
                                            class="py-2.5 px-2 rounded-xl text-xs font-bold text-center border transition"
                                            :class="conditionType === cond ? 'bg-[#F4A640] text-white border-[#F4A640] shadow-sm' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'"
                                            x-text="cond">
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- Estimated Value & Photo Upload -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">
                                    Estimated Value (₹) <span class="text-gray-400 font-normal">(Optional)</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3.5 top-3 text-gray-400 font-bold text-xs">₹</span>
                                    <input type="number" name="estimated_value" x-model="estimatedValue" min="0" step="10"
                                           class="w-full pl-8 pr-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-[#0F8B8D] outline-none"
                                           placeholder="e.g. 2500">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">
                                    Item Photo <span class="text-gray-400 font-normal">(Optional, Max 5MB)</span>
                                </label>
                                <input type="file" name="item_photo" accept="image/*" @change="previewImage($event)"
                                       class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-3.5 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#0F8B8D] file:text-white hover:file:bg-[#0c7274] file:cursor-pointer border border-gray-300 rounded-xl p-1 bg-white">
                                
                                <div x-show="photoPreview" class="mt-2 relative inline-block">
                                    <img :src="photoPreview" class="w-16 h-16 object-cover rounded-xl border border-gray-200 shadow-sm">
                                    <button type="button" @click="clearPhoto()" class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-rose-600 text-white rounded-full text-[10px] flex items-center justify-center shadow">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Donor Contact Information -->
                    <div class="space-y-4">
                        <h3 class="font-bold text-xs uppercase tracking-wider text-[#0F8B8D]">
                            3. Donor Information
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                                <input type="text" name="name" x-model="donorName" required
                                       class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-[#0F8B8D] outline-none"
                                       placeholder="Your full name">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Email Address <span class="text-red-500">*</span></label>
                                <input type="email" name="email" x-model="donorEmail" required
                                       class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-[#0F8B8D] outline-none"
                                       placeholder="you@example.com">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Mobile Number <span class="text-red-500">*</span></label>
                                <input type="tel" name="mobile" x-model="donorMobile" required pattern="[0-9]{10}" maxlength="10"
                                       class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-[#0F8B8D] outline-none"
                                       placeholder="9876543210">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">PAN Number <span class="text-gray-400 font-normal">(Optional)</span></label>
                                <input type="text" name="pan" x-model="donorPan" maxlength="10"
                                       class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm uppercase focus:ring-2 focus:ring-[#0F8B8D] outline-none"
                                       placeholder="ABCDE1234F">
                            </div>
                        </div>
                    </div>

                    <!-- 4. Pickup / Drop Location -->
                    <div class="space-y-4">
                        <h3 class="font-bold text-xs uppercase tracking-wider text-[#0F8B8D]">
                            4. Pickup / Collection Address
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">City <span class="text-red-500">*</span></label>
                                <input type="text" name="pickup_city" x-model="pickupCity" required
                                       class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-[#0F8B8D] outline-none"
                                       placeholder="e.g. Mumbai, Delhi, Lucknow">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Pincode <span class="text-red-500">*</span></label>
                                <input type="text" name="pickup_pincode" x-model="pickupPincode" required pattern="[0-9]{6}" maxlength="6"
                                       class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-[#0F8B8D] outline-none"
                                       placeholder="6-digit Pincode">
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold text-gray-700 mb-1">Complete Address / Landmark <span class="text-red-500">*</span></label>
                                <textarea name="pickup_address" x-model="pickupAddress" required rows="2"
                                          class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-[#0F8B8D] outline-none"
                                          placeholder="House/Flat No, Building, Street, Area, Landmark"></textarea>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold text-gray-700 mb-1">Allocated Project / Campaign <span class="text-gray-400 font-normal">(Optional)</span></label>
                                <select name="project_id" class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-[#0F8B8D] outline-none">
                                    <option value="">General Distribution / Greatest Need</option>
                                    <?php foreach ($projects as $project): ?>
                                        <option value="<?php echo (int)$project['id']; ?>"><?php echo htmlspecialchars($project['title']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold text-gray-700 mb-1">Special Notes / Preferred Pickup Time <span class="text-gray-400 font-normal">(Optional)</span></label>
                                <input type="text" name="remarks" placeholder="e.g. Please pick up after 5 PM on weekends"
                                       class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-[#0F8B8D] outline-none">
                            </div>
                        </div>
                    </div>

                    <?php if ($memberReferralCode !== ''): ?>
                        <div class="text-xs text-[#0F8B8D] bg-[#F0FDFD] border border-[#CCFBF1] rounded-xl px-4 py-3 flex items-center gap-2">
                            <i class="fa-solid fa-tag"></i>
                            Referral partner linked: <span class="font-mono font-bold"><?php echo htmlspecialchars($memberReferralCode); ?></span>
                        </div>
                    <?php endif; ?>

                    <template x-if="errorMessage">
                        <div class="p-4 rounded-xl bg-rose-50 text-rose-700 border border-rose-200 text-xs font-medium" x-html="errorMessage"></div>
                    </template>

                    <!-- Submit Button -->
                    <button type="submit" :disabled="loading"
                            class="w-full rounded-2xl bg-[#F4A640] hover:bg-[#d98e2b] text-white font-black py-4 text-base shadow-lg shadow-[#F4A640]/25 transition flex items-center justify-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed">
                        <span x-show="!loading"><i class="fa-solid fa-gift"></i> Submit Item Donation Pledge</span>
                        <span x-show="loading" class="flex items-center gap-2"><i class="fa-solid fa-circle-notch fa-spin"></i> Submitting Pledge...</span>
                    </button>
                </form>
            </div>

            <!-- Right Column: How it Works & Trust Badges -->
            <div class="space-y-6">
                
                <!-- 3 Steps Card -->
                <div class="bg-white rounded-[22px] p-6 shadow-xl border border-gray-100">
                    <h3 class="font-black text-lg text-gray-800 flex items-center gap-2">
                        <i class="fa-solid fa-route text-[#0F8B8D]"></i>
                        How Item Donation Works
                    </h3>
                    
                    <div class="mt-5 space-y-4">
                        <div class="flex items-start gap-3.5">
                            <div class="w-8 h-8 rounded-xl bg-teal-50 text-[#0F8B8D] font-black text-sm flex items-center justify-center flex-shrink-0">
                                1
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-gray-800">Submit Pledge Online</h4>
                                <p class="text-xs text-gray-500 mt-0.5">Select category, quantity, and your pickup location in the form.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3.5">
                            <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 font-black text-sm flex items-center justify-center flex-shrink-0">
                                2
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-gray-800">Coordinator Call & Pickup</h4>
                                <p class="text-xs text-gray-500 mt-0.5">Our local team coordinates pickup or guides you to the nearest drop-off center.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3.5">
                            <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 font-black text-sm flex items-center justify-center flex-shrink-0">
                                3
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-gray-800">Direct Beneficiary Distribution</h4>
                                <p class="text-xs text-gray-500 mt-0.5">Items are verified and distributed directly to children, families, and patients.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Accepted Items Card -->
                <div class="bg-[#F0FDFD] border border-[#CCFBF1] rounded-[22px] p-6 space-y-3">
                    <h3 class="font-bold text-sm text-[#0F8B8D] uppercase tracking-wider flex items-center gap-2">
                        <i class="fa-solid fa-list-check"></i>
                        Items Urgently Needed
                    </h3>
                    <ul class="space-y-2 text-xs text-gray-700">
                        <li class="flex items-center gap-2"><i class="fa-solid fa-circle-check text-emerald-500 text-[11px]"></i> School books, notebooks, bags & stationery</li>
                        <li class="flex items-center gap-2"><i class="fa-solid fa-circle-check text-emerald-500 text-[11px]"></i> Clean winter blankets, bedsheets & clothes</li>
                        <li class="flex items-center gap-2"><i class="fa-solid fa-circle-check text-emerald-500 text-[11px]"></i> Dry ration kits, rice, pulses & cooking oil</li>
                        <li class="flex items-center gap-2"><i class="fa-solid fa-circle-check text-emerald-500 text-[11px]"></i> Wheelchairs, crutches & assistive mobility aids</li>
                    </ul>
                </div>

                <!-- Bulk Goods Helpline -->
                <div class="bg-gradient-to-br from-[#1F2937] to-[#111827] text-white rounded-[22px] p-6 shadow-xl">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-xl bg-[#0F8B8D] flex items-center justify-center text-lg">
                            <i class="fa-solid fa-truck-ramp-box"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-sm">Corporate / Bulk Donations?</h4>
                            <p class="text-xs text-gray-400">Truckload & institutional in-kind giving</p>
                        </div>
                    </div>
                    <p class="text-xs text-gray-300 leading-relaxed mb-4">
                        If you are an organization or school planning a collection drive or large donation, our logistics team will coordinate direct vehicle transport.
                    </p>
                    <a href="contact.php" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#F4A640] hover:bg-[#d98e2b] text-white text-xs font-bold transition">
                        <i class="fa-solid fa-phone"></i> Contact Logistics Desk
                    </a>
                </div>

            </div>

        </div>

    </div>
</div>

<script>
function itemDonationPage(initialCategories) {
    return {
        categories: initialCategories || [],
        selectedCategory: initialCategories.length > 0 ? initialCategories[0].id : '',
        selectedUnit: 'pcs',
        conditionType: 'New',
        itemDescription: '',
        quantity: 1,
        estimatedValue: '',
        donorName: '',
        donorEmail: '',
        donorMobile: '',
        donorPan: '',
        pickupCity: '',
        pickupPincode: '',
        pickupAddress: '',
        photoPreview: null,
        loading: false,
        errorMessage: '',

        init() {
            if (this.categories.length > 0) {
                this.updateUnitsForCategory(this.categories[0]);
            }
        },

        get currentUnitOptions() {
            const cat = this.categories.find(c => c.id == this.selectedCategory);
            if (cat && cat.unit_suggestions) {
                return cat.unit_suggestions.split(',').map(s => s.trim()).filter(Boolean);
            }
            return ['pcs', 'kg', 'boxes', 'sets', 'packets'];
        },

        selectCategory(cat) {
            this.selectedCategory = cat.id;
            this.updateUnitsForCategory(cat);
        },

        updateUnitsForCategory(cat) {
            const units = (cat.unit_suggestions || '').split(',').map(s => s.trim()).filter(Boolean);
            if (units.length > 0) {
                this.selectedUnit = units[0];
            } else {
                this.selectedUnit = 'pcs';
            }
        },

        previewImage(e) {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 5 * 1024 * 1024) {
                    alert('Photo size exceeds 5MB limit.');
                    e.target.value = '';
                    this.photoPreview = null;
                    return;
                }
                const reader = new FileReader();
                reader.onload = (ev) => {
                    this.photoPreview = ev.target.result;
                };
                reader.readAsDataURL(file);
            }
        },

        clearPhoto() {
            this.photoPreview = null;
            const fileInput = document.querySelector('input[name="item_photo"]');
            if (fileInput) fileInput.value = '';
        },

        handleSubmit(e) {
            this.errorMessage = '';

            if (!this.selectedCategory) {
                e.preventDefault();
                this.errorMessage = 'Please select an item category.';
                return;
            }

            if (!this.donorMobile || !/^[0-9]{10}$/.test(this.donorMobile)) {
                e.preventDefault();
                this.errorMessage = 'Please enter a valid 10-digit mobile number.';
                return;
            }

            if (!this.pickupPincode || !/^[0-9]{6}$/.test(this.pickupPincode)) {
                e.preventDefault();
                this.errorMessage = 'Please enter a valid 6-digit postal pincode.';
                return;
            }

            this.loading = true;
        }
    };
}
</script>

<?php require 'includes/footer.php'; ?>
