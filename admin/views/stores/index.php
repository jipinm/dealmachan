<?php /* views/stores/index.php */ ?>

<?php if ($flash_success): ?><div class="alert alert-success alert-dismissible fade show border-0 shadow-sm"><i class="fas fa-check-circle me-2"></i><?= escape($flash_success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<?php if ($flash_error):   ?><div class="alert alert-danger  alert-dismissible fade show border-0 shadow-sm"><i class="fas fa-exclamation-circle me-2"></i><?= escape($flash_error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

<!-- Page header -->
<div class="d-flex align-items-center justify-content-between mb-3">
    <h5 class="fw-bold mb-0">Store Management</h5>
</div>

<!-- Stats row -->
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center h-100" style="border-top:3px solid var(--bs-primary) !important;">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-primary"><?= number_format($stats['total']) ?></div>
                <div class="text-muted small">Total Stores</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center h-100" style="border-top:3px solid var(--bs-success) !important;">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-success"><?= number_format($stats['active']) ?></div>
                <div class="text-muted small">Active</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center h-100" style="border-top:3px solid var(--bs-secondary) !important;">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-secondary"><?= number_format($stats['inactive']) ?></div>
                <div class="text-muted small">Inactive</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center h-100" style="border-top:3px solid var(--bs-info) !important;">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-info"><?= number_format($stats['booking_enabled']) ?></div>
                <div class="text-muted small">Booking Enabled</div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2 px-3">
        <form method="GET" action="<?= BASE_URL ?>stores" class="row g-2 align-items-center">
            <div class="col-auto flex-grow-1">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Search by store name, address, phone, email…"
                       value="<?= escape($filters['search']) ?>">
            </div>
            <div class="col-auto">
                <select name="merchant_id" class="form-select form-select-sm">
                    <option value="">All Merchants</option>
                    <?php foreach ($merchants as $m): ?>
                    <option value="<?= $m['id'] ?>" <?= (string)$filters['merchant_id'] === (string)$m['id'] ? 'selected' : '' ?>>
                        <?= escape($m['business_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <select name="city_id" class="form-select form-select-sm">
                    <option value="">All Cities</option>
                    <?php foreach ($cities as $city): ?>
                    <option value="<?= $city['id'] ?>" <?= (string)$filters['city_id'] === (string)$city['id'] ? 'selected' : '' ?>>
                        <?= escape($city['city_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="active"   <?= $filters['status'] === 'active'   ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $filters['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div class="col-auto d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search me-1"></i> Filter</button>
                <a href="<?= BASE_URL ?>stores" class="btn btn-sm btn-light">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive p-3">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Store</th>
                        <th>Merchant</th>
                        <th>City / Area</th>
                        <th>Contact</th>
                        <th class="text-center">Booking</th>
                        <th class="text-center">Status</th>
                        <th>Created</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($stores)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">No stores found.</td>
                    </tr>
                <?php else: ?>
                    <?php $rowNum = ($currentPage - 1) * $perPage + 1; ?>
                    <?php foreach ($stores as $store): ?>
                    <tr>
                        <td class="text-muted small"><?= $rowNum++ ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <?php if (!empty($store['store_image'])): ?>
                                <img src="<?= BASE_URL ?>../api/<?= escape($store['store_image']) ?>"
                                     class="rounded" style="width:36px;height:36px;object-fit:cover;" alt="">
                                <?php else: ?>
                                <div class="rounded bg-light d-flex align-items-center justify-content-center"
                                     style="width:36px;height:36px;flex-shrink:0;">
                                    <i class="fas fa-store text-muted small"></i>
                                </div>
                                <?php endif; ?>
                                <div>
                                    <div class="fw-semibold"><?= escape($store['store_name']) ?></div>
                                    <?php if (!empty($store['address'])): ?>
                                    <div class="text-muted small text-truncate" style="max-width:180px;"><?= escape($store['address']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <a href="<?= BASE_URL ?>merchants/profile?id=<?= $store['merchant_id'] ?>" class="text-decoration-none">
                                <?= escape($store['business_name'] ?? '—') ?>
                            </a>
                        </td>
                        <td>
                            <div><?= escape($store['city_name']  ?? '—') ?></div>
                            <div class="text-muted small"><?= escape($store['area_name']  ?? '') ?></div>
                        </td>
                        <td>
                            <?php if (!empty($store['phone'])): ?>
                            <div class="small"><i class="fas fa-phone fa-xs text-muted me-1"></i><?= escape($store['phone']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($store['email'])): ?>
                            <div class="small"><i class="fas fa-envelope fa-xs text-muted me-1"></i><?= escape($store['email']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($store['booking_enabled']): ?>
                            <span class="badge bg-info-subtle text-info border border-info-subtle">
                                <i class="fas fa-calendar-check me-1"></i>Yes
                            </span>
                            <?php else: ?>
                            <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <form method="POST" action="<?= BASE_URL ?>stores/toggle" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token'] ?? '') ?>">
                                <input type="hidden" name="id" value="<?= $store['id'] ?>">
                                <input type="hidden" name="redirect" value="stores?<?= escape(http_build_query(array_filter($filters))) ?>">
                                <button type="submit" class="btn btn-sm <?= $store['status'] === 'active' ? 'btn-success' : 'btn-secondary' ?> px-2 py-0"
                                        title="Click to toggle status">
                                    <?= $store['status'] === 'active' ? 'Active' : 'Inactive' ?>
                                </button>
                            </form>
                        </td>
                        <td class="text-muted small"><?= date('d M Y', strtotime($store['created_at'])) ?></td>
                        <td class="text-center">
                            <a href="<?= BASE_URL ?>merchants/edit-store?id=<?= $store['id'] ?>"
                               class="btn btn-sm btn-outline-primary px-2 py-0" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="d-flex align-items-center justify-content-between px-3 pb-3">
            <div class="text-muted small">
                Showing <?= number_format(($currentPage - 1) * $perPage + 1) ?>–<?= number_format(min($currentPage * $perPage, $totalCount)) ?>
                of <?= number_format($totalCount) ?> stores
            </div>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge(array_filter($filters), ['page' => $currentPage - 1])) ?>">«</a>
                    </li>
                    <?php
                    $start = max(1, $currentPage - 2);
                    $end   = min($totalPages, $currentPage + 2);
                    for ($p = $start; $p <= $end; $p++): ?>
                    <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge(array_filter($filters), ['page' => $p])) ?>"><?= $p ?></a>
                    </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge(array_filter($filters), ['page' => $currentPage + 1])) ?>">»</a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>
