<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

if (!canAccessModule($pdo, 'admin', 'page.access_control')) {
    setFlash('error', 'Access denied.');
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token'], $_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$_POST['csrf_token'])) {
    setFlash('error', 'Invalid request.');
    header('Location: ../access_control.php');
    exit;
}

$action = (string)($_POST['action'] ?? '');
$userId = (int)($_POST['user_id'] ?? 0);
$currentUserId = (int)($_SESSION['user_id'] ?? 0);

try {
    $requiresUserId = in_array($action, ['update_user', 'save_permissions', 'reset_password', 'toggle_status', 'delete_user'], true);
    if ($requiresUserId && $userId <= 0) {
        throw new Exception('Invalid user.');
    }

    $currentUserStmt = $pdo->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
    $currentUserStmt->execute([$currentUserId]);
    $currentUserRole = (string)($currentUserStmt->fetchColumn() ?: '');

    if ($action === 'update_user') {
        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $role = trim((string)($_POST['role'] ?? 'Volunteer'));
        $status = (int)($_POST['status'] ?? 1);

        $allowedRoles = ['Super Admin', 'Admin', 'Accountant', 'Volunteer Manager', 'CSR Manager', 'Volunteer'];
        if ($name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Name and email are required.');
        }
        if (!in_array($role, $allowedRoles, true)) {
            $role = 'Volunteer';
        }
        $status = $status === 1 ? 1 : 0;
        $hierarchy = normalizeHierarchyRole($role);

        $currentRoleStmt = $pdo->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
        $currentRoleStmt->execute([$userId]);
        $currentRole = (string)($currentRoleStmt->fetchColumn() ?: '');

        if ($currentUserRole !== 'Super Admin') {
            if ($currentRole === 'Super Admin' || $role === 'Super Admin') {
                throw new Exception('Only a Super Admin can modify or assign the Super Admin role.');
            }
        }

        if ($userId === $currentUserId && $currentRole === 'Super Admin' && $role !== 'Super Admin') {
            throw new Exception('You cannot change your own Super Admin role from this screen.');
        }
        if ($currentRole === 'Super Admin' && $role !== 'Super Admin') {
            throw new Exception('Super Admin role cannot be changed here.');
        }
        if ($currentRole === 'Super Admin' && $status === 0) {
            throw new Exception('Super Admin account cannot be blocked.');
        }

        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ?, status = ?, hierarchy_level = ? WHERE id = ?");
        $stmt->execute([$name, $email, $role, $status, $hierarchy, $userId]);
        setFlash('success', 'User profile updated.');
        header('Location: ../access_control.php?user=' . $userId);
        exit;
    }

    if ($action === 'create_user') {
        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $role = trim((string)($_POST['role'] ?? 'Volunteer'));
        $status = (int)($_POST['status'] ?? 1);
        $password = (string)($_POST['password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');

        $allowedRoles = ['Super Admin', 'Admin', 'Accountant', 'Volunteer Manager', 'CSR Manager', 'Volunteer'];
        if ($name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Name and email are required.');
        }
        if (!in_array($role, $allowedRoles, true)) {
            $role = 'Volunteer';
        }
        if ($role === 'Super Admin' && $currentUserRole !== 'Super Admin') {
            throw new Exception('Only a Super Admin can create a Super Admin account.');
        }
        if ($password === '' || strlen($password) < 6) {
            throw new Exception('Password must be at least 6 characters.');
        }
        if ($password !== $confirmPassword) {
            throw new Exception('Passwords do not match.');
        }

        $existingStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $existingStmt->execute([$email]);
        if ($existingStmt->fetchColumn()) {
            throw new Exception('A user with this email already exists.');
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $hierarchy = normalizeHierarchyRole($role);
        $status = $status === 1 ? 1 : 0;

        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, status, hierarchy_level) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $hash, $role, $status, $hierarchy]);

        $newUserId = (int)$pdo->lastInsertId();
        if ($newUserId > 0 && dbColumnExists($pdo, 'users', 'coordinator_code')) {
            $coordinatorCode = 'COORD-' . str_pad((string)$newUserId, 5, '0', STR_PAD_LEFT);
            $pdo->prepare("UPDATE users SET coordinator_code = ? WHERE id = ?")->execute([$coordinatorCode, $newUserId]);
        }

        setFlash('success', 'New user created.');
        header('Location: ../access_control.php?user=' . $newUserId);
        exit;
    }

    if ($action === 'save_permissions') {
        if (!dbTableExists($pdo, 'user_permissions')) {
            throw new Exception('user_permissions table missing. Run the migration first.');
        }

        $targetRoleStmt = $pdo->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
        $targetRoleStmt->execute([$userId]);
        $targetRole = (string)($targetRoleStmt->fetchColumn() ?: '');
        if ($targetRole === 'Super Admin' && $currentUserRole !== 'Super Admin') {
            throw new Exception('Only a Super Admin can modify Super Admin permissions.');
        }

        $permissions = $_POST['permissions'] ?? [];
        if (!is_array($permissions)) {
            $permissions = [];
        }

        $allowedKeys = [];
        foreach (getAccessModuleCatalog() as $module) {
            if (!empty($module['key'])) {
                $allowedKeys[$module['key']] = true;
            }
        }

        $submitted = [];
        foreach ($permissions as $permissionKey) {
            $permissionKey = trim((string)$permissionKey);
            if ($permissionKey !== '' && isset($allowedKeys[$permissionKey])) {
                $submitted[$permissionKey] = true;
            }
        }

        $pdo->beginTransaction();
        try {
            $pdo->prepare("DELETE FROM user_permissions WHERE user_id = ?")->execute([$userId]);
            $insert = $pdo->prepare("INSERT INTO user_permissions (user_id, permission_key, is_allowed, granted_by) VALUES (?, ?, ?, ?)");
            foreach (array_keys($allowedKeys) as $permissionKey) {
                $isAllowed = isset($submitted[$permissionKey]) ? 1 : 0;
                $insert->execute([$userId, $permissionKey, $isAllowed, $currentUserId]);
            }
            $pdo->commit();
        } catch (Throwable $inner) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $inner;
        }

        setFlash('success', 'Module access updated.');
        header('Location: ../access_control.php?user=' . $userId);
        exit;
    }

    if ($action === 'reset_password') {
        $newPassword = (string)($_POST['new_password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');
        if ($newPassword === '' || $newPassword !== $confirmPassword) {
            throw new Exception('Passwords do not match.');
        }

        $targetRoleStmt = $pdo->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
        $targetRoleStmt->execute([$userId]);
        $targetRole = (string)($targetRoleStmt->fetchColumn() ?: '');
        if ($targetRole === 'Super Admin' && $currentUserRole !== 'Super Admin') {
            throw new Exception('Only a Super Admin can reset a Super Admin password.');
        }

        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $userId]);
        setFlash('success', 'Password updated.');
        header('Location: ../access_control.php?user=' . $userId);
        exit;
    }

    if ($action === 'toggle_status') {
        $status = (int)($_POST['status'] ?? 1);
        $status = $status === 1 ? 1 : 0;

        $currentRoleStmt = $pdo->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
        $currentRoleStmt->execute([$userId]);
        $currentRole = (string)($currentRoleStmt->fetchColumn() ?: '');

        if ($currentRole === 'Super Admin' && $currentUserRole !== 'Super Admin') {
            throw new Exception('Only a Super Admin can block/unblock a Super Admin.');
        }
        if ($currentRole === 'Super Admin' && $status === 0) {
            throw new Exception('Super Admin account cannot be blocked.');
        }

        if ($userId === $currentUserId && $status === 0) {
            throw new Exception('You cannot block your own account.');
        }

        $pdo->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([$status, $userId]);
        setFlash('success', $status === 1 ? 'User unblocked.' : 'User blocked.');
        header('Location: ../access_control.php?user=' . $userId);
        exit;
    }

    if ($action === 'delete_user') {
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $role = (string)($stmt->fetchColumn() ?: '');

        if ($role === 'Super Admin' && $currentUserRole !== 'Super Admin') {
            throw new Exception('Only a Super Admin can delete a Super Admin.');
        }
        if ($userId === $currentUserId) {
            throw new Exception('You cannot delete your own account.');
        }
        if ($role === 'Super Admin') {
            throw new Exception('Super Admin accounts cannot be deleted.');
        }

        if (dbTableExists($pdo, 'user_permissions')) {
            $pdo->prepare("DELETE FROM user_permissions WHERE user_id = ?")->execute([$userId]);
        }
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
        setFlash('success', 'User deleted.');
        header('Location: ../access_control.php');
        exit;
    }

    throw new Exception('Unknown action.');
} catch (Throwable $e) {
    setFlash('error', $e->getMessage() ?: 'Operation failed.');
    header('Location: ../access_control.php?user=' . $userId);
    exit;
}
