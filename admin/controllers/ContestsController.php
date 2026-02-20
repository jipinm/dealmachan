<?php

require_once ROOT_PATH . '/core/Auth.php';
require_once ROOT_PATH . '/models/Contest.php';

class ContestsController extends Controller {

    private $auth;
    private $contest;

    private const ALLOWED_TYPES = ['super_admin', 'city_admin', 'promoter_admin'];

    public function __construct() {
        $this->auth    = new Auth();
        $this->contest = new Contest();
    }

    private function requireAuth(): array {
        if (!$this->auth->isLoggedIn()) {
            $this->redirect('auth/login');
            exit;
        }
        $cu = $this->auth->getCurrentUser();
        if (!in_array($cu['admin_type'] ?? '', self::ALLOWED_TYPES)) {
            http_response_code(403);
            die('Access denied.');
        }
        return $cu;
    }

    // GET /admin/contests
    public function index(): void {
        $cu = $this->requireAuth();
        $filters  = [
            'status' => $_GET['status'] ?? '',
            'search' => $_GET['search'] ?? '',
        ];
        $page     = max(1, (int)($_GET['page'] ?? 1));
        $perPage  = 15;
        $offset   = ($page - 1) * $perPage;
        $contests = $this->contest->getAllWithDetails($filters, $perPage, $offset);
        $total    = $this->contest->countWithDetails($filters);
        $stats    = $this->contest->getStats();
        $pages    = (int)ceil($total / $perPage);
        $this->loadView('contests/index', compact('cu', 'contests', 'filters', 'stats', 'page', 'pages', 'total'));
    }

    // GET|POST /admin/contests/add
    public function add(): void {
        $cu = $this->requireAuth();
        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title   = trim($_POST['title'] ?? '');
            $desc    = trim($_POST['description'] ?? '');
            $start   = $_POST['start_date'] ?? '';
            $end     = $_POST['end_date'] ?? '';
            $status  = in_array($_POST['status'] ?? '', ['draft','active']) ? $_POST['status'] : 'draft';

            if (!$title) $errors[] = 'Contest title is required.';
            if ($start && $end && $start > $end) $errors[] = 'End date must be after start date.';

            $rules = $this->buildRulesJson($_POST);

            if (!$errors) {
                $this->contest->createContest([
                    'title'               => $title,
                    'description'         => $desc ?: null,
                    'rules_json'          => $rules,
                    'start_date'          => $start ?: null,
                    'end_date'            => $end ?: null,
                    'status'              => $status,
                    'created_by_admin_id' => $cu['admin_id'],
                ]);
                $this->redirect('contests?success=Contest+created+successfully');
            }
        }
        $this->loadView('contests/add', compact('cu', 'errors'));
    }

    // GET|POST /admin/contests/edit?id=
    public function edit(): void {
        $cu = $this->requireAuth();
        $id = (int)($_GET['id'] ?? 0);
        $data = $this->contest->findWithDetails($id);
        if (!$data) {
            $this->redirect('contests?error=Contest+not+found');
            return;
        }
        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title  = trim($_POST['title'] ?? '');
            $desc   = trim($_POST['description'] ?? '');
            $start  = $_POST['start_date'] ?? '';
            $end    = $_POST['end_date'] ?? '';

            if (!$title) $errors[] = 'Contest title is required.';
            if ($start && $end && $start > $end) $errors[] = 'End date must be after start date.';

            $rules = $this->buildRulesJson($_POST);

            if (!$errors) {
                $this->contest->updateContest($id, [
                    'title'       => $title,
                    'description' => $desc ?: null,
                    'rules_json'  => $rules,
                    'start_date'  => $start ?: null,
                    'end_date'    => $end ?: null,
                ]);
                $this->redirect('contests?success=Contest+updated');
            }
        }
        $existingRules = $data['rules_json'] ? json_decode($data['rules_json'], true) : [];
        $this->loadView('contests/add', compact('cu', 'errors', 'data', 'existingRules'));
    }

    // POST /admin/contests/delete
    public function delete(): void {
        $this->requireAuth();
        $id = (int)($_POST['id'] ?? 0);
        if ($this->contest->deleteContest($id)) {
            $this->redirect('contests?success=Contest+deleted');
        } else {
            $this->redirect('contests?error=Cannot+delete+non-draft+contest');
        }
    }

    // POST /admin/contests/toggle
    public function toggle(): void {
        $this->requireAuth();
        $id     = (int)($_POST['id'] ?? 0);
        $action = $_POST['action'] ?? '';
        $map    = ['activate' => 'active', 'complete' => 'completed', 'cancel' => 'cancelled', 'draft' => 'draft'];
        if (!isset($map[$action])) {
            $this->redirect('contests?error=Invalid+action');
            return;
        }
        $this->contest->setStatus($id, $map[$action]);
        $this->redirect('contests?success=Status+updated');
    }

    // GET /admin/contests/winners?id=
    public function winners(): void {
        $cu = $this->requireAuth();
        $id = (int)($_GET['id'] ?? 0);
        $contest = $this->contest->findWithDetails($id);
        if (!$contest) {
            $this->redirect('contests?error=Contest+not+found');
            return;
        }
        $participants = $this->contest->getParticipants($id);
        $winners      = $this->contest->getWinners($id);
        $this->loadView('contests/winners', compact('cu', 'contest', 'participants', 'winners'));
    }

    // POST /admin/contests/select-winner  →  selectWinner()
    public function selectWinner(): void {
        $this->requireAuth();
        $contestId   = (int)($_POST['contest_id'] ?? 0);
        $customerId  = (int)($_POST['customer_id'] ?? 0);
        $position    = max(1, (int)($_POST['position'] ?? 1));
        $prizeDetails = trim($_POST['prize_details'] ?? '');
        if (!$contestId || !$customerId) {
            $this->redirect("contests/winners?id={$contestId}&error=Invalid+data");
            return;
        }
        $this->contest->selectWinner($contestId, $customerId, $position, $prizeDetails);
        $this->redirect("contests/winners?id={$contestId}&success=Winner+selected");
    }

    // POST /admin/contests/remove-winner  →  removeWinner()
    public function removeWinner(): void {
        $this->requireAuth();
        $winnerId  = (int)($_POST['winner_id'] ?? 0);
        $contestId = (int)($_POST['contest_id'] ?? 0);
        $this->contest->removeWinner($winnerId);
        $this->redirect("contests/winners?id={$contestId}&success=Winner+removed");
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function buildRulesJson(array $post): ?string {
        $items = array_filter(array_map('trim', $post['rule_item'] ?? []));
        return !empty($items) ? json_encode(array_values($items), JSON_UNESCAPED_UNICODE) : null;
    }
}
