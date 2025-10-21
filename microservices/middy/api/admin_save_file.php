<?php
// microservices/middy/api/admin_save_file.php

// HEADERS CORS - PERMITIR ACCESO DESDE localhost:3000
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Manejar preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    require_once __DIR__ . '/../config/paths.php';
    MiddyPathResolver::requireCoreFiles();

    if (!validarSesion()) {
        throw new Exception('No autorizado - Sesión inválida');
    }

    $usuario = obtenerUsuarioActual();
    if (!$usuario) {
        throw new Exception('Usuario no encontrado en sesión');
    }

    require_once __DIR__ . '/../init.php';
    $adminController = new Middy\Controllers\AdminController();

    if (!$adminController->checkAdminPermissions($usuario['role'])) {
        throw new Exception('No tienes permisos de administración. Rol: ' . $usuario['role']);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $filename = $input['filename'] ?? '';
    $content = $input['content'] ?? '';

    if (empty($filename)) {
        throw new Exception('Nombre de archivo no especificado');
    }

    $result = $adminController->saveFileContent($filename, $content, $usuario['id'], $usuario['role']);

    echo json_encode([
        'success' => true,
        'message' => 'Archivo guardado correctamente',
        'filename' => $filename
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}