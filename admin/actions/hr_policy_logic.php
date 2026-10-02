<?php
// ============================================================
// admin/actions/hr_policy_logic.php
// Controller for HR Policies & Workplace Compliance Uploads
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/upload_validator.php';

// Access control
if (!checkRole($pdo, 'manager')) {
    if (isset($_GET['ajax']) || isset($_POST['ajax'])) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Unauthorized access. Manager/Admin role required.']);
        exit;
    }
    setFlash('error', 'Unauthorized access. Manager/Admin role required.');
    header('Location: ../dashboard.php');
    exit;
}

$action = cleanInput($_REQUEST['action'] ?? '');
$adminUserId = $_SESSION['user_id'] ?? null;

// ============================================================
// 1. AJAX: Fetch Single Policy JSON
// ============================================================
if ($action === 'get_policy_json') {
    header('Content-Type: application/json; charset=utf-8');
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Invalid Policy ID']);
        exit;
    }
    $stmt = $pdo->prepare("SELECT * FROM hr_policies WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $policy = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($policy) {
        echo json_encode(['success' => true, 'data' => $policy]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Policy not found']);
    }
    exit;
}

// ============================================================
// CSRF Validation for POST requests
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$csrfToken)) {
        if (isset($_POST['ajax'])) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Security token invalid (CSRF mismatch).']);
            exit;
        }
        setFlash('error', 'Security validation failed (CSRF mismatch).');
        header('Location: ../hr_policies.php');
        exit;
    }
}

