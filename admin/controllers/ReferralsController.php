<?php
require_once CORE_PATH  . '/Auth.php';
require_once MODEL_PATH . '/Referral.php';

class ReferralsController extends Controller {

    private $auth;
    private $model;

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

        $this->model = new Referral();
    }

    // ─── LIST ─────────────────────────────────────────────────────────────────

    public function index() {
        $filters = [
            'status'      => $_GET['status']      ?? '',
            'reward_given'=> $_GET['reward_given'] ?? '',
            'referrer_id' => (int)($_GET['referrer_id'] ?? 0) ?: '',
            'date_from'   => $_GET['date_from']   ?? '',
            'date_to'     => $_GET['date_to']     ?? '',
            'search'      => trim($_GET['search']  ?? ''),
        ];

        $perPage     = self::PER_PAGE;
        $totalCount  = $this->model->countWithDetails($filters);
        $totalPages  = max(1, (int)ceil($totalCount / $perPage));
        $currentPage = max(1, min((int)($_GET['page'] ?? 1), $totalPages));
        $offset      = ($currentPage - 1) * $perPage;

        $referrals  = $this->model->getAllWithDetails(array_merge($filters, ['limit' => $perPage, 'offset' => $offset]));
        $stats      = $this->model->getStats();
        $topReferrers = $this->model->getTopReferrers(5);

        $this->loadView('referrals/index', [
            'title'        => 'Referral Tracking',
            'referrals'    => $referrals,
            'stats'        => $stats,
            'topReferrers' => $topReferrers,
            'filters'      => $filters,
            'totalCount'   => $totalCount,
            'totalPages'   => $totalPages,
            'currentPage'  => $currentPage,
            'cu'           => $this->auth->getCurrentUser(),
        ]);
    }

    // ─── DETAIL ───────────────────────────────────────────────────────────────

    public function detail() {
        $id       = (int)($_GET['id'] ?? 0);
        $referral = $this->model->findWithDetails($id);

        if (!$referral) {
            $_SESSION['error'] = 'Referral not found.';
            $this->redirect('referrals');
            return;
        }

        $this->loadView('referrals/view', [
            'title'    => 'Referral Detail',
            'referral' => $referral,
            'cu'       => $this->auth->getCurrentUser(),
        ]);
    }

    // ─── MARK REWARD GIVEN ────────────────────────────────────────────────────

    public function reward() {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            $this->jsonError('Invalid ID.');
            return;
        }

        $referral = $this->model->findWithDetails($id);
        if (!$referral) {
            $this->jsonError('Referral not found.');
            return;
        }

        if ($referral['reward_given']) {
            $this->jsonError('Reward already marked as given.');
            return;
        }

        $ok = $this->model->markRewardGiven($id);
        if ($ok) {
            echo json_encode(['success' => true]);
        } else {
            $this->jsonError('Failed to update reward status.');
        }
    }

    // ─── OVERRIDE STATUS ──────────────────────────────────────────────────────

    public function override() {
        $id     = (int)($_POST['id']     ?? 0);
        $status = trim($_POST['status']  ?? '');

        if (!$id || !$status) {
            $this->jsonError('Missing parameters.');
            return;
        }

        $ok = $this->model->overrideStatus($id, $status);
        if ($ok) {
            echo json_encode(['success' => true]);
        } else {
            $this->jsonError('Invalid status or update failed.');
        }
    }

    // ─── HELPERS ──────────────────────────────────────────────────────────────

    private function jsonError($msg) {
        echo json_encode(['success' => false, 'error' => $msg]);
    }
}
