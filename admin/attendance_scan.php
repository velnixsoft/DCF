<?php
/**
 * actions/attendance_scan.php
 * AJAX endpoint — receives a QR token or raw scan value and marks attendance.
 * Returns JSON.
 */

declare(strict_types=1);

header('Content-Type: application/json');

// ── Bootstrap ────────────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/member_module.php';
require_once __DIR__ . '/../../includes/qr_attendance.php';

// ── Auth guard ───────────────────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'status' => 'unauthorized', 'message' => 'Not authenticated.']);
    exit;
}

// ── Method guard ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'status' => 'method_not_allowed', 'message' => 'POST required.']);
    exit;
}

// ── CSRF check ───────────────────────────────────────────────────────────────
$csrfToken = trim((string)($_POST['csrf_token'] ?? ''));
if ($csrfToken === '' || !hash_equals((string)($_SESSION['csrf_token'] ?? ''), $csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'status' => 'csrf_error', 'message' => 'Invalid or missing CSRF token.']);
    exit;
}

// ── Permission guard ─────────────────────────────────────────────────────────
if (!canAccessModule($pdo, 'manager', 'page.events')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'status' => 'forbidden', 'message' => 'Access denied.']);
    exit;
}

// ── Input ────────────────────────────────────────────────────────────────────
$attendanceEventId = (int)($_POST['attendance_event_id'] ?? 0);
$rawValue          = trim((string)($_POST['token'] ?? ''));

if ($attendanceEventId <= 0) {
    echo json_encode(['success' => false, 'status' => 'invalid_event', 'message' => 'No attendance event specified.']);
    exit;
}

if ($rawValue === '') {
    echo json_encode(['success' => false, 'status' => 'empty_input', 'message' => 'No token or QR value provided.']);
    exit;
}

// ── Process ──────────────────────────────────────────────────────────────────
try {
    $result = qa_mark_attendance_by_scan($pdo, $attendanceEventId, $rawValue);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'status'  => 'server_error',
        'message' => 'An internal error occurred. Please try again.',
        // Omit $e->getMessage() in production to avoid leaking internals
    ]);
    exit;
}

echo json_encode($result);
