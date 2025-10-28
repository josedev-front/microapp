<?php
// microservices/tata-trivia/api.php
header('Content-Type: application/json; charset=utf-8');

// Redirigir todas las llamadas API al controlador correcto
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);

// Determinar qué endpoint llamar
if (strpos($path, 'player_communication') !== false) {
    require_once __DIR__ . '/api/player_communication.php';
} else if (strpos($path, 'game_actions') !== false) {
    require_once __DIR__ . '/api/game_actions.php';
} else if (strpos($path, 'get_results') !== false) {
    require_once __DIR__ . '/api/get_results.php';
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Endpoint no encontrado']);
}
?>