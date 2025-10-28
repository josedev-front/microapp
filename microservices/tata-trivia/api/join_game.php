<?php
// microservices/tata-trivia/api/join_game.php

require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $required = ['join_code', 'player_name', 'avatar'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            throw new Exception("Campo requerido faltante: $field");
        }
    }
    
    $join_code = $input['join_code'];
    $player_name = $input['player_name'];
    $avatar = $input['avatar'];
    $team_name = $input['team_name'] ?? null;
    $work_area = $input['work_area'] ?? '';
    $user_id = $input['user_id'] ?? null;
    
    if (!class_exists('TriviaController')) {
        throw new Exception('Sistema no disponible');
    }
    
    $db = getTriviaDatabaseConnection();
    
    // Buscar trivia por código
    $stmt = $db->prepare("
        SELECT id, status 
        FROM trivias 
        WHERE join_code = ? AND status IN ('setup', 'waiting', 'active')
    ");
    $stmt->execute([$join_code]);
    $trivia = $stmt->fetch();
    
    if (!$trivia) {
        throw new Exception('Código inválido o juego no disponible');
    }
    
    $trivia_id = $trivia['id'];
    
    // Verificar si el jugador ya existe en esta trivia
    $stmt = $db->prepare("
        SELECT id FROM players 
        WHERE trivia_id = ? AND player_name = ?
    ");
    $stmt->execute([$trivia_id, $player_name]);
    $existing_player = $stmt->fetch();
    
    if ($existing_player) {
        // Jugador ya existe, usar ese ID
        $player_id = $existing_player['id'];
        
        // Actualizar datos del jugador
        $stmt = $db->prepare("
            UPDATE players 
            SET avatar = ?, team_name = ?, work_area = ?, user_id = ?
            WHERE id = ?
        ");
        $stmt->execute([$avatar, $team_name, $work_area, $user_id, $player_id]);
        
    } else {
        // Crear nuevo jugador
        $stmt = $db->prepare("
            INSERT INTO players 
            (trivia_id, user_id, player_name, team_name, avatar, work_area, score, join_time)
            VALUES (?, ?, ?, ?, ?, ?, 0, NOW())
        ");
        $stmt->execute([
            $trivia_id, 
            $user_id, 
            $player_name, 
            $team_name, 
            $avatar, 
            $work_area
        ]);
        
        $player_id = $db->lastInsertId();
    }
    
    // Guardar player_id en sesión
    $_SESSION['player_id'] = $player_id;
    $_SESSION['player_name'] = $player_name;
    $_SESSION['trivia_id'] = $trivia_id;
    
    echo json_encode([
        'success' => true,
        'player_id' => $player_id,
        'trivia_id' => $trivia_id,
        'message' => 'Unido al juego exitosamente'
    ]);
    
} catch (Exception $e) {
    error_log("Error en join_game: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>