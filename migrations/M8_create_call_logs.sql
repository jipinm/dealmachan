-- Migration M8: Create call_logs table (new)
-- Required by Task: B7

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
