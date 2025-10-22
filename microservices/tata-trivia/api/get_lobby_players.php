<?php
// microservices/trivia-play/api/get_lobby_players.php

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
    
    if (!isset($_GET['trivia_id']) || empty($_GET['trivia_id'])) {
        throw new Exception('ID de trivia no especificado');
    }
    
    $trivia_id = intval($_GET['trivia_id']);
    $hostController = new TriviaPlay\Controllers\HostController();
    $players = $hostController->getLobbyPlayers($trivia_id);
    
    echo json_encode([
        'success' => true,
        'players' => $players
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}