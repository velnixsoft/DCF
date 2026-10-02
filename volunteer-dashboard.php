<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

if (empty($_SESSION['volunteer_logged_in']) || empty($_SESSION['volunteer_id'])) {
    setFlash('error', 'Please login to access the volunteer dashboard.');
    header('Location: volunteer-login.php');
    exit;
}

$volunteerId = (int)$_SESSION['volunteer_id'];
$stmt = $pdo->prepare("SELECT id, name, email, phone, photo, status, id_card_no, valid_from, valid_until, created_at FROM volunteers WHERE id = ? LIMIT 1");
$stmt->execute([$volunteerId]);
$vol = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$vol) {
    setFlash('error', 'Volunteer account not found.');
    header('Location: process/volunteer_logout.php');
    exit;
}
?>

<div class="bg-gray-50 min-h-screen py-12">
    <div class="container mx-auto px-4 max-w-5xl">
        <div class="bg-white rounded-2xl shadow border border-gray-100 p-6 md:p-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold text-gray-900">Volunteer Dashboard</h1>
                    <p class="text-sm text-gray-600 mt-1">Welcome, <?php echo htmlspecialchars((string)($vol['name'] ?? 'Volunteer')); ?>.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="volunteer-idcard.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm <?php echo empty($vol['id_card_no']) ? 'opacity-60 pointer-events-none' : ''; ?>">
                        Download ID Card
                    </a>
                    <a href="process/volunteer_logout.php" class="bg-gray-900 hover:bg-gray-800 text-white px-4 py-2 rounded-lg text-sm">Logout</a>
                </div>
            </div>

            <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-1 rounded-xl border p-4 bg-gray-50">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-full overflow-hidden bg-white border">
                            <?php if (!empty($vol['photo'])): ?>
                                <img src="<?php echo htmlspecialchars((string)$vol['photo']); ?>" alt="Photo" class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center text-gray-400">
                                    <i class="fa-solid fa-user text-2xl"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <p class="font-bold text-gray-900"><?php echo htmlspecialchars((string)$vol['name']); ?></p>
                            <p class="text-xs text-gray-600"><?php echo htmlspecialchars((string)$vol['email']); ?></p>
                            <p class="text-xs text-gray-600"><?php echo htmlspecialchars((string)($vol['phone'] ?? '')); ?></p>
                        </div>
                    </div>
                </div>

                <div class="md:col-span-2 rounded-xl border p-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div class="rounded-lg bg-blue-50 border border-blue-100 p-4">
                            <p class="text-xs text-blue-700">Volunteer ID</p>
                            <p class="text-xl font-extrabold text-blue-900 font-mono"><?php echo htmlspecialchars((string)($vol['id_card_no'] ?? '—')); ?></p>
                        </div>
                        <div class="rounded-lg bg-emerald-50 border border-emerald-100 p-4">
                            <p class="text-xs text-emerald-700">Status</p>
                            <p class="text-xl font-extrabold text-emerald-900"><?php echo htmlspecialchars((string)($vol['status'] ?? '—')); ?></p>
                        </div>
                        <div class="rounded-lg bg-violet-50 border border-violet-100 p-4">
                            <p class="text-xs text-violet-700">Valid From</p>
                            <p class="text-base font-bold text-violet-900"><?php echo !empty($vol['valid_from']) ? htmlspecialchars(date('d M Y', strtotime((string)$vol['valid_from']))) : '—'; ?></p>
                        </div>
                        <div class="rounded-lg bg-amber-50 border border-amber-100 p-4">
                            <p class="text-xs text-amber-700">Valid Until</p>
                            <p class="text-base font-bold text-amber-900"><?php echo !empty($vol['valid_until']) ? htmlspecialchars(date('d M Y', strtotime((string)$vol['valid_until']))) : '—'; ?></p>
                        </div>
                    </div>

                    <?php if (($vol['status'] ?? '') !== 'Active'): ?>
                        <div class="mt-4 rounded-xl border border-yellow-200 bg-yellow-50 p-4 text-yellow-800 text-sm">
                            Your volunteer account is currently not active. Please contact the admin for help.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mt-8 rounded-xl border p-4">
                <h2 class="font-semibold text-gray-900 mb-2">Quick Links</h2>
                <div class="flex flex-wrap gap-2">
                    <a class="bg-gray-100 hover:bg-gray-200 px-3 py-2 rounded text-sm" href="events.php">Events</a>
                    <a class="bg-gray-100 hover:bg-gray-200 px-3 py-2 rounded text-sm" href="inquiry.php">Submit Inquiry</a>
                    <a class="bg-gray-100 hover:bg-gray-200 px-3 py-2 rounded text-sm" href="volunteer-verify.php">Verify Volunteer ID</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

