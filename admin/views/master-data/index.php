<?php /* views/master-data/index.php */ ?>
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Master Data</h1>
        <p class="text-muted mb-0">Manage core reference data for the platform</p>
    </div>
</div>

<!-- Overview Cards -->
<div class="row g-4">

    <div class="col-lg-3 col-md-4 col-sm-6">
        <a href="<?= BASE_URL ?>master-data/cities" class="text-decoration-none">
            <div class="card h-100 border-0 shadow-sm hover-card">
                <div class="card-body text-center p-4">
                    <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:60px;height:60px;">
                        <i class="fas fa-city text-primary fa-lg"></i>
                    </div>
                    <h2 class="fw-bold mb-1"><?= number_format($stats['cities']) ?></h2>
                    <p class="text-muted mb-0 small fw-semibold text-uppercase">Cities</p>
                </div>
            </div>
        </a>
    </div>

    <div class="col-lg-3 col-md-4 col-sm-6">
        <a href="<?= BASE_URL ?>master-data/areas" class="text-decoration-none">
            <div class="card h-100 border-0 shadow-sm hover-card">
                <div class="card-body text-center p-4">
                    <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:60px;height:60px;">
                        <i class="fas fa-map-marked-alt text-success fa-lg"></i>
                    </div>
                    <h2 class="fw-bold mb-1"><?= number_format($stats['areas']) ?></h2>
                    <p class="text-muted mb-0 small fw-semibold text-uppercase">Areas</p>
                </div>
            </div>
        </a>
    </div>

    <div class="col-lg-3 col-md-4 col-sm-6">
        <a href="<?= BASE_URL ?>master-data/locations" class="text-decoration-none">
            <div class="card h-100 border-0 shadow-sm hover-card">
                <div class="card-body text-center p-4">
                    <div class="bg-info bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:60px;height:60px;">
                        <i class="fas fa-map-pin text-info fa-lg"></i>
                    </div>
                    <h2 class="fw-bold mb-1"><?= number_format($stats['locations']) ?></h2>
                    <p class="text-muted mb-0 small fw-semibold text-uppercase">Locations</p>
                </div>
            </div>
        </a>
    </div>

    <div class="col-lg-3 col-md-4 col-sm-6">
        <a href="<?= BASE_URL ?>master-data/labels" class="text-decoration-none">
            <div class="card h-100 border-0 shadow-sm hover-card">
                <div class="card-body text-center p-4">
                    <div class="bg-warning bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:60px;height:60px;">
                        <i class="fas fa-tag text-warning fa-lg"></i>
                    </div>
                    <h2 class="fw-bold mb-1"><?= number_format($stats['labels']) ?></h2>
                    <p class="text-muted mb-0 small fw-semibold text-uppercase">Labels</p>
                </div>
            </div>
        </a>
    </div>

    <div class="col-lg-3 col-md-4 col-sm-6">
        <a href="<?= BASE_URL ?>master-data/tags" class="text-decoration-none">
            <div class="card h-100 border-0 shadow-sm hover-card">
                <div class="card-body text-center p-4">
                    <div class="bg-secondary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:60px;height:60px;">
                        <i class="fas fa-tags text-secondary fa-lg"></i>
                    </div>
                    <h2 class="fw-bold mb-1"><?= number_format($stats['tags']) ?></h2>
                    <p class="text-muted mb-0 small fw-semibold text-uppercase">Tags</p>
                </div>
            </div>
        </a>
    </div>

    <div class="col-lg-3 col-md-4 col-sm-6">
        <a href="<?= BASE_URL ?>master-data/professions" class="text-decoration-none">
            <div class="card h-100 border-0 shadow-sm hover-card">
                <div class="card-body text-center p-4">
                    <div class="bg-danger bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:60px;height:60px;">
                        <i class="fas fa-briefcase text-danger fa-lg"></i>
                    </div>
                    <h2 class="fw-bold mb-1"><?= number_format($stats['professions']) ?></h2>
                    <p class="text-muted mb-0 small fw-semibold text-uppercase">Professions</p>
                </div>
            </div>
        </a>
    </div>

    <div class="col-lg-3 col-md-4 col-sm-6">
        <a href="<?= BASE_URL ?>master-data/day-types" class="text-decoration-none">
            <div class="card h-100 border-0 shadow-sm hover-card">
                <div class="card-body text-center p-4">
                    <div class="bg-purple bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:60px;height:60px; background-color:#f3e5ff;">
                        <i class="fas fa-calendar-alt fa-lg" style="color:#8b5cf6;"></i>
                    </div>
                    <h2 class="fw-bold mb-1"><?= number_format($stats['day_types']) ?></h2>
                    <p class="text-muted mb-0 small fw-semibold text-uppercase">Day Types</p>
                </div>
            </div>
        </a>
    </div>

</div>

<style>
.hover-card { transition: transform .15s, box-shadow .15s; }
.hover-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,.12) !important; }
</style>
