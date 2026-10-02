<?php
// ============================================================
// admin/letter_editor.php
// Official Letterhead Composer with Live A4 Preview & Rich Formatting
// Integrates seamlessly with CMS Letterhead Settings
// ============================================================

require 'includes/header.php';
require '../config/db.php';
require_once '../includes/functions.php';
require_once '../includes/letterhead_helper.php';

if (!canAccessModule($pdo, 'coordinator', 'page.letters')) {
    setFlash('error', 'Access denied. You do not have permission to compose letters.');
    header('Location: dashboard.php');
    exit;
}

$csrfToken = generateCsrfToken();
$lhSettings = get_letterhead_settings($pdo);

$editId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$letter = null;

if ($editId) {
    $stmt = $pdo->prepare("SELECT * FROM letters WHERE id = ?");
    $stmt->execute([$editId]);
    $letter = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$letter) {
        setFlash('error', 'Letter record not found.');
        header('Location: letters.php');
        exit;
    }
}

// Generate sequential default reference number if new
$currentYear = date('Y');
$nextSeq = (int)($pdo->query("SELECT COUNT(*) FROM letters WHERE YEAR(generated_date) = {$currentYear}")->fetchColumn() ?: 0) + 1;
$defaultRefNo = "JMF/LTR/{$currentYear}/" . str_pad($nextSeq, 3, '0', STR_PAD_LEFT);

// Default initial state
$formData = [
    'id' => $letter['id'] ?? 0,
    'letter_type' => $letter['letter_type'] ?? 'Appointment Letter',
    'reference_no' => $letter['reference_no'] ?? $defaultRefNo,
    'subject' => $letter['subject'] ?? 'Letter of Appointment - District Coordinator',
    'recipient_name' => $letter['recipient_name'] ?? 'Rahul Verma',
    'recipient_designation' => $letter['recipient_designation'] ?? 'Senior Outreach Coordinator',
    'recipient_organization' => $letter['recipient_organization'] ?? 'Community Welfare Division',
    'recipient_address' => $letter['recipient_address'] ?? "Plot No. 45, Civil Lines,\nJaunpur, Uttar Pradesh - 222002",
    'recipient_email' => $letter['recipient_email'] ?? 'rahul.verma@example.com',
    'recipient_phone' => $letter['recipient_phone'] ?? '+91 9876543210',
    'generated_date' => $letter['generated_date'] ?? date('Y-m-d'),
    'content' => $letter['content'] ?? "We are pleased to appoint you as the Senior Outreach Coordinator for Jaysmrutti Foundation with effect from September 15, 2026.\n\nIn this role, you will be responsible for overseeing our community welfare programs, organizing rural awareness camps, and coordinating volunteer field teams across the district.\n\nWe look forward to your dedication and valuable leadership towards achieving our social impact goals.",
    'status' => $letter['status'] ?? 'Generated',
    'signatory_name' => $letter['signatory_name'] ?? $lhSettings['signatory_name'],
    'signatory_designation' => $letter['signatory_designation'] ?? $lhSettings['signatory_designation'],
];
?>

