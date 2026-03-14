<?php
require_once CORE_PATH . '/Auth.php';
require_once MODEL_PATH . '/Survey.php';

class SurveysController extends Controller {

    private Survey $model;
    private $auth;
    private const PER_PAGE = 20;
    private const ALLOWED_TYPES = ['super_admin', 'city_admin', 'promoter_admin'];

    public function __construct() {
        $this->auth = new Auth();

        if (!$this->auth->isLoggedIn()) {
            $_SESSION['error'] = 'Please login to continue.';
            $this->redirect('auth/login');
            return;
        }

        $cu = $this->auth->getCurrentUser();
        if (!in_array($cu['admin_type'] ?? '', self::ALLOWED_TYPES)) {
            $_SESSION['error'] = 'Access denied.';
            $this->redirect('dashboard');
            return;
        }

        $this->model = new Survey();
    }

    // ─── LIST ──────────────────────────────────────────────────────────────────

    public function index() {
        $filters = [
            'search' => trim($_GET['search'] ?? ''),
            'status' => $_GET['status'] ?? '',
        ];

        $total  = $this->model->countWithDetails($filters);
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $filters['limit']  = self::PER_PAGE;
        $filters['offset'] = ($page - 1) * self::PER_PAGE;

        $surveys    = $this->model->getAllWithDetails($filters);
        $stats      = $this->model->getStats();
        $totalPages = (int)ceil($total / self::PER_PAGE);

        $this->loadView('surveys/index', compact('surveys', 'filters', 'stats', 'total', 'page', 'totalPages'));
    }

    // ─── ADD ──────────────────────────────────────────────────────────────────

    public function add() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCSRF();

            $questionsJson = $this->buildQuestionsJson($_POST);
            if ($questionsJson === null) {
                $this->redirectWithError('surveys/add', 'At least one question is required.');
                return;
            }

            $data = [
                'title'               => trim($_POST['title'] ?? ''),
                'description'         => trim($_POST['description'] ?? ''),
                'questions_json'      => $questionsJson,
                'status'              => $_POST['status'] ?? 'draft',
                'created_by_admin_id' => $this->auth->getCurrentUser()['admin_id'],
                'active_from'         => !empty($_POST['active_from']) ? $_POST['active_from'] : null,
                'active_until'        => !empty($_POST['active_until']) ? $_POST['active_until'] : null,
            ];

            if (empty($data['title'])) {
                $this->redirectWithError('surveys/add', 'Survey title is required.');
                return;
            }

