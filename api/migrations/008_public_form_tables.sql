-- ============================================================
-- Migration 008 — Public Form Tables
-- Created: 2026-02-21
-- Purpose: contact_messages, business_signups tables
--          needed by PublicFormController (POST /public/contact,
--          POST /public/business-signup)
-- ============================================================

-- Contact messages (from ContactPage)
CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id`         int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name`       varchar(100)     NOT NULL,
  `mobile`     varchar(15)      NOT NULL,
  `subject`    varchar(200)     NOT NULL,
  `message`    text             NOT NULL,
  `created_at` timestamp        NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Business sign-up enquiries (from BusinessSignupPage)
CREATE TABLE IF NOT EXISTS `business_signups` (
  `id`           int(10) unsigned NOT NULL AUTO_INCREMENT,
  `contact_name` varchar(100)     NOT NULL,
  `org_name`     varchar(200)     NOT NULL,
  `category`     varchar(100)     DEFAULT NULL,
  `email`        varchar(150)     NOT NULL,
  `phone`        varchar(15)      NOT NULL,
  `message`      text             DEFAULT NULL,
  `status`       enum('new','contacted','rejected') NOT NULL DEFAULT 'new',
  `created_at`   timestamp        NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_bs_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
