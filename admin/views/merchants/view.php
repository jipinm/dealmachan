<?php /* views/merchants/view.php */
$profileColors = ['approved'=>'success','pending'=>'warning','rejected'=>'danger'];
$subsColors    = ['active'=>'success','trial'=>'info','expired'=>'secondary'];
$statusColors  = ['active'=>'success','inactive'=>'secondary','blocked'=>'danger','pending'=>'warning'];
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>merchants">Merchants</a></li>
        <li class="breadcrumb-item active"><?= escape($merchant['business_name']) ?></li>
    </ol>
</nav>

<?php if (!empty($_SESSION['success'])): ?><div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3"><i class="fas fa-check-circle me-2"></i><?= escape($_SESSION['success']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php unset($_SESSION['success']); endif; ?>
<?php if (!empty($_SESSION['error'])):   ?><div class="alert alert-danger  alert-dismissible fade show border-0 shadow-sm mb-3"><i class="fas fa-exclamation-circle me-2"></i><?= escape($_SESSION['error']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php unset($_SESSION['error']); endif; ?>

<div class="row g-4">

    <!-- LEFT: Main profile -->
    <div class="col-lg-8">

        <!-- Header card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <div class="rounded bg-primary d-flex align-items-center justify-content-center text-white fw-bold flex-shrink-0"
                         style="width:64px;height:64px;font-size:1.4rem; overflow:hidden;">
                        <?php if (!empty($merchant['business_logo'])): ?>
                            <img src="<?= imageUrl($merchant['business_logo']) ?>" 
                                 alt="<?= escape($merchant['business_name']) ?>" 
                                 class="w-100 h-100 object-fit-cover"
                                 onerror="this.parentElement.innerHTML='<?= strtoupper(mb_substr($merchant['business_name'] ?? 'M', 0, 2)) ?>'">
                        <?php else: ?>
                            <?= strtoupper(mb_substr($merchant['business_name'] ?? 'M', 0, 2)) ?>
                        <?php endif; ?>
                    </div>
                    <div class="flex-grow-1">
                        <h2 class="h4 mb-1">
                            <?= escape($merchant['business_name']) ?>
                            <?php if ($merchant['is_premium']): ?>
                            <span class="badge ms-1 text-white" style="background:#6f42c1;">Premium</span>
                            <?php endif; ?>
                        </h2>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge bg-<?= $profileColors[$merchant['profile_status']] ?? 'secondary' ?>">
                                <?= ucfirst($merchant['profile_status']) ?>
                            </span>
                            <span class="badge bg-<?= $subsColors[$merchant['subscription_status']] ?? 'secondary' ?>">
                                <?= ucfirst($merchant['subscription_status']) ?>
                            </span>
                            <span class="badge bg-<?= $statusColors[$merchant['status']] ?? 'secondary' ?>">
                                <?= ucfirst($merchant['status']) ?>
                            </span>
                            <?php if ($merchant['label_name']): ?>
                            <span class="badge bg-info-subtle text-info border border-info-subtle">
                                <?= escape($merchant['label_name']) ?>
                            </span>
                            <?php endif; ?>
                            <?php foreach ($labels as $lbl): ?>
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                <?= escape($lbl['label_name']) ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="d-flex gap-2 flex-shrink-0">
                        <a href="<?= BASE_URL ?>merchants/edit?id=<?= $merchant['id'] ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-edit me-1"></i> Edit
                        </a>
                        <?php if ($merchant['profile_status'] === 'pending'): ?>
                        <form method="POST" action="<?= BASE_URL ?>merchants/approve" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            <input type="hidden" name="id" value="<?= $merchant['id'] ?>">
                            <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-check me-1"></i>Approve</button>
                        </form>
                        <form method="POST" action="<?= BASE_URL ?>merchants/reject" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            <input type="hidden" name="id" value="<?= $merchant['id'] ?>">
                            <button type="submit" class="btn btn-warning btn-sm"><i class="fas fa-times me-1"></i>Reject</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Business Details -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold border-bottom">
                <i class="fas fa-info-circle me-2 text-primary"></i> Business Details
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">Email</div>
                        <div><?= $merchant['email'] ? escape($merchant['email']) : '<span class="text-muted">&mdash;</span>' ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">Phone</div>
                        <div><?= $merchant['phone'] ? escape($merchant['phone']) : '<span class="text-muted">&mdash;</span>' ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">Registration Number</div>
                        <div><?= $merchant['registration_number'] ? escape($merchant['registration_number']) : '<span class="text-muted">&mdash;</span>' ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">GST Number</div>
                        <div><?= $merchant['gst_number'] ? escape($merchant['gst_number']) : '<span class="text-muted">&mdash;</span>' ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">Subscription Expiry</div>
                        <div><?= $merchant['subscription_expiry'] ? formatDate($merchant['subscription_expiry']) : '<span class="text-muted">&mdash;</span>' ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">Priority Weight</div>
                        <div><?= (int)$merchant['priority_weight'] ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">Registered</div>
                        <div><?= isset($merchant['registered_at']) ? formatDate($merchant['registered_at']) : '&mdash;' ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">Last Login</div>
                        <div><?= $merchant['last_login'] ? formatDateTime($merchant['last_login']) : '<span class="text-muted">Never</span>' ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stores -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold border-bottom d-flex align-items-center justify-content-between">
                <span><i class="fas fa-map-marker-alt me-2 text-primary"></i> Stores (<?= count($stores) ?>)</span>
                <a href="<?= BASE_URL ?>merchants/add-store?merchant_id=<?= $merchant['id'] ?>" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-plus me-1"></i> Add Store
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($stores)): ?>
                <div class="text-center text-muted py-4"><i class="fas fa-store fa-2x mb-2 d-block opacity-25"></i>No stores added yet.</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Store Name</th>
                                <th>Location</th>
                                <th>Contact</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stores as $store): ?>
                            <tr>
                                <td class="fw-semibold"><?= escape($store['store_name']) ?></td>
                                <td>
                                    <div class="small"><?= escape($store['area_name']) ?>, <?= escape($store['city_name']) ?></div>
                                    <div class="text-muted small text-truncate" style="max-width:220px;"><?= escape($store['address']) ?></div>
                                </td>
                                <td>
                                    <?php if ($store['phone']): ?><div class="small"><?= escape($store['phone']) ?></div><?php endif; ?>
                                    <?php if ($store['email']): ?><div class="text-muted small"><?= escape($store['email']) ?></div><?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <form method="POST" action="<?= BASE_URL ?>merchants/toggle-store" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                        <input type="hidden" name="id" value="<?= $store['id'] ?>">
                                        <input type="hidden" name="merchant_id" value="<?= $merchant['id'] ?>">
                                        <button type="submit" class="badge border-0 bg-<?= $store['status'] === 'active' ? 'success' : 'secondary' ?> p-2"
                                                style="cursor:pointer;"><?= ucfirst($store['status']) ?></button>
                                    </form>
                                </td>
                                <td class="text-center text-nowrap">
                                    <a href="<?= BASE_URL ?>merchants/edit-store?id=<?= $store['id'] ?>" class="btn btn-sm btn-outline-primary me-1" title="Edit"><i class="fas fa-edit"></i></a>
                                    <button class="btn btn-sm btn-outline-danger" onclick="confirmDeleteStore(<?= $store['id'] ?>, <?= $merchant['id'] ?>, '<?= escape($store['store_name']) ?>')" title="Delete"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <?php
                                // Opening Hours row (collapsible)
                                $hours = null;
                                if (!empty($store['opening_hours'])) {
                                    $hours = is_string($store['opening_hours']) ? json_decode($store['opening_hours'], true) : $store['opening_hours'];
                                }
                                if ($hours && is_array($hours)):
                            ?>
                            <tr class="table-light">
                                <td colspan="5" class="py-2 px-3">
                                    <div class="d-flex align-items-start gap-2">
                                        <i class="fas fa-clock text-muted mt-1"></i>
                                        <div class="d-flex flex-wrap gap-3 small">
                                            <?php
                                            $dayLabels = ['monday'=>'Mon','tuesday'=>'Tue','wednesday'=>'Wed','thursday'=>'Thu','friday'=>'Fri','saturday'=>'Sat','sunday'=>'Sun'];
                                            foreach ($dayLabels as $dayKey => $dayLabel):
                                                $dayData = $hours[$dayKey] ?? null;
                                                if (!$dayData) continue;
                                            ?>
                                            <span>
                                                <strong><?= $dayLabel ?>:</strong>
                                                <?php if (!empty($dayData['closed'])): ?>
                                                    <span class="text-danger">Closed</span>
                                                <?php else: ?>
                                                    <?= escape($dayData['open'] ?? '&mdash;') ?>&ndash;<?= escape($dayData['close'] ?? '&mdash;') ?>
                                                <?php endif; ?>
                                            </span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Reviews -->
        <?php if (!empty($reviews)): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold border-bottom">
                <i class="fas fa-star me-2 text-warning"></i>
                Recent Reviews
                <?php if ($merchant['avg_rating']): ?>
                <span class="ms-2 badge bg-warning text-dark"><?= $merchant['avg_rating'] ?> / 5</span>
                <span class="ms-1 text-muted small">(<?= $merchant['review_count'] ?>)</span>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Customer</th><th>Rating</th><th>Review</th><th>Status</th><th>Date</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reviews as $rev): ?>
                            <tr>
                                <td><?= escape($rev['customer_name']) ?></td>
                                <td>
                                    <?php for ($s = 1; $s <= 5; $s++): ?>
                                    <i class="fas fa-star<?= $s > $rev['rating'] ? '-half-alt' : '' ?> text-warning small"
                                       style="font-size:.8rem;<?= $s > $rev['rating'] ? 'opacity:.3' : '' ?>"></i>
                                    <?php endfor; ?>
                                    <span class="ms-1 small"><?= $rev['rating'] ?>/5</span>
                                </td>
                                <td class="text-muted small"><?= escape(mb_substr($rev['review_text'] ?? '', 0, 100)) ?><?= mb_strlen($rev['review_text'] ?? '') > 100 ? '&hellip;' : '' ?></td>
                                <td><span class="badge bg-<?= $rev['status'] === 'approved' ? 'success' : ($rev['status'] === 'rejected' ? 'danger' : 'warning') ?>"><?= ucfirst($rev['status']) ?></span></td>
                                <td class="text-muted small"><?= formatDate($rev['created_at']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Store Gallery -->
        <?php if (!empty($gallery)): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-semibold"><i class="fas fa-images me-2 text-primary"></i> Store Gallery</span>
            </div>
            <div class="card-body">
                <?php foreach ($gallery as $storeId => $storeData): ?>
                <h6 class="text-muted mb-2 mt-3 small text-uppercase fw-semibold"><?= escape($storeData['store_name']) ?></h6>
                <div class="row g-2 mb-3">
                    <?php foreach ($storeData['images'] as $img): ?>
                    <div class="col-6 col-sm-4 col-md-3" id="gallery-img-<?= $img['id'] ?>">
                        <div class="card border position-relative h-100">
                            <?php if ($img['is_cover']): ?>
                            <span class="badge bg-success position-absolute top-0 start-0 m-1" style="font-size:0.65rem;z-index:1;">Cover</span>
                            <?php endif; ?>
                            <img src="<?= imageUrl($img['image_url']) ?>" alt="<?= escape($img['caption'] ?? '') ?>"
                                 class="card-img-top object-fit-cover" style="height:100px;"
                                 onerror="this.src='<?= imageUrl('') ?>'">
                            <?php if ($img['caption']): ?>
                            <div class="card-body p-1"><small class="text-muted"><?= escape($img['caption']) ?></small></div>
                            <?php endif; ?>
                            <div class="card-footer p-1 d-flex gap-1 justify-content-end bg-transparent border-top">
                                <?php if (!$img['is_cover']): ?>
                                <button class="btn btn-xs btn-outline-success" style="font-size:0.7rem;padding:1px 5px;"
                                        onclick="setCover(<?= $img['id'] ?>, <?= $storeId ?>, <?= $merchant['id'] ?>)"
                                        title="Set as Cover">
                                    <i class="bi bi-star"></i>
                                </button>
                                <?php endif; ?>
                                <button class="btn btn-xs btn-outline-danger" style="font-size:0.7rem;padding:1px 5px;"
                                        onclick="deleteGalleryImg(<?= $img['id'] ?>, <?= $merchant['id'] ?>)"
                                        title="Delete Image">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <script>
        function deleteGalleryImg(imageId, merchantId) {
            Swal.fire({
                title: 'Delete image?', icon: 'warning',
                showCancelButton: true, confirmButtonColor: '#dc3545', confirmButtonText: 'Delete'
            }).then(r => {
                if (!r.isConfirmed) return;
                fetch('<?= BASE_URL ?>merchants/delete-gallery-image', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'image_id=' + imageId + '&merchant_id=' + merchantId
                }).then(r => r.json()).then(d => {
                    if (d.success) {
                        const el = document.getElementById('gallery-img-' + imageId);
                        if (el) el.remove();
                        Swal.fire({icon:'success', title:'Deleted', timer:1500, showConfirmButton:false});
                    } else {
                        Swal.fire('Error', d.error || 'Failed.', 'error');
                    }
                });
            });
        }
        function setCover(imageId, storeId, merchantId) {
            fetch('<?= BASE_URL ?>merchants/set-cover-image', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'image_id=' + imageId + '&store_id=' + storeId + '&merchant_id=' + merchantId
            }).then(r => r.json()).then(d => {
                if (d.success) {
                    Swal.fire({icon:'success', title:'Cover updated', timer:1500, showConfirmButton:false})
                        .then(() => location.reload());
                } else {
                    Swal.fire('Error', d.error || 'Failed.', 'error');
                }
            });
        }
        </script>
        <?php endif; ?>

    </div>

    <!-- RIGHT: Sidebar -->
    <div class="col-lg-4">

        <!-- Quick Stats -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold border-bottom">
                <i class="fas fa-chart-bar me-2 text-primary"></i> Quick Stats
            </div>
            <div class="card-body">
                <div class="row g-3 text-center">
                    <div class="col-6">
                        <div class="fs-3 fw-bold text-primary"><?= (int)$merchant['store_count'] ?></div>
                        <div class="text-muted small">Stores</div>
                    </div>
                    <div class="col-6">
                        <div class="fs-3 fw-bold text-success"><?= (int)$merchant['coupon_count'] ?></div>
                        <div class="text-muted small">Coupons</div>
                    </div>
                    <div class="col-6">
                        <div class="fs-3 fw-bold text-warning"><?= $merchant['avg_rating'] ?: '&mdash;' ?></div>
                        <div class="text-muted small">Avg Rating</div>
                    </div>
                    <div class="col-6">
                        <div class="fs-3 fw-bold text-info"><?= (int)$merchant['review_count'] ?></div>
                        <div class="text-muted small">Reviews</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold border-bottom">
                <i class="fas fa-bolt me-2 text-primary"></i> Quick Actions
            </div>
            <div class="card-body d-grid gap-2">
                <a href="<?= BASE_URL ?>merchants/add-store?merchant_id=<?= $merchant['id'] ?>" class="btn btn-outline-primary btn-sm text-start">
                    <i class="fas fa-plus me-2"></i> Add Store
                </a>
                <a href="<?= BASE_URL ?>merchants/edit?id=<?= $merchant['id'] ?>" class="btn btn-outline-secondary btn-sm text-start">
                    <i class="fas fa-edit me-2"></i> Edit Profile
                </a>
                <form method="POST" action="<?= BASE_URL ?>merchants/toggle">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="id" value="<?= $merchant['id'] ?>">
                    <input type="hidden" name="redirect" value="merchants/profile?id=<?= $merchant['id'] ?>">
                    <button type="submit" class="btn btn-outline-<?= $merchant['status'] === 'active' ? 'warning' : 'success' ?> btn-sm text-start w-100">
                        <i class="fas fa-<?= $merchant['status'] === 'active' ? 'ban' : 'check-circle' ?> me-2"></i>
                        <?= $merchant['status'] === 'active' ? 'Block Account' : 'Unblock Account' ?>
                    </button>
                </form>
            </div>
        </div>

        <!-- Danger Zone -->
        <div class="card border-danger border-opacity-25 shadow-sm mb-4">
            <div class="card-header bg-danger-subtle text-danger fw-semibold border-bottom border-danger border-opacity-25">
                <i class="fas fa-exclamation-triangle me-2"></i> Danger Zone
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">Permanently delete this merchant, all stores and associated records. This cannot be undone.</p>
                <button class="btn btn-danger btn-sm w-100"
                        onclick="confirmDeleteMerchant(<?= $merchant['id'] ?>, '<?= escape($merchant['business_name']) ?>')">
                    <i class="fas fa-trash me-2"></i> Delete Merchant
                </button>
            </div>
        </div>

    </div>
