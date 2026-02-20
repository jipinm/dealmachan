-- DealMachan: Coupon Seed Data
-- Merchants: IDs 2-16, merchant user IDs 31-45 (created_by uses admin user id=1)
-- Run: Get-Content "e:\DealMachan\database-seed-coupons.sql" | & "C:\xampp\mysql\bin\mysql.exe" -u root deal_machan

SET FOREIGN_KEY_CHECKS = 0;

-- ─── COUPONS ─────────────────────────────────────────────────────────────────

INSERT INTO `coupons`
    (title, description, coupon_code, discount_type, discount_value,
     min_purchase_amount, max_discount_amount,
     merchant_id, store_id,
     valid_from, valid_until, usage_limit, usage_count,
     is_admin_coupon, approval_status, approved_by_admin_id, approved_at,
     status, terms_conditions, created_by, created_at, updated_at)
VALUES

-- 1: Spice Garden — 20% off, approved, active
('Spice Garden Summer Sale', 'Get 20% off on your dining bill at Spice Garden restaurants.',
 'SPICE20', 'percentage', 20.00, 500.00, 300.00,
 2, NULL,
 '2025-04-01 00:00:00', '2025-08-31 23:59:59', 500, 47,
 0, 'approved', 1, '2025-04-01 10:00:00',
 'active', 'Valid on dine-in only. Cannot be combined with other offers.',
 1, NOW() - INTERVAL 90 DAY, NOW() - INTERVAL 90 DAY),

-- 2: Spice Garden — flat ₹100 off
('Dine & Save ₹100', 'Flat ₹100 off on bills above ₹700 at Spice Garden.',
 'DINE100', 'fixed', 100.00, 700.00, NULL,
 2, NULL,
 '2025-05-01 00:00:00', '2025-12-31 23:59:59', 200, 18,
 0, 'approved', 1, '2025-05-01 09:00:00',
 'active', 'Valid for dine-in and takeaway. Not valid on holidays.',
 1, NOW() - INTERVAL 60 DAY, NOW() - INTERVAL 60 DAY),

-- 3: Kerala Fashions — 15% off approx, pending
('Festive Fashion 15%', '15% discount on all ethnic wear this season.',
 'FEST15', 'percentage', 15.00, 1000.00, 500.00,
 3, NULL,
 '2025-06-01 00:00:00', '2025-09-30 23:59:59', 300, 0,
 0, 'pending', NULL, NULL,
 'active', 'Valid on ethnic wear collection only. Applicable on MRP.',
 1, NOW() - INTERVAL 10 DAY, NOW() - INTERVAL 10 DAY),

-- 4: Malabar Sweets — ₹50 off on ₹400
('Sweet Deal ₹50 Off', '₹50 off on sweets purchase above ₹400.',
 'SWEET50', 'fixed', 50.00, 400.00, NULL,
 4, NULL,
 '2025-01-01 00:00:00', '2025-06-30 23:59:59', 1000, 213,
 0, 'approved', 1, '2025-01-05 11:00:00',
 'expired', 'Valid on boxed sweets only. Not on loose items.',
 1, NOW() - INTERVAL 180 DAY, NOW() - INTERVAL 5 DAY),

-- 5: Kerala Electronics — 10% off electronics, admin coupon
('Deal Machan Tech 10%', 'Exclusive 10% discount on electronics for Deal Machan members.',
 'DMTECH10', 'percentage', 10.00, 2000.00, 1500.00,
 5, NULL,
 '2025-05-15 00:00:00', '2025-11-15 23:59:59', 100, 8,
 1, 'approved', 1, '2025-05-15 10:00:00',
 'active', 'Valid on branded electronics only. One coupon per customer.',
 1, NOW() - INTERVAL 45 DAY, NOW() - INTERVAL 45 DAY),

-- 6: TechZone Kochi — ₹500 off on ₹5000
('TechZone Mega Savings', '₹500 flat off on purchases above ₹5000.',
 'TECH500', 'fixed', 500.00, 5000.00, NULL,
 5, NULL,
 '2025-03-01 00:00:00', '2025-10-31 23:59:59', 50, 12,
 0, 'approved', 1, '2025-03-02 09:00:00',
 'active', 'Applicable on laptops, mobiles and large appliances only.',
 1, NOW() - INTERVAL 120 DAY, NOW() - INTERVAL 120 DAY),

-- 7: Ayurvedic Wellness — 25% wellness discount, admin
('Wellness Wednesday 25%', 'Every Wednesday get 25% off on all wellness treatments.',
 'WELLNESS25', 'percentage', 25.00, 500.00, 750.00,
 6, NULL,
 '2025-06-01 00:00:00', '2025-12-31 23:59:59', 200, 0,
 1, 'approved', 1, '2025-06-01 08:00:00',
 'active', 'Valid only on Wednesdays. Advance booking required.',
 1, NOW() - INTERVAL 5 DAY, NOW() - INTERVAL 5 DAY),

-- 8: Royal Café — ₹75 off
('Café Morning Treat', '₹75 off on breakfast combos above ₹350.',
 'CAFE75', 'fixed', 75.00, 350.00, NULL,
 7, NULL,
 '2025-05-01 00:00:00', '2025-09-30 23:59:59', 500, 34,
 0, 'approved', 1, '2025-05-03 10:00:00',
 'active', 'Valid 7am–12pm only. Not applicable on weekends.',
 1, NOW() - INTERVAL 55 DAY, NOW() - INTERVAL 55 DAY),

