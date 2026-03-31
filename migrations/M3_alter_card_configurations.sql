-- Migration M3: card_configurations table — add 6 feature toggle columns
-- Required by Tasks: C1, H1

ALTER TABLE `card_configurations`
  ADD COLUMN `pay_back_points_enabled` tinyint(1) NOT NULL DEFAULT 0
    COMMENT 'Toggle: card earns pay-back points on purchases'
    AFTER `coupon_authorization`,
  ADD COLUMN `pay_back_points_value` decimal(5,2) DEFAULT NULL
    COMMENT 'Points rate as % of purchase; only used when pay_back_points_enabled = 1'
    AFTER `pay_back_points_enabled`,
  ADD COLUMN `lifetime_subscription` tinyint(1) NOT NULL DEFAULT 0
    COMMENT 'Toggle: card has no expiry / lifetime validity; overrides validity_days'
    AFTER `validity_days`,
  ADD COLUMN `gift_coupon_eligibility` tinyint(1) NOT NULL DEFAULT 0
    COMMENT 'Toggle: card holders can receive gift coupons from admin'
    AFTER `lifetime_subscription`,
  ADD COLUMN `lucky_draw_eligible` tinyint(1) NOT NULL DEFAULT 0
    COMMENT 'Toggle: card holders are eligible for lucky draw participation'
    AFTER `gift_coupon_eligibility`,
  ADD COLUMN `contest_eligible` tinyint(1) NOT NULL DEFAULT 0
    COMMENT 'Toggle: card holders can participate in contests'
    AFTER `lucky_draw_eligible`;
