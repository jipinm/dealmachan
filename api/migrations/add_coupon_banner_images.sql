-- ─────────────────────────────────────────────────────────────────────────────
-- Migration: Add banner_image column to coupons + assign category images
-- Run once against the live database.
-- Images live in /uploads/coupon-banners/ on the API server.
-- ─────────────────────────────────────────────────────────────────────────────

-- 1. Add column if the live DB is on the older schema (no-op if already present)
ALTER TABLE `coupons`
  ADD COLUMN IF NOT EXISTS `banner_image` VARCHAR(500) DEFAULT NULL
    COMMENT 'Relative path to coupon promotional image'
  AFTER `terms_conditions`;

-- 2. Assign category-appropriate banner images to every coupon
--    Path format matches imageUrl() helper: /uploads/coupon-banners/<file>

-- ── Restaurants / General food ───────────────────────────────────────────────
UPDATE `coupons` SET `banner_image` = 'uploads/coupon-banners/food.jpg'
  WHERE `id` IN (1, 2, 8, 21, 22, 41, 42, 43);

-- ── Biryani / Rice dishes ────────────────────────────────────────────────────
UPDATE `coupons` SET `banner_image` = 'uploads/coupon-banners/biryani.jpg'
  WHERE `id` IN (10, 52);

-- ── Seafood ──────────────────────────────────────────────────────────────────
UPDATE `coupons` SET `banner_image` = 'uploads/coupon-banners/seafood.jpg'
  WHERE `id` IN (23, 24);

-- ── Coffee / Café ────────────────────────────────────────────────────────────
UPDATE `coupons` SET `banner_image` = 'uploads/coupon-banners/coffee.jpg'
  WHERE `id` IN (50, 51);

-- ── Bakery / Sweets / Cakes ──────────────────────────────────────────────────
UPDATE `coupons` SET `banner_image` = 'uploads/coupon-banners/cake.jpg'
  WHERE `id` IN (4, 13, 46, 47, 55);

-- ── Fashion / Textiles / Sarees ──────────────────────────────────────────────
UPDATE `coupons` SET `banner_image` = 'uploads/coupon-banners/fashion.jpg'
  WHERE `id` IN (3, 9, 44, 45);

-- ── Electronics / Tech ───────────────────────────────────────────────────────
UPDATE `coupons` SET `banner_image` = 'uploads/coupon-banners/electronics.jpg'
  WHERE `id` IN (5, 6, 11);

-- ── Spa / Wellness ───────────────────────────────────────────────────────────
UPDATE `coupons` SET `banner_image` = 'uploads/coupon-banners/spa.jpg'
  WHERE `id` IN (7, 14, 54);

-- ── Jewellery / Gold / Silver ────────────────────────────────────────────────
UPDATE `coupons` SET `banner_image` = 'uploads/coupon-banners/jewellery.jpg'
  WHERE `id` IN (18, 19, 20, 53);

-- ── Automotive / Spare Parts ─────────────────────────────────────────────────
UPDATE `coupons` SET `banner_image` = 'uploads/coupon-banners/auto.jpg'
  WHERE `id` IN (25, 26);

-- ── Furniture / Home Decor ───────────────────────────────────────────────────
UPDATE `coupons` SET `banner_image` = 'uploads/coupon-banners/furniture.jpg'
  WHERE `id` IN (12, 27, 28);

-- ── Organic / Grocery / Vegetables ──────────────────────────────────────────
UPDATE `coupons` SET `banner_image` = 'uploads/coupon-banners/organic.jpg'
  WHERE `id` IN (29);

-- ── Optics / Eyewear ─────────────────────────────────────────────────────────
UPDATE `coupons` SET `banner_image` = 'uploads/coupon-banners/optics.jpg'
  WHERE `id` IN (30, 31, 32);

-- ── Tea / Beverages ──────────────────────────────────────────────────────────
UPDATE `coupons` SET `banner_image` = 'uploads/coupon-banners/tea.jpg'
  WHERE `id` IN (33, 34);

-- ── Tattoo / Body Art ────────────────────────────────────────────────────────
UPDATE `coupons` SET `banner_image` = 'uploads/coupon-banners/tattoo.jpg'
  WHERE `id` IN (35, 36);

-- ── Travel / Cruise / Houseboat ──────────────────────────────────────────────
UPDATE `coupons` SET `banner_image` = 'uploads/coupon-banners/travel.jpg'
  WHERE `id` IN (37, 38);

-- ── Spices / Herbs ───────────────────────────────────────────────────────────
UPDATE `coupons` SET `banner_image` = 'uploads/coupon-banners/spices.jpg'
  WHERE `id` IN (39, 40);

-- ── Gym / Fitness / Sports ───────────────────────────────────────────────────
UPDATE `coupons` SET `banner_image` = 'uploads/coupon-banners/gym.jpg'
  WHERE `id` IN (48, 49);

UPDATE `coupons` SET `banner_image` = 'uploads/coupon-banners/sports.jpg'
  WHERE `id` IN (15);

-- ── Rubber / Industrial ──────────────────────────────────────────────────────
UPDATE `coupons` SET `banner_image` = 'uploads/coupon-banners/industrial.jpg'
  WHERE `id` IN (16, 17);

-- ── Seed / test coupons (56-58: Spice Garden variants → food) ────────────────
UPDATE `coupons` SET `banner_image` = 'uploads/coupon-banners/food.jpg'
  WHERE `id` IN (56, 57, 58);

-- Verify — should show 0 rows with NULL banner_image for active/approved coupons
-- SELECT id, title, banner_image FROM coupons WHERE banner_image IS NULL AND status='active';
