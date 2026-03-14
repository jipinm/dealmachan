<?php /* views/blog/view.php */
$statusColors = ['draft' => 'secondary', 'published' => 'success', 'archived' => 'dark'];
$color = $statusColors[$post['status']] ?? 'secondary';
?>

<div class="d-flex align-items-center mb-4">
    <a href="<?= BASE_URL ?>blog" class="btn btn-sm btn-outline-secondary me-3">
        <i class="fas fa-arrow-left me-1"></i> Back
    </a>
    <div>
        <h4 class="mb-0"><?= escape($post['title']) ?></h4>
        <small class="text-muted">Post #<?= $post['id'] ?> &nbsp;|&nbsp;
            <code><?= escape($post['slug']) ?></code>
        </small>
    </div>
    <div class="ms-auto d-flex gap-2 flex-wrap">
        <a href="<?= BASE_URL ?>blog/edit?id=<?= $post['id'] ?>" class="btn btn-sm btn-warning">
            <i class="fas fa-edit me-1"></i> Edit
        </a>
        <?php if ($post['status'] !== 'published'): ?>
        <form method="POST" action="<?= BASE_URL ?>blog/publish">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="id" value="<?= $post['id'] ?>">
            <button type="submit" class="btn btn-sm btn-success">
                <i class="fas fa-globe me-1"></i> Publish
            </button>
        </form>
        <?php endif; ?>
        <?php if ($post['status'] !== 'archived'): ?>
        <form method="POST" action="<?= BASE_URL ?>blog/archive">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="id" value="<?= $post['id'] ?>">
            <button type="submit" class="btn btn-sm btn-secondary">
                <i class="fas fa-archive me-1"></i> Archive
            </button>
        </form>
        <?php endif; ?>
        <button class="btn btn-sm btn-danger" id="deleteBtn">
            <i class="fas fa-trash me-1"></i> Delete
        </button>
    </div>
</div>

<div class="row g-3">
    <!-- Meta Sidebar -->
    <div class="col-12 col-lg-4 col-xl-3">
        <div class="card shadow-sm mb-3">
            <div class="card-header fw-semibold">Post Details</div>
            <div class="card-body small">
                <dl class="row mb-0">
                    <dt class="col-5 text-muted">Status</dt>
                    <dd class="col-7"><span class="badge bg-<?= $color ?>"><?= ucfirst($post['status']) ?></span></dd>

                    <dt class="col-5 text-muted">Author</dt>
                    <dd class="col-7"><?= escape($post['author_name'] ?? '&mdash;') ?></dd>

                    <dt class="col-5 text-muted">Published</dt>
                    <dd class="col-7">
                        <?= $post['published_at'] ? date('d M Y H:i', strtotime($post['published_at'])) : '<span class="text-muted">&mdash;</span>' ?>
                    </dd>
                </dl>
            </div>
        </div>

        <?php if ($post['featured_image']): ?>
        <div class="card shadow-sm">
            <div class="card-header fw-semibold small">Featured Image</div>
            <div class="card-body p-0">
                <img src="<?= imageUrl($post['featured_image']) ?>"
                     class="img-fluid w-100 rounded-bottom"
                     style="max-height:180px;object-fit:cover;"
                     alt="Featured image"
                     onerror="this.src='<?= imageUrl('') ?>'">
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Content -->
    <div class="col-12 col-lg-8 col-xl-9">
        <div class="card shadow-sm">
            <div class="card-header fw-semibold">Content Preview</div>
            <div class="card-body" style="line-height:1.8;">
                <?= $post['content'] /* trusted HTML */ ?>
            </div>
        </div>
    </div>
</div>

<!-- Delete Form -->
<form method="POST" action="<?= BASE_URL ?>blog/delete" id="deleteForm">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
    <input type="hidden" name="id" value="<?= $post['id'] ?>">
</form>

<script>
document.getElementById('deleteBtn').addEventListener('click', function() {
    Swal.fire({
        title: 'Delete Post?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Yes, delete',
    }).then(r => { if (r.isConfirmed) document.getElementById('deleteForm').submit(); });
});
</script>
