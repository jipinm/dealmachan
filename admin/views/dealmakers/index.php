<?php
$pageTitle = 'Deal Makers';
?>

<!-- Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                    <i class="fas fa-user-tie text-primary fs-4"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold"><?= (int)$stats['total_dealmakers'] ?></div>
                    <div class="text-muted small">Total Deal Makers</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                    <i class="fas fa-clock text-warning fs-4"></i>
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
                <div class="rounded-circle bg-success bg-opacity-10 p-3">
                    <i class="fas fa-check-circle text-success fs-4"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold"><?= (int)$stats['approved_this_month'] ?></div>
                    <div class="text-muted small">Approved This Month</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-info bg-opacity-10 p-3">
                    <i class="fas fa-tasks text-info fs-4"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold"><?= (int)$stats['total_tasks'] ?></div>
                    <div class="text-muted small">Total Tasks</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Approved Deal Makers <span class="badge bg-secondary ms-1"><?= $total ?></span></h5>
    <div class="d-flex gap-2">
        <?php if ((int)$stats['pending_requests'] > 0): ?>
            <a href="<?= BASE_URL ?>deal-makers/requests" class="btn btn-warning btn-sm">
                <i class="fas fa-bell me-1"></i> <?= (int)$stats['pending_requests'] ?> Pending
            </a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>deal-makers/tasks" class="btn btn-outline-primary btn-sm">
            <i class="fas fa-tasks me-1"></i> View Tasks
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-sm-5">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Search by name, phone, email…"
                       value="<?= htmlspecialchars($filters['search']) ?>">
            </div>
            <div class="col-sm-3">
                <select name="user_status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="active"   <?= $filters['user_status']==='active'   ? 'selected':'' ?>>Active</option>
                    <option value="inactive" <?= $filters['user_status']==='inactive' ? 'selected':'' ?>>Inactive</option>
                    <option value="blocked"  <?= $filters['user_status']==='blocked'  ? 'selected':'' ?>>Blocked</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search me-1"></i>Search</button>
                <a href="<?= BASE_URL ?>deal-makers" class="btn btn-outline-secondary btn-sm ms-1">Clear</a>
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
                        <th>Tasks</th>
                        <th>Rewards Paid</th>
                        <th>Approved At</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($dealmakers)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">No deal makers found.</td></tr>
                <?php else: ?>
                    <?php foreach ($dealmakers as $i => $dm): ?>
                    <tr>
                        <td class="text-muted small"><?= ($page - 1) * 25 + $i + 1 ?></td>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars($dm['name']) ?></div>
                            <div class="text-muted small"><?= $dm['gender'] ? ucfirst($dm['gender']) : '&mdash;' ?></div>
                        </td>
                        <td><?= htmlspecialchars($dm['phone']) ?></td>
                        <td class="text-muted small"><?= htmlspecialchars($dm['email']) ?></td>
                        <td>
                            <span class="badge bg-primary"><?= (int)$dm['total_tasks'] ?> total</span>
                            <span class="badge bg-success ms-1"><?= (int)$dm['completed_tasks'] ?> done</span>
                        </td>
                        <td>₹<?= number_format($dm['total_rewards_paid'], 2) ?></td>
                        <td class="text-muted small">
                            <?= $dm['dealmaker_approved_at'] ? date('d M Y', strtotime($dm['dealmaker_approved_at'])) : '&mdash;' ?>
                        </td>
                        <td>
                            <?php if ($dm['user_status'] === 'active'): ?>
                                <span class="badge bg-success">Active</span>
                            <?php elseif ($dm['user_status'] === 'blocked'): ?>
                                <span class="badge bg-danger">Blocked</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><?= ucfirst($dm['user_status']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <a href="<?= BASE_URL ?>deal-makers/tasks?dealmaker_id=<?= $dm['id'] ?>"
                               class="btn btn-outline-primary btn-sm" title="View Tasks">
                                <i class="fas fa-tasks"></i>
                            </a>
                            <form method="POST" action="<?= BASE_URL ?>deal-makers/revoke" class="d-inline"
                                  onsubmit="return confirm('Revoke Deal Maker status for <?= htmlspecialchars($dm['name']) ?>?')">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                <input type="hidden" name="customer_id" value="<?= $dm['id'] ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm" title="Revoke">
                                    <i class="fas fa-user-slash"></i>
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
            <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $page - 1, 'limit' => null, 'offset' => null])) ?>">
                &laquo;
            </a>
        </li>
        <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
            <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $p, 'limit' => null, 'offset' => null])) ?>">
                <?= $p ?>
            </a>
        </li>
        <?php endfor; ?>
        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $page + 1, 'limit' => null, 'offset' => null])) ?>">
                &raquo;
            </a>
        </li>
    </ul>
</nav>
<?php endif; ?>
