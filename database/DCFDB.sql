-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 02, 2026 at 06:25 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ngomain`
--

-- --------------------------------------------------------

--
-- Table structure for table `achievement_positions`
--

CREATE TABLE `achievement_positions` (
  `id` int(11) NOT NULL,
  `title` varchar(120) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `achievement_positions`
--

INSERT INTO `achievement_positions` (`id`, `title`, `is_active`, `created_at`) VALUES
(1, 'Winner', 1, '2026-04-22 01:58:45'),
(2, '1st Position', 1, '2026-04-22 01:58:45'),
(3, '2nd Position', 1, '2026-04-22 01:58:45'),
(4, '3rd Position', 1, '2026-04-22 01:58:45'),
(5, 'Participant', 1, '2026-04-22 01:58:45');

-- --------------------------------------------------------

--
-- Table structure for table `admin_audit_logs`
--

CREATE TABLE `admin_audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(80) NOT NULL,
  `entity_type` varchar(80) NOT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_audit_logs`
--

INSERT INTO `admin_audit_logs` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 2, 'student_approve', 'sa_students', 1, 'Student registration approved', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-06-06 15:11:31'),
(2, 2, 'student_approve', 'sa_students', 2, 'Student registration approved', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-07 11:21:04'),
(3, 2, 'referral_verify', 'sa_referrals', 1, 'Referral verified and points awarded.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-07 11:21:19');

-- --------------------------------------------------------

--
-- Table structure for table `agent_attendance`
--

CREATE TABLE `agent_attendance` (
  `id` int(11) NOT NULL,
  `agent_id` int(11) NOT NULL,
  `punch_type` enum('IN','OUT') NOT NULL,
  `punch_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` enum('recorded','verified') DEFAULT 'recorded'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `agent_salary_ledger`
--

CREATE TABLE `agent_salary_ledger` (
  `id` int(11) NOT NULL,
  `agent_id` int(11) NOT NULL,
  `month_key` char(7) NOT NULL,
  `target_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `achieved_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `base_salary` decimal(10,2) NOT NULL DEFAULT 0.00,
  `incentive_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_payable` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','credited') NOT NULL DEFAULT 'pending',
  `credited_at` datetime DEFAULT NULL,
  `credited_by_user_id` int(11) DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `agreements`
--

CREATE TABLE `agreements` (
  `id` int(11) NOT NULL,
  `agreement_no` varchar(50) NOT NULL COMMENT 'Unique identifier e.g. MOU-2026-0001',
  `partner_name` varchar(200) NOT NULL COMMENT 'Second party / partner organization name',
  `partner_type` varchar(100) DEFAULT 'organization' COMMENT 'hospital, school, corporate, vendor, ngo, etc.',
  `partner_contact` varchar(50) DEFAULT NULL,
  `partner_email` varchar(150) DEFAULT NULL,
  `partner_address` text DEFAULT NULL,
  `partner_member_id` int(11) DEFAULT NULL,
  `type` enum('mou','authorization','service_agreement','partnership') NOT NULL DEFAULT 'mou',
  `title` varchar(255) NOT NULL,
  `content` longtext NOT NULL COMMENT 'Clauses and Agreement Body Text',
  `signed_status` enum('draft','pending_signature','signed','expired','terminated') NOT NULL DEFAULT 'draft',
  `is_acknowledged` tinyint(1) NOT NULL DEFAULT 0,
  `acknowledged_at` datetime DEFAULT NULL,
  `acknowledged_name` varchar(150) DEFAULT NULL,
  `acknowledged_ip` varchar(45) DEFAULT NULL,
  `acknowledged_user_agent` varchar(255) DEFAULT NULL,
  `acknowledgment_token` varchar(64) DEFAULT NULL,
  `signed_date` date DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `pdf_path` varchar(255) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `generated_date` datetime NOT NULL DEFAULT current_timestamp(),
  `first_party_name` varchar(150) DEFAULT NULL,
  `first_party_designation` varchar(100) DEFAULT NULL,
  `second_party_name` varchar(150) DEFAULT NULL,
  `second_party_designation` varchar(100) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `agreements`
--

INSERT INTO `agreements` (`id`, `agreement_no`, `partner_name`, `partner_type`, `partner_contact`, `partner_email`, `partner_address`, `partner_member_id`, `type`, `title`, `content`, `signed_status`, `is_acknowledged`, `acknowledged_at`, `acknowledged_name`, `acknowledged_ip`, `acknowledged_user_agent`, `acknowledgment_token`, `signed_date`, `valid_until`, `pdf_path`, `file_size`, `generated_date`, `first_party_name`, `first_party_designation`, `second_party_name`, `second_party_designation`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'MOU-2026-0001', 'Metro Care Healthcare & Diagnostics Trust', 'Healthcare Network', '+91 9820011223', 'partnerships@metrocaretrust.org', 'Plot 45, MIDC Central Road, Andheri East, Mumbai, Maharashtra 400093', NULL, 'mou', 'MEMORANDUM OF UNDERSTANDING (MoU) FOR COMMUNITY WELFARE & PARTNERSHIP', 'This Memorandum of Understanding (hereinafter referred to as \"MoU\") is made and entered into on this 15 Sep 2026 (the \"Effective Date\"), by and between:\n\nFIRST PARTY:\nJaysmrutti Foundation (A Registered Non-Governmental Organization bearing 123455), having its official office at 2nd Floor, Dharma Villa, Wazidpur Tiraha, Jaunpur, Uttar Pradesh - 222002, India (hereinafter referred to as the \"First Party\" / \"NGO\", which expression shall unless repugnant to the context include its successors, trustees, and assignees).\n\nAND\n\nSECOND PARTY:\nMetro Care Healthcare Trust, having its principal facility/office at Andheri East, Mumbai, Maharashtra (hereinafter referred to as the \"Second Party\" / \"Partner\", which expression shall include its representatives, successors, and permitted assignees).\n\nWHEREAS:\nA. The First Party is dedicated to rural upliftment, health camps, vocational training, educational sponsorship, and humanitarian relief for underprivileged families.\nB. The Second Party possesses specialized facilities, community outreach capabilities, and resources aligned with charitable and social impact objectives.\nC. Both Parties mutually desire to collaborate in good faith to maximize public benefit and social welfare across operational districts.\n\nNOW, THEREFORE, IT IS MUTUALLY AGREED AS FOLLOWS:\n\n1. PURPOSE & SCOPE OF COOPERATION\nThe primary purpose of this MoU is to establish a cooperative framework for joint humanitarian initiatives, including health screening camps, subsidized diagnostics/treatments, career development workshops, and relief aid for verified beneficiaries.\n\n2. ROLES & RESPONSIBILITIES OF THE FIRST PARTY (NGO)\n2.1 Identify, verify, and refer eligible marginalized beneficiaries, students, and patients requiring assistance.\n2.2 Issue official referral vouchers, health beneficiary identity cards, and verification certificates.\n2.3 Provide volunteer support, event mobilization, and promotional assistance for joint social drives.\n2.4 Maintain transparent documentation and maintain compliance with NGO regulatory norms.\n\n3. ROLES & RESPONSIBILITIES OF THE SECOND PARTY (PARTNER)\n3.1 Provide agreed subsidized concessions, professional guidance, or priority service to beneficiaries carrying official NGO credentials.\n3.2 Participate in periodic community outreach programs, health camps, or skill development workshops as mutually scheduled.\n3.3 Share attendance/treatment records or execution summaries with the NGO coordinator for impact evaluation.\n3.4 Ensure zero commercial exploitation of sponsored candidates or marginalized families.\n\n4. NON-COMMERCIAL UNDERTAKING & ETHICS\nThis alliance is built on charitable and non-commercial principles. Neither Party shall charge unauthorized fees or misrepresent the partnership for commercial gains contrary to the spirit of social welfare.\n\n5. DURATION & VALIDITY\nThis MoU shall remain valid from 15 Sep 2026 until 14 Sep 2027, unless terminated earlier by mutual consent or extended in writing with mutual agreement.\n\n6. TERMINATION\nEither Party may terminate this MoU by providing thirty (30) days prior written notice to the other Party. Ongoing beneficiary services under active commitment shall be duly honored.\n\nIN WITNESS WHEREOF, the Authorized Representatives of the First Party and Second Party have executed this Memorandum of Understanding as of the date first written above.', 'signed', 0, NULL, NULL, NULL, NULL, 'dc15e6294bcb115fe945e9c355545859', '2026-09-15', '2027-09-14', 'uploads/documents/agreements/Agreement_MOU-2026-0001.pdf', 248705, '2026-09-12 16:00:24', 'Authorized Signatory', 'President / General Secretary', 'Dr. Vikram Malhotra', 'Executive Director & Chief Trustee', NULL, '2026-09-12 10:30:24', '2026-09-12 10:32:36'),
(2, 'AUTH-2026-0002', 'SmileCare Dental & Oral Health Institute', 'Empanelled Dental Center', '+91 9415099881', 'info@smilecareinstitute.in', 'Shop 12-14, Ground Floor, MG Road, Hazratganj, Lucknow, Uttar Pradesh 226001', NULL, 'authorization', 'OFFICIAL LETTER OF AUTHORIZATION & EMPANELMENT', 'TO WHOMSOEVER IT MAY CONCERN\n\nThis is to officially certify that:\n\nDr. Sharma Eye Care Clinic\nLocated at: Hazratganj, Lucknow, Uttar Pradesh\n\nhas been officially recognized and empanelled as an AUTHORIZED COMMUNITY COLLABORATION PARTNER & NODAL CENTER of Jaysmrutti Foundation (123455) effective from 12 Sep 2026.\n\nSCOPE OF AUTHORIZATION & RECOGNITION:\n1. The partner is authorized to act as an official community facilitation and assistance center for NGO social welfare programs, health card verification, and citizen assistance drives in its designated territory.\n2. Authorized to display official NGO collaboration signage, partner empanelment certificates, and distribute public awareness literature.\n3. Entitled to coordinate official medical checkup camps, skill workshops, and relief drives in association with designated NGO coordinators.\n4. Bound to strictly adhere to the non-profit charter, ethical guidelines, and transparency mandates of Jaysmrutti Foundation.\n\nVALIDITY & MONITORING:\nThis authorization is granted up to 11 Sep 2027 and is subject to annual performance review and ethical compliance. It does not confer financial liability or legal representation beyond the specified scope.\n\nIssued with the seal and authority of the Central Governing Body.', 'signed', 0, NULL, NULL, NULL, NULL, 'ea772d341c9838187479579c28db8ae1', '2026-09-12', '2027-09-11', 'uploads/documents/agreements/Agreement_AUTH-2026-0002.pdf', 246464, '2026-09-12 16:00:25', 'Authorized Signatory', 'President / General Secretary', 'Dr. Ananya Rastogi', 'Senior Dental Surgeon & Center Head', NULL, '2026-09-12 10:30:25', '2026-09-12 10:32:36');

-- --------------------------------------------------------

--
-- Table structure for table `attendance_bonus_history`
--

CREATE TABLE `attendance_bonus_history` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `year` int(4) NOT NULL,
  `month` int(2) NOT NULL,
  `attendance_percentage` decimal(5,2) NOT NULL,
  `threshold_percentage` decimal(5,2) NOT NULL,
  `threshold_met` tinyint(1) NOT NULL DEFAULT 0,
  `bonus_points_awarded` int(11) NOT NULL DEFAULT 0,
  `reference_threshold_id` int(11) DEFAULT NULL,
  `point_transaction_id` int(11) DEFAULT NULL,
  `awarded_at` datetime NOT NULL DEFAULT current_timestamp(),
  `processed_by_cron` tinyint(1) NOT NULL DEFAULT 0,
  `cron_execution_timestamp` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_events`
--

