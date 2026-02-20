-- ============================================================
-- Merchant Management Seed Data
-- 15 Kerala merchants, user IDs 31-45, merchant IDs 2-16
-- Password for all: Merchant@123 (same bcrypt hash as test user)
-- Run: mysql -u root deal_machan < database-seed-merchants.sql
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ── users ─────────────────────────────────────────────────────────────────
INSERT INTO users (id, email, phone, password_hash, user_type, status, created_at) VALUES
(31, 'spice.garden@kochi.com',    '9400100031', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'merchant', 'active',   '2025-08-01 09:00:00'),
(32, 'royal.textiles@tvm.com',    '9400100032', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'merchant', 'active',   '2025-08-05 10:30:00'),
(33, 'kerala.sweets@kozhikode.com','9400100033', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'merchant', 'active',   '2025-08-10 11:00:00'),
(34, 'fitness.zone@kochi.com',    '9400100034', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'merchant', 'active',   '2025-08-15 09:45:00'),
(35, 'medplus.pharmacy@tvm.com',  '9400100035', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'merchant', 'active',   '2025-08-20 14:00:00'),
(36, 'cafe.mocha@kochi.com',      '9400100036', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'merchant', 'active',   '2025-09-01 08:30:00'),
(37, 'star.electronics@tvm.com',  '9400100037', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'merchant', 'blocked',  '2025-09-05 10:00:00'),
(38, 'malabar.biriyani@kozhikode.com','9400100038','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','merchant','active', '2025-09-10 12:00:00'),
(39, 'golden.jewels@kochi.com',   '9400100039', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'merchant', 'active',   '2025-09-15 09:15:00'),
(40, 'ayur.wellness@tvm.com',     '9400100040', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'merchant', 'active',   '2025-09-20 11:30:00'),
(41, 'surf.salon@kochi.com',      '9400100041', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'merchant', 'active',   '2025-10-01 10:00:00'),
(42, 'paradise.resort@alleppey.com','9400100042','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','merchant','active',  '2025-10-05 09:00:00'),
(43, 'sunrise.bakery@kozhikode.com','9400100043','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','merchant','active',  '2025-10-10 08:00:00'),
(44, 'techmart.mobiles@kochi.com','9400100044', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'merchant', 'active',   '2025-10-15 09:30:00'),
(45, 'kerala.books@tvm.com',      '9400100045', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'merchant', 'inactive', '2025-10-18 11:00:00');

-- ── merchants ─────────────────────────────────────────────────────────────
-- Columns: id, user_id, business_name, registration_number, gst_number, 
--          is_premium, label_id, subscription_status, subscription_expiry,
--          profile_status, priority_weight, created_at
INSERT INTO merchants (id, user_id, business_name, registration_number, gst_number, is_premium, label_id, subscription_status, subscription_expiry, profile_status, priority_weight, created_at) VALUES
(2,  31, 'Spice Garden Restaurant',  'REG-KL-2023-001', '32AADCB1234F1ZX', 1, NULL, 'active',  '2026-08-01', 'approved', 100, '2025-08-01 09:00:00'),
(3,  32, 'Royal Textiles & Sarees',  'REG-KL-2023-002', '32AADCB1235F1ZX', 1, NULL, 'active',  '2026-06-30', 'approved', 90,  '2025-08-05 10:30:00'),
(4,  33, 'Kerala Sweets Palace',     'REG-KL-2023-003', NULL,              0, NULL, 'active',  '2026-05-31', 'approved', 70,  '2025-08-10 11:00:00'),
(5,  34, 'FitZone Gym & Spa',        'REG-KL-2023-004', '32AADCB1237F1ZX', 1, NULL, 'active',  '2026-07-15', 'approved', 85,  '2025-08-15 09:45:00'),
(6,  35, 'MedPlus Pharmacy',         'REG-KL-2023-005', '32AADCB1238F1ZX', 0, NULL, 'active',  '2026-04-30', 'approved', 60,  '2025-08-20 14:00:00'),
(7,  36, 'Café Mocha',               NULL,              NULL,              0, NULL, 'trial',   NULL,         'approved', 40,  '2025-09-01 08:30:00'),
(8,  37, 'Star Electronics Hub',     'REG-KL-2023-007', '32AADCB1240F1ZX', 0, NULL, 'expired', '2025-10-31', 'approved', 30,  '2025-09-05 10:00:00'),
(9,  38, 'Malabar Biriyani House',   'REG-KL-2023-008', NULL,              1, NULL, 'active',  '2026-09-10', 'approved', 95,  '2025-09-10 12:00:00'),
(10, 39, 'Golden Jewels Kochi',      'REG-KL-2023-009', '32AADCB1242F1ZX', 1, NULL, 'active',  '2026-09-15', 'approved', 80,  '2025-09-15 09:15:00'),
(11, 40, 'Ayur Wellness Centre',     'REG-KL-2023-010', NULL,              0, NULL, 'trial',   NULL,         'pending',  0,   '2025-09-20 11:30:00'),
(12, 41, 'Surf Salon & Beauty',      NULL,              NULL,              0, NULL, 'trial',   NULL,         'pending',  0,   '2025-10-01 10:00:00'),
(13, 42, 'Paradise Backwater Resort','REG-KL-2023-012', '32AADCB1245F1ZX', 1, NULL, 'active',  '2026-10-05', 'approved', 75,  '2025-10-05 09:00:00'),
(14, 43, 'Sunrise Bakers',           'REG-KL-2023-013', NULL,              0, NULL, 'active',  '2026-03-31', 'approved', 50,  '2025-10-10 08:00:00'),
(15, 44, 'TechMart Mobiles & Gadgets','REG-KL-2023-014','32AADCB1247F1ZX', 0, NULL, 'trial',   NULL,         'pending',  0,   '2025-10-15 09:30:00'),
(16, 45, 'Kerala Books & Stationery','REG-KL-2023-015', NULL,              0, NULL, 'trial',   NULL,         'rejected', 0,   '2025-10-18 11:00:00');

