<?php $pageTitle = 'Referral Tracking'; ?>

<!-- Stats -->
<div class="row g-3 mb-4">
    <?php foreach ([
        ['label' => 'Total Referrals',    'val' => number_format($stats['total']),              'color' => 'primary',   'icon' => 'bi-person-plus'],
        ['label' => 'Pending',             'val' => number_format($stats['pending']),             'color' => 'warning',   'icon' => 'bi-hourglass-split'],
        ['label' => 'Completed',           'val' => number_format($stats['completed']),           'color' => 'info',      'icon' => 'bi-check-circle'],
        ['label' => 'Rewarded',            'val' => number_format($stats['rewarded']),            'color' => 'success',   'icon' => 'bi-gift'],
        ['label' => 'Rewards Paid Out',    'val' => '₹'.number_format($stats['rewards_paid'],0), 'color' => 'danger',    'icon' => 'bi-currency-rupee'],
        ['label' => 'Total Reward Pool',   'val' => '₹'.number_format($stats['total_reward_pool'],0), 'color' => 'secondary', 'icon' => 'bi-wallet2'],
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
    <!-- Filter / Search -->
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold d-flex align-items-center justify-content-between">
                <span><i class="bi bi-funnel me-2"></i>Filters</span>
                <span class="text-muted small">Showing <?= number_format(count($referrals)) ?> of <?= number_format($totalCount) ?></span>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-2">
                    <div class="col-md-3">
                        <select name="status" class="form-select form-select-sm">
                            <option value="">All Statuses</option>
                            <?php foreach (['pending','completed','rewarded'] as $s): ?>
                            <option value="<?= $s ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="reward_given" class="form-select form-select-sm">
                            <option value="">Reward: All</option>
                            <option value="1" <?= ($filters['reward_given'] ?? '') === '1' ? 'selected' : '' ?>>Reward Given</option>
                            <option value="0" <?= ($filters['reward_given'] ?? '') === '0' ? 'selected' : '' ?>>Reward Pending</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="date" name="date_from" class="form-control form-control-sm" value="<?= escape($filters['date_from'] ?? '') ?>" placeholder="From">
                    </div>
                    <div class="col-md-3">
                        <input type="date" name="date_to" class="form-control form-control-sm" value="<?= escape($filters['date_to'] ?? '') ?>" placeholder="To">
                    </div>
                    <div class="col-md-9">
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by name, phone, referral code…" value="<?= escape($filters['search'] ?? '') ?>">
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button class="btn btn-sm btn-primary w-100"><i class="bi bi-search me-1"></i>Search</button>
                        <a href="<?= BASE_URL ?>referrals" class="btn btn-sm btn-secondary"><i class="bi bi-x"></i></a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Top Referrers -->
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-trophy text-warning me-2"></i>Top Referrers</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>#</th><th>Customer</th><th class="text-end">Refs</th><th class="text-end">Earned</th></tr></thead>
                    <tbody>
                    <?php foreach ($topReferrers as $i => $tr): ?>
                    <tr>
                        <td class="text-muted small"><?= $i + 1 ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>customers/profile?id=<?= $tr['id'] ?>" class="fw-semibold small text-decoration-none"><?= escape($tr['name']) ?></a>
                            <div class="text-muted" style="font-size:.7rem"><?= escape($tr['referral_code']) ?></div>
                        </td>
                        <td class="text-end"><?= $tr['total_referrals'] ?></td>
                        <td class="text-end">₹<?= number_format($tr['earned'],0) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($topReferrers)): ?>
                    <tr><td colspan="4" class="text-muted text-center py-3">No data</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Referrals Table -->
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>#ID</th>
                    <th>Referrer</th>
                    <th>Referee</th>
                    <th>Code Used</th>
                    <th>Status</th>
                    <th>Reward</th>
                    <th>Reward Amt</th>
                    <th>Date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($referrals as $r): ?>
            <?php
            $statusBadge = match($r['status']) {
                'completed' => 'bg-info',
                'rewarded'  => 'bg-success',
                default     => 'bg-warning text-dark',
            };
            ?>
            <tr>
                <td class="text-muted small">#<?= $r['id'] ?></td>
                <td>
                    <a href="<?= BASE_URL ?>customers/profile?id=<?= $r['referrer_customer_id'] ?>" class="fw-semibold text-decoration-none small"><?= escape($r['referrer_name']) ?></a>
                    <div class="text-muted" style="font-size:.7rem"><?= escape($r['referrer_phone']) ?></div>
                </td>
                <td>
                    <a href="<?= BASE_URL ?>customers/profile?id=<?= $r['referee_customer_id'] ?>" class="text-decoration-none small"><?= escape($r['referee_name']) ?></a>
                    <div class="text-muted" style="font-size:.7rem"><?= escape($r['referee_phone']) ?></div>
                </td>
                <td><code class="small"><?= escape($r['referral_code']) ?></code></td>
                <td><span class="badge <?= $statusBadge ?>"><?= ucfirst($r['status']) ?></span></td>
                <td>
                    <?php if ($r['reward_given']): ?>
                        <span class="badge bg-success"><i class="bi bi-check2 me-1"></i>Paid</span>
                    <?php else: ?>
                        <span class="badge bg-light text-muted border">Pending</span>
                    <?php endif; ?>
                </td>
                <td>₹<?= number_format($r['reward_amount'] ?? 0, 2) ?></td>
                <td class="text-muted small"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
                <td>
                    <a href="<?= BASE_URL ?>referrals/detail?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-secondary py-0"><i class="bi bi-eye"></i></a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($referrals)): ?>
            <tr><td colspan="9" class="text-muted text-center py-4">No referrals found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="card-footer bg-white d-flex justify-content-between align-items-center">
        <small class="text-muted">Page <?= $currentPage ?> of <?= $totalPages ?></small>
        <nav><ul class="pagination pagination-sm mb-0">
            <?php for ($p = max(1,$currentPage-2); $p <= min($totalPages,$currentPage+2); $p++): ?>
            <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
                <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $p])) ?>"><?= $p ?></a>
            </li>
            <?php endfor; ?>
        </ul></nav>
    </div>
    <?php endif; ?>
</div>
