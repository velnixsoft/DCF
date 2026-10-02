<?php

if (!function_exists('student_send_notification')) {
    function student_send_notification(PDO $pdo, int $studentId, string $type, array $vars = []): void
    {
        try {
            require_once __DIR__ . '/notification_manager.php';
            require_once __DIR__ . '/notification_template.php';

            $template = new NotificationTemplate($pdo);
            $title = $template->renderDashboardTitle($type, $vars);
            $message = $template->renderDashboardMessage($type, $vars);

            $manager = new NotificationManager($pdo);
            $manager->createNotification($studentId, $type, $title, $message, $vars);
        } catch (Throwable $e) {
            error_log('[student_send_notification] ' . $e->getMessage());
        }
    }
}
