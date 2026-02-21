<?php
/**
 * Deal Machan API — Front Controller
 * Routes all /api/* requests to appropriate controllers.
 */

// ── Bootstrap ─────────────────────────────────────────────────────────────────
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/config/JWT.php';
require_once __DIR__ . '/helpers/Response.php';
require_once __DIR__ . '/helpers/Validator.php';
require_once __DIR__ . '/middleware/CorsMiddleware.php';
require_once __DIR__ . '/middleware/AuthMiddleware.php';

// ── CORS (always first) ───────────────────────────────────────────────────────
CorsMiddleware::handle();

// ── Parse route ───────────────────────────────────────────────────────────────
$requestUri    = $_SERVER['REQUEST_URI'] ?? '/';
$requestMethod = strtoupper($_SERVER['REQUEST_METHOD']);

// Strip query string and leading /api prefix
$path = parse_url($requestUri, PHP_URL_PATH);
$path = preg_replace('#^/api#', '', $path);
$path = trim($path, '/');

// Parse JSON body once
$body = [];
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (str_contains($contentType, 'application/json')) {
    $raw = file_get_contents('php://input');
    if ($raw) {
        $body = json_decode($raw, true) ?? [];
    }
} else {
    $body = $_POST;
}

// ── Router ────────────────────────────────────────────────────────────────────
// Pattern: method:path/pattern  →  Controller@method
// :id  matches [0-9]+  and is injected as $_ROUTE_PARAMS['id']
$_ROUTE_PARAMS = [];

function matchRoute(string $method, string $pattern, string $path): bool {
    global $_ROUTE_PARAMS, $requestMethod;
    if ($requestMethod !== $method) return false;

    $regex = preg_replace('#:([a-zA-Z_]+)#', '(?P<$1>[^/]+)', $pattern);
    $regex = "#^{$regex}$#";

    if (preg_match($regex, $path, $m)) {
        foreach ($m as $k => $v) {
            if (!is_int($k)) $_ROUTE_PARAMS[$k] = $v;
        }
        return true;
    }
    return false;
}

function param(string $key, mixed $default = null): mixed {
    global $_ROUTE_PARAMS;
    return $_ROUTE_PARAMS[$key] ?? $default;
}

// Autoload controller
function loadController(string $group, string $file): void {
    require_once API_ROOT . "/controllers/{$group}/{$file}.php";
}

// ── Route Definitions ─────────────────────────────────────────────────────────

// ---------- Auth ----------
if (matchRoute('POST', 'auth/merchant/login', $path)) {
    loadController('Auth', 'MerchantAuthController');
    (new MerchantAuthController())->login($body);
}
if (matchRoute('POST', 'auth/merchant/refresh', $path)) {
    loadController('Auth', 'MerchantAuthController');
    (new MerchantAuthController())->refresh($body);
}
if (matchRoute('POST', 'auth/merchant/logout', $path)) {
    loadController('Auth', 'MerchantAuthController');
    (new MerchantAuthController())->logout($body);
}
if (matchRoute('POST', 'auth/merchant/forgot-password', $path)) {
    loadController('Auth', 'MerchantAuthController');
    (new MerchantAuthController())->forgotPassword($body);
}
if (matchRoute('POST', 'auth/merchant/reset-password', $path)) {
    loadController('Auth', 'MerchantAuthController');
    (new MerchantAuthController())->resetPassword($body);
}

// ---------- Customer Auth ----------
if (matchRoute('POST', 'auth/customer/register', $path)) {
    loadController('Customer', 'AuthController');
    (new CustomerAuthController())->register($body);
}
if (matchRoute('POST', 'auth/customer/login', $path)) {
    loadController('Customer', 'AuthController');
    (new CustomerAuthController())->login($body);
}
if (matchRoute('POST', 'auth/customer/verify-otp', $path)) {
    loadController('Customer', 'AuthController');
    (new CustomerAuthController())->verifyOtp($body);
}
if (matchRoute('POST', 'auth/customer/resend-otp', $path)) {
    loadController('Customer', 'AuthController');
    (new CustomerAuthController())->resendOtp($body);
}
if (matchRoute('POST', 'auth/customer/forgot-password', $path)) {
    loadController('Customer', 'AuthController');
    (new CustomerAuthController())->forgotPassword($body);
}
if (matchRoute('POST', 'auth/customer/reset-password', $path)) {
    loadController('Customer', 'AuthController');
    (new CustomerAuthController())->resetPassword($body);
}
if (matchRoute('POST', 'auth/customer/refresh', $path)) {
    loadController('Customer', 'AuthController');
    (new CustomerAuthController())->refresh($body);
}
if (matchRoute('POST', 'auth/customer/logout', $path)) {
    loadController('Customer', 'AuthController');
    (new CustomerAuthController())->logout($body);
}

