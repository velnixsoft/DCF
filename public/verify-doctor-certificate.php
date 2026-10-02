<?php
// ============================================================
// public/verify-doctor-certificate.php
// Online Verification Portal for Authorized Doctor & Partner Certificates
// ============================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/header.php';

$certQuery = cleanInput($_GET['cert'] ?? ($_GET['certificate_no'] ?? ''));
$record = null;
$searchError = '';

if (!empty($certQuery)) {
    $stmt = $pdo->prepare("
        SELECT 
            da.*,
            hp.provider_code,
            hp.name AS partner_name,
            hp.type AS partner_type,
            hp.speciality AS partner_speciality,
            hp.contact AS partner_contact,
            hp.email AS partner_email,
            hp.address AS partner_address,
            hp.district AS partner_district,
            hp.state AS partner_state,
            hp.photo AS partner_photo
        FROM doctor_agreements da
        JOIN healthcare_providers hp ON da.partner_id = hp.id
        WHERE UPPER(TRIM(da.certificate_no)) = UPPER(?) 
           OR UPPER(TRIM(da.agreement_no)) = UPPER(?)
        LIMIT 1
    ");
    $stmt->execute([$certQuery, $certQuery]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        $searchError = "No verified doctor empanelment certificate found matching '{$certQuery}'.";
    }
}

$siteName = (string)($settings['site_name'] ?? 'Jaysmrutti Foundation');
$ngoPhone = (string)($settings['ngo_phone'] ?? '+91 7651910331');
$ngoEmail = (string)($settings['ngo_email'] ?? 'info@velnixsoft.com');
?>

<div class="min-h-screen py-12 md:py-16 px-4 sm:px-6 lg:px-8 bg-slate-50 dark:bg-gray-900 relative overflow-hidden">

    <!-- Ambient background glows -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-[5%] left-[-10%] w-[350px] sm:w-[500px] h-[350px] sm:h-[500px] rounded-full bg-teal-100/40 dark:bg-teal-950/20 blur-[100px] sm:blur-[140px]"></div>
        <div class="absolute bottom-[10%] right-[-10%] w-[350px] sm:w-[500px] h-[350px] sm:h-[500px] rounded-full bg-amber-100/40 dark:bg-amber-950/20 blur-[100px] sm:blur-[140px]"></div>
    </div>

    <div class="max-w-3xl mx-auto relative z-10 space-y-8">

        <!-- Header Hero -->
        <div class="text-center space-y-3">
            <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold bg-teal-50 dark:bg-teal-900/40 text-[#0F8B8D] dark:text-teal-300 border border-teal-200/60 dark:border-teal-800/40 uppercase tracking-wider shadow-sm">
                <i class="fa-solid fa-shield-halved"></i> Institutional Trust & Healthcare Registry
            </span>
            <h1 class="text-3xl sm:text-4xl font-black text-gray-900 dark:text-white tracking-tight">
                Doctor Empanelment <span class="text-[#0F8B8D]">Certificate Verification</span>
            </h1>
            <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 max-w-lg mx-auto leading-relaxed">
                Verify the official credentials, empanelment status, and authorized concession terms for our partner doctors, clinics, and hospitals.
            </p>
        </div>

        <!-- Search Box Card -->
        <div class="bg-white/90 dark:bg-gray-800/90 backdrop-blur-md shadow-xl border border-slate-200 dark:border-gray-700 rounded-3xl p-6 sm:p-8">
            <form method="GET" action="verify-doctor-certificate.php" class="space-y-4">
                <label for="certInput" class="block text-[11px] font-bold text-gray-400 uppercase tracking-widest">
                    Enter Certificate Number or Agreement Code
                </label>

                <div class="flex flex-col sm:flex-row items-stretch gap-3">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 text-base">
                            <i class="fa-solid fa-award"></i>
                        </span>
                        <input type="text" id="certInput" name="cert" value="<?php echo htmlspecialchars($certQuery); ?>" required
                               placeholder="e.g. DOC-CERT-2026-0001 or AGR-DR-2026-001" 
                               class="w-full pl-11 pr-4 py-3.5 bg-slate-50 dark:bg-gray-900 border border-slate-200 dark:border-gray-700 rounded-2xl focus:bg-white focus:ring-4 focus:ring-[#0F8B8D]/10 focus:border-[#0F8B8D] outline-none text-gray-900 dark:text-white font-mono font-bold text-sm tracking-wide uppercase transition-all">
                    </div>

                    <button type="submit" 
                            class="py-3.5 px-7 bg-[#0F8B8D] hover:bg-[#0c7274] text-white font-bold text-xs sm:text-sm rounded-2xl shadow-md transition transform active:scale-95 flex items-center justify-center gap-2 flex-shrink-0">
                        <i class="fa-solid fa-magnifying-glass"></i> Verify Certificate
                    </button>
                </div>
            </form>

            <?php if (!empty($searchError)): ?>
                <div class="mt-4 p-4 rounded-2xl bg-rose-50 dark:bg-rose-900/30 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 text-xs flex items-center gap-2.5">
                    <i class="fa-solid fa-circle-exclamation text-base shrink-0"></i>
                    <span><?php echo htmlspecialchars($searchError); ?></span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Verified Record Result Display -->
        <?php if ($record): ?>
            <div class="bg-white dark:bg-gray-800 shadow-2xl border border-emerald-200/80 dark:border-emerald-800/60 rounded-3xl overflow-hidden animate-fade-in">
                
                <!-- Card Header -->
                <div class="p-6 sm:p-8 bg-gradient-to-r from-teal-900 via-[#0F8B8D] to-teal-800 text-white relative">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <div class="flex flex-wrap items-center gap-2 mb-2">
                                <span class="px-2.5 py-1 rounded-full text-[11px] font-mono font-bold bg-white/20 text-white border border-white/20">
                                    <?php echo htmlspecialchars($record['certificate_no'] ?: $record['agreement_no']); ?>
                                </span>
                                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider bg-amber-400/20 text-amber-200 border border-amber-300/30">
                                    <i class="fa-solid fa-user-doctor text-[10px]"></i> <?php echo strtoupper(htmlspecialchars($record['partner_type'])); ?>
                                </span>
                            </div>

                            <h2 class="text-2xl sm:text-3xl font-black text-white">
                                <?php echo htmlspecialchars(!empty($record['doctor_name']) ? $record['doctor_name'] : $record['partner_name']); ?>
                            </h2>
                            <p class="text-xs text-teal-100 mt-1">
                                <?php echo htmlspecialchars($record['partner_name']); ?> &bull; Speciality: <span class="font-bold text-white"><?php echo ucwords(str_replace('_', ' ', $record['speciality'] ?: $record['partner_speciality'])); ?></span>
                            </p>
                        </div>

                        <!-- Verified Stamp Badge -->
                        <div class="shrink-0 flex items-center gap-2 bg-emerald-500/20 border border-emerald-400/40 px-4 py-2 rounded-2xl">
                            <i class="fa-solid fa-certificate text-emerald-300 text-xl"></i>
                            <div>
                                <div class="text-[10px] font-extrabold uppercase text-emerald-200 tracking-wider">Status</div>
                                <div class="text-xs font-black text-white"><?php echo strtoupper(htmlspecialchars($record['status'])); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Verified Details Grid -->
                <div class="p-6 sm:p-8 space-y-6">
                    
                    <div class="p-4 rounded-2xl bg-emerald-50/70 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 text-xs text-emerald-900 dark:text-emerald-200 flex items-start gap-3">
                        <i class="fa-solid fa-circle-check text-emerald-600 text-lg mt-0.5 shrink-0"></i>
                        <div>
                            <strong class="font-bold block">Official Empanelment Verified</strong>
                            <span>This doctor / healthcare institution is officially authorized by <strong><?php echo htmlspecialchars($siteName); ?></strong> to extend priority consultation and healthcare benefits.</span>
                        </div>
                    </div>

                    <!-- Particulars Table -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 p-5 rounded-2xl bg-slate-50 dark:bg-gray-700/30 border border-slate-200 dark:border-gray-700 text-xs">
                        <div>
                            <span class="text-gray-400 block uppercase font-bold text-[10px]">Provider Code</span>
                            <span class="font-mono font-bold text-gray-900 dark:text-white mt-0.5 block"><?php echo htmlspecialchars($record['provider_code'] ?: 'N/A'); ?></span>
                        </div>

                        <div>
                            <span class="text-gray-400 block uppercase font-bold text-[10px]">Agreement Ref</span>
                            <span class="font-mono font-bold text-gray-900 dark:text-white mt-0.5 block"><?php echo htmlspecialchars($record['agreement_no']); ?></span>
                        </div>

                        <div>
                            <span class="text-gray-400 block uppercase font-bold text-[10px]">Signed Date</span>
                            <span class="font-bold text-gray-900 dark:text-white mt-0.5 block"><?php echo !empty($record['signed_date']) ? date('d M Y', strtotime($record['signed_date'])) : '—'; ?></span>
                        </div>

                        <div>
                            <span class="text-gray-400 block uppercase font-bold text-[10px]">Empanelment Validity</span>
                            <span class="font-bold text-emerald-600 dark:text-emerald-400 mt-0.5 block"><?php echo !empty($record['valid_until']) ? date('d M Y', strtotime($record['valid_until'])) : 'Active / Annual Renewal'; ?></span>
                        </div>

                        <div>
                            <span class="text-gray-400 block uppercase font-bold text-[10px]">Location</span>
                            <span class="font-bold text-gray-900 dark:text-white mt-0.5 block"><?php echo htmlspecialchars($record['partner_district'] . ', ' . $record['partner_state']); ?></span>
                        </div>

                        <div>
                            <span class="text-gray-400 block uppercase font-bold text-[10px]">Contact</span>
                            <span class="font-bold text-gray-900 dark:text-white mt-0.5 block"><?php echo htmlspecialchars($record['partner_contact']); ?></span>
                        </div>
                    </div>

                    <!-- Discount / Concession Terms -->
                    <div class="space-y-1.5">
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest">Authorized Beneficiary Discount Terms</h4>
                        <div class="p-4 rounded-2xl bg-amber-50/70 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 text-xs text-amber-900 dark:text-amber-200 font-semibold leading-relaxed">
                            <i class="fa-solid fa-hand-holding-medical mr-1.5 text-amber-600"></i>
                            <?php echo htmlspecialchars($record['discount_terms'] ?: '20% to 100% concession on OPD consultations as per Health Card Scheme.'); ?>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="pt-4 border-t border-slate-100 dark:border-gray-700 flex flex-wrap items-center justify-between gap-3">
                        <a href="healthcare-directory.php" class="text-xs font-bold text-[#0F8B8D] hover:underline flex items-center gap-1.5">
                            <i class="fa-solid fa-arrow-left"></i> View Full Healthcare Directory
                        </a>

                        <?php if (!empty($record['certificate_pdf_path']) && file_exists(__DIR__ . '/../' . ltrim($record['certificate_pdf_path'], '/\\'))): ?>
                            <a href="<?php echo htmlspecialchars($record['certificate_pdf_path']); ?>" target="_blank" class="px-5 py-2.5 rounded-2xl bg-[#0F8B8D] hover:bg-[#0c7274] text-white font-bold text-xs shadow-md transition flex items-center gap-2">
                                <i class="fa-solid fa-file-pdf"></i> Download Official Certificate (PDF)
                            </a>
                        <?php endif; ?>
                    </div>

                </div>

            </div>
        <?php endif; ?>

    </div>
</div>

<?php require 'includes/footer.php'; ?>
