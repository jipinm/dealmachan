-- =====================================================================
-- Seed Data for Test Customer: customer@test.com
-- user_id = 2  |  customer_id = 1  |  phone = 9876543210
-- =====================================================================
-- Tables populated:
--   1. customers            (UPDATE full profile)
--   2. subscriptions        (INSERT annual plan)
--   3. coupon_subscriptions (INSERT 10 saved coupons)
--   4. coupon_redemptions   (INSERT 7 more redemptions — already has 5)
--   5. notifications        (INSERT 8 customer notifications)
--   6. referrals            (INSERT 3 referral records)
--   7. store_coupons        (INSERT 2 gifted store coupons)
-- =====================================================================
-- Run once against the `deal_machan` database.
-- Safe to re-run: uses INSERT IGNORE / WHERE-guards on UPDATE.
-- =====================================================================

USE `deal_machan`;

-- =====================================================================
-- 1. Full profile update for customer_id = 1
-- =====================================================================
UPDATE `customers` SET
    `name`                  = 'Test',
    `last_name`             = 'Customer',
    `date_of_birth`         = '1990-06-15',
    `gender`                = 'male',
    `profession_id`         = 1,
    `job_title_id`          = 1,
    `customer_type`         = 'premium',
    `subscription_status`   = 'active',
    `subscription_expiry`   = '2027-01-01',
    `referral_code`         = 'REF-TEST-001',
    `is_dealmaker`          = 0,
    `city_id`               = 2,
    `area_id`               = 11,
    `bio`                   = 'Tech-savvy Deal Machan enthusiast based in Kochi. Love discovering new deals and saving money at my favourite local stores.',
    `occupation`            = 'Software Engineer',
    `full_address`          = 'Flat 4B, Palm Grove Apartments, MG Road, Ernakulam, Kochi – 682011',
    `pincode`               = '682011',
    `push_enabled`          = 1,
    `updated_at`            = NOW()
WHERE `id` = 1;

-- Also ensure user record is active and clean
UPDATE `users` SET
    `status`     = 'active',
    `otp_code`   = NULL,
    `otp_expiry` = NULL,
    `updated_at` = NOW()
WHERE `id` = 2;

-- =====================================================================
-- 2. Annual subscription for customer (user_id = 2)
-- =====================================================================
INSERT IGNORE INTO `subscriptions`
    (`user_id`, `user_type`, `plan_type`, `start_date`, `expiry_date`,
     `auto_renew`, `status`, `payment_amount`, `payment_method`, `created_at`)
SELECT
    2, 'customer', 'annual', '2026-01-01', '2027-01-01',
    1, 'active', 799.00, 'upi', NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `subscriptions`
    WHERE `user_id` = 2 AND `user_type` = 'customer'
);

-- =====================================================================
-- 3. Coupon subscriptions (saved coupons) for customer_id = 1
--    Using coupon IDs confirmed valid from coupon_redemptions + gift_coupons:
--    1,2,3,4,5,6,9,10,11,12 — standard coupon range
-- =====================================================================
INSERT IGNORE INTO `coupon_subscriptions` (`customer_id`, `coupon_id`, `subscribed_at`) VALUES
    (1, 1,  '2026-01-05 10:00:00'),
    (1, 2,  '2026-01-08 11:00:00'),
    (1, 3,  '2026-01-10 14:00:00'),
    (1, 4,  '2026-01-12 09:30:00'),
    (1, 5,  '2026-01-15 16:00:00'),
    (1, 6,  '2026-01-18 10:30:00'),
    (1, 9,  '2026-01-20 13:00:00'),
    (1, 10, '2026-01-22 15:00:00'),
    (1, 11, '2026-01-25 09:00:00'),
    (1, 12, '2026-01-28 11:00:00');

-- =====================================================================
-- 4. Additional coupon redemptions for customer_id = 1
--    (already has: coupon 7@store 7, 8@store 8, 21@store 20, 18@store 18, 41@store 1)
-- =====================================================================
INSERT INTO `coupon_redemptions`
    (`coupon_id`, `customer_id`, `store_id`, `redeemed_at`,
     `redemption_location`, `discount_amount`, `transaction_amount`, `verified_by_merchant`)
VALUES
    (1,  1, 2,  '2026-01-10 12:30:00', 'In-Store',             150.00, 1500.00, 1),
    (2,  1, 2,  '2026-01-18 14:00:00', 'At Counter',           200.00, 2000.00, 1),
    (3,  1, 3,  '2026-01-25 11:00:00', 'In-Store',             120.00, 1200.00, 0),
    (5,  1, 5,  '2026-02-01 10:00:00', 'Self-Service Kiosk',   300.00, 3000.00, 1),
    (6,  1, 7,  '2026-02-05 16:00:00', 'Online Redemption',     75.00,  750.00, 1),
    (9,  1, 10, '2026-02-10 13:30:00', 'At Counter',           180.00, 1800.00, 0),
    (11, 1, 11, '2026-02-15 15:00:00', 'In-Store',              90.00,  900.00, 1);

-- =====================================================================
-- 5. Customer notifications for user_id = 2 (user_type = 'customer')
-- =====================================================================
INSERT INTO `notifications`
    (`user_id`, `user_type`, `notification_type`, `title`, `message`,
     `action_url`, `read_status`, `read_at`, `created_at`)
