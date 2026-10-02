<?php
// ============================================================
// verify-item.php
// Public QR verification endpoint for Item Donation Receipts
// ============================================================

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';

$donationCode = cleanInput($_GET['code'] ?? '');
$token = cleanInput($_GET['token'] ?? '');
$record = null;
$isValid = false;

if (!empty($donationCode)) {
    $stmt = $pdo->prepare("
        SELECT i.*, c.category_name, c.category_icon, p.title AS project_title 
        FROM item_donations i
        LEFT JOIN item_donation_categories c ON i.category_id = c.id
        LEFT JOIN projects p ON i.project_id = p.id
        WHERE i.donation_code = ?
        LIMIT 1
    ");
    $stmt->execute([$donationCode]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($record) {
        $receiptNo = $record['receipt_no'] ?: ('RCP-ITM-' . $record['id']);
        if (!empty($token)) {
            $isValid = isValidItemDonationReceiptToken(
                $record['id'],
                $record['donation_code'],
                $receiptNo,
                $record['donor_email'],
                $record['created_at'],
                $token
            );
        } else {
            // Valid if matched directly by unique code
            $isValid = true;
            $token = generateItemDonationReceiptToken($record['id'], $record['donation_code'], $receiptNo, $record['donor_email'], $record['created_at']);
        }
    }
}

$siteName = (string)($settings['site_name'] ?? 'NGO System');
?>

<div class="min-h-screen bg-slate-50 dark:bg-gray-900 py-10 md:py-16 px-4">
    <div class="max-w-3xl mx-auto">
        
        <div class="rounded-3xl border border-slate-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-xl overflow-hidden">
            
            <!-- Header Bar -->
            <div class="bg-gradient-to-r from-[#0F8B8D] to-[#0c7274] px-6 py-5 md:px-8 text-white">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <p class="text-xs uppercase tracking-[0.25em] text-teal-100 font-bold">Secure Digital Verification</p>
                        <h1 class="text-2xl font-black mt-1"><?php echo htmlspecialchars($siteName); ?></h1>
                    </div>
                    
                    <div>
                        <?php if ($isValid && $record): ?>
                            <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-white text-emerald-700 text-xs font-black shadow-sm uppercase tracking-wider">
                                <i class="fa-solid fa-circle-check text-emerald-500"></i> Verified Item Receipt
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-rose-100 text-rose-700 text-xs font-black shadow-sm uppercase tracking-wider">
                                <i class="fa-solid fa-triangle-exclamation"></i> Unverified Record
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Body Content -->
            <div class="p-6 md:p-8">
                <?php if ($isValid && $record): ?>
                    
                    <div class="rounded-2xl border border-teal-200 bg-teal-50/50 dark:bg-teal-900/20 dark:border-teal-800 p-5 mb-6">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-[#0F8B8D] text-white flex items-center justify-center text-xl flex-shrink-0 shadow-inner">
                                <i class="fa-solid <?php echo htmlspecialchars($record['category_icon'] ?: 'fa-gift'); ?>"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <h2 class="text-xl font-bold text-gray-800 dark:text-white">
                                        <?php echo htmlspecialchars($record['donor_name']); ?>
                                    </h2>
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-100 text-emerald-700">
                                        <?php echo htmlspecialchars($record['status']); ?>
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    In-kind item contribution verified and logged on 
                                    <strong class="text-gray-700 dark:text-gray-300"><?php echo date('d F, Y', strtotime($record['donation_date'])); ?></strong>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Details Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-6 text-xs">
                        <div class="p-4 rounded-2xl bg-gray-50 dark:bg-gray-700/50 border dark:border-gray-600">
                            <span class="text-gray-400 uppercase tracking-wider block font-bold text-[10px]">Pledge Tracking Code</span>
                            <p class="font-mono text-sm font-black text-[#0F8B8D] mt-1"><?php echo htmlspecialchars($record['donation_code']); ?></p>
                        </div>

                        <div class="p-4 rounded-2xl bg-gray-50 dark:bg-gray-700/50 border dark:border-gray-600">
                            <span class="text-gray-400 uppercase tracking-wider block font-bold text-[10px]">Official Receipt No</span>
                            <p class="font-mono text-sm font-bold text-gray-800 dark:text-white mt-1"><?php echo htmlspecialchars($record['receipt_no'] ?: 'N/A'); ?></p>
                        </div>

                        <div class="p-4 rounded-2xl bg-gray-50 dark:bg-gray-700/50 border dark:border-gray-600">
                            <span class="text-gray-400 uppercase tracking-wider block font-bold text-[10px]">Item Category</span>
                            <p class="text-sm font-bold text-gray-800 dark:text-white mt-1"><?php echo htmlspecialchars($record['category_name'] ?: 'Essential Goods'); ?></p>
                        </div>

                        <div class="p-4 rounded-2xl bg-gray-50 dark:bg-gray-700/50 border dark:border-gray-600">
                            <span class="text-gray-400 uppercase tracking-wider block font-bold text-[10px]">Quantity & Unit</span>
                            <p class="text-sm font-black text-emerald-600 mt-1">
                                <?php echo number_format((float)$record['quantity'], 2) . ' ' . htmlspecialchars($record['unit']); ?>
                                <span class="text-xs text-gray-500 font-normal">(<?php echo htmlspecialchars($record['condition_type']); ?>)</span>
                            </p>
                        </div>

                        <div class="sm:col-span-2 p-4 rounded-2xl bg-gray-50 dark:bg-gray-700/50 border dark:border-gray-600">
                            <span class="text-gray-400 uppercase tracking-wider block font-bold text-[10px]">Item Description</span>
                            <p class="text-xs text-gray-700 dark:text-gray-200 mt-1"><?php echo nl2br(htmlspecialchars($record['item_description'])); ?></p>
                        </div>

                        <?php if (!empty($record['estimated_value']) && (float)$record['estimated_value'] > 0): ?>
                            <div class="p-4 rounded-2xl bg-gray-50 dark:bg-gray-700/50 border dark:border-gray-600">
                                <span class="text-gray-400 uppercase tracking-wider block font-bold text-[10px]">Estimated Valuation</span>
                                <p class="text-sm font-bold text-gray-800 dark:text-white mt-1">₹<?php echo number_format((float)$record['estimated_value'], 2); ?></p>
                            </div>
                        <?php endif; ?>

                        <div class="p-4 rounded-2xl bg-gray-50 dark:bg-gray-700/50 border dark:border-gray-600">
                            <span class="text-gray-400 uppercase tracking-wider block font-bold text-[10px]">Collection Location</span>
                            <p class="text-xs text-gray-800 dark:text-white mt-1"><?php echo htmlspecialchars($record['pickup_city'] . ' (' . $record['pickup_pincode'] . ')'); ?></p>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <a href="download-item-receipt.php?code=<?php echo urlencode($record['donation_code']); ?>&token=<?php echo urlencode($token); ?>" 
                           class="w-full sm:w-auto bg-[#0F8B8D] hover:bg-[#0c7274] text-white px-6 py-3 rounded-xl font-bold text-xs flex items-center justify-center gap-2 shadow-md transition">
                            <i class="fa-solid fa-file-pdf"></i> Download Official PDF Receipt
                        </a>
                        
                        <a href="index.php" class="text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 font-semibold">
                            ← Return to Home
                        </a>
                    </div>

                <?php else: ?>
                    
                    <div class="text-center py-10">
                        <div class="w-16 h-16 rounded-2xl bg-rose-50 text-rose-500 flex items-center justify-center mx-auto mb-4 text-2xl">
                            <i class="fa-solid fa-circle-xmark"></i>
                        </div>
                        <h2 class="text-xl font-bold text-gray-800 dark:text-white">Receipt Record Not Found</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 max-w-sm mx-auto mt-2 mb-6">
                            The item donation receipt code or token provided could not be verified in our records.
                        </p>
                        <a href="index.php" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gray-900 text-white rounded-xl text-xs font-bold">
                            Return to Website
                        </a>
                    </div>

                <?php endif; ?>
            </div>

        </div>

    </div>
</div>

<?php require 'includes/footer.php'; ?>
