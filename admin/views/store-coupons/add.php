<?php /* views/store-coupons/add.php */ ?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>store-coupons">Store Coupons</a></li>
        <li class="breadcrumb-item active">Create</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-0">Create Store Coupon</h4>
        <small class="text-muted">Admin-created coupon linked to a store</small>
    </div>
    <a href="<?= BASE_URL ?>store-coupons" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i> Back
    </a>
</div>

<?php if (!empty($_SESSION['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3">
    <i class="fas fa-exclamation-circle me-2"></i><?= escape($_SESSION['error']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['error']); endif; ?>

<form method="POST" action="<?= BASE_URL ?>store-coupons/add" id="storeCouponForm">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

    <div class="row g-4">
        <!-- LEFT -->
        <div class="col-lg-8">

            <!-- Basic Info -->
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">
                    <i class="fas fa-tags me-2 text-primary"></i>Coupon Details
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Weekend Special – 20% Off"
                               maxlength="255" required value="<?= escape($_POST['title'] ?? '') ?>">
                        <div class="form-text">Displayed to customers in their wallet and on the store profile.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"
                                  placeholder="Optional details visible to the customer…"><?= escape($_POST['description'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Terms &amp; Conditions <span class="text-danger">*</span></label>
                        <textarea name="terms_conditions" class="form-control" rows="4"
                                  placeholder="e.g. Valid on weekends only. Cannot be combined with other offers. One use per customer."
                                  required><?= escape($_POST['terms_conditions'] ?? '') ?></textarea>
                        <div class="form-text">Displayed to the customer when they view or redeem this coupon.</div>
                    </div>
                </div>
            </div>

            <!-- Store Selection -->
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">
                    <i class="fas fa-store me-2 text-info"></i>Store
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Select Store <span class="text-danger">*</span></label>
                        <select name="store_id" id="storeSelect" class="form-select" required onchange="updateStorePreview()">
                            <option value="">— Choose a store —</option>
                            <?php foreach ($stores as $s): ?>
                                <option value="<?= $s['id'] ?>"
                                        data-logo="<?= escape($s['store_logo'] ?? '') ?>"
                                        data-merchant="<?= escape($s['business_name']) ?>"
                                        <?= (isset($_POST['store_id']) && (int)$_POST['store_id'] === (int)$s['id']) ? 'selected' : '' ?>>
                                    <?= escape($s['business_name']) ?> — <?= escape($s['store_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Store preview -->
                    <div id="storePreview" class="d-none border rounded-3 p-3 bg-light">
                        <div class="d-flex align-items-center gap-3">
                            <img id="storeLogo" src="" alt="Store logo"
                                 class="rounded-2 border bg-white object-fit-cover"
                                 style="width:56px;height:56px;">
                            <div>
                                <div class="fw-semibold small" id="storeMerchantName"></div>
                                <div class="text-muted small">Store image used as coupon visual (no upload needed)</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Discount Settings -->
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">
                    <i class="fas fa-percent me-2 text-success"></i>Discount Settings
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label">Discount Type <span class="text-danger">*</span></label>
                            <select name="discount_type" id="discountType" class="form-select" required onchange="updateDiscountSymbol()">
                                <option value="percentage" <?= (($_POST['discount_type'] ?? 'percentage') === 'percentage') ? 'selected' : '' ?>>Percentage (%)</option>
                                <option value="fixed"      <?= (($_POST['discount_type'] ?? '') === 'fixed') ? 'selected' : '' ?>>Fixed Amount (₹)</option>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Discount Value <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="discount_value" id="discountValue" class="form-control"
                                       min="0.01" step="0.01" placeholder="0" required
                                       value="<?= escape($_POST['discount_value'] ?? '') ?>">
                                <span class="input-group-text" id="discountSymbol">%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Validity -->
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">
                    <i class="fas fa-calendar-alt me-2 text-warning"></i>Validity Period
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label">Valid From</label>
                            <input type="datetime-local" name="valid_from" class="form-control"
                                   value="<?= escape($_POST['valid_from'] ?? '') ?>">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Valid Until</label>
                            <input type="datetime-local" name="valid_until" class="form-control"
                                   value="<?= escape($_POST['valid_until'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <!-- RIGHT -->
        <div class="col-lg-4">

            <!-- Assignment Settings -->
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">
                    <i class="fas fa-share-nodes me-2 text-purple"></i>Assignment Settings
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Assignment Type <span class="text-danger">*</span></label>
                        <select name="assignment_type" class="form-select">
                            <option value="merchant_request" <?= (($_POST['assignment_type'] ?? 'merchant_request') === 'merchant_request') ? 'selected' : '' ?>>
                                Merchant Must Request
                            </option>
                            <option value="auto_assign" <?= (($_POST['assignment_type'] ?? '') === 'auto_assign') ? 'selected' : '' ?>>
                                Auto-assign on Card Activation
                            </option>
                        </select>
                        <div class="form-text">
                            <strong>Auto-assign:</strong> coupon added automatically when a customer activates a card.<br>
                            <strong>Merchant Request:</strong> merchant must submit a request to distribute.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Total Quantity</label>
                        <input type="number" name="total_quantity" class="form-control" min="1"
                               placeholder="Unlimited (leave blank)"
                               value="<?= escape($_POST['total_quantity'] ?? '') ?>">
                        <div class="form-text">Max number of times this coupon can be assigned. Leave blank for unlimited.</div>
                    </div>

                    <div class="border rounded-3 p-3 bg-light">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="requiresAcceptance"
                                   name="requires_acceptance" value="1"
                                   <?= !empty($_POST['requires_acceptance']) ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold" for="requiresAcceptance">
                                Requires Acceptance (Grab Now)
                            </label>
                        </div>
                        <div class="text-muted small mt-1">
                            When enabled, customers must tap <strong>Grab Now</strong> to claim this coupon. Otherwise it is
                            listed as already available.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notice -->
            <div class="card border-info-subtle bg-info-subtle shadow-sm mb-4">
                <div class="card-body py-3 px-3">
                    <div class="d-flex gap-2">
                        <i class="fas fa-info-circle text-info mt-1"></i>
                        <div class="small">
                            A unique coupon code will be auto-generated (e.g. <code>SC-A1B2C3D4</code>).
                            The store's own logo serves as the coupon visual — no image upload needed.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Create Store Coupon
                </button>
                <a href="<?= BASE_URL ?>store-coupons" class="btn btn-outline-secondary">Cancel</a>
            </div>

        </div>
    </div>
</form>

<script>
function updateStorePreview() {
    const sel     = document.getElementById('storeSelect');
    const opt     = sel.options[sel.selectedIndex];
    const preview = document.getElementById('storePreview');

    if (!sel.value) {
        preview.classList.add('d-none');
        return;
    }

    const logo     = opt.dataset.logo || '';
    const merchant = opt.dataset.merchant || '';

    document.getElementById('storeLogo').src = logo
        ? '<?= rtrim(BASE_URL, '/') ?>/uploads/stores/' + logo
        : '<?= BASE_URL ?>assets/images/placeholder-store.png';
    document.getElementById('storeMerchantName').textContent = merchant;
    preview.classList.remove('d-none');
}

function updateDiscountSymbol() {
    const type   = document.getElementById('discountType').value;
    const symbol = document.getElementById('discountSymbol');
    symbol.textContent = type === 'percentage' ? '%' : '₹';
}

// Init on page load (for repopulation after validation error)
document.addEventListener('DOMContentLoaded', function () {
    updateStorePreview();
    updateDiscountSymbol();
});
</script>
