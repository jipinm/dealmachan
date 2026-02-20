<?php $pageTitle = 'Surveys'; ?>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-2">
                <div class="fs-4 fw-bold"><?= (int)$stats['total'] ?></div>
                <div class="text-muted small">Total</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-2">
                <div class="fs-4 fw-bold text-secondary"><?= (int)$stats['draft'] ?></div>
                <div class="text-muted small">Draft</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-2">
                <div class="fs-4 fw-bold text-success"><?= (int)$stats['active'] ?></div>
                <div class="text-muted small">Active</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-2">
                <div class="fs-4 fw-bold text-danger"><?= (int)$stats['closed'] ?></div>
                <div class="text-muted small">Closed</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-2">
                <div class="fs-4 fw-bold text-primary"><?= (int)$stats['total_responses'] ?></div>
                <div class="text-muted small">Responses</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-2">
                <div class="fs-4 fw-bold text-info"><?= (int)$stats['created_today'] ?></div>
                <div class="text-muted small">Created Today</div>
            </div>
        </div>
    </div>
</div>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Surveys <span class="badge bg-secondary ms-1"><?= $total ?></span></h5>
    <a href="<?= BASE_URL ?>surveys/add" class="btn btn-primary btn-sm">
        <i class="fas fa-plus me-1"></i> Create Survey
    </a>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-sm-5">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Search title or description…"
                       value="<?= htmlspecialchars($filters['search']) ?>">
            </div>
            <div class="col-sm-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="draft"  <?= $filters['status']==='draft'  ? 'selected':'' ?>>Draft</option>
                    <option value="active" <?= $filters['status']==='active' ? 'selected':'' ?>>Active</option>
                    <option value="closed" <?= $filters['status']==='closed' ? 'selected':'' ?>>Closed</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search me-1"></i>Search</button>
                <a href="<?= BASE_URL ?>surveys" class="btn btn-outline-secondary btn-sm ms-1">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Questions</th>
                        <th>Responses</th>
                        <th>Active Period</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($surveys)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No surveys found.</td></tr>
                <?php else: ?>
                    <?php foreach ($surveys as $i => $sv): ?>
                    <?php
                        $questions = json_decode($sv['questions_json'], true) ?? [];
                        $qCount    = count($questions);
                        $statusColors = ['draft' => 'secondary', 'active' => 'success', 'closed' => 'danger'];
                    ?>
                    <tr>
                        <td class="text-muted small"><?= ($page - 1) * 20 + $i + 1 ?></td>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars($sv['title']) ?></div>
                            <?php if ($sv['description']): ?>
                                <div class="text-muted small text-truncate" style="max-width:260px">
                                    <?= htmlspecialchars($sv['description']) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge bg-light text-dark border"><?= $qCount ?> Q</span></td>
                        <td>
                            <a href="<?= BASE_URL ?>surveys/responses?id=<?= $sv['id'] ?>" class="text-decoration-none">
                                <span class="badge bg-primary"><?= (int)$sv['response_count'] ?></span>
                            </a>
                        </td>
                        <td class="text-muted small">
                            <?php if ($sv['active_from'] || $sv['active_until']): ?>
                                <?= $sv['active_from'] ? date('d M Y', strtotime($sv['active_from'])) : '∞' ?>
                                &rarr;
                                <?= $sv['active_until'] ? date('d M Y', strtotime($sv['active_until'])) : '∞' ?>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-<?= $statusColors[$sv['status']] ?? 'secondary' ?>">
                                <?= ucfirst($sv['status']) ?>
                            </span>
                        </td>
                        <td class="text-muted small"><?= htmlspecialchars($sv['created_by_name'] ?? '—') ?></td>
                        <td class="text-center">
                            <!-- Status toggle -->
                            <div class="btn-group btn-group-sm">
                                <?php if ($sv['status'] !== 'active'): ?>
                                <form method="POST" action="<?= BASE_URL ?>surveys/toggle" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                    <input type="hidden" name="survey_id" value="<?= $sv['id'] ?>">
                                    <input type="hidden" name="status" value="active">
                                    <button type="submit" class="btn btn-outline-success btn-sm" title="Activate">
                                        <i class="fas fa-play"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                                <?php if ($sv['status'] === 'active'): ?>
                                <form method="POST" action="<?= BASE_URL ?>surveys/toggle" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                    <input type="hidden" name="survey_id" value="<?= $sv['id'] ?>">
                                    <input type="hidden" name="status" value="closed">
                                    <button type="submit" class="btn btn-outline-warning btn-sm" title="Close">
                                        <i class="fas fa-stop"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                                <a href="<?= BASE_URL ?>surveys/edit?id=<?= $sv['id'] ?>"
                                   class="btn btn-outline-primary btn-sm" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="<?= BASE_URL ?>surveys/responses?id=<?= $sv['id'] ?>"
                                   class="btn btn-outline-info btn-sm" title="View Responses">
                                    <i class="fas fa-chart-bar"></i>
                                </a>
                                <?php if ($sv['status'] === 'draft'): ?>
                                <form method="POST" action="<?= BASE_URL ?>surveys/delete" class="d-inline"
                                      onsubmit="return confirm('Delete this draft survey?')">
                                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                    <input type="hidden" name="survey_id" value="<?= $sv['id'] ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-center mb-0">
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $page - 1, 'limit' => null, 'offset' => null])) ?>">&laquo;</a>
        </li>
        <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
            <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $p, 'limit' => null, 'offset' => null]))?>"><?= $p ?></a>
        </li>
        <?php endfor; ?>
        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $page + 1, 'limit' => null, 'offset' => null])) ?>">&raquo;</a>
        </li>
    </ul>
</nav>
<?php endif; ?>
