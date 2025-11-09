<?php
// microservices/back-admision/api/guardar_horarios.php
require_once __DIR__ . '/../init.php';
require_once __DIR__ . '/../controllers/TeamController.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_GET['user_id'] ?? '';
    $horarios = $_POST['horarios'] ?? [];
    
    if (empty($user_id) || empty($horarios)) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit;
    }
    
    $teamController = new TeamController();
    $actualizado = $teamController->actualizarHorarios($user_id, $horarios);
    
    if ($actualizado) {
        echo json_encode(['success' => true, 'message' => 'Horarios guardados correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al guardar los horarios']);
    }
}
?>