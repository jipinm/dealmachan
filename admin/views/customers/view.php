<?php /* views/customers/view.php */
$statusColors = [
    'active'   => 'success',
    'inactive' => 'secondary',
    'blocked'  => 'danger',
    'pending'  => 'warning',
];
$typeColors = [
    'standard'  => 'primary',
    'premium'   => 'warning',
    'dealmaker' => 'success',
];
?>

<!-- Breadcrumb & Header -->
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>customers">Customer Management</a></li>
                <li class="breadcrumb-item active"><?= escape($customer['name']) ?></li>
            </ol>
        </nav>
        <h1 class="h3 mb-0"><?= escape($customer['name']) ?></h1>
        <p class="text-muted small mb-0">Customer Profile</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>customers/edit?id=<?= $customer['id'] ?>" class="btn btn-primary btn-sm">
            <i class="fas fa-edit me-1"></i> Edit
        </a>
        <form method="POST" action="<?= BASE_URL ?>customers/toggle" class="d-inline">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="id" value="<?= $customer['id'] ?>">
            <input type="hidden" name="redirect" value="customers/profile?id=<?= $customer['id'] ?>">
            <button type="submit" class="btn btn-sm btn-outline-<?= $customer['status'] === 'active' ? 'warning' : 'success' ?>">
                <i class="fas fa-<?= $customer['status'] === 'active' ? 'ban' : 'check' ?> me-1"></i>
                <?= $customer['status'] === 'active' ? 'Block' : 'Activate' ?>
            </button>
        </form>
        <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?= $customer['id'] ?>, '<?= escape($customer['name']) ?>')">
            <i class="fas fa-trash me-1"></i> Delete
        </button>
    </div>
</div>

