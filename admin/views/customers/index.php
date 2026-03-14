<?php /* views/customers/index.php */
$typeColors = [
    'standard'  => 'primary',
    'premium'   => 'warning',
    'dealmaker' => 'success',
];
$statusColors = [
    'active'   => 'success',
    'inactive' => 'secondary',
    'blocked'  => 'danger',
    'pending'  => 'warning',
];
$regTypeLabels = [
    'self_registration'  => 'Self',
    'merchant_app'       => 'Merchant App',
    'admin_registration' => 'Admin',
    'preprinted_card'    => 'Card',
    'auto_profile'       => 'Auto',
];
?>

<?php if ($flash_success): ?><div class="alert alert-success alert-dismissible fade show border-0 shadow-sm"><i class="fas fa-check-circle me-2"></i><?= escape($flash_success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<?php if ($flash_error):   ?><div class="alert alert-danger  alert-dismissible fade show border-0 shadow-sm"><i class="fas fa-exclamation-circle me-2"></i><?= escape($flash_error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Customer Management</h1>
        <p class="text-muted mb-0 small">View, search and manage registered customers.</p>
    </div>
    <a href="<?= BASE_URL ?>customers/add" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i> Add Customer
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
        <div class="card border-0 shadow-sm text-center h-100">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-danger"><?= number_format($stats['blocked']) ?></div>
                <div class="text-muted small">Blocked</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm text-center h-100" style="border-top:3px solid var(--bs-warning) !important;">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-warning"><?= number_format($stats['premium']) ?></div>
                <div class="text-muted small">Premium</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm text-center h-100" style="border-top:3px solid var(--bs-success) !important;">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-success"><?= number_format($stats['dealmakers']) ?></div>
                <div class="text-muted small">DealMakers</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm text-center h-100" style="border-top:3px solid var(--bs-info) !important;">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-info"><?= number_format($stats['today']) ?></div>
                <div class="text-muted small">Today</div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2 px-3">
        <form method="GET" action="<?= BASE_URL ?>customers" class="row g-2 align-items-center">
            <div class="col-auto flex-grow-1">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by name, email, phone or referral code…" value="<?= escape($filters['search']) ?>">
            </div>
            <div class="col-auto">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="active"   <?= $filters['status'] === 'active'   ? 'selected' : '' ?>>Active</option>
                    <option value="blocked"  <?= $filters['status'] === 'blocked'  ? 'selected' : '' ?>>Blocked</option>
                    <option value="inactive" <?= $filters['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    <option value="pending"  <?= $filters['status'] === 'pending'  ? 'selected' : '' ?>>Pending</option>
                </select>
            </div>
            <div class="col-auto">
                <select name="customer_type" class="form-select form-select-sm">
                    <option value="">All Types</option>
                    <option value="standard"  <?= $filters['customer_type'] === 'standard'  ? 'selected' : '' ?>>Standard</option>
                    <option value="premium"   <?= $filters['customer_type'] === 'premium'   ? 'selected' : '' ?>>Premium</option>
                    <option value="dealmaker" <?= $filters['customer_type'] === 'dealmaker' ? 'selected' : '' ?>>DealMaker</option>
                </select>
            </div>
            <div class="col-auto">
                <select name="registration_type" class="form-select form-select-sm">
                    <option value="">All Reg. Types</option>
                    <option value="self_registration"  <?= $filters['registration_type'] === 'self_registration'  ? 'selected' : '' ?>>Self</option>
                    <option value="merchant_app"       <?= $filters['registration_type'] === 'merchant_app'       ? 'selected' : '' ?>>Merchant App</option>
                    <option value="admin_registration" <?= $filters['registration_type'] === 'admin_registration' ? 'selected' : '' ?>>Admin</option>
                    <option value="preprinted_card"    <?= $filters['registration_type'] === 'preprinted_card'    ? 'selected' : '' ?>>Card</option>
                    <option value="auto_profile"       <?= $filters['registration_type'] === 'auto_profile'       ? 'selected' : '' ?>>Auto</option>
                </select>
            </div>
            <div class="col-auto d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search me-1"></i> Filter</button>
                <a href="<?= BASE_URL ?>customers" class="btn btn-sm btn-light">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive p-3">
            <table id="customersTable" class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Customer</th>
                        <th>Contact</th>
                        <th>Type</th>
                        <th>Reg. Via</th>
                        <th class="text-center">Status</th>
                        <th>Referral Code</th>
                        <th>Registered</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($customers)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-5"><i class="fas fa-users fa-2x mb-2 d-block opacity-25"></i>No customers found.</td></tr>
                    <?php else: ?>
                    <?php foreach ($customers as $i => $c): ?>
                    <tr>
                        <td class="text-muted small"><?= ($currentPage - 1) * $perPage + $i + 1 ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center flex-shrink-0 fw-bold"
                                     style="width:36px;height:36px;font-size:.85rem;">
                                    <?= strtoupper(mb_substr($c['name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <a href="<?= BASE_URL ?>customers/profile?id=<?= $c['id'] ?>" class="fw-semibold text-decoration-none">
                                        <?= escape($c['name']) ?>
                                    </a>
                                    <?php if ($c['is_dealmaker']): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle ms-1 small">DM</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if ($c['email']): ?><div class="small"><?= escape($c['email']) ?></div><?php endif; ?>
                            <?php if ($c['phone']): ?><div class="text-muted small"><?= escape($c['phone']) ?></div><?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-<?= $typeColors[$c['customer_type']] ?? 'secondary' ?> rounded-pill">
                                <?= ucfirst($c['customer_type']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border small">
                                <?= $regTypeLabels[$c['registration_type']] ?? escape($c['registration_type']) ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <form method="POST" action="<?= BASE_URL ?>customers/toggle" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                <input type="hidden" name="redirect" value="customers">
                                <button type="submit" class="badge border-0 bg-<?= $statusColors[$c['status']] ?? 'secondary' ?> p-2"
                                        style="cursor:pointer;" title="Click to toggle status">
                                    <?= ucfirst($c['status']) ?>
                                </button>
                            </form>
                        </td>
                        <td><code class="small"><?= escape($c['referral_code'] ?? '&mdash;') ?></code></td>
                        <td class="text-muted small"><?= isset($c['registered_at']) ? formatDate($c['registered_at']) : '&mdash;' ?></td>
                        <td class="text-center text-nowrap">
                            <a href="<?= BASE_URL ?>customers/profile?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-info me-1" title="View Profile"><i class="fas fa-eye"></i></a>
                            <a href="<?= BASE_URL ?>customers/edit?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary me-1" title="Edit"><i class="fas fa-edit"></i></a>
                            <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?= $c['id'] ?>, '<?= escape($c['name']) ?>')" title="Delete"><i class="fas fa-trash"></i></button>
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
            echo "Showing <strong>{$from}&ndash;{$to}</strong> of <strong>{$totalCount}</strong> customers";
        ?>
    </div>
    <?php if ($totalPages > 1): ?>
    <nav aria-label="Customer pagination">
        <ul class="pagination pagination-sm mb-0">
            <?php
                // Build base query string (preserve filters, no page key)
                $qp = array_filter([
                    'search'            => $filters['search'],
                    'status'            => $filters['status'],
                    'customer_type'     => $filters['customer_type'],
                    'registration_type' => $filters['registration_type'],
                ]);
                $base = BASE_URL . 'customers?' . ($qp ? http_build_query($qp) . '&' : '');
            ?>
            <!-- Previous -->
            <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $base ?>page=<?= $currentPage - 1 ?>" aria-label="Previous">&laquo;</a>
            </li>
            <?php
                // Show a window of pages around current
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
            <!-- Next -->
            <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $base ?>page=<?= $currentPage + 1 ?>" aria-label="Next">&raquo;</a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Delete Form -->
<form method="POST" action="<?= BASE_URL ?>customers/delete" id="deleteForm">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
    <input type="hidden" name="id" id="deleteId">
</form>

<script>
function confirmDelete(id, name) {
    Swal.fire({
        title: 'Delete Customer?',
        html: `Permanently delete <b>${name}</b>? All associated data will be removed.`,
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
// Server-side pagination active &mdash; DataTable not used on this listing.
</script>
