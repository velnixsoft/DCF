<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/member_module.php';
require_once __DIR__ . '/includes/qr_attendance.php';
require_once __DIR__ . '/includes/header.php';

$token = qa_extract_token($_GET['token'] ?? '');
$verification = null;
$record = null;
$scanTime = date('d M Y h:i A');

if ($token !== '') {
    $verification = qa_validate_member_token($pdo, $token);
    $record = $verification['record'] ?? null;
    if (!empty($verification['scan_time'])) {
        $scanTime = date('d M Y h:i A', strtotime((string)$verification['scan_time']));
    }
}

$siteName = $settings['site_name'] ?? 'NGO';
$pageStatus = $verification['status'] ?? '';
$statusMap = [
    'verified' => [
        'badge' => 'VERIFIED MEMBER',
        'icon' => 'fa-circle-check',
        'box' => 'border-green-200 bg-green-50',
        'badgeClass' => 'bg-green-600 text-white',
        'titleClass' => 'text-green-700',
        'message' => 'Member identity validated successfully.',
    ],
    'inactive' => [
        'badge' => 'VERIFIED - INACTIVE',
        'icon' => 'fa-shield-halved',
        'box' => 'border-amber-200 bg-amber-50',
        'badgeClass' => 'bg-amber-500 text-white',
        'titleClass' => 'text-amber-700',
        'message' => 'QR is valid, but the member is currently inactive.',
    ],
    'expired' => [
        'badge' => 'QR EXPIRED',
        'icon' => 'fa-clock',
        'box' => 'border-amber-200 bg-amber-50',
        'badgeClass' => 'bg-amber-500 text-white',
        'titleClass' => 'text-amber-700',
        'message' => 'This QR verification token has expired.',
    ],
    'invalid' => [
        'badge' => 'INVALID QR CODE',
        'icon' => 'fa-triangle-exclamation',
        'box' => 'border-red-200 bg-red-50',
        'badgeClass' => 'bg-red-600 text-white',
        'titleClass' => 'text-red-700',
        'message' => 'No active member record matched this QR token.',
    ],
];
$ui = $statusMap[$pageStatus] ?? $statusMap['invalid'];
?>

<div class="min-h-screen bg-slate-50 py-10 px-4">
    <div class="max-w-4xl mx-auto">
        <div class="rounded-3xl border border-slate-200 bg-white shadow-xl overflow-hidden">
            <div class="bg-slate-900 px-6 py-5 md:px-8">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div>
                        <p class="text-xs uppercase tracking-[0.25em] text-slate-300">Secure Verification</p>
                        <h1 class="text-2xl md:text-3xl font-semibold text-white mt-1"><?php echo htmlspecialchars($siteName); ?></h1>
                    </div>
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full <?php echo $ui['badgeClass']; ?>">
                        <i class="fa-solid <?php echo htmlspecialchars($ui['icon']); ?>"></i>
                        <span class="text-sm font-semibold"><?php echo htmlspecialchars($ui['badge']); ?></span>
                    </div>
                </div>
            </div>

            <div class="p-6 md:p-8">
                <div class="rounded-2xl border p-5 <?php echo $ui['box']; ?>">
                    <div class="flex flex-col lg:flex-row gap-6 lg:items-start">
                        <div class="shrink-0">
                            <?php if ($record && !empty($record['photo']) && file_exists(__DIR__ . '/' . $record['photo'])): ?>
                                <img src="<?php echo htmlspecialchars($record['photo']); ?>" alt="Member photo" class="w-36 h-36 rounded-2xl object-cover border-4 border-white shadow-md">
                            <?php else: ?>
                                <div class="w-36 h-36 rounded-2xl bg-white border border-slate-200 shadow-sm flex items-center justify-center text-slate-400">
                                    <i class="fa-solid fa-user text-5xl"></i>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="min-w-0 flex-1">
                            <h2 class="text-2xl font-semibold <?php echo $ui['titleClass']; ?>">
                                <?php echo htmlspecialchars($record['full_name'] ?? $ui['badge']); ?>
                            </h2>
                            <p class="text-sm text-slate-600 mt-1"><?php echo htmlspecialchars($ui['message']); ?></p>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-5">
                                <div class="rounded-2xl bg-white/80 border border-white p-4">
                                    <p class="text-[11px] uppercase tracking-wide text-slate-500">Member ID</p>
                                    <p class="mt-1 text-base font-semibold text-slate-800"><?php echo htmlspecialchars($record['member_no'] ?? '-'); ?></p>
                                </div>
                                <div class="rounded-2xl bg-white/80 border border-white p-4">
                                    <p class="text-[11px] uppercase tracking-wide text-slate-500">Designation</p>
                                    <p class="mt-1 text-base font-semibold text-slate-800"><?php echo htmlspecialchars($record['designation_title'] ?? '-'); ?></p>
                                </div>
                                <div class="rounded-2xl bg-white/80 border border-white p-4">
                                    <p class="text-[11px] uppercase tracking-wide text-slate-500">Phone</p>
                                    <p class="mt-1 text-base font-semibold text-slate-800"><?php echo htmlspecialchars($record['phone'] ?? '-'); ?></p>
                                </div>
                                <div class="rounded-2xl bg-white/80 border border-white p-4">
                                    <p class="text-[11px] uppercase tracking-wide text-slate-500">Status</p>
                                    <p class="mt-1 text-base font-semibold text-slate-800"><?php echo htmlspecialchars($record['member_status'] ?? '-'); ?></p>
                                </div>
                                <div class="rounded-2xl bg-white/80 border border-white p-4">
                                    <p class="text-[11px] uppercase tracking-wide text-slate-500">Join Date</p>
                                    <p class="mt-1 text-base font-semibold text-slate-800"><?php echo !empty($record['member_since']) ? htmlspecialchars(date('d M Y', strtotime((string)$record['member_since']))) : '-'; ?></p>
                                </div>
                                <div class="rounded-2xl bg-white/80 border border-white p-4">
                                    <p class="text-[11px] uppercase tracking-wide text-slate-500">Valid Till</p>
                                    <p class="mt-1 text-base font-semibold text-slate-800"><?php echo !empty($record['valid_until']) ? htmlspecialchars(date('d M Y', strtotime((string)$record['valid_until']))) : '-'; ?></p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3">
                                <div class="rounded-2xl bg-white/80 border border-white p-4">
                                    <p class="text-[11px] uppercase tracking-wide text-slate-500">NGO Name</p>
                                    <p class="mt-1 text-base font-semibold text-slate-800"><?php echo htmlspecialchars($siteName); ?></p>
                                </div>
                                <div class="rounded-2xl bg-white/80 border border-white p-4">
                                    <p class="text-[11px] uppercase tracking-wide text-slate-500">QR Scan Time</p>
                                    <p class="mt-1 text-base font-semibold text-slate-800"><?php echo htmlspecialchars($scanTime); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($token === ''): ?>
                    <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-5 text-sm text-slate-600">
                        Secure verification requires a QR token. Old manual verification pages remain available for previous documents.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
