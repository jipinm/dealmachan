-- Migration M2: stores table — add website_link, booking_enabled, booking_confirmation_required
-- Required by Tasks: B5, B6, B7, B8

ALTER TABLE `stores`
  ADD COLUMN `website_link` varchar(255) DEFAULT NULL
    COMMENT 'Public website URL of the store'
    AFTER `email`,
  ADD COLUMN `booking_enabled` tinyint(1) NOT NULL DEFAULT 0
    COMMENT '1 = Book Now button is visible on customer app store profile'
    AFTER `description`,
  ADD COLUMN `booking_confirmation_required` tinyint(1) NOT NULL DEFAULT 0
    COMMENT '1 = booking status starts as pending until merchant confirms; 0 = auto-confirmed'
    AFTER `booking_enabled`;
