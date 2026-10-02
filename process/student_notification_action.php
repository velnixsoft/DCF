<?php
session_start();
require '../config/db.php';
require '../includes/functions.php';

if (empty($_SESSION['student_logged_in']) || empty($_SESSION['student_id'])) {
    setFlash('error', 'Please login to manage notifications.');
    header('Location: ../student-login.php');
    exit;
}

$csrf = (string)($_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '');
if ($csrf === '' || empty($_SESSION['student_notification_csrf']) || !hash_equals((string)$_SESSION['student_notification_csrf'], $csrf)) {
    setFlash('error', 'Security check failed. Please try again.');
    header('Location: ../student-notifications.php');
    exit;
}

$action = (string)($_POST['action'] ?? $_GET['action'] ?? '');
$studentId = (int)$_SESSION['student_id'];

try {
    if ($action === 'mark_read') {
        $notificationId = (int)($_POST['notification_id'] ?? $_GET['notification_id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE sa_notifications SET is_read = 1, read_at = NOW() WHERE id = ? AND student_id = ? LIMIT 1");
        $stmt->execute([$notificationId, $studentId]);
        setFlash('success', 'Notification marked as read.');
    } elseif ($action === 'mark_all_read') {
        $stmt = $pdo->prepare("UPDATE sa_notifications SET is_read = 1, read_at = NOW() WHERE student_id = ? AND is_read = 0");
        $stmt->execute([$studentId]);
        setFlash('success', 'All notifications marked as read.');
    } else {
        setFlash('error', 'Unsupported notification action.');
    }
} catch (Throwable $e) {
    setFlash('error', 'Unable to update notifications right now.');
}

header('Location: ../student-notifications.php');
exit;
