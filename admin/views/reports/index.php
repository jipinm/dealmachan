<?php $pageTitle = 'Reports & Analytics'; ?>

<!-- Quick Nav Cards -->
<div class="row g-3 mb-4">
    <?php
    $navCards = [
        ['icon'=>'bi-people-fill',      'color'=>'primary',  'label'=>'Customer Reports',   'url'=>'reports/customers',   'val'=>number_format($stats['total_customers'])],
        ['icon'=>'bi-shop',             'color'=>'success',  'label'=>'Merchant Reports',   'url'=>'reports/merchants',   'val'=>number_format($stats['total_merchants'])],
        ['icon'=>'bi-arrow-repeat',     'color'=>'warning',  'label'=>'Redemption Reports', 'url'=>'reports/redemptions', 'val'=>number_format($stats['total_redemptions'])],
        ['icon'=>'bi-credit-card-2-back','color'=>'info',    'label'=>'Cards Assigned',     'url'=>'cards',               'val'=>number_format($stats['assigned_cards'])],
        ['icon'=>'bi-cash-stack',           'color'=>'success',  'label'=>'Revenue / GMV',      'url'=>'reports/revenue',          'val'=>''],
        ['icon'=>'bi-credit-card',          'color'=>'primary',  'label'=>'Subscription Revenue','url'=>'reports/subscription-report','val'=>''],
        ['icon'=>'bi-ticket-perforated',    'color'=>'warning',  'label'=>'Coupon Analytics',   'url'=>'reports/coupon-analytics', 'val'=>''],
        ['icon'=>'bi-activity',             'color'=>'info',     'label'=>'Engagement',          'url'=>'reports/engagement',       'val'=>''],
    ];
    foreach ($navCards as $c): ?>
    <div class="col-6 col-xl-3">
        <a href="<?= BASE_URL ?><?= $c['url'] ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 hover-shadow">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="rounded-circle bg-<?= $c['color'] ?> bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:48px;height:48px;">
                        <i class="bi <?= $c['icon'] ?> text-<?= $c['color'] ?> fs-5"></i>
                    </div>
                    <div>
                        <div class="fs-5 fw-bold text-dark"><?= $c['val'] ?></div>
                        <div class="text-muted small"><?= $c['label'] ?></div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<!-- Summary Stats Row -->
<div class="row g-3 mb-4">
    <?php
    $sumCards = [
        ['label'=>'Active Coupons',      'val'=>number_format($stats['active_coupons']),                       'color'=>'success', 'icon'=>'bi-ticket-perforated'],
        ['label'=>'Total Discount Given','val'=>'₹'.number_format($stats['total_discount'],2),                 'color'=>'danger',  'icon'=>'bi-piggy-bank'],
        ['label'=>'Total Transaction',   'val'=>'₹'.number_format($stats['total_transaction'],2),              'color'=>'primary', 'icon'=>'bi-cash-stack'],
        ['label'=>'Pending Merchants',   'val'=>number_format($stats['pending_merchants']),                    'color'=>'warning', 'icon'=>'bi-hourglass-split'],
        ['label'=>'New Customers (Month)','val'=>number_format($stats['new_customers_month']),                 'color'=>'info',    'icon'=>'bi-person-plus'],
        ['label'=>'Active Contests',     'val'=>number_format($stats['active_contests']),                      'color'=>'secondary','icon'=>'bi-trophy'],
    ];
    foreach ($sumCards as $sc): ?>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-3 px-2">
                <i class="bi <?= $sc['icon'] ?> text-<?= $sc['color'] ?> fs-4 d-block mb-1"></i>
                <div class="fw-bold"><?= $sc['val'] ?></div>
                <div class="text-muted" style="font-size:.75rem"><?= $sc['label'] ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Charts Row -->
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Monthly Overview</span>
                <span class="badge bg-primary">Last 6 Months</span>
            </div>
            <div class="card-body"><canvas id="monthlyChart" height="110"></canvas></div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Quick Export</div>
            <div class="card-body">
                <p class="text-muted small mb-3">Export data for the current month as CSV</p>
                <div class="d-grid gap-2">
                    <a href="<?= BASE_URL ?>reports/export?type=customers&date_from=<?= date('Y-m-01') ?>&date_to=<?= date('Y-m-d') ?>"
                       class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-download me-1"></i>Export Customers CSV
                    </a>
                    <a href="<?= BASE_URL ?>reports/export?type=merchants&date_from=<?= date('Y-m-01') ?>&date_to=<?= date('Y-m-d') ?>"
                       class="btn btn-outline-success btn-sm">
                        <i class="bi bi-download me-1"></i>Export Merchants CSV
                    </a>
                    <a href="<?= BASE_URL ?>reports/export?type=redemptions&date_from=<?= date('Y-m-01') ?>&date_to=<?= date('Y-m-d') ?>"
                       class="btn btn-outline-warning btn-sm">
                        <i class="bi bi-download me-1"></i>Export Redemptions CSV
                    </a>
                </div>
                <hr>
                <div class="d-flex gap-2 flex-column">
                    <a href="<?= BASE_URL ?>reports/customers" class="btn btn-primary btn-sm"><i class="bi bi-people me-1"></i>Customer Report</a>
                    <a href="<?= BASE_URL ?>reports/merchants" class="btn btn-success btn-sm"><i class="bi bi-shop me-1"></i>Merchant Report</a>
                    <a href="<?= BASE_URL ?>reports/redemptions" class="btn btn-warning btn-sm"><i class="bi bi-arrow-repeat me-1"></i>Redemption Report</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const monthly = <?= json_encode(array_values($monthly)) ?>;
const labels  = monthly.map(m => m.label);
new Chart(document.getElementById('monthlyChart'), {
    type: 'bar',
    data: {
        labels,
        datasets: [
            {
                label: 'New Customers',
                data: monthly.map(m => m.customers),
                backgroundColor: 'rgba(102,126,234,0.7)',
                borderRadius: 4,
            },
            {
                label: 'Redemptions',
                data: monthly.map(m => m.redemptions),
                backgroundColor: 'rgba(118,75,162,0.7)',
                borderRadius: 4,
            }
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: true,
        plugins: { legend: { position: 'top' } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});
</script>
