<?php /* views/merchants/add-store.php */ ?>

<?php if ($flash_error): ?><div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm"><i class="fas fa-exclamation-circle me-2"></i><?= escape($flash_error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>merchants">Merchants</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>merchants/profile?id=<?= $merchant['id'] ?>"><?= escape($merchant['business_name']) ?></a></li>
        <li class="breadcrumb-item active">Add Store</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Add Store</h1>
        <p class="text-muted mb-0 small">New store for <strong><?= escape($merchant['business_name']) ?></strong></p>
    </div>
    <a href="<?= BASE_URL ?>merchants/profile?id=<?= $merchant['id'] ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form method="POST" action="<?= BASE_URL ?>merchants/add-store?merchant_id=<?= $merchant['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="merchant_id" value="<?= $merchant['id'] ?>">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Store Name <span class="text-danger">*</span></label>
                        <input type="text" name="store_name" class="form-control" required maxlength="255"
                               value="<?= escape($_POST['store_name'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Address <span class="text-danger">*</span></label>
                        <textarea name="address" class="form-control" rows="2" required><?= escape($_POST['address'] ?? '') ?></textarea>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">City <span class="text-danger">*</span></label>
                            <select name="city_id" id="citySelect" class="form-select" required onchange="loadAreas(this.value)">
                                <option value="">Select city…</option>
                                <?php foreach ($cities as $city): ?>
                                <option value="<?= $city['id'] ?>" <?= ($_POST['city_id'] ?? '') == $city['id'] ? 'selected' : '' ?>>
                                    <?= escape($city['city_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Area <span class="text-danger">*</span></label>
                            <select name="area_id" id="areaSelect" class="form-select" required>
                                <option value="">Select city first…</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="phone" class="form-control" maxlength="20"
                                   value="<?= escape($_POST['phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" maxlength="255"
                                   value="<?= escape($_POST['email'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2"><?= escape($_POST['description'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active"   <?= ($_POST['status'] ?? 'active') === 'active'   ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= ($_POST['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i> Add Store</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function loadAreas(cityId) {
    const sel = document.getElementById('areaSelect');
    sel.innerHTML = '<option value="">Loading…</option>';
    if (!cityId) { sel.innerHTML = '<option value="">Select city first…</option>'; return; }
    fetch(`<?= BASE_URL ?>master-data/areas-json?city_id=${cityId}`)
        .then(r => r.json())
        .then(data => {
            sel.innerHTML = '<option value="">Select area…</option>';
            data.forEach(a => sel.insertAdjacentHTML('beforeend', `<option value="${a.id}">${a.area_name}</option>`));
        })
        .catch(() => { sel.innerHTML = '<option value="">Error loading areas</option>'; });
}
</script>
