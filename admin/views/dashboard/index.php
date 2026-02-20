<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Dashboard</h1>
        <p class="text-muted">Welcome back, <?= escape($current_user['name']) ?>!</p>
    </div>
    <div class="text-end">
        <small class="text-muted">
            <i class="fas fa-clock me-1"></i>
            <?= date('l, F j, Y \a\t g:i A') ?>
        </small>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <?php if ($current_user['admin_type'] === 'super_admin' || $current_user['admin_type'] === 'city_admin'): ?>
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                             style="width: 50px; height: 50px;">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h4 class="mb-1"><?= number_format($stats['total_customers']) ?></h4>
                        <p class="text-muted mb-0 small">Total Customers</p>
                        <small class="text-success">
                            <i class="fas fa-arrow-up me-1"></i>
                            <?= number_format($stats['today_customers']) ?> today
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($current_user['admin_type'] === 'super_admin' || $current_user['admin_type'] === 'city_admin' || $current_user['admin_type'] === 'sales_admin'): ?>
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" 
                             style="width: 50px; height: 50px;">
                            <i class="fas fa-store"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h4 class="mb-1"><?= number_format($stats['total_merchants']) ?></h4>
                        <p class="text-muted mb-0 small">Total Merchants</p>
                        <small class="text-<?= $stats['pending_merchants'] > 0 ? 'warning' : 'success' ?>">
                            <i class="fas fa-clock me-1"></i>
                            <?= number_format($stats['pending_merchants']) ?> pending
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-warning text-white rounded-circle d-flex align-items-center justify-content-center" 
                             style="width: 50px; height: 50px;">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h4 class="mb-1"><?= number_format($stats['active_coupons']) ?></h4>
                        <p class="text-muted mb-0 small">Active Coupons</p>
                        <small class="text-info">
                            <i class="fas fa-calendar me-1"></i>
                            Currently available
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-info text-white rounded-circle d-flex align-items-center justify-content-center" 
                             style="width: 50px; height: 50px;">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h4 class="mb-1"><?= number_format($stats['total_redemptions']) ?></h4>
                        <p class="text-muted mb-0 small">Total Redemptions</p>
                        <small class="text-success">
                            <i class="fas fa-arrow-up me-1"></i>
                            <?= number_format($stats['today_redemptions']) ?> today
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Additional Statistics Row for Super/City Admins -->
<?php if ($current_user['admin_type'] === 'super_admin' || $current_user['admin_type'] === 'city_admin'): ?>
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-0 bg-light">
            <div class="card-body text-center">
                <h5 class="text-primary"><?= number_format($stats['week_customers']) ?></h5>
                <small class="text-muted">New Customers (7 days)</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 bg-light">
            <div class="card-body text-center">
                <h5 class="text-primary"><?= number_format($stats['month_customers']) ?></h5>
                <small class="text-muted">New Customers (30 days)</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 bg-light">
            <div class="card-body text-center">
                <h5 class="text-success"><?= number_format($stats['week_merchants']) ?></h5>
                <small class="text-muted">New Merchants (7 days)</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 bg-light">
            <div class="card-body text-center">
                <h5 class="text-<?= $stats['blocked_customers'] > 0 ? 'danger' : 'success' ?>"><?= number_format($stats['blocked_customers']) ?></h5>
                <small class="text-muted">Blocked Customers</small>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-bolt me-2"></i>
                    Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php if ($current_user['admin_type'] === 'super_admin' || $current_user['admin_type'] === 'city_admin'): ?>
                    <div class="col-md-3 mb-3">
                        <a href="<?= BASE_URL ?>customers/add" class="btn btn-outline-primary w-100">
                            <i class="fas fa-user-plus me-2"></i>
                            Add Customer
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="<?= BASE_URL ?>merchants/add" class="btn btn-outline-success w-100">
                            <i class="fas fa-store me-2"></i>
                            Add Merchant
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <div class="col-md-3 mb-3">
                        <a href="<?= BASE_URL ?>coupons/add" class="btn btn-outline-warning w-100">
                            <i class="fas fa-ticket-alt me-2"></i>
                            Create Coupon
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="<?= BASE_URL ?>cards/generate" class="btn btn-outline-info w-100">
                            <i class="fas fa-credit-card me-2"></i>
                            Generate Cards
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Content Row -->
<div class="row">
    <!-- Chart Area -->
    <div class="col-xl-8 col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h6 class="text-primary mb-0">Customer Registration Trends
                    <small class="text-muted fw-normal">(Last <?= $trend_period ?> Days)</small>
                </h6>
                <div class="dropdown">
                    <?php
                        $period_labels = [7 => 'Last 7 Days', 30 => 'Last 30 Days', 90 => 'Last 90 Days'];
                        $active_label  = $period_labels[$trend_period] ?? 'Last 30 Days';
                        // Build base URL without trend_period param
                        $base_url = BASE_URL . 'dashboard';
                    ?>
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                            id="chartPeriod" data-bs-toggle="dropdown">
                        <?= $active_label ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <?php foreach ($period_labels as $days => $label): ?>
                        <li>
                            <a class="dropdown-item <?= $trend_period === $days ? 'active' : '' ?>"
                               href="<?= $base_url ?>?trend_period=<?= $days ?>">
                                <?= $label ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <div class="card-body" style="position: relative; height: 300px;">
                <canvas id="customerTrendsChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="col-xl-4 col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h6 class="text-primary mb-0">Recent Activity</h6>
                <a href="<?= BASE_URL ?>reports" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                <?php if (!empty($recent_activities)): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recent_activities as $activity): ?>
                        <div class="list-group-item d-flex align-items-start px-0 py-3 border-0">
                            <div class="me-3">
                                <?php 
                                $icon_bg = 'bg-primary';
                                $icon = 'fas fa-info';
                                
                                switch($activity['type']) {
                                    case 'customer':
                                        $icon_bg = 'bg-primary';
                                        $icon = 'fas fa-user-plus';
                                        break;
                                    case 'merchant':
                                        $icon_bg = 'bg-success';
                                        $icon = 'fas fa-store';
                                        break;
                                    case 'coupon':
                                        $icon_bg = 'bg-warning';
                                        $icon = 'fas fa-ticket-alt';
                                        break;
                                    case 'redemption':
                                        $icon_bg = 'bg-info';
                                        $icon = 'fas fa-gift';
                                        break;
                                }
                                ?>
                                <div class="<?= $icon_bg ?> text-white rounded-circle d-flex align-items-center justify-content-center" 
                                     style="width: 35px; height: 35px;">
                                    <i class="<?= $icon ?>"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <p class="mb-1 small fw-medium"><?= htmlspecialchars($activity['description'] ?? $activity['message'] ?? '') ?></p>
                                <?php if (!empty($activity['details'])): ?>
                                    <p class="mb-1 small text-muted"><?= htmlspecialchars($activity['details'] ?? '') ?></p>
                                <?php endif; ?>
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    <?php $actTime = $activity['created_at'] ?? $activity['time'] ?? null; echo $actTime ? date('M j, g:i A', strtotime($actTime)) : ''; ?>
                                </small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-inbox fa-2x mb-2"></i>
                        <p>No recent activities</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Second Row - Additional Charts for Super/City Admins -->
