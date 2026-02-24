<?php $pageTitle = 'Referral Detail #' . $referral['id']; ?>

<div class="mb-3">
    <a href="<?= BASE_URL ?>referrals" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Referrals</a>
</div>

<?php
$statusBadge = match($referral['status']) {
    'completed' => 'bg-info',
    'rewarded'  => 'bg-success',
    default     => 'bg-warning text-dark',
};
?>

<div class="row g-4 mb-4">
    <!-- Referral Summary Card -->
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Referral Information</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-5 text-muted">Referral ID</dt>    <dd class="col-7">#<?= $referral['id'] ?></dd>
                    <dt class="col-5 text-muted">Code Used</dt>      <dd class="col-7"><code><?= escape($referral['referral_code']) ?></code></dd>
                    <dt class="col-5 text-muted">Status</dt>         <dd class="col-7"><span class="badge <?= $statusBadge ?>"><?= ucfirst($referral['status']) ?></span></dd>
                    <dt class="col-5 text-muted">Reward Amount</dt>  <dd class="col-7">₹<?= number_format($referral['reward_amount'] ?? 0, 2) ?></dd>
                    <dt class="col-5 text-muted">Reward Given</dt>   <dd class="col-7">
                        <?php if ($referral['reward_given']): ?>
                            <span class="badge bg-success"><i class="bi bi-check2 me-1"></i>Yes — Paid</span>
                        <?php else: ?>
                            <span class="badge bg-light text-dark border">No — Pending</span>
                        <?php endif; ?>
                    </dd>
                    <dt class="col-5 text-muted">Created</dt>        <dd class="col-7"><?= date('d M Y H:i', strtotime($referral['created_at'])) ?></dd>
                    <?php if ($referral['completed_at']): ?>
                    <dt class="col-5 text-muted">Completed</dt>      <dd class="col-7"><?= date('d M Y H:i', strtotime($referral['completed_at'])) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
    </div>

    <!-- Parties -->
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-person-fill text-success me-2"></i>Referrer</div>
            <div class="card-body">
                <p class="mb-1 fw-semibold"><?= escape($referral['referrer_name']) ?></p>
                <p class="text-muted mb-1 small"><?= escape($referral['referrer_phone']) ?></p>
                <p class="mb-0 small">Referral Code: <code><?= escape($referral['referrer_code']) ?></code></p>
                <a href="<?= BASE_URL ?>customers/profile?id=<?= $referral['referrer_customer_id'] ?>" class="btn btn-sm btn-outline-secondary mt-2">View Profile</a>
            </div>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-person text-info me-2"></i>Referee (Referred Customer)</div>
            <div class="card-body">
                <p class="mb-1 fw-semibold"><?= escape($referral['referee_name']) ?></p>
                <p class="text-muted mb-0 small"><?= escape($referral['referee_phone']) ?></p>
                <a href="<?= BASE_URL ?>customers/profile?id=<?= $referral['referee_customer_id'] ?>" class="btn btn-sm btn-outline-secondary mt-2">View Profile</a>
            </div>
        </div>
    </div>
</div>

<!-- Admin Actions -->
<?php if ($cu['admin_type'] === 'super_admin' || $cu['admin_type'] === 'city_admin'): ?>
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">Admin Actions</div>
    <div class="card-body d-flex flex-wrap gap-2">

        <!-- Mark Reward Given -->
        <?php if (!$referral['reward_given']): ?>
        <button class="btn btn-success btn-sm" onclick="markReward(<?= $referral['id'] ?>)">
            <i class="bi bi-gift me-1"></i>Mark Reward as Given
        </button>
        <?php endif; ?>

        <!-- Override Status -->
        <div class="d-flex gap-2 align-items-center">
            <select id="statusOverride" class="form-select form-select-sm" style="width:auto">
                <option value="">Override Status…</option>
                <?php foreach (['pending','completed','rewarded'] as $s): ?>
                <option value="<?= $s ?>" <?= $referral['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-warning btn-sm" onclick="overrideStatus(<?= $referral['id'] ?>)">
                <i class="bi bi-pencil me-1"></i>Override
            </button>
        </div>
    </div>
</div>

<script>
function markReward(id) {
    Swal.fire({
        title: 'Mark Reward as Given?',
        text: 'This will mark the reward as paid and set status to "rewarded".',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Mark Paid',
        confirmButtonColor: '#198754'
    }).then(r => {
        if (!r.isConfirmed) return;
        fetch('<?= BASE_URL ?>referrals/reward', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + id
        }).then(r => r.json()).then(d => {
            if (d.success) {
                Swal.fire('Done', 'Reward marked as given.', 'success').then(() => location.reload());
            } else {
                Swal.fire('Error', d.error || 'Failed.', 'error');
            }
        });
    });
}

function overrideStatus(id) {
    const status = document.getElementById('statusOverride').value;
    if (!status) { Swal.fire('', 'Please select a status.', 'warning'); return; }
    fetch('<?= BASE_URL ?>referrals/override', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + id + '&status=' + status
    }).then(r => r.json()).then(d => {
        if (d.success) {
            Swal.fire('Updated', 'Status has been overridden.', 'success').then(() => location.reload());
        } else {
            Swal.fire('Error', d.error || 'Failed.', 'error');
        }
    });
}
</script>
<?php endif; ?>
