<?php
class TriviaController {
    private $db;

    public function __construct() {
        $this->connectDB();
    }

    private function connectDB() {
        try {
            // Usar la función de conexión específica de trivia para evitar conflictos
            $this->db = getTriviaDatabaseConnection();
            if (!$this->db) {
                throw new Exception("No se pudo conectar a la base de datos de trivia");
            }
        } catch (Exception $e) {
            throw new Exception("Error de conexión a la base de datos de trivia: " . $e->getMessage());
        }
    }

    // Generar código único para unirse
    public function generateJoinCode() {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';
        
        do {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= $characters[rand(0, strlen($characters) - 1)];
            }
            
            // Verificar si el código ya existe
            $stmt = $this->db->prepare("SELECT id FROM trivias WHERE join_code = ?");
            $stmt->execute([$code]);
        } while ($stmt->fetch());
        
        return $code;
    }

    // Crear nueva trivia
    public function createTrivia($hostData, $theme, $gameMode, $maxWinners = 1) {
        $joinCode = $this->generateJoinCode();
        
        $stmt = $this->db->prepare("
            INSERT INTO trivias (host_id, title, theme, game_mode, max_winners, background_image, join_code, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'setup')
        ");
        
        $stmt->execute([
            $hostData['user_id'] ?? null,
            $hostData['title'] ?? 'Trivia sin título',
            $theme,
            $gameMode,
            $maxWinners,
            $hostData['background_image'] ?? '',
            $joinCode
        ]);
        
        return [
            'trivia_id' => $this->db->lastInsertId(),
            'join_code' => $joinCode
        ];
    }

    // Agregar pregunta a trivia
    public function addQuestion($triviaId, $questionData) {
        $stmt = $this->db->prepare("
            INSERT INTO questions (trivia_id, question_text, question_type, background_image, time_limit, order_index) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $triviaId,
            $questionData['question_text'],
            $questionData['question_type'],
            $questionData['background_image'] ?? '',
            $questionData['time_limit'] ?? 30,
            $questionData['order_index'] ?? 0
        ]);
        
        $questionId = $this->db->lastInsertId();
        
        // Agregar opciones para la pregunta
        if (isset($questionData['options']) && is_array($questionData['options'])) {
            foreach ($questionData['options'] as $option) {
                $this->addQuestionOption($questionId, $option);
            }
        }
        
        return $questionId;
    }

    // Agregar opción a pregunta
    private function addQuestionOption($questionId, $optionData) {
        $stmt = $this->db->prepare("
            INSERT INTO question_options (question_id, option_text, is_correct) 
            VALUES (?, ?, ?)
        ");
        
        $stmt->execute([
            $questionId,
            $optionData['text'],
            $optionData['is_correct'] ? 1 : 0
        ]);
    }

    // Unirse a trivia
    public function joinTrivia($joinCode, $playerData) {
        // Obtener trivia por código
        $stmt = $this->db->prepare("
            SELECT id, game_mode, status 
            FROM trivias 
            WHERE join_code = ? AND status IN ('setup', 'waiting')
        ");
        $stmt->execute([$joinCode]);
        $trivia = $stmt->fetch();
        
        if (!$trivia) {
            throw new Exception("Código inválido o partida no disponible");
        }
        
        // Verificar si el jugador ya existe
        $stmt = $this->db->prepare("
            SELECT id FROM players 
            WHERE trivia_id = ? AND (user_id = ? OR player_name = ?)
        ");
        $stmt->execute([
            $trivia['id'], 
            $playerData['user_id'] ?? null, 
            $playerData['player_name']
        ]);
        
        if ($stmt->fetch()) {
            throw new Exception("Ya estás unido a esta partida");
        }
        
        // Unir jugador
        $stmt = $this->db->prepare("
            INSERT INTO players (trivia_id, user_id, player_name, team_name, avatar, score) 
            VALUES (?, ?, ?, ?, ?, 0)
        ");
        
        $stmt->execute([
            $trivia['id'],
            $playerData['user_id'] ?? null,
            $playerData['player_name'],
            $playerData['team_name'] ?? null,
            $playerData['avatar'] ?? ''
        ]);
        
        return [
            'player_id' => $this->db->lastInsertId(),
            'trivia_id' => $trivia['id'],
            'game_mode' => $trivia['game_mode']
        ];
    }

    // Obtener jugadores en lobby
    public function getLobbyPlayers($triviaId) {
        $stmt = $this->db->prepare("
            SELECT id, player_name, team_name, avatar, join_time 
            FROM players 
            WHERE trivia_id = ? 
            ORDER BY join_time ASC
        ");
        $stmt->execute([$triviaId]);
        return $stmt->fetchAll();
    }

    // Obtener preguntas de trivia
    public function getTriviaQuestions($triviaId) {
        $stmt = $this->db->prepare("
            SELECT q.*, 
                   GROUP_CONCAT(CONCAT(qo.option_text, ':', qo.is_correct) SEPARATOR '|') as options_data
            FROM questions q
            LEFT JOIN question_options qo ON q.id = qo.question_id
            WHERE q.trivia_id = ?
            GROUP BY q.id
            ORDER BY q.order_index ASC
        ");
        $stmt->execute([$triviaId]);
        
        $questions = $stmt->fetchAll();
        
        // Procesar opciones
        foreach ($questions as &$question) {
            $options = [];
            if ($question['options_data']) {
                $optionPairs = explode('|', $question['options_data']);
                foreach ($optionPairs as $pair) {
                    list($text, $isCorrect) = explode(':', $pair);
                    $options[] = [
                        'text' => $text,
                        'is_correct' => (bool)$isCorrect
                    ];
                }
            }
            $question['options'] = $options;
            unset($question['options_data']);
        }
        
        return $questions;
    }

    // Registrar respuesta de jugador
    public function submitAnswer($playerId, $questionId, $selectedOptionId, $responseTime, $isCorrect) {
        $stmt = $this->db->prepare("
            INSERT INTO player_answers (player_id, question_id, selected_option_id, response_time, is_correct) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $playerId,
            $questionId,
            $selectedOptionId,
            $responseTime,
            $isCorrect ? 1 : 0
        ]);
        
        // Actualizar puntaje si es correcto
        if ($isCorrect) {
            $points = max(10 - floor($responseTime / 1000), 1); // Más puntos por responder rápido
            $this->updatePlayerScore($playerId, $points);
        }
        
        return $this->db->lastInsertId();
    }

    // Actualizar puntaje del jugador
    private function updatePlayerScore($playerId, $points) {
        $stmt = $this->db->prepare("
            UPDATE players 
            SET score = score + ? 
            WHERE id = ?
        ");
        $stmt->execute([$points, $playerId]);
    }

    // Obtener historial del jugador
    public function getPlayerHistory($userId) {
        $stmt = $this->db->prepare("
            SELECT gh.*, t.title, t.theme, t.created_at as game_date
            FROM game_history gh
            JOIN trivias t ON gh.trivia_id = t.id
            WHERE gh.player_id IN (SELECT id FROM players WHERE user_id = ?)
            ORDER BY gh.played_at DESC
            LIMIT 20
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    // Obtener historial del anfitrión
    public function getHostHistory($userId) {
        $stmt = $this->db->prepare("
            SELECT t.*, COUNT(p.id) as player_count,
                   (SELECT player_name FROM players p2 
                    JOIN game_history gh ON p2.id = gh.player_id 
                    WHERE p2.trivia_id = t.id AND gh.position = 1 
                    LIMIT 1) as winner_name
            FROM trivias t
            LEFT JOIN players p ON t.id = p.trivia_id
            WHERE t.host_id = ?
            GROUP BY t.id
            ORDER BY t.created_at DESC
            LIMIT 20
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    // Obtener resultados finales de trivia
    public function getFinalResults($triviaId) {
        $stmt = $this->db->prepare("
            SELECT p.id, p.player_name, p.team_name, p.avatar, p.score,
                   COUNT(pa.id) as total_answers,
                   SUM(CASE WHEN pa.is_correct = 1 THEN 1 ELSE 0 END) as correct_answers
            FROM players p
            LEFT JOIN player_answers pa ON p.id = pa.player_id
            LEFT JOIN questions q ON pa.question_id = q.id AND q.trivia_id = ?
            WHERE p.trivia_id = ?
            GROUP BY p.id
            ORDER BY p.score DESC, correct_answers DESC, p.join_time ASC
        ");
        $stmt->execute([$triviaId, $triviaId]);
        
        $results = $stmt->fetchAll();
        
        // Agregar posición
        foreach ($results as $index => &$result) {
            $result['position'] = $index + 1;
        }
        
        return $results;
    }

    // Finalizar trivia
    public function finishTrivia($triviaId, $hostId = null) {
        $sql = "UPDATE trivias SET status = 'finished' WHERE id = ?";
        $params = [$triviaId];
        
        if ($hostId) {
            $sql .= " AND host_id = ?";
            $params[] = $hostId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        // Guardar en historial
        $this->saveGameHistory($triviaId);
        
        return $stmt->rowCount() > 0;
    }

    private function saveGameHistory($triviaId) {
        $results = $this->getFinalResults($triviaId);
        
        foreach ($results as $result) {
            $stmt = $this->db->prepare("
                INSERT INTO game_history (trivia_id, player_id, final_score, position)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $triviaId,
                $result['id'],
                $result['score'],
                $result['position']
            ]);
        }
    }

    // Obtener trivia por código
    public function getTriviaByCode($joinCode) {
        $stmt = $this->db->prepare("
            SELECT * FROM trivias WHERE join_code = ?
        ");
        $stmt->execute([$joinCode]);
        return $stmt->fetch();
    }

    // Obtener trivia por ID
    public function getTriviaById($triviaId) {
        $stmt = $this->db->prepare("
            SELECT * FROM trivias WHERE id = ?
        ");
        $stmt->execute([$triviaId]);
        return $stmt->fetch();
    }

    // Actualizar estado de trivia
    public function updateTriviaStatus($triviaId, $status) {
        $stmt = $this->db->prepare("
            UPDATE trivias SET status = ? WHERE id = ?
        ");
        return $stmt->execute([$status, $triviaId]);
    }

    // Métodos auxiliares para respuestas JSON
    public function jsonResponse($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public function errorResponse($message, $code = 400) {
        http_response_code($code);
        $this->jsonResponse(['error' => $message]);
    }

    public function successResponse($data = null) {
        $this->jsonResponse(['success' => true, 'data' => $data]);
    }

    // Cerrar conexión
    public function __destruct() {
        $this->db = null;
    }
}
?>