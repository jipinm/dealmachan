-- ============================================================
-- DealMachan — Phase 1: Database Migrations (run all)
-- ============================================================
-- Order matters. Run as a single script or execute M1–M11 in order.
-- M11 is OPTIONAL — apply only if full points-wallet tracking is confirmed.
-- ============================================================

-- ─────────────────────────────────────────────────────────────
-- M1: merchants — add is_verified, subscription_period,
--                 coupon_limit, monthly_assignment_limit
-- ─────────────────────────────────────────────────────────────
ALTER TABLE `merchants`
  ADD COLUMN `is_verified` tinyint(1) NOT NULL DEFAULT 0
    COMMENT 'Verified Partner badge toggle'
    AFTER `is_premium`,
  ADD COLUMN `subscription_period` enum('1M','3M','6M','1Y') DEFAULT '1Y'
    COMMENT 'Duration of current subscription; used to calculate expiry on renewal'
    AFTER `subscription_expiry`,
  ADD COLUMN `coupon_limit` int(10) unsigned DEFAULT NULL
    COMMENT 'Total store-coupon assignment cap for this merchant; NULL = unlimited'
    AFTER `subscription_period`,
  ADD COLUMN `monthly_assignment_limit` int(10) unsigned DEFAULT NULL
    COMMENT 'Monthly cap on store-coupon assignments; NULL = unlimited'
    AFTER `coupon_limit`,
  ADD KEY `idx_is_verified` (`is_verified`);

-- ─────────────────────────────────────────────────────────────
-- M2: stores — add website_link, booking_enabled,
--              booking_confirmation_required
-- ─────────────────────────────────────────────────────────────
ALTER TABLE `stores`
  ADD COLUMN `website_link` varchar(255) DEFAULT NULL
    COMMENT 'Public website URL of the store'
    AFTER `email`,
  ADD COLUMN `booking_enabled` tinyint(1) NOT NULL DEFAULT 0
    COMMENT '1 = Book Now button visible on customer app store profile'
    AFTER `description`,
  ADD COLUMN `booking_confirmation_required` tinyint(1) NOT NULL DEFAULT 0
    COMMENT '1 = booking starts as pending until merchant confirms; 0 = auto-confirmed'
    AFTER `booking_enabled`;

-- ─────────────────────────────────────────────────────────────
-- M3: card_configurations — add 6 feature toggle columns
-- ─────────────────────────────────────────────────────────────
ALTER TABLE `card_configurations`
  ADD COLUMN `pay_back_points_enabled` tinyint(1) NOT NULL DEFAULT 0
    COMMENT 'Toggle: card earns pay-back points on purchases'
    AFTER `coupon_authorization`,
  ADD COLUMN `pay_back_points_value` decimal(5,2) DEFAULT NULL
    COMMENT 'Points rate as % of purchase; only used when pay_back_points_enabled = 1'
    AFTER `pay_back_points_enabled`,
  ADD COLUMN `lifetime_subscription` tinyint(1) NOT NULL DEFAULT 0
    COMMENT 'Toggle: card has no expiry / lifetime validity; overrides validity_days'
    AFTER `validity_days`,
  ADD COLUMN `gift_coupon_eligibility` tinyint(1) NOT NULL DEFAULT 0
    COMMENT 'Toggle: card holders can receive gift coupons from admin'
    AFTER `lifetime_subscription`,
  ADD COLUMN `lucky_draw_eligible` tinyint(1) NOT NULL DEFAULT 0
    COMMENT 'Toggle: card holders are eligible for lucky draw participation'
    AFTER `gift_coupon_eligibility`,
  ADD COLUMN `contest_eligible` tinyint(1) NOT NULL DEFAULT 0
    COMMENT 'Toggle: card holders can participate in contests'
    AFTER `lucky_draw_eligible`;

-- ─────────────────────────────────────────────────────────────
-- M4: cards — add assigned_to_store_id
-- ─────────────────────────────────────────────────────────────
ALTER TABLE `cards`
  ADD COLUMN `assigned_to_store_id` int(10) unsigned DEFAULT NULL
    COMMENT 'Card batch assigned to a store for on-site distribution'
    AFTER `assigned_to_merchant_id`,
  ADD KEY `idx_assigned_store` (`assigned_to_store_id`),
  ADD CONSTRAINT `fk_cards_store`
    FOREIGN KEY (`assigned_to_store_id`) REFERENCES `stores` (`id`) ON DELETE SET NULL;

-- ─────────────────────────────────────────────────────────────
-- M5: coupons — add note and usage_limit_per_user
-- ─────────────────────────────────────────────────────────────
ALTER TABLE `coupons`
  ADD COLUMN `note` text DEFAULT NULL
    COMMENT 'Internal admin note — not shown to customers'
    AFTER `terms_conditions`,
  ADD COLUMN `usage_limit_per_user` int(11) DEFAULT NULL
    COMMENT 'Max times a single customer can redeem this coupon; NULL = unlimited'
    AFTER `usage_limit`;

