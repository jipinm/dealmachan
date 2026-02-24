<?php

require_once CORE_PATH . '/Auth.php';
require_once MODEL_PATH . '/Message.php';

class NotificationsController extends Controller {

    private $auth;
    private $model;
    private const PER_PAGE = 25;

    public function __construct() {
        $this->auth  = new Auth();
        $this->model = new Message();

        if (!$this->auth->isLoggedIn()) {
            $this->redirect('auth/login');
            return;
        }
    }

    // GET /notifications
    public function index(): void {
        $cu      = $this->auth->getCurrentUser();
        $adminId = (int)$cu['admin_id'];
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $offset  = ($page - 1) * self::PER_PAGE;

        $notifications = $this->model->getNotifications($adminId, 'admin', self::PER_PAGE, $offset);
        $total         = $this->model->countNotifications($adminId, 'admin');
        $unread        = $this->model->countUnreadNotifications($adminId, 'admin');
        $stats         = $this->model->getStats($adminId);
        $pages         = (int)ceil($total / self::PER_PAGE);

        $this->loadView('notifications/index', compact('cu', 'notifications', 'total', 'unread', 'stats', 'page', 'pages'));
    }

    // POST /notifications/mark-read  →  markRead()
    public function markRead(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->redirect('notifications'); return; }
        $id = (int)($_POST['id'] ?? 0);
        if ($id) $this->model->markNotificationRead($id);
        $this->redirect($_POST['redirect'] ?? 'notifications');
    }

    // POST /notifications/mark-all  →  markAll()
    public function markAll(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->redirect('notifications'); return; }
        $cu      = $this->auth->getCurrentUser();
        $adminId = (int)$cu['admin_id'];
        $this->model->markAllNotificationsRead($adminId, 'admin');
        $this->redirect('notifications?success=All+notifications+marked+as+read');
    }

    // GET|POST /notifications/broadcast  (super_admin / city_admin only)
    public function broadcast(): void {
        $cu      = $this->auth->getCurrentUser();
        $adminId = (int)$cu['admin_id'];

        if (!in_array($cu['admin_type'] ?? '', ['super_admin', 'city_admin'])) {
            $this->redirect('notifications?error=Access+denied');
            return;
        }

        $admins          = $this->model->getAdminList();
        $totalCustomers  = $this->model->countActiveCustomers();
        $totalMerchants  = $this->model->countApprovedMerchants();
        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title   = trim($_POST['title'] ?? '');
            $message = trim($_POST['message'] ?? '');
            $validTypes = ['info','success','warning','error','promotion','coupon','flash_discount','contest','referral','order'];
            $type    = in_array($_POST['notification_type'] ?? '', $validTypes) ? $_POST['notification_type'] : 'info';
            $url     = trim($_POST['action_url'] ?? '');
            $target  = $_POST['target'] ?? 'all_admins';

            if (!$title)   $errors[] = 'Title is required.';
            if (!$message) $errors[] = 'Message is required.';

            if (!$errors) {
                $payload = [
                    'notification_type' => $type,
                    'title'             => $title,
                    'message'           => $message,
                    'action_url'        => $url ?: null,
                ];

                $count = 0;
                switch ($target) {
                    case 'all_admins':
                        $count = $this->model->broadcastToAdmins($payload);
                        break;
                    case 'specific_admins':
                        $ids = array_map('intval', (array)($_POST['admin_ids'] ?? []));
                        $count = $this->model->broadcastToAdmins($payload, $ids);
                        break;
                    case 'all_customers':
                        $count = $this->model->broadcastToCustomers($payload);
                        break;
                    case 'all_merchants':
                        $count = $this->model->broadcastToMerchants($payload);
                        break;
                    case 'all_users':
                        $count  = $this->model->broadcastToAdmins($payload);
                        $count += $this->model->broadcastToCustomers($payload);
                        $count += $this->model->broadcastToMerchants($payload);
                        break;
                }
                $label = str_replace('_', ' ', $target);
                $this->redirect("notifications?success=Sent+{$count}+notifications+to+{$label}");
                return;
            }
        }

        $stats = $this->model->getStats($adminId);
        $this->loadView('notifications/broadcast', compact('cu', 'admins', 'errors', 'stats', 'totalCustomers', 'totalMerchants'));
    }
}
