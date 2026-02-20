<?php $pageTitle = 'Edit Survey'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0">Edit Survey</h5>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>surveys/responses?id=<?= $survey['id'] ?>" class="btn btn-outline-info btn-sm">
            <i class="fas fa-chart-bar me-1"></i> Responses (<?= (int)$survey['response_count'] ?>)
        </a>
        <a href="<?= BASE_URL ?>surveys" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<form method="POST" action="<?= BASE_URL ?>surveys/edit?id=<?= $survey['id'] ?>" id="surveyForm">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent fw-semibold">Survey Details</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" required
                               value="<?= htmlspecialchars($survey['title']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($survey['description'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            <option value="draft"  <?= $survey['status']==='draft'  ? 'selected':'' ?>>Draft</option>
                            <option value="active" <?= $survey['status']==='active' ? 'selected':'' ?>>Active</option>
                            <option value="closed" <?= $survey['status']==='closed' ? 'selected':'' ?>>Closed</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Active From</label>
                        <input type="datetime-local" name="active_from" class="form-control"
                               value="<?= $survey['active_from'] ? date('Y-m-d\TH:i', strtotime($survey['active_from'])) : '' ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Active Until</label>
                        <input type="datetime-local" name="active_until" class="form-control"
                               value="<?= $survey['active_until'] ? date('Y-m-d\TH:i', strtotime($survey['active_until'])) : '' ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Questions</span>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="addQuestionBtn">
                        <i class="fas fa-plus me-1"></i> Add Question
                    </button>
                </div>
                <div class="card-body" id="questionsContainer">
                    <p class="text-muted text-center py-3" id="noQuestionsMsg" style="display:none">
                        No questions yet. Click "Add Question" to begin.
                    </p>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-3">
                <a href="<?= BASE_URL ?>surveys" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Save Changes
                </button>
            </div>
        </div>
    </div>
</form>

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
        <div class="options-section d-none">
            <label class="form-label small fw-semibold">Options</label>
            <div class="options-list"></div>
            <button type="button" class="btn btn-outline-secondary btn-sm add-option mt-1">
                <i class="fas fa-plus me-1"></i> Add Option
            </button>
        </div>
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

<!-- Existing questions data -->
<script>
var existingQuestions = <?= json_encode($questions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

(function () {
    var container = document.getElementById('questionsContainer');
    var noMsg     = document.getElementById('noQuestionsMsg');
    var template  = document.getElementById('questionTemplate');
    var addBtn    = document.getElementById('addQuestionBtn');
    var qCount    = 0;

    function updateNumbers() {
        container.querySelectorAll('.question-block').forEach(function (block, i) {
            block.querySelector('.q-num').textContent = i + 1;
        });
        var visible = container.querySelectorAll('.question-block').length;
        noMsg.style.display = visible ? 'none' : '';
    }

    function addOption(optList, qIdx, value) {
        var idx  = optList.querySelectorAll('.option-row').length;
        var row  = document.createElement('div');
        row.className = 'input-group input-group-sm mb-1 option-row';
        row.innerHTML =
            '<input type="text" name="q_options_' + qIdx + '[]" class="form-control" placeholder="Option ' + (idx + 1) + '" value="' + (value ? value.replace(/"/g,'&quot;') : '') + '">' +
            '<button type="button" class="btn btn-outline-danger remove-option"><i class="fas fa-times"></i></button>';
        row.querySelector('.remove-option').addEventListener('click', function () { row.remove(); });
        optList.appendChild(row);
    }

    function createBlock(q, idx) {
        var frag  = template.content.cloneNode(true);
        var block = frag.querySelector('.question-block');

        block.querySelector('.q-text').value = q.question || '';
        block.querySelector('.q-required').checked = !!q.required;

        var typeSelect = block.querySelector('.q-type');
        typeSelect.value = q.type || 'text';

        var ratingSelect = block.querySelector('.rating-section select');
        ratingSelect.name = 'q_scale_' + idx;
        if (q.scale) ratingSelect.value = String(q.scale);

        var optSection = block.querySelector('.options-section');
        var ratSection = block.querySelector('.rating-section');
        optSection.dataset.qidx = idx;

        function showType(type) {
            if (['radio','checkbox','select'].includes(type)) {
                optSection.classList.remove('d-none');
                ratSection.classList.add('d-none');
            } else if (type === 'rating') {
                ratSection.classList.remove('d-none');
                optSection.classList.add('d-none');
            } else {
                optSection.classList.add('d-none');
                ratSection.classList.add('d-none');
            }
        }

        showType(q.type || 'text');

        var optList = optSection.querySelector('.options-list');
        if (q.options && q.options.length) {
            q.options.forEach(function (opt) { addOption(optList, idx, opt); });
        }

        typeSelect.addEventListener('change', function () {
            showType(this.value);
            if (['radio','checkbox','select'].includes(this.value) && optList.children.length === 0) {
                addOption(optList, idx, '');
                addOption(optList, idx, '');
            }
        });

        block.querySelector('.add-option').addEventListener('click', function () {
            addOption(optList, idx, '');
        });

        block.querySelector('.remove-question').addEventListener('click', function () {
            block.remove(); updateNumbers();
        });

        return block;
    }

    addBtn.addEventListener('click', function () {
        var idx   = qCount++;
        var block = createBlock({}, idx);
        container.appendChild(block);
        updateNumbers();
    });

    // Load existing questions
    existingQuestions.forEach(function (q) {
        var idx   = qCount++;
        var block = createBlock(q, idx);
        container.appendChild(block);
    });
    updateNumbers();
})();
</script>
