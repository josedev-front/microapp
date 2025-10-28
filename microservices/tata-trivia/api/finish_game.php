<?php
require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$trivia_id = $input['trivia_id'] ?? '';

if (empty($trivia_id)) {
    echo json_encode(['success' => false, 'error' => 'Trivia ID requerido']);
    exit;
}

try {
    $triviaController = new TriviaController();
    $result = $triviaController->finishGame($trivia_id);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Error in finish_game.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error del servidor']);
}
?>