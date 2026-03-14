<?php
/**
 * Deal Machan API â€” Front Controller
 * Routes all /api/* requests to appropriate controllers.
 */

// â”€â”€ Bootstrap â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/config/JWT.php';
require_once __DIR__ . '/helpers/Response.php';
require_once __DIR__ . '/helpers/Validator.php';
require_once __DIR__ . '/helpers/Image.php';
require_once __DIR__ . '/helpers/FeatureGate.php';
require_once __DIR__ . '/middleware/CorsMiddleware.php';
require_once __DIR__ . '/middleware/AuthMiddleware.php';

// â”€â”€ CORS (always first) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
CorsMiddleware::handle();

// â”€â”€ Parse route â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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

// â”€â”€ Router â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
// Pattern: method:path/pattern  â†’  Controller@method
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

// â”€â”€ Route Definitions â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

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
if (matchRoute('PUT', 'merchants/profile/password', $path)) {
    AuthMiddleware::require();
    loadController('Merchant', 'ProfileController');
    (new ProfileController())->changePassword($body);
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
    $cities = $db->query("SELECT id, city_name, state FROM cities WHERE status='active' ORDER BY city_name");
    Response::success($cities);
}
if (matchRoute('GET', 'public/areas', $path)) {
    $db     = Database::getInstance();
    $cityId = !empty($_GET['city_id']) ? (int)$_GET['city_id'] : null;
    $where  = $cityId ? "WHERE status='active' AND city_id = {$cityId}" : "WHERE status='active'";
    $areas  = $db->query("SELECT id, area_name, city_id FROM areas {$where} ORDER BY area_name");
    Response::success($areas);
}
if (matchRoute('GET', 'public/locations', $path)) {
    $areaId = !empty($_GET['area_id']) ? (int)$_GET['area_id'] : null;
    if (!$areaId) { Response::error('area_id is required', 400); }
    $db   = Database::getInstance();
    $locs = $db->query(
        "SELECT id, location_name, latitude, longitude
         FROM locations
         WHERE area_id = ? AND status = 'active'
         ORDER BY location_name",
        [$areaId]
    );
    Response::success($locs);
}
if (matchRoute('GET', 'public/home', $path)) {
    loadController('Public', 'HomeController');
    (new HomeController())->index($_GET);
}
// ---------- Public: Store Browse (Issue 3) ----------
if (matchRoute('GET', 'public/stores', $path)) {
    loadController('Public', 'StoreBrowseController');
    (new StoreBrowseController())->index($_GET);
}
if (matchRoute('GET', 'public/stores/:id/coupons', $path)) {
    loadController('Public', 'StoreBrowseController');
    (new StoreBrowseController())->coupons((int)param('id'), $_GET);
}
if (matchRoute('GET', 'public/stores/:id', $path)) {
    loadController('Public', 'StoreBrowseController');
    (new StoreBrowseController())->show((int)param('id'));
}
if (matchRoute('POST', 'public/stores/:id/reviews', $path)) {
    loadController('Public', 'StoreBrowseController');
    CustomerMiddleware::required();
    (new StoreBrowseController())->submitReview((int)param('id'), json_decode(file_get_contents('php://input'), true) ?? []);
}

// ---------- Public: Categories (Issue 1, 9) ----------
if (matchRoute('GET', 'public/categories', $path)) {
    $db   = Database::getInstance();
    $cats = $db->query(
        "SELECT id, name, icon, color_code FROM categories WHERE status = 'active' ORDER BY name"
    );
    Response::success($cats);
}
if (matchRoute('GET', 'public/categories/:id/sub-categories', $path)) {
    $db   = Database::getInstance();
    $subs = $db->query(
        "SELECT id, category_id, name, icon FROM sub_categories WHERE category_id = ? AND status = 'active' ORDER BY name",
        [(int)param('id')]
    );
    Response::success($subs);
}