// ---------- Analytics ----------
if (matchRoute('GET', 'merchants/analytics/dashboard', $path)) {
    AuthMiddleware::require();
    loadController('Analytics', 'AnalyticsController');
    (new AnalyticsController())->dashboard();
}
if (matchRoute('GET', 'merchants/analytics/redemptions', $path)) {
    AuthMiddleware::require();
    loadController('Analytics', 'AnalyticsController');
    (new AnalyticsController())->redemptions();
}
if (matchRoute('GET', 'merchants/analytics/customers', $path)) {
    AuthMiddleware::require();
    loadController('Analytics', 'AnalyticsController');
    (new AnalyticsController())->customers();
}
if (matchRoute('GET', 'merchants/analytics/top-coupons', $path)) {
    AuthMiddleware::require();
    loadController('Analytics', 'AnalyticsController');
    (new AnalyticsController())->topCoupons();
}
if (matchRoute('GET', 'merchants/analytics/revenue', $path)) {
    AuthMiddleware::require();
    loadController('Analytics', 'AnalyticsController');
    (new AnalyticsController())->revenue();
}

// ---------- Profile ----------
if (matchRoute('GET', 'merchants/profile', $path)) {
    AuthMiddleware::require();
    loadController('Merchant', 'ProfileController');
    (new ProfileController())->show();
}
if (matchRoute('PUT', 'merchants/profile', $path)) {
    AuthMiddleware::require();
    loadController('Merchant', 'ProfileController');
    (new ProfileController())->update($body);
}
if (matchRoute('POST', 'merchants/profile/logo', $path)) {
    AuthMiddleware::require();
    loadController('Merchant', 'ProfileController');
    (new ProfileController())->uploadLogo();
}
if (matchRoute('POST', 'merchants/subscription/renew', $path)) {
    AuthMiddleware::require();
    loadController('Merchant', 'ProfileController');
    (new ProfileController())->renewSubscription($body);
}
if (matchRoute('POST', 'merchants/subscription/upgrade', $path)) {
    AuthMiddleware::require();
    loadController('Merchant', 'ProfileController');
    (new ProfileController())->upgradeSubscription($body);
}

// ---------- Stores ----------
if (matchRoute('GET', 'merchants/stores', $path)) {
    loadController('Merchant', 'StoreController');
    AuthMiddleware::require();
    (new StoreController())->index($_GET);
}
if (matchRoute('POST', 'merchants/stores', $path)) {
    loadController('Merchant', 'StoreController');
    AuthMiddleware::require();
    (new StoreController())->store($body);
}
if (matchRoute('GET', 'merchants/stores/:id', $path)) {
    loadController('Merchant', 'StoreController');
    AuthMiddleware::require();
    (new StoreController())->show((int)param('id'));
}
if (matchRoute('PUT', 'merchants/stores/:id', $path)) {
    loadController('Merchant', 'StoreController');
    AuthMiddleware::require();
    (new StoreController())->update((int)param('id'), $body);
}
if (matchRoute('DELETE', 'merchants/stores/:id', $path)) {
    loadController('Merchant', 'StoreController');
    AuthMiddleware::require();
    (new StoreController())->destroy((int)param('id'));
}
if (matchRoute('POST', 'merchants/stores/:id/gallery', $path)) {
    loadController('Merchant', 'StoreController');
    AuthMiddleware::require();
    (new StoreController())->uploadGallery((int)param('id'));
}
if (matchRoute('DELETE', 'merchants/stores/:id/gallery/:imageId', $path)) {
    loadController('Merchant', 'StoreController');
    AuthMiddleware::require();
    (new StoreController())->deleteGalleryImage((int)param('id'), (int)param('imageId'));
}
if (matchRoute('PUT', 'merchants/stores/:id/gallery/:imageId', $path)) {
    loadController('Merchant', 'StoreController');
    AuthMiddleware::require();
    (new StoreController())->setCoverImage((int)param('id'), (int)param('imageId'));
}
if (matchRoute('PUT', 'merchants/stores/:id/gallery/reorder', $path)) {
    loadController('Merchant', 'StoreController');
    AuthMiddleware::require();
    (new StoreController())->reorderGallery((int)param('id'), $body);
}

