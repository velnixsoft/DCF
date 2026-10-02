<?php
/**
 * Complaints & Suggestions System Migration Runner
 * Executes create_complaints_management.sql on the database with safety checks.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/db.php';

echo "=== Running Complaints & Suggestions Migration ===\n\n";

try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

    // 1. Create `complaints` table
    echo "1. Creating `complaints` table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `complaints` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `ticket_no` varchar(50) NOT NULL,
          `name` varchar(100) NOT NULL,
          `contact` varchar(50) NOT NULL,
          `email` varchar(100) DEFAULT NULL,
          `type` enum('complaint','suggestion') NOT NULL DEFAULT 'complaint',
          `subject` varchar(255) NOT NULL,
          `description` text NOT NULL,
          `status` enum('pending','in_progress','on_hold','resolved') NOT NULL DEFAULT 'pending',
          `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
          `admin_reply` text DEFAULT NULL,
          `reply_by` int(11) DEFAULT NULL,
          `replied_at` datetime DEFAULT NULL,
          `resolved_at` datetime DEFAULT NULL,
          `attachment_path` varchar(255) DEFAULT NULL,
          `user_ip` varchar(45) DEFAULT NULL,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
          PRIMARY KEY (`id`),
          UNIQUE KEY `uniq_complaint_ticket` (`ticket_no`),
          KEY `idx_complaints_type` (`type`),
          KEY `idx_complaints_status` (`status`),
          KEY `idx_complaints_contact` (`contact`),
          KEY `idx_complaints_email` (`email`),
          KEY `idx_complaints_created` (`created_at`),
          KEY `idx_complaints_reply_by` (`reply_by`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "   ✅ Table `complaints` created successfully.\n\n";

    // 2. Add foreign key safely if users table exists
    try {
        $pdo->exec("
            ALTER TABLE `complaints` 
            ADD CONSTRAINT `fk_complaints_reply_by` 
            FOREIGN KEY (`reply_by`) REFERENCES `users` (`id`) 
            ON DELETE SET NULL;
        ");
        echo "   ✅ Foreign key `fk_complaints_reply_by` linked to `users(id)`.\n\n";
    } catch (Throwable $e) {
        // FK might already exist or users table difference, non-fatal
    }

    // 3. Seed initial sample tickets if table is empty
    $count = (int)$pdo->query("SELECT COUNT(*) FROM `complaints`")->fetchColumn();
    if ($count === 0) {
        echo "2. Seeding initial sample complaints and suggestions...\n";
        $adminId = $pdo->query("SELECT id FROM users LIMIT 1")->fetchColumn() ?: null;

        $samples = [
            [
                'ticket_no' => 'TKT-' . date('Y') . '-1001',
                'name' => 'Vikram Singhania',
                'contact' => '+91 9876543210',
                'email' => 'vikram.s@example.com',
                'type' => 'complaint',
                'subject' => 'Delayed Ration Kit Distribution in Ward 12',
                'description' => 'The scheduled food and dry ration distribution in Ward 12 was delayed by 3 hours today. Beneficiaries had to wait in the sun.',
                'status' => 'in_progress',
                'priority' => 'high',
                'admin_reply' => 'Our regional coordinator is investigating the vehicle breakdown issue. Support teams have now arrived on site.',
                'reply_by' => $adminId,
                'replied_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
            ],
            [
                'ticket_no' => 'TKT-' . date('Y') . '-1002',
                'name' => 'Pooja Deshmukh',
                'contact' => '+91 9812345678',
                'email' => 'pooja.d@example.com',
                'type' => 'suggestion',
                'subject' => 'Suggestion to Add Digital Health Checkup Tracker',
                'description' => 'It would be great if beneficiary medical cards include a QR code linking their basic immunization and health records.',
                'status' => 'pending',
                'priority' => 'medium',
                'admin_reply' => null,
                'reply_by' => null,
                'replied_at' => null
            ],
            [
                'ticket_no' => 'TKT-' . date('Y') . '-1003',
                'name' => 'Mohammed Farhan',
                'contact' => '+91 9723456789',
                'email' => 'farhan.m@example.com',
                'type' => 'complaint',
                'subject' => 'Receipt Download Link Not Working',
                'description' => 'I made a clothes donation yesterday but the instant SMS receipt download link showed a server timeout.',
                'status' => 'resolved',
                'priority' => 'medium',
                'admin_reply' => 'The issue has been resolved and your receipt PDF has been resent to your verified email address.',
                'reply_by' => $adminId,
                'replied_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))
            ]
        ];

        $ins = $pdo->prepare("
            INSERT INTO `complaints` 
                (`ticket_no`, `name`, `contact`, `email`, `type`, `subject`, `description`, `status`, `priority`, `admin_reply`, `reply_by`, `replied_at`, `resolved_at`)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($samples as $s) {
            $resolvedAt = ($s['status'] === 'resolved') ? date('Y-m-d H:i:s') : null;
            $ins->execute([
                $s['ticket_no'], $s['name'], $s['contact'], $s['email'], $s['type'],
                $s['subject'], $s['description'], $s['status'], $s['priority'],
                $s['admin_reply'], $s['reply_by'], $s['replied_at'], $resolvedAt
            ]);
        }
        echo "   ✅ Seeded 3 sample tickets.\n\n";
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    echo "=== Complaints & Suggestions Migration Completed Successfully! ===\n";

} catch (Throwable $e) {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    echo "❌ Migration Error: " . $e->getMessage() . "\n";
    exit(1);
}
