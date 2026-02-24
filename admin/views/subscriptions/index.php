<?php /* views/subscriptions/index.php */ ?>

<!-- Stats -->
<div class="row g-3 mb-4">
    <?php
    $statCards = [
        ['label' => 'Total',          'value' => $stats['total']         ?? 0, 'color' => 'primary'],
        ['label' => 'Active',         'value' => $stats['active']        ?? 0, 'color' => 'success'],
        ['label' => 'Expired',        'value' => $stats['expired']       ?? 0, 'color' => 'warning'],
        ['label' => 'Cancelled',      'value' => $stats['cancelled']     ?? 0, 'color' => 'danger'],
        ['label' => 'Merchants',      'value' => $stats['merchants']     ?? 0, 'color' => 'info'],
        ['label' => 'Customers',      'value' => $stats['customers']     ?? 0, 'color' => 'secondary'],
        ['label' => 'Expiring ≤30d',  'value' => $stats['expiring_soon'] ?? 0, 'color' => 'warning'],
        ['label' => 'Revenue (₹)',    'value' => '₹' . number_format($stats['total_revenue'] ?? 0, 0), 'color' => 'dark'],
    ];
    foreach ($statCards as $c):
    ?>
    <div class="col-6 col-md-3 col-xl-<?= count($statCards) <= 6 ? 2 : 'auto' ?>">
        <div class="card text-bg-<?= $c['color'] ?> h-100 shadow-sm">
            <div class="card-body py-3 px-3">
                <div class="small fw-semibold text-white-50"><?= $c['label'] ?></div>
                <div class="fs-4 fw-bold"><?= is_numeric($c['value']) ? number_format($c['value']) : $c['value'] ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Page header -->
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="mb-0">Subscription Management</h4>
        <small class="text-muted">
            <?php
            $from = min($totalCount, ($currentPage - 1) * $perPage + 1);
            $to   = min($totalCount, $currentPage * $perPage);
            echo $totalCount ? "Showing {$from}–{$to} of {$totalCount}" : 'No subscriptions found';
            ?>
        </small>
    </div>
    <div class="d-flex gap-2">
        <?php if (!empty($stats['expiring_soon'])): ?>
        <a href="<?= BASE_URL ?>subscriptions?expiring_soon=1" class="btn btn-warning btn-sm">
            <i class="fas fa-exclamation-triangle me-1"></i> <?= $stats['expiring_soon'] ?> Expiring Soon
        </a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>subscriptions/add" class="btn btn-primary btn-sm">
            <i class="fas fa-plus me-1"></i> Create Subscription
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card mb-3 shadow-sm">
    <div class="card-body py-2 px-3">
        <form method="GET" action="<?= BASE_URL ?>subscriptions" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label form-label-sm mb-1">Search</label>
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Name / email / phone…" value="<?= escape($filters['search'] ?? '') ?>">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label form-label-sm mb-1">User Type</label>
                <select name="user_type" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="merchant"  <?= ($filters['user_type'] ?? '') === 'merchant'  ? 'selected' : '' ?>>Merchant</option>
                    <option value="customer"  <?= ($filters['user_type'] ?? '') === 'customer'  ? 'selected' : '' ?>>Customer</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label form-label-sm mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <?php foreach (['active', 'expired', 'cancelled'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label form-label-sm mb-1">Plan</label>
                <select name="plan_type" class="form-select form-select-sm">
                    <option value="">All</option>
                    <?php foreach (['basic', 'standard', 'premium'] as $p): ?>
                    <option value="<?= $p ?>" <?= ($filters['plan_type'] ?? '') === $p ? 'selected' : '' ?>><?= ucfirst($p) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-auto ms-md-auto d-flex gap-2">
                <button class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i>Filter</button>
                <a href="<?= BASE_URL ?>subscriptions" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($subscriptions)): ?>
        <div class="alert alert-info m-3 border-0"><i class="fas fa-info-circle me-2"></i>No subscriptions found.</div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Plan</th>
                        <th>Start</th>
                        <th>Expiry</th>
                        <th>Status</th>
                        <th>Amount</th>
                        <th>Payment</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $statusColors = ['active' => 'success', 'expired' => 'warning', 'cancelled' => 'danger'];
                foreach ($subscriptions as $s):
                    $daysLeft = (int)ceil((strtotime($s['expiry_date']) - time()) / 86400);
                    $rowClass = ($s['status'] === 'active' && $daysLeft <= 7) ? 'table-warning' :
                                ($s['status'] === 'expired' ? 'table-danger bg-opacity-25' : '');
                ?>
                    <tr class="<?= $rowClass ?>">
                        <td class="text-muted small"><?= $s['id'] ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>subscriptions/detail?id=<?= $s['id'] ?>" class="fw-semibold text-decoration-none">
                                <?= escape($s['display_name']) ?>
                            </a>
                            <div class="text-muted small"><?= escape($s['user_email'] ?? $s['user_phone'] ?? '') ?></div>
                        </td>
                        <td><span class="badge bg-<?= $s['user_type'] === 'merchant' ? 'info' : 'secondary' ?>-subtle text-<?= $s['user_type'] === 'merchant' ? 'info' : 'secondary' ?> border"><?= ucfirst($s['user_type']) ?></span></td>
                        <td><?= ucfirst($s['plan_type']) ?></td>
                        <td class="text-muted small"><?= date('d M Y', strtotime($s['start_date'])) ?></td>
                        <td class="small">
                            <?= date('d M Y', strtotime($s['expiry_date'])) ?>
                            <?php if ($s['status'] === 'active' && $daysLeft >= 0): ?>
                            <div class="text-<?= $daysLeft <= 7 ? 'danger' : ($daysLeft <= 30 ? 'warning' : 'muted') ?> small">
                                <?= $daysLeft ?> days
                            </div>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge bg-<?= $statusColors[$s['status']] ?? 'secondary' ?>"><?= ucfirst($s['status']) ?></span></td>
                        <td class="text-end">₹<?= $s['payment_amount'] !== null ? number_format($s['payment_amount'], 0) : '—' ?></td>
                        <td class="text-muted small"><?= ucfirst($s['payment_method'] ?? '—') ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>subscriptions/detail?id=<?= $s['id'] ?>" class="btn btn-xs btn-outline-primary py-0 px-1">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="d-flex justify-content-center py-3">
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
    </div>
</div>
