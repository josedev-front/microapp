<?php
// public/api/middy_chat.php - ENDPOINT DIRECTO MEJORADO

// Headers primero
header('Content-Type: application/json; charset=utf-8');

// Permitir CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // 1. Verificar método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido. Use POST.');
    }

    // 2. Leer input
    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON inválido');
    }

    $question = trim($input['question'] ?? '');
    if (empty($question)) {
        throw new Exception('Pregunta vacía');
    }

    // 3. Cargar Middy directamente
    require_once __DIR__ . '/../../microservices/middy/init.php';
    
    // 4. Procesar (usar ID 1 para testing)
    $controller = new Middy\Controllers\ChatController();
    $result = $controller->handleChat(1, $question);
    
    // 5. Mejorar respuesta si no hay fuentes
    if (($result['sources'] ?? 0) === 0 && isset($result['answer'])) {
        // Si no encontró información en los documentos, dar una respuesta más útil
        if (stripos($result['answer'], 'no tengo información') !== false || 
            stripos($result['answer'], 'no puedo proporcionar') !== false) {
            
            $result['answer'] = "No encontré información específica sobre esto en la documentación disponible. " .
                               "Te sugiero contactar con el área correspondiente o verificar los procedimientos generales " .
                               "en los documentos de gestión.";
        }
    }
    
    // 6. Devolver resultado
    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}