<?php
/**
 * Recurring Donations / Auto Pay Migration Runner
 * Usage via CLI: php run_recurring_migration.php
 * Usage via Browser: http://yourdomain/run_recurring_migration.php
 */

require_once __DIR__ . '/config/db.php';

$isCli = (php_sapi_name() === 'cli');

echo $isCli ? "=== Running Recurring Donations Migration ===\n\n" : "<h3>Recurring Donations Migration</h3><pre>";

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

    // 1. Create recurring_donations table
    echo "1. Creating `recurring_donations` table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `recurring_donations` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `project_id` int(11) DEFAULT NULL,
          `donor_name` varchar(100) NOT NULL,
          `donor_email` varchar(100) NOT NULL,
          `donor_mobile` varchar(20) NOT NULL,
          `donor_pan` varchar(20) DEFAULT NULL,
          `donor_address` text DEFAULT NULL,
          `amount` decimal(10,2) NOT NULL,
          `frequency` enum('monthly','quarterly','half_yearly','yearly') NOT NULL DEFAULT 'monthly',
          `billing_cycle_count` int(11) NOT NULL DEFAULT 0 COMMENT '0 for unlimited / until cancelled',
          `completed_cycles` int(11) NOT NULL DEFAULT 0,
          `payment_gateway` enum('Razorpay','PhonePe','Manual') NOT NULL DEFAULT 'Razorpay',
          `razorpay_plan_id` varchar(100) DEFAULT NULL,
          `razorpay_customer_id` varchar(100) DEFAULT NULL,
          `razorpay_subscription_id` varchar(100) DEFAULT NULL,
          `razorpay_token_id` varchar(100) DEFAULT NULL,
          `start_date` date NOT NULL,
          `end_date` date DEFAULT NULL,
          `next_charge_date` date DEFAULT NULL,
          `last_charge_date` date DEFAULT NULL,
          `status` enum('pending','active','paused','stopped','completed','failed') NOT NULL DEFAULT 'pending',
          `pause_reason` varchar(255) DEFAULT NULL,
          `cancel_reason` varchar(255) DEFAULT NULL,
          `is_80g_eligible` tinyint(1) NOT NULL DEFAULT 0,
          `referral_code` varchar(40) DEFAULT NULL,
          `sa_student_id` int(11) DEFAULT NULL,
          `field_agent_id` int(11) DEFAULT NULL,
          `notes` text DEFAULT NULL,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
          PRIMARY KEY (`id`),
          UNIQUE KEY `uniq_rec_subscription_id` (`razorpay_subscription_id`),
          KEY `idx_rec_donor_email` (`donor_email`),
          KEY `idx_rec_donor_mobile` (`donor_mobile`),
          KEY `idx_rec_status_next_charge` (`status`, `next_charge_date`),
          KEY `idx_rec_project_id` (`project_id`),
          KEY `idx_rec_sa_student` (`sa_student_id`),
          KEY `idx_rec_field_agent` (`field_agent_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "   ✅ Table `recurring_donations` created successfully.\n\n";

    // 2. Create recurring_donation_transactions table
    echo "2. Creating `recurring_donation_transactions` table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `recurring_donation_transactions` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `recurring_donation_id` int(11) NOT NULL,
          `donation_id` int(11) DEFAULT NULL COMMENT 'FK to master donations table',
          `cycle_number` int(11) NOT NULL DEFAULT 1,
          `amount` decimal(10,2) NOT NULL,
          `payment_gateway` enum('Razorpay','PhonePe','Manual') NOT NULL DEFAULT 'Razorpay',
          `razorpay_subscription_id` varchar(100) DEFAULT NULL,
          `razorpay_payment_id` varchar(120) DEFAULT NULL,
          `razorpay_order_id` varchar(120) DEFAULT NULL,
          `razorpay_signature` varchar(255) DEFAULT NULL,
          `razorpay_invoice_id` varchar(120) DEFAULT NULL,
          `charge_date` date NOT NULL,
          `status` enum('pending','success','failed','refunded') NOT NULL DEFAULT 'pending',
          `receipt_no` varchar(50) DEFAULT NULL,
          `error_code` varchar(100) DEFAULT NULL,
          `error_description` text DEFAULT NULL,
          `webhook_payload` longtext DEFAULT NULL,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
          PRIMARY KEY (`id`),
          KEY `idx_rec_tx_parent` (`recurring_donation_id`),
          KEY `idx_rec_tx_donation` (`donation_id`),
          KEY `idx_rec_tx_status_date` (`status`, `charge_date`),
          KEY `idx_rec_tx_payment_id` (`razorpay_payment_id`),
          KEY `idx_rec_tx_subscription_id` (`razorpay_subscription_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "   ✅ Table `recurring_donation_transactions` created successfully.\n\n";

    // 3. Add foreign key constraints safely (if not already existing)
    echo "3. Verifying Foreign Key Constraints...\n";
    $constraints = [
        ['tbl' => 'recurring_donations', 'fk' => 'fk_rec_project', 'sql' => "ALTER TABLE `recurring_donations` ADD CONSTRAINT `fk_rec_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL;"],
        ['tbl' => 'recurring_donations', 'fk' => 'fk_rec_student', 'sql' => "ALTER TABLE `recurring_donations` ADD CONSTRAINT `fk_rec_student` FOREIGN KEY (`sa_student_id`) REFERENCES `sa_students` (`id`) ON DELETE SET NULL;"],
        ['tbl' => 'recurring_donations', 'fk' => 'fk_rec_agent', 'sql' => "ALTER TABLE `recurring_donations` ADD CONSTRAINT `fk_rec_agent` FOREIGN KEY (`field_agent_id`) REFERENCES `field_agents` (`id`) ON DELETE SET NULL;"],
        ['tbl' => 'recurring_donation_transactions', 'fk' => 'fk_rec_tx_parent', 'sql' => "ALTER TABLE `recurring_donation_transactions` ADD CONSTRAINT `fk_rec_tx_parent` FOREIGN KEY (`recurring_donation_id`) REFERENCES `recurring_donations` (`id`) ON DELETE CASCADE;"],
        ['tbl' => 'recurring_donation_transactions', 'fk' => 'fk_rec_tx_donation', 'sql' => "ALTER TABLE `recurring_donation_transactions` ADD CONSTRAINT `fk_rec_tx_donation` FOREIGN KEY (`donation_id`) REFERENCES `donations` (`id`) ON DELETE SET NULL;"],
    ];

    foreach ($constraints as $c) {
        try {
            $pdo->exec($c['sql']);
            echo "   ✅ Added constraint `{$c['fk']}`\n";
        } catch (Throwable $e) {
            // Already exists or parent table check
            if (stripos($e->getMessage(), 'duplicate') !== false || stripos($e->getMessage(), 'already exists') !== false) {
                echo "   ℹ️ Constraint `{$c['fk']}` already present.\n";
            } else {
                echo "   ⚠️ Constraint notice (`{$c['fk']}`): " . $e->getMessage() . "\n";
            }
        }
    }
    echo "\n";

    // 4. Check & add columns to `donations` table if not present
    echo "4. Checking `donations` table columns...\n";
    $colCheck = $pdo->query("SHOW COLUMNS FROM `donations` LIKE 'recurring_donation_id'")->fetch();
    if (!$colCheck) {
        $pdo->exec("ALTER TABLE `donations` ADD COLUMN `recurring_donation_id` int(11) DEFAULT NULL AFTER `sa_student_id`");
        $pdo->exec("ALTER TABLE `donations` ADD KEY `idx_donations_recurring_id` (`recurring_donation_id`)");
        echo "   ✅ Added `recurring_donation_id` column to `donations`.\n";
    } else {
        echo "   ℹ️ Column `recurring_donation_id` already exists in `donations`.\n";
    }

    $colCheck2 = $pdo->query("SHOW COLUMNS FROM `donations` LIKE 'is_recurring'")->fetch();
    if (!$colCheck2) {
        $pdo->exec("ALTER TABLE `donations` ADD COLUMN `is_recurring` tinyint(1) NOT NULL DEFAULT 0 AFTER `recurring_donation_id`");
        echo "   ✅ Added `is_recurring` column to `donations`.\n";
    } else {
        echo "   ℹ️ Column `is_recurring` already exists in `donations`.\n";
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    echo "\n============================================\n";
    echo "🎉 ALL MIGRATIONS COMPLETED SUCCESSFULLY!\n";
    echo "============================================\n\n";

    // 5. Final Verification
    $tables = ['recurring_donations', 'recurring_donation_transactions'];
    echo "--- Table Verification ---\n";
    foreach ($tables as $t) {
        $check = $pdo->query("SHOW TABLES LIKE '$t'")->fetch();
        if ($check) {
            $count = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
            echo "✅ Table `$t` exists and ready (Current rows: $count).\n";
        } else {
            echo "❌ Table `$t` NOT found.\n";
        }
    }

} catch (Throwable $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
}

if (!$isCli) {
    echo "</pre>";
}
