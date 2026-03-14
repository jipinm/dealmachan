<?php /* views/customers/edit.php */ ?>
<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>customers">Customer Management</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>customers/profile?id=<?= $customer['id'] ?>"><?= escape($customer['name']) ?></a></li>
            <li class="breadcrumb-item active">Edit</li>
        </ol>
    </nav>
    <h1 class="h3 mb-0">Edit Customer</h1>
    <p class="text-muted small mb-0">Update profile and account settings for <strong><?= escape($customer['name']) ?></strong>.</p>
</div>

<?php if ($flash_error): ?><div class="alert alert-danger border-0 shadow-sm"><i class="fas fa-exclamation-circle me-2"></i><?= escape($flash_error) ?></div><?php endif; ?>

<form method="POST" action="<?= BASE_URL ?>customers/edit?id=<?= $customer['id'] ?>" id="editCustomerForm">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

    <div class="row g-4">
        <!-- Left Column -->
        <div class="col-lg-8">

            <!-- Personal Information -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold border-bottom">
                    <i class="fas fa-user me-2 text-primary"></i> Personal Information
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required
                               value="<?= escape($_POST['name'] ?? $customer['name']) ?>">
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Date of Birth</label>
                            <input type="date" name="date_of_birth" class="form-control"
                                   value="<?= escape($_POST['date_of_birth'] ?? $customer['date_of_birth'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Gender</label>
                            <select name="gender" class="form-select">
                                <option value="">&mdash; Select &mdash;</option>
                                <?php foreach (['male', 'female', 'other'] as $g): ?>
                                <option value="<?= $g ?>" <?= (($_POST['gender'] ?? $customer['gender']) === $g) ? 'selected' : '' ?>><?= ucfirst($g) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Profession</label>
                            <select name="profession_id" class="form-select select2">
                                <option value="">&mdash; None &mdash;</option>
                                <?php foreach ($professions as $prof): ?>
                                <option value="<?= $prof['id'] ?>"
                                    <?= (($_POST['profession_id'] ?? $customer['profession_id']) == $prof['id']) ? 'selected' : '' ?>>
                                    <?= escape($prof['profession_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold border-bottom">
                    <i class="fas fa-address-card me-2 text-info"></i> Contact Information
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email Address</label>
                            <input type="email" name="email" class="form-control"
                                   value="<?= escape($_POST['email'] ?? $customer['email'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Phone Number</label>
                            <input type="text" name="phone" class="form-control"
                                   value="<?= escape($_POST['phone'] ?? $customer['phone'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Change Password -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold border-bottom">
                    <i class="fas fa-lock me-2 text-warning"></i> Change Password
                    <span class="text-muted fw-normal small ms-2">(leave blank to keep current)</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">New Password</label>
                            <div class="input-group">
                                <input type="password" name="password" id="password" class="form-control" minlength="8" placeholder="Min 8 characters">
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePw('password')"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Confirm New Password</label>
                            <div class="input-group">
                                <input type="password" id="confirmPassword" class="form-control" placeholder="Re-enter new password">
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePw('confirmPassword')"><i class="fas fa-eye"></i></button>
                            </div>
                            <div id="pwMismatch" class="text-danger small mt-1 d-none">Passwords do not match.</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Account Settings -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold border-bottom">
                    <i class="fas fa-cog me-2 text-success"></i> Account Settings
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Customer Type</label>
                            <select name="customer_type" class="form-select">
                                <?php foreach (['standard', 'premium', 'dealmaker'] as $t): ?>
                                <option value="<?= $t ?>" <?= (($_POST['customer_type'] ?? $customer['customer_type']) === $t) ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Registration Type</label>
                            <select name="registration_type" class="form-select">
                                <?php
                                $regTypes = [
                                    'self_registration'  => 'Self Registration',
                                    'merchant_app'       => 'Merchant App',
                                    'admin_registration' => 'Admin Registration',
                                    'preprinted_card'    => 'Preprinted Card',
                                    'auto_profile'       => 'Auto Profile',
                                ];
                                foreach ($regTypes as $val => $lbl): ?>
                                <option value="<?= $val ?>" <?= (($_POST['registration_type'] ?? $customer['registration_type']) === $val) ? 'selected' : '' ?>><?= $lbl ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" class="form-select">
                                <option value="active"   <?= (($_POST['status'] ?? $customer['status']) === 'active')   ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= (($_POST['status'] ?? $customer['status']) === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                                <option value="blocked"  <?= (($_POST['status'] ?? $customer['status']) === 'blocked')  ? 'selected' : '' ?>>Blocked</option>
                                <option value="pending"  <?= (($_POST['status'] ?? $customer['status']) === 'pending')  ? 'selected' : '' ?>>Pending</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-2"></i> Save Changes</button>
                <a href="<?= BASE_URL ?>customers/profile?id=<?= $customer['id'] ?>" class="btn btn-light px-4">Cancel</a>
            </div>
        </div>

        <!-- Right Sidebar -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold border-bottom"><i class="fas fa-id-card me-2 text-secondary"></i> Account Info</div>
                <div class="card-body small">
                    <div class="mb-2"><span class="text-muted">Referral Code:</span> <code><?= escape($customer['referral_code'] ?? '&mdash;') ?></code></div>
                    <div class="mb-2"><span class="text-muted">Registered:</span> <?= formatDate($customer['registered_at'] ?? $customer['created_at']) ?></div>
                    <div class="mb-2"><span class="text-muted">Last Login:</span> <?= $customer['last_login'] ? formatDateTime($customer['last_login']) : '<em>Never</em>' ?></div>
                    <?php if ($customer['referrer_name']): ?>
                    <div><span class="text-muted">Referred By:</span> <?= escape($customer['referrer_name']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="alert alert-info border-0 small">
                <i class="fas fa-shield-alt me-1"></i>
                Password field is optional during edit. If left blank, the current password is preserved.
            </div>
        </div>
    </div>
</form>

<script>
function togglePw(id) {
    const f = document.getElementById(id);
    f.type = f.type === 'password' ? 'text' : 'password';
}
document.getElementById('editCustomerForm').addEventListener('submit', function(e) {
    const p = document.getElementById('password').value;
    const c = document.getElementById('confirmPassword').value;
    if (p && p !== c) {
        document.getElementById('pwMismatch').classList.remove('d-none');
        e.preventDefault();
    }
});
document.getElementById('confirmPassword').addEventListener('input', function() {
    const p = document.getElementById('password').value;
    const match = !p || this.value === p;
    document.getElementById('pwMismatch').classList.toggle('d-none', match);
});
document.addEventListener('DOMContentLoaded', function() {
    if (typeof $.fn.select2 !== 'undefined') {
        $('.select2').select2({ theme: 'bootstrap-5' });
    }
});
</script>
