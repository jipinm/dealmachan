<?php /* views/sales/view.php */ ?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>sales">Sales Registry</a></li>
        <li class="breadcrumb-item active">Sale #<?= $sale['id'] ?></li>
    </ol>
</nav>

<div class="row g-4">
    <div class="col-lg-8">

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded bg-success d-flex align-items-center justify-content-center text-white flex-shrink-0"
                         style="width:56px;height:56px;font-size:1.4rem;">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h2 class="h4 mb-0">Sale #<?= $sale['id'] ?></h2>
                        <div class="text-muted small"><?= date('d F Y, h:i A', strtotime($sale['transaction_date'])) ?></div>
                    </div>
                    <div class="text-end">
                        <div class="fs-3 fw-bold text-success">₹<?= number_format($sale['transaction_amount'], 2) ?></div>
                        <?php if ($sale['discount_amount'] > 0): ?>
                        <div class="text-muted small">Discount: ₹<?= number_format($sale['discount_amount'], 2) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Merchant info -->
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold border-bottom">
                        <i class="fas fa-store me-2 text-warning"></i> Merchant
                    </div>
                    <div class="card-body">
                        <div class="fw-semibold"><?= escape($sale['business_name']) ?></div>
                        <div class="text-muted small mt-1"><?= escape($sale['store_name']) ?></div>
                        <a href="<?= BASE_URL ?>merchants/detail?id=<?= $sale['merchant_id'] ?>" class="btn btn-outline-info btn-sm mt-2">
                            <i class="fas fa-external-link-alt me-1"></i> View Merchant
                        </a>
                    </div>
                </div>
            </div>

            <!-- Customer info -->
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold border-bottom">
                        <i class="fas fa-user me-2 text-secondary"></i> Customer
                    </div>
                    <div class="card-body">
                        <?php if ($sale['customer_name']): ?>
                        <div class="fw-semibold"><?= escape($sale['customer_name']) ?></div>
                        <div class="text-muted small"><?= escape($sale['customer_phone'] ?? '') ?></div>
                        <div class="text-muted small"><?= escape($sale['customer_email'] ?? '') ?></div>
                        <?php elseif ($sale['customer_id']): ?>
                        <div class="text-muted">Customer #<?= $sale['customer_id'] ?></div>
                        <?php else: ?>
                        <div class="text-muted fst-italic">Walk-in customer (no account)</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($sale['coupon_used']): ?>
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white fw-semibold border-bottom">
                <i class="fas fa-tag me-2 text-success"></i> Coupon Applied
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-semibold"><?= escape($sale['coupon_title'] ?? 'Coupon #' . $sale['coupon_used']) ?></div>
                        <?php if ($sale['coupon_code']): ?>
                        <code class="text-success"><?= escape($sale['coupon_code']) ?></code>
                        <?php endif; ?>
                    </div>
                    <div class="text-end">
                        <div class="text-success fw-semibold">-₹<?= number_format($sale['discount_amount'], 2) ?></div>
                        <a href="<?= BASE_URL ?>coupons/detail?id=<?= $sale['coupon_used'] ?>" class="btn btn-xs btn-outline-success mt-1">View Coupon</a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold border-bottom">
                <i class="fas fa-info-circle me-2 text-primary"></i> Transaction Info
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-6 text-muted small">Payment</dt>
                    <dd class="col-6 fw-semibold"><?= ucfirst($sale['payment_method'] ?? '&mdash;') ?></dd>

                    <dt class="col-6 text-muted small">Amount</dt>
                    <dd class="col-6 fw-semibold text-success">₹<?= number_format($sale['transaction_amount'], 2) ?></dd>

                    <?php if ($sale['discount_amount'] > 0): ?>
                    <dt class="col-6 text-muted small">Discount</dt>
                    <dd class="col-6 text-warning">-₹<?= number_format($sale['discount_amount'], 2) ?></dd>

                    <dt class="col-6 text-muted small">Net</dt>
                    <dd class="col-6 fw-bold">₹<?= number_format($sale['transaction_amount'] - $sale['discount_amount'], 2) ?></dd>
                    <?php endif; ?>

                    <dt class="col-6 text-muted small mt-2">Date</dt>
                    <dd class="col-6 mt-2 small"><?= date('d M Y', strtotime($sale['transaction_date'])) ?></dd>

                    <dt class="col-6 text-muted small">Time</dt>
                    <dd class="col-6 small"><?= date('h:i A', strtotime($sale['transaction_date'])) ?></dd>

                    <dt class="col-6 text-muted small">Record ID</dt>
                    <dd class="col-6 small text-muted">#<?= $sale['id'] ?></dd>
                </dl>
            </div>
        </div>

        <a href="<?= BASE_URL ?>sales" class="btn btn-outline-secondary btn-sm w-100">
            <i class="fas fa-arrow-left me-1"></i> Back to Sales Registry
        </a>
    </div>
</div>
