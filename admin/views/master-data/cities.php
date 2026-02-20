<?php /* views/master-data/cities.php */ ?>
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>master-data">Master Data</a></li>
                <li class="breadcrumb-item active">Cities</li>
            </ol>
        </nav>
        <h1 class="h3 mb-0">Cities</h1>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#cityModal" onclick="openAddModal()">
        <i class="fas fa-plus me-2"></i> Add City
    </button>
</div>

<!-- Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-auto">
        <div class="card border-0 shadow-sm px-3 py-2">
            <span class="text-muted small">Total</span>
            <strong class="fs-5"><?= count($cities) ?></strong>
        </div>
    </div>
    <div class="col-auto">
        <div class="card border-0 shadow-sm px-3 py-2">
            <span class="text-muted small">Active</span>
            <strong class="fs-5 text-success"><?= count(array_filter($cities, fn($c) => $c['status'] === 'active')) ?></strong>
        </div>
    </div>
    <div class="col-auto">
        <div class="card border-0 shadow-sm px-3 py-2">
            <span class="text-muted small">Inactive</span>
            <strong class="fs-5 text-danger"><?= count(array_filter($cities, fn($c) => $c['status'] === 'inactive')) ?></strong>
        </div>
    </div>
</div>

<!-- Table Card -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-semibold">All Cities</h6>
        </div>
        <div class="table-responsive p-3">
            <table id="citiesTable" class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>City Name</th>
                        <th>State</th>
                        <th>Country</th>
                        <th class="text-center">Areas</th>
                        <th class="text-center">Merchants</th>
                        <th class="text-center">Admins</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cities as $i => $city): ?>
                    <tr>
                        <td class="text-muted small"><?= $i + 1 ?></td>
                        <td class="fw-semibold"><?= escape($city['city_name']) ?></td>
                        <td><?= escape($city['state']) ?></td>
                        <td><?= escape($city['country']) ?></td>
                        <td class="text-center">
                            <span class="badge bg-primary rounded-pill"><?= $city['area_count'] ?></span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-success rounded-pill"><?= $city['merchant_count'] ?></span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-info rounded-pill"><?= $city['admin_count'] ?></span>
                        </td>
                        <td class="text-center">
                            <form method="POST" action="<?= BASE_URL ?>master-data/cities" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                <input type="hidden" name="_action" value="toggle">
                                <input type="hidden" name="id" value="<?= $city['id'] ?>">
                                <button type="submit" class="badge border-0 <?= $city['status'] === 'active' ? 'bg-success' : 'bg-danger' ?> p-2" 
                                        title="Click to toggle" style="cursor:pointer;">
                                    <?= $city['status'] === 'active' ? 'Active' : 'Inactive' ?>
                                </button>
                            </form>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary me-1" title="Edit"
                                onclick="openEditModal(<?= htmlspecialchars(json_encode($city), ENT_QUOTES) ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" title="Delete"
                                onclick="confirmDelete(<?= $city['id'] ?>, '<?= escape($city['city_name']) ?>', <?= $city['area_count'] ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($cities)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">No cities found. Add your first city.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add / Edit Modal -->
<div class="modal fade" id="cityModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= BASE_URL ?>master-data/cities" id="cityForm">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="_action" value="save">
            <input type="hidden" name="id" id="cityId" value="">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="cityModalTitle">Add City</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">City Name <span class="text-danger">*</span></label>
                        <input type="text" name="city_name" id="cityName" class="form-control" required placeholder="e.g. Mumbai">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">State <span class="text-danger">*</span></label>
                        <input type="text" name="state" id="cityState" class="form-control" required placeholder="e.g. Maharashtra">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Country</label>
                        <input type="text" name="country" id="cityCountry" class="form-control" value="India" placeholder="India">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" id="cityStatus" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Save City
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Delete Form (hidden) -->
<form method="POST" action="<?= BASE_URL ?>master-data/cities" id="deleteForm">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
    <input type="hidden" name="_action" value="delete">
    <input type="hidden" name="id" id="deleteId" value="">
</form>

<script>
function openAddModal() {
    document.getElementById('cityModalTitle').textContent = 'Add City';
    document.getElementById('cityId').value = '';
    document.getElementById('cityName').value = '';
    document.getElementById('cityState').value = '';
    document.getElementById('cityCountry').value = 'India';
    document.getElementById('cityStatus').value = 'active';
}

function openEditModal(city) {
    document.getElementById('cityModalTitle').textContent = 'Edit City';
    document.getElementById('cityId').value = city.id;
    document.getElementById('cityName').value = city.city_name;
    document.getElementById('cityState').value = city.state;
    document.getElementById('cityCountry').value = city.country;
    document.getElementById('cityStatus').value = city.status;
    new bootstrap.Modal(document.getElementById('cityModal')).show();
}

function confirmDelete(id, name, areaCount) {
    if (areaCount > 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Cannot Delete',
            text: `"${name}" has ${areaCount} area(s) linked to it. Remove areas first.`,
            confirmButtonColor: '#667eea'
        });
        return;
    }
    Swal.fire({
        title: 'Delete City?',
        html: `Are you sure you want to delete <b>${name}</b>? This cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Delete'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteForm').submit();
        }
    });
}

// Initialize DataTable
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('citiesTable').querySelector('tbody tr td[colspan]') === null) {
        $('#citiesTable').DataTable({
            pageLength: 25,
            order: [[1, 'asc']],
            columnDefs: [{ orderable: false, targets: [4, 5, 6, 7, 8] }],
            language: { search: "Search cities:" }
        });
    }
});
</script>
