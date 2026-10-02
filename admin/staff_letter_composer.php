<?php
// ============================================================
// admin/staff_letter_composer.php
// Staff, Employee & Volunteer Letter Composer with Live A4 Preview
// Reuses Module 19 Letterhead Engine with pre-built template presets
// ============================================================

require 'includes/header.php';
require '../config/db.php';
require_once '../includes/functions.php';
require_once '../includes/member_module.php';
require_once '../includes/letterhead_helper.php';
require_once '../includes/staff_letter_helper.php';

if (!canAccessModule($pdo, 'coordinator', 'page.letters')) {
    setFlash('error', 'Access denied. You do not have permission to compose staff letters.');
    header('Location: dashboard.php');
    exit;
}

if (!function_exists('clean_letter_content_for_editor')) {
    function clean_letter_content_for_editor($content): string {
        if (empty($content)) return '';
        $text = str_replace(
            ['<br>', '<br/>', '<br />', '</p>', '</div>', '</h1>', '</h2>', '</h3>', '</h4>', '</li>'],
            ["\n", "\n", "\n", "\n\n", "\n", "\n", "\n", "\n", "\n", "\n"],
            $content
        );
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/\n{3,}/", "\n\n", trim($text));
        return $text;
    }
}

$csrfToken = generateCsrfToken();
$lhSettings = get_letterhead_settings($pdo);
$settings = mm_load_settings($pdo);
$orgName = $settings['site_name'] ?? 'NGO Organization';
$templates = get_staff_letter_templates($orgName);

$editId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$presetType = cleanInput($_GET['type'] ?? 'offer');
if (!isset($templates[$presetType])) {
    $presetType = 'offer';
}

$letter = null;
if ($editId) {
    $stmt = $pdo->prepare("SELECT * FROM staff_letters WHERE id = ?");
    $stmt->execute([$editId]);
    $letter = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$letter) {
        setFlash('error', 'Letter record not found.');
        header('Location: staff_letters.php');
        exit;
    }
    $presetType = $letter['type'];
}

// Generate dynamic acronym and sequential reference number if new
$currentYear = date('Y');
$words = preg_split('/[\s\-_]+/', trim($orgName));
$orgAcronym = '';
foreach ($words as $w) {
    if (!empty($w)) {
        $orgAcronym .= strtoupper($w[0]);
    }
}
if (strlen($orgAcronym) < 2) {
    $orgAcronym = 'NGO';
}

$prefixMap = [
    'offer' => "{$orgAcronym}/OFF/{$currentYear}/",
    'appointment' => "{$orgAcronym}/APP/{$currentYear}/",
    'joining' => "{$orgAcronym}/JOIN/{$currentYear}/",
    'volunteer_joining' => "{$orgAcronym}/VOL/{$currentYear}/",
    'experience' => "{$orgAcronym}/EXP/{$currentYear}/",
    'relieving' => "{$orgAcronym}/REL/{$currentYear}/",
    'appreciation' => "{$orgAcronym}/APR/{$currentYear}/"
];
$prefix = $prefixMap[$presetType] ?? "{$orgAcronym}/STF/{$currentYear}/";
$nextSeq = (int)($pdo->query("SELECT COUNT(*) FROM staff_letters WHERE YEAR(issued_date) = {$currentYear}")->fetchColumn() ?: 0) + 1;
$defaultRefNo = $prefix . str_pad((string)$nextSeq, 3, '0', STR_PAD_LEFT);

