-- ============================================================
-- Database Migration: Add Digital Acknowledgment to Agreements & Doctor Agreements
-- Description: Adds is_acknowledged, acknowledged_at, acknowledged_name,
--              acknowledged_ip, acknowledged_user_agent, acknowledgment_token
-- Author: VELNIX SOFT / Antigravity AI
-- Date: 2026-09-12
-- ============================================================

ALTER TABLE `agreements`
  ADD COLUMN `partner_member_id` int(11) DEFAULT NULL AFTER `partner_address`,
  ADD COLUMN `is_acknowledged` tinyint(1) NOT NULL DEFAULT 0 AFTER `signed_status`,
  ADD COLUMN `acknowledged_at` datetime DEFAULT NULL AFTER `is_acknowledged`,
  ADD COLUMN `acknowledged_name` varchar(150) DEFAULT NULL AFTER `acknowledged_at`,
  ADD COLUMN `acknowledged_ip` varchar(45) DEFAULT NULL AFTER `acknowledged_name`,
  ADD COLUMN `acknowledged_user_agent` varchar(255) DEFAULT NULL AFTER `acknowledged_ip`,
  ADD COLUMN `acknowledgment_token` varchar(64) DEFAULT NULL AFTER `acknowledged_user_agent`;

ALTER TABLE `doctor_agreements`
  ADD COLUMN `is_acknowledged` tinyint(1) NOT NULL DEFAULT 0 AFTER `status`,
  ADD COLUMN `acknowledged_at` datetime DEFAULT NULL AFTER `is_acknowledged`,
  ADD COLUMN `acknowledged_name` varchar(150) DEFAULT NULL AFTER `acknowledged_at`,
  ADD COLUMN `acknowledged_ip` varchar(45) DEFAULT NULL AFTER `acknowledged_name`;
