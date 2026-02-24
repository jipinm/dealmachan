<?php
require_once CORE_PATH  . '/Auth.php';
require_once MODEL_PATH . '/Grievance.php';
require_once MODEL_PATH . '/Merchant.php';

class GrievancesController extends Controller {

    private $auth;
    private $grievanceModel;

    private const ALLOWED_TYPES = ['super_admin', 'city_admin', 'sales_admin'];
    private const PER_PAGE      = 20;

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

        $this->grievanceModel = new Grievance();
    }

    // ─── LIST ─────────────────────────────────────────────────────────────────

    public function index() {
        $filters = [
            'status'      => $_GET['status']      ?? '',
            'priority'    => $_GET['priority']    ?? '',
            'merchant_id' => !empty($_GET['merchant_id']) ? (int)$_GET['merchant_id'] : '',
            'search'      => trim($_GET['search'] ?? ''),
        ];

        $perPage     = self::PER_PAGE;
        $totalCount  = $this->grievanceModel->countWithDetails($filters);
        $totalPages  = max(1, (int)ceil($totalCount / $perPage));
        $currentPage = max(1, min((int)($_GET['page'] ?? 1), $totalPages));
        $offset      = ($currentPage - 1) * $perPage;

        $fetchFilters = array_merge($filters, ['limit' => $perPage, 'offset' => $offset]);
        $grievances   = $this->grievanceModel->getAllWithDetails($fetchFilters);
        $stats        = $this->grievanceModel->getStats();
        $merchants    = $this->grievanceModel->getMerchantsWithGrievances();

        $this->loadView('grievances/index', [
            'title'       => 'Grievance Management',
            'grievances'  => $grievances,
            'stats'       => $stats,
            'merchants'   => $merchants,
            'filters'     => $filters,
            'currentPage' => $currentPage,
            'totalPages'  => $totalPages,
            'totalCount'  => $totalCount,
            'perPage'     => $perPage,
            'current_user'=> $this->auth->getCurrentUser(),
        ]);
    }

    // ─── DETAIL ───────────────────────────────────────────────────────────────

    public function detail() {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { $this->redirectWithError('grievances', 'Invalid grievance ID.'); return; }

        $grievance = $this->grievanceModel->findWithDetails($id);
        if (!$grievance) { $this->redirectWithError('grievances', 'Grievance not found.'); return; }

        $this->loadView('grievances/view', [
            'title'       => 'Grievance #' . $grievance['id'] . ' — ' . escape($grievance['subject']),
            'grievance'   => $grievance,
            'statuses'    => Grievance::$statuses,
            'priorities'  => Grievance::$priorities,
            'current_user'=> $this->auth->getCurrentUser(),
        ]);
    }

    // ─── UPDATE STATUS ────────────────────────────────────────────────────────

    public function updateStatus() {
        $this->requireCSRF();

        $id              = (int)($_POST['id'] ?? 0);
        $status          = sanitize($_POST['status'] ?? '');
        $resolutionNotes = trim($_POST['resolution_notes'] ?? '');
        $redirect        = sanitize($_POST['redirect'] ?? 'grievances');

        if (!$id) { $this->redirectWithError($redirect, 'Invalid grievance ID.'); return; }

        if (!in_array($status, Grievance::$statuses)) {
            $this->redirectWithError($redirect, 'Invalid status value.');
            return;
        }

        $grievance = $this->grievanceModel->find($id);
        if (!$grievance) { $this->redirectWithError($redirect, 'Grievance not found.'); return; }

        $this->grievanceModel->updateStatus($id, $status, $resolutionNotes ?: null);

        $cu = $this->auth->getCurrentUser();
        logAudit('grievance_status_updated', $id, 'grievances', $cu['id']);

        $_SESSION['success'] = "Grievance status updated to " . ucfirst(str_replace('_', ' ', $status)) . ".";
        $this->redirect($redirect);
    }

    // ─── UPDATE PRIORITY ──────────────────────────────────────────────────────

    public function updatePriority() {
        $this->requireCSRF();

        $id       = (int)($_POST['id'] ?? 0);
        $priority = sanitize($_POST['priority'] ?? '');
        $redirect = sanitize($_POST['redirect'] ?? 'grievances');

        if (!$id) { $this->redirectWithError($redirect, 'Invalid grievance ID.'); return; }

        if (!in_array($priority, Grievance::$priorities)) {
            $this->redirectWithError($redirect, 'Invalid priority value.');
            return;
        }

        $grievance = $this->grievanceModel->find($id);
        if (!$grievance) { $this->redirectWithError($redirect, 'Grievance not found.'); return; }

        $this->grievanceModel->updatePriority($id, $priority);

        $cu = $this->auth->getCurrentUser();
        logAudit('grievance_priority_updated', $id, 'grievances', $cu['id']);

        $_SESSION['success'] = "Grievance priority updated to " . ucfirst($priority) . ".";
        $this->redirect($redirect);
    }

    // ─── ADD / UPDATE RESOLUTION NOTES ────────────────────────────────────────

    public function addNote() {
        $this->requireCSRF();

        $id       = (int)($_POST['id'] ?? 0);
        $notes    = trim($_POST['resolution_notes'] ?? '');
        $redirect = sanitize($_POST['redirect'] ?? 'grievances');

        if (!$id)    { $this->redirectWithError($redirect, 'Invalid grievance ID.'); return; }
        if (!$notes) { $this->redirectWithError($redirect, 'Resolution notes cannot be empty.'); return; }

        $grievance = $this->grievanceModel->find($id);
        if (!$grievance) { $this->redirectWithError($redirect, 'Grievance not found.'); return; }

        $this->grievanceModel->addNote($id, $notes);

        $cu = $this->auth->getCurrentUser();
        logAudit('grievance_note_added', $id, 'grievances', $cu['id']);

        $_SESSION['success'] = 'Resolution notes saved.';
        $this->redirect($redirect);
    }

    // ─── FORCE CLOSE ──────────────────────────────────────────────────────────

    public function forceClose() {
        $this->requireCSRF();

        $id       = (int)($_POST['id'] ?? 0);
        $redirect = sanitize($_POST['redirect'] ?? 'grievances');

        if (!$id) { $this->redirectWithError($redirect, 'Invalid grievance ID.'); return; }

        $grievance = $this->grievanceModel->find($id);
        if (!$grievance) { $this->redirectWithError($redirect, 'Grievance not found.'); return; }

        $this->grievanceModel->updateStatus($id, 'closed', 'Closed by admin.');

        $cu = $this->auth->getCurrentUser();
        logAudit('grievance_force_closed', $id, 'grievances', $cu['id']);

        $_SESSION['success'] = 'Grievance has been closed.';
        $this->redirect($redirect);
    }
}