VALUES
    (2, 'customer', 'success',
     'Welcome to Deal Machan!',
     'Your account is all set. Start browsing deals and save on your favourite stores in Kochi.',
     '/home', 1, '2026-01-01 10:05:00', '2026-01-01 10:00:00'),

    (2, 'customer', 'success',
     'Annual Subscription Activated',
     'Your Deal Machan annual subscription is now active. Enjoy premium access to all deals until Jan 2027.',
     '/my-account', 1, '2026-01-01 11:10:00', '2026-01-01 11:00:00'),

    (2, 'customer', 'info',
     'New Coupon Available – Spice Garden',
     'Spice Garden Restaurant has a new 20% off coupon available. Grab it before it expires!',
     '/coupons', 1, '2026-01-10 13:00:00', '2026-01-10 12:00:00'),

    (2, 'customer', 'success',
     'Coupon Redeemed – ₹150 Saved!',
     'You saved ₹150 at Spice Garden Ernakulam. Keep redeeming for more savings!',
     '/my-savings', 1, '2026-01-10 12:35:00', '2026-01-10 12:31:00'),

    (2, 'customer', 'warning',
     'Coupons Expiring Soon',
     '3 of your saved coupons expire in the next 7 days. Use them before they\'re gone!',
     '/my-coupons', 1, '2026-01-20 10:05:00', '2026-01-20 10:00:00'),

    (2, 'customer', 'success',
     'Gift Coupon Received!',
     'You have received a gift coupon from Deal Machan for Kerala Sweets Palace. Valid until April 2026.',
     '/gift-coupons', 1, '2026-01-21 10:30:00', '2026-01-21 10:00:00'),

    (2, 'customer', 'info',
     'Mystery Shopping Task Verified',
     'Your mystery shopping report for Test Restaurant has been verified. ₹500 reward will be credited shortly.',
     '/my-tasks', 1, '2026-02-11 12:10:00', '2026-02-11 12:00:00'),

    (2, 'customer', 'info',
     'Flash Discount Alert – Tonight Only',
     'Midnight Hunger Special: 50% off at Test Restaurant after 10 PM. Limited to 30 customers!',
     '/flash-discounts', 0, NULL, '2026-02-20 20:00:00');

-- =====================================================================
-- 6. Referrals — customer_id = 1 as referrer
--    Referees: customer_id 16, 17, 18 (exist in customers table, not yet refereed)
-- =====================================================================

-- Tag referred customers
UPDATE `customers`
SET `referred_by` = 'REF-TEST-001', `updated_at` = NOW()
WHERE `id` = 16 AND (`referred_by` IS NULL OR `referred_by` = '');

UPDATE `customers`
SET `referred_by` = 'REF-TEST-002', `updated_at` = NOW()
WHERE `id` = 17 AND (`referred_by` IS NULL OR `referred_by` = '');

UPDATE `customers`
SET `referred_by` = 'REF-TEST-003', `updated_at` = NOW()
WHERE `id` = 18 AND (`referred_by` IS NULL OR `referred_by` = '');

INSERT INTO `referrals`
    (`referrer_customer_id`, `referee_customer_id`, `referral_code`,
     `status`, `reward_given`, `reward_amount`, `created_at`, `completed_at`)
VALUES
    (1, 16, 'REF-TEST-001', 'rewarded',  1, 50.00, '2026-01-15 10:00:00', '2026-01-20 10:00:00'),
    (1, 17, 'REF-TEST-002', 'completed', 0,  0.00, '2026-01-25 10:00:00', '2026-01-30 10:00:00'),
    (1, 18, 'REF-TEST-003', 'pending',   0,  0.00, '2026-02-10 10:00:00', NULL);

-- =====================================================================
-- 7. Store coupons gifted to customer_id = 1
-- =====================================================================
INSERT IGNORE INTO `store_coupons`
    (`merchant_id`, `store_id`, `coupon_code`, `discount_type`, `discount_value`,
     `valid_from`, `valid_until`, `is_gifted`, `gifted_to_customer_id`, `gifted_at`,
     `is_redeemed`, `status`, `created_at`)
VALUES
    (9, 10, 'BIRIYANI-C1', 'percentage', 15.00,
     '2026-01-01 00:00:00', '2026-06-30 23:59:59', 1, 1, '2026-01-15 10:00:00',
     0, 'active', '2026-01-15 10:00:00'),
    (5, 6, 'FITZONE-C1', 'fixed', 500.00,
     '2026-01-01 00:00:00', '2026-04-30 23:59:59', 1, 1, '2026-02-01 10:00:00',
     0, 'active', '2026-02-01 10:00:00');

-- =====================================================================
-- Summary of changes applied:
-- customers (id=1)       : full profile set (name/dob/profession/city/bio/address)
-- users (id=2)           : status=active, OTP cleared
-- subscriptions          : 1 annual plan (₹799, upi, active until 2027-01-01)
-- coupon_subscriptions   : 10 saved coupons (ids 1-6, 9-12)
-- coupon_redemptions     : +7 redemptions (total ~12 for this customer)
-- notifications          : 8 customer notifications (6 read, 2 unread)
-- referrals              : 3 referral records (1 rewarded, 1 completed, 1 pending)
-- store_coupons          : 2 gifted store coupons (Biriyani House, FitZone)
-- =====================================================================

SELECT 'Seed complete for customer@test.com (customer_id=1)' AS status;
