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
    
    if (!$input || empty($input['trivia_id']) || empty($input['questions'])) {
        throw new Exception('Datos inválidos: trivia_id y questions son requeridos');
    }

    $triviaController = new TriviaController();
    $triviaId = $input['trivia_id'];
    $questions = $input['questions'];

    // DEBUG: Log para ver qué estamos recibiendo
    error_log("Save Questions - Trivia ID: $triviaId, Questions count: " . count($questions));
    error_log("Questions data: " . print_r($questions, true));

    // Verificar que la trivia existe
    $trivia = $triviaController->getTriviaById($triviaId);
    if (!$trivia) {
        throw new Exception('Trivia no encontrada');
    }

    $savedQuestions = 0;
    $errors = [];

    // Guardar cada pregunta con manejo de errores
    foreach ($questions as $index => $questionData) {
        try {
            error_log("Procesando pregunta $index: " . print_r($questionData, true));
            
            $result = $triviaController->addQuestion($triviaId, $questionData);
            
            if ($result['success']) {
                $savedQuestions++;
                error_log("✅ Pregunta $index guardada - ID: " . $result['question_id']);
            } else {
                $errors[] = "Pregunta " . ($index + 1) . ": " . ($result['error'] ?? 'Error desconocido');
                error_log("❌ Error en pregunta $index: " . ($result['error'] ?? 'Error desconocido'));
            }
        } catch (Exception $e) {
            $errors[] = "Pregunta " . ($index + 1) . ": " . $e->getMessage();
            error_log("❌ Exception en pregunta $index: " . $e->getMessage());
        }
    }

    // Si no se guardó ninguna pregunta, lanzar error
    if ($savedQuestions === 0) {
        throw new Exception('No se pudo guardar ninguna pregunta. Errores: ' . implode(', ', $errors));
    }

    // Actualizar estado de la trivia a "waiting" solo si se guardaron preguntas
    $updateResult = $triviaController->updateTriviaStatus($triviaId, 'waiting');
    
    if (!$updateResult) {
        error_log("⚠️ No se pudo actualizar el estado de la trivia, pero las preguntas se guardaron");
    }

    $response = [
        'success' => true,
        'message' => 'Preguntas guardadas exitosamente',
        'data' => [
            'trivia_id' => $triviaId,
            'questions_saved' => $savedQuestions,
            'total_questions' => count($questions),
            'errors' => $errors
        ]
    ];

    // DEBUG: Log de respuesta
    error_log("Save Questions Response: " . print_r($response, true));

    echo json_encode($response);

} catch (Exception $e) {
    error_log('❌ Error en save_questions.php: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>