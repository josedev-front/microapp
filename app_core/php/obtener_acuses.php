<?php
// app_core/php/obtener_acuses.php

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/ComunicadoController.php';

header('Content-Type: application/json');

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

// Verificar que se proporcionó el ID del comunicado
if (!isset($_GET['comunicado_id']) || empty($_GET['comunicado_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de comunicado no proporcionado']);
    exit;
}

$comunicado_id = intval($_GET['comunicado_id']);
$comunicadoController = new ComunicadoController();

try {
    $acuses = $comunicadoController->obtenerAcusesComunicado($comunicado_id);
    echo json_encode(['success' => true, 'acuses' => $acuses]);
} catch (Exception $e) {
    error_log("Error en obtener_acuses.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}