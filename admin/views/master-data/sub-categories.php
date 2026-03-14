<?php /* views/master-data/sub-categories.php */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>master-data">Master Data</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>master-data/categories">Categories</a></li>
                <li class="breadcrumb-item active">Sub-Categories</li>
            </ol>
        </nav>
        <h1 class="h3 mb-0">Sub-Categories</h1>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#subCatModal" onclick="openAddModal()">
        <i class="fas fa-plus me-2"></i> Add Sub-Category
    </button>
</div>

<!-- Filter bar -->
<form method="GET" action="" class="mb-3 d-flex gap-2 align-items-center">
    <label class="fw-semibold me-1">Filter by Category:</label>
    <select name="category_id" class="form-select form-select-sm" style="width:220px;" onchange="this.form.submit()">
        <option value="0">All Categories</option>
        <?php foreach ($categories as $cat): ?>
        <option value="<?= $cat['id'] ?>" <?= $filter_cat_id === (int)$cat['id'] ? 'selected' : '' ?>>
            <?= escape($cat['name']) ?>
        </option>
        <?php endforeach; ?>
    </select>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive p-3">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Category</th>
                        <th>Sub-Category</th>
                        <th>Icon</th>
                        <th class="text-center">Stores</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sub_categories as $i => $sub): ?>
                    <tr>
                        <td class="text-muted small"><?= $i + 1 ?></td>
                        <td><span class="badge bg-secondary"><?= escape($sub['category_name']) ?></span></td>
                        <td class="fw-semibold"><?= escape($sub['name']) ?></td>
                        <td>
                            <?php if ($sub['icon']): ?>
                            <i class="<?= escape($sub['icon']) ?>"></i>
                            <?php else: ?><span class="text-muted">&mdash;</span><?php endif; ?>
                        </td>
                        <td class="text-center"><span class="badge bg-success rounded-pill"><?= (int)$sub['store_count'] ?></span></td>
                        <td class="text-center">
                            <form method="POST" action="<?= BASE_URL ?>master-data/sub-categories" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                <input type="hidden" name="_action" value="toggle">
                                <input type="hidden" name="id" value="<?= $sub['id'] ?>">
                                <button type="submit" class="badge border-0 <?= $sub['status'] === 'active' ? 'bg-success' : 'bg-danger' ?> p-2">
                                    <?= $sub['status'] === 'active' ? 'Active' : 'Inactive' ?>
                                </button>
                            </form>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary me-1" onclick="openEditModal(<?= htmlspecialchars(json_encode($sub), ENT_QUOTES) ?>)"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?= $sub['id'] ?>, '<?= escape($sub['name']) ?>')"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($sub_categories)): ?><tr><td colspan="7" class="text-center text-muted py-4">No sub-categories found.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="subCatModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= BASE_URL ?>master-data/sub-categories">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="_action" value="save">
            <input type="hidden" name="id" id="subCatId" value="">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="subCatModalTitle">Add Sub-Category</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                        <select name="category_id" id="subCatCategoryId" class="form-select" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= escape($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Sub-Category Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="subCatName" class="form-control" required placeholder="e.g. Biryani &amp; Rice">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Icon Class <span class="text-muted small">(optional)</span></label>
                        <input type="text" name="icon" id="subCatIcon" class="form-control" placeholder="fas fa-bowl-rice">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" id="subCatStatus" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form method="POST" action="<?= BASE_URL ?>master-data/sub-categories">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="_action" value="delete">
            <input type="hidden" name="id" id="deleteId" value="">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="deleteMsg"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('subCatId').value         = '';
    document.getElementById('subCatCategoryId').value = '';
    document.getElementById('subCatName').value       = '';
    document.getElementById('subCatIcon').value       = '';
    document.getElementById('subCatStatus').value     = 'active';
    document.getElementById('subCatModalTitle').textContent = 'Add Sub-Category';
}
function openEditModal(sub) {
    document.getElementById('subCatId').value         = sub.id;
    document.getElementById('subCatCategoryId').value = sub.category_id;
    document.getElementById('subCatName').value       = sub.name;
    document.getElementById('subCatIcon').value       = sub.icon || '';
    document.getElementById('subCatStatus').value     = sub.status;
    document.getElementById('subCatModalTitle').textContent = 'Edit Sub-Category';
    new bootstrap.Modal(document.getElementById('subCatModal')).show();
}
function confirmDelete(id, name) {
    document.getElementById('deleteId').value  = id;
    document.getElementById('deleteMsg').innerHTML = `<p>Delete sub-category <strong>${name}</strong>?</p>`;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>
