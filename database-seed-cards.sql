-- DealMachan: Card Seed Data
-- 30 demo cards across different variants and statuses
-- Run: Get-Content "e:\DealMachan\database-seed-cards.sql" | & "C:\xampp\mysql\bin\mysql.exe" -u root deal_machan

SET FOREIGN_KEY_CHECKS = 0;

INSERT INTO `cards`
    (card_number, card_variant, is_preprinted, status,
     assigned_to_customer_id, assigned_to_merchant_id,
     generated_at, activated_at, created_at, updated_at)
VALUES

-- Available cards (no assignment)
('DMSTD00000001', 'standard', 0, 'available', NULL, NULL, NOW() - INTERVAL 30 DAY, NULL, NOW() - INTERVAL 30 DAY, NULL),
('DMSTD00000002', 'standard', 0, 'available', NULL, NULL, NOW() - INTERVAL 29 DAY, NULL, NOW() - INTERVAL 29 DAY, NULL),
('DMSTD00000003', 'standard', 1, 'available', NULL, NULL, NOW() - INTERVAL 28 DAY, NULL, NOW() - INTERVAL 28 DAY, NULL),
('DMPRE00000004', 'premium',  0, 'available', NULL, NULL, NOW() - INTERVAL 27 DAY, NULL, NOW() - INTERVAL 27 DAY, NULL),
('DMPRE00000005', 'premium',  0, 'available', NULL, NULL, NOW() - INTERVAL 26 DAY, NULL, NOW() - INTERVAL 26 DAY, NULL),
('DMGOL00000006', 'gold',     1, 'available', NULL, NULL, NOW() - INTERVAL 25 DAY, NULL, NOW() - INTERVAL 25 DAY, NULL),
('DMGOL00000007', 'gold',     0, 'available', NULL, NULL, NOW() - INTERVAL 24 DAY, NULL, NOW() - INTERVAL 24 DAY, NULL),
('DMCOR00000008', 'corporate',0, 'available', NULL, NULL, NOW() - INTERVAL 23 DAY, NULL, NOW() - INTERVAL 23 DAY, NULL),
('DMSTU00000009', 'student',  0, 'available', NULL, NULL, NOW() - INTERVAL 22 DAY, NULL, NOW() - INTERVAL 22 DAY, NULL),
('DMSTU00000010', 'student',  1, 'available', NULL, NULL, NOW() - INTERVAL 21 DAY, NULL, NOW() - INTERVAL 21 DAY, NULL),

-- Assigned to customers (customer IDs from seeded data: 1, 11-24)
('DMSTD00000011', 'standard', 0, 'assigned',  1,  NULL, NOW() - INTERVAL 20 DAY, NULL,                         NOW() - INTERVAL 20 DAY, NOW() - INTERVAL 18 DAY),
('DMSTD00000012', 'standard', 0, 'assigned',  11, NULL, NOW() - INTERVAL 19 DAY, NULL,                         NOW() - INTERVAL 19 DAY, NOW() - INTERVAL 17 DAY),
('DMPRE00000013', 'premium',  0, 'assigned',  12, NULL, NOW() - INTERVAL 18 DAY, NULL,                         NOW() - INTERVAL 18 DAY, NOW() - INTERVAL 16 DAY),
('DMPRE00000014', 'premium',  1, 'assigned',  13, NULL, NOW() - INTERVAL 17 DAY, NULL,                         NOW() - INTERVAL 17 DAY, NOW() - INTERVAL 15 DAY),
('DMGOL00000015', 'gold',     0, 'assigned',  14, NULL, NOW() - INTERVAL 16 DAY, NULL,                         NOW() - INTERVAL 16 DAY, NOW() - INTERVAL 14 DAY),

-- Activated (assigned + activated_at set)
('DMSTD00000016', 'standard', 0, 'activated', 15, NULL, NOW() - INTERVAL 60 DAY, NOW() - INTERVAL 55 DAY,      NOW() - INTERVAL 60 DAY, NOW() - INTERVAL 55 DAY),
('DMSTD00000017', 'standard', 1, 'activated', 16, NULL, NOW() - INTERVAL 58 DAY, NOW() - INTERVAL 52 DAY,      NOW() - INTERVAL 58 DAY, NOW() - INTERVAL 52 DAY),
('DMPRE00000018', 'premium',  0, 'activated', 17, NULL, NOW() - INTERVAL 55 DAY, NOW() - INTERVAL 50 DAY,      NOW() - INTERVAL 55 DAY, NOW() - INTERVAL 50 DAY),
('DMPRE00000019', 'premium',  0, 'activated', 18, NULL, NOW() - INTERVAL 52 DAY, NOW() - INTERVAL 48 DAY,      NOW() - INTERVAL 52 DAY, NOW() - INTERVAL 48 DAY),
('DMGOL00000020', 'gold',     1, 'activated', 19, NULL, NOW() - INTERVAL 50 DAY, NOW() - INTERVAL 45 DAY,      NOW() - INTERVAL 50 DAY, NOW() - INTERVAL 45 DAY),
('DMGOL00000021', 'gold',     0, 'activated', 20, NULL, NOW() - INTERVAL 48 DAY, NOW() - INTERVAL 43 DAY,      NOW() - INTERVAL 48 DAY, NOW() - INTERVAL 43 DAY),
('DMCOR00000022', 'corporate',0, 'activated', 21, NULL, NOW() - INTERVAL 45 DAY, NOW() - INTERVAL 40 DAY,      NOW() - INTERVAL 45 DAY, NOW() - INTERVAL 40 DAY),
('DMSTU00000023', 'student',  0, 'activated', 22, NULL, NOW() - INTERVAL 42 DAY, NOW() - INTERVAL 37 DAY,      NOW() - INTERVAL 42 DAY, NOW() - INTERVAL 37 DAY),

-- Assigned to merchants (merchant IDs 2-5)
('DMCOR00000024', 'corporate',0, 'assigned',  NULL, 2,  NOW() - INTERVAL 15 DAY, NULL,                         NOW() - INTERVAL 15 DAY, NOW() - INTERVAL 13 DAY),
('DMCOR00000025', 'corporate',0, 'assigned',  NULL, 3,  NOW() - INTERVAL 14 DAY, NULL,                         NOW() - INTERVAL 14 DAY, NOW() - INTERVAL 12 DAY),

-- Blocked
('DMSTD00000026', 'standard', 0, 'blocked',   23, NULL, NOW() - INTERVAL 40 DAY, NOW() - INTERVAL 35 DAY,      NOW() - INTERVAL 40 DAY, NOW() - INTERVAL 5 DAY),
('DMPRE00000027', 'premium',  0, 'blocked',   24, NULL, NOW() - INTERVAL 38 DAY, NULL,                         NOW() - INTERVAL 38 DAY, NOW() - INTERVAL 4 DAY),
('DMGOL00000028', 'gold',     0, 'blocked',   NULL, NULL, NOW() - INTERVAL 35 DAY, NULL,                       NOW() - INTERVAL 35 DAY, NOW() - INTERVAL 3 DAY),

-- Recently generated today
('DMSTD00000029', 'standard', 0, 'available', NULL, NULL, NOW(), NULL, NOW(), NULL),
('DMPRE00000030', 'premium',  0, 'available', NULL, NULL, NOW(), NULL, NOW(), NULL);

SET FOREIGN_KEY_CHECKS = 1;

SELECT CONCAT('Cards seeded: ', COUNT(*), ' rows') AS result FROM cards;