// ---------- Public: Master Data ----------
if (matchRoute('GET', 'public/cities', $path)) {
    $db = Database::getInstance();
    $cities = $db->query("SELECT id, city_name AS name, state FROM cities WHERE status='active' ORDER BY city_name");
    Response::success($cities);
}
if (matchRoute('GET', 'public/areas', $path)) {
    $db     = Database::getInstance();
    $cityId = !empty($_GET['city_id']) ? (int)$_GET['city_id'] : null;
    $where  = $cityId ? "WHERE status='active' AND city_id = {$cityId}" : "WHERE status='active'";
    $areas  = $db->query("SELECT id, area_name AS name, city_id FROM areas {$where} ORDER BY area_name");
    Response::success($areas);
}
if (matchRoute('GET', 'public/home', $path)) {
    loadController('Public', 'HomeController');
    (new HomeController())->index($_GET);
}
if (matchRoute('GET', 'public/merchants', $path)) {
    loadController('Public', 'MerchantBrowseController');
    (new MerchantBrowseController())->index($_GET);
}
if (matchRoute('GET', 'public/merchants/:id', $path)) {
    loadController('Public', 'MerchantBrowseController');
    (new MerchantBrowseController())->show((int)param('id'));
}
if (matchRoute('GET', 'public/search', $path)) {
    loadController('Public', 'SearchController');
    (new SearchController())->search($_GET);
}
if (matchRoute('GET', 'public/merchants/:id/coupons', $path)) {
    loadController('Public', 'MerchantBrowseController');
    (new MerchantBrowseController())->coupons((int)param('id'));
}
if (matchRoute('GET', 'public/merchants/:id/reviews', $path)) {
    loadController('Public', 'MerchantBrowseController');
    (new MerchantBrowseController())->reviews((int)param('id'), $_GET);
}
if (matchRoute('GET', 'public/tags', $path)) {
    $db   = Database::getInstance();
    $tags = $db->query("SELECT id, tag_name AS name, tag_category, parent_tag_id, icon FROM tags WHERE status='active' ORDER BY tag_name");
    Response::success($tags);
}
if (matchRoute('GET', 'public/flash-discounts', $path)) {
    loadController('Public', 'CouponController');
    (new PublicCouponController())->flashDiscounts($_GET);
}
if (matchRoute('GET', 'public/coupons/:id', $path)) {
    loadController('Public', 'CouponController');
    (new PublicCouponController())->show((int)param('id'));
}
if (matchRoute('GET', 'public/coupons', $path)) {
    loadController('Public', 'CouponController');
    (new PublicCouponController())->index($_GET);
}
if (matchRoute('GET', 'public/advertisements', $path)) {
    loadController('Public', 'AdController');
    (new PublicAdController())->index($_GET);
}
if (matchRoute('GET', 'public/blog/:slug', $path)) {
    loadController('Public', 'BlogController');
    (new PublicBlogController())->show(param('slug'));
}
if (matchRoute('GET', 'public/blog', $path)) {
    loadController('Public', 'BlogController');
    (new PublicBlogController())->index($_GET);
}

