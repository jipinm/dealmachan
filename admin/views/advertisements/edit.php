<?php /* views/advertisements/edit.php */ ?>

<div class="d-flex align-items-center mb-4">
    <a href="<?= BASE_URL ?>advertisements/detail?id=<?= $ad['id'] ?>" class="btn btn-sm btn-outline-secondary me-3">
        <i class="fas fa-arrow-left me-1"></i> Back
    </a>
    <h4 class="mb-0">Edit Advertisement</h4>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <ul class="mb-0 ps-3">
        <?php foreach ($errors as $e): ?><li><?= escape($e) ?></li><?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<?php $d = $old ?? $ad; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="<?= BASE_URL ?>advertisements/save" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="id" value="<?= $ad['id'] ?>">

            <div class="row g-3">
                <!-- Title -->
                <div class="col-12">
                    <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control"
                           value="<?= escape($d['title']) ?>" required maxlength="255">
                </div>

                <!-- Media Type (read-only on edit) -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Media Type</label>
                    <input type="text" class="form-control" value="<?= ucfirst($ad['media_type']) ?>" readonly>
                    <input type="hidden" name="media_type" value="<?= escape($ad['media_type']) ?>">
                </div>

                <!-- Current Media Preview -->
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Current Media</label>
                    <div class="border rounded p-2 bg-light" style="max-height:120px;overflow:hidden;">
                        <?php if ($ad['media_type'] === 'image'): ?>
                            <img src="<?= BASE_URL ?>public/<?= escape($ad['media_url']) ?>"
                                 style="max-height:100px;max-width:100%;object-fit:contain;"
                                 alt="current image" onerror="this.parentNode.innerHTML='<span class=text-muted>Preview unavailable</span>'">
                        <?php else: ?>
                            <span class="text-muted"><i class="fas fa-video me-1"></i><?= escape($ad['media_url']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Replace Media File -->
                <div class="col-12">
                    <label class="form-label fw-semibold">Replace Media File <span class="text-muted fw-normal">(optional)</span></label>
                    <input type="file" name="media_file" id="mediaFileInput" class="form-control">
                    <div id="acceptHint" class="form-text"></div>
                </div>

                <!-- Link URL -->
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Link URL</label>
                    <input type="url" name="link_url" class="form-control" placeholder="https://…"
                           value="<?= escape($d['link_url'] ?? '') ?>">
                </div>

                <!-- Display Duration -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Display Duration (seconds) <span class="text-danger">*</span></label>
                    <input type="number" name="display_duration" class="form-control" min="1" max="300"
                           value="<?= escape($d['display_duration']) ?>" required>
                </div>

                <!-- Dates -->
                <?php
                $startVal = '';
                if (!empty($d['start_date'])) {
                    $ts = strtotime($d['start_date']);
                    $startVal = $ts ? date('Y-m-d\TH:i', $ts) : '';
                }
                $endVal = '';
                if (!empty($d['end_date'])) {
                    $ts = strtotime($d['end_date']);
                    $endVal = $ts ? date('Y-m-d\TH:i', $ts) : '';
                }
                ?>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Start Date &amp; Time</label>
                    <input type="datetime-local" name="start_date" class="form-control" value="<?= escape($startVal) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">End Date &amp; Time</label>
                    <input type="datetime-local" name="end_date" class="form-control" value="<?= escape($endVal) ?>">
                </div>

                <!-- Status -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <option value="active"   <?= $d['status'] === 'active'   ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $d['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
            </div>

            <hr class="my-4">
            <div class="d-flex gap-2 justify-content-end">
                <a href="<?= BASE_URL ?>advertisements/detail?id=<?= $ad['id'] ?>" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-warning"><i class="fas fa-save me-1"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    const type = <?= json_encode($ad['media_type']) ?>;
    const hint = document.getElementById('acceptHint');
    if (type === 'video') {
        document.getElementById('mediaFileInput').accept = 'video/mp4,video/webm,video/ogg';
        hint.textContent = 'Accepted: mp4, webm, ogg — max 10 MB';
    } else {
        document.getElementById('mediaFileInput').accept = 'image/jpeg,image/png,image/gif,image/webp';
        hint.textContent = 'Accepted: jpg, jpeg, png, gif, webp — max 10 MB';
    }
})();
</script>
