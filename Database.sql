-- ========================================================
-- NGO AI - Clean & Optimized Database Schema & Master Seed
-- Generated on: 2026-09-12 07:12:02
-- Charset: utf8mb4 | Collation: utf8mb4_unicode_ci
-- ========================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";
SET FOREIGN_KEY_CHECKS = 0;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------
-- Table structure for table `achievement_positions`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `achievement_positions` (

  `id` int(11) NOT NULL,
  `title` varchar(120) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `achievement_positions`
INSERT INTO `achievement_positions` (`id`, `title`, `is_active`, `created_at`) VALUES
(1, 'Winner', 1, '2026-04-22 01:58:45'),
(2, '1st Position', 1, '2026-04-22 01:58:45'),
(3, '2nd Position', 1, '2026-04-22 01:58:45'),
(4, '3rd Position', 1, '2026-04-22 01:58:45'),
(5, 'Participant', 1, '2026-04-22 01:58:45');

-- --------------------------------------------------------
-- Table structure for table `admin_audit_logs`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `admin_audit_logs` (

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

-- --------------------------------------------------------
-- Table structure for table `agent_attendance`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `agent_attendance` (

  `id` int(11) NOT NULL,
  `agent_id` int(11) NOT NULL,
  `punch_type` enum('IN','OUT') NOT NULL,
  `punch_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` enum('recorded','verified') DEFAULT 'recorded'

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `agent_salary_ledger`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `agent_salary_ledger` (

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

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `attendance_bonus_history`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `attendance_bonus_history` (

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
-- Table structure for table `attendance_events`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `attendance_events` (

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
-- Table structure for table `attendance_logs`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `attendance_logs` (

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
-- Table structure for table `attendance_monthly_summary`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `attendance_monthly_summary` (

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
-- Table structure for table `attendance_thresholds`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `attendance_thresholds` (

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

-- Dumping data for table `attendance_thresholds`
INSERT INTO `attendance_thresholds` (`id`, `threshold_name`, `percentage_required`, `bonus_points`, `min_eligible_days`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Standard Attendance', 90.00, 25, 10, 1, 1, '2026-06-06 12:15:37', '2026-06-06 12:15:37'),
(2, 'High Attendance', 95.00, 50, 10, 1, 2, '2026-06-06 12:15:37', '2026-06-06 12:15:37'),
(3, 'Perfect Attendance', 100.00, 100, 10, 1, 3, '2026-06-06 12:15:37', '2026-06-06 12:15:37');

-- --------------------------------------------------------
-- Table structure for table `badge_rules`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `badge_rules` (

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

-- Dumping data for table `badge_rules`
INSERT INTO `badge_rules` (`id`, `badge_code`, `badge_rank`, `min_points`, `min_attendance_days`, `min_referrals`, `min_vendor_onboardings`, `min_campaign_approvals`, `min_campaign_points`, `min_donation_count`, `min_donation_amount`, `max_city_rank`, `max_state_rank`, `min_level_rank`, `min_health_campaigns`, `is_active`, `effective_from`, `effective_to`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'RISING_STAR', 1, 100, 7, 0, 0, 2, 25, 0, 0.00, 0, 0, 1, 0, 1, NULL, NULL, 'Early activity: points, attendance, and approved campaigns.', '2026-06-06 12:10:29', '2026-06-06 12:10:29'),
(2, 'COMMUNITY_BUILDER', 2, 250, 10, 5, 0, 3, 75, 0, 0.00, 0, 0, 2, 0, 1, NULL, NULL, 'Referral-led community growth.', '2026-06-06 12:10:29', '2026-06-06 12:10:29'),
(3, 'HEALTH_ADVOCATE', 3, 300, 10, 0, 0, 3, 75, 0, 0.00, 0, 0, 2, 2, 1, NULL, NULL, 'Approved health-related campaign work.', '2026-06-06 12:10:29', '2026-06-06 12:10:29'),
(4, 'FUNDRAISING_CHAMPION', 4, 500, 15, 0, 0, 4, 100, 10, 10000.00, 0, 0, 3, 0, 1, NULL, NULL, 'Verified fundraising performance.', '2026-06-06 12:10:29', '2026-06-06 12:10:29'),
(5, 'CITY_INFLUENCER', 5, 1000, 25, 10, 3, 10, 300, 0, 0.00, 10, 0, 4, 0, 1, NULL, NULL, 'Top city rank and broad campaign impact.', '2026-06-06 12:10:29', '2026-06-06 12:10:29'),
(6, 'LEADERSHIP_ELITE', 6, 2500, 45, 25, 12, 25, 900, 0, 0.00, 0, 20, 6, 0, 1, NULL, NULL, 'State-level leadership performance.', '2026-06-06 12:10:29', '2026-06-06 12:10:29'),
(7, 'DONATION_MILESTONE_CHAMPION', 99, 0, 0, 0, 0, 0, 0, 0, 10000.00, 0, 0, 0, 0, 1, NULL, NULL, 'Automatic badge for facilitating ₹10000+ in donations', '2026-06-06 12:15:37', '2026-06-06 12:15:37');

-- --------------------------------------------------------
-- Table structure for table `bank_accounts`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `bank_accounts` (

  `id` int(11) NOT NULL,
  `bank_name` varchar(255) NOT NULL,
  `account_holder` varchar(255) NOT NULL,
  `account_number` varchar(50) NOT NULL,
  `ifsc_code` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `bank_accounts`
INSERT INTO `bank_accounts` (`id`, `bank_name`, `account_holder`, `account_number`, `ifsc_code`, `is_active`) VALUES
(1, 'State Bank Of India', 'Saarthi Foundation', '1234567899', 'SBIN06564', 1);

-- --------------------------------------------------------
-- Table structure for table `cash_deposits`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `cash_deposits` (

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

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `certificates`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `certificates` (

  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `certificates`
INSERT INTO `certificates` (`id`, `title`, `image_path`, `created_at`) VALUES
(2, 'SPICE_PART_B', 'uploads/certificates/1788162308_SPICE__Part_B_Approval_Letter_AA2293644_page-0001.jpg', '2026-08-31 07:45:08');

-- --------------------------------------------------------
-- Table structure for table `contact_messages`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `contact_messages` (

  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('New','Replied') DEFAULT 'New',
  `created_at` timestamp NULL DEFAULT current_timestamp()

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `course_applications`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `course_applications` (

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

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `crowdfunding_campaigns`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `crowdfunding_campaigns` (

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
-- Table structure for table `donations`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `donations` (

  `id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `donor_name` varchar(100) NOT NULL,
  `donor_email` varchar(100) DEFAULT NULL,
  `donor_mobile` varchar(20) DEFAULT NULL,
  `donor_pan` varchar(20) DEFAULT NULL,
  `donor_address` text DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_gateway` enum('Manual','Razorpay') NOT NULL DEFAULT 'Manual',
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
  `collection_city` varchar(120) DEFAULT NULL,
  `field_agent_id` int(11) DEFAULT NULL,
  `collection_area` varchar(100) DEFAULT NULL,
  `payment_mode_field` enum('cash','online','cheque') DEFAULT 'cash'

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `donation_achievements`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `donation_achievements` (

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
-- Table structure for table `donation_milestones`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `donation_milestones` (

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

-- Dumping data for table `donation_milestones`
INSERT INTO `donation_milestones` (`id`, `milestone_amount`, `reward_type`, `reward_points`, `badge_code`, `milestone_name`, `milestone_description`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 500.00, 'points', 25, NULL, 'Donation Starter', 'First ₹500 collected - Entry to fundraising!', 1, 1, '2026-06-06 12:15:37', '2026-06-06 12:15:37'),
(2, 2000.00, 'points', 75, NULL, 'Donation Enthusiast', 'Reached ₹2000 in donations - Growing impact!', 1, 2, '2026-06-06 12:15:37', '2026-06-06 12:15:37'),
(3, 10000.00, 'both', 200, 'DONATION_MILESTONE_CHAMPION', 'Donation Champion', 'Achieved ₹10000 in donations - Major impact!', 1, 3, '2026-06-06 12:15:37', '2026-06-06 12:15:37');

-- --------------------------------------------------------
-- Table structure for table `events`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `events` (

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

-- Dumping data for table `events`
INSERT INTO `events` (`id`, `title`, `description`, `event_date`, `location`, `registration_open`, `max_registrations`, `gallery_path`, `status`, `created_at`, `created_by`) VALUES
(6, 'Free Community Health And Awareness Camp', 'Chahakta Angan Foundation is organizing a Free Community Health And Awareness Camp to provide basic health check-ups, medical consultations, health education, and wellness awareness for underprivileged families. The event aims to promote preventive healthcare, healthy living, and community well-being through expert guidance and free medical support.', '2026-01-10', 'Jaunpur', 0, NULL, NULL, 'Completed', '2026-08-21 09:05:52', 2);

-- --------------------------------------------------------
-- Table structure for table `event_gallery`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `event_gallery` (

  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `event_registrations`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `event_registrations` (

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
-- Table structure for table `field_agents`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `field_agents` (

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

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `gallery`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `gallery` (

  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `type` enum('image','video') DEFAULT 'image',
  `file_path` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `gallery`
INSERT INTO `gallery` (`id`, `title`, `type`, `file_path`, `created_at`) VALUES
(5, 'Green India', 'image', 'uploads/gallery/1788169527_6a954d371637a_WhatsApp Image 2026-08-26 at 6.24.17 PM.jpeg', '2026-08-31 09:45:27'),
(6, 'Green India', 'image', 'uploads/gallery/1788169564_6a954d5cab0e5_WhatsApp Image 2026-08-26 at 6.23.56 PM.jpeg', '2026-08-31 09:46:04'),
(7, 'Green India', 'image', 'uploads/gallery/1788169586_6a954d72962b5_WhatsApp Image 2026-08-26 at 5.44.55 PM.jpeg', '2026-08-31 09:46:26'),
(8, 'Green India', 'image', 'uploads/gallery/1788169604_6a954d845a29a_WhatsApp Image 2026-08-26 at 5.44.24 PM.jpeg', '2026-08-31 09:46:44'),
(9, 'Eductaion', 'image', 'uploads/gallery/1788169622_6a954d962b16c_WhatsApp Image 2026-08-26 at 5.43.39 PM.jpeg', '2026-08-31 09:47:02'),
(10, 'Envoierment', 'image', 'uploads/gallery/1788169655_6a954db709775_WhatsApp Image 2026-08-26 at 5.46.14 PM.jpeg', '2026-08-31 09:47:35'),
(11, 'Biodiversity Conservation Awareness Programme', 'image', 'uploads/gallery/1788169680_6a954dd092091_WhatsApp Image 2026-08-26 at 5.46.15 PM.jpeg', '2026-08-31 09:48:00');

-- --------------------------------------------------------
-- Table structure for table `health_programs`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `health_programs` (

  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `category` varchar(100) NOT NULL DEFAULT 'Health Awareness',
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `health_programs`
INSERT INTO `health_programs` (`id`, `title`, `description`, `image`, `category`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Free Medical & Diagnostic Camp', 'Comprehensive healthcare camps providing free health checkups, blood tests, sugar tests, and consultations with qualified doctors for the general public.', 'https://images.unsplash.com/photo-1576091160550-2173dba999ef?q=80&w=600&auto=format&fit=crop', 'Medical Camp', 'Active', '2026-08-21 07:57:26', '2026-08-21 07:57:26'),
(2, 'Mental Health & Stress Seminars', 'Interactive mental wellbeing and stress management sessions held in local colleges and communities to eliminate stigma and teach positive coping tools.', 'https://images.unsplash.com/photo-1582213782179-e0d53f98f2ca?q=80&w=600&auto=format&fit=crop', 'Mental Health', 'Active', '2026-08-21 07:57:26', '2026-08-21 07:57:26'),
(3, 'Women Hygiene & Health Drive', 'Special awareness campaigns focused on women health, nutrition, sanitisation practices, and distribution of wellness kits to underprivileged areas.', 'https://images.unsplash.com/photo-1559839734-2b71ea197ec2?q=80&w=600&auto=format&fit=crop', 'Women Wellness', 'Active', '2026-08-21 07:57:26', '2026-08-21 07:57:26');

-- --------------------------------------------------------
-- Table structure for table `inquiries`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `inquiries` (

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

-- --------------------------------------------------------
-- Table structure for table `management_body`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `management_body` (

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

-- Dumping data for table `management_body`
INSERT INTO `management_body` (`id`, `name`, `designation`, `department`, `phone`, `email`, `bio`, `photo`, `fb_url`, `linkedin_url`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(8, 'Velnix Soft', 'President', '', '', 'velnixsoft@gmail.com', '', 'uploads/management/mgmt_1788343249_22cd215a.png', '', '', 0, 1, '2026-09-02 10:00:49', '2026-09-02 10:00:49');

-- --------------------------------------------------------
-- Table structure for table `members`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `members` (

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

-- --------------------------------------------------------
-- Table structure for table `member_designations`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `member_designations` (

  `id` int(11) NOT NULL,
  `title` varchar(120) NOT NULL,
  `fee_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `member_designations`
INSERT INTO `member_designations` (`id`, `title`, `fee_amount`, `is_active`, `created_at`) VALUES
(1, 'Founder Member', 25000.00, 1, '2026-03-16 09:09:45'),
(2, 'Executive Member', 5000.00, 1, '2026-03-16 16:26:35'),
(3, 'General Member', 1000.00, 1, '2026-04-26 18:04:20');

-- --------------------------------------------------------
-- Table structure for table `member_documents`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `member_documents` (

  `id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `doc_type` enum('id_card','appointment_letter','membership_certificate','achievement_certificate') NOT NULL,
  `doc_no` varchar(80) DEFAULT NULL,
  `issued_at` timestamp NULL DEFAULT current_timestamp(),
  `issued_by` int(11) DEFAULT NULL,
  `meta_json` text DEFAULT NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `member_messages`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `member_messages` (

  `id` int(11) NOT NULL,
  `member_id` int(11) DEFAULT NULL,
  `subject` varchar(200) NOT NULL,
  `message_body` text NOT NULL,
  `message_type` enum('manual','birthday') NOT NULL DEFAULT 'manual',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `member_message_deliveries`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `member_message_deliveries` (

  `id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `email_status` enum('Pending','Sent','Failed') NOT NULL DEFAULT 'Pending',
  `dashboard_status` enum('Unread','Read') NOT NULL DEFAULT 'Unread',
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `ngo_documents`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `ngo_documents` (

  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `category` enum('certificate','annual_report','other') DEFAULT 'other',
  `file_path` varchar(255) NOT NULL,
  `is_public` tinyint(1) DEFAULT 0,
  `upload_date` timestamp NULL DEFAULT current_timestamp()

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `notification_preferences`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `notification_preferences` (

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
-- Table structure for table `notification_templates`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `notification_templates` (

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

-- Dumping data for table `notification_templates`
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
-- Table structure for table `payment_qrs`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `payment_qrs` (

  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `qr_image_path` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `payment_qrs`
INSERT INTO `payment_qrs` (`id`, `title`, `qr_image_path`, `is_active`) VALUES
(1, 'UPI', 'uploads/qrs/1773984670_2_1767606718_695b89be1c478.jpg', 1);

-- --------------------------------------------------------
-- Table structure for table `posts`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `posts` (

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

-- Dumping data for table `posts`
INSERT INTO `posts` (`id`, `title`, `slug`, `content`, `cover_path`, `status`, `published_at`, `created_at`, `created_by`, `updated_at`) VALUES
(2, 'School Chalo Abhiyan: Encouraging Children Towards Education', 'school-chalo-abhiyan', 'Education is one of the key areas of work for Jaysmrutti Foundation. Through the School Chalo Abhiyan, the Foundation aims to encourage children to attend school regularly and understand the importance of education.\r\n\r\nThe initiative also seeks to create awareness among families and communities so that every child gets the opportunity to learn, grow and build a better future.', 'uploads/news/news_1788169247_a1ba7bc42f6b.jpeg', 'Published', '2026-08-31 09:38:45', '2026-08-31 09:38:45', 2, '2026-08-31 09:42:00'),
(3, 'Jaysmrutti Foundation Organizes Green India Campaign', 'jaysmrutti-foundation-organizes-green-india-campaign', 'Jaysmrutti Foundation continues its commitment towards environmental protection through the Green India Campaign. The initiative focuses on tree plantation, environmental awareness and encouraging communities to take responsibility for protecting nature.\r\n\r\nThrough active participation of children, youth and community members, the campaign promotes the importance of trees and a greener environment for future generations.', 'uploads/news/news_1788169428_eb54caa60df9.jpeg', 'Published', '2026-08-31 09:44:00', '2026-08-31 09:43:48', 2, '2026-08-31 09:44:01');

-- --------------------------------------------------------
-- Table structure for table `programs`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `programs` (

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

-- Dumping data for table `programs`
INSERT INTO `programs` (`id`, `title`, `slug`, `icon`, `color_class`, `description`, `content`, `impact_text`, `image_path`, `banner_image`, `priority_order`, `is_active`, `created_at`, `updated_at`) VALUES
(6, 'Livelihood & Agriculture', 'livelihood-agriculture', '🌾', 'cat-edu', 'Farmer support programs, modern agri-techniques, organic farming training, cooperative formation, and market access for sustainable rural income.', '<h3>Sustainable Livelihoods for Rural India</h3>\r\n<p>Agriculture remains the primary source of income for millions. Our Livelihood & Agriculture program supports farmers with modern techniques, resources, and market access.</p>\r\n\r\n<h4>What We Offer:</h4>\r\n<ul>\r\n<li><strong>Organic Farming Training:</strong> Chemical-free farming methods, composting, and bio-pesticides.</li>\r\n<li><strong>Seed Distribution:</strong> High-yield and drought-resistant seed varieties at subsidized rates.</li>\r\n<li><strong>Drip Irrigation Systems:</strong> Water-efficient irrigation for water-scarce regions.</li>\r\n<li><strong>Farmer Cooperatives:</strong> Collective bargaining for better prices and bulk procurement of inputs.</li>\r\n<li><strong>Dairy & Poultry Support:</strong> Livestock management, veterinary camps, and fodder supply.</li>\r\n<li><strong>Market Linkages:</strong> Direct connection to buyers, mandis, and online agricultural marketplaces.</li>\r\n</ul>\r\n\r\n<h4>Impact:</h4>\r\n<p><strong>890+ farming families supported</strong>. Average income increase of 35% within the first year of program adoption.</p>\r\n\r\n<h4>Success Case:</h4>\r\n<p>Farmer <strong>Ramesh Singh</strong> switched to organic vegetable farming with our support. He now supplies to urban organic stores and earns ₹25,000 monthly — double his previous income.</p>', '890+ farming families', 'uploads/programs/livelihood-main.jpg', 'uploads/programs/livelihood-banner.jpg', 6, 1, '2026-03-30 02:22:25', '2026-03-30 03:46:12');

-- --------------------------------------------------------
-- Table structure for table `program_features`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `program_features` (

  `id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `feature_text` varchar(500) NOT NULL,
  `icon` varchar(50) DEFAULT '✓',
  `display_order` int(11) DEFAULT 0

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `program_features`
INSERT INTO `program_features` (`id`, `program_id`, `feature_text`, `icon`, `display_order`) VALUES
(21, 6, 'Organic farming techniques and training', '🌱', 1),
(22, 6, 'High-yield seed distribution programs', '🌾', 2),
(23, 6, 'Drip irrigation for water conservation', '💧', 3),
(24, 6, 'Direct market access and fair pricing', '🏪', 4);

-- --------------------------------------------------------
-- Table structure for table `program_gallery`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `program_gallery` (

  `id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `image_path` varchar(500) NOT NULL,
  `caption` varchar(255) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `projects`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `projects` (

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

-- Dumping data for table `projects`
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
-- Table structure for table `project_gallery`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `project_gallery` (

  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `promotion_rules`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `promotion_rules` (

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

-- Dumping data for table `promotion_rules`
INSERT INTO `promotion_rules` (`id`, `level_rank`, `level_name`, `min_points`, `min_attendance_days`, `min_attendance_rate`, `min_referrals`, `min_vendor_onboardings`, `min_campaign_approvals`, `min_campaign_points`, `demotion_grace_days`, `is_active`, `effective_from`, `effective_to`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 'Community Intern', 50, 5, 50.00, 1, 0, 1, 10, 14, 1, NULL, NULL, 'Starter operating level.', '2026-06-06 12:15:38', '2026-06-06 12:15:38'),
(2, 2, 'Senior Intern', 150, 12, 60.00, 3, 1, 3, 50, 14, 1, NULL, NULL, 'Consistent activity and first vendor outcome.', '2026-06-06 12:15:38', '2026-06-06 12:15:38'),
(3, 3, 'Campus Ambassador', 400, 20, 70.00, 7, 3, 8, 150, 21, 1, NULL, NULL, 'Campus-ready ambassador performance.', '2026-06-06 12:15:38', '2026-06-06 12:15:38'),
(4, 4, 'Team Leader', 800, 30, 75.00, 12, 5, 15, 350, 21, 1, NULL, NULL, 'Can lead interns or small campaign teams.', '2026-06-06 12:15:38', '2026-06-06 12:15:38'),
(5, 5, 'City Coordinator', 1500, 45, 80.00, 20, 10, 25, 700, 30, 1, NULL, NULL, 'City-level operational responsibility.', '2026-06-06 12:15:38', '2026-06-06 12:15:38'),
(6, 6, 'State Coordinator', 3000, 60, 85.00, 35, 20, 40, 1200, 30, 1, NULL, NULL, 'State-level coordination responsibility.', '2026-06-06 12:15:38', '2026-06-06 12:15:38');

-- --------------------------------------------------------
-- Table structure for table `qr_scan_logs`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `qr_scan_logs` (

  `id` int(11) NOT NULL,
  `qr_token_id` int(11) DEFAULT NULL,
  `member_id` int(11) DEFAULT NULL,
  `scanned_at` datetime NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(64) DEFAULT NULL,
  `device_info` varchar(255) DEFAULT NULL,
  `result_status` enum('verified','inactive','expired','invalid') NOT NULL DEFAULT 'verified',
  `created_at` timestamp NULL DEFAULT current_timestamp()

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `qr_tokens`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `qr_tokens` (

  `id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `token` varchar(128) NOT NULL,
  `document_type` varchar(80) NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  `status` enum('active','expired','revoked') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp()

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `razorpay_webhook_logs`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `razorpay_webhook_logs` (

  `id` int(11) NOT NULL,
  `event_type` varchar(64) NOT NULL,
  `payment_id` varchar(64) DEFAULT NULL,
  `order_id` varchar(64) DEFAULT NULL,
  `payload` longtext DEFAULT NULL,
  `processed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `sa_activity_logs`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `sa_activity_logs` (

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

-- --------------------------------------------------------
-- Table structure for table `sa_announcements`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `sa_announcements` (

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
-- Table structure for table `sa_attendance_logs`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `sa_attendance_logs` (

  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `attendance_type` varchar(30) NOT NULL DEFAULT 'Daily',
  `reference_id` int(11) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Present',
  `attendance_date` date NOT NULL,
  `marked_by_user_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `sa_badges`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `sa_badges` (

  `id` int(11) NOT NULL,
  `badge_code` varchar(80) DEFAULT NULL,
  `badge_name` varchar(120) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `icon_class` varchar(120) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `sa_badges`
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
-- Table structure for table `sa_certificates`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `sa_certificates` (

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
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `sa_events`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `sa_events` (

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
-- Table structure for table `sa_event_registrations`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `sa_event_registrations` (

  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'Registered',
  `registered_at` datetime NOT NULL DEFAULT current_timestamp(),
  `attendance_marked_at` datetime DEFAULT NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `sa_login_logs`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `sa_login_logs` (

  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `login_at` datetime NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `sa_notifications`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `sa_notifications` (

  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `title` varchar(160) NOT NULL,
  `message` text NOT NULL,
  `channel_type` varchar(30) NOT NULL DEFAULT 'Dashboard',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `sa_partners`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `sa_partners` (

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
-- Table structure for table `sa_penalties`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `sa_penalties` (

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
-- Table structure for table `sa_point_rules`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `sa_point_rules` (

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

-- Dumping data for table `sa_point_rules`
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
(28, 1, 'MISCONDUCT', 'Misconduct Penalty', 'penalty', 'misconduct', 'student', 'admin_review', -50, 1.00, NULL, 'none', 0, 1, 0, 1, NULL, NULL, 'Penalty entry for misconduct;

-- --------------------------------------------------------
-- Table structure for table `sa_point_transactions`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `sa_point_transactions` (

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

-- --------------------------------------------------------
-- Table structure for table `sa_programs`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `sa_programs` (

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

-- Dumping data for table `sa_programs`
INSERT INTO `sa_programs` (`id`, `program_code`, `program_name`, `program_type`, `batch_name`, `status`, `start_date`, `end_date`, `description`, `mentor_user_id`, `max_students`, `created_by_user_id`, `updated_by_user_id`, `deleted_by_user_id`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'SA-CORE-2026', 'Student Ambassador Core Program 2026', 'hybrid', 'Batch 2026', 'active', '2026-04-01', '2027-03-31', 'Default seed program for the student ambassador and internship workflow.', NULL, NULL, NULL, NULL, NULL, '2026-06-06 12:15:38', '2026-06-06 12:15:38', NULL);

-- --------------------------------------------------------
-- Table structure for table `sa_referrals`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `sa_referrals` (

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

-- --------------------------------------------------------
-- Table structure for table `sa_students`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `sa_students` (

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
  `level_name` varchar(60) NOT NULL DEFAULT 'Volunteer',
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

-- --------------------------------------------------------
-- Table structure for table `sa_student_badges`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `sa_student_badges` (

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

-- --------------------------------------------------------
-- Table structure for table `sa_student_event_requests`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `sa_student_event_requests` (

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
-- Table structure for table `sa_tasks`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `sa_tasks` (

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
-- Table structure for table `sa_task_submissions`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `sa_task_submissions` (

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
-- Table structure for table `sa_vendor_leads`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `sa_vendor_leads` (

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
-- Table structure for table `settings`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `settings` (

  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `settings`
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('about_desc', '<b>Jaysmrutti Foundation</b> is a non-profit organization committed to building an inclusive, healthy, educated, and self-reliant society. We work to create meaningful opportunities for people from vulnerable and economically weaker sections of society, helping them live with dignity, equality, and hope.\r\n\r\nOur work focuses on <b>education, skill development, healthcare, sanitation, environmental protection, social equality, and the promotion of Indian art and culture.</b> Through community participation and sustainable initiatives, we strive to create positive and lasting social change.\r\n\r\n<b>Jaysmrutti Foundation</b> believes that meaningful development is not only about providing immediate support, but also about empowering people with knowledge, skills, opportunities, and dignity.'),
('about_image', 'uploads/content/about_us_1788162530.jpg'),
('about_mission', 'The mission of <b>Jaysmrutti Foundation</b> is to work continuously and effectively for the welfare and development of different sections of society. Our aim is not only to provide support to those in need, but also to empower individuals to become self-reliant and lead dignified lives.\r\n\r\nWe are committed to:\r\n\r\n<ul>\r\n<li>Promoting <b>education, training, and skill development</b> for better career and employment opportunities.</li>\r\n<li>Creating awareness about <b>healthcare, hygiene, sanitation, nutrition, and disease prevention.</b></li>\r\n<li>Supporting <b>poor, differently abled, elderly, orphaned, widowed, and vulnerable individuals.</b></li>\r\n<li>Promoting <b>environmental protection</b> and responsible use of natural resources.</li>\r\n<li>Encouraging <b>equality, social harmony, and mutual respect</b> among all communities.</li>\r\n<li>Providing <b>scholarships and educational assistance</b> to deserving students from economically weaker backgrounds.</li>\r\n<li>Promoting and preserving <b>Indian art, crafts, traditions, and cottage industries.</b></li>\r\n</ul>'),
('about_title', 'Why We Exist'),
('about_vision', 'The vision of <b>Jaysmrutti Foundation</b> is to build an <b>inclusive, healthy, educated, and self-reliant society</b> where every individual has the right to dignity, equal opportunities, a safe environment, and a better quality of life.\r\n\r\nWe envision a society where <b>children have access to education, youth have opportunities to develop their skills, families can live with dignity, and vulnerable communities receive the support they need.</b>\r\n\r\nThrough education, empowerment, healthcare, environmental responsibility, social equality, and community participation, <b>Jaysmrutti Foundation</b> aims to contribute towards a stronger, compassionate, and sustainable India.'),
('admin_training_videos_json', '[]'),
('advisory_board_json', '{\"title\":\"Join Our Expert Network\",\"intro\":\"We invite qualified professionals to contribute to public wellness through education, consultation, and awareness initiatives.\",\"contribution\":[\"Contribute approximately 8 hours weekly.\",\"Participate in awareness initiatives.\",\"Provide expert guidance.\",\"Support community wellness programs.\",\"Mentor volunteers and interns.\"],\"expertise\":[\"Nutrition & Dietetics\",\"Fitness & Exercise Science\",\"Sports Nutrition\",\"Psychology\",\"Physiotherapy\",\"Preventive Healthcare\",\"Lifestyle Medicine\"]}'),
('ambassador_program_json', '{\"title\":\"Become a wellness advocate in your city.\",\"responsibilities\":[\"Organize awareness activities.\",\"Encourage healthy lifestyle adoption.\",\"Connect citizens with wellness resources.\",\"Support local outreach initiatives.\"],\"recognition\":[\"Ambassador Certificate\",\"Leadership Recognition\",\"Annual Awards\"]}'),
('bank_details', 'Bank Name: XYZ Bank\r\nAC No: 123456789\r\nIFSC: XYZ0001'),
('birthday_cron_key', ''),
('closing_statement', '“A healthier nation is built when communities, experts, businesses, and citizens come together with a shared purpose.” Join Mouli FitLife Foundation and become part of India’s wellness movement.'),
('contact_address', ''),
('contact_email', ''),
('contact_map_iframe', '<iframe\r\n    src=\"https://www.google.com/maps?q=2nd%20Floor,%20Dharma%20Villa,%20Wazidpur%20Tiraha,%20Jaunpur,%20Uttar%20Pradesh%20222002,%20India&output=embed\"\r\n    width=\"100%\"\r\n    height=\"400\"\r\n    style=\"border:0;

-- --------------------------------------------------------
-- Table structure for table `sliders`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `sliders` (

  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `image_path` varchar(255) NOT NULL,
  `priority` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `sliders`
INSERT INTO `sliders` (`id`, `title`, `subtitle`, `image_path`, `priority`, `is_active`) VALUES
(18, 'h2', 'Driving environmental conservation, sustainable livelihoods, and grassroots empowerment across India.', 'uploads/slider/1788161316_ChatGPT Image Aug 31, 2026, 12_58_14 PM.png', 0, 1),
(19, 'h1', 'Driving environmental conservation, sustainable livelihoods, and grassroots empowerment across India.', 'uploads/slider/1788161712_ChatGPT Image Aug 31, 2026, 01_04_47 PM.png', 0, 1);

-- --------------------------------------------------------
-- Table structure for table `sponsors`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `sponsors` (

  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `logo_path` varchar(255) NOT NULL,
  `website_url` varchar(255) DEFAULT NULL,
  `priority` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `templates`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `templates` (

  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `template_name` varchar(150) NOT NULL,
  `template_type` enum('id_card','receipt','membership_certificate','achievement_certificate','appointment_letter','visitor_certificate','volunteer_certificate','student_certificate') NOT NULL,
  `canvas_width` int(11) NOT NULL DEFAULT 800,
  `canvas_height` int(11) NOT NULL DEFAULT 600,
  `background_image` varchar(255) DEFAULT NULL,
  `json_data` longtext NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `templates`
INSERT INTO `templates` (`id`, `user_id`, `template_name`, `template_type`, `canvas_width`, `canvas_height`, `background_image`, `json_data`, `status`, `created_at`, `updated_at`) VALUES
(1, 2, 'visiter', 'achievement_certificate', 1123, 794, 'uploads/templates/template_bg_1776836448_42aee00d77.jpg', '{\"version\":\"5.3.0\",\"objects\":[{\"type\":\"image\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":35.35,\"top\":417.55,\"width\":260,\"height\":160,\"fill\":\"rgb(0,0,0)\",\"stroke\":null,\"strokeWidth\":0,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":0.45,\"scaleY\":0.56,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"cropX\":0,\"cropY\":0,\"placeholderType\":\"photo\",\"src\":\"data:image/svg+xml,
(13, 2, 'Volunteers ID Card', 'id_card', 856, 540, 'uploads/templates/template_bg_1788348016_6d6d7ae373.png', '{\"version\":\"5.3.0\",\"objects\":[{\"type\":\"group\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":33.2,\"top\":198.6,\"width\":170,\"height\":210,\"fill\":\"rgb(0,0,0)\",\"stroke\":null,\"strokeWidth\":0,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1.1,\"scaleY\":1.07,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"placeholderType\":\"photo\",\"placeholderLabel\":\"PHOTO\",\"objects\":[{\"type\":\"rect\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":-86,\"top\":-106,\"width\":170,\"height\":210,\"fill\":\"#f8fafc\",\"stroke\":\"#2563eb\",\"strokeWidth\":2,\"strokeDashArray\":[8,6],\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"rx\":4,\"ry\":4},{\"type\":\"text\",\"version\":\"5.3.0\",\"originX\":\"center\",\"originY\":\"center\",\"left\":-1,\"top\":-1,\"width\":84.9,\"height\":27.12,\"fill\":\"#1d4ed8\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Arial\",\"fontWeight\":\"bold\",\"fontSize\":24,\"text\":\"PHOTO\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\"}]},{\"type\":\"group\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":692,\"top\":21,\"width\":145,\"height\":145,\"fill\":\"rgb(0,0,0)\",\"stroke\":null,\"strokeWidth\":0,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1.09,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"placeholderType\":\"qr\",\"placeholderLabel\":\"QR\",\"objects\":[{\"type\":\"rect\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":-73.5,\"top\":-73.5,\"width\":145,\"height\":145,\"fill\":\"#f8fafc\",\"stroke\":\"#2563eb\",\"strokeWidth\":2,\"strokeDashArray\":[8,6],\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"rx\":4,\"ry\":4},{\"type\":\"text\",\"version\":\"5.3.0\",\"originX\":\"center\",\"originY\":\"center\",\"left\":-1,\"top\":-1,\"width\":33,\"height\":24.86,\"fill\":\"#1d4ed8\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Arial\",\"fontWeight\":\"bold\",\"fontSize\":22,\"text\":\"QR\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\"}]},{\"type\":\"textbox\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":426,\"top\":232,\"width\":260,\"height\":31.64,\"fill\":\"#111827\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Arial\",\"fontWeight\":\"normal\",\"fontSize\":28,\"text\":\"{{full_name}}\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\",\"minWidth\":20,\"splitByGrapheme\":false},{\"type\":\"group\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":652.88,\"top\":332.72,\"width\":220,\"height\":90,\"fill\":\"rgb(0,0,0)\",\"stroke\":null,\"strokeWidth\":0,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":0.81,\"scaleY\":0.81,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"placeholderType\":\"signature\",\"placeholderLabel\":\"SIGNATURE\",\"objects\":[{\"type\":\"rect\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":-111,\"top\":-46,\"width\":220,\"height\":90,\"fill\":\"#f8fafc\",\"stroke\":\"#2563eb\",\"strokeWidth\":2,\"strokeDashArray\":[8,6],\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"rx\":4,\"ry\":4},{\"type\":\"text\",\"version\":\"5.3.0\",\"originX\":\"center\",\"originY\":\"center\",\"left\":-1,\"top\":-1,\"width\":139.56,\"height\":27.12,\"fill\":\"#1d4ed8\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Arial\",\"fontWeight\":\"bold\",\"fontSize\":24,\"text\":\"SIGNATURE\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\"}]},{\"type\":\"textbox\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":425,\"top\":268,\"width\":345.63,\"height\":31.64,\"fill\":\"#111827\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Arial\",\"fontWeight\":\"normal\",\"fontSize\":28,\"text\":\"{{member_no}}\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\",\"minWidth\":20,\"splitByGrapheme\":false},{\"type\":\"textbox\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":424,\"top\":307,\"width\":274.08,\"height\":31.64,\"fill\":\"#111827\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Arial\",\"fontWeight\":\"normal\",\"fontSize\":28,\"text\":\"{{phone}}\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\",\"minWidth\":20,\"splitByGrapheme\":false},{\"type\":\"textbox\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":423,\"top\":343,\"width\":308.37,\"height\":31.64,\"fill\":\"#111827\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Arial\",\"fontWeight\":\"normal\",\"fontSize\":28,\"text\":\"{{blood_group}}\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\",\"minWidth\":20,\"splitByGrapheme\":false},{\"type\":\"textbox\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":424,\"top\":380,\"width\":355.03,\"height\":31.64,\"fill\":\"#111827\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Arial\",\"fontWeight\":\"normal\",\"fontSize\":28,\"text\":\"{{valid_until}}\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\",\"minWidth\":20,\"splitByGrapheme\":false}],\"background\":\"#ffffff\"}', 1, '2026-09-02 11:20:16', '2026-09-05 02:17:27'),
(16, 2, 'Membership Certificate', 'membership_certificate', 1123, 794, 'uploads/templates/template_bg_1788350322_8d6b3856b8.png', '{\"version\":\"5.3.0\",\"objects\":[{\"type\":\"textbox\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":468.79,\"top\":414.83,\"width\":260,\"height\":39.55,\"fill\":\"#111827\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Arial\",\"fontWeight\":\"normal\",\"fontSize\":35,\"text\":\"{{full_name}}\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\",\"minWidth\":20,\"splitByGrapheme\":false},{\"type\":\"group\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":850.48,\"top\":540.4,\"width\":220,\"height\":90,\"fill\":\"rgb(0,0,0)\",\"stroke\":null,\"strokeWidth\":0,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":0.86,\"scaleY\":0.86,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"placeholderType\":\"signature\",\"placeholderLabel\":\"SIGNATURE\",\"objects\":[{\"type\":\"rect\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":-111,\"top\":-46,\"width\":220,\"height\":90,\"fill\":\"#f8fafc\",\"stroke\":\"#2563eb\",\"strokeWidth\":2,\"strokeDashArray\":[8,6],\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"rx\":4,\"ry\":4},{\"type\":\"text\",\"version\":\"5.3.0\",\"originX\":\"center\",\"originY\":\"center\",\"left\":-1,\"top\":-1,\"width\":139.56,\"height\":27.12,\"fill\":\"#1d4ed8\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Arial\",\"fontWeight\":\"bold\",\"fontSize\":24,\"text\":\"SIGNATURE\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\"}]}],\"background\":\"#ffffff\"}', 1, '2026-09-02 11:41:51', '2026-09-02 11:58:42'),
(18, 2, 'Volunteer Certificate', 'volunteer_certificate', 1123, 794, 'uploads/templates/template_bg_1788350058_92767ea2bd.png', '{\"version\":\"5.3.0\",\"objects\":[{\"type\":\"textbox\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":462.8,\"top\":415.83,\"width\":260,\"height\":39.55,\"fill\":\"#111827\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Arial\",\"fontWeight\":\"normal\",\"fontSize\":35,\"text\":\"{{full_name}}\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\",\"minWidth\":20,\"splitByGrapheme\":false},{\"type\":\"group\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":848.23,\"top\":540.84,\"width\":220,\"height\":90,\"fill\":\"rgb(0,0,0)\",\"stroke\":null,\"strokeWidth\":0,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":0.86,\"scaleY\":0.86,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"placeholderType\":\"signature\",\"placeholderLabel\":\"SIGNATURE\",\"objects\":[{\"type\":\"rect\",\"version\":\"5.3.0\",\"originX\":\"left\",\"originY\":\"top\",\"left\":-111,\"top\":-46,\"width\":220,\"height\":90,\"fill\":\"#f8fafc\",\"stroke\":\"#2563eb\",\"strokeWidth\":2,\"strokeDashArray\":[8,6],\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"rx\":4,\"ry\":4},{\"type\":\"text\",\"version\":\"5.3.0\",\"originX\":\"center\",\"originY\":\"center\",\"left\":-1,\"top\":-1,\"width\":139.56,\"height\":27.12,\"fill\":\"#1d4ed8\",\"stroke\":null,\"strokeWidth\":1,\"strokeDashArray\":null,\"strokeLineCap\":\"butt\",\"strokeDashOffset\":0,\"strokeLineJoin\":\"miter\",\"strokeUniform\":false,\"strokeMiterLimit\":4,\"scaleX\":1,\"scaleY\":1,\"angle\":0,\"flipX\":false,\"flipY\":false,\"opacity\":1,\"shadow\":null,\"visible\":true,\"backgroundColor\":\"\",\"fillRule\":\"nonzero\",\"paintFirst\":\"fill\",\"globalCompositeOperation\":\"source-over\",\"skewX\":0,\"skewY\":0,\"fontFamily\":\"Arial\",\"fontWeight\":\"bold\",\"fontSize\":24,\"text\":\"SIGNATURE\",\"underline\":false,\"overline\":false,\"linethrough\":false,\"textAlign\":\"left\",\"fontStyle\":\"normal\",\"lineHeight\":1.16,\"textBackgroundColor\":\"\",\"charSpacing\":0,\"styles\":[],\"direction\":\"ltr\",\"path\":null,\"pathStartOffset\":0,\"pathSide\":\"left\",\"pathAlign\":\"baseline\"}]}],\"background\":\"#ffffff\"}', 1, '2026-09-02 11:53:29', '2026-09-02 11:54:18');

-- --------------------------------------------------------
-- Table structure for table `testimonials`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `testimonials` (

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

-- Dumping data for table `testimonials`
INSERT INTO `testimonials` (`id`, `name`, `role`, `content`, `stars`, `initial`, `avatar_path`, `status`, `priority_order`, `featured`, `created_at`, `updated_at`) VALUES
(9, 'Community Member', 'Community Member', 'Jaysmrutti Foundation के सामाजिक और जागरूकता कार्यक्रमों से समुदाय के लोगों को काफी प्रेरणा मिलती है। संस्था शिक्षा, स्वास्थ्य और पर्यावरण जैसे महत्वपूर्ण विषयों पर लोगों को जोड़कर सकारात्मक बदलाव लाने का प्रयास कर रही है।”', '★★★★★', 'R', NULL, 'Active', 0, 0, '2026-08-31 09:34:08', '2026-08-31 09:34:08'),
(10, 'Volunteer', 'Foundation Volunteer', 'Jaysmrutti Foundation के साथ जुड़कर समाज के लिए काम करने का एक meaningful अनुभव मिला। Green India Campaign और अन्य awareness programmes में लोगों की participation देखकर लगता है कि छोटे-छोटे प्रयास भी बड़े बदलाव की शुरुआत बन सकते हैं।”', '★★★★★', 'V', NULL, 'Active', 1, 0, '2026-08-31 09:35:18', '2026-08-31 09:35:18'),
(11, 'Programme Participant', 'Programme Participant', '“Jaysmrutti Foundation के programmes लोगों को केवल सहायता देने तक सीमित नहीं हैं, बल्कि उन्हें जागरूक और आत्मनिर्भर बनाने की दिशा में भी काम करते हैं। संस्था की शिक्षा, स्वास्थ्य, स्वच्छता और पर्यावरण से जुड़ी पहलें समाज के लिए उपयोगी हैं।”', '★★★★★', 'P', NULL, 'Active', 2, 0, '2026-08-31 09:35:57', '2026-08-31 09:35:57');

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `users` (

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

-- Dumping data for table `users`
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `status`, `created_at`, `coordinator_code`, `hierarchy_level`, `state`, `area`, `target_amount_monthly`) VALUES
(1, 'Super Admin', 'admin@ngo.com', '$2y$10$kkhA5RXyhrvOryG70U7ixu/IeADOrPWhT7vgXFClGDI1vZU8hSVf2', 'Admin', 1, '2025-12-02 13:14:05', 'COORD-00001', '', NULL, NULL, 0.00),
(2, 'Seed Council', 'seedcouncil.org@gmail.com', '$2y$10$wRJvvI7PpJq1onC9S65iwe/od0N3ouHSruuXFtlKVj3DTgtiZEF3q', 'Super Admin', 1, '2026-04-24 17:32:54', 'COORD-00002', '', NULL, NULL, 0.00);

-- --------------------------------------------------------
-- Table structure for table `user_permissions`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `user_permissions` (

  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `permission_key` varchar(120) NOT NULL,
  `is_allowed` tinyint(1) NOT NULL DEFAULT 1,
  `granted_by` int(11) DEFAULT NULL,
  `granted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `user_permissions`
INSERT INTO `user_permissions` (`id`, `user_id`, `permission_key`, `is_allowed`, `granted_by`, `granted_at`, `updated_at`) VALUES
(1, 2, 'page.dashboard', 1, 1, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(2, 2, 'page.coordinator_reports', 1, 1, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(3, 2, 'page.access_control', 1, 1, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(4, 2, 'page.user_security', 1, 1, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(5, 2, 'page.memberships', 1, 1, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(6, 2, 'page.member_messages', 1, 1, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(7, 2, 'page.member_documents', 1, 1, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(8, 2, 'page.visitor_certificates', 1, 1, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(9, 2, 'page.inquiries', 1, 1, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(10, 2, 'page.volunteers', 1, 1, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(11, 2, 'page.volunteer_activities', 1, 1, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(12, 2, 'page.manager_panel', 1, 1, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(13, 2, 'page.projects', 1, 1, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(14, 2, 'page.events', 1, 1, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(15, 2, 'page.donations', 1, 1, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(16, 2, 'page.documents', 1, 1, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(17, 2, 'page.management_body', 1, 1, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(18, 2, 'page.donation_analytics', 1, 1, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(19, 2, 'page.crowdfunding', 1, 1, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(20, 2, 'page.page_manager', 1, 1, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(21, 2, 'page.slider_manager', 1, 1, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(22, 2, 'page.sponsors', 1, 1, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(23, 2, 'page.about_manager', 1, 1, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(24, 2, 'page.objectives_manager', 1, 1, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(25, 2, 'page.awards_manager', 1, 1, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(26, 2, 'page.gallery_manager', 1, 1, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(27, 2, 'page.contact_manager', 1, 1, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(28, 2, 'page.certificate_manager', 1, 1, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(29, 2, 'page.privacy_manager', 1, 1, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(30, 2, 'page.terms_manager', 1, 1, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(31, 2, 'page.refund_manager', 1, 1, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(32, 2, 'page.training_videos', 1, 1, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(33, 2, 'page.news', 1, 1, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(34, 2, 'page.reports', 1, 1, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(35, 2, 'page.system_info', 1, 1, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(36, 2, 'page.settings', 1, 1, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(37, 2, 'page.backup_db', 1, 1, '2026-04-24 17:35:31', '2026-04-24 17:35:31'),
(38, 1, 'page.dashboard', 1, 1, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(39, 1, 'page.coordinator_reports', 1, 1, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(40, 1, 'page.access_control', 1, 1, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(41, 1, 'page.user_security', 1, 1, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(42, 1, 'page.memberships', 1, 1, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(43, 1, 'page.member_messages', 1, 1, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(44, 1, 'page.member_documents', 1, 1, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(45, 1, 'page.visitor_certificates', 1, 1, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(46, 1, 'page.inquiries', 1, 1, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(47, 1, 'page.volunteers', 1, 1, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(48, 1, 'page.volunteer_activities', 1, 1, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(49, 1, 'page.manager_panel', 1, 1, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(50, 1, 'page.projects', 1, 1, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(51, 1, 'page.events', 1, 1, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(52, 1, 'page.donations', 1, 1, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(53, 1, 'page.documents', 1, 1, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(54, 1, 'page.management_body', 1, 1, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(55, 1, 'page.donation_analytics', 1, 1, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(56, 1, 'page.crowdfunding', 1, 1, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(57, 1, 'page.page_manager', 1, 1, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(58, 1, 'page.slider_manager', 1, 1, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(59, 1, 'page.sponsors', 1, 1, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(60, 1, 'page.about_manager', 1, 1, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(61, 1, 'page.objectives_manager', 1, 1, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(62, 1, 'page.awards_manager', 1, 1, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(63, 1, 'page.gallery_manager', 1, 1, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(64, 1, 'page.contact_manager', 1, 1, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(65, 1, 'page.certificate_manager', 1, 1, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(66, 1, 'page.privacy_manager', 1, 1, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(67, 1, 'page.terms_manager', 1, 1, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(68, 1, 'page.refund_manager', 1, 1, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(69, 1, 'page.training_videos', 1, 1, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(70, 1, 'page.news', 1, 1, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(71, 1, 'page.reports', 1, 1, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(72, 1, 'page.system_info', 1, 1, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(73, 1, 'page.settings', 1, 1, '2026-04-28 06:44:27', '2026-04-28 06:44:27'),
(74, 1, 'page.backup_db', 1, 1, '2026-04-28 06:44:27', '2026-04-28 06:44:27');

-- --------------------------------------------------------
-- Table structure for table `visitor_certificates`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `visitor_certificates` (

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
-- Table structure for table `volunteers`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `volunteers` (

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

-- --------------------------------------------------------
-- Table structure for table `volunteer_activities`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `volunteer_activities` (

  `id` int(11) NOT NULL,
  `volunteer_id` int(11) NOT NULL,
  `activity_type` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `event_id` int(11) DEFAULT NULL,
  `hours_spent` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- Table structure for table `recurring_donations`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `recurring_donations` (
  `id` int(11) NOT NULL,
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
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `recurring_donation_transactions`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `recurring_donation_transactions` (
  `id` int(11) NOT NULL,
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
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- Indexes for Tables
-- ========================================================

-- Indexes for table `achievement_positions`
ALTER TABLE `achievement_positions`
ADD PRIMARY KEY (`id`);

-- Indexes for table `admin_audit_logs`
ALTER TABLE `admin_audit_logs`
ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin_audit_user` (`user_id`,`created_at`),
  ADD KEY `idx_admin_audit_entity` (`entity_type`,`entity_id`),
  ADD KEY `idx_admin_audit_action` (`action`,`created_at`);

-- Indexes for table `agent_attendance`
ALTER TABLE `agent_attendance`
ADD PRIMARY KEY (`id`),
  ADD KEY `idx_attendance_agent_time` (`agent_id`,`punch_time`),
  ADD KEY `idx_attendance_type` (`punch_type`);

-- Indexes for table `agent_salary_ledger`
ALTER TABLE `agent_salary_ledger`
ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_agent_month` (`agent_id`,`month_key`),
  ADD KEY `idx_salary_month` (`month_key`);

-- Indexes for table `attendance_bonus_history`
ALTER TABLE `attendance_bonus_history`
ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_bonus_history` (`student_id`,`year`,`month`),
  ADD KEY `idx_bonus_student` (`student_id`),
  ADD KEY `idx_bonus_yearmonth` (`year`,`month`),
  ADD KEY `idx_bonus_threshold_met` (`threshold_met`),
  ADD KEY `idx_bonus_awarded` (`awarded_at`),
  ADD KEY `idx_bonus_points` (`bonus_points_awarded`),
  ADD KEY `reference_threshold_id` (`reference_threshold_id`);

-- Indexes for table `attendance_events`
ALTER TABLE `attendance_events`
ADD PRIMARY KEY (`id`),
  ADD KEY `idx_attendance_events_event` (`event_id`),
  ADD KEY `idx_attendance_events_status_date` (`status`,`event_date`),
  ADD KEY `fk_attendance_events_user` (`created_by`);

-- Indexes for table `attendance_logs`
ALTER TABLE `attendance_logs`
ADD PRIMARY KEY (`id`),
  ADD KEY `idx_attendance_logs_event_member` (`event_id`,`member_id`),
  ADD KEY `idx_attendance_logs_scan_time` (`scan_time`),
  ADD KEY `fk_attendance_logs_member` (`member_id`),
  ADD KEY `idx_attendance_logs_event_volunteer` (`event_id`,`volunteer_id`),
  ADD KEY `fk_attendance_logs_volunteer` (`volunteer_id`);

-- Indexes for table `attendance_monthly_summary`
ALTER TABLE `attendance_monthly_summary`
ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_student_month` (`student_id`,`year`,`month`),
  ADD KEY `idx_monthly_student` (`student_id`),
  ADD KEY `idx_monthly_yearmonth` (`year`,`month`),
  ADD KEY `idx_monthly_percentage` (`attendance_percentage`),
  ADD KEY `idx_monthly_processed` (`is_processed`),
  ADD KEY `idx_monthly_bonus` (`attendance_bonus_awarded`);

-- Indexes for table `attendance_thresholds`
ALTER TABLE `attendance_thresholds`
ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_threshold_percentage` (`percentage_required`),
  ADD KEY `idx_threshold_active` (`is_active`),
  ADD KEY `idx_threshold_sort` (`sort_order`);

-- Indexes for table `badge_rules`
ALTER TABLE `badge_rules`
ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_badge_rules_code` (`badge_code`),
  ADD KEY `idx_badge_rules_active` (`is_active`,`effective_from`,`effective_to`),
  ADD KEY `idx_badge_rules_rank` (`badge_rank`);

-- Indexes for table `bank_accounts`
ALTER TABLE `bank_accounts`
ADD PRIMARY KEY (`id`);

-- Indexes for table `cash_deposits`
ALTER TABLE `cash_deposits`
ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cash_status` (`status`),
  ADD KEY `idx_cash_deadline` (`deposit_deadline`),
  ADD KEY `donation_id` (`donation_id`),
  ADD KEY `agent_id` (`agent_id`);

-- Indexes for table `certificates`
ALTER TABLE `certificates`
ADD PRIMARY KEY (`id`);

-- Indexes for table `contact_messages`
ALTER TABLE `contact_messages`
ADD PRIMARY KEY (`id`);

-- Indexes for table `course_applications`
ALTER TABLE `course_applications`
ADD PRIMARY KEY (`id`);

-- Indexes for table `crowdfunding_campaigns`
ALTER TABLE `crowdfunding_campaigns`
ADD PRIMARY KEY (`id`);

-- Indexes for table `donations`
ALTER TABLE `donations`
ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `receipt_no` (`receipt_no`),
  ADD KEY `project_id_fk` (`project_id`),
  ADD KEY `idx_donations_verified_by` (`verified_by`),
  ADD KEY `idx_donations_achievement` (`sa_student_id`,`achievement_processed`);

-- Indexes for table `donation_achievements`
ALTER TABLE `donation_achievements`
ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_student_milestone` (`student_id`,`milestone_id`),
  ADD KEY `idx_achievement_student` (`student_id`),
  ADD KEY `idx_achievement_milestone` (`milestone_id`),
  ADD KEY `idx_achievement_donation` (`reference_donation_id`),
  ADD KEY `idx_achievement_timestamp` (`achieved_at`),
  ADD KEY `idx_achievement_processed` (`processed_by_cron`);

-- Indexes for table `donation_milestones`
ALTER TABLE `donation_milestones`
ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_milestone_amount` (`milestone_amount`),
  ADD KEY `idx_milestones_active` (`is_active`),
  ADD KEY `idx_milestones_sort` (`sort_order`);

-- Indexes for table `events`
ALTER TABLE `events`
ADD PRIMARY KEY (`id`),
  ADD KEY `idx_events_date` (`event_date`);

-- Indexes for table `event_gallery`
ALTER TABLE `event_gallery`
ADD PRIMARY KEY (`id`),
  ADD KEY `idx_event_gallery_event` (`event_id`);

-- Indexes for table `event_registrations`
ALTER TABLE `event_registrations`
ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_event_regs_event_email` (`event_id`,`email`),
  ADD KEY `idx_event_regs_event` (`event_id`),
  ADD KEY `idx_event_regs_email` (`email`),
  ADD KEY `idx_event_reg_sa_student` (`sa_student_id`,`event_id`);

-- Indexes for table `field_agents`
ALTER TABLE `field_agents`
ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_agent_monthly` (`collected_amount_monthly`);

-- Indexes for table `gallery`
ALTER TABLE `gallery`
ADD PRIMARY KEY (`id`);

-- Indexes for table `health_programs`
ALTER TABLE `health_programs`
ADD PRIMARY KEY (`id`);

-- Indexes for table `inquiries`
ALTER TABLE `inquiries`
ADD PRIMARY KEY (`id`),
  ADD KEY `idx_inquiries_status` (`status`);

-- Indexes for table `management_body`
ALTER TABLE `management_body`
ADD PRIMARY KEY (`id`),
  ADD KEY `idx_active_order` (`is_active`,`sort_order`);

-- Indexes for table `members`
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

-- Indexes for table `member_designations`
ALTER TABLE `member_designations`
ADD PRIMARY KEY (`id`);

-- Indexes for table `member_documents`
ALTER TABLE `member_documents`
ADD PRIMARY KEY (`id`),
  ADD KEY `idx_member_docs_member` (`member_id`),
  ADD KEY `idx_member_docs_type` (`doc_type`);

-- Indexes for table `member_messages`
ALTER TABLE `member_messages`
ADD PRIMARY KEY (`id`),
  ADD KEY `idx_member_messages_member` (`member_id`);

-- Indexes for table `member_message_deliveries`
ALTER TABLE `member_message_deliveries`
ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mmd_message` (`message_id`),
  ADD KEY `idx_mmd_member` (`member_id`);

-- Indexes for table `ngo_documents`
ALTER TABLE `ngo_documents`
ADD PRIMARY KEY (`id`);

-- Indexes for table `notification_preferences`
ALTER TABLE `notification_preferences`
ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_student_pref` (`student_id`),
  ADD KEY `idx_dashboard_enabled` (`dashboard_notifications_enabled`);

-- Indexes for table `notification_templates`
ALTER TABLE `notification_templates`
ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_notification_type` (`notification_type`),
  ADD KEY `idx_enabled` (`is_enabled`);

-- Indexes for table `payment_qrs`
ALTER TABLE `payment_qrs`
ADD PRIMARY KEY (`id`);

-- Indexes for table `posts`
ALTER TABLE `posts`
ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_posts_slug` (`slug`),
  ADD KEY `idx_posts_status_published` (`status`,`published_at`);

-- Indexes for table `programs`
ALTER TABLE `programs`
ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_priority` (`priority_order`);

-- Indexes for table `program_features`
ALTER TABLE `program_features`
ADD PRIMARY KEY (`id`),
  ADD KEY `idx_program` (`program_id`);

-- Indexes for table `program_gallery`
ALTER TABLE `program_gallery`
ADD PRIMARY KEY (`id`),
  ADD KEY `idx_program` (`program_id`);

-- Indexes for table `projects`
ALTER TABLE `projects`
ADD PRIMARY KEY (`id`);

-- Indexes for table `project_gallery`
ALTER TABLE `project_gallery`
ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`);

-- Indexes for table `promotion_rules`
ALTER TABLE `promotion_rules`
ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_promotion_rules_level` (`level_name`),
  ADD UNIQUE KEY `uniq_promotion_rules_rank` (`level_rank`),
  ADD KEY `idx_promotion_rules_active` (`is_active`,`effective_from`,`effective_to`);

-- Indexes for table `qr_scan_logs`
ALTER TABLE `qr_scan_logs`
ADD PRIMARY KEY (`id`),
  ADD KEY `idx_qr_scan_logs_token` (`qr_token_id`),
  ADD KEY `idx_qr_scan_logs_member` (`member_id`),
  ADD KEY `idx_qr_scan_logs_scanned_at` (`scanned_at`);

-- Indexes for table `qr_tokens`
ALTER TABLE `qr_tokens`
ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_qr_tokens_token` (`token`),
  ADD KEY `idx_qr_tokens_member_doc` (`member_id`,`document_type`,`status`),
  ADD KEY `idx_qr_tokens_expires_at` (`expires_at`);

-- Indexes for table `razorpay_webhook_logs`
ALTER TABLE `razorpay_webhook_logs`
ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_razorpay_webhook_payment` (`payment_id`);

-- Indexes for table `sa_activity_logs`
ALTER TABLE `sa_activity_logs`
ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sa_activity_student` (`student_id`),
  ADD KEY `idx_sa_activity_date` (`created_at`);

-- Indexes for table `sa_announcements`
ALTER TABLE `sa_announcements`
ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sa_announcements_active` (`is_active`,`created_at`);

-- Indexes for table `sa_attendance_logs`
ALTER TABLE `sa_attendance_logs`
ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sa_attendance_student` (`student_id`),
  ADD KEY `idx_sa_attendance_date` (`attendance_date`);

-- Indexes for table `sa_badges`
ALTER TABLE `sa_badges`
ADD PRIMARY KEY (`id`);

-- Indexes for table `sa_certificates`
ALTER TABLE `sa_certificates`
ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sa_certificate_no` (`certificate_no`),
  ADD KEY `idx_sa_certificates_student_issued` (`student_id`,`issued_at`);

-- Indexes for table `sa_events`
ALTER TABLE `sa_events`
ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sa_events_status` (`status`);

-- Indexes for table `sa_event_registrations`
ALTER TABLE `sa_event_registrations`
ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sa_event_student` (`event_id`,`student_id`),
  ADD KEY `idx_sa_event_reg_student` (`student_id`);

-- Indexes for table `sa_login_logs`
ALTER TABLE `sa_login_logs`
ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sa_login_student` (`student_id`),
  ADD KEY `idx_sa_login_date` (`login_at`);

-- Indexes for table `sa_notifications`
ALTER TABLE `sa_notifications`
ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sa_notification_student` (`student_id`);

-- Indexes for table `sa_partners`
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

-- Indexes for table `sa_penalties`
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

-- Indexes for table `sa_point_rules`
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

-- Indexes for table `sa_point_transactions`
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

-- Indexes for table `sa_programs`
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

-- Indexes for table `sa_referrals`
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

-- Indexes for table `sa_students`
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

-- Indexes for table `sa_student_badges`
ALTER TABLE `sa_student_badges`
ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sa_student_badge` (`student_id`,`badge_id`);

-- Indexes for table `sa_student_event_requests`
ALTER TABLE `sa_student_event_requests`
ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sa_event_req_student` (`student_id`,`status`),
  ADD KEY `idx_sa_event_req_status` (`status`,`created_at`);

-- Indexes for table `sa_tasks`
ALTER TABLE `sa_tasks`
ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sa_task_status` (`status`);

-- Indexes for table `sa_task_submissions`
ALTER TABLE `sa_task_submissions`
ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sa_task_submission` (`task_id`,`student_id`),
  ADD KEY `idx_sa_task_submission_status` (`status`);

-- Indexes for table `sa_vendor_leads`
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

-- Indexes for table `settings`
ALTER TABLE `settings`
ADD PRIMARY KEY (`setting_key`);

-- Indexes for table `sliders`
ALTER TABLE `sliders`
ADD PRIMARY KEY (`id`);

-- Indexes for table `sponsors`
ALTER TABLE `sponsors`
ADD PRIMARY KEY (`id`);

-- Indexes for table `templates`
ALTER TABLE `templates`
ADD PRIMARY KEY (`id`),
  ADD KEY `idx_templates_type_status` (`template_type`,`status`),
  ADD KEY `idx_templates_user` (`user_id`);

-- Indexes for table `testimonials`
ALTER TABLE `testimonials`
ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status_priority` (`status`,`priority_order`),
  ADD KEY `idx_featured` (`featured`);

-- Indexes for table `users`
ALTER TABLE `users`
ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `uq_users_coordinator_code` (`coordinator_code`),
  ADD KEY `idx_users_hierarchy` (`hierarchy_level`),
  ADD KEY `idx_users_state` (`state`),
  ADD KEY `idx_users_area` (`area`);

-- Indexes for table `user_permissions`
ALTER TABLE `user_permissions`
ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user_permission` (`user_id`,`permission_key`),
  ADD KEY `idx_permission_key` (`permission_key`),
  ADD KEY `idx_granted_by` (`granted_by`);

-- Indexes for table `visitor_certificates`
ALTER TABLE `visitor_certificates`
ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_visitor_certificate_no` (`certificate_no`);

-- Indexes for table `volunteers`
ALTER TABLE `volunteers`
ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `id_card_no` (`id_card_no`),
  ADD KEY `idx_volunteers_approved_by` (`approved_by`);

-- Indexes for table `volunteer_activities`
ALTER TABLE `volunteer_activities`
ADD PRIMARY KEY (`id`),
  ADD KEY `volunteer_id` (`volunteer_id`),
  ADD KEY `event_id` (`event_id`);


-- Indexes for table `recurring_donations`
ALTER TABLE `recurring_donations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_rec_subscription_id` (`razorpay_subscription_id`),
  ADD KEY `idx_rec_donor_email` (`donor_email`),
  ADD KEY `idx_rec_donor_mobile` (`donor_mobile`),
  ADD KEY `idx_rec_status_next_charge` (`status`, `next_charge_date`),
  ADD KEY `idx_rec_project_id` (`project_id`),
  ADD KEY `idx_rec_sa_student` (`sa_student_id`),
  ADD KEY `idx_rec_field_agent` (`field_agent_id`);

-- Indexes for table `recurring_donation_transactions`
ALTER TABLE `recurring_donation_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rec_tx_parent` (`recurring_donation_id`),
  ADD KEY `idx_rec_tx_donation` (`donation_id`),
  ADD KEY `idx_rec_tx_status_date` (`status`, `charge_date`),
  ADD KEY `idx_rec_tx_payment_id` (`razorpay_payment_id`),
  ADD KEY `idx_rec_tx_subscription_id` (`razorpay_subscription_id`);

-- ========================================================
-- AUTO_INCREMENT for Tables
-- ========================================================

ALTER TABLE `achievement_positions` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
ALTER TABLE `admin_audit_logs` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `agent_attendance` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `agent_salary_ledger` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `attendance_bonus_history` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `attendance_events` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `attendance_logs` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `attendance_monthly_summary` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `attendance_thresholds` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
ALTER TABLE `badge_rules` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
ALTER TABLE `bank_accounts` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
ALTER TABLE `cash_deposits` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `certificates` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
ALTER TABLE `contact_messages` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `course_applications` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `crowdfunding_campaigns` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `donations` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `donation_achievements` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `donation_milestones` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
ALTER TABLE `events` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
ALTER TABLE `event_gallery` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `event_registrations` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `field_agents` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `gallery` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
ALTER TABLE `health_programs` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
ALTER TABLE `inquiries` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `management_body` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
ALTER TABLE `members` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `member_designations` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
ALTER TABLE `member_documents` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `member_messages` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `member_message_deliveries` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `ngo_documents` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `notification_preferences` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `notification_templates` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;
ALTER TABLE `payment_qrs` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
ALTER TABLE `posts` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
ALTER TABLE `programs` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
ALTER TABLE `program_features` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;
ALTER TABLE `program_gallery` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `projects` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;
ALTER TABLE `project_gallery` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `promotion_rules` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
ALTER TABLE `qr_scan_logs` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `qr_tokens` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `razorpay_webhook_logs` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `sa_activity_logs` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `sa_announcements` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `sa_attendance_logs` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `sa_badges` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
ALTER TABLE `sa_certificates` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `sa_events` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `sa_event_registrations` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `sa_login_logs` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `sa_notifications` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `sa_partners` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `sa_penalties` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `sa_point_rules` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;
ALTER TABLE `sa_point_transactions` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `sa_programs` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
ALTER TABLE `sa_referrals` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `sa_students` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `sa_student_badges` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `sa_student_event_requests` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `sa_tasks` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `sa_task_submissions` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `sa_vendor_leads` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `settings` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `sliders` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;
ALTER TABLE `sponsors` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `templates` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;
ALTER TABLE `testimonials` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
ALTER TABLE `users` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
ALTER TABLE `user_permissions` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;
ALTER TABLE `visitor_certificates` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `volunteers` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `volunteer_activities` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `recurring_donations` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `recurring_donation_transactions` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

-- ========================================================
-- Constraints for Tables
-- ========================================================

-- Constraints for table `agent_attendance`
ALTER TABLE `agent_attendance`
ADD CONSTRAINT `agent_attendance_ibfk_1` FOREIGN KEY (`agent_id`) REFERENCES `field_agents` (`id`) ON DELETE CASCADE;

-- Constraints for table `agent_salary_ledger`
ALTER TABLE `agent_salary_ledger`
ADD CONSTRAINT `agent_salary_ledger_ibfk_1` FOREIGN KEY (`agent_id`) REFERENCES `field_agents` (`id`) ON DELETE CASCADE;

-- Constraints for table `attendance_bonus_history`
ALTER TABLE `attendance_bonus_history`
ADD CONSTRAINT `attendance_bonus_history_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `sa_students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_bonus_history_ibfk_2` FOREIGN KEY (`reference_threshold_id`) REFERENCES `attendance_thresholds` (`id`) ON DELETE SET NULL;

-- Constraints for table `attendance_events`
ALTER TABLE `attendance_events`
ADD CONSTRAINT `fk_attendance_events_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_attendance_events_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- Constraints for table `attendance_logs`
ALTER TABLE `attendance_logs`
ADD CONSTRAINT `fk_attendance_logs_event` FOREIGN KEY (`event_id`) REFERENCES `attendance_events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_attendance_logs_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_attendance_logs_volunteer` FOREIGN KEY (`volunteer_id`) REFERENCES `volunteers` (`id`) ON DELETE CASCADE;

-- Constraints for table `attendance_monthly_summary`
ALTER TABLE `attendance_monthly_summary`
ADD CONSTRAINT `attendance_monthly_summary_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `sa_students` (`id`) ON DELETE CASCADE;

-- Constraints for table `cash_deposits`
ALTER TABLE `cash_deposits`
ADD CONSTRAINT `cash_deposits_ibfk_1` FOREIGN KEY (`donation_id`) REFERENCES `donations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cash_deposits_ibfk_2` FOREIGN KEY (`agent_id`) REFERENCES `field_agents` (`id`) ON DELETE CASCADE;

-- Constraints for table `donations`
ALTER TABLE `donations`
ADD CONSTRAINT `donations_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL;

-- Constraints for table `donation_achievements`
ALTER TABLE `donation_achievements`
ADD CONSTRAINT `donation_achievements_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `sa_students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `donation_achievements_ibfk_2` FOREIGN KEY (`milestone_id`) REFERENCES `donation_milestones` (`id`);

-- Constraints for table `event_gallery`
ALTER TABLE `event_gallery`
ADD CONSTRAINT `fk_event_gallery_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;

-- Constraints for table `event_registrations`
ALTER TABLE `event_registrations`
ADD CONSTRAINT `fk_event_regs_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;

-- Constraints for table `field_agents`
ALTER TABLE `field_agents`
ADD CONSTRAINT `field_agents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

-- Constraints for table `members`
ALTER TABLE `members`
ADD CONSTRAINT `fk_members_designation` FOREIGN KEY (`designation_id`) REFERENCES `member_designations` (`id`),
  ADD CONSTRAINT `fk_members_referred_by` FOREIGN KEY (`referred_by_member_id`) REFERENCES `members` (`id`) ON DELETE SET NULL;

-- Constraints for table `member_documents`
ALTER TABLE `member_documents`
ADD CONSTRAINT `fk_member_docs_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

-- Constraints for table `member_messages`
ALTER TABLE `member_messages`
ADD CONSTRAINT `fk_member_messages_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

-- Constraints for table `member_message_deliveries`
ALTER TABLE `member_message_deliveries`
ADD CONSTRAINT `fk_mmd_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_mmd_message` FOREIGN KEY (`message_id`) REFERENCES `member_messages` (`id`) ON DELETE CASCADE;

-- Constraints for table `notification_preferences`
ALTER TABLE `notification_preferences`
ADD CONSTRAINT `notification_preferences_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `sa_students` (`id`) ON DELETE CASCADE;

-- Constraints for table `program_features`
ALTER TABLE `program_features`
ADD CONSTRAINT `program_features_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE;

-- Constraints for table `program_gallery`
ALTER TABLE `program_gallery`
ADD CONSTRAINT `program_gallery_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE;

-- Constraints for table `project_gallery`
ALTER TABLE `project_gallery`
ADD CONSTRAINT `project_gallery_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;

-- Constraints for table `qr_scan_logs`
ALTER TABLE `qr_scan_logs`
ADD CONSTRAINT `fk_qr_scan_logs_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_qr_scan_logs_token` FOREIGN KEY (`qr_token_id`) REFERENCES `qr_tokens` (`id`) ON DELETE SET NULL;

-- Constraints for table `qr_tokens`
ALTER TABLE `qr_tokens`
ADD CONSTRAINT `fk_qr_tokens_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

-- Constraints for table `sa_penalties`
ALTER TABLE `sa_penalties`
ADD CONSTRAINT `fk_sa_penalties_created_by_user` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_penalties_deleted_by_user` FOREIGN KEY (`deleted_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_penalties_imposed_by_user` FOREIGN KEY (`imposed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_penalties_program` FOREIGN KEY (`program_id`) REFERENCES `sa_programs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_penalties_resolved_by_user` FOREIGN KEY (`resolved_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_penalties_student` FOREIGN KEY (`student_id`) REFERENCES `sa_students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sa_penalties_tx` FOREIGN KEY (`point_transaction_id`) REFERENCES `sa_point_transactions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_penalties_updated_by_user` FOREIGN KEY (`updated_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- Constraints for table `sa_point_rules`
ALTER TABLE `sa_point_rules`
ADD CONSTRAINT `fk_sa_point_rules_created_by_user` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_point_rules_deleted_by_user` FOREIGN KEY (`deleted_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_point_rules_program` FOREIGN KEY (`program_id`) REFERENCES `sa_programs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_point_rules_updated_by_user` FOREIGN KEY (`updated_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- Constraints for table `sa_point_transactions`
ALTER TABLE `sa_point_transactions`
ADD CONSTRAINT `fk_sa_point_tx_approved_by_user` FOREIGN KEY (`approved_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_point_tx_created_by_user` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_point_tx_deleted_by_user` FOREIGN KEY (`deleted_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_point_tx_program` FOREIGN KEY (`program_id`) REFERENCES `sa_programs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_point_tx_reversed_tx` FOREIGN KEY (`reversed_transaction_id`) REFERENCES `sa_point_transactions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_point_tx_rule` FOREIGN KEY (`rule_id`) REFERENCES `sa_point_rules` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_point_tx_student` FOREIGN KEY (`student_id`) REFERENCES `sa_students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sa_point_tx_updated_by_user` FOREIGN KEY (`updated_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- Constraints for table `sa_programs`
ALTER TABLE `sa_programs`
ADD CONSTRAINT `fk_sa_programs_created_by_user` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_programs_deleted_by_user` FOREIGN KEY (`deleted_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_programs_mentor_user` FOREIGN KEY (`mentor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_programs_updated_by_user` FOREIGN KEY (`updated_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- Constraints for table `sa_referrals`
ALTER TABLE `sa_referrals`
ADD CONSTRAINT `fk_sa_referrals_created_by_user` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_referrals_deleted_by_user` FOREIGN KEY (`deleted_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_referrals_program` FOREIGN KEY (`program_id`) REFERENCES `sa_programs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_referrals_referred_student` FOREIGN KEY (`referred_student_id`) REFERENCES `sa_students` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_referrals_referrer_student` FOREIGN KEY (`referrer_student_id`) REFERENCES `sa_students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sa_referrals_updated_by_user` FOREIGN KEY (`updated_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_referrals_verified_by_user` FOREIGN KEY (`verified_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- Constraints for table `sa_students`
ALTER TABLE `sa_students`
ADD CONSTRAINT `fk_sa_student_program` FOREIGN KEY (`program_id`) REFERENCES `sa_programs` (`id`) ON DELETE SET NULL;

-- Constraints for table `sa_vendor_leads`
ALTER TABLE `sa_vendor_leads`
ADD CONSTRAINT `fk_sa_vendor_leads_created_by_user` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_vendor_leads_deleted_by_user` FOREIGN KEY (`deleted_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_vendor_leads_program` FOREIGN KEY (`program_id`) REFERENCES `sa_programs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_vendor_leads_student` FOREIGN KEY (`student_id`) REFERENCES `sa_students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sa_vendor_leads_updated_by_user` FOREIGN KEY (`updated_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_vendor_leads_verified_by_user` FOREIGN KEY (`verified_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- Constraints for table `templates`
ALTER TABLE `templates`
ADD CONSTRAINT `fk_templates_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- Constraints for table `volunteer_activities`
ALTER TABLE `volunteer_activities`
ADD CONSTRAINT `volunteer_activities_ibfk_1` FOREIGN KEY (`volunteer_id`) REFERENCES `volunteers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `volunteer_activities_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE SET NULL;


-- Constraints for table `recurring_donations`
ALTER TABLE `recurring_donations`
  ADD CONSTRAINT `fk_rec_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_rec_student` FOREIGN KEY (`sa_student_id`) REFERENCES `sa_students` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_rec_agent` FOREIGN KEY (`field_agent_id`) REFERENCES `field_agents` (`id`) ON DELETE SET NULL;

-- Constraints for table `recurring_donation_transactions`
ALTER TABLE `recurring_donation_transactions`
  ADD CONSTRAINT `fk_rec_tx_parent` FOREIGN KEY (`recurring_donation_id`) REFERENCES `recurring_donations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rec_tx_donation` FOREIGN KEY (`donation_id`) REFERENCES `donations` (`id`) ON DELETE SET NULL;

-- --------------------------------------------------------
-- Table structure for table `item_donation_categories`
-- --------------------------------------------------------
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

-- --------------------------------------------------------
-- Table structure for table `item_donations`
-- --------------------------------------------------------
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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
