<?php /* views/merchants/edit-store.php */ ?>

<?php if ($flash_error): ?><div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm"><i class="fas fa-exclamation-circle me-2"></i><?= escape($flash_error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>merchants">Merchants</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>merchants/profile?id=<?= $store['merchant_id'] ?>"><?= escape($store['business_name']) ?></a></li>
        <li class="breadcrumb-item active">Edit Store</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Edit Store</h1>
        <p class="text-muted mb-0 small"><?= escape($store['store_name']) ?> &mdash; <?= escape($store['business_name']) ?></p>
    </div>
    <a href="<?= BASE_URL ?>merchants/profile?id=<?= $store['merchant_id'] ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form method="POST" action="<?= BASE_URL ?>merchants/edit-store?id=<?= $store['id'] ?>" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Store Name <span class="text-danger">*</span></label>
                        <input type="text" name="store_name" class="form-control" required maxlength="255"
                               value="<?= escape($_POST['store_name'] ?? $store['store_name']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Address <span class="text-danger">*</span></label>
                        <textarea name="address" class="form-control" rows="2" required><?= escape($_POST['address'] ?? $store['address']) ?></textarea>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">City <span class="text-danger">*</span></label>
                            <select name="city_id" id="citySelect" class="form-select" required onchange="loadAreas(this.value, null)">
                                <option value="">Select city…</option>
                                <?php foreach ($cities as $city): ?>
                                <option value="<?= $city['id'] ?>" <?= ($store['city_id'] == $city['id']) ? 'selected' : '' ?>>
                                    <?= escape($city['city_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Area <span class="text-danger">*</span></label>
                            <select name="area_id" id="areaSelect" class="form-select" required>
                                <?php foreach ($areas as $area): ?>
                                <option value="<?= $area['id'] ?>" <?= ($store['area_id'] == $area['id']) ? 'selected' : '' ?>>
                                    <?= escape($area['area_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="phone" class="form-control" maxlength="20"
                                   value="<?= escape($_POST['phone'] ?? $store['phone']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" maxlength="255"
                                   value="<?= escape($_POST['email'] ?? $store['email']) ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2"><?= escape($_POST['description'] ?? $store['description']) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active"   <?= ($store['status'] === 'active')   ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= ($store['status'] === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>

                    <!-- Category / Sub-category -->
                    <?php if (!empty($noCategories)): ?>
                    <div class="alert alert-warning border-0 shadow-sm mb-3">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        <strong>No categories assigned to this merchant.</strong>
                        <a href="<?= BASE_URL ?>merchants/edit?id=<?= $store['merchant_id'] ?>" class="alert-link ms-1">Assign categories first.</a>
                    </div>
                    <?php else: ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                        <select name="category_ids[]" id="storeCategorySelect" class="form-select select2-multiple" multiple required
                                data-placeholder="Select category…" onchange="loadSubCategories()">
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= in_array($cat['id'], $storeCategories ?? []) ? 'selected' : '' ?>>
                                <?= escape($cat['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Sub-category</label>
                        <select name="sub_category_ids[]" id="storeSubCategorySelect" class="form-select select2-multiple" multiple
                                data-placeholder="Select sub-category…">
                            <?php foreach ($subCategories ?? [] as $sub): ?>
                            <option value="<?= $sub['id'] ?>" data-category-id="<?= $sub['category_id'] ?>"
                                <?= in_array($sub['id'], $storeSubCategoryIds ?? []) ? 'selected' : '' ?>>
                                <?= escape($sub['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <!-- Opening Hours -->
                    <?php
                        $openingHours = [];
                        if (!empty($store['opening_hours'])) {
                            $openingHours = is_string($store['opening_hours']) ? json_decode($store['opening_hours'], true) : $store['opening_hours'];
                        }
                        if (!is_array($openingHours)) $openingHours = [];
                        $days = ['monday'=>'Monday','tuesday'=>'Tuesday','wednesday'=>'Wednesday','thursday'=>'Thursday','friday'=>'Friday','saturday'=>'Saturday','sunday'=>'Sunday'];
                    ?>
                    <div class="mb-4">
                        <label class="form-label fw-semibold"><i class="fas fa-clock me-1 text-muted"></i> Opening Hours</label>
                        <div class="border rounded p-3">
                            <?php foreach ($days as $dayKey => $dayLabel): ?>
                            <?php
                                $dayData = $openingHours[$dayKey] ?? ['open'=>'09:00','close'=>'21:00','closed'=>false];
                                $isClosed = !empty($dayData['closed']);
                            ?>
                            <div class="row g-2 align-items-center mb-2">
                                <div class="col-3 col-md-2">
                                    <label class="form-label mb-0 small fw-semibold"><?= $dayLabel ?></label>
                                </div>
                                <div class="col-3 col-md-3">
                                    <input type="time" name="hours[<?= $dayKey ?>][open]" class="form-control form-control-sm"
                                           value="<?= escape($dayData['open'] ?? '09:00') ?>" <?= $isClosed ? 'disabled' : '' ?>>
                                </div>
                                <div class="col-3 col-md-3">
                                    <input type="time" name="hours[<?= $dayKey ?>][close]" class="form-control form-control-sm"
                                           value="<?= escape($dayData['close'] ?? '21:00') ?>" <?= $isClosed ? 'disabled' : '' ?>>
                                </div>
                                <div class="col-3 col-md-4">
                                    <div class="form-check">
                                        <input type="checkbox" name="hours[<?= $dayKey ?>][closed]" value="1"
                                               class="form-check-input" id="closed_<?= $dayKey ?>"
                                               <?= $isClosed ? 'checked' : '' ?>
                                               onchange="toggleDay('<?= $dayKey ?>', this.checked)">
                                        <label class="form-check-label small" for="closed_<?= $dayKey ?>">Closed</label>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Store Admin Credentials -->
                    <div class="mb-4">
                        <div class="d-flex align-items-center justify-content-between border rounded p-3"
                             data-bs-toggle="collapse" data-bs-target="#storeAdminSection"
                             aria-expanded="<?= $storeAdmin ? 'true' : 'false' ?>" aria-controls="storeAdminSection"
                             style="cursor:pointer; background:#f8f9fa;">
                            <span class="fw-semibold"><i class="fas fa-user-shield me-2 text-muted"></i> Store Admin Credentials</span>
                            <i class="fas fa-chevron-<?= $storeAdmin ? 'up' : 'down' ?> text-muted small" id="storeAdminChevron"></i>
                        </div>
                        <div class="collapse<?= $storeAdmin ? ' show' : '' ?>" id="storeAdminSection">
                            <div class="border border-top-0 rounded-bottom p-3">
                                <?php if ($storeAdmin): ?>
                                <div class="d-flex align-items-center mb-3 p-2 bg-light rounded">
                                    <i class="fas fa-user-circle fa-2x text-secondary me-3"></i>
                                    <div>
                                        <div class="fw-semibold"><?= escape($storeAdmin['email']) ?></div>
                                        <span class="badge bg-<?= $storeAdmin['msu_status'] === 'active' ? 'success' : 'secondary' ?> mt-1">
                                            <?= ucfirst($storeAdmin['msu_status']) ?>
                                        </span>
                                    </div>
                                <div class="ms-auto d-flex gap-2">
                                        <button type="button"
                                                class="btn btn-sm <?= $storeAdmin['msu_status'] === 'active' ? 'btn-outline-warning' : 'btn-outline-success' ?>"
                                                onclick="toggleAdminStatus('<?= $storeAdmin['msu_status'] ?>')">
                                            <i class="fas fa-<?= $storeAdmin['msu_status'] === 'active' ? 'ban' : 'check-circle' ?> me-1"></i>
                                            <?= $storeAdmin['msu_status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                                onclick="if(confirm('Remove this store admin? Their login credentials will be permanently deleted.')) { document.getElementById('removeAdminFlag').value='1'; document.querySelector('form').submit(); }">
                                            <i class="fas fa-user-minus me-1"></i> Remove
                                        </button>
                                    </div>
                                </div>
                                <p class="text-muted small mb-2">Reset password for this store admin (leave blank to keep current password):</p>
                                <?php else: ?>
                                <p class="text-muted small mb-3">No store admin assigned. Fill in details below to create one.</p>
                                <div class="mb-3">
                                    <label class="form-label">Admin Email <span class="text-danger">*</span></label>
                                    <input type="email" name="admin_email" id="adminEmail" class="form-control"
                                           maxlength="255" autocomplete="off"
                                           value="<?= escape($_POST['admin_email'] ?? '') ?>">
                                </div>
                                <?php endif; ?>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label"><?= $storeAdmin ? 'New Password' : 'Password' ?> <?= !$storeAdmin ? '<span class="text-danger">*</span>' : '' ?></label>
                                        <input type="password" name="admin_password" id="adminPassword"
                                               class="form-control" maxlength="255" autocomplete="new-password">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Confirm Password <?= !$storeAdmin ? '<span class="text-danger">*</span>' : '' ?></label>
                                        <input type="password" name="admin_password_confirm" id="adminPasswordConfirm"
                                               class="form-control" maxlength="255" autocomplete="new-password">
                                        <div class="invalid-feedback">Passwords do not match.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="remove_store_admin" id="removeAdminFlag" value="0">
                    <input type="hidden" name="toggle_store_admin" id="toggleAdminFlag" value="0">

                    <!-- Store Image Upload -->
                    <div class="card mb-3">
                        <div class="card-header fw-semibold">Store Image / Banner</div>
                        <div class="card-body">
                            <?php if (!empty($store['store_image'])): ?>
                            <div id="storeImgPreviewWrap" class="mb-2">
                                <img id="storeImgPreview" src="<?= htmlspecialchars(imageUrl($store['store_image'])) ?>" alt="Current Store Image" class="img-thumbnail w-100" style="max-height:160px;object-fit:cover;">
                            </div>
                            <div class="form-check mb-2">
                                <input type="checkbox" name="remove_store_image" value="1" class="form-check-input" id="removeStoreImg">
                                <label class="form-check-label text-danger" for="removeStoreImg">Remove current image</label>
                            </div>
                            <?php else: ?>
                            <div id="storeImgPreviewWrap" class="mb-2 d-none">
                                <img id="storeImgPreview" src="" alt="Preview" class="img-thumbnail w-100" style="max-height:160px;object-fit:cover;">
                            </div>
                            <?php endif; ?>
                            <input type="file" name="store_image" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp"
                                   onchange="previewImg(this,'storeImgPreview','storeImgPreviewWrap')">
                            <div class="form-text">JPG, PNG, GIF, WebP &mdash; max 3 MB. Upload a new image to replace the current one.</div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i> Save Changes</button>
                </form>

            </div>
        </div>
    </div>
</div>

<!-- Gallery Management Section -->
<div class="container-fluid px-4 pb-5">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span class="fw-semibold"><i class="fas fa-images me-2"></i>Store Gallery</span>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addGalleryModal">
                        <i class="fas fa-plus me-1"></i> Add Image
                    </button>
                </div>
                <div class="card-body">
                    <div class="row g-3" id="galleryGrid">
                        <?php if (!empty($storeGallery)): ?>
                        <?php foreach ($storeGallery as $img): ?>
                        <div class="col-6 col-md-3 col-xl-2" id="gallery-item-<?= (int)$img['id'] ?>">
                            <div class="card h-100 <?= $img['is_cover'] ? 'border-warning' : '' ?>">
                                <img src="<?= htmlspecialchars(imageUrl($img['image_url'])) ?>"
                                     class="card-img-top" style="height:120px;object-fit:cover;" alt="Gallery">
                                <div class="card-body p-2">
                                    <?php if ($img['is_cover']): ?>
                                    <span class="badge bg-warning text-dark d-block mb-1"><i class="fas fa-star me-1"></i>Cover</span>
                                    <?php endif; ?>
                                    <?php if (!empty($img['caption'])): ?>
                                    <p class="card-text small text-muted mb-1"><?= htmlspecialchars($img['caption']) ?></p>
                                    <?php endif; ?>
                                    <div class="d-flex gap-1">
                                        <?php if (!$img['is_cover']): ?>
                                        <button class="btn btn-xs btn-outline-warning flex-fill" style="font-size:.75rem;"
                                                onclick="setCover(<?= (int)$img['id'] ?>,<?= (int)$store['id'] ?>,<?= (int)$store['merchant_id'] ?>)">
                                            <i class="fas fa-star"></i>
                                        </button>
                                        <?php endif; ?>
                                        <button class="btn btn-xs btn-outline-danger flex-fill" style="font-size:.75rem;"
                                                onclick="deleteGalleryImg(<?= (int)$img['id'] ?>,<?= (int)$store['merchant_id'] ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <div class="col-12" id="galleryEmpty"><p class="text-muted mb-0">No gallery images yet. Click <strong>Add Image</strong> to upload one.</p></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Gallery Image Modal -->
<div class="modal fade" id="addGalleryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Gallery Image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="galleryUploadError" class="alert alert-danger d-none"></div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Image <span class="text-danger">*</span></label>
                    <div id="galleryImgPreviewWrap" class="mb-2 d-none">
                        <img id="galleryImgPreview" src="" alt="Preview" class="img-fluid rounded" style="max-height:180px;">
                    </div>
                    <input type="file" id="galleryImageFile" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp"
                           onchange="previewImg(this,'galleryImgPreview','galleryImgPreviewWrap')">
                    <div class="form-text">JPG, PNG, GIF, WebP &mdash; max 3 MB</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Caption <span class="text-muted">(optional)</span></label>
                    <input type="text" id="galleryCaption" class="form-control" maxlength="255" placeholder="Short description">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="uploadGalleryBtn" onclick="uploadGalleryImage()">
                    <i class="fas fa-upload me-1"></i> Upload
                </button>
            </div>
        </div>
    </div>
</div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleDay(dayKey, isClosed) {
    const openInput = document.querySelector(`input[name="hours[${dayKey}][open]"]`);
    const closeInput = document.querySelector(`input[name="hours[${dayKey}][close]"]`);
    if (openInput) openInput.disabled = isClosed;
    if (closeInput) closeInput.disabled = isClosed;
}
function loadAreas(cityId, selectedId) {
    const sel = document.getElementById('areaSelect');
    if (!cityId) return;
    fetch(`<?= BASE_URL ?>master-data/areas-json?city_id=${cityId}`)
        .then(r => r.json())
        .then(data => {
            sel.innerHTML = '<option value="">Select area…</option>';
            data.forEach(a => {
                const opt = document.createElement('option');
                opt.value = a.id;
                opt.textContent = a.area_name;
                if (selectedId && a.id == selectedId) opt.selected = true;
                sel.appendChild(opt);
            });
        });
}
document.addEventListener('DOMContentLoaded', function() {
    if (typeof $ !== 'undefined' && $.fn.select2) {
        $('#storeCategorySelect').select2({ theme: 'bootstrap-5', width: '100%', placeholder: 'Select category…' });
    }

    // Build sub-category map from PHP-rendered options and capture pre-selected IDs
    var subMap = {};
    var initialSubIds = [];
    $('#storeSubCategorySelect option').each(function () {
        var cid = String($(this).data('category-id'));
        if (!subMap[cid]) subMap[cid] = [];
        subMap[cid].push({ id: String($(this).val()), text: $(this).text().trim() });
        if ($(this).prop('selected')) initialSubIds.push(String($(this).val()));
    });
    $('#storeSubCategorySelect').empty();
    $('#storeSubCategorySelect').select2({ theme: 'bootstrap-5', width: '100%', placeholder: 'Select a category first…' });

    function refreshStoreSubs(preselectIds) {
        var selectedCatIds = $('#storeCategorySelect').val() || [];
        var keepIds = Array.isArray(preselectIds) ? preselectIds
            : ($('#storeSubCategorySelect').hasClass('select2-hidden-accessible')
                ? ($('#storeSubCategorySelect').val() || []).map(String) : []);
        var newOptions = [];
        selectedCatIds.forEach(function (cid) {
            if (subMap[cid]) newOptions = newOptions.concat(subMap[cid]);
        });
        $('#storeSubCategorySelect').select2('destroy').empty();
        newOptions.forEach(function (o) {
            var $opt = $('<option>').val(o.id).text(o.text);
            if (keepIds.indexOf(o.id) !== -1) $opt.prop('selected', true);
            $('#storeSubCategorySelect').append($opt);
        });
        $('#storeSubCategorySelect').select2({
            theme: 'bootstrap-5', width: '100%',
            placeholder: selectedCatIds.length ? 'Select sub-category…' : 'Select a category first…'
        });
    }

    refreshStoreSubs(initialSubIds);
    $('#storeCategorySelect').on('change', refreshStoreSubs);

    // Store admin section — chevron toggle
    var adminSection = document.getElementById('storeAdminSection');
    if (adminSection) {
        adminSection.addEventListener('show.bs.collapse', function () {
            document.getElementById('storeAdminChevron').classList.replace('fa-chevron-down', 'fa-chevron-up');
        });
        adminSection.addEventListener('hide.bs.collapse', function () {
            document.getElementById('storeAdminChevron').classList.replace('fa-chevron-up', 'fa-chevron-down');
        });
    }

    // Store admin section — toggle deactivate/activate
    window.toggleAdminStatus = function (currentStatus) {
        var action = currentStatus === 'active' ? 'Deactivate' : 'Activate';
        if (!confirm(action + ' this store admin? They will ' + (currentStatus === 'active' ? 'no longer' : 'be able to') + ' log in.')) return;
        document.getElementById('toggleAdminFlag').value = '1';
        document.querySelector('form').submit();
    };

    // Store admin client-side validation
    document.querySelector('form').addEventListener('submit', function (e) {
        // Skip validation when removing admin
        if ((document.getElementById('removeAdminFlag')?.value ?? '0') === '1') return;
        var email   = (document.getElementById('adminEmail')?.value ?? '').trim();
        var pass    = document.getElementById('adminPassword')?.value ?? '';
        var confirm = document.getElementById('adminPasswordConfirm');
        if (confirm) confirm.classList.remove('is-invalid');
        // If creating new admin: email required; if email+pass given, validate
        if (email && !pass) {
            e.preventDefault();
            alert('Please enter a password for the Store Admin.');
            document.getElementById('adminPassword').focus();
            return;
        }
        if (pass && pass !== (confirm?.value ?? '')) {
            e.preventDefault();
            if (confirm) confirm.classList.add('is-invalid');
            confirm?.focus();
        }
    });
});

function previewImg(input, imgId, wrapId) {
    const file = input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById(imgId).src = e.target.result;
        document.getElementById(wrapId).classList.remove('d-none');
    };
    reader.readAsDataURL(file);
}

function setCover(imageId, storeId, merchantId) {
    if (!confirm('Set this image as the cover photo?')) return;
    fetch('<?= BASE_URL ?>merchants/set-cover-image', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `image_id=${imageId}&store_id=${storeId}&merchant_id=${merchantId}`
    }).then(r => r.json()).then(data => {
        if (data.success) location.reload();
        else alert(data.message || 'Failed to set cover image.');
    }).catch(() => alert('Request failed.'));
}

function deleteGalleryImg(imageId, merchantId) {
    if (!confirm('Delete this gallery image? This cannot be undone.')) return;
    fetch('<?= BASE_URL ?>merchants/delete-gallery-image', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `image_id=${imageId}&merchant_id=${merchantId}`
    }).then(r => r.json()).then(data => {
        if (data.success) {
            const el = document.getElementById('gallery-item-' + imageId);
            if (el) el.remove();
            const grid = document.getElementById('galleryGrid');
            if (grid && !grid.querySelector('[id^="gallery-item-"]')) {
                grid.innerHTML = '<div class="col-12" id="galleryEmpty"><p class="text-muted mb-0">No gallery images yet.</p></div>';
            }
        } else {
            alert(data.message || 'Failed to delete image.');
        }
    }).catch(() => alert('Request failed.'));
}

function uploadGalleryImage() {
    const fileInput = document.getElementById('galleryImageFile');
    const caption   = document.getElementById('galleryCaption').value.trim();
    const errBox    = document.getElementById('galleryUploadError');
    errBox.classList.add('d-none');
    if (!fileInput.files.length) { errBox.textContent = 'Please select an image.'; errBox.classList.remove('d-none'); return; }
    const fd = new FormData();
    fd.append('store_id',      <?= (int)$store['id'] ?>);
    fd.append('merchant_id',   <?= (int)$store['merchant_id'] ?>);
    fd.append('gallery_image', fileInput.files[0]);
    fd.append('caption',       caption);
    const btn = document.getElementById('uploadGalleryBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Uploading...';
    fetch('<?= BASE_URL ?>merchants/upload-gallery-image', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-upload me-1"></i> Upload';
            if (data.success) {
                const emptyMsg = document.getElementById('galleryEmpty');
                if (emptyMsg) emptyMsg.remove();
                const isCover = !document.querySelector('#galleryGrid [id^="gallery-item-"]');
                const div = document.createElement('div');
                div.className = 'col-6 col-md-3 col-xl-2';
                div.id = 'gallery-item-' + data.image_id;
                div.innerHTML = `
                    <div class="card h-100${isCover ? ' border-warning' : ''}">
                        <img src="${data.full_url}" class="card-img-top" style="height:120px;object-fit:cover;" alt="Gallery">
                        <div class="card-body p-2">
                            ${isCover ? '<span class="badge bg-warning text-dark d-block mb-1"><i class="fas fa-star me-1"></i>Cover</span>' : ''}
                            ${data.caption ? `<p class="card-text small text-muted mb-1">${data.caption}</p>` : ''}
                            <div class="d-flex gap-1">
                                ${!isCover ? `<button class="btn btn-xs btn-outline-warning flex-fill" style="font-size:.75rem;" onclick="setCover(${data.image_id},<?= (int)$store['id'] ?>,<?= (int)$store['merchant_id'] ?>)"><i class="fas fa-star"></i></button>` : ''}
                                <button class="btn btn-xs btn-outline-danger flex-fill" style="font-size:.75rem;" onclick="deleteGalleryImg(${data.image_id},<?= (int)$store['merchant_id'] ?>)"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                    </div>`;
                document.getElementById('galleryGrid').appendChild(div);
                // reset modal
                fileInput.value = '';
                document.getElementById('galleryCaption').value = '';
                document.getElementById('galleryImgPreviewWrap').classList.add('d-none');
                bootstrap.Modal.getInstance(document.getElementById('addGalleryModal')).hide();
            } else {
                errBox.textContent = data.message || 'Upload failed.';
                errBox.classList.remove('d-none');
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-upload me-1"></i> Upload';
            errBox.textContent = 'Network error. Please try again.';
            errBox.classList.remove('d-none');
        });
}
</script>
