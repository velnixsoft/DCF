<?php
require_once __DIR__ . '/_bootstrap.php';

api_require_method(['POST']);

$eventId = (int)api_input('event_id', 0);
$name = cleanInput(api_input('name', ''));
$email = filter_var((string)api_input('email', ''), FILTER_SANITIZE_EMAIL);
$phone = cleanInput(api_input('phone', ''));
$memberNo = trim((string)api_input('member_no', ''));
$memberEmail = trim((string)api_input('member_email', ''));

if ($eventId <= 0 || $name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    api_error('Please fill all required fields.', 422);
}

try {
    $eStmt = $pdo->prepare("SELECT id, title, event_date, registration_open, max_registrations, status FROM events WHERE id = ? LIMIT 1");
    $eStmt->execute([$eventId]);
    $event = $eStmt->fetch(PDO::FETCH_ASSOC);
    if (!$event) {
        api_error('Event not found.', 404);
    }

    $status = (string)($event['status'] ?? '');
    $registrationOpen = (int)($event['registration_open'] ?? 0) === 1;
    if (!$registrationOpen || !in_array($status, ['Upcoming', 'Live'], true)) {
        api_error('Registration is closed for this event.', 422);
    }

    $maxRegs = $event['max_registrations'] !== null ? (int)$event['max_registrations'] : null;
    if ($maxRegs !== null && $maxRegs > 0) {
        $cStmt = $pdo->prepare("SELECT COUNT(*) FROM event_registrations WHERE event_id = ? AND status = 'Registered'");
        $cStmt->execute([$eventId]);
        if ((int)$cStmt->fetchColumn() >= $maxRegs) {
            api_error('Registration limit reached for this event.', 422);
        }
    }

    $memberId = null;
    if ($memberNo !== '' && $memberEmail !== '') {
        $mStmt = $pdo->prepare("SELECT id FROM members WHERE member_no = ? AND email = ? LIMIT 1");
        $mStmt->execute([$memberNo, $memberEmail]);
        $memberId = $mStmt->fetchColumn() ?: null;
    }

    $dupStmt = $pdo->prepare("SELECT id FROM event_registrations WHERE event_id = ? AND email = ? LIMIT 1");
    $dupStmt->execute([$eventId, $email]);
    if ($dupStmt->fetchColumn()) {
        api_error('You are already registered for this event with this email.', 422);
    }

    $stmt = $pdo->prepare("INSERT INTO event_registrations (event_id, member_id, name, email, phone, status) VALUES (?, ?, ?, ?, ?, 'Registered')");
    $stmt->execute([$eventId, $memberId, $name, $email, $phone !== '' ? $phone : null]);
    $registrationId = (int)$pdo->lastInsertId();
} catch (Throwable $e) {
    api_error('Unable to register right now. Please try again.', 500);
}

api_ok([
    'registration_id' => $registrationId,
    'event_id' => $eventId,
], 'Registration successful. Thank you!');
