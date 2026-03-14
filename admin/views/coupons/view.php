<!-- Header -->
<div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="mb-0"><?= escape($coupon['title']) ?></h4>
        <small class="text-muted"><a href="<?= BASE_URL ?>/coupons">Coupons</a> / Detail</small>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= BASE_URL ?>/coupons/edit?id=<?= $coupon['id'] ?>" class="btn btn-sm btn-primary">
            <i class="fas fa-edit me-1"></i> Edit
        </a>
        <?php if ($coupon['approval_status'] === 'pending'): ?>
        <form method="POST" action="<?= BASE_URL ?>/coupons/approve" class="d-inline">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="id" value="<?= $coupon['id'] ?>">
            <button class="btn btn-sm btn-success"><i class="fas fa-check me-1"></i> Approve</button>
        </form>
        <form method="POST" action="<?= BASE_URL ?>/coupons/reject" class="d-inline">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="id" value="<?= $coupon['id'] ?>">
            <button class="btn btn-sm btn-danger"><i class="fas fa-times me-1"></i> Reject</button>
        </form>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/coupons" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back</a>
    </div>
</div>

<div class="row g-4">
    <!-- MAIN -->
    <div class="col-lg-8">
        <!-- Coupon Info Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span class="fw-semibold"><i class="fas fa-ticket-alt me-2 text-primary"></i>Coupon Information</span>
                <?php
                $st = $coupon['status'] ?? 'inactive';
                $stBadge = ['active' => 'success', 'inactive' => 'secondary', 'expired' => 'danger'];
                $ap = $coupon['approval_status'] ?? 'pending';
                $apBadge = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'];
                ?>
                <div class="d-flex gap-2">
                    <span class="badge bg-<?= $apBadge[$ap] ?? 'secondary' ?>"><?= ucfirst($ap) ?></span>
                    <span class="badge bg-<?= $stBadge[$st] ?? 'secondary' ?>"><?= ucfirst($st) ?></span>
                    <?php if ($coupon['is_admin_coupon']): ?>
                    <span class="badge bg-dark">Admin Coupon</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="text-muted small">Coupon Code</label>
                        <div class="d-flex align-items-center gap-2 mt-1">
                            <code class="fs-5 fw-bold text-dark border rounded px-3 py-1 bg-light"><?= escape($coupon['coupon_code']) ?></code>
                            <button class="btn btn-sm btn-outline-secondary" onclick="copyCode('<?= escape($coupon['coupon_code']) ?>')" title="Copy">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <label class="text-muted small">Discount</label>
                        <div class="mt-1">
                            <?php if ($coupon['discount_type'] === 'percentage'): ?>
                                <span class="badge bg-success fs-6"><?= number_format($coupon['discount_value'], 0) ?>% OFF</span>
                            <?php else: ?>
                                <span class="badge bg-primary fs-6">₹<?= number_format($coupon['discount_value'], 2) ?> OFF</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($coupon['min_purchase_amount']): ?>
                    <div class="col-sm-6">
                        <label class="text-muted small">Min Purchase</label>
                        <div class="fw-semibold">₹<?= number_format($coupon['min_purchase_amount'], 2) ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($coupon['max_discount_amount']): ?>
                    <div class="col-sm-6">
                        <label class="text-muted small">Max Discount</label>
                        <div class="fw-semibold">₹<?= number_format($coupon['max_discount_amount'], 2) ?></div>
                    </div>
                    <?php endif; ?>
                    <div class="col-sm-6">
                        <label class="text-muted small">Valid From</label>
                        <div><?= $coupon['valid_from'] ? formatDateTime($coupon['valid_from']) : '<span class="text-muted">&mdash;</span>' ?></div>
                    </div>
                    <div class="col-sm-6">
                        <label class="text-muted small">Valid Until</label>
                        <div><?= $coupon['valid_until'] ? formatDateTime($coupon['valid_until']) : '<span class="text-muted">&mdash;</span>' ?></div>
                    </div>
                    <div class="col-sm-6">
                        <label class="text-muted small">Usage</label>
                        <div><?= number_format($coupon['usage_count'] ?? 0) ?> / <?= $coupon['usage_limit'] ? number_format($coupon['usage_limit']) : '∞' ?></div>
                    </div>
                    <?php if (!empty($coupon['description'])): ?>
                    <div class="col-12">
                        <label class="text-muted small">Description</label>
                        <div><?= nl2br(escape($coupon['description'])) ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($coupon['terms_conditions'])): ?>
                    <div class="col-12">
                        <label class="text-muted small">Terms & Conditions</label>
                        <div class="small text-muted"><?= nl2br(escape($coupon['terms_conditions'])) ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Tags -->
        <?php if (!empty($tags)): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-semibold"><i class="fas fa-tags me-2 text-secondary"></i>Tags</div>
            <div class="card-body pb-2">
                <?php foreach ($tags as $tag): ?>
                    <span class="badge bg-secondary me-1 mb-2 px-3 py-2"><?= escape($tag['tag_name']) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Categories & Sub-categories -->
        <?php if (!empty($couponCategoryDetails)): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-semibold"><i class="fas fa-layer-group me-2 text-primary"></i>Category Targeting</div>
            <div class="card-body pb-2">
                <?php
                $grouped = [];
                foreach ($couponCategoryDetails as $row) {
                    $cName = $row['category_name'];
                    if (!isset($grouped[$cName])) $grouped[$cName] = [];
                    if ($row['sub_category_name']) $grouped[$cName][] = $row['sub_category_name'];
                }
                foreach ($grouped as $catName => $subCats):
                ?>
                <div class="mb-2">
                    <span class="badge bg-primary me-1 mb-1 px-3 py-2"><?= escape($catName) ?></span>
                    <?php foreach ($subCats as $sc): ?>
                        <span class="badge bg-secondary me-1 mb-1 px-2 py-1 small"><?= escape($sc) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Redemption History -->
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span class="fw-semibold"><i class="fas fa-receipt me-2 text-info"></i>Redemption History</span>
                <span class="badge bg-info text-dark"><?= count($redemptions) ?></span>
            </div>
            <?php if (empty($redemptions)): ?>
            <div class="card-body text-muted">No redemptions yet.</div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Customer</th><th>Store</th><th>Discount</th><th>Transaction</th><th>Date</th><th>Verified</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($redemptions as $r): ?>
                    <tr>
                        <td><?= escape($r['customer_name'] ?? '&mdash;') ?></td>
                        <td><?= escape($r['store_name'] ?? '&mdash;') ?></td>
                        <td>₹<?= number_format($r['discount_amount'], 2) ?></td>
                        <td><?= $r['transaction_amount'] ? '₹' . number_format($r['transaction_amount'], 2) : '&mdash;' ?></td>
                        <td class="small text-muted"><?= formatDateTime($r['redeemed_at']) ?></td>
                        <td>
                            <?php if ($r['verified_by_merchant']): ?>
                                <i class="fas fa-check-circle text-success"></i>
                            <?php else: ?>
                                <i class="fas fa-clock text-warning"></i>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Gift History -->
        <?php if (!empty($gifts)): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-semibold"><i class="fas fa-gift me-2 text-danger"></i>Gift History</div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Customer</th><th>Gifted By</th><th>Acceptance</th><th>Gifted At</th><th>Expires</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($gifts as $g): ?>
                    <tr>
                        <td><?= escape($g['customer_name'] ?? '&mdash;') ?></td>
                        <td><?= escape($g['admin_name'] ?? '&mdash;') ?></td>
                        <td>
                            <?php
                            $gs = $g['acceptance_status'] ?? 'pending';
                            $gb = ['pending'=>'warning','accepted'=>'success','rejected'=>'danger'];
                            ?>
                            <span class="badge bg-<?= $gb[$gs] ?? 'secondary' ?>"><?= ucfirst($gs) ?></span>
                        </td>
                        <td class="small text-muted"><?= formatDateTime($g['gifted_at']) ?></td>
                        <td class="small text-muted"><?= $g['expires_at'] ? formatDate($g['expires_at']) : '&mdash;' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- RIGHT -->
    <div class="col-lg-4">
        <!-- Quick Stats -->
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-semibold"><i class="fas fa-chart-bar me-2 text-primary"></i>Quick Stats</div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Total Redemptions</span>
                        <strong><?= count($redemptions) ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Usage Count</span>
                        <strong><?= number_format($coupon['usage_count'] ?? 0) ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Usage Limit</span>
                        <strong><?= $coupon['usage_limit'] ? number_format($coupon['usage_limit']) : '∞' ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Gifts Issued</span>
                        <strong><?= count($gifts) ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Tags</span>
                        <strong><?= count($tags) ?></strong>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Merchant Info -->
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-semibold"><i class="fas fa-store me-2 text-warning"></i>Merchant</div>
            <div class="card-body">
                <div class="fw-semibold"><?= escape($coupon['merchant_name'] ?? '&mdash;') ?></div>
                <?php if (!empty($coupon['store_name'])): ?>
                    <div class="small text-muted">Store: <?= escape($coupon['store_name']) ?></div>
                <?php else: ?>
                    <div class="small text-muted">Applies to all stores</div>
                <?php endif; ?>
                <?php if (!empty($coupon['merchant_id'])): ?>
                    <a href="<?= BASE_URL ?>/merchants/profile?id=<?= $coupon['merchant_id'] ?>" class="btn btn-sm btn-outline-secondary mt-2">
                        <i class="fas fa-external-link-alt me-1"></i> View Merchant
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Approval -->
        <?php if (!empty($coupon['approver_name'])): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-semibold"><i class="fas fa-user-check me-2 text-success"></i>Approval</div>
            <div class="card-body small">
                <div>Approved by: <strong><?= escape($coupon['approver_name']) ?></strong></div>
                <div class="text-muted"><?= formatDateTime($coupon['approved_at']) ?></div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-semibold"><i class="fas fa-bolt me-2 text-warning"></i>Quick Actions</div>
            <div class="card-body d-flex flex-column gap-2">
                <form method="POST" action="<?= BASE_URL ?>/coupons/toggle">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="id" value="<?= $coupon['id'] ?>">
                    <input type="hidden" name="redirect" value="coupons/detail?id=<?= $coupon['id'] ?>">
                    <button class="btn btn-sm w-100 <?= $coupon['status'] === 'active' ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                        <i class="fas fa-power-off me-1"></i>
                        <?= $coupon['status'] === 'active' ? 'Deactivate Coupon' : 'Activate Coupon' ?>
                    </button>
                </form>
            </div>
        </div>

        <!-- Metadata -->
        <div class="card shadow-sm mb-4 text-muted small">
            <div class="card-body">
                <div><strong>Created:</strong> <?= formatDateTime($coupon['created_at']) ?></div>
                <div><strong>Updated:</strong> <?= formatDateTime($coupon['updated_at']) ?></div>
            </div>
        </div>

        <!-- Danger Zone -->
        <div class="card border-danger shadow-sm">
            <div class="card-header text-danger fw-semibold"><i class="fas fa-exclamation-triangle me-2"></i>Danger Zone</div>
            <div class="card-body">
                <p class="small text-muted mb-2">Permanently delete this coupon and all its redemption records.</p>
                <button class="btn btn-sm btn-danger w-100" onclick="confirmDelete(<?= $coupon['id'] ?>, '<?= escape(addslashes($coupon['title'])) ?>')">
                    <i class="fas fa-trash me-1"></i> Delete Coupon
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete form -->
<form id="deleteForm" method="POST" action="<?= BASE_URL ?>/coupons/delete" style="display:none">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
    <input type="hidden" name="id" value="<?= $coupon['id'] ?>">
</form>

<script>
function confirmDelete(id, name) {
    Swal.fire({
        title: 'Delete Coupon?',
        html: `This will permanently delete <strong>${name}</strong> and all its redemption data.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Yes, delete it',
    }).then(r => {
        if (r.isConfirmed) document.getElementById('deleteForm').submit();
    });
}

function copyCode(code) {
    navigator.clipboard.writeText(code).then(() => {
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Code copied!', showConfirmButton: false, timer: 1500 });
    });
}
</script>
