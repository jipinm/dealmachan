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
        $cityId = !empty($query['city_id']) ? (int)$query['city_id'] : null;
        $limit  = min(20, max(5, (int)($query['limit'] ?? 10)));

        // ── Advertisements ────────────────────────────────────────────────────
        $adsWhere   = $cityId ? "AND (a.city_id IS NULL OR a.city_id = {$cityId})" : '';
        $ads = $this->db->query(
            "SELECT a.id, a.title, a.image_url, a.link_url, a.ad_type, a.display_order
             FROM advertisements a
             WHERE a.status = 'active'
               AND a.start_date <= CURDATE()
               AND a.end_date   >= CURDATE()
               {$adsWhere}
             ORDER BY a.display_order ASC, a.created_at DESC
             LIMIT 10"
        );

        // ── Flash discounts ───────────────────────────────────────────────────
        $fdWhere = $cityId
            ? "AND EXISTS (SELECT 1 FROM stores st WHERE st.id = fd.store_id AND st.city_id = {$cityId})"
            : '';
        $flashDiscounts = $this->db->query(
            "SELECT fd.id, fd.name, fd.discount_percentage, fd.starts_at, fd.ends_at,
                    m.business_name, m.business_logo,
                    s.name AS store_name
             FROM flash_discounts fd
             JOIN merchants m ON m.id = fd.merchant_id
             LEFT JOIN stores s ON s.id = fd.store_id
             WHERE fd.status = 'active'
               AND fd.starts_at <= NOW()
               AND fd.ends_at   >= NOW()
               {$fdWhere}
             ORDER BY fd.starts_at DESC
             LIMIT {$limit}"
        );

        // ── Featured merchants ────────────────────────────────────────────────
        $mWhere = $cityId
            ? "AND EXISTS (SELECT 1 FROM stores st WHERE st.merchant_id = m.id AND st.city_id = {$cityId} AND st.status='active')"
            : '';
        $merchants = $this->db->query(
            "SELECT m.id, m.business_name, m.business_logo, m.business_category,
                    COUNT(DISTINCT c.id) AS coupon_count
             FROM merchants m
             LEFT JOIN coupons c ON c.merchant_id = m.id AND c.status = 'active' AND c.valid_until >= CURDATE()
             WHERE m.profile_status = 'approved'
               AND m.subscription_status = 'active'
               {$mWhere}
             GROUP BY m.id
             ORDER BY coupon_count DESC, m.created_at DESC
             LIMIT {$limit}"
        );

        // ── Top coupons ───────────────────────────────────────────────────────
        $cWhere = $cityId
            ? "AND EXISTS (SELECT 1 FROM stores st WHERE st.id = c.store_id AND st.city_id = {$cityId})"
            : '';
        $coupons = $this->db->query(
            "SELECT c.id, c.title, c.description, c.discount_type, c.discount_value,
                    c.min_purchase, c.max_discount, c.valid_until, c.coupon_code,
                    m.business_name, m.business_logo,
                    COUNT(cs.id) AS save_count
             FROM coupons c
             JOIN merchants m ON m.id = c.merchant_id
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
            "SELECT t.id, t.tag_name, t.icon, t.color,
                    COUNT(DISTINCT mt.merchant_id) AS merchant_count
             FROM tags t
             LEFT JOIN merchant_tags mt ON mt.tag_id = t.id
             WHERE t.status = 'active'
             GROUP BY t.id
             ORDER BY merchant_count DESC
             LIMIT 12"
        );

        Response::success([
            'advertisements'  => $ads,
            'flash_discounts' => $flashDiscounts,
            'merchants'       => $merchants,
            'top_coupons'     => $coupons,
            'tags'            => $tags,
        ]);
    }
}
