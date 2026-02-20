<?php $pageTitle = 'Message Thread'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0"><?= htmlspecialchars($root['subject'] ?? '(no subject)') ?></h5>
    <a href="<?= BASE_URL ?>messages/inbox" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Inbox
    </a>
</div>

<?php if (!empty($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= htmlspecialchars($_GET['success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-8">

        <!-- Thread Messages -->
        <?php foreach ($thread as $i => $msg): ?>
        <?php $isMine = ($msg['sender_type'] === 'admin' && (int)$msg['sender_id'] === (int)$cu['admin_id']); ?>
        <div class="d-flex mb-3 <?= $isMine ? 'flex-row-reverse' : '' ?> gap-3">
            <!-- Avatar -->
            <div class="flex-shrink-0">
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold"
                     style="width:42px;height:42px;background:<?= $isMine ? '#667eea' : '#764ba2' ?>;font-size:.85rem;">
                    <?= strtoupper(substr($msg['sender_name'] ?? '?', 0, 1)) ?>
                </div>
            </div>
            <!-- Bubble -->
            <div class="card border-0 shadow-sm flex-grow-1" style="max-width:85%;">
                <div class="card-body px-4 py-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-semibold small"><?= htmlspecialchars($msg['sender_name'] ?? '—') ?>
                            <span class="badge bg-<?= $isMine ? 'primary' : 'secondary' ?> ms-1"><?= ucfirst($msg['sender_type']) ?></span>
                        </span>
                        <span class="text-muted" style="font-size:.75rem"><?= date('d M Y, H:i', strtotime($msg['sent_at'])) ?></span>
                    </div>
                    <p class="mb-0 text-dark" style="white-space:pre-wrap;"><?= htmlspecialchars($msg['message_text']) ?></p>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Reply Form -->
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white fw-semibold small">
                <i class="bi bi-reply me-1"></i>Reply
            </div>
            <div class="card-body">
                <form method="POST" action="<?= BASE_URL ?>messages/reply">
                    <input type="hidden" name="parent_id" value="<?= $root['id'] ?>">
                    <div class="mb-3">
                        <textarea name="message_text" class="form-control" rows="4"
                                  placeholder="Write your reply..." required></textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-send me-1"></i>Send Reply
                        </button>
                        <form method="POST" action="<?= BASE_URL ?>messages/delete" class="d-inline"
                              onsubmit="return confirm('Delete entire thread?')">
                            <input type="hidden" name="id" value="<?= $root['id'] ?>">
                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                <i class="bi bi-trash me-1"></i>Delete Thread
                            </button>
                        </form>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Right: Thread Info -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold small">Thread Details</div>
            <div class="card-body small">
                <div class="mb-2">
                    <span class="text-muted d-block">Subject</span>
                    <strong><?= htmlspecialchars($root['subject'] ?? '(no subject)') ?></strong>
                </div>
                <div class="mb-2">
                    <span class="text-muted d-block">From</span>
                    <strong><?= htmlspecialchars($root['sender_name'] ?? '—') ?></strong>
                    <span class="badge bg-secondary ms-1"><?= ucfirst($root['sender_type']) ?></span>
                </div>
                <div class="mb-2">
                    <span class="text-muted d-block">To</span>
                    <strong><?= htmlspecialchars($root['receiver_name'] ?? '—') ?></strong>
                    <span class="badge bg-secondary ms-1"><?= ucfirst($root['receiver_type']) ?></span>
                </div>
                <div class="mb-2">
                    <span class="text-muted d-block">Sent</span>
                    <strong><?= date('d M Y, H:i', strtotime($root['sent_at'])) ?></strong>
                </div>
                <div class="mb-2">
                    <span class="text-muted d-block">Status</span>
                    <?php if ($root['read_status']): ?>
                        <span class="badge bg-success">Read <?= $root['read_at'] ? date('d M, H:i', strtotime($root['read_at'])) : '' ?></span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark">Unread</span>
                    <?php endif; ?>
                </div>
                <div>
                    <span class="text-muted d-block">Messages in Thread</span>
                    <strong><?= count($thread) ?></strong>
                </div>
            </div>
        </div>

        <!-- Quick actions -->
        <div class="card border-0 shadow-sm mt-3">
            <div class="card-header bg-white fw-semibold small">Quick Actions</div>
            <div class="card-body d-flex flex-column gap-2">
                <a href="<?= BASE_URL ?>messages/compose?to_type=<?= $root['sender_type'] ?>&to_id=<?= $root['sender_id'] ?>&subject=<?= urlencode('Re: ' . ($root['subject'] ?? '')) ?>"
                   class="btn btn-sm btn-outline-primary w-100">
                    <i class="bi bi-reply me-1"></i>New Message to Sender
                </a>
                <a href="<?= BASE_URL ?>messages/inbox" class="btn btn-sm btn-outline-secondary w-100">
                    <i class="bi bi-inbox me-1"></i>Back to Inbox
                </a>
            </div>
        </div>
    </div>
</div>
