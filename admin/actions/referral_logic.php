<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/student/referral_service.php';
require_once __DIR__ . '/../../includes/admin_audit.php';

if (!checkRole($pdo, 'coordinator')) {
    setFlash('error', 'Unauthorized access.');
    header('Location: ../dashboard.php');
    exit;
}

$action = $_POST['action'] ?? '';
$csrfToken = $_POST['csrf_token'] ?? '';

if (empty($_SESSION['csrf_token']) || $csrfToken !== $_SESSION['csrf_token']) {
    setFlash('error', 'CSRF verification failed.');
    header('Location: ../student_referrals.php');
    exit;
}

$adminUserId = (int)($_SESSION['user_id'] ?? 0);
$referralService = new StudentReferralService($pdo, $adminUserId);
$referralId = (int)($_POST['id'] ?? 0);

if ($referralId <= 0) {
    setFlash('error', 'Invalid referral selected.');
    header('Location: ../student_referrals.php');
    exit;
}

if ($action === 'verify') {
    $result = $referralService->verifyReferral($referralId);
    if (!empty($result['success'])) {
        admin_audit_log($pdo, 'referral_verify', 'sa_referrals', $referralId, $result['message'] ?? 'Referral verified');
    }
    setFlash(!empty($result['success']) ? 'success' : 'error', $result['message'] ?? 'Referral action failed.');
} elseif ($action === 'reject') {
    $reason = cleanInput($_POST['reason'] ?? '');
    $result = $referralService->rejectReferral($referralId, $reason);
    if (!empty($result['success'])) {
        admin_audit_log($pdo, 'referral_reject', 'sa_referrals', $referralId, $reason !== '' ? $reason : 'Referral rejected');
    }
    setFlash(!empty($result['success']) ? 'success' : 'error', $result['message'] ?? 'Referral action failed.');
} else {
    setFlash('error', 'Unknown action.');
}

header('Location: ../student_referrals.php');
exit;
