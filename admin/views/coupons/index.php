<!-- Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card text-bg-primary h-100 shadow-sm">
            <div class="card-body py-3 px-3">
                <div class="small fw-semibold text-white-50">Total Coupons</div>
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
                <div class="small fw-semibold text-white-50">Expired</div>
                <div class="fs-4 fw-bold"><?= number_format($stats['expired'] ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card text-bg-warning h-100 shadow-sm">
            <div class="card-body py-3 px-3">
                <div class="small fw-semibold text-white-50">Pending Approval</div>
                <div class="fs-4 fw-bold"><?= number_format($stats['pending_approval'] ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card text-bg-info h-100 shadow-sm">
            <div class="card-body py-3 px-3">
                <div class="small fw-semibold text-white-50">Admin Coupons</div>
                <div class="fs-4 fw-bold"><?= number_format($stats['admin_coupons'] ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card text-bg-dark h-100 shadow-sm">
            <div class="card-body py-3 px-3">
                <div class="small fw-semibold text-white-50">Total Redemptions</div>
                <div class="fs-4 fw-bold"><?= number_format($stats['total_redemptions'] ?? 0) ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Page header -->
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="mb-0">Coupon Management</h4>
        <small class="text-muted">
            <?php
            $from = min($totalCount, ($currentPage - 1) * $perPage + 1);
            $to   = min($totalCount, $currentPage * $perPage);
            echo $totalCount ? "Showing {$from}&ndash;{$to} of {$totalCount}" : 'No coupons found';
            ?>
        </small>
    </div>
    <a href="<?= BASE_URL ?>/coupons/add" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> New Coupon
    </a>
</div>

<!-- Filter bar -->
<div class="card mb-3 shadow-sm">
    <div class="card-body py-2 px-3">
        <form method="GET" action="<?= BASE_URL ?>/coupons" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label form-label-sm mb-1">Search</label>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Title or code…" value="<?= escape($filters['search']) ?>">
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
                <label class="form-label form-label-sm mb-1">Approval</label>
                <select name="approval_status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <?php foreach (['pending', 'approved', 'rejected'] as $s): ?>
                        <option value="<?= $s ?>" <?= $filters['approval_status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label form-label-sm mb-1">Discount</label>
                <select name="discount_type" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="percentage" <?= $filters['discount_type'] === 'percentage' ? 'selected' : '' ?>>Percentage</option>
                    <option value="fixed"      <?= $filters['discount_type'] === 'fixed'      ? 'selected' : '' ?>>Fixed Amount</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label form-label-sm mb-1">Expiry</label>
                <select name="expiry" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="active"   <?= $filters['expiry'] === 'active'   ? 'selected' : '' ?>>Not Expired</option>
                    <option value="expired"  <?= $filters['expiry'] === 'expired'  ? 'selected' : '' ?>>Expired</option>
                    <option value="upcoming" <?= $filters['expiry'] === 'upcoming' ? 'selected' : '' ?>>Starting Soon</option>
                </select>
            </div>
            <div class="col-12 col-md-auto ms-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i>Filter</button>
                <a href="<?= BASE_URL ?>/coupons" class="btn btn-sm btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<?php if (empty($coupons)): ?>
    <div class="alert alert-info">No coupons match your filters.</div>
<?php else: ?>
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Title / Code</th>
                    <th>Merchant</th>
                    <th>Discount</th>
                    <th>Validity</th>
                    <th>Usage</th>
                    <th>Approval</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($coupons as $i => $c): ?>
                <tr>
                    <td class="text-muted small"><?= ($currentPage - 1) * $perPage + $i + 1 ?></td>
                    <td>
                        <div class="fw-semibold">
                            <a href="<?= BASE_URL ?>/coupons/detail?id=<?= $c['id'] ?>" class="text-decoration-none">
                                <?= escape($c['title']) ?>
                            </a>
                        </div>
                        <code class="small text-muted"><?= escape($c['coupon_code']) ?></code>
                        <?php if ($c['is_admin_coupon']): ?>
                            <span class="badge bg-dark ms-1">Admin</span>
                        <?php endif; ?>
                    </td>
                    <td class="small">
                        <?= escape($c['merchant_name'] ?? '&mdash;') ?>
                        <?php if (!empty($c['store_name'])): ?>
                            <div class="text-muted"><?= escape($c['store_name']) ?></div>
                        <?php else: ?>
                            <div class="text-muted">All Stores</div>
                        <?php endif; ?>
                    </td>
                    <td class="small">
                        <?php if ($c['discount_type'] === 'percentage'): ?>
                            <span class="badge bg-success"><?= number_format($c['discount_value'], 0) ?>% OFF</span>
                        <?php else: ?>
                            <span class="badge bg-primary">₹<?= number_format($c['discount_value'], 0) ?> OFF</span>
                        <?php endif; ?>
                    </td>
                    <td class="small" style="min-width:130px">
                        <?php if ($c['valid_from'] || $c['valid_until']): ?>
                            <?= $c['valid_from']  ? formatDate($c['valid_from'])  : '&mdash;' ?><br>
                            <span class="text-muted">to</span> <?= $c['valid_until'] ? formatDate($c['valid_until']) : '&mdash;' ?>
                        <?php else: ?>
                            <span class="text-muted">No limit</span>
                        <?php endif; ?>
                    </td>
                    <td class="small">
                        <?= number_format($c['usage_count'] ?? 0) ?>
                        <?php if ($c['usage_limit']): ?> / <?= number_format($c['usage_limit']) ?><?php endif; ?>
                        <?php if (!empty($c['redemption_count'])): ?>
                            <div class="text-muted"><?= $c['redemption_count'] ?> redeemed</div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        $ap = $c['approval_status'] ?? 'pending';
                        $apBadge = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'];
                        ?>
                        <span class="badge bg-<?= $apBadge[$ap] ?? 'secondary' ?>"><?= ucfirst($ap) ?></span>
                    </td>
                    <td>
                        <?php $st = $c['status'] ?? 'inactive'; ?>
                        <form method="POST" action="<?= BASE_URL ?>/coupons/toggle" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                            <input type="hidden" name="redirect" value="coupons">
                            <button type="submit" class="btn btn-sm <?= $st === 'active' ? 'btn-success' : 'btn-outline-secondary' ?>"
                                title="<?= $st === 'active' ? 'Deactivate' : 'Activate' ?>">
                                <?= $st === 'active' ? 'Active' : ucfirst($st) ?>
                            </button>
                        </form>
                    </td>
                    <td class="text-end" style="white-space:nowrap">
                        <!-- Approve if pending -->
                        <?php if ($ap === 'pending'): ?>
                        <form method="POST" action="<?= BASE_URL ?>/coupons/approve" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                            <input type="hidden" name="redirect" value="coupons">
                            <button class="btn btn-sm btn-outline-success" title="Approve"><i class="fas fa-check"></i></button>
                        </form>
                        <?php endif; ?>
                        <a href="<?= BASE_URL ?>/coupons/detail?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-info" title="View"><i class="fas fa-eye"></i></a>
                        <a href="<?= BASE_URL ?>/coupons/edit?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-edit"></i></a>
                        <button class="btn btn-sm btn-outline-danger" title="Delete"
                            onclick="confirmDelete(<?= $c['id'] ?>, '<?= escape(addslashes($c['title'])) ?>')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div class="d-flex justify-content-center mt-3">
    <nav>
        <ul class="pagination pagination-sm">
            <?php
            $q = $filters;
            $buildUrl = function($page) use ($q) {
                $q['page'] = $page;
                return BASE_URL . '/coupons?' . http_build_query(array_filter($q, fn($v) => $v !== ''));
            };
            ?>
            <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $buildUrl($currentPage - 1) ?>">‹</a>
            </li>
            <?php
            $start = max(1, $currentPage - 2);
            $end   = min($totalPages, $currentPage + 2);
            if ($start > 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif;
            for ($p = $start; $p <= $end; $p++): ?>
            <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
                <a class="page-link" href="<?= $buildUrl($p) ?>"><?= $p ?></a>
            </li>
            <?php endfor;
            if ($end < $totalPages): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif;
            ?>
            <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $buildUrl($currentPage + 1) ?>">›</a>
            </li>
        </ul>
    </nav>
</div>
<?php endif; ?>
<?php endif; ?>

<!-- Hidden delete form -->
<form id="deleteForm" method="POST" action="<?= BASE_URL ?>/coupons/delete" style="display:none">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
    <input type="hidden" name="id" id="deleteId">
</form>

<script>
function confirmDelete(id, name) {
    Swal.fire({
        title: 'Delete Coupon?',
        html: `Are you sure you want to delete <strong>${name}</strong>?<br>All redemptions and tags will also be removed.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Yes, delete it',
    }).then(r => {
        if (r.isConfirmed) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteForm').submit();
        }
    });
}
</script>
