<?php
/**
 * Public Merchant Browse Controller
 *
 * GET /api/public/merchants          — paginated merchant list
 * GET /api/public/merchants/:id      — single merchant + stores + active coupons
 */
class MerchantBrowseController {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ── GET /api/public/merchants ─────────────────────────────────────────────
    public function index(array $query): never {
        $page       = max(1, (int)($query['page']       ?? 1));
        $perPage    = min(50, max(10, (int)($query['per_page'] ?? 20)));
        $offset     = ($page - 1) * $perPage;
        $cityId     = !empty($query['city_id'])    ? (int)$query['city_id']    : null;
        $tagId      = !empty($query['tag_id'])     ? (int)$query['tag_id']     : null;
        $category   = !empty($query['category'])   ? trim($query['category'])  : null;
        $categoryId = !empty($query['category_id']) ? (int)$query['category_id'] : null;
        $search     = !empty($query['q'])          ? '%' . trim($query['q']) . '%' : null;

        $where  = ["m.profile_status = 'approved'"];
        $params = [];

        if ($cityId) {
            $where[]  = "EXISTS (SELECT 1 FROM stores st WHERE st.merchant_id = m.id AND st.city_id = ? AND st.status='active')";
            $params[] = $cityId;
        }
        if ($tagId) {
            $where[]  = "EXISTS (SELECT 1 FROM merchant_tags mt WHERE mt.merchant_id = m.id AND mt.tag_id = ?)";
            $params[] = $tagId;
        }
        if ($category) {
            $where[]  = "m.label_id = ?";
            $params[] = (int)$category;
        }
        if ($categoryId) {
            $where[]  = 'EXISTS (SELECT 1 FROM merchant_categories mc WHERE mc.merchant_id = m.id AND mc.category_id = ?)';
            $params[] = $categoryId;
        }
        if ($search) {
            $where[]  = "m.business_name LIKE ?";
            $params[] = $search;
        }

        $whereSQL = implode(' AND ', $where);

        $countRow = $this->db->queryOne(
            "SELECT COUNT(*) AS cnt FROM merchants m WHERE {$whereSQL}",
            $params
        );
        $total = (int)($countRow['cnt'] ?? 0);

        $limitParams = array_merge($params, [$perPage, $offset]);
        $merchants = $this->db->query(
            "SELECT m.id, m.business_name, m.business_logo, m.is_premium,
                    m.subscription_status, m.priority_weight,
                    COUNT(DISTINCT c.id) AS active_coupons_count,
                    COALESCE((SELECT ROUND(AVG(r.rating),1) FROM reviews r WHERE r.merchant_id=m.id AND r.status='approved'),0) AS avg_rating,
                    (SELECT COUNT(*) FROM reviews r WHERE r.merchant_id=m.id AND r.status='approved') AS total_reviews
             FROM merchants m
             LEFT JOIN coupons c ON c.merchant_id = m.id AND c.status='active' AND c.valid_until >= CURDATE()
             WHERE {$whereSQL}
             GROUP BY m.id
             ORDER BY m.priority_weight DESC, active_coupons_count DESC, m.created_at DESC
             LIMIT ? OFFSET ?",
            $limitParams
        );

        imageUrlField($merchants, 'business_logo');

        // Attach categories to each merchant
        foreach ($merchants as &$row) {
            $row['categories'] = $this->db->query(
                "SELECT cat.id, cat.name, cat.icon
                 FROM merchant_categories mc
                 JOIN categories cat ON cat.id = mc.category_id
                 WHERE mc.merchant_id = ?
                 GROUP BY cat.id",
                [$row['id']]
            );
        }
        unset($row);

        Response::success([
            'data'       => $merchants,
            'pagination' => [
                'total'    => $total,
                'page'     => $page,
                'per_page' => $perPage,
                'pages'    => (int)ceil($total / $perPage),
            ],
        ]);
    }

    // ── GET /api/public/merchants/:id ─────────────────────────────────────────
    public function show(int $id): never {
        $isAuth = AuthMiddleware::optional();

        $merchant = $this->db->queryOne(
            "SELECT m.id, m.business_name, m.business_logo, m.is_premium,
                    m.subscription_status,
                    COALESCE((SELECT ROUND(AVG(r.rating),1) FROM reviews r WHERE r.merchant_id=m.id AND r.status='approved'),0) AS avg_rating,
                    (SELECT COUNT(*) FROM reviews r WHERE r.merchant_id=m.id AND r.status='approved') AS total_reviews,
                    (SELECT COUNT(*) FROM coupons c WHERE c.merchant_id=m.id AND c.status='active' AND c.valid_until >= CURDATE()) AS active_coupons_count
             FROM merchants m
             WHERE m.id = ? AND m.profile_status = 'approved'",
            [$id]
        );

        if (!$merchant) Response::notFound('Merchant not found.');

        $merchant['business_logo'] = imageUrl($merchant['business_logo']);

        // Labels
        $labels = $this->db->query(
            "SELECT l.id, l.label_name, l.label_icon
             FROM merchant_labels ml
             JOIN labels l ON l.id = ml.label_id
             WHERE ml.merchant_id = ?",
            [$id]
        );
        $merchant['labels'] = $labels ?? [];

        // Stores
        $stores = $this->db->query(
            "SELECT s.id, s.store_name, s.address, s.phone, s.email,
                    s.latitude, s.longitude, s.opening_hours, s.description,
                    ci.city_name, a.area_name
             FROM stores s
             LEFT JOIN cities ci ON ci.id = s.city_id
             LEFT JOIN areas  a  ON a.id  = s.area_id
             WHERE s.merchant_id = ? AND s.status = 'active'
             ORDER BY s.store_name",
            [$id]
        );
        // Decode opening_hours JSON for each store
        foreach ($stores as &$store) {
            if (!empty($store['opening_hours'])) {
                $decoded = json_decode($store['opening_hours'], true);
                $store['opening_hours'] = is_array($decoded) ? $decoded : [];
            } else {
                $store['opening_hours'] = [];
            }
        }
        unset($store);

        // Active coupons (in show)
        $coupons = $this->db->query(
            "SELECT c.id, c.title, c.description, c.coupon_code, c.discount_type, c.discount_value,
                    c.banner_image,
                    c.min_purchase_amount, c.max_discount_amount, c.valid_until, c.terms_conditions,
                    COUNT(cs.id) AS save_count
             FROM coupons c
             LEFT JOIN coupon_subscriptions cs ON cs.coupon_id = c.id
             WHERE c.merchant_id = ? AND c.status = 'active' AND c.valid_until >= CURDATE()
             GROUP BY c.id
             ORDER BY save_count DESC, c.valid_until ASC",
            [$id]
        );

        if (!$isAuth) {
            foreach ($coupons as &$c) { $c['coupon_code'] = null; }
            unset($c);
        }
        imageUrlField($coupons, 'banner_image');

        Response::success([
            'merchant' => $merchant,
            'stores'   => $stores,
            'coupons'  => $coupons,
        ]);
    }

