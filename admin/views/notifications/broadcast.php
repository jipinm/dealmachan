<?php $pageTitle = 'Send Notification'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0">Send Notification</h5>
    <a href="<?= BASE_URL ?>notifications" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Notifications
    </a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-megaphone me-2 text-primary"></i>Broadcast Notification
            </div>
            <div class="card-body">
                <form method="POST" action="<?= BASE_URL ?>notifications/broadcast">

                    <!-- Title -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control"
                               placeholder="Notification title"
                               value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
                    </div>

                    <!-- Type -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
                        <div class="d-flex gap-3 flex-wrap">
                            <?php
                            $typeConfig = [
                                'info'           => ['text-primary',  'bi-info-circle',          'Information'],
                                'success'        => ['text-success',  'bi-check-circle',         'Success'],
                                'warning'        => ['text-warning',  'bi-exclamation-triangle', 'Warning'],
                                'error'          => ['text-danger',   'bi-x-circle',             'Error'],
                                'promotion'      => ['text-purple',   'bi-tag',                  'Promotion'],
                                'coupon'         => ['text-info',     'bi-ticket-perforated',    'Coupon'],
                                'flash_discount' => ['text-orange',   'bi-lightning',            'Flash Discount'],
                                'contest'        => ['text-indigo',   'bi-trophy',               'Contest'],
                            ];
                            foreach ($typeConfig as $val => [$cls, $icon, $label]):
                            ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="notification_type"
                                       id="nt_<?= $val ?>" value="<?= $val ?>"
                                       <?= (($_POST['notification_type'] ?? 'info') === $val) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="nt_<?= $val ?>">
                                    <i class="bi <?= $icon ?> me-1"></i><?= $label ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Message -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Message <span class="text-danger">*</span></label>
                        <textarea name="message" class="form-control" rows="5"
                                  placeholder="Notification message body..." required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                    </div>

                    <!-- Action URL -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Action URL <small class="text-muted fw-normal">(optional)</small></label>
                        <input type="url" name="action_url" class="form-control"
                               placeholder="https://..."
                               value="<?= htmlspecialchars($_POST['action_url'] ?? '') ?>">
                        <div class="form-text">A link shown in the notification for further action.</div>
                    </div>

                    <hr>

                    <!-- Recipients -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Send To <span class="text-danger">*</span></label>

                        <?php
                        $targetOpts = [
                            'all_admins'     => ['bi-shield', "All Admins (" . count($admins) . ")"],
                            'specific_admins'=> ['bi-shield-check', "Specific Admins"],
                            'all_customers'  => ['bi-people', "All Active Customers ({$totalCustomers})"],
                            'all_merchants'  => ['bi-shop', "All Approved Merchants ({$totalMerchants})"],
                            'all_users'      => ['bi-globe', "Everyone &mdash; Admins + Customers + Merchants"],
                        ];
                        $currentTarget = $_POST['target'] ?? 'all_admins';
                        foreach ($targetOpts as $val => [$icon, $label]):
                        ?>
                        <div class="form-check mb-1">
                            <input class="form-check-input" type="radio" name="target"
                                   id="tgt_<?= $val ?>" value="<?= $val ?>"
                                   <?= $currentTarget === $val ? 'checked' : '' ?>
                                   onchange="toggleSpecific()">
                            <label class="form-check-label" for="tgt_<?= $val ?>">
                                <i class="bi <?= $icon ?> me-1"></i><?= $label ?>
                            </label>
                        </div>
                        <?php endforeach; ?>

                        <!-- Specific Admin Checkboxes -->
                        <div id="specific_box" class="mt-2"
                             style="display:<?= $currentTarget === 'specific_admins' ? 'block' : 'none' ?>">
                            <div class="border rounded p-3" style="max-height:220px;overflow-y:auto;">
                                <?php foreach ($admins as $a): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox"
                                           name="admin_ids[]" value="<?= $a['id'] ?>"
                                           id="ai_<?= $a['id'] ?>"
                                           <?= (!empty($_POST['admin_ids']) && in_array($a['id'], (array)$_POST['admin_ids'])) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="ai_<?= $a['id'] ?>">
                                        <?= htmlspecialchars($a['name']) ?>
                                        <span class="badge bg-secondary ms-1 small"><?= ucwords(str_replace('_', ' ', $a['admin_type'])) ?></span>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-1">
                                <button type="button" class="btn btn-xs btn-link p-0 small" onclick="checkAll(true)">Select All</button>
                                &nbsp;·&nbsp;
                                <button type="button" class="btn btn-xs btn-link p-0 small" onclick="checkAll(false)">Deselect All</button>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send me-1"></i>Send Notification
                        </button>
                        <a href="<?= BASE_URL ?>notifications" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Right: Preview & Tips -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold small">
                <i class="bi bi-eye me-1 text-primary"></i>Preview
            </div>
            <div class="card-body" id="preview_box">
                <div class="d-flex gap-3 align-items-start">
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white bg-primary"
                         id="prev_icon_wrap" style="width:38px;height:38px;min-width:38px;">
                        <i class="bi bi-info-circle" id="prev_icon"></i>
                    </div>
                    <div>
                        <div class="fw-bold small" id="prev_title">Notification Title</div>
                        <div class="text-muted small mt-1" id="prev_msg">Your message will appear here...</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card border-0 shadow-sm mt-3">
            <div class="card-header bg-white fw-semibold small">
                <i class="bi bi-info-circle me-1 text-primary"></i>Notes
            </div>
            <div class="card-body small text-muted">
                <ul class="ps-3 mb-0">
                    <li class="mb-2">Notifications appear in the bell icon for each recipient.</li>
                    <li class="mb-2">Recipients can mark notifications as read from their panel.</li>
                    <li class="mb-2">Broadcast to <strong>Everyone</strong> may send thousands of notifications &mdash; use carefully.</li>
                    <li>Only super admins and city admins can broadcast.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
