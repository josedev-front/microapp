<?php
// microservices/tata-trivia/api/player_communication.php - VERSIÓN CON CONTROL DE CACHÉ

// HEADERS ANTI-CACHÉ - ESTO ES CRÍTICO
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Verificar que el script se está ejecutando
error_log("🎯 player_communication.php EJECUTÁNDOSE - " . date('Y-m-d H:i:s'));

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Forzar liberación de buffers
if (ob_get_level()) {
    ob_end_clean();
}

// Iniciar sesión limpia
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_write_close(); // Liberar sesión inmediatamente

function sendError($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $message,
        'timestamp' => time(),
        'server_time' => date('Y-m-d H:i:s')
    ]);
    exit;
}

function sendSuccess($data = []) {
    echo json_encode(array_merge([
        'success' => true,
        'timestamp' => time(),
        'server_time' => date('Y-m-d H:i:s')
    ], $data));
    exit;
}

function calculatePoints($is_correct, $response_time) {
    if (!$is_correct) return 0;
    
    $base_points = 100;
    $max_time = 30000;
    $normalized_time = min($response_time, $max_time);
    $speed_bonus = max(0, 50 - (($normalized_time / $max_time) * 50));
    return (int)max(10, $base_points + $speed_bonus);
}

// PROCESAMIENTO PRINCIPAL
try {
    error_log("✅ player_communication.php INICIADO - URI: " . $_SERVER['REQUEST_URI']);

    // OBTENER INPUT DE FORMA DIRECTA
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true) ?: $_POST;
    
    $action = $input['action'] ?? '';
    $trivia_id = $input['trivia_id'] ?? '';
    
    error_log("📥 Action: $action, Trivia ID: $trivia_id");

    // VALIDACIONES BÁSICAS
    if (empty($trivia_id)) sendError('trivia_id requerido');
    if (empty($action)) sendError('action requerido');

    // INCLUIR INIT CON MANEJO DE ERRORES
    $init_path = __DIR__ . '/../init.php';
    if (!file_exists($init_path)) {
        error_log("❌ init.php no encontrado en: $init_path");
        sendError('Sistema no disponible');
    }
    
    require_once $init_path;
    
    if (!class_exists('TriviaController')) {
        sendError('Controlador no disponible');
    }

    $controller = new TriviaController();
    $trivia = $controller->getTriviaById($trivia_id);
    
    if (!$trivia) {
        sendError('Trivia no encontrada');
    }

    // PROCESAR ACCIONES
    switch($action) {
        case 'get_game_state':
            $current_index = $controller->getCurrentQuestionIndex($trivia_id);
            $questions = $controller->getTriviaQuestions($trivia_id);
            
            $current_question = null;
            if ($current_index >= 0 && $current_index < count($questions)) {
                $current_question = $questions[$current_index];
                // Limpiar datos sensibles
                if ($current_question && isset($current_question['options'])) {
                    foreach ($current_question['options'] as &$option) {
                        unset($option['is_correct']);
                    }
                }
            }
            
            sendSuccess([
                'trivia_status' => $trivia['status'],
                'current_question_index' => $current_index,
                'current_question' => $current_question,
                'total_questions' => count($questions)
            ]);
            break;
            
        case 'submit_answer':
            $player_id = $input['player_id'] ?? '';
            $question_id = $input['question_id'] ?? '';
            $option_id = $input['option_id'] ?? null;
            $response_time = $input['response_time'] ?? 0;
            
            if (empty($player_id)) sendError('player_id requerido');
            if (empty($question_id)) sendError('question_id requerido');
            
            $player = $controller->getPlayerById($player_id);
            if (!$player) sendError('Jugador no encontrado');
            
            $is_correct = false;
            $points_earned = 0;
            
            if (!empty($option_id)) {
                $db = getTriviaDatabaseConnection();
                $stmt = $db->prepare("SELECT is_correct FROM question_options WHERE id = ?");
                $stmt->execute([$option_id]);
                $option = $stmt->fetch();
                
                if ($option) {
                    $is_correct = (bool)$option['is_correct'];
                    $points_earned = calculatePoints($is_correct, $response_time);
                }
            }
            
            $result = $controller->recordPlayerAnswer($player_id, $question_id, $option_id, $is_correct, $response_time, $points_earned);
            
            if ($result) {
                sendSuccess([
                    'is_correct' => $is_correct,
                    'points_earned' => $points_earned,
                    'message' => 'Respuesta registrada'
                ]);
            } else {
                sendError('Error al guardar respuesta');
            }
            break;
            
        default:
            sendError("Acción no reconocida: $action");
    }

} catch (Exception $e) {
    error_log("💥 ERROR: " . $e->getMessage());
    sendError('Error interno: ' . $e->getMessage());
}

// Limpiar completamente al final
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}
exit;
?>