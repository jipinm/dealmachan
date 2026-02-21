<?php
/**
 * Customer Favourite Merchants Controller
 *
 * GET    /api/customers/favourites
 * POST   /api/customers/favourites/:merchantId
 * DELETE /api/customers/favourites/:merchantId
 */
class FavouriteController {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    private function getCustomer(array $user): int {
        $row = $this->db->queryOne("SELECT id FROM customers WHERE user_id = ?", [$user['id']]);
        if (!$row) Response::notFound('Customer profile not found.');
        return (int)$row['id'];
    }

    // ── GET /api/customers/favourites ─────────────────────────────────────────
    public function index(array $user): never {
        $cid = $this->getCustomer($user);

        $favourites = $this->db->query(
            "SELECT m.id, m.business_name, m.business_logo, m.business_category,
                    m.subscription_status,
                    cf.saved_at,
                    COUNT(DISTINCT c.id) AS active_coupon_count,
                    COALESCE(AVG(mr.rating), 0) AS avg_rating,
                    COUNT(DISTINCT mr.id)         AS total_reviews
             FROM customer_favourite_merchants cf
             JOIN merchants m ON m.id = cf.merchant_id
             LEFT JOIN coupons c ON c.merchant_id = m.id AND c.status = 'active' AND c.valid_until >= CURDATE()
             LEFT JOIN merchant_reviews mr ON mr.merchant_id = m.id AND mr.status = 'approved'
             WHERE cf.customer_id = ?
             GROUP BY m.id, cf.saved_at
             ORDER BY cf.saved_at DESC",
            [$cid]
        );

        Response::success($favourites);
    }

    // ── POST /api/customers/favourites/:merchantId ────────────────────────────
    public function add(array $user, int $merchantId): never {
        $cid = $this->getCustomer($user);

        // Verify merchant exists
        $merchant = $this->db->queryOne("SELECT id FROM merchants WHERE id = ? AND profile_status = 'approved'", [$merchantId]);
        if (!$merchant) Response::notFound('Merchant not found.');

        // Idempotent insert
        $existing = $this->db->queryOne(
            "SELECT id FROM customer_favourite_merchants WHERE customer_id = ? AND merchant_id = ?",
            [$cid, $merchantId]
        );
        if ($existing) Response::success(null, 'Already in favourites');

        $this->db->execute(
            "INSERT INTO customer_favourite_merchants (customer_id, merchant_id, saved_at) VALUES (?, ?, NOW())",
            [$cid, $merchantId]
        );

        Response::success(['merchant_id' => $merchantId], 'Added to favourites', 201);
    }

    // ── DELETE /api/customers/favourites/:merchantId ──────────────────────────
    public function remove(array $user, int $merchantId): never {
        $cid = $this->getCustomer($user);

        $deleted = $this->db->execute(
            "DELETE FROM customer_favourite_merchants WHERE customer_id = ? AND merchant_id = ?",
            [$cid, $merchantId]
        );

        if (!$deleted) Response::notFound('Merchant not in favourites.');

        Response::success(null, 'Removed from favourites');
    }

    // ── GET /api/customers/favourites/check/:merchantId ───────────────────────
    public function check(array $user, int $merchantId): never {
        $cid = $this->getCustomer($user);

        $row = $this->db->queryOne(
            "SELECT id FROM customer_favourite_merchants WHERE customer_id = ? AND merchant_id = ?",
            [$cid, $merchantId]
        );

        Response::success(['is_favourite' => (bool)$row]);
    }
}
