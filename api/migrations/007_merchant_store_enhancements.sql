-- ============================================================
-- Migration 007: Merchant & Store Enhancement Columns
-- Run once on the production database.
-- ============================================================

-- Add missing descriptive columns to merchants
ALTER TABLE `merchants`
  ADD COLUMN IF NOT EXISTS `business_description` text DEFAULT NULL AFTER `business_name`,
  ADD COLUMN IF NOT EXISTS `business_category`    varchar(100) DEFAULT NULL AFTER `business_description`,
  ADD COLUMN IF NOT EXISTS `avg_rating`           decimal(3,2) NOT NULL DEFAULT 0.00,
  ADD COLUMN IF NOT EXISTS `total_reviews`        int(10) unsigned NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `website_url`          varchar(255) DEFAULT NULL;

-- Ensure stores table has all needed columns (already present in latest schema,
-- but included here for environments running from the original schema dump)
ALTER TABLE `stores`
  ADD COLUMN IF NOT EXISTS `email`         varchar(255) DEFAULT NULL AFTER `phone`,
  ADD COLUMN IF NOT EXISTS `latitude`      decimal(10,8) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `longitude`     decimal(11,8) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `opening_hours` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`opening_hours`));

-- Index for rating-based merchant sorting
ALTER TABLE `merchants`
  ADD INDEX IF NOT EXISTS `idx_avg_rating` (`avg_rating`);
