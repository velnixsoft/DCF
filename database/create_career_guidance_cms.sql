-- ============================================================
-- database/create_career_guidance_cms.sql
-- Migration for Career Guidance CMS, Skill Courses & Enrollments
-- ============================================================

-- 1. Insert default CMS settings for Career Guidance if not exists
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('career_guidance_title', 'Career Guidance & Skills Training'),
('career_guidance_subtitle', 'Empowering youth with market-ready vocational skills, interview mentorship, and career counseling.'),
('career_guidance_desc', 'Our Career Guidance and Skills Development Initiative bridges the gap between grassroots education and viable employment. We offer hands-on vocational modules, digital literacy programs, competitive exam preparation, and free one-on-one mentorship sessions to help youth build sustainable livelihoods.'),
('career_guidance_counseling_text', 'Need personalized guidance on choosing the right career stream or preparing for government and private sector jobs? Connect with our certified NGO career counselors for free advisory sessions.'),
('career_guidance_helpline', '+91 98765 43210'),
('career_guidance_email', 'careers@ngocare.org'),
('career_guidance_banner_image', '')
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;

-- 2. Create `skill_courses` table
CREATE TABLE IF NOT EXISTS `skill_courses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `category` ENUM('vocational', 'computer_it', 'soft_skills', 'competitive_exams', 'entrepreneurship', 'healthcare_aid', 'other') NOT NULL DEFAULT 'vocational',
    `category_custom` VARCHAR(100) DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `curriculum` TEXT DEFAULT NULL,
    `duration` VARCHAR(100) DEFAULT '3 Months',
    `eligibility` VARCHAR(150) DEFAULT '10th / 12th Pass or Equivalent',
    `mode` ENUM('Offline', 'Online', 'Hybrid') NOT NULL DEFAULT 'Offline',
    `fee_type` ENUM('100% Free', 'Subsidized Aid', 'Scholarship Based') NOT NULL DEFAULT '100% Free',
    `instructor` VARCHAR(150) DEFAULT 'Senior NGO Faculty & Field Experts',
    `location` VARCHAR(255) DEFAULT 'Field Training Center & Online',
    `image_path` VARCHAR(255) DEFAULT NULL,
    `batch_start_date` DATE DEFAULT NULL,
    `max_seats` INT NOT NULL DEFAULT 30,
    `status` ENUM('active', 'inactive', 'upcoming', 'completed') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_skill_category` (`category`),
    INDEX `idx_skill_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Create `skill_course_enrollments` table
CREATE TABLE IF NOT EXISTS `skill_course_enrollments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `application_no` VARCHAR(50) NOT NULL UNIQUE,
    `course_id` INT NOT NULL,
    `applicant_name` VARCHAR(150) NOT NULL,
    `contact` VARCHAR(20) NOT NULL,
    `email` VARCHAR(150) DEFAULT NULL,
    `gender` ENUM('Male', 'Female', 'Other') NOT NULL DEFAULT 'Male',
    `dob` DATE DEFAULT NULL,
    `age` INT DEFAULT NULL,
    `qualification` VARCHAR(150) NOT NULL,
    `state` VARCHAR(100) NOT NULL,
    `district` VARCHAR(100) NOT NULL,
    `city` VARCHAR(100) DEFAULT NULL,
    `address` TEXT NOT NULL,
    `motivation` TEXT DEFAULT NULL,
    `status` ENUM('pending', 'contacted', 'enrolled', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    `admin_notes` TEXT DEFAULT NULL,
    `reviewed_by` INT DEFAULT NULL,
    `applied_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_enr_course` (`course_id`),
    INDEX `idx_enr_status` (`status`),
    INDEX `idx_enr_contact` (`contact`),
    CONSTRAINT `fk_enr_course_id` FOREIGN KEY (`course_id`) REFERENCES `skill_courses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Seed initial sample courses if empty
INSERT INTO `skill_courses` (`title`, `category`, `description`, `curriculum`, `duration`, `eligibility`, `mode`, `fee_type`, `instructor`, `location`, `batch_start_date`, `max_seats`, `status`)
SELECT 'Digital Literacy & Office Productivity', 'computer_it', 
       'Master fundamental computer operations, Microsoft Office (Word, Excel, PPT), Internet browsing, email communication, and online government portal navigation.',
       'Module 1: Basic Computer Hardware & OS\nModule 2: MS Word & Document Design\nModule 3: MS Excel Data Management\nModule 4: Internet & Cyber Hygiene',
       '6 Weeks (45 Hours)', '8th / 10th Pass', 'Hybrid', '100% Free', 'Rajesh Verma (IT Trainer)', 'District Center & Online', DATE_ADD(CURDATE(), INTERVAL 14 DAY), 35, 'active'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `skill_courses` WHERE `title` = 'Digital Literacy & Office Productivity');

INSERT INTO `skill_courses` (`title`, `category`, `description`, `curriculum`, `duration`, `eligibility`, `mode`, `fee_type`, `instructor`, `location`, `batch_start_date`, `max_seats`, `status`)
SELECT 'Community Healthcare Assistant (First Aid & Nursing Basics)', 'healthcare_aid', 
       'Comprehensive field training in vital signs monitoring, primary first-aid, community hygiene counseling, elderly care, and basic patient support.',
       'Module 1: Vital Signs & BP Monitoring\nModule 2: Emergency First Aid & Wound Dressing\nModule 3: Patient Care Ethics & Sanitization\nModule 4: Field Internship at NGO Health Camps',
       '3 Months', '10th / 12th Pass', 'Offline', '100% Free', 'Dr. S. K. Gupta & Nursing Staff', 'NGO Medical Training Center', DATE_ADD(CURDATE(), INTERVAL 21 DAY), 25, 'active'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `skill_courses` WHERE `title` = 'Community Healthcare Assistant (First Aid & Nursing Basics)');

INSERT INTO `skill_courses` (`title`, `category`, `description`, `curriculum`, `duration`, `eligibility`, `mode`, `fee_type`, `instructor`, `location`, `batch_start_date`, `max_seats`, `status`)
SELECT 'Spoken English & Interview Communication Mastery', 'soft_skills', 
       'Build conversational confidence, professional email etiquette, resume writing, public speaking, and crack job interviews with mock rounds.',
       'Module 1: Everyday English Vocabulary & Grammar\nModule 2: Professional Workplace Dialogue\nModule 3: Resume & Cover Letter Formulation\nModule 4: Mock Interview Rounds & Feedback',
       '2 Months (60 Hours)', '12th Pass / Undergraduate', 'Online', '100% Free', 'Anjali Sharma (Communication Specialist)', 'Live Online Classes', DATE_ADD(CURDATE(), INTERVAL 10 DAY), 50, 'active'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `skill_courses` WHERE `title` = 'Spoken English & Interview Communication Mastery');

INSERT INTO `skill_courses` (`title`, `category`, `description`, `curriculum`, `duration`, `eligibility`, `mode`, `fee_type`, `instructor`, `location`, `batch_start_date`, `max_seats`, `status`)
SELECT 'Rural Micro-Entrepreneurship & SHG Handicraft Management', 'entrepreneurship', 
       'Learn practical business startup fundamentals, product pricing, micro-loans, digital UPI payments, government subsidies (PMEGP/MUDRA), and local market linkage.',
       'Module 1: Business Idea Validation & Feasibility\nModule 2: Bookkeeping & Cashflow Management\nModule 3: Government Schemes & Bank Loans\nModule 4: Marketing via WhatsApp & Local Melas',
       '4 Weeks', 'Open to All / Women & Youth', 'Offline', '100% Free', 'Vikas Mishra (Rural Enterprise Mentor)', 'Block Skill Hub', DATE_ADD(CURDATE(), INTERVAL 28 DAY), 30, 'active'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `skill_courses` WHERE `title` = 'Rural Micro-Entrepreneurship & SHG Handicraft Management');
