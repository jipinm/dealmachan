<?php $pageTitle = 'Winners & Participants &mdash; ' . htmlspecialchars($contest['title']); ?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0"><?= htmlspecialchars($contest['title']) ?></h5>
        <small class="text-muted">
            <?php
            $badge = ['draft'=>'secondary','active'=>'success','completed'=>'primary','cancelled'=>'danger'];
            $b = $badge[$contest['status']] ?? 'secondary'; ?>
            <span class="badge bg-<?= $b ?>"><?= ucfirst($contest['status']) ?></span>
            &nbsp;<?= (int)$contest['participant_count'] ?> participants &nbsp;·&nbsp; <?= (int)$contest['winner_count'] ?> winners
        </small>
    </div>
    <a href="<?= BASE_URL ?>contests" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back
    </a>
</div>

<?php if (!empty($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= htmlspecialchars($_GET['success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php elseif (!empty($_GET['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= htmlspecialchars($_GET['error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-4">

    <!-- Left: Winners -->
    <div class="col-lg-5">

        <!-- Current Winners -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-trophy-fill text-warning me-1"></i>Announced Winners
            </div>
            <div class="card-body p-0">
                <?php if (empty($winners)): ?>
                    <div class="text-center text-muted py-4 small">No winners selected yet.</div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($winners as $w): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-<?= $w['position'] === 1 ? 'warning text-dark' : ($w['position'] === 2 ? 'secondary' : 'danger') ?> me-2">
                                    #<?= $w['position'] ?>
                                </span>
                                <strong><?= htmlspecialchars($w['customer_name']) ?></strong>
                                <div class="text-muted small"><?= htmlspecialchars($w['phone'] ?? '&mdash;') ?></div>
                                <?php if ($w['prize_details']): ?>
                                    <div class="text-success small"><i class="bi bi-gift me-1"></i><?= htmlspecialchars($w['prize_details']) ?></div>
                                <?php endif; ?>
                            </div>
                            <form method="POST" action="<?= BASE_URL ?>contests/remove-winner"
                                  onsubmit="return confirm('Remove this winner?')">
                                <input type="hidden" name="winner_id" value="<?= $w['id'] ?>">
                                <input type="hidden" name="contest_id" value="<?= $contest['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-x"></i>
                                </button>
                            </form>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <!-- Select Winner Form -->
        <?php if (!empty($participants) && in_array($contest['status'], ['active','completed'])): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Select a Winner</div>
            <div class="card-body">
                <form method="POST" action="<?= BASE_URL ?>contests/select-winner">
                    <input type="hidden" name="contest_id" value="<?= $contest['id'] ?>">
                    <div class="mb-3">
                        <label class="form-label small">Participant</label>
                        <select name="customer_id" class="form-select form-select-sm" required>
                            <option value="">&mdash; Choose Participant &mdash;</option>
                            <?php foreach ($participants as $p): ?>
                                <option value="<?= $p['customer_id'] ?>">
                                    <?= htmlspecialchars($p['customer_name']) ?> (<?= htmlspecialchars($p['phone'] ?? $p['email'] ?? '&mdash;') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Position</label>
                        <select name="position" class="form-select form-select-sm" required>
                            <option value="1">1st Place</option>
                            <option value="2">2nd Place</option>
                            <option value="3">3rd Place</option>
                            <option value="4">4th Place</option>
                            <option value="5">5th Place</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Prize Details</label>
                        <input type="text" name="prize_details" class="form-control form-control-sm"
                               placeholder="e.g. ₹500 gift voucher">
                    </div>
                    <button type="submit" class="btn btn-warning btn-sm w-100">
                        <i class="bi bi-trophy me-1"></i>Declare Winner
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Right: Participants -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between">
                <span>Participants (<?= count($participants) ?>)</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($participants)): ?>
                    <div class="text-center text-muted py-4 small">No participants yet.</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Phone / Email</th>
                                <th>Participated</th>
                                <th class="text-center">Winner?</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($participants as $i => $p): ?>
                            <tr>
                                <td class="text-muted small"><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars($p['customer_name']) ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($p['phone'] ?? $p['email'] ?? '&mdash;') ?></td>
                                <td class="small text-muted"><?= date('d M Y, H:i', strtotime($p['participated_at'])) ?></td>
                                <td class="text-center">
                                    <?php if ($p['winner_id']): ?>
                                        <span class="badge bg-warning text-dark">
                                            <i class="bi bi-trophy-fill me-1"></i>#<?= $p['position'] ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">&mdash;</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Contest Info -->
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white fw-semibold">Contest Info</div>
            <div class="card-body">
                <?php if ($contest['description']): ?>
                    <p class="mb-3 text-muted small"><?= nl2br(htmlspecialchars($contest['description'])) ?></p>
                <?php endif; ?>
                <?php if ($contest['start_date'] || $contest['end_date']): ?>
                    <div class="mb-2 small">
                        <strong>Duration:</strong>
                        <?= $contest['start_date'] ? date('d M Y', strtotime($contest['start_date'])) : '&mdash;' ?>
                        → <?= $contest['end_date'] ? date('d M Y', strtotime($contest['end_date'])) : '&mdash;' ?>
                    </div>
                <?php endif; ?>
                <?php
                $rules = $contest['rules_json'] ? json_decode($contest['rules_json'], true) : [];
                if (!empty($rules)):
                ?>
                <div>
                    <strong class="small">Rules:</strong>
                    <ul class="small mt-1 ps-3 mb-0">
                        <?php foreach ($rules as $rule): ?>
                            <li><?= htmlspecialchars($rule) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>
