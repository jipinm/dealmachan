-- Migration M5: coupons table — add note and usage_limit_per_user
-- Required by Task: E2

ALTER TABLE `coupons`
  ADD COLUMN `note` text DEFAULT NULL
    COMMENT 'Internal admin note — not shown to customers'
    AFTER `terms_conditions`,
  ADD COLUMN `usage_limit_per_user` int(11) DEFAULT NULL
    COMMENT 'Max times a single customer can redeem this coupon; NULL = unlimited'
    AFTER `usage_limit`;
