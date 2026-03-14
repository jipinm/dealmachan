-- Add card_image_front and card_image_back columns to the cards table
-- to store per-card physical pre-printed card images.

ALTER TABLE `cards`
    ADD COLUMN `card_image_front` varchar(255) DEFAULT NULL AFTER `card_image`,
    ADD COLUMN `card_image_back`  varchar(255) DEFAULT NULL AFTER `card_image_front`;
