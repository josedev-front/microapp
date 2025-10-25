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
        throw new Exception('Datos inválidos');
    }

    $triviaController = new TriviaController();
    $triviaId = $input['trivia_id'];
    $questions = $input['questions'];

    // Guardar cada pregunta
    foreach ($questions as $questionData) {
        $triviaController->addQuestion($triviaId, $questionData);
    }

    // Actualizar estado de la trivia a "waiting"
    $triviaController->updateTriviaStatus($triviaId, 'waiting');

    echo json_encode([
        'success' => true,
        'message' => 'Preguntas guardadas exitosamente',
        'data' => [
            'trivia_id' => $triviaId,
            'questions_count' => count($questions)
        ]
    ]);

} catch (Exception $e) {
    error_log('Error saving questions: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>