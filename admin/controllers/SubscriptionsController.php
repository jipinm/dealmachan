<?php
require_once CORE_PATH  . '/Auth.php';
require_once MODEL_PATH . '/Subscription.php';
require_once MODEL_PATH . '/Merchant.php';

class SubscriptionsController extends Controller {

    private $auth;
    private $subscriptionModel;

    private const ALLOWED_TYPES = ['super_admin'];
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

        $this->subscriptionModel = new Subscription();
    }

    // ─── LIST ─────────────────────────────────────────────────────────────────

    public function index() {
        $filters = [
            'user_type'    => $_GET['user_type']    ?? '',
            'status'       => $_GET['status']       ?? '',
            'plan_type'    => $_GET['plan_type']     ?? '',
            'search'       => trim($_GET['search']  ?? ''),
        ];

        // expiring-soon shortcut
        if (isset($_GET['expiring_soon'])) {
            $filters['expiry_after']  = date('Y-m-d');
            $filters['expiry_before'] = date('Y-m-d', strtotime('+30 days'));
            $filters['status']        = 'active';
        }

        $perPage     = self::PER_PAGE;
        $totalCount  = $this->subscriptionModel->countWithDetails($filters);
        $totalPages  = max(1, (int)ceil($totalCount / $perPage));
        $currentPage = max(1, min((int)($_GET['page'] ?? 1), $totalPages));
        $offset      = ($currentPage - 1) * $perPage;

        $subs  = $this->subscriptionModel->getAllWithDetails(array_merge($filters, ['limit' => $perPage, 'offset' => $offset]));
        $stats = $this->subscriptionModel->getStats();

        $this->loadView('subscriptions/index', [
            'title'        => 'Subscription Management',
            'subscriptions'=> $subs,
            'stats'        => $stats,
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
        if (!$id) { $this->redirectWithError('subscriptions', 'Invalid subscription ID.'); return; }

        $sub = $this->subscriptionModel->findWithDetails($id);
        if (!$sub) { $this->redirectWithError('subscriptions', 'Subscription not found.'); return; }

        // History: all subscriptions for this user
        $history = $this->subscriptionModel->getByUser($sub['user_id'], $sub['user_type']);

        $this->loadView('subscriptions/view', [
            'title'        => 'Subscription #' . $sub['id'] . ' — ' . escape($sub['display_name']),
            'sub'          => $sub,
            'history'      => $history,
            'current_user' => $this->auth->getCurrentUser(),
        ]);
    }

    // ─── ADD ──────────────────────────────────────────────────────────────────

    public function add() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleSave(null);
            return;
        }

        $this->loadView('subscriptions/add', [
            'title'        => 'Create Subscription',
            'current_user' => $this->auth->getCurrentUser(),
        ]);
    }

    // ─── EDIT ─────────────────────────────────────────────────────────────────

    public function edit() {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { $this->redirectWithError('subscriptions', 'Invalid subscription ID.'); return; }

        $sub = $this->subscriptionModel->findWithDetails($id);
        if (!$sub) { $this->redirectWithError('subscriptions', 'Subscription not found.'); return; }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleSave($id);
            return;
        }

        $this->loadView('subscriptions/edit', [
            'title'        => 'Edit Subscription #' . $id,
            'sub'          => $sub,
            'current_user' => $this->auth->getCurrentUser(),
        ]);
    }

    // ─── SHARED SAVE ──────────────────────────────────────────────────────────

    private function handleSave($subId) {
        $this->requireCSRF();
        $cu = $this->auth->getCurrentUser();

        // Validate inputs
        $userId     = (int)($_POST['user_id']     ?? 0);
        $userType   = trim($_POST['user_type']     ?? '');
        $planType   = trim($_POST['plan_type']     ?? '');
        $startDate  = trim($_POST['start_date']    ?? '');
        $expiryDate = trim($_POST['expiry_date']   ?? '');
        $status     = trim($_POST['status']        ?? 'active');
        $paymentAmt = $_POST['payment_amount'] !== '' ? (float)$_POST['payment_amount'] : null;
        $paymentMth = trim($_POST['payment_method'] ?? '') ?: null;
        $autoRenew  = isset($_POST['auto_renew']) ? 1 : 0;

        $redirect = $subId ? "subscriptions/detail?id={$subId}" : 'subscriptions/add';

        if (!$userId) {
            $this->redirectWithError($redirect, 'User ID is required.');
            return;
        }
        if (!in_array($userType, ['merchant', 'customer'])) {
            $this->redirectWithError($redirect, 'Invalid user type.');
            return;
        }
        if (!$planType) {
            $this->redirectWithError($redirect, 'Plan type is required.');
            return;
        }
        if (!$startDate || !$expiryDate) {
            $this->redirectWithError($redirect, 'Start and expiry dates are required.');
            return;
        }
        if ($expiryDate <= $startDate) {
            $this->redirectWithError($redirect, 'Expiry date must be after start date.');
            return;
        }

        $data = compact('user_id', 'user_type', 'plan_type', 'start_date', 'expiry_date',
                        'status', 'auto_renew');
        $data['user_id']          = $userId;
        $data['payment_amount']   = $paymentAmt;
        $data['payment_method']   = $paymentMth;

        if ($subId) {
            $this->subscriptionModel->updateSubscription($subId, $data);
            logAudit('subscription_updated', $subId, 'subscriptions', $cu['id']);
            $_SESSION['success'] = 'Subscription updated successfully.';
            $this->redirect("subscriptions/detail?id={$subId}");
        } else {
            $newId = $this->subscriptionModel->createSubscription($data);
            if ($newId) {
                logAudit('subscription_created', $newId, 'subscriptions', $cu['id']);
                $_SESSION['success'] = 'Subscription created successfully.';
                $this->redirect("subscriptions/detail?id={$newId}");
            } else {
                $this->redirectWithError('subscriptions/add', 'Failed to create subscription. Please try again.');
            }
        }
    }

    // ─── CANCEL ───────────────────────────────────────────────────────────────

    public function cancel() {
        $this->requireCSRF();
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { $this->redirectWithError('subscriptions', 'Invalid ID.'); return; }

        $this->subscriptionModel->updateSubscription($id, ['status' => 'cancelled']);
        $cu = $this->auth->getCurrentUser();
        logAudit('subscription_cancelled', $id, 'subscriptions', $cu['id']);
        $_SESSION['success'] = 'Subscription cancelled.';
        $this->redirect("subscriptions/detail?id={$id}");
    }

    // ─── EXTEND ───────────────────────────────────────────────────────────────

    public function extend() {
        $this->requireCSRF();
        $id         = (int)($_POST['id'] ?? 0);
        $newExpiry  = trim($_POST['new_expiry'] ?? '');

        if (!$id || !$newExpiry) {
            $this->redirectWithError('subscriptions', 'Invalid parameters.');
            return;
        }

        $sub = $this->subscriptionModel->findWithDetails($id);
        if (!$sub || $newExpiry <= $sub['expiry_date']) {
            $this->redirectWithError("subscriptions/detail?id={$id}", 'New expiry must be after current expiry.');
            return;
        }

        $this->subscriptionModel->updateSubscription($id, [
            'expiry_date' => $newExpiry,
            'status'      => 'active',
        ]);

        $cu = $this->auth->getCurrentUser();
        logAudit('subscription_extended', $id, 'subscriptions', $cu['id']);
        $_SESSION['success'] = 'Subscription extended to ' . date('d M Y', strtotime($newExpiry)) . '.';
        $this->redirect("subscriptions/detail?id={$id}");
    }
}
