<?php /* views/gift-coupons/add.php */ ?>

<?php
$months = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
];

$oldClubIds       = array_map('intval', (array)($old['club_ids'] ?? []));
$oldProfessionIds = array_map('intval', (array)($old['profession_ids'] ?? []));
$oldCityId        = (int)($old['city_id'] ?? 0);
$oldAreaId        = (int)($old['area_id'] ?? 0);
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-0">Gift Coupon Bulk Creation</h4>
        <small class="text-muted">Filter recipients, preview audience, and send gift coupons in a single batch</small>
    </div>
    <a href="<?= BASE_URL ?>gift-coupons" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Back
    </a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger shadow-sm border-0">
    <ul class="mb-0 ps-3">
        <?php foreach ($errors as $e): ?>
        <li><?= escape($e) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<form method="POST" action="<?= BASE_URL ?>gift-coupons/save" id="giftBatchForm">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

    <div class="row g-4">
        <div class="col-lg-8">

            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">
                    <span class="badge bg-primary me-2">Step 1</span> Choose Coupon
                </div>
                <div class="card-body">
                    <label class="form-label fw-semibold">Coupon <span class="text-danger">*</span></label>
                    <select name="coupon_id" class="form-select select2" required>
                        <option value="">-- Select active approved coupon --</option>
                        <?php foreach ($coupons as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= ((int)($old['coupon_id'] ?? 0) === (int)$c['id']) ? 'selected' : '' ?>>
                            <?= escape($c['title']) ?> (<?= escape($c['coupon_code']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">
                    <span class="badge bg-primary me-2">Step 2</span> Recipient Filters (AND logic)
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Card Segment</label>
                            <select name="card_segment" class="form-select">
                                <option value="">All Segments</option>
                                <?php foreach (['silver' => 'Silver', 'gold' => 'Gold', 'platinum' => 'Platinum', 'diamond' => 'Diamond'] as $key => $label): ?>
                                <option value="<?= $key ?>" <?= (($old['card_segment'] ?? '') === $key) ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Club</label>
                            <select name="club_ids[]" class="form-select select2" multiple data-placeholder="All Clubs">
                                <?php foreach ($clubs as $club): ?>
                                <option value="<?= (int)$club['id'] ?>" <?= in_array((int)$club['id'], $oldClubIds, true) ? 'selected' : '' ?>>
                                    <?= escape($club['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Profession</label>
                            <select name="profession_ids[]" class="form-select select2" multiple data-placeholder="All Professions">
                                <?php foreach ($professions as $profession): ?>
                                <option value="<?= (int)$profession['id'] ?>" <?= in_array((int)$profession['id'], $oldProfessionIds, true) ? 'selected' : '' ?>>
                                    <?= escape($profession['profession_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Birth Month / Important Days</label>
                            <select name="birth_month" class="form-select">
                                <option value="">All Months</option>
                                <?php foreach ($months as $num => $name): ?>
                                <option value="<?= $num ?>" <?= ((int)($old['birth_month'] ?? 0) === $num) ? 'selected' : '' ?>><?= $name ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">City</label>
                            <select name="city_id" id="cityFilter" class="form-select" onchange="syncAreasByCity()">
                                <option value="">All Cities</option>
                                <?php foreach ($cities as $city): ?>
                                <option value="<?= (int)$city['id'] ?>" <?= ($oldCityId === (int)$city['id']) ? 'selected' : '' ?>>
                                    <?= escape($city['city_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Area</label>
                            <select name="area_id" id="areaFilter" class="form-select">
                                <option value="">All Areas</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select">
                                <option value="both" <?= (($old['gender'] ?? 'both') === 'both') ? 'selected' : '' ?>>Both</option>
                                <option value="male" <?= (($old['gender'] ?? '') === 'male') ? 'selected' : '' ?>>Male</option>
                                <option value="female" <?= (($old['gender'] ?? '') === 'female') ? 'selected' : '' ?>>Female</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
                    <div><span class="badge bg-primary me-2">Step 3</span> Preview Recipients</div>
                    <button type="button" id="previewRecipientsBtn" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-search me-1"></i> Preview Recipients
                    </button>
                </div>
                <div class="card-body">
                    <div id="previewEmpty" class="text-muted small">
                        Click "Preview Recipients" to view matched customer count and a sample list.
                    </div>

                    <div id="previewResult" class="d-none">
                        <div class="alert alert-info border-0 mb-3" id="previewCountBox"></div>
                        <div class="table-responsive" id="previewSampleWrap"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">
                    <span class="badge bg-primary me-2">Step 4</span> Configure and Send
                </div>
                <div class="card-body">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="requires_acceptance" id="requiresAcceptance" value="1"
                               <?= !empty($old['requires_acceptance']) ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="requiresAcceptance">
                            Customer must accept gift coupon
                        </label>
                    </div>
                    <div class="text-muted small mb-3">
                        If enabled, customers see Accept/Reject. If disabled, coupons are automatically active in wallet.
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-gift me-1"></i> Confirm and Send
                        </button>
                        <a href="<?= BASE_URL ?>gift-coupons" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </div>
            </div>

            <div class="card border-info-subtle bg-info-subtle shadow-sm">
                <div class="card-body py-3 px-3 small text-muted">
                    All recipient filters are optional and combined with AND logic. Leaving all filters empty will target all active customers.
                </div>
            </div>
        </div>
    </div>
</form>

<script>
const areas = <?= json_encode($areas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const oldAreaId = <?= (int)$oldAreaId ?>;

function syncAreasByCity() {
    const cityId = String(document.getElementById('cityFilter').value || '');
    const areaEl = document.getElementById('areaFilter');

    while (areaEl.firstChild) {
        areaEl.removeChild(areaEl.firstChild);
    }

    const allOption = document.createElement('option');
    allOption.value = '';
    allOption.textContent = 'All Areas';
    areaEl.appendChild(allOption);

    areas.forEach(function(a) {
        const areaCity = String(a.city_id || '');
        if (!cityId || areaCity === cityId) {
            const opt = document.createElement('option');
            opt.value = String(a.id);
            opt.textContent = a.area_name;
            if (oldAreaId > 0 && Number(a.id) === Number(oldAreaId)) {
                opt.selected = true;
            }
            areaEl.appendChild(opt);
        }
    });
}

function renderPreviewTable(rows) {
    if (!rows || !rows.length) {
        return '<div class="text-muted small">No sample recipients to show.</div>';
    }

    let html = '';
    html += '<table class="table table-sm table-striped mb-0">';
    html += '<thead><tr><th>Name</th><th>Email</th><th>Phone</th></tr></thead><tbody>';

    rows.forEach(function(r) {
        const name  = escapeHtml(r.name || '');
        const email = escapeHtml(r.email || '--');
        const phone = escapeHtml(r.phone || '--');

        html += '<tr>';
        html += '<td>' + name + '</td>';
        html += '<td>' + email + '</td>';
        html += '<td>' + phone + '</td>';
        html += '</tr>';
    });

    html += '</tbody></table>';
    return html;
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/\"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

async function previewRecipients() {
    const form = document.getElementById('giftBatchForm');
    const btn  = document.getElementById('previewRecipientsBtn');
    const fd   = new FormData(form);

    btn.disabled = true;
    const oldHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Previewing...';

    try {
        const resp = await fetch('<?= BASE_URL ?>gift-coupons/preview', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: new URLSearchParams(fd),
        });
        const data = await resp.json();

        if (!resp.ok || !data.success) {
            throw new Error(data.error || 'Preview failed.');
        }

        document.getElementById('previewEmpty').classList.add('d-none');
        document.getElementById('previewResult').classList.remove('d-none');
        document.getElementById('previewCountBox').innerHTML =
            '<strong>' + Number(data.count || 0).toLocaleString() + '</strong> recipients matched the selected filters.';
        document.getElementById('previewSampleWrap').innerHTML = renderPreviewTable(data.sample || []);
    } catch (e) {
        alert(e && e.message ? e.message : 'Unable to preview recipients right now.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = oldHtml;
    }
}

document.getElementById('previewRecipientsBtn').addEventListener('click', previewRecipients);

document.addEventListener('DOMContentLoaded', function() {
    syncAreasByCity();
    if (typeof $.fn.select2 !== 'undefined') {
        $('.select2').select2({ theme: 'bootstrap-5', width: '100%' });
    }
});
</script>
