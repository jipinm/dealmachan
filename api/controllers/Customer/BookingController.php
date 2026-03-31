<?php
/**
 * Customer Booking Controller
 *
 * POST  /api/customers/stores/:id/bookings   — create booking
 * GET   /api/customers/bookings              — list my bookings
 * PATCH /api/customers/bookings/:id/cancel   — cancel booking
 */
class CustomerBookingController {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ── POST /api/customers/stores/:id/bookings ───────────────────────────────
    public function create(array $user, int $storeId, array $body): never {
        $store = $this->db->queryOne(
            "SELECT id, store_name, booking_enabled, booking_confirmation_required
             FROM stores WHERE id = ? AND status = 'active' AND deleted_at IS NULL",
            [$storeId]
        );
        if (!$store) Response::notFound('Store not found.');
        if (!(int)$store['booking_enabled']) {
            Response::error('This store does not accept bookings.', 422);
        }

        $bookingDate   = trim($body['booking_date']  ?? '');
        $bookingTime   = trim($body['booking_time']  ?? '') ?: null;
        $numAttendees  = max(1, (int)($body['num_attendees'] ?? 1));
        $customerNotes = trim($body['customer_notes'] ?? '') ?: null;

        if (!$bookingDate || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $bookingDate)) {
            Response::error('booking_date is required (YYYY-MM-DD).', 422);
        }
        if ($bookingDate < date('Y-m-d')) {
            Response::error('Booking date cannot be in the past.', 422);
        }

        $customerId = (int)$user['customer_id'];
        $autoConfirm = !(int)$store['booking_confirmation_required'];
        $status = $autoConfirm ? 'confirmed' : 'pending';

        $this->db->execute(
            "INSERT INTO bookings (store_id, customer_id, booking_date, booking_time,
             num_attendees, customer_notes, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
            [$storeId, $customerId, $bookingDate, $bookingTime, $numAttendees, $customerNotes, $status]
        );
        $bookingId = (int)$this->db->lastInsertId();

        Response::success([
            'id'     => $bookingId,
            'status' => $status,
            'auto_confirmed' => $autoConfirm,
        ], $autoConfirm ? 'Booking confirmed!' : 'Booking requested — awaiting confirmation.', 201);
    }

    // ── GET /api/customers/bookings ───────────────────────────────────────────
    public function index(array $user): never {
        $customerId = (int)$user['customer_id'];

        $rows = $this->db->query(
            "SELECT b.id, b.store_id, b.booking_date, b.booking_time, b.num_attendees,
                    b.customer_notes, b.merchant_notes, b.status, b.created_at, b.updated_at,
                    s.store_name, s.address,
                    c.city_name, a.area_name,
                    s.store_image
             FROM bookings b
             JOIN stores s ON s.id = b.store_id
             LEFT JOIN cities c ON c.id = s.city_id
             LEFT JOIN areas  a ON a.id = s.area_id
             WHERE b.customer_id = ?
             ORDER BY b.booking_date DESC, b.created_at DESC",
            [$customerId]
        );

        Response::success(array_map(fn($r) => [
            'id'             => (int)$r['id'],
            'store_id'       => (int)$r['store_id'],
            'store_name'     => $r['store_name'],
            'store_address'  => $r['address'],
            'city_name'      => $r['city_name'],
            'area_name'      => $r['area_name'],
            'store_image'    => $r['store_image'],
            'booking_date'   => $r['booking_date'],
            'booking_time'   => $r['booking_time'],
            'num_attendees'  => (int)$r['num_attendees'],
            'customer_notes' => $r['customer_notes'],
            'merchant_notes' => $r['merchant_notes'],
            'status'         => $r['status'],
            'created_at'     => $r['created_at'],
            'updated_at'     => $r['updated_at'],
        ], $rows));
    }

    // ── PATCH /api/customers/bookings/:id/cancel ──────────────────────────────
    public function cancel(array $user, int $bookingId): never {
        $customerId = (int)$user['customer_id'];

        $booking = $this->db->queryOne(
            "SELECT id, status, customer_id FROM bookings WHERE id = ?",
            [$bookingId]
        );
        if (!$booking || (int)$booking['customer_id'] !== $customerId) {
            Response::notFound('Booking not found.');
        }
        if (!in_array($booking['status'], ['pending', 'confirmed'])) {
            Response::error('Only pending or confirmed bookings can be cancelled.', 422);
        }

        $this->db->execute(
            "UPDATE bookings SET status = 'cancelled', updated_at = NOW() WHERE id = ?",
            [$bookingId]
        );

        Response::success(null, 'Booking cancelled.');
    }
}
