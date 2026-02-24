-- Seed data for merchant_leads and contact_enquiries
-- DealMachan Admin Panel Test Data

-- ============================================================
-- MERCHANT LEADS (12 rows)
-- ============================================================
INSERT INTO merchant_leads 
  (contact_name, org_name, category, email, phone, message, status, assigned_to_admin_id, notes, source, created_at, updated_at)
VALUES
(
  'Rajan Pillai', 'Spice Route Kitchen', 'Restaurant',
  'rajan@spiceroute.in', '9447123456',
  'We run a popular restaurant in Kochi and want to offer special dinner coupons to attract more customers through DealMachan.',
  'new', NULL, NULL, 'website', '2026-02-01 10:15:00', '2026-02-01 10:15:00'
),
(
  'Meena Suresh', 'Kairali Boutique', 'Retail',
  'meena@kairaliboutique.com', '9895234567',
  'We sell traditional Kerala handloom and sarees. Looking to promote our Onam collection with discount cards.',
  'contacted', 4, 'Called on Feb 5. Interested in the basic merchant plan. Follow-up scheduled for Feb 10.', 'website', '2026-02-02 11:30:00', '2026-02-05 14:00:00'
),
(
  'Thomas Varghese', 'Thomas Auto Repairs', 'Automobile',
  'thomas.auto@gmail.com', '9744345678',
  'We provide car servicing and repairs. Want to offer 15% off on first service for new customers.',
  'qualified', 4, 'Very interested. Shared merchant plan PDF. Demo meeting scheduled Feb 12.', 'google', '2026-02-03 09:00:00', '2026-02-08 11:00:00'
),
(
  'Anjali Krishnan', 'Anjali Ayurveda Spa', 'Wellness',
  'anjali@anjalispa.in', '9567456789',
  'Ayurvedic spa and beauty treatments. Would like to list our wellness packages on DealMachan app.',
  'converted', 4, 'Signed up. Merchant account created on Feb 10. Onboarding completed.', 'instagram', '2026-02-04 14:20:00', '2026-02-10 16:00:00'
),
(
  'Suresh Babu', 'Hotel Seabreeze', 'Hotel',
  'suresh@hotelseabreeze.in', '9400567890',
  'We are a 3-star hotel in Thrissur. Want to offer room-rate discounts and attract weekend travellers.',
  'new', NULL, NULL, 'website', '2026-02-05 08:45:00', '2026-02-05 08:45:00'
),
(
  'Divya Nair', 'D-Fit Gym', 'Fitness',
  'divya@dfitgym.com', '8157678901',
  'New gym in Trivandrum. Want to promote monthly membership offers and free trial passes via the app.',
  'new', NULL, NULL, 'instagram', '2026-02-06 17:00:00', '2026-02-06 17:00:00'
),
(
  'Pradeep Kumar', 'Pradeep Electronics', 'Electronics',
  'pradeep.electronics@yahoo.com', '9745789012',
  'Consumer electronics store in Kozhikode. Interested in flash discount feature for clearing old stock.',
  'contacted', 5, 'Initial call done. Interested in flash discounts. Needs more info on pricing.', 'referral', '2026-02-07 12:00:00', '2026-02-09 10:30:00'
),
(
  'Shalini Menon', 'Shalini Beauty Lounge', 'Beauty',
  'shalini@beautylounge.in', '9961890123',
  'Beauty salon and makeup studio. Looking to promote bridal packages and seasonal offers.',
  'qualified', 5, 'Very positive response. Shared onboarding kit. Needs owner approval to proceed.', 'facebook', '2026-02-08 10:00:00', '2026-02-11 09:00:00'
),
(
  'Arun Raj', 'AR Catering Services', 'Catering',
  'arun.catering@gmail.com', '9388901234',
  'We do corporate and wedding catering. Interested in partnering for bulk meal deal promotions.',
  'rejected', NULL, 'Does not meet minimum transaction threshold. Category not currently supported.', 'website', '2026-02-09 15:30:00', '2026-02-12 11:00:00'
),
(
  'Nitha George', 'Nitha Homemade Foods', 'Food',
  'nithageorge.foods@gmail.com', '9446012345',
  'We sell pickles, snacks and homemade sweets online and at local markets. Want to reach more customers.',
  'new', NULL, NULL, 'facebook', '2026-02-10 09:15:00', '2026-02-10 09:15:00'
),
(
  'Binu Mathew', 'BM Travel & Tours', 'Travel',
  'binu@bmtravel.in', '9847123456',
  'Travel agency specializing in Kerala backwater tours and pilgrimage packages. Looking for coupon tie-ups.',
  'contacted', 4, 'Email sent with merchant brochure. Awaiting response.', 'website', '2026-02-11 13:00:00', '2026-02-13 10:00:00'
),
(
  'Rekha Chandran', 'Rekha Creations', 'Handicrafts',
  'rekha.creations@gmail.com', '9744234567',
  'Handmade jewellery and craft items. Active on Instagram. Wants to offer discount codes for followers.',
  'new', NULL, NULL, 'instagram', '2026-02-12 16:30:00', '2026-02-12 16:30:00'
);

