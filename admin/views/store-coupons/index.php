<!-- Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3 col-xl-2">
        <div class="card text-bg-primary h-100 shadow-sm">
            <div class="card-body py-3 px-3">
                <div class="small fw-semibold text-white-50">Total</div>
                <div class="fs-4 fw-bold"><?= number_format($stats['total'] ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <div class="card text-bg-success h-100 shadow-sm">
            <div class="card-body py-3 px-3">
                <div class="small fw-semibold text-white-50">Active</div>
                <div class="fs-4 fw-bold"><?= number_format($stats['active'] ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <div class="card text-bg-info h-100 shadow-sm">
            <div class="card-body py-3 px-3">
                <div class="small fw-semibold text-white-50">Gifted</div>
                <div class="fs-4 fw-bold"><?= number_format($stats['gifted'] ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <div class="card text-bg-warning h-100 shadow-sm">
            <div class="card-body py-3 px-3">
                <div class="small fw-semibold text-white-50">Redeemed</div>
                <div class="fs-4 fw-bold"><?= number_format($stats['redeemed'] ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <div class="card text-bg-secondary h-100 shadow-sm">
            <div class="card-body py-3 px-3">
                <div class="small fw-semibold text-white-50">Expired</div>
                <div class="fs-4 fw-bold"><?= number_format($stats['expired'] ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <div class="card text-bg-dark h-100 shadow-sm">
            <div class="card-body py-3 px-3">
                <div class="small fw-semibold text-white-50">Added Today</div>
                <div class="fs-4 fw-bold"><?= number_format($stats['today'] ?? 0) ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Page header -->
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="mb-0">Store Coupon Management</h4>
        <small class="text-muted">
            <?php
            $from = min($totalCount, ($currentPage - 1) * $perPage + 1);
            $to   = min($totalCount, $currentPage * $perPage);
            echo $totalCount ? "Showing {$from}–{$to} of {$totalCount}" : 'No store coupons found';
            ?>
        </small>
    </div>
</div>

<!-- Filter bar -->
<div class="card mb-3 shadow-sm">
    <div class="card-body py-2 px-3">
        <form method="GET" action="<?= BASE_URL ?>store-coupons" class="row g-2 align-items-end">
            <div class="col-12 col-md-2">
                <label class="form-label form-label-sm mb-1">Search</label>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Code or merchant…" value="<?= escape($filters['search']) ?>">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label form-label-sm mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <?php foreach (['active', 'inactive', 'expired'] as $s): ?>
                        <option value="<?= $s ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label form-label-sm mb-1">Gifted</label>
                <select name="is_gifted" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="1" <?= ($filters['is_gifted'] ?? '') === '1' ? 'selected' : '' ?>>Yes</option>
                    <option value="0" <?= ($filters['is_gifted'] ?? '') === '0' ? 'selected' : '' ?>>No</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label form-label-sm mb-1">Redeemed</label>
                <select name="is_redeemed" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="1" <?= ($filters['is_redeemed'] ?? '') === '1' ? 'selected' : '' ?>>Yes</option>
                    <option value="0" <?= ($filters['is_redeemed'] ?? '') === '0' ? 'selected' : '' ?>>No</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label form-label-sm mb-1">Merchant</label>
                <select name="merchant_id" class="form-select form-select-sm">
                    <option value="">All Merchants</option>
                    <?php foreach ($merchants as $m): ?>
                        <option value="<?= $m['id'] ?>" <?= ($filters['merchant_id'] ?? '') == $m['id'] ? 'selected' : '' ?>>
                            <?= escape($m['business_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-auto ms-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i>Filter</button>
                <a href="<?= BASE_URL ?>store-coupons" class="btn btn-sm btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Store Coupons Table -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($storeCoupons)): ?>
        <div class="text-center text-muted py-5">
            <i class="fas fa-tags fa-3x mb-3 opacity-25"></i>
            <p class="mb-0">No store coupons found.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Code</th>
                        <th>Merchant</th>
                        <th>Store</th>
                        <th class="text-center">Discount</th>
                        <th class="text-center">Gifted</th>
                        <th class="text-center">Redeemed</th>
                        <th>Valid Period</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($storeCoupons as $sc): ?>
                    <?php
                        $statusColors = ['active' => 'success', 'inactive' => 'secondary', 'expired' => 'warning'];
                    ?>
                    <tr>
                        <td class="text-muted small">#<?= $sc['id'] ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>store-coupons/detail?id=<?= $sc['id'] ?>" class="text-decoration-none fw-semibold font-monospace">
                                <?= escape($sc['coupon_code']) ?>
                            </a>
                        </td>
                        <td>
                            <a href="<?= BASE_URL ?>merchants/profile?id=<?= $sc['merchant_id'] ?>" class="text-decoration-none small">
                                <?= escape($sc['merchant_name']) ?>
                            </a>
                        </td>
                        <td class="small"><?= escape($sc['store_name']) ?></td>
                        <td class="text-center fw-semibold text-success">
                            <?php if ($sc['discount_type'] === 'percentage'): ?>
                                <?= number_format($sc['discount_value'], 0) ?>%
                            <?php else: ?>
                                ₹<?= number_format($sc['discount_value'], 0) ?>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($sc['is_gifted']): ?>
                                <span class="badge bg-info-subtle text-info border border-info-subtle">
                                    <i class="fas fa-gift me-1"></i><?= $sc['gifted_to_name'] ? escape($sc['gifted_to_name']) : 'Yes' ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($sc['is_redeemed']): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                    <i class="fas fa-check me-1"></i>Yes
                                </span>
                            <?php else: ?>
                                <span class="text-muted">No</span>
                            <?php endif; ?>
                        </td>
                        <td class="small">
                            <?php if ($sc['valid_from'] || $sc['valid_until']): ?>
                                <?= $sc['valid_from'] ? formatDate($sc['valid_from']) : '—' ?>
                                → <?= $sc['valid_until'] ? formatDate($sc['valid_until']) : '—' ?>
                            <?php else: ?>
                                <span class="text-muted">No expiry</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <form method="POST" action="<?= BASE_URL ?>store-coupons/toggle" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                <input type="hidden" name="id" value="<?= $sc['id'] ?>">
                                <button type="submit" class="badge border-0 bg-<?= $statusColors[$sc['status']] ?? 'secondary' ?> p-2"
                                        style="cursor:pointer;"><?= ucfirst($sc['status']) ?></button>
                            </form>
                        </td>
                        <td class="text-center text-nowrap">
                            <a href="<?= BASE_URL ?>store-coupons/detail?id=<?= $sc['id'] ?>" class="btn btn-sm btn-outline-primary me-1" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?= $sc['id'] ?>, '<?= escape($sc['coupon_code']) ?>')" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-center">
        <?php if ($currentPage > 1): ?>
        <li class="page-item">
            <a class="page-link" href="<?= BASE_URL ?>store-coupons?<?= http_build_query(array_merge($filters, ['page' => $currentPage - 1])) ?>">
                <i class="fas fa-chevron-left"></i>
            </a>
        </li>
        <?php endif; ?>

        <?php for ($p = max(1, $currentPage - 2); $p <= min($totalPages, $currentPage + 2); $p++): ?>
        <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
            <a class="page-link" href="<?= BASE_URL ?>store-coupons?<?= http_build_query(array_merge($filters, ['page' => $p])) ?>"><?= $p ?></a>
        </li>
        <?php endfor; ?>

        <?php if ($currentPage < $totalPages): ?>
        <li class="page-item">
            <a class="page-link" href="<?= BASE_URL ?>store-coupons?<?= http_build_query(array_merge($filters, ['page' => $currentPage + 1])) ?>">
                <i class="fas fa-chevron-right"></i>
            </a>
        </li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>

<!-- Delete Form -->
<form method="POST" action="<?= BASE_URL ?>store-coupons/delete" id="deleteForm">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
    <input type="hidden" name="id" id="deleteId">
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
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteForm').submit();
        }
    });
}
</script>
