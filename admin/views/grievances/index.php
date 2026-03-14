<?php /* views/grievances/index.php */
$statusColors   = ['open' => 'danger', 'in_progress' => 'warning', 'resolved' => 'success', 'closed' => 'secondary'];
$priorityColors = ['low' => 'info', 'medium' => 'primary', 'high' => 'warning', 'urgent' => 'danger'];
?>

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
        <div class="card text-bg-danger h-100 shadow-sm">
            <div class="card-body py-3 px-3">
                <div class="small fw-semibold text-white-50">Open</div>
                <div class="fs-4 fw-bold"><?= number_format($stats['open'] ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card text-bg-warning h-100 shadow-sm">
            <div class="card-body py-3 px-3">
                <div class="small fw-semibold text-white-50">In Progress</div>
                <div class="fs-4 fw-bold"><?= number_format($stats['in_progress'] ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card text-bg-success h-100 shadow-sm">
            <div class="card-body py-3 px-3">
                <div class="small fw-semibold text-white-50">Resolved</div>
                <div class="fs-4 fw-bold"><?= number_format($stats['resolved'] ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card text-bg-secondary h-100 shadow-sm">
            <div class="card-body py-3 px-3">
                <div class="small fw-semibold text-white-50">Closed</div>
                <div class="fs-4 fw-bold"><?= number_format($stats['closed'] ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card text-bg-dark h-100 shadow-sm">
            <div class="card-body py-3 px-3">
                <div class="small fw-semibold text-white-50">High/Urgent Open</div>
                <div class="fs-4 fw-bold"><?= number_format($stats['high_priority_open'] ?? 0) ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Page header -->
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="mb-0">Grievance Management</h4>
        <small class="text-muted">
            <?php
            $from = min($totalCount, ($currentPage - 1) * $perPage + 1);
            $to   = min($totalCount, $currentPage * $perPage);
            echo $totalCount ? "Showing {$from}&ndash;{$to} of {$totalCount}" : 'No grievances found';
            ?>
        </small>
    </div>
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

<!-- Filter bar -->
<div class="card mb-3 shadow-sm">
    <div class="card-body py-2 px-3">
        <form method="GET" action="<?= BASE_URL ?>grievances" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label form-label-sm mb-1">Search</label>
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Subject, customer or merchant…" value="<?= escape($filters['search']) ?>">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label form-label-sm mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <?php foreach (['open', 'in_progress', 'resolved', 'closed'] as $s): ?>
                        <option value="<?= $s ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>>
                            <?= ucfirst(str_replace('_', ' ', $s)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label form-label-sm mb-1">Priority</label>
                <select name="priority" class="form-select form-select-sm">
                    <option value="">All Priorities</option>
                    <?php foreach (['low', 'medium', 'high', 'urgent'] as $p): ?>
                        <option value="<?= $p ?>" <?= $filters['priority'] === $p ? 'selected' : '' ?>>
                            <?= ucfirst($p) ?>
                        </option>
                    <?php endforeach; ?>
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
                <a href="<?= BASE_URL ?>grievances" class="btn btn-sm btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Grievances Table -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($grievances)): ?>
        <div class="text-center text-muted py-5">
            <i class="fas fa-comments fa-3x mb-3 opacity-25"></i>
            <p class="mb-0">No grievances found.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Subject</th>
                        <th>Customer</th>
                        <th>Merchant</th>
                        <th class="text-center">Priority</th>
                        <th class="text-center">Status</th>
                        <th>Filed</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($grievances as $g): ?>
                    <tr>
                        <td class="text-muted small">#<?= $g['id'] ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>grievances/detail?id=<?= $g['id'] ?>"
                               class="text-decoration-none fw-semibold d-block"
                               style="max-width:220px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"
                               title="<?= escape($g['subject']) ?>">
                                <?= escape($g['subject']) ?>
                            </a>
                            <?php if ($g['store_name']): ?>
                                <span class="text-muted small"><?= escape($g['store_name']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= BASE_URL ?>customers/profile?id=<?= $g['customer_id'] ?>"
                               class="text-decoration-none small"><?= escape($g['customer_name']) ?></a>
                            <?php if ($g['customer_phone']): ?>
                                <div class="text-muted" style="font-size:0.75rem;"><?= escape($g['customer_phone']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= BASE_URL ?>merchants/profile?id=<?= $g['merchant_id'] ?>"
                               class="text-decoration-none small"><?= escape($g['merchant_name']) ?></a>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-<?= $priorityColors[$g['priority']] ?? 'secondary' ?>">
                                <?= ucfirst($g['priority']) ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-<?= $statusColors[$g['status']] ?? 'secondary' ?>">
                                <?= ucfirst(str_replace('_', ' ', $g['status'])) ?>
                            </span>
                        </td>
                        <td class="small text-muted"><?= formatDate($g['created_at']) ?></td>
                        <td class="text-center text-nowrap">
                            <a href="<?= BASE_URL ?>grievances/detail?id=<?= $g['id'] ?>"
                               class="btn btn-sm btn-outline-primary" title="View Details">
                                <i class="fas fa-eye"></i>
                            </a>
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
            <a class="page-link" href="<?= BASE_URL ?>grievances?<?= http_build_query(array_merge($filters, ['page' => $currentPage - 1])) ?>">
                <i class="fas fa-chevron-left"></i>
            </a>
        </li>
        <?php endif; ?>

        <?php for ($p = max(1, $currentPage - 2); $p <= min($totalPages, $currentPage + 2); $p++): ?>
        <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
            <a class="page-link" href="<?= BASE_URL ?>grievances?<?= http_build_query(array_merge($filters, ['page' => $p])) ?>"><?= $p ?></a>
        </li>
        <?php endfor; ?>

        <?php if ($currentPage < $totalPages): ?>
        <li class="page-item">
            <a class="page-link" href="<?= BASE_URL ?>grievances?<?= http_build_query(array_merge($filters, ['page' => $currentPage + 1])) ?>">
                <i class="fas fa-chevron-right"></i>
            </a>
        </li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>
