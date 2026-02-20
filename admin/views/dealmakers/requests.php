<?php
$pageTitle = 'Deal Maker Requests';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Pending Deal Maker Requests <span class="badge bg-warning text-dark ms-1"><?= $total ?></span></h5>
    <a href="<?= BASE_URL ?>deal-makers" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i> Back to Deal Makers
    </a>
</div>

<!-- Stats Summary -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                    <i class="fas fa-hourglass-half text-warning fs-4"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold"><?= (int)$stats['pending_requests'] ?></div>
                    <div class="text-muted small">Pending Requests</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                    <i class="fas fa-user-check text-primary fs-4"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold"><?= (int)$stats['total_dealmakers'] ?></div>
                    <div class="text-muted small">Approved Deal Makers</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-success bg-opacity-10 p-3">
                    <i class="fas fa-calendar-check text-success fs-4"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold"><?= (int)$stats['approved_this_month'] ?></div>
                    <div class="text-muted small">Approved This Month</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Search Filter -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-sm-6">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Search by name, phone, email…"
                       value="<?= htmlspecialchars($filters['search']) ?>">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search me-1"></i>Search</button>
                <a href="<?= BASE_URL ?>deal-makers/requests" class="btn btn-outline-secondary btn-sm ms-1">Clear</a>
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
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Gender</th>
                        <th>Registered At</th>
                        <th>Account Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($requests)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="fas fa-check-circle text-success fs-2 mb-2 d-block"></i>
                            No pending requests. All caught up!
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($requests as $i => $req): ?>
                    <tr>
                        <td class="text-muted small"><?= ($page - 1) * 25 + $i + 1 ?></td>
                        <td class="fw-semibold"><?= htmlspecialchars($req['name']) ?></td>
                        <td><?= htmlspecialchars($req['phone']) ?></td>
                        <td class="text-muted small"><?= htmlspecialchars($req['email']) ?></td>
                        <td><?= $req['gender'] ? ucfirst($req['gender']) : '—' ?></td>
                        <td class="text-muted small"><?= date('d M Y', strtotime($req['created_at'])) ?></td>
                        <td>
                            <?php if ($req['user_status'] === 'active'): ?>
                                <span class="badge bg-success">Active</span>
                            <?php elseif ($req['user_status'] === 'blocked'): ?>
                                <span class="badge bg-danger">Blocked</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><?= ucfirst($req['user_status']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <!-- Approve -->
                            <form method="POST" action="<?= BASE_URL ?>deal-makers/approve" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                <input type="hidden" name="customer_id" value="<?= $req['id'] ?>">
                                <button type="submit" class="btn btn-success btn-sm" title="Approve">
                                    <i class="fas fa-check me-1"></i> Approve
                                </button>
                            </form>
                            <!-- Reject / Revert to Standard -->
                            <form method="POST" action="<?= BASE_URL ?>deal-makers/revoke" class="d-inline ms-1"
                                  onsubmit="return confirm('Reject this Deal Maker request?')">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                <input type="hidden" name="customer_id" value="<?= $req['id'] ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm" title="Reject">
                                    <i class="fas fa-times me-1"></i> Reject
                                </button>
                            </form>
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
