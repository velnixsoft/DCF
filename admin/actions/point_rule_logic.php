<?php
session_start();
require '../../config/db.php';
require '../../includes/functions.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !checkRole($pdo, 'manager')) {
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token'], $_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$_POST['csrf_token'])) {
    setFlash('error', 'Invalid request.');
    header('Location: ../student_point_rules.php');
    exit;
}

$action = (string)($_POST['action'] ?? '');
$id = (int)($_POST['id'] ?? 0);
$userId = (int)($_SESSION['user_id'] ?? 0) ?: null;
$allowedCategories = ['reward', 'multiplier', 'bonus', 'penalty'];
$allowedScopes = ['student', 'task_submission', 'attendance', 'donation', 'referral', 'vendor_lead', 'program', 'manual', 'system'];
$allowedModes = ['none', 'admin_review', 'evidence_required', 'system_verified'];

try {
    if ($action === 'toggle') {
        if ($id <= 0) {
            throw new Exception('Invalid point rule.');
        }

        $stmt = $pdo->prepare('UPDATE sa_point_rules SET is_active = IF(is_active = 1, 0, 1), updated_by_user_id = ?, updated_at = NOW() WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$userId, $id]);
        setFlash('success', 'Rule status updated.');
        header('Location: ../student_point_rules.php');
        exit;
    }

    if ($action === 'delete') {
        if ($id <= 0) {
            throw new Exception('Invalid point rule.');
        }

        $stmt = $pdo->prepare('UPDATE sa_point_rules SET deleted_at = NOW(), deleted_by_user_id = ? WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$userId, $id]);
        setFlash('success', 'Rule deleted.');
        header('Location: ../student_point_rules.php');
        exit;
    }

    if (!in_array($action, ['create', 'update'], true)) {
        throw new Exception('Invalid action.');
    }

    $ruleCode = strtoupper(trim((string)($_POST['rule_code'] ?? '')));
    $ruleName = trim((string)($_POST['rule_name'] ?? ''));
    $category = (string)($_POST['category'] ?? 'reward');
    $triggerKey = trim((string)($_POST['trigger_key'] ?? ''));
    $entityScope = (string)($_POST['entity_scope'] ?? 'student');
    $verificationMode = (string)($_POST['verification_mode'] ?? 'none');
    $basePoints = (int)($_POST['base_points'] ?? 0);
    $isActive = (int)($_POST['is_active'] ?? 0) === 1 ? 1 : 0;
    $notes = trim((string)($_POST['notes'] ?? ''));

    if ($ruleCode === '' || !preg_match('/^[A-Z0-9_]+$/', $ruleCode)) {
        throw new Exception('Rule code is required and must contain only uppercase letters, numbers, and underscores.');
    }
    if ($ruleName === '' || $triggerKey === '') {
        throw new Exception('Rule name and trigger key are required.');
    }
    if (!in_array($category, $allowedCategories, true)) {
        throw new Exception('Invalid category selected.');
    }
    if (!in_array($entityScope, $allowedScopes, true)) {
        throw new Exception('Invalid entity scope selected.');
    }
    if (!in_array($verificationMode, $allowedModes, true)) {
        throw new Exception('Invalid verification mode selected.');
    }

    if ($action === 'create') {
        $stmt = $pdo->prepare("
            INSERT INTO sa_point_rules (
                rule_code, rule_name, category, trigger_key, entity_scope, verification_mode,
                base_points, is_active, notes, created_by_user_id, updated_by_user_id, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $ruleCode,
            $ruleName,
            $category,
            $triggerKey,
            $entityScope,
            $verificationMode,
            $basePoints,
            $isActive,
            $notes !== '' ? $notes : null,
            $userId,
            $userId,
        ]);
        setFlash('success', 'Point rule created.');
        header('Location: ../student_point_rules.php');
        exit;
    }

    if ($id <= 0) {
        throw new Exception('Invalid point rule.');
    }

    $stmt = $pdo->prepare("
        UPDATE sa_point_rules
        SET rule_code = ?, rule_name = ?, category = ?, trigger_key = ?, entity_scope = ?,
            verification_mode = ?, base_points = ?, is_active = ?, notes = ?, updated_by_user_id = ?, updated_at = NOW()
        WHERE id = ? AND deleted_at IS NULL
    ");
    $stmt->execute([
        $ruleCode,
        $ruleName,
        $category,
        $triggerKey,
        $entityScope,
        $verificationMode,
        $basePoints,
        $isActive,
        $notes !== '' ? $notes : null,
        $userId,
        $id,
    ]);

    setFlash('success', 'Point rule updated.');
    header('Location: ../student_point_rules.php?edit=' . $id);
    exit;
} catch (Throwable $e) {
    setFlash('error', $e->getMessage() ?: 'Operation failed.');
    header('Location: ../student_point_rules.php' . ($id > 0 ? '?edit=' . $id : ''));
    exit;
}
