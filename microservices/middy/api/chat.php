<?php
require_once __DIR__ . '/../init.php';
require_once __DIR__ . '/../controllers/ChatController.php';
require_once __DIR__ . '/../../../../app_core/php/main.php'; // para validar sesión

header('Content-Type: application/json; charset=utf-8');

// 1) Autenticación: solo usuarios logueados
if (!validarSesion()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// 2) Leer input
$input = json_decode(file_get_contents('php://input'), true);
$question = $input['question'] ?? '';

$controller = new ChatController();
$result = $controller->handleChat($_SESSION['id'], $question);

if (isset($result['error'])) {
    http_response_code(400);
}
echo json_encode($result);