<?php if ($current_user['admin_type'] === 'super_admin' || $current_user['admin_type'] === 'city_admin'): ?>
<div class="row mt-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header">
                <h6 class="text-primary mb-0">Coupon Categories Distribution</h6>
            </div>
            <div class="card-body" style="position: relative; height: 300px;">
                <canvas id="couponCategoriesChart"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header">
                <h6 class="text-primary mb-0">Top Performing Merchants</h6>
            </div>
            <div class="card-body">
                <?php if (!empty($chart_data['top_merchants'])): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach (array_slice($chart_data['top_merchants'], 0, 5) as $index => $merchant): ?>
                        <div class="list-group-item d-flex align-items-center px-0">
                            <div class="me-3">
                                <span class="badge bg-primary rounded-pill"><?= $index + 1 ?></span>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1"><?= htmlspecialchars($merchant['business_name']) ?></h6>
                                <small class="text-muted"><?= number_format($merchant['total_redemptions']) ?> redemptions</small>
                            </div>
                            <div class="text-success fw-bold">
                                <?= number_format($merchant['total_redemptions']) ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-chart-bar fa-2x mb-2"></i>
                        <p>No merchant data available</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Admin Type Information -->
<div class="row mt-4">
    <div class="col-12">
        <div class="alert alert-info">
            <h6><i class="fas fa-info-circle me-2"></i>Your Access Level</h6>
            <p class="mb-0">
                You are logged in as <strong><?= ucfirst(str_replace('_', ' ', $current_user['admin_type'])) ?></strong>.
                <?php if ($current_user['admin_type'] === 'super_admin'): ?>
                    You have full access to all system features and can manage other administrators.
                <?php elseif ($current_user['admin_type'] === 'city_admin'): ?>
                    You can manage customers and merchants within your assigned city.
                <?php else: ?>
                    Your access is limited to specific features based on your role.
                <?php endif; ?>
            </p>
        </div>
    </div>
