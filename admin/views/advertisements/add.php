<?php /* views/advertisements/add.php */ ?>

<div class="d-flex align-items-center mb-4">
    <a href="<?= BASE_URL ?>advertisements" class="btn btn-sm btn-outline-secondary me-3">
        <i class="fas fa-arrow-left me-1"></i> Back
    </a>
    <h4 class="mb-0">Create Advertisement</h4>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <ul class="mb-0 ps-3">
        <?php foreach ($errors as $e): ?><li><?= escape($e) ?></li><?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="<?= BASE_URL ?>advertisements/save" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="id" value="">

            <div class="row g-3">
                <!-- Title -->
                <div class="col-12">
                    <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control"
                           value="<?= escape($old['title'] ?? '') ?>" required maxlength="255">
                </div>

                <!-- Media Type -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Media Type <span class="text-danger">*</span></label>
                    <select name="media_type" id="mediaTypeSelect" class="form-select" required onchange="updateAccept(this.value)">
                        <option value="image" <?= ($old['media_type'] ?? '') === 'image' ? 'selected' : '' ?>>Image</option>
                        <option value="video" <?= ($old['media_type'] ?? '') === 'video' ? 'selected' : '' ?>>Video</option>
                    </select>
                </div>

                <!-- Media File -->
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Media File <span class="text-danger">*</span></label>
                    <input type="file" name="media_file" id="mediaFileInput" class="form-control" required>
                    <div id="acceptHint" class="form-text">Accepted: jpg, jpeg, png, gif, webp &mdash; max 10 MB</div>
                </div>

                <!-- Link URL -->
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Link URL</label>
                    <input type="url" name="link_url" class="form-control" placeholder="https://…"
                           value="<?= escape($old['link_url'] ?? '') ?>">
                </div>

                <!-- Display Duration -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Display Duration (seconds) <span class="text-danger">*</span></label>
                    <input type="number" name="display_duration" class="form-control" min="1" max="300"
                           value="<?= escape($old['display_duration'] ?? '10') ?>" required>
                </div>

                <!-- Dates -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Start Date &amp; Time</label>
                    <input type="datetime-local" name="start_date" class="form-control"
                           value="<?= escape($old['start_date'] ?? '') ?>">
                    <div class="form-text">Leave blank for no start restriction.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">End Date &amp; Time</label>
                    <input type="datetime-local" name="end_date" class="form-control"
                           value="<?= escape($old['end_date'] ?? '') ?>">
                    <div class="form-text">Leave blank for no end restriction.</div>
                </div>

                <!-- Status -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <option value="active"   <?= ($old['status'] ?? 'active') === 'active'   ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($old['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
            </div>

            <hr class="my-4">
            <div class="d-flex gap-2 justify-content-end">
                <a href="<?= BASE_URL ?>advertisements" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Create Advertisement</button>
            </div>
        </form>
    </div>
</div>

<script>
function updateAccept(type) {
    const input = document.getElementById('mediaFileInput');
    const hint  = document.getElementById('acceptHint');
    if (type === 'video') {
        input.accept = 'video/mp4,video/webm,video/ogg,.mp4,.webm,.ogg';
        hint.textContent = 'Accepted: mp4, webm, ogg &mdash; max 10 MB';
    } else {
        input.accept = 'image/jpeg,image/png,image/gif,image/webp,.jpg,.jpeg,.png,.gif,.webp';
        hint.textContent = 'Accepted: jpg, jpeg, png, gif, webp &mdash; max 10 MB';
    }
}
updateAccept(document.getElementById('mediaTypeSelect').value);
</script>
