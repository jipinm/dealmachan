<?php
require_once CORE_PATH . '/Auth.php';
require_once MODEL_PATH . '/MysteryShoppingTask.php';

class MysteryShoppingController extends Controller {

    private MysteryShoppingTask $model;
    private $auth;
    private const PER_PAGE = 20;
    private const ALLOWED_TYPES = ['super_admin', 'city_admin', 'promoter_admin'];

    public function __construct() {
        $this->auth = new Auth();

        if (!$this->auth->isLoggedIn()) {
            $_SESSION['error'] = 'Please login to continue.';
            $this->redirect('auth/login');
            return;
        }

        $cu = $this->auth->getCurrentUser();
        if (!in_array($cu['admin_type'] ?? '', self::ALLOWED_TYPES)) {
            $_SESSION['error'] = 'Access denied.';
            $this->redirect('dashboard');
            return;
        }

        $this->model = new MysteryShoppingTask();
    }

    // ─── LIST ─────────────────────────────────────────────────────────────────

    public function index() {
        $filters = [
            'search'         => trim($_GET['search'] ?? ''),
            'status'         => $_GET['status'] ?? '',
            'payment_status' => $_GET['payment_status'] ?? '',
            'merchant_id'    => (int)($_GET['merchant_id'] ?? 0) ?: '',
        ];

        $total  = $this->model->countWithDetails($filters);
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $filters['limit']  = self::PER_PAGE;
        $filters['offset'] = ($page - 1) * self::PER_PAGE;

        $tasks      = $this->model->getAllWithDetails($filters);
        $stats      = $this->model->getStats();
        $merchants  = $this->model->getMerchantList();
        $totalPages = (int)ceil($total / self::PER_PAGE);

        $this->loadView('mystery-shopping/index',
            compact('tasks', 'filters', 'stats', 'merchants', 'total', 'page', 'totalPages'));
    }

    // ─── ADD ──────────────────────────────────────────────────────────────────

    public function add() {
        $merchants = $this->model->getMerchantList();
        $shoppers  = $this->model->getShopperList();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCSRF();

            $data = [
                'customer_id'          => (int)($_POST['customer_id'] ?? 0),
                'merchant_id'          => (int)($_POST['merchant_id'] ?? 0),
                'store_id'             => (int)($_POST['store_id'] ?? 0) ?: null,
                'task_description'     => trim($_POST['task_description'] ?? ''),
                'checklist_json'       => $this->buildChecklistJson($_POST),
                'assigned_by_admin_id' => $this->auth->getCurrentUser()['admin_id'],
                'payment_amount'       => !empty($_POST['payment_amount']) ? (float)$_POST['payment_amount'] : null,
            ];

            if (!$data['customer_id'] || !$data['merchant_id'] || !$data['task_description']) {
                $this->redirectWithError('mystery-shopping/add', 'Shopper, merchant and task description are required.');
                return;
            }

            if ($this->model->createTask($data)) {
                $_SESSION['success'] = 'Mystery shopping task assigned successfully.';
                $this->redirect('mystery-shopping');
            } else {
                $this->redirectWithError('mystery-shopping/add', 'Failed to create task.');
            }
            return;
        }

        $this->loadView('mystery-shopping/add', compact('merchants', 'shoppers'));
    }

    // ─── DETAIL / REPORT ──────────────────────────────────────────────────────

    public function reports() {
        $id   = (int)($_GET['id'] ?? 0);
        $task = $this->model->findWithDetails($id);
        if (!$task) {
            $_SESSION['error'] = 'Task not found.';
            $this->redirect('mystery-shopping');
            return;
        }

        $checklist = json_decode($task['checklist_json'] ?? '[]', true) ?? [];
        $report    = json_decode($task['report_json']    ?? '{}', true) ?? [];

        $this->loadView('mystery-shopping/reports', compact('task', 'checklist', 'report'));
    }

    // ─── UPDATE STATUS ────────────────────────────────────────────────────────

    public function updateStatus() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->redirect('mystery-shopping'); return; }
        $this->requireCSRF();

        $id     = (int)($_POST['task_id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        $notes  = trim($_POST['admin_notes'] ?? '');
        $valid  = ['assigned', 'in_progress', 'completed', 'verified', 'rejected'];

        if (!$id || !in_array($status, $valid)) {
            $this->redirectWithError('mystery-shopping', 'Invalid task or status.');
            return;
        }

        $redirect = "mystery-shopping/reports?id={$id}";

        if ($this->model->updateStatus($id, $status, $notes)) {
            $_SESSION['success'] = 'Task status updated to ' . ucfirst(str_replace('_', ' ', $status)) . '.';
        } else {
            $_SESSION['error'] = 'Failed to update status.';
        }
        $this->redirect($redirect);
    }

    // ─── PAY ──────────────────────────────────────────────────────────────────

    public function payPayment() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->redirect('mystery-shopping'); return; }
        $this->requireCSRF();

        $id = (int)($_POST['task_id'] ?? 0);
        if (!$id) { $this->redirectWithError('mystery-shopping', 'Invalid task.'); return; }

        if ($this->model->markPaymentPaid($id)) {
            $_SESSION['success'] = 'Payment marked as paid.';
        } else {
            $_SESSION['error'] = 'Failed to update payment.';
        }
        $this->redirect("mystery-shopping/reports?id={$id}");
    }

    // ─── AJAX: stores for a merchant ──────────────────────────────────────────

    public function storesJson() {
        $merchantId = (int)($_GET['merchant_id'] ?? 0);
        $stores     = $merchantId ? $this->model->getStoresForMerchant($merchantId) : [];
        $this->json($stores);
    }

    // ─── HELPER ───────────────────────────────────────────────────────────────

    private function buildChecklistJson($post) {
        $items = $post['checklist_item'] ?? [];
        $result = [];
        foreach ($items as $item) {
            $item = trim($item);
            if ($item !== '') {
                $result[] = ['item' => $item, 'checked' => false];
            }
        }
        return empty($result) ? null : json_encode($result, JSON_UNESCAPED_UNICODE);
    }
}
