<?php 
require 'includes/header.php'; 

$isItem = (isset($_GET['type']) && $_GET['type'] === 'item');
$pledgeCode = htmlspecialchars(trim((string)($_GET['code'] ?? '')), ENT_QUOTES, 'UTF-8');

$itemRecord = null;
$receiptUrl = '';
$verifyUrl = '';

if ($isItem && !empty($pledgeCode)) {
    try {
        $st = $pdo->prepare("SELECT id, donation_code, receipt_no, donor_email, created_at FROM item_donations WHERE donation_code = ? LIMIT 1");
        $st->execute([$pledgeCode]);
        $itemRecord = $st->fetch(PDO::FETCH_ASSOC);
        if ($itemRecord) {
            $token = generateItemDonationReceiptToken(
                $itemRecord['id'],
                $itemRecord['donation_code'],
                $itemRecord['receipt_no'] ?: ('RCP-ITM-' . $itemRecord['id']),
                $itemRecord['donor_email'],
                $itemRecord['created_at']
            );
            $receiptUrl = 'download-item-receipt.php?id=' . (int)$itemRecord['id'] . '&token=' . urlencode($token);
            $verifyUrl = 'verify-item.php?code=' . urlencode($itemRecord['donation_code']) . '&token=' . urlencode($token);
        }
    } catch (Throwable $e) {
        // graceful fallback
    }
}
?>

<div class="bg-gray-50 min-h-screen flex items-center justify-center px-4 py-12">
    <div class="bg-white max-w-lg w-full rounded-2xl shadow-xl border border-gray-100 p-8 text-center transform transition-all hover:scale-[1.01]">

        <div class="mx-auto w-20 h-20 <?php echo $isItem ? 'bg-teal-100 ring-teal-50 text-[#0F8B8D]' : 'bg-green-100 ring-green-50 text-green-500'; ?> rounded-full flex items-center justify-center mb-6 ring-8 text-3xl">
            <?php if ($isItem): ?>
                <i class="fa-solid fa-gift"></i>
            <?php else: ?>
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            <?php endif; ?>
        </div>

        <h1 class="text-3xl font-bold text-gray-800">
            <?php echo $isItem ? 'Item Pledge Received!' : 'Thank You for Your Donation!'; ?>
        </h1>

        <?php if ($isItem && $pledgeCode !== ''): ?>
            <div class="my-3 inline-block px-4 py-2 bg-teal-50 border border-teal-200 rounded-xl text-xs font-mono font-bold text-[#0F8B8D]">
                Pledge Tracking Code: <?php echo $pledgeCode; ?>
            </div>

            <?php if (!empty($receiptUrl)): ?>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-2.5 my-4">
                    <a href="<?php echo htmlspecialchars($receiptUrl); ?>" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-[#0F8B8D] hover:bg-[#0c7274] text-white rounded-xl text-xs font-bold shadow-md transition">
                        <i class="fa-solid fa-file-pdf"></i> Download Receipt (PDF)
                    </a>
                    <a href="<?php echo htmlspecialchars($verifyUrl); ?>" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition">
                        <i class="fa-solid fa-qrcode"></i> Verify Digital QR
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <p class="text-gray-600 mt-2 leading-relaxed text-sm">
            <?php if ($isItem): ?>
                Your generous item donation pledge has been recorded. Our logistics coordinator will reach out to you shortly to arrange pickup or guide you to the nearest drop-off point.
            <?php else: ?>
                Your support is invaluable to us. We have received your donation details and will send a confirmation email once the verification is complete.
            <?php endif; ?>
        </p>

        <div class="mt-8 border-t border-gray-200 pt-6">
            <p class="text-sm font-medium text-gray-700 mb-4">Share our cause and inspire others!</p>
            <div class="flex justify-center gap-4">
                <?php
                $shareText = urlencode("I just supported a great cause! You can too.");
                $shareUrl = urlencode("http://" . $_SERVER['HTTP_HOST']);

                $socials = [
                    'Facebook' => ['url' => "https://www.facebook.com/sharer/sharer.php?u=$shareUrl&quote=$shareText", 'icon' => 'fa-brands fa-facebook-f', 'color' => 'bg-blue-600 hover:bg-blue-700'],
                    'Twitter' => ['url' => "https://twitter.com/intent/tweet?url=$shareUrl&text=$shareText", 'icon' => 'fa-brands fa-twitter', 'color' => 'bg-sky-500 hover:bg-sky-600'],
                    'WhatsApp' => ['url' => "https://api.whatsapp.com/send?text=$shareText $shareUrl", 'icon' => 'fa-brands fa-whatsapp', 'color' => 'bg-green-500 hover:bg-green-600']
                ];

                foreach ($socials as $name => $data):
                ?>
                    <a href="<?php echo $data['url']; ?>" target="_blank"
                        class="w-12 h-12 flex items-center justify-center rounded-full text-white shadow-md transition transform hover:scale-110 <?php echo $data['color']; ?>">
                        <i class="<?php echo $data['icon']; ?> text-xl"></i>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="mt-10">
            <a href="index.php" class="inline-flex items-center gap-2 text-gray-600 hover:text-blue-600 font-medium transition group">
                <svg class="w-4 h-4 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                <span>Back to Home</span>
            </a>
        </div>
    </div>
</div>

<?php require 'includes/footer.php'; ?>