-- ============================================================
-- Database Migration: Recurring Donations / Auto Pay System
-- Description: Creates tables for Recurring Donations and Subscriptions
-- Compatible with: MySQL 5.7+, MariaDB 10.3+
-- Author: VELNIX SOFT
-- Date: 2026-09-12
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Table structure for `recurring_donations`
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
  `billing_cycle_count` int(11) NOT NULL DEFAULT 0,
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
  KEY `idx_rec_field_agent` (`field_agent_id`),
  CONSTRAINT `fk_rec_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_rec_student` FOREIGN KEY (`sa_student_id`) REFERENCES `sa_students` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_rec_agent` FOREIGN KEY (`field_agent_id`) REFERENCES `field_agents` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Table structure for `recurring_donation_transactions`
CREATE TABLE IF NOT EXISTS `recurring_donation_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recurring_donation_id` int(11) NOT NULL,
  `donation_id` int(11) DEFAULT NULL,
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
  KEY `idx_rec_tx_subscription_id` (`razorpay_subscription_id`),
  CONSTRAINT `fk_rec_tx_parent` FOREIGN KEY (`recurring_donation_id`) REFERENCES `recurring_donations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rec_tx_donation` FOREIGN KEY (`donation_id`) REFERENCES `donations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
