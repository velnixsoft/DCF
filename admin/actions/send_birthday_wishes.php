<?php
require '../../config/db.php';
require '../../includes/functions.php';
require '../../includes/member_module.php';

$settings = mm_load_settings($pdo);
$cronKey = $_GET['key'] ?? '';
$isAdminSession = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$isCronAuth = !empty($settings['birthday_cron_key']) && hash_equals($settings['birthday_cron_key'], $cronKey);

if (!$isAdminSession && !$isCronAuth) {
    http_response_code(403);
    echo 'Unauthorized';
    exit;
}

$today = date('Y-m-d');
$todayMd = date('m-d');
$year = date('Y');

$stmt = $pdo->prepare("
    SELECT *
    FROM members
    WHERE status = 'Active'
      AND dob IS NOT NULL
      AND DATE_FORMAT(dob, '%m-%d') = ?
      AND (last_birthday_wish_on IS NULL OR YEAR(last_birthday_wish_on) < ?)
");
$stmt->execute([$todayMd, $year]);
$birthdayMembers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sent = 0;
$failed = 0;

foreach ($birthdayMembers as $m) {
    $subject = 'Happy Birthday, ' . $m['full_name'] . '!';
    $bodyText = "Wishing you a very Happy Birthday from " . ($settings['site_name'] ?? 'our NGO') . ". Thank you for being a valuable member.";

    $msgStmt = $pdo->prepare("INSERT INTO member_messages (member_id, subject, message_body, message_type, created_by) VALUES (?, ?, ?, 'birthday', ?)");
    $msgStmt->execute([(int)$m['id'], $subject, $bodyText, (int)($_SESSION['user_id'] ?? 0)]);
    $messageId = (int)$pdo->lastInsertId();

    $emailStatus = 'Pending';
    try {
        mm_send_email(
            $settings,
            $m['email'],
            $m['full_name'],
            $subject,
            '<p>Dear ' . htmlspecialchars($m['full_name']) . ',</p><p>' . htmlspecialchars($bodyText) . '</p><p>Regards,<br>' . htmlspecialchars($settings['site_name'] ?? 'NGO') . '</p>'
        );
        $emailStatus = 'Sent';
        $sent++;
    } catch (Exception $e) {
        $emailStatus = 'Failed';
        $failed++;
    }

    $dStmt = $pdo->prepare("INSERT INTO member_message_deliveries (message_id, member_id, email_status, dashboard_status, sent_at) VALUES (?, ?, ?, 'Unread', NOW())");
    $dStmt->execute([$messageId, (int)$m['id'], $emailStatus]);

    $pdo->prepare("UPDATE members SET last_birthday_wish_on = ? WHERE id = ?")->execute([$today, (int)$m['id']]);
}

if ($isAdminSession) {
    setFlash('success', "Birthday wishes processed. Sent: {$sent}, Failed: {$failed}");
    header('Location: ../member_messages.php');
    exit;
}

header('Content-Type: application/json');
echo json_encode(['ok' => true, 'sent' => $sent, 'failed' => $failed, 'date' => $today]);
