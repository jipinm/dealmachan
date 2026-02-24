<?php /* views/reports/coupon-analytics.php */ ?>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <h4 class="mb-0"><i class="bi bi-ticket-perforated text-warning me-2"></i>Coupon Analytics Report</h4>
    <form class="d-flex gap-2 align-items-center flex-wrap" method="GET" action="<?= BASE_URL ?>reports/coupon-analytics">
        <input type="date" name="date_from" class="form-control form-control-sm" value="<?= escape($from) ?>">
        <span class="text-muted small">to</span>
        <input type="date" name="date_to" class="form-control form-control-sm" value="<?= escape($to) ?>">
        <button class="btn btn-sm btn-primary"><i class="bi bi-funnel me-1"></i>Apply</button>
    </form>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
    <?php foreach ([
        ['label' => 'Total Saves',       'val' => number_format($stats['saves']),           'color' => 'warning',   'icon' => 'bi-bookmark-check'],
        ['label' => 'Unique Customers',  'val' => number_format($stats['uniqCustomers']),    'color' => 'primary',   'icon' => 'bi-people'],
        ['label' => 'Unique Coupons',    'val' => number_format($stats['uniqCoupons']),      'color' => 'info',      'icon' => 'bi-ticket'],
        ['label' => 'Redemptions',       'val' => number_format($stats['redemptions']),      'color' => 'success',   'icon' => 'bi-check2-circle'],
    ] as $c): ?>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-3">
                <i class="bi <?= $c['icon'] ?> text-<?= $c['color'] ?> fs-3 d-block mb-1"></i>
                <div class="fw-bold fs-5"><?= $c['val'] ?></div>
                <div class="text-muted" style="font-size:.75rem"><?= $c['label'] ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-4 mb-4">
    <!-- Save Trend Chart -->
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Daily Coupon Save Trend</div>
            <div class="card-body"><canvas id="saveTrendChart" height="110"></canvas></div>
        </div>
    </div>

    <!-- Top Saved Coupons -->
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Top 10 Most Saved Coupons</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>#</th><th>Coupon</th><th class="text-end">Saves</th></tr></thead>
                    <tbody>
                    <?php foreach ($topSaved as $i => $cp): ?>
                    <tr>
                        <td class="text-muted small"><?= $i + 1 ?></td>
                        <td>
                            <div class="fw-semibold small"><?= escape($cp['title']) ?></div>
                            <div class="text-muted" style="font-size:.72rem"><?= escape($cp['coupon_code']) ?></div>
                        </td>
                        <td class="text-end fw-semibold"><?= number_format($cp['save_count']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($topSaved)): ?>
                    <tr><td colspan="3" class="text-muted text-center py-3">No saves in this period.</td></tr>
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
    const trend = <?= json_encode($saveTrend) ?>;
    new Chart(document.getElementById('saveTrendChart'), {
        type: 'line',
        data: {
            labels: trend.map(r => r.day),
            datasets: [{
                label: 'Coupon Saves',
                data: trend.map(r => r.cnt),
                borderColor: '#ffc107',
                backgroundColor: 'rgba(255,193,7,0.15)',
                tension: 0.3,
                fill: true,
                pointRadius: 3
            }]
        },
        options: { responsive: true, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true } } }
    });
})();
</script>
