<?php
require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || empty($input['action']) || empty($input['trivia_id'])) {
        throw new Exception('Datos inválidos: action y trivia_id son requeridos');
    }

    $action = $input['action'];
    $trivia_id = $input['trivia_id'];
    $triviaController = new TriviaController();

    switch ($action) {
        case 'get_game_state':
            $trivia = $triviaController->getTriviaById($trivia_id);
            
            if (!$trivia) {
                echo json_encode(['success' => false, 'error' => 'Trivia no encontrada']);
                exit;
            }
            
            $response = [
                'success' => true,
                'trivia_status' => $trivia['status'],
                'current_question_index' => $trivia['current_question_index'],
                'trivia_id' => $trivia_id
            ];
            
            // ✅ CORREGIDO: Solo enviar pregunta si el juego está activo Y hay pregunta actual
            if ($trivia['status'] === 'active' && $trivia['current_question_index'] >= 0) {
                $questions = $triviaController->getTriviaQuestions($trivia_id);
                if (isset($questions[$trivia['current_question_index']])) {
                    $currentQuestion = $questions[$trivia['current_question_index']];
                    
                    // Asegurarse de que la pregunta tenga la estructura correcta
                    if (isset($currentQuestion['options']) && is_array($currentQuestion['options'])) {
                        $response['current_question'] = $currentQuestion;
                    } else {
                        error_log("⚠️ Pregunta sin opciones válidas: " . json_encode($currentQuestion));
                    }
                }
            }
            
            echo json_encode($response);
            break;

        case 'submit_answer':
            if (empty($input['player_id']) || empty($input['question_id'])) {
                throw new Exception('player_id y question_id son requeridos');
            }

            $player_id = $input['player_id'];
            $question_id = $input['question_id'];
            $option_id = $input['option_id'] ?? null;
            $response_time = $input['response_time'] ?? 0;

            // Verificar si la pregunta existe y obtener las opciones correctas
            $db = getTriviaDatabaseConnection();
            $stmt = $db->prepare("
                SELECT qo.id, qo.is_correct 
                FROM question_options qo 
                WHERE qo.question_id = ?
            ");
            $stmt->execute([$question_id]);
            $options = $stmt->fetchAll();

            $is_correct = false;
            
            if ($option_id) {
                // Encontrar si la opción seleccionada es correcta
                foreach ($options as $option) {
                    if ($option['id'] == $option_id && $option['is_correct']) {
                        $is_correct = true;
                        break;
                    }
                }
            }

            // Registrar la respuesta
            $result = $triviaController->recordPlayerAnswer(
                $player_id, 
                $question_id, 
                $option_id, 
                $is_correct, 
                $response_time
            );

            if ($result) {
                echo json_encode([
                    'success' => true,
                    'is_correct' => $is_correct,
                    'message' => 'Respuesta registrada correctamente'
                ]);
            } else {
                throw new Exception('Error al registrar la respuesta');
            }
            break;

        case 'get_player_score':
            if (empty($input['player_id'])) {
                throw new Exception('player_id es requerido');
            }

            $player_id = $input['player_id'];
            $player = $triviaController->getPlayerById($player_id);
            
            if ($player) {
                echo json_encode([
                    'success' => true,
                    'score' => $player['score'] ?? 0,
                    'player_name' => $player['player_name']
                ]);
            } else {
                throw new Exception('Jugador no encontrado');
            }
            break;

        default:
            throw new Exception('Acción no válida: ' . $action);
    }

} catch (Exception $e) {
    error_log('❌ Error en player_communication.php: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>