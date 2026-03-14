<?php /* views/card-configurations/view.php */
$cfg             = $config;
$classColors     = ['silver' => 'secondary', 'gold' => 'warning', 'platinum' => 'info', 'diamond' => 'primary'];
$premiumPartners = array_filter($cfg['partners'] ?? [], fn($p) => $p['partner_type'] === 'premium');
$normalPartners  = array_filter($cfg['partners'] ?? [], fn($p) => $p['partner_type'] === 'normal');
?>

<!-- Header -->
<div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="mb-0"><?= escape($cfg['name']) ?></h4>
        <small class="text-muted">
            <a href="<?= BASE_URL ?>card-configurations">Card Configurations</a> / Detail
        </small>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= BASE_URL ?>card-configurations/edit?id=<?= $cfg['id'] ?>" class="btn btn-sm btn-primary">
            <i class="fas fa-edit me-1"></i> Edit
        </a>
        <?php if (empty($cfg['cards_count']) || $cfg['cards_count'] == 0): ?>
        <form method="POST" action="<?= BASE_URL ?>card-configurations/delete"
              onsubmit="return confirm('Delete this configuration permanently?')">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="id" value="<?= $cfg['id'] ?>">
            <button type="submit" class="btn btn-sm btn-outline-danger">
                <i class="fas fa-trash me-1"></i> Delete
            </button>
        </form>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>card-configurations" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<?php if (!empty($_SESSION['success'])): ?>
<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3">
    <i class="fas fa-check-circle me-2"></i><?= escape($_SESSION['success']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['success']); endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3">
    <i class="fas fa-exclamation-circle me-2"></i><?= escape($_SESSION['error']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['error']); endif; ?>

<div class="row g-4">

    <!-- LEFT -->
    <div class="col-lg-8">

        <!-- Core Details -->
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-semibold d-flex align-items-center justify-content-between">
                <span><i class="fas fa-id-card me-2 text-primary"></i> Details</span>
                <div>
                    <span class="badge bg-<?= $classColors[$cfg['classification']] ?? 'secondary' ?> me-1">
                        <?= ucfirst($cfg['classification']) ?>
                    </span>
                    <span class="badge bg-<?= $cfg['status'] === 'active' ? 'success' : 'secondary' ?>">
                        <?= ucfirst($cfg['status']) ?>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-4">
                        <label class="text-muted small">Price</label>
                        <div><?= $cfg['price'] > 0 ? '&#x20B9;' . number_format($cfg['price'], 2) : '<span class="text-muted">Free</span>' ?></div>
                    </div>
                    <div class="col-sm-4">
                        <label class="text-muted small">Validity</label>
                        <div><?= $cfg['validity_days'] ?> days</div>
                    </div>
                    <div class="col-sm-4">
                        <label class="text-muted small">Monthly Max</label>
                        <div><?= $cfg['monthly_maximum'] ?? '<span class="text-muted">Unlimited</span>' ?></div>
                    </div>
                    <div class="col-sm-4">
                        <label class="text-muted small">Max Live Coupons</label>
                        <div><?= $cfg['max_live_coupons'] ?? '<span class="text-muted">Unlimited</span>' ?></div>
                    </div>
                    <div class="col-sm-4">
                        <label class="text-muted small">Coupon Authorization</label>
                        <div><?= $cfg['coupon_authorization'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-danger">No</span>' ?></div>
                    </div>
                    <div class="col-sm-4">
                        <label class="text-muted small">Publicly Selectable</label>
                        <div><?= $cfg['is_publicly_selectable'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' ?></div>
                    </div>
                    <?php if (!empty($cfg['features_html'])): ?>
                    <div class="col-12">
                        <label class="text-muted small">Features / Benefits</label>
                        <div class="border rounded p-3 bg-light mt-1 small">
                            <?= $cfg['features_html'] /* trusted admin input */ ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Card Images -->
        <?php if (!empty($cfg['card_image_front']) || !empty($cfg['card_image_back'])): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-semibold"><i class="fas fa-images me-2 text-info"></i> Card Images</div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-4">
                    <?php foreach (['card_image_front' => 'Front', 'card_image_back' => 'Back'] as $imgKey => $label): ?>
                    <?php if (!empty($cfg[$imgKey])): ?>
                    <div>
                        <p class="form-text mb-1"><?= $label ?></p>
                        <img src="<?= imageUrl($cfg[$imgKey]) ?>"
                             class="img-thumbnail" style="max-height:160px;" alt="<?= $label ?>"
                             onerror="this.src='<?= imageUrl('') ?>'">
                    </div>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Premium Partners -->
        <?php if (!empty($premiumPartners)): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-semibold">
                <i class="fas fa-star me-2 text-warning"></i> Premium Partners
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-3">
                    <?php foreach ($premiumPartners as $p): ?>
                    <div class="text-center">
                        <?php if (!empty($p['partner_image'])): ?>
                        <a href="<?= escape($p['url'] ?? '#') ?>" target="_blank" rel="noopener noreferrer">
                            <img src="<?= imageUrl($p['partner_image']) ?>"
                                 class="img-thumbnail" style="max-height:80px;" alt="Partner"
                                 onerror="this.src='<?= imageUrl('') ?>'">
                        </a>
                        <?php elseif (!empty($p['url'])): ?>
                        <a href="<?= escape($p['url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-warning">
                            <i class="fas fa-link me-1"></i> Link
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Normal Partners -->
        <?php if (!empty($normalPartners)): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-semibold">
                <i class="fas fa-handshake me-2 text-secondary"></i> Normal Partners
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-3">
                    <?php foreach ($normalPartners as $p): ?>
                    <div class="text-center">
                        <?php if (!empty($p['partner_image'])): ?>
                        <a href="<?= escape($p['url'] ?? '#') ?>" target="_blank" rel="noopener noreferrer">
                            <img src="<?= imageUrl($p['partner_image']) ?>"
                                 class="img-thumbnail" style="max-height:60px;" alt="Partner"
                                 onerror="this.src='<?= imageUrl('') ?>'">
                        </a>
                        <?php elseif (!empty($p['url'])): ?>
                        <a href="<?= escape($p['url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-secondary btn-sm">
                            <i class="fas fa-link me-1"></i> Link
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- RIGHT -->
    <div class="col-lg-4">

        <!-- Meta -->
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-semibold"><i class="fas fa-info-circle me-2 text-secondary"></i> Info</div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Configuration ID</span>
                        <strong>#<?= $cfg['id'] ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Created By</span>
                        <span><?= escape($cfg['created_by_name'] ?? '&mdash;') ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Created</span>
                        <small><?= formatDate($cfg['created_at']) ?></small>
                    </li>
                    <?php if ($cfg['updated_at']): ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Updated</span>
                        <small><?= formatDate($cfg['updated_at']) ?></small>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <!-- Sub-classifications -->
        <?php if (!empty($cfg['sub_classifications'])): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-semibold"><i class="fas fa-tags me-2 text-primary"></i> Sub-Classifications</div>
            <div class="card-body">
                <?php foreach ($cfg['sub_classifications'] as $sc): ?>
                    <span class="badge bg-primary-subtle text-primary me-1 mb-1"><?= escape($sc['name']) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Cities -->
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-semibold"><i class="fas fa-city me-2 text-info"></i> City Availability</div>
            <div class="card-body">
                <?php if (empty($cfg['cities'])): ?>
                    <span class="text-muted">All Cities</span>
                <?php else: ?>
                    <?php foreach ($cfg['cities'] as $city): ?>
                        <span class="badge bg-info-subtle text-info me-1 mb-1"><?= escape($city['city_name']) ?></span>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>
