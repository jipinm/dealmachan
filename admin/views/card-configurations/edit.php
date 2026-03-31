<?php /* views/card-configurations/edit.php */
$cfg      = $config;
$selSubIds        = array_column($cfg['sub_classifications'] ?? [], 'id');
$selGenderFilters = array_column($cfg['sub_classifications'] ?? [], 'gender_filter', 'id');
$selProfIdsByScId = [];
foreach ($cfg['sub_classifications'] ?? [] as $sc) {
    if (!empty($sc['profession_ids'])) {
        $selProfIdsByScId[$sc['id']] = array_map('intval', explode(',', $sc['profession_ids']));
    }
}
$selCityIds = array_column($cfg['cities'] ?? [], 'id');
$partners   = $cfg['partners'] ?? [];
$premiumPartners = array_filter($partners, fn($p) => $p['partner_type'] === 'premium');
$normalPartners  = array_filter($partners, fn($p) => $p['partner_type'] === 'normal');
?>

<!-- Page header -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-0">Edit Card Configuration</h4>
        <small class="text-muted">
            <a href="<?= BASE_URL ?>card-configurations">Card Configurations</a> /
            <a href="<?= BASE_URL ?>card-configurations/detail?id=<?= $cfg['id'] ?>"><?= escape($cfg['name']) ?></a> / Edit
        </small>
    </div>
    <a href="<?= BASE_URL ?>card-configurations/detail?id=<?= $cfg['id'] ?>" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i> Back
    </a>
</div>

