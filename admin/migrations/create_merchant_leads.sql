-- T20: Business signup / merchant interest leads
CREATE TABLE IF NOT EXISTS `merchant_leads` (
  `id`                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `contact_name`         VARCHAR(150)    NOT NULL,
  `org_name`             VARCHAR(200)    NULL,
  `category`             VARCHAR(100)    NULL,
  `email`                VARCHAR(150)    NULL,
  `phone`                VARCHAR(20)     NOT NULL,
  `message`              TEXT            NULL,
  `status`               ENUM('new','contacted','qualified','converted','rejected') NOT NULL DEFAULT 'new',
  `assigned_to_admin_id` INT UNSIGNED    NULL,
  `notes`                TEXT            NULL,
  `source`               VARCHAR(50)     NULL DEFAULT 'website',
  `created_at`           TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`           TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status`  (`status`),
  KEY `idx_phone`   (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
