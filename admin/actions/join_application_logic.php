<?php
// ============================================================
// admin/actions/join_application_logic.php
// Backend Actions: Status Update, Delete, Modal Fetch, Excel Export
// Author: VELNIX SOFT / Antigravity AI
// Date: 2026-09-12
// ============================================================

session_start();

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/join_application_helper.php';

$action = cleanInput($_REQUEST['action'] ?? '');
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
    || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
    || isset($_POST['is_ajax']);

// Verify Coordinator / Manager / Admin Authentication
if (empty($_SESSION['user_id']) || !checkRole($pdo, 'coordinator')) {
    if ($isAjax) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
        exit;
    }
    setFlash('error', 'Unauthorized access.');
    header('Location: ../join_applications.php');
    exit;
}

$currentUserId = (int)$_SESSION['user_id'];

// ============================================================
// 1. ACTION: EXPORT TO EXCEL / CSV
// ============================================================
if ($action === 'export_csv' || $action === 'export_excel') {
    $filterType = cleanInput($_GET['type'] ?? '');
    $filterStatus = cleanInput($_GET['status'] ?? '');
    $filterPayment = cleanInput($_GET['payment_status'] ?? '');
    $search = cleanInput($_GET['search'] ?? '');

    $whereClauses = ['1=1'];
    $params = [];

    if ($filterType !== '' && in_array($filterType, ['join_foundation', 'join_project', 'job_application'], true)) {
        $whereClauses[] = "j.application_type = ?";
        $params[] = $filterType;
    }
    if ($filterStatus !== '') {
        $whereClauses[] = "j.status = ?";
        $params[] = $filterStatus;
    }
    if ($filterPayment !== '') {
        $whereClauses[] = "j.payment_status = ?";
        $params[] = $filterPayment;
    }
    if ($search !== '') {
        $whereClauses[] = "(j.application_no LIKE ? OR j.applicant_name LIKE ? OR j.contact LIKE ? OR j.email LIKE ? OR j.details LIKE ?)";
        $term = "%{$search}%";
        $params = array_merge($params, [$term, $term, $term, $term, $term]);
    }

    $whereSql = implode(' AND ', $whereClauses);
    $sql = "
        SELECT j.*, 
               p.title AS project_title,
               o.title AS job_title, o.job_code,
               u.name AS reviewer_name
        FROM join_applications j
        LEFT JOIN projects p ON j.project_id = p.id
        LEFT JOIN job_openings o ON j.job_id = o.id
        LEFT JOIN users u ON j.reviewed_by = u.id
        WHERE {$whereSql}
        ORDER BY j.id DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $filename = 'Join_Applications_' . date('Y-m-d_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    // UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // CSV Header Row
    fputcsv($output, [
        'ID',
        'Application No',
        'Applied Date',
        'Applicant Name',
        'Contact / Phone',
        'Email',
        'State',
        'District',
        'Application Type',
        'Target Entity (Project / Job)',
        'Statement / Details',
        'Fee Amount (INR)',
        'Payment Status',
        'Payment Method',
        'Transaction ID',
        'Application Status',
        'Admin Review Notes',
        'Reviewed By',
        'Reviewed At'
    ]);

    foreach ($rows as $row) {
        $targetEntity = match ($row['application_type']) {
            'join_project' => 'Project: ' . ($row['project_title'] ?: 'ID ' . $row['project_id']),
            'job_application' => 'Job: ' . ($row['job_title'] ?: 'ID ' . $row['job_id']) . ' (' . ($row['job_code'] ?: '') . ')',
            default => 'Foundation Membership'
        };

        fputcsv($output, [
            $row['id'],
            $row['application_no'],
            $row['applied_date'],
            $row['applicant_name'],
            $row['contact'],
            $row['email'] ?: '',
            $row['state'] ?: '',
            $row['district'] ?: '',
            $row['application_type'],
            $targetEntity,
            $row['details'] ?: '',
            $row['fee_amount'] !== null ? number_format((float)$row['fee_amount'], 2, '.', '') : '0.00',
            $row['payment_status'],
            $row['payment_method'] ?: '',
            $row['transaction_id'] ?: '',
            $row['status'],
            $row['admin_notes'] ?: '',
            $row['reviewer_name'] ?: '',
            $row['reviewed_at'] ?: ''
        ]);
    }

    fclose($output);
    exit;
}

// ============================================================
// 2. ACTION: GET APPLICATION DETAILS (FOR AJAX MODAL)
// ============================================================
if ($action === 'get_application') {
    header('Content-Type: application/json');
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Invalid application ID.']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT j.*, 
               p.title AS project_title, p.description AS project_desc,
               o.title AS job_title, o.job_code, o.location AS job_location, o.salary_range AS job_salary,
               u.name AS reviewer_name
        FROM join_applications j
        LEFT JOIN projects p ON j.project_id = p.id
        LEFT JOIN job_openings o ON j.job_id = o.id
        LEFT JOIN users u ON j.reviewed_by = u.id
        WHERE j.id = ?
        LIMIT 1
    ");
    $stmt->execute([$id]);
    $app = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$app) {
        echo json_encode(['success' => false, 'message' => 'Application not found.']);
        exit;
    }

    echo json_encode(['success' => true, 'data' => $app]);
    exit;
}

