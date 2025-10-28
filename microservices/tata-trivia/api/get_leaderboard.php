<?php
require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');

$trivia_id = $_GET['trivia_id'] ?? '';

if (empty($trivia_id)) {
    echo json_encode(['success' => false, 'error' => 'Trivia ID requerido']);
    exit;
}

try {
    $db = getTriviaDatabaseConnection();
    $stmt = $db->prepare("
        SELECT id, player_name, avatar, score 
        FROM players 
        WHERE trivia_id = ? 
        ORDER BY score DESC, join_time ASC
    ");
    $stmt->execute([$trivia_id]);
    $players = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'players' => $players
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_leaderboard.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error del servidor']);
}
?>