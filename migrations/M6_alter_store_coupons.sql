-- Migration M6: store_coupons table — add admin-management columns
-- Required by Tasks: D1, D2
-- NOTE: Existing rows will retain NULL for created_by_admin_id (legacy merchant-created).
--       assignment_type defaults to 'merchant_request' for all existing rows.

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
    COMMENT 'auto_assign = given to customers on card activation; merchant_request = merchant submits request via admin queue'
    AFTER `assigned_count`,
  ADD KEY `idx_created_by_admin` (`created_by_admin_id`),
  ADD CONSTRAINT `fk_sc_admin`
    FOREIGN KEY (`created_by_admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL;
