<!-- Stats Row -->
<div class="row g-3 mb-4">
    <?php
    $statCards = [
        ['label' => 'Total Cards',       'key' => 'total',     'color' => 'primary'],
        ['label' => 'Available',          'key' => 'available', 'color' => 'success'],
        ['label' => 'Assigned',           'key' => 'assigned',  'color' => 'info'],
        ['label' => 'Activated',          'key' => 'activated', 'color' => 'warning'],
        ['label' => 'Blocked',            'key' => 'blocked',   'color' => 'danger'],
        ['label' => 'Generated Today',    'key' => 'today',     'color' => 'dark'],
    ];
    foreach ($statCards as $sc):
    ?>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card text-bg-<?= $sc['color'] ?> h-100 shadow-sm">
            <div class="card-body py-3 px-3">
                <div class="small fw-semibold text-white-50"><?= $sc['label'] ?></div>
                <div class="fs-4 fw-bold"><?= number_format($stats[$sc['key']] ?? 0) ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Page Header -->
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="mb-0">Card Management</h4>
        <small class="text-muted">
            <?php
            $from = min($totalCount, ($currentPage - 1) * $perPage + 1);
            $to   = min($totalCount, $currentPage * $perPage);
            echo $totalCount ? "Showing {$from}&ndash;{$to} of {$totalCount}" : 'No cards found';
            ?>
        </small>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/cards/generate" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Generate Cards
        </a>
        <a href="<?= BASE_URL ?>/cards/assign" class="btn btn-outline-success">
            <i class="fas fa-user-tag me-1"></i> Assign Card
        </a>
    </div>
</div>

<!-- Filter Bar -->
<div class="card mb-3 shadow-sm">
    <div class="card-body py-2 px-3">
        <form method="GET" action="<?= BASE_URL ?>/cards" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label form-label-sm mb-1">Search</label>
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Card number, customer, merchant…" value="<?= escape($filters['search']) ?>">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label form-label-sm mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <?php foreach (['available','assigned','activated','blocked'] as $s): ?>
                        <option value="<?= $s ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label form-label-sm mb-1">Variant</label>
                <select name="card_variant" class="form-select form-select-sm">
                    <option value="">All</option>
                    <?php foreach ($variants as $v): ?>
                        <option value="<?= $v ?>" <?= $filters['card_variant'] === $v ? 'selected' : '' ?>><?= ucfirst($v) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label form-label-sm mb-1">Assigned To</label>
                <select name="assigned_to" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="unassigned" <?= $filters['assigned_to'] === 'unassigned' ? 'selected' : '' ?>>Unassigned</option>
                    <option value="customer"   <?= $filters['assigned_to'] === 'customer'   ? 'selected' : '' ?>>Customer</option>
                    <option value="merchant"   <?= $filters['assigned_to'] === 'merchant'   ? 'selected' : '' ?>>Merchant</option>
                </select>
            </div>
            <div class="col-6 col-md-1">
                <label class="form-label form-label-sm mb-1">Preprinted</label>
                <select name="is_preprinted" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="1" <?= $filters['is_preprinted'] === '1' ? 'selected' : '' ?>>Yes</option>
                    <option value="0" <?= $filters['is_preprinted'] === '0' ? 'selected' : '' ?>>No</option>
                </select>
            </div>
            <div class="col-12 col-md-auto ms-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i>Filter</button>
                <a href="<?= BASE_URL ?>/cards" class="btn btn-sm btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<?php if (empty($cards)): ?>
    <div class="alert alert-info">No cards match your filters.</div>
