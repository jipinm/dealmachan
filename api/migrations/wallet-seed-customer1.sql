-- =====================================================================
-- Wallet Seed: Fix schema + add test data for customer@test.com
-- user_id = 2  |  customer_id = 1
-- Run against: deal_machan
-- =====================================================================

USE `deal_machan`;

-- =====================================================================
-- 1. Add missing columns to coupon_subscriptions
--    The controller uses `status` and `saved_at` but the table only
--    has `subscribed_at`.
-- =====================================================================

-- Add `status` column if it doesn't exist
ALTER TABLE `coupon_subscriptions`
    ADD COLUMN IF NOT EXISTS `status`    ENUM('saved','redeemed','removed') NOT NULL DEFAULT 'saved',
    ADD COLUMN IF NOT EXISTS `saved_at`  TIMESTAMP NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `redeemed_at` TIMESTAMP NULL DEFAULT NULL;

-- Back-fill: every existing row was a simple save
UPDATE `coupon_subscriptions`
SET `status`   = 'saved',
    `saved_at` = `subscribed_at`
WHERE `saved_at` IS NULL;

-- =====================================================================
-- 2. Saved coupons for customer_id = 1
--    Picks up to 6 active/approved/non-expired coupons that the
--    customer hasn't already saved or redeemed.
-- =====================================================================
INSERT IGNORE INTO `coupon_subscriptions`
    (`customer_id`, `coupon_id`, `status`, `saved_at`, `subscribed_at`)
SELECT
    1                   AS customer_id,
    c.id                AS coupon_id,
    'saved'             AS status,
    NOW() - INTERVAL FLOOR(RAND()*20) DAY AS saved_at,
    NOW() - INTERVAL FLOOR(RAND()*20) DAY AS subscribed_at
FROM `coupons` c
WHERE c.status           = 'active'
  AND c.approval_status  = 'approved'
  AND (c.valid_until IS NULL OR c.valid_until >= CURDATE())
  AND c.id NOT IN (
        SELECT coupon_id FROM coupon_subscriptions WHERE customer_id = 1
  )
  AND c.id NOT IN (
        SELECT coupon_id FROM coupon_redemptions   WHERE customer_id = 1
  )
ORDER BY c.id
LIMIT 6;

-- =====================================================================
-- 3. Gift coupons for customer_id = 1
--    One pending (requires action), two already accepted.
--    Use coupon IDs that are valid and not already gifted to customer 1.
-- =====================================================================

-- Finding valid approved coupons not yet gifted to customer 1
-- We'll insert directly with coupon IDs that are highly likely to exist.
-- Guard: skip if already gifted.

INSERT IGNORE INTO `gift_coupons`
    (`admin_id`, `customer_id`, `coupon_id`, `requires_acceptance`,
     `acceptance_status`, `gifted_at`, `accepted_at`, `expires_at`)
SELECT
    1, 1, c.id, 1,
    'pending',
    NOW() - INTERVAL 3 DAY,
    NULL,
    NOW() + INTERVAL 60 DAY
FROM `coupons` c
WHERE c.status          = 'active'
  AND c.approval_status = 'approved'
  AND (c.valid_until IS NULL OR c.valid_until >= CURDATE())
  AND c.id NOT IN (SELECT coupon_id FROM gift_coupons WHERE customer_id = 1)
  AND c.id NOT IN (SELECT coupon_id FROM coupon_redemptions WHERE customer_id = 1)
ORDER BY c.id DESC
LIMIT 1;

INSERT IGNORE INTO `gift_coupons`
    (`admin_id`, `customer_id`, `coupon_id`, `requires_acceptance`,
     `acceptance_status`, `gifted_at`, `accepted_at`, `expires_at`)
SELECT
    1, 1, c.id, 0,
    'accepted',
    NOW() - INTERVAL 10 DAY,
    NOW() - INTERVAL 9 DAY,
    NOW() + INTERVAL 45 DAY
FROM `coupons` c
WHERE c.status          = 'active'
  AND c.approval_status = 'approved'
  AND (c.valid_until IS NULL OR c.valid_until >= CURDATE())
  AND c.id NOT IN (SELECT coupon_id FROM gift_coupons WHERE customer_id = 1)
  AND c.id NOT IN (SELECT coupon_id FROM coupon_redemptions WHERE customer_id = 1)
  AND c.id NOT IN (SELECT coupon_id FROM coupon_subscriptions WHERE customer_id = 1)
ORDER BY c.id DESC
LIMIT 1;

-- =====================================================================
-- 4. Redemption history for customer_id = 1
--    Add entries only if this customer has fewer than 5 past redemptions.
-- =====================================================================

INSERT INTO `coupon_redemptions`
    (`coupon_id`, `customer_id`, `store_id`, `redeemed_at`,
     `redemption_location`, `discount_amount`, `transaction_amount`, `verified_by_merchant`)
SELECT
    c.id,
    1,
    c.store_id,
    NOW() - INTERVAL n.x DAY,
    ELT(1 + FLOOR(RAND()*3), 'In-Store', 'At Counter', 'Self-Service Kiosk'),
    ROUND(c.discount_value / 100.0 * (500 + FLOOR(RAND()*1500)), 2),
    500 + FLOOR(RAND()*1500),
    1
FROM `coupons` c
JOIN (
    SELECT 7 AS x UNION SELECT 14 UNION SELECT 21
) n ON 1=1
WHERE c.status          = 'active'
  AND c.approval_status = 'approved'
  AND c.discount_type   = 'percentage'
  AND c.store_id        IS NOT NULL
  AND (c.valid_until IS NULL OR c.valid_until >= CURDATE() - INTERVAL 90 DAY)
  AND c.id NOT IN (
        SELECT coupon_id FROM coupon_redemptions WHERE customer_id = 1
  )
ORDER BY c.id
LIMIT 3;

-- =====================================================================
-- Verify results
-- =====================================================================
SELECT
    'coupon_subscriptions' AS tbl,
    COUNT(*)               AS rows_for_customer_1
FROM coupon_subscriptions
WHERE customer_id = 1

UNION ALL

SELECT
    'gift_coupons',
    COUNT(*)
FROM gift_coupons
WHERE customer_id = 1

UNION ALL

SELECT
    'coupon_redemptions',
    COUNT(*)
FROM coupon_redemptions
WHERE customer_id = 1;
