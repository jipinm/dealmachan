<?php

class Report {

    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // ------------------------------------------------------------------
    // Summary / Dashboard
    // ------------------------------------------------------------------

    public function getSummaryStats(): array {
        return [
            'total_customers'    => (int)$this->db->query("SELECT COUNT(*) FROM customers")->fetchColumn(),
            'total_merchants'    => (int)$this->db->query("SELECT COUNT(*) FROM merchants")->fetchColumn(),
            'active_coupons'     => (int)$this->db->query("SELECT COUNT(*) FROM coupons WHERE status='active' AND approval_status='approved'")->fetchColumn(),
            'total_redemptions'  => (int)$this->db->query("SELECT COUNT(*) FROM coupon_redemptions")->fetchColumn(),
            'total_discount'     => (float)$this->db->query("SELECT COALESCE(SUM(discount_amount),0) FROM coupon_redemptions")->fetchColumn(),
            'total_transaction'  => (float)$this->db->query("SELECT COALESCE(SUM(transaction_amount),0) FROM coupon_redemptions")->fetchColumn(),
            'total_cards'        => (int)$this->db->query("SELECT COUNT(*) FROM cards")->fetchColumn(),
            'assigned_cards'     => (int)$this->db->query("SELECT COUNT(*) FROM cards WHERE assigned_to_customer_id IS NOT NULL")->fetchColumn(),
            'active_contests'    => (int)$this->db->query("SELECT COUNT(*) FROM contests WHERE status='active'")->fetchColumn(),
            'active_surveys'     => (int)$this->db->query("SELECT COUNT(*) FROM surveys WHERE status='active'")->fetchColumn(),
            'pending_merchants'  => (int)$this->db->query("SELECT COUNT(*) FROM merchants WHERE profile_status='pending'")->fetchColumn(),
            'new_customers_month'=> (int)$this->db->query("SELECT COUNT(*) FROM customers WHERE created_at >= DATE_FORMAT(NOW(),'%Y-%m-01')")->fetchColumn(),
        ];
    }

