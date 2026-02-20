<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-0">Create Coupon</h4>
        <small class="text-muted"><a href="<?= BASE_URL ?>/coupons">Coupons</a> / New</small>
    </div>
    <a href="<?= BASE_URL ?>/coupons" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
</div>

<form method="POST" action="<?= BASE_URL ?>/coupons/add" id="couponForm">
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
                        <input type="text" name="title" class="form-control" placeholder="e.g. Summer Sale 20% Off" required maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Brief description visible to customers…"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Coupon Code <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" name="coupon_code" id="coupon_code" class="form-control text-uppercase"
                                   placeholder="e.g. SAVE20" maxlength="50" pattern="[A-Za-z0-9_\-]+" required
                                   title="Letters, numbers, hyphen and underscore only">
                            <button type="button" class="btn btn-outline-secondary" onclick="generateCode()">
                                <i class="fas fa-random me-1"></i> Generate
                            </button>
                        </div>
                        <div class="form-text">Uppercase letters, numbers, hyphens and underscores only.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Terms & Conditions</label>
                        <textarea name="terms_conditions" class="form-control" rows="3" placeholder="Terms, restrictions…"></textarea>
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
                                <option value="percentage">Percentage (%)</option>
                                <option value="fixed">Fixed Amount (₹)</option>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" id="discountValueLabel">Discount Value (%) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="discount_value" class="form-control" min="0.01" step="0.01" required placeholder="0">
                                <span class="input-group-text" id="discountSymbol">%</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Min Purchase Amount (₹)</label>
                            <input type="number" name="min_purchase_amount" class="form-control" min="0" step="0.01" placeholder="Leave blank for none">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Max Discount Amount (₹)</label>
                            <input type="number" name="max_discount_amount" class="form-control" min="0" step="0.01" placeholder="Leave blank for none" id="maxDiscountRow">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Validity -->
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold"><i class="fas fa-calendar-alt me-2 text-info"></i>Validity & Usage</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label">Valid From</label>
                            <input type="datetime-local" name="valid_from" class="form-control">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Valid Until</label>
                            <input type="datetime-local" name="valid_until" class="form-control">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Usage Limit</label>
                            <input type="number" name="usage_limit" class="form-control" min="1" placeholder="Leave blank for unlimited">
                            <div class="form-text">Total times this coupon can be used.</div>
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
                            <option value="<?= $tag['id'] ?>"><?= escape($tag['name']) ?></option>
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
                                <option value="<?= $m['id'] ?>"><?= escape($m['business_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Store</label>
                        <select name="store_id" id="store_id" class="form-select select2-single" data-placeholder="All Stores">
                            <option value="">All Stores</option>
                        </select>
                        <div class="form-text">Leave blank to apply to all merchant stores.</div>
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
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Approval Status</label>
                        <select name="approval_status" class="form-select">
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    <div class="form-check form-switch mt-3">
                        <input type="checkbox" name="is_admin_coupon" class="form-check-input" id="is_admin_coupon" role="switch">
                        <label class="form-check-label fw-semibold" for="is_admin_coupon">Admin Coupon</label>
                        <div class="form-text">Admin-issued coupons have higher priority.</div>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Create Coupon</button>
                <a href="<?= BASE_URL ?>/coupons" class="btn btn-outline-secondary">Cancel</a>
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

function loadStores(merchantId) {
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

    // Auto-uppercase coupon code
    document.getElementById('coupon_code').addEventListener('input', function () {
        const pos = this.selectionStart;
        this.value = this.value.toUpperCase();
        this.setSelectionRange(pos, pos);
    });
});
</script>
