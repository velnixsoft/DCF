<?php
// ============================================================
// view-agreement.php
// Partner MoU / Agreement Viewer & Digital Acknowledgment Portal
// Author: VELNIX SOFT / Antigravity AI
// Date: 2026-09-12
// ============================================================

require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/letterhead_helper.php';
require_once 'includes/agreement_helper.php';

$agreementId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$token = cleanInput($_GET['token'] ?? '');

if (!$agreementId) {
    setFlash('error', 'Invalid agreement ID specified.');
    header('Location: member-dashboard.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM agreements WHERE id = ? LIMIT 1");
$stmt->execute([$agreementId]);
$agr = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$agr) {
    setFlash('error', 'Agreement / MoU document not found.');
    header('Location: member-dashboard.php');
    exit;
}

$lhSettings = get_letterhead_settings($pdo);
$settings = mm_load_settings($pdo);
$csrfToken = generateCsrfToken();
$types = get_agreement_types();
$typeMeta = $types[$agr['type']] ?? $types['mou'];

// Check Authorization: Logged in member, matching token, or admin
$isAuthorized = false;
$loggedInMember = null;

if (!empty($_SESSION['member_logged_in']) && !empty($_SESSION['member_id'])) {
    $mStmt = $pdo->prepare("SELECT * FROM members WHERE id = ? LIMIT 1");
    $mStmt->execute([(int)$_SESSION['member_id']]);
    $loggedInMember = $mStmt->fetch(PDO::FETCH_ASSOC);

    if ($loggedInMember) {
        if ($agr['partner_member_id'] == $loggedInMember['id'] || 
            $agr['partner_email'] === $loggedInMember['email'] || 
            ($agr['partner_contact'] && $agr['partner_contact'] === $loggedInMember['phone']) ||
            stripos($agr['partner_name'], $loggedInMember['full_name']) !== false
        ) {
            $isAuthorized = true;
        }
    }
}

if (!empty($token) && !empty($agr['acknowledgment_token']) && hash_equals($agr['acknowledgment_token'], $token)) {
    $isAuthorized = true;
}

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $isAuthorized = true;
}

// For open preview if no restrictive session is set
if (!$isAuthorized && empty($_SESSION['member_logged_in'])) {
    // If not logged in and no token, redirect to member login
    setFlash('error', 'Please log in with your partner credentials or use your secure MoU access link.');
    header('Location: membership/member-login.php');
    exit;
}

// Prepare formatted clauses text
$renderedContent = replace_agreement_placeholders($agr['content'], $agr, $lhSettings);
$paragraphs = preg_split('/\r\n\r\n|\n\n/', $renderedContent);

require 'includes/header.php';
?>

