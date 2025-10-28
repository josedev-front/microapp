<?php
// microservices/tata-trivia/api/validate_code.php

require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');

$code = $_GET['code'] ?? '';

if (empty($code) || strlen($code) !== 6) {
    echo json_encode(['success' => false, 'error' => 'Código inválido']);
    exit;
}

try {
    $triviaController = new TriviaController();
    
    // Buscar trivia por código
    $db = getTriviaDatabaseConnection();
    $stmt = $db->prepare("
        SELECT id, title, theme, game_mode, background_image, status 
        FROM trivias 
        WHERE join_code = ? AND status IN ('setup', 'waiting', 'active')
    ");
    $stmt->execute([$code]);
    $trivia = $stmt->fetch();
    
    if ($trivia) {
        // Obtener imagen de fondo
        $backgroundImage = $triviaController->getBackgroundImagePath($trivia['id']);
        
        echo json_encode([
            'success' => true,
            'trivia' => [
                'id' => $trivia['id'],
                'title' => $trivia['title'],
                'theme' => $trivia['theme'],
                'game_mode' => $trivia['game_mode'],
                'status' => $trivia['status'],
                'background_image' => $backgroundImage
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Código no encontrado']);
    }
    
} catch (Exception $e) {
    error_log("Error in validate_code.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error del servidor']);
}
?>