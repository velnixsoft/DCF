<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!checkRole($pdo, 'coordinator')) {
    header('Location: ../dashboard.php');
    exit;
}

$csrf = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || $csrf !== $_SESSION['csrf_token']) {
    setFlash('error', 'CSRF failed.');
    header('Location: ../student_sa_events.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';
$adminId = (int)($_SESSION['user_id'] ?? 0);

if ($id > 0 && dbTableExists($pdo, 'sa_events')) {
    if ($action === 'approve') {
        $pdo->prepare("UPDATE sa_events SET status = 'Approved', reviewed_by_user_id = ?, updated_at = NOW() WHERE id = ?")->execute([$adminId, $id]);
        setFlash('success', 'Event approved. Students can register.');
    } elseif ($action === 'reject') {
        $pdo->prepare("UPDATE sa_events SET status = 'Rejected', reviewed_by_user_id = ?, updated_at = NOW() WHERE id = ?")->execute([$adminId, $id]);
        setFlash('success', 'Event rejected.');
    } elseif ($action === 'complete') {
        $pdo->prepare("UPDATE sa_events SET status = 'Completed', updated_at = NOW() WHERE id = ?")->execute([$id]);
        setFlash('success', 'Event marked completed.');
    }
}

header('Location: ../student_sa_events.php');
exit;
