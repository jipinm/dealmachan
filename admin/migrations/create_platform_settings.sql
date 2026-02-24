-- T18: System-wide platform settings
CREATE TABLE IF NOT EXISTS `platform_settings` (
  `setting_key`   VARCHAR(100)  NOT NULL,
  `setting_value` TEXT          NULL,
  `description`   VARCHAR(255)  NULL,
  `updated_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default seed values
INSERT IGNORE INTO `platform_settings` (`setting_key`, `setting_value`, `description`) VALUES
('app_name',              'DealMachan',              'Application display name'),
('app_tagline',           'Best Deals Near You',     'App tagline / subtitle'),
('support_email',         'support@dealmachan.com',  'Customer support email'),
('support_phone',         '',                        'Support phone number'),
('referral_reward_amount','50',                      'Default referral reward (₹)'),
('welcome_bonus_amount',  '0',                       'Welcome bonus for new customers (₹)'),
('maintenance_mode',      '0',                       '1 = site under maintenance'),
('min_app_version_ios',   '1.0.0',                   'Minimum iOS app version required'),
('min_app_version_android','1.0.0',                  'Minimum Android app version required'),
('play_store_url',        '',                        'Google Play Store link'),
('app_store_url',         '',                        'Apple App Store link'),
('facebook_url',          '',                        'Facebook page URL'),
('instagram_url',         '',                        'Instagram page URL'),
('twitter_url',           '',                        'Twitter / X page URL'),
('default_city_id',       '1',                       'Default city for new signups'),
('coupon_expiry_days',    '30',                      'Default coupon validity in days');
