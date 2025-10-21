<?php
namespace Middy\Lib;

class Helpers {
    public static function log($message, $context = []) {
        $logFile = MIDDY_BASE_PATH . '/data/middy.log';
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}";
        
        if (!empty($context)) {
            $logMessage .= " - " . json_encode($context);
        }
        
        $logMessage .= "\n";
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
    
    public static function sanitizeFilename($filename) {
        return preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    }
}