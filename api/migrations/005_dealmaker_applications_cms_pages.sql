-- Migration 005: DealMaker applications + CMS pages tables
-- Run on: deal_machan database

-- ── dealmaker_applications ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `dealmaker_applications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned NOT NULL,
  `motivation` text NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `experience` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reviewed_by_admin_id` int(10) unsigned DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_da_customer` (`customer_id`),
  KEY `idx_da_status` (`status`),
  CONSTRAINT `fk_da_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── cms_pages ─────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `cms_pages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext NOT NULL COMMENT 'HTML content',
  `meta_description` varchar(300) DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cms_slug` (`slug`),
  KEY `idx_cms_published` (`is_published`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed some starter CMS pages
INSERT IGNORE INTO `cms_pages` (`slug`, `title`, `content`, `meta_description`) VALUES
('privacy-policy', 'Privacy Policy',
 '<h2>Privacy Policy</h2><p>DealMachan is committed to protecting your privacy. This policy explains how we collect, use, and safeguard your personal information.</p><h3>Information We Collect</h3><p>We collect information you provide directly to us, such as when you create an account, subscribe to a coupon, or contact us for support.</p><h3>How We Use Information</h3><p>We use the information we collect to provide, maintain, and improve our services, process transactions, and send relevant offers.</p><h3>Data Security</h3><p>We implement appropriate security measures to protect your personal information against unauthorised access, alteration, disclosure, or destruction.</p><h3>Contact Us</h3><p>If you have any questions about this Privacy Policy, please contact us at hello@dealmachan.com</p>',
 'Read DealMachan\'s privacy policy to understand how we collect and use your data.'),
('terms-and-conditions', 'Terms & Conditions',
 '<h2>Terms &amp; Conditions</h2><p>By accessing or using DealMachan, you agree to be bound by these Terms and Conditions.</p><h3>Use of Service</h3><p>DealMachan provides a platform for customers to discover and redeem offers from local merchants. Coupons and deals are subject to merchant terms and availability.</p><h3>Account Responsibility</h3><p>You are responsible for maintaining the confidentiality of your account credentials and for all activities that occur under your account.</p><h3>Coupon Usage</h3><p>Coupons must be used before the stated expiry date. DealMachan is not responsible for merchant closure or inability to honour a coupon.</p><h3>Changes to Terms</h3><p>We reserve the right to modify these terms at any time. Continued use of the service constitutes acceptance of the updated terms.</p>',
 'Read DealMachan\'s terms and conditions of service.'),
('faq', 'Frequently Asked Questions',
 '<h2>Frequently Asked Questions</h2><h3>How do I save a coupon?</h3><p>Log in to your account, browse the deals, and click "Save" on any coupon. It will appear in your wallet at /wallet.</p><h3>How do I redeem a coupon?</h3><p>Visit the store and show them the QR code from your wallet. The merchant will scan it to apply the discount.</p><h3>Can I use multiple coupons at once?</h3><p>Each transaction allows one coupon redemption at a time. Check the individual coupon terms for restrictions.</p><h3>How do I become a Deal Maker?</h3><p>Navigate to More &gt; Deal Maker and submit an application. Our team reviews applications within 2 business days.</p><h3>What is the loyalty card?</h3><p>Your DealMachan loyalty card determines how many coupons you can save simultaneously. Upgrade to Premium or Deal Maker for higher limits.</p>',
 'Find answers to common questions about DealMachan coupons, accounts, and more.');