    // ── GET /api/public/merchants/:id/coupons ─────────────────────────────────
    public function coupons(int $id): never {
        $isAuth = AuthMiddleware::optional();

        $coupons = $this->db->query(
            "SELECT c.id, c.title, c.description, c.coupon_code, c.discount_type, c.discount_value,
                    c.banner_image,
                    c.min_purchase_amount, c.max_discount_amount, c.valid_until, c.terms_conditions
             FROM coupons c
             WHERE c.merchant_id = ? AND c.status = 'active' AND c.valid_until >= CURDATE()
             ORDER BY c.valid_until ASC",
            [$id]
        );

        if (!$isAuth) {
            foreach ($coupons as &$c) { $c['coupon_code'] = null; }
            unset($c);
        }
        imageUrlField($coupons, 'banner_image');

        Response::success($coupons);
    }

    // ── GET /api/public/merchants/:id/reviews ─────────────────────────────────
    public function reviews(int $id, array $query = []): never {
        $page    = max(1, (int)($query['page'] ?? 1));
        $perPage = 10;
        $offset  = ($page - 1) * $perPage;

        $reviews = $this->db->query(
            "SELECT r.id, r.rating, r.review_text, r.created_at,
                    c.name AS customer_name
             FROM reviews r
             LEFT JOIN customers c ON c.id = r.customer_id
             WHERE r.merchant_id = ? AND r.status = 'approved'
             ORDER BY r.created_at DESC
             LIMIT ? OFFSET ?",
            [$id, $perPage, $offset]
        );
        Response::success($reviews);
    }

    // ── POST /api/public/merchants/:id/reviews ────────────────────────────────
    public function submitReview(int $id, array $body, array $user): never {
        $rating = (int)($body['rating'] ?? 0);
        if ($rating < 1 || $rating > 5) {
            Response::error('Rating must be between 1 and 5.', 422, 'INVALID_RATING');
        }
        $reviewText = trim($body['review_text'] ?? '') ?: null;

        // Get customer for this user
        $customer = $this->db->queryOne(
            "SELECT id FROM customers WHERE user_id = ?",
            [$user['id']]
        );
        if (!$customer) Response::notFound('Customer profile not found.');

        // Check merchant exists and is approved
        $merchant = $this->db->queryOne(
            "SELECT id FROM merchants WHERE id = ? AND profile_status = 'approved'",
            [$id]
        );
        if (!$merchant) Response::notFound('Merchant not found.');

        // Upsert: one review per customer per merchant
        $existing = $this->db->queryOne(
            "SELECT id FROM reviews WHERE merchant_id = ? AND customer_id = ?",
            [$id, $customer['id']]
        );

        if ($existing) {
            $this->db->execute(
                "UPDATE reviews SET rating = ?, review_text = ?, status = 'approved', updated_at = NOW()
                 WHERE id = ?",
                [$rating, $reviewText, $existing['id']]
            );
            Response::success(['message' => 'Review updated successfully.']);
        } else {
            $this->db->execute(
                "INSERT INTO reviews (customer_id, merchant_id, rating, review_text, status)
                 VALUES (?, ?, ?, ?, 'approved')",
                [$customer['id'], $id, $rating, $reviewText]
            );
            Response::success(['message' => 'Review submitted successfully.']);
        }
    }

    // ── GET /api/public/merchants/:id/gallery ─────────────────────────────────
    public function gallery(int $id): never {
        // Verify merchant exists and is approved
        $exists = $this->db->queryOne(
            "SELECT id FROM merchants WHERE id = ? AND profile_status = 'approved'",
            [$id]
        );
        if (!$exists) Response::notFound('Merchant not found.');

        $images = $this->db->query(
            "SELECT g.id, g.image_url, g.caption, g.display_order,
                    s.id AS store_id, s.store_name
             FROM store_gallery g
             JOIN stores s ON s.id = g.store_id
             WHERE s.merchant_id = ? AND s.status = 'active'
             ORDER BY s.store_name ASC, g.display_order ASC",
            [$id]
        );

        foreach ($images as &$img) {
            $img['image_url'] = imageUrl($img['image_url']);
        }
        unset($img);

        Response::success($images);
    }
}
