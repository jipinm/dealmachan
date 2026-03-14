<?php /* views/flash-discounts/add.php */
$fd = $_SESSION['flash_discount_form'] ?? [];
unset($_SESSION['flash_discount_form']);
?>

<!-- Page header -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-0">Create Flash Discount</h4>
        <small class="text-muted"><a href="<?= BASE_URL ?>flash-discounts">Flash Discounts</a> / New</small>
    </div>
    <a href="<?= BASE_URL ?>flash-discounts" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i> Back
    </a>
</div>

<?php if (!empty($_SESSION['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3">
    <i class="fas fa-exclamation-circle me-2"></i><?= escape($_SESSION['error']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['error']); endif; ?>

<form method="POST" action="<?= BASE_URL ?>flash-discounts/add" enctype="multipart/form-data" id="fdForm">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

    <div class="row g-4">

        <!-- LEFT: Main details -->
        <div class="col-lg-8">

            <!-- Basic Info -->
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">
                    <i class="fas fa-bolt me-2 text-warning"></i> Flash Discount Details
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" required maxlength="255"
                               placeholder="e.g. Midnight Hunger Special &mdash; 50% Off"
                               value="<?= escape($fd['title'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"
                                  placeholder="Brief description visible to customers…"><?= escape($fd['description'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Discount & Validity -->
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">
                    <i class="fas fa-percent me-2 text-success"></i> Discount & Validity
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label">Discount Percentage <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="discount_percentage" class="form-control"
                                       min="1" max="100" step="0.01" required placeholder="e.g. 25"
                                       value="<?= escape($fd['discount_percentage'] ?? '') ?>">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Max Redemptions</label>
                            <input type="number" name="max_redemptions" class="form-control"
                                   min="1" placeholder="Leave blank for unlimited"
                                   value="<?= escape($fd['max_redemptions'] ?? '') ?>">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Valid From</label>
                            <input type="datetime-local" name="valid_from" class="form-control"
                                   value="<?= escape($fd['valid_from'] ?? '') ?>">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Valid Until</label>
                            <input type="datetime-local" name="valid_until" class="form-control"
                                   value="<?= escape($fd['valid_until'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Banner Image -->
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">
                    <i class="fas fa-image me-2 text-info"></i> Banner Image
                </div>
                <div class="card-body">
                    <input type="file" name="banner_image" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp"
                           onchange="previewBanner(this)">
                    <div class="form-text">Optional. JPG, PNG, GIF, or WebP. Max 2 MB.</div>
                    <div id="bannerPreview" class="mt-2 d-none">
                        <img id="bannerImg" src="" alt="Preview" class="img-thumbnail" style="max-height:160px;">
                    </div>
                </div>
            </div>

            <!-- Location Targeting -->
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">
                    <i class="fas fa-map-marker-alt me-2 text-danger"></i> Location Targeting
                </div>
                <div class="card-body">
                    <p class="form-text mb-2">Leave empty to auto-populate from the selected store&rsquo;s location.</p>
                    <div id="locationRows"></div>
                    <button type="button" class="btn btn-outline-secondary btn-sm mt-2" onclick="addLocationRow()">
                        <i class="fas fa-plus me-1"></i> Add Location Row
                    </button>
                </div>
            </div>

        </div>

        <!-- RIGHT: Merchant, Store, Status -->
        <div class="col-lg-4">

            <!-- Merchant & Store -->
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">
                    <i class="fas fa-store me-2 text-warning"></i> Merchant & Store
                </div>
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

            <!-- Status -->
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">
                    <i class="fas fa-sliders-h me-2 text-dark"></i> Settings
                </div>
                <div class="card-body">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <!-- Business Categories -->
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">
                    <i class="fas fa-tags me-2 text-primary"></i> Categories
                </div>
                <div class="card-body">
                    <p class="form-text mb-2">Leave empty to auto-populate from the selected store&rsquo;s categories.</p>
                    <select name="category_ids[]" id="fdCategories" class="form-select select2" multiple
                            data-placeholder="Select categories…">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= escape($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="mt-3">
                        <label class="form-label">Sub-categories</label>
                        <select name="sub_category_ids[]" id="fdSubCategories" class="form-select select2" multiple
                                data-placeholder="Select categories first…">
                            <option value="">Select categories first…</option>
                        </select>
                        <p class="form-text">Optional. Automatically loaded when categories are selected.</p>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-bolt me-1"></i> Create Flash Discount
                </button>
                <a href="<?= BASE_URL ?>flash-discounts" class="btn btn-outline-secondary">Cancel</a>
            </div>

        </div>
    </div>
</form>

<script>
function previewBanner(input) {
    const preview = document.getElementById('bannerPreview');
    const img     = document.getElementById('bannerImg');
    if (input.files && input.files[0]) {
        img.src = URL.createObjectURL(input.files[0]);
        preview.classList.remove('d-none');
    } else {
        preview.classList.add('d-none');
    }
}

function loadFDSubCategories(preselectIds) {
    const catSel = document.getElementById('fdCategories');
    const subSel = document.getElementById('fdSubCategories');
    const catIds = Array.from(catSel.selectedOptions).map(o => o.value).filter(Boolean);
    subSel.innerHTML = '<option value="">Loading…</option>';
    if (!catIds.length) { subSel.innerHTML = '<option value="">Select categories first…</option>'; if (window.$) $(subSel).trigger('change'); return; }
    const qs = catIds.map(id => `category_id[]=${encodeURIComponent(id)}`).join('&');
    fetch(`<?= BASE_URL ?>master-data/sub-categories-json?${qs}`)
        .then(r => r.json())
        .then(data => {
            subSel.innerHTML = '';
            const pre = Array.isArray(preselectIds) ? preselectIds.map(Number) : [];
            const seen = new Set();
            data.forEach(s => {
                if (seen.has(s.name)) return;
                seen.add(s.name);
                const opt = document.createElement('option');
                opt.value = s.id;
                opt.textContent = s.name;
                if (pre.includes(parseInt(s.id))) opt.selected = true;
                subSel.appendChild(opt);
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
        })
        .catch(() => {});
}

function addLocationRow() {
    const container = document.getElementById('locationRows');
    const idx = container.querySelectorAll('.location-row').length;
    const row = document.createElement('div');
    row.className = 'location-row row g-2 mb-2 align-items-end';
    row.innerHTML = `
        <div class="col-sm-4">
            <label class="form-label form-label-sm">City</label>
            <select name="location_city_ids[]" class="form-select form-select-sm row-city"
                    onchange="rowLoadAreas(this, ${idx})">
                <option value="">&mdash; City &mdash;</option>
                <?php foreach ($cities as $c): ?>
                <option value="<?= $c['id'] ?>"><?= escape($c['city_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-4">
            <label class="form-label form-label-sm">Area</label>
            <select name="location_area_ids[]" class="form-select form-select-sm row-area"
                    onchange="rowLoadLocations(this, ${idx})" disabled>
                <option value="">&mdash; Area &mdash;</option>
            </select>
        </div>
        <div class="col-sm-3">
            <label class="form-label form-label-sm">Location</label>
            <select name="location_location_ids[]" class="form-select form-select-sm row-location" disabled>
                <option value="">&mdash; Location &mdash;</option>
            </select>
        </div>
        <div class="col-sm-1">
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('.location-row').remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>`;
    container.appendChild(row);
}

function rowLoadAreas(citySelect, idx) {
    const row       = citySelect.closest('.location-row');
    const areaSel   = row.querySelector('.row-area');
    const locSel    = row.querySelector('.row-location');
    areaSel.innerHTML = '<option value="">&mdash; Area &mdash;</option>';
    locSel.innerHTML  = '<option value="">&mdash; Location &mdash;</option>';
    areaSel.disabled  = true;
    locSel.disabled   = true;
    if (!citySelect.value) return;
    fetch(`<?= BASE_URL ?>master-data/areas-json?city_id=${citySelect.value}`)
        .then(r => r.json())
        .then(areas => {
            areas.forEach(a => {
                const o = document.createElement('option');
                o.value = a.id; o.textContent = a.area_name;
                areaSel.appendChild(o);
            });
            areaSel.disabled = false;
        }).catch(() => {});
}

function rowLoadLocations(areaSelect, idx) {
    const row    = areaSelect.closest('.location-row');
    const locSel = row.querySelector('.row-location');
    locSel.innerHTML = '<option value="">&mdash; Location &mdash;</option>';
    locSel.disabled  = true;
    if (!areaSelect.value) return;
    fetch(`<?= BASE_URL ?>master-data/locations-json?area_id=${areaSelect.value}`)
        .then(r => r.json())
        .then(locs => {
            locs.forEach(l => {
                const o = document.createElement('option');
                o.value = l.id; o.textContent = l.location_name;
                locSel.appendChild(o);
            });
            locSel.disabled = false;
        }).catch(() => {});
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.select2-single').forEach(el => {
        if (window.$) {
            $(el).select2({ theme: 'bootstrap-5', placeholder: el.dataset.placeholder, allowClear: true });
        }
    });
    if (window.$) {
        $('#fdCategories').select2({ theme: 'bootstrap-5', placeholder: 'Select categories…' });
        $('#fdSubCategories').select2({ theme: 'bootstrap-5', placeholder: 'Select categories first…' });
        $('#store_id').select2({ theme: 'bootstrap-5', placeholder: 'All Stores (leave blank)', allowClear: true });
        $('#fdCategories').on('change', function() { loadFDSubCategories([]); });
    }
});
</script>
