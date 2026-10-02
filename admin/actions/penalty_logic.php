<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/student/points.php';
require_once __DIR__ . '/../../includes/admin_audit.php';

if (!checkRole($pdo, 'coordinator')) {
    header('Location: ../dashboard.php');
    exit;
}

$csrf = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || $csrf !== $_SESSION['csrf_token']) {
    setFlash('error', 'CSRF failed.');
    header('Location: ../student_penalties.php');
    exit;
}

$studentId = (int)($_POST['student_id'] ?? 0);
$penaltyCode = cleanInput($_POST['penalty_code'] ?? '');
$reason = cleanInput($_POST['reason'] ?? '');
$adminId = (int)($_SESSION['user_id'] ?? 0);

if ($studentId > 0 && $penaltyCode !== '' && $reason !== '') {
    try {
        $engine = new StudentPointEngine($pdo, $adminId);
        $result = $engine->applyPenalty($studentId, $penaltyCode, [
            'reason_title' => $reason,
            'reason_details' => $reason,
        ]);
        if (!empty($result['success'])) {
            admin_audit_log($pdo, 'student_penalty', 'sa_penalties', (int)($result['penalty_id'] ?? 0), $penaltyCode);
            setFlash('success', 'Penalty applied.');
        } else {
            setFlash('error', $result['message'] ?? 'Penalty failed.');
        }
    } catch (Throwable $e) {
        setFlash('error', $e->getMessage());
    }
}

header('Location: ../student_penalties.php');
exit;
