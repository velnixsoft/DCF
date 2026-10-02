<?php
// ============================================================
// admin/actions/expense_logic.php
// Handles CRUD operations and bill/document uploads for Expenses
// Follows existing file upload and security patterns (CSRF, RBAC)
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/functions.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    setFlash('error', 'Session expired. Please log in again.');
    header('Location: ../index.php');
    exit;
}

if (!canAccessModule($pdo, 'coordinator', 'page.expenses')) {
    http_response_code(403);
    setFlash('error', 'Access denied.');
    header('Location: ../dashboard.php');
    exit;
}

$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$isManagerOrAdmin = checkRole($pdo, 'manager');

// ── CSRF Verification ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($submittedToken)) {
        setFlash('error', 'Invalid security token (CSRF). Please try again.');
        header('Location: ../expenses.php');
        exit;
    }
}

$action = cleanInput($_POST['action'] ?? $_GET['action'] ?? '');

// ── 1. ADD EXPENSE ────────────────────────────────────────────
if ($action === 'add_expense') {
    $categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $projectId = filter_input(INPUT_POST, 'project_id', FILTER_VALIDATE_INT) ?: null;
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $date = cleanInput($_POST['date'] ?? date('Y-m-d'));
    $purpose = cleanInput($_POST['purpose'] ?? '');
    $paymentMode = cleanInput($_POST['payment_mode'] ?? 'Cash');
    $referenceNo = cleanInput($_POST['reference_no'] ?? '') ?: null;
    $vendorPayeeName = cleanInput($_POST['vendor_payee_name'] ?? '') ?: null;
    $remarks = cleanInput($_POST['remarks'] ?? '') ?: null;

    // Validation
    if (!$categoryId) {
        setFlash('error', 'Please select an expense category.');
        header('Location: ../expenses.php');
        exit;
    }

    if (!$amount || $amount <= 0) {
        setFlash('error', 'Please enter a valid expense amount greater than 0.');
        header('Location: ../expenses.php');
        exit;
    }

    if (empty($purpose)) {
        setFlash('error', 'Please enter the purpose or description for this expense.');
        header('Location: ../expenses.php');
        exit;
    }

    // Handle Bill / Document Upload (Reusing existing certificate / gallery upload pattern)
    $billDocumentPath = null;
    if (!empty($_FILES['bill_document']['name']) && $_FILES['bill_document']['error'] === UPLOAD_ERR_OK) {
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg', 'application/pdf'];
        $fileSize = $_FILES['bill_document']['size'];
        $tmpName = $_FILES['bill_document']['tmp_name'];
        $fileMime = mime_content_type($tmpName);
        $fileExt = strtolower(pathinfo($_FILES['bill_document']['name'], PATHINFO_EXTENSION));

        if ($fileSize > 5 * 1024 * 1024) {
            setFlash('error', 'Bill/document file size must be under 5MB.');
            header('Location: ../expenses.php');
            exit;
        }

        if (!in_array($fileMime, $allowedMimes, true)) {
            setFlash('error', 'Only JPG, PNG, WEBP images and PDF documents are allowed.');
            header('Location: ../expenses.php');
            exit;
        }

        $targetDir = __DIR__ . '/../../uploads/expenses/';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $safeBase = preg_replace('/[^a-zA-Z0-9_-]/', '', pathinfo($_FILES['bill_document']['name'], PATHINFO_FILENAME));
        $fileName = 'bill_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $fileExt;
        
        if (move_uploaded_file($tmpName, $targetDir . $fileName)) {
            $billDocumentPath = 'uploads/expenses/' . $fileName;
        } else {
            setFlash('error', 'Failed to upload bill document.');
            header('Location: ../expenses.php');
            exit;
        }
    }

    // Default approved status: Admins/Managers auto-approve or can set status, Staff/Coordinators default to Pending
    $approvedStatus = 'Pending';
    $approvedBy = null;
    $approvedAt = null;

    if ($isManagerOrAdmin) {
        $customStatus = cleanInput($_POST['approved_status'] ?? 'Approved');
        if (in_array($customStatus, ['Pending', 'Approved', 'Rejected', 'Paid'], true)) {
            $approvedStatus = $customStatus;
            if ($approvedStatus === 'Approved' || $approvedStatus === 'Paid') {
                $approvedBy = $currentUserId;
                $approvedAt = date('Y-m-d H:i:s');
            }
        }
    }

    try {
        $pdo->beginTransaction();

        $sql = "INSERT INTO expenses (
            expense_code, category_id, project_id, amount, date, purpose, 
            payment_mode, reference_no, vendor_payee_name, bill_document_path, 
            added_by, approved_status, approved_by, approved_at, remarks
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            null, // expense_code generated next
            $categoryId,
            $projectId,
            $amount,
            $date,
            $purpose,
            $paymentMode,
            $referenceNo,
            $vendorPayeeName,
            $billDocumentPath,
            $currentUserId,
            $approvedStatus,
            $approvedBy,
            $approvedAt,
            $remarks
        ]);

        $newId = (int)$pdo->lastInsertId();
        $expenseCode = 'EXP-' . date('Y', strtotime($date)) . '-' . str_pad((string)$newId, 4, '0', STR_PAD_LEFT);

        $pdo->prepare("UPDATE expenses SET expense_code = ? WHERE id = ?")->execute([$expenseCode, $newId]);

        $pdo->commit();

        setFlash('success', "Expense recorded successfully with voucher code {$expenseCode}!");
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($billDocumentPath && file_exists(__DIR__ . '/../../' . $billDocumentPath)) {
            @unlink(__DIR__ . '/../../' . $billDocumentPath);
        }
        error_log("add_expense error: " . $e->getMessage());
        setFlash('error', 'Database error: ' . $e->getMessage());
    }

    header('Location: ../expenses.php');
    exit;
}

