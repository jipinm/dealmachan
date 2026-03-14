<?php /* views/master-data/labels.php */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>master-data">Master Data</a></li>
                <li class="breadcrumb-item active">Labels</li>
            </ol>
        </nav>
        <h1 class="h3 mb-0">Labels</h1>
        <p class="text-muted mb-0 small">Labels authenticate merchant credibility and control listing priority.</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#labelModal" onclick="openAddModal()">
        <i class="fas fa-plus me-2"></i> Add Label
    </button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive p-3">
            <table id="labelsTable" class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th>#</th><th>Label</th><th>Icon</th><th>Description</th><th class="text-center">Priority</th><th class="text-center">Merchants</th><th class="text-center">Status</th><th class="text-center">Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($labels as $i => $label): ?>
                    <tr>
                        <td class="text-muted small"><?= $i + 1 ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <?php if ($label['label_icon']): ?>
                                <i class="<?= escape($label['label_icon']) ?> text-warning"></i>
                                <?php endif; ?>
                                <span class="fw-semibold"><?= escape($label['label_name']) ?></span>
                            </div>
                        </td>
                        <td><code class="small"><?= escape($label['label_icon'] ?? '&mdash;') ?></code></td>
                        <td class="text-muted small" style="max-width:200px;"><?= escape($label['description'] ?? '&mdash;') ?></td>
                        <td class="text-center"><span class="badge bg-primary"><?= $label['priority_weight'] ?></span></td>
                        <td class="text-center"><span class="badge bg-success rounded-pill"><?= $label['merchant_count'] ?? 0 ?></span></td>
                        <td class="text-center">
                            <form method="POST" action="<?= BASE_URL ?>master-data/labels" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                <input type="hidden" name="_action" value="toggle">
                                <input type="hidden" name="id" value="<?= $label['id'] ?>">
                                <button type="submit" class="badge border-0 <?= $label['status'] === 'active' ? 'bg-success' : 'bg-danger' ?> p-2" style="cursor:pointer;">
                                    <?= $label['status'] === 'active' ? 'Active' : 'Inactive' ?>
                                </button>
                            </form>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary me-1" onclick="openEditModal(<?= htmlspecialchars(json_encode($label), ENT_QUOTES) ?>)"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?= $label['id'] ?>, '<?= escape($label['label_name']) ?>', <?= $label['merchant_count'] ?? 0 ?>)"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($labels)): ?><tr><td colspan="8" class="text-center text-muted py-4">No labels found.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="labelModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= BASE_URL ?>master-data/labels">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="_action" value="save">
            <input type="hidden" name="id" id="labelId" value="">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="labelModalTitle">Add Label</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Label Name <span class="text-danger">*</span></label>
                        <input type="text" name="label_name" id="lblName" class="form-control" required placeholder="e.g. Premium Partner">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Icon Class <small class="text-muted fw-normal">(FontAwesome)</small></label>
                        <div class="input-group">
                            <input type="text" name="label_icon" id="lblIcon" class="form-control" placeholder="fas fa-star" oninput="previewIcon(this.value)">
                            <span class="input-group-text" id="iconPreview"><i class="fas fa-star"></i></span>
                        </div>
                        <small class="text-muted">e.g. <code>fas fa-star</code>, <code>fas fa-crown</code>, <code>fas fa-shield-alt</code></small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" id="lblDesc" class="form-control" rows="2" placeholder="Brief description of this label..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Priority Weight</label>
                        <input type="number" name="priority_weight" id="lblPriority" class="form-control" value="0" min="0" max="100">
                        <small class="text-muted">Higher value = listed first. Range: 0&ndash;100</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" id="lblStatus" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning text-dark"><i class="fas fa-save me-1"></i> Save Label</button>
                </div>
            </div>
        </form>
    </div>
</div>

<form method="POST" action="<?= BASE_URL ?>master-data/labels" id="deleteForm">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
    <input type="hidden" name="_action" value="delete">
    <input type="hidden" name="id" id="deleteId" value="">
</form>

<script>
function previewIcon(cls) {
    const el = document.querySelector('#iconPreview i');
    el.className = cls || 'fas fa-star';
}
function openAddModal() {
    document.getElementById('labelModalTitle').textContent = 'Add Label';
    ['labelId','lblName','lblIcon','lblDesc'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('lblPriority').value = 0;
    document.getElementById('lblStatus').value = 'active';
    previewIcon('fas fa-star');
}
function openEditModal(lbl) {
    document.getElementById('labelModalTitle').textContent = 'Edit Label';
    document.getElementById('labelId').value = lbl.id;
    document.getElementById('lblName').value = lbl.label_name;
    document.getElementById('lblIcon').value = lbl.label_icon || '';
    document.getElementById('lblDesc').value = lbl.description || '';
    document.getElementById('lblPriority').value = lbl.priority_weight || 0;
    document.getElementById('lblStatus').value = lbl.status;
    previewIcon(lbl.label_icon || 'fas fa-star');
    new bootstrap.Modal(document.getElementById('labelModal')).show();
}
function confirmDelete(id, name, cnt) {
    if (cnt > 0) { Swal.fire({ icon:'warning', title:'Cannot Delete', text:`"${name}" is assigned to ${cnt} merchant(s).`, confirmButtonColor:'#667eea' }); return; }
    Swal.fire({ title:'Delete Label?', html:`Delete <b>${name}</b>?`, icon:'warning', showCancelButton:true, confirmButtonColor:'#dc3545', confirmButtonText:'Delete' })
        .then(r => { if (r.isConfirmed) { document.getElementById('deleteId').value = id; document.getElementById('deleteForm').submit(); } });
}
document.addEventListener('DOMContentLoaded', function() {
    $('#labelsTable').DataTable({ pageLength: 25, order: [[4,'desc'],[1,'asc']], columnDefs: [{orderable:false, targets:[2,5,6,7]}], language:{search:"Search labels:"} });
});
</script>
