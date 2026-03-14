<?php
require_once CORE_PATH  . '/Auth.php';
require_once MODEL_PATH . '/AuditLog.php';

class AuditLogsController extends Controller {

    private $auth;
    private $logModel;

    private const ALLOWED_TYPES = ['super_admin'];
    private const PER_PAGE      = 50;

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

        $this->logModel = new AuditLog();
    }

    // ─── LIST ─────────────────────────────────────────────────────────────────

    public function index() {
        $filters = [
            'user_type'  => $_GET['user_type']  ?? '',
            'action'     => $_GET['action']     ?? '',
            'table_name' => $_GET['table_name'] ?? '',
            'date_from'  => $_GET['date_from']  ?? '',
            'date_to'    => $_GET['date_to']    ?? '',
            'ip_address' => trim($_GET['ip_address'] ?? ''),
        ];
        if (!empty($_GET['user_id']))   $filters['user_id']   = (int)$_GET['user_id'];
        if (!empty($_GET['record_id'])) $filters['record_id'] = (int)$_GET['record_id'];

        $perPage     = self::PER_PAGE;
        $totalCount  = $this->logModel->countWithDetails($filters);
        $totalPages  = max(1, (int)ceil($totalCount / $perPage));
        $currentPage = max(1, min((int)($_GET['page'] ?? 1), $totalPages));
        $offset      = ($currentPage - 1) * $perPage;

        $logs    = $this->logModel->getAllWithDetails(array_merge($filters, ['limit' => $perPage, 'offset' => $offset]));
        $stats   = $this->logModel->getStats();
        $actions = $this->logModel->getDistinctActions();
        $tables  = $this->logModel->getDistinctTables();

        $this->loadView('audit-logs/index', [
            'title'        => 'Audit Log Viewer',
            'logs'         => $logs,
            'stats'        => $stats,
            'actions'      => $actions,
            'tables'       => $tables,
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
        if (!$id) { $this->redirectWithError('audit-logs', 'Invalid log ID.'); return; }

        $log = $this->logModel->findWithDetails($id);
        if (!$log) { $this->redirectWithError('audit-logs', 'Log entry not found.'); return; }

        $this->loadView('audit-logs/view', [
            'title'        => 'Audit Log #' . $log['id'],
            'log'          => $log,
            'current_user' => $this->auth->getCurrentUser(),
        ]);
    }

    // ─── CSV EXPORT ───────────────────────────────────────────────────────────

    public function export() {
        $filters = [
            'user_type'  => $_GET['user_type']  ?? '',
            'action'     => $_GET['action']     ?? '',
            'table_name' => $_GET['table_name'] ?? '',
            'date_from'  => $_GET['date_from']  ?? '',
            'date_to'    => $_GET['date_to']    ?? '',
        ];

        $rows     = $this->logModel->getForExport($filters);
        $filename = 'audit_log_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Date/Time', 'User Type', 'User ID', 'Actor Name', 'Action', 'Table', 'Record ID', 'IP Address']);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['id'],
                date('d/m/Y H:i:s', strtotime($r['created_at'])),
                $r['user_type'] ?? '',
                $r['user_id']   ?? '',
                $r['actor_name'] ?? '&mdash;',
                $r['action'],
                $r['table_name'] ?? '',
                $r['record_id']  ?? '',
                $r['ip_address'] ?? '',
            ]);
        }
        fclose($out);
        exit;
    }
}
