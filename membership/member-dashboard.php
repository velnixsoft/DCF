<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (empty($_SESSION['member_logged_in']) || empty($_SESSION['member_id'])) {
    setFlash('error', 'Please login to access the member dashboard.');
    header('Location: member-login.php');
    exit;
}

require_once __DIR__ . '/../includes/header.php';

$memberNo = (string)($_SESSION['member_no'] ?? '');
$email = (string)($_SESSION['member_email'] ?? '');
$member = null;
$messages = [];
$memberRefCount = 0;
$donationRefAmount = 0;
$inquiries = [];
$upcomingEvents = [];

$stmt = $pdo->prepare("SELECT m.*, d.title AS designation_title FROM members m LEFT JOIN member_designations d ON d.id = m.designation_id WHERE m.id = ? LIMIT 1");
$stmt->execute([(int)$_SESSION['member_id']]);
$member = $stmt->fetch(PDO::FETCH_ASSOC);

if ($member) {
    $cStmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE referred_by_member_id = ?");
    $cStmt->execute([(int)$member['id']]);
    $memberRefCount = (int)$cStmt->fetchColumn();

    $dStmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM donations WHERE referral_code = ? AND payment_status = 'Success'");
    $dStmt->execute([$member['donation_ref_code']]);
    $donationRefAmount = (float)$dStmt->fetchColumn();

    $msgStmt = $pdo->prepare("
            SELECT md.id AS delivery_id, md.dashboard_status, md.created_at, mm.subject, mm.message_body, mm.message_type
            FROM member_message_deliveries md
            INNER JOIN member_messages mm ON mm.id = md.message_id
            WHERE md.member_id = ?
            ORDER BY md.created_at DESC
            LIMIT 100
        ");
    $msgStmt->execute([(int)$member['id']]);
    $messages = $msgStmt->fetchAll(PDO::FETCH_ASSOC);

    $pdo->prepare("UPDATE member_message_deliveries SET dashboard_status = 'Read' WHERE member_id = ?")->execute([(int)$member['id']]);

    try {
        if (dbColumnExists($pdo, 'inquiries', 'member_id')) {
            $iStmt = $pdo->prepare("SELECT id, problem_description, status, created_at, admin_notes, category, urgency, attachment_path FROM inquiries WHERE member_id = ? ORDER BY created_at DESC LIMIT 50");
            $iStmt->execute([(int)$member['id']]);
        } else {
            $iStmt = $pdo->prepare("SELECT id, problem_description, status, created_at, admin_notes, category, urgency, attachment_path FROM inquiries WHERE submitter_email = ? ORDER BY created_at DESC LIMIT 50");
            $iStmt->execute([(string)$member['email']]);
        }
        $inquiries = $iStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $inquiries = [];
    }

    $healthCard = null;
    try {
        if (dbTableExists($pdo, 'health_cards')) {
            $hcStmt = $pdo->prepare("SELECT * FROM health_cards WHERE member_id = ? OR contact = ? ORDER BY id DESC LIMIT 1");
            $hcStmt->execute([(int)$member['id'], (string)($member['phone'] ?? '')]);
            $healthCard = $hcStmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch (Throwable $e) {
        $healthCard = null;
    }

    $partnerAgreements = [];
    try {
        if (dbTableExists($pdo, 'agreements')) {
            $mEmail = trim((string)($member['email'] ?? ''));
            $mPhone = trim((string)($member['phone'] ?? ''));
            $mName = trim((string)($member['full_name'] ?? ''));
            $mId = (int)($member['id'] ?? 0);

            $agSql = "SELECT * FROM agreements WHERE 
                        partner_member_id = ? 
                        OR (partner_email != '' AND partner_email = ?) 
                        OR (partner_contact != '' AND partner_contact = ?) 
                        OR (partner_name != '' AND LOWER(partner_name) = LOWER(?))
                      ORDER BY id DESC";
            $agStmt = $pdo->prepare($agSql);
            $agStmt->execute([$mId, $mEmail, $mPhone, $mName]);
            $partnerAgreements = $agStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Throwable $e) {
        $partnerAgreements = [];
    }

    $doctorAgreements = [];
    try {
        if (dbTableExists($pdo, 'doctor_agreements')) {
            $mEmail = trim((string)($member['email'] ?? ''));
            $mPhone = trim((string)($member['phone'] ?? ''));
            
            $daSql = "SELECT da.*, hp.name as hospital_name, hp.contact_person, hp.category, hp.city 
                      FROM doctor_agreements da 
                      LEFT JOIN healthcare_providers hp ON da.partner_id = hp.id
                      WHERE (hp.email != '' AND hp.email = ?) 
                         OR (hp.phone != '' AND hp.phone = ?)
                      ORDER BY da.id DESC";
            $daStmt = $pdo->prepare($daSql);
            $daStmt->execute([$mEmail, $mPhone]);
            $doctorAgreements = $daStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Throwable $e) {
        $doctorAgreements = [];
    }
}

$today = date('Y-m-d');
try {
    $stmt = $pdo->prepare("SELECT id, title, event_date, location, status FROM events WHERE event_date >= ? AND status IN ('Upcoming','Live') ORDER BY event_date ASC LIMIT 8");
    $stmt->execute([$today]);
    $upcomingEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $upcomingEvents = [];
}


// =========================
// 🔥 BULLETPROOF BASE URL FIX
// =========================
$rawBase = trim((string)($settings['ngo_website'] ?? ''));

// fallback if empty
if ($rawBase === '') {
    $rawBase = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
}

// Extract ONLY valid domain (removes duplicates automatically)
if (preg_match('#https?://[^/]+#i', $rawBase, $match)) {
    $baseUrl = $match[0];
} else {
    $baseUrl = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
}

// Remove trailing slash
$baseUrl = rtrim($baseUrl, '/');

// =========================
// ✅ FINAL SAFE LINKS
// =========================
$membershipReferralLink = $baseUrl . '/member-register.php?ref=' . urlencode((string)$member['referral_code']);
$donationReferralLink   = $baseUrl . '/donate.php?mref=' . urlencode((string)$member['donation_ref_code']);
?>
<div class="bg-gray-50 min-h-screen py-12">
    <div class="container mx-auto px-4 max-w-4xl">
        <div class="bg-white rounded-xl shadow border border-gray-100 p-6 md:p-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Member Dashboard</h1>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6">
                <p class="text-gray-600">
                    <?php if ($member): ?>
                        Logged in as <b><?php echo htmlspecialchars($member['full_name'] ?? ($_SESSION['member_name'] ?? 'Member')); ?></b>
                        <span class="text-gray-400">•</span>
                        <span class="font-mono text-sm"><?php echo htmlspecialchars($member['member_no'] ?? ''); ?></span>
                    <?php endif; ?>
                </p>
                <div class="flex flex-wrap gap-2">
                    <a href="process/member_logout.php" class="bg-gray-900 hover:bg-gray-800 text-white px-4 py-2 rounded-lg text-sm">Logout</a>
                </div>
            </div>

            <?php if ($member): ?>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="rounded-lg border border-blue-100 bg-blue-50 p-4">
                        <p class="text-xs text-blue-700">Member Referrals</p>
                        <p class="text-2xl font-bold text-blue-900"><?php echo $memberRefCount; ?></p>
                    </div>
                    <div class="rounded-lg border border-emerald-100 bg-emerald-50 p-4">
                        <p class="text-xs text-emerald-700">Donation Contributions</p>
                        <p class="text-2xl font-bold text-emerald-900">INR <?php echo number_format($donationRefAmount, 2); ?></p>
                    </div>
                    <div class="rounded-lg border border-violet-100 bg-violet-50 p-4">
                        <p class="text-xs text-violet-700">Member Status</p>
                        <p class="text-2xl font-bold text-violet-900"><?php echo htmlspecialchars($member['status']); ?></p>
                    </div>
                </div>

                <!-- NGO Health Card Section -->
                <?php
                $hcIsExpired = false;
                $hcIsExpiringSoon = false;
                $hcDaysRemaining = null;
                if ($healthCard && !empty($healthCard['expiry_date'])) {
                    $expTime = strtotime($healthCard['expiry_date']);
                    $todayTime = strtotime(date('Y-m-d'));
                    $hcDaysRemaining = (int)ceil(($expTime - $todayTime) / 86400);
                    if ($hcDaysRemaining < 0 || $healthCard['status'] === 'expired') {
                        $hcIsExpired = true;
                    } elseif ($hcDaysRemaining <= 60) {
                        $hcIsExpiringSoon = true;
                    }
                }
                ?>
                <div class="rounded-2xl border <?php echo $hcIsExpired ? 'border-rose-200 bg-gradient-to-r from-rose-50/70 via-white to-amber-50/50' : ($hcIsExpiringSoon ? 'border-amber-200 bg-gradient-to-r from-amber-50/80 via-white to-teal-50/50' : 'border-teal-100 bg-gradient-to-r from-teal-50/70 via-white to-emerald-50/50'); ?> p-5 md:p-6 mb-6 shadow-xs">
                    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-5">
                        
                        <!-- Left: Info & Badges -->
                        <div class="flex items-start gap-4 flex-1">
                            <div class="w-13 h-13 rounded-2xl <?php echo $hcIsExpired ? 'bg-rose-600 shadow-rose-600/20' : ($hcIsExpiringSoon ? 'bg-amber-600 shadow-amber-600/20' : 'bg-teal-600 shadow-teal-600/20'); ?> text-white flex items-center justify-center text-2xl flex-shrink-0 shadow-lg">
                                <i class="fa-solid fa-id-card-clip"></i>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="text-base font-black text-gray-900">NGO Swasthya / Health Card</h3>
                                    <?php if ($healthCard): ?>
                                        <?php if ($healthCard['status'] === 'active'): ?>
                                            <?php if ($hcIsExpiringSoon): ?>
                                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-900 border border-amber-300 flex items-center gap-1">
                                                    <i class="fa-solid fa-triangle-exclamation text-amber-600"></i> Expiring Soon
                                                </span>
                                            <?php else: ?>
                                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-900 border border-emerald-300 flex items-center gap-1">
                                                    <i class="fa-solid fa-circle-check text-emerald-600"></i> Active
                                                </span>
                                            <?php endif; ?>
                                        <?php elseif ($healthCard['status'] === 'expired' || $hcIsExpired): ?>
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-rose-100 text-rose-900 border border-rose-300 flex items-center gap-1">
                                                <i class="fa-solid fa-circle-xmark text-rose-600"></i> Expired
                                            </span>
                                        <?php elseif ($healthCard['status'] === 'pending_approval'): ?>
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-sky-100 text-sky-900 border border-sky-300 flex items-center gap-1">
                                                <i class="fa-solid fa-hourglass-half text-sky-600"></i> Under Review
                                            </span>
                                        <?php elseif ($healthCard['status'] === 'renewed'): ?>
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-purple-100 text-purple-900 border border-purple-300 flex items-center gap-1">
                                                <i class="fa-solid fa-arrows-rotate text-purple-600"></i> Renewed
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>

                                <?php if ($healthCard): ?>
                                    <div class="mt-1.5 flex items-center gap-y-1 gap-x-3 text-xs text-gray-700 flex-wrap">
                                        <span>Card No: <strong class="font-mono text-teal-800 bg-white/80 px-1.5 py-0.5 rounded border border-gray-200"><?php echo htmlspecialchars($healthCard['card_number']); ?></strong></span>
                                        
                                        <span>
                                            Valid Till: <strong class="<?php echo $hcIsExpired ? 'text-rose-600' : ($hcIsExpiringSoon ? 'text-amber-700 font-bold' : 'text-gray-900'); ?>">
                                                <?php echo htmlspecialchars(date('d M Y', strtotime($healthCard['expiry_date']))); ?>
                                            </strong>
                                            <?php if ($hcIsExpired): ?>
                                                <span class="text-rose-600 font-bold text-[11px] ml-1">(Expired <?php echo abs($hcDaysRemaining); ?> days ago)</span>
                                            <?php elseif ($hcIsExpiringSoon): ?>
                                                <span class="text-amber-700 font-bold text-[11px] ml-1">(Expires in <?php echo $hcDaysRemaining; ?> days)</span>
                                            <?php endif; ?>
                                        </span>

                                        <?php if (!empty($healthCard['blood_group'])): ?>
                                            <span>Blood: <strong class="text-rose-600"><?php echo htmlspecialchars($healthCard['blood_group']); ?></strong></span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-[11px] text-gray-500 mt-1">
                                        Avail cashless discounts on doctor OPD, diagnostics, and generic medicines across all empaneled hospitals.
                                    </p>
                                <?php else: ?>
                                    <p class="text-xs text-gray-600 mt-1">
                                        Avail subsidized medical treatments, free health checkups & pharmacy discounts across our empaneled hospital network.
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Right: Action Buttons -->
                        <div class="flex flex-wrap items-center gap-2.5 w-full lg:w-auto justify-start lg:justify-end">
                            <?php if ($healthCard): ?>
                                
                                <!-- Download PDF Button -->
                                <a href="download-health-card.php?id=<?php echo (int)$healthCard['id']; ?>&mode=download" 
                                   class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold transition-all shadow-sm hover:shadow-md">
                                    <i class="fa-solid fa-file-pdf"></i>
                                    <span>Download Card (PDF)</span>
                                </a>

                                <!-- View / Print Card -->
                                <a href="download-health-card.php?id=<?php echo (int)$healthCard['id']; ?>&mode=stream" 
                                   target="_blank"
                                   class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 rounded-xl bg-white hover:bg-gray-50 border border-gray-200 text-gray-700 text-xs font-semibold transition-all">
                                    <i class="fa-solid fa-eye text-teal-600"></i>
                                    <span>View / Print</span>
                                </a>

                                <!-- Renew Button (Near Expiry with High Emphasis) -->
                                <a href="apply-health-card.php?renew_card=<?php echo urlencode($healthCard['card_number']); ?>&renew_id=<?php echo (int)$healthCard['id']; ?>" 
                                   class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl <?php echo ($hcIsExpired || $hcIsExpiringSoon) ? 'bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white font-black shadow-md shadow-amber-500/25 ring-2 ring-amber-300 ring-offset-1 animate-pulse' : 'bg-emerald-600 hover:bg-emerald-700 text-white font-bold shadow-sm'; ?> text-xs transition-all">
                                    <i class="fa-solid fa-arrows-rotate"></i>
                                    <span><?php echo $hcIsExpired ? 'Renew Card Now' : ($hcIsExpiringSoon ? 'Renew Before Expiry' : 'Renew Card'); ?></span>
                                </a>

                                <!-- Healthcare Network Directory -->
                                <a href="healthcare-directory.php" class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 rounded-xl text-teal-700 hover:bg-teal-50 text-xs font-semibold transition-all">
                                    <i class="fa-solid fa-hospital-user"></i>
                                    <span>Hospital Panel</span>
                                </a>

                            <?php else: ?>
                                <a href="apply-health-card.php" class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-700 hover:to-emerald-700 text-white text-xs font-black uppercase tracking-wider transition-all shadow-md shadow-teal-500/20">
                                    <i class="fa-solid fa-plus text-sm"></i>
                                    <span>Apply for Health Card</span>
                                </a>
                            <?php endif; ?>
                    </div>
                </div>

                <!-- Institutional MoUs & Partner Agreements Section -->
                <?php if (!empty($partnerAgreements) || !empty($doctorAgreements)): ?>
                    <div class="rounded-2xl border border-indigo-100 dark:border-indigo-900/30 bg-gradient-to-br from-white via-indigo-50/20 to-white dark:from-gray-800 dark:to-gray-850 p-5 md:p-6 mb-6 shadow-sm">
                        <div class="flex flex-wrap items-center justify-between gap-3 mb-4 pb-3 border-b border-indigo-50 dark:border-gray-700">
                            <div class="flex items-center gap-2.5">
                                <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center shadow-md shadow-indigo-500/20 text-lg">
                                    <i class="fa-solid fa-file-contract"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-900 dark:text-white text-base">Institutional MoUs & Agreements</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Review official bilateral agreements, authorizations & digitally sign via "I Agree"</p>
                                </div>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800 dark:bg-indigo-900/50 dark:text-indigo-300">
                                <?php echo count($partnerAgreements) + count($doctorAgreements); ?> Document(s)
                            </span>
                        </div>

                        <div class="space-y-4">
                            <?php foreach ($partnerAgreements as $pAg): ?>
                                <?php 
                                    $isAck = ((int)($pAg['is_acknowledged'] ?? 0) === 1 || ($pAg['signed_status'] ?? '') === 'signed');
                                    $pAgType = (string)($pAg['type'] ?? 'mou');
                                    $typeBadge = [
                                        'mou' => ['bg-indigo-100 text-indigo-800', 'Bilateral MoU'],
                                        'authorization' => ['bg-teal-100 text-teal-800', 'Authorization Letter'],
                                        'service_agreement' => ['bg-blue-100 text-blue-800', 'Service Agreement'],
                                        'partnership' => ['bg-purple-100 text-purple-800', 'CSR Partnership']
                                    ][$pAgType] ?? ['bg-gray-100 text-gray-800', 'Agreement'];
                                ?>
                                <div class="p-4 rounded-xl border <?php echo $isAck ? 'border-emerald-200 bg-emerald-50/30 dark:border-emerald-900/40 dark:bg-emerald-950/10' : 'border-amber-300 bg-amber-50/40 dark:border-amber-900/40 dark:bg-amber-950/10 shadow-sm'; ?> transition-all">
                                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                        <div class="space-y-1.5 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-gray-800 dark:text-gray-200">
                                                    <?php echo htmlspecialchars((string)$pAg['agreement_no']); ?>
                                                </span>
                                                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold <?php echo $typeBadge[0]; ?>">
                                                    <?php echo $typeBadge[1]; ?>
                                                </span>
                                                <?php if ($isAck): ?>
                                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300 flex items-center gap-1">
                                                        <i class="fa-solid fa-circle-check"></i> Digitally Acknowledged
                                                    </span>
                                                <?php else: ?>
                                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-500 text-white animate-pulse flex items-center gap-1 shadow-xs">
                                                        <i class="fa-solid fa-bell"></i> Action Required: Sign MoU
                                                    </span>
                                                <?php endif; ?>
                                            </div>

                                            <h4 class="font-bold text-gray-900 dark:text-white text-sm">
                                                <?php echo htmlspecialchars((string)$pAg['title']); ?>
                                            </h4>

                                            <div class="text-xs text-gray-600 dark:text-gray-300 flex flex-wrap items-center gap-y-1 gap-x-4">
                                                <span><strong>Partner:</strong> <?php echo htmlspecialchars((string)$pAg['partner_name']); ?></span>
                                                <span><strong>Date:</strong> <?php echo date('d M Y', strtotime((string)$pAg['signed_date'])); ?></span>
                                                <?php if (!empty($pAg['valid_until'])): ?>
                                                    <span><strong>Valid Until:</strong> <?php echo date('d M Y', strtotime((string)$pAg['valid_until'])); ?></span>
                                                <?php endif; ?>
                                            </div>

                                            <?php if ($isAck): ?>
                                                <div class="text-[11px] text-emerald-700 dark:text-emerald-400 bg-emerald-100/60 dark:bg-emerald-900/30 px-3 py-1.5 rounded-lg inline-flex items-center gap-2 mt-1">
                                                    <i class="fa-solid fa-fingerprint"></i>
                                                    <span>
                                                        Digitally acknowledged by <strong><?php echo htmlspecialchars((string)($pAg['acknowledged_name'] ?: $pAg['partner_name'])); ?></strong>
                                                        <?php if (!empty($pAg['acknowledged_at'])): ?>
                                                            on <?php echo date('d M Y, h:i A', strtotime((string)$pAg['acknowledged_at'])); ?>
                                                        <?php endif; ?>
                                                        <?php if (!empty($pAg['acknowledged_ip'])): ?>
                                                            (IP: <?php echo htmlspecialchars((string)$pAg['acknowledged_ip']); ?>)
                                                        <?php endif; ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Actions -->
                                        <div class="flex items-center gap-2 flex-shrink-0">
                                            <?php if ($isAck): ?>
                                                <a href="../view-agreement.php?id=<?php echo (int)$pAg['id']; ?>" 
                                                   class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition-all shadow-sm">
                                                    <i class="fa-solid fa-eye"></i>
                                                    <span>View MoU</span>
                                                </a>
                                                <a href="../actions/agreement_logic.php?action=download_agreement&id=<?php echo (int)$pAg['id']; ?>" 
                                                   class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl bg-white hover:bg-gray-100 border border-gray-200 text-gray-700 text-xs font-semibold transition-all">
                                                    <i class="fa-solid fa-download text-emerald-600"></i>
                                                    <span>Download PDF</span>
                                                </a>
                                            <?php else: ?>
                                                <a href="../view-agreement.php?id=<?php echo (int)$pAg['id']; ?>" 
                                                   class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 via-blue-600 to-indigo-700 hover:from-indigo-700 hover:to-blue-800 text-white text-xs font-black uppercase tracking-wider transition-all shadow-md shadow-indigo-500/25 ring-2 ring-indigo-300 ring-offset-1">
                                                    <i class="fa-solid fa-file-signature text-sm"></i>
                                                    <span>Review & Sign Online ("I Agree")</span>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <?php foreach ($doctorAgreements as $dAg): ?>
                                <div class="p-4 rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                        <div class="space-y-1">
                                            <div class="flex items-center gap-2">
                                                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-teal-100 text-teal-800">
                                                    Healthcare Partner Agreement
                                                </span>
                                                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800">
                                                    <?php echo htmlspecialchars((string)($dAg['status'] ?? 'Active')); ?>
                                                </span>
                                            </div>
                                            <h4 class="font-bold text-gray-900 dark:text-white text-sm">
                                                <?php echo htmlspecialchars((string)($dAg['hospital_name'] ?? 'Healthcare Facility')); ?>
                                            </h4>
                                            <p class="text-xs text-gray-500">
                                                Signed: <?php echo !empty($dAg['signed_date']) ? date('d M Y', strtotime((string)$dAg['signed_date'])) : '-'; ?>
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <?php if (!empty($dAg['agreement_doc_path'])): ?>
                                                <a href="../<?php echo htmlspecialchars((string)$dAg['agreement_doc_path']); ?>" target="_blank" class="px-3.5 py-2 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold transition">
                                                    <i class="fa-solid fa-file-pdf text-red-500 mr-1"></i> View Agreement
                                                </a>
                                            <?php endif; ?>
                                            <a href="../actions/doctor_agreement_logic.php?action=generate_doctor_cert&id=<?php echo (int)$dAg['id']; ?>" class="px-3.5 py-2 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold transition shadow-xs">
                                                <i class="fa-solid fa-certificate mr-1"></i> Doctor Certificate
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

               <div class="rounded-lg border p-4 mb-6">
    <h3 class="font-semibold text-gray-800 mb-3">Referral Links</h3>

    <div class="space-y-4">

        <!-- Membership Referral -->
        <div>
            <div class="flex items-center justify-between gap-3 mb-1">
                <p class="text-sm text-gray-600">Membership Referral</p>

                <button type="button"
                    class="share-referral-btn inline-flex items-center justify-center rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700"
                    data-label="Membership referral"
                    data-link="<?php echo htmlspecialchars($membershipReferralLink); ?>">
                    Share
                </button>
            </div>

            <input readonly
                class="w-full border rounded p-2 text-sm"
                value="<?php echo htmlspecialchars($membershipReferralLink); ?>">
        </div>

        <!-- Donation Referral -->
        <div>
            <div class="flex items-center justify-between gap-3 mb-1">
                <p class="text-sm text-gray-600">Donation Referral</p>

                <button type="button"
                    class="share-referral-btn inline-flex items-center justify-center rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700"
                    data-label="Donation referral"
                    data-link="<?php echo htmlspecialchars($donationReferralLink); ?>">
                    Share
                </button>
            </div>

            <input readonly
                class="w-full border rounded p-2 text-sm"
                value="<?php echo htmlspecialchars($donationReferralLink); ?>">
        </div>

    </div>
</div>

                <?php if (!empty($upcomingEvents)): ?>
                    <div class="rounded-lg border p-4 mb-6">
                        <div class="flex items-center justify-between gap-4 mb-3">
                            <h3 class="font-semibold text-gray-800">Upcoming Events</h3>
                            <a href="events.php" class="text-xs text-blue-700 hover:underline">View all</a>
                        </div>
                        <div class="space-y-3">
                            <?php foreach ($upcomingEvents as $e): ?>
                                <a href="event-details.php?id=<?php echo (int)$e['id']; ?>" class="block border rounded-lg p-3 bg-gray-50 hover:bg-gray-100 transition">
                                    <div class="flex items-start justify-between gap-3">
                                        <p class="font-semibold text-gray-900"><?php echo htmlspecialchars((string)$e['title']); ?></p>
                                        <p class="text-xs text-gray-500"><?php echo !empty($e['event_date']) ? htmlspecialchars(date('d M Y', strtotime((string)$e['event_date']))) : '-'; ?></p>
                                    </div>
                                    <?php if (!empty($e['location'])): ?>
                                        <p class="text-xs text-gray-600 mt-1"><?php echo htmlspecialchars((string)$e['location']); ?></p>
                                    <?php endif; ?>
                                    <p class="text-xs text-gray-500 mt-1">Status: <?php echo htmlspecialchars((string)($e['status'] ?? '')); ?></p>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="rounded-lg border p-4 mb-6">
                    <h3 class="font-semibold text-gray-800 mb-3">Update Profile</h3>
                    <form action="process/update_member_profile.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">
                        <div class="md:col-span-2">
                            <label class="text-xs text-gray-600">Full Name</label>
                            <input type="text" readonly class="w-full border rounded-lg p-3 bg-gray-100" value="<?php echo htmlspecialchars((string)($member['full_name'] ?? '')); ?>">
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">Email</label>
                            <input type="email" readonly class="w-full border rounded-lg p-3 bg-gray-100" value="<?php echo htmlspecialchars((string)($member['email'] ?? '')); ?>">
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">Phone *</label>
                            <input type="tel" name="phone" required class="w-full border rounded-lg p-3" value="<?php echo htmlspecialchars((string)($member['phone'] ?? '')); ?>">
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">Date of Birth</label>
                            <input type="date" name="dob" class="w-full border rounded-lg p-3" value="<?php echo htmlspecialchars((string)($member['dob'] ?? '')); ?>">
                        </div>
                        <div>
                            <label class="text-xs text-gray-600">Gender</label>
                            <select name="gender" class="w-full border rounded-lg p-3">
                                <?php
                                $g = (string)($member['gender'] ?? '');
                                $opts = ['' => 'Prefer not to say', 'Male' => 'Male', 'Female' => 'Female', 'Other' => 'Other'];
                                foreach ($opts as $val => $label):
                                ?>
                                    <option value="<?php echo htmlspecialchars($val); ?>" <?php echo $g === $val ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-xs text-gray-600">Address</label>
                            <textarea name="address" rows="3" class="w-full border rounded-lg p-3"><?php echo htmlspecialchars((string)($member['address'] ?? '')); ?></textarea>
                        </div>
                        <div class="md:col-span-2">
                            <button class="bg-gray-900 hover:bg-gray-800 text-white px-4 py-3 rounded-lg font-semibold w-full">Save Profile</button>
                        </div>
                    </form>
                </div>

                <div class="rounded-lg border p-4 mb-6">
                    <h3 class="font-semibold text-gray-800 mb-3">Documents</h3>
                    <div class="flex flex-wrap gap-2">
                        <a target="_blank" href="download-member-receipt.php?member_no=<?php echo urlencode($member['member_no']); ?>&email=<?php echo urlencode($member['email']); ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded text-sm">Download Receipt PDF</a>
                        <a target="_blank" href="download-member-idcard.php" class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-2 rounded text-sm">Download ID Card</a>
                    </div>
                </div>

                <!-- My Auto Pay & Recurring Giving -->
                <div class="rounded-xl border border-teal-200 bg-gradient-to-r from-teal-50/60 to-emerald-50/60 p-5 mb-6">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-[#0F8B8D] text-white flex items-center justify-center text-lg">
                                <i class="fa-solid fa-arrows-rotate"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-800 text-base">My Auto Pay & Recurring Donations</h3>
                                <p class="text-xs text-gray-600">Manage monthly mandates, pause/resume auto-debit, and download 80G tax receipts.</p>
                            </div>
                        </div>
                        <a href="donor-history.php" class="bg-[#0F8B8D] hover:bg-[#0c7274] text-white font-bold px-4 py-2.5 rounded-xl text-xs flex items-center gap-2 shadow-sm transition">
                            <i class="fa-solid fa-sliders"></i> Manage Auto Pay
                        </a>
                    </div>
                </div>

                <div class="rounded-lg border p-4 mb-6">
                    <div class="flex items-center justify-between gap-4 mb-3">
                        <h3 class="font-semibold text-gray-800">Submit an Inquiry</h3>
                        <a href="inquiry.php" class="text-xs text-blue-700 hover:underline">Open full inquiry page</a>
                    </div>
                    <form action="process/submit_inquiry.php" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">
                        <input type="hidden" name="return_to" value="member_dashboard">
                        <div class="md:col-span-2">
                            <input type="text" name="name" required class="w-full border rounded-lg p-3" value="<?php echo htmlspecialchars($member['full_name'] ?? ''); ?>" placeholder="Full Name *">
                        </div>
                        <div>
                            <input type="email" name="email" required class="w-full border rounded-lg p-3" value="<?php echo htmlspecialchars($member['email'] ?? ''); ?>" placeholder="Email *">
                        </div>
                        <div>
                            <input type="tel" name="phone" required maxlength="10" pattern="^[6-9][0-9]{9}$" title="Phone number must start with 6, 7, 8 or 9 and be exactly 10 digits" class="w-full border rounded-lg p-3" value="<?php echo htmlspecialchars($member['phone'] ?? ''); ?>" placeholder="Phone *">
                        </div>
                        <div class="md:col-span-2">
                            <textarea name="problem" rows="4" required class="w-full border rounded-lg p-3" placeholder="Describe your problem or suggestion *"></textarea>
                        </div>
                        <div>
                            <select name="category" required class="w-full border rounded-lg p-3">
                                <option value="">Category *</option>
                                <option value="membership">Membership Issue</option>
                                <option value="donation">Donation Problem</option>
                                <option value="volunteer">Volunteer Related</option>
                                <option value="event">Event Registration</option>
                                <option value="general">General Inquiry</option>
                            </select>
                        </div>
                        <div>
                            <select name="urgency" class="w-full border rounded-lg p-3">
                                <option value="normal">Normal</option>
                                <option value="urgent">Urgent</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-sm text-gray-500">
                        </div>
                        <div class="md:col-span-2">
                            <button class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-3 rounded-lg font-semibold w-full">Submit Inquiry</button>
                        </div>
                    </form>
                </div>

                <div class="rounded-lg border p-4 mb-6">
                    <h3 class="font-semibold text-gray-800 mb-3">Your Inquiries</h3>
                    <?php if (!empty($inquiries)): ?>
                        <div class="space-y-3">
                            <?php foreach ($inquiries as $inq): ?>
                                <div class="border rounded-lg p-3 bg-gray-50">
                                    <div class="flex items-start justify-between gap-3">
                                        <p class="text-sm font-semibold text-gray-800">#<?php echo (int)$inq['id']; ?> • <?php echo htmlspecialchars(($inq['category'] ?? '') !== '' ? $inq['category'] : 'General'); ?></p>
                                        <p class="text-xs text-gray-500"><?php echo date('d M Y H:i', strtotime((string)$inq['created_at'])); ?></p>
                                    </div>
                                    <p class="text-sm text-gray-700 mt-1"><?php echo nl2br(htmlspecialchars((string)($inq['problem_description'] ?? ''))); ?></p>
                                    <div class="flex items-center gap-2 mt-2 text-xs">
                                        <span class="px-2 py-1 rounded-full bg-slate-100 text-slate-700"><?php echo htmlspecialchars((string)($inq['status'] ?? 'New')); ?></span>
                                        <span class="px-2 py-1 rounded-full bg-amber-50 text-amber-700"><?php echo htmlspecialchars((string)($inq['urgency'] ?? 'normal')); ?></span>
                                        <?php if (!empty($inq['attachment_path'])): ?>
                                            <a target="_blank" class="text-blue-700 hover:underline ml-auto" href="<?php echo htmlspecialchars((string)$inq['attachment_path']); ?>">Attachment</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-sm text-gray-500">No inquiries found.</p>
                    <?php endif; ?>
                </div>

                <div class="rounded-lg border p-4">
                    <h3 class="font-semibold text-gray-800 mb-3">Messages</h3>
                    <div class="space-y-3">
                        <?php foreach ($messages as $msg): ?>
                            <div class="border rounded-lg p-3 bg-gray-50">
                                <div class="flex justify-between gap-3 items-start">
                                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($msg['subject']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo date('d M Y H:i', strtotime($msg['created_at'])); ?></p>
                                </div>
                                <p class="text-xs text-gray-500 mb-1"><?php echo htmlspecialchars($msg['message_type']); ?></p>
                                <p class="text-sm text-gray-700"><?php echo nl2br(htmlspecialchars($msg['message_body'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($messages)): ?>
                            <p class="text-sm text-gray-500">No messages found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-700">
                    Member account not found.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    (function () {
        const buttons = document.querySelectorAll('.share-referral-btn');
        if (!buttons.length) return;

        async function copyText(text) {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(text);
                return;
            }

            const temp = document.createElement('textarea');
            temp.value = text;
            temp.setAttribute('readonly', '');
            temp.style.position = 'absolute';
            temp.style.left = '-9999px';
            document.body.appendChild(temp);
            temp.select();
            document.execCommand('copy');
            document.body.removeChild(temp);
        }

        buttons.forEach((button) => {
            button.addEventListener('click', async function () {
                const link = this.dataset.link || '';
                const label = this.dataset.label || 'Referral';
                if (!link) return;

                try {
                    if (navigator.share) {
                        await navigator.share({
                            title: label,
                            text: link,
                            url: link
                        });
                    } else {
                        await copyText(link);
                        alert(label + ' link copied.');
                    }
                } catch (error) {
                    if (error && error.name === 'AbortError') {
                        return;
                    }

                    try {
                        await copyText(link);
                        alert(label + ' link copied.');
                    } catch (copyError) {
                        alert('Unable to share this link right now.');
                    }
                }
            });
        });
    })();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