<div class="flex h-screen overflow-hidden bg-slate-50 dark:bg-gray-900"
     x-data="letterComposer({
        initialData: <?php echo json_encode($formData); ?>,
        lhSettings: <?php echo json_encode($lhSettings); ?>,
        isEdit: <?php echo $editId ? 'true' : 'false'; ?>
     })"
     x-init="init()">

    <?php require 'includes/sidebar.php'; ?>

    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300 min-w-0">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6 lg:p-8">

            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div>
                    <a href="letters.php" class="text-xs font-bold text-teal-600 dark:text-teal-400 hover:underline mb-1 inline-flex items-center gap-1">
                        <i class="fa-solid fa-arrow-left"></i> Back to Letters Directory
                    </a>
                    <h3 class="text-2xl md:text-3xl font-black text-gray-800 dark:text-white flex items-center gap-3">
                        <span class="w-10 h-10 rounded-2xl bg-teal-50 dark:bg-teal-900/40 text-[#0F8B8D] flex items-center justify-center text-lg shadow-sm">
                            <i class="fa-solid fa-pen-nib"></i>
                        </span>
                        <?php echo $editId ? 'Edit Official Letter' : 'Compose Official Letter'; ?>
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Compose official correspondence with real-time A4 Letterhead preview, automatic header/footer branding, and instant PDF download.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <a href="settings.php?tab=letterhead" target="_blank" class="bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 hover:bg-slate-50 dark:hover:bg-gray-700 font-bold px-3.5 py-2 rounded-xl text-xs flex items-center gap-1.5 transition shadow-sm">
                        <i class="fa-solid fa-gear text-teal-600"></i> Letterhead Settings
                    </a>
                    <?php if ($editId): ?>
                    <a href="download_letter.php?id=<?php echo (int)$editId; ?>&download=1" class="bg-rose-600 hover:bg-rose-700 text-white font-bold px-3.5 py-2 rounded-xl text-xs flex items-center gap-1.5 shadow-sm transition">
                        <i class="fa-solid fa-file-pdf"></i> Download PDF
                    </a>
                    <?php endif; ?>
                    <button type="button" @click="printPreview()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-4 py-2 rounded-xl text-xs flex items-center gap-1.5 shadow-sm transition">
                        <i class="fa-solid fa-print"></i> Print Preview
                    </button>
                </div>
            </div>

            <!-- Main Dual Pane Grid: Left Editor & Right Live A4 Preview -->
            <form action="actions/letter_logic.php" method="POST" id="letterForm" class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="action" value="<?php echo $editId ? 'update_letter' : 'create_letter'; ?>">
                <input type="hidden" name="id" value="<?php echo (int)$editId; ?>">

                <!-- ════════════════════════════════════════════════════════════ -->
                <!-- LEFT COLUMN: LETTER COMPOSER & METADATA (7 Cols)           -->
                <!-- ════════════════════════════════════════════════════════════ -->
                <div class="xl:col-span-6 space-y-5">
                    
                    <!-- 1. Template Presets & Letter Classification -->
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-3xl border border-slate-200 dark:border-gray-700 shadow-sm space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100 dark:border-gray-700">
                            <span class="text-xs font-black uppercase text-gray-700 dark:text-gray-200 flex items-center gap-2">
                                <i class="fa-solid fa-wand-magic-sparkles text-teal-600"></i> Quick Preset Templates
                            </span>
                            <div class="flex items-center gap-2">
                                <select @change="applyTemplate($event.target.value)" class="text-xs font-bold py-1.5 px-3 rounded-xl border bg-teal-50 dark:bg-teal-900/30 border-teal-200 dark:border-teal-700 text-[#0F8B8D] dark:text-teal-300 outline-none">
                                    <option value="">Load Preset Template...</option>
                                    <option value="appointment">Appointment Letter</option>
                                    <option value="appreciation">Appreciation Letter</option>
                                    <option value="experience">Experience Certificate</option>
                                    <option value="csr_request">Donation / CSR Request</option>
                                    <option value="recommendation">Recommendation Letter</option>
                                    <option value="relieving">Relieving Letter</option>
                                    <option value="inquiry_notice">Official Notice / Inquiry</option>
                                    <option value="general">General Official Letter</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <!-- Letter Type -->
                            <div>
                                <label class="text-[11px] font-bold text-gray-600 dark:text-gray-400 block mb-1">Letter Type *</label>
                                <select name="letter_type" x-model="form.letter_type" class="w-full text-xs font-medium p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                                    <option value="Appointment Letter">Appointment Letter</option>
                                    <option value="Appreciation Letter">Appreciation Letter</option>
                                    <option value="Experience Certificate">Experience Certificate</option>
                                    <option value="Donation / CSR Request">Donation / CSR Request</option>
                                    <option value="Recommendation Letter">Recommendation Letter</option>
                                    <option value="Relieving Letter">Relieving Letter</option>
                                    <option value="Official Notice">Official Notice</option>
                                    <option value="General Official Letter">General Official Letter</option>
                                </select>
                            </div>

                            <!-- Reference Number -->
                            <div>
                                <label class="text-[11px] font-bold text-gray-600 dark:text-gray-400 block mb-1">Reference / Dispatch No.</label>
                                <input type="text" name="reference_no" x-model="form.reference_no" class="w-full text-xs font-mono font-bold p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                            </div>

                            <!-- Date of Issuance -->
                            <div>
                                <label class="text-[11px] font-bold text-gray-600 dark:text-gray-400 block mb-1">Issuance Date *</label>
                                <input type="date" name="generated_date" x-model="form.generated_date" required class="w-full text-xs font-medium p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                            </div>
                        </div>
                    </div>

                    <!-- 2. Recipient Information Section -->
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-3xl border border-slate-200 dark:border-gray-700 shadow-sm space-y-4">
                        <span class="text-xs font-black uppercase text-gray-700 dark:text-gray-200 flex items-center gap-2">
                            <i class="fa-solid fa-user-tag text-teal-600"></i> Recipient Particulars
                        </span>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="sm:col-span-2">
                                <label class="text-[11px] font-bold text-gray-600 dark:text-gray-400 block mb-1">Recipient Full Name <span class="text-red-500">*</span></label>
                                <input type="text" name="recipient_name" x-model="form.recipient_name" required placeholder="e.g. Mr. Rahul Verma / Dr. Amit Singh" class="w-full text-xs font-bold p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                            </div>

                            <div>
                                <label class="text-[11px] font-bold text-gray-600 dark:text-gray-400 block mb-1">Designation / Title</label>
                                <input type="text" name="recipient_designation" x-model="form.recipient_designation" placeholder="e.g. Senior Outreach Coordinator" class="w-full text-xs font-medium p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                            </div>

                            <div>
                                <label class="text-[11px] font-bold text-gray-600 dark:text-gray-400 block mb-1">Company / Organization</label>
                                <input type="text" name="recipient_organization" x-model="form.recipient_organization" placeholder="e.g. Community Welfare Division" class="w-full text-xs font-medium p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                            </div>

                            <div class="sm:col-span-2">
                                <label class="text-[11px] font-bold text-gray-600 dark:text-gray-400 block mb-1">Recipient Postal Address</label>
                                <textarea name="recipient_address" x-model="form.recipient_address" rows="2" placeholder="Street, City, State, Pin Code..." class="w-full text-xs font-medium p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]"></textarea>
                            </div>

                            <div>
                                <label class="text-[11px] font-bold text-gray-600 dark:text-gray-400 block mb-1">Recipient Email (Optional)</label>
                                <input type="email" name="recipient_email" x-model="form.recipient_email" placeholder="recipient@example.com" class="w-full text-xs font-medium p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                            </div>

                            <div>
                                <label class="text-[11px] font-bold text-gray-600 dark:text-gray-400 block mb-1">Recipient Phone (Optional)</label>
                                <input type="text" name="recipient_phone" x-model="form.recipient_phone" placeholder="+91 9876543210" class="w-full text-xs font-medium p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                            </div>
                        </div>
                    </div>

                    <!-- 3. Subject Line & Rich-Text Letter Body -->
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-3xl border border-slate-200 dark:border-gray-700 shadow-sm space-y-4">
                        <span class="text-xs font-black uppercase text-gray-700 dark:text-gray-200 flex items-center gap-2">
                            <i class="fa-solid fa-align-left text-teal-600"></i> Subject & Letter Content
                        </span>

                        <!-- Subject -->
                        <div>
                            <label class="text-[11px] font-bold text-gray-600 dark:text-gray-400 block mb-1">Letter Subject <span class="text-red-500">*</span></label>
                            <input type="text" name="subject" x-model="form.subject" required placeholder="e.g. Letter of Appointment - District Coordinator" class="w-full text-xs font-bold p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                        </div>

                        <!-- Rich Text Toolbar -->
                        <div class="border border-slate-200 dark:border-gray-700 rounded-2xl overflow-hidden">
                            <div class="bg-slate-100 dark:bg-gray-700/80 p-2 border-b border-slate-200 dark:border-gray-600 flex flex-wrap items-center gap-1.5 text-xs">
                                <!-- Format Buttons -->
                                <button type="button" @click="insertText('**', '**')" class="p-1.5 hover:bg-white dark:hover:bg-gray-600 rounded-lg text-gray-700 dark:text-gray-200 font-black" title="Bold"><i class="fa-solid fa-bold"></i></button>
                                <button type="button" @click="insertText('*', '*')" class="p-1.5 hover:bg-white dark:hover:bg-gray-600 rounded-lg text-gray-700 dark:text-gray-200 italic" title="Italic"><i class="fa-solid fa-italic"></i></button>
                                <button type="button" @click="insertText('__', '__')" class="p-1.5 hover:bg-white dark:hover:bg-gray-600 rounded-lg text-gray-700 dark:text-gray-200 underline" title="Underline"><i class="fa-solid fa-underline"></i></button>
                                <span class="w-px h-4 bg-slate-300 dark:bg-gray-600 mx-1"></span>
                                
                                <button type="button" @click="insertLine('• ')" class="p-1.5 hover:bg-white dark:hover:bg-gray-600 rounded-lg text-gray-700 dark:text-gray-200" title="Bullet List"><i class="fa-solid fa-list-ul"></i></button>
                                <button type="button" @click="insertLine('1. ')" class="p-1.5 hover:bg-white dark:hover:bg-gray-600 rounded-lg text-gray-700 dark:text-gray-200" title="Numbered List"><i class="fa-solid fa-list-ol"></i></button>
                                <span class="w-px h-4 bg-slate-300 dark:bg-gray-600 mx-1"></span>

                                <!-- Quick Placeholder Tokens -->
                                <div class="relative inline-block text-left" x-data="{ openToken: false }">
                                    <button type="button" @click="openToken = !openToken" class="text-[11px] font-bold px-2.5 py-1 bg-white dark:bg-gray-600 text-teal-700 dark:text-teal-300 border border-teal-200 dark:border-teal-700 rounded-lg flex items-center gap-1">
                                        <i class="fa-solid fa-tags text-[10px]"></i> Insert Variable <i class="fa-solid fa-chevron-down text-[9px]"></i>
                                    </button>
                                    <div x-show="openToken" @click.away="openToken = false" x-cloak class="absolute left-0 mt-1 w-44 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-slate-200 dark:border-gray-700 py-1 z-20 text-xs">
                                        <button type="button" @click="insertToken('[Recipient Name]'); openToken=false;" class="w-full text-left px-3 py-1.5 hover:bg-teal-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 font-medium">[Recipient Name]</button>
                                        <button type="button" @click="insertToken('[Date]'); openToken=false;" class="w-full text-left px-3 py-1.5 hover:bg-teal-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 font-medium">[Date]</button>
                                        <button type="button" @click="insertToken('[Ref No]'); openToken=false;" class="w-full text-left px-3 py-1.5 hover:bg-teal-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 font-medium">[Ref No]</button>
                                        <button type="button" @click="insertToken('[Organization]'); openToken=false;" class="w-full text-left px-3 py-1.5 hover:bg-teal-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 font-medium">[Organization]</button>
                                        <button type="button" @click="insertToken('[Designation]'); openToken=false;" class="w-full text-left px-3 py-1.5 hover:bg-teal-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 font-medium">[Designation]</button>
                                    </div>
                                </div>
                            </div>

                            <textarea id="letterContentArea" name="content" x-model="form.content" rows="10" required 
                                      placeholder="Write official letter content here..." 
                                      class="w-full p-4 text-xs font-mono leading-relaxed bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-100 outline-none resize-y"></textarea>
                        </div>
                    </div>

                    <!-- 4. Signatory & Authority Block -->
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-3xl border border-slate-200 dark:border-gray-700 shadow-sm space-y-4">
                        <span class="text-xs font-black uppercase text-gray-700 dark:text-gray-200 flex items-center gap-2">
                            <i class="fa-solid fa-signature text-teal-600"></i> Authorized Signatory & Status
                        </span>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="text-[11px] font-bold text-gray-600 dark:text-gray-400 block mb-1">Signatory Name</label>
                                <input type="text" name="signatory_name" x-model="form.signatory_name" class="w-full text-xs font-bold p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                            </div>

                            <div>
                                <label class="text-[11px] font-bold text-gray-600 dark:text-gray-400 block mb-1">Signatory Designation</label>
                                <input type="text" name="signatory_designation" x-model="form.signatory_designation" class="w-full text-xs font-medium p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                            </div>

                            <div>
                                <label class="text-[11px] font-bold text-gray-600 dark:text-gray-400 block mb-1">Status</label>
                                <select name="status" x-model="form.status" class="w-full text-xs font-bold p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                                    <option value="Generated">Generated (Official)</option>
                                    <option value="Draft">Draft</option>
                                    <option value="Sent">Sent / Dispatched</option>
                                    <option value="Archived">Archived</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- 5. Submission Action Buttons -->
                    <div class="bg-white dark:bg-gray-800 p-4 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm flex flex-wrap items-center justify-between gap-3">
                        <a href="letters.php" class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 font-bold text-xs hover:bg-slate-50 dark:hover:bg-gray-700 transition">
                            Cancel
                        </a>

                        <div class="flex flex-wrap items-center gap-2">
                            <button type="submit" name="save_only" value="1" class="bg-slate-800 hover:bg-slate-900 text-white font-bold py-2.5 px-4 rounded-xl shadow-sm text-xs flex items-center gap-2 transition">
                                <i class="fa-solid fa-floppy-disk"></i> <?php echo $editId ? 'Update Record' : 'Save Record'; ?>
                            </button>

                            <button type="submit" name="save_and_download" value="1" class="bg-[#0F8B8D] hover:bg-[#0c7274] text-white font-bold py-2.5 px-4 rounded-xl shadow-md text-xs flex items-center gap-2 transition transform active:scale-95">
                                <i class="fa-solid fa-file-pdf"></i> <?php echo $editId ? 'Update & Download PDF' : 'Save & Download PDF'; ?>
                            </button>

                            <button type="submit" name="save_and_email" value="1" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-4 rounded-xl shadow-md text-xs flex items-center gap-2 transition transform active:scale-95" title="Save document and dispatch PDF to recipient email">
                                <i class="fa-solid fa-paper-plane"></i> <?php echo $editId ? 'Update & Email' : 'Save & Email'; ?>
                            </button>
                        </div>
                    </div>

                </div>

                <!-- ════════════════════════════════════════════════════════════ -->
                <!-- RIGHT COLUMN: REAL-TIME A4 LETTERHEAD PREVIEW (6 Cols)      -->
                <!-- ════════════════════════════════════════════════════════════ -->
                <div class="xl:col-span-6 sticky top-4">
                    
                    <div class="bg-slate-200/70 dark:bg-gray-800/80 p-4 md:p-6 rounded-3xl border border-slate-300 dark:border-gray-700 shadow-inner">
                        
                        <!-- Top Toolbar of Preview -->
                        <div class="flex items-center justify-between mb-3 text-xs font-bold text-gray-600 dark:text-gray-300">
                            <span class="flex items-center gap-1.5">
                                <i class="fa-solid fa-eye text-[#0F8B8D]"></i> Real-time Letterhead Document Preview
                            </span>
                            <span class="px-2.5 py-0.5 rounded-full bg-teal-100 dark:bg-teal-900/40 text-[#0F8B8D] dark:text-teal-300 text-[10px] font-black">
                                A4 Letterhead Format
                            </span>
                        </div>

                        <!-- ── A4 SIMULATED SHEET ────────────────────────── -->
                        <div id="printableLetterArea" class="bg-white text-gray-800 p-8 md:p-10 rounded-2xl shadow-xl border border-slate-200 space-y-5 min-h-[700px] flex flex-col justify-between relative overflow-hidden">
                            
                            <!-- Top Accent Colored Stripe -->
                            <div class="absolute top-0 left-0 right-0 h-2" :style="'background-color: ' + lh.header_color"></div>

                            <div>
                                <!-- Header Section (Auto Applied from CMS Settings) -->
                                <div class="flex items-start justify-between pb-3 border-b-2" :style="'border-color: ' + lh.header_color">
                                    <div class="flex items-start gap-3">
                                        <template x-if="lh.logo">
                                            <img :src="'../' + lh.logo" class="w-14 h-14 object-contain">
                                        </template>
                                        <template x-if="!lh.logo">
                                            <div class="w-12 h-12 bg-teal-50 text-[#0F8B8D] rounded-xl flex items-center justify-center font-black text-xl">
                                                NGO
                                            </div>
                                        </template>
                                        <div>
                                            <h3 class="text-lg font-black text-gray-900 leading-tight" x-text="lh.org_name"></h3>
                                            <p class="text-[11px] font-bold text-teal-700" x-text="lh.tagline"></p>
                                            <p class="text-[10px] text-gray-500" x-text="lh.reg_no"></p>
                                        </div>
                                    </div>

                                    <div class="text-right text-[10px] text-gray-600 leading-tight max-w-[200px]">
                                        <p class="font-bold"><i class="fa-solid fa-phone text-[9px]"></i> <span x-text="lh.phone"></span></p>
                                        <p class="font-bold"><i class="fa-solid fa-envelope text-[9px]"></i> <span x-text="lh.email"></span></p>
                                        <p class="font-bold"><i class="fa-solid fa-globe text-[9px]"></i> <span x-text="lh.website"></span></p>
                                        <p class="text-[9px] text-gray-500 mt-0.5 whitespace-pre-line" x-text="lh.address"></p>
                                    </div>
                                </div>

                                <!-- Reference Line & Date -->
                                <div class="flex justify-between text-xs font-bold text-gray-700 pt-3">
                                    <span>Ref. No: <strong class="text-gray-900" x-text="form.reference_no || 'JMF/LTR/2026/001'"></strong></span>
                                    <span>Date: <strong class="text-gray-900" x-text="formatDate(form.generated_date)"></strong></span>
                                </div>

                                <!-- Recipient Block -->
                                <div class="text-xs text-gray-800 pt-4 leading-relaxed">
                                    <p class="font-bold text-gray-900">To,</p>
                                    <p class="text-sm font-black text-gray-900" x-text="form.recipient_name || '[Recipient Name]'"></p>
                                    <p x-show="form.recipient_designation" class="font-medium text-gray-700" x-text="form.recipient_designation"></p>
                                    <p x-show="form.recipient_organization" class="font-medium text-gray-600" x-text="form.recipient_organization"></p>
                                    <p x-show="form.recipient_address" class="text-gray-500 text-[11px] whitespace-pre-line" x-text="form.recipient_address"></p>
                                </div>

                                <!-- Subject Line -->
                                <div class="pt-4">
                                    <p class="text-xs font-black" :style="'color: ' + lh.header_color">
                                        Subject: <span class="underline underline-offset-4" x-text="form.subject || '[Enter Letter Subject]'"></span>
                                    </p>
                                </div>

                                <!-- Letter Content Body -->
                                <div class="text-xs text-gray-800 space-y-3 pt-3 leading-relaxed whitespace-pre-line text-justify font-sans" x-text="form.content"></div>
                            </div>

                            <!-- Closing & Signatory Block -->
                            <div class="pt-6">
                                <div class="flex justify-end">
                                    <div class="text-center min-w-[180px]">
                                        <p class="text-xs text-gray-600">Sincerely / Yours Faithfully,</p>
                                        <p class="text-xs font-bold text-gray-900">For <span x-text="lh.org_name"></span></p>

                                        <!-- Signature Image -->
                                        <div class="h-12 flex items-center justify-center my-1">
                                            <template x-if="lh.signature_image">
                                                <img :src="'../' + lh.signature_image" class="h-10 mx-auto object-contain">
                                            </template>
                                            <template x-if="!lh.signature_image">
                                                <span class="text-[10px] text-gray-400 italic">[Authorized Signature]</span>
                                            </template>
                                        </div>

                                        <p class="text-xs font-black text-gray-900" x-text="form.signatory_name || lh.signatory_name"></p>
                                        <p class="text-[10px] text-gray-500 font-medium" x-text="form.signatory_designation || lh.signatory_designation"></p>
                                    </div>
                                </div>

                                <!-- Footer Section (Auto applied from CMS Settings) -->
                                <div class="mt-6 pt-3 border-t text-center text-[10px] text-gray-500 font-medium" x-text="lh.footer_text"></div>
                            </div>

                            <!-- Bottom Accent Stripe -->
                            <div class="absolute bottom-0 left-0 right-0 h-1.5" :style="'background-color: ' + lh.header_color"></div>
                        </div>

                    </div>

                </div>

            </form>

        </main>
    </div>
