<?php
require '../../config/db.php';
require '../../includes/functions.php';
require '../../includes/member_module.php';

if (!isset($_SESSION['logged_in'])) {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../member_messages.php');
    exit;
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    setFlash('error', 'Security token invalid.');
    header('Location: ../member_messages.php');
    exit;
}

$action = $_POST['action'] ?? '';

if ($action !== 'send_message') {
    setFlash('error', 'Unknown action.');
    header('Location: ../member_messages.php');
    exit;
}

$target = $_POST['target'] ?? 'all';
$memberId = (int)($_POST['member_id'] ?? 0);
$subject = mm_clean($_POST['subject'] ?? '');
$message = trim($_POST['message_body'] ?? '');

if ($subject === '' || $message === '') {
    setFlash('error', 'Subject and message are required.');
    header('Location: ../member_messages.php');
    exit;
}

$members = [];
if ($target === 'single' && $memberId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM members WHERE id = ? AND status = 'Active'");
    $stmt->execute([$memberId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $members[] = $row;
    }
} else {
    $stmt = $pdo->query("SELECT * FROM members WHERE status = 'Active' ORDER BY id DESC");
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if (empty($members)) {
    setFlash('error', 'No active members found for selected target.');
    header('Location: ../member_messages.php');
    exit;
}

$settings = mm_load_settings($pdo);
$sentCount = 0;
$failedCount = 0;

$pdo->beginTransaction();
try {
    foreach ($members as $m) {
        $msgStmt = $pdo->prepare("INSERT INTO member_messages (member_id, subject, message_body, message_type, created_by) VALUES (?, ?, ?, 'manual', ?)");
        $msgStmt->execute([(int)$m['id'], $subject, $message, (int)($_SESSION['user_id'] ?? 0)]);
        $messageId = (int)$pdo->lastInsertId();

        $deliveryStatus = 'Pending';
        try {
            if (!empty($m['email'])) {
                mm_send_email(
                    $settings,
                    $m['email'],
                    $m['full_name'],
                    $subject . ' - ' . ($settings['site_name'] ?? 'NGO'),
                    '<p>Dear ' . htmlspecialchars($m['full_name']) . ',</p><p>' . nl2br(htmlspecialchars($message)) . '</p>'
                );
                $deliveryStatus = 'Sent';
                $sentCount++;
            } else {
                $failedCount++;
                $deliveryStatus = 'Failed';
            }
        } catch (Exception $e) {
            $failedCount++;
            $deliveryStatus = 'Failed';
        }

        $dStmt = $pdo->prepare("INSERT INTO member_message_deliveries (message_id, member_id, email_status, dashboard_status, sent_at) VALUES (?, ?, ?, 'Unread', NOW())");
        $dStmt->execute([$messageId, (int)$m['id'], $deliveryStatus]);
    }

    $pdo->commit();
    setFlash('success', "Message delivery complete. Sent: {$sentCount}, Failed: {$failedCount}");
} catch (PDOException $e) {
    $pdo->rollBack();
    setFlash('error', 'Database error while sending messages.');
}

header('Location: ../member_messages.php');
exit;