const typeIcons = {
    info:'bi-info-circle', success:'bi-check-circle', warning:'bi-exclamation-triangle',
    error:'bi-x-circle', promotion:'bi-tag', coupon:'bi-ticket-perforated',
    flash_discount:'bi-lightning', contest:'bi-trophy'
};
const typeBgs = {
    info:'bg-primary', success:'bg-success', warning:'bg-warning', error:'bg-danger',
    promotion:'bg-secondary', coupon:'bg-info', flash_discount:'bg-warning', contest:'bg-dark'
};

function updatePreview() {
    const title = document.querySelector('[name=title]').value || 'Notification Title';
    const msg   = document.querySelector('[name=message]').value || 'Your message will appear here...';
    const type  = document.querySelector('[name=notification_type]:checked')?.value || 'info';
    document.getElementById('prev_title').textContent = title;
    document.getElementById('prev_msg').textContent   = msg;
    document.getElementById('prev_icon').className    = 'bi ' + (typeIcons[type] || 'bi-bell');
    const wrap = document.getElementById('prev_icon_wrap');
    wrap.className = 'rounded-circle d-flex align-items-center justify-content-center text-white ' + (typeBgs[type] || 'bg-primary');
    wrap.style.cssText = 'width:38px;height:38px;min-width:38px;';
}

document.querySelectorAll('[name=title],[name=message]').forEach(el => el.addEventListener('input', updatePreview));
document.querySelectorAll('[name=notification_type]').forEach(el => el.addEventListener('change', updatePreview));

function toggleSpecific() {
    const val = document.querySelector('[name=target]:checked')?.value;
    document.getElementById('specific_box').style.display = val === 'specific_admins' ? 'block' : 'none';
}

function checkAll(state) {
    document.querySelectorAll('#specific_box input[type=checkbox]').forEach(cb => cb.checked = state);
}
</script>


