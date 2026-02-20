<?php
$isEdit    = !empty($data);
$pageTitle = $isEdit ? 'Edit Contest' : 'Create Contest';
$action    = $isEdit ? BASE_URL . 'contests/edit?id=' . $data['id'] : BASE_URL . 'contests/add';
$existingRules = $existingRules ?? [];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0"><?= $pageTitle ?></h5>
    <a href="<?= BASE_URL ?>contests" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Contests
    </a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" action="<?= $action ?>">
<div class="row g-4">

    <!-- Left: Contest Details -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold">Contest Details</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control"
                           value="<?= htmlspecialchars($_POST['title'] ?? $data['title'] ?? '') ?>"
                           placeholder="e.g. Best Selfie Contest" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"
                              placeholder="Brief description of the contest..."><?= htmlspecialchars($_POST['description'] ?? $data['description'] ?? '') ?></textarea>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Start Date</label>
                        <input type="datetime-local" name="start_date" class="form-control"
                               value="<?= htmlspecialchars(isset($data['start_date']) ? date('Y-m-d\TH:i', strtotime($data['start_date'])) : ($_POST['start_date'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">End Date</label>
                        <input type="datetime-local" name="end_date" class="form-control"
                               value="<?= htmlspecialchars(isset($data['end_date']) ? date('Y-m-d\TH:i', strtotime($data['end_date'])) : ($_POST['end_date'] ?? '')) ?>">
                    </div>
                </div>
                <?php if (!$isEdit): ?>
                <div class="mt-3">
                    <label class="form-label">Initial Status</label>
                    <select name="status" class="form-select">
                        <option value="draft" <?= ($_POST['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="active" <?= ($_POST['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                    </select>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right: Rules Builder -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Contest Rules</span>
                <button type="button" class="btn btn-sm btn-outline-primary" id="addRuleBtn">
                    <i class="bi bi-plus-circle me-1"></i>Add Rule
                </button>
            </div>
            <div class="card-body">
                <div id="rulesContainer">
                    <!-- Rules injected by JS -->
                </div>
                <p id="noRulesMsg" class="text-muted small mb-0 <?= !empty($existingRules) ? 'd-none' : '' ?>">
                    No rules added yet. Click "Add Rule" to start.
                </p>
            </div>
        </div>

        <!-- Submit -->
        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save me-1"></i><?= $isEdit ? 'Update Contest' : 'Create Contest' ?>
            </button>
            <a href="<?= BASE_URL ?>contests" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </div>

</div>
</form>

<script>
const existingRules = <?= json_encode($existingRules) ?>;

function addRule(text = '') {
    const container = document.getElementById('rulesContainer');
    document.getElementById('noRulesMsg').classList.add('d-none');
    const idx = container.children.length;
    const div = document.createElement('div');
    div.className = 'input-group mb-2';
    div.innerHTML = `
        <span class="input-group-text"><i class="bi bi-dot"></i></span>
        <input type="text" name="rule_item[]" class="form-control form-control-sm"
               placeholder="e.g. Participants must be 18+" value="${text.replace(/"/g, '&quot;')}" required>
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.input-group').remove(); updateEmpty();">
            <i class="bi bi-x"></i>
        </button>`;
    container.appendChild(div);
}

function updateEmpty() {
    const container = document.getElementById('rulesContainer');
    document.getElementById('noRulesMsg').classList.toggle('d-none', container.children.length > 0);
}

document.getElementById('addRuleBtn').addEventListener('click', () => addRule());

// Load existing rules on edit
existingRules.forEach(r => addRule(r));
</script>
