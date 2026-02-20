<?php
/**
 * Merchant Sales Registry Controller
 *
 * GET  /merchants/sales-registry          → index   (paginated list)
 * POST /merchants/sales-registry          → store   (record a sale)
 * GET  /merchants/sales-registry/summary  → summary (totals for date range)
 * GET  /merchants/sales-registry/export   → export  (CSV download)
 */
class SalesController {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ── GET /merchants/sales-registry ─────────────────────────────────────────
    public function index(array $user, array $params = []): never {
        $merchantId = (int)$user['merchant_id'];

        $page   = max(1, (int)($params['page']  ?? 1));
        $limit  = min(50, max(10, (int)($params['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $where  = 'sr.merchant_id = ?';
        $binds  = [$merchantId];

        if (!empty($params['store_id'])) {
            $where  .= ' AND sr.store_id = ?';
            $binds[] = (int)$params['store_id'];
        }
        if (!empty($params['from'])) {
            $where  .= ' AND DATE(sr.transaction_date) >= ?';
            $binds[] = $params['from'];
        }
        if (!empty($params['to'])) {
            $where  .= ' AND DATE(sr.transaction_date) <= ?';
            $binds[] = $params['to'];
        }
        if (!empty($params['payment_method'])) {
            $where  .= ' AND sr.payment_method = ?';
            $binds[] = $params['payment_method'];
        }

        $total = (int)$this->db->queryOne(
            "SELECT COUNT(*) AS cnt FROM sales_registry sr WHERE {$where}",
            $binds
        )['cnt'];

        $rows = $this->db->query(
            "SELECT sr.*,
                    s.store_name,
                    cu.name  AS customer_name,
                    c.title  AS coupon_title,
                    c.coupon_code
             FROM sales_registry sr
             LEFT JOIN stores    s  ON  s.id = sr.store_id
             LEFT JOIN customers cu ON cu.id = sr.customer_id
             LEFT JOIN coupons   c  ON  c.id = sr.coupon_used
             WHERE {$where}
             ORDER BY sr.transaction_date DESC
             LIMIT ? OFFSET ?",
            [...$binds, $limit, $offset]
        );

        Response::success($rows, 'OK', [
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
            'pages' => (int)ceil($total / $limit),
        ]);
    }

    // ── POST /merchants/sales-registry ────────────────────────────────────────
    public function store(array $user, array $body): never {
        $merchantId = (int)$user['merchant_id'];

        $v = new Validator($body);
        $v->required('store_id')
          ->required('transaction_amount');
        if ($v->fails()) Response::validationError($v->errors());

        // Verify store ownership
        $store = $this->db->queryOne(
            'SELECT id FROM stores WHERE id = ? AND merchant_id = ? AND deleted_at IS NULL',
            [(int)$body['store_id'], $merchantId]
        );
        if (!$store) Response::notFound('Store not found');

        // Optional: resolve coupon
        $couponId = null;
        if (!empty($body['coupon_code'])) {
            $coupon = $this->db->queryOne(
                'SELECT id FROM coupons WHERE coupon_code = ? AND merchant_id = ?',
                [strtoupper(trim((string)$body['coupon_code'])), $merchantId]
            );
            if ($coupon) $couponId = (int)$coupon['id'];
        }

        $validPaymentMethods = ['cash', 'card', 'upi', 'wallet', 'other'];
        $paymentMethod = in_array($body['payment_method'] ?? '', $validPaymentMethods)
            ? $body['payment_method'] : null;

        $this->db->execute(
            "INSERT INTO sales_registry
                (merchant_id, store_id, customer_id, transaction_amount,
                 transaction_date, payment_method, coupon_used, discount_amount)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $merchantId,
                (int)$body['store_id'],
                !empty($body['customer_id']) ? (int)$body['customer_id'] : null,
                (float)$body['transaction_amount'],
                !empty($body['transaction_date']) ? $body['transaction_date'] : date('Y-m-d H:i:s'),
                $paymentMethod,
                $couponId,
                !empty($body['discount_amount']) ? (float)$body['discount_amount'] : 0.00,
            ]
        );

        $newId = $this->db->lastInsertId();
        $sale  = $this->db->queryOne('SELECT * FROM sales_registry WHERE id = ?', [$newId]);
        Response::created($sale, 'Sale recorded');
    }

    // ── GET /merchants/sales-registry/summary ────────────────────────────────
    public function summary(array $user, array $params = []): never {
        $merchantId = (int)$user['merchant_id'];

        $from = $params['from'] ?? date('Y-m-01');
        $to   = $params['to']   ?? date('Y-m-d');

        $totals = $this->db->queryOne(
            "SELECT COUNT(*) AS total_sales,
                    COALESCE(ROUND(SUM(transaction_amount), 2), 0) AS total_revenue,
                    COALESCE(ROUND(AVG(transaction_amount), 2), 0) AS avg_transaction,
                    COALESCE(ROUND(SUM(discount_amount), 2), 0) AS total_discounts
             FROM sales_registry
             WHERE merchant_id = ?
               AND DATE(transaction_date) BETWEEN ? AND ?",
            [$merchantId, $from, $to]
        );

        $byPayment = $this->db->query(
            "SELECT payment_method, COUNT(*) AS cnt,
                    ROUND(SUM(transaction_amount), 2) AS total
             FROM sales_registry
             WHERE merchant_id = ?
               AND DATE(transaction_date) BETWEEN ? AND ?
             GROUP BY payment_method
             ORDER BY cnt DESC",
            [$merchantId, $from, $to]
        );

        $daily = $this->db->query(
            "SELECT DATE(transaction_date) AS `date`,
                    COUNT(*) AS cnt,
                    ROUND(SUM(transaction_amount), 2) AS revenue
             FROM sales_registry
             WHERE merchant_id = ?
               AND DATE(transaction_date) BETWEEN ? AND ?
             GROUP BY DATE(transaction_date)
             ORDER BY `date` ASC",
            [$merchantId, $from, $to]
        );

        Response::success([
            'period'          => ['from' => $from, 'to' => $to],
            'total_sales'     => (int)$totals['total_sales'],
            'total_revenue'   => (float)$totals['total_revenue'],
            'avg_transaction' => (float)$totals['avg_transaction'],
            'total_discounts' => (float)$totals['total_discounts'],
            'by_payment'      => $byPayment,
            'daily'           => $daily,
        ]);
    }

    // ── GET /merchants/sales-registry/export ─────────────────────────────────
    public function export(array $user, array $params = []): never {
        $merchantId = (int)$user['merchant_id'];

        $from = $params['from'] ?? date('Y-m-01');
        $to   = $params['to']   ?? date('Y-m-d');

        $rows = $this->db->query(
            "SELECT sr.id, sr.transaction_date, s.store_name,
                    sr.transaction_amount, sr.discount_amount,
                    sr.payment_method, cu.name AS customer_name,
                    c.coupon_code
             FROM sales_registry sr
             LEFT JOIN stores    s  ON  s.id = sr.store_id
             LEFT JOIN customers cu ON cu.id = sr.customer_id
             LEFT JOIN coupons   c  ON  c.id = sr.coupon_used
             WHERE sr.merchant_id = ?
               AND DATE(sr.transaction_date) BETWEEN ? AND ?
             ORDER BY sr.transaction_date DESC",
            [$merchantId, $from, $to]
        );

        // Stream CSV
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"sales_{$from}_to_{$to}.csv\"");
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Date', 'Store', 'Amount (₹)', 'Discount (₹)', 'Payment', 'Customer', 'Coupon']);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['id'],
                $r['transaction_date'],
                $r['store_name'] ?? '',
                $r['transaction_amount'],
                $r['discount_amount'],
                $r['payment_method'] ?? '',
                $r['customer_name'] ?? '',
                $r['coupon_code'] ?? '',
            ]);
        }
        fclose($out);
        exit;
    }
}
