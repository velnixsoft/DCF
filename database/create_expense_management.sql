-- ============================================================
-- Database Migration: Expense Management System
-- Description: Creates tables for Expense Categories and Expenses Tracker
-- Linked to existing Projects and Users tables
-- Compatible with: MySQL 5.7+, MariaDB 10.3+
-- Author: VELNIX SOFT / Antigravity AI
-- Date: 2026-09-12
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- 1. Table structure for `expense_categories`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `expense_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  `category_slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(100) DEFAULT 'fa-receipt',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_expense_cat_name` (`category_name`),
  UNIQUE KEY `uniq_expense_cat_slug` (`category_slug`),
  KEY `idx_expense_cat_active_order` (`is_active`, `display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 2. Seed default categories for `expense_categories`
-- Categories: Travel, Event, Utility, Staff, Office, Project, Other
-- ------------------------------------------------------------
INSERT INTO `expense_categories` (`id`, `category_name`, `category_slug`, `description`, `icon`, `is_active`, `display_order`) VALUES
(1, 'Travel', 'travel', 'Field visit transport, fuel, vehicle rent, conveyance, and travel allowances', 'fa-car-side', 1, 1),
(2, 'Event', 'event', 'Community awareness drives, relief camps, stage setup, and event logistics', 'fa-calendar-check', 1, 2),
(3, 'Utility', 'utility', 'Electricity, water, high-speed internet, telephone, and recurring utility bills', 'fa-bolt', 1, 3),
(4, 'Staff', 'staff', 'Staff honorarium, coordinator stipends, volunteer welfare, and refreshments', 'fa-users-gear', 1, 4),
(5, 'Office', 'office', 'Office stationery, printing, rent, software subscriptions, and maintenance', 'fa-building', 1, 5),
(6, 'Project', 'project', 'Direct welfare material purchases, aid procurement, and project execution costs', 'fa-seedling', 1, 6),
(7, 'Other', 'other', 'Miscellaneous operational costs, bank charges, legal fees, and sundry expenses', 'fa-layer-group', 1, 7)
ON DUPLICATE KEY UPDATE
  `description` = VALUES(`description`),
  `icon` = VALUES(`icon`),
  `display_order` = VALUES(`display_order`);

-- ------------------------------------------------------------
-- 3. Table structure for `expenses`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `expense_code` varchar(50) DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `date` date NOT NULL,
  `purpose` text NOT NULL,
  `payment_mode` enum('Cash','Bank Transfer','UPI','Cheque','Credit/Debit Card','Other') NOT NULL DEFAULT 'Cash',
  `reference_no` varchar(100) DEFAULT NULL,
  `vendor_payee_name` varchar(150) DEFAULT NULL,
  `bill_document_path` varchar(255) DEFAULT NULL,
  `added_by` int(11) NOT NULL,
  `approved_status` enum('Pending','Approved','Rejected','Paid') NOT NULL DEFAULT 'Pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_expense_code` (`expense_code`),
  KEY `idx_expenses_category` (`category_id`),
  KEY `idx_expenses_project` (`project_id`),
  KEY `idx_expenses_date` (`date`),
  KEY `idx_expenses_status_date` (`approved_status`, `date`),
  KEY `idx_expenses_added_by` (`added_by`),
  KEY `idx_expenses_approved_by` (`approved_by`),
  CONSTRAINT `fk_expenses_category` FOREIGN KEY (`category_id`) REFERENCES `expense_categories` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_expenses_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_expenses_added_by` FOREIGN KEY (`added_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_expenses_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
