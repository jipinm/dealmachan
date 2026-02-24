<?php /* views/cms-pages/index.php */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">CMS Pages</h1>
        <p class="text-muted mb-0 small">Manage static pages (About, Privacy Policy, Terms, etc.)</p>
    </div>
    <a href="<?= BASE_URL ?>cms-pages/add" class="btn btn-primary"><i class="fas fa-plus me-2"></i> Add Page</a>
</div>

<?php if ($flash_success): ?>
<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i><?= escape($flash_success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if ($flash_error): ?>
<div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle me-2"></i><?= escape($flash_error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($pages)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fas fa-file-alt fa-3x mb-3 opacity-25"></i>
            <p>No CMS pages yet. <a href="<?= BASE_URL ?>cms-pages/add">Create the first one</a>.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Slug</th>
                        <th>Title</th>
                        <th class="text-center">Status</th>
                        <th>Updated</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pages as $p): ?>
                    <tr>
                        <td class="font-monospace text-muted">/<?= escape($p['slug']) ?></td>
                        <td class="fw-semibold"><?= escape($p['title']) ?></td>
                        <td class="text-center">
                            <span class="badge bg-<?= $p['status'] === 'published' ? 'success' : ($p['status'] === 'archived' ? 'secondary' : 'warning text-dark') ?>">
                                <?= ucfirst($p['status']) ?>
                            </span>
                        </td>
                        <td><?= formatDate($p['updated_at']) ?></td>
                        <td class="text-center">
                            <a href="<?= BASE_URL ?>cms-pages/edit?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary me-1"><i class="fas fa-edit"></i></a>
                            <form method="POST" action="<?= BASE_URL ?>cms-pages/delete" class="d-inline"
                                  onsubmit="return confirm('Delete page «<?= escape($p['title']) ?>»?')">
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
