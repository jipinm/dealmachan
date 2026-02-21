<?php
/**
 * Public Search Controller
 *
 * GET /api/public/search?q=&city_id=&type=
 *
 * Searches across merchants, coupons, stores.
 * Returns categorised results.
 */
class SearchController {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function search(array $query): never {
        $q      = trim($query['q'] ?? '');
        $cityId = !empty($query['city_id']) ? (int)$query['city_id'] : null;
        $type   = $query['type'] ?? 'all'; // all | merchants | coupons

        if (strlen($q) < 2) {
            Response::error('Search query must be at least 2 characters.', 400, 'QUERY_TOO_SHORT');
        }

        $like    = '%' . $q . '%';
        $results = [];

        // ── Merchants ─────────────────────────────────────────────────────────
        if ($type === 'all' || $type === 'merchants') {
            $mWhere  = "m.profile_status = 'approved' AND (m.business_name LIKE ? OR m.business_description LIKE ?)";
            $mParams = [$like, $like];

            if ($cityId) {
                $mWhere   .= " AND EXISTS (SELECT 1 FROM stores st WHERE st.merchant_id = m.id AND st.city_id = ? AND st.status='active')";
                $mParams[] = $cityId;
            }

            $results['merchants'] = $this->db->query(
                "SELECT m.id, m.business_name, m.business_logo, m.business_category,
                        COUNT(DISTINCT c.id) AS coupon_count
                 FROM merchants m
                 LEFT JOIN coupons c ON c.merchant_id = m.id AND c.status='active' AND c.valid_until >= CURDATE()
                 WHERE {$mWhere}
                 GROUP BY m.id
                 ORDER BY coupon_count DESC
                 LIMIT 10",
                $mParams
            );
        }

        // ── Coupons ───────────────────────────────────────────────────────────
        if ($type === 'all' || $type === 'coupons') {
            $cWhere  = "c.status = 'active' AND c.valid_until >= CURDATE()
                        AND m.profile_status = 'approved'
                        AND (c.title LIKE ? OR c.description LIKE ?)";
            $cParams = [$like, $like];

            if ($cityId) {
                $cWhere   .= " AND EXISTS (SELECT 1 FROM stores st WHERE st.id = c.store_id AND st.city_id = ?)";
                $cParams[] = $cityId;
            }

            $results['coupons'] = $this->db->query(
                "SELECT c.id, c.title, c.description, c.discount_type, c.discount_value,
                        c.valid_until, m.business_name, m.business_logo
                 FROM coupons c
                 JOIN merchants m ON m.id = c.merchant_id
                 WHERE {$cWhere}
                 ORDER BY c.valid_until ASC
                 LIMIT 10",
                $cParams
            );
        }

        // ── Stores ────────────────────────────────────────────────────────────
        if ($type === 'all') {
            $sWhere  = "s.status = 'active' AND (s.name LIKE ? OR s.address LIKE ?)";
            $sParams = [$like, $like];

            if ($cityId) {
                $sWhere   .= " AND s.city_id = ?";
                $sParams[] = $cityId;
            }

            $results['stores'] = $this->db->query(
                "SELECT s.id, s.name, s.address,
                        m.business_name, m.business_logo,
                        ci.city_name, a.area_name
                 FROM stores s
                 JOIN merchants m ON m.id = s.merchant_id
                 LEFT JOIN cities ci ON ci.id = s.city_id
                 LEFT JOIN areas  a  ON a.id  = s.area_id
                 WHERE {$sWhere}
                 ORDER BY s.name
                 LIMIT 10",
                $sParams
            );
        }

        Response::success(array_merge(['query' => $q], $results));
    }
}
