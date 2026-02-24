<?php
/**
 * Customer DealMaker Controller
 *
 * POST /api/customers/dealmaker/apply     — submit application
 * GET  /api/customers/dealmaker/status    — application + approval status
 * GET  /api/customers/dealmaker/tasks     — assigned tasks (approved only)
 * POST /api/customers/dealmaker/tasks/:id/complete — mark task in_progress / complete
 * GET  /api/customers/dealmaker/earnings  — paid + pending rewards
 */
class DealmakerController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ── POST /api/customers/dealmaker/apply ───────────────────────────────────
    public function apply(array $user, array $body): never
    {
        $customer = $this->db->queryOne(
            "SELECT id, is_dealmaker FROM customers WHERE user_id = ?",
            [$user['id']]
        );
        if (!$customer) Response::notFound('Customer profile not found.');

        if ((int)$customer['is_dealmaker'] === 1) {
            Response::error('You are already an approved Deal Maker.', 409);
        }

        // Check for existing pending application
        $existing = $this->db->queryOne(
            "SELECT id, status FROM dealmaker_applications WHERE customer_id = ? ORDER BY applied_at DESC LIMIT 1",
            [$customer['id']]
        );
        if ($existing && in_array($existing['status'], ['pending', 'approved'])) {
            Response::error(
                $existing['status'] === 'pending'
                    ? 'You already have a pending application. We will review it shortly.'
                    : 'Your application has been approved.',
                409
            );
        }

        $motivation = trim($body['motivation'] ?? '');
        $city       = trim($body['city']       ?? '');
        $experience = trim($body['experience'] ?? '');

        if (!$motivation) Response::error('Please tell us why you want to become a Deal Maker.', 422);

        $this->db->execute(
            "INSERT INTO dealmaker_applications (customer_id, motivation, city, experience, status, applied_at)
             VALUES (?, ?, ?, ?, 'pending', NOW())",
            [$customer['id'], $motivation, $city ?: null, $experience ?: null]
        );

        Response::success([], 'Application submitted! Our team will review it within 2 business days.', 201);
    }

    // ── GET /api/customers/dealmaker/status ───────────────────────────────────
    public function status(array $user): never
    {
        $customer = $this->db->queryOne(
            "SELECT id, is_dealmaker, dealmaker_approved_at, customer_type FROM customers WHERE user_id = ?",
            [$user['id']]
        );
        if (!$customer) Response::notFound('Customer profile not found.');

        $application = $this->db->queryOne(
            "SELECT id, status, applied_at, reviewed_at, rejection_reason
             FROM dealmaker_applications
             WHERE customer_id = ?
             ORDER BY applied_at DESC
             LIMIT 1",
            [$customer['id']]
        );

        $taskSummary = null;
        if ((int)$customer['is_dealmaker'] === 1) {
            $taskSummary = $this->db->queryOne(
                "SELECT
                   COUNT(*) AS total,
                   SUM(CASE WHEN status = 'assigned' THEN 1 ELSE 0 END) AS assigned,
                   SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress,
                   SUM(CASE WHEN status IN ('completed','verified') THEN 1 ELSE 0 END) AS completed,
                   COALESCE(SUM(CASE WHEN reward_status = 'paid' THEN reward_amount ELSE 0 END), 0) AS total_earned,
                   COALESCE(SUM(CASE WHEN reward_status = 'pending' AND status IN ('completed','verified') THEN reward_amount ELSE 0 END), 0) AS pending_earnings
                 FROM dealmaker_tasks
                 WHERE dealmaker_customer_id = ?",
                [$customer['id']]
            );
        }

        Response::success([
            'is_dealmaker'         => (bool)$customer['is_dealmaker'],
            'approved_at'         => $customer['dealmaker_approved_at'],
            'customer_type'       => $customer['customer_type'],
            'application'         => $application,
            'task_summary'        => $taskSummary,
        ]);
    }

    // ── GET /api/customers/dealmaker/tasks ────────────────────────────────────
    public function tasks(array $user): never
    {
        $customer = $this->db->queryOne(
            "SELECT id, is_dealmaker FROM customers WHERE user_id = ?",
            [$user['id']]
        );
        if (!$customer) Response::notFound('Customer profile not found.');
        if (!(int)$customer['is_dealmaker']) Response::forbidden('Deal Maker access required.');

        $tasks = $this->db->query(
            "SELECT id, task_type, task_description, status, assigned_at,
                    completed_at, completion_notes, reward_amount, reward_status
             FROM dealmaker_tasks
             WHERE dealmaker_customer_id = ?
             ORDER BY
               FIELD(status, 'assigned', 'in_progress', 'verified', 'completed') ASC,
               assigned_at DESC",
            [$customer['id']]
        );

        Response::success($tasks);
    }

    // ── POST /api/customers/dealmaker/tasks/:id/complete ─────────────────────
    public function completeTask(array $user, int $taskId, array $body): never
    {
        $customer = $this->db->queryOne(
            "SELECT id, is_dealmaker FROM customers WHERE user_id = ?",
            [$user['id']]
        );
        if (!$customer) Response::notFound('Customer profile not found.');
        if (!(int)$customer['is_dealmaker']) Response::forbidden('Deal Maker access required.');

        $task = $this->db->queryOne(
            "SELECT id, status FROM dealmaker_tasks WHERE id = ? AND dealmaker_customer_id = ?",
            [$taskId, $customer['id']]
        );
        if (!$task) Response::notFound('Task not found.');

        if (in_array($task['status'], ['completed', 'verified'])) {
            Response::error('Task is already completed.', 409);
        }

        $notes = trim($body['completion_notes'] ?? '');
        $newStatus = $task['status'] === 'assigned' ? 'in_progress' : 'completed';

        $this->db->execute(
            "UPDATE dealmaker_tasks
             SET status = ?, completion_notes = ?, completed_at = IF(? = 'completed', NOW(), NULL)
             WHERE id = ?",
            [$newStatus, $notes ?: null, $newStatus, $taskId]
        );

        Response::success(
            ['task_id' => $taskId, 'new_status' => $newStatus],
            $newStatus === 'completed' ? 'Task marked as completed!' : 'Task started!'
        );
    }

    // ── GET /api/customers/dealmaker/earnings ─────────────────────────────────
    public function earnings(array $user): never
    {
        $customer = $this->db->queryOne(
            "SELECT id, is_dealmaker FROM customers WHERE user_id = ?",
            [$user['id']]
        );
        if (!$customer) Response::notFound('Customer profile not found.');
        if (!(int)$customer['is_dealmaker']) Response::forbidden('Deal Maker access required.');

        $records = $this->db->query(
            "SELECT id, task_type, task_description, reward_amount, reward_status,
                    completed_at, status
             FROM dealmaker_tasks
             WHERE dealmaker_customer_id = ?
               AND status IN ('completed','verified')
             ORDER BY completed_at DESC",
            [$customer['id']]
        );

        $totalEarned  = 0.0;
        $totalPending = 0.0;
        foreach ($records as $r) {
            if ($r['reward_status'] === 'paid')    $totalEarned  += (float)$r['reward_amount'];
            if ($r['reward_status'] === 'pending') $totalPending += (float)$r['reward_amount'];
        }

        Response::success([
            'total_earned'   => round($totalEarned, 2),
            'total_pending'  => round($totalPending, 2),
            'records'        => $records,
        ]);
    }
}
