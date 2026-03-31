<?php
/**
 * Merchant Booking Controller
 *
 * GET   /api/merchants/bookings              — list bookings for merchant's stores
 * PATCH /api/merchants/bookings/:id/confirm  — confirm a pending booking
 * PATCH /api/merchants/bookings/:id/reject   — reject a booking
 * PATCH /api/merchants/bookings/:id/complete — mark booking as completed
 */
class MerchantBookingController {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    private function getBookingForMerchant(int $bookingId, int $merchantId): array {
        $booking = $this->db->queryOne(
            "SELECT b.* FROM bookings b
             JOIN stores s ON s.id = b.store_id
             WHERE b.id = ? AND s.merchant_id = ?",
            [$bookingId, $merchantId]
        );
        if (!$booking) Response::notFound('Booking not found.');
        return $booking;
    }

    // ── GET /api/merchants/bookings ───────────────────────────────────────────
    public function index(array $params = []): never {
        $merchant   = AuthMiddleware::user();
        $merchantId = (int)$merchant['merchant_id'];

        // Store-scoped users only see their own store's bookings
        $storeScope = ($merchant['access_scope'] ?? 'merchant') === 'store' && !empty($merchant['store_id'])
            ? (int)$merchant['store_id'] : null;

        $statusFilter = isset($params['status']) && in_array($params['status'],
            ['pending','confirmed','rejected','cancelled','completed'])
            ? $params['status'] : null;
        $storeFilter  = !empty($params['store_id']) ? (int)$params['store_id'] : null;
        $dateFilter   = !empty($params['date']) ? $params['date'] : null;

        $where = ["s.merchant_id = ?", "s.deleted_at IS NULL"];
        $binds = [$merchantId];

        if ($storeScope)   { $where[] = "b.store_id = ?";   $binds[] = $storeScope; }
        elseif ($storeFilter) { $where[] = "b.store_id = ?"; $binds[] = $storeFilter; }
        if ($statusFilter) { $where[] = "b.status = ?";     $binds[] = $statusFilter; }
        if ($dateFilter)   { $where[] = "b.booking_date = ?"; $binds[] = $dateFilter; }

        $rows = $this->db->query(
            "SELECT b.id, b.store_id, b.customer_id, b.booking_date, b.booking_time,
                    b.num_attendees, b.customer_notes, b.merchant_notes, b.status,
                    b.confirmed_by_user_id, b.created_at, b.updated_at,
                    s.store_name,
                    CONCAT(cu.name, IFNULL(CONCAT(' ', cu.last_name), '')) AS customer_name,
                    u.phone AS customer_phone, u.email AS customer_email
             FROM bookings b
             JOIN stores s   ON s.id = b.store_id
             JOIN customers cu ON cu.id = b.customer_id
             JOIN users u    ON u.id  = cu.user_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY b.booking_date DESC, b.booking_time ASC, b.created_at DESC",
            $binds
        );

        Response::success(array_map(fn($r) => [
            'id'             => (int)$r['id'],
            'store_id'       => (int)$r['store_id'],
            'store_name'     => $r['store_name'],
            'customer_id'    => (int)$r['customer_id'],
            'customer_name'  => trim($r['customer_name']),
            'customer_phone' => $r['customer_phone'],
            'customer_email' => $r['customer_email'],
            'booking_date'   => $r['booking_date'],
            'booking_time'   => $r['booking_time'],
            'num_attendees'  => (int)$r['num_attendees'],
            'customer_notes' => $r['customer_notes'],
            'merchant_notes' => $r['merchant_notes'],
            'status'         => $r['status'],
            'created_at'     => $r['created_at'],
        ], $rows));
    }

    // ── PATCH /api/merchants/bookings/:id/confirm ─────────────────────────────
    public function confirm(int $bookingId): never {
        $merchant   = AuthMiddleware::user();
        $merchantId = (int)$merchant['merchant_id'];

        $booking = $this->getBookingForMerchant($bookingId, $merchantId);
        if ($booking['status'] !== 'pending') {
            Response::error('Only pending bookings can be confirmed.', 422);
        }

        $this->db->execute(
            "UPDATE bookings SET status = 'confirmed', confirmed_by_user_id = ?, updated_at = NOW() WHERE id = ?",
            [(int)$merchant['id'], $bookingId]
        );
        Response::success(null, 'Booking confirmed.');
    }

    // ── PATCH /api/merchants/bookings/:id/reject ──────────────────────────────
    public function reject(int $bookingId, array $body): never {
        $merchant   = AuthMiddleware::user();
        $merchantId = (int)$merchant['merchant_id'];

        $booking = $this->getBookingForMerchant($bookingId, $merchantId);
        if (!in_array($booking['status'], ['pending', 'confirmed'])) {
            Response::error('Only pending or confirmed bookings can be rejected.', 422);
        }

        $merchantNotes = trim($body['merchant_notes'] ?? '') ?: null;
        $this->db->execute(
            "UPDATE bookings SET status = 'rejected', merchant_notes = ?, confirmed_by_user_id = ?, updated_at = NOW() WHERE id = ?",
            [$merchantNotes, (int)$merchant['id'], $bookingId]
        );
        Response::success(null, 'Booking rejected.');
    }

    // ── PATCH /api/merchants/bookings/:id/complete ────────────────────────────
    public function complete(int $bookingId): never {
        $merchant   = AuthMiddleware::user();
        $merchantId = (int)$merchant['merchant_id'];

        $booking = $this->getBookingForMerchant($bookingId, $merchantId);
        if ($booking['status'] !== 'confirmed') {
            Response::error('Only confirmed bookings can be marked as completed.', 422);
        }

        $this->db->execute(
            "UPDATE bookings SET status = 'completed', updated_at = NOW() WHERE id = ?",
            [$bookingId]
        );
        Response::success(null, 'Booking marked as completed.');
    }
}
