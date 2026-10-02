<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

// Verify role
if (!checkRole($pdo, 'manager')) {
    setFlash('error', 'Unauthorized access.');
    header('Location: ../dashboard.php');
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$csrfToken = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';

if (empty($_SESSION['csrf_token']) || $csrfToken !== $_SESSION['csrf_token']) {
    setFlash('error', 'CSRF verification failed.');
    header('Location: ../student_tasks.php');
    exit;
}

$adminUserId = (int)($_SESSION['user_id'] ?? 0);

if ($action === 'save') {
    $id = (int)($_POST['id'] ?? 0);
    $title = cleanInput($_POST['title'] ?? '');
    $description = cleanInput($_POST['description'] ?? '');
    $campaignName = cleanInput($_POST['campaign_name'] ?? '');
    $taskType = cleanInput($_POST['task_type'] ?? 'Social Media');
    $dueDate = cleanInput($_POST['due_date'] ?? '');
    $points = (int)($_POST['points_reward'] ?? 25);
    $status = cleanInput($_POST['status'] ?? 'Draft');

    if (empty($title)) {
        setFlash('error', 'Task Title is required.');
        header('Location: ../student_tasks.php');
        exit;
    }

    if (!preg_match('/^[a-zA-Z\s]+$/', $title)) {
        setFlash('error', 'Task Title must contain only letters and spaces.');
        header('Location: ../student_tasks.php');
        exit;
    }

    if (!empty($dueDate)) {
        $dateObj = DateTime::createFromFormat('Y-m-d', $dueDate);
        if (!$dateObj || $dateObj->format('Y-m-d') !== $dueDate) {
            setFlash('error', 'Invalid Due Date format.');
            header('Location: ../student_tasks.php');
            exit;
        }
        $year = (int)$dateObj->format('Y');
        if ($year > 9999) {
            setFlash('error', 'Year cannot be more than 4 digits.');
            header('Location: ../student_tasks.php');
            exit;
        }
        if (strtotime($dueDate) < strtotime(date('Y-m-d'))) {
            setFlash('error', 'Due Date cannot be in the past.');
            header('Location: ../student_tasks.php');
            exit;
        }
    }

    try {
        if ($id > 0) {
            // Update
            $sql = "UPDATE sa_tasks SET title = ?, description = ?, campaign_name = ?, task_type = ?, due_date = ?, points_reward = ?, status = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$title, $description ?: null, $campaignName ?: null, $taskType, $dueDate ?: null, $points, $status, $id]);
            setFlash('success', 'Campaign task updated successfully!');
        } else {
            // Insert
            $sql = "INSERT INTO sa_tasks (title, description, campaign_name, task_type, due_date, points_reward, status, created_by_user_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$title, $description ?: null, $campaignName ?: null, $taskType, $dueDate ?: null, $points, $status, $adminUserId]);
            setFlash('success', 'New campaign task created successfully!');
        }
    } catch (Exception $e) {
        setFlash('error', 'Database error: ' . $e->getMessage());
    }
} elseif ($action === 'delete') {
    $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
    if ($id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM sa_tasks WHERE id = ?");
            $stmt->execute([$id]);
            setFlash('success', 'Campaign task deleted successfully!');
        } catch (Exception $e) {
            setFlash('error', 'Database error: ' . $e->getMessage());
        }
    }
}

header('Location: ../student_tasks.php');
exit;
