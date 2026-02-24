-- ============================================================================
-- Deal Machan Customer App – Phase 2 Database Migration
-- Run once after deploying the Customer App Phase 2 features
--
-- SAFE TO RUN: All statements use CREATE TABLE IF NOT EXISTS / ADD COLUMN IF
-- NOT EXISTS so they can be re-run without error on environments that already
-- have some of these changes applied.
--
-- SECTIONS
--   1. New table  : customer_merchant_favourites
--   2. New table  : store_reviews
--   3. New table  : store_review_images
--   4. Alter table: customers   (profile + auth columns)
--   5. Alter table: stores      (rating aggregates, working_hours_json)
-- ============================================================================

-- ─── 1. customer_merchant_favourites ─────────────────────────────────────────
-- Tracks which stores a customer has starred / saved as a favourite.
-- Favourite is per *store* (unit), not per merchant company — per spec rule.

CREATE TABLE IF NOT EXISTS `customer_merchant_favourites` (
  `id`          int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned NOT NULL,
  `store_id`    int(10) unsigned NOT NULL,
  `merchant_id` int(10) unsigned NOT NULL,
  `created_at`  timestamp        NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cmf_customer_store` (`customer_id`, `store_id`),
  KEY `idx_cmf_store`     (`store_id`),
  KEY `idx_cmf_merchant`  (`merchant_id`),
  CONSTRAINT `fk_cmf_customer` FOREIGN KEY (`customer_id`)
    REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cmf_store` FOREIGN KEY (`store_id`)
    REFERENCES `stores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cmf_merchant` FOREIGN KEY (`merchant_id`)
    REFERENCES `merchants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Customer store favourites (wishlist)';

-- ─── 2. store_reviews ────────────────────────────────────────────────────────
-- Stores per-store customer reviews with multi-dimension star ratings.
-- Upsert pattern: ON DUPLICATE KEY UPDATE on (store_id, reviewer_mobile).

CREATE TABLE IF NOT EXISTS `store_reviews` (
  `id`               int(10) unsigned NOT NULL AUTO_INCREMENT,
  `store_id`         int(10) unsigned NOT NULL,
  `customer_id`      int(10) unsigned DEFAULT NULL,
  `reviewer_name`    varchar(100)     NOT NULL,
  `reviewer_mobile`  varchar(15)      NOT NULL,
  `rating`           tinyint(1) unsigned NOT NULL DEFAULT 5
    COMMENT 'Overall 1-5 star rating',
  `rating_1`         tinyint(1) unsigned DEFAULT NULL COMMENT 'Quality / Food',
  `rating_2`         tinyint(1) unsigned DEFAULT NULL COMMENT 'Service',
  `rating_3`         tinyint(1) unsigned DEFAULT NULL COMMENT 'Ambience / Value',
  `review_text`      text             DEFAULT NULL,
  `is_approved`      tinyint(1)       NOT NULL DEFAULT 0
    COMMENT '0=pending admin approval, 1=live',
  `created_at`       timestamp        NOT NULL DEFAULT current_timestamp(),
  `updated_at`       timestamp        NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_sr_store_mobile` (`store_id`, `reviewer_mobile`),
  KEY `idx_sr_store`      (`store_id`),
  KEY `idx_sr_customer`   (`customer_id`),
  KEY `idx_sr_approved`   (`is_approved`),
  CONSTRAINT `fk_sr_store` FOREIGN KEY (`store_id`)
    REFERENCES `stores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sr_customer` FOREIGN KEY (`customer_id`)
    REFERENCES `customers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Customer ratings and reviews per store unit';

-- ─── 3. store_review_images ──────────────────────────────────────────────────
-- Optional photos submitted with a store review.

CREATE TABLE IF NOT EXISTS `store_review_images` (
  `id`          int(10) unsigned NOT NULL AUTO_INCREMENT,
  `review_id`   int(10) unsigned NOT NULL,
  `image_path`  varchar(255)     NOT NULL,
  `created_at`  timestamp        NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sri_review` (`review_id`),
  CONSTRAINT `fk_sri_review` FOREIGN KEY (`review_id`)
    REFERENCES `store_reviews` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Photo attachments for store reviews';

-- ─── 4. ALTER TABLE customers ────────────────────────────────────────────────
-- Add Phase 2 profile fields and auth helpers.
-- city_id, area_id, bio added in customer-app-phase1.sql — skipped here.

ALTER TABLE `customers`
  ADD COLUMN IF NOT EXISTS `last_name`     varchar(100) DEFAULT NULL
    COMMENT 'Customer surname / last name'                   AFTER `name`,
  ADD COLUMN IF NOT EXISTS `occupation`    varchar(100) DEFAULT NULL
    COMMENT 'Job / profession free-text (complements profession_id)'
    AFTER `profession_id`,
  ADD COLUMN IF NOT EXISTS `full_address`  text         DEFAULT NULL
    COMMENT 'Street address / apartment / landmark'          AFTER `occupation`,
  ADD COLUMN IF NOT EXISTS `pincode`       varchar(10)  DEFAULT NULL
    COMMENT 'Postal / ZIP code'                              AFTER `full_address`,
  ADD COLUMN IF NOT EXISTS `temp_password` tinyint(1)   NOT NULL DEFAULT 0
    COMMENT '1 = customer must reset password on next login' AFTER `password`,
  ADD COLUMN IF NOT EXISTS `push_enabled`  tinyint(1)   NOT NULL DEFAULT 1
    COMMENT '1 = push/in-app notifications enabled'         AFTER `temp_password`;

-- Index on city + area for geographic filtering (idempotent)
ALTER TABLE `customers`
  ADD INDEX IF NOT EXISTS `idx_cust_city`  (`city_id`),
  ADD INDEX IF NOT EXISTS `idx_cust_area`  (`area_id`);

-- ─── 5. ALTER TABLE stores ───────────────────────────────────────────────────
-- Rating aggregates (updated by trigger or batch job after review approval).
-- working_hours_json stores [{day, open, close, closed}] array; the existing
-- `opening_hours` longtext column is the legacy field — both kept for compat.

ALTER TABLE `stores`
  ADD COLUMN IF NOT EXISTS `avg_rating`         decimal(3,2) NOT NULL DEFAULT 0.00
    COMMENT 'Cached average of approved store_reviews.rating',
  ADD COLUMN IF NOT EXISTS `total_reviews`      int(10) unsigned NOT NULL DEFAULT 0
    COMMENT 'Count of approved reviews — kept in sync with avg_rating',
  ADD COLUMN IF NOT EXISTS `working_hours_json` json             DEFAULT NULL
    COMMENT 'Structured [{day,open,close,closed}]; mirrors opening_hours text field';

-- ─── 6. Trigger: auto-update store rating after review approval ──────────────
-- Optional — if you prefer to maintain aggregates via a trigger rather than
-- running a batch job. Safe to skip if a scheduled job handles this.

-- Drop old version if it exists (allows safe re-run)
DROP TRIGGER IF EXISTS `trg_store_reviews_after_update`;

DELIMITER $$

CREATE TRIGGER `trg_store_reviews_after_update`
AFTER UPDATE ON `store_reviews`
FOR EACH ROW
BEGIN
  IF NEW.is_approved != OLD.is_approved OR NEW.rating != OLD.rating THEN
    UPDATE `stores` s
    SET
      s.avg_rating     = (
        SELECT IFNULL(ROUND(AVG(r.rating), 2), 0.00)
        FROM `store_reviews` r
        WHERE r.store_id = NEW.store_id AND r.is_approved = 1
      ),
      s.total_reviews  = (
        SELECT COUNT(*)
        FROM `store_reviews` r
        WHERE r.store_id = NEW.store_id AND r.is_approved = 1
      )
    WHERE s.id = NEW.store_id;
  END IF;
END$$

DELIMITER ;

-- ─── 7. Recalculate merchant rating aggregates from store_reviews ─────────────
-- Merchants have their own avg_rating/total_reviews (added in migration 007).
-- After running this migration, a one-off recalculation is recommended if
-- there is pre-existing review data in alternate tables.
--
-- Manual recalculation snippet (run as needed):
--
--   UPDATE merchants m
--   SET
--     m.avg_rating    = (SELECT IFNULL(ROUND(AVG(r.rating),2),0)
--                        FROM store_reviews r
--                        JOIN stores s ON s.id = r.store_id
--                        WHERE s.merchant_id = m.id AND r.is_approved = 1),
--     m.total_reviews = (SELECT COUNT(*)
--                        FROM store_reviews r
--                        JOIN stores s ON s.id = r.store_id
--                        WHERE s.merchant_id = m.id AND r.is_approved = 1);

-- ============================================================================
-- END OF MIGRATION
-- ============================================================================
