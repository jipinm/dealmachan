<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-0">Generate Cards</h4>
        <small class="text-muted"><a href="<?= BASE_URL ?>/cards">Cards</a> / Generate</small>
    </div>
    <a href="<?= BASE_URL ?>/cards" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4" id="generateTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="single-tab" data-bs-toggle="tab" data-bs-target="#singlePane" type="button">
            <i class="fas fa-credit-card me-2"></i> Single Card
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="bulk-tab" data-bs-toggle="tab" data-bs-target="#bulkPane" type="button">
            <i class="fas fa-layer-group me-2"></i> Bulk Generate
        </button>
    </li>
</ul>

<div class="tab-content">
    <!-- Single -->
    <div class="tab-pane fade show active" id="singlePane" role="tabpanel">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-header fw-semibold"><i class="fas fa-credit-card me-2 text-primary"></i>Single Card</div>
                    <div class="card-body">
                        <form method="POST" action="<?= BASE_URL ?>/cards/generate" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            <input type="hidden" name="generate_type" value="single">

                            <div class="mb-3">
                                <label class="form-label">Card Number</label>
                                <div class="input-group">
                                    <input type="text" name="card_number" id="single_card_number"
                                           class="form-control text-uppercase" maxlength="50"
                                           placeholder="Leave blank to auto-generate">
                                    <button type="button" class="btn btn-outline-secondary" onclick="generateNumber('single_card_number')">
                                        <i class="fas fa-random me-1"></i> Generate
                                    </button>
                                </div>
                                <div class="form-text">Only letters, numbers, hyphens. Must be unique.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Card Configuration</label>
                                <select name="config_id" id="single_config" class="form-select"
                                        onchange="updatePreview(this,'single')">
                                    <option value="">&mdash; No configuration (use variant below) &mdash;</option>
                                    <?php foreach ($configurations as $cfg): ?>
                                    <option value="<?= $cfg['id'] ?>"
                                            data-classification="<?= escape($cfg['classification']) ?>"
                                            data-validity="<?= $cfg['validity_days'] ?>"
                                            data-price="<?= $cfg['price'] ?>"
                                            data-image-front="<?= escape($cfg['card_image_front'] ?? '') ?>"
                                            data-image-back="<?= escape($cfg['card_image_back'] ?? '') ?>">
                                        <?= escape($cfg['name']) ?> &mdash; <?= ucfirst($cfg['classification']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <div id="single_preview" class="mt-2 d-none p-2 border rounded bg-light small">
                                    <span class="fw-semibold">Classification:</span> <span id="single_cls"></span>
                                    &nbsp;|&nbsp;
                                    <span class="fw-semibold">Validity:</span> <span id="single_val"></span> days
                                    &nbsp;|&nbsp;
                                    <span class="fw-semibold">Price:</span> <span id="single_prc"></span>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Card Variant (fallback) <span class="text-danger">*</span></label>
                                <select name="card_variant" class="form-select" required>
                                    <?php foreach ($variants as $v): ?>
                                        <option value="<?= $v ?>"><?= ucfirst($v) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Used only when no configuration is selected.</div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="is_preprinted" class="form-check-input" id="single_preprinted" role="switch"
                                           onchange="togglePreprintedImages('single', this.checked)">
                                    <label class="form-check-label" for="single_preprinted">Preprinted Card</label>
                                </div>
                            </div>

                            <!-- Card Images (shown when preprinted) -->
                            <div id="single_card_images" class="d-none mb-3 p-3 border rounded bg-light">
                                <p class="fw-semibold mb-2 small"><i class="fas fa-image me-1 text-primary"></i>Card Images</p>
                                <div id="single_cfg_images" class="d-none mb-3">
                                    <p class="small text-muted mb-1">Configuration images (from selected config):</p>
                                    <div class="d-flex gap-3">
                                        <div id="single_cfg_front_wrap" class="d-none text-center">
                                            <img id="single_cfg_front_img" src="" class="img-thumbnail" style="max-height:80px;" alt="Config Front">
                                            <div class="small text-muted mt-1">Front</div>
                                        </div>
                                        <div id="single_cfg_back_wrap" class="d-none text-center">
                                            <img id="single_cfg_back_img" src="" class="img-thumbnail" style="max-height:80px;" alt="Config Back">
                                            <div class="small text-muted mt-1">Back</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row g-2">
                                    <div class="col-sm-6">
                                        <label class="form-label small">Upload Front Image</label>
                                        <input type="file" name="card_image_front" id="single_img_front"
                                               class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp">
                                        <div class="form-text">JPG / PNG / WebP, max 2 MB.</div>
                                        <img id="single_preview_front" src="" class="img-thumbnail mt-2 d-none" style="max-height:80px;" alt="Front Preview">
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label small">Upload Back Image</label>
                                        <input type="file" name="card_image_back" id="single_img_back"
                                               class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp">
                                        <div class="form-text">JPG / PNG / WebP, max 2 MB.</div>
                                        <img id="single_preview_back" src="" class="img-thumbnail mt-2 d-none" style="max-height:80px;" alt="Back Preview">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Parameters (JSON)</label>
                                <textarea name="parameters_json" class="form-control font-monospace" rows="3"
                                          placeholder='{"tier": "gold", "points": 0}'></textarea>
                                <div class="form-text">Optional custom parameters in JSON format.</div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-plus me-2"></i> Generate Card
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk -->
    <div class="tab-pane fade" id="bulkPane" role="tabpanel">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-header fw-semibold"><i class="fas fa-layer-group me-2 text-success"></i>Bulk Generation</div>
                    <div class="card-body">
                        <div class="alert alert-info small mb-3">
                            <i class="fas fa-info-circle me-1"></i>
                            Card numbers will be auto-generated in the format <code>DM[VAR][8 chars]</code>.
                            Maximum 200 cards per batch.
                        </div>
                        <form method="POST" action="<?= BASE_URL ?>/cards/generate">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            <input type="hidden" name="generate_type" value="bulk">

                            <div class="mb-3">
                                <label class="form-label">Number of Cards <span class="text-danger">*</span></label>
                                <input type="number" name="bulk_count" class="form-control"
                                       min="1" max="200" value="10" required>
                                <div class="form-text">1&ndash;200 cards per generation.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Card Configuration</label>
                                <select name="config_id" id="bulk_config" class="form-select"
                                        onchange="updatePreview(this,'bulk')">
                                    <option value="">&mdash; No configuration (use variant below) &mdash;</option>
                                    <?php foreach ($configurations as $cfg): ?>
                                    <option value="<?= $cfg['id'] ?>"
                                            data-classification="<?= escape($cfg['classification']) ?>"
                                            data-validity="<?= $cfg['validity_days'] ?>"
                                            data-price="<?= $cfg['price'] ?>"
                                            data-image-front="<?= escape($cfg['card_image_front'] ?? '') ?>"
                                            data-image-back="<?= escape($cfg['card_image_back'] ?? '') ?>">
                                        <?= escape($cfg['name']) ?> &mdash; <?= ucfirst($cfg['classification']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <div id="bulk_preview" class="mt-2 d-none p-2 border rounded bg-light small">
                                    <span class="fw-semibold">Classification:</span> <span id="bulk_cls"></span>
                                    &nbsp;|&nbsp;
                                    <span class="fw-semibold">Validity:</span> <span id="bulk_val"></span> days
                                    &nbsp;|&nbsp;
                                    <span class="fw-semibold">Price:</span> <span id="bulk_prc"></span>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Card Variant (fallback) <span class="text-danger">*</span></label>
                                <select name="card_variant" class="form-select" required>
                                    <?php foreach ($variants as $v): ?>
                                        <option value="<?= $v ?>"><?= ucfirst($v) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Used only when no configuration is selected.</div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="is_preprinted" class="form-check-input" id="bulk_preprinted" role="switch"
                                           onchange="toggleBulkPreprintedNote(this.checked)">
                                    <label class="form-check-label" for="bulk_preprinted">Preprinted Cards</label>
                                </div>
                            </div>

                            <!-- Bulk preprinted note -->
                            <div id="bulk_preprinted_note" class="d-none mb-3">
                                <div class="alert alert-warning small mb-0">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Bulk-generated pre-printed cards are created without images. You can upload front &amp; back images for each card individually from <strong>Cards &rarr; View / Edit card</strong> after generation.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Parameters (JSON)</label>
                                <textarea name="parameters_json" class="form-control font-monospace" rows="3"
                                          placeholder='{"tier": "standard", "points": 0}'></textarea>
                                <div class="form-text">Applied to all generated cards.</div>
                            </div>

                            <button type="submit" class="btn btn-success w-100">
                                <i class="fas fa-layer-group me-2"></i> Generate Cards
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function generateNumber(fieldId) {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let num = 'DM';
    for (let i = 0; i < 10; i++) num += chars[Math.floor(Math.random() * chars.length)];
    document.getElementById(fieldId).value = num;
}

function updatePreview(sel, prefix) {
    const preview = document.getElementById(prefix + '_preview');
    const opt = sel.options[sel.selectedIndex];
    if (!sel.value) {
        preview.classList.add('d-none');
        if (prefix === 'single') updateCfgImagePreview(null, null);
        return;
    }
    document.getElementById(prefix + '_cls').textContent = opt.dataset.classification;
    document.getElementById(prefix + '_val').textContent = opt.dataset.validity;
    const price = parseFloat(opt.dataset.price);
    document.getElementById(prefix + '_prc').textContent = price > 0 ? '\u20b9' + price.toFixed(2) : 'Free';
    preview.classList.remove('d-none');

    // If config has images, auto-reveal preprinted section
    if (prefix === 'single') {
        const front = opt.dataset.imageFront || '';
        const back  = opt.dataset.imageBack  || '';
        updateCfgImagePreview(front, back);
        if (front || back) {
            document.getElementById('single_preprinted').checked = true;
            togglePreprintedImages('single', true);
        }
    }
}

function updateCfgImagePreview(front, back) {
    const wrap   = document.getElementById('single_cfg_images');
    const fWrap  = document.getElementById('single_cfg_front_wrap');
    const bWrap  = document.getElementById('single_cfg_back_wrap');
    const fImg   = document.getElementById('single_cfg_front_img');
    const bImg   = document.getElementById('single_cfg_back_img');
    if (!wrap) return;
    const base   = '<?= rtrim(BASE_URL, "/") ?>/';
    if (front || back) {
        wrap.classList.remove('d-none');
        if (front) { fImg.src = base + front; fWrap.classList.remove('d-none'); }
        else       { fWrap.classList.add('d-none'); }
        if (back)  { bImg.src = base + back;  bWrap.classList.remove('d-none'); }
        else       { bWrap.classList.add('d-none'); }
    } else {
        wrap.classList.add('d-none');
    }
}

function togglePreprintedImages(prefix, show) {
    const section = document.getElementById(prefix + '_card_images');
    if (!section) return;
    if (show) section.classList.remove('d-none');
    else      section.classList.add('d-none');
}

function toggleBulkPreprintedNote(show) {
    const note = document.getElementById('bulk_preprinted_note');
    if (!note) return;
    if (show) note.classList.remove('d-none');
    else      note.classList.add('d-none');
}

// Live file-input preview
function wireImagePreview(inputId, previewId) {
    const inp = document.getElementById(inputId);
    const prv = document.getElementById(previewId);
    if (!inp || !prv) return;
    inp.addEventListener('change', function () {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = e => { prv.src = e.target.result; prv.classList.remove('d-none'); };
            reader.readAsDataURL(this.files[0]);
        } else {
            prv.src = ''; prv.classList.add('d-none');
        }
    });
}

document.addEventListener('DOMContentLoaded', function () {
    // Preserve active tab on page reload after error
    const activeTab = sessionStorage.getItem('activeGenerateTab');
    if (activeTab) {
        const tab = document.querySelector(`[data-bs-target="${activeTab}"]`);
        if (tab) bootstrap.Tab.getOrCreateInstance(tab).show();
    }
    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(btn => {
        btn.addEventListener('shown.bs.tab', function () {
            sessionStorage.setItem('activeGenerateTab', btn.dataset.bsTarget);
        });
    });

    // Auto-uppercase card number
    const numInput = document.getElementById('single_card_number');
    if (numInput) {
        numInput.addEventListener('input', function () {
            const pos = this.selectionStart;
            this.value = this.value.toUpperCase();
            this.setSelectionRange(pos, pos);
        });
    }

    // Wire live image previews
    wireImagePreview('single_img_front', 'single_preview_front');
    wireImagePreview('single_img_back',  'single_preview_back');
});
</script>
