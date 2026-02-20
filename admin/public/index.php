<?php
// Define basic paths first (before loading config files)
$rootPath = dirname(__DIR__);
$configPath = $rootPath . '/config';

// Load environment and constants BEFORE starting session
require_once $configPath . '/env.php';
require_once $configPath . '/constants.php';

// Session configuration must be loaded BEFORE session_start()
require_once $configPath . '/session.php';

// Define other paths using constants from constants.php
define('CORE_PATH', ROOT_PATH . '/core');
define('CONTROLLER_PATH', ROOT_PATH . '/controllers');
define('MODEL_PATH', ROOT_PATH . '/models');
define('VIEW_PATH', ROOT_PATH . '/views');
define('HELPER_PATH', ROOT_PATH . '/helpers');

// Load core classes
require_once CORE_PATH . '/Database.php';
require_once CORE_PATH . '/Model.php';
require_once CORE_PATH . '/Controller.php';

// Load helpers
require_once HELPER_PATH . '/functions.php';

// Simple routing
$url = $_GET['url'] ?? '';
$url = rtrim($url, '/');
$url = filter_var($url, FILTER_SANITIZE_URL);

// Parse the URL
$urlArray = explode('/', $url);
$controller = $urlArray[0] ?: 'auth';
$action     = $urlArray[1] ?? 'index';

// Pass extra URL segments as GET params (e.g. /master-data/cities/edit/5)
if (isset($urlArray[2]) && $urlArray[2] !== '') {
    $_GET['action'] = $_GET['action'] ?? $urlArray[2];
}
if (isset($urlArray[3]) && $urlArray[3] !== '') {
    $_GET['id'] = $_GET['id'] ?? $urlArray[3];
}

// Convert hyphenated controller name to PascalCase
// e.g. 'master-data' → 'MasterData', 'admin-management' → 'AdminManagement'
$controllerParts = explode('-', $controller);
$controllerName  = implode('', array_map('ucfirst', $controllerParts));
$controllerClass = $controllerName . 'Controller';
$controllerFile  = CONTROLLER_PATH . '/' . $controllerClass . '.php';

// Convert hyphenated action to camelCase  (e.g. 'add-city' → 'addCity')
$actionParts  = explode('-', $action);
$actionMethod = $actionParts[0] . implode('', array_map('ucfirst', array_slice($actionParts, 1)));

// Check if controller exists
if (file_exists($controllerFile)) {
    require_once $controllerFile;

    if (class_exists($controllerClass)) {
        $controllerInstance = new $controllerClass();

        if (method_exists($controllerInstance, $actionMethod)) {
            $controllerInstance->$actionMethod();
        } else {
            header("HTTP/1.0 404 Not Found");
            echo "<h2>404 - Action Not Found</h2><p>Controller: <b>{$controllerClass}</b> | Action: <b>{$actionMethod}</b></p>";
        }
    } else {
        header("HTTP/1.0 404 Not Found");
        echo "<h2>404 - Controller Class Not Found</h2><p>{$controllerClass}</p>";
    }
} else {
    // Controller file not found - handle special cases
    if (empty($url) || $url === 'auth' || $url === 'login') {
        require_once CONTROLLER_PATH . '/AuthController.php';
        $authController = new AuthController();
        $authController->login();
    } else {
        header("HTTP/1.0 404 Not Found");
        echo "<h2>404 - Controller Not Found</h2><p>{$controllerClass}</p>";
    }
}
?>