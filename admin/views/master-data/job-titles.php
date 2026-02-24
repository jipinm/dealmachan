<?php /* views/master-data/job-titles.php */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>master-data">Master Data</a></li>
                <li class="breadcrumb-item active">Job Titles</li>
            </ol>
        </nav>
        <h1 class="h3 mb-0">Job Titles</h1>
        <p class="text-muted mb-0 small">Job titles linked to professions, selectable during customer registration.</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#jobTitleModal" onclick="openAddModal()">
        <i class="fas fa-plus me-2"></i> Add Job Title
    </button>
</div>

<?php if (!empty($_SESSION['success'])): ?>
<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3">
    <i class="fas fa-check-circle me-2"></i><?= escape($_SESSION['success']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['success']); endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3">
    <i class="fas fa-exclamation-circle me-2"></i><?= escape($_SESSION['error']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['error']); endif; ?>

<!-- Filter by profession -->
<div class="mb-3">
    <select id="professionFilter" class="form-select form-select-sm" style="width:220px;" onchange="filterByProfession(this.value)">
        <option value="">All Professions</option>
        <?php foreach ($professions as $prof): ?>
        <option value="<?= escape($prof['profession_name']) ?>"><?= escape($prof['profession_name']) ?></option>
        <?php endforeach; ?>
    </select>
</div>

<div class="row g-4">
    <!-- Table -->
    <div class="col-lg-9">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive p-3">
                    <table id="jobTitlesTable" class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Job Title</th>
                                <th>Profession</th>
                                <th class="text-center">Customers</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($jobTitles as $i => $jt): ?>
                            <tr>
                                <td class="text-muted small"><?= $i + 1 ?></td>
                                <td class="fw-semibold"><?= escape($jt['job_title_name']) ?></td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                        <?= escape($jt['profession_name']) ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info rounded-pill"><?= $jt['customer_count'] ?? 0 ?></span>
                                </td>
                                <td class="text-center">
                                    <form method="POST" action="<?= BASE_URL ?>master-data/job-titles" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                        <input type="hidden" name="_action" value="toggle">
                                        <input type="hidden" name="id" value="<?= $jt['id'] ?>">
                                        <button type="submit" class="badge border-0 <?= $jt['status'] === 'active' ? 'bg-success' : 'bg-danger' ?> p-2" style="cursor:pointer;">
                                            <?= $jt['status'] === 'active' ? 'Active' : 'Inactive' ?>
                                        </button>
                                    </form>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary me-1"
                                            onclick="openEditModal(<?= htmlspecialchars(json_encode($jt), ENT_QUOTES) ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger"
                                            onclick="confirmDelete(<?= $jt['id'] ?>, '<?= escape($jt['job_title_name']) ?>', <?= (int)($jt['customer_count'] ?? 0) ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($jobTitles)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No job titles found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar summary -->
    <div class="col-lg-3">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Summary</h6>
                <?php
                $activeCount   = count(array_filter($jobTitles, fn($j) => $j['status'] === 'active'));
                $withCustomers = count(array_filter($jobTitles, fn($j) => ($j['customer_count'] ?? 0) > 0));
                // Unique professions covered
                $profCoverage = count(array_unique(array_column($jobTitles, 'profession_name')));
                ?>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Total Titles</span>
                    <strong><?= count($jobTitles) ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Active</span>
                    <strong class="text-success"><?= $activeCount ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">With Customers</span>
                    <strong class="text-info"><?= $withCustomers ?></strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Professions Covered</span>
                    <strong class="text-primary"><?= $profCoverage ?></strong>
                </div>
            </div>
        </div>
        <div class="alert alert-info border-0 small">
            <i class="fas fa-info-circle me-1"></i>
            Job titles are linked to professions. Titles with assigned customers cannot be deleted.
        </div>
    </div>
</div>

<!-- Add / Edit Modal -->
<div class="modal fade" id="jobTitleModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form method="POST" action="<?= BASE_URL ?>master-data/job-titles">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="_action" value="save">
            <input type="hidden" name="id" id="jtId" value="">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="jtModalTitle">Add Job Title</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Profession <span class="text-danger">*</span></label>
                        <select name="profession_id" id="jtProfessionId" class="form-select" required>
                            <option value="">Select profession…</option>
                            <?php foreach ($professions as $prof): ?>
                            <option value="<?= $prof['id'] ?>"><?= escape($prof['profession_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Job Title Name <span class="text-danger">*</span></label>
                        <input type="text" name="job_title_name" id="jtName" class="form-control"
                               required placeholder="e.g. Senior Engineer">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" id="jtStatus" class="form-select">
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

<!-- Delete form -->
<form method="POST" action="<?= BASE_URL ?>master-data/job-titles" id="deleteForm">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
    <input type="hidden" name="_action" value="delete">
    <input type="hidden" name="id" id="deleteId" value="">
</form>

<script>
function openAddModal() {
    document.getElementById('jtModalTitle').textContent = 'Add Job Title';
    document.getElementById('jtId').value           = '';
    document.getElementById('jtName').value         = '';
    document.getElementById('jtProfessionId').value = '';
    document.getElementById('jtStatus').value       = 'active';
}

function openEditModal(jt) {
    document.getElementById('jtModalTitle').textContent = 'Edit Job Title';
    document.getElementById('jtId').value           = jt.id;
    document.getElementById('jtName').value         = jt.job_title_name;
    document.getElementById('jtProfessionId').value = jt.profession_id;
    document.getElementById('jtStatus').value       = jt.status;
    new bootstrap.Modal(document.getElementById('jobTitleModal')).show();
}

function confirmDelete(id, name, cnt) {
    if (cnt > 0) {
        Swal.fire({ icon:'warning', title:'Cannot Delete',
            text: `"${name}" is assigned to ${cnt} customer(s).`,
            confirmButtonColor: '#667eea' });
        return;
    }
    Swal.fire({ title:'Delete?', html:`Delete job title <b>${name}</b>?`,
        icon:'warning', showCancelButton:true,
        confirmButtonColor:'#dc3545', confirmButtonText:'Delete' })
        .then(r => {
            if (r.isConfirmed) {
                document.getElementById('deleteId').value = id;
                document.getElementById('deleteForm').submit();
            }
        });
}

function filterByProfession(profName) {
    const table = $('#jobTitlesTable').DataTable();
    table.column(2).search(profName).draw();
}

document.addEventListener('DOMContentLoaded', function() {
    $('#jobTitlesTable').DataTable({
        pageLength: 25,
        order: [[2, 'asc'], [1, 'asc']],
        columnDefs: [{ orderable: false, targets: [3, 4, 5] }]
    });
});
</script>
