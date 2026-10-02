-- ============================================================
-- Database Migration: Job & Career Management System
-- Description: Creates tables `job_openings` and `job_applications`
--              for NGO State, District, Block & Panchayat Coordinators and Staff.
-- Compatible with: MySQL 5.7+, MariaDB 10.3+
-- Author: VELNIX SOFT / Antigravity AI
-- Date: 2026-09-12
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- 1. Table structure for `job_openings`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `job_openings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_code` varchar(50) NOT NULL,
  `title` varchar(200) NOT NULL,
  `category` enum('state_coordinator','district_coordinator','block_coordinator','panchayat_coordinator','other') NOT NULL DEFAULT 'other',
  `category_custom` varchar(150) DEFAULT NULL,
  `description` text NOT NULL,
  `requirements` text DEFAULT NULL,
  `responsibilities` text DEFAULT NULL,
  `location` varchar(150) NOT NULL,
  `state` varchar(100) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `block` varchar(100) DEFAULT NULL,
  `openings_count` int(11) NOT NULL DEFAULT 1,
  `salary_range` varchar(100) DEFAULT NULL,
  `job_type` enum('Full-time','Part-time','Contract','Internship','Volunteer') NOT NULL DEFAULT 'Full-time',
  `experience_required` varchar(100) DEFAULT NULL,
  `min_qualification` varchar(150) DEFAULT NULL,
  `status` enum('active','inactive','closed','draft') NOT NULL DEFAULT 'active',
  `posted_date` date NOT NULL,
  `last_date` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_job_code` (`job_code`),
  KEY `idx_jobs_category` (`category`),
  KEY `idx_jobs_status` (`status`),
  KEY `idx_jobs_location` (`location`),
  KEY `idx_jobs_state` (`state`),
  KEY `idx_jobs_district` (`district`),
  KEY `idx_jobs_posted_date` (`posted_date`),
  KEY `idx_jobs_last_date` (`last_date`),
  KEY `idx_jobs_created_by` (`created_by`),
  CONSTRAINT `fk_job_openings_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 2. Table structure for `job_applications`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `job_applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_no` varchar(50) NOT NULL,
  `job_id` int(11) NOT NULL,
  `applicant_name` varchar(150) NOT NULL,
  `contact` varchar(50) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `gender` enum('Male','Female','Other') NOT NULL DEFAULT 'Male',
  `dob` date DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `qualification` varchar(150) DEFAULT NULL,
  `experience_years` decimal(4,1) DEFAULT 0.0,
  `current_city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `resume_path` varchar(255) NOT NULL,
  `cover_letter` text DEFAULT NULL,
  `status` enum('pending','reviewed','shortlisted','interview_scheduled','selected','rejected') NOT NULL DEFAULT 'pending',
  `applied_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `interview_date` datetime DEFAULT NULL,
  `interview_venue` varchar(255) DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_job_application_no` (`application_no`),
  KEY `idx_app_job_id` (`job_id`),
  KEY `idx_app_applicant_name` (`applicant_name`),
  KEY `idx_app_contact` (`contact`),
  KEY `idx_app_email` (`email`),
  KEY `idx_app_status` (`status`),
  KEY `idx_app_applied_date` (`applied_date`),
  KEY `idx_app_reviewed_by` (`reviewed_by`),
  CONSTRAINT `fk_job_applications_job` FOREIGN KEY (`job_id`) REFERENCES `job_openings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_job_applications_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 3. Seed Initial Sample Job Openings
