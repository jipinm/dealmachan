<?php
/**
 * Customer Contest Controller
 *
 * GET  /api/public/contests                — active contests (public, no auth)
 * GET  /api/customers/contests/my-entries  — customer's participated contests
 * GET  /api/customers/contests             — active + upcoming contests
 * GET  /api/customers/contests/:id         — detail + rules + winners
 * POST /api/customers/contests/:id/participate — enter contest
 */
class ContestController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ── GET /api/public/contests ──────────────────────────────────────────────
    public function publicIndex(): never
    {
        $rows = $this->db->query(
            "SELECT id, title, description, start_date, end_date, status,
                    (SELECT COUNT(*) FROM contest_participants cp WHERE cp.contest_id = c.id) AS participant_count
             FROM contests c
             WHERE c.status IN ('active', 'completed')
             ORDER BY
               CASE c.status WHEN 'active' THEN 0 ELSE 1 END,
               c.end_date ASC"
        );

        foreach ($rows as &$r) {
            $r['participant_count'] = (int)$r['participant_count'];
        }

        Response::success($rows);
    }

    // ── GET /api/customers/contests ───────────────────────────────────────────
    public function index(array $user): never
    {
        $customer = $this->db->queryOne(
            "SELECT id FROM customers WHERE user_id = ?",
            [$user['id']]
        );
        if (!$customer) Response::notFound('Customer profile not found.');

        $rows = $this->db->query(
            "SELECT c.id, c.title, c.description, c.start_date, c.end_date, c.status,
                    (SELECT COUNT(*) FROM contest_participants cp WHERE cp.contest_id = c.id) AS participant_count,
                    (SELECT COUNT(*) FROM contest_participants cp2 WHERE cp2.contest_id = c.id AND cp2.customer_id = ?) AS has_entered
             FROM contests c
             WHERE c.status IN ('active','completed')
             ORDER BY
               CASE c.status WHEN 'active' THEN 0 ELSE 1 END,
               c.end_date ASC",
            [$customer['id']]
        );

        foreach ($rows as &$r) {
            $r['has_entered'] = (int)$r['has_entered'] > 0;
            $r['participant_count'] = (int)$r['participant_count'];
        }

        Response::success($rows);
    }

    // ── GET /api/customers/contests/my-entries ────────────────────────────────
    public function myEntries(array $user): never
    {
        $customer = $this->db->queryOne(
            "SELECT id FROM customers WHERE user_id = ?",
            [$user['id']]
        );
        if (!$customer) Response::notFound('Customer profile not found.');

        $rows = $this->db->query(
            "SELECT c.id, c.title, c.description, c.start_date, c.end_date, c.status,
                    cp.participated_at, cp.entry_data_json,
                    (SELECT GROUP_CONCAT(CONCAT(cw.position, '|', cw.prize_details) ORDER BY cw.position SEPARATOR ';;')
                     FROM contest_winners cw WHERE cw.contest_id = c.id AND cw.customer_id = ?) AS won_positions
             FROM contest_participants cp
             JOIN contests c ON c.id = cp.contest_id
             WHERE cp.customer_id = ?
             ORDER BY cp.participated_at DESC",
            [$customer['id'], $customer['id']]
        );

        foreach ($rows as &$r) {
            $r['is_winner'] = !empty($r['won_positions']);
        }

        Response::success($rows);
    }

    // ── GET /api/customers/contests/:id ──────────────────────────────────────
    public function show(array $user, int $id): never
    {
        $customer = $this->db->queryOne(
            "SELECT id FROM customers WHERE user_id = ?",
            [$user['id']]
        );
        if (!$customer) Response::notFound('Customer profile not found.');

        $contest = $this->db->queryOne(
            "SELECT id, title, description, rules_json, start_date, end_date, status, created_at
             FROM contests
             WHERE id = ?",
            [$id]
        );
        if (!$contest) Response::notFound('Contest not found.');

        $contest['rules'] = json_decode($contest['rules_json'] ?? '[]', true);
        unset($contest['rules_json']);

        // Participant count
        $contest['participant_count'] = (int)$this->db->queryOne(
            "SELECT COUNT(*) AS cnt FROM contest_participants WHERE contest_id = ?",
            [$id]
        )['cnt'];

        // Has this customer entered?
        $entry = $this->db->queryOne(
            "SELECT id, participated_at, entry_data_json FROM contest_participants WHERE contest_id = ? AND customer_id = ?",
            [$id, $customer['id']]
        );
        $contest['has_entered']   = (bool)$entry;
        $contest['my_entry']      = $entry ?: null;

        // Winners (if announced)
        $contest['winners'] = $this->db->query(
            "SELECT cw.position, cw.prize_details, cw.announced_at,
                    c.name AS customer_name
             FROM contest_winners cw
             JOIN customers c ON c.id = cw.customer_id
             WHERE cw.contest_id = ?
             ORDER BY cw.position ASC",
            [$id]
        );

        Response::success($contest);
    }

    // ── POST /api/customers/contests/:id/participate ──────────────────────────
    public function participate(array $user, int $id, array $body): never
    {
        $customer = $this->db->queryOne(
            "SELECT id FROM customers WHERE user_id = ?",
            [$user['id']]
        );
        if (!$customer) Response::notFound('Customer profile not found.');

        $contest = $this->db->queryOne(
            "SELECT id, status, start_date, end_date FROM contests WHERE id = ?",
            [$id]
        );
        if (!$contest) Response::notFound('Contest not found.');

        if ($contest['status'] !== 'active') {
            Response::error('This contest is not currently active.', 422);
        }
        if ($contest['end_date'] && strtotime($contest['end_date']) < time()) {
            Response::error('This contest has ended.', 422);
        }

        // Duplicate entry check
        $exists = $this->db->queryOne(
            "SELECT id FROM contest_participants WHERE contest_id = ? AND customer_id = ?",
            [$id, $customer['id']]
        );
        if ($exists) Response::error('You have already entered this contest.', 409);

        $entryData = !empty($body) ? json_encode($body) : null;

        $this->db->execute(
            "INSERT INTO contest_participants (contest_id, customer_id, entry_data_json, participated_at)
             VALUES (?, ?, ?, NOW())",
            [$id, $customer['id'], $entryData]
        );

        Response::success([], 'You have successfully entered the contest! Good luck!', 201);
    }
}
