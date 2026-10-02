<?php
// ============================================================
// admin/agreement_composer.php
// Agreement & MoU Composer Studio with Live A4 Preview
// Reuses Module 19 Letterhead Engine with pre-built template presets
// Author: VELNIX SOFT / Antigravity AI
// Date: 2026-09-12
// ============================================================

require 'includes/header.php';
require '../config/db.php';
require_once '../includes/functions.php';
require_once '../includes/letterhead_helper.php';
require_once '../includes/agreement_helper.php';

if (!canAccessModule($pdo, 'coordinator', 'page.letters')) {
    setFlash('error', 'Access denied. You do not have permission to compose agreements.');
    header('Location: dashboard.php');
    exit;
}

$csrfToken = generateCsrfToken();
$lhSettings = get_letterhead_settings($pdo);
$settings = mm_load_settings($pdo);
$orgName = $settings['site_name'] ?? 'Jaysmrutti Foundation';
$types = get_agreement_types();

$editId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$presetType = cleanInput($_GET['type'] ?? 'mou');
if (!isset($types[$presetType])) {
    $presetType = 'mou';
}

$agreement = null;
if ($editId) {
    $stmt = $pdo->prepare("SELECT * FROM agreements WHERE id = ?");
    $stmt->execute([$editId]);
    $agreement = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$agreement) {
        setFlash('error', 'Agreement record not found.');
        header('Location: agreements.php');
        exit;
    }
    $presetType = $agreement['type'];
}

// Generate default sequential reference number if new
$defaultRefNo = $agreement['agreement_no'] ?? generate_agreement_number($pdo, $presetType);

// Preset template
$preset = get_agreement_template_preset($presetType, [
    'lhSettings' => $lhSettings,
    'partner_name' => $agreement['partner_name'] ?? 'Sanjeevani Multispeciality Hospital & Research Center',
    'partner_address' => $agreement['partner_address'] ?? 'Sector 4, Main Highway Road, Lucknow, Uttar Pradesh',
    'partner_type' => $agreement['partner_type'] ?? 'Healthcare / Hospital',
    'signed_date' => $agreement['signed_date'] ?? date('Y-m-d'),
    'valid_until' => $agreement['valid_until'] ?? date('Y-m-d', strtotime('+1 year'))
]);

$formData = [
    'id' => $agreement['id'] ?? 0,
    'type' => $agreement['type'] ?? $presetType,
    'agreement_no' => $agreement['agreement_no'] ?? $defaultRefNo,
    'partner_name' => $agreement['partner_name'] ?? 'Sanjeevani Multispeciality Hospital & Research Center',
    'partner_type' => $agreement['partner_type'] ?? 'Healthcare / Hospital',
    'partner_contact' => $agreement['partner_contact'] ?? '+91 9876543210',
    'partner_email' => $agreement['partner_email'] ?? 'partner.contact@example.org',
    'partner_address' => $agreement['partner_address'] ?? 'Sector 4, Main Highway Road, Lucknow, Uttar Pradesh',
    'title' => $agreement['title'] ?? $preset['title'],
    'content' => $agreement['content'] ?? $preset['content'],
    'signed_status' => $agreement['signed_status'] ?? 'draft',
    'signed_date' => $agreement['signed_date'] ?? date('Y-m-d'),
    'valid_until' => $agreement['valid_until'] ?? date('Y-m-d', strtotime('+1 year')),
    'first_party_name' => $agreement['first_party_name'] ?? ($lhSettings['signatory_name'] ?: 'Authorized Signatory'),
    'first_party_designation' => $agreement['first_party_designation'] ?? ($lhSettings['signatory_designation'] ?: 'President / General Secretary'),
    'second_party_name' => $agreement['second_party_name'] ?? 'Dr. Rajeshwar Sharma',
    'second_party_designation' => $agreement['second_party_designation'] ?? 'Medical Director / Managing Trustee'
];
?>

