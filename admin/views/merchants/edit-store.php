<?php /* views/merchants/edit-store.php */ ?>

<?php if ($flash_error): ?><div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm"><i class="fas fa-exclamation-circle me-2"></i><?= escape($flash_error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>merchants">Merchants</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>merchants/profile?id=<?= $store['merchant_id'] ?>"><?= escape($store['business_name']) ?></a></li>
        <li class="breadcrumb-item active">Edit Store</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Edit Store</h1>
        <p class="text-muted mb-0 small"><?= escape($store['store_name']) ?> — <?= escape($store['business_name']) ?></p>
    </div>
    <a href="<?= BASE_URL ?>merchants/profile?id=<?= $store['merchant_id'] ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form method="POST" action="<?= BASE_URL ?>merchants/edit-store?id=<?= $store['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Store Name <span class="text-danger">*</span></label>
                        <input type="text" name="store_name" class="form-control" required maxlength="255"
                               value="<?= escape($_POST['store_name'] ?? $store['store_name']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Address <span class="text-danger">*</span></label>
                        <textarea name="address" class="form-control" rows="2" required><?= escape($_POST['address'] ?? $store['address']) ?></textarea>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">City <span class="text-danger">*</span></label>
                            <select name="city_id" id="citySelect" class="form-select" required onchange="loadAreas(this.value, null)">
                                <option value="">Select city…</option>
                                <?php foreach ($cities as $city): ?>
                                <option value="<?= $city['id'] ?>" <?= ($store['city_id'] == $city['id']) ? 'selected' : '' ?>>
                                    <?= escape($city['city_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Area <span class="text-danger">*</span></label>
                            <select name="area_id" id="areaSelect" class="form-select" required>
                                <?php foreach ($areas as $area): ?>
                                <option value="<?= $area['id'] ?>" <?= ($store['area_id'] == $area['id']) ? 'selected' : '' ?>>
                                    <?= escape($area['area_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="phone" class="form-control" maxlength="20"
                                   value="<?= escape($_POST['phone'] ?? $store['phone']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" maxlength="255"
                                   value="<?= escape($_POST['email'] ?? $store['email']) ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2"><?= escape($_POST['description'] ?? $store['description']) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active"   <?= ($store['status'] === 'active')   ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= ($store['status'] === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>

                    <!-- Opening Hours -->
                    <?php
                        $openingHours = [];
                        if (!empty($store['opening_hours'])) {
                            $openingHours = is_string($store['opening_hours']) ? json_decode($store['opening_hours'], true) : $store['opening_hours'];
                        }
                        if (!is_array($openingHours)) $openingHours = [];
                        $days = ['monday'=>'Monday','tuesday'=>'Tuesday','wednesday'=>'Wednesday','thursday'=>'Thursday','friday'=>'Friday','saturday'=>'Saturday','sunday'=>'Sunday'];
                    ?>
                    <div class="mb-4">
                        <label class="form-label fw-semibold"><i class="fas fa-clock me-1 text-muted"></i> Opening Hours</label>
                        <div class="border rounded p-3">
                            <?php foreach ($days as $dayKey => $dayLabel): ?>
                            <?php
                                $dayData = $openingHours[$dayKey] ?? ['open'=>'09:00','close'=>'21:00','closed'=>false];
                                $isClosed = !empty($dayData['closed']);
                            ?>
                            <div class="row g-2 align-items-center mb-2">
                                <div class="col-3 col-md-2">
                                    <label class="form-label mb-0 small fw-semibold"><?= $dayLabel ?></label>
                                </div>
                                <div class="col-3 col-md-3">
                                    <input type="time" name="hours[<?= $dayKey ?>][open]" class="form-control form-control-sm"
                                           value="<?= escape($dayData['open'] ?? '09:00') ?>" <?= $isClosed ? 'disabled' : '' ?>>
                                </div>
                                <div class="col-3 col-md-3">
                                    <input type="time" name="hours[<?= $dayKey ?>][close]" class="form-control form-control-sm"
                                           value="<?= escape($dayData['close'] ?? '21:00') ?>" <?= $isClosed ? 'disabled' : '' ?>>
                                </div>
                                <div class="col-3 col-md-4">
                                    <div class="form-check">
                                        <input type="checkbox" name="hours[<?= $dayKey ?>][closed]" value="1"
                                               class="form-check-input" id="closed_<?= $dayKey ?>"
                                               <?= $isClosed ? 'checked' : '' ?>
                                               onchange="toggleDay('<?= $dayKey ?>', this.checked)">
                                        <label class="form-check-label small" for="closed_<?= $dayKey ?>">Closed</label>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i> Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function toggleDay(dayKey, isClosed) {
    const openInput = document.querySelector(`input[name="hours[${dayKey}][open]"]`);
    const closeInput = document.querySelector(`input[name="hours[${dayKey}][close]"]`);
    if (openInput) openInput.disabled = isClosed;
    if (closeInput) closeInput.disabled = isClosed;
}
function loadAreas(cityId, selectedId) {
    const sel = document.getElementById('areaSelect');
    if (!cityId) return;
    fetch(`<?= BASE_URL ?>master-data/areas-json?city_id=${cityId}`)
        .then(r => r.json())
        .then(data => {
            sel.innerHTML = '<option value="">Select area…</option>';
            data.forEach(a => {
                const opt = document.createElement('option');
                opt.value = a.id;
                opt.textContent = a.area_name;
                if (selectedId && a.id == selectedId) opt.selected = true;
                sel.appendChild(opt);
            });
        });
}
</script>
