<?php
// microservices/tata-trivia/api/game_communication.php

require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');

class GameCommunication {
    private $db;
    
    public function __construct() {
        $this->db = getTriviaDatabaseConnection();
    }
    
    public function broadcastToPlayers($triviaId, $event, $data) {
        try {
            // Guardar evento en base de datos para que los jugadores lo consulten
            $stmt = $this->db->prepare("
                INSERT INTO game_events 
                (trivia_id, event_type, event_data, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            
            $eventData = json_encode([
                'type' => $event,
                'data' => $data,
                'timestamp' => time()
            ]);
            
            return $stmt->execute([$triviaId, $event, $eventData]);
            
        } catch (Exception $e) {
            error_log("Error broadcasting: " . $e->getMessage());
            return false;
        }
    }
    
    public function getPendingEvents($triviaId, $lastEventId = 0) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM game_events 
                WHERE trivia_id = ? AND id > ?
                ORDER BY id ASC
            ");
            $stmt->execute([$triviaId, $lastEventId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error getting events: " . $e->getMessage());
            return [];
        }
    }
}

// Para crear la tabla game_events, ejecuta este SQL:
/*
CREATE TABLE game_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trivia_id INT NOT NULL,
    event_type VARCHAR(50) NOT NULL,
    event_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (trivia_id, created_at)
);
*/
?>