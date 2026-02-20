<?php
// Include header
include VIEW_PATH . '/layouts/header.php';

// Check if user is logged in
$auth = new Auth();
$current_user = $auth->getCurrentUser();
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php if ($current_user): ?>
        <div class="col-md-3 col-lg-2 px-0">
            <div class="sidebar">
                <div class="p-3">
                    <div class="d-flex align-items-center">
                        <div class="login-logo me-3">
                            <i class="fas fa-store"></i>
                        </div>
                        <div>
                            <h5 class="text-white mb-0">Deal Machan</h5>
                            <small class="text-white-50">Admin Panel</small>
                        </div>
                    </div>
                </div>
                
                <nav class="nav flex-column px-3 pb-3">
                    <a class="nav-link" href="<?= BASE_URL ?>dashboard">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    
                    <!-- Customer Management - Super Admin, City Admin, Partner Admin, Club Admin -->
                    <?php if (in_array($current_user['admin_type'], ['super_admin', 'city_admin', 'partner_admin', 'club_admin'])): ?>
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="collapse" data-bs-target="#customersMenu" aria-expanded="false">
                            <i class="fas fa-users me-2"></i> Customers
                        </a>
                        <div class="collapse" id="customersMenu">
                            <div class="nav flex-column ps-3">
                                <a class="nav-link small" href="<?= BASE_URL ?>customers">
                                    <i class="fas fa-list me-2"></i> View All
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>customers/add">
                                    <i class="fas fa-plus me-2"></i> Add Customer
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Merchant Management - Super Admin, City Admin, Sales Admin -->
                    <?php if (in_array($current_user['admin_type'], ['super_admin', 'city_admin', 'sales_admin'])): ?>
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="collapse" data-bs-target="#merchantsMenu" aria-expanded="false">
                            <i class="fas fa-store me-2"></i> Merchants
                        </a>
                        <div class="collapse" id="merchantsMenu">
                            <div class="nav flex-column ps-3">
                                <a class="nav-link small" href="<?= BASE_URL ?>merchants">
                                    <i class="fas fa-list me-2"></i> View All
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>merchants/add">
                                    <i class="fas fa-plus me-2"></i> Add Merchant
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>merchants?profile_status=pending">
                                    <i class="fas fa-hourglass-half me-2"></i> Pending Approval
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Coupon Management - All Admin Types -->
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="collapse" data-bs-target="#couponsMenu" aria-expanded="false">
                            <i class="fas fa-ticket-alt me-2"></i> Coupons
                        </a>
                        <div class="collapse" id="couponsMenu">
                            <div class="nav flex-column ps-3">
                                <a class="nav-link small" href="<?= BASE_URL ?>coupons">
                                    <i class="fas fa-list me-2"></i> View All
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>coupons/add">
                                    <i class="fas fa-plus me-2"></i> Create Coupon
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>coupons?status=active">
                                    <i class="fas fa-check-circle me-2"></i> Active Coupons
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>coupons?expiry=expired">
                                    <i class="fas fa-clock me-2"></i> Expired
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>coupons?approval_status=pending">
                                    <i class="fas fa-hourglass-half me-2"></i> Pending Approval
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Card Management - All Admin Types -->
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="collapse" data-bs-target="#cardsMenu" aria-expanded="false">
                            <i class="fas fa-credit-card me-2"></i> Cards
                        </a>
                        <div class="collapse" id="cardsMenu">
                            <div class="nav flex-column ps-3">
                                <a class="nav-link small" href="<?= BASE_URL ?>cards">
                                    <i class="fas fa-list me-2"></i> View All
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>cards/generate">
                                    <i class="fas fa-plus me-2"></i> Generate Cards
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>cards/assign">
                                    <i class="fas fa-user-tag me-2"></i> Assign Cards
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>cards/track">
                                    <i class="fas fa-search me-2"></i> Track Cards
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Deal Makers - Super Admin, City Admin, Sales Admin -->
                    <?php if (in_array($current_user['admin_type'], ['super_admin', 'city_admin', 'sales_admin'])): ?>
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="collapse" data-bs-target="#dealMakersMenu" aria-expanded="false">
                            <i class="fas fa-user-tie me-2"></i> Deal Makers
                        </a>
                        <div class="collapse" id="dealMakersMenu">
                            <div class="nav flex-column ps-3">
                                <a class="nav-link small" href="<?= BASE_URL ?>deal-makers">
                                    <i class="fas fa-users me-2"></i> All Deal Makers
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>deal-makers/requests">
                                    <i class="fas fa-clock me-2"></i> Pending Requests
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>deal-makers/tasks">
                                    <i class="fas fa-tasks me-2"></i> Tasks
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Contests & Surveys - Super Admin, City Admin, Promoter Admin -->
                    <?php if (in_array($current_user['admin_type'], ['super_admin', 'city_admin', 'promoter_admin'])): ?>
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="collapse" data-bs-target="#engagementMenu" aria-expanded="false">
                            <i class="fas fa-trophy me-2"></i> Engagement
                        </a>
                        <div class="collapse" id="engagementMenu">
                            <div class="nav flex-column ps-3">
                                <a class="nav-link small" href="<?= BASE_URL ?>contests">
                                    <i class="fas fa-medal me-2"></i> Contests
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>surveys">
                                    <i class="fas fa-poll me-2"></i> Surveys
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>mystery-shopping">
                                    <i class="fas fa-search me-2"></i> Mystery Shopping
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Communications - Super Admin, City Admin -->
                    <?php if ($current_user['admin_type'] === 'super_admin' || $current_user['admin_type'] === 'city_admin'): ?>
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="collapse" data-bs-target="#communicationMenu" aria-expanded="false">
                            <i class="fas fa-envelope me-2"></i> Communications
                        </a>
                        <div class="collapse" id="communicationMenu">
                            <div class="nav flex-column ps-3">
                                <a class="nav-link small" href="<?= BASE_URL ?>messages">
                                    <i class="fas fa-comments me-2"></i> Messages
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>notifications">
                                    <i class="fas fa-bell me-2"></i> Notifications
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>notifications/broadcast">
                                    <i class="fas fa-broadcast-tower me-2"></i> Broadcasts
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Reports - All Admin Types -->
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="collapse" data-bs-target="#reportsMenu" aria-expanded="false">
                            <i class="fas fa-chart-bar me-2"></i> Reports
                        </a>
                        <div class="collapse" id="reportsMenu">
                            <div class="nav flex-column ps-3">
                                <a class="nav-link small" href="<?= BASE_URL ?>reports/dashboard">
                                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>reports/customers">
                                    <i class="fas fa-users me-2"></i> Customer Reports
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>reports/merchants">
                                    <i class="fas fa-store me-2"></i> Merchant Reports
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>reports/redemptions">
                                    <i class="fas fa-gift me-2"></i> Redemption Reports
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Master Data - Super Admin Only -->
                    <?php if ($current_user['admin_type'] === 'super_admin'): ?>
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="collapse" data-bs-target="#masterDataMenu" aria-expanded="false">
                            <i class="fas fa-database me-2"></i> Master Data
                        </a>
                        <div class="collapse" id="masterDataMenu">
                            <div class="nav flex-column ps-3">
                                <a class="nav-link small" href="<?= BASE_URL ?>master-data">
                                    <i class="fas fa-th-large me-2"></i> Overview
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>master-data/cities">
                                    <i class="fas fa-city me-2"></i> Cities
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>master-data/areas">
                                    <i class="fas fa-map me-2"></i> Areas
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>master-data/locations">
                                    <i class="fas fa-map-marker-alt me-2"></i> Locations
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>master-data/labels">
                                    <i class="fas fa-tag me-2"></i> Labels
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>master-data/tags">
                                    <i class="fas fa-tags me-2"></i> Tags
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>master-data/professions">
                                    <i class="fas fa-briefcase me-2"></i> Professions
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>master-data/day-types">
                                    <i class="fas fa-calendar-day me-2"></i> Day Types
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Admin Management - Super Admin Only -->
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="collapse" data-bs-target="#adminMenu" aria-expanded="false">
                            <i class="fas fa-user-shield me-2"></i> Admin Management
                        </a>
                        <div class="collapse" id="adminMenu">
                            <div class="nav flex-column ps-3">
                                <a class="nav-link small" href="<?= BASE_URL ?>admin-management">
                                    <i class="fas fa-list me-2"></i> View All Admins
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>admin-management/add">
                                    <i class="fas fa-plus me-2"></i> Add Admin
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Main Content -->
        <div class="<?= $current_user ? 'col-md-9 col-lg-10' : 'col-12' ?>">
            <?php if ($current_user): ?>
            <!-- Top Navigation -->
            <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
                <div class="container-fluid">
                    <!-- Left Side - Welcome Message -->
                    <div class="d-flex align-items-center">
                        <button class="navbar-toggler me-3 d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                        
                        <div class="welcome-message">
                            <span class="text-muted">Welcome, </span>
                            <strong class="text-dark"><?= escape($current_user['name']) ?></strong>
                            <span class="text-muted"> - </span>
                            <span class="text-primary"><?= ucfirst(str_replace('_', ' ', $current_user['admin_type'])) ?></span>
                        </div>
                    </div>
                    
                    <!-- Right Side - User Dropdown -->
                    <div class="d-flex align-items-center">
                        <div class="dropdown">
                            <button class="btn btn-outline-primary dropdown-toggle d-flex align-items-center" type="button" data-bs-toggle="dropdown">
                                <div class="user-avatar me-2">
                                    <i class="fas fa-user"></i>
                                </div>
                                <span class="d-none d-sm-inline"><?= escape($current_user['name']) ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li class="dropdown-header">
                                    <strong><?= escape($current_user['name']) ?></strong><br>
                                    <small class="text-muted"><?= ucfirst(str_replace('_', ' ', $current_user['admin_type'])) ?></small>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>settings/profile">
                                    <i class="fas fa-user-cog me-2"></i> Profile Settings
                                </a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>settings/preferences">
                                    <i class="fas fa-cog me-2"></i> Preferences
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>auth/logout">
                                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                                </a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>
            <?php endif; ?>
            
            <!-- Page Content -->
            <div class="content-area p-4">
                <!-- Flash Messages -->
                <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= escape($_SESSION['success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= escape($_SESSION['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['warning'])): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?= escape($_SESSION['warning']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['warning']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['info'])): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    <?= escape($_SESSION['info']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['info']); ?>
                <?php endif; ?>
                
                <!-- Main Content Area -->
                <main>
                    <?= $content ?>
                </main>
            </div>
        </div>
    </div>
