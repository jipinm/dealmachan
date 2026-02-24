<?php /* views/leads/index.php */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Business Leads</h1>
        <p class="text-muted mb-0 small">Merchant interest / signup enquiries from the website.</p>
    </div>
</div>

<?php if ($flash_success): ?>
<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i><?= escape($flash_success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if ($flash_error): ?>
<div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle me-2"></i><?= escape($flash_error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<!-- Status Filter Tabs -->
<ul class="nav nav-pills mb-3 flex-wrap gap-1">
    <?php
    $statuses = ['' => 'All', 'new' => 'New', 'contacted' => 'Contacted', 'qualified' => 'Qualified', 'converted' => 'Converted', 'rejected' => 'Rejected'];
    $colors   = ['' => 'dark', 'new' => 'danger', 'contacted' => 'warning', 'qualified' => 'info', 'converted' => 'success', 'rejected' => 'secondary'];
    foreach ($statuses as $val => $label):
        $active = ($status_filter === $val) ? 'active' : '';
        $cnt    = $val === '' ? array_sum($counts) : ($counts[$val] ?? 0);
    ?>
    <li class="nav-item">
        <a class="nav-link <?= $active ?>" href="<?= BASE_URL ?>leads<?= $val ? '?status=' . $val : '' ?>">
            <?= $label ?> <span class="badge bg-<?= $colors[$val] ?> ms-1"><?= $cnt ?></span>
        </a>
    </li>
    <?php endforeach; ?>
</ul>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($leads)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fas fa-funnel-dollar fa-3x mb-3 opacity-25"></i>
            <p>No leads found.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Contact</th>
                        <th>Organisation</th>
                        <th>Phone</th>
                        <th class="text-center">Status</th>
                        <th>Assigned To</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leads as $lead): ?>
                    <tr>
                        <td class="text-muted small"><?= $lead['id'] ?></td>
                        <td class="fw-semibold"><?= escape($lead['contact_name']) ?></td>
                        <td><?= escape($lead['org_name'] ?? '—') ?></td>
                        <td><?= escape($lead['phone']) ?></td>
                        <td class="text-center">
                            <span class="badge bg-<?= $colors[$lead['status']] ?>">
                                <?= ucfirst($lead['status']) ?>
                            </span>
                        </td>
                        <td><?= $lead['assigned_admin_name'] ? escape($lead['assigned_admin_name']) : '<span class="text-muted">Unassigned</span>' ?></td>
                        <td><?= formatDate($lead['created_at']) ?></td>
                        <td><a href="<?= BASE_URL ?>leads/detail?id=<?= $lead['id'] ?>" class="btn btn-sm btn-outline-secondary">View</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
