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
        $page     = max(1, (int)($query['page']     ?? 1));
        $perPage  = min(50, max(10, (int)($query['per_page'] ?? 20)));
        $offset   = ($page - 1) * $perPage;
        $cityId   = !empty($query['city_id'])  ? (int)$query['city_id']  : null;
        $tagId    = !empty($query['tag_id'])   ? (int)$query['tag_id']   : null;
        $category = !empty($query['category']) ? trim($query['category']) : null;
        $search   = !empty($query['q'])        ? '%' . trim($query['q']) . '%' : null;

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
            $where[]  = "m.business_category = ?";
            $params[] = $category;
        }
        if ($search) {
            $where[]  = "(m.business_name LIKE ? OR m.business_description LIKE ?)";
            $params[] = $search;
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
            "SELECT m.id, m.business_name, m.business_logo, m.business_category,
                    m.business_description, m.subscription_status,
                    COUNT(DISTINCT c.id) AS active_coupon_count
             FROM merchants m
             LEFT JOIN coupons c ON c.merchant_id = m.id AND c.status='active' AND c.valid_until >= CURDATE()
             WHERE {$whereSQL}
             GROUP BY m.id
             ORDER BY active_coupon_count DESC, m.created_at DESC
             LIMIT ? OFFSET ?",
            $limitParams
        );

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
        $merchant = $this->db->queryOne(
            "SELECT m.id, m.business_name, m.business_logo, m.business_category,
                    m.business_description, m.website_url, m.subscription_status
             FROM merchants m
             WHERE m.id = ? AND m.profile_status = 'approved'",
            [$id]
        );

        if (!$merchant) Response::notFound('Merchant not found.');

        // Stores
        $stores = $this->db->query(
            "SELECT s.id, s.name, s.address, s.phone, s.opening_time, s.closing_time,
                    ci.city_name, a.area_name
             FROM stores s
             LEFT JOIN cities ci ON ci.id = s.city_id
             LEFT JOIN areas  a  ON a.id  = s.area_id
             WHERE s.merchant_id = ? AND s.status = 'active'
             ORDER BY s.name",
            [$id]
        );

        // Active coupons
        $coupons = $this->db->query(
            "SELECT c.id, c.title, c.description, c.discount_type, c.discount_value,
                    c.min_purchase, c.max_discount, c.valid_until, c.terms_conditions,
                    COUNT(cs.id) AS save_count
             FROM coupons c
             LEFT JOIN coupon_subscriptions cs ON cs.coupon_id = c.id
             WHERE c.merchant_id = ? AND c.status = 'active' AND c.valid_until >= CURDATE()
             GROUP BY c.id
             ORDER BY save_count DESC, c.valid_until ASC",
            [$id]
        );

        Response::success([
            'merchant' => $merchant,
            'stores'   => $stores,
            'coupons'  => $coupons,
        ]);
    }

    // ── GET /api/public/merchants/:id/coupons ─────────────────────────────────
    public function coupons(int $id): never {
        $coupons = $this->db->query(
            "SELECT c.id, c.title, c.description, c.discount_type, c.discount_value,
                    c.min_purchase, c.max_discount, c.valid_until, c.terms_conditions
             FROM coupons c
             WHERE c.merchant_id = ? AND c.status = 'active' AND c.valid_until >= CURDATE()
             ORDER BY c.valid_until ASC",
            [$id]
        );
        Response::success($coupons);
    }

    // ── GET /api/public/merchants/:id/reviews ─────────────────────────────────
    public function reviews(int $id, array $query = []): never {
        $page    = max(1, (int)($query['page'] ?? 1));
        $perPage = 10;
        $offset  = ($page - 1) * $perPage;

        $reviews = $this->db->query(
            "SELECT mr.id, mr.rating, mr.review_text, mr.created_at,
                    u.name AS customer_name
             FROM merchant_reviews mr
             LEFT JOIN users u ON u.id = mr.customer_id
             WHERE mr.merchant_id = ? AND mr.status = 'approved'
             ORDER BY mr.created_at DESC
             LIMIT ? OFFSET ?",
            [$id, $perPage, $offset]
        );
        Response::success($reviews);
    }
}
