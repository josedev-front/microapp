<?php
// microservices/tata-trivia/init.php - VERSIÓN CORREGIDA

// ==================================================
// CONFIGURACIÓN INICIAL Y MANEJO DE ERRORES
// ==================================================

// Evitar cualquier output no deseado
if (!headers_sent()) {
    header('Content-Type: text/html; charset=utf-8');
}

// Configuración de errores (modo desarrollo)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Evitar session_start() si ya hay una sesión activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==================================================
// CONFIGURACIÓN DE RUTAS DEL MICROSERVICIO
// ==================================================

define('TATA_TRIVIA_ROOT', dirname(__FILE__));
define('TATA_TRIVIA_VIEWS', TATA_TRIVIA_ROOT . '/views');
define('TATA_TRIVIA_API', TATA_TRIVIA_ROOT . '/api');
define('TATA_TRIVIA_ASSETS', TATA_TRIVIA_ROOT . '/assets');
define('TATA_TRIVIA_CONTROLLERS', TATA_TRIVIA_ROOT . '/controllers');
define('TATA_TRIVIA_MODELS', TATA_TRIVIA_ROOT . '/models');

// ==================================================
// INCLUSIÓN DE ARCHIVOS DE CONFIGURACIÓN
// ==================================================

// Incluir configuración de paths
if (!defined('TATA_TRIVIA_PATHS_LOADED')) {
    $paths_file = TATA_TRIVIA_ROOT . '/config/paths.php';
    if (file_exists($paths_file)) {
        require_once $paths_file;
        define('TATA_TRIVIA_PATHS_LOADED', true);
    } else {
        error_log("ERROR: paths.php no encontrado en " . $paths_file);
        // Valores por defecto
        define('TATA_TRIVIA_BASE_URL', '/microservices/tata-trivia');
        define('TATA_TRIVIA_ASSETS_URL', TATA_TRIVIA_BASE_URL . '/assets');
    }
}

// ==================================================
// CONEXIÓN A BASE DE DATOS TATA_TRIVIA
// ==================================================

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
            $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            
            error_log("✅ Conexión a BD Tata Trivia establecida");
        } catch(PDOException $e) {
            error_log("❌ Error de conexión Tata Trivia: " . $e->getMessage());
            return null;
        }
    }
    
    return $conn;
}

// ==================================================
// CARGA AUTOMÁTICA DE MODELOS - ESTO ES LO QUE FALTA
// ==================================================

function loadTriviaModels() {
    $modelsDir = TATA_TRIVIA_MODELS;
    
    if (!is_dir($modelsDir)) {
        error_log("❌ Directorio de modelos no existe: " . $modelsDir);
        return false;
    }
    
    $modelFiles = scandir($modelsDir);
    $loadedModels = [];
    
    foreach ($modelFiles as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
            $modelPath = $modelsDir . '/' . $file;
            $modelName = pathinfo($file, PATHINFO_FILENAME);
            
            // Verificar que el archivo sea legible
            if (!is_readable($modelPath)) {
                error_log("❌ Modelo no legible: " . $modelPath);
                continue;
            }
            
            // Verificar sintaxis antes de incluir
            $syntaxCheck = shell_exec('php -l ' . escapeshellarg($modelPath) . ' 2>&1');
            if (strpos($syntaxCheck, 'No syntax errors') === false) {
                error_log("❌ Error de sintaxis en modelo $modelName: " . $syntaxCheck);
                continue;
            }
            
            // Incluir el modelo
            require_once $modelPath;
            
            // Verificar que la clase existe después de incluir
            if (class_exists($modelName)) {
                $loadedModels[] = $modelName;
                error_log("✅ Modelo cargado: " . $modelName);
            } else {
                error_log("❌ Clase $modelName no definida después de incluir archivo");
            }
        }
    }
    
    return $loadedModels;
}

// ==================================================
// CARGA DE CONTROLADORES - VERSIÓN ROBUSTA
// ==================================================

// Función para cargar controladores de forma segura
function loadTriviaController($controllerName) {
    $controllerFile = TATA_TRIVIA_CONTROLLERS . '/' . $controllerName . '.php';
    
    if (!file_exists($controllerFile)) {
        error_log("❌ Archivo de controlador no encontrado: " . $controllerFile);
        return false;
    }
    
    // Verificar sintaxis del archivo antes de incluirlo
    $syntax_check = shell_exec('php -l ' . escapeshellarg($controllerFile) . ' 2>&1');
    if (strpos($syntax_check, 'No syntax errors') === false) {
        error_log("❌ Error de sintaxis en " . $controllerName . ": " . $syntax_check);
        return false;
    }
    
    // Incluir el archivo
    require_once $controllerFile;
    
    // Verificar que la clase existe después de incluir
    if (!class_exists($controllerName)) {
        error_log("❌ Clase " . $controllerName . " no definida después de incluir el archivo");
        return false;
    }
    
    error_log("✅ Controlador " . $controllerName . " cargado correctamente");
    return true;
}

// ==================================================
// INICIALIZACIÓN DEL SISTEMA - ORDEN CORRECTO
// ==================================================

// 1. PRIMERO cargar modelos
error_log("📦 Cargando modelos...");
$loadedModels = loadTriviaModels();
error_log("📦 Modelos cargados: " . implode(', ', $loadedModels));

// 2. LUEGO cargar controladores
$essentialControllers = ['TriviaController', 'HostController', 'PlayerController'];
foreach ($essentialControllers as $controller) {
    if (!class_exists($controller)) {
        loadTriviaController($controller);
    }
}

// Verificación final de controladores cargados
if (!class_exists('TriviaController')) {
    error_log("❌ CRÍTICO: TriviaController no disponible después de todos los intentos");
    
    // Crear controlador de emergencia mínimo
    if (!class_exists('TriviaController')) {
        class TriviaController {
            public function __construct() {
                throw new Exception("Sistema de trivia no disponible. Contacte al administrador.");
            }
        }
    }
}

