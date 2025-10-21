<?php
// microservices/middy/api/admin_get_file.php

header('Content-Type: application/json; charset=utf-8');

try {
    // Cargar archivos del core
    $base_path = 'C:/xampp/htdocs/dashboard/vsm/microapp';
    $core_path = $base_path . '/app_core';
    require_once $core_path . '/config/helpers.php';
    require_once $core_path . '/php/main.php';

    // Verificar sesión
    if (!validarSesion()) {
        throw new Exception('No autorizado');
    }

    $usuario = obtenerUsuarioActual();
    if (!$usuario) {
        throw new Exception('Usuario no encontrado');
    }

    // Cargar Middy
    require_once __DIR__ . '/../init.php';
    
    $adminController = new Middy\Controllers\AdminController();

    // Verificar permisos
    if (!$adminController->checkAdminPermissions($usuario['role'])) {
        throw new Exception('No tienes permisos de administración');
    }

    // Obtener parámetros
    $filename = $_GET['file'] ?? '';
    if (empty($filename)) {
        throw new Exception('Nombre de archivo no especificado');
    }

    // Obtener contenido
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