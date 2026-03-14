<?php
$pageTitle = 'Mystery Shopping';
$statusColors = ['assigned'=>'secondary','in_progress'=>'warning','completed'=>'info','verified'=>'success','rejected'=>'danger'];
$statusLabels = ['assigned'=>'Assigned','in_progress'=>'In Progress','completed'=>'Completed','verified'=>'Verified','rejected'=>'Rejected'];
?>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-2">
                <div class="fs-4 fw-bold"><?= (int)$stats['total'] ?></div>
                <div class="text-muted small">Total Tasks</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-2">
                <div class="fs-4 fw-bold text-warning"><?= (int)$stats['in_progress'] ?></div>
                <div class="text-muted small">In Progress</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-2">
                <div class="fs-4 fw-bold text-info"><?= (int)$stats['completed'] ?></div>
                <div class="text-muted small">Completed</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-2">
                <div class="fs-4 fw-bold text-success"><?= (int)$stats['verified'] ?></div>
                <div class="text-muted small">Verified</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-2">
                <div class="fs-4 fw-bold text-danger"><?= (int)$stats['payments_pending'] ?></div>
                <div class="text-muted small">Payments Pending</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-2">
                <div class="fs-4 fw-bold text-success">₹<?= number_format($stats['total_paid'], 0) ?></div>
                <div class="text-muted small">Total Paid</div>
            </div>
        </div>
    </div>
</div>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Mystery Shopping Tasks <span class="badge bg-secondary ms-1"><?= $total ?></span></h5>
    <a href="<?= BASE_URL ?>mystery-shopping/add" class="btn btn-primary btn-sm">
        <i class="fas fa-plus me-1"></i> Assign Task
    </a>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-sm-3">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Search shopper, phone, merchant…"
                       value="<?= htmlspecialchars($filters['search']) ?>">
            </div>
            <div class="col-sm-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <?php foreach ($statusLabels as $val => $label): ?>
                        <option value="<?= $val ?>" <?= $filters['status'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-2">
                <select name="payment_status" class="form-select form-select-sm">
                    <option value="">All Payments</option>
                    <option value="pending" <?= $filters['payment_status']==='pending' ? 'selected':'' ?>>Pending</option>
                    <option value="paid"    <?= $filters['payment_status']==='paid'    ? 'selected':'' ?>>Paid</option>
                </select>
            </div>
            <div class="col-sm-3">
                <select name="merchant_id" class="form-select form-select-sm">
                    <option value="">All Merchants</option>
                    <?php foreach ($merchants as $mer): ?>
                        <option value="<?= $mer['id'] ?>" <?= (int)$filters['merchant_id'] === (int)$mer['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($mer['business_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search me-1"></i>Filter</button>
                <a href="<?= BASE_URL ?>mystery-shopping" class="btn btn-outline-secondary btn-sm ms-1">Clear</a>
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
                        <th>Shopper</th>
                        <th>Merchant / Store</th>
                        <th>Task</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Assigned</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($tasks)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No mystery shopping tasks found.</td></tr>
                <?php else: ?>
                    <?php foreach ($tasks as $i => $task): ?>
                    <tr>
                        <td class="text-muted small"><?= ($page - 1) * 20 + $i + 1 ?></td>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars($task['shopper_name']) ?></div>
                            <div class="text-muted small"><?= htmlspecialchars($task['shopper_phone']) ?></div>
                        </td>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars($task['merchant_name']) ?></div>
                            <?php if ($task['store_name']): ?>
                                <div class="text-muted small"><i class="fas fa-store me-1"></i><?= htmlspecialchars($task['store_name']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="text-truncate" style="max-width:200px" title="<?= htmlspecialchars($task['task_description']) ?>">
                                <?= htmlspecialchars($task['task_description']) ?>
                            </div>
                            <?php if ($task['checklist_json']): ?>
                                <?php $cl = json_decode($task['checklist_json'], true) ?? []; ?>
                                <div class="text-muted small"><?= count($cl) ?> checklist items</div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-<?= $statusColors[$task['status']] ?? 'secondary' ?>">
                                <?= $statusLabels[$task['status']] ?? ucfirst($task['status']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($task['payment_amount']): ?>
                                <div>₹<?= number_format($task['payment_amount'], 2) ?></div>
                                <span class="badge <?= $task['payment_status']==='paid' ? 'bg-success' : 'bg-warning text-dark' ?> small">
                                    <?= ucfirst($task['payment_status']) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted small">&mdash;</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted small"><?= date('d M Y', strtotime($task['assigned_at'])) ?></td>
                        <td class="text-center">
                            <a href="<?= BASE_URL ?>mystery-shopping/reports?id=<?= $task['id'] ?>"
                               class="btn btn-outline-primary btn-sm" title="View / Manage">
                                <i class="fas fa-eye"></i>
                            </a>
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
            <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page'=>$page-1,'limit'=>null,'offset'=>null])) ?>">&laquo;</a>
        </li>
        <?php for ($p = max(1,$page-2); $p <= min($totalPages,$page+2); $p++): ?>
        <li class="page-item <?= $p===$page?'active':'' ?>">
            <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page'=>$p,'limit'=>null,'offset'=>null])) ?>"><?= $p ?></a>
        </li>
        <?php endfor; ?>
        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page'=>$page+1,'limit'=>null,'offset'=>null])) ?>">&raquo;</a>
        </li>
    </ul>
</nav>
<?php endif; ?>
