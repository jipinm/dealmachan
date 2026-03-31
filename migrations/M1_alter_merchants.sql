-- Migration M1: merchants table — add is_verified, subscription_period, coupon_limit, monthly_assignment_limit
-- Required by Tasks: B2, B3, B4, D3
-- Run BEFORE any application code changes.

ALTER TABLE `merchants`
  ADD COLUMN `is_verified` tinyint(1) NOT NULL DEFAULT 0
    COMMENT 'Verified Partner badge toggle'
    AFTER `is_premium`,
  ADD COLUMN `subscription_period` enum('1M','3M','6M','1Y') DEFAULT '1Y'
    COMMENT 'Duration of current subscription; used to calculate expiry on renewal'
    AFTER `subscription_expiry`,
  ADD COLUMN `coupon_limit` int(10) unsigned DEFAULT NULL
    COMMENT 'Total store-coupon assignment cap for this merchant; NULL = unlimited'
    AFTER `subscription_period`,
  ADD COLUMN `monthly_assignment_limit` int(10) unsigned DEFAULT NULL
    COMMENT 'Monthly cap on store-coupon assignments; NULL = unlimited'
    AFTER `coupon_limit`,
  ADD KEY `idx_is_verified` (`is_verified`);
