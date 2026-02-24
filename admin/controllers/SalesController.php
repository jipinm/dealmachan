<?php
require_once CORE_PATH  . '/Auth.php';
require_once MODEL_PATH . '/SalesRegistry.php';
require_once MODEL_PATH . '/Merchant.php';

class SalesController extends Controller {

    private $auth;
    private $salesModel;

    private const ALLOWED_TYPES = ['super_admin', 'city_admin', 'sales_admin'];
    private const PER_PAGE      = 25;

    public function __construct() {
        $this->auth = new Auth();

        if (!$this->auth->isLoggedIn()) {
            $_SESSION['error'] = 'Please login to continue.';
            $this->redirect('auth/login');
            return;
        }

        $cu = $this->auth->getCurrentUser();
        if (!in_array($cu['admin_type'], self::ALLOWED_TYPES)) {
            $_SESSION['error'] = 'Access denied.';
            $this->redirect('dashboard');
            return;
        }

        $this->salesModel = new SalesRegistry();
    }

    // ─── LIST ─────────────────────────────────────────────────────────────────

    public function index() {
        $filters = [
            'merchant_id'    => !empty($_GET['merchant_id'])    ? (int)$_GET['merchant_id']    : '',
            'payment_method' => $_GET['payment_method'] ?? '',
            'date_from'      => $_GET['date_from'] ?? '',
            'date_to'        => $_GET['date_to']   ?? '',
            'search'         => trim($_GET['search'] ?? ''),
        ];

        $perPage     = self::PER_PAGE;
        $totalCount  = $this->salesModel->countWithDetails($filters);
        $totalPages  = max(1, (int)ceil($totalCount / $perPage));
        $currentPage = max(1, min((int)($_GET['page'] ?? 1), $totalPages));
        $offset      = ($currentPage - 1) * $perPage;

        $sales       = $this->salesModel->getAllWithDetails(array_merge($filters, ['limit' => $perPage, 'offset' => $offset]));
        $stats       = $this->salesModel->getStats($filters);
        $merchantModel = new Merchant();
        $merchants   = $merchantModel->getAllWithDetails(['limit' => 200]);

        $this->loadView('sales/index', [
            'title'        => 'Sales Registry',
            'sales'        => $sales,
            'stats'        => $stats,
            'merchants'    => $merchants,
            'filters'      => $filters,
            'currentPage'  => $currentPage,
            'totalPages'   => $totalPages,
            'totalCount'   => $totalCount,
            'perPage'      => $perPage,
            'current_user' => $this->auth->getCurrentUser(),
        ]);
    }

    // ─── DETAIL ───────────────────────────────────────────────────────────────

    public function detail() {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { $this->redirectWithError('sales', 'Invalid sale ID.'); return; }

        $sale = $this->salesModel->findWithDetails($id);
        if (!$sale) { $this->redirectWithError('sales', 'Sale record not found.'); return; }

        $this->loadView('sales/view', [
            'title'        => 'Sale #' . $sale['id'],
            'sale'         => $sale,
            'current_user' => $this->auth->getCurrentUser(),
        ]);
    }

    // ─── CSV EXPORT ───────────────────────────────────────────────────────────

    public function export() {
        $filters = [
            'merchant_id'    => !empty($_GET['merchant_id'])    ? (int)$_GET['merchant_id']    : '',
            'payment_method' => $_GET['payment_method'] ?? '',
            'date_from'      => $_GET['date_from'] ?? '',
            'date_to'        => $_GET['date_to']   ?? '',
            'search'         => trim($_GET['search'] ?? ''),
        ];

        $rows = $this->salesModel->getForExport($filters);

        $filename = 'sales_export_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Date', 'Business', 'Store', 'Customer', 'Phone', 'Amount (₹)', 'Discount (₹)', 'Payment', 'Coupon', 'Coupon Code']);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['id'],
                date('d/m/Y H:i', strtotime($r['transaction_date'])),
                $r['business_name'],
                $r['store_name'],
                $r['customer_name'] ?? '—',
                $r['customer_phone'] ?? '—',
                number_format($r['transaction_amount'], 2),
                number_format($r['discount_amount'], 2),
                ucfirst($r['payment_method'] ?? ''),
                $r['coupon_title'] ?? '',
                $r['coupon_code']  ?? '',
            ]);
        }
        fclose($out);
        exit;
    }
}
