<?php
require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');

$code = $_GET['code'] ?? '';

if (empty($code)) {
    echo json_encode(['success' => false, 'error' => 'Código requerido']);
    exit;
}

try {
    $triviaController = new TriviaController();
    $trivia = $triviaController->getTriviaByCode($code);
    
    if (!$trivia) {
        echo json_encode(['success' => false, 'error' => 'Código inválido']);
        exit;
    }
    
    if ($trivia['status'] !== 'waiting') {
        echo json_encode(['success' => false, 'error' => 'La partida no está disponible para unirse']);
        exit;
    }
    
    // Obtener información del anfitrión si está disponible
    $host_name = 'Anfitrión';
    if ($trivia['host_id']) {
        // Aquí podrías obtener el nombre del host de la base de datos de microapps
        // Por ahora usamos un valor por defecto
        $host_name = 'Anfitrión';
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'trivia_id' => $trivia['id'],
            'title' => $trivia['title'],
            'game_mode' => $trivia['game_mode'],
            'host_name' => $host_name,
            'max_winners' => $trivia['max_winners']
        ]
    ]);
    
} catch (Exception $e) {
    error_log('Error validating code: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error al validar el código'
    ]);
}
?>