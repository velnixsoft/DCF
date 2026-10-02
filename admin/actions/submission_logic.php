<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/student/levels.php';
require_once __DIR__ . '/../../includes/student/badges.php';
require_once __DIR__ . '/../../includes/student/points.php';
require_once __DIR__ . '/../../includes/student/student_notify_helper.php';
require_once __DIR__ . '/../../includes/admin_audit.php';

// Verify permission
if (!checkRole($pdo, 'coordinator')) {
    setFlash('error', 'Unauthorized access.');
    header('Location: ../dashboard.php');
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$csrfToken = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';

if (empty($_SESSION['csrf_token']) || $csrfToken !== $_SESSION['csrf_token']) {
    setFlash('error', 'CSRF verification failed.');
    header('Location: ../student_submissions.php');
    exit;
}

$adminUserId = (int)($_SESSION['user_id'] ?? 0);

if ($action === 'approve') {
    $id = (int)$_POST['id'];
    if ($id > 0) {
        try {
            $pdo->beginTransaction();

            // Fetch submission details
            $subStmt = $pdo->prepare("
                SELECT s.*, t.title as task_title, t.points_reward, st.full_name as student_name, st.level_name, st.total_points
                FROM sa_task_submissions s
                JOIN sa_tasks t ON s.task_id = t.id
                JOIN sa_students st ON s.student_id = st.id
                WHERE s.id = ? LIMIT 1
            ");
            $subStmt->execute([$id]);
            $sub = $subStmt->fetch();

            if (!$sub) {
                setFlash('error', 'Submission not found.');
                header('Location: ../student_submissions.php');
                exit;
            }

            if ($sub['status'] === 'Approved') {
                setFlash('error', 'Submission has already been approved.');
                header('Location: ../student_submissions.php');
                exit;
            }

            // Update submission status
            $upStmt = $pdo->prepare("UPDATE sa_task_submissions SET status = 'Approved', reviewed_by_user_id = ?, reviewed_at = NOW() WHERE id = ?");
            $upStmt->execute([$adminUserId, $id]);

            $pointsReward = (int)$sub['points_reward'];
            $studentId = (int)$sub['student_id'];

            $pointEngine = new StudentPointEngine($pdo, $adminUserId);
            $pointResult = $pointEngine->awardPoints($studentId, 'TASK_SUBMISSION_APPROVED', [
                'source_type' => 'task_approval',
                'source_id' => $id,
                'base_points' => $pointsReward,
                'points_delta' => $pointsReward,
                'reference_code' => 'TASK-SUB-' . $id,
                'description' => 'Approved task proof: ' . $sub['task_title'],
                'idempotency_key' => 'task-submission-approve:' . $id,
                'approved_by_user_id' => $adminUserId,
            ]);

            if (empty($pointResult['success'])) {
                throw new Exception($pointResult['message'] ?? 'Failed to award task points.');
            }

            $levelEngine = new StudentLevelEngine($pdo, $adminUserId);
            $levelEngine->evaluatePromotion($studentId, ['apply' => true]);

            $badgeEngine = new StudentBadgeEngine($pdo, $adminUserId);
            $badgeEngine->evaluateBadges($studentId, ['apply' => true]);

            $pdo->commit();

            student_send_notification($pdo, $studentId, 'submission_approved', [
                'student_name' => $sub['student_name'],
                'task_title' => $sub['task_title'],
                'points_earned' => $pointsReward,
                'feedback' => 'Great work!',
            ]);
            admin_audit_log($pdo, 'submission_approve', 'sa_task_submissions', $id, 'Approved task: ' . $sub['task_title']);

            setFlash('success', 'Submission approved and points rewarded successfully!');
        } catch (Exception $e) {
            $pdo->rollBack();
            setFlash('error', 'Database error: ' . $e->getMessage());
        }
    }
} elseif ($action === 'reject') {
    $id = (int)$_POST['id'];
    $adminComment = cleanInput($_POST['admin_comment'] ?? '');

    if ($id > 0) {
        try {
            // Fetch submission details
            $subStmt = $pdo->prepare("
                SELECT s.*, t.title as task_title
                FROM sa_task_submissions s
                JOIN sa_tasks t ON s.task_id = t.id
                WHERE s.id = ? LIMIT 1
            ");
            $subStmt->execute([$id]);
            $sub = $subStmt->fetch();

            if (!$sub) {
                setFlash('error', 'Submission not found.');
                header('Location: ../student_submissions.php');
                exit;
            }

            if ($sub['status'] === 'Approved') {
                setFlash('error', 'Approved submissions cannot be rejected.');
                header('Location: ../student_submissions.php');
                exit;
            }

            // Update submission status
            $upStmt = $pdo->prepare("UPDATE sa_task_submissions SET status = 'Rejected', admin_comment = ?, reviewed_by_user_id = ?, reviewed_at = NOW() WHERE id = ?");
            $upStmt->execute([$adminComment ?: null, $adminUserId, $id]);

            // Log activity
            $studentId = (int)$sub['student_id'];
            $logStmt = $pdo->prepare("INSERT INTO sa_activity_logs (student_id, activity_type, title, description, points, created_at) VALUES (?, 'task_rejection', 'Task Proof Rejected', ?, 0, NOW())");
            $logStmt->execute([$studentId, "Rejected task proof: " . $sub['task_title'] . ($adminComment ? ". Reason: $adminComment" : "")]);

            $nameStmt = $pdo->prepare("SELECT full_name FROM sa_students WHERE id = ? LIMIT 1");
            $nameStmt->execute([$studentId]);
            $studentName = (string)($nameStmt->fetchColumn() ?: 'Student');

            student_send_notification($pdo, $studentId, 'submission_rejected', [
                'student_name' => $studentName,
                'task_title' => $sub['task_title'],
                'feedback' => $adminComment ?: 'Please review the task requirements and resubmit.',
            ]);
            admin_audit_log($pdo, 'submission_reject', 'sa_task_submissions', $id, 'Rejected task: ' . $sub['task_title']);

            setFlash('success', 'Submission rejected successfully.');
        } catch (Exception $e) {
            setFlash('error', 'Database error: ' . $e->getMessage());
        }
    }
}

header('Location: ../student_submissions.php');
exit;
