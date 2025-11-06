<?php
// microservices/back-admision/api/cambiar_estado_usuario.php
require_once __DIR__ . '/../init.php';
require_once __DIR__ . '/../controllers/TeamController.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'] ?? '';
    $estado = $_POST['estado'] ?? '';
    
    if (empty($user_id) || empty($estado)) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit;
    }
    
    $teamController = new TeamController();
    $actualizado = $teamController->cambiarEstadoEjecutivo($user_id, $estado);
    
    if ($actualizado) {
        echo json_encode(['success' => true, 'message' => 'Estado actualizado correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar el estado']);
    }
}
?>