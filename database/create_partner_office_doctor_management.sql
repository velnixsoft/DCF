-- ============================================================
-- Database Migration: Partner Office & Doctor Management
-- Description: Creates tables for Doctor Agreements and Staff/Volunteer Letters
-- Compatible with: MySQL 5.7+, MariaDB 10.3+
-- Author: VELNIX SOFT / Antigravity AI
-- Date: 2026-09-12
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- 1. Table structure for `doctor_agreements`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `doctor_agreements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agreement_no` varchar(50) NOT NULL,
  `partner_id` int(11) NOT NULL COMMENT 'Links to healthcare_providers.id',
  `agreement_title` varchar(255) NOT NULL DEFAULT 'Partner Doctor / Healthcare Empanelment Agreement',
  `doctor_name` varchar(150) DEFAULT NULL,
  `speciality` varchar(100) DEFAULT NULL,
  `discount_terms` varchar(255) DEFAULT NULL,
  `agreement_doc_path` varchar(255) DEFAULT NULL,
  `file_size` varchar(50) DEFAULT NULL,
  `signed_date` date DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `status` enum('active','pending_signature','under_renewal','expired','terminated') NOT NULL DEFAULT 'active',
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_doctor_agreement_no` (`agreement_no`),
  KEY `idx_doc_agr_partner` (`partner_id`),
  KEY `idx_doc_agr_status` (`status`),
  KEY `idx_doc_agr_signed_date` (`signed_date`),
  KEY `idx_doc_agr_valid_until` (`valid_until`),
  KEY `idx_doc_agr_created_by` (`created_by`),
  KEY `idx_doc_agr_created_at` (`created_at`),
  CONSTRAINT `fk_doc_agr_partner` FOREIGN KEY (`partner_id`) REFERENCES `healthcare_providers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_doc_agr_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 2. Table structure for `staff_letters`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `staff_letters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `letter_no` varchar(50) NOT NULL,
  `type` enum('joining','offer','appointment','volunteer_joining','experience','relieving','appreciation','other') NOT NULL DEFAULT 'joining',
  `name` varchar(150) NOT NULL,
  `contact` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `designation` varchar(150) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `issued_date` date NOT NULL,
  `joining_date` date DEFAULT NULL,
  `salary_or_stipend` varchar(100) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `letter_content` longtext NOT NULL,
  `pdf_path` varchar(255) DEFAULT NULL,
  `file_size` varchar(50) DEFAULT NULL,
  `signatory_name` varchar(120) DEFAULT NULL,
  `signatory_designation` varchar(120) DEFAULT NULL,
  `status` enum('draft','issued','accepted','signed','cancelled') NOT NULL DEFAULT 'issued',
  `issued_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_staff_letter_no` (`letter_no`),
  KEY `idx_staff_letter_type` (`type`),
  KEY `idx_staff_letter_status` (`status`),
  KEY `idx_staff_letter_name` (`name`),
  KEY `idx_staff_letter_designation` (`designation`),
  KEY `idx_staff_letter_issued_date` (`issued_date`),
  KEY `idx_staff_letter_issued_by` (`issued_by`),
  KEY `idx_staff_letter_created_at` (`created_at`),
  CONSTRAINT `fk_staff_letter_issued_by` FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 3. Seed Sample Records for Doctor Agreements
-- ------------------------------------------------------------
INSERT INTO `doctor_agreements` (
  `agreement_no`, `partner_id`, `agreement_title`, `doctor_name`, `speciality`, `discount_terms`, 
  `agreement_doc_path`, `file_size`, `signed_date`, `valid_until`, `status`, `remarks`
) 
SELECT 
  'AGR-DR-2026-001', id, 'Annual Doctor Empanelment & Free Consultation MOU', name, speciality,
  '25% Discount on OPD & 100% Free Consultation for Verified Health Card Holders',
  'uploads/documents/doctor_agreement_01.pdf', '320 KB', '2026-01-15', '2027-01-14', 'active',
  'Approved under Jaysmrutti Swasthya Suraksha Scheme.'
FROM `healthcare_providers` 
ORDER BY id ASC LIMIT 1;

-- ------------------------------------------------------------
-- 4. Seed Sample Records for Staff & Volunteer Letters
-- ------------------------------------------------------------
INSERT INTO `staff_letters` (
  `letter_no`, `type`, `name`, `contact`, `email`, `designation`, `department`, `issued_date`, `joining_date`, `salary_or_stipend`, `subject`, `letter_content`, `pdf_path`, `file_size`, `signatory_name`, `signatory_designation`, `status`
) VALUES
(
  'LET-OFF-2026-001', 'offer', 'Amitabh Sengupta', '+91 9876501234', 'amitabh.sengupta@example.com', 'State Program Coordinator', 'Operations & Field Strategy', '2026-02-01', '2026-02-15', '₹35,000 / month',
  'Offer of Employment: State Program Coordinator',
  '<p>Dear <strong>Amitabh Sengupta</strong>,</p><p>We are pleased to offer you the position of <strong>State Program Coordinator</strong> at Jaysmrutti Foundation. Your leadership will guide our multi-district healthcare and community development initiatives.</p><p><strong>Reporting Date:</strong> 15 February 2026<br><strong>Location:</strong> Lucknow State Headquarters</p>',
  'uploads/documents/offer_letter_amitabh.pdf', '215 KB', 'National General Secretary', 'Executive Committee', 'issued'
),
(
  'LET-APP-2026-002', 'appointment', 'Sunita Kushwaha', '+91 9786543210', 'sunita.kushwaha@example.com', 'District Operations Lead', 'District Healthcare Wing', '2026-02-10', '2026-02-16', '₹28,000 / month',
  'Official Appointment Letter: District Operations Lead',
  '<p>Dear <strong>Sunita Kushwaha</strong>,</p><p>Consequent to your interview and acceptance of our offer, we are pleased to appoint you as <strong>District Operations Lead</strong> with immediate effect.</p>',
  'uploads/documents/appointment_sunita.pdf', '240 KB', 'National President', 'Jaysmrutti Foundation', 'accepted'
),
(
  'LET-VOL-2026-003', 'volunteer_joining', 'Kavita Mishra', '+91 9123456780', 'kavita.volunteer@example.com', 'Youth & Field Mobilization Volunteer', 'Community Volunteers Wing', '2026-03-01', '2026-03-05', 'Honorary / Voluntary',
  'Volunteer Joining & Welcome Certificate Letter',
  '<p>Dear <strong>Kavita Mishra</strong>,</p><p>Welcome to Jaysmrutti Foundation. We officially acknowledge your joining as a <strong>Youth & Field Mobilization Volunteer</strong>. Thank you for your dedication towards societal welfare.</p>',
  'uploads/documents/volunteer_joining_kavita.pdf', '180 KB', 'Volunteer Coordinator', 'Community Wing', 'issued'
);

SET FOREIGN_KEY_CHECKS = 1;
