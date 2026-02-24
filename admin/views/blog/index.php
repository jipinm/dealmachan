<?php /* views/blog/index.php */ ?>

<!-- Stats -->
<div class="row g-3 mb-4">
    <?php foreach ([
        ['label' => 'Total',      'value' => $stats['total']     ?? 0, 'color' => 'primary'],
        ['label' => 'Draft',      'value' => $stats['draft']     ?? 0, 'color' => 'secondary'],
        ['label' => 'Published',  'value' => $stats['published'] ?? 0, 'color' => 'success'],
        ['label' => 'Archived',   'value' => $stats['archived']  ?? 0, 'color' => 'dark'],
        ['label' => 'This Month', 'value' => $stats['this_month'] ?? 0, 'color' => 'info'],
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
        <h4 class="mb-0">Blog / CMS</h4>
        <small class="text-muted">
            <?php
            $from = min($totalCount, ($currentPage - 1) * $perPage + 1);
            $to   = min($totalCount, $currentPage * $perPage);
            echo $totalCount ? "Showing {$from}–{$to} of {$totalCount}" : 'No posts found';
            ?>
        </small>
    </div>
    <a href="<?= BASE_URL ?>blog/add" class="btn btn-primary btn-sm">
        <i class="fas fa-plus me-1"></i> New Post
    </a>
</div>

<!-- Status Filter Tabs -->
<?php $activeStatus = $filters['status'] ?? ''; ?>
<ul class="nav nav-tabs mb-3">
    <?php foreach (['All' => '', 'Draft' => 'draft', 'Published' => 'published', 'Archived' => 'archived'] as $label => $val): ?>
    <li class="nav-item">
        <a class="nav-link <?= $activeStatus === $val ? 'active' : '' ?>"
           href="?<?= http_build_query(array_merge($_GET, ['status' => $val, 'page' => 1])) ?>">
            <?= $label ?>
        </a>
    </li>
    <?php endforeach; ?>
</ul>

<!-- Search Filter -->
<div class="card mb-3 shadow-sm">
    <div class="card-body py-2 px-3">
        <form method="GET" action="<?= BASE_URL ?>blog" class="row g-2 align-items-end">
            <?php if ($activeStatus): ?>
            <input type="hidden" name="status" value="<?= escape($activeStatus) ?>">
            <?php endif; ?>
            <div class="col-12 col-md-5">
                <label class="form-label form-label-sm mb-1">Search</label>
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Title or slug…" value="<?= escape($filters['search'] ?? '') ?>">
            </div>
            <div class="col-auto ms-auto d-flex gap-2">
                <button class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i> Filter</button>
                <a href="<?= BASE_URL ?>blog<?= $activeStatus ? '?status='.$activeStatus : '' ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<?php
$statusColors = ['draft' => 'secondary', 'published' => 'success', 'archived' => 'dark'];
?>

<?php if (empty($posts)): ?>
<div class="alert alert-info border-0 shadow-sm"><i class="fas fa-info-circle me-2"></i>No posts found.</div>
<?php else: ?>
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Title</th>
                    <th>Slug</th>
                    <th>Author</th>
                    <th>Status</th>
                    <th>Published</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($posts as $p): ?>
            <tr>
                <td class="text-muted small"><?= $p['id'] ?></td>
                <td>
                    <div class="fw-semibold"><?= escape($p['title']) ?></div>
                    <?php if (!empty($p['excerpt'])): ?>
                    <div class="text-muted small text-truncate" style="max-width:300px;" title="<?= strip_tags(escape($p['excerpt'])) ?>">
                        <?= strip_tags(mb_substr($p['excerpt'] ?? '', 0, 100)) ?>…
                    </div>
                    <?php endif; ?>
                </td>
                <td><code class="small"><?= escape($p['slug']) ?></code></td>
                <td class="small"><?= escape($p['author_name'] ?? '—') ?></td>
                <td>
                    <span class="badge bg-<?= $statusColors[$p['status']] ?? 'secondary' ?>">
                        <?= ucfirst($p['status']) ?>
                    </span>
                </td>
                <td class="small text-muted">
                    <?= $p['published_at'] ? date('d M Y', strtotime($p['published_at'])) : '—' ?>
                </td>
                <td class="text-end">
                    <a href="<?= BASE_URL ?>blog/detail?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-eye"></i>
                    </a>
                    <a href="<?= BASE_URL ?>blog/edit?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-warning ms-1">
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
<div class="d-flex justify-content-center mt-3">
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
