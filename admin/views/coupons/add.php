<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-0">Create Coupon</h4>
        <small class="text-muted"><a href="<?= BASE_URL ?>/coupons">Coupons</a> / New</small>
    </div>
    <a href="<?= BASE_URL ?>/coupons" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
</div>

<form method="POST" action="<?= BASE_URL ?>/coupons/add" id="couponForm" enctype="multipart/form-data">
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
                            <select name="discount_type" class="form-select" id="discountType" required onchange="updateDiscountFields()">
                                <option value="percentage">Percentage (%)</option>
                                <option value="fixed">Fixed Amount (₹)</option>
                                <option value="bogo">Buy X Get Y (BOGO)</option>
                                <option value="addon">Free Add-on / Freebie</option>
                            </select>
                        </div>
                        <div class="col-sm-6" id="discountValueCol">
                            <label class="form-label" id="discountValueLabel">Discount Value (%) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="discount_value" id="discount_value" class="form-control" min="0.01" step="0.01" placeholder="0">
                                <span class="input-group-text" id="discountSymbol">%</span>
                            </div>
                        </div>
                        <!-- BOGO fields -->
                        <div class="col-sm-6 d-none" id="bogoRow">
                            <label class="form-label">Buy Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="bogo_buy_quantity" class="form-control" min="1" placeholder="e.g. 1">
                        </div>
                        <div class="col-sm-6 d-none" id="bogoGetRow">
                            <label class="form-label">Get Quantity (Free) <span class="text-danger">*</span></label>
                            <input type="number" name="bogo_get_quantity" class="form-control" min="1" placeholder="e.g. 1">
                        </div>
                        <!-- Addon field -->
                        <div class="col-12 d-none" id="addonRow">
                            <label class="form-label">Free Item Description <span class="text-danger">*</span></label>
                            <input type="text" name="addon_item_description" class="form-control" maxlength="255" placeholder="e.g. Free garlic bread with any pizza">
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
                                    <option value="<?= $tag['id'] ?>"><?= escape($tag['tag_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Categories & Sub-categories (Issue 9a) -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header fw-semibold"><i class="fas fa-layer-group me-2 text-primary"></i>Category Targeting</div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Categories</label>
                                <select name="category_ids[]" id="couponCategories" class="form-select select2-multiple" multiple data-placeholder="All categories…">
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"><?= escape($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Leave blank to apply to all categories.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Sub-categories</label>
                                <select name="sub_category_ids[]" id="couponSubCategories" class="form-select select2-multiple" multiple data-placeholder="Select categories first…">
                                    <option value="">Select categories first…</option>
                                </select>
                                <div class="form-text">Optional. Automatically loaded when categories are selected.</div>
                            </div>
                        </div>
                    </div>

                    <!-- Location Targeting (area/location level) -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header fw-semibold"><i class="fas fa-map-pin me-2 text-success"></i>Location Targeting <span class="badge bg-info text-dark ms-1">Optional</span></div>
                        <div class="card-body">
                            <div class="form-text mb-2">Restrict to specific areas/locations. Leave empty to use the store's city automatically.</div>
                            <div id="locationRows"></div>
                            <button type="button" class="btn btn-outline-secondary btn-sm mt-2" onclick="addLocationRow()">
                                <i class="fas fa-plus me-1"></i> Add Location Row
                            </button>
                        </div>
                    </div>

                    <!-- City Targeting (Admin only, Issue 9b) -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header fw-semibold"><i class="fas fa-map-marker-alt me-2 text-danger"></i>City Targeting <span class="badge bg-warning text-dark ms-1">Admin Only</span></div>
                        <div class="card-body">
                            <label class="form-label">Additional Cities</label>
                            <select name="city_ids[]" class="form-select select2-multiple" multiple data-placeholder="Store's city only (default)…">
                                <?php foreach ($cities as $city): ?>
                                    <option value="<?= $city['id'] ?>"><?= escape($city['city_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Select extra cities where this coupon is visible. Leave blank to restrict to the store's registered city.</div>
                        </div>
                    </div>

                </div><!-- /col-lg-8 -->

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
                            <option value="">&mdash; Select Merchant &mdash;</option>
                            <?php foreach ($merchants as $m): ?>
                                <option value="<?= $m['id'] ?>"><?= escape($m['business_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Store</label>
                        <select name="store_ids[]" id="store_id" class="form-select select2-multiple" multiple
                                data-placeholder="All Stores (leave blank)">
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

            <!-- Banner Image -->
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold"><i class="fas fa-image me-2 text-info"></i>Banner Image</div>
                <div class="card-body">
                    <input type="hidden" name="banner_image_path" id="banner_image_path" value="">
                    <input type="file" name="banner_image" id="banner_image" class="form-control"
                           accept="image/jpeg,image/png,image/gif,image/webp">
                    <div class="form-text">Optional. JPG, PNG, GIF or WebP, max 2 MB. Image uploads immediately for preview.</div>
                    <div id="bannerUploadStatus" class="mt-2 d-none">
                        <div id="bannerUploadSpinner" class="text-muted small d-none"><i class="fas fa-spinner fa-spin me-1"></i>Uploading…</div>
                        <div id="bannerUploadError" class="alert alert-danger py-1 px-2 small d-none"></div>
                    </div>
                    <div id="bannerPreview" class="mt-2 d-none">
                        <img id="bannerPreviewImg" src="" alt="Preview" class="img-fluid rounded" style="max-height:160px">
                        <div id="bannerReadyMsg" class="small text-muted mt-1 d-none"><i class="fas fa-check-circle text-success me-1"></i>Ready to save</div>
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

function updateDiscountFields() {
    const type     = document.getElementById('discountType').value;
    const valCol   = document.getElementById('discountValueCol');
    const valInput = document.getElementById('discount_value');
    const label    = document.getElementById('discountValueLabel');
    const symbol   = document.getElementById('discountSymbol');
    const bogoRow  = document.getElementById('bogoRow');
    const bogoGet  = document.getElementById('bogoGetRow');
    const addonRow = document.getElementById('addonRow');

    valCol.classList.toggle('d-none',  type === 'bogo' || type === 'addon');
    bogoRow.classList.toggle('d-none', type !== 'bogo');
    bogoGet.classList.toggle('d-none', type !== 'bogo');
    addonRow.classList.toggle('d-none', type !== 'addon');

    valInput.required = (type === 'percentage' || type === 'fixed');
    if (type === 'percentage') {
        label.innerHTML  = 'Discount Value (%) <span class="text-danger">*</span>';
        symbol.textContent = '%';
    } else if (type === 'fixed') {
        label.innerHTML  = 'Discount Value (₹) <span class="text-danger">*</span>';
        symbol.textContent = '₹';
    }
}

function loadCouponSubCategories() {
    const catSel = document.getElementById('couponCategories');
    const subSel = document.getElementById('couponSubCategories');
    const catIds = Array.from(catSel.selectedOptions).map(o => o.value).filter(Boolean);
    subSel.innerHTML = '<option value="">Loading…</option>';
    if (!catIds.length) { subSel.innerHTML = '<option value="">Select categories first…</option>'; if (window.$) $(subSel).trigger('change'); return; }
    const qs = catIds.map(id => `category_id[]=${encodeURIComponent(id)}`).join('&');
    fetch(`<?= BASE_URL ?>master-data/sub-categories-json?${qs}`)
        .then(r => r.json())
        .then(data => {
            subSel.innerHTML = '';
            const seen = new Set();
            data.forEach(s => {
                if (seen.has(s.name)) return;
                seen.add(s.name);
                subSel.insertAdjacentHTML('beforeend', `<option value="${s.id}">${s.name}</option>`);
            });
            if (window.$) $(subSel).trigger('change');
        })
        .catch(() => { subSel.innerHTML = '<option value="">Error loading sub-categories</option>'; });
}

function loadStores(merchantId) {
    const sel = document.getElementById('store_id');
    sel.innerHTML = '';
    if (!merchantId) { if (window.$) $(sel).trigger('change'); return; }
    fetch(`<?= BASE_URL ?>coupons/stores-json?merchant_id=${merchantId}`)
        .then(r => r.json())
        .then(stores => {
            stores.forEach(s => {
                const opt = document.createElement('option');
                opt.value = s.id;
                opt.textContent = s.store_name;
                sel.appendChild(opt);
            });
            if (window.$) $(sel).trigger('change');
        });
}

function addLocationRow() {
    const container = document.getElementById('locationRows');
    const row = document.createElement('div');
    row.className = 'row g-2 align-items-center mb-2 location-row';
    row.innerHTML = `
        <div class="col-4"><select name="location_city_ids[]" class="form-select form-select-sm loc-city" onchange="rowLoadAreas(this)">
            <option value="">City…</option>
            <?php foreach ($cities as $city): ?><option value="<?= $city['id'] ?>"><?= escape($city['city_name']) ?></option><?php endforeach; ?>
        </select></div>
        <div class="col-3"><select name="location_area_ids[]" class="form-select form-select-sm loc-area" onchange="rowLoadLocations(this)">
            <option value="">Any area</option>
        </select></div>
        <div class="col-3"><select name="location_location_ids[]" class="form-select form-select-sm loc-location">
            <option value="">Any loc</option>
        </select></div>
        <div class="col-2"><button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="this.closest('.location-row').remove()"><i class="fas fa-times"></i></button></div>`;
    container.appendChild(row);
}
function rowLoadAreas(citySelect) {
    const row = citySelect.closest('.location-row');
    const areaSel = row.querySelector('.loc-area');
    const locSel  = row.querySelector('.loc-location');
    areaSel.innerHTML = '<option value="">Any area</option>';
    locSel.innerHTML  = '<option value="">Any loc</option>';
    if (!citySelect.value) return;
    fetch(`<?= BASE_URL ?>master-data/areas-json?city_id=${citySelect.value}`)
        .then(r => r.json())
        .then(data => data.forEach(a => areaSel.insertAdjacentHTML('beforeend', `<option value="${a.id}">${a.area_name}</option>`)));
}
function rowLoadLocations(areaSelect) {
    const row = areaSelect.closest('.location-row');
    const locSel = row.querySelector('.loc-location');
    locSel.innerHTML = '<option value="">Any loc</option>';
    if (!areaSelect.value) return;
    fetch(`<?= BASE_URL ?>master-data/locations-json?area_id=${areaSelect.value}`)
        .then(r => r.json())
        .then(data => data.forEach(l => locSel.insertAdjacentHTML('beforeend', `<option value="${l.id}">${l.location_name}</option>`)));
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.select2-multiple').forEach(el => {
        $(el).select2({ theme: 'bootstrap-5', placeholder: el.dataset.placeholder });
    });
    document.querySelectorAll('.select2-single').forEach(el => {
        $(el).select2({ theme: 'bootstrap-5', placeholder: el.dataset.placeholder, allowClear: true });
    });

    $('#couponCategories').on('change', function () { loadCouponSubCategories([]); });

    // Auto-uppercase coupon code
    document.getElementById('coupon_code').addEventListener('input', function () {
        const pos = this.selectionStart;
        this.value = this.value.toUpperCase();
        this.setSelectionRange(pos, pos);
    });

    // Banner image: show blob preview immediately, upload in background to get saved path
    document.getElementById('banner_image').addEventListener('change', function () {
        const file       = this.files && this.files[0];
        const preview    = document.getElementById('bannerPreview');
        const previewImg = document.getElementById('bannerPreviewImg');
        const pathInput  = document.getElementById('banner_image_path');
        const spinner    = document.getElementById('bannerUploadSpinner');
        const errDiv     = document.getElementById('bannerUploadError');
        const statusWrap = document.getElementById('bannerUploadStatus');
        const readyMsg   = document.getElementById('bannerReadyMsg');

        // Reset state
        preview.classList.add('d-none');
        previewImg.src = '';
        pathInput.value = '';
        spinner.classList.add('d-none');
        errDiv.classList.add('d-none');
        statusWrap.classList.add('d-none');
        readyMsg.classList.add('d-none');

        if (!file) return;

        // Upload in background to store on the API server
        const csrf = document.querySelector('input[name="csrf_token"]').value;
        const fd   = new FormData();
        fd.append('banner_image', file);
        fd.append('csrf_token', csrf);

        statusWrap.classList.remove('d-none');
        spinner.classList.remove('d-none');
        fetch(`<?= BASE_URL ?>coupons/upload-banner`, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                spinner.classList.add('d-none');
                statusWrap.classList.add('d-none');
                if (data.success) {
                    pathInput.value = data.path;
                    previewImg.src  = data.url;
                    preview.classList.remove('d-none');
                    readyMsg.classList.remove('d-none');
                } else {
                    errDiv.textContent = data.error || 'Upload failed.';
                    errDiv.classList.remove('d-none');
                    statusWrap.classList.remove('d-none');
                }
            })
            .catch(() => {
                spinner.classList.add('d-none');
                errDiv.textContent = 'Upload failed. Please try again.';
                errDiv.classList.remove('d-none');
                statusWrap.classList.remove('d-none');
            });
    });
});
</script>

