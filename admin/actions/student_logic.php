<?php
require_once __DIR__ . '/../../config/db.php';


require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/student/levels.php';
require_once __DIR__ . '/../../includes/student/badges.php';
require_once __DIR__ . '/../../includes/student/points.php';
require_once __DIR__ . '/../../includes/student/student_notify_helper.php';
require_once __DIR__ . '/../../includes/student/certificates.php';
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
    header('Location: ../student_directory.php');
    exit;
}

$adminUserId = (int)($_SESSION['user_id'] ?? 0);

if ($action === 'approve') {
    $id = (int)$_POST['id'];
    if ($id > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE sa_students SET status = 'Active', is_verified = 1, approved_at = NOW(), approved_by_user_id = ? WHERE id = ?");
            $stmt->execute([$adminUserId, $id]);
            
            // Log activity
            $logStmt = $pdo->prepare("INSERT INTO sa_activity_logs (student_id, activity_type, title, description, points, created_at) VALUES (?, 'onboarding_approval', 'Registration Approved', 'Account verified by administrator', 0, NOW())");
            $logStmt->execute([$id]);

            $nameStmt = $pdo->prepare("SELECT full_name FROM sa_students WHERE id = ? LIMIT 1");
            $nameStmt->execute([$id]);
            $studentName = (string)($nameStmt->fetchColumn() ?: 'Student');
            student_send_notification($pdo, $id, 'registration_approved', [
                'student_name' => $studentName,
            ]);
            admin_audit_log($pdo, 'student_approve', 'sa_students', $id, 'Student registration approved');

            // Automatically verify any pending referrals for this student
            $refStmt = $pdo->prepare("SELECT id FROM sa_referrals WHERE referred_student_id = ? AND verification_status = 'pending' LIMIT 1");
            $refStmt->execute([$id]);
            $referralId = $refStmt->fetchColumn();
            if ($referralId) {
                require_once __DIR__ . '/../../includes/student/referral_service.php';
                $referralService = new StudentReferralService($pdo, $adminUserId);
                $referralService->verifyReferral((int)$referralId);
            }

            setFlash('success', 'Student Ambassador registration approved successfully!');
        } catch (Exception $e) {
            setFlash('error', 'Database error: ' . $e->getMessage());
        }
    }
} elseif ($action === 'update_status') {
    $id = (int)$_POST['id'];
    $status = cleanInput($_POST['status'] ?? '');
    $reason = cleanInput($_POST['reason'] ?? '');
    
    if ($id > 0 && in_array($status, ['Active', 'Inactive', 'Suspended', 'Pending', 'Rejected'])) {
        try {
            $stmt = $pdo->prepare("UPDATE sa_students SET status = ?, rejection_reason = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $reason ?: null, $id]);
            
            // Log activity
            $logStmt = $pdo->prepare("INSERT INTO sa_activity_logs (student_id, activity_type, title, description, points, created_at) VALUES (?, 'status_update', 'Status Updated', ?, 0, NOW())");
            $logStmt->execute([$id, "Status changed to $status" . ($reason ? ". Reason: $reason" : "")]);

            // Sync referrals status if becoming Active or Rejected
            if ($status === 'Active') {
                $refStmt = $pdo->prepare("SELECT id FROM sa_referrals WHERE referred_student_id = ? AND verification_status = 'pending' LIMIT 1");
                $refStmt->execute([$id]);
                $referralId = $refStmt->fetchColumn();
                if ($referralId) {
                    require_once __DIR__ . '/../../includes/student/referral_service.php';
                    $referralService = new StudentReferralService($pdo, $adminUserId);
                    $referralService->verifyReferral((int)$referralId);
                }
            } elseif ($status === 'Rejected') {
                $refStmt = $pdo->prepare("SELECT id FROM sa_referrals WHERE referred_student_id = ? AND verification_status = 'pending' LIMIT 1");
                $refStmt->execute([$id]);
                $referralId = $refStmt->fetchColumn();
                if ($referralId) {
                    require_once __DIR__ . '/../../includes/student/referral_service.php';
                    $referralService = new StudentReferralService($pdo, $adminUserId);
                    $referralService->rejectReferral((int)$referralId, $reason ?: 'Referred student registration rejected');
                }
            }

            setFlash('success', "Student Ambassador status updated to $status.");
        } catch (Exception $e) {
            setFlash('error', 'Database error: ' . $e->getMessage());
        }
    }
} elseif ($action === 'adjust_points') {
    $id = (int)$_POST['id'];
    $points = (int)($_POST['points'] ?? 0);
    $reason = cleanInput($_POST['reason'] ?? 'Manual adjustment');

    if ($id > 0 && $points !== 0) {
        try {
            $pdo->beginTransaction();

            $pointEngine = new StudentPointEngine($pdo, $adminUserId);
            if ($points > 0) {
                $pointResult = $pointEngine->awardPoints($id, 'MANUAL_POINT_ADJUSTMENT', [
                    'source_type' => 'manual_adjustment',
                    'source_id' => $adminUserId,
                    'base_points' => $points,
                    'points_delta' => $points,
                    'description' => $reason,
                    'reference_code' => 'MANUAL-' . $id . '-' . time(),
                    'idempotency_key' => 'manual-adjust:' . $id . ':' . time() . ':' . $points,
                    'approved_by_user_id' => $adminUserId,
                ]);
            } else {
                $pointResult = $pointEngine->deductPoints($id, 'MANUAL_POINT_ADJUSTMENT', [
                    'source_type' => 'manual_adjustment',
                    'source_id' => $adminUserId,
                    'base_points' => abs($points),
                    'points_delta' => $points,
                    'description' => $reason,
                    'reference_code' => 'MANUAL-' . $id . '-' . time(),
                    'idempotency_key' => 'manual-adjust:' . $id . ':' . time() . ':' . $points,
                    'approved_by_user_id' => $adminUserId,
                ]);
            }

            if (empty($pointResult['success'])) {
                throw new Exception($pointResult['message'] ?? 'Point adjustment failed.');
            }

            $levelEngine = new StudentLevelEngine($pdo, $adminUserId);
            $levelEngine->evaluatePromotion($id, ['apply' => true]);

            $badgeEngine = new StudentBadgeEngine($pdo, $adminUserId);
            $badgeEngine->evaluateBadges($id, ['apply' => true]);

            $pdo->commit();
            admin_audit_log($pdo, 'student_adjust_points', 'sa_students', $id, $reason . ' (' . $points . ' pts)');
            setFlash('success', 'Points adjusted successfully!');
        } catch (Exception $e) {
            $pdo->rollBack();
            setFlash('error', 'Database error: ' . $e->getMessage());
        }
    }
} elseif ($action === 'award_badge') {
    $id = (int)$_POST['id'];
    $badgeId = (int)($_POST['badge_id'] ?? 0);
    $notes = cleanInput($_POST['notes'] ?? '');

    if ($id > 0 && $badgeId > 0) {
        try {
            $badgeEngine = new StudentBadgeEngine($pdo, $adminUserId);
            $result = $badgeEngine->manualAwardBadge($id, $badgeId, [
                'notes' => $notes ?: null,
                'override_reason' => 'Manual coordinator award.',
            ]);

            if (empty($result['success'])) {
                setFlash('error', 'Badge award failed: ' . ($result['message'] ?? 'Unknown error.'));
            } elseif (!empty($result['duplicate'])) {
                setFlash('error', 'This student has already been awarded this badge.');
            } else {
                setFlash('success', 'Badge awarded successfully!');
            }
        } catch (Exception $e) {
            setFlash('error', 'Database error: ' . $e->getMessage());
        }
    }
} elseif ($action === 'issue_certificate') {
    $id = (int)($_POST['id'] ?? 0);
    $certificateType = cleanInput($_POST['certificate_type'] ?? 'appreciation');
    $certificateTitle = cleanInput($_POST['certificate_title'] ?? '');
    $templateId = (int)($_POST['template_id'] ?? 0);
    $issuedFor = cleanInput($_POST['issued_for'] ?? '');
    $redirect = cleanInput($_POST['redirect'] ?? 'student_certificates.php');

    if ($id > 0 && $certificateTitle !== '') {
        try {
            $service = new StudentCertificateService($pdo);
            $result = $service->issueCertificate($id, $certificateType, $certificateTitle, $adminUserId, [
                'template_id' => $templateId,
                'issued_for' => $issuedFor,
            ]);
            admin_audit_log($pdo, 'student_issue_certificate', 'sa_certificates', (int)$result['id'], $certificateTitle);
            setFlash('success', 'Certificate generated: ' . $result['certificate_no']);
        } catch (Throwable $e) {
            setFlash('error', 'Certificate generation failed: ' . $e->getMessage());
        }
    } else {
        setFlash('error', 'Student and certificate title are required.');
    }

    header('Location: ../' . ($redirect !== '' ? basename($redirect) : 'student_certificates.php'));
    exit;
}

header('Location: ../student_directory.php');
exit;