// ---------- Public: Card Configurations (C11) ----------
if (matchRoute('GET', 'public/card-configurations', $path)) {
    $db     = Database::getInstance();
    $cityId = !empty($_GET['city_id']) ? (int)$_GET['city_id'] : null;
    $where  = ["cc.status = 'active'", "cc.is_publicly_selectable = 1"];
    $params = [];
    if ($cityId) {
        $where[]  = "(NOT EXISTS (SELECT 1 FROM card_config_cities ccc WHERE ccc.config_id = cc.id)
                      OR EXISTS (SELECT 1 FROM card_config_cities ccc WHERE ccc.config_id = cc.id AND ccc.city_id = ?))";
        $params[] = $cityId;
    }
    $configs = $db->query(
        "SELECT cc.id, cc.name, cc.classification, cc.features_html, cc.price,
                cc.max_live_coupons, cc.coupon_authorization, cc.card_image_front, cc.card_image_back,
                cc.validity_days
         FROM card_configurations cc
         WHERE " . implode(' AND ', $where) . "
         ORDER BY cc.classification, cc.name",
        $params
    );
    foreach ($configs as &$cfg) {
        $cfg['partners'] = $db->query(
            "SELECT id, partner_type, partner_image, url FROM card_config_partners
             WHERE config_id = ? ORDER BY partner_type DESC, sort_order",
            [$cfg['id']]
        );
    }
    unset($cfg);
    Response::success($configs);
}
if (matchRoute('GET', 'public/card-configurations/:id', $path)) {
    $db     = Database::getInstance();
    $config = $db->queryOne(
        "SELECT * FROM card_configurations WHERE id = ? AND status = 'active'",
        [(int)param('id')]
    );
    if (!$config) { Response::error('Not found', 404); }
    $config['partners']            = $db->query(
        "SELECT * FROM card_config_partners WHERE config_id = ? ORDER BY partner_type DESC, sort_order",
        [$config['id']]
    );
    $config['sub_classifications'] = $db->query(
        "SELECT s.name FROM card_config_sub_class_map m JOIN card_sub_classifications s ON s.id = m.sub_class_id WHERE m.config_id = ?",
        [$config['id']]
    );
    Response::success($config);
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
if (matchRoute('POST', 'public/merchants/:id/reviews', $path)) {
    $user = AuthMiddleware::requireCustomer();
    loadController('Public', 'MerchantBrowseController');
    (new MerchantBrowseController())->submitReview((int)param('id'), $body, $user);
}
if (matchRoute('GET', 'public/merchants/:id/gallery', $path)) {
    loadController('Public', 'MerchantBrowseController');
    (new MerchantBrowseController())->gallery((int)param('id'));
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
if (matchRoute('GET', 'public/flash-discounts/:id', $path)) {
    loadController('Public', 'CouponController');
    (new PublicCouponController())->flashDiscountDetail((int)param('id'));
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

// ---------- Public: Forms (contact, business signup) ----------
if (matchRoute('POST', 'public/contact', $path)) {
    loadController('Public', 'PublicFormController');
    (new PublicFormController())->contact($body);
}
if (matchRoute('POST', 'public/business-signup', $path)) {
    loadController('Public', 'PublicFormController');
    (new PublicFormController())->businessSignup($body);
}
if (matchRoute('GET', 'public/page/:slug', $path)) {
    loadController('Public', 'CmsController');
    (new CmsController())->show(param('slug'));
}
if (matchRoute('GET', 'public/contests', $path)) {
    loadController('Customer', 'ContestController');
    (new ContestController())->publicIndex();
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
if (matchRoute('POST', 'merchants/coupons/:id/image', $path)) {
    loadController('Merchant', 'CouponController');
    $user = AuthMiddleware::require();
    (new CouponController())->uploadImage($user, (int)param('id'));
}
if (matchRoute('DELETE', 'merchants/coupons/:id/image', $path)) {
    loadController('Merchant', 'CouponController');
    $user = AuthMiddleware::require();
    (new CouponController())->deleteImage($user, (int)param('id'));
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
// Grievance message thread (Issue 6)
if (matchRoute('GET', 'merchants/grievances/:id/messages', $path)) {
    loadController('Merchant', 'GrievanceController');
    $user = AuthMiddleware::require();
    (new GrievanceController())->messages($user, (int)param('id'));
}
if (matchRoute('POST', 'merchants/grievances/:id/messages', $path)) {
    loadController('Merchant', 'GrievanceController');
    $user = AuthMiddleware::require();
    (new GrievanceController())->addMessage($user, (int)param('id'), $body);
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
if (matchRoute('POST', 'merchants/flash-discounts/:id/image', $path)) {
    loadController('Merchant', 'FlashDiscountController');
    $user = AuthMiddleware::require();
    (new FlashDiscountController())->uploadImage($user, (int)param('id'));
}
if (matchRoute('DELETE', 'merchants/flash-discounts/:id/image', $path)) {
    loadController('Merchant', 'FlashDiscountController');
    $user = AuthMiddleware::require();
    (new FlashDiscountController())->deleteImage($user, (int)param('id'));
}

// ---------- Customer Profile ----------
if (matchRoute('GET', 'customers/profile', $path)) {
    $user = AuthMiddleware::requireCustomer();
    loadController('Customer', 'ProfileController');
    (new CustomerProfileController())->show($user);
}
if (matchRoute('PUT', 'customers/profile', $path)) {
    $user = AuthMiddleware::requireCustomer();
    loadController('Customer', 'ProfileController');
    (new CustomerProfileController())->update($user, $body);
}

// ---------- Customer Coupons ----------
if (matchRoute('GET', 'customers/coupons/wallet', $path)) {
    $user = AuthMiddleware::requireCustomer();
    loadController('Customer', 'CouponController');
    (new CustomerCouponController())->wallet($user);
}
if (matchRoute('GET', 'customers/coupons/history', $path)) {
    $user = AuthMiddleware::requireCustomer();
    loadController('Customer', 'CouponController');
    (new CustomerCouponController())->history($user, $_GET);
}
if (matchRoute('POST', 'customers/coupons/:id/save', $path)) {
    $user = AuthMiddleware::requireCustomer();
    AuthMiddleware::requireActiveCard($user);
    loadController('Customer', 'CouponController');
    (new CustomerCouponController())->save($user, (int)param('id'));
}
if (matchRoute('POST', 'customers/coupons/:id/subscribe', $path)) {
    $user = AuthMiddleware::requireCustomer();
    AuthMiddleware::requireActiveCard($user);
    loadController('Customer', 'CouponController');
    (new CustomerCouponController())->subscribe($user, (int)param('id'));
}
if (matchRoute('DELETE', 'customers/coupons/:id/save', $path)) {
    $user = AuthMiddleware::requireCustomer();
    loadController('Customer', 'CouponController');
    (new CustomerCouponController())->unsave($user, (int)param('id'));
}
if (matchRoute('POST', 'customers/coupons/redeem', $path)) {
    $user = AuthMiddleware::requireCustomer();
    AuthMiddleware::requireActiveCard($user);
    loadController('Customer', 'CouponController');
    (new CustomerCouponController())->redeem($user, $body);
}
if (matchRoute('GET', 'customers/gift-coupons', $path)) {
    $user = AuthMiddleware::requireCustomer();
    loadController('Customer', 'CouponController');
    (new CustomerCouponController())->giftCoupons($user);
}
if (matchRoute('POST', 'customers/gift-coupons/:id/accept', $path)) {
    $user = AuthMiddleware::requireCustomer();
    loadController('Customer', 'CouponController');
    (new CustomerCouponController())->acceptGift($user, (int)param('id'));
}
if (matchRoute('POST', 'customers/gift-coupons/:id/reject', $path)) {
    $user = AuthMiddleware::requireCustomer();
    loadController('Customer', 'CouponController');
    (new CustomerCouponController())->rejectGift($user, (int)param('id'));
}
if (matchRoute('GET', 'customers/store-coupons', $path)) {
    $user = AuthMiddleware::requireCustomer();
    loadController('Customer', 'CouponController');
    (new CustomerCouponController())->storeCoupons($user);
}

// ---------- Customer Profile (extended) ----------
if (matchRoute('POST', 'customers/profile/image', $path)) {
    $user = AuthMiddleware::requireCustomer();
    loadController('Customer', 'ProfileController');
    (new CustomerProfileController())->uploadImage($user);
}
if (matchRoute('GET', 'customers/subscription', $path)) {
    $user = AuthMiddleware::requireCustomer();
    loadController('Customer', 'ProfileController');
    (new CustomerProfileController())->subscription($user);
}
if (matchRoute('PUT', 'customers/profile/password', $path)) {
    $user = AuthMiddleware::requireCustomer();
    loadController('Customer', 'ProfileController');
    (new CustomerProfileController())->changePassword($user, $body);
}
if (matchRoute('POST', 'customers/password/set-new', $path)) {
    $user = AuthMiddleware::requireCustomer();
    loadController('Customer', 'ProfileController');
    (new CustomerProfileController())->setNewPassword($user, $body);
}
if (matchRoute('GET', 'customers/stats', $path)) {
    $user = AuthMiddleware::requireCustomer();
    loadController('Customer', 'ProfileController');
    (new CustomerProfileController())->stats($user);
}

// ---------- Customer Important Days ----------
if (matchRoute('GET', 'customers/important-days', $path)) {
    $user = AuthMiddleware::requireCustomer();
    loadController('Customer', 'ImportantDaysController');
    (new ImportantDaysController())->index($user);
}
if (matchRoute('POST', 'customers/important-days', $path)) {
    $user = AuthMiddleware::requireCustomer();
    loadController('Customer', 'ImportantDaysController');
    (new ImportantDaysController())->store($user, $body);
}
if (matchRoute('DELETE', 'customers/important-days/:id', $path)) {
    $user = AuthMiddleware::requireCustomer();
    loadController('Customer', 'ImportantDaysController');
    (new ImportantDaysController())->destroy($user, (int)param('id'));
}

// ---------- Customer Favourites ----------
if (matchRoute('GET', 'customers/favourites/check/:merchantId', $path)) {
    $user = AuthMiddleware::requireCustomer();
    loadController('Customer', 'FavouriteController');
    (new FavouriteController())->check($user, (int)param('merchantId'));
}
if (matchRoute('GET', 'customers/favourites', $path)) {
    $user = AuthMiddleware::requireCustomer();
    loadController('Customer', 'FavouriteController');
    (new FavouriteController())->index($user);
}
if (matchRoute('POST', 'customers/favourites/:merchantId', $path)) {
    $user = AuthMiddleware::requireCustomer();
    loadController('Customer', 'FavouriteController');
    (new FavouriteController())->add($user, (int)param('merchantId'));
}
if (matchRoute('DELETE', 'customers/favourites/:merchantId', $path)) {
    $user = AuthMiddleware::requireCustomer();
    loadController('Customer', 'FavouriteController');
    (new FavouriteController())->remove($user, (int)param('merchantId'));
}

// ---------- Customer Referrals ----------
if (matchRoute('GET', 'customers/referral', $path)) {
    $user = AuthMiddleware::requireCustomer();
    loadController('Customer', 'ReferralController');
    (new ReferralController())->index($user);
}

// ---------- DealMaker ----------
if (matchRoute('GET', 'customers/dealmaker/status', $path)) {
    $user = AuthMiddleware::requireCustomer();
    loadController('Customer', 'DealmakerController');
    (new DealmakerController())->status($user);
}
if (matchRoute('POST', 'customers/dealmaker/apply', $path)) {
    $user = AuthMiddleware::requireCustomer();
    loadController('Customer', 'DealmakerController');
    (new DealmakerController())->apply($user, $body);
}
if (matchRoute('GET', 'customers/dealmaker/tasks', $path)) {
    $user = AuthMiddleware::requireCustomer();
    loadController('Customer', 'DealmakerController');
    (new DealmakerController())->tasks($user);
}
if (matchRoute('POST', 'customers/dealmaker/tasks/:id/complete', $path)) {
    $user = AuthMiddleware::requireCustomer();
    loadController('Customer', 'DealmakerController');
    (new DealmakerController())->completeTask($user, (int)param('id'), $body);
}
if (matchRoute('GET', 'customers/dealmaker/earnings', $path)) {
    $user = AuthMiddleware::requireCustomer();
    loadController('Customer', 'DealmakerController');
    (new DealmakerController())->earnings($user);
}

// ---------- Surveys ----------
if (matchRoute('GET', 'customers/surveys/completed', $path)) {
    $user = AuthMiddleware::requireCustomer();
    loadController('Customer', 'SurveyController');
    (new SurveyController())->completed($user);
}
if (matchRoute('GET', 'customers/surveys', $path)) {
    $user = AuthMiddleware::requireCustomer();
    loadController('Customer', 'SurveyController');
    (new SurveyController())->index($user);
}
if (matchRoute('GET', 'customers/surveys/:id', $path)) {
    $user = AuthMiddleware::requireCustomer();
    loadController('Customer', 'SurveyController');
    (new SurveyController())->show($user, (int)param('id'));
}
if (matchRoute('POST', 'customers/surveys/:id/submit', $path)) {
    $user = AuthMiddleware::requireCustomer();
    loadController('Customer', 'SurveyController');
    (new SurveyController())->submit($user, (int)param('id'), $body);
}

// ---------- Contests ----------
if (matchRoute('GET', 'customers/contests/my-entries', $path)) {
    $user = AuthMiddleware::requireCustomer();
    loadController('Customer', 'ContestController');
    (new ContestController())->myEntries($user);
}
if (matchRoute('GET', 'customers/contests', $path)) {
    $user = AuthMiddleware::requireCustomer();
    loadController('Customer', 'ContestController');
    (new ContestController())->index($user);
}
if (matchRoute('GET', 'customers/contests/:id', $path)) {
    $user = AuthMiddleware::requireCustomer();
    loadController('Customer', 'ContestController');
    (new ContestController())->show($user, (int)param('id'));
}
if (matchRoute('POST', 'customers/contests/:id/participate', $path)) {
    $user = AuthMiddleware::requireCustomer();
    AuthMiddleware::requireActiveCard($user);
    loadController('Customer', 'ContestController');
    (new ContestController())->participate($user, (int)param('id'), $body);
}

// ---------- Customer Card (Issue 10) ----------
if (matchRoute('POST', 'customers/card/request-auth-code', $path)) {
    $user = AuthMiddleware::requireCustomer();
    loadController('Customer', 'CardController');
    (new CardController())->requestAuthCode($user, $body);
}
if (matchRoute('POST', 'customers/card/activate', $path)) {
    $user = AuthMiddleware::requireCustomer();
    loadController('Customer', 'CardController');
    (new CardController())->activate($user, $body);
}
if (matchRoute('POST', 'customers/card/select', $path)) {
    $user = AuthMiddleware::requireCustomer();
    loadController('Customer', 'CardController');
    (new CardController())->selectCard($user, $body);
}
if (matchRoute('GET', 'customers/card', $path)) {
    $user = AuthMiddleware::requireCustomer();
    loadController('Customer', 'CardController');
    (new CardController())->show($user);
}

// ---------- Customer Notifications ----------
if (matchRoute('GET', 'customers/notifications/unread-count', $path)) {
    $user = AuthMiddleware::requireCustomer();
    loadController('Customer', 'NotificationController');
    (new CustomerNotificationController())->unreadCount($user);
}
if (matchRoute('GET', 'customers/notifications', $path)) {
    $user = AuthMiddleware::requireCustomer();
    loadController('Customer', 'NotificationController');
    (new CustomerNotificationController())->index($user, $_GET);
}
if (matchRoute('PUT', 'customers/notifications/read-all', $path)) {
    $user = AuthMiddleware::requireCustomer();
    loadController('Customer', 'NotificationController');
    (new CustomerNotificationController())->markAllRead($user);
}
if (matchRoute('PUT', 'customers/notifications/:id/read', $path)) {
    $user = AuthMiddleware::requireCustomer();
    loadController('Customer', 'NotificationController');
    (new CustomerNotificationController())->markRead($user, (int)param('id'));
}
if (matchRoute('DELETE', 'customers/notifications/:id', $path)) {
    $user = AuthMiddleware::requireCustomer();
    loadController('Customer', 'NotificationController');
    (new CustomerNotificationController())->delete($user, (int)param('id'));
}

// ---------- Customer Grievances ----------
if (matchRoute('GET', 'customers/grievances', $path)) {
    $user = AuthMiddleware::requireCustomer();
    loadController('Customer', 'GrievanceController');
    (new CustomerGrievanceController())->index($user, $_GET);
}
if (matchRoute('GET', 'customers/grievances/:id', $path)) {
    $user = AuthMiddleware::requireCustomer();
    loadController('Customer', 'GrievanceController');
    (new CustomerGrievanceController())->show($user, (int)param('id'));
}
if (matchRoute('POST', 'customers/grievances', $path)) {
    $user = AuthMiddleware::requireCustomer();
    loadController('Customer', 'GrievanceController');
    (new CustomerGrievanceController())->store($user, $body);
}
if (matchRoute('PUT', 'customers/grievances/:id/archive', $path)) {
    $user = AuthMiddleware::requireCustomer();
    loadController('Customer', 'GrievanceController');
    (new CustomerGrievanceController())->archive($user, (int)param('id'));
}

// ---------- Health check ----------
if (matchRoute('GET', 'health', $path)) {
    Response::success(['status' => 'ok', 'timestamp' => date('c')], 'API is running');
}

// â”€â”€ 404 fallback â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
Response::notFound("Endpoint [{$requestMethod}] /api/{$path} not found.");
