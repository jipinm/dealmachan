-- Migration M9: Create gift_coupon_batches table + add batch_id to gift_coupons
-- Required by Task: F1
-- Run in this order: CREATE first, then ALTER.

-- Step 1: Create the batch tracking table
CREATE TABLE IF NOT EXISTS `gift_coupon_batches` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` int(10) unsigned NOT NULL,
  `coupon_id` int(10) unsigned NOT NULL,
  `filter_criteria` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
    COMMENT 'JSON snapshot of all filters applied: card_segment, club_ids, profession_ids, birth_month, city_id, area_id, gender'
    CHECK (json_valid(`filter_criteria`)),
  `total_recipients` int(11) NOT NULL DEFAULT 0,
  `requires_acceptance` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_coupon_id` (`coupon_id`),
  CONSTRAINT `fk_gcb_admin`
    FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_gcb_coupon`
    FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Step 2: Link existing gift_coupons table to batch records
ALTER TABLE `gift_coupons`
  ADD COLUMN `batch_id` int(10) unsigned DEFAULT NULL
    COMMENT 'References gift_coupon_batches.id; NULL = individual one-to-one gift'
    AFTER `id`,
  ADD KEY `idx_batch_id` (`batch_id`),
  ADD CONSTRAINT `fk_gc_batch`
    FOREIGN KEY (`batch_id`) REFERENCES `gift_coupon_batches` (`id`) ON DELETE SET NULL;
