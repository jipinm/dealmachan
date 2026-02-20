<?php
$pageTitle = 'Survey Responses — ' . htmlspecialchars($survey['title']);
$qTypeLabels = ['text'=>'Short Text','textarea'=>'Long Text','radio'=>'Multiple Choice',
                'checkbox'=>'Checkboxes','select'=>'Dropdown','rating'=>'Rating'];
$statusColors = ['draft'=>'secondary','active'=>'success','closed'=>'danger'];
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0"><?= htmlspecialchars($survey['title']) ?></h5>
        <span class="badge bg-<?= $statusColors[$survey['status']] ?? 'secondary' ?> mt-1">
            <?= ucfirst($survey['status']) ?>
        </span>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>surveys/edit?id=<?= $survey['id'] ?>" class="btn btn-outline-primary btn-sm">
            <i class="fas fa-edit me-1"></i> Edit Survey
        </a>
        <a href="<?= BASE_URL ?>surveys" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-2">
                <div class="fs-4 fw-bold text-primary"><?= $total ?></div>
                <div class="text-muted small">Total Responses</div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-2">
                <div class="fs-4 fw-bold text-info"><?= count($questions) ?></div>
                <div class="text-muted small">Questions</div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-2">
                <div class="fs-4 fw-bold text-muted">
                    <?php
                    $from  = $survey['active_from']  ? date('d M Y', strtotime($survey['active_from']))  : '∞';
                    $until = $survey['active_until'] ? date('d M Y', strtotime($survey['active_until'])) : '∞';
                    echo ($survey['active_from'] || $survey['active_until']) ? $from . ' – ' . $until : 'Always Open';
                    ?>
                </div>
                <div class="text-muted small">Active Period</div>
            </div>
        </div>
    </div>
</div>

<?php if ($survey['description']): ?>
<div class="alert alert-light border mb-4">
    <i class="fas fa-info-circle me-2 text-muted"></i><?= htmlspecialchars($survey['description']) ?>
</div>
<?php endif; ?>

<!-- Analytics -->
<?php if (!empty($analytics) && $total > 0): ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent fw-semibold">
        <i class="fas fa-chart-bar me-2"></i>Response Analytics
    </div>
    <div class="card-body">
        <?php foreach ($analytics as $qId => $a): ?>
        <div class="mb-4">
            <div class="fw-semibold mb-1">
                <?= htmlspecialchars($a['question']) ?>
                <span class="badge bg-light text-dark border small ms-1"><?= $qTypeLabels[$a['type']] ?? $a['type'] ?></span>
                <span class="text-muted small ms-1">(<?= count($a['answers']) ?> answers)</span>
            </div>

            <?php if (!empty($a['frequency'])): ?>
                <?php $maxFreq = max($a['frequency']); ?>
                <?php foreach ($a['frequency'] as $option => $count): ?>
                <div class="d-flex align-items-center mb-1 gap-2">
                    <div class="text-muted small" style="min-width:160px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= htmlspecialchars($option) ?>">
                        <?= htmlspecialchars($option) ?>
                    </div>
                    <div class="flex-grow-1">
                        <div class="progress" style="height:18px">
                            <div class="progress-bar bg-primary" style="width:<?= $maxFreq ? round(($count/$maxFreq)*100) : 0 ?>%"></div>
                        </div>
                    </div>
                    <div class="text-muted small" style="min-width:60px">
                        <?= $count ?> (<?= $total > 0 ? round(($count/count($a['answers']))*100) : 0 ?>%)
                    </div>
                </div>
                <?php endforeach; ?>
            <?php elseif ($a['type'] === 'rating' && !empty($a['answers'])): ?>
                <?php $avg = array_sum(array_map('floatval', $a['answers'])) / count($a['answers']); ?>
                <div class="fs-4 fw-bold text-warning">
                    <?= number_format($avg, 1) ?> <span class="text-muted fs-6">/ <?= $questions[array_search($qId, array_column($questions, 'id'))]['scale'] ?? 5 ?></span>
                </div>
            <?php else: ?>
                <!-- Text/textarea: show a few sample answers -->
                <div class="border rounded p-2 bg-light">
                    <?php foreach (array_slice($a['answers'], 0, 5) as $ans): ?>
                        <div class="mb-1 small">— <?= htmlspecialchars($ans) ?></div>
                    <?php endforeach; ?>
                    <?php if (count($a['answers']) > 5): ?>
                        <div class="text-muted small">…and <?= count($a['answers']) - 5 ?> more</div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Individual Responses -->
<div class="d-flex justify-content-between align-items-center mb-2">
    <h6 class="mb-0">Individual Responses</h6>
</div>

<!-- Search -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="id" value="<?= $survey['id'] ?>">
            <div class="col-sm-5">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Search by customer name or phone…"
                       value="<?= htmlspecialchars($filters['search']) ?>">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search me-1"></i>Search</button>
                <a href="<?= BASE_URL ?>surveys/responses?id=<?= $survey['id'] ?>" class="btn btn-outline-secondary btn-sm ms-1">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Submitted At</th>
                        <th>Answers</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($responses)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No responses yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($responses as $i => $resp): ?>
                    <?php $answers = json_decode($resp['responses_json'], true) ?? []; ?>
                    <tr>
                        <td class="text-muted small"><?= ($page - 1) * 20 + $i + 1 ?></td>
                        <td class="fw-semibold"><?= htmlspecialchars($resp['customer_name']) ?></td>
                        <td><?= htmlspecialchars($resp['customer_phone']) ?></td>
                        <td class="text-muted small"><?= date('d M Y H:i', strtotime($resp['submitted_at'])) ?></td>
                        <td>
                            <button class="btn btn-outline-primary btn-sm"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#resp-<?= $resp['id'] ?>">
                                <i class="fas fa-eye me-1"></i> View
                            </button>
                            <div class="collapse mt-2" id="resp-<?= $resp['id'] ?>">
                                <div class="border rounded p-2 bg-light small">
                                    <?php foreach ($questions as $q): ?>
                                        <?php $ans = $answers[$q['id']] ?? null; ?>
                                        <div class="mb-1">
                                            <span class="fw-semibold"><?= htmlspecialchars($q['question']) ?>:</span>
                                            <?php if (is_array($ans)): ?>
                                                <?= htmlspecialchars(implode(', ', $ans)) ?>
                                            <?php else: ?>
                                                <?= $ans !== null ? htmlspecialchars($ans) : '<span class="text-muted">—</span>' ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-center mb-0">
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['id' => $survey['id'], 'page' => $page - 1, 'limit' => null, 'offset' => null])) ?>">&laquo;</a>
        </li>
        <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
            <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['id' => $survey['id'], 'page' => $p, 'limit' => null, 'offset' => null]))?>"><?= $p ?></a>
        </li>
        <?php endfor; ?>
        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['id' => $survey['id'], 'page' => $page + 1, 'limit' => null, 'offset' => null])) ?>">&raquo;</a>
        </li>
    </ul>
</nav>
<?php endif; ?>
