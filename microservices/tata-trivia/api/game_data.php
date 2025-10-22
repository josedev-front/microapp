<?php
// microservices/trivia-play/api/game_data.php

header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

try {
    require_once __DIR__ . '/../config/paths.php';
    require_once __DIR__ . '/../init.php';
    
    $triviaId = $_GET['trivia_id'] ?? null;
    $questionId = $_GET['question_id'] ?? null;
    
    if (!$triviaId || !$questionId) {
        throw new Exception('Parámetros incompletos');
    }
    
    $hostController = new TriviaPlay\Controllers\HostController();
    $playerController = new TriviaPlay\Controllers\PlayerController();
    
    // Obtener respuestas en tiempo real
    $responses = $hostController->getQuestionResponses($questionId);
    $leaderboard = $playerController->getMiniLeaderboard($triviaId);
    $stats = $hostController->getQuestionStats($questionId);
    
    echo json_encode([
        'success' => true,
        'responses' => $responses,
        'leaderboard' => $leaderboard,
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}