// ---------- Coupons ----------
if (matchRoute('GET', 'merchants/coupons', $path)) {
    loadController('Merchant', 'CouponController');
    $user = AuthMiddleware::require();
    (new CouponController())->index($user, $_GET);
}
if (matchRoute('POST', 'merchants/coupons/scan-redeem', $path)) {
    loadController('Merchant', 'CouponController');
    $user = AuthMiddleware::require();
    (new CouponController())->scanRedeem($user, $body);
}
if (matchRoute('POST', 'merchants/coupons/manual-redeem', $path)) {
    loadController('Merchant', 'CouponController');
    $user = AuthMiddleware::require();
    (new CouponController())->manualRedeem($user, $body);
}
if (matchRoute('POST', 'merchants/coupons', $path)) {
    loadController('Merchant', 'CouponController');
    $user = AuthMiddleware::require();
    (new CouponController())->store($user, $body);
}
if (matchRoute('GET', 'merchants/coupons/:id/redemptions', $path)) {
    loadController('Merchant', 'CouponController');
    $user = AuthMiddleware::require();
    (new CouponController())->redemptions($user, (int)param('id'), $_GET);
}
if (matchRoute('GET', 'merchants/coupons/:id', $path)) {
    loadController('Merchant', 'CouponController');
    $user = AuthMiddleware::require();
    (new CouponController())->show($user, (int)param('id'));
}
if (matchRoute('PUT', 'merchants/coupons/:id', $path)) {
    loadController('Merchant', 'CouponController');
    $user = AuthMiddleware::require();
    (new CouponController())->update($user, (int)param('id'), $body);
}
if (matchRoute('DELETE', 'merchants/coupons/:id', $path)) {
    loadController('Merchant', 'CouponController');
    $user = AuthMiddleware::require();
    (new CouponController())->destroy($user, (int)param('id'));
}

// ---------- Store Coupons ----------
if (matchRoute('GET', 'merchants/store-coupons', $path)) {
    loadController('Merchant', 'StoreCouponController');
    $user = AuthMiddleware::require();
    (new StoreCouponController())->index($user, $_GET);
}
if (matchRoute('POST', 'merchants/store-coupons', $path)) {
    loadController('Merchant', 'StoreCouponController');
    $user = AuthMiddleware::require();
    (new StoreCouponController())->store($user, $body);
}
if (matchRoute('PUT', 'merchants/store-coupons/:id', $path)) {
    loadController('Merchant', 'StoreCouponController');
    $user = AuthMiddleware::require();
    (new StoreCouponController())->update($user, (int)param('id'), $body);
}
if (matchRoute('DELETE', 'merchants/store-coupons/:id', $path)) {
    loadController('Merchant', 'StoreCouponController');
    $user = AuthMiddleware::require();
    (new StoreCouponController())->destroy($user, (int)param('id'));
}
if (matchRoute('POST', 'merchants/store-coupons/:id/gift', $path)) {
    loadController('Merchant', 'StoreCouponController');
    $user = AuthMiddleware::require();
    (new StoreCouponController())->gift($user, (int)param('id'), $body);
}
if (matchRoute('GET', 'merchants/store-coupons/:id', $path)) {
    loadController('Merchant', 'StoreCouponController');
    $user = AuthMiddleware::require();
    (new StoreCouponController())->show($user, (int)param('id'));
}
if (matchRoute('POST', 'merchants/store-coupons/:id/assign', $path)) {
    loadController('Merchant', 'StoreCouponController');
    $user = AuthMiddleware::require();
    (new StoreCouponController())->assign($user, (int)param('id'), $body);
}
if (matchRoute('POST', 'merchants/store-coupons/:id/bulk-assign', $path)) {
    loadController('Merchant', 'StoreCouponController');
    $user = AuthMiddleware::require();
    (new StoreCouponController())->bulkAssign($user, (int)param('id'), $body);
}
if (matchRoute('POST', 'merchants/store-coupons/:id/redeem', $path)) {
    loadController('Merchant', 'StoreCouponController');
    $user = AuthMiddleware::require();
    (new StoreCouponController())->redeem($user, (int)param('id'), $body);
}

// ---------- Sales Registry ----------
if (matchRoute('GET', 'merchants/sales-registry/export', $path)) {
    loadController('Merchant', 'SalesController');
    $user = AuthMiddleware::require();
    (new SalesController())->export($user, $_GET);
}
if (matchRoute('GET', 'merchants/sales-registry/summary', $path)) {
    loadController('Merchant', 'SalesController');
    $user = AuthMiddleware::require();
    (new SalesController())->summary($user, $_GET);
}
if (matchRoute('GET', 'merchants/sales-registry', $path)) {
    loadController('Merchant', 'SalesController');
    $user = AuthMiddleware::require();
    (new SalesController())->index($user, $_GET);
}
if (matchRoute('POST', 'merchants/sales-registry', $path)) {
    loadController('Merchant', 'SalesController');
    $user = AuthMiddleware::require();
    (new SalesController())->store($user, $body);
}

