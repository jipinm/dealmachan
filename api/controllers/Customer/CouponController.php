<?php
/**
 * Customer Coupon Controller
 *
 * GET  /api/customers/coupons/wallet
 * GET  /api/customers/coupons/history
 * POST /api/customers/coupons/:id/save
 * DELETE /api/customers/coupons/:id/save
 */
class CustomerCouponController {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ── GET /api/customers/coupons/wallet ─────────────────────────────────────
    public function wallet(array $user): never {
        $customer = $this->db->queryOne(
            "SELECT id FROM customers WHERE user_id = ?", [$user['id']]
        );
        if (!$customer) Response::notFound('Customer profile not found.');

        $customerId = (int)$customer['id'];

        // Saved coupon subscriptions (not yet redeemed)
        $saved = $this->db->query(
            "SELECT cs.id, cs.coupon_id, cs.saved_at,
                    c.title, c.description, c.discount_type, c.discount_value,
                    c.min_purchase, c.max_discount, c.valid_from, c.valid_until,
                    c.terms_conditions, c.coupon_code,
                    m.business_name,
                    s.name AS store_name
             FROM coupon_subscriptions cs
             JOIN coupons c ON c.id = cs.coupon_id
             LEFT JOIN merchants m ON m.id = c.merchant_id
             LEFT JOIN stores s ON s.id = c.store_id
             WHERE cs.customer_id = ? AND cs.status = 'saved'
             ORDER BY cs.saved_at DESC",
            [$customerId]
        );

        // Gift coupons
        $gifts = $this->db->query(
            "SELECT gc.id, gc.coupon_id, gc.status, gc.sent_at, gc.message,
                    c.title, c.description, c.discount_type, c.discount_value,
                    c.valid_until,
                    m.business_name
             FROM gift_coupons gc
             JOIN store_coupons sc ON sc.id = gc.store_coupon_id
             JOIN coupons c ON c.id = sc.coupon_id
             LEFT JOIN merchants m ON m.id = c.merchant_id
             WHERE gc.recipient_customer_id = ? AND gc.status = 'sent'
             ORDER BY gc.sent_at DESC",
            [$customerId]
        );

        Response::success(['saved' => $saved, 'gifts' => $gifts]);
    }

    // ── GET /api/customers/coupons/history ────────────────────────────────────
    public function history(array $user, array $query): never {
        $customer = $this->db->queryOne(
            "SELECT id FROM customers WHERE user_id = ?", [$user['id']]
        );
        if (!$customer) Response::notFound('Customer profile not found.');

        $page    = max(1, (int)($query['page'] ?? 1));
        $perPage = min(50, max(10, (int)($query['per_page'] ?? 20)));
        $offset  = ($page - 1) * $perPage;

        $redemptions = $this->db->query(
            "SELECT cr.id, cr.redeemed_at, cr.discount_amount, cr.original_amount, cr.final_amount,
                    c.title, c.discount_type, c.discount_value,
                    m.business_name,
                    s.name AS store_name
             FROM coupon_redemptions cr
             JOIN coupons c ON c.id = cr.coupon_id
             LEFT JOIN merchants m ON m.id = c.merchant_id
             LEFT JOIN stores s ON s.id = cr.store_id
             WHERE cr.customer_id = ?
             ORDER BY cr.redeemed_at DESC
             LIMIT ? OFFSET ?",
            [$customer['id'], $perPage, $offset]
        );

        $total = $this->db->queryOne(
            "SELECT COUNT(*) AS cnt FROM coupon_redemptions WHERE customer_id = ?",
            [$customer['id']]
        )['cnt'] ?? 0;

        Response::success([
            'data'       => $redemptions,
            'pagination' => [
                'total'    => (int)$total,
                'page'     => $page,
                'per_page' => $perPage,
                'pages'    => (int)ceil($total / $perPage),
            ],
        ]);
    }

