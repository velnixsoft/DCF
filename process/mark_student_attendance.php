<?php
header('Content-Type: application/json');

require '../config/db.php';
require '../includes/functions.php';
require '../includes/student/badges.php';

if (empty($_SESSION['student_logged_in']) || empty($_SESSION['student_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please log in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$studentId = (int)$_SESSION['student_id'];

try {
    // Check if attendance already marked today
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM sa_attendance_logs WHERE student_id = ? AND attendance_date = CURDATE()");
    $checkStmt->execute([$studentId]);
    if ($checkStmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'You have already marked your attendance for today.']);
        exit;
    }

    // Insert attendance record
    $sql = "INSERT INTO sa_attendance_logs (student_id, attendance_type, status, attendance_date, created_at) VALUES (?, 'Daily', 'Present', CURDATE(), NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$studentId]);

    // Log Activity
    $logSql = "INSERT INTO sa_activity_logs (student_id, activity_type, title, description, points, created_at) 
               VALUES (?, 'attendance', 'Marked Daily Attendance', 'Checked in for daily attendance', 0, NOW())";
    $logStmt = $pdo->prepare($logSql);
    $logStmt->execute([$studentId]);

    $badgeEngine = new StudentBadgeEngine($pdo);
    $badgeEngine->evaluateBadges($studentId, ['apply' => true]);

    echo json_encode(['success' => true, 'message' => 'Daily attendance marked successfully!']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'A database error occurred. Details: ' . $e->getMessage()]);
}

exit;
