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
                        <form method="POST" action="<?= BASE_URL ?>/cards/generate">
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
                                <label class="form-label">Card Variant <span class="text-danger">*</span></label>
                                <select name="card_variant" class="form-select" required>
                                    <?php foreach ($variants as $v): ?>
                                        <option value="<?= $v ?>"><?= ucfirst($v) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="is_preprinted" class="form-check-input" id="single_preprinted" role="switch">
                                    <label class="form-check-label" for="single_preprinted">Preprinted Card</label>
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
                                <div class="form-text">1–200 cards per generation.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Card Variant <span class="text-danger">*</span></label>
                                <select name="card_variant" class="form-select" required>
                                    <?php foreach ($variants as $v): ?>
                                        <option value="<?= $v ?>"><?= ucfirst($v) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="is_preprinted" class="form-check-input" id="bulk_preprinted" role="switch">
                                    <label class="form-check-label" for="bulk_preprinted">Preprinted Cards</label>
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
});
</script>
