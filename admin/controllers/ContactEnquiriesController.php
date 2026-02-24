<?php
require_once CORE_PATH . '/Auth.php';
require_once MODEL_PATH . '/ContactEnquiry.php';

class ContactEnquiriesController extends Controller {

    private $auth;
    private $enquiryModel;

    public function __construct() {
        $this->auth = new Auth();
        if (!$this->auth->isLoggedIn()) {
            $_SESSION['error'] = 'Please login to continue.';
            $this->redirect('auth/login');
            return;
        }
        $this->enquiryModel = new ContactEnquiry();
    }

    // ── LIST ─────────────────────────────────────────────────────────────────
    public function index() {
        $current_user = $this->auth->getCurrentUser();
        $status       = $_GET['status'] ?? '';
        $page         = max(1, (int)($_GET['page'] ?? 1));
        $enquiries    = $this->enquiryModel->getAll($status, $page);
        $counts       = $this->enquiryModel->getStatusCounts();
        $total        = $this->enquiryModel->countByStatus($status);
        $this->loadView('contact-enquiries/index', [
            'title'         => 'Contact Enquiries',
            'current_user'  => $current_user,
            'enquiries'     => $enquiries,
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
        $enquiry      = $this->enquiryModel->findById($id);
        if (!$enquiry) {
            $_SESSION['error'] = 'Enquiry not found.'; $this->redirect('contact-enquiries'); return;
        }
        // Auto-mark as read
        $this->enquiryModel->markRead($id);

        $this->loadView('contact-enquiries/detail', [
            'title'         => 'Enquiry Detail',
            'current_user'  => $current_user,
            'enquiry'       => $enquiry,
            'flash_success' => $_SESSION['success'] ?? null,
            'flash_error'   => $_SESSION['error']   ?? null,
        ]);
        unset($_SESSION['success'], $_SESSION['error']);
    }

    // ── UPDATE STATUS + NOTES ─────────────────────────────────────────────────
    public function respond() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->redirect('contact-enquiries'); return; }
        $id     = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'responded';
        $notes  = trim($_POST['admin_notes'] ?? '');
        $this->enquiryModel->updateStatus($id, $status, $notes);
        $_SESSION['success'] = 'Enquiry updated.';
        $this->redirect("contact-enquiries/detail?id=$id");
    }
}
