-- ============================================================
-- SEED FIX for merchant admin@example.com (merchant_id=31)
-- Fixes:
--   1. Updates message sender/receiver IDs (95→31 which is merchant_id)
--   2. Adds more messages (admin <-> merchant thread)
--   3. Adds more notifications
--   4. Adds more grievances
-- Run: mysql -u root deal_machan < seed_merchant_31_fix.sql
-- ============================================================

USE `deal_machan`;

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- FIX: Messages - correct sender_id / receiver_id
-- The MessageController uses merchant_id (31), not user_id (95)
-- ============================================================
UPDATE `messages` SET sender_id = 31  WHERE id = 9  AND sender_type   = 'merchant';
UPDATE `messages` SET receiver_id = 31 WHERE id = 10 AND receiver_type = 'merchant';


-- ============================================================
-- MORE MESSAGES (root threads + replies)
-- Using:  merchant sender_id = 31 (merchants.id), sender_type='merchant'
--         admin receiver_id = 1 (admins.id),   receiver_type='admin'
-- ============================================================
INSERT INTO `messages`
  (`id`, `sender_id`, `sender_type`, `receiver_id`, `receiver_type`,
   `subject`, `message_text`, `parent_message_id`, `read_status`, `read_at`, `sent_at`)
VALUES
  -- Thread 2: merchant asks about store photos
  (11, 31, 'merchant', 1, 'admin',
   'Store photo upload issue',
   'Hello,\n\nI am trying to upload photos for my Demo Merchant Ernakulam store but the upload is failing with a file size error. What is the maximum allowed image size?\n\nThanks,\nDemo Merchant',
   NULL, 1, '2026-02-22 11:00:00', '2026-02-22 10:30:00'),

  -- Admin replies to thread 2
  (12, 1, 'admin', 31, 'merchant',
   'RE: Store photo upload issue',
   'Hi,\n\nThe maximum image size is 5MB and must be JPG, PNG or WebP format. Please compress your images before uploading. Let us know if the issue persists.\n\nRegards,\nDealMachan Support',
   11, 0, NULL, '2026-02-22 12:00:00'),

  -- Thread 3: admin sends welcome message
  (13, 1, 'admin', 31, 'merchant',
   'Welcome to DealMachan Merchant Platform!',
   'Hi Demo Merchant Team,\n\nWelcome aboard! Your merchant account is now active. Here are a few tips to get started:\n\n1. Complete your store profile with photos and opening hours\n2. Create your first coupon offer\n3. Set up a flash discount to attract new customers\n\nOur team is available if you need any help.\n\nBest regards,\nDealMachan Onboarding Team',
   NULL, 1, '2026-02-20 09:30:00', '2026-02-20 09:00:00'),

  -- Thread 4: merchant reports cashier issue
  (14, 31, 'merchant', 1, 'admin',
   'Customer complaint about cashier not accepting coupon',
   'Hello Support,\n\nWe received a complaint from a customer saying that one of our cashiers refused to accept a valid DealMachan coupon at the Ernakulam store. We have retrained the staff and this has been resolved. Please update the grievance status accordingly.\n\nThank you,\nDemo Merchant Manager',
   NULL, 1, '2026-02-23 14:00:00', '2026-02-23 13:30:00'),

  -- Admin acknowledges thread 4
  (15, 1, 'admin', 31, 'merchant',
   'RE: Customer complaint about cashier not accepting coupon',
   'Hi,\n\nThank you for the prompt action. We have updated the grievance record. Please ensure your staff is regularly briefed on coupon acceptance procedures.\n\nRegards,\nDealMachan Admin',
   14, 0, NULL, '2026-02-23 15:00:00'),

  -- Thread 5: admin notification about coupon approval
  (16, 1, 'admin', 31, 'merchant',
   'Coupons DEMOWK15 & DEMO200 Approved',
   'Hi Demo Merchant,\n\nYour coupon requests have been reviewed and approved:\n  - DEMOWK15 (Demo Weekend Special - 15% Off)\n  - DEMO200 (Demo Exclusive - Rs.200 Off)\n\nThese are now live and visible to customers. Good luck with your campaigns!\n\nBest,\nDealMachan Admin',
   NULL, 0, NULL, '2026-02-24 10:00:00'),

  -- Thread 6: merchant asks about analytics
  (17, 31, 'merchant', 1, 'admin',
   'Analytics data showing zero for some metrics',
   'Hello,\n\nI noticed that some metrics on my analytics dashboard are showing 0 even though I have had customers redeem coupons. Is there a delay in data refresh or is there a technical issue?\n\nRegards,\nDemo Merchant',
   NULL, 0, NULL, '2026-02-25 09:00:00');


-- ============================================================
-- MORE NOTIFICATIONS (10 additional for user_id=95, user_type='merchant')
-- ============================================================
INSERT INTO `notifications`
  (`id`, `user_id`, `user_type`, `notification_type`, `title`, `message`,
   `action_url`, `read_status`, `read_at`, `created_at`)
