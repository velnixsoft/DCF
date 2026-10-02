<?php
// ============================================================
// admin/actions/org_structure_logic.php
// Controller for Organization Hierarchy & Designation Structure
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

// ============================================================
// 1. AJAX: Fetch Single Node JSON
// ============================================================
if ($action === 'get_node_json') {
    header('Content-Type: application/json; charset=utf-8');
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Invalid Node ID']);
        exit;
    }
    $stmt = $pdo->prepare("
        SELECT o.*, 
               p.title AS parent_title,
               m.name AS mgmt_name, m.photo AS mgmt_photo,
               d.title AS desig_name
        FROM org_structure o
        LEFT JOIN org_structure p ON o.parent_id = p.id
        LEFT JOIN management_body m ON o.management_body_id = m.id
        LEFT JOIN member_designations d ON o.designation_id = d.id
        WHERE o.id = ? LIMIT 1
    ");
    $stmt->execute([$id]);
    $node = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($node) {
        echo json_encode(['success' => true, 'data' => $node]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Node not found']);
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
            echo json_encode(['success' => false, 'message' => 'Security token invalid.']);
            exit;
        }
        setFlash('error', 'Security validation failed (CSRF mismatch).');
        header('Location: ../org_structure.php');
        exit;
    }
}

// ============================================================
// Helper: Check for circular hierarchy reference
// ============================================================
function isDescendantOf(PDO $pdo, int $potentialAncestorId, int $nodeId): bool {
    if ($potentialAncestorId === $nodeId) return true;
    $currParentId = $potentialAncestorId;
    $visited = [];

    while ($currParentId !== null && !in_array($currParentId, $visited, true)) {
        $visited[] = $currParentId;
        $stmt = $pdo->prepare("SELECT parent_id FROM org_structure WHERE id = ? LIMIT 1");
        $stmt->execute([$currParentId]);
        $currParentId = $stmt->fetchColumn();
        if ($currParentId === false || $currParentId === null) break;
        $currParentId = (int)$currParentId;
        if ($currParentId === $nodeId) return true;
    }
    return false;
}

