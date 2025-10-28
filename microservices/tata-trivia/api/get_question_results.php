<?php
require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');

$question_id = $_GET['question_id'] ?? '';

if (empty($question_id)) {
    echo json_encode(['success' => false, 'error' => 'Question ID requerido']);
    exit;
}

try {
    $db = getTriviaDatabaseConnection();
    
    // Obtener respuesta correcta
    $stmt = $db->prepare("
        SELECT qo.option_text 
        FROM question_options qo 
        WHERE qo.question_id = ? AND qo.is_correct = 1
    ");
    $stmt->execute([$question_id]);
    $correct_option = $stmt->fetch();
    
    // Obtener jugadores que respondieron correctamente
    $stmt = $db->prepare("
        SELECT p.player_name, pa.response_time 
        FROM player_answers pa 
        JOIN players p ON pa.player_id = p.id 
        WHERE pa.question_id = ? AND pa.is_correct = 1
    ");
    $stmt->execute([$question_id]);
    $correct_players = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener jugadores que respondieron incorrectamente
    $stmt = $db->prepare("
        SELECT p.player_name 
        FROM player_answers pa 
        JOIN players p ON pa.player_id = p.id 
        WHERE pa.question_id = ? AND pa.is_correct = 0
    ");
    $stmt->execute([$question_id]);
    $incorrect_players = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'correct_answer' => $correct_option['option_text'] ?? 'No disponible',
        'correct_players' => $correct_players,
        'incorrect_players' => $incorrect_players
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_question_results.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error del servidor']);
}
?>