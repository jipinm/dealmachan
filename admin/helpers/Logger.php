<?php
class Logger {
    private static $logFile = null;
    
    public static function init() {
        if (self::$logFile === null) {
            self::$logFile = ROOT_PATH . '/logs/app.log';
            
            // Create logs directory if it doesn't exist
            $logDir = dirname(self::$logFile);
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
        }
    }
    
    public static function log($message, $level = 'INFO', $context = []) {
        self::init();
        
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
        $logMessage = "[{$timestamp}] [{$level}] {$message}{$contextStr}" . PHP_EOL;
        
        file_put_contents(self::$logFile, $logMessage, FILE_APPEND);
        
        // Also log to PHP error log for critical errors
        if ($level === 'ERROR' || $level === 'CRITICAL') {
            error_log($logMessage);
        }
    }
    
    public static function info($message, $context = []) {
        self::log($message, 'INFO', $context);
    }
    
    public static function debug($message, $context = []) {
        self::log($message, 'DEBUG', $context);
    }
    
    public static function warning($message, $context = []) {
        self::log($message, 'WARNING', $context);
    }
    
    public static function error($message, $context = []) {
        self::log($message, 'ERROR', $context);
    }
    
    public static function critical($message, $context = []) {
        self::log($message, 'CRITICAL', $context);
    }
}
?>
