<?php
session_start();
require '../../config/db.php';
require '../../includes/functions.php';
require '../../includes/member_module.php';
require '../../includes/qr_attendance.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

if (!canAccessModule($pdo, 'manager', 'page.events')) {
    setFlash('error', 'Access denied.');
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token'], $_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$_POST['csrf_token'])) {
    setFlash('error', 'Invalid request.');
    header('Location: ../events.php');
    exit;
}

$action = trim((string)($_POST['action'] ?? ''));
$id = (int)($_POST['id'] ?? 0);

try {
    if ($action === 'save') {
        $eventId = (int)($_POST['event_id'] ?? 0);
        $eventName = cleanInput($_POST['event_name'] ?? '');
        $eventDate = trim((string)($_POST['event_date'] ?? ''));
        $eventStartTime = trim((string)($_POST['event_start_time'] ?? ''));
        $eventLocation = cleanInput($_POST['event_location'] ?? '');
        $qrMode = trim((string)($_POST['qr_mode'] ?? 'single'));
        $status = trim((string)($_POST['status'] ?? 'active'));

        // Validate event date format, year limit, and past date restriction (on create)
        if (!empty($eventDate)) {
            $dateObj = DateTime::createFromFormat('Y-m-d', $eventDate);
            if (!$dateObj || $dateObj->format('Y-m-d') !== $eventDate) {
                throw new Exception('Invalid Event Date format.');
            }
            $year = (int)$dateObj->format('Y');
            if ($year > 9999) {
                throw new Exception('Year cannot be more than 4 digits.');
            }
            if ($id === 0 && strtotime($eventDate) < strtotime(date('Y-m-d'))) {
                throw new Exception('Event Date cannot be in the past.');
            }
        }

        if ($eventId > 0) {
            $source = $pdo->prepare("SELECT title, event_date, location FROM events WHERE id = ? LIMIT 1");
            $source->execute([$eventId]);
            $row = $source->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                if ($eventName === '') {
                    $eventName = (string)$row['title'];
                }
                if ($eventDate === '') {
                    $eventDate = (string)$row['event_date'];
                }
                if ($eventLocation === '') {
                    $eventLocation = (string)($row['location'] ?? '');
                }
            }
        }

        if ($eventName === '' || $eventDate === '' || !in_array($qrMode, ['single', 'multiple'], true) || !in_array($status, ['active', 'inactive', 'closed'], true)) {
            throw new RuntimeException('Please fill the required attendance event fields.');
        }

        if ($id > 0) {
            $stmt = $pdo->prepare("
                UPDATE attendance_events
                SET event_id = ?, event_name = ?, event_date = ?, event_start_time = ?, event_location = ?, qr_mode = ?, status = ?
                WHERE id = ?
            ");
            $stmt->execute([$eventId ?: null, $eventName, $eventDate, $eventStartTime !== '' ? $eventStartTime : null, $eventLocation ?: null, $qrMode, $status, $id]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO attendance_events (event_id, event_name, event_date, event_start_time, event_location, qr_mode, status, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$eventId ?: null, $eventName, $eventDate, $eventStartTime !== '' ? $eventStartTime : null, $eventLocation ?: null, $qrMode, $status, (int)($_SESSION['user_id'] ?? 0)]);
            $id = (int)$pdo->lastInsertId();
        }

        setFlash('success', 'Attendance event saved.');
        header('Location: ../event-report.php?id=' . $id);
        exit;
    }

    if ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM attendance_events WHERE id = ?");
        $stmt->execute([$id]);
        setFlash('success', 'Attendance event deleted.');
        header('Location: ../events.php');
        exit;
    }

    throw new RuntimeException('Invalid action.');
} catch (Throwable $e) {
    setFlash('error', $e->getMessage());
    header('Location: ../create-event.php' . ($id > 0 ? ('?id=' . $id) : ''));
    exit;
}
