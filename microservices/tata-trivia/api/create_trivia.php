<?php
require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

try {
    // Obtener datos del POST
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Datos inválidos');
    }

    // Validar datos requeridos
    if (empty($input['title'])) {
        throw new Exception('El título es requerido');
    }

    if (empty($input['gameMode']) || !in_array($input['gameMode'], ['individual', 'teams'])) {
        throw new Exception('Modalidad de juego inválida');
    }

    // Crear controlador y crear trivia
    $triviaController = new TriviaController();
    
    $hostData = [
        'user_id' => $input['hostData']['user_id'] ?? null,
        'title' => $input['title'],
        'background_image' => $input['customImage'] ? 'custom' : ($input['theme'] ?? 'default')
    ];

    $result = $triviaController->createTrivia(
        $hostData,
        $input['theme'] ?? 'default',
        $input['gameMode'],
        intval($input['maxWinners'] ?? 1)
    );

    // Responder con éxito
    echo json_encode([
        'success' => true,
        'message' => 'Trivia creada exitosamente',
        'data' => $result
    ]);

} catch (Exception $e) {
    error_log('Error creating trivia: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>