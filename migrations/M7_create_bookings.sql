-- Migration M7: Create bookings table (new)
-- Required by Task: B8

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
