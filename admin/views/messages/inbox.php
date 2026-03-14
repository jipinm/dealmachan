<?php $pageTitle = ($tab === 'sent') ? 'Sent Messages' : 'Inbox'; ?>

<!-- Stats row -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-2">
                <div class="fs-4 fw-bold"><?= (int)($stats['inbox'] ?? 0) ?></div>
                <div class="text-muted small">Total Inbox</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-2">
                <div class="fs-4 fw-bold text-danger"><?= (int)($stats['unread'] ?? 0) ?></div>
                <div class="text-muted small">Unread</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-2">
                <div class="fs-4 fw-bold text-primary"><?= (int)($stats['sent'] ?? 0) ?></div>
                <div class="text-muted small">Sent</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-2">
                <div class="fs-4 fw-bold text-warning"><?= (int)($stats['notifUnread'] ?? 0) ?></div>
                <div class="text-muted small">Unread Notifications</div>
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

<!-- Tabs + Compose -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <ul class="nav nav-tabs border-0">
        <li class="nav-item">
            <a class="nav-link <?= $tab === 'inbox' ? 'active' : '' ?>" href="<?= BASE_URL ?>messages/inbox">
                <i class="bi bi-inbox me-1"></i>Inbox
                <?php if (($stats['unread'] ?? 0) > 0): ?>
                    <span class="badge bg-danger ms-1"><?= (int)$stats['unread'] ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $tab === 'sent' ? 'active' : '' ?>" href="<?= BASE_URL ?>messages/sent">
                <i class="bi bi-send me-1"></i>Sent
            </a>
        </li>
    </ul>
    <a href="<?= BASE_URL ?>messages/compose" class="btn btn-primary btn-sm">
        <i class="bi bi-pencil-square me-1"></i>Compose
    </a>
</div>

<!-- Message List -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($messages)): ?>
            <div class="text-center text-muted py-5">
                <i class="bi bi-envelope fs-1 d-block mb-2 opacity-25"></i>
                <?= $tab === 'sent' ? 'No sent messages.' : 'Your inbox is empty.' ?>
            </div>
        <?php else: ?>
        <div class="list-group list-group-flush">
            <?php foreach ($messages as $m): ?>
            <?php $isUnread = !$m['read_status'] && $tab === 'inbox'; ?>
            <div class="list-group-item list-group-item-action <?= $isUnread ? 'bg-light' : '' ?> px-4 py-3">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div class="flex-grow-1 min-w-0">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <?php if ($isUnread): ?>
                                <span class="badge bg-danger" style="width:8px;height:8px;border-radius:50%;padding:0;flex-shrink:0;">&nbsp;</span>
                            <?php endif; ?>
                            <a href="<?= BASE_URL ?>messages/show?id=<?= $m['id'] ?>" class="fw-<?= $isUnread ? 'bold' : 'normal' ?> text-dark text-decoration-none text-truncate">
                                <?= htmlspecialchars($m['subject'] ?? '(no subject)') ?>
                            </a>
                            <?php if (!empty($m['reply_count']) && $m['reply_count'] > 0): ?>
                                <span class="badge bg-secondary"><?= (int)$m['reply_count'] ?> <?= $m['reply_count'] == 1 ? 'reply' : 'replies' ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="text-muted small text-truncate">
                            <?= $tab === 'sent' ? 'To: ' . htmlspecialchars($m['receiver_name'] ?? '&mdash;') : 'From: ' . htmlspecialchars($m['sender_name'] ?? '&mdash;') ?>
                            &nbsp;·&nbsp;
                            <?= htmlspecialchars(mb_strimwidth($m['message_text'], 0, 80, '…')) ?>
                        </div>
                    </div>
                    <div class="d-flex flex-column align-items-end gap-1 flex-shrink-0">
                        <small class="text-muted"><?= date('d M, H:i', strtotime($m['sent_at'])) ?></small>
                        <div class="d-flex gap-1 mt-1">
                            <a href="<?= BASE_URL ?>messages/show?id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-primary py-0 px-2" title="View">
                                <i class="bi bi-eye" style="font-size:.75rem"></i>
                            </a>
                            <form method="POST" action="<?= BASE_URL ?>messages/delete" class="d-inline"
                                  onsubmit="return confirm('Delete this message?')">
                                <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                <input type="hidden" name="redirect" value="messages/<?= $tab ?>">
                                <button class="btn btn-sm btn-outline-danger py-0 px-2" title="Delete">
                                    <i class="bi bi-trash" style="font-size:.75rem"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php if ($pages > 1): ?>
    <div class="card-footer bg-white border-0">
        <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted">Showing <?= count($messages) ?> of <?= $total ?></small>
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
