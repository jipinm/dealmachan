<?php /* views/subscriptions/edit.php */ ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-0">Edit Subscription #<?= $sub['id'] ?></h4>
        <small class="text-muted">
            <a href="<?= BASE_URL ?>subscriptions">Subscriptions</a> /
            <a href="<?= BASE_URL ?>subscriptions/detail?id=<?= $sub['id'] ?>"><?= escape($sub['display_name']) ?></a> / Edit
        </small>
    </div>
    <a href="<?= BASE_URL ?>subscriptions/detail?id=<?= $sub['id'] ?>" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i> Back
    </a>
</div>

<?php if (!empty($_SESSION['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3">
    <i class="fas fa-exclamation-circle me-2"></i><?= escape($_SESSION['error']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['error']); endif; ?>

<form method="POST" action="<?= BASE_URL ?>subscriptions/edit?id=<?= $sub['id'] ?>">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
    <input type="hidden" name="user_id"   value="<?= $sub['user_id'] ?>">
    <input type="hidden" name="user_type" value="<?= $sub['user_type'] ?>">

    <div class="row g-4">
        <div class="col-lg-8">

            <!-- Subscriber (read-only) -->
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold"><i class="fas fa-user me-2 text-info"></i> Subscriber (read-only)</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <dt class="text-muted small">Name</dt>
                            <dd class="fw-semibold"><?= escape($sub['display_name']) ?></dd>
                        </div>
                        <div class="col-sm-6">
                            <dt class="text-muted small">Type</dt>
                            <dd><?= ucfirst($sub['user_type']) ?> (user_id: <?= $sub['user_id'] ?>)</dd>
                        </div>
                        <div class="col-sm-6">
                            <dt class="text-muted small">Email / Phone</dt>
                            <dd><?= escape($sub['user_email'] ?? $sub['user_phone'] ?? '&mdash;') ?></dd>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Plan & Dates -->
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold"><i class="fas fa-calendar me-2 text-primary"></i> Plan & Validity</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-4">
                            <label class="form-label">Plan Type <span class="text-danger">*</span></label>
                            <select name="plan_type" class="form-select" required>
                                <?php foreach (['basic', 'standard', 'premium'] as $p): ?>
                                <option value="<?= $p ?>" <?= $sub['plan_type'] === $p ? 'selected' : '' ?>><?= ucfirst($p) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" class="form-control" required value="<?= $sub['start_date'] ?>">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label">Expiry Date <span class="text-danger">*</span></label>
                            <input type="date" name="expiry_date" class="form-control" required value="<?= $sub['expiry_date'] ?>">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Payment Amount (₹)</label>
                            <input type="number" name="payment_amount" class="form-control" step="0.01" min="0"
                                   value="<?= $sub['payment_amount'] ?? '' ?>">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Payment Method</label>
                            <select name="payment_method" class="form-select">
                                <option value="">&mdash; None / Unknown &mdash;</option>
                                <?php foreach (['cash', 'card', 'upi', 'wallet', 'other'] as $m): ?>
                                <option value="<?= $m ?>" <?= ($sub['payment_method'] ?? '') === $m ? 'selected' : '' ?>><?= ucfirst($m) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold"><i class="fas fa-sliders-h me-2"></i> Settings</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <?php foreach (['active', 'expired', 'cancelled'] as $s): ?>
                            <option value="<?= $s ?>" <?= $sub['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="auto_renew" id="auto_renew" value="1" <?= $sub['auto_renew'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="auto_renew">Auto Renew</label>
                    </div>
                </div>
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Changes</button>
                <a href="<?= BASE_URL ?>subscriptions/detail?id=<?= $sub['id'] ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </div>
    </div>
</form>
