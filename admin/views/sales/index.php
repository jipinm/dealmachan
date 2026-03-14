<?php /* views/sales/index.php */ ?>

<!-- Stats row -->
<div class="row g-3 mb-4">
    <?php
    $gmv      = $stats['total_gmv']       ?? 0;
    $discount = $stats['total_discount']  ?? 0;
    $avg      = $stats['avg_transaction'] ?? 0;
    $cards = [
        ['label' => 'Transactions', 'value' => number_format($stats['total_transactions'] ?? 0), 'color' => 'primary'],
        ['label' => 'Total GMV',    'value' => '₹' . number_format($gmv, 0),                   'color' => 'success'],
        ['label' => 'Avg. Ticket',  'value' => '₹' . number_format($avg, 0),                   'color' => 'info'],
        ['label' => 'Total Discount','value' => '₹' . number_format($discount, 0),             'color' => 'warning'],
        ['label' => 'With Coupon',  'value' => number_format($stats['coupon_transactions'] ?? 0),'color' => 'secondary'],
        ['label' => 'Cash',         'value' => number_format($stats['cash_count'] ?? 0),        'color' => 'dark'],
    ];
    foreach ($cards as $c):
    ?>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card text-bg-<?= $c['color'] ?> h-100 shadow-sm">
            <div class="card-body py-3 px-3">
                <div class="small fw-semibold text-white-50"><?= $c['label'] ?></div>
                <div class="fs-5 fw-bold"><?= $c['value'] ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Header -->
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="mb-0">Sales Registry</h4>
        <small class="text-muted">
            <?php
            $from = min($totalCount, ($currentPage - 1) * $perPage + 1);
            $to   = min($totalCount, $currentPage * $perPage);
            echo $totalCount ? "Showing {$from}&ndash;{$to} of {$totalCount}" : 'No sales found';
            ?>
        </small>
    </div>
    <a href="<?= BASE_URL ?>sales/export?<?= http_build_query(array_filter($filters)) ?>" class="btn btn-outline-success btn-sm">
        <i class="fas fa-file-csv me-1"></i> Export CSV
    </a>
</div>

<!-- Filters -->
<div class="card mb-3 shadow-sm">
    <div class="card-body py-2 px-3">
        <form method="GET" action="<?= BASE_URL ?>sales" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label form-label-sm mb-1">Search</label>
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Merchant / store / customer…" value="<?= escape($filters['search'] ?? '') ?>">
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
            <div class="col-6 col-md-2">
                <label class="form-label form-label-sm mb-1">Payment</label>
                <select name="payment_method" class="form-select form-select-sm">
                    <option value="">All</option>
                    <?php foreach (['cash', 'card', 'upi', 'wallet', 'other'] as $pm): ?>
                    <option value="<?= $pm ?>" <?= ($filters['payment_method'] ?? '') === $pm ? 'selected' : '' ?>><?= ucfirst($pm) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label form-label-sm mb-1">From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= escape($filters['date_from'] ?? '') ?>">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label form-label-sm mb-1">To</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= escape($filters['date_to'] ?? '') ?>">
            </div>
            <div class="col-12 col-md-auto ms-md-auto d-flex gap-2">
                <button class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i>Filter</button>
                <a href="<?= BASE_URL ?>sales" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($sales)): ?>
        <div class="alert alert-info m-3 border-0"><i class="fas fa-info-circle me-2"></i>No sales records found.</div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Business</th>
                        <th>Store</th>
                        <th>Customer</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end">Discount</th>
                        <th>Payment</th>
                        <th>Coupon</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($sales as $s): ?>
                    <tr>
                        <td class="text-muted small"><?= $s['id'] ?></td>
                        <td class="text-muted small"><?= date('d M Y H:i', strtotime($s['transaction_date'])) ?></td>
                        <td class="fw-semibold small"><?= escape($s['business_name']) ?></td>
                        <td class="text-muted small"><?= escape($s['store_name']) ?></td>
                        <td class="small">
                            <?php if ($s['customer_name']): ?>
                            <?= escape($s['customer_name']) ?>
                            <div class="text-muted"><?= escape($s['customer_phone'] ?? '') ?></div>
                            <?php else: ?>
                            <span class="text-muted">Walk-in</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end fw-semibold">₹<?= number_format($s['transaction_amount'], 2) ?></td>
                        <td class="text-end text-success small">
                            <?= $s['discount_amount'] > 0 ? '₹' . number_format($s['discount_amount'], 2) : '&mdash;' ?>
                        </td>
                        <td class="small"><?= ucfirst($s['payment_method'] ?? '&mdash;') ?></td>
                        <td class="small"><?= $s['coupon_title'] ? escape($s['coupon_title']) : '&mdash;' ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>sales/detail?id=<?= $s['id'] ?>" class="btn btn-xs btn-outline-primary py-0 px-1">
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
