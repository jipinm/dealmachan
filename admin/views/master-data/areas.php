<?php /* views/master-data/areas.php */ ?>
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>master-data">Master Data</a></li>
                <li class="breadcrumb-item active">Areas</li>
            </ol>
        </nav>
        <h1 class="h3 mb-0">Areas</h1>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#areaModal" onclick="openAddModal()">
        <i class="fas fa-plus me-2"></i> Add Area
    </button>
</div>

<!-- Filter Row -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-2 px-3 d-flex align-items-center gap-3">
        <label class="fw-semibold mb-0 small text-muted">Filter by City:</label>
        <select id="cityFilter" class="form-select form-select-sm" style="max-width:200px;" onchange="filterByCity(this.value)">
            <option value="">All Cities</option>
            <?php foreach ($cities as $c): ?>
            <option value="<?= $c['id'] ?>"><?= escape($c['city_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <span class="text-muted small ms-auto">Total: <strong><?= count($areas) ?></strong> areas</span>
    </div>
</div>

<!-- Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive p-3">
            <table id="areasTable" class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Area Name</th>
                        <th>City</th>
                        <th class="text-center">Locations</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($areas as $i => $area): ?>
                    <tr>
                        <td class="text-muted small"><?= $i + 1 ?></td>
                        <td class="fw-semibold"><?= escape($area['area_name']) ?></td>
                        <td><span class="badge bg-primary bg-opacity-10 text-primary"><?= escape($area['city_name'] ?? '—') ?></span></td>
                        <td class="text-center">
                            <span class="badge bg-info rounded-pill"><?= $area['location_count'] ?? 0 ?></span>
                        </td>
                        <td class="text-center">
                            <form method="POST" action="<?= BASE_URL ?>master-data/areas" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                <input type="hidden" name="_action" value="toggle">
                                <input type="hidden" name="id" value="<?= $area['id'] ?>">
                                <button type="submit" class="badge border-0 <?= $area['status'] === 'active' ? 'bg-success' : 'bg-danger' ?> p-2" style="cursor:pointer;">
                                    <?= $area['status'] === 'active' ? 'Active' : 'Inactive' ?>
                                </button>
                            </form>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary me-1" title="Edit"
                                onclick="openEditModal(<?= htmlspecialchars(json_encode($area), ENT_QUOTES) ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" title="Delete"
                                onclick="confirmDelete(<?= $area['id'] ?>, '<?= escape($area['area_name']) ?>', <?= $area['location_count'] ?? 0 ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($areas)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No areas found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add / Edit Modal -->
<div class="modal fade" id="areaModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= BASE_URL ?>master-data/areas">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="_action" value="save">
            <input type="hidden" name="id" id="areaId" value="">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="areaModalTitle">Add Area</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">City <span class="text-danger">*</span></label>
                        <select name="city_id" id="areaCityId" class="form-select" required>
                            <option value="">Select City</option>
                            <?php foreach ($cities as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= escape($c['city_name']) ?> (<?= escape($c['state']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Area Name <span class="text-danger">*</span></label>
                        <input type="text" name="area_name" id="areaName" class="form-control" required placeholder="e.g. Bandra West">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" id="areaStatus" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Area</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Delete Form (hidden) -->
<form method="POST" action="<?= BASE_URL ?>master-data/areas" id="deleteForm">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
    <input type="hidden" name="_action" value="delete">
    <input type="hidden" name="id" id="deleteId" value="">
</form>

<script>
var dtTable;

function openAddModal() {
    document.getElementById('areaModalTitle').textContent = 'Add Area';
    document.getElementById('areaId').value = '';
    document.getElementById('areaName').value = '';
    document.getElementById('areaCityId').value = '';
    document.getElementById('areaStatus').value = 'active';
}

function openEditModal(area) {
    document.getElementById('areaModalTitle').textContent = 'Edit Area';
    document.getElementById('areaId').value = area.id;
    document.getElementById('areaName').value = area.area_name;
    document.getElementById('areaCityId').value = area.city_id;
    document.getElementById('areaStatus').value = area.status;
    new bootstrap.Modal(document.getElementById('areaModal')).show();
}

function confirmDelete(id, name, locCount) {
    if (locCount > 0) {
        Swal.fire({ icon: 'warning', title: 'Cannot Delete', text: `"${name}" has ${locCount} location(s). Remove them first.`, confirmButtonColor: '#667eea' });
        return;
    }
    Swal.fire({
        title: 'Delete Area?', html: `Delete <b>${name}</b>?`, icon: 'warning',
        showCancelButton: true, confirmButtonColor: '#dc3545', confirmButtonText: 'Yes, Delete'
    }).then((r) => { if (r.isConfirmed) { document.getElementById('deleteId').value = id; document.getElementById('deleteForm').submit(); } });
}

function filterByCity(cityId) {
    if (dtTable) {
        dtTable.column(2).search(cityId ? $(`#areaCityId option[value="${cityId}"]`).text().split(' (')[0] : '').draw();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    dtTable = $('#areasTable').DataTable({ pageLength: 25, order: [[2,'asc'],[1,'asc']], columnDefs: [{ orderable: false, targets: [3,4,5] }], language: { search: "Search areas:" } });
});
</script>
