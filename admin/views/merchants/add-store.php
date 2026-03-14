<?php /* views/merchants/add-store.php */ ?>

<?php if ($flash_error): ?><div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm"><i class="fas fa-exclamation-circle me-2"></i><?= escape($flash_error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>merchants">Merchants</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>merchants/profile?id=<?= $merchant['id'] ?>"><?= escape($merchant['business_name']) ?></a></li>
        <li class="breadcrumb-item active">Add Store</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Add Store</h1>
        <p class="text-muted mb-0 small">New store for <strong><?= escape($merchant['business_name']) ?></strong></p>
    </div>
    <a href="<?= BASE_URL ?>merchants/profile?id=<?= $merchant['id'] ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form id="addStoreForm" method="POST" action="<?= BASE_URL ?>merchants/add-store?merchant_id=<?= $merchant['id'] ?>" enctype="multipart/form-data" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="merchant_id" value="<?= $merchant['id'] ?>">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Store Name <span class="text-danger">*</span></label>
                        <input type="text" id="store_name" name="store_name" class="form-control" maxlength="255"
                               value="<?= escape($_POST['store_name'] ?? '') ?>">
                        <div class="invalid-feedback" id="err-store-name"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Address <span class="text-danger">*</span></label>
                        <textarea id="address" name="address" class="form-control" rows="2"><?= escape($_POST['address'] ?? '') ?></textarea>
                        <div class="invalid-feedback" id="err-address"></div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">City <span class="text-danger">*</span></label>
                            <select name="city_id" id="citySelect" class="form-select" onchange="loadAreas(this.value)">
                                <option value="">Select city…</option>
                                <?php foreach ($cities as $city): ?>
                                <option value="<?= $city['id'] ?>" <?= ($_POST['city_id'] ?? '') == $city['id'] ? 'selected' : '' ?>>
                                    <?= escape($city['city_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback" id="err-city"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Area <span class="text-danger">*</span></label>
                            <select name="area_id" id="areaSelect" class="form-select" onchange="loadLocations(this.value)">
                                <option value="">Select city first…</option>
                            </select>
                            <div class="invalid-feedback" id="err-area"></div>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Location</label>
                            <select name="location_id" id="locationSelect" class="form-select">
                                <option value="">Select area first…</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="tel" id="storePhone" name="phone" class="form-control" maxlength="20"
                                   value="<?= escape($_POST['phone'] ?? '') ?>">
                            <div class="invalid-feedback" id="err-store-phone"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" id="storeEmail" name="email" class="form-control" maxlength="255"
                                   value="<?= escape($_POST['email'] ?? '') ?>">
                            <div class="invalid-feedback" id="err-store-email"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2"><?= escape($_POST['description'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active"   <?= ($_POST['status'] ?? 'active') === 'active'   ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= ($_POST['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>

                    <!-- Category / Sub-category -->
                    <?php if (!empty($noCategories)): ?>
                    <div class="alert alert-warning border-0 shadow-sm mb-3">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        <strong>No categories assigned to this merchant.</strong>
                        <a href="<?= BASE_URL ?>merchants/edit?id=<?= $merchant['id'] ?>" class="alert-link ms-1">Assign categories first</a> before adding a store.
                    </div>
                    <?php else: ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                        <select name="category_ids[]" id="storeCategorySelect" class="form-select select2-multiple" multiple
                                data-placeholder="Select category…">
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= escape($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback" id="err-category"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Sub-category</label>
                        <select name="sub_category_ids[]" id="storeSubCategorySelect" class="form-select select2-multiple" multiple
                                data-placeholder="Select sub-category…">
                            <?php foreach ($subCategories ?? [] as $sub): ?>
                            <option value="<?= $sub['id'] ?>" data-category-id="<?= $sub['category_id'] ?>">
                                <?= escape($sub['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <!-- Opening Hours -->
                    <?php
                        $days = ['monday'=>'Monday','tuesday'=>'Tuesday','wednesday'=>'Wednesday','thursday'=>'Thursday','friday'=>'Friday','saturday'=>'Saturday','sunday'=>'Sunday'];
                    ?>
                    <div class="mb-4">
                        <label class="form-label fw-semibold"><i class="fas fa-clock me-1 text-muted"></i> Opening Hours</label>
                        <div class="border rounded p-3">
                            <?php foreach ($days as $dayKey => $dayLabel): ?>
                            <div class="row g-2 align-items-center mb-2">
                                <div class="col-3 col-md-2">
                                    <label class="form-label mb-0 small fw-semibold"><?= $dayLabel ?></label>
                                </div>
                                <div class="col-3 col-md-3">
                                    <input type="time" name="hours[<?= $dayKey ?>][open]" class="form-control form-control-sm" value="09:00">
                                </div>
                                <div class="col-3 col-md-3">
                                    <input type="time" name="hours[<?= $dayKey ?>][close]" class="form-control form-control-sm" value="21:00">
                                </div>
                                <div class="col-3 col-md-4">
                                    <div class="form-check">
                                        <input type="checkbox" name="hours[<?= $dayKey ?>][closed]" value="1"
                                               class="form-check-input" id="closed_<?= $dayKey ?>"
                                               onchange="toggleDay('<?= $dayKey ?>', this.checked)">
                                        <label class="form-check-label small" for="closed_<?= $dayKey ?>">Closed</label>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Store Admin Credentials (optional) -->
                    <div class="mb-4">
                        <div class="d-flex align-items-center justify-content-between border rounded p-3"
                             data-bs-toggle="collapse" data-bs-target="#storeAdminSection"
                             aria-expanded="false" aria-controls="storeAdminSection"
                             style="cursor:pointer; background:#f8f9fa;">
                            <span class="fw-semibold"><i class="fas fa-user-shield me-2 text-muted"></i> Store Admin Credentials <span class="text-muted fw-normal small">(optional)</span></span>
                            <i class="fas fa-chevron-down text-muted small" id="storeAdminChevron"></i>
                        </div>
                        <div class="collapse<?= (!empty($_POST['admin_email']) || !empty($_POST['admin_phone'])) ? ' show' : '' ?>" id="storeAdminSection">
                            <div class="border border-top-0 rounded-bottom p-3">
                                <p class="text-muted small mb-3">Create login credentials for a Store Admin who will manage this store. Leave everything blank to skip.</p>
                                <div class="row g-3 mb-1">
                                    <div class="col-md-6">
                                        <label class="form-label">Admin Email</label>
                                        <input type="email" name="admin_email" id="adminEmail" class="form-control"
                                               maxlength="255" autocomplete="off"
                                               value="<?= escape($_POST['admin_email'] ?? '') ?>">
                                        <div class="invalid-feedback" id="err-admin-email"></div>
                                        <span id="uniq-spin-adminEmail" class="spinner-border spinner-border-sm text-secondary ms-1" role="status" style="display:none;"></span>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Admin Phone</label>
                                        <input type="tel" name="admin_phone" id="adminPhone" class="form-control"
                                               maxlength="20" autocomplete="off"
                                               value="<?= escape($_POST['admin_phone'] ?? '') ?>">
                                        <div class="invalid-feedback" id="err-admin-phone"></div>
                                        <span id="uniq-spin-adminPhone" class="spinner-border spinner-border-sm text-secondary ms-1" role="status" style="display:none;"></span>
                                    </div>
                                </div>
                                <div class="form-text mb-3">Provide an email address, a phone number, or both. At least one is required as the login ID.</div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Password <span class="text-danger">*</span></label>
                                        <input type="password" name="admin_password" id="adminPassword"
                                               class="form-control" maxlength="255" autocomplete="new-password">
                                        <div class="invalid-feedback" id="err-admin-password"></div>
                                        <div class="form-text">Minimum 6 characters.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                        <input type="password" name="admin_password_confirm" id="adminPasswordConfirm"
                                               class="form-control" maxlength="255" autocomplete="new-password">
                                        <div class="invalid-feedback" id="err-admin-confirm"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Store Image Upload -->
                    <div class="card mb-3">
                        <div class="card-header fw-semibold">Store Image / Banner</div>
                        <div class="card-body">
                            <div id="storeImgPreviewWrap" class="mb-2 d-none">
                                <img id="storeImgPreview" src="" alt="Preview" class="img-thumbnail w-100" style="max-height:160px;object-fit:cover;">
                            </div>
                            <input type="file" name="store_image" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp"
                                   onchange="previewImg(this,'storeImgPreview','storeImgPreviewWrap')">
                            <div class="form-text">JPG, PNG, GIF, WebP &mdash; max 3 MB. Displayed as store banner/thumbnail.</div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i> Add Store</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function toggleDay(dayKey, isClosed) {
    const openInput  = document.querySelector(`input[name="hours[${dayKey}][open]"]`);
    const closeInput = document.querySelector(`input[name="hours[${dayKey}][close]"]`);
    if (openInput)  openInput.disabled  = isClosed;
    if (closeInput) closeInput.disabled = isClosed;
}

function loadAreas(cityId) {
    var sel    = document.getElementById('areaSelect');
    var locSel = document.getElementById('locationSelect');
    // Clear city/area errors when city changes
    ['citySelect','areaSelect'].forEach(function (id) {
        var el = document.getElementById(id);
        var er = document.getElementById({'citySelect':'err-city','areaSelect':'err-area'}[id]);
        if (el) el.classList.remove('is-invalid');
        if (er) { er.textContent = ''; er.style.display = ''; }
    });
    sel.innerHTML = '<option value="">Loading\u2026</option>';
    if (locSel) locSel.innerHTML = '<option value="">Select area first\u2026</option>';
    if (!cityId) { sel.innerHTML = '<option value="">Select city first\u2026</option>'; return; }
    fetch(`<?= BASE_URL ?>master-data/areas-json?city_id=${cityId}`)
        .then(function (r) { return r.json(); })
        .then(function (data) {
            sel.innerHTML = '<option value="">Select area\u2026</option>';
            data.forEach(function (a) {
                sel.insertAdjacentHTML('beforeend', `<option value="${a.id}">${a.area_name}</option>`);
            });
        })
        .catch(function () { sel.innerHTML = '<option value="">Error loading areas</option>'; });
}

function loadLocations(areaId) {
    var locSel = document.getElementById('locationSelect');
    if (!locSel) return;
    locSel.innerHTML = '<option value="">Loading\u2026</option>';
    if (!areaId) { locSel.innerHTML = '<option value="">Select area first\u2026</option>'; return; }
    fetch(`<?= BASE_URL ?>master-data/locations-json?area_id=${areaId}`)
        .then(function (r) { return r.json(); })
        .then(function (data) {
            locSel.innerHTML = '<option value="">(No specific location)</option>';
            data.forEach(function (l) {
                locSel.insertAdjacentHTML('beforeend', `<option value="${l.id}">${l.location_name}</option>`);
            });
        })
        .catch(function () { locSel.innerHTML = '<option value="">Error loading locations</option>'; });
}

(function () {
    var ERROR_IDS = {
        'store_name':            'err-store-name',
        'address':               'err-address',
        'citySelect':            'err-city',
        'areaSelect':            'err-area',
        'storePhone':            'err-store-phone',
        'storeEmail':            'err-store-email',
        'storeCategorySelect':   'err-category',
        'adminEmail':            'err-admin-email',
        'adminPhone':            'err-admin-phone',
        'adminPassword':         'err-admin-password',
        'adminPasswordConfirm':  'err-admin-confirm'
    };

    function setError(fieldId, message) {
        var el  = document.getElementById(fieldId);
        var err = document.getElementById(ERROR_IDS[fieldId]);
        if (el)  el.classList.add('is-invalid');
        if (err) { err.textContent = message; err.style.display = 'block'; }
    }

    function clearError(fieldId) {
        var el  = document.getElementById(fieldId);
        var err = document.getElementById(ERROR_IDS[fieldId]);
        if (el)  el.classList.remove('is-invalid');
        if (err) { err.textContent = ''; err.style.display = ''; }
    }

    function clearAllErrors() {
        Object.keys(ERROR_IDS).forEach(clearError);
    }

    // Live-clear on input/change
    Object.keys(ERROR_IDS).forEach(function (id) {
        var el = document.getElementById(id);
        if (!el) return;
        var event = (el.tagName === 'SELECT' || el.tagName === 'TEXTAREA') ? 'change' : 'input';
        el.addEventListener(event, function () { clearError(id); });
        if (el.tagName === 'TEXTAREA') el.addEventListener('input', function () { clearError(id); });
    });

    // ── Async uniqueness check for store admin login fields ───────────────────
    var uniqueXhr = {};

    function checkAdminUnique(fieldId) {
        var input   = document.getElementById(fieldId);
        var value   = input.value.trim();
        var err     = document.getElementById(ERROR_IDS[fieldId]);
        var spinner = document.getElementById('uniq-spin-' + fieldId);
        var field   = (fieldId === 'adminEmail') ? 'email' : 'phone';

        if (!value) return;
        if (uniqueXhr[fieldId]) { uniqueXhr[fieldId].abort(); }
        if (spinner) spinner.style.display = 'inline-block';

        var url = '<?= BASE_URL ?>merchants/check-store-admin-unique'
                + '?field=' + encodeURIComponent(field)
                + '&value=' + encodeURIComponent(value);

        uniqueXhr[fieldId] = $.getJSON(url)
            .done(function (res) {
                if (res.exists) {
                    input.classList.add('is-invalid');
                    if (err) {
                        err.textContent = (field === 'email')
                            ? 'This email address is already registered.'
                            : 'This phone number is already registered.';
                        err.style.display = 'block';
                    }
                } else {
                    clearError(fieldId);
                }
            })
            .always(function () {
                if (spinner) spinner.style.display = 'none';
            });
    }

    document.getElementById('adminEmail').addEventListener('blur', function () {
        var v = this.value.trim();
        if (v && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) checkAdminUnique('adminEmail');
    });
    document.getElementById('adminPhone').addEventListener('blur', function () {
        var v = this.value.trim();
        if (v) {
            var d = v.replace(/\D/g, '');
            if (/^[0-9+\s\-()]+$/.test(v) && d.length >= 7 && d.length <= 15) checkAdminUnique('adminPhone');
        }
    });

    // ── Submit handler ────────────────────────────────────────────────────────
    document.getElementById('addStoreForm').addEventListener('submit', function (e) {
        e.preventDefault();

        // Block while async check is in flight
        if (document.querySelector('.spinner-border[style*="inline-block"]')) return;

        clearAllErrors();

        var valid      = true;
        var firstError = null;

        function fail(fieldId, msg) {
            setError(fieldId, msg);
            if (!firstError) firstError = document.getElementById(fieldId);
            valid = false;
        }

        // 1. Store Name — required
        if (!document.getElementById('store_name').value.trim())
            fail('store_name', 'Store name is required.');

        // 2. Address — required
        if (!document.getElementById('address').value.trim())
            fail('address', 'Address is required.');

        // 3. City — required
        if (!document.getElementById('citySelect').value)
            fail('citySelect', 'Please select a city.');

        // 4. Area — required
        if (!document.getElementById('areaSelect').value)
            fail('areaSelect', 'Please select an area.');

        // 5. Store Phone — optional, validate format if given
        var storePhone = document.getElementById('storePhone').value.trim();
        if (storePhone) {
            var sp = storePhone.replace(/\D/g, '');
            if (!/^[0-9+\s\-()]+$/.test(storePhone) || sp.length < 7 || sp.length > 15)
                fail('storePhone', 'Please enter a valid phone number (7\u201315 digits).');
        }

        // 6. Store Email — optional, validate format if given
        var storeEmail = document.getElementById('storeEmail').value.trim();
        if (storeEmail && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(storeEmail))
            fail('storeEmail', 'Please enter a valid email address.');

        // 7. Category — required if the select is visible (merchant has categories assigned)
        var catSel = document.getElementById('storeCategorySelect');
        if (catSel) {
            var selOpts = Array.from(catSel.selectedOptions).filter(function (o) { return o.value; });
            if (!selOpts.length)
                fail('storeCategorySelect', 'Please select at least one business category.');
        }

        // 8. Store Admin section — only validate if any admin field is filled
        var adminEmail   = (document.getElementById('adminEmail').value   || '').trim();
        var adminPhone   = (document.getElementById('adminPhone').value   || '').trim();
        var adminPass    = (document.getElementById('adminPassword').value || '');
        var adminConfirm = (document.getElementById('adminPasswordConfirm').value || '');
        var adminUsed    = adminEmail || adminPhone;

        if (adminUsed) {
            // Auto-expand the section so errors are visible
            var sec = document.getElementById('storeAdminSection');
            if (sec && !sec.classList.contains('show'))
                bootstrap.Collapse.getOrCreateInstance(sec).show();

            // Email format
            if (adminEmail && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(adminEmail))
                fail('adminEmail', 'Please enter a valid email address.');

            // Phone format
            if (adminPhone) {
                var ap = adminPhone.replace(/\D/g, '');
                if (!/^[0-9+\s\-()]+$/.test(adminPhone) || ap.length < 7 || ap.length > 15)
                    fail('adminPhone', 'Please enter a valid phone number (7\u201315 digits).');
            }

            // Carry forward async "already registered" errors
            if (document.getElementById('adminEmail').classList.contains('is-invalid') &&
                document.getElementById('err-admin-email').textContent.indexOf('already registered') !== -1) {
                if (!firstError) firstError = document.getElementById('adminEmail');
                valid = false;
            }
            if (document.getElementById('adminPhone').classList.contains('is-invalid') &&
                document.getElementById('err-admin-phone').textContent.indexOf('already registered') !== -1) {
                if (!firstError) firstError = document.getElementById('adminPhone');
                valid = false;
            }

            // Password — required when admin section is used
            if (!adminPass) {
                fail('adminPassword', 'Password is required for the store admin.');
            } else if (adminPass.length < 6) {
                fail('adminPassword', 'Password must be at least 6 characters.');
            }

            // Confirm password
            if (!adminConfirm) {
                fail('adminPasswordConfirm', 'Please confirm the password.');
            } else if (adminPass && adminConfirm !== adminPass) {
                fail('adminPasswordConfirm', 'Passwords do not match.');
            }
        }

        if (!valid) {
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstError.focus({ preventScroll: true });
            }
            return;
        }

        this.submit();
    });

    // ── DOM-ready: Select2 + sub-category map + chevron init ─────────────────
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.select2-multiple').forEach(function (el) {
            $(el).select2({ theme: 'bootstrap-5', placeholder: el.dataset.placeholder });
        });

        // Build sub-category map from PHP-rendered options (no AJAX needed)
        var subMap = {};
        $('#storeSubCategorySelect option').each(function () {
            var cid = String($(this).data('category-id'));
            if (!subMap[cid]) subMap[cid] = [];
            subMap[cid].push({ id: String($(this).val()), text: $(this).text().trim() });
        });
        $('#storeSubCategorySelect').empty();
        $('#storeSubCategorySelect').select2({
            theme: 'bootstrap-5', width: '100%', placeholder: 'Select a category first\u2026'
        });

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
                placeholder: selectedCatIds.length ? 'Select sub-category\u2026' : 'Select a category first\u2026'
            });
        }

        refreshStoreSubs([]);
        $('#storeCategorySelect').on('change', refreshStoreSubs);

        var adminSection = document.getElementById('storeAdminSection');
        if (adminSection) {
            adminSection.addEventListener('show.bs.collapse', function () {
                document.getElementById('storeAdminChevron').classList.replace('fa-chevron-down', 'fa-chevron-up');
            });
            adminSection.addEventListener('hide.bs.collapse', function () {
                document.getElementById('storeAdminChevron').classList.replace('fa-chevron-up', 'fa-chevron-down');
            });
        }
    });
}());

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
</script>
