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
                    c.min_purchase_amount, c.max_discount_amount, c.valid_from, c.valid_until,
                    c.terms_conditions, c.coupon_code,
                    m.business_name AS merchant_name, m.business_logo AS merchant_logo,
                    s.store_name
             FROM coupon_subscriptions cs
             JOIN coupons c ON c.id = cs.coupon_id
             LEFT JOIN merchants m ON m.id = c.merchant_id
             LEFT JOIN stores s ON s.id = c.store_id
             WHERE cs.customer_id = ? AND cs.status = 'saved'
             ORDER BY cs.saved_at DESC",
            [$customerId]
        );

        // Gift coupons pending acceptance
        $gifts = $this->db->query(
            "SELECT gc.id AS gift_id, gc.coupon_id, gc.acceptance_status, gc.gifted_at,
                    c.title, c.description, c.discount_type, c.discount_value,
                    c.valid_until, c.coupon_code,
                    m.business_name AS merchant_name, m.business_logo AS merchant_logo
             FROM gift_coupons gc
             JOIN coupons c ON c.id = gc.coupon_id
             LEFT JOIN merchants m ON m.id = c.merchant_id
             WHERE gc.customer_id = ? AND gc.acceptance_status = 'pending'
             ORDER BY gc.gifted_at DESC",
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
            "SELECT cr.id, cr.redeemed_at, cr.discount_amount, cr.transaction_amount,
                    c.title, c.coupon_code, c.discount_type, c.discount_value,
                    m.business_name AS merchant_name,
                    s.store_name
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

    // ── POST /api/customers/coupons/:id/subscribe ─────────────────────────────
    // 6-rule validated subscribe (proper replacement for /save over time)
    public function subscribe(array $user, int $couponId): never {
        // ── 1. Resolve customer ──────────────────────────────────────────────
        $customer = $this->db->queryOne(
            "SELECT c.id, c.customer_type, c.subscription_status
             FROM customers c WHERE c.user_id = ?",
            [$user['id']]
        );
        if (!$customer) Response::notFound('Customer profile not found.');

        $customerId = (int)$customer['id'];

        // ── 2. Coupon exists ─────────────────────────────────────────────────
        $coupon = $this->db->queryOne(
            "SELECT id, title, status, approval_status,
                    valid_from, valid_until,
                    usage_limit, usage_count
             FROM coupons WHERE id = ?",
            [$couponId]
        );
        if (!$coupon) {
            Response::notFound('Coupon not found.');
        }

        // ── 3. Active & approved ─────────────────────────────────────────────
        if ($coupon['status'] !== 'active') {
            Response::error('This coupon is no longer active.', 409, 'COUPON_INACTIVE');
        }
        if ($coupon['approval_status'] !== 'approved') {
            Response::error('This coupon is not yet approved.', 409, 'COUPON_NOT_APPROVED');
        }

        // ── 4. Not before valid_from ─────────────────────────────────────────
        if ($coupon['valid_from'] && strtotime($coupon['valid_from']) > time()) {
            Response::error('This coupon is not yet available.', 409, 'COUPON_NOT_STARTED');
        }

        // ── 5. Not expired ───────────────────────────────────────────────────
        if ($coupon['valid_until'] && strtotime($coupon['valid_until']) < strtotime('today')) {
            Response::error('This coupon has expired.', 410, 'COUPON_EXPIRED');
        }

        // ── 6. Usage / availability limit ────────────────────────────────────
        if ($coupon['usage_limit'] !== null && (int)$coupon['usage_count'] >= (int)$coupon['usage_limit']) {
            Response::error('This coupon is no longer available — all slots have been taken.', 409, 'COUPON_LIMIT_REACHED');
        }

        // ── 7. Duplicate check ───────────────────────────────────────────────
        $already = $this->db->queryOne(
            "SELECT id FROM coupon_subscriptions WHERE customer_id = ? AND coupon_id = ?",
            [$customerId, $couponId]
        );
        if ($already) {
            Response::error('You have already saved this coupon.', 409, 'ALREADY_SAVED');
        }

        // ── 8. Per-customer live subscription limit ───────────────────────────
        // Premium customers: 50 live coupons; Standard (no subscription): 10
        $isPremium = $customer['customer_type'] === 'premium'
            || $customer['customer_type'] === 'dealmaker'
            || $customer['subscription_status'] === 'active';
        $limit = $isPremium ? 50 : 10;

        $liveCount = (int)($this->db->queryOne(
            "SELECT COUNT(*) AS cnt
             FROM coupon_subscriptions cs
             WHERE cs.customer_id = ?",
            [$customerId]
        )['cnt'] ?? 0);

        if ($liveCount >= $limit) {
            Response::error(
                $isPremium
                    ? "You have reached the maximum of {$limit} saved coupons."
                    : "Free accounts can save up to {$limit} coupons at a time. Upgrade to Premium for more.",
                409, 'SUBSCRIPTION_LIMIT_REACHED'
            );
        }

        // ── Insert ───────────────────────────────────────────────────────────
        $this->db->execute(
            "INSERT INTO coupon_subscriptions (customer_id, coupon_id, status, saved_at)
             VALUES (?, ?, 'saved', NOW())",
            [$customerId, $couponId]
        );

        Response::success(
            ['coupon_id' => $couponId, 'coupon_title' => $coupon['title']],
            'Coupon saved to your wallet!',
            201
        );
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
            "SELECT gc.id AS gift_id, gc.coupon_id, gc.acceptance_status, gc.gifted_at,
                    c.title, c.discount_type, c.discount_value, c.valid_until, c.coupon_code,
                    m.business_name AS merchant_name, m.business_logo AS merchant_logo
             FROM gift_coupons gc
             JOIN coupons c ON c.id = gc.coupon_id
             LEFT JOIN merchants m ON m.id = c.merchant_id
             WHERE gc.customer_id = ?
             ORDER BY gc.gifted_at DESC",
            [$customer['id']]
        );

        Response::success($gifts);
    }

    // ── POST /api/customers/gift-coupons/:id/accept ───────────────────────────
    public function acceptGift(array $user, int $giftId): never {
        $customer = $this->db->queryOne("SELECT id FROM customers WHERE user_id = ?", [$user['id']]);
        if (!$customer) Response::notFound('Customer profile not found.');

        $gift = $this->db->queryOne(
            "SELECT gc.id, gc.coupon_id, gc.acceptance_status
             FROM gift_coupons gc
             WHERE gc.id = ? AND gc.customer_id = ?",
            [$giftId, $customer['id']]
        );
        if (!$gift) Response::notFound('Gift coupon not found.');
        if ($gift['acceptance_status'] !== 'pending') Response::error('Gift coupon already processed.', 409, 'ALREADY_PROCESSED');

        $this->db->execute(
            "UPDATE gift_coupons SET acceptance_status = 'accepted', accepted_at = NOW() WHERE id = ?",
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
            "SELECT id, acceptance_status FROM gift_coupons WHERE id = ? AND customer_id = ?",
            [$giftId, $customer['id']]
        );
        if (!$gift) Response::notFound('Gift coupon not found.');
        if ($gift['acceptance_status'] !== 'pending') Response::error('Gift coupon already processed.', 409, 'ALREADY_PROCESSED');

        $this->db->execute(
            "UPDATE gift_coupons SET acceptance_status = 'rejected', accepted_at = NOW() WHERE id = ?",
            [$giftId]
        );

        Response::success(null, 'Gift coupon declined');
    }

    // ── GET /api/customers/store-coupons ──────────────────────────────────────
    // Returns store_coupons gifted directly to this customer by a merchant
    public function storeCoupons(array $user): never {
        $customer = $this->db->queryOne("SELECT id FROM customers WHERE user_id = ?", [$user['id']]);
        if (!$customer) Response::notFound('Customer profile not found.');

        $cid = (int)$customer['id'];

        $coupons = $this->db->query(
            "SELECT sc.id,
                    sc.coupon_code,
                    sc.discount_type,
                    sc.discount_value,
                    sc.valid_from,
                    sc.valid_until,
                    sc.is_redeemed,
                    sc.redeemed_at,
                    sc.status,
                    sc.gifted_at,
                    m.business_name AS merchant_name,
                    m.business_logo AS merchant_logo,
                    s.name          AS store_name,
                    s.address       AS store_address
             FROM store_coupons sc
             LEFT JOIN merchants m ON m.id = sc.merchant_id
             LEFT JOIN stores    s ON s.id  = sc.store_id
             WHERE sc.gifted_to_customer_id = ?
               AND sc.is_gifted = 1
             ORDER BY sc.gifted_at DESC",
            [$cid]
        );

        Response::success($coupons);
    }
}
