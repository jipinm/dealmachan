<?php

require_once CORE_PATH . '/Auth.php';
require_once MODEL_PATH . '/Message.php';

class MessagesController extends Controller {

    private $auth;
    private $model;
    private const PER_PAGE = 20;

    public function __construct() {
        $this->auth  = new Auth();
        $this->model = new Message();

        if (!$this->auth->isLoggedIn()) {
            $this->redirect('auth/login');
            return;
        }
    }

    private function currentAdminId(): int {
        return (int)($this->auth->getCurrentUser()['admin_id'] ?? 0);
    }

    // GET /messages  →  redirect to inbox
    public function index(): void {
        $this->redirect('messages/inbox');
    }

    // GET /messages/inbox
    public function inbox(): void {
        $cu      = $this->auth->getCurrentUser();
        $adminId = (int)$cu['admin_id'];
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $offset  = ($page - 1) * self::PER_PAGE;

        $messages = $this->model->getInbox($adminId, self::PER_PAGE, $offset);
        $total    = $this->model->countInbox($adminId);
        $stats    = $this->model->getStats($adminId);
        $pages    = (int)ceil($total / self::PER_PAGE);
        $tab      = 'inbox';

        $this->loadView('messages/inbox', compact('cu', 'messages', 'stats', 'page', 'pages', 'total', 'tab'));
    }

    // GET /messages/sent
    public function sent(): void {
        $cu      = $this->auth->getCurrentUser();
        $adminId = (int)$cu['admin_id'];
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $offset  = ($page - 1) * self::PER_PAGE;

        $messages = $this->model->getSent($adminId, self::PER_PAGE, $offset);
        $total    = $this->model->countSent($adminId);
        $stats    = $this->model->getStats($adminId);
        $pages    = (int)ceil($total / self::PER_PAGE);
        $tab      = 'sent';

        $this->loadView('messages/inbox', compact('cu', 'messages', 'stats', 'page', 'pages', 'total', 'tab'));
    }

    // GET /messages/compose  |  POST /messages/compose
    public function compose(): void {
        $cu      = $this->auth->getCurrentUser();
        $adminId = (int)$cu['admin_id'];
        $errors  = [];

        $admins    = $this->model->getAdminList();
        $merchants = $this->model->getMerchantList();
        $customers = $this->model->getCustomerList();
        $stats     = $this->model->getStats($adminId);

        // Pre-fill recipient from GET params (e.g. reply shortcut)
        $prefill = [
            'receiver_type' => $_GET['to_type'] ?? '',
            'receiver_id'   => $_GET['to_id']   ?? '',
            'subject'       => $_GET['subject']  ?? '',
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $receiverType = $_POST['receiver_type'] ?? '';
            $receiverId   = (int)($_POST['receiver_id'] ?? 0);
            $subject      = trim($_POST['subject'] ?? '');
            $body         = trim($_POST['message_text'] ?? '');

            if (!in_array($receiverType, ['admin', 'merchant', 'customer'])) {
                $errors[] = 'Please select a valid recipient type.';
            }
            if (!$receiverId) $errors[] = 'Please select a recipient.';
            if (!$body)       $errors[] = 'Message body cannot be empty.';

            if (!$errors) {
                $this->model->send([
                    'sender_id'    => $adminId,
                    'sender_type'  => 'admin',
                    'receiver_id'  => $receiverId,
                    'receiver_type'=> $receiverType,
                    'subject'      => $subject ?: null,
                    'message_text' => $body,
                ]);
                $this->redirect('messages/sent?success=Message+sent+successfully');
                return;
            }
        }

        $this->loadView('messages/compose', compact('cu', 'admins', 'merchants', 'customers', 'stats', 'errors', 'prefill'));
    }

    // GET /messages/show?id=
    public function show(): void {
        $cu      = $this->auth->getCurrentUser();
        $adminId = (int)$cu['admin_id'];
        $id      = (int)($_GET['id'] ?? 0);

        $root = $this->model->find($id);
        if (!$root) {
            $this->redirect('messages/inbox?error=Message+not+found');
            return;
        }

        // Mark as read if this admin is the receiver
        $this->model->markThreadRead($id, $adminId);

        $thread = $this->model->getThread($id);
        $stats  = $this->model->getStats($adminId);

        $this->loadView('messages/thread', compact('cu', 'root', 'thread', 'stats'));
    }

    // POST /messages/reply
    public function reply(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->redirect('messages/inbox'); return; }

        $cu        = $this->auth->getCurrentUser();
        $adminId   = (int)$cu['admin_id'];
        $parentId  = (int)($_POST['parent_id'] ?? 0);
        $body      = trim($_POST['message_text'] ?? '');

        if (!$parentId || !$body) {
            $this->redirect("messages/show?id={$parentId}&error=Invalid+reply");
            return;
        }

        $parent = $this->model->find($parentId);
        if (!$parent) {
            $this->redirect('messages/inbox');
            return;
        }

        // Reply goes back to the original sender
        $receiverId   = $parent['sender_id'];
        $receiverType = $parent['sender_type'];
        $rootId       = $parent['parent_message_id'] ?? $parentId;

        $this->model->send([
            'sender_id'         => $adminId,
            'sender_type'       => 'admin',
            'receiver_id'       => $receiverId,
            'receiver_type'     => $receiverType,
            'subject'           => 'Re: ' . ($parent['subject'] ?? '(no subject)'),
            'message_text'      => $body,
            'parent_message_id' => $rootId,
        ]);

        $this->redirect("messages/show?id={$rootId}&success=Reply+sent");
    }

    // POST /messages/mark-read  →  markRead()
    public function markRead(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->redirect('messages/inbox'); return; }
        $id = (int)($_POST['id'] ?? 0);
        if ($id) $this->model->markRead($id);
        $this->redirect($_POST['redirect'] ?? 'messages/inbox');
    }

    // POST /messages/delete
    public function delete(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->redirect('messages/inbox'); return; }
        $id = (int)($_POST['id'] ?? 0);
        if ($id) $this->model->delete($id);
        $this->redirect($_POST['redirect'] ?? 'messages/inbox?success=Message+deleted');
    }
}
