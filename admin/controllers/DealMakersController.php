<?php
require_once CORE_PATH . '/Auth.php';
require_once MODEL_PATH . '/DealMaker.php';

class DealMakersController extends Controller {

    private DealMaker $model;
    private $auth;
    private const PER_PAGE = 25;
    private const ALLOWED_TYPES = ['super_admin', 'city_admin', 'sales_admin'];

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

        $this->model = new DealMaker();
    }

    // ─── APPROVED DEALMAKERS ──────────────────────────────────────────────────

    public function index() {
        $filters = [
            'search'      => trim($_GET['search'] ?? ''),
            'user_status' => $_GET['user_status'] ?? '',
        ];

        $total   = $this->model->countDealmakers($filters);
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $filters['limit']  = self::PER_PAGE;
        $filters['offset'] = ($page - 1) * self::PER_PAGE;

        $dealmakers   = $this->model->getAllDealmakers($filters);
        $stats        = $this->model->getStats();
        $totalPages   = (int)ceil($total / self::PER_PAGE);

        $this->loadView('dealmakers/index', compact('dealmakers', 'filters', 'stats', 'total', 'page', 'totalPages'));
    }

    // ─── PENDING REQUESTS ─────────────────────────────────────────────────────

    public function requests() {
        $filters = ['search' => trim($_GET['search'] ?? '')];

        $total  = $this->model->countPendingRequests($filters);
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $filters['limit']  = self::PER_PAGE;
        $filters['offset'] = ($page - 1) * self::PER_PAGE;

        $requests   = $this->model->getPendingRequests($filters);
        $stats      = $this->model->getStats();
        $totalPages = (int)ceil($total / self::PER_PAGE);

        $this->loadView('dealmakers/requests', compact('requests', 'filters', 'stats', 'total', 'page', 'totalPages'));
    }

    // ─── APPROVE ──────────────────────────────────────────────────────────────

    public function approve() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->redirect('deal-makers/requests'); return; }
        $this->requireCSRF();

        $id      = (int)($_POST['customer_id'] ?? 0);
        $adminId = $this->auth->getCurrentUser()['admin_id'];

        if (!$id) { $this->redirectWithError('deal-makers/requests', 'Invalid customer.'); return; }

        if ($this->model->approve($id, $adminId)) {
            $_SESSION['success'] = 'Deal Maker approved successfully.';
        } else {
            $_SESSION['error'] = 'Failed to approve Deal Maker.';
        }
        $this->redirect('deal-makers/requests');
    }

    // ─── REVOKE ───────────────────────────────────────────────────────────────

    public function revoke() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->redirect('deal-makers'); return; }
        $this->requireCSRF();

        $id = (int)($_POST['customer_id'] ?? 0);
        if (!$id) { $this->redirectWithError('deal-makers', 'Invalid customer.'); return; }

        if ($this->model->revoke($id)) {
            $_SESSION['success'] = 'Deal Maker status revoked.';
        } else {
            $_SESSION['error'] = 'Failed to revoke Deal Maker status.';
        }
        $this->redirect('deal-makers');
    }

    // ─── TASKS LIST ───────────────────────────────────────────────────────────

    public function tasks() {
        $filters = [
            'search'       => trim($_GET['search'] ?? ''),
            'status'       => $_GET['status'] ?? '',
            'task_type'    => $_GET['task_type'] ?? '',
            'reward_status'=> $_GET['reward_status'] ?? '',
            'dealmaker_id' => (int)($_GET['dealmaker_id'] ?? 0) ?: '',
        ];

        $total  = $this->model->countTasks($filters);
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $filters['limit']  = self::PER_PAGE;
        $filters['offset'] = ($page - 1) * self::PER_PAGE;

        $tasks      = $this->model->getAllTasks($filters);
        $stats      = $this->model->getStats();
        $dealmakers = $this->model->getDealmakersList();
        $totalPages = (int)ceil($total / self::PER_PAGE);

        $this->loadView('dealmakers/tasks', compact('tasks', 'filters', 'stats', 'dealmakers', 'total', 'page', 'totalPages'));
    }

    // ─── ADD TASK ─────────────────────────────────────────────────────────────

    public function addTask() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->redirect('deal-makers/tasks'); return; }
        $this->requireCSRF();

        $data = [
            'dealmaker_customer_id' => (int)($_POST['dealmaker_customer_id'] ?? 0),
            'task_type'             => trim($_POST['task_type'] ?? ''),
            'task_description'      => trim($_POST['task_description'] ?? ''),
            'assigned_by_admin_id'  => $this->auth->getCurrentUser()['admin_id'],
            'reward_amount'         => !empty($_POST['reward_amount']) ? (float)$_POST['reward_amount'] : null,
        ];

        $validTypes = ['customer_assistance','merchant_visit','survey','promotion','other'];
        if (!$data['dealmaker_customer_id'] || !$data['task_type'] || !$data['task_description']) {
            $this->redirectWithError('deal-makers/tasks', 'All required fields must be filled.');
            return;
        }
        if (!in_array($data['task_type'], $validTypes)) {
            $this->redirectWithError('deal-makers/tasks', 'Invalid task type.');
            return;
        }

        if ($this->model->createTask($data)) {
            $_SESSION['success'] = 'Task assigned successfully.';
        } else {
            $_SESSION['error'] = 'Failed to assign task.';
        }
        $this->redirect('deal-makers/tasks');
    }

    // ─── UPDATE TASK STATUS ───────────────────────────────────────────────────

    public function updateTask() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->redirect('deal-makers/tasks'); return; }
        $this->requireCSRF();

        $taskId = (int)($_POST['task_id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        $notes  = trim($_POST['completion_notes'] ?? '');

        $validStatuses = ['assigned','in_progress','completed','verified'];
        if (!$taskId || !in_array($status, $validStatuses)) {
            $this->redirectWithError('deal-makers/tasks', 'Invalid task or status.');
            return;
        }

        if ($this->model->updateTaskStatus($taskId, $status, $notes)) {
            $_SESSION['success'] = 'Task status updated.';
        } else {
            $_SESSION['error'] = 'Failed to update task.';
        }
        $this->redirect('deal-makers/tasks');
    }

    // ─── MARK REWARD PAID ─────────────────────────────────────────────────────

    public function payReward() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->redirect('deal-makers/tasks'); return; }
        $this->requireCSRF();

        $taskId = (int)($_POST['task_id'] ?? 0);
        if (!$taskId) { $this->redirectWithError('deal-makers/tasks', 'Invalid task.'); return; }

        if ($this->model->markRewardPaid($taskId)) {
            $_SESSION['success'] = 'Reward marked as paid.';
        } else {
            $_SESSION['error'] = 'Failed to update reward status.';
        }
        $this->redirect('deal-makers/tasks');
    }
}
