<?php /* views/master-data/categories.php */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>master-data">Master Data</a></li>
                <li class="breadcrumb-item active">Categories</li>
            </ol>
        </nav>
        <h1 class="h3 mb-0">Store Categories</h1>
        <p class="text-muted mb-0 small">Top-level categories for stores and coupon targeting.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>master-data/sub-categories" class="btn btn-outline-secondary">
            <i class="fas fa-sitemap me-1"></i> Sub-Categories
        </a>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#catModal" onclick="openAddModal()">
            <i class="fas fa-plus me-2"></i> Add Category
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive p-3">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Category</th>
                        <th>Icon</th>
                        <th class="text-center">Sub-categories</th>
                        <th class="text-center">Stores</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $i => $cat): ?>
                    <tr>
                        <td class="text-muted small"><?= $i + 1 ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <?php if ($cat['color_code']): ?>
                                <span class="badge rounded-pill" style="background:<?= escape($cat['color_code']) ?>;">
                                    <?= escape($cat['name']) ?>
                                </span>
                                <?php else: ?>
                                <span class="fw-semibold"><?= escape($cat['name']) ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <?php if ($cat['icon']): ?>
                            <i class="<?= escape($cat['icon']) ?>"></i>
                            <code class="small ms-1 text-muted"><?= escape($cat['icon']) ?></code>
                            <?php else: ?><span class="text-muted">&mdash;</span><?php endif; ?>
                        </td>
                        <td class="text-center">
                            <a href="<?= BASE_URL ?>master-data/sub-categories?category_id=<?= $cat['id'] ?>" class="badge bg-info rounded-pill text-decoration-none">
                                <?= (int)$cat['sub_count'] ?>
                            </a>
                        </td>
                        <td class="text-center"><span class="badge bg-success rounded-pill"><?= (int)$cat['store_count'] ?></span></td>
                        <td class="text-center">
                            <form method="POST" action="<?= BASE_URL ?>master-data/categories" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                <input type="hidden" name="_action" value="toggle">
                                <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                <button type="submit" class="badge border-0 <?= $cat['status'] === 'active' ? 'bg-success' : 'bg-danger' ?> p-2">
                                    <?= $cat['status'] === 'active' ? 'Active' : 'Inactive' ?>
                                </button>
                            </form>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary me-1" onclick="openEditModal(<?= htmlspecialchars(json_encode($cat), ENT_QUOTES) ?>)"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?= $cat['id'] ?>, '<?= escape($cat['name']) ?>', <?= (int)$cat['store_count'] ?>)"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($categories)): ?><tr><td colspan="7" class="text-center text-muted py-4">No categories found.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add / Edit Modal -->
<div class="modal fade" id="catModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= BASE_URL ?>master-data/categories">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="_action" value="save">
            <input type="hidden" name="id" id="catId" value="">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="catModalTitle">Add Category</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Category Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="catName" class="form-control" required placeholder="e.g. Food &amp; Dining">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Icon Class <span class="text-muted small">(FontAwesome, optional)</span></label>
                        <input type="text" name="icon" id="catIcon" class="form-control" placeholder="fas fa-utensils">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Color Code <span class="text-muted small">(optional hex)</span></label>
                        <div class="input-group">
                            <input type="color" name="color_code" id="catColor" class="form-control form-control-color" value="#0d6efd">
                            <input type="text" id="catColorText" class="form-control" placeholder="#0d6efd" maxlength="7" readonly>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" id="catStatus" class="form-select">
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

<!-- Delete confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form method="POST" action="<?= BASE_URL ?>master-data/categories">
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
    document.getElementById('catId').value    = '';
    document.getElementById('catName').value  = '';
    document.getElementById('catIcon').value  = '';
    document.getElementById('catColor').value = '#0d6efd';
    document.getElementById('catColorText').value = '#0d6efd';
    document.getElementById('catStatus').value = 'active';
    document.getElementById('catModalTitle').textContent = 'Add Category';
}
function openEditModal(cat) {
    document.getElementById('catId').value    = cat.id;
    document.getElementById('catName').value  = cat.name;
    document.getElementById('catIcon').value  = cat.icon  || '';
    document.getElementById('catColor').value = cat.color_code || '#0d6efd';
    document.getElementById('catColorText').value = cat.color_code || '#0d6efd';
    document.getElementById('catStatus').value = cat.status;
    document.getElementById('catModalTitle').textContent = 'Edit Category';
    new bootstrap.Modal(document.getElementById('catModal')).show();
}
function confirmDelete(id, name, storeCount) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteMsg').innerHTML =
        storeCount > 0
            ? `<p class="text-danger">Cannot delete <strong>${name}</strong>: ${storeCount} stores use this category.</p>`
            : `<p>Delete category <strong>${name}</strong>?</p>`;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
document.getElementById('catColor').addEventListener('input', function () {
    document.getElementById('catColorText').value = this.value;
});
</script>
