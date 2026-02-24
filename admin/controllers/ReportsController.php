<?php

require_once ROOT_PATH . '/core/Auth.php';
require_once MODEL_PATH . '/Report.php';

class ReportsController extends Controller {

    private Report $model;
    private Auth   $auth;
    private const PER_PAGE = 25;

    public function __construct() {
        $this->model = new Report();
        $this->auth  = new Auth();
    }

    // GET /reports  →  redirect to dashboard
    public function index(): void {
        $this->redirect('reports/dashboard');
    }

    // GET /reports/dashboard
    public function dashboard(): void {
        if (!$this->auth->isLoggedIn()) { $this->redirect('auth/login'); return; }
        $cu      = $this->auth->getCurrentUser();
        $stats   = $this->model->getSummaryStats();
        $monthly = $this->model->getMonthlyOverview(6);
        $this->loadView('reports/index', compact('cu', 'stats', 'monthly'));
    }

    // GET /reports/customers?date_from=&date_to=&page=
    public function customers(): void {
        if (!$this->auth->isLoggedIn()) { $this->redirect('auth/login'); return; }
        $cu    = $this->auth->getCurrentUser();
        [$from, $to] = $this->dateRange();
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $offset = ($page - 1) * self::PER_PAGE;

        $stats = $this->model->getCustomerStats($from, $to);
        $trend = $this->model->getCustomerTrend($from, $to);
        $list  = $this->model->getCustomerList($from, $to, self::PER_PAGE, $offset);
        $total = $this->model->countCustomers($from, $to);
        $pages = (int)ceil($total / self::PER_PAGE);

        $this->loadView('reports/customers', compact('cu','stats','trend','list','total','pages','page','from','to'));
    }

    // GET /reports/merchants?date_from=&date_to=&page=
    public function merchants(): void {
        if (!$this->auth->isLoggedIn()) { $this->redirect('auth/login'); return; }
        $cu    = $this->auth->getCurrentUser();
        [$from, $to] = $this->dateRange();
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $offset = ($page - 1) * self::PER_PAGE;

        $stats   = $this->model->getMerchantStats($from, $to);
        $trend   = $this->model->getMerchantTrend($from, $to);
        $topList = $this->model->getTopMerchantsByRedemptions($from, $to, 10);
        $list    = $this->model->getMerchantList($from, $to, self::PER_PAGE, $offset);
        $total   = $this->model->countMerchants($from, $to);
        $pages   = (int)ceil($total / self::PER_PAGE);

        $this->loadView('reports/merchants', compact('cu','stats','trend','topList','list','total','pages','page','from','to'));
    }

    // GET /reports/redemptions?date_from=&date_to=&page=
    public function redemptions(): void {
        if (!$this->auth->isLoggedIn()) { $this->redirect('auth/login'); return; }
        $cu    = $this->auth->getCurrentUser();
        [$from, $to] = $this->dateRange();
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $offset = ($page - 1) * self::PER_PAGE;

        $stats   = $this->model->getRedemptionStats($from, $to);
        $trend   = $this->model->getRedemptionTrend($from, $to);
        $topCoup = $this->model->getTopCoupons($from, $to, 10);
        $list    = $this->model->getRedemptionList($from, $to, self::PER_PAGE, $offset);
        $total   = $this->model->countRedemptions($from, $to);
        $pages   = (int)ceil($total / self::PER_PAGE);

        $this->loadView('reports/redemptions', compact('cu','stats','trend','topCoup','list','total','pages','page','from','to'));
    }

    // GET /reports/revenue
    public function revenue(): void {
        if (!$this->auth->isLoggedIn()) { $this->redirect('auth/login'); return; }
        $cu = $this->auth->getCurrentUser();
        [$from, $to] = $this->dateRange();

        $stats     = $this->model->getRevenueStats($from, $to);
        $trend     = $this->model->getRevenueTrend($from, $to);
        $topMerch  = $this->model->getTopMerchantsByRevenue($from, $to, 10);

        $this->loadView('reports/revenue', compact('cu','stats','trend','topMerch','from','to'));
    }

