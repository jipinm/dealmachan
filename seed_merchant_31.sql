-- ============================================================
-- SEED DATA for merchant admin@example.com
-- users.id = 95 | merchants.id = 31 | business_name = 'Demo Merchant'
-- Run: mysql -u root -p deal_machan < seed_merchant_31.sql
-- ============================================================

USE `deal_machan`;

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. STORES  (2 stores for merchant_id=31 in Kochi)
--    city_id=2 = Kochi | area_id=11 = Ernakulam | area_id=12 = MG Road
-- ============================================================
INSERT INTO `stores`
  (`id`, `merchant_id`, `store_name`, `address`, `city_id`, `area_id`, `location_id`,
   `phone`, `email`, `latitude`, `longitude`, `opening_hours`, `description`, `status`,
   `created_at`, `updated_at`, `deleted_at`)
VALUES
  (49, 31, 'Demo Merchant - Ernakulam',
   '42 Darbar Hall Road, Ernakulam, Kochi', 2, 11, NULL,
   '9400319001', 'ernakulam@demomerchant.com', 9.98000000, 76.28000000,
   '{"Monday":{"open":"09:00","close":"21:00"},"Tuesday":{"open":"09:00","close":"21:00"},"Wednesday":{"open":"09:00","close":"21:00"},"Thursday":{"open":"09:00","close":"21:00"},"Friday":{"open":"09:00","close":"22:00"},"Saturday":{"open":"10:00","close":"22:00"},"Sunday":{"open":"10:00","close":"20:00"}}',
   'Demo Merchant flagship store in Ernakulam. Wide range of products with exclusive deals for DealMachan members.',
   'active', '2026-02-19 13:46:50', NULL, NULL),

  (50, 31, 'Demo Merchant - MG Road',
   '15 MG Road, Near Lulu Mall, Kochi', 2, 12, NULL,
   '9400319002', 'mgroad@demomerchant.com', 9.97000000, 76.29000000,
   '{"Monday":{"open":"10:00","close":"22:00"},"Tuesday":{"open":"10:00","close":"22:00"},"Wednesday":{"open":"10:00","close":"22:00"},"Thursday":{"open":"10:00","close":"22:00"},"Friday":{"open":"10:00","close":"23:00"},"Saturday":{"open":"10:00","close":"23:00"},"Sunday":{"open":"closed","close":"closed"}}',
   'Premium MG Road outlet with exclusive weekend offers and a modern shopping experience.',
   'active', '2026-02-19 13:46:50', NULL, NULL);


-- ============================================================
-- 2. STORE GALLERY  (3 images per store = 6 total)
-- ============================================================
INSERT INTO `store_gallery`
  (`id`, `store_id`, `image_url`, `caption`, `display_order`, `created_at`, `is_cover`, `merchant_id`)
VALUES
  (120, 49, '/uploads/gallery/gallery_120.jpg', 'Storefront',     1, '2026-02-19 13:46:50', 1, 31),
  (121, 49, '/uploads/gallery/gallery_121.jpg', 'Interior View',  2, '2026-02-19 13:46:50', 0, 31),
  (122, 49, '/uploads/gallery/gallery_122.jpg', 'Product Display',3, '2026-02-19 13:46:50', 0, 31),
  (123, 50, '/uploads/gallery/gallery_123.jpg', 'Storefront',     1, '2026-02-19 13:46:50', 1, 31),
  (124, 50, '/uploads/gallery/gallery_124.jpg', 'Interior View',  2, '2026-02-19 13:46:50', 0, 31),
  (125, 50, '/uploads/gallery/gallery_125.jpg', 'Product Display',3, '2026-02-19 13:46:50', 0, 31);


-- ============================================================
-- 3. COUPONS  (4 coupons for merchant_id=31, created_by=95)
--    59, 60 = approved/active | 61, 62 = pending approval
-- ============================================================
INSERT INTO `coupons`
  (`id`, `title`, `description`, `coupon_code`, `discount_type`, `discount_value`,
   `min_purchase_amount`, `max_discount_amount`, `merchant_id`, `store_id`,
   `valid_from`, `valid_until`, `usage_limit`, `usage_count`, `is_admin_coupon`,
   `approval_status`, `approved_by_admin_id`, `approved_at`, `status`,
   `terms_conditions`, `banner_image`, `created_by`, `created_at`, `updated_at`)