// ============================================================
// 3. ACTION: UPDATE STATUS & REVIEW NOTES
// ============================================================
if ($action === 'update_status') {
    if ($isAjax) header('Content-Type: application/json');

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        if ($isAjax) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
            exit;
        }
        setFlash('error', 'Invalid security token.');
        header('Location: ../join_applications.php');
        exit;
    }

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $status = cleanInput($_POST['status'] ?? 'pending');
    $paymentStatus = cleanInput($_POST['payment_status'] ?? 'exempted');
    $adminNotes = cleanInput($_POST['admin_notes'] ?? '');

    $allowedStatuses = ['pending', 'reviewed', 'approved', 'rejected', 'onboarded'];
    $allowedPaymentStatuses = ['pending', 'paid', 'exempted', 'failed', 'refunded'];

    if (!$id) {
        if ($isAjax) {
            echo json_encode(['success' => false, 'message' => 'Invalid application ID.']);
            exit;
        }
        setFlash('error', 'Invalid ID.');
        header('Location: ../join_applications.php');
        exit;
    }

    if (!in_array($status, $allowedStatuses, true)) {
        $status = 'pending';
    }
    if (!in_array($paymentStatus, $allowedPaymentStatuses, true)) {
        $paymentStatus = 'exempted';
    }

    $reviewedBy = null;
    if ($currentUserId > 0) {
        $uStmt = $pdo->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
        $uStmt->execute([$currentUserId]);
        $reviewedBy = $uStmt->fetchColumn() ?: null;
    }

    try {
        $stmt = $pdo->prepare("UPDATE join_applications SET 
            status = ?,
            payment_status = ?,
            admin_notes = ?,
            reviewed_by = ?,
            reviewed_at = NOW(),
            updated_at = NOW()
            WHERE id = ?");
        
        $stmt->execute([$status, $paymentStatus, $adminNotes, $reviewedBy, $id]);

        if ($isAjax) {
            echo json_encode([
                'success' => true,
                'message' => 'Application status updated successfully!',
                'status' => $status,
                'payment_status' => $paymentStatus
            ]);
            exit;
        }

        setFlash('success', 'Application status updated successfully.');
        header('Location: ../join_applications.php');
        exit;
    } catch (PDOException $e) {
        error_log("Status update error: " . $e->getMessage());
        if ($isAjax) {
            echo json_encode(['success' => false, 'message' => 'Database update error.']);
            exit;
        }
        setFlash('error', 'Database error while updating status.');
        header('Location: ../join_applications.php');
        exit;
    }
}

// ============================================================
// 4. ACTION: DELETE APPLICATION
// ============================================================
if ($action === 'delete') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid security token.');
        header('Location: ../join_applications.php');
        exit;
    }

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM join_applications WHERE id = ?");
            $stmt->execute([$id]);
            setFlash('success', 'Application deleted successfully.');
        } catch (PDOException $e) {
            setFlash('error', 'Failed to delete application.');
        }
    }

    header('Location: ../join_applications.php');
    exit;
}

// Default fallback
header('Location: ../join_applications.php');
exit;