// ---------- Customers (merchant-created) ----------
if (matchRoute('GET', 'merchants/customers', $path)) {
    loadController('Merchant', 'MerchantCustomerController');
    $user = AuthMiddleware::require();
    (new MerchantCustomerController())->index($user, $_GET);
}
if (matchRoute('POST', 'merchants/customers', $path)) {
    loadController('Merchant', 'MerchantCustomerController');
    $user = AuthMiddleware::require();
    (new MerchantCustomerController())->store($user, $body);
}
if (matchRoute('GET', 'merchants/customers/:id/analytics', $path)) {
    loadController('Merchant', 'MerchantCustomerController');
    $user = AuthMiddleware::require();
    (new MerchantCustomerController())->analytics($user, (int)param('id'), $_GET);
}
if (matchRoute('GET', 'merchants/customers/:id', $path)) {
    loadController('Merchant', 'MerchantCustomerController');
    $user = AuthMiddleware::require();
    (new MerchantCustomerController())->show($user, (int)param('id'));
}
if (matchRoute('PUT', 'merchants/customers/:id', $path)) {
    loadController('Merchant', 'MerchantCustomerController');
    $user = AuthMiddleware::require();
    (new MerchantCustomerController())->update($user, (int)param('id'), $body);
}

// ---------- Customer Lookup (Redemption) ----------
if (matchRoute('GET', 'merchants/redemption/customer-lookup', $path)) {
    loadController('Merchant', 'MerchantCustomerController');
    $user = AuthMiddleware::require();
    (new MerchantCustomerController())->customerLookup($user, $_GET);
}

// ---------- Grievances ----------
if (matchRoute('GET', 'merchants/grievances', $path)) {
    loadController('Merchant', 'GrievanceController');
    $user = AuthMiddleware::require();
    (new GrievanceController())->index($user, $_GET);
}
if (matchRoute('GET', 'merchants/grievances/:id', $path)) {
    loadController('Merchant', 'GrievanceController');
    $user = AuthMiddleware::require();
    (new GrievanceController())->show($user, (int)param('id'));
}
if (matchRoute('POST', 'merchants/grievances/:id/respond', $path)) {
    loadController('Merchant', 'GrievanceController');
    $user = AuthMiddleware::require();
    (new GrievanceController())->respond($user, (int)param('id'), $body);
}
if (matchRoute('PUT', 'merchants/grievances/:id/resolve', $path)) {
    loadController('Merchant', 'GrievanceController');
    $user = AuthMiddleware::require();
    (new GrievanceController())->resolve($user, (int)param('id'));
}

// ---------- Reviews ----------
if (matchRoute('GET', 'merchants/reviews', $path)) {
    loadController('Merchant', 'ReviewController');
    $user = AuthMiddleware::require();
    (new ReviewController())->index($user, $_GET);
}
if (matchRoute('GET', 'merchants/reviews/:id', $path)) {
    loadController('Merchant', 'ReviewController');
    $user = AuthMiddleware::require();
    (new ReviewController())->show($user, (int)param('id'));
}
if (matchRoute('POST', 'merchants/reviews/:id/reply', $path)) {
    loadController('Merchant', 'ReviewController');
    $user = AuthMiddleware::require();
    (new ReviewController())->reply($user, (int)param('id'), $body);
}

// ---------- Messages ----------
if (matchRoute('GET', 'merchants/messages', $path)) {
    loadController('Merchant', 'MessageController');
    $user = AuthMiddleware::require();
    (new MessageController())->index($user, $_GET);
}
if (matchRoute('POST', 'merchants/messages', $path)) {
    loadController('Merchant', 'MessageController');
    $user = AuthMiddleware::require();
    (new MessageController())->store($user, $body);
}
if (matchRoute('GET', 'merchants/messages/:id', $path)) {
    loadController('Merchant', 'MessageController');
    $user = AuthMiddleware::require();
    (new MessageController())->show($user, (int)param('id'));
}
if (matchRoute('PUT', 'merchants/messages/:id/read', $path)) {
    loadController('Merchant', 'MessageController');
    $user = AuthMiddleware::require();
    (new MessageController())->markRead($user, (int)param('id'));
}

