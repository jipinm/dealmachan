<?php /* views/gift-coupons/view.php */
$statusBadge = [
    'pending'  => ['bg-warning text-dark', 'Pending'],
    'accepted' => ['bg-success',           'Accepted'],
    'rejected' => ['bg-danger',            'Rejected'],
];
$canRevoke = !$gift['requires_acceptance'] || in_array($gift['acceptance_status'] ?? '', ['pending', '']);
$isExpired = $gift['expires_at'] && strtotime($gift['expires_at']) < time();
?>

<div class="d-flex align-items-center mb-4">
    <a href="<?= BASE_URL ?>gift-coupons" class="btn btn-sm btn-outline-secondary me-3">
        <i class="fas fa-arrow-left me-1"></i> Back
    </a>
    <div>
        <h4 class="mb-0">Gift Coupon #<?= $gift['id'] ?></h4>
        <small class="text-muted">Gifted on <?= date('d M Y H:i', strtotime($gift['gifted_at'])) ?></small>
    </div>
    <?php if ($canRevoke && !$isExpired): ?>
    <div class="ms-auto">
        <button class="btn btn-sm btn-danger" id="revokeBtn">
            <i class="fas fa-ban me-1"></i> Revoke Gift
        </button>
    </div>
    <?php endif; ?>
</div>

<?php if ($isExpired): ?>
<div class="alert alert-secondary border-0 mb-3">
    <i class="fas fa-clock me-2"></i> This gift coupon expired on <?= date('d M Y', strtotime($gift['expires_at'])) ?>.
</div>
<?php endif; ?>

<div class="row g-3">
    <!-- Coupon Details -->
    <div class="col-12 col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-header fw-semibold"><i class="fas fa-ticket-alt me-2"></i>Coupon Details</div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">Title</dt>
                    <dd class="col-7 fw-semibold"><?= escape($gift['coupon_title']) ?></dd>

                    <dt class="col-5 text-muted">Code</dt>
                    <dd class="col-7"><code><?= escape($gift['coupon_code']) ?></code></dd>

                    <dt class="col-5 text-muted">Discount</dt>
                    <dd class="col-7">
                        <?php if ($gift['discount_type'] === 'percentage'): ?>
                            <?= $gift['discount_value'] ?>%
                        <?php else: ?>
                            ₹<?= number_format($gift['discount_value'], 2) ?>
                        <?php endif; ?>
                    </dd>

                    <dt class="col-5 text-muted">Coupon Valid Until</dt>
                    <dd class="col-7">
                        <?= $gift['valid_until'] ? date('d M Y', strtotime($gift['valid_until'])) : '—' ?>
                    </dd>
                </dl>
                <div class="mt-3">
                    <a href="<?= BASE_URL ?>coupons/detail?id=<?= $gift['coupon_id'] ?>"
                       class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-external-link-alt me-1"></i> View Coupon
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Gifting Info -->
    <div class="col-12 col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-header fw-semibold"><i class="fas fa-user me-2"></i>Recipient</div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">Customer</dt>
                    <dd class="col-7">
                        <a href="<?= BASE_URL ?>customers/profile?id=<?= $gift['customer_id'] ?>">
                            <?= escape($gift['customer_name']) ?>
                        </a>
                    </dd>

                    <dt class="col-5 text-muted">Phone</dt>
                    <dd class="col-7"><?= escape($gift['customer_phone']) ?></dd>

                    <dt class="col-5 text-muted">Email</dt>
                    <dd class="col-7"><?= escape($gift['customer_email'] ?? '—') ?></dd>

                    <dt class="col-5 text-muted">Gifted By</dt>
                    <dd class="col-7"><?= escape($gift['gifted_by']) ?></dd>

                    <dt class="col-5 text-muted">Requires Acceptance</dt>
                    <dd class="col-7">
                        <?= $gift['requires_acceptance'] ? '<span class="badge bg-info">Yes</span>' : '<span class="badge bg-secondary">No</span>' ?>
                    </dd>

                    <dt class="col-5 text-muted">Acceptance Status</dt>
                    <dd class="col-7">
                        <?php if (!$gift['requires_acceptance']): ?>
                            <span class="badge bg-secondary">Auto-gifted</span>
                        <?php elseif (isset($statusBadge[$gift['acceptance_status']])): ?>
                            <span class="badge <?= $statusBadge[$gift['acceptance_status']][0] ?>"><?= $statusBadge[$gift['acceptance_status']][1] ?></span>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </dd>

                    <?php if ($gift['accepted_at']): ?>
                    <dt class="col-5 text-muted">Accepted At</dt>
                    <dd class="col-7"><?= date('d M Y H:i', strtotime($gift['accepted_at'])) ?></dd>
                    <?php endif; ?>

                    <dt class="col-5 text-muted">Gift Expires</dt>
                    <dd class="col-7">
                        <?php if (!$gift['expires_at']): ?>
                            <span class="text-muted">No expiry</span>
                        <?php elseif ($isExpired): ?>
                            <span class="text-danger"><?= date('d M Y', strtotime($gift['expires_at'])) ?> (Expired)</span>
                        <?php else: ?>
                            <?= date('d M Y', strtotime($gift['expires_at'])) ?>
                        <?php endif; ?>
                    </dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<!-- Revoke form -->
<form method="POST" action="<?= BASE_URL ?>gift-coupons/revoke" id="revokeForm">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
    <input type="hidden" name="id" value="<?= $gift['id'] ?>">
    <input type="hidden" name="redirect" value="gift-coupons">
</form>

<script>
const btn = document.getElementById('revokeBtn');
if (btn) {
    btn.addEventListener('click', function() {
        Swal.fire({
            title: 'Revoke Gift?',
            text: 'The coupon will be removed from the customer\'s wallet.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Yes, revoke',
        }).then(r => { if (r.isConfirmed) document.getElementById('revokeForm').submit(); });
    });
}
</script>
