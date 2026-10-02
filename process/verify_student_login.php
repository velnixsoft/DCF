<?php
header('Content-Type: application/json');

require '../config/db.php';
require '../includes/functions.php';
require '../includes/student/portal_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$csrf = (string)($_POST['csrf_token'] ?? '');
if ($csrf === '' || empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], $csrf)) {
    echo json_encode(['success' => false, 'message' => 'Security token expired. Please refresh and try again.']);
    exit;
}

if (!rate_limit_check('student_login_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 8, 900)) {
    echo json_encode(['success' => false, 'message' => 'Too many login attempts. Please wait 15 minutes.']);
    exit;
}

$loginId = trim(cleanInput($_POST['login_id'] ?? ''));
$password = $_POST['password'] ?? '';

if ($loginId === '' || $password === '') {
    echo json_encode(['success' => false, 'message' => 'Please enter both login credentials.']);
    exit;
}

try {
    // Lookup by email or mobile
    $stmt = $pdo->prepare("SELECT * FROM sa_students WHERE email = ? OR mobile = ? LIMIT 1");
    $stmt->execute([$loginId, $loginId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'No student ambassador account found with this email/mobile. Please register first.']);
        exit;
    }

    if (!password_verify($password, $student['password_hash'])) {
        echo json_encode(['success' => false, 'message' => 'Incorrect password. Please try again.']);
        exit;
    }

    // Check account status
    if ($student['status'] === 'Pending') {
        echo json_encode(['success' => false, 'message' => 'Your student ambassador account is pending admin approval.']);
        exit;
    } elseif ($student['status'] === 'Rejected') {
        $reason = trim((string)($student['rejection_reason'] ?? ''));
        $message = 'Your application was rejected.';
        if ($reason !== '') {
            $message .= ' Reason: ' . $reason;
        }
        echo json_encode(['success' => false, 'message' => $message]);
        exit;
    } elseif ($student['status'] === 'Suspended') {
        $reason = trim((string)($student['rejection_reason'] ?? ''));
        $message = 'Your account has been suspended.';
        if ($reason !== '') {
            $message .= ' Reason: ' . $reason;
        } else {
            $message .= ' Please contact the administrator.';
        }
        echo json_encode(['success' => false, 'message' => $message]);
        exit;
    } elseif ($student['status'] !== 'Active') {
        echo json_encode(['success' => false, 'message' => 'Your account is not active. Status: ' . $student['status']]);
        exit;
    }

    session_regenerate_id(true);

    $_SESSION['student_logged_in'] = true;
    $_SESSION['student_id'] = (int)$student['id'];
    $_SESSION['student_no'] = $student['student_no'];
    $_SESSION['student_name'] = $student['full_name'];
    $_SESSION['student_email'] = $student['email'];

    $profileProgress = student_portal_profile_progress($student);
    $redirect = $profileProgress['complete'] ? 'student-dashboard.php' : 'student-profile.php';

    // Update login count and timestamp
    $updateStmt = $pdo->prepare("UPDATE sa_students SET login_count = login_count + 1, last_login_at = NOW(), updated_at = NOW() WHERE id = ?");
    $updateStmt->execute([$student['id']]);

    // Record login log
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $logStmt = $pdo->prepare("INSERT INTO sa_login_logs (student_id, login_at, ip_address, user_agent) VALUES (?, NOW(), ?, ?)");
    $logStmt->execute([$student['id'], $ip, $ua]);

    echo json_encode([
        'success' => true,
        'redirect' => $redirect,
        'message' => $profileProgress['complete'] ? 'Login successful!' : 'Login successful. Please complete your profile.',
        'profile_complete' => $profileProgress['complete'],
        'profile_percent' => $profileProgress['percent'],
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'A database error occurred. Please try again.']);
}

exit;
