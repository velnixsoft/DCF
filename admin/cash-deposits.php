<?php
require '../config/db.php';
require '../includes/functions.php';

if (!canAccessModule($pdo, 'area_manager', 'page.cash_deposits')) {
    setFlash('error', 'Access denied.');
    header('Location: dashboard.php');
    exit;
}

ensureFieldAgentSchema($pdo);

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$totalDeposits = 0;
try {
    $totalDeposits = (int)$pdo->query("SELECT COUNT(*) FROM cash_deposits cd JOIN donations d ON cd.donation_id = d.id JOIN field_agents fa ON cd.agent_id = fa.id JOIN users u ON fa.user_id = u.id WHERE cd.status = 'pending'")->fetchColumn();
    $offset = ($page - 1) * $perPage;
    $pendingDeposits = $pdo->query("
        SELECT cd.*, d.donor_name, d.donor_email, d.receipt_no, d.amount as donation_amount, d.created_at as donation_date,
               u.name as agent_name, fa.state, fa.area
        FROM cash_deposits cd
        JOIN donations d ON cd.donation_id = d.id
        JOIN field_agents fa ON cd.agent_id = fa.id
        JOIN users u ON fa.user_id = u.id
        WHERE cd.status = 'pending'
        ORDER BY cd.created_at DESC
        LIMIT $perPage OFFSET $offset
    ")->fetchAll();
} catch (Throwable $e) {
    $pendingDeposits = [];
}

$overdueCount = 0;
try {
    $overdueCount = (int)$pdo->query("SELECT COUNT(*) FROM cash_deposits WHERE status = 'pending' AND deposit_deadline < CURDATE()")->fetchColumn();
} catch (Throwable $e) {}

foreach ($pendingDeposits as &$deposit) {
    $createdAt = (string)($deposit['donation_date'] ?? $deposit['created_at'] ?? '');
    $token = generateDonationReceiptToken(
        (int)$deposit['donation_id'],
        (string)($deposit['receipt_no'] ?? ''),
        (string)($deposit['donor_email'] ?? ''),
        (float)($deposit['donation_amount'] ?? 0),
        $createdAt
    );
    $deposit['receipt_link'] = '../download-receipt.php?id=' . urlencode((string)$deposit['donation_id']) . '&token=' . urlencode($token);
}
unset($deposit);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_deposited'])) {
    $depositId = (int)($_POST['deposit_id'] ?? 0);
    $depositAmount = (float)($_POST['deposit_amount'] ?? 0);
    $notes = cleanInput($_POST['notes'] ?? '');

    if ($depositId > 0 && $depositAmount > 0) {
        $updateStmt = $pdo->prepare('
            UPDATE cash_deposits
            SET deposit_amount = ?, deposit_date = CURDATE(), status = "deposited", notes = ?
            WHERE id = ? AND status = "pending"
        ');
        $updateStmt->execute([$depositAmount, $notes, $depositId]);
        setFlash($updateStmt->rowCount() > 0 ? 'success' : 'error', $updateStmt->rowCount() > 0 ? 'Deposit marked as deposited.' : 'Unable to update deposit.');
    } else {
        setFlash('error', 'Enter a valid deposit amount.');
    }
    header('Location: cash-deposits.php');
    exit;
}

require '../includes/header.php';

?>
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">Cash Deposits Management</h1>
    
    <div class="bg-white rounded-xl shadow-sm border p-6 mb-8">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-red-600"><?php echo $overdueCount; ?></h2>
                <p class="text-sm text-gray-600">Overdue Deposits</p>
            </div>
            <a href="#" class="bg-orange-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-orange-700">Generate Compliance Report</a>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-4 text-left text-sm font-bold text-gray-700">Donor</th>
                    <th class="px-6 py-4 text-left text-sm font-bold text-gray-700">Agent</th>
                    <th class="px-6 py-4 text-left text-sm font-bold text-gray-700">Amount</th>
                    <th class="px-6 py-4 text-left text-sm font-bold text-gray-700">Collected</th>
                    <th class="px-6 py-4 text-left text-sm font-bold text-gray-700">Deadline</th>
                    <th class="px-6 py-4 text-left text-sm font-bold text-gray-700">Status</th>
                    <th class="px-6 py-4 text-left text-sm font-bold text-gray-700">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pendingDeposits as $deposit): ?>
                <tr class="border-t hover:bg-gray-50">
                    <td class="px-6 py-4 font-medium"><?php echo htmlspecialchars($deposit['donor_name']); ?></td>
                    <td class="px-6 py-4"><?php echo htmlspecialchars($deposit['agent_name'] . ' (' . $deposit['state'] . ')'); ?></td>
                    <td class="px-6 py-4 font-bold text-green-600">₹<?php echo number_format($deposit['donation_amount']); ?></td>
                    <td class="px-6 py-4"><?php echo date('d M Y', strtotime($deposit['created_at'])); ?></td>
                    <td class="px-6 py-4">
                        <span class="text-sm <?php echo strtotime($deposit['deposit_deadline']) < time() ? 'text-red-600 font-bold' : 'text-gray-600'; ?>">
                            <?php echo date('d M Y', strtotime($deposit['deposit_deadline'])); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-yellow-100 text-yellow-800">
                            Pending
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <form method="POST" class="flex flex-wrap items-center gap-2">
                            <input type="hidden" name="mark_deposited" value="1">
                            <input type="hidden" name="deposit_id" value="<?php echo (int)$deposit['id']; ?>">
                            <input type="number" name="deposit_amount" min="1" step="0.01" value="<?php echo htmlspecialchars((string)$deposit['collected_amount']); ?>" class="w-28 border border-gray-300 rounded px-2 py-1 text-sm" required>
                            <input type="text" name="notes" class="w-32 border border-gray-300 rounded px-2 py-1 text-sm" placeholder="Notes">
                            <button type="submit" class="text-xs bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700">Deposit</button>
                            <a href="<?php echo htmlspecialchars($deposit['receipt_link']); ?>" class="text-xs text-orange-600 hover:underline" target="_blank" rel="noopener">Receipt</a>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($pendingDeposits)): ?>
                <tr>
                    <td colspan='7' class='px-6 py-12 text-center text-gray-500'>
                        No pending deposits. All cash deposited successfully!
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php echo render_admin_pagination($totalDeposits, $page, $perPage); ?>
    </div>
</div>

<?php require '../includes/footer.php'; ?>

