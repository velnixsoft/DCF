<?php
require 'includes/header.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: events.php');
    exit;
}

$event = null;
$registrationCount = 0;
$dbError = null;
$galleryImages = [];

function eventBadgeClasses($status) {
    $status = (string)$status;
    if ($status === 'Live') return 'bg-green-100 text-green-800';
    if ($status === 'Upcoming') return 'bg-blue-100 text-blue-800';
    if ($status === 'Completed') return 'bg-gray-100 text-gray-800';
    if ($status === 'Cancelled') return 'bg-red-100 text-red-800';
    return 'bg-slate-100 text-slate-800';
}

try {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($event) {
        $today = date('Y-m-d');
        $eventDate = date('Y-m-d', strtotime($event['event_date']));
        if (($event['status'] ?? '') !== 'Cancelled') {
            if ($eventDate > $today) {
                $event['status'] = 'Upcoming';
            } elseif ($eventDate == $today) {
                $event['status'] = 'Live';
            } else {
                $event['status'] = 'Completed';
            }
        }

        try {
            $c = $pdo->prepare("SELECT COUNT(*) FROM event_registrations WHERE event_id = ?");
            $c->execute([$id]);
            $registrationCount = (int)$c->fetchColumn();
        } catch (Throwable $e) {
            $registrationCount = 0;
        }

        try {
            $g = $pdo->prepare("SELECT image_path FROM event_gallery WHERE event_id = ? ORDER BY created_at DESC LIMIT 30");
            $g->execute([$id]);
            $galleryImages = $g->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $galleryImages = [];
        }
    }
} catch (Throwable $e) {
    $dbError = 'Events module is not ready. Please run DB migration (upgrade_v2.sql).';
}

if (!$event && !$dbError) {
    setFlash('error', 'Event not found.');
    header('Location: events.php');
    exit;
}

$canRegister = false;
if ($event) {
    $status = (string)($event['status'] ?? '');
    $canRegister = !empty($event['registration_open']) && in_array($status, ['Upcoming', 'Live'], true);
}
?>

<div class="min-h-screen pt-28 pb-14 bg-gray-50">
    <div class="container mx-auto px-6 max-w-4xl">
        <div class="mb-6">
            <a href="events.php" class="text-sm font-semibold text-green-700 hover:text-green-800">
                <i class="fa-solid fa-arrow-left mr-2"></i>Back to events
            </a>
        </div>

        <?php if ($dbError): ?>
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl">
                <?php echo htmlspecialchars($dbError); ?>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-2xl shadow border border-gray-100 overflow-hidden">
                <div class="p-7 md:p-9">
                    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3">
                        <div>
                            <h1 class="text-3xl md:text-4xl font-extrabold text-gray-900">
                                <?php echo htmlspecialchars($event['title'] ?? 'Event'); ?>
                            </h1>
                            <div class="mt-3 flex flex-wrap items-center gap-3 text-sm text-gray-600">
                                <span class="inline-flex items-center gap-2">
                                    <i class="fa-regular fa-calendar text-amber-600"></i>
                                    <?php echo !empty($event['event_date']) ? date('d M Y', strtotime($event['event_date'])) : '-'; ?>
                                </span>
                                <?php if (!empty($event['location'])): ?>
                                    <span class="inline-flex items-center gap-2">
                                        <i class="fa-solid fa-location-dot text-amber-600"></i>
                                        <?php echo htmlspecialchars($event['location']); ?>
                                    </span>
                                <?php endif; ?>
                                <span class="inline-flex items-center gap-2">
                                    <i class="fa-solid fa-users text-amber-600"></i>
                                    Registrations: <?php echo $registrationCount; ?>
                                </span>
                            </div>
                        </div>
                        <div class="text-sm">
                            <span class="px-3 py-1 rounded-full font-bold <?php echo eventBadgeClasses($event['status'] ?? ''); ?>">
                                <?php echo htmlspecialchars($event['status'] ?? ''); ?>
                            </span>
                        </div>
                    </div>

                    <div class="mt-6 prose max-w-none">
                        <?php if (!empty($event['description'])): ?>
                            <p class="text-gray-700 leading-relaxed"><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                        <?php else: ?>
                            <p class="text-gray-600">Event details will be updated soon.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if (!empty($galleryImages)): ?>
                <div class="mt-8 bg-white rounded-2xl shadow border border-gray-100 p-7 md:p-9">
                    <h2 class="text-2xl font-bold text-gray-900 mb-5">Event Gallery</h2>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                        <?php foreach ($galleryImages as $img): ?>
                            <?php if (!empty($img['image_path'])): ?>
                                <a href="<?php echo htmlspecialchars((string)$img['image_path']); ?>" target="_blank" class="block rounded-xl overflow-hidden border bg-gray-50 aspect-square">
                                    <img src="<?php echo htmlspecialchars((string)$img['image_path']); ?>" class="w-full h-full object-cover">
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="mt-8 bg-white rounded-2xl shadow border border-gray-100 p-7 md:p-9">
                <div class="flex items-center justify-between gap-3 mb-5">
                    <h2 class="text-2xl font-bold text-gray-900">Event Registration</h2>
                    <?php if (!$canRegister): ?>
                        <span class="text-xs px-3 py-1 rounded-full bg-gray-100 text-gray-700 font-semibold">Registration Closed</span>
                    <?php else: ?>
                        <span class="text-xs px-3 py-1 rounded-full bg-green-100 text-green-700 font-semibold">Registration Open</span>
                    <?php endif; ?>
                </div>

                <?php if ($canRegister): ?>
                    <form action="process/event_register.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">
                        <input type="hidden" name="event_id" value="<?php echo (int)$event['id']; ?>">

                        <div class="md:col-span-2">
                            <label class="text-sm font-semibold text-gray-700">Full Name</label>
                            <input name="name" required class="w-full mt-1 border border-gray-300 rounded-xl p-3 focus:ring-2 focus:ring-green-400 focus:border-green-400" placeholder="Your name">
                        </div>

                        <div>
                            <label class="text-sm font-semibold text-gray-700">Email</label>
                            <input type="email" name="email" required class="w-full mt-1 border border-gray-300 rounded-xl p-3 focus:ring-2 focus:ring-green-400 focus:border-green-400" placeholder="name@example.com">
                        </div>

                        <div>
                            <label class="text-sm font-semibold text-gray-700">Phone</label>
                            <input name="phone" class="w-full mt-1 border border-gray-300 rounded-xl p-3 focus:ring-2 focus:ring-green-400 focus:border-green-400" placeholder="Phone number">
                        </div>

                        <div class="md:col-span-2 border-t pt-4 mt-2">
                            <p class="text-xs text-gray-500 mb-3">If you are a registered member, fill these optional fields to link your registration.</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm font-semibold text-gray-700">Member No (optional)</label>
                                    <input name="member_no" class="w-full mt-1 border border-gray-300 rounded-xl p-3 focus:ring-2 focus:ring-green-400 focus:border-green-400" placeholder="MEM-00001">
                                </div>
                                <div>
                                    <label class="text-sm font-semibold text-gray-700">Member Email (optional)</label>
                                    <input type="email" name="member_email" class="w-full mt-1 border border-gray-300 rounded-xl p-3 focus:ring-2 focus:ring-green-400 focus:border-green-400" placeholder="registered@email.com">
                                </div>
                            </div>
                        </div>

                        <div class="md:col-span-2">
                            <button class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3.5 rounded-xl shadow-lg transition">
                                Register Now
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <p class="text-gray-600 text-sm">Registration is currently closed for this event.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require 'includes/footer.php'; ?>
