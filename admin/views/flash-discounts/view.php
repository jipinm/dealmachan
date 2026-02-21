<?php /* views/flash-discounts/view.php */
$statusColors = ['active' => 'success', 'inactive' => 'secondary', 'expired' => 'warning'];
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>flash-discounts">Flash Discounts</a></li>
        <li class="breadcrumb-item active"><?= escape($flashDiscount['title']) ?></li>
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
                    <div class="rounded bg-warning d-flex align-items-center justify-content-center text-white fw-bold flex-shrink-0"
                         style="width:64px;height:64px;font-size:1.8rem;">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h2 class="h4 mb-1"><?= escape($flashDiscount['title']) ?></h2>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge bg-<?= $statusColors[$flashDiscount['status']] ?? 'secondary' ?>">
                                <?= ucfirst($flashDiscount['status']) ?>
                            </span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                <?= number_format($flashDiscount['discount_percentage'], 0) ?>% OFF
                            </span>
                        </div>
                    </div>
                    <div class="d-flex gap-2 flex-shrink-0">
                        <form method="POST" action="<?= BASE_URL ?>flash-discounts/toggle" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            <input type="hidden" name="id" value="<?= $flashDiscount['id'] ?>">
                            <input type="hidden" name="redirect" value="flash-discounts/detail?id=<?= $flashDiscount['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-<?= $flashDiscount['status'] === 'active' ? 'warning' : 'success' ?>">
                                <i class="fas fa-<?= $flashDiscount['status'] === 'active' ? 'pause' : 'play' ?> me-1"></i>
                                <?= $flashDiscount['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Details Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold border-bottom">
                <i class="fas fa-info-circle me-2 text-primary"></i> Flash Discount Details
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">Merchant</div>
                        <div>
                            <a href="<?= BASE_URL ?>merchants/profile?id=<?= $flashDiscount['merchant_id'] ?>" class="text-decoration-none">
                                <?= escape($flashDiscount['merchant_name']) ?>
                            </a>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">Store</div>
                        <div><?= $flashDiscount['store_name'] ? escape($flashDiscount['store_name']) : '<span class="text-muted">All Stores</span>' ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">Discount</div>
                        <div class="fw-semibold text-success fs-5"><?= number_format($flashDiscount['discount_percentage'], 0) ?>%</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">Redemptions</div>
                        <div class="fw-semibold">
                            <?= (int)$flashDiscount['current_redemptions'] ?>
                            <?php if ($flashDiscount['max_redemptions']): ?>
                                <span class="text-muted fw-normal">/ <?= (int)$flashDiscount['max_redemptions'] ?> max</span>
                            <?php else: ?>
                                <span class="text-muted fw-normal">(unlimited)</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">Valid From</div>
                        <div><?= $flashDiscount['valid_from'] ? formatDateTime($flashDiscount['valid_from']) : '<span class="text-muted">—</span>' ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">Valid Until</div>
                        <div><?= $flashDiscount['valid_until'] ? formatDateTime($flashDiscount['valid_until']) : '<span class="text-muted">—</span>' ?></div>
                    </div>
                    <div class="col-12">
                        <div class="text-muted small mb-1">Description</div>
                        <div><?= $flashDiscount['description'] ? nl2br(escape($flashDiscount['description'])) : '<span class="text-muted">No description</span>' ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Validity Progress -->
        <?php if ($flashDiscount['max_redemptions']): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold border-bottom">
                <i class="fas fa-chart-pie me-2 text-info"></i> Redemption Progress
            </div>
            <div class="card-body">
                <?php
                $pct = min(100, round(($flashDiscount['current_redemptions'] / max(1, $flashDiscount['max_redemptions'])) * 100));
                $barColor = $pct >= 90 ? 'danger' : ($pct >= 60 ? 'warning' : 'success');
                ?>
                <div class="d-flex justify-content-between small mb-1">
                    <span><?= $flashDiscount['current_redemptions'] ?> redeemed</span>
                    <span><?= $flashDiscount['max_redemptions'] ?> max</span>
                </div>
                <div class="progress" style="height: 24px;">
                    <div class="progress-bar bg-<?= $barColor ?>" role="progressbar"
                         style="width: <?= $pct ?>%;" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100">
                        <?= $pct ?>%
                    </div>
                </div>
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
                <div class="mb-2"><span class="text-muted">Created:</span> <?= formatDateTime($flashDiscount['created_at']) ?></div>
                <?php if ($flashDiscount['updated_at']): ?>
                <div><span class="text-muted">Updated:</span> <?= formatDateTime($flashDiscount['updated_at']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Danger Zone -->
        <div class="card border-danger border-opacity-25 shadow-sm mb-4">
            <div class="card-header bg-danger-subtle text-danger fw-semibold border-bottom border-danger border-opacity-25">
                <i class="fas fa-exclamation-triangle me-2"></i> Danger Zone
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">Permanently delete this flash discount. This cannot be undone.</p>
                <button class="btn btn-danger btn-sm w-100"
                        onclick="confirmDelete(<?= $flashDiscount['id'] ?>, '<?= escape($flashDiscount['title']) ?>')">
                    <i class="fas fa-trash me-2"></i> Delete Flash Discount
                </button>
            </div>
        </div>

    </div>
</div>

<!-- Delete Form -->
<form method="POST" action="<?= BASE_URL ?>flash-discounts/delete" id="deleteForm">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
    <input type="hidden" name="id" value="<?= $flashDiscount['id'] ?>">
</form>

<script>
function confirmDelete(id, title) {
    Swal.fire({
        title: 'Delete Flash Discount?',
        html: `Permanently delete <b>${title}</b>?`,
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
</script>
