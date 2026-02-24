<?php /* views/gift-coupons/index.php */ ?>

<!-- Stats -->
<div class="row g-3 mb-4">
    <?php foreach ([
        ['label' => 'Total Gifted',  'value' => $stats['total']         ?? 0, 'color' => 'primary'],
        ['label' => 'Pending',       'value' => $stats['pending']        ?? 0, 'color' => 'warning'],
        ['label' => 'Accepted',      'value' => $stats['accepted']       ?? 0, 'color' => 'success'],
        ['label' => 'Rejected',      'value' => $stats['rejected']       ?? 0, 'color' => 'danger'],
        ['label' => 'Expiring ≤3d',  'value' => $stats['expiring_soon']  ?? 0, 'color' => 'dark'],
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
        <h4 class="mb-0">Gift Coupon Management</h4>
        <small class="text-muted">
            <?php
            $from = min($totalCount, ($currentPage - 1) * $perPage + 1);
            $to   = min($totalCount, $currentPage * $perPage);
            echo $totalCount ? "Showing {$from}–{$to} of {$totalCount}" : 'No gift coupons found';
            ?>
        </small>
    </div>
    <div class="d-flex gap-2">
        <?php if (($stats['expiring_soon'] ?? 0) > 0): ?>
        <a href="?expiring_soon=1" class="btn btn-sm btn-outline-danger">
            <i class="fas fa-hourglass-half me-1"></i> <?= $stats['expiring_soon'] ?> Expiring Soon
        </a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>gift-coupons/add" class="btn btn-sm btn-primary">
            <i class="fas fa-gift me-1"></i> Gift a Coupon
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card mb-3 shadow-sm">
    <div class="card-body py-2 px-3">
        <form method="GET" action="<?= BASE_URL ?>gift-coupons" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label form-label-sm mb-1">Search</label>
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Customer name, coupon, phone…" value="<?= escape($filters['search'] ?? '') ?>">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label form-label-sm mb-1">Status</label>
                <select name="acceptance_status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="pending"  <?= ($filters['acceptance_status'] ?? '') === 'pending'  ? 'selected' : '' ?>>Pending</option>
                    <option value="accepted" <?= ($filters['acceptance_status'] ?? '') === 'accepted' ? 'selected' : '' ?>>Accepted</option>
                    <option value="rejected" <?= ($filters['acceptance_status'] ?? '') === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                    <option value="null"     <?= ($filters['acceptance_status'] ?? '') === 'null'     ? 'selected' : '' ?>>No Acceptance Required</option>
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
                <a href="<?= BASE_URL ?>gift-coupons" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<?php
$statusBadge = [
    'pending'  => ['bg-warning text-dark', 'Pending'],
    'accepted' => ['bg-success',           'Accepted'],
    'rejected' => ['bg-danger',            'Rejected'],
];
$now = time();
?>

<?php if (empty($gifts)): ?>
<div class="alert alert-info border-0 shadow-sm"><i class="fas fa-info-circle me-2"></i>No gift coupons found.</div>
<?php else: ?>
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Coupon</th>
                    <th>Customer</th>
                    <th>Gifted By</th>
                    <th>Acceptance</th>
                    <th>Gifted At</th>
                    <th>Expires</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($gifts as $g):
                $isExpired     = $g['expires_at'] && strtotime($g['expires_at']) < $now;
                $isExpiringSoon = $g['expires_at'] && strtotime($g['expires_at']) > $now && strtotime($g['expires_at']) <= strtotime('+3 days');
                $rowClass = $isExpired ? 'table-secondary' : ($isExpiringSoon ? 'table-warning' : '');
            ?>
            <tr class="<?= $rowClass ?>">
                <td class="text-muted small"><?= $g['id'] ?></td>
                <td>
                    <div class="fw-semibold small"><?= escape($g['coupon_title']) ?></div>
                    <code class="text-muted" style="font-size:0.75rem;"><?= escape($g['coupon_code']) ?></code>
                </td>
                <td>
                    <div class="small"><?= escape($g['customer_name']) ?></div>
                    <div class="text-muted" style="font-size:0.75rem;"><?= escape($g['customer_phone']) ?></div>
                </td>
                <td class="small"><?= escape($g['gifted_by']) ?></td>
                <td>
                    <?php if (!$g['requires_acceptance']): ?>
                        <span class="badge bg-secondary">Auto-gifted</span>
                    <?php elseif (isset($statusBadge[$g['acceptance_status']])): ?>
                        <span class="badge <?= $statusBadge[$g['acceptance_status']][0] ?>"><?= $statusBadge[$g['acceptance_status']][1] ?></span>
                    <?php else: ?>
                        <span class="badge bg-secondary">—</span>
                    <?php endif; ?>
                </td>
                <td class="small text-muted text-nowrap"><?= date('d M Y', strtotime($g['gifted_at'])) ?></td>
                <td class="small text-nowrap">
                    <?php if (!$g['expires_at']): ?>
                        <span class="text-muted">No expiry</span>
                    <?php elseif ($isExpired): ?>
                        <span class="text-danger"><i class="fas fa-times me-1"></i><?= date('d M Y', strtotime($g['expires_at'])) ?></span>
                    <?php elseif ($isExpiringSoon): ?>
                        <span class="text-warning fw-semibold"><i class="fas fa-hourglass-half me-1"></i><?= date('d M Y', strtotime($g['expires_at'])) ?></span>
                    <?php else: ?>
                        <?= date('d M Y', strtotime($g['expires_at'])) ?>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="<?= BASE_URL ?>gift-coupons/detail?id=<?= $g['id'] ?>" class="btn btn-sm btn-outline-primary">
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
