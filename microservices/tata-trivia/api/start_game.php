<?php
require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || empty($input['trivia_id'])) {
        throw new Exception('ID de trivia requerido');
    }

    $triviaController = new TriviaController();
    
    // Verificar que hay jugadores
    $players = $triviaController->getLobbyPlayers($input['trivia_id']);
    if (empty($players)) {
        throw new Exception('No hay jugadores en el lobby');
    }
    
    // Actualizar estado a "active"
    $success = $triviaController->updateTriviaStatus($input['trivia_id'], 'active');
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Juego iniciado exitosamente',
            'data' => [
                'players_count' => count($players)
            ]
        ]);
    } else {
        throw new Exception('No se pudo iniciar el juego');
    }

} catch (Exception $e) {
    error_log('Error starting game: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>