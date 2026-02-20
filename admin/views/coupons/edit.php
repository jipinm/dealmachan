<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-0">Edit Coupon</h4>
        <small class="text-muted">
            <a href="<?= BASE_URL ?>/coupons">Coupons</a> /
            <a href="<?= BASE_URL ?>/coupons/detail?id=<?= $coupon['id'] ?>"><?= escape($coupon['title']) ?></a> / Edit
        </small>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/coupons/detail?id=<?= $coupon['id'] ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<form method="POST" action="<?= BASE_URL ?>/coupons/edit?id=<?= $coupon['id'] ?>" id="couponForm">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

    <div class="row g-4">
        <!-- LEFT -->
        <div class="col-lg-8">
            <!-- Basic Info -->
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold"><i class="fas fa-ticket-alt me-2 text-primary"></i>Coupon Details</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Coupon Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="<?= escape($coupon['title']) ?>" required maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"><?= escape($coupon['description'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Coupon Code <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" name="coupon_code" id="coupon_code" class="form-control text-uppercase"
                                   value="<?= escape($coupon['coupon_code']) ?>"
                                   maxlength="50" pattern="[A-Za-z0-9_\-]+" required>
                            <button type="button" class="btn btn-outline-secondary" onclick="generateCode()">
                                <i class="fas fa-random me-1"></i> Generate
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Terms & Conditions</label>
                        <textarea name="terms_conditions" class="form-control" rows="3"><?= escape($coupon['terms_conditions'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Discount -->
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold"><i class="fas fa-percent me-2 text-success"></i>Discount Settings</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label">Discount Type <span class="text-danger">*</span></label>
                            <select name="discount_type" class="form-select" id="discountType" required onchange="updateDiscountLabels()">
                                <option value="percentage" <?= $coupon['discount_type'] === 'percentage' ? 'selected' : '' ?>>Percentage (%)</option>
                                <option value="fixed"      <?= $coupon['discount_type'] === 'fixed'      ? 'selected' : '' ?>>Fixed Amount (₹)</option>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" id="discountValueLabel">
                                <?= $coupon['discount_type'] === 'percentage' ? 'Discount Value (%)' : 'Discount Value (₹)' ?>
                                <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="number" name="discount_value" class="form-control" min="0.01" step="0.01" required
                                       value="<?= escape($coupon['discount_value']) ?>">
                                <span class="input-group-text" id="discountSymbol">
                                    <?= $coupon['discount_type'] === 'percentage' ? '%' : '₹' ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Min Purchase Amount (₹)</label>
                            <input type="number" name="min_purchase_amount" class="form-control" min="0" step="0.01"
                                   value="<?= escape($coupon['min_purchase_amount'] ?? '') ?>" placeholder="Leave blank for none">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Max Discount Amount (₹)</label>
                            <input type="number" name="max_discount_amount" class="form-control" min="0" step="0.01"
                                   value="<?= escape($coupon['max_discount_amount'] ?? '') ?>" placeholder="Leave blank for none">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Validity -->
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold"><i class="fas fa-calendar-alt me-2 text-info"></i>Validity & Usage</div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php
                        $vf = $coupon['valid_from']  ? date('Y-m-d\TH:i', strtotime($coupon['valid_from']))  : '';
                        $vu = $coupon['valid_until'] ? date('Y-m-d\TH:i', strtotime($coupon['valid_until'])) : '';
                        ?>
                        <div class="col-sm-6">
                            <label class="form-label">Valid From</label>
                            <input type="datetime-local" name="valid_from" class="form-control" value="<?= $vf ?>">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Valid Until</label>
                            <input type="datetime-local" name="valid_until" class="form-control" value="<?= $vu ?>">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Usage Limit</label>
                            <input type="number" name="usage_limit" class="form-control" min="1"
                                   value="<?= escape($coupon['usage_limit'] ?? '') ?>" placeholder="Unlimited">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label text-muted">Current Usage Count</label>
                            <div class="form-control bg-light text-muted"><?= number_format($coupon['usage_count'] ?? 0) ?> uses</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tags -->
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold"><i class="fas fa-tags me-2 text-secondary"></i>Tags</div>
                <div class="card-body">
                    <select name="tags[]" class="form-select select2-multiple" multiple data-placeholder="Select tags…">
                        <?php foreach ($tags as $tag): ?>
                            <option value="<?= $tag['id'] ?>" <?= in_array($tag['id'], $selectedTags) ? 'selected' : '' ?>>
                                <?= escape($tag['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- RIGHT -->
        <div class="col-lg-4">
            <!-- Merchant & Store -->
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold"><i class="fas fa-store me-2 text-warning"></i>Merchant & Store</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Merchant <span class="text-danger">*</span></label>
                        <select name="merchant_id" id="merchant_id" class="form-select select2-single"
                                data-placeholder="Select merchant…" required onchange="loadStores(this.value)">
                            <option value="">— Select Merchant —</option>
                            <?php foreach ($merchants as $m): ?>
                                <option value="<?= $m['id'] ?>" <?= $coupon['merchant_id'] == $m['id'] ? 'selected' : '' ?>>
                                    <?= escape($m['business_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Store</label>
                        <select name="store_id" id="store_id" class="form-select select2-single" data-placeholder="All Stores">
                            <option value="">All Stores</option>
                            <?php foreach ($stores as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= $coupon['store_id'] == $s['id'] ? 'selected' : '' ?>>
                                    <?= escape($s['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Settings -->
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold"><i class="fas fa-sliders-h me-2 text-dark"></i>Settings</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <?php foreach (['active', 'inactive', 'expired'] as $s): ?>
                                <option value="<?= $s ?>" <?= $coupon['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Approval Status</label>
                        <select name="approval_status" class="form-select">
                            <?php foreach (['pending', 'approved', 'rejected'] as $s): ?>
                                <option value="<?= $s ?>" <?= $coupon['approval_status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-check form-switch mt-3">
                        <input type="checkbox" name="is_admin_coupon" class="form-check-input" id="is_admin_coupon" role="switch"
                               <?= $coupon['is_admin_coupon'] ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="is_admin_coupon">Admin Coupon</label>
                    </div>
                </div>
            </div>

            <!-- Metadata -->
            <div class="card shadow-sm mb-4 text-muted small">
                <div class="card-body">
                    <div><strong>Created:</strong> <?= formatDateTime($coupon['created_at']) ?></div>
                    <div><strong>Updated:</strong> <?= formatDateTime($coupon['updated_at']) ?></div>
                    <?php if ($coupon['approved_at']): ?>
                    <div><strong>Approved:</strong> <?= formatDateTime($coupon['approved_at']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Submit -->
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Save Changes</button>
                <a href="<?= BASE_URL ?>/coupons/detail?id=<?= $coupon['id'] ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </div>
    </div>
</form>

<script>
function generateCode() {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let code = '';
    for (let i = 0; i < 8; i++) code += chars[Math.floor(Math.random() * chars.length)];
    document.getElementById('coupon_code').value = code;
}

function updateDiscountLabels() {
    const type = document.getElementById('discountType').value;
    const label  = document.getElementById('discountValueLabel');
    const symbol = document.getElementById('discountSymbol');
    if (type === 'percentage') {
        label.innerHTML  = 'Discount Value (%) <span class="text-danger">*</span>';
        symbol.textContent = '%';
    } else {
        label.innerHTML  = 'Discount Value (₹) <span class="text-danger">*</span>';
        symbol.textContent = '₹';
    }
}

function loadStores(merchantId, selectedStoreId) {
    const sel = document.getElementById('store_id');
    sel.innerHTML = '<option value="">All Stores</option>';
    if (!merchantId) return;
    fetch(`<?= BASE_URL ?>/coupons/stores-json?merchant_id=${merchantId}`)
        .then(r => r.json())
        .then(stores => {
            stores.forEach(s => {
                const opt = document.createElement('option');
                opt.value = s.id;
                opt.textContent = s.name;
                if (selectedStoreId && s.id == selectedStoreId) opt.selected = true;
                sel.appendChild(opt);
            });
            if (window.$) $(sel).trigger('change');
        });
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.select2-multiple').forEach(el => {
        $(el).select2({ theme: 'bootstrap-5', placeholder: el.dataset.placeholder });
    });
    document.querySelectorAll('.select2-single').forEach(el => {
        $(el).select2({ theme: 'bootstrap-5', placeholder: el.dataset.placeholder, allowClear: true });
    });

    document.getElementById('coupon_code').addEventListener('input', function () {
        const pos = this.selectionStart;
        this.value = this.value.toUpperCase();
        this.setSelectionRange(pos, pos);
    });
});
</script>
