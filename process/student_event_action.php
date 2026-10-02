<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/student/portal_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$csrf = (string)($_POST['csrf_token'] ?? '');
if ($csrf === '' || empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], $csrf)) {
    echo json_encode(['success' => false, 'message' => 'Security token expired.']);
    exit;
}

if (empty($_SESSION['student_logged_in']) || empty($_SESSION['student_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first.']);
    exit;
}

$studentId = (int)$_SESSION['student_id'];
$action = cleanInput($_POST['action'] ?? '');

$stmt = $pdo->prepare('SELECT * FROM sa_students WHERE id = ? LIMIT 1');
$stmt->execute([$studentId]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$student || ($student['status'] ?? '') !== 'Active') {
    echo json_encode(['success' => false, 'message' => 'Account not active.']);
    exit;
}

try {
    if ($action === 'register_sa' && dbTableExists($pdo, 'sa_events') && dbTableExists($pdo, 'sa_event_registrations')) {
        $eventId = (int)($_POST['event_id'] ?? 0);
        $eStmt = $pdo->prepare("SELECT id, title, status FROM sa_events WHERE id = ? AND status IN ('Approved','Live','Upcoming') LIMIT 1");
        $eStmt->execute([$eventId]);
        $event = $eStmt->fetch(PDO::FETCH_ASSOC);
        if (!$event) {
            echo json_encode(['success' => false, 'message' => 'Event not available.']);
            exit;
        }
        $dup = $pdo->prepare('SELECT id FROM sa_event_registrations WHERE event_id = ? AND student_id = ? LIMIT 1');
        $dup->execute([$eventId, $studentId]);
        if ($dup->fetchColumn()) {
            echo json_encode(['success' => false, 'message' => 'Already registered.']);
            exit;
        }
        $pdo->prepare("INSERT INTO sa_event_registrations (event_id, student_id, status) VALUES (?, ?, 'Registered')")->execute([$eventId, $studentId]);
        echo json_encode(['success' => true, 'message' => 'Registered for ' . $event['title'] . '.']);
        exit;
    }

    if ($action === 'request' && dbTableExists($pdo, 'sa_events')) {
        $title = cleanInput($_POST['event_title'] ?? '');
        if ($title === '') {
            echo json_encode(['success' => false, 'message' => 'Event title required.']);
            exit;
        }
        $pdo->prepare("
            INSERT INTO sa_events (title, description, venue, start_date, status, submitted_by_student_id, created_at, updated_at)
            VALUES (?, ?, ?, ?, 'Pending', ?, NOW(), NOW())
        ")->execute([
            $title,
            cleanInput($_POST['description'] ?? '') ?: null,
            cleanInput($_POST['event_location'] ?? '') ?: null,
            !empty($_POST['event_date']) ? ($_POST['event_date'] . ' 10:00:00') : null,
            $studentId,
        ]);
        if (dbTableExists($pdo, 'sa_student_event_requests')) {
            $pdo->prepare("
                INSERT INTO sa_student_event_requests (student_id, event_title, event_date, event_location, description, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, 'Pending', NOW(), NOW())
            ")->execute([$studentId, $title, $_POST['event_date'] ?? null, cleanInput($_POST['event_location'] ?? '') ?: null, cleanInput($_POST['description'] ?? '') ?: null]);
        }
        echo json_encode(['success' => true, 'message' => 'Event proposal submitted for admin approval.']);
        exit;
    }

    if ($action === 'register') {
        $eventId = (int)($_POST['event_id'] ?? 0);
        if ($eventId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid event.']);
            exit;
        }

        $eStmt = $pdo->prepare("SELECT id, title, registration_open, status, max_registrations FROM events WHERE id = ? LIMIT 1");
        $eStmt->execute([$eventId]);
        $event = $eStmt->fetch(PDO::FETCH_ASSOC);
        if (!$event || (int)($event['registration_open'] ?? 0) !== 1) {
            echo json_encode(['success' => false, 'message' => 'Registration is closed.']);
            exit;
        }

        $dup = $pdo->prepare('SELECT id FROM event_registrations WHERE event_id = ? AND sa_student_id = ? LIMIT 1');
        $dup->execute([$eventId, $studentId]);
        if ($dup->fetchColumn()) {
            echo json_encode(['success' => false, 'message' => 'You are already registered.']);
            exit;
        }

        $hasCol = false;
        try {
            $pdo->query('SELECT sa_student_id FROM event_registrations LIMIT 1');
            $hasCol = true;
        } catch (Throwable $e) {
            $hasCol = false;
        }

        if ($hasCol) {
            $ins = $pdo->prepare("INSERT INTO event_registrations (event_id, sa_student_id, name, email, phone, status) VALUES (?, ?, ?, ?, ?, 'Registered')");
            $ins->execute([$eventId, $studentId, $student['full_name'], $student['email'], $student['mobile']]);
        } else {
            $ins = $pdo->prepare("INSERT INTO event_registrations (event_id, name, email, phone, status) VALUES (?, ?, ?, ?, 'Registered')");
            $ins->execute([$eventId, $student['full_name'], $student['email'], $student['mobile']]);
        }

        echo json_encode(['success' => true, 'message' => 'Registered for ' . $event['title'] . '.']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Action failed. Please try again.']);
}
