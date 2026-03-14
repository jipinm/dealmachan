<?php /* views/contact-enquiries/detail.php */
$statusColors = ['new'=>'danger','read'=>'warning','responded'=>'success'];
?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>contact-enquiries">Contact Enquiries</a></li>
        <li class="breadcrumb-item active"><?= escape($enquiry['name']) ?></li>
    </ol>
</nav>

<?php if ($flash_success): ?>
<div class="alert alert-success alert-dismissible fade show"><?= escape($flash_success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-semibold"><i class="fas fa-envelope me-2 text-primary"></i><?= escape($enquiry['subject'] ?? 'Enquiry #' . $enquiry['id']) ?></span>
                <span class="badge bg-<?= $statusColors[$enquiry['status']] ?>"><?= ucfirst($enquiry['status']) ?></span>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-sm-4">
                        <p class="text-muted small mb-1">From</p>
                        <p class="fw-semibold mb-0"><?= escape($enquiry['name']) ?></p>
                    </div>
                    <div class="col-sm-4">
                        <p class="text-muted small mb-1">Mobile</p>
                        <p class="fw-semibold mb-0"><?= escape($enquiry['mobile'] ?? '&mdash;') ?></p>
                    </div>
                    <div class="col-sm-4">
                        <p class="text-muted small mb-1">Email</p>
                        <p class="fw-semibold mb-0"><?= $enquiry['email'] ? '<a href="mailto:' . escape($enquiry['email']) . '">' . escape($enquiry['email']) . '</a>' : '&mdash;' ?></p>
                    </div>
                    <div class="col-sm-4">
                        <p class="text-muted small mb-1">Received</p>
                        <p class="fw-semibold mb-0"><?= formatDateTime($enquiry['created_at']) ?></p>
                    </div>
                </div>
                <p class="text-muted small mb-1">Message</p>
                <div class="p-3 bg-light rounded border"><?= nl2br(escape($enquiry['message'])) ?></div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Respond / Update</div>
            <div class="card-body">
                <form method="POST" action="<?= BASE_URL ?>contact-enquiries/respond">
                    <input type="hidden" name="id" value="<?= $enquiry['id'] ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            <?php foreach (['new','read','responded'] as $st): ?>
                            <option value="<?= $st ?>" <?= $enquiry['status'] === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Admin Notes</label>
                        <textarea name="admin_notes" class="form-control" rows="4"><?= htmlspecialchars($enquiry['admin_notes'] ?? '', ENT_QUOTES) ?></textarea>
                    </div>
                    <button class="btn btn-primary w-100"><i class="fas fa-save me-2"></i>Save</button>
                </form>
            </div>
        </div>
    </div>
</div>
