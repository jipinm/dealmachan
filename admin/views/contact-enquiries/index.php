<?php /* views/contact-enquiries/index.php */
$statusColors = ['new'=>'danger','read'=>'warning','responded'=>'success'];
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Contact Enquiries</h1>
        <p class="text-muted mb-0 small">Messages submitted via the contact form.</p>
    </div>
</div>

<?php if ($flash_success): ?>
<div class="alert alert-success alert-dismissible fade show"><?= escape($flash_success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<!-- Tabs -->
<ul class="nav nav-pills mb-3 flex-wrap gap-1">
    <?php foreach (['' => 'All', 'new' => 'New', 'read' => 'Read', 'responded' => 'Responded'] as $val => $label):
        $cnt    = $val === '' ? array_sum($counts) : ($counts[$val] ?? 0);
        $active = ($status_filter === $val) ? 'active' : '';
    ?>
    <li class="nav-item">
        <a class="nav-link <?= $active ?>" href="<?= BASE_URL ?>contact-enquiries<?= $val ? '?status=' . $val : '' ?>">
            <?= $label ?> <span class="badge bg-<?= $val ? $statusColors[$val] : 'dark' ?> ms-1"><?= $cnt ?></span>
        </a>
    </li>
    <?php endforeach; ?>
</ul>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($enquiries)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fas fa-envelope-open fa-3x mb-3 opacity-25"></i>
            <p>No enquiries found.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Subject</th>
                        <th>Mobile</th>
                        <th class="text-center">Status</th>
                        <th>Received</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($enquiries as $e): ?>
                    <tr class="<?= $e['status'] === 'new' ? 'fw-semibold' : '' ?>">
                        <td class="text-muted small"><?= $e['id'] ?></td>
                        <td><?= escape($e['name']) ?></td>
                        <td><?= escape(mb_substr($e['subject'] ?? $e['message'], 0, 60)) ?>…</td>
                        <td><?= escape($e['mobile'] ?? '—') ?></td>
                        <td class="text-center">
                            <span class="badge bg-<?= $statusColors[$e['status']] ?>">
                                <?= ucfirst($e['status']) ?>
                            </span>
                        </td>
                        <td><?= formatDate($e['created_at']) ?></td>
                        <td><a href="<?= BASE_URL ?>contact-enquiries/detail?id=<?= $e['id'] ?>" class="btn btn-sm btn-outline-secondary">View</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
