<?php $pageTitle = 'Notifications'; ?>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-2">
                <div class="fs-4 fw-bold"><?= (int)$total ?></div>
                <div class="text-muted small">Total</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-2">
                <div class="fs-4 fw-bold text-danger"><?= (int)$unread ?></div>
                <div class="text-muted small">Unread</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-2">
                <div class="fs-4 fw-bold text-primary"><?= (int)($stats['inbox'] ?? 0) ?></div>
                <div class="text-muted small">Messages</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-2">
                <div class="fs-4 fw-bold text-secondary"><?= (int)($total - $unread) ?></div>
                <div class="text-muted small">Read</div>
            </div>
        </div>
    </div>
</div>

<!-- Alerts -->
<?php if (!empty($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= htmlspecialchars($_GET['success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php elseif (!empty($_GET['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= htmlspecialchars($_GET['error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Toolbar -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="mb-0 text-muted">
        <i class="bi bi-bell me-1"></i>
        <?= $unread > 0 ? "<strong class='text-danger'>{$unread} unread</strong> of {$total}" : "All {$total} notifications read" ?>
    </h6>
    <div class="d-flex gap-2">
        <?php if ($unread > 0): ?>
        <form method="POST" action="<?= BASE_URL ?>notifications/mark-all">
            <button type="submit" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-check2-all me-1"></i>Mark All Read
            </button>
        </form>
        <?php endif; ?>
        <?php if (in_array($cu['admin_type'] ?? '', ['super_admin', 'city_admin'])): ?>
        <a href="<?= BASE_URL ?>notifications/broadcast" class="btn btn-sm btn-primary">
            <i class="bi bi-megaphone me-1"></i>Send Notification
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- List -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($notifications)): ?>
            <div class="text-center text-muted py-5">
                <i class="bi bi-bell-slash fs-1 d-block mb-2 opacity-25"></i>
                No notifications yet.
            </div>
        <?php else: ?>
        <div class="list-group list-group-flush">
            <?php foreach ($notifications as $n): ?>
            <?php
            $typeMap = [
                'info'    => ['bg-primary', 'bi-info-circle'],
                'success' => ['bg-success', 'bi-check-circle'],
                'warning' => ['bg-warning', 'bi-exclamation-triangle'],
                'error'   => ['bg-danger',  'bi-x-circle'],
            ];
            [$bgClass, $icon] = $typeMap[$n['notification_type']] ?? ['bg-secondary', 'bi-bell'];
            $isUnread = !$n['read_status'];
            ?>
            <div class="list-group-item <?= $isUnread ? 'bg-light' : '' ?> px-4 py-3">
                <div class="d-flex gap-3 align-items-start">
                    <!-- Icon -->
                    <div class="flex-shrink-0 rounded-circle d-flex align-items-center justify-content-center text-white <?= $bgClass ?>"
                         style="width:38px;height:38px;min-width:38px;">
                        <i class="bi <?= $icon ?>" style="font-size:.9rem"></i>
                    </div>
                    <!-- Content -->
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="fw-<?= $isUnread ? 'bold' : 'normal' ?>">
                                <?= htmlspecialchars($n['title']) ?>
                            </div>
                            <small class="text-muted ms-3 flex-shrink-0"><?= date('d M, H:i', strtotime($n['created_at'])) ?></small>
                        </div>
                        <div class="text-muted small mt-1"><?= htmlspecialchars($n['message']) ?></div>
                        <?php if (!empty($n['action_url'])): ?>
                            <a href="<?= htmlspecialchars($n['action_url']) ?>" class="btn btn-xs btn-link p-0 small mt-1">
                                View details <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    <!-- Actions -->
                    <?php if ($isUnread): ?>
                    <div class="flex-shrink-0">
                        <form method="POST" action="<?= BASE_URL ?>notifications/mark-read">
                            <input type="hidden" name="id" value="<?= $n['id'] ?>">
                            <button type="submit" class="btn btn-xs btn-outline-secondary py-0 px-2 small" title="Mark read">
                                <i class="bi bi-check"></i>
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php if ($pages > 1): ?>
    <div class="card-footer bg-white border-0">
        <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted">Showing <?= count($notifications) ?> of <?= $total ?></small>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <?php for ($p = 1; $p <= $pages; $p++): ?>
                        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $p ?>"><?= $p ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    </div>
    <?php endif; ?>
</div>
