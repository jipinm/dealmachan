<?php /* views/advertisements/view.php */ ?>

<div class="d-flex align-items-center mb-4">
    <a href="<?= BASE_URL ?>advertisements" class="btn btn-sm btn-outline-secondary me-3">
        <i class="fas fa-arrow-left me-1"></i> Back
    </a>
    <div>
        <h4 class="mb-0"><?= escape($ad['title']) ?></h4>
        <small class="text-muted">Ad #<?= $ad['id'] ?></small>
    </div>
    <div class="ms-auto d-flex gap-2">
        <a href="<?= BASE_URL ?>advertisements/edit?id=<?= $ad['id'] ?>" class="btn btn-sm btn-warning">
            <i class="fas fa-edit me-1"></i> Edit
        </a>
        <button class="btn btn-sm btn-danger" id="deleteBtn">
            <i class="fas fa-trash me-1"></i> Delete
        </button>
    </div>
</div>

<div class="row g-3">
    <!-- Media Preview -->
    <div class="col-12 col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span class="fw-semibold">
                    <i class="fas fa-<?= $ad['media_type'] === 'image' ? 'image' : 'video' ?> me-2"></i>
                    <?= ucfirst($ad['media_type']) ?> Preview
                </span>
                <span class="badge bg-<?= $ad['status'] === 'active' ? 'success' : 'secondary' ?>">
                    <?= ucfirst($ad['status']) ?>
                </span>
            </div>
            <div class="card-body bg-dark p-0 rounded-bottom" style="min-height:200px;">
                <?php if ($ad['media_type'] === 'image'): ?>
                    <img src="<?= imageUrl($ad['media_url']) ?>"
                         class="img-fluid w-100 rounded-bottom"
                         style="max-height:400px;object-fit:contain;"
                         alt="<?= escape($ad['title']) ?>"
                         onerror="this.src='<?= imageUrl('') ?>'">
                <?php else: ?>
                    <video controls class="w-100 rounded-bottom" style="max-height:400px;">
                        <source src="<?= imageUrl($ad['media_url']) ?>">
                        Your browser does not support video playback.
                    </video>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Details -->
    <div class="col-12 col-lg-5">
        <div class="card shadow-sm h-100">
            <div class="card-header fw-semibold">Ad Details</div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">Title</dt>
                    <dd class="col-7"><?= escape($ad['title']) ?></dd>

                    <dt class="col-5 text-muted">Media Type</dt>
                    <dd class="col-7">
                        <span class="badge bg-<?= $ad['media_type'] === 'image' ? 'info' : 'dark' ?>">
                            <?= ucfirst($ad['media_type']) ?>
                        </span>
                    </dd>

                    <dt class="col-5 text-muted">Duration</dt>
                    <dd class="col-7"><?= $ad['display_duration'] ?> seconds</dd>

                    <?php if ($ad['link_url']): ?>
                    <dt class="col-5 text-muted">Link URL</dt>
                    <dd class="col-7">
                        <a href="<?= escape($ad['link_url']) ?>" target="_blank" class="text-truncate d-inline-block" style="max-width:200px;">
                            <?= escape($ad['link_url']) ?>
                        </a>
                    </dd>
                    <?php endif; ?>

                    <dt class="col-5 text-muted">Start Date</dt>
                    <dd class="col-7">
                        <?= $ad['start_date'] ? date('d M Y H:i', strtotime($ad['start_date'])) : '<span class="text-muted">Not set</span>' ?>
                    </dd>

                    <dt class="col-5 text-muted">End Date</dt>
                    <dd class="col-7">
                        <?= $ad['end_date'] ? date('d M Y H:i', strtotime($ad['end_date'])) : '<span class="text-muted">Not set</span>' ?>
                    </dd>

                    <dt class="col-5 text-muted">Created By</dt>
                    <dd class="col-7"><?= escape($ad['created_by_name'] ?? 'N/A') ?></dd>

                    <dt class="col-5 text-muted">Status</dt>
                    <dd class="col-7">
                        <form method="POST" action="<?= BASE_URL ?>advertisements/toggle" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            <input type="hidden" name="id" value="<?= $ad['id'] ?>">
                            <input type="hidden" name="redirect" value="advertisements/detail?id=<?= $ad['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-<?= $ad['status'] === 'active' ? 'warning' : 'success' ?>">
                                <i class="fas fa-<?= $ad['status'] === 'active' ? 'pause' : 'play' ?> me-1"></i>
                                <?= $ad['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                            </button>
                        </form>
                    </dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<!-- Delete form (hidden) -->
<form method="POST" action="<?= BASE_URL ?>advertisements/delete" id="deleteForm">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
    <input type="hidden" name="id" value="<?= $ad['id'] ?>">
</form>

<script>
document.getElementById('deleteBtn').addEventListener('click', function() {
    Swal.fire({
        title: 'Delete Advertisement?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Yes, delete',
    }).then(r => { if (r.isConfirmed) document.getElementById('deleteForm').submit(); });
});
</script>