// ============================================================
// 2. POST: Save / Update Hierarchy Node
// ============================================================
if ($action === 'save_node') {
    try {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $title = cleanInput($_POST['title'] ?? '');
        $parentId = filter_input(INPUT_POST, 'parent_id', FILTER_VALIDATE_INT) ?: null;
        $designationId = filter_input(INPUT_POST, 'designation_id', FILTER_VALIDATE_INT) ?: null;
        $managementBodyId = filter_input(INPUT_POST, 'management_body_id', FILTER_VALIDATE_INT) ?: null;
        $department = cleanInput($_POST['department'] ?? 'Executive Board');
        $holderName = cleanInput($_POST['holder_name'] ?? '');
        $holderDesignation = cleanInput($_POST['holder_designation'] ?? '');
        $holderPhone = cleanInput($_POST['holder_phone'] ?? '');
        $holderEmail = cleanInput($_POST['holder_email'] ?? '');
        $levelTier = filter_input(INPUT_POST, 'level_tier', FILTER_VALIDATE_INT) ?: 1;
        $sortOrder = filter_input(INPUT_POST, 'sort_order', FILTER_VALIDATE_INT) ?: 0;
        $badgeColor = cleanInput($_POST['badge_color'] ?? 'teal');
        $responsibilities = cleanInput($_POST['responsibilities'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if (empty($title)) {
            throw new Exception('Designation / Role Title is required.');
        }

        // Circular reference check when editing
        if ($id && $parentId) {
            if ($id === $parentId) {
                throw new Exception('A designation node cannot be its own parent.');
            }
            if (isDescendantOf($pdo, $parentId, $id)) {
                throw new Exception('Invalid hierarchy selection: Circular reference detected (cannot select a subordinate as parent).');
            }
        }

        // If linked to a management_body member and holder name not manually overridden, auto-populate
        if ($managementBodyId && empty($holderName)) {
            $mStmt = $pdo->prepare("SELECT name, designation, phone, email, photo FROM management_body WHERE id = ? LIMIT 1");
            $mStmt->execute([$managementBodyId]);
            $mgmt = $mStmt->fetch(PDO::FETCH_ASSOC);
            if ($mgmt) {
                $holderName = $mgmt['name'];
                if (empty($holderDesignation)) $holderDesignation = $mgmt['designation'];
                if (empty($holderPhone)) $holderPhone = $mgmt['phone'];
                if (empty($holderEmail)) $holderEmail = $mgmt['email'];
                if (empty($_FILES['photo']['name']) && !empty($mgmt['photo'])) {
                    $existingPhoto = $mgmt['photo'];
                }
            }
        }

        // Handle Photo Upload
        $photoPath = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../uploads/management';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $val = validateUploadedFile(
                $_FILES['photo'],
                ['image/jpeg', 'image/png', 'image/webp'],
                3 * 1024 * 1024,
                ['jpg', 'jpeg', 'png', 'webp']
            );

            if ($val['success']) {
                $stored = storeValidatedUpload(
                    $_FILES['photo'],
                    $uploadDir,
                    'uploads/management',
                    'org_node_' . preg_replace('/[^a-z0-9]/', '', strtolower($title))
                );
                if ($stored['success']) {
                    $photoPath = $stored['relative_path'];
                }
            }
        }

        if ($id && $id > 0) {
            // Update
            if ($photoPath) {
                $stmt = $pdo->prepare("
                    UPDATE org_structure 
                    SET parent_id = ?, designation_id = ?, title = ?, department = ?, 
                        holder_name = ?, holder_designation = ?, holder_photo = ?, 
                        holder_phone = ?, holder_email = ?, management_body_id = ?, 
                        level_tier = ?, sort_order = ?, badge_color = ?, responsibilities = ?, is_active = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $parentId, $designationId, $title, $department,
                    $holderName ?: null, $holderDesignation ?: null, $photoPath,
                    $holderPhone ?: null, $holderEmail ?: null, $managementBodyId,
                    $levelTier, $sortOrder, $badgeColor, $responsibilities ?: null, $isActive,
                    $id
                ]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE org_structure 
                    SET parent_id = ?, designation_id = ?, title = ?, department = ?, 
                        holder_name = ?, holder_designation = ?, 
                        holder_phone = ?, holder_email = ?, management_body_id = ?, 
                        level_tier = ?, sort_order = ?, badge_color = ?, responsibilities = ?, is_active = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $parentId, $designationId, $title, $department,
                    $holderName ?: null, $holderDesignation ?: null,
                    $holderPhone ?: null, $holderEmail ?: null, $managementBodyId,
                    $levelTier, $sortOrder, $badgeColor, $responsibilities ?: null, $isActive,
                    $id
                ]);
            }
            setFlash('success', 'Hierarchy node "' . htmlspecialchars($title) . '" updated successfully.');
        } else {
            // Insert
            $stmt = $pdo->prepare("
                INSERT INTO org_structure (
                    parent_id, designation_id, title, department, 
                    holder_name, holder_designation, holder_photo, 
                    holder_phone, holder_email, management_body_id, 
                    level_tier, sort_order, badge_color, responsibilities, is_active, created_at
                ) VALUES (
                    ?, ?, ?, ?, 
                    ?, ?, ?, 
                    ?, ?, ?, 
                    ?, ?, ?, ?, ?, NOW()
                )
            ");
            $stmt->execute([
                $parentId, $designationId, $title, $department,
                $holderName ?: null, $holderDesignation ?: null, $photoPath,
                $holderPhone ?: null, $holderEmail ?: null, $managementBodyId,
                $levelTier, $sortOrder, $badgeColor, $responsibilities ?: null, $isActive
            ]);
            setFlash('success', 'New designation node "' . htmlspecialchars($title) . '" added to organization chart.');
        }

    } catch (Throwable $e) {
        setFlash('error', 'Failed to save node: ' . $e->getMessage());
    }
    header('Location: ../org_structure.php');
    exit;
}

// ============================================================
// 3. POST: Toggle Status
// ============================================================
if ($action === 'toggle_status') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $pdo->prepare("UPDATE org_structure SET is_active = IF(is_active = 1, 0, 1) WHERE id = ?");
        $stmt->execute([$id]);
        setFlash('success', 'Designation visibility status updated.');
    }
    header('Location: ../org_structure.php');
    exit;
}

// ============================================================
// 4. POST: Delete Node
// ============================================================
if ($action === 'delete_node') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($id) {
        // Fetch deleted node's parent_id to re-parent direct children
        $pStmt = $pdo->prepare("SELECT parent_id FROM org_structure WHERE id = ? LIMIT 1");
        $pStmt->execute([$id]);
        $parentOfDeleted = $pStmt->fetchColumn() ?: null;

        // Re-parent children
        $uStmt = $pdo->prepare("UPDATE org_structure SET parent_id = ? WHERE parent_id = ?");
        $uStmt->execute([$parentOfDeleted, $id]);

        // Delete the node
        $dStmt = $pdo->prepare("DELETE FROM org_structure WHERE id = ?");
        $dStmt->execute([$id]);

        setFlash('success', 'Designation node deleted. Subordinate nodes re-parented gracefully.');
    }
    header('Location: ../org_structure.php');
    exit;
}

header('Location: ../org_structure.php');
exit;