            if ($this->model->createSurvey($data)) {
                $_SESSION['success'] = 'Survey created successfully.';
                $this->redirect('surveys');
            } else {
                $this->redirectWithError('surveys/add', 'Failed to create survey.');
            }
            return;
        }

        $this->loadView('surveys/add', []);
    }

    // ─── EDIT ─────────────────────────────────────────────────────────────────

    public function edit() {
        $id     = (int)($_GET['id'] ?? 0);
        $survey = $this->model->findWithDetails($id);
        if (!$survey) {
            $_SESSION['error'] = 'Survey not found.';
            $this->redirect('surveys');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCSRF();

            $questionsJson = $this->buildQuestionsJson($_POST);
            if ($questionsJson === null) {
                $this->redirectWithError("surveys/edit?id={$id}", 'At least one question is required.');
                return;
            }

            $data = [
                'title'        => trim($_POST['title'] ?? ''),
                'description'  => trim($_POST['description'] ?? ''),
                'questions_json' => $questionsJson,
                'status'       => $_POST['status'] ?? $survey['status'],
                'active_from'  => !empty($_POST['active_from']) ? $_POST['active_from'] : null,
                'active_until' => !empty($_POST['active_until']) ? $_POST['active_until'] : null,
            ];

            if (empty($data['title'])) {
                $this->redirectWithError("surveys/edit?id={$id}", 'Survey title is required.');
                return;
            }

            if ($this->model->updateSurvey($id, $data)) {
                $_SESSION['success'] = 'Survey updated successfully.';
                $this->redirect('surveys');
            } else {
                $this->redirectWithError("surveys/edit?id={$id}", 'Failed to update survey.');
            }
            return;
        }

        $questions = json_decode($survey['questions_json'], true) ?? [];
        $this->loadView('surveys/edit', compact('survey', 'questions'));
    }

    // ─── DELETE ───────────────────────────────────────────────────────────────

    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->redirect('surveys'); return; }
        $this->requireCSRF();

        $id = (int)($_POST['survey_id'] ?? 0);
        if (!$id) { $this->redirectWithError('surveys', 'Invalid survey.'); return; }

        if ($this->model->deleteSurvey($id)) {
            $_SESSION['success'] = 'Survey deleted successfully.';
        } else {
            $_SESSION['error'] = 'Cannot delete &mdash; survey may not be in draft status or has responses.';
        }
        $this->redirect('surveys');
    }

    // ─── TOGGLE STATUS ────────────────────────────────────────────────────────

    public function toggle() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->redirect('surveys'); return; }
        $this->requireCSRF();

        $id     = (int)($_POST['survey_id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        $valid  = ['draft', 'active', 'closed'];

        if (!$id || !in_array($status, $valid)) {
            $this->redirectWithError('surveys', 'Invalid request.');
            return;
        }

        if ($this->model->setStatus($id, $status)) {
            $_SESSION['success'] = 'Survey status updated.';
        } else {
            $_SESSION['error'] = 'Failed to update status.';
        }
        $this->redirect('surveys');
    }

    // ─── RESPONSES ────────────────────────────────────────────────────────────

    public function responses() {
        $id     = (int)($_GET['id'] ?? 0);
        $survey = $this->model->findWithDetails($id);
        if (!$survey) {
            $_SESSION['error'] = 'Survey not found.';
            $this->redirect('surveys');
            return;
        }

        $filters = ['search' => trim($_GET['search'] ?? '')];
        $total   = $this->model->countResponses($id, $filters);
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $filters['limit']  = self::PER_PAGE;
        $filters['offset'] = ($page - 1) * self::PER_PAGE;

        $responses  = $this->model->getResponses($id, $filters);
        $analytics  = $this->model->getResponseAnalytics($id);
        $questions  = json_decode($survey['questions_json'], true) ?? [];
        $totalPages = (int)ceil($total / self::PER_PAGE);

        $this->loadView('surveys/responses', compact('survey', 'responses', 'analytics', 'questions', 'filters', 'total', 'page', 'totalPages'));
    }

    // ─── HELPERS ──────────────────────────────────────────────────────────────

    /**
     * Build questions JSON from POST data.
     * Expects: q_type[], q_question[], q_required[], q_options_<n>[]
     */
    private function buildQuestionsJson($post) {
        $types     = $post['q_type']     ?? [];
        $questions = $post['q_question'] ?? [];
        $required  = $post['q_required'] ?? [];

        if (empty($questions)) return null;

        $result = [];
        foreach ($questions as $i => $qText) {
            $qText = trim($qText);
            if ($qText === '') continue;

            $type = $types[$i] ?? 'text';
            $item = [
                'id'       => $i + 1,
                'type'     => $type,
                'question' => $qText,
                'required' => isset($required[$i]) && $required[$i] === '1',
            ];

            // Options for radio, checkbox, select
            if (in_array($type, ['radio', 'checkbox', 'select'])) {
                $optKey  = "q_options_{$i}";
                $options = isset($post[$optKey]) ? array_filter(array_map('trim', $post[$optKey])) : [];
                $item['options'] = array_values($options);
            }

            // Rating scale
            if ($type === 'rating') {
                $item['scale'] = (int)($post["q_scale_{$i}"] ?? 5);
            }

            $result[] = $item;
        }

        return empty($result) ? null : json_encode($result, JSON_UNESCAPED_UNICODE);
    }
}