VALUES
  (59, 'Demo Welcome Offer - 10% Off',
   'Get 10% off on your first purchase at Demo Merchant. Show this coupon at checkout.',
   'DEMO10', 'percentage', 10.00, 300.00, 500.00, 31, NULL,
   '2026-02-20 18:30:00', '2026-12-31 18:29:59', 100, 3, 0,
   'approved', 1, '2026-02-21 10:00:00', 'active',
   'Valid on first purchase only. Cannot be combined with other offers.',
   'uploads/coupon-banners/food.jpg', 95, '2026-02-20 13:46:50', '2026-02-22 05:53:41'),

  (60, 'Demo Flat Rs.100 Off',
   'Flat Rs.100 discount on purchases above Rs.750 at Demo Merchant Ernakulam.',
   'DEMO100', 'fixed', 100.00, 750.00, NULL, 31, 49,
   '2026-02-20 18:30:00', '2026-06-30 18:29:59', 50, 2, 0,
   'approved', 1, '2026-02-21 10:00:00', 'active',
   'Valid at Ernakulam branch only. One coupon per customer per visit.',
   'uploads/coupon-banners/food.jpg', 95, '2026-02-20 13:46:50', '2026-02-22 05:53:41'),

  (61, 'Demo Weekend Special - 15% Off',
   'Enjoy 15% off every weekend at Demo Merchant MG Road branch.',
   'DEMOWK15', 'percentage', 15.00, 500.00, 300.00, 31, 50,
   '2026-02-20 18:30:00', '2026-09-30 18:29:59', 200, 0, 0,
   'pending', NULL, NULL, 'active',
   'Valid on Saturdays and Sundays only. Not applicable on already discounted items.',
   'uploads/coupon-banners/food.jpg', 95, '2026-02-21 09:00:00', NULL),

  (62, 'Demo Exclusive - Rs.200 Off',
   'Get Rs.200 off on purchases above Rs.1500 at any Demo Merchant outlet.',
   'DEMO200', 'fixed', 200.00, 1500.00, NULL, 31, NULL,
   '2026-03-01 18:30:00', '2026-07-31 18:29:59', 75, 0, 0,
   'pending', NULL, NULL, 'active',
   'Valid at all Demo Merchant outlets. One per customer.',
   'uploads/coupon-banners/food.jpg', 95, '2026-02-21 11:00:00', NULL);


-- ============================================================
-- 4. STORE COUPONS  (store-specific printable coupon cards)
-- ============================================================
INSERT INTO `store_coupons`
  (`id`, `merchant_id`, `store_id`, `coupon_code`, `discount_type`, `discount_value`,
   `valid_from`, `valid_until`, `is_gifted`, `gifted_to_customer_id`, `gifted_at`,
   `is_redeemed`, `redeemed_at`, `status`, `created_at`, `updated_at`)
VALUES
  (21, 31, 49, 'DEMOE10',   'percentage', 10.00, '2026-02-20 18:30:00', '2026-08-31 18:29:59', 0, NULL, NULL, 0, NULL, 'active', '2026-02-20 13:46:50', NULL),
  (22, 31, 49, 'DEMOE50',   'fixed',      50.00, '2026-02-20 18:30:00', '2026-06-30 18:29:59', 0, NULL, NULL, 0, NULL, 'active', '2026-02-20 13:46:50', NULL),
  (23, 31, 50, 'DEMOMG15G', 'percentage', 15.00, '2026-02-20 18:30:00', '2026-09-30 18:29:59', 1,  5,  '2026-02-22 10:00:00', 0, NULL, 'active', '2026-02-22 10:00:00', NULL),
  (24, 31, 50, 'DEMOMG100', 'fixed',     100.00, '2026-02-20 18:30:00', '2026-05-31 18:29:59', 0, NULL, NULL, 0, NULL, 'active', '2026-02-20 13:46:50', NULL);


-- ============================================================
-- 5. FLASH DISCOUNTS  (2 active + 1 expired for merchant_id=31)
-- ============================================================
INSERT INTO `flash_discounts`
  (`id`, `merchant_id`, `store_id`, `title`, `description`,
   `discount_percentage`, `valid_from`, `valid_until`, `max_redemptions`,
   `current_redemptions`, `status`, `banner_image`, `created_at`, `updated_at`)
VALUES
  (23, 31, 49, 'Demo Launch Flash - 30% Off',
   'Grand launch celebration! Flat 30% off on all products at Demo Merchant Ernakulam today only.',
   30.00, '2026-02-22 06:00:00', '2026-02-25 22:00:00', 50, 7,
   'active', 'uploads/coupon-banners/food.jpg', '2026-02-22 06:00:00', NULL),

  (24, 31, 50, 'Demo MG Road Evening Flash',
   '20% off on selected items every evening 5PM-8PM at MG Road outlet.',
   20.00, '2026-02-22 11:30:00', '2026-04-30 14:30:00', 100, 12,
   'active', 'uploads/coupon-banners/food.jpg', '2026-02-22 11:30:00', NULL),

  (25, 31, 49, 'Demo Opening Week Special',
   'Opening week flash deal - 25% off on all items. Limited slots!',
   25.00, '2026-02-10 06:00:00', '2026-02-17 22:00:00', 40, 40,
   'expired', 'uploads/coupon-banners/food.jpg', '2026-02-10 06:00:00', '2026-02-22 05:53:41');


