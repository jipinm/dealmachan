<?php /* views/store-coupons/allotment-requests.php */ ?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>store-coupons">Store Coupons</a></li>
        <li class="breadcrumb-item active">Allotment Requests</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="mb-0">Pending Allotment Requests</h4>
        <small class="text-muted">Approve or reject merchant bulk assignment requests</small>
    </div>
    <a href="<?= BASE_URL ?>store-coupons" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i> Back to Store Coupons
    </a>
</div>

<?php if (!empty($_SESSION['success'])): ?>
<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3">
    <i class="fas fa-check-circle me-2"></i><?= escape($_SESSION['success']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['success']); endif; ?>

<?php if (!empty($_SESSION['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3">
    <i class="fas fa-exclamation-circle me-2"></i><?= escape($_SESSION['error']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['error']); endif; ?>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($requests)): ?>
            <div class="text-center text-muted py-5">
                <i class="fas fa-inbox fa-3x mb-3 opacity-25"></i>
                <p class="mb-0">No pending requests.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Merchant</th>
                            <th>Store</th>
                            <th>Coupon</th>
                            <th class="text-center">Qty</th>
                            <th>Requested On</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req): ?>
                        <tr>
                            <td class="text-muted small">#<?= (int)$req['id'] ?></td>
                            <td>
                                <a href="<?= BASE_URL ?>merchants/profile?id=<?= (int)$req['merchant_id'] ?>" class="text-decoration-none small">
                                    <?= escape($req['merchant_name']) ?>
                                </a>
                            </td>
                            <td class="small"><?= escape($req['store_name']) ?></td>
                            <td>
                                <div class="small fw-semibold"><?= escape($req['coupon_title'] ?: $req['coupon_code']) ?></div>
                                <div class="text-muted small font-monospace"><?= escape($req['coupon_code']) ?></div>
                            </td>
                            <td class="text-center fw-semibold"><?= (int)$req['quantity'] ?></td>
                            <td class="small"><?= formatDateTime($req['created_at']) ?></td>
                            <td class="text-center">
                                <div class="d-inline-flex gap-2">
                                    <form method="POST" action="<?= BASE_URL ?>store-coupons/approve-allotment" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                        <input type="hidden" name="id" value="<?= (int)$req['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-success">
                                            <i class="fas fa-check me-1"></i> Approve
                                        </button>
                                    </form>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="openRejectModal(<?= (int)$req['id'] ?>)">
                                        <i class="fas fa-times me-1"></i> Reject
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>store-coupons/reject-allotment">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="id" id="rejectRequestId" value="">
                <div class="modal-header">
                    <h5 class="modal-title">Reject Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Review Note (optional)</label>
                    <textarea name="review_note" class="form-control" rows="3" placeholder="Reason for rejection..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openRejectModal(id) {
    document.getElementById('rejectRequestId').value = String(id);
    const modal = new bootstrap.Modal(document.getElementById('rejectModal'));
    modal.show();
}
</script>
