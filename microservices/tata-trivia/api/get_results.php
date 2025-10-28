<?php
// microservices/tata-trivia/api/get_results.php - VERSIÓN MEJORADA

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function sendError($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $message
    ]);
    exit;
}

function sendSuccess($data = []) {
    echo json_encode(array_merge(['success' => true], $data));
    exit;
}

try {
    require_once __DIR__ . '/../init.php';
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendError('Método no permitido. Use POST.', 405);
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    
    if (empty($input['trivia_id'])) {
        sendError("Parámetro 'trivia_id' requerido");
    }

    $trivia_id = intval($input['trivia_id']);
    $player_id = $input['player_id'] ?? null;
    
    if (!class_exists('TriviaController')) {
        sendError('Sistema de trivia no disponible', 500);
    }
    
    $controller = new TriviaController();
    $trivia = $controller->getTriviaById($trivia_id);
    
    if (!$trivia) {
        sendError('Trivia no encontrada');
    }
    
    // Usar la NUEVA función getFinalResults que obtiene datos REALES de la base de datos
    $results = $controller->getFinalResults($trivia_id);
    
    // Si no hay resultados, intentar con getLeaderboard como fallback
    if (empty($results)) {
        $results = $controller->getLeaderboard($trivia_id);
    }
    
    // Encontrar rank del jugador actual si se proporcionó
    $player_rank = 0;
    if ($player_id) {
        $player_rank = $controller->getPlayerRank($trivia_id, $player_id);
    }
    
    sendSuccess([
        'results' => $results,
        'player_rank' => $player_rank,
        'total_players' => count($results)
    ]);

} catch (Exception $e) {
    error_log("❌ ERROR en get_results: " . $e->getMessage());
    sendError('Error interno del servidor: ' . $e->getMessage(), 500);
}
?>