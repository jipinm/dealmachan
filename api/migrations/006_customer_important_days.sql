-- Migration 006: customer_important_days table
-- Run on: deal_machan database

CREATE TABLE IF NOT EXISTS `customer_important_days` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned NOT NULL,
  `event_type` enum('Birthday','Anniversary','Others') NOT NULL,
  `event_specify` varchar(100) DEFAULT NULL COMMENT 'Custom label when type=Others',
  `event_day` tinyint(2) unsigned NOT NULL,
  `event_month` tinyint(2) unsigned NOT NULL,
  `person_name` varchar(100) DEFAULT NULL COMMENT 'Name of the person (optional)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_cid_customer` (`customer_id`),
  KEY `idx_cid_month_day` (`customer_id`, `event_month`, `event_day`),
  CONSTRAINT `fk_cid_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
