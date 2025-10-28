<?php
// microservices/tata-trivia/api/validate_code.php

require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $join_code = $input['join_code'] ?? $_GET['join_code'] ?? '';
    
    if (empty($join_code)) {
        echo json_encode([
            'success' => false,
            'error' => 'Código de unión requerido'
        ]);
        exit;
    }
    
    if (!class_exists('TriviaController')) {
        throw new Exception('Sistema no disponible');
    }
    
    $controller = new TriviaController();
    
    // Buscar trivia por código
    $db = getTriviaDatabaseConnection();
    $stmt = $db->prepare("
        SELECT id, title, status, game_mode, theme 
        FROM trivias 
        WHERE join_code = ? AND status IN ('setup', 'waiting', 'active')
    ");
    $stmt->execute([$join_code]);
    $trivia = $stmt->fetch();
    
    if ($trivia) {
        echo json_encode([
            'success' => true,
            'trivia' => [
                'id' => $trivia['id'],
                'title' => $trivia['title'],
                'status' => $trivia['status'],
                'game_mode' => $trivia['game_mode'],
                'theme' => $trivia['theme']
            ],
            'message' => 'Código válido'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Código inválido o juego no disponible'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error en validate_code: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
?>