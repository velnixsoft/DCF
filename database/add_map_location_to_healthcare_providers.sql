-- ============================================================
-- Database Migration: Add Google Map Location & Coordinates to healthcare_providers
-- Description: Adds map_location, latitude, longitude, and map_embed_url
-- Author: VELNIX SOFT / Antigravity AI
-- Date: 2026-09-12
-- ============================================================

ALTER TABLE `healthcare_providers`
  ADD COLUMN `map_location` varchar(500) DEFAULT NULL COMMENT 'Google Maps URL, Place Link, or Address query' AFTER `landmark`,
  ADD COLUMN `latitude` decimal(10,8) DEFAULT NULL COMMENT 'Latitude coordinate e.g. 28.628929' AFTER `map_location`,
  ADD COLUMN `longitude` decimal(11,8) DEFAULT NULL COMMENT 'Longitude coordinate e.g. 77.206532' AFTER `latitude`,
  ADD COLUMN `map_embed_url` text DEFAULT NULL COMMENT 'Google Maps Embed iframe source URL' AFTER `longitude`;
