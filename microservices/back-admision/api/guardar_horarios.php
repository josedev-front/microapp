<?php
require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');

// Verificar que sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Verificar permisos
$user_role = $backAdmision->getUserRole();
$roles_permitidos = ['supervisor', 'backup', 'qa', 'superuser', 'developer'];

if (!in_array($user_role, $roles_permitidos)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tienes permisos para gestionar horarios']);
    exit;
}

try {
    $user_id = $_POST['user_id'] ?? 0;
    $user_name = $_POST['user_name'] ?? '';
    $horarios = $_POST['horarios'] ?? [];

    if (!$user_id) {
        throw new Exception('ID de usuario no válido');
    }

    // Cargar controlador
    require_once __DIR__ . '/../controllers/TeamController.php';
    $teamController = new TeamController();

    // Guardar horarios
    $result = $teamController->guardarHorariosUsuario($user_id, $horarios);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Horarios guardados correctamente para ' . $user_name
        ]);
    } else {
        throw new Exception('Error al guardar los horarios en la base de datos');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>