<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0">Send Notification</h5>
    <a href="<?= BASE_URL ?>notifications" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Notifications
    </a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-megaphone me-2 text-primary"></i>Broadcast Notification
            </div>
            <div class="card-body">
                <form method="POST" action="<?= BASE_URL ?>notifications/broadcast">

                    <!-- Title -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control"
                               placeholder="Notification title"
                               value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
                    </div>

                    <!-- Type -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
                        <div class="d-flex gap-3 flex-wrap">
                            <?php
                            $typeConfig = [
                                'info'    => ['text-primary',  'bi-info-circle',         'Information'],
                                'success' => ['text-success',  'bi-check-circle',        'Success'],
                                'warning' => ['text-warning',  'bi-exclamation-triangle', 'Warning'],
                                'error'   => ['text-danger',   'bi-x-circle',            'Error'],
                            ];
                            foreach ($typeConfig as $val => [$cls, $icon, $label]):
                            ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="notification_type"
                                       id="nt_<?= $val ?>" value="<?= $val ?>"
                                       <?= (($_POST['notification_type'] ?? 'info') === $val) ? 'checked' : '' ?>>
                                <label class="form-check-label <?= $cls ?>" for="nt_<?= $val ?>">
                                    <i class="bi <?= $icon ?> me-1"></i><?= $label ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Message -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Message <span class="text-danger">*</span></label>
                        <textarea name="message" class="form-control" rows="5"
                                  placeholder="Notification message body..." required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                    </div>

                    <!-- Action URL -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Action URL <small class="text-muted fw-normal">(optional)</small></label>
                        <input type="url" name="action_url" class="form-control"
                               placeholder="https://..."
                               value="<?= htmlspecialchars($_POST['action_url'] ?? '') ?>">
                        <div class="form-text">A link shown in the notification for further action.</div>
                    </div>

                    <hr>

                    <!-- Recipients -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Send To <span class="text-danger">*</span></label>
                        <div class="mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="target" id="target_all" value="all"
                                       <?= (($_POST['target'] ?? 'all') === 'all') ? 'checked' : '' ?>
                                       onchange="document.getElementById('specific_box').style.display='none'">
                                <label class="form-check-label" for="target_all">
                                    <i class="bi bi-people me-1"></i>All Admins
                                    <span class="text-muted small">(<?= count($admins) ?> admins)</span>
                                </label>
                            </div>
                            <div class="form-check mt-1">
                                <input class="form-check-input" type="radio" name="target" id="target_specific" value="specific"
                                       <?= (($_POST['target'] ?? '') === 'specific') ? 'checked' : '' ?>
                                       onchange="document.getElementById('specific_box').style.display='block'">
                                <label class="form-check-label" for="target_specific">
                                    <i class="bi bi-person-check me-1"></i>Select specific admins
                                </label>
                            </div>
                        </div>

                        <!-- Specific Admin Checkboxes -->
                        <div id="specific_box" style="display:<?= (($_POST['target'] ?? 'all') === 'specific') ? 'block' : 'none' ?>">
                            <div class="border rounded p-3" style="max-height:220px;overflow-y:auto;">
                                <?php foreach ($admins as $a): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox"
                                           name="admin_ids[]" value="<?= $a['id'] ?>"
                                           id="ai_<?= $a['id'] ?>"
                                           <?= (!empty($_POST['admin_ids']) && in_array($a['id'], (array)$_POST['admin_ids'])) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="ai_<?= $a['id'] ?>">
                                        <?= htmlspecialchars($a['name']) ?>
                                        <span class="badge bg-secondary ms-1 small"><?= ucwords(str_replace('_', ' ', $a['admin_type'])) ?></span>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-1">
                                <button type="button" class="btn btn-xs btn-link p-0 small" onclick="checkAll(true)">Select All</button>
                                &nbsp;·&nbsp;
                                <button type="button" class="btn btn-xs btn-link p-0 small" onclick="checkAll(false)">Deselect All</button>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send me-1"></i>Send Notification
                        </button>
                        <a href="<?= BASE_URL ?>notifications" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Right: Preview & Tips -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold small">
                <i class="bi bi-eye me-1 text-primary"></i>Preview
            </div>
            <div class="card-body" id="preview_box">
                <div class="d-flex gap-3 align-items-start">
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white bg-primary"
                         id="prev_icon_wrap" style="width:38px;height:38px;min-width:38px;">
                        <i class="bi bi-info-circle" id="prev_icon"></i>
                    </div>
                    <div>
                        <div class="fw-bold small" id="prev_title">Notification Title</div>
                        <div class="text-muted small mt-1" id="prev_msg">Your message will appear here...</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card border-0 shadow-sm mt-3">
            <div class="card-header bg-white fw-semibold small">
                <i class="bi bi-info-circle me-1 text-primary"></i>Notes
            </div>
            <div class="card-body small text-muted">
                <ul class="ps-3 mb-0">
                    <li class="mb-2">Notifications appear in the bell icon for each recipient.</li>
                    <li class="mb-2">Recipients can mark notifications as read from their panel.</li>
                    <li>Only super admins and city admins can broadcast notifications.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
const typeIcons = {info:'bi-info-circle',success:'bi-check-circle',warning:'bi-exclamation-triangle',error:'bi-x-circle'};
const typeBgs   = {info:'bg-primary',success:'bg-success',warning:'bg-warning',error:'bg-danger'};

function updatePreview() {
    const title = document.querySelector('[name=title]').value || 'Notification Title';
    const msg   = document.querySelector('[name=message]').value || 'Your message will appear here...';
    const type  = document.querySelector('[name=notification_type]:checked')?.value || 'info';
    document.getElementById('prev_title').textContent = title;
    document.getElementById('prev_msg').textContent   = msg;
    document.getElementById('prev_icon').className    = 'bi ' + (typeIcons[type] || 'bi-bell');
    const wrap = document.getElementById('prev_icon_wrap');
    wrap.className = 'rounded-circle d-flex align-items-center justify-content-center text-white ' + (typeBgs[type] || 'bg-primary');
    wrap.style.cssText = 'width:38px;height:38px;min-width:38px;';
}

document.querySelectorAll('[name=title],[name=message]').forEach(el => el.addEventListener('input', updatePreview));
document.querySelectorAll('[name=notification_type]').forEach(el => el.addEventListener('change', updatePreview));

function checkAll(state) {
    document.querySelectorAll('#specific_box input[type=checkbox]').forEach(cb => cb.checked = state);
}
</script>
