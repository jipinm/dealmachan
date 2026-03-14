<?php /* views/card-configurations/index.php */ ?>

<!-- Stats Row -->
<div class="row g-3 mb-4">
    <?php
    $classColors = ['silver' => 'secondary', 'gold' => 'warning', 'platinum' => 'info', 'diamond' => 'primary'];
    $statDefs = [
        ['label' => 'Total',    'key' => 'total',    'color' => 'dark'],
        ['label' => 'Active',   'key' => 'active',   'color' => 'success'],
        ['label' => 'Silver',   'key' => 'silver',   'color' => 'secondary'],
        ['label' => 'Gold',     'key' => 'gold',     'color' => 'warning'],
        ['label' => 'Platinum', 'key' => 'platinum', 'color' => 'info'],
        ['label' => 'Diamond',  'key' => 'diamond',  'color' => 'primary'],
    ];
    foreach ($statDefs as $sc):
    ?>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card text-bg-<?= $sc['color'] ?> h-100 shadow-sm">
            <div class="card-body py-3 px-3">
                <div class="small fw-semibold text-white-50"><?= $sc['label'] ?></div>
                <div class="fs-4 fw-bold"><?= number_format($stats[$sc['key']] ?? 0) ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Page Header -->
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="mb-0">Card Configurations</h4>
        <small class="text-muted">
            <?php
            $from = min($totalCount, ($currentPage - 1) * $perPage + 1);
            $to   = min($totalCount, $currentPage * $perPage);
            echo $totalCount ? "Showing {$from}&ndash;{$to} of {$totalCount}" : 'No configurations found';
            ?>
        </small>
    </div>
    <a href="<?= BASE_URL ?>card-configurations/add" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> New Configuration
    </a>
</div>

<!-- Alerts -->
<?php if (!empty($_SESSION['success'])): ?>
<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3">
    <i class="fas fa-check-circle me-2"></i><?= escape($_SESSION['success']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['success']); endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3">
    <i class="fas fa-exclamation-circle me-2"></i><?= escape($_SESSION['error']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['error']); endif; ?>

<!-- Filter Bar -->
<div class="card mb-3 shadow-sm">
    <div class="card-body py-2 px-3">
        <form method="GET" action="<?= BASE_URL ?>card-configurations" class="row g-2 align-items-end">
            <div class="col-6 col-md-3">
                <label class="form-label form-label-sm mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <?php foreach (['active', 'inactive'] as $s): ?>
                        <option value="<?= $s ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label form-label-sm mb-1">Classification</label>
                <select name="classification" class="form-select form-select-sm">
                    <option value="">All</option>
                    <?php foreach (['silver', 'gold', 'platinum', 'diamond'] as $c): ?>
                        <option value="<?= $c ?>" <?= $filters['classification'] === $c ? 'selected' : '' ?>><?= ucfirst($c) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-auto ms-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i> Filter</button>
                <a href="<?= BASE_URL ?>card-configurations" class="btn btn-sm btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<?php if (empty($configs)): ?>
    <div class="alert alert-info">No configurations match your filters.</div>
<?php else: ?>
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Classification</th>
                    <th>Validity</th>
                    <th>Price</th>
                    <th>Cards Issued</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($configs as $cfg): ?>
            <tr>
                <td class="text-muted small"><?= $cfg['id'] ?></td>
                <td>
                    <a href="<?= BASE_URL ?>card-configurations/view?id=<?= $cfg['id'] ?>" class="fw-semibold text-decoration-none">
                        <?= escape($cfg['name']) ?>
                    </a>
                    <?php if ($cfg['is_publicly_selectable']): ?>
                        <span class="badge bg-success-subtle text-success ms-1 small">Public</span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge bg-<?= $classColors[$cfg['classification']] ?? 'secondary' ?>">
                        <?= ucfirst($cfg['classification']) ?>
                    </span>
                </td>
                <td><?= $cfg['validity_days'] ?> days</td>
                <td><?= $cfg['price'] > 0 ? '₹' . number_format($cfg['price'], 2) : '<span class="text-muted">Free</span>' ?></td>
                <td><?= number_format($cfg['cards_count'] ?? 0) ?></td>
                <td>
                    <span class="badge bg-<?= $cfg['status'] === 'active' ? 'success' : 'secondary' ?>">
                        <?= ucfirst($cfg['status']) ?>
                    </span>
                </td>
                <td class="text-end">
                    <a href="<?= BASE_URL ?>card-configurations/view?id=<?= $cfg['id'] ?>" class="btn btn-sm btn-outline-secondary" title="View">
                        <i class="fas fa-eye"></i>
                    </a>
                    <a href="<?= BASE_URL ?>card-configurations/edit?id=<?= $cfg['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                        <i class="fas fa-edit"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-center mb-0">
        <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="?page=<?= $currentPage - 1 ?>&status=<?= urlencode($filters['status']) ?>&classification=<?= urlencode($filters['classification']) ?>">
                <i class="fas fa-chevron-left"></i>
            </a>
        </li>
        <?php for ($p = max(1, $currentPage - 2); $p <= min($totalPages, $currentPage + 2); $p++): ?>
        <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
            <a class="page-link" href="?page=<?= $p ?>&status=<?= urlencode($filters['status']) ?>&classification=<?= urlencode($filters['classification']) ?>">
                <?= $p ?>
            </a>
        </li>
        <?php endfor; ?>
        <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="?page=<?= $currentPage + 1 ?>&status=<?= urlencode($filters['status']) ?>&classification=<?= urlencode($filters['classification']) ?>">
                <i class="fas fa-chevron-right"></i>
            </a>
        </li>
    </ul>
</nav>
<?php endif; ?>
<?php endif; ?>
