<?php /* views/settings/system.php */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>settings/profile">Settings</a></li>
                <li class="breadcrumb-item active">System Settings</li>
            </ol>
        </nav>
        <h1 class="h3 mb-0">System Settings</h1>
        <p class="text-muted mb-0 small">Platform-wide configuration — visible to Super Admin only.</p>
    </div>
</div>

<?php if ($flash_success): ?>
<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i><?= escape($flash_success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if ($flash_error): ?>
<div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle me-2"></i><?= escape($flash_error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<?php
$s = function(string $key) use ($settings): string {
    return htmlspecialchars($settings[$key]['value'] ?? '', ENT_QUOTES);
};
?>

<form method="POST" action="<?= BASE_URL ?>settings/save-system">
<div class="row g-4">

    <!-- Left: App Info -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold"><i class="fas fa-mobile-alt me-2 text-primary"></i>App Info</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">App Name</label>
                    <input type="text" name="app_name" class="form-control" value="<?= $s('app_name') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Tagline</label>
                    <input type="text" name="app_tagline" class="form-control" value="<?= $s('app_tagline') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Support Email</label>
                    <input type="email" name="support_email" class="form-control" value="<?= $s('support_email') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Support Phone</label>
                    <input type="text" name="support_phone" class="form-control" value="<?= $s('support_phone') ?>">
                </div>
                <div class="mb-0">
                    <label class="form-label fw-semibold">Maintenance Mode</label>
                    <select name="maintenance_mode" class="form-select">
                        <option value="0" <?= ($settings['maintenance_mode']['value'] ?? '0') == '0' ? 'selected' : '' ?>>Off</option>
                        <option value="1" <?= ($settings['maintenance_mode']['value'] ?? '0') == '1' ? 'selected' : '' ?>>On — site under maintenance</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold"><i class="fas fa-share-alt me-2 text-info"></i>Social &amp; Store Links</div>
            <div class="card-body">
                <?php foreach ([
                    'play_store_url'  => ['Play Store URL',  'fab fa-google-play'],
                    'app_store_url'   => ['App Store URL',   'fab fa-apple'],
                    'facebook_url'    => ['Facebook URL',    'fab fa-facebook'],
                    'instagram_url'   => ['Instagram URL',   'fab fa-instagram'],
                    'twitter_url'     => ['Twitter / X URL', 'fab fa-x-twitter'],
                ] as $key => [$label, $icon]): ?>
                <div class="mb-3">
                    <label class="form-label fw-semibold"><i class="<?= $icon ?> me-1"></i> <?= $label ?></label>
                    <input type="url" name="<?= $key ?>" class="form-control" value="<?= $s($key) ?>" placeholder="https://">
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Right: Rewards & App Versions -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold"><i class="fas fa-gift me-2 text-success"></i>Rewards &amp; Coupons</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Referral Reward Amount (₹)</label>
                    <input type="number" min="0" name="referral_reward_amount" class="form-control" value="<?= $s('referral_reward_amount') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Welcome Bonus (₹)</label>
                    <input type="number" min="0" name="welcome_bonus_amount" class="form-control" value="<?= $s('welcome_bonus_amount') ?>">
                </div>
                <div class="mb-0">
                    <label class="form-label fw-semibold">Coupon Default Validity (days)</label>
                    <input type="number" min="1" name="coupon_expiry_days" class="form-control" value="<?= $s('coupon_expiry_days') ?>">
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold"><i class="fas fa-mobile me-2 text-warning"></i>Min App Versions</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">iOS Minimum Version</label>
                    <input type="text" name="min_app_version_ios" class="form-control" value="<?= $s('min_app_version_ios') ?>" placeholder="1.0.0">
                </div>
                <div class="mb-0">
                    <label class="form-label fw-semibold">Android Minimum Version</label>
                    <input type="text" name="min_app_version_android" class="form-control" value="<?= $s('min_app_version_android') ?>" placeholder="1.0.0">
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold"><i class="fas fa-city me-2 text-secondary"></i>Defaults</div>
            <div class="card-body">
                <div class="mb-0">
                    <label class="form-label fw-semibold">Default City ID</label>
                    <input type="number" min="1" name="default_city_id" class="form-control" value="<?= $s('default_city_id') ?>">
                    <div class="form-text">Used when no city is selected during signup.</div>
                </div>
            </div>
        </div>
    </div>

</div>

<div class="mt-3 d-flex gap-2">
    <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-2"></i> Save Settings</button>
    <a href="<?= BASE_URL ?>settings/profile" class="btn btn-outline-secondary">Cancel</a>
</div>
</form>
