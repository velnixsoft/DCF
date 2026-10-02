<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!checkRole($pdo, 'coordinator')) {
    setFlash('error', 'Unauthorized.');
    header('Location: ../dashboard.php');
    exit;
}

$csrf = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || $csrf !== $_SESSION['csrf_token']) {
    setFlash('error', 'CSRF failed.');
    header('Location: ../student_event_requests.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$action = cleanInput($_POST['action'] ?? '');

if ($id > 0 && dbTableExists($pdo, 'sa_student_event_requests')) {
    $status = $action === 'approve' ? 'Approved' : ($action === 'reject' ? 'Rejected' : '');
    if ($status !== '') {
        $adminId = (int)($_SESSION['user_id'] ?? 0);
        $reqStmt = $pdo->prepare('SELECT * FROM sa_student_event_requests WHERE id = ? LIMIT 1');
        $reqStmt->execute([$id]);
        $req = $reqStmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare('UPDATE sa_student_event_requests SET status = ?, reviewed_by_user_id = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$status, $adminId, $id]);

        if ($status === 'Approved' && $req && dbTableExists($pdo, 'sa_events')) {
            $ins = $pdo->prepare("
                INSERT INTO sa_events (title, description, event_type, city_name, venue, start_date, status, submitted_by_student_id, reviewed_by_user_id, created_at, updated_at)
                VALUES (?, ?, 'student_campaign', ?, ?, ?, 'Approved', ?, ?, NOW(), NOW())
            ");
            $ins->execute([
                $req['event_title'],
                $req['description'],
                null,
                $req['event_location'],
                $req['event_date'] ? ($req['event_date'] . ' 10:00:00') : null,
                (int)$req['student_id'],
                $adminId,
            ]);
            $eventId = (int)$pdo->lastInsertId();
            $pdo->prepare('UPDATE sa_student_event_requests SET linked_event_id = ? WHERE id = ?')->execute([$eventId, $id]);
        }

        setFlash('success', 'Event request marked as ' . $status . '.');
    }
}

header('Location: ../student_event_requests.php');
exit;
