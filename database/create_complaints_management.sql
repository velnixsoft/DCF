-- ============================================================
-- Database Migration: Complaints & Suggestions Management System
-- Description: Creates table for Public / Member / Beneficiary Complaints & Suggestions
-- Compatible with: MySQL 5.7+, MariaDB 10.3+
-- Author: VELNIX SOFT / Antigravity AI
-- Date: 2026-09-12
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- 1. Table structure for `complaints`
-- ------------------------------------------------------------
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
  KEY `idx_complaints_reply_by` (`reply_by`),
  CONSTRAINT `fk_complaints_reply_by` FOREIGN KEY (`reply_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
