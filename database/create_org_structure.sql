-- ============================================================
-- database/create_org_structure.sql
-- Migration for Organization Structure & Hierarchy Tree
-- ============================================================

CREATE TABLE IF NOT EXISTS `org_structure` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `parent_id` INT DEFAULT NULL,
    `designation_id` INT DEFAULT NULL,
    `title` VARCHAR(150) NOT NULL,
    `department` VARCHAR(100) NOT NULL DEFAULT 'Executive Board',
    `holder_name` VARCHAR(150) DEFAULT NULL,
    `holder_designation` VARCHAR(150) DEFAULT NULL,
    `holder_photo` VARCHAR(255) DEFAULT NULL,
    `holder_phone` VARCHAR(30) DEFAULT NULL,
    `holder_email` VARCHAR(150) DEFAULT NULL,
    `management_body_id` INT DEFAULT NULL,
    `member_id` INT DEFAULT NULL,
    `level_tier` INT NOT NULL DEFAULT 1,
    `sort_order` INT NOT NULL DEFAULT 0,
    `badge_color` VARCHAR(30) NOT NULL DEFAULT 'teal',
    `responsibilities` TEXT DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_org_parent` (`parent_id`),
    INDEX `idx_org_dept` (`department`),
    INDEX `idx_org_level` (`level_tier`),
    INDEX `idx_org_status` (`is_active`),
    CONSTRAINT `fk_org_parent` FOREIGN KEY (`parent_id`) REFERENCES `org_structure`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_org_mgmt_body` FOREIGN KEY (`management_body_id`) REFERENCES `management_body`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_org_member_desig` FOREIGN KEY (`designation_id`) REFERENCES `member_designations`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Sample Hierarchy Tree if table is empty
INSERT INTO `org_structure` (`id`, `parent_id`, `designation_id`, `title`, `department`, `holder_name`, `holder_designation`, `holder_phone`, `holder_email`, `level_tier`, `sort_order`, `badge_color`, `responsibilities`, `is_active`)
SELECT 1, NULL, 1, 'National President & Chairman', 'Executive Board', 'Dr. Arvind Sharma', 'Founder & Chief Patron', '+91 98765 43210', 'president@ngocare.org', 1, 1, 'indigo', 'Overall strategic vision, constitutional governance, national partnerships and policy direction.', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `org_structure` WHERE `id` = 1);

INSERT INTO `org_structure` (`id`, `parent_id`, `designation_id`, `title`, `department`, `holder_name`, `holder_designation`, `holder_phone`, `holder_email`, `level_tier`, `sort_order`, `badge_color`, `responsibilities`, `is_active`)
SELECT 2, 1, 2, 'Vice President (Programs & Alliances)', 'Executive Board', 'Smt. Vandana Mishra', 'Vice Chairperson', '+91 98765 43211', 'vp@ngocare.org', 2, 1, 'blue', 'Supervision of health, skill development and rural education programs across India.', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `org_structure` WHERE `id` = 2);

INSERT INTO `org_structure` (`id`, `parent_id`, `designation_id`, `title`, `department`, `holder_name`, `holder_designation`, `holder_phone`, `holder_email`, `level_tier`, `sort_order`, `badge_color`, `responsibilities`, `is_active`)
SELECT 3, 1, 2, 'General Secretary & CEO', 'Secretariat & Administration', 'Rajesh K. Verma', 'General Secretary', '+91 98765 43212', 'secretary@ngocare.org', 2, 2, 'teal', 'Day-to-day NGO administration, state coordinator alignments, donor liaisons and institutional reporting.', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `org_structure` WHERE `id` = 3);

INSERT INTO `org_structure` (`id`, `parent_id`, `designation_id`, `title`, `department`, `holder_name`, `holder_designation`, `holder_phone`, `holder_email`, `level_tier`, `sort_order`, `badge_color`, `responsibilities`, `is_active`)
SELECT 4, 1, 2, 'National Treasurer & Finance Head', 'Finance & Audit', 'Pooja Agarwal (CA)', 'Treasurer', '+91 98765 43213', 'treasurer@ngocare.org', 2, 3, 'amber', 'Statutory compliance, annual audits, fund allocation, budgeting and 80G/12A regulatory filings.', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `org_structure` WHERE `id` = 4);

INSERT INTO `org_structure` (`id`, `parent_id`, `designation_id`, `title`, `department`, `holder_name`, `holder_designation`, `holder_phone`, `holder_email`, `level_tier`, `sort_order`, `badge_color`, `responsibilities`, `is_active`)
SELECT 5, 2, NULL, 'Director (Healthcare & Relief Services)', 'Health Directorate', 'Dr. S. K. Gupta (MD)', 'Medical Director', '+91 98765 43214', 'health@ngocare.org', 3, 1, 'emerald', 'Empanelment of hospitals/clinics, Swasthya Card schemes, blood donation drives and relief camps.', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `org_structure` WHERE `id` = 5);

INSERT INTO `org_structure` (`id`, `parent_id`, `designation_id`, `title`, `department`, `holder_name`, `holder_designation`, `holder_phone`, `holder_email`, `level_tier`, `sort_order`, `badge_color`, `responsibilities`, `is_active`)
SELECT 6, 2, NULL, 'Director (Skill Training & Career Guidance)', 'Youth & Education Wing', 'Er. Alok Trivedi', 'Training Director', '+91 98765 43215', 'skills@ngocare.org', 3, 2, 'purple', 'Vocational courses, youth computer labs, scholarship assessments, and placement counseling.', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `org_structure` WHERE `id` = 6);

INSERT INTO `org_structure` (`id`, `parent_id`, `designation_id`, `title`, `department`, `holder_name`, `holder_designation`, `holder_phone`, `holder_email`, `level_tier`, `sort_order`, `badge_color`, `responsibilities`, `is_active`)
SELECT 7, 3, NULL, 'State Program Coordinator (UP & Bihar)', 'State Operations', 'Manoj Tripathi', 'State Coordinator', '+91 98765 43216', 'state.up@ngocare.org', 3, 1, 'teal', 'Managing all District coordinators, field projects, government liaison, and district performance.', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `org_structure` WHERE `id` = 7);

INSERT INTO `org_structure` (`id`, `parent_id`, `designation_id`, `title`, `department`, `holder_name`, `holder_designation`, `holder_phone`, `holder_email`, `level_tier`, `sort_order`, `badge_color`, `responsibilities`, `is_active`)
SELECT 8, 7, NULL, 'District Operations Coordinator (Lucknow & Varanasi)', 'District Operations', 'Suresh Kumar Yadav', 'District Coordinator', '+91 98765 43217', 'dist.lucknow@ngocare.org', 4, 1, 'blue', 'District-level project execution, block coordinator management, and community beneficiary validation.', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `org_structure` WHERE `id` = 8);

INSERT INTO `org_structure` (`id`, `parent_id`, `designation_id`, `title`, `department`, `holder_name`, `holder_designation`, `holder_phone`, `holder_email`, `level_tier`, `sort_order`, `badge_color`, `responsibilities`, `is_active`)
SELECT 9, 8, NULL, 'Block Field Officer & Mobilizer', 'Block & Field Units', 'Anil Verma', 'Block Coordinator', '+91 98765 43218', 'block.bkt@ngocare.org', 5, 1, 'rose', 'Grassroots household surveys, health card enrolments, SHG meetings, and food/kit distribution.', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `org_structure` WHERE `id` = 9);

INSERT INTO `org_structure` (`id`, `parent_id`, `designation_id`, `title`, `department`, `holder_name`, `holder_designation`, `holder_phone`, `holder_email`, `level_tier`, `sort_order`, `badge_color`, `responsibilities`, `is_active`)
SELECT 10, 9, NULL, 'Gram Panchayat Community Volunteers', 'Village Volunteer Network', 'Village Volunteer Team', 'Panchayat Unit', '+91 98765 43219', 'volunteers@ngocare.org', 5, 2, 'teal', 'Direct doorstep support, emergency coordination and event logistics at village panchayat level.', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `org_structure` WHERE `id` = 10);
