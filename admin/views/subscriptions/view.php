<?php /* views/subscriptions/view.php */
$statusColors = ['active' => 'success', 'expired' => 'warning', 'cancelled' => 'danger'];
$daysLeft = (int)ceil((strtotime($sub['expiry_date']) - time()) / 86400);
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>subscriptions">Subscriptions</a></li>
        <li class="breadcrumb-item active">Subscription #<?= $sub['id'] ?></li>
    </ol>
</nav>

<?php if (!empty($_SESSION['success'])): ?><div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3"><i class="fas fa-check-circle me-2"></i><?= escape($_SESSION['success']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php unset($_SESSION['success']); endif; ?>
<?php if (!empty($_SESSION['error'])):   ?><div class="alert alert-danger  alert-dismissible fade show border-0 shadow-sm mb-3"><i class="fas fa-exclamation-circle me-2"></i><?= escape($_SESSION['error']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php unset($_SESSION['error']); endif; ?>

<?php if ($sub['status'] === 'active' && $daysLeft <= 7 && $daysLeft >= 0): ?>
<div class="alert alert-warning border-0 shadow-sm mb-3">
    <i class="fas fa-exclamation-triangle me-2"></i>
    This subscription expires in <strong><?= $daysLeft ?> day<?= $daysLeft !== 1 ? 's' : '' ?></strong>!
</div>
<?php endif; ?>

<div class="row g-4">
    <!-- LEFT -->
    <div class="col-lg-8">

        <!-- Header card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex align-items-center flex-wrap gap-3">
                    <div class="rounded bg-<?= $statusColors[$sub['status']] ?? 'secondary' ?> d-flex align-items-center justify-content-center text-white flex-shrink-0"
                         style="width:56px;height:56px;font-size:1.4rem;">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h2 class="h4 mb-0"><?= escape($sub['display_name']) ?></h2>
                        <div class="text-muted small"><?= escape($sub['user_email'] ?? $sub['user_phone'] ?? '') ?></div>
                        <div class="d-flex flex-wrap gap-2 mt-1">
                            <span class="badge bg-<?= $statusColors[$sub['status']] ?? 'secondary' ?>"><?= ucfirst($sub['status']) ?></span>
                            <span class="badge bg-info-subtle text-info border border-info-subtle"><?= ucfirst($sub['user_type']) ?></span>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= ucfirst($sub['plan_type']) ?></span>
                        </div>
                    </div>
                    <div class="d-flex gap-2 flex-shrink-0">
                        <a href="<?= BASE_URL ?>subscriptions/edit?id=<?= $sub['id'] ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-edit me-1"></i> Edit
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Details -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold border-bottom">
                <i class="fas fa-info-circle me-2 text-primary"></i> Subscription Details
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <dt class="text-muted small">Plan Type</dt>
                        <dd class="fw-semibold"><?= ucfirst($sub['plan_type']) ?></dd>
                    </div>
                    <div class="col-sm-6">
                        <dt class="text-muted small">Auto Renew</dt>
                        <dd><?= $sub['auto_renew'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' ?></dd>
                    </div>
                    <div class="col-sm-6">
                        <dt class="text-muted small">Start Date</dt>
                        <dd><?= date('d M Y', strtotime($sub['start_date'])) ?></dd>
                    </div>
                    <div class="col-sm-6">
                        <dt class="text-muted small">Expiry Date</dt>
                        <dd class="<?= ($sub['status'] === 'active' && $daysLeft <= 30) ? 'text-warning fw-bold' : '' ?>">
                            <?= date('d M Y', strtotime($sub['expiry_date'])) ?>
                            <?php if ($sub['status'] === 'active'): ?>
                            <span class="text-muted small ms-1">(<?= $daysLeft >= 0 ? "{$daysLeft} days left" : 'Expired' ?>)</span>
                            <?php endif; ?>
                        </dd>
                    </div>
                    <div class="col-sm-6">
                        <dt class="text-muted small">Payment Amount</dt>
                        <dd>₹<?= $sub['payment_amount'] !== null ? number_format($sub['payment_amount'], 2) : '&mdash;' ?></dd>
                    </div>
                    <div class="col-sm-6">
                        <dt class="text-muted small">Payment Method</dt>
                        <dd><?= $sub['payment_method'] ? ucfirst($sub['payment_method']) : '&mdash;' ?></dd>
                    </div>
                    <div class="col-sm-6">
                        <dt class="text-muted small">User Status</dt>
                        <dd><span class="badge bg-<?= $sub['user_status'] === 'active' ? 'success' : 'warning' ?>"><?= ucfirst($sub['user_status']) ?></span></dd>
                    </div>
                    <div class="col-sm-6">
                        <dt class="text-muted small">Created</dt>
                        <dd><?= date('d M Y, h:i A', strtotime($sub['created_at'])) ?></dd>
                    </div>
                </div>
            </div>
        </div>

        <!-- History -->
        <?php if (!empty($history)): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold border-bottom">
                <i class="fas fa-history me-2 text-secondary"></i> Subscription History (<?= count($history) ?> records)
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>#</th><th>Plan</th><th>Start</th><th>Expiry</th><th>Status</th><th>Amount</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($history as $h): ?>
                            <tr class="<?= $h['id'] == $sub['id'] ? 'table-primary' : '' ?>">
                                <td class="text-muted small"><?= $h['id'] ?></td>
                                <td><?= ucfirst($h['plan_type']) ?></td>
                                <td class="text-muted small"><?= date('d M Y', strtotime($h['start_date'])) ?></td>
                                <td class="text-muted small"><?= date('d M Y', strtotime($h['expiry_date'])) ?></td>
                                <td><span class="badge bg-<?= $statusColors[$h['status']] ?? 'secondary' ?>"><?= ucfirst($h['status']) ?></span></td>
                                <td>₹<?= $h['payment_amount'] !== null ? number_format($h['payment_amount'], 0) : '&mdash;' ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- RIGHT: Actions -->
    <div class="col-lg-4">

        <!-- Extend -->
        <?php if ($sub['status'] !== 'cancelled'): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold border-bottom text-success">
                <i class="fas fa-calendar-plus me-2"></i> Extend Subscription
            </div>
            <div class="card-body">
                <form method="POST" action="<?= BASE_URL ?>subscriptions/extend">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="id" value="<?= $sub['id'] ?>">
                    <div class="mb-3">
                        <label class="form-label form-label-sm">New Expiry Date</label>
                        <input type="date" name="new_expiry" class="form-control form-control-sm"
                               min="<?= date('Y-m-d', strtotime($sub['expiry_date'] . ' +1 day')) ?>"
                               value="<?= date('Y-m-d', strtotime($sub['expiry_date'] . ' +1 year')) ?>">
                    </div>
                    <button type="submit" class="btn btn-success btn-sm w-100">Extend</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Cancel -->
        <?php if ($sub['status'] === 'active'): ?>
        <div class="card border-danger shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold border-bottom text-danger">
                <i class="fas fa-ban me-2"></i> Cancel Subscription
            </div>
            <div class="card-body">
                <p class="small text-muted mb-3">Cancelling will immediately update the status. This cannot be undone automatically.</p>
                <form method="POST" action="<?= BASE_URL ?>subscriptions/cancel" id="cancelForm">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="id" value="<?= $sub['id'] ?>">
                    <button type="button" class="btn btn-danger btn-sm w-100"
                            onclick="Swal.fire({ title: 'Cancel Subscription?', text: 'This will mark the subscription as cancelled.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc3545', confirmButtonText: 'Yes, cancel it' }).then(r => r.isConfirmed && document.getElementById('cancelForm').submit())">
                        <i class="fas fa-ban me-1"></i> Cancel Subscription
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Profile link -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <?php if ($sub['merchant_id']): ?>
                <a href="<?= BASE_URL ?>merchants/detail?id=<?= $sub['merchant_id'] ?>" class="btn btn-outline-info btn-sm w-100">
                    <i class="fas fa-store me-1"></i> View Merchant Profile
                </a>
                <?php elseif ($sub['customer_id']): ?>
                <a href="<?= BASE_URL ?>customers/profile?id=<?= $sub['customer_id'] ?>" class="btn btn-outline-secondary btn-sm w-100">
                    <i class="fas fa-user me-1"></i> View Customer Profile
                </a>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>
