<?php
// ============================================================
// verify-health-card.php
// Public Health Card Verification Gateway for QR Scans & Hospitals
// ============================================================

require_once 'config/db.php';
require_once 'includes/functions.php';

$cardNo = cleanInput($_GET['card'] ?? '');
$card = null;
if (!empty($cardNo) && dbTableExists($pdo, 'health_cards')) {
    $stmt = $pdo->prepare("SELECT * FROM health_cards WHERE card_number = ? LIMIT 1");
    $stmt->execute([$cardNo]);
    $card = $stmt->fetch(PDO::FETCH_ASSOC);
}

require 'includes/header.php';
?>

<div class="bg-gradient-to-b from-[#F0FDFD] via-white to-[#F8FAFC] min-h-screen py-10 md:py-16">
    <div class="container mx-auto px-4 max-w-2xl">
        
        <!-- Verification Box -->
        <div class="bg-white rounded-3xl shadow-2xl border border-teal-50 overflow-hidden text-center p-6 md:p-10">
            <?php if ($card): ?>
                <?php $isActive = in_array($card['status'], ['active', 'renewed'], true); ?>
                
                <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl shadow-inner <?php echo $isActive ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600'; ?>">
                    <i class="fa-solid <?php echo $isActive ? 'fa-circle-check' : 'fa-triangle-exclamation'; ?>"></i>
                </div>

                <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider <?php echo $isActive ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'; ?>">
                    <?php echo $isActive ? 'Official Verified Health Card' : 'Card ' . ucfirst($card['status']); ?>
                </span>

                <h1 class="text-2xl md:text-3xl font-black text-gray-900 mt-4 tracking-tight">
                    <?php echo htmlspecialchars((string)$card['applicant_name']); ?>
                </h1>
                
                <p class="font-mono text-base font-bold text-teal-700 mt-1">
                    <?php echo htmlspecialchars((string)$card['card_number']); ?>
                </p>

                <!-- Details Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 my-6 p-4 rounded-2xl bg-gray-50 border border-gray-100 text-left text-xs">
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400">Gender / Age</p>
                        <p class="font-semibold text-gray-800 mt-0.5"><?php echo htmlspecialchars((string)$card['gender']) . (!empty($card['age']) ? (' / ' . $card['age'] . ' Y') : ''); ?></p>
                    </div>

                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400">Blood Group</p>
                        <p class="font-bold text-rose-600 mt-0.5"><?php echo htmlspecialchars((string)($card['blood_group'] ?: 'N/A')); ?></p>
                    </div>

                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400">Status</p>
                        <p class="font-bold <?php echo $isActive ? 'text-emerald-700' : 'text-rose-700'; ?> mt-0.5"><?php echo strtoupper((string)$card['status']); ?></p>
                    </div>

                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400">Issue Date</p>
                        <p class="font-semibold text-gray-800 mt-0.5"><?php echo htmlspecialchars(date('d M Y', strtotime((string)$card['issue_date']))); ?></p>
                    </div>

                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400">Valid Until</p>
                        <p class="font-semibold text-gray-800 mt-0.5"><?php echo htmlspecialchars(date('d M Y', strtotime((string)$card['expiry_date']))); ?></p>
                    </div>

                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400">Location</p>
                        <p class="font-semibold text-gray-800 mt-0.5 truncate"><?php echo htmlspecialchars(($card['district'] ? $card['district'] . ', ' : '') . ($card['state'] ?: 'India')); ?></p>
                    </div>
                </div>

                <!-- Hospital Notice -->
                <div class="p-4 rounded-2xl bg-teal-50/70 border border-teal-100 text-left text-xs text-teal-900 mb-6">
                    <p class="font-bold flex items-center gap-1.5 text-teal-800">
                        <i class="fa-solid fa-hospital-user"></i> Hospital & Clinic Verification Note
                    </p>
                    <p class="mt-1 leading-relaxed">
                        This cardholder is a registered beneficiary under the NGO Community Health Mission and is entitled to subsidized OPD, free diagnostic camps, cataract surgery assistance, and pharmacy discounts at empaneled healthcare centers.
                    </p>
                </div>

                <!-- Actions -->
                <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                    <a href="<?php echo cleanUrl('download-health-card.php?card=' . urlencode($card['card_number'])); ?>" target="_blank" class="w-full sm:w-auto px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold rounded-xl shadow transition-colors flex items-center justify-center gap-2">
                        <i class="fa-solid fa-file-pdf"></i>
                        <span>Download Health Card PDF</span>
                    </a>

                    <a href="<?php echo cleanUrl('healthcare-directory.php'); ?>" class="w-full sm:w-auto px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-xl transition-colors">
                        Browse Hospital Network
                    </a>
                </div>

            <?php else: ?>
                <div class="w-16 h-16 bg-gray-100 text-gray-400 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>

                <h2 class="text-xl font-bold text-gray-900">Verify Health Card</h2>
                <p class="text-xs text-gray-500 mt-1">Enter a Health Card Number to verify authenticity.</p>

                <form method="GET" action="verify-health-card.php" class="mt-6 flex flex-col sm:flex-row gap-2.5 max-w-md mx-auto">
                    <input type="text" name="card" required placeholder="e.g. HC-2026-0001" class="flex-1 px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 focus:outline-none focus:ring-2 focus:ring-teal-500 font-mono">
                    <button type="submit" class="px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold rounded-xl shadow-xs transition-colors">
                        Verify Card
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require 'includes/footer.php'; ?>
