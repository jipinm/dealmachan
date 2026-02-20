<?php /* views/master-data/professions.php */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>master-data">Master Data</a></li>
                <li class="breadcrumb-item active">Professions</li>
            </ol>
        </nav>
        <h1 class="h3 mb-0">Professions</h1>
        <p class="text-muted mb-0 small">Profession options shown during customer registration.</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#professionModal" onclick="openAddModal()">
        <i class="fas fa-plus me-2"></i> Add Profession
    </button>
</div>

<div class="row g-4">
    <!-- Table -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive p-3">
                    <table id="professionsTable" class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>#</th><th>Profession Name</th><th class="text-center">Customers</th><th class="text-center">Status</th><th class="text-center">Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($professions as $i => $prof): ?>
                            <tr>
                                <td class="text-muted small"><?= $i + 1 ?></td>
                                <td class="fw-semibold"><?= escape($prof['profession_name']) ?></td>
                                <td class="text-center"><span class="badge bg-info rounded-pill"><?= $prof['customer_count'] ?? 0 ?></span></td>
                                <td class="text-center">
                                    <form method="POST" action="<?= BASE_URL ?>master-data/professions" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                        <input type="hidden" name="_action" value="toggle">
                                        <input type="hidden" name="id" value="<?= $prof['id'] ?>">
                                        <button type="submit" class="badge border-0 <?= $prof['status'] === 'active' ? 'bg-success' : 'bg-danger' ?> p-2" style="cursor:pointer;">
                                            <?= $prof['status'] === 'active' ? 'Active' : 'Inactive' ?>
                                        </button>
                                    </form>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary me-1" onclick="openEditModal(<?= htmlspecialchars(json_encode($prof), ENT_QUOTES) ?>)"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?= $prof['id'] ?>, '<?= escape($prof['profession_name']) ?>', <?= $prof['customer_count'] ?? 0 ?>)"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($professions)): ?><tr><td colspan="5" class="text-center text-muted py-4">No professions found.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!-- Summary -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Summary</h6>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Total</span>
                    <strong><?= count($professions) ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Active</span>
                    <strong class="text-success"><?= count(array_filter($professions, fn($p) => $p['status'] === 'active')) ?></strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">With Customers</span>
                    <strong class="text-info"><?= count(array_filter($professions, fn($p) => ($p['customer_count'] ?? 0) > 0)) ?></strong>
                </div>
            </div>
        </div>
        <div class="alert alert-info border-0 small">
            <i class="fas fa-info-circle me-1"></i>
            Professions with assigned customers cannot be deleted.
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="professionModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form method="POST" action="<?= BASE_URL ?>master-data/professions">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="_action" value="save">
            <input type="hidden" name="id" id="profId" value="">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="profModalTitle">Add Profession</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Profession Name <span class="text-danger">*</span></label>
                        <input type="text" name="profession_name" id="profName" class="form-control" required placeholder="e.g. Doctor">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" id="profStatus" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button>
                </div>
            </div>
        </form>
    </div>
</div>

<form method="POST" action="<?= BASE_URL ?>master-data/professions" id="deleteForm">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
    <input type="hidden" name="_action" value="delete">
    <input type="hidden" name="id" id="deleteId" value="">
</form>

<script>
function openAddModal() {
    document.getElementById('profModalTitle').textContent = 'Add Profession';
    document.getElementById('profId').value = '';
    document.getElementById('profName').value = '';
    document.getElementById('profStatus').value = 'active';
}
function openEditModal(p) {
    document.getElementById('profModalTitle').textContent = 'Edit Profession';
    document.getElementById('profId').value = p.id;
    document.getElementById('profName').value = p.profession_name;
    document.getElementById('profStatus').value = p.status;
    new bootstrap.Modal(document.getElementById('professionModal')).show();
}
function confirmDelete(id, name, cnt) {
    if (cnt > 0) { Swal.fire({ icon:'warning', title:'Cannot Delete', text:`"${name}" is linked to ${cnt} customer(s).`, confirmButtonColor:'#667eea' }); return; }
    Swal.fire({ title:'Delete?', html:`Delete profession <b>${name}</b>?`, icon:'warning', showCancelButton:true, confirmButtonColor:'#dc3545', confirmButtonText:'Delete' })
        .then(r => { if (r.isConfirmed) { document.getElementById('deleteId').value = id; document.getElementById('deleteForm').submit(); } });
}
document.addEventListener('DOMContentLoaded', function() {
    $('#professionsTable').DataTable({ pageLength: 25, order:[[1,'asc']], columnDefs:[{orderable:false,targets:[2,3,4]}] });
});
</script>