-- ============================================================
-- CONTACT ENQUIRIES (14 rows)
-- ============================================================
INSERT INTO contact_enquiries
  (name, mobile, email, subject, message, status, admin_notes, created_at, updated_at)
VALUES
(
  'Aishwarya Menon', '9447001122', 'aishwarya.m@gmail.com',
  'How to use coupon code?',
  'I received a coupon code from DealMachan but I am not sure how to apply it during checkout at the store. Can you please guide me?',
  'responded', 'Replied with step-by-step coupon redemption guide via email.', '2026-02-10 09:00:00', '2026-02-10 11:30:00'
),
(
  'Vishnu Kumar', '8129002233', 'vishnu.k@hotmail.com',
  'App is crashing on my phone',
  'The DealMachan customer app crashes every time I open the Deals section. I am using Android 11. Please fix this.',
  'responded', 'Escalated to tech team. Bug confirmed and fixed in v1.2.3 release. User notified.', '2026-02-10 14:20:00', '2026-02-11 10:00:00'
),
(
  'Sreeja Nair', '9895003344', 'sreeja.nair@gmail.com',
  'Cashback not received',
  'I made a purchase at Kairali Boutique 3 days ago and my cashback points have not been credited. Order ID: CB20260207.',
  'read', NULL, '2026-02-11 10:45:00', '2026-02-11 10:45:00'
),
(
  'Renjith Thomas', '9745004455', 'renjith.t@yahoo.com',
  'How to register my business?',
  'I own a bakery in Ernakulam. I want to list my shop on DealMachan and offer special deals. What is the process and cost?',
  'responded', 'Forwarded to merchant acquisition team. Lead created in system.', '2026-02-11 13:00:00', '2026-02-12 09:00:00'
),
(
  'Deepa Mohan', '9961005566', '  deepa.m@gmail.com',
  'Wrong discount shown',
  'The app showed 30% off at Hotel Seabreeze but when I visited they said only 10% is applicable. This is misleading.',
  'responded', 'Apologised to customer. Contacted merchant to update correct discount percentage.', '2026-02-12 08:30:00', '2026-02-12 14:00:00'
),
(
  'Gopinath Pillai', '9400006677', NULL,
  'Request to add more merchants in Palakkad',
  'There are very few merchants listed in Palakkad district. Please onboard more shops especially in Grocery and Medical categories.',
  'read', NULL, '2026-02-12 11:15:00', '2026-02-12 11:15:00'
),
(
  'Ananya Raj', '8547007788', 'ananya.raj@gmail.com',
  'Partnership inquiry',
  'I run a digital marketing agency and would like to discuss a co-marketing partnership with DealMachan for our mutual clients.',
  'new', NULL, '2026-02-13 09:00:00', '2026-02-13 09:00:00'
),
(
  'Manoj Varghese', '9388008899', 'manoj.varghese@gmail.com',
  'DealMaker program details',
  'I heard about the DealMaker referral program. Can you please explain how it works and how much I can earn by referring merchants?',
  'responded', 'Sent DealMaker brochure and enrollment link. User expressed interest in joining.', '2026-02-13 15:00:00', '2026-02-14 10:00:00'
),
(
  'Priya Suresh', '9447009900', 'priya.s23@gmail.com',
  'Happy with the app!',
  'Just wanted to say that DealMachan is an amazing app. I have saved over Rs. 2000 in the past month alone. Keep up the great work!',
  'responded', 'Thanked user. Shared on team channel. Requested Google Play review.', '2026-02-14 11:00:00', '2026-02-14 12:30:00'
),
(
  'Siju George', '9846011011', 'siju.george@rediffmail.com',
  'OTP not received',
  'I am trying to login but OTP is not coming to my mobile number 9846011011. Tried multiple times. Please help.',
  'responded', 'Checked SMS logs. Carrier delay issue. Advised user to use email OTP as alternative.', '2026-02-15 08:00:00', '2026-02-15 09:00:00'
),
(
  'Lakshmi Nair', '9495012122', 'lakshmi.n@gmail.com',
  'Subscription plan renewal',
  'My DealMachan Gold subscription expired yesterday. How do I renew it? Is there any renewal discount available?',
  'read', NULL, '2026-02-18 16:00:00', '2026-02-18 16:00:00'
),
(
  'Bibin Alex', '9961013233', NULL,
  'Delete my account',
  'I want to permanently delete my DealMachan customer account and all associated data. Please process this request.',
  'new', NULL, '2026-02-20 10:30:00', '2026-02-20 10:30:00'
),
(
  'Chinnu Chandran', '8589014344', 'chinnu.c@gmail.com',
  'Flash deal redemption issue',
  'I tried to redeem a flash deal at D-Fit Gym but the QR code scan failed at the counter. The deal expired while trying to fix it. Can I get it back?',
  'new', NULL, '2026-02-22 14:45:00', '2026-02-22 14:45:00'
),
(
  'Jithin Paul', '9947015455', 'jithin.paul@gmail.com',
  'Gift coupon query',
  'I received a DealMachan gift coupon of Rs. 500 as a birthday gift. Where can I redeem it and does it have an expiry date?',
  'responded', 'Explained gift coupon redemption process. Confirmed no expiry for this coupon batch.', '2026-02-25 13:00:00', '2026-02-25 15:00:00'
);
