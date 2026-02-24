<?php
/**
 * Customer Referral Controller
 *
 * GET /api/customers/referral — returns referral code + stats + history
 */
class ReferralController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ── GET /api/customers/referral ───────────────────────────────────────────
    public function index(array $user): never
    {
        $customer = $this->db->queryOne(
            "SELECT id, referral_code FROM customers WHERE user_id = ?",
            [$user['id']]
        );
        if (!$customer) Response::notFound('Customer profile not found.');

        $cid  = (int)$customer['id'];
        $code = $customer['referral_code'];

        // Stats
        $total    = (int)($this->db->queryOne(
            "SELECT COUNT(*) AS c FROM referrals WHERE referrer_customer_id = ?", [$cid]
        )['c'] ?? 0);

        $completed = (int)($this->db->queryOne(
            "SELECT COUNT(*) AS c FROM referrals WHERE referrer_customer_id = ? AND status IN ('completed','rewarded')", [$cid]
        )['c'] ?? 0);

        $pending   = (int)($this->db->queryOne(
            "SELECT COUNT(*) AS c FROM referrals WHERE referrer_customer_id = ? AND status = 'pending'", [$cid]
        )['c'] ?? 0);

        $totalRewards = (float)($this->db->queryOne(
            "SELECT COALESCE(SUM(reward_amount), 0) AS total FROM referrals
             WHERE referrer_customer_id = ? AND reward_given = 1", [$cid]
        )['total'] ?? 0);

        // History (latest 20)
        $history = $this->db->query(
            "SELECT r.id, r.status, r.reward_amount, r.reward_given, r.created_at, r.completed_at,
                    c.name AS referee_name
             FROM referrals r
             LEFT JOIN customers c ON c.id = r.referee_customer_id
             WHERE r.referrer_customer_id = ?
             ORDER BY r.created_at DESC
             LIMIT 20",
            [$cid]
        );

        Response::success([
            'referral_code' => $code,
            'stats' => [
                'total'         => $total,
                'completed'     => $completed,
                'pending'       => $pending,
                'total_rewards' => round($totalRewards, 2),
            ],
            'history' => $history,
        ]);
    }
}
