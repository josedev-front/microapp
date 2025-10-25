<?php
require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');

$trivia_id = $_GET['trivia_id'] ?? '';

if (empty($trivia_id)) {
    echo json_encode(['success' => false, 'error' => 'ID de trivia requerido']);
    exit;
}

try {
    $triviaController = new TriviaController();
    $trivia = $triviaController->getTriviaById($trivia_id);
    
    if (!$trivia) {
        echo json_encode(['success' => false, 'error' => 'Trivia no encontrada']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'status' => $trivia['status'],
            'title' => $trivia['title']
        ]
    ]);
    
} catch (Exception $e) {
    error_log('Error getting game status: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener estado del juego'
    ]);
}
?>