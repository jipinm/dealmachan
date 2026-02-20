<div class="content-area p-4">

    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="mb-1"><i class="fas fa-sliders-h me-2 text-primary"></i> Preferences</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>settings/profile">Profile Settings</a></li>
                    <li class="breadcrumb-item active">Preferences</li>
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
        <div class="col-lg-8">

            <!-- Notification Preferences -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-semibold"><i class="fas fa-bell me-2 text-warning"></i>Notification Preferences</h6>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <div class="fw-semibold">New Customer Registrations</div>
                                <small class="text-muted">Get notified when a new customer signs up</small>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" checked disabled>
                            </div>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <div class="fw-semibold">Merchant Approval Requests</div>
                                <small class="text-muted">Get notified when merchants submit for approval</small>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" checked disabled>
                            </div>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <div class="fw-semibold">Coupon Redemptions</div>
                                <small class="text-muted">Get notified on coupon redemption activity</small>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" disabled>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Display Preferences -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-semibold"><i class="fas fa-desktop me-2 text-info"></i>Display Preferences</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Dashboard Default Period</label>
                            <select class="form-select" disabled>
                                <option selected>Last 30 Days</option>
                                <option>Last 7 Days</option>
                                <option>Last 90 Days</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Records Per Page</label>
                            <select class="form-select" disabled>
                                <option>25</option>
                                <option selected>50</option>
                                <option>100</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="alert alert-info d-flex align-items-center">
                <i class="fas fa-info-circle me-3 fs-5"></i>
                <div>Preference customisation is coming in a future release. These settings are currently read-only.</div>
            </div>

            <a href="<?= BASE_URL ?>settings/profile" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Profile
            </a>

        </div>
    </div>
</div>
