<?php
/**
 * Merchant Analytics Controller
 *
 * GET  /merchants/analytics/dashboard    → dashboard()
 * GET  /merchants/analytics/redemptions  → redemptions()
 * GET  /merchants/analytics/customers    → customers()
 * GET  /merchants/analytics/top-coupons  → topCoupons()
 * GET  /merchants/analytics/revenue      → revenue()
 *
 * All routes are protected by AuthMiddleware::require() in index.php.
 */
class AnalyticsController {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ── GET /merchants/analytics/dashboard ───────────────────────────────────
    // Response shape: DashboardAnalytics (analytics.ts) + DashboardData (HomePage.tsx)
    public function dashboard(): never {
        $user       = AuthMiddleware::user();
        $merchantId = (int)$user['merchant_id'];

        // ── KPI counters ─────────────────────────────────────────────────────

        $totalCoupons = (int)($this->db->queryOne(
            'SELECT COUNT(*) AS cnt FROM coupons WHERE merchant_id = ?',
            [$merchantId]
        )['cnt'] ?? 0);

        $activeCoupons = (int)($this->db->queryOne(
            "SELECT COUNT(*) AS cnt FROM coupons WHERE merchant_id = ? AND status = 'active'",
            [$merchantId]
        )['cnt'] ?? 0);

        $totalRedemptions = (int)($this->db->queryOne(
            'SELECT COUNT(*) AS cnt
             FROM coupon_redemptions cr
             JOIN coupons c ON c.id = cr.coupon_id
             WHERE c.merchant_id = ?',
            [$merchantId]
        )['cnt'] ?? 0);

        $thisMonthRedemptions = (int)($this->db->queryOne(
            'SELECT COUNT(*) AS cnt
             FROM coupon_redemptions cr
             JOIN coupons c ON c.id = cr.coupon_id
             WHERE c.merchant_id = ?
               AND MONTH(cr.redeemed_at) = MONTH(NOW())
               AND YEAR(cr.redeemed_at)  = YEAR(NOW())',
            [$merchantId]
        )['cnt'] ?? 0);

        $todayRedemptions = (int)($this->db->queryOne(
            'SELECT COUNT(*) AS cnt
             FROM coupon_redemptions cr
             JOIN coupons c ON c.id = cr.coupon_id
             WHERE c.merchant_id = ?
               AND DATE(cr.redeemed_at) = CURDATE()',
            [$merchantId]
        )['cnt'] ?? 0);

        $totalCustomers = (int)($this->db->queryOne(
            'SELECT COUNT(DISTINCT cr.customer_id) AS cnt
             FROM coupon_redemptions cr
             JOIN coupons c ON c.id = cr.coupon_id
             WHERE c.merchant_id = ?',
            [$merchantId]
        )['cnt'] ?? 0);

        $avgRatingRow = $this->db->queryOne(
            "SELECT ROUND(AVG(rating), 1) AS avg_rating
             FROM reviews
             WHERE merchant_id = ? AND status = 'published'",
            [$merchantId]
        );
        $avgRating = $avgRatingRow['avg_rating'] !== null
            ? (float)$avgRatingRow['avg_rating']
            : null;

        $pendingGrievances = (int)($this->db->queryOne(
            "SELECT COUNT(*) AS cnt FROM grievances WHERE merchant_id = ? AND status = 'open'",
            [$merchantId]
        )['cnt'] ?? 0);

        // ── Charts ───────────────────────────────────────────────────────────

        // 7-day chart for HomePage
        $weeklyRaw = $this->db->query(
            'SELECT DATE(cr.redeemed_at) AS `date`, COUNT(*) AS `count`
             FROM coupon_redemptions cr
             JOIN coupons c ON c.id = cr.coupon_id
             WHERE c.merchant_id = ?
               AND cr.redeemed_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
             GROUP BY DATE(cr.redeemed_at)
             ORDER BY `date` ASC',
            [$merchantId]
        );

        // Fill missing days with zero
        $days = [];
        for ($i = 6; $i >= 0; $i--) {
            $days[date('Y-m-d', strtotime("-{$i} days"))] = 0;
        }
        foreach ($weeklyRaw as $row) {
            $days[$row['date']] = (int)$row['count'];
        }
        $weeklyRedemptions = array_map(
            static fn($d, $c) => ['date' => $d, 'count' => $c],
            array_keys($days),
            array_values($days)
        );

        // 30-day chart for AnalyticsPage / DashboardAnalytics
        $chartRaw = $this->db->query(
            'SELECT DATE(cr.redeemed_at) AS `date`, COUNT(*) AS `count`
             FROM coupon_redemptions cr
             JOIN coupons c ON c.id = cr.coupon_id
             WHERE c.merchant_id = ?
               AND cr.redeemed_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
             GROUP BY DATE(cr.redeemed_at)
             ORDER BY `date` ASC',
            [$merchantId]
        );

        $chart = array_map(static function (array $row): array {
            return ['date' => $row['date'], 'count' => (int)$row['count']];
        }, $chartRaw);

        Response::success([
            // DashboardAnalytics (analytics.ts)
            'total_coupons'          => $totalCoupons,
            'active_coupons'         => $activeCoupons,
            'total_redemptions'      => $totalRedemptions,
            'this_month_redemptions' => $thisMonthRedemptions,
            'chart'                  => $chart,
            // DashboardData (HomePage.tsx)
            'today_redemptions'      => $todayRedemptions,
            'total_customers'        => $totalCustomers,
            'avg_rating'             => $avgRating,
            'pending_grievances'     => $pendingGrievances,
            'weekly_redemptions'     => $weeklyRedemptions,
        ]);
    }