</div>

<!-- Navigation Menu JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap Collapse functionality
    const collapseElements = document.querySelectorAll('.sidebar .collapse');
    const collapses = [];
    
    // Create Bootstrap Collapse instances
    collapseElements.forEach(element => {
        const collapse = new bootstrap.Collapse(element, {
            toggle: false
        });
        collapses.push(collapse);
        
        // Add event listeners for proper state management
        element.addEventListener('show.bs.collapse', function() {
            const toggle = document.querySelector(`[data-bs-target="#${this.id}"]`);
            if (toggle) {
                toggle.setAttribute('aria-expanded', 'true');
                toggle.classList.add('active');
            }
        });
        
        element.addEventListener('hide.bs.collapse', function() {
            const toggle = document.querySelector(`[data-bs-target="#${this.id}"]`);
            if (toggle) {
                toggle.setAttribute('aria-expanded', 'false');
                toggle.classList.remove('active');
            }
        });
    });
    
    // Handle menu toggle clicks
    const dropdownToggles = document.querySelectorAll('.sidebar .dropdown-toggle');
    
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('data-bs-target');
            const targetElement = document.querySelector(targetId);
            
            if (targetElement) {
                const bsCollapse = bootstrap.Collapse.getInstance(targetElement);
                const isExpanded = this.getAttribute('aria-expanded') === 'true';
                
                if (isExpanded) {
                    // Close only this menu
                    if (bsCollapse) {
                        bsCollapse.hide();
                    }
                } else {
                    // Open only this menu (don't close others)
                    if (bsCollapse) {
                        bsCollapse.show();
                    }
                }
            }
        });
    });
    
    // Highlight active menu item based on current URL
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.sidebar .nav-link');
    
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href && currentPath.includes(href.replace('<?= BASE_URL ?>', ''))) {
            link.classList.add('active');
            
            // If it's a submenu item, expand the parent
            const parentCollapse = link.closest('.collapse');
            if (parentCollapse) {
                const bsCollapse = bootstrap.Collapse.getInstance(parentCollapse);
                if (bsCollapse) {
                    bsCollapse.show();
                } else {
                    // Fallback if collapse instance doesn't exist yet
                    parentCollapse.classList.add('show');
                    const parentToggle = document.querySelector(`[data-bs-target="#${parentCollapse.id}"]`);
                    if (parentToggle) {
                        parentToggle.setAttribute('aria-expanded', 'true');
                        parentToggle.classList.add('active');
                    }
                }
            }
        }
    });
    
    // Add smooth scrolling for long menus
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        sidebar.style.scrollBehavior = 'smooth';
    }
});
</script>

<?php
// Include footer
include VIEW_PATH . '/layouts/footer.php';
?>