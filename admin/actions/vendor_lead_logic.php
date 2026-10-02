<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Enable error reporting to identify issues
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    require_once __DIR__ . '/../../config/db.php';
    require_once __DIR__ . '/../../includes/functions.php';
    require_once __DIR__ . '/../../includes/student/vendor_leads.php';
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
        header('Location: ../student_vendor_leads.php');
        exit;
    }

    $leadId = (int)($_POST['id'] ?? 0);
    $adminUserId = (int)($_SESSION['user_id'] ?? 0);
    $engine = new StudentVendorLeadEngine($pdo, $adminUserId);

    if ($leadId > 0 && $action === 'verify') {
        $result = $engine->verifyLead($leadId, [
            'verification_notes' => cleanInput($_POST['verification_notes'] ?? ''),
        ]);

        if (!empty($result['success'])) {
            admin_audit_log($pdo, 'vendor_lead_verify', 'sa_vendor_leads', $leadId, $result['message'] ?? 'Vendor lead verified');
        }
        setFlash(!empty($result['success']) ? 'success' : 'error', $result['message'] ?? 'Lead verification completed.');
    } elseif ($leadId > 0 && $action === 'reject') {
        $result = $engine->rejectLead($leadId, cleanInput($_POST['verification_notes'] ?? ''));

        if (!empty($result['success'])) {
            admin_audit_log($pdo, 'vendor_lead_reject', 'sa_vendor_leads', $leadId, 'Vendor lead rejected');
        }
        setFlash(!empty($result['success']) ? 'success' : 'error', $result['message'] ?? 'Lead rejection completed.');
    }

    header('Location: ../student_vendor_leads.php');
    exit;
} catch (Throwable $e) {
    error_log($e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    setFlash('error', 'An internal error occurred: ' . $e->getMessage());
    header('Location: ../student_vendor_leads.php');
    exit;
}
