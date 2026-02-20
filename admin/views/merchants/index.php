<?php /* views/merchants/index.php */
$profileColors = [
    'approved' => 'success',
    'pending'  => 'warning',
    'rejected' => 'danger',
];
$subsColors = [
    'active'  => 'success',
    'trial'   => 'info',
    'expired' => 'secondary',
];
$statusColors = [
    'active'   => 'success',
    'inactive' => 'secondary',
    'blocked'  => 'danger',
    'pending'  => 'warning',
];
?>

<?php if ($flash_success): ?><div class="alert alert-success alert-dismissible fade show border-0 shadow-sm"><i class="fas fa-check-circle me-2"></i><?= escape($flash_success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<?php if ($flash_error):   ?><div class="alert alert-danger  alert-dismissible fade show border-0 shadow-sm"><i class="fas fa-exclamation-circle me-2"></i><?= escape($flash_error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Merchant Management</h1>
        <p class="text-muted mb-0 small">View, search and manage registered merchants.</p>
    </div>
    <a href="<?= BASE_URL ?>merchants/add" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i> Add Merchant
    </a>
</div>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm text-center h-100">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-dark"><?= number_format($stats['total']) ?></div>
                <div class="text-muted small">Total</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm text-center h-100">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-success"><?= number_format($stats['active']) ?></div>
                <div class="text-muted small">Active</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm text-center h-100" style="border-top:3px solid var(--bs-warning) !important;">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-warning"><?= number_format($stats['pending']) ?></div>
                <div class="text-muted small">Pending</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm text-center h-100" style="border-top:3px solid var(--bs-success) !important;">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-success"><?= number_format($stats['approved']) ?></div>
                <div class="text-muted small">Approved</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm text-center h-100" style="border-top:3px solid var(--bs-info) !important;">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-info"><?= number_format($stats['subscribed']) ?></div>
                <div class="text-muted small">Subscribed</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm text-center h-100" style="border-top:3px solid var(--bs-purple,#6f42c1) !important;">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold" style="color:#6f42c1"><?= number_format($stats['premium']) ?></div>
                <div class="text-muted small">Premium</div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2 px-3">
        <form method="GET" action="<?= BASE_URL ?>merchants" class="row g-2 align-items-center">
            <div class="col-auto flex-grow-1">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Search by business name, email, phone, GST…"
                       value="<?= escape($filters['search']) ?>">
            </div>
            <div class="col-auto">
                <select name="profile_status" class="form-select form-select-sm">
                    <option value="">All Profiles</option>
                    <option value="pending"  <?= $filters['profile_status'] === 'pending'  ? 'selected' : '' ?>>Pending</option>
                    <option value="approved" <?= $filters['profile_status'] === 'approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="rejected" <?= $filters['profile_status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                </select>
            </div>
            <div class="col-auto">
                <select name="subscription_status" class="form-select form-select-sm">
                    <option value="">All Subscriptions</option>
                    <option value="active"  <?= $filters['subscription_status'] === 'active'  ? 'selected' : '' ?>>Active</option>
                    <option value="trial"   <?= $filters['subscription_status'] === 'trial'   ? 'selected' : '' ?>>Trial</option>
                    <option value="expired" <?= $filters['subscription_status'] === 'expired' ? 'selected' : '' ?>>Expired</option>
                </select>
            </div>
            <div class="col-auto">
                <select name="is_premium" class="form-select form-select-sm">
                    <option value="">All Tiers</option>
                    <option value="1" <?= $filters['is_premium'] === '1' ? 'selected' : '' ?>>Premium</option>
                    <option value="0" <?= $filters['is_premium'] === '0' ? 'selected' : '' ?>>Standard</option>
                </select>
            </div>
            <div class="col-auto">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All User Statuses</option>
                    <option value="active"  <?= $filters['status'] === 'active'  ? 'selected' : '' ?>>Active</option>
                    <option value="blocked" <?= $filters['status'] === 'blocked' ? 'selected' : '' ?>>Blocked</option>
                    <option value="inactive"<?= $filters['status'] === 'inactive'? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div class="col-auto d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search me-1"></i> Filter</button>
                <a href="<?= BASE_URL ?>merchants" class="btn btn-sm btn-light">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive p-3">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Business</th>
                        <th>Contact</th>
                        <th>Profile</th>
                        <th>Subscription</th>
                        <th class="text-center">User Status</th>
                        <th class="text-center">Stores</th>
                        <th>Registered</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($merchants)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-5">
                        <i class="fas fa-store fa-2x mb-2 d-block opacity-25"></i>No merchants found.
                    </td></tr>
                    <?php else: ?>
                    <?php foreach ($merchants as $i => $m): ?>
                    <tr>
                        <td class="text-muted small"><?= ($currentPage - 1) * $perPage + $i + 1 ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded bg-primary d-flex align-items-center justify-content-center text-white fw-bold flex-shrink-0"
                                     style="width:38px;height:38px;font-size:.85rem;">
                                    <?= strtoupper(mb_substr($m['business_name'], 0, 2)) ?>
                                </div>
                                <div>
                                    <a href="<?= BASE_URL ?>merchants/profile?id=<?= $m['id'] ?>" class="fw-semibold text-decoration-none">
                                        <?= escape($m['business_name']) ?>
                                    </a>
                                    <?php if ($m['is_premium']): ?>
                                    <span class="badge ms-1 text-white small" style="background:#6f42c1">Premium</span>
                                    <?php endif; ?>
                                    <?php if ($m['label_name']): ?>
                                    <span class="badge bg-info-subtle text-info border border-info-subtle ms-1 small"><?= escape($m['label_name']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if ($m['email']): ?><div class="small"><?= escape($m['email']) ?></div><?php endif; ?>
                            <?php if ($m['phone']): ?><div class="text-muted small"><?= escape($m['phone']) ?></div><?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-<?= $profileColors[$m['profile_status']] ?? 'secondary' ?> rounded-pill">
                                <?= ucfirst($m['profile_status']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-<?= $subsColors[$m['subscription_status']] ?? 'secondary' ?> rounded-pill">
                                <?= ucfirst($m['subscription_status']) ?>
                            </span>
                            <?php if ($m['subscription_expiry']): ?>
                            <div class="text-muted small mt-1">Exp: <?= formatDate($m['subscription_expiry']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <form method="POST" action="<?= BASE_URL ?>merchants/toggle" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                <input type="hidden" name="redirect" value="merchants">
                                <button type="submit" class="badge border-0 bg-<?= $statusColors[$m['status']] ?? 'secondary' ?> p-2"
                                        style="cursor:pointer;" title="Click to toggle">
                                    <?= ucfirst($m['status']) ?>
                                </button>
                            </form>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border"><?= (int)$m['store_count'] ?></span>
                        </td>
                        <td class="text-muted small"><?= isset($m['registered_at']) ? formatDate($m['registered_at']) : '—' ?></td>
                        <td class="text-center text-nowrap">
                            <a href="<?= BASE_URL ?>merchants/profile?id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-info me-1" title="View Profile"><i class="fas fa-eye"></i></a>
                            <a href="<?= BASE_URL ?>merchants/edit?id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-primary me-1" title="Edit"><i class="fas fa-edit"></i></a>
                            <?php if ($m['profile_status'] === 'pending'): ?>
                            <form method="POST" action="<?= BASE_URL ?>merchants/approve" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                <input type="hidden" name="redirect" value="merchants">
                                <button type="submit" class="btn btn-sm btn-outline-success me-1" title="Approve"><i class="fas fa-check"></i></button>
                            </form>
                            <?php endif; ?>
                            <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?= $m['id'] ?>, '<?= escape($m['business_name']) ?>')" title="Delete"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1 || $totalCount > 0): ?>
<div class="d-flex flex-column flex-sm-row align-items-center justify-content-between gap-2 mt-3">
    <div class="text-muted small">
        <?php
            $from = $totalCount === 0 ? 0 : ($currentPage - 1) * $perPage + 1;
            $to   = min($currentPage * $perPage, $totalCount);
            echo "Showing <strong>{$from}–{$to}</strong> of <strong>{$totalCount}</strong> merchants";
        ?>
    </div>
    <?php if ($totalPages > 1): ?>
    <nav aria-label="Merchant pagination">
        <ul class="pagination pagination-sm mb-0">
            <?php
                $qp = array_filter([
                    'search'              => $filters['search'],
                    'status'              => $filters['status'],
                    'profile_status'      => $filters['profile_status'],
                    'subscription_status' => $filters['subscription_status'],
                    'is_premium'          => $filters['is_premium'],
                ]);
                $base = BASE_URL . 'merchants?' . ($qp ? http_build_query($qp) . '&' : '');
            ?>
            <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $base ?>page=<?= $currentPage - 1 ?>">&laquo;</a>
            </li>
            <?php
                $window = 2;
                $start  = max(1, $currentPage - $window);
                $end    = min($totalPages, $currentPage + $window);
            ?>
            <?php if ($start > 1): ?>
                <li class="page-item"><a class="page-link" href="<?= $base ?>page=1">1</a></li>
                <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">&hellip;</span></li><?php endif; ?>
            <?php endif; ?>
            <?php for ($p = $start; $p <= $end; $p++): ?>
                <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
                    <a class="page-link" href="<?= $base ?>page=<?= $p ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
            <?php if ($end < $totalPages): ?>
                <?php if ($end < $totalPages - 1): ?><li class="page-item disabled"><span class="page-link">&hellip;</span></li><?php endif; ?>
                <li class="page-item"><a class="page-link" href="<?= $base ?>page=<?= $totalPages ?>"><?= $totalPages ?></a></li>
            <?php endif; ?>
            <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $base ?>page=<?= $currentPage + 1 ?>">&raquo;</a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Delete Form -->
<form method="POST" action="<?= BASE_URL ?>merchants/delete" id="deleteForm">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
    <input type="hidden" name="id" id="deleteId">
</form>

<script>
function confirmDelete(id, name) {
    Swal.fire({
        title: 'Delete Merchant?',
        html: `Permanently delete <b>${name}</b> and all associated stores?`,
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
