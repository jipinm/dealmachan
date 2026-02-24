<?php
require_once CORE_PATH . '/Auth.php';

class MasterDataController extends Controller {

    private $auth;

    public function __construct() {
        $this->auth = new Auth();
        if (!$this->auth->isLoggedIn()) {
            $_SESSION['error'] = 'Please login to continue.';
            $this->redirect('auth/login');
            return;
        }
        // Master data restricted to super_admin only
        $user = $this->auth->getCurrentUser();
        if ($user['admin_type'] !== 'super_admin') {
            $_SESSION['error'] = 'Access denied. Super Admin only.';
            $this->redirect('dashboard');
            return;
        }
        // Load all models
        require_once MODEL_PATH . '/City.php';
        require_once MODEL_PATH . '/Area.php';
        require_once MODEL_PATH . '/Location.php';
        require_once MODEL_PATH . '/Label.php';
        require_once MODEL_PATH . '/Tag.php';
        require_once MODEL_PATH . '/Profession.php';
        require_once MODEL_PATH . '/DayType.php';
        require_once MODEL_PATH . '/JobTitle.php';
    }

    // ─── OVERVIEW ────────────────────────────────────────────────────────────

    public function index() {
        $db    = Database::getInstance()->getConnection();
        $stats = [];
        foreach (['cities', 'areas', 'locations', 'labels', 'tags', 'professions', 'day_types'] as $tbl) {
            $stmt = $db->query("SELECT COUNT(*) as cnt FROM {$tbl}");
            $stats[$tbl] = $stmt->fetch()['cnt'];
        }
        $this->loadView('master-data/index', [
            'title'   => 'Master Data - Deal Machan Admin',
            'stats'   => $stats,
            'current_user' => $this->auth->getCurrentUser(),
        ]);
    }

    // ─── CITIES ──────────────────────────────────────────────────────────────

    public function cities() {
        $model = new City();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCSRF();
            $action = $_POST['_action'] ?? '';

            if ($action === 'save') {
                $id    = (int)($_POST['id'] ?? 0);
                $data  = [
                    'city_name' => sanitize($_POST['city_name'] ?? ''),
                    'state'     => sanitize($_POST['state'] ?? ''),
                    'country'   => sanitize($_POST['country'] ?? 'India'),
                    'status'    => in_array($_POST['status'] ?? '', ['active','inactive']) ? $_POST['status'] : 'active',
                ];

                if (empty($data['city_name']) || empty($data['state'])) {
                    $_SESSION['error'] = 'City name and state are required.';
                    $this->redirect('master-data/cities');
                    return;
                }
                if ($model->nameExists($data['city_name'], $id ?: null)) {
                    $_SESSION['error'] = "City '{$data['city_name']}' already exists.";
                    $this->redirect('master-data/cities');
                    return;
                }
                if ($id) {
                    $model->update($id, $data);
                    $_SESSION['success'] = "City updated successfully.";
                    logAudit('update', 'city', $id, $data);
                } else {
                    $newId = $model->insert($data);
                    $_SESSION['success'] = "City added successfully.";
                    logAudit('create', 'city', $newId, $data);
                }

            } elseif ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if (!$model->canDelete($id)) {
                    $_SESSION['error'] = 'Cannot delete: City has linked areas. Remove areas first.';
                } else {
                    $model->delete($id);
                    $_SESSION['success'] = 'City deleted successfully.';
                    logAudit('delete', 'city', $id);
                }

            } elseif ($action === 'toggle') {
                $id = (int)($_POST['id'] ?? 0);
                $model->toggleStatus($id);
                $this->json(['success' => true]);
                return;
            }

