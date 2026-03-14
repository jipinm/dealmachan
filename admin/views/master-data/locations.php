<?php /* views/master-data/locations.php */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>master-data">Master Data</a></li>
                <li class="breadcrumb-item active">Locations</li>
            </ol>
        </nav>
        <h1 class="h3 mb-0">Locations</h1>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#locationModal" onclick="openAddModal()">
        <i class="fas fa-plus me-2"></i> Add Location
    </button>
</div>

<!-- Filter Bar -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-2 px-3 d-flex align-items-center gap-3 flex-wrap">
        <label class="fw-semibold mb-0 small text-muted">City:</label>
        <select id="filterCity" class="form-select form-select-sm" style="max-width:180px;" onchange="onCityFilter(this.value)">
            <option value="">All Cities</option>
            <?php foreach ($cities as $c): ?>
            <option value="<?= $c['id'] ?>"><?= escape($c['city_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <label class="fw-semibold mb-0 small text-muted">Area:</label>
        <select id="filterArea" class="form-select form-select-sm" style="max-width:180px;" onchange="filterTable()">
            <option value="">All Areas</option>
            <?php foreach ($areas as $a): ?>
            <option value="<?= $a['area_name'] ?>"><?= escape($a['area_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <span class="text-muted small ms-auto">Total: <strong><?= count($locations) ?></strong></span>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive p-3">
            <table id="locationsTable" class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th>#</th><th>Location</th><th>Area</th><th>City</th><th>Coordinates</th><th class="text-center">Status</th><th class="text-center">Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($locations as $i => $loc): ?>
                    <tr>
                        <td class="text-muted small"><?= $i + 1 ?></td>
                        <td class="fw-semibold"><?= escape($loc['location_name']) ?></td>
                        <td><?= escape($loc['area_name'] ?? '&mdash;') ?></td>
                        <td><span class="badge bg-primary bg-opacity-10 text-primary"><?= escape($loc['city_name'] ?? '&mdash;') ?></span></td>
                        <td class="small text-muted">
                            <?php if ($loc['latitude'] && $loc['longitude']): ?>
                            <a href="https://maps.google.com/?q=<?= $loc['latitude'] ?>,<?= $loc['longitude'] ?>" target="_blank" class="text-decoration-none">
                                <i class="fas fa-map-marker-alt text-danger me-1"></i><?= $loc['latitude'] ?>, <?= $loc['longitude'] ?>
                            </a>
                            <?php else: ?><span class="text-muted">&mdash;</span><?php endif; ?>
                        </td>
                        <td class="text-center">
                            <form method="POST" action="<?= BASE_URL ?>master-data/locations" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                <input type="hidden" name="_action" value="toggle">
                                <input type="hidden" name="id" value="<?= $loc['id'] ?>">
                                <button type="submit" class="badge border-0 <?= $loc['status'] === 'active' ? 'bg-success' : 'bg-danger' ?> p-2" style="cursor:pointer;">
                                    <?= $loc['status'] === 'active' ? 'Active' : 'Inactive' ?>
                                </button>
                            </form>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary me-1" onclick="openEditModal(<?= htmlspecialchars(json_encode($loc), ENT_QUOTES) ?>)"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?= $loc['id'] ?>, '<?= escape($loc['location_name']) ?>')"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($locations)): ?><tr><td colspan="7" class="text-center text-muted py-4">No locations found.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="locationModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= BASE_URL ?>master-data/locations">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="_action" value="save">
            <input type="hidden" name="id" id="locId" value="">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="locModalTitle">Add Location</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">City <span class="text-danger">*</span></label>
                        <select id="modalCity" class="form-select" onchange="loadAreas(this.value)" required>
                            <option value="">Select City</option>
                            <?php foreach ($cities as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= escape($c['city_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Area <span class="text-danger">*</span></label>
                        <select name="area_id" id="locAreaId" class="form-select" required>
                            <option value="">Select City first</option>
                            <?php foreach ($areas as $a): ?>
                            <option value="<?= $a['id'] ?>" data-city="<?= $a['city_id'] ?>"><?= escape($a['area_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Location Name <span class="text-danger">*</span></label>
                        <input type="text" name="location_name" id="locName" class="form-control" required placeholder="e.g. Hill Road">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col">
                            <label class="form-label fw-semibold">Latitude</label>
                            <input type="number" name="latitude" id="locLat" class="form-control" step="0.00000001" placeholder="19.0990">
                        </div>
                        <div class="col">
                            <label class="form-label fw-semibold">Longitude</label>
                            <input type="number" name="longitude" id="locLng" class="form-control" step="0.00000001" placeholder="72.8477">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" id="locStatus" class="form-select">
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

<form method="POST" action="<?= BASE_URL ?>master-data/locations" id="deleteForm">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
    <input type="hidden" name="_action" value="delete">
    <input type="hidden" name="id" id="deleteId" value="">
</form>

<script>
// All area options as JS (for dependent dropdown)
const allAreas = <?= json_encode(array_map(fn($a) => ['id' => $a['id'], 'area_name' => $a['area_name'], 'city_id' => $a['city_id']], $areas)) ?>;

function loadAreas(cityId) {
    const sel = document.getElementById('locAreaId');
    sel.innerHTML = '<option value="">Select Area</option>';
    allAreas.filter(a => !cityId || a.city_id == cityId).forEach(a => {
        sel.innerHTML += `<option value="${a.id}">${a.area_name}</option>`;
    });
}

function openAddModal() {
    document.getElementById('locModalTitle').textContent = 'Add Location';
    document.getElementById('locId').value = '';
    document.getElementById('modalCity').value = '';
    document.getElementById('locName').value = '';
    document.getElementById('locLat').value = '';
    document.getElementById('locLng').value = '';
    document.getElementById('locStatus').value = 'active';
    loadAreas('');
}

function openEditModal(loc) {
    document.getElementById('locModalTitle').textContent = 'Edit Location';
    document.getElementById('locId').value = loc.id;
    document.getElementById('locName').value = loc.location_name;
    document.getElementById('locLat').value = loc.latitude || '';
    document.getElementById('locLng').value = loc.longitude || '';
    document.getElementById('locStatus').value = loc.status;
    document.getElementById('modalCity').value = loc.city_id || '';
    loadAreas(loc.city_id || '');
    setTimeout(() => { document.getElementById('locAreaId').value = loc.area_id; }, 50);
    new bootstrap.Modal(document.getElementById('locationModal')).show();
}

function confirmDelete(id, name) {
    Swal.fire({ title: 'Delete?', html: `Delete location <b>${name}</b>?`, icon: 'warning',
        showCancelButton: true, confirmButtonColor: '#dc3545', confirmButtonText: 'Delete'
    }).then(r => { if (r.isConfirmed) { document.getElementById('deleteId').value = id; document.getElementById('deleteForm').submit(); } });
}

function filterTable() {
    if (window.dtLoc) window.dtLoc.draw();
}

document.addEventListener('DOMContentLoaded', function() {
    window.dtLoc = $('#locationsTable').DataTable({
        pageLength: 25, order: [[3,'asc'],[2,'asc'],[1,'asc']],
        columnDefs: [{ orderable: false, targets: [5, 6] }],
        language: { search: "Search locations:" }
    });
});
</script>
