<?php
// microservices/tata-trivia/api/create_trivia.php - VERSIÓN MEJORADA

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
        // Intentar con form data si json falla
        $input = $_POST;
    }

    // Validar datos requeridos
    if (empty($input['title'])) {
        throw new Exception('El título es requerido');
    }

    // Crear controlador y crear trivia
    $triviaController = new TriviaController();
    
    $hostData = [
        'user_id' => $input['user_id'] ?? ($_SESSION['user_id'] ?? null),
        'title' => $input['title'],
        'theme' => $input['theme'] ?? 'default',
        'game_mode' => $input['game_mode'] ?? 'individual',
        'max_winners' => intval($input['max_winners'] ?? 1),
        'background_image' => $input['theme'] ?? 'default'
    ];

    $result = $triviaController->createTrivia($hostData);

    if ($result['success']) {
        // Responder con éxito
        echo json_encode([
            'success' => true,
            'message' => 'Trivia creada exitosamente',
            'trivia_id' => $result['trivia_id'],
            'join_code' => $result['join_code']
        ]);
    } else {
        throw new Exception($result['error'] ?? 'Error al crear trivia');
    }

} catch (Exception $e) {
    error_log('Error creating trivia: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>