<?php
// microservices/tata-trivia/api/player_communication.php

require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? $_GET['action'] ?? '';
    $trivia_id = $input['trivia_id'] ?? $_GET['trivia_id'] ?? '';
    
    if (empty($trivia_id)) {
        throw new Exception('trivia_id requerido');
    }
    
    if (!class_exists('TriviaController')) {
        throw new Exception('Sistema no disponible');
    }
    
    $controller = new TriviaController();
    $trivia = $controller->getTriviaById($trivia_id);
    
    if (!$trivia) {
        throw new Exception('Trivia no encontrada');
    }
    
    switch($action) {
        case 'get_game_state':
            // Jugadores consultan el estado actual del juego
            $current_index = $controller->getCurrentQuestionIndex($trivia_id);
            $questions = $controller->getTriviaQuestions($trivia_id);
            
            $current_question = null;
            if ($current_index >= 0 && $current_index < count($questions)) {
                $current_question = $questions[$current_index];
            }
            
            echo json_encode([
                'success' => true,
                'trivia_status' => $trivia['status'],
                'current_question_index' => $current_index,
                'current_question' => $current_question,
                'timestamp' => time()
            ]);
            break;
            
        case 'submit_answer':
            // Jugadores envían respuestas
            $player_id = $input['player_id'] ?? '';
            $question_id = $input['question_id'] ?? '';
            $option_id = $input['option_id'] ?? '';
            $response_time = $input['response_time'] ?? 0;
            
            if (empty($player_id) || empty($question_id) || empty($option_id)) {
                throw new Exception('Datos incompletos');
            }
            
            // Verificar si la respuesta es correcta
            $db = getTriviaDatabaseConnection();
            $stmt = $db->prepare("SELECT is_correct FROM question_options WHERE id = ?");
            $stmt->execute([$option_id]);
            $option = $stmt->fetch();
            $is_correct = $option ? (bool)$option['is_correct'] : false;
            
            // Guardar respuesta
            $result = $controller->recordPlayerAnswer($player_id, $question_id, $option_id, $is_correct, $response_time);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'is_correct' => $is_correct,
                    'message' => 'Respuesta registrada'
                ]);
            } else {
                throw new Exception('Error al guardar respuesta');
            }
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    error_log("Error en player_communication: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>