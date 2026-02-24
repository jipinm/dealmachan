<?php /* views/gift-coupons/add.php */ ?>

<div class="d-flex align-items-center mb-4">
    <a href="<?= BASE_URL ?>gift-coupons" class="btn btn-sm btn-outline-secondary me-3">
        <i class="fas fa-arrow-left me-1"></i> Back
    </a>
    <h4 class="mb-0">Gift a Coupon</h4>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <ul class="mb-0 ps-3">
        <?php foreach ($errors as $e): ?><li><?= escape($e) ?></li><?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="<?= BASE_URL ?>gift-coupons/save">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

            <div class="row g-3">
                <!-- Coupon -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Coupon <span class="text-danger">*</span></label>
                    <select name="coupon_id" class="form-select select2" required>
                        <option value="">— Select Coupon —</option>
                        <?php foreach ($coupons as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($old['coupon_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                            <?= escape($c['title']) ?> (<?= escape($c['coupon_code']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Customer -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Recipient Customer <span class="text-danger">*</span></label>
                    <select name="customer_id" class="form-select select2" required>
                        <option value="">— Select Customer —</option>
                        <?php foreach ($customers as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($old['customer_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                            <?= escape($c['name']) ?> — <?= escape($c['phone']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Expires At -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Gift Expiry Date <span class="text-muted fw-normal">(optional)</span></label>
                    <input type="datetime-local" name="expires_at" class="form-control"
                           min="<?= date('Y-m-d\TH:i') ?>"
                           value="<?= escape($old['expires_at'] ?? '') ?>">
                    <div class="form-text">Leave blank for no expiry on the gift itself.</div>
                </div>

                <!-- Requires Acceptance -->
                <div class="col-md-6 d-flex align-items-center">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="requires_acceptance" id="requiresAcceptance" value="1"
                               <?= !empty($old['requires_acceptance']) ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="requiresAcceptance">
                            Require customer acceptance
                        </label>
                        <div class="form-text">If checked, the customer must explicitly accept the gift before it's added to their wallet.</div>
                    </div>
                </div>
            </div>

            <hr class="my-4">
            <div class="d-flex gap-2 justify-content-end">
                <a href="<?= BASE_URL ?>gift-coupons" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-gift me-1"></i> Send Gift
                </button>
            </div>
        </form>
    </div>
</div>
