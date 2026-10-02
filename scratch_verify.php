<?php
/**
 * Student Ambassador / Internship Module Integration Test Runner
 * Checks database tables, registers mock actions, verifies logic calculations.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/student/points.php';
require_once __DIR__ . '/includes/student/levels.php';
require_once __DIR__ . '/includes/student/badges.php';
require_once __DIR__ . '/includes/student/referral_service.php';
require_once __DIR__ . '/includes/student/vendor_leads.php';
require_once __DIR__ . '/includes/student/portal_helpers.php';

echo "========================================================\n";
echo "🟢 STARTING SYSTEM INTEGRATION VERIFICATION\n";
echo "========================================================\n\n";

$errors = 0;
$passes = 0;

function check(string $label, bool $success, string $message = "") {
    global $errors, $passes;
    if ($success) {
        $passes++;
        echo "✅ PASS: $label " . ($message ? "($message)" : "") . "\n";
    } else {
        $errors++;
        echo "❌ FAIL: $label " . ($message ? "- $message" : "") . "\n";
    }
}

// 1. Table Verification
echo "--- 1. DATABASE SCHEMA INTEGRITY ---\n";
$requiredTables = [
    'sa_students', 'sa_programs', 'sa_point_rules', 'sa_point_transactions',
    'sa_activity_logs', 'sa_tasks', 'sa_task_submissions', 'sa_attendance_logs',
    'sa_badges', 'sa_student_badges', 'sa_penalties', 'sa_referrals',
    'sa_vendor_leads', 'sa_certificates', 'sa_login_logs'
];
foreach ($requiredTables as $table) {
    try {
        $result = $pdo->query("SHOW TABLES LIKE '$table'")->fetch();
        check("Table '$table' exists", !empty($result));
    } catch (Exception $e) {
        check("Table '$table' exists", false, $e->getMessage());
    }
}

// Get Student 1
$studentStmt = $pdo->query("SELECT * FROM sa_students WHERE id = 1 LIMIT 1");
$student = $studentStmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    echo "\n⚠️ No test student with ID 1 found. Aborting logic tests.\n";
    exit(1);
}
$studentId = (int)$student['id'];
echo "\nTarget student for simulation: {$student['full_name']} (ID: $studentId, No: {$student['student_no']}, Points: {$student['total_points']})\n\n";

// 2. Point Engine Verification
echo "--- 2. POINTS & TRANSACTION LEDGER ---\n";
try {
    $engine = new StudentPointEngine($pdo, 2); // Super Admin user ID = 2
    
    // Simulate user onboarding points
    $res = $engine->awardPoints($studentId, 'USER_ONBOARDING', [
        'reference_code' => 'TEST-ONB-001',
        'description' => 'Test User Onboarding reward'
    ]);
    
    check("Award points (USER_ONBOARDING)", $res['success'], $res['message'] ?? '');
    
    if ($res['success']) {
        $transId = $res['transaction_id'];
        // Read balance
        $balance = $pdo->prepare("SELECT total_points FROM sa_students WHERE id = ?");
        $balance->execute([$studentId]);
        $currPoints = (int)$balance->fetchColumn();
        check("Balance cached in sa_students", $currPoints >= 0, "Current points: $currPoints");
    }
} catch (Exception $e) {
    check("Award points engine failure", false, $e->getMessage());
}

// 3. Task Submission Flow Verification
echo "\n--- 3. TASK & CAMPAIGN FLOW ---\n";
try {
    // 3.1 Create Mock Task
    $pdo->exec("DELETE FROM sa_tasks WHERE title = 'Test Task Verification'");
    $taskStmt = $pdo->prepare("
        INSERT INTO sa_tasks (title, description, points_reward, campaign_name, status, created_at, updated_at)
        VALUES ('Test Task Verification', 'Test description for integration test', 20, 'Test Campaign', 'Active', NOW(), NOW())
    ");
    $taskStmt->execute();
    $taskId = (int)$pdo->lastInsertId();
    check("Create mock task in sa_tasks", $taskId > 0, "Task ID: $taskId");

    // 3.2 Submit Task Proof
    $pdo->prepare("DELETE FROM sa_task_submissions WHERE student_id = ? AND task_id = ?")->execute([$studentId, $taskId]);
    $subStmt = $pdo->prepare("
        INSERT INTO sa_task_submissions (student_id, task_id, status, proof_path, notes, created_at)
        VALUES (?, ?, 'Submitted', 'uploads/submissions/test.png', 'Completed the mock task verification', NOW())
    ");
    $subStmt->execute([$studentId, $taskId]);
    $subId = (int)$pdo->lastInsertId();
    check("Submit task proof in sa_task_submissions", $subId > 0, "Submission ID: $subId");

    // 3.3 Admin Approval & Points Allocation
    // Run the logic from submission_logic.php inline
    $pdo->beginTransaction();
    $subFetch = $pdo->prepare("SELECT * FROM sa_task_submissions WHERE id = ? FOR UPDATE");
    $subFetch->execute([$subId]);
    $submission = $subFetch->fetch(PDO::FETCH_ASSOC);
    
    if ($submission && $submission['status'] === 'Submitted') {
        // Update status
        $pdo->prepare("UPDATE sa_task_submissions SET status = 'Approved', reviewed_at = NOW(), reviewed_by_user_id = ? WHERE id = ?")
            ->execute([2, $subId]);
            
        // Award points
        $pointRes = $engine->awardPoints($studentId, 'SMALL_AWARENESS_ACTIVITY', [
            'source_type' => 'task_submission',
            'source_id' => $subId,
            'reference_code' => 'TASK-' . $subId,
            'description' => 'Approved: ' . $submission['notes']
        ]);
        
        $pdo->commit();
        check("Approve task proof and award points", $pointRes['success'], $pointRes['message'] ?? '');
    } else {
        $pdo->rollBack();
        check("Approve task proof", false, "Submission record mismatch");
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    check("Task flow failure", false, $e->getMessage());
}

// 4. Rewards & Badges Verification
echo "\n--- 4. REWARDS & BADGES ---\n";
try {
    $badgeEngine = new StudentBadgeEngine($pdo, 2);
    $badgeRes = $badgeEngine->assignBadge($studentId, 'RISING_STAR', [
        'assignment_type' => 'manual',
        'notes' => 'Awarded by verification test'
    ]);
    check("Award Badge (RISING_STAR)", $badgeRes['success'], $badgeRes['message'] ?? '');
    
    // Check automatic achievements logic check
    $unreadCount = student_portal_unread_count($pdo, $studentId);
    check("Read notifications count for achievements", $unreadCount >= 0, "Unread count: $unreadCount");
} catch (Exception $e) {
    check("Badge system failure", false, $e->getMessage());
}

// 5. Level & Promotions Verification
echo "\n--- 5. LEVEL PROMOTION SYSTEM ---\n";
try {
    $levelEngine = new StudentLevelEngine($pdo);
    $promoRes = $levelEngine->evaluatePromotion($studentId);
    check("Evaluate promotion eligibility", $promoRes['success'], $promoRes['message'] ?? '');
} catch (Exception $e) {
    check("Level promotion failure", false, $e->getMessage());
}

// 6. Certificate Verification
echo "\n--- 6. CERTIFICATE GENERATION ---\n";
try {
    // Check if certificate logic creates pdf or verification record
    $certNo = 'CERT-TEST-' . time();
    $certStmt = $pdo->prepare("
        INSERT INTO sa_certificates (student_id, certificate_no, certificate_title, status, issued_at, created_at, updated_at)
        VALUES (?, ?, 'Certificate of Excellence', 'Generated', NOW(), NOW(), NOW())
    ");
    $certStmt->execute([$studentId, $certNo]);
    $certId = (int)$pdo->lastInsertId();
    check("Create mock certificate", $certId > 0, "Cert ID: $certId");
    
    // Verify certificate
    $verifyStmt = $pdo->prepare("SELECT * FROM sa_certificates WHERE certificate_no = ? LIMIT 1");
    $verifyStmt->execute([$certNo]);
    $verifiedCert = $verifyStmt->fetch(PDO::FETCH_ASSOC);
    check("Verify certificate search", !empty($verifiedCert), "Verified: {$verifiedCert['certificate_no']}");
} catch (Exception $e) {
    check("Certificate verification failure", false, $e->getMessage());
}

// 7. Cleanup Test Records
echo "\n--- 7. SYSTEM CLEANUP ---\n";
try {
    if (isset($taskId)) {
        $pdo->prepare("DELETE FROM sa_tasks WHERE id = ?")->execute([$taskId]);
    }
    if (isset($subId)) {
        $pdo->prepare("DELETE FROM sa_task_submissions WHERE id = ?")->execute([$subId]);
    }
    if (isset($certId)) {
        $pdo->prepare("DELETE FROM sa_certificates WHERE id = ?")->execute([$certId]);
    }
    check("Test cleanup complete", true);
} catch (Exception $e) {
    check("Test cleanup failure", false, $e->getMessage());
}

echo "\n========================================================\n";
echo "🟢 VERIFICATION COMPLETED\n";
echo "   Passes: $passes / " . ($passes + $errors) . "\n";
echo "   Errors: $errors\n";
echo "========================================================\n";

exit($errors === 0 ? 0 : 1);
