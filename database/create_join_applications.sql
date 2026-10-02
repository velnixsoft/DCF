-- ============================================================
-- database/create_join_applications.sql
-- Unified Join Foundation, Join Project, and Job Applications Table
-- Author: VELNIX SOFT / Antigravity AI
-- Date: 2026-09-12
-- ============================================================

CREATE TABLE IF NOT EXISTS `join_applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_no` varchar(60) NOT NULL,
  `applicant_name` varchar(150) NOT NULL,
  `contact` varchar(50) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `application_type` enum('join_foundation','join_project','job_application') NOT NULL DEFAULT 'join_foundation',
  `project_id` int(11) DEFAULT NULL,
  `job_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `fee_amount` decimal(10,2) DEFAULT NULL,
  `payment_status` enum('pending','paid','exempted','failed','refunded') NOT NULL DEFAULT 'exempted',
  `razorpay_order_id` varchar(100) DEFAULT NULL,
  `razorpay_payment_id` varchar(100) DEFAULT NULL,
  `razorpay_signature` varchar(255) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `status` enum('pending','reviewed','approved','rejected','onboarded') NOT NULL DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `applied_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_join_app_no` (`application_no`),
  KEY `idx_join_app_type` (`application_type`),
  KEY `idx_join_project_id` (`project_id`),
  KEY `idx_join_job_id` (`job_id`),
  KEY `idx_join_payment_status` (`payment_status`),
  KEY `idx_join_status` (`status`),
  KEY `idx_join_contact` (`contact`),
  KEY `idx_join_email` (`email`),
  KEY `idx_join_applied_date` (`applied_date`),
  CONSTRAINT `fk_join_app_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_join_app_job` FOREIGN KEY (`job_id`) REFERENCES `job_openings` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_join_app_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
