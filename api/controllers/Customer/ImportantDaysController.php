<?php
/**
 * Customer Important Days Controller
 *
 * GET    /api/customers/important-days        — list
 * POST   /api/customers/important-days        — add
 * DELETE /api/customers/important-days/:id    — remove
 */
class ImportantDaysController
{
    private Database $db;
    public function __construct() { $this->db = Database::getInstance(); }

    private const ALLOWED_TYPES = ['Birthday', 'Anniversary', 'Others'];

    // ── GET /api/customers/important-days ─────────────────────────────────────
    public function index(array $user): never
    {
        $customer = $this->db->queryOne(
            "SELECT id FROM customers WHERE user_id = ?",
            [$user['id']]
        );
        if (!$customer) Response::notFound('Customer profile not found.');

        $rows = $this->db->query(
            "SELECT id, event_type, event_specify, event_day, event_month, person_name, created_at
             FROM customer_important_days
             WHERE customer_id = ?
             ORDER BY event_month ASC, event_day ASC",
            [(int)$customer['id']]
        );

        Response::success($rows);
    }

    // ── POST /api/customers/important-days ────────────────────────────────────
    public function store(array $user, array $body): never
    {
        $customer = $this->db->queryOne(
            "SELECT id FROM customers WHERE user_id = ?",
            [$user['id']]
        );
        if (!$customer) Response::notFound('Customer profile not found.');

        $type   = trim($body['event_type']    ?? '');
        $day    = (int)($body['event_day']    ?? 0);
        $month  = (int)($body['event_month']  ?? 0);
        $specify = isset($body['event_specify']) ? trim($body['event_specify']) : null;
        $name   = isset($body['person_name'])  ? trim($body['person_name'])  : null;

        if (!$type)               Response::error('event_type is required.', 400, 'MISSING_FIELDS');
        if (!in_array($type, self::ALLOWED_TYPES, true))
            Response::error('Invalid event_type. Allowed: Birthday, Anniversary, Others.', 400, 'INVALID_TYPE');
        if ($day  < 1 || $day  > 31) Response::error('event_day must be 1–31.',   400, 'INVALID_DAY');
        if ($month < 1 || $month > 12) Response::error('event_month must be 1–12.', 400, 'INVALID_MONTH');
        if ($type === 'Others' && !$specify)
            Response::error('event_specify is required when event_type is Others.', 400, 'MISSING_SPECIFY');

        $cid = (int)$customer['id'];

        // Prevent duplicates (same type + day + month)
        $dup = $this->db->queryOne(
            "SELECT id FROM customer_important_days WHERE customer_id = ? AND event_type = ? AND event_day = ? AND event_month = ?",
            [$cid, $type, $day, $month]
        );
        if ($dup) Response::error('This event is already saved.', 409, 'DUPLICATE_EVENT');

        $this->db->execute(
            "INSERT INTO customer_important_days
               (customer_id, event_type, event_specify, event_day, event_month, person_name, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())",
            [$cid, $type, $specify, $day, $month, $name]
        );

        $inserted = $this->db->queryOne(
            "SELECT id, event_type, event_specify, event_day, event_month, person_name, created_at
             FROM customer_important_days WHERE id = LAST_INSERT_ID()"
        );

        Response::success($inserted, 'Important day added.', 201);
    }

    // ── DELETE /api/customers/important-days/:id ──────────────────────────────
    public function destroy(array $user, int $dayId): never
    {
        $customer = $this->db->queryOne(
            "SELECT id FROM customers WHERE user_id = ?",
            [$user['id']]
        );
        if (!$customer) Response::notFound('Customer profile not found.');

        $row = $this->db->queryOne(
            "SELECT id FROM customer_important_days WHERE id = ? AND customer_id = ?",
            [$dayId, (int)$customer['id']]
        );
        if (!$row) Response::notFound('Event not found.');

        $this->db->execute("DELETE FROM customer_important_days WHERE id = ?", [$dayId]);
        Response::success(null, 'Event removed.');
    }
}
