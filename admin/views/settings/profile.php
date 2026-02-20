<?php
$admin_type_labels = [
    'super_admin'    => 'Super Admin',
    'city_admin'     => 'City Admin',
    'sales_admin'    => 'Sales Admin',
    'promoter_admin' => 'Promoter Admin',
    'partner_admin'  => 'Partner Admin',
    'club_admin'     => 'Club Admin',
];
$admin_type_label = $admin_type_labels[$admin['admin_type'] ?? ''] ?? ucfirst($admin['admin_type'] ?? '');
?>

<div class="content-area p-4">

    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="mb-1"><i class="fas fa-user-cog me-2 text-primary"></i> Profile Settings</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item active">Profile Settings</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Flash Messages -->
    <?php if (!empty($flash_success)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i><?= escape($flash_success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <?php if (!empty($flash_error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?= escape($flash_error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row g-4">

        <!-- Avatar / Info Card -->
        <div class="col-md-4 col-lg-3">
            <div class="card shadow-sm text-center p-4">
                <div class="mx-auto mb-3" style="width:80px;height:80px;background:linear-gradient(135deg,#667eea,#764ba2);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                    <span class="text-white fw-bold" style="font-size:2rem;">
                        <?= strtoupper(substr($admin['name'] ?? 'A', 0, 1)) ?>
                    </span>
                </div>
                <h5 class="mb-1 fw-semibold"><?= escape($admin['name'] ?? '') ?></h5>
                <span class="badge bg-primary mb-2"><?= escape($admin_type_label) ?></span>
                <p class="text-muted small mb-1"><i class="fas fa-envelope me-1"></i><?= escape($admin['email'] ?? '') ?></p>
                <?php if (!empty($admin['phone'])): ?>
                <p class="text-muted small mb-1"><i class="fas fa-phone me-1"></i><?= escape($admin['phone']) ?></p>
                <?php endif; ?>
                <?php if (!empty($admin['city_name'])): ?>
                <p class="text-muted small mb-0"><i class="fas fa-map-marker-alt me-1"></i><?= escape($admin['city_name']) ?></p>
                <?php endif; ?>
                <hr class="my-3">
                <p class="text-muted small mb-1">
                    <i class="fas fa-calendar-alt me-1"></i>
                    Joined <?= !empty($admin['user_created_at']) ? date('M Y', strtotime($admin['user_created_at'])) : 'N/A' ?>
                </p>
                <p class="text-muted small mb-0">
                    <i class="fas fa-clock me-1"></i>
                    Last login: <?= !empty($admin['last_login']) ? date('d M Y, h:i A', strtotime($admin['last_login'])) : 'N/A' ?>
                </p>
            </div>
        </div>

        <!-- Edit Form -->
        <div class="col-md-8 col-lg-9">
            <form method="POST" action="<?= BASE_URL ?>settings/profile" autocomplete="off">

                <!-- Personal Information -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-semibold"><i class="fas fa-id-card me-2 text-primary"></i>Personal Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name"
                                       value="<?= escape($admin['name'] ?? '') ?>" required maxlength="100">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Role</label>
                                <input type="text" class="form-control" value="<?= escape($admin_type_label) ?>" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="email"
                                       value="<?= escape($admin['email'] ?? '') ?>" required maxlength="150">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Phone Number</label>
                                <input type="text" class="form-control" name="phone"
                                       value="<?= escape($admin['phone'] ?? '') ?>" maxlength="20">
                            </div>
                            <?php if (!empty($admin['city_name'])): ?>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Assigned City</label>
                                <input type="text" class="form-control" value="<?= escape($admin['city_name']) ?>" disabled>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Change Password -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-semibold"><i class="fas fa-lock me-2 text-warning"></i>Change Password <small class="text-muted fw-normal">(leave blank to keep current password)</small></h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Current Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" name="current_password"
                                           id="currentPassword" autocomplete="current-password">
                                    <button class="btn btn-outline-secondary password-toggle" type="button"
                                            onclick="togglePass('currentPassword', this)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">New Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" name="new_password"
                                           id="newPassword" autocomplete="new-password" minlength="8">
                                    <button class="btn btn-outline-secondary password-toggle" type="button"
                                            onclick="togglePass('newPassword', this)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">Minimum 8 characters.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Confirm New Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" name="confirm_password"
                                           id="confirmPassword" autocomplete="new-password">
                                    <button class="btn btn-outline-secondary password-toggle" type="button"
                                            onclick="togglePass('confirmPassword', this)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fas fa-save me-2"></i>Save Changes
                    </button>
                    <a href="<?= BASE_URL ?>dashboard" class="btn btn-outline-secondary px-4">Cancel</a>
                </div>

            </form>
        </div>

    </div>
</div>

<script>
function togglePass(fieldId, btn) {
    const field = document.getElementById(fieldId);
    const icon  = btn.querySelector('i');
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>
