<?php /* views/reviews/view.php */
$statusColors = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'];
$starColors   = [1 => 'danger', 2 => 'warning', 3 => 'secondary', 4 => 'primary', 5 => 'success'];
$detailUrl    = 'reviews/detail?id=' . $review['id'];
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>reviews">Reviews</a></li>
        <li class="breadcrumb-item active">Review #<?= $review['id'] ?></li>
    </ol>
</nav>

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

<div class="row g-4">

    <!-- LEFT: Review Content -->
    <div class="col-lg-8">

        <!-- Header Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex align-items-start gap-3 flex-wrap">
                    <!-- Star rating icon -->
                    <div class="rounded d-flex align-items-center justify-content-center text-white fw-bold flex-shrink-0
                                bg-<?= $starColors[$review['rating']] ?? 'secondary' ?>"
                         style="width:56px;height:56px;font-size:1.4rem;">
                        <?= $review['rating'] ?>★
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="mb-1">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star<?= $i > $review['rating'] ? '-o' : '' ?> text-<?= $starColors[$review['rating']] ?? 'secondary' ?>"
                                   style="font-size:1.1rem;"></i>
                            <?php endfor; ?>
                            <span class="ms-1 text-muted small">(<?= $review['rating'] ?> / 5)</span>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge bg-<?= $statusColors[$review['status']] ?? 'secondary' ?> text-<?= $review['status'] === 'pending' ? 'dark' : 'white' ?>">
                                <?= ucfirst($review['status']) ?>
                            </span>
                            <span class="badge bg-light text-dark border">Review #<?= $review['id'] ?></span>
                        </div>
                    </div>

                    <!-- Quick actions from detail page -->
                    <div class="d-flex gap-2 flex-shrink-0">
                        <?php if ($review['status'] !== 'approved'): ?>
                        <form method="POST" action="<?= BASE_URL ?>reviews/approve">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            <input type="hidden" name="id" value="<?= $review['id'] ?>">
                            <input type="hidden" name="redirect" value="<?= $detailUrl ?>">
                            <button type="submit" class="btn btn-sm btn-success">
                                <i class="fas fa-check me-1"></i> Approve
                            </button>
                        </form>
                        <?php endif; ?>
                        <?php if ($review['status'] !== 'rejected'): ?>
                        <form method="POST" action="<?= BASE_URL ?>reviews/reject">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            <input type="hidden" name="id" value="<?= $review['id'] ?>">
                            <input type="hidden" name="redirect" value="<?= $detailUrl ?>">
                            <button type="submit" class="btn btn-sm btn-outline-warning">
                                <i class="fas fa-times me-1"></i> Reject
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Review Text -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold border-bottom">
                <i class="fas fa-quote-left me-2 text-primary"></i> Review Content
            </div>
            <div class="card-body">
                <?php if ($review['review_text']): ?>
                    <p class="mb-0 fs-6" style="white-space: pre-wrap; line-height: 1.7;">
                        <?= escape($review['review_text']) ?>
                    </p>
                <?php else: ?>
                    <p class="text-muted fst-italic mb-0">
                        This review has no text — rating only.
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Status note -->
        <?php if ($review['status'] === 'pending'): ?>
        <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center gap-2">
            <i class="fas fa-exclamation-circle fs-5 flex-shrink-0"></i>
            <div>
                <strong>Awaiting moderation.</strong>
                This review is <strong>not yet publicly visible</strong> and is not counted in the merchant's average rating.
                Approve it to make it live, or reject it to hide it permanently.
            </div>
        </div>
        <?php elseif ($review['status'] === 'approved'): ?>
        <div class="alert alert-success border-0 shadow-sm d-flex align-items-center gap-2">
            <i class="fas fa-check-circle fs-5 flex-shrink-0"></i>
            <div>
                <strong>Live & public.</strong>
                This review is visible on the merchant's public page and is included in the average rating calculation.
            </div>
        </div>
        <?php elseif ($review['status'] === 'rejected'): ?>
        <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center gap-2">
            <i class="fas fa-eye-slash fs-5 flex-shrink-0"></i>
            <div>
                <strong>Rejected.</strong>
                This review is hidden from public view and excluded from the average rating.
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- RIGHT: Parties + Meta + Danger Zone -->
    <div class="col-lg-4">

        <!-- Customer -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold border-bottom">
                <i class="fas fa-user me-2 text-info"></i> Reviewer
            </div>
            <div class="card-body small">
                <div class="fw-semibold mb-1">
                    <a href="<?= BASE_URL ?>customers/profile?id=<?= $review['customer_id'] ?>" class="text-decoration-none">
                        <?= escape($review['customer_name']) ?>
                    </a>
                </div>
                <?php if ($review['customer_phone']): ?>
                    <div class="text-muted"><?= escape($review['customer_phone']) ?></div>
                <?php endif; ?>
                <?php if ($review['customer_email']): ?>
                    <div class="text-muted"><?= escape($review['customer_email']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Merchant -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold border-bottom">
                <i class="fas fa-store me-2 text-warning"></i> Reviewed Merchant
            </div>
            <div class="card-body small">
                <div class="fw-semibold mb-1">
                    <a href="<?= BASE_URL ?>merchants/profile?id=<?= $review['merchant_id'] ?>" class="text-decoration-none">
                        <?= escape($review['merchant_name']) ?>
                    </a>
                </div>
                <?php if ($review['store_name']): ?>
                    <div class="text-muted">Store: <?= escape($review['store_name']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Timestamps -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold border-bottom">
                <i class="fas fa-clock me-2 text-secondary"></i> Timestamps
            </div>
            <div class="card-body small">
                <div class="mb-2">
                    <span class="text-muted">Submitted:</span>
                    <?= formatDateTime($review['created_at']) ?>
                </div>
                <?php if ($review['updated_at'] && $review['updated_at'] !== $review['created_at']): ?>
                <div>
                    <span class="text-muted">Last updated:</span>
                    <?= formatDateTime($review['updated_at']) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Danger Zone -->
        <div class="card border-danger border-opacity-25 shadow-sm mb-4">
            <div class="card-header bg-danger-subtle text-danger fw-semibold border-bottom border-danger border-opacity-25">
                <i class="fas fa-exclamation-triangle me-2"></i> Danger Zone
            </div>
            <div class="card-body">
                <p class="small text-muted mb-2">
                    Permanently delete this review. This cannot be undone and will
                    immediately affect the merchant's average rating.
                </p>
                <form method="POST" action="<?= BASE_URL ?>reviews/delete" id="deleteForm">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="id" value="<?= $review['id'] ?>">
                    <button type="button" class="btn btn-sm btn-danger w-100"
                            onclick="confirmDelete(<?= $review['id'] ?>)">
                        <i class="fas fa-trash me-1"></i> Delete Review
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>

<script>
function confirmDelete(id) {
    Swal.fire({
        title: 'Delete Review?',
        html: 'This will <strong>permanently delete</strong> the review and affect the merchant\'s rating.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Delete Permanently'
    }).then(r => {
        if (r.isConfirmed) {
            document.getElementById('deleteForm').submit();
        }
    });
}
</script>