</div>

<script>
    function letterComposer(config = {}) {
        return {
            form: config.initialData || {},
            lh: config.lhSettings || {},
            isEdit: config.isEdit || false,

            init() {},

            formatDate(d) {
                if (!d) return '-';
                const parts = d.split('-');
                if (parts.length === 3) {
                    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                    const mIdx = parseInt(parts[1], 10) - 1;
                    return `${parts[2]} ${months[mIdx] || parts[1]}, ${parts[0]}`;
                }
                return d;
            },

            insertText(before, after) {
                const textarea = document.getElementById('letterContentArea');
                if (!textarea) return;
                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;
                const text = textarea.value;
                const selected = text.substring(start, end);
                const replacement = before + selected + after;
                this.form.content = text.substring(0, start) + replacement + text.substring(end);
                this.$nextTick(() => {
                    textarea.focus();
                    textarea.setSelectionRange(start + before.length, end + before.length);
                });
            },

            insertLine(prefix) {
                const textarea = document.getElementById('letterContentArea');
                if (!textarea) return;
                const start = textarea.selectionStart;
                const text = textarea.value;
                this.form.content = text.substring(0, start) + "\n" + prefix + text.substring(start);
                this.$nextTick(() => {
                    textarea.focus();
                });
            },

            insertToken(token) {
                const textarea = document.getElementById('letterContentArea');
                if (!textarea) return;
                const start = textarea.selectionStart;
                const text = textarea.value;
                this.form.content = text.substring(0, start) + token + text.substring(start);
                this.$nextTick(() => {
                    textarea.focus();
                });
            },

            applyTemplate(tpl) {
                if (!tpl) return;
                const org = this.lh.org_name || 'Jaysmrutti Foundation';
                const todayFormatted = this.formatDate(this.form.generated_date);

                switch (tpl) {
                    case 'appointment':
                        this.form.letter_type = 'Appointment Letter';
                        this.form.subject = 'Official Appointment Letter';
                        this.form.content = `We are pleased to appoint you as ${this.form.recipient_designation || 'Senior Outreach Coordinator'} at ${org} with effect from ${todayFormatted}.\n\nIn this capacity, you will be responsible for leading our community outreach programs, organizing health & nutrition camps, and coordinating with regional volunteer groups.\n\nWe trust that your dedication, skills, and enthusiasm will greatly contribute to the foundation's social welfare mission.\n\nPlease sign and return the duplicate copy of this letter as acceptance of this appointment.`;
                        break;

                    case 'appreciation':
                        this.form.letter_type = 'Appreciation Letter';
                        this.form.subject = 'Certificate & Letter of Sincere Appreciation';
                        this.form.content = `On behalf of ${org}, we express our heartfelt gratitude and appreciation for your exemplary service and outstanding dedication towards our social welfare initiatives.\n\nYour relentless commitment during our recent community relief and education drives has made a meaningful and lasting impact on the lives of many underprivileged families.\n\nWe commend your passion, hard work, and philanthropic spirit, and look forward to your continued association with our mission.`;
                        break;

                    case 'experience':
                        this.form.letter_type = 'Experience Certificate';
                        this.form.subject = 'Experience Certificate & Service Verification';
                        this.form.content = `This is to certify that ${this.form.recipient_name} was associated with ${org} as ${this.form.recipient_designation || 'Field Coordinator'} from January 2024 to ${todayFormatted}.\n\nDuring their tenure with us, they demonstrated high professionalism, strong organizational capabilities, and deep empathy for community welfare activities.\n\nTheir conduct and performance during the entire association were found to be exemplary. We wish them all the very best in all future endeavors.`;
                        break;

                    case 'csr_request':
                        this.form.letter_type = 'Donation / CSR Request';
                        this.form.subject = 'Proposal for CSR Partnership & Community Health Sponsorship';
                        this.form.content = `Greetings from ${org}.\n\nWe are writing to submit our formal proposal for collaboration under your esteemed organization's Corporate Social Responsibility (CSR) program.\n\nOur foundation is actively executing grassroots initiatives in rural healthcare, nutrition support for malnourished children, and skill development for youth. Through your support, we aim to impact over 5,000 beneficiaries across underserved districts.\n\nAll contributions to ${org} are eligible for 50% tax deduction under Section 80G of the Income Tax Act. We would be privileged to present our detailed project blueprint at your convenience.`;
                        break;

                    case 'recommendation':
                        this.form.letter_type = 'Recommendation Letter';
                        this.form.subject = 'Official Letter of Recommendation';
                        this.form.content = `It gives me immense pleasure to write this letter of recommendation for ${this.form.recipient_name}, who has been an integral contributor to ${org}.\n\nThroughout our association, ${this.form.recipient_name} has consistently exhibited exceptional problem-solving abilities, integrity, and proactive leadership in managing social impact initiatives.\n\nI strongly recommend ${this.form.recipient_name} for any role, fellowship, or institutional opportunity and believe they will prove to be an invaluable asset.`;
                        break;

                    case 'relieving':
                        this.form.letter_type = 'Relieving Letter';
                        this.form.subject = 'Official Relieving Letter & Service Clearance';
                        this.form.content = `This is with reference to your resignation letter. We hereby confirm that you are relieved from your duties and responsibilities as ${this.form.recipient_designation || 'Staff Coordinator'} at ${org} with effect from the close of business hours on ${todayFormatted}.\n\nAll institutional clearances, documentation handovers, and full-and-final settlements have been successfully completed.\n\nWe thank you for your sincere contributions during your service with us and wish you the greatest success in your future career.`;
                        break;

                    case 'inquiry_notice':
                        this.form.letter_type = 'Official Notice';
                        this.form.subject = 'Formal Communication & Advisory Notice';
                        this.form.content = `This is an official communication regarding institutional protocols and compliance guidelines established by ${org}.\n\nYou are requested to provide the updated status report and reconciliation documentation within 7 working days from the receipt of this notice.\n\nFor any clarifications or assistance, please contact the administrative headquarters at ${this.lh.phone} or via email at ${this.lh.email}.`;
                        break;

                    case 'general':
                        this.form.letter_type = 'General Official Letter';
                        this.form.subject = 'Official Communication from ' + org;
                        this.form.content = `We are writing to officially communicate regarding our ongoing programs and community welfare initiatives.\n\n${org} continues to remain committed towards empowering underprivileged sections of society and fostering transparent institutional partnerships.\n\nThank you for your ongoing cooperation and support.`;
                        break;
                }
            },

            printPreview() {
                window.print();
            }
        };
    }
</script>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    #printableLetterArea, #printableLetterArea * {
        visibility: visible;
    }
    #printableLetterArea {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        margin: 0;
        padding: 20mm;
        box-shadow: none;
        border: none;
    }
}
</style>

<?php require 'includes/footer.php'; ?>
