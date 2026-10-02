<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$password = (string)($_POST['password'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
    exit;
}

if (!rate_limit_check('partner_login_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 10, 900)) {
    echo json_encode(['success' => false, 'message' => 'Too many login attempts. Please try again later.']);
    exit;
}

try {
    if (!dbTableExists($pdo, 'sa_partners')) {
        echo json_encode(['success' => false, 'message' => 'Partner portal is not available.']);
        exit;
    }

    $stmt = $pdo->prepare('SELECT * FROM sa_partners WHERE owner_email = ? LIMIT 1');
    $stmt->execute([$email]);
    $partner = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$partner || !password_verify($password, (string)($partner['password_hash'] ?? ''))) {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
        exit;
    }

    if (($partner['status'] ?? '') === 'Pending') {
        echo json_encode(['success' => false, 'message' => 'Your account is pending admin approval.']);
        exit;
    }

    if (($partner['status'] ?? '') === 'Rejected') {
        echo json_encode(['success' => false, 'message' => 'Your registration was not approved. Contact support for details.']);
        exit;
    }

    if (($partner['status'] ?? '') === 'Suspended') {
        echo json_encode(['success' => false, 'message' => 'Your partner account is suspended.']);
        exit;
    }

    $_SESSION['partner_logged_in'] = true;
    $_SESSION['partner_id'] = (int)$partner['id'];
    $_SESSION['partner_code'] = $partner['partner_code'];
    $_SESSION['partner_name'] = $partner['business_name'];

    $pdo->prepare('UPDATE sa_partners SET last_login_at = NOW() WHERE id = ?')->execute([(int)$partner['id']]);

    echo json_encode(['success' => true, 'message' => 'Login successful.', 'redirect' => 'partner-dashboard.php']);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Login failed. Please try again.']);
}
