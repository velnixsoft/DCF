<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

$docNo = trim($_GET['doc'] ?? '');
$record = null;

if ($docNo !== '') {
    $normalized = strtoupper(preg_replace('/[^A-Z0-9]/', '', $docNo));
    if (preg_match('/^VCERT\d{4}([A-Z0-9]+)$/', $normalized, $match)) {
        $needle = $match[1];
        $stmt = $pdo->query("SELECT * FROM volunteers ORDER BY id DESC");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $candidate = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string)($row['id_card_no'] ?? '')));
            if ($candidate !== '' && $candidate === $needle) {
                $record = $row;
                break;
            }
        }
    }
}
?>

<div class="bg-gray-50 min-h-screen py-12 px-4">
    <div class="container mx-auto max-w-3xl">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-8 md:p-10">
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-shield-alt text-3xl"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-800">Volunteer Certificate Verification</h1>
                <p class="text-gray-600 mt-2">Scan results and manual lookup for volunteer certificates.</p>
            </div>

            <form method="GET" class="mb-8 grid grid-cols-1 md:grid-cols-4 gap-3">
                <input type="text" name="doc" value="<?php echo htmlspecialchars($docNo); ?>" placeholder="Certificate No" class="md:col-span-3 w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-green-500 outline-none">
                <button class="bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold py-3 px-4">Verify</button>
            </form>

            <?php if ($record): ?>
                <div class="rounded-2xl border border-green-200 bg-green-50 p-6">
                    <div class="flex flex-col md:flex-row items-center gap-5">
                        <img src="<?php echo !empty($record['photo']) ? htmlspecialchars($record['photo']) : 'https://ui-avatars.com/api/?name=' . urlencode((string)($record['name'] ?? 'Volunteer')); ?>" class="w-28 h-28 rounded-full object-cover border-4 border-white shadow-lg">
                        <div class="text-center md:text-left">
                            <h2 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($record['name'] ?? ''); ?></h2>
                            <p class="text-green-700 font-mono font-bold"><?php echo htmlspecialchars($record['id_card_no'] ?? ''); ?></p>
                            <p class="text-sm text-gray-600 mt-1">Volunteer certificate verified successfully.</p>
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div class="bg-white rounded-xl p-4 border border-green-100">
                            <p class="text-gray-500 text-xs uppercase">Status</p>
                            <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($record['status'] ?? ''); ?></p>
                        </div>
                        <div class="bg-white rounded-xl p-4 border border-green-100">
                            <p class="text-gray-500 text-xs uppercase">Valid Until</p>
                            <p class="font-semibold text-gray-800"><?php echo !empty($record['valid_until']) ? htmlspecialchars(date('d M Y', strtotime($record['valid_until']))) : '-'; ?></p>
                        </div>
                        <div class="bg-white rounded-xl p-4 border border-green-100">
                            <p class="text-gray-500 text-xs uppercase">Blood Group</p>
                            <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($record['blood_group'] ?? '-'); ?></p>
                        </div>
                        <div class="bg-white rounded-xl p-4 border border-green-100">
                            <p class="text-gray-500 text-xs uppercase">Phone</p>
                            <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($record['phone'] ?? '-'); ?></p>
                        </div>
                    </div>
                </div>
            <?php elseif ($docNo !== ''): ?>
                <div class="rounded-2xl border border-red-200 bg-red-50 p-6 text-red-700">
                    No matching volunteer certificate was found for this code.
                </div>
            <?php else: ?>
                <div class="rounded-2xl border border-gray-200 bg-gray-50 p-6 text-gray-600">
                    Enter a volunteer certificate number to verify it.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
