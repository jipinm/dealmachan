<?php /* views/reports/revenue.php */ ?>

<!-- Date Range Filter -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <h4 class="mb-0"><i class="bi bi-cash-stack text-success me-2"></i>Revenue / GMV Report</h4>
    <form class="d-flex gap-2 align-items-center flex-wrap" method="GET" action="<?= BASE_URL ?>reports/revenue">
        <input type="date" name="date_from" class="form-control form-control-sm" value="<?= escape($from) ?>">
        <span class="text-muted small">to</span>
        <input type="date" name="date_to" class="form-control form-control-sm" value="<?= escape($to) ?>">
        <button class="btn btn-sm btn-primary"><i class="bi bi-funnel me-1"></i>Apply</button>
        <a href="<?= BASE_URL ?>reports/export?type=revenue&date_from=<?= urlencode($from) ?>&date_to=<?= urlencode($to) ?>"
           class="btn btn-sm btn-outline-success"><i class="bi bi-download me-1"></i>CSV</a>
    </form>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
    <?php foreach ([
        ['label' => 'Transactions',    'val' => number_format($stats['total']),               'color' => 'primary',   'icon' => 'bi-receipt'],
        ['label' => 'Gross Revenue',   'val' => '₹'.number_format($stats['gmv'], 2),          'color' => 'success',   'icon' => 'bi-cash-stack'],
        ['label' => 'Discount Given',  'val' => '₹'.number_format($stats['discount'], 2),     'color' => 'danger',    'icon' => 'bi-percent'],
        ['label' => 'Net Revenue',     'val' => '₹'.number_format($stats['gmv'] - $stats['discount'], 2), 'color' => 'info', 'icon' => 'bi-graph-up'],
        ['label' => 'Avg Ticket',      'val' => '₹'.number_format($stats['avgTicket'], 2),    'color' => 'secondary', 'icon' => 'bi-bar-chart'],
        ['label' => 'With Coupon',     'val' => number_format($stats['withCoupon']),           'color' => 'warning',   'icon' => 'bi-ticket-perforated'],
    ] as $c): ?>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-3">
                <i class="bi <?= $c['icon'] ?> text-<?= $c['color'] ?> fs-4 d-block mb-1"></i>
                <div class="fw-bold"><?= $c['val'] ?></div>
                <div class="text-muted" style="font-size:.75rem"><?= $c['label'] ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-4 mb-4">
    <!-- Trend Chart -->
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Daily Revenue Trend</div>
            <div class="card-body"><canvas id="revTrendChart" height="110"></canvas></div>
        </div>
    </div>

    <!-- Payment Method Breakdown -->
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Payment Methods</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Method</th><th class="text-end">Txns</th><th class="text-end">Volume</th></tr></thead>
                    <tbody>
                    <?php foreach ($stats['payment_breakdown'] as $pm): ?>
                    <tr>
                        <td class="text-capitalize"><?= escape($pm['payment_method']) ?></td>
                        <td class="text-end"><?= number_format($pm['cnt']) ?></td>
                        <td class="text-end">₹<?= number_format($pm['vol'], 0) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($stats['payment_breakdown'])): ?>
                    <tr><td colspan="3" class="text-muted text-center py-3">No data</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Top Merchants by Revenue -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">Top 10 Merchants by Revenue</div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr><th>#</th><th>Merchant</th><th class="text-end">Transactions</th><th class="text-end">GMV</th><th class="text-end">Discount</th><th class="text-end">Net</th></tr>
            </thead>
            <tbody>
            <?php foreach ($topMerch as $i => $m): ?>
            <tr>
                <td class="text-muted small"><?= $i + 1 ?></td>
                <td><a href="<?= BASE_URL ?>merchants/profile?id=<?= $m['id'] ?>"><?= escape($m['business_name']) ?></a></td>
                <td class="text-end"><?= number_format($m['transactions']) ?></td>
                <td class="text-end fw-semibold">₹<?= number_format($m['gmv'], 0) ?></td>
                <td class="text-end text-danger">₹<?= number_format($m['discount'], 0) ?></td>
                <td class="text-end text-success">₹<?= number_format($m['gmv'] - $m['discount'], 0) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($topMerch)): ?>
            <tr><td colspan="6" class="text-muted text-center py-3">No transactions in this period.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
(function() {
    const trend = <?= json_encode($trend) ?>;
    const labels = trend.map(r => r.day);
    const gmv    = trend.map(r => parseFloat(r.gmv));
    const disc   = trend.map(r => parseFloat(r.discount));

    new Chart(document.getElementById('revTrendChart'), {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { label: 'GMV (₹)',      data: gmv,  backgroundColor: 'rgba(25,135,84,0.7)', order: 2 },
                { label: 'Discount (₹)', data: disc, backgroundColor: 'rgba(220,53,69,0.5)', order: 1 },
            ]
        },
        options: { responsive: true, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true } } }
    });
})();
</script>
