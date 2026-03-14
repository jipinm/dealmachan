<?php /* views/cms-pages/edit.php */ 
$isEdit = !empty($page);
$post   = array_merge($page ?? [], $post ?? []);
?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>cms-pages">CMS Pages</a></li>
        <li class="breadcrumb-item active"><?= $isEdit ? 'Edit' : 'Add' ?> Page</li>
    </ol>
</nav>
<h1 class="h3 mb-4"><?= $isEdit ? 'Edit' : 'Add' ?> CMS Page</h1>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= escape($e) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<form method="POST" action="<?= BASE_URL ?>cms-pages/<?= $isEdit ? 'edit?id=' . $page['id'] : 'add' ?>">
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($post['title'] ?? '', ENT_QUOTES) ?>" required
                           oninput="if(!this.form.slug.dataset.manual){this.form.slug.value=this.value.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');}">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Slug <span class="text-danger">*</span>
                        <small class="text-muted fw-normal ms-1">&mdash; URL path, lowercase with hyphens</small>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text text-muted">/</span>
                        <input type="text" name="slug" id="slugInput" class="form-control font-monospace"
                               value="<?= htmlspecialchars($post['slug'] ?? '', ENT_QUOTES) ?>" required
                               oninput="this.dataset.manual=1;this.value=this.value.toLowerCase().replace(/[^a-z0-9-]/g,'-');">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Content</label>
                    <textarea name="content" id="pageContent" class="form-control" rows="20"><?= htmlspecialchars($post['content'] ?? '', ENT_QUOTES) ?></textarea>
                    <div class="form-text">HTML is supported.</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold">Publish</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach (['draft'=>'Draft','published'=>'Published','archived'=>'Archived'] as $v=>$l): ?>
                        <option value="<?= $v ?>" <?= ($post['status'] ?? 'draft') === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-0">
                    <label class="form-label fw-semibold">Meta Description</label>
                    <textarea name="meta_description" class="form-control" rows="3" maxlength="255"><?= htmlspecialchars($post['meta_description'] ?? '', ENT_QUOTES) ?></textarea>
                </div>
            </div>
        </div>
        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i> Save Page</button>
            <a href="<?= BASE_URL ?>cms-pages" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </div>
</div>
</form>