// Designation list for quick dropdown / autocomplete
$designationOptions = [];
try {
    $designationOptions = $pdo->query("SELECT DISTINCT title FROM member_designations WHERE is_active = 1 ORDER BY title ASC")->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {
    $designationOptions = ['State Program Coordinator', 'District Operations Lead', 'Block Field Officer', 'Community Health Volunteer', 'Senior Accounts Officer'];
}

$activeTpl = $templates[$presetType] ?? $templates['offer'];

$rawContent = $letter['letter_content'] ?? str_replace(
    ['[Recipient Name]', '[Designation]', '[Department]', '[Joining Date]', '[Salary]', '[Org Name]', '[Issued Date]', '[Contact]', '[Email]'],
    ['Prakash Chand Srivastava', 'District Operations Lead', 'Healthcare & Community Wing', date('d M Y', strtotime('+7 days')), '₹30,000 / month', $orgName, date('d M Y'), '+91 9876543210', 'prakash.srivastava@example.com'],
    $activeTpl['default_content']
);

$formData = [
    'id' => $letter['id'] ?? 0,
    'type' => $letter['type'] ?? $presetType,
    'letter_no' => $letter['letter_no'] ?? $defaultRefNo,
    'name' => $letter['name'] ?? 'Prakash Chand Srivastava',
    'contact' => $letter['contact'] ?? '+91 9876543210',
    'email' => $letter['email'] ?? 'prakash.srivastava@example.com',
    'designation' => $letter['designation'] ?? 'District Operations Lead',
    'department' => $letter['department'] ?? 'Healthcare & Community Wing',
    'issued_date' => $letter['issued_date'] ?? date('Y-m-d'),
    'joining_date' => $letter['joining_date'] ?? date('Y-m-d', strtotime('+7 days')),
    'salary_or_stipend' => $letter['salary_or_stipend'] ?? '₹30,000 / month',
    'subject' => strip_tags(html_entity_decode((string)($letter['subject'] ?? str_replace(['[Designation]', '[Recipient Name]'], ['District Operations Lead', 'Prakash Chand Srivastava'], $activeTpl['subject'])), ENT_QUOTES | ENT_HTML5, 'UTF-8')),
    'letter_content' => clean_letter_content_for_editor($rawContent),
    'signatory_name' => $letter['signatory_name'] ?? ($lhSettings['signatory_name'] ?? 'Authorized Signatory'),
    'signatory_designation' => $letter['signatory_designation'] ?? ($lhSettings['signatory_designation'] ?? 'General Secretary / Director'),
    'status' => $letter['status'] ?? 'issued'
];
?>

<!-- Safe JSON Configuration payload for Alpine.js -->
<script id="staff-letter-config-data" type="application/json">
<?php echo json_encode([
    'initialData' => $formData,
    'templates' => $templates,
    'lhSettings' => $lhSettings,
    'orgName' => $orgName,
    'orgAcronym' => $orgAcronym,
    'isEdit' => (bool)$editId
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>
</script>

<div class="flex h-screen overflow-hidden bg-slate-50 dark:bg-gray-900"
     x-data="staffLetterComposer(JSON.parse(document.getElementById('staff-letter-config-data').textContent))"
     x-init="init()">

    <?php require 'includes/sidebar.php'; ?>

    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300 min-w-0">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6 lg:p-8">

            <!-- Top Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div>
                    <a href="staff_letters.php" class="text-xs font-bold text-teal-600 dark:text-teal-400 hover:underline mb-1 inline-flex items-center gap-1">
                        <i class="fa-solid fa-arrow-left"></i> Back to Staff Letters Roster
                    </a>
                    <h3 class="text-2xl md:text-3xl font-black text-gray-800 dark:text-white flex items-center gap-3">
                        <span class="w-10 h-10 rounded-2xl bg-teal-50 dark:bg-teal-900/40 text-[#0F8B8D] flex items-center justify-center text-lg shadow-sm">
                            <i class="fa-solid fa-envelope-open-text"></i>
                        </span>
                        <?php echo $editId ? 'Edit Staff Letter' : 'Staff & Volunteer Letter Generator'; ?>
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Compose official Offer, Appointment, Joining, Volunteer, Experience, and Relieving Letters with real-time A4 Letterhead preview & PDF generation.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <a href="settings.php?tab=letterhead" target="_blank" class="bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 hover:bg-slate-50 dark:hover:bg-gray-700 font-bold px-3.5 py-2 rounded-xl text-xs flex items-center gap-1.5 transition shadow-sm">
                        <i class="fa-solid fa-gear text-teal-600"></i> Letterhead Branding
                    </a>
                    <?php if ($editId): ?>
                        <a href="actions/staff_letter_logic.php?action=download_letter&id=<?php echo (int)$editId; ?>" class="bg-rose-600 hover:bg-rose-700 text-white font-bold px-3.5 py-2 rounded-xl text-xs flex items-center gap-1.5 shadow-sm transition">
                            <i class="fa-solid fa-file-pdf"></i> Download PDF
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Template Switcher Pills -->
            <div class="bg-white dark:bg-gray-800 p-3 rounded-2xl border border-slate-200 dark:border-gray-700 shadow-sm mb-6 flex items-center gap-2 overflow-x-auto">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider pl-2 shrink-0">Template Presets:</span>
                
                <button type="button" 
                        @click="switchTemplate('offer')"
                        :class="form.type === 'offer' ? 'bg-[#0F8B8D] text-white shadow-md' : 'bg-slate-50 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-slate-100'"
                        class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shrink-0">
                    <i class="fa-solid fa-file-lines"></i> Offer Letter
                </button>

                <button type="button" 
                        @click="switchTemplate('appointment')"
                        :class="form.type === 'appointment' ? 'bg-[#0F8B8D] text-white shadow-md' : 'bg-slate-50 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-slate-100'"
                        class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shrink-0">
                    <i class="fa-solid fa-file-signature"></i> Appointment Letter
                </button>

                <button type="button" 
                        @click="switchTemplate('joining')"
                        :class="form.type === 'joining' ? 'bg-[#0F8B8D] text-white shadow-md' : 'bg-slate-50 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-slate-100'"
                        class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shrink-0">
                    <i class="fa-solid fa-user-check"></i> Joining & Welcome
                </button>

                <button type="button" 
                        @click="switchTemplate('volunteer_joining')"
                        :class="form.type === 'volunteer_joining' ? 'bg-[#0F8B8D] text-white shadow-md' : 'bg-slate-50 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-slate-100'"
                        class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shrink-0">
                    <i class="fa-solid fa-hand-holding-heart"></i> Volunteer Joining
                </button>

                <button type="button" 
                        @click="switchTemplate('experience')"
                        :class="form.type === 'experience' ? 'bg-[#0F8B8D] text-white shadow-md' : 'bg-slate-50 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-slate-100'"
                        class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shrink-0">
                    <i class="fa-solid fa-briefcase"></i> Experience Letter
                </button>

                <button type="button" 
                        @click="switchTemplate('relieving')"
                        :class="form.type === 'relieving' ? 'bg-[#0F8B8D] text-white shadow-md' : 'bg-slate-50 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-slate-100'"
                        class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shrink-0">
                    <i class="fa-solid fa-door-open"></i> Relieving Letter
                </button>

                <button type="button" 
                        @click="switchTemplate('appreciation')"
                        :class="form.type === 'appreciation' ? 'bg-[#0F8B8D] text-white shadow-md' : 'bg-slate-50 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-slate-100'"
                        class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shrink-0">
                    <i class="fa-solid fa-award"></i> Appreciation Letter
                </button>
            </div>

            <!-- Split Screen Container (Composer Left, Live A4 Preview Right) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

                <!-- ════════════════════════════════════════════════════ -->
                <!-- LEFT: COMPOSER FORM (6 Cols)                          -->
                <!-- ════════════════════════════════════════════════════ -->
                <div class="lg:col-span-6 bg-white dark:bg-gray-800 rounded-3xl border border-slate-200 dark:border-gray-700 shadow-sm p-5 sm:p-7 space-y-5">
                    
                    <form action="actions/staff_letter_logic.php" method="POST" class="space-y-5">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="action" value="save_letter">
                        <input type="hidden" name="id" :value="form.id">
                        <input type="hidden" name="type" :value="form.type">

                        <!-- Letter Ref & Status -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">
                                    Letter Reference No. <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" name="letter_no" x-model="form.letter_no" required
                                       class="w-full px-3.5 py-2.5 rounded-2xl bg-slate-50 dark:bg-gray-700 border border-slate-200 dark:border-gray-600 text-xs sm:text-sm font-mono font-bold text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">
                                    Status
                                </label>
                                <select name="status" x-model="form.status" class="w-full px-3.5 py-2.5 rounded-2xl bg-slate-50 dark:bg-gray-700 border border-slate-200 dark:border-gray-600 text-xs sm:text-sm font-bold text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                                    <option value="draft">Draft</option>
                                    <option value="issued">Issued / Official</option>
                                    <option value="accepted">Accepted by Candidate</option>
                                    <option value="signed">Signed & Archived</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>

                        <!-- Recipient Name, Contact & Email -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div class="sm:col-span-1">
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">
                                    Recipient Name <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" name="name" x-model="form.name" required
                                       placeholder="e.g. Ramesh Chandra"
                                       class="w-full px-3 py-2 rounded-2xl bg-slate-50 dark:bg-gray-700 border border-slate-200 dark:border-gray-600 text-xs sm:text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                            </div>

                            <div class="sm:col-span-1">
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">
                                    Phone / Mobile
                                </label>
                                <input type="text" name="contact" x-model="form.contact"
                                       placeholder="+91 9876543210"
                                       class="w-full px-3 py-2 rounded-2xl bg-slate-50 dark:bg-gray-700 border border-slate-200 dark:border-gray-600 text-xs sm:text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                            </div>

                            <div class="sm:col-span-1">
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">
                                    Email Address
                                </label>
                                <input type="email" name="email" x-model="form.email"
                                       placeholder="name@example.com"
                                       class="w-full px-3 py-2 rounded-2xl bg-slate-50 dark:bg-gray-700 border border-slate-200 dark:border-gray-600 text-xs sm:text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                            </div>
                        </div>

                        <!-- Designation & Department -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">
                                    Designation / Role <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" name="designation" x-model="form.designation" required list="designationList"
                                       placeholder="e.g. State Program Coordinator"
                                       class="w-full px-3.5 py-2.5 rounded-2xl bg-slate-50 dark:bg-gray-700 border border-slate-200 dark:border-gray-600 text-xs sm:text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                                <datalist id="designationList">
                                    <?php foreach ($designationOptions as $opt): ?>
                                        <option value="<?php echo htmlspecialchars($opt); ?>"></option>
                                    <?php endforeach; ?>
                                </datalist>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">
                                    Department / Wing
                                </label>
                                <input type="text" name="department" x-model="form.department"
                                       placeholder="e.g. Healthcare Wing, Field Ops"
                                       class="w-full px-3.5 py-2.5 rounded-2xl bg-slate-50 dark:bg-gray-700 border border-slate-200 dark:border-gray-600 text-xs sm:text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                            </div>
                        </div>

                        <!-- Dates & Salary -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">
                                    Issued Date
                                </label>
                                <input type="date" name="issued_date" x-model="form.issued_date" required
                                       class="w-full px-3 py-2 rounded-2xl bg-slate-50 dark:bg-gray-700 border border-slate-200 dark:border-gray-600 text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                            </div>

                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">
                                    Joining / Start Date
                                </label>
                                <input type="date" name="joining_date" x-model="form.joining_date"
                                       class="w-full px-3 py-2 rounded-2xl bg-slate-50 dark:bg-gray-700 border border-slate-200 dark:border-gray-600 text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                            </div>

                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">
                                    Salary / Stipend
                                </label>
                                <input type="text" name="salary_or_stipend" x-model="form.salary_or_stipend"
                                       placeholder="₹25,000 / mo"
                                       class="w-full px-3 py-2 rounded-2xl bg-slate-50 dark:bg-gray-700 border border-slate-200 dark:border-gray-600 text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                            </div>
                        </div>

                        <!-- Subject -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">
                                Letter Subject <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="subject" x-model="form.subject" required
                                   class="w-full px-3.5 py-2.5 rounded-2xl bg-slate-50 dark:bg-gray-700 border border-slate-200 dark:border-gray-600 text-xs sm:text-sm font-semibold text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                        </div>

                        <!-- Letter Body Content -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                    Letter Body Content <span class="text-rose-500">*</span>
                                </label>
                                <button type="button" @click="insertPlaceholders()" class="text-[11px] font-bold text-teal-600 hover:underline inline-flex items-center gap-1">
                                    <i class="fa-solid fa-wand-magic-sparkles"></i> Apply Placeholders
                                </button>
                            </div>
                            <textarea name="letter_content" x-model="form.letter_content" required rows="10"
                                      class="w-full p-3.5 rounded-2xl bg-slate-50 dark:bg-gray-700 border border-slate-200 dark:border-gray-600 text-xs sm:text-sm text-gray-900 dark:text-white font-mono leading-relaxed focus:outline-none focus:ring-2 focus:ring-[#0F8B8D]"></textarea>
                        </div>

                        <!-- Signatory Details -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-2xl bg-slate-50 dark:bg-gray-700/40 border border-slate-200 dark:border-gray-600 text-xs">
                            <div>
                                <label class="block font-bold text-gray-500 mb-1">Signatory Name</label>
                                <input type="text" name="signatory_name" x-model="form.signatory_name"
                                       class="w-full px-3 py-1.5 rounded-xl bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 text-xs text-gray-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block font-bold text-gray-500 mb-1">Signatory Title</label>
                                <input type="text" name="signatory_designation" x-model="form.signatory_designation"
                                       class="w-full px-3 py-1.5 rounded-xl bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 text-xs text-gray-900 dark:text-white">
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex flex-col sm:flex-row items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-gray-700">
                            <button type="submit" name="save_and_download" value="1"
                                    class="w-full sm:w-auto px-5 py-3 rounded-2xl bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs shadow-md transition flex items-center justify-center gap-2">
                                <i class="fa-solid fa-file-pdf"></i> Save & Download PDF
                            </button>

                            <button type="submit"
                                    class="w-full sm:w-auto px-6 py-3 rounded-2xl bg-[#0F8B8D] hover:bg-[#0c7274] text-white font-bold text-xs shadow-md transition flex items-center justify-center gap-2">
                                <i class="fa-solid fa-floppy-disk"></i> Save & Generate Letter
                            </button>
                        </div>
                    </form>

                </div>


                <!-- ════════════════════════════════════════════════════ -->
                <!-- RIGHT: LIVE A4 LETTERHEAD PREVIEW (6 Cols)            -->
                <!-- ════════════════════════════════════════════════════ -->
                <div class="lg:col-span-6 sticky top-6">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-widest flex items-center gap-1.5">
                            <i class="fa-solid fa-eye text-[#0F8B8D]"></i> Real-time Letterhead Preview (A4)
                        </span>
                        <span class="text-[11px] font-semibold text-gray-500">Live Render</span>
                    </div>

                    <!-- Simulated Paper Box -->
                    <div class="bg-white text-slate-800 rounded-2xl shadow-2xl border border-slate-200 p-8 sm:p-10 font-sans text-xs min-h-[650px] relative overflow-hidden flex flex-col justify-between"
                         style="box-shadow: 0 10px 30px rgba(0,0,0,0.08);">
                        
                        <!-- Top Accent Bar -->
                        <div class="absolute top-0 left-0 right-0 h-1.5 bg-[#0F8B8D]"></div>

                        <!-- Letterhead Top Branding Header -->
                        <div>
                            <div class="flex items-start justify-between pb-3 border-b border-[#0F8B8D]/40">
                                <div class="flex items-center gap-3">
                                    <?php if (!empty($lhSettings['logo']) && file_exists(__DIR__ . '/../' . ltrim($lhSettings['logo'], '/'))): ?>
                                        <img src="../<?php echo htmlspecialchars(ltrim($lhSettings['logo'], '/')); ?>" class="h-12 w-12 object-contain rounded-lg">
                                    <?php endif; ?>
                                    <div>
                                        <h2 class="text-base sm:text-lg font-black text-slate-900 tracking-tight leading-tight">
                                            <?php echo htmlspecialchars($lhSettings['org_name']); ?>
                                        </h2>
                                        <p class="text-[10px] font-bold text-[#0F8B8D]">
                                            <?php echo htmlspecialchars($lhSettings['tagline']); ?>
                                        </p>
                                        <p class="text-[9px] text-gray-500">
                                            <?php echo htmlspecialchars($lhSettings['reg_no']); ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="text-right text-[8.5px] text-gray-500 leading-tight">
                                    <div><?php echo htmlspecialchars($lhSettings['phone']); ?></div>
                                    <div><?php echo htmlspecialchars($lhSettings['email']); ?></div>
                                    <div><?php echo htmlspecialchars($lhSettings['website']); ?></div>
                                    <div class="max-w-[150px] truncate"><?php echo htmlspecialchars($lhSettings['address']); ?></div>
                                </div>
                            </div>

                            <!-- Ref No & Date -->
                            <div class="flex items-center justify-between py-3 text-[10.5px] border-b border-gray-100">
                                <span class="font-mono font-bold text-slate-800">Ref: <span x-text="form.letter_no"></span></span>
                                <span class="text-gray-600">Date: <strong x-text="formatDisplayDate(form.issued_date)"></strong></span>
                            </div>

                            <!-- Recipient Block -->
                            <div class="py-3 text-[10.5px] space-y-0.5">
                                <div class="font-bold text-gray-500">To,</div>
                                <div class="text-sm font-black text-slate-900" x-text="form.name || 'Recipient Full Name'"></div>
                                <div class="text-gray-700" x-show="form.designation"><span class="font-semibold">Designation:</span> <span x-text="form.designation"></span></div>
                                <div class="text-gray-700" x-show="form.department"><span class="font-semibold">Department:</span> <span x-text="form.department"></span></div>
                                <div class="text-gray-600" x-show="form.contact"><span class="font-semibold">Contact:</span> <span x-text="form.contact"></span></div>
                                <div class="text-gray-500" x-show="form.email"><span class="font-semibold">Email:</span> <span x-text="form.email"></span></div>
                            </div>

                            <!-- Subject -->
                            <div class="py-2 text-[11px] font-black text-[#0F8B8D]">
                                <span>Subject: </span><span x-text="form.subject"></span>
                            </div>

                            <!-- Body Paragraphs -->
                            <div class="py-2 text-[10.5px] text-slate-700 leading-relaxed whitespace-pre-line text-justify"
                                 x-text="form.letter_content"></div>
                        </div>

                        <!-- Signatory & Bottom Footer -->
                        <div class="pt-6">
                            <div class="flex justify-end">
                                <div class="text-right text-[10px] space-y-1">
                                    <div class="text-gray-500">Sincerely / Yours Faithfully,</div>
                                    <div class="font-bold text-slate-900"><?php echo htmlspecialchars($lhSettings['org_name']); ?></div>

                                    <?php if (!empty($lhSettings['signature_image']) && file_exists(__DIR__ . '/../' . ltrim($lhSettings['signature_image'], '/'))): ?>
                                        <div class="flex justify-end py-1">
                                            <img src="../<?php echo htmlspecialchars(ltrim($lhSettings['signature_image'], '/')); ?>" class="h-9 object-contain">
                                        </div>
                                    <?php else: ?>
                                        <div class="h-6"></div>
                                    <?php endif; ?>

                                    <div class="font-black text-slate-900" x-text="form.signatory_name"></div>
                                    <div class="text-gray-500 text-[9px]" x-text="form.signatory_designation"></div>
                                </div>
                            </div>

                            <!-- Footer Strip -->
                            <div class="mt-6 pt-2 border-t border-[#0F8B8D]/30 text-center text-[8.5px] text-gray-400 italic">
                                <?php echo htmlspecialchars($lhSettings['footer_text']); ?>
                            </div>
                        </div>

                    </div>
                </div>

            </div>

        </main>
    </div>
</div>

<script>
function staffLetterComposer(config) {
    return {
        form: config.initialData,
        templates: config.templates,
        lhSettings: config.lhSettings,
        orgName: config.orgName,
        orgAcronym: config.orgAcronym || 'NGO',

        init() {
            // Initialized
        },

        switchTemplate(tplKey) {
            if (!this.templates[tplKey]) return;
            this.form.type = tplKey;
            
            const tpl = this.templates[tplKey];
            const currentYear = new Date().getFullYear();
            const prefixMap = {
                'offer': `${this.orgAcronym}/OFF/${currentYear}/`,
                'appointment': `${this.orgAcronym}/APP/${currentYear}/`,
                'joining': `${this.orgAcronym}/JOIN/${currentYear}/`,
                'volunteer_joining': `${this.orgAcronym}/VOL/${currentYear}/`,
                'experience': `${this.orgAcronym}/EXP/${currentYear}/`,
                'relieving': `${this.orgAcronym}/REL/${currentYear}/`,
                'appreciation': `${this.orgAcronym}/APR/${currentYear}/`
            };
            const prefix = prefixMap[tplKey] || `${this.orgAcronym}/STF/${currentYear}/`;
            const randomCode = String(Math.floor(Math.random() * 900) + 100);
            this.form.letter_no = prefix + randomCode;

            // Replace placeholders in subject and content
            this.form.subject = this.applyReplacements(tpl.subject);
            this.form.letter_content = this.applyReplacements(tpl.default_content);
        },

        applyReplacements(text) {
            if (!text) return '';
            const issuedFormatted = this.formatDisplayDate(this.form.issued_date);
            const joiningFormatted = this.formatDisplayDate(this.form.joining_date);
            const nameVal = this.form.name || 'Candidate Name';
            const desigVal = this.form.designation || 'Assigned Designation';
            const deptVal = this.form.department || 'General Wing';
            const salaryVal = this.form.salary_or_stipend || 'As agreed';
            const contactVal = this.form.contact || '';
            const emailVal = this.form.email || '';

            return text
                .replaceAll('[Recipient Name]', nameVal)
                .replaceAll('[Recipient]', nameVal)
                .replaceAll('[Name]', nameVal)
                .replaceAll('[Designation / Wing]', desigVal)
                .replaceAll('[Designation]', desigVal)
                .replaceAll('[Role]', desigVal)
                .replaceAll('[Department]', deptVal)
                .replaceAll('[Joining Date]', joiningFormatted)
                .replaceAll('[Start Date]', joiningFormatted)
                .replaceAll('[Salary]', salaryVal)
                .replaceAll('[Org Name]', this.orgName)
                .replaceAll('[Issued Date]', issuedFormatted)
                .replaceAll('[Date]', issuedFormatted)
                .replaceAll('[Contact]', contactVal)
                .replaceAll('[Email]', emailVal);
        },

        insertPlaceholders() {
            if (this.templates[this.form.type]) {
                const tpl = this.templates[this.form.type];
                this.form.subject = this.applyReplacements(tpl.subject);
                this.form.letter_content = this.applyReplacements(tpl.default_content);
            }
        },

        formatDisplayDate(dateStr) {
            if (!dateStr) return 'N/A';
            const parts = String(dateStr).split('-');
            if (parts.length === 3) {
                const year = parseInt(parts[0], 10);
                const month = parseInt(parts[1], 10) - 1;
                const day = parseInt(parts[2], 10);
                const d = new Date(year, month, day);
                return d.toLocaleDateString('en-US', { day: '2-digit', month: 'short', year: 'numeric' });
            }
            return dateStr;
        }
    }
}
</script>

<?php require 'includes/footer.php'; ?>
