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
    
    $response = [
        'success' => true,
        'trivia_status' => $trivia['status'],
        'title' => $trivia['title'],
        'current_question_index' => $trivia['current_question_index'] ?? -1
    ];
    
    // ✅ CORREGIDO: Incluir información de la pregunta actual si existe
    if ($trivia['current_question_index'] >= 0) {
        $questions = $triviaController->getTriviaQuestions($trivia_id);
        
        if (isset($questions[$trivia['current_question_index']])) {
            $currentQuestion = $questions[$trivia['current_question_index']];
            
            // Obtener opciones de la pregunta
            $options = $triviaController->getQuestionOptions($currentQuestion['id']);
            
            // Obtener fondo de la pregunta
            $backgroundPath = $triviaController->getQuestionBackgroundPath($currentQuestion);
            
            $response['current_question'] = [
                'id' => $currentQuestion['id'],
                'question_text' => $currentQuestion['question_text'],
                'question_type' => $currentQuestion['question_type'],
                'background_image' => $backgroundPath,
                'time_limit' => $currentQuestion['time_limit'],
                'options' => $options
            ];
        }
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log('Error getting game status: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener estado del juego'
    ]);
}
?>