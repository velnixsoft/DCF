<?php
// ============================================================
// public/verify-sanstha-certificate.php
// Online Verification Portal for Sanstha Authorization Certificates
// Author: VELNIX SOFT / Antigravity AI
// Date: 2026-09-12
// ============================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/header.php';

$certQuery = cleanInput($_GET['scert'] ?? ($_GET['cert'] ?? ($_GET['certificate_no'] ?? '')));
$record = null;
$searchError = '';

if (!empty($certQuery)) {
    $stmt = $pdo->prepare("
        SELECT * FROM sanstha_certificates 
        WHERE UPPER(TRIM(certificate_no)) = UPPER(?)
        LIMIT 1
    ");
    $stmt->execute([$certQuery]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        $searchError = "No official Sanstha Authorization Certificate found matching '{$certQuery}'.";
    }
}

$siteName = (string)($settings['site_name'] ?? 'NGO Organization');
$ngoPhone = (string)($settings['ngo_phone'] ?? '+91 9876543210');
$ngoEmail = (string)($settings['ngo_email'] ?? 'info@ngo.org');
?>

<div class="min-h-screen py-12 md:py-16 px-4 sm:px-6 lg:px-8 bg-slate-50 dark:bg-gray-900 relative overflow-hidden">

    <!-- Ambient background glows -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-[5%] left-[-10%] w-[350px] sm:w-[500px] h-[350px] sm:h-[500px] rounded-full bg-amber-100/40 dark:bg-amber-950/20 blur-[100px] sm:blur-[140px]"></div>
        <div class="absolute bottom-[10%] right-[-10%] w-[350px] sm:w-[500px] h-[350px] sm:h-[500px] rounded-full bg-indigo-100/40 dark:bg-indigo-950/20 blur-[100px] sm:blur-[140px]"></div>
    </div>

    <div class="max-w-3xl mx-auto relative z-10 space-y-8">

        <!-- Header Hero -->
        <div class="text-center space-y-3">
            <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold bg-amber-50 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300 border border-amber-200/60 dark:border-amber-800/40 uppercase tracking-wider shadow-sm">
                <i class="fa-solid fa-stamp"></i> Institutional Governance & Verification Registry
            </span>
            <h1 class="text-3xl sm:text-4xl font-black text-gray-900 dark:text-white tracking-tight">
                Sanstha Authorization <span class="text-amber-600">Certificate Verification</span>
            </h1>
            <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 max-w-lg mx-auto leading-relaxed">
                Verify the official institutional authorization, branch credentials, in-charge appointments, and valid jurisdiction for our recognized centers.
            </p>
        </div>

        <!-- Search Box Card -->
        <div class="bg-white/90 dark:bg-gray-800/90 backdrop-blur-md shadow-xl border border-slate-200 dark:border-gray-700 rounded-3xl p-6 sm:p-8">
            <form method="GET" action="verify-sanstha-certificate.php" class="space-y-4">
                <label for="certInput" class="block text-[11px] font-bold text-gray-400 uppercase tracking-widest">
                    Enter Sanstha Authorization Code or Certificate Reference
                </label>

                <div class="flex flex-col sm:flex-row items-stretch gap-3">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 text-base">
                            <i class="fa-solid fa-stamp"></i>
                        </span>
                        <input type="text" id="certInput" name="scert" value="<?php echo htmlspecialchars($certQuery); ?>" required
                               placeholder="e.g. AUTH-SANSTHA-2026-0001" 
                               class="w-full pl-11 pr-4 py-3.5 bg-slate-50 dark:bg-gray-900 border border-slate-200 dark:border-gray-700 rounded-2xl focus:bg-white focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 outline-none text-gray-900 dark:text-white font-mono font-bold text-sm tracking-wide uppercase transition-all">
                    </div>

                    <button type="submit" 
                            class="py-3.5 px-7 bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-700 hover:to-orange-700 text-white font-bold text-xs sm:text-sm rounded-2xl shadow-md transition transform active:scale-95 flex items-center justify-center gap-2 flex-shrink-0">
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

        <!-- Verified Record Display Card -->
        <?php if ($record): ?>
            <?php 
                $status = strtolower((string)$record['status']);
                $isActive = ($status === 'active');
                $isExpired = ($status === 'expired') || (!empty($record['valid_until']) && $record['valid_until'] < date('Y-m-d'));
            ?>

            <div class="bg-white dark:bg-gray-800 shadow-2xl border <?php echo $isActive && !$isExpired ? 'border-emerald-200 dark:border-emerald-800 ring-4 ring-emerald-500/10' : 'border-rose-200 dark:border-rose-800'; ?> rounded-3xl overflow-hidden transition-all">
                
                <!-- Status Banner -->
                <div class="<?php echo $isActive && !$isExpired ? 'bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-700' : 'bg-gradient-to-r from-rose-600 to-amber-600'; ?> p-6 text-white flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-3.5 text-center sm:text-left">
                        <div class="w-12 h-12 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center text-2xl flex-shrink-0">
                            <i class="fa-solid <?php echo $isActive && !$isExpired ? 'fa-certificate' : 'fa-triangle-exclamation'; ?>"></i>
                        </div>
                        <div>
                            <span class="text-[11px] uppercase tracking-widest font-black opacity-90 block">
                                Verification Status
                            </span>
                            <h2 class="text-xl sm:text-2xl font-black tracking-tight">
                                <?php echo $isActive && !$isExpired ? 'Official Verified Sanstha Authorization' : 'Inactive / Under Review'; ?>
                            </h2>
                        </div>
                    </div>

                    <div class="px-4 py-1.5 rounded-full bg-white/20 backdrop-blur-md text-xs font-black uppercase tracking-wider border border-white/30">
                        <?php echo htmlspecialchars($record['status']); ?>
                    </div>
                </div>

                <!-- Record Body Particulars -->
                <div class="p-6 sm:p-8 space-y-6">
                    
                    <!-- Sanstha Title & Category Header -->
                    <div class="pb-6 border-b border-slate-100 dark:border-gray-700 flex flex-col sm:flex-row justify-between sm:items-start gap-4">
                        <div class="space-y-1">
                            <span class="px-3 py-1 rounded-full text-[11px] font-bold bg-amber-50 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800 inline-block mb-1">
                                <?php echo htmlspecialchars($record['auth_type']); ?>
                            </span>
                            <h3 class="text-2xl font-black text-gray-900 dark:text-white">
                                <?php echo htmlspecialchars($record['sanstha_name']); ?>
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Authorized In-Charge: <strong class="text-gray-800 dark:text-gray-200"><?php echo htmlspecialchars($record['authorized_person']); ?></strong> (<?php echo htmlspecialchars($record['designation'] ?: 'Director'); ?>)
                            </p>
                        </div>

                        <div class="text-left sm:text-right bg-slate-50 dark:bg-gray-750 p-3.5 rounded-2xl border border-slate-200/60 dark:border-gray-700">
                            <span class="text-[10px] uppercase font-bold text-gray-400 block tracking-wider">Certificate Number</span>
                            <span class="font-mono text-base font-black text-amber-700 dark:text-amber-400 tracking-wider">
                                <?php echo htmlspecialchars($record['certificate_no']); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Metadata Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <div class="bg-slate-50 dark:bg-gray-750 p-4 rounded-2xl border border-slate-100 dark:border-gray-700 space-y-1">
                            <span class="text-[10px] uppercase font-bold text-gray-400 block tracking-wider">Validity Period</span>
                            <p class="text-gray-800 dark:text-gray-200 font-bold">
                                <?php echo date('d M Y', strtotime($record['valid_from'])); ?> 
                                <span class="text-gray-400 font-normal">to</span> 
                                <?php echo !empty($record['valid_until']) ? date('d M Y', strtotime($record['valid_until'])) : 'Perpetual / Ongoing'; ?>
                            </p>
                        </div>

                        <div class="bg-slate-50 dark:bg-gray-750 p-4 rounded-2xl border border-slate-100 dark:border-gray-700 space-y-1">
                            <span class="text-[10px] uppercase font-bold text-gray-400 block tracking-wider">Jurisdiction / Location</span>
                            <p class="text-gray-800 dark:text-gray-200 font-bold">
                                <?php echo htmlspecialchars($record['district'] ?: $record['city']); ?><?php echo !empty($record['state']) ? ', ' . htmlspecialchars($record['state']) : ''; ?>
                            </p>
                        </div>

                        <div class="bg-slate-50 dark:bg-gray-750 p-4 rounded-2xl border border-slate-100 dark:border-gray-700 space-y-1 sm:col-span-2">
                            <span class="text-[10px] uppercase font-bold text-gray-400 block tracking-wider">Full Center Address</span>
                            <p class="text-gray-700 dark:text-gray-300">
                                <?php echo htmlspecialchars($record['center_address'] ?: 'Official registered location.'); ?>
                            </p>
                        </div>
                    </div>

                    <!-- Terms of Authorization & Scope -->
                    <?php if (!empty($record['scope_of_work'])): ?>
                        <div class="bg-amber-50/50 dark:bg-amber-950/20 border border-amber-200/60 dark:border-amber-800/40 rounded-2xl p-4 sm:p-5">
                            <h4 class="text-xs font-black uppercase text-amber-900 dark:text-amber-300 tracking-wider mb-1.5 flex items-center gap-1.5">
                                <i class="fa-solid fa-file-shield text-amber-600"></i> Authorized Scope & Jurisdiction
                            </h4>
                            <p class="text-xs text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-line">
                                <?php echo htmlspecialchars($record['scope_of_work']); ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <!-- Action / Download Certificate -->
                    <div class="pt-4 border-t border-slate-100 dark:border-gray-700 flex flex-wrap items-center justify-between gap-3">
                        <div class="text-[11px] text-gray-400">
                            Issued by Governing Council of <strong><?php echo htmlspecialchars($siteName); ?></strong>
                        </div>

                        <div class="flex items-center gap-2">
                            <a href="admin/actions/sanstha_certificate_logic.php?action=view_certificate&id=<?php echo (int)$record['id']; ?>" 
                               target="_blank"
                               class="px-4 py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                                <i class="fa-solid fa-file-pdf"></i> View Certificate PDF
                            </a>
                            <a href="admin/actions/sanstha_certificate_logic.php?action=download_certificate&id=<?php echo (int)$record['id']; ?>" 
                               class="px-4 py-2.5 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5">
                                <i class="fa-solid fa-download"></i> Download
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
