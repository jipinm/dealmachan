-- ============================================================
-- DEV / TESTING ONLY — Standardize customer passwords
-- Password: Customer@123
-- Hash generated: password_hash('Customer@123', PASSWORD_DEFAULT)
-- ============================================================

-- Update ALL customer users to Customer@123
UPDATE `users`
SET `password_hash` = '$2y$10$oU/h6Tlti7AdA3NFJSCoQOWcUGW0IHi/P2n1WTR7fqzl5ZzcoBDmy',
    `status`        = 'active',
    `otp_code`      = NULL,
    `otp_expiry`    = NULL,
    `updated_at`    = NOW()
WHERE `user_type` = 'customer';

-- Verify: show a few rows
SELECT id, email, phone, LEFT(password_hash, 20) AS hash_prefix, status, user_type
FROM `users`
WHERE user_type = 'customer'
ORDER BY id
LIMIT 10;

-- ============================================================
-- Ensure primary test customer (user_id=2, phone=9876543210)
-- has city/area set so login goes to dashboard, not onboarding
-- ============================================================
UPDATE `customers`
SET
    `city_id`  = 2,   -- Kochi
    `area_id`  = 11,  -- Ernakulam
    `updated_at` = NOW()
WHERE `user_id` = 2
  AND (`city_id` IS NULL OR `city_id` = 0);

-- ============================================================
-- Summary of test credentials
-- ============================================================
-- Primary Customer:
--   Phone : 9876543210
--   Email : customer@test.com
--   Pass  : Customer@123
--   OTP   : 1234  (hardcoded in AuthController)
--
-- Other Customers (phones 9847001001 - 9847002030):
--   Pass  : Customer@123
--
-- Merchant accounts are NOT changed (use existing passwords)
-- ============================================================
