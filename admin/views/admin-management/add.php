<?php /* views/admin-management/add.php */ ?>
<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin-management">Admin Management</a></li>
            <li class="breadcrumb-item active">Add Admin</li>
        </ol>
    </nav>
    <h1 class="h3 mb-0">Add New Admin</h1>
    <p class="text-muted small mb-0">Create a new admin account with access credentials and role assignment.</p>
</div>

<?php if ($flash_error): ?><div class="alert alert-danger border-0 shadow-sm"><i class="fas fa-exclamation-circle me-2"></i><?= escape($flash_error) ?></div><?php endif; ?>

<div class="row g-4">
    <div class="col-lg-8">
        <form method="POST" action="<?= BASE_URL ?>admin-management/add" id="addAdminForm">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

            <!-- Personal Info -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold border-bottom">
                    <i class="fas fa-user me-2 text-primary"></i> Personal Information
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. John Doe" value="<?= escape($_POST['name'] ?? '') ?>">
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" required placeholder="admin@dealmachan.com" value="<?= escape($_POST['email'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Phone Number</label>
                            <input type="text" name="phone" class="form-control" placeholder="+91 98765 43210" value="<?= escape($_POST['phone'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Password -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold border-bottom">
                    <i class="fas fa-lock me-2 text-warning"></i> Login Credentials
                </div>
                <div class="card-body">
                    <div class="row g-3">
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

            <!-- Role & Access -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold border-bottom">
                    <i class="fas fa-shield-alt me-2 text-success"></i> Role & Access
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Admin Type <span class="text-danger">*</span></label>
                            <select name="admin_type" id="adminType" class="form-select select2" required onchange="toggleCityField()">
                                <option value="">&mdash; Select Type &mdash;</option>
                                <?php foreach ($adminTypes as $v => $l): ?>
                                <option value="<?= $v ?>" <?= ($_POST['admin_type'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" class="form-select">
                                <option value="active" <?= ($_POST['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= ($_POST['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-6" id="cityWrap">
                            <label class="form-label fw-semibold">Assigned City</label>
                            <select name="city_id" class="form-select select2">
                                <option value="">&mdash; All Cities / None &mdash;</option>
                                <?php foreach ($cities as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= ($_POST['city_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= escape($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Customer Cap</label>
                            <input type="number" name="customer_cap" class="form-control" min="0" placeholder="Leave blank for unlimited" value="<?= escape($_POST['customer_cap'] ?? '') ?>">
                            <div class="form-text">Maximum customers this admin can manage.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-2"></i> Create Admin</button>
                <a href="<?= BASE_URL ?>admin-management" class="btn btn-light px-4">Cancel</a>
            </div>
        </form>
    </div>

    <!-- Sidebar Info -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold border-bottom"><i class="fas fa-info-circle me-2 text-info"></i> Admin Type Guide</div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item"><span class="badge bg-danger me-2">Super Admin</span> Full access to all features</li>
                    <li class="list-group-item"><span class="badge bg-primary me-2">City Admin</span> Manage one city's operations</li>
                    <li class="list-group-item"><span class="badge bg-success me-2">Sales Admin</span> Manage merchants & coupons</li>
                    <li class="list-group-item"><span class="badge bg-warning text-dark me-2">Promoter Admin</span> Manage contests & surveys</li>
                    <li class="list-group-item"><span class="badge bg-info me-2">Partner Admin</span> Partner portal access</li>
                    <li class="list-group-item"><span class="badge bg-secondary me-2">Club Admin</span> Club & membership management</li>
                </ul>
            </div>
        </div>
        <div class="alert alert-warning border-0 small">
            <i class="fas fa-exclamation-triangle me-1"></i>
            Password must be at least <strong>8 characters</strong>. Share credentials securely &mdash; they cannot be recovered.
        </div>
    </div>
</div>

<script>
function togglePw(id) { const f = document.getElementById(id); f.type = f.type === 'password' ? 'text' : 'password'; }
function toggleCityField() {
    const t = document.getElementById('adminType').value;
    const wrap = document.getElementById('cityWrap');
    wrap.style.display = (t === 'super_admin') ? 'none' : '';
}
document.getElementById('addAdminForm').addEventListener('submit', function(e) {
    const p = document.getElementById('password').value;
    const c = document.getElementById('confirmPassword').value;
    if (p !== c) { document.getElementById('pwMismatch').classList.remove('d-none'); e.preventDefault(); }
});
document.getElementById('confirmPassword').addEventListener('input', function() {
    const match = this.value === document.getElementById('password').value;
    document.getElementById('pwMismatch').classList.toggle('d-none', match);
});
document.addEventListener('DOMContentLoaded', function() {
    if (typeof $.fn.select2 !== 'undefined') { $('.select2').select2({ theme: 'bootstrap-5' }); }
    toggleCityField();
});
</script>