<?php else: ?>
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Card Number</th>
                    <th>Variant</th>
                    <th>Assigned To</th>
                    <th>Status</th>
                    <th>Preprinted</th>
                    <th>Generated</th>
                    <th>Activated</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($cards as $i => $c): ?>
                <?php
                $statusBadge = [
                    'available' => 'success',
                    'assigned'  => 'info',
                    'activated' => 'warning',
                    'blocked'   => 'danger',
                ];
                ?>
                <tr>
                    <td class="text-muted small"><?= ($currentPage - 1) * $perPage + $i + 1 ?></td>
                    <td>
                        <a href="<?= BASE_URL ?>/cards/detail?id=<?= $c['id'] ?>"
                           class="fw-semibold text-decoration-none font-monospace">
                            <?= escape($c['card_number']) ?>
                        </a>
                    </td>
                    <td><span class="badge bg-secondary"><?= ucfirst($c['card_variant']) ?></span></td>
                    <td class="small">
                        <?php if ($c['customer_name']): ?>
                            <i class="fas fa-user text-muted me-1"></i><?= escape($c['customer_name']) ?>
                            <?php if ($c['customer_phone']): ?>
                                <div class="text-muted"><?= escape($c['customer_phone']) ?></div>
                            <?php endif; ?>
                        <?php elseif ($c['merchant_name']): ?>
                            <i class="fas fa-store text-muted me-1"></i><?= escape($c['merchant_name']) ?>
                        <?php elseif ($c['admin_name']): ?>
                            <i class="fas fa-user-shield text-muted me-1"></i><?= escape($c['admin_name']) ?>
                        <?php else: ?>
                            <span class="text-muted">&mdash;</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge bg-<?= $statusBadge[$c['status']] ?? 'secondary' ?>">
                            <?= ucfirst($c['status']) ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <?php if ($c['is_preprinted']): ?>
                            <i class="fas fa-check-circle text-success"></i>
                        <?php else: ?>
                            <i class="fas fa-times-circle text-muted"></i>
                        <?php endif; ?>
                    </td>
                    <td class="small text-muted"><?= formatDate($c['generated_at']) ?></td>
                    <td class="small text-muted"><?= $c['activated_at'] ? formatDate($c['activated_at']) : '&mdash;' ?></td>
                    <td class="text-end" style="white-space:nowrap">
                        <?php if ($c['status'] === 'assigned'): ?>
                        <form method="POST" action="<?= BASE_URL ?>/cards/activate" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                            <input type="hidden" name="redirect" value="cards">
                            <button class="btn btn-sm btn-outline-warning" title="Activate"><i class="fas fa-bolt"></i></button>
                        </form>
                        <?php endif; ?>
                        <form method="POST" action="<?= BASE_URL ?>/cards/block" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                            <input type="hidden" name="redirect" value="cards">
                            <button class="btn btn-sm <?= $c['status'] === 'blocked' ? 'btn-outline-success' : 'btn-outline-danger' ?>"
                                    title="<?= $c['status'] === 'blocked' ? 'Unblock' : 'Block' ?>">
                                <i class="fas fa-<?= $c['status'] === 'blocked' ? 'lock-open' : 'ban' ?>"></i>
                            </button>
                        </form>
                        <a href="<?= BASE_URL ?>/cards/detail?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-info" title="View">
                            <i class="fas fa-eye"></i>
                        </a>
                        <?php if ($c['status'] === 'available'): ?>
                        <button class="btn btn-sm btn-outline-danger" title="Delete"
                                onclick="confirmDelete(<?= $c['id'] ?>, '<?= escape($c['card_number']) ?>')">
                            <i class="fas fa-trash"></i>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div class="d-flex justify-content-center mt-3">
    <nav>
        <ul class="pagination pagination-sm">
            <?php
            $q = $filters;
            $buildUrl = function($page) use ($q) {
                $q['page'] = $page;
                return BASE_URL . '/cards?' . http_build_query(array_filter($q, fn($v) => $v !== ''));
            };
            ?>
            <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $buildUrl($currentPage - 1) ?>">‹</a>
            </li>
            <?php
            $start = max(1, $currentPage - 2);
            $end   = min($totalPages, $currentPage + 2);
            if ($start > 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif;
            for ($p = $start; $p <= $end; $p++): ?>
                <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
                    <a class="page-link" href="<?= $buildUrl($p) ?>"><?= $p ?></a>
                </li>
            <?php endfor;
            if ($end < $totalPages): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif;
            ?>
            <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $buildUrl($currentPage + 1) ?>">›</a>
            </li>
        </ul>
    </nav>
</div>
<?php endif; ?>
<?php endif; ?>

<!-- Delete form -->
<form id="deleteForm" method="POST" action="<?= BASE_URL ?>/cards/delete" style="display:none">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
    <input type="hidden" name="id" id="deleteId">
</form>

<script>
function confirmDelete(id, number) {
    Swal.fire({
        title: 'Delete Card?',
        html: `Delete card <strong>${number}</strong>? This cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Yes, delete it',
    }).then(r => {
        if (r.isConfirmed) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteForm').submit();
        }
    });
}
</script>
