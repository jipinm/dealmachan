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

        $limitParams = array_merge($params, [$perPage, $offset]);
        $coupons = $this->db->query(
            "SELECT c.id, c.title, c.description, c.discount_type, c.discount_value,
                    c.min_purchase, c.max_discount, c.valid_until, c.coupon_code,
                    c.terms_conditions, c.banner_image,
                    m.id AS merchant_id, m.business_name, m.business_logo,
                    m.business_category
             FROM coupons c
             JOIN merchants m ON m.id = c.merchant_id AND m.profile_status = 'approved'
             WHERE {$whereSQL}
             ORDER BY c.created_at DESC
             LIMIT ? OFFSET ?",
            $limitParams
        );

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
        $coupon = $this->db->queryOne(
            "SELECT c.id, c.title, c.description, c.discount_type, c.discount_value,
                    c.min_purchase AS min_purchase_amount, c.max_discount AS max_discount_amount,
                    c.valid_until AS expiry_date, c.coupon_code, c.terms_conditions,
                    c.banner_image,
                    m.id AS merchant_id, m.business_name, m.business_logo, m.business_category,
                    m.business_description, m.website_url,
                    m.subscription_status
             FROM coupons c
             JOIN merchants m ON m.id = c.merchant_id AND m.profile_status = 'approved'
             WHERE c.id = ? AND c.status = 'active'",
            [$id]
        );

        if (!$coupon) Response::notFound('Coupon not found.');

        // Merchant's stores where this coupon is valid
        $stores = $this->db->query(
            "SELECT s.id, s.name AS store_name, s.address, s.phone,
                    ci.city_name, a.area_name
             FROM stores s
             LEFT JOIN cities ci ON ci.id = s.city_id
             LEFT JOIN areas   a ON a.id  = s.area_id
             WHERE s.merchant_id = ? AND s.status = 'active'
             ORDER BY s.name",
            [$coupon['merchant_id']]
        );

        // Separate merchant object
        $merchant = [
            'id'                   => $coupon['merchant_id'],
            'business_name'        => $coupon['business_name'],
            'business_logo'        => $coupon['business_logo'],
            'business_category'    => $coupon['business_category'],
            'business_description' => $coupon['business_description'],
            'website_url'          => $coupon['website_url'],
        ];

        unset(
            $coupon['merchant_id'], $coupon['business_name'], $coupon['business_logo'],
            $coupon['business_category'], $coupon['business_description'], $coupon['website_url'],
            $coupon['subscription_status']
        );

        Response::success([
            'coupon'   => $coupon,
            'merchant' => $merchant,
            'stores'   => $stores,
        ]);
    }

    // ── GET /api/public/flash-discounts ───────────────────────────────────────
    public function flashDiscounts(array $query): never {
        $cityId = !empty($query['city_id']) ? (int)$query['city_id'] : null;

        $where  = ["c.status = 'active'", "c.valid_until >= CURDATE()", "c.is_flash_deal = 1"];
        $params = [];

        if ($cityId) {
            $where[]  = "EXISTS (SELECT 1 FROM stores s WHERE s.merchant_id = c.merchant_id AND s.city_id = ? AND s.status='active')";
            $params[] = $cityId;
        }

        $whereSQL = implode(' AND ', $where);

        $deals = $this->db->query(
            "SELECT c.id, c.title, c.discount_type, c.discount_value, c.valid_until,
                    m.id AS merchant_id, m.business_name AS merchant_name, m.business_logo AS merchant_logo
             FROM coupons c
             JOIN merchants m ON m.id = c.merchant_id AND m.profile_status = 'approved'
             WHERE {$whereSQL}
             ORDER BY c.valid_until ASC
             LIMIT 20",
            $params
        );

        Response::success($deals);
    }
}
