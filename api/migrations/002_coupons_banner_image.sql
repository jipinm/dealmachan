-- Migration: Add banner_image column to coupons table
-- Run once against the deal_machan database

ALTER TABLE `coupons`
  ADD COLUMN `banner_image` VARCHAR(500) DEFAULT NULL
    COMMENT 'Relative path to deal/coupon promotional image, e.g. /uploads/coupon-banners/xxx.jpg'
  AFTER `terms_conditions`;

-- Ensure upload directory exists (handled by PHP on first upload)
-- Uploads will be stored in: api/uploads/coupon-banners/