-- ── stores ─────────────────────────────────────────────────────────────────
-- city_id: 1=TVM, 2=Kochi, 3=Kozhikode, 7=Alappuzha
-- area examples: TVM=1-10, Kochi=11-22, Kozhikode=23-30
INSERT INTO stores (id, merchant_id, store_name, address, city_id, area_id, phone, email, status, created_at) VALUES
(2,  2,  'Spice Garden - Ernakulam',    '12 Market Road, Ernakulam',           2, 11, '9400200001', 'ernakulam@spicegarden.com', 'active', '2025-08-01 10:00:00'),
(3,  2,  'Spice Garden - MG Road',      '45 MG Road, Kochi',                   2, 12, '9400200002', 'mgroad@spicegarden.com',    'active', '2025-08-10 10:00:00'),
(4,  3,  'Royal Textiles - Pattom',     '78 Pattom Main Road, TVM',            1,  1, '9400200003', NULL,                        'active', '2025-08-05 11:00:00'),
(5,  4,  'Kerala Sweets - Palayam',     '30 Palayam, Kozhikode',               3, 24, '9400200004', NULL,                        'active', '2025-08-10 12:00:00'),
(6,  5,  'FitZone - Kakkanad',          '88 Info Park Road, Kakkanad, Kochi',  2, 13, '9400200005', 'kakkanad@fitzone.com',      'active', '2025-08-15 10:00:00'),
(7,  6,  'MedPlus - Thampanoor',        '15 Thampanoor, TVM',                  1,  5, '9400200006', NULL,                        'active', '2025-08-20 15:00:00'),
(8,  7,  'Café Mocha - Vyttila',        '22 Vyttila Hub, Kochi',               2, 16, '9400200007', NULL,                        'active', '2025-09-01 09:00:00'),
(9,  8,  'Star Electronics - Ulloor',   '55 Ulloor, TVM',                      1,  9, '9400200008', NULL,                        'active', '2025-09-05 11:00:00'),
(10, 9,  'Malabar Biriyani - Mavoor',   '18 Mavoor Road, Kozhikode',           3, 23, '9400200009', NULL,                        'active', '2025-09-10 13:00:00'),
(11, 9,  'Malabar Biriyani - Hilite',   'Hilite Mall, Kozhikode',              3, 25, '9400200010', NULL,                        'active', '2025-09-12 09:00:00'),
(12, 10, 'Golden Jewels - Marine Drive','14 Marine Drive, Kochi',              2, 18, '9400200011', 'info@goldenjewels.com',     'active', '2025-09-15 10:00:00'),
(13, 13, 'Paradise Resort - Alleppey',  'Near Alappuzha Jetty, Alleppey',      7, NULL, '9400200012', 'info@paradiseresort.com',  'active', '2025-10-05 10:00:00'),
(14, 14, 'Sunrise Bakers - Beach Road', '5 Beach Road, Kozhikode',             3, 29, '9400200013', NULL,                        'active', '2025-10-10 09:00:00'),
(15, 14, 'Sunrise Bakers - Ramanattukara','12 Ramanattukara, Kozhikode',       3, 27, '9400200014', NULL,                        'active', '2025-10-12 09:00:00');

-- Fix the store with NULL area_id for Paradise Resort (Alappuzha may not have area rows)
-- Use area_id from Alappuzha if available, else set a safe default
UPDATE stores SET area_id = (
    SELECT id FROM areas WHERE city_id = 7 LIMIT 1
) WHERE id = 13 AND area_id IS NULL;

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'Merchants seeded: ' AS info, COUNT(*) AS count FROM merchants WHERE id > 1;
SELECT 'Merchant stores: '  AS info, COUNT(*) AS count FROM stores       WHERE id > 1;
