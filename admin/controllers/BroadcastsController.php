<?php

require_once ROOT_PATH . '/core/Auth.php';

class BroadcastsController extends Controller {

    public function __construct() {}

    public function index(): void {
        $this->redirect('notifications/broadcast');
    }
}
