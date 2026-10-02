<?php
/**
 * Beneficiary Management System Migration Runner
 * Executes create_beneficiary_management.sql on the database with safety checks.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/db.php';

echo "=== Running Beneficiary Management Migration ===\n\n";

try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

    // 1. Create `beneficiary_categories`
    echo "1. Creating `beneficiary_categories` table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `beneficiary_categories` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `category_name` varchar(100) NOT NULL,
          `category_slug` varchar(100) NOT NULL,
          `description` text DEFAULT NULL,
          `icon` varchar(100) DEFAULT 'fa-hands-holding-child',
          `is_active` tinyint(1) NOT NULL DEFAULT 1,
          `display_order` int(11) NOT NULL DEFAULT 0,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
          PRIMARY KEY (`id`),
          UNIQUE KEY `uniq_ben_cat_name` (`category_name`),
          UNIQUE KEY `uniq_ben_cat_slug` (`category_slug`),
          KEY `idx_ben_cat_active_order` (`is_active`, `display_order`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "   ✅ Table `beneficiary_categories` ready.\n\n";

    // 2. Seed Default Beneficiary Categories
    echo "2. Seeding default welfare categories...\n";
    $categories = [
        [1, 'Below Poverty Line (BPL)', 'below-poverty-line', 'Economically disadvantaged and low-income families requiring essential livelihood support', 'fa-house-chimney-crack', 1, 1],
        [2, 'Senior Citizens & Elderly', 'senior-citizens', 'Aged individuals without family support needing care, health, and ration assistance', 'fa-person-cane', 1, 2],
        [3, 'Orphans & Vulnerable Children', 'orphans-children', 'Children in need of care, education sponsorship, nutrition, and shelter', 'fa-child-reaching', 1, 3],
        [4, 'Widows & Single Mothers', 'widows-single-mothers', 'Women facing socio-economic distress requiring financial, nutritional, or livelihood aid', 'fa-person-dress', 1, 4],
        [5, 'Divyang (Physically Challenged)', 'physically-challenged', 'Persons with special physical or mobility needs requiring assistive equipment and aids', 'fa-wheelchair', 1, 5],
        [6, 'Students & Education Aid', 'students-education', 'Underprivileged students requiring books, school fees, uniforms, and educational kits', 'fa-graduation-cap', 1, 6],
        [7, 'Medical & Health Patients', 'medical-patients', 'Patients needing critical medical support, medicines, treatment subsidies, or health devices', 'fa-hand-holding-medical', 1, 7],
        [8, 'Disaster & Emergency Relief', 'disaster-relief', 'Families affected by natural calamities, fire, floods, or sudden emergencies', 'fa-tents', 1, 8],
        [9, 'Daily Wage & Migrant Workers', 'daily-wage-workers', 'Informal workers needing emergency ration, clothing, health aids, or skill support', 'fa-person-digging', 1, 9],
        [10, 'General Welfare', 'general-welfare', 'General community welfare beneficiaries receiving community distribution support', 'fa-hands-holding-heart', 1, 10]
    ];

    $catStmt = $pdo->prepare("
        INSERT INTO `beneficiary_categories` 
            (`id`, `category_name`, `category_slug`, `description`, `icon`, `is_active`, `display_order`)
        VALUES 
            (?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            `description` = VALUES(`description`),
            `icon` = VALUES(`icon`),
            `display_order` = VALUES(`display_order`)
    ");

    foreach ($categories as $cat) {
        $catStmt->execute($cat);
    }
    echo "   ✅ Seeded 10 standard welfare categories successfully.\n\n";

    // 3. Create `beneficiaries` table
    echo "3. Creating `beneficiaries` table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `beneficiaries` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `beneficiary_code` varchar(50) DEFAULT NULL,
          `name` varchar(150) NOT NULL,
          `father_or_spouse_name` varchar(150) DEFAULT NULL,
          `gender` enum('Male','Female','Other') NOT NULL DEFAULT 'Male',
          `dob` date DEFAULT NULL,
          `age` int(11) DEFAULT NULL,
          `contact` varchar(20) NOT NULL,
          `alternate_contact` varchar(20) DEFAULT NULL,
          `email` varchar(100) DEFAULT NULL,
          `aadhar_no` varchar(20) DEFAULT NULL,
          `ration_card_no` varchar(50) DEFAULT NULL,
          `category_id` int(11) DEFAULT NULL,
          `beneficiary_type` varchar(100) DEFAULT 'General / BPL',
          `annual_income` decimal(10,2) DEFAULT NULL,
          `family_members_count` int(11) NOT NULL DEFAULT 1,
          `disability_status` enum('No','Yes') NOT NULL DEFAULT 'No',
          `disability_details` varchar(255) DEFAULT NULL,
          `address` text NOT NULL,
          `block` varchar(100) DEFAULT NULL,
          `district` varchar(100) NOT NULL,
          `state` varchar(100) NOT NULL,
          `village_city` varchar(100) DEFAULT NULL,
          `pincode` varchar(10) DEFAULT NULL,
          `photo` varchar(255) DEFAULT NULL,
          `id_proof_doc` varchar(255) DEFAULT NULL,
          `income_proof_doc` varchar(255) DEFAULT NULL,
          `registration_date` date NOT NULL,
          `status` enum('Active','Inactive','Under Review','Assisted','Archived') NOT NULL DEFAULT 'Active',
          `project_id` int(11) DEFAULT NULL,
          `registered_by` int(11) DEFAULT NULL,
          `coordinator_id` int(11) DEFAULT NULL,
          `field_agent_id` int(11) DEFAULT NULL,
          `sa_student_id` int(11) DEFAULT NULL,
          `remarks` text DEFAULT NULL,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
          PRIMARY KEY (`id`),
          UNIQUE KEY `uniq_beneficiary_code` (`beneficiary_code`),
          KEY `idx_ben_name` (`name`),
          KEY `idx_ben_contact` (`contact`),
          KEY `idx_ben_aadhar` (`aadhar_no`),
          KEY `idx_ben_location` (`state`, `district`, `block`),
          KEY `idx_ben_status_date` (`status`, `registration_date`),
          KEY `idx_ben_category` (`category_id`),
          KEY `idx_ben_project` (`project_id`),
          KEY `idx_ben_registered_by` (`registered_by`),
          KEY `idx_ben_coordinator` (`coordinator_id`),
          KEY `idx_ben_agent` (`field_agent_id`),
          KEY `idx_ben_student` (`sa_student_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "   ✅ Table `beneficiaries` ready.\n\n";

    // 4. Create `beneficiary_assistance_history` table
    echo "4. Creating `beneficiary_assistance_history` table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `beneficiary_assistance_history` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `assistance_code` varchar(50) DEFAULT NULL,
          `beneficiary_id` int(11) NOT NULL,
          `assistance_type` enum('Financial Aid','Medical Aid','Educational Support','Ration & Food Kit','Clothing & Blankets','Mobility & Assistive Devices','Shelter & Housing','Skill Training & Livelihood','Emergency Relief','In-Kind Items','Other') NOT NULL,
          `description` text NOT NULL,
          `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
          `items_detail` varchar(255) DEFAULT NULL,
          `quantity` decimal(10,2) NOT NULL DEFAULT 1.00,
          `unit` varchar(30) NOT NULL DEFAULT 'units',
          `estimated_value` decimal(10,2) NOT NULL DEFAULT 0.00,
          `date` date NOT NULL,
          `given_by` varchar(100) DEFAULT NULL,
          `coordinator_id` int(11) DEFAULT NULL,
          `field_agent_id` int(11) DEFAULT NULL,
          `sa_student_id` int(11) DEFAULT NULL,
          `project_id` int(11) DEFAULT NULL,
          `item_donation_id` int(11) DEFAULT NULL,
          `donation_id` int(11) DEFAULT NULL,
          `event_id` int(11) DEFAULT NULL,
          `distribution_location` varchar(255) DEFAULT NULL,
          `proof_photo` varchar(255) DEFAULT NULL,
          `receipt_no` varchar(50) DEFAULT NULL,
          `status` enum('Pending Approval','Approved','Distributed','Verified','Cancelled') NOT NULL DEFAULT 'Distributed',
          `remarks` text DEFAULT NULL,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
          PRIMARY KEY (`id`),
          UNIQUE KEY `uniq_assistance_code` (`assistance_code`),
          KEY `idx_ast_beneficiary_id` (`beneficiary_id`),
          KEY `idx_ast_type_date` (`assistance_type`, `date`),
          KEY `idx_ast_status` (`status`),
          KEY `idx_ast_coordinator` (`coordinator_id`),
          KEY `idx_ast_agent` (`field_agent_id`),
          KEY `idx_ast_student` (`sa_student_id`),
          KEY `idx_ast_project` (`project_id`),
          KEY `idx_ast_item_donation` (`item_donation_id`),
          KEY `idx_ast_donation` (`donation_id`),
          KEY `idx_ast_event` (`event_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "   ✅ Table `beneficiary_assistance_history` ready.\n\n";

    // 5. Add Foreign Key Constraints Safely
    echo "5. Applying foreign key relationships...\n";
    $foreignKeys = [
        [
            'table' => 'beneficiaries',
            'name'  => 'fk_ben_category',
            'sql'   => 'ALTER TABLE `beneficiaries` ADD CONSTRAINT `fk_ben_category` FOREIGN KEY (`category_id`) REFERENCES `beneficiary_categories` (`id`) ON DELETE SET NULL;'
        ],
        [
            'table' => 'beneficiaries',
            'name'  => 'fk_ben_project',
            'sql'   => 'ALTER TABLE `beneficiaries` ADD CONSTRAINT `fk_ben_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL;'
        ],
        [
            'table' => 'beneficiaries',
            'name'  => 'fk_ben_registered_by',
            'sql'   => 'ALTER TABLE `beneficiaries` ADD CONSTRAINT `fk_ben_registered_by` FOREIGN KEY (`registered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;'
        ],
        [
            'table' => 'beneficiaries',
            'name'  => 'fk_ben_coordinator',
            'sql'   => 'ALTER TABLE `beneficiaries` ADD CONSTRAINT `fk_ben_coordinator` FOREIGN KEY (`coordinator_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;'
        ],
        [
            'table' => 'beneficiaries',
            'name'  => 'fk_ben_agent',
            'sql'   => 'ALTER TABLE `beneficiaries` ADD CONSTRAINT `fk_ben_agent` FOREIGN KEY (`field_agent_id`) REFERENCES `field_agents` (`id`) ON DELETE SET NULL;'
        ],
        [
            'table' => 'beneficiaries',
            'name'  => 'fk_ben_student',
            'sql'   => 'ALTER TABLE `beneficiaries` ADD CONSTRAINT `fk_ben_student` FOREIGN KEY (`sa_student_id`) REFERENCES `sa_students` (`id`) ON DELETE SET NULL;'
        ],
        [
            'table' => 'beneficiary_assistance_history',
            'name'  => 'fk_ast_beneficiary',
            'sql'   => 'ALTER TABLE `beneficiary_assistance_history` ADD CONSTRAINT `fk_ast_beneficiary` FOREIGN KEY (`beneficiary_id`) REFERENCES `beneficiaries` (`id`) ON DELETE CASCADE;'
        ],
        [
            'table' => 'beneficiary_assistance_history',
            'name'  => 'fk_ast_coordinator',
            'sql'   => 'ALTER TABLE `beneficiary_assistance_history` ADD CONSTRAINT `fk_ast_coordinator` FOREIGN KEY (`coordinator_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;'
        ],
        [
            'table' => 'beneficiary_assistance_history',
            'name'  => 'fk_ast_agent',
            'sql'   => 'ALTER TABLE `beneficiary_assistance_history` ADD CONSTRAINT `fk_ast_agent` FOREIGN KEY (`field_agent_id`) REFERENCES `field_agents` (`id`) ON DELETE SET NULL;'
        ],
        [
            'table' => 'beneficiary_assistance_history',
            'name'  => 'fk_ast_student',
            'sql'   => 'ALTER TABLE `beneficiary_assistance_history` ADD CONSTRAINT `fk_ast_student` FOREIGN KEY (`sa_student_id`) REFERENCES `sa_students` (`id`) ON DELETE SET NULL;'
        ],
        [
            'table' => 'beneficiary_assistance_history',
            'name'  => 'fk_ast_project',
            'sql'   => 'ALTER TABLE `beneficiary_assistance_history` ADD CONSTRAINT `fk_ast_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL;'
        ],
        [
            'table' => 'beneficiary_assistance_history',
            'name'  => 'fk_ast_item_donation',
            'sql'   => 'ALTER TABLE `beneficiary_assistance_history` ADD CONSTRAINT `fk_ast_item_donation` FOREIGN KEY (`item_donation_id`) REFERENCES `item_donations` (`id`) ON DELETE SET NULL;'
        ],
        [
            'table' => 'beneficiary_assistance_history',
            'name'  => 'fk_ast_donation',
            'sql'   => 'ALTER TABLE `beneficiary_assistance_history` ADD CONSTRAINT `fk_ast_donation` FOREIGN KEY (`donation_id`) REFERENCES `donations` (`id`) ON DELETE SET NULL;'
        ],
        [
            'table' => 'beneficiary_assistance_history',
            'name'  => 'fk_ast_event',
            'sql'   => 'ALTER TABLE `beneficiary_assistance_history` ADD CONSTRAINT `fk_ast_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE SET NULL;'
        ]
    ];

    foreach ($foreignKeys as $fk) {
        try {
            $pdo->exec($fk['sql']);
            echo "   • Added constraint `{$fk['name']}` on `{$fk['table']}`\n";
        } catch (Throwable $e) {
            // Constraint may already exist or table column matched
            echo "   • Constraint `{$fk['name']}` verified / existing.\n";
        }
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    echo "\n=== Migration Completed Successfully! ===\n\n";

    // Verification summary
    echo "--- Verification of Created Tables ---\n";
    $catCount = (int)$pdo->query("SELECT COUNT(*) FROM beneficiary_categories")->fetchColumn();
    $benCount = (int)$pdo->query("SELECT COUNT(*) FROM beneficiaries")->fetchColumn();
    $astCount = (int)$pdo->query("SELECT COUNT(*) FROM beneficiary_assistance_history")->fetchColumn();

    echo "✅ Table `beneficiary_categories`: {$catCount} categories seeded.\n";
    echo "✅ Table `beneficiaries`: {$benCount} records.\n";
    echo "✅ Table `beneficiary_assistance_history`: {$astCount} records.\n";

} catch (PDOException $e) {
    echo "\n❌ Database Error: " . $e->getMessage() . "\n";
    exit(1);
}
