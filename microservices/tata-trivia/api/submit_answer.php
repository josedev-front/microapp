<?php
// microservices/trivia-play/api/submit_answer.php

header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

try {
    require_once __DIR__ . '/../config/paths.php';
    require_once __DIR__ . '/../init.php';
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['player_id']) || !isset($input['question_id']) || !isset($input['option_id'])) {
        throw new Exception('Datos incompletos');
    }
    
    $playerController = new TriviaPlay\Controllers\PlayerController();
    $result = $playerController->submitAnswer(
        $input['player_id'],
        $input['question_id'],
        $input['option_id'],
        $input['response_time'] ?? 0
    );
    
    echo json_encode([
        'success' => true,
        'correct' => $result['correct'],
        'correct_option_id' => $result['correct_option_id']
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}