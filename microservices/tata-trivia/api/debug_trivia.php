<?php
// microservices/tata-trivia/api/debug_trivia.php

require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');

$trivia_id = $_GET['trivia_id'] ?? null;

if (!$trivia_id) {
    echo json_encode(['error' => 'No trivia_id provided']);
    exit;
}

try {
    $triviaController = new TriviaController();
    
    $data = [
        'trivia_id' => $trivia_id,
        'trivia_exists' => false,
        'trivia_data' => null,
        'questions_count' => 0,
        'trivia_status' => 'unknown',
        'current_question' => null
    ];
    
    $trivia = $triviaController->getTriviaById($trivia_id);
    
    if ($trivia) {
        $data['trivia_exists'] = true;
        $data['trivia_data'] = $trivia;
        $data['trivia_status'] = $trivia['status'] ?? 'unknown';
        
        $questions = $triviaController->getTriviaQuestions($trivia_id);
        $data['questions_count'] = count($questions);
        $data['current_question'] = $triviaController->getCurrentQuestionIndex($trivia_id);
    }
    
    echo json_encode($data);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>