-- ─────────────────────────────────────────────────────────────
-- M6: store_coupons — add admin-management columns
-- ─────────────────────────────────────────────────────────────
ALTER TABLE `store_coupons`
  ADD COLUMN `title` varchar(255) DEFAULT NULL
    COMMENT 'Display name of the store coupon'
    AFTER `id`,
  ADD COLUMN `description` text DEFAULT NULL
    AFTER `title`,
  ADD COLUMN `created_by_admin_id` int(10) unsigned DEFAULT NULL
    COMMENT 'Admin who created this coupon; NULL = legacy merchant-created record'
    AFTER `description`,
  ADD COLUMN `requires_acceptance` tinyint(1) NOT NULL DEFAULT 0
    COMMENT '1 = customer must actively Grab Now; 0 = auto-assigned on card activation'
    AFTER `gifted_at`,
  ADD COLUMN `total_quantity` int(10) unsigned DEFAULT NULL
    COMMENT 'Total coupon assignments available; NULL = unlimited'
    AFTER `requires_acceptance`,
  ADD COLUMN `assigned_count` int(10) unsigned NOT NULL DEFAULT 0
    COMMENT 'Running total of times this coupon has been assigned to customers'
    AFTER `total_quantity`,
  ADD COLUMN `assignment_type` enum('auto_assign','merchant_request') NOT NULL DEFAULT 'merchant_request'
    COMMENT 'auto_assign = given on card activation; merchant_request = submitted via admin queue'
    AFTER `assigned_count`,
  ADD KEY `idx_created_by_admin` (`created_by_admin_id`),
  ADD CONSTRAINT `fk_sc_admin`
    FOREIGN KEY (`created_by_admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

-- ─────────────────────────────────────────────────────────────
-- M7: CREATE bookings table
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `bookings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int(10) unsigned NOT NULL,
  `customer_id` int(10) unsigned NOT NULL,
  `booking_date` date NOT NULL,
  `booking_time` time DEFAULT NULL,
  `num_attendees` int(11) NOT NULL DEFAULT 1,
  `customer_notes` text DEFAULT NULL,
  `status` enum('pending','confirmed','rejected','cancelled','completed') NOT NULL DEFAULT 'pending',
  `merchant_notes` text DEFAULT NULL,
  `confirmed_by_user_id` int(10) unsigned DEFAULT NULL
    COMMENT 'Merchant-side user who confirmed or rejected the booking',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_store_id` (`store_id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_status` (`status`),
  KEY `idx_booking_date` (`booking_date`),
  CONSTRAINT `fk_bookings_store`
    FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bookings_customer`
    FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- M8: CREATE call_logs table
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `call_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int(10) unsigned NOT NULL,
  `customer_id` int(10) unsigned NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `initiated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_store_id` (`store_id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_initiated_at` (`initiated_at`),
  CONSTRAINT `fk_cl_store`
    FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cl_customer`
    FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- M9: CREATE gift_coupon_batches + add batch_id to gift_coupons
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `gift_coupon_batches` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` int(10) unsigned NOT NULL,
  `coupon_id` int(10) unsigned NOT NULL,
  `filter_criteria` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
    COMMENT 'JSON snapshot of filters: card_segment, club_ids, profession_ids, birth_month, city_id, area_id, gender'
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

ALTER TABLE `gift_coupons`
  ADD COLUMN `batch_id` int(10) unsigned DEFAULT NULL
    COMMENT 'References gift_coupon_batches.id; NULL = individual one-to-one gift'
    AFTER `id`,
  ADD KEY `idx_batch_id` (`batch_id`),
  ADD CONSTRAINT `fk_gc_batch`
    FOREIGN KEY (`batch_id`) REFERENCES `gift_coupon_batches` (`id`) ON DELETE SET NULL;

-- ─────────────────────────────────────────────────────────────
-- M10: card_config_sub_class_map — add gender_filter, profession_ids
-- ─────────────────────────────────────────────────────────────
ALTER TABLE `card_config_sub_class_map`
  ADD COLUMN `gender_filter` enum('male','female','both') DEFAULT NULL
    COMMENT 'Used when sub_class_id is the Gender sub-classification'
    AFTER `sub_class_id`,
  ADD COLUMN `profession_ids` text DEFAULT NULL
    COMMENT 'Comma-separated profession IDs when sub_class_id is Profession; NULL = all';

-- ─────────────────────────────────────────────────────────────
-- M11 (OPTIONAL): CREATE customer_points + customer_points_transactions
-- Apply ONLY if full points-wallet tracking is confirmed in scope.
-- ─────────────────────────────────────────────────────────────
-- CREATE TABLE IF NOT EXISTS `customer_points` (
--   `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
--   `customer_id` int(10) unsigned NOT NULL,
--   `points_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
--   `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
--   PRIMARY KEY (`id`),
--   UNIQUE KEY `uk_customer_id` (`customer_id`),
--   CONSTRAINT `fk_cp_customer`
--     FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
--
-- CREATE TABLE IF NOT EXISTS `customer_points_transactions` (
--   `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
--   `customer_id` int(10) unsigned NOT NULL,
--   `points` decimal(10,2) NOT NULL COMMENT 'Positive = earned; negative = redeemed',
--   `transaction_type` enum('earn','redeem') NOT NULL,
--   `reference_type` varchar(50) DEFAULT NULL,
--   `reference_id` int(10) unsigned DEFAULT NULL,
--   `description` varchar(255) DEFAULT NULL,
--   `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
--   PRIMARY KEY (`id`),
--   KEY `idx_customer_id` (`customer_id`),
--   KEY `idx_created_at` (`created_at`),
--   CONSTRAINT `fk_cpt_customer`
--     FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
