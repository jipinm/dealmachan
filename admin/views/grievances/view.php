<?php /* views/grievances/view.php */
$statusColors   = ['open' => 'danger', 'in_progress' => 'warning', 'resolved' => 'success', 'closed' => 'secondary'];
$priorityColors = ['low' => 'info', 'medium' => 'primary', 'high' => 'warning', 'urgent' => 'danger'];
$detailUrl      = 'grievances/detail?id=' . $grievance['id'];
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>grievances">Grievances</a></li>
        <li class="breadcrumb-item active">Grievance #<?= $grievance['id'] ?></li>
    </ol>
</nav>

<?php if (!empty($_SESSION['success'])): ?>
<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3">
    <i class="fas fa-check-circle me-2"></i><?= escape($_SESSION['success']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['success']); endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3">
    <i class="fas fa-exclamation-circle me-2"></i><?= escape($_SESSION['error']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['error']); endif; ?>

<div class="row g-4">

    <!-- LEFT: Grievance Details -->
    <div class="col-lg-8">

        <!-- Header Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex align-items-start gap-3 flex-wrap">
                    <div class="rounded bg-danger d-flex align-items-center justify-content-center text-white fw-bold flex-shrink-0"
                         style="width:56px;height:56px;font-size:1.4rem;">
                        <i class="fas fa-comment-alt"></i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <h2 class="h5 mb-1"><?= escape($grievance['subject']) ?></h2>
                        <div class="d-flex flex-wrap gap-2 mt-1">
                            <span class="badge bg-<?= $statusColors[$grievance['status']] ?? 'secondary' ?>">
                                <?= ucfirst(str_replace('_', ' ', $grievance['status'])) ?>
                            </span>
                            <span class="badge bg-<?= $priorityColors[$grievance['priority']] ?? 'secondary' ?>">
                                <?= ucfirst($grievance['priority']) ?> Priority
                            </span>
                            <span class="badge bg-light text-dark border">
                                Grievance #<?= $grievance['id'] ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Description -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold border-bottom">
                <i class="fas fa-align-left me-2 text-primary"></i> Complaint Description
            </div>
            <div class="card-body">
                <p class="mb-0" style="white-space: pre-wrap;"><?= escape($grievance['description']) ?></p>
            </div>
        </div>

        <!-- Parties Involved -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold border-bottom">
                <i class="fas fa-users me-2 text-info"></i> Parties Involved
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">Customer</div>
                        <div>
                            <a href="<?= BASE_URL ?>customers/profile?id=<?= $grievance['customer_id'] ?>"
                               class="text-decoration-none fw-semibold">
                                <?= escape($grievance['customer_name']) ?>
                            </a>
                        </div>
                        <?php if ($grievance['customer_phone']): ?>
                            <div class="text-muted small"><?= escape($grievance['customer_phone']) ?></div>
                        <?php endif; ?>
                        <?php if ($grievance['customer_email']): ?>
                            <div class="text-muted small"><?= escape($grievance['customer_email']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">Merchant</div>
                        <div>
                            <a href="<?= BASE_URL ?>merchants/profile?id=<?= $grievance['merchant_id'] ?>"
                               class="text-decoration-none fw-semibold">
                                <?= escape($grievance['merchant_name']) ?>
                            </a>
                        </div>
                        <?php if ($grievance['store_name']): ?>
                            <div class="text-muted small">Store: <?= escape($grievance['store_name']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resolution Notes -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold border-bottom d-flex align-items-center justify-content-between">
                <span><i class="fas fa-sticky-note me-2 text-warning"></i> Resolution Notes</span>
                <button class="btn btn-sm btn-outline-secondary" type="button"
                        data-bs-toggle="collapse" data-bs-target="#noteForm">
                    <i class="fas fa-edit me-1"></i> Edit Notes
                </button>
            </div>
            <div class="card-body">
                <?php if ($grievance['resolution_notes']): ?>
                    <p class="mb-0" style="white-space: pre-wrap;"><?= escape($grievance['resolution_notes']) ?></p>
                <?php else: ?>
                    <p class="text-muted mb-0 fst-italic">No resolution notes yet.</p>
                <?php endif; ?>

                <!-- Inline note form -->
                <div class="collapse mt-3" id="noteForm">
                    <form method="POST" action="<?= BASE_URL ?>grievances/add-note">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="id" value="<?= $grievance['id'] ?>">
                        <input type="hidden" name="redirect" value="<?= $detailUrl ?>">
                        <textarea name="resolution_notes" class="form-control form-control-sm mb-2" rows="4"
                                  placeholder="Enter resolution notes…"><?= escape($grievance['resolution_notes'] ?? '') ?></textarea>
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="fas fa-save me-1"></i> Save Notes
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <!-- RIGHT: Quick Actions & Meta -->
    <div class="col-lg-4">

        <!-- Update Status -->
        <?php if (!in_array($grievance['status'], ['closed'])): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold border-bottom">
                <i class="fas fa-exchange-alt me-2 text-primary"></i> Update Status
            </div>
            <div class="card-body">
                <form method="POST" action="<?= BASE_URL ?>grievances/update-status">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="id" value="<?= $grievance['id'] ?>">
                    <input type="hidden" name="redirect" value="<?= $detailUrl ?>">
                    <div class="mb-2">
                        <select name="status" class="form-select form-select-sm">
                            <?php foreach ($statuses as $s): ?>
                                <option value="<?= $s ?>" <?= $grievance['status'] === $s ? 'selected' : '' ?>>
                                    <?= ucfirst(str_replace('_', ' ', $s)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <textarea name="resolution_notes" class="form-control form-control-sm" rows="2"
                                  placeholder="Optional: add/update resolution note…"></textarea>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary w-100">
                        <i class="fas fa-check me-1"></i> Set Status
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Update Priority -->
        <?php if (!in_array($grievance['status'], ['resolved', 'closed'])): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold border-bottom">
                <i class="fas fa-flag me-2 text-warning"></i> Override Priority
            </div>
            <div class="card-body">
                <form method="POST" action="<?= BASE_URL ?>grievances/update-priority">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="id" value="<?= $grievance['id'] ?>">
                    <input type="hidden" name="redirect" value="<?= $detailUrl ?>">
                    <div class="mb-2">
                        <select name="priority" class="form-select form-select-sm">
                            <?php foreach ($priorities as $p): ?>
                                <option value="<?= $p ?>" <?= $grievance['priority'] === $p ? 'selected' : '' ?>>
                                    <?= ucfirst($p) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-sm btn-warning w-100">
                        <i class="fas fa-flag me-1"></i> Set Priority
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Timestamps -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold border-bottom">
                <i class="fas fa-clock me-2 text-secondary"></i> Timeline
            </div>
            <div class="card-body small">
                <div class="mb-2">
                    <span class="text-muted">Filed:</span>
                    <?= formatDateTime($grievance['created_at']) ?>
                </div>
                <?php if ($grievance['resolved_at']): ?>
                <div class="mb-2">
                    <span class="text-muted">Resolved / Closed:</span>
                    <?= formatDateTime($grievance['resolved_at']) ?>
                </div>
                <?php endif; ?>
                <?php if ($grievance['status'] === 'open' || $grievance['status'] === 'in_progress'): ?>
                <?php
                $ageSeconds = time() - strtotime($grievance['created_at']);
                $ageDays    = floor($ageSeconds / 86400);
                $ageClass   = $ageDays >= 7 ? 'text-danger' : ($ageDays >= 3 ? 'text-warning' : 'text-success');
                ?>
                <div>
                    <span class="text-muted">Age:</span>
                    <span class="fw-semibold <?= $ageClass ?>">
                        <?= $ageDays ?>d <?= floor(($ageSeconds % 86400) / 3600) ?>h old
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Danger Zone — Force Close -->
        <?php if (!in_array($grievance['status'], ['closed'])): ?>
        <div class="card border-danger border-opacity-25 shadow-sm mb-4">
            <div class="card-header bg-danger-subtle text-danger fw-semibold border-bottom border-danger border-opacity-25">
                <i class="fas fa-exclamation-triangle me-2"></i> Danger Zone
            </div>
            <div class="card-body">
                <p class="small text-muted mb-2">
                    Force-close this grievance and mark it as closed by admin.
                    Use only when the issue is definitively resolved or invalid.
                </p>
                <form method="POST" action="<?= BASE_URL ?>grievances/force-close" id="forceCloseForm">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="id" value="<?= $grievance['id'] ?>">
                    <input type="hidden" name="redirect" value="grievances">
                    <button type="button" class="btn btn-sm btn-danger w-100"
                            onclick="confirmForceClose(<?= $grievance['id'] ?>)">
                        <i class="fas fa-times-circle me-1"></i> Force Close
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<script>
function confirmForceClose(id) {
    Swal.fire({
        title: 'Force Close Grievance?',
        html: 'This will mark the grievance as <b>Closed</b> and record it as admin-closed.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Yes, Force Close'
    }).then(r => {
        if (r.isConfirmed) {
            document.getElementById('forceCloseForm').submit();
        }
    });
}
</script>
