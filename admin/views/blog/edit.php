<?php /* views/blog/edit.php */
$d = $old ?? $post;
$slugEdited = !empty($old['slug']) ? '1' : '0';
?>

<div class="d-flex align-items-center mb-4">
    <a href="<?= BASE_URL ?>blog/detail?id=<?= $post['id'] ?>" class="btn btn-sm btn-outline-secondary me-3">
        <i class="fas fa-arrow-left me-1"></i> Back
    </a>
    <h4 class="mb-0">Edit Post</h4>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <ul class="mb-0 ps-3">
        <?php foreach ($errors as $e): ?><li><?= escape($e) ?></li><?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<form method="POST" action="<?= BASE_URL ?>blog/save" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
    <input type="hidden" name="id" value="<?= $post['id'] ?>">

    <div class="row g-3">
        <!-- Main Content Card -->
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="postTitle" class="form-control form-control-lg"
                               value="<?= escape($d['title']) ?>" required maxlength="255"
                               oninput="autoSlug(this.value)">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Slug <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text text-muted small">/blog/</span>
                            <input type="text" name="slug" id="postSlug" class="form-control"
                                   value="<?= escape($d['slug']) ?>" required maxlength="255"
                                   data-edited="1">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="regenSlug()">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                        <div class="form-text">Changing the slug will break existing links.</div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Content <span class="text-danger">*</span></label>
                        <textarea name="content" id="blogContent" class="form-control" rows="18"
                                  required><?= escape($d['content']) ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header fw-semibold">Publish</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            <option value="draft"     <?= $d['status'] === 'draft'     ? 'selected' : '' ?>>Draft</option>
                            <option value="published" <?= $d['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                            <option value="archived"  <?= $d['status'] === 'archived'  ? 'selected' : '' ?>>Archived</option>
                        </select>
                    </div>
                    <?php if ($post['published_at']): ?>
                    <div class="text-muted small mb-3">
                        <i class="fas fa-globe me-1"></i>
                        Published <?= date('d M Y', strtotime($post['published_at'])) ?>
                    </div>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-warning w-100">
                        <i class="fas fa-save me-1"></i> Save Changes
                    </button>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header fw-semibold">Featured Image</div>
                <div class="card-body">
                    <?php if ($post['featured_image']): ?>
                    <img src="<?= imageUrl($post['featured_image']) ?>"
                         class="img-fluid w-100 rounded mb-2"
                         style="max-height:150px;object-fit:cover;" alt=""
                         onerror="this.src='<?= imageUrl('') ?>'">
                    <?php endif; ?>
                    <input type="file" name="featured_image" class="form-control" accept="image/*">
                    <div class="form-text">Max 5 MB. Leave blank to keep current.</div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
function autoSlug(val) {
    const sf = document.getElementById('postSlug');
    if (sf.dataset.edited === '1') return;
    sf.value = val.toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '').trim()
        .replace(/\s+/g, '-').replace(/-+/g, '-').substring(0, 200);
}
function regenSlug() {
    const sf = document.getElementById('postSlug');
    sf.dataset.edited = '0';
    autoSlug(document.getElementById('postTitle').value);
}
document.getElementById('postSlug').addEventListener('input', function() {
    this.dataset.edited = '1';
});
</script>

<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
<script>
tinymce.init({
    selector: '#blogContent',
    height: 480,
    menubar: true,
    plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount code fullscreen',
    toolbar: 'undo redo | blocks fontsize | bold italic underline strikethrough | link image media | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | code fullscreen',
    content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; font-size: 15px; }',
    setup: function(editor) {
        editor.on('change', function() { editor.save(); });
    }
});
</script>