VALUES
  -- Coupon-related
  (42, 95, 'merchant', 'success',
   'Coupon DEMOWK15 Approved',
   'Your coupon "Demo Weekend Special - 15% Off" (DEMOWK15) has been approved by admin and is now active.',
   '/merchant/coupons', 1, '2026-02-24 11:00:00', '2026-02-24 10:30:00'),

  (43, 95, 'merchant', 'success',
   'Coupon DEMO200 Approved',
   'Your coupon "Demo Exclusive - Rs.200 Off" (DEMO200) has been approved by admin and is now live.',
   '/merchant/coupons', 1, '2026-02-24 11:00:00', '2026-02-24 10:30:00'),

  (44, 95, 'merchant', 'coupon',
   'Coupon DEMO10 Redeemed',
   '3 customers have redeemed your DEMO10 coupon so far. Keep sharing to increase redemptions!',
   '/merchant/coupons', 1, '2026-02-23 14:00:00', '2026-02-23 13:00:00'),

  -- Review notifications
  (45, 95, 'merchant', 'info',
   'New Customer Review Received',
   'A customer gave your Demo Merchant - Ernakulam store a 5-star rating. Check it out!',
   '/merchant/reviews', 0, NULL, '2026-02-24 16:00:00'),

  (46, 95, 'merchant', 'warning',
   'Negative Review Requires Attention',
   'A customer left a 2-star review mentioning issues with coupon processing at Ernakulam store. Please review and respond.',
   '/merchant/reviews', 0, NULL, '2026-02-24 18:00:00'),

  -- Grievance notifications
  (47, 95, 'merchant', 'warning',
   'New Grievance Filed',
   'A customer has filed a grievance regarding "Coupon discount not applied at counter" at your Ernakulam store.',
   '/merchant/grievances', 0, NULL, '2026-02-23 10:30:00'),

  (48, 95, 'merchant', 'info',
   'Grievance Resolution Acknowledged',
   'The grievance #45 "Long waiting time" has been marked resolved. Customer response acknowledged.',
   '/merchant/grievances', 1, '2026-02-22 11:00:00', '2026-02-22 10:30:00'),

  -- Flash discount notifications
  (49, 95, 'merchant', 'flash_discount',
   'Flash Discount - 7 Redemptions!',
   'Your "Demo Launch Flash - 30% Off" deal has been redeemed 7 times. It is trending among customers nearby.',
   '/merchant/flash-discounts', 1, '2026-02-23 10:00:00', '2026-02-23 09:00:00'),

  (50, 95, 'merchant', 'flash_discount',
   'Flash Deal Expiring in 24 Hours',
   'Your "Demo Launch Flash - 30% Off" flash discount expires tomorrow. Consider creating a new one to keep momentum.',
   '/merchant/flash-discounts', 0, NULL, '2026-02-24 08:00:00'),

  -- Profile / system
  (51, 95, 'merchant', 'info',
   'Add Opening Hours to Your Stores',
   'Stores with complete opening hours get 40% more visibility in search results. Update your store details now.',
   '/merchant/stores', 0, NULL, '2026-02-25 08:00:00'),

  (52, 95, 'merchant', 'promotion',
   'Upgrade to Premium for More Features',
   'Premium merchants enjoy priority listing, enhanced analytics, and unlimited coupon creation. Explore upgrade options.',
   '/merchant/profile', 0, NULL, '2026-02-25 09:00:00');


-- ============================================================
-- MORE GRIEVANCES  (5 additional, merchant_id=31)
-- Using existing customer IDs: 10, 15, 20, 25, 33
-- ============================================================
INSERT INTO `grievances`
  (`id`, `customer_id`, `merchant_id`, `store_id`, `subject`, `description`,
   `status`, `priority`, `created_at`, `resolved_at`, `resolution_notes`)
VALUES
  (46, 10, 31, 50,
   'Product mismatch at MG Road outlet',
   'I purchased what was labelled as premium quality product but the actual item I received was clearly of lower grade. The packaging was different from the displayed sample.',
   'open', 'high', '2026-02-21 16:00:00', NULL, NULL),

  (47, 15, 31, 49,
   'Billing error - charged twice',
   'My UPI shows two deductions of Rs.1850 for a single transaction at Demo Merchant Ernakulam on 20 Feb. I have attached the UPI screenshots. Please arrange a refund.',
   'in_progress', 'urgent', '2026-02-22 10:00:00', NULL, NULL),

  (48, 20, 31, 50,
   'Rude behaviour by floor staff',
   'The sales executive at MG Road was dismissive and unhelpful when I asked for product information. He suggested I look elsewhere instead of assisting. This is very unprofessional.',
   'open', 'medium', '2026-02-23 13:00:00', NULL, NULL),

  (49, 25, 31, 49,
   'Flash discount not honoured at counter',
   'I showed the active flash deal on my DealMachan app for 30% off but the cashier said their system was not updated and refused to apply the discount. I ended up paying full price.',
   'resolved', 'high', '2026-02-22 14:00:00', '2026-02-24 10:00:00',
   'Store manager confirmed the issue was a system synchronisation delay. Customer was issued a store credit equivalent to 30% of the purchase. Discount module has been updated.'),

  (50, 33, 31, 49,
   'Expired product on shelf',
   'I found a product on the shelf with an expiry date that had lapsed by 3 weeks. Staff did not seem concerned when I pointed it out. This is a serious food safety issue.',
   'in_progress', 'urgent', '2026-02-24 11:00:00', NULL, NULL);


SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- END OF FIX SEED (merchant_id=31)
-- Summary:
--   messages updated (ids 9,10 corrected) + 7 new (ids 11–17)
--   notifications: 11 new (ids 42–52)
--   grievances: 5 new (ids 46–50)
-- ============================================================
