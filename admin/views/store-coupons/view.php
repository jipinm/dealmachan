<?php /* views/store-coupons/view.php */
$statusColors = ['active' => 'success', 'inactive' => 'secondary', 'expired' => 'warning'];
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>store-coupons">Store Coupons</a></li>
        <li class="breadcrumb-item active"><?= escape($storeCoupon['coupon_code']) ?></li>
    </ol>
</nav>

<?php if (!empty($_SESSION['success'])): ?><div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3"><i class="fas fa-check-circle me-2"></i><?= escape($_SESSION['success']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php unset($_SESSION['success']); endif; ?>
<?php if (!empty($_SESSION['error'])):   ?><div class="alert alert-danger  alert-dismissible fade show border-0 shadow-sm mb-3"><i class="fas fa-exclamation-circle me-2"></i><?= escape($_SESSION['error']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php unset($_SESSION['error']); endif; ?>

<div class="row g-4">
    <!-- LEFT: Main Info -->
    <div class="col-lg-8">

        <!-- Header card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <div class="rounded bg-primary d-flex align-items-center justify-content-center text-white fw-bold flex-shrink-0"
                         style="width:64px;height:64px;font-size:1.4rem;">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h2 class="h4 mb-1 font-monospace"><?= escape($storeCoupon['coupon_code']) ?></h2>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge bg-<?= $statusColors[$storeCoupon['status']] ?? 'secondary' ?>">
                                <?= ucfirst($storeCoupon['status']) ?>
                            </span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                <?php if ($storeCoupon['discount_type'] === 'percentage'): ?>
                                    <?= number_format($storeCoupon['discount_value'], 0) ?>% OFF
                                <?php else: ?>
                                    ₹<?= number_format($storeCoupon['discount_value'], 0) ?> OFF
                                <?php endif; ?>
                            </span>
                            <?php if ($storeCoupon['is_gifted']): ?>
                            <span class="badge bg-info-subtle text-info border border-info-subtle">
                                <i class="fas fa-gift me-1"></i> Gifted
                            </span>
                            <?php endif; ?>
                            <?php if ($storeCoupon['is_redeemed']): ?>
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                <i class="fas fa-check-circle me-1"></i> Redeemed
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="d-flex gap-2 flex-shrink-0">
                        <form method="POST" action="<?= BASE_URL ?>store-coupons/toggle" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            <input type="hidden" name="id" value="<?= $storeCoupon['id'] ?>">
                            <input type="hidden" name="redirect" value="store-coupons/detail?id=<?= $storeCoupon['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-<?= $storeCoupon['status'] === 'active' ? 'warning' : 'success' ?>">
                                <i class="fas fa-<?= $storeCoupon['status'] === 'active' ? 'pause' : 'play' ?> me-1"></i>
                                <?= $storeCoupon['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Details Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold border-bottom">
                <i class="fas fa-info-circle me-2 text-primary"></i> Coupon Details
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">Merchant</div>
                        <div>
                            <a href="<?= BASE_URL ?>merchants/profile?id=<?= $storeCoupon['merchant_id'] ?>" class="text-decoration-none">
                                <?= escape($storeCoupon['merchant_name']) ?>
                            </a>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">Store</div>
                        <div><?= escape($storeCoupon['store_name']) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">Discount Type</div>
                        <div><?= ucfirst($storeCoupon['discount_type']) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">Discount Value</div>
                        <div class="fw-semibold text-success fs-5">
                            <?php if ($storeCoupon['discount_type'] === 'percentage'): ?>
                                <?= number_format($storeCoupon['discount_value'], 0) ?>%
                            <?php else: ?>
                                ₹<?= number_format($storeCoupon['discount_value'], 0) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">Valid From</div>
                        <div><?= $storeCoupon['valid_from'] ? formatDateTime($storeCoupon['valid_from']) : '<span class="text-muted">&mdash;</span>' ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">Valid Until</div>
                        <div><?= $storeCoupon['valid_until'] ? formatDateTime($storeCoupon['valid_until']) : '<span class="text-muted">&mdash;</span>' ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gift & Redemption Info -->
        <?php if ($storeCoupon['is_gifted'] || $storeCoupon['is_redeemed']): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold border-bottom">
                <i class="fas fa-exchange-alt me-2 text-info"></i> Gift & Redemption
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <?php if ($storeCoupon['is_gifted']): ?>
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">Gifted To</div>
                        <div>
                            <?php if ($storeCoupon['gifted_to_customer_id']): ?>
                                <a href="<?= BASE_URL ?>customers/profile?id=<?= $storeCoupon['gifted_to_customer_id'] ?>" class="text-decoration-none">
                                    <?= escape($storeCoupon['gifted_to_name'] ?? 'Customer #' . $storeCoupon['gifted_to_customer_id']) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">Unknown</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">Gifted At</div>
                        <div><?= $storeCoupon['gifted_at'] ? formatDateTime($storeCoupon['gifted_at']) : '<span class="text-muted">&mdash;</span>' ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($storeCoupon['is_redeemed']): ?>
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">Redeemed</div>
                        <div><span class="badge bg-success">Yes</span></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">Redeemed At</div>
                        <div><?= $storeCoupon['redeemed_at'] ? formatDateTime($storeCoupon['redeemed_at']) : '<span class="text-muted">&mdash;</span>' ?></div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php if ($storeCoupon['is_gifted'] && !$storeCoupon['is_redeemed']): ?>
                <hr>
                <button class="btn btn-sm btn-warning" onclick="revokeGift(<?= $storeCoupon['id'] ?>)">
                    <i class="bi bi-x-circle me-1"></i> Revoke Gift Assignment
                </button>
                <small class="text-muted ms-2">Removes this coupon from the customer's account (since it hasn't been used yet).</small>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- RIGHT: Sidebar -->
    <div class="col-lg-4">

        <!-- Timestamps -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold border-bottom">
                <i class="fas fa-clock me-2 text-secondary"></i> Timestamps
            </div>
            <div class="card-body small">
                <div class="mb-2"><span class="text-muted">Created:</span> <?= formatDateTime($storeCoupon['created_at']) ?></div>
                <?php if ($storeCoupon['updated_at']): ?>
                <div><span class="text-muted">Updated:</span> <?= formatDateTime($storeCoupon['updated_at']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Danger Zone -->
        <div class="card border-danger border-opacity-25 shadow-sm mb-4">
            <div class="card-header bg-danger-subtle text-danger fw-semibold border-bottom border-danger border-opacity-25">
                <i class="fas fa-exclamation-triangle me-2"></i> Danger Zone
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">Permanently delete this store coupon. This cannot be undone.</p>
                <button class="btn btn-danger btn-sm w-100"
                        onclick="confirmDelete(<?= $storeCoupon['id'] ?>, '<?= escape($storeCoupon['coupon_code']) ?>')">
                    <i class="fas fa-trash me-2"></i> Delete Store Coupon
                </button>
            </div>
        </div>

    </div>
</div>

<!-- Delete Form -->
<form method="POST" action="<?= BASE_URL ?>store-coupons/delete" id="deleteForm">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
    <input type="hidden" name="id" value="<?= $storeCoupon['id'] ?>">
</form>

<script>
function confirmDelete(id, code) {
    Swal.fire({
        title: 'Delete Store Coupon?',
        html: `Permanently delete coupon <b>${code}</b>?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Delete'
    }).then(r => {
        if (r.isConfirmed) {
            document.getElementById('deleteForm').submit();
        }
    });
}
function revokeGift(id) {
    Swal.fire({
        title: 'Revoke Gift?',
        text: 'This will remove the coupon from the customer\'s account. They will no longer be able to use it.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d97706',
        confirmButtonText: 'Yes, Revoke'
    }).then(r => {
        if (!r.isConfirmed) return;
        fetch('<?= BASE_URL ?>store-coupons/revoke', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + id
        }).then(r => r.json()).then(d => {
            if (d.success) {
                Swal.fire('Revoked', 'Gift assignment removed.', 'success').then(() => location.reload());
            } else {
                Swal.fire('Error', d.error || 'Failed.', 'error');
            }
        });
    });
}
</script>
