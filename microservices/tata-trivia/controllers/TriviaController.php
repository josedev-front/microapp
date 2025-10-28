<?php
// microservices/tata-trivia/controllers/TriviaController.php - VERSIÓN COMPLETA Y CORREGIDA

class TriviaController {
    private $triviaModel;
    private $db;
    
    public function __construct() {
        $this->triviaModel = new Trivia();
        $this->db = getTriviaDatabaseConnection();
    }
    
    public function getTriviaById($triviaId) {
        try {
            return $this->triviaModel->getById($triviaId);
        } catch (Exception $e) {
            error_log("Error en getTriviaById: " . $e->getMessage());
            return null;
        }
    }
    
    public function getTriviaQuestions($triviaId) {
        try {
            if (!$this->db) {
                throw new Exception('No hay conexión a la base de datos');
            }
            
            $stmt = $this->db->prepare("
                SELECT q.*, 
                       GROUP_CONCAT(
                           JSON_OBJECT(
                               'id', o.id,
                               'text', o.option_text,
                               'is_correct', o.is_correct
                           )
                       ) as options
                FROM questions q 
                LEFT JOIN question_options o ON q.id = o.question_id 
                WHERE q.trivia_id = ?
                GROUP BY q.id
                ORDER BY q.order_index ASC
            ");
            $stmt->execute([$triviaId]);
            $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Procesar las preguntas para tener un formato consistente
            foreach ($questions as &$question) {
                if (!empty($question['options'])) {
                    $question['options'] = json_decode('[' . $question['options'] . ']', true);
                } else {
                    $question['options'] = [];
                }
                
                // ✅ Asegurar que el background_image esté disponible
                if (empty($question['background_image'])) {
                    $question['background_image'] = $this->getQuestionBackgroundPath($question);
                }
            }
            
            return $questions;
        } catch (PDOException $e) {
            error_log("Error getting trivia questions: " . $e->getMessage());
            return [];
        } catch (Exception $e) {
            error_log("Error general en getTriviaQuestions: " . $e->getMessage());
            return [];
        }
    }
    
    public function getLobbyPlayers($triviaId) {
        try {
            return $this->triviaModel->getPlayers($triviaId);
        } catch (Exception $e) {
            error_log("Error en getLobbyPlayers: " . $e->getMessage());
            return [];
        }
    }
    
    public function getPlayerById($playerId) {
        try {
            return $this->triviaModel->getPlayerById($playerId);
        } catch (Exception $e) {
            error_log("Error en getPlayerById: " . $e->getMessage());
            return null;
        }
    }
    
    public function updateTriviaStatus($triviaId, $status) {
        try {
            return $this->triviaModel->updateStatus($triviaId, $status);
        } catch (Exception $e) {
            error_log("Error en updateTriviaStatus: " . $e->getMessage());
            return false;
        }
    }
    
    public function setCurrentQuestion($triviaId, $questionIndex) {
        try {
            return $this->triviaModel->setCurrentQuestion($triviaId, $questionIndex);
        } catch (Exception $e) {
            error_log("Error en setCurrentQuestion: " . $e->getMessage());
            return false;
        }
    }
    
    public function getCurrentQuestionIndex($triviaId) {
        try {
            return $this->triviaModel->getCurrentQuestionIndex($triviaId);
        } catch (Exception $e) {
            error_log("Error en getCurrentQuestionIndex: " . $e->getMessage());
            return 0;
        }
    }
    
    public function recordPlayerAnswer($playerId, $questionId, $optionId, $isCorrect, $responseTime) {
        try {
            return $this->triviaModel->recordAnswer($playerId, $questionId, $optionId, $isCorrect, $responseTime);
        } catch (Exception $e) {
            error_log("Error en recordPlayerAnswer: " . $e->getMessage());
            return false;
        }
    }
    
    public function finishGame($triviaId) {
        try {
            return $this->triviaModel->finishGame($triviaId);
        } catch (Exception $e) {
            error_log("Error en finishGame: " . $e->getMessage());
            return false;
        }
    }
    
    public function createTrivia($data) {
        try {
            if (!$this->db) {
                throw new Exception('No se pudo conectar a la base de datos');
            }
            
            // Validar datos requeridos
            $required = ['title', 'theme', 'game_mode', 'max_winners'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Campo requerido faltante: $field");
                }
            }
            
            // Validar título
            $title = trim($data['title']);
            if (strlen($title) < 3) {
                throw new Exception("El título debe tener al menos 3 caracteres");
            }
            
            // Generar código único
            $joinCode = $this->generateUniqueCode();
            
            // Preparar background_image
            $background_image = $data['background_image'] ?? $data['theme'];
            
            // Si es un tema predefinido, usar la imagen correspondiente
            if (strpos($background_image, 'custom/') === false) {
                $background_image = $data['theme']; // Usar el nombre del tema como referencia
            }
            
            // Preparar datos para inserción
            $stmt = $this->db->prepare("
                INSERT INTO trivias 
                (host_id, title, theme, game_mode, max_winners, background_image, status, join_code, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 'setup', ?, NOW())
            ");
            
            $result = $stmt->execute([
                $data['user_id'] ?? null,
                $title,
                $data['theme'],
                $data['game_mode'],
                intval($data['max_winners']),
                $background_image,
                $joinCode
            ]);
            
            if ($result) {
                $triviaId = $this->db->lastInsertId();
                
                // Verificar que se insertó correctamente
                $checkStmt = $this->db->prepare("SELECT id, join_code FROM trivias WHERE id = ?");
                $checkStmt->execute([$triviaId]);
                $trivia = $checkStmt->fetch();
                
                if ($trivia) {
                    return [
                        'success' => true,
                        'trivia_id' => $triviaId,
                        'join_code' => $trivia['join_code'],
                        'message' => 'Trivia creada exitosamente'
                    ];
                } else {
                    throw new Exception('Error al verificar la trivia creada');
                }
            } else {
                $errorInfo = $stmt->errorInfo();
                throw new Exception('Error en la ejecución de la consulta: ' . ($errorInfo[2] ?? 'Desconocido'));
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
        try {
            if (!$this->db) {
                throw new Exception('No se pudo conectar a la base de datos');
            }
            
            // Validar datos de la pregunta
            if (empty(trim($questionData['question_text']))) {
                throw new Exception("El texto de la pregunta es requerido");
            }
            
            // Validar que haya opciones
            if (empty($questionData['options']) || !is_array($questionData['options'])) {
                throw new Exception("La pregunta debe tener opciones");
            }
            
            // Validar que haya al menos una opción correcta
            $hasCorrectOption = false;
            foreach ($questionData['options'] as $option) {
                if ($option['is_correct']) {
                    $hasCorrectOption = true;
                    break;
                }
            }
            
            if (!$hasCorrectOption) {
                throw new Exception("La pregunta debe tener al menos una opción correcta");
            }
            
            // Procesar el fondo de la pregunta
            $backgroundImage = $this->processQuestionBackground($questionData, $triviaId);
            
            // Insertar pregunta
            $stmt = $this->db->prepare("
                INSERT INTO questions 
                (trivia_id, question_text, question_type, background_image, time_limit, order_index, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $result = $stmt->execute([
                $triviaId,
                trim($questionData['question_text']),
                $questionData['question_type'] ?? 'quiz',
                $backgroundImage,
                intval($questionData['time_limit'] ?? 30),
                intval($questionData['order_index'] ?? 0)
            ]);
            
            if ($result) {
                $questionId = $this->db->lastInsertId();
                
                // Agregar opciones
                $optionsInserted = 0;
                foreach ($questionData['options'] as $option) {
                    $optionStmt = $this->db->prepare("
                        INSERT INTO question_options 
                        (question_id, option_text, is_correct, created_at)
                        VALUES (?, ?, ?, NOW())
                    ");
                    
                    $optionResult = $optionStmt->execute([
                        $questionId,
                        trim($option['text']),
                        $option['is_correct'] ? 1 : 0
                    ]);
                    
                    if ($optionResult) {
                        $optionsInserted++;
                    }
                }
                
                if ($optionsInserted === count($questionData['options'])) {
                    return [
                        'success' => true, 
                        'question_id' => $questionId,
                        'message' => 'Pregunta agregada exitosamente'
                    ];
                } else {
                    // Si algunas opciones no se insertaron, eliminar la pregunta
                    $this->db->prepare("DELETE FROM questions WHERE id = ?")->execute([$questionId]);
                    throw new Exception("Error al insertar algunas opciones");
                }
            } else {
                $errorInfo = $stmt->errorInfo();
                throw new Exception('Error al insertar pregunta: ' . ($errorInfo[2] ?? 'Desconocido'));
            }
            
        } catch (PDOException $e) {
            error_log("Error PDO adding question: " . $e->getMessage());
            return [
                'success' => false, 
                'error' => 'Error de base de datos: ' . $e->getMessage()
            ];
        } catch (Exception $e) {
            error_log("Error adding question: " . $e->getMessage());
            return [
                'success' => false, 
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function processQuestionBackground($questionData, $triviaId) {
        $backgroundImage = $questionData['background_image'] ?? '';
        
        // Si es base64 (imagen subida por el usuario)
        if (strpos($backgroundImage, 'data:image') === 0) {
            return $this->saveQuestionBackground($backgroundImage, $triviaId);
        }
        
        // Si es una imagen predefinida (solo el nombre del archivo)
        if (!empty($backgroundImage) && strpos($backgroundImage, 'custom/') === false) {
            // Verificar si existe en la carpeta de preguntas
            $path = '/microservices/tata-trivia/assets/images/themes/questions/' . $backgroundImage;
            if (file_exists($_SERVER['DOCUMENT_ROOT'] . $path)) {
                return $backgroundImage; // Devolver solo el nombre del archivo
            }
        }
        
        // Si no hay fondo específico, devolver vacío
        return '';
    }
    
    private function saveQuestionBackground($base64Image, $triviaId) {
        try {
            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/microservices/tata-trivia/assets/images/themes/custom/';
            
            // Crear directorio si no existe
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            // Generar nombre único
            $filename = 'question_bg_' . $triviaId . '_' . uniqid() . '.jpg';
            $filePath = $uploadDir . $filename;
            
            // Convertir base64 a archivo
            $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64Image));
            
            if (file_put_contents($filePath, $imageData)) {
                return 'custom/' . $filename;
            } else {
                throw new Exception('No se pudo guardar la imagen');
            }
            
        } catch (Exception $e) {
            error_log("Error saving question background: " . $e->getMessage());
            return ''; // Devolver vacío si hay error
        }
    }
    
    public function getQuestionBackgroundPath($question) {
    try {
        $background_image = $question['background_image'] ?? '';
        
        // ✅ CORREGIDO: Si ya es una ruta completa, devolverla directamente
        if (strpos($background_image, '/microservices/tata-trivia/assets/images/themes/') === 0) {
            // Verificar si el archivo existe
            if (file_exists($_SERVER['DOCUMENT_ROOT'] . $background_image)) {
                return $background_image;
            }
        }
        
        // Si es una imagen custom guardada
        if (strpos($background_image, 'custom/') === 0) {
            $path = '/microservices/tata-trivia/assets/images/themes/' . $background_image;
            if (file_exists($_SERVER['DOCUMENT_ROOT'] . $path)) {
                return $path;
            }
        }
        
        // Si es una imagen predefinida de preguntas (solo nombre de archivo)
        if (!empty($background_image) && strpos($background_image, 'custom/') === false) {
            $path = '/microservices/tata-trivia/assets/images/themes/questions/' . $background_image;
            if (file_exists($_SERVER['DOCUMENT_ROOT'] . $path)) {
                return $path;
            }
        }
        
        // Fallback: usar el fondo de la trivia
        return $this->getTriviaBackgroundPath($question['trivia_id'] ?? 0);
        
    } catch (Exception $e) {
        error_log("Error getting question background: " . $e->getMessage());
        return '/microservices/tata-trivia/assets/images/themes/questions/general.jpg';
    }
}
    
    public function addMultipleQuestions($triviaId, $questions) {
        try {
            $results = [];
            $successCount = 0;
            
            foreach ($questions as $index => $questionData) {
                $questionData['order_index'] = $index + 1;
                $result = $this->addQuestion($triviaId, $questionData);
                
                if ($result['success']) {
                    $successCount++;
                    $results[] = [
                        'success' => true,
                        'question_id' => $result['question_id'],
                        'order_index' => $questionData['order_index']
                    ];
                } else {
                    $results[] = [
                        'success' => false,
                        'error' => $result['error'],
                        'order_index' => $questionData['order_index']
                    ];
                }
            }
            
            return [
                'success' => $successCount === count($questions),
                'total_questions' => count($questions),
                'success_count' => $successCount,
                'failed_count' => count($questions) - $successCount,
                'results' => $results
            ];
            
        } catch (Exception $e) {
            error_log("Error adding multiple questions: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function getTriviaWithDetails($triviaId) {
        try {
            if (!$this->db) {
                throw new Exception('No se pudo conectar a la base de datos');
            }
            
            // Obtener trivia básica
            $trivia = $this->getTriviaById($triviaId);
            if (!$trivia) {
                throw new Exception("Trivia no encontrada");
            }
            
            // Obtener preguntas
            $questions = $this->getTriviaQuestions($triviaId);
            
            // Obtener jugadores
            $players = $this->getLobbyPlayers($triviaId);
            
            return [
                'success' => true,
                'trivia' => $trivia,
                'questions' => $questions,
                'players' => $players,
                'questions_count' => count($questions),
                'players_count' => count($players)
            ];
            
        } catch (Exception $e) {
            error_log("Error getting trivia with details: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function validateTriviaAccess($triviaId, $userId = null) {
        try {
            $trivia = $this->getTriviaById($triviaId);
            if (!$trivia) {
                return [
                    'success' => false,
                    'error' => 'Trivia no encontrada'
                ];
            }
            
            // Si se proporciona user_id, verificar si es el host
            if ($userId && $trivia['host_id'] != $userId) {
                return [
                    'success' => false,
                    'error' => 'No tienes permisos para acceder a esta trivia'
                ];
            }
            
            return [
                'success' => true,
                'trivia' => $trivia
            ];
            
        } catch (Exception $e) {
            error_log("Error validating trivia access: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error al validar acceso: ' . $e->getMessage()
            ];
        }
    }
    
    public function getTriviaByJoinCode($joinCode) {
        try {
            if (!$this->db) {
                throw new Exception('No se pudo conectar a la base de datos');
            }
            
            $stmt = $this->db->prepare("
                SELECT id, host_id, title, theme, background_image, game_mode, max_winners, 
                       join_code, status, created_at 
                FROM trivias 
                WHERE join_code = ? AND status IN ('setup', 'waiting', 'active')
            ");
            $stmt->execute([strtoupper($joinCode)]);
            $trivia = $stmt->fetch();
            
            return $trivia ?: null;
            
        } catch (Exception $e) {
            error_log("Error getting trivia by join code: " . $e->getMessage());
            return null;
        }
    }
    
    public function getTriviaBackgroundPath($triviaId) {
        try {
            $trivia = $this->getTriviaById($triviaId);
            if (!$trivia) {
                return '/microservices/tata-trivia/assets/images/themes/setup/default.jpg';
            }
            
            $background_image = $trivia['background_image'] ?? 'default';
            
            // Si es una imagen custom
            if (strpos($background_image, 'custom/') === 0) {
                $path = '/microservices/tata-trivia/assets/images/themes/' . $background_image;
                // Verificar si el archivo existe
                if (file_exists($_SERVER['DOCUMENT_ROOT'] . $path)) {
                    return $path;
                }
            }
            
            // Para temas predefinidos
            $theme = $trivia['theme'] ?? 'default';
            $path = '/microservices/tata-trivia/assets/images/themes/setup/' . $theme . '.jpg';
            
            // Verificar si el archivo existe, sino usar default
            if (file_exists($_SERVER['DOCUMENT_ROOT'] . $path)) {
                return $path;
            }
            
            return '/microservices/tata-trivia/assets/images/themes/setup/default.jpg';
            
        } catch (Exception $e) {
            error_log("Error getting trivia background path: " . $e->getMessage());
            return '/microservices/tata-trivia/assets/images/themes/setup/default.jpg';
        }
    }
    
    private function generateUniqueCode($length = 6) {
        try {
            if (!$this->db) {
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
                
                // Verificar si el código ya existe
                $stmt = $this->db->prepare("SELECT id FROM trivias WHERE join_code = ?");
                $stmt->execute([$code]);
                $existing = $stmt->fetch();
                
                if (!$existing) {
                    return $code;
                }
                
                $attempt++;
            } while ($attempt < $maxAttempts);
            
            // Si no se pudo generar único después de varios intentos
            throw new Exception('No se pudo generar código único después de ' . $maxAttempts . ' intentos');
            
        } catch (Exception $e) {
            error_log("Error generating unique code: " . $e->getMessage());
            // Fallback: código con timestamp más aleatorio
            return 'T' . substr(strtoupper(uniqid()), -5);
        }
    }
    
    // Método para limpiar trivias antiguas (opcional, para mantenimiento)
    public function cleanupOldTrivias($days = 7) {
        try {
            if (!$this->db) {
                throw new Exception('No se pudo conectar a la base de datos');
            }
            
            $stmt = $this->db->prepare("
                DELETE FROM trivias 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY) 
                AND status IN ('finished', 'cancelled')
            ");
            
            $result = $stmt->execute([$days]);
            $deletedCount = $stmt->rowCount();
            
            return [
                'success' => true,
                'deleted_count' => $deletedCount,
                'message' => "Se eliminaron $deletedCount trivias antiguas"
            ];
            
        } catch (Exception $e) {
            error_log("Error cleaning up old trivias: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
?>