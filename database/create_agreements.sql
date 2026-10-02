-- ============================================================
-- Database Migration: Agreements & MoUs Management
-- Description: Creates table for bilateral MoUs, Authorization Letters,
--              and Service Agreements generated on Official NGO Letterhead.
-- Compatible with: MySQL 5.7+, MariaDB 10.3+
-- Author: VELNIX SOFT / Antigravity AI
-- Date: 2026-09-12
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `agreements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agreement_no` varchar(50) NOT NULL COMMENT 'Unique identifier e.g. MOU-2026-0001',
  `partner_name` varchar(200) NOT NULL COMMENT 'Second party / partner organization name',
  `partner_type` varchar(100) DEFAULT 'organization' COMMENT 'hospital, school, corporate, vendor, ngo, etc.',
  `partner_contact` varchar(50) DEFAULT NULL,
  `partner_email` varchar(150) DEFAULT NULL,
  `partner_address` text DEFAULT NULL,
  `type` enum('mou','authorization','service_agreement','partnership') NOT NULL DEFAULT 'mou',
  `title` varchar(255) NOT NULL,
  `content` longtext NOT NULL COMMENT 'Clauses and Agreement Body Text',
  `signed_status` enum('draft','pending_signature','signed','expired','terminated') NOT NULL DEFAULT 'draft',
  `signed_date` date DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `pdf_path` varchar(255) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `generated_date` datetime NOT NULL DEFAULT current_timestamp(),
  `first_party_name` varchar(150) DEFAULT NULL,
  `first_party_designation` varchar(100) DEFAULT NULL,
  `second_party_name` varchar(150) DEFAULT NULL,
  `second_party_designation` varchar(100) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_agr_no` (`agreement_no`),
  KEY `idx_agr_partner_name` (`partner_name`),
  KEY `idx_agr_type` (`type`),
  KEY `idx_agr_signed_status` (`signed_status`),
  KEY `idx_agr_signed_date` (`signed_date`),
  KEY `idx_agr_created_by` (`created_by`),
  KEY `idx_agr_created_at` (`created_at`),
  CONSTRAINT `fk_agr_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