    // ── POST /api/customers/coupons/:id/save ─────────────────────────────────
    public function save(array $user, int $couponId): never {
        $customer = $this->db->queryOne(
            "SELECT id FROM customers WHERE user_id = ?", [$user['id']]
        );
        if (!$customer) Response::notFound('Customer profile not found.');

        // Check coupon exists and is active
        $coupon = $this->db->queryOne(
            "SELECT id FROM coupons WHERE id = ? AND status = 'active' AND valid_until >= CURDATE()",
            [$couponId]
        );
        if (!$coupon) Response::notFound('Coupon not found or no longer available.');

        // Check already saved
        $existing = $this->db->queryOne(
            "SELECT id FROM coupon_subscriptions WHERE customer_id = ? AND coupon_id = ?",
            [$customer['id'], $couponId]
        );
        if ($existing) Response::error('Coupon already saved.', 409, 'ALREADY_SAVED');

        $this->db->execute(
            "INSERT INTO coupon_subscriptions (customer_id, coupon_id, status, saved_at)
             VALUES (?, ?, 'saved', NOW())",
            [$customer['id'], $couponId]
        );

        Response::success(['coupon_id' => $couponId], 'Coupon saved to wallet', 201);
    }

    // ── DELETE /api/customers/coupons/:id/save ────────────────────────────────
    public function unsave(array $user, int $couponId): never {
        $customer = $this->db->queryOne(
            "SELECT id FROM customers WHERE user_id = ?", [$user['id']]
        );
        if (!$customer) Response::notFound('Customer profile not found.');

        $deleted = $this->db->execute(
            "DELETE FROM coupon_subscriptions WHERE customer_id = ? AND coupon_id = ? AND status = 'saved'",
            [$customer['id'], $couponId]
        );

        if (!$deleted) Response::notFound('Coupon not found in wallet.');

        Response::success(null, 'Coupon removed from wallet');
    }

    // ── POST /api/customers/coupons/redeem ────────────────────────────────────
    public function redeem(array $user, array $body): never {
        $customer = $this->db->queryOne("SELECT id FROM customers WHERE user_id = ?", [$user['id']]);
        if (!$customer) Response::notFound('Customer profile not found.');

        $couponCode = trim($body['coupon_code'] ?? '');
        $storeId    = !empty($body['store_id']) ? (int)$body['store_id'] : null;

        if (!$couponCode) Response::error('Coupon code is required.', 400, 'MISSING_CODE');

        // Find the coupon by code
        $coupon = $this->db->queryOne(
            "SELECT c.id, c.merchant_id, c.discount_type, c.discount_value, c.min_purchase,
                    c.max_discount, c.valid_until, c.max_redemptions, c.current_redemptions
             FROM coupons c
             WHERE c.coupon_code = ? AND c.status = 'active' AND c.valid_until >= CURDATE()",
            [$couponCode]
        );

        if (!$coupon) Response::notFound('Coupon not found or no longer valid.');

        // Check max redemptions
        if ($coupon['max_redemptions'] && (int)$coupon['current_redemptions'] >= (int)$coupon['max_redemptions']) {
            Response::error('This coupon has reached its maximum redemption limit.', 409, 'MAX_REDEMPTIONS');
        }

        // Check if customer has already redeemed this coupon
        $alreadyRedeemed = $this->db->queryOne(
            "SELECT id FROM coupon_redemptions WHERE customer_id = ? AND coupon_id = ?",
            [$customer['id'], $coupon['id']]
        );
        if ($alreadyRedeemed) Response::error('You have already redeemed this coupon.', 409, 'ALREADY_REDEEMED');

        // Calculate discount (simplified — merchant-side verification handles accurate billing)
        $discountAmount = $coupon['discount_type'] === 'flat'
            ? (float)$coupon['discount_value']
            : 0.0; // Percentage discounts require purchase amount

        // Record redemption
        $this->db->execute(
            "INSERT INTO coupon_redemptions
             (customer_id, coupon_id, store_id, discount_amount, redeemed_at)
             VALUES (?, ?, ?, ?, NOW())",
            [$customer['id'], $coupon['id'], $storeId, $discountAmount]
        );

        // Update counter
        $this->db->execute(
            "UPDATE coupons SET current_redemptions = current_redemptions + 1 WHERE id = ?",
            [$coupon['id']]
        );

        // Mark as redeemed in wallet
        $this->db->execute(
            "UPDATE coupon_subscriptions SET status = 'redeemed', redeemed_at = NOW()
             WHERE customer_id = ? AND coupon_id = ? AND status = 'saved'",
            [$customer['id'], $coupon['id']]
        );

        Response::success([
            'coupon_id'       => (int)$coupon['id'],
            'discount_amount' => $discountAmount,
            'message'         => 'Coupon redeemed successfully!',
        ], 'Redemption recorded', 201);
    }

