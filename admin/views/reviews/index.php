<?php /* views/reviews/index.php */
$statusColors = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'];
?>

<!-- Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card text-bg-primary h-100 shadow-sm">
            <div class="card-body py-3 px-3">
                <div class="small fw-semibold text-white-50">Total</div>
                <div class="fs-4 fw-bold"><?= number_format($stats['total'] ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card text-bg-warning h-100 shadow-sm">
            <div class="card-body py-3 px-3">
                <div class="small fw-semibold text-white-50">Pending</div>
                <div class="fs-4 fw-bold"><?= number_format($stats['pending'] ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card text-bg-success h-100 shadow-sm">
            <div class="card-body py-3 px-3">
                <div class="small fw-semibold text-white-50">Approved</div>
                <div class="fs-4 fw-bold"><?= number_format($stats['approved'] ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card text-bg-danger h-100 shadow-sm">
            <div class="card-body py-3 px-3">
                <div class="small fw-semibold text-white-50">Rejected</div>
                <div class="fs-4 fw-bold"><?= number_format($stats['rejected'] ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card text-bg-info h-100 shadow-sm">
            <div class="card-body py-3 px-3">
                <div class="small fw-semibold text-white-50">Avg Approved Rating</div>
                <div class="fs-4 fw-bold"><?= $stats['avg_approved_rating'] ? number_format($stats['avg_approved_rating'], 1) . ' ★' : '—' ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card text-bg-dark h-100 shadow-sm">
            <div class="card-body py-3 px-3">
                <div class="small fw-semibold text-white-50">Added Today</div>
                <div class="fs-4 fw-bold"><?= number_format($stats['today'] ?? 0) ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Page header -->
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="mb-0">Review Moderation</h4>
        <small class="text-muted">
            <?php
            $from = min($totalCount, ($currentPage - 1) * $perPage + 1);
            $to   = min($totalCount, $currentPage * $perPage);
            echo $totalCount ? "Showing {$from}–{$to} of {$totalCount}" : 'No reviews found';
            ?>
        </small>
    </div>
    <?php if (($stats['pending'] ?? 0) > 0): ?>
    <div>
        <span class="badge bg-warning text-dark fs-6">
            <i class="fas fa-clock me-1"></i>
            <?= number_format($stats['pending']) ?> pending review<?= $stats['pending'] > 1 ? 's' : '' ?> awaiting moderation
        </span>
    </div>
    <?php endif; ?>
</div>

<?php if (!empty($_SESSION['success'])): ?>
<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3">
    <i class="fas fa-check-circle me-2"></i><?= escape($_SESSION['success']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['success']); endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3">
    <i class="fas fa-exclamation-circle me-2"></i><?= escape($_SESSION['error']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['error']); endif; ?>

<!-- Filter bar -->
<div class="card mb-3 shadow-sm">
    <div class="card-body py-2 px-3">
        <form method="GET" action="<?= BASE_URL ?>reviews" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label form-label-sm mb-1">Search</label>
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Customer, merchant or review text…"
                       value="<?= escape($filters['search']) ?>">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label form-label-sm mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <?php foreach (['pending', 'approved', 'rejected'] as $s): ?>
                        <option value="<?= $s ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>>
                            <?= ucfirst($s) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label form-label-sm mb-1">Rating</label>
                <select name="rating" class="form-select form-select-sm">
                    <option value="">All Ratings</option>
                    <?php for ($r = 5; $r >= 1; $r--): ?>
                        <option value="<?= $r ?>" <?= ($filters['rating'] ?? '') == $r ? 'selected' : '' ?>>
                            <?= str_repeat('★', $r) . str_repeat('☆', 5 - $r) ?> (<?= $r ?>)
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label form-label-sm mb-1">Merchant</label>
                <select name="merchant_id" class="form-select form-select-sm">
                    <option value="">All Merchants</option>
                    <?php foreach ($merchants as $m): ?>
                        <option value="<?= $m['id'] ?>" <?= ($filters['merchant_id'] ?? '') == $m['id'] ? 'selected' : '' ?>>
                            <?= escape($m['business_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-auto ms-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i>Filter</button>
                <a href="<?= BASE_URL ?>reviews" class="btn btn-sm btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Reviews Table (with bulk action) -->
<form method="POST" id="bulkForm">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
    <div class="card shadow-sm">
        <?php if (!empty($reviews)): ?>
        <div class="card-header bg-white border-bottom py-2 px-3 d-flex align-items-center gap-2">
            <div class="form-check mb-0">
                <input class="form-check-input" type="checkbox" id="selectAll">
                <label class="form-check-label small text-muted" for="selectAll">Select all on page</label>
            </div>
            <div class="ms-auto d-flex gap-2">
                <button type="button" class="btn btn-sm btn-success" onclick="bulkAction('<?= BASE_URL ?>reviews/bulk-approve')">
                    <i class="fas fa-check me-1"></i> Approve Selected
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="bulkAction('<?= BASE_URL ?>reviews/bulk-reject')">
                    <i class="fas fa-times me-1"></i> Reject Selected
                </button>
            </div>
        </div>
        <?php endif; ?>

        <div class="card-body p-0">
            <?php if (empty($reviews)): ?>
            <div class="text-center text-muted py-5">
                <i class="fas fa-star fa-3x mb-3 opacity-25"></i>
                <p class="mb-0">No reviews found.</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:36px;"></th>
                            <th>Customer</th>
                            <th>Merchant / Store</th>
                            <th class="text-center">Rating</th>
                            <th>Review</th>
                            <th class="text-center">Status</th>
                            <th>Submitted</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reviews as $rv): ?>
                        <tr class="<?= $rv['status'] === 'pending' ? 'table-warning bg-opacity-25' : '' ?>">
                            <td>
                                <input class="form-check-input row-check" type="checkbox" name="ids[]" value="<?= $rv['id'] ?>">
                            </td>
                            <td>
                                <a href="<?= BASE_URL ?>customers/profile?id=<?= $rv['customer_id'] ?>"
                                   class="text-decoration-none small fw-semibold"><?= escape($rv['customer_name']) ?></a>
                                <?php if ($rv['customer_phone']): ?>
                                    <div class="text-muted" style="font-size:0.73rem;"><?= escape($rv['customer_phone']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?= BASE_URL ?>merchants/profile?id=<?= $rv['merchant_id'] ?>"
                                   class="text-decoration-none small fw-semibold"><?= escape($rv['merchant_name']) ?></a>
                                <?php if ($rv['store_name']): ?>
                                    <div class="text-muted" style="font-size:0.73rem;"><?= escape($rv['store_name']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php
                                $starColors = [1 => 'danger', 2 => 'warning', 3 => 'secondary', 4 => 'primary', 5 => 'success'];
                                $sc = $starColors[$rv['rating']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?= $sc ?> fs-6" style="letter-spacing:1px;">
                                    <?= $rv['rating'] ?>★
                                </span>
                            </td>
                            <td style="max-width:260px;">
                                <?php if ($rv['review_text']): ?>
                                    <span class="small" title="<?= escape($rv['review_text']) ?>">
                                        <?= escape(mb_strimwidth($rv['review_text'], 0, 100, '…')) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted small fst-italic">No text</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-<?= $statusColors[$rv['status']] ?? 'secondary' ?>">
                                    <?= ucfirst($rv['status']) ?>
                                </span>
                            </td>
                            <td class="small text-muted"><?= formatDate($rv['created_at']) ?></td>
                            <td class="text-center text-nowrap">
                                <a href="<?= BASE_URL ?>reviews/detail?id=<?= $rv['id'] ?>"
                                   class="btn btn-sm btn-outline-primary me-1" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($rv['status'] !== 'approved'): ?>
                                <form method="POST" action="<?= BASE_URL ?>reviews/approve" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                    <input type="hidden" name="id" value="<?= $rv['id'] ?>">
                                    <input type="hidden" name="redirect" value="reviews?<?= http_build_query($filters) ?>">
                                    <button type="submit" class="btn btn-sm btn-success me-1" title="Approve">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                                <?php if ($rv['status'] !== 'rejected'): ?>
                                <form method="POST" action="<?= BASE_URL ?>reviews/reject" class="d-inline me-1">
                                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                    <input type="hidden" name="id" value="<?= $rv['id'] ?>">
                                    <input type="hidden" name="redirect" value="reviews?<?= http_build_query($filters) ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-warning" title="Reject">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-outline-danger"
                                        onclick="confirmDelete(<?= $rv['id'] ?>, '<?= escape(addslashes($rv['customer_name'])) ?>')"
                                        title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</form>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-center">
        <?php if ($currentPage > 1): ?>
        <li class="page-item">
            <a class="page-link" href="<?= BASE_URL ?>reviews?<?= http_build_query(array_merge($filters, ['page' => $currentPage - 1])) ?>">
                <i class="fas fa-chevron-left"></i>
            </a>
        </li>
        <?php endif; ?>
        <?php for ($p = max(1, $currentPage - 2); $p <= min($totalPages, $currentPage + 2); $p++): ?>
        <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
            <a class="page-link" href="<?= BASE_URL ?>reviews?<?= http_build_query(array_merge($filters, ['page' => $p])) ?>"><?= $p ?></a>
        </li>
        <?php endfor; ?>
        <?php if ($currentPage < $totalPages): ?>
        <li class="page-item">
            <a class="page-link" href="<?= BASE_URL ?>reviews?<?= http_build_query(array_merge($filters, ['page' => $currentPage + 1])) ?>">
                <i class="fas fa-chevron-right"></i>
            </a>
        </li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>

<!-- Delete form (hidden) -->
<form method="POST" action="<?= BASE_URL ?>reviews/delete" id="deleteForm">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
    <input type="hidden" name="id" id="deleteId">
</form>

<script>
// Select-all checkbox
document.getElementById('selectAll')?.addEventListener('change', function () {
    document.querySelectorAll('.row-check').forEach(cb => cb.checked = this.checked);
});

function bulkAction(url) {
    const checked = [...document.querySelectorAll('.row-check:checked')];
    if (!checked.length) { alert('Please select at least one review.'); return; }
    const form = document.getElementById('bulkForm');
    form.action = url;
    form.submit();
}

function confirmDelete(id, name) {
    Swal.fire({
        title: 'Delete Review?',
        html: `Permanently delete review by <b>${name}</b>? This cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Delete'
    }).then(r => {
        if (r.isConfirmed) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteForm').submit();
        }
    });
}
</script>
