<?php
/**
 * Public Store Browse Controller
 *
 * GET /api/public/stores          — paginated store list for Explore page
 * GET /api/public/stores/:id      — single store detail + active coupons + reviews
 * GET /api/public/stores/:id/coupons  — store coupons (paginated)
 *
 * Stores are the primary discovery entity (Issue 3 fix).
 * Merchant name is returned as metadata.
 */
class StoreBrowseController {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ── GET /api/public/stores ────────────────────────────────────────────────
    public function index(array $query): never {
        $page       = max(1, (int)($query['page']        ?? 1));
        $perPage    = min(50, max(10, (int)($query['per_page']  ?? 20)));
        $offset     = ($page - 1) * $perPage;
        $cityId     = !empty($query['city_id'])     ? (int)$query['city_id']          : null;
        $areaId     = !empty($query['area_id'])     ? (int)$query['area_id']          : null;
        $categoryId = !empty($query['category_id']) ? (int)$query['category_id']      : null;
        $subCatId   = !empty($query['sub_category_id']) ? (int)$query['sub_category_id'] : null;
        $search     = !empty($query['q'])           ? '%' . trim($query['q']) . '%'   : null;

        $where  = ["s.status = 'active'", "m.profile_status = 'approved'", "s.deleted_at IS NULL"];
        $params = [];

        if ($cityId) {
            $where[]  = 's.city_id = ?';
            $params[] = $cityId;
        }
        if ($areaId) {
            $where[]  = 's.area_id = ?';
            $params[] = $areaId;
        }
        if ($categoryId) {
            $where[]  = 'EXISTS (SELECT 1 FROM store_categories sc WHERE sc.store_id = s.id AND sc.category_id = ?)';
            $params[] = $categoryId;
        }
        if ($subCatId) {
            $where[]  = 'EXISTS (SELECT 1 FROM store_categories sc WHERE sc.store_id = s.id AND sc.sub_category_id = ?)';
            $params[] = $subCatId;
        }
        if ($search) {
            $where[]  = '(s.store_name LIKE ? OR m.business_name LIKE ?)';
            $params[] = $search;
            $params[] = $search;
        }

        $whereSQL = implode(' AND ', $where);

        $total = (int)($this->db->queryOne(
            "SELECT COUNT(*) AS cnt
             FROM stores s
             JOIN merchants m ON m.id = s.merchant_id
             WHERE {$whereSQL}",
            $params
        )['cnt'] ?? 0);

        $rows = $this->db->query(
            "SELECT s.id, s.store_name, s.address, s.phone, s.latitude, s.longitude,
                    s.store_image,
                    m.id AS merchant_id, m.business_name, m.business_logo, m.is_premium,
                    m.subscription_plan,
                    ci.city_name, a.area_name,
                    COALESCE(
                        (SELECT ROUND(AVG(r.rating),1) FROM reviews r WHERE r.store_id = s.id AND r.status = 'approved'),
                        0
                    ) AS avg_rating,
                    (SELECT COUNT(*) FROM reviews r WHERE r.store_id = s.id AND r.status = 'approved') AS total_reviews,
                    COUNT(DISTINCT c.id) AS active_coupons_count
             FROM stores s
             JOIN merchants m  ON m.id  = s.merchant_id
             LEFT JOIN cities ci ON ci.id = s.city_id
             LEFT JOIN areas  a  ON a.id  = s.area_id
             LEFT JOIN coupons c ON c.store_id = s.id
                 AND c.status = 'active'
                 AND (c.valid_until IS NULL OR c.valid_until >= CURDATE())
             WHERE {$whereSQL}
             GROUP BY s.id
             ORDER BY m.priority_weight DESC, active_coupons_count DESC, s.created_at DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );

        foreach ($rows as &$row) {
            $row['store_image']   = imageUrl($row['store_image']   ?? null);
            $row['business_logo'] = imageUrl($row['business_logo'] ?? null);

            // Attach primary category tags
            $row['categories'] = $this->db->query(
                "SELECT cat.id, cat.name, cat.icon,
                        sub.id AS sub_category_id, sub.name AS sub_category_name
                 FROM store_categories sc
                 JOIN categories cat         ON cat.id = sc.category_id
                 LEFT JOIN sub_categories sub ON sub.id = sc.sub_category_id
                 WHERE sc.store_id = ?",
                [$row['id']]
            );
        }
        unset($row);

        Response::success([
            'data'       => $rows,
            'pagination' => [
                'total'    => $total,
                'page'     => $page,
                'per_page' => $perPage,
                'pages'    => (int)ceil($total / $perPage),
            ],
        ]);
    }

