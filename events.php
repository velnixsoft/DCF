<?php
require 'includes/header.php';

$events = [];
$dbError = null;

try {
    $sql = "
        SELECT
            e.*,
            (SELECT COUNT(*) FROM event_registrations r WHERE r.event_id = e.id) AS registrations_count
        FROM events e
        ORDER BY
            FIELD(e.status, 'Live', 'Upcoming', 'Completed', 'Cancelled'),
            e.event_date ASC,
            e.created_at DESC
    ";
    $events = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    $today = date('Y-m-d');

    foreach ($events as &$event) {
        if ($event['status'] === 'Cancelled') {
            continue;
        }
        $eventDate = date('Y-m-d', strtotime($event['event_date']));

        if ($eventDate > $today) {
            $event['status'] = 'Upcoming';
        } elseif ($eventDate == $today) {
            $event['status'] = 'Live';
        } else {
            $event['status'] = 'Completed';
        }
    }
    unset($event);
} catch (Throwable $e) {
    $dbError = 'Events module is not ready. Please run DB migration (upgrade_v2.sql).';
}

function eventBadgeClasses($status) {
    $status = (string)$status;
    if ($status === 'Live') return 'bg-[#F0FDFD] text-[#0F8B8D] border border-[#CCFBF1]';
    if ($status === 'Upcoming') return 'bg-[#FFF8F1] text-[#D98E2B] border border-[#FEECDC]';
    if ($status === 'Completed') return 'bg-gray-100 text-[#4B5563]';
    if ($status === 'Cancelled') return 'bg-red-50 text-red-700';
    return 'bg-slate-100 text-slate-800';
}
?>

<div class="min-h-screen mt-6 pt-28 pb-14 bg-[#FFF8F1]/40">
    <div class="container mx-auto px-6">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-4xl font-extrabold text-[#0F8B8D]">Events and Meetings</h1>
                <p class="text-[#4B5563] mt-2">Latest programs, meetings and registrations.</p>
            </div>
        </div>

        <?php if ($dbError): ?>
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl">
                <?php echo htmlspecialchars($dbError); ?>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($events as $e): ?>
                    <a href="event-details.php?id=<?php echo (int)$e['id']; ?>"
                       class="group bg-white rounded-[18px] shadow border border-gray-100 overflow-hidden hover:border-[#F4A640] hover:shadow-xl transition duration-300">
                        <div class="p-6">
                            <div class="flex items-center justify-between gap-2 mb-3">
                                <span class="px-3 py-1 text-xs font-bold rounded-full <?php echo eventBadgeClasses($e['status'] ?? ''); ?>">
                                    <?php echo htmlspecialchars($e['status'] ?? ''); ?>
                                </span>
                                <span class="text-xs text-[#4B5563]">
                                    <?php echo !empty($e['event_date']) ? date('d M Y', strtotime($e['event_date'])) : '-'; ?>
                                </span>
                            </div>

                            <h3 class="text-xl font-bold text-[#1F2937] group-hover:text-[#F4A640] transition">
                                <?php echo htmlspecialchars($e['title'] ?? 'Event'); ?>
                            </h3>

                            <?php if (!empty($e['location'])): ?>
                                <p class="text-sm text-[#4B5563] mt-2">
                                    <i class="fa-solid fa-location-dot text-[#0F8B8D] mr-1"></i>
                                    <?php echo htmlspecialchars($e['location']); ?>
                                </p>
                            <?php endif; ?>

                            <div class="flex items-center justify-between mt-5 text-sm">
                                <span class="text-[#4B5563]">
                                    Registrations: <span class="font-semibold text-[#1F2937]"><?php echo (int)($e['registrations_count'] ?? 0); ?></span>
                                </span>
                                <span class="inline-flex items-center gap-2 font-semibold text-[#0F8B8D] group-hover:text-[#F4A640] transition">
                                    View details
                                    <i class="fa-solid fa-arrow-right text-xs"></i>
                                </span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>

                <?php if (empty($events)): ?>
                    <div class="bg-white rounded-2xl shadow border border-gray-100 p-8 text-center text-gray-500 md:col-span-2 lg:col-span-3">
                        No events published yet.
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require 'includes/footer.php'; ?>

