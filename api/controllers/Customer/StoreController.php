<?php
/**
 * Customer Store Controller
 *
 * POST /api/customers/stores/:id/call-log
 */
class CustomerStoreController {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ── POST /api/customers/stores/:id/call-log ───────────────────────────────
    public function callLog(array $user, int $storeId): never {
        // Verify store exists and get its phone number
        $store = $this->db->queryOne(
            "SELECT id, phone FROM stores WHERE id = ? AND status = 'active'",
            [$storeId]
        );

        if (!$store) Response::notFound('Store not found.');
        if (empty($store['phone'])) Response::error('This store has no phone number.', 422);

        $customerId = (int)$user['customer_id'];

        $this->db->execute(
            "INSERT INTO call_logs (store_id, customer_id, phone_number, initiated_at)
             VALUES (?, ?, ?, NOW())",
            [$storeId, $customerId, $store['phone']]
        );

        Response::success(null, 'Call logged.', 200);
    }
}
