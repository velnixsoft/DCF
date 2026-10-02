<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require '../config/db.php';
require '../includes/functions.php';

$userId = (int)($_SESSION['user_id'] ?? 0);
$hierarchy = normalizeHierarchyRole($_SESSION['hierarchy_level'] ?? '');
$limit = (int)($_GET['limit'] ?? 10);
if ($limit <= 0 || $limit > 50) {
    $limit = 10;
}

if (!in_array($hierarchy, ['field_agent', 'area_manager', 'state_admin', 'super_admin'], true)) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$agentStmt = $pdo->prepare('SELECT id FROM field_agents WHERE user_id = ? LIMIT 1');
$agentStmt->execute([$userId]);
$agentId = (int)($agentStmt->fetchColumn() ?: 0);

if ($hierarchy === 'field_agent' && $agentId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Agent profile not found']);
    exit;
}

$activities = [];

try {
    if ($agentId > 0) {
        $salaryUnion = '';
        if (dbTableExists($pdo, 'agent_salary_ledger')) {
            $salaryUnion = "
                UNION ALL
                SELECT asl.credited_at AS activity_time, 'salary' AS activity_type,
                       CONCAT('Salary credited: INR ', FORMAT(asl.total_payable, 2), ' (', asl.month_key, ')') AS action
                FROM agent_salary_ledger asl
                WHERE asl.agent_id = ? AND asl.status = 'credited' AND asl.credited_at IS NOT NULL
            ";
        }

        $stmt = $pdo->prepare("
            SELECT activity_time, activity_type, action
            FROM (
                SELECT aa.punch_time AS activity_time, 'attendance' AS activity_type,
                       CONCAT('Punch ', aa.punch_type, ' marked') AS action
                FROM agent_attendance aa
                WHERE aa.agent_id = ?
                UNION ALL
                SELECT d.created_at AS activity_time, 'donation' AS activity_type,
                       CONCAT('Donation recorded: INR ', FORMAT(d.amount, 2), ' (', UPPER(COALESCE(d.payment_mode_field, 'cash')), ')') AS action
                FROM donations d
                WHERE d.field_agent_id = ?
                UNION ALL
                SELECT CONCAT(cd.deposit_date, ' 12:00:00') AS activity_time, 'deposit' AS activity_type,
                       CONCAT('Cash deposited: INR ', FORMAT(cd.deposit_amount, 2)) AS action
                FROM cash_deposits cd
                WHERE cd.agent_id = ? AND cd.status = 'deposited' AND cd.deposit_date IS NOT NULL
                $salaryUnion
            ) t
            ORDER BY activity_time DESC
            LIMIT $limit
        ");
        $params = [$agentId, $agentId, $agentId];
        if ($salaryUnion !== '') {
            $params[] = $agentId;
        }
        $stmt->execute($params);
        $activities = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    foreach ($activities as &$row) {
        $ts = strtotime((string)$row['activity_time']);
        $row['time'] = $ts ? date('d M Y h:i A', $ts) : (string)$row['activity_time'];
        $row['initial'] = strtoupper(substr((string)$row['activity_type'], 0, 1));
    }
    unset($row);

    echo json_encode([
        'success' => true,
        'activities' => $activities,
    ]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Unable to load activity']);
}

