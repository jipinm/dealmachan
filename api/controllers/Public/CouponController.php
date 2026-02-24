<?php
/**
 * Public Coupon Controller
 *
 * GET /api/public/coupons            — paginated coupon browse
 * GET /api/public/coupons/:id        — single coupon detail
 * GET /api/public/flash-discounts    — flash deals with countdown
 */
class PublicCouponController {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ── GET /api/public/coupons ───────────────────────────────────────────────
    public function index(array $query): never {
        $page     = max(1, (int)($query['page']     ?? 1));
        $perPage  = min(50, max(10, (int)($query['per_page'] ?? 20)));
        $offset   = ($page - 1) * $perPage;

        $cityId       = !empty($query['city_id'])       ? (int)$query['city_id']            : null;
        $tagId        = !empty($query['tag_id'])        ? (int)$query['tag_id']             : null;
        $discountType = !empty($query['discount_type']) ? trim($query['discount_type'])      : null;
        $search       = !empty($query['q'])             ? '%' . trim($query['q']) . '%'
                      : (!empty($query['search'])       ? '%' . trim($query['search']) . '%' : null);

        $where  = ["c.status = 'active'", "c.valid_until >= CURDATE()"];
        $params = [];

        if ($cityId) {
            $where[]  = "EXISTS (SELECT 1 FROM stores s WHERE s.merchant_id = c.merchant_id AND s.city_id = ? AND s.status='active')";
            $params[] = $cityId;
        }
        if ($tagId) {
            $where[]  = "EXISTS (SELECT 1 FROM merchant_tags mt WHERE mt.merchant_id = c.merchant_id AND mt.tag_id = ?)";
            $params[] = $tagId;
        }
        if ($discountType) {
            $where[]  = "c.discount_type = ?";
            $params[] = $discountType;
        }
        if ($search) {
            $where[]  = "(c.title LIKE ? OR c.description LIKE ? OR m.business_name LIKE ?)";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        $whereSQL = implode(' AND ', $where);

        $countRow = $this->db->queryOne(
            "SELECT COUNT(*) AS cnt
             FROM coupons c
             JOIN merchants m ON m.id = c.merchant_id
             WHERE {$whereSQL}",
            $params
        );
        $total = (int)($countRow['cnt'] ?? 0);

        // Check if user is authenticated (optional — public route)
        $isAuthenticated = AuthMiddleware::optional();

        $limitParams = array_merge($params, [$perPage, $offset]);
        $coupons = $this->db->query(
            "SELECT c.id, c.title, c.description, c.discount_type, c.discount_value,
                    c.min_purchase_amount, c.max_discount_amount, c.valid_until,
                    c.coupon_code,
                    c.terms_conditions,
                    c.banner_image,
                    m.id AS merchant_id,
                    m.business_name AS merchant_name,
                    m.business_logo AS merchant_logo,
                    m.is_premium
             FROM coupons c
             JOIN merchants m ON m.id = c.merchant_id AND m.profile_status = 'approved'
             WHERE {$whereSQL}
             ORDER BY c.created_at DESC
             LIMIT ? OFFSET ?",
            $limitParams
        );

        // Hide coupon code for unauthenticated / guest users
        if (!$isAuthenticated) {
            foreach ($coupons as &$c) {
                $c['coupon_code'] = null;
            }
            unset($c);
        }

        imageUrlField($coupons, 'merchant_logo');
        imageUrlField($coupons, 'banner_image');

        Response::success([
            'data'       => $coupons,
            'pagination' => [
                'total'    => $total,
                'page'     => $page,
                'per_page' => $perPage,
                'pages'    => (int)ceil($total / $perPage),
            ],
        ]);
    }

    // ── GET /api/public/coupons/:id ───────────────────────────────────────────
    public function show(int $id): never {
        $isAuthenticated = AuthMiddleware::optional();

        $coupon = $this->db->queryOne(
            "SELECT c.id, c.title, c.description, c.discount_type, c.discount_value,
                    c.min_purchase_amount, c.max_discount_amount,
                    c.valid_until, c.coupon_code, c.terms_conditions,
                    c.banner_image,
                    m.id AS merchant_id, m.business_name, m.business_logo, m.is_premium
             FROM coupons c
             JOIN merchants m ON m.id = c.merchant_id AND m.profile_status = 'approved'
             WHERE c.id = ? AND c.status = 'active'",
            [$id]
        );

        if (!$coupon) Response::notFound('Coupon not found.');

        // Hide coupon code for unauthenticated / guest users
        if (!$isAuthenticated) {
            $coupon['coupon_code'] = null;
        }

        // Merchant's stores where this coupon is valid
        $stores = $this->db->query(
            "SELECT s.id, s.store_name, s.address, s.phone,
                    ci.city_name, a.area_name
             FROM stores s
             LEFT JOIN cities ci ON ci.id = s.city_id
             LEFT JOIN areas   a ON a.id  = s.area_id
             WHERE s.merchant_id = ? AND s.status = 'active'
             ORDER BY s.store_name",
            [$coupon['merchant_id']]
        );

        // Separate merchant object
        $merchant = [
            'id'            => $coupon['merchant_id'],
            'business_name' => $coupon['business_name'],
            'business_logo' => imageUrl($coupon['business_logo']),
            'is_premium'    => (bool)$coupon['is_premium'],
        ];

        unset(
            $coupon['merchant_id'], $coupon['business_name'],
            $coupon['business_logo'], $coupon['is_premium']
        );

        $coupon['banner_image'] = imageUrl($coupon['banner_image']);

        Response::success([
            'coupon'   => $coupon,
            'merchant' => $merchant,
            'stores'   => $stores,
        ]);
    }

    // ── GET /api/public/flash-discounts ───────────────────────────────────────
    public function flashDiscounts(array $query): never {
        $cityId = !empty($query['city_id']) ? (int)$query['city_id'] : null;

        $where  = ["fd.status = 'active'", "fd.valid_until >= NOW()"];
        $params = [];

        if ($cityId) {
            $where[]  = "(fd.store_id IS NULL OR EXISTS (SELECT 1 FROM stores s WHERE s.id = fd.store_id AND s.city_id = ? AND s.status='active'))";
            $params[] = $cityId;
        }

        $whereSQL = implode(' AND ', $where);

        $deals = $this->db->query(
            "SELECT fd.id, fd.title, fd.discount_percentage, fd.valid_until,
                    fd.banner_image,
                    m.id AS merchant_id, m.business_name AS merchant_name, m.business_logo AS merchant_logo
             FROM flash_discounts fd
             JOIN merchants m ON m.id = fd.merchant_id AND m.profile_status = 'approved'
             WHERE {$whereSQL}
             ORDER BY fd.valid_until ASC
             LIMIT 30",
            $params
        );

        imageUrlField($deals, 'merchant_logo');
        imageUrlField($deals, 'banner_image');

        Response::success($deals);
    }

    // ── GET /api/public/flash-discounts/:id ───────────────────────────────────
    public function flashDiscountDetail(int $id): never {
        $deal = $this->db->queryOne(
            "SELECT fd.id, fd.title, fd.description, fd.discount_percentage,
                    fd.valid_from, fd.valid_until,
                    fd.max_redemptions, fd.current_redemptions, fd.status,
                    fd.banner_image,
                    m.id AS merchant_id, m.business_name AS merchant_name,
                    m.business_logo AS merchant_logo,
                    s.id AS store_id, s.store_name,
                    s.address AS store_address, s.phone AS store_phone,
                    ci.city_name, a.area_name
             FROM flash_discounts fd
             JOIN merchants m ON m.id = fd.merchant_id AND m.profile_status = 'approved'
             LEFT JOIN stores s  ON s.id = fd.store_id
             LEFT JOIN cities ci ON ci.id = s.city_id
             LEFT JOIN areas  a  ON a.id  = s.area_id
             WHERE fd.id = ? AND fd.status = 'active' AND fd.valid_until >= NOW()",
            [$id]
        );

        if (!$deal) Response::notFound('Flash deal not found or has expired.');

        $deal['merchant_logo'] = imageUrl($deal['merchant_logo']);
        $deal['banner_image']  = imageUrl($deal['banner_image']);

        Response::success($deal);
    }
}
