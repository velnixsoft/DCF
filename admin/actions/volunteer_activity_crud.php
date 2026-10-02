<?php
session_start();
require '../../config/db.php';
require '../../includes/functions.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

if (!canAccessModule($pdo, 'coordinator', 'page.volunteer_activities')) {
    setFlash('error', 'Access denied.');
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token'], $_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$_POST['csrf_token'])) {
    setFlash('error', 'Invalid request.');
    header('Location: ../volunteer_activities.php');
    exit;
}

$action = (string)($_POST['action'] ?? '');
$id = (int)($_POST['id'] ?? 0);

try {
    if ($action === 'delete') {
        if ($id <= 0) throw new Exception('Invalid id');
        $pdo->prepare("DELETE FROM volunteer_activities WHERE id = ?")->execute([$id]);
        setFlash('success', 'Activity deleted.');
        header('Location: ../volunteer_activities.php');
        exit;
    }

    if ($action !== 'create') throw new Exception('Invalid action');

    $volunteerId = (int)($_POST['volunteer_id'] ?? 0);
    $activityType = cleanInput($_POST['activity_type'] ?? '');
    $description = cleanInput($_POST['description'] ?? '');
    $eventId = isset($_POST['event_id']) && $_POST['event_id'] !== '' ? (int)$_POST['event_id'] : null;
    $hours = isset($_POST['hours_spent']) && $_POST['hours_spent'] !== '' ? (float)$_POST['hours_spent'] : null;

    if ($volunteerId <= 0) throw new Exception('Volunteer is required.');
    if ($hours !== null && $hours < 0) $hours = 0;

    $stmt = $pdo->prepare("INSERT INTO volunteer_activities (volunteer_id, activity_type, description, event_id, hours_spent) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$volunteerId, $activityType !== '' ? $activityType : null, $description !== '' ? $description : null, $eventId, $hours]);

    setFlash('success', 'Activity saved.');
    header('Location: ../volunteer_activities.php');
    exit;
} catch (Throwable $e) {
    setFlash('error', $e->getMessage() ?: 'Operation failed.');
    header('Location: ../volunteer_activities.php');
    exit;
}
