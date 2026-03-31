-- Migration M4: cards table — add assigned_to_store_id
-- Required by Task: C6

ALTER TABLE `cards`
  ADD COLUMN `assigned_to_store_id` int(10) unsigned DEFAULT NULL
    COMMENT 'If card batch is assigned to a store for on-site distribution'
    AFTER `assigned_to_merchant_id`,
  ADD KEY `idx_assigned_store` (`assigned_to_store_id`),
  ADD CONSTRAINT `fk_cards_store`
    FOREIGN KEY (`assigned_to_store_id`) REFERENCES `stores` (`id`) ON DELETE SET NULL;
