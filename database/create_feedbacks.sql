-- ============================================================
-- Database Migration: Employee, Member & Volunteer Feedback System
-- Description: Creates table for Employee, Member, Volunteer & Stakeholder Feedback
-- Compatible with: MySQL 5.7+, MariaDB 10.3+
-- Date: 2026-09-12
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- 1. Table structure for `feedbacks`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `feedbacks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_feedback_no` (`feedback_no`),
  KEY `idx_feedback_type` (`submitter_type`),
  KEY `idx_feedback_status` (`status`),
  KEY `idx_feedback_category` (`category`),
  KEY `idx_feedback_rating` (`rating`),
  KEY `idx_feedback_email` (`email`),
  KEY `idx_feedback_created` (`created_at`),
  KEY `idx_feedback_reply_by` (`reply_by`),
  CONSTRAINT `fk_feedback_reply_by` FOREIGN KEY (`reply_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 2. Seed Sample Feedback Records
-- ------------------------------------------------------------
INSERT INTO `feedbacks` (`feedback_no`, `submitter_type`, `user_identifier`, `name`, `contact`, `email`, `department`, `category`, `rating`, `subject`, `message`, `is_anonymous`, `status`, `priority`, `admin_reply`, `created_at`) 
VALUES
('FB-2026-0001', 'employee', 'EMP-OPS-102', 'Ramesh Sharma', '+91 9876543210', 'ramesh.sharma@velnixsoft.com', 'Field Operations', 'workplace_environment', 4, 'Field Kit & Digital Tablet Update Request', 'Our rural survey team requires updated digital tablets with offline form caching so that beneficiary entries in low-network regions sync smoothly.', 0, 'action_taken', 'high', 'Approved by Management. 10 new high-battery tablets with offline sync have been dispatched to District Coordinators.', NOW() - INTERVAL 4 DAY),

('FB-2026-0002', 'member', 'MEM-2026-8841', 'Pooja Verma', '+91 9823456781', 'pooja.verma@example.com', 'Community Health', 'program_execution', 5, 'Commendable Medical Camp at Basti Division', 'The free health checkup and medicine distribution camp was exceptionally organized. We suggest organizing such camps bi-monthly.', 0, 'closed', 'medium', 'Thank you for your valuable appreciation. We have scheduled the next follow-up health camp for next month.', NOW() - INTERVAL 2 DAY),

('FB-2026-0003', 'volunteer', 'VOL-7721', 'Ankit Tripathi', '+91 9765432190', 'ankit.volunteer@gmail.com', 'Youth Programs', 'training_guidance', 5, 'Pre-Event Briefing & ID Badges Delivery', 'Volunteers enjoyed the tree plantation drive. It would be helpful if digital ID badges and task sheets are emailed 24 hours prior to future events.', 0, 'under_review', 'medium', 'Noted Ankit! The Event Coordination wing has automated 24-hour pre-event digital kit dispatches.', NOW() - INTERVAL 1 DAY),

('FB-2026-0004', 'employee', 'EMP-ACC-04', 'Anonymous Staff Member', NULL, NULL, 'Accounts & Finance', 'compensation_benefits', 4, 'Suggestions for Annual Health Checkup Reimbursement', 'Requesting clarity on the process for OPD and diagnostic test reimbursements under the updated staff health policy.', 1, 'pending', 'medium', NULL, NOW());

SET FOREIGN_KEY_CHECKS = 1;
