-- ============================================================================
-- Deal Machan - Customer Seed Data (20 sample customers)
-- Password for all: Admin@123
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Users (IDs 11–30, avoiding existing IDs 1-10)
INSERT INTO `users` (`id`, `email`, `phone`, `password_hash`, `user_type`, `status`, `created_at`) VALUES
(11, 'arjun.nair@gmail.com',      '9847001001', '$2y$10$vQZ3oUV8.gWZ8PLqU4nP5eTArk4eIwziTG8Uvf9mMPzZwLGVRNhqS', 'customer', 'active',   '2025-11-01 09:15:00'),
(12, 'priya.menon@gmail.com',     '9847001002', '$2y$10$vQZ3oUV8.gWZ8PLqU4nP5eTArk4eIwziTG8Uvf9mMPzZwLGVRNhqS', 'customer', 'active',   '2025-11-03 10:30:00'),
(13, 'rahul.krishna@yahoo.com',   '9847001003', '$2y$10$vQZ3oUV8.gWZ8PLqU4nP5eTArk4eIwziTG8Uvf9mMPzZwLGVRNhqS', 'customer', 'active',   '2025-11-05 11:00:00'),
(14, 'divya.thomas@gmail.com',    '9847001004', '$2y$10$vQZ3oUV8.gWZ8PLqU4nP5eTArk4eIwziTG8Uvf9mMPzZwLGVRNhqS', 'customer', 'active',   '2025-11-08 14:20:00'),
(15, 'arun.pillai@hotmail.com',   '9847001005', '$2y$10$vQZ3oUV8.gWZ8PLqU4nP5eTArk4eIwziTG8Uvf9mMPzZwLGVRNhqS', 'customer', 'active',   '2025-11-10 09:45:00'),
(16, 'sreelakshmi.r@gmail.com',   '9847001006', '$2y$10$vQZ3oUV8.gWZ8PLqU4nP5eTArk4eIwziTG8Uvf9mMPzZwLGVRNhqS', 'customer', 'active',   '2025-11-12 16:10:00'),
(17, 'vishnu.kumar@gmail.com',    '9847001007', '$2y$10$vQZ3oUV8.gWZ8PLqU4nP5eTArk4eIwziTG8Uvf9mMPzZwLGVRNhqS', 'customer', 'active',   '2025-11-15 08:30:00'),
(18, 'anjali.dev@gmail.com',      '9847001008', '$2y$10$vQZ3oUV8.gWZ8PLqU4nP5eTArk4eIwziTG8Uvf9mMPzZwLGVRNhqS', 'customer', 'blocked',  '2025-11-18 12:00:00'),
(19, 'santhosh.george@yahoo.com', '9847001009', '$2y$10$vQZ3oUV8.gWZ8PLqU4nP5eTArk4eIwziTG8Uvf9mMPzZwLGVRNhqS', 'customer', 'active',   '2025-11-20 15:45:00'),
(20, 'meera.vijayan@gmail.com',   '9847001010', '$2y$10$vQZ3oUV8.gWZ8PLqU4nP5eTArk4eIwziTG8Uvf9mMPzZwLGVRNhqS', 'customer', 'active',   '2025-11-22 10:00:00'),
(21, 'suresh.babu@gmail.com',     '9847001011', '$2y$10$vQZ3oUV8.gWZ8PLqU4nP5eTArk4eIwziTG8Uvf9mMPzZwLGVRNhqS', 'customer', 'active',   '2025-11-25 09:15:00'),
(22, 'deepa.nambiar@gmail.com',   '9847001012', '$2y$10$vQZ3oUV8.gWZ8PLqU4nP5eTArk4eIwziTG8Uvf9mMPzZwLGVRNhqS', 'customer', 'active',   '2025-11-28 14:00:00'),
(23, 'rajan.mv@gmail.com',        '9847001013', '$2y$10$vQZ3oUV8.gWZ8PLqU4nP5eTArk4eIwziTG8Uvf9mMPzZwLGVRNhqS', 'customer', 'inactive', '2025-12-01 11:30:00'),
(24, 'kavya.suresh@gmail.com',    '9847001014', '$2y$10$vQZ3oUV8.gWZ8PLqU4nP5eTArk4eIwziTG8Uvf9mMPzZwLGVRNhqS', 'customer', 'active',   '2025-12-03 08:00:00'),
(25, 'nikhil.jose@gmail.com',     '9847001015', '$2y$10$vQZ3oUV8.gWZ8PLqU4nP5eTArk4eIwziTG8Uvf9mMPzZwLGVRNhqS', 'customer', 'active',   '2025-12-05 17:20:00'),
(26, 'reshma.pk@gmail.com',       '9847001016', '$2y$10$vQZ3oUV8.gWZ8PLqU4nP5eTArk4eIwziTG8Uvf9mMPzZwLGVRNhqS', 'customer', 'active',   '2025-12-08 10:10:00'),
(27, 'biju.mathew@gmail.com',     '9847001017', '$2y$10$vQZ3oUV8.gWZ8PLqU4nP5eTArk4eIwziTG8Uvf9mMPzZwLGVRNhqS', 'customer', 'active',   '2025-12-10 13:45:00'),
(28, 'sindhu.raj@yahoo.com',      '9847001018', '$2y$10$vQZ3oUV8.gWZ8PLqU4nP5eTArk4eIwziTG8Uvf9mMPzZwLGVRNhqS', 'customer', 'active',   '2025-12-12 09:30:00'),
(29, 'anil.chandran@gmail.com',   '9847001019', '$2y$10$vQZ3oUV8.gWZ8PLqU4nP5eTArk4eIwziTG8Uvf9mMPzZwLGVRNhqS', 'customer', 'active',   '2025-12-15 11:00:00'),
(30, 'lekha.pillai@gmail.com',    '9847001020', '$2y$10$vQZ3oUV8.gWZ8PLqU4nP5eTArk4eIwziTG8Uvf9mMPzZwLGVRNhqS', 'customer', 'active',   '2025-12-18 14:30:00');

