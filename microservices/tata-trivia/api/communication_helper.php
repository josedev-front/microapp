<?php
// microservices/tata-trivia/api/communication_helper.php
function broadcastToPlayers($triviaId, $event, $data) {
    $message = [
        'type' => $event,
        'data' => $data,
        'timestamp' => time(),
        'trivia_id' => $triviaId
    ];
    
    // Almacenar en base de datos para persistencia
    file_put_contents(__DIR__ . "/../data/comms/{$triviaId}_last_broadcast.json", json_encode($message));
    
    return $message;
}

function getLastBroadcast($triviaId) {
    $file = __DIR__ . "/../data/comms/{$triviaId}_last_broadcast.json";
    if (file_exists($file)) {
        return json_decode(file_get_contents($file), true);
    }
    return null;
}
?>