<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-0">Track Card</h4>
        <small class="text-muted"><a href="<?= BASE_URL ?>/cards">Cards</a> / Track</small>
    </div>
    <a href="<?= BASE_URL ?>/cards" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header fw-semibold"><i class="fas fa-search me-2 text-primary"></i>Find Card by Number</div>
            <div class="card-body">
                <form method="GET" action="<?= BASE_URL ?>/cards/track">
                    <div class="input-group input-group-lg">
                        <input type="text" name="card_number" class="form-control text-uppercase font-monospace"
                               placeholder="Enter card number…"
                               value="<?= escape($card_number ?? '') ?>" autofocus
                               style="letter-spacing:2px">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i> Track
                        </button>
                    </div>
                    <div class="form-text mt-2">Enter the full card number (e.g. <code>DMSTD1A2B3C4D</code>).</div>
                </form>

                <?php if (!empty($card_number)): ?>
                <div class="alert alert-danger mt-3 mb-0">
                    <i class="fas fa-times-circle me-2"></i>
                    Card <strong><?= escape($card_number) ?></strong> was not found.
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="text-center mt-4 text-muted small">
            <p>You can also search cards from the <a href="<?= BASE_URL ?>/cards">card listing page</a> using the search bar.</p>
        </div>
    </div>
</div>

<script>
document.querySelector('input[name="card_number"]').addEventListener('input', function () {
    const pos = this.selectionStart;
    this.value = this.value.toUpperCase();
    this.setSelectionRange(pos, pos);
});
</script>
