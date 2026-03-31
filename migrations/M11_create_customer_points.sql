-- Migration M11: Create customer_points and customer_points_transactions tables (optional)
-- Required by Task: H2
-- APPLY ONLY IF: full pay-back points wallet ledger is confirmed in scope.
-- If only displaying the rate (pay_back_points_value from card_configurations), this migration is NOT needed.

CREATE TABLE IF NOT EXISTS `customer_points` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned NOT NULL,
  `points_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_customer_id` (`customer_id`),
  CONSTRAINT `fk_cp_customer`
    FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `customer_points_transactions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned NOT NULL,
  `points` decimal(10,2) NOT NULL
    COMMENT 'Positive = earned; negative = redeemed',
  `transaction_type` enum('earn','redeem') NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL
    COMMENT 'Source context: e.g. coupon_redemption, store_purchase',
  `reference_id` int(10) unsigned DEFAULT NULL
    COMMENT 'ID of the referenced entity (e.g. redemption ID)',
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_cpt_customer`
    FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
