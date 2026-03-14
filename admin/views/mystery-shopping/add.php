<?php $pageTitle = 'Assign Mystery Shopping Task'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0">Assign Mystery Shopping Task</h5>
    <a href="<?= BASE_URL ?>mystery-shopping" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i> Back
    </a>
</div>

<form method="POST" action="<?= BASE_URL ?>mystery-shopping/add" id="taskForm">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

    <div class="row g-4">
        <!-- Assignment Details -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold">Assignment Details</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mystery Shopper <span class="text-danger">*</span></label>
                        <select name="customer_id" class="form-select" required>
                            <option value="">Select shopper…</option>
                            <?php foreach ($shoppers as $sh): ?>
                                <option value="<?= $sh['id'] ?>"><?= htmlspecialchars($sh['name']) ?> (<?= $sh['phone'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Merchant <span class="text-danger">*</span></label>
                        <select name="merchant_id" class="form-select" id="merchantSelect" required>
                            <option value="">Select merchant…</option>
                            <?php foreach ($merchants as $mer): ?>
                                <option value="<?= $mer['id'] ?>"><?= htmlspecialchars($mer['business_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Specific Store <span class="text-muted small">(optional)</span></label>
                        <select name="store_id" class="form-select" id="storeSelect">
                            <option value="">All stores / Not specified</option>
                        </select>
                        <div class="form-text">Select a merchant first to load stores.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Payment Amount (₹)</label>
                        <input type="number" name="payment_amount" class="form-control"
                               min="0" step="0.01" placeholder="Leave blank if no payment">
                    </div>
                </div>
            </div>
        </div>

        <!-- Task Content -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold">Task Description</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Task Description <span class="text-danger">*</span></label>
                        <textarea name="task_description" class="form-control" rows="4" required
                                  placeholder="Describe what the mystery shopper should do, observe, and report on…"><?= htmlspecialchars($_POST['task_description'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Evaluation Checklist <span class="text-muted small">(optional)</span></span>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="addChecklistItem">
                        <i class="fas fa-plus me-1"></i> Add Item
                    </button>
                </div>
                <div class="card-body" id="checklistContainer">
                    <p class="text-muted small text-center py-2" id="noChecklistMsg">
                        No checklist items. Add items the shopper should evaluate.
                    </p>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-3">
                <a href="<?= BASE_URL ?>mystery-shopping" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-check me-1"></i> Assign Task
                </button>
            </div>
        </div>
    </div>
</form>

<script>
// ─── Stores AJAX ────────────────────────────────────────────────────────────
document.getElementById('merchantSelect').addEventListener('change', function () {
    var mid = this.value;
    var sel = document.getElementById('storeSelect');
    sel.innerHTML = '<option value="">All stores / Not specified</option>';
    if (!mid) return;
    fetch('<?= BASE_URL ?>mystery-shopping/stores-json?merchant_id=' + mid)
        .then(function(r){ return r.json(); })
        .then(function(stores){
            stores.forEach(function(s){
                var opt = document.createElement('option');
                opt.value = s.id;
                opt.textContent = s.store_name + (s.address ? ' &ndash; ' + s.address.substring(0,40) : '');
                sel.appendChild(opt);
            });
        });
});

// ─── Checklist builder ───────────────────────────────────────────────────────
var container = document.getElementById('checklistContainer');
var noMsg     = document.getElementById('noChecklistMsg');

document.getElementById('addChecklistItem').addEventListener('click', function () {
    noMsg.style.display = 'none';
    var row = document.createElement('div');
    row.className = 'input-group input-group-sm mb-2';
    row.innerHTML =
        '<span class="input-group-text"><i class="fas fa-check-square text-muted"></i></span>' +
        '<input type="text" name="checklist_item[]" class="form-control" placeholder="e.g. Staff greeted within 30 seconds">' +
        '<button type="button" class="btn btn-outline-danger remove-item"><i class="fas fa-times"></i></button>';
    row.querySelector('.remove-item').addEventListener('click', function () {
        row.remove();
        if (!container.querySelectorAll('.input-group').length) noMsg.style.display = '';
    });
    container.appendChild(row);
});
</script>