    public function getMonthlyOverview(int $months = 6): array {
        $stmt = $this->db->prepare("
            SELECT DATE_FORMAT(created_at,'%Y-%m') AS month,
                   COUNT(*) AS count
            FROM customers
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL :m MONTH)
            GROUP BY month ORDER BY month
        ");
        $stmt->execute([':m' => $months]);
        $customers = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $stmt2 = $this->db->prepare("
            SELECT DATE_FORMAT(redeemed_at,'%Y-%m') AS month,
                   COUNT(*) AS count
            FROM coupon_redemptions
            WHERE redeemed_at >= DATE_SUB(NOW(), INTERVAL :m MONTH)
            GROUP BY month ORDER BY month
        ");
        $stmt2->execute([':m' => $months]);
        $redemptions = $stmt2->fetchAll(PDO::FETCH_KEY_PAIR);

        // Build unified label set
        $allMonths = [];
        $d = new DateTime();
        for ($i = $months - 1; $i >= 0; $i--) {
            $key = (clone $d)->modify("-{$i} months")->format('Y-m');
            $allMonths[$key] = [
                'label'       => (clone $d)->modify("-{$i} months")->format('M Y'),
                'customers'   => (int)($customers[$key] ?? 0),
                'redemptions' => (int)($redemptions[$key] ?? 0),
            ];
        }
        return array_values($allMonths);
    }

    // ------------------------------------------------------------------
    // Customer Report
    // ------------------------------------------------------------------

    public function getCustomerStats(string $from, string $to): array {
        $base = "FROM customers WHERE DATE(created_at) BETWEEN :from AND :to";
        $p    = [':from' => $from, ':to' => $to];

        $total = $this->scalar("SELECT COUNT(*) $base", $p);

        $byType = $this->db->prepare("SELECT customer_type, COUNT(*) AS cnt $base GROUP BY customer_type");
        $byType->execute($p);

        $byReg = $this->db->prepare("SELECT registration_type, COUNT(*) AS cnt $base GROUP BY registration_type");
        $byReg->execute($p);

        $dealmakers = $this->scalar("SELECT COUNT(*) $base AND is_dealmaker=1", $p);
        $premium    = $this->scalar("SELECT COUNT(*) $base AND subscription_status='active'", $p);

        return [
            'total'     => $total,
            'by_type'   => $byType->fetchAll(PDO::FETCH_ASSOC),
            'by_reg'    => $byReg->fetchAll(PDO::FETCH_ASSOC),
            'dealmakers'=> $dealmakers,
            'premium'   => $premium,
        ];
    }

    public function getCustomerTrend(string $from, string $to): array {
        $stmt = $this->db->prepare("
            SELECT DATE(created_at) AS day, COUNT(*) AS cnt
            FROM customers
            WHERE DATE(created_at) BETWEEN :from AND :to
            GROUP BY day ORDER BY day
        ");
        $stmt->execute([':from' => $from, ':to' => $to]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCustomerList(string $from, string $to, int $limit = 20, int $offset = 0): array {
        $stmt = $this->db->prepare("
            SELECT c.id, c.name, c.customer_type, c.registration_type,
                   c.subscription_status, c.is_dealmaker,
                   u.email, u.phone, c.created_at
            FROM customers c
            LEFT JOIN users u ON u.id = c.user_id
            WHERE DATE(c.created_at) BETWEEN :from AND :to
            ORDER BY c.created_at DESC
            LIMIT :lim OFFSET :off
        ");
        $stmt->execute([':from' => $from, ':to' => $to, ':lim' => $limit, ':off' => $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countCustomers(string $from, string $to): int {
        return (int)$this->scalar(
            "SELECT COUNT(*) FROM customers WHERE DATE(created_at) BETWEEN :from AND :to",
            [':from' => $from, ':to' => $to]
        );
    }

    // ------------------------------------------------------------------
    // Merchant Report
    // ------------------------------------------------------------------

    public function getMerchantStats(string $from, string $to): array {
        $base = "FROM merchants WHERE DATE(created_at) BETWEEN :from AND :to";
        $p    = [':from' => $from, ':to' => $to];

        $total    = $this->scalar("SELECT COUNT(*) $base", $p);
        $approved = $this->scalar("SELECT COUNT(*) $base AND profile_status='approved'", $p);
        $pending  = $this->scalar("SELECT COUNT(*) $base AND profile_status='pending'", $p);
        $rejected = $this->scalar("SELECT COUNT(*) $base AND profile_status='rejected'", $p);
        $premium  = $this->scalar("SELECT COUNT(*) $base AND is_premium=1", $p);

        return compact('total','approved','pending','rejected','premium');
    }

    public function getMerchantTrend(string $from, string $to): array {
        $stmt = $this->db->prepare("
            SELECT DATE(created_at) AS day, COUNT(*) AS cnt
            FROM merchants
            WHERE DATE(created_at) BETWEEN :from AND :to
            GROUP BY day ORDER BY day
        ");
        $stmt->execute([':from' => $from, ':to' => $to]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTopMerchantsByRedemptions(string $from, string $to, int $limit = 10): array {
        $stmt = $this->db->prepare("
            SELECT m.id, m.business_name, m.profile_status, m.is_premium,
                   COUNT(cr.id) AS redemption_count,
                   COALESCE(SUM(cr.discount_amount),0) AS total_discount
            FROM merchants m
            LEFT JOIN coupon_redemptions cr ON cr.store_id IN (SELECT id FROM stores WHERE merchant_id = m.id)
                AND DATE(cr.redeemed_at) BETWEEN :from AND :to
            GROUP BY m.id
            ORDER BY redemption_count DESC
            LIMIT :lim
        ");
        $stmt->execute([':from' => $from, ':to' => $to, ':lim' => $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMerchantList(string $from, string $to, int $limit = 20, int $offset = 0): array {
        $stmt = $this->db->prepare("
            SELECT m.id, m.business_name, m.is_premium, m.profile_status,
                   m.subscription_status, m.created_at,
                   u.email, u.phone,
                   COUNT(cr.id) AS redemption_count
            FROM merchants m
            LEFT JOIN users u ON u.id = m.user_id
            LEFT JOIN stores s ON s.merchant_id = m.id
            LEFT JOIN coupon_redemptions cr ON cr.store_id = s.id
            WHERE DATE(m.created_at) BETWEEN :from AND :to
            GROUP BY m.id
            ORDER BY m.created_at DESC
            LIMIT :lim OFFSET :off
        ");
        $stmt->execute([':from' => $from, ':to' => $to, ':lim' => $limit, ':off' => $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countMerchants(string $from, string $to): int {
        return (int)$this->scalar(
            "SELECT COUNT(*) FROM merchants WHERE DATE(created_at) BETWEEN :from AND :to",
            [':from' => $from, ':to' => $to]
        );
    }

    // ------------------------------------------------------------------
    // Redemption Report
    // ------------------------------------------------------------------

    public function getRedemptionStats(string $from, string $to): array {
        $p = [':from' => $from, ':to' => $to];
        $base = "FROM coupon_redemptions WHERE DATE(redeemed_at) BETWEEN :from AND :to";

        $total    = $this->scalar("SELECT COUNT(*) $base", $p);
        $discount = $this->scalar("SELECT COALESCE(SUM(discount_amount),0) $base", $p);
        $txn      = $this->scalar("SELECT COALESCE(SUM(transaction_amount),0) $base", $p);
        $avgDisc  = $total > 0 ? round($discount / $total, 2) : 0;
        $avgTxn   = $total > 0 ? round($txn / $total, 2) : 0;

        // Unique coupons / customers
        $uniqCoupons   = $this->scalar("SELECT COUNT(DISTINCT coupon_id) $base", $p);
        $uniqCustomers = $this->scalar("SELECT COUNT(DISTINCT customer_id) $base", $p);

        return compact('total','discount','txn','avgDisc','avgTxn','uniqCoupons','uniqCustomers');
    }

    public function getRedemptionTrend(string $from, string $to): array {
        $stmt = $this->db->prepare("
            SELECT DATE(redeemed_at) AS day,
                   COUNT(*) AS cnt,
                   COALESCE(SUM(discount_amount),0) AS discount
            FROM coupon_redemptions
            WHERE DATE(redeemed_at) BETWEEN :from AND :to
            GROUP BY day ORDER BY day
        ");
        $stmt->execute([':from' => $from, ':to' => $to]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTopCoupons(string $from, string $to, int $limit = 10): array {
        $stmt = $this->db->prepare("
            SELECT c.id, c.title, c.coupon_code, c.discount_type, c.discount_value,
                   m.business_name,
                   COUNT(cr.id) AS redemption_count,
                   COALESCE(SUM(cr.discount_amount),0) AS total_discount
            FROM coupons c
            LEFT JOIN merchants m ON m.id = c.merchant_id
            JOIN coupon_redemptions cr ON cr.coupon_id = c.id
            WHERE DATE(cr.redeemed_at) BETWEEN :from AND :to
            GROUP BY c.id
            ORDER BY redemption_count DESC
            LIMIT :lim
        ");
        $stmt->execute([':from' => $from, ':to' => $to, ':lim' => $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRedemptionList(string $from, string $to, int $limit = 20, int $offset = 0): array {
        $stmt = $this->db->prepare("
            SELECT cr.id, cr.redeemed_at, cr.discount_amount, cr.transaction_amount,
                   c.title AS coupon_title, c.coupon_code,
                   cu.name AS customer_name,
                   m.business_name
            FROM coupon_redemptions cr
            LEFT JOIN coupons c ON c.id = cr.coupon_id
            LEFT JOIN customers cu ON cu.id = cr.customer_id
            LEFT JOIN stores s ON s.id = cr.store_id
            LEFT JOIN merchants m ON m.id = s.merchant_id
            WHERE DATE(cr.redeemed_at) BETWEEN :from AND :to
            ORDER BY cr.redeemed_at DESC
            LIMIT :lim OFFSET :off
        ");
        $stmt->execute([':from' => $from, ':to' => $to, ':lim' => $limit, ':off' => $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countRedemptions(string $from, string $to): int {
        return (int)$this->scalar(
            "SELECT COUNT(*) FROM coupon_redemptions WHERE DATE(redeemed_at) BETWEEN :from AND :to",
            [':from' => $from, ':to' => $to]
        );
    }

    // ------------------------------------------------------------------
    // CSV Export helpers
    // ------------------------------------------------------------------

    public function exportCustomersCSV(string $from, string $to): array {
        $stmt = $this->db->prepare("
            SELECT c.id, c.name, u.email, u.phone, c.customer_type,
                   c.registration_type, c.subscription_status,
                   IF(c.is_dealmaker,1,0) AS is_dealmaker, c.created_at
            FROM customers c
            LEFT JOIN users u ON u.id = c.user_id
            WHERE DATE(c.created_at) BETWEEN :from AND :to
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([':from' => $from, ':to' => $to]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function exportMerchantsCSV(string $from, string $to): array {
        $stmt = $this->db->prepare("
            SELECT m.id, m.business_name, u.email, u.phone,
                   m.profile_status, m.subscription_status,
                   IF(m.is_premium,1,0) AS is_premium, m.created_at
            FROM merchants m
            LEFT JOIN users u ON u.id = m.user_id
            WHERE DATE(m.created_at) BETWEEN :from AND :to
            ORDER BY m.created_at DESC
        ");
        $stmt->execute([':from' => $from, ':to' => $to]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function exportRedemptionsCSV(string $from, string $to): array {
        $stmt = $this->db->prepare("
            SELECT cr.id, cr.redeemed_at, c.title AS coupon_title, c.coupon_code,
                   cu.name AS customer_name, m.business_name,
                   cr.discount_amount, cr.transaction_amount
            FROM coupon_redemptions cr
            LEFT JOIN coupons c ON c.id = cr.coupon_id
            LEFT JOIN customers cu ON cu.id = cr.customer_id
            LEFT JOIN stores s ON s.id = cr.store_id
            LEFT JOIN merchants m ON m.id = s.merchant_id
            WHERE DATE(cr.redeemed_at) BETWEEN :from AND :to
            ORDER BY cr.redeemed_at DESC
        ");
        $stmt->execute([':from' => $from, ':to' => $to]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ------------------------------------------------------------------
    // Revenue / GMV Report  (sales_registry)
    // ------------------------------------------------------------------

    public function getRevenueStats(string $from, string $to): array {
        $p    = [':from' => $from, ':to' => $to];
        $base = "FROM sales_registry WHERE DATE(transaction_date) BETWEEN :from AND :to";

        $total    = $this->scalar("SELECT COUNT(*) $base", $p);
        $gmv      = (float)$this->scalar("SELECT COALESCE(SUM(transaction_amount),0) $base", $p);
        $discount = (float)$this->scalar("SELECT COALESCE(SUM(discount_amount),0) $base", $p);
        $avgTicket = $total > 0 ? round($gmv / $total, 2) : 0;
        $withCoupon = $this->scalar("SELECT COUNT(*) $base AND coupon_used IS NOT NULL", $p);

        $pmStmt = $this->db->prepare("SELECT payment_method, COUNT(*) AS cnt, COALESCE(SUM(transaction_amount),0) AS vol $base GROUP BY payment_method");
        $pmStmt->execute($p);

        return compact('total', 'gmv', 'discount', 'avgTicket', 'withCoupon') + [
            'payment_breakdown' => $pmStmt->fetchAll(PDO::FETCH_ASSOC),
        ];
    }

    public function getRevenueTrend(string $from, string $to): array {
        $stmt = $this->db->prepare("
            SELECT DATE(transaction_date) AS day,
                   COUNT(*) AS cnt,
                   COALESCE(SUM(transaction_amount),0) AS gmv,
                   COALESCE(SUM(discount_amount),0) AS discount
            FROM sales_registry
            WHERE DATE(transaction_date) BETWEEN :from AND :to
            GROUP BY day ORDER BY day
        ");
        $stmt->execute([':from' => $from, ':to' => $to]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTopMerchantsByRevenue(string $from, string $to, int $limit = 10): array {
        $stmt = $this->db->prepare("
            SELECT m.id, m.business_name,
                   COUNT(sr.id) AS transactions,
                   COALESCE(SUM(sr.transaction_amount),0) AS gmv,
                   COALESCE(SUM(sr.discount_amount),0) AS discount
            FROM sales_registry sr
            JOIN merchants m ON sr.merchant_id = m.id
            WHERE DATE(sr.transaction_date) BETWEEN :from AND :to
            GROUP BY m.id
            ORDER BY gmv DESC
            LIMIT :lim
        ");
        $stmt->execute([':from' => $from, ':to' => $to, ':lim' => $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function exportRevenueCSV(string $from, string $to): array {
        $stmt = $this->db->prepare("
            SELECT sr.id, sr.transaction_date, m.business_name, s.store_name,
                   c.name AS customer_name, u.phone AS customer_phone,
                   sr.transaction_amount, sr.discount_amount, sr.payment_method
            FROM sales_registry sr
            JOIN merchants m ON sr.merchant_id = m.id
            JOIN stores s ON sr.store_id = s.id
            LEFT JOIN customers c ON sr.customer_id = c.id
            LEFT JOIN users u ON c.user_id = u.id
            WHERE DATE(sr.transaction_date) BETWEEN :from AND :to
            ORDER BY sr.transaction_date DESC
        ");
        $stmt->execute([':from' => $from, ':to' => $to]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ------------------------------------------------------------------
    // Subscription Revenue Report
    // ------------------------------------------------------------------

    public function getSubscriptionReportStats(string $from, string $to): array {
        $p    = [':from' => $from, ':to' => $to];
        $base = "FROM subscriptions WHERE DATE(start_date) BETWEEN :from AND :to";

        $total     = $this->scalar("SELECT COUNT(*) $base", $p);
        $active    = $this->scalar("SELECT COUNT(*) $base AND status='active'", $p);
        $expired   = $this->scalar("SELECT COUNT(*) $base AND status='expired'", $p);
        $cancelled = $this->scalar("SELECT COUNT(*) $base AND status='cancelled'", $p);
        $revenue   = (float)$this->scalar("SELECT COALESCE(SUM(payment_amount),0) $base", $p);
        $merchants = $this->scalar("SELECT COUNT(*) $base AND user_type='merchant'", $p);
        $customers = $this->scalar("SELECT COUNT(*) $base AND user_type='customer'", $p);

        $planStmt = $this->db->prepare("SELECT plan_type, COUNT(*) AS cnt, COALESCE(SUM(payment_amount),0) AS revenue $base GROUP BY plan_type ORDER BY revenue DESC");
        $planStmt->execute($p);

        return compact('total','active','expired','cancelled','revenue','merchants','customers') + [
            'by_plan' => $planStmt->fetchAll(PDO::FETCH_ASSOC),
        ];
    }

    public function getSubscriptionRevenueTrend(string $from, string $to): array {
        $stmt = $this->db->prepare("
            SELECT DATE_FORMAT(start_date,'%Y-%m') AS month,
                   COUNT(*) AS cnt,
                   COALESCE(SUM(payment_amount),0) AS revenue
            FROM subscriptions
            WHERE DATE(start_date) BETWEEN :from AND :to
            GROUP BY month ORDER BY month
        ");
        $stmt->execute([':from' => $from, ':to' => $to]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ------------------------------------------------------------------
    // Coupon Analytics Report
    // ------------------------------------------------------------------

    public function getCouponAnalyticsStats(string $from, string $to): array {
        $p = [':from' => $from, ':to' => $to];

        $saves         = $this->scalar("SELECT COUNT(*) FROM coupon_subscriptions WHERE DATE(subscribed_at) BETWEEN :from AND :to", $p);
        $uniqCustomers = $this->scalar("SELECT COUNT(DISTINCT customer_id) FROM coupon_subscriptions WHERE DATE(subscribed_at) BETWEEN :from AND :to", $p);
        $uniqCoupons   = $this->scalar("SELECT COUNT(DISTINCT coupon_id) FROM coupon_subscriptions WHERE DATE(subscribed_at) BETWEEN :from AND :to", $p);
        $redemptions   = $this->scalar("SELECT COUNT(*) FROM coupon_redemptions WHERE DATE(redeemed_at) BETWEEN :from AND :to", $p);

        return compact('saves', 'uniqCustomers', 'uniqCoupons', 'redemptions');
    }

    public function getTopSavedCoupons(string $from, string $to, int $limit = 10): array {
        $stmt = $this->db->prepare("
            SELECT cp.id, cp.title, cp.coupon_code, cp.discount_type, cp.discount_value,
                   m.business_name,
                   COUNT(cs.id) AS save_count
            FROM coupon_subscriptions cs
            JOIN coupons cp ON cs.coupon_id = cp.id
            LEFT JOIN merchants m ON cp.merchant_id = m.id
            WHERE DATE(cs.subscribed_at) BETWEEN :from AND :to
            GROUP BY cp.id
            ORDER BY save_count DESC
            LIMIT :lim
        ");
        $stmt->execute([':from' => $from, ':to' => $to, ':lim' => $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCouponSaveTrend(string $from, string $to): array {
        $stmt = $this->db->prepare("
            SELECT DATE(subscribed_at) AS day, COUNT(*) AS saves
            FROM coupon_subscriptions
            WHERE DATE(subscribed_at) BETWEEN :from AND :to
            GROUP BY day ORDER BY day
        ");
        $stmt->execute([':from' => $from, ':to' => $to]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ------------------------------------------------------------------
    // Engagement Report (surveys, contests, dealmakers, referrals)
    // ------------------------------------------------------------------

    public function getEngagementStats(string $from, string $to): array {
        $p = [':from' => $from, ':to' => $to];

        $surveyResponses  = $this->scalar("SELECT COUNT(*) FROM survey_responses WHERE DATE(submitted_at) BETWEEN :from AND :to", $p);
        $contestEntries   = $this->scalar("SELECT COUNT(*) FROM contest_participants WHERE DATE(entered_at) BETWEEN :from AND :to", $p);
        $referrals        = $this->scalar("SELECT COUNT(*) FROM referrals WHERE DATE(created_at) BETWEEN :from AND :to", $p);
        $referralsCompleted= $this->scalar("SELECT COUNT(*) FROM referrals WHERE status='completed' AND DATE(created_at) BETWEEN :from AND :to", $p);
        $referralsRewarded = $this->scalar("SELECT COUNT(*) FROM referrals WHERE reward_given=1 AND DATE(created_at) BETWEEN :from AND :to", $p);
        $rewardAmount      = (float)$this->scalar("SELECT COALESCE(SUM(reward_amount),0) FROM referrals WHERE reward_given=1 AND DATE(created_at) BETWEEN :from AND :to", $p);
        $dealmakerTasks    = $this->scalar("SELECT COUNT(*) FROM dealmaker_tasks WHERE DATE(created_at) BETWEEN :from AND :to", $p);
        $dealmakerCompleted= $this->scalar("SELECT COUNT(*) FROM dealmaker_tasks WHERE status='completed' AND DATE(created_at) BETWEEN :from AND :to", $p);

        return compact('surveyResponses','contestEntries','referrals','referralsCompleted',
                       'referralsRewarded','rewardAmount','dealmakerTasks','dealmakerCompleted');
    }

    public function getTopContestsByParticipation(string $from, string $to, int $limit = 10): array {
        $stmt = $this->db->prepare("
            SELECT co.id, co.title, co.status,
                   COUNT(cp.id) AS participant_count
            FROM contests co
            LEFT JOIN contest_participants cp ON cp.contest_id = co.id
                AND DATE(cp.entered_at) BETWEEN :from AND :to
            GROUP BY co.id
            ORDER BY participant_count DESC
            LIMIT :lim
        ");
        $stmt->execute([':from' => $from, ':to' => $to, ':lim' => $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTopSurveysByResponse(string $from, string $to, int $limit = 10): array {
        $stmt = $this->db->prepare("
            SELECT sv.id, sv.title, sv.status,
                   COUNT(sr.id) AS response_count
            FROM surveys sv
            LEFT JOIN survey_responses sr ON sr.survey_id = sv.id
                AND DATE(sr.submitted_at) BETWEEN :from AND :to
            GROUP BY sv.id
            ORDER BY response_count DESC
            LIMIT :lim
        ");
        $stmt->execute([':from' => $from, ':to' => $to, ':lim' => $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ------------------------------------------------------------------
    // Utility
    // ------------------------------------------------------------------

    private function scalar(string $sql, array $params = []) {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }
}
