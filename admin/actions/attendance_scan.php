<?php
session_start();
require '../../config/db.php';
require '../../includes/functions.php';
require '../../includes/member_module.php';
require '../../includes/qr_attendance.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!canAccessModule($pdo, 'manager', 'page.events')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token'], $_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$_POST['csrf_token'])) {
    http_response_code(419);
    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
    exit;
}

$attendanceEventId = (int)($_POST['attendance_event_id'] ?? 0);
$rawValue = trim((string)($_POST['token'] ?? ''));

if ($attendanceEventId <= 0 || $rawValue === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Attendance event and QR token are required']);
    exit;
}

$result = null;

try {
    $result = qa_mark_attendance_by_scan($pdo, $attendanceEventId, $rawValue);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'status' => 'server_error',
        'message' => 'Attendance scan failed: ' . $e->getMessage(),
    ]);
    exit;
}

$member = $result['member'] ?? null;
$volunteer = $result['volunteer'] ?? null;

echo json_encode([
    'success' => (bool)($result['success'] ?? false),
    'status' => $result['status'] ?? 'invalid',
    'message' => $result['message'] ?? 'Unable to process attendance.',
    'attendee_type' => $member ? 'member' : ($volunteer ? 'volunteer' : ''),
    'member' => $member ? [
        'full_name' => $member['full_name'] ?? '',
        'member_no' => $member['member_no'] ?? '',
        'phone' => $member['phone'] ?? '',
        'designation_title' => $member['designation_title'] ?? '',
    ] : null,
    'volunteer' => $volunteer ? [
        'full_name' => $volunteer['name'] ?? '',
        'member_no' => $volunteer['id_card_no'] ?? '',
        'phone' => $volunteer['phone'] ?? '',
        'designation_title' => 'Volunteer',
    ] : null,
    'scan_time' => date('d M Y h:i:s A'),
]);