-- ============================================================
-- 6. REVIEWS  (6 reviews across both stores for merchant_id=31)
-- ============================================================
INSERT INTO `reviews`
  (`id`, `customer_id`, `merchant_id`, `store_id`, `rating`, `review_text`, `status`, `created_at`, `updated_at`)
VALUES
  (61,  5, 31, 49, 5, 'Absolutely amazing experience! The staff were super helpful and the products were top quality. Will definitely come back.',              'approved', '2026-02-18 13:53:35', '2026-02-18 13:53:35'),
  (62, 11, 31, 50, 4, 'Very good overall experience at MG Road branch. Quick service and nice ambience. Slightly pricey but worth it.',                        'approved', '2026-02-19 13:53:35', '2026-02-19 13:53:35'),
  (63, 18, 31, 49, 3, 'Decent place but could improve the waiting time. Products are good quality though.',                                                    'approved', '2026-02-20 13:53:35', '2026-02-20 13:53:35'),
  (64, 23, 31, 50, 5, 'Outstanding quality and service! The DealMachan coupon worked perfectly. Great value for money.',                                       'approved', '2026-02-21 13:53:35', '2026-02-21 13:53:35'),
  (65, 30, 31, 49, 2, 'Experience was below expectations. Coupon discount took too long to process and staff seemed unaware of the offer.',                    'pending',  '2026-02-22 13:53:35', '2026-02-22 13:53:35'),
  (66, 45, 31, 50, 4, 'Good experience overall. Clean store, polite staff, and the weekend offer was excellent. Recommended!',                                 'pending',  '2026-02-23 13:53:35', '2026-02-23 13:53:35');


-- ============================================================
-- 7. GRIEVANCES  (3 grievances from customers against merchant_id=31)
-- ============================================================
INSERT INTO `grievances`
  (`id`, `customer_id`, `merchant_id`, `store_id`, `subject`, `description`,
   `status`, `priority`, `created_at`, `resolved_at`, `resolution_notes`)
VALUES
  (43, 14, 31, 49,
   'Coupon discount not applied at counter',
   'I presented a valid DealMachan coupon for 10% off but the cashier said their system does not accept it. I was charged the full amount. Please resolve this.',
   'open', 'high', '2026-02-20 10:00:00', NULL, NULL),

  (44, 22, 31, 50,
   'Wrong product delivered',
   'I ordered 2 items but received 1 incorrect item. When I raised the issue with staff they said they could not help without a receipt even though I had the DealMachan app order history.',
   'in_progress', 'medium', '2026-02-21 09:30:00', NULL, NULL),

  (45, 37, 31, 49,
   'Long waiting time at billing',
   'Had to wait over 25 minutes at the billing counter during a non-peak hour. Only one counter was open. This is unacceptable for a premium outlet.',
   'resolved', 'low', '2026-02-15 14:00:00', '2026-02-17 10:00:00',
   'Management reviewed and assigned additional billing staff during peak hours. Customer acknowledged the improvement.');


-- ============================================================
-- 8. MESSAGES  (merchant user_id=95 <-> admin)
-- ============================================================
INSERT INTO `messages`
  (`id`, `sender_id`, `sender_type`, `receiver_id`, `receiver_type`,
   `subject`, `message_text`, `parent_message_id`, `read_status`, `read_at`, `sent_at`)
VALUES
  (9, 95, 'merchant', 1, 'admin',
   'Query about coupon approval process',
   'Hello,\n\nI created two coupons (DEMOWK15 and DEMO200) but they are still in pending approval status. Could you please let me know how long the approval process takes and if any additional information is required from my side?\n\nThank you,\nDemo Merchant',
   NULL, 0, NULL, '2026-02-21 12:00:00'),

  (10, 1, 'admin', 95, 'merchant',
   'RE: Query about coupon approval process',
   'Hello,\n\nThank you for reaching out. Coupon approvals typically take 24-48 hours. Your coupons DEMOWK15 and DEMO200 are currently under review and will be notified once approved.\n\nBest regards,\nDealMachan Admin',
   9, 1, '2026-02-21 15:00:00', '2026-02-21 14:30:00');


-- ============================================================
-- 9. NOTIFICATIONS  (for user_id=95 = merchant admin@example.com)
-- ============================================================
INSERT INTO `notifications`
  (`id`, `user_id`, `user_type`, `notification_type`, `title`, `message`,
   `action_url`, `read_status`, `read_at`, `created_at`)
