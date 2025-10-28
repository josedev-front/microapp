<?php
// microservices/tata-trivia/api/game_actions.php - VERSIÓN CORREGIDA

// HEADERS PRIMERO
header('Content-Type: application/json; charset=utf-8');

// Iniciar sesión si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Respuesta de error centralizada
function sendError($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $message
    ]);
    exit;
}

// Respuesta de éxito
function sendSuccess($data = []) {
    echo json_encode(array_merge(['success' => true], $data));
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
            // Si falla JSON, intentar con POST normal
            $input = $_POST;
        }
    } else {
        // Si no hay input JSON, usar POST normal
        $input = $_POST;
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
                'current_index' => -1
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
                    'message' => 'No hay más preguntas disponibles'
                ]);
            }
            
            // ACTUALIZAR BASE DE DATOS - ESTO ES CLAVE
            $controller->setCurrentQuestion($trivia_id, $next_index);
            
            // Actualizar sesión también
            $_SESSION['current_question_index'] = $next_index;
            $_SESSION['current_question'] = $questions[$next_index];
            $_SESSION['question_start_time'] = time();
            
            sendSuccess([
                'current_question' => $next_index + 1,
                'total_questions' => count($questions),
                'question_data' => $questions[$next_index],
                'question_index' => $next_index,
                'game_active' => true
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
            
            // Actualizar base de datos
            $controller->setCurrentQuestion($trivia_id, $question_index);
            
            $_SESSION['current_question_index'] = $question_index;
            $_SESSION['current_question'] = $questions[$question_index];
            $_SESSION['question_start_time'] = time();
            
            sendSuccess([
                'question_data' => $questions[$question_index],
                'question_index' => $question_index,
                'current_question' => $question_index + 1,
                'total_questions' => count($questions)
            ]);
            break;
            
        case 'get_question_status':
            // Obtener estado actual desde BASE DE DATOS
            $current_index = $controller->getCurrentQuestionIndex($trivia_id);
            $questions = $controller->getTriviaQuestions($trivia_id);
            $current_question = null;
            
            if ($current_index >= 0 && $current_index < count($questions)) {
                $current_question = $questions[$current_index];
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
            unset($_SESSION['current_question']);
            $_SESSION['question_end_time'] = time();
            
            sendSuccess([
                'message' => 'Pregunta finalizada'
            ]);
            break;
            
        case 'finish_game':
            // Finalizar juego completamente
            $controller->finishGame($trivia_id);
            sendSuccess([
                'message' => 'Juego finalizado'
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
                'message' => 'Estado actualizado'
            ]);
            break;
            
        default:
            sendError("Acción no reconocida: " . $action);
    }

} catch (Exception $e) {
    // Log del error completo
    error_log("❌ ERROR CRÍTICO en game_actions: " . $e->getMessage());
    
    sendError('Error interno del servidor: ' . $e->getMessage(), 500);
}
?>