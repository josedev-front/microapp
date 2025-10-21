<?php
// microservices/middy/api/chat.php - VERSIÓN ULTRA-ROBUSTA

// Evitar cualquier output antes de los headers
if (ob_get_level()) ob_clean();

// HEADERS PRIMERO - crítico
header('Content-Type: application/json; charset=utf-8');

// Manejar sesión de manera segura
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Forzar BASE_URL si no está definida
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost:3000/');
}

try {
    // 1. Verificar método HTTP
    if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido. Use POST.');
    }

    // 2. Cargar archivos del core
    $base_path = 'C:/xampp/htdocs/dashboard/vsm/microapp';
    $core_path = $base_path . '/app_core';
    
    require_once $core_path . '/config/helpers.php';
    require_once $core_path . '/php/main.php';

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
    
    // 7. Devolver resultado
    echo json_encode($result);

} catch (Exception $e) {
    // Error limpio en JSON
    $errorResponse = ['error' => $e->getMessage()];
    
    if (strpos($e->getMessage(), 'No autorizado') !== false) {
        http_response_code(401);
    } elseif (strpos($e->getMessage(), 'Método no permitido') !== false) {
        http_response_code(405);
    } else {
        http_response_code(400);
    }
    
    echo json_encode($errorResponse);
}