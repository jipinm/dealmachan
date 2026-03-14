<?php
require_once CORE_PATH  . '/Auth.php';
require_once MODEL_PATH . '/CardConfiguration.php';

class CardConfigurationsController extends Controller {

    private Auth              $auth;
    private CardConfiguration $configModel;

    private const ALLOWED_TYPES = ['super_admin', 'city_admin'];
    private const PER_PAGE      = 20;
    private const ALLOWED_EXT   = ['jpg', 'jpeg', 'png', 'webp'];
    private const MAX_IMAGE     = 2 * 1024 * 1024; // 2 MB

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

        $this->configModel = new CardConfiguration();
    }

    // ─── LIST ─────────────────────────────────────────────────────────────────

    public function index() {
        $filters = [
            'status'         => $_GET['status']         ?? '',
            'classification' => $_GET['classification'] ?? '',
        ];

        $totalCount  = $this->configModel->countAll($filters);
        $totalPages  = max(1, (int)ceil($totalCount / self::PER_PAGE));
        $currentPage = max(1, min((int)($_GET['page'] ?? 1), $totalPages));
        $offset      = ($currentPage - 1) * self::PER_PAGE;

        $configs = $this->configModel->getAll(array_merge($filters, [
            'limit'  => self::PER_PAGE,
            'offset' => $offset,
        ]));

        $this->loadView('card-configurations/index', [
            'title'        => 'Card Configurations',
            'configs'      => $configs,
            'stats'        => $this->configModel->getStats(),
            'filters'      => $filters,
            'currentPage'  => $currentPage,
            'totalPages'   => $totalPages,
            'totalCount'   => $totalCount,
            'perPage'      => self::PER_PAGE,
            'current_user' => $this->auth->getCurrentUser(),
        ]);
    }

    // ─── DETAIL ───────────────────────────────────────────────────────────────

    public function view() {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { $this->redirectWithError('card-configurations', 'Invalid configuration ID.'); return; }

        $config = $this->configModel->findWithDetails($id);
        if (!$config) { $this->redirectWithError('card-configurations', 'Configuration not found.'); return; }

        $this->loadView('card-configurations/view', [
            'title'        => 'Card Config &mdash; ' . escape($config['name']),
            'config'       => $config,
            'current_user' => $this->auth->getCurrentUser(),
        ]);
    }

    // ─── ADD ──────────────────────────────────────────────────────────────────

    public function add() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleSave(null);
            return;
        }

        require_once MODEL_PATH . '/City.php';
        $cityModel = new City();

        $this->loadView('card-configurations/add', [
            'title'              => 'Create Card Configuration',
            'subClassifications' => $this->configModel->getAllSubClassifications(),
            'cities'             => $cityModel->getActive(),
            'current_user'       => $this->auth->getCurrentUser(),
        ]);
    }

    // ─── EDIT ─────────────────────────────────────────────────────────────────

    public function edit() {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { $this->redirectWithError('card-configurations', 'Invalid configuration ID.'); return; }

        $config = $this->configModel->findWithDetails($id);
        if (!$config) { $this->redirectWithError('card-configurations', 'Configuration not found.'); return; }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleSave($id);
            return;
        }

        require_once MODEL_PATH . '/City.php';
        $cityModel = new City();

        $this->loadView('card-configurations/edit', [
            'title'              => 'Edit Card Config &mdash; ' . escape($config['name']),
            'config'             => $config,
            'subClassifications' => $this->configModel->getAllSubClassifications(),
            'cities'             => $cityModel->getActive(),
            'current_user'       => $this->auth->getCurrentUser(),
        ]);
    }

    // ─── DELETE ───────────────────────────────────────────────────────────────

    public function delete() {
        $this->requireCSRF();
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { $this->redirectWithError('card-configurations', 'Invalid configuration ID.'); return; }

        if ($this->configModel->isLinkedToCards($id)) {
            $_SESSION['error'] = 'Cannot delete: cards are already linked to this configuration.';
            $this->redirect("card-configurations/view?id={$id}");
            return;
        }

        $this->db()->prepare("DELETE FROM card_configurations WHERE id = ?")->execute([$id]);
        $cu = $this->auth->getCurrentUser();
        logAudit('card_config_deleted', 'card_configurations', $id);
        $_SESSION['success'] = 'Card configuration deleted.';
        $this->redirect('card-configurations');
    }

    // ─── SAVE HANDLER ─────────────────────────────────────────────────────────

    private function handleSave(?int $configId): void {
        $redirect = $configId
            ? "card-configurations/edit?id={$configId}"
            : 'card-configurations/add';

        $cu   = $this->auth->getCurrentUser();
        $name = trim($_POST['name'] ?? '');
        if (!$name) { $_SESSION['error'] = 'Name is required.'; $this->redirect($redirect); return; }

        $classification = $_POST['classification'] ?? 'silver';
        if (!in_array($classification, ['silver', 'gold', 'platinum', 'diamond'])) {
            $_SESSION['error'] = 'Invalid classification.';
            $this->redirect($redirect); return;
        }

        $validityDays = (int)($_POST['validity_days'] ?? 360);
        if ($validityDays < 1) { $_SESSION['error'] = 'Validity days must be at least 1.'; $this->redirect($redirect); return; }

        $data = [
            'name'                   => sanitize($name),
            'classification'         => $classification,
            'features_html'          => $_POST['features_html'] ?? null,
            'price'                  => max(0, (float)($_POST['price'] ?? 0)),
            'monthly_maximum'        => $_POST['monthly_maximum']  !== '' ? (int)$_POST['monthly_maximum']  : null,
            'max_live_coupons'       => $_POST['max_live_coupons'] !== '' ? (int)$_POST['max_live_coupons'] : null,
            'coupon_authorization'   => isset($_POST['coupon_authorization']) ? 1 : 0,
            'is_publicly_selectable' => isset($_POST['is_publicly_selectable']) ? 1 : 0,
            'validity_days'          => $validityDays,
            'status'                 => in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active',
        ];

        if (!$configId) {
            $data['created_by_admin_id'] = $cu['admin_id'];
        }

        // Handle image uploads
        foreach (['card_image_front', 'card_image_back'] as $field) {
            if (!empty($_FILES[$field]['name'])) {
                $file = $_FILES[$field];
                $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, self::ALLOWED_EXT)) {
                    $_SESSION['error'] = 'Card images must be JPG, PNG, or WebP.';
                    $this->redirect($redirect); return;
                }
                if ($file['size'] > self::MAX_IMAGE) {
                    $_SESSION['error'] = 'Each card image must be under 2 MB.';
                    $this->redirect($redirect); return;
                }
                if (!is_dir(API_CARDS_UPLOAD_DIR)) { mkdir(API_CARDS_UPLOAD_DIR, 0755, true); }
                $filename = 'cc_' . $field . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (!move_uploaded_file($file['tmp_name'], API_CARDS_UPLOAD_DIR . $filename)) {
                    $_SESSION['error'] = 'Failed to upload ' . $field . '.';
                    $this->redirect($redirect); return;
                }
                $data[$field] = 'uploads/cards/' . $filename;
            }
        }

        if ($configId) {
            $this->configModel->update($configId, $data);
            $targetId = $configId;
            logAudit('card_config_updated', 'card_configurations', $targetId);
            $_SESSION['success'] = "Card configuration '{$name}' updated.";
        } else {
            $targetId = $this->configModel->create($data);
            logAudit('card_config_created', 'card_configurations', $targetId);
            $_SESSION['success'] = "Card configuration '{$name}' created.";
        }

        // Sub-classifications
        $subClassIds = array_filter(array_map('intval', (array)($_POST['sub_class_ids'] ?? [])));
        $this->configModel->syncSubClasses($targetId, $subClassIds);

        // Cities (empty = all cities)
        $cityIds = array_filter(array_map('intval', (array)($_POST['city_ids'] ?? [])));
        $this->configModel->syncCities($targetId, $cityIds);

        // Partners
        $partnerTypes  = $_POST['partner_type']  ?? [];
        $partnerImages = $_POST['partner_img']   ?? [];
        $partnerUrls   = $_POST['partner_url']   ?? [];
        $partners = [];
        $premiumCount = 0;
        $normalCount  = 0;
        foreach ($partnerTypes as $i => $type) {
            if ($type === 'premium' && $premiumCount >= 4)  continue;
            if ($type === 'normal'  && $normalCount  >= 10) continue;
            $imgPath = null;
            if (!empty($_FILES['partner_img_files']['name'][$i])) {
                $pFile = [
                    'name'     => $_FILES['partner_img_files']['name'][$i],
                    'tmp_name' => $_FILES['partner_img_files']['tmp_name'][$i],
                    'size'     => $_FILES['partner_img_files']['size'][$i],
                    'error'    => $_FILES['partner_img_files']['error'][$i],
                ];
                $pExt = strtolower(pathinfo($pFile['name'], PATHINFO_EXTENSION));
                if (in_array($pExt, self::ALLOWED_EXT) && $pFile['size'] <= self::MAX_IMAGE && $pFile['error'] === UPLOAD_ERR_OK) {
                    if (!is_dir(API_CARDS_UPLOAD_DIR)) { mkdir(API_CARDS_UPLOAD_DIR, 0755, true); }
                    $pFilename = 'partner_' . $type . '_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $pExt;
                    if (move_uploaded_file($pFile['tmp_name'], API_CARDS_UPLOAD_DIR . $pFilename)) {
                        $imgPath = 'uploads/cards/' . $pFilename;
                    }
                }
            } elseif (!empty($partnerImages[$i])) {
                // Keep existing path on edit
                $imgPath = $partnerImages[$i];
            }
            $partners[] = ['type' => $type, 'image' => $imgPath, 'url' => $partnerUrls[$i] ?? null];
            $type === 'premium' ? $premiumCount++ : $normalCount++;
        }
        $this->configModel->syncPartners($targetId, $partners);

        $this->redirect("card-configurations/view?id={$targetId}");
    }

    // ─── DB helper ────────────────────────────────────────────────────────────

    private function db(): \PDO {
        return \Database::getInstance()->getConnection();
    }
}
