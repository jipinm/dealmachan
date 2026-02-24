-- ─────────────────────────────────────────────────────────────────────────────
-- Migration: Add banner_image column to flash_discounts + assign category images
-- Reuses images from uploads/coupon-banners/ (shared image pool).
-- ─────────────────────────────────────────────────────────────────────────────

ALTER TABLE `flash_discounts`
  ADD COLUMN IF NOT EXISTS `banner_image` VARCHAR(500) DEFAULT NULL
    COMMENT 'Relative path to flash deal promotional image'
  AFTER `status`;

-- ── Assign images by category ─────────────────────────────────────────────────

-- Restaurants / General food (Spice Garden, Heritage hotel, midnight snack)
UPDATE `flash_discounts` SET `banner_image` = 'uploads/coupon-banners/food.jpg'
  WHERE `id` IN (1, 2, 14, 15, 19, 20, 21, 22);

-- Fashion / Textiles
UPDATE `flash_discounts` SET `banner_image` = 'uploads/coupon-banners/fashion.jpg'
  WHERE `id` IN (3);

-- Sweets / Bakery / Cakes
UPDATE `flash_discounts` SET `banner_image` = 'uploads/coupon-banners/cake.jpg'
  WHERE `id` IN (4, 13);

-- Gym / Fitness
UPDATE `flash_discounts` SET `banner_image` = 'uploads/coupon-banners/gym.jpg'
  WHERE `id` IN (5);

-- Coffee / Café
UPDATE `flash_discounts` SET `banner_image` = 'uploads/coupon-banners/coffee.jpg'
  WHERE `id` IN (6);

-- Biryani / Rice dishes
UPDATE `flash_discounts` SET `banner_image` = 'uploads/coupon-banners/biryani.jpg'
  WHERE `id` IN (7);

-- Rubber / Industrial / Automotive
UPDATE `flash_discounts` SET `banner_image` = 'uploads/coupon-banners/industrial.jpg'
  WHERE `id` IN (8);

-- Jewellery / Gold
UPDATE `flash_discounts` SET `banner_image` = 'uploads/coupon-banners/jewellery.jpg'
  WHERE `id` IN (9, 16);

-- Seafood / Fish
UPDATE `flash_discounts` SET `banner_image` = 'uploads/coupon-banners/seafood.jpg'
  WHERE `id` IN (10);

-- Tea / Beverages
UPDATE `flash_discounts` SET `banner_image` = 'uploads/coupon-banners/tea.jpg'
  WHERE `id` IN (11);

-- Travel / Houseboat / Resort
UPDATE `flash_discounts` SET `banner_image` = 'uploads/coupon-banners/travel.jpg'
  WHERE `id` IN (12, 18);

-- Electronics
UPDATE `flash_discounts` SET `banner_image` = 'uploads/coupon-banners/electronics.jpg'
  WHERE `id` IN (17);

-- Verify: should be empty
-- SELECT id, title, banner_image FROM flash_discounts WHERE banner_image IS NULL;
