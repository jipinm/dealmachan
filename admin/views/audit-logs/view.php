<?php /* views/audit-logs/view.php */
$typeColors = ['admin' => 'danger', 'merchant' => 'warning', 'customer' => 'info'];
$color = $typeColors[$log['user_type'] ?? ''] ?? 'secondary';
?>

<div class="d-flex align-items-center mb-4">
    <a href="<?= BASE_URL ?>audit-logs" class="btn btn-sm btn-outline-secondary me-3">
        <i class="fas fa-arrow-left me-1"></i> Back
    </a>
    <div>
        <h4 class="mb-0">Audit Log Entry #<?= $log['id'] ?></h4>
        <small class="text-muted"><?= date('d M Y H:i:s', strtotime($log['created_at'])) ?></small>
    </div>
    <span class="badge bg-<?= $color ?> ms-3 fs-6"><?= ucfirst($log['user_type'] ?? '?') ?></span>
</div>

<div class="row g-3">
    <!-- Summary Card -->
    <div class="col-12 col-lg-5 col-xl-4">
        <div class="card shadow-sm">
            <div class="card-header fw-semibold">Event Details</div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">Actor</dt>
                    <dd class="col-7"><?= escape($log['actor_name'] ?? '—') ?></dd>

                    <dt class="col-5 text-muted">User Type</dt>
                    <dd class="col-7"><span class="badge bg-<?= $color ?>"><?= ucfirst($log['user_type'] ?? '?') ?></span></dd>

                    <dt class="col-5 text-muted">User ID</dt>
                    <dd class="col-7"><?= $log['user_id'] ?? '—' ?></dd>

                    <dt class="col-5 text-muted">Action</dt>
                    <dd class="col-7"><code><?= escape($log['action']) ?></code></dd>

                    <dt class="col-5 text-muted">Table</dt>
                    <dd class="col-7"><code><?= escape($log['table_name'] ?? '—') ?></code></dd>

                    <dt class="col-5 text-muted">Record ID</dt>
                    <dd class="col-7"><?= $log['record_id'] ?? '—' ?></dd>

                    <dt class="col-5 text-muted">IP Address</dt>
                    <dd class="col-7 font-monospace"><?= escape($log['ip_address'] ?? '—') ?></dd>

                    <dt class="col-5 text-muted">Timestamp</dt>
                    <dd class="col-7"><?= date('d M Y H:i:s', strtotime($log['created_at'])) ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <!-- JSON Diff -->
    <div class="col-12 col-lg-7 col-xl-8">
        <div class="row g-3">
            <!-- Old Values -->
            <div class="col-12 col-md-6">
                <div class="card shadow-sm h-100 border-danger border-opacity-25">
                    <div class="card-header fw-semibold text-danger bg-danger bg-opacity-10">
                        <i class="fas fa-minus-circle me-1"></i> Old Values
                    </div>
                    <div class="card-body p-0">
                        <?php if (!empty($log['old_values']) && $log['old_values'] !== 'null'): ?>
                        <?php
                        $old = $log['old_values'];
                        $decoded = is_string($old) ? json_decode($old, true) : $old;
                        $pretty = $decoded !== null ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $old;
                        ?>
                        <pre class="m-0 p-3 small" style="overflow:auto;max-height:400px;background:transparent;"><?= escape($pretty) ?></pre>
                        <?php else: ?>
                        <div class="p-3 text-muted small"><em>No old values recorded.</em></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- New Values -->
            <div class="col-12 col-md-6">
                <div class="card shadow-sm h-100 border-success border-opacity-25">
                    <div class="card-header fw-semibold text-success bg-success bg-opacity-10">
                        <i class="fas fa-plus-circle me-1"></i> New Values
                    </div>
                    <div class="card-body p-0">
                        <?php if (!empty($log['new_values']) && $log['new_values'] !== 'null'): ?>
                        <?php
                        $new = $log['new_values'];
                        $decoded = is_string($new) ? json_decode($new, true) : $new;
                        $pretty = $decoded !== null ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $new;
                        ?>
                        <pre class="m-0 p-3 small" style="overflow:auto;max-height:400px;background:transparent;"><?= escape($pretty) ?></pre>
                        <?php else: ?>
                        <div class="p-3 text-muted small"><em>No new values recorded.</em></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
