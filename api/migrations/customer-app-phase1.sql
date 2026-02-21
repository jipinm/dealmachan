-- ============================================================================
-- Deal Machan Customer App — Phase 1 Database Migration
-- Run once after deploying the Customer App backend
-- ============================================================================

-- --------------------------------------------------------------------------
-- 1. Add location & profile fields to customers table
--    These are required by the Customer App onboarding + profile flows.
-- --------------------------------------------------------------------------

ALTER TABLE `customers`
  ADD COLUMN `city_id`  INT(10) UNSIGNED DEFAULT NULL COMMENT 'Customer preferred city'  AFTER `is_dealmaker`,
  ADD COLUMN `area_id`  INT(10) UNSIGNED DEFAULT NULL COMMENT 'Customer preferred area'  AFTER `city_id`,
  ADD COLUMN `bio`      TEXT             DEFAULT NULL COMMENT 'Customer bio / tagline'   AFTER `area_id`;

ALTER TABLE `customers`
  ADD KEY `idx_city_id` (`city_id`),
  ADD KEY `idx_area_id` (`area_id`);

ALTER TABLE `customers`
  ADD CONSTRAINT `fk_customers_city` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_customers_area` FOREIGN KEY (`area_id`) REFERENCES `areas`  (`id`) ON DELETE SET NULL;

-- --------------------------------------------------------------------------
-- 2. Add status column to coupon_subscriptions if missing
--    The CouponController expects status = 'saved' | 'redeemed'
-- --------------------------------------------------------------------------

ALTER TABLE `coupon_subscriptions`
  ADD COLUMN IF NOT EXISTS `status`   ENUM('saved','redeemed') NOT NULL DEFAULT 'saved' AFTER `coupon_id`,
  ADD COLUMN IF NOT EXISTS `saved_at` TIMESTAMP                DEFAULT CURRENT_TIMESTAMP AFTER `status`;

-- --------------------------------------------------------------------------
-- 3. Indexes for Customer App query performance
-- --------------------------------------------------------------------------

-- Notifications by user, ordered by date
ALTER TABLE `notifications`
  ADD INDEX IF NOT EXISTS `idx_notifications_user_created` (`user_id`, `created_at` DESC);

-- Coupon subscription lookups
ALTER TABLE `coupon_subscriptions`
  ADD INDEX IF NOT EXISTS `idx_cs_customer_status` (`customer_id`, `status`);

-- Coupon redemption history
ALTER TABLE `coupon_redemptions`
  ADD INDEX IF NOT EXISTS `idx_cr_customer_date` (`customer_id`, `redeemed_at` DESC);

-- --------------------------------------------------------------------------
-- 4. Seed test customer data for development
-- --------------------------------------------------------------------------

-- Update the existing test customer (user_id=2) to work with the Customer App
UPDATE `customers`
SET    `city_id` = NULL   -- Null = triggers onboarding flow in the app
WHERE  `user_id` = 2;

-- Give the test customer a proper referral code if not set
UPDATE `customers`
SET    `referral_code` = CONCAT('REF', LPAD(id, 6,'0'), UPPER(SUBSTRING(MD5(id), 1, 4)))
WHERE  `referral_code` IS NULL;

-- ============================================================================
-- Migration complete
-- ============================================================================