</div>

<!-- Chart.js Integration -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Customer Trends Chart
    const customerTrendsCtx = document.getElementById('customerTrendsChart');
    if (customerTrendsCtx) {
        const customerTrendsData = <?= json_encode(array_values($chart_data['customer_trends'] ?? [])) ?>;
        
        if (customerTrendsData.length === 0) {
            customerTrendsCtx.closest('.card-body').innerHTML = '<div class="d-flex align-items-center justify-content-center h-100 text-muted"><div class="text-center"><i class="bi bi-bar-chart fs-1 d-block mb-2"></i>No registration data in the last 30 days</div></div>';
        } else {
            // Fill in missing dates with 0 counts for a continuous line
            const labels = customerTrendsData.map(item => item.date);
            const counts = customerTrendsData.map(item => parseInt(item.count, 10));

            new Chart(customerTrendsCtx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'New Customers',
                        data: counts,
                        borderColor: 'rgb(13, 110, 253)',
                        backgroundColor: 'rgba(13, 110, 253, 0.08)',
                        borderWidth: 2,
                        pointRadius: 4,
                        pointBackgroundColor: 'rgb(13, 110, 253)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: ctx => ' ' + ctx.parsed.y + ' customers'
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0 },
                            grid: { color: 'rgba(0,0,0,0.06)' }
                        },
                        x: {
                            grid: { display: false },
                            ticks: {
                                maxTicksLimit: 10,
                                maxRotation: 45
                            }
                        }
                    }
                }
            });
        }
    }

    // Coupon Categories Chart
    const couponCategoriesCtx = document.getElementById('couponCategoriesChart');
    if (couponCategoriesCtx) {
        const couponCategoriesData = <?= json_encode(array_values($chart_data['coupon_categories'] ?? [])) ?>;

        if (couponCategoriesData.length === 0) {
            couponCategoriesCtx.closest('.card-body').innerHTML = '<div class="d-flex align-items-center justify-content-center h-100 text-muted"><div class="text-center"><i class="bi bi-pie-chart fs-1 d-block mb-2"></i>No active coupon data available</div></div>';
        } else {
            const bgColors = [
                'rgba(13, 110, 253, 0.8)',
                'rgba(25, 135, 84, 0.8)',
                'rgba(255, 193, 7, 0.8)',
                'rgba(220, 53, 69, 0.8)',
                'rgba(13, 202, 240, 0.8)',
                'rgba(111, 66, 193, 0.8)'
            ];
            const labels = couponCategoriesData.map(item => {
                const map = { 'percentage': 'Percentage Discount', 'fixed': 'Fixed Amount Discount' };
                return map[item.category] || item.category;
            });
            new Chart(couponCategoriesCtx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: couponCategoriesData.map(item => parseInt(item.count, 10)),
                        backgroundColor: bgColors.slice(0, couponCategoriesData.length),
                        borderColor: bgColors.map(c => c.replace('0.8', '1')).slice(0, couponCategoriesData.length),
                        borderWidth: 2,
                        hoverOffset: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { padding: 16, usePointStyle: true }
                        },
                        tooltip: {
                            callbacks: {
                                label: ctx => ' ' + ctx.label + ': ' + ctx.parsed + ' coupons'
                            }
                        }
                    },
                    cutout: '60%'
                }
            });
        }
    }

    // Auto-refresh activity feed every 30 seconds
    setInterval(function() {
        // You can implement AJAX refresh here if needed
        console.log('Activity feed refresh interval');
    }, 30000);
});
</script>