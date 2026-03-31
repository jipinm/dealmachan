<?php
require_once CORE_PATH  . '/Auth.php';
require_once MODEL_PATH . '/GiftCoupon.php';

class GiftCouponsController extends Controller {

    private $auth;
    private $model;

    private const ALLOWED_TYPES = ['super_admin', 'city_admin'];
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

        $this->model = new GiftCoupon();
    }

    // ─── LIST ─────────────────────────────────────────────────────────────────

    public function index() {
        $filters = [
            'acceptance_status' => $_GET['acceptance_status'] ?? '',
            'customer_id'       => (int)($_GET['customer_id'] ?? 0) ?: '',
            'coupon_id'         => (int)($_GET['coupon_id']   ?? 0) ?: '',
            'date_from'         => $_GET['date_from'] ?? '',
            'date_to'           => $_GET['date_to']   ?? '',
            'search'            => trim($_GET['search'] ?? ''),
        ];

        if (isset($_GET['expiring_soon'])) {
            $filters['expiring_soon'] = true;
        }

        $perPage     = self::PER_PAGE;
        $totalCount  = $this->model->countWithDetails($filters);
        $totalPages  = max(1, (int)ceil($totalCount / $perPage));
        $currentPage = max(1, min((int)($_GET['page'] ?? 1), $totalPages));
        $offset      = ($currentPage - 1) * $perPage;

        $gifts   = $this->model->getAllWithDetails(array_merge($filters, ['limit' => $perPage, 'offset' => $offset]));
        $stats   = $this->model->getStats();

        $this->loadView('gift-coupons/index', [
            'title'       => 'Gift Coupon Management',
            'gifts'       => $gifts,
            'stats'       => $stats,
            'filters'     => $filters,
            'totalCount'  => $totalCount,
            'totalPages'  => $totalPages,
            'currentPage' => $currentPage,
            'perPage'     => $perPage,
        ]);
    }

    // ─── DETAIL ───────────────────────────────────────────────────────────────

    public function detail() {
        $id   = (int)($_GET['id'] ?? 0);
        $gift = $this->model->findWithDetails($id);

        if (!$gift) {
            $_SESSION['error'] = 'Gift coupon not found.';
            $this->redirect('gift-coupons');
            return;
        }

        $this->loadView('gift-coupons/view', [
            'title' => 'Gift Coupon #' . $id,
            'gift'  => $gift,
        ]);
    }

    // ─── ADD FORM ─────────────────────────────────────────────────────────────

    public function add() {
        $this->loadAddForm([], []);
    }

    // ─── SAVE ─────────────────────────────────────────────────────────────────

    public function save() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('gift-coupons');
            return;
        }
        $this->requireCSRF();

        $cu = $this->auth->getCurrentUser();

        $errors = [];
        $couponId           = (int)($_POST['coupon_id'] ?? 0);
        $requiresAcceptance = isset($_POST['requires_acceptance']) ? 1 : 0;
        $filters            = $this->extractRecipientFilters($_POST);

        $coupon = $couponId ? $this->model->getCouponById($couponId) : null;
        if (!$coupon) {
            $errors[] = 'Please select a valid coupon.';
        } elseif (($coupon['status'] ?? '') !== 'active' || ($coupon['approval_status'] ?? '') !== 'approved') {
            $errors[] = 'Selected coupon must be active and approved.';
        }

        $recipientCount = $this->model->countRecipientsByFilters($filters);
        if ($recipientCount <= 0) {
            $errors[] = 'No customers matched the selected filters.';
        }

        if ($errors) {
            $this->loadAddForm($errors, $_POST);
            return;
        }

        $filterCriteria = [
            'card_segment'   => $filters['card_segment'] ?: null,
            'club_ids'       => $filters['club_ids'],
            'profession_ids' => $filters['profession_ids'],
            'birth_month'    => $filters['birth_month'] ?: null,
            'city_id'        => $filters['city_id'] ?: null,
            'area_id'        => $filters['area_id'] ?: null,
            'gender'         => $filters['gender'],
        ];

        $criteriaJson = json_encode($filterCriteria, JSON_UNESCAPED_SLASHES);
        if ($criteriaJson === false) {
            $criteriaJson = null;
        }

        $pdo = Database::getInstance()->getConnection();

        try {
            $pdo->beginTransaction();

            $batchId = $this->model->createGiftBatch([
                'admin_id'            => (int)$cu['admin_id'],
                'coupon_id'           => $couponId,
                'filter_criteria'     => $criteriaJson,
                'total_recipients'    => $recipientCount,
                'requires_acceptance' => $requiresAcceptance,
            ]);

            $recipients = $this->model->getRecipientsByFilters($filters);
            $customerIds = array_map(static function($r) {
                return (int)$r['id'];
            }, $recipients);

            $inserted = $this->model->createBulkGifts(
                (int)$cu['admin_id'],
                $couponId,
                $customerIds,
                $requiresAcceptance,
                $batchId
            );

            if ($inserted > 0) {
                $notificationTitle = $requiresAcceptance
                    ? 'Pending Gift Coupon Approval'
                    : 'Gift Coupon Added to Wallet';
                $notificationMessage = $requiresAcceptance
                    ? 'You have received a gift coupon. Please review and accept or reject it from your wallet gifts tab.'
                    : 'A new gift coupon has been added to your wallet.';

                $this->model->createCustomerNotifications($customerIds, [
                    'notification_type' => 'coupon',
                    'title' => $notificationTitle,
                    'message' => $notificationMessage,
                    'action_url' => '/wallet/gifts',
                ]);
            }

            $pdo->commit();

            logAudit('gift_coupon_batch_created', 'gift_coupon_batches', $batchId, [
                'coupon_id'           => $couponId,
                'total_recipients'    => $recipientCount,
                'inserted_count'      => $inserted,
                'requires_acceptance' => $requiresAcceptance,
                'filter_criteria'     => $filterCriteria,
            ]);

            $_SESSION['success'] = "Gift coupon batch created for {$inserted} customer(s).";
            $this->redirect('gift-coupons');
            return;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Gift coupon batch save failed: ' . $e->getMessage());
            $this->loadAddForm(['Failed to create gift coupon batch. Please try again.'], $_POST);
            return;
        }
    }

    // ─── AJAX PREVIEW ─────────────────────────────────────────────────────────

    public function preview() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'error' => 'Method not allowed'], 405);
            return;
        }

        if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
            $this->json(['success' => false, 'error' => 'Invalid CSRF token'], 403);
            return;
        }

        $filters = $this->extractRecipientFilters($_POST);
        $count   = $this->model->countRecipientsByFilters($filters);
        $sample  = $this->model->getRecipientsByFilters($filters, 10);

        $this->json([
            'success' => true,
            'count'   => $count,
            'sample'  => array_map(static function($r) {
                return [
                    'id'    => (int)$r['id'],
                    'name'  => $r['name'],
                    'email' => $r['email'],
                    'phone' => $r['phone'],
                ];
            }, $sample),
        ]);
    }

    // ─── REVOKE ───────────────────────────────────────────────────────────────

    public function revoke() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('gift-coupons');
            return;
        }
        $this->requireCSRF();

        $id   = (int)($_POST['id'] ?? 0);
        $gift = $this->model->findWithDetails($id);

        if (!$gift) {
            $_SESSION['error'] = 'Gift coupon not found.';
            $this->redirect('gift-coupons');
            return;
        }

        $ok = $this->model->revoke($id);

        if ($ok) {
            logAudit('gift_coupon_revoked', 'gift_coupons', $id, null, $gift);
            $_SESSION['success'] = 'Gift coupon revoked.';
        } else {
            $_SESSION['error'] = 'Cannot revoke &mdash; coupon has already been accepted.';
        }

        $redirect = $_POST['redirect'] ?? 'gift-coupons';
        $this->redirect($redirect);
    }

    private function loadAddForm($errors = [], $old = []) {
        $this->loadView('gift-coupons/add', [
            'title'       => 'Gift Coupon Bulk Creation',
            'coupons'     => $this->model->getActiveCoupons(),
            'professions' => $this->model->getActiveProfessions(),
            'cities'      => $this->model->getActiveCities(),
            'areas'       => $this->model->getActiveAreas(),
            'clubs'       => $this->model->getClubSubClassifications(),
            'errors'      => $errors,
            'old'         => $old,
        ]);
    }

    private function extractRecipientFilters($src) {
        $cardSegment = strtolower(trim((string)($src['card_segment'] ?? '')));
        if (!in_array($cardSegment, ['silver', 'gold', 'platinum', 'diamond'], true)) {
            $cardSegment = '';
        }

        $gender = strtolower(trim((string)($src['gender'] ?? 'both')));
        if (!in_array($gender, ['male', 'female', 'both'], true)) {
            $gender = 'both';
        }

        $birthMonth = (int)($src['birth_month'] ?? 0);
        if ($birthMonth < 1 || $birthMonth > 12) {
            $birthMonth = 0;
        }

        return [
            'card_segment'   => $cardSegment,
            'club_ids'       => array_values(array_filter(array_map('intval', (array)($src['club_ids'] ?? [])))),
            'profession_ids' => array_values(array_filter(array_map('intval', (array)($src['profession_ids'] ?? [])))),
            'birth_month'    => $birthMonth,
            'city_id'        => (int)($src['city_id'] ?? 0),
            'area_id'        => (int)($src['area_id'] ?? 0),
            'gender'         => $gender,
        ];
    }
}
