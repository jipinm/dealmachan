<?php
$pageTitle = 'Deal Maker Tasks';
$taskStatuses = ['assigned' => 'Assigned', 'in_progress' => 'In Progress', 'completed' => 'Completed', 'verified' => 'Verified'];
$taskTypes    = ['customer_assistance' => 'Customer Assistance', 'merchant_visit' => 'Merchant Visit', 'survey' => 'Survey', 'promotion' => 'Promotion', 'other' => 'Other'];
$statusColors = ['assigned' => 'secondary', 'in_progress' => 'warning', 'completed' => 'info', 'verified' => 'success'];
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Deal Maker Tasks <span class="badge bg-secondary ms-1"><?= $total ?></span></h5>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>deal-makers" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-users me-1"></i> Deal Makers
        </a>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addTaskModal">
            <i class="fas fa-plus me-1"></i> Assign Task
        </button>
    </div>
</div>

<!-- Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-2">
                <div class="fs-5 fw-bold text-secondary"><?= (int)$stats['tasks_assigned'] ?></div>
                <div class="text-muted small">Assigned</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-2">
                <div class="fs-5 fw-bold text-warning"><?= (int)$stats['tasks_in_progress'] ?></div>
                <div class="text-muted small">In Progress</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-2">
                <div class="fs-5 fw-bold text-info"><?= (int)$stats['tasks_completed'] ?></div>
                <div class="text-muted small">Completed</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-2">
                <div class="fs-5 fw-bold text-success"><?= (int)$stats['tasks_verified'] ?></div>
                <div class="text-muted small">Verified</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-2">
                <div class="fs-5 fw-bold text-danger"><?= (int)$stats['rewards_pending'] ?></div>
                <div class="text-muted small">Rewards Pending</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-2">
                <div class="fs-5 fw-bold text-success">₹<?= number_format($stats['total_rewards_paid'], 0) ?></div>
                <div class="text-muted small">Rewards Paid</div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-sm-3">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Search name or description…"
                       value="<?= htmlspecialchars($filters['search']) ?>">
            </div>
            <div class="col-sm-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <?php foreach ($taskStatuses as $val => $label): ?>
                        <option value="<?= $val ?>" <?= $filters['status'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-2">
                <select name="task_type" class="form-select form-select-sm">
                    <option value="">All Types</option>
                    <?php foreach ($taskTypes as $val => $label): ?>
                        <option value="<?= $val ?>" <?= $filters['task_type'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-2">
                <select name="reward_status" class="form-select form-select-sm">
                    <option value="">All Reward Status</option>
                    <option value="pending" <?= $filters['reward_status']==='pending' ? 'selected':'' ?>>Pending</option>
                    <option value="paid"    <?= $filters['reward_status']==='paid'    ? 'selected':'' ?>>Paid</option>
                </select>
            </div>
            <div class="col-sm-2">
                <select name="dealmaker_id" class="form-select form-select-sm">
                    <option value="">All Deal Makers</option>
                    <?php foreach ($dealmakers as $dm): ?>
                        <option value="<?= $dm['id'] ?>" <?= (int)$filters['dealmaker_id'] === (int)$dm['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dm['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search me-1"></i>Filter</button>
                <a href="<?= BASE_URL ?>deal-makers/tasks" class="btn btn-outline-secondary btn-sm ms-1">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Tasks Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Deal Maker</th>
                        <th>Task Type</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Reward</th>
                        <th>Assigned At</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($tasks)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No tasks found.</td></tr>
                <?php else: ?>
                    <?php foreach ($tasks as $i => $task): ?>
                    <tr>
                        <td class="text-muted small"><?= ($page - 1) * 25 + $i + 1 ?></td>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars($task['dealmaker_name']) ?></div>
                            <div class="text-muted small"><?= htmlspecialchars($task['dealmaker_phone']) ?></div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                <?= $taskTypes[$task['task_type']] ?? ucfirst($task['task_type']) ?>
                            </span>
                        </td>
                        <td>
                            <div class="text-truncate" style="max-width:220px" title="<?= htmlspecialchars($task['task_description']) ?>">
                                <?= htmlspecialchars($task['task_description']) ?>
                            </div>
                            <?php if ($task['completion_notes']): ?>
                                <div class="text-muted small fst-italic text-truncate" style="max-width:220px">
                                    Note: <?= htmlspecialchars($task['completion_notes']) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-<?= $statusColors[$task['status']] ?? 'secondary' ?>">
                                <?= $taskStatuses[$task['status']] ?? ucfirst($task['status']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($task['reward_amount']): ?>
                                <div>₹<?= number_format($task['reward_amount'], 2) ?></div>
                                <?php if ($task['reward_status'] === 'paid'): ?>
                                    <span class="badge bg-success small">Paid</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark small">Pending</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted small">
                            <?= date('d M Y', strtotime($task['assigned_at'])) ?>
                            <?php if ($task['completed_at']): ?>
                                <br><span class="text-success">Done: <?= date('d M Y', strtotime($task['completed_at'])) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <!-- Update Status -->
                            <button class="btn btn-outline-primary btn-sm" title="Update Status"
                                    data-bs-toggle="modal" data-bs-target="#updateTaskModal"
                                    data-id="<?= $task['id'] ?>"
                                    data-status="<?= $task['status'] ?>"
                                    data-notes="<?= htmlspecialchars($task['completion_notes'] ?? '') ?>">
                                <i class="fas fa-edit"></i>
                            </button>
                            <!-- Pay Reward -->
                            <?php if ($task['reward_amount'] && $task['reward_status'] === 'pending' && $task['status'] === 'verified'): ?>
                            <form method="POST" action="<?= BASE_URL ?>deal-makers/pay-reward" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                <button type="submit" class="btn btn-outline-success btn-sm" title="Mark Reward Paid">
                                    <i class="fas fa-rupee-sign"></i>
                                </button>
                            </form>
                            <?php endif; ?>
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

<!-- ─── MODALS ────────────────────────────────────────────────────────────── -->

<!-- Add Task Modal -->
<div class="modal fade" id="addTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= BASE_URL ?>deal-makers/add-task">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Assign New Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Deal Maker <span class="text-danger">*</span></label>
                        <select name="dealmaker_customer_id" class="form-select" required>
                            <option value="">Select Deal Maker…</option>
                            <?php foreach ($dealmakers as $dm): ?>
                                <option value="<?= $dm['id'] ?>"><?= htmlspecialchars($dm['name']) ?> (<?= $dm['phone'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Task Type <span class="text-danger">*</span></label>
                        <select name="task_type" class="form-select" required>
                            <option value="">Select type…</option>
                            <?php foreach ($taskTypes as $val => $label): ?>
                                <option value="<?= $val ?>"><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Task Description <span class="text-danger">*</span></label>
                        <textarea name="task_description" class="form-control" rows="3" required
                                  placeholder="Describe what the deal maker needs to do…"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Reward Amount (₹)</label>
                        <input type="number" name="reward_amount" class="form-control" min="0" step="0.01"
                               placeholder="Leave blank if no reward">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-check me-1"></i>Assign Task</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Update Task Status Modal -->
<div class="modal fade" id="updateTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= BASE_URL ?>deal-makers/update-task">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="task_id" id="updateTaskId">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Update Task Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                        <select name="status" id="updateTaskStatus" class="form-select" required>
                            <?php foreach ($taskStatuses as $val => $label): ?>
                                <option value="<?= $val ?>"><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Notes</label>
                        <textarea name="completion_notes" id="updateTaskNotes" class="form-control" rows="3"
                                  placeholder="Optional notes or completion remarks…"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Update</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var updateModal = document.getElementById('updateTaskModal');
    updateModal.addEventListener('show.bs.modal', function (event) {
        var btn = event.relatedTarget;
        document.getElementById('updateTaskId').value     = btn.dataset.id;
        document.getElementById('updateTaskStatus').value = btn.dataset.status;
        document.getElementById('updateTaskNotes').value  = btn.dataset.notes;
    });
});
</script>