// ============================================================
// 2. POST: Create or Update HR Policy
// ============================================================
if ($action === 'save') {
    try {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $title = cleanInput($_POST['title'] ?? '');
        $policyCode = cleanInput($_POST['policy_code'] ?? '');
        $category = cleanInput($_POST['category'] ?? 'code_of_conduct');
        $categoryCustom = cleanInput($_POST['category_custom'] ?? '');
        $description = cleanInput($_POST['description'] ?? '');
        $policyVersion = cleanInput($_POST['policy_version'] ?? 'v1.0');
        $effectiveDate = cleanInput($_POST['effective_date'] ?? '');
        $reviewDate = cleanInput($_POST['review_date'] ?? '');
        $sortOrder = filter_input(INPUT_POST, 'sort_order', FILTER_VALIDATE_INT) ?: 0;
        $isPublic = isset($_POST['is_public']) ? 1 : 0;

        if (empty($title)) {
            throw new Exception('Policy Title is required.');
        }

        // Generate policy code if blank
        if (empty($policyCode)) {
            $prefixMap = [
                'code_of_conduct' => 'COC',
                'posh_gender' => 'POSH',
                'child_safeguarding' => 'CSG',
                'leave_benefits' => 'LEV',
                'whistleblower' => 'WB',
                'travel_compensation' => 'TRV',
                'volunteer_ethics' => 'VOL',
                'general' => 'GEN'
            ];
            $pfx = $prefixMap[$category] ?? 'HRP';
            $policyCode = 'HRP-' . $pfx . '-' . date('y') . rand(10, 99);
        }

        $allowedCategories = ['code_of_conduct', 'posh_gender', 'child_safeguarding', 'leave_benefits', 'whistleblower', 'travel_compensation', 'volunteer_ethics', 'general'];
        if (!in_array($category, $allowedCategories, true)) {
            $category = 'general';
        }

        // Handle Policy File Upload (PDF / DOC / DOCX)
        $filePath = null;
        $fileSizeStr = null;

        if (isset($_FILES['policy_file']) && $_FILES['policy_file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../uploads/documents';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $val = validateUploadedFile(
                $_FILES['policy_file'],
                [
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'image/jpeg',
                    'image/png'
                ],
                10 * 1024 * 1024,
                ['pdf', 'doc', 'docx', 'jpg', 'png']
            );

            if (!$val['success']) {
                throw new Exception('File upload error: ' . ($val['message'] ?? 'Invalid file format. Only PDF, DOC, DOCX up to 10MB allowed.'));
            }

            $bytes = (int)$_FILES['policy_file']['size'];
            if ($bytes >= 1024 * 1024) {
                $fileSizeStr = round($bytes / (1024 * 1024), 2) . ' MB';
            } else {
                $fileSizeStr = round($bytes / 1024, 0) . ' KB';
            }

            $stored = storeValidatedUpload(
                $_FILES['policy_file'],
                $uploadDir,
                'uploads/documents',
                'hr_policy_' . preg_replace('/[^a-z0-9]/', '', strtolower($policyCode))
            );

            if (!$stored['success']) {
                throw new Exception('Failed to save uploaded policy file.');
            }

            $filePath = $stored['relative_path'];
        }

        if ($id && $id > 0) {
            // Update
            if ($filePath) {
                // Delete old file if exists
                $oldStmt = $pdo->prepare("SELECT file_path FROM hr_policies WHERE id = ? LIMIT 1");
                $oldStmt->execute([$id]);
                $oldFile = $oldStmt->fetchColumn();
                if ($oldFile && file_exists(__DIR__ . '/../../' . $oldFile)) {
                    @unlink(__DIR__ . '/../../' . $oldFile);
                }

                $stmt = $pdo->prepare("
                    UPDATE hr_policies 
                    SET policy_code = ?, title = ?, category = ?, category_custom = ?, description = ?,
                        policy_version = ?, effective_date = ?, review_date = ?, is_public = ?, sort_order = ?,
                        file_path = ?, file_size = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $policyCode, $title, $category, $categoryCustom ?: null, $description ?: null,
                    $policyVersion, !empty($effectiveDate) ? $effectiveDate : null, !empty($reviewDate) ? $reviewDate : null, $isPublic, $sortOrder,
                    $filePath, $fileSizeStr,
                    $id
                ]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE hr_policies 
                    SET policy_code = ?, title = ?, category = ?, category_custom = ?, description = ?,
                        policy_version = ?, effective_date = ?, review_date = ?, is_public = ?, sort_order = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $policyCode, $title, $category, $categoryCustom ?: null, $description ?: null,
                    $policyVersion, !empty($effectiveDate) ? $effectiveDate : null, !empty($reviewDate) ? $reviewDate : null, $isPublic, $sortOrder,
                    $id
                ]);
            }
            setFlash('success', 'Policy "' . htmlspecialchars($title) . '" updated successfully.');
        } else {
            // Insert
            $stmt = $pdo->prepare("
                INSERT INTO hr_policies (
                    policy_code, title, category, category_custom, description,
                    policy_version, effective_date, review_date, is_public, sort_order,
                    file_path, file_size, created_by, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, NOW()
                )
            ");
            $stmt->execute([
                $policyCode, $title, $category, $categoryCustom ?: null, $description ?: null,
                $policyVersion, !empty($effectiveDate) ? $effectiveDate : null, !empty($reviewDate) ? $reviewDate : null, $isPublic, $sortOrder,
                $filePath, $fileSizeStr, $adminUserId
            ]);
            setFlash('success', 'New HR Policy "' . htmlspecialchars($title) . '" published successfully.');
        }

    } catch (Throwable $e) {
        setFlash('error', 'Error saving policy: ' . $e->getMessage());
    }
    header('Location: ../hr_policies.php');
    exit;
}

// ============================================================
// 3. POST: Toggle Public Visibility
// ============================================================
if ($action === 'toggle_public') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $pdo->prepare("UPDATE hr_policies SET is_public = IF(is_public = 1, 0, 1) WHERE id = ?");
        $stmt->execute([$id]);
        setFlash('success', 'Policy visibility toggled.');
    }
    header('Location: ../hr_policies.php');
    exit;
}

// ============================================================
// 4. POST: Delete Policy
// ============================================================
if ($action === 'delete') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $pdo->prepare("SELECT file_path FROM hr_policies WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $filePath = $stmt->fetchColumn();
        if ($filePath && file_exists(__DIR__ . '/../../' . $filePath)) {
            @unlink(__DIR__ . '/../../' . $filePath);
        }

        $dStmt = $pdo->prepare("DELETE FROM hr_policies WHERE id = ?");
        $dStmt->execute([$id]);
        setFlash('success', 'HR Policy deleted successfully.');
    }
    header('Location: ../hr_policies.php');
    exit;
}

header('Location: ../hr_policies.php');
exit;