</div>

<!-- Delete Merchant Form -->
<form method="POST" action="<?= BASE_URL ?>merchants/delete" id="deleteMerchantForm">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
    <input type="hidden" name="id" value="<?= $merchant['id'] ?>">
</form>

<!-- Delete Store Form -->
<form method="POST" action="<?= BASE_URL ?>merchants/delete-store" id="deleteStoreForm">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
    <input type="hidden" name="id" id="deleteStoreId">
    <input type="hidden" name="merchant_id" value="<?= $merchant['id'] ?>">
</form>

<script>
function confirmDeleteMerchant(id, name) {
    Swal.fire({
        title: 'Delete Merchant?',
        html: `Permanently delete <b>${name}</b> and all associated stores?`,
        icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#dc3545', confirmButtonText: 'Delete'
    }).then(r => { if (r.isConfirmed) document.getElementById('deleteMerchantForm').submit(); });
}
function confirmDeleteStore(storeId, merchantId, name) {
    Swal.fire({
        title: 'Delete Store?',
        html: `Permanently delete store <b>${name}</b>?`,
        icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#dc3545', confirmButtonText: 'Delete'
    }).then(r => {
        if (r.isConfirmed) {
            document.getElementById('deleteStoreId').value = storeId;
            document.getElementById('deleteStoreForm').submit();
        }
    });
}
</script>
