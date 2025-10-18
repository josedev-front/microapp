<?php
// app_core/config/database.php

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'microapps');
define('DB_USER', 'root');  // Cambiar según tu configuración
define('DB_PASS', '');      // Cambiar según tu configuración
define('DB_CHARSET', 'utf8mb4');

// Función de conexión a la base de datos
function conexion() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS);
            
            // Configurar PDO para que lance excepciones en errores
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            
        } catch (PDOException $e) {
            error_log("Error de conexión a la base de datos: " . $e->getMessage());
            throw new Exception("Error al conectar con la base de datos: " . $e->getMessage());
        }
    }
    
    return $pdo;
}

// Función para probar la conexión (útil para debugging)
function probarConexion() {
    try {
        $pdo = conexion();
        $stmt = $pdo->query("SELECT 1");
        return $stmt->fetch() !== false;
    } catch (Exception $e) {
        error_log("Error probando conexión: " . $e->getMessage());
        return false;
    }
}