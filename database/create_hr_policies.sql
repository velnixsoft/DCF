-- ============================================================
-- database/create_hr_policies.sql
-- Migration for HR Policies & Workplace Governance
-- ============================================================

CREATE TABLE IF NOT EXISTS `hr_policies` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `policy_code` VARCHAR(50) NOT NULL UNIQUE,
    `title` VARCHAR(255) NOT NULL,
    `category` ENUM('code_of_conduct', 'posh_gender', 'child_safeguarding', 'leave_benefits', 'whistleblower', 'travel_compensation', 'volunteer_ethics', 'general') NOT NULL DEFAULT 'code_of_conduct',
    `category_custom` VARCHAR(100) DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `content_html` LONGTEXT DEFAULT NULL,
    `file_path` VARCHAR(255) DEFAULT NULL,
    `file_size` VARCHAR(50) DEFAULT NULL,
    `policy_version` VARCHAR(20) DEFAULT 'v1.0',
    `effective_date` DATE DEFAULT NULL,
    `review_date` DATE DEFAULT NULL,
    `is_public` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_hr_category` (`category`),
    INDEX `idx_hr_public` (`is_public`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Standard Sample HR Policies if empty
INSERT INTO `hr_policies` (`policy_code`, `title`, `category`, `description`, `policy_version`, `effective_date`, `is_public`, `sort_order`, `file_path`, `file_size`)
SELECT 'HRP-COC-01', 'Code of Conduct & Workplace Professional Ethics', 'code_of_conduct',
       'Establishes standards of honesty, integrity, gender sensitivity, conflict of interest, anti-discrimination, and respectful behavior for all NGO employees, interns, and field associates.',
       'v2.1', '2026-01-01', 1, 1, 'uploads/documents/sample_code_of_conduct.pdf', '1.2 MB'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `hr_policies` WHERE `policy_code` = 'HRP-COC-01');

INSERT INTO `hr_policies` (`policy_code`, `title`, `category`, `description`, `policy_version`, `effective_date`, `is_public`, `sort_order`, `file_path`, `file_size`)
SELECT 'HRP-POSH-02', 'Prevention of Sexual Harassment (POSH) & Gender Safety Policy', 'posh_gender',
       'Mandated by the POSH Act 2013; provides zero-tolerance framework against harassment, Internal Complaints Committee (ICC) redressal mechanisms, and confidential reporting channels for all female staff and volunteers.',
       'v2.0', '2026-01-15', 1, 2, 'uploads/documents/sample_posh_policy.pdf', '980 KB'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `hr_policies` WHERE `policy_code` = 'HRP-POSH-02');

INSERT INTO `hr_policies` (`policy_code`, `title`, `category`, `description`, `policy_version`, `effective_date`, `is_public`, `sort_order`, `file_path`, `file_size`)
SELECT 'HRP-CSG-03', 'Child Protection & Safeguarding (PSEA) Framework', 'child_safeguarding',
       'Rigorous protocols for safeguarding vulnerable children, beneficiaries, and adolescent students during field operations, medical camps, and education programs against abuse and exploitation.',
       'v1.8', '2026-02-01', 1, 3, 'uploads/documents/sample_child_safeguarding.pdf', '1.5 MB'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `hr_policies` WHERE `policy_code` = 'HRP-CSG-03');

INSERT INTO `hr_policies` (`policy_code`, `title`, `category`, `description`, `policy_version`, `effective_date`, `is_public`, `sort_order`, `file_path`, `file_size`)
SELECT 'HRP-LEV-04', 'Staff Leave, Working Hours, Health & Social Benefits Policy', 'leave_benefits',
       'Governs casual leave, earned leave, maternity/paternity support, medical benefits, Swasthya Card coverage, provident fund compliance, and remote field work allowances.',
       'v2.0', '2026-01-01', 1, 4, 'uploads/documents/sample_leave_policy.pdf', '850 KB'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `hr_policies` WHERE `policy_code` = 'HRP-LEV-04');

INSERT INTO `hr_policies` (`policy_code`, `title`, `category`, `description`, `policy_version`, `effective_date`, `is_public`, `sort_order`, `file_path`, `file_size`)
SELECT 'HRP-WB-05', 'Whistleblower, Anti-Bribery & Fraud Reporting Policy', 'whistleblower',
       'Encourages employees, donors, and stakeholders to confidentially report financial fraud, corruption, or procedural non-compliance without fear of retaliation or victimization.',
       'v1.2', '2026-03-01', 1, 5, 'uploads/documents/sample_whistleblower.pdf', '720 KB'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `hr_policies` WHERE `policy_code` = 'HRP-WB-05');

INSERT INTO `hr_policies` (`policy_code`, `title`, `category`, `description`, `policy_version`, `effective_date`, `is_public`, `sort_order`, `file_path`, `file_size`)
SELECT 'HRP-TRV-06', 'Field Travel Allowance & Expense Reimbursement Norms', 'travel_compensation',
       'Sets transparent per diem rates, travel booking guidelines, fuel reimbursements for field coordinators, and audit requirements for grassroots tours.',
       'v1.1', '2026-02-15', 1, 6, 'uploads/documents/sample_travel_policy.pdf', '640 KB'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `hr_policies` WHERE `policy_code` = 'HRP-TRV-06');
