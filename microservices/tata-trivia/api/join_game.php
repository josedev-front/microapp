<?php
require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || empty($input['join_code']) || empty($input['player_name'])) {
        throw new Exception('Datos incompletos');
    }

    $triviaController = new TriviaController();
    
    $playerData = [
        'user_id' => $input['user_id'] ?? null,
        'player_name' => $input['player_name'],
        'team_name' => $input['team_name'] ?? null,
        'avatar' => $input['avatar'] ?? 'default1'
    ];

    $result = $triviaController->joinTrivia($input['join_code'], $playerData);

    echo json_encode([
        'success' => true,
        'message' => 'Te has unido exitosamente a la trivia',
        'data' => $result
    ]);

} catch (Exception $e) {
    error_log('Error joining game: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>