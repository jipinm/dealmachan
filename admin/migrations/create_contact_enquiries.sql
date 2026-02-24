-- T21: Contact form enquiries
CREATE TABLE IF NOT EXISTS `contact_enquiries` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`         VARCHAR(150)    NOT NULL,
  `mobile`       VARCHAR(20)     NULL,
  `email`        VARCHAR(150)    NULL,
  `subject`      VARCHAR(255)    NULL,
  `message`      TEXT            NOT NULL,
  `status`       ENUM('new','read','responded') NOT NULL DEFAULT 'new',
  `admin_notes`  TEXT            NULL,
  `created_at`   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
