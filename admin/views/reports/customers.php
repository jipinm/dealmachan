<?php $pageTitle = 'Customer Report'; ?>

<!-- Date Range Filter -->
<form method="GET" action="<?= BASE_URL ?>reports/customers" class="card border-0 shadow-sm mb-4">
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
                <a href="<?= BASE_URL ?>reports/export?type=customers&date_from=<?= urlencode($from) ?>&date_to=<?= urlencode($to) ?>"
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
    $cards = [
        ['label'=>'Total Registered', 'val'=>number_format($stats['total']),      'color'=>'primary', 'icon'=>'bi-people'],
        ['label'=>'Premium Members',  'val'=>number_format($stats['premium']),    'color'=>'warning', 'icon'=>'bi-star'],
        ['label'=>'Deal Makers',      'val'=>number_format($stats['dealmakers']), 'color'=>'success', 'icon'=>'bi-person-badge'],
    ];
    foreach ($cards as $c): ?>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-<?= $c['color'] ?> bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:52px;height:52px">
                    <i class="bi <?= $c['icon'] ?> text-<?= $c['color'] ?> fs-4"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold"><?= $c['val'] ?></div>
                    <div class="text-muted small"><?= $c['label'] ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Charts Row -->
<div class="row g-4 mb-4">
    <!-- Trend Line -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Registration Trend</div>
            <div class="card-body"><canvas id="trendChart" height="100"></canvas></div>
        </div>
    </div>
    <!-- Distribution Donuts -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">By Customer Type</div>
            <div class="card-body">
                <?php if (!empty($stats['by_type'])): ?>
                <canvas id="typeChart" height="180"></canvas>
                <?php else: ?>
                <p class="text-muted text-center pt-4">No data</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Registration Type Breakdown -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">By Registration Type</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light"><tr><th>Type</th><th class="text-end">Count</th></tr></thead>
                        <tbody>
                        <?php foreach ($stats['by_reg'] as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars(ucwords(str_replace('_',' ',$r['registration_type'] ?? 'unknown'))) ?></td>
                            <td class="text-end fw-bold"><?= number_format($r['cnt']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($stats['by_reg'])): ?>
                        <tr><td colspan="2" class="text-center text-muted">No data</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">By Customer Type</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light"><tr><th>Type</th><th class="text-end">Count</th></tr></thead>
                        <tbody>
                        <?php foreach ($stats['by_type'] as $t): ?>
                        <tr>
                            <td><?= htmlspecialchars(ucwords(str_replace('_',' ',$t['customer_type'] ?? 'standard'))) ?></td>
                            <td class="text-end fw-bold"><?= number_format($t['cnt']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($stats['by_type'])): ?>
                        <tr><td colspan="2" class="text-center text-muted">No data</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Customer List -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Customer List <span class="text-muted fw-normal">(<?= number_format($total) ?> records)</span></span>
        <span class="text-muted small"><?= $from ?> to <?= $to ?></span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th><th>Name</th><th>Email</th><th>Phone</th>
                    <th>Type</th><th>Registration</th><th>Subscription</th><th>Registered</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($list)): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">No customers in this date range</td></tr>
            <?php else: ?>
            <?php foreach ($list as $r): ?>
            <tr>
                <td class="text-muted small"><?= $r['id'] ?></td>
                <td>
                    <?= htmlspecialchars($r['name'] ?? '—') ?>
                    <?php if ($r['is_dealmaker']): ?><span class="badge bg-success ms-1 small">DM</span><?php endif; ?>
                </td>
                <td class="text-muted small"><?= htmlspecialchars($r['email'] ?? '—') ?></td>
                <td class="text-muted small"><?= htmlspecialchars($r['phone'] ?? '—') ?></td>
                <td><span class="badge bg-secondary"><?= htmlspecialchars($r['customer_type'] ?? 'standard') ?></span></td>
                <td class="text-muted small"><?= htmlspecialchars(str_replace('_',' ',$r['registration_type'] ?? '—')) ?></td>
                <td>
                    <?php $ss = $r['subscription_status']; ?>
                    <span class="badge bg-<?= $ss === 'active' ? 'success' : ($ss === 'expired' ? 'danger' : 'secondary') ?>">
                        <?= ucfirst($ss ?? '—') ?>
                    </span>
                </td>
                <td class="text-muted small"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
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
        datasets: [{
            label: 'Registrations',
            data: trend.map(t => t.cnt),
            borderColor: '#667eea',
            backgroundColor: 'rgba(102,126,234,0.1)',
            fill: true, tension: 0.4, pointRadius: 3,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});

<?php if (!empty($stats['by_type'])): ?>
const typeData = <?= json_encode(array_column($stats['by_type'], 'cnt')) ?>;
const typeLabels = <?= json_encode(array_map(fn($t) => ucfirst(str_replace('_',' ',$t['customer_type'] ?? 'standard')), $stats['by_type'])) ?>;
new Chart(document.getElementById('typeChart'), {
    type: 'doughnut',
    data: {
        labels: typeLabels,
        datasets: [{ data: typeData, backgroundColor: ['#667eea','#764ba2','#f093fb','#43e97b','#fa709a'], borderWidth: 2 }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
});
<?php endif; ?>
</script>
