<?php
// microservices/tata-trivia/models/Trivia.php

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
    
    public function recordAnswer($playerId, $questionId, $optionId, $isCorrect, $responseTime) {
        try {
            // Verificar si ya respondió
            $stmt = $this->db->prepare("
                SELECT id FROM player_answers 
                WHERE player_id = ? AND question_id = ?
            ");
            $stmt->execute([$playerId, $questionId]);
            
            if ($stmt->fetch()) {
                // Ya respondió, actualizar
                $stmt = $this->db->prepare("
                    UPDATE player_answers 
                    SET selected_option_id = ?, is_correct = ?, response_time = ?, answered_at = NOW()
                    WHERE player_id = ? AND question_id = ?
                ");
                return $stmt->execute([$optionId, $isCorrect, $responseTime, $playerId, $questionId]);
            } else {
                // Nueva respuesta
                $stmt = $this->db->prepare("
                    INSERT INTO player_answers 
                    (player_id, question_id, selected_option_id, is_correct, response_time, answered_at)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                return $stmt->execute([$playerId, $questionId, $optionId, $isCorrect, $responseTime]);
            }
        } catch (Exception $e) {
            error_log("Error recording answer: " . $e->getMessage());
            return false;
        }
    }
    
    public function finishGame($triviaId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE trivias 
                SET status = 'finished'
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