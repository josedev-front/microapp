<?php
// microservices/middy/config/paths.php

class MiddyPathResolver {
    private static $projectRoot = null;
    
    public static function getProjectRoot() {
        if (self::$projectRoot !== null) {
            return self::$projectRoot;
        }
        
        $possiblePaths = [
            dirname(__DIR__, 3), // 3 niveles arriba desde microservices/middy/
            dirname(__DIR__, 2), // 2 niveles arriba
            dirname(__DIR__),    // 1 nivel arriba  
            __DIR__,             // Directorio actual
        ];
        
        foreach ($possiblePaths as $path) {
            // SIN ECHOS - solo verificación silenciosa
            if (file_exists($path . '/app_core') && is_dir($path . '/app_core')) {
                self::$projectRoot = $path;
                return $path;
            }
        }
        
        throw new Exception("No se pudo determinar la raíz del proyecto");
    }
    
    public static function getCorePath() {
        return self::getProjectRoot() . '/app_core';
    }
    
    public static function requireCoreFiles() {
        $corePath = self::getCorePath();
        
        $helpersPath = $corePath . '/config/helpers.php';
        $mainPath = $corePath . '/php/main.php';
        
        if (!file_exists($helpersPath)) {
            throw new Exception("Archivo helpers.php no encontrado");
        }
        
        if (!file_exists($mainPath)) {
            throw new Exception("Archivo main.php no encontrado");
        }
        
        require_once $helpersPath;
        require_once $mainPath;
    }
}