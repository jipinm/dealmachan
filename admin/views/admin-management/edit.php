<?php /* views/admin-management/edit.php */ ?>
<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin-management">Admin Management</a></li>
            <li class="breadcrumb-item active">Edit Admin</li>
        </ol>
    </nav>
    <h1 class="h3 mb-0">Edit Admin</h1>
    <p class="text-muted small mb-0">Update admin details for <strong><?= escape($admin['name'] ?: $admin['email']) ?></strong>.</p>
</div>

<?php if ($flash_error): ?><div class="alert alert-danger border-0 shadow-sm"><i class="fas fa-exclamation-circle me-2"></i><?= escape($flash_error) ?></div><?php endif; ?>

<div class="row g-4">
    <div class="col-lg-8">
        <form method="POST" action="<?= BASE_URL ?>admin-management/edit?id=<?= $admin['id'] ?>" id="editAdminForm">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

            <!-- Personal Info -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold border-bottom">
                    <i class="fas fa-user me-2 text-primary"></i> Personal Information
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required value="<?= escape($admin['name']) ?>">
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" required value="<?= escape($admin['email']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="<?= escape($admin['phone'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Password (optional on edit) -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold border-bottom">
                    <i class="fas fa-lock me-2 text-warning"></i> Change Password
                    <span class="badge bg-light text-muted ms-2">Optional — leave blank to keep current</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">New Password</label>
                            <div class="input-group">
                                <input type="password" name="password" id="password" class="form-control" minlength="8" placeholder="Leave blank to keep current">
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
                                <?php foreach ($adminTypes as $v => $l): ?>
                                <option value="<?= $v ?>" <?= $admin['admin_type'] === $v ? 'selected' : '' ?>><?= $l ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" class="form-select">
                                <option value="active"   <?= ($admin['status'] ?? '') === 'active'   ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= ($admin['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                <option value="blocked"  <?= ($admin['status'] ?? '') === 'blocked'  ? 'selected' : '' ?>>Blocked</option>
                            </select>
                        </div>
                        <div class="col-md-6" id="cityWrap">
                            <label class="form-label fw-semibold">Assigned City</label>
                            <select name="city_id" class="form-select select2">
                                <option value="">— All Cities / None —</option>
                                <?php foreach ($cities as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= ($admin['city_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= escape($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Customer Cap</label>
                            <input type="number" name="customer_cap" class="form-control" min="0" placeholder="Leave blank for unlimited" value="<?= escape($admin['customer_cap'] ?? '') ?>">
                            <div class="form-text">Maximum customers this admin can manage.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-2"></i> Save Changes</button>
                <a href="<?= BASE_URL ?>admin-management" class="btn btn-light px-4">Cancel</a>
            </div>
        </form>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Admin Meta -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold border-bottom"><i class="fas fa-info-circle me-2 text-info"></i> Admin Info</div>
            <div class="card-body small">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Admin ID</span>
                    <strong>#<?= $admin['id'] ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Created</span>
                    <span><?= formatDate($admin['created_at']) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Last Login</span>
                    <span><?= $admin['last_login'] ? formatDateTime($admin['last_login']) : 'Never' ?></span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Current Status</span>
                    <span class="badge bg-<?= ($admin['status'] ?? '') === 'active' ? 'success' : 'danger' ?>"><?= ucfirst($admin['status'] ?? 'inactive') ?></span>
                </div>
            </div>
        </div>

        <!-- Danger Zone -->
        <div class="card border-danger border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold border-bottom text-danger"><i class="fas fa-exclamation-triangle me-2"></i> Danger Zone</div>
            <div class="card-body">
                <p class="small text-muted mb-3">Permanently delete this admin account and all associated login credentials.</p>
                <form method="POST" action="<?= BASE_URL ?>admin-management/delete" id="deleteForm">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="id" value="<?= $admin['id'] ?>">
                    <button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="confirmDelete('<?= escape($admin['name'] ?: $admin['email']) ?>')">
                        <i class="fas fa-trash me-2"></i> Delete This Admin
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function togglePw(id) { const f = document.getElementById(id); f.type = f.type === 'password' ? 'text' : 'password'; }
function toggleCityField() {
    const t = document.getElementById('adminType').value;
    document.getElementById('cityWrap').style.display = (t === 'super_admin') ? 'none' : '';
}
function confirmDelete(name) {
    Swal.fire({ title:'Delete Admin?', html:`Delete <b>${name}</b>? This cannot be undone.`, icon:'warning', showCancelButton:true, confirmButtonColor:'#dc3545', confirmButtonText:'Delete' })
        .then(r => { if (r.isConfirmed) document.getElementById('deleteForm').submit(); });
}
document.getElementById('editAdminForm').addEventListener('submit', function(e) {
    const p = document.getElementById('password').value;
    const c = document.getElementById('confirmPassword').value;
    if (p && p !== c) { document.getElementById('pwMismatch').classList.remove('d-none'); e.preventDefault(); }
});
document.getElementById('confirmPassword').addEventListener('input', function() {
    const p = document.getElementById('password').value;
    document.getElementById('pwMismatch').classList.toggle('d-none', !p || this.value === p);
});
document.addEventListener('DOMContentLoaded', function() {
    if (typeof $.fn.select2 !== 'undefined') { $('.select2').select2({ theme: 'bootstrap-5' }); }
    toggleCityField();
});
</script>