// ---------- Notifications ----------
if (matchRoute('GET', 'merchants/notifications', $path)) {
    AuthMiddleware::require();
    loadController('Merchant', 'NotificationController');
    (new NotificationController())->index();
}
if (matchRoute('PUT', 'merchants/notifications/read-all', $path)) {
    AuthMiddleware::require();
    loadController('Merchant', 'NotificationController');
    (new NotificationController())->markAllRead();
}
if (matchRoute('PUT', 'merchants/notifications/:id/read', $path)) {
    AuthMiddleware::require();
    loadController('Merchant', 'NotificationController');
    (new NotificationController())->markRead((int)param('id'));
}

// ---------- Labels ----------
if (matchRoute('GET', 'merchants/labels', $path)) {
    loadController('Merchant', 'LabelController');
    $user = AuthMiddleware::require();
    (new LabelController())->index($user);
}
if (matchRoute('POST', 'merchants/labels/request', $path)) {
    loadController('Merchant', 'LabelController');
    $user = AuthMiddleware::require();
    (new LabelController())->request($user, $body);
}

// ---------- Flash Discounts ----------
if (matchRoute('GET', 'merchants/flash-discounts', $path)) {
    loadController('Merchant', 'FlashDiscountController');
    $user = AuthMiddleware::require();
    (new FlashDiscountController())->index($user, $_GET);
}
if (matchRoute('POST', 'merchants/flash-discounts', $path)) {
    loadController('Merchant', 'FlashDiscountController');
    $user = AuthMiddleware::require();
    (new FlashDiscountController())->store($user, $body);
}
if (matchRoute('PUT', 'merchants/flash-discounts/:id', $path)) {
    loadController('Merchant', 'FlashDiscountController');
    $user = AuthMiddleware::require();
    (new FlashDiscountController())->update($user, (int)param('id'), $body);
}
if (matchRoute('DELETE', 'merchants/flash-discounts/:id', $path)) {
    loadController('Merchant', 'FlashDiscountController');
    $user = AuthMiddleware::require();
    (new FlashDiscountController())->destroy($user, (int)param('id'));
}
if (matchRoute('POST', 'merchants/flash-discounts/:id/redeem', $path)) {
    loadController('Merchant', 'FlashDiscountController');
    $user = AuthMiddleware::require();
    (new FlashDiscountController())->redeem($user, (int)param('id'), $body);
}

// ---------- Customer Profile ----------
if (matchRoute('GET', 'customers/profile', $path)) {
    $user = AuthMiddleware::require();
    loadController('Customer', 'ProfileController');
    (new CustomerProfileController())->show($user);
}
if (matchRoute('PUT', 'customers/profile', $path)) {
    $user = AuthMiddleware::require();
    loadController('Customer', 'ProfileController');
    (new CustomerProfileController())->update($user, $body);
}

// ---------- Customer Coupons ----------
if (matchRoute('GET', 'customers/coupons/wallet', $path)) {
    $user = AuthMiddleware::require();
    loadController('Customer', 'CouponController');
    (new CustomerCouponController())->wallet($user);
}
if (matchRoute('GET', 'customers/coupons/history', $path)) {
    $user = AuthMiddleware::require();
    loadController('Customer', 'CouponController');
    (new CustomerCouponController())->history($user, $_GET);
}
if (matchRoute('POST', 'customers/coupons/:id/save', $path)) {
    $user = AuthMiddleware::require();
    loadController('Customer', 'CouponController');
    (new CustomerCouponController())->save($user, (int)param('id'));
}
if (matchRoute('DELETE', 'customers/coupons/:id/save', $path)) {
    $user = AuthMiddleware::require();
    loadController('Customer', 'CouponController');
    (new CustomerCouponController())->unsave($user, (int)param('id'));
}
if (matchRoute('POST', 'customers/coupons/redeem', $path)) {
    $user = AuthMiddleware::require();
    loadController('Customer', 'CouponController');
    (new CustomerCouponController())->redeem($user, $body);
}
if (matchRoute('GET', 'customers/gift-coupons', $path)) {
    $user = AuthMiddleware::require();
    loadController('Customer', 'CouponController');
    (new CustomerCouponController())->giftCoupons($user);
}
if (matchRoute('POST', 'customers/gift-coupons/:id/accept', $path)) {
    $user = AuthMiddleware::require();
    loadController('Customer', 'CouponController');
    (new CustomerCouponController())->acceptGift($user, (int)param('id'));
}
if (matchRoute('POST', 'customers/gift-coupons/:id/reject', $path)) {
    $user = AuthMiddleware::require();
    loadController('Customer', 'CouponController');
    (new CustomerCouponController())->rejectGift($user, (int)param('id'));
}

