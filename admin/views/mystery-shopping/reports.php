<?php
$pageTitle = 'Mystery Shopping Report';
$statusColors = ['assigned'=>'secondary','in_progress'=>'warning','completed'=>'info','verified'=>'success','rejected'=>'danger'];
$statusLabels = ['assigned'=>'Assigned','in_progress'=>'In Progress','completed'=>'Completed','verified'=>'Verified','rejected'=>'Rejected'];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0">Task #<?= $task['id'] ?> &mdash; Mystery Shopping Report</h5>
    <a href="<?= BASE_URL ?>mystery-shopping" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i> Back to List
    </a>
</div>

<div class="row g-4">
    <!-- Left: Task Info -->
    <div class="col-lg-5">

        <!-- Status Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Task Status</span>
                <span class="badge bg-<?= $statusColors[$task['status']] ?? 'secondary' ?> fs-6">
                    <?= $statusLabels[$task['status']] ?? ucfirst($task['status']) ?>
                </span>
            </div>
            <div class="card-body">
                <div class="row g-2 text-sm">
                    <div class="col-5 text-muted">Shopper</div>
                    <div class="col-7 fw-semibold"><?= htmlspecialchars($task['shopper_name']) ?></div>
                    <div class="col-5 text-muted">Phone</div>
                    <div class="col-7"><?= htmlspecialchars($task['shopper_phone']) ?></div>
                    <?php if ($task['shopper_email']): ?>
                    <div class="col-5 text-muted">Email</div>
                    <div class="col-7"><?= htmlspecialchars($task['shopper_email']) ?></div>
                    <?php endif; ?>
                    <div class="col-12"><hr class="my-2"></div>
                    <div class="col-5 text-muted">Merchant</div>
                    <div class="col-7 fw-semibold"><?= htmlspecialchars($task['merchant_name']) ?></div>
                    <?php if ($task['store_name']): ?>
                    <div class="col-5 text-muted">Store</div>
                    <div class="col-7">
                        <?= htmlspecialchars($task['store_name']) ?>
                        <?php if ($task['store_address']): ?>
                            <div class="text-muted small"><?= htmlspecialchars($task['store_address']) ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <div class="col-12"><hr class="my-2"></div>
                    <div class="col-5 text-muted">Assigned By</div>
                    <div class="col-7"><?= htmlspecialchars($task['assigned_by_name'] ?? '—') ?></div>
                    <div class="col-5 text-muted">Assigned At</div>
                    <div class="col-7"><?= date('d M Y H:i', strtotime($task['assigned_at'])) ?></div>
                    <?php if ($task['completed_at']): ?>
                    <div class="col-5 text-muted">Completed At</div>
                    <div class="col-7"><?= date('d M Y H:i', strtotime($task['completed_at'])) ?></div>
                    <?php endif; ?>
                    <?php if ($task['verified_at']): ?>
                    <div class="col-5 text-muted">Verified At</div>
                    <div class="col-7"><?= date('d M Y H:i', strtotime($task['verified_at'])) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Payment Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent fw-semibold">Payment</div>
            <div class="card-body">
                <?php if ($task['payment_amount']): ?>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fs-4 fw-bold">₹<?= number_format($task['payment_amount'], 2) ?></div>
                            <span class="badge <?= $task['payment_status']==='paid' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                <?= ucfirst($task['payment_status']) ?>
                            </span>
                        </div>
                        <?php if ($task['payment_status'] === 'pending' && $task['status'] === 'verified'): ?>
                        <form method="POST" action="<?= BASE_URL ?>mystery-shopping/pay-payment">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="fas fa-rupee-sign me-1"></i> Mark Paid
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">No payment assigned for this task.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Update Status -->
        <?php if (!in_array($task['status'], ['verified', 'rejected'])): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent fw-semibold">Update Status</div>
            <div class="card-body">
                <form method="POST" action="<?= BASE_URL ?>mystery-shopping/update-status">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">New Status</label>
                        <select name="status" class="form-select">
                            <?php foreach ($statusLabels as $val => $label): ?>
                                <?php if ($val === $task['status']) continue; ?>
                                <option value="<?= $val ?>"><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Admin Notes</label>
                        <textarea name="admin_notes" class="form-control form-control-sm" rows="2"
                                  placeholder="Optional notes for verification/rejection…"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Update Status</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- Right: Task Details & Report -->
    <div class="col-lg-7">

        <!-- Task Description -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent fw-semibold">Task Description</div>
            <div class="card-body">
                <p class="mb-0"><?= nl2br(htmlspecialchars($task['task_description'])) ?></p>
            </div>
        </div>

        <!-- Checklist -->
        <?php if (!empty($checklist)): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent fw-semibold">Evaluation Checklist</div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach ($checklist as $item): ?>
                    <li class="list-group-item d-flex align-items-center gap-2">
                        <?php if ($item['checked']): ?>
                            <i class="fas fa-check-circle text-success"></i>
                        <?php else: ?>
                            <i class="far fa-circle text-muted"></i>
                        <?php endif; ?>
                        <?= htmlspecialchars($item['item']) ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <!-- Shopper Report -->
        <?php if (!empty($report)): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent fw-semibold">Shopper Report</div>
            <div class="card-body">
                <?php if (!empty($report['admin_notes'])): ?>
                <div class="alert alert-info mb-3">
                    <strong>Admin Notes:</strong> <?= htmlspecialchars($report['admin_notes']) ?>
                </div>
                <?php endif; ?>
                <?php
                // Show any other report fields (from shopper app submission)
                $skipKeys = ['admin_notes'];
                $hasOther = false;
                foreach ($report as $key => $value):
                    if (in_array($key, $skipKeys)) continue;
                    $hasOther = true;
                ?>
                <div class="mb-2">
                    <span class="fw-semibold text-capitalize"><?= htmlspecialchars(str_replace('_', ' ', $key)) ?>:</span>
                    <?php if (is_array($value)): ?>
                        <pre class="mt-1 bg-light p-2 rounded small"><?= htmlspecialchars(json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                    <?php else: ?>
                        <?= nl2br(htmlspecialchars($value)) ?>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php if (!$hasOther && empty($report['admin_notes'])): ?>
                    <p class="text-muted mb-0">No report submitted yet.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center text-muted py-4">
                <i class="fas fa-file-alt fs-2 mb-2 d-block text-muted opacity-50"></i>
                No report submitted yet.
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>