    // ── GET /merchants/analytics/redemptions ─────────────────────────────────
    // Query params: period (days, default 30) | from (Y-m-d) | to (Y-m-d)
    // Response shape: RedemptionAnalytics
    public function redemptions(): never {
        $user       = AuthMiddleware::user();
        $merchantId = (int)$user['merchant_id'];

        $period = max(1, min(365, (int)($_GET['period'] ?? 30)));
        $from   = !empty($_GET['from']) ? $_GET['from'] : date('Y-m-d', strtotime("-{$period} days"));
        $to     = !empty($_GET['to'])   ? $_GET['to']   : date('Y-m-d');

        $dailyRaw = $this->db->query(
            'SELECT DATE(cr.redeemed_at) AS `date`,
                    COUNT(*) AS `count`,
                    COALESCE(SUM(cr.discount_amount), 0) AS discount
             FROM coupon_redemptions cr
             JOIN coupons c ON c.id = cr.coupon_id
             WHERE c.merchant_id = ?
               AND DATE(cr.redeemed_at) BETWEEN ? AND ?
             GROUP BY DATE(cr.redeemed_at)
             ORDER BY `date` ASC',
            [$merchantId, $from, $to]
        );

        $daily = array_map(static function (array $row): array {
            return [
                'date'     => $row['date'],
                'count'    => (int)$row['count'],
                'discount' => number_format((float)$row['discount'], 2, '.', ''),
            ];
        }, $dailyRaw);

        $totals = $this->db->queryOne(
            'SELECT COUNT(*) AS total_redemptions,
                    COALESCE(SUM(cr.discount_amount), 0) AS total_discount
             FROM coupon_redemptions cr
             JOIN coupons c ON c.id = cr.coupon_id
             WHERE c.merchant_id = ?
               AND DATE(cr.redeemed_at) BETWEEN ? AND ?',
            [$merchantId, $from, $to]
        );

        Response::success([
            'daily'             => $daily,
            'total_redemptions' => (int)($totals['total_redemptions'] ?? 0),
            'total_discount'    => number_format((float)($totals['total_discount'] ?? 0), 2, '.', ''),
        ]);
    }

