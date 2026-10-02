-- ============================================================
-- Database Migration: Custom Receipt Management System
-- Description: Creates `custom_receipts` table for offline/custom donation & payment receipts
-- Compatible with: MySQL 5.7+, MariaDB 10.3+
-- Author: VELNIX SOFT / Antigravity AI
-- Date: 2026-09-12
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- Table structure for `custom_receipts`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `custom_receipts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `receipt_no` varchar(60) NOT NULL,
  `payer_name` varchar(255) NOT NULL,
  `payer_phone` varchar(50) DEFAULT NULL,
  `payer_email` varchar(150) DEFAULT NULL,
  `payer_pan` varchar(20) DEFAULT NULL,
  `payer_address` text DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `amount_in_words` varchar(255) DEFAULT NULL,
  `date` date NOT NULL,
  `purpose` varchar(255) NOT NULL DEFAULT 'Donation / Contribution',
  `payment_mode` varchar(50) NOT NULL DEFAULT 'Cash',
  `transaction_ref` varchar(100) DEFAULT NULL,
  `generated_by` int(11) DEFAULT NULL,
  `pdf_path` varchar(255) DEFAULT NULL,
  `status` enum('generated','cancelled','draft') NOT NULL DEFAULT 'generated',
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_receipt_no` (`receipt_no`),
  KEY `idx_payer_name` (`payer_name`),
  KEY `idx_receipt_date` (`date`),
  KEY `idx_payment_mode` (`payment_mode`),
  KEY `idx_generated_by` (`generated_by`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_custom_receipts_generated_by` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
