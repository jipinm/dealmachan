<?php $pageTitle = 'Compose Message'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0">Compose Message</h5>
    <a href="<?= BASE_URL ?>messages/inbox" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Inbox
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
            <div class="card-body">
                <form method="POST" action="<?= BASE_URL ?>messages/compose">

                    <!-- Recipient Type -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Recipient Type <span class="text-danger">*</span></label>
                        <div class="d-flex gap-3">
                            <?php foreach (['admin' => 'Admin', 'merchant' => 'Merchant', 'customer' => 'Customer'] as $val => $label): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="receiver_type"
                                       id="rt_<?= $val ?>" value="<?= $val ?>"
                                       <?= (($_POST['receiver_type'] ?? $prefill['receiver_type'] ?? '') === $val) ? 'checked' : '' ?>
                                       onchange="switchRecipient(this.value)">
                                <label class="form-check-label" for="rt_<?= $val ?>"><?= $label ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Recipient Selects (shown/hidden based on type) -->
                    <?php
                    $selTypes = [
                        'admin'    => $admins,
                        'merchant' => $merchants,
                        'customer' => $customers,
                    ];
                    $labelKeys = ['admin' => 'name', 'merchant' => 'business_name', 'customer' => 'name'];
                    foreach ($selTypes as $type => $list):
                        $selectedRec = (($_POST['receiver_type'] ?? $prefill['receiver_type'] ?? '') === $type)
                                       ? (int)($_POST['receiver_id'] ?? $prefill['receiver_id'] ?? 0) : 0;
                    ?>
                    <div id="recipient_<?= $type ?>" class="mb-3" style="display:<?= (($_POST['receiver_type'] ?? $prefill['receiver_type'] ?? 'admin') === $type) ? 'block' : 'none' ?>">
                        <label class="form-label">Select <?= ucfirst($type) ?> <span class="text-danger">*</span></label>
                        <select name="receiver_id" class="form-select recipient-select" <?= (($_POST['receiver_type'] ?? $prefill['receiver_type'] ?? 'admin') !== $type) ? 'disabled' : '' ?>>
                            <option value="">— Choose <?= ucfirst($type) ?> —</option>
                            <?php foreach ($list as $item): ?>
                                <?php
                                $id   = $item['id'];
                                $name = $item[$labelKeys[$type]];
                                $extra = ($type === 'admin') ? ' (' . ucwords(str_replace('_', ' ', $item['admin_type'])) . ')' : '';
                                if ($type === 'customer' && !empty($item['phone'])) $extra = ' · ' . $item['phone'];
                                ?>
                                <option value="<?= $id ?>" <?= $selectedRec === $id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($name . $extra) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endforeach; ?>

                    <!-- Subject -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Subject</label>
                        <input type="text" name="subject" class="form-control"
                               placeholder="Message subject (optional)"
                               value="<?= htmlspecialchars($_POST['subject'] ?? $prefill['subject'] ?? '') ?>">
                    </div>

                    <!-- Body -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Message <span class="text-danger">*</span></label>
                        <textarea name="message_text" class="form-control" rows="8"
                                  placeholder="Write your message here..." required><?= htmlspecialchars($_POST['message_text'] ?? '') ?></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send me-1"></i>Send Message
                        </button>
                        <a href="<?= BASE_URL ?>messages/inbox" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Quick Tips -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold small">
                <i class="bi bi-info-circle me-1 text-primary"></i>Quick Tips
            </div>
            <div class="card-body small text-muted">
                <ul class="ps-3 mb-0">
                    <li class="mb-2">Select recipient type, then choose the specific recipient from the dropdown.</li>
                    <li class="mb-2">Subject is optional, but helps with organization.</li>
                    <li class="mb-2">Recipients can reply to your message from their panel.</li>
                    <li>Messages are delivered instantly to the recipient's inbox.</li>
                </ul>
            </div>
        </div>
        <div class="card border-0 shadow-sm mt-3">
            <div class="card-header bg-white fw-semibold small">
                <i class="bi bi-bar-chart me-1 text-primary"></i>Message Stats
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between small">
                    <span class="text-muted">Inbox</span>
                    <strong><?= (int)($stats['inbox'] ?? 0) ?></strong>
                </div>
                <div class="d-flex justify-content-between small mt-1">
                    <span class="text-muted">Unread</span>
                    <strong class="text-danger"><?= (int)($stats['unread'] ?? 0) ?></strong>
                </div>
                <div class="d-flex justify-content-between small mt-1">
                    <span class="text-muted">Sent</span>
                    <strong class="text-primary"><?= (int)($stats['sent'] ?? 0) ?></strong>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function switchRecipient(type) {
    ['admin','merchant','customer'].forEach(t => {
        const el  = document.getElementById('recipient_' + t);
        const sel = el.querySelector('select');
        if (t === type) {
            el.style.display = 'block';
            sel.disabled = false;
            sel.name = 'receiver_id';
        } else {
            el.style.display = 'none';
            sel.disabled = true;
            sel.name = '';
        }
    });
}
// Set initial state
const checked = document.querySelector('input[name="receiver_type"]:checked');
if (checked) switchRecipient(checked.value);
</script>
