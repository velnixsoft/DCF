<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require '../config/db.php';
require '../includes/functions.php';

$userHierarchy = normalizeHierarchyRole($_SESSION['hierarchy_level'] ?? '');
if (!in_array($userHierarchy, ['field_agent', 'area_manager'])) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST only']);
    exit;
}

$depositId = (int)($_POST['deposit_id'] ?? 0);
$depositAmount = (float)($_POST['deposit_amount'] ?? 0);
$notes = cleanInput($_POST['notes'] ?? '');

if ($depositId <= 0 || $depositAmount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid deposit details']);
    exit;
}

// Get agent/manager context
$userId = (int)$_SESSION['user_id'];
$params = [$depositAmount, $notes, $depositId];
$sql = '
    UPDATE cash_deposits
    SET deposit_amount = ?, deposit_date = CURDATE(), status = "deposited", notes = ?
    WHERE id = ? AND status IN ("pending", "overdue")
';

if ($userHierarchy === 'field_agent') {
    $agentStmt = $pdo->prepare('SELECT id FROM field_agents WHERE user_id = ?');
    $agentStmt->execute([$userId]);
    $agentId = (int)($agentStmt->fetchColumn() ?: 0);
    if ($agentId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Agent not found']);
        exit;
    }
    $sql .= ' AND agent_id = ?';
    $params[] = $agentId;
}

$stmt = $pdo->prepare($sql);
if ($stmt->execute($params)) {
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Cash deposit recorded successfully!'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Deposit not found or already processed']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

