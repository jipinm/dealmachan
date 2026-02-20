<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-0">Assign Card</h4>
        <small class="text-muted"><a href="<?= BASE_URL ?>/cards">Cards</a> / Assign</small>
    </div>
    <a href="<?= BASE_URL ?>/cards" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header fw-semibold"><i class="fas fa-user-tag me-2 text-success"></i>Assign Available Card</div>
            <div class="card-body">
                <?php if (empty($available)): ?>
                    <div class="alert alert-warning mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        No available cards to assign. <a href="<?= BASE_URL ?>/cards/generate">Generate cards first.</a>
                    </div>
                <?php else: ?>
                <form method="POST" action="<?= BASE_URL ?>/cards/assign" id="assignForm">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                    <!-- Select Card -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Select Card <span class="text-danger">*</span></label>
                        <select name="card_id" class="form-select select2-single" data-placeholder="Search card number…" required>
                            <option value="">— Select Card —</option>
                            <?php foreach ($available as $avail): ?>
                                <option value="<?= $avail['id'] ?>">
                                    <?= escape($avail['card_number']) ?> (<?= ucfirst($avail['card_variant']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text"><?= count($available) ?> available cards</div>
                    </div>

                    <!-- Assign To -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Assign To <span class="text-danger">*</span></label>
                        <div class="d-flex gap-3 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="assign_type" id="typeCustomer" value="customer" checked onchange="toggleAssignType()">
                                <label class="form-check-label" for="typeCustomer"><i class="fas fa-user me-1"></i> Customer</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="assign_type" id="typeMerchant" value="merchant" onchange="toggleAssignType()">
                                <label class="form-check-label" for="typeMerchant"><i class="fas fa-store me-1"></i> Merchant</label>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Search -->
                    <div id="customerSection" class="mb-4">
                        <label class="form-label">Customer <span class="text-danger">*</span></label>
                        <input type="text" id="customerSearch" class="form-control" placeholder="Search by name, phone or email…" autocomplete="off">
                        <div id="customerResults" class="list-group mt-1" style="display:none; position:relative; z-index:100;"></div>
                        <input type="hidden" name="customer_id" id="customerId">
                        <div id="selectedCustomer" class="mt-2 d-none">
                            <span class="badge bg-success px-3 py-2" id="selectedCustomerName"></span>
                            <button type="button" class="btn btn-sm btn-link text-danger p-0 ms-2" onclick="clearCustomer()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Merchant Dropdown -->
                    <div id="merchantSection" class="mb-4" style="display:none">
                        <label class="form-label">Merchant <span class="text-danger">*</span></label>
                        <select name="merchant_id" id="merchant_id" class="form-select select2-single" data-placeholder="Select merchant…">
                            <option value="">— Select Merchant —</option>
                            <?php foreach ($merchants as $m): ?>
                                <option value="<?= $m['id'] ?>"><?= escape($m['business_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-success w-100" id="submitBtn">
                        <i class="fas fa-check me-2"></i> Assign Card
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
const ASSIGN_SEARCH_URL = '<?= BASE_URL ?>/cards/assign';

function toggleAssignType() {
    const type = document.querySelector('input[name="assign_type"]:checked').value;
    document.getElementById('customerSection').style.display = type === 'customer' ? '' : 'none';
    document.getElementById('merchantSection').style.display = type === 'merchant' ? '' : 'none';
}

let searchTimeout;
document.getElementById('customerSearch').addEventListener('input', function () {
    clearTimeout(searchTimeout);
    const q = this.value.trim();
    if (q.length < 2) { hideResults(); return; }
    searchTimeout = setTimeout(() => searchCustomers(q), 300);
});

function searchCustomers(q) {
    fetch(`${ASSIGN_SEARCH_URL}?customer_search=${encodeURIComponent(q)}`)
        .then(r => r.json())
        .then(customers => {
            const box = document.getElementById('customerResults');
            box.innerHTML = '';
            if (!customers.length) {
                box.innerHTML = '<div class="list-group-item text-muted">No customers found.</div>';
            } else {
                customers.forEach(c => {
                    const item = document.createElement('a');
                    item.className = 'list-group-item list-group-item-action';
                    item.href = '#';
                    item.innerHTML = `<strong>${c.full_name}</strong> <small class="text-muted">${c.phone ?? ''}</small>`;
                    item.addEventListener('click', function(e) {
                        e.preventDefault();
                        selectCustomer(c);
                    });
                    box.appendChild(item);
                });
            }
            box.style.display = '';
        });
}

function selectCustomer(c) {
    document.getElementById('customerId').value = c.id;
    document.getElementById('customerSearch').value = '';
    document.getElementById('selectedCustomerName').textContent = `${c.full_name} (${c.phone ?? c.email ?? ''})`;
    document.getElementById('selectedCustomer').classList.remove('d-none');
    hideResults();
}

function clearCustomer() {
    document.getElementById('customerId').value = '';
    document.getElementById('selectedCustomer').classList.add('d-none');
}

function hideResults() {
    document.getElementById('customerResults').style.display = 'none';
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('#customerSearch') && !e.target.closest('#customerResults')) hideResults();
});

document.getElementById('assignForm')?.addEventListener('submit', function(e) {
    const type = document.querySelector('input[name="assign_type"]:checked').value;
    if (type === 'customer' && !document.getElementById('customerId').value) {
        e.preventDefault();
        Swal.fire({ icon: 'warning', title: 'Select Customer', text: 'Please search and select a customer.' });
    }
});

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.select2-single').forEach(el => {
        $(el).select2({ theme: 'bootstrap-5', placeholder: el.dataset.placeholder, allowClear: true });
    });
});
</script>