// ── 2. UPDATE EXPENSE ─────────────────────────────────────────
if ($action === 'update_expense') {
    $expenseId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if (!$expenseId) {
        setFlash('error', 'Invalid expense ID.');
        header('Location: ../expenses.php');
        exit;
    }

    // Check ownership or admin status
    $stmt = $pdo->prepare("SELECT * FROM expenses WHERE id = ?");
    $stmt->execute([$expenseId]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        setFlash('error', 'Expense record not found.');
        header('Location: ../expenses.php');
        exit;
    }

    if (!$isManagerOrAdmin && (int)$existing['added_by'] !== $currentUserId) {
        setFlash('error', 'Access denied. You can only modify expenses added by yourself.');
        header('Location: ../expenses.php');
        exit;
    }

    $categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT) ?: (int)$existing['category_id'];
    $projectId = filter_input(INPUT_POST, 'project_id', FILTER_VALIDATE_INT) ?: null;
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT) ?: (float)$existing['amount'];
    $date = cleanInput($_POST['date'] ?? $existing['date']);
    $purpose = cleanInput($_POST['purpose'] ?? $existing['purpose']);
    $paymentMode = cleanInput($_POST['payment_mode'] ?? $existing['payment_mode']);
    $referenceNo = cleanInput($_POST['reference_no'] ?? '') ?: null;
    $vendorPayeeName = cleanInput($_POST['vendor_payee_name'] ?? '') ?: null;
    $remarks = cleanInput($_POST['remarks'] ?? '') ?: null;

    $billDocumentPath = $existing['bill_document_path'];

    // Handle replacement bill document
    if (!empty($_FILES['bill_document']['name']) && $_FILES['bill_document']['error'] === UPLOAD_ERR_OK) {
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg', 'application/pdf'];
        $tmpName = $_FILES['bill_document']['tmp_name'];
        $fileMime = mime_content_type($tmpName);
        $fileExt = strtolower(pathinfo($_FILES['bill_document']['name'], PATHINFO_EXTENSION));

        if (in_array($fileMime, $allowedMimes, true) && $_FILES['bill_document']['size'] <= 5 * 1024 * 1024) {
            $targetDir = __DIR__ . '/../../uploads/expenses/';
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

            $fileName = 'bill_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $fileExt;
            if (move_uploaded_file($tmpName, $targetDir . $fileName)) {
                if ($billDocumentPath && file_exists(__DIR__ . '/../../' . $billDocumentPath)) {
                    @unlink(__DIR__ . '/../../' . $billDocumentPath);
                }
                $billDocumentPath = 'uploads/expenses/' . $fileName;
            }
        }
    }

    try {
        $upSql = "UPDATE expenses SET 
            category_id = ?, project_id = ?, amount = ?, date = ?, 
            purpose = ?, payment_mode = ?, reference_no = ?, vendor_payee_name = ?, 
            bill_document_path = ?, remarks = ?
            WHERE id = ?";
        
        $upStmt = $pdo->prepare($upSql);
        $upStmt->execute([
            $categoryId, $projectId, $amount, $date,
            $purpose, $paymentMode, $referenceNo, $vendorPayeeName,
            $billDocumentPath, $remarks, $expenseId
        ]);

        setFlash('success', 'Expense record updated successfully.');
    } catch (Throwable $e) {
        setFlash('error', 'Update failed: ' . $e->getMessage());
    }

    header('Location: ../expenses.php');
    exit;
}

