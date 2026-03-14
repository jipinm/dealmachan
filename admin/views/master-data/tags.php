<?php /* views/master-data/tags.php */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>master-data">Master Data</a></li>
                <li class="breadcrumb-item active">Tags</li>
            </ol>
        </nav>
        <h1 class="h3 mb-0">Tags</h1>
        <p class="text-muted mb-0 small">Tags are special filters for merchant categories and coupon subcategories.</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tagModal" onclick="openAddModal()">
        <i class="fas fa-plus me-2"></i> Add Tag
    </button>
</div>

<!-- Category Filter -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-2 px-3 d-flex align-items-center gap-3 flex-wrap">
        <label class="fw-semibold mb-0 small text-muted">Category:</label>
        <?php
        $catCounts = ['category' => 0, 'subcategory' => 0, 'filter' => 0];
        foreach ($tags as $t) { if (isset($catCounts[$t['tag_category']])) $catCounts[$t['tag_category']]++; }
        ?>
        <button class="btn btn-sm btn-outline-secondary active" onclick="filterCat('', this)">All <span class="badge bg-secondary ms-1"><?= count($tags) ?></span></button>
        <button class="btn btn-sm btn-outline-primary" onclick="filterCat('category', this)">Category <span class="badge bg-primary ms-1"><?= $catCounts['category'] ?></span></button>
        <button class="btn btn-sm btn-outline-success" onclick="filterCat('subcategory', this)">Subcategory <span class="badge bg-success ms-1"><?= $catCounts['subcategory'] ?></span></button>
        <button class="btn btn-sm btn-outline-info" onclick="filterCat('filter', this)">Filter <span class="badge bg-info ms-1"><?= $catCounts['filter'] ?></span></button>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive p-3">
            <table id="tagsTable" class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th>#</th><th>Tag Name</th><th>Category</th><th>Parent Tag</th><th class="text-center">Children</th><th class="text-center">Merchants</th><th class="text-center">Coupons</th><th class="text-center">Status</th><th class="text-center">Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($tags as $i => $tag): ?>
                    <?php
                    $catBadge = ['category' => 'bg-primary', 'subcategory' => 'bg-success', 'filter' => 'bg-info'][$tag['tag_category']] ?? 'bg-secondary';
                    ?>
                    <tr>
                        <td class="text-muted small"><?= $i + 1 ?></td>
                        <td class="fw-semibold"><?= escape($tag['tag_name']) ?></td>
                        <td><span class="badge <?= $catBadge ?>"><?= ucfirst($tag['tag_category']) ?></span></td>
                        <td><?= $tag['parent_name'] ? escape($tag['parent_name']) : '<span class="text-muted">&mdash;</span>' ?></td>
                        <td class="text-center"><span class="badge bg-secondary rounded-pill"><?= $tag['child_count'] ?? 0 ?></span></td>
                        <td class="text-center"><span class="badge bg-success rounded-pill"><?= $tag['merchant_count'] ?? 0 ?></span></td>
                        <td class="text-center"><span class="badge bg-warning rounded-pill"><?= $tag['coupon_count'] ?? 0 ?></span></td>
                        <td class="text-center">
                            <form method="POST" action="<?= BASE_URL ?>master-data/tags" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                <input type="hidden" name="_action" value="toggle">
                                <input type="hidden" name="id" value="<?= $tag['id'] ?>">
                                <button type="submit" class="badge border-0 <?= $tag['status'] === 'active' ? 'bg-success' : 'bg-danger' ?> p-2" style="cursor:pointer;">
                                    <?= $tag['status'] === 'active' ? 'Active' : 'Inactive' ?>
                                </button>
                            </form>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary me-1" onclick="openEditModal(<?= htmlspecialchars(json_encode($tag), ENT_QUOTES) ?>)"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?= $tag['id'] ?>, '<?= escape($tag['tag_name']) ?>', <?= ($tag['child_count'] ?? 0) + ($tag['merchant_count'] ?? 0) + ($tag['coupon_count'] ?? 0) ?>)"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($tags)): ?><tr><td colspan="9" class="text-center text-muted py-4">No tags found.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="tagModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= BASE_URL ?>master-data/tags">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="_action" value="save">
            <input type="hidden" name="id" id="tagId" value="">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="tagModalTitle">Add Tag</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tag Name <span class="text-danger">*</span></label>
                        <input type="text" name="tag_name" id="tagName" class="form-control" required placeholder="e.g. Restaurants">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                        <select name="tag_category" id="tagCategory" class="form-select" onchange="onCategoryChange(this.value)">
                            <option value="category">Category (Top-level)</option>
                            <option value="subcategory">Subcategory</option>
                            <option value="filter">Filter</option>
                        </select>
                    </div>
                    <div class="mb-3" id="parentTagDiv" style="display:none;">
                        <label class="form-label fw-semibold">Parent Tag</label>
                        <select name="parent_tag_id" id="tagParent" class="form-select">
                            <option value="">None (top-level)</option>
                            <?php foreach ($parent_tags as $pt): ?>
                            <option value="<?= $pt['id'] ?>"><?= escape($pt['tag_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" id="tagStatus" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Tag</button>
                </div>
            </div>
        </form>
    </div>
</div>

<form method="POST" action="<?= BASE_URL ?>master-data/tags" id="deleteForm">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
    <input type="hidden" name="_action" value="delete">
    <input type="hidden" name="id" id="deleteId" value="">
</form>

<script>
var dtTags;

function onCategoryChange(val) {
    document.getElementById('parentTagDiv').style.display = val !== 'category' ? 'block' : 'none';
}
function openAddModal() {
    document.getElementById('tagModalTitle').textContent = 'Add Tag';
    document.getElementById('tagId').value = '';
    document.getElementById('tagName').value = '';
    document.getElementById('tagCategory').value = 'category';
    document.getElementById('tagParent').value = '';
    document.getElementById('tagStatus').value = 'active';
    onCategoryChange('category');
}
function openEditModal(tag) {
    document.getElementById('tagModalTitle').textContent = 'Edit Tag';
    document.getElementById('tagId').value = tag.id;
    document.getElementById('tagName').value = tag.tag_name;
    document.getElementById('tagCategory').value = tag.tag_category;
    document.getElementById('tagParent').value = tag.parent_tag_id || '';
    document.getElementById('tagStatus').value = tag.status;
    onCategoryChange(tag.tag_category);
    new bootstrap.Modal(document.getElementById('tagModal')).show();
}
function confirmDelete(id, name, linked) {
    if (linked > 0) { Swal.fire({ icon:'warning', title:'Cannot Delete', text:`"${name}" has linked tags/merchants/coupons (${linked} total).`, confirmButtonColor:'#667eea' }); return; }
    Swal.fire({ title:'Delete Tag?', html:`Delete <b>${name}</b>?`, icon:'warning', showCancelButton:true, confirmButtonColor:'#dc3545', confirmButtonText:'Delete' })
        .then(r => { if (r.isConfirmed) { document.getElementById('deleteId').value = id; document.getElementById('deleteForm').submit(); } });
}
function filterCat(cat, btn) {
    document.querySelectorAll('.btn-outline-secondary,.btn-outline-primary,.btn-outline-success,.btn-outline-info').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    dtTags.column(2).search(cat ? ucFirst(cat) : '').draw();
}
function ucFirst(s) { return s.charAt(0).toUpperCase() + s.slice(1); }

document.addEventListener('DOMContentLoaded', function() {
    dtTags = $('#tagsTable').DataTable({ pageLength: 25, order: [[2,'asc'],[1,'asc']], columnDefs:[{orderable:false,targets:[4,5,6,7,8]}], language:{search:"Search tags:"} });
});
</script>
