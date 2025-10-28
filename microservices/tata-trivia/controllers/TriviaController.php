<?php
// microservices/tata-trivia/controllers/TriviaController.php

class TriviaController {
    private $triviaModel;
    
    public function __construct() {
        $this->triviaModel = new Trivia();
    }
    
    // Métodos existentes - MANTENIDOS

     public function joinGame($joinData) {
        try {
            $db = getTriviaDatabaseConnection();
            
            // Buscar trivia por código
            $stmt = $db->prepare("SELECT id, title, status, game_mode FROM trivias WHERE join_code = ? AND status IN ('waiting', 'active')");
            $stmt->execute([$joinData['join_code']]);
            $trivia = $stmt->fetch();
            
            if (!$trivia) {
                return ['success' => false, 'error' => 'Código inválido o juego no disponible'];
            }
            
            // Verificar si el juego ya empezó
            if ($trivia['status'] === 'active') {
                // Podrías permitir unirse incluso si ya empezó, o no
                // Por ahora permitimos unirse
            }
            
            // Crear jugador
            $stmt = $db->prepare("
                INSERT INTO players (trivia_id, user_id, player_name, work_area, avatar, score, join_time) 
                VALUES (?, ?, ?, ?, ?, 0, NOW())
            ");
            
            $result = $stmt->execute([
                $trivia['id'],
                $joinData['user_id'],
                $joinData['player_name'],
                $joinData['work_area'],
                $joinData['avatar']
            ]);
            
            if ($result) {
                $player_id = $db->lastInsertId();
                
                return [
                    'success' => true,
                    'trivia_id' => $trivia['id'],
                    'player_id' => $player_id,
                    'message' => 'Te has unido exitosamente a la trivia'
                ];
            }
            
            return ['success' => false, 'error' => 'Error al unirse al juego'];
            
        } catch (Exception $e) {
            error_log("Error joining game: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error del servidor'];
        }
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
    
    public function recordPlayerAnswer($playerId, $questionId, $optionId, $isCorrect, $responseTime) {
        return $this->triviaModel->recordAnswer($playerId, $questionId, $optionId, $isCorrect, $responseTime);
    }
    
    public function finishGame($triviaId) {
        return $this->triviaModel->finishGame($triviaId);
    }
    
    public function createGameEvent($trivia_id, $event_type, $event_data) {
        $db = $this->getDatabaseConnection();
        
        $stmt = $db->prepare("
            INSERT INTO game_events (trivia_id, event_type, event_data, created_at) 
            VALUES (?, ?, ?, NOW())
        ");
        
        return $stmt->execute([
            $trivia_id, 
            $event_type, 
            json_encode($event_data)
        ]);
    }

    public function getGameEvents($trivia_id, $last_event_id = 0) {
        $db = $this->getDatabaseConnection();
        
        $stmt = $db->prepare("
            SELECT * FROM game_events 
            WHERE trivia_id = ? AND id > ? 
            ORDER BY id ASC
        ");
        
        $stmt->execute([$trivia_id, $last_event_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function addQuestion($triviaId, $questionData) {
        try {
            $db = getTriviaDatabaseConnection();
            
            // Obtener imagen de fondo aleatoria si no se especifica
            $backgroundImage = $questionData['background_image'] ?? $this->getRandomQuestionBackground();
            
            $stmt = $db->prepare("
                INSERT INTO questions (trivia_id, question_text, question_type, background_image, time_limit, order_index)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $triviaId,
                $questionData['question_text'],
                $questionData['question_type'],
                $backgroundImage,
                $questionData['time_limit'] ?? 30,
                $questionData['order_index'] ?? 0
            ]);
            
            if ($result) {
                $questionId = $db->lastInsertId();
                
                // Agregar opciones
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
    
    // NUEVOS MÉTODOS PARA IMÁGENES
    public function getSetupBackgrounds() {
        $setupImagesPath = $_SERVER['DOCUMENT_ROOT'] . '/microservices/tata-trivia/assets/images/themes/setup/';
        $images = [];
        
        if (is_dir($setupImagesPath)) {
            $files = scandir($setupImagesPath);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..' && in_array(pathinfo($file, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif'])) {
                    $images[] = [
                        'filename' => $file,
                        'path' => '/microservices/tata-trivia/assets/images/themes/setup/' . $file,
                        'name' => pathinfo($file, PATHINFO_FILENAME)
                    ];
                }
            }
        }
        
        return $images;
    }
    
    public function getRandomQuestionBackground() {
        $questionImagesPath = $_SERVER['DOCUMENT_ROOT'] . '/microservices/tata-trivia/assets/images/themes/questions/';
        $images = [];
        
        if (is_dir($questionImagesPath)) {
            $files = scandir($questionImagesPath);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..' && in_array(pathinfo($file, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif'])) {
                    $images[] = '/microservices/tata-trivia/assets/images/themes/questions/' . $file;
                }
            }
        }
        
        if (!empty($images)) {
            return $images[array_rand($images)];
        }
        
        return '/microservices/tata-trivia/assets/images/themes/default.jpg';
    }
    
    public function getBackgroundImagePath($triviaId) {
        $trivia = $this->getTriviaById($triviaId);
        if (!$trivia) {
            return '/microservices/tata-trivia/assets/images/themes/setup/default.jpg';
        }
        
        $backgroundImage = $trivia['background_image'];
        
        // Si es un tema predefinido
        if (in_array($backgroundImage, ['fiestas_patrias', 'navidad', 'halloween', 'default', 'dia_mujer', 'dia_amor', 'lgbt', 'pascua'])) {
            $imagePath = '/microservices/tata-trivia/assets/images/themes/setup/' . $backgroundImage . '.jpg';
            if (file_exists($_SERVER['DOCUMENT_ROOT'] . $imagePath)) {
                return $imagePath;
            }
        }
        // Si es una imagen personalizada
        elseif (strpos($backgroundImage, 'custom_') === 0) {
            $filename = str_replace('custom_', '', $backgroundImage);
            $imagePath = '/microservices/tata-trivia/assets/images/themes/setup/' . $filename;
            if (file_exists($_SERVER['DOCUMENT_ROOT'] . $imagePath)) {
                return $imagePath;
            }
        }
        
        // Imagen por defecto
        return '/microservices/tata-trivia/assets/images/themes/setup/default.jpg';
    }
    
    public function getQuestionBackgroundImage($questionId) {
        try {
            $db = getTriviaDatabaseConnection();
            $stmt = $db->prepare("SELECT background_image FROM questions WHERE id = ?");
            $stmt->execute([$questionId]);
            $question = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($question && !empty($question['background_image'])) {
                return $question['background_image'];
            }
            
            return $this->getRandomQuestionBackground();
        } catch (Exception $e) {
            return $this->getRandomQuestionBackground();
        }
    }
    
    public function createTrivia($data) {
        try {
            $db = getTriviaDatabaseConnection();
            
            // Generar código único
            $join_code = $this->generateUniqueCode();
            
            // Procesar imagen de fondo
            $backgroundImage = $data['background_image'] ?? ($data['theme'] ?? 'default');
            
            $stmt = $db->prepare("
                INSERT INTO trivias (host_id, title, theme, game_mode, max_winners, background_image, join_code, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'setup', NOW())
            ");
            
            $result = $stmt->execute([
                $data['user_id'] ?? null,
                $data['title'],
                $data['theme'] ?? 'default',
                $data['game_mode'] ?? 'individual',
                $data['max_winners'] ?? 1,
                $backgroundImage,
                $join_code
            ]);
            
            if ($result) {
                $trivia_id = $db->lastInsertId();
                return [
                    'success' => true,
                    'trivia_id' => $trivia_id,
                    'join_code' => $join_code
                ];
            }
            
            return ['success' => false, 'error' => 'Error al crear trivia'];
            
        } catch (Exception $e) {
            error_log("Error creating trivia: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error del servidor: ' . $e->getMessage()];
        }
    }
    
    private function generateUniqueCode($length = 6) {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $maxAttempts = 10; // Límite de intentos para evitar bucle infinito
    $attempt = 0;
    
    do {
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        // Verificar si el código ya existe
        $db = getTriviaDatabaseConnection();
        $stmt = $db->prepare("SELECT id FROM trivias WHERE join_code = ?");
        $stmt->execute([$code]);
        $existing = $stmt->fetch();
        
        $attempt++;
        
        // Si no existe, usar este código
        if (!$existing) {
            error_log("✅ Código único generado: $code (intento $attempt)");
            return $code;
        }
        
        error_log("⚠️ Código duplicado: $code, intentando otro...");
        
    } while ($attempt < $maxAttempts);
    
    // Si llegamos aquí, usar código con timestamp
    $fallbackCode = 'T' . substr(time(), -5);
    error_log("🔄 Usando código de respaldo: $fallbackCode");
    return $fallbackCode;
}
    
    private function getDatabaseConnection() {
        return getTriviaDatabaseConnection();
    }
}
?>