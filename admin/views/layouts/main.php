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
                                <a class="nav-link small" href="<?= BASE_URL ?>referrals">
                                    <i class="fas fa-user-plus me-2"></i> Referral Tracking
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
                    
                    <!-- Store Coupons - Super Admin, City Admin, Sales Admin -->
                    <?php if (in_array($current_user['admin_type'], ['super_admin', 'city_admin', 'sales_admin'])): ?>
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="collapse" data-bs-target="#storeCouponsMenu" aria-expanded="false">
                            <i class="fas fa-tags me-2"></i> Store Coupons
                        </a>
                        <div class="collapse" id="storeCouponsMenu">
                            <div class="nav flex-column ps-3">
                                <a class="nav-link small" href="<?= BASE_URL ?>store-coupons">
                                    <i class="fas fa-list me-2"></i> View All
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>store-coupons?status=active">
                                    <i class="fas fa-check-circle me-2"></i> Active
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>store-coupons?is_redeemed=1">
                                    <i class="fas fa-check-double me-2"></i> Redeemed
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>store-coupons?is_gifted=1">
                                    <i class="fas fa-gift me-2"></i> Gifted
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Flash Discounts - Super Admin, City Admin, Sales Admin -->
                    <?php if (in_array($current_user['admin_type'], ['super_admin', 'city_admin', 'sales_admin'])): ?>
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="collapse" data-bs-target="#flashDiscountsMenu" aria-expanded="false">
                            <i class="fas fa-bolt me-2"></i> Flash Discounts
                        </a>
                        <div class="collapse" id="flashDiscountsMenu">
                            <div class="nav flex-column ps-3">
                                <a class="nav-link small" href="<?= BASE_URL ?>flash-discounts">
                                    <i class="fas fa-list me-2"></i> View All
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>flash-discounts?status=active">
                                    <i class="fas fa-check-circle me-2"></i> Active
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>flash-discounts?expiry=expired">
                                    <i class="fas fa-clock me-2"></i> Expired
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Grievances - Super Admin, City Admin, Sales Admin -->
                    <?php if (in_array($current_user['admin_type'], ['super_admin', 'city_admin', 'sales_admin'])): ?>
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="collapse" data-bs-target="#grievancesMenu" aria-expanded="false">
                            <i class="fas fa-comment-alt me-2"></i> Grievances
                            <?php
                            // Show open count badge
                            try {
                                $db = Database::getInstance()->getConnection();
                                $openCount = (int)$db->query("SELECT COUNT(*) FROM grievances WHERE status IN ('open','in_progress')")->fetchColumn();
                                if ($openCount > 0):
                            ?>
                            <span class="badge bg-danger ms-1" style="font-size:0.65rem;"><?= $openCount ?></span>
                            <?php endif; } catch (Exception $e) {} ?>
                        </a>
                        <div class="collapse" id="grievancesMenu">
                            <div class="nav flex-column ps-3">
                                <a class="nav-link small" href="<?= BASE_URL ?>grievances">
                                    <i class="fas fa-list me-2"></i> View All
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>grievances?status=open">
                                    <i class="fas fa-exclamation-circle me-2"></i> Open
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>grievances?status=in_progress">
                                    <i class="fas fa-spinner me-2"></i> In Progress
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>grievances?priority=urgent">
                                    <i class="fas fa-fire me-2"></i> Urgent
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Review Moderation - Super Admin, City Admin, Sales Admin -->
                    <?php if (in_array($current_user['admin_type'], ['super_admin', 'city_admin', 'sales_admin'])): ?>
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="collapse" data-bs-target="#reviewsMenu" aria-expanded="false">
                            <i class="fas fa-star me-2"></i> Reviews
                        </a>
                        <div class="collapse" id="reviewsMenu">
                            <div class="nav flex-column ps-3">
                                <a class="nav-link small" href="<?= BASE_URL ?>reviews">
                                    <i class="fas fa-list me-2"></i> All Reviews
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>reviews?status=flagged">
                                    <i class="fas fa-flag me-2"></i> Flagged
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>reviews?status=approved">
                                    <i class="fas fa-check-circle me-2"></i> Approved
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Subscriptions - Super Admin -->
                    <?php if ($current_user['admin_type'] === 'super_admin'): ?>
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="collapse" data-bs-target="#subscriptionsMenu" aria-expanded="false">
                            <i class="fas fa-id-card me-2"></i> Subscriptions
                        </a>
                        <div class="collapse" id="subscriptionsMenu">
                            <div class="nav flex-column ps-3">
                                <a class="nav-link small" href="<?= BASE_URL ?>subscriptions">
                                    <i class="fas fa-list me-2"></i> All Subscriptions
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>subscriptions?expiring_soon=1">
                                    <i class="fas fa-hourglass-half me-2"></i> Expiring Soon
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>subscriptions/add">
                                    <i class="fas fa-plus me-2"></i> Add Subscription
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Sales Registry - Super Admin, City Admin, Sales Admin -->
                    <?php if (in_array($current_user['admin_type'], ['super_admin', 'city_admin', 'sales_admin'])): ?>
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="collapse" data-bs-target="#salesMenu" aria-expanded="false">
                            <i class="fas fa-cash-register me-2"></i> Sales Registry
                        </a>
                        <div class="collapse" id="salesMenu">
                            <div class="nav flex-column ps-3">
                                <a class="nav-link small" href="<?= BASE_URL ?>sales">
                                    <i class="fas fa-list me-2"></i> All Transactions
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>sales/export">
                                    <i class="fas fa-file-csv me-2"></i> Export CSV
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Advertisements - Super Admin -->
                    <?php if ($current_user['admin_type'] === 'super_admin'): ?>
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="collapse" data-bs-target="#advertisementsMenu" aria-expanded="false">
                            <i class="fas fa-bullhorn me-2"></i> Advertisements
                        </a>
                        <div class="collapse" id="advertisementsMenu">
                            <div class="nav flex-column ps-3">
                                <a class="nav-link small" href="<?= BASE_URL ?>advertisements">
                                    <i class="fas fa-list me-2"></i> All Ads
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>advertisements?live=1">
                                    <i class="fas fa-circle text-danger me-2"></i> Live Now
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>advertisements/add">
                                    <i class="fas fa-plus me-2"></i> Create Ad
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Blog / CMS - Super Admin, City Admin -->
                    <?php if (in_array($current_user['admin_type'], ['super_admin', 'city_admin'])): ?>
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="collapse" data-bs-target="#blogMenu" aria-expanded="false">
                            <i class="fas fa-blog me-2"></i> Blog / CMS
                        </a>
                        <div class="collapse" id="blogMenu">
                            <div class="nav flex-column ps-3">
                                <a class="nav-link small" href="<?= BASE_URL ?>blog">
                                    <i class="fas fa-list me-2"></i> All Posts
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>blog?status=draft">
                                    <i class="fas fa-pencil-alt me-2"></i> Drafts
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>blog?status=published">
                                    <i class="fas fa-globe me-2"></i> Published
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>blog/add">
                                    <i class="fas fa-plus me-2"></i> New Post
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Audit Logs - Super Admin -->
                    <?php if ($current_user['admin_type'] === 'super_admin'): ?>
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="collapse" data-bs-target="#auditLogsMenu" aria-expanded="false">
                            <i class="fas fa-shield-alt me-2"></i> Audit Logs
                        </a>
                        <div class="collapse" id="auditLogsMenu">
                            <div class="nav flex-column ps-3">
                                <a class="nav-link small" href="<?= BASE_URL ?>audit-logs">
                                    <i class="fas fa-list me-2"></i> All Logs
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>audit-logs?user_type=admin">
                                    <i class="fas fa-user-shield me-2"></i> Admin Activity
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>audit-logs/export">
                                    <i class="fas fa-file-csv me-2"></i> Export CSV
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Gift Coupons - Super Admin, City Admin -->
                    <?php if (in_array($current_user['admin_type'], ['super_admin', 'city_admin'])): ?>
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="collapse" data-bs-target="#giftCouponsMenu" aria-expanded="false">
                            <i class="fas fa-gift me-2"></i> Gift Coupons
                        </a>
                        <div class="collapse" id="giftCouponsMenu">
                            <div class="nav flex-column ps-3">
                                <a class="nav-link small" href="<?= BASE_URL ?>gift-coupons">
                                    <i class="fas fa-list me-2"></i> All Gifts
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>gift-coupons?acceptance_status=pending">
                                    <i class="fas fa-clock me-2"></i> Pending Acceptance
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>gift-coupons/add">
                                    <i class="fas fa-plus me-2"></i> Gift a Coupon
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

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
                                <?php if (in_array($current_user['admin_type'], ['super_admin', 'city_admin'])): ?>
                                <a class="nav-link small" href="<?= BASE_URL ?>card-configurations">
                                    <i class="fas fa-cog me-2"></i> Card Configurations
                                </a>
                                <?php endif; ?>
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
                                <a class="nav-link small" href="<?= BASE_URL ?>reports/revenue">
                                    <i class="fas fa-cash-register me-2"></i> Revenue / GMV
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>reports/subscription-report">
                                    <i class="fas fa-credit-card me-2"></i> Subscription Revenue
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>reports/coupon-analytics">
                                    <i class="fas fa-ticket-alt me-2"></i> Coupon Analytics
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>reports/engagement">
                                    <i class="fas fa-chart-line me-2"></i> Engagement
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
                                <a class="nav-link small" href="<?= BASE_URL ?>master-data/categories">
                                    <i class="fas fa-th me-2"></i> Categories
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>master-data/sub-categories">
                                    <i class="fas fa-list-ul me-2"></i> Sub-categories
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>master-data/professions">
                                    <i class="fas fa-briefcase me-2"></i> Professions
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>master-data/day-types">
                                    <i class="fas fa-calendar-day me-2"></i> Day Types
                                </a>
                                <a class="nav-link small" href="<?= BASE_URL ?>master-data/job-titles">
                                    <i class="fas fa-id-badge me-2"></i> Job Titles
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

                    <!-- CMS Pages - Super Admin Only -->
                    <div class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>cms-pages">
                            <i class="fas fa-file-alt me-2"></i> CMS Pages
                        </a>
                    </div>
                    <?php endif; ?>

                    <!-- Business Leads -->
                    <?php if (in_array($current_user['admin_type'], ['super_admin', 'sales_admin', 'city_admin'])): ?>
                    <div class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>leads">
                            <i class="fas fa-funnel-dollar me-2"></i> Business Leads
                        </a>
                    </div>

                    <!-- Contact Enquiries -->
                    <div class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>contact-enquiries">
                            <i class="fas fa-envelope me-2"></i> Contact Enquiries
                        </a>
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
                                <?php if ($current_user['admin_type'] === 'super_admin'): ?>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>settings/system">
                                    <i class="fas fa-sliders-h me-2"></i> System Settings
                                </a></li>
                                <?php endif; ?>
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
    // Strategy: collect all candidate matches, activate only the most specific one.
    // Scoring: filter links (with query params) score path.length + 1000;
    //          plain path links score path.length.
    // This ensures:
    //   /customers/add  → "Add Customer" wins over "View All" (longer path)
    //   /store-coupons?is_redeemed=1 → "Redeemed" wins over "View All" (query boost)
    //   /customers/profile?id=5 → "View All" wins (only sub-page match, no deeper link)
    const currentPath   = window.location.pathname;
    const currentSearch = window.location.search;
    const navLinks      = document.querySelectorAll('.sidebar .nav-link');
    const candidates    = [];

    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (!href || href === '#') return;

        let linkUrl;
        try {
            linkUrl = new URL(href, window.location.origin);
        } catch(e) {
            return;
        }

        const hrefPath      = linkUrl.pathname;
        const isExactPath   = currentPath === hrefPath;
        // Sub-page: e.g. /coupons/detail under /coupons &mdash; uses hrefPath + '/' to prevent
        // /store-coupons from being treated as a sub-page of /coupons
        const isSubPagePath = !isExactPath && currentPath.startsWith(hrefPath + '/');

        if (!isExactPath && !isSubPagePath) return;

        if (linkUrl.search) {
            // Filter link (e.g. ?status=active): only matches on the exact page
            // when every query param in the link is present with the same value
            if (!isExactPath) return;
            const hrefParams    = linkUrl.searchParams;
            const currentParams = new URLSearchParams(currentSearch);
            let allMatch = true;
            hrefParams.forEach((value, key) => {
                if (currentParams.get(key) !== value) allMatch = false;
            });
            if (!allMatch) return;
            // Boost score so a matching filter link always beats a plain "View All"
            candidates.push({ link, score: hrefPath.length + 1000 });
        } else {
            // Plain path link &mdash; exact or sub-page
            candidates.push({ link, score: hrefPath.length });
        }
    });

    // Activate only the single best candidate
    if (candidates.length > 0) {
        candidates.sort((a, b) => b.score - a.score);
        const winner = candidates[0].link;
        winner.classList.add('active');

        // Expand the parent collapse menu
        const parentCollapse = winner.closest('.collapse');
        if (parentCollapse) {
            const bsCollapse = bootstrap.Collapse.getInstance(parentCollapse);
            if (bsCollapse) {
                bsCollapse.show();
            } else {
                parentCollapse.classList.add('show');
                const parentToggle = document.querySelector(`[data-bs-target="#${parentCollapse.id}"]`);
                if (parentToggle) {
                    parentToggle.setAttribute('aria-expanded', 'true');
                    parentToggle.classList.add('active');
                }
            }
        }
    }
    
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