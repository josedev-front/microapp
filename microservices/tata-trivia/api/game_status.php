<?php
// microservices/tata-trivia/api/game_status.php

require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');

$trivia_id = $_GET['trivia_id'] ?? null;
$player_id = $_GET['player_id'] ?? null;

if (!$trivia_id || !$player_id) {
    echo json_encode(['error' => 'Parámetros faltantes']);
    exit;
}

try {
    $triviaController = new TriviaController();
    
    // Obtener estado actual de la trivia
    $trivia = $triviaController->getTriviaById($trivia_id);
    $currentQuestionIndex = $triviaController->getCurrentQuestionIndex($trivia_id);
    
    // Verificar si hay cambios
    $status_changed = false;
    $question_changed = false;
    
    // Aquí podrías comparar con el estado anterior almacenado en sesión
    // Por simplicidad, siempre devolvemos el estado actual
    
    echo json_encode([
        'success' => true,
        'trivia_status' => $trivia['status'],
        'current_question_index' => $currentQuestionIndex,
        'status_changed' => $status_changed,
        'question_changed' => $question_changed
    ]);
    
} catch (Exception $e) {
    error_log("Error in game_status.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error del servidor'
    ]);
}
?>