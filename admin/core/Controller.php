<?php
abstract class Controller {
    
    /**
     * Load a view file
     */
    protected function view($view, $data = []) {
        extract($data);
        
        $viewFile = __DIR__ . "/../views/{$view}.php";
        
        if (file_exists($viewFile)) {
            require_once $viewFile;
        } else {
            die("View not found: {$view}");
        }
    }
    
    /**
     * Load a model
     */
    protected function model($model) {
        $modelFile = __DIR__ . "/../models/{$model}.php";
        
        if (file_exists($modelFile)) {
            require_once $modelFile;
            return new $model();
        } else {
            die("Model not found: {$model}");
        }
    }
    
    /**
     * Redirect to another page
     */
    protected function redirect($path) {
        header("Location: " . BASE_URL . $path);
        exit();
    }
    
    /**
     * Return JSON response
     */
    protected function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }
    
    /**
     * Load view with main layout
     */
    protected function loadView($view, $data = []) {
        // Extract data for use in view
        extract($data);
        
        // Start output buffering to capture view content
        ob_start();
        
        $viewFile = VIEW_PATH . "/{$view}.php";
        
        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            die("View not found: {$view}");
        }
        
        // Get the view content
        $content = ob_get_clean();
        
        // Load main layout with content
        include VIEW_PATH . '/layouts/main.php';
    }
    
    /**
     * Get POST data
     */
    protected function post($key = null, $default = null) {
        if ($key === null) {
            return $_POST;
        }
        return $_POST[$key] ?? $default;
    }
    
    /**
     * Get GET data
     */
    protected function get($key = null, $default = null) {
        if ($key === null) {
            return $_GET;
        }
        return $_GET[$key] ?? $default;
    }
    
    /**
     * Flash message
     */
    protected function setFlash($type, $message) {
        $_SESSION['flash'][$type] = $message;
    }
    
    /**
     * Get and clear flash message
     */
    protected function getFlash($type) {
        if (isset($_SESSION['flash'][$type])) {
            $message = $_SESSION['flash'][$type];
            unset($_SESSION['flash'][$type]);
            return $message;
        }
        return null;
    }

    /**
     * Require CSRF token match or abort
     */
    protected function requireCSRF() {
        if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
            http_response_code(403);
            die('Invalid CSRF token.');
        }
    }

    /**
     * Set session error and redirect
     */
    protected function redirectWithError($path, $message) {
        $_SESSION['error'] = $message;
        $this->redirect($path);
    }
}
