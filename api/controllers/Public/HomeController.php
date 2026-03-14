<?php
/**
 * Public Home Feed Controller
 *
 * GET /api/public/home
 *
 * Returns all data needed to render the customer home screen:
 *   - Active banner advertisements
 *   - Live flash discounts
 *   - Featured merchants
 *   - Top coupons (by subscription count)
 *   - Featured tags
 */
class HomeController {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function index(array $query): never {
        $cityId     = !empty($query['city_id'])     ? (int)$query['city_id']     : null;
        $areaId     = !empty($query['area_id'])     ? (int)$query['area_id']     : null;
        $categoryId = !empty($query['category_id']) ? (int)$query['category_id'] : null;
        $limit      = min(20, max(5, (int)($query['limit'] ?? 10)));

        // Optional auth — coupon codes are only shown to logged-in users
        $isAuthenticated = AuthMiddleware::optional();

        // ── Advertisements ────────────────────────────────────────────────────
        $ads = $this->db->query(
            "SELECT a.id, a.title, a.media_url, a.link_url, a.media_type, a.display_duration
             FROM advertisements a
             WHERE a.status = 'active'
               AND (a.start_date IS NULL OR a.start_date <= NOW())
               AND (a.end_date   IS NULL OR a.end_date   >= NOW())
             ORDER BY a.created_at DESC
             LIMIT 10"
        );

        // ── Flash discounts ───────────────────────────────────────────────────
        $fdWhere  = ["fd.status = 'active'", "fd.valid_from <= NOW()", "fd.valid_until >= NOW()"];
        $fdParams = [];

        if ($cityId) {
            $fdWhere[]  = "(NOT EXISTS (SELECT 1 FROM flash_discount_locations fdl WHERE fdl.flash_discount_id = fd.id)
                            OR EXISTS (SELECT 1 FROM flash_discount_locations fdl WHERE fdl.flash_discount_id = fd.id AND fdl.city_id = ?))";
            $fdParams[] = $cityId;
        }
        if ($areaId) {
            $fdWhere[]  = "(NOT EXISTS (SELECT 1 FROM flash_discount_locations fdl WHERE fdl.flash_discount_id = fd.id)
                            OR EXISTS (SELECT 1 FROM flash_discount_locations fdl WHERE fdl.flash_discount_id = fd.id AND (fdl.area_id = ? OR fdl.area_id IS NULL)))";
            $fdParams[] = $areaId;
        }
        if ($categoryId) {
            $fdWhere[]  = "EXISTS (SELECT 1 FROM flash_discount_categories fdc WHERE fdc.flash_discount_id = fd.id AND fdc.category_id = ?)";
            $fdParams[] = $categoryId;
        }

        $flashDiscounts = $this->db->query(
            "SELECT fd.id,
                    fd.title,
                    fd.discount_percentage,
                    fd.valid_until,
                    fd.banner_image,
                    fd.merchant_id,
                    m.business_name    AS merchant_name,
                    m.business_logo    AS merchant_logo,
                    fd.store_id,
                    s.store_name,
                    s.store_image
             FROM flash_discounts fd
             JOIN merchants m ON m.id = fd.merchant_id
             LEFT JOIN stores s ON s.id = fd.store_id
             WHERE " . implode(' AND ', $fdWhere) . "
             ORDER BY fd.valid_from DESC
             LIMIT ?",
            array_merge($fdParams, [$limit])
        );

        // ── Featured merchants ────────────────────────────────────────────────
        $mWhere = $cityId
            ? "AND EXISTS (SELECT 1 FROM stores st WHERE st.merchant_id = m.id AND st.city_id = {$cityId} AND st.status='active')"
            : '';
        $merchants = $this->db->query(
            "SELECT m.id, m.business_name, m.business_logo,
                    0.00                           AS avg_rating,
                    0                              AS total_reviews,
                    MIN(ar.area_name)              AS area_name,
                    MIN(ci.city_name)              AS city_name,
                    COUNT(DISTINCT c.id)           AS active_coupons_count,
                    m.is_premium
             FROM merchants m
             LEFT JOIN stores s2 ON s2.merchant_id = m.id AND s2.status = 'active'
             LEFT JOIN areas  ar ON ar.id = s2.area_id
             LEFT JOIN cities ci ON ci.id = s2.city_id
             LEFT JOIN coupons c ON c.merchant_id = m.id AND c.status = 'active' AND c.valid_until >= CURDATE()
             WHERE m.profile_status = 'approved'
               AND m.subscription_status IN ('active', 'trial')
               {$mWhere}
             GROUP BY m.id
             ORDER BY active_coupons_count DESC, m.created_at DESC
             LIMIT {$limit}"
        );

        // ── Top coupons ───────────────────────────────────────────────────────
        $cWhere = $cityId
            ? "AND EXISTS (SELECT 1 FROM stores st WHERE st.id = c.store_id AND st.city_id = {$cityId})"
            : '';
        $coupons = $this->db->query(
            "SELECT c.id, c.title, c.coupon_code, c.discount_type, c.discount_value,
                    c.valid_until,
                    c.banner_image,
                    c.merchant_id,
                    m.business_name AS merchant_name,
                    m.business_logo AS merchant_logo,
                    c.store_id,
                    s.store_name,
                    s.store_image,
                    COUNT(cs.id) AS save_count
             FROM coupons c
             JOIN merchants m ON m.id = c.merchant_id
             LEFT JOIN stores s ON s.id = c.store_id
             LEFT JOIN coupon_subscriptions cs ON cs.coupon_id = c.id
             WHERE c.status = 'active'
               AND c.valid_until >= CURDATE()
               AND m.profile_status = 'approved'
               {$cWhere}
             GROUP BY c.id
             ORDER BY save_count DESC, c.created_at DESC
             LIMIT {$limit}"
        );

        // ── Tags ──────────────────────────────────────────────────────────────
        $tags = $this->db->query(
            "SELECT t.id, t.tag_name, NULL AS icon, NULL AS color,
                    COUNT(DISTINCT mt.merchant_id) AS merchant_count
             FROM tags t
             LEFT JOIN merchant_tags mt ON mt.tag_id = t.id
             WHERE t.status = 'active'
             GROUP BY t.id
             ORDER BY merchant_count DESC
             LIMIT 12"
        );

        Response::success([
            'advertisements'     => array_map(static function ($ad) {
                $ad['media_url'] = imageUrl($ad['media_url']);
                return $ad;
            }, $ads),
            'flash_discounts'    => array_map(static function ($fd) {
                $fd['merchant_logo'] = imageUrl($fd['merchant_logo']);
                $fd['banner_image']  = imageUrl($fd['banner_image']);
                $fd['store_image']   = imageUrl($fd['store_image'] ?? null);
                return $fd;
            }, $flashDiscounts),
            'featured_merchants' => array_map(static function ($m) {
                $m['business_logo'] = imageUrl($m['business_logo']);
                return $m;
            }, $merchants),
            'top_coupons'        => array_map(static function ($c) use ($isAuthenticated) {
                $c['merchant_logo'] = imageUrl($c['merchant_logo']);
                $c['banner_image']  = imageUrl($c['banner_image']);
                $c['store_image']   = imageUrl($c['store_image'] ?? null);
                if (!$isAuthenticated) $c['coupon_code'] = null;
                return $c;
            }, $coupons),
            'new_merchants'      => [],   // populated in future by recency-sorted query
            'tags'               => $tags,
        ]);
    }
}
