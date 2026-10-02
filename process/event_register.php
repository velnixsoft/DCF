<?php
require '../config/db.php';
require '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../events.php');
    exit;
}

if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    setFlash('error', 'Invalid Security Token!');
    header('Location: ../events.php');
    exit;
}

$eventId = (int)($_POST['event_id'] ?? 0);
$name = cleanInput($_POST['name'] ?? '');
$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$phone = cleanInput($_POST['phone'] ?? '');
$memberNo = trim((string)($_POST['member_no'] ?? ''));
$memberEmail = trim((string)($_POST['member_email'] ?? ''));

if ($eventId <= 0 || $name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    setFlash('error', 'Please fill all required fields.');
    header('Location: ../events.php');
    exit;
}

try {
    $eStmt = $pdo->prepare("SELECT id, title, event_date, registration_open, max_registrations, status FROM events WHERE id = ? LIMIT 1");
    $eStmt->execute([$eventId]);
    $event = $eStmt->fetch(PDO::FETCH_ASSOC);
    if (!$event) {
        setFlash('error', 'Event not found.');
        header('Location: ../events.php');
        exit;
    }

    $status = (string)($event['status'] ?? '');
    $registrationOpen = (int)($event['registration_open'] ?? 0) === 1;
    if (!$registrationOpen || !in_array($status, ['Upcoming', 'Live'], true)) {
        setFlash('error', 'Registration is closed for this event.');
        header('Location: ../event-details.php?id=' . $eventId);
        exit;
    }

    $maxRegs = $event['max_registrations'] !== null ? (int)$event['max_registrations'] : null;
    if ($maxRegs !== null && $maxRegs > 0) {
        $cStmt = $pdo->prepare("SELECT COUNT(*) FROM event_registrations WHERE event_id = ? AND status = 'Registered'");
        $cStmt->execute([$eventId]);
        $current = (int)$cStmt->fetchColumn();
        if ($current >= $maxRegs) {
            setFlash('error', 'Registration limit reached for this event.');
            header('Location: ../event-details.php?id=' . $eventId);
            exit;
        }
    }

    $memberId = null;
    if ($memberNo !== '' && $memberEmail !== '') {
        try {
            $mStmt = $pdo->prepare("SELECT id FROM members WHERE member_no = ? AND email = ? LIMIT 1");
            $mStmt->execute([$memberNo, $memberEmail]);
            $memberId = $mStmt->fetchColumn() ?: null;
        } catch (Throwable $e) {
            $memberId = null;
        }
    }

    $dupStmt = $pdo->prepare("SELECT id FROM event_registrations WHERE event_id = ? AND email = ? LIMIT 1");
    $dupStmt->execute([$eventId, $email]);
    if ($dupStmt->fetchColumn()) {
        setFlash('error', 'You are already registered for this event with this email.');
        header('Location: ../event-details.php?id=' . $eventId);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO event_registrations (event_id, member_id, name, email, phone, status) VALUES (?, ?, ?, ?, ?, 'Registered')");
    $stmt->execute([$eventId, $memberId, $name, $email, $phone ?: null]);

    setFlash('success', 'Registration successful. Thank you!');
    header('Location: ../event-details.php?id=' . $eventId);
    exit;
} catch (Throwable $e) {
    setFlash('error', 'Unable to register right now. Please try again.');
    header('Location: ../event-details.php?id=' . $eventId);
    exit;
}

