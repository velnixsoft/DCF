<?php
session_start();
require 'config/db.php';
require 'includes/functions.php';

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: admin/');
    exit;
}

if (normalizeHierarchyRole($_SESSION['hierarchy_level'] ?? '') !== 'field_agent') {
    header('Location: admin/dashboard.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];

// Get agent info
$agentStmt = $pdo->prepare('SELECT id FROM field_agents WHERE user_id = ?');
$agentStmt->execute([$userId]);
$agentId = $agentStmt->fetchColumn();

if (!$agentId) {
    die('Agent profile not found.');
}

$message = '';
$success = false;
$receiptLink = '';
$whatsAppLink = '';
$emailShareLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $donorName = cleanInput($_POST['donor_name']);
    $donorPhone = cleanInput($_POST['donor_phone']);
    $donorEmail = cleanInput($_POST['donor_email'] ?? '');
    $donorAddress = cleanInput($_POST['donor_address']);
    $amount = (float)$_POST['amount'];
    $paymentMode = $_POST['payment_mode'] ?? 'cash';

    if ($donorName && $donorPhone && $amount > 0) {
        $receiptNo = generateNextDonationReceiptNumber($pdo);
        $paymentStatus = $paymentMode === 'online' ? 'Success' : 'Pending';

        // Insert donation
        $stmt = $pdo->prepare('
            INSERT INTO donations (donor_name, donor_mobile, donor_email, donor_address, amount, payment_gateway, payment_status, field_agent_id, payment_mode_field, receipt_no) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        if ($stmt->execute([$donorName, $donorPhone, $donorEmail, $donorAddress, $amount, 'Manual (Agent)', $paymentStatus, $agentId, $paymentMode, $receiptNo])) {
            $donationId = $pdo->lastInsertId();

            if ($paymentMode === 'cash') {
                // Track cash deposit compliance only for cash collections.
                $deadline = date('Y-m-d', strtotime('+7 days'));
                $cashStmt = $pdo->prepare('
                    INSERT INTO cash_deposits (donation_id, agent_id, collected_amount, deposit_deadline) 
                    VALUES (?, ?, ?, ?)
                ');
                $cashStmt->execute([$donationId, $agentId, $amount, $deadline]);
                $message = 'Donation recorded. Cash deposit due by ' . date('d M Y', strtotime($deadline)) . '.';
            } else {
                $message = 'Online donation recorded successfully.';
            }

            $createdAtStmt = $pdo->prepare('SELECT created_at FROM donations WHERE id = ? LIMIT 1');
            $createdAtStmt->execute([$donationId]);
            $createdAt = (string)($createdAtStmt->fetchColumn() ?: '');
            $token = generateDonationReceiptToken($donationId, $receiptNo, $donorEmail, $amount, $createdAt);
            $receiptLink = 'download-receipt.php?id=' . urlencode((string)$donationId) . '&token=' . urlencode($token);
            $whatsAppMessage = rawurlencode('Thank you for supporting our NGO. Your receipt: ' . appBaseUrl() . '/' . $receiptLink);
            $whatsAppLink = 'https://wa.me/?text=' . $whatsAppMessage;
            $emailSubject = rawurlencode('Your Donation Receipt - ' . $receiptNo);
            $emailBody = rawurlencode("Thank you for your donation.\n\nDownload your receipt here:\n" . appBaseUrl() . '/' . $receiptLink);
            $emailShareLink = 'mailto:' . urlencode($donorEmail) . '?subject=' . $emailSubject . '&body=' . $emailBody;
            $success = true;
        } else {
            $message = 'Failed to record donation.';
        }
    } else {
        $message = 'Please fill all fields.';
    }
}

require 'includes/header.php';
?>
<div class="min-h-screen bg-gradient-to-br from-green-50 to-emerald-50 py-12">
    <div class="container mx-auto px-4 max-w-lg">
        <div class="bg-white rounded-3xl shadow-2xl border border-emerald-100 p-8">
            <div class="text-center mb-8">
                <div class="w-20 h-20 bg-emerald-500 text-white rounded-full mx-auto mb-4 flex items-center justify-center">
                    <i class="fas fa-hand-holding-heart text-3xl"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-800">Record Donation</h1>
                <p class="text-gray-600 mt-2">Quick form for field collections</p>
            </div>

            <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-2xl <?php echo $success ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-red-50 border-red-200 text-red-800'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Donor Name *</label>
                    <input type="text" name="donor_name" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" placeholder="Full name">
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Phone *</label>
                    <input type="tel" name="donor_phone" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" placeholder="9876543210">
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Email (for receipt)</label>
                    <input type="email" name="donor_email" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" placeholder="donor@example.com">
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Address</label>
                    <textarea name="donor_address" rows="2" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" placeholder="Door, street, area..."></textarea>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Amount (₹) *</label>
                    <input type="number" name="amount" min="10" step="1" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-right font-bold text-lg" placeholder="100">
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Payment Mode *</label>
                    <select name="payment_mode" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        <option value="cash">Cash</option>
                        <option value="online">Online (UPI)</option>
                        <option value="cheque">Cheque</option>
                    </select>
                </div>

                <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-4 rounded-2xl shadow-lg transition transform hover:scale-105">
                    <i class="fas fa-save mr-2"></i> Record Donation
                </button>
            </form>

            <?php if ($success && $receiptLink): ?>
            <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-3">
                <a href="<?php echo htmlspecialchars($receiptLink); ?>" class="text-center bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-xl" target="_blank" rel="noopener">
                    <i class="fas fa-file-pdf mr-2"></i> Download Receipt
                </a>
                <a href="<?php echo htmlspecialchars($whatsAppLink); ?>" class="text-center bg-green-600 hover:bg-green-700 text-white font-semibold py-3 rounded-xl" target="_blank" rel="noopener">
                    <i class="fab fa-whatsapp mr-2"></i> Share via WhatsApp
                </a>
                <a href="<?php echo htmlspecialchars($emailShareLink); ?>" class="text-center bg-slate-700 hover:bg-slate-800 text-white font-semibold py-3 rounded-xl">
                    <i class="fas fa-envelope mr-2"></i> Share via Email
                </a>
            </div>
            <?php endif; ?>

            <div class="mt-8 p-4 bg-orange-50 border border-orange-200 rounded-xl">
                <h3 class="font-bold text-orange-800 mb-2">⚠️ Cash Compliance</h3>
                <p class="text-sm text-orange-700">
                    Deposit cash within 7 days. Late deposits trigger alerts and restrictions.
                </p>
            </div>
        </div>
    </div>
</div>

<?php require 'includes/footer.php'; ?>

