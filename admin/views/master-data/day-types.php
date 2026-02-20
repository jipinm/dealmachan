<?php /* views/master-data/day-types.php */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>master-data">Master Data</a></li>
                <li class="breadcrumb-item active">Day Types</li>
            </ol>
        </nav>
        <h1 class="h3 mb-0">Day Types</h1>
        <p class="text-muted mb-0 small">Classify days (e.g. Weekday, Weekend, Public Holiday) for coupon scheduling.</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#dayTypeModal" onclick="openAddModal()">
        <i class="fas fa-plus me-2"></i> Add Day Type
    </button>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive p-3">
                    <table id="dayTypesTable" class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>#</th><th>Day Type Name</th><th>Description</th><th class="text-center">Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dayTypes as $i => $dt): ?>
                            <tr>
                                <td class="text-muted small"><?= $i + 1 ?></td>
                                <td class="fw-semibold"><?= escape($dt['day_type_name']) ?></td>
                                <td class="text-muted small"><?= $dt['description'] ? escape($dt['description']) : '<span class="text-muted fst-italic">—</span>' ?></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary me-1" onclick="openEditModal(<?= htmlspecialchars(json_encode($dt), ENT_QUOTES) ?>)"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?= $dt['id'] ?>, '<?= escape($dt['day_type_name']) ?>')"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($dayTypes)): ?><tr><td colspan="4" class="text-center text-muted py-4">No day types found.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Summary</h6>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Total Day Types</span>
                    <strong><?= count($dayTypes) ?></strong>
                </div>
            </div>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Common Day Types</h6>
                <?php $defaults = ['Weekday', 'Weekend', 'Public Holiday', 'Special Day']; ?>
                <?php foreach ($defaults as $d): ?>
                <span class="badge bg-light text-dark border me-1 mb-1"><?= $d ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="dayTypeModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= BASE_URL ?>master-data/day-types">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="_action" value="save">
            <input type="hidden" name="id" id="dtId" value="">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="dtModalTitle">Add Day Type</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Day Type Name <span class="text-danger">*</span></label>
                        <input type="text" name="day_type_name" id="dtName" class="form-control" required placeholder="e.g. Weekday">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" id="dtDesc" class="form-control" rows="3" placeholder="Optional description..."></textarea>
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

<form method="POST" action="<?= BASE_URL ?>master-data/day-types" id="deleteForm">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
    <input type="hidden" name="_action" value="delete">
    <input type="hidden" name="id" id="deleteId" value="">
</form>

<script>
function openAddModal() {
    document.getElementById('dtModalTitle').textContent = 'Add Day Type';
    document.getElementById('dtId').value = '';
    document.getElementById('dtName').value = '';
    document.getElementById('dtDesc').value = '';
}
function openEditModal(dt) {
    document.getElementById('dtModalTitle').textContent = 'Edit Day Type';
    document.getElementById('dtId').value = dt.id;
    document.getElementById('dtName').value = dt.day_type_name;
    document.getElementById('dtDesc').value = dt.description || '';
    new bootstrap.Modal(document.getElementById('dayTypeModal')).show();
}
function confirmDelete(id, name) {
    Swal.fire({ title:'Delete?', html:`Delete day type <b>${name}</b>?`, icon:'warning', showCancelButton:true, confirmButtonColor:'#dc3545', confirmButtonText:'Delete' })
        .then(r => { if (r.isConfirmed) { document.getElementById('deleteId').value = id; document.getElementById('deleteForm').submit(); } });
}
document.addEventListener('DOMContentLoaded', function() {
    $('#dayTypesTable').DataTable({ pageLength: 25, order:[[1,'asc']], columnDefs:[{orderable:false,targets:[3]}] });
});
</script>
