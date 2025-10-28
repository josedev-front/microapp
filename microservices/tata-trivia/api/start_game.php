<?php
// microservices/tata-trivia/api/start_game.php - VERSIÓN MEJORADA

require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

try {
    // Obtener datos del POST
    $input = json_decode(file_get_contents('php://input'), true);
    $trivia_id = $input['trivia_id'] ?? null;
    
    if (!$trivia_id) {
        throw new Exception('ID de trivia no proporcionado');
    }
    
    // Verificar que la trivia existe
    $triviaController = new TriviaController();
    $trivia = $triviaController->getTriviaById($trivia_id);
    
    if (!$trivia) {
        throw new Exception('Trivia no encontrada');
    }
    
    // Verificar que hay jugadores
    $players = $triviaController->getLobbyPlayers($trivia_id);
    if (empty($players)) {
        throw new Exception('No hay jugadores en el lobby');
    }
    
    // Verificar que la trivia tiene preguntas - CON MEJOR MANEJO DE ERROR
    $questions = $triviaController->getTriviaQuestions($trivia_id);
    if (empty($questions)) {
        // En lugar de lanzar excepción, redirigir a questions.php
        echo json_encode([
            'success' => false,
            'error' => 'La trivia no tiene preguntas',
            'redirect_url' => '/microservices/tata-trivia/host/questions?trivia_id=' . $trivia_id
        ]);
        exit;
    }
    
    // Actualizar estado de la trivia a "active"
    $result = $triviaController->updateTriviaStatus($trivia_id, 'active');
    
    if (!$result) {
        throw new Exception('Error al actualizar el estado de la trivia');
    }
    
    // Establecer la primera pregunta como actual
    $setQuestionResult = $triviaController->setCurrentQuestion($trivia_id, 0);
    
    if (!$setQuestionResult) {
        throw new Exception('Error al establecer la primera pregunta');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Juego iniciado correctamente',
        'trivia_id' => $trivia_id,
        'questions_count' => count($questions),
        'players_count' => count($players),
        'redirect_url' => '/microservices/tata-trivia/host/game?trivia_id=' . $trivia_id
    ]);
    
} catch (Exception $e) {
    error_log("Error en start_game.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>