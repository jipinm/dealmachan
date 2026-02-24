-- T19: CMS static pages
CREATE TABLE IF NOT EXISTS `cms_pages` (
  `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug`              VARCHAR(100)    NOT NULL UNIQUE,
  `title`             VARCHAR(255)    NOT NULL,
  `content`           LONGTEXT        NULL,
  `meta_description`  VARCHAR(255)    NULL,
  `status`            ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
  `created_by_admin_id` INT UNSIGNED  NULL,
  `created_at`        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default pages
INSERT IGNORE INTO `cms_pages` (`slug`,`title`,`content`,`status`) VALUES
('about-us',       'About Us',        '<h2>About DealMachan</h2><p>Your content here.</p>', 'draft'),
('privacy-policy', 'Privacy Policy',  '<h2>Privacy Policy</h2><p>Your content here.</p>',  'draft'),
('terms',          'Terms of Service','<h2>Terms of Service</h2><p>Your content here.</p>','draft'),
('contact-us',     'Contact Us',      '<h2>Contact Us</h2><p>Your content here.</p>',      'draft');
