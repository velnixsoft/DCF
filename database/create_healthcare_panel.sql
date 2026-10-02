-- ============================================================
-- Database Migration: Healthcare Panel & Healthcare Providers System
-- Description: Creates tables for Healthcare Providers (Hospitals, Clinics, Pathology Labs, Pharmacies, Doctors),
--              Healthcare Services/Packages, and Patient Referrals/Appointments.
-- Compatible with: MySQL 5.7+, MariaDB 10.3+
-- Author: VELNIX SOFT / Antigravity AI
-- Date: 2026-09-12
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- 1. Table structure for `healthcare_providers`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `healthcare_providers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `provider_code` varchar(50) NOT NULL,
  `name` varchar(200) NOT NULL,
  `type` enum('hospital','clinic','pathology_lab','pharmacy','doctor') NOT NULL DEFAULT 'hospital',
  `speciality` enum('eye','dental','other') NOT NULL DEFAULT 'other',
  `speciality_custom` varchar(150) DEFAULT NULL,
  `contact_person` varchar(120) DEFAULT NULL,
  `contact` varchar(50) NOT NULL,
  `alternate_contact` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `state` varchar(100) NOT NULL,
  `district` varchar(100) NOT NULL,
  `block` varchar(100) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `address` text NOT NULL,
  `landmark` varchar(255) DEFAULT NULL,
  `map_location` varchar(500) DEFAULT NULL COMMENT 'Google Maps URL or Link',
  `latitude` decimal(10,8) DEFAULT NULL COMMENT 'Latitude coordinate',
  `longitude` decimal(11,8) DEFAULT NULL COMMENT 'Longitude coordinate',
  `map_embed_url` text DEFAULT NULL COMMENT 'Map Embed Iframe URL',
  `photo` varchar(255) DEFAULT NULL,
  `timing` varchar(255) DEFAULT NULL,
  `emergency_available` tinyint(1) NOT NULL DEFAULT 0,
  `discount_offered` varchar(255) DEFAULT NULL,
  `services_offered` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `status` enum('active','inactive','pending_approval') NOT NULL DEFAULT 'active',
  `is_verified` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_hcp_code` (`provider_code`),
  KEY `idx_hcp_type` (`type`),
  KEY `idx_hcp_speciality` (`speciality`),
  KEY `idx_hcp_state` (`state`),
  KEY `idx_hcp_district` (`district`),
  KEY `idx_hcp_block` (`block`),
  KEY `idx_hcp_status` (`status`),
  KEY `idx_hcp_contact` (`contact`),
  KEY `idx_hcp_created_by` (`created_by`),
  KEY `idx_hcp_created_at` (`created_at`),
  CONSTRAINT `fk_hcp_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 2. Table structure for `healthcare_services` (Optional/Supporting)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `healthcare_services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `provider_id` int(11) NOT NULL,
  `service_name` varchar(200) NOT NULL,
  `category` varchar(100) NOT NULL DEFAULT 'Consultation',
  `standard_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discounted_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_free_for_bpl` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_hcs_provider_id` (`provider_id`),
  KEY `idx_hcs_category` (`category`),
  KEY `idx_hcs_status` (`status`),
  CONSTRAINT `fk_hcs_provider` FOREIGN KEY (`provider_id`) REFERENCES `healthcare_providers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 3. Table structure for `healthcare_referrals` (Patient / Beneficiary Appointments)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `healthcare_referrals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `referral_no` varchar(50) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `beneficiary_id` int(11) DEFAULT NULL,
  `patient_name` varchar(150) NOT NULL,
  `patient_contact` varchar(50) NOT NULL,
  `patient_age` int(11) DEFAULT NULL,
  `patient_gender` enum('Male','Female','Other') DEFAULT NULL,
  `problem_description` text DEFAULT NULL,
  `appointment_date` date NOT NULL,
  `status` enum('scheduled','completed','cancelled','no_show') NOT NULL DEFAULT 'scheduled',
  `doctor_remarks` text DEFAULT NULL,
  `discount_availed` decimal(10,2) DEFAULT 0.00,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_hcr_referral_no` (`referral_no`),
  KEY `idx_hcr_provider_id` (`provider_id`),
  KEY `idx_hcr_beneficiary_id` (`beneficiary_id`),
  KEY `idx_hcr_appointment_date` (`appointment_date`),
  KEY `idx_hcr_status` (`status`),
  KEY `idx_hcr_created_by` (`created_by`),
  CONSTRAINT `fk_hcr_provider` FOREIGN KEY (`provider_id`) REFERENCES `healthcare_providers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_hcr_beneficiary` FOREIGN KEY (`beneficiary_id`) REFERENCES `beneficiaries` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_hcr_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4. Seed Initial Sample Healthcare Providers
