<?php /* views/leads/detail.php */
$statusColors = ['new'=>'danger','contacted'=>'warning','qualified'=>'info','converted'=>'success','rejected'=>'secondary'];
?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>leads">Business Leads</a></li>
        <li class="breadcrumb-item active"><?= escape($lead['contact_name']) ?></li>
    </ol>
</nav>

<?php if ($flash_success): ?>
<div class="alert alert-success alert-dismissible fade show"><?= escape($flash_success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if ($flash_error): ?>
<div class="alert alert-danger alert-dismissible fade show"><?= escape($flash_error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-8">
        <!-- Lead Info -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-semibold"><i class="fas fa-user-tie me-2 text-primary"></i><?= escape($lead['contact_name']) ?></span>
                <span class="badge bg-<?= $statusColors[$lead['status']] ?> fs-6"><?= ucfirst($lead['status']) ?></span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <p class="text-muted small mb-1">Organisation</p>
                        <p class="fw-semibold mb-0"><?= escape($lead['org_name'] ?? '—') ?></p>
                    </div>
                    <div class="col-sm-6">
                        <p class="text-muted small mb-1">Category</p>
                        <p class="fw-semibold mb-0"><?= escape($lead['category'] ?? '—') ?></p>
                    </div>
                    <div class="col-sm-6">
                        <p class="text-muted small mb-1">Phone</p>
                        <p class="fw-semibold mb-0"><?= escape($lead['phone']) ?></p>
                    </div>
                    <div class="col-sm-6">
                        <p class="text-muted small mb-1">Email</p>
                        <p class="fw-semibold mb-0"><?= $lead['email'] ? '<a href="mailto:' . escape($lead['email']) . '">' . escape($lead['email']) . '</a>' : '—' ?></p>
                    </div>
                    <div class="col-sm-6">
                        <p class="text-muted small mb-1">Source</p>
                        <p class="fw-semibold mb-0"><?= escape($lead['source'] ?? 'website') ?></p>
                    </div>
                    <div class="col-sm-6">
                        <p class="text-muted small mb-1">Received</p>
                        <p class="fw-semibold mb-0"><?= formatDateTime($lead['created_at']) ?></p>
                    </div>
                    <?php if ($lead['message']): ?>
                    <div class="col-12">
                        <p class="text-muted small mb-1">Message</p>
                        <div class="p-3 bg-light rounded border"><?= nl2br(escape($lead['message'])) ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($lead['notes']): ?>
                    <div class="col-12">
                        <p class="text-muted small mb-1">Internal Notes</p>
                        <div class="p-3 bg-warning-subtle rounded border border-warning-subtle"><?= nl2br(escape($lead['notes'])) ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Update Status -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold">Update Status</div>
            <div class="card-body">
                <form method="POST" action="<?= BASE_URL ?>leads/update-status">
                    <input type="hidden" name="id" value="<?= $lead['id'] ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            <?php foreach (['new','contacted','qualified','converted','rejected'] as $st): ?>
                            <option value="<?= $st ?>" <?= $lead['status'] === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Note (appended)</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Optional note..."></textarea>
                    </div>
                    <button class="btn btn-primary w-100"><i class="fas fa-save me-2"></i>Save</button>
                </form>
            </div>
        </div>

        <!-- Assign -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Assign To Admin</div>
            <div class="card-body">
                <form method="POST" action="<?= BASE_URL ?>leads/assign">
                    <input type="hidden" name="id" value="<?= $lead['id'] ?>">
                    <div class="mb-3">
                        <select name="admin_id" class="form-select">
                            <option value="">— Unassigned —</option>
                            <?php foreach ($admins as $a): ?>
                            <option value="<?= $a['id'] ?>" <?= $lead['assigned_to_admin_id'] == $a['id'] ? 'selected' : '' ?>>
                                <?= escape($a['name']) ?> (<?= escape($a['admin_type']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="btn btn-outline-primary w-100"><i class="fas fa-user-check me-2"></i>Assign</button>
                </form>
            </div>
        </div>
    </div>
</div>
