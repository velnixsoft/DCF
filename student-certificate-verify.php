<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/student/certificates.php';
require_once __DIR__ . '/includes/header.php';

$docNo = trim($_GET['doc'] ?? '');
$record = null;

if ($docNo !== '' && dbTableExists($pdo, 'sa_certificates')) {
    $service = new StudentCertificateService($pdo);
    $record = $service->findByCertificateNo($docNo);
}
?>

<div class="bg-gray-50 min-h-screen py-12 px-4">
    <div class="container mx-auto max-w-3xl">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-8 md:p-10">
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-shield-alt text-3xl"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-800">Student Certificate Verification</h1>
                <p class="text-gray-600 mt-2">Verify ambassador, internship, and achievement certificates.</p>
            </div>

            <form method="GET" class="mb-8 flex flex-col sm:flex-row gap-3">
                <input type="text" name="doc" value="<?php echo htmlspecialchars($docNo); ?>" placeholder="Enter Certificate Number (e.g. SCERT-...)" required class="flex-1 px-4 py-3.5 rounded-xl border border-gray-300 focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition text-sm">
                <button type="submit" class="bg-green-600 hover:bg-emerald-700 text-white font-bold px-6 py-3.5 rounded-xl text-sm transition shadow-md hover:shadow-emerald-500/10 flex items-center justify-center gap-2 whitespace-nowrap">
                    <i class="fas fa-search text-xs"></i>
                    Verify Certificate
                </button>
            </form>

            <?php if ($record): ?>
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6">
                    <h2 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($record['full_name']); ?></h2>
                    <p class="text-emerald-700 font-mono font-bold mt-1"><?php echo htmlspecialchars($record['certificate_no']); ?></p>
                    <p class="text-sm text-gray-600 mt-3"><?php echo htmlspecialchars($record['certificate_title']); ?></p>
                    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div class="bg-white rounded-xl p-4 border border-emerald-100">
                            <p class="text-gray-500 text-xs uppercase">Student ID</p>
                            <p class="font-semibold"><?php echo htmlspecialchars($record['student_no']); ?></p>
                        </div>
                        <div class="bg-white rounded-xl p-4 border border-emerald-100">
                            <p class="text-gray-500 text-xs uppercase">Level</p>
                            <p class="font-semibold"><?php echo htmlspecialchars($record['level_name'] ?? '—'); ?></p>
                        </div>
                        <div class="bg-white rounded-xl p-4 border border-emerald-100">
                            <p class="text-gray-500 text-xs uppercase">College</p>
                            <p class="font-semibold"><?php echo htmlspecialchars($record['college_name'] ?? '—'); ?></p>
                        </div>
                        <div class="bg-white rounded-xl p-4 border border-emerald-100">
                            <p class="text-gray-500 text-xs uppercase">Issued</p>
                            <p class="font-semibold"><?php echo !empty($record['issued_at']) ? htmlspecialchars(date('d M Y', strtotime($record['issued_at']))) : '—'; ?></p>
                        </div>
                    </div>
                    <p class="mt-4 text-sm font-semibold text-emerald-800">✓ Certificate verified successfully.</p>
                </div>
            <?php elseif ($docNo !== ''): ?>
                <div class="rounded-2xl border border-red-200 bg-red-50 p-6 text-red-700">No matching student certificate was found.</div>
            <?php else: ?>
                <div class="rounded-2xl border border-gray-200 bg-gray-50 p-6 text-gray-600">Enter a certificate number from the PDF or QR code.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
