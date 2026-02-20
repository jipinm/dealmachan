<?php $pageTitle = 'Create Survey'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0">Create New Survey</h5>
    <a href="<?= BASE_URL ?>surveys" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i> Back
    </a>
</div>

<form method="POST" action="<?= BASE_URL ?>surveys/add" id="surveyForm">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

    <div class="row g-4">
        <!-- Left: Survey Details -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent fw-semibold">Survey Details</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" required
                               placeholder="e.g. Customer Satisfaction Q1 2026"
                               value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="3"
                                  placeholder="Brief description shown to respondents…"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            <option value="draft"  <?= ($_POST['status'] ?? 'draft')==='draft'  ? 'selected':'' ?>>Draft</option>
                            <option value="active" <?= ($_POST['status'] ?? '')==='active' ? 'selected':'' ?>>Active</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Active From</label>
                        <input type="datetime-local" name="active_from" class="form-control"
                               value="<?= htmlspecialchars($_POST['active_from'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Active Until</label>
                        <input type="datetime-local" name="active_until" class="form-control"
                               value="<?= htmlspecialchars($_POST['active_until'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Question Builder -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Questions</span>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="addQuestionBtn">
                        <i class="fas fa-plus me-1"></i> Add Question
                    </button>
                </div>
                <div class="card-body" id="questionsContainer">
                    <p class="text-muted text-center py-3" id="noQuestionsMsg">
                        No questions yet. Click "Add Question" to begin.
                    </p>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-3">
                <a href="<?= BASE_URL ?>surveys" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Save Survey
                </button>
            </div>
        </div>
    </div>
</form>

<!-- Question template (hidden) -->
<template id="questionTemplate">
    <div class="question-block border rounded p-3 mb-3 bg-light">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="fw-semibold text-muted small">Question <span class="q-num"></span></span>
            <button type="button" class="btn btn-outline-danger btn-sm remove-question">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="row g-2 mb-2">
            <div class="col-sm-8">
                <input type="text" name="q_question[]" class="form-control form-control-sm q-text"
                       placeholder="Enter question text…" required>
            </div>
            <div class="col-sm-3">
                <select name="q_type[]" class="form-select form-select-sm q-type">
                    <option value="text">Short Text</option>
                    <option value="textarea">Long Text</option>
                    <option value="radio">Multiple Choice</option>
                    <option value="checkbox">Checkboxes</option>
                    <option value="select">Dropdown</option>
                    <option value="rating">Rating</option>
                </select>
            </div>
            <div class="col-sm-1 d-flex align-items-center justify-content-center">
                <div class="form-check mb-0" title="Required">
                    <input class="form-check-input q-required" type="checkbox" name="q_required[]" value="1">
                </div>
            </div>
        </div>
        <!-- Options (shown for radio/checkbox/select) -->
        <div class="options-section d-none">
            <label class="form-label small fw-semibold">Options</label>
            <div class="options-list"></div>
            <button type="button" class="btn btn-outline-secondary btn-sm add-option mt-1">
                <i class="fas fa-plus me-1"></i> Add Option
            </button>
        </div>
        <!-- Rating scale (shown for rating) -->
        <div class="rating-section d-none">
            <label class="form-label small fw-semibold">Scale (max)</label>
            <select name="q_scale_QIDX" class="form-select form-select-sm" style="width:100px">
                <option value="3">3</option>
                <option value="5" selected>5</option>
                <option value="10">10</option>
            </select>
        </div>
    </div>
</template>

<script>
(function () {
    var container   = document.getElementById('questionsContainer');
    var noMsg       = document.getElementById('noQuestionsMsg');
    var template    = document.getElementById('questionTemplate');
    var addBtn      = document.getElementById('addQuestionBtn');
    var qCount      = 0;

    function updateNumbers() {
        container.querySelectorAll('.question-block').forEach(function (block, i) {
            block.querySelector('.q-num').textContent = i + 1;
        });
        noMsg.style.display = container.querySelectorAll('.question-block').length ? 'none' : '';
    }

    function addOption(optList, qIdx) {
        var idx  = optList.querySelectorAll('.option-row').length;
        var row  = document.createElement('div');
        row.className = 'input-group input-group-sm mb-1 option-row';
        row.innerHTML =
            '<input type="text" name="q_options_' + qIdx + '[]" class="form-control" placeholder="Option ' + (idx + 1) + '">' +
            '<button type="button" class="btn btn-outline-danger remove-option"><i class="fas fa-times"></i></button>';
        row.querySelector('.remove-option').addEventListener('click', function () {
            row.remove();
        });
        optList.appendChild(row);
    }

    addBtn.addEventListener('click', function () {
        var frag  = template.content.cloneNode(true);
        var block = frag.querySelector('.question-block');
        var idx   = qCount++;

        // Rename options field
        block.querySelector('.options-section').dataset.qidx = idx;
        var ratingSelect = block.querySelector('.rating-section select');
        ratingSelect.name = 'q_scale_' + idx;

        // Type change handler
        block.querySelector('.q-type').addEventListener('change', function () {
            var type        = this.value;
            var optSection  = block.querySelector('.options-section');
            var ratSection  = block.querySelector('.rating-section');
            if (['radio','checkbox','select'].includes(type)) {
                optSection.classList.remove('d-none');
                ratSection.classList.add('d-none');
                if (optSection.querySelector('.options-list').children.length === 0) {
                    addOption(optSection.querySelector('.options-list'), idx);
                    addOption(optSection.querySelector('.options-list'), idx);
                }
            } else if (type === 'rating') {
                ratSection.classList.remove('d-none');
                optSection.classList.add('d-none');
            } else {
                optSection.classList.add('d-none');
                ratSection.classList.add('d-none');
            }
        });

        block.querySelector('.add-option').addEventListener('click', function () {
            addOption(block.querySelector('.options-list'), idx);
        });

        block.querySelector('.remove-question').addEventListener('click', function () {
            block.remove();
            updateNumbers();
        });

        container.appendChild(frag);
        updateNumbers();
    });
})();
</script>