    // GET /reports/subscription-report
    public function subscriptionReport(): void {
        if (!$this->auth->isLoggedIn()) { $this->redirect('auth/login'); return; }
        $cu = $this->auth->getCurrentUser();
        [$from, $to] = $this->dateRange();

        $stats = $this->model->getSubscriptionReportStats($from, $to);
        $trend = $this->model->getSubscriptionRevenueTrend($from, $to);

        $this->loadView('reports/subscriptions', compact('cu','stats','trend','from','to'));
    }

    // GET /reports/coupon-analytics
    public function couponAnalytics(): void {
        if (!$this->auth->isLoggedIn()) { $this->redirect('auth/login'); return; }
        $cu = $this->auth->getCurrentUser();
        [$from, $to] = $this->dateRange();

        $stats      = $this->model->getCouponAnalyticsStats($from, $to);
        $topSaved   = $this->model->getTopSavedCoupons($from, $to, 10);
        $saveTrend  = $this->model->getCouponSaveTrend($from, $to);

        $this->loadView('reports/coupon-analytics', compact('cu','stats','topSaved','saveTrend','from','to'));
    }

    // GET /reports/engagement
    public function engagement(): void {
        if (!$this->auth->isLoggedIn()) { $this->redirect('auth/login'); return; }
        $cu = $this->auth->getCurrentUser();
        [$from, $to] = $this->dateRange();

        $stats        = $this->model->getEngagementStats($from, $to);
        $topContests  = $this->model->getTopContestsByParticipation($from, $to, 10);
        $topSurveys   = $this->model->getTopSurveysByResponse($from, $to, 10);

        $this->loadView('reports/engagement', compact('cu','stats','topContests','topSurveys','from','to'));
    }

    // GET /reports/export (extended)
    public function export(): void {
        if (!$this->auth->isLoggedIn()) { $this->redirect('auth/login'); return; }
        [$from, $to] = $this->dateRange();
        $type = $_GET['type'] ?? 'redemptions';

        switch ($type) {
            case 'customers':
                $rows    = $this->model->exportCustomersCSV($from, $to);
                $headers = ['ID','Name','Email','Phone','Type','Registration','Subscription','Dealmaker','Created'];
                $file    = "customers_{$from}_{$to}.csv";
                break;
            case 'merchants':
                $rows    = $this->model->exportMerchantsCSV($from, $to);
                $headers = ['ID','Business Name','Email','Phone','Profile Status','Subscription','Premium','Created'];
                $file    = "merchants_{$from}_{$to}.csv";
                break;
            case 'revenue':
                $rows    = $this->model->exportRevenueCSV($from, $to);
                $headers = ['ID','Date','Business','Store','Customer','Phone','Amount (₹)','Discount (₹)','Payment Method'];
                $file    = "revenue_{$from}_{$to}.csv";
                break;
            default:
                $rows    = $this->model->exportRedemptionsCSV($from, $to);
                $headers = ['ID','Redeemed At','Coupon','Code','Customer','Merchant','Discount (₹)','Transaction (₹)'];
                $file    = "redemptions_{$from}_{$to}.csv";
        }

        header('Content-Type: text/csv; charset=UTF-8');
        header("Content-Disposition: attachment; filename=\"{$file}\"");
        header('Pragma: no-cache');
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Excel
        fputcsv($out, $headers);
        foreach ($rows as $row) fputcsv($out, array_values($row));
        fclose($out);
        exit;
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function dateRange(): array {
        $from = $_GET['date_from'] ?? date('Y-m-01');       // first of this month
        $to   = $_GET['date_to']   ?? date('Y-m-d');        // today
        // Sanitise
        $from = preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) ? $from : date('Y-m-01');
        $to   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)   ? $to   : date('Y-m-d');
        if ($from > $to) [$from, $to] = [$to, $from];
        return [$from, $to];
    }
}