-- ------------------------------------------------------------
INSERT INTO `healthcare_providers` (
  `provider_code`, `name`, `type`, `speciality`, `speciality_custom`, `contact_person`, 
  `contact`, `alternate_contact`, `email`, `state`, `district`, `block`, 
  `pincode`, `address`, `landmark`, `timing`, `emergency_available`, 
  `discount_offered`, `services_offered`, `remarks`, `status`, `is_verified`
) VALUES
(
  'HCP-2026-0001', 'Drishti Eye & Retina Super Speciality Hospital', 'hospital', 'eye', 'Cataract & Retina Care', 'Dr. Alok Verma (MS Ophthal)',
  '+91 98765 43210', '+91 98765 43211', 'info@drishtieye.org', 'Delhi', 'New Delhi', 'Connaught Place',
  '110001', 'Plot 14, Health Park, Barakhamba Road', 'Near Metro Gate No 3', 'Mon - Sat: 08:30 AM - 07:30 PM', 1,
  '100% Free Cataract Surgery for BPL Card Holders & 30% Off for Senior Citizens',
  'Free Eye Checkup Camps, Phacoemulsification, Glaucoma screening, Diabetic Retinopathy, Spectacle distribution',
  'Official partner hospital for NGO rural eye care missions.', 'active', 1
),
(
  'HCP-2026-0002', 'SmileCare Advanced Dental Clinic & Implant Center', 'clinic', 'dental', 'Orthodontics & Implants', 'Dr. Neha Sharma (BDS, MDS)',
  '+91 98111 22334', '+91 98111 22335', 'care@smilecaredental.com', 'Uttar Pradesh', 'Lucknow', 'Hazratganj',
  '226001', 'Shop 5-6, City Centre Mall, MG Marg', 'Opposite Gandhi Ashram', 'Mon - Sat: 10:00 AM - 08:00 PM', 0,
  'Free Dental Consultation & 50% discount on Root Canal (RCT) & Scaling',
  'Dental Scaling, Root Canal Treatment, Tooth Extractions, Pediatric Dental Care, Dentures',
  'Equipped with modern digital RVG X-ray unit.', 'active', 1
),
(
  'HCP-2026-0003', 'Apex Diagnostics & Pathology Center', 'pathology_lab', 'other', 'Advanced Diagnostic & Biochemistry', 'Dr. Rajesh Gupta (MD Path)',
  '+91 99222 33445', '+91 99222 33446', 'lab@apexdiagnostics.in', 'Maharashtra', 'Mumbai Suburban', 'Andheri East',
  '400069', 'Building 2, Metro Plaza, Andheri-Kurla Road', 'Beside Western Express Highway Metro', '24x7 Open (Emergency Sample Collection)', 1,
  '40% flat discount on all Blood Profiles, Thyroid, Diabetes & Lipid Tests for NGO Referrals',
  'CBC, HbA1c, Liver Function Test (LFT), Kidney Function Test (KFT), Digital X-Ray, ECG, Ultrasound',
  'NABL Accredited Laboratory with automated analyzers.', 'active', 1
),
(
  'HCP-2026-0004', 'Jan Aushadhi Seva Pharmacy', 'pharmacy', 'other', 'Generic & Essential Medicines', 'Manoj Kumar (D.Pharm)',
  '+91 97333 44556', NULL, 'janaushadhi.care@gmail.com', 'Bihar', 'Patna', 'Kankarbagh',
  '800020', 'Main Road, Near Old Bus Stand, Kankarbagh', 'Near State Bank ATM', 'All 7 Days: 08:00 AM - 10:00 PM', 0,
  'Up to 70-80% savings on generic critical medicines & free BP/Sugar check',
  'Essential Antibiotics, Cardiac & Diabetes Care, Pediatric syrups, Ortho aids, First Aid Kits',
  'Authorized Jan Aushadhi generic dispensary partner.', 'active', 1
),
(
  'HCP-2026-0005', 'Dr. Arvind Mehra (General Physician & Cardiologist)', 'doctor', 'other', 'Internal Medicine & Cardiology', 'Dr. Arvind Mehra (MD Medicine)',
  '+91 96444 55667', NULL, 'dr.mehra@cliniccare.in', 'Rajasthan', 'Jaipur', 'Malviya Nagar',
  '302017', 'Clinic No 12, Health Square, Calgiri Marg', 'Opposite Fortis Hospital', 'Mon - Fri: 04:00 PM - 08:00 PM', 0,
  'Free Consultation for Underprivileged Patients referred by NGO',
  'Hypertension management, Diabetes screening, ECG interpretation, Preventative cardiac consultation',
  'Available for weekend rural medical camps.', 'active', 1
)
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `type` = VALUES(`type`),
  `speciality` = VALUES(`speciality`),
  `contact` = VALUES(`contact`),
  `status` = VALUES(`status`);

SET FOREIGN_KEY_CHECKS = 1;