    // ── GET /api/customers/gift-coupons ───────────────────────────────────────
    public function giftCoupons(array $user): never {
        $customer = $this->db->queryOne("SELECT id FROM customers WHERE user_id = ?", [$user['id']]);
        if (!$customer) Response::notFound('Customer profile not found.');

        $gifts = $this->db->query(
            "SELECT gc.id AS gift_id, gc.coupon_id, gc.status AS acceptance_status, gc.sent_at, gc.message,
                    c.title, c.discount_type, c.discount_value, c.valid_until,
                    m.business_name AS merchant_name, m.business_logo AS merchant_logo
             FROM gift_coupons gc
             JOIN coupons c ON c.id = gc.coupon_id
             LEFT JOIN merchants m ON m.id = c.merchant_id
             WHERE gc.recipient_customer_id = ?
             ORDER BY gc.sent_at DESC",
            [$customer['id']]
        );

        Response::success($gifts);
    }

    // ── POST /api/customers/gift-coupons/:id/accept ───────────────────────────
    public function acceptGift(array $user, int $giftId): never {
        $customer = $this->db->queryOne("SELECT id FROM customers WHERE user_id = ?", [$user['id']]);
        if (!$customer) Response::notFound('Customer profile not found.');

        $gift = $this->db->queryOne(
            "SELECT gc.id, gc.coupon_id, gc.status
             FROM gift_coupons gc
             WHERE gc.id = ? AND gc.recipient_customer_id = ?",
            [$giftId, $customer['id']]
        );
        if (!$gift) Response::notFound('Gift coupon not found.');
        if ($gift['status'] !== 'sent') Response::error('Gift coupon already processed.', 409, 'ALREADY_PROCESSED');

        $this->db->execute(
            "UPDATE gift_coupons SET status = 'accepted', responded_at = NOW() WHERE id = ?",
            [$giftId]
        );

        // Save to wallet
        $existing = $this->db->queryOne(
            "SELECT id FROM coupon_subscriptions WHERE customer_id = ? AND coupon_id = ?",
            [$customer['id'], $gift['coupon_id']]
        );
        if (!$existing) {
            $this->db->execute(
                "INSERT INTO coupon_subscriptions (customer_id, coupon_id, status, saved_at)
                 VALUES (?, ?, 'saved', NOW())",
                [$customer['id'], $gift['coupon_id']]
            );
        }

        Response::success(null, 'Gift coupon accepted and added to your wallet');
    }

    // ── POST /api/customers/gift-coupons/:id/reject ───────────────────────────
    public function rejectGift(array $user, int $giftId): never {
        $customer = $this->db->queryOne("SELECT id FROM customers WHERE user_id = ?", [$user['id']]);
        if (!$customer) Response::notFound('Customer profile not found.');

        $gift = $this->db->queryOne(
            "SELECT id, status FROM gift_coupons WHERE id = ? AND recipient_customer_id = ?",
            [$giftId, $customer['id']]
        );
        if (!$gift) Response::notFound('Gift coupon not found.');
        if ($gift['status'] !== 'sent') Response::error('Gift coupon already processed.', 409, 'ALREADY_PROCESSED');

        $this->db->execute(
            "UPDATE gift_coupons SET status = 'rejected', responded_at = NOW() WHERE id = ?",
            [$giftId]
        );

        Response::success(null, 'Gift coupon declined');
    }
}
