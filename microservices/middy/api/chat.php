<?php
// microservices/middy/api/chat.php - VERSIÓN LIMPIA

// HEADERS PRIMERO - sin output antes
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Manejar preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Limpiar buffer de salida
while (ob_get_level()) ob_end_clean();

try {
    // 1. Cargar configurador de rutas (SILENCIOSO)
    require_once __DIR__ . '/../config/paths.php';
    
    // 2. Cargar archivos del core (SILENCIOSO)
    MiddyPathResolver::requireCoreFiles();

    // 3. Verificar autenticación
    if (!validarSesion()) {
        throw new Exception('No autorizado. Inicia sesión primero.');
    }

    // 4. Leer y validar input JSON
    $inputData = file_get_contents('php://input');
    if (empty($inputData)) {
        throw new Exception('No se recibieron datos');
    }
    
    $input = json_decode($inputData, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON inválido: ' . json_last_error_msg());
    }

    $question = trim($input['question'] ?? '');
    if (empty($question)) {
        throw new Exception('La pregunta no puede estar vacía');
    }

    // 5. Cargar Middy
    require_once __DIR__ . '/../init.php';

    // 6. Procesar con el controlador
    $controller = new Middy\Controllers\ChatController();
    $result = $controller->handleChat($_SESSION['id'], $question);
    
    // 7. Devolver resultado en JSON
    echo json_encode($result);

} catch (Exception $e) {
    // Error limpio en JSON
    $errorResponse = ['error' => $e->getMessage()];
    
    if (strpos($e->getMessage(), 'No autorizado') !== false) {
        http_response_code(401);
    } else {
        http_response_code(400);
    }
    
    echo json_encode($errorResponse);
}