// ── 3. UPDATE APPROVAL STATUS (Manager / Admin Only) ──────────
if ($action === 'update_status') {
    if (!$isManagerOrAdmin) {
        setFlash('error', 'Only Managers and Admins can approve or reject expenses.');
        header('Location: ../expenses.php');
        exit;
    }

    $expenseId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $newStatus = cleanInput($_POST['status'] ?? '');
    $rejectionReason = cleanInput($_POST['rejection_reason'] ?? '') ?: null;

    if (!$expenseId || !in_array($newStatus, ['Pending', 'Approved', 'Rejected', 'Paid'], true)) {
        setFlash('error', 'Invalid status update parameters.');
        header('Location: ../expenses.php');
        exit;
    }

    $approvedBy = ($newStatus === 'Approved' || $newStatus === 'Paid') ? $currentUserId : null;
    $approvedAt = ($newStatus === 'Approved' || $newStatus === 'Paid') ? date('Y-m-d H:i:s') : null;

    try {
        $stmt = $pdo->prepare("UPDATE expenses SET approved_status = ?, approved_by = ?, approved_at = ?, rejection_reason = ? WHERE id = ?");
        $stmt->execute([$newStatus, $approvedBy, $approvedAt, $rejectionReason, $expenseId]);
        setFlash('success', "Expense status updated to {$newStatus}.");
    } catch (Throwable $e) {
        setFlash('error', 'Status update failed: ' . $e->getMessage());
    }

    header('Location: ../expenses.php');
    exit;
}

// ── 4. DELETE EXPENSE ─────────────────────────────────────────
if ($action === 'delete_expense') {
    $expenseId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if (!$expenseId) {
        setFlash('error', 'Invalid expense ID.');
        header('Location: ../expenses.php');
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM expenses WHERE id = ?");
    $stmt->execute([$expenseId]);
    $exp = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$exp) {
        setFlash('error', 'Expense not found.');
        header('Location: ../expenses.php');
        exit;
    }

    if (!$isManagerOrAdmin && (int)$exp['added_by'] !== $currentUserId) {
        setFlash('error', 'Access denied. You can only delete your own expenses.');
        header('Location: ../expenses.php');
        exit;
    }

    try {
        if (!empty($exp['bill_document_path']) && file_exists(__DIR__ . '/../../' . $exp['bill_document_path'])) {
            @unlink(__DIR__ . '/../../' . $exp['bill_document_path']);
        }
        $pdo->prepare("DELETE FROM expenses WHERE id = ?")->execute([$expenseId]);
        setFlash('success', 'Expense record deleted.');
    } catch (Throwable $e) {
        setFlash('error', 'Failed to delete expense: ' . $e->getMessage());
    }

    header('Location: ../expenses.php');
    exit;
}

// Default fallback
header('Location: ../expenses.php');
exit;