<div class="flex h-screen overflow-hidden bg-slate-50 dark:bg-gray-900"
     x-data="agreementComposer({
         initialData: <?php echo json_encode($formData); ?>,
         types: <?php echo json_encode($types); ?>,
         lhSettings: <?php echo json_encode($lhSettings); ?>,
         orgName: <?php echo json_encode($orgName); ?>,
         isEdit: <?php echo $editId ? 'true' : 'false'; ?>
     })"
     x-cloak>

    <?php require 'includes/sidebar.php'; ?>

    <div class="flex-1 flex flex-col md:ml-64 overflow-hidden">
        <?php require 'includes/navbar.php'; ?>

        <!-- Studio Action Header -->
        <header class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700/60 px-5 py-3.5 flex flex-wrap items-center justify-between gap-3 flex-shrink-0 z-10 shadow-xs">
            <div class="flex items-center gap-3">
                <a href="agreements.php" class="p-2 rounded-xl text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" title="Back to Agreements">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <div>
                    <h1 class="text-base sm:text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fa-solid fa-file-contract text-indigo-600 dark:text-indigo-400"></i>
                        <span x-text="isEdit ? 'Edit Agreement / MoU #' + form.agreement_no : 'Compose Agreement & MoU Studio'"></span>
                    </h1>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Official Letterhead Legal Agreement Engine • Bilateral Signatures
                    </p>
                </div>
            </div>

            <!-- Header Quick Action Buttons -->
            <div class="flex items-center gap-2">
                <button type="button" @click="autoFillTokens()" class="px-3 py-2 rounded-xl text-xs font-semibold bg-indigo-50 text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-900/30 dark:text-indigo-300 dark:hover:bg-indigo-900/50 border border-indigo-200 dark:border-indigo-800 transition-all flex items-center gap-1.5" title="Replace all [Tags] with real values">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                    <span class="hidden sm:inline">Auto-Fill Placeholders</span>
                </button>

                <button type="button" @click="submitForm(false)" :disabled="saving" class="px-4 py-2 rounded-xl text-xs font-bold bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 transition-all flex items-center gap-1.5 shadow-xs">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span x-text="saving ? 'Saving...' : 'Save Record'"></span>
                </button>

                <button type="button" @click="submitForm(true)" :disabled="saving" class="px-4 py-2 rounded-xl text-xs font-bold bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white shadow-md shadow-indigo-200 dark:shadow-none transition-all flex items-center gap-1.5">
                    <i class="fa-solid fa-file-pdf"></i>
                    <span>Save & Download PDF</span>
                </button>
            </div>
        </header>

        <!-- Template Selector Switcher Strip -->
        <div class="bg-gray-50 dark:bg-gray-850 border-b border-gray-200 dark:border-gray-700/60 px-5 py-2.5 overflow-x-auto flex items-center gap-2 flex-shrink-0">
            <span class="text-[11px] font-bold uppercase tracking-wider text-gray-400 mr-1 whitespace-nowrap">
                <i class="fa-solid fa-layer-group text-xs mr-1"></i> Template Presets:
            </span>
            <template x-for="(tpl, key) in types" :key="key">
                <button type="button" @click="switchTemplate(key)"
                        class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all duration-150 flex items-center gap-1.5 whitespace-nowrap"
                        :class="form.type === key 
                            ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-300 dark:shadow-none' 
                            : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700'">
                    <i class="fa-solid text-[11px]" :class="tpl.icon"></i>
                    <span x-text="tpl.short_title"></span>
                </button>
            </template>
        </div>

        <!-- Split Workspace Studio Body -->
        <div class="flex-1 flex flex-col lg:flex-row overflow-hidden">
            
            <!-- LEFT COLUMN: Form Editor Inputs -->
            <div class="w-full lg:w-1/2 overflow-y-auto p-4 md:p-6 space-y-5 bg-slate-50 dark:bg-gray-900 border-r border-gray-200 dark:border-gray-700/60">
                <form id="agreementComposerForm" @submit.prevent="submitForm(false)" class="space-y-5">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="action" value="save_agreement">
                    <input type="hidden" name="id" :value="form.id">
                    <input type="hidden" name="type" :value="form.type">
                    <input type="hidden" name="is_ajax" value="1">

                    <!-- Section 1: Partner (Second Party) Particulars -->
                    <div class="bg-white dark:bg-gray-800 p-4 md:p-5 rounded-2xl border border-gray-200/80 dark:border-gray-700 shadow-xs space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-gray-100 dark:border-gray-700">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400 flex items-center gap-2">
                                <i class="fa-solid fa-handshake"></i>
                                <span>1. Second Party / Partner Organization Details</span>
                            </h3>
                            <span class="text-[10px] text-gray-400 font-semibold">Legal Entity</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 pt-1">
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                    Partner Organization / Institution Full Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="partner_name" x-model="form.partner_name" required placeholder="e.g. Sanjeevani Multispeciality Hospital" class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-900 dark:text-white font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Partner Sector / Type</label>
                                <input type="text" name="partner_type" x-model="form.partner_type" placeholder="e.g. Hospital / Educational / Corporate" class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-900 dark:text-white focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Official Contact Phone</label>
                                <input type="text" name="partner_contact" x-model="form.partner_contact" placeholder="+91 XXXXX XXXXX" class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-900 dark:text-white focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Partner Official Email (For dispatch)</label>
                                <input type="email" name="partner_email" x-model="form.partner_email" placeholder="contact@partner.org" class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-900 dark:text-white focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Partner Full Address & City</label>
                                <textarea name="partner_address" x-model="form.partner_address" rows="2" placeholder="Facility address, sector, district, state..." class="w-full px-3.5 py-2 bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-900 dark:text-white focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Agreement Parameters & Execution Timeline -->
                    <div class="bg-white dark:bg-gray-800 p-4 md:p-5 rounded-2xl border border-gray-200/80 dark:border-gray-700 shadow-xs space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-gray-100 dark:border-gray-700">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400 flex items-center gap-2">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                                <span>2. Agreement Parameters & Timeline</span>
                            </h3>
                            <span class="text-[10px] text-gray-400 font-semibold">Terms & Serial</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 pt-1">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Agreement / Ref No. <span class="text-red-500">*</span></label>
                                <input type="text" name="agreement_no" x-model="form.agreement_no" required class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-xl text-xs font-mono font-bold text-indigo-600 dark:text-indigo-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Signed Status</label>
                                <select name="signed_status" x-model="form.signed_status" class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-900 dark:text-white focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-semibold">
                                    <option value="draft">Draft (Under Preparation)</option>
                                    <option value="pending_signature">Pending Signature (Sent for Sign)</option>
                                    <option value="signed">Signed & Fully Executed</option>
                                    <option value="expired">Expired Term</option>
                                    <option value="terminated">Terminated</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Execution / Signed Date <span class="text-red-500">*</span></label>
                                <input type="date" name="signed_date" x-model="form.signed_date" required class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-900 dark:text-white focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Valid Until / Expiry Date</label>
                                <input type="date" name="valid_until" x-model="form.valid_until" class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-900 dark:text-white focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Agreement / MoU Title Header <span class="text-red-500">*</span></label>
                                <input type="text" name="title" x-model="form.title" required placeholder="Title banner displayed on letterhead" class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-xl text-xs font-bold text-gray-900 dark:text-white focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Dual Signatory Authorities -->
                    <div class="bg-white dark:bg-gray-800 p-4 md:p-5 rounded-2xl border border-gray-200/80 dark:border-gray-700 shadow-xs space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-gray-100 dark:border-gray-700">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400 flex items-center gap-2">
                                <i class="fa-solid fa-signature"></i>
                                <span>3. Dual Signatory Representation (First Party & Second Party)</span>
                            </h3>
                            <span class="text-[10px] text-gray-400 font-semibold">Signatory Block</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                            <!-- First Party Signatory (NGO) -->
                            <div class="p-3 bg-indigo-50/40 dark:bg-indigo-950/20 rounded-xl border border-indigo-100 dark:border-indigo-800/40 space-y-2.5">
                                <p class="text-[11px] font-bold text-indigo-900 dark:text-indigo-300 flex items-center gap-1.5">
                                    <i class="fa-solid fa-building-ngo"></i> First Party (NGO Lead)
                                </p>
                                <div>
                                    <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-400 mb-1">Signatory Name</label>
                                    <input type="text" name="first_party_name" x-model="form.first_party_name" class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-400 mb-1">Designation / Role</label>
                                    <input type="text" name="first_party_designation" x-model="form.first_party_designation" class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                            </div>

                            <!-- Second Party Signatory (Partner Organization) -->
                            <div class="p-3 bg-emerald-50/40 dark:bg-emerald-950/20 rounded-xl border border-emerald-100 dark:border-emerald-800/40 space-y-2.5">
                                <p class="text-[11px] font-bold text-emerald-900 dark:text-emerald-300 flex items-center gap-1.5">
                                    <i class="fa-solid fa-hospital-user"></i> Second Party (Partner Lead)
                                </p>
                                <div>
                                    <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-400 mb-1">Signatory Name</label>
                                    <input type="text" name="second_party_name" x-model="form.second_party_name" class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-gray-600 dark:text-gray-400 mb-1">Designation / Title</label>
                                    <input type="text" name="second_party_designation" x-model="form.second_party_designation" class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 4: Rich Content Clauses & Text Body -->
                    <div class="bg-white dark:bg-gray-800 p-4 md:p-5 rounded-2xl border border-gray-200/80 dark:border-gray-700 shadow-xs space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-gray-100 dark:border-gray-700">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400 flex items-center gap-2">
                                <i class="fa-solid fa-file-lines"></i>
                                <span>4. Agreement Terms, Clauses & Conditions</span>
                            </h3>
                            <button type="button" @click="resetToTemplatePreset()" class="text-xs text-red-500 hover:text-red-600 font-semibold inline-flex items-center gap-1">
                                <i class="fa-solid fa-rotate-left"></i> Reset Preset
                            </button>
                        </div>

                        <div>
                            <textarea name="content" x-model="form.content" rows="14" required class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-900 dark:text-white font-mono leading-relaxed focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                            <p class="text-[11px] text-gray-400 mt-1.5 flex items-center gap-2">
                                <i class="fa-solid fa-circle-info"></i>
                                <span>Double line breaks separate clauses into distinct formatted paragraphs on the official Letterhead.</span>
                            </p>
                        </div>
                    </div>
                </form>
            </div>

            <!-- RIGHT COLUMN: Real-Time Live A4 Letterhead Paper Preview -->
            <div class="w-full lg:w-1/2 bg-slate-200/70 dark:bg-gray-950 p-4 md:p-6 overflow-y-auto flex items-start justify-center">
                
                <!-- A4 Simulated Paper Sheet -->
                <div class="w-full max-w-[620px] bg-white text-gray-800 shadow-2xl rounded-sm p-8 sm:p-10 border border-gray-200 min-h-[880px] flex flex-col justify-between font-sans relative transition-all">
                    
                    <div>
                        <!-- Top Letterhead Color Accent Bar -->
                        <div class="h-1.5 w-full absolute top-0 left-0" :style="'background-color: ' + (lhSettings.header_color || '#0F8B8D')"></div>

                        <!-- 1. Header with Logo & Org Details -->
                        <div class="flex items-start justify-between gap-4 pb-4 border-b-2" :style="'border-color: ' + (lhSettings.header_color || '#0F8B8D')">
                            <div class="flex items-start gap-3.5">
                                <template x-if="lhSettings.logo">
                                    <img :src="'../' + lhSettings.logo" alt="Logo" class="w-14 h-14 object-contain flex-shrink-0">
                                </template>
                                <template x-if="!lhSettings.logo">
                                    <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white font-black text-xl shadow-xs" :style="'background-color: ' + (lhSettings.header_color || '#0F8B8D')">
                                        <span x-text="lhSettings.org_name.charAt(0)"></span>
                                    </div>
                                </template>
                                <div>
                                    <h2 class="font-black text-lg text-slate-900 leading-tight" x-text="lhSettings.org_name"></h2>
                                    <p class="text-[10px] font-bold mt-0.5" :style="'color: ' + (lhSettings.header_color || '#0F8B8D')" x-text="lhSettings.tagline"></p>
                                    <p class="text-[9px] text-gray-500 font-medium" x-text="lhSettings.reg_no"></p>
                                </div>
                            </div>

                            <div class="text-right text-[8.5px] text-gray-600 space-y-0.5 leading-tight flex-shrink-0">
                                <p><span class="font-semibold text-gray-800">Phone:</span> <span x-text="lhSettings.phone"></span></p>
                                <p><span class="font-semibold text-gray-800">Email:</span> <span x-text="lhSettings.email"></span></p>
                                <p><span class="font-semibold text-gray-800">Web:</span> <span x-text="lhSettings.website"></span></p>
                                <p class="max-w-[140px] text-gray-500 italic mt-0.5" x-text="lhSettings.address"></p>
                            </div>
                        </div>

                        <!-- 2. Reference & Signed Date Bar -->
                        <div class="flex items-center justify-between text-[10px] font-semibold text-gray-600 mt-4 pb-2 border-b border-gray-100">
                            <span class="font-mono font-bold text-gray-800" x-text="'Ref: ' + (form.agreement_no || 'MOU-XXXX')"></span>
                            <div class="text-right">
                                <span class="text-gray-700" x-text="'Signed Date: ' + formatDate(form.signed_date)"></span>
                                <template x-if="form.valid_until">
                                    <span class="text-gray-400 block text-[9px]" x-text="'Valid Through: ' + formatDate(form.valid_until)"></span>
                                </template>
                            </div>
                        </div>

                        <!-- 3. Agreement Title Banner -->
                        <div class="my-4 py-2 px-3 rounded bg-slate-100/90 border-b-2 text-center" :style="'border-color: ' + (lhSettings.header_color || '#0F8B8D')">
                            <h3 class="text-xs font-black uppercase tracking-wider" :style="'color: ' + (lhSettings.header_color || '#0F8B8D')" x-text="form.title"></h3>
                        </div>

                        <!-- 4. Body Content Paragraphs (Simulated Formatted Output) -->
                        <div class="space-y-3 text-[10px] leading-relaxed text-slate-800 text-justify">
                            <template x-for="(para, idx) in previewParagraphs" :key="idx">
                                <div>
                                    <template x-if="isHeading(para)">
                                        <h4 class="font-bold text-[10.5px] mt-2 mb-1 uppercase" :style="'color: ' + (lhSettings.header_color || '#0F8B8D')" x-text="para"></h4>
                                    </template>
                                    <template x-if="!isHeading(para)">
                                        <p class="whitespace-pre-line" x-text="para"></p>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- 5. Execution Signatory Block (Bottom Dual Signatures) -->
                    <div class="pt-8 mt-6 border-t border-gray-200/80">
                        <div class="grid grid-cols-2 gap-6 text-[9.5px]">
                            
                            <!-- First Party Signatory (NGO) -->
                            <div>
                                <p class="font-bold uppercase text-[10px]" :style="'color: ' + (lhSettings.header_color || '#0F8B8D')" x-text="'FOR FIRST PARTY: ' + lhSettings.org_name.slice(0, 24)"></p>
                                <div class="h-10 my-1 flex items-end">
                                    <template x-if="lhSettings.signature_image">
                                        <img :src="'../' + lhSettings.signature_image" alt="Signature" class="max-h-8 object-contain">
                                    </template>
                                </div>
                                <p class="font-bold text-gray-900" x-text="form.first_party_name || lhSettings.signatory_name"></p>
                                <p class="text-gray-500" x-text="form.first_party_designation || lhSettings.signatory_designation"></p>
                                <p class="text-[8px] text-gray-400 italic" x-text="lhSettings.org_name + ' (Official Seal)'"></p>
                            </div>

                            <!-- Second Party Signatory (Partner) -->
                            <div class="text-right">
                                <p class="font-bold uppercase text-[10px]" :style="'color: ' + (lhSettings.header_color || '#0F8B8D')" x-text="'FOR SECOND PARTY: ' + (form.partner_name || 'Partner').slice(0, 24)"></p>
                                <div class="h-10 my-1 flex items-end justify-end">
                                    <span class="text-gray-300 text-[9px] italic border-b border-dashed border-gray-300 pb-0.5">[Authorized Seal & Sign]</span>
                                </div>
                                <p class="font-bold text-gray-900" x-text="form.second_party_name || 'Authorized Signatory'"></p>
                                <p class="text-gray-500" x-text="form.second_party_designation || 'Director / Representative'"></p>
                                <p class="text-[8px] text-gray-400 italic" x-text="(form.partner_name || 'Partner') + ' (Signature & Seal)'"></p>
                            </div>
                        </div>

                        <!-- Footer Statutory Notice -->
                        <div class="mt-8 pt-3 border-t border-gray-200 text-center text-[8px] text-gray-400">
                            <p x-text="lhSettings.footer_text"></p>
                            <p class="text-[7.5px] mt-0.5">Page 1 of 1 • Official Letterhead Generated Agreement</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('agreementComposer', ({ initialData, types, lhSettings, orgName, isEdit }) => ({
        form: initialData,
        types: types,
        lhSettings: lhSettings,
        orgName: orgName,
        isEdit: isEdit,
        saving: false,

        get previewParagraphs() {
            if (!this.form.content) return [];
            let text = this.form.content;
            
            // Auto replace placeholder tokens in real-time
            const search = [
                '\\[Partner Name\\]',
                '\\[Partner Type\\]',
                '\\[Partner Address\\]',
                '\\[Partner Contact\\]',
                '\\[Partner Email\\]',
                '\\[Agreement No\\]',
                '\\[Agreement Title\\]',
                '\\[Signed Date\\]',
                '\\[Valid Until\\]',
                '\\[Org Name\\]',
                '\\[Reg No\\]',
                '\\[Org Address\\]',
                '\\[First Party Signatory\\]',
                '\\[Second Party Signatory\\]'
            ];

            const replace = [
                this.form.partner_name || 'Partner Organization',
                this.form.partner_type || 'Partner Entity',
                this.form.partner_address || 'Partner Address',
                this.form.partner_contact || '',
                this.form.partner_email || '',
                this.form.agreement_no || 'MOU-XXXX',
                this.form.title || 'Agreement',
                this.formatDate(this.form.signed_date),
                this.form.valid_until ? this.formatDate(this.form.valid_until) : 'Mutual Period',
                this.lhSettings.org_name || 'NGO',
                this.lhSettings.reg_no || '',
                this.lhSettings.address || '',
                this.form.first_party_name || this.lhSettings.signatory_name,
                this.form.second_party_name || 'Authorized Representative'
            ];

            for (let i = 0; i < search.length; i++) {
                text = text.replace(new RegExp(search[i], 'g'), replace[i]);
            }

            return text.split(/\r?\n\r?\n/).filter(p => p.trim() !== '');
        },

        isHeading(para) {
            para = para.trim();
            return /^(\d+\.|\bFIRST PARTY:|\bSECOND PARTY:|\bWHEREAS:|\bNOW, THEREFORE|\bIN WITNESS WHEREOF|\bSCOPE OF AUTHORIZATION|\bVALIDITY & MONITORING|\bTO WHOMSOEVER)/i.test(para) ||
                   (para.toUpperCase() === para && para.length < 60);
        },

        formatDate(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            if (isNaN(d.getTime())) return dateStr;
            return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
        },

        async switchTemplate(typeKey) {
            this.form.type = typeKey;
            
            try {
                const params = new URLSearchParams({
                    action: 'get_template_text',
                    type: typeKey,
                    partner_name: this.form.partner_name,
                    partner_address: this.form.partner_address,
                    signed_date: this.form.signed_date,
                    valid_until: this.form.valid_until
                });

                const res = await fetch(`actions/agreement_logic.php?${params.toString()}`);
                const data = await res.json();
                if (data.success) {
                    this.form.title = data.title;
                    this.form.content = data.content;
                    if (!this.isEdit) {
                        this.form.first_party_name = data.first_party_name;
                        this.form.first_party_designation = data.first_party_designation;
                        this.form.second_party_name = data.second_party_name;
                        this.form.second_party_designation = data.second_party_designation;
                    }
                }
            } catch (err) {
                console.error(err);
            }
        },

        resetToTemplatePreset() {
            if (confirm('Reset content and title to default template preset? Any manual clause changes will be overwritten.')) {
                this.switchTemplate(this.form.type);
            }
        },

        autoFillTokens() {
            let content = this.form.content;
            const search = [
                '\\[Partner Name\\]',
                '\\[Partner Type\\]',
                '\\[Partner Address\\]',
                '\\[Partner Contact\\]',
                '\\[Partner Email\\]',
                '\\[Agreement No\\]',
                '\\[Agreement Title\\]',
                '\\[Signed Date\\]',
                '\\[Valid Until\\]',
                '\\[Org Name\\]',
                '\\[Reg No\\]',
                '\\[Org Address\\]',
                '\\[First Party Signatory\\]',
                '\\[Second Party Signatory\\]'
            ];

            const replace = [
                this.form.partner_name || 'Partner Organization',
                this.form.partner_type || 'Partner Entity',
                this.form.partner_address || 'Partner Address',
                this.form.partner_contact || '',
                this.form.partner_email || '',
                this.form.agreement_no || 'MOU-XXXX',
                this.form.title || 'Agreement',
                this.formatDate(this.form.signed_date),
                this.form.valid_until ? this.formatDate(this.form.valid_until) : 'Mutual Period',
                this.lhSettings.org_name || 'NGO',
                this.lhSettings.reg_no || '',
                this.lhSettings.address || '',
                this.form.first_party_name || this.lhSettings.signatory_name,
                this.form.second_party_name || 'Authorized Representative'
            ];

            for (let i = 0; i < search.length; i++) {
                content = content.replace(new RegExp(search[i], 'g'), replace[i]);
            }
            this.form.content = content;
        },

        async submitForm(downloadAfter = false) {
            if (!this.form.partner_name || !this.form.title || !this.form.content) {
                alert('Please fill in all required fields (Partner Name, Agreement Title, and Content).');
                return;
            }

            this.saving = true;
            const formData = new FormData();
            formData.append('csrf_token', '<?php echo $csrfToken; ?>');
            formData.append('action', 'save_agreement');
            formData.append('id', this.form.id || '');
            formData.append('type', this.form.type);
            formData.append('agreement_no', this.form.agreement_no);
            formData.append('partner_name', this.form.partner_name);
            formData.append('partner_type', this.form.partner_type);
            formData.append('partner_contact', this.form.partner_contact);
            formData.append('partner_email', this.form.partner_email);
            formData.append('partner_address', this.form.partner_address);
            formData.append('title', this.form.title);
            formData.append('content', this.form.content);
            formData.append('signed_status', this.form.signed_status);
            formData.append('signed_date', this.form.signed_date);
            formData.append('valid_until', this.form.valid_until);
            formData.append('first_party_name', this.form.first_party_name);
            formData.append('first_party_designation', this.form.first_party_designation);
            formData.append('second_party_name', this.form.second_party_name);
            formData.append('second_party_designation', this.form.second_party_designation);
            formData.append('is_ajax', '1');

            try {
                const res = await fetch('actions/agreement_logic.php', { method: 'POST', body: formData });
                const data = await res.json();
                this.saving = false;

                if (data.success) {
                    if (downloadAfter) {
                        window.location.href = `actions/agreement_logic.php?action=download_agreement&id=${data.agreement_id}`;
                        setTimeout(() => {
                            window.location.href = 'agreements.php';
                        }, 1200);
                    } else {
                        window.location.href = 'agreements.php';
                    }
                } else {
                    alert(data.message || 'Failed to save agreement.');
                }
            } catch (err) {
                this.saving = false;
                console.error(err);
                alert('An error occurred while saving the agreement.');
            }
        }
    }));
});
</script>

<?php require 'includes/footer.php'; ?>