<div class="row g-4">
    <!-- Left: Main Info -->
    <div class="col-lg-8">

        <!-- Profile Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex gap-4 align-items-center">
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0"
                         style="width:72px;height:72px;font-size:1.6rem;">
                        <?= strtoupper(mb_substr($customer['name'], 0, 1)) ?>
                    </div>
                    <div class="flex-grow-1">
                        <h4 class="mb-1"><?= escape($customer['name']) ?></h4>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <span class="badge bg-<?= $typeColors[$customer['customer_type']] ?? 'secondary' ?>">
                                <?= ucfirst($customer['customer_type']) ?>
                            </span>
                            <span class="badge bg-<?= $statusColors[$customer['status']] ?? 'secondary' ?>">
                                <?= ucfirst($customer['status']) ?>
                            </span>
                            <?php if ($customer['is_dealmaker']): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                <i class="fas fa-star me-1"></i> DealMaker
                            </span>
                            <?php endif; ?>
                            <?php if ($customer['subscription_status'] === 'active'): ?>
                            <span class="badge bg-info-subtle text-info border border-info-subtle">
                                <i class="fas fa-crown me-1"></i> Subscribed
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="text-end text-muted small">
                        <div>ID #<?= $customer['id'] ?></div>
                        <div>Joined <?= formatDate($customer['registered_at'] ?? $customer['created_at']) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Details -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold border-bottom"><i class="fas fa-info-circle me-2 text-primary"></i> Profile Details</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="text-muted small text-uppercase fw-semibold">Email</label>
                        <div><?= $customer['email'] ? escape($customer['email']) : '<span class="text-muted fst-italic">Not set</span>' ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small text-uppercase fw-semibold">Phone</label>
                        <div><?= $customer['phone'] ? escape($customer['phone']) : '<span class="text-muted fst-italic">Not set</span>' ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small text-uppercase fw-semibold">Date of Birth</label>
                        <div><?= $customer['date_of_birth'] ? formatDate($customer['date_of_birth']) : '<span class="text-muted fst-italic">Not set</span>' ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small text-uppercase fw-semibold">Gender</label>
                        <div><?= $customer['gender'] ? ucfirst($customer['gender']) : '<span class="text-muted fst-italic">Not set</span>' ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small text-uppercase fw-semibold">Profession</label>
                        <div><?= $customer['profession_name'] ? escape($customer['profession_name']) : '<span class="text-muted fst-italic">Not set</span>' ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small text-uppercase fw-semibold">Registration Type</label>
                        <div><?= ucwords(str_replace('_', ' ', $customer['registration_type'])) ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small text-uppercase fw-semibold">Last Login</label>
                        <div><?= $customer['last_login'] ? formatDateTime($customer['last_login']) : '<span class="text-muted fst-italic">Never</span>' ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small text-uppercase fw-semibold">Referral Code</label>
                        <div><code><?= escape($customer['referral_code'] ?? '—') ?></code></div>
                    </div>
                    <?php if ($customer['referrer_name']): ?>
                    <div class="col-md-6">
                        <label class="text-muted small text-uppercase fw-semibold">Referred By</label>
                        <div><?= escape($customer['referrer_name']) ?> <span class="text-muted small">(<?= escape($customer['referrer_code'] ?? '') ?>)</span></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($customer['subscription_expiry']): ?>
                    <div class="col-md-6">
                        <label class="text-muted small text-uppercase fw-semibold">Subscription Expiry</label>
                        <div><?= formatDate($customer['subscription_expiry']) ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Redemption History -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold border-bottom">
                <i class="fas fa-ticket-alt me-2 text-warning"></i> Recent Coupon Redemptions
            </div>
            <div class="card-body p-0">
                <?php if (empty($redemptions)): ?>
                <div class="text-center text-muted py-4 small"><i class="fas fa-receipt fa-2x d-block mb-2 opacity-25"></i> No redemptions yet.</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>Coupon</th>
                                <th>Discount</th>
                                <th>Redeemed At</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($redemptions as $r): ?>
                            <tr>
                                <td><?= escape($r['coupon_title'] ?? '—') ?></td>
                                <td>
                                    <?php if ($r['discount_type'] === 'percentage'): ?>
                                        <?= number_format($r['discount_value']) ?>%
                                    <?php else: ?>
                                        ₹<?= number_format($r['discount_value']) ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= isset($r['redeemed_at']) ? formatDateTime($r['redeemed_at']) : '—' ?></td>
                                <td><span class="badge bg-success-subtle text-success border border-success-subtle">Redeemed</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Transaction History (Sales Registry) -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold border-bottom">
                <i class="fas fa-shopping-cart me-2 text-info"></i> Recent Transactions
            </div>
            <div class="card-body p-0">
                <?php if (empty($transactions)): ?>
                <div class="text-center text-muted py-4 small"><i class="fas fa-shopping-bag fa-2x d-block mb-2 opacity-25"></i> No transactions recorded.</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Merchant</th>
                                <th>Store</th>
                                <th class="text-end">Amount</th>
                                <th class="text-end">Discount</th>
                                <th>Payment</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $t): ?>
                            <tr>
                                <td><?= formatDateTime($t['transaction_date']) ?></td>
                                <td>
                                    <a href="<?= BASE_URL ?>merchants/profile?id=<?= $t['merchant_id'] ?>" class="text-decoration-none">
                                        <?= escape($t['merchant_name']) ?>
                                    </a>
                                </td>
                                <td><?= escape($t['store_name']) ?></td>
                                <td class="text-end fw-semibold">₹<?= number_format($t['transaction_amount'], 2) ?></td>
                                <td class="text-end text-success"><?= $t['discount_amount'] > 0 ? '₹' . number_format($t['discount_amount'], 2) : '—' ?></td>
                                <td><?= $t['payment_method'] ? ucfirst($t['payment_method']) : '—' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Store Coupons -->
        <?php if (!empty($storeCoupons)): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold border-bottom">
                <i class="fas fa-tags me-2 text-primary"></i> Store Coupons Received
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>Code</th>
                                <th>Merchant / Store</th>
                                <th class="text-center">Discount</th>
                                <th class="text-center">Redeemed</th>
                                <th>Gifted At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($storeCoupons as $sc): ?>
                            <tr>
                                <td class="font-monospace fw-semibold">
                                    <a href="<?= BASE_URL ?>store-coupons/detail?id=<?= $sc['id'] ?>" class="text-decoration-none">
                                        <?= escape($sc['coupon_code']) ?>
                                    </a>
                                </td>
                                <td>
                                    <?= escape($sc['merchant_name']) ?>
                                    <span class="text-muted">/ <?= escape($sc['store_name']) ?></span>
                                </td>
                                <td class="text-center text-success fw-semibold">
                                    <?= $sc['discount_type'] === 'percentage' ? number_format($sc['discount_value'], 0) . '%' : '₹' . number_format($sc['discount_value'], 0) ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($sc['is_redeemed']): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fas fa-check me-1"></i>Yes</span>
                                    <?php else: ?>
                                        <span class="text-muted">No</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $sc['gifted_at'] ? formatDateTime($sc['gifted_at']) : '—' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- Right Sidebar -->
    <div class="col-lg-4">

        <!-- Quick Stats -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold border-bottom"><i class="fas fa-chart-bar me-2 text-info"></i> Quick Stats</div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Coupon Redemptions
                        <span class="badge bg-primary rounded-pill"><?= count($redemptions) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Subscription
                        <span class="badge bg-<?= $customer['subscription_status'] === 'active' ? 'success' : 'secondary' ?> rounded-pill">
                            <?= ucfirst($customer['subscription_status']) ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        DealMaker
                        <span class="badge bg-<?= $customer['is_dealmaker'] ? 'success' : 'light text-dark border' ?> rounded-pill">
                            <?= $customer['is_dealmaker'] ? 'Yes' : 'No' ?>
                        </span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Transaction Analytics -->
        <?php if (!empty($analytics) && $analytics['total_transactions'] > 0): ?>
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold border-bottom"><i class="fas fa-chart-line me-2 text-success"></i> Transaction Analytics</div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Total Transactions
                        <span class="fw-semibold"><?= number_format($analytics['total_transactions']) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Total Spent
                        <span class="fw-semibold text-success">₹<?= number_format($analytics['total_spent'], 2) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Average Transaction
                        <span class="fw-semibold">₹<?= number_format($analytics['avg_transaction'], 2) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Total Discount Received
                        <span class="fw-semibold text-info">₹<?= number_format($analytics['total_discount'], 2) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Merchants Visited
                        <span class="badge bg-primary rounded-pill"><?= (int)$analytics['merchant_count'] ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Stores Visited
                        <span class="badge bg-secondary rounded-pill"><?= (int)$analytics['store_count'] ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        First Transaction
                        <span class="small text-muted"><?= $analytics['first_transaction'] ? formatDate($analytics['first_transaction']) : '—' ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Last Transaction
                        <span class="small text-muted"><?= $analytics['last_transaction'] ? formatDate($analytics['last_transaction']) : '—' ?></span>
                    </li>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <!-- Timestamps -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold border-bottom"><i class="fas fa-clock me-2 text-secondary"></i> Timestamps</div>
            <div class="card-body small">
                <div class="mb-2"><span class="text-muted">Registered:</span> <?= formatDate($customer['registered_at'] ?? $customer['created_at']) ?></div>
                <div class="mb-2"><span class="text-muted">Last Login:</span> <?= $customer['last_login'] ? formatDateTime($customer['last_login']) : '<em>Never</em>' ?></div>
                <?php if ($customer['dealmaker_approved_at']): ?>
                <div><span class="text-muted">DM Approved:</span> <?= formatDateTime($customer['dealmaker_approved_at']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Danger Zone -->
        <div class="card border-danger border-opacity-25 shadow-sm">
            <div class="card-header bg-white fw-semibold border-bottom text-danger"><i class="fas fa-exclamation-triangle me-2"></i> Danger Zone</div>
            <div class="card-body">
                <p class="text-muted small mb-3">Permanently remove this customer and all associated data.</p>
                <button class="btn btn-sm btn-danger w-100" onclick="confirmDelete(<?= $customer['id'] ?>, '<?= escape($customer['name']) ?>')">
                    <i class="fas fa-trash me-1"></i> Delete Customer
                </button>
            </div>
        </div>

    </div>
</div>

<!-- Delete Form -->
<form method="POST" action="<?= BASE_URL ?>customers/delete" id="deleteForm">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
    <input type="hidden" name="id" id="deleteId">
</form>

<script>
function confirmDelete(id, name) {
    Swal.fire({
        title: 'Delete Customer?',
        html: `Permanently delete <b>${name}</b>? This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Delete'
    }).then(r => {
        if (r.isConfirmed) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteForm').submit();
        }
    });
}
</script>
