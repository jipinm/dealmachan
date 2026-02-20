<?php /* views/merchants/add.php */ ?>

<?php if ($flash_error): ?><div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm"><i class="fas fa-exclamation-circle me-2"></i><?= escape($flash_error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>merchants">Merchants</a></li>
        <li class="breadcrumb-item active">Add Merchant</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Add Merchant</h1>
        <p class="text-muted mb-0 small">Register a new merchant business account.</p>
    </div>
    <a href="<?= BASE_URL ?>merchants" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
</div>

<form method="POST" action="<?= BASE_URL ?>merchants/add">
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
                        <input type="text" name="business_name" class="form-control" required maxlength="255"
                               value="<?= escape($_POST['business_name'] ?? '') ?>">
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Registration Number</label>
                            <input type="text" name="registration_number" class="form-control" maxlength="100"
                                   value="<?= escape($_POST['registration_number'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">GST Number</label>
                            <input type="text" name="gst_number" class="form-control" maxlength="50"
                                   value="<?= escape($_POST['gst_number'] ?? '') ?>">
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
                            <input type="email" name="email" class="form-control" maxlength="255"
                                   value="<?= escape($_POST['email'] ?? '') ?>">
                            <div class="form-text">Required if phone is not provided.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Phone</label>
                            <input type="tel" name="phone" class="form-control" maxlength="20"
                                   value="<?= escape($_POST['phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="password" id="password" class="form-control" minlength="8" required autocomplete="new-password">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePwd('password','eyePassword')">
                                    <i class="fas fa-eye" id="eyePassword"></i>
                                </button>
                            </div>
                            <div class="form-text">Minimum 8 characters.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="password_confirm" id="password_confirm" class="form-control" required autocomplete="new-password">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePwd('password_confirm','eyeConfirm')">
                                    <i class="fas fa-eye" id="eyeConfirm"></i>
                                </button>
                            </div>
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
                            <option value="active"   <?= ($_POST['status'] ?? 'active') === 'active'   ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= ($_POST['status'] ?? '') === 'inactive'        ? 'selected' : '' ?>>Inactive</option>
                            <option value="blocked"  <?= ($_POST['status'] ?? '') === 'blocked'         ? 'selected' : '' ?>>Blocked</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Profile Status</label>
                        <select name="profile_status" class="form-select">
                            <option value="pending"  <?= ($_POST['profile_status'] ?? 'pending') === 'pending'  ? 'selected' : '' ?>>Pending</option>
                            <option value="approved" <?= ($_POST['profile_status'] ?? '') === 'approved'        ? 'selected' : '' ?>>Approved</option>
                            <option value="rejected" <?= ($_POST['profile_status'] ?? '') === 'rejected'        ? 'selected' : '' ?>>Rejected</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subscription Plan</label>
                        <select name="subscription_status" class="form-select">
                            <option value="trial"   <?= ($_POST['subscription_status'] ?? 'trial') === 'trial'   ? 'selected' : '' ?>>Trial</option>
                            <option value="active"  <?= ($_POST['subscription_status'] ?? '') === 'active'       ? 'selected' : '' ?>>Active</option>
                            <option value="expired" <?= ($_POST['subscription_status'] ?? '') === 'expired'      ? 'selected' : '' ?>>Expired</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subscription Expiry</label>
                        <input type="date" name="subscription_expiry" class="form-control"
                               value="<?= escape($_POST['subscription_expiry'] ?? '') ?>">
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
                            <option value="<?= $label['id'] ?>" <?= ($_POST['label_id'] ?? '') == $label['id'] ? 'selected' : '' ?>>
                                <?= escape($label['label_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Priority Weight</label>
                        <input type="number" name="priority_weight" class="form-control" min="0" max="9999"
                               value="<?= (int)($_POST['priority_weight'] ?? 0) ?>">
                        <div class="form-text">Higher = appears first in listings.</div>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_premium" id="isPremium" value="1"
                               <?= !empty($_POST['is_premium']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="isPremium">Premium Partner</label>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-save me-2"></i> Create Merchant
            </button>
        </div>

    </div><!-- row -->
</form>

<script>
function togglePwd(id, iconId) {
    const input = document.getElementById(id);
    const icon  = document.getElementById(iconId);
    if (input.type === 'password') { input.type = 'text'; icon.classList.replace('fa-eye','fa-eye-slash'); }
    else                           { input.type = 'password'; icon.classList.replace('fa-eye-slash','fa-eye'); }
}
document.querySelector('form').addEventListener('submit', function(e) {
    const p  = document.getElementById('password').value;
    const pc = document.getElementById('password_confirm').value;
    if (p !== pc) { e.preventDefault(); alert('Passwords do not match.'); }
});
document.addEventListener('DOMContentLoaded', function() {
    if (typeof $ !== 'undefined' && $.fn.select2) {
        $('.select2').select2({ theme:'bootstrap-5', width:'100%' });
    }
});
</script>