    // ── GET /api/public/stores/:id ────────────────────────────────────────────
    public function show(int $id): never {
        $isAuth = AuthMiddleware::optional();

        $store = $this->db->queryOne(
            "SELECT s.id, s.store_name, s.address, s.phone, s.email,
                    s.latitude, s.longitude, s.opening_hours, s.description,
                    s.store_image,
                    m.id AS merchant_id, m.business_name, m.business_logo, m.is_premium,
                    m.subscription_plan,
                    ci.city_name, ci.id AS city_id,
                    a.area_name,   a.id  AS area_id,
                    COALESCE(
                        (SELECT ROUND(AVG(r.rating),1) FROM reviews r WHERE r.store_id = s.id AND r.status = 'approved'),
                        0
                    ) AS avg_rating,
                    (SELECT COUNT(*) FROM reviews r WHERE r.store_id = s.id AND r.status = 'approved') AS total_reviews
             FROM stores s
             JOIN merchants m  ON m.id  = s.merchant_id
             LEFT JOIN cities ci ON ci.id = s.city_id
             LEFT JOIN areas  a  ON a.id  = s.area_id
             WHERE s.id = ? AND s.status = 'active' AND s.deleted_at IS NULL
               AND m.profile_status = 'approved'",
            [$id]
        );

        if (!$store) Response::notFound('Store not found.');

        $store['store_image']   = imageUrl($store['store_image']   ?? null);
        $store['business_logo'] = imageUrl($store['business_logo'] ?? null);

        if (!empty($store['opening_hours'])) {
            $decoded = json_decode($store['opening_hours'], true);
            $store['opening_hours'] = is_array($decoded) ? $decoded : [];
        } else {
            $store['opening_hours'] = [];
        }

        // Categories
        $store['categories'] = $this->db->query(
            "SELECT cat.id, cat.name, cat.icon,
                    sub.id AS sub_category_id, sub.name AS sub_category_name
             FROM store_categories sc
             JOIN categories cat         ON cat.id = sc.category_id
             LEFT JOIN sub_categories sub ON sub.id = sc.sub_category_id
             WHERE sc.store_id = ?",
            [$id]
        );

        // Active coupons for this store
        $coupons = $this->db->query(
            "SELECT c.id, c.title, c.description, c.coupon_code, c.discount_type, c.discount_value,
                    c.bogo_buy_quantity, c.bogo_get_quantity, c.addon_item_description,
                    c.banner_image,
                    c.min_purchase_amount, c.max_discount_amount, c.valid_until, c.terms_conditions,
                    COUNT(cs.id) AS save_count
             FROM coupons c
             LEFT JOIN coupon_subscriptions cs ON cs.coupon_id = c.id
             WHERE c.store_id = ? AND c.status = 'active'
               AND (c.valid_until IS NULL OR c.valid_until >= CURDATE())
             GROUP BY c.id
             ORDER BY save_count DESC, c.valid_until ASC",
            [$id]
        );

        if (!$isAuth) {
            foreach ($coupons as &$c) { $c['coupon_code'] = null; }
            unset($c);
        }
        imageUrlField($coupons, 'banner_image');

        // Recent approved reviews
        $reviews = $this->db->query(
            "SELECT r.id, r.rating, r.review_text, r.created_at, cu.name AS customer_name
             FROM reviews r
             LEFT JOIN customers cu ON cu.id = r.customer_id
             WHERE r.store_id = ? AND r.status = 'approved'
             ORDER BY r.created_at DESC
             LIMIT 10",
            [$id]
        );

        Response::success([
            'store'   => $store,
            'coupons' => $coupons,
            'reviews' => $reviews,
        ]);
    }

    // ── GET /api/public/stores/:id/coupons ────────────────────────────────────
    public function coupons(int $id, array $query = []): never {
        $isAuth  = AuthMiddleware::optional();
        $page    = max(1, (int)($query['page'] ?? 1));
        $perPage = 20;
        $offset  = ($page - 1) * $perPage;

        $coupons = $this->db->query(
            "SELECT c.id, c.title, c.description, c.coupon_code, c.discount_type, c.discount_value,
                    c.bogo_buy_quantity, c.bogo_get_quantity, c.addon_item_description,
                    c.banner_image,
                    c.min_purchase_amount, c.max_discount_amount, c.valid_until, c.terms_conditions
             FROM coupons c
             WHERE c.store_id = ? AND c.status = 'active'
               AND (c.valid_until IS NULL OR c.valid_until >= CURDATE())
             ORDER BY c.valid_until ASC
             LIMIT ? OFFSET ?",
            [$id, $perPage, $offset]
        );

        if (!$isAuth) {
            foreach ($coupons as &$c) { $c['coupon_code'] = null; }
            unset($c);
        }
        imageUrlField($coupons, 'banner_image');

        Response::success($coupons);
    }

    // ── POST /api/public/stores/:id/reviews ────────────────────────────────────
    public function submitReview(int $storeId, array $body): never {
        $auth = CustomerMiddleware::required();

        // Validate store exists
        $store = $this->db->queryOne(
            "SELECT s.id FROM stores s WHERE s.id = ? AND s.status = 'active' AND s.deleted_at IS NULL",
            [$storeId]
        );
        if (!$store) Response::notFound('Store not found.');

        $rating     = isset($body['rating']) ? (int)$body['rating'] : 0;
        $reviewText = isset($body['review_text']) ? trim((string)$body['review_text']) : null;

        if ($rating < 1 || $rating > 5) {
            Response::error('Rating must be between 1 and 5.', 422);
        }

        // Prevent duplicate reviews from the same customer for this store
        $existing = $this->db->queryOne(
            "SELECT id FROM reviews WHERE store_id = ? AND customer_id = ?",
            [$storeId, $auth['id']]
        );
        if ($existing) {
            Response::error('You have already submitted a review for this store.', 409);
        }

        $this->db->execute(
            "INSERT INTO reviews (store_id, customer_id, rating, review_text, status, created_at)
             VALUES (?, ?, ?, ?, 'pending', NOW())",
            [$storeId, $auth['id'], $rating, $reviewText ?: null]
        );

        Response::success(['message' => 'Review submitted. It will appear after admin approval.']);
    }
}
