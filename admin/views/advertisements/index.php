<?php /* views/advertisements/index.php */
$now = time();
?>

<!-- Stats -->
<div class="row g-3 mb-4">
    <?php foreach ([
        ['label' => 'Total',    'value' => $stats['total']    ?? 0, 'color' => 'primary'],
        ['label' => 'Active',   'value' => $stats['active']   ?? 0, 'color' => 'success'],
        ['label' => 'Inactive', 'value' => $stats['inactive'] ?? 0, 'color' => 'secondary'],
        ['label' => 'Live Now', 'value' => $stats['live_now'] ?? 0, 'color' => 'warning'],
        ['label' => 'Images',   'value' => $stats['images']   ?? 0, 'color' => 'info'],
        ['label' => 'Videos',   'value' => $stats['videos']   ?? 0, 'color' => 'dark'],
    ] as $c): ?>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card text-bg-<?= $c['color'] ?> h-100 shadow-sm">
            <div class="card-body py-3 px-3">
                <div class="small fw-semibold text-white-50"><?= $c['label'] ?></div>
                <div class="fs-4 fw-bold"><?= number_format($c['value']) ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Header -->
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="mb-0">Advertisement Management</h4>
        <small class="text-muted">
            <?php
            $from = min($totalCount, ($currentPage - 1) * $perPage + 1);
            $to   = min($totalCount, $currentPage * $perPage);
            echo $totalCount ? "Showing {$from}&ndash;{$to} of {$totalCount}" : 'No advertisements found';
            ?>
        </small>
    </div>
    <a href="<?= BASE_URL ?>advertisements/add" class="btn btn-primary btn-sm">
        <i class="fas fa-plus me-1"></i> Create Ad
    </a>
</div>

<!-- Filters -->
<div class="card mb-3 shadow-sm">
    <div class="card-body py-2 px-3">
        <form method="GET" action="<?= BASE_URL ?>advertisements" class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label form-label-sm mb-1">Search</label>
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Search by title" value="<?= escape($filters['search'] ?? '') ?>">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label form-label-sm mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="active"   <?= ($filters['status'] ?? '') === 'active'   ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label form-label-sm mb-1">Media Type</label>
                <select name="media_type" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="image" <?= ($filters['media_type'] ?? '') === 'image' ? 'selected' : '' ?>>Image</option>
                    <option value="video" <?= ($filters['media_type'] ?? '') === 'video' ? 'selected' : '' ?>>Video</option>
                </select>
            </div>
            <div class="col-12 col-md-auto ms-md-auto d-flex gap-2">
                <a href="<?= BASE_URL ?>advertisements?live=1" class="btn btn-sm btn-outline-warning">Live Now</a>
                <button class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i>Filter</button>
                <a href="<?= BASE_URL ?>advertisements" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Grid view -->
<?php if (empty($ads)): ?>
<div class="alert alert-info border-0 shadow-sm"><i class="fas fa-info-circle me-2"></i>No advertisements found.</div>
<?php else: ?>
<div class="row g-3 mb-4">
    <?php foreach ($ads as $ad):
        $isLive = $ad['status'] === 'active'
               && ($ad['start_date'] === null || strtotime($ad['start_date']) <= $now)
               && ($ad['end_date']   === null || strtotime($ad['end_date'])   >= $now);
    ?>
    <div class="col-md-6 col-xl-4">
        <div class="card h-100 shadow-sm border-<?= $ad['status'] === 'active' ? '0' : 'secondary' ?>">

            <!-- Thumbnail -->
            <div class="position-relative bg-dark" style="height:140px;overflow:hidden;border-radius:.375rem .375rem 0 0;">
                <?php if ($ad['media_type'] === 'image'): ?>
                    <img src="<?= imageUrl($ad['media_url']) ?>"
                         class="w-100 h-100 object-fit-cover" alt="<?= escape($ad['title']) ?>"
                         onerror="this.src='<?= imageUrl('') ?>'">
                <?php else: ?>
                    <div class="d-flex align-items-center justify-content-center h-100">
                        <i class="fas fa-play-circle text-white fa-3x opacity-50"></i>
                    </div>
                <?php endif; ?>
                <?php if ($isLive): ?>
                <span class="badge bg-danger position-absolute top-0 end-0 m-2">&mdash; LIVE</span>
                <?php endif; ?>
                <span class="badge bg-<?= $ad['media_type'] === 'image' ? 'info' : 'dark' ?> position-absolute top-0 start-0 m-2">
                    <i class="fas fa-<?= $ad['media_type'] === 'image' ? 'image' : 'video' ?> me-1"></i><?= ucfirst($ad['media_type']) ?>
                </span>
            </div>

            <div class="card-body">
                <h6 class="fw-semibold mb-1"><?= escape($ad['title']) ?></h6>
                <div class="text-muted small mb-2">
                    <i class="fas fa-clock me-1"></i><?= $ad['display_duration'] ?>s duration
                </div>
                <div class="d-flex flex-wrap gap-1 mb-2">
                    <span class="badge bg-<?= $ad['status'] === 'active' ? 'success' : 'secondary' ?>"><?= ucfirst($ad['status']) ?></span>
                </div>
                <?php if ($ad['start_date'] || $ad['end_date']): ?>
                <div class="text-muted small">
                    <?= $ad['start_date'] ? date('d M Y', strtotime($ad['start_date'])) : '&mdash;' ?>
                    &rarr; <?= $ad['end_date'] ? date('d M Y', strtotime($ad['end_date'])) : '&infin;' ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-white d-flex gap-2 border-top-0 pt-0">
                <a href="<?= BASE_URL ?>advertisements/detail?id=<?= $ad['id'] ?>" class="btn btn-sm btn-outline-primary flex-grow-1">
                    <i class="fas fa-eye me-1"></i> View
                </a>
                <form method="POST" action="<?= BASE_URL ?>advertisements/toggle" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="id" value="<?= $ad['id'] ?>">
                    <input type="hidden" name="redirect" value="advertisements">
                    <button type="submit" class="btn btn-sm btn-outline-<?= $ad['status'] === 'active' ? 'warning' : 'success' ?>">
                        <i class="fas fa-<?= $ad['status'] === 'active' ? 'pause' : 'play' ?>"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div class="d-flex justify-content-center">
    <nav><ul class="pagination pagination-sm mb-0">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"><?= $p ?></a>
        </li>
        <?php endfor; ?>
    </ul></nav>
</div>
<?php endif; ?>
<?php endif; ?>
