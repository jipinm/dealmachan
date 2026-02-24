-- ============================================================
-- Migration 009 — Missing Tables for Customer App
-- Created: 2026-02-21
-- Tables: customer_important_days, referrals, merchants
--         column additions (business_description, avg_rating…)
-- ============================================================

-- ── customer_important_days ───────────────────────────────────────────────────
-- Used by: ImportantDaysController, ImportantDaysPage
CREATE TABLE IF NOT EXISTS `customer_important_days` (
  `id`           int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id`  int(10) unsigned NOT NULL,
  `event_type`   enum('Birthday','Anniversary','Others') NOT NULL,
  `event_specify` varchar(100)   DEFAULT NULL COMMENT 'Custom label when type = Others',
  `event_day`    tinyint(2) unsigned NOT NULL,
  `event_month`  tinyint(2) unsigned NOT NULL,
  `person_name`  varchar(100)   DEFAULT NULL COMMENT 'Name of person the event is for',
  `created_at`   timestamp      NOT NULL DEFAULT current_timestamp(),
  `updated_at`   timestamp      NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cid_event` (`customer_id`, `event_type`, `event_day`, `event_month`),
  KEY `fk_cid_customer` (`customer_id`),
  CONSTRAINT `fk_cid_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── referrals ─────────────────────────────────────────────────────────────────
-- Used by: ReferralController, ReferralPage
CREATE TABLE IF NOT EXISTS `referrals` (
  `id`            int(10) unsigned NOT NULL AUTO_INCREMENT,
  `referrer_id`   int(10) unsigned NOT NULL COMMENT 'customer.id of the one who referred',
  `referee_id`    int(10) unsigned DEFAULT NULL COMMENT 'customer.id of the new user',
  `referee_mobile` varchar(15)    DEFAULT NULL,
  `referee_name`  varchar(100)    DEFAULT NULL,
  `status`        enum('pending','completed','rewarded') NOT NULL DEFAULT 'pending',
  `reward_amount` decimal(10,2)   DEFAULT NULL,
  `reward_given`  tinyint(1)      NOT NULL DEFAULT 0,
  `created_at`    timestamp       NOT NULL DEFAULT current_timestamp(),
  `completed_at`  timestamp       NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_ref_referrer` (`referrer_id`),
  KEY `fk_ref_referee`  (`referee_id`),
  CONSTRAINT `fk_ref_referrer` FOREIGN KEY (`referrer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ref_referee`  FOREIGN KEY (`referee_id`)  REFERENCES `customers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Add referral_code to customers if missing ─────────────────────────────────
ALTER TABLE `customers`
  ADD COLUMN IF NOT EXISTS `referral_code` varchar(20) DEFAULT NULL AFTER `is_dealmaker`,
  ADD COLUMN IF NOT EXISTS `temp_password` tinyint(1)  NOT NULL DEFAULT 0
                            COMMENT '1=must reset password before using app'
                            AFTER `referral_code`,
  ADD COLUMN IF NOT EXISTS `push_enabled`  tinyint(1)  NOT NULL DEFAULT 1
                            AFTER `temp_password`;

-- Backfill random referral codes for existing customers who have none
UPDATE `customers`
SET `referral_code` = UPPER(SUBSTRING(MD5(CONCAT(id, RAND())), 1, 8))
WHERE `referral_code` IS NULL;

-- ── Add missing merchant profile columns (if 007 migration not yet run) ───────
ALTER TABLE `merchants`
  ADD COLUMN IF NOT EXISTS `business_description` text DEFAULT NULL AFTER `business_name`,
  ADD COLUMN IF NOT EXISTS `business_category`    varchar(100) DEFAULT NULL AFTER `business_description`,
  ADD COLUMN IF NOT EXISTS `avg_rating`           decimal(3,2) NOT NULL DEFAULT 0.00 AFTER `business_logo`,
  ADD COLUMN IF NOT EXISTS `total_reviews`        int(10) unsigned NOT NULL DEFAULT 0 AFTER `avg_rating`,
  ADD COLUMN IF NOT EXISTS `website_url`          varchar(255) DEFAULT NULL AFTER `total_reviews`;

-- ── Add missing stores columns (if not already present) ──────────────────────
ALTER TABLE `stores`
  ADD COLUMN IF NOT EXISTS `email`         varchar(150) DEFAULT NULL AFTER `phone`,
  ADD COLUMN IF NOT EXISTS `latitude`      decimal(10,7) DEFAULT NULL AFTER `longitude`,
  ADD COLUMN IF NOT EXISTS `longitude`     decimal(10,7) DEFAULT NULL AFTER `latitude`,
  ADD COLUMN IF NOT EXISTS `opening_hours` json DEFAULT NULL COMMENT 'Array of {day,open,close,closed}' AFTER `email`,
  ADD COLUMN IF NOT EXISTS `description`   text DEFAULT NULL AFTER `opening_hours`;
