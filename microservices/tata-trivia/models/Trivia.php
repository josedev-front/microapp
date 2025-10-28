<?php
// microservices/tata-trivia/models/Trivia.php - VERSIÓN COMPLETAMENTE CORREGIDA

class Trivia {
    private $db;
    
    public function __construct() {
        $this->db = getTriviaDatabaseConnection();
    }
    
    public function getById($triviaId) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM trivias 
                WHERE id = ?
            ");
            $stmt->execute([$triviaId]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Error getting trivia: " . $e->getMessage());
            return null;
        }
    }
    
    public function getQuestions($triviaId) {
        try {
            $stmt = $this->db->prepare("
                SELECT q.*, 
                       GROUP_CONCAT(
                           JSON_OBJECT(
                               'id', qo.id,
                               'text', qo.option_text, 
                               'is_correct', qo.is_correct
                           )
                       ) as options_json
                FROM questions q
                LEFT JOIN question_options qo ON q.id = qo.question_id
                WHERE q.trivia_id = ?
                GROUP BY q.id
                ORDER BY q.order_index
            ");
            $stmt->execute([$triviaId]);
            
            $questions = $stmt->fetchAll();
            
            // Parsear opciones JSON
            foreach ($questions as &$question) {
                $options = [];
                if ($question['options_json']) {
                    $optionsData = json_decode('[' . $question['options_json'] . ']', true);
                    foreach ($optionsData as $option) {
                        $options[] = [
                            'id' => $option['id'],
                            'text' => $option['text'],
                            'is_correct' => (bool)$option['is_correct']
                        ];
                    }
                }
                $question['options'] = $options;
                unset($question['options_json']);
            }
            
            return $questions;
            
        } catch (Exception $e) {
            error_log("Error getting questions: " . $e->getMessage());
            return [];
        }
    }
    
    public function updateStatus($triviaId, $status) {
        try {
            $stmt = $this->db->prepare("
                UPDATE trivias 
                SET status = ? 
                WHERE id = ?
            ");
            return $stmt->execute([$status, $triviaId]);
        } catch (Exception $e) {
            error_log("Error updating trivia status: " . $e->getMessage());
            return false;
        }
    }
    
    public function setCurrentQuestion($triviaId, $questionIndex) {
        try {
            $stmt = $this->db->prepare("
                UPDATE trivias 
                SET current_question_index = ?,
                    status = 'active'
                WHERE id = ?
            ");
            return $stmt->execute([$questionIndex, $triviaId]);
        } catch (Exception $e) {
            error_log("Error setting current question: " . $e->getMessage());
            return false;
        }
    }
    
    public function getCurrentQuestionIndex($triviaId) {
        try {
            $stmt = $this->db->prepare("
                SELECT current_question_index 
                FROM trivias 
                WHERE id = ?
            ");
            $stmt->execute([$triviaId]);
            $result = $stmt->fetch();
            
            return $result ? $result['current_question_index'] : -1;
        } catch (Exception $e) {
            error_log("Error getting current question: " . $e->getMessage());
            return -1;
        }
    }
    
    public function getPlayers($triviaId) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM players 
                WHERE trivia_id = ? 
                ORDER BY join_time ASC
            ");
            $stmt->execute([$triviaId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error getting players: " . $e->getMessage());
            return [];
        }
    }
    
    public function getPlayerById($playerId) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM players 
                WHERE id = ?
            ");
            $stmt->execute([$playerId]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Error getting player: " . $e->getMessage());
            return null;
        }
    }
    
    // FUNCIÓN COMPLETAMENTE CORREGIDA: Guardar respuesta y actualizar puntaje
    public function recordAnswer($playerId, $questionId, $optionId, $isCorrect, $responseTime, $pointsEarned = 0) {
        try {
            error_log("📝 Intentando guardar respuesta: player=$playerId, question=$questionId, option=$optionId, correct=$isCorrect, points=$pointsEarned");
            
            // Verificar si ya respondió
            $stmt = $this->db->prepare("
                SELECT id, selected_option_id FROM player_answers 
                WHERE player_id = ? AND question_id = ?
            ");
            $stmt->execute([$playerId, $questionId]);
            $existingAnswer = $stmt->fetch();
            
            if ($existingAnswer) {
                // Ya respondió, actualizar
                error_log("🔄 Actualizando respuesta existente");
                $stmt = $this->db->prepare("
                    UPDATE player_answers 
                    SET selected_option_id = ?, is_correct = ?, response_time = ?, points_earned = ?, answered_at = NOW()
                    WHERE player_id = ? AND question_id = ?
                ");
                
                // Convertir valores booleanos a enteros para MySQL
                $isCorrectInt = $isCorrect ? 1 : 0;
                $result = $stmt->execute([
                    $optionId, 
                    $isCorrectInt, 
                    $responseTime, 
                    $pointsEarned, 
                    $playerId, 
                    $questionId
                ]);
                
                error_log("✅ Resultado UPDATE: " . ($result ? 'ÉXITO' : 'FALLO'));
            } else {
                // Nueva respuesta
                error_log("🆕 Insertando nueva respuesta");
                $stmt = $this->db->prepare("
                    INSERT INTO player_answers 
                    (player_id, question_id, selected_option_id, is_correct, response_time, points_earned, answered_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                
                // Convertir valores booleanos a enteros para MySQL
                $isCorrectInt = $isCorrect ? 1 : 0;
                $result = $stmt->execute([
                    $playerId, 
                    $questionId, 
                    $optionId, 
                    $isCorrectInt, 
                    $responseTime, 
                    $pointsEarned
                ]);
                
                error_log("✅ Resultado INSERT: " . ($result ? 'ÉXITO' : 'FALLO'));
            }
            
            // ACTUALIZAR PUNTAJE TOTAL DEL JUGADOR
            if ($result) {
                error_log("💰 Actualizando puntaje del jugador");
                $updateResult = $this->updatePlayerScore($playerId);
                error_log("✅ Resultado actualización puntaje: " . ($updateResult ? 'ÉXITO' : 'FALLO'));
            } else {
                error_log("❌ Falló la operación de base de datos");
                // Obtener información del error
                $errorInfo = $stmt->errorInfo();
                error_log("❌ Error PDO: " . json_encode($errorInfo));
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("❌ EXCEPCIÓN en recordAnswer: " . $e->getMessage());
            error_log("❌ Archivo: " . $e->getFile() . " Línea: " . $e->getLine());
            return false;
        }
    }
    
    // FUNCIÓN MEJORADA: Actualizar puntaje total del jugador
    public function updatePlayerScore($playerId) {
        try {
            error_log("🔄 Actualizando score para jugador: " . $playerId);
            
            $stmt = $this->db->prepare("
                UPDATE players 
                SET score = COALESCE((
                    SELECT SUM(points_earned) 
                    FROM player_answers 
                    WHERE player_id = ?
                ), 0),
                correct_answers = COALESCE((
                    SELECT COUNT(*) 
                    FROM player_answers 
                    WHERE player_id = ? AND is_correct = 1
                ), 0)
                WHERE id = ?
            ");
            
            $result = $stmt->execute([$playerId, $playerId, $playerId]);
            
            if ($result) {
                // Verificar el nuevo score
                $checkStmt = $this->db->prepare("SELECT score, correct_answers FROM players WHERE id = ?");
                $checkStmt->execute([$playerId]);
                $playerData = $checkStmt->fetch();
                error_log("✅ Score actualizado - Puntos: " . ($playerData['score'] ?? 0) . ", Correctas: " . ($playerData['correct_answers'] ?? 0));
            } else {
                error_log("❌ Falló la actualización del score");
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("❌ EXCEPCIÓN en updatePlayerScore: " . $e->getMessage());
            return false;
        }
    }
    
    // NUEVA FUNCIÓN: Verificar estructura de la tabla player_answers
    public function checkTableStructure() {
        try {
            $stmt = $this->db->prepare("DESCRIBE player_answers");
            $stmt->execute();
            $structure = $stmt->fetchAll();
            error_log("📊 Estructura de player_answers: " . json_encode($structure));
            return $structure;
        } catch (Exception $e) {
            error_log("❌ Error verificando estructura de tabla: " . $e->getMessage());
            return [];
        }
    }
    
    // NUEVA FUNCIÓN: Crear tabla player_answers si no existe
    public function createPlayerAnswersTable() {
        try {
            $createTableSQL = "
                CREATE TABLE IF NOT EXISTS player_answers (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    player_id INT NOT NULL,
                    question_id INT NOT NULL,
                    selected_option_id INT NULL,
                    is_correct TINYINT(1) DEFAULT 0,
                    response_time INT DEFAULT 0,
                    points_earned INT DEFAULT 0,
                    answered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_player_question (player_id, question_id),
                    FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
                    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
                    FOREIGN KEY (selected_option_id) REFERENCES question_options(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            
            $result = $this->db->exec($createTableSQL);
            error_log("✅ Tabla player_answers creada/verificada: " . ($result !== false ? 'ÉXITO' : 'FALLO'));
            return $result !== false;
            
        } catch (Exception $e) {
            error_log("❌ Error creando tabla player_answers: " . $e->getMessage());
            return false;
        }
    }
    
    // NUEVA FUNCIÓN: Obtener leaderboard actualizado
    public function getLeaderboard($triviaId) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    id,
                    player_name,
                    avatar,
                    team_name,
                    score,
                    correct_answers,
                    join_time
                FROM players 
                WHERE trivia_id = ? 
                ORDER BY score DESC, correct_answers DESC, join_time ASC
            ");
            $stmt->execute([$triviaId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error getting leaderboard: " . $e->getMessage());
            return [];
        }
    }
    
    // NUEVA FUNCIÓN: Obtener resultados finales
    public function getFinalResults($triviaId) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    id,
                    player_name,
                    avatar,
                    team_name,
                    score,
                    correct_answers,
                    join_time
                FROM players 
                WHERE trivia_id = ? 
                ORDER BY score DESC, correct_answers DESC, join_time ASC
                LIMIT 10
            ");
            $stmt->execute([$triviaId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error getting final results: " . $e->getMessage());
            return [];
        }
    }
    
    // NUEVA FUNCIÓN: Obtener rank de un jugador específico
    public function getPlayerRank($triviaId, $playerId) {
        try {
            $stmt = $this->db->prepare("
                SELECT position FROM (
                    SELECT 
                        id,
                        ROW_NUMBER() OVER (ORDER BY score DESC, correct_answers DESC, join_time ASC) as position
                    FROM players 
                    WHERE trivia_id = ?
                ) ranked_players
                WHERE id = ?
            ");
            $stmt->execute([$triviaId, $playerId]);
            $result = $stmt->fetch();
            
            return $result ? $result['position'] : 0;
        } catch (Exception $e) {
            error_log("Error getting player rank: " . $e->getMessage());
            return 0;
        }
    }
    
    public function finishGame($triviaId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE trivias 
                SET status = 'finished',
                    finished_at = NOW()
                WHERE id = ?
            ");
            return $stmt->execute([$triviaId]);
        } catch (Exception $e) {
            error_log("Error finishing game: " . $e->getMessage());
            return false;
        }
    }
}
?>