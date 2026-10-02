<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/student/student_notify_helper.php';

if (!checkRole($pdo, 'coordinator')) {
    setFlash('error', 'Unauthorized.');
    header('Location: ../dashboard.php');
    exit;
}

$csrf = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || $csrf !== $_SESSION['csrf_token']) {
    setFlash('error', 'CSRF failed.');
    header('Location: ../student_announcements.php');
    exit;
}

$action = $_POST['action'] ?? '';
$adminId = (int)($_SESSION['user_id'] ?? 0);

if ($action === 'broadcast' && dbTableExists($pdo, 'sa_announcements')) {
    $title = cleanInput($_POST['title'] ?? '');
    $message = cleanInput($_POST['message'] ?? '');
    $scope = in_array($_POST['target_scope'] ?? 'all', ['all', 'city', 'college', 'level'], true) ? $_POST['target_scope'] : 'all';
    $targetValue = cleanInput($_POST['target_value'] ?? '');

    if ($title !== '' && $message !== '') {
        $ins = $pdo->prepare('INSERT INTO sa_announcements (title, message, target_scope, target_value, created_by_user_id) VALUES (?, ?, ?, ?, ?)');
        $ins->execute([$title, $message, $scope, $targetValue !== '' ? $targetValue : null, $adminId]);

        $where = "status = 'Active'";
        $params = [];
        if ($scope === 'city' && $targetValue !== '') {
            $where .= ' AND city_name = ?';
            $params[] = $targetValue;
        } elseif ($scope === 'college' && $targetValue !== '') {
            $where .= ' AND college_name = ?';
            $params[] = $targetValue;
        } elseif ($scope === 'level' && $targetValue !== '') {
            $where .= ' AND level_name = ?';
            $params[] = $targetValue;
        }

        $students = $pdo->prepare("SELECT id, full_name FROM sa_students WHERE {$where}");
        $students->execute($params);
        $count = 0;
        foreach ($students->fetchAll(PDO::FETCH_ASSOC) as $row) {
            student_send_notification($pdo, (int)$row['id'], 'announcement', [
                'student_name' => $row['full_name'],
                'announcement_title' => $title,
                'announcement_message' => $message,
            ]);
            $count++;
        }
        setFlash('success', "Announcement sent to {$count} students.");
    }
}

header('Location: ../student_announcements.php');
exit;
