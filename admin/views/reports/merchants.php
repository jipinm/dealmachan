<?php $pageTitle = 'Merchant Report'; ?>

<!-- Date Range Filter -->
<form method="GET" action="<?= BASE_URL ?>reports/merchants" class="card border-0 shadow-sm mb-4">
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
                <a href="<?= BASE_URL ?>reports/export?type=merchants&date_from=<?= urlencode($from) ?>&date_to=<?= urlencode($to) ?>"
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
        ['label'=>'Total Registered', 'val'=>number_format($stats['total']),    'color'=>'primary','icon'=>'bi-shop'],
        ['label'=>'Approved',         'val'=>number_format($stats['approved']), 'color'=>'success','icon'=>'bi-check-circle'],
        ['label'=>'Pending',          'val'=>number_format($stats['pending']),  'color'=>'warning','icon'=>'bi-hourglass-split'],
        ['label'=>'Premium Merchants','val'=>number_format($stats['premium']),  'color'=>'info',   'icon'=>'bi-star'],
        ['label'=>'Rejected',         'val'=>number_format($stats['rejected']), 'color'=>'danger', 'icon'=>'bi-x-circle'],
    ];
    foreach ($scards as $c): ?>
    <div class="col-6 col-md-4 col-xl">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-3">
                <i class="bi <?= $c['icon'] ?> text-<?= $c['color'] ?> fs-4 d-block mb-1"></i>
                <div class="fs-5 fw-bold"><?= $c['val'] ?></div>
                <div class="text-muted small"><?= $c['label'] ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Charts Row -->
<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Merchant Registration Trend</div>
            <div class="card-body"><canvas id="trendChart" height="110"></canvas></div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Profile Status Distribution</div>
            <div class="card-body">
                <?php if ($stats['total'] > 0): ?>
                <canvas id="statusChart" height="190"></canvas>
                <?php else: ?>
                <p class="text-center text-muted pt-4">No data</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Top Merchants by Redemptions -->
<?php if (!empty($topList)): ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold">
        <i class="bi bi-trophy me-1 text-warning"></i>Top Merchants by Redemptions
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead class="table-light">
                <tr><th>#</th><th>Business</th><th>Status</th><th>Premium</th><th class="text-end">Redemptions</th><th class="text-end">Total Discount</th></tr>
            </thead>
            <tbody>
            <?php foreach ($topList as $i => $m): ?>
            <tr>
                <td class="text-muted small"><?= $i + 1 ?></td>
                <td class="fw-semibold"><?= htmlspecialchars($m['business_name']) ?></td>
                <td>
                    <span class="badge bg-<?= $m['profile_status']==='approved'?'success':($m['profile_status']==='pending'?'warning':'danger') ?>">
                        <?= ucfirst($m['profile_status']) ?>
                    </span>
                </td>
                <td><?= $m['is_premium'] ? '<span class="badge bg-warning text-dark">Premium</span>' : '<span class="text-muted small">&mdash;</span>' ?></td>
                <td class="text-end fw-bold"><?= number_format($m['redemption_count']) ?></td>
                <td class="text-end text-success fw-bold">₹<?= number_format($m['total_discount'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Merchant List -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Merchant List <span class="text-muted fw-normal">(<?= number_format($total) ?> records)</span></span>
        <span class="text-muted small"><?= $from ?> to <?= $to ?></span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead class="table-light">
                <tr><th>#</th><th>Business</th><th>Email</th><th>Phone</th><th>Status</th><th>Subscription</th><th class="text-end">Redemptions</th><th>Registered</th></tr>
            </thead>
            <tbody>
            <?php if (empty($list)): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">No merchants in this date range</td></tr>
            <?php else: ?>
            <?php foreach ($list as $m): ?>
            <tr>
                <td class="text-muted small"><?= $m['id'] ?></td>
                <td>
                    <?= htmlspecialchars($m['business_name']) ?>
                    <?php if ($m['is_premium']): ?><span class="badge bg-warning text-dark ms-1 small">Premium</span><?php endif; ?>
                </td>
                <td class="text-muted small"><?= htmlspecialchars($m['email'] ?? '&mdash;') ?></td>
                <td class="text-muted small"><?= htmlspecialchars($m['phone'] ?? '&mdash;') ?></td>
                <td><span class="badge bg-<?= $m['profile_status']==='approved'?'success':($m['profile_status']==='pending'?'warning':'danger') ?>">
                    <?= ucfirst($m['profile_status']) ?></span></td>
                <td><span class="badge bg-<?= $m['subscription_status']==='active'?'success':($m['subscription_status']==='trial'?'info':'secondary') ?>">
                    <?= ucfirst($m['subscription_status'] ?? '&mdash;') ?></span></td>
                <td class="text-end fw-bold"><?= number_format($m['redemption_count']) ?></td>
                <td class="text-muted small"><?= date('d M Y', strtotime($m['created_at'])) ?></td>
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
    type: 'bar',
    data: {
        labels: trend.map(t => t.day),
        datasets: [{
            label: 'New Merchants',
            data: trend.map(t => t.cnt),
            backgroundColor: 'rgba(67,233,123,0.7)',
            borderRadius: 4,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});
<?php if ($stats['total'] > 0): ?>
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: ['Approved','Pending','Rejected'],
        datasets: [{ data: [<?= (int)$stats['approved'] ?>,<?= (int)$stats['pending'] ?>,<?= (int)$stats['rejected'] ?>],
            backgroundColor: ['#198754','#ffc107','#dc3545'], borderWidth: 2 }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
});
<?php endif; ?>
</script>
