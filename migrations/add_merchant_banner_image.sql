-- Migration: Add banner_image column to merchants table
-- Date: 2026-03-13
-- Adds a separate wide banner/hero image field for merchant profiles.

ALTER TABLE `merchants`
    ADD COLUMN `banner_image` varchar(500) DEFAULT NULL
        COMMENT 'Relative path to merchant banner/hero image (served via API)'
        AFTER `business_logo`;
