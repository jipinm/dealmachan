<?php /* views/blog/add.php */ ?>

<div class="d-flex align-items-center mb-4">
    <a href="<?= BASE_URL ?>blog" class="btn btn-sm btn-outline-secondary me-3">
        <i class="fas fa-arrow-left me-1"></i> Back
    </a>
    <h4 class="mb-0">New Blog Post</h4>
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
    <input type="hidden" name="id" value="">

    <div class="row g-3">
        <!-- Main Content Card -->
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="postTitle" class="form-control form-control-lg"
                               placeholder="Post title…" value="<?= escape($old['title'] ?? '') ?>" required maxlength="255"
                               oninput="autoSlug(this.value)">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Slug <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text text-muted small">/blog/</span>
                            <input type="text" name="slug" id="postSlug" class="form-control"
                                   placeholder="url-slug" value="<?= escape($old['slug'] ?? '') ?>" required maxlength="255">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="autoSlug(document.getElementById('postTitle').value)">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                        <div class="form-text">Lowercase letters, numbers, hyphens only.</div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Content <span class="text-danger">*</span></label>
                        <textarea name="content" id="blogContent" class="form-control" rows="18"
                                  placeholder="Write post content…" required><?= escape($old['content'] ?? '') ?></textarea>
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
                            <option value="draft"     <?= ($old['status'] ?? 'draft') === 'draft'     ? 'selected' : '' ?>>Draft</option>
                            <option value="published" <?= ($old['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
                            <option value="archived"  <?= ($old['status'] ?? '') === 'archived'  ? 'selected' : '' ?>>Archived</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-save me-1"></i> Save Post
                    </button>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header fw-semibold">Featured Image</div>
                <div class="card-body">
                    <input type="file" name="featured_image" class="form-control" accept="image/*">
                    <div class="form-text">Max 5 MB. jpg, png, gif, webp.</div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
function autoSlug(val) {
    const slugField = document.getElementById('postSlug');
    if (!slugField || slugField.dataset.edited === '1') return;
    slugField.value = val.toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '')
        .trim()
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .substring(0, 200);
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
