<?php
require_once CORE_PATH  . '/Auth.php';
require_once MODEL_PATH . '/Advertisement.php';

class AdvertisementsController extends Controller {

    private $auth;
    private $adModel;

    private const ALLOWED_TYPES  = ['super_admin'];
    private const PER_PAGE       = 25;
    private const MAX_FILE_BYTES = 10 * 1024 * 1024; // 10 MB
    private const IMG_EXTS       = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private const VID_EXTS       = ['mp4', 'webm', 'ogg'];

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

        $this->adModel = new Advertisement();
    }

    // ─── LIST ─────────────────────────────────────────────────────────────────

    public function index() {
        $filters = [
            'status'     => $_GET['status']     ?? '',
            'media_type' => $_GET['media_type'] ?? '',
            'search'     => trim($_GET['search'] ?? ''),
        ];
        if (isset($_GET['live'])) $filters['active_now'] = true;

        $perPage     = self::PER_PAGE;
        $totalCount  = $this->adModel->countWithDetails($filters);
        $totalPages  = max(1, (int)ceil($totalCount / $perPage));
        $currentPage = max(1, min((int)($_GET['page'] ?? 1), $totalPages));
        $offset      = ($currentPage - 1) * $perPage;

        $ads   = $this->adModel->getAllWithDetails(array_merge($filters, ['limit' => $perPage, 'offset' => $offset]));
        $stats = $this->adModel->getStats();

        $this->loadView('advertisements/index', [
            'title'        => 'Advertisement Management',
            'ads'          => $ads,
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
        if (!$id) { $this->redirectWithError('advertisements', 'Invalid advertisement ID.'); return; }

        $ad = $this->adModel->findWithDetails($id);
        if (!$ad) { $this->redirectWithError('advertisements', 'Advertisement not found.'); return; }

        $this->loadView('advertisements/view', [
            'title'        => 'Ad: ' . escape($ad['title']),
            'ad'           => $ad,
            'current_user' => $this->auth->getCurrentUser(),
        ]);
    }

    // ─── ADD ──────────────────────────────────────────────────────────────────

    public function add() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleSave(null);
            return;
        }

        $this->loadView('advertisements/add', [
            'title'        => 'Create Advertisement',
            'current_user' => $this->auth->getCurrentUser(),
        ]);
    }

    // ─── EDIT ─────────────────────────────────────────────────────────────────

    public function edit() {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { $this->redirectWithError('advertisements', 'Invalid advertisement ID.'); return; }

        $ad = $this->adModel->findWithDetails($id);
        if (!$ad) { $this->redirectWithError('advertisements', 'Advertisement not found.'); return; }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleSave($id);
            return;
        }

        $this->loadView('advertisements/edit', [
            'title'        => 'Edit Ad: ' . escape($ad['title']),
            'ad'           => $ad,
            'current_user' => $this->auth->getCurrentUser(),
        ]);
    }

    // ─── SAVE (form action for both add & edit) ───────────────────────────────

    public function save() {
        $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
        $this->handleSave($id);
    }

    // ─── SHARED SAVE ──────────────────────────────────────────────────────────

    private function handleSave($adId) {
        $this->requireCSRF();
        $cu = $this->auth->getCurrentUser();

        $title           = trim($_POST['title']            ?? '');
        $mediaType       = trim($_POST['media_type']       ?? '');
        $linkUrl         = trim($_POST['link_url']         ?? '') ?: null;
        $displayDuration = max(1, (int)($_POST['display_duration'] ?? 5));
        $startDate       = trim($_POST['start_date']       ?? '') ?: null;
        $endDate         = trim($_POST['end_date']         ?? '') ?: null;
        $status          = trim($_POST['status']           ?? 'active');

        $redirect = $adId ? "advertisements/detail?id={$adId}" : 'advertisements/add';

        if (!$title) {
            $this->redirectWithError($redirect, 'Title is required.');
            return;
        }
        if (!in_array($mediaType, ['image', 'video'])) {
            $this->redirectWithError($redirect, 'Invalid media type.');
            return;
        }

        // Determine media_url
        $mediaUrl = $adId ? ($this->adModel->findWithDetails($adId)['media_url'] ?? '') : '';

        if (!empty($_FILES['media_file']['name'])) {
            $file   = $_FILES['media_file'];
            $ext    = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = $mediaType === 'image' ? self::IMG_EXTS : self::VID_EXTS;

            if (!in_array($ext, $allowed)) {
                $this->redirectWithError($redirect, 'Invalid file type for ' . $mediaType . '.');
                return;
            }
            if ($file['size'] > self::MAX_FILE_BYTES) {
                $this->redirectWithError($redirect, 'File exceeds 10 MB limit.');
                return;
            }

            $uploadDir = API_ADS_UPLOAD_DIR;
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $newName = 'ad_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (!move_uploaded_file($file['tmp_name'], $uploadDir . $newName)) {
                $this->redirectWithError($redirect, 'File upload failed.');
                return;
            }
            $mediaUrl = 'uploads/ads/' . $newName;
        } elseif (!$adId) {
            $this->redirectWithError($redirect, 'A media file is required for new advertisements.');
            return;
        }

        $data = [
            'title'            => $title,
            'media_type'       => $mediaType,
            'media_url'        => $mediaUrl,
            'link_url'         => $linkUrl,
            'display_duration' => $displayDuration,
            'start_date'       => $startDate,
            'end_date'         => $endDate,
            'status'           => $status,
        ];

        if ($adId) {
            $this->adModel->updateAdvertisement($adId, $data);
            logAudit('advertisement_updated', 'advertisements', $adId);
            $_SESSION['success'] = 'Advertisement updated.';
            $this->redirect("advertisements/detail?id={$adId}");
        } else {
            $data['created_by_admin_id'] = $cu['admin_id'];
            $newId = $this->adModel->createAdvertisement($data);
            if ($newId) {
                logAudit('advertisement_created', 'advertisements', $newId);
                $_SESSION['success'] = 'Advertisement created.';
                $this->redirect("advertisements/detail?id={$newId}");
            } else {
                $this->redirectWithError('advertisements/add', 'Failed to create advertisement.');
            }
        }
    }

    // ─── TOGGLE ───────────────────────────────────────────────────────────────

    public function toggle() {
        $this->requireCSRF();
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { $this->redirectWithError('advertisements', 'Invalid ID.'); return; }

        $this->adModel->toggleStatus($id);
        logAudit('advertisement_toggled', 'advertisements', $id);
        $redirect = trim($_POST['redirect'] ?? 'advertisements');
        $this->redirect($redirect);
    }

    // ─── DELETE ───────────────────────────────────────────────────────────────

    public function delete() {
        $this->requireCSRF();
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { $this->redirectWithError('advertisements', 'Invalid ID.'); return; }

        $ad = $this->adModel->findWithDetails($id);
        if (!$ad) { $this->redirectWithError('advertisements', 'Not found.'); return; }

        $this->adModel->deleteAdvertisement($id);
        logAudit('advertisement_deleted', 'advertisements', $id);
        $_SESSION['success'] = 'Advertisement deleted.';
        $this->redirect('advertisements');
    }
}
