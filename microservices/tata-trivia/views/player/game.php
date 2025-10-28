<?php
// microservices/tata-trivia/views/player/game.php - VERSIÓN CORREGIDA

$trivia_id = $_GET['trivia_id'] ?? '';
$player_id = $_SESSION['player_id'] ?? '';

// Verificar que tenemos los datos necesarios
if (empty($trivia_id) || empty($player_id)) {
    // Intentar obtener de la sesión
    $trivia_id = $_SESSION['trivia_id'] ?? '';
    $player_id = $_SESSION['player_id'] ?? '';
    
    if (empty($trivia_id) || empty($player_id)) {
        // Redirigir a unirse si no hay datos
        header('Location: /microservices/tata-trivia/player/join');
        exit;
    }
}

// Verificar que el jugador existe en la base de datos
try {
    $db = getTriviaDatabaseConnection();
    $stmt = $db->prepare("
        SELECT p.*, t.title as trivia_title, t.status as trivia_status
        FROM players p
        JOIN trivias t ON p.trivia_id = t.id
        WHERE p.id = ? AND p.trivia_id = ?
    ");
    $stmt->execute([$player_id, $trivia_id]);
    $player_data = $stmt->fetch();
    
    if (!$player_data) {
        throw new Exception('Jugador no encontrado en esta trivia');
    }
    
    // Verificar que la trivia esté activa
    if (!in_array($player_data['trivia_status'], ['waiting', 'active'])) {
        throw new Exception('Esta trivia no está disponible');
    }
    
    // Redirigir al juego real
    header('Location: /microservices/tata-trivia/player/game_player?trivia_id=' . $trivia_id);
    exit;
    
} catch (Exception $e) {
    // Si hay error, redirigir a unirse
    error_log("Error en game.php: " . $e->getMessage());
    unset($_SESSION['player_id'], $_SESSION['trivia_id']);
    header('Location: /microservices/tata-trivia/player/join');
    exit;
}
?>