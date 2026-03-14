<?php $pageTitle = 'Redemption Report'; ?>

<!-- Date Range Filter -->
<form method="GET" action="<?= BASE_URL ?>reports/redemptions" class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($from) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">To</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($to) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
            </div>
            <div class="col-md-auto ms-auto d-flex gap-2">
                <a href="<?= BASE_URL ?>reports/export?type=redemptions&date_from=<?= urlencode($from) ?>&date_to=<?= urlencode($to) ?>"
                   class="btn btn-outline-success btn-sm">
                    <i class="bi bi-download me-1"></i>Export CSV
                </a>
                <a href="<?= BASE_URL ?>reports/dashboard" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>Dashboard
                </a>
            </div>
        </div>
    </div>
</form>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <?php
    $scards = [
        ['label'=>'Total Redemptions',   'val'=>number_format($stats['total']),              'color'=>'primary','icon'=>'bi-arrow-repeat'],
        ['label'=>'Total Discount Given','val'=>'₹'.number_format($stats['discount'],2),     'color'=>'danger', 'icon'=>'bi-piggy-bank'],
        ['label'=>'Total Transaction',   'val'=>'₹'.number_format($stats['txn'],2),          'color'=>'success','icon'=>'bi-cash-stack'],
        ['label'=>'Avg. Discount',       'val'=>'₹'.number_format($stats['avgDisc'],2),      'color'=>'warning','icon'=>'bi-percent'],
        ['label'=>'Unique Coupons Used', 'val'=>number_format($stats['uniqCoupons']),         'color'=>'info',   'icon'=>'bi-ticket-perforated'],
        ['label'=>'Unique Customers',    'val'=>number_format($stats['uniqCustomers']),       'color'=>'secondary','icon'=>'bi-people'],
    ];
    foreach ($scards as $c): ?>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-3 px-2">
                <i class="bi <?= $c['icon'] ?> text-<?= $c['color'] ?> fs-4 d-block mb-1"></i>
                <div class="fw-bold"><?= $c['val'] ?></div>
                <div class="text-muted" style="font-size:.75rem"><?= $c['label'] ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Trend Chart -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold">Daily Redemption Trend</div>
    <div class="card-body"><canvas id="trendChart" height="80"></canvas></div>
</div>

<!-- Top Coupons -->
<?php if (!empty($topCoup)): ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold">
        <i class="bi bi-trophy me-1 text-warning"></i>Top Coupons by Redemption Count
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead class="table-light">
                <tr><th>#</th><th>Title</th><th>Code</th><th>Merchant</th><th>Discount</th><th class="text-end">Redemptions</th><th class="text-end">Total Discount</th></tr>
            </thead>
            <tbody>
            <?php foreach ($topCoup as $i => $c): ?>
            <tr>
                <td class="text-muted small"><?= $i+1 ?></td>
                <td class="fw-semibold"><?= htmlspecialchars($c['title']) ?></td>
                <td><code class="small"><?= htmlspecialchars($c['coupon_code']) ?></code></td>
                <td class="text-muted small"><?= htmlspecialchars($c['business_name'] ?? '(Admin)') ?></td>
                <td class="text-muted small">
                    <?= $c['discount_type']==='percentage' ? $c['discount_value'].'%' : '₹'.number_format($c['discount_value'],2) ?>
                </td>
                <td class="text-end fw-bold"><?= number_format($c['redemption_count']) ?></td>
                <td class="text-end text-success fw-bold">₹<?= number_format($c['total_discount'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Redemption List -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold">All Redemptions <span class="text-muted fw-normal">(<?= number_format($total) ?> records)</span></span>
        <span class="text-muted small"><?= $from ?> to <?= $to ?></span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead class="table-light">
                <tr><th>#</th><th>Date/Time</th><th>Coupon</th><th>Customer</th><th>Merchant</th><th class="text-end">Discount</th><th class="text-end">Transaction</th></tr>
            </thead>
            <tbody>
            <?php if (empty($list)): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">No redemptions in this date range</td></tr>
            <?php else: ?>
            <?php foreach ($list as $r): ?>
            <tr>
                <td class="text-muted small"><?= $r['id'] ?></td>
                <td class="text-muted small"><?= date('d M Y, H:i', strtotime($r['redeemed_at'])) ?></td>
                <td>
                    <span class="fw-semibold small"><?= htmlspecialchars($r['coupon_title'] ?? '&mdash;') ?></span>
                    <code class="d-block text-muted" style="font-size:.7rem"><?= htmlspecialchars($r['coupon_code'] ?? '') ?></code>
                </td>
                <td class="text-muted small"><?= htmlspecialchars($r['customer_name'] ?? '&mdash;') ?></td>
                <td class="text-muted small"><?= htmlspecialchars($r['business_name'] ?? '&mdash;') ?></td>
                <td class="text-end text-danger fw-bold small">₹<?= number_format($r['discount_amount'] ?? 0, 2) ?></td>
                <td class="text-end text-success fw-bold small">₹<?= number_format($r['transaction_amount'] ?? 0, 2) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pages > 1): ?>
    <div class="card-footer bg-white border-0 d-flex justify-content-between align-items-center">
        <small class="text-muted">Page <?= $page ?> of <?= $pages ?></small>
        <nav><ul class="pagination pagination-sm mb-0">
            <?php for ($p = max(1,$page-2); $p <= min($pages,$page+2); $p++): ?>
            <li class="page-item <?= $p===$page?'active':'' ?>">
                <a class="page-link" href="?date_from=<?= $from ?>&date_to=<?= $to ?>&page=<?= $p ?>"><?= $p ?></a>
            </li>
            <?php endfor; ?>
        </ul></nav>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const trend = <?= json_encode($trend) ?>;
new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
        labels: trend.map(t => t.day),
        datasets: [
            {
                label: 'Redemptions',
                data: trend.map(t => t.cnt),
                borderColor: '#667eea',
                backgroundColor: 'rgba(102,126,234,0.1)',
                yAxisID: 'y',
                fill: true, tension: 0.4, pointRadius: 3,
            },
            {
                label: 'Discount (₹)',
                data: trend.map(t => parseFloat(t.discount)),
                borderColor: '#fa709a',
                backgroundColor: 'transparent',
                yAxisID: 'y1',
                tension: 0.4, pointRadius: 2, borderDash: [4,4],
            }
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: true,
        plugins: { legend: { position: 'top' } },
        scales: {
            y:  { beginAtZero: true, position: 'left',  ticks: { stepSize: 1 } },
            y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false },
                  ticks: { callback: v => '₹'+v } }
        }
    }
});
</script>
