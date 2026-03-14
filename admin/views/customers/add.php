<?php /* views/customers/add.php */ ?>
<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>customers">Customer Management</a></li>
            <li class="breadcrumb-item active">Add Customer</li>
        </ol>
    </nav>
    <h1 class="h3 mb-0">Add New Customer</h1>
    <p class="text-muted small mb-0">Create a new customer account manually.</p>
</div>

<?php if ($flash_error): ?><div class="alert alert-danger border-0 shadow-sm"><i class="fas fa-exclamation-circle me-2"></i><?= escape($flash_error) ?></div><?php endif; ?>

<form method="POST" action="<?= BASE_URL ?>customers/add" id="addCustomerForm">
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
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Rahul Sharma" value="<?= escape($_POST['name'] ?? '') ?>">
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Date of Birth</label>
                            <input type="date" name="date_of_birth" class="form-control" value="<?= escape($_POST['date_of_birth'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Gender</label>
                            <select name="gender" class="form-select">
                                <option value="">&mdash; Select &mdash;</option>
                                <option value="male"   <?= ($_POST['gender'] ?? '') === 'male'   ? 'selected' : '' ?>>Male</option>
                                <option value="female" <?= ($_POST['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                                <option value="other"  <?= ($_POST['gender'] ?? '') === 'other'  ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Profession</label>
                            <select name="profession_id" class="form-select select2">
                                <option value="">&mdash; Select Profession &mdash;</option>
                                <?php foreach ($professions as $prof): ?>
                                <option value="<?= $prof['id'] ?>" <?= ($_POST['profession_id'] ?? '') == $prof['id'] ? 'selected' : '' ?>>
                                    <?= escape($prof['profession_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact & Login -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold border-bottom">
                    <i class="fas fa-address-card me-2 text-info"></i> Contact & Login Credentials
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email Address</label>
                            <input type="email" name="email" class="form-control" placeholder="customer@example.com" value="<?= escape($_POST['email'] ?? '') ?>">
                            <div class="form-text">Required if phone is not provided.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Phone Number</label>
                            <input type="text" name="phone" class="form-control" placeholder="+91 98765 43210" value="<?= escape($_POST['phone'] ?? '') ?>">
                            <div class="form-text">Required if email is not provided.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="password" id="password" class="form-control" required minlength="8" placeholder="Min 8 characters">
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePw('password')"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" id="confirmPassword" class="form-control" required placeholder="Re-enter password">
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
                                <option value="standard"  <?= ($_POST['customer_type'] ?? 'standard') === 'standard'  ? 'selected' : '' ?>>Standard</option>
                                <option value="premium"   <?= ($_POST['customer_type'] ?? '') === 'premium'   ? 'selected' : '' ?>>Premium</option>
                                <option value="dealmaker" <?= ($_POST['customer_type'] ?? '') === 'dealmaker' ? 'selected' : '' ?>>DealMaker</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Registration Type</label>
                            <select name="registration_type" class="form-select">
                                <option value="admin_registration" selected>Admin Registration</option>
                                <option value="self_registration">Self Registration</option>
                                <option value="merchant_app">Merchant App</option>
                                <option value="preprinted_card">Preprinted Card</option>
                                <option value="auto_profile">Auto Profile</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" class="form-select">
                                <option value="active"   <?= ($_POST['status'] ?? 'active') === 'active'   ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= ($_POST['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                <option value="pending"  <?= ($_POST['status'] ?? '') === 'pending'  ? 'selected' : '' ?>>Pending</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-2"></i> Create Customer</button>
                <a href="<?= BASE_URL ?>customers" class="btn btn-light px-4">Cancel</a>
            </div>
        </div>

        <!-- Right Sidebar -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold border-bottom"><i class="fas fa-info-circle me-2 text-info"></i> Customer Types</div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item"><span class="badge bg-primary me-2">Standard</span> Regular registered customer</li>
                        <li class="list-group-item"><span class="badge bg-warning text-dark me-2">Premium</span> Paid subscription member</li>
                        <li class="list-group-item"><span class="badge bg-success me-2">DealMaker</span> Ambassador / referral specialist</li>
                    </ul>
                </div>
            </div>
            <div class="alert alert-info border-0 small">
                <i class="fas fa-lightbulb me-1"></i>
                A <strong>referral code</strong> will be automatically generated for this customer after creation.
            </div>
            <div class="alert alert-warning border-0 small">
                <i class="fas fa-lock me-1"></i>
                Password must be at least <strong>8 characters</strong>. Share credentials securely.
            </div>
        </div>
    </div>
</form>

<script>
function togglePw(id) {
    const f = document.getElementById(id);
    f.type = f.type === 'password' ? 'text' : 'password';
}
document.getElementById('addCustomerForm').addEventListener('submit', function(e) {
    const p = document.getElementById('password').value;
    const c = document.getElementById('confirmPassword').value;
    if (p !== c) {
        document.getElementById('pwMismatch').classList.remove('d-none');
        e.preventDefault();
    }
});
document.getElementById('confirmPassword').addEventListener('input', function() {
    const match = this.value === document.getElementById('password').value;
    document.getElementById('pwMismatch').classList.toggle('d-none', match);
});
document.addEventListener('DOMContentLoaded', function() {
    if (typeof $.fn.select2 !== 'undefined') {
        $('.select2').select2({ theme: 'bootstrap-5' });
    }
});
</script>