-- Customers (linked to users 11–30 above)
-- profession_ids: 1=IT, 2=Healthcare, 3=Education, 4=Finance, 5=Engineering, 12=Self Employed, 13=Student
INSERT INTO `customers`
  (`id`, `user_id`, `name`, `date_of_birth`, `gender`, `profession_id`,
   `registration_type`, `customer_type`, `subscription_status`, `subscription_expiry`,
   `referral_code`, `referred_by`, `is_dealmaker`, `created_at`)
VALUES
(11, 11, 'Arjun Nair',        '1992-05-14', 'male',   1,    'self_registration',  'dealmaker', 'active',  '2026-11-01', 'REFARJUN001A', NULL, 1, '2025-11-01 09:15:00'),
(12, 12, 'Priya Menon',       '1995-08-22', 'female', 2,    'self_registration',  'premium',   'active',  '2026-11-03', 'REFPRIYA002B', NULL, 0, '2025-11-03 10:30:00'),
(13, 13, 'Rahul Krishna',     '1990-03-10', 'male',   1,    'merchant_app',       'standard',  'none',    NULL,         'REFRAHULOO3C', 11,   0, '2025-11-05 11:00:00'),
(14, 14, 'Divya Thomas',      '1997-11-30', 'female', 3,    'self_registration',  'premium',   'active',  '2026-11-08', 'REFDIVYA004D', 11,   0, '2025-11-08 14:20:00'),
(15, 15, 'Arun Pillai',       '1988-07-04', 'male',   5,    'self_registration',  'standard',  'expired', NULL,         'REFARUNP005E', NULL, 0, '2025-11-10 09:45:00'),
(16, 16, 'Sreelakshmi R',     '1993-01-18', 'female', 6,    'admin_registration', 'standard',  'none',    NULL,         'REFSREELK06F', NULL, 0, '2025-11-12 16:10:00'),
(17, 17, 'Vishnu Kumar',      '1994-09-25', 'male',   1,    'self_registration',  'dealmaker', 'active',  '2026-11-15', 'REFVISHN007G', NULL, 1, '2025-11-15 08:30:00'),
(18, 18, 'Anjali Dev',        '1998-04-12', 'female', 13,   'self_registration',  'standard',  'none',    NULL,         'REFANJAL008H', 12,   0, '2025-11-18 12:00:00'),
(19, 19, 'Santhosh George',   '1986-12-03', 'male',   7,    'merchant_app',       'premium',   'active',  '2026-11-20', 'REFSANTH009I', NULL, 0, '2025-11-20 15:45:00'),
(20, 20, 'Meera Vijayan',     '1991-06-20', 'female', 4,    'self_registration',  'standard',  'none',    NULL,         'REFMEERAV10J', 11,   0, '2025-11-22 10:00:00'),
(21, 21, 'Suresh Babu',       '1983-02-28', 'male',   12,   'preprinted_card',    'standard',  'none',    NULL,         'REFSURESHB11K', NULL, 0, '2025-11-25 09:15:00'),
(22, 22, 'Deepa Nambiar',     '1996-10-07', 'female', 2,    'self_registration',  'premium',   'active',  '2026-11-28', 'REFDEEPAN12L', 17,   0, '2025-11-28 14:00:00'),
(23, 23, 'Rajan MV',          '1979-05-15', 'male',   11,   'admin_registration', 'standard',  'none',    NULL,         'REFRAJAN013M', NULL, 0, '2025-12-01 11:30:00'),
(24, 24, 'Kavya Suresh',      '1999-03-08', 'female', 13,   'self_registration',  'standard',  'none',    NULL,         'REFKAVYA014N', 11,   0, '2025-12-03 08:00:00'),
(25, 25, 'Nikhil Jose',       '1993-08-17', 'male',   1,    'self_registration',  'dealmaker', 'active',  '2026-12-05', 'REFNIKHI015O', NULL, 1, '2025-12-05 17:20:00'),
(26, 26, 'Reshma PK',         '1994-12-22', 'female', 3,    'merchant_app',       'standard',  'none',    NULL,         'REFRSHMA016P', 17,   0, '2025-12-08 10:10:00'),
(27, 27, 'Biju Mathew',       '1987-09-11', 'male',   5,    'self_registration',  'premium',   'active',  '2026-12-10', 'REFBIJUM017Q', NULL, 0, '2025-12-10 13:45:00'),
(28, 28, 'Sindhu Raj',        '1995-07-30', 'female', 6,    'self_registration',  'standard',  'expired', NULL,         'REFSINDR018R', 25,   0, '2025-12-12 09:30:00'),
(29, 29, 'Anil Chandran',     '1980-11-05', 'male',   4,    'admin_registration', 'standard',  'none',    NULL,         'REFANIL019S',  NULL, 0, '2025-12-15 11:00:00'),
(30, 30, 'Lekha Pillai',      '1992-04-14', 'female', 8,    'self_registration',  'premium',   'active',  '2026-12-18', 'REFLEKHA020T', 11,   0, '2025-12-18 14:30:00');

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- SUMMARY
-- 20 customers seeded (user IDs & customer IDs 10-29)
-- Passwords: Admin@123
-- Mix: 3 DealMakers, 7 Premium, 10 Standard
-- Statuses: 17 Active, 1 Blocked, 1 Inactive, 1 Expired subscription
-- Professions: IT (4), Healthcare (2), Education (3), Finance (2),
--              Engineering (2), Sales (2), Business (1), Legal (1),
--              Government (1), Self Employed (1), Student (2)
-- Referrals: several customers referred by Arjun (10), Vishnu (16), Nikhil (24)
-- ============================================================================