// ==================================================
// FUNCIONES AUXILIARES DEL SISTEMA
// ==================================================

/**
 * Cargar vista del sistema de trivia
 */
function loadTriviaView($viewName, $data = []) {
    // Extraer variables para la vista
    if (!empty($data)) {
        extract($data);
    }
    
    $viewFile = TATA_TRIVIA_VIEWS . '/' . $viewName . '.php';
    
    if (file_exists($viewFile)) {
        require_once $viewFile;
    } else {
        error_log("❌ Vista no encontrada: " . $viewFile);
        http_response_code(404);
        
        // Vista de error amigable
        echo '<!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>Error - Vista no encontrada</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body class="bg-light">
            <div class="container mt-5">
                <div class="alert alert-danger">
                    <h4><i class="fas fa-exclamation-triangle"></i> Vista no encontrada</h4>
                    <p>La vista <strong>' . htmlspecialchars($viewName) . '</strong> no existe.</p>
                    <a href="/microservices/tata-trivia/" class="btn btn-primary">Volver al Inicio</a>
                </div>
            </div>
        </body>
        </html>';
    }
    
    exit;
}

/**
 * Obtener información del usuario de Microapps
 */
function getTriviaMicroappsUser() {
    // Primero intentar con la sesión de Microapps
    if (isset($_SESSION['user_id'])) {
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'] ?? '',
            'first_name' => $_SESSION['first_name'] ?? 'Usuario',
            'last_name' => $_SESSION['last_name'] ?? '',
            'work_area' => $_SESSION['work_area'] ?? '',
            'avatar' => $_SESSION['avatar'] ?? 'default1',
            'email' => $_SESSION['email'] ?? ''
        ];
    }
    
    // Si no hay sesión, permitir acceso anónimo (modo invitado)
    return [
        'id' => null,
        'username' => 'Invitado',
        'first_name' => 'Jugador',
        'last_name' => '',
        'work_area' => '',
        'avatar' => 'default1',
        'email' => ''
    ];
}

/**
 * Función para APIs - preparar respuesta JSON limpia
 */
function sendJsonResponse($data, $httpCode = 200) {
    // Limpiar buffers de output
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Headers para API
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($httpCode);
    
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Función para log de eventos del sistema
 */
function logTriviaEvent($event, $details = []) {
    $logMessage = date('Y-m-d H:i:s') . " | TATA_TRIVIA | " . $event;
    
    if (!empty($details)) {
        $logMessage .= " | " . json_encode($details, JSON_UNESCAPED_UNICODE);
    }
    
    error_log($logMessage);
}

/**
 * Generar código único para trivias
 */
function generateUniqueCode($length = 6) {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';
    
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $code;
}

/**
 * Validar si una trivia existe y está activa
 */
function validateTrivia($triviaId) {
    try {
        $db = getTriviaDatabaseConnection();
        if (!$db) return false;
        
        $stmt = $db->prepare("SELECT id, status FROM trivias WHERE id = ?");
        $stmt->execute([$triviaId]);
        $trivia = $stmt->fetch();
        
        return $trivia && in_array($trivia['status'], ['setup', 'waiting', 'active']);
    } catch (Exception $e) {
        error_log("Error validando trivia: " . $e->getMessage());
        return false;
    }
}

// ==================================================
// CONFIGURACIONES ESPECÍFICAS DEL JUEGO
// ==================================================

// Temas disponibles para trivias


// Modos de juego
define('TATA_TRIVIA_GAME_MODES', [
    'individual' => 'Competencia Individual',
    'teams' => 'Competencia por Equipos'
]);

// Avatares predeterminados
define('TATA_TRIVIA_AVATARS', [
    'default1', 'default2', 'default3', 'default4', 'default5', 'default6'
]);

// ==================================================
// INICIALIZACIÓN FINAL Y VERIFICACIONES
// ==================================================

// Log de inicialización exitosa
logTriviaEvent("SISTEMA_INICIADO", [
    'session_id' => session_id(),
    'models_cargados' => $loadedModels,
    'controllers_cargados' => [
        'TriviaController' => class_exists('TriviaController'),
        'HostController' => class_exists('HostController'),
        'PlayerController' => class_exists('PlayerController')
    ],
    'bd_conectada' => (getTriviaDatabaseConnection() !== null)
]);

// Mensaje de debug en desarrollo
if (isset($_GET['debug']) && $_GET['debug'] === 'init') {
    error_log("=== DEBUG TATA_TRIVIA_INIT ===");
    error_log("Session ID: " . session_id());
    error_log("Models loaded: " . implode(', ', $loadedModels));
    error_log("TriviaController: " . (class_exists('TriviaController') ? 'OK' : 'FALLO'));
    error_log("Database: " . (getTriviaDatabaseConnection() ? 'OK' : 'FALLO'));
    error_log("==============================");
}
function makeLocalRequest($url, $data = null) {
    $base_url = 'http://localhost:3000';
    $full_url = $base_url . $url;
    
    $context_options = [
        'http' => [
            'method' => $data ? 'POST' : 'GET',
            'timeout' => 30,
        ]
    ];
    
    if ($data) {
        $context_options['http']['header'] = 'Content-Type: application/json';
        $context_options['http']['content'] = json_encode($data);
    }
    
    $context = stream_context_create($context_options);
    
    try {
        $response = file_get_contents($full_url, false, $context);
        return json_decode($response, true);
    } catch (Exception $e) {
        error_log("Error en makeLocalRequest: " . $e->getMessage());
        return ['success' => false, 'error' => 'Error de conexión'];
    }
}
?>