<?php
// microservices/tata-trivia/controllers/TriviaController.php - VERSIÓN CORREGIDA

class TriviaController {
    private $triviaModel;
    
    public function __construct() {
        $this->triviaModel = new Trivia();
    }
    
    public function getTriviaById($triviaId) {
        return $this->triviaModel->getById($triviaId);
    }
    
    public function getTriviaQuestions($triviaId) {
        return $this->triviaModel->getQuestions($triviaId);
    }
    
    public function getLobbyPlayers($triviaId) {
        return $this->triviaModel->getPlayers($triviaId);
    }
    
    public function getPlayerById($playerId) {
        return $this->triviaModel->getPlayerById($playerId);
    }
    
    public function updateTriviaStatus($triviaId, $status) {
        return $this->triviaModel->updateStatus($triviaId, $status);
    }
    
    public function setCurrentQuestion($triviaId, $questionIndex) {
        return $this->triviaModel->setCurrentQuestion($triviaId, $questionIndex);
    }
    
    public function getCurrentQuestionIndex($triviaId) {
        return $this->triviaModel->getCurrentQuestionIndex($triviaId);
    }
    
    // ACTUALIZADA: Incluye cálculo de puntos
    public function recordPlayerAnswer($playerId, $questionId, $optionId, $isCorrect, $responseTime, $pointsEarned = 0) {
        return $this->triviaModel->recordAnswer($playerId, $questionId, $optionId, $isCorrect, $responseTime, $pointsEarned);
    }
    
    public function finishGame($triviaId) {
        return $this->triviaModel->finishGame($triviaId);
    }
    
    // NUEVAS FUNCIONES PARA PUNTAJES Y RESULTADOS
    public function getLeaderboard($triviaId) {
        return $this->triviaModel->getLeaderboard($triviaId);
    }
    
    public function getFinalResults($triviaId) {
        return $this->triviaModel->getFinalResults($triviaId);
    }
    
    public function getPlayerRank($triviaId, $playerId) {
        return $this->triviaModel->getPlayerRank($triviaId, $playerId);
    }
    
    public function createTrivia($data) {
        // Tu código existente sin cambios
        try {
            $db = getTriviaDatabaseConnection();
            if (!$db) {
                throw new Exception('No se pudo conectar a la base de datos');
            }
            
            // Validar datos requeridos
            $required = ['title', 'theme', 'game_mode', 'max_winners'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Campo requerido faltante: $field");
                }
            }
            
            // Generar código único
            $joinCode = $this->generateUniqueCode();
            
            // Preparar datos para inserción
            $stmt = $db->prepare("
                INSERT INTO trivias 
                (host_id, title, theme, game_mode, max_winners, background_image, status, join_code, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 'setup', ?, NOW())
            ");
            
            $result = $stmt->execute([
                $data['user_id'] ?? null,
                $data['title'],
                $data['theme'],
                $data['game_mode'],
                intval($data['max_winners']),
                $data['background_image'] ?? $data['theme'],
                $joinCode
            ]);
            
            if ($result) {
                $triviaId = $db->lastInsertId();
                
                $checkStmt = $db->prepare("SELECT id FROM trivias WHERE id = ?");
                $checkStmt->execute([$triviaId]);
                $trivia = $checkStmt->fetch();
                
                if ($trivia) {
                    return [
                        'success' => true,
                        'trivia_id' => $triviaId,
                        'join_code' => $joinCode,
                        'message' => 'Trivia creada exitosamente'
                    ];
                } else {
                    throw new Exception('Error al verificar la trivia creada');
                }
            } else {
                throw new Exception('Error en la ejecución de la consulta');
            }
            
        } catch (PDOException $e) {
            error_log("Error PDO creating trivia: " . $e->getMessage());
            return [
                'success' => false, 
                'error' => 'Error de base de datos: ' . $e->getMessage()
            ];
        } catch (Exception $e) {
            error_log("Error creating trivia: " . $e->getMessage());
            return [
                'success' => false, 
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function addQuestion($triviaId, $questionData) {
        // Tu código existente sin cambios
        try {
            $db = getTriviaDatabaseConnection();
            
            $stmt = $db->prepare("
                INSERT INTO questions (trivia_id, question_text, question_type, background_image, time_limit, order_index)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $triviaId,
                $questionData['question_text'],
                $questionData['question_type'],
                $questionData['background_image'] ?? '',
                $questionData['time_limit'] ?? 30,
                $questionData['order_index'] ?? 0
            ]);
            
            if ($result) {
                $questionId = $db->lastInsertId();
                
                foreach ($questionData['options'] as $option) {
                    $stmt = $db->prepare("
                        INSERT INTO question_options (question_id, option_text, is_correct)
                        VALUES (?, ?, ?)
                    ");
                    $stmt->execute([
                        $questionId,
                        $option['text'],
                        $option['is_correct'] ? 1 : 0
                    ]);
                }
                
                return ['success' => true, 'question_id' => $questionId];
            }
            
            return ['success' => false, 'error' => 'Error al agregar pregunta'];
            
        } catch (Exception $e) {
            error_log("Error adding question: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error del servidor'];
        }
    }
    
    private function generateUniqueCode($length = 6) {
        try {
            $db = getTriviaDatabaseConnection();
            if (!$db) {
                throw new Exception('No hay conexión a BD');
            }
            
            $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            $maxAttempts = 10;
            $attempt = 0;
            
            do {
                $code = '';
                for ($i = 0; $i < $length; $i++) {
                    $code .= $characters[rand(0, strlen($characters) - 1)];
                }
                
                $stmt = $db->prepare("SELECT id FROM trivias WHERE join_code = ?");
                $stmt->execute([$code]);
                $existing = $stmt->fetch();
                
                if (!$existing) {
                    return $code;
                }
                
                $attempt++;
            } while ($attempt < $maxAttempts);
            
            throw new Exception('No se pudo generar código único después de ' . $maxAttempts . ' intentos');
            
        } catch (Exception $e) {
            error_log("Error generating unique code: " . $e->getMessage());
            return 'T' . time() % 100000;
        }
    }
}
?>