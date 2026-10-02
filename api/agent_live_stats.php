<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require '../config/db.php';
require '../includes/functions.php';

ensureFieldAgentSchema($pdo);

$userId = (int)($_SESSION['user_id'] ?? 0);
$role = normalizeHierarchyRole($_SESSION['hierarchy_level'] ?? '');
if (!in_array($role, ['field_agent', 'area_manager', 'state_admin', 'super_admin'], true)) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$agentStmt = $pdo->prepare('SELECT id, target_amount_monthly, base_salary_monthly, incentive_rate_percent, min_attendance_days FROM field_agents WHERE user_id = ? LIMIT 1');
$agentStmt->execute([$userId]);
$agent = $agentStmt->fetch(PDO::FETCH_ASSOC);
if (!$agent) {
    echo json_encode(['success' => false, 'message' => 'Agent profile not found']);
    exit;
}
$agentId = (int)$agent['id'];

$monthKey = date('Y-m');
$statsStmt = $pdo->prepare("
    SELECT
      COALESCE((SELECT SUM(amount) FROM donations WHERE field_agent_id = ? AND DATE_FORMAT(created_at, '%Y-%m') = ?), 0) AS monthly_collected,
      COALESCE((SELECT COUNT(DISTINCT DATE(punch_time)) FROM agent_attendance WHERE agent_id = ? AND DATE_FORMAT(punch_time, '%Y-%m') = ?), 0) AS attendance_days_month,
      COALESCE((SELECT COUNT(*) FROM cash_deposits WHERE agent_id = ? AND status = 'pending'), 0) AS pending_deposits,
      COALESCE((SELECT COUNT(*) FROM cash_deposits WHERE agent_id = ? AND status = 'overdue'), 0) AS overdue_deposits
");
$statsStmt->execute([$agentId, $monthKey, $agentId, $monthKey, $agentId, $agentId]);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC) ?: [];

$metrics = getAgentPayrollMetrics(array_merge($agent, $stats));

echo json_encode([
    'success' => true,
    'monthly_collected' => (float)($stats['monthly_collected'] ?? 0),
    'attendance_days_month' => (int)($stats['attendance_days_month'] ?? 0),
    'pending_deposits' => (int)($stats['pending_deposits'] ?? 0),
    'overdue_deposits' => (int)($stats['overdue_deposits'] ?? 0),
    'eligible' => (bool)$metrics['eligible'],
]);

