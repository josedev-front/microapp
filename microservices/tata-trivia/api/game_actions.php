<?php
// microservices/tata-trivia/api/game_actions.php - VERSIÓN COMPLETA CORREGIDA

// HEADERS PRIMERO
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Iniciar sesión si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Respuesta de error centralizada
function sendError($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $message,
        'timestamp' => time()
    ]);
    exit;
}

// Respuesta de éxito
function sendSuccess($data = []) {
    echo json_encode(array_merge([
        'success' => true,
        'timestamp' => time()
    ], $data));
    exit;
}

try {
    // Incluir init
    require_once __DIR__ . '/../init.php';
    
    // VERIFICAR MÉTODO POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendError('Método no permitido. Use POST.', 405);
    }

    // OBTENER INPUT
    $input = [];
    $rawInput = file_get_contents('php://input');
    
    if (!empty($rawInput)) {
        $input = json_decode($rawInput, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            sendError('JSON inválido: ' . json_last_error_msg());
        }
    } else {
        sendError('No se recibieron datos');
    }

    // VALIDAR PARÁMETROS CRÍTICOS
    if (empty($input['action'])) {
        sendError("Parámetro 'action' requerido");
    }

    if (empty($input['trivia_id'])) {
        sendError("Parámetro 'trivia_id' requerido");
    }

    $action = trim($input['action']);
    $trivia_id = intval($input['trivia_id']);
    
    // VERIFICAR QUE EL CONTROLADOR EXISTA
    if (!class_exists('TriviaController')) {
        sendError('Sistema de trivia no disponible', 500);
    }
    
    // Inicializar controlador
    $controller = new TriviaController();
    
    // VERIFICAR QUE LA TRIVIA EXISTA
    $trivia = $controller->getTriviaById($trivia_id);
    if (!$trivia) {
        sendError('Trivia no encontrada');
    }
    
    // PROCESAR ACCIONES
    switch($action) {
        case 'reset_questions':
            // Reiniciar estado de preguntas
            $_SESSION['current_question_index'] = -1;
            unset($_SESSION['current_question']);
            
            // También reiniciar en base de datos
            $controller->setCurrentQuestion($trivia_id, -1);
            $controller->updateTriviaStatus($trivia_id, 'waiting');
            
            sendSuccess([
                'message' => 'Estado reiniciado',
                'current_index' => -1,
                'trivia_status' => 'waiting'
            ]);
            break;
            
        case 'next_question':
case 'advance_question':
    // Obtener preguntas de la trivia
    $questions = $controller->getTriviaQuestions($trivia_id);
    
    if (empty($questions)) {
        sendError("No hay preguntas configuradas para esta trivia");
    }
    
    // Determinar índice actual desde BASE DE DATOS
    $current_index = $controller->getCurrentQuestionIndex($trivia_id);
    $next_index = $current_index + 1;
    
    // Verificar límites
    if ($next_index < 0) $next_index = 0;
    
    if ($next_index >= count($questions)) {
        // JUEGO TERMINADO
        $controller->finishGame($trivia_id);
        sendSuccess([
            'game_finished' => true,
            'message' => '¡Juego terminado! No hay más preguntas.',
            'total_questions' => count($questions),
            'final_index' => $current_index
        ]);
    }
    
    // Obtener datos de la siguiente pregunta
    $next_question = $questions[$next_index];
    
    // ✅ CORREGIDO: Asegurar que la pregunta tenga background_image formateado correctamente
    if (empty($next_question['background_image']) || $next_question['background_image'] === '') {
        $next_question['background_image'] = $controller->getQuestionBackgroundPath($next_question);
    } else {
        // Si ya tiene background_image, asegurarse de que sea una ruta completa
        $current_bg = $next_question['background_image'];
        if (strpos($current_bg, '/microservices/tata-trivia/assets/images/themes/') !== 0) {
            // Si no es una ruta completa, convertirla
            $next_question['background_image'] = $controller->getQuestionBackgroundPath($next_question);
        }
    }
    
    // ACTUALIZAR BASE DE DATOS - ESTO ES CLAVE
    $controller->setCurrentQuestion($trivia_id, $next_index);
    $controller->updateTriviaStatus($trivia_id, 'active');
    
    // Actualizar sesión también
    $_SESSION['current_question_index'] = $next_index;
    $_SESSION['current_question'] = $next_question;
    $_SESSION['question_start_time'] = time();
    
    sendSuccess([
        'question_index' => $next_index,
        'current_question' => $next_index + 1,
        'total_questions' => count($questions),
        'question_data' => $next_question,
        'game_active' => true,
        'trivia_status' => 'active'
    ]);
    break;
            
        case 'start_question':
            // Iniciar pregunta específica
            $question_index = isset($input['question_index']) ? 
                            intval($input['question_index']) : 0;
            $questions = $controller->getTriviaQuestions($trivia_id);
            
            if ($question_index < 0 || $question_index >= count($questions)) {
                sendError("Índice de pregunta inválido");
            }
            
            $question_data = $questions[$question_index];
            
            // ✅ CORREGIDO: Asegurar que la pregunta tenga background_image
            if (empty($question_data['background_image'])) {
                $question_data['background_image'] = $controller->getQuestionBackgroundPath($question_data);
            }
            
            // Actualizar base de datos
            $controller->setCurrentQuestion($trivia_id, $question_index);
            $controller->updateTriviaStatus($trivia_id, 'active');
            
            $_SESSION['current_question_index'] = $question_index;
            $_SESSION['current_question'] = $question_data;
            $_SESSION['question_start_time'] = time();
            
            sendSuccess([
                'question_data' => $question_data,
                'question_index' => $question_index,
                'current_question' => $question_index + 1,
                'total_questions' => count($questions),
                'trivia_status' => 'active'
            ]);
            break;
            
        case 'get_question_status':
            // Obtener estado actual desde BASE DE DATOS
            $current_index = $controller->getCurrentQuestionIndex($trivia_id);
            $questions = $controller->getTriviaQuestions($trivia_id);
            $current_question = null;
            
            if ($current_index >= 0 && $current_index < count($questions)) {
                $current_question = $questions[$current_index];
                
                // ✅ CORREGIDO: Asegurar que la pregunta tenga background_image
                if (empty($current_question['background_image'])) {
                    $current_question['background_image'] = $controller->getQuestionBackgroundPath($current_question);
                }
            }
            
            sendSuccess([
                'current_question_index' => $current_index,
                'total_questions' => count($questions),
                'has_next_question' => $current_index < (count($questions) - 1),
                'current_question' => $current_question,
                'game_active' => $current_question !== null,
                'trivia_status' => $trivia['status']
            ]);
            break;
            
        case 'end_question':
            // Finalizar pregunta actual
            $current_index = $controller->getCurrentQuestionIndex($trivia_id);
            unset($_SESSION['current_question']);
            $_SESSION['question_end_time'] = time();
            
            // Cambiar estado a waiting para la siguiente pregunta
            $controller->updateTriviaStatus($trivia_id, 'waiting');
            
            sendSuccess([
                'message' => 'Pregunta finalizada',
                'question_index' => $current_index,
                'trivia_status' => 'waiting'
            ]);
            break;
            
        case 'finish_game':
            // Finalizar juego completamente
            $controller->finishGame($trivia_id);
            
            // Limpiar sesión
            unset($_SESSION['current_question_index']);
            unset($_SESSION['current_question']);
            
            sendSuccess([
                'message' => 'Juego finalizado',
                'trivia_status' => 'finished'
            ]);
            break;
            
        case 'update_game_state':
            // Actualizar estado del juego (para broadcast)
            $question_index = $input['question_index'] ?? -1;
            $status = $input['status'] ?? 'waiting';
            
            if ($question_index >= 0) {
                $controller->setCurrentQuestion($trivia_id, $question_index);
            }
            $controller->updateTriviaStatus($trivia_id, $status);
            
            sendSuccess([
                'message' => 'Estado actualizado',
                'question_index' => $question_index,
                'trivia_status' => $status
            ]);
            break;
            
        case 'get_game_data':
            // Obtener todos los datos del juego
            $questions = $controller->getTriviaQuestions($trivia_id);
            $players = $controller->getLobbyPlayers($trivia_id);
            $current_index = $controller->getCurrentQuestionIndex($trivia_id);
            
            // Procesar preguntas para incluir fondos
            foreach ($questions as &$question) {
                if (empty($question['background_image'])) {
                    $question['background_image'] = $controller->getQuestionBackgroundPath($question);
                }
            }
            
            sendSuccess([
                'trivia' => $trivia,
                'questions' => $questions,
                'players' => $players,
                'current_question_index' => $current_index,
                'total_questions' => count($questions),
                'total_players' => count($players)
            ]);
            break;
            
        default:
            sendError("Acción no reconocida: " . $action);
    }

} catch (PDOException $e) {
    error_log("❌ ERROR PDO en game_actions: " . $e->getMessage());
    sendError('Error de base de datos', 500);
    
} catch (Exception $e) {
    // Log del error completo
    error_log("❌ ERROR CRÍTICO en game_actions: " . $e->getMessage());
    error_log("📋 Stack trace: " . $e->getTraceAsString());
    
    sendError('Error interno del servidor: ' . $e->getMessage(), 500);
}
?>