<?php /* views/flash-discounts/edit.php */
$fd     = $flashDiscount;
$fdForm = $_SESSION['flash_discount_form'] ?? [];
unset($_SESSION['flash_discount_form']);

// Format datetime values for datetime-local input (MySQL → HTML)
function fmtDt($val) {
    if (!$val) return '';
    return date('Y-m-d\TH:i', strtotime($val));
}
?>

<!-- Page header -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-0">Edit Flash Discount</h4>
        <small class="text-muted">
            <a href="<?= BASE_URL ?>flash-discounts">Flash Discounts</a> /
            <a href="<?= BASE_URL ?>flash-discounts/detail?id=<?= $fd['id'] ?>"><?= escape($fd['title']) ?></a> /
            Edit
        </small>
    </div>
    <a href="<?= BASE_URL ?>flash-discounts/detail?id=<?= $fd['id'] ?>" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i> Back
    </a>
</div>

<?php if (!empty($_SESSION['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3">
    <i class="fas fa-exclamation-circle me-2"></i><?= escape($_SESSION['error']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['error']); endif; ?>

<form method="POST" action="<?= BASE_URL ?>flash-discounts/edit?id=<?= $fd['id'] ?>" enctype="multipart/form-data" id="fdForm">
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
                               value="<?= escape($fdForm['title'] ?? $fd['title']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"><?= escape($fdForm['description'] ?? $fd['description']) ?></textarea>
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
                                       min="1" max="100" step="0.01" required
                                       value="<?= escape($fdForm['discount_percentage'] ?? $fd['discount_percentage']) ?>">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Max Redemptions</label>
                            <input type="number" name="max_redemptions" class="form-control"
                                   min="1" placeholder="Leave blank for unlimited"
                                   value="<?= escape($fdForm['max_redemptions'] ?? $fd['max_redemptions']) ?>">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Valid From</label>
                            <input type="datetime-local" name="valid_from" class="form-control"
                                   value="<?= escape($fdForm['valid_from'] ?? fmtDt($fd['valid_from'])) ?>">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Valid Until</label>
                            <input type="datetime-local" name="valid_until" class="form-control"
                                   value="<?= escape($fdForm['valid_until'] ?? fmtDt($fd['valid_until'])) ?>">
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
                    <?php if (!empty($fd['banner_image'])): ?>
                    <div class="mb-2">
                        <p class="form-text mb-1">Current banner:</p>
                        <img src="<?= BASE_URL ?>public/<?= escape($fd['banner_image']) ?>"
                             alt="Current banner" class="img-thumbnail" style="max-height:120px;">
                    </div>
                    <?php endif; ?>
                    <input type="file" name="banner_image" class="form-control"
                           accept="image/jpeg,image/png,image/gif,image/webp"
                           onchange="previewBanner(this)">
                    <div class="form-text">Upload to replace the current banner. JPG, PNG, GIF, or WebP. Max 2 MB.</div>
                    <div id="bannerPreview" class="mt-2 d-none">
                        <img id="bannerImg" src="" alt="Preview" class="img-thumbnail" style="max-height:160px;">
                    </div>
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
                                data-placeholder="Select merchant…" required
                                onchange="loadStores(this.value, null)">
                            <option value="">— Select Merchant —</option>
                            <?php foreach ($merchants as $m): ?>
                                <option value="<?= $m['id'] ?>" <?= $fd['merchant_id'] == $m['id'] ? 'selected' : '' ?>>
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
                                <option value="<?= $s['id'] ?>" <?= $fd['store_id'] == $s['id'] ? 'selected' : '' ?>>
                                    <?= escape($s['store_name']) ?>
                                </option>
                            <?php endforeach; ?>
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
                        <?php foreach (['active', 'inactive', 'expired'] as $s): ?>
                            <option value="<?= $s ?>" <?= $fd['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Redemption info (read-only) -->
            <?php if (($fd['current_redemptions'] ?? 0) > 0): ?>
            <div class="alert alert-info border-0 shadow-sm">
                <i class="fas fa-info-circle me-1"></i>
                <strong><?= number_format($fd['current_redemptions']) ?></strong> redemptions recorded.
                This field is read-only.
            </div>
            <?php endif; ?>

            <!-- Submit -->
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Save Changes
                </button>
                <a href="<?= BASE_URL ?>flash-discounts/detail?id=<?= $fd['id'] ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>

        </div>
    </div>
</form>

<script>
var CURRENT_STORE_ID = <?= json_encode($fd['store_id']) ?>;

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

function loadStores(merchantId, preselectStoreId) {
    const sel = document.getElementById('store_id');
    sel.innerHTML = '<option value="">All Stores</option>';
    if (!merchantId) return;
    fetch(`<?= BASE_URL ?>coupons/stores-json?merchant_id=${merchantId}`)
        .then(r => r.json())
        .then(stores => {
            stores.forEach(s => {
                const opt = document.createElement('option');
                opt.value = s.id;
                opt.textContent = s.name;
                if (preselectStoreId && s.id == preselectStoreId) opt.selected = true;
                sel.appendChild(opt);
            });
            if (window.$) $(sel).trigger('change.select2');
        })
        .catch(() => {});
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.select2-single').forEach(el => {
        if (window.$) {
            $(el).select2({ theme: 'bootstrap-5', placeholder: el.dataset.placeholder, allowClear: true });
        }
    });

    // Pre-load stores for the existing merchant, then select the current store
    var merchantSel = document.getElementById('merchant_id');
    if (merchantSel && merchantSel.value) {
        loadStores(merchantSel.value, CURRENT_STORE_ID);
    }
});
</script>
