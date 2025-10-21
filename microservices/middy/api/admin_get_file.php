<?php
// microservices/middy/api/admin_get_file.php

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

    $filename = $_GET['file'] ?? '';
    if (empty($filename)) {
        throw new Exception('Nombre de archivo no especificado');
    }

    $content = $adminController->getFileContent($filename);
    $stats = $adminController->getDocumentStats();

    echo json_encode([
        'success' => true,
        'filename' => $filename,
        'content' => $content,
        'stats' => $stats[$filename] ?? []
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}