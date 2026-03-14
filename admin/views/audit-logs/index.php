<?php /* views/audit-logs/index.php */ ?>

<!-- Stats -->
<div class="row g-3 mb-4">
    <?php foreach ([
        ['label' => 'Total',    'value' => $stats['total']    ?? 0, 'color' => 'primary'],
        ['label' => 'Admin',    'value' => $stats['admin']    ?? 0, 'color' => 'danger'],
        ['label' => 'Merchant', 'value' => $stats['merchant'] ?? 0, 'color' => 'warning'],
        ['label' => 'Customer', 'value' => $stats['customer'] ?? 0, 'color' => 'info'],
        ['label' => 'Today',    'value' => $stats['today']    ?? 0, 'color' => 'success'],
    ] as $c): ?>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card text-bg-<?= $c['color'] ?> h-100 shadow-sm">
            <div class="card-body py-3 px-3">
                <div class="small fw-semibold text-white-50"><?= $c['label'] ?></div>
                <div class="fs-4 fw-bold"><?= number_format($c['value']) ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Header -->
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="mb-0">Audit Logs</h4>
        <small class="text-muted">
            <?php
            $from = min($totalCount, ($currentPage - 1) * $perPage + 1);
            $to   = min($totalCount, $currentPage * $perPage);
            echo $totalCount ? "Showing {$from}&ndash;{$to} of {$totalCount}" : 'No entries found';
            ?>
        </small>
    </div>
    <a href="<?= BASE_URL ?>audit-logs/export?<?= http_build_query($filters) ?>"
       class="btn btn-sm btn-outline-success">
        <i class="fas fa-file-csv me-1"></i> Export CSV
    </a>
</div>

<!-- Filters -->
<div class="card mb-3 shadow-sm">
    <div class="card-body py-2 px-3">
        <form method="GET" action="<?= BASE_URL ?>audit-logs" class="row g-2 align-items-end">
            <div class="col-6 col-md-3">
                <label class="form-label form-label-sm mb-1">User Type</label>
                <select name="user_type" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="admin"    <?= ($filters['user_type'] ?? '') === 'admin'    ? 'selected' : '' ?>>Admin</option>
                    <option value="merchant" <?= ($filters['user_type'] ?? '') === 'merchant' ? 'selected' : '' ?>>Merchant</option>
                    <option value="customer" <?= ($filters['user_type'] ?? '') === 'customer' ? 'selected' : '' ?>>Customer</option>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label form-label-sm mb-1">Action</label>
                <select name="action" class="form-select form-select-sm">
                    <option value="">All Actions</option>
                    <?php foreach ($actions as $a): ?>
                    <option value="<?= escape($a) ?>" <?= ($filters['action'] ?? '') === $a ? 'selected' : '' ?>>
                        <?= escape($a) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label form-label-sm mb-1">Table</label>
                <select name="table_name" class="form-select form-select-sm">
                    <option value="">All Tables</option>
                    <?php foreach ($tables as $t): ?>
                    <option value="<?= escape($t) ?>" <?= ($filters['table_name'] ?? '') === $t ? 'selected' : '' ?>>
                        <?= escape($t) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label form-label-sm mb-1">Date From</label>
                <input type="date" name="date_from" class="form-control form-control-sm"
                       value="<?= escape($filters['date_from'] ?? '') ?>">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label form-label-sm mb-1">Date To</label>
                <input type="date" name="date_to" class="form-control form-control-sm"
                       value="<?= escape($filters['date_to'] ?? '') ?>">
            </div>
            <div class="col-12 col-md-auto ms-md-auto d-flex gap-2">
                <button class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i> Filter</button>
                <a href="<?= BASE_URL ?>audit-logs" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<?php
$typeColors = ['admin' => 'danger', 'merchant' => 'warning', 'customer' => 'info'];
?>

<?php if (empty($logs)): ?>
<div class="alert alert-info border-0 shadow-sm"><i class="fas fa-info-circle me-2"></i>No audit log entries found.</div>
<?php else: ?>
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Actor</th>
                    <th>Type</th>
                    <th>Action</th>
                    <th>Table</th>
                    <th>Record</th>
                    <th>IP</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
            <tr>
                <td class="text-muted small"><?= $log['id'] ?></td>
                <td class="small text-nowrap text-muted">
                    <?= date('d M Y H:i', strtotime($log['created_at'])) ?>
                </td>
                <td class="small"><?= escape($log['actor_name'] ?? '&mdash;') ?></td>
                <td>
                    <span class="badge bg-<?= $typeColors[$log['user_type']] ?? 'secondary' ?>">
                        <?= ucfirst($log['user_type'] ?? '?') ?>
                    </span>
                </td>
                <td><code class="small"><?= escape($log['action']) ?></code></td>
                <td class="small text-muted"><?= escape($log['table_name'] ?? '&mdash;') ?></td>
                <td class="small text-muted"><?= $log['record_id'] ?? '&mdash;' ?></td>
                <td class="small font-monospace text-muted"><?= escape($log['ip_address'] ?? '&mdash;') ?></td>
                <td>
                    <a href="<?= BASE_URL ?>audit-logs/detail?id=<?= $log['id'] ?>" class="btn btn-xs btn-outline-primary btn-sm py-0 px-2">
                        <i class="fas fa-eye"></i>
                    </a>
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
    <nav><ul class="pagination pagination-sm mb-0">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"><?= $p ?></a>
        </li>
        <?php endfor; ?>
    </ul></nav>
</div>
<?php endif; ?>
<?php endif; ?>
