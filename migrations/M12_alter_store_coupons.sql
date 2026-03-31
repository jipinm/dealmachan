-- M12: Add terms_conditions column to store_coupons
-- Required by scope section 4.2: "Required fields: Title, Description, Code (auto-generated), Terms & Conditions, ..."

ALTER TABLE `store_coupons`
ADD COLUMN `terms_conditions` TEXT NULL
COMMENT 'Terms and conditions displayed to the customer on redemption'
AFTER `description`;
