<?php /* views/reports/subscriptions.php */ ?>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <h4 class="mb-0"><i class="bi bi-credit-card-2-back text-primary me-2"></i>Subscription Revenue Report</h4>
    <form class="d-flex gap-2 align-items-center flex-wrap" method="GET" action="<?= BASE_URL ?>reports/subscription-report">
        <input type="date" name="date_from" class="form-control form-control-sm" value="<?= escape($from) ?>">
        <span class="text-muted small">to</span>
        <input type="date" name="date_to" class="form-control form-control-sm" value="<?= escape($to) ?>">
        <button class="btn btn-sm btn-primary"><i class="bi bi-funnel me-1"></i>Apply</button>
    </form>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
    <?php foreach ([
        ['label' => 'Total Subscriptions', 'val' => number_format($stats['total']),                   'color' => 'primary',   'icon' => 'bi-credit-card'],
        ['label' => 'Active',               'val' => number_format($stats['active']),                  'color' => 'success',   'icon' => 'bi-check-circle'],
        ['label' => 'Expired',              'val' => number_format($stats['expired']),                 'color' => 'secondary', 'icon' => 'bi-clock-history'],
        ['label' => 'Cancelled',            'val' => number_format($stats['cancelled']),               'color' => 'danger',    'icon' => 'bi-x-circle'],
        ['label' => 'Revenue',              'val' => '₹'.number_format($stats['revenue'], 2),          'color' => 'info',      'icon' => 'bi-currency-rupee'],
        ['label' => 'Merchants',            'val' => number_format($stats['merchants']),               'color' => 'warning',   'icon' => 'bi-shop'],
        ['label' => 'Customers',            'val' => number_format($stats['customers']),               'color' => 'dark',      'icon' => 'bi-people'],
    ] as $c): ?>
    <div class="col-6 col-md-4 col-xl">
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
    <!-- Monthly Trend -->
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Monthly Revenue Trend</div>
            <div class="card-body"><canvas id="subTrendChart" height="110"></canvas></div>
        </div>
    </div>

    <!-- Plan Breakdown -->
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">By Plan Type</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Plan</th><th>Type</th><th class="text-end">Count</th><th class="text-end">Revenue</th></tr></thead>
                    <tbody>
                    <?php foreach ($stats['by_plan'] as $p): ?>
                    <tr>
                        <td class="text-capitalize"><?= escape($p['plan_type']) ?></td>
                        <td class="text-capitalize"><span class="badge bg-<?= $p['user_type'] === 'merchant' ? 'warning text-dark' : 'info' ?>"><?= escape($p['user_type']) ?></span></td>
                        <td class="text-end"><?= number_format($p['cnt']) ?></td>
                        <td class="text-end">₹<?= number_format($p['revenue'], 0) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($stats['by_plan'])): ?>
                    <tr><td colspan="4" class="text-muted text-center py-3">No data</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
(function() {
    const trend = <?= json_encode($trend) ?>;
    new Chart(document.getElementById('subTrendChart'), {
        type: 'line',
        data: {
            labels: trend.map(r => r.month),
            datasets: [
                { label: 'Subscriptions', data: trend.map(r => r.cnt),     borderColor: '#0d6efd', backgroundColor: 'rgba(13,110,253,0.1)', yAxisID: 'y', tension: 0.3, fill: true },
                { label: 'Revenue (₹)',   data: trend.map(r => r.revenue), borderColor: '#198754', backgroundColor: 'rgba(25,135,84,0.1)',  yAxisID: 'y2', tension: 0.3 }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } },
            scales: {
                y:  { type: 'linear', position: 'left',  beginAtZero: true, title: { display: true, text: 'Count' } },
                y2: { type: 'linear', position: 'right', beginAtZero: true, grid: { drawOnChartArea: false }, title: { display: true, text: 'Revenue (₹)' } }
            }
        }
    });
})();
</script>
