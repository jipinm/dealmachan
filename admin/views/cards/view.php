<?php
$statusBadge = [
    'available' => 'success',
    'assigned'  => 'info',
    'activated' => 'warning',
    'blocked'   => 'danger',
];
$st = $card['status'] ?? 'available';
?>
<!-- Header -->
<div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="mb-0 font-monospace"><?= escape($card['card_number']) ?></h4>
        <small class="text-muted"><a href="<?= BASE_URL ?>/cards">Cards</a> / Detail</small>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <?php if ($st === 'assigned'): ?>
        <form method="POST" action="<?= BASE_URL ?>/cards/activate" class="d-inline">
            <input type="hidden" name="csrf_token"   value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="id"           value="<?= $card['id'] ?>">
            <input type="hidden" name="redirect"     value="cards/detail?id=<?= $card['id'] ?>">
            <button class="btn btn-sm btn-warning"><i class="fas fa-bolt me-1"></i> Activate</button>
        </form>
        <?php endif; ?>
        <?php if ($st === 'available'): ?>
        <a href="<?= BASE_URL ?>/cards/assign" class="btn btn-sm btn-success">
            <i class="fas fa-user-tag me-1"></i> Assign Card
        </a>
        <?php endif; ?>
        <form method="POST" action="<?= BASE_URL ?>/cards/block" class="d-inline">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="id"         value="<?= $card['id'] ?>">
            <input type="hidden" name="redirect"   value="cards/detail?id=<?= $card['id'] ?>">
            <button class="btn btn-sm <?= $st === 'blocked' ? 'btn-outline-success' : 'btn-outline-danger' ?>">
                <i class="fas fa-<?= $st === 'blocked' ? 'lock-open' : 'ban' ?> me-1"></i>
                <?= $st === 'blocked' ? 'Unblock' : 'Block' ?>
            </button>
        </form>
        <a href="<?= BASE_URL ?>/cards" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back</a>
    </div>
</div>

