<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/student/portal_helpers.php';

$student = student_portal_require_student($pdo, 'payment');
$studentId = (int)$student['id'];

$stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'upi_vpa'");
$upiVpa = $stmt ? $stmt->fetchColumn() : 'suchi@upi';
$stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'upi_payee_name'");
$upiPayeeName = $stmt ? $stmt->fetchColumn() : 'Suchi NGO';

$upiNote = 'INT-' . $student['student_no'] . '-' . str_replace(' ', '', (string)$student['city_name']);
$upiPayUrl = 'upi://pay?pa=' . urlencode($upiVpa) . '&pn=' . urlencode($upiPayeeName) . '&tn=' . urlencode($upiNote) . '&cu=INR';
$qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=320x320&data=' . urlencode($upiPayUrl);

$collected = 0.0;
$donationCount = 0;
try {
    $d = $pdo->prepare("SELECT COALESCE(SUM(amount),0), COUNT(*) FROM donations WHERE sa_student_id = ? AND payment_status = 'Success'");
    $d->execute([$studentId]);
    $row = $d->fetch(PDO::FETCH_NUM);
    $collected = (float)($row[0] ?? 0);
    $donationCount = (int)($row[1] ?? 0);
} catch (Throwable $e) {}

student_portal_render_shell_start($pdo, $student, 'Payment QR', 'payment');
?>

<div class="grid gap-6 lg:grid-cols-2" x-data="paymentQrHandler()">
    <!-- Left Section: QR Widget -->
    <section class="rounded-3xl border border-slate-100 bg-white p-8 text-center shadow-sm transition-all duration-300 hover:shadow-md">
        <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 border border-emerald-100 uppercase tracking-wider">
            Intern Collection QR
        </span>
        <h2 class="text-xl font-extrabold text-slate-800 tracking-tight mt-3">Personalized Campaign QR</h2>
        <p class="mt-2 text-xs text-slate-500 max-w-sm mx-auto leading-relaxed">Payments received via this QR code automatically track toward your profile metrics and city leaderboard status.</p>
        
        <div class="mt-6 inline-block rounded-3xl border border-slate-100 bg-slate-50/50 p-4 shadow-inner">
            <img src="<?php echo htmlspecialchars($qrCodeUrl); ?>" alt="Payment QR" class="mx-auto rounded-2xl border border-slate-100/50 shadow bg-white max-w-[260px]">
        </div>
        
        <div class="mt-6 max-w-sm mx-auto">
            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Your Transaction Reference Note</label>
            <div class="flex items-center rounded-2xl border border-slate-100 bg-slate-50 p-1">
                <span class="font-mono text-xs text-slate-700 font-semibold px-3 py-2 select-all truncate flex-1 text-left"><?php echo htmlspecialchars($upiNote); ?></span>
                <button @click="copyText('<?php echo addslashes($upiNote); ?>')" 
                        class="rounded-xl bg-slate-800 hover:bg-black px-4 py-2 text-xs font-bold text-white transition-all whitespace-nowrap">
                    <span x-text="copied ? 'Copied!' : 'Copy Code'"></span>
                </button>
            </div>
            <p class="mt-3 text-xs text-slate-400 font-semibold flex items-center justify-center gap-1">
                <i class="fa-solid fa-location-dot"></i> City Link: <span class="text-slate-605 font-extrabold"><?php echo htmlspecialchars($student['city_name'] ?? '—'); ?></span>
            </p>
        </div>
    </section>

    <!-- Right Section: Collections Tracker -->
    <section class="rounded-3xl border border-slate-100 bg-white p-8 shadow-sm transition-all duration-300 hover:shadow-md flex flex-col justify-between">
        <div>
            <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600 border border-slate-200 uppercase tracking-wider">
                Analytics
            </span>
            <h3 class="text-xl font-extrabold text-slate-800 tracking-tight mt-3">Collected Contributions</h3>
            
            <div class="mt-6 rounded-3xl bg-slate-50 border border-slate-100 p-6 flex flex-col justify-center items-center text-center">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Funds Attributed</p>
                <p class="mt-1.5 text-4xl font-black text-emerald-600 tracking-tight">₹<?php echo number_format($collected, 2); ?></p>
                <p class="text-xs text-slate-500 mt-2 font-semibold"><?php echo $donationCount; ?> successful transactions mapped to your profile</p>
            </div>
            
            <div class="mt-8">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Guidelines & Impact</label>
                <ul class="space-y-3">
                    <li class="rounded-2xl bg-slate-50/50 border border-slate-100/50 px-4 py-3 flex items-start gap-3">
                        <span class="text-base mt-0.5">📢</span>
                        <div>
                            <p class="text-xs font-bold text-slate-800">College Events & Campaigns</p>
                            <p class="text-[10px] text-slate-400 mt-0.5 leading-relaxed">Present this QR code during public presentations and city camps.</p>
                        </div>
                    </li>
                    <li class="rounded-2xl bg-slate-50/50 border border-slate-100/50 px-4 py-3 flex items-start gap-3">
                        <span class="text-base mt-0.5">📊</span>
                        <div>
                            <p class="text-xs font-bold text-slate-800">Admin Reconciliations</p>
                            <p class="text-[10px] text-slate-400 mt-0.5 leading-relaxed">Donations are validated by finance admins using the reference code.</p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </section>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('paymentQrHandler', () => ({
        copied: false,
        copyText(text) {
            navigator.clipboard.writeText(text).then(() => {
                this.copied = true;
                setTimeout(() => this.copied = false, 2000);
            });
        }
    }));
});
</script>

<?php student_portal_render_shell_end(); ?>
