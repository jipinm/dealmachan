<?php $pageTitle = 'Contest Management'; ?>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-2">
                <div class="fs-4 fw-bold"><?= (int)($stats['total'] ?? 0) ?></div>
                <div class="text-muted small">Total</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-2">
                <div class="fs-4 fw-bold text-secondary"><?= (int)($stats['draft'] ?? 0) ?></div>
                <div class="text-muted small">Draft</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-2">
                <div class="fs-4 fw-bold text-success"><?= (int)($stats['active'] ?? 0) ?></div>
                <div class="text-muted small">Active</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-2">
                <div class="fs-4 fw-bold text-primary"><?= (int)($stats['completed'] ?? 0) ?></div>
                <div class="text-muted small">Completed</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-2">
                <div class="fs-4 fw-bold text-warning"><?= (int)($stats['total_participants'] ?? 0) ?></div>
                <div class="text-muted small">Participants</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-2">
                <div class="fs-4 fw-bold text-info"><?= (int)($stats['total_winners'] ?? 0) ?></div>
                <div class="text-muted small">Winners</div>
            </div>
        </div>
    </div>
</div>

<!-- Alerts -->
<?php if (!empty($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= htmlspecialchars($_GET['success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php elseif (!empty($_GET['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= htmlspecialchars($_GET['error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Filter Bar -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label small mb-1">Search</label>
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Search contests..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <?php foreach (['draft','active','completed','cancelled'] as $s): ?>
                        <option value="<?= $s ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>>
                            <?= ucfirst($s) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
            </div>
            <div class="col-md-2 text-end">
                <a href="<?= BASE_URL ?>contests/add" class="btn btn-success btn-sm w-100">
                    <i class="bi bi-plus-circle me-1"></i>New Contest
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Dates</th>
                        <th class="text-center">Participants</th>
                        <th class="text-center">Winners</th>
                        <th>Created By</th>
                        <th>Created</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($contests)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">No contests found.</td></tr>
                <?php else: ?>
                    <?php foreach ($contests as $c): ?>
                    <tr>
                        <td class="text-muted small"><?= $c['id'] ?></td>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars($c['title']) ?></div>
                            <?php if ($c['description']): ?>
                                <div class="text-muted small"><?= htmlspecialchars(mb_strimwidth($c['description'], 0, 60, '…')) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $badge = ['draft'=>'secondary','active'=>'success','completed'=>'primary','cancelled'=>'danger'];
                            $b = $badge[$c['status']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?= $b ?>"><?= ucfirst($c['status']) ?></span>
                        </td>
                        <td class="small">
                            <?php if ($c['start_date'] || $c['end_date']): ?>
                                <?= $c['start_date'] ? date('d M Y', strtotime($c['start_date'])) : '&mdash;' ?>
                                →
                                <?= $c['end_date'] ? date('d M Y', strtotime($c['end_date'])) : '&mdash;' ?>
                            <?php else: ?>
                                <span class="text-muted">&mdash;</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-warning text-dark"><?= (int)$c['participant_count'] ?></span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-info text-dark"><?= (int)$c['winner_count'] ?></span>
                        </td>
                        <td class="small text-muted"><?= htmlspecialchars($c['created_by_name'] ?? '&mdash;') ?></td>
                        <td class="small text-muted"><?= date('d M Y', strtotime($c['created_at'])) ?></td>
                        <td class="text-end">
                            <div class="d-flex gap-1 justify-content-end flex-wrap">
                                <!-- Winners/Participants -->
                                <a href="<?= BASE_URL ?>contests/winners?id=<?= $c['id'] ?>"
                                   class="btn btn-sm btn-outline-primary" title="Winners & Participants">
                                    <i class="bi bi-trophy"></i>
                                </a>
                                <!-- Edit (only draft) -->
                                <?php if ($c['status'] === 'draft'): ?>
                                    <a href="<?= BASE_URL ?>contests/edit?id=<?= $c['id'] ?>"
                                       class="btn btn-sm btn-outline-secondary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                <?php endif; ?>
                                <!-- Toggle buttons -->
                                <?php if ($c['status'] === 'draft'): ?>
                                    <form method="POST" action="<?= BASE_URL ?>contests/toggle" class="d-inline">
                                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                        <input type="hidden" name="action" value="activate">
                                        <button type="submit" class="btn btn-sm btn-success" title="Activate">
                                            <i class="bi bi-play-circle"></i>
                                        </button>
                                    </form>
                                <?php elseif ($c['status'] === 'active'): ?>
                                    <form method="POST" action="<?= BASE_URL ?>contests/toggle" class="d-inline">
                                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                        <input type="hidden" name="action" value="complete">
                                        <button type="submit" class="btn btn-sm btn-primary" title="Mark Completed">
                                            <i class="bi bi-check-circle"></i>
                                        </button>
                                    </form>
                                    <form method="POST" action="<?= BASE_URL ?>contests/toggle" class="d-inline">
                                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                        <input type="hidden" name="action" value="cancel">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Cancel">
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <!-- Delete (only draft) -->
                                <?php if ($c['status'] === 'draft'): ?>
                                    <form method="POST" action="<?= BASE_URL ?>contests/delete" class="d-inline"
                                          onsubmit="return confirm('Delete this contest?')">
                                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
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
    <?php if ($pages > 1): ?>
    <div class="card-footer bg-white border-0">
        <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted">Showing <?= count($contests) ?> of <?= $total ?></small>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <?php for ($p = 1; $p <= $pages; $p++): ?>
                        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $p ?>&<?= http_build_query(array_filter($filters)) ?>">
                                <?= $p ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    </div>
    <?php endif; ?>
</div>
