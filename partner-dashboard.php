<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/partner/portal_helpers.php';

$partner = partner_portal_require_partner($pdo);
$activityLabel = partner_activity_label((string)($partner['activity_type'] ?? 'retail'));
$mapUrl = 'https://www.google.com/maps?q=' . urlencode((string)$partner['latitude'] . ',' . (string)$partner['longitude']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partner Dashboard — <?php echo htmlspecialchars($partner['business_name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
</head>
<body class="bg-slate-50 text-slate-900">
<header class="border-b border-slate-200 bg-white">
    <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-blue-600">Partner Portal</p>
            <h1 class="text-xl font-extrabold"><?php echo htmlspecialchars($partner['business_name']); ?></h1>
            <p class="text-sm text-slate-500"><?php echo htmlspecialchars($partner['partner_code']); ?> · <?php echo htmlspecialchars($activityLabel); ?></p>
        </div>
        <a href="process/partner_logout.php" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Logout</a>
    </div>
</header>

<main class="mx-auto max-w-6xl px-4 py-8 space-y-6">
    <div class="grid gap-6 md:grid-cols-3">
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-bold uppercase text-slate-500">Owner</p>
            <p class="mt-2 text-lg font-extrabold"><?php echo htmlspecialchars($partner['owner_name']); ?></p>
            <p class="text-sm text-slate-600"><?php echo htmlspecialchars($partner['owner_email']); ?></p>
            <p class="text-sm text-slate-600"><?php echo htmlspecialchars($partner['owner_mobile']); ?></p>
        </section>
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-bold uppercase text-slate-500">Business Contact</p>
            <p class="mt-2 text-lg font-extrabold"><?php echo htmlspecialchars($partner['business_mobile'] ?: $partner['owner_mobile']); ?></p>
            <p class="text-sm text-slate-600"><?php echo htmlspecialchars(trim(($partner['city_name'] ?? '') . ', ' . ($partner['state_name'] ?? ''), ', ')); ?></p>
        </section>
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-bold uppercase text-slate-500">API Access</p>
            <p class="mt-2 text-sm font-semibold" :class="<?php echo (int)($partner['api_enabled'] ?? 0) === 1 ? "'text-emerald-700'" : "'text-amber-700'"; ?>">
                <?php echo (int)($partner['api_enabled'] ?? 0) === 1 ? 'Enabled for firm integrations' : 'Pending admin enablement'; ?>
            </p>
            <?php if (!empty($partner['api_key']) && (int)($partner['api_enabled'] ?? 0) === 1): ?>
                <p class="mt-2 break-all font-mono text-xs text-slate-600"><?php echo htmlspecialchars(substr((string)$partner['api_key'], 0, 12)); ?>••••••</p>
            <?php endif; ?>
        </section>
    </div>

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-extrabold">Location & Address</h2>
        <p class="mt-2 text-sm text-slate-600"><?php echo nl2br(htmlspecialchars($partner['business_address'] ?? '')); ?></p>
        <p class="mt-3 text-sm font-mono text-slate-700">Lat: <?php echo htmlspecialchars((string)$partner['latitude']); ?> · Long: <?php echo htmlspecialchars((string)$partner['longitude']); ?></p>
        <a href="<?php echo htmlspecialchars($mapUrl); ?>" target="_blank" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-700">
            <i class="fa-solid fa-map-location-dot"></i> Open in Maps
        </a>
    </section>

    <section class="rounded-3xl border border-dashed border-blue-200 bg-blue-50/50 p-6">
        <h2 class="text-lg font-extrabold text-blue-900">Connected with Student Ambassadors</h2>
        <p class="mt-2 text-sm text-blue-800">Your business profile can be shared with partner firms and student interns through our API once admin enables integration. Ambassadors may submit vendor leads linked to your partnership.</p>
    </section>
</main>
</body>
</html>
