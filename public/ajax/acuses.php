<?php
// public/ajax/acuses.php

header('Content-Type: application/json');

// DEBUG
error_log("=== AJAX ACUSES - INICIANDO ===");

// Cargar main.php primero para la sesión
require_once __DIR__ . '/../../app_core/php/main.php';

// Verificar sesión según main.php
if (!isset($_SESSION['id'])) {
    error_log("❌ NO HAY SESIÓN");
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

error_log("✅ SESIÓN ENCONTRADA - User ID: " . $_SESSION['id']);

try {
    // Cargar explícitamente el ComunicadoController
    require_once __DIR__ . '/../../app_core/controllers/ComunicadoController.php';
    
    if (!isset($_POST['comunicado_id'])) {
        throw new Exception('ID de comunicado no proporcionado');
    }

    $comunicado_id = intval($_POST['comunicado_id']);
    error_log("PROCESANDO COMUNICADO ID: " . $comunicado_id);
    
    // Crear instancia del controlador
    $comunicadoController = new ComunicadoController();
    $acuses = $comunicadoController->obtenerAcusesComunicado($comunicado_id);
    
    error_log("ACUSES OBTENIDOS: " . count($acuses));
    
    echo json_encode([
        'success' => true,
        'acuses' => $acuses,
        'total' => count($acuses)
    ]);
    
} catch (Exception $e) {
    error_log("❌ ERROR: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>