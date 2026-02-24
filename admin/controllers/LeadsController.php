<?php
require_once CORE_PATH . '/Auth.php';
require_once MODEL_PATH . '/Lead.php';
require_once MODEL_PATH . '/Admin.php';

class LeadsController extends Controller {

    private $auth;
    private $leadModel;
    private $adminModel;

    public function __construct() {
        $this->auth = new Auth();
        if (!$this->auth->isLoggedIn()) {
            $_SESSION['error'] = 'Please login to continue.';
            $this->redirect('auth/login');
            return;
        }
        $this->leadModel  = new Lead();
        $this->adminModel = new Admin();
    }

    // ── LIST ─────────────────────────────────────────────────────────────────
    public function index() {
        $current_user = $this->auth->getCurrentUser();
        $status       = $_GET['status'] ?? '';
        $page         = max(1, (int)($_GET['page'] ?? 1));
        $leads        = $this->leadModel->getAll($status, $page);
        $total        = $this->leadModel->countByStatus($status);
        $counts       = $this->leadModel->getStatusCounts();
        $this->loadView('leads/index', [
            'title'         => 'Business Leads',
            'current_user'  => $current_user,
            'leads'         => $leads,
            'counts'        => $counts,
            'total'         => $total,
            'page'          => $page,
            'status_filter' => $status,
            'flash_success' => $_SESSION['success'] ?? null,
            'flash_error'   => $_SESSION['error']   ?? null,
        ]);
        unset($_SESSION['success'], $_SESSION['error']);
    }

    // ── DETAIL ───────────────────────────────────────────────────────────────
    public function detail() {
        $current_user = $this->auth->getCurrentUser();
        $id           = (int)($_GET['id'] ?? 0);
        $lead         = $this->leadModel->findById($id);
        if (!$lead) {
            $_SESSION['error'] = 'Lead not found.'; $this->redirect('leads'); return;
        }
        $admins = $this->adminModel->getAllWithUsers();
        $this->loadView('leads/detail', [
            'title'         => 'Lead Detail',
            'current_user'  => $current_user,
            'lead'          => $lead,
            'admins'        => $admins,
            'flash_success' => $_SESSION['success'] ?? null,
            'flash_error'   => $_SESSION['error']   ?? null,
        ]);
        unset($_SESSION['success'], $_SESSION['error']);
    }

    // ── UPDATE STATUS ─────────────────────────────────────────────────────────
    public function updateStatus() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->redirect('leads'); return; }
        $id     = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $notes  = trim($_POST['notes'] ?? '');
        $valid  = ['new','contacted','qualified','converted','rejected'];
        if (!in_array($status, $valid)) {
            $_SESSION['error'] = 'Invalid status.'; $this->redirect("leads/detail?id=$id"); return;
        }
        $this->leadModel->updateStatus($id, $status, $notes);
        $_SESSION['success'] = 'Lead status updated.';
        $this->redirect("leads/detail?id=$id");
    }

    // ── ASSIGN ───────────────────────────────────────────────────────────────
    public function assign() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->redirect('leads'); return; }
        $id      = (int)($_POST['id'] ?? 0);
        $adminId = (int)($_POST['admin_id'] ?? 0);
        $this->leadModel->assign($id, $adminId);
        $_SESSION['success'] = 'Lead assigned.';
        $this->redirect("leads/detail?id=$id");
    }
}