// ---------- Customer Profile (extended) ----------
if (matchRoute('POST', 'customers/profile/image', $path)) {
    $user = AuthMiddleware::require();
    loadController('Customer', 'ProfileController');
    (new CustomerProfileController())->uploadImage($user);
}
if (matchRoute('GET', 'customers/subscription', $path)) {
    $user = AuthMiddleware::require();
    loadController('Customer', 'ProfileController');
    (new CustomerProfileController())->subscription($user);
}
if (matchRoute('PUT', 'customers/profile/password', $path)) {
    $user = AuthMiddleware::require();
    loadController('Customer', 'ProfileController');
    (new CustomerProfileController())->changePassword($user, $body);
}
if (matchRoute('GET', 'customers/stats', $path)) {
    $user = AuthMiddleware::require();
    loadController('Customer', 'ProfileController');
    (new CustomerProfileController())->stats($user);
}

// ---------- Customer Favourites ----------
if (matchRoute('GET', 'customers/favourites/check/:merchantId', $path)) {
    $user = AuthMiddleware::require();
    loadController('Customer', 'FavouriteController');
    (new FavouriteController())->check($user, (int)param('merchantId'));
}
if (matchRoute('GET', 'customers/favourites', $path)) {
    $user = AuthMiddleware::require();
    loadController('Customer', 'FavouriteController');
    (new FavouriteController())->index($user);
}
if (matchRoute('POST', 'customers/favourites/:merchantId', $path)) {
    $user = AuthMiddleware::require();
    loadController('Customer', 'FavouriteController');
    (new FavouriteController())->add($user, (int)param('merchantId'));
}
if (matchRoute('DELETE', 'customers/favourites/:merchantId', $path)) {
    $user = AuthMiddleware::require();
    loadController('Customer', 'FavouriteController');
    (new FavouriteController())->remove($user, (int)param('merchantId'));
}

// ---------- Customer Card ----------
if (matchRoute('POST', 'customers/card/activate', $path)) {
    $user = AuthMiddleware::require();
    loadController('Customer', 'CardController');
    (new CardController())->activate($user, $body);
}
if (matchRoute('GET', 'customers/card', $path)) {
    $user = AuthMiddleware::require();
    loadController('Customer', 'CardController');
    (new CardController())->show($user);
}

// ---------- Customer Notifications ----------
if (matchRoute('GET', 'customers/notifications/unread-count', $path)) {
    $user = AuthMiddleware::require();
    loadController('Customer', 'NotificationController');
    (new CustomerNotificationController())->unreadCount($user);
}
if (matchRoute('GET', 'customers/notifications', $path)) {
    $user = AuthMiddleware::require();
    loadController('Customer', 'NotificationController');
    (new CustomerNotificationController())->index($user, $_GET);
}
if (matchRoute('PUT', 'customers/notifications/read-all', $path)) {
    $user = AuthMiddleware::require();
    loadController('Customer', 'NotificationController');
    (new CustomerNotificationController())->markAllRead($user);
}
if (matchRoute('PUT', 'customers/notifications/:id/read', $path)) {
    $user = AuthMiddleware::require();
    loadController('Customer', 'NotificationController');
    (new CustomerNotificationController())->markRead($user, (int)param('id'));
}
if (matchRoute('DELETE', 'customers/notifications/:id', $path)) {
    $user = AuthMiddleware::require();
    loadController('Customer', 'NotificationController');
    (new CustomerNotificationController())->delete($user, (int)param('id'));
}

// ---------- Health check ----------
if (matchRoute('GET', 'health', $path)) {
    Response::success(['status' => 'ok', 'timestamp' => date('c')], 'API is running');
}

// ── 404 fallback ──────────────────────────────────────────────────────────────
Response::notFound("Endpoint [{$requestMethod}] /api/{$path} not found.");
