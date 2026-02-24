<?php
/**
 * Customer Survey Controller
 *
 * GET  /api/customers/surveys/completed  — completed surveys
 * GET  /api/customers/surveys            — active surveys (not yet submitted)
 * GET  /api/customers/surveys/:id        — single survey with questions
 * POST /api/customers/surveys/:id/submit — submit responses
 */
class SurveyController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ── GET /api/customers/surveys ────────────────────────────────────────────
    public function index(array $user): never
    {
        $customer = $this->db->queryOne(
            "SELECT id FROM customers WHERE user_id = ?",
            [$user['id']]
        );
        if (!$customer) Response::notFound('Customer profile not found.');

        $rows = $this->db->query(
            "SELECT s.id, s.title, s.description,
                    s.active_from, s.active_until, s.status,
                    s.questions_json,
                    (SELECT COUNT(*) FROM survey_responses sr WHERE sr.survey_id = s.id) AS total_responses,
                    (SELECT COUNT(*) FROM survey_responses sr2 WHERE sr2.survey_id = s.id AND sr2.customer_id = ?) AS already_submitted
             FROM surveys s
             WHERE s.status = 'active'
               AND (s.active_from IS NULL OR s.active_from <= NOW())
               AND (s.active_until IS NULL OR s.active_until >= NOW())
             ORDER BY s.active_until ASC, s.created_at DESC",
            [$customer['id']]
        );

        foreach ($rows as &$r) {
            $r['already_submitted'] = (int)$r['already_submitted'] > 0;
            $r['question_count'] = count(json_decode($r['questions_json'] ?? '[]', true));
            unset($r['questions_json']); // Don't expose questions in listing
        }

        Response::success($rows);
    }

    // ── GET /api/customers/surveys/completed ─────────────────────────────────
    public function completed(array $user): never
    {
        $customer = $this->db->queryOne(
            "SELECT id FROM customers WHERE user_id = ?",
            [$user['id']]
        );
        if (!$customer) Response::notFound('Customer profile not found.');

        $rows = $this->db->query(
            "SELECT s.id, s.title, s.description, sr.submitted_at, sr.id AS response_id
             FROM survey_responses sr
             JOIN surveys s ON s.id = sr.survey_id
             WHERE sr.customer_id = ?
             ORDER BY sr.submitted_at DESC",
            [$customer['id']]
        );

        Response::success($rows);
    }

    // ── GET /api/customers/surveys/:id ────────────────────────────────────────
    public function show(array $user, int $id): never
    {
        $customer = $this->db->queryOne(
            "SELECT id FROM customers WHERE user_id = ?",
            [$user['id']]
        );
        if (!$customer) Response::notFound('Customer profile not found.');

        $survey = $this->db->queryOne(
            "SELECT id, title, description, questions_json, status, active_from, active_until
             FROM surveys
             WHERE id = ? AND status IN ('active','closed')",
            [$id]
        );
        if (!$survey) Response::notFound('Survey not found.');

        $survey['questions'] = json_decode($survey['questions_json'] ?? '[]', true);
        unset($survey['questions_json']);

        $existing = $this->db->queryOne(
            "SELECT id, responses_json, submitted_at FROM survey_responses WHERE survey_id = ? AND customer_id = ?",
            [$id, $customer['id']]
        );
        $survey['already_submitted'] = (bool)$existing;
        $survey['submission'] = $existing ? [
            'id'           => $existing['id'],
            'submitted_at' => $existing['submitted_at'],
        ] : null;

        Response::success($survey);
    }

    // ── POST /api/customers/surveys/:id/submit ────────────────────────────────
    public function submit(array $user, int $id, array $body): never
    {
        $customer = $this->db->queryOne(
            "SELECT id FROM customers WHERE user_id = ?",
            [$user['id']]
        );
        if (!$customer) Response::notFound('Customer profile not found.');

        $survey = $this->db->queryOne(
            "SELECT id, status, active_from, active_until, questions_json FROM surveys WHERE id = ?",
            [$id]
        );
        if (!$survey) Response::notFound('Survey not found.');

        if ($survey['status'] !== 'active') {
            Response::error('This survey is no longer accepting responses.', 422);
        }
        if ($survey['active_until'] && strtotime($survey['active_until']) < time()) {
            Response::error('This survey has expired.', 422);
        }

        // Duplicate submission check
        $exists = $this->db->queryOne(
            "SELECT id FROM survey_responses WHERE survey_id = ? AND customer_id = ?",
            [$id, $customer['id']]
        );
        if ($exists) Response::error('You have already submitted this survey.', 409);

        $responses = $body['responses'] ?? null;
        if (!$responses || !is_array($responses)) {
            Response::error('Please answer at least one question.', 422);
        }

        // Validate required questions
        $questions = json_decode($survey['questions_json'] ?? '[]', true);
        foreach ($questions as $q) {
            if (!empty($q['required']) && empty($responses[$q['id']])) {
                Response::error("Question {$q['id']} is required.", 422);
            }
        }

        $this->db->execute(
            "INSERT INTO survey_responses (survey_id, customer_id, responses_json, submitted_at)
             VALUES (?, ?, ?, NOW())",
            [$id, $customer['id'], json_encode($responses)]
        );

        Response::success([], 'Thank you for completing the survey!', 201);
    }
}
