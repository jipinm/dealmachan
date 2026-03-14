<?php /* views/subscriptions/add.php */ ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-0">Create Subscription</h4>
        <small class="text-muted"><a href="<?= BASE_URL ?>subscriptions">Subscriptions</a> / New</small>
    </div>
    <a href="<?= BASE_URL ?>subscriptions" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
</div>

<?php if (!empty($_SESSION['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3">
    <i class="fas fa-exclamation-circle me-2"></i><?= escape($_SESSION['error']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['error']); endif; ?>

<form method="POST" action="<?= BASE_URL ?>subscriptions/add">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold"><i class="fas fa-user me-2 text-info"></i> Subscriber</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label">User Type <span class="text-danger">*</span></label>
                            <select name="user_type" class="form-select" required>
                                <option value="">&mdash; Select &mdash;</option>
                                <option value="merchant">Merchant</option>
                                <option value="customer">Customer</option>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">User ID <span class="text-danger">*</span></label>
                            <input type="number" name="user_id" class="form-control" required min="1"
                                   placeholder="User ID from users table">
                            <div class="form-text">Enter the user_id from the users table.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold"><i class="fas fa-calendar me-2 text-primary"></i> Plan & Validity</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-4">
                            <label class="form-label">Plan Type <span class="text-danger">*</span></label>
                            <select name="plan_type" class="form-select" required>
                                <option value="">&mdash; Select &mdash;</option>
                                <option value="basic">Basic</option>
                                <option value="standard">Standard</option>
                                <option value="premium">Premium</option>
                            </select>
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label">Expiry Date <span class="text-danger">*</span></label>
                            <input type="date" name="expiry_date" class="form-control" required value="<?= date('Y-m-d', strtotime('+1 year')) ?>">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Payment Amount (₹)</label>
                            <input type="number" name="payment_amount" class="form-control" step="0.01" min="0" placeholder="0.00">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Payment Method</label>
                            <select name="payment_method" class="form-select">
                                <option value="">&mdash; None / Unknown &mdash;</option>
                                <?php foreach (['cash', 'card', 'upi', 'wallet', 'other'] as $m): ?>
                                <option value="<?= $m ?>"><?= ucfirst($m) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold"><i class="fas fa-sliders-h me-2"></i> Settings</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active">Active</option>
                            <option value="expired">Expired</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="auto_renew" id="auto_renew" value="1">
                        <label class="form-check-label" for="auto_renew">Auto Renew</label>
                    </div>
                </div>
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Create Subscription</button>
                <a href="<?= BASE_URL ?>subscriptions" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </div>
    </div>
</form>
