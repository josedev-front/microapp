<?php
// microservices/back-admision/api/get_horarios_json.php
require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $user_id = $input['user_id'] ?? null;
    
    if (!$user_id) {
        throw new Exception('ID de usuario requerido');
    }
    
    require_once __DIR__ . '/../controllers/TeamController.php';
    $teamController = new TeamController();
    
    $horarios = $teamController->getHorariosEjecutivo($user_id);
    
    echo json_encode([
        'success' => true,
        'horarios' => $horarios
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>