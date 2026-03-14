<?php
require_once CORE_PATH  . '/Auth.php';
require_once MODEL_PATH . '/Card.php';
require_once MODEL_PATH . '/Merchant.php';

class CardsController extends Controller {

    private $auth;
    private $cardModel;

    private const ALLOWED_TYPES = ['super_admin', 'city_admin', 'sales_admin'];
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

        $this->cardModel = new Card();
    }

    // ─── LIST ─────────────────────────────────────────────────────────────────

    public function index() {
        $filters = [
            'status'       => $_GET['status']        ?? '',
            'card_variant' => $_GET['card_variant']   ?? '',
            'is_preprinted'=> $_GET['is_preprinted']  ?? '',
            'assigned_to'  => $_GET['assigned_to']    ?? '',
            'search'       => trim($_GET['search']    ?? ''),
        ];

        $perPage     = self::PER_PAGE;
        $totalCount  = $this->cardModel->countWithDetails($filters);
        $totalPages  = max(1, (int)ceil($totalCount / $perPage));
        $currentPage = max(1, min((int)($_GET['page'] ?? 1), $totalPages));
        $offset      = ($currentPage - 1) * $perPage;

        $cards   = $this->cardModel->getAllWithDetails(array_merge($filters, ['limit' => $perPage, 'offset' => $offset]));
        $stats   = $this->cardModel->getStats();
        $variants = $this->cardModel->getVariants();

        $this->loadView('cards/index', [
            'title'        => 'Card Management',
            'cards'        => $cards,
            'stats'        => $stats,
            'variants'     => $variants,
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
        if (!$id) { $this->redirectWithError('cards', 'Invalid card ID.'); return; }

        $card = $this->cardModel->findWithDetails($id);
        if (!$card) { $this->redirectWithError('cards', 'Card not found.'); return; }

        // Fetch activation / assignment audit trail for this card
        // Note: logAudit() is called as logAudit(action, $id, 'card', adminId)
        // so rows are stored with table_name=$id and record_id='card' (or vice-versa)
        try {
            $db   = \Database::getInstance()->getConnection();
            $stmt = $db->prepare(
                "SELECT al.*, u.name AS admin_name
                 FROM audit_logs al
                 LEFT JOIN users u ON al.user_id = u.id
                 WHERE (al.table_name = :id_str AND al.record_id IN ('card','cards'))
                    OR (al.table_name IN ('card','cards') AND al.record_id = :id_str2)
                 ORDER BY al.created_at DESC
                 LIMIT 50"
            );
            $stmt->execute([':id_str' => (string)$id, ':id_str2' => (string)$id]);
            $auditLogs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $auditLogs = [];
        }

        $this->loadView('cards/view', [
            'title'        => 'Card &mdash; ' . escape($card['card_number']),
            'card'         => $card,
            'audit_logs'   => $auditLogs,
            'current_user' => $this->auth->getCurrentUser(),
        ]);
    }

    // ─── TRACK (search alias) ─────────────────────────────────────────────────

    public function track() {
        $number = trim($_GET['card_number'] ?? '');
        if ($number) {
            $card = $this->cardModel->findByNumber($number);
            if ($card) {
                $this->redirect("cards/detail?id={$card['id']}");
                return;
            }
            $_SESSION['error'] = "Card number '{$number}' not found.";
        }
        $this->loadView('cards/track', [
            'title'        => 'Track Card',
            'card_number'  => $number,
            'current_user' => $this->auth->getCurrentUser(),
        ]);
    }

    private const ALLOWED_IMAGE_EXT = ['jpg', 'jpeg', 'png', 'webp'];
    private const MAX_CARD_IMAGE    = 2 * 1024 * 1024; // 2 MB

    // ─── GENERATE ─────────────────────────────────────────────────────────────

    public function generate() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCSRF();

            $type       = $_POST['generate_type'] ?? 'single';
            $preprinted = isset($_POST['is_preprinted']) ? 1 : 0;
            $paramsJson = trim($_POST['parameters_json'] ?? '');
            $cu         = $this->auth->getCurrentUser();

            // Resolve variant from config_id (if provided) or fall back to card_variant
            $configId = (int)($_POST['config_id'] ?? 0);
            $variant  = sanitize($_POST['card_variant'] ?? 'standard');
            if ($configId) {
                require_once MODEL_PATH . '/CardConfiguration.php';
                $cfgModel = new CardConfiguration();
                $cfgRow   = $cfgModel->find($configId);
                if (!$cfgRow) {
                    $_SESSION['error'] = 'Selected card configuration not found.';
                    $this->redirect('cards/generate');
                    return;
                }
                $variant = $cfgRow['classification']; // silver/gold/platinum/diamond
            }

            // Validate JSON if provided
            if ($paramsJson && !json_decode($paramsJson)) {
                $_SESSION['error'] = 'Parameters JSON is not valid JSON.';
                $this->redirect('cards/generate');
                return;
            }

            $genData = [
                'card_variant'         => $variant,
                'is_preprinted'        => $preprinted,
                'parameters_json'      => $paramsJson ?: null,
                'card_configuration_id'=> $configId ?: null,
            ];

            // Handle card image uploads (single preprinted cards only)
            if ($type === 'single' && $preprinted) {
                foreach (['card_image_front', 'card_image_back'] as $field) {
                    if (!empty($_FILES[$field]['name'])) {
                        $file = $_FILES[$field];
                        if ($file['error'] !== UPLOAD_ERR_OK) {
                            $_SESSION['error'] = "Upload error for {$field}.";
                            $this->redirect('cards/generate');
                            return;
                        }
                        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        if (!in_array($ext, self::ALLOWED_IMAGE_EXT)) {
                            $_SESSION['error'] = 'Card images must be JPG, PNG, or WebP.';
                            $this->redirect('cards/generate');
                            return;
                        }
                        if ($file['size'] > self::MAX_CARD_IMAGE) {
                            $_SESSION['error'] = 'Each card image must be under 2 MB.';
                            $this->redirect('cards/generate');
                            return;
                        }
                        if (!is_dir(API_CARDS_UPLOAD_DIR)) {
                            mkdir(API_CARDS_UPLOAD_DIR, 0755, true);
                        }
                        $filename = 'card_' . $field . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                        if (!move_uploaded_file($file['tmp_name'], API_CARDS_UPLOAD_DIR . $filename)) {
                            $_SESSION['error'] = "Failed to upload {$field}.";
                            $this->redirect('cards/generate');
                            return;
                        }
                        $genData[$field] = 'uploads/cards/' . $filename;
                    }
                }
            }

            if ($type === 'bulk') {
                $count = max(1, min(200, (int)($_POST['bulk_count'] ?? 10)));
                $n = $this->cardModel->generateCards($genData, $count);
                logAudit('cards_bulk_generated', 'card', $n);
                $_SESSION['success'] = "{$n} cards generated successfully.";
            } else {
                $number = strtoupper(trim($_POST['card_number'] ?? ''));
                if ($number && $this->cardModel->cardNumberExists($number)) {
                    $_SESSION['error'] = "Card number '{$number}' already exists.";
                    $this->redirect('cards/generate');
                    return;
                }
                $genData['card_number'] = $number;
                $n = $this->cardModel->generateCards($genData, 1);
                logAudit('card_generated', 'card', $n);
                $_SESSION['success'] = '1 card generated successfully.';
            }

            $this->redirect('cards');
            return;
        }

        require_once MODEL_PATH . '/CardConfiguration.php';
        $cfgModel      = new CardConfiguration();
        $configurations = $cfgModel->getAll(['status' => 'active']);

        $this->loadView('cards/generate', [
            'title'          => 'Generate Cards',
            'variants'       => $this->cardModel->getVariants(),
            'configurations' => $configurations,
            'current_user'   => $this->auth->getCurrentUser(),
        ]);
    }

    // ─── ASSIGN ───────────────────────────────────────────────────────────────

    public function assign() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCSRF();

            $cardId      = (int)($_POST['card_id']      ?? 0);
            $customerId  = (int)($_POST['customer_id']  ?? 0);
            $merchantId  = (int)($_POST['merchant_id']  ?? 0);
            $cu          = $this->auth->getCurrentUser();

            if (!$cardId) {
                $_SESSION['error'] = 'Please select a card.';
                $this->redirect('cards/assign');
                return;
            }

            $card = $this->cardModel->findWithDetails($cardId);
            if (!$card || $card['status'] !== 'available') {
                $_SESSION['error'] = 'Selected card is no longer available.';
                $this->redirect('cards/assign');
                return;
            }

            if ($customerId) {
                $this->cardModel->assignToCustomer($cardId, $customerId);
                logAudit('card_assigned_customer', 'card', $cardId);
                $_SESSION['success'] = "Card {$card['card_number']} assigned to customer.";
            } elseif ($merchantId) {
                $this->cardModel->assignToMerchant($cardId, $merchantId);
                logAudit('card_assigned_merchant', 'card', $cardId);
                $_SESSION['success'] = "Card {$card['card_number']} assigned to merchant.";
            } else {
                $_SESSION['error'] = 'Please select a customer or merchant to assign the card to.';
                $this->redirect('cards/assign');
                return;
            }

            $this->redirect("cards/detail?id={$cardId}");
            return;
        }

        // AJAX: customer search
        if (!empty($_GET['customer_search'])) {
            $this->customerSearchJson($_GET['customer_search']);
            return;
        }

        $merchantModel = new Merchant();
        $this->loadView('cards/assign', [
            'title'         => 'Assign Card',
            'available'     => $this->cardModel->getAvailable(200),
            'merchants'     => $merchantModel->getAllWithDetails(['limit' => 200]),
            'current_user'  => $this->auth->getCurrentUser(),
        ]);
    }

    // ─── ACTIVATE ─────────────────────────────────────────────────────────────

    public function activate() {
        $this->requireCSRF();
        $id       = (int)($_POST['id'] ?? 0);
        $redirect = sanitize($_POST['redirect'] ?? "cards/detail?id={$id}");
        if ($id) {
            $this->cardModel->activate($id);
            $cu = $this->auth->getCurrentUser();
            logAudit('card_activated', 'card', $id);
            $_SESSION['success'] = 'Card activated.';
        }
        $this->redirect($redirect);
    }

    // ─── BLOCK / UNBLOCK ──────────────────────────────────────────────────────

    public function block() {
        $this->requireCSRF();
        $id       = (int)($_POST['id'] ?? 0);
        $redirect = sanitize($_POST['redirect'] ?? 'cards');
        if ($id) $this->cardModel->toggleBlock($id);
        $this->redirect($redirect);
    }

    // ─── UNASSIGN ─────────────────────────────────────────────────────────────

    public function unassign() {
        $this->requireCSRF();
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { $this->redirectWithError('cards', 'Invalid card ID.'); return; }
        $this->cardModel->unassign($id);
        $cu = $this->auth->getCurrentUser();
        logAudit('card_unassigned', 'card', $id);
        $_SESSION['success'] = 'Card unassigned and reset to available.';
        $this->redirect("cards/detail?id={$id}");
    }

    // ─── DELETE ───────────────────────────────────────────────────────────────

    public function delete() {
        $this->requireCSRF();
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { $this->redirectWithError('cards', 'Invalid card ID.'); return; }

        $card = $this->cardModel->findWithDetails($id);
        if (!$card) { $this->redirectWithError('cards', 'Card not found.'); return; }

        $deleted = $this->cardModel->deleteCard($id);
        if ($deleted) {
            $cu = $this->auth->getCurrentUser();
            logAudit('card_deleted', 'card', $id);
            $_SESSION['success'] = "Card {$card['card_number']} deleted.";
        } else {
            $_SESSION['error'] = 'Only available (unassigned) cards can be deleted.';
        }
        $this->redirect('cards');
    }

    // ─── AJAX ─────────────────────────────────────────────────────────────────

    private function customerSearchJson(string $q) {
        $db   = Database::getInstance()->getConnection();
        $like = '%' . $q . '%';
        $stmt = $db->prepare(
            "SELECT c.id, c.name AS full_name, u.phone, u.email
             FROM customers c
             JOIN users u ON c.user_id = u.id
             WHERE (c.name LIKE ? OR u.phone LIKE ? OR u.email LIKE ?)
               AND u.status = 'active'
             LIMIT 20"
        );
        $stmt->execute([$like, $like, $like]);
        $this->json($stmt->fetchAll());
    }
}