<?php if (!empty($_SESSION['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3">
    <i class="fas fa-exclamation-circle me-2"></i><?= escape($_SESSION['error']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['error']); endif; ?>

<form method="POST" action="<?= BASE_URL ?>card-configurations/edit?id=<?= $cfg['id'] ?>"
      enctype="multipart/form-data" id="ccForm">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

    <div class="row g-4">

        <!-- LEFT -->
        <div class="col-lg-8">

            <!-- Basic Info -->
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">
                    <i class="fas fa-id-card me-2 text-primary"></i> Basic Information
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-7">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required maxlength="100"
                                   value="<?= escape($cfg['name']) ?>">
                        </div>
                        <div class="col-sm-5">
                            <label class="form-label">Classification <span class="text-danger">*</span></label>
                            <select name="classification" class="form-select" required>
                                <?php foreach (['silver', 'gold', 'platinum', 'diamond'] as $c): ?>
                                    <option value="<?= $c ?>" <?= $cfg['classification'] === $c ? 'selected' : '' ?>><?= ucfirst($c) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Features / Benefits</label>
                            <textarea name="features_html" class="form-control" rows="5"><?= htmlspecialchars($cfg['features_html'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                            <div class="form-text">HTML tags allowed (ul, li, b, etc.).</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pricing & Limits -->
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">
                    <i class="fas fa-sliders-h me-2 text-success"></i> Pricing &amp; Limits
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-4">
                            <label class="form-label">Price (&#x20B9;)</label>
                            <input type="number" name="price" class="form-control" min="0" step="0.01"
                                   value="<?= escape($cfg['price']) ?>">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label">Validity (days) <span class="text-danger">*</span></label>
                            <div id="validityDaysWrap">
                            <input type="number" name="validity_days" class="form-control" required min="1"
                                   value="<?= escape($cfg['validity_days']) ?>">
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label">Monthly Maximum</label>
                            <input type="number" name="monthly_maximum" class="form-control" min="1"
                                   placeholder="Unlimited"
                                   value="<?= escape($cfg['monthly_maximum'] ?? '') ?>">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label">Max Live Coupons</label>
                            <input type="number" name="max_live_coupons" class="form-control" min="1"
                                   placeholder="Unlimited"
                                   value="<?= escape($cfg['max_live_coupons'] ?? '') ?>">
                        </div>
                        <div class="col-sm-4 d-flex align-items-end">
                            <div class="form-check form-switch mb-0">
                                <input type="checkbox" name="coupon_authorization" class="form-check-input"
                                       id="couponAuth" role="switch"
                                       <?= $cfg['coupon_authorization'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="couponAuth">Coupon Authorization</label>
                            </div>
                        </div>
                        <div class="col-sm-4 d-flex align-items-end">
                            <div class="form-check form-switch mb-0">
                                <input type="checkbox" name="is_publicly_selectable" class="form-check-input"
                                       id="publicSel" role="switch"
                                       <?= $cfg['is_publicly_selectable'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="publicSel">Publicly Selectable</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card Images -->
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">
                    <i class="fas fa-images me-2 text-info"></i> Card Images
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label">Front Image</label>
                            <?php if (!empty($cfg['card_image_front'])): ?>
                            <div class="mb-2">
                                <img src="<?= imageUrl($cfg['card_image_front']) ?>"
                                     class="img-thumbnail" style="max-height:80px;" alt="Front"
                                     onerror="this.src='<?= imageUrl('') ?>'">
                            </div>
                            <?php endif; ?>
                            <input type="file" name="card_image_front" class="form-control"
                                   accept="image/jpeg,image/png,image/webp"
                                   onchange="previewImg(this,'prevFront')">
                            <div id="prevFront" class="mt-1"></div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Back Image</label>
                            <?php if (!empty($cfg['card_image_back'])): ?>
                            <div class="mb-2">
                                <img src="<?= imageUrl($cfg['card_image_back']) ?>"
                                     class="img-thumbnail" style="max-height:80px;" alt="Back"
                                     onerror="this.src='<?= imageUrl('') ?>'">
                            </div>
                            <?php endif; ?>
                            <input type="file" name="card_image_back" class="form-control"
                                   accept="image/jpeg,image/png,image/webp"
                                   onchange="previewImg(this,'prevBack')">
                            <div id="prevBack" class="mt-1"></div>
                        </div>
                    </div>
                    <div class="form-text mt-1">Upload to replace. JPG, PNG, or WebP. Max 2 MB each.</div>
                </div>
            </div>

            <!-- Premium Partners -->
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">
                    <i class="fas fa-star me-2 text-warning"></i> Premium Partners
                    <small class="text-muted fw-normal">(max 4)</small>
                </div>
                <div class="card-body">
                    <div id="premiumRows">
                    <?php foreach ($premiumPartners as $p): ?>
                        <div class="partner-row border rounded p-2 mb-2">
                            <input type="hidden" name="partner_type[]" value="premium">
                            <input type="hidden" name="partner_img[]"  value="<?= escape($p['partner_image'] ?? '') ?>">
                            <div class="row g-2 align-items-end">
                                <div class="col-sm-5">
                                    <label class="form-label form-label-sm">Image</label>
                                    <?php if (!empty($p['partner_image'])): ?>
                                    <div class="mb-1">
                                        <img src="<?= imageUrl($p['partner_image']) ?>"
                                             class="img-thumbnail" style="max-height:50px;"
                                             onerror="this.src='<?= imageUrl('') ?>'">
                                    </div>
                                    <?php endif; ?>
                                    <input type="file" name="partner_img_files[]" class="form-control form-control-sm"
                                           accept="image/jpeg,image/png,image/webp">
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label form-label-sm">URL</label>
                                    <input type="url" name="partner_url[]" class="form-control form-control-sm"
                                           value="<?= escape($p['url'] ?? '') ?>" placeholder="https://&hellip;">
                                </div>
                                <div class="col-sm-1">
                                    <button type="button" class="btn btn-outline-danger btn-sm"
                                            onclick="this.closest('.partner-row').remove()">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn btn-outline-warning btn-sm"
                            onclick="addPartnerRow('premium')">
                        <i class="fas fa-plus me-1"></i> Add Premium Partner
                    </button>
                </div>
            </div>

            <!-- Normal Partners -->
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">
                    <i class="fas fa-handshake me-2 text-secondary"></i> Normal Partners
                    <small class="text-muted fw-normal">(max 10)</small>
                </div>
                <div class="card-body">
                    <div id="normalRows">
                    <?php foreach ($normalPartners as $p): ?>
                        <div class="partner-row border rounded p-2 mb-2">
                            <input type="hidden" name="partner_type[]" value="normal">
                            <input type="hidden" name="partner_img[]"  value="<?= escape($p['partner_image'] ?? '') ?>">
                            <div class="row g-2 align-items-end">
                                <div class="col-sm-5">
                                    <label class="form-label form-label-sm">Image</label>
                                    <?php if (!empty($p['partner_image'])): ?>
                                    <div class="mb-1">
                                        <img src="<?= imageUrl($p['partner_image']) ?>"
                                             class="img-thumbnail" style="max-height:50px;"
                                             onerror="this.src='<?= imageUrl('') ?>'">
                                    </div>
                                    <?php endif; ?>
                                    <input type="file" name="partner_img_files[]" class="form-control form-control-sm"
                                           accept="image/jpeg,image/png,image/webp">
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label form-label-sm">URL</label>
                                    <input type="url" name="partner_url[]" class="form-control form-control-sm"
                                           value="<?= escape($p['url'] ?? '') ?>" placeholder="https://&hellip;">
                                </div>
                                <div class="col-sm-1">
                                    <button type="button" class="btn btn-outline-danger btn-sm"
                                            onclick="this.closest('.partner-row').remove()">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-sm"
                            onclick="addPartnerRow('normal')">
                        <i class="fas fa-plus me-1"></i> Add Normal Partner
                    </button>
                </div>
            </div>

        </div>

        <!-- RIGHT -->
        <div class="col-lg-4">

            <!-- Status -->
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">
                    <i class="fas fa-toggle-on me-2 text-dark"></i> Settings
                </div>
                <div class="card-body">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach (['active', 'inactive'] as $s): ?>
                            <option value="<?= $s ?>" <?= $cfg['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Sub-classifications -->
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">
                    <i class="fas fa-tags me-2 text-primary"></i> Sub-Classifications
                </div>
                <div class="card-body">
                    <?php foreach ($subClassifications as $sc): ?>
                    <?php
                        $isGender     = stripos($sc['name'], 'gender') !== false;
                        $isProfession = stripos($sc['name'], 'profession') !== false;
                        $isChecked    = in_array($sc['id'], $selSubIds);
                        $curGender    = $selGenderFilters[$sc['id']] ?? 'both';
                        if (!in_array($curGender, ['male', 'female', 'both'])) $curGender = 'both';
                        $curProfIds   = $selProfIdsByScId[$sc['id']] ?? [];
                    ?>
                    <div class="mb-1">
                        <div class="form-check">
                            <input type="checkbox" name="sub_class_ids[]" value="<?= $sc['id'] ?>"
                                   class="form-check-input" id="sc<?= $sc['id'] ?>"
                                   <?= $isChecked ? 'checked' : '' ?>
                                   <?php if ($isGender): ?>
                                   onchange="toggleGenderFilter(<?= $sc['id'] ?>, this.checked)"
                                   <?php elseif ($isProfession): ?>
                                   onchange="toggleProfessionFilter(<?= $sc['id'] ?>, this.checked)"
                                   <?php endif; ?>>
                            <label class="form-check-label" for="sc<?= $sc['id'] ?>"><?= escape($sc['name']) ?></label>
                        </div>
                        <?php if ($isGender): ?>
                        <div id="genderFilterWrap_<?= $sc['id'] ?>" class="ms-4 mt-1 p-2 bg-light rounded"
                             style="display:<?= $isChecked ? 'block' : 'none' ?>;">
                            <small class="text-muted d-block mb-1">Gender filter:</small>
                            <?php foreach (['both' => 'All Genders', 'male' => 'Male Only', 'female' => 'Female Only'] as $val => $label): ?>
                            <div class="form-check form-check-inline">
                                <input type="radio" name="gender_filter[<?= $sc['id'] ?>]"
                                       value="<?= $val ?>" class="form-check-input"
                                       id="gf_<?= $sc['id'] ?>_<?= $val ?>"
                                       <?= $curGender === $val ? 'checked' : '' ?>>
                                <label class="form-check-label" for="gf_<?= $sc['id'] ?>_<?= $val ?>"><?= $label ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php elseif ($isProfession): ?>
                        <div id="professionFilterWrap_<?= $sc['id'] ?>" class="ms-4 mt-1 p-2 bg-light rounded"
                             style="display:<?= $isChecked ? 'block' : 'none' ?>;">
                            <small class="text-muted d-block mb-1">Allowed professions (leave empty for all):</small>
                            <select name="profession_ids[<?= $sc['id'] ?>][]"
                                    id="profSelect_<?= $sc['id'] ?>"
                                    class="form-select form-select-sm select2"
                                    multiple data-placeholder="All Professions">
                                <?php foreach ($professions as $prof): ?>
                                <option value="<?= $prof['id'] ?>"
                                    <?= in_array($prof['id'], $curProfIds) ? 'selected' : '' ?>>
                                    <?= escape($prof['profession_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Cities -->
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">
                    <i class="fas fa-city me-2 text-info"></i> City Availability
                </div>
                <div class="card-body">
                    <p class="form-text mb-2">Leave empty for all cities.</p>
                    <select name="city_ids[]" id="citySelect" class="form-select select2" multiple
                            data-placeholder="All Cities">
                        <?php foreach ($cities as $city): ?>
                            <option value="<?= $city['id'] ?>"
                                <?= in_array($city['id'], $selCityIds) ? 'selected' : '' ?>>
                                <?= escape($city['city_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Feature Toggles -->
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">
                    <i class="fas fa-sliders-h me-2 text-purple"></i> Feature Toggles
                </div>
                <div class="card-body">
                    <!-- Pay Back Points -->
                    <div class="mb-3">
                        <div class="form-check form-switch mb-1">
                            <input type="checkbox" name="pay_back_points_enabled" class="form-check-input"
                                   id="payBackEnabled" role="switch"
                                   <?= !empty($cfg['pay_back_points_enabled']) ? 'checked' : '' ?>
                                   onchange="document.getElementById('payBackValueWrap').style.display=this.checked?'block':'none'">
                            <label class="form-check-label fw-semibold" for="payBackEnabled">Pay Back Points</label>
                        </div>
                        <div id="payBackValueWrap" style="display:<?= !empty($cfg['pay_back_points_enabled']) ? 'block' : 'none' ?>;" class="ms-4">
                            <label class="form-label form-label-sm">Points Rate (%)</label>
                            <input type="number" name="pay_back_points_value" class="form-control form-control-sm"
                                   min="0" max="100" step="0.01"
                                   value="<?= escape($cfg['pay_back_points_value'] ?? '') ?>">
                        </div>
                    </div>
                    <!-- Lifetime Subscription -->
                    <div class="form-check form-switch mb-2">
                        <input type="checkbox" name="lifetime_subscription" class="form-check-input"
                               id="lifetimeSub" role="switch"
                               <?= !empty($cfg['lifetime_subscription']) ? 'checked' : '' ?>
                               onchange="document.getElementById('validityDaysWrap').style.opacity=this.checked?'0.4':'1'">
                        <label class="form-check-label" for="lifetimeSub">Lifetime Subscription (no expiry)</label>
                    </div>
                    <!-- Gift Coupon Eligibility -->
                    <div class="form-check form-switch mb-2">
                        <input type="checkbox" name="gift_coupon_eligibility" class="form-check-input"
                               id="giftCoupon" role="switch"
                               <?= !empty($cfg['gift_coupon_eligibility']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="giftCoupon">Gift Coupon Eligibility</label>
                    </div>
                    <!-- Lucky Draw -->
                    <div class="form-check form-switch mb-2">
                        <input type="checkbox" name="lucky_draw_eligible" class="form-check-input"
                               id="luckyDraw" role="switch"
                               <?= !empty($cfg['lucky_draw_eligible']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="luckyDraw">Lucky Draw Eligible</label>
                    </div>
                    <!-- Contest -->
                    <div class="form-check form-switch mb-0">
                        <input type="checkbox" name="contest_eligible" class="form-check-input"
                               id="contestElig" role="switch"
                               <?= !empty($cfg['contest_eligible']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="contestElig">Contest Eligible</label>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Save Changes
                </button>
                <a href="<?= BASE_URL ?>card-configurations/detail?id=<?= $cfg['id'] ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>

        </div>
    </div>
</form>

<script>
function previewImg(input, targetId) {
    const el = document.getElementById(targetId);
    if (input.files && input.files[0]) {
        el.innerHTML = '<img src="' + URL.createObjectURL(input.files[0]) +
                       '" class="img-thumbnail" style="max-height:120px;">';
    } else {
        el.innerHTML = '';
    }
}

function toggleGenderFilter(scId, show) {
    const wrap = document.getElementById('genderFilterWrap_' + scId);
    if (wrap) wrap.style.display = show ? 'block' : 'none';
}

function toggleProfessionFilter(scId, show) {
    const wrap = document.getElementById('professionFilterWrap_' + scId);
    if (wrap) wrap.style.display = show ? 'block' : 'none';
}

function addPartnerRow(type) {
    const containerId = type === 'premium' ? 'premiumRows' : 'normalRows';
    const container   = document.getElementById(containerId);
    const max         = type === 'premium' ? 4 : 10;
    if (container.querySelectorAll('.partner-row').length >= max) {
        alert('Maximum ' + max + ' ' + type + ' partners allowed.');
        return;
    }
    const row = document.createElement('div');
    row.className = 'partner-row border rounded p-2 mb-2';
    row.innerHTML = `
        <input type="hidden" name="partner_type[]" value="${type}">
        <input type="hidden" name="partner_img[]"  value="">
        <div class="row g-2 align-items-end">
            <div class="col-sm-5">
                <label class="form-label form-label-sm">Image</label>
                <input type="file" name="partner_img_files[]" class="form-control form-control-sm"
                       accept="image/jpeg,image/png,image/webp">
            </div>
            <div class="col-sm-6">
                <label class="form-label form-label-sm">URL</label>
                <input type="url" name="partner_url[]" class="form-control form-control-sm"
                       placeholder="https://&hellip;">
            </div>
            <div class="col-sm-1">
                <button type="button" class="btn btn-outline-danger btn-sm"
                        onclick="this.closest('.partner-row').remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>`;
    container.appendChild(row);
}

document.addEventListener('DOMContentLoaded', function () {
    if (window.$) {
        $('#citySelect').select2({ theme: 'bootstrap-5', placeholder: 'All Cities', allowClear: true });
    }
});
</script>
