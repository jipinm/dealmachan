<?php /* views/admin-management/index.php */
$typeColors = [
    'super_admin'    => 'danger',
    'city_admin'     => 'primary',
    'sales_admin'    => 'success',
    'promoter_admin' => 'warning',
    'partner_admin'  => 'info',
    'club_admin'     => 'secondary',
];
$typeIcons = [
    'super_admin'    => 'fa-crown',
    'city_admin'     => 'fa-city',
    'sales_admin'    => 'fa-handshake',
    'promoter_admin' => 'fa-bullhorn',
    'partner_admin'  => 'fa-building',
    'club_admin'     => 'fa-users',
];
?>

<?php if ($flash_success): ?><div class="alert alert-success alert-dismissible fade show border-0 shadow-sm"><i class="fas fa-check-circle me-2"></i><?= escape($flash_success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<?php if ($flash_error):   ?><div class="alert alert-danger  alert-dismissible fade show border-0 shadow-sm"><i class="fas fa-exclamation-circle me-2"></i><?= escape($flash_error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Admin Management</h1>
        <p class="text-muted mb-0 small">Manage admin accounts and their access levels.</p>
    </div>
    <a href="<?= BASE_URL ?>admin-management/add" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i> Add Admin
    </a>
</div>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3 col-lg-2">
        <div class="card border-0 shadow-sm text-center h-100">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-dark"><?= $totalAdmins ?></div>
                <div class="text-muted small">Total</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-lg-2">
        <div class="card border-0 shadow-sm text-center h-100">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-success"><?= $totalActive ?></div>
                <div class="text-muted small">Active</div>
            </div>
        </div>
    </div>
    <?php foreach ($adminTypes as $typeKey => $typeLabel): ?>
    <div class="col-6 col-md-3 col-lg-2">
        <div class="card border-0 shadow-sm text-center h-100" style="border-top: 3px solid var(--bs-<?= $typeColors[$typeKey] ?>) !important;">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-<?= $typeColors[$typeKey] ?>"><?= $statsMap[$typeKey]['count'] ?? 0 ?></div>
                <div class="text-muted small"><?= $typeLabel ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Filter Tabs -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom">
        <ul class="nav nav-tabs card-header-tabs">
            <li class="nav-item"><a class="nav-link <?= !$filterType ? 'active' : '' ?>" href="<?= BASE_URL ?>admin-management">All (<?= $totalAdmins ?>)</a></li>
            <?php foreach ($adminTypes as $typeKey => $typeLabel): ?>
            <li class="nav-item">
                <a class="nav-link <?= $filterType === $typeKey ? 'active' : '' ?>" href="<?= BASE_URL ?>admin-management?type=<?= $typeKey ?>">
                    <?= $typeLabel ?> <span class="badge bg-<?= $typeColors[$typeKey] ?> ms-1"><?= $statsMap[$typeKey]['count'] ?? 0 ?></span>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive p-3">
            <table id="adminsTable" class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Name / Email</th>
                        <th>Type</th>
                        <th>City</th>
                        <th class="text-center">Status</th>
                        <th>Last Login</th>
                        <th>Created</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($admins as $i => $a): ?>
                    <tr>
                        <td class="text-muted small"><?= $i + 1 ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle bg-<?= $typeColors[$a['admin_type']] ?> text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width:36px;height:36px;font-size:.8rem;">
                                    <i class="fas <?= $typeIcons[$a['admin_type']] ?>"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold"><?= escape($a['name'] ?: 'Administrator') ?></div>
                                    <div class="text-muted small"><?= escape($a['email']) ?></div>
                                    <?php if ($a['phone']): ?><div class="text-muted small"><?= escape($a['phone']) ?></div><?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge bg-<?= $typeColors[$a['admin_type']] ?> rounded-pill"><?= $adminTypes[$a['admin_type']] ?></span></td>
                        <td><?= $a['city_name'] ? escape($a['city_name']) : '<span class="text-muted">—</span>' ?></td>
                        <td class="text-center">
                            <form method="POST" action="<?= BASE_URL ?>admin-management/toggle" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                <input type="hidden" name="id" value="<?= $a['id'] ?>">
                                <button type="submit" class="badge border-0 bg-<?= $a['status'] === 'active' ? 'success' : 'danger' ?> p-2" style="cursor:pointer;" title="Click to toggle">
                                    <?= ucfirst($a['status'] ?? 'inactive') ?>
                                </button>
                            </form>
                        </td>
                        <td class="text-muted small"><?= $a['last_login'] ? formatDateTime($a['last_login']) : '<span class="fst-italic">Never</span>' ?></td>
                        <td class="text-muted small"><?= formatDate($a['created_at']) ?></td>
                        <td class="text-center text-nowrap">
                            <a href="<?= BASE_URL ?>admin-management/edit?id=<?= $a['id'] ?>" class="btn btn-sm btn-outline-primary me-1" title="Edit"><i class="fas fa-edit"></i></a>
                            <button class="btn btn-sm btn-outline-warning me-1" onclick="openResetModal(<?= $a['id'] ?>, '<?= escape($a['name'] ?: $a['email']) ?>')" title="Reset Password"><i class="fas fa-key"></i></button>
                            <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?= $a['id'] ?>, '<?= escape($a['name'] ?: $a['email']) ?>')" title="Delete"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Delete Form -->
<form method="POST" action="<?= BASE_URL ?>admin-management/delete" id="deleteForm">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
    <input type="hidden" name="id" id="deleteId">
</form>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form method="POST" action="<?= BASE_URL ?>admin-management/reset-password">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="id" id="resetAdminId">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="fas fa-key me-2"></i>Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-3">Resetting password for: <strong id="resetAdminName"></strong></p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">New Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" name="new_password" id="newPassword" class="form-control" required minlength="8" placeholder="Min 8 characters">
                            <button type="button" class="btn btn-outline-secondary" onclick="togglePw('newPassword')"><i class="fas fa-eye"></i></button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save me-1"></i> Reset</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function confirmDelete(id, name) {
    Swal.fire({ title:'Delete Admin?', html:`Delete <b>${name}</b>? This will permanently remove their account.`, icon:'warning', showCancelButton:true, confirmButtonColor:'#dc3545', confirmButtonText:'Delete' })
        .then(r => { if (r.isConfirmed) { document.getElementById('deleteId').value = id; document.getElementById('deleteForm').submit(); } });
}
function openResetModal(id, name) {
    document.getElementById('resetAdminId').value = id;
    document.getElementById('resetAdminName').textContent = name;
    document.getElementById('newPassword').value = '';
    new bootstrap.Modal(document.getElementById('resetPasswordModal')).show();
}
function togglePw(id) {
    const f = document.getElementById(id);
    f.type = f.type === 'password' ? 'text' : 'password';
}
document.addEventListener('DOMContentLoaded', function() {
    $('#adminsTable').DataTable({
        pageLength: 25,
        order: [[6, 'desc']],
        columnDefs: [{ orderable: false, targets: [4, 7] }],
        language: { emptyTable: 'No admins found.' }
    });
});
</script>
