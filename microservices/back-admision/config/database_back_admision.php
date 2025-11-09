<?php
class BackAdmisionDatabase {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        // Cargar configuración desde variables de entorno o config
        $config = [
            'host' => $_ENV['BACK_ADMISION_DB_HOST'] ?? 'localhost',
            'dbname' => $_ENV['BACK_ADMISION_DB_NAME'] ?? 'back_admision',
            'username' => $_ENV['BACK_ADMISION_DB_USER'] ?? 'root',
            'password' => $_ENV['BACK_ADMISION_DB_PASS'] ?? ''
        ];
        
        try {
            $this->pdo = new PDO(
                "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4",
                $config['username'],
                $config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_PERSISTENT => false
                ]
            );
        } catch (PDOException $e) {
            error_log("❌ Error conexión BD Back-Admisión: " . $e->getMessage());
            throw new Exception("Error de conexión a la base de datos de admisión: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance->pdo;
    }
    
    public static function getConnection() {
        return self::getInstance();
    }
}

// Función helper para obtener conexión
function getBackAdmisionDB() {
    return BackAdmisionDatabase::getConnection();
}

// Función para verificar conexión
function testBackAdmisionConnection() {
    try {
        $db = getBackAdmisionDB();
        $db->query("SELECT 1");
        return true;
    } catch (Exception $e) {
        error_log("Test conexión falló: " . $e->getMessage());
        return false;
    }
}
?>