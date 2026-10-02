-- ============================================================
-- Database Migration: Item / In-Kind Donation System
-- Description: Creates tables for Item Donation Categories and Item Donations
-- Compatible with: MySQL 5.7+, MariaDB 10.3+
-- Author: VELNIX SOFT
-- Date: 2026-09-12
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- 1. Table structure for `item_donation_categories`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `item_donation_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  `category_slug` varchar(100) NOT NULL,
  `category_icon` varchar(100) DEFAULT 'fa-box',
  `description` text DEFAULT NULL,
  `unit_suggestions` varchar(255) DEFAULT 'pcs, kg, boxes, sets, pairs, packets',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_category_name` (`category_name`),
  UNIQUE KEY `uniq_category_slug` (`category_slug`),
  KEY `idx_cat_active_order` (`is_active`, `display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 2. Seed default categories for `item_donation_categories`
-- ------------------------------------------------------------
INSERT INTO `item_donation_categories` (`id`, `category_name`, `category_slug`, `category_icon`, `description`, `unit_suggestions`, `is_active`, `display_order`) VALUES
(1, 'Clothes', 'clothes', 'fa-shirt', 'Men, women, and children clothing, winter wear, and daily apparel', 'pcs, pairs, boxes, sets', 1, 1),
(2, 'Ration', 'ration', 'fa-bowl-rice', 'Dry ration kits, rice, wheat, pulses, cooking oil, spices', 'kg, packets, kits, boxes', 1, 2),
(3, 'Books', 'books', 'fa-book-open', 'Educational books, school textbooks, notebooks, reference guides, storybooks', 'pcs, sets, boxes', 1, 3),
(4, 'Medicines', 'medicines', 'fa-pills', 'First-aid supplies, unexpired prescription & OTC medicines, medical disposables', 'boxes, strips, bottles, units', 1, 4),
(5, 'Stationery', 'stationery', 'fa-pen-ruler', 'Pens, pencils, notebooks, school bags, geometry boxes, art materials', 'pcs, sets, packets, boxes', 1, 5),
(6, 'Blankets', 'blankets', 'fa-bed', 'Warm winter blankets, quilts, bedsheets, woollen shawls', 'pcs, bundles', 1, 6),
(7, 'Wheelchairs', 'wheelchairs', 'fa-wheelchair', 'Wheelchairs, walking sticks, crutches, physical mobility & assistive aids', 'pcs, units', 1, 7),
(8, 'Food Materials', 'food-materials', 'fa-apple-whole', 'Prepared fresh meal packets, fruits, dry snacks, packaged drinking water', 'packets, boxes, kg, meals', 1, 8),
(9, 'Others', 'others', 'fa-box-open', 'Toys, electronics, appliances, furniture, and general utility items', 'pcs, units, sets', 1, 9)
ON DUPLICATE KEY UPDATE
  `category_icon` = VALUES(`category_icon`),
  `description` = VALUES(`description`),
  `unit_suggestions` = VALUES(`unit_suggestions`),
  `display_order` = VALUES(`display_order`);

-- ------------------------------------------------------------
-- 3. Table structure for `item_donations`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `item_donations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `donation_code` varchar(50) DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `donor_id` int(11) DEFAULT NULL,
  `donor_name` varchar(100) NOT NULL,
  `donor_email` varchar(100) NOT NULL,
  `donor_mobile` varchar(20) NOT NULL,
  `donor_pan` varchar(20) DEFAULT NULL,
  `donor_address` text DEFAULT NULL,
  `pickup_city` varchar(100) DEFAULT NULL,
  `pickup_pincode` varchar(10) DEFAULT NULL,
  `pickup_address` text DEFAULT NULL,
  `item_description` text NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 1.00,
  `unit` varchar(30) NOT NULL DEFAULT 'pcs',
  `estimated_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `condition_type` enum('New','Gently Used','Refurbished','Usable') NOT NULL DEFAULT 'New',
  `donation_date` date NOT NULL,
  `status` enum('Pledged','Scheduled For Pickup','Collected','In Warehouse','Distributed','Cancelled') NOT NULL DEFAULT 'Pledged',
  `project_id` int(11) DEFAULT NULL,
  `receipt_no` varchar(50) DEFAULT NULL,
  `field_agent_id` int(11) DEFAULT NULL,
  `sa_student_id` int(11) DEFAULT NULL,
  `referral_code` varchar(40) DEFAULT NULL,
  `item_photo` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_item_donation_code` (`donation_code`),
  KEY `idx_item_category` (`category_id`),
  KEY `idx_item_donor_id` (`donor_id`),
  KEY `idx_item_donor_email` (`donor_email`),
  KEY `idx_item_donor_mobile` (`donor_mobile`),
  KEY `idx_item_status_date` (`status`, `donation_date`),
  KEY `idx_item_project` (`project_id`),
  KEY `idx_item_agent` (`field_agent_id`),
  KEY `idx_item_student` (`sa_student_id`),
  CONSTRAINT `fk_item_category` FOREIGN KEY (`category_id`) REFERENCES `item_donation_categories` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_item_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_item_agent` FOREIGN KEY (`field_agent_id`) REFERENCES `field_agents` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_item_student` FOREIGN KEY (`sa_student_id`) REFERENCES `sa_students` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
