-- ============================================================
-- Database Migration: Health Card Management System
-- Description: Creates table `health_cards` for NGO Beneficiary / Citizen Healthcare Cards
-- Compatible with: MySQL 5.7+, MariaDB 10.3+
-- Author: VELNIX SOFT / Antigravity AI
-- Date: 2026-09-12
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- 1. Table structure for `health_cards`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `health_cards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `card_number` varchar(50) NOT NULL,
  `applicant_name` varchar(150) NOT NULL,
  `contact` varchar(50) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `gender` enum('Male','Female','Other') NOT NULL DEFAULT 'Male',
  `blood_group` varchar(10) DEFAULT NULL,
  `aadhaar_no` varchar(20) DEFAULT NULL,
  `emergency_contact` varchar(50) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `block` varchar(100) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `address` text NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `issue_date` date NOT NULL,
  `expiry_date` date NOT NULL,
  `status` enum('active','expired','renewed','blocked') NOT NULL DEFAULT 'active',
  `beneficiary_id` int(11) DEFAULT NULL,
  `member_id` int(11) DEFAULT NULL,
  `qr_code_path` varchar(255) DEFAULT NULL,
  `pdf_path` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `issued_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_health_card_number` (`card_number`),
  KEY `idx_hc_applicant_name` (`applicant_name`),
  KEY `idx_hc_contact` (`contact`),
  KEY `idx_hc_status` (`status`),
  KEY `idx_hc_issue_date` (`issue_date`),
  KEY `idx_hc_expiry_date` (`expiry_date`),
  KEY `idx_hc_state` (`state`),
  KEY `idx_hc_district` (`district`),
  KEY `idx_hc_beneficiary_id` (`beneficiary_id`),
  KEY `idx_hc_issued_by` (`issued_by`),
  CONSTRAINT `fk_health_cards_issued_by` FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_health_cards_beneficiary` FOREIGN KEY (`beneficiary_id`) REFERENCES `beneficiaries` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 2. Seed Initial Sample Health Cards
-- ------------------------------------------------------------
INSERT INTO `health_cards` (
  `card_number`, `applicant_name`, `contact`, `email`, `dob`, `age`, 
  `gender`, `blood_group`, `aadhaar_no`, `emergency_contact`, `state`, 
  `district`, `block`, `pincode`, `address`, `issue_date`, `expiry_date`, 
  `status`, `remarks`
) VALUES
(
  'HC-2026-0001', 'Ramesh Kumar Verma', '+91 98765 11223', 'ramesh.verma@example.com', '1982-05-14', 44,
  'Male', 'B+', 'XXXX-XXXX-4512', '+91 98765 11224', 'Uttar Pradesh',
  'Lucknow', 'Hazratganj', '226001', 'House No. 45, Sector 4, Vikas Nagar',
  '2026-01-10', '2027-01-09', 'active', 'Eligible for 100% Free Cataract Surgery and OPD Subsidies.'
),
(
  'HC-2026-0002', 'Sunita Devi Sharma', '+91 98111 55667', 'sunita.sharma@example.com', '1990-08-22', 36,
  'Female', 'O+', 'XXXX-XXXX-8921', '+91 98111 55668', 'Delhi',
  'New Delhi', 'Connaught Place', '110001', 'Flat 12B, Barakhamba Lane',
  '2026-02-15', '2027-02-14', 'active', 'BPL Card Holder - Free generic medicines from partner Jan Aushadhi pharmacy.'
),
(
  'HC-2026-0003', 'Mohammad Imran Sheikh', '+91 99222 77889', 'imran.sheikh@example.com', '1975-11-03', 51,
  'Male', 'AB+', 'XXXX-XXXX-3341', '+91 99222 77890', 'Maharashtra',
  'Mumbai Suburban', 'Andheri East', '400069', 'Plot 88, Metro Nagar, Kurla Road',
  '2025-01-01', '2026-01-01', 'expired', 'Card expired. Renewal application pending.'
)
ON DUPLICATE KEY UPDATE
  `applicant_name` = VALUES(`applicant_name`),
  `contact` = VALUES(`contact`),
  `status` = VALUES(`status`),
  `expiry_date` = VALUES(`expiry_date`);

SET FOREIGN_KEY_CHECKS = 1;
