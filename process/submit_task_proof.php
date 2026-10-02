<?php
header('Content-Type: application/json');

require '../config/db.php';
require '../includes/functions.php';
require '../includes/upload_validator.php';

if (empty($_SESSION['student_logged_in']) || empty($_SESSION['student_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please log in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$csrf = (string)($_POST['csrf_token'] ?? '');
if ($csrf === '' || empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], $csrf)) {
    echo json_encode(['success' => false, 'message' => 'Security token expired. Please refresh and try again.']);
    exit;
}

if (!rate_limit_check('task_proof_' . (int)$_SESSION['student_id'], 20, 3600)) {
    echo json_encode(['success' => false, 'message' => 'Too many submissions. Please try again later.']);
    exit;
}

$studentId = (int)$_SESSION['student_id'];
$taskId = (int)($_POST['task_id'] ?? 0);
$notes = cleanInput($_POST['notes'] ?? '');

if ($taskId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid task ID.']);
    exit;
}

// Check if task exists and is active
$taskStmt = $pdo->prepare("SELECT * FROM sa_tasks WHERE id = ? AND status = 'Active' LIMIT 1");
$taskStmt->execute([$taskId]);
$task = $taskStmt->fetch();
if (!$task) {
    echo json_encode(['success' => false, 'message' => 'Campaign task not found or inactive.']);
    exit;
}

// Check if already approved/submitted
$subStmt = $pdo->prepare("SELECT status FROM sa_task_submissions WHERE student_id = ? AND task_id = ? LIMIT 1");
$subStmt->execute([$studentId, $taskId]);
$existingStatus = $subStmt->fetchColumn();

if ($existingStatus === 'Approved') {
    echo json_encode(['success' => false, 'message' => 'This task has already been approved. You cannot resubmit.']);
    exit;
} elseif ($existingStatus === 'Submitted') {
    echo json_encode(['success' => false, 'message' => 'Your submission is already pending review.']);
    exit;
}

$photoPath = null;
if (isset($_FILES['proof_screenshot']) && $_FILES['proof_screenshot']['error'] === UPLOAD_ERR_OK) {
    $validation = validateUploadedFile(
        $_FILES['proof_screenshot'],
        ['image/jpeg', 'image/png', 'image/webp'],
        5 * 1024 * 1024,
        ['jpg', 'jpeg', 'png', 'webp']
    );
    if (empty($validation['success'])) {
        echo json_encode(['success' => false, 'message' => $validation['message'] ?? 'Invalid proof file.']);
        exit;
    }

    $stored = storeValidatedUpload(
        $_FILES['proof_screenshot'],
        dirname(__DIR__) . '/uploads/submissions',
        'uploads/submissions',
        'task'
    );
    if (empty($stored['success'])) {
        echo json_encode(['success' => false, 'message' => $stored['message'] ?? 'Failed to upload proof screenshot file.']);
        exit;
    }
    $photoPath = $stored['relative_path'];
} else {
    echo json_encode(['success' => false, 'message' => 'Screenshot proof is required.']);
    exit;
}

try {
    if ($existingStatus === 'Rejected') {
        // Delete or update rejection to submitted
        $sql = "UPDATE sa_task_submissions SET proof_path = ?, notes = ?, status = 'Submitted', created_at = NOW() WHERE student_id = ? AND task_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$photoPath, $notes ?: null, $studentId, $taskId]);
    } else {
        $sql = "INSERT INTO sa_task_submissions (task_id, student_id, proof_path, notes, status, created_at) VALUES (?, ?, ?, ?, 'Submitted', NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$taskId, $studentId, $photoPath, $notes ?: null]);
    }

    // Log Activity
    $logSql = "INSERT INTO sa_activity_logs (student_id, activity_type, title, description, points, created_at) 
               VALUES (?, 'task_submission', 'Submitted Task Proof', ?, 0, NOW())";
    $logStmt = $pdo->prepare($logSql);
    $logStmt->execute([$studentId, "Submitted proof for task: " . $task['title']]);

    echo json_encode(['success' => true, 'message' => 'Task proof submitted successfully!']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'A database error occurred. Details: ' . $e->getMessage()]);
}

exit;
