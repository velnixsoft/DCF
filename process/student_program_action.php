<?php
session_start();
require '../config/db.php';
require '../includes/functions.php';

if (empty($_SESSION['student_logged_in']) || empty($_SESSION['student_id'])) {
    setFlash('error', 'Please login to manage your program.');
    header('Location: ../student-login.php');
    exit;
}

$csrf = (string)($_POST['csrf_token'] ?? '');
if ($csrf === '' || empty($_SESSION['student_program_csrf']) || !hash_equals((string)$_SESSION['student_program_csrf'], $csrf)) {
    setFlash('error', 'Security check failed. Please try again.');
    header('Location: ../student-program.php');
    exit;
}

$studentId = (int)$_SESSION['student_id'];
$action = (string)($_POST['action'] ?? '');

try {
    if ($action !== 'join_active') {
        throw new RuntimeException('Unsupported program action.');
    }

    $programStmt = $pdo->query("SELECT id, program_name FROM sa_programs WHERE status = 'active' ORDER BY start_date DESC, id DESC LIMIT 1");
    $program = $programStmt->fetch(PDO::FETCH_ASSOC);

    if (!$program) {
        throw new RuntimeException('No active program is available right now.');
    }

    $currentStmt = $pdo->prepare("SELECT program_id FROM sa_students WHERE id = ? LIMIT 1");
    $currentStmt->execute([$studentId]);
    $currentProgramId = $currentStmt->fetchColumn();

    if ((int)$currentProgramId === (int)$program['id']) {
        setFlash('success', 'You are already enrolled in the active program.');
        header('Location: ../student-program.php');
        exit;
    }

    $updateStmt = $pdo->prepare("UPDATE sa_students SET program_id = ?, updated_at = NOW() WHERE id = ? LIMIT 1");
    $updateStmt->execute([(int)$program['id'], $studentId]);

    setFlash('success', 'Joined program: ' . $program['program_name']);
    header('Location: ../student-program.php');
    exit;
} catch (Throwable $e) {
    setFlash('error', $e->getMessage() ?: 'Unable to update program right now.');
    header('Location: ../student-program.php');
    exit;
}