<div class="row g-4">
    <!-- Card Info -->
    <div class="col-lg-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span class="fw-semibold"><i class="fas fa-credit-card me-2 text-primary"></i>Card Details</span>
                <div class="d-flex gap-2">
                    <span class="badge bg-<?= $statusBadge[$st] ?? 'secondary' ?>"><?= ucfirst($st) ?></span>
                    <span class="badge bg-secondary"><?= ucfirst($card['card_variant']) ?></span>
                    <?php if ($card['is_preprinted']): ?>
                        <span class="badge bg-info text-dark">Preprinted</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="text-muted small">Card Number</label>
                        <div class="d-flex align-items-center gap-2 mt-1">
                            <code class="fs-5 fw-bold border rounded px-3 py-1 bg-light"><?= escape($card['card_number']) ?></code>
                            <button class="btn btn-sm btn-outline-secondary" onclick="copyText('<?= escape($card['card_number']) ?>')" title="Copy">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <label class="text-muted small">Variant</label>
                        <div class="mt-1"><span class="badge bg-secondary fs-6"><?= ucfirst($card['card_variant']) ?></span></div>
                    </div>
                    <div class="col-sm-6">
                        <label class="text-muted small">Generated At</label>
                        <div><?= formatDateTime($card['generated_at']) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <label class="text-muted small">Activated At</label>
                        <div><?= $card['activated_at'] ? formatDateTime($card['activated_at']) : '<span class="text-muted">—</span>' ?></div>
                    </div>
                    <?php if (!empty($card['parameters_json'])): ?>
                    <div class="col-12">
                        <label class="text-muted small">Parameters (JSON)</label>
                        <pre class="bg-light rounded p-2 small mt-1 mb-0"><?= escape(json_encode(json_decode($card['parameters_json']), JSON_PRETTY_PRINT)) ?></pre>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Assignment -->
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-semibold"><i class="fas fa-user-tag me-2 text-info"></i>Assignment</div>
            <div class="card-body">
                <?php if ($card['customer_name']): ?>
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="fw-semibold"><i class="fas fa-user me-2 text-muted"></i><?= escape($card['customer_name']) ?></div>
                        <?php if ($card['customer_phone']): ?>
                            <div class="small text-muted"><?= escape($card['customer_phone']) ?></div>
                        <?php endif; ?>
                        <?php if ($card['customer_email']): ?>
                            <div class="small text-muted"><?= escape($card['customer_email']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex gap-2">
                        <form method="POST" action="<?= BASE_URL ?>/cards/unassign">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            <input type="hidden" name="id" value="<?= $card['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Unassign this card?')">
                                <i class="fas fa-unlink me-1"></i> Unassign
                            </button>
                        </form>
                    </div>
                </div>
                <?php elseif ($card['merchant_name']): ?>
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="fw-semibold"><i class="fas fa-store me-2 text-muted"></i><?= escape($card['merchant_name']) ?></div>
                        <div class="small text-muted">Merchant</div>
                    </div>
                    <form method="POST" action="<?= BASE_URL ?>/cards/unassign">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="id" value="<?= $card['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Unassign this card?')">
                            <i class="fas fa-unlink me-1"></i> Unassign
                        </button>
                    </form>
                </div>
                <?php elseif ($card['admin_name']): ?>
                <div><i class="fas fa-user-shield me-2 text-muted"></i><?= escape($card['admin_name']) ?> <span class="text-muted small">(Admin)</span></div>
                <?php else: ?>
                <div class="text-muted">Not assigned to anyone.</div>
                <?php if ($st === 'available'): ?>
                <a href="<?= BASE_URL ?>/cards/assign" class="btn btn-sm btn-outline-success mt-2">
                    <i class="fas fa-user-tag me-1"></i> Assign Now
                </a>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- RIGHT -->
    <div class="col-lg-4">
        <!-- Quick Stats -->
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-semibold"><i class="fas fa-info-circle me-2 text-secondary"></i>Card Info</div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Status</span>
                        <span class="badge bg-<?= $statusBadge[$st] ?? 'secondary' ?>"><?= ucfirst($st) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Variant</span>
                        <strong><?= ucfirst($card['card_variant']) ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Preprinted</span>
                        <strong><?= $card['is_preprinted'] ? 'Yes' : 'No' ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Created</span>
                        <small><?= formatDate($card['created_at']) ?></small>
                    </li>
                    <?php if ($card['updated_at']): ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Updated</span>
                        <small><?= formatDate($card['updated_at']) ?></small>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <!-- Danger Zone -->
        <?php if ($st === 'available'): ?>
        <div class="card border-danger shadow-sm">
            <div class="card-header text-danger fw-semibold"><i class="fas fa-exclamation-triangle me-2"></i>Danger Zone</div>
            <div class="card-body">
                <p class="small text-muted mb-2">Permanently delete this card. Only available cards can be deleted.</p>
                <button class="btn btn-sm btn-danger w-100"
                        onclick="confirmDelete(<?= $card['id'] ?>, '<?= escape($card['card_number']) ?>')">
                    <i class="fas fa-trash me-1"></i> Delete Card
                </button>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Delete form -->
<form id="deleteForm" method="POST" action="<?= BASE_URL ?>/cards/delete" style="display:none">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
    <input type="hidden" name="id" value="<?= $card['id'] ?>">
</form>

<script>
function confirmDelete(id, number) {
    Swal.fire({
        title: 'Delete Card?',
        html: `Permanently delete card <strong>${number}</strong>?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Yes, delete it',
    }).then(r => { if (r.isConfirmed) document.getElementById('deleteForm').submit(); });
}
function copyText(text) {
    navigator.clipboard.writeText(text).then(() =>
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Copied!', showConfirmButton: false, timer: 1500 })
    );
}
</script>
