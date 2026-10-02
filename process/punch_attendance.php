<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require '../config/db.php';
require '../includes/functions.php';

$userId = (int)$_SESSION['user_id'];
$userHierarchy = normalizeHierarchyRole($_SESSION['hierarchy_level'] ?? '');

if ($userHierarchy !== 'field_agent') {
    echo json_encode(['success' => false, 'message' => 'Field Agent only']);
    exit;
}

$punchType = $_POST['punch_type'] ?? '';
$lat = $_POST['lat'] ?? null;
$lng = $_POST['lng'] ?? null;
$address = $_POST['address'] ?? null;

if (!in_array($punchType, ['IN', 'OUT'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid punch type']);
    exit;
}

if (!$lat || !$lng) {
    echo json_encode(['success' => false, 'message' => 'GPS location required']);
    exit;
}

// Get agent ID
$agentStmt = $pdo->prepare('SELECT id FROM field_agents WHERE user_id = ?');
$agentStmt->execute([$userId]);
$agentId = $agentStmt->fetchColumn();

if (!$agentId) {
    echo json_encode(['success' => false, 'message' => 'Agent profile not found']);
    exit;
}

$statusStmt = $pdo->prepare('SELECT attendance_status FROM field_agents WHERE id = ? LIMIT 1');
$statusStmt->execute([$agentId]);
$attendanceStatus = (string)($statusStmt->fetchColumn() ?: 'active');
if ($attendanceStatus !== 'active') {
    echo json_encode(['success' => false, 'message' => 'Attendance is restricted due to compliance issues. Contact your manager.']);
    exit;
}

$lastPunchStmt = $pdo->prepare('SELECT punch_type FROM agent_attendance WHERE agent_id = ? ORDER BY punch_time DESC LIMIT 1');
$lastPunchStmt->execute([$agentId]);
$lastPunchType = (string)($lastPunchStmt->fetchColumn() ?: '');
if ($lastPunchType === $punchType) {
    echo json_encode(['success' => false, 'message' => 'Duplicate punch type. Please alternate IN and OUT punches.']);
    exit;
}

// Record punch
$stmt = $pdo->prepare('
    INSERT INTO agent_attendance (agent_id, punch_type, latitude, longitude, address) 
    VALUES (?, ?, ?, ?, ?)
');
if ($stmt->execute([$agentId, $punchType, $lat, $lng, $address])) {
    echo json_encode([
        'success' => true, 
        'message' => "Punch $punchType recorded at " . date('H:i:s'),
        'location' => $address
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to record punch']);
}

