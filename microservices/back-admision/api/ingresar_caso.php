<?php
// microservices/back-admision/api/ingresar_caso.php
require_once __DIR__ . '/../init.php';
require_once __DIR__ . '/../controllers/AdmissionController.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admissionController = new AdmissionController();
    
    $sr_hijo = trim($_POST['sr_hijo'] ?? '');
    $confirmar_reasignacion = $_POST['confirmar_reasignacion'] ?? false;
    
    if (empty($sr_hijo)) {
        echo json_encode(['success' => false, 'message' => 'El número de SR es requerido']);
        exit;
    }
    
    if ($confirmar_reasignacion) {
        // Procesar reasignación confirmada
        $result = $admissionController->confirmarReasignacion($sr_hijo, true);
    } else {
        // Procesar nuevo ingreso
        $result = $admissionController->ingresarCaso($sr_hijo);
    }
    
    // Redirigir según el resultado
    if ($result === 'gestionar-solicitud') {
        echo json_encode([
            'success' => true, 
            'redirect' => '/?vista=admision-gestionar-solicitud&sr=' . urlencode($sr_hijo)
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Error al procesar el caso'
        ]);
    }
}
?>