<?php /* views/reports/engagement.php */ ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="mb-0"><i class="bi bi-activity text-info me-2"></i>Engagement Report</h4>
    <form class="d-flex gap-2 align-items-center" method="GET" action="<?= BASE_URL ?>reports/engagement">
        <input type="date" name="date_from" class="form-control form-control-sm" value="<?= escape($from) ?>">
        <span class="text-muted small">to</span>
        <input type="date" name="date_to" class="form-control form-control-sm" value="<?= escape($to) ?>">
        <button class="btn btn-sm btn-primary"><i class="bi bi-funnel me-1"></i>Apply</button>
    </form>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
    <?php foreach ([
        ['label' => 'Survey Responses',    'val' => number_format($stats['surveyResponses']),                   'color' => 'primary',   'icon' => 'bi-clipboard-data'],
        ['label' => 'Contest Entries',     'val' => number_format($stats['contestEntries']),                    'color' => 'warning',   'icon' => 'bi-trophy'],
        ['label' => 'Referrals Total',     'val' => number_format($stats['referrals']),                         'color' => 'info',      'icon' => 'bi-person-plus'],
        ['label' => 'Completed Referrals', 'val' => number_format($stats['completedReferrals']),                'color' => 'success',   'icon' => 'bi-person-check'],
        ['label' => 'Rewards Paid',        'val' => '₹'.number_format($stats['rewardsPaid'], 2),               'color' => 'danger',    'icon' => 'bi-gift'],
        ['label' => 'DealMaker Tasks',     'val' => number_format($stats['dealmakerTasks']),                    'color' => 'secondary', 'icon' => 'bi-journal-check'],
        ['label' => 'Tasks Completed',     'val' => number_format($stats['completedTasks']),                    'color' => 'dark',      'icon' => 'bi-check-all'],
    ] as $c): ?>
    <div class="col-6 col-md-4 col-xl">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-3">
                <i class="bi <?= $c['icon'] ?> text-<?= $c['color'] ?> fs-4 d-block mb-1"></i>
                <div class="fw-bold"><?= $c['val'] ?></div>
                <div class="text-muted" style="font-size:.75rem"><?= $c['label'] ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <!-- Top Contests -->
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-trophy text-warning me-2"></i>Top Contests by Participation</div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="table-light"><tr><th>#</th><th>Contest</th><th class="text-end">Entries</th></tr></thead>
                    <tbody>
                    <?php foreach ($topContests as $i => $ct): ?>
                    <tr>
                        <td class="text-muted small"><?= $i + 1 ?></td>
                        <td>
                            <div class="fw-semibold small"><?= escape($ct['title']) ?></div>
                            <?php if (!empty($ct['start_date'])): ?>
                            <div class="text-muted" style="font-size:.72rem"><?= date('d M Y', strtotime($ct['start_date'])) ?> – <?= !empty($ct['end_date']) ? date('d M Y', strtotime($ct['end_date'])) : '∞' ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-end fw-semibold"><?= number_format($ct['entries']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($topContests)): ?>
                    <tr><td colspan="3" class="text-muted text-center py-3">No contest entries in this period.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Top Surveys -->
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-clipboard-data text-primary me-2"></i>Top Surveys by Response</div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="table-light"><tr><th>#</th><th>Survey</th><th class="text-end">Responses</th></tr></thead>
                    <tbody>
                    <?php foreach ($topSurveys as $i => $sv): ?>
                    <tr>
                        <td class="text-muted small"><?= $i + 1 ?></td>
                        <td class="fw-semibold small"><?= escape($sv['title']) ?></td>
                        <td class="text-end fw-semibold"><?= number_format($sv['responses']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($topSurveys)): ?>
                    <tr><td colspan="3" class="text-muted text-center py-3">No survey responses in this period.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