    // ── GET /merchants/analytics/customers ───────────────────────────────────
    // Response shape: CustomerAnalytics
    public function customers(): never {
        $user       = AuthMiddleware::user();
        $merchantId = (int)$user['merchant_id'];

        // Group unique customers by the store city where they redeemed
        // (customers table has no city column — derive from store)
        $byCityRaw = $this->db->query(
            "SELECT COALESCE(ci.city_name, 'Unknown') AS city,
                    COUNT(DISTINCT cr.customer_id)     AS `count`
             FROM coupon_redemptions cr
             JOIN coupons c  ON  c.id = cr.coupon_id
             LEFT JOIN stores s  ON  s.id = cr.store_id
             LEFT JOIN cities ci ON ci.id = s.city_id
             WHERE c.merchant_id = ?
             GROUP BY ci.id, ci.city_name
             ORDER BY `count` DESC
             LIMIT 10",
            [$merchantId]
        );

        $byCity = array_map(static function (array $row): array {
            return ['city' => $row['city'], 'count' => (int)$row['count']];
        }, $byCityRaw);

        // New: first-ever redemption for this merchant happened in last 30 days
        // Returning: had redemptions before and also redeemed in last 30 days
        $repeatRow = $this->db->queryOne(
            "SELECT
                SUM(CASE WHEN cnt = 1 THEN 1 ELSE 0 END) AS new_customers,
                SUM(CASE WHEN cnt > 1 THEN 1 ELSE 0 END) AS returning_customers
             FROM (
                 SELECT cr.customer_id, COUNT(*) AS cnt
                 FROM coupon_redemptions cr
                 JOIN coupons c ON c.id = cr.coupon_id
                 WHERE c.merchant_id = ?
                 GROUP BY cr.customer_id
             ) sub",
            [$merchantId]
        );

        Response::success([
            'by_city'             => $byCity,
            'new_customers'       => (int)($repeatRow['new_customers']       ?? 0),
            'returning_customers' => (int)($repeatRow['returning_customers'] ?? 0),
        ]);
    }

    // ── GET /merchants/analytics/top-coupons ─────────────────────────────────
    // Response shape: TopCoupon[]
    public function topCoupons(): never {
        $user       = AuthMiddleware::user();
        $merchantId = (int)$user['merchant_id'];

        $rows = $this->db->query(
            'SELECT c.id, c.title, c.coupon_code,
                    COUNT(cr.id) AS redemption_count
             FROM coupons c
             LEFT JOIN coupon_redemptions cr ON cr.coupon_id = c.id
             WHERE c.merchant_id = ?
             GROUP BY c.id, c.title, c.coupon_code
             ORDER BY redemption_count DESC
             LIMIT 10',
            [$merchantId]
        );

        $rows = array_map(static function (array $row): array {
            $row['id']               = (int)$row['id'];
            $row['redemption_count'] = (int)$row['redemption_count'];
            return $row;
        }, $rows);

        Response::success($rows);
    }

    // ── GET /merchants/analytics/revenue ─────────────────────────────────────
    // Query params: from (Y-m-d) | to (Y-m-d)
    // Response shape: RevenueAnalytics
    public function revenue(): never {
        $user       = AuthMiddleware::user();
        $merchantId = (int)$user['merchant_id'];

        $from = !empty($_GET['from']) ? $_GET['from'] : date('Y-m-01');   // first of current month
        $to   = !empty($_GET['to'])   ? $_GET['to']   : date('Y-m-d');

        $summary = $this->db->queryOne(
            'SELECT COUNT(*) AS total_sales,
                    COALESCE(SUM(transaction_amount), 0) AS total_revenue
             FROM sales_registry
             WHERE merchant_id = ?
               AND DATE(transaction_date) BETWEEN ? AND ?',
            [$merchantId, $from, $to]
        );

        $byStoreRaw = $this->db->query(
            'SELECT sr.store_id,
                    s.store_name,
                    COUNT(*)                             AS `count`,
                    COALESCE(SUM(sr.transaction_amount), 0) AS revenue
             FROM sales_registry sr
             JOIN stores s ON s.id = sr.store_id
             WHERE sr.merchant_id = ?
               AND DATE(sr.transaction_date) BETWEEN ? AND ?
             GROUP BY sr.store_id, s.store_name
             ORDER BY revenue DESC',
            [$merchantId, $from, $to]
        );

        $byStore = array_map(static function (array $row): array {
            return [
                'store_id'   => (int)$row['store_id'],
                'store_name' => $row['store_name'],
                'count'      => (int)$row['count'],
                'revenue'    => number_format((float)$row['revenue'], 2, '.', ''),
            ];
        }, $byStoreRaw);

        Response::success([
            'total_sales'   => (int)($summary['total_sales']   ?? 0),
            'total_revenue' => number_format((float)($summary['total_revenue'] ?? 0), 2, '.', ''),
            'by_store'      => $byStore,
        ]);
    }
}
