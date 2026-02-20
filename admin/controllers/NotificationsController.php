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

        $admins = $this->model->getAdminList();
        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title   = trim($_POST['title'] ?? '');
            $message = trim($_POST['message'] ?? '');
            $type    = in_array($_POST['notif_type'] ?? '', ['info','success','warning','error'])
                       ? $_POST['notif_type'] : 'info';
            $url     = trim($_POST['action_url'] ?? '');
            $targets = $_POST['target_admins'] ?? 'all';

            if (!$title)   $errors[] = 'Title is required.';
            if (!$message) $errors[] = 'Message is required.';

            if (!$errors) {
                $ids = ($targets === 'all') ? [] : array_map('intval', (array)($_POST['admin_ids'] ?? []));
                $count = $this->model->broadcastToAdmins([
                    'notification_type' => $type,
                    'title'             => $title,
                    'message'           => $message,
                    'action_url'        => $url ?: null,
                ], $ids);
                $this->redirect("notifications?success=Notification+sent+to+{$count}+admins");
                return;
            }
        }

        $stats = $this->model->getStats($adminId);
        $this->loadView('notifications/broadcast', compact('cu', 'admins', 'errors', 'stats'));
    }
}