            $this->redirect('master-data/cities');
            return;
        }

        $this->loadView('master-data/cities', [
            'title'        => 'Cities - Master Data',
            'cities'       => $model->getCitiesWithStats(),
            'current_user' => $this->auth->getCurrentUser(),
        ]);
    }

    // ─── AREAS ───────────────────────────────────────────────────────────────

    public function areas() {
        $model     = new Area();
        $cityModel = new City();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCSRF();
            $action = $_POST['_action'] ?? '';

            if ($action === 'save') {
                $id   = (int)($_POST['id'] ?? 0);
                $data = [
                    'area_name' => sanitize($_POST['area_name'] ?? ''),
                    'city_id'   => (int)($_POST['city_id'] ?? 0),
                    'status'    => in_array($_POST['status'] ?? '', ['active','inactive']) ? $_POST['status'] : 'active',
                ];

                if (empty($data['area_name']) || !$data['city_id']) {
                    $_SESSION['error'] = 'Area name and city are required.';
                    $this->redirect('master-data/areas');
                    return;
                }
                if ($model->nameExistsInCity($data['area_name'], $data['city_id'], $id ?: null)) {
                    $_SESSION['error'] = "Area '{$data['area_name']}' already exists in this city.";
                    $this->redirect('master-data/areas');
                    return;
                }
                if ($id) {
                    $model->update($id, $data);
                    $_SESSION['success'] = 'Area updated successfully.';
                    logAudit('update', 'area', $id, $data);
                } else {
                    $newId = $model->insert($data);
                    $_SESSION['success'] = 'Area added successfully.';
                    logAudit('create', 'area', $newId, $data);
                }

            } elseif ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if (!$model->canDelete($id)) {
                    $_SESSION['error'] = 'Cannot delete: Area has linked locations.';
                } else {
                    $model->delete($id);
                    $_SESSION['success'] = 'Area deleted successfully.';
                    logAudit('delete', 'area', $id);
                }

            } elseif ($action === 'toggle') {
                $id = (int)($_POST['id'] ?? 0);
                $model->toggleStatus($id);
                $this->json(['success' => true]);
                return;
            }

            $this->redirect('master-data/areas');
            return;
        }

        $this->loadView('master-data/areas', [
            'title'        => 'Areas - Master Data',
            'areas'        => $model->getAreasWithStats(),
            'cities'       => $cityModel->getActive(),
            'current_user' => $this->auth->getCurrentUser(),
        ]);
    }

    // ─── LOCATIONS ───────────────────────────────────────────────────────────

    public function locations() {
        $model     = new Location();
        $cityModel = new City();
        $areaModel = new Area();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCSRF();
            $action = $_POST['_action'] ?? '';

            if ($action === 'save') {
                $id   = (int)($_POST['id'] ?? 0);
                $data = [
                    'location_name' => sanitize($_POST['location_name'] ?? ''),
                    'area_id'       => (int)($_POST['area_id'] ?? 0),
                    'latitude'      => !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null,
                    'longitude'     => !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null,
                    'status'        => in_array($_POST['status'] ?? '', ['active','inactive']) ? $_POST['status'] : 'active',
                ];

                if (empty($data['location_name']) || !$data['area_id']) {
                    $_SESSION['error'] = 'Location name and area are required.';
                    $this->redirect('master-data/locations');
                    return;
                }
                if ($id) {
                    $model->update($id, $data);
                    $_SESSION['success'] = 'Location updated successfully.';
                    logAudit('update', 'location', $id, $data);
                } else {
                    $newId = $model->insert($data);
                    $_SESSION['success'] = 'Location added successfully.';
                    logAudit('create', 'location', $newId, $data);
                }

            } elseif ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if (!$model->canDelete($id)) {
                    $_SESSION['error'] = 'Cannot delete: Location is linked to stores.';
                } else {
                    $model->delete($id);
                    $_SESSION['success'] = 'Location deleted successfully.';
                    logAudit('delete', 'location', $id);
                }

            } elseif ($action === 'toggle') {
                $id = (int)($_POST['id'] ?? 0);
                $model->toggleStatus($id);
                $this->json(['success' => true]);
                return;
            }

            $this->redirect('master-data/locations');
            return;
        }

        $this->loadView('master-data/locations', [
            'title'        => 'Locations - Master Data',
            'locations'    => $model->getAllWithHierarchy(),
            'cities'       => $cityModel->getActive(),
            'areas'        => $areaModel->getAllWithCity(),
            'current_user' => $this->auth->getCurrentUser(),
        ]);
    }

    // ─── LABELS ──────────────────────────────────────────────────────────────

    public function labels() {
        $model = new Label();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCSRF();
            $action = $_POST['_action'] ?? '';

            if ($action === 'save') {
                $id   = (int)($_POST['id'] ?? 0);
                $data = [
                    'label_name'      => sanitize($_POST['label_name'] ?? ''),
                    'label_icon'      => sanitize($_POST['label_icon'] ?? ''),
                    'description'     => sanitize($_POST['description'] ?? ''),
                    'priority_weight' => (int)($_POST['priority_weight'] ?? 0),
                    'status'          => in_array($_POST['status'] ?? '', ['active','inactive']) ? $_POST['status'] : 'active',
                ];

                if (empty($data['label_name'])) {
                    $_SESSION['error'] = 'Label name is required.';
                    $this->redirect('master-data/labels');
                    return;
                }
                if ($model->nameExists($data['label_name'], $id ?: null)) {
                    $_SESSION['error'] = "Label '{$data['label_name']}' already exists.";
                    $this->redirect('master-data/labels');
                    return;
                }
                if ($id) {
                    $model->update($id, $data);
                    $_SESSION['success'] = 'Label updated successfully.';
                    logAudit('update', 'label', $id, $data);
                } else {
                    $newId = $model->insert($data);
                    $_SESSION['success'] = 'Label added successfully.';
                    logAudit('create', 'label', $newId, $data);
                }

            } elseif ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if (!$model->canDelete($id)) {
                    $_SESSION['error'] = 'Cannot delete: Label is assigned to merchants.';
                } else {
                    $model->delete($id);
                    $_SESSION['success'] = 'Label deleted successfully.';
                    logAudit('delete', 'label', $id);
                }

            } elseif ($action === 'toggle') {
                $id = (int)($_POST['id'] ?? 0);
                $model->toggleStatus($id);
                $this->json(['success' => true]);
                return;
            }

            $this->redirect('master-data/labels');
            return;
        }

        $this->loadView('master-data/labels', [
            'title'        => 'Labels - Master Data',
            'labels'       => $model->getAllWithStats(),
            'current_user' => $this->auth->getCurrentUser(),
        ]);
    }

    // ─── TAGS ─────────────────────────────────────────────────────────────────

    public function tags() {
        $model = new Tag();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCSRF();
            $action = $_POST['_action'] ?? '';

            if ($action === 'save') {
                $id   = (int)($_POST['id'] ?? 0);
                $data = [
                    'tag_name'      => sanitize($_POST['tag_name'] ?? ''),
                    'tag_category'  => in_array($_POST['tag_category'] ?? '', ['category','subcategory','filter']) ? $_POST['tag_category'] : 'category',
                    'parent_tag_id' => !empty($_POST['parent_tag_id']) ? (int)$_POST['parent_tag_id'] : null,
                    'status'        => in_array($_POST['status'] ?? '', ['active','inactive']) ? $_POST['status'] : 'active',
                ];

                if (empty($data['tag_name'])) {
                    $_SESSION['error'] = 'Tag name is required.';
                    $this->redirect('master-data/tags');
                    return;
                }
                if ($id) {
                    $model->update($id, $data);
                    $_SESSION['success'] = 'Tag updated successfully.';
                    logAudit('update', 'tag', $id, $data);
                } else {
                    $newId = $model->insert($data);
                    $_SESSION['success'] = 'Tag added successfully.';
                    logAudit('create', 'tag', $newId, $data);
                }

            } elseif ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if (!$model->canDelete($id)) {
                    $_SESSION['error'] = 'Cannot delete: Tag has child tags or is linked to merchants/coupons.';
                } else {
                    $model->delete($id);
                    $_SESSION['success'] = 'Tag deleted successfully.';
                    logAudit('delete', 'tag', $id);
                }

            } elseif ($action === 'toggle') {
                $id = (int)($_POST['id'] ?? 0);
                $model->toggleStatus($id);
                $this->json(['success' => true]);
                return;
            }

            $this->redirect('master-data/tags');
            return;
        }

        $this->loadView('master-data/tags', [
            'title'        => 'Tags - Master Data',
            'tags'         => $model->getAllWithDetails(),
            'parent_tags'  => $model->getParentTags(),
            'current_user' => $this->auth->getCurrentUser(),
        ]);
    }

    // ─── PROFESSIONS ─────────────────────────────────────────────────────────

    public function professions() {
        $model = new Profession();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCSRF();
            $action = $_POST['_action'] ?? '';

            if ($action === 'save') {
                $id   = (int)($_POST['id'] ?? 0);
                $data = [
                    'profession_name' => sanitize($_POST['profession_name'] ?? ''),
                    'status'          => in_array($_POST['status'] ?? '', ['active','inactive']) ? $_POST['status'] : 'active',
                ];

                if (empty($data['profession_name'])) {
                    $_SESSION['error'] = 'Profession name is required.';
                    $this->redirect('master-data/professions');
                    return;
                }
                if ($model->nameExists($data['profession_name'], $id ?: null)) {
                    $_SESSION['error'] = "Profession '{$data['profession_name']}' already exists.";
                    $this->redirect('master-data/professions');
                    return;
                }
                if ($id) {
                    $model->update($id, $data);
                    $_SESSION['success'] = 'Profession updated successfully.';
                    logAudit('update', 'profession', $id, $data);
                } else {
                    $newId = $model->insert($data);
                    $_SESSION['success'] = 'Profession added successfully.';
                    logAudit('create', 'profession', $newId, $data);
                }

            } elseif ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if (!$model->canDelete($id)) {
                    $_SESSION['error'] = 'Cannot delete: Profession is linked to customers.';
                } else {
                    $model->delete($id);
                    $_SESSION['success'] = 'Profession deleted successfully.';
                    logAudit('delete', 'profession', $id);
                }

            } elseif ($action === 'toggle') {
                $id = (int)($_POST['id'] ?? 0);
                $model->toggleStatus($id);
                $this->json(['success' => true]);
                return;
            }

            $this->redirect('master-data/professions');
            return;
        }

        $this->loadView('master-data/professions', [
            'title'        => 'Professions - Master Data',
            'professions'  => $model->getAllWithStats(),
            'current_user' => $this->auth->getCurrentUser(),
        ]);
    }

    // ─── DAY TYPES ────────────────────────────────────────────────────────────

    public function dayTypes() {
        $model = new DayType();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCSRF();
            $action = $_POST['_action'] ?? '';

            if ($action === 'save') {
                $id   = (int)($_POST['id'] ?? 0);
                $data = [
                    'day_type_name' => sanitize($_POST['day_type_name'] ?? ''),
                    'description'   => sanitize($_POST['description'] ?? ''),
                ];

                if (empty($data['day_type_name'])) {
                    $_SESSION['error'] = 'Day type name is required.';
                    $this->redirect('master-data/day-types');
                    return;
                }
                if ($model->nameExists($data['day_type_name'], $id ?: null)) {
                    $_SESSION['error'] = "Day type '{$data['day_type_name']}' already exists.";
                    $this->redirect('master-data/day-types');
                    return;
                }
                if ($id) {
                    $model->update($id, $data);
                    $_SESSION['success'] = 'Day type updated successfully.';
                    logAudit('update', 'day_type', $id, $data);
                } else {
                    $newId = $model->insert($data);
                    $_SESSION['success'] = 'Day type added successfully.';
                    logAudit('create', 'day_type', $newId, $data);
                }

            } elseif ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                $model->delete($id);
                $_SESSION['success'] = 'Day type deleted successfully.';
                logAudit('delete', 'day_type', $id);
            }

            $this->redirect('master-data/day-types');
            return;
        }

        $this->loadView('master-data/day-types', [
            'title'        => 'Day Types - Master Data',
            'dayTypes'     => $model->getAll(),
            'current_user' => $this->auth->getCurrentUser(),
        ]);
    }

    // ─── JOB TITLES ───────────────────────────────────────────────────────────

    public function jobTitles() {
        $model      = new JobTitle();
        $profModel  = new Profession();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCSRF();
            $action = $_POST['_action'] ?? '';

            if ($action === 'save') {
                $id           = (int)($_POST['id'] ?? 0);
                $professionId = (int)($_POST['profession_id'] ?? 0);
                $data = [
                    'job_title_name' => sanitize($_POST['job_title_name'] ?? ''),
                    'profession_id'  => $professionId,
                    'status'         => in_array($_POST['status'] ?? '', ['active','inactive']) ? $_POST['status'] : 'active',
                ];

                if (empty($data['job_title_name'])) {
                    $_SESSION['error'] = 'Job title name is required.';
                    $this->redirect('master-data/job-titles');
                    return;
                }
                if (!$professionId) {
                    $_SESSION['error'] = 'Please select a profession.';
                    $this->redirect('master-data/job-titles');
                    return;
                }
                if ($model->nameExists($data['job_title_name'], $professionId, $id ?: null)) {
                    $_SESSION['error'] = "Job title '{$data['job_title_name']}' already exists under that profession.";
                    $this->redirect('master-data/job-titles');
                    return;
                }
                if ($id) {
                    $model->update($id, $data);
                    $_SESSION['success'] = 'Job title updated successfully.';
                    logAudit('update', 'job_title', $id, $data);
                } else {
                    $newId = $model->insert($data);
                    $_SESSION['success'] = 'Job title added successfully.';
                    logAudit('create', 'job_title', $newId, $data);
                }

            } elseif ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if (!$model->canDelete($id)) {
                    $_SESSION['error'] = 'Cannot delete: Job title is linked to customers.';
                } else {
                    $model->delete($id);
                    $_SESSION['success'] = 'Job title deleted successfully.';
                    logAudit('delete', 'job_title', $id);
                }

            } elseif ($action === 'toggle') {
                $id = (int)($_POST['id'] ?? 0);
                $model->toggleStatus($id);
                $this->json(['success' => true]);
                return;
            }

            $this->redirect('master-data/job-titles');
            return;
        }

        $this->loadView('master-data/job-titles', [
            'title'        => 'Job Titles - Master Data',
            'jobTitles'    => $model->getAllWithStats(),
            'professions'  => $profModel->getActive(),
            'current_user' => $this->auth->getCurrentUser(),
        ]);
    }

    // ─── AJAX HELPERS ─────────────────────────────────────────────────────────

    /**
     * JSON: get areas for a city (for dependent dropdowns)
     * URL: /master-data/areas-json?city_id=X
     */
    public function areasJson() {
        $cityId = (int)($_GET['city_id'] ?? 0);
        $model  = new Area();
        $areas  = $model->getByCity($cityId);
        $this->json($areas);
    }

    /**
     * JSON: get locations for an area (for dependent dropdowns)
     * URL: /master-data/locations-json?area_id=X
     */
    public function locationsJson() {
        $areaId    = (int)($_GET['area_id'] ?? 0);
        $model     = new Location();
        $locations = $model->getByArea($areaId);
        $this->json($locations);
    }

}
