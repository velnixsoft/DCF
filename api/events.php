<?php
require_once __DIR__ . '/_bootstrap.php';

$id = api_int('id');
if ($id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $event = false;
    }

    if (!$event) {
        api_error('Event not found.', 404);
    }

    $registrationsCount = 0;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM event_registrations WHERE event_id = ?");
        $stmt->execute([$id]);
        $registrationsCount = (int)$stmt->fetchColumn();
    } catch (Throwable $e) {
    }

    $gallery = [];
    try {
        $stmt = $pdo->prepare("SELECT id, image_path, created_at FROM event_gallery WHERE event_id = ? ORDER BY created_at DESC LIMIT 30");
        $stmt->execute([$id]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $row['image_url'] = api_public_url($row['image_path'] ?? '');
            $gallery[] = $row;
        }
    } catch (Throwable $e) {
    }

    $event['gallery_path_url'] = api_public_url($event['gallery_path'] ?? '');
    $event['excerpt'] = api_excerpt($event['description'] ?? '', 220);
    $event['registrations_count'] = $registrationsCount;
    $event['can_register'] = !empty($event['registration_open']) && in_array((string)($event['status'] ?? ''), ['Upcoming', 'Live'], true);

    api_ok([
        'event' => $event,
        'gallery' => $gallery,
    ], 'Event loaded.');
}

$items = [];
try {
    $stmt = $pdo->query("
        SELECT e.*,
               (SELECT COUNT(*) FROM event_registrations r WHERE r.event_id = e.id) AS registrations_count
        FROM events e
        ORDER BY FIELD(e.status, 'Live', 'Upcoming', 'Completed', 'Cancelled'),
                 e.event_date ASC,
                 e.created_at DESC
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['gallery_path_url'] = api_public_url($row['gallery_path'] ?? '');
        $row['excerpt'] = api_excerpt($row['description'] ?? '', 180);
        $row['can_register'] = !empty($row['registration_open']) && in_array((string)($row['status'] ?? ''), ['Upcoming', 'Live'], true);
        $items[] = $row;
    }
} catch (Throwable $e) {
}

api_ok(['events' => $items], 'Events loaded.');
