<?php
// Microservicio Tata Trivia - Sistema de trivia interactivo

// Evitar session_start() si ya hay una sesión activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuración de rutas - usar prefijos únicos
define('TATA_TRIVIA_ROOT', dirname(__FILE__));
define('TATA_TRIVIA_VIEWS', TATA_TRIVIA_ROOT . '/views');
define('TATA_TRIVIA_API', TATA_TRIVIA_ROOT . '/api');
define('TATA_TRIVIA_ASSETS', TATA_TRIVIA_ROOT . '/assets');
define('TATA_TRIVIA_CONTROLLERS', TATA_TRIVIA_ROOT . '/controllers');

// Incluir configuración de paths - con verificación
if (!defined('TATA_TRIVIA_PATHS_LOADED')) {
    require_once TATA_TRIVIA_ROOT . '/config/paths.php';
    define('TATA_TRIVIA_PATHS_LOADED', true);
}

// Conexión a la base de datos específica de trivia
function getTriviaDatabaseConnection() {
    static $conn;
    
    if (!$conn) {
        $servername = "127.0.0.1";
        $username = "root";
        $password = "";
        $dbname = "tata_trivia";
        
        try {
            $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            error_log("Error de conexión Tata Trivia: " . $e->getMessage());
            return null;
        }
    }
    
    return $conn;
}

// Función helper para cargar vistas
function loadTriviaView($viewName, $data = []) {
    extract($data);
    $viewFile = TATA_TRIVIA_VIEWS . '/' . $viewName . '.php';
    
    if (file_exists($viewFile)) {
        require_once $viewFile;
    } else {
        error_log("Vista no encontrada: " . $viewFile);
        http_response_code(404);
        echo "Vista no encontrada: " . $viewName;
    }
}

// Incluir controladores con verificación
if (!class_exists('TriviaController')) {
    require_once TATA_TRIVIA_CONTROLLERS . '/TriviaController.php';
}
if (!class_exists('HostController')) {
    require_once TATA_TRIVIA_CONTROLLERS . '/HostController.php';
}
if (!class_exists('PlayerController')) {
    require_once TATA_TRIVIA_CONTROLLERS . '/PlayerController.php';
}

// Verificar si el usuario está logueado en Microapps (sin conflictos)
function getTriviaMicroappsUser() {
    // Primero intentar con la sesión de Microapps
    if (isset($_SESSION['user_id'])) {
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'] ?? '',
            'first_name' => $_SESSION['first_name'] ?? '',
            'last_name' => $_SESSION['last_name'] ?? '',
            'work_area' => $_SESSION['work_area'] ?? '',
            'avatar' => $_SESSION['avatar'] ?? ''
        ];
    }
    
    // Si no hay sesión, permitir acceso anónimo
    return [
        'id' => null,
        'username' => 'Invitado',
        'first_name' => 'Jugador',
        'last_name' => '',
        'work_area' => '',
        'avatar' => ''
    ];
}
?>