<div class="bg-gradient-to-b from-slate-100 via-white to-slate-50 min-h-screen py-8 md:py-12"
     x-data="partnerAgreementViewer({
         agreementId: <?php echo (int)$agr['id']; ?>,
         token: <?php echo json_encode($token); ?>,
         isAcknowledged: <?php echo (int)($agr['is_acknowledged'] ?? 0); ?>,
         acknowledgedAt: <?php echo json_encode(!empty($agr['acknowledged_at']) ? date('d M Y, h:i A', strtotime($agr['acknowledged_at'])) : ''); ?>,
         signatoryName: <?php echo json_encode($agr['acknowledged_name'] ?: ($agr['second_party_name'] ?: ($loggedInMember['full_name'] ?? 'Authorized Representative'))); ?>,
         signatoryDesig: <?php echo json_encode($agr['second_party_designation'] ?: 'Director / Authorized Signatory'); ?>,
         clientIp: <?php echo json_encode($agr['acknowledged_ip'] ?? ''); ?>
     })"
     x-cloak>

    <div class="container mx-auto px-4 max-w-5xl">
        
        <!-- Top Navigation Bar & Action Header -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-200/80 p-5 mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="<?php echo !empty($_SESSION['member_logged_in']) ? 'member-dashboard.php' : 'index.php'; ?>" class="p-2.5 rounded-2xl bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors" title="Back to Dashboard">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-lg text-xs font-bold uppercase tracking-wider <?php echo $typeMeta['badge_class']; ?>">
                            <i class="fa-solid <?php echo $typeMeta['icon']; ?> mr-1"></i> <?php echo htmlspecialchars($typeMeta['short_title']); ?>
                        </span>
                        <span class="font-mono text-xs font-bold text-gray-500"><?php echo htmlspecialchars($agr['agreement_no']); ?></span>
                    </div>
                    <h1 class="text-lg sm:text-xl font-black text-gray-900 mt-1">
                        <?php echo htmlspecialchars($agr['title']); ?>
                    </h1>
                </div>
            </div>

            <!-- Header Action Controls -->
            <div class="flex flex-wrap items-center gap-2.5">
                <template x-if="acknowledged">
                    <span class="px-3.5 py-2 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-bold flex items-center gap-1.5 shadow-xs">
                        <i class="fa-solid fa-circle-check text-emerald-600"></i> Digitally Acknowledged
                    </span>
                </template>

                <a href="<?php echo !empty($agr['pdf_path']) ? htmlspecialchars($agr['pdf_path']) : 'admin/actions/agreement_logic.php?action=view_agreement&id=' . $agr['id']; ?>" target="_blank" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-all shadow-md shadow-indigo-100 flex items-center gap-2">
                    <i class="fa-solid fa-file-pdf"></i>
                    <span>Download Official PDF</span>
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
            
            <!-- LEFT 2 COLS: Simulated Official Letterhead Paper View -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-xl border border-gray-200 p-8 sm:p-12 relative overflow-hidden text-slate-800">
                    
                    <!-- Top Color Bar -->
                    <div class="h-2 w-full absolute top-0 left-0" style="background-color: <?php echo htmlspecialchars($lhSettings['header_color']); ?>;"></div>

                    <!-- 1. Official Letterhead Header -->
                    <div class="flex items-start justify-between gap-4 pb-5 border-b-2" style="border-color: <?php echo htmlspecialchars($lhSettings['header_color']); ?>;">
                        <div class="flex items-start gap-3.5">
                            <?php if (!empty($lhSettings['logo']) && file_exists(__DIR__ . '/' . ltrim($lhSettings['logo'], '/'))): ?>
                                <img src="<?php echo htmlspecialchars($lhSettings['logo']); ?>" alt="Logo" class="w-14 h-14 object-contain flex-shrink-0">
                            <?php else: ?>
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white font-black text-xl shadow-xs" style="background-color: <?php echo htmlspecialchars($lhSettings['header_color']); ?>;">
                                    <?php echo mb_substr($lhSettings['org_name'], 0, 1); ?>
                                </div>
                            <?php endif; ?>
                            <div>
                                <h2 class="font-black text-lg text-slate-900 leading-tight"><?php echo htmlspecialchars($lhSettings['org_name']); ?></h2>
                                <p class="text-[11px] font-bold mt-0.5" style="color: <?php echo htmlspecialchars($lhSettings['header_color']); ?>;"><?php echo htmlspecialchars($lhSettings['tagline']); ?></p>
                                <p class="text-[10px] text-gray-500 font-medium"><?php echo htmlspecialchars($lhSettings['reg_no']); ?></p>
                            </div>
                        </div>

                        <div class="text-right text-[9px] text-gray-600 space-y-0.5 leading-tight flex-shrink-0">
                            <p><span class="font-semibold text-gray-800">Phone:</span> <?php echo htmlspecialchars($lhSettings['phone']); ?></p>
                            <p><span class="font-semibold text-gray-800">Email:</span> <?php echo htmlspecialchars($lhSettings['email']); ?></p>
                            <p><span class="font-semibold text-gray-800">Web:</span> <?php echo htmlspecialchars($lhSettings['website']); ?></p>
                            <p class="max-w-[150px] text-gray-500 italic mt-0.5"><?php echo htmlspecialchars($lhSettings['address']); ?></p>
                        </div>
                    </div>

                    <!-- 2. Ref & Dates Bar -->
                    <div class="flex items-center justify-between text-xs font-semibold text-gray-600 mt-5 pb-3 border-b border-gray-100">
                        <span class="font-mono font-bold text-gray-900">Ref: <?php echo htmlspecialchars($agr['agreement_no']); ?></span>
                        <div class="text-right text-xs">
                            <span class="text-gray-700">Signed: <b><?php echo date('d F Y', strtotime($agr['signed_date'])); ?></b></span>
                            <?php if (!empty($agr['valid_until'])): ?>
                                <span class="text-gray-400 block text-[10px]">Valid Through: <?php echo date('d F Y', strtotime($agr['valid_until'])); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- 3. Title Banner -->
                    <div class="my-6 py-3 px-4 rounded-xl bg-slate-50 border-b-2 text-center" style="border-color: <?php echo htmlspecialchars($lhSettings['header_color']); ?>;">
                        <h3 class="text-sm font-black uppercase tracking-wider" style="color: <?php echo htmlspecialchars($lhSettings['header_color']); ?>;">
                            <?php echo htmlspecialchars($agr['title']); ?>
                        </h3>
                    </div>

                    <!-- 4. Body Content Paragraphs -->
                    <div class="space-y-4 text-xs sm:text-[13px] leading-relaxed text-slate-800 text-justify">
                        <?php foreach ($paragraphs as $para): 
                            $para = trim($para);
                            if (empty($para)) continue;
                            $isHeader = preg_match('/^(\d+\.|\bFIRST PARTY:|\bSECOND PARTY:|\bWHEREAS:|\bNOW, THEREFORE|\bIN WITNESS WHEREOF|\bSCOPE OF AUTHORIZATION|\bVALIDITY & MONITORING|\bTO WHOMSOEVER)/i', $para) || (mb_strtoupper($para) === $para && strlen($para) < 60);
                        ?>
                            <?php if ($isHeader): ?>
                                <h4 class="font-bold text-xs sm:text-sm mt-4 mb-1 uppercase" style="color: <?php echo htmlspecialchars($lhSettings['header_color']); ?>;">
                                    <?php echo htmlspecialchars($para); ?>
                                </h4>
                            <?php else: ?>
                                <p class="whitespace-pre-line"><?php echo htmlspecialchars($para); ?></p>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>

                    <!-- 5. Bilateral Signatures Execution Block -->
                    <div class="pt-10 mt-8 border-t border-gray-200">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-8 text-xs">
                            
                            <!-- First Party (NGO) -->
                            <div class="p-4 bg-slate-50 rounded-2xl border border-gray-100">
                                <p class="font-bold uppercase text-[11px] mb-2" style="color: <?php echo htmlspecialchars($lhSettings['header_color']); ?>;">
                                    FOR FIRST PARTY: <?php echo htmlspecialchars($lhSettings['org_name']); ?>
                                </p>
                                <div class="h-12 my-2 flex items-end">
                                    <?php if (!empty($lhSettings['signature_image']) && file_exists(__DIR__ . '/' . ltrim($lhSettings['signature_image'], '/'))): ?>
                                        <img src="<?php echo htmlspecialchars($lhSettings['signature_image']); ?>" alt="Signature" class="max-h-10 object-contain">
                                    <?php else: ?>
                                        <span class="text-xs text-gray-400 italic">[Authorized Signature & Seal]</span>
                                    <?php endif; ?>
                                </div>
                                <p class="font-bold text-gray-900"><?php echo htmlspecialchars($agr['first_party_name'] ?: $lhSettings['signatory_name']); ?></p>
                                <p class="text-gray-500 text-[11px]"><?php echo htmlspecialchars($agr['first_party_designation'] ?: $lhSettings['signatory_designation']); ?></p>
                                <p class="text-[10px] text-gray-400 italic mt-0.5"><?php echo htmlspecialchars($lhSettings['org_name']); ?> (Official Seal)</p>
                            </div>

                            <!-- Second Party (Partner) -->
                            <div class="p-4 bg-slate-50 rounded-2xl border border-gray-100 text-left sm:text-right">
                                <p class="font-bold uppercase text-[11px] mb-2" style="color: <?php echo htmlspecialchars($lhSettings['header_color']); ?>;">
                                    FOR SECOND PARTY: <?php echo htmlspecialchars($agr['partner_name']); ?>
                                </p>
                                <div class="h-12 my-2 flex items-end justify-start sm:justify-end">
                                    <template x-if="acknowledged">
                                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-emerald-100 text-emerald-800 text-[11px] font-bold">
                                            <i class="fa-solid fa-signature text-emerald-600"></i>
                                            <span>Digitally Executed</span>
                                        </div>
                                    </template>
                                    <template x-if="!acknowledged">
                                        <span class="text-xs text-gray-400 italic border-b border-dashed border-gray-300 pb-0.5">[Pending Digital Signature]</span>
                                    </template>
                                </div>
                                <p class="font-bold text-gray-900" x-text="name"></p>
                                <p class="text-gray-500 text-[11px]" x-text="designation"></p>
                                <p class="text-[10px] text-gray-400 italic mt-0.5"><?php echo htmlspecialchars($agr['partner_name']); ?> (Signature & Seal)</p>
                            </div>
                        </div>

                        <!-- Footer Notice -->
                        <div class="mt-8 pt-4 border-t border-gray-200 text-center text-[10px] text-gray-400">
                            <p><?php echo htmlspecialchars($lhSettings['footer_text']); ?></p>
                            <p class="text-[9px] mt-0.5">Official Letterhead Generated Legal Instrument • Bilateral MoU</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT 1 COL: Digital Acknowledgment & Audit Panel -->
            <div class="space-y-6">
                
                <!-- Digital Acknowledgment Card -->
                <div class="bg-white rounded-3xl shadow-xl border border-gray-200/80 p-6 md:p-7 relative overflow-hidden">
                    
                    <!-- State 1: Already Acknowledged / Signed -->
                    <div x-show="acknowledged" x-transition>
                        <div class="w-14 h-14 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-2xl mx-auto mb-4 shadow-sm">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                        <h3 class="text-base font-black text-gray-900 text-center">Digitally Acknowledged</h3>
                        <p class="text-xs text-gray-500 text-center mt-1">This Memorandum of Understanding has been verified and digitally signed by the authorized partner representative.</p>

                        <div class="mt-6 p-4 rounded-2xl bg-emerald-50/70 border border-emerald-200 text-xs space-y-2.5">
                            <div class="flex items-center justify-between pb-2 border-b border-emerald-100">
                                <span class="text-gray-500 font-semibold text-[11px]">Signatory Name:</span>
                                <span class="font-bold text-emerald-950" x-text="name"></span>
                            </div>
                            <div class="flex items-center justify-between pb-2 border-b border-emerald-100">
                                <span class="text-gray-500 font-semibold text-[11px]">Designation:</span>
                                <span class="font-medium text-emerald-950" x-text="designation"></span>
                            </div>
                            <div class="flex items-center justify-between pb-2 border-b border-emerald-100">
                                <span class="text-gray-500 font-semibold text-[11px]">Timestamp:</span>
                                <span class="font-mono font-bold text-emerald-900 text-[11px]" x-text="ackTime"></span>
                            </div>
                            <template x-if="ip">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500 font-semibold text-[11px]">Verified Origin IP:</span>
                                    <span class="font-mono text-gray-600 text-[10px]" x-text="ip"></span>
                                </div>
                            </template>
                        </div>

                        <div class="mt-6 pt-4 border-t border-gray-100 text-center">
                            <a href="<?php echo !empty($agr['pdf_path']) ? htmlspecialchars($agr['pdf_path']) : 'admin/actions/agreement_logic.php?action=view_agreement&id=' . $agr['id']; ?>" target="_blank" class="w-full inline-flex items-center justify-center gap-2 py-3 px-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-all shadow-md shadow-emerald-200">
                                <i class="fa-solid fa-download"></i>
                                <span>Download Executed Copy (PDF)</span>
                            </a>
                        </div>
                    </div>

                    <!-- State 2: Pending Digital Acknowledgment Form -->
                    <div x-show="!acknowledged" x-transition>
                        <div class="flex items-center gap-2.5 mb-3">
                            <span class="p-2 rounded-xl bg-amber-100 text-amber-700 text-sm">
                                <i class="fa-solid fa-signature"></i>
                            </span>
                            <h3 class="text-base font-black text-gray-900">Partner Acknowledgment</h3>
                        </div>
                        <p class="text-xs text-gray-600 mb-5 leading-relaxed">
                            Please confirm your acceptance of the terms, roles, and guidelines stated in this MoU on behalf of <b><?php echo htmlspecialchars($agr['partner_name']); ?></b>.
                        </p>

                        <form @submit.prevent="submitAcknowledgment()" class="space-y-4 text-xs">
                            <div>
                                <label class="block font-semibold text-gray-700 mb-1">Your Full Name <span class="text-red-500">*</span></label>
                                <input type="text" x-model="name" required class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 font-bold focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>

                            <div>
                                <label class="block font-semibold text-gray-700 mb-1">Your Designation / Role <span class="text-red-500">*</span></label>
                                <input type="text" x-model="designation" required class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>

                            <!-- "I Agree" Checkbox -->
                            <div class="p-4 rounded-2xl bg-indigo-50/60 border border-indigo-200/80">
                                <label class="flex items-start gap-3 cursor-pointer select-none">
                                    <input type="checkbox" x-model="iAgree" class="mt-0.5 w-4 h-4 rounded text-indigo-600 border-gray-300 focus:ring-indigo-500">
                                    <span class="text-xs text-indigo-950 font-medium leading-relaxed">
                                        <b>I Agree</b> & formally confirm that I have reviewed the clauses of this agreement on behalf of <b><?php echo htmlspecialchars($agr['partner_name']); ?></b> and record our mutual acceptance.
                                    </span>
                                </label>
                            </div>

                            <button type="submit" :disabled="submitting || !iAgree" class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white rounded-xl text-xs font-bold transition-all shadow-md shadow-indigo-200 flex items-center justify-center gap-2">
                                <i class="fa-solid fa-signature"></i>
                                <span x-text="submitting ? 'Recording Signature...' : 'Digitally Sign & Acknowledge MoU'"></span>
                            </button>

                            <p class="text-[10px] text-gray-400 text-center mt-2 flex items-center justify-center gap-1">
                                <i class="fa-solid fa-shield-halved text-teal-600"></i>
                                <span>Secured by Digital Audit Timestamp & IP Verification.</span>
                            </p>
                        </form>
                    </div>
                </div>

                <!-- Partner Information Card -->
                <div class="bg-white rounded-3xl shadow-sm border border-gray-200/80 p-6 text-xs space-y-3">
                    <h4 class="font-bold text-gray-900 uppercase text-[11px] tracking-wider text-gray-400 pb-2 border-b border-gray-100 flex items-center gap-2">
                        <i class="fa-solid fa-building-circle-check text-indigo-600"></i>
                        <span>Partner Organization Info</span>
                    </h4>
                    <div>
                        <p class="font-bold text-gray-900"><?php echo htmlspecialchars($agr['partner_name']); ?></p>
                        <?php if (!empty($agr['partner_type'])): ?>
                            <p class="text-gray-500 text-[11px]"><?php echo htmlspecialchars($agr['partner_type']); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($agr['partner_address'])): ?>
                        <div class="text-gray-600 pt-1">
                            <p class="text-[10px] uppercase font-bold text-gray-400">Address</p>
                            <p class="mt-0.5"><?php echo htmlspecialchars($agr['partner_address']); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('partnerAgreementViewer', ({ agreementId, token, isAcknowledged, acknowledgedAt, signatoryName, signatoryDesig, clientIp }) => ({
        agreementId: agreementId,
        token: token,
        acknowledged: isAcknowledged === 1,
        ackTime: acknowledgedAt,
        name: signatoryName,
        designation: signatoryDesig,
        ip: clientIp,
        iAgree: false,
        submitting: false,

        async submitAcknowledgment() {
            if (!this.iAgree) {
                alert('Please check the "I Agree" checkbox to proceed.');
                return;
            }
            if (!this.name) {
                alert('Please enter your full name for digital verification.');
                return;
            }

            this.submitting = true;
            const formData = new FormData();
            formData.append('csrf_token', '<?php echo $csrfToken; ?>');
            formData.append('agreement_id', this.agreementId);
            formData.append('token', this.token);
            formData.append('i_agree', '1');
            formData.append('signatory_name', this.name);
            formData.append('signatory_designation', this.designation);
            formData.append('is_ajax', '1');

            try {
                const res = await fetch('process/acknowledge_agreement.php', { method: 'POST', body: formData });
                const data = await res.json();
                this.submitting = false;

                if (data.success) {
                    this.acknowledged = true;
                    this.ackTime = data.acknowledged_at;
                    this.ip = data.client_ip;
                    alert(data.message);
                } else {
                    alert(data.message || 'Failed to acknowledge agreement.');
                }
            } catch (err) {
                this.submitting = false;
                console.error(err);
                alert('An error occurred while recording the digital acknowledgment.');
            }
        }
    }));
});
</script>

<?php require 'includes/footer.php'; ?>
