<?php
/**
 * Cash Compliance Cron - Run daily: php admin/field-agent-cron.php
 */
require '../config/db.php';

// Mark overdue as 'overdue'
$pdo->exec("
    UPDATE cash_deposits 
    SET status = 'overdue' 
    WHERE status = 'pending' AND deposit_deadline < CURDATE()
");

$overdueCount = $pdo->query("SELECT COUNT(*) FROM cash_deposits WHERE status = 'overdue'")->fetchColumn();
echo "Compliance Check Complete: $overdueCount overdue deposits marked.\n";

// Block agents with >3 overdue (restrict punch)
$blockedAgents = $pdo->prepare("
    UPDATE field_agents fa
    SET attendance_status = 'inactive'
    WHERE fa.id IN (
        SELECT agent_id 
        FROM cash_deposits 
        WHERE status = 'overdue'
        GROUP BY agent_id 
        HAVING COUNT(*) >= 3
    ) AND attendance_status = 'active'
");
$blockedAgents->execute();
$blocked = $pdo->query("SELECT COUNT(*) FROM field_agents WHERE attendance_status = 'inactive'")->fetchColumn();
echo "Blocked $blocked agents with 3+ overdue deposits.\n";

echo "Cron executed successfully at " . date('Y-m-d H:i:s') . "\n";
?>

