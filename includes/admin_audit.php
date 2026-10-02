<?php

if (!function_exists('admin_audit_log')) {
    function admin_audit_log(PDO $pdo, string $action, string $entityType, ?int $entityId, string $description = ''): void
    {
        try {
            $check = $pdo->query("SHOW TABLES LIKE 'admin_audit_logs'");
            if (!$check || !$check->fetchColumn()) {
                return;
            }

            $stmt = $pdo->prepare("
                INSERT INTO admin_audit_logs (
                    user_id, action, entity_type, entity_id, description,
                    ip_address, user_agent, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            $stmt->execute([
                isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null,
                substr($action, 0, 80),
                substr($entityType, 0, 80),
                $entityId,
                $description !== '' ? $description : null,
                $_SERVER['REMOTE_ADDR'] ?? null,
                isset($_SERVER['HTTP_USER_AGENT']) ? substr((string)$_SERVER['HTTP_USER_AGENT'], 0, 255) : null,
            ]);
        } catch (Throwable $e) {
            error_log('[admin_audit_log] ' . $e->getMessage());
        }
    }
}
