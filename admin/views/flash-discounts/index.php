<!-- Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card text-bg-primary h-100 shadow-sm">
            <div class="card-body py-3 px-3">
                <div class="small fw-semibold text-white-50">Total</div>
                <div class="fs-4 fw-bold"><?= number_format($stats['total'] ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card text-bg-success h-100 shadow-sm">
            <div class="card-body py-3 px-3">
                <div class="small fw-semibold text-white-50">Active</div>
                <div class="fs-4 fw-bold"><?= number_format($stats['active'] ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card text-bg-secondary h-100 shadow-sm">
            <div class="card-body py-3 px-3">
                <div class="small fw-semibold text-white-50">Inactive</div>
                <div class="fs-4 fw-bold"><?= number_format($stats['inactive'] ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card text-bg-warning h-100 shadow-sm">
            <div class="card-body py-3 px-3">
                <div class="small fw-semibold text-white-50">Expired</div>
                <div class="fs-4 fw-bold"><?= number_format($stats['expired'] ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card text-bg-info h-100 shadow-sm">
            <div class="card-body py-3 px-3">
                <div class="small fw-semibold text-white-50">Total Redemptions</div>
                <div class="fs-4 fw-bold"><?= number_format($stats['total_redemptions'] ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
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
        <h4 class="mb-0">Flash Discount Management</h4>
        <small class="text-muted">
            <?php
            $from = min($totalCount, ($currentPage - 1) * $perPage + 1);
            $to   = min($totalCount, $currentPage * $perPage);
            echo $totalCount ? "Showing {$from}–{$to} of {$totalCount}" : 'No flash discounts found';
            ?>
        </small>
    </div>
    <div>
        <a href="<?= BASE_URL ?>flash-discounts/add" class="btn btn-primary btn-sm">
            <i class="fas fa-plus me-1"></i> Create Flash Discount
        </a>
    </div>
</div>

<!-- Filter bar -->
<div class="card mb-3 shadow-sm">
    <div class="card-body py-2 px-3">
        <form method="GET" action="<?= BASE_URL ?>flash-discounts" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label form-label-sm mb-1">Search</label>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Title or merchant…" value="<?= escape($filters['search']) ?>">
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
                <label class="form-label form-label-sm mb-1">Expiry</label>
                <select name="expiry" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="active"   <?= ($filters['expiry'] ?? '') === 'active'   ? 'selected' : '' ?>>Not Expired</option>
                    <option value="expired"  <?= ($filters['expiry'] ?? '') === 'expired'  ? 'selected' : '' ?>>Expired</option>
                    <option value="upcoming" <?= ($filters['expiry'] ?? '') === 'upcoming' ? 'selected' : '' ?>>Starting Soon</option>
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
                <a href="<?= BASE_URL ?>flash-discounts" class="btn btn-sm btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Flash Discounts Table -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($flashDiscounts)): ?>
        <div class="text-center text-muted py-5">
            <i class="fas fa-bolt fa-3x mb-3 opacity-25"></i>
            <p class="mb-0">No flash discounts found.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Merchant</th>
                        <th>Store</th>
                        <th class="text-center">Discount</th>
                        <th class="text-center">Redemptions</th>
                        <th>Valid Period</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($flashDiscounts as $fd): ?>
                    <?php
                        $statusColors = ['active' => 'success', 'inactive' => 'secondary', 'expired' => 'warning'];
                    ?>
                    <tr>
                        <td class="text-muted small">#<?= $fd['id'] ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>flash-discounts/detail?id=<?= $fd['id'] ?>" class="text-decoration-none fw-semibold">
                                <?= escape($fd['title']) ?>
                            </a>
                        </td>
                        <td>
                            <a href="<?= BASE_URL ?>merchants/profile?id=<?= $fd['merchant_id'] ?>" class="text-decoration-none small">
                                <?= escape($fd['merchant_name']) ?>
                            </a>
                        </td>
                        <td class="small"><?= $fd['store_name'] ? escape($fd['store_name']) : '<span class="text-muted">—</span>' ?></td>
                        <td class="text-center fw-semibold text-success"><?= number_format($fd['discount_percentage'], 0) ?>%</td>
                        <td class="text-center">
                            <?= (int)$fd['current_redemptions'] ?>
                            <?php if ($fd['max_redemptions']): ?>
                                <span class="text-muted small">/ <?= (int)$fd['max_redemptions'] ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="small">
                            <?php if ($fd['valid_from'] || $fd['valid_until']): ?>
                                <?= $fd['valid_from'] ? formatDate($fd['valid_from']) : '—' ?>
                                → <?= $fd['valid_until'] ? formatDate($fd['valid_until']) : '—' ?>
                            <?php else: ?>
                                <span class="text-muted">No expiry</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <form method="POST" action="<?= BASE_URL ?>flash-discounts/toggle" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                <input type="hidden" name="id" value="<?= $fd['id'] ?>">
                                <button type="submit" class="badge border-0 bg-<?= $statusColors[$fd['status']] ?? 'secondary' ?> p-2"
                                        style="cursor:pointer;"><?= ucfirst($fd['status']) ?></button>
                            </form>
                        </td>
                        <td class="text-center text-nowrap">
                            <a href="<?= BASE_URL ?>flash-discounts/detail?id=<?= $fd['id'] ?>" class="btn btn-sm btn-outline-primary me-1" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?= $fd['id'] ?>, '<?= escape($fd['title']) ?>')" title="Delete">
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
            <a class="page-link" href="<?= BASE_URL ?>flash-discounts?<?= http_build_query(array_merge($filters, ['page' => $currentPage - 1])) ?>">
                <i class="fas fa-chevron-left"></i>
            </a>
        </li>
        <?php endif; ?>

        <?php for ($p = max(1, $currentPage - 2); $p <= min($totalPages, $currentPage + 2); $p++): ?>
        <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
            <a class="page-link" href="<?= BASE_URL ?>flash-discounts?<?= http_build_query(array_merge($filters, ['page' => $p])) ?>"><?= $p ?></a>
        </li>
        <?php endfor; ?>

        <?php if ($currentPage < $totalPages): ?>
        <li class="page-item">
            <a class="page-link" href="<?= BASE_URL ?>flash-discounts?<?= http_build_query(array_merge($filters, ['page' => $currentPage + 1])) ?>">
                <i class="fas fa-chevron-right"></i>
            </a>
        </li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>

<!-- Delete Form -->
<form method="POST" action="<?= BASE_URL ?>flash-discounts/delete" id="deleteForm">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
    <input type="hidden" name="id" id="deleteId">
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
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteForm').submit();
        }
    });
}
</script>