-- 9: Thrissur Textiles — rejected
('Thrissur Grand Discount 30%', '30% off on silk sarees.',
 'SILK30', 'percentage', 30.00, 2000.00, 1000.00,
 8, NULL,
 NULL, NULL, 100, 0,
 0, 'rejected', NULL, NULL,
 'inactive', NULL,
 1, NOW() - INTERVAL 20 DAY, NOW() - INTERVAL 15 DAY),

-- 10: Calicut Biryani — 18% off, admin
('Biryani Bonanza 18%', 'Special 18% off on biryani family packs.',
 'BIRYAN18', 'percentage', 18.00, 600.00, 400.00,
 9, NULL,
 '2025-06-15 00:00:00', '2025-12-15 23:59:59', 300, 5,
 1, 'approved', 1, '2025-06-16 09:00:00',
 'active', 'Valid on family pack only (min 4 plates). Takeaway only.',
 1, NOW() - INTERVAL 3 DAY, NOW() - INTERVAL 3 DAY),

-- 11: Trivandrum Mobiles — ₹200 off on ₹3000
('Mobile Deal ₹200 Off', '₹200 off on mobile accessories above ₹3000.',
 'MOB200', 'fixed', 200.00, 3000.00, NULL,
 10, NULL,
 '2025-04-01 00:00:00', '2025-07-31 23:59:59', 150, 66,
 0, 'approved', 1, '2025-04-02 10:00:00',
 'expired', 'Not valid on Apple accessories.',
 1, NOW() - INTERVAL 100 DAY, NOW() - INTERVAL 2 DAY),

-- 12: Cochin Furniture — 12% off, pending
('Home Refresh 12%', '12% off on modular furniture.',
 'FURN12', 'percentage', 12.00, 5000.00, 3000.00,
 11, NULL,
 '2025-07-01 00:00:00', '2025-12-31 23:59:59', 50, 0,
 0, 'pending', NULL, NULL,
 'active', 'Valid on modular kitchen and wardrobe products only.',
 1, NOW() - INTERVAL 1 DAY, NOW() - INTERVAL 1 DAY),

-- 13: Kannur Bakery — ₹40 flat, approved
('Bakery Bite ₹40 Off', '₹40 off on cake orders above ₹250.',
 'CAKE40', 'fixed', 40.00, 250.00, NULL,
 12, NULL,
 '2025-05-01 00:00:00', '2025-08-31 23:59:59', 1000, 89,
 0, 'approved', 1, '2025-05-01 08:30:00',
 'active', NULL,
 1, NOW() - INTERVAL 65 DAY, NOW() - INTERVAL 65 DAY),

-- 14: Kochi Spa — 20% off spa treatments, admin
('Spa & Relax 20%', '20% off on spa and massage packages.',
 'SPA20', 'percentage', 20.00, 1000.00, 800.00,
 13, NULL,
 '2025-06-01 00:00:00', '2025-10-31 23:59:59', 100, 3,
 1, 'approved', 1, '2025-06-01 09:00:00',
 'active', 'Advance appointment required. Valid Mon–Thu.',
 1, NOW() - INTERVAL 8 DAY, NOW() - INTERVAL 8 DAY),

-- 15: Sport Store — ₹300 off on ₹2000
('Sports Fiesta ₹300 Off', '₹300 off on sports equipment above ₹2000.',
 'SPORT300', 'fixed', 300.00, 2000.00, NULL,
 14, NULL,
 '2025-07-04 00:00:00', '2026-01-04 23:59:59', 75, 0,
 0, 'approved', 1, '2025-07-04 10:00:00',
 'active', 'Not valid on cricket kits. One per customer.',
 1, NOW(), NOW());

-- ─── COUPON TAGS ─────────────────────────────────────────────────────────────
-- Tag IDs assumed: food=1, fashion=2, electronics=3, wellness=4, home=5
-- (Only insert if those tag IDs exist — gracefully ignore FK errors)

INSERT IGNORE INTO `coupon_tags` (coupon_id, tag_id, created_at) VALUES
((SELECT id FROM coupons WHERE coupon_code='SPICE20'),  1, NOW()),
((SELECT id FROM coupons WHERE coupon_code='DINE100'),  1, NOW()),
((SELECT id FROM coupons WHERE coupon_code='FEST15'),   2, NOW()),
((SELECT id FROM coupons WHERE coupon_code='DMTECH10'), 3, NOW()),
((SELECT id FROM coupons WHERE coupon_code='TECH500'),  3, NOW()),
((SELECT id FROM coupons WHERE coupon_code='WELLNESS25'),4, NOW()),
((SELECT id FROM coupons WHERE coupon_code='SPA20'),    4, NOW()),
((SELECT id FROM coupons WHERE coupon_code='FURN12'),   5, NOW());

SET FOREIGN_KEY_CHECKS = 1;

SELECT CONCAT('Coupons seeded: ', COUNT(*), ' rows') AS result FROM coupons;