VALUES
  (38, 95, 'merchant', 'success',
   'Profile Approved!',
   'Congratulations! Your merchant profile has been approved. You can now start adding coupons and flash deals.',
   '/merchant/profile', 1, '2026-02-20 09:00:00', '2026-02-20 08:30:00'),

  (39, 95, 'merchant', 'info',
   'Store Setup Reminder',
   'Complete your store setup by adding photos and opening hours to attract more customers.',
   '/merchant/stores', 0, NULL, '2026-02-21 09:00:00'),

  (40, 95, 'merchant', 'warning',
   'Coupons Pending Approval',
   'Your coupons "Demo Weekend Special" (DEMOWK15) and "Demo Exclusive" (DEMO200) are pending admin approval.',
   '/merchant/coupons', 0, NULL, '2026-02-21 10:00:00'),

  (41, 95, 'merchant', 'success',
   'Coupons Approved',
   'Your coupons DEMO10 and DEMO100 have been approved and are now live for customers!',
   '/merchant/coupons', 1, '2026-02-22 10:30:00', '2026-02-22 10:00:00');


-- ============================================================
-- 10. MERCHANT LABELS  (assign Verified Partner + New Partner + Trending)
--     label 1=Verified Partner, 4=New Partner, 5=Trending
-- ============================================================
INSERT INTO `merchant_labels`
  (`id`, `merchant_id`, `label_id`, `assigned_at`, `assigned_by_admin_id`)
VALUES
  (48, 31, 1, '2026-02-21 10:00:00', 1),
  (49, 31, 4, '2026-02-21 10:00:00', 1),
  (50, 31, 5, '2026-02-21 10:00:00', 1);


-- ============================================================
-- 11. MERCHANT TAGS  (tag_id=1 food/restaurant, 3=fitness, 12=shopping)
-- ============================================================
INSERT INTO `merchant_tags`
  (`id`, `merchant_id`, `tag_id`, `created_at`)
VALUES
  (57, 31,  1, '2026-02-21 10:00:00'),
  (58, 31,  3, '2026-02-21 10:00:00'),
  (59, 31, 12, '2026-02-21 10:00:00');


-- ============================================================
-- 12. SALES REGISTRY  (purchase transactions at Demo Merchant stores)
-- ============================================================
INSERT INTO `sales_registry`
  (`id`, `merchant_id`, `store_id`, `customer_id`, `transaction_amount`,
   `transaction_date`, `payment_method`, `coupon_used`, `discount_amount`)
VALUES
  (75, 31, 49,  5, 1850.00, '2026-02-20 11:30:00', 'upi',   59, 185.00),
  (76, 31, 49, 11,  750.00, '2026-02-20 14:00:00', 'cash',  60, 100.00),
  (77, 31, 50, 18, 2300.00, '2026-02-21 16:00:00', 'card',  NULL, 0.00),
  (78, 31, 50, 23, 3100.00, '2026-02-22 12:30:00', 'upi',   NULL, 0.00),
  (79, 31, 49, 30, 1200.00, '2026-02-22 15:00:00', 'wallet',NULL, 0.00),
  (80, 31, 50, 45, 4500.00, '2026-02-23 11:00:00', 'card',  61,  675.00);


-- ============================================================
-- 13. COUPON REDEMPTIONS  (redemptions of Demo Merchant coupons)
-- ============================================================
INSERT INTO `coupon_redemptions`
  (`id`, `coupon_id`, `customer_id`, `store_id`, `redeemed_at`,
   `redemption_location`, `discount_amount`, `transaction_amount`, `verified_by_merchant`)
VALUES
  (89, 59,  5, 49, '2026-02-20 11:30:00', 'At Counter',    185.00, 1850.00, 1),
  (90, 60, 11, 49, '2026-02-20 14:00:00', 'At Counter',    100.00,  750.00, 1),
  (91, 59, 18, 49, '2026-02-21 10:00:00', 'In-Store',      120.00, 1200.00, 0);


SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- END OF SEED  (merchant_id=31 / user_id=95 / admin@example.com)
-- Summary of inserted records:
--   stores           : 2  (ids 49–50)
--   store_gallery    : 6  (ids 120–125)
--   coupons          : 4  (ids 59–62)
--   store_coupons    : 4  (ids 21–24)
--   flash_discounts  : 3  (ids 23–25)
--   reviews          : 6  (ids 61–66)
--   grievances       : 3  (ids 43–45)
--   messages         : 2  (ids 9–10)
--   notifications    : 4  (ids 38–41)
--   merchant_labels  : 3  (ids 48–50)
--   merchant_tags    : 3  (ids 57–59)
--   sales_registry   : 6  (ids 75–80)
--   coupon_redemptions : 3 (ids 89–91)
-- ============================================================
