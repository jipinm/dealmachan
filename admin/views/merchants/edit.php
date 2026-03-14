<?php /* views/merchants/edit.php */ ?>

<?php if ($flash_error): ?><div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm"><i class="fas fa-exclamation-circle me-2"></i><?= escape($flash_error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>merchants">Merchants</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>merchants/profile?id=<?= $merchant['id'] ?>"><?= escape($merchant['business_name']) ?></a></li>
        <li class="breadcrumb-item active">Edit</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Edit Merchant</h1>
        <p class="text-muted mb-0 small"><?= escape($merchant['business_name']) ?></p>
    </div>
    <a href="<?= BASE_URL ?>merchants/profile?id=<?= $merchant['id'] ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back to Profile</a>
</div>

<form id="merchantEditForm" method="POST" action="<?= BASE_URL ?>merchants/edit?id=<?= $merchant['id'] ?>" enctype="multipart/form-data" novalidate>
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
    <div class="row g-4">

        <!-- Left column -->
        <div class="col-lg-8">

            <!-- Business Information -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold border-bottom">
                    <i class="fas fa-store me-2 text-primary"></i> Business Information
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Business Name <span class="text-danger">*</span></label>
                        <input type="text" id="business_name" name="business_name" class="form-control" maxlength="255"
                               value="<?= escape($_POST['business_name'] ?? $merchant['business_name']) ?>">
                        <div class="invalid-feedback" id="err-business-name"></div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Registration Number</label>
                            <input type="text" name="registration_number" class="form-control" maxlength="100"
                                   value="<?= escape($_POST['registration_number'] ?? $merchant['registration_number']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">GST Number</label>
                            <input type="text" id="gst_number" name="gst_number" class="form-control" maxlength="50"
                                   value="<?= escape($_POST['gst_number'] ?? $merchant['gst_number']) ?>">
                            <div class="invalid-feedback" id="err-gst"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact & Credentials -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold border-bottom">
                    <i class="fas fa-envelope me-2 text-primary"></i> Contact & Login Credentials
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" id="email" name="email" class="form-control" maxlength="255"
                                   value="<?= escape($_POST['email'] ?? $merchant['email']) ?>">
                            <div class="invalid-feedback" id="err-email"></div>
                            <span id="uniq-spin-email" class="spinner-border spinner-border-sm text-secondary ms-1" role="status" style="display:none;"></span>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Phone</label>
                            <input type="tel" id="phone" name="phone" class="form-control" maxlength="20"
                                   value="<?= escape($_POST['phone'] ?? $merchant['phone']) ?>">
                            <div class="invalid-feedback" id="err-phone"></div>
                            <span id="uniq-spin-phone" class="spinner-border spinner-border-sm text-secondary ms-1" role="status" style="display:none;"></span>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">New Password</label>
                            <div class="input-group">
                                <input type="password" name="password" id="password" class="form-control" autocomplete="new-password" placeholder="Leave blank to keep current">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePwd('password','eyePassword')">
                                    <i class="fas fa-eye" id="eyePassword"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback" id="err-password"></div>
                            <div class="form-text">Minimum 8 characters. Leave blank to keep current.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirm New Password</label>
                            <div class="input-group">
                                <input type="password" name="password_confirm" id="password_confirm" class="form-control" autocomplete="new-password">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePwd('password_confirm','eyeConfirm')">
                                    <i class="fas fa-eye" id="eyeConfirm"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback" id="err-confirm"></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Right column -->
        <div class="col-lg-4">

            <!-- Account Settings -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold border-bottom">
                    <i class="fas fa-cog me-2 text-primary"></i> Account Settings
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Account Status</label>
                        <select name="status" class="form-select">
                            <?php foreach (['active','inactive','blocked'] as $s): ?>
                            <option value="<?= $s ?>" <?= ($merchant['status'] === $s) ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Profile Status</label>
                        <select name="profile_status" class="form-select">
                            <?php foreach (['pending','approved','rejected'] as $s): ?>
                            <option value="<?= $s ?>" <?= ($merchant['profile_status'] === $s) ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subscription Status</label>
                        <select name="subscription_status" class="form-select">
                            <?php foreach (['trial','active','expired'] as $s): ?>
                            <option value="<?= $s ?>" <?= ($merchant['subscription_status'] === $s) ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subscription Plan</label>
                        <select name="subscription_plan" class="form-select">
                            <?php foreach (['merchant_only','coupon_merchant','hybrid','advanced'] as $p): ?>
                            <option value="<?= $p ?>" <?= ($merchant['subscription_plan'] === $p) ? 'selected' : '' ?>><?= ucwords(str_replace('_', ' ', $p)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subscription Expiry</label>
                        <input type="date" name="subscription_expiry" class="form-control"
                               value="<?= escape($merchant['subscription_expiry'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- Labels & Priority -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold border-bottom">
                    <i class="fas fa-tag me-2 text-primary"></i> Label & Priority
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Label</label>
                        <select name="label_id" class="form-select select2" data-placeholder="No label">
                            <option value="">No label</option>
                            <?php foreach ($labels as $label): ?>
                            <option value="<?= $label['id'] ?>" <?= ($merchant['label_id'] == $label['id']) ? 'selected' : '' ?>>
                                <?= escape($label['label_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Priority Weight</label>
                        <input type="number" name="priority_weight" class="form-control" min="0" max="9999"
                               value="<?= (int)$merchant['priority_weight'] ?>">
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_premium" id="isPremium" value="1"
                               <?= $merchant['is_premium'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="isPremium">Premium Partner</label>
                    </div>
                </div>
            </div>

            <!-- Categories -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold border-bottom">
                    <i class="fas fa-layer-group me-2 text-primary"></i> Business Categories
                </div>
                <div class="card-body">
                    <label class="form-label">Categories</label>
                    <select name="category_ids[]" id="merchantCategories" class="form-select select2" multiple
                            data-placeholder="Select categories…">
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= in_array($cat['id'], $selectedCategoryIds ?? []) ? 'selected' : '' ?>>
                            <?= escape($cat['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Stores under this merchant will only be allowed to use these categories.</div>
                </div>
            </div>

            <!-- Sub Categories -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold border-bottom">
                    <i class="fas fa-tags me-2 text-primary"></i> Business Sub Categories
                </div>
                <div class="card-body">
                    <label class="form-label">Sub Categories</label>
                    <select name="sub_category_ids[]" id="merchantSubCategories" class="form-select" multiple
                            data-placeholder="Select sub categories…">
                        <?php foreach ($subCategories as $sub): ?>
                        <option value="<?= $sub['id'] ?>" data-category-id="<?= $sub['category_id'] ?>"
                            <?= in_array($sub['id'], $selectedSubCategoryIds ?? []) ? 'selected' : '' ?>>
                            <?= escape($sub['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Specific sub-categories this merchant operates in.</div>
                </div>
            </div>

            <!-- Business Logo -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold border-bottom">
                    <i class="fas fa-image me-2 text-primary"></i> Business Logo
                </div>
                <div class="card-body">
                    <?php if (!empty($merchant['business_logo'])): ?>
                    <div class="mb-2 text-center" id="logoCurrentWrap">
                        <img src="<?= imageUrl($merchant['business_logo']) ?>"
                             alt="Current logo" class="img-thumbnail"
                             style="max-height:100px;max-width:100%;object-fit:contain;"
                             onerror="this.style.display='none'">
                        <div class="mt-1">
                            <small class="text-muted">Current logo</small>
                            <label class="d-block mt-1">
                                <input type="checkbox" name="remove_logo" value="1" class="form-check-input me-1">
                                <small class="text-danger">Remove current logo</small>
                            </label>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div id="logoPreviewWrap" class="mb-2 d-none">
                        <img id="logoPreview" src="" alt="New logo preview"
                             class="img-thumbnail" style="max-height:100px;max-width:100%;object-fit:contain;">
                        <div class="mt-1"><small class="text-muted">New logo (pending save)</small></div>
                    </div>
                    <input type="file" name="business_logo" id="business_logo"
                           class="form-control" accept="image/jpeg,image/png,image/gif,image/webp"
                           onchange="previewImg(this,'logoPreview','logoPreviewWrap')">
                    <div class="form-text">JPG, PNG, GIF, WebP &mdash; max 2 MB. Upload to replace current logo.</div>
                </div>
            </div>

            <!-- Banner Image -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold border-bottom">
                    <i class="fas fa-panorama me-2 text-primary"></i> Banner Image
                </div>
                <div class="card-body">
                    <?php if (!empty($merchant['banner_image'])): ?>
                    <div class="mb-2" id="bannerCurrentWrap">
                        <img src="<?= imageUrl($merchant['banner_image']) ?>"
                             alt="Current banner" class="img-thumbnail w-100"
                             style="max-height:100px;object-fit:cover;"
                             onerror="this.style.display='none'">
                        <div class="mt-1">
                            <small class="text-muted">Current banner</small>
                            <label class="d-block mt-1">
                                <input type="checkbox" name="remove_banner" value="1" class="form-check-input me-1">
                                <small class="text-danger">Remove current banner</small>
                            </label>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div id="bannerPreviewWrap" class="mb-2 d-none">
                        <img id="bannerPreview" src="" alt="New banner preview"
                             class="img-thumbnail w-100" style="max-height:100px;object-fit:cover;">
                        <div class="mt-1"><small class="text-muted">New banner (pending save)</small></div>
                    </div>
                    <input type="file" name="banner_image" id="banner_image"
                           class="form-control" accept="image/jpeg,image/png,image/gif,image/webp"
                           onchange="previewImg(this,'bannerPreview','bannerPreviewWrap')">
                    <div class="form-text">Recommended: 1200×400 px. JPG, PNG, GIF, WebP &mdash; max 3 MB.</div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-save me-2"></i> Save Changes
            </button>
        </div>

    </div>
</form>

<script>
function togglePwd(id, iconId) {
    const input = document.getElementById(id);
    const icon  = document.getElementById(iconId);
    if (input.type === 'password') { input.type = 'text'; icon.classList.replace('fa-eye','fa-eye-slash'); }
    else                           { input.type = 'password'; icon.classList.replace('fa-eye-slash','fa-eye'); }
}

(function () {
    var FIELDS = ['business_name','email','phone','gst_number','password','password_confirm'];
    var ERROR_IDS = {
        'business_name':    'err-business-name',
        'email':            'err-email',
        'phone':            'err-phone',
        'gst_number':       'err-gst',
        'password':         'err-password',
        'password_confirm': 'err-confirm'
    };
    // Current merchant's user_id — used to exclude self from uniqueness checks
    var EXCLUDE_USER_ID = <?= (int)$merchant['user_id'] ?>;

    function setError(fieldId, message) {
        var input = document.getElementById(fieldId);
        var err   = document.getElementById(ERROR_IDS[fieldId]);
        if (input) input.classList.add('is-invalid');
        if (err)   { err.textContent = message; err.style.display = 'block'; }
    }

    function clearErrors() {
        FIELDS.forEach(function (id) {
            var input = document.getElementById(id);
            var err   = document.getElementById(ERROR_IDS[id]);
            if (input) input.classList.remove('is-invalid');
            if (err)   { err.textContent = ''; err.style.display = ''; }
        });
    }

    // Live-clear error when user starts correcting a field
    FIELDS.forEach(function (id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('input', function () {
            this.classList.remove('is-invalid');
            var err = document.getElementById(ERROR_IDS[id]);
            if (err) { err.textContent = ''; err.style.display = ''; }
        });
    });

    // ── Async uniqueness check on blur ───────────────────────────────────────
    var uniqueXhr = {};

    function checkUnique(fieldId, fieldName) {
        var input   = document.getElementById(fieldId);
        var value   = input.value.trim();
        var err     = document.getElementById(ERROR_IDS[fieldId]);
        var spinner = document.getElementById('uniq-spin-' + fieldId);

        if (!value) return;
        if (uniqueXhr[fieldId]) { uniqueXhr[fieldId].abort(); }
        if (spinner) spinner.style.display = 'inline-block';

        var url = '<?= BASE_URL ?>merchants/check-unique?field=' + encodeURIComponent(fieldName)
                + '&value=' + encodeURIComponent(value)
                + '&exclude_user_id=' + EXCLUDE_USER_ID;

        uniqueXhr[fieldId] = $.getJSON(url)
            .done(function (res) {
                if (res.exists) {
                    input.classList.add('is-invalid');
                    if (err) {
                        err.textContent = fieldName === 'email'
                            ? 'This email address is already registered.'
                            : 'This phone number is already registered.';
                        err.style.display = 'block';
                    }
                } else {
                    input.classList.remove('is-invalid');
                    if (err) { err.textContent = ''; err.style.display = ''; }
                }
            })
            .always(function () {
                if (spinner) spinner.style.display = 'none';
            });
    }

    document.getElementById('email').addEventListener('blur', function () {
        checkUnique('email', 'email');
    });
    document.getElementById('phone').addEventListener('blur', function () {
        checkUnique('phone', 'phone');
    });

    document.getElementById('merchantEditForm').addEventListener('submit', function (e) {
        e.preventDefault();

        // Block while uniqueness check is in flight
        if (document.querySelector('.spinner-border[style*="inline-block"]')) return;

        clearErrors();

        var valid      = true;
        var firstError = null;

        function fail(fieldId, message) {
            setError(fieldId, message);
            if (!firstError) firstError = document.getElementById(fieldId);
            valid = false;
        }

        // 1. Business Name — required
        var businessName = document.getElementById('business_name').value.trim();
        if (!businessName) {
            fail('business_name', 'Business name is required.');
        }

        // 2. Email / Phone — at least one must be present
        var email = document.getElementById('email').value.trim();
        var phone = document.getElementById('phone').value.trim();

        if (!email && !phone) {
            fail('email', 'Please provide an email address or a phone number.');
            fail('phone', 'Please provide an email address or a phone number.');
        } else {
            if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                fail('email', 'Please enter a valid email address.');
            }
            if (phone) {
                var digits = phone.replace(/\D/g, '');
                if (!/^[0-9+\s\-()]+$/.test(phone) || digits.length < 7 || digits.length > 15) {
                    fail('phone', 'Please enter a valid phone number (7–15 digits).');
                }
            }
        }

        // Carry forward async "already registered" errors
        if (document.getElementById('email').classList.contains('is-invalid') &&
            document.getElementById('err-email').textContent.indexOf('already registered') !== -1) {
            if (!firstError) firstError = document.getElementById('email');
            valid = false;
        }
        if (document.getElementById('phone').classList.contains('is-invalid') &&
            document.getElementById('err-phone').textContent.indexOf('already registered') !== -1) {
            if (!firstError) firstError = document.getElementById('phone');
            valid = false;
        }

        // 3. GST Number — optional, validate Indian GST format if present
        var gst = document.getElementById('gst_number').value.trim();
        if (gst && !/^[0-9]{2}[A-Za-z]{5}[0-9]{4}[A-Za-z]{1}[1-9A-Za-z]{1}Z[0-9A-Za-z]{1}$/.test(gst)) {
            fail('gst_number', 'Invalid GST number format. Expected format: 22AAAAA0000A1Z5.');
        }

        // 4. Password — optional on edit; if provided must be ≥ 8 chars
        var pwd = document.getElementById('password').value;
        if (pwd && pwd.length < 8) {
            fail('password', 'New password must be at least 8 characters long.');
        }

        // 5. Confirm Password — must match if new password is set
        var pwdConfirm = document.getElementById('password_confirm').value;
        if (pwd && !pwdConfirm) {
            fail('password_confirm', 'Please confirm the new password.');
        } else if (pwd && pwdConfirm !== pwd) {
            fail('password_confirm', 'Passwords do not match.');
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
}());

function previewImg(input, imgId, wrapId) {
    var wrap = document.getElementById(wrapId);
    var img  = document.getElementById(imgId);
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) {
            img.src = e.target.result;
            wrap.classList.remove('d-none');
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        img.src = '';
        wrap.classList.add('d-none');
    }
}
</script>
<?php $additional_js = <<<'ENDJS'
<script>
$(function () {
    // Build sub-category map from PHP-rendered options and capture any pre-selected IDs
    var subMap = {};
    var initialSubIds = [];
    $('#merchantSubCategories option').each(function () {
        var cid = String($(this).data('category-id'));
        if (!subMap[cid]) subMap[cid] = [];
        subMap[cid].push({ id: String($(this).val()), text: $(this).text().trim() });
        if ($(this).prop('selected')) initialSubIds.push(String($(this).val()));
    });

    // Clear sub-select — will be rebuilt based on selected categories
    $('#merchantSubCategories').empty();

    // Init generic dropdowns first, then override categories with placeholder
    $('select.select2').select2({ theme: 'bootstrap-5', width: '100%' });
    $('#merchantCategories').select2({
        theme: 'bootstrap-5', width: '100%',
        placeholder: 'Select categories\u2026'
    });
    $('#merchantSubCategories').select2({
        theme: 'bootstrap-5', width: '100%',
        placeholder: 'Select a category first\u2026'
    });

    function refreshSubCategories(preselectIds) {
        var selectedCatIds = $('#merchantCategories').val() || [];
        // Array.isArray distinguishes an explicit preselect array from a jQuery event object
        var keepIds = Array.isArray(preselectIds) ? preselectIds
            : ($('#merchantSubCategories').hasClass('select2-hidden-accessible')
                ? ($('#merchantSubCategories').val() || []).map(String) : []);

        var newOptions = [];
        selectedCatIds.forEach(function (cid) {
            if (subMap[cid]) newOptions = newOptions.concat(subMap[cid]);
        });

        $('#merchantSubCategories').select2('destroy').empty();
        newOptions.forEach(function (o) {
            var $opt = $('<option>').val(o.id).text(o.text);
            if (keepIds.indexOf(o.id) !== -1) $opt.prop('selected', true);
            $('#merchantSubCategories').append($opt);
        });
        $('#merchantSubCategories').select2({
            theme: 'bootstrap-5', width: '100%',
            placeholder: selectedCatIds.length ? 'Select sub categories\u2026' : 'Select a category first\u2026'
        });
    }

    // Pre-select saved sub-categories on load (no-op on add since initialSubIds is [])
    refreshSubCategories(initialSubIds);
    $('#merchantCategories').on('change', refreshSubCategories);
});
</script>
ENDJS;
?>