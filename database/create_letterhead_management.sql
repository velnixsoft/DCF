-- ============================================================
-- Database Migration: Letterhead Management System & Letters Tracker
-- Description: Creates `letters` table and initializes Letterhead Template Settings linked to CMS
-- Compatible with: MySQL 5.7+, MariaDB 10.3+
-- Author: VELNIX SOFT / Antigravity AI
-- Date: 2026-09-12
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- 1. Table structure for `letters`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `letters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reference_no` varchar(100) DEFAULT NULL,
  `letter_type` varchar(100) NOT NULL DEFAULT 'General Official Letter',
  `subject` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `recipient_name` varchar(255) NOT NULL,
  `recipient_designation` varchar(255) DEFAULT NULL,
  `recipient_organization` varchar(255) DEFAULT NULL,
  `recipient_address` text DEFAULT NULL,
  `recipient_email` varchar(150) DEFAULT NULL,
  `recipient_phone` varchar(50) DEFAULT NULL,
  `generated_date` date NOT NULL,
  `generated_by` int(11) DEFAULT NULL,
  `pdf_path` varchar(255) DEFAULT NULL,
  `status` enum('Draft','Generated','Sent','Archived') NOT NULL DEFAULT 'Generated',
  `signatory_name` varchar(150) DEFAULT NULL,
  `signatory_designation` varchar(150) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_letter_reference_no` (`reference_no`),
  KEY `idx_letters_type` (`letter_type`),
  KEY `idx_letters_generated_date` (`generated_date`),
  KEY `idx_letters_status` (`status`),
  KEY `idx_letters_generated_by` (`generated_by`),
  KEY `idx_letters_recipient_name` (`recipient_name`),
  CONSTRAINT `fk_letters_generated_by` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 2. Initialize / Seed Letterhead Template Settings in `settings` table
-- ------------------------------------------------------------
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('letterhead_org_name', ''),
('letterhead_reg_no', ''),
('letterhead_tagline', 'Empowering Communities • Transforming Lives • Sustainable Development'),
('letterhead_logo', ''),
('letterhead_address', ''),
('letterhead_phone', ''),
('letterhead_email', ''),
('letterhead_website', ''),
('letterhead_footer_text', 'Registered under Societies Registration Act | Donations Tax Exempted u/s 80G & 12A of Income Tax Act'),
('letterhead_header_color', '#0F8B8D'),
('letterhead_watermark_enabled', '1'),
('letterhead_signature_image', ''),
('letterhead_signatory_name', 'Authorized Signatory'),
('letterhead_signatory_designation', 'President / General Secretary')
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;

SET FOREIGN_KEY_CHECKS = 1;