CREATE TABLE `attendance_events` (
  `id` int(11) NOT NULL,
  `event_id` int(11) DEFAULT NULL,
  `event_name` varchar(255) NOT NULL,
  `event_date` date NOT NULL,
  `event_start_time` time DEFAULT NULL,
  `event_location` varchar(500) DEFAULT NULL,
  `qr_mode` enum('single','multiple') NOT NULL DEFAULT 'single',
  `status` enum('active','inactive','closed') NOT NULL DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_logs`
--

CREATE TABLE `attendance_logs` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `member_id` int(11) DEFAULT NULL,
  `volunteer_id` int(11) DEFAULT NULL,
  `attendee_type` enum('member','volunteer') NOT NULL DEFAULT 'member',
  `scan_time` datetime NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(64) DEFAULT NULL,
  `device_info` varchar(255) DEFAULT NULL,
  `status` enum('marked','duplicate','blocked','invalid') NOT NULL DEFAULT 'marked',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_monthly_summary`
--

CREATE TABLE `attendance_monthly_summary` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `year` int(4) NOT NULL,
  `month` int(2) NOT NULL,
  `total_eligible_days` int(11) NOT NULL DEFAULT 0,
  `days_attended` int(11) NOT NULL DEFAULT 0,
  `attendance_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `attendance_bonus_awarded` tinyint(1) NOT NULL DEFAULT 0,
  `bonus_points_amount` int(11) DEFAULT 0,
  `bonus_processing_date` datetime DEFAULT NULL,
  `is_processed` tinyint(1) NOT NULL DEFAULT 0,
  `processing_timestamp` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_thresholds`
--

CREATE TABLE `attendance_thresholds` (
  `id` int(11) NOT NULL,
  `threshold_name` varchar(100) NOT NULL,
  `percentage_required` decimal(5,2) NOT NULL,
  `bonus_points` int(11) NOT NULL DEFAULT 0,
  `min_eligible_days` int(11) NOT NULL DEFAULT 10,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance_thresholds`
--

INSERT INTO `attendance_thresholds` (`id`, `threshold_name`, `percentage_required`, `bonus_points`, `min_eligible_days`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Standard Attendance', 90.00, 25, 10, 1, 1, '2026-06-06 12:15:37', '2026-06-06 12:15:37'),
(2, 'High Attendance', 95.00, 50, 10, 1, 2, '2026-06-06 12:15:37', '2026-06-06 12:15:37'),
(3, 'Perfect Attendance', 100.00, 100, 10, 1, 3, '2026-06-06 12:15:37', '2026-06-06 12:15:37');

-- --------------------------------------------------------

--
-- Table structure for table `badge_rules`
--

CREATE TABLE `badge_rules` (
  `id` int(11) NOT NULL,
  `badge_code` varchar(80) NOT NULL,
  `badge_rank` int(11) NOT NULL DEFAULT 0,
  `min_points` int(11) NOT NULL DEFAULT 0,
  `min_attendance_days` int(11) NOT NULL DEFAULT 0,
  `min_referrals` int(11) NOT NULL DEFAULT 0,
  `min_vendor_onboardings` int(11) NOT NULL DEFAULT 0,
  `min_campaign_approvals` int(11) NOT NULL DEFAULT 0,
  `min_campaign_points` int(11) NOT NULL DEFAULT 0,
  `min_donation_count` int(11) NOT NULL DEFAULT 0,
  `min_donation_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `max_city_rank` int(11) NOT NULL DEFAULT 0,
  `max_state_rank` int(11) NOT NULL DEFAULT 0,
  `min_level_rank` int(11) NOT NULL DEFAULT 0,
  `min_health_campaigns` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `effective_from` datetime DEFAULT NULL,
  `effective_to` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `badge_rules`
--

INSERT INTO `badge_rules` (`id`, `badge_code`, `badge_rank`, `min_points`, `min_attendance_days`, `min_referrals`, `min_vendor_onboardings`, `min_campaign_approvals`, `min_campaign_points`, `min_donation_count`, `min_donation_amount`, `max_city_rank`, `max_state_rank`, `min_level_rank`, `min_health_campaigns`, `is_active`, `effective_from`, `effective_to`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'RISING_STAR', 1, 100, 7, 0, 0, 2, 25, 0, 0.00, 0, 0, 1, 0, 1, NULL, NULL, 'Early activity: points, attendance, and approved campaigns.', '2026-06-06 12:10:29', '2026-06-06 12:10:29'),
(2, 'COMMUNITY_BUILDER', 2, 250, 10, 5, 0, 3, 75, 0, 0.00, 0, 0, 2, 0, 1, NULL, NULL, 'Referral-led community growth.', '2026-06-06 12:10:29', '2026-06-06 12:10:29'),
(3, 'HEALTH_ADVOCATE', 3, 300, 10, 0, 0, 3, 75, 0, 0.00, 0, 0, 2, 2, 1, NULL, NULL, 'Approved health-related campaign work.', '2026-06-06 12:10:29', '2026-06-06 12:10:29'),
(4, 'FUNDRAISING_CHAMPION', 4, 500, 15, 0, 0, 4, 100, 10, 10000.00, 0, 0, 3, 0, 1, NULL, NULL, 'Verified fundraising performance.', '2026-06-06 12:10:29', '2026-06-06 12:10:29'),
(5, 'CITY_INFLUENCER', 5, 1000, 25, 10, 3, 10, 300, 0, 0.00, 10, 0, 4, 0, 1, NULL, NULL, 'Top city rank and broad campaign impact.', '2026-06-06 12:10:29', '2026-06-06 12:10:29'),
(6, 'LEADERSHIP_ELITE', 6, 2500, 45, 25, 12, 25, 900, 0, 0.00, 0, 20, 6, 0, 1, NULL, NULL, 'State-level leadership performance.', '2026-06-06 12:10:29', '2026-06-06 12:10:29'),
(7, 'DONATION_MILESTONE_CHAMPION', 99, 0, 0, 0, 0, 0, 0, 0, 10000.00, 0, 0, 0, 0, 1, NULL, NULL, 'Automatic badge for facilitating ₹10000+ in donations', '2026-06-06 12:15:37', '2026-06-06 12:15:37');

-- --------------------------------------------------------

--
-- Table structure for table `bank_accounts`
--

CREATE TABLE `bank_accounts` (
  `id` int(11) NOT NULL,
  `bank_name` varchar(255) NOT NULL,
  `account_holder` varchar(255) NOT NULL,
  `account_number` varchar(50) NOT NULL,
  `ifsc_code` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bank_accounts`
--

INSERT INTO `bank_accounts` (`id`, `bank_name`, `account_holder`, `account_number`, `ifsc_code`, `is_active`) VALUES
(1, 'State Bank Of India', 'Saarthi Foundation', '1234567899', 'SBIN06564', 1);

-- --------------------------------------------------------

--
-- Table structure for table `beneficiaries`
--

CREATE TABLE `beneficiaries` (
  `id` int(11) NOT NULL,
  `beneficiary_code` varchar(50) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `father_or_spouse_name` varchar(150) DEFAULT NULL,
  `gender` enum('Male','Female','Other') NOT NULL DEFAULT 'Male',
  `dob` date DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `contact` varchar(20) NOT NULL,
  `alternate_contact` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `aadhar_no` varchar(20) DEFAULT NULL,
  `ration_card_no` varchar(50) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `beneficiary_type` varchar(100) DEFAULT 'General / BPL',
  `annual_income` decimal(10,2) DEFAULT NULL,
  `family_members_count` int(11) NOT NULL DEFAULT 1,
  `disability_status` enum('No','Yes') NOT NULL DEFAULT 'No',
  `disability_details` varchar(255) DEFAULT NULL,
  `address` text NOT NULL,
  `block` varchar(100) DEFAULT NULL,
  `district` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `village_city` varchar(100) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `id_proof_doc` varchar(255) DEFAULT NULL,
  `income_proof_doc` varchar(255) DEFAULT NULL,
  `registration_date` date NOT NULL,
  `status` enum('Active','Inactive','Under Review','Assisted','Archived') NOT NULL DEFAULT 'Active',
  `project_id` int(11) DEFAULT NULL,
  `registered_by` int(11) DEFAULT NULL,
  `coordinator_id` int(11) DEFAULT NULL,
  `field_agent_id` int(11) DEFAULT NULL,
  `sa_student_id` int(11) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `beneficiaries`
--

INSERT INTO `beneficiaries` (`id`, `beneficiary_code`, `name`, `father_or_spouse_name`, `gender`, `dob`, `age`, `contact`, `alternate_contact`, `email`, `aadhar_no`, `ration_card_no`, `category_id`, `beneficiary_type`, `annual_income`, `family_members_count`, `disability_status`, `disability_details`, `address`, `block`, `district`, `state`, `village_city`, `pincode`, `photo`, `id_proof_doc`, `income_proof_doc`, `registration_date`, `status`, `project_id`, `registered_by`, `coordinator_id`, `field_agent_id`, `sa_student_id`, `remarks`, `created_at`, `updated_at`) VALUES
(1, 'BEN-2026-0001', 'Sunita Devi', 'Late Rajesh Sharma', 'Female', '1982-04-12', 44, '9876543210', NULL, NULL, '123456789012', NULL, 4, 'Widows & Single Mothers', 36000.00, 3, 'No', NULL, 'Village Rampur, Near Primary School', NULL, 'Patna', 'Bihar', NULL, NULL, NULL, NULL, NULL, '2026-01-15', 'Assisted', NULL, NULL, NULL, NULL, NULL, 'Enrolled under women welfare outreach program', '2026-09-12 07:32:48', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `beneficiary_assistance_history`
--

CREATE TABLE `beneficiary_assistance_history` (
  `id` int(11) NOT NULL,
  `assistance_code` varchar(50) DEFAULT NULL,
  `beneficiary_id` int(11) NOT NULL,
  `assistance_type` enum('Financial Aid','Medical Aid','Educational Support','Ration & Food Kit','Clothing & Blankets','Mobility & Assistive Devices','Shelter & Housing','Skill Training & Livelihood','Emergency Relief','In-Kind Items','Other') NOT NULL,
  `description` text NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `items_detail` varchar(255) DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 1.00,
  `unit` varchar(30) NOT NULL DEFAULT 'units',
  `estimated_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `date` date NOT NULL,
  `given_by` varchar(100) DEFAULT NULL,
  `coordinator_id` int(11) DEFAULT NULL,
  `field_agent_id` int(11) DEFAULT NULL,
  `sa_student_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `item_donation_id` int(11) DEFAULT NULL,
  `donation_id` int(11) DEFAULT NULL,
  `event_id` int(11) DEFAULT NULL,
  `distribution_location` varchar(255) DEFAULT NULL,
  `proof_photo` varchar(255) DEFAULT NULL,
  `receipt_no` varchar(50) DEFAULT NULL,
  `status` enum('Pending Approval','Approved','Distributed','Verified','Cancelled') NOT NULL DEFAULT 'Distributed',
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `beneficiary_assistance_history`
--

INSERT INTO `beneficiary_assistance_history` (`id`, `assistance_code`, `beneficiary_id`, `assistance_type`, `description`, `amount`, `items_detail`, `quantity`, `unit`, `estimated_value`, `date`, `given_by`, `coordinator_id`, `field_agent_id`, `sa_student_id`, `project_id`, `item_donation_id`, `donation_id`, `event_id`, `distribution_location`, `proof_photo`, `receipt_no`, `status`, `remarks`, `created_at`, `updated_at`) VALUES
(1, 'AST-2026-0001', 1, 'Ration & Food Kit', 'Emergency dry ration kit (Rice 10kg, Wheat Flour 10kg, Pulses 2kg, Cooking Oil 2L, Spices)', 0.00, 'Dry Ration Family Kit', 1.00, 'kit', 1450.00, '2026-01-20', 'Admin Coordinator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Patna Central Relief Camp', NULL, NULL, 'Distributed', NULL, '2026-09-12 07:32:48', NULL),
(2, 'AST-2026-0002', 1, 'Educational Support', 'School fee sponsorship and book kit for 2 children (Class 6 & 8)', 3500.00, 'School Books & Stationery Kits', 2.00, 'kits', 1200.00, '2026-02-10', 'Education Wing', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Patna Head Office', NULL, NULL, 'Distributed', NULL, '2026-09-12 07:32:48', NULL),
(3, 'AST-2026-0003', 1, 'Clothing & Blankets', 'Winter woollens, blankets, and school uniforms distribution', 0.00, 'Warm Blankets & Clothes', 3.00, 'sets', 1800.00, '2026-03-01', 'Field Team', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Rampur Community Center', NULL, NULL, 'Distributed', NULL, '2026-09-12 07:32:48', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `beneficiary_categories`
--

CREATE TABLE `beneficiary_categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `category_slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(100) DEFAULT 'fa-hands-holding-child',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `beneficiary_categories`
--

INSERT INTO `beneficiary_categories` (`id`, `category_name`, `category_slug`, `description`, `icon`, `is_active`, `display_order`, `created_at`, `updated_at`) VALUES
(1, 'Below Poverty Line (BPL)', 'below-poverty-line', 'Economically disadvantaged and low-income families requiring essential livelihood support', 'fa-house-chimney-crack', 1, 1, '2026-09-12 07:26:35', NULL),
(2, 'Senior Citizens & Elderly', 'senior-citizens', 'Aged individuals without family support needing care, health, and ration assistance', 'fa-person-cane', 1, 2, '2026-09-12 07:26:35', NULL),
(3, 'Orphans & Vulnerable Children', 'orphans-children', 'Children in need of care, education sponsorship, nutrition, and shelter', 'fa-child-reaching', 1, 3, '2026-09-12 07:26:35', NULL),
(4, 'Widows & Single Mothers', 'widows-single-mothers', 'Women facing socio-economic distress requiring financial, nutritional, or livelihood aid', 'fa-person-dress', 1, 4, '2026-09-12 07:26:35', NULL),
(5, 'Divyang (Physically Challenged)', 'physically-challenged', 'Persons with special physical or mobility needs requiring assistive equipment and aids', 'fa-wheelchair', 1, 5, '2026-09-12 07:26:35', NULL),
(6, 'Students & Education Aid', 'students-education', 'Underprivileged students requiring books, school fees, uniforms, and educational kits', 'fa-graduation-cap', 1, 6, '2026-09-12 07:26:35', NULL),
(7, 'Medical & Health Patients', 'medical-patients', 'Patients needing critical medical support, medicines, treatment subsidies, or health devices', 'fa-hand-holding-medical', 1, 7, '2026-09-12 07:26:35', NULL),
(8, 'Disaster & Emergency Relief', 'disaster-relief', 'Families affected by natural calamities, fire, floods, or sudden emergencies', 'fa-tents', 1, 8, '2026-09-12 07:26:35', NULL),
(9, 'Daily Wage & Migrant Workers', 'daily-wage-workers', 'Informal workers needing emergency ration, clothing, health aids, or skill support', 'fa-person-digging', 1, 9, '2026-09-12 07:26:35', NULL),
(10, 'General Welfare', 'general-welfare', 'General community welfare beneficiaries receiving community distribution support', 'fa-hands-holding-heart', 1, 10, '2026-09-12 07:26:35', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `cash_deposits`
--

CREATE TABLE `cash_deposits` (
  `id` int(11) NOT NULL,
  `donation_id` int(11) NOT NULL,
  `agent_id` int(11) NOT NULL,
  `collected_amount` decimal(10,2) NOT NULL,
  `deposit_amount` decimal(10,2) DEFAULT NULL,
  `deposit_date` date DEFAULT NULL,
  `status` enum('pending','deposited','overdue','disputed') DEFAULT 'pending',
  `deposit_deadline` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `certificates`
--

CREATE TABLE `certificates` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `certificates`
--

INSERT INTO `certificates` (`id`, `title`, `image_path`, `created_at`) VALUES
(5, '12A (part A)', 'uploads/certificates/1790487014_Screenshot_27-9-2026_105936_.jpeg', '2026-09-27 05:30:14'),
(6, '12A(part B)', 'uploads/certificates/1790487088_Screenshot_27-9-2026_11058_.jpeg', '2026-09-27 05:31:28'),
(7, '80 G', 'uploads/certificates/1790487165_Screenshot_27-9-2026_11228_.jpeg', '2026-09-27 05:32:45'),
(8, '80G (Part B)', 'uploads/certificates/1790487288_Screenshot_27-9-2026_1144_.jpeg', '2026-09-27 05:34:48'),
(9, 'NGO Darpan', 'uploads/certificates/1790487372_Screenshot_27-9-2026_11540_.jpeg', '2026-09-27 05:36:12'),
(10, 'Pan Card', 'uploads/certificates/1790487442_Screenshot_27-9-2026_11658_.jpeg', '2026-09-27 05:37:22');

-- --------------------------------------------------------

--
-- Table structure for table `complaints`
--

CREATE TABLE `complaints` (
  `id` int(11) NOT NULL,
  `ticket_no` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `contact` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `type` enum('complaint','suggestion') NOT NULL DEFAULT 'complaint',
  `subject` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `status` enum('pending','in_progress','on_hold','resolved') NOT NULL DEFAULT 'pending',
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `admin_reply` text DEFAULT NULL,
  `reply_by` int(11) DEFAULT NULL,
  `replied_at` datetime DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `user_ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `complaints`
--

INSERT INTO `complaints` (`id`, `ticket_no`, `name`, `contact`, `email`, `type`, `subject`, `description`, `status`, `priority`, `admin_reply`, `reply_by`, `replied_at`, `resolved_at`, `attachment_path`, `user_ip`, `created_at`, `updated_at`) VALUES
(1, 'TKT-2026-1001', 'Vikram Singhania', '+91 9876543210', 'vikram.s@example.com', 'complaint', 'Delayed Ration Kit Distribution in Ward 12', 'The scheduled food and dry ration distribution in Ward 12 was delayed by 3 hours today. Beneficiaries had to wait in the sun.', 'in_progress', 'high', 'Our regional coordinator is investigating the vehicle breakdown issue. Support teams have now arrived on site.', 2, '2026-09-11 13:48:45', NULL, NULL, NULL, '2026-09-12 08:18:45', NULL),
(2, 'TKT-2026-1002', 'Pooja Deshmukh', '+91 9812345678', 'pooja.d@example.com', 'suggestion', 'Suggestion to Add Digital Health Checkup Tracker', 'It would be great if beneficiary medical cards include a QR code linking their basic immunization and health records.', 'pending', 'medium', NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-12 08:18:45', NULL),
(3, 'TKT-2026-1003', 'Mohammed Farhan', '+91 9723456789', 'farhan.m@example.com', 'complaint', 'Receipt Download Link Not Working', 'I made a clothes donation yesterday but the instant SMS receipt download link showed a server timeout.', 'resolved', 'medium', 'The issue has been resolved and your receipt PDF has been resent to your verified email address.', 2, '2026-09-12 11:48:45', '2026-09-12 13:48:45', NULL, NULL, '2026-09-12 08:18:45', NULL),
(6, 'TKT-TEST-6317', 'Ramesh Kumar', '+91 9876543210', 'ramesh.test@example.com', 'complaint', 'Street light issue near community center', 'The street lights near the community hall are broken for 2 weeks.', 'pending', 'medium', NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-12 08:31:25', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('New','Replied') DEFAULT 'New',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `message`, `status`, `created_at`) VALUES
(1, 'shndgcm', 'mourya.rajkumar25@gmail.com', 'afwefwefwef', 'Replied', '2026-03-23 13:14:22'),
(2, 'anuj upadhyay', 'anujbca2022@gmail.com', 'anuj upadhyay', 'Replied', '2026-04-06 10:43:58'),
(3, 'Velnix Soft', 'velnixsoft@gmail.com', 'mujhe aap ke ngo mai donation karna hai', 'Replied', '2026-09-02 09:57:54');

-- --------------------------------------------------------

--
-- Table structure for table `course_applications`
--

CREATE TABLE `course_applications` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `father_name` varchar(100) DEFAULT NULL,
  `mobile` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `qualification` varchar(100) DEFAULT NULL,
  `course` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `crowdfunding_campaigns`
--

CREATE TABLE `crowdfunding_campaigns` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `goal_amount` decimal(12,2) NOT NULL,
  `raised_amount` decimal(12,2) DEFAULT 0.00,
  `image_path` varchar(255) DEFAULT NULL,
  `status` enum('Active','Completed','Paused') DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `custom_receipts`
--

CREATE TABLE `custom_receipts` (
  `id` int(11) NOT NULL,
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
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctor_agreements`
--

CREATE TABLE `doctor_agreements` (
  `id` int(11) NOT NULL,
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
  `certificate_no` varchar(50) DEFAULT NULL,
  `certificate_pdf_path` varchar(255) DEFAULT NULL,
  `certificate_issued_at` datetime DEFAULT NULL,
  `status` enum('active','pending_signature','under_renewal','expired','terminated') NOT NULL DEFAULT 'active',
  `is_acknowledged` tinyint(1) NOT NULL DEFAULT 0,
  `acknowledged_at` datetime DEFAULT NULL,
  `acknowledged_name` varchar(150) DEFAULT NULL,
  `acknowledged_ip` varchar(45) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `doctor_agreements`
--

INSERT INTO `doctor_agreements` (`id`, `agreement_no`, `partner_id`, `agreement_title`, `doctor_name`, `speciality`, `discount_terms`, `agreement_doc_path`, `file_size`, `signed_date`, `valid_until`, `certificate_no`, `certificate_pdf_path`, `certificate_issued_at`, `status`, `is_acknowledged`, `acknowledged_at`, `acknowledged_name`, `acknowledged_ip`, `remarks`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'AGR-DR-2026-001', 1, 'Annual Doctor Empanelment & Free Consultation MOU', 'Drishti Eye & Retina Super Speciality Hospital', 'eye', '25% Discount on OPD & 100% Free Consultation for Verified Health Card Holders', 'uploads/documents/doctor_agreement_01.pdf', '320 KB', '2026-01-15', '2027-01-14', 'DOC-CERT-2026-0001', 'uploads/documents/doctor_certificates/Doctor_Certificate_DOC-CERT-2026-0001.pdf', '2026-09-12 15:38:06', 'active', 0, NULL, NULL, NULL, 'Approved under Jaysmrutti Swasthya Suraksha Scheme.', NULL, '2026-09-12 10:02:51', '2026-09-12 10:08:06'),
(2, 'TEST-AGR-1789207610', 1, 'Annual Doctor Empanelment & Free Consultation Test MOU', 'Dr. Vikramaditya Rathore, MS (Eye)', 'Ophthalmology & Eye Care', '30% Discount on Specialized Surgeries & 100% Free OPD for Health Card Holders', NULL, NULL, '2026-03-01', '2027-02-28', 'DOC-CERT-2026-0002', 'uploads/documents/doctor_certificates/Doctor_Certificate_DOC-CERT-2026-0002.pdf', '2026-09-12 17:06:26', 'active', 0, NULL, NULL, NULL, 'Verified under test suite.', NULL, '2026-09-12 10:06:50', '2026-09-12 11:36:26');

-- --------------------------------------------------------

--
-- Table structure for table `donations`
--

CREATE TABLE `donations` (
  `id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `donor_name` varchar(100) NOT NULL,
  `donor_email` varchar(100) DEFAULT NULL,
  `donor_mobile` varchar(20) DEFAULT NULL,
  `donor_pan` varchar(20) DEFAULT NULL,
  `donor_address` text DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_gateway` varchar(50) NOT NULL DEFAULT 'Razorpay',
  `transaction_id` varchar(100) DEFAULT NULL,
  `razorpay_order_id` varchar(120) DEFAULT NULL,
  `razorpay_payment_id` varchar(120) DEFAULT NULL,
  `razorpay_signature` varchar(255) DEFAULT NULL,
  `payment_screenshot` varchar(255) DEFAULT NULL,
  `payment_status` enum('Pending','Success','Failed') DEFAULT 'Pending',
  `receipt_no` varchar(50) DEFAULT NULL,
  `referral_code` varchar(40) DEFAULT NULL,
  `is_80g_eligible` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `achievement_processed` tinyint(1) NOT NULL DEFAULT 0,
  `sa_student_id` int(11) DEFAULT NULL,
  `recurring_donation_id` int(11) DEFAULT NULL,
  `is_recurring` tinyint(1) NOT NULL DEFAULT 0,
  `collection_city` varchar(120) DEFAULT NULL,
  `field_agent_id` int(11) DEFAULT NULL,
  `collection_area` varchar(100) DEFAULT NULL,
  `payment_mode_field` enum('cash','online','cheque') DEFAULT 'cash'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `donations`
--

INSERT INTO `donations` (`id`, `project_id`, `donor_name`, `donor_email`, `donor_mobile`, `donor_pan`, `donor_address`, `amount`, `payment_gateway`, `transaction_id`, `razorpay_order_id`, `razorpay_payment_id`, `razorpay_signature`, `payment_screenshot`, `payment_status`, `receipt_no`, `referral_code`, `is_80g_eligible`, `created_at`, `verified_by`, `verified_at`, `achievement_processed`, `sa_student_id`, `recurring_donation_id`, `is_recurring`, `collection_city`, `field_agent_id`, `collection_area`, `payment_mode_field`) VALUES
(7, NULL, 'Abhishek Kumar', 'panditabhishek9651@gmail.com', '9651826737', '', NULL, 500.00, 'Razorpay', 'pay_TDhhssv8zftmNj', 'order_TDhhh250ar8opC', 'pay_TDhhssv8zftmNj', '420d69a10ea1d2b9aabe6e7e457442fdabe4dd5e0426fe70c956ae749cc306ad', NULL, 'Success', 'R2026-00001', NULL, 0, '2026-07-15 07:55:04', NULL, NULL, 0, NULL, NULL, 0, NULL, NULL, NULL, 'cash'),
(8, 13, 'Velnix Soft', 'velnixsoft@gmail.com', '7651910331', 'KSYPK8808N', NULL, 500.00, 'Manual', 'T2608300755315474719115', NULL, NULL, NULL, 'uploads/donations/1788342515_6a97f0f39f584_payment.jpeg', 'Success', 'R2026-00002', NULL, 1, '2026-09-02 09:48:35', NULL, NULL, 0, NULL, NULL, 0, NULL, NULL, NULL, 'cash');

-- --------------------------------------------------------

--
-- Table structure for table `donation_achievements`
--

CREATE TABLE `donation_achievements` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `milestone_id` int(11) NOT NULL,
  `milestone_amount` decimal(12,2) NOT NULL,
  `cumulative_donation_amount` decimal(12,2) NOT NULL,
  `reward_type` enum('points','badge','both') NOT NULL,
  `points_awarded` int(11) DEFAULT 0,
  `badge_awarded` varchar(80) DEFAULT NULL,
  `reference_donation_id` int(11) DEFAULT NULL,
  `achieved_at` datetime NOT NULL DEFAULT current_timestamp(),
  `processed_by_cron` tinyint(1) NOT NULL DEFAULT 0,
  `cron_timestamp` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `donation_milestones`
--

CREATE TABLE `donation_milestones` (
  `id` int(11) NOT NULL,
  `milestone_amount` decimal(12,2) NOT NULL,
  `reward_type` enum('points','badge','both') NOT NULL DEFAULT 'points',
  `reward_points` int(11) DEFAULT 0,
  `badge_code` varchar(80) DEFAULT NULL,
  `milestone_name` varchar(255) NOT NULL,
  `milestone_description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `donation_milestones`
--

INSERT INTO `donation_milestones` (`id`, `milestone_amount`, `reward_type`, `reward_points`, `badge_code`, `milestone_name`, `milestone_description`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 500.00, 'points', 25, NULL, 'Donation Starter', 'First ₹500 collected - Entry to fundraising!', 1, 1, '2026-06-06 12:15:37', '2026-06-06 12:15:37'),
(2, 2000.00, 'points', 75, NULL, 'Donation Enthusiast', 'Reached ₹2000 in donations - Growing impact!', 1, 2, '2026-06-06 12:15:37', '2026-06-06 12:15:37'),
(3, 10000.00, 'both', 200, 'DONATION_MILESTONE_CHAMPION', 'Donation Champion', 'Achieved ₹10000 in donations - Major impact!', 1, 3, '2026-06-06 12:15:37', '2026-06-06 12:15:37');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `event_date` date NOT NULL,
  `location` varchar(500) DEFAULT NULL,
  `registration_open` tinyint(1) DEFAULT 1,
  `max_registrations` int(11) DEFAULT NULL,
  `gallery_path` varchar(255) DEFAULT NULL,
  `status` enum('Upcoming','Live','Completed','Cancelled') DEFAULT 'Upcoming',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `title`, `description`, `event_date`, `location`, `registration_open`, `max_registrations`, `gallery_path`, `status`, `created_at`, `created_by`) VALUES
(6, 'Free Community Health And Awareness Camp', 'Chahakta Angan Foundation is organizing a Free Community Health And Awareness Camp to provide basic health check-ups, medical consultations, health education, and wellness awareness for underprivileged families. The event aims to promote preventive healthcare, healthy living, and community well-being through expert guidance and free medical support.', '2026-01-10', 'Jaunpur', 0, NULL, NULL, 'Completed', '2026-08-21 09:05:52', 2);

-- --------------------------------------------------------

--
-- Table structure for table `event_gallery`
--

CREATE TABLE `event_gallery` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_registrations`
--

CREATE TABLE `event_registrations` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `member_id` int(11) DEFAULT NULL,
  `sa_student_id` int(11) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `status` enum('Registered','Cancelled','Attended') DEFAULT 'Registered',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
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
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `expense_code`, `category_id`, `project_id`, `amount`, `date`, `purpose`, `payment_mode`, `reference_no`, `vendor_payee_name`, `bill_document_path`, `added_by`, `approved_status`, `approved_by`, `approved_at`, `rejection_reason`, `remarks`, `created_at`, `updated_at`) VALUES
(2, 'EXP-2026-0002', 1, 9, 100.00, '2026-09-13', 'visiting schools', 'Cash', NULL, NULL, NULL, 2, 'Approved', 2, '2026-09-13 12:52:17', NULL, 'Anuj gaya visit karne', '2026-09-13 07:22:17', '2026-09-13 07:22:17');

-- --------------------------------------------------------

--
-- Table structure for table `expense_categories`
--

CREATE TABLE `expense_categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `category_slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(100) DEFAULT 'fa-receipt',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `expense_categories`
--

INSERT INTO `expense_categories` (`id`, `category_name`, `category_slug`, `description`, `icon`, `is_active`, `display_order`, `created_at`, `updated_at`) VALUES
(1, 'Travel', 'travel', 'Field visit transport, fuel, vehicle rent, conveyance, and travel allowances', 'fa-car-side', 1, 1, '2026-09-12 07:47:45', NULL),
(2, 'Event', 'event', 'Community awareness drives, relief camps, stage setup, and event logistics', 'fa-calendar-check', 1, 2, '2026-09-12 07:47:45', NULL),
(3, 'Utility', 'utility', 'Electricity, water, high-speed internet, telephone, and recurring utility bills', 'fa-bolt', 1, 3, '2026-09-12 07:47:45', NULL),
(4, 'Staff', 'staff', 'Staff honorarium, coordinator stipends, volunteer welfare, and refreshments', 'fa-users-gear', 1, 4, '2026-09-12 07:47:45', NULL),
(5, 'Office', 'office', 'Office stationery, printing, rent, software subscriptions, and maintenance', 'fa-building', 1, 5, '2026-09-12 07:47:45', NULL),
(6, 'Project', 'project', 'Direct welfare material purchases, aid procurement, and project execution costs', 'fa-seedling', 1, 6, '2026-09-12 07:47:45', NULL),
(7, 'Other', 'other', 'Miscellaneous operational costs, bank charges, legal fees, and sundry expenses', 'fa-layer-group', 1, 7, '2026-09-12 07:47:45', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `feedbacks`
--

CREATE TABLE `feedbacks` (
  `id` int(11) NOT NULL,
  `feedback_no` varchar(50) NOT NULL,
  `submitter_type` enum('employee','member','volunteer','field_agent','donor','other') NOT NULL DEFAULT 'member',
  `user_identifier` varchar(100) DEFAULT NULL COMMENT 'Member ID / Employee Code / Volunteer Reg ID',
  `name` varchar(100) NOT NULL,
  `contact` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `category` varchar(80) NOT NULL DEFAULT 'general_suggestion',
  `rating` tinyint(4) NOT NULL DEFAULT 5,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_anonymous` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('pending','under_review','action_taken','closed') NOT NULL DEFAULT 'pending',
  `priority` enum('low','medium','high') NOT NULL DEFAULT 'medium',
  `admin_reply` text DEFAULT NULL,
  `reply_by` int(11) DEFAULT NULL,
  `replied_at` datetime DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `user_ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `feedbacks`
--

INSERT INTO `feedbacks` (`id`, `feedback_no`, `submitter_type`, `user_identifier`, `name`, `contact`, `email`, `department`, `category`, `rating`, `subject`, `message`, `is_anonymous`, `status`, `priority`, `admin_reply`, `reply_by`, `replied_at`, `resolved_at`, `attachment_path`, `user_ip`, `created_at`, `updated_at`) VALUES
(1, 'FB-2026-0001', 'employee', 'EMP-OPS-102', 'Ramesh Sharma', '+91 9876543210', 'ramesh.sharma@velnixsoft.com', 'Field Operations', 'workplace_environment', 4, 'Field Kit & Digital Tablet Update Request', 'Our rural survey team requires updated digital tablets with offline form caching so that beneficiary entries in low-network regions sync smoothly.', 0, 'action_taken', 'high', 'Approved by Management. 10 new high-battery tablets with offline sync have been dispatched to District Coordinators.', NULL, NULL, NULL, NULL, NULL, '2026-09-08 09:58:25', NULL),
(2, 'FB-2026-0002', 'member', 'MEM-2026-8841', 'Pooja Verma', '+91 9823456781', 'pooja.verma@example.com', 'Community Health', 'program_execution', 5, 'Commendable Medical Camp at Basti Division', 'The free health checkup and medicine distribution camp was exceptionally organized. We suggest organizing such camps bi-monthly.', 0, 'closed', 'medium', 'Thank you for your valuable appreciation. We have scheduled the next follow-up health camp for next month.', NULL, NULL, NULL, NULL, NULL, '2026-09-10 09:58:25', NULL),
(3, 'FB-2026-0003', 'volunteer', 'VOL-7721', 'Ankit Tripathi', '+91 9765432190', 'ankit.volunteer@gmail.com', 'Youth Programs', 'training_guidance', 5, 'Pre-Event Briefing & ID Badges Delivery', 'Volunteers enjoyed the tree plantation drive. It would be helpful if digital ID badges and task sheets are emailed 24 hours prior to future events.', 0, 'under_review', 'medium', 'Noted Ankit! The Event Coordination wing has automated 24-hour pre-event digital kit dispatches.', NULL, NULL, NULL, NULL, NULL, '2026-09-11 09:58:25', NULL),
(4, 'FB-2026-0004', 'employee', 'EMP-ACC-04', 'Anonymous Staff Member', NULL, NULL, 'Accounts & Finance', 'compensation_benefits', 4, 'Suggestions for Annual Health Checkup Reimbursement', 'Requesting clarity on the process for OPD and diagnostic test reimbursements under the updated staff health policy.', 1, 'pending', 'medium', NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-12 09:58:25', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `field_agents`
--

CREATE TABLE `field_agents` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `state` varchar(50) NOT NULL,
  `area` varchar(100) NOT NULL,
  `base_salary_monthly` decimal(10,2) DEFAULT 0.00,
  `incentive_rate_percent` decimal(5,2) DEFAULT 2.00,
  `min_attendance_days` int(11) DEFAULT 20,
  `target_amount_monthly` decimal(10,2) DEFAULT 0.00,
  `collected_amount_monthly` decimal(10,2) DEFAULT 0.00,
  `attendance_status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `field_agents`
--

INSERT INTO `field_agents` (`id`, `user_id`, `state`, `area`, `base_salary_monthly`, `incentive_rate_percent`, `min_attendance_days`, `target_amount_monthly`, `collected_amount_monthly`, `attendance_status`, `created_at`) VALUES
(1, 7, 'Uttar Pradesh', '', 18000.00, 2.50, 20, 50000.00, 0.00, 'active', '2026-06-06 06:45:39'),
(2, 8, 'Uttar Pradesh', 'Lucknow', 18000.00, 2.50, 20, 50000.00, 0.00, 'active', '2026-06-06 06:45:39'),
(3, 9, 'Uttar Pradesh', 'Lucknow', 18000.00, 2.50, 20, 50000.00, 0.00, 'active', '2026-06-06 06:45:39');

-- --------------------------------------------------------

--
-- Table structure for table `gallery`
--

CREATE TABLE `gallery` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `type` enum('image','video') DEFAULT 'image',
  `file_path` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `gallery`
--

INSERT INTO `gallery` (`id`, `title`, `type`, `file_path`, `created_at`) VALUES
(5, 'Green India', 'image', 'uploads/gallery/1788169527_6a954d371637a_WhatsApp Image 2026-08-26 at 6.24.17 PM.jpeg', '2026-08-31 09:45:27'),
(6, 'Green India', 'image', 'uploads/gallery/1788169564_6a954d5cab0e5_WhatsApp Image 2026-08-26 at 6.23.56 PM.jpeg', '2026-08-31 09:46:04'),
(7, 'Green India', 'image', 'uploads/gallery/1788169586_6a954d72962b5_WhatsApp Image 2026-08-26 at 5.44.55 PM.jpeg', '2026-08-31 09:46:26'),
(8, 'Green India', 'image', 'uploads/gallery/1788169604_6a954d845a29a_WhatsApp Image 2026-08-26 at 5.44.24 PM.jpeg', '2026-08-31 09:46:44'),
(9, 'Eductaion', 'image', 'uploads/gallery/1788169622_6a954d962b16c_WhatsApp Image 2026-08-26 at 5.43.39 PM.jpeg', '2026-08-31 09:47:02'),
(10, 'Envoierment', 'image', 'uploads/gallery/1788169655_6a954db709775_WhatsApp Image 2026-08-26 at 5.46.14 PM.jpeg', '2026-08-31 09:47:35'),
(11, 'Biodiversity Conservation Awareness Programme', 'image', 'uploads/gallery/1788169680_6a954dd092091_WhatsApp Image 2026-08-26 at 5.46.15 PM.jpeg', '2026-08-31 09:48:00');

-- --------------------------------------------------------

--
-- Table structure for table `healthcare_providers`
--

CREATE TABLE `healthcare_providers` (
  `id` int(11) NOT NULL,
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
  `latitude` decimal(10,8) DEFAULT NULL COMMENT 'Latitude',
  `longitude` decimal(11,8) DEFAULT NULL COMMENT 'Longitude',
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
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `healthcare_providers`
--

INSERT INTO `healthcare_providers` (`id`, `provider_code`, `name`, `type`, `speciality`, `speciality_custom`, `contact_person`, `contact`, `alternate_contact`, `email`, `website`, `state`, `district`, `block`, `pincode`, `address`, `landmark`, `map_location`, `latitude`, `longitude`, `map_embed_url`, `photo`, `timing`, `emergency_available`, `discount_offered`, `services_offered`, `remarks`, `status`, `is_verified`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'HCP-2026-0001', 'Drishti Eye & Retina Super Speciality Hospital', 'hospital', 'eye', 'Cataract & Retina Care', 'Dr. Alok Verma (MS Ophthal)', '+91 98765 43210', '+91 98765 43211', 'info@drishtieye.org', NULL, 'Delhi', 'New Delhi', 'Connaught Place', '110001', 'Plot 14, Health Park, Barakhamba Road', 'Near Metro Gate No 3', 'https://maps.google.com/?q=28.628929,77.221532', 28.62892900, 77.22153200, 'https://maps.google.com/maps?q=28.628929,77.221532&hl=en&z=15&output=embed', NULL, 'Mon - Sat: 08:30 AM - 07:30 PM', 1, '100% Free Cataract Surgery for BPL Card Holders & 30% Off for Senior Citizens', 'Free Eye Checkup Camps, Phacoemulsification, Glaucoma screening, Diabetic Retinopathy, Spectacle distribution', 'Official partner hospital for NGO rural eye care missions.', 'active', 1, NULL, '2026-09-12 08:47:37', '2026-09-12 10:17:58'),
(2, 'HCP-2026-0002', 'SmileCare Advanced Dental Clinic & Implant Center', 'clinic', 'dental', 'Orthodontics & Implants', 'Dr. Neha Sharma (BDS, MDS)', '+91 98111 22334', '+91 98111 22335', 'care@smilecaredental.com', NULL, 'Uttar Pradesh', 'Lucknow', 'Hazratganj', '226001', 'Shop 5-6, City Centre Mall, MG Marg', 'Opposite Gandhi Ashram', 'https://maps.google.com/?q=26.8467088,80.946166', 26.84670880, 80.94616600, 'https://maps.google.com/maps?q=26.8467088,80.946166&hl=en&z=15&output=embed', NULL, 'Mon - Sat: 10:00 AM - 08:00 PM', 0, 'Free Dental Consultation & 50% discount on Root Canal (RCT) & Scaling', 'Dental Scaling, Root Canal Treatment, Tooth Extractions, Pediatric Dental Care, Dentures', 'Equipped with modern digital RVG X-ray unit.', 'active', 1, NULL, '2026-09-12 08:47:37', '2026-09-12 10:17:58'),
(3, 'HCP-2026-0003', 'Apex Diagnostics & Pathology Center', 'pathology_lab', 'other', 'Advanced Diagnostic & Biochemistry', 'Dr. Rajesh Gupta (MD Path)', '+91 99222 33445', '+91 99222 33446', 'lab@apexdiagnostics.in', NULL, 'Maharashtra', 'Mumbai Suburban', 'Andheri East', '400069', 'Building 2, Metro Plaza, Andheri-Kurla Road', 'Beside Western Express Highway Metro', 'https://maps.google.com/?q=19.113645,72.869734', 19.11364500, 72.86973400, 'https://maps.google.com/maps?q=19.113645,72.869734&hl=en&z=15&output=embed', NULL, '24x7 Open (Emergency Sample Collection)', 1, '40% flat discount on all Blood Profiles, Thyroid, Diabetes & Lipid Tests for NGO Referrals', 'CBC, HbA1c, Liver Function Test (LFT), Kidney Function Test (KFT), Digital X-Ray, ECG, Ultrasound', 'NABL Accredited Laboratory with automated analyzers.', 'active', 1, NULL, '2026-09-12 08:47:37', '2026-09-12 10:17:58'),
(4, 'HCP-2026-0004', 'Jan Aushadhi Seva Pharmacy', 'pharmacy', 'other', 'Generic & Essential Medicines', 'Manoj Kumar (D.Pharm)', '+91 97333 44556', NULL, 'janaushadhi.care@gmail.com', NULL, 'Bihar', 'Patna', 'Kankarbagh', '800020', 'Main Road, Near Old Bus Stand, Kankarbagh', 'Near State Bank ATM', 'https://maps.google.com/?q=25.594095,85.137566', 25.59409500, 85.13756600, 'https://maps.google.com/maps?q=25.594095,85.137566&hl=en&z=15&output=embed', NULL, 'All 7 Days: 08:00 AM - 10:00 PM', 0, 'Up to 70-80% savings on generic critical medicines & free BP/Sugar check', 'Essential Antibiotics, Cardiac & Diabetes Care, Pediatric syrups, Ortho aids, First Aid Kits', 'Authorized Jan Aushadhi generic dispensary partner.', 'active', 1, NULL, '2026-09-12 08:47:37', '2026-09-12 10:17:58'),
(5, 'HCP-2026-0005', 'Dr. Arvind Mehra (General Physician & Cardiologist)', 'doctor', 'other', 'Internal Medicine & Cardiology', 'Dr. Arvind Mehra (MD Medicine)', '+91 96444 55667', NULL, 'dr.mehra@cliniccare.in', NULL, 'Rajasthan', 'Jaipur', 'Malviya Nagar', '302017', 'Clinic No 12, Health Square, Calgiri Marg', 'Opposite Fortis Hospital', 'https://maps.google.com/?q=26.912434,75.787271', 26.91243400, 75.78727100, 'https://maps.google.com/maps?q=26.912434,75.787271&hl=en&z=15&output=embed', NULL, 'Mon - Fri: 04:00 PM - 08:00 PM', 0, 'Free Consultation for Underprivileged Patients referred by NGO', 'Hypertension management, Diabetes screening, ECG interpretation, Preventative cardiac consultation', 'Available for weekend rural medical camps.', 'active', 1, NULL, '2026-09-12 08:47:37', '2026-09-12 10:17:58');

-- --------------------------------------------------------

--
-- Table structure for table `healthcare_referrals`
--

CREATE TABLE `healthcare_referrals` (
  `id` int(11) NOT NULL,
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
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `healthcare_services`
--

CREATE TABLE `healthcare_services` (
  `id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `service_name` varchar(200) NOT NULL,
  `category` varchar(100) NOT NULL DEFAULT 'Consultation',
  `standard_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discounted_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_free_for_bpl` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `health_cards`
--

CREATE TABLE `health_cards` (
  `id` int(11) NOT NULL,
  `card_number` varchar(50) NOT NULL,
  `previous_card_number` varchar(50) DEFAULT NULL,
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
  `status` enum('pending_approval','active','expired','renewed','rejected','blocked') NOT NULL DEFAULT 'pending_approval',
  `beneficiary_id` int(11) DEFAULT NULL,
  `member_id` int(11) DEFAULT NULL,
  `qr_code_path` varchar(255) DEFAULT NULL,
  `pdf_path` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `issued_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `health_cards`
--

INSERT INTO `health_cards` (`id`, `card_number`, `previous_card_number`, `applicant_name`, `contact`, `email`, `dob`, `age`, `gender`, `blood_group`, `aadhaar_no`, `emergency_contact`, `state`, `district`, `block`, `pincode`, `address`, `photo`, `issue_date`, `expiry_date`, `status`, `beneficiary_id`, `member_id`, `qr_code_path`, `pdf_path`, `remarks`, `issued_by`, `created_at`, `updated_at`) VALUES
(1, 'HC-2026-0001', NULL, 'Ramesh Kumar Verma', '+91 98765 11223', 'ramesh.verma@example.com', '1982-05-14', 44, 'Male', 'B+', 'XXXX-XXXX-4512', '+91 98765 11224', 'Uttar Pradesh', 'Lucknow', 'Hazratganj', '226001', 'House No. 45, Sector 4, Vikas Nagar', NULL, '2026-01-10', '2027-01-09', 'active', NULL, NULL, NULL, NULL, 'Eligible for 100% Free Cataract Surgery and OPD Subsidies.', NULL, '2026-09-12 08:56:01', NULL),
(2, 'HC-2026-0002', NULL, 'Sunita Devi Sharma', '+91 98111 55667', 'sunita.sharma@example.com', '1990-08-22', 36, 'Female', 'O+', 'XXXX-XXXX-8921', '+91 98111 55668', 'Delhi', 'New Delhi', 'Connaught Place', '110001', 'Flat 12B, Barakhamba Lane', NULL, '2026-02-15', '2027-02-14', 'active', NULL, NULL, NULL, NULL, 'BPL Card Holder - Free generic medicines from partner Jan Aushadhi pharmacy.', NULL, '2026-09-12 08:56:01', NULL),
(3, 'HC-2026-0003', NULL, 'Mohammad Imran Sheikh', '+91 99222 77889', 'imran.sheikh@example.com', '1975-11-03', 51, 'Male', 'AB+', 'XXXX-XXXX-3341', '+91 99222 77890', 'Maharashtra', 'Mumbai Suburban', 'Andheri East', '400069', 'Plot 88, Metro Nagar, Kurla Road', NULL, '2025-01-01', '2026-01-01', 'expired', NULL, NULL, NULL, NULL, 'Card expired. Renewal application pending.', NULL, '2026-09-12 08:56:01', NULL),
(6, 'HC-2026-0004', NULL, 'Ramesh Kumar Sharma', '9876543210', 'ramesh.sharma@example.com', '1988-05-14', NULL, 'Male', 'B+', NULL, NULL, 'Bihar', 'Patna', 'Patna Sadar', NULL, 'House 45, Gandhi Nagar, Patna', NULL, '2026-09-12', '2027-09-12', 'active', NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=http%3A%2F%2Flocalhost%2Fverify-health-card.php%3Fcard%3DHC-2026-0004', 'uploads/health_cards/Health_Card_HC-2026-0004_1789204012.pdf', 'Approved during test', NULL, '2026-09-12 09:06:52', '2026-09-12 09:06:53'),
(7, '', NULL, 'Sunita Verma', '9123456780', 'sunita@example.com', NULL, NULL, 'Male', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, '0000-00-00', '0000-00-00', 'rejected', NULL, NULL, NULL, NULL, 'Invalid proof of identity', NULL, '2026-09-12 09:06:53', '2026-09-12 09:06:53'),
(8, 'HC-2025-9999', NULL, 'Vikramaditya Singh', '9876500001', 'vikram@example.com', '1985-06-20', 41, 'Male', 'O+', 'XXXX-XXXX-9999', '9876500002', 'Madhya Pradesh', 'Bhopal', 'Huzur', '462001', 'B-12, Arera Colony, Bhopal', NULL, '2025-01-01', '2026-01-01', 'renewed', NULL, NULL, NULL, NULL, 'Initial annual issue', NULL, '2026-09-12 09:11:13', '2026-09-12 09:11:13'),
(9, 'HC-2026-0005', 'HC-2025-9999', 'Vikramaditya Singh', '9876500001', 'vikram@example.com', '1985-06-20', 41, 'Male', 'O+', 'XXXX-XXXX-9999', '9876500002', 'Madhya Pradesh', 'Bhopal', 'Huzur', '462001', 'B-12, Arera Colony, Bhopal', NULL, '2026-09-12', '2027-09-12', 'active', NULL, NULL, 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=http%3A%2F%2Flocalhost%2Fverify-health-card.php%3Fcard%3DHC-2026-0005', 'uploads/health_cards/Health_Card_HC-2026-0005_1789204273.pdf', 'Renewal application for previous Health Card: HC-2025-9999', NULL, '2026-09-12 09:11:13', '2026-09-12 09:11:15');

-- --------------------------------------------------------

--
-- Table structure for table `health_programs`
--

CREATE TABLE `health_programs` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `category` varchar(100) NOT NULL DEFAULT 'Health Awareness',
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `health_programs`
--

INSERT INTO `health_programs` (`id`, `title`, `description`, `image`, `category`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Free Medical & Diagnostic Camp', 'Comprehensive healthcare camps providing free health checkups, blood tests, sugar tests, and consultations with qualified doctors for the general public.', 'https://images.unsplash.com/photo-1576091160550-2173dba999ef?q=80&w=600&auto=format&fit=crop', 'Medical Camp', 'Active', '2026-08-21 07:57:26', '2026-08-21 07:57:26'),
(2, 'Mental Health & Stress Seminars', 'Interactive mental wellbeing and stress management sessions held in local colleges and communities to eliminate stigma and teach positive coping tools.', 'https://images.unsplash.com/photo-1582213782179-e0d53f98f2ca?q=80&w=600&auto=format&fit=crop', 'Mental Health', 'Active', '2026-08-21 07:57:26', '2026-08-21 07:57:26'),
(3, 'Women Hygiene & Health Drive', 'Special awareness campaigns focused on women health, nutrition, sanitisation practices, and distribution of wellness kits to underprivileged areas.', 'https://images.unsplash.com/photo-1559839734-2b71ea197ec2?q=80&w=600&auto=format&fit=crop', 'Women Wellness', 'Active', '2026-08-21 07:57:26', '2026-08-21 07:57:26');

-- --------------------------------------------------------

--
-- Table structure for table `hr_policies`
--

CREATE TABLE `hr_policies` (
  `id` int(11) NOT NULL,
  `policy_code` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `category` enum('code_of_conduct','posh_gender','child_safeguarding','leave_benefits','whistleblower','travel_compensation','volunteer_ethics','general') NOT NULL DEFAULT 'code_of_conduct',
  `category_custom` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `content_html` longtext DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_size` varchar(50) DEFAULT NULL,
  `policy_version` varchar(20) DEFAULT 'v1.0',
  `effective_date` date DEFAULT NULL,
  `review_date` date DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hr_policies`
--

INSERT INTO `hr_policies` (`id`, `policy_code`, `title`, `category`, `category_custom`, `description`, `content_html`, `file_path`, `file_size`, `policy_version`, `effective_date`, `review_date`, `is_public`, `sort_order`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'HRP-COC-01', 'Code of Conduct & Workplace Professional Ethics', 'code_of_conduct', NULL, 'Establishes standards of honesty, integrity, gender sensitivity, conflict of interest, anti-discrimination, and respectful behavior for all NGO employees, interns, and field associates.', NULL, 'uploads/documents/sample_code_of_conduct.pdf', '1.2 MB', 'v2.1', '2026-01-01', NULL, 1, 1, NULL, '2026-09-12 09:54:07', '2026-09-12 09:54:07'),
(2, 'HRP-POSH-02', 'Prevention of Sexual Harassment (POSH) & Gender Safety Policy', 'posh_gender', NULL, 'Mandated by the POSH Act 2013; provides zero-tolerance framework against harassment, Internal Complaints Committee (ICC) redressal mechanisms, and confidential reporting channels for all female staff and volunteers.', NULL, 'uploads/documents/sample_posh_policy.pdf', '980 KB', 'v2.0', '2026-01-15', NULL, 1, 2, NULL, '2026-09-12 09:54:07', '2026-09-12 09:54:07'),
(3, 'HRP-CSG-03', 'Child Protection & Safeguarding (PSEA) Framework', 'child_safeguarding', NULL, 'Rigorous protocols for safeguarding vulnerable children, beneficiaries, and adolescent students during field operations, medical camps, and education programs against abuse and exploitation.', NULL, 'uploads/documents/sample_child_safeguarding.pdf', '1.5 MB', 'v1.8', '2026-02-01', NULL, 1, 3, NULL, '2026-09-12 09:54:07', '2026-09-12 09:54:07'),
(4, 'HRP-LEV-04', 'Staff Leave, Working Hours, Health & Social Benefits Policy', 'leave_benefits', NULL, 'Governs casual leave, earned leave, maternity/paternity support, medical benefits, Swasthya Card coverage, provident fund compliance, and remote field work allowances.', NULL, 'uploads/documents/sample_leave_policy.pdf', '850 KB', 'v2.0', '2026-01-01', NULL, 1, 4, NULL, '2026-09-12 09:54:07', '2026-09-12 09:54:07'),
(5, 'HRP-WB-05', 'Whistleblower, Anti-Bribery & Fraud Reporting Policy', 'whistleblower', NULL, 'Encourages employees, donors, and stakeholders to confidentially report financial fraud, corruption, or procedural non-compliance without fear of retaliation or victimization.', NULL, 'uploads/documents/sample_whistleblower.pdf', '720 KB', 'v1.2', '2026-03-01', NULL, 1, 5, NULL, '2026-09-12 09:54:07', '2026-09-12 09:54:07'),
(6, 'HRP-TRV-06', 'Field Travel Allowance & Expense Reimbursement Norms', 'travel_compensation', NULL, 'Sets transparent per diem rates, travel booking guidelines, fuel reimbursements for field coordinators, and audit requirements for grassroots tours.', NULL, 'uploads/documents/sample_travel_policy.pdf', '640 KB', 'v1.1', '2026-02-15', NULL, 1, 6, NULL, '2026-09-12 09:54:07', '2026-09-12 09:54:07');

-- --------------------------------------------------------

--
-- Table structure for table `inquiries`
--

CREATE TABLE `inquiries` (
  `id` int(11) NOT NULL,
  `submitter_name` varchar(150) DEFAULT NULL,
  `submitter_email` varchar(150) DEFAULT NULL,
  `submitter_phone` varchar(20) DEFAULT NULL,
  `member_id` int(11) DEFAULT NULL,
  `problem_description` text NOT NULL,
  `category` varchar(80) DEFAULT NULL,
  `urgency` enum('normal','urgent','critical') DEFAULT 'normal',
  `attachment_path` varchar(255) DEFAULT NULL,
  `status` enum('New','In Progress','Resolved','Closed') DEFAULT 'New',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inquiries`
--

INSERT INTO `inquiries` (`id`, `submitter_name`, `submitter_email`, `submitter_phone`, `member_id`, `problem_description`, `category`, `urgency`, `attachment_path`, `status`, `admin_notes`, `created_at`) VALUES
(4, 'anuj upadhyay', 'anujbca2022@gmail.com', '7570032407', NULL, 'that i have problem certificate', 'membership', 'urgent', '../uploads/inquiries/inquiry_1777458728_d6a5debee28b7b43.jpeg', 'New', NULL, '2026-04-29 10:32:08'),
(5, 'Abhishek Kumar', 'panditabhishek9651@gmail.com', '9651826737', NULL, 'Mere sath proble ho rahi hai', 'membership', 'normal', NULL, 'New', NULL, '2026-08-21 10:16:34');

-- --------------------------------------------------------

--
-- Table structure for table `item_donations`
--

CREATE TABLE `item_donations` (
  `id` int(11) NOT NULL,
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
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `item_donation_categories`
--

CREATE TABLE `item_donation_categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `category_slug` varchar(100) NOT NULL,
  `category_icon` varchar(100) DEFAULT 'fa-box',
  `description` text DEFAULT NULL,
  `unit_suggestions` varchar(255) DEFAULT 'pcs, kg, boxes, sets, pairs, packets',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `item_donation_categories`
--

INSERT INTO `item_donation_categories` (`id`, `category_name`, `category_slug`, `category_icon`, `description`, `unit_suggestions`, `is_active`, `display_order`, `created_at`, `updated_at`) VALUES
(1, 'Clothes', 'clothes', 'fa-shirt', 'Men, women, and children clothing, winter wear, and daily apparel', 'pcs, pairs, boxes, sets', 1, 1, '2026-09-12 06:21:42', NULL),
(2, 'Ration', 'ration', 'fa-bowl-rice', 'Dry ration kits, rice, wheat, pulses, cooking oil, spices', 'kg, packets, kits, boxes', 1, 2, '2026-09-12 06:21:42', NULL),
(3, 'Books', 'books', 'fa-book-open', 'Educational books, school textbooks, notebooks, reference guides, storybooks', 'pcs, sets, boxes', 1, 3, '2026-09-12 06:21:42', NULL),
(4, 'Medicines', 'medicines', 'fa-pills', 'First-aid supplies, unexpired prescription & OTC medicines, medical disposables', 'boxes, strips, bottles, units', 1, 4, '2026-09-12 06:21:42', NULL),
(5, 'Stationery', 'stationery', 'fa-pen-ruler', 'Pens, pencils, notebooks, school bags, geometry boxes, art materials', 'pcs, sets, packets, boxes', 1, 5, '2026-09-12 06:21:42', NULL),
(6, 'Blankets', 'blankets', 'fa-bed', 'Warm winter blankets, quilts, bedsheets, woollen shawls', 'pcs, bundles', 1, 6, '2026-09-12 06:21:42', NULL),
(7, 'Wheelchairs', 'wheelchairs', 'fa-wheelchair', 'Wheelchairs, walking sticks, crutches, physical mobility & assistive aids', 'pcs, units', 1, 7, '2026-09-12 06:21:42', NULL),
(8, 'Food Materials', 'food-materials', 'fa-apple-whole', 'Prepared fresh meal packets, fruits, dry snacks, packaged drinking water', 'packets, boxes, kg, meals', 1, 8, '2026-09-12 06:21:42', NULL),
(9, 'Others', 'others', 'fa-box-open', 'Toys, electronics, appliances, furniture, and general utility items', 'pcs, units, sets', 1, 9, '2026-09-12 06:21:42', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `job_applications`
--

CREATE TABLE `job_applications` (
  `id` int(11) NOT NULL,
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
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `job_applications`
--

INSERT INTO `job_applications` (`id`, `application_no`, `job_id`, `applicant_name`, `contact`, `email`, `gender`, `dob`, `age`, `qualification`, `experience_years`, `current_city`, `state`, `district`, `address`, `resume_path`, `cover_letter`, `status`, `applied_date`, `interview_date`, `interview_venue`, `admin_notes`, `reviewed_by`, `created_at`, `updated_at`) VALUES
(1, 'APP-2026-0001', 1, 'Amitabh Sharma', '9876543210', 'amitabh.sharma@example.com', 'Male', '1992-04-15', 34, 'Master of Social Work (MSW)', 4.5, 'Lucknow', 'Uttar Pradesh', 'Lucknow', 'Plot 45, Aliganj, Lucknow, UP', 'uploads/resumes/sample_resume.pdf', 'I have 4+ years of experience in rural development and grassroots project coordination.', 'reviewed', '2026-09-11 05:00:00', NULL, NULL, NULL, NULL, '2026-09-12 09:23:16', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `job_openings`
--

CREATE TABLE `job_openings` (
  `id` int(11) NOT NULL,
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
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `job_openings`
--

INSERT INTO `job_openings` (`id`, `job_code`, `title`, `category`, `category_custom`, `description`, `requirements`, `responsibilities`, `location`, `state`, `district`, `block`, `openings_count`, `salary_range`, `job_type`, `experience_required`, `min_qualification`, `status`, `posted_date`, `last_date`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'JOB-2026-0001', 'State Program Coordinator', 'state_coordinator', NULL, 'Lead state-level development programs, manage district coordinators, liaise with government departments and partner NGOs for community health, education, and livelihood projects.', 'Strong leadership skills, proficiency in Hindi & English, experience in NGO/CSR project management, willingness to travel across districts.', 'Supervise district coordinators, monitor KPIs, submit monthly impact reports, organize state level review meetings.', 'State Head Office, Lucknow', 'Uttar Pradesh', 'Lucknow', NULL, 2, '₹35,000 - ₹50,000 / month', 'Full-time', '3-5 Years in NGO/Rural Dev', 'Post Graduate / MSW / MBA', 'active', '2026-09-01', '2026-10-31', NULL, '2026-09-12 09:23:16', NULL),
(2, 'JOB-2026-0002', 'District Operations Coordinator', 'district_coordinator', NULL, 'Oversee block level coordinators, execute health card drives, coordinate with empaneled hospitals, and manage volunteer activities across the district.', 'Good communication skills, local district knowledge, basic computer skills (MS Excel/Google Sheets), two-wheeler with valid license.', 'Onboard healthcare partners, organize health & donation camps, supervise block coordinators, verify beneficiary applications.', 'Patna District Office', 'Bihar', 'Patna', NULL, 5, '₹22,000 - ₹30,000 / month', 'Full-time', '1-3 Years in Field Operations', 'Graduate / BSW / Any Degree', 'active', '2026-09-05', '2026-10-25', NULL, '2026-09-12 09:23:16', NULL),
(3, 'JOB-2026-0003', 'Block Field Coordinator', 'block_coordinator', NULL, 'Grassroots coordinator responsible for panchayat outreach, beneficiary identification, swasthya health card enrollments, and organizing village meetings.', 'Active community presence, excellent interpersonal skills, mobile app handling skills.', 'Conduct door-to-door awareness, coordinate with Gram Pradhans, enroll citizens for health cards, report to district coordinator.', 'Varanasi Sadar Block', 'Uttar Pradesh', 'Varanasi', NULL, 12, '₹15,000 - ₹20,000 / month', 'Full-time', '0-2 Years / Freshers Welcome', '12th Pass / Graduate', 'active', '2026-09-08', '2026-11-15', NULL, '2026-09-12 09:23:16', NULL),
(4, 'JOB-2026-0004', 'Gram Panchayat Mobilizer & Coordinator', 'panchayat_coordinator', NULL, 'Village level representative to assist villagers in emergency health assistance, grievance logging, and NGO welfare initiatives.', 'Resident of local gram panchayat, trusted by community, basic smartphone usage.', 'Panchayat level awareness, distribution of health cards, guiding beneficiaries to network hospitals.', 'Gram Panchayat Level (Multi-location)', 'Madhya Pradesh', 'Bhopal', NULL, 25, '₹8,000 - ₹12,000 / month + Incentives', 'Contract', 'Fresher / Community Worker', '10th / 12th Pass', 'active', '2026-09-10', '2026-11-30', NULL, '2026-09-12 09:23:16', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `join_applications`
--

CREATE TABLE `join_applications` (
  `id` int(11) NOT NULL,
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
  `applied_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `join_applications`
--

INSERT INTO `join_applications` (`id`, `application_no`, `applicant_name`, `contact`, `email`, `state`, `district`, `application_type`, `project_id`, `job_id`, `details`, `fee_amount`, `payment_status`, `razorpay_order_id`, `razorpay_payment_id`, `razorpay_signature`, `payment_method`, `transaction_id`, `status`, `admin_notes`, `reviewed_by`, `reviewed_at`, `applied_date`, `created_at`, `updated_at`) VALUES
(1, 'JOIN-2026-0001', 'Aarav Sharma', '9876543210', 'aarav.sharma@example.com', NULL, NULL, 'join_foundation', NULL, NULL, 'Interested in joining Jaysmrutti Foundation as an active community volunteer to support child education and healthcare awareness campaigns.', 500.00, 'paid', NULL, NULL, NULL, 'UPI', 'UPI98374291823', 'approved', 'Onboarding welcome packet sent.', 2, '2026-09-12 16:22:44', '2026-09-07 10:52:44', '2026-09-12 10:52:44', '2026-09-12 10:52:44'),
(2, 'PROJ-JOIN-2026-0002', 'Priya Patel', '9812345678', 'priya.patel@example.com', NULL, NULL, 'join_project', 7, NULL, 'Want to volunteer for Project field operations in Lucknow district, focusing on rural healthcare and nutrition distribution.', NULL, 'exempted', NULL, NULL, NULL, 'Exempted', NULL, 'pending', 'Under project coordinator review.', NULL, NULL, '2026-09-10 10:52:44', '2026-09-12 10:52:44', '2026-09-12 10:52:44'),
(3, 'JOB-APP-2026-0003', 'Vikram Singh', '9898989898', 'vikram.singh@example.com', NULL, NULL, 'job_application', NULL, 1, 'Applying for District Coordinator position. 5 years experience in NGO grassroots outreach and project reporting.', 100.00, 'paid', NULL, NULL, NULL, 'Razorpay', 'pay_K98xZy781923', 'reviewed', 'Shortlisted for telephone screening.', 2, '2026-09-12 16:22:44', '2026-09-11 10:52:44', '2026-09-12 10:52:44', '2026-09-12 10:52:44'),
(4, 'JOIN-2026-0002', 'Aarav Sharma', '9876543210', 'aarav.sharma@example.com', 'Uttar Pradesh', 'Lucknow', 'join_foundation', NULL, NULL, 'Preferred Wing: Child Education & School Literacy | Want to volunteer on weekends', NULL, 'exempted', NULL, NULL, NULL, 'Exempted', NULL, 'pending', NULL, NULL, NULL, '2026-09-12 10:58:42', '2026-09-12 10:58:42', '2026-09-12 10:58:42'),
(5, 'JOIN-2026-0003', 'Aarav Sharma', '9876543210', 'aarav.sharma@example.com', 'Uttar Pradesh', 'Lucknow', 'join_foundation', NULL, NULL, 'Preferred Wing: Child Education & School Literacy | Want to volunteer on weekends', NULL, 'exempted', NULL, NULL, NULL, 'Exempted', NULL, 'pending', NULL, NULL, NULL, '2026-09-12 10:58:55', '2026-09-12 10:58:55', '2026-09-12 10:58:55'),
(6, 'PROJ-JOIN-2026-0003', 'Pooja Verma', '9123456780', 'pooja.verma@example.com', 'Uttar Pradesh', 'Varanasi', 'join_project', 16, NULL, 'Skills/Availability: Field Coordination & Tree Plantation, 8 hrs/week', NULL, 'exempted', NULL, NULL, NULL, 'Exempted', NULL, 'pending', NULL, NULL, NULL, '2026-09-12 10:58:55', '2026-09-12 10:58:55', '2026-09-12 10:58:55'),
(7, 'JOB-APP-2026-0004', 'Rohan Singh', '9988776655', 'rohan.singh@example.com', 'Bihar', 'Patna', 'job_application', NULL, 4, 'Qualification: MSW | Experience: 3 Years | Resume: https://drive.google.com/resume.pdf', NULL, 'exempted', NULL, NULL, NULL, 'Exempted', NULL, 'pending', NULL, NULL, NULL, '2026-09-12 10:58:55', '2026-09-12 10:58:55', '2026-09-12 10:58:55'),
(8, 'JOIN-2026-0004', 'Vikram Malhotra', '9765432109', 'vikram.malhotra@example.com', 'Delhi', 'New Delhi', 'join_foundation', NULL, NULL, 'Preferred Wing: Women Empowerment | Foundation Life Member Contribution', 500.00, 'paid', 'order_test_1789210735', 'pay_test_1789210735', 'e4085d73f44b0ca305deee3056325fb799e2ae6428ccfa712dc2b7cce978cc8e', 'Razorpay', 'pay_test_1789210735', 'approved', 'Verified by Coordination Officer during test run. Approved for induction.', 2, '2026-09-12 16:31:51', '2026-09-12 10:58:55', '2026-09-12 10:58:55', '2026-09-12 11:01:51');

-- --------------------------------------------------------

--
-- Table structure for table `letters`
--

CREATE TABLE `letters` (
  `id` int(11) NOT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `letter_type` varchar(100) NOT NULL DEFAULT 'General Official Letter',
  `subject` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `recipient_name` varchar(255) NOT NULL,
  `recipient_designation` varchar(255) DEFAULT NULL,
  `recipient_organization` varchar(255) DEFAULT NULL,
  `recipient_address` text DEFAULT NULL,
  `recipient_email` varchar(150) DEFAULT NULL,
  `recipient_phone` varchar(50) DEFAULT NULL,
  `generated_date` date NOT NULL,
  `generated_by` int(11) DEFAULT NULL,
  `pdf_path` varchar(255) DEFAULT NULL,
  `status` enum('Draft','Generated','Sent','Archived') NOT NULL DEFAULT 'Generated',
  `signatory_name` varchar(150) DEFAULT NULL,
  `signatory_designation` varchar(150) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `letters`
--

INSERT INTO `letters` (`id`, `reference_no`, `letter_type`, `subject`, `content`, `recipient_name`, `recipient_designation`, `recipient_organization`, `recipient_address`, `recipient_email`, `recipient_phone`, `generated_date`, `generated_by`, `pdf_path`, `status`, `signatory_name`, `signatory_designation`, `notes`, `created_at`, `updated_at`) VALUES
(4, 'JMF/LTR/2026/001', 'Appointment Letter', 'Official Appointment as Senior Outreach Coordinator', 'We are pleased to appoint you as Senior Outreach Coordinator at Jaysmrutti Foundation with effect from 15 January 2026.\n\nIn this capacity, you will oversee our regional health and nutrition assistance drives, volunteer mobilization, and coordination with community centers.\n\nWe look forward to your valuable contributions to our mission.', 'Dr. Rajesh Verma', 'Senior Outreach Coordinator', 'Jaysmrutti Foundation', 'Varanasi, Uttar Pradesh, India', 'rajesh.verma@example.com', '+91 9876543210', '2026-09-07', 2, NULL, 'Generated', 'Authorized Signatory', 'President / General Secretary', NULL, '2026-09-12 08:14:36', NULL),
(5, 'JMF/LTR/2026/002', 'Appreciation Letter', 'Certificate & Letter of Commendable Philanthropic Support', 'On behalf of Jaysmrutti Foundation, we extend our heartfelt gratitude for your generous support and active participation in our winter relief and medical distribution camps.\n\nYour dedication has brought relief to over 400 underprivileged families.', 'Sneha Kulkarni', 'Community Partner', 'Hope Care Welfare Initiative', 'Lucknow, Uttar Pradesh', 'sneha.kulkarni@example.com', '+91 9812345678', '2026-09-10', 2, NULL, 'Sent', 'Authorized Signatory', 'General Secretary', NULL, '2026-09-12 08:14:36', NULL),
(6, 'JMF/LTR/2026/003', 'Donation / CSR Request', 'CSR Partnership Proposal for Child Healthcare & Nutrition', 'Greetings from Jaysmrutti Foundation.\n\nWe submit this formal proposal for partnership under your Corporate Social Responsibility (CSR) wing for rural pediatric malnutrition eradication programs across Eastern UP.\n\nAll donations are eligible for tax deduction u/s 80G.', 'Amitabh Sen', 'Head of CSR', 'Zenith Global Enterprises', 'Connaught Place, New Delhi', 'csr@zenithglobal.com', '+91 11 43210987', '2026-09-12', 2, NULL, 'Generated', 'Authorized Signatory', 'Managing Trustee', NULL, '2026-09-12 08:14:36', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `management_body`
--

CREATE TABLE `management_body` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `designation` varchar(150) NOT NULL,
  `department` varchar(100) DEFAULT NULL COMMENT 'e.g. Board of Directors, Executive Team',
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL COMMENT 'Path to uploaded image',
  `fb_url` varchar(255) DEFAULT NULL,
  `linkedin_url` varchar(255) DEFAULT NULL,
  `sort_order` int(5) NOT NULL DEFAULT 0 COMMENT 'Display order (lower = first)',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `management_body`
--

INSERT INTO `management_body` (`id`, `name`, `designation`, `department`, `phone`, `email`, `bio`, `photo`, `fb_url`, `linkedin_url`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(8, 'Velnix Soft', 'President', '', '', 'velnixsoft@gmail.com', '', 'uploads/management/mgmt_1788343249_22cd215a.png', '', '', 0, 1, '2026-09-02 10:00:49', '2026-09-02 10:00:49');

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE `members` (
  `id` int(11) NOT NULL,
  `member_no` varchar(60) DEFAULT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(120) NOT NULL,
  `phone` varchar(25) NOT NULL,
  `blood_group` varchar(10) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `gender` varchar(15) DEFAULT NULL,
  `qualification` varchar(150) DEFAULT NULL,
  `profession` varchar(150) DEFAULT NULL,
  `marital_status` varchar(30) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `local_body_type` varchar(30) DEFAULT NULL,
  `local_body_name` varchar(150) DEFAULT NULL,
  `ward_no` varchar(20) DEFAULT NULL,
  `ward_name` varchar(150) DEFAULT NULL,
  `kudumbha_samithi` varchar(150) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `designation_id` int(11) NOT NULL,
  `event_id` int(11) DEFAULT NULL,
  `event_title` varchar(255) DEFAULT NULL,
  `event_date` date DEFAULT NULL,
  `event_location` varchar(500) DEFAULT NULL,
  `occasion_name` varchar(255) DEFAULT NULL,
  `achievement_position` varchar(120) DEFAULT NULL,
  `membership_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_gateway` enum('Manual','Razorpay') NOT NULL DEFAULT 'Manual',
  `payment_status` enum('Pending','Success','Failed') NOT NULL DEFAULT 'Pending',
  `payment_txn_id` varchar(120) DEFAULT NULL,
  `razorpay_order_id` varchar(120) DEFAULT NULL,
  `razorpay_payment_id` varchar(120) DEFAULT NULL,
  `razorpay_signature` varchar(255) DEFAULT NULL,
  `payment_proof` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Active','Blocked') NOT NULL DEFAULT 'Pending',
  `referral_code` varchar(40) DEFAULT NULL,
  `donation_ref_code` varchar(40) DEFAULT NULL,
  `referred_by_member_id` int(11) DEFAULT NULL,
  `referred_by_source` enum('none','member_ref','donation_ref') NOT NULL DEFAULT 'none',
  `member_receipt_no` varchar(80) DEFAULT NULL,
  `member_since` date DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `last_birthday_wish_on` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `verification_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `members`
--

INSERT INTO `members` (`id`, `member_no`, `full_name`, `email`, `phone`, `blood_group`, `dob`, `gender`, `qualification`, `profession`, `marital_status`, `address`, `district`, `state`, `local_body_type`, `local_body_name`, `ward_no`, `ward_name`, `kudumbha_samithi`, `photo`, `designation_id`, `event_id`, `event_title`, `event_date`, `event_location`, `occasion_name`, `achievement_position`, `membership_fee`, `payment_gateway`, `payment_status`, `payment_txn_id`, `razorpay_order_id`, `razorpay_payment_id`, `razorpay_signature`, `payment_proof`, `status`, `referral_code`, `donation_ref_code`, `referred_by_member_id`, `referred_by_source`, `member_receipt_no`, `member_since`, `valid_until`, `last_birthday_wish_on`, `created_at`, `created_by`, `verified_by`, `verified_at`, `verification_notes`) VALUES
(13, 'MEM-00001', 'Velnix Soft', 'velnixsoft@gmail.com', '7651910331', 'A+', '2000-01-02', 'Male', 'MCA', 'Developer', 'Single', 'Jaunpur Uttar Pardesh india', 'Jaunpur', 'Uttar Pradesh', NULL, NULL, NULL, NULL, NULL, 'uploads/members/member_photo_1788348841_414189501998.jpeg', 3, 6, 'Free Community Health And Awareness Camp', '2026-01-10', 'Jaunpur', NULL, NULL, 1000.00, 'Manual', 'Success', 'T2608300755315474719115', NULL, NULL, NULL, 'uploads/members/1788337851_6a97debb2ba38_payment.jpeg', 'Active', 'MRF-29C590BD', 'DRF-0BB5491F', NULL, 'none', 'MRCPT-2026-000001', '2026-09-02', '2027-09-02', NULL, '2026-09-02 08:30:51', NULL, 2, '2026-09-02 08:32:08', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `member_designations`
--

CREATE TABLE `member_designations` (
  `id` int(11) NOT NULL,
  `title` varchar(120) NOT NULL,
  `fee_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `member_designations`
--

INSERT INTO `member_designations` (`id`, `title`, `fee_amount`, `is_active`, `created_at`) VALUES
(1, 'Founder Member', 25000.00, 1, '2026-03-16 09:09:45'),
(2, 'Executive Member', 5000.00, 1, '2026-03-16 16:26:35'),
(3, 'General Member', 1000.00, 1, '2026-04-26 18:04:20');

-- --------------------------------------------------------

--
-- Table structure for table `member_documents`
--

CREATE TABLE `member_documents` (
  `id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `doc_type` enum('id_card','appointment_letter','membership_certificate','achievement_certificate') NOT NULL,
  `doc_no` varchar(80) DEFAULT NULL,
  `issued_at` timestamp NULL DEFAULT current_timestamp(),
  `issued_by` int(11) DEFAULT NULL,
  `meta_json` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `member_messages`
--

CREATE TABLE `member_messages` (
  `id` int(11) NOT NULL,
  `member_id` int(11) DEFAULT NULL,
  `subject` varchar(200) NOT NULL,
  `message_body` text NOT NULL,
  `message_type` enum('manual','birthday') NOT NULL DEFAULT 'manual',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `member_message_deliveries`
--

CREATE TABLE `member_message_deliveries` (
  `id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `email_status` enum('Pending','Sent','Failed') NOT NULL DEFAULT 'Pending',
  `dashboard_status` enum('Unread','Read') NOT NULL DEFAULT 'Unread',
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ngo_documents`
--

CREATE TABLE `ngo_documents` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `category` enum('certificate','annual_report','other') DEFAULT 'other',
  `file_path` varchar(255) NOT NULL,
  `is_public` tinyint(1) DEFAULT 0,
  `upload_date` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_preferences`
--

CREATE TABLE `notification_preferences` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `email_task_assigned` tinyint(1) NOT NULL DEFAULT 1,
  `email_submission_approved` tinyint(1) NOT NULL DEFAULT 1,
  `email_promotion_achieved` tinyint(1) NOT NULL DEFAULT 1,
  `email_badge_earned` tinyint(1) NOT NULL DEFAULT 1,
  `email_penalty_applied` tinyint(1) NOT NULL DEFAULT 1,
  `email_certificate_issued` tinyint(1) NOT NULL DEFAULT 1,
  `dashboard_notifications_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `digest_emails_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `digest_frequency` varchar(20) DEFAULT 'daily' COMMENT 'daily, weekly',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_templates`
--

CREATE TABLE `notification_templates` (
  `id` int(11) NOT NULL,
  `notification_type` varchar(50) NOT NULL,
  `email_subject_template` varchar(255) NOT NULL,
  `email_body_template` longtext NOT NULL,
  `dashboard_title_template` varchar(255) NOT NULL,
  `dashboard_message_template` text NOT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `icon` varchar(50) DEFAULT 'bell',
  `color_class` varchar(50) DEFAULT 'info',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notification_templates`
--

INSERT INTO `notification_templates` (`id`, `notification_type`, `email_subject_template`, `email_body_template`, `dashboard_title_template`, `dashboard_message_template`, `is_enabled`, `icon`, `color_class`, `created_at`, `updated_at`) VALUES
(1, 'task_assigned', 'New Task Assigned: {task_title}', '<p>Hi {student_name},</p><p>You have been assigned a new task:</p><p><strong>{task_title}</strong></p><p>{task_description}</p><p>Due: {due_date}</p><p>Points Available: {points_available}</p><p>Log in to see more details.</p>', 'New Task Available', 'New task available: {task_title}', 1, 'tasks', 'primary', '2026-06-06 12:15:37', '2026-06-06 14:20:23'),
(2, 'submission_approved', 'Your Submission Was Approved!', '<p>Hi {student_name},</p><p>Great news! Your submission for <strong>{task_title}</strong> has been approved.</p><p>Points Earned: <strong>{points_earned}</strong></p><p>Feedback: {feedback}</p>', 'Submission Approved', 'Your submission for {task_title} was approved! +{points_earned} points', 1, 'check-circle', 'success', '2026-06-06 12:15:37', '2026-06-06 12:15:37'),
(3, 'promotion_achieved', 'Congratulations! You\'ve Been Promoted', '<p>Hi {student_name},</p><p>Congratulations! You have been promoted from <strong>{old_level}</strong> to <strong>{new_level}</strong>!</p><p>Benefits: {benefits}</p>', 'Promotion Achievement', 'Promoted from {old_level} to {new_level}!', 1, 'award', 'success', '2026-06-06 12:15:37', '2026-06-06 12:15:37'),
(4, 'badge_earned', 'Badge Earned: {badge_name}', '<p>Hi {student_name},</p><p>You have earned the <strong>{badge_name}</strong> badge!</p><p>Description: {badge_description}</p><p>Points: +{points_earned}</p>', 'Badge Earned: {badge_name}', 'You earned the {badge_name} badge! +{points_earned} points', 1, 'star', 'warning', '2026-06-06 12:15:37', '2026-06-06 12:15:37'),
(5, 'penalty_applied', 'Penalty Applied to Your Account', '<p>Hi {student_name},</p><p>A penalty has been applied to your account.</p><p>Type: {penalty_type}</p><p>Reason: {reason}</p><p>Points Deducted: -{points_deducted}</p>', 'Penalty Applied', '{penalty_type}: {reason} (-{points_deducted} points)', 1, 'alert-circle', 'danger', '2026-06-06 12:15:37', '2026-06-06 12:15:37'),
(6, 'certificate_issued', 'Your Certificate Is Ready', '<p>Hi {student_name},</p><p>Your <strong>{certificate_type}</strong> certificate has been issued.</p><p>Issue Date: {issue_date}</p><p>You can download it from your dashboard.</p>', 'Certificate Ready', 'Your certificate {certificate_title} is ready. Certificate No: {certificate_no}', 1, 'award', 'info', '2026-06-06 12:15:37', '2026-06-07 16:49:20'),
(7, 'registration_approved', 'Your Ambassador Account Is Approved', '<p>Hi {student_name},</p><p>Your student ambassador registration has been approved. You can now access the full portal.</p>', 'Account Approved', 'Your ambassador account is now active. Welcome aboard!', 1, 'check-circle', 'success', '2026-06-06 14:20:01', '2026-06-06 14:20:01'),
(8, 'referral_verified', 'Referral Verified: {referred_name}', '<p>Hi {student_name},</p><p>Your referral for <strong>{referred_name}</strong> has been verified.</p><p>Points earned: <strong>+{points_earned}</strong></p>', 'Referral Verified', 'Referral for {referred_name} verified. +{points_earned} points.', 1, 'share-nodes', 'success', '2026-06-06 14:20:01', '2026-06-06 14:20:01'),
(9, 'submission_rejected', 'Submission Needs Revision: {task_title}', '<p>Hi {student_name},</p><p>Your submission for <strong>{task_title}</strong> was not approved.</p><p>Feedback: {feedback}</p>', 'Submission Rejected', 'Your submission for {task_title} needs revision.', 1, 'alert-circle', 'warning', '2026-06-06 14:20:01', '2026-06-06 14:20:01'),
(10, 'vendor_lead_verified', 'Vendor Lead Verified: {business_name}', '<p>Hi {student_name},</p><p>Your vendor lead <strong>{business_name}</strong> has been verified.</p><p>Points earned: +{points_earned}</p>', 'Vendor Lead Verified', 'Your lead for {business_name} was verified. +{points_earned} points.', 1, 'handshake', 'success', '2026-06-06 14:20:23', '2026-06-06 14:20:23'),
(13, 'announcement', 'Announcement: {announcement_title}', '<p>Hi {student_name},</p><p>{announcement_message}</p>', '{announcement_title}', '{announcement_message}', 1, 'bullhorn', 'primary', '2026-06-07 16:49:20', '2026-06-07 16:49:20');

-- --------------------------------------------------------

--
-- Table structure for table `org_structure`
--

CREATE TABLE `org_structure` (
  `id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `designation_id` int(11) DEFAULT NULL,
  `title` varchar(150) NOT NULL,
  `department` varchar(100) NOT NULL DEFAULT 'Executive Board',
  `holder_name` varchar(150) DEFAULT NULL,
  `holder_designation` varchar(150) DEFAULT NULL,
  `holder_photo` varchar(255) DEFAULT NULL,
  `holder_phone` varchar(30) DEFAULT NULL,
  `holder_email` varchar(150) DEFAULT NULL,
  `management_body_id` int(11) DEFAULT NULL,
  `member_id` int(11) DEFAULT NULL,
  `level_tier` int(11) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `badge_color` varchar(30) NOT NULL DEFAULT 'teal',
  `responsibilities` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `org_structure`
--

INSERT INTO `org_structure` (`id`, `parent_id`, `designation_id`, `title`, `department`, `holder_name`, `holder_designation`, `holder_photo`, `holder_phone`, `holder_email`, `management_body_id`, `member_id`, `level_tier`, `sort_order`, `badge_color`, `responsibilities`, `is_active`, `created_at`, `updated_at`) VALUES
(1, NULL, 1, 'National President & Chairman', 'Executive Board', 'Dr. Arvind Sharma', 'Founder & Chief Patron', NULL, '+91 98765 43210', 'president@ngocare.org', NULL, NULL, 1, 1, 'indigo', 'Overall strategic vision, constitutional governance, national partnerships and policy direction.', 1, '2026-09-12 09:49:45', '2026-09-12 09:49:45'),
(2, 1, 2, 'Vice President (Programs & Alliances)', 'Executive Board', 'Smt. Vandana Mishra', 'Vice Chairperson', NULL, '+91 98765 43211', 'vp@ngocare.org', NULL, NULL, 2, 1, 'blue', 'Supervision of health, skill development and rural education programs across India.', 1, '2026-09-12 09:49:45', '2026-09-12 09:49:45'),
(3, 1, 2, 'General Secretary & CEO', 'Secretariat & Administration', 'Rajesh K. Verma', 'General Secretary', NULL, '+91 98765 43212', 'secretary@ngocare.org', NULL, NULL, 2, 2, 'teal', 'Day-to-day NGO administration, state coordinator alignments, donor liaisons and institutional reporting.', 1, '2026-09-12 09:49:45', '2026-09-12 09:49:45'),
(4, 1, 2, 'National Treasurer & Finance Head', 'Finance & Audit', 'Pooja Agarwal (CA)', 'Treasurer', NULL, '+91 98765 43213', 'treasurer@ngocare.org', NULL, NULL, 2, 3, 'amber', 'Statutory compliance, annual audits, fund allocation, budgeting and 80G/12A regulatory filings.', 1, '2026-09-12 09:49:45', '2026-09-12 09:49:45'),
(5, 2, NULL, 'Director (Healthcare & Relief Services)', 'Health Directorate', 'Dr. S. K. Gupta (MD)', 'Medical Director', NULL, '+91 98765 43214', 'health@ngocare.org', NULL, NULL, 3, 1, 'emerald', 'Empanelment of hospitals/clinics, Swasthya Card schemes, blood donation drives and relief camps.', 1, '2026-09-12 09:49:45', '2026-09-12 09:49:45'),
(6, 2, NULL, 'Director (Skill Training & Career Guidance)', 'Youth & Education Wing', 'Er. Alok Trivedi', 'Training Director', NULL, '+91 98765 43215', 'skills@ngocare.org', NULL, NULL, 3, 2, 'purple', 'Vocational courses, youth computer labs, scholarship assessments, and placement counseling.', 1, '2026-09-12 09:49:45', '2026-09-12 09:49:45'),
(7, 3, NULL, 'State Program Coordinator (UP & Bihar)', 'State Operations', 'Manoj Tripathi', 'State Coordinator', NULL, '+91 98765 43216', 'state.up@ngocare.org', NULL, NULL, 3, 1, 'teal', 'Managing all District coordinators, field projects, government liaison, and district performance.', 1, '2026-09-12 09:49:45', '2026-09-12 09:49:45'),
(8, 7, NULL, 'District Operations Coordinator (Lucknow & Varanasi)', 'District Operations', 'Suresh Kumar Yadav', 'District Coordinator', NULL, '+91 98765 43217', 'dist.lucknow@ngocare.org', NULL, NULL, 4, 1, 'blue', 'District-level project execution, block coordinator management, and community beneficiary validation.', 1, '2026-09-12 09:49:45', '2026-09-12 09:49:45'),
(9, 8, NULL, 'Block Field Officer & Mobilizer', 'Block & Field Units', 'Anil Verma', 'Block Coordinator', NULL, '+91 98765 43218', 'block.bkt@ngocare.org', NULL, NULL, 5, 1, 'rose', 'Grassroots household surveys, health card enrolments, SHG meetings, and food/kit distribution.', 1, '2026-09-12 09:49:45', '2026-09-12 09:49:45'),
(10, 9, NULL, 'Gram Panchayat Community Volunteers', 'Village Volunteer Network', 'Village Volunteer Team', 'Panchayat Unit', NULL, '+91 98765 43219', 'volunteers@ngocare.org', NULL, NULL, 5, 2, 'teal', 'Direct doorstep support, emergency coordination and event logistics at village panchayat level.', 1, '2026-09-12 09:49:45', '2026-09-12 09:49:45');

-- --------------------------------------------------------

--
-- Table structure for table `payment_qrs`
--

CREATE TABLE `payment_qrs` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `qr_image_path` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment_qrs`
--

INSERT INTO `payment_qrs` (`id`, `title`, `qr_image_path`, `is_active`) VALUES
(1, 'UPI', 'uploads/qrs/1773984670_2_1767606718_695b89be1c478.jpg', 1);

-- --------------------------------------------------------

--
-- Table structure for table `payment_webhook_logs`
--

CREATE TABLE `payment_webhook_logs` (
  `id` int(11) NOT NULL,
  `gateway` varchar(30) NOT NULL,
  `event_type` varchar(64) NOT NULL,
  `payment_id` varchar(120) DEFAULT NULL,
  `order_id` varchar(120) DEFAULT NULL,
  `payload` longtext DEFAULT NULL,
  `processed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL,
  `cover_path` varchar(255) DEFAULT NULL,
  `status` enum('Draft','Published','Archived') NOT NULL DEFAULT 'Draft',
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`id`, `title`, `slug`, `content`, `cover_path`, `status`, `published_at`, `created_at`, `created_by`, `updated_at`) VALUES
(2, 'School Chalo Abhiyan: Encouraging Children Towards Education', 'school-chalo-abhiyan', 'Education is one of the key areas of work for Jaysmrutti Foundation. Through the School Chalo Abhiyan, the Foundation aims to encourage children to attend school regularly and understand the importance of education.\r\n\r\nThe initiative also seeks to create awareness among families and communities so that every child gets the opportunity to learn, grow and build a better future.', 'uploads/news/news_1788169247_a1ba7bc42f6b.jpeg', 'Published', '2026-08-31 09:38:45', '2026-08-31 09:38:45', 2, '2026-08-31 09:42:00'),
(3, 'Jaysmrutti Foundation Organizes Green India Campaign', 'jaysmrutti-foundation-organizes-green-india-campaign', 'Jaysmrutti Foundation continues its commitment towards environmental protection through the Green India Campaign. The initiative focuses on tree plantation, environmental awareness and encouraging communities to take responsibility for protecting nature.\r\n\r\nThrough active participation of children, youth and community members, the campaign promotes the importance of trees and a greener environment for future generations.', 'uploads/news/news_1788169428_eb54caa60df9.jpeg', 'Published', '2026-08-31 09:44:00', '2026-08-31 09:43:48', 2, '2026-08-31 09:44:01');

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `icon` varchar(50) DEFAULT '?',
  `color_class` varchar(50) DEFAULT 'cat-edu',
  `description` text NOT NULL,
  `content` longtext DEFAULT NULL,
  `impact_text` varchar(255) DEFAULT NULL,
  `image_path` varchar(500) DEFAULT NULL,
  `banner_image` varchar(500) DEFAULT NULL,
  `priority_order` int(11) DEFAULT 999,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`id`, `title`, `slug`, `icon`, `color_class`, `description`, `content`, `impact_text`, `image_path`, `banner_image`, `priority_order`, `is_active`, `created_at`, `updated_at`) VALUES
(6, 'Livelihood & Agriculture', 'livelihood-agriculture', '🌾', 'cat-edu', 'Farmer support programs, modern agri-techniques, organic farming training, cooperative formation, and market access for sustainable rural income.', '<h3>Sustainable Livelihoods for Rural India</h3>\r\n<p>Agriculture remains the primary source of income for millions. Our Livelihood & Agriculture program supports farmers with modern techniques, resources, and market access.</p>\r\n\r\n<h4>What We Offer:</h4>\r\n<ul>\r\n<li><strong>Organic Farming Training:</strong> Chemical-free farming methods, composting, and bio-pesticides.</li>\r\n<li><strong>Seed Distribution:</strong> High-yield and drought-resistant seed varieties at subsidized rates.</li>\r\n<li><strong>Drip Irrigation Systems:</strong> Water-efficient irrigation for water-scarce regions.</li>\r\n<li><strong>Farmer Cooperatives:</strong> Collective bargaining for better prices and bulk procurement of inputs.</li>\r\n<li><strong>Dairy & Poultry Support:</strong> Livestock management, veterinary camps, and fodder supply.</li>\r\n<li><strong>Market Linkages:</strong> Direct connection to buyers, mandis, and online agricultural marketplaces.</li>\r\n</ul>\r\n\r\n<h4>Impact:</h4>\r\n<p><strong>890+ farming families supported</strong>. Average income increase of 35% within the first year of program adoption.</p>\r\n\r\n<h4>Success Case:</h4>\r\n<p>Farmer <strong>Ramesh Singh</strong> switched to organic vegetable farming with our support. He now supplies to urban organic stores and earns ₹25,000 monthly — double his previous income.</p>', '890+ farming families', 'uploads/programs/livelihood-main.jpg', 'uploads/programs/livelihood-banner.jpg', 6, 1, '2026-03-30 02:22:25', '2026-03-30 03:46:12');

-- --------------------------------------------------------

--
-- Table structure for table `program_features`
--

CREATE TABLE `program_features` (
  `id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `feature_text` varchar(500) NOT NULL,
  `icon` varchar(50) DEFAULT '✓',
  `display_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `program_features`
--

INSERT INTO `program_features` (`id`, `program_id`, `feature_text`, `icon`, `display_order`) VALUES
(21, 6, 'Organic farming techniques and training', '🌱', 1),
(22, 6, 'High-yield seed distribution programs', '🌾', 2),
(23, 6, 'Drip irrigation for water conservation', '💧', 3),
(24, 6, 'Direct market access and fair pricing', '🏪', 4);

-- --------------------------------------------------------

--
-- Table structure for table `program_gallery`
--

CREATE TABLE `program_gallery` (
  `id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `image_path` varchar(500) NOT NULL,
  `caption` varchar(255) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `thumbnail_image` varchar(255) DEFAULT NULL,
  `target_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `raised_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `upi_qr_image` varchar(255) DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `status` enum('Active','Completed','Paused') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `title`, `description`, `thumbnail_image`, `target_amount`, `raised_amount`, `upi_qr_image`, `video_url`, `status`, `created_at`, `updated_at`) VALUES
(7, 'Green India Campaign', 'Jaysmrutti Foundation का Green India Campaign पर्यावरण संरक्षण और वृक्षारोपण के प्रति लोगों में जागरूकता बढ़ाने की पहल है। इस अभियान के माध्यम से बच्चों, युवाओं और समुदायों को पेड़ लगाने, उनकी देखभाल करने और प्राकृतिक संसाधनों की रक्षा करने के लिए प्रेरित किया जाता है। हमारा उद्देश्य एक स्वच्छ, हरा-भरा और sustainable environment बनाना है।', 'uploads/projects/thumbnails/7_1788165587_6a953dd34d92c.jpeg', 100000.00, 0.00, NULL, '', 'Active', '2026-08-31 08:12:48', '2026-08-31 08:39:47'),
(8, 'Stationery Bank', 'Stationery Bank का उद्देश्य जरूरतमंद और आर्थिक रूप से कमजोर विद्यार्थियों तक आवश्यक शैक्षिक सामग्री पहुँचाना है। Jaysmrutti Foundation के माध्यम से notebooks, pens, pencils, school supplies और अन्य उपयोगी stationery उपलब्ध कराने का प्रयास किया जाता है, ताकि आर्थिक कठिनाइयाँ बच्चों की शिक्षा में बाधा न बनें।', 'uploads/projects/thumbnails/8_1788166503_6a9541679100b.jpeg', 100000.00, 0.00, NULL, '', 'Active', '2026-08-31 08:15:19', '2026-08-31 08:55:03'),
(9, 'School Chalo Abhiyan', 'School Chalo Abhiyan बच्चों को शिक्षा से जोड़ने और उन्हें नियमित रूप से विद्यालय जाने के लिए प्रोत्साहित करने की पहल है। Jaysmrutti Foundation शिक्षा के महत्व के प्रति परिवारों और समुदायों में जागरूकता पैदा करने तथा जरूरतमंद बच्चों को बेहतर educational opportunities उपलब्ध कराने के लिए कार्य करता है।', 'uploads/projects/thumbnails/9_1788166694_6a954226e002a.jpeg', 100000.00, 0.00, NULL, '', 'Active', '2026-08-31 08:18:28', '2026-08-31 08:58:14'),
(10, 'Mother Orientation Programme', 'Mother Orientation Programme का उद्देश्य माताओं को बच्चों की शिक्षा, स्वास्थ्य, स्वच्छता, पोषण और समग्र विकास के प्रति जागरूक करना है। Jaysmrutti Foundation का मानना है कि जागरूक और सशक्त माताएँ बच्चों तथा पूरे परिवार के बेहतर भविष्य की मजबूत नींव तैयार कर सकती हैं।', 'uploads/projects/thumbnails/10_1788166903_6a9542f7f0fa1.jpeg', 10000.00, 0.00, NULL, '', 'Active', '2026-08-31 08:19:18', '2026-08-31 09:01:43'),
(11, 'Road Safety Awareness Programme', 'Road Safety Awareness Programme के माध्यम से लोगों, विशेषकर बच्चों और युवाओं को सड़क सुरक्षा के नियमों और जिम्मेदार यातायात व्यवहार के बारे में जागरूक किया जाता है। Jaysmrutti Foundation सुरक्षित सड़क उपयोग, traffic rules के पालन और सावधानीपूर्ण व्यवहार को बढ़ावा देकर सड़क दुर्घटनाओं को कम करने की दिशा में जागरूकता फैलाने का प्रयास करता है।', 'uploads/projects/thumbnails/11_1788167146_6a9543eaef3b6.jpeg', 100000.00, 0.00, NULL, '', 'Active', '2026-08-31 08:20:16', '2026-08-31 09:05:46'),
(12, 'Health Awareness Programme', 'Health Awareness Programme का उद्देश्य समुदायों में स्वास्थ्य, स्वच्छता, पोषण, साफ-सफाई और रोगों से बचाव के प्रति जागरूकता बढ़ाना है। Jaysmrutti Foundation लोगों को स्वस्थ जीवनशैली अपनाने और समय पर स्वास्थ्य संबंधी सावधानियाँ बरतने के लिए प्रेरित करता है।', 'uploads/projects/thumbnails/12_1788167357_6a9544bd9b52d.jpeg', 100000.00, 0.00, NULL, '', 'Active', '2026-08-31 08:21:36', '2026-08-31 09:09:17'),
(13, 'Biodiversity Conservation Awareness Programme', 'Biodiversity Conservation Awareness Programme का उद्देश्य जैव विविधता, प्राकृतिक संसाधनों और ecological balance के महत्व के बारे में लोगों को जागरूक करना है। Jaysmrutti Foundation समुदायों को प्रकृति, पेड़-पौधों, जीव-जंतुओं और स्थानीय पर्यावरण के संरक्षण में सक्रिय भागीदारी के लिए प्रेरित करता है।', 'uploads/projects/thumbnails/13_1788167674_6a9545fa1db17.jpeg', 100000.00, 500.00, NULL, '', 'Active', '2026-08-31 08:23:04', '2026-09-02 09:49:22'),
(14, 'Ek Roti Ek Muskan', 'Ek Roti Ek Muskan मानवता और सामुदायिक सहयोग पर आधारित पहल है, जिसका उद्देश्य जरूरतमंद लोगों तक भोजन और सहयोग पहुँचाना है। Jaysmrutti Foundation लोगों को भोजन साझा करने और समाज के जरूरतमंद वर्गों के प्रति संवेदनशीलता एवं करुणा का भाव विकसित करने के लिए प्रेरित करता है।', 'uploads/projects/thumbnails/14_1788167847_6a9546a754adf.jpeg', 100000.00, 0.00, NULL, '', 'Active', '2026-08-31 08:23:56', '2026-08-31 09:17:27'),
(15, 'Water Conservation Awareness Programme', 'Water Conservation Awareness Programme का उद्देश्य जल के महत्व और उसके संरक्षण के प्रति जन-जागरूकता बढ़ाना है। Jaysmrutti Foundation लोगों को पानी की बचत, responsible water usage और जल संसाधनों के संरक्षण के लिए प्रेरित करता है, ताकि आने वाली पीढ़ियों के लिए जल उपलब्धता सुनिश्चित करने में योगदान दिया जा सके।', 'uploads/projects/thumbnails/15_1788168226_6a9548224451e.jpeg', 100000.00, 0.00, NULL, '', 'Active', '2026-08-31 08:24:41', '2026-08-31 09:23:46'),
(16, 'Women Empowerment Awareness Programme', 'Women Empowerment Awareness Programme महिलाओं को उनके अधिकारों, अवसरों, शिक्षा, आत्मनिर्भरता और सामाजिक भागीदारी के प्रति जागरूक करने पर केंद्रित है। Jaysmrutti Foundation महिलाओं को ज्ञान, अवसर और आत्मविश्वास के माध्यम से सशक्त बनाने तथा उन्हें समाज के विकास में सक्रिय भूमिका निभाने के लिए प्रोत्साहित करता है।', 'uploads/projects/thumbnails/16_1788168474_6a95491a23d95.jpeg', 100000.00, 0.00, NULL, '', 'Active', '2026-08-31 08:28:36', '2026-08-31 09:27:54');

-- --------------------------------------------------------

--
-- Table structure for table `project_gallery`
--

CREATE TABLE `project_gallery` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `promotion_rules`
--

CREATE TABLE `promotion_rules` (
  `id` int(11) NOT NULL,
  `level_rank` int(11) NOT NULL,
  `level_name` varchar(80) NOT NULL,
  `min_points` int(11) NOT NULL DEFAULT 0,
  `min_attendance_days` int(11) NOT NULL DEFAULT 0,
  `min_attendance_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `min_referrals` int(11) NOT NULL DEFAULT 0,
  `min_vendor_onboardings` int(11) NOT NULL DEFAULT 0,
  `min_campaign_approvals` int(11) NOT NULL DEFAULT 0,
  `min_campaign_points` int(11) NOT NULL DEFAULT 0,
  `demotion_grace_days` int(11) NOT NULL DEFAULT 14,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `effective_from` datetime DEFAULT NULL,
  `effective_to` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `promotion_rules`
--

INSERT INTO `promotion_rules` (`id`, `level_rank`, `level_name`, `min_points`, `min_attendance_days`, `min_attendance_rate`, `min_referrals`, `min_vendor_onboardings`, `min_campaign_approvals`, `min_campaign_points`, `demotion_grace_days`, `is_active`, `effective_from`, `effective_to`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 'Community Intern', 50, 5, 50.00, 1, 0, 1, 10, 14, 1, NULL, NULL, 'Starter operating level.', '2026-06-06 12:15:38', '2026-06-06 12:15:38'),
(2, 2, 'Senior Intern', 150, 12, 60.00, 3, 1, 3, 50, 14, 1, NULL, NULL, 'Consistent activity and first vendor outcome.', '2026-06-06 12:15:38', '2026-06-06 12:15:38'),
(3, 3, 'Campus Ambassador', 400, 20, 70.00, 7, 3, 8, 150, 21, 1, NULL, NULL, 'Campus-ready ambassador performance.', '2026-06-06 12:15:38', '2026-06-06 12:15:38'),
(4, 4, 'Team Leader', 800, 30, 75.00, 12, 5, 15, 350, 21, 1, NULL, NULL, 'Can lead interns or small campaign teams.', '2026-06-06 12:15:38', '2026-06-06 12:15:38'),
(5, 5, 'City Coordinator', 1500, 45, 80.00, 20, 10, 25, 700, 30, 1, NULL, NULL, 'City-level operational responsibility.', '2026-06-06 12:15:38', '2026-06-06 12:15:38'),
(6, 6, 'State Coordinator', 3000, 60, 85.00, 35, 20, 40, 1200, 30, 1, NULL, NULL, 'State-level coordination responsibility.', '2026-06-06 12:15:38', '2026-06-06 12:15:38');

-- --------------------------------------------------------

--
-- Table structure for table `qr_scan_logs`
--

CREATE TABLE `qr_scan_logs` (
  `id` int(11) NOT NULL,
  `qr_token_id` int(11) DEFAULT NULL,
  `member_id` int(11) DEFAULT NULL,
  `scanned_at` datetime NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(64) DEFAULT NULL,
  `device_info` varchar(255) DEFAULT NULL,
  `result_status` enum('verified','inactive','expired','invalid') NOT NULL DEFAULT 'verified',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `qr_scan_logs`
--

INSERT INTO `qr_scan_logs` (`id`, `qr_token_id`, `member_id`, `scanned_at`, `ip_address`, `device_info`, `result_status`, `created_at`) VALUES
(17, NULL, NULL, '2026-04-24 09:31:02', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 'verified', '2026-04-24 07:31:02'),
(18, NULL, NULL, '2026-04-24 09:31:03', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 'verified', '2026-04-24 07:31:03'),
(19, NULL, NULL, '2026-04-24 10:35:16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 'verified', '2026-04-24 08:35:16'),
(20, NULL, NULL, '2026-04-24 10:35:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 'verified', '2026-04-24 08:35:27'),
(21, NULL, NULL, '2026-04-24 10:35:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 'verified', '2026-04-24 08:35:27'),
(22, NULL, NULL, '2026-04-24 10:35:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 'verified', '2026-04-24 08:35:27'),
(23, NULL, NULL, '2026-04-24 10:36:01', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 'verified', '2026-04-24 08:36:01'),
(24, NULL, NULL, '2026-04-24 10:36:26', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 'verified', '2026-04-24 08:36:26'),
(25, NULL, NULL, '2026-04-24 11:06:41', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 'verified', '2026-04-24 09:06:41'),
(26, NULL, NULL, '2026-04-24 10:17:16', '106.219.171.82', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Mobile Safari/537.36', 'verified', '2026-04-24 10:17:16'),
(27, NULL, NULL, '2026-04-24 10:17:43', '106.219.171.82', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Mobile Safari/537.36', 'verified', '2026-04-24 10:17:43'),
(28, NULL, NULL, '2026-04-24 11:36:00', '106.219.171.82', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Mobile Safari/537.36', 'verified', '2026-04-24 11:36:00'),
(29, NULL, NULL, '2026-04-27 09:02:28', '106.219.174.109', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Mobile Safari/537.36', 'invalid', '2026-04-27 09:02:28'),
(30, NULL, NULL, '2026-04-27 16:58:59', '157.51.227.215', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36', 'verified', '2026-04-27 16:58:59'),
(31, NULL, NULL, '2026-04-27 18:14:21', '157.51.226.200', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36', 'verified', '2026-04-27 18:14:21'),
(32, NULL, NULL, '2026-04-27 18:14:42', '157.51.226.200', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36', 'verified', '2026-04-27 18:14:42'),
(33, NULL, NULL, '2026-04-28 17:06:10', '223.188.113.217', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', 'verified', '2026-04-28 17:06:10'),
(34, NULL, NULL, '2026-04-28 17:06:39', '157.51.226.97', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36', 'verified', '2026-04-28 17:06:39'),
(35, NULL, NULL, '2026-04-28 17:07:04', '223.188.113.217', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', 'verified', '2026-04-28 17:07:04'),
(36, NULL, NULL, '2026-04-28 17:07:18', '157.51.226.97', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36', 'verified', '2026-04-28 17:07:18'),
(37, NULL, NULL, '2026-04-28 18:26:58', '157.51.226.156', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36', 'verified', '2026-04-28 18:26:58'),
(38, NULL, NULL, '2026-04-29 02:10:14', '152.59.222.85', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36', 'verified', '2026-04-29 02:10:14'),
(39, NULL, NULL, '2026-04-29 08:37:29', '157.51.214.195', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36', 'verified', '2026-04-29 08:37:29'),
(40, NULL, NULL, '2026-04-29 08:37:31', '64.233.173.96', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36 (compatible; Google-Read-Aloud; +https://support.google.com/webmasters/answer/1061943)', 'verified', '2026-04-29 08:37:31'),
(41, NULL, NULL, '2026-04-29 08:37:33', '192.178.15.71', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36 (compatible; Google-Read-Aloud; +https://support.google.com/webmasters/answer/1061943)', 'verified', '2026-04-29 08:37:33'),
(42, NULL, NULL, '2026-04-29 08:37:33', '192.178.15.71', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36 (compatible; Google-Read-Aloud; +https://support.google.com/webmasters/answer/1061943)', 'verified', '2026-04-29 08:37:33');

-- --------------------------------------------------------

--
-- Table structure for table `qr_tokens`
--

CREATE TABLE `qr_tokens` (
  `id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `token` varchar(128) NOT NULL,
  `document_type` varchar(80) NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  `status` enum('active','expired','revoked') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `qr_tokens`
--

INSERT INTO `qr_tokens` (`id`, `member_id`, `token`, `document_type`, `expires_at`, `status`, `created_at`) VALUES
(23, 13, '0bd8fc053695511d4e1cc421d1fc150cb0bda32d6e1eca39b22b5cb17c89f605', 'receipt', NULL, 'active', '2026-09-02 08:32:08'),
(24, 13, 'b5799be7e5ef5542dc930a8ab8d13c87d9882e20c54d1e6272fe0a68980aa5db', 'id_card', NULL, 'active', '2026-09-02 08:33:13'),
(25, 13, 'a7628546ece8284a4b66364b96723b7093d4d48666155c7cebd43e28c2c3bf7e', 'membership_certificate', NULL, 'active', '2026-09-02 11:59:08');

-- --------------------------------------------------------

--
-- Table structure for table `razorpay_webhook_logs`
--

CREATE TABLE `razorpay_webhook_logs` (
  `id` int(11) NOT NULL,
  `event_type` varchar(64) NOT NULL,
  `payment_id` varchar(64) DEFAULT NULL,
  `order_id` varchar(64) DEFAULT NULL,
  `payload` longtext DEFAULT NULL,
  `processed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `razorpay_webhook_logs`
--

INSERT INTO `razorpay_webhook_logs` (`id`, `event_type`, `payment_id`, `order_id`, `payload`, `processed`, `created_at`) VALUES
(1, 'payment.authorized', 'pay_SZU4CnVfigR6GV', 'order_SZU44TCJHJuhKu', '{\"entity\":\"event\",\"account_id\":\"acc_SJeI5Pr2NG2DSt\",\"event\":\"order.paid\",\"contains\":[\"payment\",\"order\"],\"payload\":{\"payment\":{\"entity\":{\"id\":\"pay_SZU4CnVfigR6GV\",\"entity\":\"payment\",\"amount\":100000,\"currency\":\"INR\",\"status\":\"captured\",\"order_id\":\"order_SZU44TCJHJuhKu\",\"invoice_id\":null,\"international\":false,\"method\":\"netbanking\",\"amount_refunded\":0,\"refund_status\":null,\"captured\":true,\"description\":\"Donation\",\"card_id\":null,\"bank\":\"BARB_R\",\"wallet\":null,\"vpa\":null,\"email\":\"connexifyofficial@gmail.com\",\"contact\":\"+918437982054\",\"notes\":{\"module\":\"donations\",\"donor_name\":\"Connexify\",\"donor_email\":\"connexifyofficial@gmail.com\",\"project_id\":\"general\"},\"fee\":2360,\"tax\":360,\"error_code\":null,\"error_description\":null,\"error_source\":null,\"error_step\":null,\"error_reason\":null,\"acquirer_data\":{\"bank_transaction_id\":\"3575746\"},\"created_at\":1775320614,\"reward\":null}},\"order\":{\"entity\":{\"id\":\"order_SZU44TCJHJuhKu\",\"entity\":\"order\",\"amount\":100000,\"amount_paid\":100000,\"amount_due\":0,\"currency\":\"INR\",\"receipt\":\"don_20260404183646_7010\",\"offer_id\":null,\"status\":\"paid\",\"attempts\":1,\"notes\":{\"module\":\"donations\",\"donor_name\":\"Connexify\",\"donor_email\":\"connexifyofficial@gmail.com\",\"project_id\":\"general\"},\"created_at\":1775320606,\"description\":null,\"checkout\":null}}},\"created_at\":1775320619}', 1, '2026-04-04 16:42:24'),
(3, 'payment.authorized', 'pay_SZU1B34ONAYkiT', 'order_SZU0vOw4tRZMD9', '{\"entity\":\"event\",\"account_id\":\"acc_SJeI5Pr2NG2DSt\",\"event\":\"payment.captured\",\"contains\":[\"payment\"],\"payload\":{\"payment\":{\"entity\":{\"id\":\"pay_SZU1B34ONAYkiT\",\"entity\":\"payment\",\"amount\":50000,\"currency\":\"INR\",\"status\":\"captured\",\"order_id\":\"order_SZU0vOw4tRZMD9\",\"invoice_id\":null,\"international\":false,\"method\":\"netbanking\",\"amount_refunded\":0,\"refund_status\":null,\"captured\":true,\"description\":\"Donation\",\"card_id\":null,\"bank\":\"CNRB\",\"wallet\":null,\"vpa\":null,\"email\":\"connexifyofficial@gmail.com\",\"contact\":\"+918437982054\",\"notes\":{\"module\":\"donations\",\"donor_name\":\"Connexify\",\"donor_email\":\"connexifyofficial@gmail.com\",\"project_id\":\"general\"},\"fee\":1180,\"tax\":180,\"error_code\":null,\"error_description\":null,\"error_source\":null,\"error_step\":null,\"error_reason\":null,\"acquirer_data\":{\"bank_transaction_id\":\"8138816\"},\"created_at\":1775320442,\"reward\":null,\"base_amount\":50000}}},\"created_at\":1775320448}', 1, '2026-04-04 16:44:48'),
(5, 'payment.authorized', 'pay_SZUE9jvKblg0gW', 'order_SZUDzoFxKXbirw', '{\"entity\":\"event\",\"account_id\":\"acc_SJeI5Pr2NG2DSt\",\"event\":\"payment.captured\",\"contains\":[\"payment\"],\"payload\":{\"payment\":{\"entity\":{\"id\":\"pay_SZUE9jvKblg0gW\",\"entity\":\"payment\",\"amount\":250000,\"currency\":\"INR\",\"status\":\"captured\",\"order_id\":\"order_SZUDzoFxKXbirw\",\"invoice_id\":null,\"international\":false,\"method\":\"netbanking\",\"amount_refunded\":0,\"refund_status\":null,\"captured\":true,\"description\":\"Donation\",\"card_id\":null,\"bank\":\"BARB_R\",\"wallet\":null,\"vpa\":null,\"email\":\"bholeupadhyayu@gmail.com\",\"contact\":\"+917570032407\",\"notes\":{\"module\":\"donations\",\"donor_name\":\"Bhole upadhyay Upadhyay\",\"donor_email\":\"bholeupadhyayu@gmail.com\",\"project_id\":\"general\"},\"fee\":5900,\"tax\":900,\"error_code\":null,\"error_description\":null,\"error_source\":null,\"error_step\":null,\"error_reason\":null,\"acquirer_data\":{\"bank_transaction_id\":\"8159968\"},\"created_at\":1775321179,\"reward\":null,\"base_amount\":250000}}},\"created_at\":1775321184}', 1, '2026-04-04 16:46:24'),
(7, 'payment.authorized', 'pay_SZUtkasMRrZcci', 'order_SZUtacxldUyabN', '{\"entity\":\"event\",\"account_id\":\"acc_SJeI5Pr2NG2DSt\",\"event\":\"payment.captured\",\"contains\":[\"payment\"],\"payload\":{\"payment\":{\"entity\":{\"id\":\"pay_SZUtkasMRrZcci\",\"entity\":\"payment\",\"amount\":100000,\"currency\":\"INR\",\"status\":\"captured\",\"order_id\":\"order_SZUtacxldUyabN\",\"invoice_id\":null,\"international\":false,\"method\":\"netbanking\",\"amount_refunded\":0,\"refund_status\":null,\"captured\":true,\"description\":\"Donation\",\"card_id\":null,\"bank\":\"BARB_R\",\"wallet\":null,\"vpa\":null,\"email\":\"bholeupadhyayu@gmail.com\",\"contact\":\"+917570032407\",\"notes\":{\"module\":\"donations\",\"donor_name\":\"Bhole upadhyay Upadhyay\",\"donor_email\":\"bholeupadhyayu@gmail.com\",\"project_id\":\"general\"},\"fee\":2360,\"tax\":360,\"error_code\":null,\"error_description\":null,\"error_source\":null,\"error_step\":null,\"error_reason\":null,\"acquirer_data\":{\"bank_transaction_id\":\"9034168\"},\"created_at\":1775323542,\"reward\":null,\"base_amount\":100000}}},\"created_at\":1775323548}', 1, '2026-04-04 17:25:47');

-- --------------------------------------------------------

--
-- Table structure for table `recurring_donations`
--

CREATE TABLE `recurring_donations` (
  `id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `donor_name` varchar(100) NOT NULL,
  `donor_email` varchar(100) NOT NULL,
  `donor_mobile` varchar(20) NOT NULL,
  `donor_pan` varchar(20) DEFAULT NULL,
  `donor_address` text DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `frequency` enum('monthly','quarterly','half_yearly','yearly') NOT NULL DEFAULT 'monthly',
  `billing_cycle_count` int(11) NOT NULL DEFAULT 0 COMMENT '0 for unlimited / until cancelled',
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
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recurring_donation_transactions`
--

CREATE TABLE `recurring_donation_transactions` (
  `id` int(11) NOT NULL,
  `recurring_donation_id` int(11) NOT NULL,
  `donation_id` int(11) DEFAULT NULL COMMENT 'FK to master donations table',
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
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sanstha_certificates`
--

CREATE TABLE `sanstha_certificates` (
  `id` int(11) NOT NULL,
  `certificate_no` varchar(100) NOT NULL,
  `sanstha_name` varchar(255) NOT NULL,
  `authorized_person` varchar(255) NOT NULL,
  `designation` varchar(150) DEFAULT 'Center Head / Director',
  `auth_type` varchar(100) NOT NULL DEFAULT 'Branch Office',
  `contact_phone` varchar(30) DEFAULT NULL,
  `contact_email` varchar(150) DEFAULT NULL,
  `center_address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `pincode` varchar(20) DEFAULT NULL,
  `valid_from` date NOT NULL,
  `valid_until` date DEFAULT NULL,
  `scope_of_work` text DEFAULT NULL,
  `template_id` int(11) DEFAULT NULL,
  `template_no` tinyint(2) NOT NULL DEFAULT 1,
  `pdf_path` varchar(255) DEFAULT NULL,
  `qr_payload` text DEFAULT NULL,
  `status` enum('active','expired','suspended','revoked') NOT NULL DEFAULT 'active',
  `issued_by` int(11) DEFAULT NULL,
  `issued_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sanstha_certificates`
--

INSERT INTO `sanstha_certificates` (`id`, `certificate_no`, `sanstha_name`, `authorized_person`, `designation`, `auth_type`, `contact_phone`, `contact_email`, `center_address`, `city`, `district`, `state`, `pincode`, `valid_from`, `valid_until`, `scope_of_work`, `template_id`, `template_no`, `pdf_path`, `qr_payload`, `status`, `issued_by`, `issued_at`, `created_at`, `updated_at`) VALUES
(1, 'AUTH-SANSTHA-2026-0001', 'Pragati Jan Seva Sanstha & Skill Training Center', 'Dr. Manoj Kumar Srivastava', 'State Zonal Director', 'District Project Center', '+91 9876543210', 'pragati.lucknow@ngo.org', '45, Vikas Bhawan Road, Gomti Nagar', 'Lucknow', 'Lucknow', 'Uttar Pradesh', '226010', '2026-09-12', '2029-09-12', 'Authorized to conduct official branch operations, beneficiary enrollments, vocational skill training, and social welfare projects under organization guidelines.', NULL, 1, 'uploads/documents/sanstha_certificates/Sanstha_Certificate_AUTH-SANSTHA-2026-0001.pdf', NULL, 'active', 2, '2026-09-12 16:20:06', '2026-09-12 10:50:06', '2026-09-12 10:50:06');

-- --------------------------------------------------------

--
-- Table structure for table `sa_activity_logs`
--

CREATE TABLE `sa_activity_logs` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `activity_type` varchar(60) NOT NULL,
  `title` varchar(160) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `points` int(11) NOT NULL DEFAULT 0,
  `reference_id` int(11) DEFAULT NULL,
  `reference_type` varchar(60) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sa_activity_logs`
--

INSERT INTO `sa_activity_logs` (`id`, `student_id`, `activity_type`, `title`, `description`, `points`, `reference_id`, `reference_type`, `created_at`) VALUES
(1, 1, 'onboarding', 'Registered on Platform', 'Student ambassador registration submitted', 0, NULL, NULL, '2026-06-06 14:50:23'),
(2, 1, 'onboarding_approval', 'Registration Approved', 'Account verified by administrator', 0, NULL, NULL, '2026-06-06 15:11:31'),
(3, 1, 'attendance', 'Marked Daily Attendance', 'Checked in for daily attendance', 0, NULL, NULL, '2026-06-06 15:14:31'),
(4, 1, 'attendance', 'Marked Daily Attendance', 'Checked in for daily attendance', 0, NULL, NULL, '2026-06-07 11:14:16'),
(5, 2, 'onboarding', 'Registered on Platform', 'Student ambassador registration submitted', 0, NULL, NULL, '2026-06-07 11:17:36'),
(6, 2, 'onboarding_approval', 'Registration Approved', 'Account verified by administrator', 0, NULL, NULL, '2026-06-07 11:21:04'),
(7, 1, 'points_awarded', 'Points Awarded', 'Referral verified: bhole upadhyay', 10, 1, 'sa_point_transactions', '2026-06-07 11:21:19'),
(8, 1, 'badge_awarded', 'Badge Awarded', 'Awarded badge: Rising Star. Notes: Anuj is a excellent performer', 0, NULL, 'badge_rules', '2026-06-07 11:26:13'),
(9, 1, 'points_awarded', 'Points Awarded', 'Test User Onboarding reward', 5, 2, 'sa_point_transactions', '2026-06-11 15:24:01'),
(10, 1, 'points_awarded', 'Points Awarded', 'Approved: Completed the mock task verification', 25, 3, 'sa_point_transactions', '2026-06-11 15:25:24'),
(11, 1, 'points_awarded', 'Points Awarded', 'Approved: Completed the mock task verification', 25, 4, 'sa_point_transactions', '2026-06-11 15:29:52');

-- --------------------------------------------------------

--
-- Table structure for table `sa_announcements`
--

CREATE TABLE `sa_announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `target_scope` enum('all','city','college','level') NOT NULL DEFAULT 'all',
  `target_value` varchar(150) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by_user_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sa_attendance_logs`
--

CREATE TABLE `sa_attendance_logs` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `attendance_type` varchar(30) NOT NULL DEFAULT 'Daily',
  `reference_id` int(11) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Present',
  `attendance_date` date NOT NULL,
  `marked_by_user_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sa_attendance_logs`
--

INSERT INTO `sa_attendance_logs` (`id`, `student_id`, `attendance_type`, `reference_id`, `status`, `attendance_date`, `marked_by_user_id`, `created_at`) VALUES
(1, 1, 'Daily', NULL, 'Present', '2026-06-06', NULL, '2026-06-06 15:14:31'),
(2, 1, 'Daily', NULL, 'Present', '2026-06-07', NULL, '2026-06-07 11:14:16');

-- --------------------------------------------------------

--
-- Table structure for table `sa_badges`
--

CREATE TABLE `sa_badges` (
  `id` int(11) NOT NULL,
  `badge_code` varchar(80) DEFAULT NULL,
  `badge_name` varchar(120) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `icon_class` varchar(120) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sa_badges`
--

INSERT INTO `sa_badges` (`id`, `badge_code`, `badge_name`, `description`, `icon_class`, `is_active`, `created_at`) VALUES
(1, NULL, 'Top Performer', 'Highest performer based on points, referrals, and delivery.', 'fa-trophy', 1, '2026-05-18 21:30:50'),
(2, NULL, 'Event Leader', 'Consistently delivers and leads event execution.', 'fa-calendar-check', 1, '2026-05-18 21:30:50'),
(3, NULL, 'Referral Champion', 'Builds strong verified referral growth.', 'fa-share-nodes', 1, '2026-05-18 21:30:50'),
(4, NULL, 'Best Volunteer', 'Reliable execution and strong accountability.', 'fa-medal', 1, '2026-05-18 21:30:50'),
(5, 'RISING_STAR', 'Rising Star', 'Earned by active new ambassadors with early points and attendance.', 'fa-star', 1, '2026-06-06 12:15:39'),
(6, 'COMMUNITY_BUILDER', 'Community Builder', 'Earned for verified referrals and community growth.', 'fa-people-group', 1, '2026-06-06 12:15:39'),
(7, 'HEALTH_ADVOCATE', 'Health Advocate', 'Earned for approved health, hygiene, nutrition, medical, or blood-drive campaigns.', 'fa-heart-pulse', 1, '2026-06-06 12:15:39'),
(8, 'FUNDRAISING_CHAMPION', 'Fundraising Champion', 'Earned for verified donor and fundraising performance.', 'fa-hand-holding-dollar', 1, '2026-06-06 12:15:39'),
(9, 'CITY_INFLUENCER', 'City Influencer', 'Earned by top city performers with strong campaign impact.', 'fa-city', 1, '2026-06-06 12:15:39'),
(10, 'LEADERSHIP_ELITE', 'Leadership Elite', 'Earned by senior leaders with strong points, referrals, and onboarding performance.', 'fa-crown', 1, '2026-06-06 12:15:39');

-- --------------------------------------------------------

--
-- Table structure for table `sa_certificates`
--

CREATE TABLE `sa_certificates` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `certificate_type` varchar(80) NOT NULL,
  `certificate_title` varchar(200) DEFAULT NULL,
  `title` varchar(180) NOT NULL,
  `certificate_no` varchar(60) NOT NULL,
  `pdf_path` varchar(255) DEFAULT NULL,
  `template_id` int(11) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'Generated',
  `verification_url` varchar(500) DEFAULT NULL,
  `issued_at` datetime NOT NULL DEFAULT current_timestamp(),
  `issued_by_user_id` int(11) DEFAULT NULL,
  `issued_for` text DEFAULT NULL,
  `event_title` varchar(200) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `recipient_name` varchar(120) DEFAULT NULL,
  `recipient_college` varchar(150) DEFAULT NULL,
  `recipient_level` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sa_events`
--

CREATE TABLE `sa_events` (
  `id` int(11) NOT NULL,
  `title` varchar(180) NOT NULL,
  `description` text DEFAULT NULL,
  `event_type` varchar(60) DEFAULT NULL,
  `city_name` varchar(120) DEFAULT NULL,
  `venue` varchar(180) DEFAULT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'Pending',
  `submitted_by_student_id` int(11) DEFAULT NULL,
  `reviewed_by_user_id` int(11) DEFAULT NULL,
  `report_notes` text DEFAULT NULL,
  `report_images_json` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sa_event_registrations`
--

CREATE TABLE `sa_event_registrations` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'Registered',
  `registered_at` datetime NOT NULL DEFAULT current_timestamp(),
  `attendance_marked_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sa_login_logs`
--

CREATE TABLE `sa_login_logs` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `login_at` datetime NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sa_login_logs`
--

INSERT INTO `sa_login_logs` (`id`, `student_id`, `login_at`, `ip_address`, `user_agent`) VALUES
(1, 1, '2026-06-06 15:14:11', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0'),
(2, 1, '2026-06-07 11:12:12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0'),
(3, 1, '2026-06-11 15:03:25', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36');

-- --------------------------------------------------------

--
-- Table structure for table `sa_notifications`
--

CREATE TABLE `sa_notifications` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `title` varchar(160) NOT NULL,
  `message` text NOT NULL,
  `channel_type` varchar(30) NOT NULL DEFAULT 'Dashboard',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sa_partners`
--

CREATE TABLE `sa_partners` (
  `id` int(11) NOT NULL,
  `partner_code` varchar(32) NOT NULL,
  `owner_name` varchar(150) NOT NULL,
  `business_name` varchar(200) NOT NULL,
  `owner_mobile` varchar(20) NOT NULL,
  `business_mobile` varchar(20) DEFAULT NULL,
  `owner_email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `activity_type` enum('retail','physical_activity_center') NOT NULL DEFAULT 'retail',
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `business_address` text DEFAULT NULL,
  `city_name` varchar(120) DEFAULT NULL,
  `state_name` varchar(120) DEFAULT NULL,
  `status` enum('Pending','Active','Rejected','Suspended') NOT NULL DEFAULT 'Pending',
  `api_key` varchar(64) DEFAULT NULL,
  `api_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `referred_by_student_id` int(11) DEFAULT NULL,
  `rejection_reason` varchar(255) DEFAULT NULL,
  `approved_by_user_id` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sa_penalties`
--

CREATE TABLE `sa_penalties` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `program_id` int(11) DEFAULT NULL,
  `point_transaction_id` int(11) DEFAULT NULL,
  `penalty_code` varchar(80) NOT NULL,
  `penalty_type` enum('warning','points_deduction','suspension','termination') NOT NULL DEFAULT 'warning',
  `severity` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `status` enum('draft','active','resolved','cancelled') NOT NULL DEFAULT 'active',
  `penalty_points` int(11) NOT NULL DEFAULT 0,
  `reason_title` varchar(150) NOT NULL,
  `reason_details` text DEFAULT NULL,
  `evidence_path` varchar(255) DEFAULT NULL,
  `starts_at` datetime DEFAULT current_timestamp(),
  `ends_at` datetime DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `imposed_by_user_id` int(11) DEFAULT NULL,
  `resolved_by_user_id` int(11) DEFAULT NULL,
  `created_by_user_id` int(11) DEFAULT NULL,
  `updated_by_user_id` int(11) DEFAULT NULL,
  `deleted_by_user_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sa_point_rules`
--

CREATE TABLE `sa_point_rules` (
  `id` int(11) NOT NULL,
  `program_id` int(11) DEFAULT NULL,
  `rule_code` varchar(80) NOT NULL,
  `rule_name` varchar(150) NOT NULL,
  `category` enum('reward','multiplier','bonus','penalty') NOT NULL DEFAULT 'reward',
  `trigger_key` varchar(100) NOT NULL,
  `entity_scope` enum('student','task_submission','attendance','donation','referral','vendor_lead','program','manual','system') NOT NULL DEFAULT 'student',
  `verification_mode` enum('none','admin_review','evidence_required','system_verified') NOT NULL DEFAULT 'none',
  `base_points` int(11) NOT NULL DEFAULT 0,
  `multiplier_value` decimal(8,2) NOT NULL DEFAULT 1.00,
  `max_awards_per_period` int(11) DEFAULT NULL,
  `period_scope` enum('none','daily','weekly','monthly','program','lifetime') NOT NULL DEFAULT 'none',
  `cooldown_days` int(11) NOT NULL DEFAULT 0,
  `requires_approval` tinyint(1) NOT NULL DEFAULT 0,
  `is_stackable` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `effective_from` datetime DEFAULT NULL,
  `effective_to` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by_user_id` int(11) DEFAULT NULL,
  `updated_by_user_id` int(11) DEFAULT NULL,
  `deleted_by_user_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sa_point_rules`
--

INSERT INTO `sa_point_rules` (`id`, `program_id`, `rule_code`, `rule_name`, `category`, `trigger_key`, `entity_scope`, `verification_mode`, `base_points`, `multiplier_value`, `max_awards_per_period`, `period_scope`, `cooldown_days`, `requires_approval`, `is_stackable`, `is_active`, `effective_from`, `effective_to`, `notes`, `created_by_user_id`, `updated_by_user_id`, `deleted_by_user_id`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 'USER_ONBOARDING', 'User Onboarding', 'reward', 'user_onboarding', 'student', 'system_verified', 5, 1.00, NULL, 'none', 0, 0, 0, 1, NULL, NULL, 'Verified app signup.', NULL, NULL, NULL, '2026-06-06 14:19:10', '2026-06-06 14:19:10', NULL),
(2, 1, 'ACTIVE_USER_ONBOARDING', 'Active User Onboarding', 'reward', 'active_user_onboarding', 'student', 'admin_review', 10, 1.00, NULL, 'none', 0, 1, 0, 1, NULL, NULL, 'User completes profile or required activity.', NULL, NULL, NULL, '2026-06-06 14:19:10', '2026-06-06 14:19:10', NULL),
(3, 1, 'VENDOR_MEETING', 'Vendor Meeting', 'reward', 'vendor_meeting', 'vendor_lead', 'admin_review', 10, 1.00, NULL, 'none', 0, 1, 0, 1, NULL, NULL, 'Verified visit or pitch.', NULL, NULL, NULL, '2026-06-06 14:19:10', '2026-06-06 14:19:10', NULL),
(4, 1, 'VENDOR_ONBOARDING', 'Vendor Onboarding', 'reward', 'vendor_onboarding', 'vendor_lead', 'admin_review', 50, 1.00, NULL, 'none', 0, 1, 0, 1, NULL, NULL, 'Successful vendor onboarding.', NULL, NULL, NULL, '2026-06-06 14:19:10', '2026-06-06 14:19:10', NULL),
(5, 1, 'PREMIUM_VENDOR_ONBOARDING', 'Premium Vendor Onboarding', 'reward', 'premium_vendor_onboarding', 'vendor_lead', 'admin_review', 100, 1.00, NULL, 'none', 0, 1, 0, 1, NULL, NULL, 'Paid or premium partner onboarding.', NULL, NULL, NULL, '2026-06-06 14:19:10', '2026-06-06 14:19:10', NULL),
(6, 1, 'SMALL_AWARENESS_ACTIVITY', 'Conduct Small Awareness Activity', 'reward', 'small_awareness_activity', 'student', 'evidence_required', 25, 1.00, NULL, 'none', 0, 1, 0, 1, NULL, NULL, 'Classroom or society awareness activity.', NULL, NULL, NULL, '2026-06-06 14:19:10', '2026-06-06 14:19:10', NULL),
(7, 1, 'MAJOR_EVENT', 'Conduct Major Event', 'reward', 'major_event', 'student', 'evidence_required', 100, 1.00, NULL, 'none', 0, 1, 0, 1, NULL, NULL, 'Workshop, camp, or college event.', NULL, NULL, NULL, '2026-06-06 14:19:10', '2026-06-06 14:19:10', NULL),
(8, 1, 'EVENT_SUPPORT', 'Event Participation Support', 'reward', 'event_support', 'student', 'admin_review', 15, 1.00, NULL, 'none', 0, 1, 0, 1, NULL, NULL, 'Volunteer support for an event.', NULL, NULL, NULL, '2026-06-06 14:19:10', '2026-06-06 14:19:10', NULL),
(9, 1, 'DONOR_GENERATED', 'Donation Contributor Generated', 'reward', 'donor_generated', 'donation', 'system_verified', 20, 1.00, NULL, 'none', 0, 0, 0, 1, NULL, NULL, 'Each verified contributor.', NULL, NULL, NULL, '2026-06-06 14:19:10', '2026-06-06 14:19:10', NULL),
(10, 1, 'DONATION_500_MILESTONE', 'Donation 500 Milestone', 'bonus', 'donation_500_milestone', 'donation', 'system_verified', 25, 1.00, NULL, 'none', 0, 0, 0, 1, NULL, NULL, 'Additional bonus for 500 INR milestone.', NULL, NULL, NULL, '2026-06-06 14:19:10', '2026-06-06 14:19:10', NULL),
(11, 1, 'DONATION_2000_MILESTONE', 'Donation 2000 Milestone', 'bonus', 'donation_2000_milestone', 'donation', 'system_verified', 75, 1.00, NULL, 'none', 0, 0, 0, 1, NULL, NULL, 'Additional bonus for 2000 INR milestone.', NULL, NULL, NULL, '2026-06-06 14:19:10', '2026-06-06 14:19:10', NULL),
(12, 1, 'REFERRAL_NEW_INTERN', 'Referral of New Intern', 'reward', 'referral_new_intern', 'referral', 'admin_review', 10, 1.00, NULL, 'none', 0, 1, 0, 1, NULL, NULL, 'Active intern referral reward.', NULL, NULL, NULL, '2026-06-06 14:19:10', '2026-06-06 14:19:10', NULL),
(13, 1, 'REFERRAL_CAMPUS_AMBASSADOR', 'Referral of Campus Ambassador', 'reward', 'referral_campus_ambassador', 'referral', 'admin_review', 30, 1.00, NULL, 'none', 0, 1, 0, 1, NULL, NULL, 'Successful campus ambassador referral.', NULL, NULL, NULL, '2026-06-06 14:19:10', '2026-06-06 14:19:10', NULL),
(14, 1, 'SOCIAL_MEDIA_CONTENT', 'Social Media Awareness Content', 'reward', 'social_media_content', 'student', 'evidence_required', 10, 1.00, NULL, 'none', 0, 1, 0, 1, NULL, NULL, 'Approved content posted.', NULL, NULL, NULL, '2026-06-06 14:19:10', '2026-06-06 14:19:10', NULL),
(15, 1, 'CAMPAIGN_LEADERSHIP', 'Campaign Leadership', 'reward', 'campaign_leadership', 'student', 'admin_review', 50, 1.00, NULL, 'none', 0, 1, 0, 1, NULL, NULL, 'Lead organizer reward.', NULL, NULL, NULL, '2026-06-06 14:19:10', '2026-06-06 14:19:10', NULL),
(16, 1, 'COLLEGE_PARTNERSHIP_LEAD', 'College Partnership Lead', 'reward', 'college_partnership_lead', 'vendor_lead', 'admin_review', 75, 1.00, NULL, 'none', 0, 1, 0, 1, NULL, NULL, 'Successful tie-up.', NULL, NULL, NULL, '2026-06-06 14:19:10', '2026-06-06 14:19:10', NULL),
(17, 1, 'SPONSOR_BUSINESS_PARTNERSHIP_LEAD', 'Sponsor / Business Partnership Lead', 'reward', 'sponsor_business_partnership_lead', 'vendor_lead', 'admin_review', 100, 1.00, NULL, 'none', 0, 1, 0, 1, NULL, NULL, 'Verified collaboration.', NULL, NULL, NULL, '2026-06-06 14:19:10', '2026-06-06 14:19:10', NULL),
(18, 1, 'ATTENDANCE_90_BONUS', '90 Percent Attendance Bonus', 'bonus', 'attendance_90_bonus', 'attendance', 'system_verified', 25, 1.00, NULL, 'none', 0, 0, 0, 1, NULL, NULL, 'Attendance quality bonus.', NULL, NULL, NULL, '2026-06-06 14:19:10', '2026-06-06 14:19:10', NULL),
(19, 1, 'WEEKLY_REPORTING_CONSISTENCY', 'Weekly Reporting Consistency', 'bonus', 'weekly_reporting_consistency', 'student', 'admin_review', 20, 1.00, NULL, 'none', 0, 1, 0, 1, NULL, NULL, 'Consistency reward.', NULL, NULL, NULL, '2026-06-06 14:19:10', '2026-06-06 14:19:10', NULL),
(20, 1, 'POSITIVE_VENDOR_FEEDBACK', 'Positive Vendor Feedback', 'bonus', 'positive_vendor_feedback', 'vendor_lead', 'admin_review', 15, 1.00, NULL, 'none', 0, 1, 0, 1, NULL, NULL, 'Verified positive vendor feedback.', NULL, NULL, NULL, '2026-06-06 14:19:10', '2026-06-06 14:19:10', NULL),
(21, 1, 'BEST_CITY_PERFORMER', 'Best City Performer', 'bonus', 'best_city_performer', 'program', 'admin_review', 50, 1.00, NULL, 'none', 0, 1, 0, 1, NULL, NULL, 'Top city performer bonus.', NULL, NULL, NULL, '2026-06-06 14:19:10', '2026-06-06 14:19:10', NULL),
(22, 1, 'BEST_CAMPUS_PERFORMER', 'Best Campus Performer', 'bonus', 'best_campus_performer', 'program', 'admin_review', 50, 1.00, NULL, 'none', 0, 1, 0, 1, NULL, NULL, 'Top campus performer bonus.', NULL, NULL, NULL, '2026-06-06 14:19:10', '2026-06-06 14:19:10', NULL),
(23, 1, 'TEAM_RETENTION_MAINTAINED', 'Team Retention Maintained', 'bonus', 'team_retention_maintained', 'program', 'admin_review', 40, 1.00, NULL, 'none', 0, 1, 0, 1, NULL, NULL, 'Retention bonus.', NULL, NULL, NULL, '2026-06-06 14:19:10', '2026-06-06 14:19:10', NULL),
(24, 1, 'FAKE_ONBOARDING', 'Fake Onboarding Penalty', 'penalty', 'fake_onboarding', 'student', 'admin_review', -100, 1.00, NULL, 'none', 0, 1, 0, 1, NULL, NULL, 'Penalty for fake onboarding.', NULL, NULL, NULL, '2026-06-06 14:19:10', '2026-06-06 14:19:10', NULL),
(25, 1, 'MISLEADING_DONATION', 'Misleading Donation Collection Penalty', 'penalty', 'misleading_donation_collection', 'donation', 'admin_review', -150, 1.00, NULL, 'none', 0, 1, 0, 1, NULL, NULL, 'Penalty for misleading donation collection.', NULL, NULL, NULL, '2026-06-06 14:19:10', '2026-06-06 14:19:10', NULL),
(26, 1, 'INACTIVE_TWO_WEEKS', 'Inactive for Two Weeks Penalty', 'penalty', 'inactive_two_weeks', 'student', 'system_verified', -25, 1.00, NULL, 'none', 0, 0, 0, 1, NULL, NULL, 'Penalty for inactivity.', NULL, NULL, NULL, '2026-06-06 14:19:10', '2026-06-06 14:19:10', NULL),
(27, 1, 'FAKE_REFERRALS', 'Fake Referrals Penalty', 'penalty', 'fake_referrals', 'referral', 'admin_review', -50, 1.00, NULL, 'none', 0, 1, 0, 1, NULL, NULL, 'Penalty for fake referrals.', NULL, NULL, NULL, '2026-06-06 14:19:10', '2026-06-06 14:19:10', NULL),
(28, 1, 'MISCONDUCT', 'Misconduct Penalty', 'penalty', 'misconduct', 'student', 'admin_review', -50, 1.00, NULL, 'none', 0, 1, 0, 1, NULL, NULL, 'Penalty entry for misconduct; suspension can be handled in sa_penalties.', NULL, NULL, NULL, '2026-06-06 14:19:10', '2026-06-06 14:19:10', NULL),
(29, 1, 'TASK_SUBMISSION_APPROVED', 'Task Submission Approved', 'reward', 'task_submission_approved', 'task_submission', 'admin_review', 0, 1.00, NULL, 'none', 0, 1, 0, 1, NULL, NULL, 'Dynamic points from task points_reward on approval.', NULL, NULL, NULL, '2026-06-06 14:20:01', '2026-06-06 14:20:01', NULL),
(30, 1, 'MANUAL_POINT_ADJUSTMENT', 'Manual Point Adjustment', 'reward', 'manual_point_adjustment', 'manual', 'admin_review', 0, 1.00, NULL, 'none', 0, 1, 0, 1, NULL, NULL, 'Coordinator manual credit or debit.', NULL, NULL, NULL, '2026-06-06 14:20:01', '2026-06-06 14:20:01', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sa_point_transactions`
--

CREATE TABLE `sa_point_transactions` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `program_id` int(11) DEFAULT NULL,
  `rule_id` int(11) DEFAULT NULL,
  `source_type` enum('manual_adjustment','registration','onboarding_approval','task_submission','task_approval','attendance','donation','referral','vendor_lead','badge','penalty','migration','system') NOT NULL DEFAULT 'system',
  `source_id` int(11) DEFAULT NULL,
  `reference_code` varchar(100) DEFAULT NULL,
  `idempotency_key` varchar(190) DEFAULT NULL,
  `direction` enum('credit','debit') NOT NULL,
  `base_points` int(11) NOT NULL DEFAULT 0,
  `multiplier_value` decimal(8,2) NOT NULL DEFAULT 1.00,
  `points_delta` int(11) NOT NULL,
  `balance_after` int(11) DEFAULT NULL,
  `transaction_status` enum('pending','posted','reversed','void') NOT NULL DEFAULT 'posted',
  `description` varchar(255) DEFAULT NULL,
  `approved_by_user_id` int(11) DEFAULT NULL,
  `reversed_transaction_id` int(11) DEFAULT NULL,
  `posted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by_user_id` int(11) DEFAULT NULL,
  `updated_by_user_id` int(11) DEFAULT NULL,
  `deleted_by_user_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sa_point_transactions`
--

INSERT INTO `sa_point_transactions` (`id`, `student_id`, `program_id`, `rule_id`, `source_type`, `source_id`, `reference_code`, `idempotency_key`, `direction`, `base_points`, `multiplier_value`, `points_delta`, `balance_after`, `transaction_status`, `description`, `approved_by_user_id`, `reversed_transaction_id`, `posted_at`, `created_by_user_id`, `updated_by_user_id`, `deleted_by_user_id`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 1, 12, 'referral', 1, 'REF-1', 'referral-verify:1', 'credit', 10, 1.00, 10, 10, 'posted', 'Referral verified: bhole upadhyay', 2, NULL, '2026-06-07 11:21:19', 2, 2, NULL, '2026-06-07 11:21:19', '2026-06-07 11:21:19', NULL),
(2, 1, 1, 1, '', NULL, 'TEST-ONB-001', '5685bd6b193bb28462391a651763efa6e780717d0c056e0d4fb4955294833f64', 'credit', 5, 1.00, 5, 15, 'posted', 'Test User Onboarding reward', 2, NULL, '2026-06-11 15:24:01', 2, 2, NULL, '2026-06-11 15:24:01', '2026-06-11 15:24:01', NULL),
(3, 1, 1, 6, 'task_submission', 1, 'TASK-1', '9d3c9e131f56b1208baed76bdae1594d9d98dd1a889de19f7a2ae245da661118', 'credit', 25, 1.00, 25, 40, 'posted', 'Approved: Completed the mock task verification', 2, NULL, '2026-06-11 15:25:24', 2, 2, NULL, '2026-06-11 15:25:24', '2026-06-11 15:25:24', NULL),
(4, 1, 1, 6, 'task_submission', 2, 'TASK-2', 'baf234b254e32651a51d43426ade58b3a23f323ee4f32f0efb39adc5442ccdd8', 'credit', 25, 1.00, 25, 65, 'posted', 'Approved: Completed the mock task verification', 2, NULL, '2026-06-11 15:29:52', 2, 2, NULL, '2026-06-11 15:29:52', '2026-06-11 15:29:52', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sa_programs`
--

CREATE TABLE `sa_programs` (
  `id` int(11) NOT NULL,
  `program_code` varchar(50) NOT NULL,
  `program_name` varchar(150) NOT NULL,
  `program_type` enum('student_ambassador','internship','hybrid') NOT NULL DEFAULT 'student_ambassador',
  `batch_name` varchar(100) DEFAULT NULL,
  `status` enum('draft','active','paused','completed','archived') NOT NULL DEFAULT 'draft',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `mentor_user_id` int(11) DEFAULT NULL,
  `max_students` int(11) DEFAULT NULL,
  `created_by_user_id` int(11) DEFAULT NULL,
  `updated_by_user_id` int(11) DEFAULT NULL,
  `deleted_by_user_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sa_programs`
--

INSERT INTO `sa_programs` (`id`, `program_code`, `program_name`, `program_type`, `batch_name`, `status`, `start_date`, `end_date`, `description`, `mentor_user_id`, `max_students`, `created_by_user_id`, `updated_by_user_id`, `deleted_by_user_id`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'SA-CORE-2026', 'Student Ambassador Core Program 2026', 'hybrid', 'Batch 2026', 'active', '2026-04-01', '2027-03-31', 'Default seed program for the student ambassador and internship workflow.', NULL, NULL, NULL, NULL, NULL, '2026-06-06 12:15:38', '2026-06-06 12:15:38', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sa_referrals`
--

CREATE TABLE `sa_referrals` (
  `id` int(11) NOT NULL,
  `program_id` int(11) DEFAULT NULL,
  `referrer_student_id` int(11) NOT NULL,
  `referred_student_id` int(11) DEFAULT NULL,
  `referral_type` enum('intern','campus_ambassador','student_user','donor','other') NOT NULL DEFAULT 'intern',
  `referral_code_used` varchar(80) DEFAULT NULL,
  `referred_full_name` varchar(150) DEFAULT NULL,
  `referred_email` varchar(150) DEFAULT NULL,
  `referred_mobile` varchar(30) DEFAULT NULL,
  `verification_status` enum('pending','verified','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `verified_at` datetime DEFAULT NULL,
  `verified_by_user_id` int(11) DEFAULT NULL,
  `rejection_reason` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by_user_id` int(11) DEFAULT NULL,
  `updated_by_user_id` int(11) DEFAULT NULL,
  `deleted_by_user_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sa_referrals`
--

INSERT INTO `sa_referrals` (`id`, `program_id`, `referrer_student_id`, `referred_student_id`, `referral_type`, `referral_code_used`, `referred_full_name`, `referred_email`, `referred_mobile`, `verification_status`, `verified_at`, `verified_by_user_id`, `rejection_reason`, `notes`, `created_by_user_id`, `updated_by_user_id`, `deleted_by_user_id`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 1, 2, 'intern', 'INT-15014F', 'bhole upadhyay', 'bholeupadhyayu@gmail.com', '7572021365', 'verified', '2026-06-07 11:21:19', 2, NULL, NULL, NULL, 2, NULL, '2026-06-07 11:17:36', '2026-06-07 11:21:19', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sa_students`
--

CREATE TABLE `sa_students` (
  `id` int(11) NOT NULL,
  `student_no` varchar(30) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `mobile` varchar(20) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'Pending',
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `gender` varchar(20) DEFAULT NULL,
  `college_name` varchar(150) DEFAULT NULL,
  `city_name` varchar(120) DEFAULT NULL,
  `state_name` varchar(120) DEFAULT NULL,
  `department_name` varchar(120) DEFAULT NULL,
  `year_of_study` varchar(30) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `referral_code` varchar(30) NOT NULL,
  `referred_by_student_id` int(11) DEFAULT NULL,
  `total_points` int(11) NOT NULL DEFAULT 0,
  `monthly_attendance_percentage` decimal(5,2) DEFAULT 0.00,
  `last_attendance_bonus_date` datetime DEFAULT NULL,
  `rank_position` int(11) DEFAULT NULL,
  `level_name` varchar(60) NOT NULL DEFAULT 'Student Ambassador',
  `program_id` int(11) DEFAULT NULL,
  `certificates_earned` int(11) NOT NULL DEFAULT 0,
  `last_login_at` datetime DEFAULT NULL,
  `login_count` int(11) NOT NULL DEFAULT 0,
  `approved_at` datetime DEFAULT NULL,
  `approved_by_user_id` int(11) DEFAULT NULL,
  `rejection_reason` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sa_students`
--

INSERT INTO `sa_students` (`id`, `student_no`, `full_name`, `email`, `mobile`, `password_hash`, `status`, `is_verified`, `gender`, `college_name`, `city_name`, `state_name`, `department_name`, `year_of_study`, `address`, `referral_code`, `referred_by_student_id`, `total_points`, `monthly_attendance_percentage`, `last_attendance_bonus_date`, `rank_position`, `level_name`, `program_id`, `certificates_earned`, `last_login_at`, `login_count`, `approved_at`, `approved_by_user_id`, `rejection_reason`, `created_at`, `updated_at`) VALUES
(1, 'SA-2026-0001', 'anuj upadhyay', 'anujbca2022@gmail.com', '7570032407', '$2y$10$RtxX4mqdOhL3zhstn5fsrOHHwLpAzHDHNbgizbnvzsXTHFT1BAcce', 'Active', 1, 'Male', 'united insitutie of management', 'jaunpur', 'Uttar Pradesh', 'BCA', '2nd Year', 'baserwan\r\nkaserwan', 'INT-15014F', NULL, 65, 0.00, NULL, NULL, 'Student Ambassador', 1, 0, '2026-06-11 15:03:25', 3, '2026-06-06 15:11:31', 2, NULL, '2026-06-06 14:50:23', '2026-06-11 15:29:52'),
(2, 'SA-2026-0002', 'bhole upadhyay', 'bholeupadhyayu@gmail.com', '7572021365', '$2y$10$o5n6eqgnR9hv0tu/SLhuNe2qK4i0uT/4mqXn1x09s85e8TKmj34nK', 'Active', 1, 'Male', 'united insitutie of management', 'jaunpur', 'Uttar Pradesh', 'BCA', '3rd Year', 'baserwan\r\nkaserwan', 'INT-EDEEB4', 1, 0, 0.00, NULL, NULL, 'Student Ambassador', NULL, 0, NULL, 0, '2026-06-07 11:21:04', 2, NULL, '2026-06-07 11:17:36', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sa_student_badges`
--

CREATE TABLE `sa_student_badges` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `badge_id` int(11) NOT NULL,
  `assignment_type` enum('auto','manual','override') NOT NULL DEFAULT 'manual',
  `badge_rule_id` int(11) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `override_reason` varchar(255) DEFAULT NULL,
  `awarded_by_user_id` int(11) DEFAULT NULL,
  `awarded_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sa_student_badges`
--

INSERT INTO `sa_student_badges` (`id`, `student_id`, `badge_id`, `assignment_type`, `badge_rule_id`, `notes`, `override_reason`, `awarded_by_user_id`, `awarded_at`) VALUES
(1, 1, 5, 'manual', NULL, 'Anuj is a excellent performer', 'Manual coordinator award.', 2, '2026-06-07 07:56:13');

-- --------------------------------------------------------

--
-- Table structure for table `sa_student_event_requests`
--

CREATE TABLE `sa_student_event_requests` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `event_title` varchar(200) NOT NULL,
  `event_date` date DEFAULT NULL,
  `event_location` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `expected_attendees` int(11) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Completed') NOT NULL DEFAULT 'Pending',
  `linked_event_id` int(11) DEFAULT NULL,
  `reviewed_by_user_id` int(11) DEFAULT NULL,
  `review_notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sa_tasks`
--

CREATE TABLE `sa_tasks` (
  `id` int(11) NOT NULL,
  `title` varchar(180) NOT NULL,
  `description` text DEFAULT NULL,
  `campaign_name` varchar(150) DEFAULT NULL,
  `task_type` varchar(60) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `points_reward` int(11) NOT NULL DEFAULT 25,
  `status` varchar(30) NOT NULL DEFAULT 'Draft',
  `created_by_user_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sa_task_submissions`
--

CREATE TABLE `sa_task_submissions` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `proof_path` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'Submitted',
  `reviewed_by_user_id` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `admin_comment` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sa_vendor_leads`
--

CREATE TABLE `sa_vendor_leads` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `program_id` int(11) DEFAULT NULL,
  `lead_type` enum('vendor_meeting','vendor_onboarding','premium_vendor_onboarding','college_partnership','sponsor_partnership','business_partnership','other') NOT NULL DEFAULT 'vendor_meeting',
  `lead_status` enum('new','contacted','meeting_done','verified','converted','rejected','inactive') NOT NULL DEFAULT 'new',
  `verification_status` enum('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  `business_name` varchar(180) NOT NULL,
  `contact_name` varchar(120) DEFAULT NULL,
  `contact_phone` varchar(30) DEFAULT NULL,
  `contact_email` varchar(150) DEFAULT NULL,
  `city_name` varchar(120) DEFAULT NULL,
  `state_name` varchar(120) DEFAULT NULL,
  `is_premium` tinyint(1) NOT NULL DEFAULT 0,
  `meeting_date` datetime DEFAULT NULL,
  `conversion_date` datetime DEFAULT NULL,
  `proof_path` varchar(255) DEFAULT NULL,
  `verification_notes` text DEFAULT NULL,
  `admin_comment` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `verified_by_user_id` int(11) DEFAULT NULL,
  `created_by_user_id` int(11) DEFAULT NULL,
  `updated_by_user_id` int(11) DEFAULT NULL,
  `deleted_by_user_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('about_desc', '\r\n<div class=\"about-content\"><p><strong>Dnyaneshwari Charitable Foundation</strong> is a dedicated <strong>non-profit organization</strong> solely committed to the service, welfare, and upliftment of the elderly and underprivileged sections of society.</p><p>The foundation operates a specialized <strong>old age home</strong> that provides a safe, affectionate, and dignified residential environment for senior citizens, ensuring their comprehensive physical and emotional well-being.</p><p>It specializes in offering <strong>24/7 caretaker support</strong>, <strong>professional palliative care</strong>, and dedicated assistance for <strong>bedridden and critically ill geriatric patients</strong>.</p><p>Through compassionate care, dignity, and continuous support, the foundation strives to create a secure and respectful environment where every senior citizen can live with comfort, care, and dignity.</p></div>\r\n\r\n'),
('about_image', 'uploads/content/about_us_1788162530.jpg'),
('about_mission', '<section class=\"mission-content\"><h2>Our Mission</h2><p>To enrich the lives of <strong>senior citizens</strong> in the evening of their journey by offering <strong>unconditional love</strong>, <strong>essential medical aid</strong>, and a <strong>peaceful and dignified shelter</strong>.</p><p>The foundation strives to prevent neglect and ensure that every <strong>helpless and ailing elder</strong> receives compassionate care, respect, and continuous support, bringing a consistent smile to their face.</p></section>'),
('about_title', 'Why We Exist'),
('about_vision', '<section class=\"vision-content\"><h2>Our Vision</h2><p>To cultivate an <strong>inclusive and empathetic society</strong> where no senior citizen is left <strong>destitute, abandoned, or unassisted</strong>.</p><p>We envision a future where every elderly person receives <strong>holistic care</strong>, access to essential <strong>healthcare resources</strong>, and the <strong>social dignity and respect</strong> they deserve throughout their lives, until their final days.</p></section>'),
('active_payment_gateway', 'razorpay'),
('admin_training_videos_json', '[]'),
('advisory_board_json', '{\"title\":\"Join Our Expert Network\",\"intro\":\"We invite qualified professionals to contribute to public wellness through education, consultation, and awareness initiatives.\",\"contribution\":[\"Contribute approximately 8 hours weekly.\",\"Participate in awareness initiatives.\",\"Provide expert guidance.\",\"Support community wellness programs.\",\"Mentor volunteers and interns.\"],\"expertise\":[\"Nutrition & Dietetics\",\"Fitness & Exercise Science\",\"Sports Nutrition\",\"Psychology\",\"Physiotherapy\",\"Preventive Healthcare\",\"Lifestyle Medicine\"]}'),
('ambassador_program_json', '{\"title\":\"Become a wellness advocate in your city.\",\"responsibilities\":[\"Organize awareness activities.\",\"Encourage healthy lifestyle adoption.\",\"Connect citizens with wellness resources.\",\"Support local outreach initiatives.\"],\"recognition\":[\"Ambassador Certificate\",\"Leadership Recognition\",\"Annual Awards\"]}'),
('bank_details', 'Bank Name: XYZ Bank\r\nAC No: 123456789\r\nIFSC: XYZ0001'),
('birthday_cron_key', ''),
('career_guidance_banner_image', ''),
('career_guidance_counseling_text', 'Need personalized guidance on choosing the right career stream or preparing for government and private sector jobs? Connect with our certified NGO career counselors for free advisory sessions.'),
('career_guidance_desc', 'Our Career Guidance and Skills Development Initiative bridges the gap between grassroots education and viable employment. We offer hands-on vocational modules, digital literacy programs, competitive exam preparation, and free one-on-one mentorship sessions to help youth build sustainable livelihoods.'),
('career_guidance_email', 'careers@ngocare.org'),
('career_guidance_helpline', '+91 98765 43210'),
('career_guidance_subtitle', 'Empowering youth with market-ready vocational skills, interview mentorship, and career counseling.'),
('career_guidance_title', 'Career Guidance & Skills Training'),
('closing_statement', '“A healthier nation is built when communities, experts, businesses, and citizens come together with a shared purpose.” Join Mouli FitLife Foundation and become part of India’s wellness movement.'),
('contact_address', ''),
('contact_email', ''),
('contact_map_iframe', '<iframe\r\n    src=\"https://www.google.com/maps?q=2nd%20Floor,%20Dharma%20Villa,%20Wazidpur%20Tiraha,%20Jaunpur,%20Uttar%20Pradesh%20222002,%20India&output=embed\"\r\n    width=\"100%\"\r\n    height=\"400\"\r\n    style=\"border:0;\"\r\n    allowfullscreen=\"\"\r\n    loading=\"lazy\"\r\n    referrerpolicy=\"no-referrer-when-downgrade\">\r\n</iframe>'),
('contact_phone', ''),
('csr_partnership_json', '{\"title\":\"Partner With Us For Sustainable Community Wellness\",\"intro\":\"Organizations can support various initiatives through CSR contributions.\",\"support_areas\":[\"Health Awareness Campaigns\",\"School Wellness Programs\",\"Community Fitness Initiatives\",\"Nutrition Education Programs\",\"Volunteer Development\",\"Digital Health Inclusion\"],\"benefits\":[\"Measurable Social Impact\",\"Program Transparency\",\"Community Engagement\",\"Recognition & Reporting Support\"]}'),
('doc_brand_1_address', ''),
('doc_brand_1_logo', 'uploads/settings/doc_brand_1_logo_1775288037.png'),
('doc_brand_1_name', 'first '),
('doc_brand_1_phone', '7570032407'),
('doc_brand_1_signature', 'uploads/settings/doc_brand_1_signature_1775288037.jpg'),
('doc_brand_1_website', ''),
('doc_brand_2_address', ''),
('doc_brand_2_name', ''),
('doc_brand_2_phone', ''),
('doc_brand_2_website', ''),
('doc_brand_3_address', ''),
('doc_brand_3_name', ''),
('doc_brand_3_phone', ''),
('doc_brand_3_website', ''),
('donate_qr_image', ''),
('email_from_name', ''),
('enable_80g', '1'),
('focus_areas_json', '[{\"title\":\"Nutrition Awareness\",\"description\":\"Promoting healthy eating habits through workshops, awareness campaigns, and expert-led sessions.\",\"icon\":\"fa-apple-whole\"},{\"title\":\"Physical Fitness\",\"description\":\"Encouraging active lifestyles through fitness challenges, community programs, and wellness events.\",\"icon\":\"fa-person-running\"},{\"title\":\"Preventive Healthcare\",\"description\":\"Educating citizens on lifestyle-related diseases and preventive measures.\",\"icon\":\"fa-heart-pulse\"},{\"title\":\"Mental Well-being\",\"description\":\"Supporting emotional wellness through awareness initiatives and expert guidance.\",\"icon\":\"fa-brain\"},{\"title\":\"Community Engagement\",\"description\":\"Mobilizing volunteers and local partners to create sustainable health impact.\",\"icon\":\"fa-users\"}]'),
('footer_about', ''),
('google_maps_api_key', ''),
('home_theme', 'modern'),
('impact_goals_json', '{\"cities\":\"100+\",\"volunteers\":\"10,000+\",\"partners\":\"5,000+\",\"experts\":\"1,000+\",\"citizens\":\"1 Million\"}'),
('internship_program_json', '{\"title\":\"Learn While Creating Impact\",\"intro\":\"Students gain practical experience in various departments while driving wellness advocacy.\",\"areas\":[{\"name\":\"Community Outreach\",\"desc\":\"Connecting communities with wellness opportunities.\"},{\"name\":\"Partner Engagement\",\"desc\":\"Building relationships with local wellness partners.\"},{\"name\":\"Event Management\",\"desc\":\"Supporting health camps and awareness programs.\"},{\"name\":\"Digital Media\",\"desc\":\"Promoting wellness through social media campaigns.\"},{\"name\":\"Research & Data Collection\",\"desc\":\"Supporting impact assessment and program improvement.\"}],\"benefits\":[\"Internship Certificate\",\"Letter of Recommendation\",\"Practical Exposure\",\"Leadership Development\",\"Networking Opportunities\"]}'),
('letterhead_address', ''),
('letterhead_email', ''),
('letterhead_footer_text', 'Registered under Societies Registration Act | Donations Tax Exempted u/s 80G & 12A of Income Tax Act'),
('letterhead_header_color', '#0F8B8D'),
('letterhead_logo', ''),
('letterhead_org_name', ''),
('letterhead_phone', ''),
('letterhead_reg_no', ''),
('letterhead_signatory_designation', 'President / General Secretary'),
('letterhead_signatory_name', 'Authorized Signatory'),
('letterhead_signature_image', ''),
('letterhead_tagline', 'Empowering Communities • Transforming Lives • Sustainable Development'),
('letterhead_watermark_enabled', '1'),
('letterhead_website', ''),
('member_prefix', 'MEM-'),
('member_receipt_prefix', 'MRCPT-'),
('ngo_address', 'Shop No. 2, Vijaya Villa, Plot No. 410, Sector R3, \r\n  Vadghar, Panvel, Karanjade, Raigad, Maharashtra - 410206'),
('ngo_city', 'Jaunpur'),
('ngo_district', 'Jaunpur'),
('ngo_email', 'dcfoldage@gmail.com'),
('ngo_logo', 'uploads/settings/ngo_logo_1790485236.jpeg'),
('ngo_phone', '+91 7039024175'),
('ngo_signature', 'uploads/settings/ngo_signature_1788350423.png'),
('ngo_state', 'Uttar Pradesh'),
('ngo_website', ' dcfoldage.com'),
('objectives_content', 'The objectives of Jaysmrutti Foundation are focused on creating an inclusive, healthy, educated and self-reliant society by empowering individuals and strengthening communities.\r\n\r\n• To promote education, vocational training and skill development for youth and people from disadvantaged communities.\r\n\r\n• To create awareness about healthcare, hygiene, sanitation, nutrition, cleanliness and disease prevention.\r\n\r\n• To provide welfare, relief, educational assistance and support to poor, differently abled, elderly, orphaned, widowed and vulnerable individuals.\r\n\r\n• To promote environmental protection, tree plantation and conservation of natural resources through community participation.\r\n\r\n• To encourage social equality, mutual respect, harmony and cooperation among people of different religions, cultures and backgrounds.\r\n\r\n• To provide scholarships and educational assistance to meritorious students from economically weaker sections.\r\n\r\n• To promote, preserve and revive Indian art, crafts, traditional practices and cottage industries.\r\n\r\n• To spread knowledge and positive social awareness through publications, literature, seminars, audio-visual content and awareness programmes.\r\n\r\n• To establish and support community welfare and sustainable development programmes that create long-term social impact.\r\n\r\n• To encourage donations, grants, partnerships and community participation for the advancement of social welfare activities.\r\n\r\nThrough these objectives, Jaysmrutti Foundation aims to create meaningful and lasting social change by providing people with knowledge, opportunities, skills, support and dignity.'),
('objectives_image', 'uploads/content/objectives_1788163155_8922e793.jpeg'),
('objectives_title', 'Our Objectives'),
('partner_api_key', NULL),
('partner_benefits_json', NULL),
('partner_categories_json', '[{\"id\":\"fitness_centers\",\"name\":\"Fitness Centers & Gyms\",\"icon\":\"fa-dumbbell\",\"activities\":[\"Host fitness challenges\",\"Conduct awareness workshops\",\"Offer member wellness programs\",\"Participate in community campaigns\"]},{\"id\":\"restaurants_cafes\",\"name\":\"Restaurants & Cafes\",\"icon\":\"fa-utensils\",\"activities\":[\"Promote healthier food choices\",\"Participate in nutrition awareness drives\",\"Feature wellness-focused menu options\",\"Engage health-conscious customers\"]},{\"id\":\"salons_wellness\",\"name\":\"Salons & Wellness Centers\",\"icon\":\"fa-spa\",\"activities\":[\"Promote holistic wellness\",\"Support awareness campaigns\",\"Encourage healthy lifestyle conversations\"]},{\"id\":\"healthcare_professionals\",\"name\":\"Healthcare Professionals\",\"icon\":\"fa-user-doctor\",\"activities\":[\"Dietitians & Nutritionists counseling\",\"Fitness Coaching & Physiotherapy guidance\",\"Doctors & Psychologists consultations\"]},{\"id\":\"educational_institutions\",\"name\":\"Educational Institutions\",\"icon\":\"fa-graduation-cap\",\"activities\":[\"Student volunteer programs\",\"Wellness ambassador initiatives\",\"Health awareness events\"]},{\"id\":\"corporate_organizations\",\"name\":\"Corporate Organizations\",\"icon\":\"fa-building\",\"activities\":[\"CSR collaborations\",\"Employee wellness programs\",\"Community outreach initiatives\"]}]'),
('partner_portal_enabled', '1'),
('pdf_color_template', 'emerald'),
('privacy_policy_content', '<p>Welcome to our privacy policy page...</p>'),
('razorpay_donation_key_id', 'rzp_test_SJeMoTDfMwxNSS'),
('razorpay_donation_key_secret', 'j4Yn72hazBvdmtBvWAPmF25c'),
('razorpay_key_id', 'rzp_test_SJeMoTDfMwxNSS'),
('razorpay_key_secret', 'j4Yn72hazBvdmtBvWAPmF25c'),
('receipt_disclaimer', 'Donations are tax exempted u/s 80G.'),
('receipt_prefix', 'R'),
('refund_policy_content', '<p>Our policy on refunds for donations is as follows...</p>'),
('reg_no', '123455'),
('site_favicon', 'uploads/settings/site_favicon_1790485484.jpeg'),
('site_name', 'Dnyaneshwari charitable foundation '),
('smtp_host', 'smtp.hostinger.com'),
('smtp_pass', 'Kitandkin2022@'),
('smtp_port', '587'),
('smtp_secure', 'ssl'),
('smtp_user', 'Info@kithandkinn.org'),
('social_facebook', 'https://www.facebook.com/profile.php?id=61593792671229'),
('social_instagram', 'https://www.instagram.com/velnixsoft/'),
('social_youtube', 'https://youtube.com/@dcfoldage?si=DqilZ8yq-ti04LSN'),
('terms_conditions', '<h2>Terms & Conditions</h2><p>Your content here...</p>'),
('terms_conditions_content', '<p>Welcome to our website. If you continue to browse and use this website, you are agreeing to comply with and be bound by the following terms and conditions of use...</p>'),
('transparency_active_projects', '0'),
('transparency_total_donations', '0'),
('transparency_total_volunteers', '0'),
('upi_payee_name', NULL),
('upi_vpa', NULL),
('volunteer_prefix', 'VOL-'),
('whatsapp_api_key', ''),
('whatsapp_number', '917039024175');

-- --------------------------------------------------------

--
-- Table structure for table `skill_courses`
--

CREATE TABLE `skill_courses` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `category` enum('vocational','computer_it','soft_skills','competitive_exams','entrepreneurship','healthcare_aid','other') NOT NULL DEFAULT 'vocational',
  `category_custom` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `curriculum` text DEFAULT NULL,
  `duration` varchar(100) DEFAULT '3 Months',
  `eligibility` varchar(150) DEFAULT '10th / 12th Pass or Equivalent',
  `mode` enum('Offline','Online','Hybrid') NOT NULL DEFAULT 'Offline',
  `fee_type` enum('100% Free','Subsidized Aid','Scholarship Based') NOT NULL DEFAULT '100% Free',
  `instructor` varchar(150) DEFAULT 'Senior NGO Faculty & Field Experts',
  `location` varchar(255) DEFAULT 'Field Training Center & Online',
  `image_path` varchar(255) DEFAULT NULL,
  `batch_start_date` date DEFAULT NULL,
  `max_seats` int(11) NOT NULL DEFAULT 30,
  `status` enum('active','inactive','upcoming','completed') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `skill_courses`
--

INSERT INTO `skill_courses` (`id`, `title`, `category`, `category_custom`, `description`, `curriculum`, `duration`, `eligibility`, `mode`, `fee_type`, `instructor`, `location`, `image_path`, `batch_start_date`, `max_seats`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Digital Literacy & Office Productivity', 'computer_it', NULL, 'Master fundamental computer operations, Microsoft Office (Word, Excel, PPT), Internet browsing, email communication, and online government portal navigation.', 'Module 1: Basic Computer Hardware & OS\nModule 2: MS Word & Document Design\nModule 3: MS Excel Data Management\nModule 4: Internet & Cyber Hygiene', '6 Weeks (45 Hours)', '8th / 10th Pass', 'Hybrid', '100% Free', 'Rajesh Verma (IT Trainer)', 'District Center & Online', NULL, '2026-09-26', 35, 'active', '2026-09-12 09:42:31', '2026-09-12 09:42:31'),
(2, 'Community Healthcare Assistant (First Aid & Nursing Basics)', 'healthcare_aid', NULL, 'Comprehensive field training in vital signs monitoring, primary first-aid, community hygiene counseling, elderly care, and basic patient support.', 'Module 1: Vital Signs & BP Monitoring\nModule 2: Emergency First Aid & Wound Dressing\nModule 3: Patient Care Ethics & Sanitization\nModule 4: Field Internship at NGO Health Camps', '3 Months', '10th / 12th Pass', 'Offline', '100% Free', 'Dr. S. K. Gupta & Nursing Staff', 'NGO Medical Training Center', NULL, '2026-10-03', 25, 'active', '2026-09-12 09:42:31', '2026-09-12 09:42:31'),
(3, 'Spoken English & Interview Communication Mastery', 'soft_skills', NULL, 'Build conversational confidence, professional email etiquette, resume writing, public speaking, and crack job interviews with mock rounds.', 'Module 1: Everyday English Vocabulary & Grammar\nModule 2: Professional Workplace Dialogue\nModule 3: Resume & Cover Letter Formulation\nModule 4: Mock Interview Rounds & Feedback', '2 Months (60 Hours)', '12th Pass / Undergraduate', 'Online', '100% Free', 'Anjali Sharma (Communication Specialist)', 'Live Online Classes', NULL, '2026-09-22', 50, 'active', '2026-09-12 09:42:31', '2026-09-12 09:42:31'),
(4, 'Rural Micro-Entrepreneurship & SHG Handicraft Management', 'entrepreneurship', NULL, 'Learn practical business startup fundamentals, product pricing, micro-loans, digital UPI payments, government subsidies (PMEGP/MUDRA), and local market linkage.', 'Module 1: Business Idea Validation & Feasibility\nModule 2: Bookkeeping & Cashflow Management\nModule 3: Government Schemes & Bank Loans\nModule 4: Marketing via WhatsApp & Local Melas', '4 Weeks', 'Open to All / Women & Youth', 'Offline', '100% Free', 'Vikas Mishra (Rural Enterprise Mentor)', 'Block Skill Hub', NULL, '2026-10-10', 30, 'active', '2026-09-12 09:42:31', '2026-09-12 09:42:31');

-- --------------------------------------------------------

--
-- Table structure for table `skill_course_enrollments`
--

CREATE TABLE `skill_course_enrollments` (
  `id` int(11) NOT NULL,
  `application_no` varchar(50) NOT NULL,
  `course_id` int(11) NOT NULL,
  `applicant_name` varchar(150) NOT NULL,
  `contact` varchar(20) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `gender` enum('Male','Female','Other') NOT NULL DEFAULT 'Male',
  `dob` date DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `qualification` varchar(150) NOT NULL,
  `state` varchar(100) NOT NULL,
  `district` varchar(100) NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `address` text NOT NULL,
  `motivation` text DEFAULT NULL,
  `status` enum('pending','contacted','enrolled','completed','cancelled') NOT NULL DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `applied_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sliders`
--

CREATE TABLE `sliders` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `image_path` varchar(255) NOT NULL,
  `priority` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sliders`
--

INSERT INTO `sliders` (`id`, `title`, `subtitle`, `image_path`, `priority`, `is_active`) VALUES
(20, 'Test', 'test', 'uploads/slider/1790856304_WhatsApp Image 2026-09-30 at 1.25.56 PM.jpeg', 0, 1),
(21, 'second', 'Test', 'uploads/slider/1790856327_WhatsApp Image 2026-09-30 at 1.25.48 PM.jpeg', 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `sponsors`
--

CREATE TABLE `sponsors` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `logo_path` varchar(255) NOT NULL,
  `website_url` varchar(255) DEFAULT NULL,
  `priority` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_letters`
--

CREATE TABLE `staff_letters` (
  `id` int(11) NOT NULL,
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
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staff_letters`
--

INSERT INTO `staff_letters` (`id`, `letter_no`, `type`, `name`, `contact`, `email`, `designation`, `department`, `issued_date`, `joining_date`, `salary_or_stipend`, `subject`, `letter_content`, `pdf_path`, `file_size`, `signatory_name`, `signatory_designation`, `status`, `issued_by`, `created_at`, `updated_at`) VALUES
(1, 'LET-OFF-2026-001', 'offer', 'Amitabh Sengupta', '+91 9876501234', 'amitabh.sengupta@example.com', 'State Program Coordinator', 'Operations & Field Strategy', '2026-02-01', '2026-02-15', '₹35,000 / month', 'Offer of Employment: State Program Coordinator', '<p>Dear <strong>Amitabh Sengupta</strong>,</p><p>We are pleased to offer you the position of <strong>State Program Coordinator</strong> at Jaysmrutti Foundation. Your leadership will guide our multi-district healthcare and community development initiatives.</p><p><strong>Reporting Date:</strong> 15 February 2026<br><strong>Location:</strong> Lucknow State Headquarters</p>', 'uploads/documents/staff_letters/Staff_Letter_LET-OFF-2026-001.pdf', '240 KB', 'National General Secretary', 'Executive Committee', 'issued', NULL, '2026-09-12 10:02:51', '2026-09-12 10:14:25'),
(2, 'LET-APP-2026-002', 'appointment', 'Sunita Kushwaha', '+91 9786543210', 'sunita.kushwaha@example.com', 'District Operations Lead', 'District Healthcare Wing', '2026-02-10', '2026-02-16', '₹28,000 / month', 'Official Appointment Letter: District Operations Lead', '<p>Dear <strong>Sunita Kushwaha</strong>,</p><p>Consequent to your interview and acceptance of our offer, we are pleased to appoint you as <strong>District Operations Lead</strong> with immediate effect.</p>', 'uploads/documents/staff_letters/Staff_Letter_LET-APP-2026-002.pdf', '240 KB', 'National President', 'Jaysmrutti Foundation', 'accepted', NULL, '2026-09-12 10:02:51', '2026-09-12 10:14:25'),
(3, 'LET-VOL-2026-003', 'volunteer_joining', 'Kavita Mishra', '+91 9123456780', 'kavita.volunteer@example.com', 'Youth & Field Mobilization Volunteer', 'Community Volunteers Wing', '2026-03-01', '2026-03-05', 'Honorary / Voluntary', 'Volunteer Joining & Welcome Certificate Letter', '<p>Dear <strong>Kavita Mishra</strong>,</p><p>Welcome to Jaysmrutti Foundation. We officially acknowledge your joining as a <strong>Youth & Field Mobilization Volunteer</strong>. Thank you for your dedication towards societal welfare.</p>', 'uploads/documents/staff_letters/Staff_Letter_LET-VOL-2026-003.pdf', '240 KB', 'Volunteer Coordinator', 'Community Wing', 'issued', NULL, '2026-09-12 10:02:51', '2026-09-12 10:14:25');

-- --------------------------------------------------------

--
-- Table structure for table `templates`
--

CREATE TABLE `templates` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `template_name` varchar(150) NOT NULL,
  `template_type` enum('id_card','receipt','membership_certificate','achievement_certificate','appointment_letter','visitor_certificate','volunteer_certificate','student_certificate','sanstha_authorization') NOT NULL,
  `canvas_width` int(11) NOT NULL DEFAULT 800,
  `canvas_height` int(11) NOT NULL DEFAULT 600,
  `background_image` varchar(255) DEFAULT NULL,
  `json_data` longtext NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `templates`
--

INSERT INTO `templates` (`id`, `user_id`, `template_name`, `template_type`, `canvas_width`, `canvas_height`, `background_image`, `json_data`, `status`, `created_at`, `updated_at`) VALUES
(1, 2, 'visiter', 'achievement_certificate', 1123, 794, 'uploads/templates/template_bg_1776836448_42aee00d77.jpg', '{\"version\":\"5.3.0\",\"objects\":[{\"type\":\"image\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":35.35,\"top\":417.55,\"width\":260,\"height\":160,\"fill\":\"rgb(0,0,0)\",\"stroke\":null,\"strokeWidth\":0,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":0.45,\"scaleY\":0.56,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"cropX\":0,\"cropY\":0,\"placeholderType\":\"photo\",\"src\":\"data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22260%22%20height%3D%22160%22%3E%3Crect%20width%3D%22100%25%22%20height%3D%22100%25%22%20fill%3D%22%23f8fafc%22%20stroke%3D%22%2394a3b8%22%20stroke-dasharray%3D%228%206%22%2F%3E%3Ctext%20x%3D%2250%25%22%20y%3D%2250%25%22%20dominant-baseline%3D%22middle%22%20text-anchor%3D%22middle%22%20font-family%3D%22Arial%22%20font-size%3D%2222%22%20fill%3D%22%23475569%22%3EPHOTO%3C%2Ftext%3E%3C%2Fsvg%3E\",\"crossOrigin\":null,\"filters\":[]},{\"type\":\"textbox\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":644.77,\"top\":418.67,\"width\":348.25,\"height\":31.64,\"fill\":\"#111827\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":0.93,\"scaleY\":0.8,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Arial\",\"fontWeight\":\"normal\",\"fontSize\":28,\"text\":\"{{member_no}}\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\",\"minWidth\":20,\"splitByGrapheme\":false},{\"type\":\"group\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":946.28,\"top\":428.77,\"width\":145,\"height\":145,\"fill\":\"rgb(0,0,0)\",\"stroke\":null,\"strokeWidth\":0,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":0.91,\"scaleY\":0.76,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"placeholderType\":\"qr\",\"placeholderLabel\":\"QR\",\"objects\":[{\"type\":\"rect\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":-73.5,\"top\":-73.5,\"width\":145,\"height\":145,\"fill\":\"#f8fafc\",\"stroke\":\"#2563eb\",\"strokeWidth\":2,\"strokeDashArray\":[8,6],\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"rx\":4,\"ry\":4},{\"type\":\"text\",\"version\":\"5.3.0\",\"originX\":\"center\",\"originY\":\"center\",\"left\":-1,\"top\":-1,\"width\":33.05,\"height\":24.86,\"fill\":\"#1d4ed8\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Arial\",\"fontWeight\":\"bold\",\"fontSize\":22,\"text\":\"QR\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\"}]},{\"type\":\"textbox\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":440.89,\"top\":327,\"width\":260,\"height\":31.64,\"fill\":\"#111827\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Arial\",\"fontWeight\":\"normal\",\"fontSize\":28,\"text\":\"{{full_name}}\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\",\"minWidth\":20,\"splitByGrapheme\":false}],\"background\":\"#ffffff\"}', 1, '2026-04-21 08:56:50', '2026-04-23 05:49:20'),
(4, 2, 'shewow', '', 1123, 794, 'uploads/templates/template_bg_1776834057_a22b0c183a.jpg', '{\"version\":\"5.3.0\",\"objects\":[{\"type\":\"textbox\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":461.89,\"top\":327,\"width\":260,\"height\":31.64,\"fill\":\"#111827\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Arial\",\"fontWeight\":\"normal\",\"fontSize\":28,\"text\":\"{{full_name}}\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\",\"minWidth\":20,\"splitByGrapheme\":false},{\"type\":\"textbox\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":325.93,\"top\":371,\"width\":325.64,\"height\":29.38,\"fill\":\"#d21e1e\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Times New Roman\",\"fontWeight\":\"normal\",\"fontSize\":26,\"text\":\"{{event_title}}\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\",\"minWidth\":20,\"splitByGrapheme\":false},{\"type\":\"textbox\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":718.11,\"top\":375.99,\"width\":394.66,\"height\":31.64,\"fill\":\"#111827\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":0.94,\"scaleY\":0.94,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Arial\",\"fontWeight\":\"normal\",\"fontSize\":28,\"text\":\"{{occasion_name}}\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\",\"minWidth\":20,\"splitByGrapheme\":false},{\"type\":\"group\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":33.8,\"top\":396.19,\"width\":170,\"height\":210,\"fill\":\"rgb(0,0,0)\",\"stroke\":null,\"strokeWidth\":0,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":0.69,\"scaleY\":0.69,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"placeholderType\":\"photo\",\"placeholderLabel\":\"PHOTO\",\"objects\":[{\"type\":\"rect\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":-86,\"top\":-106,\"width\":170,\"height\":210,\"fill\":\"#f8fafc\",\"stroke\":\"#2563eb\",\"strokeWidth\":2,\"strokeDashArray\":[8,6],\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"rx\":4,\"ry\":4},{\"type\":\"text\",\"version\":\"5.3.0\",\"originX\":\"center\",\"originY\":\"center\",\"left\":-1,\"top\":-1,\"width\":85.56,\"height\":27.12,\"fill\":\"#1d4ed8\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Arial\",\"fontWeight\":\"bold\",\"fontSize\":24,\"text\":\"PHOTO\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\"}]},{\"type\":\"group\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":946.74,\"top\":417,\"width\":145,\"height\":145,\"fill\":\"rgb(0,0,0)\",\"stroke\":null,\"strokeWidth\":0,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"placeholderType\":\"qr\",\"placeholderLabel\":\"QR\",\"objects\":[{\"type\":\"rect\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":-73.5,\"top\":-73.5,\"width\":145,\"height\":145,\"fill\":\"#f8fafc\",\"stroke\":\"#2563eb\",\"strokeWidth\":2,\"strokeDashArray\":[8,6],\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"rx\":4,\"ry\":4},{\"type\":\"text\",\"version\":\"5.3.0\",\"originX\":\"center\",\"originY\":\"center\",\"left\":-1,\"top\":-1,\"width\":33.05,\"height\":24.86,\"fill\":\"#1d4ed8\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Arial\",\"fontWeight\":\"bold\",\"fontSize\":22,\"text\":\"QR\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\"}]},{\"type\":\"textbox\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":361.92,\"top\":413,\"width\":260,\"height\":31.64,\"fill\":\"#111827\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Arial\",\"fontWeight\":\"normal\",\"fontSize\":28,\"text\":\"{{date}}\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\",\"minWidth\":20,\"splitByGrapheme\":false},{\"type\":\"textbox\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":638.03,\"top\":428.27,\"width\":470.47,\"height\":31.64,\"fill\":\"#111827\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":0.6,\"scaleY\":0.53,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Arial\",\"fontWeight\":\"normal\",\"fontSize\":28,\"text\":\"{{achievement_position}}\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\",\"minWidth\":20,\"splitByGrapheme\":false}],\"background\":\"#ffffff\"}', 1, '2026-04-22 05:00:57', '2026-04-22 05:33:58'),
(5, 2, 'New Template', '', 1123, 794, '', '{\"version\":\"5.3.0\",\"objects\":[{\"type\":\"textbox\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":292.99,\"top\":279.83,\"width\":264.32,\"height\":31.64,\"fill\":\"#111827\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Arial\",\"fontWeight\":\"normal\",\"fontSize\":28,\"text\":\"{{email}}\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\",\"minWidth\":20,\"splitByGrapheme\":false},{\"type\":\"group\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":32,\"top\":230.86,\"width\":170,\"height\":210,\"fill\":\"rgb(0,0,0)\",\"stroke\":null,\"strokeWidth\":0,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"placeholderType\":\"photo\",\"placeholderLabel\":\"PHOTO\",\"objects\":[{\"type\":\"rect\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":-86,\"top\":-106,\"width\":170,\"height\":210,\"fill\":\"#f8fafc\",\"stroke\":\"#2563eb\",\"strokeWidth\":2,\"strokeDashArray\":[8,6],\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"rx\":4,\"ry\":4},{\"type\":\"text\",\"version\":\"5.3.0\",\"originX\":\"center\",\"originY\":\"center\",\"left\":-1,\"top\":-1,\"width\":85.56,\"height\":27.12,\"fill\":\"#1d4ed8\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Arial\",\"fontWeight\":\"bold\",\"fontSize\":24,\"text\":\"PHOTO\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\"}]},{\"type\":\"group\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":9,\"top\":29.03,\"width\":180,\"height\":90,\"fill\":\"rgb(0,0,0)\",\"stroke\":null,\"strokeWidth\":0,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"placeholderType\":\"logo\",\"placeholderLabel\":\"LOGO\",\"objects\":[{\"type\":\"rect\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":-91,\"top\":-46,\"width\":180,\"height\":90,\"fill\":\"#f8fafc\",\"stroke\":\"#2563eb\",\"strokeWidth\":2,\"strokeDashArray\":[8,6],\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"rx\":4,\"ry\":4},{\"type\":\"text\",\"version\":\"5.3.0\",\"originX\":\"center\",\"originY\":\"center\",\"left\":-1,\"top\":-1,\"width\":70.98,\"height\":27.12,\"fill\":\"#1d4ed8\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Arial\",\"fontWeight\":\"bold\",\"fontSize\":24,\"text\":\"LOGO\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\"}]},{\"type\":\"textbox\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":259.99,\"top\":183.91,\"width\":260,\"height\":31.64,\"fill\":\"#111827\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Arial\",\"fontWeight\":\"normal\",\"fontSize\":28,\"text\":\"{{full_name}}\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\",\"minWidth\":20,\"splitByGrapheme\":false}],\"background\":\"#ffffff\"}', 1, '2026-04-23 05:51:19', '2026-04-23 05:51:19'),
(6, 2, 'visiteraaar', '', 1123, 794, '', '{\"version\":\"5.3.0\",\"objects\":[{\"type\":\"textbox\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":223,\"top\":393.74,\"width\":348.25,\"height\":31.64,\"fill\":\"#111827\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Arial\",\"fontWeight\":\"normal\",\"fontSize\":28,\"text\":\"{{member_no}}\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\",\"minWidth\":20,\"splitByGrapheme\":false},{\"type\":\"group\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":127,\"top\":166.91,\"width\":170,\"height\":210,\"fill\":\"rgb(0,0,0)\",\"stroke\":null,\"strokeWidth\":0,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"placeholderType\":\"photo\",\"placeholderLabel\":\"PHOTO\",\"objects\":[{\"type\":\"rect\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":-86,\"top\":-106,\"width\":170,\"height\":210,\"fill\":\"#f8fafc\",\"stroke\":\"#2563eb\",\"strokeWidth\":2,\"strokeDashArray\":[8,6],\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"rx\":4,\"ry\":4},{\"type\":\"text\",\"version\":\"5.3.0\",\"originX\":\"center\",\"originY\":\"center\",\"left\":-1,\"top\":-1,\"width\":85.56,\"height\":27.12,\"fill\":\"#1d4ed8\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Arial\",\"fontWeight\":\"bold\",\"fontSize\":24,\"text\":\"PHOTO\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\"}]},{\"type\":\"group\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":11,\"top\":-12.94,\"width\":180,\"height\":90,\"fill\":\"rgb(0,0,0)\",\"stroke\":null,\"strokeWidth\":0,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"placeholderType\":\"logo\",\"placeholderLabel\":\"LOGO\",\"objects\":[{\"type\":\"rect\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":-91,\"top\":-46,\"width\":180,\"height\":90,\"fill\":\"#f8fafc\",\"stroke\":\"#2563eb\",\"strokeWidth\":2,\"strokeDashArray\":[8,6],\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"rx\":4,\"ry\":4},{\"type\":\"text\",\"version\":\"5.3.0\",\"originX\":\"center\",\"originY\":\"center\",\"left\":-1,\"top\":-1,\"width\":70.98,\"height\":27.12,\"fill\":\"#1d4ed8\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Arial\",\"fontWeight\":\"bold\",\"fontSize\":24,\"text\":\"LOGO\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\"}]},{\"type\":\"group\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":319.99,\"top\":26.03,\"width\":145,\"height\":145,\"fill\":\"rgb(0,0,0)\",\"stroke\":null,\"strokeWidth\":0,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"placeholderType\":\"qr\",\"placeholderLabel\":\"QR\",\"objects\":[{\"type\":\"rect\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":-73.5,\"top\":-73.5,\"width\":145,\"height\":145,\"fill\":\"#f8fafc\",\"stroke\":\"#2563eb\",\"strokeWidth\":2,\"strokeDashArray\":[8,6],\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"rx\":4,\"ry\":4},{\"type\":\"text\",\"version\":\"5.3.0\",\"originX\":\"center\",\"originY\":\"center\",\"left\":-1,\"top\":-1,\"width\":33.05,\"height\":24.86,\"fill\":\"#1d4ed8\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Arial\",\"fontWeight\":\"bold\",\"fontSize\":22,\"text\":\"QR\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\"}]},{\"type\":\"rect\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":330.99,\"top\":259.85,\"width\":180,\"height\":90,\"fill\":\"#ffffff\",\"stroke\":\"#111827\",\"strokeWidth\":2,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"rx\":0,\"ry\":0}],\"background\":\"#ffffff\"}', 1, '2026-04-23 06:51:58', '2026-04-23 06:51:58'),
(12, 2, 'Memberships ID Card', 'id_card', 856, 540, 'uploads/templates/template_bg_1788347670_70b3b34be7.png', '{\"version\":\"5.3.0\",\"objects\":[{\"type\":\"group\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":30.2,\"top\":193.2,\"width\":170,\"height\":210,\"fill\":\"rgb(0,0,0)\",\"stroke\":null,\"strokeWidth\":0,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1.07,\"scaleY\":1.1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"placeholderType\":\"photo\",\"placeholderLabel\":\"PHOTO\",\"objects\":[{\"type\":\"rect\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":-86,\"top\":-106,\"width\":170,\"height\":210,\"fill\":\"#f8fafc\",\"stroke\":\"#2563eb\",\"strokeWidth\":2,\"strokeDashArray\":[8,6],\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"rx\":4,\"ry\":4},{\"type\":\"text\",\"version\":\"5.3.0\",\"originX\":\"center\",\"originY\":\"center\",\"left\":-1,\"top\":-1,\"width\":84.9,\"height\":27.12,\"fill\":\"#1d4ed8\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Arial\",\"fontWeight\":\"bold\",\"fontSize\":24,\"text\":\"PHOTO\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\"}]},{\"type\":\"textbox\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":420,\"top\":223,\"width\":260,\"height\":31.64,\"fill\":\"#111827\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Arial\",\"fontWeight\":\"normal\",\"fontSize\":28,\"text\":\"{{full_name}}\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\",\"minWidth\":20,\"splitByGrapheme\":false},{\"type\":\"group\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":688,\"top\":15.2,\"width\":145,\"height\":145,\"fill\":\"rgb(0,0,0)\",\"stroke\":null,\"strokeWidth\":0,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1.13,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"placeholderType\":\"qr\",\"placeholderLabel\":\"QR\",\"objects\":[{\"type\":\"rect\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":-73.5,\"top\":-73.5,\"width\":145,\"height\":145,\"fill\":\"#f8fafc\",\"stroke\":\"#2563eb\",\"strokeWidth\":2,\"strokeDashArray\":[8,6],\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"rx\":4,\"ry\":4},{\"type\":\"text\",\"version\":\"5.3.0\",\"originX\":\"center\",\"originY\":\"center\",\"left\":-1,\"top\":-1,\"width\":33,\"height\":24.86,\"fill\":\"#1d4ed8\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Arial\",\"fontWeight\":\"bold\",\"fontSize\":22,\"text\":\"QR\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\"}]},{\"type\":\"group\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":642.3,\"top\":334.9,\"width\":220,\"height\":90,\"fill\":\"rgb(0,0,0)\",\"stroke\":null,\"strokeWidth\":0,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":0.81,\"scaleY\":0.81,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"placeholderType\":\"signature\",\"placeholderLabel\":\"SIGNATURE\",\"objects\":[{\"type\":\"rect\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":-111,\"top\":-46,\"width\":220,\"height\":90,\"fill\":\"#f8fafc\",\"stroke\":\"#2563eb\",\"strokeWidth\":2,\"strokeDashArray\":[8,6],\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"rx\":4,\"ry\":4},{\"type\":\"text\",\"version\":\"5.3.0\",\"originX\":\"center\",\"originY\":\"center\",\"left\":-1,\"top\":-1,\"width\":139.56,\"height\":27.12,\"fill\":\"#1d4ed8\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Arial\",\"fontWeight\":\"bold\",\"fontSize\":24,\"text\":\"SIGNATURE\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\"}]},{\"type\":\"textbox\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":422,\"top\":257,\"width\":345.63,\"height\":31.64,\"fill\":\"#111827\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Arial\",\"fontWeight\":\"normal\",\"fontSize\":28,\"text\":\"{{member_no}}\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\",\"minWidth\":20,\"splitByGrapheme\":false},{\"type\":\"textbox\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":421,\"top\":294,\"width\":345.63,\"height\":31.64,\"fill\":\"#111827\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Arial\",\"fontWeight\":\"normal\",\"fontSize\":28,\"text\":\"{{dob}}\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\",\"minWidth\":20,\"splitByGrapheme\":false},{\"type\":\"textbox\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":420,\"top\":331,\"width\":345.63,\"height\":31.64,\"fill\":\"#111827\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Arial\",\"fontWeight\":\"normal\",\"fontSize\":28,\"text\":\"{{phone}}\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\",\"minWidth\":20,\"splitByGrapheme\":false},{\"type\":\"textbox\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":421,\"top\":368,\"width\":345.63,\"height\":31.64,\"fill\":\"#111827\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Arial\",\"fontWeight\":\"normal\",\"fontSize\":28,\"text\":\"{{blood_group}}\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\",\"minWidth\":20,\"splitByGrapheme\":false},{\"type\":\"textbox\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":419,\"top\":404,\"width\":355.03,\"height\":31.64,\"fill\":\"#111827\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Arial\",\"fontWeight\":\"normal\",\"fontSize\":28,\"text\":\"{{valid_until}}\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\",\"minWidth\":20,\"splitByGrapheme\":false}],\"background\":\"#ffffff\"}', 1, '2026-09-02 11:10:39', '2026-09-02 11:35:20');
INSERT INTO `templates` (`id`, `user_id`, `template_name`, `template_type`, `canvas_width`, `canvas_height`, `background_image`, `json_data`, `status`, `created_at`, `updated_at`) VALUES
(13, 2, 'Volunteers ID Card', 'id_card', 856, 540, 'uploads/templates/template_bg_1788348016_6d6d7ae373.png', '{\"version\":\"5.3.0\",\"objects\":[{\"type\":\"group\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":33.2,\"top\":198.6,\"width\":170,\"height\":210,\"fill\":\"rgb(0,0,0)\",\"stroke\":null,\"strokeWidth\":0,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1.1,\"scaleY\":1.07,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"placeholderType\":\"photo\",\"placeholderLabel\":\"PHOTO\",\"objects\":[{\"type\":\"rect\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":-86,\"top\":-106,\"width\":170,\"height\":210,\"fill\":\"#f8fafc\",\"stroke\":\"#2563eb\",\"strokeWidth\":2,\"strokeDashArray\":[8,6],\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"rx\":4,\"ry\":4},{\"type\":\"text\",\"version\":\"5.3.0\",\"originX\":\"center\",\"originY\":\"center\",\"left\":-1,\"top\":-1,\"width\":84.9,\"height\":27.12,\"fill\":\"#1d4ed8\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Arial\",\"fontWeight\":\"bold\",\"fontSize\":24,\"text\":\"PHOTO\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\"}]},{\"type\":\"group\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":692,\"top\":21,\"width\":145,\"height\":145,\"fill\":\"rgb(0,0,0)\",\"stroke\":null,\"strokeWidth\":0,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1.09,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"placeholderType\":\"qr\",\"placeholderLabel\":\"QR\",\"objects\":[{\"type\":\"rect\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":-73.5,\"top\":-73.5,\"width\":145,\"height\":145,\"fill\":\"#f8fafc\",\"stroke\":\"#2563eb\",\"strokeWidth\":2,\"strokeDashArray\":[8,6],\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"rx\":4,\"ry\":4},{\"type\":\"text\",\"version\":\"5.3.0\",\"originX\":\"center\",\"originY\":\"center\",\"left\":-1,\"top\":-1,\"width\":33,\"height\":24.86,\"fill\":\"#1d4ed8\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Arial\",\"fontWeight\":\"bold\",\"fontSize\":22,\"text\":\"QR\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\"}]},{\"type\":\"textbox\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":426,\"top\":232,\"width\":260,\"height\":31.64,\"fill\":\"#111827\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Arial\",\"fontWeight\":\"normal\",\"fontSize\":28,\"text\":\"{{full_name}}\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\",\"minWidth\":20,\"splitByGrapheme\":false},{\"type\":\"group\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":652.88,\"top\":332.72,\"width\":220,\"height\":90,\"fill\":\"rgb(0,0,0)\",\"stroke\":null,\"strokeWidth\":0,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":0.81,\"scaleY\":0.81,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"placeholderType\":\"signature\",\"placeholderLabel\":\"SIGNATURE\",\"objects\":[{\"type\":\"rect\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":-111,\"top\":-46,\"width\":220,\"height\":90,\"fill\":\"#f8fafc\",\"stroke\":\"#2563eb\",\"strokeWidth\":2,\"strokeDashArray\":[8,6],\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"rx\":4,\"ry\":4},{\"type\":\"text\",\"version\":\"5.3.0\",\"originX\":\"center\",\"originY\":\"center\",\"left\":-1,\"top\":-1,\"width\":139.56,\"height\":27.12,\"fill\":\"#1d4ed8\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Arial\",\"fontWeight\":\"bold\",\"fontSize\":24,\"text\":\"SIGNATURE\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\"}]},{\"type\":\"textbox\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":425,\"top\":268,\"width\":345.63,\"height\":31.64,\"fill\":\"#111827\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Arial\",\"fontWeight\":\"normal\",\"fontSize\":28,\"text\":\"{{member_no}}\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\",\"minWidth\":20,\"splitByGrapheme\":false},{\"type\":\"textbox\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":424,\"top\":307,\"width\":274.08,\"height\":31.64,\"fill\":\"#111827\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Arial\",\"fontWeight\":\"normal\",\"fontSize\":28,\"text\":\"{{phone}}\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\",\"minWidth\":20,\"splitByGrapheme\":false},{\"type\":\"textbox\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":423,\"top\":343,\"width\":308.37,\"height\":31.64,\"fill\":\"#111827\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Arial\",\"fontWeight\":\"normal\",\"fontSize\":28,\"text\":\"{{blood_group}}\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\",\"minWidth\":20,\"splitByGrapheme\":false},{\"type\":\"textbox\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":424,\"top\":380,\"width\":355.03,\"height\":31.64,\"fill\":\"#111827\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Arial\",\"fontWeight\":\"normal\",\"fontSize\":28,\"text\":\"{{valid_until}}\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\",\"minWidth\":20,\"splitByGrapheme\":false}],\"background\":\"#ffffff\"}', 1, '2026-09-02 11:20:16', '2026-09-05 02:17:27'),
(16, 2, 'Membership Certificate', 'membership_certificate', 1123, 794, 'uploads/templates/template_bg_1788350322_8d6b3856b8.png', '{\"version\":\"5.3.0\",\"objects\":[{\"type\":\"textbox\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":468.79,\"top\":414.83,\"width\":260,\"height\":39.55,\"fill\":\"#111827\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Arial\",\"fontWeight\":\"normal\",\"fontSize\":35,\"text\":\"{{full_name}}\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\",\"minWidth\":20,\"splitByGrapheme\":false},{\"type\":\"group\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":850.48,\"top\":540.4,\"width\":220,\"height\":90,\"fill\":\"rgb(0,0,0)\",\"stroke\":null,\"strokeWidth\":0,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":0.86,\"scaleY\":0.86,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"placeholderType\":\"signature\",\"placeholderLabel\":\"SIGNATURE\",\"objects\":[{\"type\":\"rect\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":-111,\"top\":-46,\"width\":220,\"height\":90,\"fill\":\"#f8fafc\",\"stroke\":\"#2563eb\",\"strokeWidth\":2,\"strokeDashArray\":[8,6],\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"rx\":4,\"ry\":4},{\"type\":\"text\",\"version\":\"5.3.0\",\"originX\":\"center\",\"originY\":\"center\",\"left\":-1,\"top\":-1,\"width\":139.56,\"height\":27.12,\"fill\":\"#1d4ed8\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Arial\",\"fontWeight\":\"bold\",\"fontSize\":24,\"text\":\"SIGNATURE\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\"}]}],\"background\":\"#ffffff\"}', 1, '2026-09-02 11:41:51', '2026-09-02 11:58:42'),
(18, 2, 'Volunteer Certificate', 'volunteer_certificate', 1123, 794, 'uploads/templates/template_bg_1788350058_92767ea2bd.png', '{\"version\":\"5.3.0\",\"objects\":[{\"type\":\"textbox\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":462.8,\"top\":415.83,\"width\":260,\"height\":39.55,\"fill\":\"#111827\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Arial\",\"fontWeight\":\"normal\",\"fontSize\":35,\"text\":\"{{full_name}}\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\",\"minWidth\":20,\"splitByGrapheme\":false},{\"type\":\"group\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":848.23,\"top\":540.84,\"width\":220,\"height\":90,\"fill\":\"rgb(0,0,0)\",\"stroke\":null,\"strokeWidth\":0,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":0.86,\"scaleY\":0.86,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"placeholderType\":\"signature\",\"placeholderLabel\":\"SIGNATURE\",\"objects\":[{\"type\":\"rect\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":-111,\"top\":-46,\"width\":220,\"height\":90,\"fill\":\"#f8fafc\",\"stroke\":\"#2563eb\",\"strokeWidth\":2,\"strokeDashArray\":[8,6],\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"rx\":4,\"ry\":4},{\"type\":\"text\",\"version\":\"5.3.0\",\"originX\":\"center\",\"originY\":\"center\",\"left\":-1,\"top\":-1,\"width\":139.56,\"height\":27.12,\"fill\":\"#1d4ed8\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Arial\",\"fontWeight\":\"bold\",\"fontSize\":24,\"text\":\"SIGNATURE\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\"}]}],\"background\":\"#ffffff\"}', 1, '2026-09-02 11:53:29', '2026-09-02 11:54:18');

-- --------------------------------------------------------

--
-- Table structure for table `testimonials`
--

CREATE TABLE `testimonials` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `role` varchar(150) NOT NULL,
  `content` text NOT NULL,
  `stars` enum('★★★★★','★★★★☆','★★★☆☆','★★☆☆☆','★☆☆☆☆') DEFAULT '★★★★★',
  `initial` char(2) NOT NULL,
  `avatar_path` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `priority_order` int(11) DEFAULT 0,
  `featured` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `testimonials`
--

INSERT INTO `testimonials` (`id`, `name`, `role`, `content`, `stars`, `initial`, `avatar_path`, `status`, `priority_order`, `featured`, `created_at`, `updated_at`) VALUES
(9, 'Community Member', 'Community Member', 'Jaysmrutti Foundation के सामाजिक और जागरूकता कार्यक्रमों से समुदाय के लोगों को काफी प्रेरणा मिलती है। संस्था शिक्षा, स्वास्थ्य और पर्यावरण जैसे महत्वपूर्ण विषयों पर लोगों को जोड़कर सकारात्मक बदलाव लाने का प्रयास कर रही है।”', '★★★★★', 'R', NULL, 'Active', 0, 0, '2026-08-31 09:34:08', '2026-08-31 09:34:08'),
(10, 'Volunteer', 'Foundation Volunteer', 'Jaysmrutti Foundation के साथ जुड़कर समाज के लिए काम करने का एक meaningful अनुभव मिला। Green India Campaign और अन्य awareness programmes में लोगों की participation देखकर लगता है कि छोटे-छोटे प्रयास भी बड़े बदलाव की शुरुआत बन सकते हैं।”', '★★★★★', 'V', NULL, 'Active', 1, 0, '2026-08-31 09:35:18', '2026-08-31 09:35:18'),
(11, 'Programme Participant', 'Programme Participant', '“Jaysmrutti Foundation के programmes लोगों को केवल सहायता देने तक सीमित नहीं हैं, बल्कि उन्हें जागरूक और आत्मनिर्भर बनाने की दिशा में भी काम करते हैं। संस्था की शिक्षा, स्वास्थ्य, स्वच्छता और पर्यावरण से जुड़ी पहलें समाज के लिए उपयोगी हैं।”', '★★★★★', 'P', NULL, 'Active', 2, 0, '2026-08-31 09:35:57', '2026-08-31 09:35:57');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('Super Admin','State Admin','Area Manager','Field Agent','Admin','Accountant','Volunteer Manager','CSR Manager','Volunteer') DEFAULT 'Volunteer',
  `status` tinyint(4) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `coordinator_code` varchar(40) DEFAULT NULL,
  `hierarchy_level` enum('admin','manager','coordinator') NOT NULL DEFAULT 'coordinator',
  `state` varchar(50) DEFAULT NULL,
  `area` varchar(100) DEFAULT NULL,
  `target_amount_monthly` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `status`, `created_at`, `coordinator_code`, `hierarchy_level`, `state`, `area`, `target_amount_monthly`) VALUES
(2, 'Super Admin', 'admin@ngo.com', '$2y$10$kkhA5RXyhrvOryG70U7ixu/IeADOrPWhT7vgXFClGDI1vZU8hSVf2', 'Admin', 1, '2025-12-02 13:14:05', 'COORD-00002', '', NULL, NULL, 0.00),
(4, 'Bhole upadhyay', 'bholeupadhyayu@gmail.com', '$2y$10$SiCI01Ji3HlfUncrqy3sMe0/RK7mo7zkzAJwTl/MiNpTARvB/.Dcu', 'Volunteer', 1, '2026-04-04 09:01:51', 'COORD-00004', 'coordinator', NULL, NULL, 0.00),
(5, 'Seed Council', 'seedcouncil.org@gmail.com', '$2y$10$wRJvvI7PpJq1onC9S65iwe/od0N3ouHSruuXFtlKVj3DTgtiZEF3q', 'Super Admin', 1, '2026-04-24 17:32:54', 'COORD-00005', '', NULL, NULL, 0.00),
(6, 'anuj upadhyay', 'anujbca2022@gmail.com', '$2y$10$JyuybY3yxet4sh9F5ucAjeK7m4S70NPspcWPKCEdKHF4Yh1HD66YW', 'Volunteer', 1, '2026-04-29 10:40:23', 'COORD-00006', 'coordinator', NULL, NULL, 0.00),
(7, 'State Admin Demo', 'state@ngo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'State Admin', 1, '2026-06-06 06:45:39', NULL, '', 'Uttar Pradesh', NULL, 0.00),
(8, 'Area Manager Demo', 'area@ngo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Area Manager', 1, '2026-06-06 06:45:39', NULL, '', 'Uttar Pradesh', 'Lucknow', 0.00),
(9, 'Field Agent Demo', 'agent@ngo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Field Agent', 1, '2026-06-06 06:45:39', NULL, '', 'Uttar Pradesh', 'Lucknow', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `user_permissions`
--

CREATE TABLE `user_permissions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `permission_key` varchar(120) NOT NULL,
  `is_allowed` tinyint(1) NOT NULL DEFAULT 1,
  `granted_by` int(11) DEFAULT NULL,
  `granted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_permissions`
--

INSERT INTO `user_permissions` (`id`, `user_id`, `permission_key`, `is_allowed`, `granted_by`, `granted_at`, `updated_at`) VALUES
(440, 4, 'page.dashboard', 0, 2, '2026-04-16 01:45:45', '2026-04-16 01:45:45'),
(441, 4, 'page.coordinator_reports', 0, 2, '2026-04-16 01:45:45', '2026-04-16 01:45:45'),
(442, 4, 'page.access_control', 0, 2, '2026-04-16 01:45:45', '2026-04-16 01:45:45'),
(443, 4, 'page.user_security', 0, 2, '2026-04-16 01:45:45', '2026-04-16 01:45:45'),
(444, 4, 'page.memberships', 1, 2, '2026-04-16 01:45:45', '2026-04-16 01:45:45'),
(445, 4, 'page.member_messages', 1, 2, '2026-04-16 01:45:45', '2026-04-16 01:45:45'),
(446, 4, 'page.member_documents', 1, 2, '2026-04-16 01:45:45', '2026-04-16 01:45:45'),
(447, 4, 'page.visitor_certificates', 1, 2, '2026-04-16 01:45:45', '2026-04-16 01:45:45'),
(448, 4, 'page.inquiries', 1, 2, '2026-04-16 01:45:45', '2026-04-16 01:45:45'),
(449, 4, 'page.volunteers', 1, 2, '2026-04-16 01:45:45', '2026-04-16 01:45:45'),
(450, 4, 'page.volunteer_activities', 1, 2, '2026-04-16 01:45:45', '2026-04-16 01:45:45'),
(451, 4, 'page.manager_panel', 1, 2, '2026-04-16 01:45:45', '2026-04-16 01:45:45'),
(452, 4, 'page.projects', 1, 2, '2026-04-16 01:45:45', '2026-04-16 01:45:45'),
(453, 4, 'page.events', 1, 2, '2026-04-16 01:45:45', '2026-04-16 01:45:45'),
(454, 4, 'page.donations', 1, 2, '2026-04-16 01:45:45', '2026-04-16 01:45:45'),
(455, 4, 'page.documents', 1, 2, '2026-04-16 01:45:45', '2026-04-16 01:45:45'),
(456, 4, 'page.management_body', 1, 2, '2026-04-16 01:45:45', '2026-04-16 01:45:45'),
(457, 4, 'page.donation_analytics', 1, 2, '2026-04-16 01:45:45', '2026-04-16 01:45:45'),
(458, 4, 'page.crowdfunding', 1, 2, '2026-04-16 01:45:45', '2026-04-16 01:45:45'),
(459, 4, 'page.page_manager', 1, 2, '2026-04-16 01:45:45', '2026-04-16 01:45:45'),
(460, 4, 'page.slider_manager', 1, 2, '2026-04-16 01:45:45', '2026-04-16 01:45:45'),
(461, 4, 'page.sponsors', 1, 2, '2026-04-16 01:45:45', '2026-04-16 01:45:45'),
(462, 4, 'page.about_manager', 1, 2, '2026-04-16 01:45:45', '2026-04-16 01:45:45'),
(463, 4, 'page.objectives_manager', 1, 2, '2026-04-16 01:45:45', '2026-04-16 01:45:45'),
(464, 4, 'page.awards_manager', 1, 2, '2026-04-16 01:45:45', '2026-04-16 01:45:45'),
(465, 4, 'page.gallery_manager', 1, 2, '2026-04-16 01:45:45', '2026-04-16 01:45:45'),
(466, 4, 'page.contact_manager', 1, 2, '2026-04-16 01:45:45', '2026-04-16 01:45:45'),
(467, 4, 'page.certificate_manager', 1, 2, '2026-04-16 01:45:45', '2026-04-16 01:45:45'),
(468, 4, 'page.privacy_manager', 1, 2, '2026-04-16 01:45:45', '2026-04-16 01:45:45'),
(469, 4, 'page.terms_manager', 1, 2, '2026-04-16 01:45:45', '2026-04-16 01:45:45'),
(470, 4, 'page.refund_manager', 1, 2, '2026-04-16 01:45:45', '2026-04-16 01:45:45'),
(471, 4, 'page.training_videos', 1, 2, '2026-04-16 01:45:45', '2026-04-16 01:45:45'),
(472, 4, 'page.news', 1, 2, '2026-04-16 01:45:45', '2026-04-16 01:45:45'),
(473, 4, 'page.reports', 1, 2, '2026-04-16 01:45:45', '2026-04-16 01:45:45'),
(474, 4, 'page.system_info', 1, 2, '2026-04-16 01:45:45', '2026-04-16 01:45:45'),
(475, 4, 'page.settings', 0, 2, '2026-04-16 01:45:45', '2026-04-16 01:45:45'),
(476, 4, 'page.backup_db', 0, 2, '2026-04-16 01:45:45', '2026-04-16 01:45:45'),
(477, 5, 'page.dashboard', 1, 2, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(478, 5, 'page.coordinator_reports', 1, 2, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(479, 5, 'page.access_control', 1, 2, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(480, 5, 'page.user_security', 1, 2, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(481, 5, 'page.memberships', 1, 2, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(482, 5, 'page.member_messages', 1, 2, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(483, 5, 'page.member_documents', 1, 2, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(484, 5, 'page.visitor_certificates', 1, 2, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(485, 5, 'page.inquiries', 1, 2, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(486, 5, 'page.volunteers', 1, 2, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(487, 5, 'page.volunteer_activities', 1, 2, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(488, 5, 'page.manager_panel', 1, 2, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(489, 5, 'page.projects', 1, 2, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(490, 5, 'page.events', 1, 2, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(491, 5, 'page.donations', 1, 2, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(492, 5, 'page.documents', 1, 2, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(493, 5, 'page.management_body', 1, 2, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(494, 5, 'page.donation_analytics', 1, 2, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(495, 5, 'page.crowdfunding', 1, 2, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(496, 5, 'page.page_manager', 1, 2, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(497, 5, 'page.slider_manager', 1, 2, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(498, 5, 'page.sponsors', 1, 2, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(499, 5, 'page.about_manager', 1, 2, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(500, 5, 'page.objectives_manager', 1, 2, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(501, 5, 'page.awards_manager', 1, 2, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(502, 5, 'page.gallery_manager', 1, 2, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(503, 5, 'page.contact_manager', 1, 2, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(504, 5, 'page.certificate_manager', 1, 2, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(505, 5, 'page.privacy_manager', 1, 2, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(506, 5, 'page.terms_manager', 1, 2, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(507, 5, 'page.refund_manager', 1, 2, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(508, 5, 'page.training_videos', 1, 2, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(509, 5, 'page.news', 1, 2, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(510, 5, 'page.reports', 1, 2, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(511, 5, 'page.system_info', 1, 2, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(512, 5, 'page.settings', 1, 2, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(513, 5, 'page.backup_db', 1, 2, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(514, 2, 'page.dashboard', 1, 2, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(515, 2, 'page.coordinator_reports', 1, 2, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(516, 2, 'page.access_control', 1, 2, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(517, 2, 'page.user_security', 1, 2, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(518, 2, 'page.memberships', 1, 2, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(519, 2, 'page.member_messages', 1, 2, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(520, 2, 'page.member_documents', 1, 2, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(521, 2, 'page.visitor_certificates', 1, 2, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(522, 2, 'page.inquiries', 1, 2, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(523, 2, 'page.volunteers', 1, 2, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(524, 2, 'page.volunteer_activities', 1, 2, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(525, 2, 'page.manager_panel', 1, 2, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(526, 2, 'page.projects', 1, 2, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(527, 2, 'page.events', 1, 2, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(528, 2, 'page.donations', 1, 2, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(529, 2, 'page.documents', 1, 2, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(530, 2, 'page.management_body', 1, 2, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(531, 2, 'page.donation_analytics', 1, 2, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(532, 2, 'page.crowdfunding', 1, 2, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(533, 2, 'page.page_manager', 1, 2, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(534, 2, 'page.slider_manager', 1, 2, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(535, 2, 'page.sponsors', 1, 2, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(536, 2, 'page.about_manager', 1, 2, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(537, 2, 'page.objectives_manager', 1, 2, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(538, 2, 'page.awards_manager', 1, 2, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(539, 2, 'page.gallery_manager', 1, 2, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(540, 2, 'page.contact_manager', 1, 2, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(541, 2, 'page.certificate_manager', 1, 2, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(542, 2, 'page.privacy_manager', 1, 2, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(543, 2, 'page.terms_manager', 1, 2, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(544, 2, 'page.refund_manager', 1, 2, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(545, 2, 'page.training_videos', 1, 2, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(546, 2, 'page.news', 1, 2, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(547, 2, 'page.reports', 1, 2, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(548, 2, 'page.system_info', 1, 2, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(549, 2, 'page.settings', 1, 2, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(550, 2, 'page.backup_db', 1, 2, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(551, 6, 'page.dashboard', 1, 2, '2026-04-29 10:40:50', '2026-04-29 10:40:50'),
(552, 6, 'page.coordinator_reports', 0, 2, '2026-04-29 10:40:50', '2026-04-29 10:40:50'),
(553, 6, 'page.access_control', 0, 2, '2026-04-29 10:40:50', '2026-04-29 10:40:50'),
(554, 6, 'page.user_security', 1, 2, '2026-04-29 10:40:50', '2026-04-29 10:40:50'),
(555, 6, 'page.memberships', 1, 2, '2026-04-29 10:40:50', '2026-04-29 10:40:50'),
(556, 6, 'page.member_messages', 0, 2, '2026-04-29 10:40:50', '2026-04-29 10:40:50'),
(557, 6, 'page.member_documents', 0, 2, '2026-04-29 10:40:50', '2026-04-29 10:40:50'),
(558, 6, 'page.visitor_certificates', 0, 2, '2026-04-29 10:40:50', '2026-04-29 10:40:50'),
(559, 6, 'page.inquiries', 0, 2, '2026-04-29 10:40:50', '2026-04-29 10:40:50'),
(560, 6, 'page.volunteers', 0, 2, '2026-04-29 10:40:50', '2026-04-29 10:40:50'),
(561, 6, 'page.volunteer_activities', 0, 2, '2026-04-29 10:40:50', '2026-04-29 10:40:50'),
(562, 6, 'page.manager_panel', 0, 2, '2026-04-29 10:40:50', '2026-04-29 10:40:50'),
(563, 6, 'page.projects', 0, 2, '2026-04-29 10:40:50', '2026-04-29 10:40:50'),
(564, 6, 'page.events', 0, 2, '2026-04-29 10:40:50', '2026-04-29 10:40:50'),
(565, 6, 'page.donations', 0, 2, '2026-04-29 10:40:50', '2026-04-29 10:40:50'),
(566, 6, 'page.documents', 0, 2, '2026-04-29 10:40:50', '2026-04-29 10:40:50'),
(567, 6, 'page.management_body', 0, 2, '2026-04-29 10:40:50', '2026-04-29 10:40:50'),
(568, 6, 'page.donation_analytics', 0, 2, '2026-04-29 10:40:50', '2026-04-29 10:40:50'),
(569, 6, 'page.crowdfunding', 0, 2, '2026-04-29 10:40:50', '2026-04-29 10:40:50'),
(570, 6, 'page.page_manager', 0, 2, '2026-04-29 10:40:50', '2026-04-29 10:40:50'),
(571, 6, 'page.slider_manager', 0, 2, '2026-04-29 10:40:50', '2026-04-29 10:40:50'),
(572, 6, 'page.sponsors', 0, 2, '2026-04-29 10:40:50', '2026-04-29 10:40:50'),
(573, 6, 'page.about_manager', 0, 2, '2026-04-29 10:40:50', '2026-04-29 10:40:50'),
(574, 6, 'page.objectives_manager', 0, 2, '2026-04-29 10:40:50', '2026-04-29 10:40:50'),
(575, 6, 'page.awards_manager', 0, 2, '2026-04-29 10:40:50', '2026-04-29 10:40:50'),
(576, 6, 'page.gallery_manager', 0, 2, '2026-04-29 10:40:50', '2026-04-29 10:40:50'),
(577, 6, 'page.contact_manager', 0, 2, '2026-04-29 10:40:50', '2026-04-29 10:40:50'),
(578, 6, 'page.certificate_manager', 0, 2, '2026-04-29 10:40:50', '2026-04-29 10:40:50'),
(579, 6, 'page.privacy_manager', 0, 2, '2026-04-29 10:40:50', '2026-04-29 10:40:50'),
(580, 6, 'page.terms_manager', 0, 2, '2026-04-29 10:40:50', '2026-04-29 10:40:50'),
(581, 6, 'page.refund_manager', 0, 2, '2026-04-29 10:40:50', '2026-04-29 10:40:50'),
(582, 6, 'page.training_videos', 0, 2, '2026-04-29 10:40:50', '2026-04-29 10:40:50'),
(583, 6, 'page.news', 0, 2, '2026-04-29 10:40:50', '2026-04-29 10:40:50'),
(584, 6, 'page.reports', 1, 2, '2026-04-29 10:40:50', '2026-04-29 10:40:50'),
(585, 6, 'page.system_info', 0, 2, '2026-04-29 10:40:50', '2026-04-29 10:40:50'),
(586, 6, 'page.settings', 0, 2, '2026-04-29 10:40:50', '2026-04-29 10:40:50'),
(587, 6, 'page.backup_db', 0, 2, '2026-04-29 10:40:50', '2026-04-29 10:40:50');

-- --------------------------------------------------------

--
-- Table structure for table `visitor_certificates`
--

CREATE TABLE `visitor_certificates` (
  `id` int(11) NOT NULL,
  `recipient_name` varchar(150) NOT NULL,
  `recipient_email` varchar(150) NOT NULL,
  `certificate_title` varchar(200) NOT NULL,
  `issued_for` varchar(255) DEFAULT NULL,
  `template_no` tinyint(2) NOT NULL DEFAULT 1,
  `certificate_no` varchar(80) DEFAULT NULL,
  `qr_payload` text DEFAULT NULL,
  `issued_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `volunteers`
--

CREATE TABLE `volunteers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `qualification` varchar(150) DEFAULT NULL,
  `profession` varchar(150) DEFAULT NULL,
  `marital_status` varchar(30) DEFAULT NULL,
  `blood_group` varchar(5) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `local_body_type` varchar(30) DEFAULT NULL,
  `local_body_name` varchar(150) DEFAULT NULL,
  `ward_no` varchar(20) DEFAULT NULL,
  `ward_name` varchar(150) DEFAULT NULL,
  `kudumbha_samithi` varchar(150) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Active','Inactive','Rejected') DEFAULT 'Pending',
  `id_card_no` varchar(50) DEFAULT NULL,
  `valid_from` date DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `volunteers`
--

INSERT INTO `volunteers` (`id`, `name`, `email`, `phone`, `qualification`, `profession`, `marital_status`, `blood_group`, `address`, `district`, `state`, `local_body_type`, `local_body_name`, `ward_no`, `ward_name`, `kudumbha_samithi`, `photo`, `status`, `id_card_no`, `valid_from`, `valid_until`, `created_at`, `approved_by`, `approved_at`) VALUES
(10, 'Abhishek Upadhyay', 'panditabhishek9651@gmail.com', '9651826737', 'MCA', 'Developer', 'Single', 'A+', 'Jaunpur', 'Jaunpur', 'Uttar Pradesh', NULL, NULL, NULL, NULL, NULL, 'uploads/volunteers/1788342735_6a97f1cf06638.jpg', 'Active', 'VOL-0001', '2026-08-21', '2027-09-02', '2026-08-21 08:49:34', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `volunteer_activities`
--

CREATE TABLE `volunteer_activities` (
  `id` int(11) NOT NULL,
  `volunteer_id` int(11) NOT NULL,
  `activity_type` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `event_id` int(11) DEFAULT NULL,
  `hours_spent` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `achievement_positions`
--
ALTER TABLE `achievement_positions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin_audit_logs`
--
ALTER TABLE `admin_audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin_audit_user` (`user_id`,`created_at`),
  ADD KEY `idx_admin_audit_entity` (`entity_type`,`entity_id`),
  ADD KEY `idx_admin_audit_action` (`action`,`created_at`);

--
-- Indexes for table `agent_attendance`
--
ALTER TABLE `agent_attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_attendance_agent_time` (`agent_id`,`punch_time`),
  ADD KEY `idx_attendance_type` (`punch_type`);

--
-- Indexes for table `agent_salary_ledger`
--
ALTER TABLE `agent_salary_ledger`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_agent_month` (`agent_id`,`month_key`),
  ADD KEY `idx_salary_month` (`month_key`);

--
-- Indexes for table `agreements`
--
ALTER TABLE `agreements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_agr_no` (`agreement_no`),
  ADD KEY `idx_agr_partner_name` (`partner_name`),
  ADD KEY `idx_agr_type` (`type`),
  ADD KEY `idx_agr_signed_status` (`signed_status`),
  ADD KEY `idx_agr_signed_date` (`signed_date`),
  ADD KEY `idx_agr_created_by` (`created_by`),
  ADD KEY `idx_agr_created_at` (`created_at`);

--
-- Indexes for table `attendance_bonus_history`
--
ALTER TABLE `attendance_bonus_history`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_bonus_history` (`student_id`,`year`,`month`),
  ADD KEY `idx_bonus_student` (`student_id`),
  ADD KEY `idx_bonus_yearmonth` (`year`,`month`),
  ADD KEY `idx_bonus_threshold_met` (`threshold_met`),
  ADD KEY `idx_bonus_awarded` (`awarded_at`),
  ADD KEY `idx_bonus_points` (`bonus_points_awarded`),
  ADD KEY `reference_threshold_id` (`reference_threshold_id`);

--
-- Indexes for table `attendance_events`
--
ALTER TABLE `attendance_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_attendance_events_event` (`event_id`),
  ADD KEY `idx_attendance_events_status_date` (`status`,`event_date`),
  ADD KEY `fk_attendance_events_user` (`created_by`);

--
-- Indexes for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_attendance_logs_event_member` (`event_id`,`member_id`),
  ADD KEY `idx_attendance_logs_scan_time` (`scan_time`),
  ADD KEY `fk_attendance_logs_member` (`member_id`),
  ADD KEY `idx_attendance_logs_event_volunteer` (`event_id`,`volunteer_id`),
  ADD KEY `fk_attendance_logs_volunteer` (`volunteer_id`);

--
-- Indexes for table `attendance_monthly_summary`
--
ALTER TABLE `attendance_monthly_summary`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_student_month` (`student_id`,`year`,`month`),
  ADD KEY `idx_monthly_student` (`student_id`),
  ADD KEY `idx_monthly_yearmonth` (`year`,`month`),
  ADD KEY `idx_monthly_percentage` (`attendance_percentage`),
  ADD KEY `idx_monthly_processed` (`is_processed`),
  ADD KEY `idx_monthly_bonus` (`attendance_bonus_awarded`);

--
-- Indexes for table `attendance_thresholds`
--
ALTER TABLE `attendance_thresholds`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_threshold_percentage` (`percentage_required`),
  ADD KEY `idx_threshold_active` (`is_active`),
  ADD KEY `idx_threshold_sort` (`sort_order`);

--
-- Indexes for table `badge_rules`
--
ALTER TABLE `badge_rules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_badge_rules_code` (`badge_code`),
  ADD KEY `idx_badge_rules_active` (`is_active`,`effective_from`,`effective_to`),
  ADD KEY `idx_badge_rules_rank` (`badge_rank`);

--
-- Indexes for table `bank_accounts`
--
ALTER TABLE `bank_accounts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `beneficiaries`
--
ALTER TABLE `beneficiaries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_beneficiary_code` (`beneficiary_code`),
  ADD KEY `idx_ben_name` (`name`),
  ADD KEY `idx_ben_contact` (`contact`),
  ADD KEY `idx_ben_aadhar` (`aadhar_no`),
  ADD KEY `idx_ben_location` (`state`,`district`,`block`),
  ADD KEY `idx_ben_status_date` (`status`,`registration_date`),
  ADD KEY `idx_ben_category` (`category_id`),
  ADD KEY `idx_ben_project` (`project_id`),
  ADD KEY `idx_ben_registered_by` (`registered_by`),
  ADD KEY `idx_ben_coordinator` (`coordinator_id`),
  ADD KEY `idx_ben_agent` (`field_agent_id`),
  ADD KEY `idx_ben_student` (`sa_student_id`);

--
-- Indexes for table `beneficiary_assistance_history`
--
ALTER TABLE `beneficiary_assistance_history`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_assistance_code` (`assistance_code`),
  ADD KEY `idx_ast_beneficiary_id` (`beneficiary_id`),
  ADD KEY `idx_ast_type_date` (`assistance_type`,`date`),
  ADD KEY `idx_ast_status` (`status`),
  ADD KEY `idx_ast_coordinator` (`coordinator_id`),
  ADD KEY `idx_ast_agent` (`field_agent_id`),
  ADD KEY `idx_ast_student` (`sa_student_id`),
  ADD KEY `idx_ast_project` (`project_id`),
  ADD KEY `idx_ast_item_donation` (`item_donation_id`),
  ADD KEY `idx_ast_donation` (`donation_id`),
  ADD KEY `idx_ast_event` (`event_id`);

--
-- Indexes for table `beneficiary_categories`
--
ALTER TABLE `beneficiary_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_ben_cat_name` (`category_name`),
  ADD UNIQUE KEY `uniq_ben_cat_slug` (`category_slug`),
  ADD KEY `idx_ben_cat_active_order` (`is_active`,`display_order`);

--
-- Indexes for table `cash_deposits`
--
ALTER TABLE `cash_deposits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cash_status` (`status`),
  ADD KEY `idx_cash_deadline` (`deposit_deadline`),
  ADD KEY `donation_id` (`donation_id`),
  ADD KEY `agent_id` (`agent_id`);

--
-- Indexes for table `certificates`
--
ALTER TABLE `certificates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `complaints`
--
ALTER TABLE `complaints`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_complaint_ticket` (`ticket_no`),
  ADD KEY `idx_complaints_type` (`type`),
  ADD KEY `idx_complaints_status` (`status`),
  ADD KEY `idx_complaints_contact` (`contact`),
  ADD KEY `idx_complaints_email` (`email`),
  ADD KEY `idx_complaints_created` (`created_at`),
  ADD KEY `idx_complaints_reply_by` (`reply_by`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `course_applications`
--
ALTER TABLE `course_applications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `crowdfunding_campaigns`
--
ALTER TABLE `crowdfunding_campaigns`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `custom_receipts`
--
ALTER TABLE `custom_receipts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_receipt_no` (`receipt_no`),
  ADD KEY `idx_payer_name` (`payer_name`),
  ADD KEY `idx_receipt_date` (`date`),
  ADD KEY `idx_payment_mode` (`payment_mode`),
  ADD KEY `idx_generated_by` (`generated_by`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `doctor_agreements`
--
ALTER TABLE `doctor_agreements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_doctor_agreement_no` (`agreement_no`),
  ADD KEY `idx_doc_agr_partner` (`partner_id`),
  ADD KEY `idx_doc_agr_status` (`status`),
  ADD KEY `idx_doc_agr_signed_date` (`signed_date`),
  ADD KEY `idx_doc_agr_valid_until` (`valid_until`),
  ADD KEY `idx_doc_agr_created_by` (`created_by`),
  ADD KEY `idx_doc_agr_created_at` (`created_at`),
  ADD KEY `idx_doc_agr_cert_no` (`certificate_no`);

--
-- Indexes for table `donations`
--
ALTER TABLE `donations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `receipt_no` (`receipt_no`),
  ADD KEY `project_id_fk` (`project_id`),
  ADD KEY `idx_donations_verified_by` (`verified_by`),
  ADD KEY `idx_donations_achievement` (`sa_student_id`,`achievement_processed`),
  ADD KEY `idx_donations_recurring_id` (`recurring_donation_id`);

--
-- Indexes for table `donation_achievements`
--
ALTER TABLE `donation_achievements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_student_milestone` (`student_id`,`milestone_id`),
  ADD KEY `idx_achievement_student` (`student_id`),
  ADD KEY `idx_achievement_milestone` (`milestone_id`),
  ADD KEY `idx_achievement_donation` (`reference_donation_id`),
  ADD KEY `idx_achievement_timestamp` (`achieved_at`),
  ADD KEY `idx_achievement_processed` (`processed_by_cron`);

--
-- Indexes for table `donation_milestones`
--
ALTER TABLE `donation_milestones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_milestone_amount` (`milestone_amount`),
  ADD KEY `idx_milestones_active` (`is_active`),
  ADD KEY `idx_milestones_sort` (`sort_order`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_events_date` (`event_date`);

--
-- Indexes for table `event_gallery`
--
ALTER TABLE `event_gallery`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_event_gallery_event` (`event_id`);

--
-- Indexes for table `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_event_regs_event_email` (`event_id`,`email`),
  ADD KEY `idx_event_regs_event` (`event_id`),
  ADD KEY `idx_event_regs_email` (`email`),
  ADD KEY `idx_event_reg_sa_student` (`sa_student_id`,`event_id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_expense_code` (`expense_code`),
  ADD KEY `idx_expenses_category` (`category_id`),
  ADD KEY `idx_expenses_project` (`project_id`),
  ADD KEY `idx_expenses_date` (`date`),
  ADD KEY `idx_expenses_status_date` (`approved_status`,`date`),
  ADD KEY `idx_expenses_added_by` (`added_by`),
  ADD KEY `idx_expenses_approved_by` (`approved_by`);

--
-- Indexes for table `expense_categories`
--
ALTER TABLE `expense_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_expense_cat_name` (`category_name`),
  ADD UNIQUE KEY `uniq_expense_cat_slug` (`category_slug`),
  ADD KEY `idx_expense_cat_active_order` (`is_active`,`display_order`);

--
-- Indexes for table `feedbacks`
--
ALTER TABLE `feedbacks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_feedback_no` (`feedback_no`),
  ADD KEY `idx_feedback_type` (`submitter_type`),
  ADD KEY `idx_feedback_status` (`status`),
  ADD KEY `idx_feedback_category` (`category`),
  ADD KEY `idx_feedback_rating` (`rating`),
  ADD KEY `idx_feedback_email` (`email`),
  ADD KEY `idx_feedback_created` (`created_at`),
  ADD KEY `idx_feedback_reply_by` (`reply_by`);

--
-- Indexes for table `field_agents`
--
ALTER TABLE `field_agents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_agent_monthly` (`collected_amount_monthly`);

--
-- Indexes for table `gallery`
--
ALTER TABLE `gallery`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `healthcare_providers`
--
ALTER TABLE `healthcare_providers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_hcp_code` (`provider_code`),
  ADD KEY `idx_hcp_type` (`type`),
  ADD KEY `idx_hcp_speciality` (`speciality`),
  ADD KEY `idx_hcp_state` (`state`),
  ADD KEY `idx_hcp_district` (`district`),
  ADD KEY `idx_hcp_block` (`block`),
  ADD KEY `idx_hcp_status` (`status`),
  ADD KEY `idx_hcp_contact` (`contact`),
  ADD KEY `idx_hcp_created_by` (`created_by`),
  ADD KEY `idx_hcp_created_at` (`created_at`);

--
-- Indexes for table `healthcare_referrals`
--
ALTER TABLE `healthcare_referrals`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_hcr_referral_no` (`referral_no`),
  ADD KEY `idx_hcr_provider_id` (`provider_id`),
  ADD KEY `idx_hcr_beneficiary_id` (`beneficiary_id`),
  ADD KEY `idx_hcr_appointment_date` (`appointment_date`),
  ADD KEY `idx_hcr_status` (`status`),
  ADD KEY `idx_hcr_created_by` (`created_by`);

--
-- Indexes for table `healthcare_services`
--
ALTER TABLE `healthcare_services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_hcs_provider_id` (`provider_id`),
  ADD KEY `idx_hcs_category` (`category`),
  ADD KEY `idx_hcs_status` (`status`);

--
-- Indexes for table `health_cards`
--
ALTER TABLE `health_cards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_health_card_number` (`card_number`),
  ADD KEY `idx_hc_applicant_name` (`applicant_name`),
  ADD KEY `idx_hc_contact` (`contact`),
  ADD KEY `idx_hc_status` (`status`),
  ADD KEY `idx_hc_issue_date` (`issue_date`),
  ADD KEY `idx_hc_expiry_date` (`expiry_date`),
  ADD KEY `idx_hc_state` (`state`),
  ADD KEY `idx_hc_district` (`district`),
  ADD KEY `idx_hc_beneficiary_id` (`beneficiary_id`),
  ADD KEY `idx_hc_issued_by` (`issued_by`),
  ADD KEY `idx_hc_previous_card_number` (`previous_card_number`);

--
-- Indexes for table `health_programs`
--
ALTER TABLE `health_programs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hr_policies`
--
ALTER TABLE `hr_policies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `policy_code` (`policy_code`),
  ADD KEY `idx_hr_category` (`category`),
  ADD KEY `idx_hr_public` (`is_public`);

--
-- Indexes for table `inquiries`
--
ALTER TABLE `inquiries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_inquiries_status` (`status`);

--
-- Indexes for table `item_donations`
--
ALTER TABLE `item_donations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_item_donation_code` (`donation_code`),
  ADD KEY `idx_item_category` (`category_id`),
  ADD KEY `idx_item_donor_id` (`donor_id`),
  ADD KEY `idx_item_donor_email` (`donor_email`),
  ADD KEY `idx_item_donor_mobile` (`donor_mobile`),
  ADD KEY `idx_item_status_date` (`status`,`donation_date`),
  ADD KEY `idx_item_project` (`project_id`),
  ADD KEY `idx_item_agent` (`field_agent_id`),
  ADD KEY `idx_item_student` (`sa_student_id`);

--
-- Indexes for table `item_donation_categories`
--
ALTER TABLE `item_donation_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_category_name` (`category_name`),
  ADD UNIQUE KEY `uniq_category_slug` (`category_slug`),
  ADD KEY `idx_cat_active_order` (`is_active`,`display_order`);

--
-- Indexes for table `job_applications`
--
ALTER TABLE `job_applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_job_application_no` (`application_no`),
  ADD KEY `idx_app_job_id` (`job_id`),
  ADD KEY `idx_app_applicant_name` (`applicant_name`),
  ADD KEY `idx_app_contact` (`contact`),
  ADD KEY `idx_app_email` (`email`),
  ADD KEY `idx_app_status` (`status`),
  ADD KEY `idx_app_applied_date` (`applied_date`),
  ADD KEY `idx_app_reviewed_by` (`reviewed_by`);

--
-- Indexes for table `job_openings`
--
ALTER TABLE `job_openings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_job_code` (`job_code`),
  ADD KEY `idx_jobs_category` (`category`),
  ADD KEY `idx_jobs_status` (`status`),
  ADD KEY `idx_jobs_location` (`location`),
  ADD KEY `idx_jobs_state` (`state`),
  ADD KEY `idx_jobs_district` (`district`),
  ADD KEY `idx_jobs_posted_date` (`posted_date`),
  ADD KEY `idx_jobs_last_date` (`last_date`),
  ADD KEY `idx_jobs_created_by` (`created_by`);

--
-- Indexes for table `join_applications`
--
ALTER TABLE `join_applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_join_app_no` (`application_no`),
  ADD KEY `idx_join_app_type` (`application_type`),
  ADD KEY `idx_join_project_id` (`project_id`),
  ADD KEY `idx_join_job_id` (`job_id`),
  ADD KEY `idx_join_payment_status` (`payment_status`),
  ADD KEY `idx_join_status` (`status`),
  ADD KEY `idx_join_contact` (`contact`),
  ADD KEY `idx_join_email` (`email`),
  ADD KEY `idx_join_applied_date` (`applied_date`),
  ADD KEY `fk_join_app_reviewed_by` (`reviewed_by`);

--
-- Indexes for table `letters`
--
ALTER TABLE `letters`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_letter_reference_no` (`reference_no`),
  ADD KEY `idx_letters_type` (`letter_type`),
  ADD KEY `idx_letters_generated_date` (`generated_date`),
  ADD KEY `idx_letters_status` (`status`),
  ADD KEY `idx_letters_generated_by` (`generated_by`),
  ADD KEY `idx_letters_recipient_name` (`recipient_name`);

--
-- Indexes for table `management_body`
--
ALTER TABLE `management_body`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_active_order` (`is_active`,`sort_order`);

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_members_email` (`email`),
  ADD UNIQUE KEY `uq_member_no` (`member_no`),
  ADD UNIQUE KEY `uq_referral_code` (`referral_code`),
  ADD UNIQUE KEY `uq_donation_ref_code` (`donation_ref_code`),
  ADD UNIQUE KEY `uq_member_receipt_no` (`member_receipt_no`),
  ADD KEY `idx_members_designation` (`designation_id`),
  ADD KEY `idx_members_referred_by` (`referred_by_member_id`),
  ADD KEY `idx_members_created_by` (`created_by`);

--
-- Indexes for table `member_designations`
--
ALTER TABLE `member_designations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `member_documents`
--
ALTER TABLE `member_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_member_docs_member` (`member_id`),
  ADD KEY `idx_member_docs_type` (`doc_type`);

--
-- Indexes for table `member_messages`
--
ALTER TABLE `member_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_member_messages_member` (`member_id`);

--
-- Indexes for table `member_message_deliveries`
--
ALTER TABLE `member_message_deliveries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mmd_message` (`message_id`),
  ADD KEY `idx_mmd_member` (`member_id`);

--
-- Indexes for table `ngo_documents`
--
ALTER TABLE `ngo_documents`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_student_pref` (`student_id`),
  ADD KEY `idx_dashboard_enabled` (`dashboard_notifications_enabled`);

--
-- Indexes for table `notification_templates`
--
ALTER TABLE `notification_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_notification_type` (`notification_type`),
  ADD KEY `idx_enabled` (`is_enabled`);

--
-- Indexes for table `org_structure`
--
ALTER TABLE `org_structure`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_org_parent` (`parent_id`),
  ADD KEY `idx_org_dept` (`department`),
  ADD KEY `idx_org_level` (`level_tier`),
  ADD KEY `idx_org_status` (`is_active`),
  ADD KEY `fk_org_mgmt_body` (`management_body_id`),
  ADD KEY `fk_org_member_desig` (`designation_id`);

--
-- Indexes for table `payment_qrs`
--
ALTER TABLE `payment_qrs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payment_webhook_logs`
--
ALTER TABLE `payment_webhook_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_gateway_payid` (`gateway`,`payment_id`),
  ADD KEY `idx_gateway_orderid` (`gateway`,`order_id`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_posts_slug` (`slug`),
  ADD KEY `idx_posts_status_published` (`status`,`published_at`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_priority` (`priority_order`);

--
-- Indexes for table `program_features`
--
ALTER TABLE `program_features`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_program` (`program_id`);

--
-- Indexes for table `program_gallery`
--
ALTER TABLE `program_gallery`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_program` (`program_id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `project_gallery`
--
ALTER TABLE `project_gallery`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `promotion_rules`
--
ALTER TABLE `promotion_rules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_promotion_rules_level` (`level_name`),
  ADD UNIQUE KEY `uniq_promotion_rules_rank` (`level_rank`),
  ADD KEY `idx_promotion_rules_active` (`is_active`,`effective_from`,`effective_to`);

--
-- Indexes for table `qr_scan_logs`
--
ALTER TABLE `qr_scan_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_qr_scan_logs_token` (`qr_token_id`),
  ADD KEY `idx_qr_scan_logs_member` (`member_id`),
  ADD KEY `idx_qr_scan_logs_scanned_at` (`scanned_at`);

--
-- Indexes for table `qr_tokens`
--
ALTER TABLE `qr_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_qr_tokens_token` (`token`),
  ADD KEY `idx_qr_tokens_member_doc` (`member_id`,`document_type`,`status`),
  ADD KEY `idx_qr_tokens_expires_at` (`expires_at`);

--
-- Indexes for table `razorpay_webhook_logs`
--
ALTER TABLE `razorpay_webhook_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_razorpay_webhook_payment` (`payment_id`);

--
-- Indexes for table `recurring_donations`
--
ALTER TABLE `recurring_donations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_rec_subscription_id` (`razorpay_subscription_id`),
  ADD KEY `idx_rec_donor_email` (`donor_email`),
  ADD KEY `idx_rec_donor_mobile` (`donor_mobile`),
  ADD KEY `idx_rec_status_next_charge` (`status`,`next_charge_date`),
  ADD KEY `idx_rec_project_id` (`project_id`),
  ADD KEY `idx_rec_sa_student` (`sa_student_id`),
  ADD KEY `idx_rec_field_agent` (`field_agent_id`);

--
-- Indexes for table `recurring_donation_transactions`
--
ALTER TABLE `recurring_donation_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rec_tx_parent` (`recurring_donation_id`),
  ADD KEY `idx_rec_tx_donation` (`donation_id`),
  ADD KEY `idx_rec_tx_status_date` (`status`,`charge_date`),
  ADD KEY `idx_rec_tx_payment_id` (`razorpay_payment_id`),
  ADD KEY `idx_rec_tx_subscription_id` (`razorpay_subscription_id`);

--
-- Indexes for table `sanstha_certificates`
--
ALTER TABLE `sanstha_certificates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_scert_no` (`certificate_no`),
  ADD KEY `idx_scert_status` (`status`);

--
-- Indexes for table `sa_activity_logs`
--
ALTER TABLE `sa_activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sa_activity_student` (`student_id`),
  ADD KEY `idx_sa_activity_date` (`created_at`);

--
-- Indexes for table `sa_announcements`
--
ALTER TABLE `sa_announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sa_announcements_active` (`is_active`,`created_at`);

--
-- Indexes for table `sa_attendance_logs`
--
ALTER TABLE `sa_attendance_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sa_attendance_student` (`student_id`),
  ADD KEY `idx_sa_attendance_date` (`attendance_date`);

--
-- Indexes for table `sa_badges`
--
ALTER TABLE `sa_badges`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sa_certificates`
--
ALTER TABLE `sa_certificates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sa_certificate_no` (`certificate_no`),
  ADD KEY `idx_sa_certificates_student_issued` (`student_id`,`issued_at`);

--
-- Indexes for table `sa_events`
--
ALTER TABLE `sa_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sa_events_status` (`status`);

--
-- Indexes for table `sa_event_registrations`
--
ALTER TABLE `sa_event_registrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sa_event_student` (`event_id`,`student_id`),
  ADD KEY `idx_sa_event_reg_student` (`student_id`);

--
-- Indexes for table `sa_login_logs`
--
ALTER TABLE `sa_login_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sa_login_student` (`student_id`),
  ADD KEY `idx_sa_login_date` (`login_at`);

--
-- Indexes for table `sa_notifications`
--
ALTER TABLE `sa_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sa_notification_student` (`student_id`);

--
-- Indexes for table `sa_partners`
--
ALTER TABLE `sa_partners`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_sa_partners_code` (`partner_code`),
  ADD UNIQUE KEY `uniq_sa_partners_email` (`owner_email`),
  ADD KEY `idx_sa_partners_status` (`status`),
  ADD KEY `idx_sa_partners_activity` (`activity_type`),
  ADD KEY `idx_sa_partners_city` (`city_name`,`status`),
  ADD KEY `idx_sa_partners_coords` (`latitude`,`longitude`),
  ADD KEY `idx_sa_partners_student_ref` (`referred_by_student_id`),
  ADD KEY `idx_sa_partners_email_status` (`owner_email`,`status`);

--
-- Indexes for table `sa_penalties`
--
ALTER TABLE `sa_penalties`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sa_penalties_student` (`student_id`,`status`),
  ADD KEY `idx_sa_penalties_program` (`program_id`),
  ADD KEY `idx_sa_penalties_type` (`penalty_type`,`severity`),
  ADD KEY `idx_sa_penalties_dates` (`starts_at`,`ends_at`,`resolved_at`),
  ADD KEY `idx_sa_penalties_tx` (`point_transaction_id`),
  ADD KEY `fk_sa_penalties_imposed_by_user` (`imposed_by_user_id`),
  ADD KEY `fk_sa_penalties_resolved_by_user` (`resolved_by_user_id`),
  ADD KEY `fk_sa_penalties_created_by_user` (`created_by_user_id`),
  ADD KEY `fk_sa_penalties_updated_by_user` (`updated_by_user_id`),
  ADD KEY `fk_sa_penalties_deleted_by_user` (`deleted_by_user_id`);

--
-- Indexes for table `sa_point_rules`
--
ALTER TABLE `sa_point_rules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_sa_point_rules_code` (`rule_code`),
  ADD KEY `idx_sa_point_rules_program` (`program_id`),
  ADD KEY `idx_sa_point_rules_category` (`category`),
  ADD KEY `idx_sa_point_rules_trigger` (`trigger_key`),
  ADD KEY `idx_sa_point_rules_scope` (`entity_scope`),
  ADD KEY `idx_sa_point_rules_active` (`is_active`,`deleted_at`),
  ADD KEY `idx_sa_point_rules_effective` (`effective_from`,`effective_to`),
  ADD KEY `fk_sa_point_rules_created_by_user` (`created_by_user_id`),
  ADD KEY `fk_sa_point_rules_updated_by_user` (`updated_by_user_id`),
  ADD KEY `fk_sa_point_rules_deleted_by_user` (`deleted_by_user_id`);

--
-- Indexes for table `sa_point_transactions`
--
ALTER TABLE `sa_point_transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_sa_point_tx_idempotency` (`idempotency_key`),
  ADD KEY `idx_sa_point_tx_student` (`student_id`,`posted_at`),
  ADD KEY `idx_sa_point_tx_program` (`program_id`),
  ADD KEY `idx_sa_point_tx_rule` (`rule_id`),
  ADD KEY `idx_sa_point_tx_source` (`source_type`,`source_id`),
  ADD KEY `idx_sa_point_tx_status` (`transaction_status`,`deleted_at`),
  ADD KEY `idx_sa_point_tx_reference` (`reference_code`),
  ADD KEY `idx_sa_point_tx_reversed_tx` (`reversed_transaction_id`),
  ADD KEY `fk_sa_point_tx_approved_by_user` (`approved_by_user_id`),
  ADD KEY `fk_sa_point_tx_created_by_user` (`created_by_user_id`),
  ADD KEY `fk_sa_point_tx_updated_by_user` (`updated_by_user_id`),
  ADD KEY `fk_sa_point_tx_deleted_by_user` (`deleted_by_user_id`),
  ADD KEY `idx_sa_point_tx_posted` (`posted_at`,`transaction_status`,`deleted_at`);

--
-- Indexes for table `sa_programs`
--
ALTER TABLE `sa_programs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_sa_programs_code` (`program_code`),
  ADD KEY `idx_sa_programs_status` (`status`),
  ADD KEY `idx_sa_programs_type` (`program_type`),
  ADD KEY `idx_sa_programs_batch` (`batch_name`),
  ADD KEY `idx_sa_programs_dates` (`start_date`,`end_date`),
  ADD KEY `idx_sa_programs_mentor` (`mentor_user_id`),
  ADD KEY `idx_sa_programs_deleted_at` (`deleted_at`),
  ADD KEY `idx_sa_programs_created_by` (`created_by_user_id`),
  ADD KEY `idx_sa_programs_updated_by` (`updated_by_user_id`),
  ADD KEY `idx_sa_programs_deleted_by` (`deleted_by_user_id`);

--
-- Indexes for table `sa_referrals`
--
ALTER TABLE `sa_referrals`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_sa_referrals_student_link` (`referrer_student_id`,`referred_student_id`,`referral_type`),
  ADD KEY `idx_sa_referrals_program` (`program_id`),
  ADD KEY `idx_sa_referrals_status` (`verification_status`,`deleted_at`),
  ADD KEY `idx_sa_referrals_referred_email` (`referred_email`),
  ADD KEY `idx_sa_referrals_referred_mobile` (`referred_mobile`),
  ADD KEY `idx_sa_referrals_verified_by_user` (`verified_by_user_id`),
  ADD KEY `fk_sa_referrals_referred_student` (`referred_student_id`),
  ADD KEY `fk_sa_referrals_created_by_user` (`created_by_user_id`),
  ADD KEY `fk_sa_referrals_updated_by_user` (`updated_by_user_id`),
  ADD KEY `fk_sa_referrals_deleted_by_user` (`deleted_by_user_id`);

--
-- Indexes for table `sa_students`
--
ALTER TABLE `sa_students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sa_student_no` (`student_no`),
  ADD UNIQUE KEY `uq_sa_student_email` (`email`),
  ADD UNIQUE KEY `uq_sa_student_mobile` (`mobile`),
  ADD UNIQUE KEY `uq_sa_student_referral` (`referral_code`),
  ADD KEY `idx_sa_student_status` (`status`),
  ADD KEY `idx_sa_student_referrer` (`referred_by_student_id`),
  ADD KEY `idx_sa_student_city` (`city_name`),
  ADD KEY `idx_sa_student_college` (`college_name`),
  ADD KEY `idx_sa_student_state` (`state_name`),
  ADD KEY `idx_sa_student_rank` (`rank_position`),
  ADD KEY `idx_sa_student_program` (`program_id`),
  ADD KEY `idx_sa_students_points_active` (`status`,`total_points`),
  ADD KEY `idx_sa_students_city_points` (`city_name`,`status`,`total_points`),
  ADD KEY `idx_sa_students_college_points` (`college_name`,`status`,`total_points`),
  ADD KEY `idx_sa_students_state_points` (`state_name`,`status`,`total_points`);

--
-- Indexes for table `sa_student_badges`
--
ALTER TABLE `sa_student_badges`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sa_student_badge` (`student_id`,`badge_id`);

--
-- Indexes for table `sa_student_event_requests`
--
ALTER TABLE `sa_student_event_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sa_event_req_student` (`student_id`,`status`),
  ADD KEY `idx_sa_event_req_status` (`status`,`created_at`);

--
-- Indexes for table `sa_tasks`
--
ALTER TABLE `sa_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sa_task_status` (`status`);

--
-- Indexes for table `sa_task_submissions`
--
ALTER TABLE `sa_task_submissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sa_task_submission` (`task_id`,`student_id`),
  ADD KEY `idx_sa_task_submission_status` (`status`);

--
-- Indexes for table `sa_vendor_leads`
--
ALTER TABLE `sa_vendor_leads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sa_vendor_leads_student` (`student_id`,`lead_status`),
  ADD KEY `idx_sa_vendor_leads_program` (`program_id`),
  ADD KEY `idx_sa_vendor_leads_type` (`lead_type`,`verification_status`),
  ADD KEY `idx_sa_vendor_leads_business` (`business_name`),
  ADD KEY `idx_sa_vendor_leads_contact_phone` (`contact_phone`),
  ADD KEY `idx_sa_vendor_leads_contact_email` (`contact_email`),
  ADD KEY `idx_sa_vendor_leads_verified_by_user` (`verified_by_user_id`),
  ADD KEY `fk_sa_vendor_leads_created_by_user` (`created_by_user_id`),
  ADD KEY `fk_sa_vendor_leads_updated_by_user` (`updated_by_user_id`),
  ADD KEY `fk_sa_vendor_leads_deleted_by_user` (`deleted_by_user_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `skill_courses`
--
ALTER TABLE `skill_courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_skill_category` (`category`),
  ADD KEY `idx_skill_status` (`status`);

--
-- Indexes for table `skill_course_enrollments`
--
ALTER TABLE `skill_course_enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `application_no` (`application_no`),
  ADD KEY `idx_enr_course` (`course_id`),
  ADD KEY `idx_enr_status` (`status`),
  ADD KEY `idx_enr_contact` (`contact`);

--
-- Indexes for table `sliders`
--
ALTER TABLE `sliders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sponsors`
--
ALTER TABLE `sponsors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staff_letters`
--
ALTER TABLE `staff_letters`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_staff_letter_no` (`letter_no`),
  ADD KEY `idx_staff_letter_type` (`type`),
  ADD KEY `idx_staff_letter_status` (`status`),
  ADD KEY `idx_staff_letter_name` (`name`),
  ADD KEY `idx_staff_letter_designation` (`designation`),
  ADD KEY `idx_staff_letter_issued_date` (`issued_date`),
  ADD KEY `idx_staff_letter_issued_by` (`issued_by`),
  ADD KEY `idx_staff_letter_created_at` (`created_at`);

--
-- Indexes for table `templates`
--
ALTER TABLE `templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_templates_type_status` (`template_type`,`status`),
  ADD KEY `idx_templates_user` (`user_id`);

--
-- Indexes for table `testimonials`
--
ALTER TABLE `testimonials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status_priority` (`status`,`priority_order`),
  ADD KEY `idx_featured` (`featured`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `uq_users_coordinator_code` (`coordinator_code`),
  ADD KEY `idx_users_hierarchy` (`hierarchy_level`),
  ADD KEY `idx_users_state` (`state`),
  ADD KEY `idx_users_area` (`area`);

--
-- Indexes for table `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user_permission` (`user_id`,`permission_key`),
  ADD KEY `idx_permission_key` (`permission_key`),
  ADD KEY `idx_granted_by` (`granted_by`);

--
-- Indexes for table `visitor_certificates`
--
ALTER TABLE `visitor_certificates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_visitor_certificate_no` (`certificate_no`);

--
-- Indexes for table `volunteers`
--
ALTER TABLE `volunteers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `id_card_no` (`id_card_no`),
  ADD KEY `idx_volunteers_approved_by` (`approved_by`);

--
-- Indexes for table `volunteer_activities`
--
ALTER TABLE `volunteer_activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `volunteer_id` (`volunteer_id`),
  ADD KEY `event_id` (`event_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `achievement_positions`
--
ALTER TABLE `achievement_positions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `admin_audit_logs`
--
ALTER TABLE `admin_audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `agent_attendance`
--
ALTER TABLE `agent_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `agent_salary_ledger`
--
ALTER TABLE `agent_salary_ledger`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `agreements`
--
ALTER TABLE `agreements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `attendance_bonus_history`
--
ALTER TABLE `attendance_bonus_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance_events`
--
ALTER TABLE `attendance_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `attendance_monthly_summary`
--
ALTER TABLE `attendance_monthly_summary`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance_thresholds`
--
ALTER TABLE `attendance_thresholds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `badge_rules`
--
ALTER TABLE `badge_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `bank_accounts`
--
ALTER TABLE `bank_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `beneficiaries`
--
ALTER TABLE `beneficiaries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `beneficiary_assistance_history`
--
ALTER TABLE `beneficiary_assistance_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `beneficiary_categories`
--
ALTER TABLE `beneficiary_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `cash_deposits`
--
ALTER TABLE `cash_deposits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `certificates`
--
ALTER TABLE `certificates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `complaints`
--
ALTER TABLE `complaints`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `course_applications`
--
ALTER TABLE `course_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `crowdfunding_campaigns`
--
ALTER TABLE `crowdfunding_campaigns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `custom_receipts`
--
ALTER TABLE `custom_receipts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `doctor_agreements`
--
ALTER TABLE `doctor_agreements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `donations`
--
ALTER TABLE `donations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `donation_achievements`
--
ALTER TABLE `donation_achievements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `donation_milestones`
--
ALTER TABLE `donation_milestones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `event_gallery`
--
ALTER TABLE `event_gallery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_registrations`
--
ALTER TABLE `event_registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `expense_categories`
--
ALTER TABLE `expense_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `feedbacks`
--
ALTER TABLE `feedbacks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `field_agents`
--
ALTER TABLE `field_agents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `gallery`
--
ALTER TABLE `gallery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `healthcare_providers`
--
ALTER TABLE `healthcare_providers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `healthcare_referrals`
--
ALTER TABLE `healthcare_referrals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `healthcare_services`
--
ALTER TABLE `healthcare_services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `health_cards`
--
ALTER TABLE `health_cards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `health_programs`
--
ALTER TABLE `health_programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `hr_policies`
--
ALTER TABLE `hr_policies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `inquiries`
--
ALTER TABLE `inquiries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `item_donations`
--
ALTER TABLE `item_donations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `item_donation_categories`
--
ALTER TABLE `item_donation_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `job_applications`
--
ALTER TABLE `job_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `job_openings`
--
ALTER TABLE `job_openings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `join_applications`
--
ALTER TABLE `join_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `letters`
--
ALTER TABLE `letters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `management_body`
--
ALTER TABLE `management_body`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `member_designations`
--
ALTER TABLE `member_designations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `member_documents`
--
ALTER TABLE `member_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `member_messages`
--
ALTER TABLE `member_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `member_message_deliveries`
--
ALTER TABLE `member_message_deliveries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `ngo_documents`
--
ALTER TABLE `ngo_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_templates`
--
ALTER TABLE `notification_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `org_structure`
--
ALTER TABLE `org_structure`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `payment_qrs`
--
ALTER TABLE `payment_qrs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payment_webhook_logs`
--
ALTER TABLE `payment_webhook_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `program_features`
--
ALTER TABLE `program_features`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `program_gallery`
--
ALTER TABLE `program_gallery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `project_gallery`
--
ALTER TABLE `project_gallery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `promotion_rules`
--
ALTER TABLE `promotion_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `qr_scan_logs`
--
ALTER TABLE `qr_scan_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `qr_tokens`
--
ALTER TABLE `qr_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `razorpay_webhook_logs`
--
ALTER TABLE `razorpay_webhook_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `recurring_donations`
--
ALTER TABLE `recurring_donations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `recurring_donation_transactions`
--
ALTER TABLE `recurring_donation_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sanstha_certificates`
--
ALTER TABLE `sanstha_certificates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sa_activity_logs`
--
ALTER TABLE `sa_activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `sa_announcements`
--
ALTER TABLE `sa_announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sa_attendance_logs`
--
ALTER TABLE `sa_attendance_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `sa_badges`
--
ALTER TABLE `sa_badges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `sa_certificates`
--
ALTER TABLE `sa_certificates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `sa_events`
--
ALTER TABLE `sa_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sa_event_registrations`
--
ALTER TABLE `sa_event_registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sa_login_logs`
--
ALTER TABLE `sa_login_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sa_notifications`
--
ALTER TABLE `sa_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sa_partners`
--
ALTER TABLE `sa_partners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sa_penalties`
--
ALTER TABLE `sa_penalties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sa_point_rules`
--
ALTER TABLE `sa_point_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `sa_point_transactions`
--
ALTER TABLE `sa_point_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sa_programs`
--
ALTER TABLE `sa_programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `sa_referrals`
--
ALTER TABLE `sa_referrals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sa_students`
--
ALTER TABLE `sa_students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `sa_student_badges`
--
ALTER TABLE `sa_student_badges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sa_student_event_requests`
--
ALTER TABLE `sa_student_event_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sa_tasks`
--
ALTER TABLE `sa_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sa_task_submissions`
--
ALTER TABLE `sa_task_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `sa_vendor_leads`
--
ALTER TABLE `sa_vendor_leads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `skill_courses`
--
ALTER TABLE `skill_courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `skill_course_enrollments`
--
ALTER TABLE `skill_course_enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sliders`
--
ALTER TABLE `sliders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `sponsors`
--
ALTER TABLE `sponsors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_letters`
--
ALTER TABLE `staff_letters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `templates`
--
ALTER TABLE `templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `testimonials`
--
ALTER TABLE `testimonials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `user_permissions`
--
ALTER TABLE `user_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=588;

--
-- AUTO_INCREMENT for table `visitor_certificates`
--
ALTER TABLE `visitor_certificates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `volunteers`
--
ALTER TABLE `volunteers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `volunteer_activities`
--
ALTER TABLE `volunteer_activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `agent_attendance`
--
ALTER TABLE `agent_attendance`
  ADD CONSTRAINT `agent_attendance_ibfk_1` FOREIGN KEY (`agent_id`) REFERENCES `field_agents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `agent_salary_ledger`
--
ALTER TABLE `agent_salary_ledger`
  ADD CONSTRAINT `agent_salary_ledger_ibfk_1` FOREIGN KEY (`agent_id`) REFERENCES `field_agents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `agreements`
--
ALTER TABLE `agreements`
  ADD CONSTRAINT `fk_agr_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `attendance_bonus_history`
--
ALTER TABLE `attendance_bonus_history`
  ADD CONSTRAINT `attendance_bonus_history_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `sa_students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_bonus_history_ibfk_2` FOREIGN KEY (`reference_threshold_id`) REFERENCES `attendance_thresholds` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `attendance_events`
--
ALTER TABLE `attendance_events`
  ADD CONSTRAINT `fk_attendance_events_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_attendance_events_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  ADD CONSTRAINT `fk_attendance_logs_event` FOREIGN KEY (`event_id`) REFERENCES `attendance_events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_attendance_logs_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_attendance_logs_volunteer` FOREIGN KEY (`volunteer_id`) REFERENCES `volunteers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance_monthly_summary`
--
ALTER TABLE `attendance_monthly_summary`
  ADD CONSTRAINT `attendance_monthly_summary_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `sa_students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `beneficiaries`
--
ALTER TABLE `beneficiaries`
  ADD CONSTRAINT `fk_ben_agent` FOREIGN KEY (`field_agent_id`) REFERENCES `field_agents` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_ben_category` FOREIGN KEY (`category_id`) REFERENCES `beneficiary_categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_ben_coordinator` FOREIGN KEY (`coordinator_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_ben_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_ben_registered_by` FOREIGN KEY (`registered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_ben_student` FOREIGN KEY (`sa_student_id`) REFERENCES `sa_students` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `beneficiary_assistance_history`
--
ALTER TABLE `beneficiary_assistance_history`
  ADD CONSTRAINT `fk_ast_agent` FOREIGN KEY (`field_agent_id`) REFERENCES `field_agents` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_ast_beneficiary` FOREIGN KEY (`beneficiary_id`) REFERENCES `beneficiaries` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ast_coordinator` FOREIGN KEY (`coordinator_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_ast_donation` FOREIGN KEY (`donation_id`) REFERENCES `donations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_ast_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_ast_item_donation` FOREIGN KEY (`item_donation_id`) REFERENCES `item_donations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_ast_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_ast_student` FOREIGN KEY (`sa_student_id`) REFERENCES `sa_students` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `cash_deposits`
--
ALTER TABLE `cash_deposits`
  ADD CONSTRAINT `cash_deposits_ibfk_1` FOREIGN KEY (`donation_id`) REFERENCES `donations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cash_deposits_ibfk_2` FOREIGN KEY (`agent_id`) REFERENCES `field_agents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `complaints`
--
ALTER TABLE `complaints`
  ADD CONSTRAINT `fk_complaints_reply_by` FOREIGN KEY (`reply_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `custom_receipts`
--
ALTER TABLE `custom_receipts`
  ADD CONSTRAINT `fk_custom_receipts_generated_by` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `doctor_agreements`
--
ALTER TABLE `doctor_agreements`
  ADD CONSTRAINT `fk_doc_agr_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_doc_agr_partner` FOREIGN KEY (`partner_id`) REFERENCES `healthcare_providers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `donations`
--
ALTER TABLE `donations`
  ADD CONSTRAINT `donations_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `donation_achievements`
--
ALTER TABLE `donation_achievements`
  ADD CONSTRAINT `donation_achievements_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `sa_students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `donation_achievements_ibfk_2` FOREIGN KEY (`milestone_id`) REFERENCES `donation_milestones` (`id`);

--
-- Constraints for table `event_gallery`
--
ALTER TABLE `event_gallery`
  ADD CONSTRAINT `fk_event_gallery_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD CONSTRAINT `fk_event_regs_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `fk_expenses_added_by` FOREIGN KEY (`added_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_expenses_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_expenses_category` FOREIGN KEY (`category_id`) REFERENCES `expense_categories` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_expenses_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `feedbacks`
--
ALTER TABLE `feedbacks`
  ADD CONSTRAINT `fk_feedback_reply_by` FOREIGN KEY (`reply_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `field_agents`
--
ALTER TABLE `field_agents`
  ADD CONSTRAINT `field_agents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `healthcare_providers`
--
ALTER TABLE `healthcare_providers`
  ADD CONSTRAINT `fk_hcp_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `healthcare_referrals`
--
ALTER TABLE `healthcare_referrals`
  ADD CONSTRAINT `fk_hcr_beneficiary` FOREIGN KEY (`beneficiary_id`) REFERENCES `beneficiaries` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hcr_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hcr_provider` FOREIGN KEY (`provider_id`) REFERENCES `healthcare_providers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `healthcare_services`
--
ALTER TABLE `healthcare_services`
  ADD CONSTRAINT `fk_hcs_provider` FOREIGN KEY (`provider_id`) REFERENCES `healthcare_providers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `health_cards`
--
ALTER TABLE `health_cards`
  ADD CONSTRAINT `fk_health_cards_beneficiary` FOREIGN KEY (`beneficiary_id`) REFERENCES `beneficiaries` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_health_cards_issued_by` FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `item_donations`
--
ALTER TABLE `item_donations`
  ADD CONSTRAINT `fk_item_agent` FOREIGN KEY (`field_agent_id`) REFERENCES `field_agents` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_item_category` FOREIGN KEY (`category_id`) REFERENCES `item_donation_categories` (`id`) ON DELETE NO ACTION,
  ADD CONSTRAINT `fk_item_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_item_student` FOREIGN KEY (`sa_student_id`) REFERENCES `sa_students` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `job_applications`
--
ALTER TABLE `job_applications`
  ADD CONSTRAINT `fk_job_applications_job` FOREIGN KEY (`job_id`) REFERENCES `job_openings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_job_applications_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `job_openings`
--
ALTER TABLE `job_openings`
  ADD CONSTRAINT `fk_job_openings_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `join_applications`
--
ALTER TABLE `join_applications`
  ADD CONSTRAINT `fk_join_app_job` FOREIGN KEY (`job_id`) REFERENCES `job_openings` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_join_app_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_join_app_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `letters`
--
ALTER TABLE `letters`
  ADD CONSTRAINT `fk_letters_generated_by` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `members`
--
ALTER TABLE `members`
  ADD CONSTRAINT `fk_members_designation` FOREIGN KEY (`designation_id`) REFERENCES `member_designations` (`id`),
  ADD CONSTRAINT `fk_members_referred_by` FOREIGN KEY (`referred_by_member_id`) REFERENCES `members` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `member_documents`
--
ALTER TABLE `member_documents`
  ADD CONSTRAINT `fk_member_docs_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `member_messages`
--
ALTER TABLE `member_messages`
  ADD CONSTRAINT `fk_member_messages_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `member_message_deliveries`
--
ALTER TABLE `member_message_deliveries`
  ADD CONSTRAINT `fk_mmd_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_mmd_message` FOREIGN KEY (`message_id`) REFERENCES `member_messages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  ADD CONSTRAINT `notification_preferences_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `sa_students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `org_structure`
--
ALTER TABLE `org_structure`
  ADD CONSTRAINT `fk_org_member_desig` FOREIGN KEY (`designation_id`) REFERENCES `member_designations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_org_mgmt_body` FOREIGN KEY (`management_body_id`) REFERENCES `management_body` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_org_parent` FOREIGN KEY (`parent_id`) REFERENCES `org_structure` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `program_features`
--
ALTER TABLE `program_features`
  ADD CONSTRAINT `program_features_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `program_gallery`
--
ALTER TABLE `program_gallery`
  ADD CONSTRAINT `program_gallery_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `project_gallery`
--
ALTER TABLE `project_gallery`
  ADD CONSTRAINT `project_gallery_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `qr_scan_logs`
--
ALTER TABLE `qr_scan_logs`
  ADD CONSTRAINT `fk_qr_scan_logs_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_qr_scan_logs_token` FOREIGN KEY (`qr_token_id`) REFERENCES `qr_tokens` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `qr_tokens`
--
ALTER TABLE `qr_tokens`
  ADD CONSTRAINT `fk_qr_tokens_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `recurring_donations`
--
ALTER TABLE `recurring_donations`
  ADD CONSTRAINT `fk_rec_agent` FOREIGN KEY (`field_agent_id`) REFERENCES `field_agents` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_rec_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_rec_student` FOREIGN KEY (`sa_student_id`) REFERENCES `sa_students` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `recurring_donation_transactions`
--
ALTER TABLE `recurring_donation_transactions`
  ADD CONSTRAINT `fk_rec_tx_donation` FOREIGN KEY (`donation_id`) REFERENCES `donations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_rec_tx_parent` FOREIGN KEY (`recurring_donation_id`) REFERENCES `recurring_donations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sa_penalties`
--
ALTER TABLE `sa_penalties`
  ADD CONSTRAINT `fk_sa_penalties_created_by_user` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_penalties_deleted_by_user` FOREIGN KEY (`deleted_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_penalties_imposed_by_user` FOREIGN KEY (`imposed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_penalties_program` FOREIGN KEY (`program_id`) REFERENCES `sa_programs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_penalties_resolved_by_user` FOREIGN KEY (`resolved_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_penalties_student` FOREIGN KEY (`student_id`) REFERENCES `sa_students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sa_penalties_tx` FOREIGN KEY (`point_transaction_id`) REFERENCES `sa_point_transactions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_penalties_updated_by_user` FOREIGN KEY (`updated_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sa_point_rules`
--
ALTER TABLE `sa_point_rules`
  ADD CONSTRAINT `fk_sa_point_rules_created_by_user` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_point_rules_deleted_by_user` FOREIGN KEY (`deleted_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_point_rules_program` FOREIGN KEY (`program_id`) REFERENCES `sa_programs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_point_rules_updated_by_user` FOREIGN KEY (`updated_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sa_point_transactions`
--
ALTER TABLE `sa_point_transactions`
  ADD CONSTRAINT `fk_sa_point_tx_approved_by_user` FOREIGN KEY (`approved_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_point_tx_created_by_user` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_point_tx_deleted_by_user` FOREIGN KEY (`deleted_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_point_tx_program` FOREIGN KEY (`program_id`) REFERENCES `sa_programs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_point_tx_reversed_tx` FOREIGN KEY (`reversed_transaction_id`) REFERENCES `sa_point_transactions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_point_tx_rule` FOREIGN KEY (`rule_id`) REFERENCES `sa_point_rules` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_point_tx_student` FOREIGN KEY (`student_id`) REFERENCES `sa_students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sa_point_tx_updated_by_user` FOREIGN KEY (`updated_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sa_programs`
--
ALTER TABLE `sa_programs`
  ADD CONSTRAINT `fk_sa_programs_created_by_user` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_programs_deleted_by_user` FOREIGN KEY (`deleted_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_programs_mentor_user` FOREIGN KEY (`mentor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_programs_updated_by_user` FOREIGN KEY (`updated_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sa_referrals`
--
ALTER TABLE `sa_referrals`
  ADD CONSTRAINT `fk_sa_referrals_created_by_user` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_referrals_deleted_by_user` FOREIGN KEY (`deleted_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_referrals_program` FOREIGN KEY (`program_id`) REFERENCES `sa_programs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_referrals_referred_student` FOREIGN KEY (`referred_student_id`) REFERENCES `sa_students` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_referrals_referrer_student` FOREIGN KEY (`referrer_student_id`) REFERENCES `sa_students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sa_referrals_updated_by_user` FOREIGN KEY (`updated_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_referrals_verified_by_user` FOREIGN KEY (`verified_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sa_students`
--
ALTER TABLE `sa_students`
  ADD CONSTRAINT `fk_sa_student_program` FOREIGN KEY (`program_id`) REFERENCES `sa_programs` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sa_vendor_leads`
--
ALTER TABLE `sa_vendor_leads`
  ADD CONSTRAINT `fk_sa_vendor_leads_created_by_user` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_vendor_leads_deleted_by_user` FOREIGN KEY (`deleted_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_vendor_leads_program` FOREIGN KEY (`program_id`) REFERENCES `sa_programs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_vendor_leads_student` FOREIGN KEY (`student_id`) REFERENCES `sa_students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sa_vendor_leads_updated_by_user` FOREIGN KEY (`updated_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_vendor_leads_verified_by_user` FOREIGN KEY (`verified_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `skill_course_enrollments`
--
ALTER TABLE `skill_course_enrollments`
  ADD CONSTRAINT `fk_enr_course_id` FOREIGN KEY (`course_id`) REFERENCES `skill_courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `staff_letters`
--
ALTER TABLE `staff_letters`
  ADD CONSTRAINT `fk_staff_letter_issued_by` FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `templates`
--
ALTER TABLE `templates`
  ADD CONSTRAINT `fk_templates_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `volunteer_activities`
--
ALTER TABLE `volunteer_activities`
  ADD CONSTRAINT `volunteer_activities_ibfk_1` FOREIGN KEY (`volunteer_id`) REFERENCES `volunteers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `volunteer_activities_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