-- ------------------------------------------------------------
INSERT INTO `job_openings` (
  `job_code`, `title`, `category`, `description`, `requirements`, `responsibilities`,
  `location`, `state`, `district`, `openings_count`, `salary_range`, `job_type`, 
  `experience_required`, `min_qualification`, `status`, `posted_date`, `last_date`
) VALUES
(
  'JOB-2026-0001',
  'State Program Coordinator',
  'state_coordinator',
  'Lead state-level development programs, manage district coordinators, liaise with government departments and partner NGOs for community health, education, and livelihood projects.',
  'Strong leadership skills, proficiency in Hindi & English, experience in NGO/CSR project management, willingness to travel across districts.',
  'Supervise district coordinators, monitor KPIs, submit monthly impact reports, organize state level review meetings.',
  'State Head Office, Lucknow',
  'Uttar Pradesh',
  'Lucknow',
  2,
  '₹35,000 - ₹50,000 / month',
  'Full-time',
  '3-5 Years in NGO/Rural Dev',
  'Post Graduate / MSW / MBA',
  'active',
  '2026-09-01',
  '2026-10-31'
),
(
  'JOB-2026-0002',
  'District Operations Coordinator',
  'district_coordinator',
  'Oversee block level coordinators, execute health card drives, coordinate with empaneled hospitals, and manage volunteer activities across the district.',
  'Good communication skills, local district knowledge, basic computer skills (MS Excel/Google Sheets), two-wheeler with valid license.',
  'Onboard healthcare partners, organize health & donation camps, supervise block coordinators, verify beneficiary applications.',
  'Patna District Office',
  'Bihar',
  'Patna',
  5,
  '₹22,000 - ₹30,000 / month',
  'Full-time',
  '1-3 Years in Field Operations',
  'Graduate / BSW / Any Degree',
  'active',
  '2026-09-05',
  '2026-10-25'
),
(
  'JOB-2026-0003',
  'Block Field Coordinator',
  'block_coordinator',
  'Grassroots coordinator responsible for panchayat outreach, beneficiary identification, swasthya health card enrollments, and organizing village meetings.',
  'Active community presence, excellent interpersonal skills, mobile app handling skills.',
  'Conduct door-to-door awareness, coordinate with Gram Pradhans, enroll citizens for health cards, report to district coordinator.',
  'Varanasi Sadar Block',
  'Uttar Pradesh',
  'Varanasi',
  12,
  '₹15,000 - ₹20,000 / month',
  'Full-time',
  '0-2 Years / Freshers Welcome',
  '12th Pass / Graduate',
  'active',
  '2026-09-08',
  '2026-11-15'
),
(
  'JOB-2026-0004',
  'Gram Panchayat Mobilizer & Coordinator',
  'panchayat_coordinator',
  'Village level representative to assist villagers in emergency health assistance, grievance logging, and NGO welfare initiatives.',
  'Resident of local gram panchayat, trusted by community, basic smartphone usage.',
  'Panchayat level awareness, distribution of health cards, guiding beneficiaries to network hospitals.',
  'Gram Panchayat Level (Multi-location)',
  'Madhya Pradesh',
  'Bhopal',
  25,
  '₹8,000 - ₹12,000 / month + Incentives',
  'Contract',
  'Fresher / Community Worker',
  '10th / 12th Pass',
  'active',
  '2026-09-10',
  '2026-11-30'
)
ON DUPLICATE KEY UPDATE
  `title` = VALUES(`title`),
  `status` = VALUES(`status`),
  `openings_count` = VALUES(`openings_count`);

-- ------------------------------------------------------------
-- 4. Seed Sample Job Application
-- ------------------------------------------------------------
INSERT INTO `job_applications` (
  `application_no`, `job_id`, `applicant_name`, `contact`, `email`, 
  `gender`, `dob`, `age`, `qualification`, `experience_years`, 
  `current_city`, `state`, `district`, `address`, `resume_path`, 
  `cover_letter`, `status`, `applied_date`
) VALUES
(
  'APP-2026-0001',
  1,
  'Amitabh Sharma',
  '9876543210',
  'amitabh.sharma@example.com',
  'Male',
  '1992-04-15',
  34,
  'Master of Social Work (MSW)',
  4.5,
  'Lucknow',
  'Uttar Pradesh',
  'Lucknow',
  'Plot 45, Aliganj, Lucknow, UP',
  'uploads/resumes/sample_resume.pdf',
  'I have 4+ years of experience in rural development and grassroots project coordination.',
  'reviewed',
  '2026-09-11 10:30:00'
)
ON DUPLICATE KEY UPDATE
  `applicant_name` = VALUES(`applicant_name`),
  `status` = VALUES(`status`);

SET FOREIGN_KEY_CHECKS = 1;
