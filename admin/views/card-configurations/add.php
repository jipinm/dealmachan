<?php /* views/card-configurations/add.php */ ?>

<!-- Page header -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-0">Create Card Configuration</h4>
        <small class="text-muted">
            <a href="<?= BASE_URL ?>card-configurations">Card Configurations</a> / New
        </small>
    </div>
    <a href="<?= BASE_URL ?>card-configurations" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i> Back
    </a>
</div>

<?php if (!empty($_SESSION['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3">
    <i class="fas fa-exclamation-circle me-2"></i><?= escape($_SESSION['error']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['error']); endif; ?>

<form method="POST" action="<?= BASE_URL ?>card-configurations/add"
      enctype="multipart/form-data" id="ccForm">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

    <div class="row g-4">

        <!-- LEFT -->
        <div class="col-lg-8">

            <!-- Basic Info -->
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">
                    <i class="fas fa-id-card me-2 text-primary"></i> Basic Information
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-7">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required maxlength="100"
                                   placeholder="e.g. Gold Membership Card">
                        </div>
                        <div class="col-sm-5">
                            <label class="form-label">Classification <span class="text-danger">*</span></label>
                            <select name="classification" class="form-select" required>
                                <?php foreach (['silver', 'gold', 'platinum', 'diamond'] as $c): ?>
                                    <option value="<?= $c ?>"><?= ucfirst($c) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Features / Benefits</label>
                            <textarea name="features_html" class="form-control" rows="5"
                                      placeholder="HTML allowed &mdash; describe the card benefits visible to customers…"></textarea>
                            <div class="form-text">HTML tags allowed (ul, li, b, etc.).</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pricing & Limits -->
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">
                    <i class="fas fa-sliders-h me-2 text-success"></i> Pricing &amp; Limits
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-4">
                            <label class="form-label">Price (₹)</label>
                            <input type="number" name="price" class="form-control" min="0" step="0.01" value="0"
                                   placeholder="0 = Free">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label">Validity (days) <span class="text-danger">*</span></label>
                            <input type="number" name="validity_days" class="form-control" required min="1" value="360">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label">Monthly Maximum</label>
                            <input type="number" name="monthly_maximum" class="form-control" min="1"
                                   placeholder="Unlimited">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label">Max Live Coupons</label>
                            <input type="number" name="max_live_coupons" class="form-control" min="1"
                                   placeholder="Unlimited">
                        </div>
                        <div class="col-sm-4 d-flex align-items-end">
                            <div class="form-check form-switch mb-0">
                                <input type="checkbox" name="coupon_authorization" class="form-check-input"
                                       id="couponAuth" role="switch" checked>
                                <label class="form-check-label" for="couponAuth">Coupon Authorization</label>
                            </div>
                        </div>
                        <div class="col-sm-4 d-flex align-items-end">
                            <div class="form-check form-switch mb-0">
                                <input type="checkbox" name="is_publicly_selectable" class="form-check-input"
                                       id="publicSel" role="switch">
                                <label class="form-check-label" for="publicSel">Publicly Selectable</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card Images -->
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">
                    <i class="fas fa-images me-2 text-info"></i> Card Images
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label">Front Image</label>
                            <input type="file" name="card_image_front" class="form-control"
                                   accept="image/jpeg,image/png,image/webp"
                                   onchange="previewImg(this,'prevFront')">
                            <div class="mt-2" id="prevFront"></div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Back Image</label>
                            <input type="file" name="card_image_back" class="form-control"
                                   accept="image/jpeg,image/png,image/webp"
                                   onchange="previewImg(this,'prevBack')">
                            <div class="mt-2" id="prevBack"></div>
                        </div>
                    </div>
                    <div class="form-text mt-1">JPG, PNG, or WebP. Max 2 MB each.</div>
                </div>
            </div>

            <!-- Premium Partners (max 4) -->
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">
                    <i class="fas fa-star me-2 text-warning"></i> Premium Partners
                    <small class="text-muted fw-normal">(max 4)</small>
                </div>
                <div class="card-body">
                    <div id="premiumRows"></div>
                    <button type="button" class="btn btn-outline-warning btn-sm"
                            onclick="addPartnerRow('premium')">
                        <i class="fas fa-plus me-1"></i> Add Premium Partner
                    </button>
                </div>
            </div>

            <!-- Normal Partners (max 10) -->
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">
                    <i class="fas fa-handshake me-2 text-secondary"></i> Normal Partners
                    <small class="text-muted fw-normal">(max 10)</small>
                </div>
                <div class="card-body">
                    <div id="normalRows"></div>
                    <button type="button" class="btn btn-outline-secondary btn-sm"
                            onclick="addPartnerRow('normal')">
                        <i class="fas fa-plus me-1"></i> Add Normal Partner
                    </button>
                </div>
            </div>

        </div>

        <!-- RIGHT -->
        <div class="col-lg-4">

            <!-- Status -->
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">
                    <i class="fas fa-toggle-on me-2 text-dark"></i> Settings
                </div>
                <div class="card-body">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <!-- Sub-classifications -->
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">
                    <i class="fas fa-tags me-2 text-primary"></i> Sub-Classifications
                </div>
                <div class="card-body">
                    <?php foreach ($subClassifications as $sc): ?>
                    <div class="form-check">
                        <input type="checkbox" name="sub_class_ids[]" value="<?= $sc['id'] ?>"
                               class="form-check-input" id="sc<?= $sc['id'] ?>">
                        <label class="form-check-label" for="sc<?= $sc['id'] ?>"><?= escape($sc['name']) ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- City Availability -->
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">
                    <i class="fas fa-city me-2 text-info"></i> City Availability
                </div>
                <div class="card-body">
                    <p class="form-text mb-2">Leave empty to make available in all cities.</p>
                    <select name="city_ids[]" id="citySelect" class="form-select select2" multiple
                            data-placeholder="All Cities">
                        <?php foreach ($cities as $city): ?>
                            <option value="<?= $city['id'] ?>"><?= escape($city['city_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Submit -->
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Create Configuration
                </button>
                <a href="<?= BASE_URL ?>card-configurations" class="btn btn-outline-secondary">Cancel</a>
            </div>

        </div>
    </div>
</form>

<script>
function previewImg(input, targetId) {
    const el = document.getElementById(targetId);
    if (input.files && input.files[0]) {
        el.innerHTML = '<img src="' + URL.createObjectURL(input.files[0]) +
                       '" class="img-thumbnail" style="max-height:120px;">';
    } else {
        el.innerHTML = '';
    }
}

var partnerFileIdx = 0;

function addPartnerRow(type) {
    const containerId = type === 'premium' ? 'premiumRows' : 'normalRows';
    const container   = document.getElementById(containerId);
    const max         = type === 'premium' ? 4 : 10;
    if (container.querySelectorAll('.partner-row').length >= max) {
        alert('Maximum ' + max + ' ' + type + ' partners allowed.');
        return;
    }
    const idx = partnerFileIdx++;
    const row = document.createElement('div');
    row.className = 'partner-row border rounded p-2 mb-2';
    row.innerHTML = `
        <input type="hidden" name="partner_type[]" value="${type}">
        <input type="hidden" name="partner_img[]"  value="">
        <div class="row g-2 align-items-end">
            <div class="col-sm-5">
                <label class="form-label form-label-sm">Image</label>
                <input type="file" name="partner_img_files[${idx}]" class="form-control form-control-sm"
                       accept="image/jpeg,image/png,image/webp"
                       onchange="previewPartnerImg(this, 'pprev${idx}')">
                <div id="pprev${idx}" class="mt-1"></div>
            </div>
            <div class="col-sm-6">
                <label class="form-label form-label-sm">URL</label>
                <input type="url" name="partner_url[]" class="form-control form-control-sm"
                       placeholder="https://…">
            </div>
            <div class="col-sm-1">
                <button type="button" class="btn btn-outline-danger btn-sm"
                        onclick="this.closest('.partner-row').remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>`;
    container.appendChild(row);
}

function previewPartnerImg(input, targetId) {
    const el = document.getElementById(targetId);
    if (el && input.files && input.files[0]) {
        el.innerHTML = '<img src="' + URL.createObjectURL(input.files[0]) +
                       '" class="img-thumbnail" style="max-height:60px;">';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    if (window.$) {
        $('#citySelect').select2({ theme: 'bootstrap-5', placeholder: 'All Cities', allowClear: true });
    }
});
</script>
